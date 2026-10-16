<?php
/**
 * Outgoing mail through ZeptoMail's SMTP relay.
 *
 * The container has no sendmail, so wp_mail() fails silently unless this is
 * configured. Inert until DBT_ZEPTOMAIL_TOKEN (a ZeptoMail "Send Mail" token)
 * is set as a wp-config constant or an environment variable; DBT_MAIL_FROM
 * must be an address on a domain verified in ZeptoMail. ZeptoMail is on the
 * .com datacentre, so the host defaults to smtp.zeptomail.com.
 */

defined( 'ABSPATH' ) || exit;

function dbt_mail_setting( $name, $fallback = '' ) {
	if ( defined( $name ) ) {
		return (string) constant( $name );
	}
	$env = getenv( $name );
	return false === $env || '' === trim( $env ) ? $fallback : trim( (string) $env );
}

/** Whether outgoing mail is set up; without it nothing is attempted. */
function dbt_mail_ready() {
	return '' !== dbt_mail_setting( 'DBT_ZEPTOMAIL_TOKEN' );
}

add_action( 'phpmailer_init', 'dbt_mail_smtp' );
function dbt_mail_smtp( $phpmailer ) {
	if ( ! dbt_mail_ready() ) {
		return;
	}
	$phpmailer->isSMTP();
	$phpmailer->Host       = dbt_mail_setting( 'DBT_SMTP_HOST', 'smtp.zeptomail.com' );
	$phpmailer->Port       = 587;
	$phpmailer->SMTPSecure = 'tls';
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = 'emailapikey';
	$phpmailer->Password   = dbt_mail_setting( 'DBT_ZEPTOMAIL_TOKEN' );
	$phpmailer->Timeout    = 10;
}

add_filter( 'wp_mail_from', 'dbt_mail_from' );
function dbt_mail_from( $from ) {
	return dbt_mail_ready() ? dbt_mail_setting( 'DBT_MAIL_FROM', 'noreply@linkfa.de' ) : $from;
}

add_filter( 'wp_mail_from_name', 'dbt_mail_from_name' );
function dbt_mail_from_name( $name ) {
	return dbt_mail_ready() ? 'Linkfa' : $name;
}

/** Record a failed send on the lead, so a silent failure shows in wp-admin. */
add_action( 'wp_mail_failed', 'dbt_mail_failed' );
function dbt_mail_failed( $error ) {
	if ( is_wp_error( $error ) ) {
		error_log( 'Linkfa mail failed: ' . $error->get_error_message() );
	}
}

/**
 * The one confirmation a visitor gets: what we received and when we reply.
 * Sent only when mail is set up; replies go to the owner's inbox.
 */
function dbt_send_confirmation( $post_id, $name, $email, $service, $message, $reply_promise ) {
	if ( ! dbt_mail_ready() ) {
		return;
	}
	$first = trim( strtok( $name, ' ' ) );
	$body  = "Hi {$first},\n\n"
		. "Thanks for getting in touch with Linkfa. We have your request and a founder reads every one; you'll hear from us {$reply_promise}.\n\n"
		. "What you sent:\n"
		. "Interested in: {$service}\n"
		. $message . "\n\n"
		. "If anything changes, just reply to this email.\n\n"
		. "Linkfa\nby DaveBukar Technologies\nhttps://linkfa.de\n";
	$sent = wp_mail( $email, 'We got your request · Linkfa', $body, array( sprintf( 'Reply-To: Linkfa <%s>', DBT_CONTACT_EMAIL ) ) );
	update_post_meta( $post_id, 'dbt_lead_confirmed', $sent ? 'sent' : 'failed' );
}
