<?php
/**
 * apache_inspector.php
 * Cross-platform Apache environment inspector (Linux, macOS, Windows)
 *
 * Outputs:
 * - Operating System and Architecture
 * - Apache binary path
 * - Apache version
 * - Apache uptime
 * - Main config path
 * - Included config files
 * - Active Virtual Hosts
 * - Apache environment variables
 * - PHP config info
 *
 * @author Pav
 * @license MIT
 * @version 1.1
 */

require_once __DIR__ . '/../config/config.php';

// Default to config value; override with ?fast=1 or ?fast=0 if provided
$fastMode = $apacheFastMode ?? false;

if ( isset( $_GET['fast'] ) ) {
	$fastMode = filter_var( $_GET['fast'], FILTER_VALIDATE_BOOLEAN );
}

// SYSTEM INFO
$os   = PHP_OS_FAMILY;
$arch = ( PHP_INT_SIZE === 8 ) ? '64-bit' : '32-bit';
echo '<h2>Apache Inspector</h2>';
echo "<pre>";
echo "🖥️ Operating System: $os ($arch)\n";
echo "🚀 Fast Mode: " . ( $fastMode ? 'Enabled (some checks skipped)' : 'Disabled (full inspection)' ) . "\n";

/**
 * Execute a shell command with fallbacks.
 *
 * @param string $cmd
 *
 * @return string|null
 */
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

/**
 * Check if the current PHP environment is running under Apache.
 *
 * @return bool
 */
function isApache() {
	return (
		strpos( $_SERVER['SERVER_SOFTWARE'] ?? '', 'Apache' ) !== false ||
		php_sapi_name() === 'apache2handler' ||
		function_exists( 'apache_get_version' )
	);
}

/**
 * Retrieve the Apache version from various sources.
 *
 * @return string
 */
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

/**
 * Attempt to detect the Apache binary path.
 *
 * @return string|null
 */
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

/**
 * Extract the Apache config file path from the `-V` output.
 *
 * @param string $binary
 *
 * @return string|null
 */
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

/**
 * Parse Include/IncludeOptional directives from a config file.
 *
 * @param string $conf
 *
 * @return array<int, string>
 */
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

/**
 * Estimate Apache uptime cross-platform.
 *
 * @param string $os
 *
 * @return string
 */
function getApacheUptimeEstimate( string $os ): string {
	if ( $os === 'Windows' ) {
		$output = tryShell( 'wmic process where "name=\'httpd.exe\'" get CreationDate /value' );
		if ( preg_match( '/CreationDate=(\d{14})/', $output, $match ) ) {
			$start = \DateTime::createFromFormat( 'YmdHis', substr( $match[1], 0, 14 ) );
			if ( $start ) {
				$diff = ( new \DateTime() )->getTimestamp() - $start->getTimestamp();

				return formatDuration( $diff );
			}
		}
	} else {
		$output = tryShell( 'ps -eo etimes,comm | grep -E "apache|httpd" | head -n1' );
		if ( preg_match( '/^\s*(\d+)/', $output, $match ) ) {
			return formatDuration( (int) $match[1] );
		}
	}

	return 'Unavailable';
}

/**
 * Format a duration in seconds into a human-readable H:M:S string.
 *
 * @param int $seconds
 *
 * @return string
 */
function formatDuration( int $seconds ): string {
	$hours     = floor( $seconds / 3600 );
	$minutes   = floor( ( $seconds % 3600 ) / 60 );
	$remaining = $seconds % 60;

	return "{$seconds} seconds ({$hours}h {$minutes}m {$remaining}s)";
}

/**
 * Get the output of `-S` for virtual host listing, if available.
 *
 * @param string $binary
 *
 * @return string|null
 */
function getVirtualHosts( $binary ) {
	foreach ( [ $binary, 'apachectl', 'httpd' ] as $tool ) {
		$out = tryShell( "$tool -S" );
		if ( $out && stripos( $out, 'VirtualHost' ) !== false ) {
			return trim( $out );
		}
	}

	return null;
}

/**
 * Detect if Apache SAPI is in use via phpinfo() output.
 *
 * @return string|null
 */
function detectApacheSAPI() {
	ob_start();
	phpinfo( INFO_MODULES );
	$data = ob_get_clean();
	if ( strpos( $data, 'apache2handler' ) !== false ) {
		return 'apache2handler (Apache SAPI)';
	}

	return null;
}

/**
 * Get Apache-related environment variables from getenv and optionally /proc/self/environ.
 *
 * @return array<string, string>
 */
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

/**
 * Get loaded PHP .ini file info.
 *
 * @return array<string, string>
 */
function getIniFilesInfo() {
	return [
		'Loaded php.ini'     => php_ini_loaded_file() ?: 'N/A',
		'Scanned .ini files' => php_ini_scanned_files() ?: 'N/A'
	];
}

// ==== OUTPUT ====
echo "🧠 Apache Context Detected: " . ( isApache() ? 'Yes' : 'No' ) . "\n";
echo "📃 Apache SAPI: " . ( detectApacheSAPI() ?? 'Unknown or not Apache' ) . "\n";

$version = getApacheVersion();
echo "📦 Apache Version: $version\n";

$binary = detectApacheBinary();
echo "📓 Apache Binary: " . ( $binary ?: "Not found" ) . "\n";

if ( ! $fastMode ) {
	$uptime = getApacheUptimeEstimate( $os );
	echo "🕒 Apache Uptime (estimated): " . ( $uptime !== 'Unavailable' ? $uptime : 'Not available on this platform or config' ) . "\n";
}

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
