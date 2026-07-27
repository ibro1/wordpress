<?php
/**
 * Parametric design engine.
 *
 * The AI chooses design PARAMETERS - a hue, a chroma level, a density, a
 * column count - and this file derives every actual colour and CSS value
 * from them. The model never emits CSS, and never emits a finished colour
 * either.
 *
 * Why not just let it write the CSS: models reliably produce near-invisible
 * text (a light foreground on a light background), accents that fail
 * contrast under white button labels, and grid values that overflow at
 * common widths. Those faults reach customers rather than testing.
 *
 * Why not a fixed catalogue of presets: with a handful of palettes, every
 * store built on this theme starts looking like every other one, which is
 * the templated feel this was meant to avoid in the first place.
 *
 * So: the hue can be anything on the wheel and the knobs vary continuously,
 * giving effectively unlimited distinct designs - but each derived colour is
 * measured against its own background with the real WCAG contrast formula
 * and darkened until it passes. An unreadable result is arithmetically
 * impossible rather than merely unlikely.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Colour maths
 * ---------------------------------------------------------------------- */

function wookiee_hsl_to_rgb( $h, $s, $l ) {
	$h = fmod( fmod( (float) $h, 360 ) + 360, 360 ) / 360;
	$s = max( 0, min( 1, (float) $s ) );
	$l = max( 0, min( 1, (float) $l ) );

	if ( 0.0 === $s ) {
		$r = $g = $b = $l;
	} else {
		$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
		$p = 2 * $l - $q;

		$hue_to_rgb = function ( $p, $q, $t ) {
			if ( $t < 0 ) { $t += 1; }
			if ( $t > 1 ) { $t -= 1; }
			if ( $t < 1 / 6 ) { return $p + ( $q - $p ) * 6 * $t; }
			if ( $t < 1 / 2 ) { return $q; }
			if ( $t < 2 / 3 ) { return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6; }
			return $p;
		};

		$r = $hue_to_rgb( $p, $q, $h + 1 / 3 );
		$g = $hue_to_rgb( $p, $q, $h );
		$b = $hue_to_rgb( $p, $q, $h - 1 / 3 );
	}

	return array( (int) round( $r * 255 ), (int) round( $g * 255 ), (int) round( $b * 255 ) );
}

function wookiee_hsl_to_hex( $h, $s, $l ) {
	list( $r, $g, $b ) = wookiee_hsl_to_rgb( $h, $s, $l );
	return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

function wookiee_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/**
 * WCAG 2.x relative luminance.
 */
function wookiee_relative_luminance( $hex ) {
	$parts = array();
	foreach ( wookiee_hex_to_rgb( $hex ) as $c ) {
		$c       = $c / 255;
		$parts[] = ( $c <= 0.03928 ) ? ( $c / 12.92 ) : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $parts[0] + 0.7152 * $parts[1] + 0.0722 * $parts[2];
}

/**
 * WCAG contrast ratio between two colours, 1.0 (identical) to 21.0
 * (black on white).
 */
function wookiee_contrast_ratio( $hex_a, $hex_b ) {
	$la = wookiee_relative_luminance( $hex_a );
	$lb = wookiee_relative_luminance( $hex_b );
	$lighter = max( $la, $lb );
	$darker  = min( $la, $lb );
	return ( $lighter + 0.05 ) / ( $darker + 0.05 );
}

/**
 * Returns a colour of the given hue/saturation whose contrast against
 * $against meets $target - found by walking lightness down (or up) in small
 * steps until the measured ratio passes.
 *
 * This is the guarantee the whole feature rests on: whatever hue the model
 * picked, the value that actually ships is verified readable rather than
 * assumed to be.
 */
function wookiee_solve_contrast( $h, $s, $against, $target, $start_l = 0.5, $step = -0.02 ) {
	$l    = $start_l;
	$best = wookiee_hsl_to_hex( $h, $s, $l );

	// 50 steps covers the full 0..1 lightness range at 0.02 - the loop is
	// bounded so a target that is genuinely unreachable (e.g. asking for
	// 7:1 against mid-grey) terminates at the closest achievable value
	// rather than spinning.
	for ( $i = 0; $i < 50; $i++ ) {
		$candidate = wookiee_hsl_to_hex( $h, $s, $l );
		if ( wookiee_contrast_ratio( $candidate, $against ) >= $target ) {
			return $candidate;
		}
		$best = $candidate;
		$l   += $step;
		if ( $l <= 0 || $l >= 1 ) {
			break;
		}
	}

	return $best;
}

/* -------------------------------------------------------------------------
 * Parameters -> palette
 * ---------------------------------------------------------------------- */

/**
 * The design parameters, with the defaults that reproduce the theme's
 * original look. Anything the model omits or gets wrong falls back to these.
 */
function wookiee_design_param_defaults() {
	return array(
		'hue'             => 25,          // 0-360, the brand hue
		'accent_offset'   => 0,           // -60..60, accent hue relative to base
		'chroma'          => 'medium',    // muted | low | medium | high
		'paper'           => 'warm',      // warm | neutral | cool
		'density'         => 'balanced',  // dense | balanced | airy
		'corners'         => 'soft',      // sharp | soft | round
		'elevation'       => 'subtle',    // flat | subtle | strong
		'columns'         => 3,           // 2-4 products per row
		'emphasis'        => 'products',  // products | story
		'heading_scale'   => 1.0,         // 0.85-1.35
		'align'           => 'center',    // center | left
		// The hero is the whole first screen. Earlier versions varied only
		// the product grid and section padding - all of it below the fold -
		// so a regenerated design looked identical until you scrolled.
		'hero'            => 'image-right', // image-right | image-left | centered
		'hero_bg'         => 'page',        // page | white | tint
	);
}

function wookiee_chroma_value( $name ) {
	$map = array( 'muted' => 0.10, 'low' => 0.18, 'medium' => 0.30, 'high' => 0.46 );
	return isset( $map[ $name ] ) ? $map[ $name ] : $map['medium'];
}

/**
 * Derives the full 11-token palette from the parameters.
 *
 * Lightness targets are fixed and light-mode by design; only hue and
 * saturation are free. That is what keeps every generated palette in the
 * same readable family as the theme it is restyling, instead of
 * occasionally producing dark-on-dark.
 */
function wookiee_derive_palette( array $p ) {
	$hue    = (float) $p['hue'];
	$accent = fmod( $hue + (float) $p['accent_offset'] + 360, 360 );
	$chroma = wookiee_chroma_value( $p['chroma'] );

	/*
	 * Page background: the BRAND hue, nudged warm or cool, not a fixed
	 * orange or blue. Pinning paper to a fixed hue meant a green brand with
	 * "warm paper" produced a beige page with no green in it anywhere - the
	 * chosen hue only survived in small accents, so every generated design
	 * looked like the same off-white site.
	 *
	 * Saturation has a floor as well as a ceiling: below roughly 0.12 at
	 * this lightness the tint is invisible on most screens, which is what
	 * made earlier output indistinguishable from the stock theme.
	 */
	$paper_shift = array( 'warm' => 18, 'neutral' => 0, 'cool' => -18 );
	$ph          = $hue + ( isset( $paper_shift[ $p['paper'] ] ) ? $paper_shift[ $p['paper'] ] : 0 );
	$bg          = wookiee_hsl_to_hex( $ph, max( 0.16, min( 0.55, $chroma * 1.1 ) ), 0.94 );

	// Body text must clear 7:1 on the page background (AAA at body size);
	// headings sit a touch darker still.
	$text = wookiee_solve_contrast( $hue, min( 0.30, $chroma * 0.5 ), $bg, 7.0, 0.42 );
	$ink  = wookiee_solve_contrast( $hue, min( 0.35, $chroma * 0.6 ), $bg, 12.0, 0.28 );

	// Secondary text: 4.5:1, the AA floor.
	$muted = wookiee_solve_contrast( $hue, min( 0.22, $chroma * 0.4 ), $bg, 4.5, 0.55 );

	// The accent carries white button text, so it is solved against white,
	// not against the page.
	$accent_hex = wookiee_solve_contrast( $accent, max( 0.35, $chroma ), '#ffffff', 4.5, 0.55 );
	$accent_dk  = wookiee_solve_contrast( $accent, max( 0.35, $chroma ), '#ffffff', 7.0, 0.42 );

	// Borders are decorative - no contrast requirement, just a visible edge.
	$border = wookiee_hsl_to_hex( $ph, max( 0.14, min( 0.40, $chroma * 0.8 ) ), 0.84 );

	$gold = wookiee_solve_contrast( 42, 0.55, '#ffffff', 3.0, 0.48 );

	return array(
		'wookiee-ink'         => $ink,
		'wookiee-ink-hover'   => wookiee_solve_contrast( $hue, min( 0.35, $chroma * 0.6 ), $bg, 9.0, 0.34 ),
		'wookiee-ink-light'   => wookiee_solve_contrast( $hue, min( 0.30, $chroma * 0.5 ), $bg, 6.0, 0.45 ),
		'wookiee-bg'          => $bg,
		'wookiee-white'       => '#ffffff',
		'wookiee-text'        => $text,
		'wookiee-text-muted'  => $muted,
		'wookiee-border'      => $border,
		'wookiee-accent'      => $accent_hex,
		'wookiee-accent-dark' => $accent_dk,
		'wookiee-gold'        => $gold,
	);
}

/**
 * Post-derivation verification. Every pair that carries real text is
 * re-measured here; anything short is reported rather than shipped, which
 * is what makes "guaranteed readable" a checkable claim instead of a hope.
 */
function wookiee_audit_palette_contrast( array $tokens, array $params = array() ) {
	$checks = array(
		array( 'body text on page',      $tokens['wookiee-text'],        $tokens['wookiee-bg'],    7.0 ),
		array( 'headings on page',       $tokens['wookiee-ink'],         $tokens['wookiee-bg'],    7.0 ),
		array( 'muted text on page',     $tokens['wookiee-text-muted'],  $tokens['wookiee-bg'],    4.5 ),
		array( 'body text on cards',     $tokens['wookiee-text'],        '#ffffff',                7.0 ),
		array( 'muted text on cards',    $tokens['wookiee-text-muted'],  '#ffffff',                4.5 ),
		array( 'white text on accent',   '#ffffff',                      $tokens['wookiee-accent'], 4.5 ),
	);

	// The tinted hero is a second background that real text sits on, so it
	// has to be audited too, not just the page.
	if ( $params && isset( $params['hero_bg'] ) && 'tint' === $params['hero_bg'] ) {
		$hero = wookiee_hero_tint( $params );
		$checks[] = array( 'body text on hero band', $tokens['wookiee-text'], $hero, 4.5 );
		$checks[] = array( 'headings on hero band', $tokens['wookiee-ink'], $hero, 4.5 );
	}

	$failures = array();
	foreach ( $checks as $c ) {
		list( $label, $fg, $bg, $min ) = $c;
		$ratio = wookiee_contrast_ratio( $fg, $bg );
		if ( $ratio < $min ) {
			$failures[] = sprintf( '%s: %.2f:1 (needs %.1f:1)', $label, $ratio, $min );
		}
	}
	return $failures;
}

/* -------------------------------------------------------------------------
 * Parameters -> layout CSS
 * ---------------------------------------------------------------------- */

/**
 * A deeper wash of the brand hue for the hero band - darker and more
 * saturated than the page, but still light enough that the same solved text
 * colours remain readable on it (verified alongside the page background in
 * the contrast audit).
 */
function wookiee_hero_tint( array $p ) {
	$chroma      = wookiee_chroma_value( $p['chroma'] );
	$paper_shift = array( 'warm' => 18, 'neutral' => 0, 'cool' => -18 );
	$ph          = (float) $p['hue'] + ( isset( $paper_shift[ $p['paper'] ] ) ? $paper_shift[ $p['paper'] ] : 0 );

	return wookiee_hsl_to_hex( $ph, max( 0.22, min( 0.62, $chroma * 1.35 ) ), 0.88 );
}

function wookiee_derive_layout_css( array $p ) {
	$density = array( 'dense' => 40, 'balanced' => 70, 'airy' => 104 );
	$pad     = isset( $density[ $p['density'] ] ) ? $density[ $p['density'] ] : 70;

	$corners = array( 'sharp' => array( 2, 4 ), 'soft' => array( 14, 20 ), 'round' => array( 24, 30 ) );
	list( $r_md, $r_lg ) = isset( $corners[ $p['corners'] ] ) ? $corners[ $p['corners'] ] : $corners['soft'];

	$elev = array(
		'flat'   => array( 'none', 'none', 'none' ),
		'subtle' => array( '0 2px 8px rgba(0,0,0,.06)', '0 10px 30px rgba(0,0,0,.08)', '0 20px 40px rgba(0,0,0,.10)' ),
		'strong' => array( '0 4px 14px rgba(0,0,0,.09)', '0 16px 40px rgba(0,0,0,.13)', '0 28px 60px rgba(0,0,0,.17)' ),
	);
	list( $s_sm, $s_md, $s_lg ) = isset( $elev[ $p['elevation'] ] ) ? $elev[ $p['elevation'] ] : $elev['subtle'];

	$cols  = max( 2, min( 4, (int) $p['columns'] ) );
	$scale = max( 0.85, min( 1.35, (float) $p['heading_scale'] ) );
	$title = (int) round( 38 * $scale );

	$css  = ':root{';
	$css .= '--radius-md:' . $r_md . 'px;--radius-lg:' . $r_lg . 'px;';
	$css .= '--shadow-sm:' . $s_sm . ';--shadow-md:' . $s_md . ';--shadow-lg:' . $s_lg . ';';
	$css .= '}';

	$css .= '.wookiee-gen .home-section{padding-top:' . $pad . 'px;padding-bottom:' . $pad . 'px;}';
	$css .= '.wookiee-gen .section-title{font-size:' . $title . 'px;}';

	/*
	 * Hero treatment. This is the entire first screen, so it is where a
	 * regenerated design has to be visibly different - varying only the
	 * product grid and section padding (all below the fold) is what made
	 * earlier versions look like nothing had happened.
	 */
	$hero_pad = (int) round( $pad * 0.85 );
	$css .= '.wookiee-gen .hero-section{padding-top:' . $hero_pad . 'px;padding-bottom:' . ( $hero_pad + 8 ) . 'px;}';

	if ( 'white' === $p['hero_bg'] ) {
		$css .= '.wookiee-gen .hero-section{background:var(--wookiee-white);}';
	} elseif ( 'tint' === $p['hero_bg'] ) {
		// A deeper wash of the brand hue than the page itself, so the hero
		// reads as a distinct band rather than merging into the page.
		$tint = wookiee_hero_tint( $p );
		$css .= '.wookiee-gen .hero-section{background:' . $tint . ';}';
	}

	if ( 'centered' === $p['hero'] ) {
		$css .= '@media(min-width:900px){'
			. '.wookiee-gen .hero-grid{grid-template-columns:1fr;text-align:center;gap:36px;max-width:900px;}'
			. '.wookiee-gen .hero-cta-row{justify-content:center;}'
			. '.wookiee-gen .hero-lead{margin-left:auto;margin-right:auto;}'
			. '}';
	} elseif ( 'image-left' === $p['hero'] ) {
		$css .= '@media(min-width:900px){'
			. '.wookiee-gen .hero-text-col{order:2;}'
			. '.wookiee-gen .hero-image-col{order:1;}'
			. '}';
	}

	if ( 'left' === $p['align'] ) {
		$css .= '.wookiee-gen .section-header.text-center{text-align:left;}';
		$css .= '.wookiee-gen .section-header .section-subtitle{margin-left:0;}';
	}

	if ( 'flat' === $p['elevation'] ) {
		$css .= '.wookiee-gen .product-card,.wookiee-gen .collection-card{border:1px solid var(--wookiee-border);}';
	}

	// Structural rules only above 900px - below that the theme's own
	// responsive rules already collapse to one column, and overriding them
	// is how a restyle becomes a broken phone layout.
	$css .= '@media(min-width:900px){';
	$css .= '.wookiee-gen .products-grid{grid-template-columns:repeat(' . $cols . ',1fr);}';
	if ( 'story' === $p['emphasis'] ) {
		/*
		 * Visual reorder only - the DOM order, and therefore reading order
		 * for assistive tech and crawlers, is untouched.
		 *
		 * The hero needs an explicit order of its own. Every flex child
		 * defaults to order 0, so the negative orders below were lifting the
		 * philosophy and how-it-works sections above the HERO, not just above
		 * the product grid - the store opened on two blocks of prose and the
		 * first screen was pushed off it entirely. The hero is pinned ahead
		 * of both.
		 *
		 * Scoped to the front page: .site-main is every template's wrapper,
		 * and turning it into a flex column on the shop, cart and product
		 * pages changes layouts that have no story sections to reorder.
		 */
		$css .= '.wookiee-gen.home .site-main{display:flex;flex-direction:column;}';
		$css .= '.wookiee-gen.home .hero-section{order:-3;}';
		$css .= '.wookiee-gen.home .philosophy-section{order:-2;}';
		$css .= '.wookiee-gen.home .how-it-works{order:-1;}';
	}
	$css .= '}';

	return $css;
}

/* -------------------------------------------------------------------------
 * Storage + rendering
 * ---------------------------------------------------------------------- */

/**
 * Clamps every incoming value to its allowed range/enum. Applied to
 * whatever the model returns, so an out-of-range or invented value becomes
 * the default instead of reaching the CSS.
 */
function wookiee_sanitize_design_params( $raw ) {
	$d   = wookiee_design_param_defaults();
	$out = $d;

	if ( ! is_array( $raw ) ) {
		return $out;
	}

	if ( isset( $raw['hue'] ) && is_numeric( $raw['hue'] ) ) {
		$out['hue'] = fmod( fmod( (float) $raw['hue'], 360 ) + 360, 360 );
	}
	if ( isset( $raw['accent_offset'] ) && is_numeric( $raw['accent_offset'] ) ) {
		$out['accent_offset'] = max( -60, min( 60, (float) $raw['accent_offset'] ) );
	}
	foreach ( array(
		'chroma'    => array( 'muted', 'low', 'medium', 'high' ),
		'paper'     => array( 'warm', 'neutral', 'cool' ),
		'density'   => array( 'dense', 'balanced', 'airy' ),
		'corners'   => array( 'sharp', 'soft', 'round' ),
		'elevation' => array( 'flat', 'subtle', 'strong' ),
		'emphasis'  => array( 'products', 'story' ),
		'align'     => array( 'center', 'left' ),
		'hero'      => array( 'image-right', 'image-left', 'centered' ),
		'hero_bg'   => array( 'page', 'white', 'tint' ),
	) as $key => $allowed ) {
		if ( isset( $raw[ $key ] ) && in_array( (string) $raw[ $key ], $allowed, true ) ) {
			$out[ $key ] = (string) $raw[ $key ];
		}
	}
	if ( isset( $raw['columns'] ) && is_numeric( $raw['columns'] ) ) {
		$out['columns'] = max( 2, min( 4, (int) $raw['columns'] ) );
	}
	if ( isset( $raw['heading_scale'] ) && is_numeric( $raw['heading_scale'] ) ) {
		$out['heading_scale'] = max( 0.85, min( 1.35, (float) $raw['heading_scale'] ) );
	}

	return $out;
}

function wookiee_current_design_params() {
	$stored = get_option( 'wookiee_design_params', array() );
	return is_array( $stored ) && $stored ? wookiee_sanitize_design_params( $stored ) : array();
}

function wookiee_save_design_params( array $params, $note = '' ) {
	$clean = wookiee_sanitize_design_params( $params );

	// Verify before storing. A palette that somehow fails is rejected rather
	// than published - the loop should make this unreachable, but a silent
	// contrast failure on a live store is not something to leave to trust.
	$failures = wookiee_audit_palette_contrast( wookiee_derive_palette( $clean ), $clean );
	if ( $failures ) {
		return new WP_Error( 'wookiee_design_contrast', 'Generated palette failed contrast checks: ' . implode( '; ', $failures ) );
	}

	$previous = get_option( 'wookiee_design_params', array() );
	update_option( 'wookiee_design_params_previous', is_array( $previous ) ? $previous : array() );
	update_option( 'wookiee_design_params', $clean );
	update_option( 'wookiee_design_note', (string) $note );

	return true;
}

function wookiee_revert_design_params() {
	$prev = get_option( 'wookiee_design_params_previous', array() );
	if ( ! is_array( $prev ) || ! $prev ) {
		// No previous generated design means going back to the theme's own
		// stylesheet, which is a legitimate destination.
		delete_option( 'wookiee_design_params' );
		delete_option( 'wookiee_design_note' );
		return true;
	}
	$current = get_option( 'wookiee_design_params', array() );
	update_option( 'wookiee_design_params', $prev );
	update_option( 'wookiee_design_params_previous', is_array( $current ) ? $current : array() );
	return true;
}

add_action( 'wp_head', 'wookiee_print_generated_design_css', 21 );
function wookiee_print_generated_design_css() {
	$params = wookiee_current_design_params();
	if ( ! $params ) {
		return;
	}

	$tokens = wookiee_derive_palette( $params );

	$css = ':root{';
	foreach ( $tokens as $name => $value ) {
		$css .= '--' . $name . ':' . $value . ';';
	}
	$css .= '}';
	$css .= wookiee_derive_layout_css( $params );

	echo "\n<style id=\"wookiee-generated-design\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is computed by the derivation functions above from clamped parameters; none is user-supplied text.
}

add_filter( 'body_class', 'wookiee_generated_design_body_class' );
function wookiee_generated_design_body_class( $classes ) {
	if ( wookiee_current_design_params() ) {
		$classes[] = 'wookiee-gen';
	}
	return $classes;
}

/* -------------------------------------------------------------------------
 * Admin panel
 * ---------------------------------------------------------------------- */

function wookiee_render_design_panel() {
	$params = wookiee_current_design_params();
	$note   = (string) get_option( 'wookiee_design_note', '' );
	$tokens = $params ? wookiee_derive_palette( $params ) : array();
	$audit  = $tokens ? wookiee_audit_palette_contrast( $tokens, $params ) : array();
	?>
	<div class="wookiee-design-panel" style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:16px 18px;margin:0 0 16px;">
		<p class="description" style="margin:0 0 14px;">
			The look of your <strong>storefront</strong> - homepage, shop, product and About pages. The AI designs it from your niche: it chooses a hue anywhere on the colour wheel plus spacing, density and layout, and the theme works out the actual colours from that, checking every text/background pair against WCAG contrast rules before anything goes live. Page <em>wording</em> is separate: see the Homepage Copy and About &amp; Contact Copy tabs.
		</p>

		<?php if ( $params ) : ?>
			<div style="display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap;align-items:center;">
				<?php foreach ( array( 'wookiee-bg' => 'Page', 'wookiee-ink' => 'Headings', 'wookiee-text' => 'Body', 'wookiee-text-muted' => 'Muted', 'wookiee-accent' => 'Accent', 'wookiee-border' => 'Border' ) as $tok => $label ) : ?>
					<span title="<?php echo esc_attr( $label . ': ' . $tokens[ $tok ] ); ?>" style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#50575e;">
						<span data-swatch="<?php echo esc_attr( $tok ); ?>" style="width:22px;height:22px;border-radius:4px;border:1px solid #dcdcde;background:<?php echo esc_attr( $tokens[ $tok ] ); ?>;display:inline-block;"></span>
						<?php echo esc_html( $label ); ?>
					</span>
				<?php endforeach; ?>
			</div>

			<p class="description" style="margin:0 0 10px;">
				<strong>Current design:</strong>
				<span id="wookiee-design-summary">hue <?php echo esc_html( round( $params['hue'] ) ); ?>°,
				<?php echo esc_html( $params['chroma'] ); ?> saturation,
				<?php echo esc_html( $params['paper'] ); ?> paper,
				<?php echo esc_html( $params['density'] ); ?> spacing,
				<?php echo esc_html( $params['corners'] ); ?> corners,
				<?php echo esc_html( $params['elevation'] ); ?> elevation,
				<?php echo esc_html( $params['columns'] ); ?> columns,
				<?php echo esc_html( 'story' === $params['emphasis'] ? 'story first' : 'products first' ); ?>.</span>
				<br><em id="wookiee-design-note"><?php echo esc_html( $note ); ?></em>
			</p>

			<p class="description" style="margin:0 0 12px;">
				<?php if ( $audit ) : ?>
					<span style="color:#a3272a;">Contrast check failed: <?php echo esc_html( implode( '; ', $audit ) ); ?></span>
				<?php else : ?>
					<span style="color:#00622e;">✓ Contrast verified — all text passes WCAG AA or better against its background.</span>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p class="description" style="margin:0 0 12px;">Currently using the theme's original design. Generate one to make this store look like its own.</p>
		<?php endif; ?>

		<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
			<button type="button" class="button button-primary" id="wookiee-design-generate">
				<?php echo $params ? 'Generate a different design' : 'Design my store with AI'; ?>
			</button>
			<?php if ( $params ) : ?>
				<button type="button" class="button" id="wookiee-design-revert">Undo last change</button>
			<?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button">View store</a>
		</div>
		<p id="wookiee-design-status" class="description" style="margin-top:10px;"></p>
	</div>
	<script>
	( function () {
		var NONCE  = '<?php echo esc_js( wp_create_nonce( 'wookiee_design' ) ); ?>';
		var status = document.getElementById( 'wookiee-design-status' );
		var genBtn = document.getElementById( 'wookiee-design-generate' );

		function post( action ) {
			var data = new FormData();
			data.append( 'action', action );
			data.append( 'nonce', NONCE );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function ( r ) { return r.json(); } );
		}

		genBtn.addEventListener( 'click', function () {
			var original = genBtn.textContent;
			genBtn.disabled = true;
			genBtn.textContent = 'Designing…';
			status.textContent = 'Designing your storefront from your niche…';

			post( 'wookiee_generate_design' ).then( function ( res ) {
				genBtn.disabled = false;
				genBtn.textContent = original;
				if ( ! res.success ) {
					status.textContent = ( res.data && res.data.message ) || 'Could not generate a design.';
					return;
				}
				// Update the panel in place from the response, which already
				// carries the derived tokens and parameters - the previous
				// version made you reload just to see what had been saved.
				var t = res.data.tokens || {};
				[ [ 'wookiee-bg', 'Page' ], [ 'wookiee-ink', 'Headings' ], [ 'wookiee-text', 'Body' ],
				  [ 'wookiee-text-muted', 'Muted' ], [ 'wookiee-accent', 'Accent' ], [ 'wookiee-border', 'Border' ] ]
					.forEach( function ( pair ) {
						var sw = document.querySelector( '[data-swatch="' + pair[0] + '"]' );
						if ( sw && t[ pair[0] ] ) {
							sw.style.background = t[ pair[0] ];
							sw.parentNode.title = pair[1] + ': ' + t[ pair[0] ];
						}
					} );

				var p = res.data.params || {};
				var summary = document.getElementById( 'wookiee-design-summary' );
				if ( summary && p.hue !== undefined ) {
					summary.textContent = 'hue ' + Math.round( p.hue ) + '°, ' + p.chroma + ' saturation, ' +
						p.paper + ' paper, ' + p.density + ' spacing, ' + p.corners + ' corners, ' +
						p.elevation + ' elevation, ' + p.columns + ' columns, ' +
						( p.emphasis === 'story' ? 'story first' : 'products first' ) + '.';
				}
				var noteEl = document.getElementById( 'wookiee-design-note' );
				if ( noteEl ) { noteEl.textContent = res.data.reason || ''; }

				status.innerHTML = '';
				status.appendChild( document.createTextNode( 'Saved and live. ' ) );
				var view = document.createElement( 'a' );
				view.className = 'button button-primary';
				view.target = '_blank';
				view.href = '<?php echo esc_js( home_url( '/' ) ); ?>';
				view.textContent = 'View store';
				status.appendChild( view );
			} );
		} );

		var revert = document.getElementById( 'wookiee-design-revert' );
		if ( revert ) {
			revert.addEventListener( 'click', function () {
				status.textContent = 'Reverting…';
				post( 'wookiee_revert_generated_design' ).then( function () { window.location.reload(); } );
			} );
		}
	}() );
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * AI generation
 * ---------------------------------------------------------------------- */

/**
 * Asks for parameters, not colours and not CSS.
 *
 * The model is told the hue wheel is fully open to it precisely so that two
 * stores in different niches don't converge on the same look - but it is
 * never asked for a hex value, because the readable version of that hue is
 * something this code computes, not something a model can reliably judge.
 */
function wookiee_build_design_params_prompt( $brief, $avoid = array() ) {
	$prompt = "You are art-directing the storefront of a UK single-niche ecommerce store.\n\n"
		. "Store niche, in the owner's own words: \"{$brief}\"\n\n";

	/*
	 * Without this, a niche with an obvious colour association ("outdoor
	 * cooking" -> green) returns the same hue on every regeneration, so
	 * "generate a different design" produced the same design. Telling it
	 * what is already live, and requiring real distance from it, is what
	 * makes the button mean what it says.
	 */
	if ( ! empty( $avoid['hue'] ) || ( isset( $avoid['hue'] ) && 0 === (int) $avoid['hue'] ) ) {
		$prompt .= "The store currently uses hue {$avoid['hue']} with a {$avoid['hero']} hero. This is a request for a DIFFERENT design: choose a hue at least 60 degrees away from that one, and change the hero treatment and at least two other parameters. The obvious colour for a niche is not the only defensible one - a considered, less literal choice is usually the better brand decision.\n\n";
	}

	$prompt .= ''
		. "Choose design parameters that suit this specific niche and the customer who buys from it.\n\n"
		. "Commit to a real point of view. The middle option on every scale produces a storefront indistinguishable from every other one, which is the outcome to avoid: prefer a definite choice - dense or airy rather than balanced, sharp or round rather than soft, 2 or 4 columns rather than 3 - unless the middle genuinely is right for this niche and you can say why. Two different niches must not end up with the same answer.\n\n"
		. "Respond with exactly these labelled lines, nothing else:\n"
		. "HUE: a number 0-359. The brand's base hue on the colour wheel (0 red, 30 orange, 60 yellow, 120 green, 180 cyan, 210 blue, 270 purple, 330 pink).\n"
		. "ACCENT_OFFSET: a number -60 to 60. How far the accent hue sits from the base. 0 is monochrome and calm; 30+ gives a complementary pop.\n"
		. "CHROMA: muted, low, medium or high. How saturated the whole scheme is.\n"
		. "PAPER: warm, neutral or cool. The undertone of the page background.\n"
		. "DENSITY: dense, balanced or airy. Vertical breathing room.\n"
		. "CORNERS: sharp, soft or round.\n"
		. "ELEVATION: flat, subtle or strong. How much the cards lift off the page.\n"
		. "COLUMNS: 2, 3 or 4 products per row.\n"
		. "EMPHASIS: products or story. 'story' puts the philosophy and how-it-works sections above the product grid.\n"
		. "HEADING_SCALE: a number 0.85-1.35. Section heading size relative to default.\n"
		. "ALIGN: center or left. Section heading alignment.\n"
		. "HERO: image-right, image-left or centered. The hero is the first screen a customer sees - image-right and image-left put the photo on that side, centered stacks the text above it.\n"
		. "HERO_BG: page, white or tint. Whether the hero sits on the page colour, on white, or on a deeper band of the brand hue.\n"
		. "REASON: one sentence, plain English, on why this suits this niche.\n\n"
		. "Do not output colour codes, CSS, or any value outside the ranges above.";

	return wookiee_maybe_override( 'design_params', $prompt, array( 'brief' => $brief ) );
}

/**
 * Generates and applies a design. Everything the model returns passes
 * through wookiee_sanitize_design_params() (clamped to range) and then the
 * contrast audit before it can be stored.
 */
function wookiee_ai_generate_design( $brief ) {
	if ( '' === trim( (string) $brief ) ) {
		return new WP_Error( 'wookiee_design_no_brief', 'Set a niche brief first - the design is generated from it.' );
	}

	$text = wookiee_call_llm( wookiee_build_design_params_prompt( $brief, wookiee_current_design_params() ), 500 );
	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$fields = wookiee_parse_labelled_sections( $text, array(
		'HUE'           => 'hue',
		'ACCENT_OFFSET' => 'accent_offset',
		'CHROMA'        => 'chroma',
		'PAPER'         => 'paper',
		'DENSITY'       => 'density',
		'CORNERS'       => 'corners',
		'ELEVATION'     => 'elevation',
		'COLUMNS'       => 'columns',
		'EMPHASIS'      => 'emphasis',
		'HEADING_SCALE' => 'heading_scale',
		'ALIGN'         => 'align',
		'HERO'          => 'hero',
		'HERO_BG'       => 'hero_bg',
		'REASON'        => 'reason',
	) );

	$reason = trim( (string) $fields['reason'] );
	unset( $fields['reason'] );

	foreach ( $fields as $k => $v ) {
		$fields[ $k ] = strtolower( trim( (string) $v ) );
	}

	$saved = wookiee_save_design_params( $fields, $reason );
	if ( is_wp_error( $saved ) ) {
		return $saved;
	}

	$params = wookiee_current_design_params();
	$tokens = wookiee_derive_palette( $params );

	return array(
		'params' => $params,
		'tokens' => $tokens,
		'reason' => $reason,
	);
}

add_action( 'wp_ajax_wookiee_generate_design', 'wookiee_generate_design_handler' );
function wookiee_generate_design_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_design', 'nonce' );

	$result = wookiee_ai_generate_design( get_option( 'wookiee_niche_brief', '' ) );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}
	wp_send_json_success( $result );
}

add_action( 'wp_ajax_wookiee_revert_generated_design', 'wookiee_revert_generated_design_handler' );
function wookiee_revert_generated_design_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_design', 'nonce' );

	wookiee_revert_design_params();
	wp_send_json_success();
}
