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
	return 'hero' === $slot ? 'wookiee_hero_image_id' : 'wookiee_image_' . $slot . '_id';
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
