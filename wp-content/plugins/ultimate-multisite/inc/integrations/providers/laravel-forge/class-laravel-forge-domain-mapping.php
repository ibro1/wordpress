<?php
/**
 * Laravel Forge Domain Mapping Capability.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/LaravelForge
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\LaravelForge;

use Psr\Log\LogLevel;
use WP_Ultimo\Integrations\Base_Capability_Module;
use WP_Ultimo\Integrations\Capabilities\Domain_Mapping_Capability;

defined('ABSPATH') || exit;

/**
 * Laravel Forge domain mapping capability module.
 *
 * Creates Forge sites for mapped domains, provisions Let's Encrypt SSL,
 * configures load balancing, and runs deploy commands.
 *
 * @since 2.5.0
 */
class LaravelForge_Domain_Mapping extends Base_Capability_Module implements Domain_Mapping_Capability {

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

		$lines = [
			'will'     => [
				__('Create new sites on your Forge servers for each mapped domain', 'ultimate-multisite'),
				__('Configure load balancing when using multiple backend servers', 'ultimate-multisite'),
			],
			'will_not' => [],
		];

		$forge = $this->get_forge();

		if ($forge->get_credential('WU_FORGE_DEPLOY_COMMAND') || $forge->get_credential('WU_FORGE_SYMLINK_TARGET')) {
			$lines['will'][] = __('Run deploy commands or create symlinks after site creation', 'ultimate-multisite');
		}

		return $lines;
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
	 * Gets the parent LaravelForge_Integration for API calls.
	 *
	 * @since 2.5.0
	 * @return LaravelForge_Integration
	 */
	private function get_forge(): LaravelForge_Integration {

		/** @var LaravelForge_Integration */
		return $this->get_integration();
	}

	/**
	 * Called when a new domain is mapped.
	 *
	 * Creates a Forge site on all configured servers, provisions SSL,
	 * configures load balancing, and runs deploy commands.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being mapped.
	 * @param int    $site_id ID of the site receiving the mapping.
	 * @return void
	 */
	public function on_add_domain(string $domain, int $site_id): void {

		$servers = $this->get_forge()->get_server_list();

		if (empty($servers)) {
			wu_log_add('integration-forge', 'No servers configured', LogLevel::ERROR);

			return;
		}

		$load_balancer_server_id = $this->get_forge()->get_load_balancer_server_id();
		$load_balancer_site_id   = null;
		$created_sites           = [];

		foreach ($servers as $server_id) {
			$is_load_balancer = $load_balancer_server_id && (int) $server_id === (int) $load_balancer_server_id;

			$result = $this->create_site_on_server($server_id, $domain);

			if (is_wp_error($result)) {
				wu_log_add(
					'integration-forge',
					sprintf('Failed to create site on server %s: %s', $server_id, $result->get_error_message()),
					LogLevel::ERROR
				);

				continue;
			}

			if (isset($result['site']['id'])) {
				$forge_site_id = $result['site']['id'];

				wu_log_add(
					'integration-forge',
					sprintf('Site created on server %s with ID %s', $server_id, $forge_site_id)
				);

				if ($is_load_balancer) {
					$load_balancer_site_id = $forge_site_id;
				} else {
					$created_sites[] = [
						'server_id' => $server_id,
						'site_id'   => $forge_site_id,
					];
				}
			}
		}

		// Provision SSL on load balancer or primary server.
		$ssl_server_id = $load_balancer_server_id ?: $this->get_forge()->get_primary_server_id();
		$ssl_site_id   = $load_balancer_site_id ?: $this->get_forge()->get_primary_site_id();

		if ($ssl_server_id && $ssl_site_id) {
			$this->install_ssl_certificate($ssl_server_id, $ssl_site_id, $domain);
		}

		// Configure load balancing if applicable.
		if ($load_balancer_server_id && $load_balancer_site_id && ! empty($created_sites)) {
			$this->configure_load_balancing($load_balancer_server_id, $load_balancer_site_id, $created_sites);
		}

		// Run deploy commands on backend servers.
		if (! empty($created_sites)) {
			$this->run_deploy_commands($created_sites, $domain);
		}
	}

	/**
	 * Called when a mapped domain is removed.
	 *
	 * Deletes the Forge site for the domain on all configured servers.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being removed.
	 * @param int    $site_id ID of the site.
	 * @return void
	 */
	public function on_remove_domain(string $domain, int $site_id): void {

		$servers = $this->get_forge()->get_server_list();

		foreach ($servers as $server_id) {
			$forge_site_id = $this->find_site_by_domain($server_id, $domain);

			if (! $forge_site_id) {
				wu_log_add(
					'integration-forge',
					sprintf('Site not found for domain %s on server %s', $domain, $server_id)
				);

				continue;
			}

			$response = $this->get_forge()->send_forge_request(
				sprintf('/servers/%s/sites/%s', $server_id, $forge_site_id),
				[],
				'DELETE'
			);

			if (is_wp_error($response)) {
				wu_log_add(
					'integration-forge',
					sprintf('Failed to delete site on server %s: %s', $server_id, $response->get_error_message()),
					LogLevel::ERROR
				);
			} else {
				wu_log_add(
					'integration-forge',
					sprintf('Site deleted for domain %s on server %s', $domain, $server_id)
				);
			}
		}
	}

	/**
	 * Called when a new subdomain is added.
	 *
	 * Subdomains are handled by wildcard DNS/SSL in Forge — no action needed.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being added.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_add_subdomain(string $subdomain, int $site_id): void {}

	/**
	 * Called when a subdomain is removed.
	 *
	 * Subdomains are handled by wildcard DNS/SSL in Forge — no action needed.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being removed.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_remove_subdomain(string $subdomain, int $site_id): void {}

	/**
	 * Creates a site on a specific Forge server.
	 *
	 * @since 2.5.0
	 *
	 * @param int    $server_id The Forge server ID.
	 * @param string $domain    The domain name.
	 * @return array|\WP_Error
	 */
	private function create_site_on_server(int $server_id, string $domain) {

		$response = $this->get_forge()->send_forge_request(
			sprintf('/servers/%s/sites', $server_id),
			[
				'domain'       => $domain,
				'project_type' => 'php',
				'directory'    => '/public',
			],
			'POST'
		);

		if (is_wp_error($response)) {
			return $response;
		}

		return $this->get_forge()->parse_response($response);
	}

	/**
	 * Installs a Let's Encrypt SSL certificate on a site.
	 *
	 * @since 2.5.0
	 *
	 * @param int    $server_id The Forge server ID.
	 * @param int    $site_id   The Forge site ID.
	 * @param string $domain    The domain name.
	 * @return void
	 */
	private function install_ssl_certificate(int $server_id, int $site_id, string $domain): void {

		$domains = [$domain];

		if (! str_starts_with($domain, 'www.') && ! str_starts_with($domain, '*.')) {
			$domains[] = 'www.' . $domain;
		}

		$response = $this->get_forge()->send_forge_request(
			sprintf('/servers/%s/sites/%s/certificates/letsencrypt', $server_id, $site_id),
			['domains' => $domains],
			'POST'
		);

		if (is_wp_error($response)) {
			wu_log_add(
				'integration-forge',
				sprintf('Failed to install SSL for %s: %s', $domain, $response->get_error_message()),
				LogLevel::ERROR
			);
		} else {
			wu_log_add(
				'integration-forge',
				sprintf('SSL certificate requested for %s on server %s', $domain, $server_id)
			);
		}
	}

	/**
	 * Configures load balancing for a site.
	 *
	 * @since 2.5.0
	 *
	 * @param int   $lb_server_id    Load balancer server ID.
	 * @param int   $lb_site_id      Load balancer site ID.
	 * @param array $backend_servers Array of backend server/site pairs.
	 * @return void
	 */
	private function configure_load_balancing(int $lb_server_id, int $lb_site_id, array $backend_servers): void {

		$servers = [];

		foreach ($backend_servers as $backend) {
			$servers[] = [
				'id'     => (int) $backend['server_id'],
				'weight' => 1,
			];
		}

		$response = $this->get_forge()->send_forge_request(
			sprintf('/servers/%s/sites/%s/balancing', $lb_server_id, $lb_site_id),
			[
				'servers' => $servers,
				'method'  => 'least_conn',
			],
			'PUT'
		);

		if (is_wp_error($response)) {
			wu_log_add(
				'integration-forge',
				sprintf('Failed to configure load balancing: %s', $response->get_error_message()),
				LogLevel::ERROR
			);
		} else {
			wu_log_add('integration-forge', 'Load balancing configured successfully');
		}
	}

	/**
	 * Runs deploy commands on the created sites.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $sites  Array of server/site pairs.
	 * @param string $domain The domain name.
	 * @return void
	 */
	private function run_deploy_commands(array $sites, string $domain): void {

		$command = $this->get_forge()->get_deploy_command($domain);

		if (empty($command)) {
			return;
		}

		foreach ($sites as $site) {
			$response = $this->get_forge()->send_forge_request(
				sprintf('/servers/%s/sites/%s/commands', $site['server_id'], $site['site_id']),
				['command' => $command],
				'POST'
			);

			if (is_wp_error($response)) {
				wu_log_add(
					'integration-forge',
					sprintf(
						'Failed to run deploy command on server %s: %s',
						$site['server_id'],
						$response->get_error_message()
					),
					LogLevel::ERROR
				);
			} else {
				wu_log_add(
					'integration-forge',
					sprintf('Deploy command executed on server %s for site %s', $site['server_id'], $site['site_id'])
				);
			}
		}
	}

	/**
	 * Finds a site by domain on a specific server.
	 *
	 * @since 2.5.0
	 *
	 * @param int    $server_id The Forge server ID.
	 * @param string $domain    The domain name to search for.
	 * @return int|false Site ID if found, false otherwise.
	 */
	private function find_site_by_domain(int $server_id, string $domain) {

		$response = $this->get_forge()->send_forge_request(
			sprintf('/servers/%s/sites', $server_id),
			[],
			'GET'
		);

		if (is_wp_error($response)) {
			return false;
		}

		$body = $this->get_forge()->parse_response($response);

		if (is_wp_error($body) || ! isset($body['sites'])) {
			return false;
		}

		foreach ($body['sites'] as $site) {
			if (isset($site['name']) && $site['name'] === $domain) {
				return (int) $site['id'];
			}
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function test_connection() {

		return $this->get_forge()->test_connection();
	}
}
