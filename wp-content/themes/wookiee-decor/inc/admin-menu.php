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
		.wookiee-ch-search-results {
			max-width: 480px; max-height: 260px; overflow-y: auto;
			border: 1px solid #dcdcde; border-radius: 4px; margin-top: 8px; background: #fff;
		}
		.wookiee-ch-search-result {
			display: block; width: 100%; text-align: left; background: none; border: none;
			border-bottom: 1px solid #f0f0f1; padding: 8px 10px; cursor: pointer; font-size: 13px;
		}
		.wookiee-ch-search-result:last-child { border-bottom: none; }
		.wookiee-ch-search-result:hover { background: #f6f0e8; }
		.wookiee-ch-search-result span { color: #646970; }
		.wookiee-ch-search-msg { padding: 8px 10px; margin: 0; color: #646970; }
		.wookiee-spinner {
			display: inline-block; width: 14px; height: 14px; vertical-align: middle; margin-right: 6px;
			border: 2px solid #dcdcde; border-top-color: #2271b1; border-radius: 50%;
			animation: wookiee-suggest-spin 0.8s linear infinite;
		}
		.wookiee-domain-suggestions { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; }
		.wookiee-domain-suggestions-group { min-width: 220px; }
		.wookiee-domain-suggestions-group h4 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #646970; }
		.wookiee-domain-suggestion-row {
			display: flex; align-items: center; justify-content: space-between; gap: 10px;
			padding: 6px 10px; border: 1px solid #dcdcde; border-radius: 4px; margin-bottom: 6px; background: #fff;
		}
		.wookiee-domain-suggestion-row .button { flex-shrink: 0; }
		.wookiee-register-domain-modal {
			position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 100000;
			display: flex; align-items: center; justify-content: center;
		}
		.wookiee-register-domain-card {
			background: #fff; border-radius: 6px; padding: 24px; width: 480px; max-width: 92vw;
			max-height: 86vh; overflow-y: auto;
		}
		.wookiee-register-domain-card h2 { margin-top: 0; }
		.wookiee-register-domain-card .form-table th { width: 130px; }
		.wookiee-register-domain-card input[type=text],
		.wookiee-register-domain-card input[type=email],
		.wookiee-register-domain-card input[type=tel],
		.wookiee-register-domain-card select { width: 100%; }
		.wookiee-register-domain-actions { display: flex; gap: 8px; margin-top: 16px; }
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

		// Companies House lookup button - one field accepts either the exact
		// company number (fills business_name/registered_address directly)
		// or a company name (shows a scrollable list of active matches to
		// pick from, wherever this field row is rendered: Settings' Business
		// Identity tab, or the Setup wizard's step 1).
		var chBtn           = document.getElementById( 'wookiee-ch-lookup-btn' );
		var chNumberField   = document.getElementById( 'wookiee_setting_company_number' );
		var chSearchResults = document.getElementById( 'wookiee-ch-search-results' );

		function runChNumberLookup( number, status ) {
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
					runSiteNameSuggest( res.data.company_name );
				} )
				.catch( function() {
					chBtn.disabled = false;
					status.textContent = 'Lookup failed — could not reach the server.';
				} );
		}

		// Only present on the Setup wizard's Business Identity step, not
		// the Settings page - suggests a short site title from whatever
		// company was just looked up/picked, with a live .com/.uk
		// availability check (and Register button, if Spaceship is fully
		// configured) for up to 3 candidates per extension.
		function runSiteNameSuggest( companyName ) {
			var blognameField = document.getElementById( 'blogname' );
			var nameStatus     = document.getElementById( 'wookiee-site-name-status' );
			var spinner        = document.getElementById( 'wookiee-site-name-spinner' );
			var suggestWrap    = document.getElementById( 'wookiee-domain-suggestions' );
			if ( ! blognameField || ! nameStatus || ! companyName ) { return; }
			if ( spinner ) { spinner.hidden = false; }
			if ( suggestWrap ) { suggestWrap.hidden = true; }
			nameStatus.textContent = 'Suggesting a site title…';
			var data = new FormData();
			data.append( 'action', 'wookiee_suggest_site_name' );
			data.append( 'nonce', " . wp_json_encode( wp_create_nonce( 'wookiee_suggest_site_name' ) ) . " );
			data.append( 'company_name', companyName );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( spinner ) { spinner.hidden = true; }
					if ( ! res.success || ! res.data ) {
						nameStatus.textContent = '';
						return;
					}
					blognameField.value = res.data.site_name;
					renderDomainSuggestions( res.data );
				} )
				.catch( function() {
					if ( spinner ) { spinner.hidden = true; }
					nameStatus.textContent = '';
				} );
		}

		function renderDomainSuggestions( result ) {
			var nameStatus  = document.getElementById( 'wookiee-site-name-status' );
			var suggestWrap = document.getElementById( 'wookiee-domain-suggestions' );
			var comWrap     = document.getElementById( 'wookiee-domain-suggestions-com' );
			var ukWrap    = document.getElementById( 'wookiee-domain-suggestions-uk' );

			if ( ! result.checked || ! result.suggestions ) {
				nameStatus.textContent = result.message
					? ( 'Suggested ‘' + result.site_name + '’ — ' + result.message )
					: ( 'Suggested ‘' + result.site_name + '’ — add a Spaceship API key/secret on Settings to also check domain availability.' );
				return;
			}

			var total = result.suggestions.com.length + result.suggestions.uk.length;
			if ( ! total ) {
				nameStatus.textContent = 'Suggested ‘' + result.site_name + '’ — no matching .com/.uk found available nearby, check manually.';
				return;
			}

			nameStatus.textContent = 'Suggested ‘' + result.site_name + '’ — pick a domain below to register, or keep the site title as-is.';
			comWrap.innerHTML = '';
			ukWrap.innerHTML = '';
			result.suggestions.com.forEach( function( item ) { comWrap.appendChild( buildDomainSuggestionRow( item ) ); } );
			result.suggestions.uk.forEach( function( item ) { ukWrap.appendChild( buildDomainSuggestionRow( item ) ); } );
			if ( ! result.suggestions.com.length ) {
				var noneCom = document.createElement( 'p' );
				noneCom.className = 'description';
				noneCom.textContent = 'None found available nearby.';
				comWrap.appendChild( noneCom );
			}
			if ( ! result.suggestions.uk.length ) {
				var noneCouk = document.createElement( 'p' );
				noneCouk.className = 'description';
				noneCouk.textContent = 'None found available nearby.';
				ukWrap.appendChild( noneCouk );
			}
			suggestWrap.hidden = false;
		}

		function buildDomainSuggestionRow( item ) {
			var row = document.createElement( 'div' );
			row.className = 'wookiee-domain-suggestion-row';
			var label = document.createElement( 'span' );
			label.textContent = item.domain;
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'button button-small';
			btn.textContent = 'Register';
			btn.addEventListener( 'click', function() { openRegisterDomainModal( item.domain ); } );
			row.appendChild( label );
			row.appendChild( btn );
			return row;
		}

		function makeRegField( labelText, inputType, id, value ) {
			var tr = document.createElement( 'tr' );
			var th = document.createElement( 'th' );
			th.textContent = labelText;
			var td = document.createElement( 'td' );
			var input = document.createElement( 'input' );
			input.type = inputType;
			input.id = id;
			if ( value ) { input.value = value; }
			td.appendChild( input );
			tr.appendChild( th );
			tr.appendChild( td );
			return tr;
		}

		// Builds a real registration form as a page overlay - not wired
		// into the Setup wizard's own step markup since it's a one-off
		// modal, not something that needs to persist/reload with the rest
		// of the page. Registering is a genuine purchase against whatever
		// payment method is on the Spaceship account, so this asks for
		// registrant details explicitly (nothing here is guessed/reused
		// from elsewhere except the organization name) and requires an
		// extra native confirm() on top of the modal's own Confirm button.
		function openRegisterDomainModal( domain ) {
			var overlay = document.createElement( 'div' );
			overlay.className = 'wookiee-register-domain-modal';
			var card = document.createElement( 'div' );
			card.className = 'wookiee-register-domain-card';

			var heading = document.createElement( 'h2' );
			heading.textContent = 'Register ' + domain;
			card.appendChild( heading );

			var notice = document.createElement( 'p' );
			notice.className = 'description';
			notice.textContent = 'This is a real purchase, charged to whatever payment method is on file for your Spaceship account — not a preview. Review everything below before confirming.';
			card.appendChild( notice );

			var table = document.createElement( 'table' );
			table.className = 'form-table';
			var orgField = document.getElementById( 'wookiee_setting_business_name' );

			table.appendChild( makeRegField( 'First name', 'text', 'wookiee-reg-first-name' ) );
			table.appendChild( makeRegField( 'Last name', 'text', 'wookiee-reg-last-name' ) );
			table.appendChild( makeRegField( 'Organization', 'text', 'wookiee-reg-org', orgField ? orgField.value : '' ) );
			table.appendChild( makeRegField( 'Email', 'email', 'wookiee-reg-email' ) );
			table.appendChild( makeRegField( 'Phone', 'tel', 'wookiee-reg-phone' ) );
			table.appendChild( makeRegField( 'Address line 1', 'text', 'wookiee-reg-address1' ) );
			table.appendChild( makeRegField( 'Address line 2', 'text', 'wookiee-reg-address2' ) );
			table.appendChild( makeRegField( 'City', 'text', 'wookiee-reg-city' ) );
			table.appendChild( makeRegField( 'County/State', 'text', 'wookiee-reg-state' ) );
			table.appendChild( makeRegField( 'Postal code', 'text', 'wookiee-reg-postal' ) );
			table.appendChild( makeRegField( 'Country (2-letter code)', 'text', 'wookiee-reg-country', 'GB' ) );

			var yearsRow = document.createElement( 'tr' );
			var yearsTh  = document.createElement( 'th' );
			yearsTh.textContent = 'Years';
			var yearsTd  = document.createElement( 'td' );
			var yearsSelect = document.createElement( 'select' );
			yearsSelect.id = 'wookiee-reg-years';
			for ( var y = 1; y <= 10; y++ ) {
				var opt = document.createElement( 'option' );
				opt.value = String( y );
				opt.textContent = y > 1 ? ( y + ' years' ) : '1 year';
				yearsSelect.appendChild( opt );
			}
			yearsTd.appendChild( yearsSelect );
			yearsRow.appendChild( yearsTh );
			yearsRow.appendChild( yearsTd );
			table.appendChild( yearsRow );

			var renewRow = document.createElement( 'tr' );
			var renewTh  = document.createElement( 'th' );
			renewTh.textContent = 'Auto-renew';
			var renewTd  = document.createElement( 'td' );
			var renewLabel = document.createElement( 'label' );
			var renewCheck = document.createElement( 'input' );
			renewCheck.type = 'checkbox';
			renewCheck.id = 'wookiee-reg-autorenew';
			renewLabel.appendChild( renewCheck );
			renewLabel.appendChild( document.createTextNode( ' Renew automatically each term (off by default)' ) );
			renewTd.appendChild( renewLabel );
			renewRow.appendChild( renewTh );
			renewRow.appendChild( renewTd );
			table.appendChild( renewRow );

			card.appendChild( table );

			var status = document.createElement( 'p' );
			status.id = 'wookiee-reg-status';
			status.style.color = '#646970';
			card.appendChild( status );

			var actions = document.createElement( 'div' );
			actions.className = 'wookiee-register-domain-actions';
			var confirmBtn = document.createElement( 'button' );
			confirmBtn.type = 'button';
			confirmBtn.className = 'button button-primary';
			confirmBtn.textContent = 'Confirm & register';
			var cancelBtn = document.createElement( 'button' );
			cancelBtn.type = 'button';
			cancelBtn.className = 'button';
			cancelBtn.textContent = 'Cancel';
			actions.appendChild( confirmBtn );
			actions.appendChild( cancelBtn );
			card.appendChild( actions );

			overlay.appendChild( card );
			document.body.appendChild( overlay );

			cancelBtn.addEventListener( 'click', function() { overlay.remove(); } );

			confirmBtn.addEventListener( 'click', function() {
				var fields = {
					first_name: document.getElementById( 'wookiee-reg-first-name' ).value.trim(),
					last_name: document.getElementById( 'wookiee-reg-last-name' ).value.trim(),
					organization: document.getElementById( 'wookiee-reg-org' ).value.trim(),
					email: document.getElementById( 'wookiee-reg-email' ).value.trim(),
					phone: document.getElementById( 'wookiee-reg-phone' ).value.trim(),
					address1: document.getElementById( 'wookiee-reg-address1' ).value.trim(),
					address2: document.getElementById( 'wookiee-reg-address2' ).value.trim(),
					city: document.getElementById( 'wookiee-reg-city' ).value.trim(),
					state: document.getElementById( 'wookiee-reg-state' ).value.trim(),
					postal_code: document.getElementById( 'wookiee-reg-postal' ).value.trim(),
					country: document.getElementById( 'wookiee-reg-country' ).value.trim(),
					years: yearsSelect.value,
					auto_renew: renewCheck.checked,
				};
				if ( ! fields.first_name || ! fields.last_name || ! fields.email || ! fields.phone || ! fields.address1 || ! fields.city || ! fields.country ) {
					status.textContent = 'Fill in every required field first.';
					return;
				}
				var confirmMsg = 'Register ' + domain + ' for ' + fields.years + ( fields.years > 1 ? ' years' : ' year' ) +
					( fields.auto_renew ? ' with auto-renew ON' : ' with auto-renew OFF' ) +
					'. This charges your Spaceship account now. Continue?';
				if ( ! window.confirm( confirmMsg ) ) { return; }

				confirmBtn.disabled = true;
				status.textContent = 'Submitting registration…';

				var regData = new FormData();
				regData.append( 'action', 'wookiee_register_domain' );
				regData.append( 'nonce', " . wp_json_encode( wp_create_nonce( 'wookiee_register_domain' ) ) . " );
				regData.append( 'domain', domain );
				Object.keys( fields ).forEach( function( key ) { regData.append( key, fields[ key ] ); } );

				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: regData } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						if ( ! res.success ) {
							confirmBtn.disabled = false;
							status.textContent = res.data && res.data.message ? res.data.message : 'Registration failed.';
							return;
						}
						status.textContent = 'Registering… this can take up to a minute.';
						pollDomainRegistration( res.data.operation_id, status, 0 );
					} )
					.catch( function() {
						confirmBtn.disabled = false;
						status.textContent = 'Registration failed — could not reach the server.';
					} );
			} );
		}

		function pollDomainRegistration( operationId, status, attempt ) {
			if ( attempt >= 15 ) {
				status.textContent = 'Still processing after a while — check your Spaceship dashboard for the final result.';
				return;
			}
			setTimeout( function() {
				var data = new FormData();
				data.append( 'action', 'wookiee_poll_domain_registration' );
				data.append( 'nonce', " . wp_json_encode( wp_create_nonce( 'wookiee_register_domain' ) ) . " );
				data.append( 'operation_id', operationId );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						if ( ! res.success ) {
							status.textContent = res.data && res.data.message ? res.data.message : 'Could not check status.';
							return;
						}
						if ( 'success' === res.data.status ) {
							status.textContent = 'Registered! Point it at your site from your Spaceship dashboard, then update Site Address (URL) under Settings > General.';
						} else if ( 'failed' === res.data.status ) {
							status.textContent = 'Registration failed: ' + ( res.data.details || 'no further detail from Spaceship.' );
						} else {
							pollDomainRegistration( operationId, status, attempt + 1 );
						}
					} )
					.catch( function() {
						pollDomainRegistration( operationId, status, attempt + 1 );
					} );
			}, 3000 );
		}

		function runChNameSearch( name, status ) {
			chBtn.disabled = true;
			status.textContent = 'Searching…';
			chSearchResults.hidden = false;
			chSearchResults.innerHTML = '<p class=\"wookiee-ch-search-msg\">Searching…</p>';
			var searchData = new FormData();
			searchData.append( 'action', 'wookiee_ch_search' );
			searchData.append( 'nonce', " . wp_json_encode( wp_create_nonce( 'wookiee_ch_search' ) ) . " );
			searchData.append( 'query', name );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: searchData } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					chBtn.disabled = false;
					status.textContent = '';
					if ( ! res.success ) {
						chSearchResults.innerHTML = '<p class=\"wookiee-ch-search-msg\">' + ( res.data && res.data.message ? res.data.message : 'Search failed.' ) + '</p>';
						return;
					}
					chSearchResults.innerHTML = '';
					res.data.results.forEach( function( item ) {
						var row = document.createElement( 'button' );
						row.type = 'button';
						row.className = 'wookiee-ch-search-result';
						row.innerHTML = '<strong>' + item.title + '</strong><br><span>' + item.company_number + ( item.address ? ' — ' + item.address : '' ) + '</span>';
						row.addEventListener( 'click', function() {
							chSearchResults.hidden = true;
							chSearchResults.innerHTML = '';
							chNumberField.value = item.company_number;
							runChNumberLookup( item.company_number, status );
						} );
						chSearchResults.appendChild( row );
					} );
				} )
				.catch( function() {
					chBtn.disabled = false;
					chSearchResults.innerHTML = '<p class=\"wookiee-ch-search-msg\">Search failed — could not reach the server.</p>';
				} );
		}

		if ( chBtn && chNumberField ) {
			chBtn.addEventListener( 'click', function() {
				var status = document.getElementById( 'wookiee-ch-lookup-status' );
				var value  = chNumberField.value.trim();
				if ( ! value ) {
					status.textContent = 'Enter a company number or name first.';
					return;
				}
				chSearchResults.hidden = true;
				chSearchResults.innerHTML = '';
				// Real Companies House numbers are 6-8 characters ending in
				// digits (e.g. 12345678, SC769264, NI045678) - names almost
				// never match that shape, so this is enough to route
				// correctly without asking the admin to pick a mode.
				var looksLikeNumber = /^[A-Za-z]{0,2}[0-9]{6,8}$/.test( value.replace( /\s+/g, '' ) );
				if ( looksLikeNumber ) {
					runChNumberLookup( value, status );
				} else {
					runChNameSearch( value, status );
				}
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
