<?php
// Task 2.1: Image Detection and Analysis System
// Task 2.5: Image Assignment Algorithms

class AFI_Image_Selector {

    /**
     * Cache for the latest library image to avoid repeated expensive queries within a single batch.
     * @var int|null|false
     */
    private static $latest_gallery_image_id = null;

    public static function find_image_for_post($post_id, $method = 'content') {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        switch ($method) {
            case 'attachment':
                return self::find_first_attached_image($post);
            case 'gallery':
                return self::find_latest_library_image();
            case 'content':
            default:
                return self::find_first_image_in_content($post);
        }
    }

    /**
     * Algorithm 1: Find the first image in the post content.
     */
    private static function find_first_image_in_content($post) {
        if (empty($post->post_content)) {
            return false;
        }

        $matches = array();
        // Regex to find an image tag and capture its class attribute
        preg_match('/<img.+?class=[\'"]([^\'"]+?)[\'"].*?>/i', $post->post_content, $matches);

        if (isset($matches[1])) {
            // Check if the image is an attachment by its class
            $class = $matches[1];
            if (preg_match('/wp-image-([\d]+)/i', $class, $class_matches)) {
                $attachment_id = absint($class_matches[1]);
                if (get_post($attachment_id)) {
                    return $attachment_id;
                }
            }
        }

        // Fallback: If no class-based match, just find the first image URL and try to match it
        preg_match('/<img.+?src=[\'"]([^\'"]+?)[\'"].*?>/i', $post->post_content, $matches);
        if (isset($matches[1])) {
            $image_url = $matches[1];
            return self::get_attachment_id_from_url($image_url);
        }

        return false;
    }

    /**
     * Algorithm 2: Find the first image attached to the post.
     */
    private static function find_first_attached_image($post) {
        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'posts_per_page' => 1,
            'post_parent'    => $post->ID,
            'post_mime_type' => 'image',
            'orderby'        => 'date',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ));

        if (!empty($attachments)) {
            return $attachments[0];
        }

        return false;
    }

    /**
     * IMPROVEMENT: Algorithm 3: Find the most recently uploaded image in the entire Media Library.
     * This uses a static variable for caching to ensure the expensive query runs only once per batch.
     */
    private static function find_latest_library_image() {
        if ( self::$latest_gallery_image_id !== null ) {
            return self::$latest_gallery_image_id ? self::$latest_gallery_image_id : false;
        }

        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'post_mime_type' => 'image',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ));

        if ( !empty($attachments) ) {
            self::$latest_gallery_image_id = $attachments[0];
            return self::$latest_gallery_image_id;
        }
        
        // Cache the "not found" result to avoid re-querying
        self::$latest_gallery_image_id = 0;
        return false;
    }
    
    /**
     * Helper to get an attachment ID from its URL.
     * Task 4.1: Database Query Optimization - This can be intensive, use with care.
     */
    private static function get_attachment_id_from_url($attachment_url = '') {
        global $wpdb;
        $attachment_id = false;

        if ('' == $attachment_url) {
            return false;
        }
        
        // Remove query strings from URL
        $attachment_url = preg_replace('/\?.*/', '', $attachment_url);

        // Get the upload directory paths
        $upload_dir_paths = wp_upload_dir();
        
        // Make sure the upload path is part of the attachment URL
        if (false !== strpos($attachment_url, $upload_dir_paths['baseurl'])) {
            // Get the file path relative to the upload directory
            $file_path = str_replace($upload_dir_paths['baseurl'] . '/', '', $attachment_url);

            // Query the database for a post with a matching guid
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT wposts.ID FROM {$wpdb->posts} wposts, {$wpdb->postmeta} wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = %s AND wposts.post_type = 'attachment'",
                $file_path
            ));
        }
        
        return $attachment_id;
    }
}