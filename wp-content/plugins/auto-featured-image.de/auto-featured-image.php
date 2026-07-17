<?php
/**
 * Plugin Name: Auto Featured Image
 * Plugin URI: https://github.com/your-username/auto-featured-image
 * Description: Automatically assign featured images to posts that lack them using high-performance background processing. Designed for websites with large datasets (millions of posts and images).
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: auto-featured-image
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package AutoFeaturedImage
 * @version 1.0.0
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'AUTO_FEATURED_IMAGE_VERSION', '1.0.0' );
define( 'AUTO_FEATURED_IMAGE_PLUGIN_FILE', __FILE__ );
define( 'AUTO_FEATURED_IMAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AUTO_FEATURED_IMAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AUTO_FEATURED_IMAGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AUTO_FEATURED_IMAGE_TEXT_DOMAIN', 'auto-featured-image' );
define( 'AUTO_FEATURED_IMAGE_DB_VERSION', '1.0.0' );

// Minimum requirements
define( 'AUTO_FEATURED_IMAGE_MIN_WP_VERSION', '5.0' );
define( 'AUTO_FEATURED_IMAGE_MIN_PHP_VERSION', '7.4' );

/**
 * Check if the current environment meets the plugin requirements
 *
 * @return bool True if requirements are met, false otherwise
 */
function auto_featured_image_check_requirements() {
    global $wp_version;
    
    // Check WordPress version
    if ( version_compare( $wp_version, AUTO_FEATURED_IMAGE_MIN_WP_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'auto_featured_image_wp_version_notice' );
        return false;
    }
    
    // Check PHP version
    if ( version_compare( PHP_VERSION, AUTO_FEATURED_IMAGE_MIN_PHP_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'auto_featured_image_php_version_notice' );
        return false;
    }
    
    return true;
}

/**
 * Display WordPress version requirement notice
 */
function auto_featured_image_wp_version_notice() {
    $message = sprintf(
        /* translators: 1: Plugin name, 2: Required WordPress version, 3: Current WordPress version */
        esc_html__( '%1$s requires WordPress version %2$s or higher. You are running version %3$s. Please update WordPress.', 'auto-featured-image' ),
        '<strong>Auto Featured Image</strong>',
        AUTO_FEATURED_IMAGE_MIN_WP_VERSION,
        $GLOBALS['wp_version']
    );
    
    printf( '<div class="notice notice-error"><p>%s</p></div>', $message );
}

/**
 * Display PHP version requirement notice
 */
function auto_featured_image_php_version_notice() {
    $message = sprintf(
        /* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
        esc_html__( '%1$s requires PHP version %2$s or higher. You are running version %3$s. Please update PHP.', 'auto-featured-image' ),
        '<strong>Auto Featured Image</strong>',
        AUTO_FEATURED_IMAGE_MIN_PHP_VERSION,
        PHP_VERSION
    );
    
    printf( '<div class="notice notice-error"><p>%s</p></div>', $message );
}

/**
 * Initialize the plugin
 */
function auto_featured_image_init() {
    // Check requirements before proceeding
    if ( ! auto_featured_image_check_requirements() ) {
        return;
    }

    // Include required files
    require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image.php';

    // Initialize the main plugin class
    Auto_Featured_Image::get_instance();
}

/**
 * Plugin activation hook
 */
function auto_featured_image_activate() {
    // Check requirements on activation
    if ( ! auto_featured_image_check_requirements() ) {
        deactivate_plugins( AUTO_FEATURED_IMAGE_PLUGIN_BASENAME );
        wp_die( 
            esc_html__( 'Auto Featured Image plugin cannot be activated due to unmet requirements.', 'auto-featured-image' ),
            esc_html__( 'Plugin Activation Error', 'auto-featured-image' ),
            array( 'back_link' => true )
        );
    }
    
    // Include activation class
    require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-activator.php';
    Auto_Featured_Image_Activator::activate();
}

/**
 * Plugin deactivation hook
 */
function auto_featured_image_deactivate() {
    // Include deactivation class
    require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-deactivator.php';
    Auto_Featured_Image_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'auto_featured_image_activate' );
register_deactivation_hook( __FILE__, 'auto_featured_image_deactivate' );

// Initialize the plugin
add_action( 'plugins_loaded', 'auto_featured_image_init' );

/**
 * Add plugin action links
 *
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
function auto_featured_image_plugin_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url( 'tools.php?page=auto-featured-image' ),
        esc_html__( 'Settings', 'auto-featured-image' )
    );
    
    array_unshift( $links, $settings_link );
    
    return $links;
}
add_filter( 'plugin_action_links_' . AUTO_FEATURED_IMAGE_PLUGIN_BASENAME, 'auto_featured_image_plugin_action_links' );

/**
 * Add plugin meta links
 *
 * @param array  $links Existing plugin meta links
 * @param string $file  Plugin file path
 * @return array Modified plugin meta links
 */
function auto_featured_image_plugin_meta_links( $links, $file ) {
    if ( $file === AUTO_FEATURED_IMAGE_PLUGIN_BASENAME ) {
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/your-username/auto-featured-image/wiki',
            esc_html__( 'Documentation', 'auto-featured-image' )
        );
        
        $links[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://github.com/your-username/auto-featured-image/issues',
            esc_html__( 'Support', 'auto-featured-image' )
        );
    }
    
    return $links;
}
add_filter( 'plugin_row_meta', 'auto_featured_image_plugin_meta_links', 10, 2 );
