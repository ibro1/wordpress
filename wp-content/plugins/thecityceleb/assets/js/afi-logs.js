/**
 * Auto Featured Image - Logs JavaScript
 * 
 * Handles the logs interface functionality including filtering, pagination,
 * and log management operations.
 */

(function($) {
    'use strict';
    
    // Global variables
    let currentPage = 1;
    let currentFilters = {};
    let isLoading = false;
    
    // Initialize when document is ready
    $(document).ready(function() {
        initLogsInterface();
        loadLogs();
        loadLogStats();
    });
    
    /**
     * Initialize the logs interface
     */
    function initLogsInterface() {
        // Filter button
        $('#afi-filter-logs').on('click', function() {
            currentPage = 1;
            loadLogs();
        });
        
        // Clear filters button
        $('#afi-clear-log-filters').on('click', function() {
            clearFilters();
            currentPage = 1;
            loadLogs();
        });
        
        // Refresh button
        $('#afi-refresh-logs').on('click', function() {
            loadLogs();
            loadLogStats();
        });
        
        // Export button
        $('#afi-export-logs').on('click', function() {
            exportLogs();
        });
        
        // Clear logs button
        $('#afi-clear-logs').on('click', function() {
            showClearLogsModal();
        });
        
        // Pagination buttons
        $('#afi-logs-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadLogs();
            }
        });
        
        $('#afi-logs-next-page').on('click', function() {
            currentPage++;
            loadLogs();
        });
        
        // Search on Enter key
        $('#afi-log-search').on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                loadLogs();
            }
        });
        
        // Modal close handlers
        $('.afi-close-modal, .afi-modal-overlay').on('click', function() {
            closeModals();
        });
        
        // Confirm clear logs
        $('#afi-confirm-clear-logs').on('click', function() {
            clearLogs();
        });
        
        // Auto-refresh logs every 30 seconds
        setInterval(function() {
            if (!isLoading) {
                loadLogs(true); // Silent refresh
            }
        }, 30000);
    }
    
    /**
     * Load logs with current filters and pagination
     */
    function loadLogs(silent = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        if (!silent) {
            showLoading();
        }
        
        // Get current filters
        currentFilters = {
            level: $('#afi-log-level-filter').val(),
            job_id: $('#afi-log-job-filter').val(),
            date_from: $('#afi-log-date-from').val(),
            date_to: $('#afi-log-date-to').val(),
            search: $('#afi-log-search').val(),
            limit: 50,
            offset: (currentPage - 1) * 50,
            order: 'DESC'
        };
        
        // Remove empty filters
        Object.keys(currentFilters).forEach(key => {
            if (currentFilters[key] === '' || currentFilters[key] === null) {
                delete currentFilters[key];
            }
        });
        
        // Add nonce
        currentFilters.nonce = afiAjax.nonce;
        currentFilters.action = 'afi_get_logs';
        
        $.ajax({
            url: afiAjax.ajaxUrl,
            type: 'POST',
            data: currentFilters,
            success: function(response) {
                if (response.success) {
                    displayLogs(response.data.logs);
                    updatePagination(response.data.pagination, response.data.total_logs);
                    updateResultsInfo(response.data.total_logs);
                } else {
                    showError(response.data.message || 'Failed to load logs.');
                }
            },
            error: function() {
                showError('Network error occurred while loading logs.');
            },
            complete: function() {
                isLoading = false;
                hideLoading();
            }
        });
    }
    
    /**
     * Load log statistics
     */
    function loadLogStats() {
        $.ajax({
            url: afiAjax.ajaxUrl,
            type: 'POST',
            data: {
                action: 'afi_get_log_stats',
                nonce: afiAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayLogStats(response.data);
                }
            }
        });
    }
    
    /**
     * Display logs in the table
     */
    function displayLogs(logs) {
        const tbody = $('#afi-logs-table-body');
        tbody.empty();
        
        if (logs.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="6" class="afi-no-results">
                        ${afiAjax.strings.noLogsFound || 'No logs found.'}
                    </td>
                </tr>
            `);
            return;
        }
        
        logs.forEach(function(log) {
            const levelClass = `afi-log-level-${log.level}`;
            const levelBadge = `<span class="afi-log-level-badge ${levelClass}">${log.level.toUpperCase()}</span>`;
            
            const row = $(`
                <tr data-log-id="${log.id}">
                    <td class="column-level">${levelBadge}</td>
                    <td class="column-message">
                        <div class="afi-log-message-preview">${escapeHtml(log.message)}</div>
                    </td>
                    <td class="column-job-id">${log.job_id || '-'}</td>
                    <td class="column-user">${log.user_name || '-'}</td>
                    <td class="column-created">${log.created_at}</td>
                    <td class="column-actions">
                        <button type="button" class="button button-small afi-view-log-details" 
                                data-log-id="${log.id}" title="View Details">
                            <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                        </button>
                    </td>
                </tr>
            `);
            
            tbody.append(row);
        });
        
        // Attach click handlers for view details buttons
        $('.afi-view-log-details').on('click', function() {
            const logId = $(this).data('log-id');
            const logData = logs.find(log => log.id == logId);
            if (logData) {
                showLogDetails(logData);
            }
        });
    }
    
    /**
     * Display log statistics
     */
    function displayLogStats(stats) {
        $('#afi-total-logs').text(stats.total.toLocaleString());
        $('#afi-error-logs').text(stats.error.toLocaleString());
        $('#afi-warning-logs').text(stats.warning.toLocaleString());
        $('#afi-info-logs').text(stats.info.toLocaleString());
        
        $('#afi-logs-stats').show();
    }
    
    /**
     * Update pagination controls
     */
    function updatePagination(pagination, totalLogs) {
        const totalPages = pagination.total_pages;
        
        // Update page info
        $('#afi-logs-page-info').text(`Page ${currentPage} of ${totalPages}`);
        
        // Update button states
        $('#afi-logs-prev-page').prop('disabled', currentPage <= 1);
        $('#afi-logs-next-page').prop('disabled', currentPage >= totalPages);
        
        // Show/hide pagination
        if (totalPages > 1) {
            $('#afi-logs-pagination').show();
        } else {
            $('#afi-logs-pagination').hide();
        }
    }
    
    /**
     * Update results info
     */
    function updateResultsInfo(totalLogs) {
        const start = ((currentPage - 1) * 50) + 1;
        const end = Math.min(currentPage * 50, totalLogs);
        
        if (totalLogs === 0) {
            $('#afi-logs-showing-results').text('No logs found.');
        } else {
            $('#afi-logs-showing-results').text(
                `Showing ${start.toLocaleString()} to ${end.toLocaleString()} of ${totalLogs.toLocaleString()} logs`
            );
        }
    }
    
    /**
     * Show log details modal
     */
    function showLogDetails(log) {
        // Populate modal with log data
        $('#afi-log-detail-level').removeClass().addClass(`afi-log-level-badge afi-log-level-${log.level}`).text(log.level.toUpperCase());
        $('#afi-log-detail-created').text(log.created_at);
        $('#afi-log-detail-job-id').text(log.job_id || '-');
        $('#afi-log-detail-post-id').text(log.post_id || '-');
        $('#afi-log-detail-user').text(log.user_name || '-');
        $('#afi-log-detail-ip').text(log.ip_address || '-');
        $('#afi-log-detail-message').text(log.message);
        
        // Show/hide context section
        if (log.context && Object.keys(log.context).length > 0) {
            $('#afi-log-detail-context').text(JSON.stringify(log.context, null, 2));
            $('#afi-log-context-section').show();
        } else {
            $('#afi-log-context-section').hide();
        }
        
        // Show/hide stack trace section
        if (log.stack_trace) {
            $('#afi-log-detail-stack-trace').text(log.stack_trace);
            $('#afi-log-stack-trace-section').show();
        } else {
            $('#afi-log-stack-trace-section').hide();
        }
        
        // Show/hide user agent section
        if (log.user_agent) {
            $('#afi-log-detail-user-agent').text(log.user_agent);
            $('#afi-log-user-agent-section').show();
        } else {
            $('#afi-log-user-agent-section').hide();
        }
        
        // Show modal
        $('#afi-log-modal').show().attr('aria-hidden', 'false');
        $('body').addClass('afi-modal-open');
    }
    
    /**
     * Show clear logs modal
     */
    function showClearLogsModal() {
        $('#afi-clear-logs-modal').show().attr('aria-hidden', 'false');
        $('body').addClass('afi-modal-open');
    }
    
    /**
     * Close all modals
     */
    function closeModals() {
        $('.afi-modal').hide().attr('aria-hidden', 'true');
        $('body').removeClass('afi-modal-open');
    }
    
    /**
     * Clear filters
     */
    function clearFilters() {
        $('#afi-log-level-filter').val('');
        $('#afi-log-job-filter').val('');
        $('#afi-log-date-from').val('');
        $('#afi-log-date-to').val('');
        $('#afi-log-search').val('');
    }
    
    /**
     * Export logs to CSV
     */
    function exportLogs() {
        const exportFilters = {
            level: $('#afi-log-level-filter').val(),
            job_id: $('#afi-log-job-filter').val(),
            date_from: $('#afi-log-date-from').val(),
            date_to: $('#afi-log-date-to').val(),
            nonce: afiAjax.nonce,
            action: 'afi_export_logs'
        };
        
        // Remove empty filters
        Object.keys(exportFilters).forEach(key => {
            if (exportFilters[key] === '' || exportFilters[key] === null) {
                delete exportFilters[key];
            }
        });
        
        $.ajax({
            url: afiAjax.ajaxUrl,
            type: 'POST',
            data: exportFilters,
            success: function(response) {
                if (response.success) {
                    // Create and trigger download
                    const blob = new Blob([response.data.csv_content], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showSuccess(response.data.message);
                } else {
                    showError(response.data.message || 'Failed to export logs.');
                }
            },
            error: function() {
                showError('Network error occurred while exporting logs.');
            }
        });
    }
    
    /**
     * Clear logs
     */
    function clearLogs() {
        const clearType = $('input[name="afi_clear_type"]:checked').val();
        const clearData = {
            action: 'afi_clear_logs',
            nonce: afiAjax.nonce,
            clear_type: clearType
        };
        
        if (clearType === 'old') {
            clearData.days = $('#afi-clear-days').val();
        } else if (clearType === 'by_level') {
            clearData.level = $('#afi-clear-level').val();
        }
        
        $.ajax({
            url: afiAjax.ajaxUrl,
            type: 'POST',
            data: clearData,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    closeModals();
                    currentPage = 1;
                    loadLogs();
                    loadLogStats();
                } else {
                    showError(response.data.message || 'Failed to clear logs.');
                }
            },
            error: function() {
                showError('Network error occurred while clearing logs.');
            }
        });
    }
    
    /**
     * Show loading state
     */
    function showLoading() {
        $('#afi-logs-table-body').html(`
            <tr>
                <td colspan="6" class="afi-loading">
                    <span class="spinner is-active"></span>
                    Loading logs...
                </td>
            </tr>
        `);
    }
    
    /**
     * Hide loading state
     */
    function hideLoading() {
        // Loading state is replaced by actual content or error message
    }
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        showNotice(message, 'success');
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        showNotice(message, 'error');
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
        
        // Manual dismiss
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        });
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
})(jQuery);