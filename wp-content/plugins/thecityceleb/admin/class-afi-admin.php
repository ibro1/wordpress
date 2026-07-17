<?php
/**
 * Admin controller class for Auto Featured Image
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin controller class that handles admin interface
 */
class AFI_Admin {
    
    /**
     * Plugin name
     *
     * @var string
     */
    private $plugin_name;
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * Initialize the admin class
     *
     * @param string $plugin_name The name of the plugin
     * @param string $version The version of the plugin
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add submenu page under Tools
        add_management_page(
            __('Auto Featured Image', 'auto-featured-image'),
            __('Auto Featured Image', 'auto-featured-image'),
            'manage_options',
            'auto-featured-image',
            array($this, 'display_admin_page')
        );
    }
    
    /**
     * Display the admin page
     */
    public function display_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'auto-featured-image'));
        }
        
        // Handle form submissions
        $this->handle_form_submissions();
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('afi_messages'); ?>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('tools.php?page=auto-featured-image&tab=dashboard')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Dashboard', 'auto-featured-image'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('tools.php?page=auto-featured-image&tab=new-job')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'new-job' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('New Job', 'auto-featured-image'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('tools.php?page=auto-featured-image&tab=history')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'history' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Job History', 'auto-featured-image'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('tools.php?page=auto-featured-image&tab=logs')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Logs', 'auto-featured-image'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('tools.php?page=auto-featured-image&tab=data-management')); ?>" 
                   class="nav-tab <?php echo $current_tab === 'data-management' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Data Management', 'auto-featured-image'); ?>
                </a>
            </nav>
            
            <!-- Tab Content -->
            <div class="afi-tab-content">
                <?php
                switch ($current_tab) {
                    case 'new-job':
                        $this->display_new_job_tab();
                        break;
                    case 'history':
                        $this->display_history_tab();
                        break;
                    case 'logs':
                        $this->display_logs_tab();
                        break;
                    case 'data-management':
                        $this->display_data_management_tab();
                        break;
                    case 'dashboard':
                    default:
                        $this->display_dashboard_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display dashboard tab
     */
    private function display_dashboard_tab() {
        ?>
        <div class="afi-dashboard">
            <div class="afi-welcome-panel">
                <h2><?php _e('Welcome to Auto Featured Image', 'auto-featured-image'); ?></h2>
                <p><?php _e('This plugin helps you automatically assign featured images to posts that don\'t have them. Use the tabs above to create new jobs or view job history.', 'auto-featured-image'); ?></p>
            </div>
            
            <div class="afi-stats-grid">
                <div class="afi-stat-card">
                    <h3><?php _e('Posts Without Featured Images', 'auto-featured-image'); ?></h3>
                    <div class="afi-stat-number" id="afi-posts-without-images">
                        <span class="spinner is-active"></span>
                    </div>
                </div>
                
                <div class="afi-stat-card">
                    <h3><?php _e('Available Images', 'auto-featured-image'); ?></h3>
                    <div class="afi-stat-number" id="afi-available-images">
                        <span class="spinner is-active"></span>
                    </div>
                </div>
                
                <div class="afi-stat-card">
                    <h3><?php _e('Active Jobs', 'auto-featured-image'); ?></h3>
                    <div class="afi-stat-number" id="afi-active-jobs">
                        <span class="spinner is-active"></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display new job tab
     */
    private function display_new_job_tab() {
        // Get available post types
        $post_types = get_post_types(array('public' => true), 'objects');
        unset($post_types['attachment']); // Remove attachments
        
        // Get previously submitted values for form persistence
        $submitted_post_types = isset($_POST['afi_post_types']) ? $_POST['afi_post_types'] : array('post', 'page');
        $submitted_image_selection = isset($_POST['afi_image_selection']) ? $_POST['afi_image_selection'] : 'all';
        $submitted_date_start = isset($_POST['afi_date_start']) ? $_POST['afi_date_start'] : '';
        $submitted_date_end = isset($_POST['afi_date_end']) ? $_POST['afi_date_end'] : '';
        $submitted_keyword = isset($_POST['afi_keyword']) ? $_POST['afi_keyword'] : '';
        
        ?>
        <div class="afi-new-job">
            <div class="afi-form-header">
                <h2><?php _e('Create New Job', 'auto-featured-image'); ?></h2>
                <p><?php _e('Configure a new job to automatically assign featured images to posts that don\'t have them.', 'auto-featured-image'); ?></p>
            </div>
            
            <form method="post" action="" id="afi-new-job-form" novalidate>
                <?php wp_nonce_field('afi_create_job', 'afi_create_job_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="afi_post_types"><?php _e('Post Types', 'auto-featured-image'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <fieldset id="afi_post_types">
                                <legend class="screen-reader-text"><?php _e('Select post types to process', 'auto-featured-image'); ?></legend>
                                <?php foreach ($post_types as $post_type): ?>
                                    <label>
                                        <input type="checkbox" name="afi_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" 
                                               <?php checked(in_array($post_type->name, $submitted_post_types)); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                        <span class="afi-post-type-count" data-post-type="<?php echo esc_attr($post_type->name); ?>">
                                            (<span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>)
                                        </span>
                                    </label><br>
                                <?php endforeach; ?>
                                <p class="description"><?php _e('Select which post types to process. Numbers in parentheses show posts without featured images.', 'auto-featured-image'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="afi_image_selection"><?php _e('Image Selection', 'auto-featured-image'); ?></label>
                        </th>
                        <td>
                            <fieldset id="afi_image_selection">
                                <legend class="screen-reader-text"><?php _e('Choose image selection method', 'auto-featured-image'); ?></legend>
                                <label>
                                    <input type="radio" name="afi_image_selection" value="all" <?php checked($submitted_image_selection, 'all'); ?>>
                                    <?php _e('Use all available images', 'auto-featured-image'); ?>
                                    <span class="afi-image-count" id="afi-all-images-count">
                                        (<span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>)
                                    </span>
                                </label><br>
                                <label>
                                    <input type="radio" name="afi_image_selection" value="filtered" <?php checked($submitted_image_selection, 'filtered'); ?>>
                                    <?php _e('Use filtered images', 'auto-featured-image'); ?>
                                    <span class="afi-image-count" id="afi-filtered-images-count" style="display: none;">
                                        (<span class="count">0</span> <?php _e('images match filters', 'auto-featured-image'); ?>)
                                    </span>
                                </label>
                                <p class="description"><?php _e('Choose whether to use all images or apply filters. Numbers in parentheses show available images.', 'auto-featured-image'); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr class="afi-filter-options" <?php echo $submitted_image_selection === 'filtered' ? '' : 'style="display: none;"'; ?>>
                        <th scope="row">
                            <label for="afi_date_start"><?php _e('Date Range', 'auto-featured-image'); ?></label>
                        </th>
                        <td>
                            <div class="afi-date-range-inputs">
                                <input type="date" name="afi_date_start" id="afi_date_start" value="<?php echo esc_attr($submitted_date_start); ?>" 
                                       aria-describedby="afi-date-range-desc">
                                <span class="afi-date-separator"><?php _e('to', 'auto-featured-image'); ?></span>
                                <input type="date" name="afi_date_end" id="afi_date_end" value="<?php echo esc_attr($submitted_date_end); ?>" 
                                       aria-describedby="afi-date-range-desc">
                            </div>
                            <p class="description" id="afi-date-range-desc"><?php _e('Optional: Filter images by upload date range. Leave empty to include all dates.', 'auto-featured-image'); ?></p>
                        </td>
                    </tr>
                    
                    <tr class="afi-filter-options" <?php echo $submitted_image_selection === 'filtered' ? '' : 'style="display: none;"'; ?>>
                        <th scope="row">
                            <label for="afi_keyword"><?php _e('Keyword Filter', 'auto-featured-image'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="afi_keyword" id="afi_keyword" class="regular-text" 
                                   value="<?php echo esc_attr($submitted_keyword); ?>" 
                                   placeholder="<?php esc_attr_e('Enter keyword to search...', 'auto-featured-image'); ?>"
                                   aria-describedby="afi-keyword-desc"
                                   minlength="2" maxlength="100">
                            <p class="description" id="afi-keyword-desc"><?php _e('Optional: Filter images by filename, title, or alt text. Must be 2-100 characters.', 'auto-featured-image'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="afi-job-preview" id="afi-job-preview" style="display: none;">
                    <h3><?php _e('Job Preview', 'auto-featured-image'); ?></h3>
                    <div class="afi-preview-content">
                        <p><strong><?php _e('Posts to process:', 'auto-featured-image'); ?></strong> <span id="afi-preview-posts">0</span></p>
                        <p><strong><?php _e('Available images:', 'auto-featured-image'); ?></strong> <span id="afi-preview-images">0</span></p>
                        <p><strong><?php _e('Estimated time:', 'auto-featured-image'); ?></strong> <span id="afi-preview-time"><?php _e('Calculating...', 'auto-featured-image'); ?></span></p>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="afi_create_job" id="afi-create-job-btn" class="button-primary" 
                           value="<?php esc_attr_e('Create Job', 'auto-featured-image'); ?>" disabled>
                    <span class="spinner" id="afi-create-job-spinner"></span>
                    <span class="afi-form-status" id="afi-form-status"></span>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Display history tab
     */
    private function display_history_tab() {
        ?>
        <div class="afi-history">
            <div class="afi-history-header">
                <h2><?php _e('Job History', 'auto-featured-image'); ?></h2>
                <p><?php _e('View and manage your previous jobs. Click "View" to see detailed progress and results.', 'auto-featured-image'); ?></p>
                
                <div class="afi-history-controls">
                    <div class="afi-history-search">
                        <input type="text" id="afi-history-search" class="regular-text" 
                               placeholder="<?php esc_attr_e('Search jobs...', 'auto-featured-image'); ?>" 
                               aria-label="<?php esc_attr_e('Search job history', 'auto-featured-image'); ?>">
                        <button type="button" id="afi-search-jobs" class="button">
                            <?php _e('Search', 'auto-featured-image'); ?>
                        </button>
                        <button type="button" id="afi-clear-search" class="button" style="display: none;">
                            <?php _e('Clear', 'auto-featured-image'); ?>
                        </button>
                    </div>
                    
                    <div class="afi-history-filters">
                        <select id="afi-status-filter" aria-label="<?php esc_attr_e('Filter by status', 'auto-featured-image'); ?>">
                            <option value=""><?php _e('All Statuses', 'auto-featured-image'); ?></option>
                            <option value="pending"><?php _e('Pending', 'auto-featured-image'); ?></option>
                            <option value="scanning"><?php _e('Scanning', 'auto-featured-image'); ?></option>
                            <option value="running"><?php _e('Running', 'auto-featured-image'); ?></option>
                            <option value="paused"><?php _e('Paused', 'auto-featured-image'); ?></option>
                            <option value="complete"><?php _e('Complete', 'auto-featured-image'); ?></option>
                            <option value="canceled"><?php _e('Canceled', 'auto-featured-image'); ?></option>
                            <option value="failed"><?php _e('Failed', 'auto-featured-image'); ?></option>
                        </select>
                        
                        <button type="button" id="afi-refresh-history" class="button">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <?php _e('Refresh', 'auto-featured-image'); ?>
                        </button>
                        
                        <button type="button" id="afi-cleanup-jobs" class="button button-secondary">
                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                            <?php _e('Cleanup Old Jobs', 'auto-featured-image'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="afi-history-stats" id="afi-history-stats" style="display: none;">
                <div class="afi-stats-grid">
                    <div class="afi-stat-card">
                        <h3><?php _e('Total Jobs', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number" id="afi-total-jobs">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Active Jobs', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number" id="afi-active-jobs-count">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Completed Jobs', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number" id="afi-completed-jobs">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Total Posts Processed', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number" id="afi-total-processed">0</div>
                    </div>
                </div>
            </div>
            
            <div class="afi-history-table-container">
                <div class="afi-table-header">
                    <div class="afi-table-info">
                        <span id="afi-showing-results"><?php _e('Loading...', 'auto-featured-image'); ?></span>
                    </div>
                    <div class="afi-table-pagination" id="afi-table-pagination" style="display: none;">
                        <button type="button" id="afi-prev-page" class="button" disabled>
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                            <?php _e('Previous', 'auto-featured-image'); ?>
                        </button>
                        <span id="afi-page-info">Page 1 of 1</span>
                        <button type="button" id="afi-next-page" class="button" disabled>
                            <?php _e('Next', 'auto-featured-image'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped" id="afi-history-table">
                    <thead>
                        <tr>
                            <th class="column-id sortable" data-sort="id">
                                <a href="#" class="afi-sort-link">
                                    <span><?php _e('ID', 'auto-featured-image'); ?></span>
                                    <span class="sorting-indicators">
                                        <span class="sorting-indicator asc" aria-hidden="true"></span>
                                        <span class="sorting-indicator desc" aria-hidden="true"></span>
                                    </span>
                                </a>
                            </th>
                            <th class="column-status sortable" data-sort="status">
                                <a href="#" class="afi-sort-link">
                                    <span><?php _e('Status', 'auto-featured-image'); ?></span>
                                    <span class="sorting-indicators">
                                        <span class="sorting-indicator asc" aria-hidden="true"></span>
                                        <span class="sorting-indicator desc" aria-hidden="true"></span>
                                    </span>
                                </a>
                            </th>
                            <th class="column-post-types"><?php _e('Post Types', 'auto-featured-image'); ?></th>
                            <th class="column-progress"><?php _e('Progress', 'auto-featured-image'); ?></th>
                            <th class="column-created sortable desc" data-sort="created_at">
                                <a href="#" class="afi-sort-link">
                                    <span><?php _e('Created', 'auto-featured-image'); ?></span>
                                    <span class="sorting-indicators">
                                        <span class="sorting-indicator asc" aria-hidden="true"></span>
                                        <span class="sorting-indicator desc" aria-hidden="true"></span>
                                    </span>
                                </a>
                            </th>
                            <th class="column-duration"><?php _e('Duration', 'auto-featured-image'); ?></th>
                            <th class="column-actions"><?php _e('Actions', 'auto-featured-image'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="afi-history-table-body">
                        <tr>
                            <td colspan="7" class="afi-loading">
                                <span class="spinner is-active"></span>
                                <?php _e('Loading job history...', 'auto-featured-image'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Job Details Modal -->
        <div id="afi-job-modal" class="afi-modal" style="display: none;" role="dialog" aria-labelledby="afi-modal-title" aria-hidden="true">
            <div class="afi-modal-overlay"></div>
            <div class="afi-modal-dialog">
                <div class="afi-modal-header">
                    <h2 id="afi-modal-title"><?php _e('Job Details', 'auto-featured-image'); ?></h2>
                    <button type="button" class="afi-close-modal" aria-label="<?php esc_attr_e('Close modal', 'auto-featured-image'); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    </button>
                </div>
                
                <div class="afi-modal-content">
                    <div class="afi-modal-tabs">
                        <button type="button" class="afi-tab-button active" data-tab="overview">
                            <?php _e('Overview', 'auto-featured-image'); ?>
                        </button>
                        <button type="button" class="afi-tab-button" data-tab="progress">
                            <?php _e('Progress', 'auto-featured-image'); ?>
                        </button>
                        <button type="button" class="afi-tab-button" data-tab="results">
                            <?php _e('Results', 'auto-featured-image'); ?>
                        </button>
                        <button type="button" class="afi-tab-button" data-tab="logs">
                            <?php _e('Logs', 'auto-featured-image'); ?>
                        </button>
                    </div>
                    
                    <div class="afi-tab-content">
                        <!-- Overview Tab -->
                        <div id="afi-tab-overview" class="afi-tab-panel active">
                            <div class="afi-job-overview">
                                <div class="afi-job-info">
                                    <h3 id="afi-job-title"><?php _e('Job #', 'auto-featured-image'); ?><span id="afi-job-id">-</span></h3>
                                    <div class="afi-job-meta">
                                        <p><strong><?php _e('Status:', 'auto-featured-image'); ?></strong> <span id="afi-job-status-text" class="afi-status-badge">-</span></p>
                                        <p><strong><?php _e('Post Types:', 'auto-featured-image'); ?></strong> <span id="afi-job-post-types">-</span></p>
                                        <p><strong><?php _e('Image Filters:', 'auto-featured-image'); ?></strong> <span id="afi-job-filters">-</span></p>
                                        <p><strong><?php _e('Created:', 'auto-featured-image'); ?></strong> <span id="afi-job-created">-</span></p>
                                        <p><strong><?php _e('Duration:', 'auto-featured-image'); ?></strong> <span id="afi-job-duration">-</span></p>
                                    </div>
                                </div>
                                
                                <div class="afi-job-controls">
                                    <div id="afi-job-control-buttons">
                                        <!-- Control buttons will be populated dynamically -->
                                    </div>
                                </div>
                            </div>
                            
                            <div class="afi-job-summary">
                                <h4><?php _e('Summary', 'auto-featured-image'); ?></h4>
                                <div class="afi-summary-stats">
                                    <div class="afi-summary-item">
                                        <span class="afi-summary-label"><?php _e('Total Items:', 'auto-featured-image'); ?></span>
                                        <span class="afi-summary-value" id="afi-summary-total">0</span>
                                    </div>
                                    <div class="afi-summary-item">
                                        <span class="afi-summary-label"><?php _e('Processed:', 'auto-featured-image'); ?></span>
                                        <span class="afi-summary-value" id="afi-summary-processed">0</span>
                                    </div>
                                    <div class="afi-summary-item">
                                        <span class="afi-summary-label"><?php _e('Successful:', 'auto-featured-image'); ?></span>
                                        <span class="afi-summary-value" id="afi-summary-success">0</span>
                                    </div>
                                    <div class="afi-summary-item">
                                        <span class="afi-summary-label"><?php _e('Failed:', 'auto-featured-image'); ?></span>
                                        <span class="afi-summary-value" id="afi-summary-failed">0</span>
                                    </div>
                                    <div class="afi-summary-item">
                                        <span class="afi-summary-label"><?php _e('Processing Rate:', 'auto-featured-image'); ?></span>
                                        <span class="afi-summary-value" id="afi-summary-rate">0/min</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Tab -->
                        <div id="afi-tab-progress" class="afi-tab-panel">
                            <div class="afi-progress-section">
                                <div class="afi-progress-stats">
                                    <div class="afi-stat">
                                        <div class="afi-stat-number" id="afi-progress-total">0</div>
                                        <div class="afi-stat-label"><?php _e('Total Items', 'auto-featured-image'); ?></div>
                                    </div>
                                    <div class="afi-stat">
                                        <div class="afi-stat-number" id="afi-progress-processed">0</div>
                                        <div class="afi-stat-label"><?php _e('Processed', 'auto-featured-image'); ?></div>
                                    </div>
                                    <div class="afi-stat">
                                        <div class="afi-stat-number" id="afi-progress-percentage">0%</div>
                                        <div class="afi-stat-label"><?php _e('Complete', 'auto-featured-image'); ?></div>
                                    </div>
                                    <div class="afi-stat">
                                        <div class="afi-stat-number" id="afi-progress-rate">0/min</div>
                                        <div class="afi-stat-label"><?php _e('Rate', 'auto-featured-image'); ?></div>
                                    </div>
                                    <div class="afi-stat">
                                        <div class="afi-stat-number" id="afi-progress-eta">-</div>
                                        <div class="afi-stat-label"><?php _e('ETA', 'auto-featured-image'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="afi-progress-bar-large">
                                    <div class="afi-progress-fill" id="afi-progress-fill" style="width: 0%"></div>
                                    <div class="afi-progress-text" id="afi-progress-text">0 / 0</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Results Tab -->
                        <div id="afi-tab-results" class="afi-tab-panel">
                            <div class="afi-results-container">
                                <div class="afi-results-header">
                                    <h3><?php _e('Processing Results', 'auto-featured-image'); ?></h3>
                                    <div class="afi-results-search">
                                        <input type="text" id="afi-results-search" class="afi-search-input" 
                                               placeholder="<?php esc_attr_e('Search posts...', 'auto-featured-image'); ?>"
                                               aria-label="<?php esc_attr_e('Search results', 'auto-featured-image'); ?>">
                                        <button type="button" id="afi-search-results" class="button">
                                            <?php _e('Search', 'auto-featured-image'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="afi-results-content">
                                    <div class="afi-results-loading">
                                        <span class="spinner is-active"></span>
                                        <p><?php _e('Loading results...', 'auto-featured-image'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Logs Tab -->
                        <div id="afi-tab-logs" class="afi-tab-panel">
                            <div class="afi-logs-container">
                                <div class="afi-logs-header">
                                    <h3><?php _e('Processing Logs', 'auto-featured-image'); ?></h3>
                                    <div class="afi-logs-controls">
                                        <button type="button" id="afi-refresh-logs" class="button button-small">
                                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                            <?php _e('Refresh', 'auto-featured-image'); ?>
                                        </button>
                                        <button type="button" id="afi-clear-logs" class="button button-small">
                                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                            <?php _e('Clear', 'auto-featured-image'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="afi-logs-content">
                                    <div class="afi-logs-loading">
                                        <span class="spinner is-active"></span>
                                        <p><?php _e('Loading logs...', 'auto-featured-image'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle form submissions
     */
    private function handle_form_submissions() {
        // Handle job creation
        if (isset($_POST['afi_create_job']) && wp_verify_nonce($_POST['afi_create_job_nonce'], 'afi_create_job')) {
            $this->handle_job_creation();
        }
    }
    
    /**
     * Handle job creation form submission
     */
    private function handle_job_creation() {
        // Validate post types
        $post_types = isset($_POST['afi_post_types']) ? array_map('sanitize_text_field', $_POST['afi_post_types']) : array();
        
        if (empty($post_types)) {
            add_settings_error('afi_messages', 'afi_message', __('Please select at least one post type.', 'auto-featured-image'), 'error');
            return;
        }
        
        // Validate that selected post types exist
        foreach ($post_types as $post_type) {
            if (!post_type_exists($post_type)) {
                add_settings_error('afi_messages', 'afi_message', sprintf(__('Invalid post type: %s', 'auto-featured-image'), $post_type), 'error');
                return;
            }
        }
        
        // Validate image selection
        $image_selection = sanitize_text_field($_POST['afi_image_selection']);
        $image_filters = array();
        
        if ($image_selection === 'filtered') {
            // Handle date range
            $date_start = sanitize_text_field($_POST['afi_date_start']);
            $date_end = sanitize_text_field($_POST['afi_date_end']);
            
            // Validate date format and range
            if (!empty($date_start) && !$this->validate_date($date_start)) {
                add_settings_error('afi_messages', 'afi_message', __('Invalid start date format.', 'auto-featured-image'), 'error');
                return;
            }
            
            if (!empty($date_end) && !$this->validate_date($date_end)) {
                add_settings_error('afi_messages', 'afi_message', __('Invalid end date format.', 'auto-featured-image'), 'error');
                return;
            }
            
            if (!empty($date_start) && !empty($date_end) && $date_start > $date_end) {
                add_settings_error('afi_messages', 'afi_message', __('End date must be after start date.', 'auto-featured-image'), 'error');
                return;
            }
            
            if (!empty($date_start) || !empty($date_end)) {
                $image_filters['date_range'] = array(
                    'start' => $date_start,
                    'end' => $date_end
                );
            }
            
            // Handle keyword filter
            $keyword = sanitize_text_field($_POST['afi_keyword']);
            if (!empty($keyword)) {
                // Validate keyword length
                if (strlen($keyword) < 2) {
                    add_settings_error('afi_messages', 'afi_message', __('Keyword must be at least 2 characters long.', 'auto-featured-image'), 'error');
                    return;
                }
                
                if (strlen($keyword) > 100) {
                    add_settings_error('afi_messages', 'afi_message', __('Keyword must be less than 100 characters.', 'auto-featured-image'), 'error');
                    return;
                }
                
                $image_filters['keyword'] = $keyword;
            }
            
            // If filtered is selected but no filters are provided, show error
            if (empty($image_filters)) {
                add_settings_error('afi_messages', 'afi_message', __('Please specify at least one filter when using filtered image selection.', 'auto-featured-image'), 'error');
                return;
            }
        }
        
        // Create the job using the job manager
        try {
            // Load job manager
            require_once AFI_PLUGIN_DIR . 'includes/class-afi-job-manager.php';
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: About to create job manager');
            }
            
            $job_manager = new AFI_Job_Manager();
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: Job manager created, about to create scan job with post_types: ' . json_encode($post_types) . ', filters: ' . json_encode($image_filters));
            }
            
            // Create the scan job
            $job_id = $job_manager->create_scan_job($post_types, $image_filters);
            
            // Debug logging
            if (function_exists('error_log')) {
                error_log('AFI Debug: create_scan_job returned: ' . ($job_id ? $job_id : 'false'));
            }
            
            if ($job_id) {
                add_settings_error('afi_messages', 'afi_message', sprintf(
                    __('Job #%d created successfully! The scanning process has started in the background. You can monitor progress in the Job History tab.', 'auto-featured-image'),
                    $job_id
                ), 'updated');
                
                // Debug logging
                if (function_exists('error_log')) {
                    error_log('AFI Debug: Job created successfully with ID: ' . $job_id);
                }
            } else {
                add_settings_error('afi_messages', 'afi_message', __('Failed to create job. Please try again.', 'auto-featured-image'), 'error');
            }
        } catch (Exception $e) {
            add_settings_error('afi_messages', 'afi_message', sprintf(
                __('Error creating job: %s', 'auto-featured-image'),
                $e->getMessage()
            ), 'error');
        }
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date Date string to validate
     * @return bool True if valid, false otherwise
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        // Only enqueue on our admin page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'tools_page_auto-featured-image') {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            AFI_PLUGIN_URL . 'assets/css/afi-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Enqueue logs styles
        wp_enqueue_style(
            $this->plugin_name . '-logs',
            AFI_PLUGIN_URL . 'assets/css/afi-logs.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on our admin page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'tools_page_auto-featured-image') {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            AFI_PLUGIN_URL . 'assets/js/afi-admin.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // Enqueue logs script
        wp_enqueue_script(
            $this->plugin_name . '-logs',
            AFI_PLUGIN_URL . 'assets/js/afi-logs.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name . '-admin',
            'afi_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('afi_ajax_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'auto-featured-image'),
                    'error' => __('An error occurred. Please try again.', 'auto-featured-image'),
                    'confirm_cancel' => __('Are you sure you want to cancel this job?', 'auto-featured-image'),
                    'confirm_delete' => __('Are you sure you want to delete this job?', 'auto-featured-image'),
                    'noLogsFound' => __('No logs found.', 'auto-featured-image')
                )
            )
        );
    }

    /**
     * Display logs tab
     */
    private function display_logs_tab() {
        ?>
        <div class="afi-logs">
            <div class="afi-logs-header">
                <h2><?php _e('System Logs', 'auto-featured-image'); ?></h2>
                <p><?php _e('View detailed logs of all plugin activities including processing steps, errors, and system events.', 'auto-featured-image'); ?></p>
                
                <div class="afi-logs-controls">
                    <div class="afi-logs-filters">
                        <select id="afi-log-level-filter" aria-label="<?php esc_attr_e('Filter by log level', 'auto-featured-image'); ?>">
                            <option value=""><?php _e('All Levels', 'auto-featured-image'); ?></option>
                            <option value="debug"><?php _e('Debug', 'auto-featured-image'); ?></option>
                            <option value="info"><?php _e('Info', 'auto-featured-image'); ?></option>
                            <option value="warning"><?php _e('Warning', 'auto-featured-image'); ?></option>
                            <option value="error"><?php _e('Error', 'auto-featured-image'); ?></option>
                            <option value="critical"><?php _e('Critical', 'auto-featured-image'); ?></option>
                        </select>
                        
                        <input type="number" id="afi-log-job-filter" class="small-text" 
                               placeholder="<?php esc_attr_e('Job ID', 'auto-featured-image'); ?>" 
                               aria-label="<?php esc_attr_e('Filter by job ID', 'auto-featured-image'); ?>">
                        
                        <input type="date" id="afi-log-date-from" 
                               aria-label="<?php esc_attr_e('Filter from date', 'auto-featured-image'); ?>">
                        
                        <input type="date" id="afi-log-date-to" 
                               aria-label="<?php esc_attr_e('Filter to date', 'auto-featured-image'); ?>">
                        
                        <input type="text" id="afi-log-search" class="regular-text" 
                               placeholder="<?php esc_attr_e('Search log messages...', 'auto-featured-image'); ?>" 
                               aria-label="<?php esc_attr_e('Search logs', 'auto-featured-image'); ?>">
                        
                        <button type="button" id="afi-filter-logs" class="button">
                            <?php _e('Filter', 'auto-featured-image'); ?>
                        </button>
                        
                        <button type="button" id="afi-clear-log-filters" class="button">
                            <?php _e('Clear', 'auto-featured-image'); ?>
                        </button>
                    </div>
                    
                    <div class="afi-logs-actions">
                        <button type="button" id="afi-refresh-logs" class="button">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <?php _e('Refresh', 'auto-featured-image'); ?>
                        </button>
                        
                        <button type="button" id="afi-export-logs" class="button">
                            <span class="dashicons dashicons-download" aria-hidden="true"></span>
                            <?php _e('Export CSV', 'auto-featured-image'); ?>
                        </button>
                        
                        <button type="button" id="afi-clear-logs" class="button button-secondary">
                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                            <?php _e('Clear Logs', 'auto-featured-image'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="afi-logs-stats" id="afi-logs-stats" style="display: none;">
                <div class="afi-stats-grid">
                    <div class="afi-stat-card">
                        <h3><?php _e('Total Logs', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number" id="afi-total-logs">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Errors', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number afi-stat-error" id="afi-error-logs">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Warnings', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number afi-stat-warning" id="afi-warning-logs">0</div>
                    </div>
                    <div class="afi-stat-card">
                        <h3><?php _e('Info', 'auto-featured-image'); ?></h3>
                        <div class="afi-stat-number afi-stat-info" id="afi-info-logs">0</div>
                    </div>
                </div>
            </div>
            
            <div class="afi-logs-table-container">
                <div class="afi-table-header">
                    <div class="afi-table-info">
                        <span id="afi-logs-showing-results"><?php _e('Loading...', 'auto-featured-image'); ?></span>
                    </div>
                    <div class="afi-table-pagination" id="afi-logs-pagination" style="display: none;">
                        <button type="button" id="afi-logs-prev-page" class="button" disabled>
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                            <?php _e('Previous', 'auto-featured-image'); ?>
                        </button>
                        <span id="afi-logs-page-info">Page 1 of 1</span>
                        <button type="button" id="afi-logs-next-page" class="button" disabled>
                            <?php _e('Next', 'auto-featured-image'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped" id="afi-logs-table">
                    <thead>
                        <tr>
                            <th class="column-level"><?php _e('Level', 'auto-featured-image'); ?></th>
                            <th class="column-message"><?php _e('Message', 'auto-featured-image'); ?></th>
                            <th class="column-job-id"><?php _e('Job ID', 'auto-featured-image'); ?></th>
                            <th class="column-user"><?php _e('User', 'auto-featured-image'); ?></th>
                            <th class="column-created"><?php _e('Created', 'auto-featured-image'); ?></th>
                            <th class="column-actions"><?php _e('Actions', 'auto-featured-image'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="afi-logs-table-body">
                        <tr>
                            <td colspan="6" class="afi-loading">
                                <span class="spinner is-active"></span>
                                <?php _e('Loading logs...', 'auto-featured-image'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Log Details Modal -->
        <div id="afi-log-modal" class="afi-modal" style="display: none;" role="dialog" aria-labelledby="afi-log-modal-title" aria-hidden="true">
            <div class="afi-modal-overlay"></div>
            <div class="afi-modal-dialog afi-modal-large">
                <div class="afi-modal-header">
                    <h2 id="afi-log-modal-title"><?php _e('Log Details', 'auto-featured-image'); ?></h2>
                    <button type="button" class="afi-close-modal" aria-label="<?php esc_attr_e('Close modal', 'auto-featured-image'); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    </button>
                </div>
                
                <div class="afi-modal-content">
                    <div class="afi-log-details">
                        <div class="afi-log-meta">
                            <div class="afi-log-meta-item">
                                <strong><?php _e('Level:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-level" class="afi-log-level-badge">-</span>
                            </div>
                            <div class="afi-log-meta-item">
                                <strong><?php _e('Created:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-created">-</span>
                            </div>
                            <div class="afi-log-meta-item">
                                <strong><?php _e('Job ID:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-job-id">-</span>
                            </div>
                            <div class="afi-log-meta-item">
                                <strong><?php _e('Post ID:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-post-id">-</span>
                            </div>
                            <div class="afi-log-meta-item">
                                <strong><?php _e('User:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-user">-</span>
                            </div>
                            <div class="afi-log-meta-item">
                                <strong><?php _e('IP Address:', 'auto-featured-image'); ?></strong>
                                <span id="afi-log-detail-ip">-</span>
                            </div>
                        </div>
                        
                        <div class="afi-log-message">
                            <h4><?php _e('Message', 'auto-featured-image'); ?></h4>
                            <div id="afi-log-detail-message" class="afi-log-message-content">-</div>
                        </div>
                        
                        <div class="afi-log-context" id="afi-log-context-section" style="display: none;">
                            <h4><?php _e('Context', 'auto-featured-image'); ?></h4>
                            <pre id="afi-log-detail-context" class="afi-log-context-content"></pre>
                        </div>
                        
                        <div class="afi-log-stack-trace" id="afi-log-stack-trace-section" style="display: none;">
                            <h4><?php _e('Stack Trace', 'auto-featured-image'); ?></h4>
                            <pre id="afi-log-detail-stack-trace" class="afi-log-stack-trace-content"></pre>
                        </div>
                        
                        <div class="afi-log-user-agent" id="afi-log-user-agent-section" style="display: none;">
                            <h4><?php _e('User Agent', 'auto-featured-image'); ?></h4>
                            <div id="afi-log-detail-user-agent" class="afi-log-user-agent-content">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Clear Logs Confirmation Modal -->
        <div id="afi-clear-logs-modal" class="afi-modal" style="display: none;" role="dialog" aria-labelledby="afi-clear-logs-modal-title" aria-hidden="true">
            <div class="afi-modal-overlay"></div>
            <div class="afi-modal-dialog afi-modal-small">
                <div class="afi-modal-header">
                    <h2 id="afi-clear-logs-modal-title"><?php _e('Clear Logs', 'auto-featured-image'); ?></h2>
                    <button type="button" class="afi-close-modal" aria-label="<?php esc_attr_e('Close modal', 'auto-featured-image'); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    </button>
                </div>
                
                <div class="afi-modal-content">
                    <div class="afi-clear-logs-options">
                        <p><?php _e('Choose how you want to clear the logs:', 'auto-featured-image'); ?></p>
                        
                        <label>
                            <input type="radio" name="afi_clear_type" value="all" checked>
                            <?php _e('Clear all logs', 'auto-featured-image'); ?>
                        </label><br>
                        
                        <label>
                            <input type="radio" name="afi_clear_type" value="old">
                            <?php _e('Clear logs older than', 'auto-featured-image'); ?>
                            <input type="number" id="afi-clear-days" value="30" min="1" max="365" class="small-text">
                            <?php _e('days', 'auto-featured-image'); ?>
                        </label><br>
                        
                        <label>
                            <input type="radio" name="afi_clear_type" value="by_level">
                            <?php _e('Clear logs by level:', 'auto-featured-image'); ?>
                            <select id="afi-clear-level">
                                <option value="debug"><?php _e('Debug', 'auto-featured-image'); ?></option>
                                <option value="info"><?php _e('Info', 'auto-featured-image'); ?></option>
                                <option value="warning"><?php _e('Warning', 'auto-featured-image'); ?></option>
                                <option value="error"><?php _e('Error', 'auto-featured-image'); ?></option>
                                <option value="critical"><?php _e('Critical', 'auto-featured-image'); ?></option>
                            </select>
                        </label>
                        
                        <div class="afi-clear-warning">
                            <p><strong><?php _e('Warning:', 'auto-featured-image'); ?></strong> <?php _e('This action cannot be undone.', 'auto-featured-image'); ?></p>
                        </div>
                    </div>
                    
                    <div class="afi-modal-actions">
                        <button type="button" id="afi-confirm-clear-logs" class="button button-primary">
                            <?php _e('Clear Logs', 'auto-featured-image'); ?>
                        </button>
                        <button type="button" class="button afi-close-modal">
                            <?php _e('Cancel', 'auto-featured-image'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display data management tab
     */
    private function display_data_management_tab() {
        ?>
        <div class="afi-data-management">
            <div class="afi-data-header">
                <h2><?php _e('Data Management', 'auto-featured-image'); ?></h2>
                <p><?php _e('Manage plugin data including job cleanup, database maintenance, and export/import functionality.', 'auto-featured-image'); ?></p>
            </div>

            <div class="afi-data-sections">
                <!-- Job Cleanup Section -->
                <div class="afi-data-section">
                    <h3><?php _e('Job Cleanup', 'auto-featured-image'); ?></h3>
                    <p><?php _e('Remove old completed jobs and their associated data to keep your database clean.', 'auto-featured-image'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('afi_cleanup_jobs', 'afi_cleanup_jobs_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="cleanup_days"><?php _e('Keep jobs from last', 'auto-featured-image'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="cleanup_days" name="cleanup_days" value="90" min="1" max="365" class="small-text" />
                                    <span><?php _e('days', 'auto-featured-image'); ?></span>
                                    <p class="description"><?php _e('Jobs older than this will be permanently deleted.', 'auto-featured-image'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" name="afi_cleanup_jobs" class="button button-secondary" 
                                   value="<?php esc_attr_e('Clean Up Old Jobs', 'auto-featured-image'); ?>" 
                                   onclick="return confirm('<?php esc_js(_e('Are you sure you want to delete old jobs? This action cannot be undone.', 'auto-featured-image')); ?>');" />
                        </p>
                    </form>
                </div>

                <!-- Database Maintenance Section -->
                <div class="afi-data-section">
                    <h3><?php _e('Database Maintenance', 'auto-featured-image'); ?></h3>
                    <p><?php _e('Optimize database tables and check for any data integrity issues.', 'auto-featured-image'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('afi_optimize_db', 'afi_optimize_db_nonce'); ?>
                        <p class="submit">
                            <input type="submit" name="afi_optimize_db" class="button button-secondary" 
                                   value="<?php esc_attr_e('Optimize Database', 'auto-featured-image'); ?>" />
                        </p>
                    </form>
                </div>

                <!-- Reset Plugin Data Section -->
                <div class="afi-data-section afi-danger-zone">
                    <h3><?php _e('Reset Plugin Data', 'auto-featured-image'); ?></h3>
                    <p><?php _e('Permanently delete all plugin data including jobs, logs, and settings. This action cannot be undone.', 'auto-featured-image'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('afi_reset_data', 'afi_reset_data_nonce'); ?>
                        <p class="submit">
                            <input type="submit" name="afi_reset_data" class="button button-secondary afi-danger-button" 
                                   value="<?php esc_attr_e('Reset All Data', 'auto-featured-image'); ?>" 
                                   onclick="return confirm('<?php esc_js(_e('WARNING: This will permanently delete ALL plugin data including jobs, logs, and settings. This action cannot be undone. Are you absolutely sure?', 'auto-featured-image')); ?>');" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}