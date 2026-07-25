<?php
/**
 * CPanel Integration.
 *
 * Shared cPanel integration providing API access for domain mapping
 * and other capabilities.
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Providers
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Providers\CPanel;

use WP_Ultimo\Integrations\Integration;
use WP_Ultimo\Integrations\Host_Providers\CPanel_API\CPanel_API;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * CPanel integration provider.
 *
 * @since 2.5.0
 */
class CPanel_Integration extends Integration {

	/**
	 * Holds the API object.
	 *
	 * @since 2.5.0
	 * @var CPanel_API|null
	 */
	protected ?CPanel_API $api = null;

	/**
	 * Constructor.
	 *
	 * @since 2.5.0
	 */
	public function __construct() {

		parent::__construct('cpanel', 'cPanel');

		$this->set_logo(function_exists('wu_get_asset') ? wu_get_asset('cpanel.svg', 'img/hosts') : '');
		$this->set_tutorial_link('https://ultimatemultisite.com/docs/user-guide/host-integrations/cpanel');
		$this->set_constants(
			[
				'WU_CPANEL_USERNAME',
				['WU_CPANEL_API_TOKEN', 'WU_CPANEL_PASSWORD'], // Either API token (recommended) or password
				'WU_CPANEL_HOST',
			]
		);
		$this->set_optional_constants(['WU_CPANEL_PORT', 'WU_CPANEL_ROOT_DIR']);
		$this->set_supports(['autossl']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {

		return __('cPanel is the management panel being used on a large number of shared and dedicated hosts across the globe.', 'ultimate-multisite');
	}

	/**
	 * {@inheritdoc}
	 */
	public function detect(): bool {

		return false;
	}

	/**
	 * Tests the connection with the cPanel API.
	 *
	 * @since 2.5.0
	 * @return true|\WP_Error
	 */
	public function test_connection() {

		$results = $this->load_api()->api2('Cron', 'fetchcron', []);

		if (isset($results->cpanelresult->data) && ! isset($results->cpanelresult->error)) {
			return true;
		}

		return new \WP_Error('cpanel-error', __('Could not connect to cPanel.', 'ultimate-multisite'));
	}

	/**
	 * Returns the list of installation fields.
	 *
	 * @since 2.5.0
	 * @return array
	 */
	public function get_fields(): array {

		return [
			'WU_CPANEL_USERNAME'  => [
				'title'       => __('cPanel Username', 'ultimate-multisite'),
				'desc'        => __('Your cPanel account username. Check your hosting welcome email or contact your host if you use SSO.', 'ultimate-multisite'),
				'placeholder' => __('e.g. username', 'ultimate-multisite'),
			],
			'WU_CPANEL_API_TOKEN' => [
				'type'        => 'password',
				'html_attr'   => [
					'autocomplete' => 'new-password',
				],
				'title'       => __('cPanel API Token (Recommended)', 'ultimate-multisite'),
				'desc'        => __('Create in cPanel → Security → Manage API Tokens. More secure than password authentication.', 'ultimate-multisite'),
				'placeholder' => __('e.g. U7HMR63FHY282DQZ4H5BIH16JLYSO01M', 'ultimate-multisite'),
			],
			'WU_CPANEL_PASSWORD'  => [
				'type'        => 'password',
				'html_attr'   => [
					'autocomplete' => 'new-password',
				],
				'title'       => __('cPanel Password (Alternative)', 'ultimate-multisite'),
				'desc'        => __('Only required if not using an API token above. If you use SSO to access cPanel, you may need to request direct credentials from your host.', 'ultimate-multisite'),
				'placeholder' => __('password', 'ultimate-multisite'),
			],
			'WU_CPANEL_HOST'      => [
				'title'       => __('cPanel Host', 'ultimate-multisite'),
				'desc'        => __('Your domain or server hostname — without the port number or trailing characters. Example: yourdomain.com or server123.hostingprovider.com', 'ultimate-multisite'),
				'placeholder' => __('e.g. yourdomain.com', 'ultimate-multisite'),
			],
			'WU_CPANEL_PORT'      => [
				'title'       => __('cPanel Port', 'ultimate-multisite'),
				'desc'        => __('The cPanel port. Standard is 2083 for HTTPS connections.', 'ultimate-multisite'),
				'placeholder' => __('Defaults to 2083', 'ultimate-multisite'),
				'value'       => 2083,
			],
			'WU_CPANEL_ROOT_DIR'  => [
				'title'       => __('Root Directory', 'ultimate-multisite'),
				'desc'        => __('The web root directory where your WordPress installation is located.', 'ultimate-multisite'),
				'placeholder' => __('Defaults to /public_html', 'ultimate-multisite'),
				'value'       => '/public_html',
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

		wu_get_template('wizards/host-integrations/cpanel-instructions');
	}

	/**
	 * Load the cPanel API.
	 *
	 * @since 2.5.0
	 * @return CPanel_API
	 */
	public function load_api(): CPanel_API {

		if (null === $this->api) {
			$username  = $this->get_credential('WU_CPANEL_USERNAME');
			$password  = $this->get_credential('WU_CPANEL_PASSWORD');
			$api_token = $this->get_credential('WU_CPANEL_API_TOKEN');
			$host      = $this->get_credential('WU_CPANEL_HOST');
			$port      = (int) ($this->get_credential('WU_CPANEL_PORT') ?: 2083);

			/*
			 * Sanitize the host value to handle common user input errors:
			 * - Strip protocol prefix (https://, http://)
			 * - Strip trailing semicolons, slashes, and whitespace
			 * - Strip port number if accidentally included (e.g. yourdomain.com:2083)
			 */
			$clean_host = preg_replace('#^https?://#', '', (string) $host);
			$clean_host = rtrim($clean_host, "; \t\n\r\0\x0B/");
			$clean_host = preg_replace('#:\d+$#', '', $clean_host);

			$this->api = new CPanel_API(
				$username,
				$password ?: null,
				$clean_host,
				$port,
				false,
				$api_token ?: null
			);
		}

		return $this->api;
	}
}
