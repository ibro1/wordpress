<?php
/**
 * Shared LLM caller, used by both the product generator
 * (inc/product-generator.php) and the page content generator
 * (inc/content-generator.php) so the request/error-handling plumbing
 * exists in exactly one place.
 *
 * Talks to any OpenAI-compatible Chat Completions endpoint - not tied to
 * one vendor - configured entirely from Wookiee Settings: an API key, a
 * base URL (defaults to OpenAI itself), and a model name. Swapping to a
 * different OpenAI-compatible provider (OpenRouter, Groq, a self-hosted
 * vLLM/llama.cpp server, etc.) is just changing the base URL and model,
 * no code change.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends a single-turn prompt to the configured LLM and returns the raw
 * text reply, or a WP_Error. Callers decide how to parse the text (plain
 * prose, JSON, etc.) since that varies by use case.
 */
function wookiee_call_llm( $prompt, $max_tokens = 2048 ) {
	$api_key = wookiee_get_setting( 'llm_api_key' );
	if ( '' === trim( (string) $api_key ) ) {
		return new WP_Error( 'wookiee_ai_no_key', 'Add an LLM API key on the Wookiee Settings page first.' );
	}

	$base_url = rtrim( (string) wookiee_get_setting( 'llm_base_url' ), '/' );
	$model    = wookiee_get_setting( 'llm_default_model' );

	$response = wp_remote_post(
		$base_url . '/chat/completions',
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'      => $model,
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
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : ( 'HTTP ' . intval( $code ) );
		return new WP_Error( 'wookiee_ai_error', 'LLM API error: ' . $msg );
	}

	$text = isset( $body['choices'][0]['message']['content'] ) ? $body['choices'][0]['message']['content'] : '';
	$text = trim( $text );

	if ( '' === $text ) {
		return new WP_Error( 'wookiee_ai_empty', 'The LLM returned an empty response.' );
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

/**
 * Splits a response into labelled sections, where each section's value
 * can span multiple lines/paragraphs (unlike a simple per-line label
 * match, which only captures one line per label - fine for short single-
 * line fields like a headline, useless for a real multi-paragraph
 * product description). $labels maps LABEL => array key, e.g.
 * array( 'LONG_DESCRIPTION' => 'long_description' ). Expects the model
 * to have put each label on its own line as "LABEL:" followed by that
 * section's content, in the order given.
 */
function wookiee_parse_labelled_sections( $text, array $labels ) {
	$fields  = array_fill_keys( array_values( $labels ), '' );
	$pattern = '/(?:^|\n)(' . implode( '|', array_map( function ( $l ) { return preg_quote( $l, '/' ); }, array_keys( $labels ) ) ) . '):[ \t]*/';

	$parts = preg_split( $pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! $parts ) {
		return $fields;
	}

	$current = null;
	foreach ( $parts as $part ) {
		$trimmed = trim( $part );
		if ( isset( $labels[ $trimmed ] ) ) {
			$current = $labels[ $trimmed ];
			continue;
		}
		if ( null !== $current ) {
			$fields[ $current ] = trim( $part );
			$current = null;
		}
	}

	return $fields;
}
