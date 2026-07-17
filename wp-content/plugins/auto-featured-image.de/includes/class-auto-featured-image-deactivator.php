<?php
/**
 * Plugin Deactivator Class
 *
 * Handles plugin deactivation tasks including cleanup of scheduled events
 * and temporary data. Does not remove user data or settings.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Deactivator Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Deactivator {

    /**
     * Deactivate the plugin
     *
     * This method is called when the plugin is deactivated.
     * It handles cleanup of scheduled events and temporary data.
     * User settings and data are preserved.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Cancel pending Action Scheduler jobs
        self::cancel_pending_jobs();
        
        // Clear transients
        self::clear_transients();
        
        // Set deactivation flag
        update_option( 'auto_featured_image_deactivated', true );
        
        // Log deactivation
        error_log( 'Auto Featured Image plugin deactivated.' );
    }

    /**
     * Clear all scheduled events
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear cleanup event
        $cleanup_timestamp = wp_next_scheduled( 'auto_featured_image_cleanup' );
        if ( $cleanup_timestamp ) {
            wp_unschedule_event( $cleanup_timestamp, 'auto_featured_image_cleanup' );
        }

        // Clear health check event
        $health_check_timestamp = wp_next_scheduled( 'auto_featured_image_health_check' );
        if ( $health_check_timestamp ) {
            wp_unschedule_event( $health_check_timestamp, 'auto_featured_image_health_check' );
        }

        // Clear all instances of our scheduled events
        wp_clear_scheduled_hook( 'auto_featured_image_cleanup' );
        wp_clear_scheduled_hook( 'auto_featured_image_health_check' );
    }

    /**
     * Cancel pending Action Scheduler jobs
     *
     * @since 1.0.0
     */
    private static function cancel_pending_jobs() {
        // Check if Action Scheduler is available
        if ( ! class_exists( 'ActionScheduler' ) ) {
            return;
        }

        try {
            // Get Action Scheduler store
            $store = ActionScheduler::store();

            // Cancel pending batch processing jobs
            $pending_actions = $store->query_actions( array(
                'hook' => 'auto_featured_image_process_batch',
                'status' => ActionScheduler_Store::STATUS_PENDING,
            ) );

            foreach ( $pending_actions as $action_id ) {
                $store->cancel_action( $action_id );
            }

            // Cancel any running jobs (mark as failed)
            $running_actions = $store->query_actions( array(
                'hook' => 'auto_featured_image_process_batch',
                'status' => ActionScheduler_Store::STATUS_RUNNING,
            ) );

            foreach ( $running_actions as $action_id ) {
                $store->mark_failure( $action_id );
            }

        } catch ( Exception $e ) {
            error_log( 'Error canceling Action Scheduler jobs: ' . $e->getMessage() );
        }
    }

    /**
     * Clear plugin transients
     *
     * @since 1.0.0
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete plugin-specific transients
        $transients = array(
            'auto_featured_image_progress',
            'auto_featured_image_stats',
            'auto_featured_image_health_check',
            'auto_featured_image_batch_status',
        );

        foreach ( $transients as $transient ) {
            delete_transient( $transient );
            delete_site_transient( $transient );
        }

        // Clean up any remaining plugin transients from database
        $wpdb->query( 
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_auto_featured_image_%',
                '_transient_timeout_auto_featured_image_%'
            )
        );

        // Clean up site transients for multisite
        if ( is_multisite() ) {
            $wpdb->query( 
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
                    '_site_transient_auto_featured_image_%',
                    '_site_transient_timeout_auto_featured_image_%'
                )
            );
        }
    }

    /**
     * Update job statuses to cancelled
     *
     * @since 1.0.0
     */
    private static function update_job_statuses() {
        global $wpdb;

        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jobs_table ) ) !== $jobs_table ) {
            return;
        }

        // Update pending and running jobs to cancelled
        $wpdb->update(
            $jobs_table,
            array(
                'status' => 'cancelled',
                'updated_at' => current_time( 'mysql' ),
            ),
            array(
                'status' => 'pending',
            )
        );

        $wpdb->update(
            $jobs_table,
            array(
                'status' => 'cancelled',
                'updated_at' => current_time( 'mysql' ),
            ),
            array(
                'status' => 'running',
            )
        );
    }

    /**
     * Log deactivation statistics
     *
     * @since 1.0.0
     */
    private static function log_deactivation_stats() {
        global $wpdb;

        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';
        $log_table = $wpdb->prefix . 'auto_featured_image_log';

        // Check if tables exist
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $jobs_table ) ) !== $jobs_table ||
             $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $log_table ) ) !== $log_table ) {
            return;
        }

        // Get job statistics
        $total_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $jobs_table" );
        $completed_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $jobs_table WHERE status = 'completed'" );
        $failed_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $jobs_table WHERE status = 'failed'" );
        $cancelled_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $jobs_table WHERE status = 'cancelled'" );

        // Log deactivation with statistics
        $message = sprintf(
            'Plugin deactivated. Statistics: Total jobs: %d, Completed: %d, Failed: %d, Cancelled: %d',
            $total_jobs,
            $completed_jobs,
            $failed_jobs,
            $cancelled_jobs
        );

        $wpdb->insert(
            $log_table,
            array(
                'level' => 'info',
                'message' => $message,
                'context' => wp_json_encode( array(
                    'event' => 'plugin_deactivated',
                    'total_jobs' => $total_jobs,
                    'completed_jobs' => $completed_jobs,
                    'failed_jobs' => $failed_jobs,
                    'cancelled_jobs' => $cancelled_jobs,
                ) ),
                'created_at' => current_time( 'mysql' ),
            )
        );
    }
}
