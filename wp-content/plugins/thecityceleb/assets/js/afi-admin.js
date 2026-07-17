/**
 * Admin JavaScript for Auto Featured Image plugin
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main admin object
     */
    var AFI_Admin = {
        
        // Progress monitoring state
        progressMonitors: {},
        
        /**
         * Initialize the admin interface
         */
        init: function() {
            this.bindEvents();
            this.loadDashboardStats();
            this.loadJobHistory();
            this.initFormValidation();
            this.loadPostTypeCounts();
            this.loadImageCounts();
            this.initProgressMonitoring();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Image selection radio buttons
            $('input[name="afi_image_selection"]').on('change', this.toggleFilterOptions);
            
            // Form submission
            $('#afi-new-job-form').on('submit', this.validateJobForm);
            
            // Job control buttons (will be added dynamically)
            $(document).on('click', '.afi-job-control', this.handleJobControl);
            
            // Job view buttons
            $(document).on('click', '.afi-job-view', this.showJobDetails);
            
            // Refresh buttons
            $(document).on('click', '.afi-refresh-stats', this.loadDashboardStats);
            $(document).on('click', '#afi-refresh-history', this.loadJobHistory);
            
            // Enhanced job history controls
            $(document).on('click', '#afi-search-jobs', this.searchJobHistory);
            $(document).on('click', '#afi-clear-search', this.clearJobSearch);
            $(document).on('change', '#afi-status-filter', this.filterJobHistory);
            $(document).on('click', '#afi-cleanup-jobs', this.cleanupOldJobs);
            $(document).on('click', '.afi-sort-link', this.sortJobHistory);
            $(document).on('click', '#afi-prev-page, #afi-next-page', this.navigateJobHistory);
            
            // Progress modal controls
            $(document).on('click', '.afi-close-modal', this.closeJobModal);
            $(document).on('click', '.afi-modal-overlay', this.closeJobModal);
            $(document).on('click', '.afi-tab-button', this.switchModalTab);
            
            // Results search
            $(document).on('click', '#afi-search-results', this.searchJobResults);
            $(document).on('click', '#afi-refresh-logs', this.refreshJobLogs);
            
            // Form field changes for dynamic updates
            $('input[name="afi_post_types[]"]').on('change', this.updateJobPreview);
            $('input[name="afi_image_selection"]').on('change', this.updateImageCounts);
            $('#afi_date_start, #afi_date_end, #afi_keyword').on('input change', this.debounce(this.updateFilteredImageCount, 500));
            
            // Enable/disable submit button based on form validity
            $('#afi-new-job-form input, #afi-new-job-form select').on('change input', this.updateSubmitButton);
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts);
        },
        
        /**
         * Toggle filter options based on image selection
         */
        toggleFilterOptions: function() {
            var selectedValue = $('input[name="afi_image_selection"]:checked').val();
            var filterOptions = $('.afi-filter-options');
            
            if (selectedValue === 'filtered') {
                filterOptions.show();
            } else {
                filterOptions.hide();
            }
        },
        
        /**
         * Load dashboard statistics
         */
        loadDashboardStats: function() {
            // Only load stats if we're on the dashboard tab
            if (!$('#afi-posts-without-images').length) {
                return;
            }
            
            // Show loading spinners
            $('#afi-posts-without-images, #afi-available-images, #afi-active-jobs').html('<span class="spinner is-active"></span>');
            
            // Make AJAX request for stats
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_dashboard_stats',
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#afi-posts-without-images').text(response.data.posts_without_images || '0');
                        $('#afi-available-images').text(response.data.available_images || '0');
                        $('#afi-active-jobs').text(response.data.active_jobs || '0');
                    } else {
                        AFI_Admin.showError('Failed to load dashboard statistics.');
                    }
                },
                error: function() {
                    AFI_Admin.showError('Failed to load dashboard statistics.');
                }
            });
        },
        
        /**
         * Load job history
         */
        loadJobHistory: function() {
            // Only load history if we're on the history tab
            if (!$('#afi-history-table-body').length) {
                return;
            }
            
            AFI_Admin.loadJobHistoryWithFilters();
        },
        
        /**
         * Load job history with filters
         */
        loadJobHistoryWithFilters: function(filters) {
            filters = filters || {};
            
            // Show loading state
            $('#afi-history-table-body').html(
                '<tr><td colspan="7" class="afi-loading">' +
                '<span class="spinner is-active"></span>' +
                'Loading...' +
                '</td></tr>'
            );
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: $.extend({
                    action: 'afi_get_job_history',
                    nonce: afi_ajax.nonce
                }, filters),
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.renderEnhancedJobHistory(response.data);
                    } else {
                        AFI_Admin.showError('Failed to load job history.');
                    }
                },
                error: function() {
                    AFI_Admin.showError('Failed to load job history.');
                }
            });
        },
        
        /**
         * Render enhanced job history
         */
        renderEnhancedJobHistory: function(data) {
            var tbody = $('#afi-history-table-body');
            tbody.empty();
            
            if (!data.jobs || data.jobs.length === 0) {
                tbody.html(
                    '<tr><td colspan="7" class="afi-text-center afi-text-muted">' +
                    'No jobs found.' +
                    '</td></tr>'
                );
                return;
            }
            
            // Update pagination info
            if (data.pagination) {
                var pagination = data.pagination;
                $('#afi-showing-results').text(
                    'Showing ' + pagination.showing_start + '-' + pagination.showing_end + 
                    ' of ' + pagination.total_items + ' jobs'
                );
                
                if (pagination.total_pages > 1) {
                    $('#afi-table-pagination').show();
                    $('#afi-page-info').text('Page ' + pagination.page + ' of ' + pagination.total_pages);
                    $('#afi-prev-page').prop('disabled', pagination.page <= 1);
                    $('#afi-next-page').prop('disabled', pagination.page >= pagination.total_pages);
                } else {
                    $('#afi-table-pagination').hide();
                }
            }
            
            // Render job rows
            $.each(data.jobs, function(index, job) {
                var row = AFI_Admin.createEnhancedJobRow(job);
                tbody.append(row);
            });
        },
        
        /**
         * Create enhanced job row
         */
        createEnhancedJobRow: function(job) {
            var statusBadge = '<span class="afi-status-badge ' + job.status + '">' + job.status + '</span>';
            var progressBar = '<div class="afi-progress-bar">' +
                '<div class="afi-progress-fill" style="width: ' + job.progress_percentage + '%"></div>' +
                '<div class="afi-progress-text">' + job.processed_items + ' / ' + job.total_items + '</div>' +
                '</div>';
            
            var actions = AFI_Admin.createJobActions(job);
            
            return '<tr>' +
                '<td>' + job.id + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' + (job.post_types || []).join(', ') + '</td>' +
                '<td>' + progressBar + '</td>' +
                '<td>' + job.created_at + '</td>' +
                '<td>' + (job.duration || '-') + '</td>' +
                '<td>' + actions + '</td>' +
                '</tr>';
        },
        
        /**
         * Create job action buttons
         */
        createJobActions: function(job) {
            var actions = '<div class="afi-action-buttons">';
            
            // View button (always available)
            actions += '<a href="#" class="afi-action-button afi-job-view" data-job-id="' + job.id + '">View</a>';
            
            // Control buttons based on status
            if (job.status === 'running') {
                actions += '<button class="afi-action-button afi-job-control" data-job-id="' + job.id + '" data-action="pause">Pause</button>';
                actions += '<button class="afi-action-button danger afi-job-control" data-job-id="' + job.id + '" data-action="cancel">Cancel</button>';
            } else if (job.status === 'paused') {
                actions += '<button class="afi-action-button afi-job-control" data-job-id="' + job.id + '" data-action="resume">Resume</button>';
                actions += '<button class="afi-action-button danger afi-job-control" data-job-id="' + job.id + '" data-action="cancel">Cancel</button>';
            } else if (job.status === 'pending') {
                actions += '<button class="afi-action-button afi-job-control" data-job-id="' + job.id + '" data-action="start">Start</button>';
                actions += '<button class="afi-action-button danger afi-job-control" data-job-id="' + job.id + '" data-action="delete">Delete</button>';
            } else if (job.status === 'complete' || job.status === 'canceled' || job.status === 'failed') {
                actions += '<button class="afi-action-button danger afi-job-control" data-job-id="' + job.id + '" data-action="delete">Delete</button>';
            }
            
            actions += '</div>';
            return actions;
        },
        
        /**
         * Handle job control actions
         */
        handleJobControl: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var jobId = $button.data('job-id');
            var action = $button.data('action');
            
            // Confirm destructive actions
            if (action === 'cancel' && !confirm(afi_ajax.strings.confirm_cancel)) {
                return;
            }
            
            if (action === 'delete' && !confirm(afi_ajax.strings.confirm_delete)) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true).text(afi_ajax.strings.loading);
            
            // Make AJAX request
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_control_job',
                    job_id: jobId,
                    job_action: action,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload job history to reflect changes
                        AFI_Admin.loadJobHistory();
                        AFI_Admin.showSuccess(response.data.message || 'Action completed successfully.');
                    } else {
                        AFI_Admin.showError(response.data.message || 'Action failed.');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    AFI_Admin.showError('Action failed.');
                    $button.prop('disabled', false);
                },
                complete: function() {
                    // Reset button text will happen when history reloads
                }
            });
        },
        
        /**
         * Show job details modal
         */
        showJobDetails: function(e) {
            e.preventDefault();
            
            var jobId = $(this).data('job-id');
            if (!jobId) return;
            
            // Show modal
            $('#afi-job-modal').show();
            $('body').addClass('afi-modal-open');
            
            // Load job details
            AFI_Admin.loadJobDetails(jobId);
        },
        
        /**
         * Close job modal
         */
        closeJobModal: function(e) {
            if (e.target !== this) return;
            
            $('#afi-job-modal').hide();
            $('body').removeClass('afi-modal-open');
        },
        
        /**
         * Switch modal tab
         */
        switchModalTab: function(e) {
            e.preventDefault();
            
            var tabName = $(this).data('tab');
            
            // Update tab buttons
            $('.afi-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Update tab panels
            $('.afi-tab-panel').removeClass('active');
            $('#afi-tab-' + tabName).addClass('active');
            
            // Load tab content if needed
            var jobId = $('#afi-job-id').text();
            if (jobId && jobId !== '-') {
                switch (tabName) {
                    case 'results':
                        AFI_Admin.loadJobResults(jobId);
                        break;
                    case 'logs':
                        AFI_Admin.loadJobLogs(jobId);
                        break;
                }
            }
        },
        
        /**
         * Load job details
         */
        loadJobDetails: function(jobId) {
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_job_details',
                    job_id: jobId,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.populateJobDetails(response.data);
                    } else {
                        AFI_Admin.showModalError('Failed to load job details.');
                    }
                },
                error: function() {
                    AFI_Admin.showModalError('Failed to load job details.');
                }
            });
        },
        
        /**
         * Populate job details in modal
         */
        populateJobDetails: function(data) {
            var job = data.job;
            var stats = data.stats;
            
            // Overview tab
            $('#afi-job-id').text(job.id);
            $('#afi-job-status-text').removeClass().addClass('afi-status-badge ' + job.status).text(job.status);
            $('#afi-job-post-types').text(job.post_types.join(', '));
            $('#afi-job-filters').text(job.filters_display);
            $('#afi-job-created').text(job.created_at);
            $('#afi-job-duration').text(job.duration);
            
            // Summary stats
            $('#afi-summary-total').text(job.total_items.toLocaleString());
            $('#afi-summary-processed').text(job.processed_items.toLocaleString());
            $('#afi-summary-success').text((stats.complete || 0).toLocaleString());
            $('#afi-summary-failed').text((stats.failed || 0).toLocaleString());
            $('#afi-summary-rate').text(job.processing_rate + '/min');
            
            // Progress tab
            $('#afi-progress-total').text(job.total_items.toLocaleString());
            $('#afi-progress-processed').text(job.processed_items.toLocaleString());
            $('#afi-progress-percentage').text(job.progress_percentage + '%');
            $('#afi-progress-rate').text(job.processing_rate + '/min');
            $('#afi-progress-eta').text(job.time_remaining);
            
            // Progress bar
            $('#afi-progress-fill').css('width', job.progress_percentage + '%');
            $('#afi-progress-text').text(job.processed_items + ' / ' + job.total_items);
            
            // Control buttons
            AFI_Admin.updateJobControlButtons(job);
        },
        
        /**
         * Update job control buttons
         */
        updateJobControlButtons: function(job) {
            var $container = $('#afi-job-control-buttons');
            $container.empty();
            
            var buttons = '';
            
            if (job.status === 'running') {
                buttons += '<button class="button afi-job-control" data-job-id="' + job.id + '" data-action="pause">Pause</button>';
                buttons += '<button class="button button-secondary afi-job-control" data-job-id="' + job.id + '" data-action="cancel">Cancel</button>';
            } else if (job.status === 'paused') {
                buttons += '<button class="button button-primary afi-job-control" data-job-id="' + job.id + '" data-action="resume">Resume</button>';
                buttons += '<button class="button button-secondary afi-job-control" data-job-id="' + job.id + '" data-action="cancel">Cancel</button>';
            } else if (job.status === 'pending') {
                buttons += '<button class="button button-primary afi-job-control" data-job-id="' + job.id + '" data-action="start">Start</button>';
                buttons += '<button class="button button-secondary afi-job-control" data-job-id="' + job.id + '" data-action="delete">Delete</button>';
            } else if (job.status === 'complete' || job.status === 'canceled' || job.status === 'failed') {
                buttons += '<button class="button button-secondary afi-job-control" data-job-id="' + job.id + '" data-action="delete">Delete</button>';
            }
            
            $container.html(buttons);
        },
        
        /**
         * Load job results
         */
        loadJobResults: function(jobId, page, search) {
            page = page || 1;
            search = search || '';
            
            var $container = $('.afi-results-content');
            $container.html('<div class="afi-results-loading"><span class="spinner is-active"></span><p>Loading results...</p></div>');
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_scan_results',
                    job_id: jobId,
                    page: page,
                    per_page: 20,
                    search: search,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.renderJobResults(response.data);
                    } else {
                        $container.html('<div class="afi-no-results"><p>Failed to load results.</p></div>');
                    }
                },
                error: function() {
                    $container.html('<div class="afi-no-results"><p>Failed to load results.</p></div>');
                }
            });
        },
        
        /**
         * Render job results
         */
        renderJobResults: function(data) {
            var $container = $('.afi-results-content');
            
            if (!data.items || data.items.length === 0) {
                $container.html('<div class="afi-no-results"><p>No results found.</p></div>');
                return;
            }
            
            var html = '<div class="afi-results-table-container">';
            html += '<table>';
            html += '<thead>';
            html += '<tr>';
            html += '<th>Post</th>';
            html += '<th>Status</th>';
            html += '<th>Assigned Image</th>';
            html += '<th>Message</th>';
            html += '<th>Processed</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';
            
            $.each(data.items, function(index, item) {
                html += '<tr>';
                html += '<td>';
                if (item.post_url) {
                    html += '<a href="' + item.post_url + '" target="_blank">' + item.post_title + '</a>';
                } else {
                    html += item.post_title;
                }
                html += '</td>';
                html += '<td><span class="afi-status-' + item.status + '">' + item.status + '</span></td>';
                html += '<td>';
                if (item.assigned_image_url) {
                    html += '<img src="' + item.assigned_image_url + '" class="afi-result-thumbnail" alt="Assigned image">';
                } else {
                    html += '<span class="afi-no-image">No image</span>';
                }
                html += '</td>';
                html += '<td>' + (item.log_message || '-') + '</td>';
                html += '<td>' + (item.processed_at || '-') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            
            // Add pagination if needed
            if (data.total_pages > 1) {
                html += AFI_Admin.createPagination(data.page, data.total_pages);
            }
            
            $container.html(html);
        },
        
        /**
         * Load job logs
         */
        loadJobLogs: function(jobId) {
            var $container = $('.afi-logs-content');
            $container.html('<div class="afi-logs-loading"><span class="spinner is-active"></span><p>Loading logs...</p></div>');
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_job_logs',
                    job_id: jobId,
                    limit: 100,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.renderJobLogs(response.data.logs);
                    } else {
                        $container.html('<div class="afi-no-logs"><p>Failed to load logs.</p></div>');
                    }
                },
                error: function() {
                    $container.html('<div class="afi-no-logs"><p>Failed to load logs.</p></div>');
                }
            });
        },
        
        /**
         * Render job logs
         */
        renderJobLogs: function(logs) {
            var $container = $('.afi-logs-content');
            
            if (!logs || logs.length === 0) {
                $container.html('<div class="afi-no-logs"><p>No logs available.</p></div>');
                return;
            }
            
            var html = '<div class="afi-logs-list">';
            
            $.each(logs, function(index, log) {
                var logClass = '';
                if (log.status === 'complete') {
                    logClass = 'afi-log-success';
                } else if (log.status === 'failed') {
                    logClass = 'afi-log-error';
                }
                
                html += '<div class="afi-log-entry ' + logClass + '">';
                html += '<div class="afi-log-time">' + log.processed_at + '</div>';
                html += '<div class="afi-log-message">' + (log.log_message || 'No message') + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            $container.html(html);
        },
        
        /**
         * Create pagination HTML
         */
        createPagination: function(currentPage, totalPages) {
            var html = '<div class="afi-pagination">';
            
            // Previous button
            if (currentPage > 1) {
                html += '<button class="afi-page-btn" data-page="' + (currentPage - 1) + '">Previous</button>';
            }
            
            // Page numbers
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            for (var i = startPage; i <= endPage; i++) {
                var activeClass = i === currentPage ? ' active' : '';
                html += '<button class="afi-page-btn' + activeClass + '" data-page="' + i + '">' + i + '</button>';
            }
            
            // Next button
            if (currentPage < totalPages) {
                html += '<button class="afi-page-btn" data-page="' + (currentPage + 1) + '">Next</button>';
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Show modal error
         */
        showModalError: function(message) {
            $('.afi-modal-content').html('<div class="afi-modal-error"><p>' + message + '</p></div>');
        },
        
        /**
         * Search job history
         */
        searchJobHistory: function(e) {
            e.preventDefault();
            var searchTerm = $('#afi-history-search').val();
            AFI_Admin.loadJobHistoryWithFilters({ search: searchTerm });
            
            if (searchTerm) {
                $('#afi-clear-search').show();
            }
        },
        
        /**
         * Clear job search
         */
        clearJobSearch: function(e) {
            e.preventDefault();
            $('#afi-history-search').val('');
            $('#afi-clear-search').hide();
            AFI_Admin.loadJobHistoryWithFilters({ search: '' });
        },
        
        /**
         * Filter job history by status
         */
        filterJobHistory: function() {
            var status = $(this).val();
            AFI_Admin.loadJobHistoryWithFilters({ status: status });
        },
        
        /**
         * Sort job history
         */
        sortJobHistory: function(e) {
            e.preventDefault();
            
            var $th = $(this).closest('th');
            var sortBy = $th.data('sort');
            var currentOrder = $th.hasClass('asc') ? 'asc' : 'desc';
            var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Update sort indicators
            $('.sortable').removeClass('asc desc');
            $th.addClass(newOrder);
            
            AFI_Admin.loadJobHistoryWithFilters({ 
                sort_by: sortBy, 
                sort_order: newOrder 
            });
        },
        
        /**
         * Navigate job history pagination
         */
        navigateJobHistory: function(e) {
            e.preventDefault();
            
            var direction = $(this).attr('id') === 'afi-prev-page' ? -1 : 1;
            var currentPage = parseInt($('#afi-page-info').text().match(/\d+/)[0]) || 1;
            var newPage = currentPage + direction;
            
            AFI_Admin.loadJobHistoryWithFilters({ page: newPage });
        },
        
        /**
         * Cleanup old jobs
         */
        cleanupOldJobs: function(e) {
            e.preventDefault();
            
            var days = prompt('Delete jobs older than how many days? (default: 90)', '90');
            if (!days || isNaN(days)) return;
            
            if (!confirm('Are you sure you want to delete jobs older than ' + days + ' days? This cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_cleanup_old_jobs',
                    days: parseInt(days),
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.showSuccess(response.data.message);
                        AFI_Admin.loadJobHistory();
                    } else {
                        AFI_Admin.showError(response.data.message || 'Cleanup failed.');
                    }
                },
                error: function() {
                    AFI_Admin.showError('Cleanup failed.');
                }
            });
        },
        
        /**
         * Search job results
         */
        searchJobResults: function(e) {
            e.preventDefault();
            var search = $('#afi-results-search').val();
            var jobId = $('#afi-job-id').text();
            AFI_Admin.loadJobResults(jobId, 1, search);
        },
        
        /**
         * Refresh job logs
         */
        refreshJobLogs: function(e) {
            e.preventDefault();
            var jobId = $('#afi-job-id').text();
            AFI_Admin.loadJobLogs(jobId);
        },
        
        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function(e) {
            // ESC key closes modal
            if (e.keyCode === 27 && $('#afi-job-modal').is(':visible')) {
                AFI_Admin.closeJobModal.call($('#afi-job-modal')[0], e);
            }
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Real-time validation for post types
            $('input[name="afi_post_types[]"]').on('change', function() {
                var checkedBoxes = $('input[name="afi_post_types[]"]:checked');
                var errorElement = $('.afi-post-types-error');
                
                if (checkedBoxes.length === 0) {
                    if (errorElement.length === 0) {
                        $('input[name="afi_post_types[]"]').first().closest('td').append(
                            '<span class="afi-error-message afi-post-types-error">Please select at least one post type.</span>'
                        );
                    }
                } else {
                    errorElement.remove();
                }
            });
            
            // Date range validation
            $('#afi_date_start, #afi_date_end').on('change', function() {
                var startDate = $('#afi_date_start').val();
                var endDate = $('#afi_date_end').val();
                var errorElement = $('.afi-date-range-error');
                
                errorElement.remove();
                
                if (startDate && endDate && startDate > endDate) {
                    $('#afi_date_end').after(
                        '<span class="afi-error-message afi-date-range-error">End date must be after start date.</span>'
                    );
                }
            });
            
            // Check initial form state
            this.updateSubmitButton();
        },
        
        /**
         * Validate job creation form
         */
        validateJobForm: function(e) {
            var isValid = true;
            
            // Clear previous errors
            $('.afi-error-message').remove();
            $('.afi-field-error').removeClass('afi-field-error');
            
            // Validate post types
            var checkedBoxes = $('input[name="afi_post_types[]"]:checked');
            if (checkedBoxes.length === 0) {
                $('input[name="afi_post_types[]"]').first().addClass('afi-field-error');
                $('input[name="afi_post_types[]"]').first().closest('td').append(
                    '<span class="afi-error-message">Please select at least one post type.</span>'
                );
                isValid = false;
            }
            
            // Validate date range if filtered images are selected
            if ($('input[name="afi_image_selection"]:checked').val() === 'filtered') {
                var startDate = $('#afi_date_start').val();
                var endDate = $('#afi_date_end').val();
                
                if (startDate && endDate && startDate > endDate) {
                    $('#afi_date_end').addClass('afi-field-error').after(
                        '<span class="afi-error-message">End date must be after start date.</span>'
                    );
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                var firstError = $('.afi-field-error').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
            }
            
            return isValid;
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            AFI_Admin.showNotice(message, 'success');
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            AFI_Admin.showNotice(message, 'error');
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            var noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            var notice = '<div class="notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>';
            
            // Remove existing notices
            $('.afi-tab-content .notice').remove();
            
            // Add new notice
            $('.afi-tab-content').prepend(notice);
            
            // Make notice dismissible
            $('.notice-dismiss').on('click', function() {
                $(this).closest('.notice').fadeOut();
            });
            
            // Auto-dismiss success notices after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $('.notice-success').fadeOut();
                }, 5000);
            }
        },
        
        /**
         * Load post type counts for job creation form
         */
        loadPostTypeCounts: function() {
            // Only load if we're on the new job tab
            if (!$('.afi-post-type-count').length) {
                return;
            }
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_post_type_counts',
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $.each(response.data, function(postType, count) {
                            $('.afi-post-type-count[data-post-type="' + postType + '"]').html('(' + count + ')');
                        });
                    } else {
                        $('.afi-post-type-count').html('(?)');
                    }
                },
                error: function() {
                    $('.afi-post-type-count').html('(?)');
                }
            });
        },
        
        /**
         * Load image counts for job creation form
         */
        loadImageCounts: function() {
            // Only load if we're on the new job tab
            if (!$('#afi-all-images-count').length) {
                return;
            }
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_image_counts',
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#afi-all-images-count').html('(' + response.data.all_images + ' images)');
                        $('#afi-filtered-images-count .count').text(response.data.filtered_images);
                    } else {
                        $('#afi-all-images-count').html('(?)');
                    }
                },
                error: function() {
                    $('#afi-all-images-count').html('(?)');
                }
            });
        },
        
        /**
         * Update image counts when selection changes
         */
        updateImageCounts: function() {
            AFI_Admin.toggleFilterOptions();
            
            var selectedValue = $('input[name="afi_image_selection"]:checked').val();
            
            if (selectedValue === 'filtered') {
                $('#afi-filtered-images-count').show();
                AFI_Admin.updateFilteredImageCount();
            } else {
                $('#afi-filtered-images-count').hide();
            }
            
            AFI_Admin.updateJobPreview();
        },
        
        /**
         * Update filtered image count based on current filters
         */
        updateFilteredImageCount: function() {
            // Only update if filtered option is selected
            if ($('input[name="afi_image_selection"]:checked').val() !== 'filtered') {
                return;
            }
            
            var dateStart = $('#afi_date_start').val();
            var dateEnd = $('#afi_date_end').val();
            var keyword = $('#afi_keyword').val();
            
            // Show loading
            $('#afi-filtered-images-count .count').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>');
            
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_image_counts',
                    date_start: dateStart,
                    date_end: dateEnd,
                    keyword: keyword,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#afi-filtered-images-count .count').text(response.data.filtered_images);
                        AFI_Admin.updateJobPreview();
                    } else {
                        $('#afi-filtered-images-count .count').text('?');
                    }
                },
                error: function() {
                    $('#afi-filtered-images-count .count').text('?');
                }
            });
        },
        
        /**
         * Update job preview based on current form values
         */
        updateJobPreview: function() {
            var selectedPostTypes = $('input[name="afi_post_types[]"]:checked');
            var selectedImageSelection = $('input[name="afi_image_selection"]:checked').val();
            
            if (selectedPostTypes.length === 0) {
                $('#afi-job-preview').hide();
                return;
            }
            
            // Calculate total posts to process
            var totalPosts = 0;
            selectedPostTypes.each(function() {
                var postType = $(this).val();
                var countText = $('.afi-post-type-count[data-post-type="' + postType + '"]').text();
                var count = parseInt(countText.replace(/[^\d]/g, '')) || 0;
                totalPosts += count;
            });
            
            // Get available images count
            var availableImages = 0;
            if (selectedImageSelection === 'all') {
                var allImagesText = $('#afi-all-images-count').text();
                availableImages = parseInt(allImagesText.replace(/[^\d]/g, '')) || 0;
            } else {
                var filteredImagesText = $('#afi-filtered-images-count .count').text();
                availableImages = parseInt(filteredImagesText.replace(/[^\d]/g, '')) || 0;
            }
            
            // Update preview
            $('#afi-preview-posts').text(totalPosts.toLocaleString());
            $('#afi-preview-images').text(availableImages.toLocaleString());
            
            // Calculate estimated time (rough estimate: 1 post per second)
            var estimatedSeconds = totalPosts;
            var estimatedTime = AFI_Admin.formatDuration(estimatedSeconds);
            $('#afi-preview-time').text(estimatedTime);
            
            // Show preview if we have posts to process
            if (totalPosts > 0) {
                $('#afi-job-preview').show();
            } else {
                $('#afi-job-preview').hide();
            }
            
            AFI_Admin.updateSubmitButton();
        },
        
        /**
         * Update submit button state based on form validity
         */
        updateSubmitButton: function() {
            var $submitBtn = $('#afi-create-job-btn');
            var selectedPostTypes = $('input[name="afi_post_types[]"]:checked');
            var selectedImageSelection = $('input[name="afi_image_selection"]:checked').val();
            
            var isValid = true;
            var statusMessage = '';
            
            // Check if post types are selected
            if (selectedPostTypes.length === 0) {
                isValid = false;
                statusMessage = 'Please select at least one post type.';
            }
            
            // Check if filtered images have filters when selected
            if (isValid && selectedImageSelection === 'filtered') {
                var dateStart = $('#afi_date_start').val();
                var dateEnd = $('#afi_date_end').val();
                var keyword = $('#afi_keyword').val();
                
                if (!dateStart && !dateEnd && !keyword) {
                    isValid = false;
                    statusMessage = 'Please specify at least one filter when using filtered image selection.';
                }
                
                // Check date range validity
                if (isValid && dateStart && dateEnd && dateStart > dateEnd) {
                    isValid = false;
                    statusMessage = 'End date must be after start date.';
                }
                
                // Check keyword length
                if (isValid && keyword && (keyword.length < 2 || keyword.length > 100)) {
                    isValid = false;
                    statusMessage = 'Keyword must be 2-100 characters long.';
                }
            }
            
            // Update button and status
            $submitBtn.prop('disabled', !isValid);
            $('#afi-form-status').text(statusMessage).toggleClass('afi-error-message', !isValid);
        },
        
        /**
         * Format duration in seconds to human readable format
         */
        formatDuration: function(seconds) {
            if (seconds < 60) {
                return seconds + ' seconds';
            } else if (seconds < 3600) {
                var minutes = Math.ceil(seconds / 60);
                return minutes + ' minute' + (minutes !== 1 ? 's' : '');
            } else if (seconds < 86400) {
                var hours = Math.ceil(seconds / 3600);
                return hours + ' hour' + (hours !== 1 ? 's' : '');
            } else {
                var days = Math.ceil(seconds / 86400);
                return days + ' day' + (days !== 1 ? 's' : '');
            }
        },
        
        /**
         * Initialize progress monitoring
         */
        initProgressMonitoring: function() {
            // Start monitoring active jobs on page load
            this.startMonitoringActiveJobs();
        },
        
        /**
         * Start monitoring active jobs
         */
        startMonitoringActiveJobs: function() {
            // Find all running or scanning jobs in the history table
            $('.afi-status-badge.running, .afi-status-badge.scanning').each(function() {
                var $row = $(this).closest('tr');
                var jobId = $row.find('.afi-job-view').data('job-id');
                if (jobId) {
                    AFI_Admin.startProgressMonitoring(jobId);
                }
            });
        },
        
        /**
         * Start progress monitoring for a specific job
         */
        startProgressMonitoring: function(jobId) {
            // Don't start if already monitoring
            if (AFI_Admin.progressMonitors[jobId]) {
                return;
            }
            
            // Start polling every 5 seconds
            AFI_Admin.progressMonitors[jobId] = setInterval(function() {
                AFI_Admin.updateJobProgress(jobId);
            }, 5000);
            
            // Initial update
            AFI_Admin.updateJobProgress(jobId);
        },
        
        /**
         * Stop progress monitoring for a specific job
         */
        stopProgressMonitoring: function(jobId) {
            if (AFI_Admin.progressMonitors[jobId]) {
                clearInterval(AFI_Admin.progressMonitors[jobId]);
                delete AFI_Admin.progressMonitors[jobId];
            }
        },
        
        /**
         * Update job progress via AJAX
         */
        updateJobProgress: function(jobId) {
            $.ajax({
                url: afi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'afi_get_job_progress',
                    job_id: jobId,
                    nonce: afi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AFI_Admin.updateJobProgressDisplay(response.data);
                        
                        // Stop monitoring if job is complete
                        if (response.data.status === 'complete' || 
                            response.data.status === 'canceled' || 
                            response.data.status === 'failed') {
                            AFI_Admin.stopProgressMonitoring(jobId);
                            AFI_Admin.loadJobHistory(); // Refresh the table
                        }
                    }
                },
                error: function() {
                    // Stop monitoring on error
                    AFI_Admin.stopProgressMonitoring(jobId);
                }
            });
        },
        
        /**
         * Update job progress display in the table
         */
        updateJobProgressDisplay: function(jobData) {
            var $row = $('.afi-job-view[data-job-id="' + jobData.job_id + '"]').closest('tr');
            if (!$row.length) return;
            
            // Update status badge
            var $statusBadge = $row.find('.afi-status-badge');
            $statusBadge.removeClass().addClass('afi-status-badge ' + jobData.status).text(jobData.status);
            
            // Update progress bar
            var $progressBar = $row.find('.afi-progress-bar');
            var $progressFill = $progressBar.find('.afi-progress-fill');
            var $progressText = $progressBar.find('.afi-progress-text');
            
            $progressFill.css('width', jobData.progress_percentage + '%');
            $progressText.text(jobData.processed_items + ' / ' + jobData.total_items);
            
            // Update action buttons
            var $actionsCell = $row.find('.afi-action-buttons');
            var newActions = AFI_Admin.createJobActions(jobData);
            $actionsCell.html($(newActions).html());
        },
        
        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AFI_Admin.init();
    });

})(jQuery);