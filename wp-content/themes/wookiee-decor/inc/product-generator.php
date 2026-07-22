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
 * Still Draft-only, still requires a manual Publish - nothing here
 * fabricates data or auto-publishes. See docs/workflow/v2/spec.md §2c
 * for why: AI-invented listings going live with zero review is a real
 * consumer-protection risk the moment a customer orders something that
 * can't actually be fulfilled as described.
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOKIEE_AI_MAX_PRODUCTS', 8 );

function wookiee_render_product_generator_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_key     = '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
	$has_cj_creds = '' !== trim( (string) wookiee_get_setting( 'cj_email' ) ) && '' !== trim( (string) wookiee_get_setting( 'cj_api_key' ) );
	$has_woo     = class_exists( 'WooCommerce' );
	$saved_brief = get_option( 'wookiee_niche_brief', '' );
	?>
	<div class="wrap">
		<h1>Wookiee Product Generator</h1>
		<p>Describe the one niche this store sells in. The AI works out what concepts this catalog needs, then sources a <strong>real, fulfillable product</strong> for each one from the CJ Dropshipping catalog - real title and description (cleaned up for Google Merchant Center), real price (with your markup applied), real photo (background automatically replaced with white). Every result lands as a WooCommerce product in <strong>Draft</strong> status — nothing appears on the live site, and nothing is orderable, until you review it and click Publish yourself.</p>

		<?php if ( ! $has_woo ) : ?>
			<div class="notice notice-error"><p>WooCommerce isn't active, so sourced products have nowhere to be created. Activate WooCommerce first.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_key ) : ?>
			<div class="notice notice-warning"><p>No LLM API key set. Add one on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_cj_creds ) : ?>
			<div class="notice notice-warning"><p>Add your CJ Dropshipping email and API key on <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> first - this tool now sources real products from that catalog rather than inventing fictional ones.</p></div>
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
			status.textContent = 'Working out ' + count + ' product concepts, then searching CJ for real matches… this can take a few minutes.';

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
					var sourced = res.data.products.filter( function( p ) { return p.edit_link; } ).length;
					status.textContent = sourced + ' of ' + res.data.products.length + ' concept(s) matched to a real product. Review each before publishing.';
					var html = '<table class="widefat"><thead><tr><th>Concept</th><th>Status</th><th></th></tr></thead><tbody>';
					res.data.products.forEach( function( p ) {
						html += '<tr><td>' + p.title + '</td><td>' + p.status + '</td><td>' + ( p.edit_link ? '<a href="' + p.edit_link + '" class="button" target="_blank" rel="noopener">Edit draft</a>' : '' ) + '</td></tr>';
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
	if ( '' === trim( (string) wookiee_get_setting( 'cj_email' ) ) || '' === trim( (string) wookiee_get_setting( 'cj_api_key' ) ) ) {
		wp_send_json_error( array( 'message' => 'Add your CJ Dropshipping email and API key on the Wookiee Settings page first - this tool sources real products from that catalog.' ) );
	}

	$ideas = wookiee_ai_generate_product_ideas( $brief, $count );
	if ( is_wp_error( $ideas ) ) {
		wp_send_json_error( array( 'message' => $ideas->get_error_message() ) );
	}

	$created = array();
	foreach ( $ideas as $idea ) {
		$sourced = wookiee_source_real_product_for_idea( $idea['title'] );

		if ( is_wp_error( $sourced ) ) {
			$created[] = array(
				'title'     => esc_html( $idea['title'] ),
				'status'    => esc_html( $sourced->get_error_message() ),
				'edit_link' => '',
			);
			continue;
		}

		$created[] = array(
			'title'     => esc_html( $idea['title'] ),
			'status'    => esc_html( $sourced['note'] ? $sourced['note'] : 'Sourced from CJ Dropshipping' ),
			'edit_link' => get_edit_post_link( $sourced['post_id'], 'raw' ),
		);
	}

	wp_send_json_success( array( 'products' => $created ) );
}

/**
 * Calls the LLM and returns a plain array of {title, category} concepts,
 * or a WP_Error. Each "title" is deliberately a plain, keyword-style
 * search query (not a polished marketing title) since it's used as a CJ
 * Dropshipping search term, not shown to a customer - the real title
 * customers see comes from wookiee_ai_clean_supplier_product() during
 * import, generated from the actual matched product.
 */
function wookiee_ai_generate_product_ideas( $brief, $count ) {
	$prompt = "You are helping source real products for a single-niche UK ecommerce store from a wholesale/dropship catalog. The store's niche, in the owner's own words:\n\"" . $brief . "\"\n\n"
		. "Generate exactly {$count} distinct product concepts this store's catalog should include - same niche, consistent quality tier, no duplicate concepts, forming 2-4 categories total, not {$count} unique ones. Do not reference or imitate any specific real-world brand or existing product listing.\n\n"
		. "Each concept's title will be used as a search query against a real product catalog, so phrase it the way you'd search for that item - a few plain, common keywords (e.g. \"ceramic plant pot\"), not a stylized marketing title.\n\n"
		. "Respond with ONLY a raw JSON array (no markdown fences, no commentary before or after), where each element has exactly these keys:\n"
		. "- \"title\": the plain keyword search query for this product concept\n"
		. "- \"category\": a short category name; reuse the same category string across concepts that belong together";

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
		$clean[] = array(
			'title'    => sanitize_text_field( $idea['title'] ),
			'category' => ! empty( $idea['category'] ) ? sanitize_text_field( $idea['category'] ) : 'General',
		);
	}

	if ( empty( $clean ) ) {
		return new WP_Error( 'wookiee_ai_parse_error', 'AI response did not contain any usable product concepts.' );
	}

	return $clean;
}
