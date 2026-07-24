<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="prose-page">
	<div class="prose-page__inner">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
			<h1><?php the_title(); ?></h1>
			<div class="prose-page__body"><?php the_content(); ?></div>
		<?php endwhile; endif; ?>
	</div>
</article>

<?php get_footer(); ?>
