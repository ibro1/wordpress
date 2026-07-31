<?php
/**
 * Niche-agnostic UK policy page generator (v2 spec §2b, phase 3 of the
 * roadmap - revised). Generates Terms, Privacy, Shipping, Returns,
 * Payment, Cookie Policy, and Cookie Preferences from the store's niche
 * brief and real business details already held in Wookiee Settings.
 *
 * Writes straight into the REAL, live page for each (creating it if
 * somehow missing) - these are plain legal/informational text pages
 * with nothing visual to preserve, so there's no separate "(AI Draft)"
 * copy to review and manually copy across; the compliance audit below
 * is the review step instead. The policy prompt below is adapted from
 * docs/policy writing law.txt (not shipped to the live server, since
 * deployment only copies the theme folder - so the same instructions
 * are reproduced here rather than read from that file at runtime).
 *
 * The Homepage, About, and Contact pages have real visual designs to
 * preserve, so they're handled differently - see the "Homepage Copy"
 * and "About & Contact Copy" tabs on Wookiee Settings (inc/theme-settings.php),
 * which regenerate every text slot in place via [wookiee_field] merge
 * tags / live settings, using the shared prompt-building/parsing
 * helpers below (wookiee_homepage_copy_fields(), wookiee_about_contact_copy_fields(),
 * wookiee_parse_copy_fields()).
 */

defined( 'ABSPATH' ) || exit;

/**
 * The 7 policy-style pages this generator can write - each maps to a
 * real starter-page slug (inc/static-content.php's wookiee_starter_pages()).
 * Generation edits that REAL page's content directly (creating it if
 * it's somehow missing), never a separate "(AI Draft)" copy - these
 * pages are plain legal/informational text with no visual design to
 * preserve, unlike About/Contact/Home, so editing in place is safe.
 */
function wookiee_content_generator_pieces() {
	return array(
		'terms'       => array( 'label' => 'Terms & Conditions', 'slug' => 'terms', 'title' => 'Terms and conditions' ),
		'privacy'     => array( 'label' => 'Privacy Policy', 'slug' => 'privacy', 'title' => 'Privacy policy' ),
		'shipping'    => array( 'label' => 'Shipping Policy', 'slug' => 'shipping', 'title' => 'Shipping policy' ),
		'returns'     => array( 'label' => 'Returns & Refunds Policy', 'slug' => 'returns', 'title' => 'Returns, refunds and cancellations' ),
		'payment'     => array( 'label' => 'Payment Policy', 'slug' => 'payment', 'title' => 'Payment policy' ),
		'cookies'     => array( 'label' => 'Cookie Policy', 'slug' => 'cookie', 'title' => 'Cookie policy' ),
		'cookie_pref' => array( 'label' => 'Cookie Preferences page', 'slug' => 'cookie-pref', 'title' => 'Cookie preferences' ),
	);
}

function wookiee_render_content_generator_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_key        = wookiee_central_api_configured() || '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$saved_brief    = get_option( 'wookiee_niche_brief', '' );
	$already_done   = wookiee_any_policy_page_ai_generated();
	$verb           = $already_done ? 'Regenerate' : 'Generate';
	$missing_fields = wookiee_missing_critical_business_fields();

	// Every policy page that's been AI-generated at least once, with
	// whatever audit result is currently persisted for it (if any) - lets
	// the compliance dashboard rehydrate instantly from the database on
	// page load instead of starting blank until Generate is clicked again.
	$persisted_pages = array();
	foreach ( wookiee_content_generator_pieces() as $piece ) {
		$page = get_page_by_path( $piece['slug'], OBJECT, 'page' );
		if ( ! $page || ! get_post_meta( $page->ID, '_wookiee_ai_generated', true ) ) {
			continue;
		}
		$report             = get_post_meta( $page->ID, '_wookiee_audit_report', true );
		$persisted_pages[] = array(
			'title'        => $piece['title'],
			'post_id'      => $page->ID,
			'preview_link' => get_permalink( $page->ID ),
			'report'       => $report ? $report : null,
			'persisted'    => true,
		);
	}
	?>
	<div class="wrap">
		<h1>Wookiee Content Generator</h1>
		<?php wookiee_render_model_picker(); ?>
		<p>Generates UK policy pages from the store's niche and the business details already saved in Wookiee Settings. Generating edits the <strong>real, live page directly</strong> — there's no separate draft copy to review and copy across manually. Every generated page is analysed for compliance automatically, with a chance to fix or tweak each one before you move on.</p>
		<p class="description">Looking to update the <strong>Homepage</strong> or <strong>About/Contact</strong> pages instead? Those have real visual designs to preserve, so they're regenerated from the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#homepage' ) ); ?>">Homepage Copy</a> and <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#about_contact' ) ); ?>">About &amp; Contact Copy</a> tabs on Wookiee Settings instead, where you can review the new text right in place before saving.</p>

		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>

		<?php if ( ! empty( $missing_fields ) ) : ?>
			<div class="notice notice-error"><p style="color:#b32d2e;font-weight:600;">Fill in these real business details before generating - otherwise every policy page will use this theme's placeholder demo business instead of yours: <?php echo esc_html( implode( ', ', $missing_fields ) ); ?>. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#business' ) ); ?>">Edit on the Business Identity tab</a>.</p></div>
		<?php endif; ?>

		<div id="wookiee-cg-generate-screen">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wookiee-niche-brief-2">Niche brief</label></th>
					<td>
						<div class="wookiee-niche-input-wrap is-textarea">
							<textarea id="wookiee-niche-brief-2" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $saved_brief ); ?></textarea>
							<?php wookiee_niche_suggest_button( 'wookiee-niche-brief-2' ); ?>
						</div>
						<p class="description">Shared with the Product Generator's niche brief. Click the sparkle to have AI suggest one.</p>
					</td>
				</tr>
				<tr>
					<th scope="row" id="wookiee-cg-policy-th">Policy pages to <?php echo strtolower( $verb ); ?></th>
					<td>
						<?php foreach ( wookiee_content_generator_pieces() as $key => $piece ) :
							$page       = get_page_by_path( $piece['slug'], OBJECT, 'page' );
							$page_done  = $page && get_post_meta( $page->ID, '_wookiee_ai_generated', true );
						?>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" class="wookiee-content-piece" value="<?php echo esc_attr( $key ); ?>" checked>
								<?php echo esc_html( $piece['label'] ); ?>
								<?php if ( $page_done ) : ?><span class="description">— already generated</span><?php endif; ?>
							</label>
						<?php endforeach; ?>
						<p class="description">Untick anything you don't want touched this time.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Custom prompt</th>
					<td>
						<label><input type="checkbox" id="wookiee-cg-custom-toggle"> Write my own instructions instead of the built-in prompt</label>
						<div id="wookiee-cg-custom-wrap" hidden style="margin-top:8px;">
							<textarea id="wookiee-cg-custom-prompt" rows="4" class="large-text" placeholder="e.g. Keep it under 400 words, use a very casual tone, and mention we only ship within the UK"></textarea>
							<p class="description">Applies to every checked page above. Real business details are still included automatically and nothing is ever invented beyond them, regardless of these instructions.</p>
						</div>
					</td>
				</tr>
			</table>

			<p>
				<button type="button" class="button button-primary" id="wookiee-content-generate-btn" <?php disabled( ! $has_key || ! empty( $missing_fields ) ); ?>><?php echo esc_html( $verb ); ?> selected pages</button>
				<span id="wookiee-content-generate-status" style="margin-left:8px;"></span>
			</p>
			<?php if ( ! empty( $missing_fields ) ) : ?>
				<p class="wookiee-cg-status-error">Fill in these first: <?php echo esc_html( implode( ', ', $missing_fields ) ); ?> - <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#business' ) ); ?>">Edit on the Business Identity tab</a>.</p>
			<?php endif; ?>
		</div>

		<div id="wookiee-cg-audit-screen" hidden>
			<p><button type="button" class="button" id="wookiee-cg-back-btn">&larr; Back to generate</button></p>
			<h2>Compliance review</h2>
			<p class="description">Each page below was analysed automatically (Google Merchant Center risk, UK consumer/privacy law, quality) — adapted from <code>docs/policy audit new.txt</code>'s US/GMC audit format for a UK-only store. Fix the issues in one click, or give a custom instruction for anything else you want changed, then reanalyse to confirm.</p>
			<div id="wookiee-cg-audit-cards"></div>
		</div>
	</div>
	<style>
		.wookiee-audit-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; margin-bottom: 14px; overflow: hidden; }
		.wookiee-audit-card-head {
			display: flex; align-items: center; gap: 12px; padding: 14px 20px; cursor: pointer;
		}
		.wookiee-audit-card-head:hover { background: #f6f7f7; }
		.wookiee-audit-card-title { font-weight: 600; flex: 1 1 auto; }
		.wookiee-audit-card-badge {
			font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
			background: #f0f0f1; color: #50575e; white-space: nowrap;
		}
		.wookiee-audit-card-badge.is-low { background: #edfaef; color: #00a32a; }
		.wookiee-audit-card-badge.is-medium { background: #fcf9e8; color: #996800; }
		.wookiee-audit-card-badge.is-high { background: #fcf0f1; color: #b32d2e; }
		.wookiee-audit-card-chevron { transition: transform 0.15s; color: #8a7d6d; }
		.wookiee-audit-card.is-open .wookiee-audit-card-chevron { transform: rotate(180deg); }
		.wookiee-audit-card-content { padding: 0 20px 20px; }
		.wookiee-audit-card-body { white-space: pre-wrap; margin: 0 0 14px; max-height: 320px; overflow-y: auto; background: #f6f7f7; border-radius: 6px; padding: 12px 14px; font-size: 13px; }
		.wookiee-audit-custom-instruction { width: 100%; max-width: 600px; margin-top: 10px; display: block; }
		.wookiee-cg-status-error { color: #b32d2e; font-weight: 600; }
	</style>
	<script>
	( function() {
		var NONCE = <?php echo wp_json_encode( wp_create_nonce( 'wookiee_generate_content' ) ); ?>;
		var PERSISTED_PAGES = <?php echo wp_json_encode( $persisted_pages ); ?>;

		var generateScreen = document.getElementById( 'wookiee-cg-generate-screen' );
		var auditScreen    = document.getElementById( 'wookiee-cg-audit-screen' );
		var cardsContainer = document.getElementById( 'wookiee-cg-audit-cards' );
		var backBtn        = document.getElementById( 'wookiee-cg-back-btn' );

		if ( backBtn ) {
			backBtn.addEventListener( 'click', function() {
				auditScreen.hidden = true;
				generateScreen.hidden = false;
			} );
		}

		function badgeFromReport( report ) {
			var scoreMatch = report.match( /OVERALL SCORE:\s*(\d+)/i );
			var riskMatch  = report.match( /GMC RISK:\s*(Low|Medium|High)/i );
			if ( ! scoreMatch && ! riskMatch ) { return { text: '', level: '' }; }
			var parts = [];
			if ( scoreMatch ) { parts.push( 'Score ' + scoreMatch[ 1 ] + '/10' ); }
			if ( riskMatch ) { parts.push( riskMatch[ 1 ] + ' risk' ); }
			return { text: parts.join( ' · ' ), level: riskMatch ? riskMatch[ 1 ].toLowerCase() : '' };
		}

		function setCardOpen( card, open ) {
			card.classList.toggle( 'is-open', open );
			card.querySelector( '.wookiee-audit-card-content' ).hidden = ! open;
		}

		// Populates a card's badge/body/actions from a report string -
		// shared by a fresh audit result (runAudit below) and a
		// persisted one rehydrated from postmeta on page load, so both
		// paths render identically.
		function applyReportToCard( card, report ) {
			var badge        = card.querySelector( '.wookiee-audit-card-badge' );
			var body         = card.querySelector( '.wookiee-audit-card-body' );
			var actions      = card.querySelector( '.wookiee-audit-card-actions' );
			var fixBtn       = card.querySelector( '.wookiee-audit-fix-btn' );
			var reanalyzeBtn = card.querySelector( '.wookiee-audit-reanalyze-btn' );
			body.textContent = report;
			card.setAttribute( 'data-report', report );
			actions.hidden = false;
			if ( fixBtn ) { fixBtn.hidden = false; }
			if ( reanalyzeBtn ) { reanalyzeBtn.hidden = true; }
			var b = badgeFromReport( report );
			badge.textContent = b.text;
			badge.className = 'wookiee-audit-card-badge' + ( b.level ? ' is-' + b.level : '' );
		}

		function runAudit( card, postId, keepOpenAfter ) {
			var badge  = card.querySelector( '.wookiee-audit-card-badge' );
			var body   = card.querySelector( '.wookiee-audit-card-body' );
			var actions = card.querySelector( '.wookiee-audit-card-actions' );
			setCardOpen( card, true );
			badge.textContent = 'Analysing…';
			badge.className = 'wookiee-audit-card-badge';
			body.textContent = 'Analysing…';
			actions.hidden = true;
			var data = new FormData();
			data.append( 'action', 'wookiee_audit_policy_page' );
			data.append( 'nonce', NONCE );
			data.append( 'post_id', postId );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( ! res.success ) {
						body.innerHTML = res.data && res.data.message ? res.data.message : 'Audit failed.';
						badge.textContent = 'Failed';
						actions.hidden = false;
						return;
					}
					applyReportToCard( card, res.data.report );
					if ( ! keepOpenAfter ) { setCardOpen( card, false ); }
				} )
				.catch( function() {
					body.textContent = 'Audit failed — could not reach the server.';
					badge.textContent = 'Failed';
					actions.hidden = false;
				} );
		}

		function buildCard( page ) {
			var card = document.createElement( 'div' );
			card.className = 'wookiee-audit-card';
			card.setAttribute( 'data-post-id', page.post_id );
			var needsAudit    = ! page.report && page.persisted;
			var initialBadge  = page.report ? '' : ( needsAudit ? 'Not yet analysed' : 'Waiting…' );
			var initialBody   = page.report ? '' : ( needsAudit ? 'Not analysed yet - click "Run analysis" below, or reanalyse anytime.' : 'Waiting…' );
			card.innerHTML =
				'<div class="wookiee-audit-card-head">' +
					'<span class="wookiee-audit-card-title"></span>' +
					'<span class="wookiee-audit-card-badge">' + initialBadge + '</span>' +
					( page.preview_link ? '<a href="' + page.preview_link + '" target="_blank" rel="noopener" class="button wookiee-audit-preview-link">Preview &#8599;</a>' : '' ) +
					'<span class="wookiee-audit-card-chevron">&#9662;</span>' +
				'</div>' +
				'<div class="wookiee-audit-card-content" hidden>' +
					'<div class="wookiee-audit-card-body">' + initialBody + '</div>' +
					'<div class="wookiee-audit-card-actions" hidden>' +
						'<button type="button" class="button button-primary wookiee-audit-fix-btn" hidden>Regenerate (fix these issues)</button> ' +
						'<button type="button" class="button wookiee-audit-reanalyze-btn" hidden>Reanalyse</button> ' +
						'<span class="wookiee-audit-card-status"></span>' +
						'<textarea class="wookiee-audit-custom-instruction" rows="2" placeholder="Or give a custom instruction, e.g. \'make this shorter and friendlier\'"></textarea>' +
						'<button type="button" class="button wookiee-audit-custom-btn">Regenerate with this instruction</button>' +
					'</div>' +
				'</div>';
			card.querySelector( '.wookiee-audit-card-title' ).textContent = page.title;

			card.querySelector( '.wookiee-audit-card-head' ).addEventListener( 'click', function( e ) {
				if ( e.target.closest( 'a' ) ) { return; }
				setCardOpen( card, ! card.classList.contains( 'is-open' ) );
			} );

			if ( page.report ) {
				applyReportToCard( card, page.report );
			} else if ( needsAudit ) {
				var actions      = card.querySelector( '.wookiee-audit-card-actions' );
				var reanalyzeBtn = card.querySelector( '.wookiee-audit-reanalyze-btn' );
				actions.hidden = false;
				reanalyzeBtn.hidden = false;
				reanalyzeBtn.textContent = 'Run analysis';
			}

			return card;
		}

		function wireCardActions( card, postId ) {
			var fixBtn       = card.querySelector( '.wookiee-audit-fix-btn' );
			var customBtn    = card.querySelector( '.wookiee-audit-custom-btn' );
			var reanalyzeBtn = card.querySelector( '.wookiee-audit-reanalyze-btn' );
			var status       = card.querySelector( '.wookiee-audit-card-status' );

			fixBtn.addEventListener( 'click', function() {
				var report = card.getAttribute( 'data-report' ) || '';
				fixBtn.disabled = true;
				status.textContent = 'Rewriting…';
				var data = new FormData();
				data.append( 'action', 'wookiee_apply_audit_fixes' );
				data.append( 'nonce', NONCE );
				data.append( 'post_id', postId );
				data.append( 'audit_report', report );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						fixBtn.disabled = false;
						if ( ! res.success ) {
							status.innerHTML = res.data && res.data.message ? res.data.message : 'Failed to apply fixes.';
							status.classList.add( 'wookiee-cg-status-error' );
							return;
						}
						status.classList.remove( 'wookiee-cg-status-error' );
						status.textContent = 'Updated.';
						reanalyzeBtn.hidden = false;
					} )
					.catch( function() {
						fixBtn.disabled = false;
						status.textContent = 'Failed — could not reach the server.';
					} );
			} );

			customBtn.addEventListener( 'click', function() {
				var instruction = card.querySelector( '.wookiee-audit-custom-instruction' ).value.trim();
				if ( ! instruction ) {
					status.textContent = 'Describe what you want changed first.';
					return;
				}
				customBtn.disabled = true;
				status.textContent = 'Rewriting…';
				var data = new FormData();
				data.append( 'action', 'wookiee_apply_custom_policy_prompt' );
				data.append( 'nonce', NONCE );
				data.append( 'post_id', postId );
				data.append( 'instruction', instruction );
				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
					.then( function( r ) { return r.json(); } )
					.then( function( res ) {
						customBtn.disabled = false;
						if ( ! res.success ) {
							status.innerHTML = res.data && res.data.message ? res.data.message : 'Failed to apply.';
							status.classList.add( 'wookiee-cg-status-error' );
							return;
						}
						status.classList.remove( 'wookiee-cg-status-error' );
						status.textContent = 'Updated.';
						reanalyzeBtn.hidden = false;
					} )
					.catch( function() {
						customBtn.disabled = false;
						status.textContent = 'Failed — could not reach the server.';
					} );
			} );

			reanalyzeBtn.addEventListener( 'click', function() {
				status.textContent = '';
				runAudit( card, postId, true );
			} );
		}

		// Rehydrate the compliance dashboard from whatever's persisted in
		// the database, so a plain page reload shows the last known
		// analysis instantly instead of an empty generate screen -
		// nothing here calls the LLM; pages with no stored report yet
		// just get a manual "Run analysis" trigger instead of auto-fetching.
		if ( PERSISTED_PAGES.length ) {
			generateScreen.hidden = true;
			auditScreen.hidden = false;
			PERSISTED_PAGES.forEach( function( p ) {
				var card = buildCard( p );
				cardsContainer.appendChild( card );
				wireCardActions( card, p.post_id );
			} );
		}

		var customToggleEl = document.getElementById( 'wookiee-cg-custom-toggle' );
		var customWrapEl   = document.getElementById( 'wookiee-cg-custom-wrap' );
		if ( customToggleEl && customWrapEl ) {
			customToggleEl.addEventListener( 'change', function() {
				customWrapEl.hidden = ! customToggleEl.checked;
			} );
		}

		var genBtn = document.getElementById( 'wookiee-content-generate-btn' );
		if ( ! genBtn ) {
			return;
		}
		genBtn.addEventListener( 'click', function() {
			var status  = document.getElementById( 'wookiee-content-generate-status' );
			var brief   = document.getElementById( 'wookiee-niche-brief-2' ).value.trim();
			var checked = Array.prototype.slice.call( document.querySelectorAll( '.wookiee-content-piece:checked' ) ).map( function( el ) { return el.value; } );

			if ( ! brief ) {
				status.textContent = 'Describe the niche first.';
				return;
			}
			if ( ! checked.length ) {
				status.textContent = 'Select at least one item to generate.';
				return;
			}

			genBtn.disabled = true;
			status.textContent = 'Generating ' + checked.length + ' item(s) with the LLM… this can take a minute or two.';

			var data = new FormData();
			data.append( 'action', 'wookiee_generate_content' );
			data.append( 'nonce', NONCE );
			data.append( 'brief', brief );
			checked.forEach( function( key ) { data.append( 'pieces[]', key ); } );

			var customToggle = document.getElementById( 'wookiee-cg-custom-toggle' );
			if ( customToggle && customToggle.checked ) {
				var customPrompt = document.getElementById( 'wookiee-cg-custom-prompt' ).value.trim();
				if ( customPrompt ) { data.append( 'custom_prompt', customPrompt ); }
			}

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					genBtn.disabled = false;
					if ( ! res.success ) {
						status.innerHTML = res.data && res.data.message ? res.data.message : 'Generation failed.';
						status.classList.add( 'wookiee-cg-status-error' );
						return;
					}
					status.classList.remove( 'wookiee-cg-status-error' );
					status.textContent = '';

					// The button/checkbox labels reflect server-render-time
					// state and won't know a generation just happened without
					// this - otherwise "Back to generate" would still show
					// "Generate" for pages that were just written.
					checked.forEach( function( key, i ) {
						var result = res.data.pages[ i ];
						if ( ! result || result.error ) { return; }
						var checkbox = document.querySelector( '.wookiee-content-piece[value="' + key + '"]' );
						var label    = checkbox ? checkbox.closest( 'label' ) : null;
						if ( label && ! label.querySelector( '.wookiee-cg-already-generated' ) ) {
							var span = document.createElement( 'span' );
							span.className = 'description wookiee-cg-already-generated';
							span.textContent = ' — already generated';
							label.appendChild( span );
						}
					} );
					if ( res.data.pages.some( function( p ) { return ! p.error; } ) ) {
						genBtn.textContent = 'Regenerate selected pages';
						var policyTh = document.getElementById( 'wookiee-cg-policy-th' );
						if ( policyTh ) { policyTh.textContent = 'Policy pages to regenerate'; }
					}

					generateScreen.hidden = true;
					auditScreen.hidden = false;
					cardsContainer.innerHTML = '';

					var validPages = res.data.pages.filter( function( p ) { return p.post_id && ! p.error; } );
					var errorPages = res.data.pages.filter( function( p ) { return p.error; } );

					errorPages.forEach( function( p ) {
						var card = document.createElement( 'div' );
						card.className = 'wookiee-audit-card';
						card.innerHTML = '<div class="wookiee-audit-card-head"><h3></h3></div><div class="wookiee-audit-card-body"></div>';
						card.querySelector( 'h3' ).textContent = p.title;
						card.querySelector( '.wookiee-audit-card-body' ).textContent = p.error;
						cardsContainer.appendChild( card );
					} );

					// Sequential, not parallel - avoids firing a dozen
					// concurrent LLM calls at once when several pages are
					// generated together.
					var chain = Promise.resolve();
					validPages.forEach( function( p ) {
						var card = buildCard( p );
						cardsContainer.appendChild( card );
						wireCardActions( card, p.post_id );
						chain = chain.then( function() { return runAudit( card, p.post_id ); } );
					} );
				} )
				.catch( function() {
					genBtn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<?php
}

add_action( 'wp_ajax_wookiee_generate_content', 'wookiee_generate_content_handler' );
function wookiee_generate_content_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	$brief         = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	$pieces        = isset( $_POST['pieces'] ) && is_array( $_POST['pieces'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['pieces'] ) ) : array();
	$custom_prompt = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_prompt'] ) ) : '';

	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}
	if ( empty( $pieces ) ) {
		wp_send_json_error( array( 'message' => 'Select at least one item to generate.' ) );
	}

	$missing_fields = wookiee_missing_critical_business_fields();
	if ( ! empty( $missing_fields ) ) {
		wp_send_json_error( array( 'message' => 'Fill in these real business details first: ' . implode( ', ', $missing_fields ) . ' - on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#business' ) ) . '" target="_blank" rel="noopener">Business Identity tab</a>.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	$available = wookiee_content_generator_pieces();
	$results   = array();

	foreach ( $pieces as $key ) {
		if ( ! isset( $available[ $key ] ) ) {
			continue;
		}
		$piece = $available[ $key ];

		/*
		 * Feed the previous audit back in on a regeneration.
		 *
		 * Without this, "Regenerate" rebuilt the identical prompt from the
		 * identical brief, so the model had no idea the last attempt had
		 * already been reviewed and found wanting - it would produce
		 * essentially the same page and score the same again, which is
		 * exactly what regenerating was supposed to fix. The report is read
		 * here, before wookiee_update_real_static_page() clears it as stale.
		 */
		$previous_audit = wookiee_previous_audit_for_piece( $piece );

		$prompt = '' !== trim( $custom_prompt )
			? wookiee_build_custom_policy_prompt( $piece['title'], $custom_prompt )
			: wookiee_build_content_prompt( $key, $brief, $previous_audit );
		$text   = wookiee_call_llm( $prompt, wookiee_content_piece_max_tokens( $key ) );

		if ( is_wp_error( $text ) ) {
			$results[] = array(
				'title'        => esc_html( $piece['title'] ),
				'error'        => esc_html( $text->get_error_message() ),
				'post_id'      => 0,
				'edit_link'    => '',
				'preview_link' => '',
			);
			continue;
		}

		$post_id = wookiee_update_real_static_page( $piece['slug'], $piece['title'], $text );
		$results[] = array(
			'title'        => esc_html( $piece['title'] ),
			'error'        => '',
			'post_id'      => $post_id,
			'edit_link'    => $post_id ? get_edit_post_link( $post_id, 'raw' ) : '',
			'preview_link' => $post_id ? get_permalink( $post_id ) : '',
		);
	}

	wp_send_json_success( array( 'pages' => $results ) );
}

/**
 * The full list of homepage copy slots an AI regeneration writes to -
 * every one of these is a real wookiee_setting_* option that
 * front-page.php reads live, so writing fresh text into them updates
 * the homepage's design/layout as-is, without ever creating a separate
 * draft page. Field key === the labelled-section name (lowercased) ===
 * the setting key, so parsing and applying need no separate mapping.
 */
function wookiee_homepage_copy_fields() {
	return array( 'hero_eyebrow', 'hero_headline', 'hero_subheadline', 'hero_cta_primary', 'hero_cta_secondary', 'hero_stat_label',
		'trust_1_title', 'trust_2_title', 'trust_2_desc', 'trust_3_title', 'trust_3_desc',
		'products_kicker', 'products_title',
		'categories_kicker', 'categories_title', 'categories_subtitle',
		'how_it_works_kicker', 'how_it_works_title', 'how_it_works_lead',
		'how_it_works_step1_title', 'how_it_works_step1_desc',
		'how_it_works_step2_title', 'how_it_works_step2_desc',
		'how_it_works_step3_title', 'how_it_works_step3_desc', 'how_it_works_cta',
		'collections_kicker', 'collections_title',
		'homepage_philosophy_heading', 'homepage_philosophy',
	);
}

/**
 * The full list of About/Contact page copy slots - same principle as
 * the homepage fields above, but for the two other "designed" pages
 * (real layout/HTML, not plain policy text). Each is embedded in the
 * live page via a [wookiee_field key="..."] merge tag, so rewriting
 * these settings updates the actual page without touching its markup.
 */
function wookiee_about_contact_copy_fields() {
	return array( 'about_hero_kicker', 'about_hero_heading', 'about_hero_lead', 'about_hero_body', 'about_cta_primary', 'about_cta_secondary',
		'about_stat_kicker', 'about_legal_note', 'about_fulfilment_title', 'about_fulfilment_note', 'about_delivery_note',
		'about_section2_kicker', 'about_section2_heading', 'about_section2_lead', 'about_section2_body1', 'about_section2_body2',
		'about_highlight_title', 'about_highlight_desc',
		'contact_kicker', 'contact_heading', 'contact_lead', 'contact_form_subtitle',
	);
}

/**
 * Parses a labelled-section AI response (LABEL_NAME: value, one per
 * field) into a plain array keyed by lowercased label - shared by the
 * homepage and About/Contact generators since both use the same
 * "one label per setting key" convention.
 */
function wookiee_parse_copy_fields( $text, array $field_keys ) {
	$labels = array();
	foreach ( $field_keys as $key ) {
		$labels[ strtoupper( $key ) ] = $key;
	}
	return wookiee_parse_labelled_sections( $text, $labels );
}

function wookiee_parse_homepage_copy( $text ) {
	return wookiee_parse_copy_fields( $text, wookiee_homepage_copy_fields() );
}

/**
 * A single block of real business details, shared by every prompt below
 * so the model has the same facts to draw from and nothing to invent.
 */
/**
 * Which core business facts are genuinely missing right now - checked
 * against the REAL saved option, not wookiee_get_setting()'s fallback
 * default, since every one of these fields ships with a plausible-
 * looking demo value ("Wookiee Decor Ltd", a fake company number, a
 * Cowdenbeath address). An admin who never touched Settings would
 * otherwise silently generate legal pages full of someone else's
 * placeholder business details and have no way of knowing. Only the
 * facts a policy page cannot be coherent without are checked here -
 * things like the phone number or support hours degrade gracefully
 * with an honest placeholder instead, so they don't block generation.
 */
function wookiee_missing_critical_business_fields() {
	$required = array(
		'business_name'      => 'Registered company name',
		'registered_address' => 'Registered office address',
		'company_number'     => 'Company number',
		'contact_email'      => 'Contact email',
	);
	$missing = array();
	foreach ( $required as $key => $label ) {
		if ( '' === trim( (string) get_option( 'wookiee_setting_' . $key, '' ) ) ) {
			$missing[] = $label;
		}
	}
	return $missing;
}

function wookiee_business_details_block() {
	$lines = array(
		/*
		 * The prompts ask for an effective-date line "using the current date".
		 * A language model has no clock, so it was answering from its training
		 * data - the live terms page went out stamped "Last updated: October
		 * 2023", which is exactly the kind of stale-looking date a compliance
		 * reviewer treats as evidence the page is unmaintained. Today's date
		 * is a business fact like any other and belongs in this block.
		 */
		'Today\'s date (use this, and only this, for any "last updated" or effective-date line): ' . date_i18n( 'j F Y' ),
		'Business/trading name: ' . wookiee_get_setting( 'business_name' ),
		'Registered address: ' . str_replace( "\n", ', ', wookiee_get_setting( 'registered_address' ) ),
		'Company number: ' . wookiee_get_setting( 'company_number' ),
		'Contact email: ' . wookiee_get_setting( 'contact_email' ),
		'Contact phone: ' . wookiee_get_setting( 'contact_phone' ),
		'Support hours: ' . wookiee_get_setting( 'support_hours' ),
		'Countries served: ' . wookiee_get_setting( 'countries_served' ),
		'Flat shipping rate: £' . wookiee_get_setting( 'shipping_rate' ),
		/*
		 * Handling and transit are given separately, then the total, because
		 * a shipping/returns policy has to be explicit about which clock a
		 * given promise runs on. The free-text summary below is what the
		 * storefront already displays; it is labelled as such so the model
		 * treats the three figures above as authoritative and doesn't emit a
		 * policy that contradicts its own delivery estimate.
		 */
		'Handling time before dispatch: ' . wookiee_get_setting( 'handling_time' ),
		'Transit time once with the carrier: ' . wookiee_get_setting( 'transit_time' ),
		'Estimated total delivery time (handling plus transit): ' . wookiee_get_setting( 'estimated_delivery' ),
		'Delivery summary already shown on the storefront (keep any policy consistent with this): ' . wookiee_get_setting( 'shipping_dispatch' ),
		/*
		 * The shipping prompt below asks for this by name ("the 'Orders are
		 * dispatched from' value in the business details") but the value was
		 * never actually put in the business details - so the model was being
		 * told to quote a field it could not see, and filled the gap however it
		 * liked. Labelled here with the exact phrase the prompt uses.
		 */
		'Orders are dispatched from: ' . wookiee_get_setting( 'dispatch_origin' ),
		'Returns period offered: ' . wookiee_get_setting( 'returns_period_days' ) . ' days (this is the business\'s own voluntary returns window - it sits ALONGSIDE, not instead of, the customer\'s 14-day statutory right to cancel under the Consumer Contracts Regulations; state both clearly rather than treating them as conflicting)',
		/*
		 * Distinct from the returns window: cancelling an order that hasn't
		 * shipped yet, versus sending back goods already received. Policies
		 * routinely conflate the two, so they're labelled explicitly here.
		 */
		'Order cancellation period after ordering (before dispatch - not the same as the returns window above): ' . wookiee_get_setting( 'cancellation_period' ),
		'Restocking fees: ' . wookiee_get_setting( 'restocking_fee' ),
		'Returns address: ' . str_replace( "\n", ', ', wookiee_get_returns_address() ),
		/*
		 * Both of these were named by the prompts below and supplied by
		 * nothing, so the model did as it was told and published
		 * "[Business input required: ...]" placeholders in their place.
		 * Carriers may legitimately be empty - the prompt has a generic
		 * fallback for it - so the line says so rather than leaving the model
		 * to interpret a bare empty value.
		 */
		'Delivery carriers used: ' . ( '' !== trim( (string) wookiee_get_setting( 'shipping_carriers' ) ) ? wookiee_get_setting( 'shipping_carriers' ) : 'not specified - refer to "a tracked UK courier service" in general terms, do not name a carrier and do not use a placeholder' ),
		'Payment methods accepted: ' . wookiee_get_setting( 'payment_methods' ),
		'Website: ' . home_url( '/' ),
	);
	return implode( "\n", $lines );
}

/**
 * Policy pages need more room than brand-voice copy - a cookie or terms
 * page covering every required section can run well past 2048 tokens
 * and get cut off mid-sentence, which is exactly what a compliance audit
 * will (correctly) flag as an "incomplete" issue.
 */
function wookiee_content_piece_max_tokens( $key ) {
	// Every piece this generator handles is now a full policy page (see
	// wookiee_content_generator_pieces()) - these can run well past 2048
	// tokens and get cut off mid-sentence otherwise.
	return 4096;
}

/**
 * Voice instructions shared by every customer-facing page.
 *
 * Named anti-patterns rather than an abstract "write authentically": models
 * reliably reproduce the exact phrases below when asked for ecommerce copy,
 * and a generic instruction to avoid being generic does not stop it. The
 * dropshipping tells in particular are what make a new store read as
 * disposable to both customers and reviewers.
 */
function wookiee_founder_voice_block() {
	return "VOICE - this is written BY the founder/director of the business, in their own words:\n"
		. "- Write in the first person as the owner (\"I started this because...\", \"we keep our range small because...\"). Not a faceless corporate register, and not a marketing agency describing the business from outside.\n"
		. "- Be specific to THIS business and its niche. Every sentence should be one that could not be copied onto a different store's site unchanged. If a sentence would work equally well for a phone-case shop and a garden-furniture shop, it is filler - cut it or replace it with something concrete.\n"
		. "- Never use these phrases or close variants, they are the standard tells of a templated dropshipping site: \"we are passionate about\", \"our mission is to bring you\", \"curated collection\", \"carefully curated\", \"sourced from trusted suppliers around the world\", \"quality you can trust\", \"we believe that everyone deserves\", \"at [business name], we...\", \"your one-stop shop\", \"look no further\", \"we pride ourselves on\", \"elevate your\", \"transform your space\".\n"
		. "- Do not imply scale, facilities, staff, heritage, or partnerships that are not in the business details above - no invented warehouses, teams, design studios, founding dates, or awards. A small, honestly-described business reads as more credible than a vague large one.\n"
		. "- It is fine, and better, to acknowledge being small and deliberate about it - a short range, a specific reason for stocking what it stocks, a personal standard the owner holds.\n"
		. "- Use plain British English. No exclamation marks, no hype adjectives stacked together, no sentence that exists only to sound warm.\n";
}

/**
 * The "resolve these audit findings" section appended to a regeneration.
 *
 * Its own editable slot rather than part of each policy prompt, because it
 * is conditional - it only exists when the page has actually been audited -
 * and a flat editable template cannot express that. Folding it into the six
 * policy prompts would mean an override either always claimed a previous
 * review existed, or lost the behaviour entirely.
 */
function wookiee_build_audit_feedback_block( $previous_audit ) {
	$block = "A previous version of this exact page was reviewed by a compliance auditor and scored below full marks. That review is reproduced below. You are rewriting the page specifically to resolve every issue it raises - this is not a fresh first draft.\n\n"
		. "--- PREVIOUS COMPLIANCE REVIEW ---\n" . trim( (string) $previous_audit ) . "\n--- END REVIEW ---\n\n"
		. "Address every point in that review. Keep whatever the review did not criticise. Where the review says information is missing and it genuinely isn't available in the business details above, use an explicit \"[Business input required: X]\" placeholder rather than inventing it or silently omitting the section - a missing fact the owner can fill in scores better, and is more honest, than a plausible-sounding guess.\n\n"
		. "Apply the placeholder test from the rules above before writing one, and apply it to any placeholder already in the page you are rewriting: if the fact is now present in the business details, fill it in; if the topic has a true general formulation, use it; if it is standard or statutory wording, write it out. A placeholder that survives a rewrite because it was in the previous draft is the most common way these pages keep the same score twice.";

	return wookiee_maybe_override( 'policy_audit_feedback', $block, array( 'previous_audit' => $previous_audit ) );
}

function wookiee_build_content_prompt( $key, $brief, $previous_audit = '' ) {
	$policy_labels = array(
		'terms'    => 'Terms & Conditions',
		'privacy'  => 'Privacy Policy',
		'shipping' => 'Shipping Policy',
		'returns'  => 'Returns & Refunds Policy',
		'payment'  => 'Payment Policy',
		'cookies'  => 'Cookie Policy',
	);

	if ( isset( $policy_labels[ $key ] ) ) {
		$prompt = "Act as a UK e-commerce legal policy writer. Write a complete, ready-to-publish {$policy_labels[ $key ]} page for a UK online store.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "Rules:\n"
			. "- Check against UK GDPR, the Data Protection Act 2018, the Consumer Rights Act 2015, the Consumer Contracts Regulations, the Electronic Commerce Regulations, and PECR, wherever relevant to this specific policy.\n"
			. "- Do not invent any business fact beyond the details given above. If something relevant is missing, write a clear inline placeholder like \"[Business input required: X]\" instead of guessing.\n"
			. "- A placeholder is a LAST RESORT, not a way to acknowledge a topic. Before writing one, check three things: is the fact already in the business details above under a different label; does the topic have an accepted general formulation that is true without it (carriers are the standard example - \"a tracked UK courier service\" is a complete answer); and is it something only the owner can supply at all. Statutory templates and standard wording are NEVER a business input - a model cancellation form, for instance, is a fixed form filled in from the business name and address given above, so write it out rather than asking the owner for it. Only a genuine, owner-only, un-generalisable fact earns a placeholder. Every placeholder published is a visible gap on a live page, and a reviewer counts it as missing information rather than as honesty.\n"
			. "- Do not copy another company's policy text.\n"
			. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
			. "- A policy page is still the business speaking, not a template: where it is not a legal requirement to use set wording, use the business's own plain voice.\n"
			. "- Include a clearly labelled section near the end on how customers can contact the business about this policy, using the contact email given above.\n"
			. "- In that same section, also explain how a customer can escalate a complaint if they're unhappy with how it was handled directly - mention that UK customers can contact Citizens Advice or their local Trading Standards if the issue can't be resolved with the business directly. Do not just give the contact email and stop.\n"
			. "- Include a brief note that this policy may be updated from time to time and customers should check this page periodically.\n"
			. "- Where genuinely relevant, refer to the store's other policies by name (e.g. mention the Privacy Policy when discussing personal data, the Returns Policy when discussing refunds) rather than repeating their content.\n"
			. "- State the business's full legal/trading name and company registration number explicitly within the body text itself (not only implied) - UK company law expects this on formal business documents, and it must appear even if it feels repetitive with other sections.\n"
			. "- Do NOT tell the reader to consult a solicitor, and do NOT state that this is not legal advice. This page is the shop speaking to its customers; advice about having it reviewed is for the shop owner, not for the person reading it, and printing it undermines the document in front of the very people it is written for.\n"
			. "- Open with a short PLAIN-ENGLISH SUMMARY headed '## The short version': three to five bullet-style sentences giving the answers most readers came for, before any formal wording. State that it is a summary and the detail below governs. Almost no store does this; it is the single thing that makes a policy page usable rather than merely present.\n"
			. "- Give an effective date line ('Last updated: <month year>') taken from the \"Today's date\" line in the business details above - do not use any other date, and do not date it from memory - and say the current version is the one that applies to orders placed while it is published.\n"
			. "- Name the actual legislation where it is relied on, in full and correctly: the Consumer Rights Act 2015, the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, the Electronic Commerce (EC Directive) Regulations 2002, UK GDPR and the Data Protection Act 2018, the Privacy and Electronic Communications Regulations 2003. A named instrument is checkable; 'consumer law' is not.\n"
			. "- Include a pricing and availability error clause: what happens if an item is listed at the wrong price or is unavailable after an order is placed - that the business may cancel and refund in full before dispatch, and that a customer is never charged more than the price shown at checkout without agreeing to it.\n"
			. "- Open with a SCOPE paragraph: what this policy covers, which goods it applies to, and which country's customers. Then a DEFINITIONS line fixing the ambiguous terms it uses - that 'we', 'us' and 'our' mean the named legal entity, and that 'UK local time' means Greenwich Mean Time or British Summer Time as applicable.\n"
			. "- Include a statutory-rights savings clause, in substance: nothing in this policy excludes, restricts or replaces any statutory consumer right or remedy that cannot lawfully be excluded or restricted. A policy that reads as if it overrides consumer law is worse than no policy.\n"
			. "- State amounts in pounds sterling and say so. State time periods in CALENDAR days (or working days) and say which - 'within 14 days' is ambiguous and ambiguity is what disputes are made of.\n"
			. "- Be concrete wherever a real value exists in the business details above. A number, a place name or a named provider is worth a paragraph of careful phrasing; 'promptly', 'as soon as possible' and 'a reasonable period' tell the reader nothing and commit the business to nothing.\n"
			. "- Structure it with SECTION HEADINGS. Start with the page title on its own line, then break the body into clearly-labelled sections, each heading on its own line prefixed with '## ' (e.g. '## How long delivery takes'). A wall of undifferentiated paragraphs is unreadable, and a customer looking for one specific answer will not find it.\n"
			. "- Name the sections after what a customer is actually looking for, in their words, rather than legal register: 'How long delivery takes', not 'Delivery Timeframes Provision'.\n"
			. "- Be thorough. Aim for 1000-1500 words. Cover the ordinary awkward cases as well as the happy path - what happens when something is late, damaged, undeliverable, or the customer changes their mind - because those are the situations that send someone to this page in the first place.\n"
			. "- Output ONLY the finished policy text. Plain paragraphs separated by blank lines, with '## ' section headings as described. No other markdown, no HTML, no commentary, no A/B/C-style breakdown - just the finished, publishable page.";

		if ( in_array( $key, array( 'privacy', 'cookies' ), true ) ) {
			$prompt .= "\n\nThis policy must explicitly explain the data subject's rights under UK GDPR: the right to access, rectify, erase, restrict processing of, and port their personal data, the right to object, and the right to withdraw consent at any time - and state plainly that requests to exercise these rights can be sent to the contact email given above, describing briefly what a customer should expect after making such a request.";
		}

		if ( 'cookies' === $key ) {
			$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts (do not describe any other mechanism, and do not omit it): " . wookiee_cookie_consent_mechanism_description() . "\n"
				. "Also state explicitly how long cookies are retained on the customer's device (state the actual duration if given above, otherwise state a typical, honest range such as \"up to 12 months\" rather than omitting it), commit to notifying customers of this page if the categories of cookies used change in future, and briefly explain how a customer can exercise their data rights specifically in relation to cookie data (not just personal data generally).";
		}

		if ( 'terms' === $key ) {
			$prompt .= "\n\nWhere this page mentions the Payment Policy or Returns Policy, state the specific page slug so it reads as a real link once published (e.g. \"our Payment Policy at /payment/\" and \"our Returns Policy at /returns/\") rather than naming the policy with no way to find it.\n"
				. "For the section on cancelling an order, explicitly list the accepted methods for a customer to inform the business of their decision to cancel (e.g. email using the contact address given above, or a clear written statement) - do not just say \"inform us\" without saying how.\n"
				. "Where shipping is mentioned, note that delivery timeframes and methods are covered in full on the Shipping Policy (at /shipping/) rather than repeating or inventing shipping-method specifics here.";
		}

		if ( 'privacy' === $key ) {
			$prompt .= "\n\nGive RETENTION PERIODS as actual durations against each category of data - order and transaction records kept for six years to meet UK tax and accounting obligations, support correspondence for a stated period, marketing consent until withdrawn - not 'as long as necessary', which is the phrase every weak notice uses and which tells the reader nothing.\n"
				. "Cover INTERNATIONAL TRANSFERS: whether personal data is transferred outside the UK, and if so that it is protected by UK adequacy regulations or the International Data Transfer Agreement/Addendum. Say honestly that common service providers may process data outside the UK rather than implying everything stays in the country.\n"
				. "State the right to complain to the Information Commissioner's Office with its real contact route (ico.org.uk, helpline 0303 123 1113), and that the customer is encouraged but not required to raise it with the business first.\n"
				. "Name the data controller explicitly - the registered legal entity, not the trading name alone.\n"
				. "Be accurate about payment data: the payment provider collects card details directly through its own secure interface, and the shop itself does not receive or store complete card numbers or security codes. Claiming to hold less data than you do is as wrong as claiming to hold more, so describe what the shop genuinely receives - typically a masked card reference, an amount, and an authorisation result.\n"
				. "Say that creating an account is optional where guest checkout is offered, and list what is additionally processed for account holders.\n"
				. "Cover the evidence customers send in support of a claim - photographs or video of damage or incorrect delivery - since that is personal data the shop asks for and rarely mentions.\n"
				. "Include a full postal address for privacy-related enquiries and complaints, not just the contact email - use the registered address given above.\n"
				. "Describe HOW consent is obtained for anything that requires it (e.g. a checkbox at checkout or on a sign-up form) and explicitly how a customer can withdraw that consent at any time - do not just state that consent is a legal basis without explaining the mechanism.\n"
				. "Include a brief, specific summary of what cookies are used for (not just a reference), and provide the actual page path to the full Cookie Policy (/cookie/) so it reads as a real link.\n"
				. "Avoid generic filler phrases like \"we value your privacy\" with nothing concrete behind them - every sentence should state a specific, real practice.";
		}

		if ( 'shipping' === $key ) {
			$prompt .= "\n\nFULFILMENT TRANSPARENCY. State plainly where orders are dispatched from, using the 'Orders are dispatched from' value in the business details verbatim in substance - it names a town and a country, and that named place is the whole point of the disclosure. This is the first thing a payment provider or Google Merchant Center review looks for, and its absence is what makes a store look like it is hiding a supply chain.\n"
				. "Do not hedge that sentence and do not soften it. No 'may', 'typically', 'usually', 'where possible' or similar qualifier in front of the dispatch location; no 'supplier partners', 'fulfilment partners', 'third-party logistics' or equivalent standing in for the named place. A hedged origin reads worse to a reviewer than a plain one, because it answers the question with a refusal to answer it.\n"
				. "If that field is empty, omit the topic entirely rather than filling the gap: make no claim about warehouse location, country of origin or who fulfils, and do not substitute a vague sentence about details being confirmed on dispatch. That is the same rule already applied to the company number and registered address above - state what the business has told you and nothing else - and it matters more here because a wrong answer about fulfilment is the kind a payment provider acts on.\n"
				. "Name the delivery providers in general or specific terms, making clear that a carrier transports parcels and does not select, store or pack them.\n"
				. "Address the United Kingdom's edges explicitly: whether the Crown Dependencies (Jersey, Guernsey, the Isle of Man) and the offshore islands are covered, since they are commonly assumed to be included and are not part of the UK for delivery purposes.\n"
				. "State whether the delivery charge is per order or per item, and whether collection in person is available - a registered office is not a shop, and customers do turn up.\n"
				. "Commit to updating this page if the fulfilment arrangements materially change.\n\n"
				. "State the delivery timings EXPLICITLY and separately, using the real values given in the business details above: how long before an order is dispatched (handling time), how long it then takes in transit, and the total a customer should expect from order to doorstep. This is the single thing most people open a shipping page to find, and a policy that describes dispatch without ever saying when the parcel arrives has not answered it.\n"
				. "Also cover what happens when delivery goes wrong - a parcel that is late, lost, damaged in transit, or returned as undeliverable because nobody was in or the address was wrong - and say what the customer should do in each case.\n"
				. "Name the carriers given on the 'Delivery carriers used' line above. If that line says none were specified, describe the service in general terms instead (e.g. \"a tracked UK courier service\") - that is a complete and accurate answer, so do NOT write a placeholder asking the owner to name a carrier, and do not omit the topic either. Confirm that tracking information is provided once an order is dispatched.\n"
				. "If shipping costs are non-refundable in some circumstances, state clearly that this does not apply where the goods are faulty, not as described, or the order was cancelled under the customer's statutory rights - do not state a blanket non-refundable shipping rule without that carve-out.\n"
				. "Do not restate return-period or refund-process details here beyond a one-line pointer to the Returns Policy (at /returns/) by name - that policy is the single source of truth for returns, to avoid the two pages describing it inconsistently.\n"
				. "If the niche brief mentions sustainability/eco-friendly framing, do not make unsubstantiated environmental claims here - keep any such mention brief and factual, or omit it if it's not something concretely described in the business details above.";
		}

		/*
		 * Terms had no per-page block at all, and it showed: every one of the
		 * placeholders published on the live terms page landed in a topic the
		 * shared prompt names but nothing here tells it how to answer. Payment
		 * methods and carriers now have fields; the model cancellation form
		 * never needed one - it is a statutory template, and the returns page
		 * already writes it out in full, so terms points at it rather than
		 * asking the owner to write one.
		 */
		if ( 'terms' === $key ) {
			$prompt .= "\n\nName the payment methods from the 'Payment methods accepted' line in the business details, as the customer-facing brands they are. Do not write \"major payment methods\" or similar filler, and do not write a placeholder for this.\n"
				. "On the model cancellation form required by the Consumer Contracts Regulations: the Returns Policy at /returns/ sets it out in full. Point the reader there in one line. Do NOT reproduce the form here, and above all do not write a placeholder asking the business to supply one - it is a fixed statutory template, not a business input, and the two pages carrying separate copies of it is how they end up disagreeing.\n"
				. "Set out how a contract is formed: that an order confirmation acknowledges receipt rather than accepting the order, and that acceptance happens on dispatch. Customers dispute this exact point and terms pages routinely leave it unsaid.\n"
				. "State the governing law and jurisdiction, and get it right for where the business is registered - England and Wales, Scotland, or Northern Ireland as applicable, taken from the registered address above. Add that a consumer keeps the right to bring proceedings in their own place of residence.\n"
				. "Do not restate the shipping, returns or privacy policies at length. Summarise each in a sentence and point to it by name and path (/shipping/, /returns/, /privacy-policy/) - terms is the map, not a second copy of the territory.";
		}

		if ( 'payment' === $key ) {
			$prompt .= "\n\nState a specific refund timeframe (within 14 days of the return being received or the cancellation being accepted, matching the Consumer Contracts Regulations) and note that the refund may take a few additional days to appear depending on the customer's card issuer or bank.\n"
				. "Rather than a vague reference to delivery being affected by \"factors beyond our control\", give 2-3 concrete examples (e.g. courier delays, extreme weather, high demand periods) so the statement reads as genuine rather than a hedge.\n"
				. "State that payments are processed through a PCI-DSS compliant payment provider and that full card details are never stored on the store's own servers.\n"
				. "Include a brief statement on accessibility - that the store aims to make checkout usable for customers using assistive technology, and invite customers to contact the business (using the contact email above) if they encounter an accessibility barrier.";
		}

		if ( 'returns' === $key ) {
			$prompt .= "\n\nSet out the FAULTY GOODS remedies as the tiered structure the Consumer Rights Act 2015 actually creates, not as a single blanket refund promise:\n"
				. "- a short-term right to reject for a full refund within 30 days of receiving the goods;\n"
				. "- after that, one repair or one replacement within a reasonable time and without significant inconvenience;\n"
				. "- and if that repair or replacement fails or is not possible, a final right to reject, or to keep the goods and claim a price reduction.\n"
				. "Most policies compress this to 'we refund faulty items', which understates what the customer is entitled to and is wrong in a way that favours the shop - state it accurately.\n"
				. "Also state that after the first six months the burden of proving a fault was present at delivery shifts to the customer, and that any commercial guarantee offered is in addition to these rights and does not replace them.\n\n"
				. "Include a MODEL CANCELLATION FORM at the end, as a plain block the customer can copy: to (business name and address), I hereby give notice that I cancel my contract of sale for the following goods, ordered on / received on, name of consumer, address of consumer, date. The Consumer Contracts Regulations require traders to make this form available, and almost no small store does - saying customers may use it or any other clear statement.\n\n"
				. "Be precise about the mechanics, because this is the policy customers argue over:\n"
				. "- Say what the return window is counted FROM (the delivery date, not the order date), in calendar days, and that it is not extended for weekends or public holidays.\n"
				. "- Say that where items in one order are delivered separately, each has its own window.\n"
				. "- Define what condition a change-of-mind return must be in, concretely - unopened in original packaging, or unused - and state that this voluntary condition does not restrict statutory cancellation or faulty-goods rights.\n"
				. "- Say WHO PAYS return postage in each case: the customer for change of mind, the business for goods that are faulty, damaged, unsafe, misdescribed, incomplete or wrongly supplied.\n"
				. "- State the refund deadline in calendar days and that refunds go to the original payment method.\n"
				. "- Cover the self-service cancellation window for an order not yet dispatched, using the cancellation period in the business details.\n";

			$prompt .= "\n\nThis policy covers TWO distinct return rights, and a compliance audit has repeatedly flagged when these get conflated - keep them clearly separate:\n"
				. "1. The UK statutory 14-day cancellation right under the Consumer Contracts Regulations (a cooling-off period counted from the day the customer receives the goods), which applies regardless of the store's own policy.\n"
				. "2. This store's own voluntary returns period (the number of days given in the business details above), which EXTENDS the statutory minimum rather than replacing or shortening it.\n"
				. "State plainly that the voluntary period is in addition to, not instead of, the statutory 14-day right, and explain in plain terms how the two interact (e.g. the statutory right always applies even if it were shorter than the store's own policy).\n"
				. "Also state that refunds (including the original standard delivery charge, where the entire order is returned) will be processed within 14 days of the business receiving the returned goods or evidence that they've been sent back, matching the Consumer Contracts Regulations - do not leave the refund timeframe unstated.\n"
				. "Present the returns address as a clearly separated block, one line each for: business/trading name, street address, city, postcode, country - not as a single flowing sentence.";
		}

		/*
		 * Appended after the per-policy instructions so it is the last thing
		 * the model reads before writing - a known-issues list buried above
		 * several paragraphs of general rules gets weighted far less.
		 */
		if ( '' !== trim( (string) $previous_audit ) ) {
			$prompt .= "\n\n" . wookiee_build_audit_feedback_block( $previous_audit );
		}

		return wookiee_maybe_override(
			'policy_' . $key,
			$prompt,
			array( 'brief' => $brief, 'previous_audit' => $previous_audit )
		);
	}

	if ( 'cookie_pref' === $key ) {
		return "Write a plain-English \"Cookie Preferences\" help page for a UK online store - this is a short customer-facing explainer, not a formal legal policy (the full legal Cookie Policy is a separate page).\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "This store's actual cookie consent mechanism, describe it accurately using these facts (do not describe any other mechanism, and do not omit it): " . wookiee_cookie_consent_mechanism_description() . "\n\n"
			. "Rules:\n"
			. "- Briefly explain each of the three standard cookie categories (Strictly Necessary, Analytics, Marketing/Advertising) in plain language and how a customer can manage each.\n"
			. "- Explain how to manage/delete cookies via common browsers (Chrome, Firefox, Safari, Edge) in general terms, without fake links.\n"
			. "- Point customers to the full Cookie Policy page by name for complete details, and give the contact email for questions.\n"
			. "- Do not invent any business fact beyond the details given above.\n"
			. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
			. "- A policy page is still the business speaking, not a template: where it is not a legal requirement to use set wording, use the business's own plain voice.\n"
			. "- Output ONLY the finished page text as plain paragraphs/short headings separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary.";
	}

	if ( 'homepage_copy' === $key ) {
		return "Write homepage marketing copy for a UK single-niche ecommerce store, to slot into an EXISTING page design - you are only rewriting text, the layout/sections themselves are fixed and already built.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "The page has these fixed sections, in this order: a hero (eyebrow tag, headline, subheadline, two buttons, a shipping stat badge), a 3-item trust bar, a best-selling-products section (kicker+title only, products are real and already listed), a categories section (kicker+title+subtitle, cards are real categories already listed), a \"how it works\" section (kicker, title, lead paragraph, 3 numbered steps each with a title+description, a button), a philosophy section (heading+paragraph), and a collections section (kicker+title).\n\n"
			. wookiee_founder_voice_block() . "\n"
			. "Provide EXACTLY these labelled sections, each on its own line as \"LABEL: value\" (including the colon), nothing before or after them, in this exact order:\n"
			. "HERO_EYEBROW: very short tag line above the headline (2-5 words)\n"
			. "HERO_HEADLINE: short, punchy hero headline (under 10 words)\n"
			. "HERO_SUBHEADLINE: two sentences under the headline - what the store sells and who it is for\n"
			. "HERO_CTA_PRIMARY: primary hero button label (2-4 words, e.g. \"Shop now\")\n"
			. "HERO_CTA_SECONDARY: secondary hero button label (2-4 words)\n"
			. "HERO_STAT_LABEL: a short phrase completing \"[shipping icon] £X \" about delivery, e.g. \"flat-rate UK shipping\" - do not include the price, just the phrase after it\n"
			. "TRUST_1_TITLE: trust-bar item 1 title (2-3 words, about shipping)\n"
			. "TRUST_2_TITLE: trust-bar item 2 title (2-3 words, about returns)\n"
			. "TRUST_2_DESC: trust-bar item 2 subtext - a full short sentence, not a fragment\n"
			. "TRUST_3_TITLE: trust-bar item 3 title (2-3 words, about payment security)\n"
			. "TRUST_3_DESC: trust-bar item 3 subtext - a full short sentence, not a fragment\n"
			. "PRODUCTS_KICKER: short kicker tag for the best-sellers section (2-4 words)\n"
			. "PRODUCTS_TITLE: title for the best-sellers section (under 8 words)\n"
			. "CATEGORIES_KICKER: short kicker tag for the categories section (2-4 words)\n"
			. "CATEGORIES_TITLE: title for the categories section (under 8 words)\n"
			. "CATEGORIES_SUBTITLE: two sentences under the categories title, saying how the range is organised and how to choose\n"
			. "HOW_IT_WORKS_KICKER: short kicker tag (2-4 words)\n"
			. "HOW_IT_WORKS_TITLE: title for the how-it-works section (under 10 words)\n"
			. "HOW_IT_WORKS_LEAD: a lead paragraph of 2-3 sentences setting up the three steps\n"
			. "HOW_IT_WORKS_STEP1_TITLE: step 1 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP1_DESC: step 1 description, two sentences - what the customer does and what happens next\n"
			. "HOW_IT_WORKS_STEP2_TITLE: step 2 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP2_DESC: step 2 description, two sentences\n"
			. "HOW_IT_WORKS_STEP3_TITLE: step 3 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP3_DESC: step 3 description, two sentences\n"
			. "HOW_IT_WORKS_CTA: button label (2-4 words)\n"
			. "COLLECTIONS_KICKER: short kicker tag for the collections section (2-4 words)\n"
			. "COLLECTIONS_TITLE: title for the collections section (under 8 words)\n"
			. "HOMEPAGE_PHILOSOPHY_HEADING: short heading for the store's values/approach section (under 8 words)\n"
			. "HOMEPAGE_PHILOSOPHY: a 150-200 word passage about the store's approach and values for this niche - why these products were chosen, what is deliberately not stocked, and what a customer can expect. On one line (no internal line breaks)\n\n"
			. "Rules: natural, human, on-brand voice for THIS niche - not generic AI-sounding filler; do not invent specific facts (materials, awards, founding year) that weren't given above and do not reference or imitate a real competitor brand; no markdown; every value on a single line (no line breaks within a value).\n"
			. "Meet the stated lengths. Sections sized for a paragraph look broken and unfinished holding one short sentence, and a page of one-liners reads as a placeholder rather than a real shop. Where a length is given, write to it with specifics about THIS niche rather than padding with adjectives.";
	}

	if ( 'about_contact' === $key ) {
		return "Write About-page and Contact-page copy for a UK single-niche ecommerce store, to slot into TWO EXISTING page designs - you are only rewriting text, the layout/sections are fixed and already built.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "The About page has: a hero (kicker, heading, one bold lead sentence, one body paragraph, two buttons), a small stat badge (kicker only - the business name/tagline is filled in automatically), a 4-item facts strip (a short note on legal registration; a fulfilment title+note; a delivery note), a second section (kicker, heading, bold lead sentence, two body paragraphs) and one small highlight card (title+description).\n"
			. "The Contact page has: a kicker, a heading, one lead sentence, and a form subtitle.\n\n"
			. wookiee_founder_voice_block() . "\n"
			. "Provide EXACTLY these labelled sections, each on its own line as \"LABEL: value\" (including the colon), nothing before or after them, in this exact order:\n"
			. "ABOUT_HERO_KICKER: short kicker tag (2-4 words)\n"
			. "ABOUT_HERO_HEADING: page heading, e.g. \"About {Business Name}\" (adapt naturally)\n"
			. "ABOUT_HERO_LEAD: one or two bold, confident sentences about what the business is and does\n"
			. "ABOUT_HERO_BODY: a substantial paragraph of 90-130 words on what customers get and why it matters\n"
			. "ABOUT_CTA_PRIMARY: primary button label (2-4 words)\n"
			. "ABOUT_CTA_SECONDARY: secondary button label (2-4 words)\n"
			. "ABOUT_STAT_KICKER: a short label for the stat badge, e.g. describing the retail model (2-5 words)\n"
			. "ABOUT_LEGAL_NOTE: a short factual note naming the real registered country/region from the business details above (e.g. \"Registered in Scotland\") - use the actual registered address given, do not guess a different one\n"
			. "ABOUT_FULFILMENT_TITLE: a short phrase naming where orders are fulfilled from, based on the real registered/returns address above (2-5 words, e.g. \"Fulfilled from Cowdenbeath\") - only go generic (\"Fulfilled in the UK\") if no address was given\n"
			. "ABOUT_FULFILMENT_NOTE: a short note on storage/packing/dispatch (under 6 words)\n"
			. "ABOUT_DELIVERY_NOTE: a short delivery-speed note based on the real typical delivery time given above (under 6 words)\n"
			. "ABOUT_SECTION2_KICKER: short kicker tag (2-4 words)\n"
			. "ABOUT_SECTION2_HEADING: a heading about the product range/approach (under 8 words)\n"
			. "ABOUT_SECTION2_LEAD: one or two bold sentences about the product range\n"
			. "ABOUT_SECTION2_BODY1: a paragraph of 90-130 words on the nature of the product range - how it is chosen and what is left out\n"
			. "ABOUT_SECTION2_BODY2: a paragraph of 90-130 words on who operates the business and what they handle day to day (order admin, delivery, support)\n"
			. "ABOUT_HIGHLIGHT_TITLE: a short highlight-card title (2-4 words)\n"
			. "ABOUT_HIGHLIGHT_DESC: a highlight-card description of two sentences\n"
			. "CONTACT_KICKER: short kicker tag (2-4 words)\n"
			. "CONTACT_HEADING: contact page heading (under 6 words)\n"
			. "CONTACT_LEAD: two welcoming sentences inviting the customer to get in touch and saying what they can ask about\n"
			. "CONTACT_FORM_SUBTITLE: a short reassurance about reply time (under 10 words) - do not invent a specific number of hours if none is implied by typical UK ecommerce practice; \"within 1-2 business days\" is a safe default phrase\n\n"
			. "Rules: natural, human, on-brand voice for THIS niche - not generic AI-sounding filler; do not invent specific business facts (founding year, headcount, awards, exact locations) that weren't given above; do not reference or imitate a real competitor brand; no markdown; every value on a single line (no line breaks within a value).";
	}

	return '';
}

/**
 * Generates a policy page from the admin's own custom instructions
 * instead of the built-in per-policy prompt above - real business
 * details are still appended and "don't invent facts" still applies
 * regardless, since that guarantee shouldn't depend on which prompt
 * path was used to get here.
 */
function wookiee_build_custom_policy_prompt( $title, $custom_instruction ) {
	$prompt = "Write a complete, ready-to-publish {$title} page for a UK online store, following these instructions from the store owner:\n\n"
		. "--- OWNER'S INSTRUCTIONS ---\n{$custom_instruction}\n--- END INSTRUCTIONS ---\n\n"
		. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
		. "Rules:\n"
		. "- Follow the owner's instructions as closely as possible without contradicting UK consumer/privacy law or inventing a business fact not given above - if something relevant is missing, use a clear \"[Business input required: X]\" placeholder instead of guessing.\n"
		. "- State the business's full legal/trading name and company registration number explicitly within the body text itself.\n"
		. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
		. "- Output ONLY the finished page text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary.";

	return wookiee_maybe_override( 'policy_custom', $prompt, array( 'title' => $title, 'custom_instruction' => $custom_instruction ) );
}

/**
 * Runs an already-generated policy draft through a compliance audit and
 * returns a plain-text report - it never edits the page. Adapted from
 * docs/policy audit new.txt: that prompt is written for US law (FTC,
 * CCPA/CPRA) which doesn't apply here, so this version swaps in the same
 * UK frameworks used by the generation prompt above, keeping the audit's
 * rigor (scored risk, itemised issues, missing-information callouts)
 * rather than reserving the US original for a future non-UK site.
 */
add_action( 'wp_ajax_wookiee_audit_policy_page', 'wookiee_audit_policy_page_handler' );
function wookiee_audit_policy_page_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );


	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid policy draft first.' ) );
	}

	$prompt = wookiee_build_policy_audit_prompt( $post->post_title, wp_strip_all_tags( $post->post_content ) );
	$report = wookiee_call_llm( $prompt, 3000 );

	if ( is_wp_error( $report ) ) {
		wp_send_json_error( array( 'message' => $report->get_error_message() ) );
	}

	wookiee_store_audit_result( $post_id, $report );

	wp_send_json_success( array( 'report' => $report ) );
}

/**
 * Persists an audit's report/score/risk as postmeta so reloading this
 * admin page shows the last known analysis instantly instead of
 * starting from a blank slate - audits only ever lived in the
 * browser's JS state before, so a page refresh silently discarded
 * every score, forcing a fresh (billable) LLM call just to see numbers
 * that were already computed a moment earlier.
 */
function wookiee_store_audit_result( $post_id, $report ) {
	update_post_meta( $post_id, '_wookiee_audit_report', $report );
	update_post_meta( $post_id, '_wookiee_audit_date', current_time( 'mysql' ) );

	if ( preg_match( '/OVERALL SCORE:\s*(\d+)/i', $report, $m ) ) {
		update_post_meta( $post_id, '_wookiee_audit_score', intval( $m[1] ) );
	} else {
		delete_post_meta( $post_id, '_wookiee_audit_score' );
	}

	if ( preg_match( '/GMC RISK:\s*(Low|Medium|High)/i', $report, $m ) ) {
		update_post_meta( $post_id, '_wookiee_audit_risk', $m[1] );
	} else {
		delete_post_meta( $post_id, '_wookiee_audit_risk' );
	}
}

/**
 * Clears a stale audit result once the page's content has actually
 * changed (a fix, a custom-instruction rewrite, or a fresh generation)
 * - the old score no longer describes what's live, so the dashboard
 * should show "not yet analysed" rather than a number that might now
 * be wrong in either direction.
 */
/**
 * The stored audit report for a page that's about to be regenerated, or ''
 * if it has never been analysed. Looked up by the piece's slug because the
 * generator works in slugs, not post IDs.
 */
function wookiee_previous_audit_for_piece( array $piece ) {
	if ( empty( $piece['slug'] ) ) {
		return '';
	}

	$existing = get_page_by_path( $piece['slug'] );
	if ( ! $existing ) {
		return '';
	}

	return (string) get_post_meta( $existing->ID, '_wookiee_audit_report', true );
}

function wookiee_clear_audit_result( $post_id ) {
	delete_post_meta( $post_id, '_wookiee_audit_report' );
	delete_post_meta( $post_id, '_wookiee_audit_score' );
	delete_post_meta( $post_id, '_wookiee_audit_risk' );
	delete_post_meta( $post_id, '_wookiee_audit_date' );
}

function wookiee_build_policy_audit_prompt( $title, $policy_text ) {
	$prompt = "Act as a senior UK e-commerce compliance reviewer: a Google Merchant Center (GMC) policy reviewer, a UK solicitor specialising in consumer protection and e-commerce law, and a professional legal copywriter. Perform a compliance audit of the following policy page - do not just proofread it.\n\n"
		. "Policy page: {$title}\n\n"
		. "--- POLICY TEXT ---\n{$policy_text}\n--- END POLICY TEXT ---\n\n"
		. "Review against:\n"
		. "- Google Merchant Center requirements: misrepresentation, missing business information, unclear refund/shipping disclosures, trustworthiness, account suspension risk.\n"
		. "- UK law: the Consumer Rights Act 2015, the Consumer Contracts Regulations, the Electronic Commerce Regulations, UK GDPR, the Data Protection Act 2018, and PECR, wherever relevant.\n"
		. "- Quality: weak, confusing, or contradictory wording; generic boilerplate; AI-sounding text; missing sections; poor formatting.\n"
		. "- The standard this store holds itself to, above the legal minimum. Treat each of these as an issue when absent from a policy where it belongs:\n"
		. "  * a plain-English summary before the formal wording, so the page is usable and not merely present;\n"
		. "  * an effective/last-updated date;\n"
		. "  * legislation named in full rather than gestured at as 'consumer law';\n"
		. "  * a statutory-rights savings clause, and no wording that reads as if the policy overrides consumer law;\n"
		. "  * durations given as calendar or working days and said which, amounts in a named currency;\n"
		. "  * concrete values where the business has them - vague commitments like 'promptly' or 'as long as necessary' commit to nothing and should be flagged;\n"
		. "  * on shipping: where orders are dispatched from, given as a named town and country - treat a hedged origin ('may dispatch from within or outside the UK', 'our supplier partners', 'fulfilment partners', 'third-party logistics') as an issue in its own right rather than as acceptable caution, since it answers the disclosure with a refusal to answer it; also carriers and their role, whether the Crown Dependencies are covered, per-order vs per-item charging;\n"
		. "  * on returns: the Consumer Rights Act tiered remedies stated correctly (30-day short-term right to reject, then repair or replacement, then final right to reject or price reduction), who pays return postage in each case, and a model cancellation form;\n"
		. "  * on privacy: named controller, actual retention periods, international transfers, and the ICO complaint route.\n"
		/*
		 * Both of these were sitting in plain sight on a page that still scored
		 * a middling pass: a returns address in a different county from the
		 * registered office, and a registered company name belonging to an
		 * entirely different trade from the goods being sold. Neither is a
		 * missing section, so nothing in the list above caught them, and both
		 * are exactly what a Merchant Center reviewer reads as a front.
		 */
		. "  * internal consistency of the business's own details: two different postal addresses used for the same purpose, a phone number or company number that does not match the others, a trading name that differs between sections. Flag the contradiction and say which values disagree - do not pick one and assume the rest are typos.\n"
		. "  * whether the named legal entity plausibly matches the goods described. A registered name from an unrelated trade selling something else entirely (an apparel company selling skincare, say) is not a wording problem, it is the misrepresentation signal a Merchant Center review acts on, and it should be raised even though every individual sentence reads correctly.\n"
		. "  * any remaining \"[Business input required: X]\" placeholder, or any equivalent bracketed gap, TBC or blank left in the published text. Treat each one as a Serious issue: it is a visible hole on a live page, and it tells a reviewer the business has not finished setting up.\n\n"
		. "Do not invent legal obligations that don't apply, and do not assume any business fact that isn't present in the text above - flag missing information instead of guessing. Note: a business-offered returns window longer than 14 days (e.g. 30 days) is normal and legal - it sits alongside, not instead of, the customer's 14-day statutory cancellation right under the Consumer Contracts Regulations. Only flag this as an issue if the text is genuinely unclear about the two coexisting, not merely because two different day-counts appear.\n\n"
		. "Output in plain text, no markdown, using exactly this structure:\n"
		. "OVERALL SCORE: a number from 1 to 10, calibrated strictly against the ISSUES FOUND list you produce below - do not default to a middle score out of habit:\n"
		. "  9-10 = zero or near-zero issues found, fully compliant\n"
		. "  7-8 = only Minor issues found, nothing Serious\n"
		. "  5-6 = one or two Serious issues, or several Minor ones\n"
		. "  3-4 = three or more Serious issues, or any actively misleading statement\n"
		. "  1-2 = missing required legal content entirely, or the policy would likely trigger a GMC suspension\n"
		. "GMC RISK: Low, Medium, or High, with a one-sentence reason\n"
		. "LEGAL RISK: a short paragraph on UK legal concerns, if any\n"
		. "ISSUES FOUND: a numbered list - what's wrong, how serious, how to fix it\n"
		. "MISSING INFORMATION: anything the business needs to supply that isn't in the text\n"
		. "RECOMMENDATION: a short closing paragraph\n\n"
		. "Be critical and specific. This is a QA report for a human to act on - do not rewrite the policy, only assess it.";

	return wookiee_maybe_override( 'policy_audit', $prompt, array( 'title' => $title, 'policy_text' => $policy_text ) );
}

/**
 * Rewrites a live policy page to resolve everything a compliance audit
 * flagged, instead of the admin manually retyping fixes an AI report
 * already itemised. Writes directly to the same real page (preserving
 * its status) - there's no draft copy in between.
 */
add_action( 'wp_ajax_wookiee_apply_audit_fixes', 'wookiee_apply_audit_fixes_handler' );
function wookiee_apply_audit_fixes_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );


	$missing_fields = wookiee_missing_critical_business_fields();
	if ( ! empty( $missing_fields ) ) {
		wp_send_json_error( array( 'message' => 'Fill in these real business details first: ' . implode( ', ', $missing_fields ) . ' - on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#business' ) ) . '" target="_blank" rel="noopener">Business Identity tab</a>.' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid policy page first.' ) );
	}

	$audit_report = isset( $_POST['audit_report'] ) ? sanitize_textarea_field( wp_unslash( $_POST['audit_report'] ) ) : '';
	if ( '' === trim( $audit_report ) ) {
		wp_send_json_error( array( 'message' => 'Run the compliance audit first.' ) );
	}

	$prompt = wookiee_build_policy_fix_prompt( $post->post_title, wp_strip_all_tags( $post->post_content ), $audit_report );
	$text   = wookiee_call_llm( $prompt, 4096 );

	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	wp_update_post( array(
		'ID'           => $post->ID,
		'post_content' => wookiee_policy_text_to_html( $text ),
	) );
	wookiee_clear_audit_result( $post->ID );

	wp_send_json_success( array( 'edit_link' => get_edit_post_link( $post->ID, 'raw' ) ) );
}

/**
 * Turns a generated policy into page HTML.
 *
 * esc_html() + wpautop() alone left two visible faults on the live pages:
 *
 *  - Markdown reached the reader literally. The prompt asks for no markdown,
 *    but models emit "**Pricing**" anyway, and escaping it means the asterisks
 *    are shown rather than rendered. Handling it here is reliable in a way
 *    that asking more firmly is not.
 *  - Nothing carried the page's own indentation. The original baked policy
 *    pages wrapped themselves in a max-width container; regenerating replaced
 *    that with bare paragraphs, so the text ran edge to edge. The CSS for
 *    .entry-content now constrains plain text children, which fixes pages
 *    already saved as well as new ones.
 *
 * Escaping still happens first, so the model cannot inject markup - only the
 * two markdown forms below are promoted back to tags, from already-escaped
 * text.
 */
function wookiee_policy_text_to_html( $text ) {
	$escaped = esc_html( trim( (string) $text ) );

	// ## Heading  ->  <h2>
	$escaped = preg_replace( '/^#{2,3}\s*(.+)$/m', '<h2>$1</h2>', $escaped );

	// **bold** -> <strong>. Non-greedy and single-line so an unmatched pair
	// cannot swallow the rest of the document.
	$escaped = preg_replace( '/\*\*(?!\s)([^*\n]+?)\*\*/', '<strong>$1</strong>', $escaped );

	// A numbered heading like "1. **Pricing**" is a heading, not a list item -
	// promote the whole line so it does not sit mid-paragraph.
	$escaped = preg_replace( '/^(\d+\.\s*)<strong>(.+?)<\/strong>\s*$/m', '<h2>$1$2</h2>', $escaped );

	return wpautop( $escaped );
}

function wookiee_build_policy_fix_prompt( $title, $current_text, $audit_report ) {
	$prompt = "You previously drafted a UK ecommerce policy page. Below is its CURRENT text, followed by a compliance audit report listing problems with it. Rewrite the complete policy to resolve every issue in the audit report while keeping everything that was already correct.\n\n"
		. "Policy page: {$title}\n\n"
		. "--- CURRENT POLICY TEXT ---\n{$current_text}\n--- END CURRENT POLICY TEXT ---\n\n"
		. "--- AUDIT REPORT ---\n{$audit_report}\n--- END AUDIT REPORT ---\n\n"
		. "Real business details (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
		. "Rules:\n"
		. "- Resolve every item under \"ISSUES FOUND\".\n"
		. "- For anything under \"MISSING INFORMATION\", either fill it in from the business details above, or if it's genuinely not available, use a clear \"[Business input required: X]\" placeholder - do not invent it.\n"
		. "- Do not claim any feature, mechanism, or business practice exists unless it's in the business details above or already accurately stated in the current text - if the audit flagged something missing that isn't something this business actually has, use a placeholder rather than inventing it.\n"
		. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
		. "- State the business's full legal/trading name and company registration number explicitly within the body text itself - UK company law expects this on formal business documents.\n"
		. "- Whatever section covers contacting the business must also explain how to escalate a complaint if it's not resolved directly - mention Citizens Advice or local Trading Standards as the UK escalation route - not just repeat the contact email.\n"
		. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
		. "- Output ONLY the finished, complete policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary, no changelog of what you fixed - just the finished, publishable page.";

	if ( false !== stripos( $title, 'privacy' ) || false !== stripos( $title, 'cookie' ) ) {
		$prompt .= "\n\nThis policy must explicitly explain the data subject's rights under UK GDPR: the right to access, rectify, erase, restrict processing of, and port their personal data, the right to object, and the right to withdraw consent at any time - and state plainly that requests can be sent to the contact email given above.";
	}

	if ( false !== stripos( $title, 'cookie' ) ) {
		$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts: " . wookiee_cookie_consent_mechanism_description() . "\n"
			. "Also state explicitly how long cookies are retained on the customer's device, commit to notifying customers of this page if the categories of cookies used change in future, and explain how a customer can exercise their data rights specifically in relation to cookie data.";
	}

	if ( false !== stripos( $title, 'return' ) ) {
		$prompt .= "\n\nThis policy covers TWO distinct return rights - keep them clearly separate: (1) the UK statutory 14-day cancellation right under the Consumer Contracts Regulations (a cooling-off period from delivery, applies regardless of store policy), and (2) this store's own voluntary returns period (from the business details above), which EXTENDS the statutory minimum rather than replacing it. State plainly that the voluntary period is in addition to, not instead of, the statutory right. Also state that refunds (including the original standard delivery charge, where the whole order is returned) are processed within 14 days of the business receiving the returned goods or evidence they've been sent back. Present the returns address as a clearly separated block, one line each for business/trading name, street address, city, postcode, country - not a flowing sentence.";
	}

	if ( false !== stripos( $title, 'terms' ) ) {
		$prompt .= "\n\nWhere this page mentions the Payment Policy or Returns Policy, state the specific page slug (e.g. \"our Payment Policy at /payment/\", \"our Returns Policy at /returns/\") so it reads as a real link. For cancelling an order, explicitly list the accepted methods to inform the business of the decision (e.g. email using the contact address above, or a clear written statement) - do not just say \"inform us\" without saying how. Point to the Shipping Policy (at /shipping/) for delivery specifics rather than repeating or inventing them here.";
	}

	if ( false !== stripos( $title, 'privacy' ) ) {
		$prompt .= "\n\nInclude a full postal address for privacy enquiries/complaints (the registered address above), not just an email. Describe HOW consent is obtained where relevant and how it can be withdrawn - do not just cite consent as a legal basis without the mechanism. Provide the actual page path to the full Cookie Policy (/cookie/). Avoid generic filler like \"we value your privacy\" with nothing concrete behind it.";
	}

	if ( false !== stripos( $title, 'shipping' ) ) {
		$prompt .= "\n\nName the delivery service/carrier used in general terms and confirm tracking is provided once dispatched. If shipping costs are ever non-refundable, state clearly this doesn't apply where goods are faulty, not as described, or the order was cancelled under statutory rights. Point to the Returns Policy (at /returns/) for return specifics rather than restating them here, to avoid the two pages describing it inconsistently.";
	}

	if ( false !== stripos( $title, 'payment' ) ) {
		$prompt .= "\n\nState a specific refund timeframe (within 14 days of the return/cancellation being accepted) and note it may take a few extra days depending on the customer's bank. Replace any vague \"factors beyond our control\" wording on delivery with 2-3 concrete examples (courier delays, extreme weather, high demand). State that payments are processed through a PCI-DSS compliant provider and full card details are never stored on the store's own servers. Include a brief accessibility statement inviting customers to report any barrier via the contact email above.";
	}

	return wookiee_maybe_override( 'policy_fix', $prompt, array( 'title' => $title, 'current_text' => $current_text, 'audit_report' => $audit_report ) );
}

/**
 * Rewrites a live policy page per a free-form instruction from the
 * admin (e.g. "make this shorter", "mention we ship internationally
 * too") - same real-business-details guardrails as every other policy
 * prompt, just driven by open intent instead of an audit report.
 */
add_action( 'wp_ajax_wookiee_apply_custom_policy_prompt', 'wookiee_apply_custom_policy_prompt_handler' );
function wookiee_apply_custom_policy_prompt_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );


	$missing_fields = wookiee_missing_critical_business_fields();
	if ( ! empty( $missing_fields ) ) {
		wp_send_json_error( array( 'message' => 'Fill in these real business details first: ' . implode( ', ', $missing_fields ) . ' - on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#business' ) ) . '" target="_blank" rel="noopener">Business Identity tab</a>.' ) );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'page' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid policy page first.' ) );
	}

	$instruction = isset( $_POST['instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instruction'] ) ) : '';
	if ( '' === trim( $instruction ) ) {
		wp_send_json_error( array( 'message' => 'Describe what you want changed first.' ) );
	}

	$prompt = "You previously drafted a UK ecommerce policy page. Below is its CURRENT text, followed by an instruction from the store owner on what to change. Apply that instruction while keeping everything else accurate and intact.\n\n"
		. "Policy page: {$post->post_title}\n\n"
		. "--- CURRENT POLICY TEXT ---\n" . wp_strip_all_tags( $post->post_content ) . "\n--- END CURRENT POLICY TEXT ---\n\n"
		. "--- OWNER'S INSTRUCTION ---\n{$instruction}\n--- END INSTRUCTION ---\n\n"
		. "Real business details (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
		. "Rules:\n"
		. "- Apply the owner's instruction as directly as possible, without contradicting UK consumer/privacy law or the real business details above.\n"
		. "- Do not invent any business fact, feature, or practice not in the details above or already accurately stated in the current text.\n"
		. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
		. "- State the business's full legal/trading name and company registration number explicitly within the body text itself.\n"
		. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
		. "- Output ONLY the finished, complete policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary.";

	if ( false !== stripos( $post->post_title, 'privacy' ) || false !== stripos( $post->post_title, 'cookie' ) ) {
		$prompt .= "\n\nThis policy must explicitly explain the data subject's rights under UK GDPR: the right to access, rectify, erase, restrict processing of, and port their personal data, the right to object, and the right to withdraw consent at any time.";
	}

	$text = wookiee_call_llm( $prompt, 4096 );
	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	wp_update_post( array(
		'ID'           => $post->ID,
		'post_content' => wookiee_policy_text_to_html( $text ),
	) );
	update_post_meta( $post->ID, '_wookiee_ai_generated', 1 );
	wookiee_clear_audit_result( $post->ID );

	wp_send_json_success( array( 'edit_link' => get_edit_post_link( $post->ID, 'raw' ) ) );
}

/**
 * Writes generated policy text straight into the REAL page for that
 * slug - creating it (published, matching wookiee_starter_pages()'s
 * own convention) if it's somehow missing, or updating its content in
 * place if it already exists, preserving whatever status it currently
 * has. These pages are plain legal/informational text with nothing
 * visual to lose, unlike About/Contact/Home, so there's no need for a
 * separate draft-then-manually-copy-across step - edit in place is the
 * whole point.
 */
function wookiee_update_real_static_page( $slug, $title, $raw_text ) {
	$content  = wookiee_policy_text_to_html( $raw_text );
	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $existing ) {
		wp_update_post( array(
			'ID'           => $existing->ID,
			'post_content' => $content,
		) );
		update_post_meta( $existing->ID, '_wookiee_ai_generated', 1 );
		wookiee_clear_audit_result( $existing->ID );
		return $existing->ID;
	}

	$post_id = wp_insert_post( array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	) );

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_wookiee_ai_generated', 1 );
		return $post_id;
	}

	return 0;
}

/**
 * Whether any of the 7 policy pages have already been through at least
 * one AI generation - purely cosmetic (labels the button "Generate" vs
 * "Regenerate"), tracked via a postmeta flag set every time
 * wookiee_update_real_static_page() writes to one of these pages.
 */
function wookiee_any_policy_page_ai_generated() {
	return wookiee_count_policy_pages_ai_generated() > 0;
}

/**
 * How many of the 7 policy pages have been through at least one AI
 * generation - used for the Setup wizard's accordion header status
 * ("4 of 7 generated") alongside the Generate/Regenerate button label.
 */
function wookiee_count_policy_pages_ai_generated() {
	$count = 0;
	foreach ( wookiee_content_generator_pieces() as $piece ) {
		$page = get_page_by_path( $piece['slug'], OBJECT, 'page' );
		if ( $page && get_post_meta( $page->ID, '_wookiee_ai_generated', true ) ) {
			$count++;
		}
	}
	return $count;
}

/**
 * "Suggest a niche" (the sparkle icon inside every niche-brief field) -
 * picks a candidate niche the admin might not have thought of, instead
 * of requiring them to already know what to type. Grounded in real UK
 * search-volume/CPC data when Google Ads is configured (the same
 * integration the Product Generator already uses), so "genuine demand"
 * is an actual claim, not LLM guessing dressed up as one; falls back to
 * a plain LLM brainstorm otherwise, same fail-safe pattern as everywhere
 * else this integration is used.
 */
/**
 * Categories that are off-limits for a suggested niche.
 *
 * Skincare, cosmetics and anything health- or medical-adjacent are excluded
 * deliberately. Two reasons, both learned the hard way:
 *
 *   - Google Merchant Center treats health and beauty claims as a high-risk
 *     area. A small store selling them attracts scrutiny it cannot easily
 *     answer, and a suspension takes the whole account, not one product.
 *   - The supplier catalogs behind these stores carry very little genuine
 *     skincare, so a skincare niche produces concepts nothing can fill - a
 *     store with no products, which is worse than a duller niche with stock.
 *
 * Generic physical goods - clothing, furniture, pet, homeware - have neither
 * problem.
 */
function wookiee_excluded_niche_terms() {
	return array(
		'skincare', 'skin care', 'cosmetic', 'makeup', 'make-up', 'beauty',
		'medical', 'medicine', 'health', 'wellness', 'supplement', 'vitamin',
		'pharmac', 'therapeutic', 'treatment', 'serum', 'cream', 'lotion',
		'dermat', 'cbd', 'essential oil', 'slimming', 'detox',
	);
}

function wookiee_niche_suggestion_seed_categories() {
	// No beauty/skincare/wellness entries - see wookiee_excluded_niche_terms().
	return array(
		'home decor', 'kitchen gadgets', 'pet supplies', 'baby products', 'fitness equipment',
		'garden tools', 'phone accessories', 'car accessories', 'office supplies',
		'camping gear', 'craft supplies', 'jewellery', 'travel accessories',
		'cleaning supplies', 'sports equipment', 'baking supplies', 'gaming accessories',
		'clothing and accessories', 'home furniture', 'stationery', 'kids toys',
		'bags and luggage', 'lighting', 'bedding and linens', 'tools and DIY',
	);
}

/** True when a suggested brief strays into the excluded territory. */
function wookiee_niche_is_excluded( $brief ) {
	$haystack = strtolower( (string) $brief );
	foreach ( wookiee_excluded_niche_terms() as $term ) {
		if ( false !== strpos( $haystack, $term ) ) {
			return true;
		}
	}
	return false;
}

function wookiee_get_recent_niche_suggestions() {
	$recent = get_option( 'wookiee_recent_niche_suggestions', array() );
	return is_array( $recent ) ? $recent : array();
}

function wookiee_remember_niche_suggestion( $brief ) {
	$recent   = wookiee_get_recent_niche_suggestions();
	$recent[] = $brief;
	update_option( 'wookiee_recent_niche_suggestions', array_slice( $recent, -10 ), false );
}

add_action( 'wp_ajax_wookiee_suggest_niche', 'wookiee_suggest_niche_handler' );
function wookiee_suggest_niche_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_suggest_niche', 'nonce' );


	$recent_suggestions = wookiee_get_recent_niche_suggestions();

	// A different shortlist each click, not just a different pick from
	// the same list, so repeated clicks genuinely explore new ground
	// rather than circling the same handful of categories.
	$seed_categories = wookiee_niche_suggestion_seed_categories();
	shuffle( $seed_categories );
	$candidates = array_slice( $seed_categories, 0, 6 );

	$grounded      = false;
	$keyword_lines = array();

	if ( wookiee_google_ads_configured() ) {
		$keyword_data = wookiee_google_ads_keyword_ideas( $candidates );
		if ( ! is_wp_error( $keyword_data ) && ! empty( $keyword_data ) ) {
			$grounded = true;
			foreach ( array_slice( $keyword_data, 0, 20 ) as $k ) {
				$cpc               = ( null !== $k['low_cpc_gbp'] && null !== $k['high_cpc_gbp'] ) ? ( '£' . $k['low_cpc_gbp'] . '-£' . $k['high_cpc_gbp'] . ' CPC' ) : 'CPC unknown';
				$keyword_lines[]   = "- \"{$k['keyword']}\" - {$k['avg_monthly_searches']} avg monthly UK searches, {$k['competition']} competition, {$cpc}";
			}
		}
	}

	$exclude_note = '';
	if ( ! empty( $recent_suggestions ) ) {
		$exclude_note = "\n\nDo not suggest any of these niches already suggested recently:\n- " . implode( "\n- ", array_slice( $recent_suggestions, -8 ) ) . "\n";
	}

	$example = 'UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats';

	// Stated in the prompt as well as filtered afterwards. The filter is what
	// guarantees it; the instruction is what stops most attempts wasting a
	// retry.
	$avoid_note = "\n\nHARD CONSTRAINT: do not suggest anything in skincare, cosmetics, beauty, personal care, health, wellness, supplements or any medical or therapeutic category, however lightly. These attract Google Merchant Center scrutiny a small store cannot answer, and the supplier catalogs behind these stores barely stock them. Suggest ordinary physical goods instead - clothing, homeware, furniture, pet, garden, hobby, kids, tools.\n";

	if ( $grounded ) {
		$prompt = "You are helping a UK dropshipping ecommerce store owner pick a promising single-niche to build a store around.\n\n"
			. "Real UK search-volume and CPC data for several candidate categories, from Google Ads Keyword Planner:\n" . implode( "\n", $keyword_lines ) . "\n"
			. $exclude_note . $avoid_note . "\n"
			. "Pick the ONE candidate niche from the data above with the best combination of genuine search demand (higher avg monthly searches) and reasonable ad cost (lower CPC/competition) for a small dropshipping store to realistically compete in.\n\n"
			. "Respond with ONLY a single, concise niche brief in the same style as this example (one sentence, plain and specific, no markdown, no preamble/commentary): \"{$example}\"";
	} else {
		$prompt = "You are helping a UK dropshipping ecommerce store owner pick a promising single-niche to build a store around - one they might not have thought of themselves, but with genuine, steady consumer demand and realistic to source/ship as a small operation (lightweight, not fragile, not heavily regulated).\n"
			. $exclude_note . $avoid_note . "\n"
			. "Suggest ONE such niche, favouring evergreen demand over fleeting trends.\n\n"
			. "Respond with ONLY a single, concise niche brief in the same style as this example (one sentence, plain and specific, no markdown, no preamble/commentary): \"{$example}\"";
	}

	/*
	 * Ask, check, ask again. A prompt constraint is a request, not a
	 * guarantee - and "no skincare" is exactly the kind of rule a model
	 * satisfies loosely ("natural beauty accessories"). The filter is what
	 * actually holds the line; the retry gives it a second chance with the
	 * refusal spelt out before giving up.
	 */
	$brief = '';
	for ( $try = 0; $try < 2; $try++ ) {
		$text = wookiee_call_llm( $try ? $prompt . "\n\nYour previous answer was in an excluded category. Suggest something entirely different - ordinary physical goods, nothing to do with skin, beauty, health or wellbeing." : $prompt, 200 );
		if ( is_wp_error( $text ) ) {
			wp_send_json_error( array( 'message' => $text->get_error_message() ) );
		}

		$candidate = trim( trim( wookiee_strip_code_fence( $text ) ), "\"' \t\n" );
		if ( '' !== $candidate && ! wookiee_niche_is_excluded( $candidate ) ) {
			$brief = $candidate;
			break;
		}
	}

	if ( '' === $brief ) {
		wp_send_json_error( array( 'message' => 'Could not come up with a suggestion outside the excluded categories - try again.' ) );
	}

	wookiee_remember_niche_suggestion( $brief );

	wp_send_json_success( array(
		'brief'    => sanitize_text_field( $brief ),
		'grounded' => $grounded,
	) );
}

/**
 * Plain save for the shared niche-brief option, used by the Setup
 * wizard's own step (which doesn't otherwise submit through the
 * Settings API form, since wookiee_niche_brief is a standalone option
 * read directly by the Product/Content Generators and CJ import, not
 * part of wookiee_settings_group).
 */
add_action( 'wp_ajax_wookiee_save_niche_brief', 'wookiee_save_niche_brief_handler' );
function wookiee_save_niche_brief_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_save_niche_brief', 'nonce' );

	$brief = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	wp_send_json_success( array( 'message' => 'Niche brief saved.' ) );
}

/**
 * Saves whichever wookiee_setting_* fields (and/or the core site title)
 * were present in this particular request - used by the Setup wizard's
 * single "Save & Continue" button per step, instead of the WordPress
 * Settings API's one-form-per-options-group flow, which would otherwise
 * force separate buttons for e.g. business fields vs. the site title
 * (different option groups) plus a distinct AJAX save for Homepage/
 * About/Contact copy. Only ever writes a key that's BOTH a real
 * registered wookiee_setting_* field AND present in this request - same
 * effective whitelist as register_setting() would enforce, just without
 * needing a real page-reloading <form> per group.
 */
add_action( 'wp_ajax_wookiee_save_setup_step', 'wookiee_save_setup_step_handler' );
function wookiee_save_setup_step_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_save_setup_step', 'nonce' );

	foreach ( wookiee_settings_fields() as $key => $field ) {
		$post_key = 'wookiee_setting_' . $key;
		if ( isset( $_POST[ $post_key ] ) ) {
			$sanitizer = wookiee_sanitizer_for( $field['type'] );
			update_option( $post_key, call_user_func( $sanitizer, wp_unslash( $_POST[ $post_key ] ) ) );
		}
	}

	if ( isset( $_POST['blogname'] ) ) {
		update_option( 'blogname', sanitize_text_field( wp_unslash( $_POST['blogname'] ) ) );
	}

	// Its own option, not a wookiee_setting_* one, because every generator
	// reads it directly - so it needs handling explicitly here.
	if ( isset( $_POST['wookiee_niche_brief'] ) ) {
		update_option( 'wookiee_niche_brief', sanitize_textarea_field( wp_unslash( $_POST['wookiee_niche_brief'] ) ) );
	}

	wp_send_json_success( array( 'message' => 'Saved.' ) );
}
