<?php

/**
 * Created by PhpStorm.
 * User: radu
 * Date: 1/29/16
 * Time: 9:45 AM
 */
class JSON_API_NoPress_Controller
{
    public function get_post_cards()
    {
        $allPosts = $this->get_posts_by_cat()['data']['posts'];
        $postCards = array();

        foreach ($allPosts as $post) {
            $attachments = $post->attachments;

            foreach ($attachments as $att) {
                $meta = get_post_meta($att->id);

                $att->lang = 'en';
                if ($meta['wpcf-att-lang'] > 0) {
                    $att->lang = $meta['wpcf-att-lang'][0];
                }
            }

            $card = array(
//                "content" => $post->content,
                "excerpt" => $post->excerpt,
                "id" => $post->id,
                "type" => $post->type,
                "slug" => $post->slug,
                "url" => $post->url,
                "title" => $post->title,
                "date" => get_the_time('U', $post->id) * 1000, // $post->date,
                "categories" => $post->categories,
                "tags" => $post->tags,
                "author" => $post->author,
                "thumbnail" => wp_get_attachment_url(get_post_thumbnail_id($post->id)), // $post->thumbnail,
                "custom_fields" => $post->custom_fields,
                "attachments" => $attachments
            );
            $postCards[$post->slug] = $card;
        }

        return ['data' => $postCards];
    }

    public function get_posts_by_cat()
    {

        // list of IDs already cached on the client
        // if not empty, exclude these posts from the response
        $excludeList = $_GET['notin'] ? explode(',', $_GET['notin']) : array();

        global $json_api;
        $url = parse_url($_SERVER['REQUEST_URI']);

        $defaults = array(
            'ignore_sticky_posts' => true
        );

        $query['posts_per_page'] = 10;
        $query = wp_parse_args($url['query']);

        $query['paged'] = $_GET['grid_page'] ? $_GET['grid_page'] : 1;

        if ($query['cat-slug'] && !is_numeric($query['cat-slug'])) {
            // it's a cat slug, so find it's ID
            $query['cat'] = get_category_by_slug($query['cat-slug'])->term_id;
        }

        unset($query['json']);
        unset($query['post_status']);

        if (count($excludeList) > 0) {
            $query['post_not_in'] = $excludeList;
        }

        $query = array_merge($defaults, $query);
        $posts = $json_api->introspector->get_posts($query);
        $result = $this->posts_result($posts);
        $result['query'] = $query;

        return ['data' => $result];
    }

    protected function posts_result($posts)
    {
        global $wp_query;
        return array(
            'count' => count($posts),
            'count_total' => (int)$wp_query->found_posts,
            'pages' => $wp_query->max_num_pages,
            'posts' => $posts
        );
    }

    public function get_single_post()
    {
        global $json_api, $post;
        $previous = $next = false;
        $post = $json_api->introspector->get_current_post();

        $out = null;

        if ($post) {
            $previous = get_adjacent_post(false, '', true);
            $next = get_adjacent_post(false, '', false);

//            $post->date = get_the_time('U', $post->id);
        } else {
            $json_api->error("Not found.");
        }

        /**
         * Allow for requesting only partial data from this post
         */
        if ($_GET['bits']) {
            $bits = explode(',', $_GET['bits']);

            $partPost = array();

            foreach ($bits as $bit) {
                $partPost[$bit] = $post[$bit];
            }

            $out = $partPost;
        }

        if ($post) {
            $response = array(
                'post' => new JSON_API_Post($post)
            );

            if ($previous) {
                $response['previous_slug'] = $previous; // get_permalink($previous->ID);
            }

            if ($next) {
                $response['next_slug'] = $next; // get_permalink($next->ID);
            }

            foreach ($response['post']->attachments as $att) {
                $meta = get_post_meta($att->id);

                $att->lang = 'en';
                if ($meta['wpcf-att-lang'] > 0) {
                    $att->lang = $meta['wpcf-att-lang'][0];
                }
            }

            $response['post']->date = get_the_time('U', $post->id) * 1000;
//            $response['post']->thumbnail = $response['post']->thumbnail_images->full->url;

            $response['post']->author->avatar = get_avatar_url($response['post']->author->id);

            $out = $response;
        }

        return ['data' => $out];
    }

    protected function posts_object_result($posts, $object)
    {
        global $wp_query;
        // Convert something like "JSON_API_Category" into "category"
        $object_key = strtolower(substr(get_class($object), 9));
        return array(
            'count' => count($posts),
            'pages' => (int)$wp_query->max_num_pages,
            $object_key => $object,
            'posts' => $posts
        );
    }


}