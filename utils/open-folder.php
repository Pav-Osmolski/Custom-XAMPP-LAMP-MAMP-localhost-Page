<?php
header( 'Content-Type: application/json' );

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	http_response_code( 405 );
	echo json_encode( [ 'error' => 'Invalid request method.' ] );
	exit;
}

$data = json_decode( file_get_contents( 'php://input' ), true );

if ( ! isset( $data['path'] ) || ! is_string( $data['path'] ) || ! file_exists( $data['path'] ) ) {
	http_response_code( 400 );
	echo json_encode( [ 'error' => 'Invalid or missing folder path.' ] );
	exit;
}

$rawPath = $data['path'];
$path    = str_replace( '/', DIRECTORY_SEPARATOR, $rawPath );

$os = PHP_OS_FAMILY;

try {
	if ( $os === 'Windows' ) {
		pclose( popen( "start \"\" $path", 'r' ) );
	} elseif ( $os === 'Darwin' ) {
		shell_exec( "open $path" );
	} elseif ( $os === 'Linux' ) {
		shell_exec( "xdg-open $path" );
	} else {
		throw new Exception( "Unsupported OS: $os" );
	}

	echo json_encode( [ 'success' => true, 'message' => "Opened: {$data['path']}" ] );
} catch ( Exception $e ) {
	http_response_code( 500 );
	echo json_encode( [ 'error' => $e->getMessage() ] );
}
