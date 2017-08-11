<?php

require_once 'wpml-wordpress-actions.class.php';

/**
 * Class WPML_Post_Duplication
 *
 * @package    wpml-core
 * @subpackage post-translation
 */
class WPML_Post_Duplication
{

    function get_duplicates($master_post_id)
    {
        global $wpdb, $wpml_post_translations;
        $duplicates = array();

        $post_ids_query
            = " SELECT post_id
                            FROM {$wpdb->postmeta}
                            WHERE meta_key='_icl_lang_duplicate_of'
                                AND meta_value = %d
                                AND post_id <> %d";
        $post_ids_prepare = $wpdb->prepare($post_ids_query, array($master_post_id, $master_post_id));
        $post_ids = $wpdb->get_col($post_ids_prepare);

        foreach ($post_ids as $post_id) {
            $language_code = $wpml_post_translations->get_element_lang_code($post_id);
            $duplicates[$language_code] = $post_id;
        }

        return $duplicates;
    }

    function make_duplicate($master_post_id, $lang)
    {
        global $wpml_post_translations, $wpml_language_resolution;

        static $duplicated_post_ids;
        if (!isset($duplicated_post_ids)) {
            $duplicated_post_ids = array();
        }

        //It is already done? (avoid infinite recursions)
        if (!$wpml_language_resolution->is_language_active($lang)
            || in_array($master_post_id . '|' . $lang, $duplicated_post_ids)
        ) {
            return true;
        }
        $duplicated_post_ids[] = $master_post_id . '|' . $lang;

        global $sitepress, $sitepress_settings;

        do_action('icl_before_make_duplicate', $master_post_id, $lang);

        $master_post = get_post($master_post_id);

        $is_duplicated = false;
        $translations = $wpml_post_translations->get_element_translations($master_post_id, false, false);

        if (isset($translations[$lang])) {
            $post_array['ID'] = $translations[$lang];
            if (WPML_WordPress_Actions::is_bulk_trash($post_array['ID']) || WPML_WordPress_Actions::is_bulk_untrash($post_array['ID'])) {
                return true;
            }
            $is_duplicated = get_post_meta($translations[$lang], '_icl_lang_duplicate_of', true);
        }

        $post_array['post_author'] = $master_post->post_author;
        $post_array['post_date'] = $master_post->post_date;
        $post_array['post_date_gmt'] = $master_post->post_date_gmt;
        $post_array['post_content'] = addslashes_gpc(apply_filters('icl_duplicate_generic_string',
            $master_post->post_content,
            $lang,
            array(
                'context' => 'post',
                'attribute' => 'content',
                'key' => $master_post->ID
            )));
        $post_array['post_title'] = addslashes_gpc(apply_filters('icl_duplicate_generic_string',
            $master_post->post_title,
            $lang,
            array(
                'context' => 'post',
                'attribute' => 'title',
                'key' => $master_post->ID
            )));
        $post_array['post_excerpt'] = addslashes_gpc(apply_filters('icl_duplicate_generic_string',
            $master_post->post_excerpt,
            $lang,
            array(
                'context' => 'post',
                'attribute' => 'excerpt',
                'key' => $master_post->ID
            )));

        if (isset($sitepress_settings['sync_post_status']) && $sitepress_settings['sync_post_status']) {
            $sync_post_status = true;
        } else {
            $sync_post_status = (!isset($post_array['ID']) || ($sitepress_settings['sync_delete'] && $master_post->post_status == 'trash') || $is_duplicated);
        }

        if ($sync_post_status || (isset($post_array['ID']) && get_post_status($post_array['ID']) === 'auto-draft')) {
            $post_array['post_status'] = $master_post->post_status;
        }

        $post_array['comment_status'] = $master_post->comment_status;
        $post_array['ping_status'] = $master_post->ping_status;
        $post_array['post_name'] = $master_post->post_name;

        if ($master_post->post_parent) {
            $parent = icl_object_id($master_post->post_parent,
                $master_post->post_type,
                false,
                $lang);
            $post_array['post_parent'] = $parent;
        }

        $post_array['menu_order'] = $master_post->menu_order;
        $post_array['post_type'] = $master_post->post_type;
        $post_array['post_mime_type'] = $master_post->post_mime_type;

        $trid = $sitepress->get_element_trid($master_post->ID, 'post_' . $master_post->post_type);

        $id = $this->save_duplicate($post_array, $lang);

        require_once ICL_PLUGIN_PATH . '/inc/cache.php';
        icl_cache_clear();

        global $ICL_Pro_Translation;
        /** @var WPML_Pro_Translation $ICL_Pro_Translation */
        if ($ICL_Pro_Translation) {
            $ICL_Pro_Translation->_content_fix_links_to_translated_content($id, $lang);
        }

        if (!is_wp_error($id)) {
            $ret = $this->run_wpml_actions($master_post, $trid, $lang, $id, $post_array);
        } else {
            $ret = false;
        }

        return $ret;
    }

    private function run_wpml_actions($master_post, $trid, $lang, $id, $post_array)
    {
        global $sitepress, $wpdb, $iclTranslationManagement;

        $master_post_id = $master_post->ID;
        $sitepress->set_element_language_details($id, 'post_' . $master_post->post_type, $trid, $lang);
        $iclTranslationManagement = $iclTranslationManagement ? $iclTranslationManagement : wpml_load_core_tm();
        $iclTranslationManagement->save_post_actions($id, get_post($id), ICL_TM_DUPLICATE);
        $this->sync_duplicate_password($master_post_id, $id);
        $this->sync_page_template($master_post_id, $id);
        $this->duplicate_fix_children($master_post_id, $lang);

        // make sure post name is copied
        $wpdb->update($wpdb->posts, array('post_name' => $master_post->post_name), array('ID' => $id));
        update_post_meta($id, '_icl_lang_duplicate_of', $master_post->ID);

        if ($sitepress->get_option('sync_post_taxonomies')) {
            $this->duplicate_taxonomies($master_post_id, $lang);
        }
        $this->duplicate_custom_fields($master_post_id, $lang);

        // Duplicate post format after the taxonomies because post format is stored
        // as a taxonomy by WP.
        if ($sitepress->get_setting('sync_post_format')) {
            $_wp_post_format = get_post_format($master_post_id);
            set_post_format($id, $_wp_post_format);
        }

        if ($sitepress->get_setting('sync_comments_on_duplicates')) {
            $this->duplicate_comments($master_post_id, $id);
        }

        $status_helper = wpml_get_post_status_helper();
        $status_helper->set_status($id, ICL_TM_DUPLICATE);
        $status_helper->set_update_status($id, false);
        do_action('icl_make_duplicate', $master_post_id, $lang, $post_array, $id);
        clean_post_cache($id);

        return $id;
    }

    private function sync_page_template($master_post_id, $duplicate_post_id)
    {
        $_wp_page_template = get_post_meta($master_post_id, '_wp_page_template', true);
        if (!empty($_wp_page_template)) {
            update_post_meta($duplicate_post_id, '_wp_page_template', $_wp_page_template);
        }
    }

    private function duplicate_comments($master_post_id, $translated_id)
    {
        global $sitepress;

        remove_filter('comments_clauses', array($sitepress, 'comments_clauses'), 10);
        $comments_on_master = get_comments(array('post_id' => $master_post_id));
        $comments_on_translation = get_comments(array('post_id' => $translated_id, 'status' => 'any'));
        add_filter('comments_clauses', array($sitepress, 'comments_clauses'), 10, 2);
        foreach ($comments_on_translation as $comment) {
            wp_delete_comment($comment->comment_ID, true);
            clean_comment_cache($comment->comment_ID);
        }

        $iclTranslationManagement = wpml_load_core_tm();
        foreach ($comments_on_master as $comment) {
            $iclTranslationManagement->duplication_insert_comment($comment->comment_ID);
            clean_comment_cache($comment->comment_ID);
        }

        wp_update_comment_count_now($master_post_id);
        wp_update_comment_count_now($translated_id);
    }

    private function save_duplicate($post_array, $lang)
    {
        if (isset($post_array['ID'])) {
            $id = wp_update_post($post_array);
        } else {
            $create_post_helper = wpml_get_create_post_helper();
            $id = $create_post_helper->icl_insert_post($post_array, $lang);
        }

        return $id;
    }

    private function duplicate_fix_children($master_post_id, $lang)
    {
        global $wpdb;

        $post_type = $wpdb->get_var(
            $wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $master_post_id)
        );
        $master_children = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_parent=%d AND post_type != 'revision'",
                $master_post_id
            )
        );
        $dup_parent = icl_object_id($master_post_id, $post_type, false, $lang);

        if ($master_children) {
            foreach ($master_children as $master_child) {
                $dup_child = icl_object_id($master_child, $post_type, false, $lang);
                if ($dup_child) {
                    $wpdb->update($wpdb->posts, array('post_parent' => $dup_parent), array('ID' => $dup_child));
                }
                $this->duplicate_fix_children($master_child, $lang);
            }
        }
    }

    private function duplicate_taxonomies($master_post_id, $lang)
    {
        global $sitepress;

        $post_type = get_post_field('post_type', $master_post_id);
        $taxonomies = get_object_taxonomies($post_type);
        $trid = $sitepress->get_element_trid($master_post_id, 'post_' . $post_type);
        if ($trid) {
            $translations = $sitepress->get_element_translations($trid, 'post_' . $post_type, false, false, true);
            if (isset($translations[$lang])) {
                $duplicate_post_id = $translations[$lang]->element_id;
                /* If we have an existing post, we first of all remove all terms currently attached to it.
                 * The main reason behind is the removal of the potentially present default category on the post.
                 */
                wp_delete_object_term_relationships($duplicate_post_id, $taxonomies);
            } else {
                return false; // translation not found!
            }
        }

        $term_helper = wpml_get_term_translation_util();
        $term_helper->duplicate_terms($master_post_id, $lang);

        return true;
    }

    private function sync_duplicate_password($master_post_id, $duplicate_post_id)
    {
        global $wpdb;

        if (post_password_required($master_post_id)) {
            $sql = $wpdb->prepare("UPDATE {$wpdb->posts} AS dupl,
									(SELECT org.post_password FROM {$wpdb->posts} AS org WHERE ID = %d ) AS pwd
									SET dupl.post_password = pwd.post_password
									WHERE dupl.ID = %d",
                array($master_post_id, $duplicate_post_id));
            $wpdb->query($sql);
        }
    }

    private function duplicate_custom_fields($master_post_id, $lang)
    {
        /**
         * @var wpdb $wpdb
         * @var SitePress $sitepress
         */
        global $wpdb, $sitepress;

        $duplicate_post_id = false;
        $post_type = get_post_field('post_type', $master_post_id);

        $trid = $sitepress->get_element_trid($master_post_id, 'post_' . $post_type);
        if ($trid) {
            $translations = $sitepress->get_element_translations($trid, 'post_' . $post_type);
            if (isset($translations[$lang])) {
                $duplicate_post_id = $translations[$lang]->element_id;
            } else {
                return false; // translation not found!
            }
        }

        $default_exceptions = WPML_Config::get_custom_fields_translation_settings();
        $exceptions = apply_filters('wpml_duplicate_custom_fields_exceptions', array());
        $exceptions = array_merge($exceptions, $default_exceptions);
        $exceptions = array_unique($exceptions);

        $exceptions_in = !empty($exceptions)
            ? 'AND meta_key NOT IN ( ' . wpml_prepare_in($exceptions) . ') ' : '';

        $from_where_string = "FROM {$wpdb->postmeta} WHERE post_id = %d " . $exceptions_in;

        $post_meta_master = $wpdb->get_results("SELECT meta_key, meta_value " . $wpdb->prepare($from_where_string,
                $master_post_id));
        $wpdb->query("DELETE " . $wpdb->prepare($from_where_string, $duplicate_post_id));

        foreach ($post_meta_master as $post_meta) {
            $is_serialized = is_serialized($post_meta->meta_value);
            $meta_data = array(
                'context' => 'custom_field',
                'attribute' => 'value',
                'key' => $post_meta->meta_key,
                'is_serialized' => $is_serialized,
                'post_id' => $duplicate_post_id,
                'master_post_id' => $master_post_id,
            );

            /**
             * @deprecated use 'wpml_duplicate_generic_string' instead, with the same arguments
             */
            $icl_duplicate_generic_string = apply_filters('icl_duplicate_generic_string',
                $post_meta->meta_value,
                $lang,
                $meta_data);
            $post_meta->meta_value = $icl_duplicate_generic_string;

            $wpml_duplicate_generic_string = apply_filters('wpml_duplicate_generic_string',
                $post_meta->meta_value,
                $lang,
                $meta_data);
            $post_meta->meta_value = $wpml_duplicate_generic_string;

            if (!is_serialized($post_meta->meta_value)) {
                $post_meta->meta_value = maybe_serialize($post_meta->meta_value);
            }

            $wpdb->insert($wpdb->postmeta,
                array(
                    'post_id' => $duplicate_post_id,
                    'meta_key' => $post_meta->meta_key,
                    'meta_value' => $post_meta->meta_value
                ),
                array('%d', '%s', '%s'));
        }

        return true;
    }
}
