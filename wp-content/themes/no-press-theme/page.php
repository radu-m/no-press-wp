<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage NoPress
 * @since NoPressTheme 1.0
 */
/*

// Start the loop.
while (have_posts()) : the_post();

    // Include the page content template.
    get_template_part('template-parts/content', 'page');

    // If comments are open or we have at least one comment, load up the comment template.
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    echo the_content();

    // End of the loop.
endwhile;

*/
global $post;
if (!$post) {
    die();
}

$html = '';
$realPostUri = str_replace('/backend', '', $_SERVER['REQUEST_URI']);
$reqUrl = ((!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? 'https' : 'http') . '://0.0.0.0';

$postMeta = get_post_meta($post->ID);

function get_post_featured_img()
{
    $thumb_id = get_post_thumbnail_id();
    $thumb_url_array = wp_get_attachment_image_src($thumb_id, 'full', true);
    $thumb_url = $thumb_url_array[0];

    // make sure the host has www. otherwise the request is denied
    if (!stristr($thumb_url, 'www.')) {
        $parts = explode('://', $thumb_url);
        $thumb_url = $parts[0] . '://www.' . $parts[1];
    }

    return $thumb_url;
}


$featImg = get_post_featured_img();
$realUrl = $reqUrl . $realPostUri;

$html = <<<CONTENT
<!DOCTYPE html>
<html lang="en" class="no-js" prefix="og: http://ogp.me/ns#">
    <head>
        <title>
            {$post->post_title}
        </title>

        <meta prefix="og: http://ogp.me/ns#" name="author" content="NoPress"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:title" content="{$post->post_title}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:description" content="{$post->post_excerpt}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:image" content="{$featImg}"/>
        <meta prefix="og: http://ogp.me/ns#" property="og:image:width" content="790">
        <meta prefix="og: http://ogp.me/ns#" property="og:image:height" content="425">
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

//$srv = print_r($_SERVER, true);
//file_put_contents('srv.txt', $srv);
//file_put_contents('single.txt', $html);
//file_put_contents('single.txt', print_r($postMeta, true));
//file_put_contents('single.txt', print_r($post, true));

// 3. return the page
echo $html;

