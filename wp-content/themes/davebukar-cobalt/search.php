<?php
/**
 * Search results, listed with the blog's post cards.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="hero hero--lite hero--single">
	<div class="hero__inner">
		<div class="hero__copy">
			<p class="mono-label">SEARCH</p>
			<h1 class="hero__title"><?php echo esc_html( '' !== get_search_query() ? 'Results for “' . get_search_query() . '”' : 'Search' ); ?></h1>
			<?php dbt_search_form( 'search-page' ); ?>
		</div>
	</div>
</section>

<section class="blog" aria-label="Search results">
	<?php if ( have_posts() ) : ?>
		<div class="post-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php if ( 'post' === get_post_type() ) : ?>
					<?php dbt_post_card(); ?>
				<?php else : ?>
					<article class="post-card">
						<div class="post-card__body">
							<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<a class="cell__link post-card__link" href="<?php the_permalink(); ?>">Open the page →</a>
						</div>
					</article>
				<?php endif; ?>
			<?php endwhile; ?>
		</div>
		<?php
		global $wp_query;
		dbt_pagination( (int) $wp_query->max_num_pages, max( 1, (int) get_query_var( 'paged' ) ), str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ) );
		?>
	<?php else : ?>
		<p class="blog__empty">Nothing matched. Try another word, or <a href="<?php echo esc_url( dbt_page_url( 'blog' ) ); ?>">browse the blog</a>.</p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
