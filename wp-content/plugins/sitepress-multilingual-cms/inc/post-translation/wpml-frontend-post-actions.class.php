<?php

class WPML_Frontend_Post_Actions extends WPML_Post_Translation
{

    /**
     * @param int $pidd
     * @param WP_Post $post
     *
     * @return void
     */
    public function save_post_actions($pidd, $post)
    {
        global $sitepress;

        wp_defer_term_counting(true);
        $post = isset($post) ? $post : get_post($pidd);
        // exceptions
        if (!$this->has_save_post_action($post)) {
            wp_defer_term_counting(false);
            return;
        }

        $default_language = $sitepress->get_default_language();

        // allow post arguments to be passed via wp_insert_post directly and not be expected on $_POST exclusively
        $post_vars = (array)$_POST;
        foreach ((array)$post as $k => $v) {
            $post_vars[$k] = $v;
        }

        $post_vars['post_type'] = isset($post_vars['post_type']) ? $post_vars['post_type'] : $post->post_type;

        $post_id = isset($post_vars['post_ID']) ? $post_vars['post_ID']
            : $pidd; //latter case for XML-RPC publishing
        $language_code = $this->get_save_post_lang($post_id, $sitepress);

        $trid = $this->get_save_post_trid($post_id, $post->post_status);
        // after getting the right trid set the source language from it by referring to the root translation
        // of this trid, in case no proper source language has been set yet
        $source_language = isset($source_language)
            ? $source_language : $this->get_save_post_source_lang($trid, $language_code, $default_language);

        $this->maybe_set_elid($trid, $post->post_type, $language_code, $post_id, $source_language);

        $translation_sync = $this->get_sync_helper();

        $original_id = $this->get_original_element($post_id);
        if ($original_id) {
            $translation_sync->sync_with_translations($original_id, $post_vars);
        }

        require_once ICL_PLUGIN_PATH . '/inc/cache.php';
        icl_cache_clear($post_vars['post_type'] . 's_per_language', true);
        wp_defer_term_counting(false);
    }

    protected function get_save_post_source_lang($trid, $language_code, $default_language)
    {
        $post_id = $this->get_element_id($trid, $language_code);

        return $post_id ? $this->get_source_lang_code($post_id) : null;
    }

    /**
     * @param Integer $post_id
     * @param String $post_status
     * @return null|int
     */
    protected function get_save_post_trid($post_id, $post_status)
    {

        return $this->get_element_trid($post_id);
    }
}