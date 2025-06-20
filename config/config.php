<?php
/**
 * Global Configuration and Initialisation
 *
 * Centralised loader for core settings, user preferences, path definitions,
 * theme parsing, UI flag defaults, and server environment metadata.
 *
 * Key Responsibilities:
 * - Loads user-defined overrides from `user_config.php` if available
 * - Defines application path constants (`APACHE_PATH`, `HTDOCS_PATH`, `PHP_PATH`)
 * - Sets database and UI configuration defaults (e.g. `$displayClock`, `$theme`)
 * - Parses theme SCSS metadata for `themeOptions` and `themeTypes` arrays
 * - Resolves current user identity and adjusts `$bodyClasses` accordingly
 * - Provides `renderServerInfo()` for displaying Apache/PHP/MySQL details
 * - Provides `renderTooltip()` to inject labelled headings with accessible tooltips
 *
 * Assumptions:
 * - `encryptValue()` and `getDecrypted()` handle DB credential obfuscation
 * - Server detection is OS-aware (Windows, macOS, Linux)
 * - Optional utilities in `/utils/` may affect display state via `$bodyClasses`
 *
 * Global Outputs:
 * - `$theme`, `$currentTheme`, `$themeOptions`, `$themeTypes`
 * - `$bodyClasses`, `$user`, `$dbUser`, `$dbPass`
 * - `$display*` flags (UI toggles)
 * - `$tooltips`, `$defaultTooltipMessage`
 *
 * Functions Provided:
 * - `renderServerInfo()`: Print current server versions
 * - `renderTooltip()`: Render tooltip-linked headers
 * - `getTooltip()`: Return tooltip content with fallback handling
 *
 * @author Pav
 * @license MIT
 * @version 2.0
 */

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

// Theme and UI display defaults (overridden by user_config.php)
$defaults = [
	'theme'                => 'default',
	'displayClock'         => true,
	'displaySearch'        => true,
	'displaySystemStats'   => true,
	'displayApacheErrorLog'=> true,
	'useAjaxForStats'      => true,
];

foreach ( $defaults as $key => $value ) {
	if ( ! isset( $$key ) ) {
		$$key = $value;
	}
}

// Detect OS for older PHP versions. Not actually used because of $os, but kept here for reference.
$isWindowsPHPOld = strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
$isLinuxPHPOld   = strtoupper( substr( PHP_OS, 0, 5 ) ) === 'LINUX';
$isMacPHPOld     = strtoupper( substr( PHP_OS, 0, 6 ) ) === 'DARWIN' || strtoupper( substr( PHP_OS, 0, 3 ) ) === 'MAC';

// Check for valid paths and files
$apachePathValid = file_exists( APACHE_PATH );
$htdocsPathValid = file_exists( HTDOCS_PATH );
$phpPathValid    = file_exists( PHP_PATH );
$apacheToggle    = file_exists( __DIR__ . '/../utils/toggle_apache.php' );

// Decrypt current DB User and Password
$dbUser = getDecrypted( 'DB_USER' );
$dbPass = getDecrypted( 'DB_PASSWORD' );

// Get current PHP Error Level
$currentErrorLevel = ini_get( 'error_reporting' );

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

// Injected <body> classes
$classes = [ 'background-image' ];
$classes[] = $displayClock ? 'clock-active' : 'clock-inactive';
$classes[] = $displaySearch ? 'search-active' : 'search-inactive';
$classes[] = ( file_exists( __DIR__ . '/../utils/system_stats.php' ) && $displaySystemStats )
	? 'system-monitor-active' : 'system-monitor-inactive';
$classes[] = ( file_exists( __DIR__ . '/../utils/apache_error_log.php' ) && $displayApacheErrorLog )
	? 'error-log-section-active' : 'error-log-section-inactive';

if ( isset( $theme ) && $theme !== 'default' ) {
	$themeFile = __DIR__ . '/../assets/scss/themes/_' . $theme . '.scss';

	if ( file_exists( $themeFile ) && preg_match( '#\$theme-type\s*:\s*[\'"]Light[\'"]#i', file_get_contents( $themeFile ) ) ) {
		$classes[] = 'light-mode';
	}
}

$bodyClasses = implode( ' ', $classes );

// Add default theme manually
$themeOptions = [
	'default' => 'Default',
];

// Load custom themes from /themes/
$themeDir   = __DIR__ . '/../assets/scss/themes/';
$themeFiles = glob( $themeDir . '_*.scss' );
$themeTypes = [];

foreach ( $themeFiles as $file ) {
	$basename = basename( $file, '.scss' );
	$themeId  = str_replace( '_', '', $basename );
	$content  = file_get_contents( $file );

	$themeName = ucfirst( $themeId );
	$themeType = null;

	// Remove comments before matching
	$content = preg_replace( '#//.*#', '', $content );

	if ( preg_match( '/\$theme-name\s*:\s*[\'"](.+?)[\'"]/', $content, $matches ) ) {
		$themeName = $matches[1];
	}
	if ( preg_match( '/\$theme-type\s*:\s*[\'"](light|dark)[\'"]/i', $content, $matches ) ) {
		$themeTypes[ $themeId ] = strtolower( $matches[1] );
	}

	$themeOptions[ $themeId ] = $themeName;
}

$currentTheme = $theme ?? 'default';

// Centralised tooltip descriptions
$tooltips = [
	'user_settings'  => 'Set your database credentials, Apache configuration, HTDocs directory, and PHP executable path.',
	'user_interface' => 'Toggle visual and interactive elements like themes, layouts, and visibility of dashboard components.',
	'php_error'      => 'Configure how PHP displays or logs errors, including toggling error reporting levels and defining log output behavior for development or production use.',
	'folders'        => 'Manage which folders appear in each column, their titles, filters, and link behaviour.',
	'link_templates' => 'Define how each folder\'s website links should appear by customising the HTML templates used per column.',
	'dock'           => 'Manage the items displayed in the dock, including their order, icons, and link targets.',
	'apache_control' => 'Restart the Apache server.',
	'vhosts_manager' => 'Browse, check, and open virtual hosts with cert and DNS validation.',
	'clear_storage'  => 'This will reset saved UI settings (theme, Column Order and Column Size etc.) stored in your browser’s local storage.'
];

$defaultTooltipMessage = 'No description available for this setting.';

function getTooltip( $key, $tooltips, $default ) {
	return isset( $tooltips[ $key ] )
		? htmlspecialchars( $tooltips[ $key ] )
		: htmlspecialchars( $default . " (Missing tooltip key: $key)" );
}

function renderTooltip( string $key, array $tooltips, string $defaultTooltipMessage, string $headingTag = 'h3', string $label = '' ): string {
	$desc  = getTooltip( $key, $tooltips, $defaultTooltipMessage );
	$title = $label ?: ucwords( str_replace( '_', ' ', $key ) );
	ob_start(); ?>
	<<?= $headingTag ?>><?= htmlspecialchars( $title ) ?>
	<span class="tooltip-icon" aria-describedby="tooltip-<?= $key ?>" tabindex="0"
	      data-tooltip="<?= $desc ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?></span>
	</<?= $headingTag ?>>
	<span id="tooltip-<?= $key ?>" class="sr-only" role="tooltip"><?= $desc ?></span>
	<?php
	return ob_get_clean();
}

// Summarise detected versions of Apache, PHP, and MySQL
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
