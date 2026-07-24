<?php
/**
 * Template Name: Service Page
 */

defined( 'ABSPATH' ) || exit;

get_header();

$slug     = get_post_field( 'post_name' );
$services = dbt_services();
$service  = isset( $services[ $slug ] ) ? $services[ $slug ] : null;
?>

<?php if ( $service ) : ?>

	<section class="hero hero--lite">
		<div class="hero__inner">
			<div class="hero__copy reveal">
				<p class="mono-label"><?php echo esc_html( strtoupper( $service['nav_label'] ) ); ?></p>
				<h1 class="hero__title"><?php echo esc_html( $service['title'] ); ?></h1>
				<p class="hero__lede"><?php echo esc_html( $service['lede'] ); ?></p>
				<div class="hero__actions">
					<button type="button" class="btn btn--primary js-book-call">Book a call</button>
				</div>
			</div>

			<?php if ( ! empty( $service['terminal'] ) ) : ?>
			<div class="hero__demo reveal">
				<div class="code-card" data-typein>
					<div class="code-card__bar">
						<span class="code-card__file"><?php echo esc_html( $slug ); ?>.sh</span>
						<span class="status status--ok">PASS</span>
					</div>
					<pre class="code-card__body"><code><?php
						foreach ( $service['terminal'] as $i => $line ) {
							$cls = ( 0 === strpos( $line, '$' ) ) ? 'tok-cmd' : ( ( false !== strpos( $line, 'PASS' ) || false !== strpos( $line, '200 OK' ) || false !== strpos( $line, 'verified' ) ) ? 'tok-key' : 'tok-muted' );
							echo '<span class="code-line ' . esc_attr( $cls ) . '" data-line="' . (int) $i . '">' . esc_html( $line ) . '</span>' . "\n";
						}
					?></code></pre>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="bento bento--detail reveal" aria-label="What’s included">
		<?php foreach ( $service['points'] as $i => $point ) : ?>
			<article class="cell span-1x1">
				<h3 class="cell__title"><?php echo esc_html( $point['heading'] ); ?></h3>
				<p class="cell__body"><?php echo esc_html( $point['body'] ); ?></p>
			</article>
		<?php endforeach; ?>
	</section>

	<section class="cta reveal">
		<div class="cta__inner">
			<h2 class="cta__title">Tell us what you’re building.</h2>
			<div class="cta__actions">
				<button type="button" class="btn btn--primary js-book-call">Book a call</button>
				<a class="cta__email" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
			</div>
		</div>
	</section>

<?php else : ?>

	<article class="prose-page">
		<div class="prose-page__inner">
			<?php while ( have_posts() ) : the_post(); ?>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			<?php endwhile; ?>
		</div>
	</article>

<?php endif; ?>

<?php get_footer(); ?>
