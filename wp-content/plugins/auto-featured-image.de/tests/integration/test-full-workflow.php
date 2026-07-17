<?php
/**
 * Full Workflow Integration Tests
 *
 * Tests the complete workflow from post creation to featured image assignment.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Full Workflow Integration Test Class
 */
class Test_Auto_Featured_Image_Full_Workflow extends Auto_Featured_Image_Test_Case {

    /**
     * Test complete workflow for post with images
     */
    public function test_complete_workflow_with_images() {
        // Create a post with embedded images
        $post_data = $this->test_factory->create_post_with_images( array(
            'image_count' => 3,
            'post_title' => 'Test Post with Multiple Images',
        ) );

        $post_id = $post_data['post_id'];
        $image_ids = $post_data['image_ids'];

        // Verify post has no featured image initially
        $this->assertPostHasNoFeaturedImage( $post_id );

        // Trigger the workflow
        $this->trigger_action( 'save_post', $post_id );

        // Verify job was added to queue
        $this->assertJobExists( $post_id, 'pending' );

        // Process the job
        $job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $job );
        $this->assertEquals( $post_id, $job['post_id'] );

        // Simulate job processing
        $result = $this->plugin->processor->process_post( $post_id );
        $this->assertTrue( $result );

        // Complete the job
        $this->plugin->queue->complete_job( $job['id'] );

        // Verify post now has featured image
        $this->assertPostHasFeaturedImage( $post_id );

        // Verify featured image is one of the embedded images
        $featured_image_id = get_post_thumbnail_id( $post_id );
        $this->assertContains( $featured_image_id, $image_ids );

        // Verify job is completed
        $this->assertJobExists( $post_id, 'completed' );

        // Verify logs were created
        $this->assertLogExists( 'info', 'Featured image assigned', $post_id );
    }

    /**
     * Test workflow for post without images
     */
    public function test_workflow_without_images() {
        // Create a post without images
        $post_id = $this->create_test_post( array(
            'post_content' => '<p>This post has no images at all.</p>',
        ) );

        // Trigger the workflow
        $this->trigger_action( 'save_post', $post_id );

        // Verify job was added
        $this->assertJobExists( $post_id, 'pending' );

        // Process the job
        $job = $this->plugin->queue->get_next_job();
        $result = $this->plugin->processor->process_post( $post_id );

        // Should fail gracefully
        $this->assertFalse( $result );

        // Post should still have no featured image
        $this->assertPostHasNoFeaturedImage( $post_id );

        // Job should be marked as failed
        $this->plugin->queue->fail_job( $job['id'], 'No suitable images found' );
        $this->assertJobExists( $post_id, 'failed' );

        // Verify appropriate log entry
        $this->assertLogExists( 'warning', 'No suitable images found', $post_id );
    }

    /**
     * Test batch processing workflow
     */
    public function test_batch_processing_workflow() {
        // Create multiple posts
        $batch_data = $this->test_factory->create_batch_scenario( array(
            'post_count' => 10,
            'posts_with_images' => 7,
            'posts_with_featured_images' => 2,
        ) );

        // Add all posts to queue
        foreach ( $batch_data['all_posts'] as $post_id ) {
            $this->plugin->queue->add_job( $post_id );
        }

        // Verify initial queue state
        $initial_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 10, $initial_stats['total'] );
        $this->assertEquals( 10, $initial_stats['pending'] );

        // Process batch
        $batch_size = 5;
        $processed_jobs = $this->plugin->queue->process_next_batch( $batch_size );

        // Verify batch processing results
        $this->assertIsArray( $processed_jobs );
        $this->assertLessThanOrEqual( $batch_size, count( $processed_jobs ) );

        // Check updated queue stats
        $updated_stats = $this->plugin->queue->get_queue_stats();
        $this->assertGreaterThan( 0, $updated_stats['processing'] + $updated_stats['completed'] + $updated_stats['failed'] );

        // Process remaining jobs
        while ( $job = $this->plugin->queue->get_next_job() ) {
            $result = $this->plugin->processor->process_post( $job['post_id'] );
            
            if ( $result ) {
                $this->plugin->queue->complete_job( $job['id'] );
            } else {
                $this->plugin->queue->fail_job( $job['id'], 'Processing failed' );
            }
        }

        // Verify final state
        $final_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 10, $final_stats['total'] );
        $this->assertEquals( 0, $final_stats['pending'] );
        $this->assertEquals( 0, $final_stats['processing'] );

        // Count posts that should have gotten featured images
        $posts_with_new_featured_images = 0;
        foreach ( $batch_data['posts_with_images'] as $post_id ) {
            if ( ! in_array( $post_id, $batch_data['posts_with_featured_images'] ) ) {
                if ( has_post_thumbnail( $post_id ) ) {
                    $posts_with_new_featured_images++;
                }
            }
        }

        $this->assertGreaterThan( 0, $posts_with_new_featured_images );
    }

    /**
     * Test error handling in workflow
     */
    public function test_error_handling_workflow() {
        $post_id = $this->create_test_post();

        // Mock a database error
        $this->mock_wp_function( 'wp_insert_post', new WP_Error( 'db_error', 'Database error' ) );

        // Trigger workflow
        $this->trigger_action( 'save_post', $post_id );

        // Process job (should handle error gracefully)
        $job = $this->plugin->queue->get_next_job();
        if ( $job ) {
            $result = $this->plugin->processor->process_post( $post_id );
            
            // Should handle error and not crash
            $this->assertFalse( $result );
            
            // Error should be logged
            $this->assertLogExists( 'error', 'Database error', $post_id );
        }
    }

    /**
     * Test performance with realistic load
     *
     * @group performance
     */
    public function test_performance_realistic_load() {
        $this->skip_if_requirements_not_met( array(
            'function' => 'memory_get_peak_usage',
        ) );

        $start_time = microtime( true );
        $start_memory = memory_get_usage( true );

        // Create realistic scenario
        $post_count = 25;
        $posts_with_images = 20;

        $post_ids = array();

        // Create posts with varying image counts
        for ( $i = 0; $i < $posts_with_images; $i++ ) {
            $image_count = rand( 1, 5 );
            $post_data = $this->test_factory->create_post_with_images( array(
                'image_count' => $image_count,
            ) );
            $post_ids[] = $post_data['post_id'];
        }

        // Create posts without images
        for ( $i = 0; $i < ( $post_count - $posts_with_images ); $i++ ) {
            $post_ids[] = $this->create_test_post( array(
                'post_content' => '<p>Post without images.</p>',
            ) );
        }

        $setup_time = microtime( true ) - $start_time;

        // Add all to queue and process
        $processing_start = microtime( true );

        foreach ( $post_ids as $post_id ) {
            $this->plugin->queue->add_job( $post_id );
        }

        // Process all jobs
        $processed_count = 0;
        while ( $job = $this->plugin->queue->get_next_job() ) {
            $result = $this->plugin->processor->process_post( $job['post_id'] );
            
            if ( $result ) {
                $this->plugin->queue->complete_job( $job['id'] );
            } else {
                $this->plugin->queue->fail_job( $job['id'], 'No suitable images' );
            }
            
            $processed_count++;
        }

        $processing_time = microtime( true ) - $processing_start;
        $total_time = microtime( true ) - $start_time;
        $memory_used = memory_get_usage( true ) - $start_memory;
        $peak_memory = memory_get_peak_usage( true );

        // Performance assertions
        $this->assertEquals( $post_count, $processed_count, 'All posts should be processed' );
        $this->assertLessThan( 30.0, $total_time, 'Total processing should complete within 30 seconds' );
        $this->assertLessThan( 20 * 1024 * 1024, $memory_used, 'Memory usage should be under 20MB' );
        $this->assertLessThan( 50 * 1024 * 1024, $peak_memory, 'Peak memory should be under 50MB' );

        // Verify results
        $final_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( $post_count, $final_stats['total'] );
        $this->assertGreaterThan( 0, $final_stats['completed'] );

        // Count successful assignments
        $successful_assignments = 0;
        foreach ( $post_ids as $post_id ) {
            if ( has_post_thumbnail( $post_id ) ) {
                $successful_assignments++;
            }
        }

        $this->assertGreaterThan( 0, $successful_assignments );
        $this->assertLessThanOrEqual( $posts_with_images, $successful_assignments );

        // Log performance metrics
        $this->plugin->logger->info( 'Performance test completed', array(
            'post_count' => $post_count,
            'setup_time' => round( $setup_time, 3 ),
            'processing_time' => round( $processing_time, 3 ),
            'total_time' => round( $total_time, 3 ),
            'memory_used_mb' => round( $memory_used / 1024 / 1024, 2 ),
            'peak_memory_mb' => round( $peak_memory / 1024 / 1024, 2 ),
            'successful_assignments' => $successful_assignments,
        ) );
    }

    /**
     * Test cron job integration
     */
    public function test_cron_job_integration() {
        // Create posts and add to queue
        $post_ids = array();
        for ( $i = 0; $i < 3; $i++ ) {
            $post_data = $this->test_factory->create_post_with_images();
            $post_ids[] = $post_data['post_id'];
            $this->plugin->queue->add_job( $post_data['post_id'] );
        }

        // Verify jobs are pending
        $initial_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 3, $initial_stats['pending'] );

        // Simulate cron job execution
        $this->test_utils->simulate_cron_execution( 'auto_featured_image_process_queue' );

        // Verify jobs were processed
        $final_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 0, $final_stats['pending'] );
        $this->assertGreaterThan( 0, $final_stats['completed'] + $final_stats['failed'] );
    }

    /**
     * Test AJAX integration
     */
    public function test_ajax_integration() {
        $post_id = $this->create_test_post();

        // Simulate AJAX request to process single post
        $response = $this->test_utils->simulate_ajax_request( 'auto_featured_image_process_post', array(
            'post_id' => $post_id,
            'nonce' => wp_create_nonce( 'auto_featured_image_ajax' ),
        ) );

        $this->assertIsArray( $response );
        $this->assertTrue( isset( $response['success'] ) );
    }

    /**
     * Test multisite compatibility
     *
     * @group multisite
     */
    public function test_multisite_compatibility() {
        if ( ! is_multisite() ) {
            $this->markTestSkipped( 'Multisite tests require multisite installation' );
        }

        // Test plugin functionality across different sites
        $site_id = $this->factory->blog->create();
        switch_to_blog( $site_id );

        // Plugin should work on new site
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        $job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $job );
        $this->assertEquals( $post_id, $job['post_id'] );

        restore_current_blog();
    }
}
