<?php
/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

// Define test environment
define( 'AUTO_FEATURED_IMAGE_TESTING', true );

// Get the WordPress tests directory
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit( 1 );
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname( dirname( __FILE__ ) ) . '/auto-featured-image.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test utilities
require_once dirname( __FILE__ ) . '/includes/class-test-case.php';
require_once dirname( __FILE__ ) . '/includes/class-test-factory.php';
require_once dirname( __FILE__ ) . '/includes/class-test-utils.php';
require_once dirname( __FILE__ ) . '/includes/class-mock-objects.php';

// Initialize test environment
Auto_Featured_Image_Test_Utils::init();
