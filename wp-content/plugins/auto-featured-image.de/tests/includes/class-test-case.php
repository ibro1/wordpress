<?php
/**
 * Base Test Case Class
 *
 * Provides common functionality for all test cases.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Auto Featured Image Test Case
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Test_Case extends WP_UnitTestCase {

    /**
     * Plugin instance
     *
     * @var Auto_Featured_Image
     */
    protected $plugin;

    /**
     * Test factory
     *
     * @var Auto_Featured_Image_Test_Factory
     */
    protected $test_factory;

    /**
     * Test utilities
     *
     * @var Auto_Featured_Image_Test_Utils
     */
    protected $test_utils;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();

        // Get plugin instance
        $this->plugin = Auto_Featured_Image::get_instance();

        // Initialize test utilities
        $this->test_factory = new Auto_Featured_Image_Test_Factory();
        $this->test_utils = new Auto_Featured_Image_Test_Utils();

        // Create test database tables
        $this->plugin->database->create_tables();

        // Clear any existing data
        $this->clean_up_test_data();

        // Set up default test settings
        $this->set_up_default_settings();
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        // Clean up test data
        $this->clean_up_test_data();

        // Reset plugin state
        $this->reset_plugin_state();

        parent::tearDown();
    }

    /**
     * Clean up test data
     */
    protected function clean_up_test_data() {
        global $wpdb;

        // Clear plugin tables
        $tables = array(
            $this->plugin->database->get_jobs_table(),
            $this->plugin->database->get_progress_table(),
            $this->plugin->database->get_log_table(),
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "TRUNCATE TABLE {$table}" );
        }

        // Clear plugin options
        delete_option( 'auto_featured_image_settings' );
        delete_option( 'auto_featured_image_db_version' );

        // Clear transients
        $this->test_utils->clear_plugin_transients();

        // Clear caches
        wp_cache_flush();
    }

    /**
     * Reset plugin state
     */
    protected function reset_plugin_state() {
        // Reset queue state
        if ( $this->plugin->queue ) {
            $this->plugin->queue->clear_queue();
        }

        // Reset batch manager state
        if ( $this->plugin->batch_manager ) {
            $this->plugin->batch_manager->reset_state();
        }

        // Reset error handler state
        if ( $this->plugin->error_handler ) {
            $this->plugin->error_handler->reset_error_statistics();
        }
    }

    /**
     * Set up default test settings
     */
    protected function set_up_default_settings() {
        $default_settings = array(
            'auto_processing_enabled' => true,
            'post_types' => array( 'post' ),
            'enabled_algorithms' => array( 'first_quality_image', 'content_based' ),
            'batch_size' => 10,
            'skip_existing' => true,
            'log_level' => 'debug',
        );

        update_option( 'auto_featured_image_settings', $default_settings );
    }

    /**
     * Create test post with content
     *
     * @param array $args Post arguments
     * @return int Post ID
     */
    protected function create_test_post( $args = array() ) {
        $defaults = array(
            'post_title' => 'Test Post',
            'post_content' => $this->get_test_post_content(),
            'post_status' => 'publish',
            'post_type' => 'post',
        );

        $args = wp_parse_args( $args, $defaults );
        return $this->factory->post->create( $args );
    }

    /**
     * Create test attachment
     *
     * @param array $args Attachment arguments
     * @return int Attachment ID
     */
    protected function create_test_attachment( $args = array() ) {
        $defaults = array(
            'post_mime_type' => 'image/jpeg',
            'post_title' => 'Test Image',
            'post_status' => 'inherit',
        );

        $args = wp_parse_args( $args, $defaults );
        $attachment_id = $this->factory->attachment->create( $args );

        // Add fake metadata
        $metadata = array(
            'width' => 800,
            'height' => 600,
            'file' => 'test-image.jpg',
            'sizes' => array(
                'thumbnail' => array(
                    'file' => 'test-image-150x150.jpg',
                    'width' => 150,
                    'height' => 150,
                    'mime-type' => 'image/jpeg',
                ),
                'medium' => array(
                    'file' => 'test-image-300x225.jpg',
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
     * Get test post content with images
     *
     * @return string Post content
     */
    protected function get_test_post_content() {
        return '
            <p>This is a test post with some content.</p>
            <img src="https://example.com/image1.jpg" alt="Test Image 1" width="800" height="600" />
            <p>Some more content here.</p>
            <img src="https://example.com/image2.jpg" alt="Test Image 2" width="400" height="300" />
            <p>Final paragraph with more text.</p>
        ';
    }

    /**
     * Assert that a job exists in the queue
     *
     * @param int    $post_id Post ID
     * @param string $status Job status
     */
    protected function assertJobExists( $post_id, $status = 'pending' ) {
        $job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertNotNull( $job, "Job for post {$post_id} should exist" );
        $this->assertEquals( $status, $job['status'], "Job status should be {$status}" );
    }

    /**
     * Assert that a job does not exist in the queue
     *
     * @param int $post_id Post ID
     */
    protected function assertJobNotExists( $post_id ) {
        $job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertNull( $job, "Job for post {$post_id} should not exist" );
    }

    /**
     * Assert that a log entry exists
     *
     * @param string $level Log level
     * @param string $message Log message (partial match)
     * @param int    $post_id Optional post ID
     */
    protected function assertLogExists( $level, $message, $post_id = null ) {
        $logs = $this->plugin->database->get_logs( array(
            'level' => $level,
            'limit' => 100,
        ) );

        $found = false;
        foreach ( $logs as $log ) {
            if ( strpos( $log['message'], $message ) !== false ) {
                if ( $post_id === null || $log['post_id'] == $post_id ) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue( $found, "Log entry with level '{$level}' and message containing '{$message}' should exist" );
    }

    /**
     * Assert that post has featured image
     *
     * @param int $post_id Post ID
     * @param int $attachment_id Optional specific attachment ID
     */
    protected function assertPostHasFeaturedImage( $post_id, $attachment_id = null ) {
        $featured_image_id = get_post_thumbnail_id( $post_id );
        $this->assertNotEmpty( $featured_image_id, "Post {$post_id} should have a featured image" );

        if ( $attachment_id !== null ) {
            $this->assertEquals( $attachment_id, $featured_image_id, "Post should have specific featured image" );
        }
    }

    /**
     * Assert that post does not have featured image
     *
     * @param int $post_id Post ID
     */
    protected function assertPostHasNoFeaturedImage( $post_id ) {
        $featured_image_id = get_post_thumbnail_id( $post_id );
        $this->assertEmpty( $featured_image_id, "Post {$post_id} should not have a featured image" );
    }

    /**
     * Mock WordPress functions for testing
     *
     * @param string $function Function name
     * @param mixed  $return_value Return value
     */
    protected function mock_wp_function( $function, $return_value ) {
        $this->test_utils->mock_function( $function, $return_value );
    }

    /**
     * Simulate processing delay
     *
     * @param float $seconds Seconds to delay
     */
    protected function simulate_processing_delay( $seconds = 0.1 ) {
        usleep( $seconds * 1000000 );
    }

    /**
     * Get plugin setting
     *
     * @param string $key Setting key
     * @param mixed  $default Default value
     * @return mixed Setting value
     */
    protected function get_plugin_setting( $key, $default = null ) {
        $settings = get_option( 'auto_featured_image_settings', array() );
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Set plugin setting
     *
     * @param string $key Setting key
     * @param mixed  $value Setting value
     */
    protected function set_plugin_setting( $key, $value ) {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $settings[ $key ] = $value;
        update_option( 'auto_featured_image_settings', $settings );
    }

    /**
     * Trigger WordPress action
     *
     * @param string $action Action name
     * @param mixed  ...$args Action arguments
     */
    protected function trigger_action( $action, ...$args ) {
        do_action( $action, ...$args );
    }

    /**
     * Capture output from action or function
     *
     * @param callable $callback Callback to execute
     * @return string Captured output
     */
    protected function capture_output( $callback ) {
        ob_start();
        call_user_func( $callback );
        return ob_get_clean();
    }

    /**
     * Skip test if requirements not met
     *
     * @param array $requirements Requirements to check
     */
    protected function skip_if_requirements_not_met( $requirements ) {
        foreach ( $requirements as $requirement => $condition ) {
            switch ( $requirement ) {
                case 'extension':
                    if ( ! extension_loaded( $condition ) ) {
                        $this->markTestSkipped( "Extension {$condition} is required" );
                    }
                    break;

                case 'function':
                    if ( ! function_exists( $condition ) ) {
                        $this->markTestSkipped( "Function {$condition} is required" );
                    }
                    break;

                case 'class':
                    if ( ! class_exists( $condition ) ) {
                        $this->markTestSkipped( "Class {$condition} is required" );
                    }
                    break;

                case 'wp_version':
                    global $wp_version;
                    if ( version_compare( $wp_version, $condition, '<' ) ) {
                        $this->markTestSkipped( "WordPress {$condition} or higher is required" );
                    }
                    break;
            }
        }
    }
}
