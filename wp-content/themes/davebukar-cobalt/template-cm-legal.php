<?php
/**
 * Template Name: Channel Manager Legal
 *
 * The Channel Manager terms and privacy pages. Their text lives in dbt_cm_legal_pages(), keyed by
 * slug, so it is reviewed in the theme like the site's other legal text.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="prose-page">
	<div class="prose-page__inner">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( 'publish' !== get_post_status() ) : ?>
				<p class="prose-page__draft"><strong>DRAFT for the owner's review.</strong> Not published and not indexed.</p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<?php
			$dbt_cm_legal = dbt_cm_legal_pages();
			$dbt_cm_slug  = get_post_field( 'post_name', get_the_ID() );
			?>
			<div class="prose-page__body">
				<?php
				if ( isset( $dbt_cm_legal[ $dbt_cm_slug ] ) ) {
					echo wp_kses_post( $dbt_cm_legal[ $dbt_cm_slug ] );
				} else {
					the_content();
				}
				?>
			</div>
		<?php endwhile; ?>
	</div>
</article>

<?php get_footer(); ?>
