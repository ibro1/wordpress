<?php
/**
 * Template Name: Custom Checkout Template
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'anyora-checkout-funnel-page' ); ?>>

    <!-- Custom Checkout Header -->
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
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="back-to-cart-link">
                    &larr; Back to Cart
                </a>
            </div>
            <div class="checkout-header-badge">
                🔒 Secure 256-bit SSL Checkout
            </div>
        </div>
    </header>

    <main class="checkout-main">
        <div class="checkout-content-container">
            <!-- Checkout Progress Steps -->
            <div class="anyora-checkout-steps">
                <div class="step done">
                    <div class="step-bubble">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="step-label">Cart</span>
                </div>
                <div class="step-line"></div>
                <div class="step active">
                    <div class="step-bubble">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <span class="step-label">Checkout</span>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-bubble">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <span class="step-label">Confirm</span>
                </div>
            </div>
            <h1 class="checkout-page-title">Checkout</h1>
            <?php
            while ( have_posts() ) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
    </main>

    <!-- Custom Checkout Footer -->
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
