<?php
/**
 * BunnyNet Integration.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/BunnyNet
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\BunnyNet;

use WP_Ultimo\Integrations\Integration;

defined('ABSPATH') || exit;

/**
 * BunnyNet integration provider.
 *
 * @since 2.5.0
 */
class BunnyNet_Integration extends Integration {

	/**
	 * BunnyNet API base URL.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.bunny.net/';

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 */
	public function __construct() {

		parent::__construct('bunnynet', 'BunnyNet');

		$this->set_logo(function_exists('wu_get_asset') ? wu_get_asset('bunnynet.svg', 'img/hosts') : '');
		$this->set_tutorial_link('https://github.com/superdav42/wp-multisite-waas/wiki/BunnyNet-Integration');
		$this->set_constants(
			[
				'WU_BUNNYNET_API_KEY',
				'WU_BUNNYNET_ZONE_ID',
			]
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {

		return __('BunnyNet is a global content delivery network (CDN) and edge storage platform that provides DNS management, CDN acceleration, and DDoS protection for your websites and applications.', 'ultimate-multisite');
	}

	/**
	 * {@inheritdoc}
	 */
	public function detect(): bool {

		// No automatic detection for BunnyNet.
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function test_connection() {

		$zone_id = $this->get_credential('WU_BUNNYNET_ZONE_ID');

		if (! $zone_id) {
			return new \WP_Error('bunnynet-error', __('Zone ID is required.', 'ultimate-multisite'));
		}

		$results = $this->send_bunnynet_request("dnszone/$zone_id");

		if (is_wp_error($results)) {
			return $results;
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_fields(): array {

		return [
			'WU_BUNNYNET_ZONE_ID' => [
				'title'       => __('DNS Zone ID', 'ultimate-multisite'),
				'placeholder' => __('e.g. 12345', 'ultimate-multisite'),
			],
			'WU_BUNNYNET_API_KEY' => [
				'title'       => __('API Key', 'ultimate-multisite'),
				'placeholder' => __('e.g. your-bunnynet-api-key', 'ultimate-multisite'),
				'type'        => 'password',
				'html_attr'   => [
					'autocomplete' => 'new-password',
				],
			],
		];
	}

	/**
	 * Renders the instructions content.
	 *
	 * @since 2.5.0
	 * @return void
	 */
	public function get_instructions(): void {

		wu_get_template('wizards/host-integrations/bunnynet-instructions');
	}

	/**
	 * Sends an API call to BunnyNet.
	 *
	 * @since 2.5.0
	 *
	 * @param string $endpoint The endpoint to call.
	 * @param string $method   The HTTP verb. Defaults to GET.
	 * @param array  $data     The data to send.
	 * @return object|\WP_Error
	 */
	public function send_bunnynet_request(string $endpoint = 'dnszone', string $method = 'GET', array $data = []) {

		$endpoint_url = self::API_BASE_URL . $endpoint;

		$args = [
			'method'  => $method,
			'headers' => [
				'AccessKey'    => $this->get_credential('WU_BUNNYNET_API_KEY'),
				'Content-Type' => 'application/json',
			],
		];

		if ('GET' !== $method && ! empty($data)) {
			$args['body'] = wp_json_encode($data);
		}

		$response = wp_remote_request($endpoint_url, $args);

		if (! is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);
			$code = wp_remote_retrieve_response_code($response);

			if ($code >= 200 && $code < 300) {
				return json_decode($body);
			}

			$error_message = wp_remote_retrieve_response_message($response);

			return new \WP_Error('bunnynet-error', sprintf('%s: %s', $error_message, $body));
		}

		return $response;
	}
}
