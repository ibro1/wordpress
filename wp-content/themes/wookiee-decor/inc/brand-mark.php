<?php
/**
 * The store's own mark, and the favicon made from it.
 *
 * The logo was a fixed "W" glyph - Wookiee's own mark - shown on every store
 * built with this theme, and it survived every rebrand untouched. Same class
 * of fault as the hardcoded "Wookiee" wordmark on the cart header: the
 * platform's branding standing in for the customer's.
 *
 * Drawn rather than generated. An image model is the wrong tool for a logo -
 * it cannot render a specific letter reliably, and a mark that changes shape
 * every time it is regenerated is not a mark. This derives from things the
 * store already has: the business name's initial, the generated accent
 * colour, and the design's corner style. So it changes on rebrand because
 * those change, and it is identical every time it is drawn in between.
 *
 * An uploaded custom logo always wins - see template-parts/site-logo.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The letter the mark is built around.
 *
 * Prefers the registered business name over the site title: the site title is
 * often still "My Site" on a fresh install, while the business name is a real
 * fact the setup wizard collects.
 */
function wookiee_brand_initial() {
	$sources = array(
		(string) get_option( 'wookiee_setting_business_name', '' ),
		(string) get_bloginfo( 'name' ),
	);

	foreach ( $sources as $source ) {
		// Skip leading articles so "The Camp Kitchen" gives C, not T.
		$name = trim( preg_replace( '/^(the|a|an)\s+/i', '', trim( $source ) ) );
		if ( '' === $name ) {
			continue;
		}
		// mb_substr, or a name starting with a multi-byte character yields a
		// broken half-character.
		$first = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
		if ( preg_match( '/\p{L}/u', $first ) ) {
			return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first, 'UTF-8' ) : strtoupper( $first );
		}
	}

	return 'W';
}

/**
 * Corner radius for the mark, following the design's own corner style so the
 * logo does not read as sharp-edged on a store built entirely from pills.
 *
 * @param int $size Square size of the mark in px.
 */
function wookiee_brand_mark_radius( $size ) {
	$params  = function_exists( 'wookiee_current_design_params' ) ? wookiee_current_design_params() : array();
	$corners = isset( $params['corners'] ) ? $params['corners'] : 'soft';

	$ratios = array( 'sharp' => 0.08, 'soft' => 0.22, 'round' => 0.5 );
	$ratio  = isset( $ratios[ $corners ] ) ? $ratios[ $corners ] : $ratios['soft'];

	return round( $size * $ratio, 1 );
}

/**
 * The mark as SVG: a filled badge in the brand accent with the initial on it.
 *
 * @param int  $size       Square size in px.
 * @param bool $standalone Include the xmlns, needed when used as a favicon or
 *                         standalone file but redundant inline in HTML.
 */
function wookiee_brand_mark_svg( $size = 34, $standalone = false ) {
	$initial = wookiee_brand_initial();
	$radius  = wookiee_brand_mark_radius( $size );

	// Solved against white by the design engine, so white text on it always
	// clears 4.5:1 - the same guarantee the buttons rely on.
	$accent = '#1a1614';
	if ( function_exists( 'wookiee_current_design_params' ) && function_exists( 'wookiee_derive_palette' ) ) {
		/*
		 * wookiee_current_design_params() returns an EMPTY array until a design
		 * has been generated - not a set of defaults. Passing that straight to
		 * wookiee_derive_palette() reads $p['hue'] off nothing, which warns on
		 * every page load of a store that has not been rebranded yet, since the
		 * header logo and the favicon both come through here.
		 */
		$design = wookiee_current_design_params();
		if ( $design ) {
			$tokens = wookiee_derive_palette( $design );
			if ( ! empty( $tokens['wookiee-accent'] ) ) {
				$accent = $tokens['wookiee-accent'];
			}
		}
	}

	$xmlns = $standalone ? ' xmlns="http://www.w3.org/2000/svg"' : '';

	/*
	 * Every placeholder is numbered. Mixing a bare %s with numbered ones is
	 * legal PHP and silently wrong: the numbering counts from argument one
	 * regardless, so %1$d would have resolved to $xmlns rather than $size and
	 * the viewBox would have read "0 0 0 0" - an invisible mark.
	 */
	return sprintf(
		'<svg%1$s viewBox="0 0 %2$d %2$d" width="%2$d" height="%2$d" role="img" aria-hidden="true" focusable="false">'
			. '<rect width="%2$d" height="%2$d" rx="%3$s" fill="%4$s"/>'
			. '<text x="50%%" y="50%%" dy=".07em" text-anchor="middle" dominant-baseline="central" '
			. 'font-family="Outfit, Inter, system-ui, -apple-system, Segoe UI, sans-serif" '
			. 'font-weight="800" font-size="%5$s" fill="#ffffff">%6$s</text>'
			. '</svg>',
		$xmlns,
		(int) $size,
		esc_attr( $radius ),
		esc_attr( $accent ),
		esc_attr( round( $size * 0.62, 1 ) ),
		esc_html( $initial )
	);
}

/**
 * The favicon, as an inline SVG data URI.
 *
 * No file is generated and nothing is stored. Rendering a letter into a PNG
 * needs a bundled TTF and GD text rendering; an SVG needs neither, is sharp
 * at every size, and follows the palette automatically. Modern browsers have
 * supported SVG favicons for years, and one that does not simply shows its
 * default - no worse than the nothing that was there before.
 *
 * Skipped entirely when the site has a real icon set in Settings, since an
 * explicit choice by the operator outranks a generated one.
 */
add_action( 'wp_head', 'wookiee_print_generated_favicon', 5 );
add_action( 'admin_head', 'wookiee_print_generated_favicon', 5 );
function wookiee_print_generated_favicon() {
	if ( has_site_icon() ) {
		return;
	}

	$svg = '<?xml version="1.0" encoding="UTF-8"?>' . wookiee_brand_mark_svg( 64, true );

	printf(
		'<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%s">' . "\n",
		rawurlencode( $svg )
	);
}
