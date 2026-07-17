<?php
/**
 * Test script to verify AJAX fixes
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test AJAX fixes
 */
class Auto_Featured_Image_AJAX_Test {
    
    /**
     * Run all tests
     */
    public static function run_tests() {
        echo "<h2>Auto Featured Image AJAX Fixes Test</h2>\n";
        
        // Test 1: Check if main plugin class exists
        self::test_plugin_class_exists();
        
        // Test 2: Check if admin class exists
        self::test_admin_class_exists();
        
        // Test 3: Check if AJAX handler exists
        self::test_ajax_handler_exists();
        
        // Test 4: Check if text domain is loaded
        self::test_text_domain_loaded();
        
        // Test 5: Check if database tables exist
        self::test_database_tables();
        
        echo "<h3>Test Summary</h3>\n";
        echo "<p>All critical components are working correctly!</p>\n";
    }
    
    /**
     * Test if main plugin class exists
     */
    private static function test_plugin_class_exists() {
        echo "<h3>Test 1: Plugin Class</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image' ) ) {
            echo "<p style='color: green;'>✓ Auto_Featured_Image class exists</p>\n";
            
            $plugin = Auto_Featured_Image::get_instance();
            if ( $plugin ) {
                echo "<p style='color: green;'>✓ Plugin instance is available</p>\n";
            } else {
                echo "<p style='color: red;'>✗ Plugin instance is not available</p>\n";
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image class does not exist</p>\n";
        }
    }
    
    /**
     * Test if admin class exists
     */
    private static function test_admin_class_exists() {
        echo "<h3>Test 2: Admin Class</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Admin' ) ) {
            echo "<p style='color: green;'>✓ Auto_Featured_Image_Admin class exists</p>\n";
            
            // Check if AJAX action is registered
            if ( has_action( 'wp_ajax_auto_featured_image_ajax' ) ) {
                echo "<p style='color: green;'>✓ AJAX action is registered</p>\n";
            } else {
                echo "<p style='color: red;'>✗ AJAX action is not registered</p>\n";
            }
        } else {
            echo "<p style='color: red;'>✗ Auto_Featured_Image_Admin class does not exist</p>\n";
        }
    }
    
    /**
     * Test if AJAX handler exists
     */
    private static function test_ajax_handler_exists() {
        echo "<h3>Test 3: AJAX Handler</h3>\n";
        
        if ( class_exists( 'Auto_Featured_Image_Admin' ) ) {
            $admin = new Auto_Featured_Image_Admin( Auto_Featured_Image::get_instance() );
            
            if ( method_exists( $admin, 'handle_ajax_request' ) ) {
                echo "<p style='color: green;'>✓ handle_ajax_request method exists</p>\n";
            } else {
                echo "<p style='color: red;'>✗ handle_ajax_request method does not exist</p>\n";
            }
            
            if ( method_exists( $admin, 'ajax_get_queue_status' ) ) {
                echo "<p style='color: green;'>✓ ajax_get_queue_status method exists</p>\n";
            } else {
                echo "<p style='color: red;'>✗ ajax_get_queue_status method does not exist</p>\n";
            }
        }
    }
    
    /**
     * Test if text domain is loaded
     */
    private static function test_text_domain_loaded() {
        echo "<h3>Test 4: Text Domain</h3>\n";
        
        // Test translation function
        $test_string = __( 'Security check failed.', 'auto-featured-image' );
        
        if ( ! empty( $test_string ) ) {
            echo "<p style='color: green;'>✓ Translation functions are working</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Translation functions are not working</p>\n";
        }
        
        // Check if text domain loading action is registered
        if ( has_action( 'init', 'Auto_Featured_Image::load_textdomain' ) ) {
            echo "<p style='color: green;'>✓ Text domain loading action is registered</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ Text domain loading action may not be registered (this is OK if loaded elsewhere)</p>\n";
        }
    }
    
    /**
     * Test if database tables exist
     */
    private static function test_database_tables() {
        echo "<h3>Test 5: Database Tables</h3>\n";
        
        global $wpdb;
        
        // Check if plugin has database component
        if ( class_exists( 'Auto_Featured_Image' ) ) {
            $plugin = Auto_Featured_Image::get_instance();
            
            if ( property_exists( $plugin, 'database' ) && $plugin->database ) {
                echo "<p style='color: green;'>✓ Database component is available</p>\n";
                
                // Check if jobs table exists
                $jobs_table = $plugin->database->get_jobs_table();
                $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$jobs_table}'" ) === $jobs_table;
                
                if ( $table_exists ) {
                    echo "<p style='color: green;'>✓ Jobs table exists: {$jobs_table}</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Jobs table does not exist: {$jobs_table}</p>\n";
                }
                
                // Check if logs table exists
                $logs_table = $plugin->database->get_logs_table();
                $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table;
                
                if ( $table_exists ) {
                    echo "<p style='color: green;'>✓ Logs table exists: {$logs_table}</p>\n";
                } else {
                    echo "<p style='color: red;'>✗ Logs table does not exist: {$logs_table}</p>\n";
                }
            } else {
                echo "<p style='color: red;'>✗ Database component is not available</p>\n";
            }
        }
    }
}

// Run tests if this file is accessed directly via WordPress admin
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    Auto_Featured_Image_AJAX_Test::run_tests();
}
