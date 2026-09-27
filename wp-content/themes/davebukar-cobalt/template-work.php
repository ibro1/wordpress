<?php
/**
 * Template Name: Work
 *
 * Case studies (client systems, by sector) and the products we run
 * ourselves. Content lives in inc/work-content.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="hero hero--lite hero--single">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">WORK</p>
			<h1 class="hero__title">Systems we designed, built and still keep running.</h1>
			<p class="hero__lede">Logistics, government, transport, healthcare and AI — each one taken from an empty repo to production by the same small team, including the servers it runs on.</p>
			<div class="hero__actions">
				<button type="button" class="btn btn--primary js-book-call">Book a call</button>
				<a class="btn btn--outline" href="#products">Our own products</a>
			</div>
		</div>
	</div>
</section>

<section class="work reveal" aria-label="Client work">
	<?php foreach ( dbt_case_studies() as $case ) : ?>
		<article class="case">
			<div class="case__head">
				<p class="cell__label"><?php echo esc_html( $case['sector'] ); ?></p>
				<h2 class="case__title"><?php echo esc_html( $case['title'] ); ?></h2>
				<p class="case__lede"><?php echo esc_html( $case['lede'] ); ?></p>
				<p class="case__scale"><?php echo esc_html( $case['scale'] ); ?></p>
			</div>
			<div class="case__body">
				<ul class="case__points">
					<?php foreach ( $case['points'] as $point ) : ?>
						<li><?php echo esc_html( $point ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="cell__tags">
					<?php foreach ( $case['stack'] as $tag ) : ?>
						<span class="cell__tag"><?php echo esc_html( $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
	<p class="work__note">Client names are withheld where the work was delivered under another agency’s contract. References are available on a call.</p>
</section>

<section class="band reveal" id="products" aria-label="Our own products">
	<div class="band__inner">
		<h2 class="band__title">Products we build and run ourselves</h2>
		<div class="products">
			<?php foreach ( dbt_own_products() as $product ) : ?>
				<article class="product">
					<p class="band__step-no"><?php echo esc_html( strtoupper( $product['kind'] ) ); ?></p>
					<h3 class="band__step-title"><?php echo esc_html( $product['name'] ); ?></h3>
					<p class="band__step-body"><?php echo esc_html( $product['body'] ); ?></p>
					<p class="product__tags"><?php echo esc_html( implode( ' · ', $product['tags'] ) ); ?></p>
					<?php if ( ! empty( $product['url'] ) ) : ?>
						<a class="product__link" href="<?php echo esc_url( $product['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $product['url'], PHP_URL_HOST ) ); ?> ↗</a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="cta reveal">
	<div class="cta__inner">
		<h2 class="cta__title">Need something like this built?</h2>
		<div class="cta__actions">
			<button type="button" class="btn btn--primary js-book-call">Book a call</button>
			<a class="cta__email" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
