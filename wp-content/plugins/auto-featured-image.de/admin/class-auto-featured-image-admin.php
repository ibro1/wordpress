<?php
/**
 * Admin Interface Class
 *
 * Handles all admin interface functionality including menu creation,
 * page routing, permissions, and admin-specific features.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Admin Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Admin {

    /**
     * Plugin instance
     *
     * @var Auto_Featured_Image
     * @since 1.0.0
     */
    private $plugin;

    /**
     * Admin pages
     *
     * @var array
     * @since 1.0.0
     */
    private $admin_pages = array();

    /**
     * Current admin page
     *
     * @var string
     * @since 1.0.0
     */
    private $current_page = '';

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->init_admin_pages();
        $this->init_hooks();
    }

    /**
     * Initialize admin pages configuration
     *
     * @since 1.0.0
     */
    private function init_admin_pages() {
        // Initialize admin pages with delayed translation loading
        add_action( 'init', array( $this, 'setup_admin_pages' ) );
    }

    /**
     * Setup admin pages after init hook
     *
     * @since 1.0.0
     */
    public function setup_admin_pages() {
        $this->admin_pages = array(
            'dashboard' => array(
                'title' => 'Dashboard',
                'menu_title' => 'Auto Featured Image',
                'capability' => 'manage_options',
                'icon' => 'dashicons-format-image',
                'position' => 30,
                'callback' => array( $this, 'render_dashboard_page' ),
                'is_main' => true,
            ),
            'settings' => array(
                'title' => 'Settings',
                'menu_title' => 'Settings',
                'capability' => 'manage_options',
                'callback' => array( $this, 'render_settings_page' ),
                'parent' => 'dashboard',
            ),
            'bulk-process' => array(
                'title' => 'Bulk Processing',
                'menu_title' => 'Bulk Processing',
                'capability' => 'edit_posts',
                'callback' => array( $this, 'render_bulk_process_page' ),
                'parent' => 'dashboard',
            ),
            'queue-monitor' => array(
                'title' => 'Queue Monitor',
                'menu_title' => 'Queue Monitor',
                'capability' => 'edit_posts',
                'callback' => array( $this, 'render_queue_monitor_page' ),
                'parent' => 'dashboard',
            ),
            'logs' => array(
                'title' => 'Logs & Analytics',
                'menu_title' => 'Logs & Analytics',
                'capability' => 'manage_options',
                'callback' => array( $this, 'render_logs_page' ),
                'parent' => 'dashboard',
            ),
            'tools' => array(
                'title' => 'Tools',
                'menu_title' => 'Tools',
                'capability' => 'manage_options',
                'callback' => array( $this, 'render_tools_page' ),
                'parent' => 'dashboard',
            ),
        );
    }

    /**
     * Initialize admin hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Wait for init to ensure text domain is loaded before setting up admin menu
        add_action( 'init', array( $this, 'init_admin_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'init_admin_settings' ) );
        add_action( 'wp_ajax_auto_featured_image_ajax', array( $this, 'handle_ajax_request' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
        add_filter( 'plugin_action_links_' . AUTO_FEATURED_IMAGE_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * Initialize admin menu after init hook
     *
     * @since 1.0.0
     */
    public function init_admin_menu() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * Add admin menu pages
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        foreach ( $this->admin_pages as $page_slug => $page_config ) {
            if ( isset( $page_config['is_main'] ) && $page_config['is_main'] ) {
                // Add main menu page
                $hook = add_menu_page(
                    $page_config['title'],
                    $page_config['menu_title'],
                    $page_config['capability'],
                    'auto-featured-image-' . $page_slug,
                    $page_config['callback'],
                    $page_config['icon'],
                    $page_config['position']
                );
                
                add_action( "load-$hook", array( $this, 'load_admin_page' ) );
            } else {
                // Add submenu page
                $parent_slug = 'auto-featured-image-' . $page_config['parent'];
                $hook = add_submenu_page(
                    $parent_slug,
                    $page_config['title'],
                    $page_config['menu_title'],
                    $page_config['capability'],
                    'auto-featured-image-' . $page_slug,
                    $page_config['callback']
                );
                
                add_action( "load-$hook", array( $this, 'load_admin_page' ) );
            }
        }
    }

    /**
     * Load admin page
     *
     * @since 1.0.0
     */
    public function load_admin_page() {
        // Get current page
        $screen = get_current_screen();
        $this->current_page = str_replace( 'toplevel_page_auto-featured-image-', '', $screen->id );
        $this->current_page = str_replace( 'auto-featured-image_page_auto-featured-image-', '', $this->current_page );
        
        // Add screen options if needed
        $this->add_screen_options();
        
        // Add help tabs
        $this->add_help_tabs();
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on our admin pages
        if ( strpos( $hook, 'auto-featured-image' ) === false ) {
            return;
        }

        // Enqueue WordPress media scripts
        wp_enqueue_media();

        // Enqueue admin CSS
        wp_enqueue_style(
            'auto-featured-image-admin',
            AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AUTO_FEATURED_IMAGE_VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'auto-featured-image-admin',
            AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            AUTO_FEATURED_IMAGE_VERSION,
            true
        );

        // Localize script with admin data
        wp_localize_script( 'auto-featured-image-admin', 'autoFeaturedImageAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'auto_featured_image_admin_nonce' ),
            'currentPage' => $this->current_page,
            'strings' => array(
                'processing' => __( 'Processing...', 'auto-featured-image' ),
                'error' => __( 'An error occurred. Please try again.', 'auto-featured-image' ),
                'success' => __( 'Operation completed successfully.', 'auto-featured-image' ),
                'confirm' => __( 'Are you sure you want to proceed?', 'auto-featured-image' ),
                'cancel' => __( 'Cancel', 'auto-featured-image' ),
                'continue' => __( 'Continue', 'auto-featured-image' ),
                'no_recent_activity' => __( 'No recent activity to display.', 'auto-featured-image' ),
                'loading' => __( 'Loading...', 'auto-featured-image' ),
                'refresh' => __( 'Refresh', 'auto-featured-image' ),
            ),
        ) );

        // Page-specific scripts
        $this->enqueue_page_specific_scripts();
    }

    /**
     * Enqueue page-specific scripts
     *
     * @since 1.0.0
     */
    private function enqueue_page_specific_scripts() {
        switch ( $this->current_page ) {
            case 'dashboard':
                // Enqueue Chart.js for dashboard charts
                wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );

                // Enqueue dashboard-specific JavaScript
                wp_enqueue_script(
                    'auto-featured-image-dashboard',
                    AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/js/dashboard.js',
                    array( 'jquery', 'auto-featured-image-admin', 'chart-js' ),
                    AUTO_FEATURED_IMAGE_VERSION,
                    true
                );

                // Localize dashboard script
                wp_localize_script( 'auto-featured-image-dashboard', 'autoFeaturedImageDashboard', array(
                    'refreshInterval' => 30000, // 30 seconds
                    'chartColors' => array(
                        'primary' => '#2271b1',
                        'secondary' => '#72aee6',
                        'success' => '#00a32a',
                        'warning' => '#dba617',
                        'error' => '#d63638',
                    ),
                ) );
                break;

            case 'bulk-process':
                wp_enqueue_script( 'jquery-ui-progressbar' );

                // Enqueue bulk processing JavaScript
                wp_enqueue_script(
                    'auto-featured-image-bulk-process',
                    AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/js/bulk-process.js',
                    array( 'jquery', 'auto-featured-image-admin', 'jquery-ui-progressbar' ),
                    AUTO_FEATURED_IMAGE_VERSION,
                    true
                );
                break;

            case 'logs':
                wp_enqueue_script( 'jquery-ui-datepicker' );
                wp_enqueue_style( 'jquery-ui-datepicker' );

                // Enqueue Chart.js for analytics charts
                wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true );

                // Enqueue logs-specific JavaScript
                wp_enqueue_script(
                    'auto-featured-image-logs',
                    AUTO_FEATURED_IMAGE_PLUGIN_URL . 'assets/js/logs.js',
                    array( 'jquery', 'auto-featured-image-admin', 'jquery-ui-datepicker', 'chart-js' ),
                    AUTO_FEATURED_IMAGE_VERSION,
                    true
                );
                break;
        }
    }

    /**
     * Initialize admin settings
     *
     * @since 1.0.0
     */
    public function init_admin_settings() {
        // Register settings
        register_setting( 'auto_featured_image_settings', 'auto_featured_image_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );

        // Add settings sections and fields
        $this->add_settings_sections();
    }

    /**
     * Add settings sections
     *
     * @since 1.0.0
     */
    private function add_settings_sections() {
        // General Settings Section
        add_settings_section(
            'auto_featured_image_general',
            __( 'General Settings', 'auto-featured-image' ),
            array( $this, 'render_general_settings_section' ),
            'auto_featured_image_settings'
        );

        // Algorithm Settings Section
        add_settings_section(
            'auto_featured_image_algorithms',
            __( 'Algorithm Settings', 'auto-featured-image' ),
            array( $this, 'render_algorithm_settings_section' ),
            'auto_featured_image_settings'
        );

        // Performance Settings Section
        add_settings_section(
            'auto_featured_image_performance',
            __( 'Performance Settings', 'auto-featured-image' ),
            array( $this, 'render_performance_settings_section' ),
            'auto_featured_image_settings'
        );

        // Add individual settings fields
        $this->add_settings_fields();
    }

    /**
     * Add individual settings fields
     *
     * @since 1.0.0
     */
    private function add_settings_fields() {
        // General settings fields
        add_settings_field(
            'auto_processing_enabled',
            __( 'Auto Processing', 'auto-featured-image' ),
            array( $this, 'render_checkbox_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_general',
            array(
                'field_name' => 'auto_processing_enabled',
                'description' => __( 'Automatically assign featured images to new posts', 'auto-featured-image' ),
            )
        );

        add_settings_field(
            'post_types',
            __( 'Post Types', 'auto-featured-image' ),
            array( $this, 'render_post_types_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_general'
        );

        add_settings_field(
            'min_image_score',
            __( 'Minimum Image Score', 'auto-featured-image' ),
            array( $this, 'render_number_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_general',
            array(
                'field_name' => 'min_image_score',
                'min' => 0,
                'max' => 100,
                'default' => 30,
                'description' => __( 'Minimum quality score (0-100) required for an image to be selected', 'auto-featured-image' ),
            )
        );

        // Algorithm settings fields
        add_settings_field(
            'enabled_algorithms',
            __( 'Enabled Algorithms', 'auto-featured-image' ),
            array( $this, 'render_algorithms_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_algorithms'
        );

        add_settings_field(
            'algorithm_weights',
            __( 'Algorithm Weights', 'auto-featured-image' ),
            array( $this, 'render_algorithm_weights_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_algorithms'
        );

        // Performance settings fields
        add_settings_field(
            'batch_size',
            __( 'Batch Size', 'auto-featured-image' ),
            array( $this, 'render_number_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_performance',
            array(
                'field_name' => 'batch_size',
                'min' => 1,
                'max' => 1000,
                'default' => 25,
                'description' => __( 'Number of posts to process in each batch', 'auto-featured-image' ),
            )
        );

        add_settings_field(
            'adaptive_batch_sizing',
            __( 'Adaptive Batch Sizing', 'auto-featured-image' ),
            array( $this, 'render_checkbox_field' ),
            'auto_featured_image_settings',
            'auto_featured_image_performance',
            array(
                'field_name' => 'adaptive_batch_sizing',
                'description' => __( 'Automatically adjust batch sizes based on performance', 'auto-featured-image' ),
                'default' => true,
            )
        );
    }

    /**
     * Handle AJAX requests
     *
     * @since 1.0.0
     */
    public function handle_ajax_request() {
        // Check if this is a valid AJAX request
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            wp_send_json_error( 'Invalid request.' );
            return;
        }

        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'auto_featured_image_admin_nonce' ) ) {
            wp_send_json_error( 'Security check failed.' );
            return;
        }

        $action = sanitize_text_field( $_POST['action_type'] ?? '' );

        // Check if action is provided
        if ( empty( $action ) ) {
            wp_send_json_error( 'No action specified.' );
            return;
        }

        switch ( $action ) {
            case 'get_queue_status':
                $this->ajax_get_queue_status();
                break;

            case 'start_processing':
                $this->ajax_start_processing();
                break;

            case 'stop_processing':
                $this->ajax_stop_processing();
                break;

            case 'get_logs':
                $this->ajax_get_logs();
                break;

            case 'clear_logs':
                $this->ajax_clear_logs();
                break;

            case 'reset_settings':
                $this->ajax_reset_settings();
                break;

            case 'clear_all_data':
                $this->ajax_clear_all_data();
                break;

            case 'get_statistics':
                $this->ajax_get_statistics();
                break;

            case 'get_recent_activity':
                $this->ajax_get_recent_activity();
                break;

            case 'get_processing_chart_data':
                $this->ajax_get_processing_chart_data();
                break;

            case 'get_algorithm_chart_data':
                $this->ajax_get_algorithm_chart_data();
                break;

            case 'get_performance_chart_data':
                $this->ajax_get_performance_chart_data();
                break;

            case 'pause_processing':
                $this->ajax_pause_processing();
                break;

            case 'start_bulk_processing':
                $this->ajax_start_bulk_processing();
                break;

            case 'estimate_bulk_jobs':
                $this->ajax_estimate_bulk_jobs();
                break;

            case 'get_processing_progress':
                $this->ajax_get_processing_progress();
                break;

            case 'get_post_type_counts':
                $this->ajax_get_post_type_counts();
                break;

            case 'get_processing_history':
                $this->ajax_get_processing_history();
                break;

            case 'get_logs_paginated':
                $this->ajax_get_logs_paginated();
                break;

            case 'get_log_detail':
                $this->ajax_get_log_detail();
                break;

            case 'get_error_summary':
                $this->ajax_get_error_summary();
                break;

            case 'get_log_analytics':
                $this->ajax_get_log_analytics();
                break;

            default:
                wp_send_json_error( 'Invalid action.' );
        }
    }

    /**
     * Display admin notices
     *
     * @since 1.0.0
     */
    public function display_admin_notices() {
        // Only show on our admin pages
        $screen = get_current_screen();
        if ( strpos( $screen->id, 'auto-featured-image' ) === false ) {
            return;
        }

        // Check for Action Scheduler
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            echo '<div class="notice notice-warning"><p>';
            echo __( 'Action Scheduler is not available. Some features may not work properly. Consider installing WooCommerce or Action Scheduler plugin.', 'auto-featured-image' );
            echo '</p></div>';
        }

        // Check database tables
        if ( ! $this->plugin->database->tables_exist() ) {
            echo '<div class="notice notice-error"><p>';
            echo __( 'Database tables are missing. Please deactivate and reactivate the plugin.', 'auto-featured-image' );
            echo '</p></div>';
        }

        // Show success/error messages from URL parameters
        if ( isset( $_GET['message'] ) ) {
            $message_type = sanitize_text_field( $_GET['message'] );
            $this->display_url_message( $message_type );
        }
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     * @since 1.0.0
     */
    public function add_plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=auto-featured-image-settings' ) . '">' . __( 'Settings', 'auto-featured-image' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=auto-featured-image-dashboard' ) . '">' . __( 'Dashboard', 'auto-featured-image' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    /**
     * Add screen options
     *
     * @since 1.0.0
     */
    private function add_screen_options() {
        switch ( $this->current_page ) {
            case 'logs':
                add_screen_option( 'per_page', array(
                    'label' => __( 'Log entries per page', 'auto-featured-image' ),
                    'default' => 20,
                    'option' => 'auto_featured_image_logs_per_page',
                ) );
                break;
                
            case 'queue-monitor':
                add_screen_option( 'per_page', array(
                    'label' => __( 'Jobs per page', 'auto-featured-image' ),
                    'default' => 50,
                    'option' => 'auto_featured_image_jobs_per_page',
                ) );
                break;
        }
    }

    /**
     * Add help tabs
     *
     * @since 1.0.0
     */
    private function add_help_tabs() {
        $screen = get_current_screen();
        
        // Common help tab
        $screen->add_help_tab( array(
            'id' => 'auto-featured-image-overview',
            'title' => __( 'Overview', 'auto-featured-image' ),
            'content' => $this->get_help_content( 'overview' ),
        ) );

        // Page-specific help tabs
        switch ( $this->current_page ) {
            case 'dashboard':
                $screen->add_help_tab( array(
                    'id' => 'auto-featured-image-dashboard-help',
                    'title' => __( 'Dashboard Help', 'auto-featured-image' ),
                    'content' => $this->get_help_content( 'dashboard' ),
                ) );
                break;
                
            case 'settings':
                $screen->add_help_tab( array(
                    'id' => 'auto-featured-image-settings-help',
                    'title' => __( 'Settings Help', 'auto-featured-image' ),
                    'content' => $this->get_help_content( 'settings' ),
                ) );
                break;
        }

        // Set help sidebar
        $screen->set_help_sidebar( $this->get_help_sidebar() );
    }

    /**
     * Get help content for specific section
     *
     * @param string $section Help section
     * @return string Help content
     * @since 1.0.0
     */
    private function get_help_content( $section ) {
        switch ( $section ) {
            case 'overview':
                return '<p>' . __( 'Auto Featured Image automatically assigns featured images to your posts using advanced algorithms and image analysis.', 'auto-featured-image' ) . '</p>';
                
            case 'dashboard':
                return '<p>' . __( 'The dashboard shows real-time statistics about your image processing queue, performance metrics, and system status.', 'auto-featured-image' ) . '</p>';
                
            case 'settings':
                return '<p>' . __( 'Configure algorithm preferences, batch processing settings, and performance options to optimize the plugin for your site.', 'auto-featured-image' ) . '</p>';
                
            default:
                return '';
        }
    }

    /**
     * Get help sidebar content
     *
     * @return string Help sidebar content
     * @since 1.0.0
     */
    private function get_help_sidebar() {
        return '<p><strong>' . __( 'For more information:', 'auto-featured-image' ) . '</strong></p>' .
               '<p><a href="#" target="_blank">' . __( 'Plugin Documentation', 'auto-featured-image' ) . '</a></p>' .
               '<p><a href="#" target="_blank">' . __( 'Support Forum', 'auto-featured-image' ) . '</a></p>';
    }

    /**
     * Render admin page template
     *
     * @param string $template Template name
     * @param array  $data Template data
     * @since 1.0.0
     */
    private function render_template( $template, $data = array() ) {
        // Extract data for template
        extract( $data );

        // Include header
        include AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'admin/partials/admin-header.php';

        // Include page template
        $template_file = AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'admin/partials/' . $template . '.php';
        if ( file_exists( $template_file ) ) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' .
                 sprintf( __( 'Template not found: %s', 'auto-featured-image' ), esc_html( $template ) ) .
                 '</p></div>';
        }

        // Include footer
        include AUTO_FEATURED_IMAGE_PLUGIN_DIR . 'admin/partials/admin-footer.php';
    }

    /**
     * Get tabs for current page
     *
     * @param string $page Current page
     * @return array Page tabs
     * @since 1.0.0
     */
    private function get_page_tabs( $page ) {
        $tabs = array();

        switch ( $page ) {
            case 'auto-featured-image-dashboard':
                $tabs = array(
                    'overview' => array( 'title' => __( 'Overview', 'auto-featured-image' ) ),
                    'statistics' => array( 'title' => __( 'Statistics', 'auto-featured-image' ) ),
                    'performance' => array( 'title' => __( 'Performance', 'auto-featured-image' ) ),
                );
                break;

            case 'auto-featured-image-settings':
                $tabs = array(
                    'general' => array( 'title' => __( 'General', 'auto-featured-image' ) ),
                    'algorithms' => array( 'title' => __( 'Algorithms', 'auto-featured-image' ) ),
                    'performance' => array( 'title' => __( 'Performance', 'auto-featured-image' ) ),
                    'advanced' => array( 'title' => __( 'Advanced', 'auto-featured-image' ) ),
                );
                break;

            case 'auto-featured-image-logs':
                $tabs = array(
                    'recent' => array( 'title' => __( 'Recent Logs', 'auto-featured-image' ) ),
                    'errors' => array( 'title' => __( 'Errors', 'auto-featured-image' ) ),
                    'analytics' => array( 'title' => __( 'Analytics', 'auto-featured-image' ) ),
                );
                break;
        }

        return $tabs;
    }

    // Page rendering methods - basic implementations for now
    public function render_dashboard_page() {
        $this->render_template( 'dashboard', array(
            'queue_stats' => $this->plugin->queue->get_queue_stats(),
            'performance_metrics' => $this->plugin->batch_manager->get_metrics(),
        ) );
    }

    public function render_settings_page() {
        $this->render_template( 'settings', array(
            'settings' => get_option( 'auto_featured_image_settings', array() ),
            'algorithms' => $this->plugin->algorithms->get_algorithms(),
        ) );
    }

    public function render_bulk_process_page() {
        $this->render_template( 'bulk-process', array(
            'post_types' => get_post_types( array( 'public' => true ), 'objects' ),
            'queue_status' => $this->plugin->queue->is_processing_active(),
        ) );
    }

    public function render_queue_monitor_page() {
        $this->render_template( 'queue-monitor', array(
            'queue_stats' => $this->plugin->queue->get_queue_stats(),
            'recent_jobs' => $this->plugin->database->get_recent_jobs( 50 ),
        ) );
    }

    public function render_logs_page() {
        $this->render_template( 'logs', array(
            'recent_logs' => $this->plugin->database->get_recent_logs( 100 ),
            'log_levels' => array( 'error', 'warning', 'info', 'debug' ),
        ) );
    }

    public function render_tools_page() {
        $this->render_template( 'tools', array(
            'database_info' => $this->plugin->database->get_table_info(),
            'system_info' => $this->get_system_info(),
        ) );
    }

    /**
     * AJAX: Get queue status
     *
     * @since 1.0.0
     */
    private function ajax_get_queue_status() {
        try {
            $queue_stats = $this->plugin->queue->get_queue_stats();
            $is_processing = $this->plugin->queue->is_processing_active();
            $is_paused = $this->plugin->queue->is_paused();

            // Ensure queue_stats is an array with default values
            if ( ! is_array( $queue_stats ) ) {
                $queue_stats = array();
            }

            $queue_stats = wp_parse_args( $queue_stats, array(
                'total_jobs' => 0,
                'pending_jobs' => 0,
                'completed_jobs' => 0,
                'failed_jobs' => 0,
                'processing_jobs' => 0,
            ) );

            $status = 'idle';
            if ( $is_paused ) {
                $status = 'paused';
            } elseif ( $is_processing ) {
                $status = 'active';
            }

            wp_send_json_success( array(
                'status' => $status,
                'metrics' => array(
                    'total_jobs' => (int) $queue_stats['total_jobs'],
                    'pending_jobs' => (int) $queue_stats['pending_jobs'],
                    'completed_jobs' => (int) $queue_stats['completed_jobs'],
                'failed_jobs' => (int) $queue_stats['failed_jobs'],
                'processing_jobs' => (int) $queue_stats['processing_jobs'],
                'success_rate' => isset( $queue_stats['success_rate'] ) ? $queue_stats['success_rate'] : '0%',
            ),
            'is_processing' => (bool) $is_processing,
            'is_paused' => (bool) $is_paused,
        ) );

        } catch ( Exception $e ) {
            wp_send_json_error( 'Failed to get queue status: ' . $e->getMessage() );
        }
    }

    /**
     * AJAX: Start processing
     *
     * @since 1.0.0
     */
    private function ajax_start_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_types = $_POST['post_types'] ?? array( 'post' );
        $batch_size = intval( $_POST['batch_size'] ?? 25 );
        $skip_existing = ! empty( $_POST['skip_existing'] );

        $result = $this->plugin->queue->start_processing( array(
            'post_types' => $post_types,
            'batch_size' => $batch_size,
            'skip_existing' => $skip_existing,
            'priority' => 'normal',
        ) );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'message' => __( 'Bulk processing started successfully.', 'auto-featured-image' ),
            ) );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    /**
     * AJAX: Stop processing
     *
     * @since 1.0.0
     */
    private function ajax_stop_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $this->plugin->queue->pause_processing();

        wp_send_json_success( array(
            'message' => 'Processing stopped successfully.',
        ) );
    }

    /**
     * AJAX: Get logs
     *
     * @since 1.0.0
     */
    private function ajax_get_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'auto-featured-image' ) );
        }

        $level = sanitize_text_field( $_POST['level'] ?? 'all' );
        $limit = intval( $_POST['limit'] ?? 100 );
        $offset = intval( $_POST['offset'] ?? 0 );

        $logs = $this->plugin->database->get_logs( array(
            'level' => $level,
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'DESC',
        ) );

        wp_send_json_success( array(
            'logs' => $logs,
            'total' => $this->plugin->database->count_logs( $level ),
        ) );
    }

    /**
     * AJAX: Clear logs
     *
     * @since 1.0.0
     */
    private function ajax_clear_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'auto-featured-image' ) );
        }

        $level = sanitize_text_field( $_POST['level'] ?? 'all' );
        $older_than_days = intval( $_POST['older_than_days'] ?? 0 );

        $deleted = $this->plugin->database->clear_logs( $level, $older_than_days );

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: Number of deleted log entries */
                __( 'Cleared %d log entries.', 'auto-featured-image' ),
                $deleted
            ),
            'deleted_count' => $deleted,
        ) );
    }

    /**
     * AJAX: Reset settings
     *
     * @since 1.0.0
     */
    private function ajax_reset_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'auto-featured-image' ) );
        }

        delete_option( 'auto_featured_image_settings' );

        wp_send_json_success( array(
            'message' => __( 'Settings reset to defaults successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Clear all data
     *
     * @since 1.0.0
     */
    private function ajax_clear_all_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'auto-featured-image' ) );
        }

        // Clear all plugin data
        $this->plugin->database->clear_all_data();

        // Reset performance metrics
        $this->plugin->batch_manager->reset_metrics();

        wp_send_json_success( array(
            'message' => __( 'All plugin data cleared successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Get statistics
     *
     * @since 1.0.0
     */
    private function ajax_get_statistics() {
        $queue_stats = $this->plugin->queue->get_queue_stats();
        $performance_metrics = $this->plugin->batch_manager->get_metrics();

        wp_send_json_success( array(
            'queue' => $queue_stats,
            'performance' => $performance_metrics,
            'system' => $this->get_system_info(),
            'detailed' => $this->get_detailed_statistics(),
        ) );
    }

    /**
     * AJAX: Get recent activity
     *
     * @since 1.0.0
     */
    private function ajax_get_recent_activity() {
        $recent_logs = $this->plugin->database->get_logs( array(
            'level' => 'info',
            'limit' => 10,
            'order' => 'DESC',
        ) );

        $activities = array();
        foreach ( $recent_logs as $log ) {
            $activities[] = array(
                'title' => $log['message'],
                'time' => human_time_diff( strtotime( $log['created_at'] ) ) . ' ago',
                'level' => $log['level'],
            );
        }

        wp_send_json_success( $activities );
    }

    /**
     * AJAX: Get processing chart data
     *
     * @since 1.0.0
     */
    private function ajax_get_processing_chart_data() {
        $days = intval( $_POST['days'] ?? 7 );
        $chart_data = $this->plugin->database->get_processing_chart_data( $days );

        wp_send_json_success( $chart_data );
    }

    /**
     * AJAX: Get algorithm chart data
     *
     * @since 1.0.0
     */
    private function ajax_get_algorithm_chart_data() {
        $days = intval( $_POST['days'] ?? 30 );
        $algorithm_data = $this->plugin->database->get_algorithm_performance_data( $days );

        wp_send_json_success( $algorithm_data );
    }

    /**
     * AJAX: Get performance chart data
     *
     * @since 1.0.0
     */
    private function ajax_get_performance_chart_data() {
        $hours = intval( $_POST['hours'] ?? 24 );
        $performance_data = $this->plugin->batch_manager->get_performance_chart_data( $hours );

        wp_send_json_success( $performance_data );
    }

    /**
     * AJAX: Pause processing
     *
     * @since 1.0.0
     */
    private function ajax_pause_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'auto-featured-image' ) );
        }

        $this->plugin->queue->pause_processing();

        wp_send_json_success( array(
            'message' => __( 'Processing paused successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * Get detailed statistics
     *
     * @return array Detailed statistics
     * @since 1.0.0
     */
    private function get_detailed_statistics() {
        try {
            $stats = $this->plugin->database->get_detailed_statistics();

            // Ensure stats is an array
            if ( ! is_array( $stats ) ) {
                $stats = array();
            }

            return array(
                'total_processed' => isset( $stats['jobs']['completed_jobs'] ) ? (int) $stats['jobs']['completed_jobs'] : 0,
                'total_processed_change' => '+0%', // Placeholder for now
                'images_assigned' => isset( $stats['jobs']['completed_jobs'] ) ? (int) $stats['jobs']['completed_jobs'] : 0,
            'images_assigned_change' => '+0%', // Placeholder for now
            'avg_processing_time' => $this->format_duration( $stats['avg_processing_time'] ?? 0 ),
            'avg_processing_time_change' => '+0s', // Placeholder for now
            'queue_throughput' => isset( $stats['recent_activity']['jobs_last_24h'] ) ? (int) $stats['recent_activity']['jobs_last_24h'] : 0,
            'queue_throughput_change' => '+0%', // Placeholder for now
            'success_rate' => $stats['success_rate'] ?? 0,
            'error_count' => isset( $stats['errors']['total_errors'] ) ? (int) $stats['errors']['total_errors'] : 0,
        );

        } catch ( Exception $e ) {
            // Return default values if there's an error
            return array(
                'total_processed' => 0,
                'total_processed_change' => '+0%',
                'images_assigned' => 0,
                'images_assigned_change' => '+0%',
                'avg_processing_time' => '0s',
                'avg_processing_time_change' => '+0s',
                'queue_throughput' => 0,
                'queue_throughput_change' => '+0%',
                'success_rate' => 0,
                'error_count' => 0,
            );
        }
    }

    /**
     * Calculate percentage change
     *
     * @param float $old_value Old value
     * @param float $new_value New value
     * @return string Formatted change
     * @since 1.0.0
     */
    private function calculate_change( $old_value, $new_value ) {
        if ( $old_value == 0 ) {
            return $new_value > 0 ? '+100%' : '0%';
        }

        $change = ( ( $new_value - $old_value ) / $old_value ) * 100;
        $sign = $change >= 0 ? '+' : '';

        return $sign . number_format( $change, 1 ) . '%';
    }

    /**
     * Calculate time change
     *
     * @param float $old_time Old time in seconds
     * @param float $new_time New time in seconds
     * @return string Formatted time change
     * @since 1.0.0
     */
    private function calculate_time_change( $old_time, $new_time ) {
        $change = $new_time - $old_time;
        $sign = $change >= 0 ? '+' : '';

        return $sign . $this->format_duration( abs( $change ) );
    }

    /**
     * Format duration
     *
     * @param float $seconds Duration in seconds
     * @return string Formatted duration
     * @since 1.0.0
     */
    private function format_duration( $seconds ) {
        if ( $seconds < 1 ) {
            return number_format( $seconds * 1000, 0 ) . 'ms';
        } elseif ( $seconds < 60 ) {
            return number_format( $seconds, 1 ) . 's';
        } else {
            $minutes = floor( $seconds / 60 );
            $remaining_seconds = $seconds % 60;
            return $minutes . 'm ' . number_format( $remaining_seconds, 0 ) . 's';
        }
    }

    /**
     * AJAX: Start bulk processing
     *
     * @since 1.0.0
     */
    private function ajax_start_bulk_processing() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_types = $_POST['post_types'] ?? array( 'post' );
        $skip_existing = ! empty( $_POST['skip_existing'] );
        $batch_size = max( 1, min( 1000, intval( $_POST['batch_size'] ?? 25 ) ) );
        $priority = sanitize_text_field( $_POST['priority'] ?? 'normal' );
        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to = sanitize_text_field( $_POST['date_to'] ?? '' );
        $specific_posts = sanitize_text_field( $_POST['specific_posts'] ?? '' );

        // Parse specific posts
        $post_ids = array();
        if ( ! empty( $specific_posts ) ) {
            $post_ids = array_map( 'intval', array_filter( explode( ',', $specific_posts ) ) );
        }

        $result = $this->plugin->queue->start_bulk_processing( array(
            'post_types' => $post_types,
            'skip_existing' => $skip_existing,
            'batch_size' => $batch_size,
            'priority' => $priority,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'post_ids' => $post_ids,
        ) );

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'message' => __( 'Bulk processing started successfully.', 'auto-featured-image' ),
                'job_count' => $result['job_count'] ?? 0,
            ) );
        } else {
            wp_send_json_error( $result['message'] ?? __( 'Failed to start bulk processing.', 'auto-featured-image' ) );
        }
    }

    /**
     * AJAX: Estimate bulk jobs
     *
     * @since 1.0.0
     */
    private function ajax_estimate_bulk_jobs() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_types = $_POST['post_types'] ?? array( 'post' );
        $skip_existing = ! empty( $_POST['skip_existing'] );
        $batch_size = max( 1, min( 1000, intval( $_POST['batch_size'] ?? 25 ) ) );
        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to = sanitize_text_field( $_POST['date_to'] ?? '' );
        $specific_posts = sanitize_text_field( $_POST['specific_posts'] ?? '' );

        // Parse specific posts
        $post_ids = array();
        if ( ! empty( $specific_posts ) ) {
            $post_ids = array_map( 'intval', array_filter( explode( ',', $specific_posts ) ) );
        }

        $estimation = $this->plugin->queue->estimate_bulk_jobs( array(
            'post_types' => $post_types,
            'skip_existing' => $skip_existing,
            'batch_size' => $batch_size,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'post_ids' => $post_ids,
        ) );

        wp_send_json_success( $estimation );
    }

    /**
     * AJAX: Get processing progress
     *
     * @since 1.0.0
     */
    private function ajax_get_processing_progress() {
        try {
            $progress = $this->plugin->queue->get_processing_progress();

            // Ensure progress is an array and sanitize it
            if ( ! is_array( $progress ) ) {
                $progress = array();
            }

            // Sanitize the progress data to ensure no invalid callbacks
            $sanitized_progress = array();
            foreach ( $progress as $key => $value ) {
                // Only allow scalar values (string, int, float, bool) and null
                if ( is_scalar( $value ) || is_null( $value ) ) {
                    // Additional safety: convert to appropriate types
                    if ( is_bool( $value ) ) {
                        $sanitized_progress[ $key ] = (bool) $value;
                    } elseif ( is_numeric( $value ) ) {
                        $sanitized_progress[ $key ] = is_float( $value ) ? (float) $value : (int) $value;
                    } elseif ( is_string( $value ) ) {
                        $sanitized_progress[ $key ] = (string) $value;
                    } else {
                        $sanitized_progress[ $key ] = $value;
                    }
                }
            }

            // Ensure required fields exist with default values
            $sanitized_progress = wp_parse_args( $sanitized_progress, array(
                'total' => 0,
                'processed' => 0,
                'remaining' => 0,
                'success' => 0,
                'failed' => 0,
                'percentage' => 0,
                'status' => 'idle',
                'is_processing' => false,
                'is_paused' => false,
            ) );

            // Final safety check: ensure data can be JSON encoded/decoded safely
            $json_test = json_encode( $sanitized_progress );
            if ( $json_test === false ) {
                wp_send_json_error( 'Data serialization failed' );
                return;
            }

            $final_data = json_decode( $json_test, true );
            if ( $final_data === null ) {
                wp_send_json_error( 'Data deserialization failed' );
                return;
            }

            // Debug logging (remove in production)
            error_log( 'Auto Featured Image: Sending progress data: ' . print_r( $final_data, true ) );

            wp_send_json_success( $final_data );

        } catch ( Exception $e ) {
            wp_send_json_error( 'Failed to get processing progress: ' . $e->getMessage() );
        }
    }

    /**
     * AJAX: Get post type counts
     *
     * @since 1.0.0
     */
    private function ajax_get_post_type_counts() {
        $post_types = $_POST['post_types'] ?? array( 'post' );
        $skip_existing = ! empty( $_POST['skip_existing'] );

        $counts = array();
        foreach ( $post_types as $post_type ) {
            $post_type = sanitize_text_field( $post_type );

            $args = array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
            );

            if ( $skip_existing ) {
                $args['meta_query'] = array(
                    array(
                        'key' => '_thumbnail_id',
                        'compare' => 'NOT EXISTS',
                    ),
                );
            }

            $query = new WP_Query( $args );
            $counts[ $post_type ] = $query->found_posts;
        }

        wp_send_json_success( $counts );
    }

    /**
     * AJAX: Get processing history
     *
     * @since 1.0.0
     */
    private function ajax_get_processing_history() {
        $history = $this->plugin->database->get_processing_history( 10 );
        wp_send_json_success( $history );
    }

    /**
     * AJAX: Get logs with pagination
     *
     * @since 1.0.0
     */
    private function ajax_get_logs_paginated() {
        $page = max( 1, intval( $_POST['page'] ?? 1 ) );
        $per_page = max( 1, min( 200, intval( $_POST['per_page'] ?? 50 ) ) );
        $level = sanitize_text_field( $_POST['level'] ?? 'all' );
        $date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
        $date_to = sanitize_text_field( $_POST['date_to'] ?? '' );
        $search = sanitize_text_field( $_POST['search'] ?? '' );

        $args = array(
            'level' => $level !== 'all' ? $level : null,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'search' => $search,
            'page' => $page,
            'per_page' => $per_page,
            'order' => 'DESC',
        );

        $logs = $this->plugin->database->get_logs_paginated( $args );

        wp_send_json_success( $logs );
    }

    /**
     * AJAX: Get log detail
     *
     * @since 1.0.0
     */
    private function ajax_get_log_detail() {
        $log_id = intval( $_POST['log_id'] ?? 0 );

        if ( ! $log_id ) {
            wp_send_json_error( __( 'Invalid log ID.', 'auto-featured-image' ) );
        }

        $log = $this->plugin->database->get_log_by_id( $log_id );

        if ( ! $log ) {
            wp_send_json_error( __( 'Log entry not found.', 'auto-featured-image' ) );
        }

        wp_send_json_success( $log );
    }

    /**
     * AJAX: Get error summary
     *
     * @since 1.0.0
     */
    private function ajax_get_error_summary() {
        $summary = $this->plugin->database->get_error_summary();
        wp_send_json_success( $summary );
    }

    /**
     * AJAX: Get log analytics
     *
     * @since 1.0.0
     */
    private function ajax_get_log_analytics() {
        $analytics = array(
            'processing_volume' => $this->plugin->database->get_processing_volume( 7 ),
            'avg_performance' => $this->format_duration( $this->plugin->database->get_avg_processing_time( 7 ) ),
            'success_rate' => $this->plugin->database->get_success_rate( 7 ),
            'activity_timeline' => $this->plugin->database->get_activity_timeline( 30 ),
            'algorithm_performance' => $this->plugin->database->get_algorithm_performance_stats( 30 ),
        );

        wp_send_json_success( $analytics );
    }

    /**
     * Get system information
     *
     * @return array System information
     * @since 1.0.0
     */
    private function get_system_info() {
        global $wpdb;

        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo( 'version' ),
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
            'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
            'post_max_size' => ini_get( 'post_max_size' ),
            'mysql_version' => $wpdb->db_version(),
            'action_scheduler_available' => function_exists( 'as_schedule_single_action' ),
            'gd_extension' => extension_loaded( 'gd' ),
            'imagick_extension' => extension_loaded( 'imagick' ),
        );
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments
     * @since 1.0.0
     */
    public function render_checkbox_field( $args ) {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $field_name = $args['field_name'];
        $value = $settings[ $field_name ] ?? ( $args['default'] ?? false );
        $description = $args['description'] ?? '';

        echo '<label>';
        echo '<input type="checkbox" ';
        echo 'name="auto_featured_image_settings[' . esc_attr( $field_name ) . ']" ';
        echo 'value="1" ';
        checked( $value );
        echo ' />';
        echo esc_html( $description );
        echo '</label>';
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments
     * @since 1.0.0
     */
    public function render_number_field( $args ) {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $field_name = $args['field_name'];
        $value = $settings[ $field_name ] ?? ( $args['default'] ?? 0 );
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 999999;
        $description = $args['description'] ?? '';

        echo '<input type="number" ';
        echo 'name="auto_featured_image_settings[' . esc_attr( $field_name ) . ']" ';
        echo 'value="' . esc_attr( $value ) . '" ';
        echo 'min="' . esc_attr( $min ) . '" ';
        echo 'max="' . esc_attr( $max ) . '" ';
        echo 'class="small-text" />';

        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * Render post types field
     *
     * @since 1.0.0
     */
    public function render_post_types_field() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $enabled_post_types = $settings['post_types'] ?? array( 'post' );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );

        foreach ( $post_types as $post_type ) {
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" ';
            echo 'name="auto_featured_image_settings[post_types][]" ';
            echo 'value="' . esc_attr( $post_type->name ) . '" ';
            checked( in_array( $post_type->name, $enabled_post_types ) );
            echo ' />';
            echo esc_html( $post_type->label );
            echo '</label>';
        }

        echo '<p class="description">';
        echo esc_html__( 'Select which post types should have featured images automatically assigned.', 'auto-featured-image' );
        echo '</p>';
    }

    /**
     * Render algorithms field
     *
     * @since 1.0.0
     */
    public function render_algorithms_field() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $algorithms = $this->plugin->algorithms->get_algorithms();
        $enabled_algorithms = $settings['enabled_algorithms'] ?? array_keys( $algorithms );

        foreach ( $algorithms as $algorithm_key => $algorithm_data ) {
            echo '<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">';
            echo '<label style="display: block; font-weight: 600; margin-bottom: 5px;">';
            echo '<input type="checkbox" ';
            echo 'name="auto_featured_image_settings[enabled_algorithms][]" ';
            echo 'value="' . esc_attr( $algorithm_key ) . '" ';
            checked( in_array( $algorithm_key, $enabled_algorithms ) );
            echo ' />';
            echo esc_html( $algorithm_data['name'] );
            echo '</label>';
            echo '<p class="description" style="margin: 0;">';
            echo esc_html( $algorithm_data['description'] );
            echo '</p>';
            echo '</div>';
        }

        echo '<p class="description">';
        echo esc_html__( 'Select which algorithms should be used for image selection.', 'auto-featured-image' );
        echo '</p>';
    }

    /**
     * Render algorithm weights field
     *
     * @since 1.0.0
     */
    public function render_algorithm_weights_field() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        $algorithm_weights = $settings['algorithm_weights'] ?? array(
            'content_relevance' => 30,
            'image_quality' => 25,
            'position_priority' => 20,
            'semantic_match' => 15,
            'user_preference' => 10,
        );

        $weight_labels = array(
            'content_relevance' => __( 'Content Relevance', 'auto-featured-image' ),
            'image_quality' => __( 'Image Quality', 'auto-featured-image' ),
            'position_priority' => __( 'Position Priority', 'auto-featured-image' ),
            'semantic_match' => __( 'Semantic Matching', 'auto-featured-image' ),
            'user_preference' => __( 'User Preference', 'auto-featured-image' ),
        );

        echo '<table class="form-table">';
        foreach ( $weight_labels as $weight_key => $weight_label ) {
            echo '<tr>';
            echo '<th scope="row">' . esc_html( $weight_label ) . '</th>';
            echo '<td>';
            echo '<input type="range" ';
            echo 'name="auto_featured_image_settings[algorithm_weights][' . esc_attr( $weight_key ) . ']" ';
            echo 'value="' . esc_attr( $algorithm_weights[ $weight_key ] ) . '" ';
            echo 'min="0" max="50" step="1" style="width: 200px;" />';
            echo '<span class="weight-value">' . esc_html( $algorithm_weights[ $weight_key ] ) . '%</span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    // Settings methods - enhanced implementations
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Sanitize boolean fields
        $boolean_fields = array( 'auto_processing_enabled', 'skip_existing', 'adaptive_batch_sizing', 'debug_mode' );
        foreach ( $boolean_fields as $field ) {
            $sanitized[ $field ] = ! empty( $input[ $field ] );
        }

        // Sanitize numeric fields with ranges
        $numeric_fields = array(
            'batch_size' => array( 'min' => 1, 'max' => 1000, 'default' => 25 ),
            'min_image_score' => array( 'min' => 0, 'max' => 100, 'default' => 30 ),
            'target_execution_time' => array( 'min' => 5, 'max' => 300, 'default' => 30 ),
            'max_retry_attempts' => array( 'min' => 0, 'max' => 10, 'default' => 3 ),
            'log_retention_days' => array( 'min' => 1, 'max' => 365, 'default' => 30 ),
            'cleanup_completed_jobs' => array( 'min' => 1, 'max' => 90, 'default' => 7 ),
        );

        foreach ( $numeric_fields as $field => $constraints ) {
            if ( isset( $input[ $field ] ) ) {
                $value = intval( $input[ $field ] );
                $sanitized[ $field ] = max( $constraints['min'], min( $constraints['max'], $value ) );
            }
        }

        // Sanitize array fields
        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            $sanitized['post_types'] = array_map( 'sanitize_text_field', $input['post_types'] );
        }

        if ( isset( $input['enabled_algorithms'] ) && is_array( $input['enabled_algorithms'] ) ) {
            $sanitized['enabled_algorithms'] = array_map( 'sanitize_text_field', $input['enabled_algorithms'] );
        }

        // Sanitize algorithm weights
        if ( isset( $input['algorithm_weights'] ) && is_array( $input['algorithm_weights'] ) ) {
            $sanitized['algorithm_weights'] = array();
            foreach ( $input['algorithm_weights'] as $key => $weight ) {
                $sanitized['algorithm_weights'][ sanitize_text_field( $key ) ] = max( 0, min( 50, intval( $weight ) ) );
            }
        }

        // Sanitize select fields
        $select_fields = array( 'primary_algorithm', 'fallback_algorithm' );
        foreach ( $select_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        return $sanitized;
    }

    public function render_general_settings_section() {
        echo '<p>' . esc_html__( 'Configure general plugin settings and behavior.', 'auto-featured-image' ) . '</p>';
    }

    public function render_algorithm_settings_section() {
        echo '<p>' . esc_html__( 'Configure image selection algorithms and their priorities.', 'auto-featured-image' ) . '</p>';
    }

    public function render_performance_settings_section() {
        echo '<p>' . esc_html__( 'Optimize performance settings for your server environment.', 'auto-featured-image' ) . '</p>';
    }

    private function display_url_message( $message_type ) {
        $messages = array(
            'settings-saved' => array(
                'type' => 'success',
                'message' => __( 'Settings saved successfully.', 'auto-featured-image' ),
            ),
            'processing-started' => array(
                'type' => 'success',
                'message' => __( 'Bulk processing started successfully.', 'auto-featured-image' ),
            ),
            'processing-stopped' => array(
                'type' => 'info',
                'message' => __( 'Processing stopped.', 'auto-featured-image' ),
            ),
            'logs-cleared' => array(
                'type' => 'success',
                'message' => __( 'Logs cleared successfully.', 'auto-featured-image' ),
            ),
            'error' => array(
                'type' => 'error',
                'message' => __( 'An error occurred. Please try again.', 'auto-featured-image' ),
            ),
        );

        if ( isset( $messages[ $message_type ] ) ) {
            $message = $messages[ $message_type ];
            echo '<div class="notice notice-' . esc_attr( $message['type'] ) . ' is-dismissible">';
            echo '<p>' . esc_html( $message['message'] ) . '</p>';
            echo '</div>';
        }
    }
}
        $field_name = $args['field_name'];
        $value = $settings[ $field_name ] ?? ( $args['default'] ?? 0 );
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 100;
        $step = $args['step'] ?? 1;
        $description = $args['description'] ?? '';

        echo '<input type="number" ';
        echo 'name="auto_featured_image_settings[' . esc_attr( $field_name ) . ']" ';
        echo 'value="' . esc_attr( $value ) . '" ';
        echo 'min="' . esc_attr( $min ) . '" ';
        echo 'max="' . esc_attr( $max ) . '" ';
        echo 'step="' . esc_attr( $step ) . '" ';
        echo 'class="small-text" />';
        
        if ( $description ) {
            echo '<p class="description">' . esc_html( $description ) . '</p>';
        }
    }

    /**
     * AJAX: Get queue status
     *
     * @since 1.0.0
     */
    public function ajax_get_queue_status() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $status = $this->plugin->queue->get_queue_stats();
        wp_send_json_success( $status );
    }

    /**
     * AJAX: Start processing
     *
     * @since 1.0.0
     */
    public function ajax_start_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        if ( $this->plugin->queue->is_processing_active() ) {
            wp_send_json_error( 'Processing is already active.' );
            return;
        }

        $result = $this->plugin->queue->start_processing();
        
        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Processing started successfully.', 'auto-featured-image' ),
            ) );
        } else {
            wp_send_json_error( 'Failed to start processing.' );
        }
    }

    /**
     * AJAX: Stop processing
     *
     * @since 1.0.0
     */
    public function ajax_stop_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $this->plugin->queue->pause_processing();

        wp_send_json_success( array(
            'message' => __( 'Processing stopped successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Get logs
     *
     * @since 1.0.0
     */
    public function ajax_get_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $page = intval( $_POST['page'] ?? 1 );
        $per_page = intval( $_POST['per_page'] ?? 20 );
        $level = sanitize_text_field( $_POST['level'] ?? '' );

        $logs = $this->plugin->database->get_logs_paginated( $page, $per_page, $level );
        wp_send_json_success( $logs );
    }

    /**
     * AJAX: Clear logs
     *
     * @since 1.0.0
     */
    public function ajax_clear_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $this->plugin->logger->clear_all_logs();
        wp_send_json_success( array(
            'message' => __( 'Logs cleared successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Reset settings
     *
     * @since 1.0.0
     */
    public function ajax_reset_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        delete_option( 'auto_featured_image_settings' );
        wp_send_json_success( array(
            'message' => __( 'Settings reset successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Clear all data
     *
     * @since 1.0.0
     */
    public function ajax_clear_all_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $this->plugin->database->clear_all_data();
        wp_send_json_success( array(
            'message' => __( 'All data cleared successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Get statistics
     *
     * @since 1.0.0
     */
    public function ajax_get_statistics() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $stats = $this->plugin->database->get_detailed_statistics();
        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Get recent activity
     *
     * @since 1.0.0
     */
    public function ajax_get_recent_activity() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $activity = $this->plugin->logger->get_recent_logs( 10 );
        wp_send_json_success( $activity );
    }

    /**
     * AJAX: Get processing chart data
     *
     * @since 1.0.0
     */
    public function ajax_get_processing_chart_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Mock data for now - implement actual chart data logic
        $data = array(
            'labels' => array( 'Jan', 'Feb', 'Mar', 'Apr', 'May' ),
            'datasets' => array(
                array(
                    'label' => 'Processed',
                    'data' => array( 10, 20, 30, 40, 50 ),
                ),
            ),
        );
        
        wp_send_json_success( $data );
    }

    /**
     * AJAX: Get algorithm chart data
     *
     * @since 1.0.0
     */
    public function ajax_get_algorithm_chart_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Mock data for now - implement actual chart data logic
        $data = array(
            'labels' => array( 'First Image', 'Content Analysis', 'Category Based' ),
            'datasets' => array(
                array(
                    'label' => 'Success Rate',
                    'data' => array( 85, 70, 60 ),
                ),
            ),
        );
        
        wp_send_json_success( $data );
    }

    /**
     * AJAX: Get performance chart data
     *
     * @since 1.0.0
     */
    public function ajax_get_performance_chart_data() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        // Mock data for now - implement actual chart data logic
        $data = array(
            'labels' => array( 'Memory', 'CPU', 'Database' ),
            'datasets' => array(
                array(
                    'label' => 'Usage %',
                    'data' => array( 45, 30, 25 ),
                ),
            ),
        );
        
        wp_send_json_success( $data );
    }

    /**
     * AJAX: Pause processing
     *
     * @since 1.0.0
     */
    public function ajax_pause_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $this->plugin->queue->pause_processing();
        wp_send_json_success( array(
            'message' => __( 'Processing paused successfully.', 'auto-featured-image' ),
        ) );
    }

    /**
     * AJAX: Start bulk processing
     *
     * @since 1.0.0
     */
    public function ajax_start_bulk_processing() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_types = array_map( 'sanitize_text_field', $_POST['post_types'] ?? array( 'post' ) );
        $batch_size = intval( $_POST['batch_size'] ?? 10 );
        $skip_existing = ! empty( $_POST['skip_existing'] );

        $result = $this->plugin->queue->start_bulk_processing( array(
            'post_types' => $post_types,
            'batch_size' => $batch_size,
            'skip_existing' => $skip_existing,
            'priority' => 'normal',
        ) );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Bulk processing started successfully.', 'auto-featured-image' ),
            ) );
        } else {
            wp_send_json_error( 'Failed to start bulk processing.' );
        }
    }

    /**
     * AJAX: Estimate bulk jobs
     *
     * @since 1.0.0
     */
    public function ajax_estimate_bulk_jobs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $post_types = array_map( 'sanitize_text_field', $_POST['post_types'] ?? array( 'post' ) );
        $skip_existing = ! empty( $_POST['skip_existing'] );

        $estimate = $this->plugin->queue->estimate_bulk_jobs( $post_types, $skip_existing );
        wp_send_json_success( $estimate );
    }

    /**
     * AJAX: Get processing progress
     *
     * @since 1.0.0
     */
    public function ajax_get_processing_progress() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $progress = $this->plugin->queue->get_processing_progress();
        wp_send_json_success( $progress );
    }
}