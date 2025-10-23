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
 * Accessibility and UX Enhancements:
 * - Tabbable, keyboard-operable accordion triggers
 * - ARIA-complete wiring via helper functions
 * - Tooltips via `renderHeadingTooltip()` using keys from `$tooltips` with fallback support
 * - Dynamic theme metadata injected into JS context
 *
 * Dependencies:
 * - `config.php` for helpers, theme detection, display flags, path constants, tooltip data, access control
 * - `partials/vhosts.php` for virtual host listing
 * - `partials/export.php` for export features
 *
 * Outputs:
 * - Dynamic HTML form with grouped setting panels
 * - JavaScript values for theme interaction
 * - Inline server path validation indicators
 *
 * Security Notes:
 * - CSRF token output with `csrf_get_token()`
 * - Sensitive values are obfuscated for display via `obfuscate_value()`
 *
 * @author  Pav
 * @license MIT
 * @version 2.3
 */

/**
 * @var bool $apacheFastMode Fast mode flag for Apache inspector
 * @var bool $mysqlFastMode Fast mode flag for MySQL inspector
 * @var bool $displayHeader UI flag to show header
 * @var bool $displayFooter UI flag to show footer
 * @var bool $displayClock UI flag to show clock
 * @var bool $displaySearch UI flag to show search
 * @var bool $displayApacheErrorLog UI flag to show Apache error log
 * @var bool $displayPhpErrorLog UI flag to show PHP error log
 * @var bool $displaySystemStats UI flag to show system stats
 * @var bool $useAjaxForStats UI flag to fetch stats and logs via AJAX
 * @var bool $apacheToggle True if Apache restart endpoint is available
 * @var bool $apachePathValid Validation state for Apache path
 * @var bool $htdocsPathValid Validation state for HTDocs path
 * @var bool $phpPathValid Validation state for PHP path
 * @var array $themeTypes Theme type metadata for client-side use
 * @var array $tooltips Tooltip copy map
 * @var array $themeOptions Theme options for the select box
 * @var string $currentTheme Active theme key
 * @var string $defaultTooltipMessage Default tooltip fallback message
 * @var string $dbUser Database user for display (obfuscated on output)
 * @var string $dbPass Database password for display (obfuscated on output)
 * @var string $currentPhpErrorLevel Current PHP error reporting level constant value
 */

require_once __DIR__ . '/../config/config.php';
?>

<script>
	const themeTypes = <?= json_encode( $themeTypes ) ?>;
	const serverTheme = <?= json_encode( $currentTheme ) ?>;
</script>

<?php if ( isset( $_GET['saved'] ) ): ?>
	<div class="post-confirmation-container <?= $_GET['saved'] === '1' ? 'success' : 'failure' ?>" role="status"
	     aria-live="polite">
		<div class="post-confirmation-message">
			<?= $_GET['saved'] === '1' ? '✔️ User Settings saved successfully.' : '⚠️ Demo Mode says no! Changes weren’t saved.' ?>
		</div>
	</div>
<?php endif; ?>

<div id="settings-view">
	<!-- User Settings -->
	<div class="heading">
		<?= renderHeadingTooltip( 'user_config', $tooltips, $defaultTooltipMessage, 'h2', 'User Configuration' ) ?>
	</div>

	<?php if ( defined( 'DEMO_MODE' ) && DEMO_MODE ): ?>
		<div class="demo-mode" role="alert">
			<p><strong>Demo Mode:</strong> Saving is disabled and credentials are obfuscated in this environment.</p>
			<br>
		</div>
	<?php endif; ?>

	<form method="post" action="" accept-charset="UTF-8" autocomplete="off">
		<input type="hidden" name="csrf" value="<?= htmlspecialchars( csrf_get_token() ) ?>">

		<?php
		// Database & Paths
		renderAccordionSectionStart(
			'user-settings',
			renderHeadingTooltip( 'user_settings', $tooltips, $defaultTooltipMessage, 'h3', 'Database & Paths' ),
			[
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
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
		<?php renderAccordionSectionEnd(); ?>

		<?php renderSeparatorLine(); ?>

		<?php
		// User Interface
		renderAccordionSectionStart(
			'user-interface',
			renderHeadingTooltip( 'user_interface', $tooltips, $defaultTooltipMessage, 'h3', 'User Interface' ),
			[
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
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

			<label>Display Header:
				<input type="checkbox" name="displayHeader" <?= $displayHeader ? 'checked' : '' ?>>
			</label>

			<label>Display Footer:
				<input type="checkbox" name="displayFooter" <?= $displayFooter ? 'checked' : '' ?>>
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
		<?php renderAccordionSectionEnd(); ?>

		<?php renderSeparatorLine(); ?>

		<?php
		// PHP Error Handling & Logging (disabled when PHP path invalid)
		$phpErrorHeading = renderHeadingTooltip( 'php_error', $tooltips, $defaultTooltipMessage, 'h3', 'PHP Error Handling & Logging' ) . ( $phpPathValid ? '' : ' &nbsp;❕' );

		renderAccordionSectionStart(
			'php-error',
			$phpErrorHeading,
			[
				'disabled'  => ! $phpPathValid,
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
		<?php if ( ! $phpPathValid ): ?>
			<p><strong>Note:</strong> PHP Error Handling & Logging will save to <code>user_config.php</code> but will
				not be reflected in <code>php.ini</code> (invalid PHP path).</p><br>
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
		<?php renderAccordionSectionEnd(); ?>

		<?php renderSeparatorLine(); ?>

		<?php
		// Folders Configuration
		renderAccordionSectionStart(
			'folders-config',
			renderHeadingTooltip( 'folders', $tooltips, $defaultTooltipMessage, 'h3', 'Folders Configuration' ),
			[
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
		<div id="folders-config">
			<ul id="folders-config-list" class="draggable-list"></ul>
			<button type="button" id="add-folder-column">➕ Add Folder Column</button>
		</div>
		<input type="hidden" id="folders_json_input" name="folders_json">
		<br>
		<button type="submit">Save Settings</button>
		<br><br>
		<?php renderAccordionSectionEnd(); ?>

		<?php renderSeparatorLine(); ?>

		<?php
		// Folder Link Templates
		renderAccordionSectionStart(
			'link-templates-config',
			renderHeadingTooltip( 'link_templates', $tooltips, $defaultTooltipMessage, 'h3', 'Folder Link Templates' ),
			[
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
		<div id="link-templates-config">
			<ul id="link-templates-list" class="template-list"></ul>
			<button type="button" id="add-link-template">➕ Add Link Template</button>
		</div>
		<input type="hidden" id="link_templates_json_input" name="link_templates_json" value="">
		<br>
		<button type="submit">Save Settings</button>
		<br><br>
		<?php renderAccordionSectionEnd(); ?>

		<?php renderSeparatorLine(); ?>

		<?php
		// Dock Configuration
		renderAccordionSectionStart(
			'dock-config',
			renderHeadingTooltip( 'dock', $tooltips, $defaultTooltipMessage, 'h3', 'Dock Configuration' ),
			[
				'expanded'  => false,
				'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
			]
		);
		?>
		<div id="dock-config-editor">
			<ul id="dock-list"></ul>
			<button type="button" id="add-dock-item">➕ Add Dock Item</button>
		</div>
		<input type="hidden" id="dock_json_input" name="dock_json">
		<br>
		<button type="submit">Save Settings</button>
		<br><br>
		<?php renderAccordionSectionEnd(); ?>

	</form>

	<?php renderSeparatorLine(); ?>

	<!-- vHosts Manager -->
	<?php
	$vhostsHeading = renderHeadingTooltip( 'vhosts_manager', $tooltips, $defaultTooltipMessage, 'h3', 'Virtual Hosts Manager' ) . ( $apachePathValid ? '' : ' &nbsp;❕' );

	renderAccordionSectionStart(
		'vhosts-manager',
		$vhostsHeading,
		[
			'disabled'  => ! $apachePathValid,
			'expanded'  => false,
			'settings'  => true,
			'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
		]
	);
	?>
	<?php require_once __DIR__ . '/../partials/vhosts.php'; ?>
	<?php renderAccordionSectionEnd(); ?>

	<?php renderSeparatorLine(); ?>

	<!-- Export -->
	<?php
	$exportHeading = renderHeadingTooltip( 'export', $tooltips, $defaultTooltipMessage, 'h3', 'Export Files & Database' ) . ( $phpPathValid ? '' : ' &nbsp;❕' );

	renderAccordionSectionStart(
		'export',
		$exportHeading,
		[
			'disabled'  => ! $phpPathValid,
			'expanded'  => false,
			'settings'  => true,
			'caretPath' => __DIR__ . '/../assets/images/caret-down.svg',
		]
	);
	?>
	<?php require_once __DIR__ . '/../partials/export.php'; ?>
	<?php renderAccordionSectionEnd(); ?>

	<?php renderSeparatorLine(); ?>

	<!-- Apache Control -->
	<div class="apache-control">
		<div class="heading">
			<?= renderHeadingTooltip( 'apache_control', $tooltips, $defaultTooltipMessage, 'h2', 'Apache Control' ) ?>
		</div>
		<?php if ( $apacheToggle && $apachePathValid ): ?>
			<button id="restart-apache-button">Restart Apache</button>
			<?php renderSeparatorLine( 'small' ) ?>
			<div id="apache-status-message" role="status" aria-live="polite"></div>
		<?php else: ?>
			<p>Apache control
				unavailable<?= ! $apachePathValid ? ' (invalid Apache path)' : ' (toggle_apache.php missing)' ?></p><br>
			<button disabled id="restart-apache-button">Restart Apache</button>
			<?php renderSeparatorLine( 'small' ) ?>
		<?php endif; ?>
	</div>

	<div id="clear-settings-wrapper">
		<div class="heading">
			<?= renderHeadingTooltip( 'clear_storage', $tooltips, $defaultTooltipMessage, 'h2', 'Reset Settings' ) ?>
		</div>
		<button id="clear-local-storage" class="button warning">🧹 Clear Local Storage</button>
	</div>
</div>
