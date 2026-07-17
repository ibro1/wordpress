<?php
/**
 * Plugin Name: Auto Featured Image
 * Plugin URI: https://example.com/auto-featured-image
 * Description: Automatically assign featured images to posts that lack them using asynchronous background processing for large datasets.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AFI_VERSION', '1.0.0');
define('AFI_PLUGIN_FILE', __FILE__);
define('AFI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Temporary: Enable immediate processing for testing
define('AFI_IMMEDIATE_PROCESSING', true);

/**
 * Plugin activation hook
 */
function afi_activate_plugin() {
    // Load the main plugin class
    require_once AFI_PLUGIN_DIR . 'includes/class-afi-plugin.php';
    
    // Run activation procedures
    AFI_Plugin::activate();
}
register_activation_hook(__FILE__, 'afi_activate_plugin');

/**
 * Plugin deactivation hook
 */
function afi_deactivate_plugin() {
    // Load the main plugin class
    require_once AFI_PLUGIN_DIR . 'includes/class-afi-plugin.php';
    
    // Run deactivation procedures
    AFI_Plugin::deactivate();
}
register_deactivation_hook(__FILE__, 'afi_deactivate_plugin');

/**
 * Plugin uninstall hook
 */
function afi_uninstall_plugin() {
    // Load the main plugin class
    require_once AFI_PLUGIN_DIR . 'includes/class-afi-plugin.php';
    
    // Run uninstall procedures
    AFI_Plugin::uninstall();
}
register_uninstall_hook(__FILE__, 'afi_uninstall_plugin');

/**
 * Initialize the plugin
 */
function afi_init_plugin() {
    // Load the main plugin class
    require_once AFI_PLUGIN_DIR . 'includes/class-afi-plugin.php';
    
    // Initialize and run the plugin
    $plugin = new AFI_Plugin();
    $plugin->run();
}

// Hook into WordPress init
add_action('plugins_loaded', 'afi_init_plugin');