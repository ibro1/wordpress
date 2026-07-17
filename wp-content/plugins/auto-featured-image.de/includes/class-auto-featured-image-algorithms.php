<?php
/**
 * Image Assignment Algorithms Class
 *
 * Multiple image assignment algorithms with scoring, fallback mechanisms,
 * and intelligent selection strategies for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Algorithms Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Algorithms {

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
     * Available algorithms
     *
     * @var array
     * @since 1.0.0
     */
    private $algorithms = array();

    /**
     * Algorithm weights for scoring
     *
     * @var array
     * @since 1.0.0
     */
    private $algorithm_weights = array(
        'content_relevance' => 0.3,
        'image_quality' => 0.25,
        'position_priority' => 0.2,
        'semantic_match' => 0.15,
        'user_preference' => 0.1,
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Auto_Featured_Image_Logger();
        $this->analyzer = new Auto_Featured_Image_Analyzer();
        $this->init_algorithms();
        $this->load_algorithm_weights();
    }

    /**
     * Initialize available algorithms
     *
     * @since 1.0.0
     */
    private function init_algorithms() {
        $this->algorithms = array(
            'smart_content_analysis' => array(
                'name' => __( 'Smart Content Analysis', 'auto-featured-image' ),
                'description' => __( 'Analyzes content context and selects most relevant image', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_smart_content_analysis' ),
                'priority' => 1,
                'enabled' => true,
            ),
            'first_quality_image' => array(
                'name' => __( 'First Quality Image', 'auto-featured-image' ),
                'description' => __( 'Selects first high-quality image from content', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_first_quality_image' ),
                'priority' => 2,
                'enabled' => true,
            ),
            'semantic_matching' => array(
                'name' => __( 'Semantic Matching', 'auto-featured-image' ),
                'description' => __( 'Matches images based on semantic content analysis', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_semantic_matching' ),
                'priority' => 3,
                'enabled' => true,
            ),
            'category_based' => array(
                'name' => __( 'Category-Based Selection', 'auto-featured-image' ),
                'description' => __( 'Selects images based on post categories and tags', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_category_based' ),
                'priority' => 4,
                'enabled' => true,
            ),
            'user_preference_learning' => array(
                'name' => __( 'User Preference Learning', 'auto-featured-image' ),
                'description' => __( 'Learns from user selections and applies preferences', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_user_preference_learning' ),
                'priority' => 5,
                'enabled' => false, // Disabled by default, requires training data
            ),
            'fallback_media_library' => array(
                'name' => __( 'Media Library Fallback', 'auto-featured-image' ),
                'description' => __( 'Fallback to curated media library images', 'auto-featured-image' ),
                'callback' => array( $this, 'algorithm_fallback_media_library' ),
                'priority' => 99,
                'enabled' => true,
            ),
        );
    }

    /**
     * Load algorithm weights from settings
     *
     * @since 1.0.0
     */
    private function load_algorithm_weights() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        
        if ( isset( $settings['algorithm_weights'] ) && is_array( $settings['algorithm_weights'] ) ) {
            $this->algorithm_weights = array_merge( $this->algorithm_weights, $settings['algorithm_weights'] );
        }
    }

    /**
     * Find best image for post using multiple algorithms
     *
     * @param WP_Post $post Post object
     * @param array   $options Algorithm options
     * @return array|false Best image result or false if none found
     * @since 1.0.0
     */
    public function find_best_image( $post, $options = array() ) {
        $defaults = array(
            'algorithms' => array_keys( $this->algorithms ),
            'min_score' => 30,
            'max_attempts' => 3,
            'fallback_enabled' => true,
        );
        
        $options = wp_parse_args( $options, $defaults );
        
        // Get image candidates from analyzer
        $candidates = $this->analyzer->analyze_post_images( $post );
        
        if ( empty( $candidates ) && $options['fallback_enabled'] ) {
            // Try fallback algorithms if no content images found
            $candidates = $this->get_fallback_candidates( $post );
        }
        
        if ( empty( $candidates ) ) {
            $this->logger->warning( "No image candidates found for post {$post->ID}" );
            return false;
        }
        
        // Apply multiple algorithms and combine scores
        $algorithm_results = array();
        
        foreach ( $options['algorithms'] as $algorithm_name ) {
            if ( ! isset( $this->algorithms[ $algorithm_name ] ) || ! $this->algorithms[ $algorithm_name ]['enabled'] ) {
                continue;
            }
            
            $algorithm = $this->algorithms[ $algorithm_name ];
            $result = call_user_func( $algorithm['callback'], $post, $candidates, $options );
            
            if ( $result ) {
                $algorithm_results[ $algorithm_name ] = $result;
            }
        }
        
        if ( empty( $algorithm_results ) ) {
            $this->logger->warning( "No algorithms produced results for post {$post->ID}" );
            return false;
        }
        
        // Combine algorithm results using weighted scoring
        $final_result = $this->combine_algorithm_results( $algorithm_results, $post );
        
        if ( $final_result && $final_result['total_score'] >= $options['min_score'] ) {
            $this->logger->info( 
                "Selected image {$final_result['attachment_id']} for post {$post->ID} with score {$final_result['total_score']}",
                array(
                    'algorithm_scores' => $final_result['algorithm_scores'],
                    'winning_algorithms' => array_keys( $algorithm_results ),
                )
            );
            
            return $final_result;
        }
        
        $this->logger->warning( 
            "No image met minimum score threshold ({$options['min_score']}) for post {$post->ID}",
            array( 'best_score' => $final_result['total_score'] ?? 0 )
        );
        
        return false;
    }

    /**
     * Smart Content Analysis Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_smart_content_analysis( $post, $candidates, $options ) {
        $best_candidate = null;
        $best_score = 0;
        
        foreach ( $candidates as $candidate ) {
            $score = 0;
            
            // Content relevance scoring
            $content_score = $this->calculate_content_relevance_score( $candidate, $post );
            $score += $content_score * 0.4;
            
            // Position importance
            $position_score = $this->calculate_position_score( $candidate );
            $score += $position_score * 0.3;
            
            // Image quality
            $quality_score = $candidate['scores']['quality'] ?? 0;
            $score += $quality_score * 0.3;
            
            if ( $score > $best_score ) {
                $best_score = $score;
                $best_candidate = $candidate;
                $best_candidate['algorithm_score'] = $score;
                $best_candidate['algorithm_details'] = array(
                    'content_relevance' => $content_score,
                    'position_score' => $position_score,
                    'quality_score' => $quality_score,
                );
            }
        }
        
        return $best_candidate;
    }

    /**
     * First Quality Image Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_first_quality_image( $post, $candidates, $options ) {
        $min_quality_score = 50; // Minimum quality threshold
        
        // Sort by position (first in content gets priority)
        usort( $candidates, function( $a, $b ) {
            return ( $a['position'] ?? 999 ) <=> ( $b['position'] ?? 999 );
        } );
        
        foreach ( $candidates as $candidate ) {
            $quality_score = $candidate['scores']['quality'] ?? 0;
            
            if ( $quality_score >= $min_quality_score ) {
                $candidate['algorithm_score'] = $quality_score;
                $candidate['algorithm_details'] = array(
                    'quality_threshold_met' => true,
                    'position_in_content' => $candidate['position'] ?? 0,
                );
                
                return $candidate;
            }
        }
        
        return false;
    }

    /**
     * Semantic Matching Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_semantic_matching( $post, $candidates, $options ) {
        $post_keywords = $this->extract_post_keywords( $post );
        
        if ( empty( $post_keywords ) ) {
            return false;
        }
        
        $best_candidate = null;
        $best_score = 0;
        
        foreach ( $candidates as $candidate ) {
            $image_keywords = $this->extract_image_keywords( $candidate );
            $semantic_score = $this->calculate_semantic_similarity( $post_keywords, $image_keywords );
            
            // Boost score for images with descriptive alt text
            if ( ! empty( $candidate['alt'] ) && strlen( $candidate['alt'] ) > 10 ) {
                $semantic_score *= 1.2;
            }
            
            if ( $semantic_score > $best_score ) {
                $best_score = $semantic_score;
                $best_candidate = $candidate;
                $best_candidate['algorithm_score'] = $semantic_score;
                $best_candidate['algorithm_details'] = array(
                    'post_keywords' => $post_keywords,
                    'image_keywords' => $image_keywords,
                    'semantic_similarity' => $semantic_score,
                );
            }
        }
        
        return $best_candidate;
    }

    /**
     * Category-Based Selection Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_category_based( $post, $candidates, $options ) {
        $post_categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
        $post_tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
        
        $taxonomy_terms = array_merge( $post_categories, $post_tags );
        
        if ( empty( $taxonomy_terms ) ) {
            return false;
        }
        
        $best_candidate = null;
        $best_score = 0;
        
        foreach ( $candidates as $candidate ) {
            $score = 0;
            
            // Check image metadata for category/tag matches
            $image_text = implode( ' ', array_filter( array(
                $candidate['alt'] ?? '',
                $candidate['caption'] ?? '',
                $candidate['title'] ?? '',
            ) ) );
            
            foreach ( $taxonomy_terms as $term ) {
                if ( stripos( $image_text, $term ) !== false ) {
                    $score += 20; // Bonus for each matching term
                }
            }
            
            // Check image filename for matches
            if ( isset( $candidate['attachment_id'] ) ) {
                $attachment = get_post( $candidate['attachment_id'] );
                if ( $attachment ) {
                    foreach ( $taxonomy_terms as $term ) {
                        if ( stripos( $attachment->post_name, sanitize_title( $term ) ) !== false ) {
                            $score += 15; // Filename match bonus
                        }
                    }
                }
            }
            
            if ( $score > $best_score ) {
                $best_score = $score;
                $best_candidate = $candidate;
                $best_candidate['algorithm_score'] = $score;
                $best_candidate['algorithm_details'] = array(
                    'matched_terms' => $taxonomy_terms,
                    'category_matches' => $score / 20, // Approximate number of matches
                );
            }
        }
        
        return $best_candidate;
    }

    /**
     * User Preference Learning Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_user_preference_learning( $post, $candidates, $options ) {
        $user_preferences = $this->get_user_preferences();

        if ( empty( $user_preferences ) ) {
            return false; // No learning data available
        }

        $best_candidate = null;
        $best_score = 0;

        foreach ( $candidates as $candidate ) {
            $score = $this->calculate_preference_score( $candidate, $user_preferences );

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_candidate = $candidate;
                $best_candidate['algorithm_score'] = $score;
                $best_candidate['algorithm_details'] = array(
                    'preference_matches' => $this->get_preference_matches( $candidate, $user_preferences ),
                );
            }
        }

        return $best_candidate;
    }

    /**
     * Fallback Media Library Algorithm
     *
     * @param WP_Post $post Post object
     * @param array   $candidates Image candidates
     * @param array   $options Algorithm options
     * @return array|false Algorithm result
     * @since 1.0.0
     */
    public function algorithm_fallback_media_library( $post, $candidates, $options ) {
        // Get curated images from media library
        $fallback_images = $this->get_curated_fallback_images( $post );

        if ( empty( $fallback_images ) ) {
            return false;
        }

        // Select best fallback image based on post content
        $best_image = $this->select_best_fallback_image( $fallback_images, $post );

        if ( $best_image ) {
            $best_image['algorithm_score'] = 40; // Moderate score for fallback
            $best_image['algorithm_details'] = array(
                'fallback_source' => 'media_library',
                'selection_method' => 'content_matching',
            );
        }

        return $best_image;
    }

    /**
     * Get fallback image candidates
     *
     * @param WP_Post $post Post object
     * @return array Fallback candidates
     * @since 1.0.0
     */
    private function get_fallback_candidates( $post ) {
        $candidates = array();

        // Try attached images first
        $attachments = get_attached_media( 'image', $post->ID );
        foreach ( $attachments as $attachment ) {
            $candidates[] = array(
                'attachment_id' => $attachment->ID,
                'source' => 'attached_fallback',
                'url' => wp_get_attachment_url( $attachment->ID ),
                'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                'caption' => $attachment->post_excerpt,
                'title' => $attachment->post_title,
                'metadata' => wp_get_attachment_metadata( $attachment->ID ),
            );
        }

        // If no attached images, try media library
        if ( empty( $candidates ) ) {
            $media_candidates = $this->get_curated_fallback_images( $post );
            $candidates = array_merge( $candidates, $media_candidates );
        }

        return $candidates;
    }

    /**
     * Combine results from multiple algorithms
     *
     * @param array   $algorithm_results Results from each algorithm
     * @param WP_Post $post Post object
     * @return array|false Combined result
     * @since 1.0.0
     */
    private function combine_algorithm_results( $algorithm_results, $post ) {
        $image_scores = array();

        // Collect scores for each image from all algorithms
        foreach ( $algorithm_results as $algorithm_name => $result ) {
            $attachment_id = $result['attachment_id'];
            $algorithm_score = $result['algorithm_score'] ?? 0;

            if ( ! isset( $image_scores[ $attachment_id ] ) ) {
                $image_scores[ $attachment_id ] = array(
                    'candidate' => $result,
                    'algorithm_scores' => array(),
                    'total_score' => 0,
                    'algorithm_count' => 0,
                );
            }

            $image_scores[ $attachment_id ]['algorithm_scores'][ $algorithm_name ] = $algorithm_score;
            $image_scores[ $attachment_id ]['algorithm_count']++;
        }

        // Calculate weighted final scores
        foreach ( $image_scores as $attachment_id => &$score_data ) {
            $weighted_score = 0;
            $total_weight = 0;

            foreach ( $score_data['algorithm_scores'] as $algorithm_name => $score ) {
                $weight = $this->get_algorithm_weight( $algorithm_name );
                $weighted_score += $score * $weight;
                $total_weight += $weight;
            }

            // Normalize score
            if ( $total_weight > 0 ) {
                $score_data['total_score'] = $weighted_score / $total_weight;
            }

            // Bonus for multiple algorithm agreement
            if ( $score_data['algorithm_count'] > 1 ) {
                $score_data['total_score'] *= ( 1 + ( $score_data['algorithm_count'] - 1 ) * 0.1 );
            }
        }

        // Find best scoring image
        $best_image = null;
        $best_score = 0;

        foreach ( $image_scores as $attachment_id => $score_data ) {
            if ( $score_data['total_score'] > $best_score ) {
                $best_score = $score_data['total_score'];
                $best_image = $score_data['candidate'];
                $best_image['total_score'] = $score_data['total_score'];
                $best_image['algorithm_scores'] = $score_data['algorithm_scores'];
                $best_image['algorithm_count'] = $score_data['algorithm_count'];
            }
        }

        return $best_image;
    }

    /**
     * Get algorithm weight for scoring
     *
     * @param string $algorithm_name Algorithm name
     * @return float Weight value
     * @since 1.0.0
     */
    private function get_algorithm_weight( $algorithm_name ) {
        $weights = array(
            'smart_content_analysis' => 0.3,
            'first_quality_image' => 0.25,
            'semantic_matching' => 0.2,
            'category_based' => 0.15,
            'user_preference_learning' => 0.1,
            'fallback_media_library' => 0.05,
        );

        return $weights[ $algorithm_name ] ?? 0.1;
    }

    /**
     * Calculate content relevance score
     *
     * @param array   $candidate Image candidate
     * @param WP_Post $post Post object
     * @return float Relevance score
     * @since 1.0.0
     */
    private function calculate_content_relevance_score( $candidate, $post ) {
        $score = 0;

        // Alt text relevance
        if ( ! empty( $candidate['alt'] ) ) {
            $similarity = $this->calculate_text_similarity( $candidate['alt'], $post->post_title );
            $score += $similarity * 30;
        }

        // Caption relevance
        if ( ! empty( $candidate['caption'] ) ) {
            $similarity = $this->calculate_text_similarity( $candidate['caption'], $post->post_content );
            $score += $similarity * 20;
        }

        return $score;
    }

    /**
     * Calculate position score
     *
     * @param array $candidate Image candidate
     * @return float Position score
     * @since 1.0.0
     */
    private function calculate_position_score( $candidate ) {
        $position = $candidate['position'] ?? 999;

        // Higher score for images appearing earlier
        if ( $position === 0 ) {
            return 50; // First image
        } elseif ( $position < 500 ) {
            return 40; // Early in content
        } elseif ( $position < 1000 ) {
            return 25; // Middle of content
        } else {
            return 10; // Late in content
        }
    }

    /**
     * Extract keywords from post
     *
     * @param WP_Post $post Post object
     * @return array Keywords
     * @since 1.0.0
     */
    private function extract_post_keywords( $post ) {
        $text = $post->post_title . ' ' . wp_strip_all_tags( $post->post_content );
        return $this->extract_keywords_from_text( $text );
    }

    /**
     * Extract keywords from image
     *
     * @param array $candidate Image candidate
     * @return array Keywords
     * @since 1.0.0
     */
    private function extract_image_keywords( $candidate ) {
        $text = implode( ' ', array_filter( array(
            $candidate['alt'] ?? '',
            $candidate['caption'] ?? '',
            $candidate['title'] ?? '',
        ) ) );

        return $this->extract_keywords_from_text( $text );
    }

    /**
     * Extract keywords from text
     *
     * @param string $text Input text
     * @return array Keywords
     * @since 1.0.0
     */
    private function extract_keywords_from_text( $text ) {
        $stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );

        $text = strtolower( wp_strip_all_tags( $text ) );
        $text = preg_replace( '/[^\w\s]/', ' ', $text );
        $words = preg_split( '/\s+/', $text );

        $keywords = array();
        foreach ( $words as $word ) {
            $word = trim( $word );
            if ( strlen( $word ) > 2 && ! in_array( $word, $stop_words ) ) {
                $keywords[] = $word;
            }
        }

        return array_unique( $keywords );
    }

    /**
     * Calculate semantic similarity between keyword sets
     *
     * @param array $keywords1 First keyword set
     * @param array $keywords2 Second keyword set
     * @return float Similarity score
     * @since 1.0.0
     */
    private function calculate_semantic_similarity( $keywords1, $keywords2 ) {
        if ( empty( $keywords1 ) || empty( $keywords2 ) ) {
            return 0;
        }

        $intersection = array_intersect( $keywords1, $keywords2 );
        $union = array_unique( array_merge( $keywords1, $keywords2 ) );

        return ( count( $intersection ) / count( $union ) ) * 100;
    }

    /**
     * Calculate text similarity
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     * @since 1.0.0
     */
    private function calculate_text_similarity( $text1, $text2 ) {
        $keywords1 = $this->extract_keywords_from_text( $text1 );
        $keywords2 = $this->extract_keywords_from_text( $text2 );

        return $this->calculate_semantic_similarity( $keywords1, $keywords2 ) / 100;
    }

    /**
     * Get user preferences for learning algorithm
     *
     * @return array User preferences
     * @since 1.0.0
     */
    private function get_user_preferences() {
        return get_option( 'auto_featured_image_user_preferences', array() );
    }

    /**
     * Calculate preference score based on user learning data
     *
     * @param array $candidate Image candidate
     * @param array $preferences User preferences
     * @return float Preference score
     * @since 1.0.0
     */
    private function calculate_preference_score( $candidate, $preferences ) {
        $score = 0;

        // Preferred image dimensions
        if ( isset( $preferences['preferred_dimensions'] ) ) {
            $metadata = $candidate['metadata'] ?? array();
            $width = $metadata['width'] ?? 0;
            $height = $metadata['height'] ?? 0;

            $pref_width = $preferences['preferred_dimensions']['width'] ?? 800;
            $pref_height = $preferences['preferred_dimensions']['height'] ?? 600;

            $width_diff = abs( $width - $pref_width ) / $pref_width;
            $height_diff = abs( $height - $pref_height ) / $pref_height;

            $dimension_score = max( 0, 50 - ( ( $width_diff + $height_diff ) * 25 ) );
            $score += $dimension_score;
        }

        return $score;
    }

    /**
     * Get preference matches for candidate
     *
     * @param array $candidate Image candidate
     * @param array $preferences User preferences
     * @return array Preference matches
     * @since 1.0.0
     */
    private function get_preference_matches( $candidate, $preferences ) {
        return array(); // Placeholder for preference matching logic
    }

    /**
     * Get curated fallback images from media library
     *
     * @param WP_Post $post Post object
     * @return array Fallback images
     * @since 1.0.0
     */
    private function get_curated_fallback_images( $post ) {
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => 10,
            'orderby' => 'rand',
        );

        $images = get_posts( $args );
        $candidates = array();

        foreach ( $images as $image ) {
            $candidates[] = array(
                'attachment_id' => $image->ID,
                'source' => 'curated_fallback',
                'url' => wp_get_attachment_url( $image->ID ),
                'alt' => get_post_meta( $image->ID, '_wp_attachment_image_alt', true ),
                'caption' => $image->post_excerpt,
                'title' => $image->post_title,
                'metadata' => wp_get_attachment_metadata( $image->ID ),
            );
        }

        return $candidates;
    }

    /**
     * Select best fallback image for post
     *
     * @param array   $fallback_images Available fallback images
     * @param WP_Post $post Post object
     * @return array|false Best fallback image
     * @since 1.0.0
     */
    private function select_best_fallback_image( $fallback_images, $post ) {
        if ( empty( $fallback_images ) ) {
            return false;
        }

        // For now, return first available fallback
        return $fallback_images[0];
    }

    /**
     * Get available algorithms
     *
     * @return array Available algorithms
     * @since 1.0.0
     */
    public function get_algorithms() {
        return $this->algorithms;
    }

    /**
     * Enable/disable algorithm
     *
     * @param string $algorithm_name Algorithm name
     * @param bool   $enabled Whether to enable
     * @return bool Success status
     * @since 1.0.0
     */
    public function set_algorithm_enabled( $algorithm_name, $enabled ) {
        if ( ! isset( $this->algorithms[ $algorithm_name ] ) ) {
            return false;
        }

        $this->algorithms[ $algorithm_name ]['enabled'] = (bool) $enabled;

        // Save to settings
        $settings = get_option( 'auto_featured_image_settings', array() );
        $settings['enabled_algorithms'] = array();

        foreach ( $this->algorithms as $name => $algorithm ) {
            if ( $algorithm['enabled'] ) {
                $settings['enabled_algorithms'][] = $name;
            }
        }

        update_option( 'auto_featured_image_settings', $settings );

        return true;
    }

    /**
     * Update algorithm weights
     *
     * @param array $weights New weights
     * @since 1.0.0
     */
    public function update_algorithm_weights( $weights ) {
        $this->algorithm_weights = array_merge( $this->algorithm_weights, $weights );

        // Save to settings
        $settings = get_option( 'auto_featured_image_settings', array() );
        $settings['algorithm_weights'] = $this->algorithm_weights;
        update_option( 'auto_featured_image_settings', $settings );

        $this->logger->info( 'Algorithm weights updated', $weights );
    }
}
