<?php
// Task 2.2: Post Processing Engine
// Task 2.4: Batch Processing Logic
// Task 4.1: Database Query Optimization
// Task 5.1: Comprehensive Error Handling System

class AFI_Processor {

    public static function process_batch() {
        $options = get_option('afi_settings');
        $status = get_option('afi_status');
        
        $status['running'] = true;
        update_option('afi_status', $status);

        $batch_size = isset($options['batch_size']) ? intval($options['batch_size']) : 100;
        $post_types = isset($options['post_types']) ? (array) $options['post_types'] : array('post');

        // Task 4.1: Highly optimized query to find posts without a featured image.
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => $batch_size,
            'fields'         => 'ids', // Performance: Only get IDs
            'meta_query'     => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'no_found_rows'          => true, // Performance: Skip pagination
            'update_post_meta_cache' => false, // Performance: We don't need post meta yet
            'update_post_term_cache' => false, // Performance: We don't need term meta
        );

        $post_ids = get_posts($args);

        if ( empty($post_ids) ) {
            // No more posts to process. We are done.
            self::finish_processing();
            return;
        }

        foreach ($post_ids as $post_id) {
            try {
                self::process_single_post($post_id, $options);
            } catch (Exception $e) {
                // Task 5.2: Logging and Debug System
                AFI_Logger::log('Error', "Failed to process Post ID {$post_id}: " . $e->getMessage());
            }
        }
        
        $status = get_option('afi_status');
        $status['processed'] += count($post_ids);
        $status['last_run'] = current_time('mysql');
        update_option('afi_status', $status);

        // Schedule the next batch
        self::schedule_next_batch();
    }
    
    public static function process_single_post($post_id, $options) {
        $image_id = AFI_Image_Selector::find_image_for_post($post_id, $options['image_source']);

        if ($image_id) {
            set_post_thumbnail($post_id, $image_id);
            AFI_Logger::log('Success', "Set featured image (ID: {$image_id}) for Post ID: {$post_id}.");
        } else {
            AFI_Logger::log('Info', "No suitable image found for Post ID: {$post_id}.");
            // Optional: Mark this post as checked to avoid re-scanning
            update_post_meta($post_id, '_afi_scanned_no_image_found', time());
        }
    }

    public static function schedule_next_batch() {
        $options = get_option('afi_settings');
        $interval = !empty($options['interval']) ? intval($options['interval']) : 5;

        as_schedule_single_action(
            time() + ($interval * 60), 
            AFI_Job_Manager::BATCH_PROCESSING_HOOK, 
            array(), 
            AFI_Job_Manager::BATCH_GROUP
        );
    }

    public static function finish_processing() {
        $status = get_option('afi_status');
        $status['running'] = false;
        update_option('afi_status', $status);
        AFI_Job_Manager::cancel_all_jobs();
        AFI_Logger::log('System', 'All posts processed. Job queue finished.');
    }
    
    public static function count_posts_without_featured_image() {
        // Task 4.2: Caching Implementation
        $count = get_transient('afi_unprocessed_count');
        if (false === $count) {
            $options = get_option('afi_settings');
            $post_types = isset($options['post_types']) ? (array) $options['post_types'] : array('post');

            global $wpdb;
            $post_types_in = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
            
            // Task 4.1: A direct, optimized SQL query for counting.
            $count = $wpdb->get_var(
                "SELECT COUNT(p.ID) 
                 FROM {$wpdb->posts} p 
                 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id' 
                 WHERE p.post_type IN ({$post_types_in}) 
                 AND p.post_status = 'publish' 
                 AND pm.meta_value IS NULL"
            );
            set_transient('afi_unprocessed_count', $count, 1 * HOUR_IN_SECONDS);
        }
        return $count;
    }
}