<?php
/**
 * Paystack Gateway for Ultimate Multisite.
 *
 * Redirect-based flow, same shape as the bundled PayPal REST gateway
 * (inc/gateways/class-paypal-rest-gateway.php): initialize a transaction,
 * redirect the customer to Paystack's hosted page, verify on return, and
 * again independently on webhook (idempotent either way).
 *
 * @package PaystackGateway
 */

namespace PaystackGateway;

use WP_Ultimo\Gateways\Base_Gateway;
use WP_Ultimo\Gateways\Ignorable_Exception;
use WP_Ultimo\Database\Payments\Payment_Status;
use WP_Ultimo\Database\Memberships\Membership_Status;

defined('ABSPATH') || exit;

/**
 * Do NOT add PHP return type declarations to the methods below that
 * override Base_Gateway - the base class's own docblock explicitly warns
 * that doing so can fatally break external addons if a future core
 * release adds/changes a return type on the abstract method. @return
 * PHPDoc tags only.
 */
class Paystack_Gateway extends Base_Gateway {

	/**
	 * @var string
	 */
	protected $id = 'paystack';

	/**
	 * @var bool
	 */
	protected $sandbox_mode = true;

	/**
	 * @var string
	 */
	protected $secret_key = '';

	/**
	 * @var string
	 */
	protected $public_key = '';

	const API_BASE = 'https://api.paystack.co';

	/**
	 * Loads whichever key pair (test/live) is active, same naming
	 * convention as the Stripe gateways: {id}_sandbox_mode,
	 * {id}_{test|live}_{secret|public}_key.
	 *
	 * @return void
	 */
	public function init() {

		$this->sandbox_mode = (bool) wu_get_setting('paystack_sandbox_mode', true);

		$mode = $this->sandbox_mode ? 'test' : 'live';

		$this->secret_key = trim((string) wu_get_setting("paystack_{$mode}_secret_key", ''));
		$this->public_key = trim((string) wu_get_setting("paystack_{$mode}_public_key", ''));
	}

	/**
	 * The settings-field system has no 'password' field type (only
	 * 'text' has a template - views/settings/fields/field-text.php - and
	 * the field's declared type is used both to pick that template AND
	 * as the rendered <input>'s literal HTML type attribute, so the two
	 * can't be decoupled from PHP alone without a template that doesn't
	 * exist). Masking the secret-key inputs is done in JS instead, only
	 * on this plugin's own settings screen - the public keys are left as
	 * plain text since they're designed to be exposed client-side and
	 * masking them would just be friction with no security benefit.
	 *
	 * @return void
	 */
	public function hooks() {

		add_action('admin_footer', [$this, 'render_secret_key_mask_script']);
	}

	/**
	 * @return void
	 */
	public function render_secret_key_mask_script() {

		if ('wp-ultimo-settings' !== wu_request('page')) {
			return;
		}
		?>
		<script>
		(function () {
			['paystack_test_secret_key', 'paystack_live_secret_key'].forEach(function (id) {
				var input = document.getElementById(id);
				if (!input) {
					return;
				}
				input.type = 'password';
				var toggle = document.createElement('button');
				toggle.type = 'button';
				toggle.textContent = 'Show';
				toggle.style.marginLeft = '8px';
				toggle.className = 'button button-secondary';
				toggle.addEventListener('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					var revealed = input.getAttribute('type') === 'text';
					input.setAttribute('type', revealed ? 'password' : 'text');
					toggle.textContent = revealed ? 'Show' : 'Hide';
				});
				input.parentNode.insertBefore(toggle, input.nextSibling);
			});
		})();
		</script>
		<?php
	}

	/**
	 * @return void
	 */
	public function settings() {

		wu_register_settings_field(
			'payment-gateways',
			'paystack_header',
			[
				'title'           => __('Paystack', 'paystack-gateway'),
				'desc'            => __('Use the settings section below to configure Paystack as a payment method.', 'paystack-gateway'),
				'type'            => 'header',
				'show_as_submenu' => true,
				'require'         => [
					'active_gateways' => 'paystack',
				],
			]
		);

		/*
		 * The toggle's true-value/false-value are '1'/'0' (strings), and
		 * assets/js/vue-apps.js's require() does a strict === comparison
		 * - so every `require: ['..._sandbox_mode' => 1]` (integer) below
		 * would silently never match, permanently hiding the fields it
		 * gates. This affects Stripe/PayPal's own key fields the same
		 * way (they use the same integer convention); using string '1'/'0'
		 * here instead sidesteps that bug rather than reproducing it.
		 */
		wu_register_settings_field(
			'payment-gateways',
			'paystack_sandbox_mode',
			[
				'title'     => __('Paystack Sandbox Mode', 'paystack-gateway'),
				'desc'      => __('Toggle this to put Paystack on test mode. Turn off only once you have confirmed real test transactions complete correctly.', 'paystack-gateway'),
				'type'      => 'toggle',
				'default'   => '1',
				'html_attr' => [
					'v-model' => 'paystack_sandbox_mode',
				],
				'require'   => [
					'active_gateways' => 'paystack',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'paystack_test_public_key',
			[
				'title'       => __('Paystack Test Public Key', 'paystack-gateway'),
				'tooltip'     => __('Make sure you are placing the TEST keys, not the live ones.', 'paystack-gateway'),
				'placeholder' => __('pk_test_***********', 'paystack-gateway'),
				'type'        => 'text',
				'default'     => '',
				'require'     => [
					'active_gateways'      => 'paystack',
					'paystack_sandbox_mode' => '1',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'paystack_test_secret_key',
			[
				'title'       => __('Paystack Test Secret Key', 'paystack-gateway'),
				'tooltip'     => __('Make sure you are placing the TEST keys, not the live ones.', 'paystack-gateway'),
				'placeholder' => __('sk_test_***********', 'paystack-gateway'),
				'type'        => 'text',
				'default'     => '',
				'require'     => [
					'active_gateways'      => 'paystack',
					'paystack_sandbox_mode' => '1',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'paystack_live_public_key',
			[
				'title'       => __('Paystack Live Public Key', 'paystack-gateway'),
				'tooltip'     => __('Make sure you are placing the LIVE keys, not the test ones.', 'paystack-gateway'),
				'placeholder' => __('pk_live_***********', 'paystack-gateway'),
				'type'        => 'text',
				'default'     => '',
				'require'     => [
					'active_gateways'      => 'paystack',
					'paystack_sandbox_mode' => '0',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'paystack_live_secret_key',
			[
				'title'       => __('Paystack Live Secret Key', 'paystack-gateway'),
				'tooltip'     => __('Make sure you are placing the LIVE keys, not the test ones.', 'paystack-gateway'),
				'placeholder' => __('sk_live_***********', 'paystack-gateway'),
				'type'        => 'text',
				'default'     => '',
				'require'     => [
					'active_gateways'      => 'paystack',
					'paystack_sandbox_mode' => '0',
				],
			]
		);

		$webhook_message = sprintf(
			'<span class="wu-p-2 wu-bg-blue-100 wu-text-blue-600 wu-rounded wu-mt-3 wu-mb-0 wu-block wu-text-xs">%s</span>',
			__('Paste this URL into your Paystack Dashboard under Settings -> API Keys & Webhooks -> Webhook URL.', 'paystack-gateway')
		);

		wu_register_settings_field(
			'payment-gateways',
			'paystack_webhook_listener_explanation',
			[
				'title'         => __('Webhook URL', 'paystack-gateway'),
				'desc'          => $webhook_message,
				'type'          => 'text-display',
				'copy'          => true,
				'display_value' => $this->get_webhook_listener_url(),
				'require'       => [
					'active_gateways' => 'paystack',
				],
			]
		);
	}

	/**
	 * Not a stored-card gateway - nothing extra to render in the
	 * checkout form itself, the redirect handles everything.
	 *
	 * @return string
	 */
	public function fields() {

		return '';
	}

	/**
	 * Paystack has no native trial-without-payment-method concept in
	 * this integration; free trials are handled the same way the Manual
	 * gateway handles them (the free gateway takes the $0 first payment).
	 *
	 * @return bool
	 */
	public function supports_free_trials() {

		return false;
	}

	/**
	 * Deliberately false for v1. Paystack's repeat-charge model
	 * ("Charge Authorization" against a saved authorization_code from a
	 * prior successful transaction) needs its own renewal-cron wiring
	 * that hasn't been built/tested yet. Claiming true here without that
	 * wiring would mean renewals silently never get charged. Until that
	 * lands, Ultimate Multisite's normal "payment due" flow applies -
	 * same honest behaviour as the bundled Manual gateway.
	 *
	 * @return bool
	 */
	public function supports_recurring() {

		return false;
	}

	/**
	 * @param \WP_Ultimo\Models\Payment    $payment
	 * @param \WP_Ultimo\Models\Membership $membership
	 * @param \WP_Ultimo\Models\Customer   $customer
	 * @param \WP_Ultimo\Checkout\Cart     $cart
	 * @param string                       $type
	 * @return bool
	 */
	public function process_checkout($payment, $membership, $customer, $cart, $type) {

		unset($membership, $cart, $type);

		$currency = strtoupper($payment->get_currency());
		$amount   = (int) round($payment->get_total() * wu_stripe_get_currency_multiplier($currency));

		// A zero-total payment (fully covered by a trial/discount) has
		// nothing for Paystack to do - let it stay pending exactly like
		// the Manual gateway does, without ever hitting the API.
		if ($amount <= 0) {
			return true;
		}

		$reference = $payment->get_hash() . '-' . time();

		$body = [
			'reference'   => $reference,
			'amount'      => $amount,
			'currency'    => $currency,
			'email'       => $customer->get_email_address(),
			'callback_url' => $this->get_confirm_url(),
			// Webhooks arrive with no query string at all, so metadata is
			// the only way process_webhooks() can find the local Payment.
			'metadata'    => [
				'payment_hash' => $payment->get_hash(),
			],
		];

		$result = $this->api_request('/transaction/initialize', $body);

		if (is_wp_error($result)) {
			$this->redirect_with_error($result->get_error_message());
			return false;
		}

		$payment->set_gateway_payment_id($reference);
		$payment->save();

		wp_redirect($result['data']['authorization_url']); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Paystack external URL
		exit;
	}

	/**
	 * Return-redirect handler - the browser lands back here after the
	 * customer completes (or abandons) the Paystack hosted page.
	 *
	 * @return void
	 */
	public function process_confirmation() {

		if ( ! $this->payment) {
			$this->redirect_with_error(__('Payment record not found.', 'paystack-gateway'));
			return;
		}

		$reference = $this->payment->get_gateway_payment_id();

		if ( ! $reference) {
			$this->redirect_with_error(__('Missing Paystack reference for this payment.', 'paystack-gateway'));
			return;
		}

		$this->verify_and_finalize($reference, $this->payment);
	}

	/**
	 * Webhook handler - Paystack POSTs here independently of (and often
	 * after) the confirm-redirect above; must be idempotent.
	 *
	 * @return void
	 * @throws Ignorable_Exception For events that aren't ours to act on.
	 */
	public function process_webhooks() {

		$raw_body  = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) : '';

		if (empty($this->secret_key) || empty($signature)) {
			throw new Ignorable_Exception('Paystack webhook: missing secret key or signature header.');
		}

		$computed = hash_hmac('sha512', $raw_body, $this->secret_key);

		if ( ! hash_equals($computed, $signature)) {
			status_header(401);
			throw new Ignorable_Exception('Paystack webhook: signature mismatch.');
		}

		$event = json_decode($raw_body, true);

		if (empty($event['event']) || 'charge.success' !== $event['event']) {
			// Not an event we act on (e.g. transfer/refund notifications) -
			// acknowledge quietly, nothing to do.
			throw new Ignorable_Exception('Paystack webhook: ignored event type.');
		}

		$reference    = $event['data']['reference'] ?? '';
		$payment_hash = $event['data']['metadata']['payment_hash'] ?? '';
		$payment      = $payment_hash ? wu_get_payment_by_hash($payment_hash) : false;

		if ( ! $payment || ! $reference) {
			throw new Ignorable_Exception('Paystack webhook: payment not found for this reference.');
		}

		if (Payment_Status::COMPLETED === $payment->get_status()) {
			// Already completed via the confirm-redirect path - a normal,
			// expected race, not an error.
			return;
		}

		$this->set_payment($payment);
		$this->verify_and_finalize($reference, $payment);
	}

	/**
	 * Shared by both the confirm-redirect and the webhook: re-verify the
	 * transaction directly against Paystack (never trust the webhook
	 * payload's amount/status alone) and complete the payment + renew or
	 * reactivate the membership.
	 *
	 * @param string                     $reference
	 * @param \WP_Ultimo\Models\Payment $payment
	 * @return void
	 */
	protected function verify_and_finalize($reference, $payment) {

		$result = $this->api_request('/transaction/verify/' . rawurlencode($reference), [], 'GET');

		if (is_wp_error($result)) {
			$this->redirect_with_error($result->get_error_message());
			return;
		}

		$data = $result['data'] ?? [];

		if (('success' !== ($data['status'] ?? '')) || empty($data['status'])) {
			$this->redirect_with_error(
				// translators: %s is the Paystack transaction status.
				sprintf(__('Payment not completed. Status: %s', 'paystack-gateway'), $data['status'] ?? 'unknown')
			);
			return;
		}

		$membership = $payment->get_membership();
		$customer   = $payment->get_customer();

		if ( ! $membership) {
			$this->redirect_with_error(__('Membership not found for this payment.', 'paystack-gateway'));
			return;
		}

		$payment->set_gateway('paystack');
		$payment->set_gateway_payment_id($reference);
		$payment->set_status(Payment_Status::COMPLETED);
		$payment->save();

		$membership->set_gateway('paystack');
		$membership->set_gateway_customer_id((string) ($data['customer']['customer_code'] ?? ''));
		$membership->add_to_times_billed(1);

		$inactive_statuses = [Membership_Status::CANCELLED, Membership_Status::EXPIRED];

		$membership_result = in_array($membership->get_status(), $inactive_statuses, true)
			? $membership->reactivate(false)
			: $membership->renew(false);

		if (true !== $membership_result) {
			$error_message = is_wp_error($membership_result)
				? $membership_result->get_error_message()
				: __('Membership transition failed.', 'paystack-gateway');

			wu_log_add('paystack', sprintf('Reference %s: membership %d transition failed: %s', $reference, $membership->get_id(), $error_message));

			$this->redirect_with_error($error_message);
			return;
		}

		$this->set_payment($payment);
		$this->trigger_payment_processed($payment, $membership);

		unset($customer);

		// Only the confirm-redirect path should actually redirect the
		// browser - the webhook path has no browser attached to it.
		if ( ! wu_request('wu-confirm')) {
			return;
		}

		wp_safe_redirect($this->get_return_url());
		exit;
	}

	/**
	 * @param \WP_Ultimo\Models\Membership $membership
	 * @param \WP_Ultimo\Models\Customer   $customer
	 * @return bool
	 */
	public function process_cancellation($membership, $customer) {

		unset($membership, $customer);

		// v1 has no stored subscription object on Paystack's side to
		// cancel (see supports_recurring() note) - nothing to do yet.
		return true;
	}

	/**
	 * @param float                        $amount
	 * @param \WP_Ultimo\Models\Payment    $payment
	 * @param \WP_Ultimo\Models\Membership $membership
	 * @param \WP_Ultimo\Models\Customer   $customer
	 * @return bool
	 */
	public function process_refund($amount, $payment, $membership, $customer) {

		unset($membership, $customer);

		$currency = strtoupper($payment->get_currency());

		$result = $this->api_request(
			'/refund',
			[
				'transaction' => $payment->get_gateway_payment_id(),
				'amount'      => (int) round($amount * wu_stripe_get_currency_multiplier($currency)),
			]
		);

		if (is_wp_error($result)) {
			wu_log_add('paystack', 'Refund failed: ' . $result->get_error_message());
			return false;
		}

		return true;
	}

	/**
	 * Thin wrapper around Paystack's REST API.
	 *
	 * @param string $endpoint
	 * @param array  $body
	 * @param string $method
	 * @return array|\WP_Error Decoded response body on success.
	 */
	protected function api_request($endpoint, $body = [], $method = 'POST') {

		if (empty($this->secret_key)) {
			return new \WP_Error('paystack-not-configured', __('Paystack secret key is not configured.', 'paystack-gateway'));
		}

		$args = [
			'method'  => $method,
			'timeout' => 30,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
			],
		];

		if ('GET' !== $method) {
			$args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request(self::API_BASE . $endpoint, $args);

		if (is_wp_error($response)) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code($response);
		$data = json_decode(wp_remote_retrieve_body($response), true);

		if ($code < 200 || $code >= 300 || empty($data['status'])) {
			return new \WP_Error(
				'paystack-api-error',
				$data['message'] ?? sprintf(
					// translators: %d is the HTTP response code.
					__('Paystack API request failed (HTTP %d).', 'paystack-gateway'),
					$code
				)
			);
		}

		return $data;
	}
}
