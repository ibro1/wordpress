<?php
/**
 * One-click rebrand.
 *
 * The settings screen grew a tab per concern - Store Design, Homepage Copy,
 * About & Contact Copy, Business Identity - which is a reasonable shape for
 * EDITING one thing, and a bad shape for the job people actually arrive
 * with: "make this store look and read like my business". That needed four
 * screens, in an order nobody could guess, with a different button on each.
 *
 * This page does the whole job from one brief and one button: it generates
 * the visual design, the homepage copy and the About/Contact copy in one
 * pass and applies them together. The tabs remain as the place to adjust an
 * individual field afterwards, which is what they are good at.
 *
 * Everything it writes is captured beforehand so a single Undo puts the
 * whole rebrand back - not just the last step of it.
 */

defined( 'ABSPATH' ) || exit;

// Priority 11: the parent "wookiee-setup" menu is registered by
// inc/admin-menu.php on the default priority 10, and add_submenu_page()
// fails outright against a parent that does not exist yet.
add_action( 'admin_menu', 'wookiee_register_rebrand_page', 11 );
function wookiee_register_rebrand_page() {
	add_submenu_page(
		'wookiee-setup',
		'Rebrand store',
		'Rebrand',
		'manage_options',
		'wookiee-rebrand',
		'wookiee_render_rebrand_page'
	);
}

/**
 * Every option a rebrand touches, so the whole thing can be rolled back as
 * one unit rather than leaving a half-rebranded store if the operator
 * dislikes the result.
 */
function wookiee_rebrand_tracked_options() {
	$keys = array(
		'wookiee_design_params',
		// The design engine keeps its own one-step undo pointer. Restoring
		// wookiee_design_params without it would leave "Undo design" on the
		// Store Design tab pointing at a design that no longer exists.
		'wookiee_design_params_previous',
		'wookiee_design_note',
		'wookiee_niche_brief',
		'wookiee_homepage_ai_generated',
		'wookiee_about_contact_ai_generated',
	);

	// The generated image replaces the slot's attachment id. Undo puts the
	// previous image back; the attachment itself is never deleted, so the
	// restored id still resolves.
	foreach ( array_keys( wookiee_image_slots() ) as $slot ) {
		$keys[] = wookiee_image_option_key( $slot );
	}

	foreach ( array_keys( wookiee_homepage_copy_fields() ) as $field ) {
		$keys[] = 'wookiee_setting_' . $field;
	}
	foreach ( array_keys( wookiee_about_contact_copy_fields() ) as $field ) {
		$keys[] = 'wookiee_setting_' . $field;
	}

	return $keys;
}

function wookiee_snapshot_before_rebrand() {
	$snapshot = array();
	foreach ( wookiee_rebrand_tracked_options() as $key ) {
		// false means "was not set", which restore() honours by deleting -
		// storing '' instead would resurrect the option as empty and make
		// the theme's own default stop applying.
		$snapshot[ $key ] = get_option( $key, false );
	}
	update_option( 'wookiee_rebrand_snapshot', $snapshot );
}

function wookiee_restore_before_rebrand() {
	$snapshot = get_option( 'wookiee_rebrand_snapshot', array() );
	if ( ! is_array( $snapshot ) || ! $snapshot ) {
		return false;
	}

	foreach ( $snapshot as $key => $value ) {
		if ( false === $value ) {
			delete_option( $key );
		} else {
			update_option( $key, $value );
		}
	}

	delete_option( 'wookiee_rebrand_snapshot' );
	return true;
}

/**
 * The rebrand as an ordered list of steps. The browser runs one request per
 * step rather than one request for the lot: each LLM call allows up to 60
 * seconds, so running all three inline risked blowing PHP's max_execution_time
 * and leaving the store half-rebranded behind a spinner that never resolved.
 * One request per step also means the progress shown is real rather than a
 * guess about which step is running.
 */
function wookiee_rebrand_steps() {
	$steps = array( 'design' => 'Colours &amp; layout' );

	foreach ( wookiee_image_slots() as $slot => $meta ) {
		$steps[ 'image_' . $slot ] = $meta['label'] . ' image';
	}

	$steps['homepage'] = 'Homepage copy';
	$steps['about']    = 'About &amp; Contact copy';

	return $steps;
}

/**
 * The image steps, which are opt-in.
 *
 * Generating costs real money per image and takes far longer than the text
 * steps, so a run that only needed the copy adjusted should not silently
 * spend three image generations. Off by default once a store has its own
 * images; on by default while it is still showing bundled demo photos,
 * which is the case where leaving them is clearly wrong.
 */
function wookiee_rebrand_image_steps() {
	return array_map(
		function ( $slot ) {
			return 'image_' . $slot;
		},
		array_keys( wookiee_image_slots() )
	);
}

/**
 * The design parameters in plain English, split into what changed about the
 * colours and what changed about the layout.
 *
 * Both come from a single generation, but reporting them as one line made
 * the layout work invisible: the hero treatment, section rhythm and section
 * ORDER all change on a regenerate, and an operator reading "hue 210, image-
 * left hero, 3 columns" has no way to know the philosophy section just moved
 * above the fold. Saying so is the difference between "nothing happened" and
 * "here is what happened".
 */
function wookiee_describe_design( array $p ) {
	$hue_names = array(
		15 => 'red', 45 => 'amber', 70 => 'olive', 150 => 'green', 190 => 'teal',
		240 => 'blue', 280 => 'indigo', 320 => 'purple', 350 => 'pink', 360 => 'red',
	);
	$hue_name = 'red';
	foreach ( $hue_names as $ceiling => $name ) {
		if ( (float) $p['hue'] < $ceiling ) {
			$hue_name = $name;
			break;
		}
	}

	$colours = sprintf(
		'%s (hue %d&deg;), %s saturation, %s paper',
		$hue_name,
		round( $p['hue'] ),
		$p['chroma'],
		$p['paper']
	);

	$hero_bg = array(
		'page'  => 'on the page background',
		'white' => 'on a white band',
		'tint'  => 'on a tinted brand band',
	);
	$hero = array(
		'image-right' => 'image right',
		'image-left'  => 'image left',
		'centered'    => 'centered, full width',
	);

	$layout = array(
		sprintf(
			'%s hero (%s)',
			isset( $hero[ $p['hero'] ] ) ? $hero[ $p['hero'] ] : $p['hero'],
			isset( $hero_bg[ $p['hero_bg'] ] ) ? $hero_bg[ $p['hero_bg'] ] : $p['hero_bg']
		),
		$p['density'] . ' section spacing',
		$p['corners'] . ' corners',
		'flat' === $p['elevation'] ? 'no card shadows' : $p['elevation'] . ' card shadows',
		(int) $p['columns'] . ' products per row',
		'left' === $p['align'] ? 'left-aligned section headings' : 'centred section headings',
	);

	// The single most visible structural change, and the one most likely to
	// be mistaken for "nothing happened" if it goes unmentioned.
	if ( 'story' === $p['emphasis'] ) {
		$layout[] = '<strong>story order</strong> - the philosophy and how-it-works sections move above the product grid';
	} else {
		$layout[] = '<strong>shop order</strong> - products lead, story sections follow';
	}

	return array(
		'Colours' => $colours,
		'Layout'  => implode( '; ', $layout ),
	);
}

/**
 * Runs one step and applies it. Each step reports its own outcome so a
 * failure doesn't discard the steps that already succeeded - a design that
 * generated fine shouldn't be thrown away because the About copy timed out.
 */
function wookiee_run_rebrand_step( $step, $brief ) {
	if ( '' === trim( (string) $brief ) ) {
		return new WP_Error( 'wookiee_rebrand_no_brief', 'Describe your store in a sentence first.' );
	}

	// The snapshot is taken once, before the first step, so Undo restores
	// the state from before the whole run rather than from mid-run.
	if ( 'design' === $step ) {
		wookiee_snapshot_before_rebrand();
	}

	update_option( 'wookiee_niche_brief', $brief );

	// Image steps are named image_<slot>, so one branch covers every slot and
	// adding a slot needs no change here.
	if ( 0 === strpos( $step, 'image_' ) ) {
		$slot   = substr( $step, strlen( 'image_' ) );
		$result = wookiee_generate_slot_image( $slot, $brief );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return 'new image generated and applied';
	}

	switch ( $step ) {
		case 'design':
			$design = wookiee_ai_generate_design( $brief );
			if ( is_wp_error( $design ) ) {
				return $design;
			}
			return wookiee_describe_design( $design['params'] );

		case 'homepage':
			$text = wookiee_call_llm( wookiee_build_content_prompt( 'homepage_copy', $brief ), 3072 );
			if ( is_wp_error( $text ) ) {
				return $text;
			}
			$n = wookiee_apply_copy_fields( wookiee_parse_copy_fields( $text, wookiee_homepage_copy_fields() ) );
			update_option( 'wookiee_homepage_ai_generated', 1 );
			return $n . ' of ' . count( wookiee_homepage_copy_fields() ) . ' fields rewritten';

		case 'about':
			$text = wookiee_call_llm( wookiee_build_content_prompt( 'about_contact', $brief ), 2048 );
			if ( is_wp_error( $text ) ) {
				return $text;
			}
			$n = wookiee_apply_copy_fields( wookiee_parse_copy_fields( $text, wookiee_about_contact_copy_fields() ) );
			update_option( 'wookiee_about_contact_ai_generated', 1 );
			return $n . ' of ' . count( wookiee_about_contact_copy_fields() ) . ' fields rewritten';
	}

	return new WP_Error( 'wookiee_rebrand_bad_step', 'Unknown rebrand step.' );
}

/**
 * Writes parsed copy straight to the settings options. Blank values are
 * skipped so a field the model omitted keeps whatever was there rather than
 * being wiped.
 */
function wookiee_apply_copy_fields( array $fields ) {
	$count = 0;
	foreach ( $fields as $key => $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			continue;
		}
		update_option( 'wookiee_setting_' . $key, $value );
		$count++;
	}
	return $count;
}

add_action( 'wp_ajax_wookiee_rebrand', 'wookiee_rebrand_handler' );
function wookiee_rebrand_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_rebrand', 'nonce' );

	$brief = isset( $_POST['brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['brief'] ) ) : '';
	$step  = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : '';

	if ( ! array_key_exists( $step, wookiee_rebrand_steps() ) ) {
		wp_send_json_error( array( 'message' => 'Unknown rebrand step.' ) );
	}

	$result = wookiee_run_rebrand_step( $step, $brief );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}
	// A step reports either one line or a set of labelled lines (the design
	// step splits into Colours and Layout so neither hides behind the other).
	if ( is_array( $result ) ) {
		wp_send_json_success( array( 'parts' => $result ) );
	}
	wp_send_json_success( array( 'detail' => $result ) );
}

add_action( 'wp_ajax_wookiee_rebrand_undo', 'wookiee_rebrand_undo_handler' );
function wookiee_rebrand_undo_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	check_ajax_referer( 'wookiee_rebrand', 'nonce' );

	if ( ! wookiee_restore_before_rebrand() ) {
		wp_send_json_error( array( 'message' => 'There is no previous version to restore.' ) );
	}
	wp_send_json_success();
}

function wookiee_render_rebrand_page() {
	$brief    = (string) get_option( 'wookiee_niche_brief', '' );
	$params   = function_exists( 'wookiee_current_design_params' ) ? wookiee_current_design_params() : array();
	$tokens   = $params ? wookiee_derive_palette( $params ) : array();
	$has_undo = (bool) get_option( 'wookiee_rebrand_snapshot', array() );
	?>
	<div class="wrap">
		<h1>Rebrand store</h1>
		<p class="description" style="max-width:760px;font-size:14px;">
			Describe the store in a sentence. This rewrites the <strong>look</strong> (colours, spacing, hero and layout) and the <strong>words</strong> (homepage, About and Contact) together, in one pass, so they match each other. Individual fields can still be adjusted afterwards on <a href="<?php echo esc_url( admin_url( 'admin.php?page=wookiee-settings' ) ); ?>">Settings</a>.
		</p>

		<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:18px;max-width:760px;margin-top:16px;">
			<label for="wookiee-rebrand-brief" style="display:block;font-weight:600;margin-bottom:6px;">What does this store sell?</label>
			<textarea id="wookiee-rebrand-brief" rows="3" style="width:100%;" placeholder="e.g. Portable outdoor cooking gear - compact grills, camp stoves and cookware for British campers and van-lifers."><?php echo esc_textarea( $brief ); ?></textarea>

			<?php if ( $params && $tokens ) : ?>
				<?php $described = wookiee_describe_design( $params ); ?>
				<div style="margin-top:16px;border-top:1px solid #f0f0f1;padding-top:14px;">
					<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
						<span style="font-size:12px;color:#646970;width:64px;flex:none;">Colours</span>
						<?php foreach ( array( 'wookiee-bg', 'wookiee-ink', 'wookiee-text', 'wookiee-accent', 'wookiee-border' ) as $tok ) : ?>
							<span style="width:20px;height:20px;border-radius:4px;border:1px solid #dcdcde;background:<?php echo esc_attr( $tokens[ $tok ] ); ?>;display:inline-block;"></span>
						<?php endforeach; ?>
						<span style="font-size:12px;color:#646970;"><?php echo wp_kses( $described['Colours'], array( 'strong' => array() ) ); ?></span>
					</div>
					<div style="display:flex;gap:6px;margin-top:8px;">
						<span style="font-size:12px;color:#646970;width:64px;flex:none;">Layout</span>
						<span style="font-size:12px;color:#646970;line-height:1.5;"><?php echo wp_kses( $described['Layout'], array( 'strong' => array() ) ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$placeholder_slots = array_filter( array_keys( wookiee_image_slots() ), 'wookiee_image_is_placeholder' );
			$images_default    = ! empty( $placeholder_slots );
			?>
			<div style="margin-top:16px;padding:12px 14px;background:#f6f7f7;border-radius:4px;">
				<label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
					<input type="checkbox" id="wookiee-rebrand-images" <?php checked( $images_default ); ?> style="margin-top:3px;">
					<span>
						<strong>Also generate new photographs</strong>
						<span style="display:block;font-size:12px;color:#646970;margin-top:2px;">
							<?php if ( $images_default ) : ?>
								<?php echo esc_html( count( $placeholder_slots ) ); ?> of <?php echo esc_html( count( wookiee_image_slots() ) ); ?> images are still the bundled demo photos, which will not match what this store sells.
							<?php else : ?>
								This store already has its own images. Generating replaces them - the old ones stay in the media library.
							<?php endif; ?>
							Adds about a minute per image, and costs more than the text steps.
						</span>
					</span>
				</label>
			</div>

			<p style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
				<button type="button" class="button button-primary button-hero" id="wookiee-rebrand-go">Rebrand my store</button>
				<?php if ( $has_undo ) : ?>
					<button type="button" class="button" id="wookiee-rebrand-undo">Undo last rebrand</button>
				<?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button">View store</a>
			</p>

			<div id="wookiee-rebrand-steps" style="margin-top:8px;"></div>
		</div>
	</div>
	<script>
	( function () {
		var NONCE = '<?php echo esc_js( wp_create_nonce( 'wookiee_rebrand' ) ); ?>';
		var STEPS = <?php echo wp_json_encode( wookiee_rebrand_steps() ); ?>;
		var IMAGE_STEPS = <?php echo wp_json_encode( array_values( wookiee_rebrand_image_steps() ) ); ?>;
		var go    = document.getElementById( 'wookiee-rebrand-go' );
		var out   = document.getElementById( 'wookiee-rebrand-steps' );

		function post( action, extra ) {
			var d = new FormData();
			d.append( 'action', action );
			d.append( 'nonce', NONCE );
			Object.keys( extra || {} ).forEach( function ( k ) { d.append( k, extra[ k ] ); } );
			return fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: d } )
				.then( function ( r ) { return r.json(); } );
		}

		function row( key ) { return document.getElementById( 'wookiee-step-' + key ); }

		function mark( key, icon, colour, detail ) {
			var el = row( key );
			if ( ! el ) { return; }
			el.querySelector( '.wookiee-step-icon' ).innerHTML = icon;
			el.querySelector( '.wookiee-step-icon' ).style.color = colour;
			el.querySelector( '.wookiee-step-detail' ).innerHTML = detail;
		}

		go.addEventListener( 'click', function () {
			var brief = document.getElementById( 'wookiee-rebrand-brief' ).value.trim();
			if ( ! brief ) {
				out.innerHTML = '<p style="color:#a3272a;">Describe the store first.</p>';
				return;
			}

			var wantImages = document.getElementById( 'wookiee-rebrand-images' ).checked;
			var keys = Object.keys( STEPS ).filter( function ( k ) {
				return wantImages || IMAGE_STEPS.indexOf( k ) === -1;
			} );
			var html = '<ul style="margin:14px 0 0;padding:0;list-style:none;">';
			keys.forEach( function ( k ) {
				html += '<li id="wookiee-step-' + k + '" style="margin:6px 0;display:flex;gap:8px;align-items:baseline;">' +
					'<span class="wookiee-step-icon" style="width:14px;color:#8c8f94;">·</span>' +
					'<span><strong>' + STEPS[ k ] + '</strong> ' +
					'<span class="wookiee-step-detail" style="color:#646970;">waiting</span></span></li>';
			} );
			out.innerHTML = html + '</ul>';

			go.disabled = true;
			go.textContent = 'Rebranding…';

			var failed = false;

			// Sequential, not parallel: the design step takes the rollback
			// snapshot, and the copy steps must not overwrite settings before
			// it has been captured.
			var chain = keys.reduce( function ( prev, key ) {
				return prev.then( function () {
					if ( failed ) { return null; }
					mark( key, '<span class="spinner is-active" style="float:none;margin:0;"></span>', '', 'working…' );

					return post( 'wookiee_rebrand', { brief: brief, step: key } ).then( function ( res ) {
						if ( res && res.success ) {
							var detail = res.data.detail;
							if ( res.data.parts ) {
								// Labelled sub-lines, so the layout change is
								// readable on its own rather than buried in a
								// comma-separated run of parameter names.
								detail = '<span style="display:block;margin-top:2px;">';
								Object.keys( res.data.parts ).forEach( function ( label ) {
									detail += '<span style="display:block;"><em>' + label + ':</em> ' +
										res.data.parts[ label ] + '</span>';
								} );
								detail += '</span>';
							}
							mark( key, '&#10003;', '#00622e', detail );
						} else {
							failed = true;
							mark( key, '&#10007;', '#a3272a', ( res && res.data && res.data.message ) || 'Failed.' );
						}
					} ).catch( function () {
						failed = true;
						mark( key, '&#10007;', '#a3272a', 'The request did not complete. Try this step again.' );
					} );
				} );
			}, Promise.resolve() );

			chain.then( function () {
				go.disabled = false;
				go.textContent = failed ? 'Try again' : 'Rebrand my store';
				keys.forEach( function ( k ) {
					var d = row( k ) && row( k ).querySelector( '.wookiee-step-detail' );
					if ( d && 'waiting' === d.textContent ) { d.textContent = 'skipped'; }
				} );
				if ( ! failed ) {
					// Reload so the swatches, the Undo button and the Settings
					// tabs all show what was just written.
					setTimeout( function () { window.location.reload(); }, 1500 );
				}
			} );
		} );

		var undo = document.getElementById( 'wookiee-rebrand-undo' );
		if ( undo ) {
			undo.addEventListener( 'click', function () {
				if ( ! window.confirm( 'Put back the design and copy from before the last rebrand?' ) ) { return; }
				undo.disabled = true;
				post( 'wookiee_rebrand_undo' ).then( function () { window.location.reload(); } );
			} );
		}
	}() );
	</script>
	<?php
}
