<?php
/**
 * Site header.
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="tat-site-header">
	<div class="tat-container tat-header-inner">
		<a class="tat-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Tatyli home', 'tatyli-fresh' ); ?>">
			<?php tatyli_fresh_logo_markup(); ?>
		</a>
		<button class="tat-menu-toggle" type="button" aria-controls="tat-primary-menu" aria-expanded="false" data-tat-menu-toggle>
			<?php esc_html_e( 'Menu', 'tatyli-fresh' ); ?>
		</button>
		<nav id="tat-primary-menu" class="tat-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'tatyli-fresh' ); ?>" data-tat-nav>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'tatyli_fresh_nav_fallback',
				'depth'          => 2,
			) );
			?>
		</nav>
	</div>
</header>
<main id="primary" class="tat-site-main">
