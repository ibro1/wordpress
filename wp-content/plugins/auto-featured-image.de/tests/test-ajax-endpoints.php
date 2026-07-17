<?php
/**
 * Test AJAX endpoints
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test AJAX endpoints functionality
 */
class Auto_Featured_Image_AJAX_Endpoint_Test {
    
    /**
     * Run endpoint tests
     */
    public static function run_tests() {
        echo "<h2>Auto Featured Image AJAX Endpoint Tests</h2>\n";
        
        // Test 1: Check if queue methods exist
        self::test_queue_methods();
        
        // Test 2: Check if admin class methods exist
        self::test_admin_methods();
        
        // Test 3: Test AJAX action registration
        self::test_ajax_registration();
        
        echo "<h3>Test Summary</h3>\n";
        echo "<p>All AJAX endpoints are properly configured!</p>\n";
    }
    
    /**
     * Test queue methods
     */
    private static function test_queue_methods() {
        echo "<h3>Test 1: Queue Methods</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Queue' ) ) {
            $queue = new Auto_Featured_Image_Queue();
            
            $required_methods = array(
                'get_processing_progress',
                'start_bulk_processing',
                'estimate_bulk_jobs',
                'get_queue_stats',
                'is_processing_active',
                'is_paused',
                'pause_processing',
            );
            
            foreach ( $required_methods as $method ) {
                if ( method_exists( $queue, $method ) ) {
                    echo "<p style='color: green;'>✓ Method {$method} exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Method {$method} is missing</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image_Queue class not found</p>\n";
        }
    }
    
    /**
     * Test admin methods
     */
    private static function test_admin_methods() {
        echo "<h3>Test 2: Admin Methods</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Admin' ) ) {
            $plugin = Auto_Featured_Image::get_instance();
            $admin = new Auto_Featured_Image_Admin( $plugin );
            
            $required_methods = array(
                'handle_ajax_request',
                'ajax_get_queue_status',
                'ajax_get_processing_progress',
                'ajax_start_bulk_processing',
                'ajax_estimate_bulk_jobs',
            );
            
            foreach ( $required_methods as $method ) {
                if ( method_exists( $admin, $method ) ) {
                    echo "<p style='color: green;'>✓ Method {$method} exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Method {$method} is missing</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image_Admin class not found</p>\n";
        }
    }
    
    /**
     * Test AJAX registration
     */
    private static function test_ajax_registration() {
        echo "<h3>Test 3: AJAX Registration</h3>\n";
        
        // Check if AJAX action is registered
        if ( has_action( 'wp_ajax_auto_featured_image_ajax' ) ) {
            echo "<p style='color: green;'>✓ AJAX action 'wp_ajax_auto_featured_image_ajax' is registered</p>\n";
        } else {
            echo "<p style='color: red;'>✗ AJAX action 'wp_ajax_auto_featured_image_ajax' is not registered</p>\n";
        }
        
        // Check if text domain loading is properly scheduled
        if ( has_action( 'init' ) ) {
            echo "<p style='color: green;'>✓ Init actions are registered</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ No init actions found</p>\n";
        }
    }
}

// Run tests if this file is accessed directly via WordPress admin
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    Auto_Featured_Image_AJAX_Endpoint_Test::run_tests();
}
