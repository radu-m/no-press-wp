<?php

class WPML_Lang_URL_Validator
{

    /** @var WP_Http $http_client */
    private $http_client;
    /** @var WPML_URL_Converter $wpml_url_converter */
    private $url_converter;
    /** @var array|WP_Error $response */
    private $response;
    /** @var  string $validation_url */
    private $posted_url;

    public function __construct($client, $wpml_url_converter, $posted_url)
    {
        $this->url_converter = $wpml_url_converter;
        $this->http_client = $client;
    }

    public function get_sample_url($sample_lang_code)
    {
        $abs_home = $this->url_converter->get_abs_home();

        return untrailingslashit(trailingslashit($abs_home) . $sample_lang_code);
    }

    public function get_validation_url($sample_lang_code)
    {
        $url_glue = false === strpos($this->posted_url, '?') ? '?' : '&';

        return $this->get_sample_url($sample_lang_code) . $url_glue . '____icl_validate_directory=1';
    }

    private function do_request($validation_url)
    {

        $this->response = $this->http_client->request(
            $validation_url,
            array('timeout' => 15, 'decompress' => false)
        );

        return $this->response;
    }

    public function validate_langs_in_dirs($sample_lang)
    {

        $response = $this->do_request($this->get_validation_url($sample_lang));
        if ((!is_wp_error($response)
                && ($response['response']['code'] == '200')
                && ($response['body'] === '<!--' . $this->get_sample_url($sample_lang) . '-->'))
            || (is_wp_error($response)
                && isset($response->errors['http_request_failed'])
                && $response->errors['http_request_failed'][0]
                === 'SSL certificate problem: self signed certificate')
        ) {
            $icl_folder_url_disabled = false;
        } else {
            $icl_folder_url_disabled = true;
        }

        return $icl_folder_url_disabled;
    }

    public function print_error_response()
    {

        $response = $this->response;

        $output = '';
        if (is_wp_error($response)) {
            $output .= '<strong>';
            $output .= $response->get_error_message();
            $output .= '</strong>';
        } elseif ($response['response']['code'] != '200') {
            $output .= '<strong>';
            $output .= sprintf(
                __('HTTP code: %s (%s)', 'sitepress'),
                $response['response']['code'],
                $response['response']['message']
            );
            $output .= '</strong>';
        } else {
            $output .= '<div style="width:100%;height:150px;overflow:auto;background-color:#fff;color:#000;font-family:Courier;font-style:normal;border:1px solid #aaa;">'
                . htmlentities($response['body']) . '</div>';
        }

        return $output;
    }

    public function print_explanation($def_lang_code, $sample_lang_code, $root = false)
    {
        global $sitepress;

        $sample_lang = $sitepress->get_language_details($sample_lang_code);
        $def_lang = $sitepress->get_language_details($def_lang_code);
        $output = '<span class="explanation-text">(';

        $output .= sprintf(
            '%s - %s, %s - %s',
            trailingslashit($this->get_sample_url($root ? $def_lang_code : '')),
            $def_lang['display_name'],
            trailingslashit($this->get_sample_url($sample_lang_code)),
            $sample_lang['display_name']
        );
        $output .= ')</span>';

        return $output;
    }
}