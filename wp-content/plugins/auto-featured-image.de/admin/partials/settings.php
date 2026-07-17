<?php
/**
 * Settings Page Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_tab = $_GET['tab'] ?? 'general';
$settings = get_option( 'auto_featured_image_settings', array() );
?>

<form method="post" action="options.php" class="auto-featured-image-form">
    <?php settings_fields( 'auto_featured_image_settings' ); ?>
    
    <!-- General Settings Tab -->
    <div id="tab-general" class="auto-featured-image-tab-content" <?php echo $current_tab === 'general' ? '' : 'style="display:none;"'; ?>>
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e( 'General Settings', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <th scope="row">
                        <label for="auto_processing_enabled">
                            <?php esc_html_e( 'Auto Processing', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="auto_processing_enabled" 
                                   name="auto_featured_image_settings[auto_processing_enabled]" 
                                   value="1" 
                                   <?php checked( $settings['auto_processing_enabled'] ?? false ); ?> />
                            <?php esc_html_e( 'Automatically assign featured images to new posts', 'auto-featured-image' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, featured images will be automatically assigned to new posts as they are published.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="post_types">
                            <?php esc_html_e( 'Post Types', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        $post_types = get_post_types( array( 'public' => true ), 'objects' );
                        $enabled_post_types = $settings['post_types'] ?? array( 'post' );
                        
                        foreach ( $post_types as $post_type ) :
                            ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" 
                                       name="auto_featured_image_settings[post_types][]" 
                                       value="<?php echo esc_attr( $post_type->name ); ?>"
                                       <?php checked( in_array( $post_type->name, $enabled_post_types ) ); ?> />
                                <?php echo esc_html( $post_type->label ); ?>
                            </label>
                            <?php
                        endforeach;
                        ?>
                        <p class="description">
                            <?php esc_html_e( 'Select which post types should have featured images automatically assigned.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="skip_existing">
                            <?php esc_html_e( 'Skip Existing', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="skip_existing" 
                                   name="auto_featured_image_settings[skip_existing]" 
                                   value="1" 
                                   <?php checked( $settings['skip_existing'] ?? true ); ?> />
                            <?php esc_html_e( 'Skip posts that already have featured images', 'auto-featured-image' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, posts with existing featured images will be skipped during bulk processing.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_image_score">
                            <?php esc_html_e( 'Minimum Image Score', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="min_image_score" 
                               name="auto_featured_image_settings[min_image_score]" 
                               value="<?php echo esc_attr( $settings['min_image_score'] ?? 30 ); ?>"
                               min="0" 
                               max="100" 
                               class="small-text" />
                        <p class="description">
                            <?php esc_html_e( 'Minimum quality score (0-100) required for an image to be selected as featured image.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Algorithm Settings Tab -->
    <div id="tab-algorithms" class="auto-featured-image-tab-content" <?php echo $current_tab === 'algorithms' ? '' : 'style="display:none;"'; ?>>
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-admin-tools"></span>
                <?php esc_html_e( 'Algorithm Configuration', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Enabled Algorithms', 'auto-featured-image' ); ?>
                    </th>
                    <td>
                        <?php
                        $algorithms = $this->plugin->algorithms->get_algorithms();
                        $enabled_algorithms = $settings['enabled_algorithms'] ?? array_keys( $algorithms );
                        
                        foreach ( $algorithms as $algorithm_key => $algorithm_data ) :
                            ?>
                            <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">
                                    <input type="checkbox" 
                                           name="auto_featured_image_settings[enabled_algorithms][]" 
                                           value="<?php echo esc_attr( $algorithm_key ); ?>"
                                           <?php checked( in_array( $algorithm_key, $enabled_algorithms ) ); ?> />
                                    <?php echo esc_html( $algorithm_data['name'] ); ?>
                                </label>
                                <p class="description" style="margin: 0;">
                                    <?php echo esc_html( $algorithm_data['description'] ); ?>
                                </p>
                            </div>
                            <?php
                        endforeach;
                        ?>
                        <p class="description">
                            <?php esc_html_e( 'Select which algorithms should be used for image selection. Multiple algorithms can be enabled and their results will be combined.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="primary_algorithm">
                            <?php esc_html_e( 'Primary Algorithm', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="primary_algorithm" name="auto_featured_image_settings[primary_algorithm]">
                            <?php
                            $primary_algorithm = $settings['primary_algorithm'] ?? 'smart_content_analysis';
                            
                            foreach ( $algorithms as $algorithm_key => $algorithm_data ) :
                                ?>
                                <option value="<?php echo esc_attr( $algorithm_key ); ?>" 
                                        <?php selected( $primary_algorithm, $algorithm_key ); ?>>
                                    <?php echo esc_html( $algorithm_data['name'] ); ?>
                                </option>
                                <?php
                            endforeach;
                            ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Primary algorithm to use when multiple algorithms are enabled.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="fallback_algorithm">
                            <?php esc_html_e( 'Fallback Algorithm', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="fallback_algorithm" name="auto_featured_image_settings[fallback_algorithm]">
                            <option value=""><?php esc_html_e( 'None', 'auto-featured-image' ); ?></option>
                            <?php
                            $fallback_algorithm = $settings['fallback_algorithm'] ?? 'first_quality_image';
                            
                            foreach ( $algorithms as $algorithm_key => $algorithm_data ) :
                                ?>
                                <option value="<?php echo esc_attr( $algorithm_key ); ?>" 
                                        <?php selected( $fallback_algorithm, $algorithm_key ); ?>>
                                    <?php echo esc_html( $algorithm_data['name'] ); ?>
                                </option>
                                <?php
                            endforeach;
                            ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Fallback algorithm to use if the primary algorithm fails to find a suitable image.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e( 'Algorithm Weights', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <td colspan="2">
                        <p><?php esc_html_e( 'Adjust the relative importance of different scoring factors:', 'auto-featured-image' ); ?></p>
                    </td>
                </tr>
                
                <?php
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
                
                foreach ( $weight_labels as $weight_key => $weight_label ) :
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="weight_<?php echo esc_attr( $weight_key ); ?>">
                                <?php echo esc_html( $weight_label ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="range" 
                                   id="weight_<?php echo esc_attr( $weight_key ); ?>" 
                                   name="auto_featured_image_settings[algorithm_weights][<?php echo esc_attr( $weight_key ); ?>]" 
                                   value="<?php echo esc_attr( $algorithm_weights[ $weight_key ] ); ?>"
                                   min="0" 
                                   max="50" 
                                   step="1"
                                   style="width: 200px;" />
                            <span class="weight-value"><?php echo esc_html( $algorithm_weights[ $weight_key ] ); ?>%</span>
                        </td>
                    </tr>
                    <?php
                endforeach;
                ?>
            </table>
        </div>
    </div>
    
    <!-- Performance Settings Tab -->
    <div id="tab-performance" class="auto-featured-image-tab-content" <?php echo $current_tab === 'performance' ? '' : 'style="display:none;"'; ?>>
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-performance"></span>
                <?php esc_html_e( 'Performance Settings', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <th scope="row">
                        <label for="batch_size">
                            <?php esc_html_e( 'Batch Size', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="batch_size" 
                               name="auto_featured_image_settings[batch_size]" 
                               value="<?php echo esc_attr( $settings['batch_size'] ?? 25 ); ?>"
                               min="1" 
                               max="1000" 
                               class="small-text" />
                        <p class="description">
                            <?php esc_html_e( 'Number of posts to process in each batch. Lower values use less memory but take longer.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="target_execution_time">
                            <?php esc_html_e( 'Target Execution Time', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="target_execution_time" 
                               name="auto_featured_image_settings[target_execution_time]" 
                               value="<?php echo esc_attr( $settings['target_execution_time'] ?? 30 ); ?>"
                               min="5" 
                               max="300" 
                               class="small-text" />
                        <span><?php esc_html_e( 'seconds', 'auto-featured-image' ); ?></span>
                        <p class="description">
                            <?php esc_html_e( 'Target execution time per batch. The system will adjust batch sizes to meet this target.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="adaptive_batch_sizing">
                            <?php esc_html_e( 'Adaptive Batch Sizing', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="adaptive_batch_sizing" 
                                   name="auto_featured_image_settings[adaptive_batch_sizing]" 
                                   value="1" 
                                   <?php checked( $settings['adaptive_batch_sizing'] ?? true ); ?> />
                            <?php esc_html_e( 'Automatically adjust batch sizes based on performance', 'auto-featured-image' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, the system will automatically optimize batch sizes based on server performance.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_retry_attempts">
                            <?php esc_html_e( 'Max Retry Attempts', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="max_retry_attempts" 
                               name="auto_featured_image_settings[max_retry_attempts]" 
                               value="<?php echo esc_attr( $settings['max_retry_attempts'] ?? 3 ); ?>"
                               min="0" 
                               max="10" 
                               class="small-text" />
                        <p class="description">
                            <?php esc_html_e( 'Maximum number of times to retry failed jobs before marking them as permanently failed.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Advanced Settings Tab -->
    <div id="tab-advanced" class="auto-featured-image-tab-content" <?php echo $current_tab === 'advanced' ? '' : 'style="display:none;"'; ?>>
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Advanced Settings', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <th scope="row">
                        <label for="debug_mode">
                            <?php esc_html_e( 'Debug Mode', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="debug_mode" 
                                   name="auto_featured_image_settings[debug_mode]" 
                                   value="1" 
                                   <?php checked( $settings['debug_mode'] ?? false ); ?> />
                            <?php esc_html_e( 'Enable detailed debug logging', 'auto-featured-image' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, detailed debug information will be logged. Only enable for troubleshooting.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="log_retention_days">
                            <?php esc_html_e( 'Log Retention', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="log_retention_days" 
                               name="auto_featured_image_settings[log_retention_days]" 
                               value="<?php echo esc_attr( $settings['log_retention_days'] ?? 30 ); ?>"
                               min="1" 
                               max="365" 
                               class="small-text" />
                        <span><?php esc_html_e( 'days', 'auto-featured-image' ); ?></span>
                        <p class="description">
                            <?php esc_html_e( 'Number of days to keep log entries before automatic cleanup.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cleanup_completed_jobs">
                            <?php esc_html_e( 'Cleanup Completed Jobs', 'auto-featured-image' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cleanup_completed_jobs" 
                               name="auto_featured_image_settings[cleanup_completed_jobs]" 
                               value="<?php echo esc_attr( $settings['cleanup_completed_jobs'] ?? 7 ); ?>"
                               min="1" 
                               max="90" 
                               class="small-text" />
                        <span><?php esc_html_e( 'days', 'auto-featured-image' ); ?></span>
                        <p class="description">
                            <?php esc_html_e( 'Number of days to keep completed job records before cleanup.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'Reset Options', 'auto-featured-image' ); ?>
            </h3>
            
            <table class="auto-featured-image-form-table">
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Reset Settings', 'auto-featured-image' ); ?>
                    </th>
                    <td>
                        <button type="button" 
                                class="auto-featured-image-button button-secondary auto-featured-image-confirm" 
                                data-action="reset_settings"
                                data-confirm="<?php esc_attr_e( 'Are you sure you want to reset all settings to defaults? This cannot be undone.', 'auto-featured-image' ); ?>">
                            <?php esc_html_e( 'Reset All Settings', 'auto-featured-image' ); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e( 'Reset all plugin settings to their default values.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Clear Data', 'auto-featured-image' ); ?>
                    </th>
                    <td>
                        <button type="button" 
                                class="auto-featured-image-button button-danger auto-featured-image-confirm" 
                                data-action="clear_all_data"
                                data-confirm="<?php esc_attr_e( 'Are you sure you want to clear all plugin data? This will remove all jobs, logs, and statistics. This cannot be undone.', 'auto-featured-image' ); ?>">
                            <?php esc_html_e( 'Clear All Data', 'auto-featured-image' ); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e( 'Remove all plugin data including jobs, logs, and performance metrics.', 'auto-featured-image' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php submit_button( __( 'Save Settings', 'auto-featured-image' ), 'primary', 'submit', true, array( 'class' => 'auto-featured-image-button' ) ); ?>
</form>

<script>
jQuery(document).ready(function($) {
    // Update weight value displays
    $('input[type="range"]').on('input', function() {
        $(this).siblings('.weight-value').text($(this).val() + '%');
    });
    
    // Tab switching
    $('.auto-featured-image-nav-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href').split('#')[1];
        
        // Update active tab
        $(this).siblings().removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show/hide content
        $('.auto-featured-image-tab-content').hide();
        $('#tab-' + target).show();
    });
});
</script>
