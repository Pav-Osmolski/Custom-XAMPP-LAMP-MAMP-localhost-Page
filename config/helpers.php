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
 * @version 1.1
 */

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
 * Generate dynamic <body> class string from flags and theme.
 *
 * @return string
 */
function buildBodyClasses( $theme, $displayClock, $displaySearch, $displaySystemStats, $displayApacheErrorLog ): string {
    $classes = [ 'background-image' ];
    $classes[] = $displayClock ? 'clock-active' : 'clock-inactive';
    $classes[] = $displaySearch ? 'search-active' : 'search-inactive';

    if ( file_exists( __DIR__ . '/../utils/system_stats.php' ) && $displaySystemStats ) {
        $classes[] = 'system-monitor-active';
    } else {
        $classes[] = 'system-monitor-inactive';
    }

    if ( file_exists( __DIR__ . '/../utils/apache_error_log.php' ) && $displayApacheErrorLog ) {
        $classes[] = 'error-log-section-active';
    } else {
        $classes[] = 'error-log-section-inactive';
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
 * @return string
 */
function getTooltip( string $key, array $tooltips, string $default ): string {
    return array_key_exists( $key, $tooltips )
        ? htmlspecialchars( $tooltips[ $key ] )
        : htmlspecialchars( $default . " (Missing tooltip key: $key)" );
}

/**
 * Render a labelled heading with tooltip.
 *
 * @param string $key
 * @param array<string, string> $tooltips
 * @param string $defaultTooltipMessage
 * @param string $headingTag
 * @param string $label
 * @return string
 */
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
function renderServerInfo( $dbUser, $dbPass ) {
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
        echo "<span class='php-info'>PHP: <a href='#' id='toggle-phpinfo'>{$phpVersion} {$isThreadSafe} {$isFastCGI}</a> ✔️</span>";
    }

    // Check MySQL version
    try {
        $mysqli = new mysqli( DB_HOST, $dbUser, $dbPass );

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
