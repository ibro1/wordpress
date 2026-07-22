<?php
/**
 * White-background automation for the first/featured product image on
 * CJ Dropshipping imports. Two swappable providers, chosen in Wookiee
 * Settings, with automatic fallback from one to the other on failure.
 * Both return a transparent-background image, then this composites it
 * onto solid white with PHP's GD library (bundled with WordPress) - the
 * "flatten to white" step is identical regardless of which provider ran.
 *
 * Deliberately NOT generative AI (e.g. Gemini/GPT image models). Those
 * regenerate pixels from a prompt and can subtly alter the real product
 * (color, shape, fine detail) - exactly what docs/exif and webp.txt
 * already forbids ("do NOT regenerate the product image with AI").
 * Both providers here do real segmentation of the existing photo, not
 * regeneration - the product's actual pixels are preserved, only the
 * background changes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Entry point: takes a source image URL, returns finished white-
 * background JPEG bytes, or a WP_Error if disabled/unconfigured/every
 * attempt failed. Tries the configured primary provider first, then
 * falls back to the other provider if it has credentials/endpoint set.
 */
function wookiee_remove_background_to_white( $image_url ) {
	$primary = wookiee_get_setting( 'bg_removal_provider' );
	if ( '' === $primary || 'none' === $primary ) {
		return new WP_Error( 'wookiee_bg_disabled', 'Background removal is not enabled (set a provider on the AI & Integrations tab).' );
	}

	$providers = ( 'cloudinary' === $primary )
		? array( 'cloudinary', 'rembg' )
		: array( 'rembg', 'cloudinary' );

	$last_error = null;
	foreach ( $providers as $provider ) {
		if ( 'cloudinary' === $provider && ! wookiee_cloudinary_configured() ) {
			continue;
		}
		if ( 'rembg' === $provider && ! wookiee_rembg_configured() ) {
			continue;
		}

		$png = ( 'cloudinary' === $provider )
			? wookiee_bg_removal_cloudinary( $image_url )
			: wookiee_bg_removal_rembg( $image_url );

		if ( is_wp_error( $png ) ) {
			$last_error = $png;
			continue;
		}

		$flattened = wookiee_composite_png_onto_white( $png );
		if ( is_wp_error( $flattened ) ) {
			$last_error = $flattened;
			continue;
		}

		return $flattened;
	}

	return $last_error ? $last_error : new WP_Error( 'wookiee_bg_no_provider', 'No background-removal provider is configured with valid credentials.' );
}

function wookiee_cloudinary_configured() {
	return '' !== trim( (string) wookiee_get_setting( 'cloudinary_cloud_name' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'cloudinary_api_key' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'cloudinary_api_secret' ) );
}

function wookiee_rembg_configured() {
	return '' !== trim( (string) wookiee_get_setting( 'rembg_endpoint_url' ) );
}

/**
 * Uploads to Cloudinary with the AI Background Removal add-on and
 * returns the resulting transparent-PNG bytes. That add-on has to be
 * enabled on the Cloudinary account - if it isn't, Cloudinary's API
 * returns an error, surfaced here as a WP_Error so the caller falls
 * back to the other provider.
 */
function wookiee_bg_removal_cloudinary( $image_url ) {
	$cloud_name = wookiee_get_setting( 'cloudinary_cloud_name' );
	$api_key    = wookiee_get_setting( 'cloudinary_api_key' );
	$api_secret = wookiee_get_setting( 'cloudinary_api_secret' );

	$timestamp = time();
	// Cloudinary signs every param except file/api_key/signature/resource_type, sorted alphabetically, then appends the API secret.
	$params_to_sign = array(
		'background_removal' => 'cloudinary_ai',
		'timestamp'           => $timestamp,
	);
	ksort( $params_to_sign );
	$to_sign = array();
	foreach ( $params_to_sign as $key => $value ) {
		$to_sign[] = $key . '=' . $value;
	}
	$signature = sha1( implode( '&', $to_sign ) . $api_secret );

	$response = wp_remote_post( "https://api.cloudinary.com/v1_1/{$cloud_name}/image/upload", array(
		'timeout' => 60,
		'body'    => array(
			'file'               => $image_url,
			'api_key'            => $api_key,
			'timestamp'          => $timestamp,
			'background_removal' => 'cloudinary_ai',
			'signature'          => $signature,
		),
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_cloudinary_error', 'Cloudinary error: ' . $msg );
	}

	$png_url = isset( $data['secure_url'] ) ? $data['secure_url'] : '';
	if ( '' === $png_url ) {
		return new WP_Error( 'wookiee_cloudinary_no_url', 'Cloudinary did not return a processed image URL.' );
	}

	$image_response = wp_remote_get( $png_url, array( 'timeout' => 30 ) );
	if ( is_wp_error( $image_response ) ) {
		return $image_response;
	}
	if ( 200 !== wp_remote_retrieve_response_code( $image_response ) ) {
		return new WP_Error( 'wookiee_cloudinary_fetch_failed', 'Could not download the processed image from Cloudinary.' );
	}

	return wp_remote_retrieve_body( $image_response );
}

/**
 * Calls a self-hosted rembg server (the danielgatis/rembg Docker image
 * running in "serve" mode - see the compose snippet supplied alongside
 * this feature). Verified against the live container's own
 * /openapi.json: rembg exposes two unrelated routes under the same
 * path - GET /api/remove?url=... ("Remove from URL", query param) and
 * POST /api/remove ("Remove from Stream", requiring a raw multipart
 * file upload, not a URL). The original implementation POSTed a `url`
 * form field, which matches neither route - hence the 422s. Fixed to
 * use the GET+query route, which is the one actually meant for this.
 */
function wookiee_bg_removal_rembg( $image_url ) {
	$endpoint = rtrim( (string) wookiee_get_setting( 'rembg_endpoint_url' ), '/' );

	$response = wp_remote_get( $endpoint . '/api/remove?url=' . rawurlencode( $image_url ), array(
		'timeout' => 60,
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$msg  = isset( $data['detail'] ) ? wp_json_encode( $data['detail'] ) : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_rembg_error', 'Self-hosted rembg service error: ' . $msg );
	}

	$body = wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return new WP_Error( 'wookiee_rembg_empty', 'Self-hosted rembg service returned an empty response.' );
	}

	return $body;
}

/**
 * Flattens a transparent-background image onto solid white using GD
 * (bundled with WordPress, no extra dependency). Returns JPEG bytes -
 * JPEG has no alpha channel, which forces a fully opaque white rather
 * than trusting either provider's own flattening behaviour.
 */
function wookiee_composite_png_onto_white( $image_bytes ) {
	if ( ! function_exists( 'imagecreatefromstring' ) ) {
		return new WP_Error( 'wookiee_bg_no_gd', 'The GD image library is not available on this server.' );
	}

	$source = @imagecreatefromstring( $image_bytes );
	if ( ! $source ) {
		return new WP_Error( 'wookiee_bg_decode_failed', 'Could not decode the processed image.' );
	}

	$width  = imagesx( $source );
	$height = imagesy( $source );

	$canvas = imagecreatetruecolor( $width, $height );
	$white  = imagecolorallocate( $canvas, 255, 255, 255 );
	imagefill( $canvas, 0, 0, $white );
	imagealphablending( $canvas, true );
	imagecopy( $canvas, $source, 0, 0, 0, 0, $width, $height );

	ob_start();
	imagejpeg( $canvas, null, 90 );
	$jpeg_bytes = ob_get_clean();

	imagedestroy( $source );
	imagedestroy( $canvas );

	if ( '' === (string) $jpeg_bytes ) {
		return new WP_Error( 'wookiee_bg_encode_failed', 'Could not encode the flattened image.' );
	}

	return $jpeg_bytes;
}

/**
 * Sideloads raw image bytes (the flattened white-background JPEG,
 * which has no URL of its own) as a media library attachment. The
 * existing wookiee_sideload_remote_image() only accepts a URL, so this
 * covers the one case that isn't a URL fetch.
 */
function wookiee_sideload_image_from_binary( $binary_data, $filename, $title ) {
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$upload = wp_upload_bits( $filename, null, $binary_data );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype   = wp_check_filetype( $upload['file'] );
	$attach_id  = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => $title,
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $upload['file'] );

	if ( ! $attach_id || is_wp_error( $attach_id ) ) {
		return 0;
	}

	$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	return (int) $attach_id;
}
