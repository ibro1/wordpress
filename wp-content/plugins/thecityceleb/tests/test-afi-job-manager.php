<?php
/**
 * Unit tests for AFI_Job_Manager class
 *
 * Tests job lifecycle management, Action Scheduler integration,
 * and background job processing coordination.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

class AFI_Job_Manager_Test extends WP_UnitTestCase {
    
    /**
     * Job manager instance
     *
     * @var AFI_Job_Manager
     */
    private $job_manager;
    
    /**
     * Database instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Test post IDs
     *
     * @var array
     */
    private $test_posts = array();
    
    /**
     * Test image IDs
     *
     * @var array
     */
    private $test_images = array();
    
    /**
     * Set up test environment
     */
    public function setUp() {
        parent::setUp();
        
        // Initialize database and job manager
        $this->database = new AFI_Database();
        $this->database->create_tables();
        $this->job_manager = new AFI_Job_Manager();
        
        // Create test posts without featured images
        $this->create_test_posts();
        
        // Create test images
        $this->create_test_images();
        
        // Mock Action Scheduler functions if not available
        $this->mock_action_scheduler_functions();
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown() {
        // Clean up test posts
        foreach ($this->test_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        // Clean up test images
        foreach ($this->test_images as $image_id) {
            wp_delete_attachment($image_id, true);
        }
        
        // Clean up database tables
        $this->database->drop_tables();
        
        parent::tearDown();
    }
    
    /**
     * Test job creation with valid parameters
     */
    public function test_create_scan_job_success() {
        $post_types = array('post', 'page');
        $image_filters = array(
            'date_range' => array(
                'start' => '2023-01-01',
                'end' => '2023-12-31'
            )
        );
        
        $job_id = $this->job_manager->create_scan_job($post_types, $image_filters);
        
        $this->assertNotFalse($job_id);
        $this->assertIsInt($job_id);
        $this->assertGreaterThan(0, $job_id);
        
        // Verify job was created in database
        $job = $this->database->get_job($job_id);
        $this->assertNotNull($job);
        $this->assertEquals('scanning', $job->status);
        $this->assertEquals($post_types, $job->post_types);
        $this->assertEquals($image_filters, $job->image_filters);
    }
    
    /**
     * Test job creation with invalid parameters
     */
    public function test_create_scan_job_invalid_parameters() {
        // Test with empty post types
        $job_id = $this->job_manager->create_scan_job(array());
        $this->assertFalse($job_id);
        
        // Test with non-array post types
        $job_id = $this->job_manager->create_scan_job('post');
        $this->assertFalse($job_id);
        
        // Test with non-existent post type
        $job_id = $this->job_manager->create_scan_job(array('nonexistent_post_type'));
        $this->assertFalse($job_id);
    }  
  
    /**
     * Test starting job processing
     */
    public function test_start_processing_success() {
        // Create a job and set it to pending status
        $job_id = $this->create_test_job('pending');
        
        $result = $this->job_manager->start_processing($job_id);
        $this->assertTrue($result);
        
        // Verify job status changed to running
        $job = $this->database->get_job($job_id);
        $this->assertEquals('running', $job->status);
    }
    
    /**
     * Test starting processing with invalid job
     */
    public function test_start_processing_invalid_job() {
        // Test with non-existent job
        $result = $this->job_manager->start_processing(999999);
        $this->assertFalse($result);
        
        // Test with job in wrong status
        $job_id = $this->create_test_job('running');
        $result = $this->job_manager->start_processing($job_id);
        $this->assertFalse($result);
    }
    
    /**
     * Test pausing a running job
     */
    public function test_pause_job_success() {
        $job_id = $this->create_test_job('running');
        
        $result = $this->job_manager->pause_job($job_id);
        $this->assertTrue($result);
        
        // Verify job status changed to paused
        $job = $this->database->get_job($job_id);
        $this->assertEquals('paused', $job->status);
    }
    
    /**
     * Test pausing job with invalid status
     */
    public function test_pause_job_invalid_status() {
        $job_id = $this->create_test_job('complete');
        
        $result = $this->job_manager->pause_job($job_id);
        $this->assertFalse($result);
    }
    
    /**
     * Test resuming a paused job
     */
    public function test_resume_job_success() {
        // Test resuming scanning phase
        $job_id = $this->create_test_job('paused', 0); // 0 total_items means scanning phase
        
        $result = $this->job_manager->resume_job($job_id);
        $this->assertTrue($result);
        
        $job = $this->database->get_job($job_id);
        $this->assertEquals('scanning', $job->status);
        
        // Test resuming processing phase
        $job_id = $this->create_test_job('paused', 100); // >0 total_items means processing phase
        
        $result = $this->job_manager->resume_job($job_id);
        $this->assertTrue($result);
        
        $job = $this->database->get_job($job_id);
        $this->assertEquals('running', $job->status);
    }
    
    /**
     * Test resuming job with invalid status
     */
    public function test_resume_job_invalid_status() {
        $job_id = $this->create_test_job('running');
        
        $result = $this->job_manager->resume_job($job_id);
        $this->assertFalse($result);
    }
    
    /**
     * Test canceling a job
     */
    public function test_cancel_job_success() {
        $job_id = $this->create_test_job('running');
        
        $result = $this->job_manager->cancel_job($job_id);
        $this->assertTrue($result);
        
        // Verify job status changed to canceled
        $job = $this->database->get_job($job_id);
        $this->assertEquals('canceled', $job->status);
        $this->assertNotNull($job->finished_at);
    }
    
    /**
     * Test canceling job with invalid status
     */
    public function test_cancel_job_invalid_status() {
        $job_id = $this->create_test_job('complete');
        
        $result = $this->job_manager->cancel_job($job_id);
        $this->assertFalse($result);
    }
    
    /**
     * Test getting job status
     */
    public function test_get_job_status() {
        $job_id = $this->create_test_job('running', 100, 25);
        
        $status = $this->job_manager->get_job_status($job_id);
        
        $this->assertIsArray($status);
        $this->assertEquals($job_id, $status['id']);
        $this->assertEquals('running', $status['status']);
        $this->assertEquals(100, $status['total_items']);
        $this->assertEquals(25, $status['processed_items']);
        $this->assertEquals(25, $status['progress_percentage']);
        $this->assertTrue($status['is_active']);
        $this->assertTrue($status['can_be_paused']);
    }
    
    /**
     * Test getting status for non-existent job
     */
    public function test_get_job_status_invalid_job() {
        $status = $this->job_manager->get_job_status(999999);
        $this->assertFalse($status);
    } 
   
    /**
     * Test scan batch processing
     */
    public function test_process_scan_batch() {
        $job_id = $this->create_test_job('scanning');
        $post_types = array('post');
        
        // Process first batch
        $this->job_manager->process_scan_batch($job_id, $post_types, 1);
        
        // Verify job items were created
        $job_items = $this->database->get_job_items($job_id, 100, 0);
        $this->assertNotEmpty($job_items);
        
        // Verify job total_items was updated
        $job = $this->database->get_job($job_id);
        $this->assertGreaterThan(0, $job->total_items);
    }
    
    /**
     * Test scan batch with paused job
     */
    public function test_process_scan_batch_paused_job() {
        $job_id = $this->create_test_job('paused');
        $post_types = array('post');
        
        // Should not process when job is paused
        $this->job_manager->process_scan_batch($job_id, $post_types, 1);
        
        // Verify no job items were created
        $job_items = $this->database->get_job_items($job_id, 100, 0);
        $this->assertEmpty($job_items);
    }
    
    /**
     * Test job items batch processing
     */
    public function test_process_job_items_batch() {
        $job_id = $this->create_test_job('running', 10, 0);
        
        // Create test job items
        for ($i = 0; $i < 5; $i++) {
            $this->database->create_job_item(array(
                'job_id' => $job_id,
                'post_id' => $this->test_posts[$i],
                'status' => 'pending'
            ));
        }
        
        // Process batch
        $this->job_manager->process_job_items_batch($job_id, 1);
        
        // Verify items were processed
        $job = $this->database->get_job($job_id);
        $this->assertGreaterThan(0, $job->processed_items);
        
        // Verify some items have been assigned images
        $job_items = $this->database->get_job_items($job_id, 100, 0);
        $processed_items = array_filter($job_items, function($item) {
            return $item->status !== 'pending';
        });
        $this->assertNotEmpty($processed_items);
    }
    
    /**
     * Test processing with no available images
     */
    public function test_process_job_items_no_images() {
        // Remove test images
        foreach ($this->test_images as $image_id) {
            wp_delete_attachment($image_id, true);
        }
        $this->test_images = array();
        
        $job_id = $this->create_test_job('running', 5, 0);
        
        // Create test job item
        $item_id = $this->database->create_job_item(array(
            'job_id' => $job_id,
            'post_id' => $this->test_posts[0],
            'status' => 'pending'
        ));
        
        // Process batch
        $this->job_manager->process_job_items_batch($job_id, 1);
        
        // Verify item was marked as failed
        $item = $this->database->get_job_item($item_id);
        $this->assertEquals('failed', $item->status);
        $this->assertStringContains('No suitable images found', $item->log_message);
    }
    
    /**
     * Test cleanup of old jobs
     */
    public function test_cleanup_old_jobs() {
        // Create old job (simulate by updating created_at)
        $job_id = $this->create_test_job('complete');
        
        global $wpdb;
        $jobs_table = $wpdb->prefix . 'afi_jobs';
        $wpdb->update(
            $jobs_table,
            array('created_at' => date('Y-m-d H:i:s', strtotime('-100 days'))),
            array('id' => $job_id)
        );
        
        // Run cleanup
        $deleted_count = $this->job_manager->cleanup_old_jobs(90);
        
        $this->assertEquals(1, $deleted_count);
        
        // Verify job was deleted
        $job = $this->database->get_job($job_id);
        $this->assertNull($job);
    }
    
    /**
     * Test canceling all jobs
     */
    public function test_cancel_all_jobs() {
        // Create multiple active jobs
        $job1 = $this->create_test_job('running');
        $job2 = $this->create_test_job('scanning');
        $job3 = $this->create_test_job('paused');
        $job4 = $this->create_test_job('complete'); // Should not be canceled
        
        $canceled_count = $this->job_manager->cancel_all_jobs();
        
        $this->assertEquals(3, $canceled_count);
        
        // Verify jobs were canceled
        $this->assertEquals('canceled', $this->database->get_job($job1)->status);
        $this->assertEquals('canceled', $this->database->get_job($job2)->status);
        $this->assertEquals('canceled', $this->database->get_job($job3)->status);
        $this->assertEquals('complete', $this->database->get_job($job4)->status); // Should remain unchanged
    }
    
    /**
     * Test Action Scheduler integration for scan scheduling
     */
    public function test_schedule_scan_batch() {
        $job_id = $this->create_test_job('scanning');
        $post_types = array('post', 'page');
        
        // Schedule scan batch
        $this->job_manager->schedule_scan_batch($job_id, $post_types, 1);
        
        // Verify action was scheduled
        $this->assertTrue($this->was_action_scheduled('afi_process_scan_batch'));
    }
    
    /**
     * Test Action Scheduler integration for processing scheduling
     */
    public function test_schedule_processing_batch() {
        $job_id = $this->create_test_job('running');
        
        // Schedule processing batch
        $this->job_manager->schedule_processing_batch($job_id, 1);
        
        // Verify action was scheduled
        $this->assertTrue($this->was_action_scheduled('afi_process_job_items_batch'));
    }
    
    /**
     * Test job completion detection
     */
    public function test_check_job_completion() {
        $job_id = $this->create_test_job('running', 5, 5);
        
        // Check completion
        $this->job_manager->check_job_completion($job_id);
        
        // Verify job was marked as complete
        $job = $this->database->get_job($job_id);
        $this->assertEquals('complete', $job->status);
        $this->assertNotNull($job->finished_at);
    }
    
    /**
     * Test job completion with incomplete items
     */
    public function test_check_job_completion_incomplete() {
        $job_id = $this->create_test_job('running', 10, 5);
        
        // Check completion
        $this->job_manager->check_job_completion($job_id);
        
        // Verify job remains running
        $job = $this->database->get_job($job_id);
        $this->assertEquals('running', $job->status);
        $this->assertNull($job->finished_at);
    }
    
    /**
     * Test error handling during job processing
     */
    public function test_handle_processing_error() {
        $job_id = $this->create_test_job('running');
        $item_id = $this->database->create_job_item(array(
            'job_id' => $job_id,
            'post_id' => $this->test_posts[0],
            'status' => 'pending'
        ));
        
        $error_message = 'Test error message';
        
        // Handle error
        $this->job_manager->handle_processing_error($item_id, $error_message);
        
        // Verify item was marked as failed
        $item = $this->database->get_job_item($item_id);
        $this->assertEquals('failed', $item->status);
        $this->assertEquals($error_message, $item->log_message);
        $this->assertNotNull($item->processed_at);
    }
    
    /**
     * Test concurrent job processing prevention
     */
    public function test_prevent_concurrent_processing() {
        $job_id = $this->create_test_job('running');
        
        // Simulate concurrent processing attempt
        $result1 = $this->job_manager->acquire_processing_lock($job_id);
        $result2 = $this->job_manager->acquire_processing_lock($job_id);
        
        $this->assertTrue($result1);
        $this->assertFalse($result2);
        
        // Release lock
        $this->job_manager->release_processing_lock($job_id);
        
        // Should be able to acquire again
        $result3 = $this->job_manager->acquire_processing_lock($job_id);
        $this->assertTrue($result3);
    }
    
    /**
     * Test job statistics calculation
     */
    public function test_get_job_statistics() {
        $job_id = $this->create_test_job('complete', 100, 100);
        
        // Create job items with different statuses
        for ($i = 0; $i < 5; $i++) {
            $this->database->create_job_item(array(
                'job_id' => $job_id,
                'post_id' => $this->test_posts[$i % count($this->test_posts)],
                'status' => 'complete',
                'assigned_image_id' => $this->test_images[0]
            ));
        }
        
        // Create failed items
        for ($i = 0; $i < 2; $i++) {
            $this->database->create_job_item(array(
                'job_id' => $job_id,
                'post_id' => $this->test_posts[$i % count($this->test_posts)],
                'status' => 'failed',
                'log_message' => 'Test failure'
            ));
        }
        
        $stats = $this->job_manager->get_job_statistics($job_id);
        
        $this->assertIsArray($stats);
        $this->assertEquals(7, $stats['total_items']);
        $this->assertEquals(5, $stats['successful_items']);
        $this->assertEquals(2, $stats['failed_items']);
        $this->assertEquals(0, $stats['pending_items']);
        $this->assertArrayHasKey('success_rate', $stats);
        $this->assertArrayHasKey('processing_time', $stats);
    }
    
    /**
     * Test memory usage monitoring
     */
    public function test_monitor_memory_usage() {
        $initial_memory = memory_get_usage();
        
        // Simulate memory-intensive operation
        $job_id = $this->create_test_job('running');
        $this->job_manager->monitor_memory_usage($job_id);
        
        $memory_info = $this->job_manager->get_memory_info($job_id);
        
        $this->assertIsArray($memory_info);
        $this->assertArrayHasKey('current_usage', $memory_info);
        $this->assertArrayHasKey('peak_usage', $memory_info);
        $this->assertArrayHasKey('memory_limit', $memory_info);
        $this->assertGreaterThanOrEqual($initial_memory, $memory_info['current_usage']);
    }
    
    /**
     * Helper method to create test job
     */
    private function create_test_job($status = 'pending', $total_items = 0, $processed_items = 0) {
        $job_data = array(
            'status' => $status,
            'post_types' => array('post'),
            'image_filters' => array(),
            'total_items' => $total_items,
            'processed_items' => $processed_items,
            'created_at' => current_time('mysql')
        );
        
        if (in_array($status, array('complete', 'canceled'))) {
            $job_data['finished_at'] = current_time('mysql');
        }
        
        return $this->database->create_job($job_data);
    }
    
    /**
     * Helper method to create test posts
     */
    private function create_test_posts() {
        for ($i = 0; $i < 10; $i++) {
            $post_id = $this->factory->post->create(array(
                'post_title' => 'Test Post ' . ($i + 1),
                'post_content' => 'Test content for post ' . ($i + 1),
                'post_status' => 'publish',
                'post_type' => 'post'
            ));
            $this->test_posts[] = $post_id;
        }
        
        // Create some pages too
        for ($i = 0; $i < 5; $i++) {
            $post_id = $this->factory->post->create(array(
                'post_title' => 'Test Page ' . ($i + 1),
                'post_content' => 'Test content for page ' . ($i + 1),
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
            $this->test_posts[] = $post_id;
        }
    }
    
    /**
     * Helper method to create test images
     */
    private function create_test_images() {
        for ($i = 0; $i < 5; $i++) {
            $image_id = $this->factory->attachment->create_object(
                'test-image-' . ($i + 1) . '.jpg',
                0,
                array(
                    'post_mime_type' => 'image/jpeg',
                    'post_title' => 'Test Image ' . ($i + 1),
                    'post_content' => 'Test image description',
                    'post_status' => 'inherit'
                )
            );
            $this->test_images[] = $image_id;
        }
    }
    
    /**
     * Mock Action Scheduler functions for testing
     */
    private function mock_action_scheduler_functions() {
        if (!function_exists('as_schedule_single_action')) {
            function as_schedule_single_action($timestamp, $hook, $args = array(), $group = '') {
                // Store scheduled actions for verification
                global $test_scheduled_actions;
                if (!isset($test_scheduled_actions)) {
                    $test_scheduled_actions = array();
                }
                $test_scheduled_actions[] = array(
                    'timestamp' => $timestamp,
                    'hook' => $hook,
                    'args' => $args,
                    'group' => $group
                );
                return true;
            }
        }
        
        if (!function_exists('as_unschedule_all_actions')) {
            function as_unschedule_all_actions($hook, $args = null, $group = '') {
                global $test_scheduled_actions;
                if (isset($test_scheduled_actions)) {
                    $test_scheduled_actions = array_filter($test_scheduled_actions, function($action) use ($hook) {
                        return $action['hook'] !== $hook;
                    });
                }
                return true;
            }
        }
        
        if (!function_exists('as_get_scheduled_actions')) {
            function as_get_scheduled_actions($args = array()) {
                global $test_scheduled_actions;
                return isset($test_scheduled_actions) ? $test_scheduled_actions : array();
            }
        }
    }
    
    /**
     * Helper method to check if action was scheduled
     */
    private function was_action_scheduled($hook) {
        global $test_scheduled_actions;
        if (!isset($test_scheduled_actions)) {
            return false;
        }
        
        foreach ($test_scheduled_actions as $action) {
            if ($action['hook'] === $hook) {
                return true;
            }
        }
        
        return false;
    }
}