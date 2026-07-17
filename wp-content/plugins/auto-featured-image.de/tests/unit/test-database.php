<?php
/**
 * Database Tests
 *
 * Tests for the database functionality.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Database Test Class
 */
class Test_Auto_Featured_Image_Database extends Auto_Featured_Image_Test_Case {

    /**
     * Test database table creation
     */
    public function test_create_tables() {
        // Tables should be created during setUp
        $this->assertTrue( $this->plugin->database->tables_exist() );

        // Test specific table existence
        global $wpdb;
        
        $jobs_table = $wpdb->prefix . 'auto_featured_image_jobs';
        $this->assertEquals( $jobs_table, $wpdb->get_var( "SHOW TABLES LIKE '{$jobs_table}'" ) );

        $progress_table = $wpdb->prefix . 'auto_featured_image_progress';
        $this->assertEquals( $progress_table, $wpdb->get_var( "SHOW TABLES LIKE '{$progress_table}'" ) );

        $log_table = $wpdb->prefix . 'auto_featured_image_log';
        $this->assertEquals( $log_table, $wpdb->get_var( "SHOW TABLES LIKE '{$log_table}'" ) );
    }

    /**
     * Test job insertion
     */
    public function test_insert_job() {
        $post_id = $this->create_test_post();
        
        $job_id = $this->plugin->database->insert_job( $post_id, 'pending', 10 );
        
        $this->assertIsInt( $job_id );
        $this->assertGreaterThan( 0, $job_id );

        // Verify job was inserted
        $job = $this->plugin->database->get_job_by_id( $job_id );
        $this->assertNotNull( $job );
        $this->assertEquals( $post_id, $job['post_id'] );
        $this->assertEquals( 'pending', $job['status'] );
        $this->assertEquals( 10, $job['priority'] );
    }

    /**
     * Test job retrieval by post ID
     */
    public function test_get_job_by_post_id() {
        $post_id = $this->create_test_post();
        $job_id = $this->plugin->database->insert_job( $post_id );

        $job = $this->plugin->database->get_job_by_post_id( $post_id );
        
        $this->assertNotNull( $job );
        $this->assertEquals( $job_id, $job['id'] );
        $this->assertEquals( $post_id, $job['post_id'] );
    }

    /**
     * Test job status update
     */
    public function test_update_job_status() {
        $post_id = $this->create_test_post();
        $job_id = $this->plugin->database->insert_job( $post_id );

        $result = $this->plugin->database->update_job_status( $job_id, 'processing' );
        $this->assertTrue( $result );

        $job = $this->plugin->database->get_job_by_id( $job_id );
        $this->assertEquals( 'processing', $job['status'] );
    }

    /**
     * Test log insertion
     */
    public function test_insert_log() {
        $post_id = $this->create_test_post();
        $context = array( 'test' => true, 'value' => 123 );

        $log_id = $this->plugin->database->insert_log( 'info', 'Test log message', $context, $post_id );
        
        $this->assertIsInt( $log_id );
        $this->assertGreaterThan( 0, $log_id );

        // Verify log was inserted
        $log = $this->plugin->database->get_log_by_id( $log_id );
        $this->assertNotNull( $log );
        $this->assertEquals( 'info', $log['level'] );
        $this->assertEquals( 'Test log message', $log['message'] );
        $this->assertEquals( $post_id, $log['post_id'] );
        $this->assertEquals( $context, $log['context'] );
    }

    /**
     * Test log retrieval with filters
     */
    public function test_get_logs_with_filters() {
        $post_id = $this->create_test_post();

        // Insert multiple logs
        $this->plugin->database->insert_log( 'info', 'Info message 1', array(), $post_id );
        $this->plugin->database->insert_log( 'error', 'Error message 1', array(), $post_id );
        $this->plugin->database->insert_log( 'info', 'Info message 2', array(), $post_id );

        // Test level filter
        $info_logs = $this->plugin->database->get_logs( array( 'level' => 'info' ) );
        $this->assertCount( 2, $info_logs );

        $error_logs = $this->plugin->database->get_logs( array( 'level' => 'error' ) );
        $this->assertCount( 1, $error_logs );

        // Test limit
        $limited_logs = $this->plugin->database->get_logs( array( 'limit' => 2 ) );
        $this->assertCount( 2, $limited_logs );
    }

    /**
     * Test progress tracking
     */
    public function test_progress_tracking() {
        $batch_id = 'test_batch_' . time();
        
        $progress_data = array(
            'batch_id' => $batch_id,
            'total_posts' => 10,
            'processed_posts' => 0,
            'successful_posts' => 0,
            'failed_posts' => 0,
            'status' => 'pending',
        );

        $progress_id = $this->plugin->database->insert_progress( $progress_data );
        $this->assertIsInt( $progress_id );

        // Update progress
        $update_data = array(
            'processed_posts' => 5,
            'successful_posts' => 4,
            'failed_posts' => 1,
            'status' => 'processing',
        );

        $result = $this->plugin->database->update_progress( $batch_id, $update_data );
        $this->assertTrue( $result );

        // Verify update
        $progress = $this->plugin->database->get_progress_by_batch_id( $batch_id );
        $this->assertEquals( 5, $progress['processed_posts'] );
        $this->assertEquals( 4, $progress['successful_posts'] );
        $this->assertEquals( 1, $progress['failed_posts'] );
        $this->assertEquals( 'processing', $progress['status'] );
    }

    /**
     * Test queue statistics
     */
    public function test_queue_statistics() {
        // Create test jobs with different statuses
        $post_ids = array();
        for ( $i = 0; $i < 5; $i++ ) {
            $post_ids[] = $this->create_test_post();
        }

        // Insert jobs
        $this->plugin->database->insert_job( $post_ids[0], 'pending' );
        $this->plugin->database->insert_job( $post_ids[1], 'pending' );
        $this->plugin->database->insert_job( $post_ids[2], 'processing' );
        $this->plugin->database->insert_job( $post_ids[3], 'completed' );
        $this->plugin->database->insert_job( $post_ids[4], 'failed' );

        $stats = $this->plugin->database->get_queue_stats();
        
        $this->assertEquals( 5, $stats['total'] );
        $this->assertEquals( 2, $stats['pending'] );
        $this->assertEquals( 1, $stats['processing'] );
        $this->assertEquals( 1, $stats['completed'] );
        $this->assertEquals( 1, $stats['failed'] );
    }

    /**
     * Test data cleanup
     */
    public function test_cleanup_old_logs() {
        $post_id = $this->create_test_post();

        // Insert logs with different dates
        $this->plugin->database->insert_log( 'info', 'Recent log', array(), $post_id );
        
        // Simulate old log by directly updating the database
        global $wpdb;
        $log_table = $this->plugin->database->get_log_table();
        
        $old_date = date( 'Y-m-d H:i:s', strtotime( '-35 days' ) );
        $wpdb->insert( $log_table, array(
            'level' => 'info',
            'message' => 'Old log',
            'post_id' => $post_id,
            'created_at' => $old_date,
        ) );

        // Clean up logs older than 30 days
        $deleted_count = $this->plugin->database->cleanup_old_logs( 30 );
        $this->assertEquals( 1, $deleted_count );

        // Verify only recent log remains
        $remaining_logs = $this->plugin->database->get_logs();
        $this->assertCount( 1, $remaining_logs );
        $this->assertEquals( 'Recent log', $remaining_logs[0]['message'] );
    }

    /**
     * Test database migration
     */
    public function test_database_migration() {
        // Test that database version is set correctly
        $db_version = get_option( 'auto_featured_image_db_version' );
        $this->assertNotEmpty( $db_version );

        // Test migration detection
        update_option( 'auto_featured_image_db_version', '0.9.0' );
        
        // Reinitialize database to trigger migration check
        $database = new Auto_Featured_Image_Database();
        
        // Version should be updated
        $new_version = get_option( 'auto_featured_image_db_version' );
        $this->assertNotEquals( '0.9.0', $new_version );
    }

    /**
     * Test table integrity
     */
    public function test_table_integrity() {
        $integrity_issues = $this->plugin->database->check_table_integrity();
        $this->assertIsArray( $integrity_issues );
        $this->assertEmpty( $integrity_issues, 'Database tables should have no integrity issues' );
    }

    /**
     * Test performance with large dataset
     */
    public function test_performance_with_large_dataset() {
        $this->skip_if_requirements_not_met( array(
            'function' => 'memory_get_peak_usage',
        ) );

        $start_memory = memory_get_usage( true );
        $start_time = microtime( true );

        // Insert many jobs
        $post_ids = array();
        for ( $i = 0; $i < 100; $i++ ) {
            $post_ids[] = $this->create_test_post();
        }

        foreach ( $post_ids as $post_id ) {
            $this->plugin->database->insert_job( $post_id );
        }

        $end_time = microtime( true );
        $end_memory = memory_get_usage( true );

        $execution_time = $end_time - $start_time;
        $memory_used = $end_memory - $start_memory;

        // Performance assertions
        $this->assertLessThan( 5.0, $execution_time, 'Bulk insert should complete within 5 seconds' );
        $this->assertLessThan( 10 * 1024 * 1024, $memory_used, 'Memory usage should be less than 10MB' );

        // Verify all jobs were inserted
        $stats = $this->plugin->database->get_queue_stats();
        $this->assertEquals( 100, $stats['total'] );
    }
}
