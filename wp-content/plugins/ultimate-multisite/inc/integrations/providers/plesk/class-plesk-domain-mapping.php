<?php
/**
 * Plesk Domain Mapping Capability.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/Plesk
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\Plesk;

use Psr\Log\LogLevel;
use WP_Ultimo\Integrations\Base_Capability_Module;
use WP_Ultimo\Integrations\Capabilities\Domain_Mapping_Capability;

defined('ABSPATH') || exit;

/**
 * Plesk domain mapping capability module.
 *
 * Uses the Plesk CLI gateway (/api/v2/cli) to manage site aliases
 * and subdomains for domain mapping.
 *
 * @since 2.5.0
 */
class Plesk_Domain_Mapping extends Base_Capability_Module implements Domain_Mapping_Capability {

	/**
	 * Supported features.
	 *
	 * @since 2.5.0
	 * @var array
	 */
	protected array $supported_features = ['autossl'];

	/**
	 * {@inheritdoc}
	 */
	public function get_capability_id(): string {

		return 'domain-mapping';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_title(): string {

		return __('Domain Mapping', 'ultimate-multisite');
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_explainer_lines(): array {

		$explainer_lines = [
			'will'     => [
				'send_domains' => __('Add domain aliases in Plesk whenever a new domain mapping gets created on your network', 'ultimate-multisite'),
				'autossl'      => __('SSL certificates will be automatically provisioned if Plesk SSL It! or Let\'s Encrypt extension is active', 'ultimate-multisite'),
			],
			'will_not' => [],
		];

		if (is_subdomain_install()) {
			$explainer_lines['will']['send_sub_domains'] = __('Add subdomains in Plesk whenever a new site gets created on your network', 'ultimate-multisite');
		}

		return $explainer_lines;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_hooks(): void {

		add_action('wu_add_domain', [$this, 'on_add_domain'], 10, 2);
		add_action('wu_remove_domain', [$this, 'on_remove_domain'], 10, 2);
		add_action('wu_add_subdomain', [$this, 'on_add_subdomain'], 10, 2);
		add_action('wu_remove_subdomain', [$this, 'on_remove_subdomain'], 10, 2);
	}

	/**
	 * Gets the parent Plesk_Integration for API calls.
	 *
	 * @since 2.5.0
	 * @return Plesk_Integration
	 */
	private function get_plesk(): Plesk_Integration {

		/** @var Plesk_Integration */
		return $this->get_integration();
	}

	/**
	 * Called when a new domain is mapped.
	 *
	 * Creates a site alias in Plesk via the CLI gateway.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being mapped.
	 * @param int    $site_id ID of the site receiving the mapping.
	 * @return void
	 */
	public function on_add_domain(string $domain, int $site_id): void {

		$base_domain = $this->get_plesk()->get_credential('WU_PLESK_DOMAIN');

		if (empty($base_domain)) {
			wu_log_add('integration-plesk', __('Missing WU_PLESK_DOMAIN; cannot add alias.', 'ultimate-multisite'), LogLevel::ERROR);

			return;
		}

		// Create site alias
		$this->log_response(
			sprintf('Add alias %s', $domain),
			$this->get_plesk()->send_plesk_api_request(
				'/api/v2/cli/site_alias/call',
				'POST',
				[
					'params' => ['--create', $domain, '-domain', $base_domain],
				]
			)
		);

		// Optionally add www alias
		if (! str_starts_with($domain, 'www.') && \WP_Ultimo\Managers\Domain_Manager::get_instance()->should_create_www_subdomain($domain)) {
			$www = 'www.' . $domain;

			$this->log_response(
				sprintf('Add alias %s', $www),
				$this->get_plesk()->send_plesk_api_request(
					'/api/v2/cli/site_alias/call',
					'POST',
					[
						'params' => ['--create', $www, '-domain', $base_domain],
					]
				)
			);
		}
	}

	/**
	 * Called when a mapped domain is removed.
	 *
	 * Deletes the site alias from Plesk via the CLI gateway.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being removed.
	 * @param int    $site_id ID of the site.
	 * @return void
	 */
	public function on_remove_domain(string $domain, int $site_id): void {

		// Delete site alias
		$this->log_response(
			sprintf('Delete alias %s', $domain),
			$this->get_plesk()->send_plesk_api_request(
				'/api/v2/cli/site_alias/call',
				'POST',
				[
					'params' => ['--delete', $domain],
				]
			)
		);

		// Also try to remove www alias
		if (! str_starts_with($domain, 'www.')) {
			$www = 'www.' . $domain;

			$this->log_response(
				sprintf('Delete alias %s', $www),
				$this->get_plesk()->send_plesk_api_request(
					'/api/v2/cli/site_alias/call',
					'POST',
					[
						'params' => ['--delete', $www],
					]
				)
			);
		}
	}

	/**
	 * Called when a new subdomain is added.
	 *
	 * Creates a subdomain in Plesk via the CLI gateway.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being added.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_add_subdomain(string $subdomain, int $site_id): void {

		$base_domain = $this->get_plesk()->get_credential('WU_PLESK_DOMAIN');

		if (empty($base_domain)) {
			wu_log_add('integration-plesk', __('Missing WU_PLESK_DOMAIN; cannot add subdomain.', 'ultimate-multisite'), LogLevel::ERROR);

			return;
		}

		$this->log_response(
			sprintf('Add subdomain %s', $subdomain),
			$this->get_plesk()->send_plesk_api_request(
				'/api/v2/cli/subdomain/call',
				'POST',
				[
					'params' => ['--create', $subdomain, '-domain', $base_domain, '-www-root', '/httpdocs'],
				]
			)
		);
	}

	/**
	 * Called when a subdomain is removed.
	 *
	 * Deletes the subdomain from Plesk via the CLI gateway.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being removed.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_remove_subdomain(string $subdomain, int $site_id): void {

		$this->log_response(
			sprintf('Delete subdomain %s', $subdomain),
			$this->get_plesk()->send_plesk_api_request(
				'/api/v2/cli/subdomain/call',
				'POST',
				[
					'params' => ['--delete', $subdomain],
				]
			)
		);
	}

	/**
	 * Log an API response with a contextual label.
	 *
	 * @since 2.5.0
	 *
	 * @param string          $action_label Descriptive label for the action.
	 * @param array|\WP_Error $response     The API response.
	 * @return void
	 */
	protected function log_response(string $action_label, $response): void {

		if (is_wp_error($response)) {
			wu_log_add('integration-plesk', sprintf('[%s] %s', $action_label, $response->get_error_message()), LogLevel::ERROR);

			return;
		}

		wu_log_add('integration-plesk', sprintf('[%s] %s', $action_label, wp_json_encode($response)));
	}

	/**
	 * {@inheritdoc}
	 */
	public function test_connection() {

		return $this->get_plesk()->test_connection();
	}
}
