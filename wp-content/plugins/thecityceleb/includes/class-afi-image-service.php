<?php
/**
 * Image service class for Auto Featured Image
 *
 * Handles media library interaction, image filtering logic, and caching
 * for optimized image selection and assignment.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image service class with filtering and caching capabilities
 */
class AFI_Image_Service {
    
    /**
     * Cache group for image counts
     *
     * @var string
     */
    private $cache_group = 'afi_image_counts';
    
    /**
     * Cache expiration time in seconds (1 hour)
     *
     * @var int
     */
    private $cache_expiration = 3600;
    
    /**
     * Default image query arguments
     *
     * @var array
     */
    private $default_query_args = array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'fields' => 'ids',
        'no_found_rows' => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false
    );
    
    /**
     * Get filtered images based on provided filters
     *
     * @param array $filters Image filters
     * @param int   $limit   Number of images to retrieve (default: -1 for all)
     * @param int   $offset  Offset for pagination (default: 0)
     * @return array Array of image IDs
     */
    public function get_filtered_images($filters = array(), $limit = -1, $offset = 0) {
        $query_args = $this->default_query_args;
        
        // Set limit and offset
        if ($limit > 0) {
            $query_args['posts_per_page'] = $limit;
            if ($offset > 0) {
                $query_args['offset'] = $offset;
            }
        } else {
            $query_args['posts_per_page'] = -1;
        }
        
        // Apply filters
        $query_args = $this->apply_filters($query_args, $filters);
        
        $query = new WP_Query($query_args);
        
        return $query->posts;
    }
    
    /**
     * Get count of filtered images
     *
     * @param array $filters Image filters
     * @param bool  $use_cache Whether to use cached results
     * @return int Count of images matching filters
     */
    public function get_filtered_image_count($filters = array(), $use_cache = true) {
        $cache_key = $this->get_cache_key($filters);
        
        // Try to get from cache first
        if ($use_cache) {
            $cached_count = wp_cache_get($cache_key, $this->cache_group);
            if ($cached_count !== false) {
                return (int) $cached_count;
            }
        }
        
        // Build query for counting
        $query_args = $this->default_query_args;
        $query_args['posts_per_page'] = -1;
        $query_args = $this->apply_filters($query_args, $filters);
        
        $query = new WP_Query($query_args);
        $count = $query->found_posts;
        
        // Cache the result
        if ($use_cache) {
            wp_cache_set($cache_key, $count, $this->cache_group, $this->cache_expiration);
        }
        
        return $count;
    }
    
    /**
     * Apply date range filter to query arguments
     *
     * @param array $query_args Current query arguments
     * @param array $date_range Date range filter with 'start' and 'end' keys
     * @return array Modified query arguments
     */
    public function apply_date_filter($query_args, $date_range) {
        if (empty($date_range) || !is_array($date_range)) {
            return $query_args;
        }
        
        $date_query = array();
        
        // Add start date filter
        if (!empty($date_range['start'])) {
            $start_date = sanitize_text_field($date_range['start']);
            if ($this->is_valid_date($start_date)) {
                $date_query['after'] = $start_date;
            }
        }
        
        // Add end date filter
        if (!empty($date_range['end'])) {
            $end_date = sanitize_text_field($date_range['end']);
            if ($this->is_valid_date($end_date)) {
                $date_query['before'] = $end_date;
            }
        }
        
        // Apply date query if we have valid dates
        if (!empty($date_query)) {
            $query_args['date_query'] = array($date_query);
        }
        
        return $query_args;
    }
    
    /**
     * Apply keyword filter to query arguments
     *
     * @param array  $query_args Current query arguments
     * @param string $keyword    Keyword to search for
     * @param array  $fields     Fields to search in (filename, title, alt, description)
     * @return array Modified query arguments
     */
    public function apply_keyword_filter($query_args, $keyword, $fields = array('filename', 'title', 'alt')) {
        if (empty($keyword)) {
            return $query_args;
        }
        
        $keyword = sanitize_text_field($keyword);
        $fields = array_intersect($fields, array('filename', 'title', 'alt', 'description'));
        
        if (empty($fields)) {
            $fields = array('filename', 'title', 'alt');
        }
        
        // Build meta query for keyword search
        $meta_query = array('relation' => 'OR');
        
        foreach ($fields as $field) {
            switch ($field) {
                case 'filename':
                    // Search in post_name (slug) which is based on filename
                    $query_args['s'] = $keyword;
                    break;
                    
                case 'title':
                    // Search in post_title
                    if (!isset($query_args['s'])) {
                        $query_args['s'] = $keyword;
                    }
                    break;
                    
                case 'alt':
                    // Search in _wp_attachment_image_alt meta
                    $meta_query[] = array(
                        'key' => '_wp_attachment_image_alt',
                        'value' => $keyword,
                        'compare' => 'LIKE'
                    );
                    break;
                    
                case 'description':
                    // Search in post_content (description)
                    if (!isset($query_args['s'])) {
                        $query_args['s'] = $keyword;
                    }
                    break;
            }
        }
        
        // Add meta query if we have alt text search
        if (count($meta_query) > 1) {
            if (isset($query_args['meta_query'])) {
                $query_args['meta_query'] = array(
                    'relation' => 'AND',
                    $query_args['meta_query'],
                    $meta_query
                );
            } else {
                $query_args['meta_query'] = $meta_query;
            }
        }
        
        return $query_args;
    }
    
    /**
     * Cache image count for given filters
     *
     * @param array $filters Image filters
     * @param int   $count   Count to cache
     * @return bool True on success, false on failure
     */
    public function cache_image_count($filters, $count) {
        $cache_key = $this->get_cache_key($filters);
        return wp_cache_set($cache_key, $count, $this->cache_group, $this->cache_expiration);
    }
    
    /**
     * Get cached image count for given filters
     *
     * @param array $filters Image filters
     * @return int|false Cached count or false if not found
     */
    public function get_cached_image_count($filters) {
        $cache_key = $this->get_cache_key($filters);
        return wp_cache_get($cache_key, $this->cache_group);
    }
    
    /**
     * Clear image count cache
     *
     * @param array $filters Optional specific filters to clear, or all if empty
     * @return bool True on success
     */
    public function clear_image_count_cache($filters = array()) {
        if (empty($filters)) {
            // Clear all cache in the group
            wp_cache_flush_group($this->cache_group);
        } else {
            // Clear specific cache entry
            $cache_key = $this->get_cache_key($filters);
            wp_cache_delete($cache_key, $this->cache_group);
        }
        
        return true;
    }
    
    /**
     * Get random image IDs efficiently using count-and-offset method
     *
     * @param array $filters Image filters
     * @param int   $count   Number of random images to get
     * @return array Array of random image IDs
     */
    public function get_random_images_efficient($filters = array(), $count = 1) {
        // Get total count of filtered images
        $total_count = $this->get_filtered_image_count($filters);
        
        if ($total_count === 0) {
            return array();
        }
        
        $random_images = array();
        $used_offsets = array();
        
        // Generate random offsets and fetch images
        for ($i = 0; $i < $count && $i < $total_count; $i++) {
            $max_attempts = 10; // Prevent infinite loops
            $attempts = 0;
            
            do {
                $random_offset = rand(0, $total_count - 1);
                $attempts++;
            } while (in_array($random_offset, $used_offsets) && $attempts < $max_attempts);
            
            // If we couldn't find a unique offset, use the current one anyway
            $used_offsets[] = $random_offset;
            
            // Get image at this offset
            $images = $this->get_filtered_images($filters, 1, $random_offset);
            
            if (!empty($images)) {
                $random_images[] = $images[0];
            }
        }
        
        return array_unique($random_images);
    }
    
    /**
     * Get a single random image efficiently
     *
     * @param array $filters Image filters
     * @return int|false Random image ID or false if none found
     */
    public function get_single_random_image($filters = array()) {
        $images = $this->get_random_images_efficient($filters, 1);
        return !empty($images) ? $images[0] : false;
    }
    
    /**
     * Validate image exists and is accessible
     *
     * @param int $image_id Image ID to validate
     * @return bool True if image is valid, false otherwise
     */
    public function validate_image($image_id) {
        if (!$image_id || !is_numeric($image_id)) {
            return false;
        }
        
        $attachment = get_post($image_id);
        
        // Check if attachment exists
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        // Check if it's an image
        if (!wp_attachment_is_image($image_id)) {
            return false;
        }
        
        // Check if file exists
        $file_path = get_attached_file($image_id);
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get image metadata for display purposes
     *
     * @param int $image_id Image ID
     * @return array|false Image metadata or false if not found
     */
    public function get_image_metadata($image_id) {
        if (!$this->validate_image($image_id)) {
            return false;
        }
        
        $attachment = get_post($image_id);
        $metadata = wp_get_attachment_metadata($image_id);
        
        return array(
            'id' => $image_id,
            'title' => $attachment->post_title,
            'filename' => basename(get_attached_file($image_id)),
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
            'description' => $attachment->post_content,
            'url' => wp_get_attachment_url($image_id),
            'width' => isset($metadata['width']) ? $metadata['width'] : 0,
            'height' => isset($metadata['height']) ? $metadata['height'] : 0,
            'file_size' => isset($metadata['filesize']) ? $metadata['filesize'] : filesize(get_attached_file($image_id)),
            'mime_type' => $attachment->post_mime_type,
            'upload_date' => $attachment->post_date
        );
    }
    
    /**
     * Apply all filters to query arguments
     *
     * @param array $query_args Current query arguments
     * @param array $filters    Filters to apply
     * @return array Modified query arguments
     */
    private function apply_filters($query_args, $filters) {
        if (empty($filters) || !is_array($filters)) {
            return $query_args;
        }
        
        // Apply date range filter
        if (!empty($filters['date_range'])) {
            $query_args = $this->apply_date_filter($query_args, $filters['date_range']);
        }
        
        // Apply keyword filter
        if (!empty($filters['keyword'])) {
            $keyword_fields = isset($filters['keyword_fields']) ? $filters['keyword_fields'] : array('filename', 'title', 'alt');
            $query_args = $this->apply_keyword_filter($query_args, $filters['keyword'], $keyword_fields);
        }
        
        // Apply custom meta filters if provided
        if (!empty($filters['meta_query'])) {
            if (isset($query_args['meta_query'])) {
                $query_args['meta_query'] = array(
                    'relation' => 'AND',
                    $query_args['meta_query'],
                    $filters['meta_query']
                );
            } else {
                $query_args['meta_query'] = $filters['meta_query'];
            }
        }
        
        return $query_args;
    }
    
    /**
     * Generate cache key for given filters
     *
     * @param array $filters Image filters
     * @return string Cache key
     */
    private function get_cache_key($filters) {
        // Sort filters to ensure consistent cache keys
        if (is_array($filters)) {
            ksort($filters);
        }
        
        return 'image_count_' . md5(serialize($filters));
    }
    
    /**
     * Validate date string
     *
     * @param string $date Date string to validate
     * @return bool True if valid date, false otherwise
     */
    private function is_valid_date($date) {
        if (empty($date)) {
            return false;
        }
        
        // Try to parse the date
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > 0;
    }
    
    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_statistics() {
        return array(
            'cache_group' => $this->cache_group,
            'cache_expiration' => $this->cache_expiration,
            'cache_enabled' => wp_using_ext_object_cache()
        );
    }
    
    /**
     * Set cache expiration time
     *
     * @param int $seconds Cache expiration in seconds
     * @return bool True on success
     */
    public function set_cache_expiration($seconds) {
        if ($seconds > 0) {
            $this->cache_expiration = (int) $seconds;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get supported image mime types
     *
     * @return array Array of supported mime types
     */
    public function get_supported_mime_types() {
        return array(
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        );
    }
    
    /**
     * Filter images by mime type
     *
     * @param array $query_args Current query arguments
     * @param array $mime_types Array of mime types to include
     * @return array Modified query arguments
     */
    public function filter_by_mime_type($query_args, $mime_types) {
        if (empty($mime_types) || !is_array($mime_types)) {
            return $query_args;
        }
        
        // Validate mime types
        $supported_types = $this->get_supported_mime_types();
        $mime_types = array_intersect($mime_types, $supported_types);
        
        if (!empty($mime_types)) {
            $query_args['post_mime_type'] = $mime_types;
        }
        
        return $query_args;
    }
}