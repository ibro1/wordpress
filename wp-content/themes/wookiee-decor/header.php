<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="announcement-bar">
	<p>&pound;<?php echo esc_html( wookiee_get_setting( 'shipping_rate' ) ); ?> UK shipping &nbsp;&middot;&nbsp; 30-day hassle-free returns &nbsp;&middot;&nbsp; Secure checkout</p>
</div>

<header class="site-header">
	<div class="container header-inner">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<div class="site-logo-link site-logo-custom"><?php the_custom_logo(); ?></div>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo-link" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none" class="site-logo-svg">
						<path d="M8 8 L12 30 L17.5 17 L23 30 L27 8" stroke="#1a1614" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
						<rect x="10" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
						<rect x="20.5" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
						<line x1="11" y1="13" x2="13.5" y2="13" stroke="#1a1614" stroke-width="1.2" stroke-linecap="round"/>
						<line x1="21.5" y1="13" x2="24" y2="13" stroke="#1a1614" stroke-width="1.2" stroke-linecap="round"/>
						<text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="#1a1614" letter-spacing="-0.5px"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></text>
					</svg>
				</a>
			<?php endif; ?>
		</div>

		<nav class="main-navigation" id="main-navigation" aria-label="Primary">
			<div class="mobile-nav-head">
				<span class="mobile-nav-brand">Menu</span>
				<button type="button" id="close-mobile-nav" class="mobile-nav-close" aria-label="Close menu">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
			</div>
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
			<button id="toggle-search" class="header-icon-btn" aria-label="Search">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
			</button>
			<a href="<?php echo class_exists( 'WooCommerce' ) ? esc_url( wc_get_cart_url() ) : '#'; ?>" class="header-icon-btn cart-icon-btn" aria-label="Cart">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<span class="cart-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
				<?php endif; ?>
			</a>
			<button id="toggle-mobile-nav" class="header-icon-btn mobile-nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-navigation">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
			</button>
		</div>
	</div>

	<div class="header-search-bar" id="header-search-bar">
		<div class="container header-search-inner">
			<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="header-search-form">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b6058" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				<input type="text" name="s" placeholder="Search for products, categories..." autocomplete="off" id="search-input">
				<input type="hidden" name="post_type" value="product">
			</form>
			<button type="button" id="close-search" class="header-search-close" aria-label="Close search">&times;</button>
		</div>
	</div>
</header>

<div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
