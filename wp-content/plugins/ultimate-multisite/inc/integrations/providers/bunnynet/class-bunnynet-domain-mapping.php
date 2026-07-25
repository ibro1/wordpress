<?php
/**
 * BunnyNet Domain Mapping Capability.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/BunnyNet
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\BunnyNet;

use Psr\Log\LogLevel;
use WP_Ultimo\Integrations\Base_Capability_Module;
use WP_Ultimo\Integrations\Capabilities\Domain_Mapping_Capability;

defined('ABSPATH') || exit;

/**
 * BunnyNet domain mapping capability module.
 *
 * Handles subdomain DNS record creation/removal via the BunnyNet DNS API.
 * BunnyNet does not support automated domain mapping for custom domains —
 * only subdomain records within a configured DNS zone.
 *
 * @since 2.5.0
 */
class BunnyNet_Domain_Mapping extends Base_Capability_Module implements Domain_Mapping_Capability {

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
			'will'     => [],
			'will_not' => [],
		];

		if (is_subdomain_install()) {
			$lines['will']['send_sub_domains'] = __('Add a new DNS record to the configured BunnyNet DNS zone whenever a new site gets created', 'ultimate-multisite');
		} else {
			$lines['will']['subdirectory'] = __('Do nothing! The BunnyNet integration has no effect in subdirectory multisite installs such as this one', 'ultimate-multisite');
		}

		$lines['will_not']['send_domain'] = __('Add domain mappings as new BunnyNet DNS zones', 'ultimate-multisite');

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
		add_filter('wu_domain_dns_get_record', [$this, 'add_bunnynet_dns_entries'], 10, 2);
	}

	/**
	 * Gets the parent BunnyNet_Integration for API calls.
	 *
	 * @since 2.5.0
	 * @return BunnyNet_Integration
	 */
	private function get_bunnynet(): BunnyNet_Integration {

		/** @var BunnyNet_Integration */
		return $this->get_integration();
	}

	/**
	 * Called when a new domain is mapped.
	 *
	 * BunnyNet does not support automated custom domain mapping — no action needed.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being mapped.
	 * @param int    $site_id ID of the site receiving the mapping.
	 * @return void
	 */
	public function on_add_domain(string $domain, int $site_id): void {}

	/**
	 * Called when a mapped domain is removed.
	 *
	 * BunnyNet does not support automated custom domain mapping — no action needed.
	 *
	 * @since 2.5.0
	 *
	 * @param string $domain  The domain name being removed.
	 * @param int    $site_id ID of the site.
	 * @return void
	 */
	public function on_remove_domain(string $domain, int $site_id): void {}

	/**
	 * Called when a new subdomain is added.
	 *
	 * Creates an A record in the BunnyNet DNS zone for the new subdomain.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being added.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_add_subdomain(string $subdomain, int $site_id): void {

		global $current_site;

		$zone_id = $this->get_bunnynet()->get_credential('WU_BUNNYNET_ZONE_ID');

		if (! $zone_id) {
			return;
		}

		if (! str_contains($subdomain, (string) $current_site->domain)) {
			return; // Not a sub-domain of the main domain.
		}

		$subdomain = rtrim(str_replace($current_site->domain, '', $subdomain), '.');

		if (! $subdomain) {
			return;
		}

		// Build FQDN so Domain_Manager can classify main vs. subdomain correctly.
		$full_domain    = $subdomain . '.' . $current_site->domain;
		$should_add_www = apply_filters(
			'wu_bunnynet_should_add_www',
			\WP_Ultimo\Managers\Domain_Manager::get_instance()->should_create_www_subdomain($full_domain),
			$subdomain,
			$site_id
		);

		$domains_to_send = [$subdomain];

		if (! str_starts_with($subdomain, 'www.') && $should_add_www) {
			$domains_to_send[] = 'www.' . $subdomain;
		}

		foreach ($domains_to_send as $subdomain_entry) {
			$server_addr = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '';

			$data = apply_filters(
				'wu_bunnynet_on_add_domain_data',
				[
					'Type'  => 0, // A record type
					'Ttl'   => 3600,
					'Name'  => $subdomain_entry,
					'Value' => $server_addr,
				],
				$subdomain_entry,
				$site_id
			);

			$results = $this->get_bunnynet()->send_bunnynet_request("dnszone/$zone_id/records", 'PUT', $data);

			if (is_wp_error($results)) {
				wu_log_add('integration-bunnynet', sprintf('Failed to add subdomain "%s" to BunnyNet. Reason: %s', $subdomain_entry, $results->get_error_message()), LogLevel::ERROR);

				return;
			}

			wu_log_add('integration-bunnynet', sprintf('Added sub-domain "%s" to BunnyNet.', $subdomain_entry));
		}
	}

	/**
	 * Called when a subdomain is removed.
	 *
	 * Removes the A record from the BunnyNet DNS zone for the subdomain.
	 *
	 * @since 2.5.0
	 *
	 * @param string $subdomain The subdomain being removed.
	 * @param int    $site_id   ID of the site.
	 * @return void
	 */
	public function on_remove_subdomain(string $subdomain, int $site_id): void {

		global $current_site;

		$zone_id = $this->get_bunnynet()->get_credential('WU_BUNNYNET_ZONE_ID');

		if (! $zone_id) {
			return;
		}

		if (! str_contains($subdomain, (string) $current_site->domain)) {
			return; // Not a sub-domain of the main domain.
		}

		$subdomain = rtrim(str_replace($current_site->domain, '', $subdomain), '.');

		if (! $subdomain) {
			return;
		}

		$domains_to_remove = [
			$subdomain,
			'www.' . $subdomain,
		];

		// Get all DNS records for this zone.
		$zone_data = $this->get_bunnynet()->send_bunnynet_request("dnszone/$zone_id", 'GET');

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- BunnyNet API uses PascalCase
		if (is_wp_error($zone_data) || empty($zone_data->Records)) {
			return;
		}

		foreach ($domains_to_remove as $domain_to_remove) {
			foreach ($zone_data->Records as $record) {
				if ($domain_to_remove === $record->Name) {
					$record_id = $record->Id;
					// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					$results = $this->get_bunnynet()->send_bunnynet_request("dnszone/$zone_id/records/$record_id", 'DELETE');

					if (is_wp_error($results)) {
						wu_log_add('integration-bunnynet', sprintf('Failed to remove subdomain "%s" from BunnyNet. Reason: %s', $domain_to_remove, $results->get_error_message()), LogLevel::ERROR);

						continue;
					}

					wu_log_add('integration-bunnynet', sprintf('Removed sub-domain "%s" from BunnyNet.', $domain_to_remove));
				}
			}
		}
	}

	/**
	 * Adds BunnyNet DNS entries to the DNS comparison table.
	 *
	 * @since 2.5.0
	 *
	 * @param array  $dns_records List of current DNS records.
	 * @param string $domain      The domain name.
	 * @return array
	 */
	public function add_bunnynet_dns_entries(array $dns_records, string $domain): array {

		$zone_id = $this->get_bunnynet()->get_credential('WU_BUNNYNET_ZONE_ID');

		if (! $zone_id) {
			return $dns_records;
		}

		$dns_entries = $this->get_bunnynet()->send_bunnynet_request("dnszone/$zone_id", 'GET');

		if (is_wp_error($dns_entries)) {
			return $dns_records;
		}

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- BunnyNet API uses PascalCase
		if (! empty($dns_entries->Records)) {
			$bunnynet_tag = sprintf(
				'<span class="wu-bg-orange-500 wu-text-white wu-p-1 wu-rounded wu-text-3xs wu-uppercase wu-ml-2 wu-font-bold" role="tooltip" aria-label="%s">%s</span>',
				__('BunnyNet', 'ultimate-multisite'),
				__('BunnyNet', 'ultimate-multisite')
			);

			foreach ($dns_entries->Records as $entry) {
				if ($domain === $entry->Name || '@' === $entry->Name) {
					$dns_records[] = [
						'ttl'  => $entry->Ttl ?? 3600,
						'data' => $entry->Value ?? '',
						'type' => $entry->Type ?? 'A',
						'host' => '@' === $entry->Name ? $domain : $entry->Name,
						'tag'  => $bunnynet_tag,
					];
				}
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return $dns_records;
	}

	/**
	 * {@inheritdoc}
	 */
	public function test_connection() {

		return $this->get_bunnynet()->test_connection();
	}
}
