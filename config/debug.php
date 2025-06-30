<?php
/**
 * Logs a command string to a debug log file for diagnostic purposes.
 *
 * This function writes a timestamped entry to `logs/localhost-page.log`,
 * containing the raw command, a JSON-encoded version to reveal invisible characters,
 * the command length, and optional context information.
 *
 * If the logs directory does not exist, it is created automatically.
 *
 * @param string $command The raw command string to be logged.
 * @param string $context Optional context label for grouping or identifying the log entry (e.g. 'cert-gen', 'apache-restart').
 *
 * @return void
 */
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