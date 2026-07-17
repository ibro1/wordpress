<?php
// Task 2.3: Job Queue Management System

class AFI_Job_Manager {

    const BATCH_PROCESSING_HOOK = 'afi_process_batch';
    const BATCH_GROUP = 'auto-featured-image';

    /**
     * Schedule the recurring batch processing.
     * Task 2.4: Batch Processing Logic
     */
    public static function schedule_batch_processing() {
        // First, cancel any existing jobs to prevent duplicates
        self::cancel_all_jobs();

        $options = get_option('afi_settings');
        $interval = !empty($options['interval']) ? intval($options['interval']) : 5;

        // Schedule the first run immediately
        as_enqueue_async_action(self::BATCH_PROCESSING_HOOK, array(), self::BATCH_GROUP);

        AFI_Logger::log('System', 'Batch processing started.');
    }

    /**
     * Cancel all scheduled jobs for this plugin.
     */
    public static function cancel_all_jobs() {
        as_unschedule_all_actions(self::BATCH_PROCESSING_HOOK, array(), self::BATCH_GROUP);
        AFI_Logger::log('System', 'All processing jobs have been cancelled.');
    }

    /**
     * Check if there are any pending actions.
     */
    public static function is_job_pending() {
        $pending_actions = as_get_scheduled_actions(array(
            'hook' => self::BATCH_PROCESSING_HOOK,
            'status' => ActionScheduler_Store::STATUS_PENDING,
            'group' => self::BATCH_GROUP,
        ));
        return !empty($pending_actions);
    }
}