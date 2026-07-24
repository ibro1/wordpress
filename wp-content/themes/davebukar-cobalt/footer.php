</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__statement">We build the software, run the infrastructure, and stay reachable after launch.</p>

		<div class="site-footer__meta">
			<a class="site-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Dave Bukar<span class="nav__brand-mono">.tech</span></a>

			<nav class="site-footer__links" aria-label="Footer">
				<?php foreach ( dbt_services() as $slug => $service ) : $page = get_page_by_path( $slug, OBJECT, 'page' ); ?>
					<a href="<?php echo esc_url( $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' ) ); ?>"><?php echo esc_html( $service['nav_label'] ); ?></a>
				<?php endforeach; ?>
				<?php $privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' ); ?>
				<a href="<?php echo esc_url( $privacy ? get_permalink( $privacy ) : home_url( '/privacy-policy/' ) ); ?>">Privacy</a>
				<?php $terms = get_page_by_path( 'terms-of-service', OBJECT, 'page' ); ?>
				<a href="<?php echo esc_url( $terms ? get_permalink( $terms ) : home_url( '/terms-of-service/' ) ); ?>">Terms</a>
				<a href="mailto:<?php echo esc_attr( DBT_CONTACT_EMAIL ); ?>"><?php echo esc_html( DBT_CONTACT_EMAIL ); ?></a>
			</nav>

			<p class="site-footer__copyright">© <?php echo esc_html( date( 'Y' ) ); ?> Dave Bukar Technologies.</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
