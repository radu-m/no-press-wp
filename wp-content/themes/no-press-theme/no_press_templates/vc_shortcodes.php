<?php

add_filter('vc_before_init', 'register_no_press_vc_shortcodes');
function register_no_press_vc_shortcodes()
{
    /**
     * TEXT BOX
     * - block element with title, subtitle, body-text, image
     */
    vc_map(array(
        "name" => __("Text Box", "no-press-vc"),
        "base" => "no_press_text_box",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
//        "is_container" => true,
        "content_element" => true,
        "params" => array(
            array(
                "type" => "textfield",
                'holder' => 'div',
                "heading" => __("Title"),
                "param_name" => "text-box-title",
                "value" => __("", "no-press-vc"),
            ),
            array(
                "type" => "textfield",
                'holder' => 'div',
                "heading" => __("Subtitle"),
                "param_name" => "text-box-subtitle",
                "value" => __("", "no-press-vc"),
            ),
            array(
                "type" => "textarea_html",
                'holder' => 'div',
                "heading" => __("Body text"),
                "param_name" => "content",
                "value" => __("", "no-press-vc"),
            ),
            array(
                'type' => 'vc_link',
                'heading' => __('Read more link', 'no-press-vc'),
                'holder' => 'a',
                'param_name' => 'box-extra-link',
                'description' => __('Link to full-size-text/reference/resource/etc.', 'no-press-vc')
            ),
            array(
                "type" => "textfield",
                'holder' => 'p',
                "heading" => __("Image Class"),
                'description' => __('Use this to set the icon as font or icon-class. NB: Will overwrite the featured image, if any!', 'no-press-vc'),
                "param_name" => "image-class",
            ),
            array(
                'type' => 'vc_link',
                'heading' => __('Image link', 'no-press-vc'),
                'holder' => 'p',
                'param_name' => 'image-link',
                'description' => __('Enter URL if you want this image to have a link (Note: parameters like "mailto:" are also accepted).', 'no-press-vc')
            ),
            array(
                'type' => 'attach_image',
                'holder' => 'img',
                'heading' => __('Featured Image'),
                'param_name' => 'image_id',
                'value' => __('Default'),
                'description' => __('Featured image')
            ),
            array(
                "type" => "textfield",
                'holder' => 'div',
                "heading" => __("CSS Class", 'no-press-vc'),
                "param_name" => "extra-css-class",
            ),
        )
    ));

    /**
     * BUTTON
     */
    vc_map(array(
        "name" => __("Button (NoPress)", "no-press-vc"),
        "base" => "no_press_button",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                "type" => "textfield",
                "holder" => "div",
                "heading" => __("Button label"),
                "param_name" => "button-label",
                "value" => __("", "no-press-vc")
            ),
            array(
                'type' => 'vc_link',
                'heading' => __('URL (Link)', 'no-press-vc'),
                'param_name' => 'button-link',
                'description' => __('Add link to button.', 'no-press-vc'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'no-press-vc'),
                'param_name' => 'button-class',
            ),
            array(
                'type' => 'dropdown',
                "heading" => __("Button action"),
                "holder" => "div",
                'param_name' => 'button-action',
                'value' => array(
                    __('- none -', 'no-press-vc') => '',
                    __('Watch video', 'no-press-vc') => 'watchVideo()',
                    __('Load more content', 'no-press-vc') => 'loadMoreContent()',
                    __('Submit contact-form', 'no-press-vc') => 'submitContactForm()',
                    __('Download all', 'no-press-vc') => 'downloadAllAttachments()'
                ),
                'description' => __('Choose button action (callback function to be called onClick())')
            )
        )
    ));

    /**
     * HEADLINE
     */
    $hTags = array(
        'h1' => 'h1',
        'h2' => 'h2',
        'h3' => 'h3',
        'h4' => 'h4',
        'h5' => 'h5',
        'h6' => 'h6',
    );
    vc_map(array(
        "name" => __("Headline", "no-press-vc"),
        "base" => "no_press_headline",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                "type" => "textfield",
                "holder" => "h1",
                "heading" => __("Headline text"),
                "param_name" => "headline-text",
                "value" => __("", "no-press-vc")
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('How big you want it?', 'js_composer'),
                'param_name' => 'headline_html_tag',
                'value' => $hTags,
                'std' => 'h1', // $hTags[0],
                'description' => __('Select an HTML tag (defaults to h1)', 'js_composer'),
                'admin_label' => true,
                'save_always' => true,
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'no-press-vc'),
                'param_name' => 'headline-class',
            )
        )
    ));

    /**
     * CUSTOM MENU
     */
    $custom_menus = array();
    $menus = get_terms('nav_menu', array('hide_empty' => false));
    if (is_array($menus) && !empty($menus)) {
        foreach ($menus as $single_menu) {
            if (is_object($single_menu) && isset($single_menu->name, $single_menu->slug)) {
                $custom_menus[$single_menu->name] = array_search($single_menu->term_id, get_nav_menu_locations());
            }
        }
    }
    vc_map(array(
        "name" => __("Custom Menu (NoPress)", "no-press-vc"),
        "base" => "no_press_custom_menu",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Menu', 'js_composer'),
                'param_name' => 'nav_menu',
                'value' => $custom_menus,
                'description' => empty($custom_menus) ? __('Custom menus not found. Please visit <b>Appearance > Menus</b> page to create new menu.', 'js_composer') : __('Select menu to display.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true,
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            )
        )
    ));

    /**
     * POST GRID
     */
    $args = array(
        'show_option_all' => '',
        'orderby' => 'name',
        'order' => 'ASC',
        'style' => 'list',
        'show_count' => 0,
        'hide_empty' => 0,
        'use_desc_for_title' => 1,
        'child_of' => 0,
        'feed' => '',
        'feed_type' => '',
        'feed_image' => '',
        'exclude' => '',
        'exclude_tree' => '',
        'include' => '',
        'hierarchical' => 1,
        'title_li' => __('Categories'),
        'show_option_none' => __(''),
        'number' => null,
        'echo' => 1,
        'depth' => 0,
        'current_category' => 0,
        'pad_counts' => 0,
        'taxonomy' => 'category',
        'walker' => null
    );
    $post_cats = get_categories(0, $args);

    $cats = array();
    foreach ($post_cats as $cat) {
        $cats[$cat->name] = $cat->slug;
    }

    vc_map(array(
        "name" => __("Post Grid (NoPress)", "no-press-vc"),
        "base" => "no_press_post_grid",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Default posts category', 'js_composer'),
                'param_name' => 'default_category',
                'value' => $cats,
                'std' => $cats[0],
                'description' => empty($cats) ? __('No registered categories were found.', 'js_composer') : __('Select default post category to display.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true,
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            ),
        )
    ));


    /**
     * FOOTER PICKER
     */
    $args = array(
        'post_type' => 'footer',
        'post_status' => 'publish',
        'suppress_filters' => false
    );
    $footers = [];
    foreach (get_posts($args) as $footer) {
        $footers[$footer->post_title] = $footer->post_name;
    };
    vc_map(array(
        "name" => __("Footer (NoPress)", "no-press-vc"),
        "base" => "no_press_footer_picker",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Footer for page or post', 'js_composer'),
                'param_name' => 'page_footer',
                'value' => $footers,
                'description' => empty($footers) ? __('No published posts of type Footer were found.', 'js_composer') : __('Select footer for this page or post.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true,
            )
        )
    ));

    /**
     * HEADER PICKER
     */
    $args = array(
        'post_type' => 'header',
        'post_status' => 'publish',
        'suppress_filters' => false
    );
    $footers = [];
    foreach (get_posts($args) as $header) {
        $headers[$header->post_title] = $header->post_name;
    };
    vc_map(array(
        "name" => __("Header (NoPress)", "no-press-vc"),
        "base" => "no_press_header_picker",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Header for page or post', 'js_composer'),
                'param_name' => 'np_header',
                'value' => $headers,
                'description' => empty($headers) ? __('No published posts of type Header were found.', 'js_composer') : __('Select header for this page or post.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true,
            )
        )
    ));

    /**
     * FORM PICKER
     */
    $args = array(
        "active" => true,
        "trash" => false
    );
    $forms = [];
    foreach (GFAPI::get_forms($args) as $form) {
//        print_r($form);
        $forms[$form['title']] = $form['id'];
    };
    vc_map(array(
        "name" => __("Form Picker (NoPress)", "no-press-vc"),
        "base" => "no_press_form_picker",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Choose a Gravity Form', 'js_composer'),
                'param_name' => 'gravity_form',
                'value' => $forms,
//                'std' => $footers[0],
                'description' => empty($forms) ? __('No published Gravity forms were found.', 'js_composer') : __('Select a form.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            )
        )
    ));

    vc_map(array(
        "name" => __("Post Archive (NoPress)", "no-press-vc"),
        "base" => "no_press_post_dates_menu",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            ),
        )
    ));

    vc_map(array(
        "name" => __("Angular Directive (NoPress)", "no-press-vc"),
        "base" => "no_press_ng_directive",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "params" => array(
            array(
                'type' => 'textfield',
                'heading' => __('Directive name', 'js_composer'),
                'param_name' => 'directive-name',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
                'admin_label' => true,
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Directive attributes', 'js_composer'),
                'param_name' => 'directive-attrs',
                'description' => __('Optional space-separated attributes ex: attr-name="attr1.value"', 'js_composer'),
                'admin_label' => true,
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            )
        )
    ));

    vc_map(array(
        "name" => __("Social Media (NoPress)", "no-press-vc"),
        "base" => "no_press_social_media",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        "description" => __('Make current URL shareable on:', 'js_composer'),
        "params" => array(
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on FaceBook?', 'js_composer'),
                'param_name' => 'social_share_fb',
                'value' => array(__('FaceBook', 'js_composer') => 'fb')
            ),
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on LinkedIn?', 'js_composer'),
                'param_name' => 'social_share_li',
                'value' => array(__('LinkedIn', 'js_composer') => 'li')
            ),
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on G+?', 'js_composer'),
                'param_name' => 'social_share_gp',
                'value' => array(__('G+', 'js_composer') => 'gp')
            ),
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on Twitter?', 'js_composer'),
                'param_name' => 'social_share_tw',
                'value' => array(__('Twitter', 'js_composer') => 'tw')
            ),
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on Tumblr?', 'js_composer'),
                'param_name' => 'social_share_tum',
                'value' => array(__('Tumblr', 'js_composer') => 'tum')
            ),
            array(
                'type' => 'checkbox',
//                'heading' => __('Enable sharing on Pinterest?', 'js_composer'),
                'param_name' => 'social_share_pin',
                'value' => array(__('Pinterest', 'js_composer') => 'pin')
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            ),
        )
    ));

    vc_map(array(
        "name" => __("Dropdown (NoPress)", "no-press-vc"),
        "base" => "no_press_dropdown",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        'description' => __('ng-directive polyfill. ', 'js_composer'),
        "params" => array(
            /*            array(
                            'type' => 'textfield',
                            'heading' => __('ng-model object name', 'js_composer'),
                            'param_name' => 'ng-model',
                            'description' => __('(optional) $scope object to bind to', 'js_composer'),
                        ),
                        array(
                            'type' => 'textfield',
                            'heading' => __('Option select callback', 'js_composer'),
                            'param_name' => 'select-callback',
                            'description' => __('Name of callback function on option selection', 'js_composer'),
                        ),*/
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            ),
        )
    ));

    /**
     * POPUP OVERLAY
     */
    $backdropColors = array(
        'By mouse position' => 'byMousePos',
        'Black' => '#00000',
        'White' => '#ffffff',
        'Random' => 'random'
    );

    $openDropdownOn = array(
        'Never' => 'never',
        'Scroll' => 'scroll',
        'Timeout' => 'timeout'
    );

    $cooldownTimeOpts = array(
        "never" => 0,
        "1 day" => 1,
        "2 days" => 2,
        "1 week" => 7,
        "1 month" => 30,
        "6 months" => 180,
        "1 year" => 365
    );

    $pages = [];
    foreach (get_pages() as $page) {
        $pages[$page->post_title] = $page->post_name;
    }

    vc_map(array(
        "name" => __("Contact PopUp Overlay (NoPress)", "no-press-vc"),
        "base" => "no_press_contact_popup_overlay",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        'description' => __('Overlay popup', 'js_composer'),
        "params" => array(
            array(
                'type' => 'dropdown',
                'heading' => __('Open popup on', 'js_composer'),
                'param_name' => 'popup_open_on',
                'value' => $openDropdownOn,
                'std' => $openDropdownOn[0],
                'admin_label' => true,
                'save_always' => true
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Min. vertical scroll before (in pixels)', 'js_composer'),
                'std' => 300,
                'param_name' => 'popup_scroll_open_y',
                'description' => __('How much one has to scroll down the page before opening', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Timeout in milliseconds', 'js_composer'),
                'std' => 3000,
                'param_name' => 'popup_timeout_open_ms',
                'description' => __('Wait for * milliseconds before opening', 'js_composer'),
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Cooldown time (countdown is reset by reaching Cooldown # sessions): ', 'js_composer'),
                'param_name' => 'popup_cooldown_time',
                'value' => $cooldownTimeOpts,
                'std' => $cooldownTimeOpts[0],
                "description" => __('If the client returns within this interval, the popup will not open.', 'js_composer'),
                'admin_label' => true,
                'save_always' => true
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Cooldown # sessions (count reset by Cooldown time): ', 'js_composer'),
                'param_name' => 'popup_cooldown_sessions_no',
                'value' => 3,
                "description" => __('Only show it again the #th time a client returns. (0 == never)', 'js_composer'),
                'admin_label' => true,
                'save_always' => true
            ),
            array(
                "type" => "checkbox",
                "heading" => "Never show if already submitted the contact form.",
                "param_name" => "popup_show_if_made_contact",
                "value" => "true",
                "std" => "true",
            ),
            array(
                "type" => "checkbox",
                "heading" => "Exclude pages",
                "param_name" => "popup_exclude_pages",
                "value" => $pages,
                "std" => "contact",
                "description" => "Do not open the popup on the selected pages"
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Backdrop color ', 'js_composer'),
                'param_name' => 'popup_backdrop_color',
                'value' => $backdropColors,
                'admin_label' => true,
                'save_always' => true
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            )
        )
    ));

    vc_map(array(
        "name" => __("Contact Details (NoPress)", "no-press-vc"),
        "base" => "no_press_contact_details",
        "class" => "",
        "category" => __("NoPress", "no-press-vc"),
        'description' => __('Site-wide contact details', 'js_composer'),
        "params" => array(
            array(
                'type' => 'textfield',
                'heading' => __('Phone', 'js_composer'),
                'param_name' => 'contact-phone',
                'description' => __('Phone number with optional country code', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Email', 'js_composer'),
                'param_name' => 'contact-email',
                'description' => __('Public email address', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Street address', 'js_composer'),
                'param_name' => 'contact-street',
                'description' => __('Street name and building number', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Zip', 'js_composer'),
                'param_name' => 'contact-zip',
                'description' => __('Just the zip', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('City', 'js_composer'),
                'param_name' => 'contact-city',
                'description' => __('City name', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Country', 'js_composer'),
                'param_name' => 'contact-country',
                'description' => __('Country  / state / region', 'js_composer'),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Extra class name', 'js_composer'),
                'param_name' => 'extra-class',
                'description' => __('Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer'),
            )
        )
    ));
}
