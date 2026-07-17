<?php
/**
 * Image filter model class for Auto Featured Image
 *
 * Handles image filtering configuration and WordPress query integration
 * for optimized image selection based on user-defined criteria.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image filter model class with WordPress query integration
 */
class AFI_Image_Filter_Model {
    
    /**
     * Whether to use all images (no filters)
     *
     * @var bool
     */
    public $use_all_images = true;
    
    /**
     * Date range filter
     *
     * @var array|null Array with 'start' and 'end' keys, or null for no date filter
     */
    public $date_range = null;
    
    /**
     * Keyword filter
     *
     * @var string|null Keyword to search for, or null for no keyword filter
     */
    public $keyword = null;
    
    /**
     * Fields to search for keyword
     *
     * @var array Array of fields to search: filename, title, alt, description
     */
    public $keyword_fields = array('filename', 'title', 'alt');
    
    /**
     * Custom meta query filters
     *
     * @var array|null Custom meta query array, or null for no custom filters
     */
    public $meta_query = null;
    
    /**
     * Supported mime types filter
     *
     * @var array|null Array of mime types to include, or null for all image types
     */
    public $mime_types = null;
    
    /**
     * Constructor
     *
     * @param array $data Filter data array
     */
    public function __construct($data = array()) {
        $this->load_from_array($data);
    }
    
    /**
     * Load filter data from array
     *
     * @param array $data Filter data
     */
    public function load_from_array($data) {
        if (!is_array($data)) {
            return;
        }
        
        // Set use_all_images flag
        if (isset($data['use_all_images'])) {
            $this->use_all_images = (bool) $data['use_all_images'];
        }
        
        // Set date range filter
        if (isset($data['date_range']) && is_array($data['date_range'])) {
            $this->date_range = $this->sanitize_date_range($data['date_range']);
        }
        
        // Set keyword filter
        if (isset($data['keyword']) && !empty($data['keyword'])) {
            $this->keyword = sanitize_text_field($data['keyword']);
            $this->use_all_images = false;
        }
        
        // Set keyword fields
        if (isset($data['keyword_fields']) && is_array($data['keyword_fields'])) {
            $this->keyword_fields = $this->sanitize_keyword_fields($data['keyword_fields']);
        }
        
        // Set custom meta query
        if (isset($data['meta_query']) && is_array($data['meta_query'])) {
            $this->meta_query = $data['meta_query'];
            $this->use_all_images = false;
        }
        
        // Set mime types filter
        if (isset($data['mime_types']) && is_array($data['mime_types'])) {
            $this->mime_types = $this->sanitize_mime_types($data['mime_types']);
            if (!empty($this->mime_types)) {
                $this->use_all_images = false;
            }
        }
    }
    
    /**
     * Convert filter model to WordPress query arguments
     *
     * @return array WordPress query arguments
     */
    public function to_wp_query_args() {
        $query_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );
        
        // If using all images, return basic query
        if ($this->use_all_images && empty($this->date_range) && empty($this->keyword) && empty($this->meta_query) && empty($this->mime_types)) {
            return $query_args;
        }
        
        // Apply date range filter
        if (!empty($this->date_range)) {
            $query_args = $this->apply_date_range_to_query($query_args);
        }
        
        // Apply keyword filter
        if (!empty($this->keyword)) {
            $query_args = $this->apply_keyword_to_query($query_args);
        }
        
        // Apply custom meta query
        if (!empty($this->meta_query)) {
            $query_args = $this->apply_meta_query_to_query($query_args);
        }
        
        // Apply mime types filter
        if (!empty($this->mime_types)) {
            $query_args['post_mime_type'] = $this->mime_types;
        }
        
        return $query_args;
    }
    
    /**
     * Get cache key for this filter configuration
     *
     * @return string Unique cache key
     */
    public function get_cache_key() {
        $filter_data = array(
            'use_all_images' => $this->use_all_images,
            'date_range' => $this->date_range,
            'keyword' => $this->keyword,
            'keyword_fields' => $this->keyword_fields,
            'meta_query' => $this->meta_query,
            'mime_types' => $this->mime_types
        );
        
        // Sort to ensure consistent cache keys
        ksort($filter_data);
        
        return 'afi_filter_' . md5(serialize($filter_data));
    }
    
    /**
     * Check if any filters are active
     *
     * @return bool True if filters are active, false if using all images
     */
    public function has_active_filters() {
        return !$this->use_all_images || 
               !empty($this->date_range) || 
               !empty($this->keyword) || 
               !empty($this->meta_query) || 
               !empty($this->mime_types);
    }
    
    /**
     * Get filter summary for display
     *
     * @return array Filter summary
     */
    public function get_filter_summary() {
        $summary = array();
        
        if ($this->use_all_images && !$this->has_active_filters()) {
            $summary[] = 'Using all images';
            return $summary;
        }
        
        if (!empty($this->date_range)) {
            $date_summary = 'Date range: ';
            if (!empty($this->date_range['start'])) {
                $date_summary .= 'from ' . $this->date_range['start'];
            }
            if (!empty($this->date_range['end'])) {
                $date_summary .= ' to ' . $this->date_range['end'];
            }
            $summary[] = $date_summary;
        }
        
        if (!empty($this->keyword)) {
            $fields_text = implode(', ', $this->keyword_fields);
            $summary[] = "Keyword: '{$this->keyword}' in {$fields_text}";
        }
        
        if (!empty($this->mime_types)) {
            $summary[] = 'Mime types: ' . implode(', ', $this->mime_types);
        }
        
        if (!empty($this->meta_query)) {
            $summary[] = 'Custom meta filters applied';
        }
        
        return $summary;
    }
    
    /**
     * Validate filter configuration
     *
     * @return array Validation result with success status and errors
     */
    public function validate() {
        $errors = array();
        
        // Validate date range
        if (!empty($this->date_range)) {
            if (!is_array($this->date_range)) {
                $errors[] = 'Date range must be an array';
            } else {
                if (!empty($this->date_range['start']) && !$this->is_valid_date($this->date_range['start'])) {
                    $errors[] = 'Invalid start date format';
                }
                if (!empty($this->date_range['end']) && !$this->is_valid_date($this->date_range['end'])) {
                    $errors[] = 'Invalid end date format';
                }
                
                // Check if start date is before end date
                if (!empty($this->date_range['start']) && !empty($this->date_range['end'])) {
                    $start_time = strtotime($this->date_range['start']);
                    $end_time = strtotime($this->date_range['end']);
                    
                    if ($start_time > $end_time) {
                        $errors[] = 'Start date must be before end date';
                    }
                }
            }
        }
        
        // Validate keyword fields
        if (!empty($this->keyword_fields)) {
            $valid_fields = array('filename', 'title', 'alt', 'description');
            $invalid_fields = array_diff($this->keyword_fields, $valid_fields);
            
            if (!empty($invalid_fields)) {
                $errors[] = 'Invalid keyword fields: ' . implode(', ', $invalid_fields);
            }
        }
        
        // Validate mime types
        if (!empty($this->mime_types)) {
            $valid_mime_types = array(
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
                'image/webp', 'image/svg+xml'
            );
            
            $invalid_types = array_diff($this->mime_types, $valid_mime_types);
            
            if (!empty($invalid_types)) {
                $errors[] = 'Invalid mime types: ' . implode(', ', $invalid_types);
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Convert model to array for storage
     *
     * @return array Filter data array
     */
    public function to_array() {
        return array(
            'use_all_images' => $this->use_all_images,
            'date_range' => $this->date_range,
            'keyword' => $this->keyword,
            'keyword_fields' => $this->keyword_fields,
            'meta_query' => $this->meta_query,
            'mime_types' => $this->mime_types
        );
    }
    
    /**
     * Apply date range filter to query arguments
     *
     * @param array $query_args Current query arguments
     * @return array Modified query arguments
     */
    private function apply_date_range_to_query($query_args) {
        if (empty($this->date_range)) {
            return $query_args;
        }
        
        $date_query = array();
        
        if (!empty($this->date_range['start'])) {
            $date_query['after'] = $this->date_range['start'];
        }
        
        if (!empty($this->date_range['end'])) {
            $date_query['before'] = $this->date_range['end'];
        }
        
        if (!empty($date_query)) {
            $query_args['date_query'] = array($date_query);
        }
        
        return $query_args;
    }
    
    /**
     * Apply keyword filter to query arguments
     *
     * @param array $query_args Current query arguments
     * @return array Modified query arguments
     */
    private function apply_keyword_to_query($query_args) {
        if (empty($this->keyword)) {
            return $query_args;
        }
        
        $meta_query = array('relation' => 'OR');
        $has_meta_query = false;
        
        foreach ($this->keyword_fields as $field) {
            switch ($field) {
                case 'filename':
                case 'title':
                case 'description':
                    // These are searched via the 's' parameter
                    $query_args['s'] = $this->keyword;
                    break;
                    
                case 'alt':
                    // Alt text is stored in meta
                    $meta_query[] = array(
                        'key' => '_wp_attachment_image_alt',
                        'value' => $this->keyword,
                        'compare' => 'LIKE'
                    );
                    $has_meta_query = true;
                    break;
            }
        }
        
        // Add meta query if we have alt text search
        if ($has_meta_query) {
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
     * Apply custom meta query to query arguments
     *
     * @param array $query_args Current query arguments
     * @return array Modified query arguments
     */
    private function apply_meta_query_to_query($query_args) {
        if (empty($this->meta_query)) {
            return $query_args;
        }
        
        if (isset($query_args['meta_query'])) {
            $query_args['meta_query'] = array(
                'relation' => 'AND',
                $query_args['meta_query'],
                $this->meta_query
            );
        } else {
            $query_args['meta_query'] = $this->meta_query;
        }
        
        return $query_args;
    }
    
    /**
     * Sanitize date range array
     *
     * @param array $date_range Raw date range data
     * @return array|null Sanitized date range or null if invalid
     */
    private function sanitize_date_range($date_range) {
        if (!is_array($date_range)) {
            return null;
        }
        
        $sanitized = array();
        
        if (!empty($date_range['start'])) {
            $start_date = sanitize_text_field($date_range['start']);
            if ($this->is_valid_date($start_date)) {
                $sanitized['start'] = $start_date;
            }
        }
        
        if (!empty($date_range['end'])) {
            $end_date = sanitize_text_field($date_range['end']);
            if ($this->is_valid_date($end_date)) {
                $sanitized['end'] = $end_date;
            }
        }
        
        return !empty($sanitized) ? $sanitized : null;
    }
    
    /**
     * Sanitize keyword fields array
     *
     * @param array $fields Raw keyword fields
     * @return array Sanitized keyword fields
     */
    private function sanitize_keyword_fields($fields) {
        if (!is_array($fields)) {
            return array('filename', 'title', 'alt');
        }
        
        $valid_fields = array('filename', 'title', 'alt', 'description');
        $sanitized = array_intersect($fields, $valid_fields);
        
        return !empty($sanitized) ? $sanitized : array('filename', 'title', 'alt');
    }
    
    /**
     * Sanitize mime types array
     *
     * @param array $mime_types Raw mime types
     * @return array|null Sanitized mime types or null if invalid
     */
    private function sanitize_mime_types($mime_types) {
        if (!is_array($mime_types)) {
            return null;
        }
        
        $valid_mime_types = array(
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
            'image/webp', 'image/svg+xml'
        );
        
        $sanitized = array_intersect($mime_types, $valid_mime_types);
        
        return !empty($sanitized) ? $sanitized : null;
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
        
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > 0;
    }
    
    /**
     * Create filter model from job data
     *
     * @param object $job Job object with image_filters property
     * @return AFI_Image_Filter_Model Filter model instance
     */
    public static function from_job($job) {
        $filters = array();
        
        if (!empty($job->image_filters)) {
            if (is_string($job->image_filters)) {
                $filters = json_decode($job->image_filters, true) ?: array();
            } elseif (is_array($job->image_filters)) {
                $filters = $job->image_filters;
            }
        }
        
        return new self($filters);
    }
    
    /**
     * Get default filter configuration
     *
     * @return AFI_Image_Filter_Model Default filter model
     */
    public static function get_default() {
        return new self(array(
            'use_all_images' => true,
            'keyword_fields' => array('filename', 'title', 'alt')
        ));
    }
}