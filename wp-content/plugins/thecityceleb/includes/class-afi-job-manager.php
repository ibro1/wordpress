<?php
/**
 * Job manager class for Auto Featured Image
 *
 * Handles job lifecycle management, Action Scheduler integration,
 * and background job processing coordination.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job manager class that handles job lifecycle management
 */
class AFI_Job_Manager {
    
    /**
     * Database instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Scanner instance
     *
     * @var AFI_Scanner
     */
    private $scanner;
    
    /**
     * Processor instance
     *
     * @var AFI_Processor
     */
    private $processor;
    
    /**
     * Action Scheduler group name
     *
     * @var string
     */
    private $scheduler_group = 'afi_jobs';
    
    /**
     * Scanner batch size
     *
     * @var int
     */
    private $scanner_batch_size = 1000;
    
    /**
     * Processor batch size
     *
     * @var int
     */
    private $processor_batch_size = 50;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Job manager constructor starting');
            }
            
            $this->database = new AFI_Database();
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Database created');
            }
            
            $this->scanner = new AFI_Scanner($this->database);
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Scanner created');
            }
            
            // Load required classes for processor
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-service.php';
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-processor.php';
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: About to create image service');
            }
            
            $image_service = new AFI_Image_Service();
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Image service created, about to create processor');
            }
            
            $this->processor = new AFI_Processor($this->database, $image_service);
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Processor created, about to init hooks');
            }
            
            $this->init_hooks();
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Job manager constructor completed successfully');
            }
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('AFI Debug: Job manager constructor failed: ' . $e->getMessage());
                error_log('AFI Debug: Stack trace: ' . $e->getTraceAsString());
            }
            throw $e;
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register Action Scheduler hooks
        add_action('afi_scan_posts_batch', array($this, 'process_scan_batch'), 10, 3);
        add_action('afi_process_job_items', array($this, 'process_job_items_batch'), 10, 2);
        add_action('afi_cleanup_old_jobs', array($this, 'cleanup_old_jobs'), 10, 1);
        
        // Schedule daily cleanup if not already scheduled
        if (!wp_next_scheduled('afi_cleanup_old_jobs')) {
            wp_schedule_event(time(), 'daily', 'afi_cleanup_old_jobs', array(90));
        }
    }
    
    /**
     * Create a new scan job
     *
     * @param array $post_types Array of post types to scan
     * @param array $image_filters Optional image filters
     * @return int|false Job ID on success, false on failure
     */
    public function create_scan_job($post_types, $image_filters = array()) {
        try {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: create_scan_job called with post_types: ' . json_encode($post_types) . ', filters: ' . json_encode($image_filters));
            }
            
            // Validate input
            if (empty($post_types) || !is_array($post_types)) {
                if (function_exists('error_log')) {
                    error_log('AFI Debug: create_scan_job failed - invalid post_types');
                }
                return false;
            }
            
            // Sanitize post types
            $post_types = array_map('sanitize_text_field', $post_types);
            
            // Validate post types exist
            foreach ($post_types as $post_type) {
                if (!post_type_exists($post_type)) {
                    if (function_exists('error_log')) {
                        error_log('AFI Debug: create_scan_job failed - post type does not exist: ' . $post_type);
                    }
                    return false;
                }
            }
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: About to create job record');
            }
            
            // Create job record
            $job_data = array(
                'status' => 'scanning',
                'post_types' => $post_types,
                'image_filters' => $image_filters,
                'total_items' => 0,
                'processed_items' => 0,
                'created_at' => current_time('mysql')
            );
            
            $job_id = $this->database->create_job($job_data);
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: create_job returned: ' . ($job_id ? $job_id : 'false'));
            }
            
            if (!$job_id) {
                if (function_exists('error_log')) {
                    error_log('AFI Debug: create_scan_job failed - database create_job returned false');
                }
                return false;
            }
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: About to schedule first scan batch');
            }
            
            // For testing: Process immediately instead of scheduling
            if (defined('AFI_IMMEDIATE_PROCESSING') && AFI_IMMEDIATE_PROCESSING) {
                // Debug logging
                if (function_exists('error_log')) {
                    error_log('AFI Debug: Using immediate processing mode');
                }
                
                // Process scan batch immediately
                $this->process_scan_batch($job_id, $post_types, 1);
            } else {
                // Schedule the first scan batch
                $this->schedule_scan_batch($job_id, $post_types, 1);
            }
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: create_scan_job completed successfully, returning job_id: ' . $job_id);
            }
            
            return $job_id;
            
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('AFI Debug: create_scan_job exception: ' . $e->getMessage());
                error_log('AFI Debug: Stack trace: ' . $e->getTraceAsString());
            }
            return false;
        }
    }
    
    /**
     * Start processing a scanned job
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function start_processing($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job || $job->status !== 'pending') {
            return false;
        }
        
        // Update job status to running
        $updated = $this->database->update_job($job_id, array(
            'status' => 'running'
        ));
        
        if (!$updated) {
            return false;
        }
        
        // Schedule processing batches
        $this->schedule_processing_batches($job_id);
        
        return true;
    }
    
    /**
     * Pause a running job
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function pause_job($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job || !in_array($job->status, array('scanning', 'running'))) {
            return false;
        }
        
        // Update job status to paused
        $updated = $this->database->update_job($job_id, array(
            'status' => 'paused'
        ));
        
        if (!$updated) {
            return false;
        }
        
        // Cancel all scheduled actions for this job
        $this->cancel_scheduled_actions($job_id);
        
        return true;
    }
    
    /**
     * Resume a paused job
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function resume_job($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job || $job->status !== 'paused') {
            return false;
        }
        
        // Determine what phase to resume
        if ($job->total_items == 0) {
            // Resume scanning
            $updated = $this->database->update_job($job_id, array(
                'status' => 'scanning'
            ));
            
            if ($updated) {
                $this->resume_scanning($job_id);
            }
        } else {
            // Resume processing
            $updated = $this->database->update_job($job_id, array(
                'status' => 'running'
            ));
            
            if ($updated) {
                $this->schedule_processing_batches($job_id);
            }
        }
        
        return $updated;
    }
    
    /**
     * Cancel a job
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function cancel_job($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job || in_array($job->status, array('complete', 'canceled', 'failed'))) {
            return false;
        }
        
        // Update job status to canceled
        $updated = $this->database->update_job($job_id, array(
            'status' => 'canceled',
            'finished_at' => current_time('mysql')
        ));
        
        if (!$updated) {
            return false;
        }
        
        // Cancel all scheduled actions for this job
        $this->cancel_scheduled_actions($job_id);
        
        return true;
    }
    
    /**
     * Get job status and progress
     *
     * @param int $job_id Job ID
     * @return object|false Job status data or false on failure
     */
    public function get_job_status($job_id) {
        return $this->database->get_job($job_id);
    }
    
    /**
     * Process a scan batch (Action Scheduler/WordPress cron callback)
     *
     * @param int   $job_id Job ID
     * @param array $post_types Post types to scan
     * @param int   $page Current page number
     */
    public function process_scan_batch($job_id, $post_types, $page) {
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: process_scan_batch called with job_id: ' . $job_id . ', post_types: ' . json_encode($post_types) . ', page: ' . $page);
        }
        
        // Check if job is still valid and in scanning status
        $job = $this->database->get_job($job_id);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Retrieved job: ' . ($job ? 'found, status: ' . $job->status : 'not found'));
        }
        
        if (!$job || !in_array($job->status, array('scanning', 'running'))) {
            if (function_exists('error_log')) {
                error_log('AFI Debug: process_scan_batch exiting - job not valid or wrong status');
            }
            return; // Job was canceled or completed
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: About to call scanner->scan_posts_batch');
        }
        
        // Use the scanner service to process the batch
        $result = $this->scanner->scan_posts_batch($job_id, $post_types, $page);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: scanner->scan_posts_batch returned: ' . json_encode($result));
        }
        
        if (!$result['success']) {
            // Log error and stop processing
            if (function_exists('error_log')) {
                error_log("AFI Scanner Error: " . $result['error']);
            }
            return;
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner result success, checking if has_more: ' . ($result['has_more'] ? 'true' : 'false'));
            error_log('AFI Debug: About to check has_more condition');
        }
        
        // Schedule next batch if there are more posts
        if ($result['has_more']) {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: More posts to scan, scheduling next batch');
            }
            $this->schedule_scan_batch($job_id, $post_types, $page + 1);
        } else {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Scanning complete, transitioning to processing phase');
            }
            
            // Scanning complete, start processing job items
            $this->database->update_job($job_id, array(
                'status' => 'running'
            ));
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Job status updated to running, about to schedule processing batches');
            }
            
            // Schedule processing batches
            $this->schedule_processing_batches($job_id);
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Processing batches scheduled');
            }
        }
        
        // Debug logging - end of method
        if (function_exists('error_log')) {
            error_log('AFI Debug: process_scan_batch method completed');
        }
    }
    
    /**
     * Process job items batch (Action Scheduler callback)
     *
     * @param int $job_id Job ID
     * @param int $batch_number Batch number for tracking
     */
    public function process_job_items_batch($job_id, $batch_number) {
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: process_job_items_batch called with job_id: ' . $job_id . ', batch_number: ' . $batch_number);
        }
        
        $job = $this->database->get_job($job_id);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Retrieved job for processing: ' . ($job ? 'found, status: ' . $job->status : 'not found'));
        }
        
        // Check if job is still running
        if (!$job || $job->status !== 'running') {
            if (function_exists('error_log')) {
                error_log('AFI Debug: process_job_items_batch exiting - job not found or not running');
            }
            return;
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: About to get pending job items for job_id: ' . $job_id);
        }
        
        // Get pending job items
        $job_items = $this->database->get_job_items(
            $job_id, 
            $this->processor_batch_size, 
            0, 
            'pending'
        );
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Retrieved ' . count($job_items) . ' pending job items');
        }
        
        if (empty($job_items)) {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: No pending job items found, marking job as complete');
            }
            
            // No more items to process, mark job as complete
            $this->database->update_job($job_id, array(
                'status' => 'complete',
                'finished_at' => current_time('mysql')
            ));
            return;
        }
        
        $processed_count = 0;
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: About to process ' . count($job_items) . ' job items');
        }
        
        foreach ($job_items as $item) {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Processing job item ID: ' . $item->id . ', post_id: ' . $item->post_id);
            }
            
            $this->process_single_item($item);
            $processed_count++;
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Completed processing job item ID: ' . $item->id);
            }
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Processed ' . $processed_count . ' job items');
        }
        
        // Update job processed items count
        $this->database->update_job($job_id, array(
            'processed_items' => $job->processed_items + $processed_count
        ));
        
        // Schedule next batch if there are more items
        $remaining_items = $this->database->get_job_items($job_id, 1, 0, 'pending');
        if (!empty($remaining_items)) {
            if ($this->is_action_scheduler_available()) {
                as_schedule_single_action(
                    time() + 5, // 5 second delay between batches
                    'afi_process_job_items',
                    array($job_id, $batch_number + 1),
                    $this->scheduler_group
                );
            } else {
                // Fallback to WordPress cron
                wp_schedule_single_event(
                    time() + 5,
                    'afi_process_job_items',
                    array($job_id, $batch_number + 1)
                );
            }
        } else {
            // All items processed, mark job as complete
            $this->database->update_job($job_id, array(
                'status' => 'complete',
                'finished_at' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Process a single job item
     *
     * @param object $item Job item data
     */
    private function process_single_item($item) {
        // Use the new processor class for optimized processing
        $result = $this->processor->process_job_item($item->id);
        
        // Log any errors for debugging
        if (!$result['success'] && function_exists('error_log')) {
            error_log("AFI Processing Error for item {$item->id}: " . $result['error']);
        }
    }
    

    
    /**
     * Schedule a scan batch using Action Scheduler or WordPress cron
     *
     * @param int   $job_id Job ID
     * @param array $post_types Post types to scan
     * @param int   $page Page number
     */
    private function schedule_scan_batch($job_id, $post_types, $page) {
        if ($this->is_action_scheduler_available()) {
            as_schedule_single_action(
                time() + 1, // 1 second delay
                'afi_scan_posts_batch',
                array($job_id, $post_types, $page),
                $this->scheduler_group
            );
        } else {
            // Fallback to WordPress cron
            wp_schedule_single_event(
                time() + 1,
                'afi_scan_posts_batch',
                array($job_id, $post_types, $page)
            );
        }
    }
    
    /**
     * Schedule processing batches using Action Scheduler or WordPress cron
     *
     * @param int $job_id Job ID
     */
    private function schedule_processing_batches($job_id) {
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: schedule_processing_batches called for job_id: ' . $job_id);
        }
        
        // For immediate processing mode, call directly
        if (defined('AFI_IMMEDIATE_PROCESSING') && AFI_IMMEDIATE_PROCESSING) {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Using immediate processing mode for job items');
            }
            
            // Process job items immediately
            $this->process_job_items_batch($job_id, 1);
            return;
        }
        
        // Schedule the first processing batch
        if ($this->is_action_scheduler_available()) {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Using Action Scheduler for processing');
            }
            
            as_schedule_single_action(
                time() + 1, // 1 second delay
                'afi_process_job_items',
                array($job_id, 1),
                $this->scheduler_group
            );
        } else {
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Using WordPress cron for processing');
            }
            
            // Fallback to WordPress cron
            wp_schedule_single_event(
                time() + 1,
                'afi_process_job_items',
                array($job_id, 1)
            );
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Processing batch scheduled successfully');
        }
    }
    
    /**
     * Resume scanning for a paused job
     *
     * @param int $job_id Job ID
     */
    private function resume_scanning($job_id) {
        $resume_info = $this->scanner->resume_scan($job_id);
        
        if (!$resume_info['success']) {
            if (function_exists('error_log')) {
                error_log("AFI Scanner Resume Error: " . $resume_info['error']);
            }
            return;
        }
        
        $job = $this->database->get_job($job_id);
        if ($job) {
            $this->schedule_scan_batch($job_id, $job->post_types, $resume_info['next_page']);
        }
    }
    
    /**
     * Cancel all scheduled actions for a job
     *
     * @param int $job_id Job ID
     */
    private function cancel_scheduled_actions($job_id) {
        if ($this->is_action_scheduler_available()) {
            // Cancel scan actions
            as_unschedule_all_actions('afi_scan_posts_batch', array($job_id), $this->scheduler_group);
            
            // Cancel processing actions
            as_unschedule_all_actions('afi_process_job_items', array($job_id), $this->scheduler_group);
        } else {
            // For WordPress cron, we need to clear scheduled events
            // Note: WordPress cron doesn't have a direct way to cancel by arguments,
            // so we'll just let them run and check job status in the callbacks
            wp_clear_scheduled_hook('afi_scan_posts_batch');
            wp_clear_scheduled_hook('afi_process_job_items');
        }
    }
    
    /**
     * Cancel all running jobs
     */
    public function cancel_all_jobs() {
        // Get all active jobs
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        
        $active_jobs = $wpdb->get_results(
            "SELECT id FROM {$jobs_table} 
             WHERE status IN ('scanning', 'running', 'paused')"
        );
        
        foreach ($active_jobs as $job) {
            $this->cancel_job($job->id);
        }
        
        return count($active_jobs);
    }
    
    /**
     * Cleanup old jobs (Action Scheduler callback)
     *
     * @param int $days Number of days to keep (default 90)
     */
    public function cleanup_old_jobs($days = 90) {
        $deleted_count = $this->database->cleanup_old_jobs($days);
        
        // Log cleanup activity
        if (function_exists('error_log')) {
            error_log("AFI: Cleaned up {$deleted_count} old jobs older than {$days} days");
        }
        
        return $deleted_count;
    }
    

    
    /**
     * Check if Action Scheduler is available
     *
     * @return bool True if available, false otherwise
     */
    public function is_action_scheduler_available() {
        return function_exists('as_schedule_single_action');
    }
    
    /**
     * Get scheduler group name
     *
     * @return string Scheduler group name
     */
    public function get_scheduler_group() {
        return $this->scheduler_group;
    }
    
    /**
     * Set scanner batch size
     *
     * @param int $size Batch size
     */
    public function set_scanner_batch_size($size) {
        $this->scanner_batch_size = max(100, min(2000, absint($size)));
        // Also update the scanner's batch size
        $this->scanner->set_batch_size($this->scanner_batch_size);
    }
    
    /**
     * Set processor batch size
     *
     * @param int $size Batch size
     */
    public function set_processor_batch_size($size) {
        $this->processor_batch_size = max(10, min(200, absint($size)));
    }
    
    /**
     * Get scanner batch size
     *
     * @return int Batch size
     */
    public function get_scanner_batch_size() {
        return $this->scanner_batch_size;
    }
    
    /**
     * Get processor batch size
     *
     * @return int Batch size
     */
    public function get_processor_batch_size() {
        return $this->processor_batch_size;
    }
    
    /**
     * Get scan progress for a job
     *
     * @param int $job_id Job ID
     * @return array Progress information
     */
    public function get_scan_progress($job_id) {
        return $this->scanner->get_scan_progress($job_id);
    }
    
    /**
     * Count posts without featured images
     *
     * @param array $post_types Array of post types to count
     * @return int Total count
     */
    public function count_posts_without_featured_image($post_types) {
        return $this->scanner->count_posts_without_featured_image($post_types);
    }
    
    /**
     * Check if scan is complete for a job
     *
     * @param int $job_id Job ID
     * @return bool True if complete, false otherwise
     */
    public function is_scan_complete($job_id) {
        return $this->scanner->is_scan_complete($job_id);
    }
    
    /**
     * Get scanner statistics
     *
     * @return array Scanner statistics
     */
    public function get_scanner_statistics() {
        return $this->scanner->get_statistics();
    }
    
    /**
     * Optimize scanner batch size based on server resources
     *
     * @return int Optimized batch size
     */
    public function optimize_scanner_batch_size() {
        $optimized_size = $this->scanner->optimize_batch_size();
        $this->scanner_batch_size = $optimized_size;
        return $optimized_size;
    }
    
    /**
     * Delete a job and all its data
     *
     * @param int $job_id Job ID
     * @return bool True on success, false on failure
     */
    public function delete_job($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job) {
            return false;
        }
        
        // Cancel any scheduled actions first
        $this->cancel_scheduled_actions($job_id);
        
        // Delete the job and its items from database
        return $this->database->delete_job($job_id);
    }
    
    /**
     * Get jobs with pagination (override for better compatibility)
     *
     * @param int $limit Number of jobs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of jobs
     */
    public function get_jobs($limit = 20, $offset = 0) {
        return $this->database->get_jobs($limit, $offset);
    }
}