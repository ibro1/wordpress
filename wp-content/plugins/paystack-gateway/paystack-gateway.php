<?php
/**
 * Plugin Name: Paystack Gateway for Ultimate Multisite
 * Plugin URI: https://davebukartechnologies.com/
 * Description: Adds Paystack as a payment gateway option for Ultimate Multisite (checkout, webhook, refunds). Requires the Ultimate Multisite plugin to be network-active.
 * Version: 1.0.0
 * Author: Dave Bukar Technologies
 * Author URI: https://davebukartechnologies.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paystack-gateway
 * Network: true
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

define('PAYSTACK_GATEWAY_VERSION', '1.0.0');
define('PAYSTACK_GATEWAY_DIR', plugin_dir_path(__FILE__));

/**
 * Ultimate Multisite loads late enough (plugins_loaded, default priority)
 * that we wait for it too, then bail out cleanly with an admin notice
 * instead of a fatal error if it isn't active - this addon is useless
 * without it, but shouldn't be able to break the site if deactivated.
 */
add_action('plugins_loaded', 'paystack_gateway_bootstrap', 20);

function paystack_gateway_bootstrap() {
	if ( ! class_exists('\WP_Ultimo\Gateways\Base_Gateway')) {
		add_action('admin_notices', 'paystack_gateway_missing_dependency_notice');
		return;
	}

	require_once PAYSTACK_GATEWAY_DIR . 'includes/class-paystack-gateway.php';

	add_action('wu_register_gateways', 'paystack_gateway_register');
}

function paystack_gateway_register() {
	wu_register_gateway(
		'paystack',
		__('Paystack', 'paystack-gateway'),
		__('Accept card, bank transfer, and USSD payments via Paystack.', 'paystack-gateway'),
		\PaystackGateway\Paystack_Gateway::class
	);
}

function paystack_gateway_missing_dependency_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__('Paystack Gateway for Ultimate Multisite is active but Ultimate Multisite itself is not - network-activate Ultimate Multisite first.', 'paystack-gateway') .
		'</p></div>';
}
