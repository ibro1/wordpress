<?php
/**
 * CJ Dropshipping supplier integration (v2 spec §2c phase 4 - the "make
 * the catalog actually fulfillable" piece). Two halves:
 *
 * 1. Catalog search/import (this file, admin-triggered): search CJ's real
 *    product catalog, review results, import one as a WooCommerce product
 *    in Draft status - same "human reviews before it goes live" rule as
 *    the AI product generator, except here the description/images/price
 *    are real supplier data, not AI-invented.
 * 2. Order fulfillment push: when a real customer's order reaches
 *    "Processing", any line items sourced from CJ are pushed to CJ as a
 *    fulfillment order automatically.
 *
 * IMPORTANT: built against CJ Dropshipping's documented Open API v2
 * request/response shapes, but not yet exercised against a live account -
 * there were no API credentials available to test with while writing
 * this. Treat the exact field names in the CJ responses (wookiee_cj_*
 * functions below) as needing a real-credential smoke test before relying
 * on it for actual orders.
 */

defined( 'ABSPATH' ) || exit;

define( 'WOOKIEE_CJ_BASE', 'https://developers.cjdropshipping.com/api2.0/v1' );
define( 'WOOKIEE_CJ_MAX_BULK_IMPORT', 10 );

/**
 * Returns a valid CJ access token, authenticating or refreshing as
 * needed. Tokens are cached in options so every catalog search/import
 * doesn't re-authenticate.
 */
function wookiee_cj_get_access_token() {
	$token  = get_option( 'wookiee_cj_access_token' );
	$expiry = get_option( 'wookiee_cj_access_token_expiry' );
	if ( $token && $expiry && strtotime( $expiry ) > time() + 300 ) {
		return $token;
	}

	$refresh_token  = get_option( 'wookiee_cj_refresh_token' );
	$refresh_expiry = get_option( 'wookiee_cj_refresh_token_expiry' );

	if ( $refresh_token && $refresh_expiry && strtotime( $refresh_expiry ) > time() + 300 ) {
		$result = wookiee_cj_auth_request( '/authentication/refreshAccessToken', array( 'refreshToken' => $refresh_token ) );
	} else {
		$email   = wookiee_get_setting( 'cj_email' );
		$api_key = wookiee_get_setting( 'cj_api_key' );
		if ( '' === trim( (string) $email ) || '' === trim( (string) $api_key ) ) {
			return new WP_Error( 'wookiee_cj_no_creds', 'Add your CJ Dropshipping email and API key on the Wookiee Settings page first.' );
		}
		$result = wookiee_cj_auth_request( '/authentication/getAccessToken', array(
			'email'    => $email,
			'password' => $api_key,
		) );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$data = isset( $result['data'] ) && is_array( $result['data'] ) ? $result['data'] : array();
	if ( empty( $data['accessToken'] ) ) {
		$msg = isset( $result['message'] ) ? $result['message'] : 'CJ Dropshipping authentication failed - check the email/API key.';
		return new WP_Error( 'wookiee_cj_auth_failed', $msg );
	}

	update_option( 'wookiee_cj_access_token', $data['accessToken'] );
	update_option( 'wookiee_cj_access_token_expiry', isset( $data['accessTokenExpiryDate'] ) ? $data['accessTokenExpiryDate'] : gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) );
	if ( ! empty( $data['refreshToken'] ) ) {
		update_option( 'wookiee_cj_refresh_token', $data['refreshToken'] );
		update_option( 'wookiee_cj_refresh_token_expiry', isset( $data['refreshTokenExpiryDate'] ) ? $data['refreshTokenExpiryDate'] : gmdate( 'Y-m-d H:i:s', time() + 14 * DAY_IN_SECONDS ) );
	}

	return $data['accessToken'];
}

function wookiee_cj_auth_request( $path, $body ) {
	$response = wp_remote_post( WOOKIEE_CJ_BASE . $path, array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( $body ),
		'timeout' => 30,
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'wookiee_cj_bad_response', 'CJ Dropshipping returned an unreadable response.' );
	}

	return $data;
}

/**
 * Generic authenticated CJ request. On a 401 the cached token is cleared
 * so the next call re-authenticates rather than looping on a dead token.
 */
function wookiee_cj_request( $method, $path, $body = null ) {
	$token = wookiee_cj_get_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$args = array(
		'method'  => $method,
		'headers' => array(
			'CJ-Access-Token' => $token,
			'Content-Type'    => 'application/json',
		),
		'timeout' => 30,
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}

	$response = wp_remote_request( WOOKIEE_CJ_BASE . $path, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 401 === $code ) {
		delete_option( 'wookiee_cj_access_token' );
		return new WP_Error( 'wookiee_cj_unauthorized', 'CJ Dropshipping rejected the access token. Try again.' );
	}
	if ( 200 !== $code ) {
		$msg = isset( $data['message'] ) ? $data['message'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_cj_error', 'CJ Dropshipping error: ' . $msg );
	}

	return is_array( $data ) ? $data : array();
}

function wookiee_cj_search_products( $keyword, $page = 1 ) {
	$result = wookiee_cj_request( 'GET', '/product/list?pageNum=' . intval( $page ) . '&pageSize=20&productName=' . rawurlencode( $keyword ) );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$list  = isset( $result['data']['list'] ) && is_array( $result['data']['list'] ) ? $result['data']['list'] : array();
	$clean = array();
	foreach ( $list as $item ) {
		$clean[] = array(
			'pid'      => isset( $item['pid'] ) ? $item['pid'] : '',
			'name'     => ! empty( $item['productNameEn'] ) ? $item['productNameEn'] : ( isset( $item['productName'] ) ? $item['productName'] : '' ),
			'image'    => isset( $item['productImage'] ) ? $item['productImage'] : '',
			'price'    => isset( $item['sellPrice'] ) ? $item['sellPrice'] : '',
			'category' => isset( $item['categoryName'] ) ? $item['categoryName'] : '',
		);
	}
	return $clean;
}

/**
 * Bridges the AI Product Generator into this real supplier catalog: an
 * AI-invented product idea (a title/concept the LLM thinks this niche
 * needs) is used as a CJ search query, and the best real, fulfillable
 * match is imported through the normal pipeline (AI-cleaned GMC-
 * compliant title/description, markup pricing, white-background
 * featured image) - the invented idea never itself becomes a product;
 * it's only ever a search query pointed at real inventory.
 *
 * Tries up to $max_attempts search results before giving up, since the
 * first hit isn't always a good fit (wookiee_cj_import_product()'s own
 * AI fit-check, run with auto-skip on, will reject it and this moves on
 * to the next result). Bounded to control LLM cost/time per idea - each
 * attempt runs its own AI cleanup call.
 */
function wookiee_source_real_product_for_idea( $query, $max_attempts = 3 ) {
	$results = wookiee_cj_search_products( $query );
	if ( is_wp_error( $results ) ) {
		return $results;
	}
	if ( empty( $results ) ) {
		return new WP_Error( 'wookiee_no_cj_match', 'No matching product found on CJ Dropshipping for "' . $query . '".' );
	}

	$attempts = 0;
	foreach ( $results as $result ) {
		if ( $attempts >= $max_attempts ) {
			break;
		}
		$attempts++;

		$imported = wookiee_cj_import_product( $result['pid'], true );
		if ( ! is_wp_error( $imported ) ) {
			return $imported;
		}
	}

	return new WP_Error( 'wookiee_no_suitable_match', 'Found results on CJ for "' . $query . '" but none were a good fit for the niche.' );
}

/**
 * Applies the configured markup to a CJ supplier cost price. Previously
 * CJ's raw supplier price was used as the live selling price with zero
 * margin - a real gap, not a rounding nicety. The original cost is kept
 * as postmeta (_wookiee_cj_cost_price) alongside the marked-up price.
 */
function wookiee_apply_product_markup( $cost_price ) {
	$markup_percent = (float) wookiee_get_setting( 'product_markup_percent' );
	$marked_up      = (float) $cost_price * ( 1 + ( $markup_percent / 100 ) );
	return number_format( $marked_up, 2, '.', '' );
}

/**
 * Pulls whatever real weight/dimension/material fields CJ actually
 * provided into a plain-text block the AI is only allowed to quote from
 * (never invent numbers for). Field names are a best-effort match
 * against CJ's typical Open API v2 product/variant schema - NOT yet
 * confirmed against a live response (same caveat as the rest of this
 * integration); if none of these happen to match, the block comes back
 * empty and the description prompt is instructed to mark specs as not
 * provided rather than silently guessing.
 */
function wookiee_extract_supplier_specs( $product, $variant ) {
	$candidates = array(
		'Weight'  => array( 'variantWeight', 'productWeight' ),
		'Length'  => array( 'variantLength' ),
		'Width'   => array( 'variantWidth' ),
		'Height'  => array( 'variantHeight' ),
		'Material' => array( 'materialNameEn', 'materialName', 'material' ),
	);

	$lines = array();
	foreach ( $candidates as $label => $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $variant[ $key ] ) ) {
				$lines[] = $label . ': ' . $variant[ $key ];
				continue 2;
			}
			if ( ! empty( $product[ $key ] ) ) {
				$lines[] = $label . ': ' . $product[ $key ];
				continue 2;
			}
		}
	}

	return $lines ? implode( "\n", $lines ) : '';
}

/**
 * Runs a supplier's raw title/description through the LLM before import:
 * this is the GMC-compliance piece. Raw CJ listings are written by and
 * for cross-border sellers - keyword-stuffed titles ("Cross-border
 * Aluminum Material Serpentine Night Light..."), trade jargon a UK
 * customer has no reason to see, and category assignments that
 * sometimes don't even match the product (a leg massager filed under
 * "Home Office Storage"). Google Merchant Center requires accurate,
 * clear, non-keyword-stuffed titles - importing CJ's raw text verbatim
 * risks exactly the misrepresentation issues GMC audits flag.
 *
 * Also produces a genuinely detailed product description (overview,
 * features, a real spec sheet, how-to-use/care guidance) instead of the
 * 1-3 sentence blurb this used to be capped at - flagged as not reading
 * like a real product page. Specs are only ever quoted from
 * $specs_text (real supplier data); anything not present there gets an
 * honest "[Not specified by supplier]" placeholder rather than an
 * invented number - same principle as the policy-generation prompts
 * never inventing business facts.
 *
 * This does NOT invent product facts - it only rewrites presentation of
 * the same real product CJ described, and separately judges whether the
 * product actually belongs in this store's one niche at all (CJ's own
 * keyword search returns plenty of noise, as the screenshot that
 * prompted this showed). Degrades gracefully: if no LLM key is set, or
 * niche brief is missing, callers fall back to the raw CJ data.
 */
function wookiee_ai_clean_supplier_product( $raw_title, $raw_description, $raw_category, $specs_text = '' ) {
	$brief = get_option( 'wookiee_niche_brief', '' );
	if ( '' === trim( $brief ) ) {
		return new WP_Error( 'wookiee_ai_no_brief', 'Set a niche brief on the Content Generator or Product Generator page first.' );
	}

	$existing_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'names' ) );
	$existing_list  = ( ! is_wp_error( $existing_terms ) && ! empty( $existing_terms ) ) ? implode( ', ', $existing_terms ) : 'none yet';
	$specs_block    = '' !== trim( $specs_text ) ? $specs_text : 'None provided by the supplier.';

	$prompt = "Act as a UK ecommerce copywriter preparing a supplier-sourced product for Google Merchant Center and a UK storefront.\n\n"
		. "Store niche: \"{$brief}\"\n\n"
		. "Supplier's raw product data (do not invent anything beyond this - only clean up the presentation of this same real product):\n"
		. "Title: {$raw_title}\n"
		. "Description: {$raw_description}\n"
		. "Supplier category: {$raw_category}\n"
		. "Known specs from the supplier (the ONLY specification values you may state - do not invent any others):\n{$specs_block}\n\n"
		. "Existing store categories: {$existing_list}\n\n"
		. "Respond with exactly these six labelled sections, each label on its own line, nothing else:\n"
		. "FIT: yes or no - does this product genuinely belong in a store with the niche described above?\n"
		. "REASON: one short sentence explaining the FIT answer\n"
		. "CLEAN_TITLE: a clear, accurate Google Merchant Center-compliant product title on one line - no keyword stuffing, no supplier/trade jargon (e.g. \"cross-border\"), no ALL CAPS, no promotional symbols\n"
		. "CATEGORY: one line - prefer an existing store category from the list above if a good match exists, otherwise a short new category name\n"
		. "SHORT_DESCRIPTION: one line, 1-2 sentences - a hook description for the cart/catalog view\n"
		. "LONG_DESCRIPTION: the full product page description, written as several plain-text paragraphs separated by blank lines, in this order:\n"
		. "  1. An overview paragraph - what it is, who it's for, the core benefit, in this niche's voice.\n"
		. "  2. A \"Key features\" paragraph or list of short lines (one per line, starting with \"- \").\n"
		. "  3. A \"Specifications\" list (one \"- Label: value\" line per spec) using ONLY the known specs given above - for any specification a real product page would normally list but wasn't provided (e.g. exact dimensions, weight, material), write \"- [Spec name]: Not specified by supplier\" instead of guessing a number.\n"
		. "  4. A short \"How to use / install\" paragraph with generic, accurate guidance appropriate to this type of product (e.g. general mounting/assembly/care advice) - do not state specific measurements, hardware, or steps that weren't given.\n"
		. "  5. A brief closing care/maintenance sentence if relevant to this product type.\n"
		. "Do not invent features, materials, exact measurements, or claims beyond what the supplier's data and the known specs above provide.";

	$text = wookiee_call_llm( $prompt, 1400 );
	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$fields = wookiee_parse_labelled_sections( $text, array(
		'FIT'               => 'fit',
		'REASON'            => 'reason',
		'CLEAN_TITLE'       => 'clean_title',
		'CATEGORY'          => 'category',
		'SHORT_DESCRIPTION' => 'short_description',
		'LONG_DESCRIPTION'  => 'long_description',
	) );
	$fields['fit'] = strtolower( trim( $fields['fit'] ) );

	return $fields;
}

/**
 * Finds an already-imported product for this exact CJ product ID, using
 * the _wookiee_cj_pid postmeta set on every CJ import - deterministic
 * regardless of what title the AI cleanup step generates, unlike a
 * title-based lookup. Checks 'any' post_status so a product already
 * published (no longer Draft) still counts as "already imported".
 */
function wookiee_find_existing_cj_product( $pid ) {
	$existing = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'meta_key'       => '_wookiee_cj_pid',
		'meta_value'     => $pid,
		'fields'         => 'ids',
	) );
	return ! empty( $existing ) ? (int) $existing[0] : 0;
}

/**
 * Imports one CJ product (by pid) as a WooCommerce Draft product using
 * the first variant's price/SKU. Skips creating a duplicate if this
 * exact CJ product has already been imported (checked by pid, before
 * the AI cleanup runs) or if a product with the AI-cleaned title
 * already exists as a secondary safety net.
 *
 * Runs the raw supplier data through wookiee_ai_clean_supplier_product()
 * first. When $auto_skip_low_fit is true (bulk import), a "no" FIT
 * answer skips the product entirely rather than cluttering the draft
 * list with catalog noise. For a single explicit "Import as draft"
 * click, the admin already chose this exact item, so it still imports -
 * just with the AI's fit judgement attached as an advisory note.
 */
function wookiee_cj_import_product( $pid, $auto_skip_low_fit = false ) {
	$detail = wookiee_cj_request( 'GET', '/product/query?pid=' . rawurlencode( $pid ) );
	if ( is_wp_error( $detail ) ) {
		return $detail;
	}

	$p = isset( $detail['data'] ) && is_array( $detail['data'] ) ? $detail['data'] : array();
	if ( empty( $p ) ) {
		return new WP_Error( 'wookiee_cj_not_found', 'Product not found on CJ Dropshipping.' );
	}

	// Check by the real, deterministic CJ product ID before running the
	// AI cleanup at all - checking by AI-generated title instead (further
	// below, as a secondary safety net) isn't reliable on its own, since
	// re-importing the same CJ product twice could produce two slightly
	// different "clean" titles from the LLM and slip past a title-only
	// check, creating a duplicate WooCommerce product for one real item.
	$existing_id = wookiee_find_existing_cj_product( $pid );
	if ( $existing_id ) {
		return array( 'post_id' => $existing_id, 'note' => '' );
	}

	$title            = ! empty( $p['productNameEn'] ) ? $p['productNameEn'] : ( isset( $p['productName'] ) ? $p['productName'] : 'Untitled product' );
	$raw_title        = $title;
	$description      = ! empty( $p['description'] ) ? wp_strip_all_tags( $p['description'] ) : '';
	$short_description = '';
	$category         = ! empty( $p['categoryName'] ) ? $p['categoryName'] : '';
	$note             = '';

	$variants      = isset( $p['variants'] ) && is_array( $p['variants'] ) ? $p['variants'] : array();
	$first_variant = ! empty( $variants ) ? $variants[0] : array();
	$specs_text    = wookiee_extract_supplier_specs( $p, $first_variant );

	$ai = wookiee_ai_clean_supplier_product( $raw_title, $description, $category, $specs_text );
	if ( ! is_wp_error( $ai ) ) {
		if ( 'no' === $ai['fit'] ) {
			if ( $auto_skip_low_fit ) {
				return new WP_Error( 'wookiee_cj_low_fit', 'Skipped - ' . ( $ai['reason'] ? $ai['reason'] : 'doesn\'t fit the store niche.' ) );
			}
			$note = 'AI note: this may not fit your niche - ' . $ai['reason'];
		}
		$title             = ! empty( $ai['clean_title'] ) ? $ai['clean_title'] : $title;
		$description       = ! empty( $ai['long_description'] ) ? $ai['long_description'] : $description;
		$short_description = ! empty( $ai['short_description'] ) ? $ai['short_description'] : '';
		$category          = ! empty( $ai['category'] ) ? $ai['category'] : $category;
	}

	$existing = get_page_by_title( $title, OBJECT, 'product' );
	if ( $existing ) {
		return array( 'post_id' => $existing->ID, 'note' => $note );
	}

	$cost_price = ! empty( $first_variant['variantSellPrice'] ) ? $first_variant['variantSellPrice'] : ( isset( $p['sellPrice'] ) ? $p['sellPrice'] : '0.00' );
	$price      = wookiee_apply_product_markup( $cost_price );
	$vid        = isset( $first_variant['vid'] ) ? $first_variant['vid'] : '';
	$sku        = isset( $first_variant['variantSku'] ) ? $first_variant['variantSku'] : '';

	$post_id = wp_insert_post( array(
		'post_title'   => $title,
		'post_content' => wpautop( wp_kses_post( $description ) ),
		'post_excerpt' => sanitize_textarea_field( $short_description ),
		'post_status'  => 'draft',
		'post_type'    => 'product',
	) );

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return new WP_Error( 'wookiee_cj_insert_failed', 'Could not create the draft product.' );
	}

	wp_set_object_terms( $post_id, 'simple', 'product_type' );
	update_post_meta( $post_id, '_visibility', 'visible' );
	update_post_meta( $post_id, '_stock_status', 'instock' );
	update_post_meta( $post_id, '_regular_price', $price );
	update_post_meta( $post_id, '_price', $price );
	update_post_meta( $post_id, '_sku', $sku );
	update_post_meta( $post_id, '_wookiee_cj_pid', $pid );
	update_post_meta( $post_id, '_wookiee_cj_vid', $vid );
	update_post_meta( $post_id, '_wookiee_cj_cost_price', $cost_price );

	$images = array();
	if ( ! empty( $p['productImage'] ) ) {
		$images[] = $p['productImage'];
	}
	if ( ! empty( $p['productImageSet'] ) && is_array( $p['productImageSet'] ) ) {
		$images = array_merge( $images, $p['productImageSet'] );
	}
	$images = array_slice( array_unique( array_filter( $images ) ), 0, 5 );

	$attach_ids = array();
	foreach ( $images as $index => $url ) {
		$attach_id = 0;

		// Only the first/featured image gets the white-background
		// treatment - the rest of the gallery are real supplier
		// lifestyle/detail shots that should stay as-is.
		if ( 0 === $index ) {
			$flattened = wookiee_remove_background_to_white( $url );
			if ( ! is_wp_error( $flattened ) ) {
				$attach_id = wookiee_sideload_image_from_binary( $flattened, 'product-' . sanitize_title( $title ) . '-main.jpg', $title );
			}
		}

		if ( ! $attach_id ) {
			$attach_id = wookiee_sideload_remote_image( $url, $title );
		}

		if ( $attach_id ) {
			$attach_ids[] = $attach_id;
		}
	}
	if ( ! empty( $attach_ids ) ) {
		update_post_meta( $post_id, '_thumbnail_id', $attach_ids[0] );
		if ( count( $attach_ids ) > 1 ) {
			update_post_meta( $post_id, '_product_image_gallery', implode( ',', array_slice( $attach_ids, 1 ) ) );
		}
	}

	if ( ! empty( $category ) ) {
		$slug = sanitize_title( $category );
		if ( ! term_exists( $slug, 'product_cat' ) ) {
			wp_insert_term( $category, 'product_cat', array( 'slug' => $slug ) );
		}
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term ) {
			wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
		}
	}

	return array( 'post_id' => $post_id, 'note' => $note );
}

/**
 * Sideloads an externally-hosted image (a CJ product photo URL) into the
 * media library and returns the attachment ID, or 0 on failure.
 */
function wookiee_sideload_remote_image( $url, $title ) {
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$attach_id = media_sideload_image( $url, 0, $title, 'id' );
	return is_wp_error( $attach_id ) ? 0 : (int) $attach_id;
}

function wookiee_render_supplier_catalog_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$has_creds = '' !== trim( (string) wookiee_get_setting( 'cj_email' ) ) && '' !== trim( (string) wookiee_get_setting( 'cj_api_key' ) );
	$has_woo   = class_exists( 'WooCommerce' );
	$brief     = get_option( 'wookiee_niche_brief', '' );
	?>
	<div class="wrap">
		<h1>Wookiee Supplier Catalog</h1>
		<p>Search CJ Dropshipping's real, fulfillable catalog and import products as WooCommerce drafts — real title, description, price and photos from the supplier, reviewed by you before publishing. Once published, orders for these products are pushed to CJ automatically for fulfillment when they reach "Processing".</p>

		<?php if ( ! $has_woo ) : ?>
			<div class="notice notice-error"><p>WooCommerce isn't active.</p></div>
		<?php endif; ?>
		<?php if ( ! $has_creds ) : ?>
			<div class="notice notice-warning"><p>Add your CJ Dropshipping email and API key on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>

		<p>
			<input type="text" id="wookiee-cj-search-input" class="regular-text" value="<?php echo esc_attr( $brief ); ?>" placeholder="Search keyword, e.g. drawer organiser">
			<button type="button" class="button button-primary" id="wookiee-cj-search-btn" <?php disabled( ! $has_woo || ! $has_creds ); ?>>Search</button>
			<span id="wookiee-cj-search-status" style="margin-left:8px;"></span>
		</p>
		<p class="description">Every import (single or bulk) runs the supplier's raw title/description through the AI first — it cleans up presentation for Google Merchant Center (no keyword stuffing, no supplier jargon) without inventing anything, and flags results that don't genuinely fit your niche brief (CJ's own keyword search returns plenty of unrelated items, as its results often show). Bulk import skips low-fit items automatically; a single "Import as draft" click still imports what you explicitly picked, with an advisory note instead.</p>

		<p>
			<button type="button" class="button button-primary" id="wookiee-cj-bulk-import-btn" disabled>Import selected as drafts</button>
			<span id="wookiee-cj-bulk-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-cj-search-results"></div>
	</div>
	<script>
	( function() {
		var searchBtn    = document.getElementById( 'wookiee-cj-search-btn' );
		var bulkBtn      = document.getElementById( 'wookiee-cj-bulk-import-btn' );
		var bulkStatus   = document.getElementById( 'wookiee-cj-bulk-status' );
		if ( ! searchBtn ) {
			return;
		}
		var nonce   = '<?php echo esc_js( wp_create_nonce( 'wookiee_cj_catalog' ) ); ?>';
		var maxBulk = <?php echo (int) WOOKIEE_CJ_MAX_BULK_IMPORT; ?>;

		function updateBulkButton() {
			var checked = document.querySelectorAll( '.wookiee-cj-select:checked' ).length;
			bulkBtn.disabled = checked === 0;
			bulkBtn.textContent = checked ? 'Import ' + checked + ' selected as drafts' : 'Import selected as drafts';
		}

		function importProduct( pid, btn ) {
			btn.disabled = true;
			btn.textContent = 'Importing…';
			var data = new FormData();
			data.append( 'action', 'wookiee_cj_import_product' );
			data.append( 'nonce', nonce );
			data.append( 'pid', pid );
			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( ! res.success ) {
						btn.textContent = res.data && res.data.message ? res.data.message : 'Import failed';
						return;
					}
					btn.outerHTML = '<a href="' + res.data.edit_link + '" class="button" target="_blank" rel="noopener">Edit draft</a>' + ( res.data.note ? '<div style="color:#996800;font-size:12px;margin-top:4px;">' + res.data.note + '</div>' : '' );
				} )
				.catch( function() {
					btn.disabled = false;
					btn.textContent = 'Import failed — retry';
				} );
		}

		bulkBtn.addEventListener( 'click', function() {
			var pids = Array.prototype.slice.call( document.querySelectorAll( '.wookiee-cj-select:checked' ) ).map( function( el ) { return el.value; } );
			if ( ! pids.length ) {
				return;
			}
			if ( pids.length > maxBulk ) {
				pids = pids.slice( 0, maxBulk );
			}
			bulkBtn.disabled = true;
			bulkStatus.textContent = 'Importing ' + pids.length + ' product(s)… this can take a minute or two.';

			var data = new FormData();
			data.append( 'action', 'wookiee_cj_bulk_import_products' );
			data.append( 'nonce', nonce );
			pids.forEach( function( pid ) { data.append( 'pids[]', pid ); } );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					if ( ! res.success ) {
						bulkStatus.textContent = res.data && res.data.message ? res.data.message : 'Bulk import failed.';
						updateBulkButton();
						return;
					}
					var imported = 0, skipped = 0;
					res.data.results.forEach( function( r ) {
						var row = document.querySelector( '.wookiee-cj-select[value="' + r.pid + '"]' );
						if ( ! row ) { return; }
						var tr = row.closest( 'tr' );
						var statusCell = tr.querySelector( '.wookiee-cj-row-status' );
						if ( r.edit_link ) {
							imported++;
							statusCell.innerHTML = '<a href="' + r.edit_link + '" target="_blank" rel="noopener">' + r.status + '</a>';
						} else {
							skipped++;
							statusCell.textContent = r.status;
						}
						row.disabled = true;
					} );
					bulkStatus.textContent = imported + ' imported, ' + skipped + ' skipped.';
					updateBulkButton();
				} )
				.catch( function() {
					bulkStatus.textContent = 'Bulk import failed — could not reach the server.';
					updateBulkButton();
				} );
		} );

		searchBtn.addEventListener( 'click', function() {
			var status  = document.getElementById( 'wookiee-cj-search-status' );
			var results = document.getElementById( 'wookiee-cj-search-results' );
			var keyword = document.getElementById( 'wookiee-cj-search-input' ).value.trim();
			if ( ! keyword ) {
				status.textContent = 'Enter a search keyword first.';
				return;
			}
			searchBtn.disabled = true;
			status.textContent = 'Searching…';
			results.innerHTML = '';
			bulkStatus.textContent = '';
			bulkBtn.disabled = true;
			bulkBtn.textContent = 'Import selected as drafts';

			var data = new FormData();
			data.append( 'action', 'wookiee_cj_search_products' );
			data.append( 'nonce', nonce );
			data.append( 'keyword', keyword );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function( r ) { return r.json(); } )
				.then( function( res ) {
					searchBtn.disabled = false;
					if ( ! res.success ) {
						status.textContent = res.data && res.data.message ? res.data.message : 'Search failed.';
						return;
					}
					if ( ! res.data.products.length ) {
						status.textContent = 'No results.';
						return;
					}
					status.textContent = res.data.products.length + ' result(s). Select up to ' + maxBulk + ' for bulk import, or import one at a time.';
					var html = '<table class="widefat"><thead><tr><th></th><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th colspan="2"></th></tr></thead><tbody>';
					res.data.products.forEach( function( p ) {
						html += '<tr><td><input type="checkbox" class="wookiee-cj-select" value="' + p.pid + '"></td><td>' + ( p.image ? '<img src="' + p.image + '" style="width:50px;height:50px;object-fit:cover;">' : '' ) + '</td><td>' + p.name + '</td><td>' + p.category + '</td><td>' + p.price + '</td><td><button type="button" class="button wookiee-cj-import-btn" data-pid="' + p.pid + '">Import as draft</button></td><td class="wookiee-cj-row-status"></td></tr>';
					} );
					html += '</tbody></table>';
					results.innerHTML = html;

					results.querySelectorAll( '.wookiee-cj-import-btn' ).forEach( function( btn ) {
						btn.addEventListener( 'click', function() {
							importProduct( btn.getAttribute( 'data-pid' ), btn );
						} );
					} );
					results.querySelectorAll( '.wookiee-cj-select' ).forEach( function( cb ) {
						cb.addEventListener( 'change', updateBulkButton );
					} );
				} )
				.catch( function() {
					searchBtn.disabled = false;
					status.textContent = 'Search failed — could not reach the server.';
				} );
		} );
	} )();
	</script>
	<?php
}

add_action( 'wp_ajax_wookiee_cj_search_products', 'wookiee_cj_search_products_handler' );
function wookiee_cj_search_products_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_cj_catalog', 'nonce' );

	$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
	if ( '' === trim( $keyword ) ) {
		wp_send_json_error( array( 'message' => 'Enter a search keyword first.' ) );
	}

	$products = wookiee_cj_search_products( $keyword );
	if ( is_wp_error( $products ) ) {
		wp_send_json_error( array( 'message' => $products->get_error_message() ) );
	}

	$safe = array_map( function( $p ) {
		return array(
			'pid'      => esc_attr( $p['pid'] ),
			'name'     => esc_html( $p['name'] ),
			'image'    => esc_url( $p['image'] ),
			'price'    => esc_html( $p['price'] ),
			'category' => esc_html( $p['category'] ),
		);
	}, $products );

	wp_send_json_success( array( 'products' => $safe ) );
}

add_action( 'wp_ajax_wookiee_cj_import_product', 'wookiee_cj_import_product_handler' );
function wookiee_cj_import_product_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_cj_catalog', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
	}

	$pid = isset( $_POST['pid'] ) ? sanitize_text_field( wp_unslash( $_POST['pid'] ) ) : '';
	if ( '' === $pid ) {
		wp_send_json_error( array( 'message' => 'Missing product ID.' ) );
	}

	$result = wookiee_cj_import_product( $pid );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( array(
		'edit_link' => get_edit_post_link( $result['post_id'], 'raw' ),
		'note'      => $result['note'],
	) );
}

/**
 * Imports several selected CJ products in one call, capped at
 * WOOKIEE_CJ_MAX_BULK_IMPORT to keep LLM cost/time bounded. Unlike the
 * single-import path, a low AI fit judgement here skips the product
 * rather than importing it - bulk import is exactly the case where
 * curating out catalog noise (the CJ search screenshot that prompted
 * this had a leg massager next to home-decor results) matters most,
 * since there's no per-item human decision happening along the way.
 */
add_action( 'wp_ajax_wookiee_cj_bulk_import_products', 'wookiee_cj_bulk_import_products_handler' );
function wookiee_cj_bulk_import_products_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_cj_catalog', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ) );
	}

	$pids = isset( $_POST['pids'] ) && is_array( $_POST['pids'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['pids'] ) ) : array();
	$pids = array_slice( array_filter( $pids ), 0, WOOKIEE_CJ_MAX_BULK_IMPORT );

	if ( empty( $pids ) ) {
		wp_send_json_error( array( 'message' => 'Select at least one product first.' ) );
	}

	$results = array();
	foreach ( $pids as $pid ) {
		$result = wookiee_cj_import_product( $pid, true );
		if ( is_wp_error( $result ) ) {
			$results[] = array( 'pid' => $pid, 'status' => $result->get_error_message(), 'edit_link' => '' );
			continue;
		}
		$results[] = array(
			'pid'       => $pid,
			'status'    => $result['note'] ? $result['note'] : 'Imported',
			'edit_link' => get_edit_post_link( $result['post_id'], 'raw' ),
		);
	}

	wp_send_json_success( array( 'results' => $results ) );
}

/**
 * Fulfillment push: once a real order reaches "Processing", push any
 * CJ-sourced line items to CJ as a fulfillment order. Products not
 * carrying a _wookiee_cj_vid (e.g. AI-drafted or hand-added products)
 * are skipped - this only fires for genuinely CJ-sourced items.
 */
add_action( 'woocommerce_order_status_processing', 'wookiee_cj_maybe_push_order' );
function wookiee_cj_maybe_push_order( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_wookiee_cj_order_pushed' ) ) {
		return;
	}

	$line_items = array();
	foreach ( $order->get_items() as $item ) {
		$product_id = $item->get_product_id();
		$vid        = get_post_meta( $product_id, '_wookiee_cj_vid', true );
		if ( ! $vid ) {
			continue;
		}
		$line_items[] = array( 'vid' => $vid, 'quantity' => $item->get_quantity() );
	}

	if ( empty( $line_items ) ) {
		return;
	}

	$body = array(
		'orderNumber'          => 'WK-' . $order_id,
		'shippingCountryCode'  => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
		'shippingProvince'     => $order->get_shipping_state(),
		'shippingCity'         => $order->get_shipping_city(),
		'shippingAddress'      => $order->get_shipping_address_1(),
		'shippingAddress2'     => $order->get_shipping_address_2(),
		'shippingZip'          => $order->get_shipping_postcode(),
		'shippingCustomerName' => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
		'shippingPhone'        => $order->get_billing_phone(),
		'email'                => $order->get_billing_email(),
		'products'             => $line_items,
	);

	$result = wookiee_cj_request( 'POST', '/shopping/order/createOrder', $body );

	if ( is_wp_error( $result ) ) {
		$order->add_order_note( 'CJ Dropshipping fulfillment push failed: ' . $result->get_error_message() );
		return;
	}

	$order->update_meta_data( '_wookiee_cj_order_pushed', 1 );
	if ( isset( $result['data']['orderId'] ) ) {
		$order->update_meta_data( '_wookiee_cj_order_id', $result['data']['orderId'] );
	}
	$order->add_order_note( 'Pushed to CJ Dropshipping for fulfillment.' );
	$order->save();
}
