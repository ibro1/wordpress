/**
 * Auto Featured Image Logs JavaScript
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Logs Object
     */
    window.AutoFeaturedImageLogs = {
        
        currentPage: 1,
        totalPages: 1,
        currentFilters: {},
        charts: {},
        
        /**
         * Initialize logs interface
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.loadLogs();
            this.loadErrorSummary();
            this.loadAnalytics();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Filter form
            $('#log-filters-form').on('submit', this.handleFilterSubmit.bind(this));
            $('#clear-filters-btn').on('click', this.clearFilters.bind(this));
            
            // Log controls
            $('#refresh-logs-btn').on('click', this.refreshLogs.bind(this));
            $('#clear-logs-btn').on('click', this.clearLogs.bind(this));
            $('#export-logs-btn').on('click', this.exportLogs.bind(this));
            
            // Pagination
            $(document).on('click', '.log-pagination .page-link', this.handlePagination.bind(this));
            
            // Logs per page
            $('#logs-per-page').on('change', this.handlePerPageChange.bind(this));
            
            // Log detail modal
            $(document).on('click', '.view-log-detail', this.showLogDetail.bind(this));
            $(document).on('click', '.modal-close', this.closeModal.bind(this));
            
            // Tab switching
            $(document).on('click', '.auto-featured-image-nav-tabs .nav-tab', this.handleTabSwitch.bind(this));
        },

        /**
         * Handle filter form submission
         */
        handleFilterSubmit: function(e) {
            e.preventDefault();
            
            this.currentFilters = {
                level: $('#log-level-filter').val(),
                date_from: $('#log-date-from').val(),
                date_to: $('#log-date-to').val(),
                search: $('#log-search').val()
            };
            
            this.currentPage = 1;
            this.loadLogs();
        },

        /**
         * Clear filters
         */
        clearFilters: function() {
            $('#log-filters-form')[0].reset();
            this.currentFilters = {};
            this.currentPage = 1;
            this.loadLogs();
        },

        /**
         * Handle pagination
         */
        handlePagination: function(e) {
            e.preventDefault();
            
            var page = parseInt($(e.currentTarget).data('page'));
            if (page && page !== this.currentPage) {
                this.currentPage = page;
                this.loadLogs();
            }
        },

        /**
         * Handle per page change
         */
        handlePerPageChange: function() {
            this.currentPage = 1;
            this.loadLogs();
        },

        /**
         * Handle tab switching
         */
        handleTabSwitch: function(e) {
            var target = $(e.currentTarget).attr('href').split('#')[1];
            
            switch (target) {
                case 'errors':
                    this.loadErrorSummary();
                    break;
                case 'analytics':
                    this.loadAnalytics();
                    break;
            }
        },

        /**
         * Load logs
         */
        loadLogs: function() {
            var data = $.extend({}, this.currentFilters, {
                page: this.currentPage,
                per_page: $('#logs-per-page').val() || 50
            });
            
            $('#logs-table-body').html('<tr><td colspan="5" class="auto-featured-image-text-center">Loading logs...</td></tr>');
            
            AutoFeaturedImageAdmin.ajaxRequest('get_logs_paginated', data)
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageLogs.displayLogs(response.data);
                    }
                })
                .fail(function() {
                    $('#logs-table-body').html('<tr><td colspan="5" class="auto-featured-image-text-center">Failed to load logs.</td></tr>');
                });
        },

        /**
         * Display logs
         */
        displayLogs: function(data) {
            var $tbody = $('#logs-table-body');
            $tbody.empty();
            
            if (data.logs.length === 0) {
                $tbody.html('<tr><td colspan="5" class="auto-featured-image-text-center">No logs found.</td></tr>');
                return;
            }
            
            $.each(data.logs, function(index, log) {
                var levelClass = 'log-level-' + log.level;
                var contextPreview = log.context ? JSON.stringify(log.context).substring(0, 50) + '...' : '-';
                
                var row = '<tr class="' + levelClass + '">' +
                    '<td>' + log.created_at + '</td>' +
                    '<td><span class="log-level-badge level-' + log.level + '">' + log.level.toUpperCase() + '</span></td>' +
                    '<td class="log-message">' + log.message + '</td>' +
                    '<td>' + contextPreview + '</td>' +
                    '<td>' +
                    '<button type="button" class="auto-featured-image-button button-secondary view-log-detail" data-log-id="' + log.id + '">' +
                    'View Details' +
                    '</button>' +
                    '</td>' +
                    '</tr>';
                
                $tbody.append(row);
            });
            
            // Update pagination
            this.updatePagination(data.pagination);
            
            // Update log count
            $('#log-count').text('(' + data.pagination.total + ')');
        },

        /**
         * Update pagination
         */
        updatePagination: function(pagination) {
            this.totalPages = pagination.total_pages;
            this.currentPage = pagination.current_page;
            
            var $pagination = $('#log-pagination');
            $pagination.empty();
            
            if (this.totalPages <= 1) {
                return;
            }
            
            var html = '<div class="pagination-links">';
            
            // Previous button
            if (this.currentPage > 1) {
                html += '<a href="#" class="page-link" data-page="' + (this.currentPage - 1) + '">&laquo; Previous</a>';
            }
            
            // Page numbers
            var startPage = Math.max(1, this.currentPage - 2);
            var endPage = Math.min(this.totalPages, this.currentPage + 2);
            
            for (var i = startPage; i <= endPage; i++) {
                var activeClass = i === this.currentPage ? ' current' : '';
                html += '<a href="#" class="page-link' + activeClass + '" data-page="' + i + '">' + i + '</a>';
            }
            
            // Next button
            if (this.currentPage < this.totalPages) {
                html += '<a href="#" class="page-link" data-page="' + (this.currentPage + 1) + '">Next &raquo;</a>';
            }
            
            html += '</div>';
            html += '<div class="pagination-info">Page ' + this.currentPage + ' of ' + this.totalPages + '</div>';
            
            $pagination.html(html);
        },

        /**
         * Show log detail modal
         */
        showLogDetail: function(e) {
            var logId = $(e.currentTarget).data('log-id');
            
            AutoFeaturedImageAdmin.ajaxRequest('get_log_detail', { log_id: logId })
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageLogs.displayLogDetail(response.data);
                        $('#log-detail-modal').show();
                    }
                });
        },

        /**
         * Display log detail
         */
        displayLogDetail: function(log) {
            var html = '<div class="log-detail">' +
                '<div class="log-detail-row"><strong>ID:</strong> ' + log.id + '</div>' +
                '<div class="log-detail-row"><strong>Timestamp:</strong> ' + log.created_at + '</div>' +
                '<div class="log-detail-row"><strong>Level:</strong> <span class="log-level-badge level-' + log.level + '">' + log.level.toUpperCase() + '</span></div>' +
                '<div class="log-detail-row"><strong>Message:</strong> ' + log.message + '</div>';
            
            if (log.context) {
                html += '<div class="log-detail-row"><strong>Context:</strong><pre>' + JSON.stringify(log.context, null, 2) + '</pre></div>';
            }
            
            if (log.post_id) {
                html += '<div class="log-detail-row"><strong>Post ID:</strong> ' + log.post_id + '</div>';
            }
            
            html += '</div>';
            
            $('#log-detail-content').html(html);
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.auto-featured-image-modal').hide();
        },

        /**
         * Refresh logs
         */
        refreshLogs: function() {
            this.loadLogs();
        },

        /**
         * Clear logs
         */
        clearLogs: function() {
            var level = this.currentFilters.level || 'all';
            
            AutoFeaturedImageAdmin.ajaxRequest('clear_logs', { level: level })
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('success', response.message);
                    AutoFeaturedImageLogs.loadLogs();
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to clear logs.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                });
        },

        /**
         * Export logs
         */
        exportLogs: function() {
            var data = $.extend({}, this.currentFilters, {
                format: 'csv'
            });
            
            // Create download link
            var params = $.param(data);
            var url = autoFeaturedImageAdmin.ajaxUrl + '?action=auto_featured_image_export_logs&' + params + '&nonce=' + autoFeaturedImageAdmin.nonce;
            
            var link = document.createElement('a');
            link.href = url;
            link.download = 'auto-featured-image-logs.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                return;
            }
            
            this.initErrorTypesChart();
            this.initActivityTimelineChart();
        },

        /**
         * Initialize error types chart
         */
        initErrorTypesChart: function() {
            var ctx = document.getElementById('error-types-chart');
            if (!ctx) return;
            
            this.charts.errorTypes = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#d63638', '#dba617', '#72aee6', '#00a32a']
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
         * Initialize activity timeline chart
         */
        initActivityTimelineChart: function() {
            var ctx = document.getElementById('activity-timeline-chart');
            if (!ctx) return;
            
            this.charts.activityTimeline = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Processing Activity',
                        data: [],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
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
                    }
                }
            });
        },

        /**
         * Load error summary
         */
        loadErrorSummary: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_error_summary')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageLogs.displayErrorSummary(response.data);
                    }
                });
        },

        /**
         * Display error summary
         */
        displayErrorSummary: function(data) {
            $('#total-errors').text(data.total_errors || 0);
            $('#error-rate').text((data.error_rate || 0) + '%');
            $('#last-error-time').text(data.last_error_time || '-');
            
            // Update error types chart
            if (this.charts.errorTypes && data.error_types) {
                this.charts.errorTypes.data.labels = data.error_types.labels;
                this.charts.errorTypes.data.datasets[0].data = data.error_types.values;
                this.charts.errorTypes.update();
            }
        },

        /**
         * Load analytics
         */
        loadAnalytics: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_log_analytics')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageLogs.displayAnalytics(response.data);
                    }
                });
        },

        /**
         * Display analytics
         */
        displayAnalytics: function(data) {
            $('#processing-volume').text(data.processing_volume || 0);
            $('#avg-performance').text(data.avg_performance || '0s');
            $('#analytics-success-rate').text((data.success_rate || 0) + '%');
            
            // Update activity timeline chart
            if (this.charts.activityTimeline && data.activity_timeline) {
                this.charts.activityTimeline.data.labels = data.activity_timeline.labels;
                this.charts.activityTimeline.data.datasets[0].data = data.activity_timeline.values;
                this.charts.activityTimeline.update();
            }
            
            // Update algorithm performance table
            if (data.algorithm_performance) {
                this.displayAlgorithmPerformance(data.algorithm_performance);
            }
        },

        /**
         * Display algorithm performance
         */
        displayAlgorithmPerformance: function(algorithms) {
            var $tbody = $('#algorithm-performance-body');
            $tbody.empty();
            
            if (algorithms.length === 0) {
                $tbody.html('<tr><td colspan="5" class="auto-featured-image-text-center">No algorithm performance data available.</td></tr>');
                return;
            }
            
            $.each(algorithms, function(index, algorithm) {
                var performanceClass = algorithm.success_rate >= 80 ? 'success' : 
                                     algorithm.success_rate >= 60 ? 'warning' : 'error';
                
                var row = '<tr>' +
                    '<td>' + algorithm.name + '</td>' +
                    '<td>' + AutoFeaturedImageAdmin.formatNumber(algorithm.usage_count) + '</td>' +
                    '<td><span class="performance-indicator ' + performanceClass + '">' + algorithm.success_rate + '%</span></td>' +
                    '<td>' + algorithm.avg_score + '</td>' +
                    '<td>' + algorithm.performance_rating + '</td>' +
                    '</tr>';
                
                $tbody.append(row);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#tab-recent').length && autoFeaturedImageAdmin.currentPage === 'logs') {
            AutoFeaturedImageLogs.init();
        }
    });

})(jQuery);
