<?php
/**
 * Test Utilities Class
 *
 * Provides utility functions for testing.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Auto Featured Image Test Utils
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Test_Utils {

    /**
     * Mocked functions
     *
     * @var array
     */
    private static $mocked_functions = array();

    /**
     * Initialize test utilities
     */
    public static function init() {
        // Set up function mocking
        self::setup_function_mocking();
    }

    /**
     * Set up function mocking
     */
    private static function setup_function_mocking() {
        // Add filters to mock WordPress functions
        add_filter( 'wp_remote_get', array( __CLASS__, 'mock_wp_remote_get' ), 10, 2 );
        add_filter( 'wp_remote_post', array( __CLASS__, 'mock_wp_remote_post' ), 10, 2 );
    }

    /**
     * Mock a function
     *
     * @param string $function Function name
     * @param mixed  $return_value Return value
     */
    public static function mock_function( $function, $return_value ) {
        self::$mocked_functions[ $function ] = $return_value;
    }

    /**
     * Clear function mocks
     */
    public static function clear_function_mocks() {
        self::$mocked_functions = array();
    }

    /**
     * Check if function is mocked
     *
     * @param string $function Function name
     * @return bool Whether function is mocked
     */
    public static function is_function_mocked( $function ) {
        return isset( self::$mocked_functions[ $function ] );
    }

    /**
     * Get mocked function return value
     *
     * @param string $function Function name
     * @return mixed Mocked return value
     */
    public static function get_mocked_function_return( $function ) {
        return self::$mocked_functions[ $function ] ?? null;
    }

    /**
     * Mock wp_remote_get
     *
     * @param mixed $response Original response
     * @param array $args Request arguments
     * @return mixed Mocked response
     */
    public static function mock_wp_remote_get( $response, $args ) {
        if ( self::is_function_mocked( 'wp_remote_get' ) ) {
            return self::get_mocked_function_return( 'wp_remote_get' );
        }
        return $response;
    }

    /**
     * Mock wp_remote_post
     *
     * @param mixed $response Original response
     * @param array $args Request arguments
     * @return mixed Mocked response
     */
    public static function mock_wp_remote_post( $response, $args ) {
        if ( self::is_function_mocked( 'wp_remote_post' ) ) {
            return self::get_mocked_function_return( 'wp_remote_post' );
        }
        return $response;
    }

    /**
     * Clear plugin transients
     */
    public static function clear_plugin_transients() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_auto_featured_image_%' 
             OR option_name LIKE '_transient_timeout_auto_featured_image_%'"
        );
    }

    /**
     * Create test image file
     *
     * @param string $filename Filename
     * @param int    $width Image width
     * @param int    $height Image height
     * @return string File path
     */
    public static function create_test_image( $filename = 'test-image.jpg', $width = 800, $height = 600 ) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Create a simple test image
        $image = imagecreatetruecolor( $width, $height );
        $background = imagecolorallocate( $image, 255, 255, 255 );
        $text_color = imagecolorallocate( $image, 0, 0, 0 );

        imagefill( $image, 0, 0, $background );
        imagestring( $image, 5, 10, 10, 'Test Image', $text_color );

        imagejpeg( $image, $file_path, 90 );
        imagedestroy( $image );

        return $file_path;
    }

    /**
     * Delete test image file
     *
     * @param string $file_path File path
     */
    public static function delete_test_image( $file_path ) {
        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }
    }

    /**
     * Create test post with images
     *
     * @param array $args Post arguments
     * @return array Post data with image IDs
     */
    public static function create_test_post_with_images( $args = array() ) {
        $defaults = array(
            'post_title' => 'Test Post with Images',
            'post_status' => 'publish',
            'post_type' => 'post',
            'image_count' => 2,
        );

        $args = wp_parse_args( $args, $defaults );
        $image_count = $args['image_count'];
        unset( $args['image_count'] );

        // Create images
        $image_ids = array();
        $image_html = '';

        for ( $i = 1; $i <= $image_count; $i++ ) {
            $image_path = self::create_test_image( "test-image-{$i}.jpg", 800 + ( $i * 100 ), 600 + ( $i * 50 ) );
            $image_id = self::create_attachment_from_file( $image_path );
            $image_ids[] = $image_id;

            $image_url = wp_get_attachment_url( $image_id );
            $image_html .= "<img src=\"{$image_url}\" alt=\"Test Image {$i}\" width=\"" . ( 800 + ( $i * 100 ) ) . "\" height=\"" . ( 600 + ( $i * 50 ) ) . "\" />\n";
        }

        // Create post with image content
        $args['post_content'] = "<p>This is a test post with images.</p>\n{$image_html}<p>End of post content.</p>";
        $post_id = wp_insert_post( $args );

        return array(
            'post_id' => $post_id,
            'image_ids' => $image_ids,
            'image_html' => $image_html,
        );
    }

    /**
     * Create attachment from file
     *
     * @param string $file_path File path
     * @param int    $parent_id Parent post ID
     * @return int Attachment ID
     */
    public static function create_attachment_from_file( $file_path, $parent_id = 0 ) {
        $filename = basename( $file_path );
        $upload_dir = wp_upload_dir();

        $wp_filetype = wp_check_filetype( $filename, null );
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $file_path, $parent_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
            wp_update_attachment_metadata( $attachment_id, $attachment_data );
        }

        return $attachment_id;
    }

    /**
     * Simulate AJAX request
     *
     * @param string $action AJAX action
     * @param array  $data Request data
     * @return array Response data
     */
    public static function simulate_ajax_request( $action, $data = array() ) {
        $_POST['action'] = $action;
        $_POST = array_merge( $_POST, $data );

        // Set up AJAX environment
        if ( ! defined( 'DOING_AJAX' ) ) {
            define( 'DOING_AJAX', true );
        }

        // Capture output
        ob_start();
        
        try {
            do_action( 'wp_ajax_' . $action );
        } catch ( WPAjaxDieStopException $e ) {
            // Expected for AJAX requests
        }

        $output = ob_get_clean();

        // Parse JSON response
        $response = json_decode( $output, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $response = array( 'raw_output' => $output );
        }

        return $response;
    }

    /**
     * Simulate cron job execution
     *
     * @param string $hook Cron hook
     * @param array  $args Cron arguments
     */
    public static function simulate_cron_execution( $hook, $args = array() ) {
        if ( ! defined( 'DOING_CRON' ) ) {
            define( 'DOING_CRON', true );
        }

        do_action_ref_array( $hook, $args );
    }

    /**
     * Get database table row count
     *
     * @param string $table Table name
     * @return int Row count
     */
    public static function get_table_row_count( $table ) {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Truncate database table
     *
     * @param string $table Table name
     */
    public static function truncate_table( $table ) {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$table}" );
    }

    /**
     * Get last database error
     *
     * @return string Last error
     */
    public static function get_last_db_error() {
        global $wpdb;
        return $wpdb->last_error;
    }

    /**
     * Assert database table exists
     *
     * @param string $table Table name
     * @return bool Whether table exists
     */
    public static function table_exists( $table ) {
        global $wpdb;
        return $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
    }

    /**
     * Get memory usage in MB
     *
     * @return float Memory usage in MB
     */
    public static function get_memory_usage() {
        return memory_get_usage( true ) / 1024 / 1024;
    }

    /**
     * Get peak memory usage in MB
     *
     * @return float Peak memory usage in MB
     */
    public static function get_peak_memory_usage() {
        return memory_get_peak_usage( true ) / 1024 / 1024;
    }

    /**
     * Measure execution time of callback
     *
     * @param callable $callback Callback to measure
     * @return array Execution time and result
     */
    public static function measure_execution_time( $callback ) {
        $start_time = microtime( true );
        $result = call_user_func( $callback );
        $end_time = microtime( true );

        return array(
            'execution_time' => $end_time - $start_time,
            'result' => $result,
        );
    }

    /**
     * Generate random string
     *
     * @param int $length String length
     * @return string Random string
     */
    public static function generate_random_string( $length = 10 ) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';

        for ( $i = 0; $i < $length; $i++ ) {
            $string .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
        }

        return $string;
    }

    /**
     * Create temporary directory
     *
     * @param string $prefix Directory prefix
     * @return string Directory path
     */
    public static function create_temp_directory( $prefix = 'auto_featured_image_test_' ) {
        $temp_dir = sys_get_temp_dir() . '/' . $prefix . self::generate_random_string( 8 );
        
        if ( ! file_exists( $temp_dir ) ) {
            mkdir( $temp_dir, 0755, true );
        }

        return $temp_dir;
    }

    /**
     * Remove temporary directory
     *
     * @param string $dir Directory path
     */
    public static function remove_temp_directory( $dir ) {
        if ( is_dir( $dir ) ) {
            $files = array_diff( scandir( $dir ), array( '.', '..' ) );
            
            foreach ( $files as $file ) {
                $file_path = $dir . '/' . $file;
                if ( is_dir( $file_path ) ) {
                    self::remove_temp_directory( $file_path );
                } else {
                    unlink( $file_path );
                }
            }
            
            rmdir( $dir );
        }
    }

    /**
     * Clean up test environment
     */
    public static function cleanup() {
        // Clear function mocks
        self::clear_function_mocks();

        // Clear transients
        self::clear_plugin_transients();

        // Clear caches
        wp_cache_flush();
    }
}
