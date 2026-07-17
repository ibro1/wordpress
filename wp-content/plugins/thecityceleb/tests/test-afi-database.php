<?php
/**
 * Unit tests for AFI_Database class
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 */

class AFI_Database_Test extends WP_UnitTestCase {
    
    /**
     * Database instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load the database class
        require_once dirname(__DIR__) . '/includes/class-afi-database.php';
        
        $this->database = new AFI_Database();
        
        // Ensure clean state
        $this->database->drop_tables();
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up tables
        $this->database->drop_tables();
        
        parent::tearDown();
    }
    
    /**
     * Test table creation
     */
    public function test_create_tables() {
        $result = $this->database->create_tables();
        
        $this->assertTrue($result, 'Table creation should succeed');
        $this->assertTrue($this->database->tables_exist(), 'Tables should exist after creation');
        
        // Verify database version is set
        $this->assertEquals('1.0.0', $this->database->get_db_version());
    }
    
    /**
     * Test table dropping
     */
    public function test_drop_tables() {
        // First create tables
        $this->database->create_tables();
        $this->assertTrue($this->database->tables_exist());
        
        // Then drop them
        $result = $this->database->drop_tables();
        
        $this->assertTrue($result, 'Table dropping should succeed');
        $this->assertFalse($this->database->tables_exist(), 'Tables should not exist after dropping');
        
        // Verify database version option is removed
        $this->assertEquals('0.0.0', $this->database->get_db_version());
    }
    
    /**
     * Test job creation
     */
    public function test_create_job() {
        $this->database->create_tables();
        
        $job_data = array(
            'status' => 'pending',
            'post_types' => array('post', 'page'),
            'image_filters' => array(
                'use_all_images' => false,
                'date_range' => array(
                    'start' => '2023-01-01',
                    'end' => '2023-12-31'
                )
            ),
            'total_items' => 100
        );
        
        $job_id = $this->database->create_job($job_data);
        
        $this->assertIsInt($job_id, 'Job creation should return integer ID');
        $this->assertGreaterThan(0, $job_id, 'Job ID should be positive');
        
        // Verify job was created correctly
        $job = $this->database->get_job($job_id);
        $this->assertNotNull($job, 'Created job should be retrievable');
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(array('post', 'page'), $job->post_types);
        $this->assertEquals(100, $job->total_items);
    }
    
    /**
     * Test job retrieval
     */
    public function test_get_job() {
        $this->database->create_tables();
        
        // Test non-existent job
        $job = $this->database->get_job(999);
        $this->assertNull($job, 'Non-existent job should return null');
        
        // Test invalid job ID
        $job = $this->database->get_job(0);
        $this->assertNull($job, 'Invalid job ID should return null');
        
        // Create and retrieve job
        $job_data = array(
            'status' => 'running',
            'post_types' => array('product'),
            'total_items' => 50
        );
        
        $job_id = $this->database->create_job($job_data);
        $job = $this->database->get_job($job_id);
        
        $this->assertNotNull($job);
        $this->assertEquals('running', $job->status);
        $this->assertEquals(array('product'), $job->post_types);
        $this->assertEquals(50, $job->total_items);
    }
    
    /**
     * Test job updates
     */
    public function test_update_job() {
        $this->database->create_tables();
        
        // Create initial job
        $job_id = $this->database->create_job(array(
            'status' => 'pending',
            'total_items' => 100,
            'processed_items' => 0
        ));
        
        // Update job
        $update_data = array(
            'status' => 'running',
            'processed_items' => 25
        );
        
        $result = $this->database->update_job($job_id, $update_data);
        $this->assertTrue($result, 'Job update should succeed');
        
        // Verify update
        $job = $this->database->get_job($job_id);
        $this->assertEquals('running', $job->status);
        $this->assertEquals(25, $job->processed_items);
        $this->assertEquals(100, $job->total_items); // Should remain unchanged
        
        // Test invalid job ID
        $result = $this->database->update_job(0, $update_data);
        $this->assertFalse($result, 'Update with invalid ID should fail');
    }
    
    /**
     * Test job item creation
     */
    public function test_create_job_item() {
        $this->database->create_tables();
        
        // Create a job first
        $job_id = $this->database->create_job(array('status' => 'pending'));
        
        $item_data = array(
            'job_id' => $job_id,
            'post_id' => 123,
            'status' => 'pending'
        );
        
        $item_id = $this->database->create_job_item($item_data);
        
        $this->assertIsInt($item_id, 'Job item creation should return integer ID');
        $this->assertGreaterThan(0, $item_id, 'Job item ID should be positive');
        
        // Test missing required fields
        $invalid_data = array('status' => 'pending');
        $result = $this->database->create_job_item($invalid_data);
        $this->assertFalse($result, 'Job item creation without required fields should fail');
    }
    
    /**
     * Test job item retrieval
     */
    public function test_get_job_items() {
        $this->database->create_tables();
        
        // Create a job
        $job_id = $this->database->create_job(array('status' => 'pending'));
        
        // Create multiple job items
        $post_ids = array(101, 102, 103, 104, 105);
        foreach ($post_ids as $post_id) {
            $this->database->create_job_item(array(
                'job_id' => $job_id,
                'post_id' => $post_id,
                'status' => 'pending'
            ));
        }
        
        // Test basic retrieval
        $items = $this->database->get_job_items($job_id);
        $this->assertCount(5, $items, 'Should retrieve all job items');
        
        // Test pagination
        $items = $this->database->get_job_items($job_id, 2, 0);
        $this->assertCount(2, $items, 'Should respect limit parameter');
        
        $items = $this->database->get_job_items($job_id, 2, 2);
        $this->assertCount(2, $items, 'Should respect offset parameter');
        
        // Test status filtering
        // Update one item to 'complete'
        $items = $this->database->get_job_items($job_id, 100, 0);
        $this->database->update_job_item($items[0]->id, array('status' => 'complete'));
        
        $pending_items = $this->database->get_job_items($job_id, 100, 0, 'pending');
        $this->assertCount(4, $pending_items, 'Should filter by status');
        
        $complete_items = $this->database->get_job_items($job_id, 100, 0, 'complete');
        $this->assertCount(1, $complete_items, 'Should filter by status');
        
        // Test invalid job ID
        $items = $this->database->get_job_items(0);
        $this->assertEmpty($items, 'Invalid job ID should return empty array');
    }
    
    /**
     * Test job item updates
     */
    public function test_update_job_item() {
        $this->database->create_tables();
        
        // Create job and item
        $job_id = $this->database->create_job(array('status' => 'pending'));
        $item_id = $this->database->create_job_item(array(
            'job_id' => $job_id,
            'post_id' => 123,
            'status' => 'pending'
        ));
        
        // Update item
        $update_data = array(
            'status' => 'complete',
            'assigned_image_id' => 456,
            'log_message' => 'Successfully assigned image',
            'processed_at' => current_time('mysql')
        );
        
        $result = $this->database->update_job_item($item_id, $update_data);
        $this->assertTrue($result, 'Job item update should succeed');
        
        // Verify update
        $items = $this->database->get_job_items($job_id);
        $item = $items[0];
        $this->assertEquals('complete', $item->status);
        $this->assertEquals(456, $item->assigned_image_id);
        $this->assertEquals('Successfully assigned image', $item->log_message);
        
        // Test invalid item ID
        $result = $this->database->update_job_item(0, $update_data);
        $this->assertFalse($result, 'Update with invalid ID should fail');
    }
    
    /**
     * Test job statistics
     */
    public function test_get_job_stats() {
        $this->database->create_tables();
        
        // Create job
        $job_id = $this->database->create_job(array('status' => 'pending'));
        
        // Create items with different statuses
        $statuses = array('pending', 'pending', 'complete', 'complete', 'failed');
        foreach ($statuses as $index => $status) {
            $item_id = $this->database->create_job_item(array(
                'job_id' => $job_id,
                'post_id' => 100 + $index,
                'status' => 'pending'
            ));
            
            if ($status !== 'pending') {
                $this->database->update_job_item($item_id, array('status' => $status));
            }
        }
        
        $stats = $this->database->get_job_stats($job_id);
        
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(2, $stats['complete']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(5, $stats['total']);
        
        // Test invalid job ID
        $stats = $this->database->get_job_stats(0);
        $this->assertEmpty($stats, 'Invalid job ID should return empty stats');
    }
    
    /**
     * Test cleanup of old jobs
     */
    public function test_cleanup_old_jobs() {
        $this->database->create_tables();
        
        // Create old completed job
        $old_job_id = $this->database->create_job(array(
            'status' => 'complete',
            'created_at' => date('Y-m-d H:i:s', strtotime('-100 days'))
        ));
        
        // Create recent job
        $recent_job_id = $this->database->create_job(array(
            'status' => 'complete',
            'created_at' => current_time('mysql')
        ));
        
        // Create running job (should not be deleted even if old)
        $running_job_id = $this->database->create_job(array(
            'status' => 'running',
            'created_at' => date('Y-m-d H:i:s', strtotime('-100 days'))
        ));
        
        // Add items to old job
        $this->database->create_job_item(array(
            'job_id' => $old_job_id,
            'post_id' => 123
        ));
        
        // Cleanup jobs older than 90 days
        $deleted = $this->database->cleanup_old_jobs(90);
        
        $this->assertEquals(1, $deleted, 'Should delete one old completed job');
        
        // Verify old job is gone
        $old_job = $this->database->get_job($old_job_id);
        $this->assertNull($old_job, 'Old job should be deleted');
        
        // Verify recent and running jobs remain
        $recent_job = $this->database->get_job($recent_job_id);
        $this->assertNotNull($recent_job, 'Recent job should remain');
        
        $running_job = $this->database->get_job($running_job_id);
        $this->assertNotNull($running_job, 'Running job should remain');
        
        // Verify job items were also deleted
        $items = $this->database->get_job_items($old_job_id);
        $this->assertEmpty($items, 'Job items should be deleted with job');
    }
    
    /**
     * Test recent jobs retrieval
     */
    public function test_get_recent_jobs() {
        $this->database->create_tables();
        
        // Create multiple jobs
        $job_ids = array();
        for ($i = 0; $i < 15; $i++) {
            $job_ids[] = $this->database->create_job(array(
                'status' => 'complete',
                'post_types' => array('post'),
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours"))
            ));
        }
        
        // Get recent jobs with default limit
        $recent_jobs = $this->database->get_recent_jobs();
        $this->assertCount(10, $recent_jobs, 'Should return default limit of 10');
        
        // Verify they are ordered by creation date (newest first)
        $this->assertEquals($job_ids[0], $recent_jobs[0]->id, 'Newest job should be first');
        
        // Test custom limit
        $recent_jobs = $this->database->get_recent_jobs(5);
        $this->assertCount(5, $recent_jobs, 'Should respect custom limit');
        
        // Verify JSON fields are decoded
        $this->assertIsArray($recent_jobs[0]->post_types, 'Post types should be decoded array');
        $this->assertEquals(array('post'), $recent_jobs[0]->post_types);
    }
    
    /**
     * Test database upgrade functionality
     */
    public function test_database_upgrade() {
        $this->database->create_tables();
        
        // Initially should not need upgrade
        $this->assertFalse($this->database->needs_upgrade(), 'Fresh database should not need upgrade');
        
        // Simulate old version
        update_option('afi_db_version', '0.9.0');
        $this->assertTrue($this->database->needs_upgrade(), 'Old version should need upgrade');
        
        // Perform upgrade
        $result = $this->database->maybe_upgrade();
        $this->assertTrue($result, 'Upgrade should succeed');
        $this->assertFalse($this->database->needs_upgrade(), 'After upgrade should not need upgrade');
    }
    
    /**
     * Test table names retrieval
     */
    public function test_get_table_names() {
        global $wpdb;
        
        $table_names = $this->database->get_table_names();
        
        $this->assertIsArray($table_names, 'Should return array');
        $this->assertArrayHasKey('jobs', $table_names);
        $this->assertArrayHasKey('job_items', $table_names);
        $this->assertEquals($wpdb->prefix . 'afi_jobs', $table_names['jobs']);
        $this->assertEquals($wpdb->prefix . 'afi_job_items', $table_names['job_items']);
    }
    
    /**
     * Test JSON field handling
     */
    public function test_json_field_handling() {
        $this->database->create_tables();
        
        $complex_filters = array(
            'use_all_images' => false,
            'date_range' => array(
                'start' => '2023-01-01',
                'end' => '2023-12-31'
            ),
            'keyword' => 'test',
            'keyword_fields' => array('filename', 'title', 'alt')
        );
        
        $job_id = $this->database->create_job(array(
            'post_types' => array('post', 'page', 'product'),
            'image_filters' => $complex_filters
        ));
        
        $job = $this->database->get_job($job_id);
        
        $this->assertIsArray($job->post_types, 'Post types should be decoded as array');
        $this->assertIsArray($job->image_filters, 'Image filters should be decoded as array');
        $this->assertEquals(array('post', 'page', 'product'), $job->post_types);
        $this->assertEquals($complex_filters, $job->image_filters);
    }
}