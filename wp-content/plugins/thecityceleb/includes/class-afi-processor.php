<?php
/**
 * Processor class for Auto Featured Image
 *
 * Handles efficient image assignment processing with optimized random selection,
 * batch processing, and comprehensive error handling.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Processor class with efficient random image selection
 */
class AFI_Processor {
    
    /**
     * Database instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Image service instance
     *
     * @var AFI_Image_Service
     */
    private $image_service;
    
    /**
     * Batch size for image fetching
     *
     * @var int
     */
    private $image_batch_size = 100;
    
    /**
     * Maximum retry attempts for failed assignments
     *
     * @var int
     */
    private $max_retry_attempts = 3;
    
    /**
     * Image batch cache
     *
     * @var array
     */
    private $image_batch_cache = array();
    
    /**
     * Cache expiration time for image batches (5 minutes)
     *
     * @var int
     */
    private $batch_cache_expiration = 300;
    
    /**
     * Constructor
     *
     * @param AFI_Database     $database      Database instance
     * @param AFI_Image_Service $image_service Image service instance
     */
    public function __construct($database = null, $image_service = null) {
        $this->database = $database ?: new AFI_Database();
        $this->image_service = $image_service ?: new AFI_Image_Service();
    }
    
    /**
     * Process a single job item
     *
     * @param int $item_id Job item ID
     * @return array Processing result with success status and details
     */
    public function process_job_item($item_id) {
        // Get job item
        $item = $this->database->get_job_item($item_id);
        
        if (!$item) {
            return array(
                'success' => false,
                'error' => 'Job item not found',
                'item_id' => $item_id
            );
        }
        
        // Get job details
        $job = $this->database->get_job($item->job_id);
        
        if (!$job) {
            return array(
                'success' => false,
                'error' => 'Job not found',
                'item_id' => $item_id,
                'job_id' => $item->job_id
            );
        }
        
        // Create job item model for state management
        $item_model = new AFI_Job_Item_Model((array) $item);
        
        // Mark item as processing
        $item_model->mark_processing();
        $this->database->update_job_item($item_id, $item_model->to_array());
        
        try {
            // Validate post still exists
            $post = get_post($item->post_id);
            if (!$post) {
                $item_model->mark_failed('Post no longer exists');
                $this->database->update_job_item($item_id, $item_model->to_array());
                
                return array(
                    'success' => false,
                    'error' => 'Post no longer exists',
                    'item_id' => $item_id,
                    'post_id' => $item->post_id
                );
            }
            
            // Check if post already has featured image
            if (has_post_thumbnail($item->post_id)) {
                $item_model->mark_skipped('Post already has featured image');
                $this->database->update_job_item($item_id, $item_model->to_array());
                
                return array(
                    'success' => true,
                    'skipped' => true,
                    'reason' => 'Post already has featured image',
                    'item_id' => $item_id,
                    'post_id' => $item->post_id
                );
            }
            
            // Assign random image
            $assignment_result = $this->assign_random_image($item->post_id, $job->image_filters);
            
            if ($assignment_result['success']) {
                $item_model->mark_complete(
                    $assignment_result['image_id'],
                    'Featured image assigned successfully'
                );
                
                $result = array(
                    'success' => true,
                    'item_id' => $item_id,
                    'post_id' => $item->post_id,
                    'image_id' => $assignment_result['image_id'],
                    'message' => 'Featured image assigned successfully'
                );
            } else {
                $item_model->mark_failed($assignment_result['error']);
                
                $result = array(
                    'success' => false,
                    'error' => $assignment_result['error'],
                    'item_id' => $item_id,
                    'post_id' => $item->post_id
                );
            }
            
        } catch (Exception $e) {
            $error_message = 'Processing error: ' . $e->getMessage();
            $item_model->mark_failed($error_message);
            
            $result = array(
                'success' => false,
                'error' => $error_message,
                'item_id' => $item_id,
                'post_id' => $item->post_id ?? null
            );
        }
        
        // Update job item in database
        $this->database->update_job_item($item_id, $item_model->to_array());
        
        return $result;
    }
    
    /**
     * Assign random image to a post with efficient selection
     *
     * @param int   $post_id Post ID
     * @param array $image_filters Image filters to apply
     * @return array Assignment result with success status and details
     */
    public function assign_random_image($post_id, $image_filters = array()) {
        // Validate post
        if (!$post_id || !get_post($post_id)) {
            return array(
                'success' => false,
                'error' => 'Invalid post ID'
            );
        }
        
        // Get random image using efficient method
        $image_id = $this->get_random_image_efficient($image_filters);
        
        if (!$image_id) {
            return array(
                'success' => false,
                'error' => 'No suitable images found matching the filters'
            );
        }
        
        // Validate image before assignment
        if (!$this->image_service->validate_image($image_id)) {
            return array(
                'success' => false,
                'error' => 'Selected image is not valid or accessible'
            );
        }
        
        // Attempt to assign featured image with retry logic
        $assignment_success = false;
        $attempts = 0;
        $last_error = '';
        
        while (!$assignment_success && $attempts < $this->max_retry_attempts) {
            $attempts++;
            
            try {
                $assignment_success = set_post_thumbnail($post_id, $image_id);
                
                if (!$assignment_success) {
                    $last_error = 'WordPress set_post_thumbnail() returned false';
                    
                    // Try with a different image on retry
                    if ($attempts < $this->max_retry_attempts) {
                        $image_id = $this->get_random_image_efficient($image_filters);
                        if (!$image_id) {
                            break; // No more images available
                        }
                    }
                }
                
            } catch (Exception $e) {
                $last_error = 'Exception during assignment: ' . $e->getMessage();
                
                // Try with a different image on retry
                if ($attempts < $this->max_retry_attempts) {
                    $image_id = $this->get_random_image_efficient($image_filters);
                    if (!$image_id) {
                        break; // No more images available
                    }
                }
            }
        }
        
        if ($assignment_success) {
            return array(
                'success' => true,
                'image_id' => $image_id,
                'attempts' => $attempts,
                'message' => 'Featured image assigned successfully'
            );
        } else {
            return array(
                'success' => false,
                'error' => "Failed to assign featured image after {$attempts} attempts. Last error: {$last_error}",
                'attempts' => $attempts
            );
        }
    }
    
    /**
     * Get random image efficiently using count-and-offset method
     *
     * @param array $filters Image filters
     * @return int|false Random image ID or false if none found
     */
    private function get_random_image_efficient($filters) {
        // Try to get from batch cache first
        $cached_image = $this->get_image_from_batch_cache($filters);
        if ($cached_image) {
            return $cached_image;
        }
        
        // Get total count of filtered images
        $total_count = $this->image_service->get_filtered_image_count($filters);
        
        // Debug logging
        if (function_exists('error_log')) {
            error_log("AFI Debug: get_random_image_efficient - total_count: {$total_count}, filters: " . json_encode($filters));
        }
        
        if ($total_count === 0) {
            if (function_exists('error_log')) {
                error_log("AFI Debug: No images found with filters: " . json_encode($filters));
            }
            return false;
        }
        
        // Use count-and-offset method for efficient random selection
        $random_offset = rand(0, $total_count - 1);
        
        // Get single image at random offset
        $images = $this->image_service->get_filtered_images($filters, 1, $random_offset);
        
        if (empty($images)) {
            return false;
        }
        
        $image_id = $images[0];
        
        // Validate the selected image
        if (!$this->image_service->validate_image($image_id)) {
            // If invalid, try a different offset (up to 3 attempts)
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $random_offset = rand(0, $total_count - 1);
                $images = $this->image_service->get_filtered_images($filters, 1, $random_offset);
                
                if (!empty($images) && $this->image_service->validate_image($images[0])) {
                    return $images[0];
                }
            }
            
            return false;
        }
        
        return $image_id;
    }
    
    /**
     * Get image batch for performance optimization
     *
     * @param array $filters Image filters
     * @param int   $count   Number of images to fetch
     * @return array Array of image IDs
     */
    public function get_image_batch($filters, $count = null) {
        $count = $count ?: $this->image_batch_size;
        
        // Check if we have a cached batch for these filters
        $cache_key = $this->get_batch_cache_key($filters);
        
        if (isset($this->image_batch_cache[$cache_key])) {
            $cached_batch = $this->image_batch_cache[$cache_key];
            
            // Check if cache is still valid
            if (time() - $cached_batch['timestamp'] < $this->batch_cache_expiration) {
                return $cached_batch['images'];
            } else {
                // Cache expired, remove it
                unset($this->image_batch_cache[$cache_key]);
            }
        }
        
        // Fetch new batch using efficient random selection
        $images = $this->image_service->get_random_images_efficient($filters, $count);
        
        // Validate all images in the batch
        $valid_images = array();
        foreach ($images as $image_id) {
            if ($this->image_service->validate_image($image_id)) {
                $valid_images[] = $image_id;
            }
        }
        
        // Cache the batch
        $this->image_batch_cache[$cache_key] = array(
            'images' => $valid_images,
            'timestamp' => time(),
            'used_count' => 0
        );
        
        return $valid_images;
    }
    
    /**
     * Get image from batch cache
     *
     * @param array $filters Image filters
     * @return int|false Image ID or false if none available
     */
    private function get_image_from_batch_cache($filters) {
        $cache_key = $this->get_batch_cache_key($filters);
        
        if (!isset($this->image_batch_cache[$cache_key])) {
            // No cache exists, create one
            $this->get_image_batch($filters);
        }
        
        if (isset($this->image_batch_cache[$cache_key])) {
            $cached_batch = &$this->image_batch_cache[$cache_key];
            
            // Check if cache is still valid
            if (time() - $cached_batch['timestamp'] >= $this->batch_cache_expiration) {
                unset($this->image_batch_cache[$cache_key]);
                return false;
            }
            
            // Get next unused image from batch
            if ($cached_batch['used_count'] < count($cached_batch['images'])) {
                $image_id = $cached_batch['images'][$cached_batch['used_count']];
                $cached_batch['used_count']++;
                
                return $image_id;
            } else {
                // Batch exhausted, remove from cache
                unset($this->image_batch_cache[$cache_key]);
            }
        }
        
        return false;
    }
    
    /**
     * Generate cache key for image batch
     *
     * @param array $filters Image filters
     * @return string Cache key
     */
    private function get_batch_cache_key($filters) {
        // Sort filters to ensure consistent cache keys
        if (is_array($filters)) {
            ksort($filters);
        }
        
        return 'batch_' . md5(serialize($filters));
    }
    
    /**
     * Clear image batch cache
     *
     * @param array $filters Optional specific filters to clear, or all if empty
     * @return bool True on success
     */
    public function clear_image_batch_cache($filters = array()) {
        if (empty($filters)) {
            // Clear all cache
            $this->image_batch_cache = array();
        } else {
            // Clear specific cache entry
            $cache_key = $this->get_batch_cache_key($filters);
            unset($this->image_batch_cache[$cache_key]);
        }
        
        return true;
    }
    
    /**
     * Process multiple job items in batch
     *
     * @param array $item_ids Array of job item IDs
     * @return array Batch processing results
     */
    public function process_job_items_batch($item_ids) {
        if (empty($item_ids) || !is_array($item_ids)) {
            return array(
                'success' => false,
                'error' => 'No job items provided',
                'processed' => 0,
                'results' => array()
            );
        }
        
        $results = array();
        $processed_count = 0;
        $success_count = 0;
        $error_count = 0;
        $skipped_count = 0;
        
        foreach ($item_ids as $item_id) {
            $result = $this->process_job_item($item_id);
            $results[] = $result;
            $processed_count++;
            
            if ($result['success']) {
                if (isset($result['skipped']) && $result['skipped']) {
                    $skipped_count++;
                } else {
                    $success_count++;
                }
            } else {
                $error_count++;
            }
        }
        
        return array(
            'success' => true,
            'processed' => $processed_count,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'skipped_count' => $skipped_count,
            'results' => $results
        );
    }
    
    /**
     * Get processor statistics
     *
     * @return array Processor statistics
     */
    public function get_statistics() {
        return array(
            'image_batch_size' => $this->image_batch_size,
            'max_retry_attempts' => $this->max_retry_attempts,
            'batch_cache_expiration' => $this->batch_cache_expiration,
            'cached_batches' => count($this->image_batch_cache),
            'cache_memory_usage' => $this->calculate_cache_memory_usage()
        );
    }
    
    /**
     * Calculate memory usage of batch cache
     *
     * @return int Memory usage in bytes
     */
    private function calculate_cache_memory_usage() {
        return strlen(serialize($this->image_batch_cache));
    }
    
    /**
     * Set image batch size
     *
     * @param int $size Batch size (10-500)
     * @return bool True on success, false on failure
     */
    public function set_image_batch_size($size) {
        $size = absint($size);
        
        if ($size < 10 || $size > 500) {
            return false;
        }
        
        $this->image_batch_size = $size;
        return true;
    }
    
    /**
     * Set maximum retry attempts
     *
     * @param int $attempts Maximum retry attempts (1-10)
     * @return bool True on success, false on failure
     */
    public function set_max_retry_attempts($attempts) {
        $attempts = absint($attempts);
        
        if ($attempts < 1 || $attempts > 10) {
            return false;
        }
        
        $this->max_retry_attempts = $attempts;
        return true;
    }
    
    /**
     * Set batch cache expiration time
     *
     * @param int $seconds Cache expiration in seconds (60-3600)
     * @return bool True on success, false on failure
     */
    public function set_batch_cache_expiration($seconds) {
        $seconds = absint($seconds);
        
        if ($seconds < 60 || $seconds > 3600) {
            return false;
        }
        
        $this->batch_cache_expiration = $seconds;
        return true;
    }
    
    /**
     * Get image batch size
     *
     * @return int Current batch size
     */
    public function get_image_batch_size() {
        return $this->image_batch_size;
    }
    
    /**
     * Get maximum retry attempts
     *
     * @return int Current max retry attempts
     */
    public function get_max_retry_attempts() {
        return $this->max_retry_attempts;
    }
    
    /**
     * Get batch cache expiration time
     *
     * @return int Current cache expiration in seconds
     */
    public function get_batch_cache_expiration() {
        return $this->batch_cache_expiration;
    }
    
    /**
     * Validate processor configuration
     *
     * @return array Validation results
     */
    public function validate_configuration() {
        $issues = array();
        
        // Check if image service is available
        if (!$this->image_service) {
            $issues[] = 'Image service not available';
        }
        
        // Check if database is available
        if (!$this->database) {
            $issues[] = 'Database service not available';
        }
        
        // Check batch size
        if ($this->image_batch_size < 10 || $this->image_batch_size > 500) {
            $issues[] = 'Image batch size out of valid range (10-500)';
        }
        
        // Check retry attempts
        if ($this->max_retry_attempts < 1 || $this->max_retry_attempts > 10) {
            $issues[] = 'Max retry attempts out of valid range (1-10)';
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues
        );
    }
}