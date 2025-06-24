<?php
/**
 * Settings Panel Interface
 *
 * Renders a multi-section settings dashboard for user configuration of:
 * - Environment paths (Apache, PHP, HTDocs)
 * - Database credentials (host, user, password)
 * - UI feature toggles (clock, search, stats, error log)
 * - PHP error handling options (display, log, level)
 * - Folder column layout and filtering
 * - Link templates for folder rendering
 * - Dock item management (order, labels, links)
 * - Apache control (restart support if `toggle_apache.php` is present)
 * - vHosts manager integration
 * - Resetting saved UI settings from localStorage
 *
 * Accessibility & UX Enhancements:
 * - Tooltips provided via `renderTooltip()` using keys from `$tooltips` with fallback support
 * - Dynamic theme metadata (`$themeTypes`, `$themeOptions`) injected into JS context
 *
 * Dependencies:
 * - `config.php` (theme detection, display flags, path constants, tooltip data)
 * - `security.php` (access control)
 * - `vhosts.php` (virtual host listing)
 *
 * Outputs:
 * - Dynamic HTML form with grouped setting panels
 * - JavaScript values for theme interaction
 * - Inline server path validation (✔️ / ❌)
 *
 * Author: Pav
 * License: MIT
 * Version: 2.0
 */

/** @var bool $apacheFastMode */
/** @var bool $mysqlFastMode */
/** @var bool $displayClock */
/** @var bool $displaySearch */
/** @var bool $displayApacheErrorLog */
/** @var bool $displayPhpErrorLog */
/** @var bool $displaySystemStats */
/** @var bool $useAjaxForStats */
/** @var bool $apacheToggle */
/** @var bool $apachePathValid */
/** @var bool $htdocsPathValid */
/** @var bool $phpPathValid */
/** @var array $themeTypes */
/** @var array $tooltips */
/** @var array $themeOptions */
/** @var string $currentTheme */
/** @var string $defaultTooltipMessage */
/** @var string $dbUser */
/** @var string $dbPass */
/** @var string $currentErrorLevel */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';
?>

<script>
	const themeTypes = <?= json_encode( $themeTypes ) ?>;
	const serverTheme = <?= json_encode( $currentTheme ) ?>;
</script>

<div id="settings-view">
	<!-- User Settings -->
	<?= renderTooltip( 'user_settings', $tooltips, $defaultTooltipMessage, 'h2', 'User Configuration' ) ?>
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
		</label>

		<label>
			<input type="checkbox"
			       name="apacheFastMode" <?= isset( $apacheFastMode ) && $apacheFastMode ? 'checked' : '' ?>>
			Fast Mode for Apache Inspector
		</label>

		<label>
			<input type="checkbox"
			       name="mysqlFastMode" <?= isset( $mysqlFastMode ) && $mysqlFastMode ? 'checked' : '' ?>>
			Fast Mode for MySQL Inspector
		</label><br>

		<?= renderTooltip( 'user_interface', $tooltips, $defaultTooltipMessage, 'h3', 'User Interface' ) ?>
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

			<label>Display PHP Error Log:
				<input type="checkbox" name="displayPhpErrorLog" <?= $displayPhpErrorLog ? 'checked' : '' ?>>
			</label>

			<label>Use AJAX for Stats and Error log:
				<input type="checkbox" name="useAjaxForStats" <?= $useAjaxForStats ? 'checked' : '' ?>>
			</label>
		</div>
		<br>

		<?= renderTooltip( 'php_error', $tooltips, $defaultTooltipMessage, 'h3', 'PHP Error Handling & Logging' ) ?>
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

		<?= renderTooltip( 'folders', $tooltips, $defaultTooltipMessage, 'h3', 'Folders Configuration' ) ?><br>
		<div id="folders-config">
			<ul id="folders-config-list" class="draggable-list"></ul>
			<button type="button" id="add-folder-column">➕ Add Column</button>
		</div>
		<input type="hidden" id="folders_json_input" name="folders_json">
		<br>

		<?= renderTooltip( 'link_templates', $tooltips, $defaultTooltipMessage, 'h3', 'Folder Link Templates' ) ?>
		<div id="link-templates-config">
			<ul id="link-templates-list" class="template-list"></ul>
			<button type="button" id="add-link-template">➕ Add Link Template</button>
		</div>
		<input type="hidden" id="link_templates_json_input" name="link_templates" value="">
		<br>

		<button type="submit">Save Settings</button>

		<br><br>

		<?= renderTooltip( 'dock', $tooltips, $defaultTooltipMessage, 'h3', 'Dock Configuration' ) ?>
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
		<?= renderTooltip( 'apache_control', $tooltips, $defaultTooltipMessage, 'h2', 'Apache Control' ) ?>
		<?php if ( $apacheToggle && $apachePathValid ): ?>
			<button id="restart-apache-button">Restart Apache</button>
			<div id="apache-status-message" role="status" aria-live="polite"></div>
		<?php else: ?>
			<p>Apache control unavailable<?= ! $apachePathValid ? ' (invalid Apache path)' : ' (toggle_apache.php missing)' ?></p><br>
			<button disabled id="restart-apache-button">Restart Apache</button>
		<?php endif; ?>
	</div>

	<!-- vHosts Manager -->
	<?php require_once __DIR__ . '/../partials/vhosts.php'; ?>

	<div id="clear-settings-wrapper">
		<?= renderTooltip( 'clear_storage', $tooltips, $defaultTooltipMessage, 'h2', 'Reset Settings' ) ?>
		<button id="clear-local-storage" class="button warning">🧹 Clear Local Storage</button>
	</div>
</div>
