<?php
/**
 * /talk?ref=<slug> - source-tracking redirect to WhatsApp.
 *
 * Article calls to action point here. The visit is logged as a private
 * dbt_click post (ref, utm source/medium/campaign, date; no IP or user agent
 * is stored), then the visitor is forwarded to the prefilled WhatsApp link.
 * The ref is also appended to the prefilled message, so the source arrives
 * with the lead's first WhatsApp message.
 */

defined( 'ABSPATH' ) || exit;

define( 'DBT_TALK_WA', 'https://wa.me/2348113235992' );
define( 'DBT_TALK_MSG', 'Hi Linkfa, I read your article and I\'d like to talk about a project' );

add_action( 'init', 'dbt_register_click_cpt' );
function dbt_register_click_cpt() {
	register_post_type( 'dbt_click', array(
		'labels'          => array(
			'name'          => 'Talk clicks',
			'singular_name' => 'Talk click',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => 'edit.php?post_type=dbt_lead',
		'supports'        => array( 'title' ),
		'capability_type' => 'page',
	) );
}

function dbt_talk_clean( $key ) {
	$value = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	return substr( preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ), 0, 80 );
}

add_action( 'init', 'dbt_talk_redirect', 1 );
function dbt_talk_redirect() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( 'talk' !== trim( (string) $path, '/' ) ) {
		return;
	}

	$ref = dbt_talk_clean( 'ref' );
	if ( '' === $ref ) {
		$ref = dbt_talk_clean( 'utm_source' );
	}
	$ref = '' !== $ref ? $ref : 'direct';

	// Log at most 20 clicks per visitor per hour, and skip obvious crawlers.
	$ua    = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key   = 'dbt_talk_rl_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count < 20 && ! preg_match( '/bot|crawl|spider|slurp|preview|monitor|curl|wget/i', $ua ) ) {
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		$id = wp_insert_post( array(
			'post_type'   => 'dbt_click',
			'post_status' => 'private',
			'post_title'  => $ref . ' - ' . gmdate( 'Y-m-d H:i' ) . ' UTC',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'dbt_click_ref', $ref );
			foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $k ) {
				update_post_meta( $id, 'dbt_click_' . $k, dbt_talk_clean( $k ) );
			}
		}
	}

	// Channel Manager buttons ask for the free audit instead of naming an article.
	if ( 0 === strpos( $ref, 'channel-manager' ) ) {
		$base = 'Hi Linkfa, I\'d like the free YouTube channel audit';
	} elseif ( 0 === strpos( $ref, 'footer' ) || 0 === strpos( $ref, 'home' ) ) {
		// Site-wide buttons name no article.
		$base = 'Hi Linkfa, I\'d like to talk about a project';
	} else {
		$base = DBT_TALK_MSG;
	}
	$msg  = $base . ( 'direct' !== $ref ? ' (ref: ' . $ref . ')' : '' );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	wp_redirect( DBT_TALK_WA . '?text=' . rawurlencode( $msg ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect
	exit;
}
