<?php
/**
 * The site logo, in one place.
 *
 * header.php, page-cart.php and page-checkout.php each had their own copy of
 * this markup. Only header.php checked has_custom_logo(); the cart and
 * checkout copies were a hardcoded SVG with the literal word "Wookiee" baked
 * into it, so a store that had uploaded its own logo showed its brand on the
 * homepage and the theme author's brand at the moment of payment. They also
 * carried the storage-niche terracotta (#c1704a) that no palette change
 * could reach.
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
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none" class="site-logo-svg" role="img" aria-hidden="true" focusable="false">
			<path d="M8 8 L12 30 L17.5 17 L23 30 L27 8" stroke="var(--wookiee-ink)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
			<rect x="10" y="11" width="4.5" height="4" rx="1" fill="var(--wookiee-accent)"/>
			<rect x="20.5" y="11" width="4.5" height="4" rx="1" fill="var(--wookiee-accent)"/>
			<line x1="11" y1="13" x2="13.5" y2="13" stroke="var(--wookiee-ink)" stroke-width="1.2" stroke-linecap="round"/>
			<line x1="21.5" y1="13" x2="24" y2="13" stroke="var(--wookiee-ink)" stroke-width="1.2" stroke-linecap="round"/>
			<text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="var(--wookiee-ink)" letter-spacing="-0.5px"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></text>
		</svg>
	</a>
<?php endif; ?>
