<?php
/**
 * Polyfills
 *
 * Currently provides a PHP 8+ compatibility polyfill for str_starts_with().
 * Only declared if the native function does not exist (PHP < 8.0).
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

/**
 * Polyfill for PHP 8.0's str_starts_with().
 *
 * Behaviour:
 * - Case-sensitive, binary-safe prefix check.
 * - Returns true when $needle is an empty string.
 * - Equivalent to: strncmp($haystack, $needle, strlen($needle)) === 0
 *
 * Notes:
 * - Provided only if str_starts_with() does not already exist (PHP < 8.0).
 * - For multibyte/locale-aware checks, consider mb_strpos($haystack, $needle) === 0.
 *
 * @param string $haystack Full string to inspect.
 * @param string $needle Prefix to compare against.
 *
 * @return bool  True if $haystack begins with $needle; false otherwise.
 */
if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( string $haystack, string $needle ): bool {
		return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}
}
