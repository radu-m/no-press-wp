<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 12/21/15
 * Time: 5:40 PM
 */
class JSON_API_Menus_Controller
{
    /*    protected static $collection = [];

        function __construct(){
            global $json_api;

            self::$collection['menus'] = $this->get_menus();
    //        self::$collection['sidebars'] = $this->get_sidebars();
    //        self::$collection['footers'] = $this->get_footers();
        }*/

    /**
     * TODO: refactor and possibly merge the 2 methods;
     */

    public function get_menus()
    {
        $menus = get_terms('nav_menu', array('hide_empty' => true));
        $menuItems = [];
        $menuItems['data'] = [];
        $menuItems['code'] = 200;

        foreach ($menus as $k => $m) {
            $menuItems['data'][$m->name] = wp_get_nav_menu_items($m->term_id);
        }

        if (count($menus) === 0) {
            $menuItems['code'] = 505;
            $menuItems['message'] = 'No menus were found.';
        }

        return $menuItems;
    }

    public function get_main_menu()
    {
        if(!$_GET['m']){
            $_GET['m'] = 'header-navigation';
        }

        $mData = $this->get_nav_menu();

//        $args = array('skip_missing' => 1);

//        $mData['data']['languages'] = apply_filters( 'wpml_active_languages', null, $args);

        return $mData;
    }

    public function get_nav_menu()
    {
        if (!$_GET['m'] || empty($_GET['m'])) {
            return;
        }

        $m_identity = trim(str_replace('/', '', $_GET['m'])); //your theme location name
        $menu_post_id = null;
        $menu = [];
//        $registered_menus = get_terms('nav_menu', array('hide_empty' => false));
        $menu_obj = null;
        $theme_locations = get_nav_menu_locations();

        if (count($m_identity) > 0 && !is_numeric($m_identity)) {
            if (!$menu_obj) {
                $menu_obj = get_term($theme_locations[$m_identity], 'nav_menu');
            }
        } else {
            // or a term_id
            $menu_obj = wp_get_nav_menu_object($m_identity);
        }

//        print_r('---------------------');
//        print_r($menu_obj);
//        print_r('---------------------');

        $menu['id'] = $menu_obj->term_id;
        $menu['name'] = $menu_obj->name;
        $menu['slug'] = $menu_obj->slug;
        $menu['taxonomy'] = $menu_obj->taxonomy;
        $menu['count'] = $menu_obj->count;

        foreach( $theme_locations as $location => $menu_id ){
            if( $menu_obj->term_id == $menu_id ){
                $menu['theme_location'] = $location;
            }
        }

        $items = wp_get_nav_menu_items($menu_obj->term_id);

//        print_r($items);

        // add destination slug to the items
        foreach ($items as $mKey => $mItem) {
            $tSlug = null;

            if ($mItem->type == 'taxonomy' && $mItem->object == 'category') {
                $target = get_category($mItem->object_id);
                $tSlug = $target->slug;
            } else if ($mItem->type == 'post_type') {
                $target = get_post($mItem->object_id);
                $tSlug = $target->post_name;
            }


            $menu['items'][$mKey] = object_to_array($mItem);
            $menu['items'][$mKey]['object_slug'] = $tSlug;
        }

        /**
         * TODO: after the template is ready, remove all unused fields from ['items']
         */
        return ['data' => $menu];
    }

    public function get_archives_as_menu()
    {
        $links = [];

        $args = array(
            'type' => 'monthly',
            'limit' => '',
            'format' => 'html',
            'before' => '',
            'after' => '',
            'show_post_count' => false,
            'echo' => 1,
            'order' => 'DESC',
            'post_type' => 'post'
        );

        $archivesHtml = wp_get_archives($args);

        foreach ($archivesHtml as $link) {
            $link = str_replace(array('<li>', "\n", "\t", "\s"), '', $link);
            if ('' != $link)
                $links[] = $link;
            else
                continue;
        }

        return $links;
    }
}