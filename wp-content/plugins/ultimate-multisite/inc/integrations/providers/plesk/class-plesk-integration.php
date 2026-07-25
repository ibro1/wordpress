<?php
/**
 * Plesk Integration.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers/Plesk
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\Plesk;

use WP_Ultimo\Integrations\Integration;

defined('ABSPATH') || exit;

/**
 * Plesk integration provider.
 *
 * Uses Plesk's REST API v2 CLI gateway to manage site aliases
 * and subdomains for domain mapping.
 *
 * @since 2.5.0
 */
class Plesk_Integration extends Integration {

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 */
	public function __construct() {

		parent::__construct('plesk', 'Plesk');

		$this->set_logo(function_exists('wu_get_asset') ? wu_get_asset('plesk.svg', 'img/hosts') : '');
		$this->set_tutorial_link('https://ultimatemultisite.com/docs/user-guide/host-integrations/plesk');
		$this->set_constants(
			[
				'WU_PLESK_HOST',
				['WU_PLESK_API_KEY', 'WU_PLESK_PASSWORD'],
				'WU_PLESK_DOMAIN',
			]
		);
		$this->set_optional_constants(['WU_PLESK_PORT', 'WU_PLESK_USERNAME']);
		$this->set_supports(['autossl', 'no-instructions']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {

		return __('Integrates with Plesk to add and remove domain aliases automatically when domains are mapped or removed.', 'ultimate-multisite');
	}

	/**
	 * {@inheritdoc}
	 */
	public function detect(): bool {

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function test_connection() {

		$response = $this->send_plesk_api_request('/api/v2/server', 'GET');

		if (is_wp_error($response)) {
			return $response;
		}

		if (isset($response['platform'])) {
			return true;
		}

		return new \WP_Error(
			'connection-failed',
			sprintf(
				/* translators: %s is the error message from the API */
				__('Failed to connect to Plesk API: %s', 'ultimate-multisite'),
				$response['error'] ?? __('Unknown error', 'ultimate-multisite')
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_fields(): array {

		return [
			'WU_PLESK_HOST'     => [
				'title'       => __('Plesk Host', 'ultimate-multisite'),
				'desc'        => __('The hostname or IP address of your Plesk server (e.g., server.example.com). Do not include the port or protocol.', 'ultimate-multisite'),
				'placeholder' => __('e.g. server.example.com', 'ultimate-multisite'),
			],
			'WU_PLESK_PORT'     => [
				'title'       => __('Plesk Port', 'ultimate-multisite'),
				'desc'        => __('The port Plesk listens on. Defaults to 8443 if not set.', 'ultimate-multisite'),
				'placeholder' => __('8443', 'ultimate-multisite'),
				'value'       => '8443',
			],
			'WU_PLESK_API_KEY'  => [
				'type'        => 'password',
				'html_attr'   => ['autocomplete' => 'new-password'],
				'title'       => __('Plesk API Key', 'ultimate-multisite'),
				'desc'        => __('Generate an API key in Plesk under Tools &amp; Settings &rarr; API. Optional if using username/password authentication.', 'ultimate-multisite'),
				'placeholder' => __('Your API key', 'ultimate-multisite'),
			],
			'WU_PLESK_USERNAME' => [
				'title'       => __('Plesk Username', 'ultimate-multisite'),
				'desc'        => __('Plesk admin username. Only required if authenticating with a password instead of an API key.', 'ultimate-multisite'),
				'placeholder' => __('e.g. admin', 'ultimate-multisite'),
			],
			'WU_PLESK_PASSWORD' => [
				'type'        => 'password',
				'html_attr'   => ['autocomplete' => 'new-password'],
				'title'       => __('Plesk Password', 'ultimate-multisite'),
				'desc'        => __('Plesk admin password. Optional if using API key authentication.', 'ultimate-multisite'),
				'placeholder' => __('Your password', 'ultimate-multisite'),
			],
			'WU_PLESK_DOMAIN'   => [
				'title'       => __('Base Domain', 'ultimate-multisite'),
				'desc'        => __('The domain in Plesk that your WordPress multisite is served from. Aliases will be attached to this domain.', 'ultimate-multisite'),
				'placeholder' => __('e.g. network.example.com', 'ultimate-multisite'),
			],
		];
	}

	/**
	 * Sends a request to the Plesk REST API v2.
	 *
	 * Supports API key authentication (preferred) or HTTP Basic Auth as a fallback.
	 *
	 * @since 2.5.0
	 *
	 * @param string       $endpoint API endpoint (e.g. /api/v2/server).
	 * @param string       $method   HTTP method (GET, POST, DELETE, etc.).
	 * @param array|string $data     Request body data (for POST/PUT/PATCH).
	 * @return array|\WP_Error
	 */
	public function send_plesk_api_request(string $endpoint, string $method = 'GET', $data = []) {

		$host = $this->get_credential('WU_PLESK_HOST');

		if (empty($host)) {
			wu_log_add('integration-plesk', 'WU_PLESK_HOST not defined or empty');

			return new \WP_Error('wu_plesk_no_host', __('Missing WU_PLESK_HOST', 'ultimate-multisite'));
		}

		$port    = $this->get_credential('WU_PLESK_PORT') ?: '8443';
		$api_url = sprintf('https://%s:%s%s', $host, $port, $endpoint);

		$headers = [
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
			'User-Agent'   => 'WP-Ultimo-Plesk-Integration/2.0',
		];

		// Auth: prefer API key, fall back to Basic Auth
		$api_key  = $this->get_credential('WU_PLESK_API_KEY');
		$username = $this->get_credential('WU_PLESK_USERNAME');
		$password = $this->get_credential('WU_PLESK_PASSWORD');

		if (! empty($api_key)) {
			$headers['X-API-Key'] = $api_key;
		} elseif (! empty($username) && ! empty($password)) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
		} else {
			wu_log_add('integration-plesk', 'No authentication credentials configured (need API key or username/password)');

			return new \WP_Error('wu_plesk_no_auth', __('Missing Plesk authentication credentials', 'ultimate-multisite'));
		}

		$args = [
			'method'  => $method,
			'timeout' => 45,
			'headers' => $headers,
		];

		if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && ! empty($data)) {
			$args['body'] = wp_json_encode($data);
		}

		wu_log_add('integration-plesk', sprintf('Making %s request to: %s', $method, $api_url));

		if (! empty($data)) {
			wu_log_add('integration-plesk', sprintf('Request data: %s', wp_json_encode($data)));
		}

		$response = wp_remote_request($api_url, $args);

		if (is_wp_error($response)) {
			wu_log_add('integration-plesk', sprintf('API request failed: %s', $response->get_error_message()));

			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		wu_log_add('integration-plesk', sprintf('API response code: %d, body: %s', $response_code, $response_body));

		if ($response_code >= 200 && $response_code < 300) {
			if (empty($response_body)) {
				return ['success' => true];
			}

			$body = json_decode($response_body, true);

			if (json_last_error() === JSON_ERROR_NONE) {
				return $body;
			}

			// CLI gateway may return plain text on success
			return [
				'success' => true,
				'output'  => $response_body,
			];
		}

		$error_data = [
			'success'       => false,
			'error'         => sprintf('HTTP %d error', $response_code),
			'response_code' => $response_code,
			'response_body' => $response_body,
		];

		if (! empty($response_body)) {
			$error_body = json_decode($response_body, true);

			if (json_last_error() === JSON_ERROR_NONE && isset($error_body['message'])) {
				$error_data['error'] = $error_body['message'];
			}
		}

		return new \WP_Error(
			'wu_plesk_api_error',
			$error_data['error'],
			$error_data
		);
	}
}
