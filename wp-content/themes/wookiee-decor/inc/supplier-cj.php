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
 * Imports one CJ product (by pid) as a WooCommerce Draft product using
 * the first variant's price/SKU. Skips creating a duplicate if a product
 * with the same title already exists.
 */
function wookiee_cj_import_product( $pid ) {
	$detail = wookiee_cj_request( 'GET', '/product/query?pid=' . rawurlencode( $pid ) );
	if ( is_wp_error( $detail ) ) {
		return $detail;
	}

	$p = isset( $detail['data'] ) && is_array( $detail['data'] ) ? $detail['data'] : array();
	if ( empty( $p ) ) {
		return new WP_Error( 'wookiee_cj_not_found', 'Product not found on CJ Dropshipping.' );
	}

	$title = ! empty( $p['productNameEn'] ) ? $p['productNameEn'] : ( isset( $p['productName'] ) ? $p['productName'] : 'Untitled product' );

	$existing = get_page_by_title( $title, OBJECT, 'product' );
	if ( $existing ) {
		return $existing->ID;
	}

	$variants      = isset( $p['variants'] ) && is_array( $p['variants'] ) ? $p['variants'] : array();
	$first_variant = ! empty( $variants ) ? $variants[0] : array();
	$price         = ! empty( $first_variant['variantSellPrice'] ) ? $first_variant['variantSellPrice'] : ( isset( $p['sellPrice'] ) ? $p['sellPrice'] : '0.00' );
	$vid           = isset( $first_variant['vid'] ) ? $first_variant['vid'] : '';
	$sku           = isset( $first_variant['variantSku'] ) ? $first_variant['variantSku'] : '';
	$description   = ! empty( $p['description'] ) ? wp_kses_post( $p['description'] ) : '';

	$post_id = wp_insert_post( array(
		'post_title'   => $title,
		'post_content' => $description,
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

	$images = array();
	if ( ! empty( $p['productImage'] ) ) {
		$images[] = $p['productImage'];
	}
	if ( ! empty( $p['productImageSet'] ) && is_array( $p['productImageSet'] ) ) {
		$images = array_merge( $images, $p['productImageSet'] );
	}
	$images = array_slice( array_unique( array_filter( $images ) ), 0, 5 );

	$attach_ids = array();
	foreach ( $images as $url ) {
		$attach_id = wookiee_sideload_remote_image( $url, $title );
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

	if ( ! empty( $p['categoryName'] ) ) {
		$slug = sanitize_title( $p['categoryName'] );
		if ( ! term_exists( $slug, 'product_cat' ) ) {
			wp_insert_term( $p['categoryName'], 'product_cat', array( 'slug' => $slug ) );
		}
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term ) {
			wp_set_object_terms( $post_id, $term->term_id, 'product_cat' );
		}
	}

	return $post_id;
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

add_action( 'admin_menu', 'wookiee_register_supplier_catalog_page' );
function wookiee_register_supplier_catalog_page() {
	add_theme_page(
		'Wookiee Supplier Catalog',
		'Wookiee Supplier Catalog',
		'manage_options',
		'wookiee-supplier-catalog',
		'wookiee_render_supplier_catalog_page'
	);
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
			<div class="notice notice-warning"><p>Add your CJ Dropshipping email and API key on the <a href="<?php echo esc_url( admin_url( 'themes.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page first.</p></div>
		<?php endif; ?>

		<p>
			<input type="text" id="wookiee-cj-search-input" class="regular-text" value="<?php echo esc_attr( $brief ); ?>" placeholder="Search keyword, e.g. drawer organiser">
			<button type="button" class="button button-primary" id="wookiee-cj-search-btn" <?php disabled( ! $has_woo || ! $has_creds ); ?>>Search</button>
			<span id="wookiee-cj-search-status" style="margin-left:8px;"></span>
		</p>

		<div id="wookiee-cj-search-results"></div>
	</div>
	<script>
	( function() {
		var searchBtn = document.getElementById( 'wookiee-cj-search-btn' );
		if ( ! searchBtn ) {
			return;
		}
		var nonce = '<?php echo esc_js( wp_create_nonce( 'wookiee_cj_catalog' ) ); ?>';

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
					btn.textContent = 'Imported ✓';
					btn.outerHTML = '<a href="' + res.data.edit_link + '" class="button">Edit draft</a>';
				} )
				.catch( function() {
					btn.disabled = false;
					btn.textContent = 'Import failed — retry';
				} );
		}

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
					status.textContent = res.data.products.length + ' result(s).';
					var html = '<table class="widefat"><thead><tr><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th></th></tr></thead><tbody>';
					res.data.products.forEach( function( p, i ) {
						html += '<tr><td>' + ( p.image ? '<img src="' + p.image + '" style="width:50px;height:50px;object-fit:cover;">' : '' ) + '</td><td>' + p.name + '</td><td>' + p.category + '</td><td>' + p.price + '</td><td><button type="button" class="button wookiee-cj-import-btn" data-pid="' + p.pid + '">Import as draft</button></td></tr>';
					} );
					html += '</tbody></table>';
					results.innerHTML = html;

					results.querySelectorAll( '.wookiee-cj-import-btn' ).forEach( function( btn ) {
						btn.addEventListener( 'click', function() {
							importProduct( btn.getAttribute( 'data-pid' ), btn );
						} );
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

	$post_id = wookiee_cj_import_product( $pid );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
	}

	wp_send_json_success( array( 'edit_link' => get_edit_post_link( $post_id, 'raw' ) ) );
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
