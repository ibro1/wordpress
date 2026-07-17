<?php
/**
 * Main plugin class for Auto Featured Image
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class that handles initialization, dependency loading, and hook registration
 */
class AFI_Plugin {
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * Plugin loader instance
     *
     * @var AFI_Loader
     */
    private $loader;
    
    /**
     * Admin controller instance
     *
     * @var AFI_Admin
     */
    private $admin;
    
    /**
     * Job manager instance
     *
     * @var AFI_Job_Manager
     */
    private $job_manager;
    
    /**
     * Database manager instance
     *
     * @var AFI_Database
     */
    private $database;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->version = AFI_VERSION;
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load the plugin loader
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-loader.php';
        $this->loader = new AFI_Loader();
        
        // Load core classes
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-i18n.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-service.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-filter-model.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-model.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-item-model.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-scanner.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-processor.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
        
        // Load admin classes if in admin area
        if (is_admin()) {
            require_once AFI_PLUGIN_DIR . 'admin/class-afi-admin.php';
            require_once AFI_PLUGIN_DIR . 'admin/class-afi-ajax.php';
        }
        
        // Initialize core components
        $this->database = new AFI_Database();
        $this->job_manager = new AFI_Job_Manager();
        
        // Register cron hooks now that job manager is available
        $this->define_cron_hooks();
        
        // Initialize admin components if in admin area
        if (is_admin()) {
            $this->admin = new AFI_Admin($this->get_plugin_name(), $this->get_version());
        }
    }
    
    /**
     * Set the plugin locale for internationalization
     */
    private function set_locale() {
        $plugin_i18n = new AFI_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Define admin-specific hooks
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }
        
        // Admin menu and pages
        $this->loader->add_action('admin_menu', $this->admin, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        
        // AJAX handlers
        $ajax_handler = new AFI_Ajax();
        
        // Dashboard and stats
        $this->loader->add_action('wp_ajax_afi_get_dashboard_stats', $ajax_handler, 'get_dashboard_stats');
        
        // Job creation form support
        $this->loader->add_action('wp_ajax_afi_get_post_type_counts', $ajax_handler, 'get_post_type_counts');
        $this->loader->add_action('wp_ajax_afi_get_image_counts', $ajax_handler, 'get_image_counts');
        $this->loader->add_action('wp_ajax_afi_validate_filters', $ajax_handler, 'validate_filters');
        
        // Job history and management
        $this->loader->add_action('wp_ajax_afi_get_job_history', $ajax_handler, 'get_job_history');
        $this->loader->add_action('wp_ajax_afi_get_job_progress', $ajax_handler, 'get_job_progress');
        $this->loader->add_action('wp_ajax_afi_control_job', $ajax_handler, 'control_job');
        
        // Legacy handlers (for task 9)
        $this->loader->add_action('wp_ajax_afi_get_job_logs', $ajax_handler, 'get_job_logs');
        $this->loader->add_action('wp_ajax_afi_get_scan_results', $ajax_handler, 'get_scan_results');
        
        // Logging system handlers
        $this->loader->add_action('wp_ajax_afi_get_logs', $ajax_handler, 'get_logs');
        $this->loader->add_action('wp_ajax_afi_clear_logs', $ajax_handler, 'clear_logs');
        $this->loader->add_action('wp_ajax_afi_export_logs', $ajax_handler, 'export_logs');
        $this->loader->add_action('wp_ajax_afi_get_log_stats', $ajax_handler, 'get_log_stats');
    }
    
    /**
     * Define cron hooks for background processing
     */
    private function define_cron_hooks() {
        // Register cron hooks for job processing (fallback when Action Scheduler is not available)
        $this->loader->add_action('afi_scan_posts_batch', $this->job_manager, 'process_scan_batch', 10, 3);
        $this->loader->add_action('afi_process_job_items', $this->job_manager, 'process_job_items_batch', 10, 2);
    }

    /**
     * Define public-facing hooks
     */
    private function define_public_hooks() {
        // Currently no public hooks needed
        // This method is here for future extensibility
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get the plugin name
     *
     * @return string
     */
    public function get_plugin_name() {
        return 'auto-featured-image';
    }
    
    /**
     * Get the plugin version
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Plugin activation procedures
     */
    public static function activate() {
        // Check WordPress version compatibility
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            wp_die(__('Auto Featured Image requires WordPress 5.0 or higher.', 'auto-featured-image'));
        }
        
        // Check PHP version compatibility
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            wp_die(__('Auto Featured Image requires PHP 7.4 or higher.', 'auto-featured-image'));
        }
        
        // Check if Action Scheduler is available
        if (!class_exists('ActionScheduler')) {
            // Action Scheduler will be loaded as a dependency in future tasks
            // For now, we'll just note this requirement
        }
        
        // Create database tables
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
        $database = new AFI_Database();
        $database->create_tables();
        
        // Set plugin version option
        update_option('afi_version', AFI_VERSION);
        
        // Set default options
        $default_options = array(
            'batch_size' => 1000,
            'cleanup_days' => 90,
            'enable_logging' => true,
            'log_level' => 'info',
            'max_log_entries' => 10000,
            'log_retention_days' => 30
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (get_option('afi_' . $option_name) === false) {
                update_option('afi_' . $option_name, $default_value);
            }
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation procedures
     */
    public static function deactivate() {
        // Cancel all running jobs
        if (class_exists('AFI_Job_Manager')) {
            $job_manager = new AFI_Job_Manager();
            $job_manager->cancel_all_jobs();
        }
        
        // Clear any scheduled actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('afi_scan_posts_batch');
            as_unschedule_all_actions('afi_process_job_item');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall procedures
     */
    public static function uninstall() {
        // Check if user wants to keep data
        $keep_data = get_option('afi_keep_data_on_uninstall', false);
        
        if (!$keep_data) {
            // Remove database tables
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-database.php';
            $database = new AFI_Database();
            $database->drop_tables();
            
            // Remove all plugin options
            $options_to_remove = array(
                'afi_version',
                'afi_batch_size',
                'afi_cleanup_days',
                'afi_enable_logging',
                'afi_log_level',
                'afi_keep_data_on_uninstall'
            );
            
            foreach ($options_to_remove as $option) {
                delete_option($option);
            }
        }
        
        // Clear any remaining scheduled actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('afi_scan_posts_batch');
            as_unschedule_all_actions('afi_process_job_item');
        }
    }
}