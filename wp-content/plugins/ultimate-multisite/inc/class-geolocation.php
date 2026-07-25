<?php
/**
 * Optimized Geolocation Class.
 *
 * High-performance geolocation using local MaxMind database with multi-layer caching.
 * Eliminates external HTTP calls that were causing 1+ second delays.
 *
 * Performance improvements:
 * - Uses bundled MaxMind database reader via Composer (no external API calls)
 * - In-memory static cache for same-request lookups
 * - Object cache integration for cross-request caching
 * - Proper IP detection for CloudFlare, proxies, and load balancers
 * - Falls back gracefully when database is unavailable
 *
 * @package WP_Ultimo
 * @subpackage Checkout
 * @since 2.0.0
 */

namespace WP_Ultimo;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Geolocation Class.
 */
class Geolocation {


	/**
	 * Cache group for geolocation data.
	 */
	const CACHE_GROUP = 'wu_geolocation';

	/**
	 * In-memory cache for current request.
	 *
	 * @var array
	 */
	private static array $memory_cache = [];

	/**
	 * Cached IP address for current request.
	 *
	 * @var string|null
	 */
	private static ?string $cached_ip = null;

	/**
	 * MaxMind database reader instance.
	 *
	 * @var \MaxMind\Db\Reader|null
	 */
	private static ?\MaxMind\Db\Reader $reader = null;

	/**
	 * GeoLite2 DB URL (deprecated - we use local MaxMind database).
	 *
	 * @deprecated 3.4.0
	 */
	const GEOLITE2_DB = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz';

	/**
	 * Hook in geolocation functionality.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Register shutdown handler to close MaxMind reader
		\register_shutdown_function([self::class, 'close_reader']);
	}

	/**
	 * Close the MaxMind database reader.
	 *
	 * @return void
	 */
	public static function close_reader(): void {
		if (null !== self::$reader) {
			try {
				self::$reader->close();
			} catch (\Exception $e) {
				// Ignore errors on shutdown.
				unset($e); // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
			self::$reader = null;
		}
	}

	/**
	 * Get current user IP Address with comprehensive proxy/CDN support.
	 *
	 * Checks headers in order of trust:
	 * 1. CF-Connecting-IP (Cloudflare)
	 * 2. True-Client-IP (Cloudflare Enterprise / Akamai)
	 * 3. X-Real-IP (Nginx proxy)
	 * 4. X-Forwarded-For (Standard proxy header - first IP only)
	 * 5. REMOTE_ADDR (Direct connection)
	 *
	 * @return string The client IP address.
	 */
	public static function get_ip_address(): string {
		// Return cached IP if available (same request optimization)
		if (null !== self::$cached_ip) {
			return self::$cached_ip;
		}

		$ip = '';

		// Cloudflare (most trusted when using CF)
		if (! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$ip = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
		} elseif (! empty($_SERVER['HTTP_TRUE_CLIENT_IP'])) {
			// Cloudflare Enterprise / Akamai
			$ip = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_TRUE_CLIENT_IP']));
		} elseif (! empty($_SERVER['HTTP_X_REAL_IP'])) {
			// Nginx proxy
			$ip = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_X_REAL_IP']));
		} elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// Standard proxy header (take first IP - the client)
			$forwarded = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
			// X-Forwarded-For: client, proxy1, proxy2
			$ips = array_map('trim', explode(',', $forwarded));
			$ip  = self::validate_ip($ips[0]);
		} elseif (! empty($_SERVER['REMOTE_ADDR'])) {
			// Direct connection
			$ip = \sanitize_text_field(\wp_unslash($_SERVER['REMOTE_ADDR']));
		}

		// Validate and cache
		$validated_ip    = self::validate_ip($ip);
		self::$cached_ip = $validated_ip;

		return $validated_ip;
	}

	/**
	 * Validate an IP address.
	 *
	 * @param string $ip The IP address to validate.
	 * @return string The validated IP or empty string.
	 */
	private static function validate_ip(string $ip): string {
		// Remove port from IPv4 (e.g., "1.2.3.4:8080" -> "1.2.3.4")
		if (preg_match('/^(\d+\.\d+\.\d+\.\d+):\d+$/', $ip, $matches)) {
			$ip = $matches[1];
		}
		// Remove brackets and port from IPv6 (e.g., "[::1]:8080" -> "::1")
		if (preg_match('/^\[([^]]+)](:\d+)?$/', $ip, $matches)) {
			$ip = $matches[1];
		}

		// Use WordPress's IP validation
		return (string) \rest_is_ip_address($ip);
	}

	/**
	 * Check if an IP is a private/local address.
	 *
	 * @param string $ip The IP address to check.
	 * @return bool True if private/local.
	 */
	private static function is_private_ip(string $ip): bool {
		if (empty($ip)) {
			return true;
		}

		// Check for private/reserved ranges
		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * Geolocate an IP address.
	 *
	 * Uses multi-layer caching and local database lookup for performance.
	 * Completely eliminates external HTTP calls.
	 *
	 * @param string $ip_address   IP Address (empty = current user).
	 * @param bool   $fallback     If true, fallbacks to alternative IP detection.
	 * @param bool   $api_fallback Deprecated - API fallback is disabled for performance.
	 * @return array{ip: string, country: string, state: string}
	 */
	public static function geolocate_ip(string $ip_address = '', bool $fallback = false, bool $api_fallback = true): array {
		// Get IP if not provided
		if (empty($ip_address)) {
			$ip_address = self::get_ip_address();
		}

		// Check in-memory cache first (same request)
		if (isset(self::$memory_cache[ $ip_address ])) {
			return self::$memory_cache[ $ip_address ];
		}

		// Allow plugins to short-circuit
		$country_code = \apply_filters('wu_geolocate_ip', false, $ip_address, $fallback, $api_fallback);

		if (false === $country_code) {
			// Try to get country from HTTP headers (CDN/proxy provided)
			$country_code = self::get_country_from_headers();

			// If no header, try local database lookup
			if (empty($country_code) && ! empty($ip_address)) {
				// Check object cache
				$cache_key    = 'geo_' . md5($ip_address);
				$country_code = \wp_cache_get($cache_key, self::CACHE_GROUP);

				if (false === $country_code) {
					// Perform database lookup
					$country_code = self::geolocate_via_db($ip_address);

					// Cache the result (even empty results to prevent repeated lookups)
					\wp_cache_set($cache_key, $country_code ?: '', self::CACHE_GROUP, DAY_IN_SECONDS);
				}
			}

			// Handle local/private IPs with fallback
			if (empty($country_code) && $fallback && self::is_private_ip($ip_address)) {
				// For local development, use a default country instead of external API
				$country_code = \apply_filters('wu_geolocation_default_country', 'US');
			}
		}

		$result = [
			'ip'      => $ip_address,
			'country' => is_string($country_code) ? $country_code : '',
			'state'   => '',
		];

		// Cache in memory for this request
		self::$memory_cache[ $ip_address ] = $result;

		return $result;
	}

	/**
	 * Get country code from HTTP headers set by CDN/proxy.
	 *
	 * @return string Country code or empty string.
	 */
	private static function get_country_from_headers(): string {
		$headers = [
			'HTTP_CF_IPCOUNTRY',     // Cloudflare
			'GEOIP_COUNTRY_CODE',    // Nginx GeoIP module
			'HTTP_X_COUNTRY_CODE',   // Generic proxy header
			'MM_COUNTRY_CODE',       // MaxMind via server
		];

		foreach ($headers as $header) {
			if (! empty($_SERVER[ $header ])) {
				$code = strtoupper(\sanitize_text_field(\wp_unslash($_SERVER[ $header ])));
				// Validate it looks like a country code (2 letters)
				if (preg_match('/^[A-Z]{2}$/', $code)) {
					return $code;
				}
			}
		}

		return '';
	}

	/**
	 * Geolocate using local MaxMind database.
	 *
	 * Uses the bundled MaxMind reader via Composer autoloader.
	 *
	 * @param string $ip_address The IP address to lookup.
	 * @return string Country code or empty string.
	 */
	private static function geolocate_via_db(string $ip_address): string {
		$database_path = self::get_local_database_path();

		if (! file_exists($database_path)) {
			return '';
		}

		try {
			// Reuse reader instance for performance
			if (null === self::$reader) {
				// MaxMind\Db\Reader is loaded via Composer autoloader
				self::$reader = new \MaxMind\Db\Reader($database_path);
			}

			$data = self::$reader->get($ip_address);
			if (isset($data['country']['iso_code'])) {
				return strtoupper(\sanitize_text_field($data['country']['iso_code']));
			}
		} catch (\Exception $e) {
			// Log error but don't break the site
			wu_log_add('geolocation', 'Geolocation DB error: ' . $e->getMessage());
		}

		return '';
	}

	/**
	 * Get the path to the local MaxMind database.
	 *
	 * Checks locations in order of preference:
	 * 1. WP Ultimo's own database path (in uploads directory)
	 * 2. Custom path via filter
	 *
	 * @param string $deprecated Deprecated parameter.
	 * @return string Database path.
	 */
	public static function get_local_database_path(string $deprecated = '2'): string {
		// Use WP Ultimo's own path in uploads directory
		$upload_dir = \wp_upload_dir();
		$wu_path    = $upload_dir['basedir'] . '/GeoLite2-Country.mmdb';

		return \apply_filters('wu_geolocation_local_database_path', $wu_path, $deprecated);
	}

	/**
	 * Get user IP Address using an external service.
	 *
	 * @deprecated External IP lookup is disabled for performance.
	 *             Use get_ip_address() instead.
	 * @return string
	 */
	public static function get_external_ip_address(): string {
		// For backwards compatibility, just return the detected IP
		// External API calls have been removed for performance
		$ip = self::get_ip_address();

		// If it's a private IP, return a placeholder
		if (self::is_private_ip($ip)) {
			return '0.0.0.0';
		}

		return $ip;
	}

	/**
	 * Check if server supports MaxMind GeoLite2 Reader.
	 *
	 * @return bool
	 */
	private static function supports_geolite2(): bool {
		// Check if MaxMind reader is available via Composer autoloader
		return class_exists('MaxMind\Db\Reader');
	}

	/**
	 * Check if geolocation is enabled.
	 *
	 * @param string $current_settings Current geolocation settings.
	 * @return bool
	 */
	private static function is_geolocation_enabled(string $current_settings): bool {
		return in_array($current_settings, ['geolocation', 'geolocation_ajax'], true);
	}

	/**
	 * Update geoip database.
	 *
	 * @deprecated Database management must be handled separately.
	 * @return void
	 */
	public static function update_database(): void {
		// Database updates should be handled by a separate process
		// This method is kept for backwards compatibility
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

	/**
	 * Maybe trigger a DB update for the first time.
	 *
	 * @deprecated Database management must be handled separately.
	 * @param string $new_value New value.
	 * @param string $old_value Old value.
	 * @return string
	 */
	public static function maybe_update_database(
		string $new_value,
		string $old_value
	): string {

		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $new_value;
	}

	/**
	 * Disable geolocation on legacy PHP.
	 *
	 * @deprecated PHP 8.0+ is now required.
	 * @param string $default_customer_address Current value.
	 * @return string
	 */
	public static function disable_geolocation_on_legacy_php(string $default_customer_address): string {
		return $default_customer_address;
	}

	/**
	 * Clear all geolocation caches.
	 *
	 * Useful when database is updated or for debugging.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$memory_cache = [];
		self::$cached_ip    = null;

		// Note: Object cache group deletion depends on cache implementation
		// Most object caches don't support group deletion
		if (function_exists('wp_cache_flush_group')) {
			\wp_cache_flush_group(self::CACHE_GROUP);
		}
	}
}
