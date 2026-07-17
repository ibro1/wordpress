<?php
/**
 * Mock Objects for Testing
 *
 * Provides mock objects and stubs for testing.
 *
 * @package AutoFeaturedImage
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Mock WordPress Post Object
 */
class Mock_WP_Post {
    public $ID;
    public $post_title;
    public $post_content;
    public $post_status;
    public $post_type;
    public $post_author;
    public $post_date;
    public $post_modified;

    public function __construct( $data = array() ) {
        $defaults = array(
            'ID' => 1,
            'post_title' => 'Mock Post',
            'post_content' => 'Mock post content',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1,
            'post_date' => current_time( 'mysql' ),
            'post_modified' => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        foreach ( $data as $key => $value ) {
            $this->$key = $value;
        }
    }
}

/**
 * Mock Image Analyzer
 */
class Mock_Image_Analyzer {
    private $mock_results = array();

    public function set_mock_result( $image_id, $result ) {
        $this->mock_results[ $image_id ] = $result;
    }

    public function analyze_image( $image_id ) {
        if ( isset( $this->mock_results[ $image_id ] ) ) {
            return $this->mock_results[ $image_id ];
        }

        return array(
            'quality_score' => 75,
            'dimensions' => array( 'width' => 800, 'height' => 600 ),
            'file_size' => 150000,
            'colors' => array( '#ffffff', '#000000' ),
            'faces_detected' => 0,
            'text_detected' => false,
        );
    }
}

/**
 * Mock Algorithm
 */
class Mock_Algorithm {
    private $mock_scores = array();
    private $default_score = 50;

    public function set_mock_score( $post_id, $image_id, $score ) {
        $this->mock_scores[ $post_id ][ $image_id ] = $score;
    }

    public function set_default_score( $score ) {
        $this->default_score = $score;
    }

    public function calculate_score( $post_id, $image_id, $context = array() ) {
        if ( isset( $this->mock_scores[ $post_id ][ $image_id ] ) ) {
            return $this->mock_scores[ $post_id ][ $image_id ];
        }

        return $this->default_score;
    }

    public function get_name() {
        return 'mock_algorithm';
    }

    public function get_description() {
        return 'Mock algorithm for testing';
    }
}

/**
 * Mock HTTP Response
 */
class Mock_HTTP_Response {
    public static function success( $body = '', $code = 200 ) {
        return array(
            'response' => array( 'code' => $code ),
            'body' => $body,
        );
    }

    public static function error( $message = 'Mock error', $code = 500 ) {
        return new WP_Error( 'mock_error', $message, array( 'status' => $code ) );
    }
}

/**
 * Mock Database
 */
class Mock_Database {
    private $data = array(
        'jobs' => array(),
        'logs' => array(),
        'progress' => array(),
    );

    public function insert_job( $post_id, $status = 'pending', $priority = 10 ) {
        $job_id = count( $this->data['jobs'] ) + 1;
        $this->data['jobs'][ $job_id ] = array(
            'id' => $job_id,
            'post_id' => $post_id,
            'status' => $status,
            'priority' => $priority,
            'created_at' => current_time( 'mysql' ),
        );
        return $job_id;
    }

    public function get_job_by_id( $job_id ) {
        return $this->data['jobs'][ $job_id ] ?? null;
    }

    public function get_job_by_post_id( $post_id ) {
        foreach ( $this->data['jobs'] as $job ) {
            if ( $job['post_id'] == $post_id ) {
                return $job;
            }
        }
        return null;
    }

    public function update_job_status( $job_id, $status ) {
        if ( isset( $this->data['jobs'][ $job_id ] ) ) {
            $this->data['jobs'][ $job_id ]['status'] = $status;
            return true;
        }
        return false;
    }

    public function insert_log( $level, $message, $context = array(), $post_id = null ) {
        $log_id = count( $this->data['logs'] ) + 1;
        $this->data['logs'][ $log_id ] = array(
            'id' => $log_id,
            'level' => $level,
            'message' => $message,
            'context' => wp_json_encode( $context ),
            'post_id' => $post_id,
            'created_at' => current_time( 'mysql' ),
        );
        return $log_id;
    }

    public function get_logs( $args = array() ) {
        $logs = $this->data['logs'];

        if ( isset( $args['level'] ) ) {
            $logs = array_filter( $logs, function( $log ) use ( $args ) {
                return $log['level'] === $args['level'];
            } );
        }

        if ( isset( $args['limit'] ) ) {
            $logs = array_slice( $logs, 0, $args['limit'] );
        }

        return array_values( $logs );
    }

    public function clear_data() {
        $this->data = array(
            'jobs' => array(),
            'logs' => array(),
            'progress' => array(),
        );
    }

    public function get_data() {
        return $this->data;
    }
}

/**
 * Mock Queue
 */
class Mock_Queue {
    private $jobs = array();
    private $processing = false;

    public function add_job( $post_id, $priority = 10 ) {
        $this->jobs[] = array(
            'post_id' => $post_id,
            'priority' => $priority,
            'status' => 'pending',
            'added_at' => time(),
        );
        return true;
    }

    public function get_next_job() {
        foreach ( $this->jobs as $index => $job ) {
            if ( $job['status'] === 'pending' ) {
                $this->jobs[ $index ]['status'] = 'processing';
                return $job;
            }
        }
        return null;
    }

    public function complete_job( $post_id ) {
        foreach ( $this->jobs as $index => $job ) {
            if ( $job['post_id'] == $post_id ) {
                $this->jobs[ $index ]['status'] = 'completed';
                return true;
            }
        }
        return false;
    }

    public function fail_job( $post_id ) {
        foreach ( $this->jobs as $index => $job ) {
            if ( $job['post_id'] == $post_id ) {
                $this->jobs[ $index ]['status'] = 'failed';
                return true;
            }
        }
        return false;
    }

    public function get_queue_stats() {
        $stats = array(
            'total' => count( $this->jobs ),
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
        );

        foreach ( $this->jobs as $job ) {
            $stats[ $job['status'] ]++;
        }

        return $stats;
    }

    public function clear_queue() {
        $this->jobs = array();
    }

    public function is_processing() {
        return $this->processing;
    }

    public function set_processing( $processing ) {
        $this->processing = $processing;
    }
}

/**
 * Mock Logger
 */
class Mock_Logger {
    private $logs = array();

    public function log( $level, $message, $context = array(), $post_id = null ) {
        $this->logs[] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'post_id' => $post_id,
            'timestamp' => time(),
        );
    }

    public function debug( $message, $context = array(), $post_id = null ) {
        $this->log( 'debug', $message, $context, $post_id );
    }

    public function info( $message, $context = array(), $post_id = null ) {
        $this->log( 'info', $message, $context, $post_id );
    }

    public function warning( $message, $context = array(), $post_id = null ) {
        $this->log( 'warning', $message, $context, $post_id );
    }

    public function error( $message, $context = array(), $post_id = null ) {
        $this->log( 'error', $message, $context, $post_id );
    }

    public function critical( $message, $context = array(), $post_id = null ) {
        $this->log( 'critical', $message, $context, $post_id );
    }

    public function get_logs( $level = null ) {
        if ( $level === null ) {
            return $this->logs;
        }

        return array_filter( $this->logs, function( $log ) use ( $level ) {
            return $log['level'] === $level;
        } );
    }

    public function clear_logs() {
        $this->logs = array();
    }

    public function has_log( $level, $message_contains = null ) {
        foreach ( $this->logs as $log ) {
            if ( $log['level'] === $level ) {
                if ( $message_contains === null || strpos( $log['message'], $message_contains ) !== false ) {
                    return true;
                }
            }
        }
        return false;
    }
}

/**
 * Mock Performance Monitor
 */
class Mock_Performance_Monitor {
    private $metrics = array();
    private $start_time;
    private $start_memory;

    public function start_monitoring() {
        $this->start_time = microtime( true );
        $this->start_memory = memory_get_usage( true );
    }

    public function end_monitoring() {
        $end_time = microtime( true );
        $end_memory = memory_get_usage( true );

        $this->metrics = array(
            'execution_time' => $end_time - $this->start_time,
            'memory_used' => $end_memory - $this->start_memory,
            'peak_memory' => memory_get_peak_usage( true ),
        );

        return $this->metrics;
    }

    public function get_metrics() {
        return $this->metrics;
    }

    public function reset_metrics() {
        $this->metrics = array();
        $this->start_time = null;
        $this->start_memory = null;
    }
}
