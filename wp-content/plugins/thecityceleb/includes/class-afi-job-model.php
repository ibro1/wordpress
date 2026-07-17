<?php

/**
 * AFI Job Model Class
 *
 * Handles job data management, status transitions, and progress calculations.
 *
 * @package Auto_Featured_Image
 * @subpackage Models
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AFI_Job_Model {
    
    /**
     * Job ID
     * @var int
     */
    public $id;
    
    /**
     * Job status
     * @var string
     */
    public $status;
    
    /**
     * Post types to process (JSON array)
     * @var string
     */
    public $post_types;
    
    /**
     * Image filters (JSON object)
     * @var string
     */
    public $image_filters;
    
    /**
     * Total items to process
     * @var int
     */
    public $total_items;
    
    /**
     * Processed items count
     * @var int
     */
    public $processed_items;
    
    /**
     * Job creation timestamp
     * @var string
     */
    public $created_at;
    
    /**
     * Job completion timestamp
     * @var string
     */
    public $finished_at;
    
    /**
     * Valid job statuses
     * @var array
     */
    const VALID_STATUSES = [
        'scanning',
        'pending', 
        'running',
        'paused',
        'complete',
        'canceled',
        'failed'
    ];
    
    /**
     * Constructor
     *
     * @param array $data Job data
     */
    public function __construct($data = []) {
        $this->populate_from_array($data);
    }
    
    /**
     * Populate model from array data
     *
     * @param array $data Job data
     */
    private function populate_from_array($data) {
        $this->id = isset($data['id']) ? (int) $data['id'] : 0;
        $this->status = isset($data['status']) ? sanitize_text_field($data['status']) : 'pending';
        $this->post_types = isset($data['post_types']) ? $data['post_types'] : '';
        $this->image_filters = isset($data['image_filters']) ? $data['image_filters'] : '';
        $this->total_items = isset($data['total_items']) ? (int) $data['total_items'] : 0;
        $this->processed_items = isset($data['processed_items']) ? (int) $data['processed_items'] : 0;
        $this->created_at = isset($data['created_at']) ? $data['created_at'] : current_time('mysql');
        $this->finished_at = isset($data['finished_at']) ? $data['finished_at'] : null;
    }
    
    /**
     * Calculate progress percentage
     *
     * @return float Progress percentage (0-100)
     */
    public function get_progress_percentage() {
        if ($this->total_items <= 0) {
            return 0.0;
        }
        
        $percentage = ($this->processed_items / $this->total_items) * 100;
        return round($percentage, 2);
    }
    
    /**
     * Check if job is currently active
     *
     * @return bool True if job is active
     */
    public function is_active() {
        return in_array($this->status, ['scanning', 'running']);
    }
    
    /**
     * Check if job can be paused
     *
     * @return bool True if job can be paused
     */
    public function can_be_paused() {
        return in_array($this->status, ['scanning', 'running']);
    }
    
    /**
     * Check if job can be resumed
     *
     * @return bool True if job can be resumed
     */
    public function can_be_resumed() {
        return $this->status === 'paused';
    }
    
    /**
     * Check if job is complete
     *
     * @return bool True if job is complete
     */
    public function is_complete() {
        return in_array($this->status, ['complete', 'canceled', 'failed']);
    }
    
    /**
     * Update job status with validation
     *
     * @param string $new_status New status
     * @return bool True if status was updated
     */
    public function update_status($new_status) {
        $new_status = sanitize_text_field($new_status);
        
        if (!$this->is_valid_status($new_status)) {
            return false;
        }
        
        if (!$this->is_valid_status_transition($this->status, $new_status)) {
            return false;
        }
        
        $this->status = $new_status;
        
        // Set finished_at timestamp for completion statuses
        if (in_array($new_status, ['complete', 'canceled', 'failed'])) {
            $this->finished_at = current_time('mysql');
        }
        
        return true;
    }
    
    /**
     * Validate status value
     *
     * @param string $status Status to validate
     * @return bool True if valid
     */
    public function is_valid_status($status) {
        return in_array($status, self::VALID_STATUSES);
    }
    
    /**
     * Validate status transition
     *
     * @param string $from_status Current status
     * @param string $to_status New status
     * @return bool True if transition is valid
     */
    public function is_valid_status_transition($from_status, $to_status) {
        $valid_transitions = [
            'scanning' => ['pending', 'canceled', 'failed'],
            'pending' => ['running', 'canceled'],
            'running' => ['paused', 'complete', 'canceled', 'failed'],
            'paused' => ['running', 'canceled'],
            'complete' => [], // Terminal state
            'canceled' => [], // Terminal state
            'failed' => []    // Terminal state
        ];
        
        if (!isset($valid_transitions[$from_status])) {
            return false;
        }
        
        return in_array($to_status, $valid_transitions[$from_status]);
    }
    
    /**
     * Get post types as array
     *
     * @return array Post types
     */
    public function get_post_types_array() {
        if (empty($this->post_types)) {
            return [];
        }
        
        $post_types = json_decode($this->post_types, true);
        return is_array($post_types) ? $post_types : [];
    }
    
    /**
     * Set post types from array
     *
     * @param array $post_types Post types array
     */
    public function set_post_types_array($post_types) {
        if (!is_array($post_types)) {
            $post_types = [];
        }
        
        // Sanitize post types
        $post_types = array_map('sanitize_text_field', $post_types);
        $this->post_types = wp_json_encode($post_types);
    }
    
    /**
     * Get image filters as array
     *
     * @return array Image filters
     */
    public function get_image_filters_array() {
        if (empty($this->image_filters)) {
            return [];
        }
        
        $filters = json_decode($this->image_filters, true);
        return is_array($filters) ? $filters : [];
    }
    
    /**
     * Set image filters from array
     *
     * @param array $filters Image filters array
     */
    public function set_image_filters_array($filters) {
        if (!is_array($filters)) {
            $filters = [];
        }
        
        $this->image_filters = wp_json_encode($filters);
    }
    
    /**
     * Validate job data
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate() {
        $errors = [];
        
        // Validate status
        if (!$this->is_valid_status($this->status)) {
            $errors[] = 'Invalid job status: ' . $this->status;
        }
        
        // Validate post types
        $post_types = $this->get_post_types_array();
        if (empty($post_types)) {
            $errors[] = 'At least one post type must be specified';
        } else {
            foreach ($post_types as $post_type) {
                if (!post_type_exists($post_type)) {
                    $errors[] = 'Invalid post type: ' . $post_type;
                }
            }
        }
        
        // Validate numeric fields
        if ($this->total_items < 0) {
            $errors[] = 'Total items cannot be negative';
        }
        
        if ($this->processed_items < 0) {
            $errors[] = 'Processed items cannot be negative';
        }
        
        if ($this->processed_items > $this->total_items) {
            $errors[] = 'Processed items cannot exceed total items';
        }
        
        return $errors;
    }
    
    /**
     * Convert model to array for database storage
     *
     * @return array Model data as array
     */
    public function to_array() {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'post_types' => $this->post_types,
            'image_filters' => $this->image_filters,
            'total_items' => $this->total_items,
            'processed_items' => $this->processed_items,
            'created_at' => $this->created_at,
            'finished_at' => $this->finished_at
        ];
    }
}