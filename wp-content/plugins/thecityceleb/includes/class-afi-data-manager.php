<?php
/**
 * Data management and cleanup class for Auto Featured Image plugin
 *
 * Handles automatic data cleanup, manual cleanup tools, data export,
 * and database optimization for large datasets.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data management class
 *
 * Provides methods for data cleanup, export, and optimization
 * with configurable retention periods and manual cleanup tools.
 */
class AFI_Data_Manager {
    
    /**
     * Database manager instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Logger instance
     *
     * @var AFI_Logger
     */
    private $logger;
    
    /**
     * Default retention period in days
     *
     * @var int
     */
    private $default_retention_days = 90;
    
    /**
     * Initialize the data manager
     */
    public function __construct() {
        $this->database = new AFI_Database();
        
        if (class_exists('AFI_Logger')) {
            $this->logger = new AFI_Logger();
        }
    }
    
    /**
     * Perform automatic data cleanup based on retention settings
     *
     * Requirement 7.1: Automatic data cleanup with configurable retention periods
     *
     * @param int $retention_days Number of days to retain data (optional)
     * @return array Cleanup results
     */
    public function perform_automatic_cleanup($retention_days = null) {
        if ($retention_days === null) {
            $retention_days = get_option('afi_cleanup_days', $this->default_retention_days);
        }
        
        $retention_days = absint($retention_days);
        if ($retention_days === 0) {
            return array(
                'success' => false,
                'message' => __('Invalid retention period specified.', 'auto-featured-image')
            );
        }
        
        $results = array(
            'success' => true,
            'jobs_deleted' => 0,
            'job_items_deleted' => 0,
            'logs_deleted' => 0,
            'space_freed' => 0,
            'errors' => array()
        );
        
        try {
            // Clean up old completed and canceled jobs
            $jobs_deleted = $this->cleanup_old_jobs($retention_days);
            $results['jobs_deleted'] = $jobs_deleted;
            
            // Clean up orphaned job items
            $orphaned_items = $this->cleanup_orphaned_job_items();
            $results['job_items_deleted'] = $orphaned_items;
            
            // Clean up old logs if logging is enabled
            if (get_option('afi_enable_logging', true)) {
                $log_retention_days = get_option('afi_log_retention_days', 30);
                $logs_deleted = $this->cleanup_old_logs($log_retention_days);
                $results['logs_deleted'] = $logs_deleted;
            }
            
            // Calculate space freed (approximate)
            $results['space_freed'] = $this->estimate_space_freed($results);
            
            // Log cleanup activity
            if ($this->logger) {
                $this->logger->info('Automatic cleanup completed', array(
                    'jobs_deleted' => $results['jobs_deleted'],
                    'items_deleted' => $results['job_items_deleted'],
                    'logs_deleted' => $results['logs_deleted'],
                    'retention_days' => $retention_days
                ));
            }
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            
            if ($this->logger) {
                $this->logger->error('Automatic cleanup failed', array(
                    'error' => $e->getMessage(),
                    'retention_days' => $retention_days
                ));
            }
        }
        
        return $results;
    }
    
    /**
     * Clean up old jobs and their associated data
     *
     * @param int $retention_days Number of days to retain data
     * @return int Number of jobs deleted
     */
    private function cleanup_old_jobs($retention_days) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        
        // Get job IDs to delete (only completed, canceled, or failed jobs)
        $job_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$jobs_table} 
                 WHERE created_at < %s 
                 AND status IN ('complete', 'canceled', 'failed')",
                $cutoff_date
            )
        );
        
        if (empty($job_ids)) {
            return 0;
        }
        
        $job_ids_placeholder = implode(',', array_fill(0, count($job_ids), '%d'));
        
        // Delete job items first (foreign key constraint)
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$job_items_table} 
                 WHERE job_id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        // Delete jobs
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$jobs_table} 
                 WHERE id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        return $deleted;
    }
    
    /**
     * Clean up orphaned job items (items without parent jobs)
     *
     * @return int Number of orphaned items deleted
     */
    private function cleanup_orphaned_job_items() {
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        
        $deleted = $wpdb->query(
            "DELETE ji FROM {$job_items_table} ji
             LEFT JOIN {$jobs_table} j ON ji.job_id = j.id
             WHERE j.id IS NULL"
        );
        
        return $deleted;
    }
    
    /**
     * Clean up old log entries
     *
     * @param int $retention_days Number of days to retain logs
     * @return int Number of log entries deleted
     */
    private function cleanup_old_logs($retention_days) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'afi_logs';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$logs_table} WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        return $deleted;
    }
    
    /**
     * Perform manual cleanup with specific options
     *
     * Requirement 7.2: Manual cleanup tools for administrators
     *
     * @param array $options Cleanup options
     * @return array Cleanup results
     */
    public function perform_manual_cleanup($options = array()) {
        $defaults = array(
            'cleanup_jobs' => false,
            'cleanup_logs' => false,
            'cleanup_orphaned' => false,
            'optimize_tables' => false,
            'job_retention_days' => 90,
            'log_retention_days' => 30,
            'job_statuses' => array('complete', 'canceled', 'failed')
        );
        
        $options = wp_parse_args($options, $defaults);
        
        $results = array(
            'success' => true,
            'jobs_deleted' => 0,
            'job_items_deleted' => 0,
            'logs_deleted' => 0,
            'tables_optimized' => 0,
            'space_freed' => 0,
            'errors' => array()
        );
        
        try {
            // Clean up jobs if requested
            if ($options['cleanup_jobs']) {
                $jobs_deleted = $this->cleanup_jobs_by_criteria(
                    $options['job_retention_days'],
                    $options['job_statuses']
                );
                $results['jobs_deleted'] = $jobs_deleted;
            }
            
            // Clean up logs if requested
            if ($options['cleanup_logs']) {
                $logs_deleted = $this->cleanup_old_logs($options['log_retention_days']);
                $results['logs_deleted'] = $logs_deleted;
            }
            
            // Clean up orphaned items if requested
            if ($options['cleanup_orphaned']) {
                $orphaned_items = $this->cleanup_orphaned_job_items();
                $results['job_items_deleted'] = $orphaned_items;
            }
            
            // Optimize database tables if requested
            if ($options['optimize_tables']) {
                $optimized = $this->optimize_database_tables();
                $results['tables_optimized'] = $optimized;
            }
            
            // Calculate space freed
            $results['space_freed'] = $this->estimate_space_freed($results);
            
            // Log manual cleanup activity
            if ($this->logger) {
                $this->logger->info('Manual cleanup completed', array(
                    'options' => $options,
                    'results' => $results
                ));
            }
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            
            if ($this->logger) {
                $this->logger->error('Manual cleanup failed', array(
                    'error' => $e->getMessage(),
                    'options' => $options
                ));
            }
        }
        
        return $results;
    } 
   
    /**
     * Clean up jobs by specific criteria
     *
     * @param int   $retention_days Number of days to retain
     * @param array $statuses       Job statuses to clean up
     * @return int Number of jobs deleted
     */
    private function cleanup_jobs_by_criteria($retention_days, $statuses) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        
        // Sanitize statuses
        $statuses = array_map('sanitize_text_field', $statuses);
        $status_placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        
        // Get job IDs to delete
        $job_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$jobs_table} 
                 WHERE created_at < %s 
                 AND status IN ({$status_placeholders})",
                array_merge(array($cutoff_date), $statuses)
            )
        );
        
        if (empty($job_ids)) {
            return 0;
        }
        
        $job_ids_placeholder = implode(',', array_fill(0, count($job_ids), '%d'));
        
        // Delete job items first
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$job_items_table} 
                 WHERE job_id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        // Delete jobs
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$jobs_table} 
                 WHERE id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        return $deleted;
    }
    
    /**
     * Export job history data
     *
     * Requirement 7.4: Data export functionality for job history
     *
     * @param array $options Export options
     * @return array Export results with file path or data
     */
    public function export_job_history($options = array()) {
        $defaults = array(
            'format' => 'csv', // csv, json, xml
            'date_from' => null,
            'date_to' => null,
            'statuses' => array(),
            'include_items' => false,
            'include_logs' => false,
            'filename' => null
        );
        
        $options = wp_parse_args($options, $defaults);
        
        try {
            // Get jobs data
            $jobs_data = $this->get_jobs_for_export($options);
            
            if (empty($jobs_data)) {
                return array(
                    'success' => false,
                    'message' => __('No data found for export.', 'auto-featured-image')
                );
            }
            
            // Generate filename if not provided
            if (!$options['filename']) {
                $timestamp = date('Y-m-d_H-i-s');
                $options['filename'] = "afi_job_history_{$timestamp}.{$options['format']}";
            }
            
            // Export based on format
            switch ($options['format']) {
                case 'json':
                    $result = $this->export_to_json($jobs_data, $options);
                    break;
                case 'xml':
                    $result = $this->export_to_xml($jobs_data, $options);
                    break;
                case 'csv':
                default:
                    $result = $this->export_to_csv($jobs_data, $options);
                    break;
            }
            
            // Log export activity
            if ($this->logger) {
                $this->logger->info('Job history exported', array(
                    'format' => $options['format'],
                    'filename' => $options['filename'],
                    'records_count' => count($jobs_data)
                ));
            }
            
            return $result;
            
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Job history export failed', array(
                    'error' => $e->getMessage(),
                    'options' => $options
                ));
            }
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get jobs data for export
     *
     * @param array $options Export options
     * @return array Jobs data
     */
    private function get_jobs_for_export($options) {
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $where_conditions = array('1=1');
        $where_params = array();
        
        // Date range filter
        if ($options['date_from']) {
            $where_conditions[] = 'created_at >= %s';
            $where_params[] = $options['date_from'];
        }
        
        if ($options['date_to']) {
            $where_conditions[] = 'created_at <= %s';
            $where_params[] = $options['date_to'];
        }
        
        // Status filter
        if (!empty($options['statuses'])) {
            $statuses = array_map('sanitize_text_field', $options['statuses']);
            $status_placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $where_conditions[] = "status IN ({$status_placeholders})";
            $where_params = array_merge($where_params, $statuses);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM {$jobs_table} WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($where_params)) {
            $sql = $wpdb->prepare($sql, $where_params);
        }
        
        $jobs = $wpdb->get_results($sql, ARRAY_A);
        
        // Include job items if requested
        if ($options['include_items'] && !empty($jobs)) {
            foreach ($jobs as &$job) {
                $job['items'] = $this->get_job_items_for_export($job['id'], $options);
            }
        }
        
        return $jobs;
    }
    
    /**
     * Get job items for export
     *
     * @param int   $job_id Job ID
     * @param array $options Export options
     * @return array Job items data
     */
    private function get_job_items_for_export($job_id, $options) {
        global $wpdb;
        
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$job_items_table} WHERE job_id = %d ORDER BY id ASC",
            $job_id
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Export data to CSV format
     *
     * @param array $data    Jobs data
     * @param array $options Export options
     * @return array Export result
     */
    private function export_to_csv($data, $options) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $options['filename'];
        
        $file = fopen($file_path, 'w');
        if (!$file) {
            throw new Exception(__('Could not create export file.', 'auto-featured-image'));
        }
        
        // Write CSV headers
        $headers = array(
            'ID', 'Status', 'Post Types', 'Image Filters', 
            'Total Items', 'Processed Items', 'Created At', 'Finished At'
        );
        fputcsv($file, $headers);
        
        // Write data rows
        foreach ($data as $job) {
            $row = array(
                $job['id'],
                $job['status'],
                $job['post_types'],
                $job['image_filters'],
                $job['total_items'],
                $job['processed_items'],
                $job['created_at'],
                $job['finished_at']
            );
            fputcsv($file, $row);
        }
        
        fclose($file);
        
        return array(
            'success' => true,
            'file_path' => $file_path,
            'file_url' => $upload_dir['url'] . '/' . $options['filename'],
            'filename' => $options['filename'],
            'records_count' => count($data)
        );
    }
    
    /**
     * Export data to JSON format
     *
     * @param array $data    Jobs data
     * @param array $options Export options
     * @return array Export result
     */
    private function export_to_json($data, $options) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $options['filename'];
        
        $json_data = array(
            'export_info' => array(
                'plugin' => 'Auto Featured Image',
                'version' => AFI_VERSION,
                'export_date' => current_time('mysql'),
                'records_count' => count($data)
            ),
            'jobs' => $data
        );
        
        $json_content = wp_json_encode($json_data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($file_path, $json_content) === false) {
            throw new Exception(__('Could not create export file.', 'auto-featured-image'));
        }
        
        return array(
            'success' => true,
            'file_path' => $file_path,
            'file_url' => $upload_dir['url'] . '/' . $options['filename'],
            'filename' => $options['filename'],
            'records_count' => count($data)
        );
    }
    
    /**
     * Export data to XML format
     *
     * @param array $data    Jobs data
     * @param array $options Export options
     * @return array Export result
     */
    private function export_to_xml($data, $options) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $options['filename'];
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export></export>');
        
        // Add export info
        $info = $xml->addChild('export_info');
        $info->addChild('plugin', 'Auto Featured Image');
        $info->addChild('version', AFI_VERSION);
        $info->addChild('export_date', current_time('mysql'));
        $info->addChild('records_count', count($data));
        
        // Add jobs data
        $jobs_node = $xml->addChild('jobs');
        foreach ($data as $job) {
            $job_node = $jobs_node->addChild('job');
            foreach ($job as $key => $value) {
                if (is_array($value)) {
                    $value = wp_json_encode($value);
                }
                $job_node->addChild($key, htmlspecialchars($value));
            }
        }
        
        if ($xml->asXML($file_path) === false) {
            throw new Exception(__('Could not create export file.', 'auto-featured-image'));
        }
        
        return array(
            'success' => true,
            'file_path' => $file_path,
            'file_url' => $upload_dir['url'] . '/' . $options['filename'],
            'filename' => $options['filename'],
            'records_count' => count($data)
        );
    }
    
    /**
     * Optimize database tables
     *
     * Requirement 7.3: Database optimization tools
     *
     * @return int Number of tables optimized
     */
    public function optimize_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'afi_jobs',
            $wpdb->prefix . 'afi_job_items',
            $wpdb->prefix . 'afi_logs'
        );
        
        $optimized = 0;
        
        foreach ($tables as $table) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                $result = $wpdb->query("OPTIMIZE TABLE {$table}");
                if ($result !== false) {
                    $optimized++;
                }
            }
        }
        
        return $optimized;
    }
    
    /**
     * Get database statistics
     *
     * @return array Database statistics
     */
    public function get_database_statistics() {
        global $wpdb;
        
        $stats = array(
            'jobs_count' => 0,
            'job_items_count' => 0,
            'logs_count' => 0,
            'database_size' => 0,
            'table_sizes' => array()
        );
        
        // Get record counts
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        $logs_table = $wpdb->prefix . 'afi_logs';
        
        $stats['jobs_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$jobs_table}");
        $stats['job_items_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$job_items_table}");
        $stats['logs_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logs_table}");
        
        // Get table sizes
        $tables = array($jobs_table, $job_items_table, $logs_table);
        
        foreach ($tables as $table) {
            $size_query = $wpdb->prepare(
                "SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                 FROM information_schema.TABLES 
                 WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            );
            
            $size = $wpdb->get_var($size_query);
            $stats['table_sizes'][$table] = $size ? (float) $size : 0;
            $stats['database_size'] += $stats['table_sizes'][$table];
        }
        
        return $stats;
    }
    
    /**
     * Estimate space freed by cleanup operations
     *
     * @param array $results Cleanup results
     * @return float Estimated space freed in MB
     */
    private function estimate_space_freed($results) {
        // Rough estimates based on average record sizes
        $job_size_kb = 1; // ~1KB per job record
        $item_size_kb = 0.5; // ~0.5KB per job item record
        $log_size_kb = 2; // ~2KB per log record
        
        $space_freed = 0;
        $space_freed += ($results['jobs_deleted'] * $job_size_kb);
        $space_freed += ($results['job_items_deleted'] * $item_size_kb);
        $space_freed += ($results['logs_deleted'] * $log_size_kb);
        
        // Convert to MB
        return round($space_freed / 1024, 2);
    }
    
    /**
     * Schedule automatic cleanup
     *
     * @return bool True if scheduled successfully
     */
    public function schedule_automatic_cleanup() {
        if (!wp_next_scheduled('afi_automatic_cleanup')) {
            // Schedule daily cleanup at 2 AM
            $timestamp = strtotime('tomorrow 2:00 AM');
            return wp_schedule_event($timestamp, 'daily', 'afi_automatic_cleanup');
        }
        
        return true;
    }
    
    /**
     * Unschedule automatic cleanup
     *
     * @return bool True if unscheduled successfully
     */
    public function unschedule_automatic_cleanup() {
        $timestamp = wp_next_scheduled('afi_automatic_cleanup');
        if ($timestamp) {
            return wp_unschedule_event($timestamp, 'afi_automatic_cleanup');
        }
        
        return true;
    }
    
    /**
     * Get cleanup recommendations based on current data
     *
     * @return array Cleanup recommendations
     */
    public function get_cleanup_recommendations() {
        $stats = $this->get_database_statistics();
        $recommendations = array();
        
        // Check for old completed jobs
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $old_jobs_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$jobs_table} 
                 WHERE created_at < %s 
                 AND status IN ('complete', 'canceled', 'failed')",
                date('Y-m-d H:i:s', strtotime('-90 days'))
            )
        );
        
        if ($old_jobs_count > 0) {
            $recommendations[] = array(
                'type' => 'old_jobs',
                'priority' => 'medium',
                'message' => sprintf(
                    __('You have %d old completed jobs that can be cleaned up.', 'auto-featured-image'),
                    $old_jobs_count
                ),
                'action' => 'cleanup_old_jobs'
            );
        }
        
        // Check for large database size
        if ($stats['database_size'] > 100) { // > 100MB
            $recommendations[] = array(
                'type' => 'large_database',
                'priority' => 'high',
                'message' => sprintf(
                    __('Your database is using %.2f MB. Consider running cleanup and optimization.', 'auto-featured-image'),
                    $stats['database_size']
                ),
                'action' => 'optimize_database'
            );
        }
        
        // Check for orphaned items
        $job_items_table = $wpdb->prefix . 'afi_job_items';
        $orphaned_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$job_items_table} ji
             LEFT JOIN {$jobs_table} j ON ji.job_id = j.id
             WHERE j.id IS NULL"
        );
        
        if ($orphaned_count > 0) {
            $recommendations[] = array(
                'type' => 'orphaned_items',
                'priority' => 'low',
                'message' => sprintf(
                    __('Found %d orphaned job items that can be cleaned up.', 'auto-featured-image'),
                    $orphaned_count
                ),
                'action' => 'cleanup_orphaned'
            );
        }
        
        return $recommendations;
    }
}