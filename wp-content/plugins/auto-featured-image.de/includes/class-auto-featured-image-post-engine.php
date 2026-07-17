<?php
/**
 * Post Processing Engine Class
 *
 * Comprehensive post processing system with content parsing, metadata extraction,
 * and intelligent content analysis for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Post Engine Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Post_Engine {

    /**
     * Logger instance
     *
     * @var Auto_Featured_Image_Logger
     * @since 1.0.0
     */
    private $logger;

    /**
     * Processing cache
     *
     * @var array
     * @since 1.0.0
     */
    private $cache = array();

    /**
     * Content parsers
     *
     * @var array
     * @since 1.0.0
     */
    private $parsers = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->logger = new Auto_Featured_Image_Logger();
        $this->init_parsers();
    }

    /**
     * Initialize content parsers
     *
     * @since 1.0.0
     */
    private function init_parsers() {
        $this->parsers = array(
            'blocks' => array( $this, 'parse_gutenberg_blocks' ),
            'shortcodes' => array( $this, 'parse_shortcodes' ),
            'embeds' => array( $this, 'parse_embeds' ),
            'galleries' => array( $this, 'parse_galleries' ),
            'images' => array( $this, 'parse_images' ),
            'text' => array( $this, 'parse_text_content' ),
        );
    }

    /**
     * Process a post comprehensively
     *
     * @param int|WP_Post $post Post ID or post object
     * @return array Comprehensive post analysis
     * @since 1.0.0
     */
    public function process_post( $post ) {
        if ( is_numeric( $post ) ) {
            $post = get_post( $post );
        }

        if ( ! $post || $post->post_status !== 'publish' ) {
            return false;
        }

        $cache_key = 'post_analysis_' . $post->ID;
        
        if ( isset( $this->cache[ $cache_key ] ) ) {
            return $this->cache[ $cache_key ];
        }

        $analysis = array(
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
            'post_title' => $post->post_title,
            'post_excerpt' => $post->post_excerpt,
            'post_date' => $post->post_date,
            'word_count' => $this->count_words( $post->post_content ),
            'reading_time' => $this->estimate_reading_time( $post->post_content ),
            'content_structure' => $this->analyze_content_structure( $post ),
            'metadata' => $this->extract_post_metadata( $post ),
            'taxonomy_terms' => $this->extract_taxonomy_terms( $post ),
            'content_analysis' => $this->analyze_content( $post ),
            'image_opportunities' => $this->identify_image_opportunities( $post ),
            'seo_analysis' => $this->analyze_seo_factors( $post ),
            'processed_at' => current_time( 'mysql' ),
        );

        $this->cache[ $cache_key ] = $analysis;

        $this->logger->debug( 
            "Processed post {$post->ID}: {$analysis['word_count']} words, {$analysis['content_analysis']['paragraph_count']} paragraphs",
            array( 'analysis_summary' => $this->get_analysis_summary( $analysis ) ),
            $post->ID
        );

        return $analysis;
    }

    /**
     * Analyze content structure
     *
     * @param WP_Post $post Post object
     * @return array Content structure analysis
     * @since 1.0.0
     */
    private function analyze_content_structure( $post ) {
        $content = $post->post_content;
        $structure = array(
            'has_blocks' => has_blocks( $content ),
            'block_count' => 0,
            'blocks' => array(),
            'shortcodes' => array(),
            'embeds' => array(),
            'headings' => array(),
            'lists' => array(),
            'tables' => array(),
        );

        // Analyze Gutenberg blocks
        if ( $structure['has_blocks'] ) {
            $blocks = parse_blocks( $content );
            $structure['block_count'] = count( $blocks );
            $structure['blocks'] = $this->analyze_blocks( $blocks );
        }

        // Parse shortcodes
        $structure['shortcodes'] = $this->extract_shortcodes( $content );

        // Find embeds
        $structure['embeds'] = $this->extract_embeds( $content );

        // Analyze headings
        $structure['headings'] = $this->extract_headings( $content );

        // Find lists
        $structure['lists'] = $this->extract_lists( $content );

        // Find tables
        $structure['tables'] = $this->extract_tables( $content );

        return $structure;
    }

    /**
     * Analyze Gutenberg blocks
     *
     * @param array $blocks Array of parsed blocks
     * @return array Block analysis
     * @since 1.0.0
     */
    private function analyze_blocks( $blocks ) {
        $analysis = array(
            'types' => array(),
            'image_blocks' => array(),
            'gallery_blocks' => array(),
            'media_blocks' => array(),
            'text_blocks' => array(),
        );

        foreach ( $blocks as $block ) {
            $block_name = $block['blockName'] ?? 'unknown';
            
            // Count block types
            if ( ! isset( $analysis['types'][ $block_name ] ) ) {
                $analysis['types'][ $block_name ] = 0;
            }
            $analysis['types'][ $block_name ]++;

            // Categorize blocks
            if ( strpos( $block_name, 'image' ) !== false ) {
                $analysis['image_blocks'][] = $block;
            } elseif ( strpos( $block_name, 'gallery' ) !== false ) {
                $analysis['gallery_blocks'][] = $block;
            } elseif ( strpos( $block_name, 'media' ) !== false || strpos( $block_name, 'video' ) !== false ) {
                $analysis['media_blocks'][] = $block;
            } elseif ( in_array( $block_name, array( 'core/paragraph', 'core/heading', 'core/list' ) ) ) {
                $analysis['text_blocks'][] = $block;
            }

            // Recursively analyze inner blocks
            if ( ! empty( $block['innerBlocks'] ) ) {
                $inner_analysis = $this->analyze_blocks( $block['innerBlocks'] );
                $analysis = $this->merge_block_analysis( $analysis, $inner_analysis );
            }
        }

        return $analysis;
    }

    /**
     * Extract post metadata
     *
     * @param WP_Post $post Post object
     * @return array Post metadata
     * @since 1.0.0
     */
    private function extract_post_metadata( $post ) {
        $metadata = array(
            'custom_fields' => get_post_meta( $post->ID ),
            'featured_image' => get_post_thumbnail_id( $post->ID ),
            'author' => get_userdata( $post->post_author ),
            'comment_count' => $post->comment_count,
            'menu_order' => $post->menu_order,
            'post_parent' => $post->post_parent,
            'guid' => $post->guid,
            'post_name' => $post->post_name,
        );

        // Extract SEO metadata if available
        $metadata['seo'] = $this->extract_seo_metadata( $post );

        // Extract social metadata
        $metadata['social'] = $this->extract_social_metadata( $post );

        return $metadata;
    }

    /**
     * Extract taxonomy terms
     *
     * @param WP_Post $post Post object
     * @return array Taxonomy terms
     * @since 1.0.0
     */
    private function extract_taxonomy_terms( $post ) {
        $taxonomies = get_object_taxonomies( $post->post_type );
        $terms = array();

        foreach ( $taxonomies as $taxonomy ) {
            $post_terms = wp_get_post_terms( $post->ID, $taxonomy );
            
            if ( ! is_wp_error( $post_terms ) && ! empty( $post_terms ) ) {
                $terms[ $taxonomy ] = array();
                
                foreach ( $post_terms as $term ) {
                    $terms[ $taxonomy ][] = array(
                        'term_id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'description' => $term->description,
                        'count' => $term->count,
                    );
                }
            }
        }

        return $terms;
    }

    /**
     * Analyze content for insights
     *
     * @param WP_Post $post Post object
     * @return array Content analysis
     * @since 1.0.0
     */
    private function analyze_content( $post ) {
        $content = wp_strip_all_tags( $post->post_content );
        
        $analysis = array(
            'character_count' => strlen( $content ),
            'word_count' => $this->count_words( $content ),
            'sentence_count' => $this->count_sentences( $content ),
            'paragraph_count' => $this->count_paragraphs( $post->post_content ),
            'average_words_per_sentence' => 0,
            'readability_score' => $this->calculate_readability( $content ),
            'keyword_density' => $this->analyze_keyword_density( $content ),
            'content_topics' => $this->extract_content_topics( $content ),
            'language' => $this->detect_language( $content ),
        );

        // Calculate averages
        if ( $analysis['sentence_count'] > 0 ) {
            $analysis['average_words_per_sentence'] = round( $analysis['word_count'] / $analysis['sentence_count'], 2 );
        }

        return $analysis;
    }

    /**
     * Identify image opportunities in content
     *
     * @param WP_Post $post Post object
     * @return array Image opportunities
     * @since 1.0.0
     */
    private function identify_image_opportunities( $post ) {
        $opportunities = array(
            'needs_featured_image' => ! has_post_thumbnail( $post->ID ),
            'content_images_count' => $this->count_content_images( $post->post_content ),
            'image_gaps' => array(),
            'suggested_positions' => array(),
            'content_sections' => array(),
        );

        // Analyze content sections for image placement opportunities
        $sections = $this->split_content_into_sections( $post->post_content );
        
        foreach ( $sections as $index => $section ) {
            $section_analysis = array(
                'index' => $index,
                'word_count' => $this->count_words( wp_strip_all_tags( $section ) ),
                'has_images' => $this->section_has_images( $section ),
                'topics' => $this->extract_section_topics( $section ),
                'image_opportunity_score' => 0,
            );

            // Score image opportunity
            if ( ! $section_analysis['has_images'] && $section_analysis['word_count'] > 100 ) {
                $section_analysis['image_opportunity_score'] = min( 100, $section_analysis['word_count'] / 10 );
                $opportunities['image_gaps'][] = $section_analysis;
            }

            $opportunities['content_sections'][] = $section_analysis;
        }

        // Sort image gaps by opportunity score
        usort( $opportunities['image_gaps'], function( $a, $b ) {
            return $b['image_opportunity_score'] <=> $a['image_opportunity_score'];
        } );

        return $opportunities;
    }

    /**
     * Analyze SEO factors
     *
     * @param WP_Post $post Post object
     * @return array SEO analysis
     * @since 1.0.0
     */
    private function analyze_seo_factors( $post ) {
        $analysis = array(
            'title_length' => strlen( $post->post_title ),
            'excerpt_length' => strlen( $post->post_excerpt ),
            'has_meta_description' => false,
            'has_focus_keyword' => false,
            'image_alt_texts' => array(),
            'internal_links' => 0,
            'external_links' => 0,
            'heading_structure' => array(),
        );

        // Check for meta description
        $meta_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
        if ( empty( $meta_description ) ) {
            $meta_description = get_post_meta( $post->ID, '_aioseop_description', true );
        }
        $analysis['has_meta_description'] = ! empty( $meta_description );

        // Analyze images for alt text
        $analysis['image_alt_texts'] = $this->extract_image_alt_texts( $post->post_content );

        // Count links
        $link_counts = $this->count_links( $post->post_content );
        $analysis['internal_links'] = $link_counts['internal'];
        $analysis['external_links'] = $link_counts['external'];

        // Analyze heading structure
        $analysis['heading_structure'] = $this->analyze_heading_structure( $post->post_content );

        return $analysis;
    }

    /**
     * Count words in content
     *
     * @param string $content Content to analyze
     * @return int Word count
     * @since 1.0.0
     */
    private function count_words( $content ) {
        $content = wp_strip_all_tags( $content );
        $content = preg_replace( '/\s+/', ' ', $content );
        $words = explode( ' ', trim( $content ) );
        return count( array_filter( $words ) );
    }

    /**
     * Estimate reading time
     *
     * @param string $content Content to analyze
     * @return int Reading time in minutes
     * @since 1.0.0
     */
    private function estimate_reading_time( $content ) {
        $word_count = $this->count_words( $content );
        $words_per_minute = 200; // Average reading speed
        return max( 1, ceil( $word_count / $words_per_minute ) );
    }

    /**
     * Count sentences in content
     *
     * @param string $content Content to analyze
     * @return int Sentence count
     * @since 1.0.0
     */
    private function count_sentences( $content ) {
        $content = wp_strip_all_tags( $content );
        $sentences = preg_split( '/[.!?]+/', $content );
        return count( array_filter( array_map( 'trim', $sentences ) ) );
    }

    /**
     * Count paragraphs in content
     *
     * @param string $content Content to analyze
     * @return int Paragraph count
     * @since 1.0.0
     */
    private function count_paragraphs( $content ) {
        $paragraphs = preg_split( '/<\/p>|<br\s*\/?>/i', $content );
        return count( array_filter( array_map( 'trim', array_map( 'wp_strip_all_tags', $paragraphs ) ) ) );
    }

    /**
     * Get analysis summary
     *
     * @param array $analysis Full analysis array
     * @return array Summary data
     * @since 1.0.0
     */
    private function get_analysis_summary( $analysis ) {
        return array(
            'word_count' => $analysis['word_count'],
            'reading_time' => $analysis['reading_time'],
            'has_blocks' => $analysis['content_structure']['has_blocks'],
            'image_count' => $analysis['image_opportunities']['content_images_count'],
            'needs_featured_image' => $analysis['image_opportunities']['needs_featured_image'],
        );
    }

    /**
     * Extract shortcodes from content
     *
     * @param string $content Content to analyze
     * @return array Shortcodes found
     * @since 1.0.0
     */
    private function extract_shortcodes( $content ) {
        $shortcodes = array();
        preg_match_all( '/\[([^\]]+)\]/', $content, $matches );

        foreach ( $matches[1] as $shortcode ) {
            $parts = explode( ' ', $shortcode );
            $tag = $parts[0];

            if ( ! isset( $shortcodes[ $tag ] ) ) {
                $shortcodes[ $tag ] = 0;
            }
            $shortcodes[ $tag ]++;
        }

        return $shortcodes;
    }

    /**
     * Extract embeds from content
     *
     * @param string $content Content to analyze
     * @return array Embeds found
     * @since 1.0.0
     */
    private function extract_embeds( $content ) {
        $embeds = array();

        // Common embed patterns
        $patterns = array(
            'youtube' => '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/',
            'vimeo' => '/vimeo\.com\/(\d+)/',
            'twitter' => '/twitter\.com\/\w+\/status\/(\d+)/',
            'instagram' => '/instagram\.com\/p\/([a-zA-Z0-9_-]+)/',
        );

        foreach ( $patterns as $type => $pattern ) {
            if ( preg_match_all( $pattern, $content, $matches ) ) {
                $embeds[ $type ] = count( $matches[0] );
            }
        }

        return $embeds;
    }

    /**
     * Extract headings from content
     *
     * @param string $content Content to analyze
     * @return array Headings structure
     * @since 1.0.0
     */
    private function extract_headings( $content ) {
        $headings = array();
        preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $content, $matches, PREG_SET_ORDER );

        foreach ( $matches as $match ) {
            $headings[] = array(
                'level' => intval( $match[1] ),
                'text' => wp_strip_all_tags( $match[2] ),
                'full_tag' => $match[0],
            );
        }

        return $headings;
    }

    /**
     * Extract lists from content
     *
     * @param string $content Content to analyze
     * @return array Lists found
     * @since 1.0.0
     */
    private function extract_lists( $content ) {
        $lists = array(
            'ordered' => substr_count( $content, '<ol' ),
            'unordered' => substr_count( $content, '<ul' ),
        );

        return $lists;
    }

    /**
     * Extract tables from content
     *
     * @param string $content Content to analyze
     * @return array Tables found
     * @since 1.0.0
     */
    private function extract_tables( $content ) {
        return array(
            'count' => substr_count( $content, '<table' ),
        );
    }

    /**
     * Merge block analysis arrays
     *
     * @param array $analysis1 First analysis
     * @param array $analysis2 Second analysis
     * @return array Merged analysis
     * @since 1.0.0
     */
    private function merge_block_analysis( $analysis1, $analysis2 ) {
        foreach ( $analysis2['types'] as $type => $count ) {
            if ( ! isset( $analysis1['types'][ $type ] ) ) {
                $analysis1['types'][ $type ] = 0;
            }
            $analysis1['types'][ $type ] += $count;
        }

        $analysis1['image_blocks'] = array_merge( $analysis1['image_blocks'], $analysis2['image_blocks'] );
        $analysis1['gallery_blocks'] = array_merge( $analysis1['gallery_blocks'], $analysis2['gallery_blocks'] );
        $analysis1['media_blocks'] = array_merge( $analysis1['media_blocks'], $analysis2['media_blocks'] );
        $analysis1['text_blocks'] = array_merge( $analysis1['text_blocks'], $analysis2['text_blocks'] );

        return $analysis1;
    }

    /**
     * Extract SEO metadata
     *
     * @param WP_Post $post Post object
     * @return array SEO metadata
     * @since 1.0.0
     */
    private function extract_seo_metadata( $post ) {
        $seo = array();

        // Yoast SEO
        $seo['yoast'] = array(
            'title' => get_post_meta( $post->ID, '_yoast_wpseo_title', true ),
            'description' => get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ),
            'focus_keyword' => get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ),
            'canonical' => get_post_meta( $post->ID, '_yoast_wpseo_canonical', true ),
        );

        // All in One SEO
        $seo['aioseo'] = array(
            'title' => get_post_meta( $post->ID, '_aioseop_title', true ),
            'description' => get_post_meta( $post->ID, '_aioseop_description', true ),
            'keywords' => get_post_meta( $post->ID, '_aioseop_keywords', true ),
        );

        return $seo;
    }

    /**
     * Extract social metadata
     *
     * @param WP_Post $post Post object
     * @return array Social metadata
     * @since 1.0.0
     */
    private function extract_social_metadata( $post ) {
        $social = array();

        // Open Graph
        $social['og'] = array(
            'title' => get_post_meta( $post->ID, '_yoast_wpseo_opengraph-title', true ),
            'description' => get_post_meta( $post->ID, '_yoast_wpseo_opengraph-description', true ),
            'image' => get_post_meta( $post->ID, '_yoast_wpseo_opengraph-image', true ),
        );

        // Twitter Cards
        $social['twitter'] = array(
            'title' => get_post_meta( $post->ID, '_yoast_wpseo_twitter-title', true ),
            'description' => get_post_meta( $post->ID, '_yoast_wpseo_twitter-description', true ),
            'image' => get_post_meta( $post->ID, '_yoast_wpseo_twitter-image', true ),
        );

        return $social;
    }

    /**
     * Calculate readability score (simplified Flesch Reading Ease)
     *
     * @param string $content Content to analyze
     * @return float Readability score
     * @since 1.0.0
     */
    private function calculate_readability( $content ) {
        $word_count = $this->count_words( $content );
        $sentence_count = $this->count_sentences( $content );

        if ( $sentence_count === 0 || $word_count === 0 ) {
            return 0;
        }

        $avg_sentence_length = $word_count / $sentence_count;

        // Simplified readability score (higher is better)
        $score = 100 - ( $avg_sentence_length * 2 );

        return max( 0, min( 100, $score ) );
    }

    /**
     * Analyze keyword density
     *
     * @param string $content Content to analyze
     * @return array Keyword density analysis
     * @since 1.0.0
     */
    private function analyze_keyword_density( $content ) {
        $words = preg_split( '/\s+/', strtolower( wp_strip_all_tags( $content ) ) );
        $words = array_filter( $words );
        $total_words = count( $words );

        if ( $total_words === 0 ) {
            return array();
        }

        $word_counts = array_count_values( $words );
        arsort( $word_counts );

        $density = array();
        $top_words = array_slice( $word_counts, 0, 10, true );

        foreach ( $top_words as $word => $count ) {
            if ( strlen( $word ) > 3 ) { // Skip short words
                $density[ $word ] = round( ( $count / $total_words ) * 100, 2 );
            }
        }

        return $density;
    }

    /**
     * Extract content topics
     *
     * @param string $content Content to analyze
     * @return array Content topics
     * @since 1.0.0
     */
    private function extract_content_topics( $content ) {
        $density = $this->analyze_keyword_density( $content );

        // Simple topic extraction based on keyword density
        $topics = array();
        foreach ( $density as $word => $percentage ) {
            if ( $percentage > 1.0 ) { // Words appearing more than 1% of the time
                $topics[] = $word;
            }
        }

        return array_slice( $topics, 0, 5 ); // Top 5 topics
    }

    /**
     * Detect content language (simplified)
     *
     * @param string $content Content to analyze
     * @return string Language code
     * @since 1.0.0
     */
    private function detect_language( $content ) {
        // Very basic language detection - in real implementation,
        // you might use a proper language detection library
        $common_english_words = array( 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by' );

        $words = preg_split( '/\s+/', strtolower( wp_strip_all_tags( $content ) ) );
        $english_word_count = 0;

        foreach ( $words as $word ) {
            if ( in_array( $word, $common_english_words ) ) {
                $english_word_count++;
            }
        }

        $total_words = count( $words );
        if ( $total_words > 0 && ( $english_word_count / $total_words ) > 0.05 ) {
            return 'en';
        }

        return 'unknown';
    }

    /**
     * Count content images
     *
     * @param string $content Content to analyze
     * @return int Image count
     * @since 1.0.0
     */
    private function count_content_images( $content ) {
        return substr_count( $content, '<img' );
    }

    /**
     * Split content into sections
     *
     * @param string $content Content to split
     * @return array Content sections
     * @since 1.0.0
     */
    private function split_content_into_sections( $content ) {
        // Split by headings or double line breaks
        $sections = preg_split( '/<h[1-6][^>]*>.*?<\/h[1-6]>|<\/p>\s*<\/p>/i', $content );

        return array_filter( array_map( 'trim', $sections ) );
    }

    /**
     * Check if section has images
     *
     * @param string $section Section content
     * @return bool True if has images
     * @since 1.0.0
     */
    private function section_has_images( $section ) {
        return strpos( $section, '<img' ) !== false;
    }

    /**
     * Extract section topics
     *
     * @param string $section Section content
     * @return array Section topics
     * @since 1.0.0
     */
    private function extract_section_topics( $section ) {
        return $this->extract_content_topics( $section );
    }

    /**
     * Extract image alt texts
     *
     * @param string $content Content to analyze
     * @return array Alt texts
     * @since 1.0.0
     */
    private function extract_image_alt_texts( $content ) {
        $alt_texts = array();
        preg_match_all( '/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $content, $matches );

        foreach ( $matches[1] as $alt ) {
            if ( ! empty( trim( $alt ) ) ) {
                $alt_texts[] = trim( $alt );
            }
        }

        return $alt_texts;
    }

    /**
     * Count links in content
     *
     * @param string $content Content to analyze
     * @return array Link counts
     * @since 1.0.0
     */
    private function count_links( $content ) {
        $counts = array( 'internal' => 0, 'external' => 0 );

        preg_match_all( '/<a[^>]+href=["\']([^"\']*)["\'][^>]*>/i', $content, $matches );

        $site_url = get_site_url();

        foreach ( $matches[1] as $url ) {
            if ( strpos( $url, $site_url ) === 0 || strpos( $url, '/' ) === 0 ) {
                $counts['internal']++;
            } else {
                $counts['external']++;
            }
        }

        return $counts;
    }

    /**
     * Analyze heading structure
     *
     * @param string $content Content to analyze
     * @return array Heading structure analysis
     * @since 1.0.0
     */
    private function analyze_heading_structure( $content ) {
        $headings = $this->extract_headings( $content );
        $structure = array();

        foreach ( $headings as $heading ) {
            $level = 'h' . $heading['level'];
            if ( ! isset( $structure[ $level ] ) ) {
                $structure[ $level ] = 0;
            }
            $structure[ $level ]++;
        }

        return $structure;
    }

    /**
     * Clear processing cache
     *
     * @since 1.0.0
     */
    public function clear_cache() {
        $this->cache = array();
    }
}
