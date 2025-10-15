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
 * @version 1.1
 */

define( 'CRYPTO_KEY_FILE', __DIR__ . '/../../.key' );

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
 * Returns a CSRF token for the current session, creating one if needed.
 *
 * @return string
 */
function csrf_get_token(): string {
	if ( session_status() !== PHP_SESSION_ACTIVE ) {
		// If headers already sent, don't try to start; rely on early bootstrap
		if ( headers_sent() ) {
			// No active session; return empty so the form won’t validate (safer fail)
			error_log( '[csrf_get_token] Headers already sent; session not active.' );

			return '';
		}
		session_start();
	}

	if ( empty( $_SESSION['csrf_token'] ) || ! is_string( $_SESSION['csrf_token'] ) ) {
		$_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
	}

	return $_SESSION['csrf_token'];
}

/**
 * Verifies a CSRF token from user input against the session token.
 * If valid, rotates the token to prevent replay.
 *
 * @param string|null $token
 *
 * @return bool
 */
function csrf_verify( ?string $token ): bool {
	if ( session_status() !== PHP_SESSION_ACTIVE ) {
		session_start();
	}

	$valid = (
		is_string( $token )
		&& isset( $_SESSION['csrf_token'] )
		&& is_string( $_SESSION['csrf_token'] )
		&& hash_equals( $_SESSION['csrf_token'], $token )
	);

	if ( $valid ) {
		// rotate token to make it single-use
		$_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
	}

	return $valid;
}

/**
 * Returns true if the HTTP request appears to originate from the same origin.
 *
 * @return bool
 */
function request_is_same_origin(): bool {
	$scheme = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https' : 'http';
	$host   = $_SERVER['HTTP_HOST'] ?? '';
	if ( $host === '' ) {
		return false;
	}
	$origin   = $_SERVER['HTTP_ORIGIN'] ?? null;
	$referrer = $_SERVER['HTTP_REFERER'] ?? null;
	$allowed  = $scheme . '://' . $host;

	foreach ( [ $origin, $referrer ] as $h ) {
		if ( $h === null ) {
			continue;
		}
		// compare prefix only (exact scheme+host), normalised to lowercase
		if ( stripos( $h, $allowed ) !== 0 ) {
			return false;
		}
	}

	return true;
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
		'where',
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

/**
 * Returns a PHP define() statement with an encrypted and escaped value.
 *
 * The value is encrypted using encryptValue() and properly escaped for safe
 * inclusion in generated PHP config files.
 *
 * @param string $name The constant name
 * @param string $value The raw value to encrypt and define
 *
 * @return string PHP define() code snippet
 */
function defineEncrypted( string $name, string $value ): string {
	return "define('$name', '" . addslashes( encryptValue( $value ) ) . "');\n";
}

/**
 * Obfuscates a sensitive configuration value when demo mode is active.
 *
 * @param string $value The sensitive value to obfuscate.
 *
 * @return string The obfuscated value if demo mode is enabled, or the original value otherwise.
 */
function obfuscate_value( string $value ): string {
	if ( defined( 'DEMO_MODE' ) && DEMO_MODE ) {
		return str_repeat( '*', strlen( $value ) );
	}

	return $value;
}

/**
 * Merge a parsed <VirtualHost> block into the $serverData map.
 *
 * @param array<string, array<string, mixed>> $serverData
 * @param array<string, mixed>|null $block
 *
 * @return void
 */
function collectServerBlock( array &$serverData, ?array $block ): void {
	if ( ! is_array( $block ) || empty( $block['name'] ) ) {
		return;
	}
	$name = $block['name'];
	if ( isset( $serverData[ $name ] ) ) {
		$serverData[ $name ]['_duplicate'] = true;
		$block['_duplicate']               = true;
	}
	$serverData[ $name ] = array_merge( [
		'valid'     => false,
		'cert'      => '',
		'key'       => '',
		'certValid' => true,
		'docRoot'   => '',
	], $block );
}
