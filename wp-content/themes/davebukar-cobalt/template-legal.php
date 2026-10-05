<?php
/**
 * Template Name: Legal Page
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="prose-page">
	<div class="prose-page__inner">
		<?php while ( have_posts() ) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
			<?php
			// The theme's own legal text wins over whatever the page stores: WordPress creates its own "Privacy Policy" page with
			// sample text under the same slug, which provisioning leaves in place.
			$dbt_legal = dbt_legal_pages();
			$dbt_slug  = get_post_field( 'post_name', get_the_ID() );
			?>
			<div class="prose-page__body">
				<?php
				if ( isset( $dbt_legal[ $dbt_slug ] ) ) {
					echo wp_kses_post( $dbt_legal[ $dbt_slug ]['content'] );
				} else {
					the_content();
				}
				?>
			</div>
		<?php endwhile; ?>
	</div>
</article>

<?php get_footer(); ?>
