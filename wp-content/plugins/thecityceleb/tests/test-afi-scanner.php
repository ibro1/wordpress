<?php
/**
 * Unit tests for AFI_Scanner class
 *
 * Tests batch processing capabilities, progress tracking,
 * and post identification functionality.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

class AFI_Scanner_Test extends WP_UnitTestCase {
    
    /**
     * Scanner instance
     *
     * @var AFI_Scanner
     */
    private $scanner;
    
    /**
     * Mock database instance
     *
     * @var AFI_Database
     */
    private $mock_database;
    
    /**
     * Test job ID
     *
     * @var int
     */
    private $test_job_id = 1;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mock database
        $this->mock_database = $this->createMock('AFI_Database');
        
        // Create scanner instance with mock database
        $this->scanner = new AFI_Scanner($this->mock_database);
        
        // Create test posts
        $this->create_test_posts();
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up test posts
        $this->cleanup_test_posts();
        
        parent::tearDown();
    }
    
    /**
     * Test batch scanning with posts that need featured images
     */
    public function test_scan_posts_batch_with_posts_needing_images() {
        // Mock job data
        $mock_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'scanning',
            'total_items' => 0
        );
        
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with($this->test_job_id)
            ->willReturn($mock_job);
        
        $this->mock_database
            ->expects($this->atLeastOnce())
            ->method('create_job_item')
            ->willReturn(true);
        
        $this->mock_database
            ->expects($this->once())
            ->method('update_job')
            ->willReturn(true);
        
        // Run scan
        $result = $this->scanner->scan_posts_batch($this->test_job_id, array('post'), 1);
        
        // Assertions
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['posts_found']);
        $this->assertGreaterThan(0, $result['items_created']);
        $this->assertEquals(1, $result['current_page']);
        $this->assertArrayHasKey('has_more', $result);
    }
    
    /**
     * Test batch scanning with invalid job ID
     */
    public function test_scan_posts_batch_with_invalid_job() {
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with(999)
            ->willReturn(null);
        
        $result = $this->scanner->scan_posts_batch(999, array('post'), 1);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Job not found', $result['error']);
        $this->assertEquals(0, $result['posts_found']);
        $this->assertEquals(0, $result['items_created']);
    }
    
    /**
     * Test batch scanning with invalid parameters
     */
    public function test_scan_posts_batch_with_invalid_parameters() {
        // Test with empty post types
        $result = $this->scanner->scan_posts_batch($this->test_job_id, array(), 1);
        $this->assertFalse($result['success']);
        $this->assertStringContains('Invalid parameters', $result['error']);
        
        // Test with null job ID
        $result = $this->scanner->scan_posts_batch(null, array('post'), 1);
        $this->assertFalse($result['success']);
        $this->assertStringContains('Invalid parameters', $result['error']);
        
        // Test with non-array post types
        $result = $this->scanner->scan_posts_batch($this->test_job_id, 'post', 1);
        $this->assertFalse($result['success']);
        $this->assertStringContains('Invalid parameters', $result['error']);
    }
    
    /**
     * Test counting posts without featured images
     */
    public function test_count_posts_without_featured_image() {
        $count = $this->scanner->count_posts_without_featured_image(array('post'));
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
    
    /**
     * Test counting with invalid post types
     */
    public function test_count_posts_with_invalid_post_types() {
        // Test with empty array
        $count = $this->scanner->count_posts_without_featured_image(array());
        $this->assertEquals(0, $count);
        
        // Test with non-array
        $count = $this->scanner->count_posts_without_featured_image('post');
        $this->assertEquals(0, $count);
        
        // Test with null
        $count = $this->scanner->count_posts_without_featured_image(null);
        $this->assertEquals(0, $count);
    }
    
    /**
     * Test scan completion check
     */
    public function test_is_scan_complete() {
        // Mock completed job
        $completed_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'pending'
        );
        
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with($this->test_job_id)
            ->willReturn($completed_job);
        
        $result = $this->scanner->is_scan_complete($this->test_job_id);
        $this->assertTrue($result);
        
        // Test with non-existent job
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with(999)
            ->willReturn(null);
        
        $result = $this->scanner->is_scan_complete(999);
        $this->assertFalse($result);
    }
    
    /**
     * Test scan progress tracking
     */
    public function test_get_scan_progress() {
        $mock_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'scanning',
            'total_items' => 50,
            'post_types' => array('post'),
            'created_at' => current_time('mysql')
        );
        
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with($this->test_job_id)
            ->willReturn($mock_job);
        
        $result = $this->scanner->get_scan_progress($this->test_job_id);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($this->test_job_id, $result['job_id']);
        $this->assertEquals('scanning', $result['status']);
        $this->assertEquals(50, $result['total_items_found']);
        $this->assertArrayHasKey('progress_percentage', $result);
        $this->assertArrayHasKey('estimated_total_posts', $result);
        $this->assertArrayHasKey('is_complete', $result);
    }
    
    /**
     * Test scan progress with invalid job
     */
    public function test_get_scan_progress_with_invalid_job() {
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with(999)
            ->willReturn(null);
        
        $result = $this->scanner->get_scan_progress(999);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Job not found', $result['error']);
    }
    
    /**
     * Test resume scan functionality
     */
    public function test_resume_scan() {
        $paused_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'paused',
            'total_items' => 100
        );
        
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with($this->test_job_id)
            ->willReturn($paused_job);
        
        $result = $this->scanner->resume_scan($this->test_job_id);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('next_page', $result);
        $this->assertArrayHasKey('scanned_posts', $result);
        $this->assertEquals(100, $result['total_items_found']);
    }
    
    /**
     * Test resume scan with non-paused job
     */
    public function test_resume_scan_with_non_paused_job() {
        $running_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'running'
        );
        
        $this->mock_database
            ->expects($this->once())
            ->method('get_job')
            ->with($this->test_job_id)
            ->willReturn($running_job);
        
        $result = $this->scanner->resume_scan($this->test_job_id);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Job is not paused', $result['error']);
    }
    
    /**
     * Test batch size management
     */
    public function test_batch_size_management() {
        // Test default batch size
        $this->assertEquals(1000, $this->scanner->get_batch_size());
        
        // Test setting valid batch size
        $this->assertTrue($this->scanner->set_batch_size(500));
        $this->assertEquals(500, $this->scanner->get_batch_size());
        
        // Test setting invalid batch sizes
        $this->assertFalse($this->scanner->set_batch_size(50)); // Too small
        $this->assertFalse($this->scanner->set_batch_size(3000)); // Too large
        $this->assertEquals(500, $this->scanner->get_batch_size()); // Should remain unchanged
        
        // Test batch size limits
        $limits = $this->scanner->get_batch_size_limits();
        $this->assertArrayHasKey('min', $limits);
        $this->assertArrayHasKey('max', $limits);
        $this->assertArrayHasKey('current', $limits);
        $this->assertEquals(100, $limits['min']);
        $this->assertEquals(2000, $limits['max']);
        $this->assertEquals(500, $limits['current']);
    }
    
    /**
     * Test batch size optimization
     */
    public function test_optimize_batch_size() {
        $original_size = $this->scanner->get_batch_size();
        $optimized_size = $this->scanner->optimize_batch_size();
        
        $this->assertIsInt($optimized_size);
        $this->assertGreaterThanOrEqual(100, $optimized_size);
        $this->assertLessThanOrEqual(2000, $optimized_size);
        $this->assertEquals($optimized_size, $this->scanner->get_batch_size());
    }
    
    /**
     * Test scanner statistics
     */
    public function test_get_statistics() {
        $stats = $this->scanner->get_statistics();
        
        $this->assertArrayHasKey('batch_size', $stats);
        $this->assertArrayHasKey('batch_size_limits', $stats);
        $this->assertArrayHasKey('memory_limit', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        $this->assertArrayHasKey('memory_peak', $stats);
        
        $this->assertIsInt($stats['batch_size']);
        $this->assertIsArray($stats['batch_size_limits']);
        $this->assertIsInt($stats['memory_limit']);
        $this->assertIsInt($stats['memory_usage']);
        $this->assertIsInt($stats['memory_peak']);
    }
    
    /**
     * Test memory usage during batch processing
     */
    public function test_memory_usage_during_batch_processing() {
        $initial_memory = memory_get_usage(true);
        
        // Mock job for memory test
        $mock_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'scanning',
            'total_items' => 0
        );
        
        $this->mock_database
            ->method('get_job')
            ->willReturn($mock_job);
        
        $this->mock_database
            ->method('create_job_item')
            ->willReturn(true);
        
        $this->mock_database
            ->method('update_job')
            ->willReturn(true);
        
        // Process multiple batches
        for ($i = 1; $i <= 3; $i++) {
            $this->scanner->scan_posts_batch($this->test_job_id, array('post'), $i);
        }
        
        $final_memory = memory_get_usage(true);
        $memory_increase = $final_memory - $initial_memory;
        
        // Memory increase should be reasonable (less than 10MB for test)
        $this->assertLessThan(10 * 1024 * 1024, $memory_increase);
    }
    
    /**
     * Test progress tracking accuracy
     */
    public function test_progress_tracking_accuracy() {
        $mock_job = (object) array(
            'id' => $this->test_job_id,
            'status' => 'scanning',
            'total_items' => 25,
            'post_types' => array('post'),
            'created_at' => current_time('mysql')
        );
        
        $this->mock_database
            ->method('get_job')
            ->willReturn($mock_job);
        
        $progress = $this->scanner->get_scan_progress($this->test_job_id);
        
        $this->assertTrue($progress['success']);
        $this->assertIsFloat($progress['progress_percentage']);
        $this->assertGreaterThanOrEqual(0, $progress['progress_percentage']);
        $this->assertLessThanOrEqual(100, $progress['progress_percentage']);
    }
    
    /**
     * Create test posts for scanning
     */
    private function create_test_posts() {
        // Create posts without featured images
        for ($i = 1; $i <= 5; $i++) {
            wp_insert_post(array(
                'post_title' => "Test Post Without Featured Image {$i}",
                'post_content' => 'Test content',
                'post_status' => 'publish',
                'post_type' => 'post'
            ));
        }
        
        // Create posts with featured images
        for ($i = 1; $i <= 3; $i++) {
            $post_id = wp_insert_post(array(
                'post_title' => "Test Post With Featured Image {$i}",
                'post_content' => 'Test content',
                'post_status' => 'publish',
                'post_type' => 'post'
            ));
            
            // Create a test attachment
            $attachment_id = wp_insert_post(array(
                'post_title' => "Test Image {$i}",
                'post_content' => '',
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'post_mime_type' => 'image/jpeg'
            ));
            
            // Set as featured image
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
    
    /**
     * Clean up test posts
     */
    private function cleanup_test_posts() {
        $posts = get_posts(array(
            'post_type' => array('post', 'attachment'),
            'post_status' => array('publish', 'inherit'),
            'numberposts' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'post_title',
                    'value' => 'Test Post',
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'post_title',
                    'value' => 'Test Image',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
}