<?php
/**
 * Curated design presets for AI-driven restyling.
 *
 * The AI SELECTS from these; it never writes CSS. That is the whole design
 * of this feature and the reason it can't break a live storefront.
 *
 * Letting a model emit colours or layout rules directly is the obvious
 * implementation and the wrong one: it produces unreadable text on
 * near-matching backgrounds, collapsed grids, and accessibility failures
 * that nobody notices until a customer does. Every palette below is written
 * by hand with its foreground/background pairs checked, and every layout is
 * a presentation-only variant of markup that already works - so the worst a
 * bad model response can do is pick a combination you don't like, which is
 * one click to undo.
 *
 * This is the same bounded-choice approach Shopify/Squarespace/Webflow use
 * for site styles, for the same reason.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Palettes. Each supplies the full token set main.css expects, so a preset
 * can never leave a variable undefined and fall back to something arbitrary.
 *
 * Contrast: every 'text'/'ink' value is a dark tone on its own light 'bg'
 * and 'white' surface (all pairs comfortably past WCAG AA for body text),
 * and every accent is dark enough to carry white button text.
 */
function wookiee_design_palettes() {
	return array(
		'warm-clay' => array(
			'label'  => 'Warm Clay (default)',
			'mood'   => 'warm, handmade, homely',
			'tokens' => array(
				'wookiee-ink' => '#1a1614', 'wookiee-ink-hover' => '#2e2621', 'wookiee-ink-light' => '#443a32',
				'wookiee-bg' => '#ece2d3', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#332b24',
				'wookiee-text-muted' => '#75695c', 'wookiee-border' => '#ddd0bd',
				'wookiee-accent' => '#c1704a', 'wookiee-accent-dark' => '#a35d3a', 'wookiee-gold' => '#c9972f',
			),
		),
		'sage-linen' => array(
			'label'  => 'Sage Linen',
			'mood'   => 'calm, natural, botanical',
			'tokens' => array(
				'wookiee-ink' => '#1b241d', 'wookiee-ink-hover' => '#2a362c', 'wookiee-ink-light' => '#41503f',
				'wookiee-bg' => '#eaeee4', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#2b352b',
				'wookiee-text-muted' => '#6b7668', 'wookiee-border' => '#cdd6c6',
				'wookiee-accent' => '#5c7a52', 'wookiee-accent-dark' => '#48603f', 'wookiee-gold' => '#b08d3e',
			),
		),
		'ink-stone' => array(
			'label'  => 'Ink & Stone',
			'mood'   => 'minimal, architectural, premium',
			'tokens' => array(
				'wookiee-ink' => '#16181c', 'wookiee-ink-hover' => '#262a30', 'wookiee-ink-light' => '#3c424a',
				'wookiee-bg' => '#f0f0ef', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#24272c',
				'wookiee-text-muted' => '#6c7178', 'wookiee-border' => '#d6d6d4',
				'wookiee-accent' => '#3f5468', 'wookiee-accent-dark' => '#2f4152', 'wookiee-gold' => '#9a8358',
			),
		),
		'deep-plum' => array(
			'label'  => 'Deep Plum',
			'mood'   => 'rich, boutique, considered',
			'tokens' => array(
				'wookiee-ink' => '#1f1520', 'wookiee-ink-hover' => '#31232f', 'wookiee-ink-light' => '#4a3746',
				'wookiee-bg' => '#f2ecef', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#2e2130',
				'wookiee-text-muted' => '#756572', 'wookiee-border' => '#ddccd6',
				'wookiee-accent' => '#7a4361', 'wookiee-accent-dark' => '#5e3149', 'wookiee-gold' => '#b3893c',
			),
		),
		'harbour-blue' => array(
			'label'  => 'Harbour Blue',
			'mood'   => 'crisp, trustworthy, practical',
			'tokens' => array(
				'wookiee-ink' => '#121a22', 'wookiee-ink-hover' => '#1f2b36', 'wookiee-ink-light' => '#354653',
				'wookiee-bg' => '#e9eef2', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#1e2a33',
				'wookiee-text-muted' => '#647380', 'wookiee-border' => '#ccd7e0',
				'wookiee-accent' => '#2f6690', 'wookiee-accent-dark' => '#24506f', 'wookiee-gold' => '#b3893c',
			),
		),
		'terracotta-cream' => array(
			'label'  => 'Terracotta & Cream',
			'mood'   => 'mediterranean, bright, characterful',
			'tokens' => array(
				'wookiee-ink' => '#221812', 'wookiee-ink-hover' => '#33261d', 'wookiee-ink-light' => '#4c3a2d',
				'wookiee-bg' => '#f6eee2', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#33251b',
				'wookiee-text-muted' => '#7d6a58', 'wookiee-border' => '#e3d3bd',
				'wookiee-accent' => '#b4552f', 'wookiee-accent-dark' => '#8f4224', 'wookiee-gold' => '#c08a2e',
			),
		),
		'charcoal-mint' => array(
			'label'  => 'Charcoal & Mint',
			'mood'   => 'modern, clean, contemporary',
			'tokens' => array(
				'wookiee-ink' => '#171c1b', 'wookiee-ink-hover' => '#252d2b', 'wookiee-ink-light' => '#3d4744',
				'wookiee-bg' => '#edf1ef', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#232b29',
				'wookiee-text-muted' => '#68736f', 'wookiee-border' => '#d2dbd7',
				'wookiee-accent' => '#2f7d6a', 'wookiee-accent-dark' => '#236052', 'wookiee-gold' => '#a98c46',
			),
		),
		'oxblood-sand' => array(
			'label'  => 'Oxblood & Sand',
			'mood'   => 'heritage, sturdy, traditional',
			'tokens' => array(
				'wookiee-ink' => '#1e1513', 'wookiee-ink-hover' => '#2f221f', 'wookiee-ink-light' => '#493631',
				'wookiee-bg' => '#f0e8dd', 'wookiee-white' => '#ffffff', 'wookiee-text' => '#2f2320',
				'wookiee-text-muted' => '#786357', 'wookiee-border' => '#dfd0bf',
				'wookiee-accent' => '#8c3b34', 'wookiee-accent-dark' => '#6d2c27', 'wookiee-gold' => '#b58b3a',
			),
		),
	);
}

/**
 * Layout variants. Presentation only - each is a body class that main.css /
 * the injected rules below act on. No markup is generated or rearranged by
 * the model, so a layout can't produce invalid HTML or orphan a section.
 */
function wookiee_design_layouts() {
	return array(
		'classic' => array(
			'label' => 'Classic (default)',
			'mood'  => 'products first, generous spacing, soft corners',
		),
		'editorial' => array(
			'label' => 'Editorial',
			'mood'  => 'story-led - the philosophy/how-it-works sections come before the product grid, flatter cards, tighter corners',
		),
		'compact' => array(
			'label' => 'Compact',
			'mood'  => 'denser spacing, more products visible without scrolling, subtle borders instead of shadows',
		),
		'showcase' => array(
			'label' => 'Showcase',
			'mood'  => 'large imagery, airy spacing, strong elevation on cards, rounded corners',
		),
	);
}

function wookiee_current_palette_id() {
	$id = (string) get_option( 'wookiee_design_palette', 'warm-clay' );
	return isset( wookiee_design_palettes()[ $id ] ) ? $id : 'warm-clay';
}

function wookiee_current_layout_id() {
	$id = (string) get_option( 'wookiee_design_layout', 'classic' );
	return isset( wookiee_design_layouts()[ $id ] ) ? $id : 'classic';
}

/**
 * Applies a preset pair. Returns false for any unknown id rather than
 * storing it - this is the guard that makes a bad or hallucinated model
 * response a no-op instead of a broken site.
 */
function wookiee_apply_design_preset( $palette_id, $layout_id ) {
	$palettes = wookiee_design_palettes();
	$layouts  = wookiee_design_layouts();

	if ( ! isset( $palettes[ $palette_id ] ) || ! isset( $layouts[ $layout_id ] ) ) {
		return false;
	}

	// Remember what was live so a single click can put it back.
	update_option( 'wookiee_design_previous', array(
		'palette' => wookiee_current_palette_id(),
		'layout'  => wookiee_current_layout_id(),
	) );

	update_option( 'wookiee_design_palette', $palette_id );
	update_option( 'wookiee_design_layout', $layout_id );

	return true;
}

function wookiee_revert_design_preset() {
	$previous = get_option( 'wookiee_design_previous', array() );
	if ( empty( $previous['palette'] ) || empty( $previous['layout'] ) ) {
		return false;
	}
	return wookiee_apply_design_preset( $previous['palette'], $previous['layout'] );
}

/**
 * Emits the active palette as a :root override plus the layout rules.
 *
 * Printed after the stylesheet so it wins without !important, and only when
 * the selection differs from the theme's shipped default - a site that has
 * never used this feature gets no extra output at all.
 */
add_action( 'wp_head', 'wookiee_print_design_preset_css', 20 );
function wookiee_print_design_preset_css() {
	$palette_id = wookiee_current_palette_id();
	$layout_id  = wookiee_current_layout_id();

	if ( 'warm-clay' === $palette_id && 'classic' === $layout_id ) {
		return;
	}

	$palettes = wookiee_design_palettes();
	$tokens   = $palettes[ $palette_id ]['tokens'];

	$css = ':root{';
	foreach ( $tokens as $name => $value ) {
		$css .= '--' . $name . ':' . $value . ';';
	}
	$css .= '}';

	$css .= wookiee_design_layout_css( $layout_id );

	echo "\n<style id=\"wookiee-design-preset\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values come from the hardcoded preset tables above, never user input.
}

/**
 * The CSS for each layout variant.
 *
 * Section reordering uses flex `order` on the homepage's existing sibling
 * <section> elements rather than moving markup, so it is purely visual,
 * fully reversible, and leaves the DOM order (and therefore the reading
 * order for assistive tech and crawlers) untouched.
 */
function wookiee_design_layout_css( $layout_id ) {
	// Only ever applied at >=900px. Below that the theme's own responsive
	// rules already collapse everything to one column, and fighting them
	// with layout variants is how a "restyle" turns into a broken phone
	// view - the palette still changes on mobile, the structure doesn't.
	$mq = '@media(min-width:900px){';

	switch ( $layout_id ) {
		case 'editorial':
			// Story before product grid: the two narrative sections move
			// above everything else, and the whole page reads left-aligned
			// and flatter, like a magazine rather than a catalogue.
			return ':root{--radius-md:2px;--radius-lg:4px;'
				. '--shadow-sm:0 1px 2px rgba(0,0,0,.05);--shadow-md:0 2px 6px rgba(0,0,0,.06);--shadow-lg:0 4px 12px rgba(0,0,0,.07);}'
				. $mq
				. '.wookiee-layout-editorial .site-main{display:flex;flex-direction:column;}'
				. '.wookiee-layout-editorial .philosophy-section{order:-2;}'
				. '.wookiee-layout-editorial .how-it-works{order:-1;}'
				. '.wookiee-layout-editorial .section-header.text-center{text-align:left;}'
				. '.wookiee-layout-editorial .section-header .section-subtitle{margin-left:0;}'
				. '.wookiee-layout-editorial .section-title{font-size:46px;letter-spacing:-1.5px;}'
				. '.wookiee-layout-editorial .hero-grid{grid-template-columns:1fr;gap:32px;}'
				. '.wookiee-layout-editorial .products-grid{grid-template-columns:repeat(2,1fr);gap:48px;}'
				. '}';

		case 'compact':
			// More on screen at once: a denser product grid, bordered cards
			// instead of shadows, and much tighter vertical rhythm.
			return ':root{--radius-md:6px;--radius-lg:8px;'
				. '--shadow-sm:none;--shadow-md:none;--shadow-lg:none;}'
				. '.wookiee-layout-compact .home-section{padding-top:38px;padding-bottom:38px;}'
				. '.wookiee-layout-compact .section-header{margin-bottom:24px;}'
				. '.wookiee-layout-compact .section-title{font-size:30px;}'
				. '.wookiee-layout-compact .product-card,.wookiee-layout-compact .collection-card{border:1px solid var(--wookiee-border);}'
				. $mq
				. '.wookiee-layout-compact .products-grid{grid-template-columns:repeat(4,1fr);gap:18px;}'
				. '.wookiee-layout-compact .hero-grid{gap:32px;}'
				. '}';

		case 'showcase':
			// Fewer, bigger things: a two-up product grid, heavy elevation,
			// deep rounding and a lot of air.
			return ':root{--radius-md:24px;--radius-lg:30px;'
				. '--shadow-sm:0 4px 14px rgba(0,0,0,.08);--shadow-md:0 16px 40px rgba(0,0,0,.12);--shadow-lg:0 28px 60px rgba(0,0,0,.16);}'
				. '.wookiee-layout-showcase .home-section{padding-top:104px;padding-bottom:104px;}'
				. '.wookiee-layout-showcase .section-header{margin-bottom:60px;}'
				. '.wookiee-layout-showcase .section-title{font-size:52px;letter-spacing:-1.5px;}'
				. $mq
				. '.wookiee-layout-showcase .products-grid{grid-template-columns:repeat(2,1fr);gap:44px;}'
				. '.wookiee-layout-showcase .hero-grid{grid-template-columns:1fr 1.15fr;gap:72px;}'
				. '}';
	}

	return '';
}

/**
 * Layout class on <body> so the rules above have something to hang off.
 */
add_filter( 'body_class', 'wookiee_design_body_class' );
function wookiee_design_body_class( $classes ) {
	$classes[] = 'wookiee-layout-' . wookiee_current_layout_id();
	return $classes;
}

/**
 * The design panel, rendered on the Content Generator screen.
 *
 * Kept off the content "Generate" button on purpose: tying a restyle to
 * regenerating copy would mean you cannot fix a typo without the whole shop
 * changing colour. It's a separate, deliberate action with an undo.
 */
function wookiee_render_design_panel() {
	$palettes   = wookiee_design_palettes();
	$layouts    = wookiee_design_layouts();
	$palette_id = wookiee_current_palette_id();
	$layout_id  = wookiee_current_layout_id();
	$previous   = get_option( 'wookiee_design_previous', array() );
	?>
	<div class="wookiee-design-panel" style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:16px 18px;margin:0 0 16px;">
		<p class="description" style="margin:0 0 14px;">The colour palette and layout of your <strong>storefront</strong> - homepage, shop, product and About pages. Every option is pre-built and tested; the AI picks between them and never writes CSS, so a restyle cannot break the site. Page <em>wording</em> is separate: see the Homepage Copy and About &amp; Contact Copy tabs.</p>

		<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
			<div>
				<label for="wookiee-palette" style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Palette</label>
				<select id="wookiee-palette">
					<?php foreach ( $palettes as $id => $p ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $palette_id ); ?>><?php echo esc_html( $p['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="wookiee-layout" style="display:block;font-weight:600;font-size:12px;margin-bottom:4px;">Layout</label>
				<select id="wookiee-layout">
					<?php foreach ( $layouts as $id => $l ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, $layout_id ); ?>><?php echo esc_html( $l['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="button" class="button button-primary" id="wookiee-design-apply">Apply</button>
			<button type="button" class="button" id="wookiee-design-shuffle">Let AI choose from my niche</button>
			<?php if ( ! empty( $previous['palette'] ) ) : ?>
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

		function post( action, extra ) {
			var data = new FormData();
			data.append( 'action', action );
			data.append( 'nonce', NONCE );
			Object.keys( extra || {} ).forEach( function ( k ) { data.append( k, extra[ k ] ); } );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
				.then( function ( r ) { return r.json(); } );
		}

		document.getElementById( 'wookiee-design-apply' ).addEventListener( 'click', function () {
			status.textContent = 'Applying…';
			post( 'wookiee_set_design', {
				palette: document.getElementById( 'wookiee-palette' ).value,
				layout: document.getElementById( 'wookiee-layout' ).value
			} ).then( function ( res ) {
				status.textContent = res.success ? 'Applied. Open the store to see it.' : ( res.data && res.data.message ) || 'Could not apply.';
				if ( res.success ) { window.location.reload(); }
			} );
		} );

		var shuffleBtn = document.getElementById( 'wookiee-design-shuffle' );
		shuffleBtn.addEventListener( 'click', function () {
			// Disable + relabel the button itself: the status line sits below
			// the fold on smaller screens, so a text-only "working…" was easy
			// to miss entirely and the change appeared to happen with no
			// feedback at all.
			var original = shuffleBtn.textContent;
			shuffleBtn.disabled = true;
			shuffleBtn.textContent = 'Choosing…';
			status.textContent = 'Asking the AI to pick a palette and layout for your niche…';

			post( 'wookiee_shuffle_design' ).then( function ( res ) {
				shuffleBtn.disabled = false;
				shuffleBtn.textContent = original;

				if ( ! res.success ) {
					status.textContent = ( res.data && res.data.message ) || 'Could not pick a design.';
					return;
				}

				// Show what changed and why, and let the operator trigger the
				// reload - auto-reloading threw away the explanation before
				// it could be read.
				status.innerHTML = '';
				var msg = document.createElement( 'span' );
				msg.textContent = 'Chose ' + res.data.palette_label + ' + ' + res.data.layout_label + ' — ' + res.data.reason + ' ';
				status.appendChild( msg );
				var apply = document.createElement( 'button' );
				apply.type = 'button';
				apply.className = 'button button-primary';
				apply.textContent = 'Reload to see it';
				apply.addEventListener( 'click', function () { window.location.reload(); } );
				status.appendChild( apply );
			} );
		} );

		var revert = document.getElementById( 'wookiee-design-revert' );
		if ( revert ) {
			revert.addEventListener( 'click', function () {
				status.textContent = 'Reverting…';
				post( 'wookiee_revert_design' ).then( function ( res ) {
					if ( res.success ) { window.location.reload(); }
					else { status.textContent = ( res.data && res.data.message ) || 'Nothing to revert to.'; }
				} );
			} );
		}
	}() );
	</script>
	<?php
}

/**
 * Prompt for the design pick. Deliberately asks for two ids and one line of
 * reasoning - nothing that could become markup or CSS.
 */
function wookiee_build_design_pick_prompt( $brief ) {
	$palette_lines = array();
	foreach ( wookiee_design_palettes() as $id => $p ) {
		$palette_lines[] = "- {$id}: {$p['label']} - {$p['mood']}";
	}
	$layout_lines = array();
	foreach ( wookiee_design_layouts() as $id => $l ) {
		$layout_lines[] = "- {$id}: {$l['label']} - {$l['mood']}";
	}

	$prompt = "Choose the visual design for a UK single-niche ecommerce store.\n\n"
		. "Store niche, in the owner's own words: \"{$brief}\"\n\n"
		. "Available colour palettes:\n" . implode( "\n", $palette_lines ) . "\n\n"
		. "Available layouts:\n" . implode( "\n", $layout_lines ) . "\n\n"
		. "Pick the one palette and the one layout that best suit this specific niche and the kind of customer who buys from it. Avoid defaulting to the first option in each list unless it is genuinely the best fit. Do not invent an id that is not listed above.\n\n"
		. "Respond with exactly these three labelled lines, nothing else:\n"
		. "PALETTE: the palette id\n"
		. "LAYOUT: the layout id\n"
		. "REASON: one sentence, plain English, on why this pairing suits this niche";

	return wookiee_maybe_override( 'design_pick', $prompt, array( 'brief' => $brief ) );
}

/**
 * Asks the model to choose a preset, then validates its answer against the
 * catalogues before anything is stored.
 *
 * Both ids must be recognised or nothing is applied - an unparseable or
 * invented answer leaves the live design exactly as it was rather than
 * half-applying a change.
 */
function wookiee_ai_pick_design( $brief ) {
	if ( '' === trim( (string) $brief ) ) {
		return new WP_Error( 'wookiee_design_no_brief', 'Set a niche brief first - the design is chosen from it.' );
	}

	$text = wookiee_call_llm( wookiee_build_design_pick_prompt( $brief ), 300 );
	if ( is_wp_error( $text ) ) {
		return $text;
	}

	$fields = wookiee_parse_labelled_sections( $text, array(
		'PALETTE' => 'palette',
		'LAYOUT'  => 'layout',
		'REASON'  => 'reason',
	) );

	$palette = strtolower( trim( (string) $fields['palette'] ) );
	$layout  = strtolower( trim( (string) $fields['layout'] ) );

	if ( ! isset( wookiee_design_palettes()[ $palette ] ) || ! isset( wookiee_design_layouts()[ $layout ] ) ) {
		return new WP_Error(
			'wookiee_design_bad_pick',
			'The model returned a design that is not in the approved list, so nothing was changed. Try again.'
		);
	}

	if ( ! wookiee_apply_design_preset( $palette, $layout ) ) {
		return new WP_Error( 'wookiee_design_apply_failed', 'Could not save the chosen design.' );
	}

	return array(
		'palette'       => $palette,
		'palette_label' => wookiee_design_palettes()[ $palette ]['label'],
		'layout'        => $layout,
		'layout_label'  => wookiee_design_layouts()[ $layout ]['label'],
		'reason'        => trim( (string) $fields['reason'] ),
	);
}

add_action( 'wp_ajax_wookiee_shuffle_design', 'wookiee_shuffle_design_handler' );
function wookiee_shuffle_design_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_design', 'nonce' );

	$result = wookiee_ai_pick_design( get_option( 'wookiee_niche_brief', '' ) );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success( $result );
}

add_action( 'wp_ajax_wookiee_set_design', 'wookiee_set_design_handler' );
function wookiee_set_design_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_design', 'nonce' );

	$palette = isset( $_POST['palette'] ) ? sanitize_key( wp_unslash( $_POST['palette'] ) ) : '';
	$layout  = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : '';

	if ( ! wookiee_apply_design_preset( $palette, $layout ) ) {
		wp_send_json_error( array( 'message' => 'That palette/layout combination is not recognised.' ) );
	}

	wp_send_json_success();
}

add_action( 'wp_ajax_wookiee_revert_design', 'wookiee_revert_design_handler' );
function wookiee_revert_design_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_design', 'nonce' );

	if ( ! wookiee_revert_design_preset() ) {
		wp_send_json_error( array( 'message' => 'There is no previous design to go back to.' ) );
	}

	wp_send_json_success();
}
