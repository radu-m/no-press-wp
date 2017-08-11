<?php
/*
Plugin Name: NO-PRESS JSON API
Description: Creates JSON structures from HTML strings returned by JSON API (http://wordpress.org/plugins/json-api/)
Version: 1.0.0
Author: NoPress
Author URI: http://no-press.com
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

defined('ABSPATH') or die('No script kiddies please!');

$dir = no_press_json_api_dir();

@require_once "$dir/assets/utils.php";
@require_once "$dir/singletons/api.php";
@require_once "$dir/assets/RecursiveDOMIterator.php";


function no_press_json_api_init()
{
    add_no_press_theme_filters();

//    $_GET["json_unescaped_unicode"] = 1;
//    print_r($_GET);

    ///
//    $_SERVER['REQUEST_URI'] = str_replace('no-press-api', 'api', $_SERVER['REQUEST_URI']);
    add_filter('json_api_encode', 'refactor_json');

    add_filter('json_api_encode', function ($data) {
        $out = array(
            'code' => 200,
            'data' => $data
        );

        return $out;
    });
}

// Add initialization and activation hooks
add_action('init', 'no_press_json_api_init');
register_activation_hook("$dir/no-press-json-api.php", 'no_press_json_api_activation');
register_deactivation_hook("$dir/no-press-json-api.php", 'no_press_json_api_deactivation');


function add_no_press_theme_filters()
{
    add_filter('rewrite_rules_array', 'no_press_json_api_rewrites');
    /**
     * Configuration for JSON-API plugin - custom controllers
     */

    ///
    function add_downloads_controller($controllers)
    {
        $controllers[] = 'downloads';
        return $controllers;
    }
    add_filter('json_api_controllers', 'add_downloads_controller');

    function set_downloads_controller_path()
    {
        return no_press_json_api_dir() . "/controllers/downloads.php";
    }
    add_filter('json_api_downloads_controller_path', 'set_downloads_controller_path');

    ///
    function add_locale_controller($controllers)
    {
        $controllers[] = 'locale';
        return $controllers;
    }
    add_filter('json_api_controllers', 'add_locale_controller');

    function set_locale_controller_path()
    {
        return no_press_json_api_dir() . "/controllers/locale.php";
    }
    add_filter('json_api_locale_controller_path', 'set_locale_controller_path');

    ///
    function add_menus_controller($controllers)
    {
        $controllers[] = 'menus';
        return $controllers;
    }
    add_filter('json_api_controllers', 'add_menus_controller');

    function set_menus_controller_path()
    {
        return no_press_json_api_dir() . "/controllers/menus.php";
    }
    add_filter('json_api_menus_controller_path', 'set_menus_controller_path');

    ///
    function add_no_press_posts_controller($controllers)
    {
        $controllers[] = 'no_press_posts';
        return $controllers;
    }
    add_filter('json_api_controllers', 'add_no_press_posts_controller');

    function set_no_press_posts_controller_path()
    {
        return no_press_json_api_dir() . "/controllers/no-press-posts.php";
    }
    add_filter('json_api_no_press_posts_controller_path', 'set_no_press_posts_controller_path');

    ///
    function add_forms_controller($controllers)
    {
        $controllers[] = 'forms';
        return $controllers;
    }
    add_filter('json_api_controllers', 'add_forms_controller');

    function set_forms_controller_path()
    {
        return no_press_json_api_dir() . "/controllers/forms.php";
    }
    add_filter('json_api_forms_controller_path', 'set_forms_controller_path');

}

function refactor_json($data)
{
    // test case
//    $content = $data['post']['content'];
//    $t = array("one", "two", "3", 4);
//    $data = ["1st" => $t, "2nd" => $t, "3rd" => $t, "4th" => $t, "5th" => array($t, "Mads Frost, PhD, is also a post doctoral researcher at the IT University of Copenhagen (ITU). He successfully defended his PhD dissertation in 2014 on the topic of Personal Health Technologies. In 2013, Mads co-founded NoPress based on this research. To learn more visit <a href=\"http://madsfrost.dk/\">http://madsfrost.dk/</a>.")];

    function unsafe_arr_walk_recursive($arr, $recursion = 0)
    {

        $refactored = [];
        // unpack instances from json-api plugin so we only have arrays from now on
        foreach ($arr as $k => $v) {
            $current = [];
            switch (gettype($v)) {
                case 'object':
                    $vAsArr = Utils::object_to_array_deep($v);
                    $current = unsafe_arr_walk_recursive($vAsArr, $recursion++);
                    break;
                case 'array':
                    $current = unsafe_arr_walk_recursive($v, $recursion++);
                    break;
                case 'integer':
                    $current = $v;
                    break;
                case 'double':
                    $current = $v;
                    break;
                case 'bool':
                    $current = $v ? 'true' : 'false';
                    break;
                case 'string':
                    if (strstr($v, '<') && strstr($v, '>')) {
                        $current = buildDOMTreeByXPath($v);
                    } else {
                        $current = $v;
                    }
                    break;
            }
            $refactored[$k] = $current;
        }
        return $refactored;
    }

    return unsafe_arr_walk_recursive($data);
}

function get_node_attributes($n)
{
    $attrs = null;
    foreach ($n->attributes as $attr) {
        $attrs[$attr->name] = $attr->value;
    }
    return $attrs;
}

function get_node_details($n)
{
    $details = array(
        "tag" => $n->tagName,
        "attrs" => get_node_attributes($n),
        "value" => $n->nodeType === XML_TEXT_NODE ? trim($n->textContent) : null,
        "children" => $n->hasChildNodes() ? [] : false,
        "nodeType" => $n->nodeType
    );

/*    if($details['value']){
        print_r('>>>>>>>');
        print_r($details['value']);
        print_r('<<<<<<<');
    }*/

    // remove empty nodes
    // will be false for elements that can have children and null for those which can't
    if (is_null($details['value']) && is_null($details['children'])) {
        return false;
    }
    return $details;
}

function buildDOMTreeByXPath($raw)
{
    $dom = null;
    $nodes = null;

    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;

    $dom->loadHTML("<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"></head></head><body>$raw</body></html>");

    $xpath = new DOMXPath($dom);

    $tree = ['tag' => 'root', 'children' => []];

    $prevPathArr = false;
    $prevNode =& $tree;

    $nodes = $xpath->query('//node()'); // this includes text nodes

    foreach ($nodes as $node) {

        if ($node->nodeType === XML_TEXT_NODE && strlen(trim($node->textContent)) == 0) {
            // skip empty text-nodes
            continue;
        }

        if ($node->tagName != 'html' && $node->tagName != 'head' && $node->tagName != 'body') {
            $nodePath = str_replace('/html/body/', '', $node->getNodePath());
            $pathArr = mb_split('/', $nodePath);

            if ($prevPathArr && count($pathArr) == count($prevPathArr)) {
                // sibling nodes
                $nodeDetails = get_node_details($node);
                if ($nodeDetails !== false) {
                    $prevNode['children'][end($pathArr)] = $nodeDetails;
                }
            } else if ($prevPathArr && count($pathArr) > count($prevPathArr)) {
                // child
                $nodeDetails = get_node_details($node);
                if ($nodeDetails !== false) {
                    $prevNode =& $prevNode['children'][end($prevPathArr)];
                    $prevNode['children'][end($pathArr)] = $nodeDetails;
                }
            } else {
                // start from root, as we don't keep reference to parent nodes
                $prevNode =& $tree;
                foreach ($pathArr as $key) {
                    if (isset($prevNode['children'][$key])) {
                        $prevNode =& $prevNode['children'][$key];
                    } else {
                        $nodeDetails = get_node_details($node);
                        if ($nodeDetails !== false) {
                            $prevNode['children'][$key] = $nodeDetails;
                        }
                    }
                }
            }
            $prevPathArr = $pathArr;
        }
    }
    return $tree;
}


function no_press_json_api_activation()
{
    // Add the rewrite rule on activation
    global $wp_rewrite;
    add_filter('rewrite_rules_array', 'no_press_json_api_rewrites');
    $wp_rewrite->flush_rules();
}

function no_press_json_api_deactivation()
{
    // Remove the rewrite rule on deactivation
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function no_press_json_api_rewrites($wp_rules)
{
    $base = no_press_json_api_dir() . '/no-press-api/'; // get_option('json_api_base', 'api');
    if (empty($base)) {
        return $wp_rules;
    }
    $json_api_rules = array(
        "$base\$" => 'index.php?json=info',
        "$base/(.+)\$" => 'index.php?json=$matches[1]'
    );

    return array_merge($json_api_rules, $wp_rules);
}

function no_press_json_api_dir()
{
    if (defined('MON_JSON_API_DIR') && file_exists(MON_JSON_API_DIR)) {
        return MON_JSON_API_DIR;
    } else {
        return dirname(__FILE__);
    }
}
