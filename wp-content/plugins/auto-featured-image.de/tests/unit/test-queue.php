<?php
/**
 * Queue Tests
 *
 * Tests for the job queue functionality.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Queue Test Class
 */
class Test_Auto_Featured_Image_Queue extends Auto_Featured_Image_Test_Case {

    /**
     * Test adding job to queue
     */
    public function test_add_job_to_queue() {
        $post_id = $this->create_test_post();
        
        $result = $this->plugin->queue->add_job( $post_id );
        $this->assertTrue( $result );

        // Verify job was added
        $this->assertJobExists( $post_id, 'pending' );
    }

    /**
     * Test adding job with priority
     */
    public function test_add_job_with_priority() {
        $post_id = $this->create_test_post();
        
        $result = $this->plugin->queue->add_job( $post_id, 5 ); // High priority
        $this->assertTrue( $result );

        $job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertEquals( 5, $job['priority'] );
    }

    /**
     * Test duplicate job prevention
     */
    public function test_duplicate_job_prevention() {
        $post_id = $this->create_test_post();
        
        // Add job first time
        $result1 = $this->plugin->queue->add_job( $post_id );
        $this->assertTrue( $result1 );

        // Try to add same job again
        $result2 = $this->plugin->queue->add_job( $post_id );
        $this->assertFalse( $result2 );

        // Should still have only one job
        $stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 1, $stats['total'] );
    }

    /**
     * Test getting next job
     */
    public function test_get_next_job() {
        // Add multiple jobs with different priorities
        $post_id_1 = $this->create_test_post();
        $post_id_2 = $this->create_test_post();
        $post_id_3 = $this->create_test_post();

        $this->plugin->queue->add_job( $post_id_1, 10 ); // Low priority
        $this->plugin->queue->add_job( $post_id_2, 5 );  // High priority
        $this->plugin->queue->add_job( $post_id_3, 7 );  // Medium priority

        // Should get highest priority job first
        $next_job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $next_job );
        $this->assertEquals( $post_id_2, $next_job['post_id'] );
        $this->assertEquals( 5, $next_job['priority'] );
    }

    /**
     * Test job processing
     */
    public function test_job_processing() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        // Start processing
        $job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $job );

        // Job should be marked as processing
        $updated_job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertEquals( 'processing', $updated_job['status'] );

        // Complete the job
        $result = $this->plugin->queue->complete_job( $job['id'] );
        $this->assertTrue( $result );

        // Job should be marked as completed
        $completed_job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertEquals( 'completed', $completed_job['status'] );
    }

    /**
     * Test job failure handling
     */
    public function test_job_failure_handling() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        $job = $this->plugin->queue->get_next_job();
        
        // Fail the job
        $result = $this->plugin->queue->fail_job( $job['id'], 'Test failure' );
        $this->assertTrue( $result );

        // Job should be marked as failed
        $failed_job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertEquals( 'failed', $failed_job['status'] );
        $this->assertStringContainsString( 'Test failure', $failed_job['error_message'] );
    }

    /**
     * Test job retry mechanism
     */
    public function test_job_retry_mechanism() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        $job = $this->plugin->queue->get_next_job();
        
        // Fail the job (should be retried)
        $this->plugin->queue->fail_job( $job['id'], 'Temporary failure' );

        // Job should be available for retry
        $retry_job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $retry_job );
        $this->assertEquals( $post_id, $retry_job['post_id'] );
        $this->assertEquals( 1, $retry_job['attempts'] );
    }

    /**
     * Test max retry limit
     */
    public function test_max_retry_limit() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        // Fail job multiple times to exceed retry limit
        for ( $i = 0; $i < 4; $i++ ) {
            $job = $this->plugin->queue->get_next_job();
            if ( $job ) {
                $this->plugin->queue->fail_job( $job['id'], "Failure attempt " . ( $i + 1 ) );
            }
        }

        // Should not get job anymore (exceeded max attempts)
        $next_job = $this->plugin->queue->get_next_job();
        $this->assertNull( $next_job );

        // Job should be permanently failed
        $final_job = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->assertEquals( 'failed', $final_job['status'] );
        $this->assertGreaterThanOrEqual( 3, $final_job['attempts'] );
    }

    /**
     * Test batch processing
     */
    public function test_batch_processing() {
        // Create multiple posts
        $post_ids = array();
        for ( $i = 0; $i < 5; $i++ ) {
            $post_ids[] = $this->create_test_post();
        }

        // Add all to queue
        foreach ( $post_ids as $post_id ) {
            $this->plugin->queue->add_job( $post_id );
        }

        // Process batch
        $batch_size = 3;
        $processed = $this->plugin->queue->process_next_batch( $batch_size );

        $this->assertIsArray( $processed );
        $this->assertLessThanOrEqual( $batch_size, count( $processed ) );

        // Verify some jobs were processed
        $stats = $this->plugin->queue->get_queue_stats();
        $this->assertGreaterThan( 0, $stats['processing'] + $stats['completed'] + $stats['failed'] );
    }

    /**
     * Test queue statistics
     */
    public function test_queue_statistics() {
        // Start with empty queue
        $stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 0, $stats['total'] );

        // Add jobs with different outcomes
        $post_ids = array();
        for ( $i = 0; $i < 4; $i++ ) {
            $post_ids[] = $this->create_test_post();
            $this->plugin->queue->add_job( $post_ids[ $i ] );
        }

        // Process some jobs
        $job1 = $this->plugin->queue->get_next_job();
        $this->plugin->queue->complete_job( $job1['id'] );

        $job2 = $this->plugin->queue->get_next_job();
        $this->plugin->queue->fail_job( $job2['id'], 'Test failure' );

        // Check updated statistics
        $updated_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 4, $updated_stats['total'] );
        $this->assertEquals( 2, $updated_stats['pending'] );
        $this->assertEquals( 1, $updated_stats['completed'] );
        $this->assertEquals( 1, $updated_stats['failed'] );
    }

    /**
     * Test queue clearing
     */
    public function test_queue_clearing() {
        // Add multiple jobs
        for ( $i = 0; $i < 3; $i++ ) {
            $post_id = $this->create_test_post();
            $this->plugin->queue->add_job( $post_id );
        }

        // Verify jobs exist
        $stats_before = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 3, $stats_before['total'] );

        // Clear queue
        $result = $this->plugin->queue->clear_queue();
        $this->assertTrue( $result );

        // Verify queue is empty
        $stats_after = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( 0, $stats_after['total'] );
    }

    /**
     * Test queue pausing and resuming
     */
    public function test_queue_pause_resume() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job( $post_id );

        // Pause queue
        $this->plugin->queue->pause_processing();
        $this->assertFalse( $this->plugin->queue->is_processing_active() );

        // Should not get jobs when paused
        $job = $this->plugin->queue->get_next_job();
        $this->assertNull( $job );

        // Resume queue
        $this->plugin->queue->resume_processing();
        $this->assertTrue( $this->plugin->queue->is_processing_active() );

        // Should get jobs when resumed
        $job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $job );
    }

    /**
     * Test scheduled job processing
     */
    public function test_scheduled_job_processing() {
        $post_id = $this->create_test_post();
        
        // Schedule job for future
        $scheduled_time = time() + 3600; // 1 hour from now
        $this->plugin->queue->add_job( $post_id, 10, $scheduled_time );

        // Should not get job yet (not scheduled)
        $job = $this->plugin->queue->get_next_job();
        $this->assertNull( $job );

        // Update job to be scheduled for now
        $job_data = $this->plugin->database->get_job_by_post_id( $post_id );
        $this->plugin->database->update_job( $job_data['id'], array(
            'scheduled_at' => date( 'Y-m-d H:i:s', time() - 60 ), // 1 minute ago
        ) );

        // Should get job now
        $job = $this->plugin->queue->get_next_job();
        $this->assertNotNull( $job );
    }

    /**
     * Test queue performance with large dataset
     */
    public function test_queue_performance() {
        $this->skip_if_requirements_not_met( array(
            'function' => 'memory_get_peak_usage',
        ) );

        $start_time = microtime( true );
        $start_memory = memory_get_usage( true );

        // Add many jobs
        $job_count = 50;
        for ( $i = 0; $i < $job_count; $i++ ) {
            $post_id = $this->create_test_post();
            $this->plugin->queue->add_job( $post_id );
        }

        $add_time = microtime( true ) - $start_time;

        // Process all jobs
        $process_start = microtime( true );
        $processed_count = 0;
        
        while ( $job = $this->plugin->queue->get_next_job() ) {
            $this->plugin->queue->complete_job( $job['id'] );
            $processed_count++;
            
            if ( $processed_count >= $job_count ) {
                break; // Safety break
            }
        }

        $process_time = microtime( true ) - $process_start;
        $total_time = microtime( true ) - $start_time;
        $memory_used = memory_get_usage( true ) - $start_memory;

        // Performance assertions
        $this->assertLessThan( 3.0, $add_time, 'Adding jobs should be fast' );
        $this->assertLessThan( 5.0, $process_time, 'Processing jobs should be fast' );
        $this->assertLessThan( 5 * 1024 * 1024, $memory_used, 'Memory usage should be reasonable' );
        $this->assertEquals( $job_count, $processed_count, 'All jobs should be processed' );

        // Verify final state
        $final_stats = $this->plugin->queue->get_queue_stats();
        $this->assertEquals( $job_count, $final_stats['completed'] );
    }
}
