<?php
/**
 * AnsPress Compatibility Layer
 *
 * Prevents AnsPress from intercepting Ultimate Multisite's Light Ajax
 * requests and causing a fatal error when selecting a product to add
 * to a membership.
 *
 * AnsPress hooks an AJAX dispatcher onto the `init` action (via
 * AP_Ajax::init()) that calls die() after processing any request it
 * recognises.  Ultimate Multisite's Light Ajax also fires on `init`
 * (when wu-when=init) and relies on reaching its own action hooks
 * without interference.  When both plugins are active, AnsPress's
 * handler runs first and terminates the request before Ultimate
 * Multisite can serve the product-search JSON, producing a fatal /
 * empty-response error in the membership product-selection modal.
 *
 * The fix: when a wu-ajax request is detected, remove AnsPress's
 * init-time AJAX handler so Ultimate Multisite can complete normally.
 *
 * @package WP_Ultimo
 * @subpackage Compat/AnsPress_Compat
 * @since 2.4.3
 */

namespace WP_Ultimo\Compat;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * AnsPress compatibility class.
 *
 * @since 2.4.3
 */
class AnsPress_Compat {

	use \WP_Ultimo\Traits\Singleton;

	/**
	 * Instantiate the necessary hooks.
	 *
	 * We hook as early as possible (plugins_loaded priority 5) so we
	 * can remove AnsPress's init handler before it fires.
	 *
	 * @since 2.4.3
	 * @return void
	 */
	public function init(): void {

		/*
		 * Only act when this is a wu-ajax request.
		 * phpcs:ignore WordPress.Security.NonceVerification.Recommended
		 */
		if ( ! isset($_REQUEST['wu-ajax'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		/*
		 * Guard: only apply the fix when AnsPress is actually active.
		 * We check for the AP_Ajax class (present in all AnsPress
		 * versions ≥ 4.x) and the legacy anspress_ajax() function
		 * (versions < 4.x).
		 */
		if ( ! class_exists('AP_Ajax') && ! function_exists('anspress_ajax')) {
			return;
		}

		/*
		 * Remove AnsPress's init-time AJAX dispatcher so it cannot
		 * intercept and terminate our wu-ajax request.
		 *
		 * AnsPress 4.x registers AP_Ajax::init() on `init` at
		 * priority 1.  We remove it here (before `init` fires) so
		 * Ultimate Multisite's Light Ajax handler can run cleanly.
		 */
		add_action(
			'plugins_loaded',
			[$this, 'remove_anspress_ajax_hooks'],
			PHP_INT_MAX
		);
	}

	/**
	 * Removes AnsPress's AJAX hooks that conflict with wu-ajax requests.
	 *
	 * Called late on plugins_loaded (after AnsPress has registered its
	 * own hooks) so we can safely remove them before `init` fires.
	 *
	 * @since 2.4.3
	 * @return void
	 */
	public function remove_anspress_ajax_hooks(): void {

		/*
		 * AnsPress 4.x — class-based AJAX handler.
		 *
		 * AP_Ajax::init() is registered on `init` at priority 1.
		 * It calls ap_send_json() / die() after handling any request
		 * it recognises, which terminates our wu-ajax response early.
		 */
		if (class_exists('AP_Ajax')) {
			$this->remove_class_action('init', 'AP_Ajax', 'init', 1);
			$this->remove_class_action('init', 'AP_Ajax', 'init', 2);
			$this->remove_class_action('init', 'AP_Ajax', 'init', 10);
		}

		/*
		 * AnsPress < 4.x — function-based AJAX handler.
		 *
		 * Older versions registered a global anspress_ajax() function
		 * directly on `init`.
		 */
		if (function_exists('anspress_ajax')) {
			remove_action('init', 'anspress_ajax', 1);
			remove_action('init', 'anspress_ajax', 2);
			remove_action('init', 'anspress_ajax', 10);
		}
	}

	/**
	 * Removes an action registered by a class instance or statically.
	 *
	 * WordPress stores hook callbacks keyed by a string that includes
	 * the class name and method.  When the callback was registered via
	 * an instance (not a static call) we cannot use remove_action()
	 * directly because we don't have the original instance.  This
	 * helper iterates the global $wp_filter array to find and remove
	 * the matching entry.
	 *
	 * @since 2.4.3
	 *
	 * @param string $tag       The action hook name.
	 * @param string $class     The fully-qualified class name.
	 * @param string $method    The method name.
	 * @param int    $priority  The priority the action was registered at.
	 * @return void
	 */
	protected function remove_class_action(string $tag, string $class, string $method, int $priority): void {

		global $wp_filter;

		if ( ! isset($wp_filter[ $tag ][ $priority ])) {
			return;
		}

		foreach ($wp_filter[ $tag ][ $priority ] as $hook_key => $hook_data) {
			$callback = $hook_data['function'];

			// Static call: ['ClassName', 'method']
			if (
				is_array($callback)
				&& is_string($callback[0])
				&& $callback[0] === $class
				&& $callback[1] === $method
			) {
				unset($wp_filter[ $tag ][ $priority ][ $hook_key ]);
				return;
			}

			// Instance call: [$object, 'method']
			if (
				is_array($callback)
				&& is_object($callback[0])
				&& is_a($callback[0], $class)
				&& $callback[1] === $method
			) {
				unset($wp_filter[ $tag ][ $priority ][ $hook_key ]);
				return;
			}
		}
	}
}
