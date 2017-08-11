<?php
/**
 * Created by PhpStorm.
 * User: radu
 * Date: 4/21/16
 * Time: 4:50 PM
 */

if (!$post) {
    die();
}

$html = '';
$realPostUri = str_replace('/backend', '', $_SERVER['REQUEST_URI']);
$reqUrl = ((!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? 'https' : 'http') . '://www.no-press.com';

$postMeta = get_post_meta($post->ID);

function find_top_category_parent($catID)
{
    $cat = get_category($catID);

    if ($cat->parent < 1) {
        return $cat;
    }
    return find_top_category_parent($cat->parent);
}

function get_default_post_image($post)
{
    $cats = get_the_category($post->ID);
    if (count($cats) > 0) {
        $parentCat = $cats[0];

        if ($parentCat->parent > 0) {
            $parentCat = find_top_category_parent($parentCat->parent);
        }

        return z_taxonomy_image_url($parentCat->term_id);
    }
    return null;
}

function get_post_featured_img($post)
{
    $thumb_id = get_post_thumbnail_id();
    $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'full', true);
    $thumb_url = $thumb_url_array[0];

    if (!$thumb_url || stristr($thumb_url, 'crystal/default.png')) {
        if (function_exists('z_taxonomy_image_url')) {
            $thumb_url = get_default_post_image($post);
        }
    }

    // make sure the host has www. otherwise the request is denied
    if (!stristr($thumb_url, 'www.')) {
        $parts = explode('://', $thumb_url);
        $thumb_url = $parts[0] . '://www.' . $parts[1];
    }

    return $thumb_url;
}

//print_r($post);
//echo "<br/>";
//echo "<br/>";
//print_r($postMeta);
//echo "<br/>";
//echo "<br/>";
//print_r(get_the_author_meta('display_name', $post->post_author));
//die();

$authMeta = get_the_author_meta('display_name', $post->post_author);
$featImg = get_post_featured_img($post);
$realUrl = $reqUrl . $realPostUri;

//<meta prefix="og: http://ogp.me/ns#" property="og:image:width" content="790">
//        <meta prefix="og: http://ogp.me/ns#" property="og:image:height" content="425">

$html = <<<CONTENT
<!DOCTYPE html>
<html lang="en" class="no-js" prefix="og: http://ogp.me/ns#">
    <head>
        <title>
            {$post->post_title}
        </title>

        <meta prefix="og: http://ogp.me/ns#" name="author" content="{$authMeta}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:title" content="{$post->post_title}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:description" content="{$post->post_excerpt}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:image" content="{$featImg}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:url" content="{$realUrl}">

        <meta prefix="og: http://ogp.me/ns#" name="twitter:card" content="summary_large_image">
        <meta prefix="og: http://ogp.me/ns#" name="twitter:site" content="@no-press">
        <meta prefix="og: http://ogp.me/ns#" name="twitter:creator" content="@no-press"/>
        <meta prefix="og: http://ogp.me/ns#" property="twitter:title" content="{$post->post_title}"/>
        <meta prefix="og: http://ogp.me/ns#" property="twitter:description" content="{$post->post_excerpt}"/>
        <meta prefix="og: http://ogp.me/ns#" property="twitter:image" content="{$featImg}"/>
    </head>

    <body>
        <h1>
            <a href="{$realUrl}">{$post->post_title}</a>
        </h1>

        <p>
            <a href="{$realUrl}">
                <img src="{$featImg}" alt=""/>
            </a>
        </p>

        <p>
            {$post->post_excerpt}
        </p>
    </body>
</html>
CONTENT;

$srv = print_r($_SERVER, true);
file_put_contents('srv.txt', $srv);
file_put_contents('single.txt', $html);

// 3. return the page
echo $html;

//<meta prefix="og: http://ogp.me/ns#" http-equiv="refresh" content="0;url={$realUrl}">
