<?php

/**
 * AFI Job Item Model Class
 *
 * Handles individual job item data management and state transitions.
 *
 * @package Auto_Featured_Image
 * @subpackage Models
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AFI_Job_Item_Model {
    
    /**
     * Job item ID
     * @var int
     */
    public $id;
    
    /**
     * Parent job ID
     * @var int
     */
    public $job_id;
    
    /**
     * Post ID to process
     * @var int
     */
    public $post_id;
    
    /**
     * Item status
     * @var string
     */
    public $status;
    
    /**
     * Assigned image ID
     * @var int
     */
    public $assigned_image_id;
    
    /**
     * Log message
     * @var string
     */
    public $log_message;
    
    /**
     * Processing timestamp
     * @var string
     */
    public $processed_at;
    
    /**
     * Valid item statuses
     * @var array
     */
    const VALID_STATUSES = [
        'pending',
        'processing',
        'complete',
        'failed',
        'skipped'
    ];
    
    /**
     * Constructor
     *
     * @param array $data Job item data
     */
    public function __construct($data = []) {
        $this->populate_from_array($data);
    }
    
    /**
     * Populate model from array data
     *
     * @param array $data Job item data
     */
    private function populate_from_array($data) {
        $this->id = isset($data['id']) ? (int) $data['id'] : 0;
        $this->job_id = isset($data['job_id']) ? (int) $data['job_id'] : 0;
        $this->post_id = isset($data['post_id']) ? (int) $data['post_id'] : 0;
        $this->status = isset($data['status']) ? sanitize_text_field($data['status']) : 'pending';
        $this->assigned_image_id = isset($data['assigned_image_id']) ? (int) $data['assigned_image_id'] : null;
        $this->log_message = isset($data['log_message']) ? sanitize_textarea_field($data['log_message']) : '';
        $this->processed_at = isset($data['processed_at']) ? $data['processed_at'] : null;
    }
    
    /**
     * Mark item as complete with assigned image
     *
     * @param int $image_id Assigned image ID
     * @param string $message Optional log message
     * @return bool True if successful
     */
    public function mark_complete($image_id, $message = '') {
        if (!$this->can_transition_to('complete')) {
            return false;
        }
        
        $image_id = (int) $image_id;
        if ($image_id <= 0) {
            return false;
        }
        
        // Verify image exists
        if (!wp_attachment_is_image($image_id)) {
            return false;
        }
        
        $this->status = 'complete';
        $this->assigned_image_id = $image_id;
        $this->processed_at = current_time('mysql');
        
        if (!empty($message)) {
            $this->log_message = sanitize_textarea_field($message);
        } else {
            $this->log_message = sprintf('Successfully assigned image ID %d to post ID %d', $image_id, $this->post_id);
        }
        
        return true;
    }
    
    /**
     * Mark item as failed with error message
     *
     * @param string $error_message Error message
     * @return bool True if successful
     */
    public function mark_failed($error_message) {
        if (!$this->can_transition_to('failed')) {
            return false;
        }
        
        $this->status = 'failed';
        $this->processed_at = current_time('mysql');
        $this->log_message = sanitize_textarea_field($error_message);
        
        return true;
    }
    
    /**
     * Mark item as skipped with reason
     *
     * @param string $reason Reason for skipping
     * @return bool True if successful
     */
    public function mark_skipped($reason) {
        if (!$this->can_transition_to('skipped')) {
            return false;
        }
        
        $this->status = 'skipped';
        $this->processed_at = current_time('mysql');
        $this->log_message = sanitize_textarea_field($reason);
        
        return true;
    }
    
    /**
     * Mark item as processing
     *
     * @return bool True if successful
     */
    public function mark_processing() {
        if (!$this->can_transition_to('processing')) {
            return false;
        }
        
        $this->status = 'processing';
        $this->log_message = sprintf('Started processing post ID %d', $this->post_id);
        
        return true;
    }
    
    /**
     * Update item status with validation
     *
     * @param string $new_status New status
     * @return bool True if status was updated
     */
    public function update_status($new_status) {
        $new_status = sanitize_text_field($new_status);
        
        if (!$this->is_valid_status($new_status)) {
            return false;
        }
        
        if (!$this->can_transition_to($new_status)) {
            return false;
        }
        
        $this->status = $new_status;
        
        // Set processed_at timestamp for completion statuses
        if (in_array($new_status, ['complete', 'failed', 'skipped'])) {
            $this->processed_at = current_time('mysql');
        }
        
        return true;
    }
    
    /**
     * Check if status transition is valid
     *
     * @param string $to_status Target status
     * @return bool True if transition is valid
     */
    public function can_transition_to($to_status) {
        if (!$this->is_valid_status($to_status)) {
            return false;
        }
        
        $valid_transitions = [
            'pending' => ['processing', 'skipped'],
            'processing' => ['complete', 'failed', 'skipped'],
            'complete' => [], // Terminal state
            'failed' => ['processing'], // Can retry failed items
            'skipped' => ['processing'] // Can retry skipped items
        ];
        
        if (!isset($valid_transitions[$this->status])) {
            return false;
        }
        
        return in_array($to_status, $valid_transitions[$this->status]);
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
     * Check if item is complete
     *
     * @return bool True if item is complete
     */
    public function is_complete() {
        return $this->status === 'complete';
    }
    
    /**
     * Check if item failed
     *
     * @return bool True if item failed
     */
    public function is_failed() {
        return $this->status === 'failed';
    }
    
    /**
     * Check if item was skipped
     *
     * @return bool True if item was skipped
     */
    public function is_skipped() {
        return $this->status === 'skipped';
    }
    
    /**
     * Check if item is pending
     *
     * @return bool True if item is pending
     */
    public function is_pending() {
        return $this->status === 'pending';
    }
    
    /**
     * Check if item is currently processing
     *
     * @return bool True if item is processing
     */
    public function is_processing() {
        return $this->status === 'processing';
    }
    
    /**
     * Check if item can be retried
     *
     * @return bool True if item can be retried
     */
    public function can_be_retried() {
        return in_array($this->status, ['failed', 'skipped']);
    }
    
    /**
     * Get post object
     *
     * @return WP_Post|null Post object or null if not found
     */
    public function get_post() {
        return get_post($this->post_id);
    }
    
    /**
     * Get assigned image object
     *
     * @return WP_Post|null Image attachment object or null if not assigned
     */
    public function get_assigned_image() {
        if (!$this->assigned_image_id) {
            return null;
        }
        
        return get_post($this->assigned_image_id);
    }
    
    /**
     * Validate job item data
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validate() {
        $errors = [];
        
        // Validate job ID
        if ($this->job_id <= 0) {
            $errors[] = 'Job ID must be a positive integer';
        }
        
        // Validate post ID
        if ($this->post_id <= 0) {
            $errors[] = 'Post ID must be a positive integer';
        } else {
            // Check if post exists
            $post = get_post($this->post_id);
            if (!$post) {
                $errors[] = 'Post with ID ' . $this->post_id . ' does not exist';
            }
        }
        
        // Validate status
        if (!$this->is_valid_status($this->status)) {
            $errors[] = 'Invalid item status: ' . $this->status;
        }
        
        // Validate assigned image ID if set
        if ($this->assigned_image_id !== null) {
            if ($this->assigned_image_id <= 0) {
                $errors[] = 'Assigned image ID must be a positive integer';
            } else {
                // Check if image exists and is an attachment
                if (!wp_attachment_is_image($this->assigned_image_id)) {
                    $errors[] = 'Assigned image ID ' . $this->assigned_image_id . ' is not a valid image attachment';
                }
            }
        }
        
        // Validate that complete items have assigned images
        if ($this->status === 'complete' && !$this->assigned_image_id) {
            $errors[] = 'Complete items must have an assigned image ID';
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
            'job_id' => $this->job_id,
            'post_id' => $this->post_id,
            'status' => $this->status,
            'assigned_image_id' => $this->assigned_image_id,
            'log_message' => $this->log_message,
            'processed_at' => $this->processed_at
        ];
    }
}