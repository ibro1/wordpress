<?php
/**
 * Plugin Name: Dual Currency for Ultimate Multisite
 * Plugin URI: https://davebukartechnologies.com/
 * Description: Lets checkout show and charge either the site's base currency or Naira, converted at an admin-set exchange rate. Requires the Ultimate Multisite plugin to be network-active.
 * Version: 1.0.0
 * Author: Dave Bukar Technologies
 * Author URI: https://davebukartechnologies.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dual-currency
 * Network: true
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

define('DUAL_CURRENCY_VERSION', '1.0.0');
define('DUAL_CURRENCY_DIR', plugin_dir_path(__FILE__));
define('DUAL_CURRENCY_ALT', 'NGN');

/**
 * Ultimate Multisite loads on plugins_loaded (default priority), so we wait
 * for it too, then bail out cleanly with an admin notice instead of a fatal
 * error if it isn't active.
 */
add_action('plugins_loaded', 'dual_currency_bootstrap', 20);

function dual_currency_bootstrap() {
	if ( ! function_exists('wu_register_settings_field')) {
		add_action('admin_notices', 'dual_currency_missing_dependency_notice');
		return;
	}

	require_once DUAL_CURRENCY_DIR . 'includes/class-dual-currency.php';

	Dual_Currency\Dual_Currency::get_instance();
}

function dual_currency_missing_dependency_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__('Dual Currency is active but Ultimate Multisite itself is not - network-activate Ultimate Multisite first.', 'dual-currency') .
		'</p></div>';
}

/**
 * Public helpers - the stable contract other plugins/theme code (the
 * register-page redesign, the Paystack/NOWPayments gateways) build on top
 * of, so none of them need to know about the Dual_Currency class directly.
 */

/**
 * The site's base currency - whatever Ultimate Multisite's own global
 * "currency_symbol" setting is set to (products/plans are priced in this
 * currency; it is never converted).
 *
 * @return string
 */
function dual_currency_get_base_currency() {
	return strtoupper((string) wu_get_setting('currency_symbol', 'USD'));
}

/**
 * The one alternate currency this plugin supports converting to, in v1.
 *
 * @return string
 */
function dual_currency_get_alt_currency() {
	return DUAL_CURRENCY_ALT;
}

/**
 * Whether switching currency is available at all right now - false when the
 * site's base currency already IS the alt currency (nothing to convert to),
 * or when the admin hasn't set a usable exchange rate yet.
 *
 * @return bool
 */
function dual_currency_is_active() {
	if ( ! class_exists(Dual_Currency\Dual_Currency::class)) {
		return false;
	}

	return Dual_Currency\Dual_Currency::get_instance()->is_active();
}

/**
 * The currency the current visitor/customer has selected (or would be
 * defaulted to): the base currency or DUAL_CURRENCY_ALT.
 *
 * @param \WP_Ultimo\Checkout\Checkout|null $checkout Optional in-progress
 *   checkout instance, so mid-flow steps (retry/renewal) prefer whatever
 *   was already stored in the signup session over a fresh cookie/geo read.
 * @return string
 */
function dual_currency_get_selected_currency($checkout = null) {
	if ( ! class_exists(Dual_Currency\Dual_Currency::class)) {
		return dual_currency_get_base_currency();
	}

	return Dual_Currency\Dual_Currency::get_instance()->resolve_currency($checkout);
}

/**
 * Converts a base-currency amount into $target_currency (defaults to the
 * visitor's selected currency) using the admin-configured exchange rate.
 * Returns the amount unchanged if no conversion is needed or possible.
 *
 * @param float       $amount
 * @param string|null $target_currency
 * @return float
 */
function dual_currency_convert_amount($amount, $target_currency = null) {
	if ( ! class_exists(Dual_Currency\Dual_Currency::class)) {
		return $amount;
	}

	$target_currency = $target_currency ?: dual_currency_get_selected_currency();

	return Dual_Currency\Dual_Currency::get_instance()->convert_amount($amount, $target_currency);
}
