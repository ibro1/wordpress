<?php
/**
 * Unit tests for AFI_Processor class
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

class AFI_Processor_Test extends WP_UnitTestCase {
    
    /**
     * Processor instance
     *
     * @var AFI_Processor
     */
    private $processor;
    
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
     * Test image IDs
     *
     * @var array
     */
    private $test_images = array();
    
    /**
     * Test post IDs
     *
     * @var array
     */
    private $test_posts = array();
    
    /**
     * Test job ID
     *
     * @var int
     */
    private $test_job_id;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load required classes
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-service.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-processor.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-item-model.php';
        
        $this->database = new AFI_Database();
        $this->image_service = new AFI_Image_Service();
        $this->processor = new AFI_Processor($this->database, $this->image_service);
        
        // Create test data
        $this->create_test_data();
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        // Clean up test images
        foreach ($this->test_images as $image_id) {
            wp_delete_attachment($image_id, true);
        }
        
        // Clean up test posts
        foreach ($this->test_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test processing a single job item successfully
     */
    public function test_process_job_item_success() {
        // Create a job item
        $item_data = array(
            'job_id' => $this->test_job_id,
            'post_id' => $this->test_posts[0],
            'status' => 'pending'
        );
        
        $item_id = $this->database->create_job_item($item_data);
        $this->assertNotFalse($item_id);
        
        // Process the item
        $result = $this->processor->process_job_item($item_id);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($item_id, $result['item_id']);
        $this->assertEquals($this->test_posts[0], $result['post_id']);
        $this->assertArrayHasKey('image_id', $result);
        
        // Verify featured image was set
        $this->assertTrue(has_post_thumbnail($this->test_posts[0]));
    }
    
    /**
     * Test processing job item with non-existent item ID
     */
    public function test_process_job_item_invalid_item() {
        $result = $this->processor->process_job_item(999999);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Job item not found', $result['error']);
    }
    
    /**
     * Test processing job item with non-existent post
     */
    public function test_process_job_item_invalid_post() {
        // Create a job item with invalid post ID
        $item_data = array(
            'job_id' => $this->test_job_id,
            'post_id' => 999999,
            'status' => 'pending'
        );
        
        $item_id = $this->database->create_job_item($item_data);
        $this->assertNotFalse($item_id);
        
        // Process the item
        $result = $this->processor->process_job_item($item_id);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Post no longer exists', $result['error']);
    }
    
    /**
     * Test processing job item that already has featured image
     */
    public function test_process_job_item_already_has_featured_image() {
        // Set featured image on test post
        set_post_thumbnail($this->test_posts[0], $this->test_images[0]);
        
        // Create a job item
        $item_data = array(
            'job_id' => $this->test_job_id,
            'post_id' => $this->test_posts[0],
            'status' => 'pending'
        );
        
        $item_id = $this->database->create_job_item($item_data);
        $this->assertNotFalse($item_id);
        
        // Process the item
        $result = $this->processor->process_job_item($item_id);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertTrue($result['skipped']);
        $this->assertEquals('Post already has featured image', $result['reason']);
    }
    
    /**
     * Test assigning random image to post
     */
    public function test_assign_random_image_success() {
        $result = $this->processor->assign_random_image($this->test_posts[0]);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('image_id', $result);
        $this->assertIsInt($result['image_id']);
        $this->assertGreaterThan(0, $result['image_id']);
        
        // Verify featured image was set
        $this->assertTrue(has_post_thumbnail($this->test_posts[0]));
        $this->assertEquals($result['image_id'], get_post_thumbnail_id($this->test_posts[0]));
    }
    
    /**
     * Test assigning random image with invalid post ID
     */
    public function test_assign_random_image_invalid_post() {
        $result = $this->processor->assign_random_image(999999);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid post ID', $result['error']);
    }
    
    /**
     * Test assigning random image with filters
     */
    public function test_assign_random_image_with_filters() {
        $filters = array(
            'keyword' => 'test'
        );
        
        $result = $this->processor->assign_random_image($this->test_posts[0], $filters);
        
        $this->assertIsArray($result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('image_id', $result);
            $this->assertTrue(has_post_thumbnail($this->test_posts[0]));
        }
    }
    
    /**
     * Test getting image batch for performance optimization
     */
    public function test_get_image_batch() {
        $batch_size = 5;
        $batch = $this->processor->get_image_batch(array(), $batch_size);
        
        $this->assertIsArray($batch);
        $this->assertLessThanOrEqual($batch_size, count($batch));
        
        // Verify all returned IDs are valid integers
        foreach ($batch as $image_id) {
            $this->assertIsInt($image_id);
            $this->assertGreaterThan(0, $image_id);
        }
    }
    
    /**
     * Test batch processing of multiple job items
     */
    public function test_process_job_items_batch() {
        $item_ids = array();
        
        // Create multiple job items
        for ($i = 0; $i < 3; $i++) {
            $item_data = array(
                'job_id' => $this->test_job_id,
                'post_id' => $this->test_posts[$i],
                'status' => 'pending'
            );
            
            $item_id = $this->database->create_job_item($item_data);
            $this->assertNotFalse($item_id);
            $item_ids[] = $item_id;
        }
        
        // Process batch
        $result = $this->processor->process_job_items_batch($item_ids);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertArrayHasKey('success_count', $result);
        $this->assertArrayHasKey('error_count', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(3, $result['results']);
    }
    
    /**
     * Test batch processing with empty array
     */
    public function test_process_job_items_batch_empty() {
        $result = $this->processor->process_job_items_batch(array());
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('No job items provided', $result['error']);
    }
    
    /**
     * Test clearing image batch cache
     */
    public function test_clear_image_batch_cache() {
        // Get a batch to populate cache
        $this->processor->get_image_batch(array(), 5);
        
        // Clear cache
        $result = $this->processor->clear_image_batch_cache();
        $this->assertTrue($result);
        
        // Clear specific cache
        $filters = array('keyword' => 'test');
        $result = $this->processor->clear_image_batch_cache($filters);
        $this->assertTrue($result);
    }
    
    /**
     * Test processor statistics
     */
    public function test_get_statistics() {
        $stats = $this->processor->get_statistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('image_batch_size', $stats);
        $this->assertArrayHasKey('max_retry_attempts', $stats);
        $this->assertArrayHasKey('batch_cache_expiration', $stats);
        $this->assertArrayHasKey('cached_batches', $stats);
        $this->assertArrayHasKey('cache_memory_usage', $stats);
    }
    
    /**
     * Test setting image batch size
     */
    public function test_set_image_batch_size() {
        $new_size = 50;
        
        $result = $this->processor->set_image_batch_size($new_size);
        $this->assertTrue($result);
        $this->assertEquals($new_size, $this->processor->get_image_batch_size());
        
        // Test invalid size
        $invalid_result = $this->processor->set_image_batch_size(5); // Too small
        $this->assertFalse($invalid_result);
        
        $invalid_result = $this->processor->set_image_batch_size(1000); // Too large
        $this->assertFalse($invalid_result);
    }
    
    /**
     * Test setting maximum retry attempts
     */
    public function test_set_max_retry_attempts() {
        $new_attempts = 5;
        
        $result = $this->processor->set_max_retry_attempts($new_attempts);
        $this->assertTrue($result);
        $this->assertEquals($new_attempts, $this->processor->get_max_retry_attempts());
        
        // Test invalid attempts
        $invalid_result = $this->processor->set_max_retry_attempts(0); // Too small
        $this->assertFalse($invalid_result);
        
        $invalid_result = $this->processor->set_max_retry_attempts(15); // Too large
        $this->assertFalse($invalid_result);
    }
    
    /**
     * Test setting batch cache expiration
     */
    public function test_set_batch_cache_expiration() {
        $new_expiration = 600; // 10 minutes
        
        $result = $this->processor->set_batch_cache_expiration($new_expiration);
        $this->assertTrue($result);
        $this->assertEquals($new_expiration, $this->processor->get_batch_cache_expiration());
        
        // Test invalid expiration
        $invalid_result = $this->processor->set_batch_cache_expiration(30); // Too small
        $this->assertFalse($invalid_result);
        
        $invalid_result = $this->processor->set_batch_cache_expiration(7200); // Too large
        $this->assertFalse($invalid_result);
    }
    
    /**
     * Test processor configuration validation
     */
    public function test_validate_configuration() {
        $validation = $this->processor->validate_configuration();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('issues', $validation);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['issues']);
    }
    
    /**
     * Create test data for testing
     */
    private function create_test_data() {
        // Create test images
        $this->create_test_images();
        
        // Create test posts
        $this->create_test_posts();
        
        // Create test job
        $this->create_test_job();
    }
    
    /**
     * Create test images
     */
    private function create_test_images() {
        $upload_dir = wp_upload_dir();
        
        for ($i = 1; $i <= 3; $i++) {
            // Create a simple test image
            $image_data = $this->create_test_image_data();
            $filename = "test-processor-image-{$i}.jpg";
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            file_put_contents($file_path, $image_data);
            
            // Create attachment
            $attachment_data = array(
                'post_title' => "Test Processor Image {$i}",
                'post_content' => "Test processor image {$i} description",
                'post_status' => 'inherit',
                'post_mime_type' => 'image/jpeg'
            );
            
            $attachment_id = wp_insert_attachment($attachment_data, $file_path);
            
            if (!is_wp_error($attachment_id)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', "Test processor alt text {$i}");
                
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
                
                $this->test_images[] = $attachment_id;
            }
        }
    }
    
    /**
     * Create test posts
     */
    private function create_test_posts() {
        for ($i = 1; $i <= 3; $i++) {
            $post_id = $this->factory->post->create(array(
                'post_title' => "Test Processor Post {$i}",
                'post_content' => "Test processor post {$i} content",
                'post_status' => 'publish'
            ));
            
            $this->test_posts[] = $post_id;
        }
    }
    
    /**
     * Create test job
     */
    private function create_test_job() {
        $job_data = array(
            'status' => 'running',
            'post_types' => array('post'),
            'image_filters' => array(),
            'total_items' => 0,
            'processed_items' => 0,
            'created_at' => current_time('mysql')
        );
        
        $this->test_job_id = $this->database->create_job($job_data);
    }
    
    /**
     * Create minimal test image data
     *
     * @return string Binary image data
     */
    private function create_test_image_data() {
        // Create a minimal 1x1 pixel JPEG
        return base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A');
    }
}