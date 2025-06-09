<?php
/**
 * apache_inspector.php
 * Cross-platform Apache environment inspector (Linux, macOS, Windows)
 *
 * Outputs:
 * - Operating System and Architecture
 * - Apache binary path
 * - Apache version
 * - Main config path
 * - Included config files
 * - Active Virtual Hosts
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

// SYSTEM INFO
$os   = PHP_OS_FAMILY;
$arch = ( PHP_INT_SIZE === 8 ) ? '64-bit' : '32-bit';
echo "<pre>";
echo "🖥️ Operating System: $os ($arch)\n";

// Utility: shell command executor with fallbacks
function tryShell( $cmd ) {
	if ( function_exists( 'shell_exec' ) && ! in_array( 'shell_exec', explode( ',', ini_get( 'disable_functions' ) ?? '' ) ) ) {
		return safe_shell_exec( "$cmd 2>&1" );
	}
	if ( function_exists( 'proc_open' ) && ! in_array( 'proc_open', explode( ',', ini_get( 'disable_functions' ) ?? '' ) ) ) {
		$descriptorspec = [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
		$process        = safe_shell_exec( $cmd, $descriptorspec, $pipes );
		if ( is_resource( $process ) ) {
			$output = stream_get_contents( $pipes[1] );
			fclose( $pipes[1] );
			proc_close( $process );

			return $output;
		}
	}

	return null;
}

// Check for Apache environment
function isApache() {
	return (
		strpos( $_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache' ) !== false ||
		php_sapi_name() === 'apache2handler' ||
		function_exists( 'apache_get_version' )
	);
}

// Apache version
function getApacheVersion() {
	if ( function_exists( 'apache_get_version' ) ) {
		return "via apache_get_version: " . apache_get_version();
	}
	if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
		return "via SERVER_SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'];
	}
	ob_start();
	phpinfo( INFO_MODULES );
	$data = ob_get_clean();
	if ( preg_match( '/Apache\/[\d.]+/', $data, $m ) ) {
		return "via phpinfo: " . $m[0];
	}

	return "not detected";
}

// Apache binary detection across platforms
function detectApacheBinary() {
	$paths = [
		// Linux
		'/usr/sbin/apache2',
		'/usr/sbin/httpd',
		'/usr/local/apache2/bin/httpd',
		'/opt/lampp/bin/httpd',
		'/usr/libexec/apache2/httpd',

		// macOS (Homebrew)
		'/opt/homebrew/bin/httpd',
		'/opt/homebrew/sbin/httpd',
		'/opt/homebrew/opt/httpd/bin/httpd',

		// Windows
		'C:\\xampp\\apache\\bin\\httpd.exe',
		'C:\\Program Files\\Apache Group\\Apache2\\bin\\httpd.exe',
		'C:\\Apache24\\bin\\httpd.exe'
	];

	foreach ( $paths as $p ) {
		if ( file_exists( $p ) && is_executable( $p ) ) {
			return $p;
		}
	}

	// Fallback via shell
	foreach ( [ 'which apache2', 'which httpd', 'where httpd' ] as $cmd ) {
		$out = tryShell( $cmd );
		if ( $out && file_exists( trim( $out ) ) ) {
			return trim( $out );
		}
	}

	return null;
}

// Apache config path from -V output
function getApacheConfigPath( $binary ) {
	$out = tryShell( "$binary -V" );
	if ( preg_match( '/SERVER_CONFIG_FILE="([^"]+)"/', $out, $conf ) ) {
		$file = $conf[1];
		if ( preg_match( '/HTTPD_ROOT="([^"]+)"/', $out, $root ) ) {
			return rtrim( $root[1], '/' ) . '/' . ltrim( $file, '/' );
		}

		return $file;
	}

	return null;
}

// Get Include lines from config
function getIncludes( $conf ) {
	if ( ! file_exists( $conf ) ) {
		return [];
	}
	$lines  = file( $conf );
	$result = [];
	foreach ( $lines as $line ) {
		if ( preg_match( '/^\s*Include(?:Optional)?\s+(.+)/i', $line, $m ) ) {
			$result[] = trim( $m[1] );
		}
	}

	return $result;
}

// Virtual hosts via apachectl/httpd -S
function getVirtualHosts( $binary ) {
	foreach ( [ $binary, 'apachectl', 'httpd' ] as $tool ) {
		$out = tryShell( "$tool -S" );
		if ( $out && stripos( $out, 'VirtualHost' ) !== false ) {
			return trim( $out );
		}
	}

	return null;
}

// ==== OUTPUT ====
echo "🧠 Apache Context Detected: " . ( isApache() ? 'Yes' : 'No' ) . "\n";

$version = getApacheVersion();
echo "📦 Apache Version: $version\n";

$binary = detectApacheBinary();
echo "🧾 Apache Binary: " . ( $binary ?: "Not found" ) . "\n";

if ( $binary ) {
	$config = getApacheConfigPath( $binary );
	echo "📝 Config File: " . ( $config ?: "Not detected" ) . "\n";

	if ( $config && file_exists( $config ) ) {
		$includes = getIncludes( $config );
		if ( $includes ) {
			echo "📂 Included Config Files:\n";
			foreach ( $includes as $inc ) {
				echo "  - $inc\n";
			}
		} else {
			echo "ℹ️ No Include directives found.\n";
		}
	}

	$vhosts = getVirtualHosts( $binary );
	if ( $vhosts ) {
		echo "\n🌐 Active Virtual Hosts:\n$vhosts\n";
	} else {
		echo "❌ VirtualHost information not available (likely restricted).\n";
	}
} else {
	echo "❌ Apache Binary Not Found. Config/VHosts skipped.\n";
}
echo "</pre>";
