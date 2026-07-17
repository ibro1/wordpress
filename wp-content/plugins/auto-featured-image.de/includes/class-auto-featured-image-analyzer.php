<?php
/**
 * Image Analyzer Class
 *
 * Advanced image analysis and quality assessment for the Auto Featured Image plugin.
 * Handles image detection, quality scoring, and content relevance analysis.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Analyzer Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Analyzer {

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Analysis cache
     *
     * @var array
     * @since 1.0.0
     */
    private $cache = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Auto_Featured_Image_Logger();
    }

    /**
     * Analyze post content for optimal featured image candidates
     *
     * @param WP_Post $post Post object to analyze
     * @return array Array of image candidates with scores
     * @since 1.0.0
     */
    public function analyze_post_images( $post ) {
        $cache_key = 'post_images_' . $post->ID;
        
        if ( isset( $this->cache[ $cache_key ] ) ) {
            return $this->cache[ $cache_key ];
        }

        $candidates = array();
        
        // Analyze content images
        $content_images = $this->find_content_images( $post );
        $candidates = array_merge( $candidates, $content_images );
        
        // Analyze attached images
        $attached_images = $this->find_attached_images( $post );
        $candidates = array_merge( $candidates, $attached_images );
        
        // Analyze gallery images
        $gallery_images = $this->find_gallery_images( $post );
        $candidates = array_merge( $candidates, $gallery_images );
        
        // Remove duplicates and score all candidates
        $candidates = $this->deduplicate_candidates( $candidates );
        $scored_candidates = $this->score_image_candidates( $candidates, $post );
        
        // Sort by score (highest first)
        usort( $scored_candidates, function( $a, $b ) {
            return $b['total_score'] <=> $a['total_score'];
        } );
        
        $this->cache[ $cache_key ] = $scored_candidates;
        
        $this->logger->debug( 
            "Analyzed {$post->ID}: found " . count( $scored_candidates ) . " image candidates",
            array( 'candidates' => count( $scored_candidates ) ),
            $post->ID
        );
        
        return $scored_candidates;
    }

    /**
     * Find images in post content
     *
     * @param WP_Post $post Post object
     * @return array Array of image candidates
     * @since 1.0.0
     */
    private function find_content_images( $post ) {
        $candidates = array();
        $content = $post->post_content;
        
        // Find all img tags
        preg_match_all( '/<img[^>]+>/i', $content, $matches, PREG_OFFSET_CAPTURE );
        
        foreach ( $matches[0] as $index => $match ) {
            $img_tag = $match[0];
            $position = $match[1];
            
            $candidate = $this->parse_image_tag( $img_tag, $position, 'content' );
            if ( $candidate ) {
                $candidates[] = $candidate;
            }
        }
        
        return $candidates;
    }

    /**
     * Find images attached to the post
     *
     * @param WP_Post $post Post object
     * @return array Array of image candidates
     * @since 1.0.0
     */
    private function find_attached_images( $post ) {
        $candidates = array();
        
        $attachments = get_attached_media( 'image', $post->ID );
        
        foreach ( $attachments as $index => $attachment ) {
            $candidates[] = array(
                'attachment_id' => $attachment->ID,
                'source' => 'attached',
                'position' => $index,
                'url' => wp_get_attachment_url( $attachment->ID ),
                'metadata' => wp_get_attachment_metadata( $attachment->ID ),
                'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
                'caption' => $attachment->post_excerpt,
                'description' => $attachment->post_content,
                'title' => $attachment->post_title,
            );
        }
        
        return $candidates;
    }

    /**
     * Find images in gallery shortcodes
     *
     * @param WP_Post $post Post object
     * @return array Array of image candidates
     * @since 1.0.0
     */
    private function find_gallery_images( $post ) {
        $candidates = array();
        $content = $post->post_content;
        
        // Find gallery shortcodes
        preg_match_all( '/\[gallery[^\]]*\]/', $content, $matches, PREG_OFFSET_CAPTURE );
        
        foreach ( $matches[0] as $match ) {
            $shortcode = $match[0];
            $position = $match[1];
            
            // Parse shortcode attributes
            $atts = shortcode_parse_atts( $shortcode );
            
            if ( isset( $atts['ids'] ) ) {
                $ids = array_map( 'intval', explode( ',', $atts['ids'] ) );
                
                foreach ( $ids as $index => $attachment_id ) {
                    if ( wp_attachment_is_image( $attachment_id ) ) {
                        $candidates[] = array(
                            'attachment_id' => $attachment_id,
                            'source' => 'gallery',
                            'position' => $position + $index,
                            'url' => wp_get_attachment_url( $attachment_id ),
                            'metadata' => wp_get_attachment_metadata( $attachment_id ),
                            'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
                            'caption' => get_post( $attachment_id )->post_excerpt,
                            'description' => get_post( $attachment_id )->post_content,
                            'title' => get_post( $attachment_id )->post_title,
                        );
                    }
                }
            }
        }
        
        return $candidates;
    }

    /**
     * Parse image tag and extract metadata
     *
     * @param string $img_tag Image tag HTML
     * @param int    $position Position in content
     * @param string $source Source type
     * @return array|false Image candidate data or false if invalid
     * @since 1.0.0
     */
    private function parse_image_tag( $img_tag, $position, $source ) {
        $candidate = array(
            'source' => $source,
            'position' => $position,
            'tag' => $img_tag,
            'attachment_id' => null,
            'url' => null,
            'alt' => null,
            'title' => null,
            'width' => null,
            'height' => null,
            'class' => null,
        );
        
        // Extract attributes
        if ( preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $match ) ) {
            $candidate['url'] = $match[1];
        }
        
        if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $match ) ) {
            $candidate['alt'] = $match[1];
        }
        
        if ( preg_match( '/title=["\']([^"\']*)["\']/', $img_tag, $match ) ) {
            $candidate['title'] = $match[1];
        }
        
        if ( preg_match( '/width=["\'](\d+)["\']/', $img_tag, $match ) ) {
            $candidate['width'] = intval( $match[1] );
        }
        
        if ( preg_match( '/height=["\'](\d+)["\']/', $img_tag, $match ) ) {
            $candidate['height'] = intval( $match[1] );
        }
        
        if ( preg_match( '/class=["\']([^"\']*)["\']/', $img_tag, $match ) ) {
            $candidate['class'] = $match[1];
        }
        
        // Try to get attachment ID
        if ( $candidate['url'] ) {
            $attachment_id = attachment_url_to_postid( $candidate['url'] );
            if ( $attachment_id ) {
                $candidate['attachment_id'] = $attachment_id;
                $candidate['metadata'] = wp_get_attachment_metadata( $attachment_id );
            }
        }
        
        // Extract from wp-image class
        if ( $candidate['class'] && preg_match( '/wp-image-(\d+)/', $candidate['class'], $match ) ) {
            $attachment_id = intval( $match[1] );
            if ( wp_attachment_is_image( $attachment_id ) ) {
                $candidate['attachment_id'] = $attachment_id;
                $candidate['metadata'] = wp_get_attachment_metadata( $attachment_id );
            }
        }
        
        // Only return if we have a valid attachment
        return $candidate['attachment_id'] ? $candidate : false;
    }

    /**
     * Remove duplicate candidates
     *
     * @param array $candidates Array of image candidates
     * @return array Deduplicated candidates
     * @since 1.0.0
     */
    private function deduplicate_candidates( $candidates ) {
        $seen = array();
        $unique = array();
        
        foreach ( $candidates as $candidate ) {
            $key = $candidate['attachment_id'];
            
            if ( ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $unique[] = $candidate;
            }
        }
        
        return $unique;
    }

    /**
     * Score image candidates for featured image suitability
     *
     * @param array   $candidates Array of image candidates
     * @param WP_Post $post Post object for context
     * @return array Scored candidates
     * @since 1.0.0
     */
    private function score_image_candidates( $candidates, $post ) {
        $scored = array();
        
        foreach ( $candidates as $candidate ) {
            $scores = array(
                'quality' => $this->score_image_quality( $candidate ),
                'relevance' => $this->score_content_relevance( $candidate, $post ),
                'position' => $this->score_position( $candidate ),
                'metadata' => $this->score_metadata( $candidate ),
            );
            
            $total_score = array_sum( $scores );
            
            $scored[] = array_merge( $candidate, array(
                'scores' => $scores,
                'total_score' => $total_score,
            ) );
        }
        
        return $scored;
    }

    /**
     * Score image quality based on technical attributes
     *
     * @param array $candidate Image candidate
     * @return float Quality score
     * @since 1.0.0
     */
    private function score_image_quality( $candidate ) {
        $score = 0;
        $metadata = $candidate['metadata'] ?? array();
        
        if ( empty( $metadata ) ) {
            return 0;
        }
        
        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;
        
        // Dimension scoring
        if ( $width >= 1200 && $height >= 800 ) {
            $score += 40;
        } elseif ( $width >= 800 && $height >= 600 ) {
            $score += 30;
        } elseif ( $width >= 600 && $height >= 400 ) {
            $score += 20;
        } elseif ( $width >= 400 && $height >= 300 ) {
            $score += 10;
        } else {
            $score -= 20;
        }
        
        // Aspect ratio scoring
        if ( $width > 0 && $height > 0 ) {
            $ratio = $width / $height;
            if ( $ratio >= 1.2 && $ratio <= 2.0 ) {
                $score += 20; // Good landscape
            } elseif ( $ratio >= 0.8 && $ratio <= 1.2 ) {
                $score += 15; // Square-ish
            } else {
                $score -= 10; // Poor ratio
            }
        }
        
        // File format scoring
        if ( isset( $candidate['attachment_id'] ) ) {
            $mime_type = get_post_mime_type( $candidate['attachment_id'] );
            switch ( $mime_type ) {
                case 'image/webp':
                    $score += 15;
                    break;
                case 'image/jpeg':
                case 'image/jpg':
                    $score += 10;
                    break;
                case 'image/png':
                    $score += 8;
                    break;
                default:
                    $score += 5;
            }
        }
        
        return max( 0, $score );
    }

    /**
     * Score content relevance
     *
     * @param array   $candidate Image candidate
     * @param WP_Post $post Post object
     * @return float Relevance score
     * @since 1.0.0
     */
    private function score_content_relevance( $candidate, $post ) {
        $score = 0;
        
        // Alt text relevance
        if ( ! empty( $candidate['alt'] ) ) {
            $similarity = $this->calculate_text_similarity( $candidate['alt'], $post->post_title );
            $score += $similarity * 25;
        }
        
        // Caption relevance
        if ( ! empty( $candidate['caption'] ) ) {
            $similarity = $this->calculate_text_similarity( $candidate['caption'], $post->post_title );
            $score += $similarity * 20;
        }
        
        // Title relevance
        if ( ! empty( $candidate['title'] ) ) {
            $similarity = $this->calculate_text_similarity( $candidate['title'], $post->post_title );
            $score += $similarity * 15;
        }
        
        return $score;
    }

    /**
     * Score based on position in content
     *
     * @param array $candidate Image candidate
     * @return float Position score
     * @since 1.0.0
     */
    private function score_position( $candidate ) {
        $position = $candidate['position'] ?? 0;
        
        // Higher score for images appearing earlier
        if ( $position === 0 ) {
            return 30; // First image
        } elseif ( $position < 500 ) {
            return 25; // Early in content
        } elseif ( $position < 1000 ) {
            return 15; // Middle of content
        } else {
            return 5; // Late in content
        }
    }

    /**
     * Score based on metadata quality
     *
     * @param array $candidate Image candidate
     * @return float Metadata score
     * @since 1.0.0
     */
    private function score_metadata( $candidate ) {
        $score = 0;
        
        // Has alt text
        if ( ! empty( $candidate['alt'] ) ) {
            $score += 10;
        }
        
        // Has caption
        if ( ! empty( $candidate['caption'] ) ) {
            $score += 8;
        }
        
        // Has title
        if ( ! empty( $candidate['title'] ) ) {
            $score += 5;
        }
        
        // Source bonus
        switch ( $candidate['source'] ) {
            case 'content':
                $score += 10; // Content images are preferred
                break;
            case 'gallery':
                $score += 8; // Gallery images are good
                break;
            case 'attached':
                $score += 5; // Attached images are okay
                break;
        }
        
        return $score;
    }

    /**
     * Calculate text similarity using simple word matching
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     * @since 1.0.0
     */
    private function calculate_text_similarity( $text1, $text2 ) {
        if ( empty( $text1 ) || empty( $text2 ) ) {
            return 0;
        }
        
        $words1 = $this->extract_keywords( strtolower( $text1 ) );
        $words2 = $this->extract_keywords( strtolower( $text2 ) );
        
        if ( empty( $words1 ) || empty( $words2 ) ) {
            return 0;
        }
        
        $intersection = array_intersect( $words1, $words2 );
        $union = array_unique( array_merge( $words1, $words2 ) );
        
        return count( $intersection ) / count( $union );
    }

    /**
     * Extract keywords from text
     *
     * @param string $text Input text
     * @return array Keywords
     * @since 1.0.0
     */
    private function extract_keywords( $text ) {
        $stop_words = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );
        
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
     * Clear analysis cache
     *
     * @since 1.0.0
     */
    public function clear_cache() {
        $this->cache = array();
    }
}
