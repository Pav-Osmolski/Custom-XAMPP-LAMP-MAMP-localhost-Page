<?php
// Debug helper
function log_command( $command, $context = '' ) {
	$logDir  = __DIR__ . '/../logs';
	$logFile = $logDir . '/localhost-page.log';

	if ( ! is_dir( $logDir ) ) {
		mkdir( $logDir, 0755, true );
	}

	$timestamp  = date( '[Y-m-d H:i:s]' );
	$contextStr = $context ? " [$context]" : '';

	// Encode to preserve invisible characters (optional debug enhancement)
	$visibleCommand = json_encode( $command );

	$logEntry = "$timestamp$contextStr\nRaw: $command\nVisible: " . json_encode( $command ) . "\nLength: " . strlen( $command ) . "\n\n";

	file_put_contents( $logFile, $logEntry, FILE_APPEND );
}