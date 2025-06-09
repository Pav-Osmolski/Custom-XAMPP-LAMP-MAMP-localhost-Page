<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

header( 'Content-Type: text/plain' );

if ( empty( $_GET['name'] ) ) {
	http_response_code( 400 );
	exit( 'Missing domain name.' );
}

$domain = preg_replace( '/[^a-zA-Z0-9.-]/', '', $_GET['name'] );

$os     = PHP_OS_FAMILY;
$crtDir = APACHE_PATH . DIRECTORY_SEPARATOR . 'crt';

if ( $os === 'Windows' ) {
	$ps1Path = $crtDir . DIRECTORY_SEPARATOR . 'make-cert-silent.ps1';
	$batPath = $crtDir . DIRECTORY_SEPARATOR . 'make-cert-silent.bat';

	if ( file_exists( $ps1Path ) ) {
		$command = 'powershell -ExecutionPolicy Bypass -File "' . $ps1Path . '" "' . $domain . '"';
	} elseif ( file_exists( $batPath ) ) {
		$command = 'cmd /c "' . $batPath . ' ' . $domain . '"';
	} else {
		http_response_code( 500 );
		exit( "Cannot find PowerShell or BAT script in:\n$crtDir" );
	}
} else {
	$shPath = $crtDir . DIRECTORY_SEPARATOR . 'make-cert-silent.sh';
	if ( file_exists( $shPath ) ) {
		$command = 'bash "' . $shPath . '" "' . $domain . '"';
	} else {
		http_response_code( 500 );
		exit( "Cannot find shell script in:\n$crtDir" );
	}
}

$output = safe_shell_exec( $command );

echo $output ?: 'Failed to generate certificate.';
