</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__statement">We build the software, run the infrastructure, and stay reachable after launch.</p>

		<div class="site-footer__meta">
			<a class="site-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo dbt_brand_mark(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts ?>Linkfa</a>

			<nav class="site-footer__links" aria-label="Footer">
				<?php foreach ( dbt_services() as $slug => $service ) : $page = get_page_by_path( $slug, OBJECT, 'page' ); ?>
					<a href="<?php echo esc_url( $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $service['nav_label'] ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( dbt_page_url( 'work' ) ); ?>">Work</a>
				<a href="<?php echo esc_url( dbt_page_url( 'clips' ) ); ?>">Clips</a>
				<a href="<?php echo esc_url( dbt_page_url( 'pricing-faq' ) ); ?>">Pricing</a>
				<a href="<?php echo esc_url( dbt_page_url( 'blog' ) ); ?>">Blog</a>
				<?php $privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ); ?>
				<a href="<?php echo esc_url( $privacy ? get_permalink( $privacy ) : home_url( '/privacy-policy/' ) ); ?>">Privacy</a>
				<a href="<?php echo esc_url( dbt_page_url( 'refund' ) ); ?>">Refunds</a>
				<?php $terms = get_page_by_path( 'terms-of-service', OBJECT, 'page' ); ?>
				<?php if ( $terms && 'publish' === $terms->post_status ) : ?><a href="<?php echo esc_url( get_permalink( $terms ) ); ?>">Terms</a><?php endif; ?>
				<a href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
			</nav>

			<p class="site-footer__copyright">© <?php echo esc_html( date( 'Y' ) ); ?> Linkfa, by DaveBukar Technologies.</p>
		</div>
	</div>
</footer>

<a class="wa-float" href="<?php echo esc_url( dbt_wa_link( 'Hi Linkfa, I found your site and have a question.' ) ); ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">WhatsApp</a>

<?php wp_footer(); ?>
</body>
</html>
