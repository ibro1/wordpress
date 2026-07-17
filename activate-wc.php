<?php
require_once 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'woocommerce/woocommerce.php';
if ( ! is_plugin_active( $plugin ) ) {
    $result = activate_plugin( $plugin );
    if ( is_wp_error( $result ) ) {
        echo "Error: " . $result->get_error_message();
    } else {
        echo "WooCommerce activated.\n";
        // Run WooCommerce's install routines to create default pages
        if ( class_exists( 'WC_Install' ) ) {
            WC_Install::install();
            echo "WooCommerce default pages created.\n";
        }
        
        // Re-run the theme's starter content logic
        if ( function_exists( 'anyora_create_starter_content' ) ) {
            anyora_create_starter_content();
            echo "Theme starter content re-generated.\n";
        }
    }
} else {
    echo "WooCommerce is already active.\n";
    if ( function_exists( 'anyora_create_starter_content' ) ) {
        anyora_create_starter_content();
        echo "Theme starter content re-generated.\n";
    }
}
