<?php
/**
 * Client for the centrally-operated wookiee-api backend (services/wookiee-api
 * in the theme repo) - one Companies House / LLM / CJ Dropshipping /
 * Cloudinary / Google Ads / Spaceship account, run once by the platform
 * operator, shared by every WordPress install running this theme. A store
 * owner using the theme has no account with any of those providers and
 * never sees a key field for them - only the operator (whoever deploys the
 * backend) manages those, in the backend's own settings UI.
 *
 * What DOES stay per-site in wp_options: the backend's base URL + shared
 * secret (how this site reaches the backend at all - not a third-party
 * credential, just a connection detail), and genuinely per-site data like
 * company_number/business_name/registered_address.
 *
 * Every call site that used to hit a provider directly with a local
 * wp_options key now checks wookiee_central_api_configured() first and
 * prefers the backend; the direct-call code path still exists as a
 * fallback for sites that haven't connected a backend yet, so nothing
 * breaks mid-rollout.
 */

defined( 'ABSPATH' ) || exit;

function wookiee_central_api_base_url() {
	return rtrim( (string) get_option( 'wookiee_setting_wookiee_api_base_url', '' ), '/' );
}

function wookiee_central_api_shared_secret() {
	return (string) get_option( 'wookiee_setting_wookiee_api_shared_secret', '' );
}

function wookiee_central_api_configured() {
	return '' !== wookiee_central_api_base_url() && '' !== trim( wookiee_central_api_shared_secret() );
}

/**
 * The provider keys a store owner using this theme has no business seeing
 * or entering - the platform operator manages exactly one copy of each,
 * centrally, in the backend's own settings UI. Shared between the
 * migration handler below and theme-settings.php's rendering, so the two
 * can't drift out of sync with each other.
 */
function wookiee_operator_only_settings_keys() {
	return array(
		'companies_house_api_key',
		'llm_api_key', 'llm_base_url', 'llm_default_model',
		'cj_email', 'cj_api_key',
		'cloudinary_cloud_name', 'cloudinary_api_key', 'cloudinary_api_secret',
		'rembg_endpoint_url',
		'google_ads_developer_token', 'google_ads_client_id', 'google_ads_client_secret',
		'google_ads_refresh_token', 'google_ads_customer_id', 'google_ads_login_customer_id',
		'spaceship_api_key', 'spaceship_api_secret',
	);
}

function wookiee_secrets_migrated_to_backend() {
	return (bool) get_option( 'wookiee_secrets_migrated_to_backend', false );
}

/**
 * Generic authenticated request to the backend. $path starts with a slash,
 * e.g. '/companies-house/lookup?company_number=SC769264'. Returns the
 * decoded JSON body (array) on success, or a WP_Error - callers check
 * is_wp_error() exactly like they already do for direct provider calls, so
 * swapping the call site over is a small, mechanical change.
 */
function wookiee_central_api_request( $method, $path, $body = null ) {
	if ( ! wookiee_central_api_configured() ) {
		return new WP_Error( 'wookiee_central_api_not_configured', 'The central backend is not connected yet (Settings > AI & Integrations).' );
	}

	$args = array(
		'method'  => $method,
		'headers' => array(
			'X-Api-Key' => wookiee_central_api_shared_secret(),
		),
		'timeout' => 30,
	);

	if ( null !== $body ) {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body']                    = wp_json_encode( $body );
	}

	$response = wp_remote_request( wookiee_central_api_base_url() . $path, $args );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code < 200 || $code >= 300 ) {
		$message = is_array( $data ) && isset( $data['error'] ) ? $data['error'] : ( 'Backend returned HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_central_api_error', $message );
	}

	return is_array( $data ) ? $data : array();
}

/**
 * One-time push of every provider key currently sitting in this site's
 * wp_options up to the backend, then clears them locally - after this,
 * this WordPress install holds none of those credentials itself, only the
 * backend does. Safe to click more than once (the backend just overwrites
 * with whatever's sent - if you've since edited a key directly on the
 * backend, DON'T re-run this from an install with stale local values, since
 * it would overwrite the backend's newer value with the stale local one;
 * the button is hidden after the first successful migration for exactly
 * this reason).
 */
add_action( 'wp_ajax_wookiee_migrate_secrets_to_backend', 'wookiee_migrate_secrets_to_backend_handler' );
function wookiee_migrate_secrets_to_backend_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_migrate_secrets_to_backend', 'nonce' );

	$keys = wookiee_operator_only_settings_keys();

	$values = array();
	foreach ( $keys as $key ) {
		$values[ $key ] = wookiee_get_setting( $key );
	}

	$result = wookiee_central_api_request( 'POST', '/settings', array( 'values' => $values ) );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	foreach ( $keys as $key ) {
		delete_option( 'wookiee_setting_' . $key );
	}
	update_option( 'wookiee_secrets_migrated_to_backend', 1 );

	wp_send_json_success();
}

/**
 * Same end state as the migration above (local fields cleared, hidden from
 * Settings from then on), but skips the backend call entirely - for when
 * the values were already copied over to the backend by hand (e.g. read via
 * the Show/Hide toggle and pasted into env vars) rather than through the
 * migrate button. Doesn't require the backend to even be reachable.
 */
add_action( 'wp_ajax_wookiee_clear_local_secrets', 'wookiee_clear_local_secrets_handler' );
function wookiee_clear_local_secrets_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_clear_local_secrets', 'nonce' );

	foreach ( wookiee_operator_only_settings_keys() as $key ) {
		delete_option( 'wookiee_setting_' . $key );
	}
	update_option( 'wookiee_secrets_migrated_to_backend', 1 );

	wp_send_json_success();
}
