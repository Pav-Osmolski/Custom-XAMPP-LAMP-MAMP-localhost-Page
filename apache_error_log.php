<?php
header( 'Content-Type: application/json' );
header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

include 'config.php';

$logFile = APACHE_PATH . 'logs\\error.log';
$lines   = 5;

if ( file_exists( $logFile ) ) {
    $logContent = file( $logFile );
    $logContent = array_slice( $logContent, - $lines );
    echo implode( "", $logContent );
} else {
    echo "Error log not found.";
}
?>