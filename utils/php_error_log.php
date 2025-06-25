<?php
/**
 * PHP Error Log Viewer
 *
 * Displays the most recent PHP error log entries
 * either as raw text (for AJAX fetch) or HTML markup.
 *
 * @author Pav
 * @license MIT
 * @version 1.3
 */

require_once __DIR__ . '/../config/config.php';

$displayPhpErrorLog = $displayPhpErrorLog ?? true;
$useAjaxForStats    = $useAjaxForStats ?? true;

if ( ! $displayPhpErrorLog ) {
    header( 'Content-Type: application/json' );
    echo json_encode( [ 'error' => 'PHP error log display is disabled.' ] );
    exit;
}

$logFile = ini_get( 'error_log' );
if ( ! $logFile || ! file_exists( $logFile ) ) {
    $logContent = "PHP error log not found or not configured.";
} else {
    $logContent = tail_log( $logFile );
}

function tail_log( $file, $lines = 25 ) {
    $data = [];
    $fp = fopen( $file, 'r' );
    if ( $fp === false ) return $data;

    fseek( $fp, -1, SEEK_END );
    $pos = ftell( $fp );
    $line = '';

    while ( $pos > 0 && count( $data ) < $lines ) {
        $char = fgetc( $fp );
        if ( $char === "\n" ) {
            array_unshift( $data, $line );
            $line = '';
        } else {
            $line = $char . $line;
        }
        fseek( $fp, --$pos );
    }
    fclose( $fp );

    if ( $line ) array_unshift( $data, $line );
    return implode( "\n", array_filter( $data, fn($l) => trim($l) !== '' ) );
}

if ( $useAjaxForStats ) {
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    echo $logContent;
} else {
    echo "
        <h3 id='php-error-log-title'>
            <button id='toggle-php-error-log' aria-expanded='false' aria-controls='php-error-log'>
            📝 Toggle PHP Error Log
            </button>
        </h3>
        <pre id='php-error-log' aria-live='polite' tabindex='0'>
            <code>"
                 . htmlspecialchars( $logContent ) . "
            </code>
        </pre>";
}
?>