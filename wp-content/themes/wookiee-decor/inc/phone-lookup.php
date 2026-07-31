<?php
/**
 * Finds a business's own published phone number.
 *
 * Setup asks for a contact number, and an operator setting up a store for a
 * company they have just looked up on Companies House frequently does not have
 * one to hand. This searches the web for the number the business already
 * publishes, so the field can be filled rather than invented.
 *
 * NON-BLOCKING, at every level. The search backend answers "not configured"
 * rather than failing when no Firecrawl key is saved; a failed search, a
 * timeout, an unparseable answer and a genuine "this business publishes no
 * number" all return the same empty string; and nothing in setup waits on it
 * or refuses to continue without it. The worst case is the field stays empty
 * and gets typed by hand, exactly as before this existed.
 *
 * Two stages on purpose: the backend returns search results and nothing more,
 * and the judgement - which of these numbers belongs to THIS business - is
 * made here, where the model selection and per-licence limits already are.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $business Registered business name.
 * @param string $address  Registered address, used to disambiguate common names.
 * @return string A phone number, or '' when none could be established.
 */
function wookiee_lookup_business_phone( $business, $address = '' ) {
	$business = trim( (string) $business );
	if ( '' === $business ) {
		return '';
	}

	if ( ! wookiee_central_api_configured() ) {
		return '';
	}

	// The town is worth more than the full address here: search engines match
	// it, and a full postal address tends to return directory pages for the
	// street rather than the company.
	$locality = '';
	if ( '' !== trim( (string) $address ) ) {
		$parts = array_values( array_filter( array_map( 'trim', preg_split( '/[\n,]+/', $address ) ) ) );
		// Second-to-last line is usually the town; last is usually the country.
		if ( count( $parts ) >= 2 ) {
			$locality = $parts[ count( $parts ) - 2 ];
		}
	}

	$query = $business . ' ' . $locality . ' contact telephone number';

	$search = wookiee_central_api_request(
		'POST',
		'/web/search',
		array( 'query' => trim( $query ), 'limit' => 5 ),
		// Longer than the search alone: the backend scrapes each result now,
		// because meta descriptions almost never carry a phone number.
		60
	);

	if ( is_wp_error( $search ) || empty( $search['results'] ) ) {
		return '';
	}

	$lines = array();
	foreach ( $search['results'] as $result ) {
		$lines[] = '- ' . ( isset( $result['title'] ) ? $result['title'] : '' )
			. ' (' . ( isset( $result['url'] ) ? $result['url'] : '' ) . ")\n  "
			. ( isset( $result['snippet'] ) ? $result['snippet'] : '' );
	}

	$results_block = implode( "\n\n", $lines );

	$answer = wookiee_call_llm( wookiee_build_phone_lookup_prompt( $business, $address, $results_block ), 120 );
	if ( is_wp_error( $answer ) ) {
		return '';
	}

	$number = wookiee_parse_phone_answer( $answer );

	/*
	 * The prompt tells the model the number must appear in the results and that
	 * it must not construct or complete one. Nothing checked that it obeyed,
	 * and a fabricated number is the one failure mode this whole function is
	 * built to avoid - it passes every shape test, looks filled in, and so
	 * never gets read again. A store went live publishing one.
	 *
	 * So verify it rather than trusting it: the digits have to be present in
	 * the text the model was shown. Separators are stripped from the haystack
	 * (numbers are written "020 7946 0000", "(020) 7946-0000" and every
	 * variation in between) while letters are kept, so two unrelated numbers
	 * cannot merge into a match across a sentence boundary.
	 */
	if ( '' !== $number && ! wookiee_phone_appears_in( $number, $results_block ) ) {
		return '';
	}

	return $number;
}

/**
 * True when a candidate number's digits genuinely occur in the source text.
 *
 * Compares on the national portion, since a page may print "020 7946 0000"
 * where the model answered "+44 20 7946 0000" - the leading 44 is a formatting
 * choice the model was explicitly asked to make, not evidence of invention.
 */
function wookiee_phone_appears_in( $number, $haystack ) {
	$digits = preg_replace( '/\D/', '', (string) $number );

	if ( 0 === strpos( $digits, '44' ) ) {
		$digits = substr( $digits, 2 );
	}

	$digits = ltrim( $digits, '0' );

	if ( strlen( $digits ) < 7 ) {
		return false;
	}

	$flat = preg_replace( '/[\s().\-\x{2010}-\x{2015}]+/u', '', (string) $haystack );

	return false !== strpos( $flat, $digits );
}

function wookiee_build_phone_lookup_prompt( $business, $address, $results_block ) {
	$prompt = "Below are web search results. Identify the public contact telephone number for this specific UK business, if the results actually contain it.\n\n"
		. 'Business: ' . $business . "\n"
		. ( '' !== trim( (string) $address ) ? 'Registered address: ' . preg_replace( '/\s*\n\s*/', ', ', trim( $address ) ) . "\n" : '' )
		. "\n--- SEARCH RESULTS ---\n" . $results_block . "\n--- END RESULTS ---\n\n"
		. "Rules:\n"
		. "- Answer with the phone number ONLY, on one line, in international format where possible (e.g. +44 20 7946 0000).\n"
		. "- It must appear in the results above. Do not construct, complete or guess any part of a number.\n"
		. "- It must plainly belong to THIS business, not a directory, a competitor, a trade body or a general enquiries line for some other organisation. Business names repeat; if the results are about a different company with a similar name, that is a NONE.\n"
		. "- Ignore premium-rate, fax and personal mobile numbers where a landline is also present.\n"
		. "- If you cannot establish the number with confidence, answer exactly: NONE\n"
		. "- Output nothing else - no explanation, no label, no punctuation around the number.";

	return wookiee_maybe_override(
		'phone_lookup',
		$prompt,
		array( 'business' => $business, 'address' => $address, 'results' => $results_block )
	);
}

/**
 * Pulls a usable number out of the model's reply.
 *
 * Everything unrecognised becomes '' rather than being shown to the operator:
 * a wrong phone number on a live storefront is worse than an empty field,
 * because nobody checks a field that looks filled in.
 */
function wookiee_parse_phone_answer( $answer ) {
	$answer = trim( wp_strip_all_tags( (string) $answer ) );

	if ( '' === $answer || 0 === stripos( $answer, 'NONE' ) ) {
		return '';
	}

	// First line only - a model that adds a sentence despite being told not to
	// still usually leads with the number.
	$answer = trim( strtok( $answer, "\n" ) );

	if ( ! preg_match( '/\+?[\d][\d\s().-]{7,20}\d/', $answer, $m ) ) {
		return '';
	}

	$number = preg_replace( '/\s+/', ' ', trim( $m[0] ) );

	// A plausible phone number has 7-15 digits (ITU E.164 caps at 15). Outside
	// that it is a date, a company number or a fragment of an address.
	$digits = preg_replace( '/\D/', '', $number );
	if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
		return '';
	}

	if ( ! wookiee_uk_phone_shape_is_valid( $digits ) ) {
		return '';
	}

	return $number;
}

/**
 * Structural check on a UK number, on top of the digit count above.
 *
 * The count check catches a date or a company number but not a number of
 * simply the wrong shape. Ofcom has never issued an 04 or 06 range, and the 05
 * range is only 055, 056 and the withdrawn 0500, so a number outside the
 * allocated openings cannot be anyone's line however plausible its length.
 *
 * Non-UK numbers pass untouched. The theme is UK-facing, but a UK company may
 * perfectly well publish an overseas support line, and rejecting one on a
 * guess about its country would be worse than the problem being fixed.
 */
function wookiee_uk_phone_shape_is_valid( $digits ) {
	if ( 0 === strpos( $digits, '44' ) ) {
		$national = ltrim( substr( $digits, 2 ), '0' );
	} elseif ( 0 === strpos( $digits, '0' ) ) {
		$national = ltrim( $digits, '0' );
	} else {
		return true;
	}

	// UK subscriber numbers run to 9 or 10 digits after the trunk prefix.
	if ( strlen( $national ) < 9 || strlen( $national ) > 10 ) {
		return false;
	}

	return 1 === preg_match( '/^(1|2|3|500|55|56|7|80|84|87|9)/', $national );
}

add_action( 'wp_ajax_wookiee_lookup_phone', 'wookiee_lookup_phone_handler' );
function wookiee_lookup_phone_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_lookup_phone', 'nonce' );

	$business = isset( $_POST['business'] ) ? sanitize_text_field( wp_unslash( $_POST['business'] ) ) : '';
	$address  = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

	if ( '' === trim( $business ) ) {
		wp_send_json_success( array( 'phone' => '', 'message' => 'Fill in the business name first.' ) );
	}

	$phone = wookiee_lookup_business_phone( $business, $address );

	// Success either way - "not found" is an ordinary outcome here, not a
	// failure, and reporting it as an error would make an optional enrichment
	// look like something is broken.
	wp_send_json_success( array(
		'phone'   => $phone,
		'message' => '' !== $phone ? '' : 'No published number found - type one in.',
	) );
}
