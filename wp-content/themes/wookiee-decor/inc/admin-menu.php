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
	$GLOBALS['wookiee_niche_suggest_hooks'][] = add_submenu_page( 'wookiee-setup', 'Wookiee Settings', 'Settings', 'manage_options', 'wookiee-settings', 'wookiee_render_settings_page' );
	$GLOBALS['wookiee_niche_suggest_hooks'][] = add_submenu_page( 'wookiee-setup', 'Wookiee Product Generator', 'Product Generator', 'manage_options', 'wookiee-product-generator', 'wookiee_render_product_generator_page' );
	$GLOBALS['wookiee_niche_suggest_hooks'][] = add_submenu_page( 'wookiee-setup', 'Wookiee Content Generator', 'Content Generator', 'manage_options', 'wookiee-content-generator', 'wookiee_render_content_generator_page' );
	add_submenu_page( 'wookiee-setup', 'Wookiee Supplier Catalog', 'Supplier Catalog', 'manage_options', 'wookiee-supplier-catalog', 'wookiee_render_supplier_catalog_page' );
}

/**
 * The "suggest a niche" sparkle icon appears inside 4 different niche-
 * brief fields across 3 admin screens (Product Generator, Content
 * Generator, and both niche-brief fields on the Settings page). Rather
 * than duplicating the same CSS/JS in each render function, this loads
 * it once, only on those screens, and wires up any element matching
 * .wookiee-niche-suggest-btn found on the page - each screen only needs
 * to output the button markup itself (see wookiee_niche_suggest_button()).
 */
add_action( 'admin_enqueue_scripts', 'wookiee_enqueue_niche_suggest_assets' );
function wookiee_enqueue_niche_suggest_assets( $hook ) {
	if ( empty( $GLOBALS['wookiee_niche_suggest_hooks'] ) || ! in_array( $hook, $GLOBALS['wookiee_niche_suggest_hooks'], true ) ) {
		return;
	}

	$css = '
		.wookiee-niche-input-wrap { position: relative; display: inline-block; vertical-align: middle; }
		.wookiee-niche-input-wrap.is-textarea { display: block; }
		.wookiee-niche-input-wrap input[type=text],
		.wookiee-niche-input-wrap textarea { padding-right: 34px !important; box-sizing: border-box; }
		.wookiee-niche-suggest-btn {
			position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
			background: none; border: none; cursor: pointer; padding: 4px; line-height: 0;
			color: #8a7d6d; border-radius: 4px;
		}
		.wookiee-niche-input-wrap.is-textarea .wookiee-niche-suggest-btn { top: 8px; transform: none; }
		.wookiee-niche-suggest-btn:hover { color: #c1704a; background: #f0f0f0; }
		.wookiee-niche-suggest-btn.is-loading svg { animation: wookiee-suggest-spin 0.9s linear infinite; }
		@keyframes wookiee-suggest-spin { to { transform: rotate(360deg); } }
	';
	wp_register_style( 'wookiee-niche-suggest', false );
	wp_enqueue_style( 'wookiee-niche-suggest' );
	wp_add_inline_style( 'wookiee-niche-suggest', $css );

	$js = "
	( function() {
		var NONCE = " . wp_json_encode( wp_create_nonce( 'wookiee_suggest_niche' ) ) . ";
		document.querySelectorAll( '.wookiee-niche-suggest-btn' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() {
				var field = document.getElementById( btn.getAttribute( 'data-target' ) );
				if ( ! field ) { return; }
				btn.disabled = true;
				btn.classList.add( 'is-loading' );
				var data = new FormData();
				data.append( 'action', 'wookiee_suggest_niche' );
				data.append( 'nonce', NONCE );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						btn.disabled = false;
						btn.classList.remove( 'is-loading' );
						if ( ! res.success ) {
							btn.title = res.data && res.data.message ? res.data.message : 'Failed to suggest a niche.';
							return;
						}
						field.value = res.data.brief;
						field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
						btn.title = res.data.grounded ? 'Suggested from real UK search-demand data - click again for another' : 'Suggested niche - click again for another';
					} )
					.catch( function() {
						btn.disabled = false;
						btn.classList.remove( 'is-loading' );
						btn.title = 'Failed - could not reach the server.';
					} );
			} );
		} );
	} )();
	";
	wp_register_script( 'wookiee-niche-suggest', false, array(), false, true );
	wp_enqueue_script( 'wookiee-niche-suggest' );
	wp_add_inline_script( 'wookiee-niche-suggest', $js );
}

/**
 * Markup for the sparkle "suggest a niche" button - wrap it and the
 * existing niche-brief input/textarea together in a
 * `.wookiee-niche-input-wrap` div (add the `is-textarea` modifier class
 * too when wrapping a <textarea>, so the icon sits at the top-right
 * corner instead of dead-center vertically).
 */
function wookiee_niche_suggest_button( $field_id ) {
	printf(
		'<button type="button" class="wookiee-niche-suggest-btn" data-target="%s" title="Suggest a niche">%s</button>',
		esc_attr( $field_id ),
		'<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c.3 0 .6.2.7.5L14 8l4.5 1.3c.3.1.5.4.5.7s-.2.6-.5.7L14 12l-1.3 4.5c-.1.3-.4.5-.7.5s-.6-.2-.7-.5L10 12l-4.5-1.3c-.3-.1-.5-.4-.5-.7s.2-.6.5-.7L10 8l1.3-4.5c.1-.3.4-.5.7-.5z"/></svg>'
	);
}
