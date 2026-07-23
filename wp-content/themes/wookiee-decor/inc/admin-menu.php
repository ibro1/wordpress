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

	$GLOBALS['wookiee_niche_suggest_hooks'][] = add_submenu_page( 'wookiee-setup', 'Wookiee Setup', 'Setup', 'manage_options', 'wookiee-setup', 'wookiee_render_setup_wizard_page' );
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
		.wookiee-niche-input-wrap textarea { padding-right: 46px !important; box-sizing: border-box; }
		.wookiee-niche-suggest-btn {
			position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
			width: 32px; height: 32px;
			display: flex; align-items: center; justify-content: center;
			background: #f6f0e8; border: 1px solid #e5d9c8; cursor: pointer; padding: 0;
			color: #c1704a; border-radius: 6px; z-index: 2;
		}
		.wookiee-niche-suggest-btn svg { width: 20px; height: 20px; }
		.wookiee-niche-input-wrap.is-textarea .wookiee-niche-suggest-btn { top: 8px; transform: none; }
		.wookiee-niche-suggest-btn:hover { background: #c1704a; border-color: #c1704a; color: #fff; }
		.wookiee-niche-suggest-btn.is-loading svg { animation: wookiee-suggest-spin 0.9s linear infinite; }
		@keyframes wookiee-suggest-spin { to { transform: rotate(360deg); } }
		.wookiee-niche-suggest-inline-status.is-error { color: #b32d2e; }
		.wookiee-niche-suggest-inline-status.is-success { color: #00a32a; }
	';
	wp_register_style( 'wookiee-niche-suggest', false );
	wp_enqueue_style( 'wookiee-niche-suggest' );
	wp_add_inline_style( 'wookiee-niche-suggest', $css );

	$js = "
	( function() {
		var NONCE = " . wp_json_encode( wp_create_nonce( 'wookiee_suggest_niche' ) ) . ";

		// Errors here used to only set the button's hover tooltip, which
		// looks exactly like \"nothing happened\" if you don't hover over
		// it (e.g. no LLM key configured yet) - show a real, visible
		// message under the field instead, created on demand so it works
		// regardless of which page/step the button is on.
		function showNicheStatus( btn, message, type ) {
			var wrap = btn.closest( '.wookiee-niche-input-wrap' );
			if ( ! wrap ) { return; }
			var status = wrap.parentNode.querySelector( '.wookiee-niche-suggest-inline-status' );
			if ( ! status ) {
				status = document.createElement( 'p' );
				status.className = 'wookiee-niche-suggest-inline-status description';
				wrap.insertAdjacentElement( 'afterend', status );
			}
			status.innerHTML = message;
			status.classList.remove( 'is-error', 'is-success' );
			if ( 'error' === type ) { status.classList.add( 'is-error' ); }
			if ( 'success' === type ) { status.classList.add( 'is-success' ); }
		}

		document.querySelectorAll( '.wookiee-niche-suggest-btn' ).forEach( function( btn ) {
			btn.addEventListener( 'click', function() {
				var field = document.getElementById( btn.getAttribute( 'data-target' ) );
				if ( ! field ) { return; }
				btn.disabled = true;
				btn.classList.add( 'is-loading' );
				showNicheStatus( btn, 'Thinking of a niche…', 'loading' );
				var data = new FormData();
				data.append( 'action', 'wookiee_suggest_niche' );
				data.append( 'nonce', NONCE );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						btn.disabled = false;
						btn.classList.remove( 'is-loading' );
						if ( ! res.success ) {
							var msg = res.data && res.data.message ? res.data.message : 'Failed to suggest a niche.';
							showNicheStatus( btn, msg, 'error' );
							btn.title = msg;
							return;
						}
						field.value = res.data.brief;
						field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
						var okMsg = res.data.grounded ? 'Suggested from real UK search-demand data - click again for another.' : 'Suggested niche - click again for another.';
						showNicheStatus( btn, okMsg, 'success' );
						btn.title = okMsg;
					} )
					.catch( function() {
						btn.disabled = false;
						btn.classList.remove( 'is-loading' );
						showNicheStatus( btn, 'Failed - could not reach the server.', 'error' );
						btn.title = 'Failed - could not reach the server.';
					} );
			} );
		} );

		// The Homepage Copy / About & Contact Copy \"Generate with AI\"
		// buttons - shared here (rather than living only in
		// inc/theme-settings.php) since the Setup wizard renders these
		// exact same fields/buttons too and needs identical wiring.
		// wireInlineGenerator() no-ops safely if its button id isn't on
		// the current page, so calling it for both is harmless.
		function wireInlineGenerator( btnId, briefId, statusId, action, nonceAction ) {
			var btn = document.getElementById( btnId );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function() {
				var status = document.getElementById( statusId );
				var brief  = document.getElementById( briefId ).value.trim();
				if ( ! brief ) {
					status.textContent = 'Describe the niche first.';
					return;
				}
				btn.disabled = true;
				status.textContent = 'Generating… this can take up to a minute.';
				var data = new FormData();
				data.append( 'action', action );
				data.append( 'nonce', nonceAction );
				data.append( 'brief', brief );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						btn.disabled = false;
						if ( ! res.success ) {
							status.innerHTML = res.data && res.data.message ? res.data.message : 'Generation failed.';
							return;
						}
						Object.keys( res.data.fields ).forEach( function( key ) {
							var field = document.getElementById( 'wookiee_setting_' + key );
							if ( field && res.data.fields[ key ] ) {
								field.value = res.data.fields[ key ];
							}
						} );
						status.textContent = 'Drafted below. Review, then click Save Changes.';
					} )
					.catch( function() {
						btn.disabled = false;
						status.textContent = 'Generation failed — could not reach the server.';
					} );
			} );
		}

		wireInlineGenerator( 'wookiee-homepage-ai-btn', 'wookiee-homepage-ai-brief', 'wookiee-homepage-ai-status', 'wookiee_inline_generate_homepage_copy', " . wp_json_encode( wp_create_nonce( 'wookiee_inline_homepage_copy' ) ) . " );
		wireInlineGenerator( 'wookiee-about-ai-btn', 'wookiee-about-ai-brief', 'wookiee-about-ai-status', 'wookiee_inline_generate_about_contact_copy', " . wp_json_encode( wp_create_nonce( 'wookiee_inline_about_contact_copy' ) ) . " );
		wireInlineGenerator( 'wookiee-about-ai-btn-contact', 'wookiee-about-ai-brief-contact', 'wookiee-about-ai-status-contact', 'wookiee_inline_generate_about_contact_copy', " . wp_json_encode( wp_create_nonce( 'wookiee_inline_about_contact_copy' ) ) . " );

		// Companies House lookup button - fills business_name/registered_address
		// from the company number, wherever that field row is rendered
		// (Settings' Business Identity tab, or the Setup wizard's step 1).
		var chBtn = document.getElementById( 'wookiee-ch-lookup-btn' );
		if ( chBtn ) {
			chBtn.addEventListener( 'click', function() {
				var status      = document.getElementById( 'wookiee-ch-lookup-status' );
				var numberField = document.getElementById( 'wookiee_setting_company_number' );
				var number      = numberField ? numberField.value.trim() : '';
				if ( ! number ) {
					status.textContent = 'Enter a company number first.';
					return;
				}
				chBtn.disabled = true;
				status.textContent = 'Looking up…';
				var chData = new FormData();
				chData.append( 'action', 'wookiee_ch_lookup' );
				chData.append( 'nonce', " . wp_json_encode( wp_create_nonce( 'wookiee_ch_lookup' ) ) . " );
				chData.append( 'company_number', number );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: chData } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						chBtn.disabled = false;
						if ( ! res.success ) {
							status.textContent = res.data && res.data.message ? res.data.message : 'Lookup failed.';
							return;
						}
						var nameField = document.getElementById( 'wookiee_setting_business_name' );
						var addrField = document.getElementById( 'wookiee_setting_registered_address' );
						if ( nameField ) { nameField.value = res.data.company_name; }
						if ( addrField ) { addrField.value = res.data.address; }
						status.textContent = 'Found: ' + res.data.company_name + ' (status: ' + res.data.company_status + '). Review the fields, then click Save Changes.';
					} )
					.catch( function() {
						chBtn.disabled = false;
						status.textContent = 'Lookup failed — could not reach the server.';
					} );
			} );
		}
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
