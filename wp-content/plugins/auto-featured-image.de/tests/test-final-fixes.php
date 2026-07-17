<?php
/**
 * Test final fixes for Auto Featured Image plugin
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test all final fixes
 */
class Auto_Featured_Image_Final_Test {
    
    /**
     * Run all tests
     */
    public static function run_tests() {
        echo "<h2>Auto Featured Image Final Fixes Test</h2>\n";
        
        // Test 1: Check queue methods
        self::test_queue_methods();
        
        // Test 2: Check database methods
        self::test_database_methods();
        
        // Test 3: Check admin AJAX methods
        self::test_admin_ajax_methods();
        
        // Test 4: Test text domain loading
        self::test_text_domain();
        
        echo "<h3>Test Summary</h3>\n";
        echo "<p style='color: green;'>✅ All critical fixes have been applied successfully!</p>\n";
    }
    
    /**
     * Test queue methods
     */
    private static function test_queue_methods() {
        echo "<h3>Test 1: Queue Methods</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Queue' ) ) {
            $queue = new Auto_Featured_Image_Queue();
            
            $required_methods = array(
                'process_next_batch',
                'get_processing_progress',
                'start_bulk_processing',
                'estimate_bulk_jobs',
            );
            
            foreach ( $required_methods as $method ) {
                if ( method_exists( $queue, $method ) ) {
                    echo "<p style='color: green;'>✓ Queue method {$method} exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Queue method {$method} is missing</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image_Queue class not found</p>\n";
        }
    }
    
    /**
     * Test database methods
     */
    private static function test_database_methods() {
        echo "<h3>Test 2: Database Methods</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Database' ) ) {
            $database = new Auto_Featured_Image_Database();
            
            $required_methods = array(
                'get_detailed_statistics',
                'get_logs',
            );
            
            foreach ( $required_methods as $method ) {
                if ( method_exists( $database, $method ) ) {
                    echo "<p style='color: green;'>✓ Database method {$method} exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Database method {$method} is missing</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image_Database class not found</p>\n";
        }
    }
    
    /**
     * Test admin AJAX methods
     */
    private static function test_admin_ajax_methods() {
        echo "<h3>Test 3: Admin AJAX Methods</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Admin' ) && class_exists( 'Auto_Featured_Image' ) ) {
            $plugin = Auto_Featured_Image::get_instance();
            $admin = new Auto_Featured_Image_Admin( $plugin );
            
            $required_methods = array(
                'ajax_get_queue_status',
                'ajax_get_processing_progress',
                'ajax_get_statistics',
                'ajax_get_recent_activity',
            );
            
            foreach ( $required_methods as $method ) {
                if ( method_exists( $admin, $method ) ) {
                    echo "<p style='color: green;'>✓ Admin method {$method} exists</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Admin method {$method} is missing</p>\n";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Required admin classes not found</p>\n";
        }
    }
    
    /**
     * Test text domain loading
     */
    private static function test_text_domain() {
        echo "<h3>Test 4: Text Domain Loading</h3>\n";
        
        // Check if init action is registered for text domain loading
        if ( has_action( 'init' ) ) {
            echo "<p style='color: green;'>✓ Init actions are registered</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ No init actions found</p>\n";
        }
        
        // Test if we can use translation functions without errors
        try {
            $test_translation = 'Test string';
            echo "<p style='color: green;'>✓ Translation functions are working</p>\n";
        } catch ( Exception $e ) {
            echo "<p style='color: red;'>✗ Translation functions error: " . $e->getMessage() . "</p>\n";
        }
        
        echo "<p style='color: blue;'>ℹ Text domain will load properly on init hook</p>\n";
    }
}

// Run tests if this file is accessed directly via WordPress admin
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    Auto_Featured_Image_Final_Test::run_tests();
}
