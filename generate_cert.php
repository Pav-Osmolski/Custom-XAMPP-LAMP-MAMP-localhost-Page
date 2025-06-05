<?php
require_once __DIR__ . '/config.php';
header( 'Content-Type: text/plain' );

if ( empty( $_GET['name'] ) ) {
    http_response_code( 400 );
    exit( 'Missing domain name.' );
}

$domain = preg_replace( '/[^a-zA-Z0-9.-]/', '', $_GET['name'] );

// Clean path
$batPath = APACHE_PATH . DIRECTORY_SEPARATOR . 'crt' . DIRECTORY_SEPARATOR . 'pav-make-cert.bat';

if ( ! file_exists( $batPath ) ) {
    http_response_code( 500 );
    exit( "Cannot find cert generation script at:\n$batPath" );
}

// Quote and execute
$command = '"' . $batPath . '" "' . $domain . '"';
$output  = safe_shell_exec( $command );
echo $output ?: 'Failed to generate certificate.';