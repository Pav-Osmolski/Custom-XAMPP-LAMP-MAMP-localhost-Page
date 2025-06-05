<?php
require_once 'config.php';

// Set safe defaults
$displayApacheErrorLog = $displayApacheErrorLog ?? false;
$useAjaxForStats       = $useAjaxForStats ?? true;

if ( ! $displayApacheErrorLog ) {
    header( 'Content-Type: application/json' );
    echo json_encode( [ 'error' => 'Apache error log display is disabled.' ] );
    exit;
}

$logFile = '';

switch ( PHP_OS_FAMILY ) {
    case 'Windows':
        $logFile = APACHE_PATH . '\\logs\\error.log';
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

$lines      = 5;
$logContent = '';

if ( file_exists( $logFile ) ) {
    $linesArray = file( $logFile );
    $logContent = implode( "", array_slice( $linesArray, - $lines ) );
} else {
    $logContent = "Error log not found.";
}

if ( $useAjaxForStats ) {
    header( 'Content-Type: application/json' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    echo $logContent;
} else {
    echo "
        <h3 id='error-log-title'>
            <button id='toggle-error-log' aria-expanded='false' aria-controls='error-log'>
            📝 Toggle Apache Error Log
            </button>
        </h3>
        <pre id='error-log' aria-live='polite' tabindex='0' style='display: none;'><code>" . htmlspecialchars( $logContent ) . "</code></pre>";
}
?>