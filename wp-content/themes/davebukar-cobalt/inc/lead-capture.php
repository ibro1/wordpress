<?php
/**
 * "Book a call" modal — AJAX handler + storage.
 *
 * Stored as a private CPT (dbt_lead) so submissions show up in wp-admin
 * with zero extra infrastructure - this site has no separate backend
 * service the way wookiee-decor does, so the WordPress database itself
 * is "our backend" here. A notification email is also sent so nothing
 * depends on someone remembering to check wp-admin.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'dbt_register_lead_cpt' );
function dbt_register_lead_cpt() {
	register_post_type( 'dbt_lead', array(
		'labels'          => array(
			'name'          => 'Leads',
			'singular_name' => 'Lead',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_icon'       => 'dashicons-phone',
		'menu_position'   => 25,
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'page',
	) );
}

add_action( 'wp_ajax_dbt_book_call', 'dbt_handle_book_call' );
add_action( 'wp_ajax_nopriv_dbt_book_call', 'dbt_handle_book_call' );
function dbt_handle_book_call() {
	if ( ! check_ajax_referer( 'dbt_book_call', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Your session expired — please reload the page and try again.' ), 403 );
	}

	// Honeypot: real visitors never fill in a field that's hidden from view.
	$honeypot = isset( $_POST['website'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['website'] ) ) ) : '';
	if ( '' !== $honeypot ) {
		wp_send_json_success( array( 'message' => 'We read every one ourselves — expect a reply within one business day.' ) );
	}

	// Basic rate limit per IP: 5 submissions per 10 minutes.
	$ip           = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$limit_key    = 'dbt_lead_rl_' . md5( $ip );
	$submissions  = (int) get_transient( $limit_key );
	if ( $submissions >= 5 ) {
		wp_send_json_error( array( 'message' => 'Too many requests — please try again in a few minutes.' ), 429 );
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$company = isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '';
	$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$allowed_services = array( 'Software Development', 'DevOps & Infrastructure', 'SEO & Online Advertising', 'AI Agents & Bots', 'Not sure yet' );
	if ( '' === $service || ! in_array( $service, $allowed_services, true ) ) {
		$service = 'Not sure yet';
	}

	if ( '' === $name || '' === $email || '' === $message || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Please fill in your name, a valid email, and a short note on what you need.' ), 400 );
	}

	$post_id = wp_insert_post( array(
		'post_type'    => 'dbt_lead',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s (%s)', $name, $email ),
		'post_content' => $message,
	), true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Something went wrong saving your request — please email us directly instead.' ), 500 );
	}

	update_post_meta( $post_id, 'dbt_lead_email', $email );
	update_post_meta( $post_id, 'dbt_lead_company', $company );
	update_post_meta( $post_id, 'dbt_lead_service', $service );

	$recipient = DBT_CONTACT_EMAIL;
	$subject   = sprintf( 'New call request — %s', $name );
	$body      = "Name: {$name}\nEmail: {$email}\n" . ( $company ? "Company: {$company}\n" : '' ) . "Interested in: {$service}\n\nWhat they're building:\n{$message}";
	$headers   = array( sprintf( 'Reply-To: %s <%s>', $name, $email ) );
	$notified = wp_mail( $recipient, $subject, $body, $headers );
	update_post_meta( $post_id, 'dbt_lead_emailed', $notified ? 'sent' : 'failed' );
	dbt_send_confirmation( $post_id, $name, $email, $service, $message, 'within one business day' );

	dbt_push_lead( $post_id, array(
		'name'    => $name,
		'email'   => $email,
		'company' => $company,
		'service' => $service,
		'message' => $message,
	) );

	set_transient( $limit_key, $submissions + 1, 10 * MINUTE_IN_SECONDS );

	wp_send_json_success( array( 'message' => 'We read every one ourselves — expect a reply within one business day.' ) );
}

/**
 * Push a saved lead to the harness as a signed JSON POST.
 *
 * Inert unless DBT_LEAD_WEBHOOK_URL and DBT_LEAD_WEBHOOK_SECRET are defined
 * (wp-config.php constants or environment variables). The dbt_lead post and
 * the email stay the fallback: a failed push only marks the lead, never the
 * visitor's submission.
 *
 * Signature: X-Linkfa-Signature = hex HMAC-SHA256( secret, timestamp . "." . body ),
 * with X-Linkfa-Timestamp (unix seconds) so the receiver can reject replays.
 */
function dbt_lead_webhook_setting( $name ) {
	if ( defined( $name ) ) {
		return (string) constant( $name );
	}
	$env = getenv( $name );
	return false === $env ? '' : (string) $env;
}

function dbt_push_lead( $post_id, array $lead, $source = 'linkfa.de/contact-form', array $extra = array() ) {
	$url    = dbt_lead_webhook_setting( 'DBT_LEAD_WEBHOOK_URL' );
	$secret = dbt_lead_webhook_setting( 'DBT_LEAD_WEBHOOK_SECRET' );
	if ( '' === $url || '' === $secret ) {
		return;
	}

	$body = wp_json_encode( $extra + array(
		'source'     => $source,
		'lead_id'    => (int) $post_id,
		'created_at' => gmdate( 'c' ),
		'name'       => $lead['name'],
		'email'      => $lead['email'],
		'company'    => $lead['company'],
		'service'    => $lead['service'],
		'message'    => $lead['message'],
	) );
	$ts   = (string) time();
	$resp = wp_remote_post( $url, array(
		'timeout'  => 4,
		'blocking' => true,
		'headers'  => array(
			'Content-Type'        => 'application/json',
			'X-Linkfa-Timestamp'  => $ts,
			'X-Linkfa-Signature'  => hash_hmac( 'sha256', $ts . '.' . $body, $secret ),
		),
		'body'     => $body,
	) );

	$ok = ! is_wp_error( $resp ) && (int) wp_remote_retrieve_response_code( $resp ) >= 200 && (int) wp_remote_retrieve_response_code( $resp ) < 300;
	update_post_meta( $post_id, 'dbt_lead_pushed', $ok ? 'ok' : 'failed' );
}

/**
 * "Start a project" intake on /register/. Same storage, email and signed push as
 * the Book-a-call form, with the WhatsApp number, budget range and timeline added.
 */
add_action( 'wp_ajax_dbt_intake', 'dbt_handle_intake' );
add_action( 'wp_ajax_nopriv_dbt_intake', 'dbt_handle_intake' );
function dbt_handle_intake() {
	$done = 'Thanks. We reply within one business day on WhatsApp.';
	if ( ! check_ajax_referer( 'dbt_intake', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Your session expired. Please reload the page and try again.' ), 403 );
	}
	if ( '' !== trim( isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '' ) ) {
		wp_send_json_success( array( 'message' => $done ) );
	}

	$ip          = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$limit_key   = 'dbt_lead_rl_' . md5( $ip );
	$submissions = (int) get_transient( $limit_key );
	if ( $submissions >= 5 ) {
		wp_send_json_error( array( 'message' => 'Too many requests. Please try again in a few minutes.' ), 429 );
	}

	$field = function ( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	};
	$name     = $field( 'name' );
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$whatsapp = preg_replace( '/[^0-9+]/', '', $field( 'whatsapp' ) );
	$need     = $field( 'need' );
	$budget   = $field( 'budget' );
	$timeline = $field( 'timeline' );
	$message  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	$needs     = array( 'Software Development', 'DevOps & Infrastructure', 'SEO & Online Advertising', 'AI Agents & Bots', 'Short clips', 'Not sure yet' );
	$timelines = array( 'As soon as possible', 'Within a month', 'In 1 to 3 months', 'Just exploring' );
	if ( ! in_array( $need, $needs, true ) ) {
		$need = 'Not sure yet';
	}
	if ( ! in_array( $timeline, $timelines, true ) ) {
		$timeline = '';
	}

	if ( '' === $name || '' === $message || ! is_email( $email ) || strlen( preg_replace( '/\D/', '', $whatsapp ) ) < 7 ) {
		wp_send_json_error( array( 'message' => 'Please fill in your name, WhatsApp number, a valid email and what you need.' ), 400 );
	}

	$post_id = wp_insert_post( array(
		'post_type'    => 'dbt_lead',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s (%s)', $name, $email ),
		'post_content' => $message,
	), true );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Something went wrong saving your request. Please message us on WhatsApp instead.' ), 500 );
	}
	update_post_meta( $post_id, 'dbt_lead_email', $email );
	update_post_meta( $post_id, 'dbt_lead_service', $need );
	update_post_meta( $post_id, 'dbt_lead_whatsapp', $whatsapp );
	update_post_meta( $post_id, 'dbt_lead_budget', $budget );
	update_post_meta( $post_id, 'dbt_lead_timeline', $timeline );
	update_post_meta( $post_id, 'dbt_lead_source', 'start-a-project' );

	$body = "Name: {$name}\nWhatsApp: {$whatsapp}\nEmail: {$email}\nNeed: {$need}\nBudget: " . ( $budget ? $budget : 'not given' ) . "\nTimeline: " . ( $timeline ? $timeline : 'not given' ) . "\n\n{$message}";
	$notified = wp_mail( DBT_CONTACT_EMAIL, sprintf( 'New project request: %s', $name ), $body, array( sprintf( 'Reply-To: %s <%s>', $name, $email ) ) );
	update_post_meta( $post_id, 'dbt_lead_emailed', $notified ? 'sent' : 'failed' );
	dbt_send_confirmation( $post_id, $name, $email, $need, $message, 'within one business day on WhatsApp' );

	dbt_push_lead( $post_id, array(
		'name'    => $name,
		'email'   => $email,
		'company' => '',
		'service' => $need,
		'message' => $message,
	), 'linkfa.de/start-a-project', array(
		'whatsapp' => $whatsapp,
		'budget'   => $budget,
		'timeline' => $timeline,
	) );

	set_transient( $limit_key, $submissions + 1, 10 * MINUTE_IN_SECONDS );
	wp_send_json_success( array( 'message' => $done ) );
}
