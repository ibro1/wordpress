<?php
/**
 * Credential Store helper for encrypting/decrypting hosting integration credentials.
 *
 * @package WP_Ultimo
 * @subpackage Helpers
 * @since 2.3.0
 */

namespace WP_Ultimo\Helpers;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Handles encryption and decryption of hosting credentials stored in network options.
 *
 * @since 2.3.0
 */
class Credential_Store {

	/**
	 * The cipher method used for encryption.
	 *
	 * @var string
	 */
	const CIPHER_METHOD = 'aes-256-cbc';

	/**
	 * Prefix for encrypted values to identify them.
	 *
	 * @var string
	 */
	const ENCRYPTED_PREFIX = '$wu_enc$';

	/**
	 * Prefix for sodium-encrypted values.
	 *
	 * @var string
	 */
	const SODIUM_PREFIX = '$wu_sodium$';

	/**
	 * Encrypt a value for storage.
	 *
	 * Tries OpenSSL first, then libsodium as fallback.
	 * Refuses to store if no real encryption is available.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value The plaintext value to encrypt.
	 * @return string The encrypted value, or empty string if encryption is unavailable.
	 */
	public static function encrypt(string $value): string {

		if (empty($value)) {
			return '';
		}

		// Try OpenSSL first
		if (function_exists('openssl_encrypt') && in_array(self::CIPHER_METHOD, openssl_get_cipher_methods(), true)) {
			$key = self::get_encryption_key();
			$iv  = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));

			$encrypted = openssl_encrypt($value, self::CIPHER_METHOD, $key, 0, $iv);

			if (false !== $encrypted) {
				return self::ENCRYPTED_PREFIX . base64_encode($iv . $encrypted); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}
		}

		// Fallback to libsodium (available in PHP 7.2+)
		if (function_exists('sodium_crypto_secretbox')) {
			$key   = self::get_sodium_key();
			$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

			$encrypted = sodium_crypto_secretbox($value, $nonce, $key);

			return self::SODIUM_PREFIX . base64_encode($nonce . $encrypted); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		// No encryption available — refuse to store in plaintext
		wu_log_add('credential-store', 'Cannot encrypt credential: neither OpenSSL nor libsodium is available.', \Psr\Log\LogLevel::ERROR);

		return '';
	}

	/**
	 * Decrypt a stored value.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value The encrypted value to decrypt.
	 * @return string The decrypted plaintext value.
	 */
	public static function decrypt(string $value): string {

		if (empty($value)) {
			return '';
		}

		// Handle sodium-encrypted values
		if (strpos($value, self::SODIUM_PREFIX) === 0) {
			return self::decrypt_sodium($value);
		}

		// Handle OpenSSL-encrypted values
		if (strpos($value, self::ENCRYPTED_PREFIX) !== 0) {
			return $value;
		}

		$encoded = substr($value, strlen(self::ENCRYPTED_PREFIX));
		$decoded = base64_decode($encoded); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if (false === $decoded) {
			return '';
		}

		if ( ! function_exists('openssl_decrypt') || ! in_array(self::CIPHER_METHOD, openssl_get_cipher_methods(), true)) {
			// Cannot decrypt without OpenSSL — do NOT return raw decoded data
			wu_log_add('credential-store', 'Cannot decrypt credential: OpenSSL is not available.', \Psr\Log\LogLevel::ERROR);

			return '';
		}

		$iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);
		$iv        = substr($decoded, 0, $iv_length);
		$encrypted = substr($decoded, $iv_length);

		if (empty($encrypted)) {
			return '';
		}

		$key       = self::get_encryption_key();
		$decrypted = openssl_decrypt($encrypted, self::CIPHER_METHOD, $key, 0, $iv);

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Decrypt a sodium-encrypted value.
	 *
	 * @since 2.3.0
	 *
	 * @param string $value The sodium-encrypted value.
	 * @return string The decrypted plaintext value.
	 */
	private static function decrypt_sodium(string $value): string {

		if ( ! function_exists('sodium_crypto_secretbox_open')) {
			wu_log_add('credential-store', 'Cannot decrypt credential: libsodium is not available.', \Psr\Log\LogLevel::ERROR);

			return '';
		}

		$encoded = substr($value, strlen(self::SODIUM_PREFIX));
		$decoded = base64_decode($encoded); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if (false === $decoded) {
			return '';
		}

		$nonce     = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encrypted = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

		if (empty($encrypted)) {
			return '';
		}

		try {
			$key       = self::get_sodium_key();
			$decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
		} catch (\SodiumException $e) {
			wu_log_add('credential-store', 'Sodium decryption failed: ' . $e->getMessage(), \Psr\Log\LogLevel::ERROR);

			return '';
		}

		return false === $decrypted ? '' : $decrypted;
	}

	/**
	 * Get the encryption key derived from WordPress salts (for OpenSSL).
	 *
	 * @since 2.3.0
	 * @return string
	 */
	private static function get_encryption_key(): string {

		return hash('sha256', wp_salt('auth'), true);
	}

	/**
	 * Get the encryption key for libsodium (must be exactly 32 bytes).
	 *
	 * @since 2.3.0
	 * @return string
	 */
	private static function get_sodium_key(): string {

		return hash('sha256', wp_salt('auth'), true);
	}
}
