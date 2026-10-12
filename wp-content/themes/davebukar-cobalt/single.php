<?php
/**
 * Single article layout (SEO employee posts): title, date, body, call to action.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<article class="prose-page prose-page--article">
	<div class="prose-page__inner">
		<?php while ( have_posts() ) : the_post(); ?>
			<p class="article__meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time> · <?php echo esc_html( DBT_ORG_NAME ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="article__image"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></figure>
			<?php endif; ?>
			<div class="prose-page__body"><?php the_content(); ?></div>
			<aside class="article__cta">
				<h2>Talk to us about your project</h2>
				<p>Tell us what you need built, fixed or run. We agree scope on a call before any work begins.</p>
				<p>
					<button type="button" class="btn btn--primary js-book-call">Book a free call</button>
					<a class="btn btn--outline" href="https://wa.me/<?php echo esc_attr( DBT_WHATSAPP ); ?>">WhatsApp us</a>
				</p>
			</aside>
		<?php endwhile; ?>
	</div>
</article>

<?php get_footer(); ?>
