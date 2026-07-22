<?php
/**
 * Draft-only AI product content generator (v2 spec §2c, phase 2 of the
 * roadmap). Given a one-line niche brief, asks the configured LLM for a small batch of
 * uniform, plausible product ideas (title, category, short description,
 * price, and a photo brief) and creates each as a real WooCommerce product
 * in Draft status. Nothing here fabricates a product photo or auto-
 * publishes anything - a human has to add the real image and hit Publish.
 * See docs/workflow/v2/spec.md §2c for why: AI-invented listings going
 * live with zero review is a genuine consumer-protection risk the moment
 * a real customer orders something that can't actually be fulfilled as
 * described.
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOKIEE_AI_MAX_PRODUCTS', 8 );

function wookiee_render_product_generator_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_key   = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$has_woo   = class_exists( 'WooCommerce' );
	$saved_brief = get_option( 'wookiee_niche_brief', '' );
	?>
	<div class="wrap">
		<h1>Wookiee Product Generator</h1>
		<p>Describe the one niche this store sells in, and generate a small batch of uniform product ideas. Each one is created as a real WooCommerce product in <strong>Draft</strong> status — nothing appears on the live site, and nothing is orderable, until you open it, add a real product photo, check the details, and click Publish yourself.</p>

		<?php if ( ! $has_woo ) : ?>
			<div class="notice notice-error"><p>WooCommerce isn't active, so generated products have nowhere to be created. Activate WooCommerce first.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="wookiee-niche-brief">Niche brief</label></th>
				<td>
					<textarea id="wookiee-niche-brief" rows="3" class="large-text" placeholder="e.g. UK home-storage and organisation products - baskets, shelving, drawer organisers, aimed at small flats"><?php echo esc_textarea( $saved_brief ); ?></textarea>
					<p class="description">One niche for the whole site — every generated product should feel like it belongs in the same catalog.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wookiee-product-count">How many products</label></th>
				<td>
					<input type="number" id="wookiee-product-count" value="4" min="1" max="<?php echo esc_attr( WOOKIEE_AI_MAX_PRODUCTS ); ?>" class="small-text">
					<p class="description">Capped at <?php echo esc_html( WOOKIEE_AI_MAX_PRODUCTS ); ?> per batch.</p>
				</td>
			</tr>
		</table>

		<p>
			<button type="button" class="button button-primary" id="wookiee-generate-btn" <?php disabled( ! $has_woo || ! $has_key ); ?>>Generate draft products</button>
			<span id="wookiee-generate-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-generate-results"></div>
	</div>
	<script>
	( function() {
		var btn = document.getElementById( 'wookiee-generate-btn' );
		if ( ! btn ) {
			return;
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
			status.textContent = 'Asking the LLM for ' + count + ' product ideas… this can take up to a minute.';

			var data = new FormData();
			data.append( 'action', 'wookiee_generate_products' );
			data.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'wookiee_generate_products' ) ); ?>' );
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
					status.textContent = 'Created ' + res.data.products.length + ' draft product(s). Review each before publishing.';
					var html = '<table class="widefat"><thead><tr><th>Title</th><th>Category</th><th>Price</th><th>Photo needed</th><th></th></tr></thead><tbody>';
					res.data.products.forEach( function( p ) {
						html += '<tr><td>' + p.title + '</td><td>' + p.category + '</td><td>£' + p.price + '</td><td>' + p.image_brief + '</td><td><a href="' + p.edit_link + '" class="button" target="_blank" rel="noopener">Edit draft</a></td></tr>';
					} );
					html += '</tbody></table>';
					results.innerHTML = html;
				} )
				.catch( function() {
					btn.disabled = false;
					status.textContent = 'Generation failed — could not reach the server.';
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

	$ideas = wookiee_ai_generate_product_ideas( $brief, $count );
	if ( is_wp_error( $ideas ) ) {
		wp_send_json_error( array( 'message' => $ideas->get_error_message() ) );
	}

	$created = array();
	foreach ( $ideas as $idea ) {
		$product_id = wookiee_insert_ai_draft_product( $idea );
		if ( $product_id ) {
			$created[] = array(
				'title'       => esc_html( $idea['title'] ),
				'category'    => esc_html( $idea['category'] ),
				'price'       => esc_html( $idea['price_gbp'] ),
				'image_brief' => esc_html( $idea['image_brief'] ),
				'edit_link'   => get_edit_post_link( $product_id, 'raw' ),
			);
		}
	}

	wp_send_json_success( array( 'products' => $created ) );
}

/**
 * Calls the LLM and returns a plain array of product idea arrays, or a
 * WP_Error. Strict JSON-only instructions since this is parsed
 * programmatically, not shown to a human as chat output.
 */
function wookiee_ai_generate_product_ideas( $brief, $count ) {
	$prompt = "You are helping set up a single-niche UK ecommerce store. The store's niche, in the owner's own words:\n\"" . $brief . "\"\n\n"
		. "Generate exactly {$count} product ideas that would all plausibly sit in this one store's catalog - same niche, consistent quality tier and price range, no duplicate concepts. Do not reference or imitate any specific real-world brand, retailer, or existing product listing.\n\n"
		. "Respond with ONLY a raw JSON array (no markdown fences, no commentary before or after), where each element has exactly these keys:\n"
		. "- \"title\": short product title\n"
		. "- \"category\": a short category name; reuse the same category string across products that belong together so the set forms 2-4 categories total, not {$count} unique ones\n"
		. "- \"short_description\": 1-2 plain-English sentences describing the product, suitable as WooCommerce product page copy\n"
		. "- \"price_gbp\": a realistic price as a plain number string, e.g. \"24.99\"\n"
		. "- \"image_brief\": a short phrase describing exactly what product photo is needed (angle, background, what's shown) so someone can go source or shoot the real image later - this is an instruction for a photographer/sourcer, not a description of an image that already exists";

	$text = wookiee_call_llm( $prompt, 2048 );
	if ( is_wp_error( $text ) ) {
		return $text;
	}
	$text = wookiee_strip_code_fence( $text );

	$ideas = json_decode( $text, true );
	if ( ! is_array( $ideas ) || empty( $ideas ) ) {
		return new WP_Error( 'wookiee_ai_parse_error', 'Could not parse a product list from the AI response.' );
	}

	$clean = array();
	foreach ( $ideas as $idea ) {
		if ( ! is_array( $idea ) || empty( $idea['title'] ) ) {
			continue;
		}
		$clean[] = array(
			'title'            => sanitize_text_field( $idea['title'] ),
			'category'         => ! empty( $idea['category'] ) ? sanitize_text_field( $idea['category'] ) : 'General',
			'short_description' => ! empty( $idea['short_description'] ) ? sanitize_textarea_field( $idea['short_description'] ) : '',
			'price_gbp'        => ! empty( $idea['price_gbp'] ) ? preg_replace( '/[^0-9.]/', '', $idea['price_gbp'] ) : '0.00',
			'image_brief'      => ! empty( $idea['image_brief'] ) ? sanitize_textarea_field( $idea['image_brief'] ) : '',
		);
	}

	if ( empty( $clean ) ) {
		return new WP_Error( 'wookiee_ai_parse_error', 'AI response did not contain any usable product ideas.' );
	}

	return $clean;
}

/**
 * Creates one AI-suggested idea as a real WooCommerce product in Draft
 * status. Skips creating a duplicate if a product with the same title
 * already exists, so re-running generation with the same brief doesn't
 * pile up repeats.
 */
function wookiee_insert_ai_draft_product( $idea ) {
	$existing = get_page_by_title( $idea['title'], OBJECT, 'product' );
	if ( $existing ) {
		return $existing->ID;
	}

	$post_id = wp_insert_post( array(
		'post_title'   => $idea['title'],
		'post_content' => $idea['short_description'],
		'post_status'  => 'draft',
		'post_type'    => 'product',
	) );

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return 0;
	}

	wp_set_object_terms( $post_id, 'simple', 'product_type' );
	update_post_meta( $post_id, '_visibility', 'visible' );
	update_post_meta( $post_id, '_stock_status', 'instock' );
	update_post_meta( $post_id, '_regular_price', $idea['price_gbp'] );
	update_post_meta( $post_id, '_price', $idea['price_gbp'] );
	update_post_meta( $post_id, '_wookiee_ai_generated', 1 );
	update_post_meta( $post_id, '_wookiee_ai_image_brief', $idea['image_brief'] );

	$slug = sanitize_title( $idea['category'] );
	if ( ! term_exists( $slug, 'product_cat' ) ) {
		wp_insert_term( $idea['category'], 'product_cat', array( 'slug' => $slug ) );
	}
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
	}

	return $post_id;
}
