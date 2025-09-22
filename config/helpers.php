<?php
/**
 * Shared utility functions for UI rendering, theming, and system detection.
 *
 * Provides:
 * - OS/user detection
 * - Theme metadata parsing
 * - Tooltip rendering
 * - Apache/PHP/MySQL version reporting
 *
 * All functions are standalone and safe for inclusion in multiple contexts.
 *
 * @author Pav
 * @license MIT
 * @version 1.2
 */

/**
 * Defines a path-related constant if not already defined, with normalisation.
 *
 * Converts slashes to the appropriate OS directory separator and trims
 * trailing separators before defining the constant. Prevents redefinition
 * if the constant is already set.
 *
 * @param string $name The name of the constant to define
 * @param string $default The default path value to normalise and use
 *
 * @return void
 */
function define_path_constant( string $name, string $default ): void {
	if ( ! defined( $name ) ) {
		$normalised = rtrim( str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $default ), DIRECTORY_SEPARATOR );
		define( $name, $normalised );
	}
}

/**
 * Returns a PHP define() statement with an encrypted and escaped value.
 *
 * The value is encrypted using encryptValue() and properly escaped for safe
 * inclusion in generated PHP config files.
 *
 * @param string $name The constant name
 * @param string $value The raw value to encrypt and define
 *
 * @return string PHP define() code snippet
 */
function defineEncrypted( string $name, string $value ): string {
	return "define('$name', '" . addslashes( encryptValue( $value ) ) . "');\n";
}

/**
 * Normalises a filesystem path to use the current OS directory separator
 * and removes any trailing slashes.
 *
 * Converts forward/backward slashes to DIRECTORY_SEPARATOR and ensures
 * the path has no trailing slash.
 *
 * @param string $path The raw user-provided path
 *
 * @return string The cleaned and normalised path
 */
function normalise_path( string $path ): string {
	$path = str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $path );

	return rtrim( $path, DIRECTORY_SEPARATOR );
}

/**
 * Sends a generic 400 error without leaking sensitive details.
 *
 * @param string $msg
 * @return void
 */
function submit_fail( string $msg ): void {
	http_response_code( 400 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Bad request.';
	error_log( '[submit.php] ' . $msg );
	exit;
}

/**
 * Writes content atomically with restrictive permissions.
 *
 * @param string $dstPath
 * @param string $content
 * @return void
 */
function atomic_write( string $dstPath, string $content ): void {
	$dir = dirname( $dstPath );
	if ( ! is_dir( $dir ) ) {
		if ( ! mkdir( $dir, 0750, true ) ) {
			submit_fail( 'Failed to create directory: ' . $dir );
		}
	}

	$temp = $dstPath . '.tmp.' . bin2hex( random_bytes( 8 ) );
	if ( file_put_contents( $temp, $content, LOCK_EX ) === false ) {
		submit_fail( 'Failed to write temp file: ' . $temp );
	}
	@chmod( $temp, 0600 );

	if ( ! rename( $temp, $dstPath ) ) {
		@unlink( $temp );
		submit_fail( 'Failed to move temp into place: ' . $dstPath );
	}
}

/**
 * Validates JSON text and returns a pretty-printed canonical form.
 *
 * @param string $raw
 * @param bool   $allowEmptyArray
 * @return string Canonical JSON
 */
function validate_and_canonicalise_json( string $raw, bool $allowEmptyArray = true ): string {
	$raw = trim( $raw );
	if ( $raw === '' ) {
		return $allowEmptyArray ? "[]" : "{}";
	}
	$data = json_decode( $raw, true, 512, JSON_THROW_ON_ERROR );
	if ( ! is_array( $data ) ) {
		throw new InvalidArgumentException( 'JSON root must be array or object.' );
	}
	return json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

/**
 * Safely read a JSON file into an associative array.
 *
 * Returns an empty array if the file does not exist, is unreadable,
 * is empty, or contains invalid JSON.
 *
 * @param string $path Absolute file path to the JSON file.
 * @return array<int|string, mixed> Decoded array or [] on failure.
 */
function read_json_array_safely( string $path ): array {
    if ( ! is_file( $path ) || ! is_readable( $path ) ) {
        return [];
    }

    $raw = file_get_contents( $path );
    if ( $raw === false || $raw === '' ) {
        return [];
    }

    $decoded = json_decode( $raw, true );
    if ( is_array( $decoded ) ) {
        return $decoded;
    }

    // Optional breadcrumb for debugging malformed JSON
    error_log( basename( $path ) . ' JSON decode failed: ' . json_last_error_msg() );
    return [];
}

/**
 * Normalises a boolean from various HTML input forms.
 *
 * @param mixed $v
 * @return string "true" or "false"
 */
function normalise_bool( $v ): string {
	$truthy = [ '1', 1, true, 'true', 'on', 'yes' ];
	return in_array( $v, $truthy, true ) ? 'true' : 'false';
}

/**
 * Detect legacy OS flags.
 *
 * @return array<string, bool>
 */
function getLegacyOSFlags(): array {
	return [
		'isWindows' => strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN',
		'isLinux'   => strtoupper( substr( PHP_OS, 0, 5 ) ) === 'LINUX',
		'isMac'     => strtoupper( substr( PHP_OS, 0, 6 ) ) === 'DARWIN' || strtoupper( substr( PHP_OS, 0, 3 ) ) === 'MAC',
	];
}

/**
 * Attempt to resolve the current OS user.
 *
 * @return string
 */
function resolveCurrentUser(): string {
	$user = $_SERVER['USERNAME']
	        ?? $_SERVER['USER']
	           ?? trim( safe_shell_exec( 'whoami' ) )
	              ?? get_current_user();

	if ( strpos( $user, '\\' ) !== false ) {
		$user = explode( '\\', $user )[1];
	} elseif ( strpos( $user, '@' ) !== false ) {
		$user = explode( '@', $user )[0];
	}

	return $user ?: 'Guest';
}

/**
 * Generate dynamic <body> class string based on UI settings and theme.
 *
 * Constructs a CSS class string for the <body> tag by evaluating the current
 * theme, system stats visibility, and availability of Apache/PHP log viewers.
 *
 * @param string $theme
 * @param bool $displayHeader
 * @param bool $displayFooter
 * @param bool $displayClock
 * @param bool $displaySearch
 * @param bool $displaySystemStats
 * @param bool $displayApacheErrorLog
 * @param bool $displayPhpErrorLog
 *
 * @return string
 */
function buildBodyClasses( string $theme, bool $displayHeader, bool $displayFooter, bool $displayClock, bool $displaySearch, bool $displaySystemStats, bool $displayApacheErrorLog, bool $displayPhpErrorLog ): string {
	$classes   = [ 'background-image' ];
	$classes[] = $displayHeader ? 'header-active' : 'header-inactive';
	$classes[] = $displayFooter ? 'footer-active' : 'footer-inactive';
	$classes[] = $displayClock ? 'clock-active' : 'clock-inactive';
	$classes[] = $displaySearch ? 'search-active' : 'search-inactive';

	if ( file_exists( __DIR__ . '/../utils/system_stats.php' ) && $displaySystemStats ) {
		$classes[] = 'system-monitor-active';
	} else {
		$classes[] = 'system-monitor-inactive';
	}

	$apacheLogAvailable = file_exists( __DIR__ . '/../utils/apache_error_log.php' );
	$phpLogAvailable    = file_exists( __DIR__ . '/../utils/php_error_log.php' );

	if ( $apacheLogAvailable && $displayApacheErrorLog ) {
		$classes[] = 'apache-error-log-active';
	} else {
		$classes[] = 'apache-error-log-inactive';
	}

	if ( $phpLogAvailable && $displayPhpErrorLog ) {
		$classes[] = 'php-error-log-active';
	} else {
		$classes[] = 'php-error-log-inactive';
	}

	if (
		( $apacheLogAvailable && $displayApacheErrorLog ) ||
		( $phpLogAvailable && $displayPhpErrorLog )
	) {
		$classes[] = 'error-log-active';
	}

	$themeFile = __DIR__ . '/../assets/scss/themes/_' . $theme . '.scss';
	if ( $theme !== 'default' && file_exists( $themeFile ) ) {
		if ( preg_match( '#\$theme-type\s*:\s*[\'"]Light[\'"]#i', file_get_contents( $themeFile ) ) ) {
			$classes[] = 'light-mode';
		}
	}

	return implode( ' ', $classes );
}

/**
 * Load theme names and types from SCSS metadata.
 *
 * @param string $themeDir
 *
 * @return array{0: array<string, string>, 1: array<string, string>}
 */
function loadThemes( string $themeDir ): array {
	$themeOptions = [ 'default' => 'Default' ];
	$themeTypes   = [];

	foreach ( glob( $themeDir . '_*.scss' ) as $file ) {
		$themeId = str_replace( '_', '', basename( $file, '.scss' ) );
		$content = preg_replace( '#//.*#', '', file_get_contents( $file ) );

		$nameMatch = preg_match( '/\$theme-name\s*:\s*[\'"](.+?)[\'"]/', $content, $name ) ? $name[1] : ucfirst( $themeId );
		$typeMatch = preg_match( '/\$theme-type\s*:\s*[\'"](light|dark)[\'"]/i', $content, $type ) ? strtolower( $type[1] ) : null;

		$themeOptions[ $themeId ] = $nameMatch;
		if ( $typeMatch ) {
			$themeTypes[ $themeId ] = $typeMatch;
		}
	}

	return [ $themeOptions, $themeTypes ];
}

/**
 * Get default tooltip definitions.
 *
 * @return array<string, string>
 */
function getDefaultTooltips(): array {
	return [
		'user_config'    => 'Adjust default settings, paths, and overrides for your Apache, MySQL and PHP environment.',
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
}

/**
 * Get the fallback tooltip string.
 *
 * @return string
 */
function getDefaultTooltipMessage(): string {
	return 'No description available for this setting.';
}

/**
 * Get tooltip content by key.
 *
 * @param string $key
 * @param array<string, string> $tooltips
 * @param string $default
 *
 * @return string
 */
function getTooltip( string $key, array $tooltips, string $default ): string {
	return array_key_exists( $key, $tooltips )
		? htmlspecialchars( $tooltips[ $key ] )
		: htmlspecialchars( $default . " (Missing tooltip key: $key)" );
}

/**
 * Render a labelled heading with tooltip, just the tooltip icon, or just the heading.
 *
 * @param string $key Unique key for tooltip content and ID.
 * @param array<string, string> $tooltips Map of tooltip keys to messages.
 * @param string $defaultTooltipMessage Fallback message if key is not found.
 * @param string $headingTag HTML heading tag (ignored if $tooltipOnly is true).
 * @param string $label Optional heading label (autogenerated from key if omitted).
 * @param bool $tooltipOnly If true, only the tooltip icon and accessible span are rendered.
 * @param bool $headingOnly If true, only the heading is rendered (tooltip is omitted).
 *
 * @return string HTML output
 */
function renderHeadingTooltip( string $key, array $tooltips, string $defaultTooltipMessage, string $headingTag = 'h3', string $label = '', bool $tooltipOnly = false, bool $headingOnly = false ): string {
	$desc  = getTooltip( $key, $tooltips, $defaultTooltipMessage );
	$title = $label ?: ucwords( str_replace( '_', ' ', $key ) );
	ob_start();

	if ( $tooltipOnly ) {
		?>
		<span class="tooltip-icon" aria-describedby="tooltip-<?= $key ?>" tabindex="0"
		      data-tooltip="<?= $desc ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?></span>
		<span id="tooltip-<?= $key ?>" class="sr-only" role="tooltip"><?= $desc ?></span>
		<?php
	} elseif ( $headingOnly ) {
		echo "<{$headingTag}>" . htmlspecialchars( $title ) . "</{$headingTag}>";
	} else {
		echo "<{$headingTag}>" . htmlspecialchars( $title ) . "</{$headingTag}>";
		?>
		<span class="tooltip-icon" aria-describedby="tooltip-<?= $key ?>" tabindex="0"
		      data-tooltip="<?= $desc ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?></span>
		<span id="tooltip-<?= $key ?>" class="sr-only" role="tooltip"><?= $desc ?></span>
		<?php
	}

	return ob_get_clean();
}

/**
 * Injects an SVG with unique IDs by auto-prefixing them.
 *
 * @param string $svgPath Path to the SVG file.
 * @param string $prefix Unique prefix to prevent ID clashes (e.g., 'icon1').
 *
 * @return string SVG content with updated IDs.
 */
function injectSvgWithUniqueIds( string $svgPath, string $prefix ): string {
	$svg = file_get_contents( $svgPath );
	if ( $svg === false ) {
		return ''; // Failed to load SVG
	}

	// Match IDs in the defs (e.g. id="a")
	preg_match_all( '/id="([^"]+)"/', $svg, $matches );
	if ( empty( $matches[1] ) ) {
		return $svg; // No IDs to replace
	}

	foreach ( $matches[1] as $originalId ) {
		$newId = $prefix . '-' . $originalId;
		// Replace ID declaration
		$svg = preg_replace( '/id="' . preg_quote( $originalId, '/' ) . '"/', 'id="' . $newId . '"', $svg );
		// Replace ID references (url(#...))
		$svg = preg_replace( '/url\(#' . preg_quote( $originalId, '/' ) . '\)/', 'url(#' . $newId . ')', $svg );
		// Optional: Also replace hrefs or other attribute references if needed
	}

	return $svg;
}

/**
 * Render a visual separator line for UI sections.
 *
 * Outputs a horizontal separator line, typically used to divide
 * sections within settings panels or dashboards.
 *
 * This function echoes the following markup:
 * <div class="separator-line" aria-hidden="true"></div>
 *
 * @param string $extraClass Optional additional CSS class(es) to append. Defaults to 'medium'.
 *
 * @return void
 */
function renderSeparatorLine( string $extraClass = '' ): void {
	$class = 'separator-line' . ( $extraClass ? ' ' . $extraClass : ' medium' );
	echo '<div class="' . $class . '" aria-hidden="true"></div>';
}

/**
 * Outputs detected versions of Apache, PHP, and MySQL with UI badges.
 *
 * This function performs shell-level checks to determine installed versions
 * of Apache and PHP, and attempts a MySQL connection using the provided
 * credentials. It directly echoes HTML badge markup indicating each status.
 *
 * Assumes constants `APACHE_PATH` and `DB_HOST` are defined.
 *
 * @param string $dbUser The decrypted database username
 * @param string $dbPass The decrypted database password
 *
 * @return void Outputs HTML directly
 */
function renderServerInfo( string $dbUser, string $dbPass ): void {
	$os            = PHP_OS_FAMILY;
	$apacheVersion = '';
	$apacheBin     = '';

	// 1. Search known binary paths under APACHE_PATH
	if ( defined( 'APACHE_PATH' ) ) {
		$binCandidates = [
			'bin/httpd',
			'bin/httpd.exe',
			'sbin/httpd',
			'httpd',
			'httpd.exe',
			'bin/apachectl',
			'apachectl',
			'sbin/apachectl',
		];

		foreach ( $binCandidates as $subpath ) {
			$testPath = rtrim( APACHE_PATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $subpath;
			if ( file_exists( $testPath ) ) {
				$apacheBin = $testPath;
				break;
			}
		}
	}

	// 2. OS-specific fallbacks if not found via APACHE_PATH
	if ( empty( $apacheBin ) ) {
		if ( $os === 'Windows' ) {
			$apachePath = trim( (string) safe_shell_exec( 'where httpd' ) );
			if ( ! empty( $apachePath ) && file_exists( $apachePath ) ) {
				$apacheBin = $apachePath;
			}
		} elseif ( $os === 'Darwin' ) {
			$macPaths = [
				'/Applications/MAMP/Library/bin/httpd',
				trim( (string) safe_shell_exec( 'which httpd' ) ),
			];
			foreach ( $macPaths as $path ) {
				if ( ! empty( $path ) && file_exists( $path ) ) {
					$apacheBin = $path;
					break;
				}
			}
		} else { // Linux & others
			$linuxPaths = [
				trim( (string) safe_shell_exec( 'command -v apachectl 2>/dev/null' ) ),
				trim( (string) safe_shell_exec( 'command -v httpd 2>/dev/null' ) ),
			];
			foreach ( $linuxPaths as $path ) {
				if ( ! empty( $path ) && file_exists( $path ) ) {
					$apacheBin = $path;
					break;
				}
			}
		}
	}

	// Optional: log if nothing was found
	if ( empty( $apacheBin ) ) {
		error_log( '[renderServerInfo] Apache binary not found in APACHE_PATH or system PATH.' );
	}

	// 3. Try to get Apache version
	if ( ! empty( $apacheBin ) ) {
		$apacheVersion = safe_shell_exec( "$apacheBin -v" );
	}

	if ( $apacheVersion && preg_match( '/Server version: Apache\/([\d.]+)/', $apacheVersion, $matches ) ) {
		echo '<span class="apache-info">Apache: <a href="#" id="toggle-apache-inspector">' . $matches[1] . ' ✔️</a></span>';
	} else {
		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false ) {
			echo '<span class="apache-unknown-info">Apache: <a href="#" id="toggle-apache-inspector">Version unknown ⚠️</a></span>';
		} else {
			echo '<span class="apache-error-info">Apache: <a href="#" id="toggle-apache-inspector">Not detected ❌</a></span>';
		}
	}

	// PHP version
	$phpVersion = phpversion();
	if ( ! $phpVersion ) {
		echo '<span class="php-unknown-info">PHP: Version unknown ⚠️</span>';
	} else {
		$isThreadSafe = ( ZEND_THREAD_SAFE ) ? 'TS' : 'NTS';
		$isFastCGI    = ( strpos( PHP_SAPI, 'cgi-fcgi' ) !== false ) ? 'FastCGI' : 'Non-FastCGI';
		echo "<span class='php-info'>PHP: <a href='#' id='toggle-phpinfo'>{$phpVersion} {$isThreadSafe} {$isFastCGI}</a> ✔️</span>";
	}

	// MySQL version
	try {
		$mysqli = new mysqli( DB_HOST, $dbUser, $dbPass );

		if ( $mysqli->connect_error ) {
			throw new Exception( "<span class='mysql-error-info'>MySQL: <a href='#' id='toggle-mysql-inspector'>" . $mysqli->connect_error . "</a></span>" );
		}

		echo "<span class='mysql-info'>MySQL: <a href='#' id='toggle-mysql-inspector'>" . $mysqli->server_info . " ✔️</a></span>";
		$mysqli->close();
	} catch ( Exception $e ) {
		echo "<span class='mysql-error-info'>MySQL: <a href='#' id='toggle-mysql-inspector'>" . $e->getMessage() . " ❌</a></span>";
	}
}

/**
 * Obfuscates a sensitive configuration value when demo mode is active.
 *
 * This function masks the provided value if the constant `DEMO_MODE` is enabled.
 * It is intended for use in forms and settings pages to prevent exposing
 * sensitive paths, database credentials, or other private values in demo environments.
 *
 * By default, it replaces each character with an asterisk (*) to maintain
 * approximate field length for consistent UI rendering.
 *
 * @param string $value The sensitive value to obfuscate.
 *
 * @return string The obfuscated value if demo mode is enabled, or the original value otherwise.
 */
function obfuscate_value( string $value ): string {
	if ( defined( 'DEMO_MODE' ) && DEMO_MODE ) {
		return str_repeat( '*', strlen( $value ) );
	}

	return $value;
}

/**
 * Merge a parsed <VirtualHost> block into the $serverData map.
 *
 * - Marks duplicates on both the previously stored entry and the incoming block
 * - Applies defaults before storing
 *
 * @param array<string, array<string, mixed>> $serverData
 * @param array<string, mixed>|null           $block
 * @return void
 */
function collectServerBlock( array &$serverData, ?array $block ): void {
	if ( ! is_array( $block ) || empty( $block['name'] ) ) {
		return;
	}

	$name = $block['name'];

	// Detect duplicate before overwriting
	if ( isset( $serverData[ $name ] ) ) {
		$serverData[ $name ]['_duplicate'] = true;
		$block['_duplicate']               = true;
	}

	$serverData[ $name ] = array_merge( [
		'valid'     => false,
		'cert'      => '',
		'key'       => '',
		'certValid' => true,
		'docRoot'   => '',
	], $block );
}

?>
