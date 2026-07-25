<?php
/**
 * Handles hashing to encode ids and prevent spoofing due to auto-increments.
 *
 * Uses a pure PHP implementation with no external dependencies.
 *
 * @package WP_Ultimo
 * @subpackage Helper
 * @since 2.0.0
 */

namespace WP_Ultimo\Helpers;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Handles hashing to encode ids and prevent spoofing due to auto-increments.
 *
 * @since 2.0.0
 */
class Hash {

	/**
	 * Hash length.
	 */
	const LENGTH = 10;

	/**
	 * The character set used for encoding.
	 */
	const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Encodes a number or ID. Do not use to encode strings.
	 *
	 * @since 2.0.0
	 *
	 * @param integer $number Number to encode.
	 * @param string  $group Hash group. Used to increase entropy.
	 * @return string
	 */
	public static function encode($number, $group = 'ultimate-multisite') {

		$alphabet = self::shuffle_alphabet($group);
		$base     = strlen($alphabet);
		$seed     = self::derive_seed($group);

		$obfuscated = (int) $number ^ $seed;

		// Ensure positive value for base conversion.
		if ($obfuscated < 0) {
			$obfuscated = ~$obfuscated;
		}

		$result = '';

		do {
			$result = $alphabet[ $obfuscated % $base ] . $result;

			$obfuscated = intdiv($obfuscated, $base);
		} while ($obfuscated > 0);

		return str_pad($result, self::LENGTH, $alphabet[0], STR_PAD_LEFT);
	}

	/**
	 * Decodes a hash back into an integer.
	 *
	 * @since 2.0.0
	 *
	 * @param string $hash Hash to decode.
	 * @param string $group Hash group. Used to increase entropy.
	 * @return string|int
	 */
	public static function decode($hash, $group = 'ultimate-multisite') {

		if (empty($hash) || ! is_string($hash)) {
			return false;
		}

		$alphabet = self::shuffle_alphabet($group);
		$base     = strlen($alphabet);
		$seed     = self::derive_seed($group);

		$number      = 0;
		$hash_length = strlen($hash);

		for ($i = 0; $i < $hash_length; $i++) {
			$pos = strpos($alphabet, $hash[ $i ]);

			if (false === $pos) {
				return false;
			}

			$number = $number * $base + $pos;
		}

		return $number ^ $seed;
	}

	/**
	 * Creates a deterministic shuffled alphabet based on the group string.
	 *
	 * Uses md5 as a portable source of deterministic pseudo-random bytes.
	 *
	 * @since 2.5.0
	 *
	 * @param string $group The group/salt string.
	 * @return string The shuffled alphabet.
	 */
	private static function shuffle_alphabet(string $group): string {

		$chars = str_split(self::ALPHABET);
		$key   = md5($group);

		for ($i = count($chars) - 1; $i > 0; $i--) {
			$j = ord($key[ $i % strlen($key) ]) % ($i + 1);

			[$chars[ $i ], $chars[ $j ]] = [$chars[ $j ], $chars[ $i ]];
		}

		return implode('', $chars);
	}

	/**
	 * Derives a positive numeric seed from the group string.
	 *
	 * @since 2.5.0
	 *
	 * @param string $group The group/salt string.
	 * @return int A positive integer seed.
	 */
	private static function derive_seed(string $group): int {

		return abs(crc32($group . ':wu-hash-seed'));
	}
}
