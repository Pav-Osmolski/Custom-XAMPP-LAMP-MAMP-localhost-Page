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
 * @version 1.2
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
