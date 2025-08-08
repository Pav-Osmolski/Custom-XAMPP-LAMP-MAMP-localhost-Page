<?php
/**
 * User Configuration Submit Handler
 *
 * Processes POST submissions from the settings UI and updates:
 * - `user_config.php` with DB credentials, paths, flags, and PHP settings
 * - `folders.json` for custom folder column config
 * - `link_templates.json` for link generation patterns
 * - `dock.json` for the quick-access dock
 * - Updates the active `php.ini` file with display_errors and error_reporting settings
 * - Invalidates OPcache to apply config changes immediately
 *
 * Security:
 * - Paths are normalised
 * - DB user/pass are encrypted using `encryptValue()`
 *
 * Dependencies:
 * - `security.php` for authentication
 * - `config.php` for constants
 *
 * Redirects:
 * - Returns to `index.php` on successful submission
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

	if ( defined( 'DEMO_MODE' ) && DEMO_MODE ) {
		header( "Location: ?view=settings&saved=0" );
		exit;
	}

	$theme                 = isset( $_POST['theme'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $_POST['theme'] ) : 'default';
	$apacheFastMode        = isset( $_POST['apacheFastMode'] ) ? 'true' : 'false';
	$mysqlFastMode         = isset( $_POST['mysqlFastMode'] ) ? 'true' : 'false';
	$displayHeader         = isset( $_POST['displayHeader'] ) ? 'true' : 'false';
	$displayFooter         = isset( $_POST['displayFooter'] ) ? 'true' : 'false';
	$displayClock          = isset( $_POST['displayClock'] ) ? 'true' : 'false';
	$displaySearch         = isset( $_POST['displaySearch'] ) ? 'true' : 'false';
	$displaySystemStats    = isset( $_POST['displaySystemStats'] ) ? 'true' : 'false';
	$displayApacheErrorLog = isset( $_POST['displayApacheErrorLog'] ) ? 'true' : 'false';
	$displayPhpErrorLog    = isset( $_POST['displayPhpErrorLog'] ) ? 'true' : 'false';
	$useAjaxForStats       = isset( $_POST['useAjaxForStats'] ) ? 'true' : 'false';
	$displayPhpErrors      = isset( $_POST['displayPhpErrors'] ) ? '1' : '0';
	$phpErrorLevel         = $_POST['phpErrorLevel'] ?? 'E_ALL';
	$logPhpErrors          = isset( $_POST['logPhpErrors'] ) ? '1' : '0';

	$user_config = "<?php\n";

	// DB settings
	$user_config .= "define('DB_HOST', '" . addslashes( $_POST['DB_HOST'] ) . "');\n";
	$user_config .= defineEncrypted( 'DB_USER', $_POST['DB_USER'] ?? '' );
	$user_config .= defineEncrypted( 'DB_PASSWORD', $_POST['DB_PASSWORD'] ?? '' );

	// Raw user paths (slashes not normalised here — config.php handles it)
	$user_config .= "define('APACHE_PATH', '" . addslashes( normalise_path( $_POST['APACHE_PATH'] ) ) . "');\n";
	$user_config .= "define('HTDOCS_PATH', '" . addslashes( normalise_path( $_POST['HTDOCS_PATH'] ) ) . "');\n";
	$user_config .= "define('PHP_PATH', '" . addslashes( normalise_path( $_POST['PHP_PATH'] ) ) . "');\n";

	// Utility options
	$user_config .= "\$apacheFastMode = {$apacheFastMode};\n";
	$user_config .= "\$mysqlFastMode = {$mysqlFastMode};\n";

	// Feature flags
	$user_config .= "\$theme = '$theme';\n";
	$user_config .= "\$displayHeader = {$displayHeader};\n";
	$user_config .= "\$displayFooter = {$displayFooter};\n";
	$user_config .= "\$displayClock = {$displayClock};\n";
	$user_config .= "\$displaySearch = {$displaySearch};\n";
	$user_config .= "\$displaySystemStats = {$displaySystemStats};\n";
	$user_config .= "\$displayApacheErrorLog = {$displayApacheErrorLog};\n";
	$user_config .= "\$displayPhpErrorLog = {$displayPhpErrorLog};\n";
	$user_config .= "\$useAjaxForStats = {$useAjaxForStats};\n";

	// PHP error handling
	$user_config .= "ini_set('display_errors', {$displayPhpErrors});\n";
	$user_config .= "error_reporting($phpErrorLevel);\n";
	$user_config .= "ini_set('log_errors', {$logPhpErrors});\n";

	// Save folders, link_templates and dock json configuration
	write_valid_json( __DIR__ . '/../config/folders.json', $_POST['folders_json'] ?? '' );
	write_valid_json( __DIR__ . '/../config/link_templates.json', $_POST['link_templates'] ?? '' );
	write_valid_json( __DIR__ . '/../config/dock.json', $_POST['dock_json'] ?? '' );

	file_put_contents( __DIR__ . '/../config/user_config.php', $user_config );

	// Invalidate OPcache to ensure updated config is used immediately
	if ( function_exists( 'opcache_invalidate' ) ) {
		opcache_invalidate( __DIR__ . '/../config/user_config.php', true );
	}

	// Update php.ini file with submitted settings
	$php_ini_path = php_ini_loaded_file();

	if ( $php_ini_path && file_exists( $php_ini_path ) && is_writable( $php_ini_path ) ) {
		// Backup original ini
		$backup_path = $php_ini_path . '.bak';
		if ( ! file_exists( $backup_path ) ) {
			copy( $php_ini_path, $backup_path );
		}

		$ini_content = file_get_contents( $php_ini_path );

		$display_errors_value  = isset( $_POST['displayPhpErrors'] ) ? 'On' : 'Off';
		$error_reporting_value = $_POST['phpErrorLevel'] ?? 'E_ALL';

		// Patch display_errors
		if ( preg_match( '/^\s*display_errors\s*=.*/mi', $ini_content ) ) {
			$ini_content = preg_replace( '/^\s*display_errors\s*=.*/mi', 'display_errors = ' . $display_errors_value, $ini_content );
		} else {
			$ini_content .= "\ndisplay_errors = " . $display_errors_value;
		}

		// Patch error_reporting
		if ( preg_match( '/^\s*error_reporting\s*=.*/mi', $ini_content ) ) {
			$ini_content = preg_replace( '/^\s*error_reporting\s*=.*/mi', 'error_reporting = ' . $error_reporting_value, $ini_content );
		} else {
			$ini_content .= "\nerror_reporting = " . $error_reporting_value;
		}

		file_put_contents( $php_ini_path, $ini_content );
	}

	header( "Location: ?view=settings&saved=1" );
	exit;
}
