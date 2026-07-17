<?php
/**
 * Plugin Name:       Auto Featured Image
 * Plugin URI:        https://davebukartechnologies.com/
 * Description:       Automatically assigns featured images to posts/pages using a high-performance background processing queue.
 * Version:           1.0.3
 * Author:            Davebukar Technologies
 * Author URI:        https://davebukartechnologies.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-featured-image
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'AFI_VERSION', '1.0.3' );
define( 'AFI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFI_MAIN_FILE', __FILE__ );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require AFI_PLUGIN_DIR . 'includes/class-afi-core.php';


// ======================================================================
// Load dependencies needed for activation/deactivation hooks, as they run
// outside the normal plugin instantiation lifecycle.
// ======================================================================
require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';


register_activation_hook( AFI_MAIN_FILE, array( 'AFI_Core', 'activate' ) );
register_deactivation_hook( AFI_MAIN_FILE, array( 'AFI_Core', 'deactivate' ) );


/**
 * Begins execution of the plugin.
 *
 * This function is hooked to `plugins_loaded` to ensure that all other plugins,
 * like Action Scheduler, are loaded before this plugin's logic runs.
 *
 * @since    1.0.2
 */
function run_auto_featured_image() {
    // Check for Action Scheduler dependency.
    if ( ! function_exists( 'as_enqueue_async_action' ) ) {
        add_action( 'admin_notices', 'afi_action_scheduler_missing_notice' );
        return; // Stop execution if dependency is missing.
    }

    // If we get here, the dependency exists, so we can run the plugin.
    $plugin = new AFI_Core();
    $plugin->run();
}

/**
 * Admin notice for missing Action Scheduler.
 *
 * This function is now only hooked if the dependency check fails.
 *
 * @since    1.0.0
 */
function afi_action_scheduler_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( '<strong>Auto Featured Image</strong> requires the <strong>Action Scheduler</strong> plugin to be installed and active. Please install it to continue.', 'auto-featured-image' ); ?></p>
    </div>
    <?php
}

// ======================================================================
// THE FIX: Use the `plugins_loaded` hook.
//
// We hook our main execution function `run_auto_featured_image` into the
// `plugins_loaded` action. This guarantees that by the time our function
// runs, WordPress has finished loading all other active plugins, including
// Action Scheduler. This solves the load order problem.
// ======================================================================
add_action( 'plugins_loaded', 'run_auto_featured_image' );