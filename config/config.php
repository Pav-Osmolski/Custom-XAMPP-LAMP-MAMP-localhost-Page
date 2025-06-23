<?php
/**
 * Global Configuration and Initialisation
 *
 * Bootstraps the application environment, merging user overrides, defining constants,
 * setting defaults, and loading helper utilities for theming, UI behaviour, and diagnostics.
 *
 * Responsibilities:
 * - Load user overrides from `user_config.php` (if available)
 * - Define environment paths: `APACHE_PATH`, `HTDOCS_PATH`, `PHP_PATH`
 * - Define DB constants if not already set: `DB_HOST`, `DB_USER`, `DB_PASSWORD`
 * - Populate UI feature flags and theme selection
 * - Derive current user, body classes, and theme metadata
 * - Decrypt DB credentials using `getDecrypted()`
 * - Provide default tooltip descriptions
 *
 * Assumptions:
 * - Constants `DB_USER` and `DB_PASSWORD` are encrypted and must be decrypted
 * - `toggle_apache.php` is optional and affects `$apacheToggle`
 * - External helpers from `helpers.php` handle rendering, detection, and theming
 *
 * Global Outputs:
 * - Paths: `APACHE_PATH`, `HTDOCS_PATH`, `PHP_PATH`
 * - Flags: `$apachePathValid`, `$htdocsPathValid`, `$phpPathValid`, `$apacheToggle`
 * - DB: `$dbUser`, `$dbPass`
 * - UI: `$theme`, `$currentTheme`, `$bodyClasses`, `$tooltips`, `$defaultTooltipMessage`
 * - Toggles: `$displayClock`, `$displaySearch`, `$displaySystemStats`, `$displayApacheErrorLog`, `$useAjaxForStats`
 * - Themes: `$themeOptions`, `$themeTypes`
 * - Misc: `$user`, `$currentErrorLevel`
 *
 * Depends On:
 * - `security.php` for access control
 * - `helpers.php` for shared logic like `resolveCurrentUser()`, `buildBodyClasses()`, `loadThemes()`, etc.
 *
 * @author Pav
 * @license MIT
 * @version 2.2
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/helpers.php';

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
foreach (
	[
		'DB_HOST'     => 'localhost',
		'DB_USER'     => 'user',
		'DB_PASSWORD' => 'password',
	] as $const => $default
) {
	if ( ! defined( $const ) ) {
		define( $const, $default );
	}
}

// Paths (user-defined values will pass through untouched)
define_path_constant( 'APACHE_PATH', 'C:/xampp/apache' );
define_path_constant( 'HTDOCS_PATH', 'C:/htdocs' );
define_path_constant( 'PHP_PATH', 'C:/xampp/php' );

// Theme and UI display defaults (overridden by user_config.php)
$defaults = [
	'theme'                 => 'default',
	'apacheFastMode'        => false,
	'mysqlFastMode'         => false,
	'displayClock'          => true,
	'displaySearch'         => true,
	'displaySystemStats'    => true,
	'displayApacheErrorLog' => true,
	'useAjaxForStats'       => true,
];

foreach ( $defaults as $key => $value ) {
	if ( ! isset( $$key ) ) {
		$$key = $value;
	}
}

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

// Finalise computed environment values using helper functions
$user = resolveCurrentUser();

$bodyClasses = buildBodyClasses(
	$theme,
	$displayClock,
	$displaySearch,
	$displaySystemStats,
	$displayApacheErrorLog
);

[ $themeOptions, $themeTypes ] = loadThemes( __DIR__ . '/../assets/scss/themes/' );

$currentTheme = $theme;

$tooltips              = getDefaultTooltips();
$defaultTooltipMessage = getDefaultTooltipMessage();

?>
