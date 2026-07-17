<?php
/**
 * Comprehensive logging system for Auto Featured Image plugin
 *
 * Provides configurable logging with multiple levels, detailed action logging,
 * error logging with stack traces, and log management functionality.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class for comprehensive logging functionality
 *
 * Handles all logging operations with configurable levels, context,
 * and automatic cleanup functionality.
 */
class AFI_Logger {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log level priorities for filtering
     *
     * @var array
     */
    private static $level_priorities = array(
        self::LEVEL_DEBUG => 1,
        self::LEVEL_INFO => 2,
        self::LEVEL_WARNING => 3,
        self::LEVEL_ERROR => 4,
        self::LEVEL_CRITICAL => 5
    );
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Logs table name
     *
     * @var string
     */
    private $logs_table;
    
    /**
     * Current log level threshold
     *
     * @var string
     */
    private $log_level;
    
    /**
     * Whether logging is enabled
     *
     * @var bool
     */
    private $logging_enabled;
    
    /**
     * Maximum log entries to keep
     *
     * @var int
     */
    private $max_log_entries;
    
    /**
     * Initialize the logger
     */
    public function __construct() {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->logs_table = $wpdb->prefix . 'afi_logs';
        
        // Load configuration
        $this->logging_enabled = get_option('afi_enable_logging', true);
        $this->log_level = get_option('afi_log_level', self::LEVEL_INFO);
        $this->max_log_entries = get_option('afi_max_log_entries', 10000);
        
        // Create logs table if it doesn't exist
        $this->maybe_create_logs_table();
    }
    
    /**
     * Create logs table if it doesn't exist
     */
    private function maybe_create_logs_table() {
        $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->logs_table}'") === $this->logs_table;
        
        if (!$table_exists) {
            $this->create_logs_table();
        }
    }
    
    /**
     * Create the logs table
     *
     * @return bool True on success, false on failure
     */
    public function create_logs_table() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            job_id bigint(20) unsigned DEFAULT NULL,
            post_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            stack_trace longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY job_id (job_id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY level_created (level, created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        try {
            dbDelta($sql);
            return true;
        } catch (Exception $e) {
            error_log('AFI Logger: Failed to create logs table: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Drop the logs table
     *
     * @return bool True on success, false on failure
     */
    public function drop_logs_table() {
        try {
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->logs_table}");
            return true;
        } catch (Exception $e) {
            error_log('AFI Logger: Failed to drop logs table: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a debug message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     */
    public function debug($message, $context = array()) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log an info message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log a critical message
     *
     * @param string $message Log message
     * @param array  $context Optional context data
     */
    public function critical($message, $context = array()) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Log an exception with full stack trace
     *
     * @param Exception $exception The exception to log
     * @param string    $level     Log level (default: error)
     * @param array     $context   Optional additional context
     */
    public function exception($exception, $level = self::LEVEL_ERROR, $context = array()) {
        $message = $exception->getMessage();
        
        $context['exception_class'] = get_class($exception);
        $context['exception_file'] = $exception->getFile();
        $context['exception_line'] = $exception->getLine();
        $context['exception_code'] = $exception->getCode();
        
        $stack_trace = $exception->getTraceAsString();
        
        $this->log($level, $message, $context, $stack_trace);
    }
    
    /**
     * Log a message with specified level
     *
     * @param string $level       Log level
     * @param string $message     Log message
     * @param array  $context     Optional context data
     * @param string $stack_trace Optional stack trace
     */
    public function log($level, $message, $context = array(), $stack_trace = null) {
        // Check if logging is enabled
        if (!$this->logging_enabled) {
            return;
        }
        
        // Check if level meets threshold
        if (!$this->should_log($level)) {
            return;
        }
        
        // Prepare log entry data
        $log_data = array(
            'level' => $level,
            'message' => $this->interpolate_message($message, $context),
            'context' => wp_json_encode($context),
            'job_id' => isset($context['job_id']) ? absint($context['job_id']) : null,
            'post_id' => isset($context['post_id']) ? absint($context['post_id']) : null,
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
            'stack_trace' => $stack_trace,
            'created_at' => current_time('mysql')
        );
        
        // Insert log entry
        $result = $this->wpdb->insert(
            $this->logs_table,
            $log_data,
            array('%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Handle insertion failure
        if ($result === false) {
            error_log('AFI Logger: Failed to insert log entry: ' . $this->wpdb->last_error);
        }
        
        // Trigger cleanup if needed
        $this->maybe_cleanup_logs();
    }
    
    /**
     * Check if a log level should be logged based on current threshold
     *
     * @param string $level Log level to check
     * @return bool True if should log, false otherwise
     */
    private function should_log($level) {
        if (!isset(self::$level_priorities[$level]) || !isset(self::$level_priorities[$this->log_level])) {
            return false;
        }
        
        return self::$level_priorities[$level] >= self::$level_priorities[$this->log_level];
    }
    
    /**
     * Interpolate context variables into message
     *
     * @param string $message Message with placeholders
     * @param array  $context Context data
     * @return string Interpolated message
     */
    private function interpolate_message($message, $context) {
        if (empty($context)) {
            return $message;
        }
        
        $replace = array();
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }
    
    /**
     * Get client IP address
     *
     * @return string|null Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = sanitize_text_field($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null;
    }
    
    /**
     * Get logs with filtering and pagination
     *
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public function get_logs($args = array()) {
        $defaults = array(
            'level' => null,
            'job_id' => null,
            'post_id' => null,
            'user_id' => null,
            'search' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 50,
            'offset' => 0,
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['level'])) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $args['level'];
        }
        
        if (!empty($args['job_id'])) {
            $where_conditions[] = 'job_id = %d';
            $where_values[] = absint($args['job_id']);
        }
        
        if (!empty($args['post_id'])) {
            $where_conditions[] = 'post_id = %d';
            $where_values[] = absint($args['post_id']);
        }
        
        if (!empty($args['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = absint($args['user_id']);
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = 'message LIKE %s';
            $where_values[] = '%' . $this->wpdb->esc_like($args['search']) . '%';
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        // Build ORDER BY clause
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build final query
        $sql = "SELECT * FROM {$this->logs_table} 
                {$where_clause} 
                ORDER BY created_at {$order} 
                LIMIT %d OFFSET %d";
        
        $where_values[] = absint($args['limit']);
        $where_values[] = absint($args['offset']);
        
        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, $where_values);
        }
        
        $results = $this->wpdb->get_results($sql);
        
        // Decode context for each log entry
        foreach ($results as $log) {
            $log->context = json_decode($log->context, true);
        }
        
        return $results;
    }
    
    /**
     * Count logs with filtering
     *
     * @param array $args Query arguments
     * @return int Number of matching logs
     */
    public function count_logs($args = array()) {
        $defaults = array(
            'level' => null,
            'job_id' => null,
            'post_id' => null,
            'user_id' => null,
            'search' => null,
            'date_from' => null,
            'date_to' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause (same logic as get_logs)
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['level'])) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $args['level'];
        }
        
        if (!empty($args['job_id'])) {
            $where_conditions[] = 'job_id = %d';
            $where_values[] = absint($args['job_id']);
        }
        
        if (!empty($args['post_id'])) {
            $where_conditions[] = 'post_id = %d';
            $where_values[] = absint($args['post_id']);
        }
        
        if (!empty($args['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = absint($args['user_id']);
        }
        
        if (!empty($args['search'])) {
            $where_conditions[] = 'message LIKE %s';
            $where_values[] = '%' . $this->wpdb->esc_like($args['search']) . '%';
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->logs_table} {$where_clause}";
        
        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, $where_values);
        }
        
        return (int) $this->wpdb->get_var($sql);
    }
    
    /**
     * Get log statistics
     *
     * @return array Statistics array
     */
    public function get_log_stats() {
        $sql = "SELECT 
                    level,
                    COUNT(*) as count
                FROM {$this->logs_table} 
                GROUP BY level";
        
        $results = $this->wpdb->get_results($sql);
        
        $stats = array(
            'debug' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'critical' => 0,
            'total' => 0
        );
        
        foreach ($results as $result) {
            $stats[$result->level] = (int) $result->count;
            $stats['total'] += (int) $result->count;
        }
        
        return $stats;
    }
    
    /**
     * Clean up old logs based on retention settings
     *
     * @param int $days Number of days to keep (optional)
     * @return int Number of logs deleted
     */
    public function cleanup_old_logs($days = null) {
        if ($days === null) {
            $days = get_option('afi_log_retention_days', 30);
        }
        
        $days = absint($days);
        
        if ($days === 0) {
            return 0;
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->logs_table} WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        return $deleted !== false ? $deleted : 0;
    }
    
    /**
     * Clean up logs by count limit
     *
     * @param int $max_entries Maximum entries to keep (optional)
     * @return int Number of logs deleted
     */
    public function cleanup_logs_by_count($max_entries = null) {
        if ($max_entries === null) {
            $max_entries = $this->max_log_entries;
        }
        
        $max_entries = absint($max_entries);
        
        if ($max_entries === 0) {
            return 0;
        }
        
        // Get current count
        $current_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->logs_table}");
        
        if ($current_count <= $max_entries) {
            return 0;
        }
        
        $to_delete = $current_count - $max_entries;
        
        // Delete oldest entries
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->logs_table} 
                 ORDER BY created_at ASC 
                 LIMIT %d",
                $to_delete
            )
        );
        
        return $deleted !== false ? $deleted : 0;
    }
    
    /**
     * Maybe trigger automatic log cleanup
     */
    private function maybe_cleanup_logs() {
        // Only run cleanup occasionally to avoid performance impact
        if (rand(1, 100) > 5) { // 5% chance
            return;
        }
        
        // Clean up by date
        $this->cleanup_old_logs();
        
        // Clean up by count
        $this->cleanup_logs_by_count();
    }
    
    /**
     * Clear all logs
     *
     * @return bool True on success, false on failure
     */
    public function clear_all_logs() {
        $result = $this->wpdb->query("TRUNCATE TABLE {$this->logs_table}");
        return $result !== false;
    }
    
    /**
     * Get available log levels
     *
     * @return array Array of log levels
     */
    public static function get_log_levels() {
        return array_keys(self::$level_priorities);
    }
    
    /**
     * Get log level priority
     *
     * @param string $level Log level
     * @return int Priority number
     */
    public static function get_level_priority($level) {
        return isset(self::$level_priorities[$level]) ? self::$level_priorities[$level] : 0;
    }
    
    /**
     * Set logging configuration
     *
     * @param array $config Configuration array
     */
    public function set_config($config) {
        if (isset($config['enabled'])) {
            $this->logging_enabled = (bool) $config['enabled'];
            update_option('afi_enable_logging', $this->logging_enabled);
        }
        
        if (isset($config['level'])) {
            $this->log_level = $config['level'];
            update_option('afi_log_level', $this->log_level);
        }
        
        if (isset($config['max_entries'])) {
            $this->max_log_entries = absint($config['max_entries']);
            update_option('afi_max_log_entries', $this->max_log_entries);
        }
    }
    
    /**
     * Get current logging configuration
     *
     * @return array Configuration array
     */
    public function get_config() {
        return array(
            'enabled' => $this->logging_enabled,
            'level' => $this->log_level,
            'max_entries' => $this->max_log_entries,
            'retention_days' => get_option('afi_log_retention_days', 30)
        );
    }
    
    /**
     * Export logs to CSV format
     *
     * @param array $args Query arguments
     * @return string CSV content
     */
    public function export_logs_csv($args = array()) {
        $logs = $this->get_logs(array_merge($args, array('limit' => 0, 'offset' => 0)));
        
        $csv_content = "ID,Level,Message,Job ID,Post ID,User ID,IP Address,Created At\n";
        
        foreach ($logs as $log) {
            $csv_content .= sprintf(
                "%d,%s,\"%s\",%s,%s,%s,%s,%s\n",
                $log->id,
                $log->level,
                str_replace('"', '""', $log->message),
                $log->job_id ?: '',
                $log->post_id ?: '',
                $log->user_id ?: '',
                $log->ip_address ?: '',
                $log->created_at
            );
        }
        
        return $csv_content;
    }
}