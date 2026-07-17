/**
 * Auto Featured Image Bulk Processing JavaScript
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Bulk Processing Object
     */
    window.AutoFeaturedImageBulkProcess = {
        
        isProcessing: false,
        progressInterval: null,
        
        /**
         * Initialize bulk processing interface
         */
        init: function() {
            this.bindEvents();
            this.loadProcessingHistory();
            this.checkProcessingStatus();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission
            $('#bulk-processing-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Control buttons
            $('#start-processing-btn').on('click', this.startProcessing.bind(this));
            $('#pause-processing-btn').on('click', this.pauseProcessing.bind(this));
            $('#stop-processing-btn').on('click', this.stopProcessing.bind(this));
            
            // Estimation button
            $('#estimate-jobs-btn').on('click', this.estimateJobs.bind(this));
            
            // Post type checkboxes
            $('input[name="post_types[]"]').on('change', this.updatePostTypeCounts.bind(this));
            
            // Skip existing checkbox
            $('#bulk_skip_existing').on('change', this.updateEstimation.bind(this));
        },

        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            if (this.isProcessing) {
                AutoFeaturedImageAdmin.showNotice('warning', 'Processing is already in progress.');
                return;
            }
            
            this.startBulkProcessing();
        },

        /**
         * Start bulk processing
         */
        startBulkProcessing: function() {
            var formData = this.getFormData();
            
            if (!this.validateFormData(formData)) {
                return;
            }
            
            this.setProcessingState(true);
            
            AutoFeaturedImageAdmin.ajaxRequest('start_bulk_processing', formData)
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('success', response.message);
                    AutoFeaturedImageBulkProcess.startProgressTracking();
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to start bulk processing.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                    AutoFeaturedImageBulkProcess.setProcessingState(false);
                });
        },

        /**
         * Start processing (simple)
         */
        startProcessing: function() {
            this.setProcessingState(true);
            
            AutoFeaturedImageAdmin.ajaxRequest('start_processing')
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('success', response.message);
                    AutoFeaturedImageBulkProcess.startProgressTracking();
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to start processing.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                    AutoFeaturedImageBulkProcess.setProcessingState(false);
                });
        },

        /**
         * Pause processing
         */
        pauseProcessing: function() {
            AutoFeaturedImageAdmin.ajaxRequest('pause_processing')
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('info', response.message);
                    AutoFeaturedImageBulkProcess.setProcessingState(false);
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to pause processing.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                });
        },

        /**
         * Stop processing
         */
        stopProcessing: function() {
            if (!confirm('Are you sure you want to stop processing? This will cancel all pending jobs.')) {
                return;
            }
            
            AutoFeaturedImageAdmin.ajaxRequest('stop_processing')
                .done(function(response) {
                    AutoFeaturedImageAdmin.showNotice('info', response.message);
                    AutoFeaturedImageBulkProcess.setProcessingState(false);
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to stop processing.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                });
        },

        /**
         * Estimate jobs
         */
        estimateJobs: function() {
            var formData = this.getFormData();
            
            $('#estimate-jobs-btn').prop('disabled', true).addClass('auto-featured-image-loading');
            
            AutoFeaturedImageAdmin.ajaxRequest('estimate_bulk_jobs', formData)
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageBulkProcess.displayEstimation(response.data);
                    }
                })
                .fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.data 
                        ? xhr.responseJSON.data 
                        : 'Failed to estimate jobs.';
                    AutoFeaturedImageAdmin.showNotice('error', message);
                })
                .always(function() {
                    $('#estimate-jobs-btn').prop('disabled', false).removeClass('auto-featured-image-loading');
                });
        },

        /**
         * Get form data
         */
        getFormData: function() {
            var formData = {};
            
            // Post types
            formData.post_types = [];
            $('input[name="post_types[]"]:checked').each(function() {
                formData.post_types.push($(this).val());
            });
            
            // Other form fields
            formData.skip_existing = $('#bulk_skip_existing').is(':checked');
            formData.batch_size = parseInt($('#bulk_batch_size').val()) || 25;
            formData.priority = $('#bulk_priority').val();
            formData.date_from = $('#bulk_date_from').val();
            formData.date_to = $('#bulk_date_to').val();
            formData.specific_posts = $('#bulk_specific_posts').val();
            
            return formData;
        },

        /**
         * Validate form data
         */
        validateFormData: function(formData) {
            if (formData.post_types.length === 0) {
                AutoFeaturedImageAdmin.showNotice('error', 'Please select at least one post type.');
                return false;
            }
            
            if (formData.batch_size < 1 || formData.batch_size > 1000) {
                AutoFeaturedImageAdmin.showNotice('error', 'Batch size must be between 1 and 1000.');
                return false;
            }
            
            if (formData.date_from && formData.date_to && formData.date_from > formData.date_to) {
                AutoFeaturedImageAdmin.showNotice('error', 'From date cannot be later than to date.');
                return false;
            }
            
            return true;
        },

        /**
         * Set processing state
         */
        setProcessingState: function(isProcessing) {
            this.isProcessing = isProcessing;
            
            // Update status display
            var $status = $('#processing-status');
            if (isProcessing) {
                $status.removeClass('status-idle').addClass('status-active');
                $status.find('.status-text').text('Processing');
            } else {
                $status.removeClass('status-active').addClass('status-idle');
                $status.find('.status-text').text('Idle');
            }
            
            // Update button states
            $('#start-processing-btn, #start-bulk-processing-btn').prop('disabled', isProcessing);
            $('#pause-processing-btn, #stop-processing-btn').prop('disabled', !isProcessing);
            
            // Show/hide progress display
            if (isProcessing) {
                $('#progress-display').show();
            } else {
                $('#progress-display').hide();
                this.stopProgressTracking();
            }
        },

        /**
         * Start progress tracking
         */
        startProgressTracking: function() {
            this.updateProgress();
            
            // Update progress every 5 seconds
            this.progressInterval = setInterval(function() {
                AutoFeaturedImageBulkProcess.updateProgress();
            }, 5000);
        },

        /**
         * Stop progress tracking
         */
        stopProgressTracking: function() {
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
                this.progressInterval = null;
            }
        },

        /**
         * Update progress
         */
        updateProgress: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_processing_progress')
                .done(function(response) {
                    console.log('Progress response:', response);

                    if (response.success && response.data) {
                        AutoFeaturedImageBulkProcess.displayProgress(response.data);

                        // Check if processing is complete
                        if (response.data.status === 'completed' || response.data.status === 'idle') {
                            AutoFeaturedImageBulkProcess.setProcessingState(false);
                            AutoFeaturedImageBulkProcess.loadProcessingHistory();
                        }
                    } else {
                        console.error('Invalid progress response:', response);
                        // Display default progress to prevent errors
                        AutoFeaturedImageBulkProcess.displayProgress({});
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Progress request failed:', error);
                    // Display default progress to prevent errors
                    AutoFeaturedImageBulkProcess.displayProgress({});
                });
        },

        /**
         * Display progress
         */
        displayProgress: function(data) {
            // Debug logging
            console.log('displayProgress called with data:', data);

            // Ensure data object has default values
            data = data || {};
            var processed = data.processed || 0;
            var total = data.total || 0;
            var remaining = data.remaining || 0;
            var success = data.success || 0;
            var failed = data.failed || 0;

            // Debug logging for values
            console.log('Progress values:', { processed, total, remaining, success, failed });

            // Update progress bar
            var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
            $('.auto-featured-image-progress-bar').css('width', percentage + '%');
            $('.auto-featured-image-progress-text').text(percentage + '%');

            // Update counters with safety checks
            if (typeof AutoFeaturedImageAdmin !== 'undefined' && AutoFeaturedImageAdmin.formatNumber) {
                $('#processed-count').text(AutoFeaturedImageAdmin.formatNumber(processed));
                $('#remaining-count').text(AutoFeaturedImageAdmin.formatNumber(remaining));
                $('#success-count').text(AutoFeaturedImageAdmin.formatNumber(success));
                $('#failed-count').text(AutoFeaturedImageAdmin.formatNumber(failed));
            } else {
                // Fallback formatting
                $('#processed-count').text(processed.toString());
                $('#remaining-count').text(remaining.toString());
                $('#success-count').text(success.toString());
                $('#failed-count').text(failed.toString());
            }
            
            // Update current batch info
            $('#current-batch').text(data.current_batch || '-');
            $('#estimated-time').text(data.estimated_time || '-');
        },

        /**
         * Display estimation results
         */
        displayEstimation: function(data) {
            $('#estimated-total-posts').text(AutoFeaturedImageAdmin.formatNumber(data.total_posts));
            $('#estimated-processable-posts').text(AutoFeaturedImageAdmin.formatNumber(data.processable_posts));
            $('#estimated-batches').text(AutoFeaturedImageAdmin.formatNumber(data.estimated_batches));
            $('#estimated-duration').text(data.estimated_duration);
            
            // Update breakdown table
            var $table = $('#estimation-breakdown-table');
            $table.empty();
            
            if (data.breakdown) {
                $.each(data.breakdown, function(postType, stats) {
                    var row = '<tr>' +
                        '<td>' + stats.label + '</td>' +
                        '<td>' + AutoFeaturedImageAdmin.formatNumber(stats.total) + '</td>' +
                        '<td>' + AutoFeaturedImageAdmin.formatNumber(stats.without_featured) + '</td>' +
                        '<td>' + AutoFeaturedImageAdmin.formatNumber(stats.to_process) + '</td>' +
                        '</tr>';
                    $table.append(row);
                });
            }
            
            $('#estimation-results').show();
        },

        /**
         * Update post type counts
         */
        updatePostTypeCounts: function() {
            var selectedTypes = [];
            $('input[name="post_types[]"]:checked').each(function() {
                selectedTypes.push($(this).val());
            });
            
            if (selectedTypes.length > 0) {
                AutoFeaturedImageAdmin.ajaxRequest('get_post_type_counts', {
                    post_types: selectedTypes,
                    skip_existing: $('#bulk_skip_existing').is(':checked')
                })
                .done(function(response) {
                    if (response.success && response.data) {
                        $.each(response.data, function(postType, count) {
                            $('.post-type-count[data-post-type="' + postType + '"]')
                                .text('(' + AutoFeaturedImageAdmin.formatNumber(count) + ' posts)');
                        });
                    }
                });
            }
        },

        /**
         * Update estimation when settings change
         */
        updateEstimation: function() {
            if ($('#estimation-results').is(':visible')) {
                this.estimateJobs();
            }
        },

        /**
         * Check current processing status
         */
        checkProcessingStatus: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_queue_status')
                .done(function(response) {
                    if (response.success && response.data) {
                        var isProcessing = response.data.status === 'active';
                        AutoFeaturedImageBulkProcess.setProcessingState(isProcessing);
                        
                        if (isProcessing) {
                            AutoFeaturedImageBulkProcess.startProgressTracking();
                        }
                    }
                });
        },

        /**
         * Load processing history
         */
        loadProcessingHistory: function() {
            AutoFeaturedImageAdmin.ajaxRequest('get_processing_history')
                .done(function(response) {
                    if (response.success && response.data) {
                        AutoFeaturedImageBulkProcess.displayProcessingHistory(response.data);
                    }
                })
                .fail(function() {
                    $('#processing-history').html('<p class="auto-featured-image-text-center">Failed to load processing history.</p>');
                });
        },

        /**
         * Display processing history
         */
        displayProcessingHistory: function(history) {
            var $container = $('#processing-history');
            
            if (history.length === 0) {
                $container.html('<p class="auto-featured-image-text-center">No processing history available.</p>');
                return;
            }
            
            var html = '<table class="auto-featured-image-table">' +
                '<thead>' +
                '<tr>' +
                '<th>Started</th>' +
                '<th>Duration</th>' +
                '<th>Posts Processed</th>' +
                '<th>Success Rate</th>' +
                '<th>Status</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>';
            
            $.each(history, function(index, item) {
                html += '<tr>' +
                    '<td>' + item.started_at + '</td>' +
                    '<td>' + item.duration + '</td>' +
                    '<td>' + AutoFeaturedImageAdmin.formatNumber(item.posts_processed) + '</td>' +
                    '<td>' + item.success_rate + '%</td>' +
                    '<td><span class="auto-featured-image-status status-' + item.status + '">' + 
                    item.status.charAt(0).toUpperCase() + item.status.slice(1) + '</span></td>' +
                    '</tr>';
            });
            
            html += '</tbody></table>';
            $container.html(html);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.auto-featured-image-bulk-process').length) {
            AutoFeaturedImageBulkProcess.init();
        }
    });

    // Cleanup when leaving page
    $(window).on('beforeunload', function() {
        if (window.AutoFeaturedImageBulkProcess) {
            AutoFeaturedImageBulkProcess.stopProgressTracking();
        }
    });

})(jQuery);
