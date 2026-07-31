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
	$has_key      = wookiee_central_api_configured() || '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$has_cj_creds = wookiee_central_api_configured() || ( '' !== trim( (string) wookiee_get_setting( 'cj_email' ) ) && '' !== trim( (string) wookiee_get_setting( 'cj_api_key' ) ) );
	$has_woo      = class_exists( 'WooCommerce' );
	$has_ads      = wookiee_google_ads_configured();
	$saved_brief  = get_option( 'wookiee_niche_brief', '' );

	// Draft products this tool has sourced before, with whatever
	// compliance analysis is currently persisted for each - lets the
	// results table rehydrate on page load instead of going blank the
	// moment you navigate away, even though the products/scores
	// themselves were never actually lost.
	$persisted_products = array();
	if ( $has_woo ) {
		$existing_ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'draft',
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => '_wookiee_cj_pid',
			'fields'         => 'ids',
		) );
		foreach ( $existing_ids as $product_id ) {
			$report               = get_post_meta( $product_id, '_wookiee_audit_report', true );
			$persisted_products[] = array(
				'post_id'      => $product_id,
				'title'        => get_the_title( $product_id ),
				'demand'       => '',
				'status'       => 'Sourced from CJ Dropshipping',
				'edit_link'    => get_edit_post_link( $product_id, 'raw' ),
				'preview_link' => get_preview_post_link( $product_id ),
				'report'       => $report ? $report : null,
				'persisted'    => true,
			);
		}
	}
	?>
	<div class="wrap">
		<h1>Wookiee Product Generator</h1>
		<?php wookiee_render_model_picker(); ?>
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
			<?php
			/*
			 * Read-only on purpose. This was an editable textarea with its own
			 * suggest button, which made it the second place the one site-wide
			 * niche could be set - and the two drifted, so products got sourced
			 * against a niche the policy pages and store design knew nothing
			 * about. The niche is decided once, in Business Identity; every
			 * later screen shows what it is and links back to it.
			 *
			 * The hidden input keeps the id the generate handler reads, so the
			 * value still travels with the request without being editable here.
			 */
			$wookiee_brief_set  = '' !== trim( (string) $saved_brief );
			$wookiee_brief_link = admin_url( 'admin.php?page=wookiee-setup#business' );
			?>
			<tr>
				<th scope="row">Niche</th>
				<td>
					<input type="hidden" id="wookiee-niche-brief" value="<?php echo esc_attr( $saved_brief ); ?>">
					<?php // Rewritten in place by the wizard when step 2 saves a niche, so step 5 does not keep claiming there isn't one. ?>
					<div id="wookiee-niche-display" data-link="<?php echo esc_url( $wookiee_brief_link ); ?>">
					<?php if ( $wookiee_brief_set ) : ?>
						<p style="margin:0;"><strong><?php echo esc_html( $saved_brief ); ?></strong></p>
						<p class="description">Set once for the whole site, so every sourced product belongs in the same catalog. <a href="<?php echo esc_url( $wookiee_brief_link ); ?>">Change it in Setup &rsaquo; Business identity</a> — changing it here as well is what lets the two drift apart.</p>
						<?php if ( wookiee_niche_is_excluded( $saved_brief ) ) : ?>
							<div class="notice notice-warning inline" style="margin:8px 0 0;"><p>This niche is in a restricted category (skincare, cosmetics, health, wellness or similar). These attract Google Merchant Center scrutiny a small store cannot answer, and the supplier catalog barely stocks them. Change the niche before sourcing products.</p></div>
						<?php endif; ?>
					<?php else : ?>
						<div class="notice notice-warning inline" style="margin:0;"><p>No niche set yet. <a href="<?php echo esc_url( $wookiee_brief_link ); ?>">Set it in Setup &rsaquo; Business identity</a> first — product sourcing has nothing to search for without it.</p></div>
					<?php endif; ?>
					</div>
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
			<?php // Nothing to type a niche into on this screen any more, so a missing one has to block the button rather than surface as an error on click. ?>
			<button type="button" class="button button-primary" id="wookiee-generate-btn" data-prereqs-ok="<?php echo ( $has_woo && $has_key && $has_cj_creds ) ? '1' : '0'; ?>" <?php disabled( ! $has_woo || ! $has_key || ! $has_cj_creds || ! $wookiee_brief_set ); ?>>Generate &amp; source real products</button>
			<span id="wookiee-generate-status" style="margin-left:8px;"></span>
		</p>

		<p>
			<button type="button" class="button button-primary" id="wookiee-bulk-publish-btn" disabled>Publish selected</button>
			<span id="wookiee-bulk-publish-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-generate-results"></div>
	</div>
	<style>
		.wookiee-audit-card-badge {
			font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
			background: #f0f0f1; color: #50575e; white-space: nowrap; cursor: pointer; display: inline-block;
		}
		.wookiee-audit-card-badge.is-low { background: #edfaef; color: #00a32a; }
		.wookiee-audit-card-badge.is-medium { background: #fcf9e8; color: #996800; }
		.wookiee-audit-card-badge.is-high { background: #fcf0f1; color: #b32d2e; }
		.wookiee-compliance-detail td { background: #f6f7f7; padding: 14px 20px; }
		.wookiee-compliance-body { white-space: pre-wrap; margin: 0 0 10px; max-height: 300px; overflow-y: auto; font-size: 13px; }
		.wookiee-compliance-chevron { cursor: pointer; display: inline-block; margin-left: 6px; color: #8a7d6d; transition: transform 0.15s; user-select: none; }
		.wookiee-compliance-chevron.is-open { transform: rotate(180deg); }
	</style>
	<script>
	( function() {
		var btn          = document.getElementById( 'wookiee-generate-btn' );
		var bulkBtn       = document.getElementById( 'wookiee-bulk-publish-btn' );
		var bulkStatus    = document.getElementById( 'wookiee-bulk-publish-status' );
		var results       = document.getElementById( 'wookiee-generate-results' );
		// Shared, not local to the click handler: the per-concept sourcing
		// loop reports progress through it too.
		var status        = document.getElementById( 'wookiee-generate-status' );
		if ( ! btn ) {
			return;
		}
		var nonce = '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_products' ) ); ?>';
		var auditNonce = '<?php echo esc_js( wp_create_nonce( 'wookiee_audit_product' ) ); ?>';
		var PERSISTED_PRODUCTS = <?php echo wp_json_encode( $persisted_products ); ?>;

		function badgeFromReport( report ) {
			var scoreMatch = report.match( /OVERALL SCORE:\s*(\d+)/i );
			var riskMatch  = report.match( /GMC RISK:\s*(Low|Medium|High)/i );
			if ( ! scoreMatch && ! riskMatch ) { return { text: '', level: '' }; }
			var parts = [];
			if ( scoreMatch ) { parts.push( 'Score ' + scoreMatch[ 1 ] + '/10' ); }
			if ( riskMatch ) { parts.push( riskMatch[ 1 ] + ' risk' ); }
			return { text: parts.join( ' · ' ), level: riskMatch ? riskMatch[ 1 ].toLowerCase() : '' };
		}

		// Runs independently per product row - not chained/queued behind
		// any other row's analysis, so a batch of several sourced
		// products all start analysing in parallel the moment each one
		// lands, instead of waiting in a queue.
		function runProductAudit( postId, badge, detailBody, reanalyzeBtn ) {
			badge.textContent = 'Analysing…';
			badge.className = 'wookiee-audit-card-badge';
			if ( reanalyzeBtn ) { reanalyzeBtn.disabled = true; }
			var data = new FormData();
			data.append( 'action', 'wookiee_audit_product' );
			data.append( 'nonce', auditNonce );
			data.append( 'post_id', postId );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( reanalyzeBtn ) { reanalyzeBtn.disabled = false; }
					if ( ! res.success ) {
						badge.textContent = 'Failed';
						badge.className = 'wookiee-audit-card-badge is-high';
						detailBody.textContent = res.data && res.data.message ? res.data.message : 'Analysis failed.';
						return;
					}
					var b = badgeFromReport( res.data.report );
					badge.textContent = b.text;
					badge.className = 'wookiee-audit-card-badge' + ( b.level ? ' is-' + b.level : '' );
					detailBody.textContent = res.data.report;
				} )
				.catch( function() {
					if ( reanalyzeBtn ) { reanalyzeBtn.disabled = false; }
					badge.textContent = 'Failed';
					badge.className = 'wookiee-audit-card-badge is-high';
					detailBody.textContent = 'Could not reach the server.';
				} );
		}

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

		// Builds the two <tr> rows (product + its collapsible compliance
		// detail) for one product - shared by a freshly-generated result
		// and a persisted one rehydrated from postmeta on page load, so
		// both render identically.
		function buildRowHtml( p ) {
			var needsAudit    = ! p.report && p.persisted;
			var hideReanalyze = ! p.report && ! needsAudit;
			var badgeText     = p.report ? '' : ( needsAudit ? 'Not yet analysed' : 'Analysing…' );
			var bodyText      = p.report ? p.report : ( needsAudit ? 'Not analysed yet - click "Run analysis" below, or reanalyse anytime.' : 'Waiting…' );
			var reanalyzeLabel = p.report ? 'Reanalyse' : 'Run analysis';
			return '<tr data-post-id="' + p.post_id + '">' +
					'<td><input type="checkbox" class="wookiee-product-select" value="' + p.post_id + '"></td>' +
					'<td>' + p.title + '</td>' +
					'<td>' + ( p.demand || '—' ) + '</td>' +
					'<td class="wookiee-row-status">' + p.status + '</td>' +
					'<td><span class="wookiee-audit-card-badge wookiee-compliance-badge">' + badgeText + '</span><span class="wookiee-compliance-chevron">&#9662;</span></td>' +
					'<td>' + ( p.preview_link ? '<a href="' + p.preview_link + '" class="button" target="_blank" rel="noopener">Preview</a>' : '' ) + '</td>' +
					'<td><a href="' + p.edit_link + '" class="button" target="_blank" rel="noopener">Edit draft</a></td>' +
					'<td><button type="button" class="button wookiee-publish-one-btn">Publish</button></td>' +
				'</tr>' +
				'<tr class="wookiee-compliance-detail" hidden><td colspan="8">' +
					'<div class="wookiee-compliance-body">' + bodyText + '</div>' +
					'<button type="button" class="button wookiee-compliance-reanalyze-btn"' + ( hideReanalyze ? ' hidden' : '' ) + '>' + reanalyzeLabel + '</button>' +
				'</td></tr>';
		}

		// Wires one product row's toggle/reanalyse behaviour and decides
		// what happens on load: a fresh (just-generated) row auto-runs
		// its first analysis immediately; a persisted row with a stored
		// report just displays it (no LLM call); a persisted row with no
		// report yet waits for the admin to click "Run analysis".
		function wireRow( row, p ) {
			var postId       = row.getAttribute( 'data-post-id' );
			var badge        = row.querySelector( '.wookiee-compliance-badge' );
			var chevron      = row.querySelector( '.wookiee-compliance-chevron' );
			var detailRow    = row.nextElementSibling;
			var detailBody   = detailRow.querySelector( '.wookiee-compliance-body' );
			var reanalyzeBtn = detailRow.querySelector( '.wookiee-compliance-reanalyze-btn' );

			function toggleDetail() {
				detailRow.hidden = ! detailRow.hidden;
				chevron.classList.toggle( 'is-open', ! detailRow.hidden );
			}
			badge.addEventListener( 'click', toggleDetail );
			chevron.addEventListener( 'click', toggleDetail );

			reanalyzeBtn.addEventListener( 'click', function() {
				runProductAudit( postId, badge, detailBody, reanalyzeBtn );
			} );

			if ( p.report ) {
				var b = badgeFromReport( p.report );
				badge.textContent = b.text;
				badge.className = 'wookiee-audit-card-badge' + ( b.level ? ' is-' + b.level : '' );
			} else if ( ! p.persisted ) {
				runProductAudit( postId, badge, detailBody, reanalyzeBtn );
			}
		}

		// Renders a full results table from a plain array of product-like
		// objects - used both for a freshly-generated batch and for
		// rehydrating already-sourced products on page load.
		// Creates the results table if it is not there yet and returns its
		// tbody. Products arrive one at a time now, so the table cannot be
		// built from a complete list up front.
		function ensureProductsTbody() {
			var table = results.querySelector( 'table.wookiee-products-table' );
			if ( ! table ) {
				var wrap = document.createElement( 'div' );
				wrap.innerHTML = '<table class="widefat wookiee-products-table"><thead><tr><th></th><th>Concept</th><th>Est. demand</th><th>Status</th><th>GMC compliance</th><th></th><th colspan="2"></th></tr></thead><tbody></tbody></table>';
				table = wrap.firstChild;
				results.appendChild( table );
			}
			return table.querySelector( 'tbody' );
		}

		// Per-row wiring. Was done per-table, which only works when every row
		// exists before anything is wired.
		function wireRowControls( row ) {
			var cb = row.querySelector( '.wookiee-product-select' );
			if ( cb ) { cb.addEventListener( 'change', updateBulkButton ); }

			var pubBtn = row.querySelector( '.wookiee-publish-one-btn' );
			if ( ! pubBtn ) { return; }
			pubBtn.addEventListener( 'click', function() {
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
		}

		function appendProductRow( p ) {
			var tbody = ensureProductsTbody();
			var tmp   = document.createElement( 'tbody' );
			tmp.innerHTML = buildRowHtml( p );

			var added = [];
			while ( tmp.firstChild ) { added.push( tbody.appendChild( tmp.firstChild ) ); }

			var row = added[0];
			wireRowControls( row );
			wireRow( row, p );
		}

		function renderProductsTable( products ) {
			results.innerHTML = '';
			products.forEach( appendProductRow );
		}

		btn.addEventListener( 'click', function() {
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
					var ideas = res.data.ideas || [];
					if ( ! ideas.length ) {
						status.textContent = 'No concepts were generated - try a more specific niche.';
						btn.disabled = false;
						return;
					}

					results.innerHTML = '';
					sourceIdeasInTurn( ideas );
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
				} );
		} );

		/**
		 * Sources the concepts one at a time, showing each as it lands.
		 *
		 * Sequential rather than parallel: each one runs a catalog search and
		 * up to three AI calls, and firing them together would multiply the
		 * load on both without arriving meaningfully sooner.
		 */
		function sourceIdeasInTurn( ideas ) {
			var done = 0, sourced = 0;
			var skipped = [];

			function report() {
				status.textContent = sourced + ' of ' + ideas.length + ' sourced'
					+ ( skipped.length ? ', ' + skipped.length + ' not found' : '' )
					+ ( done < ideas.length ? ' — working on ' + ( done + 1 ) + ' of ' + ideas.length + '…' : '. Review each before publishing.' );
			}
			report();

			var chain = ideas.reduce( function( prev, idea ) {
				return prev.then( function() {
					var d = new FormData();
					d.append( 'action', 'wookiee_source_product' );
					// Same action nonce as the concept request - the sourcing
					// endpoint verifies wookiee_generate_products too.
					d.append( 'nonce', nonce );
					d.append( 'title', idea.title );
					d.append( 'category', idea.category || '' );
					d.append( 'demand', idea.demand || '' );

					return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: d } )
						.then( function( r ) { return r.json(); } )
						.then( function( res ) {
							if ( res && res.success && res.data.sourced ) {
								sourced++;
								appendProductRow( res.data.product );
							} else if ( res && res.success ) {
								skipped.push( { title: res.data.title, reason: res.data.reason } );
							} else {
								skipped.push( { title: idea.title, reason: ( res && res.data && res.data.message ) || 'Failed.' } );
							}
						} )
						.catch( function() {
							skipped.push( { title: idea.title, reason: 'The request did not complete.' } );
						} )
						.then( function() { done++; report(); } );
				} );
			}, Promise.resolve() );

			chain.then( function() {
				btn.disabled = false;
				renderSkipped( skipped );
			} );
		}

		/*
		 * Names each concept that produced nothing, and why. "Skipped" alone
		 * tells the operator to change something without saying what, and the
		 * causes - catalog has nothing, results were rejected, API failed -
		 * each need a different response.
		 */
		function renderSkipped( skipped ) {
			if ( ! skipped.length ) { return; }

			var box = document.createElement( 'div' );
			box.style.cssText = 'margin-top:14px;padding:12px 14px;background:#fcf9e8;border:1px solid #dba617;border-radius:4px;';
			var h = document.createElement( 'p' );
			h.style.cssText = 'margin:0 0 8px;font-weight:600;';
			h.textContent = 'Not sourced (' + skipped.length + ')';
			box.appendChild( h );
			var ul = document.createElement( 'ul' );
			ul.style.cssText = 'margin:0;padding-left:18px;';
			skipped.forEach( function( item ) {
				var li = document.createElement( 'li' );
				li.style.margin = '4px 0';
				var strong = document.createElement( 'strong' );
				strong.textContent = item.title;
				li.appendChild( strong );
				li.appendChild( document.createTextNode( ' — ' + item.reason ) );
				ul.appendChild( li );
			} );
			box.appendChild( ul );
			results.appendChild( box );
		}

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

		// Rehydrate previously-sourced products (and whatever compliance
		// analysis is stored for each) on page load, so navigating away
		// and back doesn't lose sight of a batch that's still sitting
		// there in Draft - nothing here calls the LLM for rows that
		// already have a stored report.
		if ( PERSISTED_PRODUCTS.length ) {
			renderProductsTable( PERSISTED_PRODUCTS );
		}
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

	if ( ! wookiee_central_api_configured() && ( '' === trim( (string) wookiee_get_setting( 'cj_email' ) ) || '' === trim( (string) wookiee_get_setting( 'cj_api_key' ) ) ) ) {
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

	/*
	 * Return the concepts and stop. Sourcing happens one concept per request
	 * (see below).
	 *
	 * Doing all of them here meant a single HTTP request performing N catalog
	 * searches and up to 3N AI calls. At the 8-product cap that reliably
	 * exceeds Cloudflare's 100-second proxy ceiling, and the browser is told
	 * the generation failed while the products are quietly created anyway -
	 * the same fault that had to be fixed in Rebrand. Per-concept requests are
	 * each short, so no batch size can outgrow the proxy.
	 *
	 * It also means the first product appears in seconds instead of after the
	 * whole batch, and one unsourceable concept no longer delays the rest.
	 */
	// Format the demand figure here rather than in the browser - it is the
	// same number the results table used to show, and number_format_i18n()
	// respects the site's locale in a way a JS join would not.
	$payload = array();
	foreach ( $ideas as $idea ) {
		$demand = '';
		if ( isset( $idea['avg_monthly_searches'] ) ) {
			$demand = number_format_i18n( $idea['avg_monthly_searches'] ) . '/mo';
			if ( isset( $idea['low_cpc_gbp'], $idea['high_cpc_gbp'] ) && null !== $idea['low_cpc_gbp'] ) {
				$demand .= ' · £' . $idea['low_cpc_gbp'] . '-£' . $idea['high_cpc_gbp'] . ' CPC';
			}
		}

		$payload[] = array(
			'title'    => $idea['title'],
			'category' => isset( $idea['category'] ) ? $idea['category'] : '',
			'demand'   => $demand,
		);
	}

	wp_send_json_success( array( 'ideas' => $payload ) );
}

/**
 * Sources one concept. Called once per idea by the browser.
 */
add_action( 'wp_ajax_wookiee_source_product', 'wookiee_source_product_handler' );
function wookiee_source_product_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_products', 'nonce' );

	$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
	$demand   = isset( $_POST['demand'] ) ? sanitize_text_field( wp_unslash( $_POST['demand'] ) ) : '';

	if ( '' === trim( $title ) ) {
		wp_send_json_error( array( 'message' => 'No concept given.' ) );
	}

	$sourced = wookiee_source_real_product_for_idea( $title, 3, $category );

	if ( is_wp_error( $sourced ) ) {
		// Success with a reason, not an error: a concept the catalog cannot
		// fill is an ordinary outcome, and the browser needs to carry on to
		// the next one rather than treat the batch as broken.
		wp_send_json_success( array(
			'sourced' => false,
			'title'   => $title,
			'reason'  => $sourced->get_error_message(),
		) );
	}

	wp_send_json_success( array(
		'sourced' => true,
		'product' => array(
			'post_id'      => $sourced['post_id'],
			'title'        => $title,
			'demand'       => $demand,
			'status'       => $sourced['note'] ? $sourced['note'] : 'Sourced from CJ Dropshipping',
			'edit_link'    => get_edit_post_link( $sourced['post_id'], 'raw' ),
			'preview_link' => get_preview_post_link( $sourced['post_id'] ),
		),
	) );
}

/**
 * Compliance audit for a single sourced product listing - a Google
 * Merchant Center (GMC) product-data policy review, distinct from the
 * UK legal-policy audit in inc/content-generator.php (that one reviews
 * Terms/Privacy/etc pages against consumer law; this one reviews an
 * individual product's title/description/price/category against GMC's
 * product-listing requirements - misrepresentation, prohibited/
 * restricted content, missing required data). Reuses the same
 * wookiee_store_audit_result()/wookiee_clear_audit_result() postmeta
 * persistence and the same OVERALL SCORE/GMC RISK report structure, so
 * both audits share one badge-parsing/UI pattern.
 */
function wookiee_build_product_audit_prompt( $product_id ) {

	$title              = get_the_title( $product_id );
	$description        = wp_strip_all_tags( get_post_field( 'post_content', $product_id ) );
	$short_description  = wp_strip_all_tags( get_post_field( 'post_excerpt', $product_id ) );
	$price              = get_post_meta( $product_id, '_price', true );
	$categories         = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
	$has_image          = has_post_thumbnail( $product_id );
	$niche              = get_option( 'wookiee_niche_brief', '' );

	$categories_str = ( ! empty( $categories ) && ! is_wp_error( $categories ) ) ? implode( ', ', $categories ) : '(none assigned)';

	return wookiee_build_product_audit_prompt_from_data(
		$title, $short_description, $description, $price, $categories_str, $has_image, $niche
	);
}

/**
 * Pure builder - takes the listing's values rather than reading them, so
 * the prompt can be captured and overridden without a real product.
 */
function wookiee_build_product_audit_prompt_from_data( $title, $short_description, $description, $price, $categories_str, $has_image, $niche ) {
	$prompt = "Act as a Google Merchant Center (GMC) product-data policy reviewer, checking one UK ecommerce product listing before it goes live - do not just proofread it.\n\n"
		. "Store niche: \"{$niche}\"\n"
		. "Product title: {$title}\n"
		. "Short description: " . ( $short_description ? $short_description : '(none)' ) . "\n"
		. "Full description: " . ( $description ? $description : '(none)' ) . "\n"
		. "Price: £" . ( $price ? $price : '(not set)' ) . "\n"
		. "Category: {$categories_str}\n"
		. "Featured image set: " . ( $has_image ? 'Yes' : 'No - no product photo currently attached' ) . "\n\n"
		. "Review against Google Merchant Center's product data policies:\n"
		. "- Title accuracy: no keyword stuffing, no ALL CAPS, no promotional text (e.g. \"free shipping\", \"% off\", \"best price\") baked into the title itself, and the title genuinely matches what's described/offered.\n"
		. "- Description accuracy: no misleading or unsubstantiated claims (especially health, safety, or environmental claims), no fake urgency/scarcity language, consistent with the title and category, no supplier/trade jargon left over from a cross-border listing.\n"
		. "- Pricing: stated clearly; flag if the description text mentions a different price/discount than the listed price above, or omits the price entirely where it should be repeated.\n"
		. "- Prohibited/restricted content: flag anything that could fall under GMC's restricted categories (e.g. supplements/medical devices implying treatment claims, weapons, counterfeit-sounding branding) or misrepresentation policy.\n"
		. "- Category fit: does this product genuinely belong in the assigned category given the niche and title.\n"
		. "- Image: flag clearly if no featured image is set at all (a hard GMC requirement) - note that the actual image content/quality cannot be visually assessed by this text-only review, so treat that as a separate manual-check reminder rather than an assumption either way.\n\n"
		. "Do not invent facts about the product beyond what's given above - flag missing information instead of guessing.\n\n"
		. "Output in plain text, no markdown, using exactly this structure:\n"
		. "OVERALL SCORE: a number from 1 to 10, calibrated strictly against the ISSUES FOUND list you produce below - do not default to a middle score out of habit:\n"
		. "  9-10 = zero or near-zero issues, fully GMC-compliant\n"
		. "  7-8 = only Minor issues, nothing Serious\n"
		. "  5-6 = one or two Serious issues, or several Minor ones\n"
		. "  3-4 = three or more Serious issues, or an actively misleading claim\n"
		. "  1-2 = missing required data entirely, or this listing would likely be disapproved/suspended by GMC\n"
		. "GMC RISK: Low, Medium, or High, with a one-sentence reason\n"
		. "ISSUES FOUND: a numbered list - what's wrong, how serious, how to fix it\n"
		. "MISSING INFORMATION: anything needed that isn't in the data above\n"
		. "RECOMMENDATION: a short closing paragraph\n\n"
		. "Be critical and specific. This is a QA report for a human to act on - do not rewrite the listing, only assess it.";

	return wookiee_maybe_override( 'product_audit', $prompt, array(
		'title'       => $title,
		'description' => $description,
		'price'       => $price,
		'niche'       => $niche,
	) );

}

add_action( 'wp_ajax_wookiee_audit_product', 'wookiee_audit_product_handler' );
function wookiee_audit_product_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_audit_product', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	$post    = $post_id ? get_post( $post_id ) : null;
	if ( ! $post || 'product' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => 'Select a valid product first.' ) );
	}

	$prompt = wookiee_build_product_audit_prompt( $post_id );
	$report = wookiee_call_llm( $prompt, 2000 );

	if ( is_wp_error( $report ) ) {
		wp_send_json_error( array( 'message' => $report->get_error_message() ) );
	}

	wookiee_store_audit_result( $post_id, $report );

	wp_send_json_success( array( 'report' => $report ) );
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
 * Pure prompt builder for product concepts, split out from
 * wookiee_ai_generate_product_ideas() so the prompt can be captured and
 * overridden without triggering a real LLM call.
 */
function wookiee_build_product_ideas_prompt( $brief, $count, $keyword_data, $recent_concepts ) {
	$has_keywords = ! is_wp_error( $keyword_data ) && ! empty( $keyword_data );

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

	$prompt = wookiee_maybe_override(
		$has_keywords ? 'product_ideas_keywords' : 'product_ideas',
		$prompt,
		array( 'brief' => $brief, 'count' => $count )
	);

	return $prompt;
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

	$prompt = wookiee_build_product_ideas_prompt( $brief, $count, $keyword_data, $recent_concepts );

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
