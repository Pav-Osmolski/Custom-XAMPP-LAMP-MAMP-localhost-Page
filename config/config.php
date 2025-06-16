<?php
// config.php
require_once __DIR__ . '/security.php';

// Load user-specific overrides first
if ( file_exists( __DIR__ . '/user_config.php' ) ) {
	require_once __DIR__ . '/user_config.php';
}

// Helper: define only if not already defined, with path normalisation
function define_path_constant( $name, $default ) {
	if ( ! defined( $name ) ) {
		$normalised = rtrim( str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $default ), DIRECTORY_SEPARATOR );
		define( $name, $normalised );
	}
}

// DB settings with guards
if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', 'localhost' );
}
if ( ! defined( 'DB_USER' ) ) {
	define( 'DB_USER', 'user' );
}
if ( ! defined( 'DB_PASSWORD' ) ) {
	define( 'DB_PASSWORD', 'password' );
}

// Paths (user-defined values will pass through untouched)
define_path_constant( 'APACHE_PATH', 'C:/xampp/apache' );
define_path_constant( 'HTDOCS_PATH', 'C:/htdocs' );
define_path_constant( 'PHP_PATH', 'C:/xampp/php' );

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
$isLinuxPHPOld   = strtoupper( substr( PHP_OS, 0, 5 ) ) === 'LINUX';
$isMacPHPOld     = strtoupper( substr( PHP_OS, 0, 6 ) ) === 'DARWIN' || strtoupper( substr( PHP_OS, 0, 3 ) ) === 'MAC';

// Get username based on OS
$user = $_SERVER['USERNAME']
        ?? $_SERVER['USER']
           ?? trim( safe_shell_exec( 'whoami' ) )
              ?? get_current_user();

// Extract last part of domain\user or user@domain
if ( strpos( $user, '\\' ) !== false ) {
	$user = explode( '\\', $user )[1];
} elseif ( strpos( $user, '@' ) !== false ) {
	$user = explode( '@', $user )[0];
}

$user = $user ?: 'Guest';

$bodyClasses = 'background-image';

if ( file_exists( __DIR__ . '/../utils/system_stats.php' ) && $displaySystemStats ) {
	$bodyClasses .= ' system-monitor-active';
}

if ( file_exists( __DIR__ . '/../utils/apache_error_log.php' ) && $displayApacheErrorLog ) {
	$bodyClasses .= ' error-log-section-active';
}

function renderServerInfo() {
	$os = PHP_OS_FAMILY;

	$apacheVersion = '';
	if ( $os === 'Windows' ) {
		$httpdPath = APACHE_PATH . '\\bin\\httpd.exe';
		if ( file_exists( $httpdPath ) ) {
			$apacheVersion = safe_shell_exec( $httpdPath . " -v" );
		}
	} elseif ( $os === 'Darwin' ) {
		$mampPath = '/Applications/MAMP/Library/bin/httpd';
		$brewPath = trim( safe_shell_exec( 'which httpd' ) );

		if ( file_exists( $mampPath ) ) {
			$apacheVersion = safe_shell_exec( $mampPath . " -v" );
		} elseif ( ! empty( $brewPath ) ) {
			$apacheVersion = safe_shell_exec( "$brewPath -v" );
		}
	} else {
		$apacheVersion = safe_shell_exec( 'apachectl -v 2>/dev/null' ) ?: safe_shell_exec( 'httpd -v 2>/dev/null' );
	}

	// Attempt to extract the versions
	if ( $apacheVersion && preg_match( '/Server version: Apache\/([\d.]+)/', $apacheVersion, $matches ) ) {
		echo '<span class="apache-info">Apache: <a href="#" id="toggle-apache-inspector">' . $matches[1] . ' ✔️</a></span>';
	} else {
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false ) {
			echo '<span class="apache-unknown-info">Apache: <a href="#" id="toggle-apache-inspector">Version unknown ⚠️</a></span>';
		} else {
			echo '<span class="apache-error-info">Apache: <a href="#" id="toggle-apache-inspector">Not detected ❌</a></span>';
		}
	}

	// Check if PHP version is detected
	$phpVersion = phpversion();
	if ( $phpVersion === false ) {
		echo '<span class="php-unknown-info">PHP: Version unknown ⚠️</span>';
	} else {
		$isThreadSafe = ( ZEND_THREAD_SAFE ) ? "TS" : "NTS";
		$isFastCGI    = ( strpos( PHP_SAPI, 'cgi-fcgi' ) !== false ) ? "FastCGI" : "Non-FastCGI";
		echo '<span class="php-info">PHP: <a href="#" id="toggle-phpinfo">' . $phpVersion . " $isThreadSafe $isFastCGI</a> ✔️</span>";
	}

	// Check MySQL version
	$db_user = getDecrypted( 'DB_USER' );
	$db_pass = getDecrypted( 'DB_PASSWORD' );

	try {
		$mysqli = new mysqli( DB_HOST, $db_user, $db_pass );

		if ( $mysqli->connect_error ) {
			throw new Exception( "<span class='mysql-error-info'>MySQL: " . $mysqli->connect_error . "</span>" );
		}
		echo "<span class='mysql-info'>MySQL: " . $mysqli->server_info . " ✔️</span>";
		$mysqli->close();
	} catch ( Exception $e ) {
		echo "<span class='mysql-error-info'>MySQL: " . $e->getMessage() . " ❌</span>";
	}
}

?>
