<?php
/**
 * Internationalization class for Auto Featured Image
 *
 * @package AutoFeaturedImage
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Internationalization class that handles plugin text domain loading
 */
class AFI_i18n {
    
    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'auto-featured-image',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}