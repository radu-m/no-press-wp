<?php

/**
 * Define constants
 */
define('NO_PRESS_PLUGINS_DIR', trailingslashit(get_template_directory() . '/plugins'));

/** REMOVE THIS **/
function add_cors_http_header(){
    header("Access-Control-Allow-Origin: *");
}

add_action('init','add_cors_http_header');

/**
 * Include Visual Composer functions
 */
if (class_exists('WPBakeryVisualComposerAbstract')) {
    require_once('functions-visual-composer.php');
}

require_once('no_press_templates/vc_shortcodes.php');

/**
 * TGM Plugin Activation
 */
function no_press_tgmpa()
{
    $plugins = array(
        array(
            'name' => 'Visual Composer',
            'slug' => 'js_composer',
            'source' => NO_PRESS_PLUGINS_DIR . 'js_composer.zip',
            'required' => true,
        )
    );

    $config = array(
        'domain' => 'no-press',
        'parent_menu_slug' => 'plugins.php',
        'parent_url_slug' => 'plugins.php',
        'strings' => array(
            'menu_title' => __('Required Plugins', 'qualia_td'),
        ),
    );

    tgmpa($plugins, $config);
}

add_action('tgmpa_register', 'no_press_tgmpa');

/**
 * Register navigation location
 */
function no_press_action_register_menus()
{
    register_nav_menus(array(
        'header-navigation' => __('Header Navigation', 'no-press-theme'),
        'resources-context-nav' => __('Resources sidebar Navigation', 'no-press-theme'),
        'careers-nav' => __('Careers Navigation', 'no-press-theme'),
        'blog-categories-nav' => __('Blog sidebar Navigation', 'no-press-theme'),
        'news-categories-nav' => __('News sidebar Navigation', 'no-press-theme'),
        'footer-resources' => __('Footer Navigation 1st', 'no-press-theme'),
        'footer-privacy' => __('Footer Navigation 3rd', 'no-press-theme'),
        'footer-extra-pages' => __('Extra pages', 'no-press-theme')
    ));
}

add_action('init', 'no_press_action_register_menus');


/**
 * Define custom shortcodes for Visual Editor
 */

add_shortcode('no_press_text_box', 'no_press_text_box_reg');
function no_press_text_box_reg($attrs, $content = null)
{
    $a = shortcode_atts(array(
        "extra-css-class" => '',
        "text-box-title" => '',
        "text-box-subtitle" => '',
//        "box-extra-link" => null,
        "image-link" => '',
        "image_id" => '',
        "image-class" => ''
    ), $attrs);

//    print_r('#############', $attrs);
    $raw_image = new WP_Query(array('post_type' => 'attachment', 'attachment_id' => $a['image_id']));
    $image_url = $raw_image->posts[0]->guid;
    $image_href = trim(urldecode($a['image-link']));

    $a['box-extra-link'] = $attrs['box-extra-link'] ? trim(urldecode($attrs['box-extra-link'])) : null;

    if (empty($attrs['extra-css-class'])) {
        $a['extra-css-class'] .= empty($image_url) ? '' : 'image-left';
    }

    return <<<CONTENT
    <div text-box=""
        class="no-press-text-box {$a['extra-css-class']}"
        img-href="{$image_href}"
        img-class="{$a['image-class']}"
        img-src="{$image_url}"
        box-title="{$a['text-box-title']}"
        box-subtitle="{$a['text-box-subtitle']}"
        content="{$content}"
        box-extra-link="{$a['box-extra-link']}">
    </div>
CONTENT;

    /*    return <<<CONTENT
    <div text-box="" img-src="'{$image_url}'" class="{$a['class']}">
        <dl>
            <dt class='box-header' style='background-image: url( $image_url );'>
                <h2>{$a['text-box-title']}</h2>
                <h3>{$a['text-box-subtitle']}</h3>
            </dt>
            <dd class="box-content">
                    {$a['box-content']}
                </div>
            </dd>
        </dl>
    </div>
    CONTENT;*/
}


function clean_btn_url($rawUrl)
{
    $clean = urldecode($rawUrl);
    $clean = str_replace('url:', '', $clean);
    $clean = str_replace('/backend', '', $clean);
    $clean = str_replace('||', '', $clean);
    return $clean;
}

add_shortcode('no_press_button', 'no_press_button_reg');
function no_press_button_reg($attrs)
{
    $a = shortcode_atts(array(
        "button-label" => '',
        "button-link" => '',
        "button-class" => '',
        "button-action" => ''
    ), $attrs);

    $cleanUrl = clean_btn_url($a['button-link']);

    $buttonAction = $a['button-action'] != '' ? $a['button-action'] : ($a['button-link'] != '' ? 'followUrl()' : false);

    return <<<CONTENT
    <button no-press-button="{$a['button-label']}"
        class="no-press-btn {$a['button-class']}"
        button-action="{$buttonAction}"
        button-url="{$cleanUrl}">
    </button>
CONTENT;
}

add_shortcode('no_press_headline', 'no_press_section_header_reg');
function no_press_section_header_reg($attrs)
{
    $a = shortcode_atts(array(
        "headline-text" => '',
        "headline_html_tag" => 'h1',
        "headline-class" => ''
    ), $attrs);

    return <<<CONTENT
    <div class="no-press-headline {$a['headline-class']}">
       <{$a['headline_html_tag']}>{$a['headline-text']}</{$a['headline_html_tag']}>
    </div>
CONTENT;
}

add_shortcode('no_press_ng_directive', 'no_press_ng_directive_reg');
function no_press_ng_directive_reg($attrs)
{
    $a = shortcode_atts(array(
        "directive-name" => '',
        "directive-attrs" => '',
        "extra-class" => ''
    ), $attrs);

    if (!$a['directive-name']) {
        return '';
    }

    return <<<CONTENT
    <div {$a['directive-name']} {$a['directive-attrs']} class="{$a['extra-class']}"></div>
CONTENT;
}

add_shortcode('no_press_contact_details', 'no_press_contact_details_reg');
function no_press_contact_details_reg($attrs)
{
    $a = shortcode_atts(array(
        "contact-phone" => '',
        "contact-email" => '',
        "contact-street" => '',
        "contact-zip" => '',
        "contact-city" => '',
        "contact-country" => '',
        "headline-class" => ''
    ), $attrs);

    return <<<CONTENT
    <div contact-details
        data-contact-phone="{$a['contact-phone']}"
        data-contact-email="{$a['contact-email']}"
        data-contact-street="{$a['contact-street']}"
        data-contact-zip="{$a['contact-zip']}"
        data-contact-city="{$a['contact-city']}"
        data-contact-country="{$a['contact-country']}"
        data-options="vm.contactDetailsOptions"
        class="contact-details {$a['extra-class']}">
        </div>
CONTENT;
}

add_shortcode('no_press_custom_menu', 'no_press_custom_nav_menu_reg');
function no_press_custom_nav_menu_reg($attrs)
{
    $a = shortcode_atts(array(
        "nav_menu" => '',
        "extra-class" => ''
    ), $attrs);

    $menu_obj = null;

    $theme_locations = get_nav_menu_locations();
    $menu_obj = get_term($theme_locations[$a['nav_menu']], 'nav_menu');

    foreach ($theme_locations as $location => $menu_id) {
        if ($menu_obj->term_id == $menu_id) {
            $menu_obj->theme_location = $location;
        }
    }

    return <<<CONTENT
    <div nav-menu="{$menu_obj->slug}"
        menu-location="{$menu_obj->theme_location}"
        menu-config="vm.menusConfig['{$menu_obj->theme_location}']"
        class="no-press-menu {$menu_obj->slug} {$a['extra-class']}">
    </div>
CONTENT;
}

add_shortcode('no_press_post_grid', 'no_press_post_grid_reg');
function no_press_post_grid_reg($attrs)
{
    $a = shortcode_atts(array(
        "default_category" => '',
        "extra-class" => ''
    ), $attrs);

    return <<<CONTENT
    <div no-press-post-grid="" data-default-category="{$a['default_category']}" class="no-press-post-grid {$a['extra-class']}"></div>
CONTENT;
}

add_shortcode('no_press_footer_picker', 'no_press_footer_picker_reg');
function no_press_footer_picker_reg($attrs)
{
    $a = shortcode_atts(array(
        "page_footer" => ''
    ), $attrs);

    $fc = apply_filters('the_content', get_page_by_path($a['page_footer'], OBJECT, 'footer')->post_content);

//    return apply_filters('the_content', get_page_by_path($a['page_footer'], OBJECT, 'footer')->post_content);
    return <<<CONTENT
    <div class="{$a['page_footer']}" page-footer="{$a['page_footer']}">
    {$fc}
    </div>
CONTENT;
}

add_shortcode('no_press_form_picker', 'no_press_form_picker_reg');
function no_press_form_picker_reg($attrs)
{
    $a = shortcode_atts(array(
        "gravity_form" => '',
        "extra-class" => ''
    ), $attrs);

    return <<<CONTENT
    <div contact-form="{$a['gravity_form']}" form-options="vm.formOptions"></div>
CONTENT;
}

add_shortcode('no_press_post_dates_menu', 'no_press_post_dates_menu_reg');
function no_press_post_dates_menu_reg($attrs)
{
    $a = shortcode_atts(array(
        "extra-class" => ''
    ), $attrs);

    return <<<CONTENT
    <div no_press_post_dates_menu="" class="no_press_post_dates_menu {$a['extra-class']}"></div>
CONTENT;
}


add_shortcode('no_press_contact_popup_overlay', 'no_press_contact_popup_overlay_reg');
function no_press_contact_popup_overlay_reg($attrs)
{
    $a = shortcode_atts(array(
        "popup_open_on" => false,
        "popup_scroll_open_y" => false,
        "popup_timeout_open_ms" => false,
        "popup_cooldown_time" => false,
        "popup_cooldown_sessions_no" => false,
        "popup_show_if_made_contact" => false,
        "popup_exclude_pages" => false,
        "popup_backdrop_color" => false,
        "extra-class" => false,
    ), $attrs);

    return <<<CONTENT
        <div no-press-contact-popup-overlay="{$a['popup_open_on']}"
             open-on-y-pos="{$a['popup_scroll_open_y']}"
             open-on-timeout="{$a['popup_timeout_open_ms']}"
             popup-cooldown-time="{$a['popup_cooldown_time']}"
             popup-cooldown-sessions-no="{$a['popup_cooldown_sessions_no']}"
             popup_show_if_made_contact="{$a['popup_show_if_made_contact']}"
             exclude-pages="{$a['popup_exclude_pages']}"
             backdrop-color="{$a['popup_backdrop_color']}"
             class="{$a['extra-class']}">
        </div>
CONTENT;
}

add_shortcode('no_press_dropdown', 'no_press_dropdown_reg');
function no_press_dropdown_reg($attrs)
{
    $a = shortcode_atts(array(
        "extra-class" => ''
    ), $attrs);

    // data-on-select="{$a['select-callback']}" data-ng-model="{$a['ng-model']}"

    return <<<CONTENT
    <div no-press-dropdown="dropdownOptions" class="ng-hide {$a['extra-class']}"></div>
CONTENT;
}

add_shortcode('no_press_social_media', 'no_press_social_media_reg');
function no_press_social_media_reg($attrs)
{
    $a = shortcode_atts(array(
        "social_share_fb" => false,
        "social_share_li" => false,
        "social_share_gp" => false,
        "social_share_tw" => false,
        "social_share_tum" => false,
        "social_share_pin" => false,
        "extra-class" => ''
    ), $attrs);

    // data-on-select="{$a['select-callback']}" data-ng-model="{$a['ng-model']}"

    $tmpl = '<div no-press-social-bar="';
    foreach ($a as $k => $v) {
        if ($v && $k != 'extra-class') {
            $tmpl .= $v . '_';
        }
    }
    $tmpl .= '" optional-config="vm.socialBarConfig"';
    $tmpl .= ' class="' . $a['extra-class'] . '"></div>';

    return $tmpl;
}

// Add custom row options
add_action('vc_after_init', 'change_vc_rows');
function change_vc_rows()
{

    // Add parameters we want
    vc_add_param('vc_row', array(
        'type' => 'checkbox',
        'heading' => "Row is page section?",
        'param_name' => 'row_is_page_section',
        'value' => array(__('Yes', 'no-press-vc') => 'yes'),
        'description' => __("Replace the output root-tag for this container.", "no-press-vc")
    ));

    vc_add_param('vc_row', array(
        'type' => 'textfield',
        'heading' => "Directive name",
        'param_name' => 'row_ng_directive',
        'value' => '',
        'description' => __("Name of AngularJS directive to render this element", "no-press-vc")
    ));

    vc_add_param('vc_row_inner', array(
        'type' => 'textfield',
        'heading' => "Directive name",
        'param_name' => 'row_inner_ng_directive',
        'value' => '',
        'description' => __("Name of AngularJS directive to render this element", "no-press-vc")
    ));

    $new_row_map = WPBMap::getShortCode('vc_row');
    $new_row_inner_map = WPBMap::getShortCode('vc_row_inner');

    // Remove default vc_row
    vc_remove_element('vc_row');
    vc_remove_element('vc_row_inner');

    // Remap shortcode with custom template
    vc_map($new_row_map);
    vc_map($new_row_inner_map);
}

// Add custom column options
add_action('vc_after_init', 'change_vc_columns');
function change_vc_columns()
{
    vc_add_param('vc_column', array(
        'type' => 'textfield',
        'heading' => "Directive name",
        'param_name' => 'column_ng_directive',
        'value' => '',
        'description' => __("Name of AngularJS directive to render this element", "no-press-vc")
    ));

    $newMap = WPBMap::getShortCode('vc_column');

    // Remove default vc_column
    vc_remove_element('vc_column');

    // Remap shortcode with custom template
    vc_map($newMap);
}

/**
 * Replace default vc_ css classes with bootstrap equivalents
 */
// Filter to replace default css class names for vc_row shortcode and vc_column
add_filter('vc_shortcodes_css_class', 'custom_css_classes_for_vc_row_and_vc_column', 10, 2);
function custom_css_classes_for_vc_row_and_vc_column($class_string, $tag)
{
    if ($tag == 'vc_column' || $tag == 'vc_column_inner') {
        $class_string = preg_replace('/vc_col-sm-(\d{1,2})/', 'col-sm-$1', $class_string); // This will replace "vc_col-sm-%" with "my_col-sm-%"
    }

    return $class_string; // Important: you should always return modified or original $class_string
}

add_action('init', 'my_add_excerpts_to_pages');
function my_add_excerpts_to_pages()
{
    add_post_type_support('page', 'excerpt');
}

