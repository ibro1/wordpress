<?php
/**
 * Database management class for Auto Featured Image plugin
 *
 * Handles database table creation, management, and data access operations
 * for the Auto Featured Image plugin with optimized schemas for large datasets.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database management class
 *
 * Provides methods for creating, managing, and accessing custom database tables
 * optimized for handling large datasets efficiently.
 */
class AFI_Database {
    
    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Jobs table name
     *
     * @var string
     */
    private $jobs_table;
    
    /**
     * Job items table name
     *
     * @var string
     */
    private $job_items_table;
    
    /**
     * Logs table name
     *
     * @var string
     */
    private $logs_table;
    
    /**
     * Database version for schema updates
     *
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Initialize the database manager
     */
    public function __construct() {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->jobs_table = $wpdb->prefix . 'afi_jobs';
        $this->job_items_table = $wpdb->prefix . 'afi_job_items';
        $this->logs_table = $wpdb->prefix . 'afi_logs';
    }
    
    /**
     * Create database tables with proper indexing
     *
     * Creates both wp_afi_jobs and wp_afi_job_items tables with optimized
     * schemas and indexes for handling large datasets efficiently.
     *
     * @return bool True on success, false on failure
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        try {
            // Create jobs table
            $jobs_sql = "CREATE TABLE {$this->jobs_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                status varchar(20) NOT NULL DEFAULT 'pending',
                post_types longtext NOT NULL,
                image_filters longtext,
                total_items bigint(20) unsigned DEFAULT 0,
                processed_items bigint(20) unsigned DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                finished_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY status (status),
                KEY created_at (created_at),
                KEY status_created (status, created_at)
            ) $charset_collate;";
            
            // Create job items table
            $job_items_sql = "CREATE TABLE {$this->job_items_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                job_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                assigned_image_id bigint(20) unsigned DEFAULT NULL,
                log_message text,
                processed_at datetime DEFAULT NULL,
                PRIMARY KEY (id),
                KEY job_id (job_id),
                KEY post_id (post_id),
                KEY status (status),
                KEY job_status (job_id, status),
                KEY post_status (post_id, status),
                UNIQUE KEY job_post (job_id, post_id)
            ) $charset_collate;";
            
            // Create logs table
            $logs_sql = "CREATE TABLE {$this->logs_table} (
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
            
            $jobs_result = dbDelta($jobs_sql);
            $items_result = dbDelta($job_items_sql);
            $logs_result = dbDelta($logs_sql);
            
            // Update database version
            update_option('afi_db_version', $this->db_version);
            
            // Log successful table creation
            if (function_exists('error_log')) {
                error_log('AFI: Database tables created successfully');
            }
            
            return true;
            
        } catch (Exception $e) {
            // Log error
            if (function_exists('error_log')) {
                error_log('AFI: Database table creation failed: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Drop database tables
     *
     * Removes both custom tables and cleans up any related data.
     * Used during plugin uninstall.
     *
     * @return bool True on success, false on failure
     */
    public function drop_tables() {
        try {
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->job_items_table}");
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->logs_table}");
            $this->wpdb->query("DROP TABLE IF EXISTS {$this->jobs_table}");
            
            // Remove database version option
            delete_option('afi_db_version');
            
            // Log successful table removal
            if (function_exists('error_log')) {
                error_log('AFI: Database tables dropped successfully');
            }
            
            return true;
            
        } catch (Exception $e) {
            // Log error
            if (function_exists('error_log')) {
                error_log('AFI: Database table removal failed: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Check if tables exist
     *
     * @return bool True if both tables exist, false otherwise
     */
    public function tables_exist() {
        $jobs_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->jobs_table}'") === $this->jobs_table;
        $items_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->job_items_table}'") === $this->job_items_table;
        
        return $jobs_exists && $items_exists;
    }
    
    /**
     * Get job by ID
     *
     * @param int $job_id Job ID
     * @return object|null Job object or null if not found
     */
    public function get_job($job_id) {
        $job_id = absint($job_id);
        
        if ($job_id === 0) {
            return null;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} WHERE id = %d",
            $job_id
        );
        
        $result = $this->wpdb->get_row($sql);
        
        if ($result) {
            // Decode JSON fields
            $result->post_types = json_decode($result->post_types, true);
            $result->image_filters = json_decode($result->image_filters, true);
        }
        
        return $result;
    }
    
    /**
     * Create a new job
     *
     * @param array $data Job data
     * @return int|false Job ID on success, false on failure
     */
    public function create_job($data) {
        $defaults = array(
            'status' => 'pending',
            'post_types' => array(),
            'image_filters' => array(),
            'total_items' => 0,
            'processed_items' => 0,
            'created_at' => current_time('mysql'),
            'finished_at' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Encode JSON fields
        $data['post_types'] = wp_json_encode($data['post_types']);
        $data['image_filters'] = wp_json_encode($data['image_filters']);
        
        $result = $this->wpdb->insert(
            $this->jobs_table,
            $data,
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update job data
     *
     * @param int   $job_id Job ID
     * @param array $data   Data to update
     * @return bool True on success, false on failure
     */
    public function update_job($job_id, $data) {
        $job_id = absint($job_id);
        
        if ($job_id === 0) {
            return false;
        }
        
        // Encode JSON fields if present
        if (isset($data['post_types'])) {
            $data['post_types'] = wp_json_encode($data['post_types']);
        }
        
        if (isset($data['image_filters'])) {
            $data['image_filters'] = wp_json_encode($data['image_filters']);
        }
        
        $result = $this->wpdb->update(
            $this->jobs_table,
            $data,
            array('id' => $job_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get a single job item by ID
     *
     * @param int $item_id Job item ID
     * @return object|null Job item object or null if not found
     */
    public function get_job_item($item_id) {
        $item_id = absint($item_id);
        
        if ($item_id === 0) {
            return null;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->job_items_table} WHERE id = %d",
            $item_id
        );
        
        return $this->wpdb->get_row($sql);
    }

    /**
     * Get job items with pagination, status filtering, and search
     *
     * @param int    $job_id Job ID
     * @param int    $limit  Number of items to retrieve
     * @param int    $offset Offset for pagination
     * @param string $status Optional status filter
     * @param string $search Optional search term for post titles
     * @return array Array of job items
     */
    public function get_job_items($job_id, $limit = 100, $offset = 0, $status = null, $search = '') {
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: get_job_items called with job_id: ' . $job_id . ', limit: ' . $limit . ', offset: ' . $offset . ', status: ' . ($status ?: 'null') . ', search: ' . $search);
        }
        
        $job_id = absint($job_id);
        $limit = absint($limit);
        $offset = absint($offset);
        
        if ($job_id === 0) {
            if (function_exists('error_log')) {
                error_log('AFI Debug: get_job_items returning empty array - job_id is 0');
            }
            return array();
        }
        
        $where_clause = "WHERE ji.job_id = %d";
        $params = array($job_id);
        
        if ($status !== null) {
            $where_clause .= " AND ji.status = %s";
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $search = '%' . $this->wpdb->esc_like($search) . '%';
            $where_clause .= " AND (p.post_title LIKE %s OR p.post_title IS NULL)";
            $params[] = $search;
        }
        
        if (!empty($search)) {
            // Join with posts table for search functionality
            $sql = $this->wpdb->prepare(
                "SELECT ji.* 
                 FROM {$this->job_items_table} ji
                 LEFT JOIN {$this->wpdb->posts} p ON ji.post_id = p.ID
                 {$where_clause} 
                 ORDER BY ji.id ASC 
                 LIMIT %d OFFSET %d",
                array_merge($params, array($limit, $offset))
            );
        } else {
            // Simple query without join when no search is needed
            $sql = $this->wpdb->prepare(
                "SELECT * FROM {$this->job_items_table} ji
                 {$where_clause} 
                 ORDER BY ji.id ASC 
                 LIMIT %d OFFSET %d",
                array_merge($params, array($limit, $offset))
            );
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: get_job_items SQL: ' . $sql);
        }
        
        $results = $this->wpdb->get_results($sql);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: get_job_items returned ' . count($results) . ' results');
            if (!empty($results)) {
                error_log('AFI Debug: First result: ' . json_encode($results[0]));
            }
        }
        
        return $results;
    }
    
    /**
     * Create a new job item
     *
     * @param array $data Job item data
     * @return int|false Job item ID on success, false on failure
     */
    public function create_job_item($data) {
        $defaults = array(
            'status' => 'pending',
            'assigned_image_id' => null,
            'log_message' => null,
            'processed_at' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['job_id']) || empty($data['post_id'])) {
            return false;
        }
        
        $result = $this->wpdb->insert(
            $this->job_items_table,
            $data,
            array('%d', '%d', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Update job item data
     *
     * @param int   $item_id Item ID
     * @param array $data    Data to update
     * @return bool True on success, false on failure
     */
    public function update_job_item($item_id, $data) {
        $item_id = absint($item_id);
        
        if ($item_id === 0) {
            return false;
        }
        
        $result = $this->wpdb->update(
            $this->job_items_table,
            $data,
            array('id' => $item_id),
            null,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get job statistics
     *
     * @param int $job_id Job ID
     * @return array Statistics array
     */
    public function get_job_stats($job_id) {
        $job_id = absint($job_id);
        
        if ($job_id === 0) {
            return array();
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as count
             FROM {$this->job_items_table} 
             WHERE job_id = %d 
             GROUP BY status",
            $job_id
        );
        
        $results = $this->wpdb->get_results($sql);
        
        $stats = array(
            'pending' => 0,
            'complete' => 0,
            'failed' => 0,
            'total' => 0
        );
        
        foreach ($results as $result) {
            $stats[$result->status] = (int) $result->count;
            $stats['total'] += (int) $result->count;
        }
        
        return $stats;
    }
    
    /**
     * Delete old jobs and their items
     *
     * @param int $days Number of days to keep
     * @return int Number of jobs deleted
     */
    public function cleanup_old_jobs($days = 90) {
        $days = absint($days);
        
        if ($days === 0) {
            return 0;
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get job IDs to delete
        $job_ids = $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->jobs_table} 
                 WHERE created_at < %s 
                 AND status IN ('complete', 'canceled')",
                $cutoff_date
            )
        );
        
        if (empty($job_ids)) {
            return 0;
        }
        
        $job_ids_placeholder = implode(',', array_fill(0, count($job_ids), '%d'));
        
        // Delete job items first (foreign key constraint)
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->job_items_table} 
                 WHERE job_id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        // Delete jobs
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->jobs_table} 
                 WHERE id IN ({$job_ids_placeholder})",
                $job_ids
            )
        );
        
        return $deleted;
    }
    
    /**
     * Get recent jobs for dashboard display
     *
     * @param int $limit Number of jobs to retrieve
     * @return array Array of recent jobs
     */
    public function get_recent_jobs($limit = 10) {
        $limit = absint($limit);
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        );
        
        $results = $this->wpdb->get_results($sql);
        
        // Decode JSON fields for each job
        foreach ($results as $job) {
            $job->post_types = json_decode($job->post_types, true);
            $job->image_filters = json_decode($job->image_filters, true);
        }
        
        return $results;
    }
    
    /**
     * Count active jobs (scanning, running, paused)
     *
     * @return int Number of active jobs
     */
    public function count_active_jobs() {
        $count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->jobs_table} 
             WHERE status IN ('scanning', 'running', 'paused')"
        );
        
        return (int) $count;
    }
    
    /**
     * Get database table names
     *
     * @return array Array of table names
     */
    public function get_table_names() {
        return array(
            'jobs' => $this->jobs_table,
            'job_items' => $this->job_items_table
        );
    }
    
    /**
     * Get database version
     *
     * @return string Database version
     */
    public function get_db_version() {
        return get_option('afi_db_version', '0.0.0');
    }
    
    /**
     * Check if database needs upgrade
     *
     * @return bool True if upgrade needed, false otherwise
     */
    public function needs_upgrade() {
        return version_compare($this->get_db_version(), $this->db_version, '<');
    }
    
    /**
     * Perform database upgrade if needed
     *
     * @return bool True on success, false on failure
     */
    public function maybe_upgrade() {
        if (!$this->needs_upgrade()) {
            return true;
        }
        
        // For now, just recreate tables
        // In future versions, this would handle incremental upgrades
        return $this->create_tables();
    }
    
    /**
     * Get job logs (recent job items with log messages)
     *
     * @param int $job_id Job ID
     * @param int $limit  Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of log entries
     */
    public function get_job_logs($job_id, $limit = 50, $offset = 0) {
        $job_id = absint($job_id);
        $limit = absint($limit);
        $offset = absint($offset);
        
        if ($job_id === 0) {
            return array();
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT id, post_id, status, assigned_image_id, log_message, processed_at
             FROM {$this->job_items_table} 
             WHERE job_id = %d 
             AND processed_at IS NOT NULL
             ORDER BY processed_at DESC 
             LIMIT %d OFFSET %d",
            $job_id, $limit, $offset
        );
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Count job items with optional search
     *
     * @param int    $job_id Job ID
     * @param string $search Optional search term for post titles
     * @return int Number of items
     */
    public function count_job_items($job_id, $search = '') {
        $job_id = absint($job_id);
        
        if ($job_id === 0) {
            return 0;
        }
        
        if (empty($search)) {
            $count = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->job_items_table} WHERE job_id = %d",
                    $job_id
                )
            );
        } else {
            // Join with posts table to search by title
            $search = '%' . $this->wpdb->esc_like($search) . '%';
            $count = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*) 
                     FROM {$this->job_items_table} ji
                     LEFT JOIN {$this->wpdb->posts} p ON ji.post_id = p.ID
                     WHERE ji.job_id = %d 
                     AND (p.post_title LIKE %s OR p.post_title IS NULL)",
                    $job_id, $search
                )
            );
        }
        
        return (int) $count;
    }
    

    
    /**
     * Get jobs with pagination
     *
     * @param int $limit  Number of jobs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of jobs
     */
    public function get_jobs($limit = 20, $offset = 0) {
        $limit = absint($limit);
        $offset = absint($offset);
        
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $limit, $offset
        );
        
        $results = $this->wpdb->get_results($sql);
        
        // Decode JSON fields for each job
        foreach ($results as $job) {
            $job->post_types = json_decode($job->post_types, true);
            $job->image_filters = json_decode($job->image_filters, true);
        }
        
        return $results;
    }
    
    /**
     * Delete a job and all its items
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function delete_job($job_id) {
        $job_id = absint($job_id);
        
        if ($job_id === 0) {
            return false;
        }
        
        // Delete job items first (foreign key constraint)
        $items_deleted = $this->wpdb->delete(
            $this->job_items_table,
            array('job_id' => $job_id),
            array('%d')
        );
        
        // Delete job
        $job_deleted = $this->wpdb->delete(
            $this->jobs_table,
            array('id' => $job_id),
            array('%d')
        );
        
        return $job_deleted !== false;
    }
}