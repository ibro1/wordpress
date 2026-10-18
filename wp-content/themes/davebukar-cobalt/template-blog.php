<?php
/**
 * Template Name: Blog
 *
 * The blog index at /blog/: every published post, newest first, paginated
 * at /blog/page/N/. Posts keep their own root permalinks.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$dbt_paged = dbt_blog_paged();
$dbt_posts = dbt_blog_query( $dbt_paged );
?>

<section class="hero hero--lite hero--single">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">BLOG</p>
			<h1 class="hero__title">Guides before you pay for it.</h1>
			<p class="hero__lede">What websites, Google Ads, software, DevOps and AI agents really cost, and what to ask for, written by the team that builds and runs them.</p>
		</div>
	</div>
</section>

<section class="blog" aria-label="Articles">
	<?php if ( $dbt_posts->have_posts() ) : ?>
		<div class="post-grid">
			<?php
			$dbt_i = 0;
			while ( $dbt_posts->have_posts() ) :
				$dbt_posts->the_post();
				dbt_post_card( 0 === $dbt_i++ && 1 === $dbt_paged );
			endwhile;
			?>
		</div>
		<?php dbt_pagination( (int) $dbt_posts->max_num_pages, $dbt_paged, trailingslashit( get_permalink() ) . 'page/%#%/' ); ?>
	<?php else : ?>
		<p class="blog__empty">No articles yet. The first guides are on their way.</p>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
