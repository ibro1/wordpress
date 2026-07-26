<?php
/**
 * Template Name: Checkout
 *
 * Wide, chrome-free shell for pages that hold a [wu_checkout] form (e.g.
 * /register/) - the generic page.php prose column is too narrow for a
 * plan-grid + order-summary layout, so this gives the checkout form the
 * full container width instead.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="dbt-checkout">
	<?php while ( have_posts() ) : the_post(); ?>
		<h1 class="dbt-checkout__title"><?php the_title(); ?></h1>
		<?php the_content(); ?>
	<?php endwhile; ?>
</article>

<?php get_footer(); ?>
