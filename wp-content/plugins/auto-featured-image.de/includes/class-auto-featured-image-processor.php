<?php
/**
 * Image Processor Class
 *
 * Handles the core image processing functionality for automatically
 * assigning featured images to posts.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Processor Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Processor {

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Image analyzer
     *
     * @var Auto_Featured_Image_Analyzer
     * @since 1.0.0
     */
    private $analyzer;

    /**
     * Post processing engine
     *
     * @var Auto_Featured_Image_Post_Engine
     * @since 1.0.0
     */
    private $post_engine;

    /**
     * Image assignment algorithms
     *
     * @var Auto_Featured_Image_Algorithms
     * @since 1.0.0
     */
    private $algorithms;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Auto_Featured_Image_Logger();
        $this->analyzer = new Auto_Featured_Image_Analyzer();
        $this->post_engine = new Auto_Featured_Image_Post_Engine();
        $this->algorithms = new Auto_Featured_Image_Algorithms();
    }

    /**
     * Process a single post to assign featured image
     *
     * @param int $post_id Post ID to process
     * @return bool True on success, false on failure
     * @since 1.0.0
     */
    public function process_post( $post_id ) {
        try {
            // Get the post
            $post = get_post( $post_id );
            if ( ! $post ) {
                throw new Exception( 'Post not found' );
            }

            // Check if post already has featured image
            if ( has_post_thumbnail( $post_id ) ) {
                $this->logger->debug( "Post $post_id already has featured image", null, $post_id );
                return true;
            }

            // Get plugin settings
            $settings = get_option( 'auto_featured_image_settings', array() );
            $method = isset( $settings['image_selection_method'] ) ? $settings['image_selection_method'] : 'first_content_image';

            // Perform comprehensive post analysis
            $post_analysis = $this->post_engine->process_post( $post );

            // Use advanced image analysis with post context
            $image_id = $this->find_best_image_for_post( $post, $method, $post_analysis );

            if ( $image_id ) {
                // Set the featured image
                $result = set_post_thumbnail( $post_id, $image_id );

                if ( $result ) {
                    $this->logger->info(
                        "Successfully assigned featured image $image_id to post $post_id",
                        array(
                            'method' => $method,
                            'post_analysis' => $post_analysis ? $this->get_analysis_summary( $post_analysis ) : null
                        ),
                        $post_id
                    );
                    return true;
                } else {
                    throw new Exception( 'Failed to set post thumbnail' );
                }
            } else {
                // Try fallback method if configured
                $fallback_method = isset( $settings['fallback_method'] ) ? $settings['fallback_method'] : null;

                if ( $fallback_method && $fallback_method !== $method ) {
                    $image_id = $this->find_best_image_for_post( $post, $fallback_method, $post_analysis );

                    if ( $image_id ) {
                        $result = set_post_thumbnail( $post_id, $image_id );

                        if ( $result ) {
                            $this->logger->info(
                                "Successfully assigned fallback featured image $image_id to post $post_id",
                                array( 'fallback_method' => $fallback_method ),
                                $post_id
                            );
                            return true;
                        }
                    }
                }

                $this->logger->warning(
                    "No suitable image found for post $post_id",
                    array(
                        'tried_methods' => array_filter( array( $method, $fallback_method ) ),
                        'post_analysis' => $post_analysis ? $this->get_analysis_summary( $post_analysis ) : null
                    ),
                    $post_id
                );
                return false;
            }

        } catch ( Exception $e ) {
            $this->logger->error( "Error processing post $post_id: " . $e->getMessage(), null, $post_id );
            return false;
        }
    }

    /**
     * Find the best image for a post using advanced analysis
     *
     * @param WP_Post $post Post object
     * @param string  $method Primary selection method
     * @param array   $post_analysis Optional post analysis data
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function find_best_image_for_post( $post, $method, $post_analysis = null ) {
        // Use advanced algorithms for image selection
        $algorithm_options = array(
            'algorithms' => $this->get_enabled_algorithms_for_method( $method ),
            'min_score' => $this->get_minimum_score_threshold(),
            'fallback_enabled' => true,
        );

        $result = $this->algorithms->find_best_image( $post, $algorithm_options );

        if ( $result ) {
            $this->logger->debug(
                "Selected image {$result['attachment_id']} with total score {$result['total_score']} for post {$post->ID}",
                array(
                    'algorithm_scores' => $result['algorithm_scores'] ?? array(),
                    'algorithm_count' => $result['algorithm_count'] ?? 0,
                    'method' => $method,
                    'post_analysis_used' => $post_analysis !== null
                ),
                $post->ID
            );

            return $result['attachment_id'];
        }

        // Fallback to legacy method if algorithms fail
        $this->logger->debug(
            "Algorithm-based selection failed, trying legacy method",
            array( 'method' => $method ),
            $post->ID
        );

        return $this->find_image_for_post( $post, $method );
    }

    /**
     * Get enabled algorithms for specific method
     *
     * @param string $method Selection method
     * @return array Enabled algorithms
     * @since 1.0.0
     */
    private function get_enabled_algorithms_for_method( $method ) {
        $all_algorithms = array_keys( $this->algorithms->get_algorithms() );

        // Method-specific algorithm preferences
        $method_preferences = array(
            'smart_analysis' => array( 'smart_content_analysis', 'semantic_matching', 'category_based' ),
            'first_content_image' => array( 'first_quality_image', 'smart_content_analysis' ),
            'best_quality' => array( 'first_quality_image', 'smart_content_analysis' ),
            'most_relevant' => array( 'semantic_matching', 'category_based', 'smart_content_analysis' ),
        );

        if ( isset( $method_preferences[ $method ] ) ) {
            return $method_preferences[ $method ];
        }

        // Default: use all enabled algorithms
        return $all_algorithms;
    }

    /**
     * Get minimum score threshold from settings
     *
     * @return int Minimum score threshold
     * @since 1.0.0
     */
    private function get_minimum_score_threshold() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        return isset( $settings['min_image_score'] ) ? intval( $settings['min_image_score'] ) : 30;
    }

    /**
     * Filter candidates based on selection method preference
     *
     * @param array  $candidates Array of image candidates
     * @param string $method Selection method
     * @return array Filtered candidates
     * @since 1.0.0
     */
    private function filter_candidates_by_method( $candidates, $method ) {
        switch ( $method ) {
            case 'first_content_image':
                return array_filter( $candidates, function( $c ) {
                    return $c['source'] === 'content';
                } );

            case 'first_gallery_image':
                return array_filter( $candidates, function( $c ) {
                    return $c['source'] === 'gallery';
                } );

            case 'attached_images':
                return array_filter( $candidates, function( $c ) {
                    return $c['source'] === 'attached';
                } );

            case 'best_quality':
                // Return all candidates sorted by quality score
                usort( $candidates, function( $a, $b ) {
                    return $b['scores']['quality'] <=> $a['scores']['quality'];
                } );
                return $candidates;

            case 'most_relevant':
                // Return all candidates sorted by relevance score
                usort( $candidates, function( $a, $b ) {
                    return $b['scores']['relevance'] <=> $a['scores']['relevance'];
                } );
                return $candidates;

            default:
                return $candidates;
        }
    }

    /**
     * Find an image for a post using the specified method (legacy)
     *
     * @param WP_Post $post Post object
     * @param string  $method Image selection method
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function find_image_for_post( $post, $method ) {
        switch ( $method ) {
            case 'first_content_image':
                return $this->get_first_content_image( $post );

            case 'first_gallery_image':
                return $this->get_first_gallery_image( $post );

            case 'attached_images':
                return $this->get_first_attached_image( $post );

            case 'media_library':
                return $this->get_random_media_library_image();

            case 'external_api':
                return $this->get_image_from_external_api( $post );

            default:
                return false;
        }
    }

    /**
     * Get the first image from post content with advanced analysis
     *
     * @param WP_Post $post Post object
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function get_first_content_image( $post ) {
        $content = $post->post_content;

        // Parse content for images with detailed analysis
        $images = $this->extract_images_from_content( $content );

        if ( empty( $images ) ) {
            return false;
        }

        // Score and rank images
        $scored_images = $this->score_content_images( $images, $post );

        if ( empty( $scored_images ) ) {
            return false;
        }

        // Return the highest scoring image
        return $scored_images[0]['attachment_id'];
    }

    /**
     * Extract all images from post content with metadata
     *
     * @param string $content Post content
     * @return array Array of image data
     * @since 1.0.0
     */
    private function extract_images_from_content( $content ) {
        $images = array();

        // Look for img tags with comprehensive attribute extraction
        preg_match_all( '/<img[^>]+>/i', $content, $matches );

        if ( empty( $matches[0] ) ) {
            return $images;
        }

        foreach ( $matches[0] as $index => $img_tag ) {
            $image_data = $this->parse_image_tag( $img_tag, $index );

            if ( $image_data ) {
                $images[] = $image_data;
            }
        }

        return $images;
    }

    /**
     * Parse individual image tag and extract metadata
     *
     * @param string $img_tag Image tag HTML
     * @param int    $position Position in content
     * @return array|false Image data or false if invalid
     * @since 1.0.0
     */
    private function parse_image_tag( $img_tag, $position ) {
        $image_data = array(
            'tag' => $img_tag,
            'position' => $position,
            'attachment_id' => null,
            'src' => null,
            'alt' => null,
            'title' => null,
            'width' => null,
            'height' => null,
            'class' => null,
            'is_local' => false,
        );

        // Extract src attribute
        if ( preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $src_match ) ) {
            $image_data['src'] = $src_match[1];
        }

        // Extract alt attribute
        if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match ) ) {
            $image_data['alt'] = $alt_match[1];
        }

        // Extract title attribute
        if ( preg_match( '/title=["\']([^"\']*)["\']/', $img_tag, $title_match ) ) {
            $image_data['title'] = $title_match[1];
        }

        // Extract width and height
        if ( preg_match( '/width=["\'](\d+)["\']/', $img_tag, $width_match ) ) {
            $image_data['width'] = intval( $width_match[1] );
        }

        if ( preg_match( '/height=["\'](\d+)["\']/', $img_tag, $height_match ) ) {
            $image_data['height'] = intval( $height_match[1] );
        }

        // Extract class attribute
        if ( preg_match( '/class=["\']([^"\']*)["\']/', $img_tag, $class_match ) ) {
            $image_data['class'] = $class_match[1];
        }

        // Try to get attachment ID from URL
        if ( $image_data['src'] ) {
            $attachment_id = attachment_url_to_postid( $image_data['src'] );

            if ( $attachment_id ) {
                $image_data['attachment_id'] = $attachment_id;
                $image_data['is_local'] = true;
            }
        }

        // Extract attachment ID from wp-image-* class
        if ( $image_data['class'] && preg_match( '/wp-image-(\d+)/', $image_data['class'], $class_match ) ) {
            $attachment_id = intval( $class_match[1] );

            if ( $attachment_id && wp_attachment_is_image( $attachment_id ) ) {
                $image_data['attachment_id'] = $attachment_id;
                $image_data['is_local'] = true;
            }
        }

        // Only return if we have a valid local attachment
        if ( $image_data['attachment_id'] && $image_data['is_local'] ) {
            return $image_data;
        }

        return false;
    }

    /**
     * Score and rank content images based on quality and relevance
     *
     * @param array   $images Array of image data
     * @param WP_Post $post Post object for context
     * @return array Sorted array of images by score (highest first)
     * @since 1.0.0
     */
    private function score_content_images( $images, $post ) {
        $scored_images = array();

        foreach ( $images as $image ) {
            $score = $this->calculate_image_score( $image, $post );

            if ( $score > 0 ) {
                $scored_images[] = array(
                    'attachment_id' => $image['attachment_id'],
                    'score' => $score,
                    'data' => $image,
                );
            }
        }

        // Sort by score (highest first)
        usort( $scored_images, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );

        return $scored_images;
    }

    /**
     * Calculate quality score for an image
     *
     * @param array   $image Image data
     * @param WP_Post $post Post object for context
     * @return float Image score (0-100)
     * @since 1.0.0
     */
    private function calculate_image_score( $image, $post ) {
        $score = 0;
        $attachment_id = $image['attachment_id'];

        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            return 0;
        }

        // Get image metadata
        $metadata = wp_get_attachment_metadata( $attachment_id );
        if ( ! $metadata ) {
            return 0;
        }

        // Base score for having valid metadata
        $score += 10;

        // Dimension scoring (prefer larger images)
        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;

        if ( $width >= 1200 && $height >= 800 ) {
            $score += 30; // High resolution
        } elseif ( $width >= 800 && $height >= 600 ) {
            $score += 20; // Medium resolution
        } elseif ( $width >= 400 && $height >= 300 ) {
            $score += 10; // Minimum acceptable
        } else {
            $score -= 20; // Too small
        }

        // Aspect ratio scoring (prefer landscape or square)
        if ( $width > 0 && $height > 0 ) {
            $aspect_ratio = $width / $height;

            if ( $aspect_ratio >= 1.2 && $aspect_ratio <= 2.0 ) {
                $score += 15; // Good landscape ratio
            } elseif ( $aspect_ratio >= 0.8 && $aspect_ratio <= 1.2 ) {
                $score += 10; // Square-ish
            } else {
                $score -= 5; // Poor aspect ratio
            }
        }

        // Position scoring (prefer images near the beginning)
        $position_score = max( 0, 15 - ( $image['position'] * 2 ) );
        $score += $position_score;

        // Alt text scoring (prefer descriptive alt text)
        if ( ! empty( $image['alt'] ) ) {
            $alt_length = strlen( $image['alt'] );
            if ( $alt_length > 10 && $alt_length < 100 ) {
                $score += 10; // Good alt text length
            } elseif ( $alt_length > 0 ) {
                $score += 5; // Has alt text
            }

            // Bonus for alt text relevance to post title
            if ( $this->text_similarity( $image['alt'], $post->post_title ) > 0.3 ) {
                $score += 10;
            }
        } else {
            $score -= 5; // No alt text
        }

        // File size scoring (prefer reasonable file sizes)
        $file_size = $this->get_attachment_file_size( $attachment_id );
        if ( $file_size ) {
            if ( $file_size > 50000 && $file_size < 500000 ) { // 50KB - 500KB
                $score += 10;
            } elseif ( $file_size > 500000 && $file_size < 2000000 ) { // 500KB - 2MB
                $score += 5;
            } elseif ( $file_size > 2000000 ) { // > 2MB
                $score -= 10; // Too large
            }
        }

        // Image format scoring
        $mime_type = get_post_mime_type( $attachment_id );
        switch ( $mime_type ) {
            case 'image/jpeg':
            case 'image/jpg':
                $score += 5; // Preferred format
                break;
            case 'image/png':
                $score += 3; // Good format
                break;
            case 'image/webp':
                $score += 8; // Modern format
                break;
            case 'image/gif':
                $score -= 5; // Usually not ideal for featured images
                break;
        }

        // Content relevance scoring
        $relevance_score = $this->calculate_content_relevance( $image, $post );
        $score += $relevance_score;

        return max( 0, $score );
    }

    /**
     * Calculate content relevance score
     *
     * @param array   $image Image data
     * @param WP_Post $post Post object
     * @return float Relevance score
     * @since 1.0.0
     */
    private function calculate_content_relevance( $image, $post ) {
        $score = 0;

        // Check image filename relevance
        $attachment = get_post( $image['attachment_id'] );
        if ( $attachment ) {
            $filename = $attachment->post_name;
            $title_similarity = $this->text_similarity( $filename, $post->post_title );
            $score += $title_similarity * 15;

            // Check image caption/description
            if ( ! empty( $attachment->post_excerpt ) ) {
                $caption_similarity = $this->text_similarity( $attachment->post_excerpt, $post->post_title );
                $score += $caption_similarity * 10;
            }
        }

        // Check surrounding content context
        $context_score = $this->analyze_image_context( $image, $post );
        $score += $context_score;

        return $score;
    }

    /**
     * Analyze image context within post content
     *
     * @param array   $image Image data
     * @param WP_Post $post Post object
     * @return float Context score
     * @since 1.0.0
     */
    private function analyze_image_context( $image, $post ) {
        $score = 0;
        $content = $post->post_content;

        // Find the image position in content
        $img_pos = strpos( $content, $image['tag'] );
        if ( $img_pos === false ) {
            return 0;
        }

        // Extract surrounding text (200 chars before and after)
        $context_start = max( 0, $img_pos - 200 );
        $context_end = min( strlen( $content ), $img_pos + strlen( $image['tag'] ) + 200 );
        $context = substr( $content, $context_start, $context_end - $context_start );

        // Remove HTML tags for analysis
        $context_text = wp_strip_all_tags( $context );

        // Check if image is in first paragraph (higher relevance)
        $first_para_end = strpos( $content, '</p>' );
        if ( $first_para_end && $img_pos < $first_para_end ) {
            $score += 15;
        }

        // Check for relevant keywords in surrounding text
        $title_words = $this->extract_keywords( $post->post_title );
        $context_words = $this->extract_keywords( $context_text );

        $common_words = array_intersect( $title_words, $context_words );
        $score += count( $common_words ) * 2;

        return $score;
    }

    /**
     * Calculate text similarity between two strings
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     * @since 1.0.0
     */
    private function text_similarity( $text1, $text2 ) {
        if ( empty( $text1 ) || empty( $text2 ) ) {
            return 0;
        }

        // Normalize texts
        $text1 = strtolower( wp_strip_all_tags( $text1 ) );
        $text2 = strtolower( wp_strip_all_tags( $text2 ) );

        // Extract keywords
        $words1 = $this->extract_keywords( $text1 );
        $words2 = $this->extract_keywords( $text2 );

        if ( empty( $words1 ) || empty( $words2 ) ) {
            return 0;
        }

        // Calculate Jaccard similarity
        $intersection = array_intersect( $words1, $words2 );
        $union = array_unique( array_merge( $words1, $words2 ) );

        return count( $intersection ) / count( $union );
    }

    /**
     * Extract meaningful keywords from text
     *
     * @param string $text Input text
     * @return array Array of keywords
     * @since 1.0.0
     */
    private function extract_keywords( $text ) {
        // Common stop words to exclude
        $stop_words = array(
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'
        );

        // Clean and split text
        $text = preg_replace( '/[^\w\s]/', ' ', $text );
        $words = preg_split( '/\s+/', $text );

        // Filter words
        $keywords = array();
        foreach ( $words as $word ) {
            $word = trim( strtolower( $word ) );

            // Skip short words, numbers, and stop words
            if ( strlen( $word ) > 2 && ! is_numeric( $word ) && ! in_array( $word, $stop_words ) ) {
                $keywords[] = $word;
            }
        }

        return array_unique( $keywords );
    }

    /**
     * Get attachment file size
     *
     * @param int $attachment_id Attachment ID
     * @return int|false File size in bytes or false if not found
     * @since 1.0.0
     */
    private function get_attachment_file_size( $attachment_id ) {
        $file_path = get_attached_file( $attachment_id );

        if ( $file_path && file_exists( $file_path ) ) {
            return filesize( $file_path );
        }

        return false;
    }

    /**
     * Get the first image from a gallery shortcode
     *
     * @param WP_Post $post Post object
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function get_first_gallery_image( $post ) {
        $content = $post->post_content;
        
        // Look for gallery shortcodes
        if ( preg_match( '/\[gallery[^\]]*\]/', $content, $matches ) ) {
            $shortcode = $matches[0];
            
            // Parse shortcode attributes
            $atts = shortcode_parse_atts( $shortcode );
            
            if ( isset( $atts['ids'] ) ) {
                $ids = explode( ',', $atts['ids'] );
                $first_id = intval( trim( $ids[0] ) );
                
                if ( $first_id && wp_attachment_is_image( $first_id ) ) {
                    return $first_id;
                }
            }
        }
        
        return false;
    }

    /**
     * Get the first image attached to the post
     *
     * @param WP_Post $post Post object
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function get_first_attached_image( $post ) {
        $attachments = get_attached_media( 'image', $post->ID );
        
        if ( ! empty( $attachments ) ) {
            $first_attachment = reset( $attachments );
            return $first_attachment->ID;
        }
        
        return false;
    }

    /**
     * Get a random image from the media library
     *
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function get_random_media_library_image() {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'orderby' => 'rand',
        );
        
        $images = get_posts( $args );
        
        if ( ! empty( $images ) ) {
            return $images[0]->ID;
        }
        
        return false;
    }

    /**
     * Get an image from an external API (placeholder for future implementation)
     *
     * @param WP_Post $post Post object
     * @return int|false Image attachment ID or false if not found
     * @since 1.0.0
     */
    private function get_image_from_external_api( $post ) {
        // This is a placeholder for future implementation
        // Could integrate with services like Unsplash, Pixabay, etc.
        
        $this->logger->debug( 'External API image selection not yet implemented', null, $post->ID );
        return false;
    }

    /**
     * Process a batch of posts
     *
     * @param array $post_ids Array of post IDs to process
     * @return array Results array with success/failure counts
     * @since 1.0.0
     */
    public function process_batch( $post_ids ) {
        $results = array(
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        foreach ( $post_ids as $post_id ) {
            $results['processed']++;
            
            if ( $this->process_post( $post_id ) ) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $post_id;
            }
        }

        return $results;
    }

    /**
     * Validate image for use as featured image
     *
     * @param int $attachment_id Attachment ID
     * @return bool True if valid, false otherwise
     * @since 1.0.0
     */
    private function validate_image( $attachment_id ) {
        // Check if attachment exists and is an image
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return false;
        }

        // Get image metadata
        $metadata = wp_get_attachment_metadata( $attachment_id );
        
        if ( ! $metadata ) {
            return false;
        }

        // Check minimum dimensions (configurable via settings)
        $settings = get_option( 'auto_featured_image_settings', array() );
        $min_width = isset( $settings['min_image_width'] ) ? $settings['min_image_width'] : 300;
        $min_height = isset( $settings['min_image_height'] ) ? $settings['min_image_height'] : 200;

        if ( $metadata['width'] < $min_width || $metadata['height'] < $min_height ) {
            return false;
        }

        return true;
    }

    /**
     * Get processing statistics
     *
     * @return array Statistics array
     * @since 1.0.0
     */
    public function get_stats() {
        $stats = get_option( 'auto_featured_image_stats', array(
            'total_processed' => 0,
            'total_successful' => 0,
            'total_failed' => 0,
            'last_run' => null,
            'total_runtime' => 0,
        ) );

        return $stats;
    }

    /**
     * Update processing statistics
     *
     * @param array $new_stats New statistics to add
     * @since 1.0.0
     */
    public function update_stats( $new_stats ) {
        $current_stats = $this->get_stats();
        
        $updated_stats = array(
            'total_processed' => $current_stats['total_processed'] + ( $new_stats['processed'] ?? 0 ),
            'total_successful' => $current_stats['total_successful'] + ( $new_stats['successful'] ?? 0 ),
            'total_failed' => $current_stats['total_failed'] + ( $new_stats['failed'] ?? 0 ),
            'last_run' => current_time( 'mysql' ),
            'total_runtime' => $current_stats['total_runtime'] + ( $new_stats['runtime'] ?? 0 ),
        );

        update_option( 'auto_featured_image_stats', $updated_stats );
    }

    /**
     * Enhance image candidates with post analysis context
     *
     * @param array $candidates Image candidates
     * @param array $post_analysis Post analysis data
     * @return array Enhanced candidates
     * @since 1.0.0
     */
    private function enhance_candidates_with_context( $candidates, $post_analysis ) {
        foreach ( $candidates as &$candidate ) {
            // Add context-based scoring
            $context_score = $this->calculate_context_score( $candidate, $post_analysis );
            $candidate['scores']['context'] = $context_score;
            $candidate['total_score'] += $context_score;
        }

        // Re-sort by updated total score
        usort( $candidates, function( $a, $b ) {
            return $b['total_score'] <=> $a['total_score'];
        } );

        return $candidates;
    }

    /**
     * Calculate context-based score for image candidate
     *
     * @param array $candidate Image candidate
     * @param array $post_analysis Post analysis data
     * @return float Context score
     * @since 1.0.0
     */
    private function calculate_context_score( $candidate, $post_analysis ) {
        $score = 0;

        // Content type bonus
        if ( isset( $post_analysis['content_structure']['has_blocks'] ) && $post_analysis['content_structure']['has_blocks'] ) {
            $score += 5; // Modern block editor content
        }

        // Word count consideration
        $word_count = $post_analysis['word_count'] ?? 0;
        if ( $word_count > 1000 ) {
            $score += 10; // Long-form content benefits from featured images
        } elseif ( $word_count > 500 ) {
            $score += 5; // Medium-length content
        }

        // SEO factors
        if ( isset( $post_analysis['seo_analysis']['has_meta_description'] ) && $post_analysis['seo_analysis']['has_meta_description'] ) {
            $score += 3; // SEO-optimized content
        }

        // Content topics relevance
        if ( isset( $post_analysis['content_analysis']['content_topics'] ) ) {
            $topics = $post_analysis['content_analysis']['content_topics'];
            $image_alt = $candidate['alt'] ?? '';

            foreach ( $topics as $topic ) {
                if ( stripos( $image_alt, $topic ) !== false ) {
                    $score += 8; // Image alt text matches content topics
                    break;
                }
            }
        }

        // Image opportunity scoring
        if ( isset( $post_analysis['image_opportunities']['needs_featured_image'] ) && $post_analysis['image_opportunities']['needs_featured_image'] ) {
            $score += 15; // High priority for posts that need featured images
        }

        // Reading time consideration
        $reading_time = $post_analysis['reading_time'] ?? 0;
        if ( $reading_time > 5 ) {
            $score += 8; // Longer articles benefit more from featured images
        }

        return $score;
    }

    /**
     * Get analysis summary for logging
     *
     * @param array $post_analysis Post analysis data
     * @return array Summary data
     * @since 1.0.0
     */
    private function get_analysis_summary( $post_analysis ) {
        if ( ! $post_analysis ) {
            return null;
        }

        return array(
            'word_count' => $post_analysis['word_count'] ?? 0,
            'reading_time' => $post_analysis['reading_time'] ?? 0,
            'has_blocks' => $post_analysis['content_structure']['has_blocks'] ?? false,
            'image_count' => $post_analysis['image_opportunities']['content_images_count'] ?? 0,
            'needs_featured_image' => $post_analysis['image_opportunities']['needs_featured_image'] ?? true,
            'content_topics_count' => count( $post_analysis['content_analysis']['content_topics'] ?? array() ),
        );
    }
}
