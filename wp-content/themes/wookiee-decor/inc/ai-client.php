<?php
/**
 * Shared Anthropic API caller, used by both the product generator
 * (inc/product-generator.php) and the page content generator
 * (inc/content-generator.php) so the request/error-handling plumbing
 * exists in exactly one place.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WOOKIEE_AI_MODEL' ) ) {
	define( 'WOOKIEE_AI_MODEL', 'claude-sonnet-5' );
}

/**
 * Sends a single-turn prompt to Claude and returns the raw text reply, or
 * a WP_Error. Callers decide how to parse the text (plain prose, JSON,
 * etc.) since that varies by use case.
 */
function wookiee_call_claude( $prompt, $max_tokens = 2048 ) {
	$api_key = wookiee_get_setting( 'anthropic_api_key' );
	if ( '' === trim( (string) $api_key ) ) {
		return new WP_Error( 'wookiee_ai_no_key', 'Add an Anthropic API key on the Wookiee Settings page first.' );
	}

	$response = wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		array(
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'      => WOOKIEE_AI_MODEL,
				'max_tokens' => $max_tokens,
				'messages'   => array(
					array( 'role' => 'user', 'content' => $prompt ),
				),
			) ),
			'timeout' => 60,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_ai_error', 'Anthropic API error: ' . $msg );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$text = isset( $body['content'][0]['text'] ) ? $body['content'][0]['text'] : '';
	$text = trim( $text );

	if ( '' === $text ) {
		return new WP_Error( 'wookiee_ai_empty', 'Claude returned an empty response.' );
	}

	return $text;
}

/**
 * Strips a leading/trailing markdown code fence if the model added one
 * despite being asked not to - common enough with JSON-only prompts that
 * it's worth handling centrally rather than in every caller.
 */
function wookiee_strip_code_fence( $text ) {
	return preg_replace( '/^```(?:json)?\s*|\s*```$/', '', trim( $text ) );
}
