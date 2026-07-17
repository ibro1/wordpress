<?php
// Task 5.2: Logging and Debug System

class AFI_Logger {
    const LOG_OPTION_KEY = 'afi_logs';
    const MAX_LOG_ENTRIES = 200;

    public static function log($type, $message) {
        $logs = get_option(self::LOG_OPTION_KEY, array());
        
        $entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'message' => $message,
        );

        // Add the new log to the beginning of the array
        array_unshift($logs, $entry);

        // Trim the log to a maximum size
        if (count($logs) > self::MAX_LOG_ENTRIES) {
            $logs = array_slice($logs, 0, self::MAX_LOG_ENTRIES);
        }

        update_option(self::LOG_OPTION_KEY, $logs, false); // 'false' for no autoload
    }
    
    public static function get_logs() {
        return get_option(self::LOG_OPTION_KEY, array());
    }

    public static function clear_logs() {
        delete_option(self::LOG_OPTION_KEY);
    }
}