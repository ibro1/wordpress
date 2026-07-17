<?php
/**
 * Job Queue Manager Class
 *
 * Manages job queue operations using Action Scheduler for background processing.
 * Handles job creation, scheduling, progress tracking, and queue management.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Queue Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Queue {

    /**
     * Action Scheduler group name
     *
     * @var string
     * @since 1.0.0
     */
    private $scheduler_group = 'auto_featured_image';

    /**
     * Database handler
     *
     * @var Auto_Featured_Image_Database
     * @since 1.0.0
     */
    private $database;

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Batch manager
     *
     * @var Auto_Featured_Image_Batch_Manager
     * @since 1.0.0
     */
    private $batch_manager;

    /**
     * Current batch ID
     *
     * @var string
     * @since 1.0.0
     */
    private $current_batch_id;

    /**
     * Queue priorities
     *
     * @var array
     * @since 1.0.0
     */
    private $priorities = array(
        'urgent' => 1,
        'high' => 5,
        'normal' => 10,
        'low' => 15,
        'background' => 20,
    );

    /**
     * Retry configuration
     *
     * @var array
     * @since 1.0.0
     */
    private $retry_config = array(
        'max_attempts' => 3,
        'retry_delays' => array( 60, 300, 900 ), // 1min, 5min, 15min
        'exponential_backoff' => true,
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->database = new Auto_Featured_Image_Database();
        $this->logger = new Auto_Featured_Image_Logger();
        $this->batch_manager = new Auto_Featured_Image_Batch_Manager();
        $this->init_retry_config();
        $this->init_hooks();
    }

    /**
     * Initialize retry configuration from settings
     *
     * @since 1.0.0
     */
    private function init_retry_config() {
        $settings = get_option( 'auto_featured_image_settings', array() );

        if ( isset( $settings['max_retry_attempts'] ) ) {
            $this->retry_config['max_attempts'] = intval( $settings['max_retry_attempts'] );
        }

        if ( isset( $settings['retry_delays'] ) && is_array( $settings['retry_delays'] ) ) {
            $this->retry_config['retry_delays'] = $settings['retry_delays'];
        }

        if ( isset( $settings['exponential_backoff'] ) ) {
            $this->retry_config['exponential_backoff'] = (bool) $settings['exponential_backoff'];
        }
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Register Action Scheduler hooks
        add_action( 'auto_featured_image_process_batch', array( $this, 'process_batch' ), 10, 2 );
        add_action( 'auto_featured_image_scan_posts', array( $this, 'scan_posts_batch' ), 10, 3 );
        add_action( 'auto_featured_image_retry_job', array( $this, 'retry_failed_job' ), 10, 1 );
        add_action( 'auto_featured_image_cleanup_jobs', array( $this, 'cleanup_old_jobs' ), 10, 1 );
        add_action( 'auto_featured_image_priority_process', array( $this, 'process_priority_queue' ), 10, 1 );
    }

    /**
     * Check if Action Scheduler is available
     *
     * @return bool True if available, false otherwise
     * @since 1.0.0
     */
    public function is_action_scheduler_available() {
        return function_exists( 'as_schedule_single_action' ) && class_exists( 'ActionScheduler' );
    }

    /**
     * Start processing posts
     *
     * @param array $args Processing arguments
     * @return array Result array with success status and message
     * @since 1.0.0
     */
    public function start_processing( $args = array() ) {
        // Default arguments
        $defaults = array(
            'post_types' => array( 'post' ),
            'post_status' => array( 'publish' ),
            'batch_size' => 50,
            'skip_existing' => true,
            'priority' => 'normal',
            'force_restart' => false,
        );

        $args = wp_parse_args( $args, $defaults );

        // Check if processing is already running
        if ( ! $args['force_restart'] && $this->is_processing_active() ) {
            return array(
                'success' => false,
                'message' => __( 'Processing is already active. Use force_restart to override.', 'auto-featured-image' ),
            );
        }

        // Generate unique batch ID
        $this->current_batch_id = 'batch_' . time() . '_' . wp_generate_password( 8, false );

        // Get posts that need featured images
        $posts_query = $this->get_posts_without_featured_images( $args );
        $total_posts = $posts_query->found_posts;

        if ( $total_posts === 0 ) {
            return array(
                'success' => false,
                'message' => __( 'No posts found that need featured images.', 'auto-featured-image' ),
            );
        }

        // Create progress batch
        $this->database->create_progress_batch( $this->current_batch_id, $total_posts );

        // Create jobs for all posts with priority
        $this->create_jobs_for_posts( $posts_query->posts, $args );

        // Schedule first batch with priority consideration
        $this->schedule_next_batch( $args['priority'] );

        $this->logger->log( 
            'info', 
            sprintf( 'Started processing %d posts in batch %s', $total_posts, $this->current_batch_id ),
            array( 'batch_id' => $this->current_batch_id, 'total_posts' => $total_posts )
        );

        return array(
            'success' => true,
            'message' => sprintf( __( 'Started processing %d posts.', 'auto-featured-image' ), $total_posts ),
            'batch_id' => $this->current_batch_id,
            'total_posts' => $total_posts,
        );
    }

    /**
     * Stop processing
     *
     * @return array Result array
     * @since 1.0.0
     */
    public function stop_processing() {
        // Cancel all pending actions
        if ( $this->is_action_scheduler_available() ) {
            as_unschedule_all_actions( 'auto_featured_image_process_batch', null, $this->scheduler_group );
            as_unschedule_all_actions( 'auto_featured_image_scan_posts', null, $this->scheduler_group );
        }

        // Update any running jobs to cancelled
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';
        
        $wpdb->update(
            $jobs_table,
            array( 'status' => 'cancelled', 'updated_at' => current_time( 'mysql' ) ),
            array( 'status' => 'pending' )
        );

        $this->logger->log( 'info', 'Processing stopped by user' );

        return array(
            'success' => true,
            'message' => __( 'Processing stopped successfully.', 'auto-featured-image' ),
        );
    }

    /**
     * Get processing progress
     *
     * @param string $batch_id Optional batch ID
     * @return array Progress data
     * @since 1.0.0
     */
    public function get_progress( $batch_id = null ) {
        if ( ! $batch_id && $this->current_batch_id ) {
            $batch_id = $this->current_batch_id;
        }

        if ( ! $batch_id ) {
            // Get latest batch
            global $wpdb;
            $progress_table = $wpdb->prefix . 'auto_featured_image_progress';
            $batch_id = $wpdb->get_var( 
                "SELECT batch_id FROM $progress_table ORDER BY created_at DESC LIMIT 1" 
            );
        }

        if ( ! $batch_id ) {
            return array(
                'batch_id' => null,
                'status' => 'idle',
                'total_posts' => 0,
                'processed_posts' => 0,
                'successful_posts' => 0,
                'failed_posts' => 0,
                'percentage' => 0,
            );
        }

        $progress = $this->database->get_progress_batch( $batch_id );
        
        if ( ! $progress ) {
            return array(
                'batch_id' => $batch_id,
                'status' => 'not_found',
                'total_posts' => 0,
                'processed_posts' => 0,
                'successful_posts' => 0,
                'failed_posts' => 0,
                'percentage' => 0,
            );
        }

        $percentage = $progress->total_posts > 0 ? 
            round( ( $progress->processed_posts / $progress->total_posts ) * 100, 2 ) : 0;

        return array(
            'batch_id' => $progress->batch_id,
            'status' => $progress->status,
            'total_posts' => (int) $progress->total_posts,
            'processed_posts' => (int) $progress->processed_posts,
            'successful_posts' => (int) $progress->successful_posts,
            'failed_posts' => (int) $progress->failed_posts,
            'percentage' => $percentage,
            'started_at' => $progress->started_at,
            'completed_at' => $progress->completed_at,
        );
    }

    /**
     * Process a batch of jobs
     *
     * @param string $batch_id Batch ID
     * @param int    $batch_number Batch number
     * @since 1.0.0
     */
    public function process_batch( $batch_id, $batch_number = 1 ) {
        // Calculate optimal batch size using batch manager
        $batch_size = $this->batch_manager->calculate_optimal_batch_size();

        // Get pending jobs for this batch
        $jobs = $this->database->get_pending_jobs( $batch_size );

        if ( empty( $jobs ) ) {
            // No more jobs, mark batch as complete
            $this->database->update_progress_batch( $batch_id, array(
                'status' => 'completed',
                'completed_at' => current_time( 'mysql' ),
            ) );

            $this->logger->log( 'info', "Batch $batch_id completed - no more jobs to process" );
            return;
        }

        // Start batch processing with performance monitoring
        $actual_batch_size = count( $jobs );
        $this->batch_manager->start_batch( $batch_id . '_' . $batch_number, $actual_batch_size );

        $processed = 0;
        $successful = 0;
        $failed = 0;

        foreach ( $jobs as $job ) {
            // Use batch manager to process item with performance monitoring
            $result = $this->batch_manager->process_item(
                array( $this, 'process_single_job' ),
                $job
            );

            $processed++;
            if ( $result === true ) {
                $successful++;
            } elseif ( $result === 'retry' ) {
                // Job will be retried, don't count as failed yet
                $this->schedule_job_retry( $job );
            } else {
                $failed++;
            }

            // The batch manager will handle early stopping internally
            // through the process_item method
        }

        // Finish batch and get performance results
        $batch_results = $this->batch_manager->finish_batch();

        // Update progress with performance data
        $progress = $this->database->get_progress_batch( $batch_id );
        if ( $progress ) {
            $this->database->update_progress_batch( $batch_id, array(
                'processed_posts' => $progress->processed_posts + $processed,
                'successful_posts' => $progress->successful_posts + $successful,
                'failed_posts' => $progress->failed_posts + $failed,
                'status' => 'running',
            ) );
        }

        // Schedule next batch if there are more jobs
        $remaining_jobs = $this->database->get_pending_jobs( 1 );
        if ( ! empty( $remaining_jobs ) ) {
            // Use adaptive scheduling based on performance
            $priority = $this->determine_next_batch_priority( $batch_results );
            $this->schedule_batch( $batch_id, $batch_number + 1, $priority );
        } else {
            // Mark batch as complete
            $this->database->update_progress_batch( $batch_id, array(
                'status' => 'completed',
                'completed_at' => current_time( 'mysql' ),
            ) );
        }

        $this->logger->info(
            "Processed batch $batch_number: $processed jobs ($successful successful, $failed failed) " .
            "in " . round( $batch_results['execution_time'], 2 ) . "s",
            array(
                'performance' => array(
                    'items_per_second' => round( $batch_results['items_per_second'], 2 ),
                    'memory_used_mb' => round( $batch_results['memory_used'] / 1024 / 1024, 2 ),
                    'success_rate' => round( $batch_results['success_rate'], 2 ),
                ),
            )
        );
    }

    /**
     * Determine priority for next batch based on performance
     *
     * @param array $batch_results Previous batch results
     * @return string Priority level
     * @since 1.0.0
     */
    private function determine_next_batch_priority( $batch_results ) {
        // If performance is good, maintain normal priority
        if ( $batch_results['success_rate'] > 90 && $batch_results['execution_time'] < 30 ) {
            return 'normal';
        }

        // If performance is poor, reduce to low priority
        if ( $batch_results['success_rate'] < 70 || $batch_results['execution_time'] > 60 ) {
            return 'low';
        }

        // Default to normal
        return 'normal';
    }

    /**
     * Process a single job
     *
     * @param object $job Job object
     * @return bool|string True on success, 'retry' for retry, false on failure
     * @since 1.0.0
     */
    private function process_single_job( $job ) {
        try {
            // Update job status to running
            $this->database->update_job_status( $job->id, 'running' );

            // Get the post
            $post = get_post( $job->post_id );
            if ( ! $post ) {
                throw new Exception( 'Post not found' );
            }

            // Check if post already has featured image
            if ( has_post_thumbnail( $post->ID ) ) {
                $this->database->update_job_status( $job->id, 'completed' );
                return true;
            }

            // Process the post
            $processor = new Auto_Featured_Image_Processor();
            $result = $processor->process_post( $post->ID );

            if ( $result ) {
                $this->database->update_job_status( $job->id, 'completed' );
                $this->logger->debug( "Job {$job->id} completed successfully", null, $job->post_id );
                return true;
            } else {
                throw new Exception( 'Failed to assign featured image' );
            }

        } catch ( Exception $e ) {
            $attempts = intval( $job->attempts ) + 1;
            $max_attempts = intval( $job->max_attempts );

            // Check if we should retry
            if ( $attempts < $max_attempts && $this->should_retry_error( $e ) ) {
                $this->database->update_job_status( $job->id, 'pending', $e->getMessage() );

                // Update attempt count
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'auto_featured_image_jobs',
                    array( 'attempts' => $attempts ),
                    array( 'id' => $job->id )
                );

                $this->logger->warning(
                    "Job {$job->id} failed (attempt $attempts/$max_attempts): " . $e->getMessage(),
                    array( 'will_retry' => true ),
                    $job->post_id
                );

                return 'retry';
            } else {
                $this->database->update_job_status( $job->id, 'failed', $e->getMessage() );
                $this->logger->error(
                    "Job {$job->id} failed permanently after $attempts attempts: " . $e->getMessage(),
                    array( 'final_failure' => true ),
                    $job->post_id
                );
                return false;
            }
        }
    }

    /**
     * Get posts without featured images
     *
     * @param array $args Query arguments
     * @return WP_Query Query object
     * @since 1.0.0
     */
    private function get_posts_without_featured_images( $args ) {
        $query_args = array(
            'post_type' => $args['post_types'],
            'post_status' => $args['post_status'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '',
                    'compare' => '=',
                ),
            ),
        );

        return new WP_Query( $query_args );
    }

    /**
     * Create jobs for posts
     *
     * @param array $post_ids Array of post IDs
     * @param array $args Processing arguments
     * @since 1.0.0
     */
    private function create_jobs_for_posts( $post_ids, $args ) {
        $priority_value = $this->get_priority_value( $args['priority'] ?? 'normal' );

        foreach ( $post_ids as $post_id ) {
            $this->database->insert_job( $post_id, 'pending', $priority_value );
        }
    }

    /**
     * Get numeric priority value
     *
     * @param string $priority Priority name
     * @return int Priority value
     * @since 1.0.0
     */
    private function get_priority_value( $priority ) {
        return $this->priorities[ $priority ] ?? $this->priorities['normal'];
    }

    /**
     * Check if processing is currently active
     *
     * @return bool True if active
     * @since 1.0.0
     */
    public function is_processing_active() {
        // Check for running jobs
        global $wpdb;
        $jobs_table = $this->database->get_jobs_table();

        $running_jobs = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$jobs_table} WHERE status IN (%s, %s)",
            'running',
            'pending'
        ) );

        // Check for scheduled actions
        if ( $this->is_action_scheduler_available() ) {
            try {
                $pending_actions = as_get_scheduled_actions( array(
                    'hook' => 'auto_featured_image_process_batch',
                    'status' => ActionScheduler_Store::STATUS_PENDING,
                ) );

                return (bool) ( $running_jobs > 0 || ! empty( $pending_actions ) );
            } catch ( Exception $e ) {
                // If Action Scheduler fails, fall back to job count only
                return (bool) ( $running_jobs > 0 );
            }
        }

        return (bool) ( $running_jobs > 0 );
    }

    /**
     * Schedule job retry with exponential backoff
     *
     * @param object $job Job object
     * @since 1.0.0
     */
    private function schedule_job_retry( $job ) {
        $attempts = intval( $job->attempts );
        $delay = $this->calculate_retry_delay( $attempts );

        if ( $this->is_action_scheduler_available() ) {
            as_schedule_single_action(
                time() + $delay,
                'auto_featured_image_retry_job',
                array( $job->id ),
                $this->scheduler_group
            );
        } else {
            wp_schedule_single_event(
                time() + $delay,
                'auto_featured_image_retry_job',
                array( $job->id )
            );
        }

        $this->logger->debug(
            "Scheduled retry for job {$job->id} in {$delay} seconds",
            array( 'attempt' => $attempts + 1, 'delay' => $delay ),
            $job->post_id
        );
    }

    /**
     * Calculate retry delay with exponential backoff
     *
     * @param int $attempt_number Attempt number (0-based)
     * @return int Delay in seconds
     * @since 1.0.0
     */
    private function calculate_retry_delay( $attempt_number ) {
        $delays = $this->retry_config['retry_delays'];

        if ( isset( $delays[ $attempt_number ] ) ) {
            $base_delay = $delays[ $attempt_number ];
        } else {
            $base_delay = end( $delays );
        }

        if ( $this->retry_config['exponential_backoff'] ) {
            $multiplier = pow( 2, $attempt_number );
            return min( $base_delay * $multiplier, 3600 ); // Max 1 hour
        }

        return $base_delay;
    }

    /**
     * Determine if error should trigger retry
     *
     * @param Exception $exception Exception object
     * @return bool True if should retry
     * @since 1.0.0
     */
    private function should_retry_error( $exception ) {
        $message = $exception->getMessage();

        // Don't retry for these permanent errors
        $permanent_errors = array(
            'Post not found',
            'Invalid post type',
            'Post already has featured image',
            'No images available',
        );

        foreach ( $permanent_errors as $error ) {
            if ( stripos( $message, $error ) !== false ) {
                return false;
            }
        }

        // Retry for temporary errors
        $temporary_errors = array(
            'timeout',
            'connection',
            'memory',
            'server error',
            'database error',
        );

        foreach ( $temporary_errors as $error ) {
            if ( stripos( $message, $error ) !== false ) {
                return true;
            }
        }

        // Default: retry unknown errors
        return true;
    }

    /**
     * Retry a failed job
     *
     * @param int $job_id Job ID
     * @since 1.0.0
     */
    public function retry_failed_job( $job_id ) {
        $job = $this->database->get_job( $job_id );

        if ( ! $job ) {
            $this->logger->error( "Cannot retry job $job_id: job not found" );
            return;
        }

        if ( $job->status !== 'pending' ) {
            $this->logger->warning( "Cannot retry job $job_id: status is {$job->status}, expected pending" );
            return;
        }

        $this->logger->info( "Retrying job $job_id for post {$job->post_id}" );
        $this->process_single_job( $job );
    }

    /**
     * Process priority queue
     *
     * @param string $priority Priority level
     * @since 1.0.0
     */
    public function process_priority_queue( $priority = 'urgent' ) {
        $priority_value = $this->get_priority_value( $priority );

        // Get high priority jobs
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $jobs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $jobs_table
             WHERE status = 'pending' AND priority <= %d
             ORDER BY priority ASC, created_at ASC
             LIMIT 10",
            $priority_value
        ) );

        if ( empty( $jobs ) ) {
            return;
        }

        $this->logger->info(
            "Processing " . count( $jobs ) . " priority jobs (priority <= $priority_value)",
            array( 'priority' => $priority )
        );

        foreach ( $jobs as $job ) {
            $this->process_single_job( $job );
        }
    }

    /**
     * Schedule next batch
     *
     * @param string $priority Priority level
     * @since 1.0.0
     */
    private function schedule_next_batch( $priority = 'normal' ) {
        if ( $this->current_batch_id ) {
            $this->schedule_batch( $this->current_batch_id, 1, $priority );
        }
    }

    /**
     * Schedule a batch for processing
     *
     * @param string $batch_id Batch ID
     * @param int    $batch_number Batch number
     * @param string $priority Priority level
     * @since 1.0.0
     */
    private function schedule_batch( $batch_id, $batch_number, $priority = 'normal' ) {
        $delay = $this->get_priority_delay( $priority );

        if ( $this->is_action_scheduler_available() ) {
            as_schedule_single_action(
                time() + $delay,
                'auto_featured_image_process_batch',
                array( $batch_id, $batch_number ),
                $this->scheduler_group
            );
        } else {
            // Fallback to WordPress cron
            wp_schedule_single_event(
                time() + $delay,
                'auto_featured_image_process_batch',
                array( $batch_id, $batch_number )
            );
        }
    }

    /**
     * Get delay based on priority
     *
     * @param string $priority Priority level
     * @return int Delay in seconds
     * @since 1.0.0
     */
    private function get_priority_delay( $priority ) {
        $delays = array(
            'urgent' => 1,     // 1 second
            'high' => 3,       // 3 seconds
            'normal' => 5,     // 5 seconds
            'low' => 10,       // 10 seconds
            'background' => 30, // 30 seconds
        );

        return $delays[ $priority ] ?? $delays['normal'];
    }

    /**
     * Check for stuck jobs and recover them
     *
     * @since 1.0.0
     */
    public function check_stuck_jobs() {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        // Find jobs that have been running for more than 10 minutes
        $stuck_jobs = $wpdb->get_results(
            "SELECT * FROM $jobs_table 
             WHERE status = 'running' 
             AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        );

        foreach ( $stuck_jobs as $job ) {
            // Reset stuck jobs to pending
            $this->database->update_job_status( $job->id, 'pending', 'Job was stuck and reset' );
            $this->logger->log( 'warning', "Reset stuck job {$job->id}", null, $job->post_id );
        }

        if ( ! empty( $stuck_jobs ) ) {
            $this->logger->log( 'info', 'Found and reset ' . count( $stuck_jobs ) . ' stuck jobs' );
        }
    }

    /**
     * Clean up old completed jobs
     *
     * @param int $days Days to keep completed jobs
     * @since 1.0.0
     */
    public function cleanup_old_jobs( $days = 7 ) {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM $jobs_table
             WHERE status IN ('completed', 'failed')
             AND updated_at < %s",
            $cutoff_date
        ) );

        if ( $deleted > 0 ) {
            $this->logger->info( "Cleaned up $deleted old jobs (older than $days days)" );
        }

        return $deleted;
    }

    /**
     * Get queue statistics
     *
     * @return array Queue statistics
     * @since 1.0.0
     */
    public function get_queue_stats() {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $stats = array(
            'total_jobs' => 0,
            'pending_jobs' => 0,
            'running_jobs' => 0,
            'completed_jobs' => 0,
            'failed_jobs' => 0,
            'retry_jobs' => 0,
            'priority_breakdown' => array(),
            'average_processing_time' => 0,
            'success_rate' => 0,
        );

        // Get basic counts
        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $jobs_table GROUP BY status"
        );

        foreach ( $results as $result ) {
            $stats[ $result->status . '_jobs' ] = intval( $result->count );
            $stats['total_jobs'] += intval( $result->count );
        }

        // Get priority breakdown
        $priority_results = $wpdb->get_results(
            "SELECT priority, COUNT(*) as count FROM $jobs_table
             WHERE status = 'pending' GROUP BY priority ORDER BY priority"
        );

        foreach ( $priority_results as $result ) {
            $priority_name = array_search( intval( $result->priority ), $this->priorities );
            $stats['priority_breakdown'][ $priority_name ?: 'unknown' ] = intval( $result->count );
        }

        // Calculate success rate
        $completed = $stats['completed_jobs'];
        $failed = $stats['failed_jobs'];
        $total_processed = $completed + $failed;

        if ( $total_processed > 0 ) {
            $stats['success_rate'] = round( ( $completed / $total_processed ) * 100, 2 );
        }

        // Get jobs that need retry
        $stats['retry_jobs'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $jobs_table
             WHERE status = 'pending' AND attempts > 0"
        );

        return $stats;
    }

    /**
     * Pause queue processing
     *
     * @return bool True on success
     * @since 1.0.0
     */
    public function pause_processing() {
        // Cancel pending actions
        if ( $this->is_action_scheduler_available() ) {
            as_unschedule_all_actions( 'auto_featured_image_process_batch', null, $this->scheduler_group );
        }

        // Set a pause flag
        update_option( 'auto_featured_image_queue_paused', true );

        $this->logger->info( 'Queue processing paused' );

        return true;
    }

    /**
     * Resume queue processing
     *
     * @return bool True on success
     * @since 1.0.0
     */
    public function resume_processing() {
        // Remove pause flag
        delete_option( 'auto_featured_image_queue_paused' );

        // Check for pending jobs and restart processing
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $pending_jobs = $wpdb->get_var(
            "SELECT COUNT(*) FROM $jobs_table WHERE status = 'pending'"
        );

        if ( $pending_jobs > 0 ) {
            // Generate new batch ID and restart
            $this->current_batch_id = 'resume_' . time() . '_' . wp_generate_password( 8, false );
            $this->schedule_next_batch();

            $this->logger->info( "Queue processing resumed with $pending_jobs pending jobs" );
        }

        return true;
    }

    /**
     * Check if queue is paused
     *
     * @return bool True if paused
     * @since 1.0.0
     */
    public function is_paused() {
        return (bool) get_option( 'auto_featured_image_queue_paused', false );
    }

    /**
     * Add single job to queue
     *
     * @param int    $post_id Post ID
     * @param string $priority Priority level
     * @return int|false Job ID or false on failure
     * @since 1.0.0
     */
    public function add_job( $post_id, $priority = 'normal' ) {
        $priority_value = $this->get_priority_value( $priority );
        $job_id = $this->database->insert_job( $post_id, 'pending', $priority_value );

        if ( $job_id ) {
            $this->logger->debug( "Added job $job_id for post $post_id with priority $priority" );

            // If it's urgent priority, process immediately
            if ( $priority === 'urgent' ) {
                $this->process_priority_queue( 'urgent' );
            }
        }

        return $job_id;
    }

    /**
     * Remove job from queue
     *
     * @param int $job_id Job ID
     * @return bool True on success
     * @since 1.0.0
     */
    public function remove_job( $job_id ) {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $result = $wpdb->delete( $jobs_table, array( 'id' => $job_id ) );

        if ( $result ) {
            $this->logger->debug( "Removed job $job_id from queue" );
        }

        return (bool) $result;
    }

    /**
     * Get scheduler group name
     *
     * @return string Scheduler group name
     * @since 1.0.0
     */
    public function get_scheduler_group() {
        return $this->scheduler_group;
    }

    /**
     * Get retry configuration
     *
     * @return array Retry configuration
     * @since 1.0.0
     */
    public function get_retry_config() {
        return $this->retry_config;
    }

    /**
     * Update retry configuration
     *
     * @param array $config New configuration
     * @since 1.0.0
     */
    public function update_retry_config( $config ) {
        $this->retry_config = array_merge( $this->retry_config, $config );

        // Save to settings
        $settings = get_option( 'auto_featured_image_settings', array() );
        $settings['max_retry_attempts'] = $this->retry_config['max_attempts'];
        $settings['retry_delays'] = $this->retry_config['retry_delays'];
        $settings['exponential_backoff'] = $this->retry_config['exponential_backoff'];
        update_option( 'auto_featured_image_settings', $settings );
    }

    /**
     * Clear all jobs from the queue
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public function clear_queue() {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';

        $result = $wpdb->query( "TRUNCATE TABLE {$jobs_table}" );

        if ( $result !== false ) {
            $this->logger->info( 'Queue cleared successfully' );
            return true;
        } else {
            $this->logger->error( 'Failed to clear queue', array(
                'error' => $wpdb->last_error,
            ) );
            return false;
        }
    }

    /**
     * Get processing progress information
     *
     * @return array Progress information
     * @since 1.0.0
     */
    public function get_processing_progress() {
        global $wpdb;

        $jobs_table = $this->database->get_jobs_table();

        // Get overall statistics
        $total_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table}" );
        $completed_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = 'completed'" );
        $failed_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = 'failed'" );
        $pending_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = 'pending'" );
        $processing_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = 'processing'" );

        // Calculate percentages
        $completion_percentage = $total_jobs > 0 ? round( ( $completed_jobs / $total_jobs ) * 100, 2 ) : 0;
        $success_rate = ( $completed_jobs + $failed_jobs ) > 0 ? round( ( $completed_jobs / ( $completed_jobs + $failed_jobs ) ) * 100, 2 ) : 0;

        // Get current processing status (ensure boolean values)
        $is_processing = (bool) $this->is_processing_active();
        $is_paused = (bool) $this->is_paused();

        // Get estimated time remaining (ensure integer)
        $estimated_time = (int) $this->estimate_remaining_time();

        return array(
            // Original format for backward compatibility
            'total_jobs' => (int) $total_jobs,
            'completed_jobs' => (int) $completed_jobs,
            'failed_jobs' => (int) $failed_jobs,
            'pending_jobs' => (int) $pending_jobs,
            'processing_jobs' => (int) $processing_jobs,
            'completion_percentage' => $completion_percentage,
            'success_rate' => $success_rate,
            'is_processing' => $is_processing,
            'is_paused' => $is_paused,
            'estimated_time_remaining' => $estimated_time,
            'status' => $is_paused ? 'paused' : ( $is_processing ? 'processing' : 'idle' ),

            // JavaScript-expected format
            'total' => (int) $total_jobs,
            'processed' => (int) $completed_jobs,
            'remaining' => (int) $pending_jobs,
            'success' => (int) $completed_jobs,
            'failed' => (int) $failed_jobs,
            'percentage' => $completion_percentage,
        );
    }

    /**
     * Estimate remaining processing time
     *
     * @return int Estimated seconds remaining
     * @since 1.0.0
     */
    private function estimate_remaining_time() {
        global $wpdb;

        $jobs_table = $this->database->get_jobs_table();

        // Get pending jobs count
        $pending_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM {$jobs_table} WHERE status = 'pending'" );

        if ( $pending_jobs == 0 ) {
            return 0;
        }

        // Get average processing time from recent completed jobs
        $avg_time = $wpdb->get_var( "
            SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at))
            FROM {$jobs_table}
            WHERE status = 'completed'
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            LIMIT 100
        " );

        // Default to 30 seconds per job if no data available
        $avg_time = $avg_time ? $avg_time : 30;

        return (int) ( $pending_jobs * $avg_time );
    }

    /**
     * Start bulk processing
     *
     * @param array $args Processing arguments
     * @return array Result with success status and message
     * @since 1.0.0
     */
    public function start_bulk_processing( $args = array() ) {
        $defaults = array(
            'post_types' => array( 'post' ),
            'skip_existing' => true,
            'batch_size' => 25,
            'priority' => 'normal',
            'date_from' => '',
            'date_to' => '',
            'post_ids' => array(),
        );

        $args = wp_parse_args( $args, $defaults );

        // Validate arguments
        if ( empty( $args['post_types'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'No post types specified.', 'auto-featured-image' ),
            );
        }

        // Get posts to process
        $posts = $this->get_posts_for_bulk_processing( $args );

        if ( empty( $posts ) ) {
            return array(
                'success' => false,
                'message' => __( 'No posts found to process.', 'auto-featured-image' ),
            );
        }

        // Add jobs to queue
        $job_count = 0;
        foreach ( $posts as $post ) {
            $job_id = $this->add_job( $post->ID, array(
                'priority' => $args['priority'],
                'source' => 'bulk_processing',
            ) );

            if ( $job_id ) {
                $job_count++;
            }
        }

        // Start processing
        $this->resume_processing();

        return array(
            'success' => true,
            'message' => sprintf( __( 'Added %d jobs to the queue.', 'auto-featured-image' ), $job_count ),
            'job_count' => $job_count,
        );
    }

    /**
     * Estimate bulk jobs
     *
     * @param array $args Processing arguments
     * @return array Estimation data
     * @since 1.0.0
     */
    public function estimate_bulk_jobs( $args = array() ) {
        $defaults = array(
            'post_types' => array( 'post' ),
            'skip_existing' => true,
            'batch_size' => 25,
            'date_from' => '',
            'date_to' => '',
            'post_ids' => array(),
        );

        $args = wp_parse_args( $args, $defaults );

        // Get posts that would be processed
        $posts = $this->get_posts_for_bulk_processing( $args );
        $total_posts = count( $posts );

        // Estimate processing time (30 seconds per post average)
        $estimated_time = $total_posts * 30;

        return array(
            'total_posts' => $total_posts,
            'estimated_time' => $estimated_time,
            'estimated_time_formatted' => $this->format_duration( $estimated_time ),
            'post_types' => $args['post_types'],
        );
    }

    /**
     * Get posts for bulk processing
     *
     * @param array $args Query arguments
     * @return array Posts to process
     * @since 1.0.0
     */
    private function get_posts_for_bulk_processing( $args ) {
        $query_args = array(
            'post_type' => $args['post_types'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        );

        // Add date filters
        if ( ! empty( $args['date_from'] ) || ! empty( $args['date_to'] ) ) {
            $date_query = array();

            if ( ! empty( $args['date_from'] ) ) {
                $date_query['after'] = $args['date_from'];
            }

            if ( ! empty( $args['date_to'] ) ) {
                $date_query['before'] = $args['date_to'];
            }

            $query_args['date_query'] = array( $date_query );
        }

        // Filter by specific post IDs
        if ( ! empty( $args['post_ids'] ) ) {
            $query_args['post__in'] = $args['post_ids'];
        }

        // Skip posts that already have featured images
        if ( $args['skip_existing'] ) {
            $query_args['meta_query'] = array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
            );
        }

        $query = new WP_Query( $query_args );
        return $query->posts;
    }

    /**
     * Format duration in human readable format
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     * @since 1.0.0
     */
    private function format_duration( $seconds ) {
        if ( $seconds < 60 ) {
            return sprintf( __( '%d seconds', 'auto-featured-image' ), $seconds );
        } elseif ( $seconds < 3600 ) {
            $minutes = floor( $seconds / 60 );
            return sprintf( __( '%d minutes', 'auto-featured-image' ), $minutes );
        } else {
            $hours = floor( $seconds / 3600 );
            $minutes = floor( ( $seconds % 3600 ) / 60 );
            return sprintf( __( '%d hours %d minutes', 'auto-featured-image' ), $hours, $minutes );
        }
    }

    /**
     * Process next batch of jobs
     *
     * @return array Processing result
     * @since 1.0.0
     */
    public function process_next_batch() {
        global $wpdb;

        $jobs_table = $this->database->get_jobs_table();
        $batch_size = $this->get_batch_size();

        // Get pending jobs
        $jobs = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$jobs_table}
             WHERE status = 'pending'
             ORDER BY priority DESC, created_at ASC
             LIMIT %d",
            $batch_size
        ) );

        if ( empty( $jobs ) ) {
            return array(
                'success' => true,
                'processed' => 0,
                'message' => 'No pending jobs to process',
            );
        }

        $processed = 0;
        $errors = array();

        foreach ( $jobs as $job ) {
            // Mark job as processing
            $wpdb->update(
                $jobs_table,
                array( 'status' => 'processing', 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => $job->id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            try {
                // Process the job (returns boolean or 'retry')
                $result = $this->process_single_job( $job );

                if ( $result === true ) {
                    $processed++;
                } elseif ( $result === 'retry' ) {
                    // Job will be retried later, don't count as error
                } else {
                    $errors[] = 'Job processing failed';
                }
            } catch ( Exception $e ) {
                $errors[] = $e->getMessage();
                $this->logger->error( 'Job processing exception: ' . $e->getMessage(), array( 'job_id' => $job->id ) );
            }
        }

        return array(
            'success' => true,
            'processed' => $processed,
            'total_jobs' => count( $jobs ),
            'errors' => $errors,
            'message' => sprintf( 'Processed %d of %d jobs', $processed, count( $jobs ) ),
        );
    }



    /**
     * Get batch size for processing
     *
     * @return int Batch size
     * @since 1.0.0
     */
    private function get_batch_size() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        return isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 25;
    }
}