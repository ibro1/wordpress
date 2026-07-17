<?php
/**
 * Site footer.
 */

defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="tat-site-footer">
	<div class="tat-container">
		<div class="tat-footer-grid">
			<div>
				<h3>Tatyli</h3>
				<p>A non-profit cultural organization supporting cultural understanding through folk traditions, performing arts, learning, and community participation.</p>
			</div>
			<div>
				<h4><?php esc_html_e( 'Pages', 'tatyli-fresh' ); ?></h4>
				<ul class="tat-footer-links">
					<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
					<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a></li>
					<li><a href="<?php echo esc_url( home_url( '/mission/' ) ); ?>">Mission</a></li>
					<li><a href="<?php echo esc_url( home_url( '/activities/' ) ); ?>">Activities</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
				</ul>
			</div>
			<div>
				<h4><?php esc_html_e( 'Contact', 'tatyli-fresh' ); ?></h4>
				<p>Rue Hocheporte 20, 4000 Liège, Belgium</p>
				<p><a href="mailto:info@tatyli.be">info@tatyli.be</a><br><a href="tel:+32489249962">+32 489249962</a></p>
			</div>
			<div>
				<h4><?php esc_html_e( 'Legal', 'tatyli-fresh' ); ?></h4>
				<ul class="tat-footer-links">
					<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
				</ul>
			</div>
		</div>
		<div class="tat-footer-bottom">
			<span>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> Tatyli.</span>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
