<?php
/**
 * Node Management Capability Interface.
 *
 * Defines the contract for hosting integrations that can manage Node.js
 * applications and processes (e.g., CloudLinux Node.js Selector, cPanel
 * PassengerApps, PM2, systemd services).
 *
 * @package WP_Ultimo
 * @subpackage Integrations/Capabilities
 * @since 2.5.0
 */

namespace WP_Ultimo\Integrations\Capabilities;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Interface for Node.js management capability modules.
 *
 * @since 2.5.0
 */
interface Node_Management_Capability {

	/**
	 * Detect available Node.js installations on the server.
	 *
	 * @since 2.5.0
	 * @return array{
	 *     found: bool,
	 *     installations: array<array{path: string, version: string, source: string}>,
	 *     message: string
	 * }
	 */
	public function detect_node(): array;

	/**
	 * Register/create a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param array $config {
	 *     Application configuration.
	 *
	 *     @type string $app_root   Absolute path to the application root directory.
	 *     @type string $app_url    URL path where the application will be accessible.
	 *     @type string $startup    Startup file (e.g., 'app.js', 'server.js').
	 *     @type string $node_version Node.js version to use (e.g., '22').
	 *     @type int    $port       Port number for the application (if applicable).
	 * }
	 * @return array{success: bool, message: string, app_id?: string}
	 */
	public function create_app(array $config): array;

	/**
	 * Start a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier (path, name, or ID).
	 * @return array{success: bool, message: string}
	 */
	public function start_app(string $app_id): array;

	/**
	 * Stop a running Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier.
	 * @return array{success: bool, message: string}
	 */
	public function stop_app(string $app_id): array;

	/**
	 * Restart a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier.
	 * @return array{success: bool, message: string}
	 */
	public function restart_app(string $app_id): array;

	/**
	 * Remove/unregister a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier.
	 * @return array{success: bool, message: string}
	 */
	public function destroy_app(string $app_id): array;

	/**
	 * Get the status of a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier.
	 * @return array{
	 *     success: bool,
	 *     running: bool,
	 *     message: string,
	 *     pid?: int,
	 *     uptime?: string,
	 *     memory?: string
	 * }
	 */
	public function get_app_status(string $app_id): array;

	/**
	 * List all registered Node.js applications.
	 *
	 * @since 2.5.0
	 * @return array{
	 *     success: bool,
	 *     apps: array<array{
	 *         app_id: string,
	 *         app_root: string,
	 *         running: bool,
	 *         node_version?: string
	 *     }>,
	 *     message: string
	 * }
	 */
	public function list_apps(): array;

	/**
	 * Install npm dependencies for a Node.js application.
	 *
	 * @since 2.5.0
	 *
	 * @param string $app_id Application identifier.
	 * @return array{success: bool, message: string, output?: string}
	 */
	public function install_deps(string $app_id): array;
}
