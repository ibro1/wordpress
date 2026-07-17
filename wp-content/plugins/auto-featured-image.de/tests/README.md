# Auto Featured Image Plugin - Testing Framework

This directory contains the comprehensive testing framework for the Auto Featured Image plugin, including unit tests, integration tests, and performance tests.

## Overview

The testing framework is built on PHPUnit and follows WordPress testing best practices. It provides:

- **Unit Tests**: Test individual components in isolation
- **Integration Tests**: Test complete workflows and component interactions
- **Performance Tests**: Verify performance characteristics under load
- **Mock Objects**: Simulate external dependencies and services
- **Test Utilities**: Helper functions for common testing scenarios

## Setup

### Prerequisites

1. **PHP 7.4 or higher**
2. **PHPUnit 8.0 or higher**
3. **WordPress Test Suite**
4. **MySQL/MariaDB**

### Installation

1. **Install PHPUnit**:
   ```bash
   composer global require phpunit/phpunit
   ```

2. **Set up WordPress Test Environment**:
   ```bash
   bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

3. **Configure Environment Variables**:
   ```bash
   export WP_TESTS_DIR="/tmp/wordpress-tests-lib"
   export WP_CORE_DIR="/tmp/wordpress"
   ```

## Running Tests

### Quick Start

Run all tests:
```bash
bin/run-tests.sh
```

### Test Suites

Run specific test suites:

```bash
# Unit tests only
bin/run-tests.sh --suite unit

# Integration tests only
bin/run-tests.sh --suite integration

# Performance tests only
bin/run-tests.sh --suite performance
```

### Coverage Reports

Generate code coverage reports:
```bash
bin/run-tests.sh --coverage
```

Coverage reports will be generated in `tests/coverage/html/`.

### Verbose Output

Run tests with detailed output:
```bash
bin/run-tests.sh --verbose
```

### WordPress Versions

Test against specific WordPress versions:
```bash
bin/run-tests.sh --wp-version 5.8
bin/run-tests.sh --wp-version latest
```

### Multisite Testing

Test multisite compatibility:
```bash
bin/run-tests.sh --multisite
```

## Test Structure

### Directory Layout

```
tests/
├── bootstrap.php              # Test environment setup
├── phpunit.xml               # PHPUnit configuration
├── includes/                 # Test utilities and base classes
│   ├── class-test-case.php   # Base test case class
│   ├── class-test-factory.php # Test data factory
│   ├── class-test-utils.php  # Test utilities
│   └── class-mock-objects.php # Mock objects
├── unit/                     # Unit tests
│   ├── test-database.php     # Database functionality tests
│   ├── test-queue.php        # Queue system tests
│   ├── test-algorithms.php   # Algorithm tests
│   └── ...
├── integration/              # Integration tests
│   ├── test-full-workflow.php # Complete workflow tests
│   ├── test-admin-interface.php # Admin interface tests
│   └── ...
├── performance/              # Performance tests
│   ├── test-batch-processing.php # Batch processing performance
│   ├── test-memory-usage.php # Memory usage tests
│   └── ...
└── coverage/                 # Coverage reports (generated)
```

### Base Test Case

All tests extend `Auto_Featured_Image_Test_Case` which provides:

- Plugin instance access
- Test data factories
- Common assertions
- Setup/teardown helpers
- Mock utilities

Example:
```php
class Test_My_Feature extends Auto_Featured_Image_Test_Case {
    public function test_my_feature() {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job($post_id);
        $this->assertJobExists($post_id);
    }
}
```

### Test Factory

The test factory creates realistic test data:

```php
// Create test post with images
$post_data = $this->test_factory->create_post_with_images(array(
    'image_count' => 3,
    'post_title' => 'Test Post'
));

// Create batch scenario
$batch = $this->test_factory->create_batch_scenario(array(
    'post_count' => 10,
    'posts_with_images' => 7
));
```

### Mock Objects

Mock external dependencies:

```php
// Mock HTTP responses
$this->mock_wp_function('wp_remote_get', Mock_HTTP_Response::success('{"status":"ok"}'));

// Mock database errors
$mock_db = new Mock_Database();
$mock_db->set_error('Connection failed');
```

## Writing Tests

### Unit Test Example

```php
class Test_Image_Analyzer extends Auto_Featured_Image_Test_Case {
    
    public function test_analyze_image_quality() {
        $attachment_id = $this->create_test_attachment();
        
        $analyzer = new Auto_Featured_Image_Analyzer();
        $result = $analyzer->analyze_image($attachment_id);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('quality_score', $result);
        $this->assertGreaterThan(0, $result['quality_score']);
    }
}
```

### Integration Test Example

```php
class Test_Post_Processing extends Auto_Featured_Image_Test_Case {
    
    public function test_complete_post_processing() {
        $post_data = $this->test_factory->create_post_with_images();
        $post_id = $post_data['post_id'];
        
        // Trigger processing
        $this->trigger_action('save_post', $post_id);
        
        // Process queue
        $job = $this->plugin->queue->get_next_job();
        $result = $this->plugin->processor->process_post($post_id);
        
        $this->assertTrue($result);
        $this->assertPostHasFeaturedImage($post_id);
    }
}
```

### Performance Test Example

```php
/**
 * @group performance
 */
public function test_batch_processing_performance() {
    $start_time = microtime(true);
    
    // Create large dataset
    for ($i = 0; $i < 100; $i++) {
        $post_id = $this->create_test_post();
        $this->plugin->queue->add_job($post_id);
    }
    
    // Process all jobs
    while ($job = $this->plugin->queue->get_next_job()) {
        $this->plugin->processor->process_post($job['post_id']);
    }
    
    $execution_time = microtime(true) - $start_time;
    $this->assertLessThan(30.0, $execution_time);
}
```

## Custom Assertions

The framework provides custom assertions:

```php
// Job assertions
$this->assertJobExists($post_id, 'pending');
$this->assertJobNotExists($post_id);

// Featured image assertions
$this->assertPostHasFeaturedImage($post_id);
$this->assertPostHasNoFeaturedImage($post_id);

// Log assertions
$this->assertLogExists('error', 'Database error', $post_id);
```

## Test Groups

Tests can be organized into groups:

```php
/**
 * @group database
 * @group slow
 */
public function test_large_dataset() {
    // Test implementation
}
```

Run specific groups:
```bash
phpunit --group database
phpunit --exclude-group slow
```

## Continuous Integration

The testing framework is designed for CI/CD pipelines:

```yaml
# GitHub Actions example
- name: Run Tests
  run: |
    bin/install-wp-tests.sh wordpress_test root '' localhost latest
    bin/run-tests.sh --coverage
    
- name: Upload Coverage
  uses: codecov/codecov-action@v1
  with:
    file: tests/coverage/clover.xml
```

## Best Practices

1. **Test Isolation**: Each test should be independent
2. **Descriptive Names**: Use clear, descriptive test method names
3. **Single Responsibility**: Each test should verify one specific behavior
4. **Mock External Dependencies**: Don't rely on external services
5. **Performance Awareness**: Mark slow tests with `@group slow`
6. **Clean Up**: Use setUp/tearDown for proper test isolation

## Troubleshooting

### Common Issues

1. **Database Connection Errors**:
   - Verify MySQL is running
   - Check database credentials
   - Ensure test database exists

2. **Memory Limit Errors**:
   - Increase PHP memory limit
   - Use `@group slow` for memory-intensive tests

3. **Timeout Issues**:
   - Increase PHPUnit timeout settings
   - Optimize test data creation

4. **WordPress Not Found**:
   - Verify WP_TESTS_DIR environment variable
   - Run install-wp-tests.sh script

### Debug Mode

Enable debug mode for detailed output:
```bash
WP_DEBUG=true bin/run-tests.sh --verbose
```

## Contributing

When adding new tests:

1. Follow existing naming conventions
2. Add appropriate test groups
3. Include performance considerations
4. Update this documentation if needed
5. Ensure tests pass in CI environment

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)
- [WordPress Test Suite](https://github.com/WordPress/wordpress-develop/tree/trunk/tests/phpunit)
