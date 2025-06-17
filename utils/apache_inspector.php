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
 * - Apache environment variables
 * - PHP config info
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

// Fast mode flag (can be set via ?fast=1 or ?fast=true)
$fastMode = isset( $_GET['fast'] ) && filter_var( $_GET['fast'], FILTER_VALIDATE_BOOLEAN );

// SYSTEM INFO
$os   = PHP_OS_FAMILY;
$arch = ( PHP_INT_SIZE === 8 ) ? '64-bit' : '32-bit';
echo '<h2>Apache Inspector</h2>';
echo "<pre>";
echo "🖥️ Operating System: $os ($arch)\n";
echo "🚀 Fast Mode: " . ( $fastMode ? 'Enabled (some checks skipped)' : 'Disabled (full inspection)' ) . "\n";

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
		// User defined
		APACHE_PATH,

		// Linux
		'/usr/sbin/apache2',
		'/usr/sbin/httpd',
		'/usr/local/apache2/bin/httpd',
		'/opt/lampp/bin/httpd',
		'/usr/libexec/apache2/httpd',
		'/usr/local/sbin/httpd',
		'/snap/bin/httpd',

		// Linuxbrew
		'/home/linuxbrew/.linuxbrew/bin/httpd',
		'/home/linuxbrew/.linuxbrew/opt/httpd/bin/httpd',

		// macOS (Homebrew)
		'/opt/homebrew/bin/httpd',
		'/opt/homebrew/sbin/httpd',
		'/opt/homebrew/opt/httpd/bin/httpd',

		// macOS (MAMP)
		'/Applications/MAMP/Library/bin/httpd',

		// macOS (AMPPS)
		'/Applications/AMPPS/apache/bin/httpd',

		// Windows (XAMPP + AMPPS)
		'C:\\xampp\\apache\\bin\\httpd.exe',
		'C:\\Program Files\\Apache Group\\Apache2\\bin\\httpd.exe',
		'C:\\Apache24\\bin\\httpd.exe',
		'C:\\Program Files (x86)\\Ampps\\apache\\bin\\httpd.exe',
		'C:\\Program Files\\Ampps\\apache\\bin\\httpd.exe'
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

// Apache SAPI detection from phpinfo()
function detectApacheSAPI() {
	ob_start();
	phpinfo( INFO_MODULES );
	$data = ob_get_clean();
	if ( strpos( $data, 'apache2handler' ) !== false ) {
		return 'apache2handler (Apache SAPI)';
	}

	return null;
}

// Apache environment variables from getenv and /proc/self/environ
function getApacheEnvVars() {
	$envVars = [];
	foreach ( [ 'APACHE_RUN_DIR', 'APACHE_PID_FILE', 'APACHE_LOCK_DIR', 'INVOCATION_ID' ] as $var ) {
		$val = getenv( $var );
		if ( $val ) {
			$envVars[ $var ] = $val;
		}
	}
	if ( ! $GLOBALS['fastMode'] ) {
		$procPath = '/proc/self/environ';
		if ( file_exists( $procPath ) && is_readable( $procPath ) ) {
			$data  = file_get_contents( $procPath );
			$pairs = explode( "\0", $data );
			foreach ( $pairs as $pair ) {
				if ( stripos( $pair, 'apache' ) !== false && strpos( $pair, '=' ) !== false ) {
					list( $k, $v ) = explode( '=', $pair, 2 );
					$envVars[ $k ] = $v;
				}
			}
		}
	}

	return $envVars;
}

// PHP ini files
function getIniFilesInfo() {
	return [
		'Loaded php.ini'     => php_ini_loaded_file() ?: 'N/A',
		'Scanned .ini files' => php_ini_scanned_files() ?: 'N/A'
	];
}

// ==== OUTPUT ====
echo "🧠 Apache Context Detected: " . ( isApache() ? 'Yes' : 'No' ) . "\n";
echo "🗏️ Apache SAPI: " . ( detectApacheSAPI() ?? 'Unknown or not Apache' ) . "\n";

$version = getApacheVersion();
echo "📦 Apache Version: $version\n";

$binary = detectApacheBinary();
echo "🗒 Apache Binary: " . ( $binary ?: "Not found" ) . "\n";

if ( $binary && ! $fastMode ) {
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
} elseif ( $binary ) {
	echo "🔴 Fast mode: Config/VHosts skipped.\n";
} else {
	echo "❌ Apache Binary Not Found. Config/VHosts skipped.\n";
}

// Output Apache environment vars
echo "\n🌱 Apache Environment Variables:\n";
$envVars = getApacheEnvVars();
if ( $envVars ) {
	foreach ( $envVars as $k => $v ) {
		echo "  $k: $v\n";
	}
} else {
	echo "  None detected.\n";
}

// Output PHP .ini info
echo "\n⚙️ PHP Configuration:\n";
foreach ( getIniFilesInfo() as $k => $v ) {
	echo "  $k: $v\n";
}

echo "\n📅 Inspection complete.</pre>";
