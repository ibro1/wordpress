<?php
/**
 * NOWPayments Gateway for Ultimate Multisite.
 *
 * Redirect-based flow, same shape as the Paystack addon: create a hosted
 * invoice, redirect the customer to NOWPayments' page (where they pick
 * their own coin), and complete the payment when the IPN webhook reports
 * payment_status=finished. Unlike Paystack, there is no synchronous verify
 * call to make on the return redirect - crypto confirmations are async, so
 * the webhook is the only path that ever completes a payment here.
 *
 * @package NOWPaymentsGateway
 */

namespace NOWPaymentsGateway;

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
class NOWPayments_Gateway extends Base_Gateway {

	/**
	 * @var string
	 */
	protected $id = 'nowpayments';

	/**
	 * @var bool
	 */
	protected $sandbox_mode = true;

	/**
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * @var string
	 */
	protected $ipn_secret = '';

	/*
	 * Sandbox is a fully separate NOWPayments account (signed up at
	 * account-sandbox.nowpayments.io), not just a mode flag on the same
	 * account - hence the separate base URL, not just a separate key.
	 */
	const API_BASE_LIVE    = 'https://api.nowpayments.io/v1';
	const API_BASE_SANDBOX = 'https://api-sandbox.nowpayments.io/v1';

	/**
	 * Loads whichever key pair (sandbox/live) is active, same naming
	 * convention as the Paystack addon: {id}_sandbox_mode,
	 * {id}_{sandbox|live}_{api_key|ipn_secret}.
	 *
	 * @return void
	 */
	public function init() {

		$this->sandbox_mode = (bool) wu_get_setting('nowpayments_sandbox_mode', true);

		$mode = $this->sandbox_mode ? 'sandbox' : 'live';

		$this->api_key    = trim((string) wu_get_setting("nowpayments_{$mode}_api_key", ''));
		$this->ipn_secret = trim((string) wu_get_setting("nowpayments_{$mode}_ipn_secret", ''));
	}

	/**
	 * Same JS-masking approach used by the Paystack addon - the
	 * settings-field system has no 'password' field type, so masking the
	 * secret inputs is done in JS instead, only on this plugin's own
	 * settings screen.
	 *
	 * @return void
	 */
	public function hooks() {

		add_action('admin_footer', [$this, 'render_secret_mask_script']);
	}

	/**
	 * @return void
	 */
	public function render_secret_mask_script() {

		if ('wp-ultimo-settings' !== wu_request('page')) {
			return;
		}
		?>
		<script>
		(function () {
			['nowpayments_sandbox_api_key', 'nowpayments_live_api_key', 'nowpayments_sandbox_ipn_secret', 'nowpayments_live_ipn_secret'].forEach(function (id) {
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
			'nowpayments_header',
			[
				'title'           => __('NOWPayments (Crypto)', 'nowpayments-gateway'),
				'desc'            => __('Use the settings section below to configure NOWPayments as a payment method. Sandbox testing requires a separate account at account-sandbox.nowpayments.io - sandbox and live keys are not interchangeable.', 'nowpayments-gateway'),
				'type'            => 'header',
				'show_as_submenu' => true,
				'require'         => [
					'active_gateways' => 'nowpayments',
				],
			]
		);

		/*
		 * Same string '1'/'0' workaround used by the Paystack addon for
		 * assets/js/vue-apps.js's strict-equality require() bug.
		 */
		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_sandbox_mode',
			[
				'title'     => __('NOWPayments Sandbox Mode', 'nowpayments-gateway'),
				'desc'      => __('Toggle this to use your sandbox NOWPayments account. Turn off only once you have confirmed a real test payment completes correctly.', 'nowpayments-gateway'),
				'type'      => 'toggle',
				'default'   => '1',
				'html_attr' => [
					'v-model' => 'nowpayments_sandbox_mode',
				],
				'require'   => [
					'active_gateways' => 'nowpayments',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_sandbox_api_key',
			[
				'title'   => __('NOWPayments Sandbox API Key', 'nowpayments-gateway'),
				'tooltip' => __('From account-sandbox.nowpayments.io - a separate account from your live one.', 'nowpayments-gateway'),
				'type'    => 'text',
				'default' => '',
				'require' => [
					'active_gateways'         => 'nowpayments',
					'nowpayments_sandbox_mode' => '1',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_sandbox_ipn_secret',
			[
				'title'   => __('NOWPayments Sandbox IPN Secret', 'nowpayments-gateway'),
				'tooltip' => __('Generated in the sandbox account\'s Payment Settings tab - different from the API key.', 'nowpayments-gateway'),
				'type'    => 'text',
				'default' => '',
				'require' => [
					'active_gateways'         => 'nowpayments',
					'nowpayments_sandbox_mode' => '1',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_live_api_key',
			[
				'title'   => __('NOWPayments Live API Key', 'nowpayments-gateway'),
				'tooltip' => __('Make sure this is the LIVE account key, not the sandbox one.', 'nowpayments-gateway'),
				'type'    => 'text',
				'default' => '',
				'require' => [
					'active_gateways'         => 'nowpayments',
					'nowpayments_sandbox_mode' => '0',
				],
			]
		);

		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_live_ipn_secret',
			[
				'title'   => __('NOWPayments Live IPN Secret', 'nowpayments-gateway'),
				'tooltip' => __('Generated in the live account\'s Payment Settings tab - different from the API key.', 'nowpayments-gateway'),
				'type'    => 'text',
				'default' => '',
				'require' => [
					'active_gateways'         => 'nowpayments',
					'nowpayments_sandbox_mode' => '0',
				],
			]
		);

		$webhook_message = sprintf(
			'<span class="wu-p-2 wu-bg-blue-100 wu-text-blue-600 wu-rounded wu-mt-3 wu-mb-0 wu-block wu-text-xs">%s</span>',
			__('Paste this URL into your NOWPayments Dashboard Payment Settings as the IPN callback URL.', 'nowpayments-gateway')
		);

		wu_register_settings_field(
			'payment-gateways',
			'nowpayments_webhook_listener_explanation',
			[
				'title'         => __('IPN Callback URL', 'nowpayments-gateway'),
				'desc'          => $webhook_message,
				'type'          => 'text-display',
				'copy'          => true,
				'display_value' => $this->get_webhook_listener_url(),
				'require'       => [
					'active_gateways' => 'nowpayments',
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
	 * @return bool
	 */
	public function supports_free_trials() {

		return false;
	}

	/**
	 * Deliberately false for v1. NOWPayments has a separate
	 * subscriptions/recurring-payments API that hasn't been wired up here -
	 * claiming true without it would mean renewals silently never get
	 * charged. Same honest behaviour as the Paystack addon.
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

		unset($cart, $type);

		$amount = (float) $payment->get_total();

		// A zero-total payment (fully covered by a trial/discount) has
		// nothing for NOWPayments to do - let it stay pending exactly like
		// the Manual gateway does, without ever hitting the API.
		if ($amount <= 0) {
			return true;
		}

		// price_amount is a plain decimal fiat amount here, not
		// subunits/cents - unlike Paystack/Stripe, no currency multiplier.
		$body = [
			'price_amount'      => $amount,
			'price_currency'    => strtolower($payment->get_currency()),
			'order_id'          => $payment->get_hash(),
			'order_description' => sprintf(
				// translators: %1$s is the site name, %2$d is the membership ID.
				__('%1$s - Membership #%2$d', 'nowpayments-gateway'),
				get_bloginfo('name'),
				$membership->get_id()
			),
			'ipn_callback_url'  => $this->get_webhook_listener_url(),
			'success_url'       => $this->get_confirm_url(),
			'cancel_url'        => wu_get_registration_url(),
		];

		$result = $this->api_request('/invoice', $body);

		if (is_wp_error($result)) {
			$this->redirect_with_error($result->get_error_message());
			return false;
		}

		if (empty($result['invoice_url'])) {
			$this->redirect_with_error(__('NOWPayments did not return an invoice URL.', 'nowpayments-gateway'));
			return false;
		}

		$payment->set_gateway_payment_id((string) ($result['id'] ?? ''));
		$payment->save();

		wp_redirect($result['invoice_url']); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- NOWPayments external URL
		exit;
	}

	/**
	 * Return-redirect handler - the browser lands back here after the
	 * customer leaves NOWPayments' hosted invoice page. Crypto payments
	 * confirm asynchronously (on-chain), so unlike Paystack there is
	 * nothing to verify synchronously here: the payment stays pending
	 * until the IPN webhook reports payment_status=finished. This just
	 * sends the customer on to the normal thank-you/pending-payment flow.
	 *
	 * @return void
	 */
	public function process_confirmation() {

		if ( ! $this->payment) {
			$this->redirect_with_error(__('Payment record not found.', 'nowpayments-gateway'));
			return;
		}

		wp_safe_redirect($this->get_return_url());
		exit;
	}

	/**
	 * Webhook handler - the only path that ever completes a NOWPayments
	 * payment. Must be idempotent, since NOWPayments retries IPNs that
	 * don't get a 200 response, and can send more than one status update
	 * per payment (waiting -> confirming -> confirmed -> finished, etc).
	 *
	 * @return void
	 * @throws Ignorable_Exception For events that aren't ours to act on.
	 */
	public function process_webhooks() {

		$raw_body  = file_get_contents('php://input');
		$signature = isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) : '';

		if (empty($this->ipn_secret) || empty($signature) || empty($raw_body)) {
			throw new Ignorable_Exception('NOWPayments webhook: missing IPN secret, signature header, or body.');
		}

		$event = json_decode($raw_body, true);

		if ( ! is_array($event)) {
			throw new Ignorable_Exception('NOWPayments webhook: unparseable body.');
		}

		// JSON_UNESCAPED_SLASHES matters here - NOWPayments signs the body
		// with slashes left unescaped (e.g. inside URL-shaped fields), and
		// PHP's default json_encode would escape them to "\/", producing a
		// different byte string and a signature that never matches.
		$sorted_json = wp_json_encode(self::recursive_ksort($event), JSON_UNESCAPED_SLASHES);
		$computed    = hash_hmac('sha512', $sorted_json, $this->ipn_secret);

		if ( ! hash_equals($computed, $signature)) {
			status_header(401);
			throw new Ignorable_Exception('NOWPayments webhook: signature mismatch.');
		}

		$order_id = $event['order_id'] ?? '';
		$payment  = $order_id ? wu_get_payment_by_hash($order_id) : false;

		if ( ! $payment) {
			throw new Ignorable_Exception('NOWPayments webhook: payment not found for this order_id.');
		}

		if (Payment_Status::COMPLETED === $payment->get_status()) {
			// Already completed by an earlier "finished" IPN - a normal,
			// expected occurrence since NOWPayments can send several
			// status updates for the same payment.
			return;
		}

		$status = (string) ($event['payment_status'] ?? '');

		if ('partially_paid' === $status) {
			$payment->set_status(Payment_Status::PARTIAL);
			$payment->save();

			wu_log_add('nowpayments', sprintf('Order %s: partially paid. Amount received: %s', $order_id, $event['actually_paid'] ?? 'unknown'));

			throw new Ignorable_Exception('NOWPayments webhook: partial payment, awaiting the rest.');
		}

		if (in_array($status, ['failed', 'expired'], true)) {
			wu_log_add('nowpayments', sprintf('Order %s: payment %s.', $order_id, $status));

			throw new Ignorable_Exception('NOWPayments webhook: payment failed or expired.');
		}

		if ('finished' !== $status) {
			// waiting / confirming / confirmed / sending - on-chain
			// confirmation still in progress, nothing to finalize yet.
			throw new Ignorable_Exception('NOWPayments webhook: payment not finished yet (' . $status . ').');
		}

		$membership = $payment->get_membership();

		if ( ! $membership) {
			throw new Ignorable_Exception('NOWPayments webhook: membership not found for this payment.');
		}

		$payment->set_gateway('nowpayments');
		$payment->set_gateway_payment_id((string) ($event['payment_id'] ?? $payment->get_gateway_payment_id()));
		$payment->set_status(Payment_Status::COMPLETED);
		$payment->save();

		$membership->set_gateway('nowpayments');
		$membership->add_to_times_billed(1);

		$inactive_statuses = [Membership_Status::CANCELLED, Membership_Status::EXPIRED];

		$membership_result = in_array($membership->get_status(), $inactive_statuses, true)
			? $membership->reactivate(false)
			: $membership->renew(false);

		if (true !== $membership_result) {
			$error_message = is_wp_error($membership_result)
				? $membership_result->get_error_message()
				: __('Membership transition failed.', 'nowpayments-gateway');

			wu_log_add('nowpayments', sprintf('Order %s: membership %d transition failed: %s', $order_id, $membership->get_id(), $error_message));

			throw new Ignorable_Exception('NOWPayments webhook: membership transition failed - ' . $error_message);
		}

		$this->set_payment($payment);
		$this->trigger_payment_processed($payment, $membership);
	}

	/**
	 * @param \WP_Ultimo\Models\Membership $membership
	 * @param \WP_Ultimo\Models\Customer   $customer
	 * @return bool
	 */
	public function process_cancellation($membership, $customer) {

		unset($membership, $customer);

		// v1 has no stored subscription object on NOWPayments' side to
		// cancel (see supports_recurring() note) - nothing to do yet.
		return true;
	}

	/**
	 * Crypto payments are not reversible on-chain, and NOWPayments' API has
	 * no refund endpoint - a refund has to be a manual crypto transfer back
	 * to the customer, arranged outside this integration.
	 *
	 * @param float                        $amount
	 * @param \WP_Ultimo\Models\Payment    $payment
	 * @param \WP_Ultimo\Models\Membership $membership
	 * @param \WP_Ultimo\Models\Customer   $customer
	 * @return bool
	 */
	public function process_refund($amount, $payment, $membership, $customer) {

		unset($amount, $membership, $customer);

		wu_log_add('nowpayments', sprintf('Refund requested for payment %s - NOWPayments has no refund API; send crypto back to the customer manually.', $payment->get_hash()));

		return false;
	}

	/**
	 * Sorts an array's keys recursively (including within nested arrays),
	 * matching NOWPayments' documented IPN signing behaviour - required so
	 * the HMAC we compute matches the one NOWPayments sent regardless of
	 * key order or nesting.
	 *
	 * @param array $data
	 * @return array
	 */
	protected static function recursive_ksort($data) {

		if ( ! is_array($data)) {
			return $data;
		}

		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[ $key ] = self::recursive_ksort($value);
			}
		}

		ksort($data);

		return $data;
	}

	/**
	 * Thin wrapper around NOWPayments' REST API.
	 *
	 * @param string $endpoint
	 * @param array  $body
	 * @param string $method
	 * @return array|\WP_Error Decoded response body on success.
	 */
	protected function api_request($endpoint, $body = [], $method = 'POST') {

		if (empty($this->api_key)) {
			return new \WP_Error('nowpayments-not-configured', __('NOWPayments API key is not configured.', 'nowpayments-gateway'));
		}

		$base = $this->sandbox_mode ? self::API_BASE_SANDBOX : self::API_BASE_LIVE;

		$args = [
			'method'  => $method,
			'timeout' => 30,
			'headers' => [
				'x-api-key'    => $this->api_key,
				'Content-Type' => 'application/json',
			],
		];

		if ('GET' !== $method) {
			$args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request($base . $endpoint, $args);

		if (is_wp_error($response)) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code($response);
		$data = json_decode(wp_remote_retrieve_body($response), true);

		if ($code < 200 || $code >= 300 || ! is_array($data)) {
			return new \WP_Error(
				'nowpayments-api-error',
				is_array($data) && ! empty($data['message'])
					? $data['message']
					: sprintf(
						// translators: %d is the HTTP response code.
						__('NOWPayments API request failed (HTTP %d).', 'nowpayments-gateway'),
						$code
					)
			);
		}

		return $data;
	}
}
