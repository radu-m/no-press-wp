<?php
/**
 * Created by PhpStorm.
 * User: radu
 * Date: 12/14/15
 * Time: 11:19 AM
 */

class MON_JSON_API
{
    function __construct()
    {
        add_action('wp_redirect', array(&$this, 'template_redirect'));

    }

    function template_redirect(){

    }

}