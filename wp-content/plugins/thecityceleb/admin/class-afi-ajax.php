<?php
/**
 * AJAX handler class for Auto Featured Image
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler class that manages AJAX requests
 */
class AFI_Ajax {
    
    /**
     * Initialize AJAX hooks
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress AJAX hooks
     */
    private function init_hooks() {
        // Dashboard stats
        add_action('wp_ajax_afi_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        
        // Job creation form support
        add_action('wp_ajax_afi_get_post_type_counts', array($this, 'get_post_type_counts'));
        add_action('wp_ajax_afi_get_image_counts', array($this, 'get_image_counts'));
        add_action('wp_ajax_afi_validate_filters', array($this, 'validate_filters'));
        
        // Enhanced job history
        add_action('wp_ajax_afi_get_job_history', array($this, 'get_job_history'));
        add_action('wp_ajax_afi_get_job_history_stats', array($this, 'get_job_history_stats'));
        add_action('wp_ajax_afi_get_job_details', array($this, 'get_job_details'));
        add_action('wp_ajax_afi_cleanup_old_jobs', array($this, 'cleanup_old_jobs'));
        
        // Job progress monitoring and control
        add_action('wp_ajax_afi_get_job_progress', array($this, 'get_job_progress'));
        add_action('wp_ajax_afi_control_job', array($this, 'control_job'));
        add_action('wp_ajax_afi_get_scan_results', array($this, 'get_scan_results'));
        add_action('wp_ajax_afi_get_job_logs', array($this, 'get_job_logs'));
    }
    
    /**
     * Validate nonce for AJAX requests
     *
     * @param string $action Action name for nonce validation
     * @return bool True if valid, false otherwise
     */
    public function validate_nonce($action = 'afi_ajax_nonce') {
        return wp_verify_nonce($_POST['nonce'] ?? '', $action);
    }
    
    /**
     * Get dashboard statistics via AJAX
     */
    public function get_dashboard_stats() {
        // Start output buffering to catch any stray output
        ob_start();
        
        // Verify nonce
        if (!$this->validate_nonce()) {
            ob_end_clean();
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            // Load required classes
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            
            $job_manager = new AFI_Job_Manager();
            $database = new AFI_Database();
            
            // Get posts without featured images (all public post types)
            $public_post_types = get_post_types(array('public' => true), 'names');
            unset($public_post_types['attachment']);
            $posts_without_images = $job_manager->count_posts_without_featured_image(array_values($public_post_types));
            
            // Get available images count
            $available_images = $this->count_available_images();
            
            // Get active jobs count
            $active_jobs = $database->count_active_jobs();
            
            // Clean output buffer and send response
            ob_end_clean();
            wp_send_json_success(array(
                'posts_without_images' => number_format($posts_without_images),
                'available_images' => number_format($available_images),
                'active_jobs' => $active_jobs
            ));
            
        } catch (Exception $e) {
            ob_end_clean();
            wp_send_json_error(array('message' => __('Failed to load statistics.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get post type counts for job creation form
     */
    public function get_post_type_counts() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
            $job_manager = new AFI_Job_Manager();
            
            $post_types = get_post_types(array('public' => true), 'names');
            unset($post_types['attachment']);
            
            $counts = array();
            foreach ($post_types as $post_type) {
                $counts[$post_type] = $job_manager->count_posts_without_featured_image(array($post_type));
            }
            
            wp_send_json_success($counts);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load post type counts.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get image counts for job creation form
     */
    public function get_image_counts() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            // Get all images count
            $all_images = $this->count_available_images();
            
            // Get filtered images count if filters are provided
            $filtered_images = $all_images;
            $filters = array();
            
            // Parse filters from request
            if (!empty($_POST['date_start']) || !empty($_POST['date_end'])) {
                $filters['date_range'] = array(
                    'start' => sanitize_text_field($_POST['date_start']),
                    'end' => sanitize_text_field($_POST['date_end'])
                );
            }
            
            if (!empty($_POST['keyword'])) {
                $filters['keyword'] = sanitize_text_field($_POST['keyword']);
            }
            
            if (!empty($filters)) {
                $filtered_images = $this->count_filtered_images($filters);
            }
            
            wp_send_json_success(array(
                'all_images' => $all_images,
                'filtered_images' => $filtered_images
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load image counts.', 'auto-featured-image')));
        }
    }
    
    /**
     * Validate image filters via AJAX
     */
    public function validate_filters() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $errors = array();
        
        // Validate date range
        $date_start = sanitize_text_field($_POST['date_start'] ?? '');
        $date_end = sanitize_text_field($_POST['date_end'] ?? '');
        
        if (!empty($date_start) && !$this->validate_date($date_start)) {
            $errors[] = __('Invalid start date format.', 'auto-featured-image');
        }
        
        if (!empty($date_end) && !$this->validate_date($date_end)) {
            $errors[] = __('Invalid end date format.', 'auto-featured-image');
        }
        
        if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
            $errors[] = __('End date must be after start date.', 'auto-featured-image');
        }
        
        // Validate keyword
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        if (!empty($keyword)) {
            if (strlen($keyword) < 2) {
                $errors[] = __('Keyword must be at least 2 characters long.', 'auto-featured-image');
            }
            
            if (strlen($keyword) > 100) {
                $errors[] = __('Keyword must be less than 100 characters.', 'auto-featured-image');
            }
        }
        
        if (empty($errors)) {
            wp_send_json_success(array('message' => __('Filters are valid.', 'auto-featured-image')));
        } else {
            wp_send_json_error(array('errors' => $errors));
        }
    }
    
    /**
     * Get job history via AJAX with enhanced pagination and filtering
     */
    public function get_job_history() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            // Get pagination parameters
            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 20);
            $search = sanitize_text_field($_POST['search'] ?? '');
            $status_filter = sanitize_text_field($_POST['status'] ?? '');
            $sort_by = sanitize_text_field($_POST['sort_by'] ?? 'created_at');
            $sort_order = sanitize_text_field($_POST['sort_order'] ?? 'desc');
            
            // Validate parameters
            $per_page = min(max($per_page, 5), 100); // Between 5 and 100
            $page = max($page, 1);
            $sort_by = in_array($sort_by, ['id', 'status', 'created_at', 'finished_at']) ? $sort_by : 'created_at';
            $sort_order = in_array($sort_order, ['asc', 'desc']) ? $sort_order : 'desc';
            
            // Get jobs with enhanced filtering
            $jobs = $this->get_filtered_jobs($page, $per_page, $search, $status_filter, $sort_by, $sort_order);
            $total_jobs = $this->count_filtered_jobs($search, $status_filter);
            
            // Format jobs for display
            $formatted_jobs = array();
            foreach ($jobs as $job) {
                $duration = $this->calculate_job_duration($job);
                $progress_percentage = $job->total_items > 0 ? round(($job->processed_items / $job->total_items) * 100, 1) : 0;
                
                $formatted_jobs[] = array(
                    'id' => $job->id,
                    'status' => $job->status,
                    'post_types' => is_array($job->post_types) ? $job->post_types : json_decode($job->post_types, true),
                    'image_filters' => is_array($job->image_filters) ? $job->image_filters : json_decode($job->image_filters, true),
                    'total_items' => intval($job->total_items),
                    'processed_items' => intval($job->processed_items),
                    'progress_percentage' => $progress_percentage,
                    'created_at' => mysql2date('M j, Y g:i A', $job->created_at),
                    'finished_at' => $job->finished_at ? mysql2date('M j, Y g:i A', $job->finished_at) : null,
                    'duration' => $duration,
                    'created_timestamp' => strtotime($job->created_at),
                    'finished_timestamp' => $job->finished_at ? strtotime($job->finished_at) : null
                );
            }
            
            wp_send_json_success(array(
                'jobs' => $formatted_jobs,
                'pagination' => array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_jobs,
                    'total_pages' => ceil($total_jobs / $per_page),
                    'showing_start' => (($page - 1) * $per_page) + 1,
                    'showing_end' => min($page * $per_page, $total_jobs)
                ),
                'filters' => array(
                    'search' => $search,
                    'status' => $status_filter,
                    'sort_by' => $sort_by,
                    'sort_order' => $sort_order
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load job history.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get job history statistics
     */
    public function get_job_history_stats() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            global $wpdb;
            $jobs_table = $wpdb->prefix . 'afi_jobs';
            
            // Get job statistics
            $stats = $wpdb->get_row(
                "SELECT 
                    COUNT(*) as total_jobs,
                    SUM(CASE WHEN status IN ('scanning', 'running', 'paused') THEN 1 ELSE 0 END) as active_jobs,
                    SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed_jobs,
                    SUM(CASE WHEN status = 'complete' THEN processed_items ELSE 0 END) as total_processed
                 FROM {$jobs_table}"
            );
            
            wp_send_json_success(array(
                'total_jobs' => intval($stats->total_jobs),
                'active_jobs' => intval($stats->active_jobs),
                'completed_jobs' => intval($stats->completed_jobs),
                'total_processed' => intval($stats->total_processed)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load job statistics.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get detailed job information
     */
    public function get_job_details() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(array('message' => __('Invalid job ID.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            // Get job details
            $job = $database->get_job($job_id);
            if (!$job) {
                wp_send_json_error(array('message' => __('Job not found.', 'auto-featured-image')));
            }
            
            // Get job statistics
            $job_stats = $database->get_job_stats($job_id);
            
            // Calculate additional metrics
            $duration = $this->calculate_job_duration($job);
            $progress_percentage = $job->total_items > 0 ? round(($job->processed_items / $job->total_items) * 100, 1) : 0;
            $processing_rate = $this->calculate_processing_rate($job_id);
            $time_remaining = $this->estimate_time_remaining($job, $processing_rate);
            
            // Format image filters for display
            $filters_display = $this->format_image_filters($job->image_filters);
            
            wp_send_json_success(array(
                'job' => array(
                    'id' => $job->id,
                    'status' => $job->status,
                    'post_types' => $job->post_types,
                    'image_filters' => $job->image_filters,
                    'filters_display' => $filters_display,
                    'total_items' => intval($job->total_items),
                    'processed_items' => intval($job->processed_items),
                    'progress_percentage' => $progress_percentage,
                    'created_at' => mysql2date('M j, Y g:i A', $job->created_at),
                    'finished_at' => $job->finished_at ? mysql2date('M j, Y g:i A', $job->finished_at) : null,
                    'duration' => $duration,
                    'processing_rate' => $processing_rate,
                    'time_remaining' => $time_remaining
                ),
                'stats' => $job_stats
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load job details.', 'auto-featured-image')));
        }
    }
    
    /**
     * Cleanup old jobs
     */
    public function cleanup_old_jobs() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $days = intval($_POST['days'] ?? 90);
        $days = max($days, 1); // At least 1 day
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            $deleted_count = $database->cleanup_old_jobs($days);
            
            wp_send_json_success(array(
                'message' => sprintf(
                    _n(
                        'Deleted %d old job.',
                        'Deleted %d old jobs.',
                        $deleted_count,
                        'auto-featured-image'
                    ),
                    $deleted_count
                ),
                'deleted_count' => $deleted_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to cleanup old jobs.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get job progress via AJAX
     */
    public function get_job_progress() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) {
            wp_send_json_error(array('message' => __('Invalid job ID.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            
            $job_manager = new AFI_Job_Manager();
            $database = new AFI_Database();
            
            // Get job status and progress
            $job_status = $job_manager->get_job_status($job_id);
            if (!$job_status) {
                wp_send_json_error(array('message' => __('Job not found.', 'auto-featured-image')));
            }
            
            // Get recent log entries
            $logs = $database->get_job_logs($job_id, 10);
            
            // Calculate progress percentage
            $progress_percentage = 0;
            if ($job_status->total_items > 0) {
                $progress_percentage = round(($job_status->processed_items / $job_status->total_items) * 100, 1);
            }
            
            // Get processing rate (items per minute)
            $processing_rate = $this->calculate_processing_rate($job_id);
            
            // Estimate time remaining
            $time_remaining = $this->estimate_time_remaining($job_status, $processing_rate);
            
            wp_send_json_success(array(
                'job_id' => $job_id,
                'status' => $job_status->status,
                'total_items' => intval($job_status->total_items),
                'processed_items' => intval($job_status->processed_items),
                'progress_percentage' => $progress_percentage,
                'processing_rate' => $processing_rate,
                'time_remaining' => $time_remaining,
                'created_at' => mysql2date('M j, Y g:i A', $job_status->created_at),
                'finished_at' => $job_status->finished_at ? mysql2date('M j, Y g:i A', $job_status->finished_at) : null,
                'logs' => $logs,
                'last_updated' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to get job progress.', 'auto-featured-image')));
        }
    }
    
    /**
     * Control job via AJAX
     */
    public function control_job() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        $action = sanitize_text_field($_POST['job_action'] ?? '');
        
        if (!$job_id || !$action) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
            $job_manager = new AFI_Job_Manager();
            
            $result = false;
            $message = '';
            
            switch ($action) {
                case 'start':
                    $result = $job_manager->start_processing($job_id);
                    $message = __('Job started successfully.', 'auto-featured-image');
                    break;
                    
                case 'pause':
                    $result = $job_manager->pause_job($job_id);
                    $message = __('Job paused successfully.', 'auto-featured-image');
                    break;
                    
                case 'resume':
                    $result = $job_manager->resume_job($job_id);
                    $message = __('Job resumed successfully.', 'auto-featured-image');
                    break;
                    
                case 'cancel':
                    $result = $job_manager->cancel_job($job_id);
                    $message = __('Job canceled successfully.', 'auto-featured-image');
                    break;
                    
                case 'delete':
                    $result = $job_manager->delete_job($job_id);
                    $message = __('Job deleted successfully.', 'auto-featured-image');
                    break;
                    
                default:
                    wp_send_json_error(array('message' => __('Invalid action.', 'auto-featured-image')));
            }
            
            if ($result) {
                wp_send_json_success(array('message' => $message));
            } else {
                wp_send_json_error(array('message' => __('Action failed. Please try again.', 'auto-featured-image')));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => sprintf(__('Action failed: %s', 'auto-featured-image'), $e->getMessage())));
        }
    }
    
    /**
     * Get scan results via AJAX
     */
    public function get_scan_results() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        if (!$job_id) {
            wp_send_json_error(array('message' => __('Invalid job ID.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            // Get job items with pagination
            $offset = ($page - 1) * $per_page;
            $items = $database->get_job_items($job_id, $per_page, $offset, $search);
            $total_items = $database->count_job_items($job_id, $search);
            
            // Format items for display
            $formatted_items = array();
            foreach ($items as $item) {
                $post = get_post($item->post_id);
                $formatted_items[] = array(
                    'id' => $item->id,
                    'post_id' => $item->post_id,
                    'post_title' => $post ? $post->post_title : __('Post not found', 'auto-featured-image'),
                    'post_url' => $post ? get_edit_post_link($item->post_id) : '',
                    'status' => $item->status,
                    'assigned_image_id' => $item->assigned_image_id,
                    'assigned_image_url' => $item->assigned_image_id ? wp_get_attachment_image_url($item->assigned_image_id, 'thumbnail') : '',
                    'log_message' => $item->log_message,
                    'processed_at' => $item->processed_at ? mysql2date('M j, Y g:i A', $item->processed_at) : null
                );
            }
            
            wp_send_json_success(array(
                'items' => $formatted_items,
                'total_items' => $total_items,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to get scan results.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get job logs via AJAX
     */
    public function get_job_logs() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $job_id = intval($_POST['job_id'] ?? 0);
        $limit = intval($_POST['limit'] ?? 50);
        $offset = intval($_POST['offset'] ?? 0);
        
        if (!$job_id) {
            wp_send_json_error(array('message' => __('Invalid job ID.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            
            $logs = $database->get_job_logs($job_id, $limit, $offset);
            
            wp_send_json_success(array(
                'logs' => $logs,
                'has_more' => count($logs) === $limit
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to get job logs.', 'auto-featured-image')));
        }
    }
    
    /**
     * Calculate processing rate (items per minute)
     *
     * @param int $job_id Job ID
     * @return float Processing rate
     */
    private function calculate_processing_rate($job_id) {
        global $wpdb;
        
        // Get processing data from the last 10 minutes
        $recent_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}afi_job_items 
             WHERE job_id = %d 
             AND status = 'complete' 
             AND processed_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
            $job_id
        ));
        
        // Calculate rate (items per minute)
        return $recent_items > 0 ? round($recent_items / 10, 1) : 0;
    }
    
    /**
     * Estimate time remaining for job completion
     *
     * @param object $job_status Job status object
     * @param float $processing_rate Processing rate (items per minute)
     * @return string Formatted time remaining
     */
    private function estimate_time_remaining($job_status, $processing_rate) {
        if ($job_status->status === 'complete' || $job_status->status === 'canceled') {
            return __('N/A', 'auto-featured-image');
        }
        
        if ($processing_rate <= 0) {
            return __('Calculating...', 'auto-featured-image');
        }
        
        $remaining_items = $job_status->total_items - $job_status->processed_items;
        if ($remaining_items <= 0) {
            return __('Almost done', 'auto-featured-image');
        }
        
        $minutes_remaining = ceil($remaining_items / $processing_rate);
        
        if ($minutes_remaining < 1) {
            return __('Less than 1 minute', 'auto-featured-image');
        } elseif ($minutes_remaining < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes_remaining, 'auto-featured-image'), $minutes_remaining);
        } else {
            $hours = floor($minutes_remaining / 60);
            $mins = $minutes_remaining % 60;
            if ($mins > 0) {
                return sprintf(__('%d hours %d minutes', 'auto-featured-image'), $hours, $mins);
            } else {
                return sprintf(_n('%d hour', '%d hours', $hours, 'auto-featured-image'), $hours);
            }
        }
    }
    
    /**
     * Count available images in media library
     *
     * @return int Number of available images
     */
    private function count_available_images() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type LIKE 'image/%' 
             AND post_status = 'inherit'"
        );
        
        return (int) $count;
    }
    
    /**
     * Count filtered images based on criteria
     *
     * @param array $filters Filter criteria
     * @return int Number of filtered images
     */
    private function count_filtered_images($filters) {
        global $wpdb;
        
        $where_clauses = array(
            "post_type = 'attachment'",
            "post_mime_type LIKE 'image/%'",
            "post_status = 'inherit'"
        );
        
        // Add date range filter
        if (!empty($filters['date_range'])) {
            if (!empty($filters['date_range']['start'])) {
                $where_clauses[] = $wpdb->prepare("post_date >= %s", $filters['date_range']['start'] . ' 00:00:00');
            }
            
            if (!empty($filters['date_range']['end'])) {
                $where_clauses[] = $wpdb->prepare("post_date <= %s", $filters['date_range']['end'] . ' 23:59:59');
            }
        }
        
        // Add keyword filter
        if (!empty($filters['keyword'])) {
            $keyword = '%' . $wpdb->esc_like($filters['keyword']) . '%';
            $where_clauses[] = $wpdb->prepare(
                "(post_title LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s)",
                $keyword, $keyword, $keyword
            );
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where_sql}");
        
        return (int) $count;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date Date string to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Get filtered jobs with pagination and sorting
     *
     * @param int $page Page number
     * @param int $per_page Items per page
     * @param string $search Search term
     * @param string $status_filter Status filter
     * @param string $sort_by Sort column
     * @param string $sort_order Sort order
     * @return array Jobs array
     */
    private function get_filtered_jobs($page, $per_page, $search, $status_filter, $sort_by, $sort_order) {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        
        $where_clauses = array('1=1');
        $params = array();
        
        // Add search filter
        if (!empty($search)) {
            $where_clauses[] = "(id LIKE %s OR post_types LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $where_clauses[] = "status = %s";
            $params[] = $status_filter;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Calculate offset
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $sql = "SELECT * FROM {$jobs_table} WHERE {$where_sql} ORDER BY {$sort_by} {$sort_order} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql);
        
        // Decode JSON fields for each job
        foreach ($results as $job) {
            $job->post_types = json_decode($job->post_types, true);
            $job->image_filters = json_decode($job->image_filters, true);
        }
        
        return $results;
    }
    
    /**
     * Count filtered jobs
     *
     * @param string $search Search term
     * @param string $status_filter Status filter
     * @return int Job count
     */
    private function count_filtered_jobs($search, $status_filter) {
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        
        $where_clauses = array('1=1');
        $params = array();
        
        // Add search filter
        if (!empty($search)) {
            $where_clauses[] = "(id LIKE %s OR post_types LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $where_clauses[] = "status = %s";
            $params[] = $status_filter;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT COUNT(*) FROM {$jobs_table} WHERE {$where_sql}";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Calculate job duration
     *
     * @param object $job Job object
     * @return string Formatted duration
     */
    private function calculate_job_duration($job) {
        if (!$job->finished_at) {
            if (in_array($job->status, ['scanning', 'running'])) {
                $start_time = strtotime($job->created_at);
                $current_time = current_time('timestamp');
                $duration_seconds = $current_time - $start_time;
                return $this->format_duration($duration_seconds);
            } else {
                return __('N/A', 'auto-featured-image');
            }
        }
        
        $start_time = strtotime($job->created_at);
        $end_time = strtotime($job->finished_at);
        $duration_seconds = $end_time - $start_time;
        
        return $this->format_duration($duration_seconds);
    }
    
    /**
     * Format duration in seconds to human readable format
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function format_duration($seconds) {
        if ($seconds < 60) {
            return sprintf(_n('%d second', '%d seconds', $seconds, 'auto-featured-image'), $seconds);
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remaining_seconds = $seconds % 60;
            if ($remaining_seconds > 0) {
                return sprintf(__('%d min %d sec', 'auto-featured-image'), $minutes, $remaining_seconds);
            } else {
                return sprintf(_n('%d minute', '%d minutes', $minutes, 'auto-featured-image'), $minutes);
            }
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $remaining_minutes = floor(($seconds % 3600) / 60);
            if ($remaining_minutes > 0) {
                return sprintf(__('%d hr %d min', 'auto-featured-image'), $hours, $remaining_minutes);
            } else {
                return sprintf(_n('%d hour', '%d hours', $hours, 'auto-featured-image'), $hours);
            }
        } else {
            $days = floor($seconds / 86400);
            $remaining_hours = floor(($seconds % 86400) / 3600);
            if ($remaining_hours > 0) {
                return sprintf(__('%d day %d hr', 'auto-featured-image'), $days, $remaining_hours);
            } else {
                return sprintf(_n('%d day', '%d days', $days, 'auto-featured-image'), $days);
            }
        }
    }
    
    /**
     * Format image filters for display
     *
     * @param array $filters Image filters array
     * @return string Formatted filters display
     */
    private function format_image_filters($filters) {
        if (empty($filters)) {
            return __('All images', 'auto-featured-image');
        }
        
        $filter_parts = array();
        
        if (!empty($filters['date_range'])) {
            $date_range = $filters['date_range'];
            if (!empty($date_range['start']) && !empty($date_range['end'])) {
                $filter_parts[] = sprintf(
                    __('Date: %s to %s', 'auto-featured-image'),
                    mysql2date('M j, Y', $date_range['start'] . ' 00:00:00'),
                    mysql2date('M j, Y', $date_range['end'] . ' 23:59:59')
                );
            } elseif (!empty($date_range['start'])) {
                $filter_parts[] = sprintf(
                    __('Date: from %s', 'auto-featured-image'),
                    mysql2date('M j, Y', $date_range['start'] . ' 00:00:00')
                );
            } elseif (!empty($date_range['end'])) {
                $filter_parts[] = sprintf(
                    __('Date: until %s', 'auto-featured-image'),
                    mysql2date('M j, Y', $date_range['end'] . ' 23:59:59')
                );
            }
        }
        
        if (!empty($filters['keyword'])) {
            $filter_parts[] = sprintf(
                __('Keyword: "%s"', 'auto-featured-image'),
                esc_html($filters['keyword'])
            );
        }
        
        return !empty($filter_parts) ? implode(', ', $filter_parts) : __('All images', 'auto-featured-image');
    }
   
    /**
     * Get logs via AJAX with filtering and pagination
     */
    public function get_logs() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
            $logger = new AFI_Logger();
            
            // Get filter parameters
            $args = array(
                'level' => sanitize_text_field($_POST['level'] ?? ''),
                'job_id' => intval($_POST['job_id'] ?? 0),
                'post_id' => intval($_POST['post_id'] ?? 0),
                'user_id' => intval($_POST['user_id'] ?? 0),
                'search' => sanitize_text_field($_POST['search'] ?? ''),
                'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
                'limit' => min(intval($_POST['limit'] ?? 50), 100),
                'offset' => intval($_POST['offset'] ?? 0),
                'order' => sanitize_text_field($_POST['order'] ?? 'DESC')
            );
            
            // Remove empty values
            $args = array_filter($args, function($value) {
                return $value !== '' && $value !== 0;
            });
            
            // Get logs and count
            $logs = $logger->get_logs($args);
            $total_logs = $logger->count_logs($args);
            
            // Format logs for display
            $formatted_logs = array();
            foreach ($logs as $log) {
                $formatted_logs[] = array(
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'context' => $log->context,
                    'job_id' => $log->job_id,
                    'post_id' => $log->post_id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user_id ? get_userdata($log->user_id)->display_name : null,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'stack_trace' => $log->stack_trace,
                    'created_at' => mysql2date('M j, Y g:i:s A', $log->created_at),
                    'created_timestamp' => strtotime($log->created_at)
                );
            }
            
            wp_send_json_success(array(
                'logs' => $formatted_logs,
                'total_logs' => $total_logs,
                'pagination' => array(
                    'limit' => $args['limit'] ?? 50,
                    'offset' => $args['offset'] ?? 0,
                    'total_pages' => ceil($total_logs / ($args['limit'] ?? 50))
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load logs.', 'auto-featured-image')));
        }
    }
    
    /**
     * Clear logs via AJAX
     */
    public function clear_logs() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        $clear_type = sanitize_text_field($_POST['clear_type'] ?? 'all');
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
            $logger = new AFI_Logger();
            
            $deleted_count = 0;
            $message = '';
            
            switch ($clear_type) {
                case 'all':
                    $result = $logger->clear_all_logs();
                    $message = __('All logs cleared successfully.', 'auto-featured-image');
                    break;
                    
                case 'old':
                    $days = intval($_POST['days'] ?? 30);
                    $deleted_count = $logger->cleanup_old_logs($days);
                    $message = sprintf(
                        _n(
                            'Deleted %d old log entry.',
                            'Deleted %d old log entries.',
                            $deleted_count,
                            'auto-featured-image'
                        ),
                        $deleted_count
                    );
                    break;
                    
                case 'by_level':
                    $level = sanitize_text_field($_POST['level'] ?? '');
                    if (empty($level)) {
                        wp_send_json_error(array('message' => __('Log level is required.', 'auto-featured-image')));
                    }
                    
                    global $wpdb;
                    $logs_table = $wpdb->prefix . 'afi_logs';
                    $deleted_count = $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$logs_table} WHERE level = %s",
                        $level
                    ));
                    
                    $message = sprintf(
                        _n(
                            'Deleted %d %s log entry.',
                            'Deleted %d %s log entries.',
                            $deleted_count,
                            'auto-featured-image'
                        ),
                        $deleted_count,
                        strtoupper($level)
                    );
                    break;
                    
                default:
                    wp_send_json_error(array('message' => __('Invalid clear type.', 'auto-featured-image')));
            }
            
            wp_send_json_success(array(
                'message' => $message,
                'deleted_count' => $deleted_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to clear logs.', 'auto-featured-image')));
        }
    }
    
    /**
     * Export logs via AJAX
     */
    public function export_logs() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
            $logger = new AFI_Logger();
            
            // Get export parameters
            $args = array(
                'level' => sanitize_text_field($_POST['level'] ?? ''),
                'job_id' => intval($_POST['job_id'] ?? 0),
                'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
                'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
                'limit' => 0, // No limit for export
                'offset' => 0
            );
            
            // Remove empty values
            $args = array_filter($args, function($value) {
                return $value !== '' && $value !== 0;
            });
            
            // Generate CSV content
            $csv_content = $logger->export_logs_csv($args);
            
            // Generate filename
            $filename = 'afi-logs-' . date('Y-m-d-H-i-s') . '.csv';
            
            wp_send_json_success(array(
                'csv_content' => $csv_content,
                'filename' => $filename,
                'message' => __('Logs exported successfully.', 'auto-featured-image')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to export logs.', 'auto-featured-image')));
        }
    }
    
    /**
     * Get log statistics via AJAX
     */
    public function get_log_stats() {
        // Verify nonce
        if (!$this->validate_nonce()) {
            wp_send_json_error(array('message' => __('Security check failed.', 'auto-featured-image')));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'auto-featured-image')));
        }
        
        try {
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
            $logger = new AFI_Logger();
            
            $stats = $logger->get_log_stats();
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Failed to load log statistics.', 'auto-featured-image')));
        }
    }}
