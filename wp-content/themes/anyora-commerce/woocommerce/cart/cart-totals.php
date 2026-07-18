<?php
/**
 * Anyora Commerce - Custom Cart Totals Template
 * Overrides: woocommerce/cart/cart-totals.php
 */

defined( 'ABSPATH' ) || exit;

$cart_subtotal_raw = WC()->cart->get_subtotal();
$free_ship_threshold = 50; // £50 free shipping threshold
$remaining = max( 0, $free_ship_threshold - $cart_subtotal_raw );
$progress_pct = min( 100, ( $cart_subtotal_raw / $free_ship_threshold ) * 100 );
?>
<div class="anyora-cart-totals">

    <h3 class="anyora-totals-title">Order Summary</h3>

    <!-- Free Shipping Progress Bar -->
    <div class="anyora-shipping-progress">
        <?php if ( $remaining > 0 ) : ?>
            <div class="shipping-progress-msg">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Add <strong><?php echo wc_price( $remaining ); ?></strong> more for free shipping!
            </div>
        <?php else : ?>
            <div class="shipping-progress-msg achieved">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <strong>You qualify for free shipping!</strong> 🎉
            </div>
        <?php endif; ?>
        <div class="shipping-progress-bar-bg">
            <div class="shipping-progress-bar-fill" style="width: <?php echo esc_attr( $progress_pct ); ?>%"></div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="anyora-totals-rows">
        <div class="anyora-totals-row">
            <span class="label"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
            <span class="value"><?php wc_cart_totals_subtotal_html(); ?></span>
        </div>

        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
        <div class="anyora-totals-row anyora-coupon-row">
            <span class="label coupon-code-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                <?php echo esc_html( strtoupper( $code ) ); ?>
                <a href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_cart_url() ) ); ?>" class="anyora-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>">&times;</a>
            </span>
            <span class="value coupon-value"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
        </div>
        <?php endforeach; ?>

        <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
        <div class="anyora-totals-row">
            <span class="label"><?php echo esc_html( $fee->name ); ?></span>
            <span class="value"><?php wc_cart_totals_fee_html( $fee ); ?></span>
        </div>
        <?php endforeach; ?>

        <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
        <div class="anyora-totals-row anyora-shipping-row">
            <span class="label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
            <span class="value"><?php woocommerce_shipping_calculator(); ?></span>
        </div>
        <?php endif; ?>

        <?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>
    </div>

    <!-- Grand Total -->
    <div class="anyora-totals-grand">
        <div>
            <span class="grand-label"><?php esc_html_e( 'Total', 'woocommerce' ); ?></span>
            <span class="grand-tax-note"><?php echo WC()->countries->inc_tax_or_vat(); ?></span>
        </div>
        <span class="grand-value"><?php wc_cart_totals_order_total_html(); ?></span>
    </div>

    <?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>

    <!-- Checkout CTA -->
    <div class="anyora-checkout-cta">
        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="anyora-checkout-btn">
            Proceed to Checkout
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
        <a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>" class="anyora-continue-link">
            &larr; Continue Shopping
        </a>
    </div>

    <!-- Payment Icons -->
    <div class="anyora-payment-icons">
        <span class="payment-icons-label">Accepted payments</span>
        <div class="payment-icons-row">
            <svg class="payment-icon" viewBox="0 0 60 38" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="38" rx="5" fill="#1A1F71"/><text x="30" y="24" text-anchor="middle" font-family="Arial" font-weight="bold" font-size="11" fill="white">VISA</text></svg>
            <svg class="payment-icon" viewBox="0 0 60 38" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="38" rx="5" fill="#fff" stroke="#e0e0e0"/><circle cx="23" cy="19" r="10" fill="#EB001B"/><circle cx="37" cy="19" r="10" fill="#F79E1B"/><path d="M30 11.3a10 10 0 010 15.4A10 10 0 0130 11.3z" fill="#FF5F00"/></svg>
            <svg class="payment-icon" viewBox="0 0 60 38" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="38" rx="5" fill="#003087"/><text x="30" y="24" text-anchor="middle" font-family="Arial" font-weight="bold" font-size="9" fill="white">PayPal</text></svg>
            <svg class="payment-icon" viewBox="0 0 60 38" xmlns="http://www.w3.org/2000/svg"><rect width="60" height="38" rx="5" fill="#000"/><text x="30" y="26" text-anchor="middle" font-family="Arial" font-weight="bold" font-size="14" fill="white">⬛ Pay</text></svg>
        </div>
    </div>

    <!-- Trust signals -->
    <div class="anyora-mini-trust">
        <div class="mini-trust-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            256-bit SSL Encrypted Checkout
        </div>
        <div class="mini-trust-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
            30-Day Hassle-Free Returns
        </div>
        <div class="mini-trust-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6fbdbd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Fast UK Tracked Dispatch
        </div>
    </div>

</div>
