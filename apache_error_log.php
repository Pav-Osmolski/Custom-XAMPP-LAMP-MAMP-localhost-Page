<?php
header( 'Content-Type: application/json' );
header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

require_once 'config.php';

$logFile = '';

switch ( PHP_OS_FAMILY ) {
	case 'Windows':
		$logFile = APACHE_PATH . 'logs\error.log';
		break;

	case 'Darwin':
		$mampLog = '/Applications/MAMP/logs/apache_error.log';
		if ( file_exists( $mampLog ) ) {
			$logFile = $mampLog;
			break;
		}
		$logFile = '/usr/local/var/log/httpd/error_log';
		break;

	default:
		$logFile = '/var/log/apache2/error.log';
		if ( ! file_exists( $logFile ) ) {
			$logFile = '/var/log/httpd/error_log';
		}
		break;
}

$lines = 5;

if ( file_exists( $logFile ) ) {
	$logContent = file( $logFile );
	$logContent = array_slice( $logContent, - $lines );
	echo implode( "", $logContent );
} else {
	echo "Error log not found.";
}
?>
