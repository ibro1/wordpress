<?php
/**
 * Test Factory Class
 *
 * Creates test data for unit tests.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Auto Featured Image Test Factory
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Test_Factory {

    /**
     * Create test job
     *
     * @param array $args Job arguments
     * @return int Job ID
     */
    public function create_job( $args = array() ) {
        $defaults = array(
            'post_id' => $this->create_post(),
            'status' => 'pending',
            'priority' => 10,
            'attempts' => 0,
            'max_attempts' => 3,
        );

        $args = wp_parse_args( $args, $defaults );

        $plugin = Auto_Featured_Image::get_instance();
        return $plugin->database->insert_job(
            $args['post_id'],
            $args['status'],
            $args['priority']
        );
    }

    /**
     * Create test post
     *
     * @param array $args Post arguments
     * @return int Post ID
     */
    public function create_post( $args = array() ) {
        $defaults = array(
            'post_title' => 'Test Post ' . wp_generate_password( 8, false ),
            'post_content' => $this->get_sample_content(),
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
        );

        $args = wp_parse_args( $args, $defaults );
        return wp_insert_post( $args );
    }

    /**
     * Create test attachment
     *
     * @param array $args Attachment arguments
     * @return int Attachment ID
     */
    public function create_attachment( $args = array() ) {
        $defaults = array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Image ' . wp_generate_password( 8, false ),
            'post_status' => 'inherit',
            'post_parent' => 0,
        );

        $args = wp_parse_args( $args, $defaults );
        $attachment_id = wp_insert_post( $args );

        // Add metadata
        $metadata = array(
            'width' => $args['width'] ?? 800,
            'height' => $args['height'] ?? 600,
            'file' => 'test-image-' . $attachment_id . '.jpg',
            'sizes' => array(
                'thumbnail' => array(
                    'file' => 'test-image-' . $attachment_id . '-150x150.jpg',
                    'width' => 150,
                    'height' => 150,
                    'mime-type' => 'image/jpeg',
                ),
                'medium' => array(
                    'file' => 'test-image-' . $attachment_id . '-300x225.jpg',
                    'width' => 300,
                    'height' => 225,
                    'mime-type' => 'image/jpeg',
                ),
            ),
        );

        wp_update_attachment_metadata( $attachment_id, $metadata );

        return $attachment_id;
    }

    /**
     * Create test log entry
     *
     * @param array $args Log arguments
     * @return int Log ID
     */
    public function create_log_entry( $args = array() ) {
        $defaults = array(
            'level' => 'info',
            'message' => 'Test log message',
            'context' => array( 'test' => true ),
            'post_id' => null,
            'batch_id' => null,
        );

        $args = wp_parse_args( $args, $defaults );

        $plugin = Auto_Featured_Image::get_instance();
        return $plugin->database->insert_log(
            $args['level'],
            $args['message'],
            $args['context'],
            $args['post_id'],
            $args['batch_id']
        );
    }

    /**
     * Create test progress entry
     *
     * @param array $args Progress arguments
     * @return int Progress ID
     */
    public function create_progress_entry( $args = array() ) {
        $defaults = array(
            'batch_id' => 'test_batch_' . wp_generate_password( 8, false ),
            'total_posts' => 10,
            'processed_posts' => 5,
            'successful_posts' => 4,
            'failed_posts' => 1,
            'status' => 'processing',
        );

        $args = wp_parse_args( $args, $defaults );

        $plugin = Auto_Featured_Image::get_instance();
        return $plugin->database->insert_progress( $args );
    }

    /**
     * Create multiple test posts
     *
     * @param int   $count Number of posts to create
     * @param array $args Post arguments
     * @return array Post IDs
     */
    public function create_posts( $count = 5, $args = array() ) {
        $post_ids = array();

        for ( $i = 0; $i < $count; $i++ ) {
            $post_args = array_merge( $args, array(
                'post_title' => 'Test Post ' . ( $i + 1 ) . ' ' . wp_generate_password( 8, false ),
            ) );
            $post_ids[] = $this->create_post( $post_args );
        }

        return $post_ids;
    }

    /**
     * Create multiple test attachments
     *
     * @param int   $count Number of attachments to create
     * @param array $args Attachment arguments
     * @return array Attachment IDs
     */
    public function create_attachments( $count = 5, $args = array() ) {
        $attachment_ids = array();

        for ( $i = 0; $i < $count; $i++ ) {
            $attachment_args = array_merge( $args, array(
                'post_title' => 'Test Image ' . ( $i + 1 ) . ' ' . wp_generate_password( 8, false ),
                'width' => 800 + ( $i * 100 ),
                'height' => 600 + ( $i * 75 ),
            ) );
            $attachment_ids[] = $this->create_attachment( $attachment_args );
        }

        return $attachment_ids;
    }

    /**
     * Create test post with embedded images
     *
     * @param array $args Post arguments
     * @return array Post data with image IDs
     */
    public function create_post_with_images( $args = array() ) {
        $image_count = $args['image_count'] ?? 3;
        unset( $args['image_count'] );

        // Create images
        $image_ids = $this->create_attachments( $image_count );

        // Build content with images
        $content = '<p>This is a test post with embedded images.</p>';
        
        foreach ( $image_ids as $i => $image_id ) {
            $image_url = wp_get_attachment_url( $image_id );
            $content .= "<p>Image " . ( $i + 1 ) . ":</p>";
            $content .= "<img src=\"{$image_url}\" alt=\"Test Image " . ( $i + 1 ) . "\" />";
        }

        $content .= '<p>End of post content.</p>';

        $args['post_content'] = $content;
        $post_id = $this->create_post( $args );

        return array(
            'post_id' => $post_id,
            'image_ids' => $image_ids,
            'content' => $content,
        );
    }

    /**
     * Create test batch scenario
     *
     * @param array $args Batch arguments
     * @return array Batch data
     */
    public function create_batch_scenario( $args = array() ) {
        $defaults = array(
            'post_count' => 10,
            'posts_with_images' => 7,
            'posts_with_featured_images' => 3,
        );

        $args = wp_parse_args( $args, $defaults );

        $scenario = array(
            'all_posts' => array(),
            'posts_with_images' => array(),
            'posts_with_featured_images' => array(),
            'posts_without_images' => array(),
        );

        // Create posts with images
        for ( $i = 0; $i < $args['posts_with_images']; $i++ ) {
            $post_data = $this->create_post_with_images( array(
                'image_count' => rand( 1, 4 ),
            ) );
            
            $scenario['all_posts'][] = $post_data['post_id'];
            $scenario['posts_with_images'][] = $post_data['post_id'];
        }

        // Create posts without images
        $posts_without_images = $args['post_count'] - $args['posts_with_images'];
        for ( $i = 0; $i < $posts_without_images; $i++ ) {
            $post_id = $this->create_post( array(
                'post_content' => '<p>This post has no images.</p>',
            ) );
            
            $scenario['all_posts'][] = $post_id;
            $scenario['posts_without_images'][] = $post_id;
        }

        // Set featured images for some posts
        $posts_for_featured = array_slice( $scenario['posts_with_images'], 0, $args['posts_with_featured_images'] );
        foreach ( $posts_for_featured as $post_id ) {
            $attachment_id = $this->create_attachment();
            set_post_thumbnail( $post_id, $attachment_id );
            $scenario['posts_with_featured_images'][] = $post_id;
        }

        return $scenario;
    }

    /**
     * Get sample post content
     *
     * @param bool $with_images Whether to include images
     * @return string Sample content
     */
    public function get_sample_content( $with_images = true ) {
        $content = '<p>This is a sample post content for testing purposes. It contains multiple paragraphs and various HTML elements.</p>';
        
        if ( $with_images ) {
            $content .= '<p>Here is an image:</p>';
            $content .= '<img src="https://example.com/sample-image-1.jpg" alt="Sample Image 1" width="800" height="600" />';
            $content .= '<p>Some more content between images.</p>';
            $content .= '<img src="https://example.com/sample-image-2.jpg" alt="Sample Image 2" width="400" height="300" />';
        }

        $content .= '<p>This is the final paragraph of the sample content.</p>';
        
        return $content;
    }

    /**
     * Create test settings
     *
     * @param array $overrides Setting overrides
     * @return array Settings
     */
    public function create_test_settings( $overrides = array() ) {
        $defaults = array(
            'auto_processing_enabled' => true,
            'post_types' => array( 'post', 'page' ),
            'enabled_algorithms' => array( 'first_quality_image', 'content_based', 'semantic_analysis' ),
            'batch_size' => 10,
            'skip_existing' => true,
            'image_quality_threshold' => 70,
            'min_image_width' => 300,
            'min_image_height' => 200,
            'log_level' => 'info',
            'cleanup_logs_days' => 30,
            'cleanup_completed_jobs' => 7,
        );

        $settings = wp_parse_args( $overrides, $defaults );
        update_option( 'auto_featured_image_settings', $settings );

        return $settings;
    }

    /**
     * Create test user
     *
     * @param array $args User arguments
     * @return int User ID
     */
    public function create_user( $args = array() ) {
        $defaults = array(
            'user_login' => 'testuser_' . wp_generate_password( 8, false ),
            'user_email' => 'test_' . wp_generate_password( 8, false ) . '@example.com',
            'user_pass' => wp_generate_password(),
            'role' => 'editor',
        );

        $args = wp_parse_args( $args, $defaults );
        return wp_insert_user( $args );
    }

    /**
     * Create test taxonomy term
     *
     * @param string $taxonomy Taxonomy name
     * @param array  $args Term arguments
     * @return int Term ID
     */
    public function create_term( $taxonomy = 'category', $args = array() ) {
        $defaults = array(
            'name' => 'Test Term ' . wp_generate_password( 8, false ),
            'slug' => 'test-term-' . wp_generate_password( 8, false ),
        );

        $args = wp_parse_args( $args, $defaults );
        $result = wp_insert_term( $args['name'], $taxonomy, $args );

        return is_wp_error( $result ) ? 0 : $result['term_id'];
    }

    /**
     * Clean up created data
     *
     * @param array $data_ids Data IDs to clean up
     */
    public function cleanup( $data_ids = array() ) {
        // Clean up posts
        if ( isset( $data_ids['posts'] ) ) {
            foreach ( $data_ids['posts'] as $post_id ) {
                wp_delete_post( $post_id, true );
            }
        }

        // Clean up attachments
        if ( isset( $data_ids['attachments'] ) ) {
            foreach ( $data_ids['attachments'] as $attachment_id ) {
                wp_delete_attachment( $attachment_id, true );
            }
        }

        // Clean up users
        if ( isset( $data_ids['users'] ) ) {
            foreach ( $data_ids['users'] as $user_id ) {
                wp_delete_user( $user_id );
            }
        }

        // Clean up terms
        if ( isset( $data_ids['terms'] ) ) {
            foreach ( $data_ids['terms'] as $term_data ) {
                wp_delete_term( $term_data['term_id'], $term_data['taxonomy'] );
            }
        }
    }
}
