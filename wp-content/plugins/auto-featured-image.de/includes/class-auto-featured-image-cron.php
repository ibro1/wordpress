<?php
/**
 * Cron Scheduler Class
 *
 * Handles all scheduled tasks and cron job management for the
 * Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Cron Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Cron {

    /**
     * Plugin instance
     *
     * @var Auto_Featured_Image
     * @since 1.0.0
     */
    private $plugin;

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Scheduled events
     *
     * @var array
     * @since 1.0.0
     */
    private $scheduled_events = array();

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->logger = new Auto_Featured_Image_Logger();
        $this->init_scheduled_events();
        $this->init_hooks();
    }

    /**
     * Initialize scheduled events configuration
     *
     * @since 1.0.0
     */
    private function init_scheduled_events() {
        $this->scheduled_events = array(
            'auto_featured_image_process_queue' => array(
                'interval' => 'auto_featured_image_5min',
                'callback' => 'process_queue_cron',
                'description' => 'Process pending jobs in the queue',
                'enabled' => true,
            ),
            'auto_featured_image_cleanup' => array(
                'interval' => 'daily',
                'callback' => 'cleanup_cron',
                'description' => 'Clean up old logs and completed jobs',
                'enabled' => true,
            ),
            'auto_featured_image_health_check' => array(
                'interval' => 'hourly',
                'callback' => 'health_check_cron',
                'description' => 'Perform system health checks',
                'enabled' => true,
            ),
            'auto_featured_image_optimize' => array(
                'interval' => 'auto_featured_image_weekly',
                'callback' => 'optimize_cron',
                'description' => 'Optimize database and performance',
                'enabled' => true,
            ),
            'auto_featured_image_backup_settings' => array(
                'interval' => 'daily',
                'callback' => 'backup_settings_cron',
                'description' => 'Backup plugin settings and configuration',
                'enabled' => false, // Disabled by default
            ),
        );
    }

    /**
     * Initialize cron hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Register custom cron intervals
        add_filter( 'cron_schedules', array( $this, 'add_custom_cron_intervals' ) );
        
        // Register cron event handlers
        foreach ( $this->scheduled_events as $event_name => $event_config ) {
            if ( $event_config['enabled'] && method_exists( $this, $event_config['callback'] ) ) {
                add_action( $event_name, array( $this, $event_config['callback'] ) );
            }
        }
        
        // Plugin activation/deactivation hooks
        register_activation_hook( AUTO_FEATURED_IMAGE_PLUGIN_FILE, array( $this, 'schedule_events' ) );
        register_deactivation_hook( AUTO_FEATURED_IMAGE_PLUGIN_FILE, array( $this, 'unschedule_events' ) );
        
        // Admin hooks for cron management
        add_action( 'admin_init', array( $this, 'maybe_reschedule_events' ) );
    }

    /**
     * Add custom cron intervals
     *
     * @param array $schedules Existing cron schedules
     * @return array Modified schedules
     * @since 1.0.0
     */
    public function add_custom_cron_intervals( $schedules ) {
        $schedules['auto_featured_image_5min'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __( 'Every 5 Minutes', 'auto-featured-image' ),
        );
        
        $schedules['auto_featured_image_15min'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => __( 'Every 15 Minutes', 'auto-featured-image' ),
        );
        
        $schedules['auto_featured_image_30min'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __( 'Every 30 Minutes', 'auto-featured-image' ),
        );
        
        $schedules['auto_featured_image_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __( 'Weekly', 'auto-featured-image' ),
        );
        
        return $schedules;
    }

    /**
     * Schedule all events
     *
     * @since 1.0.0
     */
    public function schedule_events() {
        foreach ( $this->scheduled_events as $event_name => $event_config ) {
            if ( $event_config['enabled'] ) {
                $this->schedule_event( $event_name, $event_config );
            }
        }
        
        $this->logger->info( 'Scheduled cron events', array(
            'events' => array_keys( array_filter( $this->scheduled_events, function( $config ) {
                return $config['enabled'];
            } ) ),
        ) );
    }

    /**
     * Schedule a single event
     *
     * @param string $event_name Event name
     * @param array  $event_config Event configuration
     * @since 1.0.0
     */
    private function schedule_event( $event_name, $event_config ) {
        if ( ! wp_next_scheduled( $event_name ) ) {
            $scheduled = wp_schedule_event( time(), $event_config['interval'], $event_name );
            
            if ( $scheduled === false ) {
                $this->logger->error( "Failed to schedule event: {$event_name}" );
            } else {
                $this->logger->debug( "Scheduled event: {$event_name}", array(
                    'interval' => $event_config['interval'],
                    'next_run' => wp_next_scheduled( $event_name ),
                ) );
            }
        }
    }

    /**
     * Unschedule all events
     *
     * @since 1.0.0
     */
    public function unschedule_events() {
        foreach ( $this->scheduled_events as $event_name => $event_config ) {
            $this->unschedule_event( $event_name );
        }
        
        $this->logger->info( 'Unscheduled cron events' );
    }

    /**
     * Unschedule a single event
     *
     * @param string $event_name Event name
     * @since 1.0.0
     */
    private function unschedule_event( $event_name ) {
        $timestamp = wp_next_scheduled( $event_name );
        
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, $event_name );
            $this->logger->debug( "Unscheduled event: {$event_name}" );
        }
    }

    /**
     * Maybe reschedule events if configuration changed
     *
     * @since 1.0.0
     */
    public function maybe_reschedule_events() {
        $last_config_hash = get_option( 'auto_featured_image_cron_config_hash', '' );
        $current_config_hash = md5( serialize( $this->scheduled_events ) );
        
        if ( $last_config_hash !== $current_config_hash ) {
            $this->logger->info( 'Cron configuration changed, rescheduling events' );
            
            $this->unschedule_events();
            $this->schedule_events();
            
            update_option( 'auto_featured_image_cron_config_hash', $current_config_hash );
        }
    }

    /**
     * Process queue cron job
     *
     * @since 1.0.0
     */
    public function process_queue_cron() {
        $this->logger->debug( 'Running queue processing cron job' );
        
        try {
            $start_time = microtime( true );
            
            // Check if processing is enabled
            if ( ! $this->plugin->queue->is_processing_active() ) {
                $this->logger->debug( 'Queue processing is paused, skipping cron job' );
                return;
            }
            
            // Process next batch
            $result = $this->plugin->queue->process_next_batch();
            
            $execution_time = microtime( true ) - $start_time;
            
            $this->logger->info( 'Queue processing cron completed', array(
                'processed_jobs' => $result['processed'] ?? 0,
                'failed_jobs' => $result['failed'] ?? 0,
                'execution_time' => round( $execution_time, 3 ),
            ) );
            
        } catch ( Exception $e ) {
            $this->logger->error( 'Queue processing cron failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );
        }
    }

    /**
     * Cleanup cron job
     *
     * @since 1.0.0
     */
    public function cleanup_cron() {
        $this->logger->debug( 'Running cleanup cron job' );
        
        try {
            $start_time = microtime( true );
            
            $cleanup_results = array();
            
            // Clean up old logs
            $settings = get_option( 'auto_featured_image_settings', array() );
            $log_retention_days = $settings['log_retention_days'] ?? 30;
            
            $deleted_logs = $this->plugin->database->cleanup_old_logs( $log_retention_days );
            $cleanup_results['deleted_logs'] = $deleted_logs;
            
            // Clean up completed jobs
            $job_retention_days = $settings['cleanup_completed_jobs'] ?? 7;
            $deleted_jobs = $this->plugin->database->cleanup_completed_jobs( $job_retention_days );
            $cleanup_results['deleted_jobs'] = $deleted_jobs;
            
            // Clean up orphaned metadata
            $deleted_metadata = $this->cleanup_orphaned_metadata();
            $cleanup_results['deleted_metadata'] = $deleted_metadata;
            
            // Clean up temporary files
            $deleted_files = $this->cleanup_temporary_files();
            $cleanup_results['deleted_files'] = $deleted_files;
            
            $execution_time = microtime( true ) - $start_time;
            
            $this->logger->info( 'Cleanup cron completed', array_merge( $cleanup_results, array(
                'execution_time' => round( $execution_time, 3 ),
            ) ) );
            
        } catch ( Exception $e ) {
            $this->logger->error( 'Cleanup cron failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Health check cron job
     *
     * @since 1.0.0
     */
    public function health_check_cron() {
        $this->logger->debug( 'Running health check cron job' );
        
        try {
            $start_time = microtime( true );
            
            $health_checks = array(
                'database' => $this->check_database_health(),
                'queue' => $this->check_queue_health(),
                'performance' => $this->check_performance_health(),
                'disk_space' => $this->check_disk_space(),
                'memory_usage' => $this->check_memory_usage(),
            );
            
            $overall_health = $this->calculate_overall_health( $health_checks );
            
            $execution_time = microtime( true ) - $start_time;
            
            $this->logger->info( 'Health check cron completed', array(
                'overall_health' => $overall_health,
                'checks' => $health_checks,
                'execution_time' => round( $execution_time, 3 ),
            ) );
            
            // Take action if health is poor
            if ( $overall_health < 70 ) {
                $this->handle_poor_health( $health_checks );
            }
            
        } catch ( Exception $e ) {
            $this->logger->error( 'Health check cron failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Optimization cron job
     *
     * @since 1.0.0
     */
    public function optimize_cron() {
        $this->logger->debug( 'Running optimization cron job' );
        
        try {
            $start_time = microtime( true );
            
            $optimization_results = array();
            
            // Optimize database tables
            $optimized_tables = $this->plugin->database->optimize_tables();
            $optimization_results['optimized_tables'] = $optimized_tables;
            
            // Update performance metrics
            $this->plugin->batch_manager->update_performance_metrics();
            $optimization_results['metrics_updated'] = true;
            
            // Clear expired caches
            $cleared_caches = $this->clear_expired_caches();
            $optimization_results['cleared_caches'] = $cleared_caches;
            
            // Analyze and optimize batch sizes
            $this->plugin->batch_manager->analyze_and_optimize_batch_size();
            $optimization_results['batch_size_optimized'] = true;
            
            $execution_time = microtime( true ) - $start_time;
            
            $this->logger->info( 'Optimization cron completed', array_merge( $optimization_results, array(
                'execution_time' => round( $execution_time, 3 ),
            ) ) );
            
        } catch ( Exception $e ) {
            $this->logger->error( 'Optimization cron failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Backup settings cron job
     *
     * @since 1.0.0
     */
    public function backup_settings_cron() {
        $this->logger->debug( 'Running settings backup cron job' );
        
        try {
            $start_time = microtime( true );
            
            $settings = get_option( 'auto_featured_image_settings', array() );
            $backup_data = array(
                'timestamp' => current_time( 'mysql' ),
                'version' => AUTO_FEATURED_IMAGE_VERSION,
                'settings' => $settings,
            );
            
            // Store backup in database
            $backup_id = $this->plugin->database->store_settings_backup( $backup_data );
            
            // Clean up old backups (keep last 30)
            $this->plugin->database->cleanup_old_settings_backups( 30 );
            
            $execution_time = microtime( true ) - $start_time;
            
            $this->logger->info( 'Settings backup cron completed', array(
                'backup_id' => $backup_id,
                'execution_time' => round( $execution_time, 3 ),
            ) );
            
        } catch ( Exception $e ) {
            $this->logger->error( 'Settings backup cron failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Get cron status
     *
     * @return array Cron status information
     * @since 1.0.0
     */
    public function get_cron_status() {
        $status = array();
        
        foreach ( $this->scheduled_events as $event_name => $event_config ) {
            $next_run = wp_next_scheduled( $event_name );
            
            $status[ $event_name ] = array(
                'enabled' => $event_config['enabled'],
                'scheduled' => $next_run !== false,
                'next_run' => $next_run ? date( 'Y-m-d H:i:s', $next_run ) : null,
                'interval' => $event_config['interval'],
                'description' => $event_config['description'],
            );
        }
        
        return $status;
    }

    /**
     * Manually trigger a cron event
     *
     * @param string $event_name Event name
     * @return bool Success status
     * @since 1.0.0
     */
    public function trigger_event( $event_name ) {
        if ( ! isset( $this->scheduled_events[ $event_name ] ) ) {
            return false;
        }

        $event_config = $this->scheduled_events[ $event_name ];

        if ( ! method_exists( $this, $event_config['callback'] ) ) {
            return false;
        }

        try {
            $this->logger->info( "Manually triggering cron event: {$event_name}" );
            call_user_func( array( $this, $event_config['callback'] ) );
            return true;
        } catch ( Exception $e ) {
            $this->logger->error( "Failed to trigger cron event: {$event_name}", array(
                'error' => $e->getMessage(),
            ) );
            return false;
        }
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Cleanup orphaned metadata
     *
     * @return int Number of deleted entries
     * @since 1.0.0
     */
    private function cleanup_orphaned_metadata() {
        global $wpdb;

        $deleted_count = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL
             AND pm.meta_key LIKE '_auto_featured_image_%'"
        );

        return intval( $deleted_count );
    }

    /**
     * Cleanup temporary files
     *
     * @return int Number of deleted files
     * @since 1.0.0
     */
    private function cleanup_temporary_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/auto-featured-image-temp/';

        if ( ! is_dir( $temp_dir ) ) {
            return 0;
        }

        $deleted_count = 0;
        $files = glob( $temp_dir . '*' );

        foreach ( $files as $file ) {
            if ( is_file( $file ) && filemtime( $file ) < ( time() - DAY_IN_SECONDS ) ) {
                if ( unlink( $file ) ) {
                    $deleted_count++;
                }
            }
        }

        return $deleted_count;
    }

    /**
     * Check database health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_database_health() {
        $health = array( 'score' => 100, 'issues' => array() );

        // Check if tables exist
        if ( ! $this->plugin->database->tables_exist() ) {
            $health['score'] -= 50;
            $health['issues'][] = 'Database tables missing';
        }

        // Check table integrity
        $integrity_issues = $this->plugin->database->check_table_integrity();
        if ( ! empty( $integrity_issues ) ) {
            $health['score'] -= 30;
            $health['issues'] = array_merge( $health['issues'], $integrity_issues );
        }

        return $health;
    }

    /**
     * Check queue health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_queue_health() {
        $health = array( 'score' => 100, 'issues' => array() );

        $queue_stats = $this->plugin->queue->get_queue_stats();

        // Check for stuck jobs
        if ( $queue_stats['stuck_jobs'] > 0 ) {
            $health['score'] -= 20;
            $health['issues'][] = "Stuck jobs: {$queue_stats['stuck_jobs']}";
        }

        // Check failure rate
        if ( $queue_stats['failure_rate'] > 20 ) {
            $health['score'] -= 30;
            $health['issues'][] = "High failure rate: {$queue_stats['failure_rate']}%";
        }

        return $health;
    }

    /**
     * Check performance health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_performance_health() {
        $health = array( 'score' => 100, 'issues' => array() );

        $metrics = $this->plugin->batch_manager->get_metrics();

        // Check average execution time
        if ( $metrics['avg_execution_time'] > 60 ) {
            $health['score'] -= 25;
            $health['issues'][] = "Slow execution: {$metrics['avg_execution_time']}s average";
        }

        return $health;
    }

    /**
     * Check disk space
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_disk_space() {
        $health = array( 'score' => 100, 'issues' => array() );

        $upload_dir = wp_upload_dir();
        $free_bytes = disk_free_space( $upload_dir['basedir'] );
        $total_bytes = disk_total_space( $upload_dir['basedir'] );

        if ( $free_bytes && $total_bytes ) {
            $free_percentage = ( $free_bytes / $total_bytes ) * 100;

            if ( $free_percentage < 10 ) {
                $health['score'] -= 40;
                $health['issues'][] = "Low disk space: {$free_percentage}% free";
            } elseif ( $free_percentage < 20 ) {
                $health['score'] -= 20;
                $health['issues'][] = "Disk space warning: {$free_percentage}% free";
            }
        }

        return $health;
    }

    /**
     * Check memory usage
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_memory_usage() {
        $health = array( 'score' => 100, 'issues' => array() );

        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $memory_usage = memory_get_usage( true );

        if ( $memory_limit > 0 ) {
            $usage_percentage = ( $memory_usage / $memory_limit ) * 100;

            if ( $usage_percentage > 80 ) {
                $health['score'] -= 30;
                $health['issues'][] = "High memory usage: {$usage_percentage}%";
            } elseif ( $usage_percentage > 60 ) {
                $health['score'] -= 15;
                $health['issues'][] = "Memory usage warning: {$usage_percentage}%";
            }
        }

        return $health;
    }

    /**
     * Calculate overall health score
     *
     * @param array $health_checks Health check results
     * @return int Overall health score
     * @since 1.0.0
     */
    private function calculate_overall_health( $health_checks ) {
        $total_score = 0;
        $count = 0;

        foreach ( $health_checks as $check ) {
            if ( isset( $check['score'] ) ) {
                $total_score += $check['score'];
                $count++;
            }
        }

        return $count > 0 ? round( $total_score / $count ) : 0;
    }

    /**
     * Handle poor health
     *
     * @param array $health_checks Health check results
     * @since 1.0.0
     */
    private function handle_poor_health( $health_checks ) {
        $this->logger->warning( 'System health is poor', array(
            'health_checks' => $health_checks,
        ) );

        // Take corrective actions
        foreach ( $health_checks as $component => $status ) {
            if ( $status['score'] < 50 ) {
                switch ( $component ) {
                    case 'queue':
                        $this->plugin->queue->recover_stuck_jobs();
                        break;

                    case 'database':
                        $this->plugin->database->repair_tables();
                        break;

                    case 'performance':
                        $this->plugin->batch_manager->optimize_batch_size();
                        break;
                }
            }
        }

        // Send notification if configured
        $this->send_health_notification( $health_checks );
    }

    /**
     * Send health notification
     *
     * @param array $health_checks Health check results
     * @since 1.0.0
     */
    private function send_health_notification( $health_checks ) {
        $settings = get_option( 'auto_featured_image_settings', array() );

        if ( empty( $settings['health_notifications_enabled'] ) ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );

        $subject = sprintf( '[%s] Auto Featured Image Health Alert', $site_name );

        $message = "The Auto Featured Image plugin has detected health issues:\n\n";

        foreach ( $health_checks as $component => $status ) {
            $message .= ucfirst( $component ) . ": {$status['score']}%\n";
            if ( ! empty( $status['issues'] ) ) {
                foreach ( $status['issues'] as $issue ) {
                    $message .= "  - {$issue}\n";
                }
            }
            $message .= "\n";
        }

        $message .= "Please check your WordPress admin dashboard for more details.";

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Clear expired caches
     *
     * @return int Number of cleared cache entries
     * @since 1.0.0
     */
    private function clear_expired_caches() {
        $cleared_count = 0;

        // Clear WordPress object cache for our plugin
        $cache_keys = array(
            'auto_featured_image_algorithms',
            'auto_featured_image_settings',
            'auto_featured_image_queue_stats',
            'auto_featured_image_performance_metrics',
        );

        foreach ( $cache_keys as $cache_key ) {
            if ( wp_cache_delete( $cache_key, 'auto_featured_image' ) ) {
                $cleared_count++;
            }
        }

        return $cleared_count;
    }
}
