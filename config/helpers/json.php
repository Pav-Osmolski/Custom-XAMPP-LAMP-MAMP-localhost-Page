<?php
/**
 * JSON helpers
 *
 * validate_and_canonicalise_json(), read_json_array_safely()
 *
 * @author  Pawel Osmolski
 * @version 1.0
 */

/**
 * Validates JSON text and returns a pretty-printed canonical form.
 *
 * @param string $raw
 * @param bool $allowEmptyArray
 *
 * @return string Canonical JSON
 * @throws InvalidArgumentException on invalid non-empty JSON
 */
function validate_and_canonicalise_json( string $raw, bool $allowEmptyArray = true ): string {
	$raw = trim( $raw );
	if ( $raw === '' ) {
		return $allowEmptyArray ? "[]" : "{}";
	}
	$data = json_decode( $raw, true, 512, JSON_THROW_ON_ERROR );
	if ( ! is_array( $data ) ) {
		throw new InvalidArgumentException( 'JSON root must be array or object.' );
	}

	return json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

/**
 * Safely read a JSON file into an associative array.
 *
 * Returns an empty array if the file does not exist, is unreadable,
 * is empty, or contains invalid JSON.
 *
 * @param string $path Absolute file path to the JSON file.
 *
 * @return array<int|string, mixed> Decoded array or [] on failure.
 */
function read_json_array_safely( string $path ): array {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		return [];
	}
	$raw = file_get_contents( $path );
	if ( $raw === false || $raw === '' ) {
		return [];
	}
	$decoded = json_decode( $raw, true );
	if ( is_array( $decoded ) ) {
		return $decoded;
	}
	error_log( basename( $path ) . ' JSON decode failed: ' . json_last_error_msg() );

	return [];
}
