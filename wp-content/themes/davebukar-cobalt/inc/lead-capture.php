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
		wp_send_json_success( array( 'message' => 'Thanks — we’ll be in touch.' ) );
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

	$allowed_services = array( 'Software Development', 'DevOps & Infrastructure', 'Online Advertising', 'AI Agents & Bots', 'Not sure yet' );
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
	wp_mail( $recipient, $subject, $body, $headers );

	set_transient( $limit_key, $submissions + 1, 10 * MINUTE_IN_SECONDS );

	wp_send_json_success( array( 'message' => 'Thanks — we’ll be in touch within one business day.' ) );
}
