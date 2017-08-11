<?php
/**
 * Created by PhpStorm.
 * User: radu
 * Date: 3/22/16
 * Time: 12:52 PM
 */

class JSON_API_Locale_Controller
{
    public function get_registered_languages(){
        $args = array('skip_missing' => 1);
        return ['data' => apply_filters( 'wpml_active_languages', null, $args)];
    }
}