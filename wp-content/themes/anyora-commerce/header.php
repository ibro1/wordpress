<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="container header-inner">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Anyora.</a>
		</div>
		<nav class="main-navigation">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
				) );
			} else {
				echo '<ul><li><a href="' . esc_url( home_url( '/' ) ) . '">Home</a></li><li><a href="' . esc_url( home_url( '/shop/' ) ) . '">Shop</a></li><li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li></ul>';
			}
			?>
		</nav>
		<div class="header-actions">
			<a href="#" class="header-icon" aria-label="Search">🔍</a>
			<a href="<?php echo class_exists( 'WooCommerce' ) ? esc_url( wc_get_cart_url() ) : '#'; ?>" class="header-icon cart-icon" aria-label="Cart">
                🛒 
                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <span class="cart-count">(<?php echo WC()->cart->get_cart_contents_count(); ?>)</span>
                <?php endif; ?>
            </a>
		</div>
	</div>
</header>
