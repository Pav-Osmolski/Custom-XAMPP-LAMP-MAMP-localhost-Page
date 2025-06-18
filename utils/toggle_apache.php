<?php
/**
 * Apache Restart Handler
 *
 * This script accepts a POST request with an `action` key (currently only `restart`)
 * and attempts to restart the Apache web server depending on the OS and available tools.
 *
 * Supported Environments:
 * - Windows (XAMPP, AMPPS, manual installations)
 * - macOS (MAMP, brew, system apachectl)
 * - Linux (apache2ctl, apachectl, systemd)
 *
 * Output:
 * - JSON object with success status, message, and any command output.
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

ob_start();
error_reporting( E_ERROR | E_PARSE );
ini_set( 'display_errors', 0 );

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

header( 'Content-Type: application/json' );

$action     = $_POST['action'] ?? '';
$apachePath = defined( 'APACHE_PATH' ) ? rtrim( APACHE_PATH, '\\/' ) : '';
$os         = PHP_OS_FAMILY;
function runCommand( $cmd ) {
	exec( $cmd . ' 2>&1', $output, $return_var );

	return [ 'output' => implode( "\n", $output ), 'success' => $return_var === 0 ];
}
function findDefaultCommand( $action, $os, $apachePath ) {
	switch ( $os ) {
		case 'Windows':
		{
			if ( $apachePath ) {
				$httpdPath = $apachePath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'httpd.exe';

				// Check if XAMPP httpd.exe is running
				$isHttpdRunning = false;
				$taskList       = safe_shell_exec( 'tasklist /FI "IMAGENAME eq httpd.exe"' );
				if ( strpos( $taskList, 'httpd.exe' ) !== false ) {
					$isHttpdRunning = true;
				}

				if ( $isHttpdRunning && file_exists( $httpdPath ) ) {
					return "start /B \"ApacheRestart\" \"$httpdPath\" -k restart";
				}

				// Fallback: check if Apache service is running
				$serviceStatus = safe_shell_exec( 'sc query Apache2.4' );
				if ( strpos( $serviceStatus, 'RUNNING' ) !== false ) {
					return "net stop Apache2.4 && net start Apache2.4";
				}

				// Optional: fallback to batch files
				$stopBat  = $apachePath . DIRECTORY_SEPARATOR . 'apache_stop.bat';
				$startBat = $apachePath . DIRECTORY_SEPARATOR . 'apache_start.bat';
				if ( file_exists( $stopBat ) && file_exists( $startBat ) ) {
					return "\"$stopBat\" && \"$startBat\"";
				}
			}

			// No known Apache instance running
			return '';
		}

		case 'Darwin':
		{
			// 1. Attempt to detect running Apache binary
			$apacheBinary = trim( safe_shell_exec( "ps -eo comm,args | grep -E 'httpd|apache2' | grep -v grep | awk '{print $1}' | head -n 1" ) );
			if ( ! empty( $apacheBinary ) && file_exists( $apacheBinary ) ) {
				return "sudo $apacheBinary -k restart";
			}

			// 2. MAMP-specific apachectl
			if ( file_exists( '/Applications/MAMP/Library/bin/apachectl' ) ) {
				return 'sudo /Applications/MAMP/Library/bin/apachectl restart';
			}

			// 3. Fallback to standard apachectl
			if ( file_exists( '/usr/sbin/apachectl' ) ) {
				return 'sudo /usr/sbin/apachectl restart';
			}

			return 'sudo apachectl restart'; // final fallback
		}

		case 'Linux':
		{
			// 1. Detect currently running Apache binary
			$apacheBinary = trim( safe_shell_exec( "ps -eo comm,args | grep -E 'httpd|apache2' | grep -v grep | awk '{print $1}' | head -n 1" ) );
			if ( ! empty( $apacheBinary ) && file_exists( $apacheBinary ) ) {
				return "sudo $apacheBinary -k restart";
			}

			// 2. Common Apache restart tools
			if ( file_exists( '/usr/sbin/apache2ctl' ) ) {
				return 'sudo /usr/sbin/apache2ctl restart';
			}

			if ( file_exists( '/usr/sbin/apachectl' ) ) {
				return 'sudo /usr/sbin/apachectl restart';
			}

			// 3. Fallback to systemd
			return 'sudo systemctl restart apache2';
		}

		default:
			return '';
	}
}

if ( ! in_array( $action, [ 'restart' ] ) ) {
	ob_end_clean();
	echo json_encode( [ 'success' => false, 'message' => 'Invalid action' ] );
	exit;
}

$cmd = findDefaultCommand( $action, $os, $apachePath );
if ( ! $cmd ) {
	ob_end_clean();
	echo json_encode( [ 'success' => false, 'message' => 'Unable to determine command' ] );
	exit;
}

$result = runCommand( $cmd );
ob_end_clean();
echo json_encode( [
	'success' => $result['success'],
	'message' => $result['success'] ? "Apache $action command executed successfully." : "Failed to $action Apache.",
	'output'  => $result['output']
] );
