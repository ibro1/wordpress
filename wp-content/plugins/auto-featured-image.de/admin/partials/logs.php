<?php
/**
 * Logs and Analytics Page Template
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_tab = $_GET['tab'] ?? 'recent';
$recent_logs = $recent_logs ?? array();
$log_levels = $log_levels ?? array( 'error', 'warning', 'info', 'debug' );
?>

<!-- Recent Logs Tab -->
<div id="tab-recent" class="auto-featured-image-tab-content" <?php echo $current_tab === 'recent' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Log Filters -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-filter"></span>
            <?php esc_html_e( 'Log Filters', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <form id="log-filters-form" class="auto-featured-image-flex auto-featured-image-gap-10">
                <div class="filter-group">
                    <label for="log-level-filter"><?php esc_html_e( 'Level:', 'auto-featured-image' ); ?></label>
                    <select id="log-level-filter" name="level">
                        <option value="all"><?php esc_html_e( 'All Levels', 'auto-featured-image' ); ?></option>
                        <?php foreach ( $log_levels as $level ) : ?>
                            <option value="<?php echo esc_attr( $level ); ?>">
                                <?php echo esc_html( ucfirst( $level ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="log-date-from"><?php esc_html_e( 'From:', 'auto-featured-image' ); ?></label>
                    <input type="date" id="log-date-from" name="date_from" />
                </div>
                
                <div class="filter-group">
                    <label for="log-date-to"><?php esc_html_e( 'To:', 'auto-featured-image' ); ?></label>
                    <input type="date" id="log-date-to" name="date_to" />
                </div>
                
                <div class="filter-group">
                    <label for="log-search"><?php esc_html_e( 'Search:', 'auto-featured-image' ); ?></label>
                    <input type="text" 
                           id="log-search" 
                           name="search" 
                           placeholder="<?php esc_attr_e( 'Search log messages...', 'auto-featured-image' ); ?>" />
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="auto-featured-image-button">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e( 'Filter', 'auto-featured-image' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="clear-filters-btn" 
                            class="auto-featured-image-button button-secondary">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e( 'Clear', 'auto-featured-image' ); ?>
                    </button>
                    
                    <button type="button" 
                            id="export-logs-btn" 
                            class="auto-featured-image-button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e( 'Export', 'auto-featured-image' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Log Entries -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e( 'Log Entries', 'auto-featured-image' ); ?>
            <span class="log-count" id="log-count">(0)</span>
        </h3>
        <div class="card-content">
            <div class="log-controls auto-featured-image-flex auto-featured-image-gap-10" style="margin-bottom: 15px;">
                <select id="logs-per-page">
                    <option value="25">25 per page</option>
                    <option value="50" selected>50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="200">200 per page</option>
                </select>
                
                <button type="button" 
                        id="refresh-logs-btn" 
                        class="auto-featured-image-button button-secondary">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Refresh', 'auto-featured-image' ); ?>
                </button>
                
                <button type="button" 
                        id="clear-logs-btn" 
                        class="auto-featured-image-button button-danger auto-featured-image-confirm"
                        data-confirm="<?php esc_attr_e( 'Are you sure you want to clear all logs? This action cannot be undone.', 'auto-featured-image' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e( 'Clear All Logs', 'auto-featured-image' ); ?>
                </button>
            </div>
            
            <div id="logs-table-container">
                <table class="auto-featured-image-table" id="logs-table">
                    <thead>
                        <tr>
                            <th class="column-timestamp"><?php esc_html_e( 'Timestamp', 'auto-featured-image' ); ?></th>
                            <th class="column-level"><?php esc_html_e( 'Level', 'auto-featured-image' ); ?></th>
                            <th class="column-message"><?php esc_html_e( 'Message', 'auto-featured-image' ); ?></th>
                            <th class="column-context"><?php esc_html_e( 'Context', 'auto-featured-image' ); ?></th>
                            <th class="column-actions"><?php esc_html_e( 'Actions', 'auto-featured-image' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <tr>
                            <td colspan="5" class="auto-featured-image-text-center">
                                <?php esc_html_e( 'Loading logs...', 'auto-featured-image' ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="log-pagination" id="log-pagination" style="margin-top: 20px;">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
    
</div>

<!-- Errors Tab -->
<div id="tab-errors" class="auto-featured-image-tab-content" <?php echo $current_tab === 'errors' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Error Summary -->
    <div class="auto-featured-image-dashboard">
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e( 'Error Summary', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric error" id="total-errors">0</div>
                <p><?php esc_html_e( 'Total Errors (24h)', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e( 'Error Rate', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric error" id="error-rate">0%</div>
                <p><?php esc_html_e( 'Error Rate (24h)', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e( 'Last Error', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" id="last-error-time">-</div>
                <p><?php esc_html_e( 'Time Since Last Error', 'auto-featured-image' ); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Error Types Chart -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-chart-pie"></span>
            <?php esc_html_e( 'Error Types Distribution', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <canvas id="error-types-chart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Recent Errors -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-list-view"></span>
            <?php esc_html_e( 'Recent Errors', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <div id="recent-errors-list">
                <p class="auto-featured-image-text-center">
                    <?php esc_html_e( 'Loading recent errors...', 'auto-featured-image' ); ?>
                </p>
            </div>
        </div>
    </div>
    
</div>

<!-- Analytics Tab -->
<div id="tab-analytics" class="auto-featured-image-tab-content" <?php echo $current_tab === 'analytics' ? '' : 'style="display:none;"'; ?>>
    
    <!-- Analytics Summary -->
    <div class="auto-featured-image-dashboard">
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e( 'Processing Volume', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" id="processing-volume">0</div>
                <p><?php esc_html_e( 'Jobs Processed (7 days)', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-performance"></span>
                <?php esc_html_e( 'Average Performance', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric" id="avg-performance">0s</div>
                <p><?php esc_html_e( 'Avg Processing Time', 'auto-featured-image' ); ?></p>
            </div>
        </div>
        
        <div class="auto-featured-image-card">
            <h3>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e( 'Success Rate', 'auto-featured-image' ); ?>
            </h3>
            <div class="card-content">
                <div class="card-metric success" id="analytics-success-rate">0%</div>
                <p><?php esc_html_e( 'Overall Success Rate', 'auto-featured-image' ); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Activity Timeline Chart -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-chart-line"></span>
            <?php esc_html_e( 'Activity Timeline (Last 30 Days)', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <canvas id="activity-timeline-chart" width="800" height="300"></canvas>
        </div>
    </div>
    
    <!-- Algorithm Performance -->
    <div class="auto-featured-image-card">
        <h3>
            <span class="dashicons dashicons-admin-tools"></span>
            <?php esc_html_e( 'Algorithm Performance Analysis', 'auto-featured-image' ); ?>
        </h3>
        <div class="card-content">
            <table class="auto-featured-image-table" id="algorithm-performance-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Algorithm', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Usage Count', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Success Rate', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Avg Score', 'auto-featured-image' ); ?></th>
                        <th><?php esc_html_e( 'Performance', 'auto-featured-image' ); ?></th>
                    </tr>
                </thead>
                <tbody id="algorithm-performance-body">
                    <tr>
                        <td colspan="5" class="auto-featured-image-text-center">
                            <?php esc_html_e( 'Loading algorithm performance data...', 'auto-featured-image' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<!-- Log Detail Modal -->
<div id="log-detail-modal" class="auto-featured-image-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php esc_html_e( 'Log Entry Details', 'auto-featured-image' ); ?></h3>
            <button type="button" class="modal-close" aria-label="<?php esc_attr_e( 'Close', 'auto-featured-image' ); ?>">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="modal-body" id="log-detail-content">
            <!-- Populated by JavaScript -->
        </div>
        <div class="modal-footer">
            <button type="button" class="auto-featured-image-button button-secondary modal-close">
                <?php esc_html_e( 'Close', 'auto-featured-image' ); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize logs interface
    AutoFeaturedImageLogs.init();
});
</script>
