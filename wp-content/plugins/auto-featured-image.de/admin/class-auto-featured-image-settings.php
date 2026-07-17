<?php
/**
 * Settings Management Class
 *
 * Handles settings validation, import/export, and configuration management
 * for the Auto Featured Image plugin.
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Featured Image Settings Class
 *
 * @since 1.0.0
 */
class Auto_Featured_Image_Settings {

    /**
     * Default settings
     *
     * @var array
     * @since 1.0.0
     */
    private $defaults = array(
        'auto_processing_enabled' => false,
        'post_types' => array( 'post' ),
        'skip_existing' => true,
        'min_image_score' => 30,
        'enabled_algorithms' => array(
            'smart_content_analysis',
            'first_quality_image',
            'semantic_matching',
            'category_based',
        ),
        'primary_algorithm' => 'smart_content_analysis',
        'fallback_algorithm' => 'first_quality_image',
        'algorithm_weights' => array(
            'content_relevance' => 30,
            'image_quality' => 25,
            'position_priority' => 20,
            'semantic_match' => 15,
            'user_preference' => 10,
        ),
        'batch_size' => 25,
        'target_execution_time' => 30,
        'adaptive_batch_sizing' => true,
        'max_retry_attempts' => 3,
        'debug_mode' => false,
        'log_retention_days' => 30,
        'cleanup_completed_jobs' => 7,
    );

    /**
     * Settings schema for validation
     *
     * @var array
     * @since 1.0.0
     */
    private $schema = array(
        'auto_processing_enabled' => array(
            'type' => 'boolean',
            'default' => false,
        ),
        'post_types' => array(
            'type' => 'array',
            'items' => 'string',
            'default' => array( 'post' ),
            'validate' => 'validate_post_types',
        ),
        'skip_existing' => array(
            'type' => 'boolean',
            'default' => true,
        ),
        'min_image_score' => array(
            'type' => 'integer',
            'min' => 0,
            'max' => 100,
            'default' => 30,
        ),
        'enabled_algorithms' => array(
            'type' => 'array',
            'items' => 'string',
            'default' => array( 'smart_content_analysis', 'first_quality_image' ),
            'validate' => 'validate_algorithms',
        ),
        'primary_algorithm' => array(
            'type' => 'string',
            'default' => 'smart_content_analysis',
            'validate' => 'validate_algorithm_name',
        ),
        'fallback_algorithm' => array(
            'type' => 'string',
            'default' => 'first_quality_image',
            'validate' => 'validate_algorithm_name',
        ),
        'algorithm_weights' => array(
            'type' => 'object',
            'properties' => array(
                'content_relevance' => array( 'type' => 'integer', 'min' => 0, 'max' => 50 ),
                'image_quality' => array( 'type' => 'integer', 'min' => 0, 'max' => 50 ),
                'position_priority' => array( 'type' => 'integer', 'min' => 0, 'max' => 50 ),
                'semantic_match' => array( 'type' => 'integer', 'min' => 0, 'max' => 50 ),
                'user_preference' => array( 'type' => 'integer', 'min' => 0, 'max' => 50 ),
            ),
            'default' => array(
                'content_relevance' => 30,
                'image_quality' => 25,
                'position_priority' => 20,
                'semantic_match' => 15,
                'user_preference' => 10,
            ),
        ),
        'batch_size' => array(
            'type' => 'integer',
            'min' => 1,
            'max' => 1000,
            'default' => 25,
        ),
        'target_execution_time' => array(
            'type' => 'integer',
            'min' => 5,
            'max' => 300,
            'default' => 30,
        ),
        'adaptive_batch_sizing' => array(
            'type' => 'boolean',
            'default' => true,
        ),
        'max_retry_attempts' => array(
            'type' => 'integer',
            'min' => 0,
            'max' => 10,
            'default' => 3,
        ),
        'debug_mode' => array(
            'type' => 'boolean',
            'default' => false,
        ),
        'log_retention_days' => array(
            'type' => 'integer',
            'min' => 1,
            'max' => 365,
            'default' => 30,
        ),
        'cleanup_completed_jobs' => array(
            'type' => 'integer',
            'min' => 1,
            'max' => 90,
            'default' => 7,
        ),
    );

    /**
     * Get default settings
     *
     * @return array Default settings
     * @since 1.0.0
     */
    public function get_defaults() {
        return $this->defaults;
    }

    /**
     * Get current settings with defaults
     *
     * @return array Current settings
     * @since 1.0.0
     */
    public function get_settings() {
        $settings = get_option( 'auto_featured_image_settings', array() );
        return wp_parse_args( $settings, $this->defaults );
    }

    /**
     * Validate settings array
     *
     * @param array $settings Settings to validate
     * @return array Validated settings
     * @since 1.0.0
     */
    public function validate_settings( $settings ) {
        $validated = array();
        $errors = array();

        foreach ( $this->schema as $key => $schema ) {
            $value = $settings[ $key ] ?? $schema['default'];
            
            try {
                $validated[ $key ] = $this->validate_field( $key, $value, $schema );
            } catch ( Exception $e ) {
                $errors[ $key ] = $e->getMessage();
                $validated[ $key ] = $schema['default'];
            }
        }

        if ( ! empty( $errors ) ) {
            // Store validation errors for display
            set_transient( 'auto_featured_image_validation_errors', $errors, 300 );
        }

        return $validated;
    }

    /**
     * Validate individual field
     *
     * @param string $key Field key
     * @param mixed  $value Field value
     * @param array  $schema Field schema
     * @return mixed Validated value
     * @throws Exception If validation fails
     * @since 1.0.0
     */
    private function validate_field( $key, $value, $schema ) {
        switch ( $schema['type'] ) {
            case 'boolean':
                return (bool) $value;

            case 'integer':
                $int_value = intval( $value );
                if ( isset( $schema['min'] ) && $int_value < $schema['min'] ) {
                    throw new Exception( sprintf( 'Value must be at least %d', $schema['min'] ) );
                }
                if ( isset( $schema['max'] ) && $int_value > $schema['max'] ) {
                    throw new Exception( sprintf( 'Value must be at most %d', $schema['max'] ) );
                }
                return $int_value;

            case 'string':
                $string_value = sanitize_text_field( $value );
                if ( isset( $schema['validate'] ) && method_exists( $this, $schema['validate'] ) ) {
                    if ( ! $this->{$schema['validate']}( $string_value ) ) {
                        throw new Exception( 'Invalid value' );
                    }
                }
                return $string_value;

            case 'array':
                if ( ! is_array( $value ) ) {
                    throw new Exception( 'Value must be an array' );
                }
                
                $validated_array = array();
                foreach ( $value as $item ) {
                    if ( $schema['items'] === 'string' ) {
                        $validated_array[] = sanitize_text_field( $item );
                    } elseif ( $schema['items'] === 'integer' ) {
                        $validated_array[] = intval( $item );
                    }
                }
                
                if ( isset( $schema['validate'] ) && method_exists( $this, $schema['validate'] ) ) {
                    if ( ! $this->{$schema['validate']}( $validated_array ) ) {
                        throw new Exception( 'Invalid array values' );
                    }
                }
                
                return $validated_array;

            case 'object':
                if ( ! is_array( $value ) ) {
                    throw new Exception( 'Value must be an object/array' );
                }
                
                $validated_object = array();
                if ( isset( $schema['properties'] ) ) {
                    foreach ( $schema['properties'] as $prop_key => $prop_schema ) {
                        $prop_value = $value[ $prop_key ] ?? $prop_schema['default'] ?? null;
                        $validated_object[ $prop_key ] = $this->validate_field( $prop_key, $prop_value, $prop_schema );
                    }
                }
                
                return $validated_object;

            default:
                return $value;
        }
    }

    /**
     * Validate post types
     *
     * @param array $post_types Post types to validate
     * @return bool True if valid
     * @since 1.0.0
     */
    private function validate_post_types( $post_types ) {
        $available_post_types = get_post_types( array( 'public' => true ) );
        
        foreach ( $post_types as $post_type ) {
            if ( ! in_array( $post_type, $available_post_types ) ) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate algorithms array
     *
     * @param array $algorithms Algorithms to validate
     * @return bool True if valid
     * @since 1.0.0
     */
    private function validate_algorithms( $algorithms ) {
        // This would need access to the algorithms class
        // For now, just check if it's not empty
        return ! empty( $algorithms );
    }

    /**
     * Validate algorithm name
     *
     * @param string $algorithm_name Algorithm name to validate
     * @return bool True if valid
     * @since 1.0.0
     */
    private function validate_algorithm_name( $algorithm_name ) {
        // This would need access to the algorithms class
        // For now, just check basic format
        return ! empty( $algorithm_name ) && preg_match( '/^[a-z_]+$/', $algorithm_name );
    }

    /**
     * Export settings
     *
     * @return array Settings export data
     * @since 1.0.0
     */
    public function export_settings() {
        $settings = $this->get_settings();
        
        return array(
            'version' => AUTO_FEATURED_IMAGE_VERSION,
            'exported_at' => current_time( 'mysql' ),
            'settings' => $settings,
        );
    }

    /**
     * Import settings
     *
     * @param array $import_data Import data
     * @return array Result with success status and message
     * @since 1.0.0
     */
    public function import_settings( $import_data ) {
        if ( ! isset( $import_data['settings'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid import data format.', 'auto-featured-image' ),
            );
        }

        try {
            $validated_settings = $this->validate_settings( $import_data['settings'] );
            update_option( 'auto_featured_image_settings', $validated_settings );
            
            return array(
                'success' => true,
                'message' => __( 'Settings imported successfully.', 'auto-featured-image' ),
            );
        } catch ( Exception $e ) {
            return array(
                'success' => false,
                'message' => sprintf( __( 'Import failed: %s', 'auto-featured-image' ), $e->getMessage() ),
            );
        }
    }

    /**
     * Reset settings to defaults
     *
     * @return bool True on success
     * @since 1.0.0
     */
    public function reset_to_defaults() {
        delete_option( 'auto_featured_image_settings' );
        return true;
    }

    /**
     * Get validation errors
     *
     * @return array Validation errors
     * @since 1.0.0
     */
    public function get_validation_errors() {
        return get_transient( 'auto_featured_image_validation_errors' ) ?: array();
    }

    /**
     * Clear validation errors
     *
     * @since 1.0.0
     */
    public function clear_validation_errors() {
        delete_transient( 'auto_featured_image_validation_errors' );
    }
}
