<?php
/**
 * Named image slots.
 *
 * Every image on the designed pages used to be a hardcoded path to a bundled
 * asset - assets/images/drawer-organizer.png on the About page, and so on.
 * Those bundled images are all from the original storage-niche demo, so an
 * outdoor cooking store shipped a photo of a drawer organiser on its About
 * page and storage boxes in its hero, and nothing in the theme could change
 * that short of editing the page HTML by hand.
 *
 * A slot is a name ("about_hero") that resolves to an uploaded attachment if
 * one has been set for this site, and to the bundled asset otherwise. That
 * makes the images replaceable per store - by hand today, and by the image
 * generator that fills the same slots.
 *
 * @see wookiee_image_shortcode() for use inside baked page content, which
 *      cannot call PHP directly.
 */

defined( 'ABSPATH' ) || exit;

/**
 * slot => [ bundled fallback filename, alt text, description used when
 * generating a replacement ].
 */
function wookiee_image_slots() {
	return array(
		'hero'        => array(
			'fallback' => 'hero-banner.png',
			'label'    => 'Homepage hero',
			'brief'    => 'the main homepage hero image - the single most important photo on the site',
		),
		// The "how it works" band on the homepage. Predates slots and has its
		// own option name already in use on live sites, same as the hero.
		'feature'     => array(
			'fallback' => 'lifestyle.png',
			'label'    => 'Homepage how-it-works',
			'brief'    => 'the wide band beside the "how it works" steps on the homepage, showing the products in the setting they are used in',
		),
		'about_hero'  => array(
			'fallback' => 'drawer-organizer.png',
			'label'    => 'About page hero',
			'brief'    => 'the About page hero image, shown beside the founder introduction',
		),
		'about_story' => array(
			'fallback' => 'bathroom-shelf.png',
			'label'    => 'About page story',
			'brief'    => 'a supporting image for the About page story section, further down the page',
		),
	);
}

function wookiee_image_option_key( $slot ) {
	// The homepage hero predates slots and already has its own option name in
	// use on live sites; renaming it would silently blank existing heroes.
	$legacy = array(
		'hero'    => 'wookiee_hero_image_id',
		'feature' => 'wookiee_feature_image_id',
	);
	return isset( $legacy[ $slot ] ) ? $legacy[ $slot ] : 'wookiee_image_' . $slot . '_id';
}

function wookiee_image_attachment_id( $slot ) {
	return (int) get_option( wookiee_image_option_key( $slot ), 0 );
}

/**
 * The URL for a slot: the uploaded attachment if set and still present in the
 * media library, otherwise the bundled fallback. An attachment that has been
 * deleted returns a false URL, which would render a broken image - so the
 * fallback covers that case too, not just "never set".
 */
function wookiee_image_url( $slot ) {
	$slots = wookiee_image_slots();
	if ( ! isset( $slots[ $slot ] ) ) {
		return '';
	}

	$id = wookiee_image_attachment_id( $slot );
	if ( $id ) {
		$url = wp_get_attachment_url( $id );
		if ( $url ) {
			return $url;
		}
	}

	return WOOKIEE_URI . 'assets/images/' . $slots[ $slot ]['fallback'];
}

/**
 * True when the slot is still showing a bundled demo image rather than
 * something belonging to this store. Used to warn on the Rebrand screen,
 * where an off-niche hero is the most visible thing a rebrand leaves behind.
 */
function wookiee_image_is_placeholder( $slot ) {
	return 0 === wookiee_image_attachment_id( $slot );
}

/**
 * [wookiee_image slot="about_hero" class="about-story-image"]
 *
 * Baked page content cannot call PHP, so image slots reach the About and
 * Contact templates the same way business details do - through a shortcode
 * resolved at render time rather than a URL frozen at page-creation time.
 */
add_shortcode( 'wookiee_image', 'wookiee_image_shortcode' );
function wookiee_image_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'slot'  => '',
			'class' => '',
			'alt'   => '',
		),
		$atts
	);

	$url = wookiee_image_url( $atts['slot'] );
	if ( '' === $url ) {
		return '';
	}

	$slots = wookiee_image_slots();
	$alt   = '' !== $atts['alt'] ? $atts['alt'] : $slots[ $atts['slot'] ]['label'];

	return sprintf(
		'<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">',
		esc_url( $url ),
		esc_attr( $alt ),
		esc_attr( $atts['class'] )
	);
}

/* -------------------------------------------------------------------------
 * Generation
 * ---------------------------------------------------------------------- */

/**
 * The prompt for one slot.
 *
 * Deliberately prescriptive about what NOT to produce. Left to itself an
 * image model will put text, logos and a watermark-shaped smudge into a
 * "hero image for a store", none of which survives being cropped behind a
 * headline. It also has to be told the image sits under overlaid copy, or
 * it centres the subject exactly where the text goes.
 */
function wookiee_build_image_prompt( $slot, $brief ) {
	$slots = wookiee_image_slots();
	if ( ! isset( $slots[ $slot ] ) ) {
		return '';
	}

	/*
	 * The brief and purpose are interpolated into the DEFAULT here, not left
	 * as {{tokens}} for wookiee_maybe_override() to fill in. That function
	 * only substitutes on the override path - with no override saved it
	 * returns the default verbatim, so tokens left in it reach the model as
	 * the literal text "{{brief}}". An image model given that writes a
	 * generic lifestyle stock photo, which is exactly what shipped.
	 *
	 * Every other builder in the registry already interpolates first and
	 * passes $vars only so an operator's override can use the tokens.
	 */
	$default = 'Photograph for a UK online store. The store sells: ' . trim( (string) $brief ) . '.'
		. "\n\nThis image is " . $slots[ $slot ]['brief'] . '.'
		. "\n\nRequirements:"
		. "\n- The image MUST show what this store actually sells, as described above. Do not substitute a generic lifestyle, home-interior or technology scene."
		. "\n- A real photographic scene of the products in use, or styled in a setting that suits them. Not an illustration, not a 3D render, not a flat-lay of icons."
		. "\n- Natural light, shallow depth of field, plenty of clean empty space on one side where headline text will be placed over it."
		. "\n- No text, no lettering, no logos, no watermarks, no signage, no packaging with brand names."
		. "\n- No people looking directly at the camera; hands or a partial figure using the product are fine."
		. "\n- Calm, uncluttered composition. One clear subject.";

	return wookiee_maybe_override(
		'slot_image',
		$default,
		array(
			'brief'   => $brief,
			'purpose' => $slots[ $slot ]['brief'],
		)
	);
}

/**
 * Generates one slot's image and puts it in the media library.
 *
 * Returns the attachment id, or WP_Error. The previous attachment is left
 * alone rather than deleted - an operator who dislikes the new image can
 * point the slot back at the old one from the media library, which is not
 * possible if generating destroyed it.
 */
function wookiee_generate_slot_image( $slot, $brief ) {
	$slots = wookiee_image_slots();
	if ( ! isset( $slots[ $slot ] ) ) {
		return new WP_Error( 'wookiee_image_bad_slot', 'Unknown image slot.' );
	}
	if ( '' === trim( (string) $brief ) ) {
		return new WP_Error( 'wookiee_image_no_brief', 'Describe the store first - the image is generated from it.' );
	}
	if ( ! wookiee_central_api_configured() ) {
		return new WP_Error( 'wookiee_image_not_activated', 'Image generation needs the activation code (Settings > Activation).' );
	}

	$body = array(
		'prompt' => wookiee_build_image_prompt( $slot, $brief ),
		'size'   => '1536x1024',
	);

	/*
	 * Precedence, matching wookiee_call_llm(): the model picked for this
	 * request (the picker on the Rebrand screen) > the site's saved default >
	 * nothing, which lets the backend apply this activation code's own
	 * default. Omitting it entirely is what every install did before image
	 * model selection existed, so an untouched site behaves as before.
	 */
	$model = wookiee_image_model_override();
	if ( '' === $model ) {
		$model = trim( (string) wookiee_get_setting( 'image_model' ) );
	}
	if ( '' !== $model ) {
		$body['model'] = $model;
	}

	/*
	 * 600s, not the 200 this used to allow. A hosted provider answers in
	 * well under a minute, but a self-hosted CPU model runs into minutes -
	 * cutting it off there reports a failure for work that was going to
	 * finish. 600 matches the WordPress container's own max_execution_time,
	 * so this is the longest wait that can actually complete; anything
	 * slower than that needs a GPU rather than a bigger number here.
	 */
	$result = wookiee_central_api_request( 'POST', '/images/generate', $body, 600 );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$encoded = isset( $result['image_base64'] ) ? (string) $result['image_base64'] : '';
	if ( '' === $encoded ) {
		return new WP_Error( 'wookiee_image_empty', 'The backend returned no image.' );
	}

	$binary = base64_decode( $encoded, true );
	if ( false === $binary || '' === $binary ) {
		return new WP_Error( 'wookiee_image_decode', 'The generated image could not be decoded.' );
	}

	$attach_id = wookiee_sideload_image_from_binary(
		$binary,
		'wookiee-' . $slot . '-' . time() . '.png',
		$slots[ $slot ]['label']
	);

	if ( ! $attach_id ) {
		return new WP_Error( 'wookiee_image_sideload', 'The image was generated but could not be saved to the media library.' );
	}

	update_option( wookiee_image_option_key( $slot ), $attach_id );

	return $attach_id;
}
