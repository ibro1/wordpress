<?php
/**
 * The prompts behind every AI feature, in one place, so the operator can
 * review and override them from the central backend.
 *
 * Why the defaults live here rather than in the backend: this is where the
 * code that builds and consumes them lives, so a prompt and the variables it
 * interpolates can never drift apart across a theme release. The backend
 * stores only overrides and is told about these defaults by
 * wookiee_publish_prompt_registry() - it is the editing surface, not the
 * source of truth.
 *
 * Resolution order for any slot: operator override (fetched from the
 * backend, cached) > the default below. A site with no overrides therefore
 * behaves exactly as it did before this feature existed.
 *
 * Placeholders are {{name}} and are substituted by wookiee_get_prompt().
 * Anything listed in 'required' cannot be removed by an override - the
 * backend rejects such an edit, because a policy prompt that stops
 * interpolating the real business details would quietly start producing
 * generic, factually empty pages rather than failing visibly.
 */

defined( 'ABSPATH' ) || exit;

function wookiee_prompt_registry() {
	$policy = array(
		'terms'    => 'Terms & Conditions',
		'privacy'  => 'Privacy Policy',
		'shipping' => 'Shipping Policy',
		'returns'  => 'Returns & Refunds Policy',
		'payment'  => 'Payment Policy',
		'cookies'  => 'Cookie Policy',
	);

	$registry = array();

	/*
	 * One slot per policy type rather than a single shared one: each already
	 * carries its own compliance-specific instructions in code (the returns
	 * page's statutory-vs-voluntary split, the cookie page's consent
	 * mechanism, and so on). Collapsing them into one editable prompt would
	 * either hide those differences from the operator or force them to
	 * re-derive each one by hand.
	 */
	foreach ( $policy as $key => $label ) {
		$registry[ 'policy_' . $key ] = array(
			'label'        => $label . ' page',
			'description'  => 'Drafts the complete ' . $label . ' page from the store niche and the real business details.',
			'placeholders' => array( 'brief' ),
			'required'     => array(),
		);
	}

	$registry['policy_audit'] = array(
		'label'        => 'Policy compliance audit',
		'description'  => 'Reviews an existing policy page against UK consumer law and Google Merchant Center requirements, and reports the issues found.',
		'placeholders' => array( 'title', 'policy_text' ),
		'required'     => array( 'title', 'policy_text' ),
	);

	$registry['policy_fix'] = array(
		'label'        => 'Apply policy audit fixes',
		'description'  => 'Rewrites a policy page to resolve every issue raised by the audit.',
		'placeholders' => array( 'title', 'current_text', 'audit_report' ),
		'required'     => array( 'title', 'current_text', 'audit_report' ),
	);

	$registry['policy_custom'] = array(
		'label'        => 'Policy page from custom instructions',
		'description'  => 'Writes a policy page following the store owner\'s own written instructions.',
		'placeholders' => array( 'title', 'custom_instruction' ),
		'required'     => array( 'title', 'custom_instruction' ),
	);

	return $registry;
}

/**
 * Cached operator overrides, keyed by theme version so a release that
 * changes prompt ids or placeholders can't be served stale data.
 */
function wookiee_prompt_overrides( $force_refresh = false ) {
	if ( ! wookiee_central_api_configured() ) {
		return array();
	}

	$cache_key = 'wookiee_prompt_overrides_' . WOOKIEE_VERSION;

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$result = wookiee_central_api_request( 'GET', '/site-prompts' );
	if ( is_wp_error( $result ) || ! isset( $result['prompts'] ) || ! is_array( $result['prompts'] ) ) {
		set_transient( $cache_key, array(), 5 * MINUTE_IN_SECONDS );
		return array();
	}

	set_transient( $cache_key, $result['prompts'], HOUR_IN_SECONDS );

	return $result['prompts'];
}

/**
 * Publishes this theme's prompt registry (including default text) to the
 * backend, so the operator's editor can show real defaults beside overrides.
 *
 * Rate-limited to once a day per theme version: it only needs to change when
 * the theme does, and doing it on every settings load would put an avoidable
 * HTTP round trip in front of an admin screen.
 */
function wookiee_publish_prompt_registry( $force = false ) {
	if ( ! wookiee_central_api_configured() ) {
		return;
	}

	$flag = 'wookiee_prompts_published_' . WOOKIEE_VERSION;
	if ( ! $force && get_transient( $flag ) ) {
		return;
	}

	$slots = array();
	foreach ( wookiee_prompt_registry() as $id => $slot ) {
		$slots[] = array(
			'id'                    => $id,
			'label'                 => $slot['label'],
			'description'           => $slot['description'],
			'placeholders'          => $slot['placeholders'],
			'required_placeholders' => $slot['required'],
			'default_text'          => wookiee_default_prompt_text( $id ),
		);
	}

	wookiee_central_api_request( 'POST', '/site-prompts/registry', array( 'prompts' => $slots ) );

	set_transient( $flag, 1, DAY_IN_SECONDS );
}

/**
 * Publish the registry whenever an admin loads a Wookiee screen. Cheap after
 * the first call (day-long transient, keyed by theme version), and means the
 * operator's editor is populated without anyone having to remember a sync
 * step after a theme release.
 */
add_action( 'admin_init', 'wookiee_maybe_publish_prompt_registry' );
function wookiee_maybe_publish_prompt_registry() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || 0 !== strpos( (string) $_GET['page'], 'wookiee' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	wookiee_publish_prompt_registry();
}

/**
 * Capture mode: while on, wookiee_maybe_override() ignores overrides and
 * returns the builder's own text. Used to publish real defaults to the
 * backend without hand-transcribing them.
 */
function wookiee_prompt_capture_mode( $set = null ) {
	static $on = false;
	if ( null !== $set ) {
		$on = (bool) $set;
	}
	return $on;
}

/**
 * The single hook every prompt builder routes its finished text through.
 *
 * Deliberately a one-line wrapper at the END of each builder rather than a
 * rewrite of them: the existing prompts are long, legally-sensitive and
 * conditionally assembled, and re-typing them into template literals would
 * risk silently degrading policy generation for no benefit. This way the
 * code that produces a prompt remains the definition of that prompt, and
 * the published default can never drift from what the site actually sends.
 *
 * $default is what the builder produced; $vars are the values it
 * interpolated, re-exposed as {{placeholders}} an override can use.
 */
function wookiee_maybe_override( $id, $default, array $vars = array() ) {
	if ( wookiee_prompt_capture_mode() ) {
		return $default;
	}

	$overrides = wookiee_prompt_overrides();
	if ( ! isset( $overrides[ $id ] ) || '' === trim( (string) $overrides[ $id ] ) ) {
		return $default;
	}

	$text = (string) $overrides[ $id ];
	foreach ( $vars as $name => $value ) {
		$text = str_replace( '{{' . $name . '}}', (string) $value, $text );
	}

	// A stray unresolved {{foo}} reaching the model is noise it will try to
	// interpret - strip rather than pass through.
	return trim( preg_replace( '/\{\{[a-z0-9_]+\}\}/i', '', $text ) );
}

/**
 * The exact default a builder produces, with its variables left as
 * {{placeholders}} - obtained by running the real builder in capture mode
 * with token arguments, so it is always byte-identical to what the site
 * would send with no override in place.
 */
function wookiee_default_prompt_text( $id ) {
	$builders = wookiee_prompt_capture_builders();
	if ( ! isset( $builders[ $id ] ) || ! is_callable( $builders[ $id ] ) ) {
		return '';
	}

	wookiee_prompt_capture_mode( true );
	try {
		$text = (string) call_user_func( $builders[ $id ] );
	} catch ( Throwable $e ) {
		$text = '';
	}
	wookiee_prompt_capture_mode( false );

	return $text;
}

/**
 * How to invoke each builder for capture. Token arguments come back
 * embedded in the returned text, which is what makes the published default
 * a real, editable template.
 */
function wookiee_prompt_capture_builders() {
	return array(
		'policy_terms'    => function () { return wookiee_build_content_prompt( 'terms', '{{brief}}' ); },
		'policy_privacy'  => function () { return wookiee_build_content_prompt( 'privacy', '{{brief}}' ); },
		'policy_shipping' => function () { return wookiee_build_content_prompt( 'shipping', '{{brief}}' ); },
		'policy_returns'  => function () { return wookiee_build_content_prompt( 'returns', '{{brief}}' ); },
		'policy_payment'  => function () { return wookiee_build_content_prompt( 'payment', '{{brief}}' ); },
		'policy_cookies'  => function () { return wookiee_build_content_prompt( 'cookies', '{{brief}}' ); },
		'policy_audit'    => function () { return wookiee_build_policy_audit_prompt( '{{title}}', '{{policy_text}}' ); },
		'policy_fix'      => function () { return wookiee_build_policy_fix_prompt( '{{title}}', '{{current_text}}', '{{audit_report}}' ); },
		'policy_custom'   => function () { return wookiee_build_custom_policy_prompt( '{{title}}', '{{custom_instruction}}' ); },
	);
}
