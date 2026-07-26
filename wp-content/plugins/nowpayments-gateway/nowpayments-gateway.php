<?php
/**
 * Plugin Name: NOWPayments Gateway for Ultimate Multisite
 * Plugin URI: https://davebukartechnologies.com/
 * Description: Adds NOWPayments as a crypto payment gateway option for Ultimate Multisite (hosted invoice checkout, IPN webhook). Requires the Ultimate Multisite plugin to be network-active.
 * Version: 1.0.0
 * Author: Dave Bukar Technologies
 * Author URI: https://davebukartechnologies.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nowpayments-gateway
 * Network: true
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

define('NOWPAYMENTS_GATEWAY_VERSION', '1.0.0');
define('NOWPAYMENTS_GATEWAY_DIR', plugin_dir_path(__FILE__));

/**
 * Ultimate Multisite loads late enough (plugins_loaded, default priority)
 * that we wait for it too, then bail out cleanly with an admin notice
 * instead of a fatal error if it isn't active - this addon is useless
 * without it, but shouldn't be able to break the site if deactivated.
 */
add_action('plugins_loaded', 'nowpayments_gateway_bootstrap', 20);

function nowpayments_gateway_bootstrap() {
	if ( ! class_exists('\WP_Ultimo\Gateways\Base_Gateway')) {
		add_action('admin_notices', 'nowpayments_gateway_missing_dependency_notice');
		return;
	}

	require_once NOWPAYMENTS_GATEWAY_DIR . 'includes/class-nowpayments-gateway.php';

	add_action('wu_register_gateways', 'nowpayments_gateway_register');
}

function nowpayments_gateway_register() {
	wu_register_gateway(
		'nowpayments',
		__('Crypto (NOWPayments)', 'nowpayments-gateway'),
		__('Accept Bitcoin, Ethereum, USDT, and 300+ other cryptocurrencies via NOWPayments.', 'nowpayments-gateway'),
		\NOWPaymentsGateway\NOWPayments_Gateway::class
	);
}

function nowpayments_gateway_missing_dependency_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__('NOWPayments Gateway for Ultimate Multisite is active but Ultimate Multisite itself is not - network-activate Ultimate Multisite first.', 'nowpayments-gateway') .
		'</p></div>';
}
