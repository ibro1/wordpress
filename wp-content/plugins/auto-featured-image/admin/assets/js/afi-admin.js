// Phase 3.3: Progress Monitoring Dashboard via AJAX
jQuery(document).ready(function($) {
    'use strict';

    var statusInterval;

    // --- Button Elements ---
    var $startButton = $('#afi-start-btn');
    var $pauseButton = $('#afi-pause-btn');
    var $cancelButton = $('#afi-cancel-btn');
    var $allButtons = $('#afi-controls-box button');

    // Function to update button states based on running status
    function updateButtonStates(isRunning) {
        $startButton.prop('disabled', isRunning);
        $pauseButton.prop('disabled', !isRunning);
        $cancelButton.prop('disabled', !isRunning);
    }

    // Function to update status from server
    function getStatus() {
        $.ajax({
            url: afi_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'afi_get_status',
                nonce: afi_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $statusBox = $('#afi-status-box');
                    var $logsContainer = $('.afi-logs-container');
                    
                    // Check if the status indicator is 'running'
                    var isRunning = $(response.data.status_html).find('.afi-status-indicator').hasClass('running');
                    
                    $statusBox.html(response.data.status_html);
                    $logsContainer.html(response.data.logs_html);

                    // Update buttons based on the new status
                    updateButtonStates(isRunning);
                }
            }
        });
    }

    // --- Initial State Setup ---
    updateButtonStates(afi_ajax_object.is_running);
    statusInterval = setInterval(getStatus, 5000);
    getStatus(); // Initial load

    // --- Button Click Handlers ---
    $startButton.on('click', function(e) {
        e.preventDefault();
        $allButtons.prop('disabled', true);
        $(this).text('Starting...');

        $.ajax({
            url: afi_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'afi_start_processing',
                nonce: afi_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    getStatus(); // Update status immediately
                } else {
                    alert('Error: ' + response.data.message);
                    updateButtonStates(false); // Re-enable buttons on error
                }
            },
            complete: function() {
                $startButton.text('Start / Resume Processing');
            }
        });
    });

    $pauseButton.on('click', function(e) {
        e.preventDefault();
        $allButtons.prop('disabled', true);
        $(this).text('Pausing...');

        $.ajax({
            url: afi_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'afi_pause_processing',
                nonce: afi_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    getStatus(); // Update status immediately
                } else {
                    alert('Error: ' + response.data.message);
                    updateButtonStates(true); // Re-enable buttons on error
                }
            },
            complete: function() {
                $pauseButton.text('Pause Processing');
            }
        });
    });
    
    $cancelButton.on('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to cancel all jobs and reset the progress? This cannot be undone.')) {
            return;
        }
        $allButtons.prop('disabled', true);
        $(this).text('Cancelling...');

        $.ajax({
            url: afi_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'afi_cancel_processing',
                nonce: afi_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                   getStatus(); // Refresh the status to show reset values
                } else {
                   alert('Error: ' + response.data.message);
                   updateButtonStates(true); // Re-enable buttons on error
                }
            },
            complete: function() {
                $cancelButton.text('Cancel & Reset');
            }
        });
    });

    // Use event delegation for the dynamically loaded clear button
    $('#afi-logs-box').on('click', '#afi-clear-logs-btn', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to clear all log entries?')) {
            return;
        }
        $.ajax({
            url: afi_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'afi_clear_logs',
                nonce: afi_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                   getStatus(); // Refresh the logs display
                }
            }
        });
    });
});