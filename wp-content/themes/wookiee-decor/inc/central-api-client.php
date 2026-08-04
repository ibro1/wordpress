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
	/*
	 * Prefer the container-to-container address when one is configured.
	 *
	 * The public hostname is behind Cloudflare, which cuts any proxied request
	 * at 100 seconds - so a WordPress -> API call for a 109-second image render
	 * came back as HTTP 524 even though the image had been generated and
	 * returned successfully. Both containers sit on the same Docker network;
	 * that hop has no business leaving the machine.
	 *
	 * Set WOOKIEE_API_INTERNAL_URL in wp-config (the compose file does this
	 * from an env var). Unset, everything behaves exactly as before.
	 */
	if ( defined( 'WOOKIEE_API_INTERNAL_URL' ) && WOOKIEE_API_INTERNAL_URL ) {
		return rtrim( (string) WOOKIEE_API_INTERNAL_URL, '/' );
	}

	return wookiee_central_api_public_url();
}

/**
 * The internet-facing address, and the fallback whenever the internal one
 * cannot be reached.
 */
function wookiee_central_api_public_url() {
	/*
	 * This was hardcoded to api.davebukartechnologies.com, which has since
	 * expired - so the fallback that exists to rescue a failed internal hop
	 * pointed at a domain that no longer resolves, and every retry was
	 * guaranteed to fail. It also meant moving the network to a new domain
	 * silently broke the backup route while leaving the primary one working,
	 * which is the hardest kind of fault to notice.
	 *
	 * An explicit constant wins if one is set. Otherwise it is derived from
	 * the NETWORK's domain, which is the right source: the API's Traefik
	 * router is Host(`api.${MAIN_DOMAIN}`), and MAIN_DOMAIN is by definition
	 * the network's main site. Deriving from home_url() instead would break
	 * on a mapped custom domain, where the shop answers on its own hostname
	 * but the API does not.
	 */
	if ( defined( 'WOOKIEE_API_PUBLIC_URL' ) && WOOKIEE_API_PUBLIC_URL ) {
		return rtrim( (string) WOOKIEE_API_PUBLIC_URL, '/' );
	}

	if ( function_exists( 'get_network' ) ) {
		$network = get_network();
		if ( $network && ! empty( $network->domain ) ) {
			return 'https://api.' . ltrim( (string) $network->domain, '.' );
		}
	}

	return 'https://api.' . (string) wp_parse_url( home_url(), PHP_URL_HOST );
}

function wookiee_central_api_shared_secret() {
	return (string) get_option( 'wookiee_setting_wookiee_api_shared_secret', '' );
}

function wookiee_central_api_configured() {
	return '' !== wookiee_central_api_base_url() && '' !== trim( wookiee_central_api_shared_secret() );
}

/**
 * Whether the backend will actually accept this site - not merely whether a
 * code has been typed in.
 *
 * wookiee_central_api_configured() only checks that a string is stored. It
 * cannot tell an activated site from one whose code is bound to a hostname it
 * no longer answers on, and it reported "activated" throughout a domain move
 * while every single request was being refused. The status a person reads
 * has to come from the party doing the refusing.
 *
 * Cached for ten minutes because this renders on admin screens: long enough
 * that page loads do not each cost a round trip, short enough that fixing a
 * licence shows up without hunting for a way to clear it. Saving a code
 * clears it immediately, which covers the case people actually hit.
 *
 * A backend that cannot be reached returns null rather than false - "I could
 * not ask" is not the same as "you are not activated", and showing the second
 * when the first is true sends people off fixing a licence that was fine.
 *
 * @return array{valid: bool|null, reason: string}
 */
function wookiee_central_api_activation_status( $force = false ) {
	$code = trim( wookiee_central_api_shared_secret() );
	if ( '' === $code ) {
		return array( 'valid' => false, 'reason' => 'No activation code has been entered yet.' );
	}

	// Keyed on code + domain: both are what the answer depends on, so a
	// re-bound licence or a moved site cannot be answered from a stale entry.
	$key = 'wookiee_activation_' . md5( $code . '|' . wookiee_current_site_domain() );

	if ( ! $force ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_post(
		wookiee_central_api_base_url() . '/licenses/verify',
		array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'code' => $code, 'domain' => wookiee_current_site_domain() ) ),
			'timeout' => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'valid' => null, 'reason' => 'Could not reach the backend to check: ' . $response->get_error_message() );
	}

	$data   = json_decode( wp_remote_retrieve_body( $response ), true );
	$status = array(
		'valid'  => is_array( $data ) && ! empty( $data['valid'] ),
		'reason' => is_array( $data ) && isset( $data['reason'] ) ? (string) $data['reason'] : '',
	);

	set_transient( $key, $status, 10 * MINUTE_IN_SECONDS );

	return $status;
}

/** Drop the cached answer - called whenever a code is saved. */
function wookiee_central_api_forget_activation_status() {
	$code = trim( wookiee_central_api_shared_secret() );
	if ( '' !== $code ) {
		delete_transient( 'wookiee_activation_' . md5( $code . '|' . wookiee_current_site_domain() ) );
	}
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
/**
 * @param int $timeout Seconds to wait. The 30s default suits the text and
 *                     lookup endpoints; image generation regularly runs past
 *                     a minute, and cutting it off there reports a failure
 *                     for a request the provider is about to complete (and
 *                     bill for), so those callers pass their own.
 */
function wookiee_central_api_request( $method, $path, $body = null, $timeout = 30 ) {
	if ( ! wookiee_central_api_configured() ) {
		return new WP_Error( 'wookiee_central_api_not_configured', 'The central backend is not connected yet (Settings > Activation).' );
	}

	$args = array(
		'method'  => $method,
		'headers' => array(
			'X-Api-Key'     => wookiee_central_api_shared_secret(),
			'X-Site-Domain' => wookiee_current_site_domain(),
		),
		'timeout' => (int) $timeout,
	);

	if ( null !== $body ) {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body']                    = wp_json_encode( $body );
	}

	$response = wp_remote_request( wookiee_central_api_base_url() . $path, $args );

	/*
	 * A WP_Error here is a connection-level failure - DNS, refused, no route -
	 * not an API error, which comes back as an HTTP status. If the internal
	 * address is the one that failed, the container name is probably wrong or
	 * the API is on a different network, so try the public route once rather
	 * than taking the whole site's AI features down over a misconfiguration.
	 */
	if ( is_wp_error( $response ) && wookiee_central_api_base_url() !== wookiee_central_api_public_url() ) {
		$response = wp_remote_request( wookiee_central_api_public_url() . $path, $args );
	}

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
 * The AI models this site's activation code is allowed to use, as
 * value => label pairs for a settings dropdown.
 *
 * The backend decides this per activation code, so the list is whatever the
 * operator granted this customer - and it only ever contains models whose
 * provider actually has a key saved centrally, so anything offered here will
 * really work. Cached in a transient because it's read on every Wookiee
 * Settings page render and changes only when the operator edits the licence.
 *
 * Returns an empty array on any failure (not activated, backend down, older
 * backend without the endpoint) - callers fall back to letting the backend
 * pick, which is the pre-existing behaviour.
 */
function wookiee_central_api_models( $force_refresh = false ) {
	return wookiee_central_api_model_list( '/llm/models', 'wookiee_llm_models_', $force_refresh );
}

/**
 * The image-model equivalent. Separate endpoint and separate cache: the two
 * catalogues have different providers and different keys, so a licence may
 * well be granted text models and no image models, or the reverse.
 */
function wookiee_central_api_image_models( $force_refresh = false ) {
	return wookiee_central_api_model_list( '/images/models', 'wookiee_image_models_', $force_refresh );
}

/**
 * Shared fetch+cache for both catalogues.
 *
 * @param string $path         Backend endpoint.
 * @param string $cache_prefix Transient prefix; the theme version is appended.
 * @param bool   $force_refresh Bypass the cache.
 */
function wookiee_central_api_model_list( $path, $cache_prefix, $force_refresh = false ) {
	if ( ! wookiee_central_api_configured() ) {
		return array();
	}

	/*
	 * Keyed by theme version so a theme update always invalidates it. The
	 * shape and labelling of this data is decided by theme code, so a
	 * release that changes either would otherwise keep serving the old
	 * format from cache for up to an hour after deploying - which is
	 * exactly what happened when provider names were removed from labels.
	 */
	$cache_key = $cache_prefix . WOOKIEE_VERSION;

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$result = wookiee_central_api_request( 'GET', $path );
	if ( is_wp_error( $result ) || ! isset( $result['models'] ) || ! is_array( $result['models'] ) ) {
		// Cache the empty result briefly too, so a down backend doesn't mean
		// an HTTP round trip on every single admin page load.
		set_transient( $cache_key, array(), 5 * MINUTE_IN_SECONDS );
		return array();
	}

	$models = array();
	foreach ( $result['models'] as $model ) {
		if ( empty( $model['id'] ) ) {
			continue;
		}
		/*
		 * Model name only. Which upstream provider serves it, and what it
		 * costs, are the operator's business - the backend already strips
		 * both from this endpoint, and nothing here should try to
		 * reconstruct them for display on a customer's screen.
		 */
		$models[ (string) $model['id'] ] = ! empty( $model['label'] ) ? $model['label'] : $model['id'];
	}

	/*
	 * An empty-but-successful response is cached only briefly. It usually
	 * means the operator simply hasn't finished configuring the backend yet
	 * (no provider key saved, or no models granted to this code) - caching
	 * that for an hour left sites stuck on "Automatic" long after the
	 * operator had fixed it, with no way to tell why.
	 */
	set_transient( $cache_key, $models, $models ? HOUR_IN_SECONDS : 2 * MINUTE_IN_SECONDS );

	return $models;
}

/**
 * Manual "check again" for the model list, so a site owner is never stuck
 * waiting out a cache after the operator changes what their code may use.
 * Redirects straight back to the Activation tab.
 */
add_action( 'admin_post_wookiee_refresh_models', 'wookiee_refresh_models_handler' );
function wookiee_refresh_models_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}
	check_admin_referer( 'wookiee_refresh_models' );

	// Both catalogues: the button says "check again for models", and an
	// operator who has just been granted image models would otherwise have to
	// wait out a separate cache with nothing telling them to.
	wookiee_central_api_models( true );
	wookiee_central_api_image_models( true );

	/*
	 * Return to whichever screen the link was clicked on. Allowlisted rather
	 * than echoed back: an arbitrary page slug from the query string is a
	 * redirect someone else gets to choose.
	 */
	$allowed = array( 'wookiee-settings', 'wookiee-rebrand' );
	$return  = isset( $_GET['return'] ) ? sanitize_key( wp_unslash( $_GET['return'] ) ) : '';
	$page    = in_array( $return, $allowed, true ) ? $return : 'wookiee-settings';

	$url = admin_url( 'admin.php?page=' . $page . '&wookiee_models_refreshed=1' );
	if ( 'wookiee-settings' === $page ) {
		$url .= '#integrations';
	}

	wp_safe_redirect( $url );
	exit;
}

/**
 * The "check again" link, for anywhere a model list is shown.
 *
 * Both catalogues are cached for an hour, so a licence that gains models
 * shows a stale list until it expires - with nothing on screen explaining
 * why. This lived only under the text model field, which is not where an
 * operator looks when the IMAGE list is wrong.
 *
 * Returns markup rather than echoing so callers can place it inside their
 * own <p class="description">.
 */
function wookiee_refresh_models_link( $return_page = 'wookiee-settings' ) {
	if ( ! wookiee_central_api_configured() ) {
		return '';
	}

	$url = wp_nonce_url(
		admin_url( 'admin-post.php?action=wookiee_refresh_models&return=' . rawurlencode( $return_page ) ),
		'wookiee_refresh_models'
	);

	return '<a href="' . esc_url( $url ) . '">Check for new models</a>';
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

	// The stored answer is keyed on the old code; keeping it would show the
	// previous verdict against the new one.
	wookiee_central_api_forget_activation_status();

	// The allowed-model list is per activation code, so a newly entered code
	// almost certainly grants a different set - drop the cache rather than
	// show the previous code's models for up to an hour.
	delete_transient( 'wookiee_llm_models_' . WOOKIEE_VERSION );

	wp_send_json_success();
}
