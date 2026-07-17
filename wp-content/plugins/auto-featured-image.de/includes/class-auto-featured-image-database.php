<?php
/**
 * Database Handler Class
 *
 * Manages all database operations for the Auto Featured Image plugin
 * including table creation, data manipulation, and queries.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Database Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Database {

    /**
     * WordPress database object
     *
     * @var wpdb
     * @since 1.0.0
     */
    private $wpdb;

    /**
     * Jobs table name
     *
     * @var string
     * @since 1.0.0
     */
    private $jobs_table;

    /**
     * Progress table name
     *
     * @var string
     * @since 1.0.0
     */
    private $progress_table;

    /**
     * Log table name
     *
     * @var string
     * @since 1.0.0
     */
    private $log_table;

    /**
     * Assignments table name
     *
     * @var string
     * @since 1.0.0
     */
    private $assignments_table;

    /**
     * Settings backup table name
     *
     * @var string
     * @since 1.0.0
     */
    private $settings_backup_table;

    /**
     * Database version
     *
     * @var string
     * @since 1.0.0
     */
    private $db_version = '1.0.0';

    /**
     * Migration history
     *
     * @var array
     * @since 1.0.0
     */
    private $migrations = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';
        $this->progress_table = $wpdb->prefix . 'auto_featured_image_progress';
        $this->log_table = $wpdb->prefix . 'auto_featured_image_log';
        $this->assignments_table = $wpdb->prefix . 'auto_featured_image_assignments';
        $this->settings_backup_table = $wpdb->prefix . 'auto_featured_image_settings_backup';

        $this->init_migrations();
        $this->maybe_run_migrations();
    }

    /**
     * Create database tables
     *
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Jobs table SQL
        $jobs_sql = "CREATE TABLE {$this->jobs_table} (
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

        // Progress table SQL
        $progress_sql = "CREATE TABLE {$this->progress_table} (
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

        // Log table SQL
        $log_sql = "CREATE TABLE {$this->log_table} (
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

        // Assignments table SQL
        $assignments_sql = "CREATE TABLE {$this->assignments_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            algorithm_used varchar(100) DEFAULT NULL,
            score decimal(5,2) DEFAULT NULL,
            method varchar(50) NOT NULL DEFAULT 'auto',
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY attachment_id (attachment_id),
            KEY algorithm_used (algorithm_used),
            KEY method (method),
            KEY assigned_at (assigned_at)
        ) $charset_collate;";

        // Settings backup table SQL
        $settings_backup_sql = "CREATE TABLE {$this->settings_backup_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            backup_data longtext NOT NULL,
            version varchar(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY version (version),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $results = array();
        $results['jobs'] = dbDelta( $jobs_sql );
        $results['progress'] = dbDelta( $progress_sql );
        $results['log'] = dbDelta( $log_sql );
        $results['assignments'] = dbDelta( $assignments_sql );
        $results['settings_backup'] = dbDelta( $settings_backup_sql );

        // Update database version
        update_option( 'auto_featured_image_db_version', $this->db_version );

        return $this->verify_tables_created( $results );
    }

    /**
     * Insert a new job
     *
     * @param int    $post_id Post ID to process
     * @param string $status  Job status (default: 'pending')
     * @param int    $priority Job priority (default: 10)
     * @return int|false Job ID on success, false on failure
     * @since 1.0.0
     */
    public function insert_job( $post_id, $status = 'pending', $priority = 10 ) {
        $result = $this->wpdb->insert(
            $this->jobs_table,
            array(
                'post_id' => $post_id,
                'status' => $status,
                'priority' => $priority,
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%d', '%s', '%s' )
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update job status
     *
     * @param int    $job_id Job ID
     * @param string $status New status
     * @param string $error_message Optional error message
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function update_job_status( $job_id, $status, $error_message = null ) {
        $data = array(
            'status' => $status,
            'updated_at' => current_time( 'mysql' ),
        );

        $format = array( '%s', '%s' );

        if ( $status === 'completed' ) {
            $data['completed_at'] = current_time( 'mysql' );
            $format[] = '%s';
        }

        if ( $error_message ) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }

        $result = $this->wpdb->update(
            $this->jobs_table,
            $data,
            array( 'id' => $job_id ),
            $format,
            array( '%d' )
        );

        return $result !== false;
    }

    /**
     * Get pending jobs
     *
     * @param int $limit Number of jobs to retrieve (default: 50)
     * @param int $offset Offset for pagination (default: 0)
     * @return array Array of job objects
     * @since 1.0.0
     */
    public function get_pending_jobs( $limit = 50, $offset = 0 ) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} 
             WHERE status = 'pending' 
             ORDER BY priority DESC, created_at ASC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        );

        return $this->wpdb->get_results( $sql );
    }

    /**
     * Get job by ID
     *
     * @param int $job_id Job ID
     * @return object|null Job object or null if not found
     * @since 1.0.0
     */
    public function get_job( $job_id ) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} WHERE id = %d",
            $job_id
        );

        return $this->wpdb->get_row( $sql );
    }

    /**
     * Get jobs by post ID
     *
     * @param int $post_id Post ID
     * @return array Array of job objects
     * @since 1.0.0
     */
    public function get_jobs_by_post( $post_id ) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} WHERE post_id = %d ORDER BY created_at DESC",
            $post_id
        );

        return $this->wpdb->get_results( $sql );
    }

    /**
     * Get job statistics
     *
     * @return array Statistics array
     * @since 1.0.0
     */
    public function get_job_stats() {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM {$this->jobs_table} 
                GROUP BY status";

        $results = $this->wpdb->get_results( $sql );
        
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'running' => 0,
            'completed' => 0,
            'failed' => 0,
            'cancelled' => 0,
        );

        foreach ( $results as $result ) {
            $stats[ $result->status ] = (int) $result->count;
            $stats['total'] += (int) $result->count;
        }

        return $stats;
    }

    /**
     * Create progress batch
     *
     * @param string $batch_id Unique batch identifier
     * @param int    $total_posts Total number of posts to process
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function create_progress_batch( $batch_id, $total_posts ) {
        $result = $this->wpdb->insert(
            $this->progress_table,
            array(
                'batch_id' => $batch_id,
                'total_posts' => $total_posts,
                'status' => 'pending',
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%s', '%s', '%s' )
        );

        return $result !== false;
    }

    /**
     * Update progress batch
     *
     * @param string $batch_id Batch ID
     * @param array  $data Data to update
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function update_progress_batch( $batch_id, $data ) {
        $data['updated_at'] = current_time( 'mysql' );

        $result = $this->wpdb->update(
            $this->progress_table,
            $data,
            array( 'batch_id' => $batch_id ),
            null,
            array( '%s' )
        );

        return $result !== false;
    }

    /**
     * Get progress batch
     *
     * @param string $batch_id Batch ID
     * @return object|null Progress object or null if not found
     * @since 1.0.0
     */
    public function get_progress_batch( $batch_id ) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->progress_table} WHERE batch_id = %s",
            $batch_id
        );

        return $this->wpdb->get_row( $sql );
    }

    /**
     * Insert log entry
     *
     * @param string $level Log level (info, warning, error, debug)
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param int    $post_id Optional post ID
     * @param string $batch_id Optional batch ID
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function insert_log( $level, $message, $context = null, $post_id = null, $batch_id = null ) {
        $data = array(
            'level' => $level,
            'message' => $message,
            'created_at' => current_time( 'mysql' ),
        );

        $format = array( '%s', '%s', '%s' );

        if ( $context ) {
            $data['context'] = wp_json_encode( $context );
            $format[] = '%s';
        }

        if ( $post_id ) {
            $data['post_id'] = $post_id;
            $format[] = '%d';
        }

        if ( $batch_id ) {
            $data['batch_id'] = $batch_id;
            $format[] = '%s';
        }

        $result = $this->wpdb->insert(
            $this->log_table,
            $data,
            $format
        );

        return $result !== false;
    }

    /**
     * Clean up old log entries
     *
     * @param int $days Number of days to keep (default: 30)
     * @return int Number of deleted entries
     * @since 1.0.0
     */
    public function cleanup_old_logs( $days = 30 ) {
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->log_table} WHERE created_at < %s",
                $cutoff_date
            )
        );

        return $result ? $result : 0;
    }

    /**
     * Get table names
     *
     * @return array Array of table names
     * @since 1.0.0
     */
    public function get_table_names() {
        return array(
            'jobs' => $this->jobs_table,
            'progress' => $this->progress_table,
            'log' => $this->log_table,
        );
    }

    /**
     * Check if tables exist
     *
     * @return bool True if all tables exist, false otherwise
     * @since 1.0.0
     */
    public function tables_exist() {
        $tables = $this->get_table_names();
        
        foreach ( $tables as $table ) {
            if ( $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
                return false;
            }
        }
        
        return true;
    }

    // ========================================================================
    // Migration System
    // ========================================================================

    /**
     * Initialize migration definitions
     *
     * @since 1.0.0
     */
    private function init_migrations() {
        $this->migrations = array(
            '1.0.0' => array(
                'description' => 'Initial database schema',
                'callback' => 'migration_1_0_0',
            ),
            '1.0.1' => array(
                'description' => 'Add assignments table',
                'callback' => 'migration_1_0_1',
            ),
            '1.0.2' => array(
                'description' => 'Add settings backup table',
                'callback' => 'migration_1_0_2',
            ),
            '1.0.3' => array(
                'description' => 'Add indexes for performance',
                'callback' => 'migration_1_0_3',
            ),
        );
    }

    /**
     * Check if migrations need to be run
     *
     * @since 1.0.0
     */
    private function maybe_run_migrations() {
        $current_version = get_option( 'auto_featured_image_db_version', '0.0.0' );

        if ( version_compare( $current_version, $this->db_version, '<' ) ) {
            $this->run_migrations( $current_version );
        }
    }

    /**
     * Run migrations from current version to target version
     *
     * @param string $from_version Current database version
     * @since 1.0.0
     */
    private function run_migrations( $from_version ) {
        $logger = new Auto_Featured_Image_Logger();

        $logger->info( "Running database migrations from {$from_version} to {$this->db_version}" );

        foreach ( $this->migrations as $version => $migration ) {
            if ( version_compare( $from_version, $version, '<' ) ) {
                $logger->info( "Running migration {$version}: {$migration['description']}" );

                try {
                    if ( method_exists( $this, $migration['callback'] ) ) {
                        call_user_func( array( $this, $migration['callback'] ) );
                        $logger->info( "Migration {$version} completed successfully" );
                    } else {
                        $logger->error( "Migration callback not found: {$migration['callback']}" );
                    }
                } catch ( Exception $e ) {
                    $logger->error( "Migration {$version} failed", array(
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ) );

                    // Stop migrations on error
                    return;
                }
            }
        }

        // Update database version
        update_option( 'auto_featured_image_db_version', $this->db_version );
        $logger->info( "Database migrations completed. Version updated to {$this->db_version}" );
    }

    /**
     * Migration 1.0.0 - Initial schema
     *
     * @since 1.0.0
     */
    private function migration_1_0_0() {
        // This is handled by create_tables() method
        $this->create_tables();
    }

    /**
     * Migration 1.0.1 - Add assignments table
     *
     * @since 1.0.0
     */
    private function migration_1_0_1() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->assignments_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            attachment_id bigint(20) unsigned NOT NULL,
            algorithm_used varchar(100) DEFAULT NULL,
            score decimal(5,2) DEFAULT NULL,
            method varchar(50) NOT NULL DEFAULT 'auto',
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY attachment_id (attachment_id),
            KEY algorithm_used (algorithm_used),
            KEY method (method),
            KEY assigned_at (assigned_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Migration 1.0.2 - Add settings backup table
     *
     * @since 1.0.0
     */
    private function migration_1_0_2() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->settings_backup_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            backup_data longtext NOT NULL,
            version varchar(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY version (version),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Migration 1.0.3 - Add performance indexes
     *
     * @since 1.0.0
     */
    private function migration_1_0_3() {
        // Add composite indexes for better performance
        $indexes = array(
            "ALTER TABLE {$this->jobs_table} ADD INDEX idx_status_priority (status, priority)",
            "ALTER TABLE {$this->jobs_table} ADD INDEX idx_post_status (post_id, status)",
            "ALTER TABLE {$this->log_table} ADD INDEX idx_level_created (level, created_at)",
            "ALTER TABLE {$this->assignments_table} ADD INDEX idx_post_method (post_id, method)",
        );

        foreach ( $indexes as $sql ) {
            $this->wpdb->query( $sql );
        }
    }

    /**
     * Get table information
     *
     * @return array Table information
     * @since 1.0.0
     */
    public function get_table_info() {
        $info = array();

        $tables = array(
            'jobs' => $this->jobs_table,
            'progress' => $this->progress_table,
            'log' => $this->log_table,
            'assignments' => $this->assignments_table,
            'settings_backup' => $this->settings_backup_table,
        );

        foreach ( $tables as $key => $table ) {
            $count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
            $size_result = $this->wpdb->get_row( $this->wpdb->prepare(
                "SELECT
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ) );

            $info[ $key ] = array(
                'name' => $table,
                'rows' => intval( $count ),
                'size_mb' => $size_result ? $size_result->size_mb : 0,
                'exists' => $this->wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table,
            );
        }

        return $info;
    }

    // ========================================================================
    // Data Management Methods
    // ========================================================================

    /**
     * Record featured image assignment
     *
     * @param array $data Assignment data
     * @return int|false Assignment ID or false on failure
     * @since 1.0.0
     */
    public function record_assignment( $data ) {
        $defaults = array(
            'post_id' => 0,
            'attachment_id' => 0,
            'algorithm_used' => null,
            'score' => null,
            'method' => 'auto',
            'assigned_at' => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $this->wpdb->insert(
            $this->assignments_table,
            $data,
            array( '%d', '%d', '%s', '%f', '%s', '%s' )
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Store settings backup
     *
     * @param array $backup_data Backup data
     * @return int|false Backup ID or false on failure
     * @since 1.0.0
     */
    public function store_settings_backup( $backup_data ) {
        $data = array(
            'backup_data' => wp_json_encode( $backup_data ),
            'version' => $backup_data['version'] ?? AUTO_FEATURED_IMAGE_VERSION,
        );

        $result = $this->wpdb->insert(
            $this->settings_backup_table,
            $data,
            array( '%s', '%s' )
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Cleanup old settings backups
     *
     * @param int $keep_count Number of backups to keep
     * @return int Number of deleted backups
     * @since 1.0.0
     */
    public function cleanup_old_settings_backups( $keep_count = 30 ) {
        $result = $this->wpdb->query( $this->wpdb->prepare(
            "DELETE FROM {$this->settings_backup_table}
             WHERE id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM {$this->settings_backup_table}
                     ORDER BY created_at DESC
                     LIMIT %d
                 ) AS keep_backups
             )",
            $keep_count
        ) );

        return $result ? $result : 0;
    }

    /**
     * Cleanup completed jobs
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted jobs
     * @since 1.0.0
     */
    public function cleanup_completed_jobs( $days = 7 ) {
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $result = $this->wpdb->query( $this->wpdb->prepare(
            "DELETE FROM {$this->jobs_table}
             WHERE status IN ('completed', 'failed')
             AND updated_at < %s",
            $cutoff_date
        ) );

        return $result ? $result : 0;
    }

    /**
     * Get logs with pagination
     *
     * @param array $args Query arguments
     * @return array Paginated logs data
     * @since 1.0.0
     */
    public function get_logs_paginated( $args ) {
        $defaults = array(
            'level' => null,
            'date_from' => '',
            'date_to' => '',
            'search' => '',
            'page' => 1,
            'per_page' => 50,
            'order' => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $where_conditions = array( '1=1' );
        $where_values = array();

        if ( $args['level'] ) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $args['level'];
        }

        if ( $args['date_from'] ) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'] . ' 00:00:00';
        }

        if ( $args['date_to'] ) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }

        if ( $args['search'] ) {
            $where_conditions[] = 'message LIKE %s';
            $where_values[] = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
        }

        $where_clause = implode( ' AND ', $where_conditions );

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->log_table} WHERE {$where_clause}";
        $total = $this->wpdb->get_var(
            empty( $where_values ) ? $count_sql : $this->wpdb->prepare( $count_sql, $where_values )
        );

        // Get paginated results
        $offset = ( $args['page'] - 1 ) * $args['per_page'];
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->log_table}
                WHERE {$where_clause}
                ORDER BY created_at {$order}
                LIMIT %d OFFSET %d";

        $query_values = array_merge( $where_values, array( $args['per_page'], $offset ) );
        $logs = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $query_values ), ARRAY_A );

        return array(
            'logs' => $logs,
            'pagination' => array(
                'total' => intval( $total ),
                'per_page' => $args['per_page'],
                'current_page' => $args['page'],
                'total_pages' => ceil( $total / $args['per_page'] ),
            ),
        );
    }

    /**
     * Get log by ID
     *
     * @param int $log_id Log ID
     * @return array|null Log data or null if not found
     * @since 1.0.0
     */
    public function get_log_by_id( $log_id ) {
        $log = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM {$this->log_table} WHERE id = %d",
            $log_id
        ), ARRAY_A );

        if ( $log && $log['context'] ) {
            $log['context'] = json_decode( $log['context'], true );
        }

        return $log;
    }

    /**
     * Get error summary
     *
     * @return array Error summary data
     * @since 1.0.0
     */
    public function get_error_summary() {
        $cutoff_24h = date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

        // Total errors in last 24 hours
        $total_errors = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table}
             WHERE level = 'error' AND created_at >= %s",
            $cutoff_24h
        ) );

        // Error rate calculation
        $total_logs = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table} WHERE created_at >= %s",
            $cutoff_24h
        ) );

        $error_rate = $total_logs > 0 ? round( ( $total_errors / $total_logs ) * 100, 2 ) : 0;

        // Last error time
        $last_error = $this->wpdb->get_var(
            "SELECT created_at FROM {$this->log_table}
             WHERE level = 'error'
             ORDER BY created_at DESC
             LIMIT 1"
        );

        $last_error_time = $last_error ? human_time_diff( strtotime( $last_error ) ) . ' ago' : 'None';

        // Error types distribution
        $error_types = $this->wpdb->get_results(
            "SELECT
                SUBSTRING_INDEX(message, ':', 1) as error_type,
                COUNT(*) as count
             FROM {$this->log_table}
             WHERE level = 'error' AND created_at >= '{$cutoff_24h}'
             GROUP BY error_type
             ORDER BY count DESC
             LIMIT 5",
            ARRAY_A
        );

        $error_types_data = array(
            'labels' => array(),
            'values' => array(),
        );

        foreach ( $error_types as $type ) {
            $error_types_data['labels'][] = $type['error_type'];
            $error_types_data['values'][] = intval( $type['count'] );
        }

        return array(
            'total_errors' => intval( $total_errors ),
            'error_rate' => $error_rate,
            'last_error_time' => $last_error_time,
            'error_types' => $error_types_data,
        );
    }

    /**
     * Clear all plugin data
     *
     * @since 1.0.0
     */
    public function clear_all_data() {
        $tables = array(
            $this->jobs_table,
            $this->progress_table,
            $this->log_table,
            $this->assignments_table,
        );

        foreach ( $tables as $table ) {
            $this->wpdb->query( "TRUNCATE TABLE {$table}" );
        }
    }

    // ========================================================================
    // Getter Methods
    // ========================================================================

    /**
     * Get jobs table name
     *
     * @return string Jobs table name
     * @since 1.0.0
     */
    public function get_jobs_table() {
        return $this->jobs_table;
    }

    /**
     * Get progress table name
     *
     * @return string Progress table name
     * @since 1.0.0
     */
    public function get_progress_table() {
        return $this->progress_table;
    }

    /**
     * Get log table name
     *
     * @return string Log table name
     * @since 1.0.0
     */
    public function get_log_table() {
        return $this->log_table;
    }

    /**
     * Get assignments table name
     *
     * @return string Assignments table name
     * @since 1.0.0
     */
    public function get_assignments_table() {
        return $this->assignments_table;
    }

    /**
     * Get settings backup table name
     *
     * @return string Settings backup table name
     * @since 1.0.0
     */
    public function get_settings_backup_table() {
        return $this->settings_backup_table;
    }

    /**
     * Store performance metrics
     *
     * @param array $metrics_data Performance metrics data
     * @return int|false Metrics ID or false on failure
     * @since 1.0.0
     */
    public function store_performance_metrics( $metrics_data ) {
        // For now, just store in options table
        // In a full implementation, you might want a dedicated metrics table
        $existing_metrics = get_option( 'auto_featured_image_performance_metrics', array() );

        // Keep only last 100 entries
        if ( count( $existing_metrics ) >= 100 ) {
            $existing_metrics = array_slice( $existing_metrics, -99 );
        }

        $existing_metrics[] = $metrics_data;

        return update_option( 'auto_featured_image_performance_metrics', $existing_metrics );
    }

    /**
     * Get detailed statistics
     *
     * @return array Detailed statistics
     * @since 1.0.0
     */
    public function get_detailed_statistics() {
        global $wpdb;

        $jobs_table = $this->get_jobs_table();
        $logs_table = $this->get_logs_table();

        // Job statistics
        $job_stats = $wpdb->get_row( "
            SELECT
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_jobs
            FROM {$jobs_table}
        ", ARRAY_A );

        // Success rate
        $total_processed = $job_stats['completed_jobs'] + $job_stats['failed_jobs'];
        $success_rate = $total_processed > 0 ? round( ( $job_stats['completed_jobs'] / $total_processed ) * 100, 2 ) : 0;

        // Recent activity (last 24 hours)
        $recent_activity = $wpdb->get_row( "
            SELECT
                COUNT(*) as jobs_last_24h,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_last_24h
            FROM {$jobs_table}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", ARRAY_A );

        // Error statistics
        $error_stats = $wpdb->get_row( "
            SELECT
                COUNT(*) as total_errors,
                COUNT(DISTINCT DATE(created_at)) as error_days
            FROM {$logs_table}
            WHERE level = 'error'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", ARRAY_A );

        // Performance metrics
        $avg_processing_time = $wpdb->get_var( "
            SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))
            FROM {$jobs_table}
            WHERE status = 'completed'
            AND completed_at IS NOT NULL
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        " );

        return array(
            'jobs' => $job_stats,
            'success_rate' => $success_rate,
            'recent_activity' => $recent_activity,
            'errors' => $error_stats,
            'avg_processing_time' => round( $avg_processing_time ?: 0, 2 ),
            'generated_at' => current_time( 'mysql' ),
        );
    }

    /**
     * Get logs with optional filtering
     *
     * @param array $args Query arguments
     * @return array Logs
     * @since 1.0.0
     */
    public function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'level' => '',
            'limit' => 50,
            'offset' => 0,
            'date_from' => '',
            'date_to' => '',
            'search' => '',
        );

        $args = wp_parse_args( $args, $defaults );
        $logs_table = $this->get_logs_table();

        // Build WHERE clause
        $where_conditions = array();
        $where_values = array();

        if ( ! empty( $args['level'] ) && $args['level'] !== 'all' ) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $args['level'];
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $args['date_from'];
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }

        if ( ! empty( $args['search'] ) ) {
            $where_conditions[] = '(message LIKE %s OR context LIKE %s)';
            $search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = '';
        if ( ! empty( $where_conditions ) ) {
            $where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
        }

        // Build query
        $query = "SELECT * FROM {$logs_table} {$where_clause} ORDER BY created_at DESC";

        if ( $args['limit'] > 0 ) {
            $query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
        }

        // Prepare query if we have values
        if ( ! empty( $where_values ) ) {
            $query = $wpdb->prepare( $query, $where_values );
        }

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * Get table sizes
     *
     * @return array Table sizes information
     * @since 1.0.0
     */
    public function get_table_sizes() {
        global $wpdb;

        $tables = array(
            'jobs' => $this->jobs_table,
            'log' => $this->log_table,
            'progress' => $this->progress_table,
            'assignments' => $this->assignments_table,
            'settings_backup' => $this->settings_backup_table,
        );

        $sizes = array();
        foreach ( $tables as $name => $table ) {
            $result = $wpdb->get_row( $wpdb->prepare( "
                SELECT 
                    table_rows as row_count,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = %s 
                AND table_name = %s
            ", DB_NAME, $table ) );

            $sizes[ $name ] = array(
                'rows' => $result ? intval( $result->row_count ) : 0,
                'size_mb' => $result ? floatval( $result->size_mb ) : 0,
            );
        }

        return $sizes;
    }}
