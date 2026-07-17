<?php
/**
 * Auto Featured Image Uninstall
 *
 * Handles cleanup when the plugin is deleted.
 * This file is called when the plugin is uninstalled via the WordPress admin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Security check
if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
}

/**
 * Clean up plugin data on uninstall
 */
function auto_featured_image_uninstall_cleanup() {
    global $wpdb;

    // Remove plugin options
    $options_to_delete = array(
        'auto_featured_image_settings',
        'auto_featured_image_version',
        'auto_featured_image_db_version',
        'auto_featured_image_stats',
        'auto_featured_image_last_run',
    );

    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
        delete_site_option( $option ); // For multisite
    }

    // Remove custom database tables
    $table_names = array(
        $wpdb->prefix . 'auto_featured_image_jobs',
        $wpdb->prefix . 'auto_featured_image_progress',
        $wpdb->prefix . 'auto_featured_image_log',
    );

    foreach ( $table_names as $table_name ) {
        $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );
    }

    // Clear any scheduled events
    wp_clear_scheduled_hook( 'auto_featured_image_cleanup' );
    wp_clear_scheduled_hook( 'auto_featured_image_health_check' );

    // Remove any Action Scheduler actions related to this plugin
    if ( class_exists( 'ActionScheduler' ) ) {
        // Cancel any pending actions
        $action_scheduler_store = ActionScheduler::store();
        $pending_actions = $action_scheduler_store->query_actions( array(
            'hook' => 'auto_featured_image_process_batch',
            'status' => ActionScheduler_Store::STATUS_PENDING,
        ) );

        foreach ( $pending_actions as $action_id ) {
            $action_scheduler_store->cancel_action( $action_id );
        }
    }

    // Remove any uploaded plugin files (if any)
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/auto-featured-image/';
    
    if ( is_dir( $plugin_upload_dir ) ) {
        auto_featured_image_remove_directory( $plugin_upload_dir );
    }

    // Clear any cached data
    wp_cache_flush();
}

/**
 * Recursively remove directory and its contents
 *
 * @param string $dir Directory path to remove
 * @return bool True on success, false on failure
 */
function auto_featured_image_remove_directory( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return false;
    }

    $files = array_diff( scandir( $dir ), array( '.', '..' ) );
    
    foreach ( $files as $file ) {
        $file_path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if ( is_dir( $file_path ) ) {
            auto_featured_image_remove_directory( $file_path );
        } else {
            unlink( $file_path );
        }
    }
    
    return rmdir( $dir );
}

// Execute cleanup
auto_featured_image_uninstall_cleanup();
