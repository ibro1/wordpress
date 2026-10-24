</main>

<?php
// A page's URL when it is published, otherwise null, so the footer never links to a 404.
$dbt_published_url = function ( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	return ( $page && 'publish' === $page->post_status ) ? get_permalink( $page ) : null;
};

$dbt_footer_legal = array_filter( array(
	'Privacy policy'          => $dbt_published_url( 'privacy-policy' ),
	'Refund policy'           => $dbt_published_url( 'refund' ),
	'Terms of service'        => $dbt_published_url( 'terms-of-service' ),
	'Channel Manager terms'   => $dbt_published_url( 'channel-manager-terms' ),
	'Channel Manager privacy' => $dbt_published_url( 'channel-manager-privacy' ),
) );
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__top">
			<div class="site-footer__intro">
				<a class="site-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo dbt_brand_mark(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts ?>Linkfa</a>
				<p class="site-footer__statement">We build the software, run the infrastructure, and stay reachable after launch.</p>
			</div>
			<a class="btn btn--primary site-footer__cta" href="<?php echo esc_url( home_url( '/talk/?ref=footer' ) ); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
		</div>

		<nav class="site-footer__cols" aria-label="Footer">
			<div class="site-footer__col">
				<h2 class="site-footer__heading">Services</h2>
				<ul class="site-footer__list">
					<?php foreach ( dbt_services() as $slug => $service ) : ?>
						<li><a href="<?php echo esc_url( dbt_page_url( $slug ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( dbt_page_url( 'channel-manager' ) ); ?>">YouTube Channel Manager</a></li>
					<li><a href="<?php echo esc_url( dbt_page_url( 'clips' ) ); ?>">Short clips</a></li>
				</ul>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__heading">Company</h2>
				<ul class="site-footer__list">
					<li><a href="<?php echo esc_url( dbt_page_url( 'work' ) ); ?>">Work</a></li>
					<li><a href="<?php echo esc_url( home_url( '/#how-we-work' ) ); ?>">How we work</a></li>
					<li><a href="<?php echo esc_url( dbt_page_url( 'pricing-faq' ) ); ?>">Pricing &amp; FAQ</a></li>
					<li><a href="<?php echo esc_url( dbt_page_url( 'blog' ) ); ?>">Blog</a></li>
					<li><a href="<?php echo esc_url( dbt_page_url( 'register' ) ); ?>">Start a project</a></li>
				</ul>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__heading">Legal</h2>
				<ul class="site-footer__list">
					<?php foreach ( $dbt_footer_legal as $label => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="site-footer__col">
				<h2 class="site-footer__heading">Contact</h2>
				<ul class="site-footer__list">
					<li><a href="<?php echo esc_url( home_url( '/talk/?ref=footer' ) ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
					<li><a href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a></li>
					<li><button type="button" class="site-footer__linkbtn js-book-call">Book a call</button></li>
				</ul>
			</div>
		</nav>

		<div class="site-footer__bottom">
			<p class="site-footer__copyright">
				<span class="site-footer__copyright-line">© <?php echo esc_html( date( 'Y' ) ); ?> Linkfa · DaveBukar Technologies (BN 8527446)</span><span class="site-footer__copyright-sep" aria-hidden="true"> · </span><span class="site-footer__copyright-line">Suite B57 EFAB Mall, Area 11, Garki, Abuja</span>
			</p>
		</div>
	</div>
</footer>

<a class="wa-float" href="<?php echo esc_url( dbt_wa_link( 'Hi Linkfa, I found your site and have a question.' ) ); ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">WhatsApp</a>

<?php wp_footer(); ?>
</body>
</html>
