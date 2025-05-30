<?php
// config.php
if ( file_exists( __DIR__ . '/user_config.php' ) ) {
	include __DIR__ . '/user_config.php';
}

if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', 'localhost' );
}
if ( ! defined( 'DB_USER' ) ) {
	define( 'DB_USER', 'user' );
}
if ( ! defined( 'DB_PASSWORD' ) ) {
	define( 'DB_PASSWORD', 'password' );
}
if ( ! defined( 'APACHE_PATH' ) ) {
	define( 'APACHE_PATH', 'C:\\xampp\\apache\\' );
}
if ( ! defined( 'HTDOCS_PATH' ) ) {
	define( 'HTDOCS_PATH', 'C:\\htdocs\\' );
} // Where your local projects are stored
if ( ! defined( 'PHP_PATH' ) ) {
	define( 'PHP_PATH', 'C:\\xampp\\php\\' );
}

if ( ! isset( $displaySystemStats ) ) {
	$displaySystemStats = true;
}
if ( ! isset( $displayApacheErrorLog ) ) {
	$displayApacheErrorLog = true;
}

// Toggle AJAX loading for system stats and error log
if ( ! isset( $useAjaxForStats ) ) {
	$useAjaxForStats = true;
}

// Detect OS for older PHP versions. Not actually used because of $os, but kept here for reference.
$isWindowsPHPOld = strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
$isLinuxPHPOld = strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX';
$isMacPHPOld = strtoupper(substr(PHP_OS, 0, 6)) === 'DARWIN' || strtoupper(substr(PHP_OS, 0, 3)) === 'MAC';

// Get username based on OS
$user = $_SERVER['USERNAME']
        ?? $_SERVER['USER']
           ?? trim( shell_exec( 'whoami' ) )
              ?? get_current_user();

// Remove the computer name if present (Windows)
if ( str_contains( $user, '\\' ) ) {
	$user = explode( '\\', $user )[1];
}

$user = $user ?: 'Guest';

$bodyClasses = 'background-image';

if ( file_exists( 'system_stats.php' ) && $displaySystemStats ) {
	$bodyClasses .= ' system-monitor-active';
}

if ( file_exists( 'apache_error_log.php' ) && $displayApacheErrorLog ) {
	$bodyClasses .= ' error-log-section-active';
}

function renderServerInfo() {
	$os = PHP_OS_FAMILY;

	$apacheVersion = '';
	if ( $os === 'Windows' ) {
		$httpdPath = APACHE_PATH . 'bin\\httpd.exe';
		if ( file_exists( $httpdPath ) ) {
			$apacheVersion = shell_exec( "\"$httpdPath\" -v" );
		}
	} elseif ( $os === 'Darwin' ) {
		$mampPath = '/Applications/MAMP/Library/bin/httpd';
		$brewPath = trim( shell_exec( 'which httpd' ) );

		if ( file_exists( $mampPath ) ) {
			$apacheVersion = shell_exec( "\"$mampPath\" -v" );
		} elseif ( ! empty( $brewPath ) ) {
			$apacheVersion = shell_exec( "$brewPath -v" );
		}
	} else {
		$apacheVersion = shell_exec( 'apachectl -v 2>/dev/null' ) ?: shell_exec( 'httpd -v 2>/dev/null' );
	}

	// Attempt to extract the versions
	if ( $apacheVersion && preg_match( '/Server version: Apache\/([\d.]+)/', $apacheVersion, $matches ) ) {
		echo 'Apache: ' . $matches[1] . ' ✔️<br>';
	} else {
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false ) {
			echo 'Apache: Version unknown ⚠️<br>';
		} else {
			echo 'Apache: Not detected ❌<br>';
		}
	}

	// Check if PHP version is detected
	$phpVersion = phpversion();
	if ( $phpVersion === false ) {
		echo 'PHP: Version unknown ⚠️<br>';
	} else {
		$isThreadSafe = ( ZEND_THREAD_SAFE ) ? "TS" : "NTS";
		$isFastCGI    = ( strpos( PHP_SAPI, 'cgi-fcgi' ) !== false ) ? "FastCGI" : "Non-FastCGI";
		echo 'PHP: <a href="#" id="toggle-phpinfo">' . $phpVersion . " $isThreadSafe $isFastCGI</a> ✔️<br>";
	}

	// Check MySQL version
	try {
		$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASSWORD );
		if ( $mysqli->connect_error ) {
			throw new Exception( "Connection failed: " . $mysqli->connect_error );
		}
		echo "MySQL: " . $mysqli->server_info . " ✔️<br>";
		$mysqli->close();
	} catch ( Exception $e ) {
		echo "MySQL: " . $e->getMessage() . " ❌<br>";
	}
}

?>
