<?php

/**
 * Class WPML_Element_Translation_Package
 *
 * @package wpml-core
 */
class WPML_Element_Translation_Package
{

    /**
     * create translation package
     *
     * @param object|int $post
     *
     * @return array
     */
    function create_translation_package($post)
    {
        global $sitepress, $iclTranslationManagement;

        if (empty($iclTranslationManagement->settings)) {
            $iclTranslationManagement->init();
        }

        $package = array();

        if (is_numeric($post)) {
            $post = get_post($post);
        }

        $post_type = $post->post_type;
        if (apply_filters('wpml_is_external', false, $post)) {

            foreach ($post->string_data as $key => $value) {
                $package['contents'][$key] = array(
                    'translate' => 1,
                    'data' => $iclTranslationManagement->encode_field_data($value, 'base64'),
                    'format' => 'base64'
                );
            }

            $package['contents']['original_id'] = array(
                'translate' => 0,
                'data' => $post->post_id,
            );
        } else {
            $home_url = get_home_url();
            if ($post_type == 'page') {
                $package['url'] = htmlentities($home_url . '?page_id=' . ($post->ID));
            } else {
                $package['url'] = htmlentities($home_url . '?p=' . ($post->ID));
            }

            $package['contents']['title'] = array(
                'translate' => 1,
                'data' => $iclTranslationManagement->encode_field_data($post->post_title, 'base64'),
                'format' => 'base64'
            );

            if (wpml_get_setting_filter(false, 'translated_document_page_url') === 'translate') {
                $package['contents']['URL'] = array(
                    'translate' => 1,
                    'data' => $iclTranslationManagement->encode_field_data($post->post_name, 'base64'),
                    'format' => 'base64'
                );
            }

            $package['contents']['body'] = array(
                'translate' => 1,
                'data' => $iclTranslationManagement->encode_field_data($post->post_content, 'base64'),
                'format' => 'base64'
            );

            if (!empty($post->post_excerpt)) {
                $package['contents']['excerpt'] = array(
                    'translate' => 1,
                    'data' => base64_encode($post->post_excerpt),
                    'format' => 'base64'
                );
            }

            $package['contents']['original_id'] = array(
                'translate' => 0,
                'data' => $post->ID
            );
            if (!empty($iclTranslationManagement->settings['custom_fields_translation'])) {
                $package = $this->add_custom_field_contents($package,
                    $post,
                    $iclTranslationManagement->settings['custom_fields_translation']);
            }

            foreach ((array)$sitepress->get_translatable_taxonomies(true, $post_type) as $taxonomy) {
                $terms = get_the_terms($post->ID, $taxonomy);
                if (!empty($terms)) {
                    foreach ($terms as $term) {
                        $package['contents']['t_' . $term->term_taxonomy_id] = array(
                            'translate' => 1,
                            'data' => $iclTranslationManagement->encode_field_data($term->name, 'csv_base64'),
                            'format' => 'csv_base64'
                        );
                    }
                }
            }
        }

        return $package;
    }

    /**
     * @param array $package
     * @param object $post
     * @param array $fields
     * @return array
     */
    private function add_custom_field_contents($package, $post, $fields)
    {
        global $iclTranslationManagement;

        foreach ($fields as $key => $op) {
            if ($op == 2) { // translate
                $custom_fields_values = array_values(array_filter(get_post_meta($post->ID, $key)));
                foreach ($custom_fields_values as $index => $custom_field_val) {
                    if (!is_scalar($custom_field_val)) {
                        continue;
                    }
                    $cf = $key . '-' . $index;
                    $package['contents']['field-' . $cf] = array(
                        'translate' => 1,
                        'data' => $iclTranslationManagement->encode_field_data(
                            $custom_field_val,
                            'base64'
                        ),
                        'format' => 'base64'
                    );
                    $package['contents']['field-' . $cf . '-name'] = array(
                        'translate' => 0,
                        'data' => $cf
                    );
                    $package['contents']['field-' . $cf . '-type'] = array(
                        'translate' => 0,
                        'data' => 'custom_field'
                    );
                }
            }
        }

        return $package;
    }

    /**
     * @param array $translation_package
     * @param array $prev_translation
     * @param int $job_id
     */
    public function save_package_to_job($translation_package, $job_id, $prev_translation)
    {
        global $wpdb;

        foreach ($translation_package['contents'] as $field => $value) {
            $job_translate = array(
                'job_id' => $job_id,
                'content_id' => 0,
                'field_type' => $field,
                'field_format' => isset($value['format']) ? $value['format'] : '',
                'field_translate' => $value['translate'],
                'field_data' => $value['data'],
                'field_data_translated' => isset($prev_translation[$field]) ? $prev_translation[$field] : '',
                'field_finished' => 0
            );
            if (isset($unchanged[$field])) {
                $job_translate['field_finished'] = 1;
            }

            $wpdb->hide_errors();
            $wpdb->insert($wpdb->prefix . 'icl_translate', $job_translate);
        }
    }

    /**
     * @param object $job
     * @param int $post_id
     * @param array $fields
     */
    public function save_job_custom_fields($job, $post_id, $fields)
    {

        $field_names = array();
        foreach ($fields as $field_name => $val) {
            if ($val == 2) { // should be translated
                // find it in the translation
                foreach ($job->elements as $el_data) {
                    if (strpos($el_data->field_data, (string)$field_name) === 0) {
                        if (preg_match("/field-(.*?)-name/", $el_data->field_type, $match)) {
                            $field_names[$field_name] = isset($field_names[$field_name])
                                ? $field_names[$field_name] : array();
                            $field_id_string = $match[1];
                            $explode = explode('-', $field_id_string);
                            $sub_id = $explode[count($explode) - 1];
                            $field_translation = false;
                            foreach ($job->elements as $v) {
                                if ($v->field_type === 'field-' . $field_id_string) {
                                    $field_translation = TranslationManagement::decode_field_data(
                                        $v->field_data_translated,
                                        $v->field_format
                                    );
                                }
                                if ($v->field_type == 'field-' . $field_id_string . '-type') {
                                    $field_type = $v->field_data;
                                }
                            }
                            if ($field_translation !== false && isset($field_type) && $field_type === 'custom_field') {
                                $field_translation = str_replace('&#0A;', "\n", $field_translation);
                                // always decode html entities  eg decode &amp; to &
                                $field_translation = html_entity_decode($field_translation);
                                $contents[$sub_id] = $field_translation;
                                $field_names[$field_name][$sub_id] = $field_translation;
                            }
                        }
                    }
                }
            }
        }

        $this->save_custom_field_values($field_names, $post_id);
    }

    private function save_custom_field_values($field_names, $post_id)
    {

        foreach ($field_names as $name => $contents) {
            delete_post_meta($post_id, $name);
            foreach ($contents as $val) {
                $single = count($contents) === 1;
                add_post_meta($post_id, $name, $val, $single);
            }
        }
    }
}