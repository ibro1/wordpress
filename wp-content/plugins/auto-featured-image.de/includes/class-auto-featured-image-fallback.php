<?php
/**
 * Fallback and Graceful Degradation System
 *
 * Provides fallback mechanisms and graceful degradation when core
 * components fail or are unavailable.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Fallback Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Fallback {

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
     * Fallback modes
     *
     * @var array
     * @since 1.0.0
     */
    private $fallback_modes = array();

    /**
     * Current fallback level
     *
     * @var string
     * @since 1.0.0
     */
    private $current_fallback_level = 'none';

    /**
     * Constructor
     *
     * @param Auto_Featured_Image $plugin Plugin instance
     * @since 1.0.0
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->logger = new Auto_Featured_Image_Logger();
        $this->init_fallback_modes();
    }

    /**
     * Initialize fallback modes
     *
     * @since 1.0.0
     */
    private function init_fallback_modes() {
        $this->fallback_modes = array(
            'none' => array(
                'description' => 'Normal operation',
                'features' => array( 'all' ),
                'restrictions' => array(),
            ),
            'limited' => array(
                'description' => 'Limited functionality - basic algorithms only',
                'features' => array( 'basic_processing', 'simple_algorithms' ),
                'restrictions' => array( 'no_advanced_algorithms', 'reduced_batch_size' ),
            ),
            'minimal' => array(
                'description' => 'Minimal functionality - first image only',
                'features' => array( 'first_image_only' ),
                'restrictions' => array( 'no_algorithms', 'no_batch_processing', 'no_queue' ),
            ),
            'disabled' => array(
                'description' => 'Plugin disabled due to critical errors',
                'features' => array(),
                'restrictions' => array( 'all_features_disabled' ),
            ),
        );
    }

    /**
     * Activate fallback mode
     *
     * @param string $level Fallback level
     * @param string $reason Reason for fallback
     * @since 1.0.0
     */
    public function activate_fallback( $level, $reason = '' ) {
        if ( ! isset( $this->fallback_modes[ $level ] ) ) {
            return false;
        }

        $previous_level = $this->current_fallback_level;
        $this->current_fallback_level = $level;

        $this->logger->warning( "Activating fallback mode: {$level}", array(
            'previous_level' => $previous_level,
            'reason' => $reason,
            'features' => $this->fallback_modes[ $level ]['features'],
            'restrictions' => $this->fallback_modes[ $level ]['restrictions'],
        ) );

        // Store fallback state
        update_option( 'auto_featured_image_fallback_level', $level );
        update_option( 'auto_featured_image_fallback_reason', $reason );
        update_option( 'auto_featured_image_fallback_activated', time() );

        // Apply fallback restrictions
        $this->apply_fallback_restrictions( $level );

        // Notify administrators
        $this->notify_fallback_activation( $level, $reason );

        return true;
    }

    /**
     * Deactivate fallback mode
     *
     * @since 1.0.0
     */
    public function deactivate_fallback() {
        $previous_level = $this->current_fallback_level;
        $this->current_fallback_level = 'none';

        $this->logger->info( "Deactivating fallback mode", array(
            'previous_level' => $previous_level,
        ) );

        // Clear fallback state
        delete_option( 'auto_featured_image_fallback_level' );
        delete_option( 'auto_featured_image_fallback_reason' );
        delete_option( 'auto_featured_image_fallback_activated' );

        // Remove fallback restrictions
        $this->remove_fallback_restrictions();

        return true;
    }

    /**
     * Check if feature is available in current fallback mode
     *
     * @param string $feature Feature name
     * @return bool Whether feature is available
     * @since 1.0.0
     */
    public function is_feature_available( $feature ) {
        $current_mode = $this->fallback_modes[ $this->current_fallback_level ];

        // If all features are disabled
        if ( in_array( 'all_features_disabled', $current_mode['restrictions'] ) ) {
            return false;
        }

        // If all features are available
        if ( in_array( 'all', $current_mode['features'] ) ) {
            return true;
        }

        // Check specific feature availability
        return in_array( $feature, $current_mode['features'] );
    }

    /**
     * Get fallback processor for post
     *
     * @param int $post_id Post ID
     * @return bool|int Attachment ID or false
     * @since 1.0.0
     */
    public function process_post_fallback( $post_id ) {
        if ( ! $this->is_feature_available( 'basic_processing' ) && 
             ! $this->is_feature_available( 'first_image_only' ) ) {
            return false;
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return false;
        }

        try {
            // Use minimal processing approach
            if ( $this->current_fallback_level === 'minimal' ) {
                return $this->get_first_image_fallback( $post );
            }

            // Use limited processing approach
            if ( $this->current_fallback_level === 'limited' ) {
                return $this->get_basic_image_fallback( $post );
            }

            return false;

        } catch ( Exception $e ) {
            $this->logger->error( "Fallback processing failed for post {$post_id}", array(
                'error' => $e->getMessage(),
                'fallback_level' => $this->current_fallback_level,
            ), $post_id );

            return false;
        }
    }

    /**
     * Get first image from post content (minimal fallback)
     *
     * @param WP_Post $post Post object
     * @return bool|int Attachment ID or false
     * @since 1.0.0
     */
    private function get_first_image_fallback( $post ) {
        // Extract first image from content
        preg_match( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches );

        if ( empty( $matches[1] ) ) {
            return false;
        }

        $image_url = $matches[1];

        // Try to get attachment ID from URL
        $attachment_id = attachment_url_to_postid( $image_url );

        if ( $attachment_id ) {
            // Verify it's an image
            if ( wp_attachment_is_image( $attachment_id ) ) {
                set_post_thumbnail( $post->ID, $attachment_id );
                
                $this->logger->info( "Fallback: Set first image as featured image", array(
                    'post_id' => $post->ID,
                    'attachment_id' => $attachment_id,
                    'method' => 'first_image_fallback',
                ), $post->ID );

                return $attachment_id;
            }
        }

        return false;
    }

    /**
     * Get image using basic algorithm (limited fallback)
     *
     * @param WP_Post $post Post object
     * @return bool|int Attachment ID or false
     * @since 1.0.0
     */
    private function get_basic_image_fallback( $post ) {
        // Get all images from post content
        preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->post_content, $matches );

        if ( empty( $matches[1] ) ) {
            return false;
        }

        $best_image = null;
        $best_score = 0;

        foreach ( $matches[1] as $image_url ) {
            $attachment_id = attachment_url_to_postid( $image_url );

            if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
                continue;
            }

            // Simple scoring based on image size
            $metadata = wp_get_attachment_metadata( $attachment_id );
            if ( ! $metadata || ! isset( $metadata['width'], $metadata['height'] ) ) {
                continue;
            }

            $score = $metadata['width'] * $metadata['height'];

            // Prefer images that are more square (better for featured images)
            $aspect_ratio = $metadata['width'] / $metadata['height'];
            if ( $aspect_ratio >= 0.8 && $aspect_ratio <= 1.25 ) {
                $score *= 1.5;
            }

            // Prefer larger images but not too large
            if ( $metadata['width'] >= 300 && $metadata['width'] <= 1200 ) {
                $score *= 1.2;
            }

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_image = $attachment_id;
            }
        }

        if ( $best_image ) {
            set_post_thumbnail( $post->ID, $best_image );
            
            $this->logger->info( "Fallback: Set best image as featured image", array(
                'post_id' => $post->ID,
                'attachment_id' => $best_image,
                'score' => $best_score,
                'method' => 'basic_algorithm_fallback',
            ), $post->ID );

            return $best_image;
        }

        return false;
    }

    /**
     * Apply fallback restrictions
     *
     * @param string $level Fallback level
     * @since 1.0.0
     */
    private function apply_fallback_restrictions( $level ) {
        $restrictions = $this->fallback_modes[ $level ]['restrictions'];

        foreach ( $restrictions as $restriction ) {
            switch ( $restriction ) {
                case 'no_advanced_algorithms':
                    // Disable advanced algorithms
                    add_filter( 'auto_featured_image_enabled_algorithms', array( $this, 'filter_basic_algorithms_only' ) );
                    break;

                case 'reduced_batch_size':
                    // Reduce batch size
                    add_filter( 'auto_featured_image_batch_size', array( $this, 'filter_reduced_batch_size' ) );
                    break;

                case 'no_algorithms':
                    // Disable all algorithms
                    add_filter( 'auto_featured_image_enabled_algorithms', '__return_empty_array' );
                    break;

                case 'no_batch_processing':
                    // Disable batch processing
                    add_filter( 'auto_featured_image_batch_processing_enabled', '__return_false' );
                    break;

                case 'no_queue':
                    // Disable queue processing
                    add_filter( 'auto_featured_image_queue_processing_enabled', '__return_false' );
                    break;

                case 'all_features_disabled':
                    // Disable all plugin features
                    add_filter( 'auto_featured_image_processing_enabled', '__return_false' );
                    break;
            }
        }
    }

    /**
     * Remove fallback restrictions
     *
     * @since 1.0.0
     */
    private function remove_fallback_restrictions() {
        // Remove all fallback filters
        remove_filter( 'auto_featured_image_enabled_algorithms', array( $this, 'filter_basic_algorithms_only' ) );
        remove_filter( 'auto_featured_image_batch_size', array( $this, 'filter_reduced_batch_size' ) );
        remove_filter( 'auto_featured_image_enabled_algorithms', '__return_empty_array' );
        remove_filter( 'auto_featured_image_batch_processing_enabled', '__return_false' );
        remove_filter( 'auto_featured_image_queue_processing_enabled', '__return_false' );
        remove_filter( 'auto_featured_image_processing_enabled', '__return_false' );
    }

    /**
     * Filter to allow only basic algorithms
     *
     * @param array $algorithms Enabled algorithms
     * @return array Filtered algorithms
     * @since 1.0.0
     */
    public function filter_basic_algorithms_only( $algorithms ) {
        $basic_algorithms = array( 'first_quality_image', 'content_based' );
        return array_intersect( $algorithms, $basic_algorithms );
    }

    /**
     * Filter to reduce batch size
     *
     * @param int $batch_size Current batch size
     * @return int Reduced batch size
     * @since 1.0.0
     */
    public function filter_reduced_batch_size( $batch_size ) {
        return max( 1, intval( $batch_size / 4 ) );
    }

    /**
     * Notify administrators of fallback activation
     *
     * @param string $level Fallback level
     * @param string $reason Reason for fallback
     * @since 1.0.0
     */
    private function notify_fallback_activation( $level, $reason ) {
        $settings = get_option( 'auto_featured_image_settings', array() );

        if ( empty( $settings['fallback_notifications_enabled'] ) ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );

        $subject = sprintf( '[%s] Auto Featured Image - Fallback Mode Activated', $site_name );

        $body = "The Auto Featured Image plugin has activated fallback mode:\n\n";
        $body .= "Fallback Level: {$level}\n";
        $body .= "Reason: {$reason}\n";
        $body .= "Description: {$this->fallback_modes[$level]['description']}\n";
        $body .= "Time: " . current_time( 'mysql' ) . "\n\n";

        $body .= "Available Features:\n";
        foreach ( $this->fallback_modes[$level]['features'] as $feature ) {
            $body .= "  - {$feature}\n";
        }

        $body .= "\nRestrictions:\n";
        foreach ( $this->fallback_modes[$level]['restrictions'] as $restriction ) {
            $body .= "  - {$restriction}\n";
        }

        $body .= "\nPlease check your WordPress admin dashboard for more details and to resolve any issues.";

        wp_mail( $admin_email, $subject, $body );
    }

    /**
     * Get current fallback status
     *
     * @return array Fallback status
     * @since 1.0.0
     */
    public function get_fallback_status() {
        return array(
            'level' => $this->current_fallback_level,
            'description' => $this->fallback_modes[ $this->current_fallback_level ]['description'],
            'features' => $this->fallback_modes[ $this->current_fallback_level ]['features'],
            'restrictions' => $this->fallback_modes[ $this->current_fallback_level ]['restrictions'],
            'activated_at' => get_option( 'auto_featured_image_fallback_activated', null ),
            'reason' => get_option( 'auto_featured_image_fallback_reason', '' ),
        );
    }

    /**
     * Check if system should enter fallback mode
     *
     * @return bool Whether fallback should be activated
     * @since 1.0.0
     */
    public function should_activate_fallback() {
        // Check error rates
        if ( $this->plugin->error_handler ) {
            $health_status = $this->plugin->error_handler->check_system_health();
            
            if ( ! $health_status['healthy'] ) {
                $critical_issues = 0;
                foreach ( $health_status['issues'] as $issue ) {
                    if ( strpos( $issue, 'High error rate' ) !== false ) {
                        $critical_issues++;
                    }
                }
                
                if ( $critical_issues >= 3 ) {
                    return 'minimal';
                } elseif ( $critical_issues >= 1 ) {
                    return 'limited';
                }
            }
        }

        // Check database connectivity
        if ( ! $this->plugin->database->tables_exist() ) {
            return 'disabled';
        }

        // Check memory usage
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $memory_usage = memory_get_usage( true );
        $usage_percentage = ( $memory_usage / $memory_limit ) * 100;

        if ( $usage_percentage > 95 ) {
            return 'minimal';
        } elseif ( $usage_percentage > 85 ) {
            return 'limited';
        }

        return false;
    }

    /**
     * Auto-check and activate fallback if needed
     *
     * @since 1.0.0
     */
    public function auto_check_fallback() {
        $should_fallback = $this->should_activate_fallback();
        
        if ( $should_fallback && $this->current_fallback_level === 'none' ) {
            $this->activate_fallback( $should_fallback, 'Automatic activation due to system issues' );
        } elseif ( ! $should_fallback && $this->current_fallback_level !== 'none' ) {
            // Check if we can safely deactivate fallback
            $activated_time = get_option( 'auto_featured_image_fallback_activated', 0 );
            
            // Wait at least 5 minutes before deactivating
            if ( time() - $activated_time > 300 ) {
                $this->deactivate_fallback();
            }
        }
    }
}
