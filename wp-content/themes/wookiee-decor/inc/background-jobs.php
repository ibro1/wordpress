<?php
/**
 * Fire-and-poll background jobs for anything that calls an LLM and might
 * run past about a minute - which policy-page generation and compliance
 * audits both routinely do.
 *
 * This site runs behind Cloudflare (see WOOKIEE_VERSION's comment in
 * functions.php), whose proxy enforces its own ~100 second timeout on any
 * request between a browser and this server - completely independent of
 * PHP's max_execution_time, wp_remote_request's own timeout, or anything
 * else configured in ai-client.php. No amount of raising those helps: a
 * generation that legitimately takes two minutes gets its connection cut
 * by Cloudflare's edge at the 100-second mark regardless, and WordPress
 * never gets the chance to answer - the backend keeps working and
 * eventually succeeds, but into a connection nobody is listening on
 * anymore. That is what "could not reach the server" actually was.
 *
 * The fix is to never hold one HTTP request open for the slow part at
 * all. A "start" handler validates input fast, creates a job record, and
 * fires the real work as a SEPARATE, non-blocking request back to this
 * site's own admin-ajax.php - one with no browser attached to it, so
 * nothing is waiting on that connection for Cloudflare (or anything else)
 * to time out. The original request returns a job id in well under a
 * second. The browser then polls a second, equally fast endpoint every
 * few seconds asking "is it done yet" until it gets a result - each poll
 * is itself a normal fast request, so none of them can hit the same wall
 * the original slow call did.
 */

defined( 'ABSPATH' ) || exit;

/** How long an unclaimed job record survives - generous, but not forever. */
define( 'WOOKIEE_JOB_TTL', 30 * MINUTE_IN_SECONDS );

function wookiee_job_transient_key( $job_id ) {
	return 'wookiee_job_' . $job_id;
}

/**
 * Registers what a job "type" string actually runs. Kept as one small
 * lookup table rather than a hook, since every job this theme runs is
 * defined right here in this codebase - there is no third-party need to
 * add a new type without editing this file anyway.
 */
function wookiee_job_runners() {
	return array(
		'generate_content'    => 'wookiee_run_generate_content_job',
		'audit_policy'         => 'wookiee_run_audit_policy_job',
		'apply_audit_fixes'    => 'wookiee_run_apply_audit_fixes_job',
		'apply_custom_prompt'  => 'wookiee_run_apply_custom_prompt_job',
	);
}

/**
 * Creates a job record and returns [job_id, secret]. The secret - not the
 * current user's login state - is what authorises the background trigger
 * below to actually run it (see wookiee_run_job_handler): the trigger
 * request is fired server-to-server and carries no browser session, so
 * there is no logged-in user to check `current_user_can` against by the
 * time it arrives.
 */
function wookiee_create_job( $type, $input ) {
	$job_id = wp_generate_uuid4();
	$secret = wp_generate_password( 40, false );

	set_transient( wookiee_job_transient_key( $job_id ), array(
		'type'    => $type,
		'input'   => $input,
		'secret'  => $secret,
		'status'  => 'pending', // pending -> running -> done|error
		'result'  => null,
		'created' => time(),
	), WOOKIEE_JOB_TTL );

	return array( $job_id, $secret );
}

function wookiee_get_job( $job_id ) {
	$job = get_transient( wookiee_job_transient_key( $job_id ) );
	return is_array( $job ) ? $job : null;
}

function wookiee_update_job( $job_id, array $patch ) {
	$job = wookiee_get_job( $job_id );
	if ( ! $job ) {
		return;
	}
	set_transient( wookiee_job_transient_key( $job_id ), array_merge( $job, $patch ), WOOKIEE_JOB_TTL );
}

/**
 * Fires the job's real work as a separate request this function does not
 * wait for. `blocking => false` is what makes this non-blocking: WordPress
 * still actually sends the request and a new PHP process picks it up, but
 * this function returns as soon as the request is handed off, not when it
 * completes - the whole point, since the slow part happens in that other
 * process while this one is free to answer the browser immediately.
 *
 * sslverify is left at the site's own default rather than forced off -
 * this is a request to the site's own admin-ajax.php, wherever that
 * resolves to, and disabling verification unconditionally would be a
 * silent security downgrade for every install, not something worth
 * trading for one edge case.
 */
function wookiee_dispatch_job( $job_id, $secret ) {
	wp_remote_post( admin_url( 'admin-ajax.php' ), array(
		'timeout'   => 0.5,
		'blocking'  => false,
		'body'      => array(
			'action' => 'wookiee_run_job',
			'job_id' => $job_id,
			'secret' => $secret,
		),
	) );
}

/*
 * Registered under wp_ajax_nopriv as well as wp_ajax - the dispatch
 * request above carries no auth cookie, so WordPress has no logged-in
 * user to route it to `wp_ajax_wookiee_run_job` with; without the nopriv
 * hook this would 400 with "Invalid request" and the job would sit at
 * "pending" forever. Security here rests entirely on the per-job secret
 * (see wookiee_create_job) rather than on capability checks, because
 * there is no user session for a capability check to run against.
 */
add_action( 'wp_ajax_wookiee_run_job', 'wookiee_run_job_handler' );
add_action( 'wp_ajax_nopriv_wookiee_run_job', 'wookiee_run_job_handler' );
function wookiee_run_job_handler() {
	$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
	$secret = isset( $_POST['secret'] ) ? sanitize_text_field( wp_unslash( $_POST['secret'] ) ) : '';
	$job    = $job_id ? wookiee_get_job( $job_id ) : null;

	// Wrong secret, unknown job, or already claimed by a prior trigger -
	// say nothing either way. This endpoint has no legitimate caller other
	// than wookiee_dispatch_job(), so there is no error message a real
	// caller needs to see.
	if ( ! $job || ! hash_equals( $job['secret'], $secret ) || 'pending' !== $job['status'] ) {
		wp_die();
	}

	wookiee_update_job( $job_id, array( 'status' => 'running' ) );

	$runners = wookiee_job_runners();
	if ( ! isset( $runners[ $job['type'] ] ) || ! is_callable( $runners[ $job['type'] ] ) ) {
		wookiee_update_job( $job_id, array( 'status' => 'error', 'result' => array( 'message' => 'Unknown job type.' ) ) );
		wp_die();
	}

	try {
		$result = call_user_func( $runners[ $job['type'] ], $job['input'] );
		wookiee_update_job( $job_id, array( 'status' => 'done', 'result' => $result ) );
	} catch ( \Throwable $e ) {
		wookiee_update_job( $job_id, array( 'status' => 'error', 'result' => array( 'message' => $e->getMessage() ) ) );
	}

	wp_die();
}

/**
 * The poll endpoint - a normal, fast, logged-in AJAX call (unlike the
 * trigger above), reused across every job type so the browser only needs
 * one polling routine regardless of what the job actually does.
 */
add_action( 'wp_ajax_wookiee_poll_job', 'wookiee_poll_job_handler' );
function wookiee_poll_job_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_generate_content', 'nonce' );

	$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';
	$job    = $job_id ? wookiee_get_job( $job_id ) : null;

	if ( ! $job ) {
		wp_send_json_error( array( 'message' => 'Job not found or expired.' ) );
	}

	if ( 'done' === $job['status'] ) {
		wp_send_json_success( array( 'status' => 'done', 'result' => $job['result'] ) );
	}
	if ( 'error' === $job['status'] ) {
		wp_send_json_success( array(
			'status'  => 'error',
			'message' => isset( $job['result']['message'] ) ? $job['result']['message'] : 'Failed.',
		) );
	}

	// pending or running - nothing to report yet.
	wp_send_json_success( array( 'status' => $job['status'] ) );
}
