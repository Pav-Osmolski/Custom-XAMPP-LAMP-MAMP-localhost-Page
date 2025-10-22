<?php
/**
 * Polyfills
 *
 * Currently provides a PHP 8+ compatibility polyfill for str_starts_with().
 * Only declared if the native function does not exist (PHP < 8.0).
 *
 * @author Pav
 * @license MIT
 * @version 1.1
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

/**
 * Polyfill for PHP 8.0's str_contains().
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
if ( ! function_exists( 'str_contains' ) ) {
	function str_contains( string $haystack, string $needle ): bool {
		return $needle === '' || strpos( $haystack, $needle ) !== false;
	}
}

/**
 * Polyfill for PHP 8.0's str_ends_with().
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
if ( ! function_exists( 'str_ends_with' ) ) {
	function str_ends_with( string $haystack, string $needle ): bool {
		if ( $needle === '' ) {
			return true;
		}
		$len = strlen( $needle );

		return substr( $haystack, - $len ) === $needle;
	}
}

/**
 * Polyfill for PHP 7.3's is_countable().
 *
 * @param mixed $value
 *
 * @return bool
 */
if ( ! function_exists( 'is_countable' ) ) {
	function is_countable( $value ): bool {
		return is_array( $value ) || $value instanceof Countable;
	}
}

/**
 * Polyfill for PHP 8.0's get_debug_type().
 *
 * @param mixed $value
 *
 * @return string
 */
if ( ! function_exists( 'get_debug_type' ) ) {
	function get_debug_type( $value ): string {
		if ( is_object( $value ) ) {
			return get_class( $value );
		}
		if ( $value === null ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return 'bool';
		}
		if ( is_int( $value ) ) {
			return 'int';
		}
		if ( is_float( $value ) ) {
			return 'float';
		}
		if ( is_string( $value ) ) {
			return 'string';
		}
		if ( is_array( $value ) ) {
			return 'array';
		}
		if ( is_resource( $value ) ) {
			return get_resource_type( $value );
		}

		return gettype( $value );
	}
}
