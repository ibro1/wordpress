<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing
 * the admin-specific stylesheet and JavaScript.
 *
 * @package    Auto_Featured_Image
 * @subpackage Auto_Featured_Image/admin
 */
class AFI_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // AJAX hook for the cancel & reset button.
        add_action( 'wp_ajax_afi_cancel_processing', array( $this, 'ajax_cancel_processing' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     * @param string $hook The current admin page.
     */
    public function enqueue_styles($hook) {
        // Only load styles on our plugin pages to avoid conflicts.
        if ( 'toplevel_page_auto-featured-image' !== $hook && 'auto-featured-image_page_auto-featured-image-analyzer' !== $hook ) {
            return;
        }
        wp_enqueue_style($this->plugin_name, AFI_PLUGIN_URL . 'admin/assets/css/afi-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook) {
        // Only load interactive scripts on the main settings page.
        if ('toplevel_page_auto-featured-image' !== $hook) {
            return;
        }
        wp_enqueue_script($this->plugin_name, AFI_PLUGIN_URL . 'admin/assets/js/afi-admin.js', array('jquery'), $this->version, true);

        // Pass initial running state to JS for better UX on page load.
        $status = get_option('afi_status');
        $is_running = AFI_Job_Manager::is_job_pending() || ($status['running'] ?? false);

        wp_localize_script($this->plugin_name, 'afi_ajax_object', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('afi_admin_nonce'),
            'is_running' => $is_running,
        ));
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     */
    public function add_plugin_admin_menu() {
        
        // ======================================================================
        // START: === THE FIX ===
        // We now create a dedicated top-level menu for the plugin.
        // ======================================================================

        // Create the top-level menu item
        add_menu_page(
            'Auto Featured Image',            // Page title
            'Auto Featured Image',            // Menu title
            'manage_options',                 // Capability
            $this->plugin_name,               // Menu slug ('auto-featured-image')
            array($this, 'display_plugin_setup_page'), // Callback for the main page
            'dashicons-format-image',         // Icon
            76                                // Position in menu
        );

        // Add the Dashboard as the first submenu item.
        // We use the same slug as the parent to make it the default page and avoid duplication.
        add_submenu_page(
            $this->plugin_name,               // Parent slug (correctly points to our new top-level menu)
            'Dashboard & Settings',           // Page title
            'Dashboard',                      // Menu title
            'manage_options',                 // Capability
            $this->plugin_name,               // Menu slug (matches parent)
            array($this, 'display_plugin_setup_page') // Callback
        );

        // Add the Post Analyzer as the second submenu item.
        add_submenu_page(
            $this->plugin_name,               // Parent slug (correctly points to our new top-level menu)
            'Post Analyzer',                  // Page title
            'Post Analyzer',                  // Menu title
            'manage_options',                 // Capability
            $this->plugin_name . '-analyzer', // Menu slug
            array($this, 'display_post_analyzer_page') // Callback
        );

        // ======================================================================
        // END: === THE FIX ===
        // ======================================================================
    }

    /**
     * Render the main settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        $options = get_option('afi_settings');
        $status = get_option('afi_status');
        $is_running = AFI_Job_Manager::is_job_pending() || ($status['running'] ?? false);
        ?>
        <div class="wrap afi-wrap">
            <h1><?php _e('Auto Featured Image Dashboard & Settings', 'auto-featured-image'); ?></h1>
            <div class="afi-container">
                <div class="afi-main-content">
                    <h2><?php _e('Processing Status', 'auto-featured-image'); ?></h2>
                    <div id="afi-status-box" class="afi-box">
                        <?php $this->render_status_content($is_running); ?>
                    </div>
                    <h2><?php _e('Controls', 'auto-featured-image'); ?></h2>
                    <div id="afi-controls-box" class="afi-box">
                        <button id="afi-start-btn" class="button button-primary" <?php disabled( $is_running, true ); ?>><?php _e('Start / Resume Processing', 'auto-featured-image'); ?></button>
                        <button id="afi-pause-btn" class="button" <?php disabled( !$is_running, true ); ?>><?php _e('Pause Processing', 'auto-featured-image'); ?></button>
                        <button id="afi-cancel-btn" class="button button-danger" <?php disabled( !$is_running, true ); ?>><?php _e('Cancel & Reset', 'auto-featured-image'); ?></button>
                        <p class="description"><?php _e('Processing runs in the background. You can safely leave this page.', 'auto-featured-image'); ?></p>
                    </div>
                    <h2><?php _e('Processing Log', 'auto-featured-image'); ?></h2>
                    <div id="afi-logs-box" class="afi-box">
                         <button id="afi-clear-logs-btn" class="button right"><?php _e('Clear Log', 'auto-featured-image'); ?></button>
                        <div class="afi-logs-container">
                            <?php $this->render_logs_content(); ?>
                        </div>
                    </div>
                </div>
                <div class="afi-sidebar">
                    <h2><?php _e('Configuration', 'auto-featured-image'); ?></h2>
                    <div class="afi-box">
                        <form method="post" action="options.php">
                            <?php settings_fields('afi_settings_group'); do_settings_sections('afi_settings_group'); ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Post Types to Scan', 'auto-featured-image'); ?></th>
                                    <td>
                                        <?php
                                        $post_types = get_post_types(array('public' => true), 'objects');
                                        $selected_post_types = $options['post_types'] ?? array('post');
                                        foreach ($post_types as $post_type) {
                                            if ($post_type->name === 'attachment') continue;
                                            echo '<label><input type="checkbox" name="afi_settings[post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked(in_array($post_type->name, $selected_post_types), true, false) . '> ' . esc_html($post_type->label) . '</label><br>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Image Source', 'auto-featured-image'); ?></th>
                                    <td>
                                        <select name="afi_settings[image_source]">
                                            <option value="content" <?php selected($options['image_source'] ?? 'content', 'content'); ?>><?php _e('First image from post content', 'auto-featured-image'); ?></option>
                                            <option value="attachment" <?php selected($options['image_source'] ?? 'content', 'attachment'); ?>><?php _e('First attached image', 'auto-featured-image'); ?></option>
                                            <option value="gallery" <?php selected($options['image_source'] ?? 'content', 'gallery'); ?>><?php _e('Most recent from Media Library', 'auto-featured-image'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Batch Size', 'auto-featured-image'); ?></th>
                                    <td><input type="number" name="afi_settings[batch_size]" value="<?php echo esc_attr($options['batch_size'] ?? 100); ?>" min="1" max="1000" /><p class="description"><?php _e('Number of posts to process per batch. Lower this if you experience timeouts.', 'auto-featured-image'); ?></p></td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Processing Interval', 'auto-featured-image'); ?></th>
                                    <td><input type="number" name="afi_settings[interval]" value="<?php echo esc_attr($options['interval'] ?? 5); ?>" min="1" /><p class="description"><?php _e('Minutes to wait between batches. Higher values reduce server load.', 'auto-featured-image'); ?></p></td>
                                </tr>
                            </table>
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Post Analyzer page.
     */
    public function display_post_analyzer_page() {
        ?>
        <div class="wrap afi-analyzer-wrap">
            <h1><?php _e('Post Featured Image Analyzer', 'auto-featured-image'); ?></h1>
            <p><?php _e('Enter a Post ID to see its current featured image status and what action the plugin would take based on your current settings.', 'auto-featured-image'); ?></p>

            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>">
                <label for="post_id_to_check"><strong><?php _e('Post ID:', 'auto-featured-image'); ?></strong></label>
                <input type="number" id="post_id_to_check" name="post_id" min="1" required value="<?php echo isset($_GET['post_id']) ? esc_attr(absint($_GET['post_id'])) : ''; ?>">
                <input type="submit" class="button button-primary" value="<?php _e('Analyze Post', 'auto-featured-image'); ?>">
            </form>

            <?php
            if (isset($_GET['post_id']) && !empty($_GET['post_id'])) {
                $post_id_to_check = absint($_GET['post_id']);
                $analysis_results = $this->analyze_post_thumbnail_status($post_id_to_check);
                $this->render_analysis_results($analysis_results);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Register settings fields.
     */
    public function register_settings() {
        register_setting('afi_settings_group', 'afi_settings', array($this, 'sanitize_settings'));
    }

    /**
     * Sanitize and validate settings input.
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        if (isset($input['post_types'])) {
            $sanitized_input['post_types'] = array_map('sanitize_text_field', $input['post_types']);
        }
        if (isset($input['image_source']) && in_array($input['image_source'], ['content', 'attachment', 'gallery'])) {
            $sanitized_input['image_source'] = sanitize_key($input['image_source']);
        }
        if (isset($input['batch_size'])) {
            $sanitized_input['batch_size'] = absint($input['batch_size']);
        }
        if (isset($input['interval'])) {
            $sanitized_input['interval'] = absint($input['interval']);
        }
        // After saving, clear the cached count of posts to process.
        delete_transient('afi_unprocessed_count');
        return $sanitized_input;
    }

    // --- AJAX Handlers ---

    private function verify_ajax_request() {
        check_ajax_referer('afi_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
    }

    public function ajax_start_processing() {
        $this->verify_ajax_request();
        delete_transient('afi_unprocessed_count');
        $total_to_process = AFI_Processor::count_posts_without_featured_image();
        
        $status = get_option('afi_status');
        $status['total'] = $total_to_process;
        $status['processed'] = 0; // Reset count on new start
        $status['running'] = true;
        update_option('afi_status', $status);

        AFI_Job_Manager::schedule_batch_processing();
        wp_send_json_success(array('message' => 'Processing started.'));
    }

    public function ajax_pause_processing() {
        $this->verify_ajax_request();
        AFI_Job_Manager::cancel_all_jobs();
        
        $status = get_option('afi_status');
        $status['running'] = false;
        update_option('afi_status', $status);
        AFI_Logger::log('System', 'Processing paused by user.');
        wp_send_json_success(array('message' => 'Processing paused.'));
    }

    public function ajax_cancel_processing() {
        $this->verify_ajax_request();
        AFI_Job_Manager::cancel_all_jobs();
        
        $default_status = array('processed' => 0, 'total' => 0, 'running' => false, 'last_run' => '');
        update_option('afi_status', $default_status);
        delete_transient('afi_unprocessed_count');
        AFI_Logger::log('System', 'Processing cancelled and statistics reset by user.');
        wp_send_json_success(array('message' => 'Processing cancelled and reset.'));
    }

    public function ajax_get_status() {
        $this->verify_ajax_request();
        $status_option = get_option('afi_status');
        $is_running = AFI_Job_Manager::is_job_pending() || ($status_option['running'] ?? false);
        
        ob_start();
        $this->render_status_content($is_running);
        $status_html = ob_get_clean();

        ob_start();
        $this->render_logs_content();
        $logs_html = ob_get_clean();
        
        wp_send_json_success(array('status_html' => $status_html, 'logs_html' => $logs_html));
    }
    
    public function ajax_clear_logs() {
        $this->verify_ajax_request();
        AFI_Logger::clear_logs();
        wp_send_json_success();
    }
    
    // --- Render and Analysis Helper Functions ---
    
    private function render_status_content($is_running = null) {
        if ($is_running === null) {
            $status_option = get_option('afi_status');
            $is_running = AFI_Job_Manager::is_job_pending() || ($status_option['running'] ?? false);
        }
        $status = get_option('afi_status');
        $total = $status['total'] ?? 0;
        if ($total === 0 && !$is_running) {
            $total = AFI_Processor::count_posts_without_featured_image();
        }
        $processed = $status['processed'] ?? 0;
        $percent = ($total > 0) ? round(($processed / $total) * 100) : 0;
        $status_text = $is_running ? 'Running' : 'Paused / Idle';
        $status_class = $is_running ? 'running' : 'paused';
        ?>
        <div class="afi-status-grid">
            <div><strong><?php _e('Status:', 'auto-featured-image'); ?></strong></div>
            <div><span class="afi-status-indicator <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></div>
            <div><strong><?php _e('Posts Processed:', 'auto-featured-image'); ?></strong></div>
            <div><?php echo esc_html($processed); ?> / <?php echo esc_html($total); ?></div>
            <div><strong><?php _e('Last Activity:', 'auto-featured-image'); ?></strong></div>
            <div><?php echo !empty($status['last_run']) ? esc_html($status['last_run']) : 'N/A'; ?></div>
        </div>
        <div class="afi-progress-bar">
            <div class="afi-progress" style="width: <?php echo esc_attr($percent); ?>%;"></div>
            <span><?php echo esc_html($percent); ?>%</span>
        </div>
        <?php
    }

    private function render_logs_content() {
        $logs = AFI_Logger::get_logs();
        if (empty($logs)) {
            echo '<p>' . __('No log entries yet.', 'auto-featured-image') . '</p>';
            return;
        }
        echo '<table>';
        foreach ($logs as $log) {
            $type_class = strtolower(esc_attr($log['type']));
            echo '<tr>';
            echo '<td class="log-time">' . esc_html($log['timestamp']) . '</td>';
            echo '<td class="log-type"><span class="log-label ' . $type_class . '">' . esc_html($log['type']) . '</span></td>';
            echo '<td class="log-message">' . esc_html($log['message']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    private function analyze_post_thumbnail_status($post_id) {
        $post = get_post($post_id);
        $results = [
            'post_id'                       => $post_id,
            'post_exists'                   => (bool) $post,
            'post_title'                    => '',
            'post_link'                     => '',
            'current_thumbnail_id'          => null,
            'current_thumbnail_status_code' => 'info',
            'current_thumbnail_status_text' => '',
            'current_thumbnail_url'         => null,
            'current_thumbnail_preview'     => null,
            'suggested_action'              => __('No Action Needed', 'auto-featured-image'),
            'suggested_reasoning'           => '',
            'suggested_thumbnail_id'        => null,
            'suggested_thumbnail_preview'   => null,
        ];

        if (!$results['post_exists']) { return $results; }

        $results['post_title'] = $post->post_title;
        $results['post_link'] = get_edit_post_link($post_id);
        $needs_fixing = false;

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            $results['current_thumbnail_status_code'] = 'error';
            $results['current_thumbnail_status_text'] = __('Not Set', 'auto-featured-image');
            $needs_fixing = true;
        } else {
            $results['current_thumbnail_id'] = $thumbnail_id;
            $attachment = get_post($thumbnail_id);
            if ($attachment && 'attachment' === $attachment->post_type) {
                $results['current_thumbnail_status_code'] = 'success';
                $results['current_thumbnail_status_text'] = __('Valid', 'auto-featured-image');
                $results['current_thumbnail_url'] = wp_get_attachment_url($thumbnail_id);
                $results['current_thumbnail_preview'] = wp_get_attachment_image($thumbnail_id, 'thumbnail');
            } else {
                $results['current_thumbnail_status_code'] = 'error';
                $results['current_thumbnail_status_text'] = __('Orphaned / Corrupted', 'auto-featured-image');
                $needs_fixing = true;
            }
        }
        
        if ($needs_fixing) {
            $results['suggested_action'] = __('Assign New Image', 'auto-featured-image');
            $settings = get_option('afi_settings');
            $image_source_method = $settings['image_source'] ?? 'content';

            switch ($image_source_method) {
                case 'attachment': $reason = 'First attached image'; break;
                case 'gallery': $reason = 'Most recent from Media Library'; break;
                default: $reason = 'First image from post content';
            }
            $results['suggested_reasoning'] = sprintf(__('The plugin would search for the "%s" based on your settings.', 'auto-featured-image'), $reason);

            $suggested_id = AFI_Image_Selector::find_image_for_post($post_id, $image_source_method);

            if ($suggested_id) {
                $results['suggested_thumbnail_id'] = $suggested_id;
                $results['suggested_thumbnail_preview'] = wp_get_attachment_image($suggested_id, 'thumbnail');
            } else {
                $results['suggested_action'] = __('Attempt to Assign, but No Image Found', 'auto-featured-image');
            }
        } else {
            $results['suggested_reasoning'] = __('The post already has a valid featured image.', 'auto-featured-image');
        }

        return $results;
    }

    private function render_analysis_results($results) {
        if (!$results['post_exists']) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('Error: Post with ID %d could not be found.', 'auto-featured-image'), $results['post_id']) . '</p></div>';
            return;
        }
        ?>
        <hr>
        <h2><?php printf(__('Analysis for Post #%d: %s', 'auto-featured-image'), $results['post_id'], esc_html($results['post_title'])); ?></h2>
        <a href="<?php echo esc_url($results['post_link']); ?>" target="_blank" class="button button-secondary"><?php _e('View/Edit this post', 'auto-featured-image'); ?></a>

        <div class="afi-analysis-container">
            <div class="afi-box">
                <h3><?php _e('Current Status', 'auto-featured-image'); ?></h3>
                <div class="analysis-grid">
                    <div><strong><?php _e('Status:', 'auto-featured-image'); ?></strong></div>
                    <div><span class="log-label <?php echo esc_attr(strtolower($results['current_thumbnail_status_code'])); ?>"><?php echo esc_html($results['current_thumbnail_status_text']); ?></span></div>
                    <div><strong><?php _e('Image ID:', 'auto-featured-image'); ?></strong></div>
                    <div><?php echo $results['current_thumbnail_id'] ? esc_html($results['current_thumbnail_id']) : 'N/A'; ?></div>
                    <div><strong><?php _e('Image URL:', 'auto-featured-image'); ?></strong></div>
                    <div class="analysis-url"><?php echo $results['current_thumbnail_url'] ? '<a href="' . esc_url($results['current_thumbnail_url']) . '" target="_blank">' . esc_html($results['current_thumbnail_url']) . '</a>' : 'N/A'; ?></div>
                    <div><strong><?php _e('Preview:', 'auto-featured-image'); ?></strong></div>
                    <div><?php echo $results['current_thumbnail_preview'] ? $results['current_thumbnail_preview'] : 'N/A'; ?></div>
                </div>
            </div>
            <div class="afi-box">
                <h3><?php _e('Suggested Action (based on current settings)', 'auto-featured-image'); ?></h3>
                <div class="analysis-grid">
                     <div><strong><?php _e('Action:', 'auto-featured-image'); ?></strong></div>
                    <div><strong><?php echo esc_html($results['suggested_action']); ?></strong></div>
                    <div><strong><?php _e('Reasoning:', 'auto-featured-image'); ?></strong></div>
                    <div><?php echo esc_html($results['suggested_reasoning']); ?></div>
                    <div><strong><?php _e('Image to Assign:', 'auto-featured-image'); ?></strong></div>
                    <div><?php echo $results['suggested_thumbnail_id'] ? esc_html($results['suggested_thumbnail_id']) : 'N/A'; ?></div>
                    <div><strong><?php _e('Preview:', 'auto-featured-image'); ?></strong></div>
                    <div><?php echo $results['suggested_thumbnail_preview'] ? $results['suggested_thumbnail_preview'] : 'N/A'; ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}