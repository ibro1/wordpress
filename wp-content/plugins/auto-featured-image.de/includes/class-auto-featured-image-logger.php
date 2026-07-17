<?php
/**
 * Logger Class
 *
 * Handles logging functionality for the Auto Featured Image plugin.
 * Provides structured logging with different levels and context data.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Logger Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Logger {

    /**
     * Database handler
     *
     * @var Auto_Featured_Image_Database
     * @since 1.0.0
     */
    private $database;

    /**
     * Log levels
     *
     * @var array
     * @since 1.0.0
     */
    private $log_levels = array(
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Initialize database handler only when needed to avoid circular dependency
        $this->init_database();
    }

    /**
     * Initialize database handler
     *
     * @since 1.0.0
     */
    private function init_database() {
        if ( ! $this->database && class_exists( 'Auto_Featured_Image_Database' ) ) {
            $this->database = new Auto_Featured_Image_Database();
        }
    }

    /**
     * Log a message
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function log( $level, $message, $context = null, $post_id = null, $batch_id = null ) {
        // Check if logging is enabled
        $settings = get_option( 'auto_featured_image_settings', array() );
        if ( ! isset( $settings['enable_logging'] ) || ! $settings['enable_logging'] ) {
            return false;
        }

        // Check log level
        $min_level = isset( $settings['log_level'] ) ? $settings['log_level'] : 'info';
        if ( ! $this->should_log( $level, $min_level ) ) {
            return false;
        }

        // Ensure database is initialized
        $this->init_database();

        // Log to database if available
        $db_logged = false;
        if ( $this->database ) {
            $db_logged = $this->database->insert_log( $level, $message, $context, $post_id, $batch_id );
        }

        // Also log to WordPress debug log if WP_DEBUG_LOG is enabled
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $this->log_to_debug_file( $level, $message, $context, $post_id, $batch_id );
        }

        return $db_logged;
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function debug( $message, $context = null, $post_id = null, $batch_id = null ) {
        return $this->log( 'debug', $message, $context, $post_id, $batch_id );
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function info( $message, $context = null, $post_id = null, $batch_id = null ) {
        return $this->log( 'info', $message, $context, $post_id, $batch_id );
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function warning( $message, $context = null, $post_id = null, $batch_id = null ) {
        return $this->log( 'warning', $message, $context, $post_id, $batch_id );
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function error( $message, $context = null, $post_id = null, $batch_id = null ) {
        return $this->log( 'error', $message, $context, $post_id, $batch_id );
    }

    /**
     * Check if message should be logged based on level
     *
     * @param string $level Message level
     * @param string $min_level Minimum level to log
     * @return bool True if should log, false otherwise
     * @since 1.0.0
     */
    private function should_log( $level, $min_level ) {
        if ( ! isset( $this->log_levels[ $level ] ) || ! isset( $this->log_levels[ $min_level ] ) ) {
            return false;
        }

        return $this->log_levels[ $level ] >= $this->log_levels[ $min_level ];
    }

    /**
     * Log to WordPress debug file
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @since 1.0.0
     */
    private function log_to_debug_file( $level, $message, $context = null, $post_id = null, $batch_id = null ) {
        $log_entry = sprintf(
            '[%s] Auto Featured Image [%s]: %s',
            current_time( 'Y-m-d H:i:s' ),
            strtoupper( $level ),
            $message
        );

        if ( $post_id ) {
            $log_entry .= " (Post ID: $post_id)";
        }

        if ( $batch_id ) {
            $log_entry .= " (Batch ID: $batch_id)";
        }

        if ( $context ) {
            $log_entry .= ' Context: ' . wp_json_encode( $context );
        }

        error_log( $log_entry );
    }

    /**
     * Get recent log entries
     *
     * @param int    $limit Number of entries to retrieve
     * @param string $level Optional level filter
     * @param string $batch_id Optional batch ID filter
     * @return array Array of log entries
     * @since 1.0.0
     */
    public function get_recent_logs( $limit = 100, $level = null, $batch_id = null ) {
        $this->init_database();

        if ( ! $this->database ) {
            return array();
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'auto_featured_image_log';

        $where_conditions = array();
        $where_values = array();

        if ( $level ) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }

        if ( $batch_id ) {
            $where_conditions[] = 'batch_id = %s';
            $where_values[] = $batch_id;
        }

        $where_clause = '';
        if ( ! empty( $where_conditions ) ) {
            $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
        }

        $where_values[] = $limit;

        $sql = "SELECT * FROM $log_table $where_clause ORDER BY created_at DESC LIMIT %d";

        if ( ! empty( $where_values ) ) {
            $sql = $wpdb->prepare( $sql, $where_values );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get log statistics
     *
     * @param string $batch_id Optional batch ID filter
     * @return array Statistics array
     * @since 1.0.0
     */
    public function get_log_stats( $batch_id = null ) {
        $this->init_database();

        if ( ! $this->database ) {
            return array();
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'auto_featured_image_log';

        $where_clause = '';
        $where_values = array();

        if ( $batch_id ) {
            $where_clause = 'WHERE batch_id = %s';
            $where_values[] = $batch_id;
        }

        $sql = "SELECT level, COUNT(*) as count FROM $log_table $where_clause GROUP BY level";

        if ( ! empty( $where_values ) ) {
            $sql = $wpdb->prepare( $sql, $where_values );
        }

        $results = $wpdb->get_results( $sql );

        $stats = array(
            'total' => 0,
            'debug' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
        );

        foreach ( $results as $result ) {
            $stats[ $result->level ] = (int) $result->count;
            $stats['total'] += (int) $result->count;
        }

        return $stats;
    }

    /**
     * Clear old log entries
     *
     * @param int $days Number of days to keep (default: 30)
     * @return int Number of deleted entries
     * @since 1.0.0
     */
    public function cleanup_old_logs( $days = null ) {
        $this->init_database();

        if ( ! $this->database ) {
            return 0;
        }

        // Get cleanup days from settings if not provided
        if ( $days === null ) {
            $settings = get_option( 'auto_featured_image_settings', array() );
            $days = isset( $settings['cleanup_logs_days'] ) ? $settings['cleanup_logs_days'] : 30;
        }

        $deleted = $this->database->cleanup_old_logs( $days );

        if ( $deleted > 0 ) {
            $this->info( "Cleaned up $deleted old log entries (older than $days days)" );
        }

        return $deleted;
    }

    /**
     * Clear all log entries
     *
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function clear_all_logs() {
        $this->init_database();

        if ( ! $this->database ) {
            return false;
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'auto_featured_image_log';

        $result = $wpdb->query( "TRUNCATE TABLE $log_table" );

        if ( $result !== false ) {
            $this->info( 'All log entries cleared by user' );
            return true;
        }

        return false;
    }

    /**
     * Export logs to CSV format
     *
     * @param array $filters Optional filters (level, batch_id, date_range)
     * @return string CSV content
     * @since 1.0.0
     */
    public function export_logs_csv( $filters = array() ) {
        $this->init_database();

        if ( ! $this->database ) {
            return '';
        }

        global $wpdb;
        $log_table = $wpdb->prefix . 'auto_featured_image_log';

        $where_conditions = array();
        $where_values = array();

        if ( isset( $filters['level'] ) && $filters['level'] ) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $filters['level'];
        }

        if ( isset( $filters['batch_id'] ) && $filters['batch_id'] ) {
            $where_conditions[] = 'batch_id = %s';
            $where_values[] = $filters['batch_id'];
        }

        if ( isset( $filters['date_range'] ) && is_array( $filters['date_range'] ) ) {
            if ( isset( $filters['date_range']['start'] ) ) {
                $where_conditions[] = 'created_at >= %s';
                $where_values[] = $filters['date_range']['start'];
            }
            if ( isset( $filters['date_range']['end'] ) ) {
                $where_conditions[] = 'created_at <= %s';
                $where_values[] = $filters['date_range']['end'];
            }
        }

        $where_clause = '';
        if ( ! empty( $where_conditions ) ) {
            $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
        }

        $sql = "SELECT * FROM $log_table $where_clause ORDER BY created_at DESC";

        if ( ! empty( $where_values ) ) {
            $sql = $wpdb->prepare( $sql, $where_values );
        }

        $logs = $wpdb->get_results( $sql, ARRAY_A );

        // Generate CSV
        $csv = "ID,Level,Message,Post ID,Batch ID,Created At\n";
        
        foreach ( $logs as $log ) {
            $csv .= sprintf(
                "%d,%s,\"%s\",%s,%s,%s\n",
                $log['id'],
                $log['level'],
                str_replace( '"', '""', $log['message'] ),
                $log['post_id'] ?: '',
                $log['batch_id'] ?: '',
                $log['created_at']
            );
        }

        return $csv;
    }


}