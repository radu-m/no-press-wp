<?php

class MON_JSON_API
{
    function __construct()
    {
        add_action('wp_redirect', array(&$this, 'template_redirect'));

    }

    function template_redirect(){

    }

}
