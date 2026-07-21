<?php
/**
 * Wookiee Decor - Custom Empty Cart Template
 * Overrides: woocommerce/cart/cart-empty.php
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );
?>

<div class="wookiee-empty-cart">
    <div class="empty-cart-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
            <circle cx="50" cy="50" r="50" fill="#ece2d3"/>
            <path d="M28 34h6l6 28h22l5-20H36" stroke="#1a1614" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="44" cy="68" r="3" fill="#c1704a"/>
            <circle cx="58" cy="68" r="3" fill="#c1704a"/>
            <path d="M40 46h20M40 53h14" stroke="#c1704a" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    </div>
    <h2 class="empty-cart-title">Your cart is empty</h2>
    <p class="empty-cart-subtitle">Looks like you haven't added anything yet. Explore our premium collection and discover something you'll love.</p>
    <div class="empty-cart-actions">
        <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="wookiee-btn-primary">
            Browse All Products
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="wookiee-btn-outline">
            Back to Home
        </a>
    </div>
    <div class="empty-cart-features">
        <div class="empty-cart-feature"><span class="feature-icon">🚚</span> <span>Free shipping over £50</span></div>
        <div class="empty-cart-feature"><span class="feature-icon">🔄</span> <span>30-day easy returns</span></div>
        <div class="empty-cart-feature"><span class="feature-icon">🔒</span> <span>Secure checkout</span></div>
    </div>
</div>
