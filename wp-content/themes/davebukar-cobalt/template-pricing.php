<?php
/**
 * Template Name: Pricing & FAQ
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="hero hero--lite">
	<div class="hero__inner">
		<div class="hero__copy reveal">
			<p class="mono-label">PRICING &amp; FAQ</p>
			<h1 class="hero__title">Pricing &amp; FAQ</h1>
			<p class="hero__lede">Prices where we publish them. Everything else is scoped first, then quoted. Linkfa is a service of <?php echo esc_html( DBT_COMPANY_NAME ); ?>, working with clients in Nigeria and the UK.</p>
		</div>
	</div>
</section>

<section class="plans reveal" aria-label="Published prices">
	<h2 class="plans__title">Short clips</h2>
	<?php dbt_render_clips_plans( ' (from the pricing page)' ); ?>
</section>

<section class="plans plans--two reveal" aria-label="Advert pack">
	<h2 class="plans__title">Short adverts</h2>
	<?php dbt_render_clips_plans( ' (from the pricing page)', array_slice( dbt_business_packs(), 0, 1 ) ); ?>
</section>

<section class="glance reveal" aria-label="Priced on request">
	<h2 class="glance__title">Priced on request</h2>
	<dl class="glance__list">
		<?php foreach ( dbt_services() as $slug => $service ) : ?>
			<div class="glance__row"><dt><a href="<?php echo esc_url( home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></dt><dd>On request. We quote after a short scoping call, and offer fixed pricing where possible.</dd></div>
		<?php endforeach; ?>
	</dl>
</section>

<?php dbt_render_faq( dbt_pricing_faq() ); ?>

<section class="cta reveal">
	<div class="cta__inner">
		<h2 class="cta__title">Tell us what you’re building.</h2>
		<div class="cta__actions">
			<button type="button" class="btn btn--primary js-book-call">Book a call</button>
			<a class="cta__email" href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
