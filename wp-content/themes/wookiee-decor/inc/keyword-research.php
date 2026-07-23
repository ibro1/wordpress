<?php
/**
 * Real keyword search-volume + CPC data via the Google Ads API, used to
 * ground the Product Generator's AI concept picks in actual demand
 * instead of pure LLM guessing - directly answers "would this get
 * traffic" and "what would ads cost" with real numbers instead of
 * nothing. Optional: if not configured, the Product Generator falls
 * back to its previous pure-LLM brainstorming, unchanged.
 *
 * Requires a Google Ads account, a developer token (Basic access for
 * real production data, not just test-account access), and OAuth2
 * credentials (client ID/secret + a one-time-generated refresh token) -
 * see Wookiee Settings -> AI & Integrations. The API itself has no
 * per-call charge from Google, but this is a genuine OAuth setup, not a
 * single API key like the theme's other integrations.
 *
 * NOT yet exercised against a live Google Ads account - built against
 * the documented Google Ads API REST shape for GenerateKeywordIdeas.
 * Same "needs a real-credential smoke test" caveat as CJ Dropshipping
 * and rembg when they were first built.
 */

defined( 'ABSPATH' ) || exit;

// Google Ads API versions are deprecated roughly annually - bump this
// if requests start failing with a version-related error.
define( 'WOOKIEE_GOOGLE_ADS_API_VERSION', 'v17' );

function wookiee_google_ads_configured() {
	if ( wookiee_central_api_configured() ) {
		$status = wookiee_central_api_request( 'GET', '/google-ads/status' );
		return ! is_wp_error( $status ) && ! empty( $status['configured'] );
	}
	return '' !== trim( (string) wookiee_get_setting( 'google_ads_developer_token' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'google_ads_client_id' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'google_ads_client_secret' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'google_ads_refresh_token' ) )
		&& '' !== trim( (string) wookiee_get_setting( 'google_ads_customer_id' ) );
}

/**
 * Exchanges the stored refresh token for a short-lived access token,
 * cached in a transient (Google's access tokens last ~1 hour; cached
 * for 50 minutes to stay safely inside that window without needing a
 * fresh OAuth round-trip on every keyword request).
 */
function wookiee_google_ads_get_access_token() {
	$cached = get_transient( 'wookiee_google_ads_access_token' );
	if ( $cached ) {
		return $cached;
	}

	$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 30,
		'body'    => array(
			'client_id'     => wookiee_get_setting( 'google_ads_client_id' ),
			'client_secret' => wookiee_get_setting( 'google_ads_client_secret' ),
			'refresh_token' => wookiee_get_setting( 'google_ads_refresh_token' ),
			'grant_type'    => 'refresh_token',
		),
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code || empty( $data['access_token'] ) ) {
		$msg = isset( $data['error_description'] ) ? $data['error_description'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_google_ads_auth_failed', 'Google Ads authentication failed: ' . $msg );
	}

	$expires_in = isset( $data['expires_in'] ) ? intval( $data['expires_in'] ) : 3600;
	set_transient( 'wookiee_google_ads_access_token', $data['access_token'], max( 60, $expires_in - 300 ) );

	return $data['access_token'];
}

/**
 * Requests real keyword ideas (avg monthly UK search volume,
 * competition, top-of-page CPC range) seeded from a niche brief or list
 * of terms. Returns a plain array of {keyword, avg_monthly_searches,
 * competition, low_cpc_gbp, high_cpc_gbp}, sorted by search volume
 * descending, or a WP_Error.
 */
function wookiee_google_ads_keyword_ideas( $seed_keywords ) {
	if ( ! wookiee_google_ads_configured() ) {
		return new WP_Error( 'wookiee_google_ads_not_configured', 'Add your Google Ads API credentials on the Wookiee Settings page first.' );
	}

	if ( wookiee_central_api_configured() ) {
		$result = wookiee_central_api_request( 'POST', '/google-ads/keyword-ideas', array( 'seed_keywords' => array_values( array_filter( (array) $seed_keywords ) ) ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return isset( $result['ideas'] ) ? $result['ideas'] : array();
	}

	$access_token = wookiee_google_ads_get_access_token();
	if ( is_wp_error( $access_token ) ) {
		return $access_token;
	}

	$customer_id = preg_replace( '/[^0-9]/', '', (string) wookiee_get_setting( 'google_ads_customer_id' ) );
	$login_id    = preg_replace( '/[^0-9]/', '', (string) wookiee_get_setting( 'google_ads_login_customer_id' ) );

	$headers = array(
		'Authorization'   => 'Bearer ' . $access_token,
		'developer-token' => wookiee_get_setting( 'google_ads_developer_token' ),
		'Content-Type'    => 'application/json',
	);
	if ( '' !== $login_id ) {
		$headers['login-customer-id'] = $login_id;
	}

	$body = array(
		'keywordSeed'        => array( 'keywords' => array_values( array_filter( (array) $seed_keywords ) ) ),
		'geoTargetConstants'  => array( 'geoTargetConstants/2826' ), // United Kingdom
		'language'            => 'languageConstants/1000', // English
		'keywordPlanNetwork'  => 'GOOGLE_SEARCH',
	);

	$response = wp_remote_post(
		'https://googleads.googleapis.com/' . WOOKIEE_GOOGLE_ADS_API_VERSION . "/customers/{$customer_id}:generateKeywordIdeas",
		array(
			'timeout' => 30,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_google_ads_error', 'Google Ads API error: ' . $msg );
	}

	$results = isset( $data['results'] ) && is_array( $data['results'] ) ? $data['results'] : array();
	$ideas   = array();
	foreach ( $results as $result ) {
		$metrics  = isset( $result['keywordIdeaMetrics'] ) ? $result['keywordIdeaMetrics'] : array();
		$ideas[] = array(
			'keyword'              => isset( $result['text'] ) ? $result['text'] : '',
			'avg_monthly_searches' => isset( $metrics['avgMonthlySearches'] ) ? intval( $metrics['avgMonthlySearches'] ) : 0,
			'competition'          => isset( $metrics['competition'] ) ? $metrics['competition'] : 'UNKNOWN',
			'low_cpc_gbp'          => isset( $metrics['lowTopOfPageBidMicros'] ) ? round( $metrics['lowTopOfPageBidMicros'] / 1000000, 2 ) : null,
			'high_cpc_gbp'         => isset( $metrics['highTopOfPageBidMicros'] ) ? round( $metrics['highTopOfPageBidMicros'] / 1000000, 2 ) : null,
		);
	}

	usort( $ideas, function ( $a, $b ) {
		return $b['avg_monthly_searches'] <=> $a['avg_monthly_searches'];
	} );

	return $ideas;
}

/**
 * One-click "Connect to Google Ads" flow: instead of the admin having
 * to run some external script/tool to generate a refresh token by
 * hand, this drives the standard OAuth2 authorization-code flow
 * entirely through the browser and saves the resulting refresh token
 * automatically. The Client ID/Secret must already be saved in Wookiee
 * Settings before connecting, since the redirect-back exchange step
 * needs them. access_type=offline + prompt=consent guarantees Google
 * returns a refresh token (it's only ever returned on first consent
 * unless consent is forced), and a WP nonce doubles as the OAuth
 * `state` param for CSRF protection across the external redirect.
 */
function wookiee_google_ads_oauth_redirect_uri() {
	return admin_url( 'admin-post.php?action=wookiee_google_ads_oauth_callback' );
}

function wookiee_google_ads_oauth_start_url() {
	if ( wookiee_central_api_configured() ) {
		// The whole OAuth round-trip (consent screen + callback + token
		// exchange) happens entirely on the backend when it's connected -
		// nothing comes back through WordPress at all, unlike the local
		// admin-post.php-based flow below.
		return wookiee_central_api_base_url() . '/google-ads/oauth/start';
	}
	$params = array(
		'client_id'     => wookiee_get_setting( 'google_ads_client_id' ),
		'redirect_uri'  => wookiee_google_ads_oauth_redirect_uri(),
		'response_type' => 'code',
		'scope'         => 'https://www.googleapis.com/auth/adwords',
		'access_type'   => 'offline',
		'prompt'        => 'consent',
		'state'         => wp_create_nonce( 'wookiee_google_ads_oauth' ),
	);
	return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
}

add_action( 'admin_post_wookiee_google_ads_oauth_callback', 'wookiee_google_ads_oauth_callback' );
function wookiee_google_ads_oauth_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}

	$settings_url = admin_url( 'admin.php?page=wookiee-settings#integrations' );

	if ( ! empty( $_GET['error'] ) ) {
		wp_safe_redirect( add_query_arg( 'wookiee_google_ads_error', rawurlencode( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ), $settings_url ) );
		exit;
	}

	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
	if ( ! wp_verify_nonce( $state, 'wookiee_google_ads_oauth' ) ) {
		wp_safe_redirect( add_query_arg( 'wookiee_google_ads_error', 'invalid_state', $settings_url ) );
		exit;
	}

	$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	if ( '' === $code ) {
		wp_safe_redirect( add_query_arg( 'wookiee_google_ads_error', 'missing_code', $settings_url ) );
		exit;
	}

	$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 30,
		'body'    => array(
			'code'          => $code,
			'client_id'     => wookiee_get_setting( 'google_ads_client_id' ),
			'client_secret' => wookiee_get_setting( 'google_ads_client_secret' ),
			'redirect_uri'  => wookiee_google_ads_oauth_redirect_uri(),
			'grant_type'    => 'authorization_code',
		),
	) );

	if ( is_wp_error( $response ) ) {
		wp_safe_redirect( add_query_arg( 'wookiee_google_ads_error', rawurlencode( $response->get_error_message() ), $settings_url ) );
		exit;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data['refresh_token'] ) ) {
		$msg = isset( $data['error_description'] ) ? $data['error_description'] : 'Google did not return a refresh token.';
		wp_safe_redirect( add_query_arg( 'wookiee_google_ads_error', rawurlencode( $msg ), $settings_url ) );
		exit;
	}

	update_option( 'wookiee_setting_google_ads_refresh_token', sanitize_text_field( $data['refresh_token'] ) );
	delete_transient( 'wookiee_google_ads_access_token' );

	wp_safe_redirect( add_query_arg( 'wookiee_google_ads_connected', '1', $settings_url ) );
	exit;
}
