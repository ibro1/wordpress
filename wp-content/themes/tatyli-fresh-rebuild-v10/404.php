<?php
/**
 * 404 template.
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="tat-section">
	<div class="tat-container">
		<div class="tat-panel">
			<h1><?php esc_html_e( 'Page not found', 'tatyli-fresh' ); ?></h1>
			<p><?php esc_html_e( 'The page you are looking for may have moved. Please return to the homepage or contact Tatyli.', 'tatyli-fresh' ); ?></p>
			<p><a class="tat-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go Home', 'tatyli-fresh' ); ?></a></p>
		</div>
	</div>
</section>
<?php
get_footer();
