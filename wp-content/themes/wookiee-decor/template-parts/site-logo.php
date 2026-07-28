<?php
/**
 * The site logo, in one place.
 *
 * header.php, page-cart.php and page-checkout.php each had their own copy of
 * this markup. Only header.php checked has_custom_logo(); the cart and
 * checkout copies were a hardcoded SVG with the literal word "Wookiee" baked
 * into it, so a store that had uploaded its own logo showed its brand on the
 * homepage and the theme author's brand at the moment of payment.
 *
 * Two further faults fixed here:
 *
 *  - The mark was a fixed "W" glyph, Wookiee's own, on every store built with
 *    this theme and unchanged by any rebrand. It is now drawn from the
 *    store's own initial and accent colour (see inc/brand-mark.php).
 *  - The store name was rendered as <text> inside a fixed 160x38 viewBox, so
 *    anything longer than about eleven characters was silently clipped -
 *    "High Beeches" showed as "High Beeche". The name is HTML now, beside the
 *    mark rather than inside it, so it is laid out and wrapped by CSS like
 *    any other text.
 *
 * Args:
 *   link_class - class for the <a> wrapper, since the header and the
 *                cart/checkout headers style theirs differently.
 *
 * @param array $args Passed via get_template_part()'s $args (WP 5.5+).
 */

defined( 'ABSPATH' ) || exit;

$wookiee_link_class = isset( $args['link_class'] ) ? $args['link_class'] : 'site-logo-link';
?>
<?php if ( has_custom_logo() ) : ?>
	<div class="<?php echo esc_attr( $wookiee_link_class ); ?> site-logo-custom"><?php the_custom_logo(); ?></div>
<?php else : ?>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo esc_attr( $wookiee_link_class ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<span class="site-logo-mark"><?php echo wookiee_brand_mark_svg( 34 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in wookiee_brand_mark_svg(). ?></span>
		<span class="site-logo-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	</a>
<?php endif; ?>
