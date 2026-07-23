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
	$has_key      = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$saved_brief  = get_option( 'wookiee_niche_brief', '' );
	$already_done = wookiee_any_policy_page_ai_generated();
	$verb         = $already_done ? 'Regenerate' : 'Generate';
	?>
	<div class="wrap">
		<h1>Wookiee Content Generator</h1>
		<p>Generates UK policy pages from the store's niche and the business details already saved in Wookiee Settings. Generating edits the <strong>real, live page directly</strong> — there's no separate draft copy to review and copy across manually. Every generated page is analysed for compliance automatically, with a chance to fix or tweak each one before you move on.</p>
		<p class="description">Looking to update the <strong>Homepage</strong> or <strong>About/Contact</strong> pages instead? Those have real visual designs to preserve, so they're regenerated from the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#homepage' ) ); ?>">Homepage Copy</a> and <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#about_contact' ) ); ?>">About &amp; Contact Copy</a> tabs on Wookiee Settings instead, where you can review the new text right in place before saving.</p>

		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
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
			</table>

			<p>
				<button type="button" class="button button-primary" id="wookiee-content-generate-btn" <?php disabled( ! $has_key ); ?>><?php echo esc_html( $verb ); ?> selected pages</button>
				<span id="wookiee-content-generate-status" style="margin-left:8px;"></span>
			</p>
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
	</style>
	<script>
	( function() {
		var NONCE = <?php echo wp_json_encode( wp_create_nonce( 'wookiee_generate_content' ) ); ?>;

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

		function runAudit( card, postId, keepOpenAfter ) {
			var badge  = card.querySelector( '.wookiee-audit-card-badge' );
			var body   = card.querySelector( '.wookiee-audit-card-body' );
			var actions = card.querySelector( '.wookiee-audit-card-actions' );
			var reanalyzeBtn = card.querySelector( '.wookiee-audit-reanalyze-btn' );
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
						return;
					}
					body.textContent = res.data.report;
					card.setAttribute( 'data-report', res.data.report );
					actions.hidden = false;
					if ( reanalyzeBtn ) { reanalyzeBtn.hidden = true; }
					var b = badgeFromReport( res.data.report );
					badge.textContent = b.text;
					badge.className = 'wookiee-audit-card-badge' + ( b.level ? ' is-' + b.level : '' );
					if ( ! keepOpenAfter ) { setCardOpen( card, false ); }
				} )
				.catch( function() {
					body.textContent = 'Audit failed — could not reach the server.';
					badge.textContent = 'Failed';
				} );
		}

		function buildCard( page ) {
			var card = document.createElement( 'div' );
			card.className = 'wookiee-audit-card';
			card.setAttribute( 'data-post-id', page.post_id );
			card.innerHTML =
				'<div class="wookiee-audit-card-head">' +
					'<span class="wookiee-audit-card-title"></span>' +
					'<span class="wookiee-audit-card-badge">Waiting…</span>' +
					( page.preview_link ? '<a href="' + page.preview_link + '" target="_blank" rel="noopener" class="button wookiee-audit-preview-link">Preview &#8599;</a>' : '' ) +
					'<span class="wookiee-audit-card-chevron">&#9662;</span>' +
				'</div>' +
				'<div class="wookiee-audit-card-content" hidden>' +
					'<div class="wookiee-audit-card-body">Waiting…</div>' +
					'<div class="wookiee-audit-card-actions" hidden>' +
						'<button type="button" class="button button-primary wookiee-audit-fix-btn">Regenerate (fix these issues)</button> ' +
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
							return;
						}
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
							return;
						}
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

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					genBtn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Generation failed.';
						return;
					}
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

	$brief  = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	$pieces = isset( $_POST['pieces'] ) && is_array( $_POST['pieces'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['pieces'] ) ) : array();

	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}
	if ( empty( $pieces ) ) {
		wp_send_json_error( array( 'message' => 'Select at least one item to generate.' ) );
	}
	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the Wookiee Settings page first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	$available = wookiee_content_generator_pieces();
	$results   = array();

	foreach ( $pieces as $key ) {
		if ( ! isset( $available[ $key ] ) ) {
			continue;
		}
		$piece  = $available[ $key ];
		$prompt = wookiee_build_content_prompt( $key, $brief );
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
function wookiee_business_details_block() {
	$lines = array(
		'Business/trading name: ' . wookiee_get_setting( 'business_name' ),
		'Registered address: ' . str_replace( "\n", ', ', wookiee_get_setting( 'registered_address' ) ),
		'Company number: ' . wookiee_get_setting( 'company_number' ),
		'Contact email: ' . wookiee_get_setting( 'contact_email' ),
		'Countries served: ' . wookiee_get_setting( 'countries_served' ),
		'Typical delivery time: ' . wookiee_get_setting( 'shipping_dispatch' ),
		'Flat shipping rate: £' . wookiee_get_setting( 'shipping_rate' ),
		'Returns period: ' . wookiee_get_setting( 'returns_period_days' ) . ' days',
		'Returns address: ' . str_replace( "\n", ', ', wookiee_get_returns_address() ),
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

function wookiee_build_content_prompt( $key, $brief ) {
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
			. "- Do not copy another company's policy text.\n"
			. "- Write in plain, professional, customer-friendly English - not robotic or generic-sounding boilerplate.\n"
			. "- Include a clearly labelled section near the end on how customers can contact the business about this policy, using the contact email given above.\n"
			. "- Include a brief note that this policy may be updated from time to time and customers should check this page periodically.\n"
			. "- Where genuinely relevant, refer to the store's other policies by name (e.g. mention the Privacy Policy when discussing personal data, the Returns Policy when discussing refunds) rather than repeating their content.\n"
			. "- State the business's full legal/trading name and company registration number explicitly within the body text itself (not only implied) - UK company law expects this on formal business documents, and it must appear even if it feels repetitive with other sections.\n"
			. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
			. "- Output ONLY the finished policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary, no A/B/C-style breakdown - just the finished, publishable page.";

		if ( in_array( $key, array( 'privacy', 'cookies' ), true ) ) {
			$prompt .= "\n\nThis policy must explicitly explain the data subject's rights under UK GDPR: the right to access, rectify, erase, restrict processing of, and port their personal data, the right to object, and the right to withdraw consent at any time - and state plainly that requests to exercise these rights can be sent to the contact email given above.";
		}

		if ( 'cookies' === $key ) {
			$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts (do not describe any other mechanism, and do not omit it): " . wookiee_cookie_consent_mechanism_description();
		}

		return $prompt;
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
			. "- Output ONLY the finished page text as plain paragraphs/short headings separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary.";
	}

	if ( 'homepage_copy' === $key ) {
		return "Write homepage marketing copy for a UK single-niche ecommerce store, to slot into an EXISTING page design - you are only rewriting text, the layout/sections themselves are fixed and already built.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "The page has these fixed sections, in this order: a hero (eyebrow tag, headline, subheadline, two buttons, a shipping stat badge), a 3-item trust bar, a best-selling-products section (kicker+title only, products are real and already listed), a categories section (kicker+title+subtitle, cards are real categories already listed), a \"how it works\" section (kicker, title, lead paragraph, 3 numbered steps each with a title+description, a button), a philosophy section (heading+paragraph), and a collections section (kicker+title).\n\n"
			. "Provide EXACTLY these labelled sections, each on its own line as \"LABEL: value\" (including the colon), nothing before or after them, in this exact order:\n"
			. "HERO_EYEBROW: very short tag line above the headline (2-5 words)\n"
			. "HERO_HEADLINE: short, punchy hero headline (under 10 words)\n"
			. "HERO_SUBHEADLINE: one supporting sentence under the headline\n"
			. "HERO_CTA_PRIMARY: primary hero button label (2-4 words, e.g. \"Shop now\")\n"
			. "HERO_CTA_SECONDARY: secondary hero button label (2-4 words)\n"
			. "HERO_STAT_LABEL: a short phrase completing \"[shipping icon] £X \" about delivery, e.g. \"flat-rate UK shipping\" - do not include the price, just the phrase after it\n"
			. "TRUST_1_TITLE: trust-bar item 1 title (2-3 words, about shipping)\n"
			. "TRUST_2_TITLE: trust-bar item 2 title (2-3 words, about returns)\n"
			. "TRUST_2_DESC: trust-bar item 2 subtext (short)\n"
			. "TRUST_3_TITLE: trust-bar item 3 title (2-3 words, about payment security)\n"
			. "TRUST_3_DESC: trust-bar item 3 subtext (short)\n"
			. "PRODUCTS_KICKER: short kicker tag for the best-sellers section (2-4 words)\n"
			. "PRODUCTS_TITLE: title for the best-sellers section (under 8 words)\n"
			. "CATEGORIES_KICKER: short kicker tag for the categories section (2-4 words)\n"
			. "CATEGORIES_TITLE: title for the categories section (under 8 words)\n"
			. "CATEGORIES_SUBTITLE: one supporting sentence under the categories title\n"
			. "HOW_IT_WORKS_KICKER: short kicker tag (2-4 words)\n"
			. "HOW_IT_WORKS_TITLE: title for the how-it-works section (under 10 words)\n"
			. "HOW_IT_WORKS_LEAD: one supporting sentence/lead paragraph\n"
			. "HOW_IT_WORKS_STEP1_TITLE: step 1 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP1_DESC: step 1 one-sentence description\n"
			. "HOW_IT_WORKS_STEP2_TITLE: step 2 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP2_DESC: step 2 one-sentence description\n"
			. "HOW_IT_WORKS_STEP3_TITLE: step 3 short title (2-4 words)\n"
			. "HOW_IT_WORKS_STEP3_DESC: step 3 one-sentence description\n"
			. "HOW_IT_WORKS_CTA: button label (2-4 words)\n"
			. "COLLECTIONS_KICKER: short kicker tag for the collections section (2-4 words)\n"
			. "COLLECTIONS_TITLE: title for the collections section (under 8 words)\n"
			. "HOMEPAGE_PHILOSOPHY_HEADING: short heading for the store's values/approach section (under 8 words)\n"
			. "HOMEPAGE_PHILOSOPHY: a 80-120 word paragraph about the store's approach and values for this niche, on one line (no internal line breaks)\n\n"
			. "Rules: natural, human, on-brand voice for THIS niche - not generic AI-sounding filler; do not invent specific facts (materials, awards, founding year) that weren't given above and do not reference or imitate a real competitor brand; no markdown; every value on a single line (no line breaks within a value).";
	}

	if ( 'about_contact' === $key ) {
		return "Write About-page and Contact-page copy for a UK single-niche ecommerce store, to slot into TWO EXISTING page designs - you are only rewriting text, the layout/sections are fixed and already built.\n\n"
			. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
			. "Real business details to use (do not invent anything beyond this list):\n" . wookiee_business_details_block() . "\n\n"
			. "The About page has: a hero (kicker, heading, one bold lead sentence, one body paragraph, two buttons), a small stat badge (kicker only - the business name/tagline is filled in automatically), a 4-item facts strip (a short note on legal registration; a fulfilment title+note; a delivery note), a second section (kicker, heading, bold lead sentence, two body paragraphs) and one small highlight card (title+description).\n"
			. "The Contact page has: a kicker, a heading, one lead sentence, and a form subtitle.\n\n"
			. "Provide EXACTLY these labelled sections, each on its own line as \"LABEL: value\" (including the colon), nothing before or after them, in this exact order:\n"
			. "ABOUT_HERO_KICKER: short kicker tag (2-4 words)\n"
			. "ABOUT_HERO_HEADING: page heading, e.g. \"About {Business Name}\" (adapt naturally)\n"
			. "ABOUT_HERO_LEAD: one bold, confident sentence about what the business is and does\n"
			. "ABOUT_HERO_BODY: one paragraph (2-3 sentences) on what customers get and why it matters\n"
			. "ABOUT_CTA_PRIMARY: primary button label (2-4 words)\n"
			. "ABOUT_CTA_SECONDARY: secondary button label (2-4 words)\n"
			. "ABOUT_STAT_KICKER: a short label for the stat badge, e.g. describing the retail model (2-5 words)\n"
			. "ABOUT_LEGAL_NOTE: a short factual note naming the real registered country/region from the business details above (e.g. \"Registered in Scotland\") - use the actual registered address given, do not guess a different one\n"
			. "ABOUT_FULFILMENT_TITLE: a short phrase naming where orders are fulfilled from, based on the real registered/returns address above (2-5 words, e.g. \"Fulfilled from Cowdenbeath\") - only go generic (\"Fulfilled in the UK\") if no address was given\n"
			. "ABOUT_FULFILMENT_NOTE: a short note on storage/packing/dispatch (under 6 words)\n"
			. "ABOUT_DELIVERY_NOTE: a short delivery-speed note based on the real typical delivery time given above (under 6 words)\n"
			. "ABOUT_SECTION2_KICKER: short kicker tag (2-4 words)\n"
			. "ABOUT_SECTION2_HEADING: a heading about the product range/approach (under 8 words)\n"
			. "ABOUT_SECTION2_LEAD: one bold sentence about the product range\n"
			. "ABOUT_SECTION2_BODY1: one paragraph about the nature of the product range\n"
			. "ABOUT_SECTION2_BODY2: one paragraph about who operates the business and what they handle (order admin, delivery, support)\n"
			. "ABOUT_HIGHLIGHT_TITLE: a short highlight-card title (2-4 words)\n"
			. "ABOUT_HIGHLIGHT_DESC: a one-sentence highlight-card description\n"
			. "CONTACT_KICKER: short kicker tag (2-4 words)\n"
			. "CONTACT_HEADING: contact page heading (under 6 words)\n"
			. "CONTACT_LEAD: one welcoming sentence inviting the customer to get in touch\n"
			. "CONTACT_FORM_SUBTITLE: a short reassurance about reply time (under 10 words) - do not invent a specific number of hours if none is implied by typical UK ecommerce practice; \"within 1-2 business days\" is a safe default phrase\n\n"
			. "Rules: natural, human, on-brand voice for THIS niche - not generic AI-sounding filler; do not invent specific business facts (founding year, headcount, awards, exact locations) that weren't given above; do not reference or imitate a real competitor brand; no markdown; every value on a single line (no line breaks within a value).";
	}

	return '';
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

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ) . '" target="_blank" rel="noopener">AI &amp; Integrations tab</a> first.' ) );
	}

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

	wp_send_json_success( array( 'report' => $report ) );
}

function wookiee_build_policy_audit_prompt( $title, $policy_text ) {
	return "Act as a senior UK e-commerce compliance reviewer: a Google Merchant Center (GMC) policy reviewer, a UK solicitor specialising in consumer protection and e-commerce law, and a professional legal copywriter. Perform a compliance audit of the following policy page - do not just proofread it.\n\n"
		. "Policy page: {$title}\n\n"
		. "--- POLICY TEXT ---\n{$policy_text}\n--- END POLICY TEXT ---\n\n"
		. "Review against:\n"
		. "- Google Merchant Center requirements: misrepresentation, missing business information, unclear refund/shipping disclosures, trustworthiness, account suspension risk.\n"
		. "- UK law: the Consumer Rights Act 2015, the Consumer Contracts Regulations, the Electronic Commerce Regulations, UK GDPR, the Data Protection Act 2018, and PECR, wherever relevant.\n"
		. "- Quality: weak, confusing, or contradictory wording; generic boilerplate; AI-sounding text; missing sections; poor formatting.\n\n"
		. "Do not invent legal obligations that don't apply, and do not assume any business fact that isn't present in the text above - flag missing information instead of guessing.\n\n"
		. "Output in plain text, no markdown, using exactly this structure:\n"
		. "OVERALL SCORE: a number from 1 to 10\n"
		. "GMC RISK: Low, Medium, or High, with a one-sentence reason\n"
		. "LEGAL RISK: a short paragraph on UK legal concerns, if any\n"
		. "ISSUES FOUND: a numbered list - what's wrong, how serious, how to fix it\n"
		. "MISSING INFORMATION: anything the business needs to supply that isn't in the text\n"
		. "RECOMMENDATION: a short closing paragraph\n\n"
		. "Be critical and specific. This is a QA report for a human to act on - do not rewrite the policy, only assess it.";
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

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ) . '" target="_blank" rel="noopener">AI &amp; Integrations tab</a> first.' ) );
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
		'post_content' => wpautop( esc_html( $text ) ),
	) );

	wp_send_json_success( array( 'edit_link' => get_edit_post_link( $post->ID, 'raw' ) ) );
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
		. "- End with a short note that this policy should be reviewed by a qualified UK solicitor before being relied on, since it is not legal advice.\n"
		. "- Output ONLY the finished, complete policy text as plain paragraphs separated by a blank line, starting with a single plain-text heading line. No markdown, no HTML, no commentary, no changelog of what you fixed - just the finished, publishable page.";

	if ( false !== stripos( $title, 'privacy' ) || false !== stripos( $title, 'cookie' ) ) {
		$prompt .= "\n\nThis policy must explicitly explain the data subject's rights under UK GDPR: the right to access, rectify, erase, restrict processing of, and port their personal data, the right to object, and the right to withdraw consent at any time - and state plainly that requests can be sent to the contact email given above.";
	}

	if ( false !== stripos( $title, 'cookie' ) ) {
		$prompt .= "\n\nThis store's actual cookie consent mechanism, describe it accurately using these facts: " . wookiee_cookie_consent_mechanism_description();
	}

	return $prompt;
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

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ) . '" target="_blank" rel="noopener">AI &amp; Integrations tab</a> first.' ) );
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
		'post_content' => wpautop( esc_html( $text ) ),
	) );
	update_post_meta( $post->ID, '_wookiee_ai_generated', 1 );

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
	$content  = wpautop( esc_html( $raw_text ) );
	$existing = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $existing ) {
		wp_update_post( array(
			'ID'           => $existing->ID,
			'post_content' => $content,
		) );
		update_post_meta( $existing->ID, '_wookiee_ai_generated', 1 );
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
function wookiee_niche_suggestion_seed_categories() {
	return array(
		'home decor', 'kitchen gadgets', 'pet supplies', 'baby products', 'fitness equipment',
		'garden tools', 'phone accessories', 'car accessories', 'beauty tools', 'office supplies',
		'camping gear', 'craft supplies', 'health and wellness gadgets', 'jewellery', 'travel accessories',
		'cleaning supplies', 'sports equipment', 'baking supplies', 'gaming accessories', 'skincare tools',
	);
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

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the <a href="' . esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ) . '" target="_blank" rel="noopener">AI &amp; Integrations tab</a> first.' ) );
	}

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

	if ( $grounded ) {
		$prompt = "You are helping a UK dropshipping ecommerce store owner pick a promising single-niche to build a store around.\n\n"
			. "Real UK search-volume and CPC data for several candidate categories, from Google Ads Keyword Planner:\n" . implode( "\n", $keyword_lines ) . "\n"
			. $exclude_note . "\n"
			. "Pick the ONE candidate niche from the data above with the best combination of genuine search demand (higher avg monthly searches) and reasonable ad cost (lower CPC/competition) for a small dropshipping store to realistically compete in.\n\n"
			. "Respond with ONLY a single, concise niche brief in the same style as this example (one sentence, plain and specific, no markdown, no preamble/commentary): \"{$example}\"";
	} else {
		$prompt = "You are helping a UK dropshipping ecommerce store owner pick a promising single-niche to build a store around - one they might not have thought of themselves, but with genuine, steady consumer demand and realistic to source/ship as a small operation (lightweight, not fragile, not heavily regulated).\n"
			. $exclude_note . "\n"
			. "Suggest ONE such niche, favouring evergreen demand over fleeting trends.\n\n"
			. "Respond with ONLY a single, concise niche brief in the same style as this example (one sentence, plain and specific, no markdown, no preamble/commentary): \"{$example}\"";
	}

	$text = wookiee_call_llm( $prompt, 200 );
	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	$brief = trim( wookiee_strip_code_fence( $text ) );
	$brief = trim( $brief, "\"' \t\n" );

	if ( '' === $brief ) {
		wp_send_json_error( array( 'message' => 'Could not come up with a suggestion - try again.' ) );
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

	wp_send_json_success( array( 'message' => 'Saved.' ) );
}
