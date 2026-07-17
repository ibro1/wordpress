<?php
/**
 * WordPress Hooks Integration Class
 *
 * Handles all WordPress hook integrations for automatic featured image
 * assignment, including post save, publish, update, and other events.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Hooks Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Hooks {

    /**
     * Plugin instance
     *
     * @var Auto_Featured_Image
     * @since 1.0.0
     */
    private $plugin;

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Settings cache
     *
     * @var array
     * @since 1.0.0
     */
    private $settings = array();

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->logger = new Auto_Featured_Image_Logger();
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Load plugin settings
     *
     * @since 1.0.0
     */
    private function load_settings() {
        $this->settings = get_option( 'auto_featured_image_settings', array() );
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Post lifecycle hooks
        add_action( 'save_post', array( $this, 'handle_post_save' ), 10, 3 );
        add_action( 'wp_insert_post', array( $this, 'handle_post_insert' ), 10, 3 );
        add_action( 'post_updated', array( $this, 'handle_post_updated' ), 10, 3 );
        add_action( 'transition_post_status', array( $this, 'handle_post_status_transition' ), 10, 3 );
        
        // Post deletion hooks
        add_action( 'before_delete_post', array( $this, 'handle_post_deletion' ), 10, 1 );
        add_action( 'wp_trash_post', array( $this, 'handle_post_trash' ), 10, 1 );
        add_action( 'untrash_post', array( $this, 'handle_post_untrash' ), 10, 1 );
        
        // Media hooks
        add_action( 'add_attachment', array( $this, 'handle_attachment_added' ), 10, 1 );
        add_action( 'delete_attachment', array( $this, 'handle_attachment_deleted' ), 10, 1 );
        add_action( 'wp_update_attachment_metadata', array( $this, 'handle_attachment_metadata_updated' ), 10, 2 );
        
        // Featured image hooks
        add_action( 'added_post_meta', array( $this, 'handle_featured_image_added' ), 10, 4 );
        add_action( 'updated_post_meta', array( $this, 'handle_featured_image_updated' ), 10, 4 );
        add_action( 'deleted_post_meta', array( $this, 'handle_featured_image_deleted' ), 10, 4 );
        
        // Plugin lifecycle hooks
        add_action( 'activated_plugin', array( $this, 'handle_plugin_activated' ), 10, 2 );
        add_action( 'deactivated_plugin', array( $this, 'handle_plugin_deactivated' ), 10, 2 );
        
        // Settings hooks
        add_action( 'update_option_auto_featured_image_settings', array( $this, 'handle_settings_updated' ), 10, 3 );
        
        // Cron hooks
        add_action( 'auto_featured_image_process_queue', array( $this, 'handle_queue_processing' ) );
        add_action( 'auto_featured_image_cleanup', array( $this, 'handle_cleanup' ) );
        add_action( 'auto_featured_image_health_check', array( $this, 'handle_health_check' ) );
        
        // AJAX hooks for frontend (if needed)
        add_action( 'wp_ajax_auto_featured_image_process_single', array( $this, 'handle_ajax_process_single' ) );
        add_action( 'wp_ajax_nopriv_auto_featured_image_process_single', array( $this, 'handle_ajax_process_single' ) );
        
        // REST API hooks
        add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );
        
        // Theme switch hooks
        add_action( 'switch_theme', array( $this, 'handle_theme_switch' ) );
        
        // Import/Export hooks
        add_action( 'import_end', array( $this, 'handle_import_end' ) );
        add_action( 'wp_import_post_exists', array( $this, 'handle_import_post_exists' ), 10, 2 );
    }

    /**
     * Handle post save event
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool    $update Whether this is an update
     * @since 1.0.0
     */
    public function handle_post_save( $post_id, $post, $update ) {
        // Skip if auto-processing is disabled
        if ( ! $this->is_auto_processing_enabled() ) {
            return;
        }
        
        // Skip if not a supported post type
        if ( ! $this->is_supported_post_type( $post->post_type ) ) {
            return;
        }
        
        // Skip if post is not published
        if ( $post->post_status !== 'publish' ) {
            return;
        }
        
        // Skip if this is an autosave or revision
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        // Skip if post already has featured image and skip_existing is enabled
        if ( $this->should_skip_existing() && has_post_thumbnail( $post_id ) ) {
            $this->logger->debug( "Skipping post {$post_id} - already has featured image", array(), $post_id );
            return;
        }
        
        // Check if we should process this post
        if ( $this->should_process_post( $post_id, $post, $update ) ) {
            $this->queue_post_for_processing( $post_id, 'post_save', array(
                'update' => $update,
                'trigger' => 'save_post',
            ) );
        }
    }

    /**
     * Handle post insert event
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool    $update Whether this is an update
     * @since 1.0.0
     */
    public function handle_post_insert( $post_id, $post, $update ) {
        // Only process new posts (not updates)
        if ( $update ) {
            return;
        }
        
        $this->logger->debug( "New post inserted: {$post_id}", array(
            'post_type' => $post->post_type,
            'post_status' => $post->post_status,
        ), $post_id );
        
        // The actual processing will be handled by save_post hook
        // This is just for logging and potential future enhancements
    }

    /**
     * Handle post updated event
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post_after Post object after update
     * @param WP_Post $post_before Post object before update
     * @since 1.0.0
     */
    public function handle_post_updated( $post_id, $post_after, $post_before ) {
        // Check if content has changed significantly
        $content_changed = $this->has_content_changed_significantly( $post_before, $post_after );
        
        if ( $content_changed ) {
            $this->logger->debug( "Post content changed significantly: {$post_id}", array(
                'content_length_before' => strlen( $post_before->post_content ),
                'content_length_after' => strlen( $post_after->post_content ),
            ), $post_id );
            
            // Re-process if content changed and auto-update is enabled
            if ( $this->is_auto_update_enabled() ) {
                $this->queue_post_for_processing( $post_id, 'content_updated', array(
                    'trigger' => 'post_updated',
                    'content_changed' => true,
                ) );
            }
        }
    }

    /**
     * Handle post status transition
     *
     * @param string  $new_status New post status
     * @param string  $old_status Old post status
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function handle_post_status_transition( $new_status, $old_status, $post ) {
        // Process when post is published for the first time
        if ( $new_status === 'publish' && $old_status !== 'publish' ) {
            $this->logger->info( "Post published: {$post->ID}", array(
                'old_status' => $old_status,
                'new_status' => $new_status,
                'post_type' => $post->post_type,
            ), $post->ID );
            
            if ( $this->is_auto_processing_enabled() && $this->is_supported_post_type( $post->post_type ) ) {
                $this->queue_post_for_processing( $post->ID, 'published', array(
                    'trigger' => 'status_transition',
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                ) );
            }
        }
        
        // Handle unpublishing
        if ( $old_status === 'publish' && $new_status !== 'publish' ) {
            $this->logger->debug( "Post unpublished: {$post->ID}", array(
                'old_status' => $old_status,
                'new_status' => $new_status,
            ), $post->ID );
        }
    }

    /**
     * Handle post deletion
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function handle_post_deletion( $post_id ) {
        // Cancel any pending jobs for this post
        $this->plugin->queue->cancel_jobs_for_post( $post_id );
        
        // Clean up any plugin-specific metadata
        $this->cleanup_post_metadata( $post_id );
        
        $this->logger->debug( "Post deleted: {$post_id}", array(
            'action' => 'cleanup_jobs_and_metadata',
        ), $post_id );
    }

    /**
     * Handle post trash
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function handle_post_trash( $post_id ) {
        // Pause any pending jobs for this post
        $this->plugin->queue->pause_jobs_for_post( $post_id );
        
        $this->logger->debug( "Post trashed: {$post_id}", array(
            'action' => 'pause_jobs',
        ), $post_id );
    }

    /**
     * Handle post untrash
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function handle_post_untrash( $post_id ) {
        // Resume any paused jobs for this post
        $this->plugin->queue->resume_jobs_for_post( $post_id );
        
        $this->logger->debug( "Post untrashed: {$post_id}", array(
            'action' => 'resume_jobs',
        ), $post_id );
    }

    /**
     * Handle attachment added
     *
     * @param int $attachment_id Attachment ID
     * @since 1.0.0
     */
    public function handle_attachment_added( $attachment_id ) {
        $attachment = get_post( $attachment_id );
        
        if ( ! $attachment || ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }
        
        $this->logger->debug( "Image attachment added: {$attachment_id}", array(
            'filename' => basename( get_attached_file( $attachment_id ) ),
            'mime_type' => $attachment->post_mime_type,
        ) );
        
        // Check if this image should trigger re-processing of related posts
        if ( $this->is_auto_reprocess_enabled() ) {
            $this->check_for_related_posts_to_reprocess( $attachment_id );
        }
    }

    /**
     * Handle attachment deleted
     *
     * @param int $attachment_id Attachment ID
     * @since 1.0.0
     */
    public function handle_attachment_deleted( $attachment_id ) {
        // Find posts that were using this attachment as featured image
        $posts_using_attachment = $this->get_posts_using_attachment_as_featured( $attachment_id );
        
        foreach ( $posts_using_attachment as $post_id ) {
            $this->logger->warning( "Featured image deleted for post {$post_id}", array(
                'deleted_attachment_id' => $attachment_id,
            ), $post_id );
            
            // Queue for re-processing if auto-recovery is enabled
            if ( $this->is_auto_recovery_enabled() ) {
                $this->queue_post_for_processing( $post_id, 'featured_image_deleted', array(
                    'trigger' => 'attachment_deleted',
                    'deleted_attachment_id' => $attachment_id,
                ) );
            }
        }
    }

    /**
     * Handle attachment metadata updated
     *
     * @param array $data Attachment metadata
     * @param int   $attachment_id Attachment ID
     * @since 1.0.0
     */
    public function handle_attachment_metadata_updated( $data, $attachment_id ) {
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return $data;
        }
        
        $this->logger->debug( "Image metadata updated: {$attachment_id}", array(
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'file' => $data['file'] ?? '',
        ) );
        
        return $data;
    }

    /**
     * Handle featured image added
     *
     * @param int    $meta_id Meta ID
     * @param int    $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed  $meta_value Meta value
     * @since 1.0.0
     */
    public function handle_featured_image_added( $meta_id, $post_id, $meta_key, $meta_value ) {
        if ( $meta_key !== '_thumbnail_id' ) {
            return;
        }
        
        $this->logger->info( "Featured image added to post {$post_id}", array(
            'attachment_id' => $meta_value,
            'meta_id' => $meta_id,
        ), $post_id );
        
        // Cancel any pending jobs for this post since it now has a featured image
        $this->plugin->queue->cancel_jobs_for_post( $post_id );
        
        // Record this assignment for analytics
        $this->record_featured_image_assignment( $post_id, $meta_value, 'manual' );
    }

    /**
     * Handle featured image updated
     *
     * @param int    $meta_id Meta ID
     * @param int    $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed  $meta_value Meta value
     * @since 1.0.0
     */
    public function handle_featured_image_updated( $meta_id, $post_id, $meta_key, $meta_value ) {
        if ( $meta_key !== '_thumbnail_id' ) {
            return;
        }
        
        $this->logger->info( "Featured image updated for post {$post_id}", array(
            'new_attachment_id' => $meta_value,
            'meta_id' => $meta_id,
        ), $post_id );
        
        // Record this update for analytics
        $this->record_featured_image_assignment( $post_id, $meta_value, 'manual_update' );
    }

    /**
     * Handle featured image deleted
     *
     * @param array  $meta_ids Meta IDs
     * @param int    $post_id Post ID
     * @param string $meta_key Meta key
     * @param mixed  $meta_value Meta value
     * @since 1.0.0
     */
    public function handle_featured_image_deleted( $meta_ids, $post_id, $meta_key, $meta_value ) {
        if ( $meta_key !== '_thumbnail_id' ) {
            return;
        }

        $this->logger->warning( "Featured image removed from post {$post_id}", array(
            'removed_attachment_id' => $meta_value,
            'meta_ids' => $meta_ids,
        ), $post_id );

        // Queue for re-processing if auto-recovery is enabled
        if ( $this->is_auto_recovery_enabled() && $this->is_supported_post_type( get_post_type( $post_id ) ) ) {
            $this->queue_post_for_processing( $post_id, 'featured_image_removed', array(
                'trigger' => 'featured_image_deleted',
                'removed_attachment_id' => $meta_value,
            ) );
        }
    }

    /**
     * Handle plugin activated
     *
     * @param string $plugin Plugin file
     * @param bool   $network_wide Whether activated network-wide
     * @since 1.0.0
     */
    public function handle_plugin_activated( $plugin, $network_wide ) {
        if ( $plugin === AUTO_FEATURED_IMAGE_PLUGIN_BASENAME ) {
            $this->logger->info( 'Plugin activated', array(
                'network_wide' => $network_wide,
            ) );
        }
    }

    /**
     * Handle plugin deactivated
     *
     * @param string $plugin Plugin file
     * @param bool   $network_wide Whether deactivated network-wide
     * @since 1.0.0
     */
    public function handle_plugin_deactivated( $plugin, $network_wide ) {
        if ( $plugin === AUTO_FEATURED_IMAGE_PLUGIN_BASENAME ) {
            $this->logger->info( 'Plugin deactivated', array(
                'network_wide' => $network_wide,
            ) );

            // Pause all processing
            $this->plugin->queue->pause_processing();
        }
    }

    /**
     * Handle settings updated
     *
     * @param mixed $old_value Old settings value
     * @param mixed $value New settings value
     * @param string $option Option name
     * @since 1.0.0
     */
    public function handle_settings_updated( $old_value, $value, $option ) {
        $this->logger->info( 'Plugin settings updated', array(
            'option' => $option,
            'changes' => $this->get_settings_changes( $old_value, $value ),
        ) );

        // Reload settings cache
        $this->load_settings();

        // Handle specific setting changes
        $this->handle_specific_setting_changes( $old_value, $value );
    }

    /**
     * Handle queue processing cron job
     *
     * @since 1.0.0
     */
    public function handle_queue_processing() {
        if ( ! $this->plugin->queue->is_processing_active() ) {
            return;
        }

        $this->logger->debug( 'Processing queue via cron' );

        try {
            $this->plugin->queue->process_next_batch();
        } catch ( Exception $e ) {
            $this->logger->error( 'Queue processing failed', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ) );
        }
    }

    /**
     * Handle cleanup cron job
     *
     * @since 1.0.0
     */
    public function handle_cleanup() {
        $this->logger->debug( 'Running cleanup tasks' );

        try {
            // Clean up old logs
            $this->plugin->database->cleanup_old_logs();

            // Clean up completed jobs
            $this->plugin->database->cleanup_completed_jobs();

            // Clean up orphaned metadata
            $this->cleanup_orphaned_metadata();

            $this->logger->info( 'Cleanup completed successfully' );
        } catch ( Exception $e ) {
            $this->logger->error( 'Cleanup failed', array(
                'error' => $e->getMessage(),
            ) );
        }
    }

    /**
     * Handle health check cron job
     *
     * @since 1.0.0
     */
    public function handle_health_check() {
        $this->logger->debug( 'Running health check' );

        $health_status = array(
            'database' => $this->check_database_health(),
            'queue' => $this->check_queue_health(),
            'performance' => $this->check_performance_health(),
            'dependencies' => $this->check_dependencies_health(),
        );

        $overall_health = $this->calculate_overall_health( $health_status );

        $this->logger->info( 'Health check completed', array(
            'overall_health' => $overall_health,
            'details' => $health_status,
        ) );

        // Take action if health is poor
        if ( $overall_health < 70 ) {
            $this->handle_poor_health( $health_status );
        }
    }

    /**
     * Handle AJAX process single post
     *
     * @since 1.0.0
     */
    public function handle_ajax_process_single() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'auto_featured_image_process_single' ) ) {
            wp_die( 'Security check failed' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );

        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( 'Invalid post ID or insufficient permissions' );
        }

        try {
            $result = $this->plugin->processor->process_post( $post_id );

            if ( $result ) {
                wp_send_json_success( array(
                    'message' => 'Post processed successfully',
                    'attachment_id' => $result,
                ) );
            } else {
                wp_send_json_error( 'No suitable image found for this post' );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( 'Processing failed: ' . $e->getMessage() );
        }
    }

    /**
     * Register REST API endpoints
     *
     * @since 1.0.0
     */
    public function register_rest_endpoints() {
        register_rest_route( 'auto-featured-image/v1', '/process/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array( $this, 'rest_process_post' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    }
                ),
            ),
        ) );

        register_rest_route( 'auto-featured-image/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_status' ),
            'permission_callback' => array( $this, 'rest_permission_check' ),
        ) );
    }

    /**
     * REST API process post endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     * @since 1.0.0
     */
    public function rest_process_post( $request ) {
        $post_id = $request->get_param( 'id' );

        try {
            $result = $this->plugin->processor->process_post( $post_id );

            return new WP_REST_Response( array(
                'success' => true,
                'post_id' => $post_id,
                'attachment_id' => $result,
                'message' => $result ? 'Featured image assigned successfully' : 'No suitable image found',
            ), 200 );
        } catch ( Exception $e ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $e->getMessage(),
            ), 500 );
        }
    }

    /**
     * REST API get status endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     * @since 1.0.0
     */
    public function rest_get_status( $request ) {
        $status = array(
            'queue' => $this->plugin->queue->get_queue_stats(),
            'processing' => $this->plugin->queue->is_processing_active(),
            'health' => $this->get_system_health_summary(),
        );

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * REST API permission check
     *
     * @param WP_REST_Request $request Request object
     * @return bool Whether user has permission
     * @since 1.0.0
     */
    public function rest_permission_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Handle theme switch
     *
     * @since 1.0.0
     */
    public function handle_theme_switch() {
        $this->logger->info( 'Theme switched', array(
            'new_theme' => get_stylesheet(),
        ) );

        // Theme switch might affect image sizes, so clear any cached data
        $this->clear_image_size_cache();
    }

    /**
     * Handle import end
     *
     * @since 1.0.0
     */
    public function handle_import_end() {
        $this->logger->info( 'Import completed' );

        // Queue all imported posts for processing if auto-processing is enabled
        if ( $this->is_auto_processing_enabled() && $this->is_import_processing_enabled() ) {
            $this->queue_imported_posts_for_processing();
        }
    }

    /**
     * Handle import post exists check
     *
     * @param int   $post_id Post ID
     * @param array $post_data Post data
     * @return int Post ID if exists, 0 otherwise
     * @since 1.0.0
     */
    public function handle_import_post_exists( $post_id, $post_data ) {
        if ( $post_id ) {
            $this->logger->debug( "Import: Post exists {$post_id}", array(
                'post_title' => $post_data['post_title'] ?? '',
            ) );
        }

        return $post_id;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Check if auto-processing is enabled
     *
     * @return bool Whether auto-processing is enabled
     * @since 1.0.0
     */
    private function is_auto_processing_enabled() {
        return ! empty( $this->settings['auto_processing_enabled'] );
    }

    /**
     * Check if auto-update is enabled
     *
     * @return bool Whether auto-update is enabled
     * @since 1.0.0
     */
    private function is_auto_update_enabled() {
        return ! empty( $this->settings['auto_update_enabled'] );
    }

    /**
     * Check if auto-recovery is enabled
     *
     * @return bool Whether auto-recovery is enabled
     * @since 1.0.0
     */
    private function is_auto_recovery_enabled() {
        return ! empty( $this->settings['auto_recovery_enabled'] );
    }

    /**
     * Check if auto-reprocess is enabled
     *
     * @return bool Whether auto-reprocess is enabled
     * @since 1.0.0
     */
    private function is_auto_reprocess_enabled() {
        return ! empty( $this->settings['auto_reprocess_enabled'] );
    }

    /**
     * Check if import processing is enabled
     *
     * @return bool Whether import processing is enabled
     * @since 1.0.0
     */
    private function is_import_processing_enabled() {
        return ! empty( $this->settings['process_imported_posts'] );
    }

    /**
     * Check if should skip existing featured images
     *
     * @return bool Whether to skip existing
     * @since 1.0.0
     */
    private function should_skip_existing() {
        return ! empty( $this->settings['skip_existing'] );
    }

    /**
     * Check if post type is supported
     *
     * @param string $post_type Post type
     * @return bool Whether post type is supported
     * @since 1.0.0
     */
    private function is_supported_post_type( $post_type ) {
        $supported_types = $this->settings['post_types'] ?? array( 'post' );
        return in_array( $post_type, $supported_types );
    }

    /**
     * Check if post should be processed
     *
     * @param int     $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool    $update Whether this is an update
     * @return bool Whether post should be processed
     * @since 1.0.0
     */
    private function should_process_post( $post_id, $post, $update ) {
        // Skip if post is empty or too short
        if ( strlen( trim( $post->post_content ) ) < 100 ) {
            return false;
        }

        // Skip if post doesn't contain any images
        if ( ! $this->post_contains_images( $post->post_content ) ) {
            return false;
        }

        // Skip if recently processed (to avoid duplicate processing)
        if ( $this->was_recently_processed( $post_id ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if post contains images
     *
     * @param string $content Post content
     * @return bool Whether post contains images
     * @since 1.0.0
     */
    private function post_contains_images( $content ) {
        return preg_match( '/<img[^>]+>/i', $content ) ||
               preg_match( '/\[gallery[^\]]*\]/i', $content ) ||
               preg_match( '/\[image[^\]]*\]/i', $content );
    }

    /**
     * Check if post was recently processed
     *
     * @param int $post_id Post ID
     * @return bool Whether post was recently processed
     * @since 1.0.0
     */
    private function was_recently_processed( $post_id ) {
        $last_processed = get_post_meta( $post_id, '_auto_featured_image_last_processed', true );

        if ( ! $last_processed ) {
            return false;
        }

        // Consider "recent" as within the last 5 minutes
        return ( time() - $last_processed ) < 300;
    }

    /**
     * Check if content has changed significantly
     *
     * @param WP_Post $post_before Post before update
     * @param WP_Post $post_after Post after update
     * @return bool Whether content changed significantly
     * @since 1.0.0
     */
    private function has_content_changed_significantly( $post_before, $post_after ) {
        $content_before = wp_strip_all_tags( $post_before->post_content );
        $content_after = wp_strip_all_tags( $post_after->post_content );

        $length_before = strlen( $content_before );
        $length_after = strlen( $content_after );

        // Consider significant if content length changed by more than 20%
        if ( $length_before > 0 ) {
            $change_percentage = abs( $length_after - $length_before ) / $length_before;
            return $change_percentage > 0.2;
        }

        return $length_after > 100; // New content added
    }

    /**
     * Queue post for processing
     *
     * @param int    $post_id Post ID
     * @param string $reason Processing reason
     * @param array  $context Additional context
     * @since 1.0.0
     */
    private function queue_post_for_processing( $post_id, $reason, $context = array() ) {
        $priority = $this->get_processing_priority( $reason );

        $job_data = array_merge( $context, array(
            'post_id' => $post_id,
            'reason' => $reason,
            'queued_at' => time(),
        ) );

        $job_id = $this->plugin->queue->add_job( $post_id, $priority, $job_data );

        if ( $job_id ) {
            $this->logger->debug( "Queued post {$post_id} for processing", array(
                'job_id' => $job_id,
                'reason' => $reason,
                'priority' => $priority,
            ), $post_id );

            // Update last processed timestamp
            update_post_meta( $post_id, '_auto_featured_image_last_queued', time() );
        } else {
            $this->logger->warning( "Failed to queue post {$post_id} for processing", array(
                'reason' => $reason,
            ), $post_id );
        }
    }

    /**
     * Get processing priority based on reason
     *
     * @param string $reason Processing reason
     * @return string Priority level
     * @since 1.0.0
     */
    private function get_processing_priority( $reason ) {
        $priority_map = array(
            'published' => 'high',
            'post_save' => 'normal',
            'content_updated' => 'normal',
            'featured_image_deleted' => 'high',
            'featured_image_removed' => 'high',
            'import' => 'low',
        );

        return $priority_map[ $reason ] ?? 'normal';
    }

    /**
     * Cleanup post metadata
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    private function cleanup_post_metadata( $post_id ) {
        $meta_keys = array(
            '_auto_featured_image_last_processed',
            '_auto_featured_image_last_queued',
            '_auto_featured_image_processing_attempts',
            '_auto_featured_image_last_error',
            '_auto_featured_image_algorithm_used',
            '_auto_featured_image_score',
        );

        foreach ( $meta_keys as $meta_key ) {
            delete_post_meta( $post_id, $meta_key );
        }
    }

    /**
     * Check for related posts to reprocess
     *
     * @param int $attachment_id Attachment ID
     * @since 1.0.0
     */
    private function check_for_related_posts_to_reprocess( $attachment_id ) {
        // Find posts that might benefit from this new image
        $parent_post = get_post_parent( $attachment_id );

        if ( $parent_post && ! has_post_thumbnail( $parent_post->ID ) ) {
            $this->queue_post_for_processing( $parent_post->ID, 'new_attachment_added', array(
                'trigger' => 'attachment_added',
                'attachment_id' => $attachment_id,
            ) );
        }
    }

    /**
     * Get posts using attachment as featured image
     *
     * @param int $attachment_id Attachment ID
     * @return array Post IDs
     * @since 1.0.0
     */
    private function get_posts_using_attachment_as_featured( $attachment_id ) {
        global $wpdb;

        $post_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
            $attachment_id
        ) );

        return array_map( 'intval', $post_ids );
    }

    /**
     * Record featured image assignment
     *
     * @param int    $post_id Post ID
     * @param int    $attachment_id Attachment ID
     * @param string $method Assignment method
     * @since 1.0.0
     */
    private function record_featured_image_assignment( $post_id, $attachment_id, $method ) {
        $this->plugin->database->record_assignment( array(
            'post_id' => $post_id,
            'attachment_id' => $attachment_id,
            'method' => $method,
            'assigned_at' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Get settings changes
     *
     * @param array $old_value Old settings
     * @param array $new_value New settings
     * @return array Changes
     * @since 1.0.0
     */
    private function get_settings_changes( $old_value, $new_value ) {
        $changes = array();

        if ( ! is_array( $old_value ) ) {
            $old_value = array();
        }

        if ( ! is_array( $new_value ) ) {
            $new_value = array();
        }

        $all_keys = array_unique( array_merge( array_keys( $old_value ), array_keys( $new_value ) ) );

        foreach ( $all_keys as $key ) {
            $old_val = $old_value[ $key ] ?? null;
            $new_val = $new_value[ $key ] ?? null;

            if ( $old_val !== $new_val ) {
                $changes[ $key ] = array(
                    'old' => $old_val,
                    'new' => $new_val,
                );
            }
        }

        return $changes;
    }

    /**
     * Handle specific setting changes
     *
     * @param array $old_value Old settings
     * @param array $new_value New settings
     * @since 1.0.0
     */
    private function handle_specific_setting_changes( $old_value, $new_value ) {
        // Handle auto-processing toggle
        if ( ( $old_value['auto_processing_enabled'] ?? false ) !== ( $new_value['auto_processing_enabled'] ?? false ) ) {
            if ( $new_value['auto_processing_enabled'] ?? false ) {
                $this->logger->info( 'Auto-processing enabled' );
            } else {
                $this->logger->info( 'Auto-processing disabled' );
                $this->plugin->queue->pause_processing();
            }
        }

        // Handle post type changes
        if ( ( $old_value['post_types'] ?? array() ) !== ( $new_value['post_types'] ?? array() ) ) {
            $this->logger->info( 'Supported post types changed', array(
                'old_types' => $old_value['post_types'] ?? array(),
                'new_types' => $new_value['post_types'] ?? array(),
            ) );
        }

        // Handle algorithm changes
        if ( ( $old_value['enabled_algorithms'] ?? array() ) !== ( $new_value['enabled_algorithms'] ?? array() ) ) {
            $this->logger->info( 'Enabled algorithms changed', array(
                'old_algorithms' => $old_value['enabled_algorithms'] ?? array(),
                'new_algorithms' => $new_value['enabled_algorithms'] ?? array(),
            ) );
        }
    }

    /**
     * Cleanup orphaned metadata
     *
     * @since 1.0.0
     */
    private function cleanup_orphaned_metadata() {
        global $wpdb;

        // Clean up metadata for deleted posts
        $deleted_count = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL
             AND pm.meta_key LIKE '_auto_featured_image_%'"
        );

        if ( $deleted_count > 0 ) {
            $this->logger->info( "Cleaned up {$deleted_count} orphaned metadata entries" );
        }
    }

    /**
     * Check database health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_database_health() {
        $health = array(
            'score' => 100,
            'issues' => array(),
        );

        // Check if tables exist
        if ( ! $this->plugin->database->tables_exist() ) {
            $health['score'] -= 50;
            $health['issues'][] = 'Database tables missing';
        }

        // Check table sizes
        $table_sizes = $this->plugin->database->get_table_sizes();
        foreach ( $table_sizes as $table => $size ) {
            if ( $size > 100 * 1024 * 1024 ) { // 100MB
                $health['score'] -= 10;
                $health['issues'][] = "Large table: {$table} ({$size} bytes)";
            }
        }

        return $health;
    }

    /**
     * Check queue health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_queue_health() {
        $health = array(
            'score' => 100,
            'issues' => array(),
        );

        $queue_stats = $this->plugin->queue->get_queue_stats();

        // Check for stuck jobs
        if ( $queue_stats['stuck_jobs'] > 0 ) {
            $health['score'] -= 20;
            $health['issues'][] = "Stuck jobs: {$queue_stats['stuck_jobs']}";
        }

        // Check failure rate
        if ( $queue_stats['failure_rate'] > 20 ) {
            $health['score'] -= 30;
            $health['issues'][] = "High failure rate: {$queue_stats['failure_rate']}%";
        }

        // Check queue size
        if ( $queue_stats['pending_jobs'] > 1000 ) {
            $health['score'] -= 15;
            $health['issues'][] = "Large queue: {$queue_stats['pending_jobs']} pending jobs";
        }

        return $health;
    }

    /**
     * Check performance health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_performance_health() {
        $health = array(
            'score' => 100,
            'issues' => array(),
        );

        $metrics = $this->plugin->batch_manager->get_metrics();

        // Check average execution time
        if ( $metrics['avg_execution_time'] > 60 ) {
            $health['score'] -= 25;
            $health['issues'][] = "Slow execution: {$metrics['avg_execution_time']}s average";
        }

        // Check memory usage
        if ( $metrics['peak_memory_usage'] > 128 * 1024 * 1024 ) { // 128MB
            $health['score'] -= 20;
            $health['issues'][] = "High memory usage: " . size_format( $metrics['peak_memory_usage'] );
        }

        return $health;
    }

    /**
     * Check dependencies health
     *
     * @return array Health status
     * @since 1.0.0
     */
    private function check_dependencies_health() {
        $health = array(
            'score' => 100,
            'issues' => array(),
        );

        // Check Action Scheduler
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            $health['score'] -= 30;
            $health['issues'][] = 'Action Scheduler not available';
        }

        // Check image processing extensions
        if ( ! extension_loaded( 'gd' ) && ! extension_loaded( 'imagick' ) ) {
            $health['score'] -= 40;
            $health['issues'][] = 'No image processing extension available';
        }

        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        if ( $memory_limit < 64 * 1024 * 1024 ) { // 64MB
            $health['score'] -= 20;
            $health['issues'][] = 'Low memory limit: ' . ini_get( 'memory_limit' );
        }

        return $health;
    }

    /**
     * Calculate overall health score
     *
     * @param array $health_status Health status from all checks
     * @return int Overall health score
     * @since 1.0.0
     */
    private function calculate_overall_health( $health_status ) {
        $total_score = 0;
        $count = 0;

        foreach ( $health_status as $component => $status ) {
            if ( isset( $status['score'] ) ) {
                $total_score += $status['score'];
                $count++;
            }
        }

        return $count > 0 ? round( $total_score / $count ) : 0;
    }

    /**
     * Handle poor health
     *
     * @param array $health_status Health status
     * @since 1.0.0
     */
    private function handle_poor_health( $health_status ) {
        $this->logger->warning( 'System health is poor', array(
            'health_status' => $health_status,
        ) );

        // Take corrective actions
        foreach ( $health_status as $component => $status ) {
            if ( $status['score'] < 50 ) {
                switch ( $component ) {
                    case 'queue':
                        $this->plugin->queue->recover_stuck_jobs();
                        break;

                    case 'database':
                        $this->plugin->database->repair_tables();
                        break;

                    case 'performance':
                        $this->plugin->batch_manager->optimize_batch_size();
                        break;
                }
            }
        }
    }

    /**
     * Get system health summary
     *
     * @return array Health summary
     * @since 1.0.0
     */
    private function get_system_health_summary() {
        $health_status = array(
            'database' => $this->check_database_health(),
            'queue' => $this->check_queue_health(),
            'performance' => $this->check_performance_health(),
            'dependencies' => $this->check_dependencies_health(),
        );

        return array(
            'overall_score' => $this->calculate_overall_health( $health_status ),
            'components' => $health_status,
        );
    }

    /**
     * Clear image size cache
     *
     * @since 1.0.0
     */
    private function clear_image_size_cache() {
        wp_cache_delete( 'auto_featured_image_sizes', 'auto_featured_image' );
    }

    /**
     * Queue imported posts for processing
     *
     * @since 1.0.0
     */
    private function queue_imported_posts_for_processing() {
        // This would need to be implemented based on specific import tracking
        $this->logger->info( 'Queuing imported posts for processing' );

        // For now, we'll just log this event
        // In a real implementation, we'd track imported posts and queue them
    }
}
