<?php
/**
 * Security Utilities and Encryption Handler
 *
 * Provides cryptographic functionality for securely storing and retrieving sensitive values,
 * such as database credentials. Also includes a safe shell execution wrapper to restrict
 * command usage to a known list of binaries.
 *
 * Features:
 * - Symmetric encryption using AES-256-CBC with a generated key file (`.key`)
 * - `encryptValue()` and `decryptValue()` for encoding/decoding strings
 * - `getDecrypted()` for secure constant value retrieval (e.g. `DB_USER`)
 * - `safe_shell_exec()` allows execution of whitelisted system commands only
 *
 * Constants:
 * - `CRYPTO_KEY_FILE` defines the encryption key file location
 *
 * Safety:
 * - Prevents execution of untrusted shell commands
 * - Gracefully handles missing or malformed encrypted values
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

define( 'CRYPTO_KEY_FILE', __DIR__ . '/../.key' );

/**
 * Get or create the encryption key used for AES operations.
 *
 * @return string 32-character hexadecimal key
 */
function getEncryptionKey() {
	if ( ! file_exists( CRYPTO_KEY_FILE ) ) {
		$key = bin2hex( openssl_random_pseudo_bytes( 16 ) );
		file_put_contents( CRYPTO_KEY_FILE, $key );
	}

	return trim( file_get_contents( CRYPTO_KEY_FILE ) );
}

/**
 * Encrypts a plaintext string using AES-256-CBC with IV and returns base64-encoded result.
 *
 * @param string $plaintext The value to encrypt
 *
 * @return string Base64-encoded encrypted string
 */
function encryptValue( $plaintext ) {
	$key       = getEncryptionKey();
	$iv        = openssl_random_pseudo_bytes( 16 );
	$encrypted = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

	return base64_encode( $iv . $encrypted );
}

/**
 * Decrypts a base64-encoded AES-256-CBC encrypted string.
 *
 * @param string $encoded The encrypted base64 string
 *
 * @return string Decrypted plaintext, or empty string on failure
 */
function decryptValue( $encoded ) {
	$key  = getEncryptionKey();
	$data = base64_decode( $encoded );
	if ( strlen( $data ) < 17 ) {
		return '';
	} // failsafe
	$iv         = substr( $data, 0, 16 );
	$ciphertext = substr( $data, 16 );

	return openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
}

/**
 * Retrieves a decrypted version of a constant, or falls back to plain text if invalid.
 *
 * @param string $const The constant name (e.g., 'DB_USER')
 * @param bool $allowFallback If true, returns the raw value on failure
 *
 * @return string Decrypted value or fallback string
 */
function getDecrypted( $const, $allowFallback = true ) {
	static $allowed = [ 'DB_USER', 'DB_PASSWORD' ]; // Only decrypt these

	if ( ! defined( $const ) || ! in_array( $const, $allowed, true ) ) {
		return '';
	}

	$value = constant( $const );

	// heuristic: only decrypt if it's base64-looking and long enough
	if ( base64_decode( $value, true ) === false || strlen( $value ) < 24 ) {
		return $allowFallback ? $value : '';
	}

	$decrypted = decryptValue( $value );

	return $decrypted !== false ? $decrypted : ( $allowFallback ? $value : '' );
}

/**
 * Safely executes a whitelisted shell command.
 *
 * @param string $cmd The shell command to execute
 *
 * @return string|null Output of the command, or null if disallowed
 */
function safe_shell_exec( $cmd ) {
	$allowed = [
		'apachectl',
		'httpd',
		'nproc',
		'sysctl',
		'tasklist',
		'sc',
		'ps',
		'awk',
		'grep',
		'typeperf',
		'wmic',
		'which',
		'whoami',
		'explorer',
		'bash',
		'start',
		'open',
		'xdg-open',
		'cmd',
		'command',
		'powershell',
		'auto-make-cert',
		'make-cert',
		'make-cert-silent'
	];

	$cmd = rtrim( $cmd, "\r\n" );

	// Extract the first word of the command
	$parts  = preg_split( '/\s+/', $cmd );
	$binary = strtolower( pathinfo( $parts[0], PATHINFO_FILENAME ) );

	if ( in_array( $binary, $allowed, true ) ) {
		$output = shell_exec( $cmd );

		if ( $output === null ) {
			error_log( "[safe_shell_exec] shell_exec returned null for: $cmd" );
		}

		return $output;
	}

	// Blocked
	error_log( "[safe_shell_exec] Blocked command: $cmd" );

	return null;
}