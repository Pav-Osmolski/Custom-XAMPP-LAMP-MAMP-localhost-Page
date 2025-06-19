<?php
/**
 * Settings Panel Renderer
 *
 * Displays a comprehensive settings interface for configuring:
 * - Database credentials (host, user, password)
 * - Paths for Apache, PHP, and HTDocs
 * - UI features (clock, search, stats, error log)
 * - PHP error reporting and logging options
 * - Custom folder column layout and filters
 * - Folder link templates for URL rendering
 * - Dock item configuration
 * - Apache restart integration (if `toggle_apache.php` exists)
 *
 * Tooltips are dynamically populated from the `$tooltips` array for accessibility.
 * Also includes a vHosts manager panel and local storage reset button.
 *
 * Dependencies:
 * - `security.php` for access control
 * - `config.php` for constants and path definitions
 * - `vhosts.php` for embedded virtual host listing
 *
 * Output:
 * - Full HTML form and dynamic JavaScript UI hooks for settings control
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

/** @var bool $displayClock */
/** @var bool $displaySearch */
/** @var bool $displayApacheErrorLog */
/** @var bool $displaySystemStats */
/** @var bool $useAjaxForStats */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

$apachePathValid = file_exists( APACHE_PATH );
$htdocsPathValid = file_exists( HTDOCS_PATH );
$phpPathValid    = file_exists( PHP_PATH );
$apacheToggle    = file_exists( __DIR__ . '/../utils/toggle_apache.php' );

$dbUser = getDecrypted( 'DB_USER' );
$dbPass = getDecrypted( 'DB_PASSWORD' );

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

$currentTheme      = $theme ?? 'default';
$currentErrorLevel = ini_get( 'error_reporting' );

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
?>

<script>
	const themeTypes = <?= json_encode( $themeTypes ) ?>;
	const serverTheme = <?= json_encode( $currentTheme ) ?>;
</script>

<div id="settings-view">
	<!-- User Settings -->
	<h2>User Settings Configuration <span class="tooltip-icon" aria-describedby="tooltip-user_settings" tabindex="0"
	                                      data-tooltip="<?= getTooltip( 'user_settings', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
		</span></h2>
	<span id="tooltip-user_settings" class="sr-only"
	      role="tooltip"><?= getTooltip( 'user_settings', $tooltips, $defaultTooltipMessage ) ?></span>
	<form method="post">
		<label>DB Host:&nbsp;<input type="text" name="DB_HOST" value="<?= DB_HOST ?>"></label>
		<label>DB User:&nbsp;<input type="text" name="DB_USER" value="<?= htmlspecialchars( $dbUser ) ?>"></label>
		<label>DB Password:&nbsp;<input type="password" name="DB_PASSWORD"
		                                value="<?= htmlspecialchars( $dbPass ) ?>"></label>

		<label>Apache Path:&nbsp;
			<input type="text" name="APACHE_PATH" value="<?= APACHE_PATH ?>">
			<?= $apachePathValid ? '✔️' : '❌' ?>
		</label>

		<label>HTDocs Path:&nbsp;
			<input type="text" name="HTDOCS_PATH" value="<?= HTDOCS_PATH ?>">
			<?= $htdocsPathValid ? '✔️' : '❌' ?>
		</label>

		<label>PHP Path:&nbsp;
			<input type="text" name="PHP_PATH" value="<?= PHP_PATH ?>">
			<?= $phpPathValid ? '✔️' : '❌' ?>
		</label><br>

		<h3>User Interface <span class="tooltip-icon" aria-describedby="tooltip-user_interface" tabindex="0"
		                                data-tooltip="<?= getTooltip( 'user_interface', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-user_interface" class="sr-only"
		      role="tooltip"><?= getTooltip( 'user_interface', $tooltips, $defaultTooltipMessage ) ?></span>
		<div class="ui-features">
			<label class="select">Theme:
				<select id="theme-selector" name="theme" aria-label="Select Theme">
					<?php foreach ( $themeOptions as $id => $label ) : ?>
						<option value="<?= $id ?>" <?= $currentTheme === $id ? 'selected="selected"' : '' ?>>
							<?= htmlspecialchars( $label ) ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>Display Clock:
				<input type="checkbox" name="displayClock" <?= $displayClock ? 'checked' : '' ?>>
			</label>

			<label>Display Search:
				<input type="checkbox" name="displaySearch" <?= $displaySearch ? 'checked' : '' ?>>
			</label>

			<label>Display System Stats:
				<input type="checkbox" name="displaySystemStats" <?= $displaySystemStats ? 'checked' : '' ?>>
			</label>

			<label>Display Apache Error Log:
				<input type="checkbox" name="displayApacheErrorLog" <?= $displayApacheErrorLog ? 'checked' : '' ?>>
			</label>

			<label>Use AJAX for Stats and Error log:
				<input type="checkbox" name="useAjaxForStats" <?= $useAjaxForStats ? 'checked' : '' ?>>
			</label>
		</div><br>

		<h3>PHP Error Handling & Logging
			<span class="tooltip-icon" aria-describedby="tooltip-php_error" tabindex="0"
			      data-tooltip="<?= getTooltip( 'php_error', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-php_error" class="sr-only"
		      role="tooltip"><?= getTooltip( 'php_error', $tooltips, $defaultTooltipMessage ) ?></span>

		<label>Display Errors:
			<input type="checkbox" name="displayErrors" <?= ini_get( 'display_errors' ) ? 'checked' : '' ?>>
		</label>

		<label>Error Reporting Level:
			<select name="errorReportingLevel">
				<option value="E_ALL" <?= $currentErrorLevel == E_ALL ? 'selected' : '' ?>>E_ALL</option>
				<option value="E_ERROR" <?= $currentErrorLevel == E_ERROR ? 'selected' : '' ?>>E_ERROR</option>
				<option value="E_WARNING" <?= $currentErrorLevel == E_WARNING ? 'selected' : '' ?>>E_WARNING</option>
				<option value="E_NOTICE" <?= $currentErrorLevel == E_NOTICE ? 'selected' : '' ?>>E_NOTICE</option>
			</select>
		</label>

		<label>Log Errors:
			<input type="checkbox" name="logErrors" <?= ini_get( 'log_errors' ) ? 'checked' : '' ?>>
		</label><br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Folders Configuration <span class="tooltip-icon" aria-describedby="tooltip-folders" tabindex="0"
		                                data-tooltip="<?= getTooltip( 'folders', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-folders" class="sr-only" role="tooltip"><?= getTooltip( 'folders', $tooltips, $defaultTooltipMessage ) ?></span><br>

		<div id="folders-config">
			<ul id="folders-config-list" class="draggable-list"></ul>
			<button type="button" id="add-folder-column">➕ Add Column</button>
		</div>
		<input type="hidden" id="folders_json_input" name="folders_json">
		<br>

		<h3>Folder Link Templates <span class="tooltip-icon" aria-describedby="tooltip-link_templates" tabindex="0"
		                                data-tooltip="<?= getTooltip( 'link_templates', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-link_templates" class="sr-only"
		      role="tooltip"><?= getTooltip( 'link_templates', $tooltips, $defaultTooltipMessage ) ?></span>

		<div id="link-templates-config">
			<ul id="link-templates-list" class="template-list"></ul>
			<button type="button" id="add-link-template">➕ Add Link Template</button>
		</div>
		<input type="hidden" id="link_templates_json_input" name="link_templates" value="">
		<br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Dock Configuration <span class="tooltip-icon" aria-describedby="tooltip-dock" tabindex="0"
		                             data-tooltip="<?= getTooltip( 'dock', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-dock" class="sr-only" role="tooltip"><?= getTooltip( 'dock', $tooltips, $defaultTooltipMessage ) ?></span>

		<div id="dock-config-editor">
			<ul id="dock-list"></ul>
			<button type="button" id="add-dock-item">➕ Add Item</button>
		</div>
		<input type="hidden" id="dock_json_input" name="dock_json">
		<br>

		<button type="submit">Save Settings</button>
	</form>
	<br>

	<!-- Apache Control -->
	<div class="apache-control">
		<h2>Apache Control <span class="tooltip-icon" aria-describedby="tooltip-apache_control" tabindex="0"
		                         data-tooltip="<?= getTooltip( 'apache_control', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span></h2>
		<span id="tooltip-apache_control" class="sr-only"
		      role="tooltip"><?= getTooltip( 'apache_control', $tooltips, $defaultTooltipMessage ) ?></span>
		<?php if ( $apacheToggle ): ?>
			<button id="restart-apache-button">Restart Apache</button>
			<div id="apache-status-message" role="status" aria-live="polite"></div>
		<?php else: ?>
			<p>Apache control unavailable (toggle_apache.php missing)</p><br>
			<button disabled id="restart-apache-button">Restart Apache</button>
		<?php endif; ?>
	</div>

	<!-- vHosts Manager -->
	<?php require_once __DIR__ . '/../partials/vhosts.php'; ?>

	<div id="clear-settings-wrapper">
		<button id="clear-local-storage" class="button warning">🧹 Clear Local Storage</button>
		<span class="tooltip-icon" aria-describedby="tooltip-clear_storage" tabindex="0"
		      data-tooltip="<?= getTooltip( 'clear_storage', $tooltips, $defaultTooltipMessage ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
		</span>
		<span id="tooltip-clear_storage" class="sr-only"
		      role="tooltip"><?= getTooltip( 'clear_storage', $tooltips, $defaultTooltipMessage ) ?></span>
	</div>
</div>
