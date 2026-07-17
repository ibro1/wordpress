<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" style="position: relative;">
	<div class="container header-inner">
		<div class="site-branding" style="display: flex; align-items: center;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display: flex; align-items: center;">
				<img src="/wp-content/themes/anyora-commerce/assets/images/anyora-logo.png" alt="Anyora Logo" style="height: 28px; width: auto; display: block;">
			</a>
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
		<div class="header-actions" style="display: flex; align-items: center; gap: 20px;">
			<button id="toggle-search" class="header-icon-btn" aria-label="Search" onclick="var bar = document.getElementById('header-search-bar'); bar.style.display = bar.style.display === 'none' || bar.style.display === '' ? 'block' : 'none'; if(bar.style.display === 'block') { document.getElementById('search-input').focus(); } return false;" style="background: none; border: none; cursor: pointer; color: var(--anyora-navy); padding: 0; display: flex; align-items: center; transition: opacity 0.2s;">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
			</button>
			<a href="<?php echo class_exists( 'WooCommerce' ) ? esc_url( wc_get_cart_url() ) : '#'; ?>" class="header-icon-btn cart-icon-btn" aria-label="Cart" style="color: var(--anyora-navy); display: flex; align-items: center; position: relative; transition: opacity 0.2s;">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<span class="cart-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</div>
	
	<!-- Search Bar inside header -->
	<div id="header-search-bar" style="display: none; background: #ffffff; border-top: 1px solid var(--anyora-border); padding: 20px 0; position: absolute; width: 100%; left: 0; top: 100%; z-index: 99; box-shadow: 0 10px 30px rgba(0,0,0,0.08); box-sizing: border-box;">
		<div style="max-width: 800px; margin: 0 auto; display: flex; align-items: center; gap: 20px; padding: 0 20px; box-sizing: border-box;">
			<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" style="flex: 1; display: flex; align-items: center; gap: 15px; margin: 0; background: #f4f5f0; padding: 12px 20px; border-radius: 30px;">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				<input type="text" name="s" placeholder="Search for products, categories..." style="flex: 1; border: none; outline: none; font-size: 15px; font-family: var(--font-primary); color: var(--anyora-text); background: transparent;" required autocomplete="off" id="search-input">
				<input type="hidden" name="post_type" value="product">
			</form>
			<button id="close-search" onclick="document.getElementById('header-search-bar').style.display='none'; return false;" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #888; padding: 0; line-height: 1; display: flex; align-items: center; transition: color 0.2s;">&times;</button>
		</div>
	</div>
</header>
