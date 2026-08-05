<?php
/**
 * Re-categorise the products a store already has.
 *
 * Categories are chosen at import, one product at a time, and a store that
 * sourced its catalog before that logic was fixed is stuck with whatever it
 * got - most often every product in a single broad category, because the
 * import prompt preferred the closest existing match and a broad category
 * matches everything. Re-sourcing to fix it would throw away the titles,
 * descriptions, images and compliance scores those products already carry.
 *
 * So: retag in place, and do it for the WHOLE CATALOGUE IN ONE CALL. Judging
 * each product on its own is precisely what produced the problem - a model
 * shown one item and a list of existing categories has no way to see that it
 * is filing the twentieth thing into the same bucket. Shown all of them at
 * once, it can design a set of categories that actually divides the range.
 *
 * Two steps, never one. Suggestions are produced, displayed beside the current
 * categories, and applied only when the operator says so - the same rule the
 * product generator follows for publishing, and for the same reason: this
 * rewrites the browse structure of a live shop.
 */

defined( 'ABSPATH' ) || exit;

/** Products worth offering to retag: anything real, in any status but trash. */
function wookiee_recategorise_products() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	) );

	$products = array();
	foreach ( $ids as $id ) {
		$terms = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'names' ) );
		$products[] = array(
			'id'      => (int) $id,
			'title'   => get_the_title( $id ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $id ) ), 24, '' ),
			'current' => is_wp_error( $terms ) ? array() : $terms,
			'status'  => get_post_status( $id ),
		);
	}

	return $products;
}

/** One product per line, in the shape the prompt below expects. */
function wookiee_recategorise_product_lines( array $products ) {
	$lines = array();
	foreach ( $products as $p ) {
		$lines[] = '- id ' . $p['id'] . ': ' . $p['title']
			. ( '' !== $p['excerpt'] ? ' — ' . $p['excerpt'] : '' )
			. ( $p['current'] ? ' [currently: ' . implode( ', ', $p['current'] ) . ']' : ' [currently: uncategorised]' );
	}

	return implode( "\n", $lines );
}

/**
 * Asks for a category plan across the entire catalogue at once.
 *
 * The target count is a range tied to catalogue size rather than a fixed
 * number: three categories over eighty products is as useless as twenty over
 * eight, and both are easy failure modes for a model told only to "group
 * these".
 *
 * Takes the rendered block and the count rather than the product array, so the
 * prompt registry can capture this template by passing the literal
 * "{{products}}" - the same way every other builder here is captured. Handed
 * an array instead, capture would record a prompt with an empty product list
 * and the placeholder would never appear in the editable version.
 */
function wookiee_build_recategorise_prompt( $products_block, $count, $brief ) {
	$target = max( 2, min( 8, (int) ceil( max( (int) $count, 1 ) / 4 ) ) );

	$prompt = "You are organising the catalogue of a UK online shop into browsable categories.\n\n"
		. "What the shop sells, in the owner's words: \"{$brief}\"\n\n"
		. "Every product currently in the catalogue:\n" . $products_block . "\n\n"
		. "Assign each product a category. Rules:\n"
		. "- Name the KIND of product - \"Travel Journals\", \"Wash Bags\", \"Drinkware\" - not the shop's subject matter. A category that fits every product in the shop is not a category, it is the shop, and it gives a customer nothing to narrow down. If you see one in the current assignments above, that is the problem you are fixing.\n"
		. "- Aim for roughly {$target} categories across these {$count} products. Judge by what genuinely differs: do not split two near-identical items apart to reach a number, and do not merge unrelated things to stay under one.\n"
		. "- Every category must end up with at least one product, and no category should hold more than about half the catalogue.\n"
		. "- Reuse a current category name only where it already names a kind of product correctly. Renaming is expected here.\n"
		. "- Use plain British retail wording a shopper would recognise, in Title Case, two or three words.\n\n"
		. "Respond with ONLY a raw JSON array (no markdown fences, no commentary), each element exactly: {\"id\": <number>, \"category\": \"<name>\"}. Include every product id listed above exactly once.";

	return wookiee_maybe_override( 'recategorise', $prompt, array( 'brief' => $brief, 'products' => $products_block ) );
}

add_action( 'wp_ajax_wookiee_suggest_categories', 'wookiee_suggest_categories_handler' );
function wookiee_suggest_categories_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_recategorise', 'nonce' );

	$brief = trim( (string) get_option( 'wookiee_niche_brief', '' ) );
	if ( '' === $brief ) {
		wp_send_json_error( array( 'message' => 'Set the niche in Setup > Business identity first - the categories are derived from what this shop sells.' ) );
	}

	$products = wookiee_recategorise_products();
	if ( empty( $products ) ) {
		wp_send_json_error( array( 'message' => 'There are no products to categorise yet.' ) );
	}

	$text = wookiee_call_llm(
		wookiee_build_recategorise_prompt( wookiee_recategorise_product_lines( $products ), count( $products ), $brief ),
		2000
	);
	if ( is_wp_error( $text ) ) {
		wp_send_json_error( array( 'message' => $text->get_error_message() ) );
	}

	$parsed = json_decode( wookiee_strip_code_fence( $text ), true );
	if ( ! is_array( $parsed ) ) {
		wp_send_json_error( array( 'message' => 'The model did not return a usable plan. Try again.' ) );
	}

	/*
	 * Keyed by id and filtered against the products actually offered, so a
	 * hallucinated id cannot reach the apply step and a product the model
	 * silently dropped simply has no suggestion rather than being emptied.
	 */
	$known       = wp_list_pluck( $products, 'id' );
	$suggestions = array();
	foreach ( $parsed as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['id'], $row['category'] ) ) {
			continue;
		}
		$id   = (int) $row['id'];
		$name = sanitize_text_field( (string) $row['category'] );
		if ( in_array( $id, $known, true ) && '' !== trim( $name ) ) {
			$suggestions[ $id ] = $name;
		}
	}

	if ( empty( $suggestions ) ) {
		wp_send_json_error( array( 'message' => 'The plan came back with no usable assignments. Try again.' ) );
	}

	wp_send_json_success( array(
		'suggestions' => $suggestions,
		'missing'     => array_values( array_diff( $known, array_keys( $suggestions ) ) ),
	) );
}

add_action( 'wp_ajax_wookiee_apply_categories', 'wookiee_apply_categories_handler' );
function wookiee_apply_categories_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_recategorise', 'nonce' );

	$raw = isset( $_POST['assignments'] ) ? json_decode( wp_unslash( $_POST['assignments'] ), true ) : array();
	if ( ! is_array( $raw ) || empty( $raw ) ) {
		wp_send_json_error( array( 'message' => 'Nothing was selected to apply.' ) );
	}

	$applied = 0;
	$created = array();

	foreach ( $raw as $id => $name ) {
		$id   = (int) $id;
		$name = sanitize_text_field( (string) $name );
		if ( ! $id || '' === trim( $name ) || 'product' !== get_post_type( $id ) ) {
			continue;
		}

		$term = get_term_by( 'name', $name, 'product_cat' );
		if ( ! $term ) {
			$made = wp_insert_term( $name, 'product_cat', array( 'slug' => sanitize_title( $name ) ) );
			if ( is_wp_error( $made ) ) {
				continue;
			}
			$term_id   = (int) $made['term_id'];
			$created[] = $name;
		} else {
			$term_id = (int) $term->term_id;
		}

		// Replaces rather than appends: leaving the old broad category on the
		// product would keep it in the very collection this is moving it out
		// of, and the homepage would look unchanged.
		wp_set_object_terms( $id, array( $term_id ), 'product_cat', false );
		$applied++;
	}

	/*
	 * Categories emptied by the move are removed. A term with no products is
	 * invisible on the storefront but stays in the admin and, more to the
	 * point, keeps being offered to future imports as an existing category to
	 * reuse - which is how the catch-all kept winning in the first place.
	 */
	$removed = array();
	foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
		if ( 0 === (int) $term->count && (int) get_option( 'default_product_cat', 0 ) !== (int) $term->term_id ) {
			wp_delete_term( $term->term_id, 'product_cat' );
			$removed[] = $term->name;
		}
	}

	wp_send_json_success( array(
		'applied' => $applied,
		'created' => array_values( array_unique( $created ) ),
		'removed' => $removed,
	) );
}

function wookiee_render_recategorise_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$has_woo   = class_exists( 'WooCommerce' );
	$has_key   = wookiee_central_api_configured() || '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$brief     = trim( (string) get_option( 'wookiee_niche_brief', '' ) );
	$products  = wookiee_recategorise_products();
	?>
	<div class="wrap">
		<h1>Re-categorise products</h1>
		<p>Sorts the products you already have into categories that describe what they are, without re-sourcing them &mdash; titles, descriptions, images, prices and compliance scores are untouched. The whole catalogue is planned in one pass rather than product by product, because judging each item on its own is what lands everything in a single category.</p>

		<?php if ( ! $has_woo ) : ?>
			<div class="notice notice-error"><p>WooCommerce isn't active, so there are no products to categorise.</p></div>
		<?php elseif ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No AI access configured. Enter an activation code on <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ); ?>">Settings &rsaquo; Activation</a> first.</p></div>
		<?php elseif ( '' === $brief ) : ?>
			<div class="notice notice-warning"><p>No niche set. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-setup#business' ) ); ?>">Set it in Setup &rsaquo; Business identity</a> &mdash; the categories are derived from what this shop sells.</p></div>
		<?php elseif ( empty( $products ) ) : ?>
			<div class="notice notice-info"><p>No products yet. Source some on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-product-generator' ) ); ?>">Product Generator</a> first.</p></div>
		<?php endif; ?>

		<p>
			<button type="button" class="button button-primary" id="wookiee-recat-suggest" <?php disabled( ! $has_woo || ! $has_key || '' === $brief || empty( $products ) ); ?>>Suggest categories</button>
			<button type="button" class="button button-primary" id="wookiee-recat-apply" disabled>Apply selected</button>
			<span id="wookiee-recat-status" style="margin-left:8px;"></span>
		</p>
		<p class="description">Nothing changes until you press Apply. Applying replaces a product's categories rather than adding to them, and any category left with no products afterwards is removed &mdash; an empty one is invisible on the shop but keeps being offered to future imports.</p>

		<table class="wp-list-table widefat fixed striped" id="wookiee-recat-table">
			<thead>
				<tr>
					<td class="check-column"><input type="checkbox" id="wookiee-recat-all" checked></td>
					<th scope="col">Product</th>
					<th scope="col">Status</th>
					<th scope="col">Current category</th>
					<th scope="col">Suggested</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $products as $p ) : ?>
					<tr data-id="<?php echo esc_attr( $p['id'] ); ?>">
						<th class="check-column"><input type="checkbox" class="wookiee-recat-pick" checked></th>
						<td><a href="<?php echo esc_url( get_edit_post_link( $p['id'] ) ); ?>"><?php echo esc_html( $p['title'] ); ?></a></td>
						<td><?php echo esc_html( $p['status'] ); ?></td>
						<td><?php echo esc_html( $p['current'] ? implode( ', ', $p['current'] ) : '—' ); ?></td>
						<td class="wookiee-recat-suggested">—</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<script>
	( function() {
		var NONCE = <?php echo wp_json_encode( wp_create_nonce( 'wookiee_recategorise' ) ); ?>;
		var suggestBtn = document.getElementById( 'wookiee-recat-suggest' );
		var applyBtn   = document.getElementById( 'wookiee-recat-apply' );
		var status     = document.getElementById( 'wookiee-recat-status' );
		var table      = document.getElementById( 'wookiee-recat-table' );
		if ( ! suggestBtn || ! table ) { return; }

		var toggleAll = document.getElementById( 'wookiee-recat-all' );
		if ( toggleAll ) {
			toggleAll.addEventListener( 'change', function() {
				table.querySelectorAll( '.wookiee-recat-pick' ).forEach( function( c ) { c.checked = toggleAll.checked; } );
			} );
		}

		suggestBtn.addEventListener( 'click', function() {
			suggestBtn.disabled = true;
			applyBtn.disabled = true;
			status.textContent = 'Planning the catalogue… this reads every product at once, so give it a moment.';

			var data = new FormData();
			data.append( 'action', 'wookiee_suggest_categories' );
			data.append( 'nonce', NONCE );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					suggestBtn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Could not produce a plan.';
						return;
					}

					var changed = 0;
					table.querySelectorAll( 'tbody tr' ).forEach( function( row ) {
						var cell = row.querySelector( '.wookiee-recat-suggested' );
						var name = res.data.suggestions[ row.getAttribute( 'data-id' ) ];
						if ( ! name ) {
							// No suggestion is not the same as "clear this
							// product's category" - leave it visibly untouched.
							cell.textContent = 'no change';
							row.querySelector( '.wookiee-recat-pick' ).checked = false;
							return;
						}
						cell.textContent = name;
						cell.dataset.category = name;
						changed++;
					} );

					applyBtn.disabled = changed === 0;
					status.textContent = changed + ' suggestion(s). Review, untick anything you disagree with, then Apply.';
				} )
				.catch( function() {
					suggestBtn.disabled = false;
					status.textContent = 'Failed — could not reach the server.';
				} );
		} );

		applyBtn.addEventListener( 'click', function() {
			var assignments = {};
			table.querySelectorAll( 'tbody tr' ).forEach( function( row ) {
				var cell = row.querySelector( '.wookiee-recat-suggested' );
				if ( row.querySelector( '.wookiee-recat-pick' ).checked && cell.dataset.category ) {
					assignments[ row.getAttribute( 'data-id' ) ] = cell.dataset.category;
				}
			} );

			if ( ! Object.keys( assignments ).length ) {
				status.textContent = 'Nothing ticked that has a suggestion.';
				return;
			}

			applyBtn.disabled = true;
			status.textContent = 'Applying…';

			var data = new FormData();
			data.append( 'action', 'wookiee_apply_categories' );
			data.append( 'nonce', NONCE );
			data.append( 'assignments', JSON.stringify( assignments ) );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( ! res.success ) {
						applyBtn.disabled = false;
						status.textContent = res.data && res.data.message ? res.data.message : 'Could not apply.';
						return;
					}
					var msg = res.data.applied + ' product(s) recategorised';
					if ( res.data.created.length ) { msg += '; created ' + res.data.created.join( ', ' ); }
					if ( res.data.removed.length ) { msg += '; removed empty ' + res.data.removed.join( ', ' ); }
					status.textContent = msg + '. Reloading…';
					window.location.reload();
				} )
				.catch( function() {
					applyBtn.disabled = false;
					status.textContent = 'Failed — could not reach the server.';
				} );
		} );
	}() );
	</script>
	<?php
}
