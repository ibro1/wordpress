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
 * The backend URL is the same for every WordPress install running this
 * theme (one backend, many stores), so it's baked in below rather than a
 * per-site field - a store owner has no reason to ever see or change it.
 * The shared secret DOES stay a per-site wp_options field (not baked in):
 * how this site authenticates to that backend.
 *
 * Every call site that used to hit a provider directly with a local
 * wp_options key now checks wookiee_central_api_configured() first and
 * prefers the backend; the direct-call code path still exists as a
 * fallback for sites that haven't set a shared secret yet, so nothing
 * breaks mid-rollout.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fixed for every install of this theme - not a setting. Change this one
 * line if the backend ever moves to a different domain.
 */
function wookiee_central_api_base_url() {
	return 'https://api.davebukartechnologies.com';
}

function wookiee_central_api_shared_secret() {
	return (string) get_option( 'wookiee_setting_wookiee_api_shared_secret', '' );
}

function wookiee_central_api_configured() {
	return '' !== wookiee_central_api_base_url() && '' !== trim( wookiee_central_api_shared_secret() );
}

/**
 * The domain this activation code is bound to on the backend - the
 * backend rejects any request presenting a code that hasn't been
 * activated for this exact host, so it has to be sent on every request,
 * not just the one-time activation call.
 */
function wookiee_current_site_domain() {
	return strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
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
		'rembg_endpoint_url', 'bg_removal_provider',
		'google_ads_developer_token', 'google_ads_client_id', 'google_ads_client_secret',
		'google_ads_refresh_token', 'google_ads_customer_id', 'google_ads_login_customer_id',
		'spaceship_api_key', 'spaceship_api_secret',
	);
}

/**
 * Site-wide reminder that nothing depending on the backend works yet -
 * shown across wp-admin, not just on the Settings page, since an admin
 * could easily land on Products or the Setup wizard first and otherwise
 * have no idea why AI/domain/sourcing features are all failing silently.
 * Suppressed on the Settings page itself, where the same message is
 * already the first thing on the page (see wookiee_render_activation_section()).
 */
add_action( 'admin_notices', 'wookiee_maybe_show_activation_notice' );
function wookiee_maybe_show_activation_notice() {
	if ( ! current_user_can( 'manage_options' ) || wookiee_central_api_configured() ) {
		return;
	}
	if ( isset( $_GET['page'] ) && 'wookiee-settings' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	?>
	<div class="notice notice-error">
		<p><strong>Wookiee is not activated.</strong> AI generation, Companies House lookup, domain search/registration, and CJ product sourcing are unavailable until an activation code is entered on the <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Wookiee Settings</a> page.</p>
	</div>
	<?php
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
		return new WP_Error( 'wookiee_central_api_not_configured', 'The central backend is not connected yet (Settings > Activation).' );
	}

	$args = array(
		'method'  => $method,
		'headers' => array(
			'X-Api-Key'     => wookiee_central_api_shared_secret(),
			'X-Site-Domain' => wookiee_current_site_domain(),
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
 * Validates an activation code against the backend's public activate
 * endpoint (no X-Api-Key needed - that's the point of this call) and only
 * saves it locally on success. A code is rejected if it doesn't exist, has
 * been revoked, or has already reached its site limit on OTHER domains;
 * re-activating the same code for this same domain always succeeds
 * (idempotent), so re-saving after e.g. a typo fix never burns an
 * activation slot twice.
 */
add_action( 'wp_ajax_wookiee_activate_backend', 'wookiee_activate_backend_handler' );
function wookiee_activate_backend_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_activate_backend', 'nonce' );

	$code = isset( $_POST['code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
	if ( '' === trim( $code ) ) {
		wp_send_json_error( array( 'message' => 'Enter an activation code first.' ) );
	}

	$response = wp_remote_post( wookiee_central_api_base_url() . '/licenses/activate', array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( array( 'code' => $code, 'domain' => wookiee_current_site_domain() ) ),
		'timeout' => 20,
	) );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( array( 'message' => $response->get_error_message() ) );
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$data        = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $status_code < 200 || $status_code >= 300 ) {
		$message = is_array( $data ) && isset( $data['error'] ) ? $data['error'] : ( 'Backend returned HTTP ' . intval( $status_code ) );
		wp_send_json_error( array( 'message' => $message ) );
	}

	update_option( 'wookiee_setting_wookiee_api_shared_secret', $code );

	wp_send_json_success();
}
