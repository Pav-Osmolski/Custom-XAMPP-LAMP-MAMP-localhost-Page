<?php
/**
 * Path helpers
 *
 * define_path_constant(), normalise_path()
 */

/**
 * Defines a path-related constant if not already defined, with normalisation.
 *
 * Converts slashes to the appropriate OS directory separator and trims
 * trailing separators before defining the constant. Prevents redefinition
 * if the constant is already set.
 *
 * @param string $name The name of the constant to define
 * @param string $default The default path value to normalise and use
 *
 * @return void
 */
function define_path_constant( string $name, string $default ): void {
	if ( ! defined( $name ) ) {
		$normalised = rtrim( str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $default ), DIRECTORY_SEPARATOR );
		define( $name, $normalised );
	}
}

/**
 * Normalises a filesystem path to use the current OS directory separator
 * and removes any trailing slashes.
 *
 * @param string $path The raw user-provided path
 *
 * @return string The cleaned and normalised path
 */
function normalise_path( string $path ): string {
	$path = str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );

	return rtrim( $path, DIRECTORY_SEPARATOR );
}
