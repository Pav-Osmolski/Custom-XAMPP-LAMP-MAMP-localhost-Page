<?php
/**
 * mysql_inspector.php
 * Cross-platform MySQL environment inspector (via mysqli)
 *
 * Outputs:
 * - MySQL server version
 * - Connection details
 * - Client library info
 * - Active databases and size (unless fast mode)
 * - Status and configuration variables
 * - Process list
 *
 * Optional: ?fast=1 to skip size aggregation
 *
 * @author Pav
 * @license MIT
 * @version 1.1
 */

/** @var string $dbUser */
/** @var string $dbPass */

require_once __DIR__ . '/../config/config.php';

// Default to config value; override with ?fast=1 or ?fast=0 if provided
$fastMode = $mysqlFastMode ?? false;

if ( isset( $_GET['fast'] ) ) {
	$fastMode = filter_var( $_GET['fast'], FILTER_VALIDATE_BOOLEAN );
}

$start = microtime( true );

echo '<h2>MySQL Inspector</h2>';
echo '<pre>';

mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

try {
	$mysqli = new mysqli( DB_HOST, $dbUser, $dbPass );
	$mysqli->set_charset( 'utf8mb4' );
} catch ( mysqli_sql_exception $e ) {
	exit( "❌ MySQL connection failed:\n" . htmlspecialchars( $e->getMessage() ) . "\n" );
}

echo "✅ Connected to MySQL server\n";
echo "🚀 Fast Mode: " . ( $fastMode ? 'Enabled (some checks skipped)' : 'Disabled (full inspection)' ) . "\n";
echo "🔢 Server Version: " . htmlspecialchars( $mysqli->server_info ) . "\n";
echo "📚 Client Version: " . htmlspecialchars( mysqli_get_client_info() ) . "\n";
echo "🔌 Host Info: " . htmlspecialchars( obfuscate_value( $mysqli->host_info ) ) . "\n";

$userResult = $mysqli->query( "SELECT USER()" );
$user       = $userResult ? $userResult->fetch_row()[0] : 'Unknown';
echo "🔐 Current User: " . htmlspecialchars( obfuscate_value( $user ) ) . "\n";

$result = $mysqli->query( "SHOW DATABASES" );
if ( ! $result ) {
	echo "⚠️ Failed to list databases.\n";
} else {
	$databases = [];
	while ( $row = $result->fetch_row() ) {
		$db = $row[0];
		if ( in_array( $db, [ 'information_schema', 'performance_schema', 'mysql', 'sys' ] ) ) {
			continue;
		}
		$databases[] = $db;
	}

	if ( $fastMode ) {
		echo "\n📁 Databases:";
		foreach ( $databases as $db ) {
			echo "\n- " . obfuscate_value( htmlspecialchars( $db ) );
		}
	} else {
		echo "\n📦 Databases with Approximate Sizes:";
		foreach ( $databases as $db ) {
			$sizeResult = $mysqli->query( "SHOW TABLE STATUS FROM `$db`" );
			$totalSize  = 0;
			while ( $sizeRow = $sizeResult->fetch_assoc() ) {
				$totalSize += $sizeRow['Data_length'] + $sizeRow['Index_length'];
			}
			$mb = round( $totalSize / 1024 / 1024, 2 );
			echo "\n- " . obfuscate_value( htmlspecialchars( $db ) ) . ": {$mb} MB";
		}
	}
}

echo "\n\n⚙️ Configuration Variables (Partial):\n";
$variables = $mysqli->query( "SHOW VARIABLES LIKE 'version%'" );
while ( $row = $variables->fetch_assoc() ) {
	echo htmlspecialchars( $row['Variable_name'] ) . ": " . htmlspecialchars( $row['Value'] ) . "\n";
}

echo "\n📊 Status Snapshot:\n";
$status = $mysqli->query( "SHOW STATUS LIKE 'Uptime'" );
while ( $row = $status->fetch_assoc() ) {
	$seconds   = (int) $row['Value'];
	$hours     = floor( $seconds / 3600 );
	$minutes   = floor( ( $seconds % 3600 ) / 60 );
	$remaining = $seconds % 60;

	echo htmlspecialchars( $row['Variable_name'] ) . ": {$seconds} seconds ({$hours}h {$minutes}m {$remaining}s)\n";
}

echo "\n📋 Current Processes:\n";
$processList = $mysqli->query( "SHOW FULL PROCESSLIST" );
while ( $row = $processList->fetch_assoc() ) {
	echo "- [" . htmlspecialchars( (string) ( $row['Id'] ?? '' ) ) . "] "
	     . htmlspecialchars( (string) ( obfuscate_value( $row['User'] ) ?? '' ) ) . "@"
	     . htmlspecialchars( (string) ( obfuscate_value( $row['Host'] ) ?? '' ) ) . ": "
	     . htmlspecialchars( (string) ( $row['Info'] ?? '' ) ) . "\n";
}

$mysqli->close();

$elapsed = round( microtime( true ) - $start, 2 );
echo "\n⏱️ Completed in {$elapsed} seconds\n";
echo "</pre>";
?>
