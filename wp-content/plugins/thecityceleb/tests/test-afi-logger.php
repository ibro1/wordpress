<?php
/**
 * Unit tests for AFI_Logger class
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

class AFI_Logger_Test extends WP_UnitTestCase {
    
    /**
     * Logger instance
     *
     * @var AFI_Logger
     */
    private $logger;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load the logger class
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
        
        // Create logger instance
        $this->logger = new AFI_Logger();
        
        // Ensure logging is enabled for tests
        update_option('afi_enable_logging', true);
        update_option('afi_log_level', 'debug');
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clear all logs
        $this->logger->clear_all_logs();
        
        parent::tearDown();
    }
    
    /**
     * Test logger initialization
     */
    public function test_logger_initialization() {
        $this->assertInstanceOf('AFI_Logger', $this->logger);
        
        // Test that logs table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'afi_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        $this->assertTrue($table_exists, 'Logs table should exist after logger initialization');
    }
    
    /**
     * Test basic logging functionality
     */
    public function test_basic_logging() {
        // Test debug logging
        $this->logger->debug('Debug message', array('test' => 'data'));
        
        // Test info logging
        $this->logger->info('Info message', array('job_id' => 123));
        
        // Test warning logging
        $this->logger->warning('Warning message');
        
        // Test error logging
        $this->logger->error('Error message', array('post_id' => 456));
        
        // Test critical logging
        $this->logger->critical('Critical message');
        
        // Verify logs were created
        $logs = $this->logger->get_logs(array('limit' => 10));
        $this->assertCount(5, $logs, 'Should have 5 log entries');
        
        // Verify log levels
        $levels = array_column($logs, 'level');
        $this->assertContains('debug', $levels);
        $this->assertContains('info', $levels);
        $this->assertContains('warning', $levels);
        $this->assertContains('error', $levels);
        $this->assertContains('critical', $levels);
    }
    
    /**
     * Test log level filtering
     */
    public function test_log_level_filtering() {
        // Set log level to warning (should filter out debug and info)
        update_option('afi_log_level', 'warning');
        $logger = new AFI_Logger();
        
        // Log messages at different levels
        $logger->debug('Debug message');
        $logger->info('Info message');
        $logger->warning('Warning message');
        $logger->error('Error message');
        
        // Should only have warning and error logs
        $logs = $logger->get_logs();
        $this->assertCount(2, $logs, 'Should only log warning and error messages');
        
        $levels = array_column($logs, 'level');
        $this->assertContains('warning', $levels);
        $this->assertContains('error', $levels);
        $this->assertNotContains('debug', $levels);
        $this->assertNotContains('info', $levels);
    }
    
    /**
     * Test exception logging
     */
    public function test_exception_logging() {
        $exception = new Exception('Test exception', 500);
        
        $this->logger->exception($exception, 'error', array('context' => 'test'));
        
        $logs = $this->logger->get_logs(array('limit' => 1));
        $this->assertCount(1, $logs);
        
        $log = $logs[0];
        $this->assertEquals('error', $log->level);
        $this->assertEquals('Test exception', $log->message);
        $this->assertNotEmpty($log->stack_trace);
        $this->assertNotEmpty($log->context);
        
        $context = json_decode($log->context, true);
        $this->assertEquals('Exception', $context['exception_class']);
        $this->assertEquals(500, $context['exception_code']);
        $this->assertEquals('test', $context['context']);
    }
    
    /**
     * Test message interpolation
     */
    public function test_message_interpolation() {
        $this->logger->info('Processing post {post_id} with image {image_id}', array(
            'post_id' => 123,
            'image_id' => 456
        ));
        
        $logs = $this->logger->get_logs(array('limit' => 1));
        $log = $logs[0];
        
        $this->assertEquals('Processing post 123 with image 456', $log->message);
    }
    
    /**
     * Test log filtering and search
     */
    public function test_log_filtering() {
        // Create test logs
        $this->logger->info('Job started', array('job_id' => 100));
        $this->logger->error('Job failed', array('job_id' => 100));
        $this->logger->info('Job started', array('job_id' => 200));
        $this->logger->warning('Post processing', array('post_id' => 300));
        
        // Test filtering by job_id
        $logs = $this->logger->get_logs(array('job_id' => 100));
        $this->assertCount(2, $logs, 'Should find 2 logs for job 100');
        
        // Test filtering by level
        $logs = $this->logger->get_logs(array('level' => 'error'));
        $this->assertCount(1, $logs, 'Should find 1 error log');
        
        // Test filtering by post_id
        $logs = $this->logger->get_logs(array('post_id' => 300));
        $this->assertCount(1, $logs, 'Should find 1 log for post 300');
        
        // Test search functionality
        $logs = $this->logger->get_logs(array('search' => 'started'));
        $this->assertCount(2, $logs, 'Should find 2 logs containing "started"');
    }
    
    /**
     * Test log pagination
     */
    public function test_log_pagination() {
        // Create multiple logs
        for ($i = 1; $i <= 25; $i++) {
            $this->logger->info("Log message {$i}");
        }
        
        // Test first page
        $logs = $this->logger->get_logs(array('limit' => 10, 'offset' => 0));
        $this->assertCount(10, $logs, 'First page should have 10 logs');
        
        // Test second page
        $logs = $this->logger->get_logs(array('limit' => 10, 'offset' => 10));
        $this->assertCount(10, $logs, 'Second page should have 10 logs');
        
        // Test third page
        $logs = $this->logger->get_logs(array('limit' => 10, 'offset' => 20));
        $this->assertCount(5, $logs, 'Third page should have 5 logs');
        
        // Test count
        $count = $this->logger->count_logs();
        $this->assertEquals(25, $count, 'Should count 25 total logs');
    }
    
    /**
     * Test log statistics
     */
    public function test_log_statistics() {
        // Create logs at different levels
        $this->logger->debug('Debug 1');
        $this->logger->debug('Debug 2');
        $this->logger->info('Info 1');
        $this->logger->info('Info 2');
        $this->logger->info('Info 3');
        $this->logger->warning('Warning 1');
        $this->logger->error('Error 1');
        $this->logger->error('Error 2');
        
        $stats = $this->logger->get_log_stats();
        
        $this->assertEquals(8, $stats['total']);
        $this->assertEquals(2, $stats['debug']);
        $this->assertEquals(3, $stats['info']);
        $this->assertEquals(1, $stats['warning']);
        $this->assertEquals(2, $stats['error']);
        $this->assertEquals(0, $stats['critical']);
    }
    
    /**
     * Test log cleanup by date
     */
    public function test_log_cleanup_by_date() {
        // Create old logs by directly inserting into database
        global $wpdb;
        $table_name = $wpdb->prefix . 'afi_logs';
        
        // Insert old log (35 days ago)
        $wpdb->insert(
            $table_name,
            array(
                'level' => 'info',
                'message' => 'Old log',
                'context' => '{}',
                'created_at' => date('Y-m-d H:i:s', strtotime('-35 days'))
            )
        );
        
        // Insert recent log
        $this->logger->info('Recent log');
        
        // Verify we have 2 logs
        $count = $this->logger->count_logs();
        $this->assertEquals(2, $count);
        
        // Clean up logs older than 30 days
        $deleted = $this->logger->cleanup_old_logs(30);
        $this->assertEquals(1, $deleted, 'Should delete 1 old log');
        
        // Verify only recent log remains
        $count = $this->logger->count_logs();
        $this->assertEquals(1, $count);
        
        $logs = $this->logger->get_logs();
        $this->assertEquals('Recent log', $logs[0]->message);
    }
    
    /**
     * Test log cleanup by count
     */
    public function test_log_cleanup_by_count() {
        // Create 15 logs
        for ($i = 1; $i <= 15; $i++) {
            $this->logger->info("Log {$i}");
            // Small delay to ensure different timestamps
            usleep(1000);
        }
        
        // Clean up to keep only 10 logs
        $deleted = $this->logger->cleanup_logs_by_count(10);
        $this->assertEquals(5, $deleted, 'Should delete 5 oldest logs');
        
        // Verify only 10 logs remain
        $count = $this->logger->count_logs();
        $this->assertEquals(10, $count);
        
        // Verify the remaining logs are the newest ones
        $logs = $this->logger->get_logs(array('order' => 'ASC'));
        $this->assertStringContains('Log 6', $logs[0]->message);
    }
    
    /**
     * Test CSV export functionality
     */
    public function test_csv_export() {
        // Create test logs
        $this->logger->info('Test log 1', array('job_id' => 100));
        $this->logger->error('Test log 2', array('post_id' => 200));
        
        $csv_content = $this->logger->export_logs_csv();
        
        // Verify CSV header
        $this->assertStringContains('ID,Level,Message,Job ID,Post ID,User ID,IP Address,Created At', $csv_content);
        
        // Verify CSV content
        $this->assertStringContains('info,"Test log 1",100', $csv_content);
        $this->assertStringContains('error,"Test log 2",,200', $csv_content);
    }
    
    /**
     * Test logging configuration
     */
    public function test_logging_configuration() {
        $config = $this->logger->get_config();
        
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('level', $config);
        $this->assertArrayHasKey('max_entries', $config);
        $this->assertArrayHasKey('retention_days', $config);
        
        // Test setting configuration
        $this->logger->set_config(array(
            'enabled' => false,
            'level' => 'error',
            'max_entries' => 5000
        ));
        
        $new_config = $this->logger->get_config();
        $this->assertFalse($new_config['enabled']);
        $this->assertEquals('error', $new_config['level']);
        $this->assertEquals(5000, $new_config['max_entries']);
    }
    
    /**
     * Test logging when disabled
     */
    public function test_logging_when_disabled() {
        // Disable logging
        update_option('afi_enable_logging', false);
        $logger = new AFI_Logger();
        
        // Try to log
        $logger->info('This should not be logged');
        
        // Verify no logs were created
        $logs = $logger->get_logs();
        $this->assertCount(0, $logs, 'No logs should be created when logging is disabled');
    }
    
    /**
     * Test log level priorities
     */
    public function test_log_level_priorities() {
        $this->assertEquals(1, AFI_Logger::get_level_priority('debug'));
        $this->assertEquals(2, AFI_Logger::get_level_priority('info'));
        $this->assertEquals(3, AFI_Logger::get_level_priority('warning'));
        $this->assertEquals(4, AFI_Logger::get_level_priority('error'));
        $this->assertEquals(5, AFI_Logger::get_level_priority('critical'));
        $this->assertEquals(0, AFI_Logger::get_level_priority('invalid'));
    }
    
    /**
     * Test available log levels
     */
    public function test_available_log_levels() {
        $levels = AFI_Logger::get_log_levels();
        
        $expected_levels = array('debug', 'info', 'warning', 'error', 'critical');
        $this->assertEquals($expected_levels, $levels);
    }
    
    /**
     * Test table creation and removal
     */
    public function test_table_management() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'afi_logs';
        
        // Drop table
        $result = $this->logger->drop_logs_table();
        $this->assertTrue($result, 'Should successfully drop logs table');
        
        // Verify table doesn't exist
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        $this->assertFalse($table_exists, 'Logs table should not exist after dropping');
        
        // Recreate table
        $result = $this->logger->create_logs_table();
        $this->assertTrue($result, 'Should successfully create logs table');
        
        // Verify table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        $this->assertTrue($table_exists, 'Logs table should exist after creation');
    }
    
    /**
     * Test context data handling
     */
    public function test_context_data_handling() {
        $context = array(
            'job_id' => 123,
            'post_id' => 456,
            'user_data' => array(
                'name' => 'Test User',
                'email' => 'test@example.com'
            ),
            'settings' => array(
                'batch_size' => 100,
                'enabled' => true
            )
        );
        
        $this->logger->info('Complex context test', $context);
        
        $logs = $this->logger->get_logs(array('limit' => 1));
        $log = $logs[0];
        
        $this->assertEquals(123, $log->job_id);
        $this->assertEquals(456, $log->post_id);
        
        $decoded_context = json_decode($log->context, true);
        $this->assertEquals($context, $decoded_context);
    }
    
    /**
     * Test user and IP tracking
     */
    public function test_user_and_ip_tracking() {
        // Create a test user
        $user_id = $this->factory->user->create(array(
            'user_login' => 'testuser',
            'user_email' => 'test@example.com'
        ));
        
        // Set current user
        wp_set_current_user($user_id);
        
        // Mock IP address
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';
        
        $this->logger->info('User tracking test');
        
        $logs = $this->logger->get_logs(array('limit' => 1));
        $log = $logs[0];
        
        $this->assertEquals($user_id, $log->user_id);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Test User Agent', $log->user_agent);
        
        // Clean up
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_USER_AGENT']);
        wp_set_current_user(0);
    }
}