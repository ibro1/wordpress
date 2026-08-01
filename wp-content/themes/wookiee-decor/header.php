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
	<?php
	/*
	 * The shipping price now lives here rather than on a badge pinned over the
	 * hero image, which is where review asked for it: an announcement bar is
	 * the conventional place for a delivery line, and the hero is stronger
	 * without a price plate covering the photograph. Still one fact in one
	 * place - the trust bar below carries dispatch time, not this.
	 */
	$wookiee_bar_rate = trim( (string) wookiee_get_setting( 'shipping_rate' ) );
	$wookiee_bar_days = trim( (string) wookiee_get_setting( 'returns_period_days' ) );

	$wookiee_bar = array();
	if ( '' !== $wookiee_bar_rate ) {
		$wookiee_bar[] = 'Flat-rate UK delivery £' . $wookiee_bar_rate;
	}
	if ( '' !== $wookiee_bar_days ) {
		$wookiee_bar[] = $wookiee_bar_days . '-day returns';
	}
	$wookiee_bar[] = 'Secure checkout';
	?>
	<p><?php echo esc_html( implode( '  ·  ', $wookiee_bar ) ); ?></p>
</div>

<header class="site-header">
	<div class="container header-inner">
		<div class="site-branding">
			<?php get_template_part( 'template-parts/site-logo', null, array( 'link_class' => 'site-logo-link' ) ); ?>
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
				/*
				 * No menu assigned to the location yet. This fallback was a
				 * hardcoded Home/Shop/About - which is why Contact was missing
				 * from the nav even when the menu itself had it: the frontend
				 * was never reading the menu at all. Built from the starter
				 * pages now, so it cannot drift from them again.
				 */
				echo '<ul>';
				foreach ( wookiee_starter_pages() as $wookiee_slug => $wookiee_page ) {
					if ( '' === $wookiee_page['menu'] ) {
						continue;
					}
					// A fresh install has no pages until onboarding creates
					// them; linking to slugs that do not exist yet would put
					// four 404s in the header of every new site.
					if ( 'home' !== $wookiee_slug && ! get_page_by_path( $wookiee_slug, OBJECT, 'page' ) ) {
						continue;
					}
					$wookiee_url = 'home' === $wookiee_slug ? home_url( '/' ) : home_url( '/' . $wookiee_slug . '/' );
					printf(
						'<li><a href="%s">%s</a></li>',
						esc_url( $wookiee_url ),
						esc_html( $wookiee_page['menu'] )
					);
				}
				echo '</ul>';
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
