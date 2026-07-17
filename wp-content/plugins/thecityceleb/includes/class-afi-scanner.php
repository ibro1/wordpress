<?php
/**
 * Scanner service class for Auto Featured Image
 *
 * Handles efficient post scanning with batch processing capabilities,
 * progress tracking, and real-time status updates.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * High-performance scanner service class
 */
class AFI_Scanner {
    
    /**
     * Database instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Default batch size for scanning posts
     *
     * @var int
     */
    private $batch_size = 1000;
    
    /**
     * Maximum batch size allowed
     *
     * @var int
     */
    private $max_batch_size = 2000;
    
    /**
     * Minimum batch size allowed
     *
     * @var int
     */
    private $min_batch_size = 100;
    
    /**
     * Constructor
     *
     * @param AFI_Database $database Database instance
     */
    public function __construct($database = null) {
        $this->database = $database ?: new AFI_Database();
    }
    
    /**
     * Scan posts in batches to identify those without featured images
     *
     * @param int   $job_id Job ID
     * @param array $post_types Array of post types to scan
     * @param int   $page Current page number (1-based)
     * @return array Scan results with statistics
     */
    public function scan_posts_batch($job_id, $post_types, $page = 1) {
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner scan_posts_batch called with job_id: ' . $job_id . ', post_types: ' . json_encode($post_types) . ', page: ' . $page);
        }
        
        // Validate inputs
        if (!$job_id || empty($post_types) || !is_array($post_types)) {
            if (function_exists('error_log')) {
                error_log('AFI Debug: Scanner validation failed - invalid inputs');
            }
            return array(
                'success' => false,
                'error' => 'Invalid parameters provided',
                'posts_found' => 0,
                'items_created' => 0,
                'has_more' => false
            );
        }
        
        // Validate job exists and is in scanning status
        $job = $this->database->get_job($job_id);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner retrieved job: ' . ($job ? 'found, status: ' . $job->status : 'not found'));
        }
        
        if (!$job || $job->status !== 'scanning') {
            if (function_exists('error_log')) {
                error_log('AFI Debug: Scanner validation failed - job not found or wrong status');
            }
            return array(
                'success' => false,
                'error' => 'Job not found or not in scanning status',
                'posts_found' => 0,
                'items_created' => 0,
                'has_more' => false
            );
        }
        
        // Build query arguments for posts without featured images
        $query_args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $this->batch_size,
            'paged' => $page,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '0',
                    'compare' => '='
                )
            ),
            'fields' => 'ids',
            'no_found_rows' => false, // We need total count for pagination
            'update_post_meta_cache' => false, // Don't load meta cache
            'update_post_term_cache' => false  // Don't load term cache
        );
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner about to execute WP_Query with args: ' . json_encode($query_args));
        }
        
        // Execute query
        $query = new WP_Query($query_args);
        $post_ids = $query->posts;
        $posts_found = count($post_ids);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner WP_Query found ' . $posts_found . ' posts, max_num_pages: ' . $query->max_num_pages);
            error_log('AFI Debug: Scanner post_ids: ' . json_encode($post_ids));
        }
        
        // Create job items for posts without featured images
        $items_created = 0;
        $failed_items = 0;
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner about to process ' . count($post_ids) . ' posts');
        }
        
        foreach ($post_ids as $post_id) {
            // Double-check that post doesn't have featured image
            if ($this->has_featured_image($post_id)) {
                if (function_exists('error_log')) {
                    error_log('AFI Debug: Scanner skipping post ' . $post_id . ' - already has featured image');
                }
                continue;
            }
            
            $item_data = array(
                'job_id' => $job_id,
                'post_id' => $post_id,
                'status' => 'pending',
                'log_message' => 'Post identified as needing featured image'
            );
            
            if ($this->database->create_job_item($item_data)) {
                $items_created++;
                if (function_exists('error_log')) {
                    error_log('AFI Debug: Scanner created job item for post ' . $post_id);
                }
            } else {
                $failed_items++;
                if (function_exists('error_log')) {
                    error_log('AFI Debug: Scanner failed to create job item for post ' . $post_id);
                }
            }
        }
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log('AFI Debug: Scanner created ' . $items_created . ' job items, failed: ' . $failed_items);
        }
        
        // Update job progress
        $current_total = $job->total_items + $items_created;
        $this->database->update_job($job_id, array(
            'total_items' => $current_total
        ));
        
        // Determine if there are more pages to process
        $has_more = $query->max_num_pages > $page;
        
        return array(
            'success' => true,
            'posts_found' => $posts_found,
            'items_created' => $items_created,
            'failed_items' => $failed_items,
            'has_more' => $has_more,
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_posts_scanned' => ($page - 1) * $this->batch_size + $posts_found,
            'job_total_items' => $current_total
        );
    }
    
    /**
     * Count total posts without featured images for given post types
     *
     * @param array $post_types Array of post types to count
     * @return int Total count of posts without featured images
     */
    public function count_posts_without_featured_image($post_types) {
        if (empty($post_types) || !is_array($post_types)) {
            return 0;
        }
        
        $query_args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => '_thumbnail_id',
                    'value' => '0',
                    'compare' => '='
                )
            ),
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        $query = new WP_Query($query_args);
        
        // Filter out posts that actually have featured images
        $actual_count = 0;
        foreach ($query->posts as $post_id) {
            if (!$this->has_featured_image($post_id)) {
                $actual_count++;
            }
        }
        
        return $actual_count;
    }
    
    /**
     * Check if scanning is complete for a job
     *
     * @param int $job_id Job ID
     * @return bool True if scan is complete, false otherwise
     */
    public function is_scan_complete($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job) {
            return false;
        }
        
        // Scan is complete if status is not 'scanning'
        return $job->status !== 'scanning';
    }
    
    /**
     * Get scan progress for a job
     *
     * @param int $job_id Job ID
     * @return array Progress information
     */
    public function get_scan_progress($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job) {
            return array(
                'success' => false,
                'error' => 'Job not found'
            );
        }
        
        // Get estimated total posts for progress calculation
        $estimated_total = 0;
        if (!empty($job->post_types)) {
            $estimated_total = $this->estimate_total_posts($job->post_types);
        }
        
        // Calculate progress percentage
        $progress_percentage = 0;
        if ($estimated_total > 0) {
            $scanned_posts = $this->calculate_scanned_posts($job_id);
            $progress_percentage = min(100, ($scanned_posts / $estimated_total) * 100);
        }
        
        return array(
            'success' => true,
            'job_id' => $job_id,
            'status' => $job->status,
            'total_items_found' => $job->total_items,
            'estimated_total_posts' => $estimated_total,
            'progress_percentage' => round($progress_percentage, 2),
            'is_complete' => $this->is_scan_complete($job_id),
            'created_at' => $job->created_at
        );
    }
    
    /**
     * Resume scanning from where it left off
     *
     * @param int $job_id Job ID
     * @return array Resume information
     */
    public function resume_scan($job_id) {
        $job = $this->database->get_job($job_id);
        
        if (!$job) {
            return array(
                'success' => false,
                'error' => 'Job not found'
            );
        }
        
        if ($job->status !== 'paused') {
            return array(
                'success' => false,
                'error' => 'Job is not paused'
            );
        }
        
        // Calculate next page to resume from
        $scanned_posts = $this->calculate_scanned_posts($job_id);
        $next_page = floor($scanned_posts / $this->batch_size) + 1;
        
        return array(
            'success' => true,
            'next_page' => $next_page,
            'scanned_posts' => $scanned_posts,
            'total_items_found' => $job->total_items
        );
    }
    
    /**
     * Check if a post has a featured image
     *
     * @param int $post_id Post ID
     * @return bool True if post has featured image, false otherwise
     */
    private function has_featured_image($post_id) {
        $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
        
        // Check if thumbnail ID exists and is valid
        if (empty($thumbnail_id) || $thumbnail_id === '0') {
            return false;
        }
        
        // Verify the attachment actually exists
        $attachment = get_post($thumbnail_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        // Verify it's an image
        if (!wp_attachment_is_image($thumbnail_id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Estimate total posts for given post types
     *
     * @param array $post_types Array of post types
     * @return int Estimated total posts
     */
    private function estimate_total_posts($post_types) {
        $total = 0;
        
        foreach ($post_types as $post_type) {
            $count = wp_count_posts($post_type);
            if (isset($count->publish)) {
                $total += $count->publish;
            }
        }
        
        return $total;
    }
    
    /**
     * Calculate how many posts have been scanned for a job
     *
     * @param int $job_id Job ID
     * @return int Number of posts scanned
     */
    private function calculate_scanned_posts($job_id) {
        // This is an approximation based on items found
        // In a real implementation, we might track this more precisely
        $job = $this->database->get_job($job_id);
        
        if (!$job) {
            return 0;
        }
        
        // Estimate based on items found and typical ratio
        // This is a simplified calculation
        return $job->total_items * 2; // Assuming ~50% of posts need featured images
    }
    
    /**
     * Set batch size for scanning
     *
     * @param int $size Batch size
     * @return bool True on success, false on failure
     */
    public function set_batch_size($size) {
        $size = absint($size);
        
        if ($size < $this->min_batch_size || $size > $this->max_batch_size) {
            return false;
        }
        
        $this->batch_size = $size;
        return true;
    }
    
    /**
     * Get current batch size
     *
     * @return int Current batch size
     */
    public function get_batch_size() {
        return $this->batch_size;
    }
    
    /**
     * Get batch size limits
     *
     * @return array Array with min and max batch sizes
     */
    public function get_batch_size_limits() {
        return array(
            'min' => $this->min_batch_size,
            'max' => $this->max_batch_size,
            'current' => $this->batch_size
        );
    }
    
    /**
     * Optimize batch size based on server resources
     *
     * @return int Optimized batch size
     */
    public function optimize_batch_size() {
        // Get available memory
        $memory_limit = $this->get_memory_limit();
        $available_memory = $memory_limit - memory_get_usage(true);
        
        // Estimate memory per post (rough calculation)
        $memory_per_post = 1024; // 1KB per post ID
        
        // Calculate optimal batch size
        $optimal_size = floor($available_memory / $memory_per_post * 0.5); // Use 50% of available memory
        
        // Ensure it's within limits
        $optimal_size = max($this->min_batch_size, min($this->max_batch_size, $optimal_size));
        
        $this->batch_size = $optimal_size;
        
        return $optimal_size;
    }
    
    /**
     * Get memory limit in bytes
     *
     * @return int Memory limit in bytes
     */
    private function get_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        
        if ($memory_limit == -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
    
    /**
     * Get scanner statistics
     *
     * @return array Scanner statistics
     */
    public function get_statistics() {
        return array(
            'batch_size' => $this->batch_size,
            'batch_size_limits' => $this->get_batch_size_limits(),
            'memory_limit' => $this->get_memory_limit(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        );
    }
}