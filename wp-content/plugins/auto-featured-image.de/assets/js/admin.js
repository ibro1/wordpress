/**
 * Auto Featured Image Admin JavaScript
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main Admin Object
     */
    window.AutoFeaturedImageAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.startPolling();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Navigation tabs
            $(document).on('click', '.auto-featured-image-nav-tabs .nav-tab', this.handleTabClick);
            
            // Action buttons
            $(document).on('click', '.auto-featured-image-action-button', this.handleActionButton);
            
            // Form submissions
            $(document).on('submit', '.auto-featured-image-form', this.handleFormSubmit);
            
            // Refresh buttons
            $(document).on('click', '.auto-featured-image-refresh', this.handleRefresh);
            
            // Confirmation dialogs
            $(document).on('click', '.auto-featured-image-confirm', this.handleConfirmAction);
        },

        /**
         * Initialize components
         */
        initComponents: function() {
            this.initProgressBars();
            this.initTooltips();
            this.initDatePickers();
            this.initCharts();
        },

        /**
         * Handle tab navigation
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var target = $tab.data('target');
            
            // Update active tab
            $tab.siblings().removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show/hide content
            $('.auto-featured-image-tab-content').hide();
            $(target).show();
            
            // Update URL without reload
            if (history.pushState) {
                var url = new URL(window.location);
                url.searchParams.set('tab', $tab.data('tab'));
                history.pushState(null, '', url);
            }
        },

        /**
         * Handle action buttons
         */
        handleActionButton: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var data = $button.data();
            
            // Disable button during processing
            $button.prop('disabled', true).addClass('auto-featured-image-loading');
            
            AutoFeaturedImageAdmin.ajaxRequest(action, data)
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('success', response.message || autoFeaturedImageAdmin.strings.success);
                    AutoFeaturedImageAdmin.refreshData();
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : autoFeaturedImageAdmin.strings.error;
                    AutoFeaturedImageAdmin.showNotice('error', message);
                })
                .always(function() {
                    $button.prop('disabled', false).removeClass('auto-featured-image-loading');
                });
        },

        /**
         * Handle form submissions
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = new FormData(this);
            
            // Add AJAX data
            formData.append('action', 'auto_featured_image_ajax');
            formData.append('nonce', autoFeaturedImageAdmin.nonce);
            formData.append('action_type', $form.data('action'));
            
            // Show loading state
            $form.addClass('auto-featured-image-loading');
            
            $.ajax({
                url: autoFeaturedImageAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AutoFeaturedImageAdmin.showNotice('success', response.data.message || autoFeaturedImageAdmin.strings.success);
                        AutoFeaturedImageAdmin.refreshData();
                    } else {
                        AutoFeaturedImageAdmin.showNotice('error', response.data || autoFeaturedImageAdmin.strings.error);
                    }
                },
                error: function() {
                    AutoFeaturedImageAdmin.showNotice('error', autoFeaturedImageAdmin.strings.error);
                },
                complete: function() {
                    $form.removeClass('auto-featured-image-loading');
                }
            });
        },

        /**
         * Handle refresh actions
         */
        handleRefresh: function(e) {
            e.preventDefault();
            AutoFeaturedImageAdmin.refreshData();
        },

        /**
         * Handle confirmation dialogs
         */
        handleConfirmAction: function(e) {
            var message = $(this).data('confirm') || autoFeaturedImageAdmin.strings.confirm;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Make AJAX request
         */
        ajaxRequest: function(actionType, data) {
            var requestData = $.extend({
                action: 'auto_featured_image_ajax',
                action_type: actionType,
                nonce: autoFeaturedImageAdmin.nonce
            }, data || {});

            return $.post(autoFeaturedImageAdmin.ajaxUrl, requestData);
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.auto-featured-image-admin .notice').remove();
            
            // Add new notice
            $('.auto-featured-image-admin .wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Refresh dashboard data
         */
        refreshData: function() {
            // Refresh queue status
            this.updateQueueStatus();
            
            // Refresh statistics
            this.updateStatistics();
            
            // Refresh charts if present
            this.updateCharts();
        },

        /**
         * Update queue status
         */
        updateQueueStatus: function() {
            this.ajaxRequest('get_queue_status')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageAdmin.updateStatusDisplay(response.data);
                    }
                });
        },

        /**
         * Update status display
         */
        updateStatusDisplay: function(data) {
            // Update status indicators
            $('.auto-featured-image-status').each(function() {
                var $status = $(this);
                var statusType = data.status || 'idle';
                
                $status.removeClass('status-active status-idle status-error status-warning')
                       .addClass('status-' + statusType);
                
                $status.find('.status-text').text(statusType.charAt(0).toUpperCase() + statusType.slice(1));
            });
            
            // Update metrics
            if (data.metrics) {
                $.each(data.metrics, function(key, value) {
                    $('.card-metric[data-metric="' + key + '"]').text(value);
                });
            }
            
            // Update progress bars
            if (data.progress) {
                this.updateProgressBars(data.progress);
            }
        },

        /**
         * Update statistics
         */
        updateStatistics: function() {
            this.ajaxRequest('get_statistics')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageAdmin.updateStatisticsDisplay(response.data);
                    }
                });
        },

        /**
         * Update statistics display
         */
        updateStatisticsDisplay: function(data) {
            $.each(data, function(key, value) {
                var $element = $('[data-statistic="' + key + '"]');
                if ($element.length) {
                    $element.text(value);
                }
            });
        },

        /**
         * Initialize progress bars
         */
        initProgressBars: function() {
            $('.auto-featured-image-progress').each(function() {
                var $progress = $(this);
                var $bar = $progress.find('.auto-featured-image-progress-bar');
                var $text = $progress.find('.auto-featured-image-progress-text');
                var percentage = $progress.data('percentage') || 0;
                
                $bar.css('width', percentage + '%');
                $text.text(percentage + '%');
            });
        },

        /**
         * Update progress bars
         */
        updateProgressBars: function(progressData) {
            $.each(progressData, function(key, data) {
                var $progress = $('.auto-featured-image-progress[data-progress="' + key + '"]');
                var $bar = $progress.find('.auto-featured-image-progress-bar');
                var $text = $progress.find('.auto-featured-image-progress-text');
                
                var percentage = Math.round((data.completed / data.total) * 100);
                
                $bar.animate({ width: percentage + '%' }, 300);
                $text.text(percentage + '%');
            });
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            if ($.fn.tooltip) {
                $('[data-tooltip]').tooltip({
                    content: function() {
                        return $(this).data('tooltip');
                    }
                });
            }
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.auto-featured-image-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            // Chart initialization will be implemented in dashboard task
            if (typeof Chart !== 'undefined') {
                this.initDashboardCharts();
            }
        },

        /**
         * Initialize dashboard charts
         */
        initDashboardCharts: function() {
            // Implementation will be added in dashboard task
        },

        /**
         * Update charts
         */
        updateCharts: function() {
            // Chart update logic will be implemented in dashboard task
        },

        /**
         * Initialize logs interface
         */
        initLogsInterface: function() {
            if (typeof AutoFeaturedImageLogs !== 'undefined') {
                AutoFeaturedImageLogs.init();
            }
        },

        /**
         * Start polling for real-time updates
         */
        startPolling: function() {
            // Only poll on dashboard and queue monitor pages
            if (['dashboard', 'queue-monitor'].indexOf(autoFeaturedImageAdmin.currentPage) !== -1) {
                setInterval(function() {
                    AutoFeaturedImageAdmin.updateQueueStatus();
                }, 30000); // Poll every 30 seconds
            }
        },

        /**
         * Utility: Format numbers
         */
        formatNumber: function(num) {
            // Handle undefined, null, or non-numeric values
            if (num === undefined || num === null || isNaN(num)) {
                return '0';
            }

            // Convert to number if it's a string
            if (typeof num === 'string') {
                num = parseFloat(num);
                if (isNaN(num)) {
                    return '0';
                }
            }

            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

        /**
         * Utility: Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * Utility: Format duration
         */
        formatDuration: function(seconds) {
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = Math.floor(seconds % 60);
            
            if (hours > 0) {
                return hours + 'h ' + minutes + 'm ' + secs + 's';
            } else if (minutes > 0) {
                return minutes + 'm ' + secs + 's';
            } else {
                return secs + 's';
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        AutoFeaturedImageAdmin.init();
    });

})(jQuery);
