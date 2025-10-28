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
 * @version 1.2
 */

ob_start();
error_reporting( E_ERROR | E_PARSE );
ini_set( 'display_errors', 0 );

require_once __DIR__ . '/../config/config.php';

header( 'Content-Type: application/json' );

$action     = $_POST['action'] ?? '';
$apachePath = defined( 'APACHE_PATH' ) ? rtrim( APACHE_PATH, '\\/' ) : '';
$os         = PHP_OS_FAMILY;

if ( ! in_array( $action, [ 'restart' ] ) ) {
	ob_end_clean();
	echo json_encode( [ 'success' => false, 'message' => 'Invalid action' ] );
	exit;
}

if ( defined( 'DEMO_MODE' ) && DEMO_MODE ) {
	http_response_code( 403 );
	ob_end_clean();
	echo json_encode( [
		'success' => false,
		'message' => 'Apache control is disabled in demo mode'
	] );
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
