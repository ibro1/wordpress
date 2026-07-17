<?php
/**
 * Main Plugin Class
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Auto Featured Image Class
 *
 * This class handles the core functionality of the plugin including
 * initialization, hook registration, and component management.
 *
 * @since 1.0.0
 */
class Auto_Featured_Image {

    /**
     * Plugin instance
     *
     * @var Auto_Featured_Image
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     * @since 1.0.0
     */
    public $version;

    /**
     * Database handler
     *
     * @var Auto_Featured_Image_Database
     * @since 1.0.0
     */
    public $database;

    /**
     * Image processor
     *
     * @var Auto_Featured_Image_Processor
     * @since 1.0.0
     */
    public $processor;

    /**
     * Job queue manager
     *
     * @var Auto_Featured_Image_Queue
     * @since 1.0.0
     */
    public $queue;

    /**
     * Admin interface
     *
     * @var Auto_Featured_Image_Admin
     * @since 1.0.0
     */
    public $admin;

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    public $logger;

    /**
     * Image analyzer
     *
     * @var Auto_Featured_Image_Analyzer
     * @since 1.0.0
     */
    public $analyzer;

    /**
     * Post processing engine
     *
     * @var Auto_Featured_Image_Post_Engine
     * @since 1.0.0
     */
    public $post_engine;

    /**
     * Batch manager
     *
     * @var Auto_Featured_Image_Batch_Manager
     * @since 1.0.0
     */
    public $batch_manager;

    /**
     * Image assignment algorithms
     *
     * @var Auto_Featured_Image_Algorithms
     * @since 1.0.0
     */
    public $algorithms;

    /**
     * WordPress hooks integration
     *
     * @var Auto_Featured_Image_Hooks
     * @since 1.0.0
     */
    public $hooks;

    /**
     * Cron scheduler
     *
     * @var Auto_Featured_Image_Cron
     * @since 1.0.0
     */
    public $cron;

    /**
     * Error handler
     *
     * @var Auto_Featured_Image_Error_Handler
     * @since 1.0.0
     */
    public $error_handler;

    /**
     * Fallback system
     *
     * @var Auto_Featured_Image_Fallback
     * @since 1.0.0
     */
    public $fallback;

    /**
     * Performance optimization
     *
     * @var Auto_Featured_Image_Performance
     * @since 1.0.0
     */
    public $performance;

    /**
     * Get plugin instance
     *
     * @return Auto_Featured_Image
     * @since 1.0.0
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->version = AUTO_FEATURED_IMAGE_VERSION;
        $this->init();
    }

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Register hooks
        $this->register_hooks();
        
        // Initialize admin interface if in admin
        if ( is_admin() ) {
            $this->init_admin();
        }
    }

    /**
     * Load plugin dependencies
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Core classes
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-database.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-logger.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-analyzer.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-post-engine.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-batch-manager.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-algorithms.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-processor.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-queue.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-hooks.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-cron.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-error-handler.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-fallback.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-performance.php';

        // Admin classes (loaded conditionally)
        if ( is_admin() ) {
            require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'admin/class-auto-featured-image-admin.php';
            require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'admin/class-auto-featured-image-settings.php';
        }
        
        // Activation/Deactivation classes
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-activator.php';
        require_once AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'includes/class-auto-featured-image-deactivator.php';
    }

    /**
     * Initialize plugin components
     *
     * @since 1.0.0
     */
    private function init_components() {
        // Initialize logger first (other components may need it)
        $this->logger = new Auto_Featured_Image_Logger();
        
        // Initialize database handler
        $this->database = new Auto_Featured_Image_Database();

        // Initialize image analyzer
        $this->analyzer = new Auto_Featured_Image_Analyzer();

        // Initialize post processing engine
        $this->post_engine = new Auto_Featured_Image_Post_Engine();

        // Initialize batch manager
        $this->batch_manager = new Auto_Featured_Image_Batch_Manager();

        // Initialize image assignment algorithms
        $this->algorithms = new Auto_Featured_Image_Algorithms();

        // Initialize image processor
        $this->processor = new Auto_Featured_Image_Processor();

        // Initialize job queue
        $this->queue = new Auto_Featured_Image_Queue();



        // Initialize WordPress hooks integration
        $this->hooks = new Auto_Featured_Image_Hooks( $this );

        // Initialize cron scheduler
        $this->cron = new Auto_Featured_Image_Cron( $this );

        // Initialize error handler (should be last to catch all errors)
        $this->error_handler = new Auto_Featured_Image_Error_Handler( $this );

        // Initialize fallback system
        $this->fallback = new Auto_Featured_Image_Fallback( $this );

        // Initialize performance optimization (should be early for monitoring)
        $this->performance = new Auto_Featured_Image_Performance( $this );
    }

    /**
     * Load plugin text domain for translations
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'auto-featured-image',
            false,
            dirname( AUTO_FEATURED_IMAGE_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Register WordPress hooks
     *
     * @since 1.0.0
     */
    private function register_hooks() {
        // Plugin lifecycle hooks
        add_action( 'init', array( $this, 'init_plugin' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_loaded', array( $this, 'plugin_loaded' ) );
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // AJAX hooks are handled by the admin class
        
        // Scheduled events
        add_action( 'auto_featured_image_cleanup', array( $this, 'cleanup_old_logs' ) );
        add_action( 'auto_featured_image_health_check', array( $this, 'health_check' ) );
        
        // Action Scheduler hooks
        add_action( 'auto_featured_image_process_batch', array( $this->processor, 'process_batch' ) );
    }

    /**
     * Initialize admin interface
     *
     * @since 1.0.0
     */
    private function init_admin() {
        $this->admin = new Auto_Featured_Image_Admin( $this );
    }

    /**
     * Plugin initialization callback
     *
     * @since 1.0.0
     */
    public function init_plugin() {
        // Check if database needs updating
        $this->maybe_update_database();
        
        // Schedule cleanup events if not already scheduled
        if ( ! wp_next_scheduled( 'auto_featured_image_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'auto_featured_image_cleanup' );
        }
        
        if ( ! wp_next_scheduled( 'auto_featured_image_health_check' ) ) {
            wp_schedule_event( time(), 'hourly', 'auto_featured_image_health_check' );
        }
    }

    /**
     * Plugin loaded callback
     *
     * @since 1.0.0
     */
    public function plugin_loaded() {
        // Plugin is fully loaded and ready
        do_action( 'auto_featured_image_loaded', $this );
    }

    /**
     * Enqueue public assets
     *
     * @since 1.0.0
     */
    public function enqueue_public_assets() {
        // Currently no public assets needed
        // This method is here for future extensibility
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix Current admin page hook suffix
     * @since 1.0.0
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on our admin pages
        if ( strpos( $hook_suffix, 'auto-featured-image' ) === false ) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'auto-featured-image-admin',
            AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );
        
        // Enqueue admin JavaScript
        wp_enqueue_script(
            'auto-featured-image-admin',
            AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            $this->version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script(
            'auto-featured-image-admin',
            'autoFeaturedImageAjax',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'auto_featured_image_nonce' ),
                'strings' => array(
                    'processing' => __( 'Processing...', 'auto-featured-image' ),
                    'error'      => __( 'An error occurred. Please try again.', 'auto-featured-image' ),
                    'success'    => __( 'Operation completed successfully.', 'auto-featured-image' ),
                ),
            )
        );
    }

    /**
     * Check if database needs updating
     *
     * @since 1.0.0
     */
    private function maybe_update_database() {
        $current_db_version = get_option( 'auto_featured_image_db_version', '0' );
        
        if ( version_compare( $current_db_version, AUTO_FEATURED_IMAGE_DB_VERSION, '<' ) ) {
            $this->database->create_tables();
            update_option( 'auto_featured_image_db_version', AUTO_FEATURED_IMAGE_DB_VERSION );
        }
    }



    /**
     * Cleanup old log entries
     *
     * @since 1.0.0
     */
    public function cleanup_old_logs() {
        $this->logger->cleanup_old_logs();
    }

    /**
     * Perform health check
     *
     * @since 1.0.0
     */
    public function health_check() {
        // Check if any jobs are stuck
        $this->queue->check_stuck_jobs();
        
        // Log health check
        $this->logger->log( 'Health check completed', 'info' );
    }

    /**
     * Get plugin version
     *
     * @return string Plugin version
     * @since 1.0.0
     */
    public function get_version() {
        return $this->version;
    }
}
