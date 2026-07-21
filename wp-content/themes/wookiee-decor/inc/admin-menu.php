<?php
/**
 * Single consolidated "Wookiee" admin menu, grouping every screen this
 * theme adds (Setup, Settings, and the three generators) as one top-level
 * item with a submenu, instead of five separate entries scattered inside
 * Appearance. Every wookiee_render_*_page() function still lives in its
 * own inc/ file - this is purely the menu registration.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'wookiee_register_admin_menu' );
function wookiee_register_admin_menu() {
	add_menu_page(
		'Wookiee',
		'Wookiee',
		'manage_options',
		'wookiee-setup',
		'wookiee_render_setup_wizard_page',
		'dashicons-store',
		58
	);

	add_submenu_page( 'wookiee-setup', 'Wookiee Setup', 'Setup', 'manage_options', 'wookiee-setup', 'wookiee_render_setup_wizard_page' );
	add_submenu_page( 'wookiee-setup', 'Wookiee Settings', 'Settings', 'manage_options', 'wookiee-settings', 'wookiee_render_settings_page' );
	add_submenu_page( 'wookiee-setup', 'Wookiee Product Generator', 'Product Generator', 'manage_options', 'wookiee-product-generator', 'wookiee_render_product_generator_page' );
	add_submenu_page( 'wookiee-setup', 'Wookiee Content Generator', 'Content Generator', 'manage_options', 'wookiee-content-generator', 'wookiee_render_content_generator_page' );
	add_submenu_page( 'wookiee-setup', 'Wookiee Supplier Catalog', 'Supplier Catalog', 'manage_options', 'wookiee-supplier-catalog', 'wookiee_render_supplier_catalog_page' );
}
