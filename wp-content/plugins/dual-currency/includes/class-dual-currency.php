<?php
/**
 * Core logic: settings, currency resolution, and the two cart filters that
 * convert a price from the base currency into Naira.
 */

namespace Dual_Currency;

defined('ABSPATH') || exit;

class Dual_Currency {

	const COOKIE_NAME = 'dc_currency';

	private static $instance;

	public static function get_instance() {
		if ( ! self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action('init', [$this, 'maybe_set_currency_cookie'], 5);
		add_action('init', [$this, 'add_settings']);

		add_filter('wu_cart_parameters', [$this, 'inject_cart_currency'], 10, 2);
		add_filter('wu_add_product_line_item', [$this, 'convert_line_item'], 10, 5);
		add_filter('wu_cart_product_setup_fee', [$this, 'convert_setup_fee'], 10, 3);
	}

	/**
	 * True when there is actually something to convert to/from - i.e. the
	 * base currency isn't already Naira, and the admin has set a positive
	 * exchange rate.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		if (dual_currency_get_base_currency() === DUAL_CURRENCY_ALT) {
			return false;
		}

		return $this->get_rate() > 0;
	}

	/**
	 * The admin-configured base-currency-to-Naira rate (how many Naira for
	 * 1 unit of the base currency). No live FX lookup by design - this is a
	 * plain number the admin sets and updates by hand.
	 *
	 * @return float
	 */
	public function get_rate(): float {
		return (float) wu_get_setting('dual_currency_ngn_rate', 0);
	}

	/**
	 * Reads ?currency=/POST currency= off the current request and, if it's
	 * a currency this plugin supports, remembers it in a cookie for the
	 * rest of the visitor's session. Runs on every front-end request, early
	 * on `init`, before any output.
	 *
	 * @return void
	 */
	public function maybe_set_currency_cookie(): void {
		if (is_admin() || headers_sent()) {
			return;
		}

		$requested = isset($_REQUEST['currency']) ? strtoupper(sanitize_text_field(wp_unslash($_REQUEST['currency']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array($requested, [dual_currency_get_base_currency(), DUAL_CURRENCY_ALT], true)) {
			return;
		}

		if (isset($_COOKIE[ self::COOKIE_NAME ]) && wp_unslash($_COOKIE[ self::COOKIE_NAME ]) === $requested) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return;
		}

		setcookie(self::COOKIE_NAME, $requested, time() + MONTH_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN);
		$_COOKIE[ self::COOKIE_NAME ] = $requested;
	}

	/**
	 * Resolves which currency the current visitor should see/pay in:
	 * 1. An explicit choice already on this request or remembered in the
	 *    cookie wins.
	 * 2. Mid-checkout, whatever the signup session already has (so a
	 *    later step of the same flow doesn't flip currency underneath the
	 *    customer).
	 * 3. Otherwise, default by geolocation (Nigeria -> Naira).
	 *
	 * @param \WP_Ultimo\Checkout\Checkout|null $checkout
	 * @return string
	 */
	public function resolve_currency($checkout = null): string {
		$base = dual_currency_get_base_currency();

		if ( ! $this->is_active()) {
			return $base;
		}

		$valid = [$base, DUAL_CURRENCY_ALT];

		if ($checkout && method_exists($checkout, 'request_or_session')) {
			$from_checkout = strtoupper((string) $checkout->request_or_session('currency', ''));

			if (in_array($from_checkout, $valid, true)) {
				return $from_checkout;
			}
		}

		$requested = isset($_REQUEST['currency']) ? strtoupper(sanitize_text_field(wp_unslash($_REQUEST['currency']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if (in_array($requested, $valid, true)) {
			return $requested;
		}

		if (isset($_COOKIE[ self::COOKIE_NAME ])) {
			$from_cookie = strtoupper(sanitize_text_field(wp_unslash($_COOKIE[ self::COOKIE_NAME ])));

			if (in_array($from_cookie, $valid, true)) {
				return $from_cookie;
			}
		}

		if ( ! wu_get_setting('dual_currency_geo_default', true)) {
			return $base;
		}

		$geolocation = class_exists('\WP_Ultimo\Geolocation') ? \WP_Ultimo\Geolocation::geolocate_ip('', true) : [];

		return ( ! empty($geolocation['country']) && 'NG' === $geolocation['country']) ? DUAL_CURRENCY_ALT : $base;
	}

	/**
	 * Converts a base-currency amount into $target_currency. Only Naira is
	 * supported as a conversion target in v1; anything else (including the
	 * base currency itself) is returned unchanged.
	 *
	 * @param float  $amount
	 * @param string $target_currency
	 * @return float
	 */
	public function convert_amount($amount, $target_currency) {
		if ( ! $amount || ! $this->is_active()) {
			return $amount;
		}

		if (strtoupper((string) $target_currency) !== DUAL_CURRENCY_ALT) {
			return $amount;
		}

		$rate = $this->get_rate();

		if ($rate <= 0) {
			return $amount;
		}

		return round(((float) $amount) * $rate, 2);
	}

	/**
	 * wu_cart_parameters filter. Only fills in `currency` when it hasn't
	 * already been set (e.g. a renewal/reactivation/pending-payment cart,
	 * which Ultimate Multisite itself locks to the membership's/payment's
	 * own currency later in Cart::build_from_membership()/build_from_payment()
	 * regardless of what we pass here) - so this only ever affects brand
	 * new signups, which is the intended v1 scope.
	 *
	 * @param array                              $params
	 * @param \WP_Ultimo\Checkout\Checkout        $checkout
	 * @return array
	 */
	public function inject_cart_currency($params, $checkout) {
		if (empty($params['currency'])) {
			$params['currency'] = $this->resolve_currency($checkout);
		}

		return $params;
	}

	/**
	 * wu_add_product_line_item filter - the single choke point that runs
	 * after price-variation and PWYW resolution, so converting here covers
	 * base price, duration-variation pricing, and PWYW amounts uniformly.
	 *
	 * @param array                          $line_item_data
	 * @param \WP_Ultimo\Models\Product      $product
	 * @param mixed                          $duration
	 * @param mixed                          $duration_unit
	 * @param \WP_Ultimo\Checkout\Cart       $cart
	 * @return array
	 */
	public function convert_line_item($line_item_data, $product, $duration, $duration_unit, $cart) {
		if (isset($line_item_data['unit_price'])) {
			$line_item_data['unit_price'] = $this->convert_amount($line_item_data['unit_price'], $cart->get_currency());
		}

		return $line_item_data;
	}

	/**
	 * wu_cart_product_setup_fee filter - setup fees are added to the cart
	 * via a separate line item and need the same conversion.
	 *
	 * @param float                      $setup_fee
	 * @param \WP_Ultimo\Models\Product  $product
	 * @param \WP_Ultimo\Checkout\Cart   $cart
	 * @return float
	 */
	public function convert_setup_fee($setup_fee, $product, $cart) {
		return $this->convert_amount($setup_fee, $cart->get_currency());
	}

	/**
	 * Registers the "Dual Currency" settings section under Network Admin ->
	 * Ultimate Multisite -> Settings.
	 *
	 * @return void
	 */
	public function add_settings(): void {
		if ( ! function_exists('wu_register_settings_section')) {
			return;
		}

		wu_register_settings_section(
			'dual_currency',
			[
				'title' => __('Dual Currency', 'dual-currency'),
				'desc'  => __('Dual Currency', 'dual-currency'),
				'icon'  => 'dashicons-wu-credit-card',
				'order' => 96,
			]
		);

		wu_register_settings_field(
			'dual_currency',
			'dual_currency_header',
			[
				'title' => __('Naira Conversion', 'dual-currency'),
				'desc'  => sprintf(
					// translators: %s is the site's configured base currency, e.g. USD.
					__('Prices are set in %s. Customers who pick Naira at checkout are charged the amount below, converted at the rate you set here - there is no live exchange-rate lookup, so update this by hand when the rate moves.', 'dual-currency'),
					esc_html(dual_currency_get_base_currency())
				),
				'type'  => 'header',
			]
		);

		wu_register_settings_field(
			'dual_currency',
			'dual_currency_ngn_rate',
			[
				'title'   => __('Exchange rate (NGN per 1 base unit)', 'dual-currency'),
				'desc'    => sprintf(
					// translators: %s is the site's configured base currency, e.g. USD.
					__('How many Naira equal 1 %s. Leave at 0 to keep Naira checkout disabled.', 'dual-currency'),
					esc_html(dual_currency_get_base_currency())
				),
				'type'    => 'number',
				'min'     => 0,
				'default' => 0,
			]
		);

		wu_register_settings_field(
			'dual_currency',
			'dual_currency_geo_default',
			[
				'title'   => __('Default Nigerian visitors to Naira', 'dual-currency'),
				'desc'    => __('New visitors whose IP geolocates to Nigeria see Naira pricing automatically. They can still switch currency manually.', 'dual-currency'),
				'type'    => 'toggle',
				'default' => 1,
			]
		);
	}
}
