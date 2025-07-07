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
 * - Tooltips provided via `renderHeadingTooltip()` using keys from `$tooltips` with fallback support
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
/** @var string $currentPhpErrorLevel */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';
?>

<script>
	const themeTypes = <?= json_encode( $themeTypes ) ?>;
	const serverTheme = <?= json_encode( $currentTheme ) ?>;
</script>

<div id="settings-view">
	<!-- User Settings -->
	<div class="heading">
		<?= renderHeadingTooltip( 'user_config', $tooltips, $defaultTooltipMessage, 'h2', 'User Configuration' ) ?>
	</div>
	<?php if ( defined( 'DEMO_MODE' ) && DEMO_MODE ): ?>
		<div class="demo-mode" role="alert">
			<p><strong>Demo Mode:</strong> Saving is disabled and credentials are obfuscated in this environment.</p><br>
		</div>
	<?php endif; ?>
	<form method="post">
		<div class="toggle-content-container" data-id="user-settings">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'user_settings', $tooltips, $defaultTooltipMessage, 'h3', 'Database & Paths' ) ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
				<div class="background-logos">
					<?php echo injectSvgWithUniqueIds( __DIR__ . '/../assets/images/Apache.svg', 'Apache2' ); ?>
					<?php echo injectSvgWithUniqueIds( __DIR__ . '/../assets/images/MariaDB.svg', 'MariaDB1' ); ?>
					<?php echo injectSvgWithUniqueIds( __DIR__ . '/../assets/images/PHP.svg', 'PHP2' ); ?>
				</div>
				<div class="user-settings">
					<label>DB Host:&nbsp;
						<input type="text" name="DB_HOST" value="<?= obfuscate_value( DB_HOST ) ?>">
					</label>
					<label>DB User:&nbsp;
						<input type="text" name="DB_USER" value="<?= obfuscate_value( htmlspecialchars( $dbUser ) ) ?>">
					</label>
					<label>DB Password:&nbsp;
						<input type="password" name="DB_PASSWORD" value="<?= obfuscate_value( htmlspecialchars( $dbPass ) ) ?>">
					</label>

					<label>Apache Path:&nbsp;
						<input type="text" name="APACHE_PATH" value="<?= obfuscate_value( APACHE_PATH ) ?>">
						<?= $apachePathValid ? '✔️' : '❌' ?>
					</label>

					<label>HTDocs Path:&nbsp;
						<input type="text" name="HTDOCS_PATH" value="<?= obfuscate_value( HTDOCS_PATH ) ?>">
						<?= $htdocsPathValid ? '✔️' : '❌' ?>
					</label>

					<label>PHP Path:&nbsp;
						<input type="text" name="PHP_PATH" value="<?= obfuscate_value( PHP_PATH ) ?>">
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

					<button type="submit">Save Settings</button>
					<br><br>
				</div>
			</div>
		</div>

		<?= renderSeparatorLine() ?>

		<div class="toggle-content-container" data-id="user-interface">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'user_interface', $tooltips, $defaultTooltipMessage, 'h3', 'User Interface' ) ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
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
					</label><br>
				</div>
				<br>
				<button type="submit">Save Settings</button>
				<br><br>
			</div>
		</div>

		<?= renderSeparatorLine() ?>

		<div class="toggle-content-container<?= ! $phpPathValid ? ' disabled' : '' ?>" data-id="php-error">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'php_error', $tooltips, $defaultTooltipMessage, 'h3', 'PHP Error Handling & Logging' ) ?>
				<?= $phpPathValid ? '' : '&nbsp;❕' ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
				<?php if ( ! $phpPathValid ): ?>
					<p><strong>Note:</strong> PHP Error Handling & Logging will save to <code>user_config.php</code> but will not be reflected in <code>php.ini</code> (invalid PHP path).</p><br>
				<?php endif; ?>
				<label>Display Errors:
					<input type="checkbox" name="displayPhpErrors" <?= ini_get( 'display_errors' ) ? 'checked' : '' ?>>
				</label>

				<label>Error Reporting Level:
					<?php
						$phpErrorLevels = [
							E_ALL     => 'E_ALL',
							E_ERROR   => 'E_ERROR',
							E_WARNING => 'E_WARNING',
							E_NOTICE  => 'E_NOTICE'
						];
					?>
					<select name="phpErrorLevel">
						<?php foreach ( $phpErrorLevels as $value => $label ) : ?>
							<option value="<?= $label ?>" <?= $currentPhpErrorLevel == $value ? 'selected' : '' ?>><?= $label ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label>Log Errors:
					<input type="checkbox" name="logPhpErrors" <?= ini_get( 'log_errors' ) ? 'checked' : '' ?>>
				</label><br>

				<button type="submit">Save Settings</button>
				<br><br>
			</div>
		</div>

		<?= renderSeparatorLine() ?>

		<div class="toggle-content-container" data-id="folders-config">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'folders', $tooltips, $defaultTooltipMessage, 'h3', 'Folders Configuration' ) ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
				<div id="folders-config">
					<ul id="folders-config-list" class="draggable-list"></ul>
					<button type="button" id="add-folder-column">➕ Add Column</button>
				</div>
				<input type="hidden" id="folders_json_input" name="folders_json">
				<br>
				<button type="submit">Save Settings</button>
				<br><br>
			</div>
		</div>

		<?= renderSeparatorLine() ?>

		<div class="toggle-content-container" data-id="link-templates-config">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'link_templates', $tooltips, $defaultTooltipMessage, 'h3', 'Folder Link Templates' ) ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
				<div id="link-templates-config">
					<ul id="link-templates-list" class="template-list"></ul>
					<button type="button" id="add-link-template">➕ Add Link Template</button>
				</div>
				<input type="hidden" id="link_templates_json_input" name="link_templates" value="">
				<br>

				<button type="submit">Save Settings</button>
				<br><br>
			</div>
		</div>

		<?= renderSeparatorLine() ?>

		<div class="toggle-content-container" data-id="dock-config">
			<div class="toggle-accordion">
				<?= renderHeadingTooltip( 'dock', $tooltips, $defaultTooltipMessage, 'h3', 'Dock Configuration' ) ?>
				<?php echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
			</div>
			<div class="toggle-content">
				<div id="dock-config-editor">
					<ul id="dock-list"></ul>
					<button type="button" id="add-dock-item">➕ Add Item</button>
				</div>
				<input type="hidden" id="dock_json_input" name="dock_json">
				<br>

				<button type="submit">Save Settings</button>
				<br><br>
			</div>
		</div>
	</form>

	<?= renderSeparatorLine() ?>

	<!-- vHosts Manager -->
	<div class="toggle-content-container <?= ! $apachePathValid ? ' disabled' : '' ?>" data-id="vhosts-manager">
		<div class="toggle-accordion">
			<?= renderHeadingTooltip( 'vhosts_manager', $tooltips, $defaultTooltipMessage, 'h3', 'Virtual Hosts Manager' ) ?>
			<?= $apachePathValid ? '' : '&nbsp;❕' ?>
			<?php $settingsView = true; echo file_get_contents( __DIR__ . '/../assets/images/caret-down.svg' ); ?>
		</div>
		<div class="toggle-content">
			<?php require_once __DIR__ . '/../partials/vhosts.php'; ?>
		</div>
	</div>

	<?= renderSeparatorLine() ?>

	<!-- Apache Control -->
	<div class="apache-control">
		<div class="heading">
			<?= renderHeadingTooltip( 'apache_control', $tooltips, $defaultTooltipMessage, 'h2', 'Apache Control' ) ?>
		</div>
		<?php if ( $apacheToggle && $apachePathValid ): ?>
			<button id="restart-apache-button">Restart Apache</button>
			<div id="apache-status-message" role="status" aria-live="polite"></div>
		<?php else: ?>
			<p>Apache control
				unavailable<?= ! $apachePathValid ? ' (invalid Apache path)' : ' (toggle_apache.php missing)' ?></p><br>
			<button disabled id="restart-apache-button">Restart Apache</button>
		<?php endif; ?>
	</div>

	<div id="clear-settings-wrapper">
		<div class="heading">
			<?= renderHeadingTooltip( 'clear_storage', $tooltips, $defaultTooltipMessage, 'h2', 'Reset Settings' ) ?>
		</div>
		<button id="clear-local-storage" class="button warning">🧹 Clear Local Storage</button>
	</div>
</div>
