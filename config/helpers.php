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
 * @version 1.6
 */

/**
 * Polyfill for PHP 8.0's str_starts_with().
 *
 * Behaviour:
 * - Case-sensitive, binary-safe prefix check.
 * - Returns true when $needle is an empty string.
 * - Equivalent to: strncmp($haystack, $needle, strlen($needle)) === 0
 *
 * Notes:
 * - Provided only if str_starts_with() does not already exist (PHP < 8.0).
 * - For multibyte/locale-aware checks, consider mb_strpos($haystack, $needle) === 0.
 *
 * @param string $haystack Full string to inspect.
 * @param string $needle Prefix to compare against.
 *
 * @return bool  True if $haystack begins with $needle; false otherwise.
 */
if ( ! function_exists( 'str_starts_with' ) ) {
	function str_starts_with( string $haystack, string $needle ): bool {
		return strncmp( $haystack, $needle, strlen( $needle ) ) === 0;
	}
}

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
 *
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
 *
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
 * @param bool $allowEmptyArray
 *
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
 *
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
 * Normalise a configured subdirectory safely below HTDOCS_PATH.
 *
 * Uses your normalise_path() for slash handling, prevents traversal,
 * and anchors the result under HTDOCS_PATH.
 *
 * @param string $relative
 *
 * @return array{dir:string,error:?string}
 */
function normalise_subdir( $relative ) {
	$relative = (string) $relative;

	// Canonical slash handling via your helper
	$subdir = trim( normalise_path( $relative ), DIRECTORY_SEPARATOR );

	// Prevent traversal
	if ( strpos( $subdir, '..' ) !== false ) {
		return [ 'dir' => '', 'error' => 'Security: directory traversal detected in "dir".' ];
	}

	$abs = rtrim( HTDOCS_PATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR;

	return [ 'dir' => $abs, 'error' => null ];
}

/**
 * List immediate subdirectories of a directory, skipping dot entries and sorting naturally.
 *
 * @param string $absDir
 *
 * @return array<int, string> Folder basenames
 */
function list_subdirs( $absDir ) {
	if ( ! is_dir( $absDir ) ) {
		return [];
	}

	$out = [];
	$it  = new DirectoryIterator( $absDir );
	foreach ( $it as $f ) {
		if ( $f->isDot() ) {
			continue;
		}
		if ( $f->isDir() ) {
			$out[] = $f->getBasename();
		}
	}

	natcasesort( $out );

	return array_values( $out );
}

/**
 * Build the URL/display name for a folder using column rules and special cases.
 *
 * Behaviour:
 * - If both `urlRules.match` and `urlRules.replace` are empty: no rule is applied, no error.
 * - If only one of the two is provided: an error is added (both must be set or both empty).
 * - If both are provided:
 *     - Validates `urlRules.match` as a regex.
 *     - If valid and matches $folderName, applies `urlRules.replace` as a regex replace.
 *     - If invalid, logs an error and leaves $folderName unchanged.
 *     - If it does not match $folderName, returns "__SKIP__" to signal exclusion.
 * - After urlRules, applies any `specialCases` overrides if present.
 *
 * @param string $folderName The original folder name.
 * @param array<string,mixed> $column The column definition (may contain urlRules and specialCases).
 * @param array<int,string> $errors Reference to an array that accumulates human-readable errors.
 *
 * @return string The transformed URL/display name, or "__SKIP__" sentinel if the folder should be skipped.
 */
function build_url_name( $folderName, array $column, array &$errors ) {
	$urlName = $folderName;

	if ( isset( $column['urlRules'] ) && is_array( $column['urlRules'] ) ) {
		$match   = isset( $column['urlRules']['match'] ) ? (string) $column['urlRules']['match'] : '';
		$replace = isset( $column['urlRules']['replace'] ) ? (string) $column['urlRules']['replace'] : '';

		$matchTrim   = trim( $match );
		$replaceTrim = trim( $replace );

		// Both empty => no rule, no error
		if ( $matchTrim === '' && $replaceTrim === '' ) {
			// do nothing
		} elseif ( ( $matchTrim === '' ) !== ( $replaceTrim === '' ) ) {
			// One provided without the other
			$errors[] = 'Both urlRules.match and urlRules.replace must be set (or both empty) for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
		} else {
			// Validate regex for match
			set_error_handler( static function () {
			}, E_WARNING );
			$ok = @preg_match( $matchTrim, '' );
			restore_error_handler();

			if ( $ok === false ) {
				$errors[] = 'Invalid regex in urlRules.match for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
			} else {
				if ( preg_match( $matchTrim, $folderName ) ) {
					// Apply "replace" as your pattern to strip
					$newName = @preg_replace( $replaceTrim, '', $folderName );
					if ( $newName === null ) {
						$errors[] = 'Invalid regex in urlRules.replace for column "' . htmlspecialchars( (string) ( $column['title'] ?? '' ) ) . '".';
					} else {
						$urlName = $newName;
					}
				} else {
					// No match => skip this item for this column
					return '__SKIP__';
				}
			}
		}
	}

	if ( ! empty( $column['specialCases'] ) && is_array( $column['specialCases'] ) ) {
		if ( array_key_exists( $urlName, $column['specialCases'] ) ) {
			$urlName = (string) $column['specialCases'][ $urlName ];
		}
	}

	return $urlName;
}

/**
 * Resolve a template by name to HTML.
 *
 * @param string $templateName
 * @param array<string, array<string,mixed>> $templatesByName
 *
 * @return string
 */
function resolve_template_html( $templateName, array $templatesByName ) {
	if ( isset( $templatesByName[ $templateName ]['html'] ) ) {
		return (string) $templatesByName[ $templateName ]['html'];
	}
	if ( isset( $templatesByName['basic']['html'] ) ) {
		return (string) $templatesByName['basic']['html'];
	}

	return '<li><a href="/{urlName}">{urlName}</a></li>';
}

/**
 * Render one list item from a template, substituting placeholders safely.
 *
 * @param string $templateHtml
 * @param string $urlName
 * @param bool $disableLinks
 *
 * @return string
 */
function render_item_html( $templateHtml, $urlName, $disableLinks ) {
	$safe = htmlspecialchars( $urlName, ENT_QUOTES, 'UTF-8' );
	$html = str_replace( '{urlName}', $safe, $templateHtml );

	if ( $disableLinks ) {
		$html = strip_tags( $html, '<li><div><span>' );
	}

	return $html;
}

/**
 * Normalises a boolean from various HTML input forms.
 *
 * @param mixed $v
 *
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
		'export'         => 'Create an archive of site files or a database. Pick a subfolder, optionally include or export only wp-content/uploads, and apply your exclude list.',
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
 * @param array<string, mixed>|null $block
 *
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

/**
 * Returns the default folder names to exclude when zipping.
 *
 * Override by defining EXPORT_EXCLUDE (array) in user_config.php.
 *
 * @return string[] Normalised, unique folder names (no empties).
 */
function export_get_default_excludes(): array {
	$defaults = [ '.git', '.idea', '.vscode', 'node_modules', 'vendor', 'dist', 'build' ];
	if ( defined( 'EXPORT_EXCLUDE' ) && is_array( EXPORT_EXCLUDE ) ) {
		$items = array_map(
			static function ( $v ) {
				return trim( (string) $v );
			},
			EXPORT_EXCLUDE
		);
		$items = array_values( array_unique( array_filter(
			$items,
			static function ( $v ) {
				return $v !== '';
			}
		) ) );

		return $items;
	}

	return $defaults;
}

/**
 * Load folders.json and return as an array.
 *
 * @param string $path Absolute path to folders.json
 *
 * @return array<int, array<string,mixed>> Parsed config; empty array on missing/invalid JSON.
 */
function export_load_folders_json( string $path ): array {
	if ( ! is_file( $path ) ) {
		return [];
	}
	$json = @file_get_contents( $path );
	if ( $json === false || $json === '' ) {
		return [];
	}
	$data = json_decode( $json, true );

	return is_array( $data ) ? $data : [];
}

/**
 * Parse a regex literal string like "/^wp-/" into a valid pattern.
 * If input looks like a delimited regex already, it is returned as-is.
 *
 * @param string $raw
 *
 * @return string|null
 */
function export_parse_regex_literal( string $raw ): ?string {
	$raw = trim( $raw );
	// Looks like /.../flags already
	if ( strlen( $raw ) >= 2 && $raw[0] === '/' && strrpos( $raw, '/' ) !== 0 ) {
		return $raw;
	}

	return null;
}

/**
 * Detect if a directory looks like a WordPress site root.
 *
 * @param string $absPath
 *
 * @return bool
 */
function export_is_wp_root( string $absPath ): bool {
	return is_file( $absPath . DIRECTORY_SEPARATOR . 'wp-config.php' )
	       || is_dir( $absPath . DIRECTORY_SEPARATOR . 'wp-content' );
}

/**
 * Detect if a WordPress uploads folder exists under a given root.
 *
 * @param string $absPath
 *
 * @return bool
 */
function export_has_wp_uploads( string $absPath ): bool {
	return is_dir( $absPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' );
}

/**
 * List subfolders of a base "dir" entry, applying excludeList and urlRules.match.
 *
 * @param string $htdocsPath Absolute HTDOCS path base.
 * @param string $dirEntry Relative dir from folders.json.
 * @param string[] $excludeList Names to exclude (exact match).
 * @param array $urlRules Optional urlRules with "match" (pre-delimited regex string).
 *
 * @return array<int, array{name:string,abs:string,isWp:bool,hasUploads:bool}>
 */
function export_list_subfolders( string $htdocsPath, string $dirEntry, array $excludeList = [], array $urlRules = [] ): array {
	$result  = [];
	$baseAbs = rtrim( $htdocsPath, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . str_replace( [
			'/',
			'\\'
		], DIRECTORY_SEPARATOR, $dirEntry );
	if ( ! is_dir( $baseAbs ) ) {
		return $result;
	}

	$excludeSet = array_flip( $excludeList );
	$matchRaw   = isset( $urlRules['match'] ) && is_string( $urlRules['match'] ) ? $urlRules['match'] : null;
	$pattern    = $matchRaw ? export_parse_regex_literal( $matchRaw ) : null;

	$items = @scandir( $baseAbs ) ?: [];
	foreach ( $items as $name ) {
		if ( $name === '.' || $name === '..' ) {
			continue;
		}
		if ( isset( $excludeSet[ $name ] ) ) {
			continue;
		}

		$abs = $baseAbs . DIRECTORY_SEPARATOR . $name;
		if ( ! is_dir( $abs ) ) {
			continue;
		}

		if ( $pattern !== null ) {
			$pm = @preg_match( $pattern, $name );
			if ( $pm !== 1 ) {
				continue;
			} // skip non-matches or invalid regex
		}

		$isWp       = export_is_wp_root( $abs );
		$hasUploads = $isWp && export_has_wp_uploads( $abs );

		$result[] = [
			'name'       => $name,
			'abs'        => $abs,
			'isWp'       => $isWp,
			'hasUploads' => $hasUploads,
		];
	}

	usort( $result, static function ( $a, $b ) {
		return strnatcasecmp( $a['name'], $b['name'] );
	} );

	return $result;
}

/**
 * Ensure the public exports directory exists and is writable.
 * Uses /dist/exports so the browser can download directly.
 *
 * @return array{abs:string,rel:string} Absolute path and web-relative path.
 */
function export_ensure_exports_dir(): array {
	$root    = dirname( __DIR__ ); // project root (config/..)
	$absPath = $root . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'exports';
	if ( ! is_dir( $absPath ) ) {
		@mkdir( $absPath, 0775, true );
	}

	return [
		'abs' => $absPath,
		'rel' => 'dist/exports', // web path uses forward slashes
	];
}

/**
 * Create a compressed archive of a project directory for download.
 *
 * Prefers ZipArchive; falls back to .tar.gz via PharData (or .tar if zlib is absent).
 * Returns false on failure and populates $error with a human-readable reason.
 *
 * Rules:
 * - Folder names in $excludeFolders are skipped anywhere in the tree (case-insensitive).
 * - If the source is a WordPress root, wp-content/uploads is excluded unless $includeUploads is true.
 * - If $onlyUploads is true, only wp-content/uploads is archived.
 *
 * @param string $sourceAbs Absolute path to the directory to archive.
 * @param string $zipAbs Preferred .zip path; fallback may create .tar.gz or .tar with the same basename.
 * @param string[] $excludeFolders Folder names to exclude at any depth (e.g. ['.git','node_modules','vendor','dist','build','.idea','.vscode']).
 * @param bool $includeUploads Include wp-content/uploads when the source is a WP root (ignored when $onlyUploads is true).
 * @param string|null   &$error Output: detailed error string on failure; null on success.
 * @param bool $onlyUploads When true, export only wp-content/uploads for WP sites.
 *
 * @return bool True if an archive was created (either $zipAbs or the same path with .tar[.gz]).
 */
function export_zip_directory( string $sourceAbs, string $zipAbs, array $excludeFolders, bool $includeUploads, ?string &$error = null, bool $onlyUploads = false ): bool {
	$error = null;

	if ( ! is_dir( $sourceAbs ) ) {
		$error = 'Source folder does not exist.';

		return false;
	}

	$sourceAbs    = rtrim( $sourceAbs, DIRECTORY_SEPARATOR );
	$baseLen      = strlen( $sourceAbs ) + 1;
	$excludeSet   = array_flip( array_map( 'strtolower', $excludeFolders ) ); // case-insensitive
	$wpUploadsRel = 'wp-content' . DIRECTORY_SEPARATOR . 'uploads';
	$isWp         = export_is_wp_root( $sourceAbs );
	$uploadsOk    = $isWp && $includeUploads;

	// If exporting uploads only, retarget the iterator to the uploads root
	if ( $onlyUploads ) {
		$uploadsAbs = $sourceAbs . DIRECTORY_SEPARATOR . $wpUploadsRel;
		if ( ! $isWp || ! is_dir( $uploadsAbs ) ) {
			$error = 'Uploads folder not found for this selection.';

			return false;
		}
		$sourceAbs  = $uploadsAbs;
		$baseLen    = strlen( $sourceAbs ) + 1;
		$excludeSet = []; // not relevant within uploads
	}

	$iter = function () use ( $sourceAbs, $baseLen, $excludeSet, $isWp, $uploadsOk, $wpUploadsRel, $onlyUploads ) {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $sourceAbs, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $it as $path => $info ) {
			$rel = substr( $path, $baseLen );

			if ( ! $onlyUploads ) {
				// Fast any-depth exclude (case-insensitive)
				$segments   = explode( DIRECTORY_SEPARATOR, strtolower( $rel ) );
				$excludeHit = false;
				foreach ( $segments as $seg ) {
					if ( isset( $excludeSet[ $seg ] ) ) {
						$excludeHit = true;
						break;
					}
				}
				if ( $excludeHit ) {
					continue;
				}

				// Skip uploads unless explicitly included
				if ( $isWp && ! $uploadsOk ) {
					if ( str_starts_with( $rel, $wpUploadsRel ) ) {
						continue;
					}
				}
			}

			yield [
				'path'  => $path,
				'rel'   => $rel,
				'isDir' => $info->isDir(),
			];
		}
	};

	// Ensure destination directory exists and is writable
	$dstDir = dirname( $zipAbs );
	if ( ! is_dir( $dstDir ) && ! @mkdir( $dstDir, 0775, true ) ) {
		$error = 'Cannot create destination directory: ' . $dstDir;

		return false;
	}
	if ( ! is_writable( $dstDir ) ) {
		$error = 'Destination directory is not writable: ' . $dstDir;

		return false;
	}

	// Preferred: ZIP via ZipArchive
	if ( class_exists( 'ZipArchive' ) ) {
		$zip      = new ZipArchive();
		$openCode = $zip->open( $zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		if ( $openCode !== true ) {
			$error = 'ZipArchive open failed with code ' . (string) $openCode . ' for ' . $zipAbs;

			return false;
		}

		$added = 0;
		foreach ( $iter() as $entry ) {
			$rel = str_replace( '\\', '/', $entry['rel'] );
			if ( $entry['isDir'] ) {
				$zip->addEmptyDir( rtrim( $rel, '/' ) );
			} else {
				$zip->addFile( $entry['path'], $rel );
			}
			$added ++;
		}
		$zip->close();

		if ( ! is_file( $zipAbs ) ) {
			$error = 'ZIP was not created (no files were added).';

			return false;
		}

		return true;
	}

	// Fallback: TAR(.GZ) via PharData
	if ( ! extension_loaded( 'phar' ) ) {
		$error = 'Neither ZipArchive nor Phar are available.';

		return false;
	}

	// Robust readonly check: accepts "0/1" or "Off/On"
	$roRaw = ini_get( 'phar.readonly' );
	$roVal = filter_var( $roRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	if ( $roVal === true ) {
		$error = 'Phar fallback blocked: set phar.readonly=0 in php.ini.';

		return false;
	}

	$wantGzip = extension_loaded( 'zlib' ); // gzip only if zlib exists
	$tarAbs   = preg_replace( '/\.zip$/i', '.tar', $zipAbs );
	$tgzAbs   = preg_replace( '/\.zip$/i', '.tar.gz', $zipAbs );

	try {
		if ( file_exists( $tarAbs ) ) {
			@unlink( $tarAbs );
		}
		if ( file_exists( $tgzAbs ) ) {
			@unlink( $tgzAbs );
		}

		$tar   = new PharData( $tarAbs );
		$added = 0;

		foreach ( $iter() as $entry ) {
			$rel = str_replace( '\\', '/', $entry['rel'] ); // tar needs forward slashes
			if ( $entry['isDir'] ) {
				$tar->addEmptyDir( rtrim( $rel, '/' ) );
			} else {
				$tar->addFile( $entry['path'], $rel );
			}
			$added ++;
		}

		if ( $wantGzip ) {
			$tar->compress( Phar::GZ );     // creates .tar.gz alongside .tar
			unset( $tar );
			@unlink( $tarAbs );              // keep only .tar.gz
			if ( ! is_file( $tgzAbs ) ) {
				$error = 'TAR.GZ was not created (unknown reason).';

				return false;
			}

			return true;
		}

		unset( $tar );                      // plain .tar outcome
		if ( ! is_file( $tarAbs ) ) {
			$error = 'TAR was not created (unknown reason).';

			return false;
		}

		return true;
	} catch ( Throwable $e ) {
		$error = 'Phar error: ' . $e->getMessage();

		return false;
	}
}

/**
 * Dump a MySQL database to SQL text (schema + data).
 *
 * Design:
 * - Uses its **own** mysqli connection (no multi-statements).
 * - For each table:
 *   - Writes `DROP TABLE` + `SHOW CREATE TABLE` DDL.
 *   - Streams `SELECT *` rows unbuffered (MYSQLI_USE_RESULT) into batched multi-value INSERTs.
 * - Avoids the classic “Commands out of sync” by:
 *   - Fetching **SHOW COLUMNS** BEFORE opening the unbuffered SELECT.
 *   - NEVER issuing another query until the active unbuffered result is fully drained and freed.
 *
 * Safety & formatting:
 * - Identifiers are backticked and escaped (`` => ```).
 * - Values are SQL-escaped via `$mysqli->real_escape_string()`. NULLs preserved.
 * - Batch size defaults to 200 rows per INSERT (edit inline if you need).
 *
 * Performance notes:
 * - Unbuffered reads keep memory stable on large tables.
 * - If tables are tiny or RAM is plentiful, you may switch to buffered SELECTs.
 *
 * Limitations:
 * - Only dumps BASE TABLEs (no views, routines, triggers). Extend as needed.
 * - Does not wrap output in transactions. Add if your restore workflow expects it.
 *
 * @param string $host MySQL host (may include port via host:port or use default).
 * @param string $user MySQL username.
 * @param string $pass MySQL password.
 * @param string $dbName Database name to dump.
 * @param null|string $charset Connection charset (default 'utf8mb4'); set null to skip.
 *
 * @return string  Complete SQL dump.
 *
 * @throws RuntimeException if the mysqli init or connect fails.
 */
function export_dump_mysql_database( string $host, string $user, string $pass, string $dbName, ?string $charset = 'utf8mb4' ): string {
	$mysqli = mysqli_init();
	if ( ! $mysqli ) {
		throw new RuntimeException( 'mysqli_init() failed' );
	}
	// IMPORTANT: no CLIENT_MULTI_STATEMENTS flags
	if ( ! $mysqli->real_connect( $host, $user, $pass, $dbName, 0, null, 0 ) ) {
		throw new RuntimeException( 'MySQL connect error: ' . mysqli_connect_error() );
	}
	if ( $charset ) {
		$mysqli->set_charset( $charset );
	}

	$e = static function ( string $s ) {
		return '`' . str_replace( '`', '``', $s ) . '`';
	};

	$out = "-- Dump of database {$e($dbName)}\n";
	$out .= "-- Generated: " . date( 'Y-m-d H:i:s' ) . "\n\n";
	$out .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
	$out .= "SET time_zone = '+00:00';\n";
	$out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

	// Tables (BASE TABLEs only)
	$tables = [];
	if ( $res = $mysqli->query( "SHOW FULL TABLES WHERE Table_type='BASE TABLE'" ) ) {
		while ( $row = $res->fetch_row() ) {
			$tables[] = (string) $row[0];
		}
		$res->free();
	}

	foreach ( $tables as $table ) {
		// Schema (DDL)
		if ( $res = $mysqli->query( "SHOW CREATE TABLE " . $e( $table ) ) ) {
			$row    = $res->fetch_array( MYSQLI_NUM );
			$create = $row[1] ?? '';
			$res->free();
		} else {
			$out .= "-- ERROR: SHOW CREATE TABLE failed for {$e($table)}: " . $mysqli->error . "\n\n";
			continue;
		}

		$out .= "DROP TABLE IF EXISTS " . $e( $table ) . ";\n";
		$out .= $create . ";\n\n";

		// Columns FIRST (buffered), then start unbuffered data stream
		$columns = [];
		if ( $colsRes = $mysqli->query( "SHOW COLUMNS FROM " . $e( $table ) ) ) {
			while ( $f = $colsRes->fetch_assoc() ) {
				$columns[] = $f['Field'];
			}
			$colsRes->free();
		}

		// Data (unbuffered)
		$res = $mysqli->query( "SELECT * FROM " . $e( $table ), MYSQLI_USE_RESULT );
		if ( ! $res ) {
			$out .= "-- ERROR: SELECT * failed for {$e($table)}: " . $mysqli->error . "\n\n";
			continue;
		}

		$batchSize = 200;
		$values    = [];
		$rowCount  = 0;

		$flush = function () use ( &$out, $table, &$values, $columns, $e ) {
			if ( ! $values ) {
				return;
			}
			$colsSql = $columns ? ( " (" . implode( ',', array_map( $e, $columns ) ) . ")" ) : '';
			$out     .= "INSERT INTO " . $e( $table ) . $colsSql . " VALUES\n" . implode( ",\n", $values ) . ";\n";
			$values  = [];
		};

		// Stream rows (no other queries until we free() this)
		while ( $row = $res->fetch_assoc() ) {
			if ( ! $columns ) {
				$columns = array_keys( $row );
			} // fallback if SHOW COLUMNS failed
			$vals = [];
			foreach ( $columns as $col ) {
				$v      = $row[ $col ] ?? null;
				$vals[] = $v === null ? "NULL" : "'" . $mysqli->real_escape_string( $v ) . "'";
			}
			$values[] = '(' . implode( ',', $vals ) . ')';
			if ( count( $values ) >= $batchSize ) {
				$flush();
			}
			$rowCount ++;
		}
		$res->free(); // CRUCIAL: free the unbuffered result before any next query

		if ( $values ) {
			$flush();
		}
		if ( $rowCount > 0 ) {
			$out .= "\n";
		}
	}

	$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
	$mysqli->close();

	return $out;
}

/**
 * List databases available to the configured user (excludes system schemas).
 *
 * @param string $host
 * @param string $user
 * @param string $pass
 *
 * @return string[] Database names (sorted natural, case-insensitive).
 */
function export_list_databases( string $host, string $user, string $pass ): array {
	$mysqli = @new mysqli( $host, $user, $pass );
	if ( $mysqli->connect_error ) {
		return [];
	}
	$mysqli->set_charset( 'utf8mb4' );

	$out = [];
	if ( $res = $mysqli->query( 'SHOW DATABASES' ) ) {
		while ( $row = $res->fetch_array( MYSQLI_NUM ) ) {
			$name = (string) $row[0];
			// Hide system schemas
			if ( in_array( $name, [ 'information_schema', 'performance_schema', 'mysql', 'sys' ], true ) ) {
				continue;
			}
			$out[] = $name;
		}
		$res->free();
	}

	$mysqli->close();
	sort( $out, SORT_NATURAL | SORT_FLAG_CASE );

	return $out;
}

/**
 * Map ZipArchive::open() error codes to readable messages.
 *
 * Intended for diagnostics when creating ZIPs:
 *   $code = $zip->open($path, ZipArchive::CREATE|ZipArchive::OVERWRITE);
 *   if ($code !== true) { echo export_zip_err($code); }
 *
 * Notes:
 * - Covers the common ER_* constants; unknown codes return "Unknown error".
 * - Messages are deliberately brief for UI/tooltips; expand if needed.
 *
 * @param int $code ZipArchive error/status code (e.g., ZipArchive::ER_OPEN).
 *
 * @return string Human-friendly description for the given error code.
 */
function export_zip_err( int $code ): string {
	switch ( $code ) {
		case ZipArchive::ER_EXISTS:
			return 'File already exists';
		case ZipArchive::ER_INCONS:
			return 'Archive inconsistent';
		case ZipArchive::ER_INVAL:
			return 'Invalid argument';
		case ZipArchive::ER_MEMORY:
			return 'Out of memory';
		case ZipArchive::ER_NOENT:
			return 'No such file';
		case ZipArchive::ER_NOZIP:
			return 'Not a zip archive';
		case ZipArchive::ER_OPEN:
			return 'Cannot open file';
		case ZipArchive::ER_READ:
			return 'Read error';
		case ZipArchive::ER_SEEK:
			return 'Seek error';
		default:
			return 'Unknown error';
	}
}

?>
