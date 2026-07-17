<?php
/**
 * Batch Processing Manager Class
 *
 * Intelligent batch processing with dynamic sizing, performance monitoring,
 * and adaptive optimization for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Batch Manager Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Batch_Manager {

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Database handler
     *
     * @var Auto_Featured_Image_Database
     * @since 1.0.0
     */
    private $database;

    /**
     * Performance metrics
     *
     * @var array
     * @since 1.0.0
     */
    private $metrics = array();

    /**
     * Batch configuration
     *
     * @var array
     * @since 1.0.0
     */
    private $config = array(
        'min_batch_size' => 5,
        'max_batch_size' => 100,
        'default_batch_size' => 25,
        'target_execution_time' => 30, // seconds
        'memory_limit_threshold' => 0.8, // 80% of memory limit
        'adaptive_sizing' => true,
        'performance_monitoring' => true,
    );

    /**
     * Current batch metrics
     *
     * @var array
     * @since 1.0.0
     */
    private $current_batch = array(
        'start_time' => 0,
        'start_memory' => 0,
        'processed_items' => 0,
        'successful_items' => 0,
        'failed_items' => 0,
        'batch_id' => null,
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Auto_Featured_Image_Logger();
        $this->database = new Auto_Featured_Image_Database();
        $this->init_config();
        $this->load_performance_history();
    }

    /**
     * Initialize configuration from settings
     *
     * @since 1.0.0
     */
    private function init_config() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        
        // Override defaults with settings
        if ( isset( $settings['batch_size'] ) ) {
            $this->config['default_batch_size'] = max( $this->config['min_batch_size'], 
                min( $this->config['max_batch_size'], intval( $settings['batch_size'] ) ) );
        }
        
        if ( isset( $settings['target_execution_time'] ) ) {
            $this->config['target_execution_time'] = intval( $settings['target_execution_time'] );
        }
        
        if ( isset( $settings['adaptive_batch_sizing'] ) ) {
            $this->config['adaptive_sizing'] = (bool) $settings['adaptive_batch_sizing'];
        }
        
        if ( isset( $settings['performance_monitoring'] ) ) {
            $this->config['performance_monitoring'] = (bool) $settings['performance_monitoring'];
        }
    }

    /**
     * Load performance history for adaptive sizing
     *
     * @since 1.0.0
     */
    private function load_performance_history() {
        $this->metrics = get_option( 'auto_featured_image_batch_metrics', array(
            'average_execution_time' => 0,
            'average_memory_usage' => 0,
            'average_items_per_second' => 0,
            'optimal_batch_size' => $this->config['default_batch_size'],
            'total_batches' => 0,
            'last_updated' => 0,
        ) );
    }

    /**
     * Calculate optimal batch size based on performance history
     *
     * @param string $priority Job priority
     * @return int Optimal batch size
     * @since 1.0.0
     */
    public function calculate_optimal_batch_size( $priority = 'normal' ) {
        if ( ! $this->config['adaptive_sizing'] ) {
            return $this->config['default_batch_size'];
        }

        $base_size = $this->config['default_batch_size'];
        
        // Adjust based on priority
        $priority_multipliers = array(
            'urgent' => 0.5,     // Smaller batches for urgent jobs
            'high' => 0.7,
            'normal' => 1.0,
            'low' => 1.3,
            'background' => 1.5, // Larger batches for background jobs
        );
        
        $multiplier = $priority_multipliers[ $priority ] ?? 1.0;
        $adjusted_size = round( $base_size * $multiplier );
        
        // Apply performance-based adjustments
        if ( $this->metrics['total_batches'] > 5 ) {
            $performance_adjustment = $this->calculate_performance_adjustment();
            $adjusted_size = round( $adjusted_size * $performance_adjustment );
        }
        
        // Apply system resource constraints
        $resource_adjustment = $this->calculate_resource_adjustment();
        $adjusted_size = round( $adjusted_size * $resource_adjustment );
        
        // Ensure within bounds
        $optimal_size = max( $this->config['min_batch_size'], 
            min( $this->config['max_batch_size'], $adjusted_size ) );
        
        $this->logger->debug( 
            "Calculated optimal batch size: $optimal_size (base: $base_size, priority: $priority)",
            array(
                'priority_multiplier' => $multiplier,
                'performance_adjustment' => $performance_adjustment ?? 1.0,
                'resource_adjustment' => $resource_adjustment,
            )
        );
        
        return $optimal_size;
    }

    /**
     * Calculate performance-based adjustment
     *
     * @return float Adjustment multiplier
     * @since 1.0.0
     */
    private function calculate_performance_adjustment() {
        $target_time = $this->config['target_execution_time'];
        $avg_time = $this->metrics['average_execution_time'];
        
        if ( $avg_time <= 0 ) {
            return 1.0;
        }
        
        // If execution time is too high, reduce batch size
        if ( $avg_time > $target_time * 1.2 ) {
            return 0.8; // Reduce by 20%
        }
        
        // If execution time is too low, increase batch size
        if ( $avg_time < $target_time * 0.5 ) {
            return 1.3; // Increase by 30%
        }
        
        // Fine-tune based on ratio
        $ratio = $target_time / $avg_time;
        return min( 1.5, max( 0.5, $ratio * 0.8 + 0.2 ) );
    }

    /**
     * Calculate resource-based adjustment
     *
     * @return float Adjustment multiplier
     * @since 1.0.0
     */
    private function calculate_resource_adjustment() {
        $adjustment = 1.0;
        
        // Memory usage adjustment
        $memory_usage = $this->get_memory_usage_percentage();
        if ( $memory_usage > $this->config['memory_limit_threshold'] ) {
            $adjustment *= 0.7; // Reduce batch size if memory is high
        } elseif ( $memory_usage < 0.5 ) {
            $adjustment *= 1.2; // Increase if memory usage is low
        }
        
        // Server load adjustment (if available)
        if ( function_exists( 'sys_getloadavg' ) ) {
            $load = sys_getloadavg();
            if ( $load[0] > 2.0 ) {
                $adjustment *= 0.8; // Reduce if server load is high
            }
        }
        
        return $adjustment;
    }

    /**
     * Start batch processing
     *
     * @param string $batch_id Batch identifier
     * @param int    $batch_size Number of items to process
     * @return bool True on success
     * @since 1.0.0
     */
    public function start_batch( $batch_id, $batch_size ) {
        $this->current_batch = array(
            'batch_id' => $batch_id,
            'batch_size' => $batch_size,
            'start_time' => microtime( true ),
            'start_memory' => memory_get_usage( true ),
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'peak_memory' => memory_get_usage( true ),
        );
        
        $this->logger->debug( 
            "Started batch $batch_id with size $batch_size",
            array(
                'start_memory_mb' => round( $this->current_batch['start_memory'] / 1024 / 1024, 2 ),
                'memory_limit' => ini_get( 'memory_limit' ),
            )
        );
        
        return true;
    }

    /**
     * Process single item in batch
     *
     * @param callable $processor Processing function
     * @param mixed    $item Item to process
     * @return bool True on success
     * @since 1.0.0
     */
    public function process_item( $processor, $item ) {
        $item_start_time = microtime( true );
        $item_start_memory = memory_get_usage( true );
        
        try {
            $result = call_user_func( $processor, $item );
            
            $this->current_batch['processed_items']++;
            
            if ( $result ) {
                $this->current_batch['successful_items']++;
            } else {
                $this->current_batch['failed_items']++;
            }
            
            // Update peak memory
            $current_memory = memory_get_usage( true );
            if ( $current_memory > $this->current_batch['peak_memory'] ) {
                $this->current_batch['peak_memory'] = $current_memory;
            }
            
            // Check for memory or time limits
            if ( $this->should_stop_batch() ) {
                $this->logger->warning( 
                    "Stopping batch early due to resource constraints",
                    array(
                        'processed_items' => $this->current_batch['processed_items'],
                        'elapsed_time' => microtime( true ) - $this->current_batch['start_time'],
                        'memory_usage_mb' => round( $current_memory / 1024 / 1024, 2 ),
                    )
                );
                return false;
            }
            
            return $result;
            
        } catch ( Exception $e ) {
            $this->current_batch['processed_items']++;
            $this->current_batch['failed_items']++;
            
            $this->logger->error( 
                "Error processing item in batch: " . $e->getMessage(),
                array(
                    'batch_id' => $this->current_batch['batch_id'],
                    'item' => $item,
                )
            );
            
            return false;
        }
    }

    /**
     * Finish batch processing and update metrics
     *
     * @return array Batch results
     * @since 1.0.0
     */
    public function finish_batch() {
        $end_time = microtime( true );
        $end_memory = memory_get_usage( true );
        
        $batch_results = array(
            'batch_id' => $this->current_batch['batch_id'],
            'batch_size' => $this->current_batch['batch_size'],
            'processed_items' => $this->current_batch['processed_items'],
            'successful_items' => $this->current_batch['successful_items'],
            'failed_items' => $this->current_batch['failed_items'],
            'execution_time' => $end_time - $this->current_batch['start_time'],
            'memory_used' => $this->current_batch['peak_memory'] - $this->current_batch['start_memory'],
            'peak_memory' => $this->current_batch['peak_memory'],
            'items_per_second' => 0,
            'success_rate' => 0,
        );
        
        // Calculate rates
        if ( $batch_results['execution_time'] > 0 ) {
            $batch_results['items_per_second'] = $batch_results['processed_items'] / $batch_results['execution_time'];
        }
        
        if ( $batch_results['processed_items'] > 0 ) {
            $batch_results['success_rate'] = ( $batch_results['successful_items'] / $batch_results['processed_items'] ) * 100;
        }
        
        // Update performance metrics
        if ( $this->config['performance_monitoring'] ) {
            $this->update_performance_metrics( $batch_results );
        }
        
        $this->logger->info( 
            "Completed batch {$batch_results['batch_id']}: {$batch_results['processed_items']} items in " . 
            round( $batch_results['execution_time'], 2 ) . "s",
            array(
                'success_rate' => round( $batch_results['success_rate'], 2 ) . '%',
                'items_per_second' => round( $batch_results['items_per_second'], 2 ),
                'memory_used_mb' => round( $batch_results['memory_used'] / 1024 / 1024, 2 ),
            )
        );
        
        return $batch_results;
    }

    /**
     * Check if batch should be stopped early
     *
     * @return bool True if should stop
     * @since 1.0.0
     */
    private function should_stop_batch() {
        $current_time = microtime( true );
        $elapsed_time = $current_time - $this->current_batch['start_time'];

        // Check execution time limit
        if ( $elapsed_time > $this->config['target_execution_time'] * 1.5 ) {
            return true;
        }

        // Check memory usage
        $memory_usage = $this->get_memory_usage_percentage();
        if ( $memory_usage > $this->config['memory_limit_threshold'] ) {
            return true;
        }

        // Check if we're approaching PHP max execution time
        $max_execution_time = ini_get( 'max_execution_time' );
        if ( $max_execution_time > 0 && $elapsed_time > ( $max_execution_time * 0.8 ) ) {
            return true;
        }

        return false;
    }

    /**
     * Get current memory usage percentage
     *
     * @return float Memory usage percentage (0-1)
     * @since 1.0.0
     */
    private function get_memory_usage_percentage() {
        $current_memory = memory_get_usage( true );
        $memory_limit = $this->parse_memory_limit( ini_get( 'memory_limit' ) );

        if ( $memory_limit <= 0 ) {
            return 0; // No limit set
        }

        return $current_memory / $memory_limit;
    }

    /**
     * Parse memory limit string to bytes
     *
     * @param string $limit Memory limit string
     * @return int Memory limit in bytes
     * @since 1.0.0
     */
    private function parse_memory_limit( $limit ) {
        if ( $limit === '-1' ) {
            return -1; // No limit
        }

        $limit = trim( $limit );
        $last_char = strtolower( $limit[ strlen( $limit ) - 1 ] );
        $number = intval( $limit );

        switch ( $last_char ) {
            case 'g':
                $number *= 1024;
                // Fall through
            case 'm':
                $number *= 1024;
                // Fall through
            case 'k':
                $number *= 1024;
        }

        return $number;
    }

    /**
     * Update performance metrics with batch results
     *
     * @param array $batch_results Batch results
     * @since 1.0.0
     */
    private function update_performance_metrics( $batch_results ) {
        $total_batches = $this->metrics['total_batches'];
        $new_total = $total_batches + 1;

        // Calculate running averages
        $this->metrics['average_execution_time'] = (
            ( $this->metrics['average_execution_time'] * $total_batches ) + $batch_results['execution_time']
        ) / $new_total;

        $this->metrics['average_memory_usage'] = (
            ( $this->metrics['average_memory_usage'] * $total_batches ) + $batch_results['memory_used']
        ) / $new_total;

        $this->metrics['average_items_per_second'] = (
            ( $this->metrics['average_items_per_second'] * $total_batches ) + $batch_results['items_per_second']
        ) / $new_total;

        // Update optimal batch size based on performance
        if ( $batch_results['execution_time'] > 0 && $batch_results['success_rate'] > 80 ) {
            $performance_score = $batch_results['items_per_second'] / ( $batch_results['memory_used'] / 1024 / 1024 );

            if ( $performance_score > $this->metrics['best_performance_score'] ?? 0 ) {
                $this->metrics['optimal_batch_size'] = $batch_results['batch_size'];
                $this->metrics['best_performance_score'] = $performance_score;
            }
        }

        $this->metrics['total_batches'] = $new_total;
        $this->metrics['last_updated'] = time();

        // Save metrics
        update_option( 'auto_featured_image_batch_metrics', $this->metrics );
    }

    /**
     * Get current metrics
     *
     * @return array Current performance metrics
     * @since 1.0.0
     */
    public function get_metrics() {
        return $this->metrics;
    }

    /**
     * Get current batch size
     *
     * @return int Current batch size
     * @since 1.0.0
     */
    public function get_current_batch_size() {
        return $this->batch_size;
    }

    /**
     * Set batch size
     *
     * @param int $size New batch size
     * @since 1.0.0
     */
    public function set_batch_size( $size ) {
        $this->batch_size = max( 1, intval( $size ) );
        update_option( 'auto_featured_image_batch_size', $this->batch_size );
    }

    /**
     * Reset batch manager state
     *
     * @since 1.0.0
     */
    public function reset_state() {
        $this->metrics = array(
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'avg_execution_time' => 0,
            'last_batch_time' => 0,
        );

        $this->current_batch = array();
        $this->processing = false;
    }

    /**
     * Get batch configuration
     *
     * @return array Batch configuration
     * @since 1.0.0
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Reset performance metrics
     *
     * @since 1.0.0
     */
    public function reset_metrics() {
        $this->metrics = array(
            'average_execution_time' => 0,
            'average_memory_usage' => 0,
            'average_items_per_second' => 0,
            'optimal_batch_size' => $this->config['default_batch_size'],
            'total_batches' => 0,
            'last_updated' => 0,
        );

        update_option( 'auto_featured_image_batch_metrics', $this->metrics );
        $this->logger->info( 'Performance metrics reset' );
    }
}
