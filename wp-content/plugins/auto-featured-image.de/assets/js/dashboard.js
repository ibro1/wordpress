/**
 * Auto Featured Image Dashboard JavaScript
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Dashboard Object
     */
    window.AutoFeaturedImageDashboard = {
        
        charts: {},
        updateInterval: null,
        
        /**
         * Initialize dashboard
         */
        init: function() {
            this.initCharts();
            this.startRealTimeUpdates();
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Refresh button
            $(document).on('click', '.auto-featured-image-refresh', this.refreshData.bind(this));
            
            // Tab switching
            $(document).on('click', '.auto-featured-image-nav-tabs .nav-tab', this.handleTabSwitch.bind(this));
            
            // Action buttons
            $(document).on('click', '.auto-featured-image-action-button', this.handleActionButton.bind(this));
        },

        /**
         * Handle tab switching
         */
        handleTabSwitch: function(e) {
            e.preventDefault();
            
            var $tab = $(e.currentTarget);
            var target = $tab.attr('href').split('#')[1];
            
            // Update active tab
            $tab.siblings().removeClass('nav-tab-active');
            $tab.addClass('nav-tab-active');
            
            // Show/hide content
            $('.auto-featured-image-tab-content').hide();
            $('#tab-' + target).show();
            
            // Load tab-specific data
            this.loadTabData(target);
        },

        /**
         * Handle action buttons
         */
        handleActionButton: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var action = $button.data('action');
            
            $button.prop('disabled', true).addClass('auto-featured-image-loading');
            
            AutoFeaturedImageAdmin.ajaxRequest(action)
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('success', response.message);
                    AutoFeaturedImageDashboard.refreshData();
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
         * Initialize charts
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            // Processing Activity Chart
            this.initProcessingChart();
            
            // Algorithm Performance Chart
            this.initAlgorithmChart();
            
            // Performance Trends Chart
            this.initPerformanceChart();
        },

        /**
         * Initialize processing activity chart
         */
        initProcessingChart: function() {
            var ctx = document.getElementById('processing-chart');
            if (!ctx) return;

            this.charts.processing = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jobs Completed',
                        data: [],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Jobs Failed',
                        data: [],
                        borderColor: '#d63638',
                        backgroundColor: 'rgba(214, 54, 56, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        },

        /**
         * Initialize algorithm performance chart
         */
        initAlgorithmChart: function() {
            var ctx = document.getElementById('algorithm-chart');
            if (!ctx) return;

            this.charts.algorithm = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2271b1',
                            '#72aee6',
                            '#00a32a',
                            '#dba617',
                            '#d63638'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Initialize performance trends chart
         */
        initPerformanceChart: function() {
            var ctx = document.getElementById('performance-chart');
            if (!ctx) return;

            this.charts.performance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Execution Time (s)',
                        data: [],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        yAxisID: 'y'
                    }, {
                        label: 'Memory Usage (MB)',
                        data: [],
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Execution Time (s)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Memory Usage (MB)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        },

        /**
         * Start real-time updates
         */
        startRealTimeUpdates: function() {
            // Update every 30 seconds
            this.updateInterval = setInterval(function() {
                AutoFeaturedImageDashboard.refreshData();
            }, 30000);
        },

        /**
         * Stop real-time updates
         */
        stopRealTimeUpdates: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        },

        /**
         * Refresh all dashboard data
         */
        refreshData: function() {
            this.updateQueueStatus();
            this.updateStatistics();
            this.updateRecentActivity();
            this.updateTimestamp();
        },

        /**
         * Load initial data
         */
        loadInitialData: function() {
            this.refreshData();
            this.loadTabData('overview');
        },

        /**
         * Load tab-specific data
         */
        loadTabData: function(tab) {
            switch (tab) {
                case 'statistics':
                    this.loadStatisticsData();
                    break;
                case 'performance':
                    this.loadPerformanceData();
                    break;
            }
        },

        /**
         * Update queue status
         */
        updateQueueStatus: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_queue_status')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageDashboard.updateStatusDisplay(response.data);
                    }
                });
        },

        /**
         * Update status display
         */
        updateStatusDisplay: function(data) {
            // Update status indicators
            $('#queue-status')
                .removeClass('status-active status-idle status-error status-paused')
                .addClass('status-' + data.status)
                .find('.status-text')
                .text(data.status.charAt(0).toUpperCase() + data.status.slice(1));

            // Update metrics
            if (data.metrics) {
                $.each(data.metrics, function(key, value) {
                    $('[data-metric="' + key + '"]').text(value);
                });
            }

            // Update system health
            var healthStatus = data.metrics && data.metrics.error_rate > 10 ? 'warning' : 'active';
            $('#system-health')
                .removeClass('status-active status-warning status-error')
                .addClass('status-' + healthStatus);
        },

        /**
         * Update statistics
         */
        updateStatistics: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_statistics')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageDashboard.updateStatisticsDisplay(response.data);
                    }
                });
        },

        /**
         * Update statistics display
         */
        updateStatisticsDisplay: function(data) {
            // Update detailed statistics table
            if (data.detailed) {
                $.each(data.detailed, function(key, value) {
                    $('[data-statistic="' + key + '"]').text(value);
                });
            }
        },

        /**
         * Update recent activity
         */
        updateRecentActivity: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_recent_activity')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageDashboard.updateRecentActivityDisplay(response.data);
                    }
                });
        },

        /**
         * Update recent activity display
         */
        updateRecentActivityDisplay: function(activities) {
            var $list = $('#recent-activity-list');
            $list.empty();

            if (activities.length === 0) {
                $list.html('<p class="auto-featured-image-text-center">' + 
                          autoFeaturedImageAdmin.strings.no_recent_activity + '</p>');
                return;
            }

            var html = '<ul style="margin: 0; padding: 0; list-style: none;">';
            $.each(activities, function(index, activity) {
                html += '<li style="padding: 8px 0; border-bottom: 1px solid #f0f0f1;">';
                html += '<strong>' + activity.title + '</strong><br>';
                html += '<small style="color: #646970;">' + activity.time + '</small>';
                html += '</li>';
            });
            html += '</ul>';

            $list.html(html);
        },

        /**
         * Load statistics data
         */
        loadStatisticsData: function() {
            // Load chart data
            this.loadProcessingChartData();
            this.loadAlgorithmChartData();
        },

        /**
         * Load processing chart data
         */
        loadProcessingChartData: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_processing_chart_data')
                .done(function(response) {
                    if (response.success && response.data && AutoFeaturedImageDashboard.charts.processing) {
                        var chart = AutoFeaturedImageDashboard.charts.processing;
                        chart.data.labels = response.data.labels;
                        chart.data.datasets[0].data = response.data.completed;
                        chart.data.datasets[1].data = response.data.failed;
                        chart.update();
                    }
                });
        },

        /**
         * Load algorithm chart data
         */
        loadAlgorithmChartData: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_algorithm_chart_data')
                .done(function(response) {
                    if (response.success && response.data && AutoFeaturedImageDashboard.charts.algorithm) {
                        var chart = AutoFeaturedImageDashboard.charts.algorithm;
                        chart.data.labels = response.data.labels;
                        chart.data.datasets[0].data = response.data.values;
                        chart.update();
                    }
                });
        },

        /**
         * Load performance data
         */
        loadPerformanceData: function() {
            this.loadPerformanceChartData();
        },

        /**
         * Load performance chart data
         */
        loadPerformanceChartData: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_performance_chart_data')
                .done(function(response) {
                    if (response.success && response.data && AutoFeaturedImageDashboard.charts.performance) {
                        var chart = AutoFeaturedImageDashboard.charts.performance;
                        chart.data.labels = response.data.labels;
                        chart.data.datasets[0].data = response.data.execution_time;
                        chart.data.datasets[1].data = response.data.memory_usage;
                        chart.update();
                    }
                });
        },

        /**
         * Update timestamp
         */
        updateTimestamp: function() {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            $('.timestamp').text(timeString);
        },

        /**
         * Destroy dashboard
         */
        destroy: function() {
            this.stopRealTimeUpdates();
            
            // Destroy charts
            $.each(this.charts, function(key, chart) {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            
            this.charts = {};
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.auto-featured-image-admin').length && autoFeaturedImageAdmin.currentPage === 'dashboard') {
            AutoFeaturedImageDashboard.init();
        }
    });

    // Cleanup when leaving page
    $(window).on('beforeunload', function() {
        if (window.AutoFeaturedImageDashboard) {
            AutoFeaturedImageDashboard.destroy();
        }
    });

})(jQuery);
