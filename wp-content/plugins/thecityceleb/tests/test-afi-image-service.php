<?php
/**
 * Unit tests for AFI_Image_Service class
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

class AFI_Image_Service_Test extends WP_UnitTestCase {
    
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
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load the class
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-service.php';
        
        $this->image_service = new AFI_Image_Service();
        
        // Create test images
        $this->create_test_images();
    }
    
    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        // Clean up test images
        foreach ($this->test_images as $image_id) {
            wp_delete_attachment($image_id, true);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test getting filtered images with no filters
     */
    public function test_get_filtered_images_no_filters() {
        $images = $this->image_service->get_filtered_images();
        
        $this->assertIsArray($images);
        $this->assertGreaterThanOrEqual(count($this->test_images), count($images));
        
        // Check that our test images are included
        foreach ($this->test_images as $test_image_id) {
            $this->assertContains($test_image_id, $images);
        }
    }
    
    /**
     * Test getting filtered images with limit
     */
    public function test_get_filtered_images_with_limit() {
        $limit = 2;
        $images = $this->image_service->get_filtered_images(array(), $limit);
        
        $this->assertIsArray($images);
        $this->assertLessThanOrEqual($limit, count($images));
    }
    
    /**
     * Test getting filtered images with offset
     */
    public function test_get_filtered_images_with_offset() {
        $offset = 1;
        $all_images = $this->image_service->get_filtered_images();
        $offset_images = $this->image_service->get_filtered_images(array(), -1, $offset);
        
        $this->assertIsArray($offset_images);
        $this->assertEquals(count($all_images) - $offset, count($offset_images));
    }
    
    /**
     * Test getting filtered image count
     */
    public function test_get_filtered_image_count() {
        $count = $this->image_service->get_filtered_image_count();
        
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(count($this->test_images), $count);
    }
    
    /**
     * Test date filter application
     */
    public function test_apply_date_filter() {
        $query_args = array('post_type' => 'attachment');
        $date_range = array(
            'start' => '2023-01-01',
            'end' => '2023-12-31'
        );
        
        $filtered_args = $this->image_service->apply_date_filter($query_args, $date_range);
        
        $this->assertArrayHasKey('date_query', $filtered_args);
        $this->assertIsArray($filtered_args['date_query']);
    }
    
    /**
     * Test keyword filter application
     */
    public function test_apply_keyword_filter() {
        $query_args = array('post_type' => 'attachment');
        $keyword = 'test';
        
        $filtered_args = $this->image_service->apply_keyword_filter($query_args, $keyword);
        
        $this->assertArrayHasKey('s', $filtered_args);
        $this->assertEquals($keyword, $filtered_args['s']);
    }
    
    /**
     * Test keyword filter with alt text search
     */
    public function test_apply_keyword_filter_with_alt_text() {
        $query_args = array('post_type' => 'attachment');
        $keyword = 'test';
        $fields = array('alt');
        
        $filtered_args = $this->image_service->apply_keyword_filter($query_args, $keyword, $fields);
        
        $this->assertArrayHasKey('meta_query', $filtered_args);
        $this->assertIsArray($filtered_args['meta_query']);
    }
    
    /**
     * Test cache functionality
     */
    public function test_cache_functionality() {
        $filters = array('keyword' => 'test');
        $count = 5;
        
        // Cache the count
        $result = $this->image_service->cache_image_count($filters, $count);
        $this->assertTrue($result);
        
        // Retrieve from cache
        $cached_count = $this->image_service->get_cached_image_count($filters);
        $this->assertEquals($count, $cached_count);
        
        // Clear cache
        $this->image_service->clear_image_count_cache($filters);
        $cleared_count = $this->image_service->get_cached_image_count($filters);
        $this->assertFalse($cleared_count);
    }
    
    /**
     * Test efficient random image selection
     */
    public function test_get_random_images_efficient() {
        $count = 2;
        $random_images = $this->image_service->get_random_images_efficient(array(), $count);
        
        $this->assertIsArray($random_images);
        $this->assertLessThanOrEqual($count, count($random_images));
        
        // Check that returned IDs are valid
        foreach ($random_images as $image_id) {
            $this->assertIsInt($image_id);
            $this->assertGreaterThan(0, $image_id);
        }
    }
    
    /**
     * Test single random image selection
     */
    public function test_get_single_random_image() {
        $random_image = $this->image_service->get_single_random_image();
        
        if ($random_image !== false) {
            $this->assertIsInt($random_image);
            $this->assertGreaterThan(0, $random_image);
        }
    }
    
    /**
     * Test image validation
     */
    public function test_validate_image() {
        // Test with valid image
        $valid_result = $this->image_service->validate_image($this->test_images[0]);
        $this->assertTrue($valid_result);
        
        // Test with invalid image ID
        $invalid_result = $this->image_service->validate_image(999999);
        $this->assertFalse($invalid_result);
        
        // Test with non-numeric ID
        $non_numeric_result = $this->image_service->validate_image('invalid');
        $this->assertFalse($non_numeric_result);
    }
    
    /**
     * Test image metadata retrieval
     */
    public function test_get_image_metadata() {
        $metadata = $this->image_service->get_image_metadata($this->test_images[0]);
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('id', $metadata);
        $this->assertArrayHasKey('title', $metadata);
        $this->assertArrayHasKey('filename', $metadata);
        $this->assertArrayHasKey('url', $metadata);
        
        $this->assertEquals($this->test_images[0], $metadata['id']);
    }
    
    /**
     * Test cache statistics
     */
    public function test_get_cache_statistics() {
        $stats = $this->image_service->get_cache_statistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_group', $stats);
        $this->assertArrayHasKey('cache_expiration', $stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
    }
    
    /**
     * Test setting cache expiration
     */
    public function test_set_cache_expiration() {
        $new_expiration = 7200; // 2 hours
        
        $result = $this->image_service->set_cache_expiration($new_expiration);
        $this->assertTrue($result);
        
        $stats = $this->image_service->get_cache_statistics();
        $this->assertEquals($new_expiration, $stats['cache_expiration']);
        
        // Test invalid expiration
        $invalid_result = $this->image_service->set_cache_expiration(-1);
        $this->assertFalse($invalid_result);
    }
    
    /**
     * Test supported mime types
     */
    public function test_get_supported_mime_types() {
        $mime_types = $this->image_service->get_supported_mime_types();
        
        $this->assertIsArray($mime_types);
        $this->assertContains('image/jpeg', $mime_types);
        $this->assertContains('image/png', $mime_types);
        $this->assertContains('image/gif', $mime_types);
    }
    
    /**
     * Test mime type filtering
     */
    public function test_filter_by_mime_type() {
        $query_args = array('post_type' => 'attachment');
        $mime_types = array('image/jpeg', 'image/png');
        
        $filtered_args = $this->image_service->filter_by_mime_type($query_args, $mime_types);
        
        $this->assertArrayHasKey('post_mime_type', $filtered_args);
        $this->assertEquals($mime_types, $filtered_args['post_mime_type']);
    }
    
    /**
     * Create test images for testing
     */
    private function create_test_images() {
        // Create test image files
        $upload_dir = wp_upload_dir();
        
        for ($i = 1; $i <= 3; $i++) {
            // Create a simple test image
            $image_data = $this->create_test_image_data();
            $filename = "test-image-{$i}.jpg";
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            file_put_contents($file_path, $image_data);
            
            // Create attachment
            $attachment_data = array(
                'post_title' => "Test Image {$i}",
                'post_content' => "Test image {$i} description",
                'post_status' => 'inherit',
                'post_mime_type' => 'image/jpeg'
            );
            
            $attachment_id = wp_insert_attachment($attachment_data, $file_path);
            
            if (!is_wp_error($attachment_id)) {
                // Set alt text
                update_post_meta($attachment_id, '_wp_attachment_image_alt', "Test alt text {$i}");
                
                // Generate metadata
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
                
                $this->test_images[] = $attachment_id;
            }
        }
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