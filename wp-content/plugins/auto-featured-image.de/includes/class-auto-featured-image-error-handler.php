<?php
/**
 * Error Handler and Recovery System
 *
 * Provides comprehensive error handling, recovery mechanisms, and graceful
 * degradation for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Error Handler Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Error_Handler {

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
     * Error types and their configurations
     *
     * @var array
     * @since 1.0.0
     */
    private $error_types = array();

    /**
     * Recovery strategies
     *
     * @var array
     * @since 1.0.0
     */
    private $recovery_strategies = array();

    /**
     * Error statistics
     *
     * @var array
     * @since 1.0.0
     */
    private $error_stats = array();

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->logger = new Auto_Featured_Image_Logger();
        $this->init_error_types();
        $this->init_recovery_strategies();
        $this->init_error_handling();
    }

    /**
     * Initialize error types configuration
     *
     * @since 1.0.0
     */
    private function init_error_types() {
        $this->error_types = array(
            'database_error' => array(
                'severity' => 'critical',
                'retry_count' => 3,
                'retry_delay' => 5,
                'recovery_strategy' => 'database_recovery',
                'notification' => true,
            ),
            'image_processing_error' => array(
                'severity' => 'high',
                'retry_count' => 2,
                'retry_delay' => 2,
                'recovery_strategy' => 'image_fallback',
                'notification' => false,
            ),
            'network_error' => array(
                'severity' => 'medium',
                'retry_count' => 3,
                'retry_delay' => 10,
                'recovery_strategy' => 'network_retry',
                'notification' => false,
            ),
            'memory_error' => array(
                'severity' => 'critical',
                'retry_count' => 1,
                'retry_delay' => 30,
                'recovery_strategy' => 'memory_optimization',
                'notification' => true,
            ),
            'timeout_error' => array(
                'severity' => 'medium',
                'retry_count' => 2,
                'retry_delay' => 5,
                'recovery_strategy' => 'timeout_recovery',
                'notification' => false,
            ),
            'permission_error' => array(
                'severity' => 'high',
                'retry_count' => 0,
                'retry_delay' => 0,
                'recovery_strategy' => 'permission_fallback',
                'notification' => true,
            ),
            'validation_error' => array(
                'severity' => 'low',
                'retry_count' => 0,
                'retry_delay' => 0,
                'recovery_strategy' => 'validation_skip',
                'notification' => false,
            ),
        );
    }

    /**
     * Initialize recovery strategies
     *
     * @since 1.0.0
     */
    private function init_recovery_strategies() {
        $this->recovery_strategies = array(
            'database_recovery' => array(
                'callback' => 'recover_database_error',
                'description' => 'Attempt database reconnection and repair',
            ),
            'image_fallback' => array(
                'callback' => 'recover_image_error',
                'description' => 'Use fallback image selection method',
            ),
            'network_retry' => array(
                'callback' => 'recover_network_error',
                'description' => 'Retry network operation with backoff',
            ),
            'memory_optimization' => array(
                'callback' => 'recover_memory_error',
                'description' => 'Optimize memory usage and retry',
            ),
            'timeout_recovery' => array(
                'callback' => 'recover_timeout_error',
                'description' => 'Adjust timeout settings and retry',
            ),
            'permission_fallback' => array(
                'callback' => 'recover_permission_error',
                'description' => 'Use alternative method with lower permissions',
            ),
            'validation_skip' => array(
                'callback' => 'recover_validation_error',
                'description' => 'Skip validation and continue processing',
            ),
        );
    }

    /**
     * Initialize error handling hooks
     *
     * @since 1.0.0
     */
    private function init_error_handling() {
        // Set custom error handler
        set_error_handler( array( $this, 'handle_php_error' ), E_ALL );
        
        // Set exception handler
        set_exception_handler( array( $this, 'handle_uncaught_exception' ) );
        
        // Register shutdown function for fatal errors
        register_shutdown_function( array( $this, 'handle_fatal_error' ) );
        
        // WordPress error hooks
        add_action( 'wp_die_handler', array( $this, 'handle_wp_die' ) );
        add_filter( 'wp_die_ajax_handler', array( $this, 'handle_ajax_die' ) );
        
        // Database error hooks
        add_action( 'wp_db_error', array( $this, 'handle_database_error' ) );
        
        // Memory limit hooks
        add_action( 'admin_init', array( $this, 'check_memory_usage' ) );
    }

    /**
     * Handle and process errors with recovery
     *
     * @param string $error_type Error type
     * @param string $message Error message
     * @param array  $context Error context
     * @param int    $post_id Related post ID
     * @return bool Whether error was handled successfully
     * @since 1.0.0
     */
    public function handle_error( $error_type, $message, $context = array(), $post_id = null ) {
        // Record error statistics
        $this->record_error_stats( $error_type );
        
        // Get error configuration
        $error_config = $this->error_types[ $error_type ] ?? $this->get_default_error_config();
        
        // Log the error
        $this->log_error( $error_type, $message, $context, $post_id, $error_config['severity'] );
        
        // Attempt recovery if strategy is defined
        $recovery_success = false;
        if ( isset( $error_config['recovery_strategy'] ) ) {
            $recovery_success = $this->attempt_recovery( $error_config['recovery_strategy'], $context );
        }
        
        // Send notification if required
        if ( $error_config['notification'] && ! $recovery_success ) {
            $this->send_error_notification( $error_type, $message, $context );
        }
        
        // Update error tracking
        $this->update_error_tracking( $error_type, $recovery_success );
        
        return $recovery_success;
    }

    /**
     * Handle PHP errors
     *
     * @param int    $errno Error number
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int    $errline Error line
     * @return bool Whether error was handled
     * @since 1.0.0
     */
    public function handle_php_error( $errno, $errstr, $errfile, $errline ) {
        // Skip if error reporting is disabled
        if ( ! ( error_reporting() & $errno ) ) {
            return false;
        }
        
        // Determine error type
        $error_type = $this->classify_php_error( $errno );
        
        $context = array(
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline,
            'error_type' => $error_type,
        );
        
        // Only handle plugin-related errors
        if ( strpos( $errfile, AUTO_FEATURED_IMAGE_PLUGIN_DIR ) !== false ) {
            $this->handle_error( $error_type, $errstr, $context );
        }
        
        // Don't prevent default error handling
        return false;
    }

    /**
     * Handle uncaught exceptions
     *
     * @param Throwable $exception Exception object
     * @since 1.0.0
     */
    public function handle_uncaught_exception( $exception ) {
        $error_type = $this->classify_exception( $exception );
        
        $context = array(
            'exception_class' => get_class( $exception ),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        );
        
        $this->handle_error( $error_type, $exception->getMessage(), $context );
    }

    /**
     * Handle fatal errors
     *
     * @since 1.0.0
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
            // Only handle plugin-related fatal errors
            if ( strpos( $error['file'], AUTO_FEATURED_IMAGE_PLUGIN_DIR ) !== false ) {
                $context = array(
                    'type' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                );
                
                $this->handle_error( 'fatal_error', $error['message'], $context );
            }
        }
    }

    /**
     * Handle WordPress die events
     *
     * @param string $message Die message
     * @since 1.0.0
     */
    public function handle_wp_die( $message ) {
        if ( is_string( $message ) && strpos( $message, 'auto-featured-image' ) !== false ) {
            $this->handle_error( 'wp_die', $message, array( 'source' => 'wp_die' ) );
        }
    }

    /**
     * Handle AJAX die events
     *
     * @param string $message Die message
     * @since 1.0.0
     */
    public function handle_ajax_die( $message ) {
        if ( is_string( $message ) && strpos( $message, 'auto-featured-image' ) !== false ) {
            $this->handle_error( 'ajax_error', $message, array( 'source' => 'ajax_die' ) );
        }
    }

    /**
     * Handle database errors
     *
     * @param string $error Database error
     * @since 1.0.0
     */
    public function handle_database_error( $error ) {
        global $wpdb;
        
        if ( $wpdb->last_error ) {
            $context = array(
                'last_query' => $wpdb->last_query,
                'last_error' => $wpdb->last_error,
            );
            
            $this->handle_error( 'database_error', $error, $context );
        }
    }

    /**
     * Check memory usage and handle memory issues
     *
     * @since 1.0.0
     */
    public function check_memory_usage() {
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $memory_usage = memory_get_usage( true );
        
        $usage_percentage = ( $memory_usage / $memory_limit ) * 100;
        
        if ( $usage_percentage > 90 ) {
            $context = array(
                'memory_usage' => $memory_usage,
                'memory_limit' => $memory_limit,
                'usage_percentage' => $usage_percentage,
            );
            
            $this->handle_error( 'memory_error', 'High memory usage detected', $context );
        }
    }

    /**
     * Attempt error recovery
     *
     * @param string $strategy Recovery strategy
     * @param array  $context Error context
     * @return bool Whether recovery was successful
     * @since 1.0.0
     */
    private function attempt_recovery( $strategy, $context ) {
        if ( ! isset( $this->recovery_strategies[ $strategy ] ) ) {
            return false;
        }
        
        $strategy_config = $this->recovery_strategies[ $strategy ];
        
        if ( ! method_exists( $this, $strategy_config['callback'] ) ) {
            return false;
        }
        
        try {
            $this->logger->info( "Attempting recovery strategy: {$strategy}" );
            
            $result = call_user_func( array( $this, $strategy_config['callback'] ), $context );
            
            if ( $result ) {
                $this->logger->info( "Recovery strategy successful: {$strategy}" );
            } else {
                $this->logger->warning( "Recovery strategy failed: {$strategy}" );
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            $this->logger->error( "Recovery strategy exception: {$strategy}", array(
                'error' => $e->getMessage(),
            ) );
            
            return false;
        }
    }

    // ========================================================================
    // Recovery Methods
    // ========================================================================

    /**
     * Recover from database errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_database_error( $context ) {
        global $wpdb;

        // Attempt to reconnect to database
        $wpdb->db_connect();

        // Check if connection is restored
        if ( $wpdb->last_error ) {
            return false;
        }

        // Verify tables exist and repair if necessary
        if ( ! $this->plugin->database->tables_exist() ) {
            $this->plugin->database->create_tables();
        }

        // Run table repair if needed
        $integrity_issues = $this->plugin->database->check_table_integrity();
        if ( ! empty( $integrity_issues ) ) {
            $this->plugin->database->repair_tables();
        }

        return true;
    }

    /**
     * Recover from image processing errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_image_error( $context ) {
        // Try alternative image processing method
        if ( extension_loaded( 'imagick' ) && ! isset( $context['tried_imagick'] ) ) {
            $context['tried_imagick'] = true;
            return true; // Signal to retry with ImageMagick
        }

        if ( extension_loaded( 'gd' ) && ! isset( $context['tried_gd'] ) ) {
            $context['tried_gd'] = true;
            return true; // Signal to retry with GD
        }

        // Use fallback algorithm
        if ( ! isset( $context['tried_fallback'] ) ) {
            $context['tried_fallback'] = true;
            return true; // Signal to retry with fallback algorithm
        }

        return false;
    }

    /**
     * Recover from network errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_network_error( $context ) {
        // Implement exponential backoff
        $retry_count = $context['retry_count'] ?? 0;
        $delay = min( 30, pow( 2, $retry_count ) );

        sleep( $delay );

        // Check network connectivity
        $response = wp_remote_get( 'https://www.google.com', array(
            'timeout' => 5,
            'sslverify' => false,
        ) );

        return ! is_wp_error( $response );
    }

    /**
     * Recover from memory errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_memory_error( $context ) {
        // Clear WordPress object cache
        wp_cache_flush();

        // Clear plugin-specific caches
        $this->clear_plugin_caches();

        // Reduce batch size if possible
        $current_batch_size = $this->plugin->batch_manager->get_current_batch_size();
        if ( $current_batch_size > 5 ) {
            $new_batch_size = max( 5, intval( $current_batch_size / 2 ) );
            $this->plugin->batch_manager->set_batch_size( $new_batch_size );
        }

        // Force garbage collection
        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }

        return true;
    }

    /**
     * Recover from timeout errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_timeout_error( $context ) {
        // Increase timeout limits if possible
        $current_timeout = ini_get( 'max_execution_time' );
        if ( $current_timeout > 0 && $current_timeout < 300 ) {
            set_time_limit( min( 300, $current_timeout + 30 ) );
        }

        // Reduce batch size to process fewer items
        $current_batch_size = $this->plugin->batch_manager->get_current_batch_size();
        if ( $current_batch_size > 1 ) {
            $new_batch_size = max( 1, intval( $current_batch_size / 2 ) );
            $this->plugin->batch_manager->set_batch_size( $new_batch_size );
        }

        return true;
    }

    /**
     * Recover from permission errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_permission_error( $context ) {
        // Try alternative methods that require fewer permissions

        // If file system access failed, try using WordPress functions
        if ( isset( $context['filesystem_error'] ) ) {
            return true; // Signal to retry with WP filesystem API
        }

        // If database access failed, try with lower privileges
        if ( isset( $context['database_error'] ) ) {
            return true; // Signal to retry with read-only operations
        }

        return false;
    }

    /**
     * Recover from validation errors
     *
     * @param array $context Error context
     * @return bool Recovery success
     * @since 1.0.0
     */
    private function recover_validation_error( $context ) {
        // Skip validation and continue processing
        // This is appropriate for non-critical validation errors
        return true;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Classify PHP error type
     *
     * @param int $errno Error number
     * @return string Error type
     * @since 1.0.0
     */
    private function classify_php_error( $errno ) {
        switch ( $errno ) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'fatal_error';

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'warning';

            case E_NOTICE:
            case E_USER_NOTICE:
                return 'notice';

            case E_STRICT:
                return 'strict';

            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'deprecated';

            default:
                return 'unknown_error';
        }
    }

    /**
     * Classify exception type
     *
     * @param Throwable $exception Exception object
     * @return string Error type
     * @since 1.0.0
     */
    private function classify_exception( $exception ) {
        $class_name = get_class( $exception );

        if ( strpos( $class_name, 'Database' ) !== false || strpos( $exception->getMessage(), 'database' ) !== false ) {
            return 'database_error';
        }

        if ( strpos( $class_name, 'Memory' ) !== false || strpos( $exception->getMessage(), 'memory' ) !== false ) {
            return 'memory_error';
        }

        if ( strpos( $class_name, 'Timeout' ) !== false || strpos( $exception->getMessage(), 'timeout' ) !== false ) {
            return 'timeout_error';
        }

        if ( strpos( $class_name, 'Permission' ) !== false || strpos( $exception->getMessage(), 'permission' ) !== false ) {
            return 'permission_error';
        }

        return 'general_exception';
    }

    /**
     * Get default error configuration
     *
     * @return array Default error config
     * @since 1.0.0
     */
    private function get_default_error_config() {
        return array(
            'severity' => 'medium',
            'retry_count' => 1,
            'retry_delay' => 5,
            'recovery_strategy' => null,
            'notification' => false,
        );
    }

    /**
     * Log error with appropriate level
     *
     * @param string $error_type Error type
     * @param string $message Error message
     * @param array  $context Error context
     * @param int    $post_id Related post ID
     * @param string $severity Error severity
     * @since 1.0.0
     */
    private function log_error( $error_type, $message, $context, $post_id, $severity ) {
        $log_context = array_merge( $context, array(
            'error_type' => $error_type,
            'severity' => $severity,
        ) );

        switch ( $severity ) {
            case 'critical':
                $this->logger->critical( $message, $log_context, $post_id );
                break;

            case 'high':
                $this->logger->error( $message, $log_context, $post_id );
                break;

            case 'medium':
                $this->logger->warning( $message, $log_context, $post_id );
                break;

            case 'low':
            default:
                $this->logger->info( $message, $log_context, $post_id );
                break;
        }
    }

    /**
     * Record error statistics
     *
     * @param string $error_type Error type
     * @since 1.0.0
     */
    private function record_error_stats( $error_type ) {
        if ( ! isset( $this->error_stats[ $error_type ] ) ) {
            $this->error_stats[ $error_type ] = array(
                'count' => 0,
                'first_occurrence' => time(),
                'last_occurrence' => time(),
            );
        }

        $this->error_stats[ $error_type ]['count']++;
        $this->error_stats[ $error_type ]['last_occurrence'] = time();

        // Store in database for persistence
        update_option( 'auto_featured_image_error_stats', $this->error_stats );
    }

    /**
     * Send error notification
     *
     * @param string $error_type Error type
     * @param string $message Error message
     * @param array  $context Error context
     * @since 1.0.0
     */
    private function send_error_notification( $error_type, $message, $context ) {
        $settings = get_option( 'auto_featured_image_settings', array() );

        if ( empty( $settings['error_notifications_enabled'] ) ) {
            return;
        }

        // Rate limit notifications (max 1 per hour per error type)
        $notification_key = "auto_featured_image_notification_{$error_type}";
        $last_notification = get_transient( $notification_key );

        if ( $last_notification ) {
            return;
        }

        set_transient( $notification_key, time(), HOUR_IN_SECONDS );

        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );

        $subject = sprintf( '[%s] Auto Featured Image Error: %s', $site_name, ucfirst( str_replace( '_', ' ', $error_type ) ) );

        $body = "An error has occurred in the Auto Featured Image plugin:\n\n";
        $body .= "Error Type: {$error_type}\n";
        $body .= "Message: {$message}\n";
        $body .= "Time: " . current_time( 'mysql' ) . "\n\n";

        if ( ! empty( $context ) ) {
            $body .= "Context:\n";
            foreach ( $context as $key => $value ) {
                if ( is_scalar( $value ) ) {
                    $body .= "  {$key}: {$value}\n";
                }
            }
        }

        $body .= "\nPlease check your WordPress admin dashboard for more details.";

        wp_mail( $admin_email, $subject, $body );
    }

    /**
     * Update error tracking
     *
     * @param string $error_type Error type
     * @param bool   $recovery_success Whether recovery was successful
     * @since 1.0.0
     */
    private function update_error_tracking( $error_type, $recovery_success ) {
        $tracking_key = "auto_featured_image_error_tracking_{$error_type}";
        $tracking_data = get_option( $tracking_key, array(
            'total_occurrences' => 0,
            'successful_recoveries' => 0,
            'recovery_rate' => 0,
        ) );

        $tracking_data['total_occurrences']++;

        if ( $recovery_success ) {
            $tracking_data['successful_recoveries']++;
        }

        $tracking_data['recovery_rate'] = round(
            ( $tracking_data['successful_recoveries'] / $tracking_data['total_occurrences'] ) * 100,
            2
        );

        update_option( $tracking_key, $tracking_data );
    }

    /**
     * Clear plugin-specific caches
     *
     * @since 1.0.0
     */
    private function clear_plugin_caches() {
        $cache_keys = array(
            'auto_featured_image_algorithms',
            'auto_featured_image_settings',
            'auto_featured_image_queue_stats',
            'auto_featured_image_performance_metrics',
        );

        foreach ( $cache_keys as $cache_key ) {
            wp_cache_delete( $cache_key, 'auto_featured_image' );
        }
    }

    /**
     * Get error statistics
     *
     * @return array Error statistics
     * @since 1.0.0
     */
    public function get_error_statistics() {
        return get_option( 'auto_featured_image_error_stats', array() );
    }

    /**
     * Get recovery statistics
     *
     * @return array Recovery statistics
     * @since 1.0.0
     */
    public function get_recovery_statistics() {
        $recovery_stats = array();

        foreach ( array_keys( $this->error_types ) as $error_type ) {
            $tracking_key = "auto_featured_image_error_tracking_{$error_type}";
            $recovery_stats[ $error_type ] = get_option( $tracking_key, array(
                'total_occurrences' => 0,
                'successful_recoveries' => 0,
                'recovery_rate' => 0,
            ) );
        }

        return $recovery_stats;
    }

    /**
     * Reset error statistics
     *
     * @since 1.0.0
     */
    public function reset_error_statistics() {
        delete_option( 'auto_featured_image_error_stats' );

        foreach ( array_keys( $this->error_types ) as $error_type ) {
            delete_option( "auto_featured_image_error_tracking_{$error_type}" );
        }

        $this->error_stats = array();
    }

    /**
     * Check system health and trigger recovery if needed
     *
     * @return array Health status
     * @since 1.0.0
     */
    public function check_system_health() {
        $health_issues = array();

        // Check error rates
        $error_stats = $this->get_error_statistics();
        foreach ( $error_stats as $error_type => $stats ) {
            $recent_errors = 0;
            if ( $stats['last_occurrence'] > ( time() - HOUR_IN_SECONDS ) ) {
                $recent_errors = $stats['count'];
            }

            if ( $recent_errors > 10 ) {
                $health_issues[] = "High error rate for {$error_type}: {$recent_errors} errors in last hour";
            }
        }

        // Check recovery rates
        $recovery_stats = $this->get_recovery_statistics();
        foreach ( $recovery_stats as $error_type => $stats ) {
            if ( $stats['total_occurrences'] > 5 && $stats['recovery_rate'] < 50 ) {
                $health_issues[] = "Low recovery rate for {$error_type}: {$stats['recovery_rate']}%";
            }
        }

        return array(
            'healthy' => empty( $health_issues ),
            'issues' => $health_issues,
        );
    }
}
