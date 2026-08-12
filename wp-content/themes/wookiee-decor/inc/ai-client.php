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
/**
 * Per-request model override, set from the model picker on the generator
 * screens (see wookiee_apply_request_model_override()).
 *
 * A request-scoped override rather than a parameter threaded through
 * wookiee_call_llm() deliberately: there are a dozen call sites across the
 * product generator, content generator, policy audit and supplier import,
 * several of them nested, and every one of them would otherwise have to
 * learn about model selection just to pass a string along. The override is
 * only ever read on the request that set it.
 *
 * Pass a string to set, null to read.
 */
function wookiee_llm_model_override( $model = null ) {
	static $override = '';

	if ( null !== $model ) {
		$override = trim( (string) $model );
	}

	return $override;
}

/**
 * Picks up a model chosen in the UI for this one request.
 *
 * Hooked globally rather than per-handler so every wookiee_* AJAX action
 * honours the picker without each one needing its own line of plumbing.
 * No capability/allow-list check here on purpose - the backend enforces the
 * licence's model allowlist itself and 403s anything not permitted, so this
 * can't be used to reach a model the activation code isn't entitled to.
 */
/** The same, for image generation. Separate catalogue, separate override. */
function wookiee_image_model_override( $model = null ) {
	static $override = '';

	if ( null !== $model ) {
		$override = trim( (string) $model );
	}

	return $override;
}

add_action( 'admin_init', 'wookiee_apply_request_model_override' );
function wookiee_apply_request_model_override() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- the action's own handler verifies its nonce; this only selects which model that verified action will use.
	if ( isset( $_POST['wookiee_model'] ) ) {
		wookiee_llm_model_override( sanitize_text_field( wp_unslash( $_POST['wookiee_model'] ) ) );
	}
	if ( isset( $_POST['wookiee_image_model'] ) ) {
		wookiee_image_model_override( sanitize_text_field( wp_unslash( $_POST['wookiee_image_model'] ) ) );
	}
	// phpcs:enable
}

/**
 * Whether text generation can work at all right now.
 *
 * Either the central backend is activated, or the site has its own
 * OpenAI-compatible key. With neither, every generate call returns the same
 * error - so callers that are about to do something destructive first should
 * ask here rather than find out halfway through.
 */
function wookiee_generation_available() {
	if ( wookiee_central_api_configured() ) {
		return true;
	}

	return '' !== trim( (string) wookiee_get_setting( 'llm_api_key' ) );
}

function wookiee_call_llm( $prompt, $max_tokens = 2048 ) {
	/*
	 * A non-streaming generation call can legitimately run past a minute -
	 * some configured providers (routed gateways in particular) are simply
	 * slower than others for the same model name, and a long policy page
	 * can be a few thousand tokens. Left at PHP's usual 30s default, the
	 * request gets killed mid-generation and reports as a network failure
	 * ("could not reach the server") that has nothing to do with
	 * reachability. Suppressed because some hosts disable it entirely.
	 *
	 * The backend now retries several times with backoff on its own before
	 * giving up on a model, and - if a fallback model is configured - tries
	 * that too, so a single call from here can legitimately be many
	 * provider round trips back to back rather than one or two. Individual
	 * attempts have been observed taking upwards of 120s each, so the
	 * ceiling here needs enough room for a full retry+fallback sequence,
	 * not just a couple of attempts. Uncomfortably long, but nothing is
	 * waiting on this specific connection any more (see
	 * inc/background-jobs.php) so there is no user-facing cost to it being
	 * generous.
	 */
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	if ( wookiee_central_api_configured() ) {
		$body = array( 'prompt' => $prompt, 'max_tokens' => $max_tokens );

		// Precedence: the model picked for this request (generator screens) >
		// the site's saved default > nothing, which lets the backend apply
		// this activation code's own default. Omitting it entirely is what
		// every install did before model selection existed, so an untouched
		// site behaves exactly as before.
		$model = wookiee_llm_model_override();
		if ( '' === $model ) {
			$model = trim( (string) wookiee_get_setting( 'llm_model' ) );
		}
		if ( '' !== $model ) {
			$body['model'] = $model;
		}

		$fallback_model = trim( (string) wookiee_get_setting( 'llm_fallback_model' ) );
		if ( '' !== $fallback_model ) {
			$body['fallback_model'] = $fallback_model;
		}

		// Lets the backend post live "attempt 2/4, retrying..." status back
		// onto this job while it's still working, instead of the browser
		// staring at a static message for however long generation takes -
		// see wookiee_update_job_progress_handler() in background-jobs.php.
		// Only present when this call is running inside a background job
		// (i.e. everything on the generator screens); a direct/legacy
		// caller simply gets no progress updates, exactly as before.
		$job_context = function_exists( 'wookiee_current_job_context' ) ? wookiee_current_job_context() : null;
		if ( $job_context ) {
			$body['progress_callback'] = array(
				'url'    => admin_url( 'admin-ajax.php' ),
				'job_id' => $job_context['job_id'],
				'secret' => $job_context['secret'],
			);
		}

		$result = wookiee_central_api_request( 'POST', '/llm/generate', $body, 580 );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return isset( $result['text'] ) ? trim( (string) $result['text'] ) : '';
	}

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
			'timeout' => 240,
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
 * Renders the "Generate with" model picker used on the generator screens,
 * so the model can be switched between runs without going back to Settings.
 *
 * Emits nothing when there's no real choice to make (fewer than two models
 * available) - a dropdown with one entry is noise, and the site still works
 * exactly as before via the licence's assigned default.
 *
 * The accompanying script wraps window.fetch once and appends the current
 * selection to any wookiee_* admin-ajax call. Every generator on this theme
 * posts a FormData to ajaxurl, so one interception covers the product
 * generator, content generator, policy audit/rewrite and setup wizard
 * without each of them having to thread the value through its own payload.
 */
function wookiee_render_model_picker() {
	if ( ! wookiee_central_api_configured() ) {
		return;
	}

	$models = wookiee_central_api_models();
	if ( count( $models ) < 2 ) {
		return;
	}

	$current = trim( (string) wookiee_get_setting( 'llm_model' ) );
	?>
	<div class="wookiee-model-picker" style="margin:12px 0;padding:10px 12px;background:#fff;border:1px solid #dcdcde;border-radius:4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
		<label for="wookiee-model-picker" style="font-weight:600;margin:0;">Generate with</label>
		<select id="wookiee-model-picker" style="min-width:280px;max-width:100%;">
			<option value=""<?php selected( '', $current ); ?>>Automatic (this site's assigned model)</option>
			<?php foreach ( $models as $id => $label ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $current ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description" style="margin:0;">Applies to generations on this page only. The saved default lives in <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ); ?>">Settings &rsaquo; Activation</a>.</span>
	</div>
	<?php
	wookiee_print_model_picker_script();
}

/**
 * Wraps window.fetch once and appends whichever picker is on the page to any
 * wookiee_* admin-ajax call.
 *
 * Handles the text and image pickers together, and is printed by both
 * renderers, because a page may carry either or both - the Rebrand screen
 * generates copy AND photographs, so it has one of each. Guarded so two
 * pickers on one page still only patch fetch once.
 */
function wookiee_print_model_picker_script() {
	?>
	<script>
	( function () {
		if ( window.__wookieeModelFetchPatched ) { return; }
		window.__wookieeModelFetchPatched = true;

		var FIELDS = [
			[ 'wookiee_model', 'wookiee-model-picker' ],
			[ 'wookiee_image_model', 'wookiee-image-model-picker' ]
		];

		var originalFetch = window.fetch;
		window.fetch = function ( input, init ) {
			try {
				if ( init && init.body instanceof FormData ) {
					var action = init.body.get( 'action' );
					if ( action && String( action ).indexOf( 'wookiee_' ) === 0 ) {
						FIELDS.forEach( function ( f ) {
							if ( init.body.has( f[0] ) ) { return; }
							var picker = document.getElementById( f[1] );
							if ( picker && picker.value ) {
								init.body.append( f[0], picker.value );
							}
						} );
					}
				}
			} catch ( e ) {
				// Never let a picker break a generation request - fall
				// through and send exactly what the caller built.
			}
			return originalFetch.apply( this, arguments );
		};
	}() );
	</script>
	<?php
}

/**
 * The image-model equivalent of wookiee_render_model_picker().
 *
 * Same "this page only" semantics, separate catalogue: a licence can be
 * granted text models and no image models, or the reverse, so this emits
 * independently of whether the text picker did.
 */
function wookiee_render_image_model_picker() {
	if ( ! wookiee_central_api_configured() ) {
		return;
	}

	$models = wookiee_central_api_image_models();
	if ( count( $models ) < 2 ) {
		return;
	}

	$current = trim( (string) wookiee_get_setting( 'image_model' ) );
	?>
	<div class="wookiee-model-picker" style="margin:12px 0;padding:10px 12px;background:#fff;border:1px solid #dcdcde;border-radius:4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
		<label for="wookiee-image-model-picker" style="font-weight:600;margin:0;">Generate images with</label>
		<select id="wookiee-image-model-picker" style="min-width:280px;max-width:100%;">
			<option value=""<?php selected( '', $current ); ?>>Automatic (this site's assigned model)</option>
			<?php foreach ( $models as $id => $label ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $current ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description" style="margin:0;">Applies to this page only. The saved default lives in <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings#integrations' ) ); ?>">Settings &rsaquo; Activation</a>.</span>
	</div>
	<?php
	wookiee_print_model_picker_script();
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

/**
 * The spelling rule, in one place.
 *
 * Only the brand-voice block ever asked for British English, so everything it
 * did not cover - product titles, category names, policy text - was free to
 * come back American. The live store ended up with a category called
 * "Personalized Travel Accessories" beside copy about being "organised",
 * which a customer reads as carelessness even without being able to say why.
 */
function wookiee_british_english_rule() {
	return "Write in British English throughout: personalised, organised, customised, colour, favourite, catalogue, jewellery - never the American spellings. This is a UK shop, and one American spelling beside a British one reads as copied in from somewhere else.";
}
