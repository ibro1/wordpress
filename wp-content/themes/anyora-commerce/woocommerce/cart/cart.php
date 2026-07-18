<?php
/**
 * Anyora Commerce - Custom Cart Page Template
 * Overrides: woocommerce/cart/cart.php
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<!-- Checkout Progress Steps -->
<div class="anyora-checkout-steps">
    <div class="step active">
        <div class="step-bubble">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <span class="step-label">Cart</span>
    </div>
    <div class="step-line"></div>
    <div class="step">
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

<div class="anyora-cart-grid">

    <!-- LEFT: Cart Items -->
    <div class="anyora-cart-items">

        <?php do_action( 'woocommerce_before_cart_table' ); ?>

        <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

            <!-- Column Headers -->
            <div class="anyora-cart-header">
                <span class="col-product">Product</span>
                <span class="col-price">Unit Price</span>
                <span class="col-qty">Quantity</span>
                <span class="col-total">Total</span>
            </div>

            <?php do_action( 'woocommerce_before_cart_contents' ); ?>

            <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
                $visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) :
                    $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                    $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                    $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
                    $max_qty           = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
            ?>

            <div class="anyora-cart-row <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

                <!-- Remove Button -->
                <div class="col-remove">
                    <?php echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                        '<a href="%s" class="anyora-remove-btn" aria-label="Remove %s">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </a>',
                        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                        wp_strip_all_tags( $product_name )
                    ), $cart_item_key ); ?>
                </div>

                <!-- Product Info -->
                <div class="col-product">
                    <div class="anyora-cart-product">
                        <div class="anyora-cart-thumb">
                            <?php if ( $product_permalink ) : ?>
                                <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; ?></a>
                            <?php else : echo $thumbnail; endif; ?>
                        </div>
                        <div class="anyora-cart-meta">
                            <span class="anyora-cart-product-name">
                                <?php if ( $product_permalink ) : ?>
                                    <a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
                                <?php else : echo wp_kses_post( $product_name ); endif; ?>
                            </span>
                            <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
                            <?php if ( $_product->get_sku() ) : ?>
                                <span class="anyora-cart-sku">SKU: <?php echo esc_html( $_product->get_sku() ); ?></span>
                            <?php endif; ?>
                            <?php if ( $_product->is_in_stock() ) : ?>
                                <span class="anyora-in-stock">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="#2e7d32"><circle cx="12" cy="12" r="10"/></svg>
                                    In Stock
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Price -->
                <div class="col-price" data-label="Unit Price">
                    <?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
                </div>

                <!-- Quantity -->
                <div class="col-qty" data-label="Qty">
                    <?php if ( $_product->is_sold_individually() ) : ?>
                        <span class="anyora-cart-qty-fixed">1</span>
                        <input type="hidden" name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]" value="1">
                    <?php else : ?>
                        <div class="anyora-qty-stepper">
                            <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity">−</button>
                            <input type="number"
                                name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]"
                                class="qty-input"
                                value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
                                min="0"
                                max="<?php echo esc_attr( $max_qty > 0 ? $max_qty : 999 ); ?>"
                                step="1">
                            <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity">+</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Subtotal -->
                <div class="col-total" data-label="Total">
                    <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
                </div>

            </div>

            <?php endif; endforeach; ?>

            <?php do_action( 'woocommerce_cart_contents' ); ?>

            <!-- Cart Actions Row -->
            <div class="anyora-cart-actions">
                <?php if ( wc_coupons_enabled() ) : ?>
                <div class="anyora-coupon-wrap">
                    <button type="button" class="anyora-coupon-toggle" id="coupon-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Have a promo code?
                        <svg class="toggle-chevron" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="anyora-coupon-panel" id="coupon-panel">
                        <div class="anyora-coupon-input-row">
                            <input type="text" name="coupon_code" class="anyora-coupon-input" id="coupon_code" placeholder="Enter promo code" />
                            <button type="submit" class="anyora-coupon-btn" name="apply_coupon" value="Apply">Apply</button>
                        </div>
                    </div>
                    <?php do_action( 'woocommerce_cart_coupon' ); ?>
                </div>
                <?php endif; ?>

                <div class="anyora-cart-action-right">
                    <button type="submit" class="anyora-update-cart-btn" name="update_cart" value="update">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                        Update Cart
                    </button>
                    <?php do_action( 'woocommerce_cart_actions' ); ?>
                    <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                </div>
            </div>

            <?php do_action( 'woocommerce_after_cart_contents' ); ?>
        </form>

        <?php do_action( 'woocommerce_after_cart_table' ); ?>
    </div>

    <!-- RIGHT: Cart Totals -->
    <div class="anyora-cart-sidebar">
        <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
        <div class="cart-collaterals">
            <?php do_action( 'woocommerce_cart_collaterals' ); ?>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Custom +/- steppers
    document.querySelectorAll('.anyora-qty-stepper').forEach(function (stepper) {
        var input = stepper.querySelector('.qty-input');
        var minus = stepper.querySelector('.qty-minus');
        var plus  = stepper.querySelector('.qty-plus');
        var max   = parseInt(input.getAttribute('max')) || 999;

        minus.addEventListener('click', function () {
            var val = parseInt(input.value) || 1;
            if (val > 0) { input.value = val - 1; input.dispatchEvent(new Event('change')); }
        });
        plus.addEventListener('click', function () {
            var val = parseInt(input.value) || 0;
            if (val < max) { input.value = val + 1; input.dispatchEvent(new Event('change')); }
        });
    });

    // Coupon accordion toggle
    var toggle = document.getElementById('coupon-toggle');
    var panel  = document.getElementById('coupon-panel');
    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            panel.classList.toggle('open');
            toggle.classList.toggle('open');
        });
    }
});
</script>

<?php do_action( 'woocommerce_after_cart' ); ?>
