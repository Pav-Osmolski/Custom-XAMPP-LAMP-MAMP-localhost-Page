<?php
header( 'Content-Type: application/json' );
header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

require_once 'config.php';

// Determine the Apache log file path
if ( $isWindows ) {
    $logFile = APACHE_PATH . 'logs\\error.log';
} else {
    // Check the custom Apache path first if defined
    $logFile = defined( 'APACHE_PATH' ) ? APACHE_PATH . '/logs/error.log' : '';

    // If not found, check standard Linux locations
    if ( ! file_exists( $logFile ) || empty( $logFile ) ) {
        $logFile = '/var/log/apache2/error.log'; // Ubuntu/Debian default
    }
    if ( ! file_exists( $logFile ) ) {
        $logFile = '/var/log/httpd/error_log'; // Red Hat/CentOS/Fedora default
    }
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