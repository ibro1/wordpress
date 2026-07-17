<?php
// Task 1.3: Core Plugin Class Architecture

class AFI_Core {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = AFI_VERSION;
        $this->plugin_name = 'auto-featured-image';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once AFI_PLUGIN_DIR . 'admin/class-afi-admin.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-processor.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-image-selector.php';
        require_once AFI_PLUGIN_DIR . 'includes/class-afi-logger.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new AFI_Admin( $this->get_plugin_name(), $this->get_version() );

        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $plugin_admin, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $plugin_admin, 'register_settings' ) );

        // AJAX hooks for admin dashboard
        add_action( 'wp_ajax_afi_start_processing', array( $plugin_admin, 'ajax_start_processing' ) );
        add_action( 'wp_ajax_afi_pause_processing', array( $plugin_admin, 'ajax_pause_processing' ) );
        add_action( 'wp_ajax_afi_get_status', array( $plugin_admin, 'ajax_get_status' ) );
        add_action( 'wp_ajax_afi_clear_logs', array( $plugin_admin, 'ajax_clear_logs' ) );
    }

    // Task 1.5: Action Scheduler Integration Setup
    private function define_public_hooks() {
        // This hook is what Action Scheduler will call to process a batch.
        add_action( 'afi_process_batch', array( 'AFI_Processor', 'process_batch' ), 10, 1 );
    }

    public function run() {
        // Plugin is now running
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    /**
     * Fired during plugin activation.
     * Task 1.4: Database Schema Design and Creation (using options instead of custom tables)
     */
    public static function activate() {
        // Set default options
        $defaults = array(
            'post_types' => array('post'),
            'image_source' => 'content',
            'batch_size' => 100,
            'interval' => 5,
        );
        add_option('afi_settings', $defaults);
        add_option('afi_status', array(
            'processed' => 0,
            'total' => 0,
            'running' => false,
            'last_run' => ''
        ));
        add_option('afi_logs', array());
    }

    /**
     * Fired during plugin deactivation.
     */
    public static function deactivate() {
        AFI_Job_Manager::cancel_all_jobs();
    }
}

register_activation_hook( AFI_MAIN_FILE, array( 'AFI_Core', 'activate' ) );
register_deactivation_hook( AFI_MAIN_FILE, array( 'AFI_Core', 'deactivate' ) );