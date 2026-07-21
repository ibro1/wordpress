<?php
/**
 * Template Name: Custom Cart Template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'wookiee-cart-funnel-page' ); ?>>

    <!-- Cart Page Header -->
    <header class="checkout-header">
        <div class="checkout-header-inner">
            <div class="checkout-header-left">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="checkout-logo-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none">
					<path d="M8 8 L12 30 L17.5 17 L23 30 L27 8" stroke="#1a1614" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
					<rect x="10" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
					<rect x="20.5" y="11" width="4.5" height="4" rx="1" fill="#c1704a"/>
					<line x1="11" y1="13" x2="13.5" y2="13" stroke="#1a1614" stroke-width="1.2" stroke-linecap="round"/>
					<line x1="21.5" y1="13" x2="24" y2="13" stroke="#1a1614" stroke-width="1.2" stroke-linecap="round"/>
					<text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="#1a1614" letter-spacing="-0.5px">Wookiee</text>
				</svg>
                </a>
                <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="back-to-cart-link">
                    &larr; Continue Shopping
                </a>
            </div>
            <div class="checkout-header-badge" style="background-color: #e3f2fd; color: #1565c0;">
                🛍️ Your Shopping Cart
            </div>
        </div>
    </header>

    <main class="checkout-main">
        <div class="checkout-content-container">

            <?php if ( class_exists('WooCommerce') && ! WC()->cart->is_empty() ) : ?>

                <div class="wookiee-cart-layout">
                    <!-- Cart Items Section -->
                    <div class="wookiee-cart-items-col">
                        <h1 class="checkout-page-title">Your Cart <span class="cart-item-count">(<?php echo WC()->cart->get_cart_contents_count(); ?> <?php echo WC()->cart->get_cart_contents_count() === 1 ? 'item' : 'items'; ?>)</span></h1>
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            the_content();
                        endwhile;
                        ?>
                    </div>
                </div>

            <?php else : ?>

                <!-- Beautiful Empty Cart State -->
                <div class="wookiee-empty-cart">
                    <div class="empty-cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none">
                            <circle cx="40" cy="40" r="40" fill="#ece2d3"/>
                            <path d="M22 26h4l5 22h18l4-16H29" stroke="#1a1614" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="35" cy="54" r="2.5" fill="#c1704a"/>
                            <circle cx="47" cy="54" r="2.5" fill="#c1704a"/>
                            <path d="M33 36h14M33 41h10" stroke="#c1704a" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h1 class="empty-cart-title">Your cart is empty</h1>
                    <p class="empty-cart-subtitle">Looks like you haven't added anything to your cart yet. Discover our premium collection and find something you'll love.</p>
                    <div class="empty-cart-actions">
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wookiee-btn-primary">
                            Browse All Products
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wookiee-btn-outline">
                            Back to Home
                        </a>
                    </div>
                    <div class="empty-cart-features">
                        <div class="empty-cart-feature">
                            <span class="feature-icon">🚚</span>
                            <span>Free shipping on orders over £50</span>
                        </div>
                        <div class="empty-cart-feature">
                            <span class="feature-icon">🔄</span>
                            <span>30-day easy returns</span>
                        </div>
                        <div class="empty-cart-feature">
                            <span class="feature-icon">🔒</span>
                            <span>Secure checkout</span>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <!-- Cart Footer -->
    <footer class="checkout-footer">
        <div class="checkout-footer-inner">
            <div class="checkout-trust-signals">
                <div class="trust-signal">
                    <span class="trust-icon">🚚</span>
                    <span class="trust-text">Fast & Tracked Shipping</span>
                </div>
                <div class="trust-signal">
                    <span class="trust-icon">🔄</span>
                    <span class="trust-text">30-Day Money Back Guarantee</span>
                </div>
                <div class="trust-signal">
                    <span class="trust-icon">✉️</span>
                    <span class="trust-text">Customer Support: info@wookied.com</span>
                </div>
            </div>
            <p class="checkout-copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
