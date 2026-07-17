<?php
/**
 * Plugin Activator Class
 *
 * Handles plugin activation tasks including database setup,
 * default options, and initial configuration.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Activator Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Activator {

    /**
     * Activate the plugin
     *
     * This method is called when the plugin is activated.
     * It handles database creation, default settings, and initial setup.
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule events
        self::schedule_events();
        
        // Set activation flag
        update_option( 'auto_featured_image_activated', true );
        
        // Log activation
        error_log( 'Auto Featured Image plugin activated successfully.' );
    }

    /**
     * Create database tables
     *
     * @since 1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Jobs table for queue management
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';
        $jobs_sql = "CREATE TABLE $jobs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            priority int(11) NOT NULL DEFAULT 10,
            attempts int(11) NOT NULL DEFAULT 0,
            max_attempts int(11) NOT NULL DEFAULT 3,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            scheduled_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY priority (priority),
            KEY scheduled_at (scheduled_at)
        ) $charset_collate;";

        // Progress tracking table
        $progress_table = $wpdb->prefix . 'auto_featured_image_progress';
        $progress_sql = "CREATE TABLE $progress_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            batch_id varchar(100) NOT NULL,
            total_posts int(11) NOT NULL DEFAULT 0,
            processed_posts int(11) NOT NULL DEFAULT 0,
            successful_posts int(11) NOT NULL DEFAULT 0,
            failed_posts int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY batch_id (batch_id),
            KEY status (status)
        ) $charset_collate;";

        // Log table for debugging and monitoring
        $log_table = $wpdb->prefix . 'auto_featured_image_log';
        $log_sql = "CREATE TABLE $log_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext DEFAULT NULL,
            post_id bigint(20) unsigned DEFAULT NULL,
            batch_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY post_id (post_id),
            KEY batch_id (batch_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta( $jobs_sql );
        dbDelta( $progress_sql );
        dbDelta( $log_sql );

        // Update database version
        update_option( 'auto_featured_image_db_version', AUTO_FEATURED_IMAGE_DB_VERSION );
    }

    /**
     * Set default plugin options
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        $default_settings = array(
            'batch_size' => 50,
            'max_execution_time' => 300,
            'memory_limit' => '256M',
            'image_selection_method' => 'first_content_image',
            'fallback_method' => 'media_library',
            'post_types' => array( 'post' ),
            'post_status' => array( 'publish' ),
            'skip_posts_with_images' => true,
            'enable_logging' => true,
            'log_level' => 'info',
            'cleanup_logs_days' => 30,
            'enable_notifications' => true,
            'notification_email' => get_option( 'admin_email' ),
            'enable_health_checks' => true,
            'health_check_interval' => 'hourly',
        );

        // Only set defaults if no settings exist
        if ( ! get_option( 'auto_featured_image_settings' ) ) {
            update_option( 'auto_featured_image_settings', $default_settings );
        }

        // Set plugin version
        update_option( 'auto_featured_image_version', AUTO_FEATURED_IMAGE_VERSION );

        // Initialize statistics
        $default_stats = array(
            'total_processed' => 0,
            'total_successful' => 0,
            'total_failed' => 0,
            'last_run' => null,
            'total_runtime' => 0,
        );

        if ( ! get_option( 'auto_featured_image_stats' ) ) {
            update_option( 'auto_featured_image_stats', $default_stats );
        }
    }

    /**
     * Schedule recurring events
     *
     * @since 1.0.0
     */
    private static function schedule_events() {
        // Schedule cleanup event
        if ( ! wp_next_scheduled( 'auto_featured_image_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'auto_featured_image_cleanup' );
        }

        // Schedule health check event
        if ( ! wp_next_scheduled( 'auto_featured_image_health_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'auto_featured_image_health_check' );
        }
    }
}
