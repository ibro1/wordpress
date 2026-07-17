<?php
/**
 * Bulk Processing Page Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_types = $post_types ?? array();
$queue_status = $queue_status ?? false;
$settings = get_option( 'auto_featured_image_settings', array() );
?>

<div class="auto-featured-image-bulk-process">
    
    <!-- Processing Status Card -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Processing Status', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <div class="auto-featured-image-status-display">
                <div class="auto-featured-image-status <?php echo $queue_status ? 'status-active' : 'status-idle'; ?>" id="processing-status">
                    <span class="status-dot"></span>
                    <span class="status-text">
                        <?php echo $queue_status ? esc_html__( 'Processing', 'auto-featured-image' ) : esc_html__( 'Idle', 'auto-featured-image' ); ?>
                    </span>
                </div>
                
                <div class="processing-controls auto-featured-image-flex auto-featured-image-gap-10">
                    <button type="button" 
                            id="start-processing-btn" 
                            class="auto-featured-image-button"
                            <?php echo $queue_status ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e( 'Start Processing', 'auto-featured-image' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="pause-processing-btn" 
                            class="auto-featured-image-button button-secondary"
                            <?php echo ! $queue_status ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-controls-pause"></span>
                        <?php esc_html_e( 'Pause Processing', 'auto-featured-image' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="stop-processing-btn" 
                            class="auto-featured-image-button button-danger"
                            <?php echo ! $queue_status ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-controls-forward"></span>
                        <?php esc_html_e( 'Stop Processing', 'auto-featured-image' ); ?>
                    </button>
                </div>
            </div>
            
            <!-- Progress Display -->
            <div class="progress-display" id="progress-display" style="<?php echo ! $queue_status ? 'display: none;' : ''; ?>">
                <div class="auto-featured-image-progress" data-progress="overall">
                    <div class="auto-featured-image-progress-bar" style="width: 0%;">
                        <div class="auto-featured-image-progress-text">0%</div>
                    </div>
                </div>
                
                <div class="progress-details auto-featured-image-flex auto-featured-image-gap-20" style="margin-top: 15px;">
                    <div class="progress-stat">
                        <strong id="processed-count">0</strong>
                        <span><?php esc_html_e( 'Processed', 'auto-featured-image' ); ?></span>
                    </div>
                    <div class="progress-stat">
                        <strong id="remaining-count">0</strong>
                        <span><?php esc_html_e( 'Remaining', 'auto-featured-image' ); ?></span>
                    </div>
                    <div class="progress-stat">
                        <strong id="success-count">0</strong>
                        <span><?php esc_html_e( 'Success', 'auto-featured-image' ); ?></span>
                    </div>
                    <div class="progress-stat">
                        <strong id="failed-count">0</strong>
                        <span><?php esc_html_e( 'Failed', 'auto-featured-image' ); ?></span>
                    </div>
                </div>
                
                <div class="processing-info" style="margin-top: 15px;">
                    <p>
                        <strong><?php esc_html_e( 'Current Batch:', 'auto-featured-image' ); ?></strong>
                        <span id="current-batch">-</span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Estimated Time Remaining:', 'auto-featured-image' ); ?></strong>
                        <span id="estimated-time">-</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Processing Configuration -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e( 'Bulk Processing Configuration', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <form id="bulk-processing-form" class="auto-featured-image-form">
                <table class="auto-featured-image-form-table">
                    <tr>
                        <th scope="row">
                            <label for="bulk_post_types">
                                <?php esc_html_e( 'Post Types', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <?php foreach ( $post_types as $post_type ) : ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" 
                                           name="post_types[]" 
                                           value="<?php echo esc_attr( $post_type->name ); ?>"
                                           <?php checked( in_array( $post_type->name, $settings['post_types'] ?? array( 'post' ) ) ); ?> />
                                    <?php echo esc_html( $post_type->label ); ?>
                                    <span class="post-type-count" data-post-type="<?php echo esc_attr( $post_type->name ); ?>">
                                        (<?php echo esc_html( wp_count_posts( $post_type->name )->publish ); ?> posts)
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <p class="description">
                                <?php esc_html_e( 'Select which post types to process for featured image assignment.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bulk_skip_existing">
                                <?php esc_html_e( 'Skip Existing', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="bulk_skip_existing" 
                                       name="skip_existing" 
                                       value="1" 
                                       <?php checked( $settings['skip_existing'] ?? true ); ?> />
                                <?php esc_html_e( 'Skip posts that already have featured images', 'auto-featured-image' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'When enabled, posts with existing featured images will be skipped.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bulk_batch_size">
                                <?php esc_html_e( 'Batch Size', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="bulk_batch_size" 
                                   name="batch_size" 
                                   value="<?php echo esc_attr( $settings['batch_size'] ?? 25 ); ?>"
                                   min="1" 
                                   max="1000" 
                                   class="small-text" />
                            <p class="description">
                                <?php esc_html_e( 'Number of posts to process in each batch. Lower values use less memory.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bulk_priority">
                                <?php esc_html_e( 'Processing Priority', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <select id="bulk_priority" name="priority">
                                <option value="low"><?php esc_html_e( 'Low (Background)', 'auto-featured-image' ); ?></option>
                                <option value="normal" selected><?php esc_html_e( 'Normal', 'auto-featured-image' ); ?></option>
                                <option value="high"><?php esc_html_e( 'High', 'auto-featured-image' ); ?></option>
                                <option value="urgent"><?php esc_html_e( 'Urgent', 'auto-featured-image' ); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Higher priority jobs will be processed first.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bulk_date_range">
                                <?php esc_html_e( 'Date Range', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <div class="auto-featured-image-flex auto-featured-image-gap-10">
                                <input type="date" 
                                       id="bulk_date_from" 
                                       name="date_from" 
                                       placeholder="<?php esc_attr_e( 'From date', 'auto-featured-image' ); ?>" />
                                <span><?php esc_html_e( 'to', 'auto-featured-image' ); ?></span>
                                <input type="date" 
                                       id="bulk_date_to" 
                                       name="date_to" 
                                       placeholder="<?php esc_attr_e( 'To date', 'auto-featured-image' ); ?>" />
                            </div>
                            <p class="description">
                                <?php esc_html_e( 'Optional: Limit processing to posts published within this date range.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="bulk_specific_posts">
                                <?php esc_html_e( 'Specific Posts', 'auto-featured-image' ); ?>
                            </label>
                        </th>
                        <td>
                            <textarea id="bulk_specific_posts" 
                                      name="specific_posts" 
                                      rows="3" 
                                      class="large-text"
                                      placeholder="<?php esc_attr_e( 'Enter post IDs separated by commas (e.g., 123, 456, 789)', 'auto-featured-image' ); ?>"></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Optional: Process only specific posts by entering their IDs.', 'auto-featured-image' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="form-actions auto-featured-image-flex auto-featured-image-gap-10" style="margin-top: 20px;">
                    <button type="button" 
                            id="estimate-jobs-btn" 
                            class="auto-featured-image-button button-secondary">
                        <span class="dashicons dashicons-calculator"></span>
                        <?php esc_html_e( 'Estimate Jobs', 'auto-featured-image' ); ?>
                    </button>
                    
                    <button type="submit" 
                            id="start-bulk-processing-btn" 
                            class="auto-featured-image-button">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e( 'Start Bulk Processing', 'auto-featured-image' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Job Estimation Results -->
    <div class="auto-featured-image-card" id="estimation-results" style="display: none;">
        <h3>
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e( 'Job Estimation', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <div class="estimation-summary auto-featured-image-flex auto-featured-image-gap-20">
                <div class="estimation-stat">
                    <div class="card-metric" id="estimated-total-posts">0</div>
                    <p><?php esc_html_e( 'Total Posts', 'auto-featured-image' ); ?></p>
                </div>
                <div class="estimation-stat">
                    <div class="card-metric" id="estimated-processable-posts">0</div>
                    <p><?php esc_html_e( 'Processable Posts', 'auto-featured-image' ); ?></p>
                </div>
                <div class="estimation-stat">
                    <div class="card-metric" id="estimated-batches">0</div>
                    <p><?php esc_html_e( 'Estimated Batches', 'auto-featured-image' ); ?></p>
                </div>
                <div class="estimation-stat">
                    <div class="card-metric" id="estimated-duration">0</div>
                    <p><?php esc_html_e( 'Estimated Duration', 'auto-featured-image' ); ?></p>
                </div>
            </div>
            
            <div class="estimation-breakdown" style="margin-top: 20px;">
                <h4><?php esc_html_e( 'Breakdown by Post Type', 'auto-featured-image' ); ?></h4>
                <table class="auto-featured-image-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Post Type', 'auto-featured-image' ); ?></th>
                            <th><?php esc_html_e( 'Total Posts', 'auto-featured-image' ); ?></th>
                            <th><?php esc_html_e( 'Without Featured Image', 'auto-featured-image' ); ?></th>
                            <th><?php esc_html_e( 'To Process', 'auto-featured-image' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="estimation-breakdown-table">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Processing History -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e( 'Recent Processing History', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <div id="processing-history">
                <p class="auto-featured-image-text-center">
                    <?php esc_html_e( 'Loading processing history...', 'auto-featured-image' ); ?>
                </p>
            </div>
        </div>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize bulk processing interface
    AutoFeaturedImageBulkProcess.init();
});
</script>
