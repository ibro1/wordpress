/* eslint-disable */
/* global wu_stripe_checkout */

const stripeCheckout = function() {

  wp.hooks.addAction('wu_on_form_success', 'nextpress/wp-ultimo', function(checkout, results) {

    if (checkout.gateway === 'stripe-checkout' && results.gateway.slug !== 'free' && results.gateway.data && results.gateway.data.stripe_session_id) {

      // Prevent redirect to thank you page
      // set_prevent_submission not work in this case
      checkout.prevent_submission = true;

      // Prefer the direct session URL returned by the server (modern Stripe approach).
      // Fall back to constructing the URL from the session ID for backwards compatibility.
      var checkoutUrl = results.gateway.data.stripe_checkout_url;

      if (checkoutUrl) {
        window.location.href = checkoutUrl;
      } else {
        // Fallback: construct the Stripe Checkout URL from the session ID.
        window.location.href = 'https://checkout.stripe.com/pay/' + results.gateway.data.stripe_session_id;
      }

    } // end if;

  });

};

/**
 * Initializes the Stripe checkout onto the checkout form on load.
 */
wp.hooks.addAction('wu_checkout_loaded', 'nextpress/wp-ultimo', function() {

  stripeCheckout();

});