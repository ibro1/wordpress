<?php
/**
 * AI-assisted product sourcing (v2 spec §2c, phase 2 of the roadmap -
 * revised). Originally generated fictional product concepts as draft
 * WooCommerce products with no real photo, price, or supplier behind
 * them - useless as automation, since there was never a real product to
 * publish. Fixed by bridging into the real CJ Dropshipping catalog
 * (inc/supplier-cj.php): the LLM still figures out what this niche's
 * catalog needs, but each idea is now used as a search query against
 * real inventory, and the actual imported product - real title/
 * description (AI-cleaned for GMC compliance), real price (with
 * markup), real photo (with automated white-background processing) -
 * comes from wookiee_source_real_product_for_idea(). The invented idea
 * itself never becomes a product; it's only ever a search query.
 *
 * Still Draft by default - nothing here fabricates data or auto-
 * publishes on its own. See docs/workflow/v2/spec.md §2c for why:
 * AI-invented listings going live with zero review is a real consumer-
 * protection risk the moment a customer orders something that can't
 * actually be fulfilled as described. The bulk/single Publish actions
 * below are a deliberate, explicit admin action - nothing is ever
 * pre-selected, so publishing still requires a human to actually tick
 * a box (or click Publish) for each item, not a silent default.
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOKIEE_AI_MAX_PRODUCTS', 8 );

function wookiee_render_product_generator_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_key      = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$has_cj_creds = '' !== trim( (string) wookiee_get_setting( 'cj_email' ) ) && '' !== trim( (string) wookiee_get_setting( 'cj_api_key' ) );
	$has_woo      = class_exists( 'WooCommerce' );
	$has_ads      = wookiee_google_ads_configured();
	$saved_brief  = get_option( 'wookiee_niche_brief', '' );
	?>
	<div class="wrap">
		<h1>Wookiee Product Generator</h1>
		<p>Describe the one niche this store sells in. The AI works out what concepts this catalog needs<?php echo $has_ads ? ', grounded in real UK search-volume and CPC data from Google Ads' : ''; ?>, then sources a <strong>real, fulfillable product</strong> for each one from the CJ Dropshipping catalog - real title and description (cleaned up for Google Merchant Center), real price (with your markup applied), real photo (background automatically replaced with white). Every result lands as a WooCommerce product in <strong>Draft</strong> status by default — nothing appears on the live site, and nothing is orderable, until you review it and publish (below, or in the editor).</p>

		<?php if ( ! $has_woo ) : ?>
			<div class="notice notice-error"><p>WooCommerce isn't active, so sourced products have nowhere to be created. Activate WooCommerce first.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_cj_creds ) : ?>
			<div class="notice notice-warning"><p>Add your CJ Dropshipping email and API key on <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> first - this tool now sources real products from that catalog rather than inventing fictional ones.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_ads ) : ?>
			<div class="notice notice-info"><p>Optional: add Google Ads API credentials on <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> to ground concept picks in real UK search-volume/CPC data instead of AI guessing. Works without it, just less informed.</p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wookiee-niche-brief">Niche brief</label></th>
				<td>
					<textarea id="wookiee-niche-brief" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $saved_brief ); ?></textarea>
					<p class="description">One niche for the whole site — every sourced product should feel like it belongs in the same catalog.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wookiee-product-count">How many products</label></th>
				<td>
					<input type="number" id="wookiee-product-count" value="4" min="1" max="<?php echo esc_attr( WOOKIEE_AI_MAX_PRODUCTS ); ?>" class="small-text">
					<p class="description">Capped at <?php echo esc_html( WOOKIEE_AI_MAX_PRODUCTS ); ?> per batch — each one involves a real catalog search plus AI review, so keep batches modest.</p>
				</td>
			</tr>
		</table>

		<p>
			<button type="button" class="button button-primary" id="wookiee-generate-btn" <?php disabled( ! $has_woo || ! $has_key || ! $has_cj_creds ); ?>>Generate &amp; source real products</button>
			<span id="wookiee-generate-status" style="margin-left:8px;"></span>
		</p>

		<p>
			<button type="button" class="button button-primary" id="wookiee-bulk-publish-btn" disabled>Publish selected</button>
			<span id="wookiee-bulk-publish-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-generate-results"></div>
	</div>
	<script>
	( function() {
		var btn          = document.getElementById( 'wookiee-generate-btn' );
		var bulkBtn       = document.getElementById( 'wookiee-bulk-publish-btn' );
		var bulkStatus    = document.getElementById( 'wookiee-bulk-publish-status' );
		if ( ! btn ) {
			return;
		}
		var nonce = '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_products' ) ); ?>';

		function updateBulkButton() {
			var checked = document.querySelectorAll( '.wookiee-product-select:checked' ).length;
			bulkBtn.disabled = checked === 0;
			bulkBtn.textContent = checked ? 'Publish ' + checked + ' selected' : 'Publish selected';
		}

		function publishOne( postId, row ) {
			var data = new FormData();
			data.append( 'action', 'wookiee_publish_products' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_publish_products' ) ); ?>' );
			data.append( 'post_ids[]', postId );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } ).then( function( r ) { return r.json(); } );
		}

		btn.addEventListener( 'click', function() {
			var status  = document.getElementById( 'wookiee-generate-status' );
			var results = document.getElementById( 'wookiee-generate-results' );
			var brief   = document.getElementById( 'wookiee-niche-brief' ).value.trim();
			var count   = document.getElementById( 'wookiee-product-count' ).value;

			if ( ! brief ) {
				status.textContent = 'Describe the niche first.';
				return;
			}

			btn.disabled = true;
			results.innerHTML = '';
			bulkBtn.disabled = true;
			status.textContent = 'Working out ' + count + ' product concept(s), then searching CJ for real matches… this can take a few minutes.';

			var data = new FormData();
			data.append( 'action', 'wookiee_generate_products' );
			data.append( 'nonce', nonce );
			data.append( 'brief', brief );
			data.append( 'count', count );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					btn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Generation failed.';
						return;
					}
					var products = res.data.products;
					var skipped  = res.data.total - products.length;
					status.textContent = products.length + ' of ' + res.data.total + ' concept(s) sourced' + ( skipped ? ' (' + skipped + " didn't fit the niche and " + ( skipped === 1 ? 'was' : 'were' ) + ' skipped)' : '' ) + '. Review each before publishing.';

					if ( ! products.length ) {
						results.innerHTML = '';
						return;
					}

					var html = '<table class="widefat"><thead><tr><th></th><th>Concept</th><th>Est. demand</th><th>Status</th><th colspan="2"></th></tr></thead><tbody>';
					products.forEach( function( p ) {
						html += '<tr data-post-id="' + p.post_id + '"><td><input type="checkbox" class="wookiee-product-select" value="' + p.post_id + '"></td><td>' + p.title + '</td><td>' + ( p.demand || '—' ) + '</td><td class="wookiee-row-status">' + p.status + '</td><td><a href="' + p.edit_link + '" class="button" target="_blank" rel="noopener">Edit draft</a></td><td><button type="button" class="button wookiee-publish-one-btn">Publish</button></td></tr>';
					} );
					html += '</tbody></table>';
					results.innerHTML = html;

					results.querySelectorAll( '.wookiee-product-select' ).forEach( function( cb ) {
						cb.addEventListener( 'change', updateBulkButton );
					} );
					results.querySelectorAll( '.wookiee-publish-one-btn' ).forEach( function( pubBtn ) {
						pubBtn.addEventListener( 'click', function() {
							var row    = pubBtn.closest( 'tr' );
							var postId = row.getAttribute( 'data-post-id' );
							pubBtn.disabled = true;
							pubBtn.textContent = 'Publishing…';
							publishOne( postId, row ).then( function( res ) {
								if ( res.success && res.data.results[0] ) {
									row.querySelector( '.wookiee-row-status' ).textContent = res.data.results[0].status;
									pubBtn.outerHTML = '<span>&#10003;</span>';
								} else {
									pubBtn.disabled = false;
									pubBtn.textContent = 'Publish failed — retry';
								}
							} ).catch( function() {
								pubBtn.disabled = false;
								pubBtn.textContent = 'Publish failed — retry';
							} );
						} );
					} );
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
				} );
		} );

		bulkBtn.addEventListener( 'click', function() {
			var checked = Array.prototype.slice.call( document.querySelectorAll( '.wookiee-product-select:checked' ) );
			if ( ! checked.length ) {
				return;
			}
			bulkBtn.disabled = true;
			bulkStatus.textContent = 'Publishing ' + checked.length + ' product(s)…';

			var data = new FormData();
			data.append( 'action', 'wookiee_publish_products' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_publish_products' ) ); ?>' );
			checked.forEach( function( cb ) { data.append( 'post_ids[]', cb.value ); } );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( ! res.success ) {
						bulkStatus.textContent = res.data && res.data.message ? res.data.message : 'Bulk publish failed.';
						updateBulkButton();
						return;
					}
					res.data.results.forEach( function( r ) {
						var row = document.querySelector( 'tr[data-post-id="' + r.post_id + '"]' );
						if ( ! row ) { return; }
						row.querySelector( '.wookiee-row-status' ).textContent = r.status;
						var pubBtn = row.querySelector( '.wookiee-publish-one-btn' );
						if ( pubBtn ) { pubBtn.outerHTML = '<span>&#10003;</span>'; }
						row.querySelector( '.wookiee-product-select' ).disabled = true;
					} );
					bulkStatus.textContent = 'Done.';
					updateBulkButton();
				} )
				.catch( function() {
					bulkStatus.textContent = 'Bulk publish failed — could not reach the server.';
					updateBulkButton();
				} );
		} );
	} )();
	</script>
	<?php
}

add_action( 'wp_ajax_wookiee_generate_products', 'wookiee_generate_products_handler' );
function wookiee_generate_products_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_products', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
	}

	$brief = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	$count = isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 4;
	$count = max( 1, min( WOOKIEE_AI_MAX_PRODUCTS, $count ) );

	if ( '' === trim( $brief ) ) {
		wp_send_json_error( array( 'message' => 'Describe the niche first.' ) );
	}

	update_option( 'wookiee_niche_brief', $brief );

	if ( '' === trim( (string) wookiee_get_setting( 'llm_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add an LLM API key on the Wookiee Settings page first.' ) );
	}
	if ( '' === trim( (string) wookiee_get_setting( 'cj_email' ) ) || '' === trim( (string) wookiee_get_setting( 'cj_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add your CJ Dropshipping email and API key on the Wookiee Settings page first - this tool sources real products from that catalog.' ) );
	}

	$ideas = wookiee_ai_generate_product_ideas( $brief, $count );
	if ( is_wp_error( $ideas ) ) {
		wp_send_json_error( array( 'message' => $ideas->get_error_message() ) );
	}

	// The prompt asks the LLM for "exactly $count" concepts, but models
	// don't always obey an exact count - enforce it here regardless of
	// how many came back, rather than trusting the model's compliance.
	$ideas = array_slice( $ideas, 0, $count );

	// Remember every concept attempted (not just ones that sourced
	// successfully) so the next generation run - even with the same
	// generic brief - doesn't keep landing on the same top-of-mind
	// concept with nothing telling it "already covered."
	wookiee_remember_concepts( wp_list_pluck( $ideas, 'title' ) );

	$created = array();
	foreach ( $ideas as $idea ) {
		$sourced = wookiee_source_real_product_for_idea( $idea['title'] );

		// Concepts that didn't source a real product (no CJ match, or no
		// good niche fit) are intentionally left out of the results the
		// admin sees - a results table isn't the place to list what
		// wasn't found, only what's actually ready to review.
		if ( is_wp_error( $sourced ) ) {
			continue;
		}

		$demand = '';
		if ( isset( $idea['avg_monthly_searches'] ) ) {
			$demand = number_format_i18n( $idea['avg_monthly_searches'] ) . '/mo';
			if ( isset( $idea['low_cpc_gbp'], $idea['high_cpc_gbp'] ) && null !== $idea['low_cpc_gbp'] ) {
				$demand .= ' · £' . esc_html( $idea['low_cpc_gbp'] ) . '-£' . esc_html( $idea['high_cpc_gbp'] ) . ' CPC';
			}
		}

		$created[] = array(
			'post_id'   => $sourced['post_id'],
			'title'     => esc_html( $idea['title'] ),
			'demand'    => esc_html( $demand ),
			'status'    => esc_html( $sourced['note'] ? $sourced['note'] : 'Sourced from CJ Dropshipping' ),
			'edit_link' => get_edit_post_link( $sourced['post_id'], 'raw' ),
		);
	}

	wp_send_json_success( array( 'products' => $created, 'total' => count( $ideas ) ) );
}

/**
 * Rolling memory of recently-generated concept titles, so repeated
 * generation runs (especially with a short, generic brief) don't keep
 * converging on the same top-of-mind concept with no signal that it's
 * already covered. Capped and de-duplicated case-insensitively.
 */
function wookiee_get_recent_concepts() {
	$recent = get_option( 'wookiee_recent_product_concepts', array() );
	return is_array( $recent ) ? $recent : array();
}

function wookiee_remember_concepts( array $titles ) {
	$recent = wookiee_get_recent_concepts();
	foreach ( $titles as $title ) {
		$title = trim( (string) $title );
		if ( '' !== $title ) {
			$recent[] = $title;
		}
	}

	$seen    = array();
	$deduped = array();
	foreach ( array_reverse( $recent ) as $title ) {
		$key = strtolower( $title );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$deduped[]    = $title;
	}

	update_option( 'wookiee_recent_product_concepts', array_slice( array_reverse( $deduped ), -60 ), false );
}

/**
 * Calls the LLM and returns a plain array of {title, category} concepts
 * (plus real search-volume/CPC fields when Google Ads is configured),
 * or a WP_Error. Each "title" is deliberately a plain, keyword-style
 * search query (not a polished marketing title) since it's used as a CJ
 * Dropshipping search term, not shown to a customer - the real title
 * customers see comes from wookiee_ai_clean_supplier_product() during
 * import, generated from the actual matched product.
 *
 * When Google Ads is configured (inc/keyword-research.php), this grounds
 * concept selection in real UK search-volume and CPC data instead of
 * pure LLM guessing: the model picks its {$count} concepts FROM that
 * real keyword list rather than inventing them, so "would this get
 * traffic" and "what would ads cost" have actual numbers behind them.
 * Falls back to the previous pure-LLM brainstorming if Google Ads isn't
 * set up, so this remains fully optional.
 */
function wookiee_ai_generate_product_ideas( $brief, $count ) {
	$is_configured = wookiee_google_ads_configured();
	$keyword_data  = $is_configured ? wookiee_google_ads_keyword_ideas( array( $brief ) ) : new WP_Error( 'wookiee_google_ads_not_configured', '' );

	// Configured-but-failing is worth knowing about (e.g. Basic access
	// still pending, a bad customer ID) - configured-but-not-set-up
	// yet isn't, so only log the former to avoid noise.
	if ( $is_configured && is_wp_error( $keyword_data ) ) {
		error_log( 'Wookiee Product Generator: Google Ads keyword lookup failed - ' . $keyword_data->get_error_message() );
	}

	$has_keywords = ! is_wp_error( $keyword_data ) && ! empty( $keyword_data );

	// Don't let the model re-pick a concept this catalog already has -
	// otherwise a generic brief run repeatedly just keeps returning its
	// single most obvious answer for the niche every time.
	$recent_concepts = wookiee_get_recent_concepts();
	if ( $has_keywords && ! empty( $recent_concepts ) ) {
		$recent_lower = array_map( 'strtolower', $recent_concepts );
		$keyword_data = array_values( array_filter( $keyword_data, function ( $k ) use ( $recent_lower ) {
			return ! in_array( strtolower( $k['keyword'] ), $recent_lower, true );
		} ) );
		$has_keywords = ! empty( $keyword_data );
	}

	if ( $has_keywords ) {
		$top   = array_slice( $keyword_data, 0, 30 );
		$lines = array();
		foreach ( $top as $k ) {
			$cpc       = ( null !== $k['low_cpc_gbp'] && null !== $k['high_cpc_gbp'] ) ? ( '£' . $k['low_cpc_gbp'] . '-£' . $k['high_cpc_gbp'] . ' CPC' ) : 'CPC unknown';
			$lines[]   = "- \"{$k['keyword']}\" - {$k['avg_monthly_searches']} avg monthly UK searches, {$k['competition']} competition, {$cpc}";
		}

		$prompt = "You are helping source real products for a single-niche UK ecommerce store from a wholesale/dropship catalog. The store's niche, in the owner's own words:\n\"" . $brief . "\"\n\n"
			. "Real UK search-volume and cost-per-click data for this niche, from Google Ads Keyword Planner:\n" . implode( "\n", $lines ) . "\n\n"
			. "Using ONLY the keywords listed above, pick exactly {$count} of the best ones to build this store's catalog around - prioritise genuine search demand (higher avg monthly searches) balanced against reasonable ad cost (lower CPC), while keeping the set feeling like one coherent niche catalog (2-4 categories total, not {$count} unique ones). Do not invent a keyword that isn't in the list above, and do not alter the wording of the keywords you choose.\n\n"
			. "Respond with ONLY a raw JSON array containing EXACTLY {$count} element(s) (no markdown fences, no commentary before or after), where each element has exactly these keys:\n"
			. "- \"title\": one of the exact keywords from the list above\n"
			. "- \"category\": a short category name; reuse the same category string across concepts that belong together";
	} else {
		$exclude_note = '';
		if ( ! empty( $recent_concepts ) ) {
			$exclude_note = "\n\nThis store's catalog already includes these concepts - do not repeat any of them:\n- " . implode( "\n- ", array_slice( $recent_concepts, -30 ) ) . "\n";
		}

		$prompt = "You are helping source real products for a single-niche UK ecommerce store from a wholesale/dropship catalog. The store's niche, in the owner's own words:\n\"" . $brief . "\"\n"
			. $exclude_note . "\n"
			. "Generate exactly {$count} distinct product concepts this store's catalog should include - same niche, consistent quality tier, no duplicate concepts, forming 2-4 categories total, not {$count} unique ones. Do not reference or imitate any specific real-world brand or existing product listing.\n\n"
			. "Each concept's title will be used as a search query against a real product catalog, so phrase it the way you'd search for that item - a few plain, common keywords (e.g. \"ceramic plant pot\"), not a stylized marketing title.\n\n"
			. "Respond with ONLY a raw JSON array containing EXACTLY {$count} element(s) (no markdown fences, no commentary before or after), where each element has exactly these keys:\n"
			. "- \"title\": the plain keyword search query for this product concept\n"
			. "- \"category\": a short category name; reuse the same category string across concepts that belong together";
	}

	$text = wookiee_call_llm( $prompt, 1024 );
	if ( is_wp_error( $text ) ) {
		return $text;
	}
	$text = wookiee_strip_code_fence( $text );

	$ideas = json_decode( $text, true );
	if ( ! is_array( $ideas ) || empty( $ideas ) ) {
		return new WP_Error( 'wookiee_ai_parse_error', 'Could not parse a product concept list from the AI response.' );
	}

	$clean = array();
	foreach ( $ideas as $idea ) {
		if ( ! is_array( $idea ) || empty( $idea['title'] ) ) {
			continue;
		}
		$entry = array(
			'title'    => sanitize_text_field( $idea['title'] ),
			'category' => ! empty( $idea['category'] ) ? sanitize_text_field( $idea['category'] ) : 'General',
		);

		if ( $has_keywords ) {
			foreach ( $keyword_data as $k ) {
				if ( 0 === strcasecmp( $k['keyword'], $entry['title'] ) ) {
					$entry['avg_monthly_searches'] = $k['avg_monthly_searches'];
					$entry['low_cpc_gbp']          = $k['low_cpc_gbp'];
					$entry['high_cpc_gbp']         = $k['high_cpc_gbp'];
					break;
				}
			}
		}

		$clean[] = $entry;
	}

	if ( empty( $clean ) ) {
		return new WP_Error( 'wookiee_ai_parse_error', 'AI response did not contain any usable product concepts.' );
	}

	return $clean;
}
