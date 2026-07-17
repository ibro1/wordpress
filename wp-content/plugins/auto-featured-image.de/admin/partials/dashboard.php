<?php
/**
 * Dashboard Page Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_tab = $_GET['tab'] ?? 'overview';
$queue_stats = $queue_stats ?? array();
$performance_metrics = $performance_metrics ?? array();
?>

<!-- Overview Tab -->
<div id="tab-overview" class="auto-featured-image-tab-content" <?php echo $current_tab === 'overview' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Status Cards -->
    <div class="auto-featured-image-dashboard">
        
        <!-- Queue Status Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e( 'Queue Status', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="auto-featured-image-status status-idle" id="queue-status">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php esc_html_e( 'Idle', 'auto-featured-image' ); ?></span>
                </div>
                <div class="card-metric" data-metric="pending_jobs">
                    <?php echo esc_html( $queue_stats['pending_jobs'] ?? 0 ); ?>
                </div>
                <p><?php esc_html_e( 'Jobs Pending', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <!-- Processing Statistics Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e( 'Processing Statistics', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric success" data-metric="completed_jobs">
                    <?php echo esc_html( $queue_stats['completed_jobs'] ?? 0 ); ?>
                </div>
                <p><?php esc_html_e( 'Jobs Completed', 'auto-featured-image' ); ?></p>
                
                <div class="card-metric error" data-metric="failed_jobs" style="margin-top: 10px;">
                    <?php echo esc_html( $queue_stats['failed_jobs'] ?? 0 ); ?>
                </div>
                <p><?php esc_html_e( 'Jobs Failed', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <!-- Success Rate Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e( 'Success Rate', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric success" data-metric="success_rate">
                    <?php echo esc_html( ( $queue_stats['success_rate'] ?? 0 ) . '%' ); ?>
                </div>
                <p><?php esc_html_e( 'Overall Success Rate', 'auto-featured-image' ); ?></p>
                
                <!-- Progress Bar -->
                <div class="auto-featured-image-progress" style="margin-top: 15px;">
                    <div class="auto-featured-image-progress-bar" style="width: <?php echo esc_attr( $queue_stats['success_rate'] ?? 0 ); ?>%;">
                        <div class="auto-featured-image-progress-text">
                            <?php echo esc_html( ( $queue_stats['success_rate'] ?? 0 ) . '%' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Health Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e( 'System Health', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="auto-featured-image-status status-active" id="system-health">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php esc_html_e( 'Healthy', 'auto-featured-image' ); ?></span>
                </div>
                <div class="card-metric" data-metric="memory_usage">
                    <?php echo esc_html( $performance_metrics['memory_usage'] ?? '0 MB' ); ?>
                </div>
                <p><?php esc_html_e( 'Memory Usage', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
    </div>
    
    <!-- Recent Activity -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e( 'Recent Activity', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <div id="recent-activity-list">
                <p class="auto-featured-image-text-center">
                    <?php esc_html_e( 'Loading recent activity...', 'auto-featured-image' ); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e( 'Quick Actions', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content auto-featured-image-flex auto-featured-image-gap-10">
            <button type="button" 
                    class="auto-featured-image-button auto-featured-image-action-button" 
                    data-action="start_processing">
                <span class="dashicons dashicons-controls-play"></span>
                <?php esc_html_e( 'Start Processing', 'auto-featured-image' ); ?>
            </button>
            
            <button type="button" 
                    class="auto-featured-image-button button-secondary auto-featured-image-action-button" 
                    data-action="pause_processing">
                <span class="dashicons dashicons-controls-pause"></span>
                <?php esc_html_e( 'Pause Processing', 'auto-featured-image' ); ?>
            </button>
            
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-featured-image-bulk-process' ) ); ?>" 
               class="auto-featured-image-button button-secondary">
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e( 'Bulk Process', 'auto-featured-image' ); ?>
            </a>
        </div>
    </div>
    
</div>

<!-- Statistics Tab -->
<div id="tab-statistics" class="auto-featured-image-tab-content" <?php echo $current_tab === 'statistics' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Charts Section -->
    <div class="auto-featured-image-dashboard">
        
        <!-- Processing Chart -->
        <div class="auto-featured-image-card" style="grid-column: span 2;">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e( 'Processing Activity (Last 7 Days)', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <canvas id="processing-chart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Algorithm Performance -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-chart-pie"></span>
                <?php esc_html_e( 'Algorithm Performance', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <canvas id="algorithm-chart" width="300" height="300"></canvas>
            </div>
        </div>
        
    </div>
    
    <!-- Detailed Statistics -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-analytics"></span>
            <?php esc_html_e( 'Detailed Statistics', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <table class="auto-featured-image-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Metric', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Value', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Change (24h)', 'auto-featured-image' ); ?></th>
                    </tr>
                </thead>
                <tbody id="detailed-statistics">
                    <tr>
                        <td><?php esc_html_e( 'Total Posts Processed', 'auto-featured-image' ); ?></td>
                        <td data-statistic="total_processed">-</td>
                        <td data-statistic="total_processed_change">-</td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Images Assigned', 'auto-featured-image' ); ?></td>
                        <td data-statistic="images_assigned">-</td>
                        <td data-statistic="images_assigned_change">-</td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Average Processing Time', 'auto-featured-image' ); ?></td>
                        <td data-statistic="avg_processing_time">-</td>
                        <td data-statistic="avg_processing_time_change">-</td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Queue Throughput', 'auto-featured-image' ); ?></td>
                        <td data-statistic="queue_throughput">-</td>
                        <td data-statistic="queue_throughput_change">-</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<!-- Performance Tab -->
<div id="tab-performance" class="auto-featured-image-tab-content" <?php echo $current_tab === 'performance' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Performance Metrics -->
    <div class="auto-featured-image-dashboard">
        
        <!-- Execution Time Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e( 'Execution Time', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" data-metric="avg_execution_time">
                    <?php echo esc_html( $performance_metrics['avg_execution_time'] ?? '0s' ); ?>
                </div>
                <p><?php esc_html_e( 'Average per Batch', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <!-- Memory Usage Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-database"></span>
                <?php esc_html_e( 'Memory Usage', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" data-metric="peak_memory">
                    <?php echo esc_html( $performance_metrics['peak_memory'] ?? '0 MB' ); ?>
                </div>
                <p><?php esc_html_e( 'Peak Memory Usage', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <!-- Batch Size Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-grid-view"></span>
                <?php esc_html_e( 'Batch Performance', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" data-metric="optimal_batch_size">
                    <?php echo esc_html( $performance_metrics['optimal_batch_size'] ?? '25' ); ?>
                </div>
                <p><?php esc_html_e( 'Optimal Batch Size', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <!-- Error Rate Card -->
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'Error Rate', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric error" data-metric="error_rate">
                    <?php echo esc_html( ( $performance_metrics['error_rate'] ?? 0 ) . '%' ); ?>
                </div>
                <p><?php esc_html_e( 'Last 24 Hours', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
    </div>
    
    <!-- Performance Chart -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-chart-area"></span>
            <?php esc_html_e( 'Performance Trends', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <canvas id="performance-chart" width="800" height="300"></canvas>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e( 'System Information', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <table class="auto-featured-image-table">
                <tbody id="system-information">
                    <tr>
                        <td><?php esc_html_e( 'PHP Version', 'auto-featured-image' ); ?></td>
                        <td><?php echo esc_html( PHP_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'WordPress Version', 'auto-featured-image' ); ?></td>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Memory Limit', 'auto-featured-image' ); ?></td>
                        <td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Max Execution Time', 'auto-featured-image' ); ?></td>
                        <td><?php echo esc_html( ini_get( 'max_execution_time' ) . 's' ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Action Scheduler', 'auto-featured-image' ); ?></td>
                        <td>
                            <?php if ( function_exists( 'as_schedule_single_action' ) ) : ?>
                                <span class="auto-featured-image-status status-active">
                                    <span class="status-dot"></span>
                                    <?php esc_html_e( 'Available', 'auto-featured-image' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="auto-featured-image-status status-error">
                                    <span class="status-dot"></span>
                                    <?php esc_html_e( 'Not Available', 'auto-featured-image' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize dashboard
    AutoFeaturedImageDashboard.init();
});
</script>
