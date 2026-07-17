<?php
/**
 * Performance Optimization System
 *
 * Handles database query optimization, caching strategies, and performance
 * monitoring for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Performance Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Performance {

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
     * Cache group
     *
     * @var string
     * @since 1.0.0
     */
    private $cache_group = 'auto_featured_image';

    /**
     * Performance metrics
     *
     * @var array
     * @since 1.0.0
     */
    private $metrics = array();

    /**
     * Query cache
     *
     * @var array
     * @since 1.0.0
     */
    private $query_cache = array();

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->logger = new Auto_Featured_Image_Logger();
        $this->init_performance_monitoring();
        $this->init_caching();
        $this->init_query_optimization();
    }

    /**
     * Initialize performance monitoring
     *
     * @since 1.0.0
     */
    private function init_performance_monitoring() {
        // Hook into WordPress query monitoring
        add_action( 'wp_loaded', array( $this, 'start_performance_monitoring' ) );
        add_action( 'shutdown', array( $this, 'end_performance_monitoring' ) );
        
        // Monitor database queries
        add_filter( 'query', array( $this, 'monitor_database_query' ) );
        
        // Monitor memory usage
        add_action( 'init', array( $this, 'monitor_memory_usage' ) );
        
        // Schedule performance analysis
        add_action( 'auto_featured_image_performance_analysis', array( $this, 'analyze_performance' ) );
        
        if ( ! wp_next_scheduled( 'auto_featured_image_performance_analysis' ) ) {
            wp_schedule_event( time(), 'hourly', 'auto_featured_image_performance_analysis' );
        }
    }

    /**
     * Initialize caching system
     *
     * @since 1.0.0
     */
    private function init_caching() {
        // Add cache group
        wp_cache_add_global_groups( array( $this->cache_group ) );
        
        // Hook into cache invalidation events
        add_action( 'save_post', array( $this, 'invalidate_post_cache' ) );
        add_action( 'delete_post', array( $this, 'invalidate_post_cache' ) );
        add_action( 'add_attachment', array( $this, 'invalidate_image_cache' ) );
        add_action( 'delete_attachment', array( $this, 'invalidate_image_cache' ) );
        
        // Settings cache invalidation
        add_action( 'update_option_auto_featured_image_settings', array( $this, 'invalidate_settings_cache' ) );
    }

    /**
     * Initialize query optimization
     *
     * @since 1.0.0
     */
    private function init_query_optimization() {
        // Optimize WordPress queries
        add_action( 'pre_get_posts', array( $this, 'optimize_post_queries' ) );
        
        // Add database indexes if needed
        add_action( 'admin_init', array( $this, 'ensure_database_indexes' ) );
        
        // Query result caching
        add_filter( 'posts_pre_query', array( $this, 'maybe_return_cached_query' ), 10, 2 );
        add_action( 'the_posts', array( $this, 'maybe_cache_query_results' ), 10, 2 );
    }

    /**
     * Start performance monitoring for current request
     *
     * @since 1.0.0
     */
    public function start_performance_monitoring() {
        $this->metrics['start_time'] = microtime( true );
        $this->metrics['start_memory'] = memory_get_usage( true );
        $this->metrics['start_peak_memory'] = memory_get_peak_usage( true );
        $this->metrics['queries_start'] = get_num_queries();
    }

    /**
     * End performance monitoring and record metrics
     *
     * @since 1.0.0
     */
    public function end_performance_monitoring() {
        if ( ! isset( $this->metrics['start_time'] ) ) {
            return;
        }

        $this->metrics['end_time'] = microtime( true );
        $this->metrics['end_memory'] = memory_get_usage( true );
        $this->metrics['end_peak_memory'] = memory_get_peak_usage( true );
        $this->metrics['queries_end'] = get_num_queries();

        $this->metrics['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
        $this->metrics['memory_used'] = $this->metrics['end_memory'] - $this->metrics['start_memory'];
        $this->metrics['peak_memory'] = $this->metrics['end_peak_memory'];
        $this->metrics['query_count'] = $this->metrics['queries_end'] - $this->metrics['queries_start'];

        // Store metrics if this is a plugin-related request
        if ( $this->is_plugin_request() ) {
            $this->store_performance_metrics();
        }
    }

    /**
     * Monitor database queries
     *
     * @param string $query SQL query
     * @return string Unmodified query
     * @since 1.0.0
     */
    public function monitor_database_query( $query ) {
        // Only monitor plugin-related queries
        if ( strpos( $query, 'auto_featured_image' ) !== false ) {
            $start_time = microtime( true );
            
            // Store query info for analysis
            $this->metrics['queries'][] = array(
                'query' => $query,
                'start_time' => $start_time,
                'backtrace' => wp_debug_backtrace_summary(),
            );
        }

        return $query;
    }

    /**
     * Monitor memory usage
     *
     * @since 1.0.0
     */
    public function monitor_memory_usage() {
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $memory_usage = memory_get_usage( true );
        $usage_percentage = ( $memory_usage / $memory_limit ) * 100;

        if ( $usage_percentage > 80 ) {
            $this->logger->warning( 'High memory usage detected', array(
                'memory_usage' => size_format( $memory_usage ),
                'memory_limit' => size_format( $memory_limit ),
                'usage_percentage' => round( $usage_percentage, 2 ),
            ) );

            // Trigger memory optimization
            $this->optimize_memory_usage();
        }
    }

    /**
     * Analyze performance data
     *
     * @since 1.0.0
     */
    public function analyze_performance() {
        $this->logger->debug( 'Running performance analysis' );

        try {
            // Analyze query performance
            $this->analyze_query_performance();
            
            // Analyze cache hit rates
            $this->analyze_cache_performance();
            
            // Analyze memory usage patterns
            $this->analyze_memory_patterns();
            
            // Generate optimization recommendations
            $recommendations = $this->generate_optimization_recommendations();
            
            if ( ! empty( $recommendations ) ) {
                $this->logger->info( 'Performance optimization recommendations', array(
                    'recommendations' => $recommendations,
                ) );
            }

        } catch ( Exception $e ) {
            $this->logger->error( 'Performance analysis failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    // ========================================================================
    // Caching Methods
    // ========================================================================

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param string $subgroup Cache subgroup
     * @return mixed Cached data or false
     * @since 1.0.0
     */
    public function get_cache( $key, $subgroup = 'default' ) {
        $cache_key = $this->build_cache_key( $key, $subgroup );
        return wp_cache_get( $cache_key, $this->cache_group );
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed  $data Data to cache
     * @param int    $expiration Expiration time in seconds
     * @param string $subgroup Cache subgroup
     * @return bool Success status
     * @since 1.0.0
     */
    public function set_cache( $key, $data, $expiration = 3600, $subgroup = 'default' ) {
        $cache_key = $this->build_cache_key( $key, $subgroup );
        return wp_cache_set( $cache_key, $data, $this->cache_group, $expiration );
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @param string $subgroup Cache subgroup
     * @return bool Success status
     * @since 1.0.0
     */
    public function delete_cache( $key, $subgroup = 'default' ) {
        $cache_key = $this->build_cache_key( $key, $subgroup );
        return wp_cache_delete( $cache_key, $this->cache_group );
    }

    /**
     * Build cache key
     *
     * @param string $key Base key
     * @param string $subgroup Subgroup
     * @return string Full cache key
     * @since 1.0.0
     */
    private function build_cache_key( $key, $subgroup ) {
        return $subgroup . '_' . $key;
    }

    /**
     * Invalidate post-related cache
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function invalidate_post_cache( $post_id ) {
        $cache_keys = array(
            "post_images_{$post_id}",
            "post_analysis_{$post_id}",
            "post_algorithms_{$post_id}",
        );

        foreach ( $cache_keys as $key ) {
            $this->delete_cache( $key, 'posts' );
        }

        // Clear related query caches
        $this->clear_query_cache( 'posts' );
    }

    /**
     * Invalidate image-related cache
     *
     * @param int $attachment_id Attachment ID
     * @since 1.0.0
     */
    public function invalidate_image_cache( $attachment_id ) {
        $cache_keys = array(
            "image_analysis_{$attachment_id}",
            "image_metadata_{$attachment_id}",
        );

        foreach ( $cache_keys as $key ) {
            $this->delete_cache( $key, 'images' );
        }

        // Clear related query caches
        $this->clear_query_cache( 'images' );
    }

    /**
     * Invalidate settings cache
     *
     * @since 1.0.0
     */
    public function invalidate_settings_cache() {
        $cache_keys = array(
            'plugin_settings',
            'algorithm_settings',
            'performance_settings',
        );

        foreach ( $cache_keys as $key ) {
            $this->delete_cache( $key, 'settings' );
        }
    }

    /**
     * Clear query cache
     *
     * @param string $type Cache type to clear
     * @since 1.0.0
     */
    private function clear_query_cache( $type = 'all' ) {
        if ( $type === 'all' ) {
            $this->query_cache = array();
        } else {
            unset( $this->query_cache[ $type ] );
        }
    }

    // ========================================================================
    // Query Optimization Methods
    // ========================================================================

    /**
     * Optimize post queries
     *
     * @param WP_Query $query Query object
     * @since 1.0.0
     */
    public function optimize_post_queries( $query ) {
        // Only optimize main queries
        if ( ! $query->is_main_query() ) {
            return;
        }

        // Optimize for plugin-related queries
        if ( isset( $query->query_vars['meta_key'] ) && 
             strpos( $query->query_vars['meta_key'], '_auto_featured_image' ) !== false ) {
            
            // Ensure we're using indexes
            $query->set( 'meta_query', array(
                'relation' => 'AND',
                $query->get( 'meta_query' ),
            ) );

            // Limit fields if we only need IDs
            if ( ! isset( $query->query_vars['fields'] ) ) {
                $query->set( 'fields', 'ids' );
            }
        }
    }

    /**
     * Ensure database indexes exist
     *
     * @since 1.0.0
     */
    public function ensure_database_indexes() {
        global $wpdb;

        $indexes_needed = array(
            $wpdb->postmeta => array(
                'post_id_meta_key' => array( 'post_id', 'meta_key' ),
            ),
            $this->plugin->database->get_jobs_table() => array(
                'status_priority' => array( 'status', 'priority' ),
                'post_id_status' => array( 'post_id', 'status' ),
                'created_at' => array( 'created_at' ),
            ),
        );

        foreach ( $indexes_needed as $table => $indexes ) {
            foreach ( $indexes as $index_name => $columns ) {
                $this->ensure_index_exists( $table, $index_name, $columns );
            }
        }
    }

    /**
     * Ensure specific index exists
     *
     * @param string $table Table name
     * @param string $index_name Index name
     * @param array  $columns Index columns
     * @since 1.0.0
     */
    private function ensure_index_exists( $table, $index_name, $columns ) {
        global $wpdb;

        // Skip if table doesn't exist
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return;
        }

        // Check if index exists
        $index_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW INDEX FROM {$table} WHERE Key_name = %s",
            $index_name
        ) );

        if ( ! $index_exists ) {
            $columns_sql = implode( ', ', array_map( function( $col ) {
                return "`{$col}`";
            }, $columns ) );

            $sql = "ALTER TABLE {$table} ADD INDEX {$index_name} ({$columns_sql})";

            // Suppress errors for index creation as they're not critical
            $wpdb->suppress_errors( true );
            $result = $wpdb->query( $sql );
            $wpdb->suppress_errors( false );

            if ( $result === false && $wpdb->last_error ) {
                $this->logger->debug( "Could not create index {$index_name} on table {$table}: " . $wpdb->last_error );
            } elseif ( $result !== false ) {
                $this->logger->debug( "Created index {$index_name} on table {$table}" );
            }
        }
    }

    /**
     * Maybe return cached query results
     *
     * @param array|null $posts Posts array or null
     * @param WP_Query   $query Query object
     * @return array|null Cached posts or null
     * @since 1.0.0
     */
    public function maybe_return_cached_query( $posts, $query ) {
        // Only cache specific plugin queries
        if ( ! $this->should_cache_query( $query ) ) {
            return $posts;
        }

        $cache_key = $this->generate_query_cache_key( $query );
        $cached_results = $this->get_cache( $cache_key, 'queries' );

        if ( $cached_results !== false ) {
            $this->logger->debug( 'Returning cached query results', array(
                'cache_key' => $cache_key,
                'post_count' => count( $cached_results ),
            ) );

            return $cached_results;
        }

        return $posts;
    }

    /**
     * Maybe cache query results
     *
     * @param array    $posts Posts array
     * @param WP_Query $query Query object
     * @return array Unmodified posts array
     * @since 1.0.0
     */
    public function maybe_cache_query_results( $posts, $query ) {
        // Only cache specific plugin queries
        if ( ! $this->should_cache_query( $query ) ) {
            return $posts;
        }

        $cache_key = $this->generate_query_cache_key( $query );
        $this->set_cache( $cache_key, $posts, 1800, 'queries' ); // 30 minutes

        $this->logger->debug( 'Cached query results', array(
            'cache_key' => $cache_key,
            'post_count' => count( $posts ),
        ) );

        return $posts;
    }

    /**
     * Check if query should be cached
     *
     * @param WP_Query $query Query object
     * @return bool Whether to cache
     * @since 1.0.0
     */
    private function should_cache_query( $query ) {
        // Cache queries with plugin-specific meta queries
        $meta_query = $query->get( 'meta_query' );
        if ( ! empty( $meta_query ) ) {
            foreach ( $meta_query as $meta_clause ) {
                if ( isset( $meta_clause['key'] ) && 
                     strpos( $meta_clause['key'], '_auto_featured_image' ) !== false ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate cache key for query
     *
     * @param WP_Query $query Query object
     * @return string Cache key
     * @since 1.0.0
     */
    private function generate_query_cache_key( $query ) {
        $key_parts = array(
            'post_type' => $query->get( 'post_type' ),
            'post_status' => $query->get( 'post_status' ),
            'meta_query' => $query->get( 'meta_query' ),
            'posts_per_page' => $query->get( 'posts_per_page' ),
            'orderby' => $query->get( 'orderby' ),
            'order' => $query->get( 'order' ),
        );

        return 'query_' . md5( serialize( $key_parts ) );
    }

    // ========================================================================
    // Performance Analysis Methods
    // ========================================================================

    /**
     * Analyze query performance
     *
     * @since 1.0.0
     */
    private function analyze_query_performance() {
        global $wpdb;

        // Get slow query log if available
        $slow_queries = $wpdb->get_results(
            "SELECT * FROM information_schema.PROCESSLIST
             WHERE COMMAND = 'Query' AND TIME > 1 AND INFO LIKE '%auto_featured_image%'",
            ARRAY_A
        );

        if ( ! empty( $slow_queries ) ) {
            $this->logger->warning( 'Slow queries detected', array(
                'slow_query_count' => count( $slow_queries ),
                'queries' => array_slice( $slow_queries, 0, 5 ), // Log first 5
            ) );
        }

        // Analyze query patterns
        $this->analyze_query_patterns();
    }

    /**
     * Analyze query patterns
     *
     * @since 1.0.0
     */
    private function analyze_query_patterns() {
        if ( empty( $this->metrics['queries'] ) ) {
            return;
        }

        $query_stats = array();
        $total_time = 0;

        foreach ( $this->metrics['queries'] as $query_info ) {
            $query_type = $this->classify_query( $query_info['query'] );

            if ( ! isset( $query_stats[ $query_type ] ) ) {
                $query_stats[ $query_type ] = array(
                    'count' => 0,
                    'total_time' => 0,
                );
            }

            $query_stats[ $query_type ]['count']++;

            if ( isset( $query_info['execution_time'] ) ) {
                $query_stats[ $query_type ]['total_time'] += $query_info['execution_time'];
                $total_time += $query_info['execution_time'];
            }
        }

        // Calculate averages and identify bottlenecks
        foreach ( $query_stats as $type => &$stats ) {
            $stats['avg_time'] = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
            $stats['percentage'] = $total_time > 0 ? ( $stats['total_time'] / $total_time ) * 100 : 0;
        }

        $this->logger->info( 'Query performance analysis', array(
            'total_queries' => count( $this->metrics['queries'] ),
            'total_time' => $total_time,
            'query_stats' => $query_stats,
        ) );
    }

    /**
     * Classify query type
     *
     * @param string $query SQL query
     * @return string Query type
     * @since 1.0.0
     */
    private function classify_query( $query ) {
        $query_lower = strtolower( $query );

        if ( strpos( $query_lower, 'select' ) === 0 ) {
            if ( strpos( $query_lower, 'auto_featured_image_jobs' ) !== false ) {
                return 'job_select';
            } elseif ( strpos( $query_lower, 'auto_featured_image_log' ) !== false ) {
                return 'log_select';
            } elseif ( strpos( $query_lower, 'postmeta' ) !== false ) {
                return 'meta_select';
            }
            return 'other_select';
        } elseif ( strpos( $query_lower, 'insert' ) === 0 ) {
            return 'insert';
        } elseif ( strpos( $query_lower, 'update' ) === 0 ) {
            return 'update';
        } elseif ( strpos( $query_lower, 'delete' ) === 0 ) {
            return 'delete';
        }

        return 'other';
    }

    /**
     * Analyze cache performance
     *
     * @since 1.0.0
     */
    private function analyze_cache_performance() {
        // Get cache statistics if available
        if ( function_exists( 'wp_cache_get_stats' ) ) {
            $cache_stats = wp_cache_get_stats();

            if ( isset( $cache_stats[ $this->cache_group ] ) ) {
                $group_stats = $cache_stats[ $this->cache_group ];

                $hit_rate = $group_stats['gets'] > 0 ?
                    ( $group_stats['hits'] / $group_stats['gets'] ) * 100 : 0;

                $this->logger->info( 'Cache performance analysis', array(
                    'cache_group' => $this->cache_group,
                    'hit_rate' => round( $hit_rate, 2 ),
                    'hits' => $group_stats['hits'],
                    'misses' => $group_stats['gets'] - $group_stats['hits'],
                    'sets' => $group_stats['sets'],
                ) );

                // Recommend cache optimizations if hit rate is low
                if ( $hit_rate < 70 ) {
                    $this->logger->warning( 'Low cache hit rate detected', array(
                        'hit_rate' => $hit_rate,
                        'recommendation' => 'Consider increasing cache expiration times or reviewing cache invalidation logic',
                    ) );
                }
            }
        }
    }

    /**
     * Analyze memory usage patterns
     *
     * @since 1.0.0
     */
    private function analyze_memory_patterns() {
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $current_usage = memory_get_usage( true );
        $peak_usage = memory_get_peak_usage( true );

        $usage_percentage = ( $current_usage / $memory_limit ) * 100;
        $peak_percentage = ( $peak_usage / $memory_limit ) * 100;

        $this->logger->info( 'Memory usage analysis', array(
            'memory_limit' => size_format( $memory_limit ),
            'current_usage' => size_format( $current_usage ),
            'peak_usage' => size_format( $peak_usage ),
            'usage_percentage' => round( $usage_percentage, 2 ),
            'peak_percentage' => round( $peak_percentage, 2 ),
        ) );

        // Check for memory leaks
        if ( isset( $this->metrics['start_memory'] ) ) {
            $memory_increase = $current_usage - $this->metrics['start_memory'];

            if ( $memory_increase > 10 * 1024 * 1024 ) { // 10MB increase
                $this->logger->warning( 'Significant memory increase detected', array(
                    'memory_increase' => size_format( $memory_increase ),
                    'recommendation' => 'Check for memory leaks in plugin code',
                ) );
            }
        }
    }

    /**
     * Generate optimization recommendations
     *
     * @return array Optimization recommendations
     * @since 1.0.0
     */
    private function generate_optimization_recommendations() {
        $recommendations = array();

        // Check execution time
        if ( isset( $this->metrics['execution_time'] ) && $this->metrics['execution_time'] > 5 ) {
            $recommendations[] = array(
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Long execution time detected. Consider optimizing algorithms or reducing batch sizes.',
                'metric' => 'execution_time',
                'value' => $this->metrics['execution_time'],
            );
        }

        // Check query count
        if ( isset( $this->metrics['query_count'] ) && $this->metrics['query_count'] > 50 ) {
            $recommendations[] = array(
                'type' => 'database',
                'priority' => 'medium',
                'message' => 'High number of database queries. Consider implementing query caching or optimization.',
                'metric' => 'query_count',
                'value' => $this->metrics['query_count'],
            );
        }

        // Check memory usage
        if ( isset( $this->metrics['peak_memory'] ) ) {
            $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
            $usage_percentage = ( $this->metrics['peak_memory'] / $memory_limit ) * 100;

            if ( $usage_percentage > 80 ) {
                $recommendations[] = array(
                    'type' => 'memory',
                    'priority' => 'high',
                    'message' => 'High memory usage detected. Consider reducing batch sizes or optimizing memory usage.',
                    'metric' => 'memory_usage_percentage',
                    'value' => $usage_percentage,
                );
            }
        }

        return $recommendations;
    }

    // ========================================================================
    // Optimization Methods
    // ========================================================================

    /**
     * Optimize memory usage
     *
     * @since 1.0.0
     */
    private function optimize_memory_usage() {
        // Clear WordPress object cache
        wp_cache_flush();

        // Clear plugin-specific caches
        $this->clear_query_cache();

        // Force garbage collection
        if ( function_exists( 'gc_collect_cycles' ) ) {
            gc_collect_cycles();
        }

        // Reduce batch size if possible
        if ( $this->plugin->batch_manager ) {
            $current_size = $this->plugin->batch_manager->get_current_batch_size();
            if ( $current_size > 5 ) {
                $new_size = max( 5, intval( $current_size / 2 ) );
                $this->plugin->batch_manager->set_batch_size( $new_size );

                $this->logger->info( 'Reduced batch size due to memory pressure', array(
                    'old_size' => $current_size,
                    'new_size' => $new_size,
                ) );
            }
        }
    }

    /**
     * Optimize database performance
     *
     * @since 1.0.0
     */
    public function optimize_database_performance() {
        global $wpdb;

        // Optimize plugin tables
        $tables = array(
            $this->plugin->database->get_jobs_table(),
            $this->plugin->database->get_progress_table(),
            $this->plugin->database->get_log_table(),
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE {$table}" );
        }

        // Update table statistics
        foreach ( $tables as $table ) {
            $wpdb->query( "ANALYZE TABLE {$table}" );
        }

        $this->logger->info( 'Database optimization completed', array(
            'optimized_tables' => count( $tables ),
        ) );
    }

    /**
     * Preload critical data
     *
     * @since 1.0.0
     */
    public function preload_critical_data() {
        // Preload plugin settings
        $settings = get_option( 'auto_featured_image_settings' );
        $this->set_cache( 'plugin_settings', $settings, 3600, 'settings' );

        // Preload algorithm configurations
        if ( $this->plugin->algorithms ) {
            $algorithms = $this->plugin->algorithms->get_available_algorithms();
            $this->set_cache( 'available_algorithms', $algorithms, 3600, 'algorithms' );
        }

        // Preload queue statistics
        if ( $this->plugin->queue ) {
            $queue_stats = $this->plugin->queue->get_queue_stats();
            $this->set_cache( 'queue_stats', $queue_stats, 300, 'queue' ); // 5 minutes
        }

        $this->logger->debug( 'Critical data preloaded' );
    }

    // ========================================================================
    // Utility Methods
    // ========================================================================

    /**
     * Check if current request is plugin-related
     *
     * @return bool Whether request is plugin-related
     * @since 1.0.0
     */
    private function is_plugin_request() {
        // Check if we're in admin and on plugin pages
        if ( is_admin() ) {
            $screen = get_current_screen();
            if ( $screen && strpos( $screen->id, 'auto-featured-image' ) !== false ) {
                return true;
            }
        }

        // Check if AJAX request is plugin-related
        if ( wp_doing_ajax() ) {
            $action = $_POST['action'] ?? $_GET['action'] ?? '';
            if ( strpos( $action, 'auto_featured_image' ) !== false ) {
                return true;
            }
        }

        // Check if cron job is plugin-related
        if ( wp_doing_cron() ) {
            $cron_action = $_GET['doing_wp_cron'] ?? '';
            if ( strpos( $cron_action, 'auto_featured_image' ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Store performance metrics
     *
     * @since 1.0.0
     */
    private function store_performance_metrics() {
        $metrics_data = array(
            'timestamp' => time(),
            'execution_time' => $this->metrics['execution_time'],
            'memory_used' => $this->metrics['memory_used'],
            'peak_memory' => $this->metrics['peak_memory'],
            'query_count' => $this->metrics['query_count'],
            'request_type' => $this->get_request_type(),
        );

        // Store in database for historical analysis
        $this->plugin->database->store_performance_metrics( $metrics_data );

        // Store in cache for quick access
        $this->set_cache( 'latest_metrics', $metrics_data, 3600, 'performance' );
    }

    /**
     * Get current request type
     *
     * @return string Request type
     * @since 1.0.0
     */
    private function get_request_type() {
        if ( wp_doing_ajax() ) {
            return 'ajax';
        } elseif ( wp_doing_cron() ) {
            return 'cron';
        } elseif ( is_admin() ) {
            return 'admin';
        } else {
            return 'frontend';
        }
    }

    /**
     * Get performance statistics
     *
     * @return array Performance statistics
     * @since 1.0.0
     */
    public function get_performance_statistics() {
        return array(
            'current_metrics' => $this->metrics,
            'cached_metrics' => $this->get_cache( 'latest_metrics', 'performance' ),
            'cache_stats' => $this->get_cache_statistics(),
            'memory_info' => $this->get_memory_info(),
        );
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     * @since 1.0.0
     */
    private function get_cache_statistics() {
        if ( function_exists( 'wp_cache_get_stats' ) ) {
            $stats = wp_cache_get_stats();
            return $stats[ $this->cache_group ] ?? array();
        }

        return array();
    }

    /**
     * Get memory information
     *
     * @return array Memory information
     * @since 1.0.0
     */
    private function get_memory_info() {
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $current_usage = memory_get_usage( true );
        $peak_usage = memory_get_peak_usage( true );

        return array(
            'limit' => $memory_limit,
            'limit_formatted' => size_format( $memory_limit ),
            'current' => $current_usage,
            'current_formatted' => size_format( $current_usage ),
            'peak' => $peak_usage,
            'peak_formatted' => size_format( $peak_usage ),
            'usage_percentage' => round( ( $current_usage / $memory_limit ) * 100, 2 ),
            'peak_percentage' => round( ( $peak_usage / $memory_limit ) * 100, 2 ),
        );
    }

    /**
     * Reset performance data
     *
     * @since 1.0.0
     */
    public function reset_performance_data() {
        $this->metrics = array();
        $this->query_cache = array();

        // Clear performance caches
        $this->delete_cache( 'latest_metrics', 'performance' );
        $this->delete_cache( 'queue_stats', 'queue' );

        $this->logger->info( 'Performance data reset' );
    }
}
