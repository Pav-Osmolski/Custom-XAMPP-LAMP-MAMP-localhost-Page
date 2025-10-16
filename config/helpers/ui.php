<?php
/**
 * UI helpers
 */

/**
 * Generate dynamic <body> class string based on UI settings and theme.
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

	if ( file_exists( __DIR__ . '/../../utils/system_stats.php' ) && $displaySystemStats ) {
		$classes[] = 'system-monitor-active';
	} else {
		$classes[] = 'system-monitor-inactive';
	}

	$apacheLogAvailable = file_exists( __DIR__ . '/../../utils/apache_error_log.php' );
	$phpLogAvailable    = file_exists( __DIR__ . '/../../utils/php_error_log.php' );

	$classes[] = ( $apacheLogAvailable && $displayApacheErrorLog ) ? 'apache-error-log-active' : 'apache-error-log-inactive';
	$classes[] = ( $phpLogAvailable && $displayPhpErrorLog ) ? 'php-error-log-active' : 'php-error-log-inactive';

	if ( ( $apacheLogAvailable && $displayApacheErrorLog ) || ( $phpLogAvailable && $displayPhpErrorLog ) ) {
		$classes[] = 'error-log-active';
	}

	$themeFile = __DIR__ . '/../../assets/scss/themes/_' . $theme . '.scss';
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
		      data-tooltip="<?= $desc ?>"><?php include __DIR__ . '/../../assets/images/tooltip-icon.svg'; ?></span>
		<span id="tooltip-<?= $key ?>" class="sr-only" role="tooltip"><?= $desc ?></span>
		<?php
	} elseif ( $headingOnly ) {
		echo "<{$headingTag}>" . htmlspecialchars( $title ) . "</{$headingTag}>";
	} else {
		echo "<{$headingTag}>" . htmlspecialchars( $title ) . "</{$headingTag}>";
		?>
		<span class="tooltip-icon" aria-describedby="tooltip-<?= $key ?>" tabindex="0"
		      data-tooltip="<?= $desc ?>"><?php include __DIR__ . '/../../assets/images/tooltip-icon.svg'; ?></span>
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
		return '';
	}
	preg_match_all( '/id="([^"]+)"/', $svg, $matches );
	if ( empty( $matches[1] ) ) {
		return $svg;
	}
	foreach ( $matches[1] as $originalId ) {
		$newId = $prefix . '-' . $originalId;
		$svg   = preg_replace( '/id="' . preg_quote( $originalId, '/' ) . '"/', 'id="' . $newId . '"', $svg );
		$svg   = preg_replace( '/url\(#' . preg_quote( $originalId, '/' ) . '\)/', 'url(#' . $newId . ')', $svg );
	}

	return $svg;
}

/**
 * Render a visual separator line for UI sections.
 *
 * @param string $extraClass Optional CSS class(es) to append. Defaults to 'medium'.
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
 * Assumes constants APACHE_PATH and DB_HOST are defined.
 *
 * @param string $dbUser The decrypted database username
 * @param string $dbPass The decrypted database password
 *
 * @return void
 */
function renderServerInfo( string $dbUser, string $dbPass ): void {
	$os            = PHP_OS_FAMILY;
	$apacheVersion = '';
	$apacheBin     = '';

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

	if ( empty( $apacheBin ) ) {
		if ( $os === 'Windows' ) {
			$apachePath = trim( (string) ( function_exists( 'safe_shell_exec' ) ? safe_shell_exec( 'where httpd' ) : '' ) );
			if ( ! empty( $apachePath ) && file_exists( $apachePath ) ) {
				$apacheBin = $apachePath;
			}
		} elseif ( $os === 'Darwin' ) {
			$macPaths = [
				'/Applications/MAMP/Library/bin/httpd',
				trim( (string) ( function_exists( 'safe_shell_exec' ) ? safe_shell_exec( 'which httpd' ) : '' ) ),
			];
			foreach ( $macPaths as $path ) {
				if ( ! empty( $path ) && file_exists( $path ) ) {
					$apacheBin = $path;
					break;
				}
			}
		} else {
			$linuxPaths = [
				trim( (string) ( function_exists( 'safe_shell_exec' ) ? safe_shell_exec( 'command -v apachectl 2>/dev/null' ) : '' ) ),
				trim( (string) ( function_exists( 'safe_shell_exec' ) ? safe_shell_exec( 'command -v httpd 2>/dev/null' ) : '' ) ),
			];
			foreach ( $linuxPaths as $path ) {
				if ( ! empty( $path ) && file_exists( $path ) ) {
					$apacheBin = $path;
					break;
				}
			}
		}
	}

	if ( ! empty( $apacheBin ) ) {
		$apacheVersion = function_exists( 'safe_shell_exec' ) ? safe_shell_exec( "$apacheBin -v" ) : '';
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

	$phpVersion = phpversion();
	if ( ! $phpVersion ) {
		echo '<span class="php-unknown-info">PHP: Version unknown ⚠️</span>';
	} else {
		$isThreadSafe = ( ZEND_THREAD_SAFE ) ? 'TS' : 'NTS';
		$isFastCGI    = ( strpos( PHP_SAPI, 'cgi-fcgi' ) !== false ) ? 'FastCGI' : 'Non-FastCGI';
		echo "<span class='php-info'>PHP: <a href='#' id='toggle-phpinfo'>{$phpVersion} {$isThreadSafe} {$isFastCGI}</a> ✔️</span>";
	}

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
 * Render a versioned <script> tag for an asset plus a BASE_URL bootstrap.
 *
 * - Figures out BASE_URL by stripping "/partials" when a partial is hit directly.
 * - Versions the asset with filemtime() (falls back to time()).
 * - Returns the full HTML snippet so you can `echo` it where needed.
 *
 * @param string $assetRel Web-relative asset path (e.g. "dist/js/script.min.js").
 * @param string|null $projectRoot Absolute project root; defaults to dirname(__DIR__).
 * @param string $stripSuffix Suffix to strip from SCRIPT_NAME dir (default "/partials").
 *
 * @return string HTML snippet defining window.BASE_URL and the <script> tag.
 */
function render_versioned_script_with_base( string $assetRel = 'dist/js/script.min.js', ?string $projectRoot = null, string $stripSuffix = '/partials' ): string {
	// 1) Version from absolute path (assumes helpers live in /config or /partials tree)
	$projectRoot = $projectRoot ?: dirname( __DIR__ );
	$assetAbs    = rtrim( $projectRoot, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR
	               . str_replace( [ '/', '\\' ], DIRECTORY_SEPARATOR, $assetRel );
	$ver         = is_file( $assetAbs ) ? filemtime( $assetAbs ) : time();

	// 2) Compute a base URL, stripping /partials when accessed directly
	$scriptName = isset( $_SERVER['SCRIPT_NAME'] ) ? (string) $_SERVER['SCRIPT_NAME'] : '';
	$scriptDir  = rtrim( dirname( $scriptName ), '/\\' ); // e.g. "", "/", "/site", "/site/partials"

	if ( $stripSuffix !== '' && $stripSuffix[0] === '/' && preg_match( '~' . preg_quote( $stripSuffix, '~' ) . '$~', $scriptDir ) ) {
		$baseUrl = rtrim( substr( $scriptDir, 0, - strlen( $stripSuffix ) ), '/' );
	} else {
		$baseUrl = $scriptDir;
	}
	$baseUrl = ( $baseUrl === '' ? '/' : $baseUrl . '/' ); // normalise trailing slash

	$src = $baseUrl . ltrim( $assetRel, '/' );

	// Build HTML (don’t override an existing BASE_URL if the host page already set it)
	$html = '<script>window.BASE_URL = window.BASE_URL || ' . json_encode( $baseUrl ) . ';</script>' . "\n";
	$html .= '<script src="' . htmlspecialchars( $src, ENT_QUOTES, 'UTF-8' ) . '?v=' . (int) $ver . '"></script>';

	return $html;
}
