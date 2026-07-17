<?php
/**
 * Archive template.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="tat-section">
	<div class="tat-container">
		<div class="tat-section-head">
			<p class="tat-kicker"><?php esc_html_e( 'Archive', 'tatyli-fresh' ); ?></p>
			<h1><?php the_archive_title(); ?></h1>
		</div>
		<?php if ( have_posts() ) : ?>
			<div class="tat-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'tat-card' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
