<?php
// config/security.php
define( 'CRYPTO_KEY_FILE', __DIR__ . '/../.key' );

function getEncryptionKey() {
	if ( ! file_exists( CRYPTO_KEY_FILE ) ) {
		$key = bin2hex( openssl_random_pseudo_bytes( 16 ) );
		file_put_contents( CRYPTO_KEY_FILE, $key );
	}

	return trim( file_get_contents( CRYPTO_KEY_FILE ) );
}

function encryptValue( $plaintext ) {
	$key       = getEncryptionKey();
	$iv        = openssl_random_pseudo_bytes( 16 );
	$encrypted = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

	return base64_encode( $iv . $encrypted );
}

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

// Safe shell execution wrapper
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
		'open',
		'xdg-open',
		'cmd',
		'powershell',
		'auto-make-cert',
		'make-cert',
		'make-cert-silent'
	];

	$cmd = rtrim( $cmd, "\r\n" );

	// Extract the first part of the command
	preg_match( '/(?:^|["\'])([a-zA-Z]:)?[^\\s"\']+/', $cmd, $matches );
	$fullPath = $matches[0] ?? '';

	// Normalise: get filename without extension
	$binary = strtolower( pathinfo( $fullPath, PATHINFO_FILENAME ) );

	if ( in_array( $binary, $allowed, true ) ) {
		return shell_exec( $cmd );
	}

	// Optional: debug logging
	error_log( "[safe_shell_exec] Blocked command: $cmd" );

	return null;
}