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
<body <?php body_class( 'anyora-cart-funnel-page' ); ?>>

    <!-- Cart Page Header -->
    <header class="checkout-header">
        <div class="checkout-header-inner">
            <div class="checkout-header-left">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="checkout-logo-link">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 38" width="150" height="34" fill="none">
                        <path d="M8 30 L18 8 L28 30" stroke="#081d34" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="12" y1="20" x2="24" y2="20" stroke="#6fbdbd" stroke-width="2.5" stroke-linecap="round"/>
                        <line x1="9.5" y1="25.5" x2="26.5" y2="25.5" stroke="#6fbdbd" stroke-width="2.5" stroke-linecap="round"/>
                        <rect x="14" y="21" width="8" height="4.5" rx="1" fill="#081d34"/>
                        <rect x="16.5" y="15.5" width="5" height="4.5" rx="1" fill="#6fbdbd"/>
                        <text x="36" y="28" font-family="'Outfit', 'Inter', system-ui, sans-serif" font-weight="800" font-size="22" fill="#081d34" letter-spacing="-0.5px">anyora</text>
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

                <div class="anyora-cart-layout">
                    <!-- Cart Items Section -->
                    <div class="anyora-cart-items-col">
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
                <div class="anyora-empty-cart">
                    <div class="empty-cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none">
                            <circle cx="40" cy="40" r="40" fill="#f4f5f0"/>
                            <path d="M22 26h4l5 22h18l4-16H29" stroke="#081d34" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="35" cy="54" r="2.5" fill="#6fbdbd"/>
                            <circle cx="47" cy="54" r="2.5" fill="#6fbdbd"/>
                            <path d="M33 36h14M33 41h10" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h1 class="empty-cart-title">Your cart is empty</h1>
                    <p class="empty-cart-subtitle">Looks like you haven't added anything to your cart yet. Discover our premium collection and find something you'll love.</p>
                    <div class="empty-cart-actions">
                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="anyora-btn-primary">
                            Browse All Products
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="anyora-btn-outline">
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
                    <span class="trust-text">Customer Support: support@anyora.uk</span>
                </div>
            </div>
            <p class="checkout-copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
