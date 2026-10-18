<?php
/**
 * 404: say so plainly, and offer home, the blog and a search.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="notfound">
	<div class="notfound__inner">
		<p class="mono-label">404 · NOT FOUND</p>
		<h1 class="notfound__title">Page not found</h1>
		<p class="notfound__lede">The page you asked for isn’t here. It may have moved, or the link may be mistyped.</p>
		<div class="hero__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Go to the homepage</a>
			<a class="btn btn--outline" href="<?php echo esc_url( dbt_page_url( 'blog' ) ); ?>">Read the blog</a>
		</div>
		<?php dbt_search_form( 'notfound-search' ); ?>
	</div>
</section>

<?php get_footer(); ?>
