<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

$apachePathValid = file_exists( APACHE_PATH );
$htdocsPathValid = file_exists( HTDOCS_PATH );
$phpPathValid    = file_exists( PHP_PATH );
$apacheToggle    = file_exists( __DIR__ . '/../utils/toggle_apache.php' );

$dbUser          = getDecrypted( 'DB_USER' );
$dbPass          = getDecrypted( 'DB_PASSWORD' );

$currentLevel    = ini_get('error_reporting');

// Centralised tooltip descriptions
$tooltips = [
	'php_error'      => 'Configure how PHP displays or logs errors, including toggling error reporting levels and defining log output behavior for development or production use.',
	'folders'        => 'Manage which folders appear in each column, their titles, filters, and link behaviour.',
	'link_templates' => 'Define how each folder\'s website links should appear by customising the HTML templates used per column.',
	'dock'           => 'Manage the items displayed in the dock, including their order, icons, and link targets.',
	'apache_control' => 'Restart the Apache server.',
	'vhosts_manager' => 'Browse, check, and open virtual hosts with cert and DNS validation.',
	'clear_storage'  => 'This will reset saved UI settings (theme, Column Order and Column Size etc.) stored in your browser’s local storage.'
];
?>

<div id="settings-view">
	<!-- User Settings -->
	<h2>User Settings Configuration</h2>
	<form method="post">
		<label>DB Host:&nbsp;<input type="text" name="DB_HOST" value="<?= DB_HOST ?>"></label>
		<label>DB User:&nbsp;<input type="text" name="DB_USER" value="<?= htmlspecialchars( $dbUser ) ?>"></label>
		<label>DB Password:&nbsp;<input type="password" name="DB_PASSWORD" value="<?= htmlspecialchars( $dbPass ) ?>"></label>

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

		<label>Display System Stats:
			<input type="checkbox" name="displaySystemStats" <?= $displaySystemStats ? 'checked' : '' ?>>
		</label>

		<label>Display Apache Error Log:
			<input type="checkbox" name="displayApacheErrorLog" <?= $displayApacheErrorLog ? 'checked' : '' ?>>
		</label>

		<label>Use AJAX for Stats and Error log:
			<input type="checkbox" name="useAjaxForStats" <?= $useAjaxForStats ? 'checked' : '' ?>>
		</label><br>

		<h3>PHP Error Handling & Logging
			<span class="tooltip-icon" aria-describedby="tooltip-php_error" tabindex="0"
			      data-tooltip="<?= htmlspecialchars( $tooltips['php_error'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-php_error" class="sr-only"
		      role="tooltip"><?= htmlspecialchars( $tooltips['php_error'] ) ?></span>

		<label>Display Errors:
			<input type="checkbox" name="displayErrors" <?= ini_get( 'display_errors' ) ? 'checked' : '' ?>>
		</label>

		<label>Error Reporting Level:
			<select name="errorReportingLevel">
				<option value="E_ALL" <?= $currentLevel == E_ALL ? 'selected' : '' ?>>E_ALL</option>
				<option value="E_ERROR" <?= $currentLevel == E_ERROR ? 'selected' : '' ?>>E_ERROR</option>
				<option value="E_WARNING" <?= $currentLevel == E_WARNING ? 'selected' : '' ?>>E_WARNING</option>
				<option value="E_NOTICE" <?= $currentLevel == E_NOTICE ? 'selected' : '' ?>>E_NOTICE</option>
			</select>
		</label>

		<label>Log Errors:
			<input type="checkbox" name="logErrors" <?= ini_get( 'log_errors' ) ? 'checked' : '' ?>>
		</label><br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Folders Configuration <span class="tooltip-icon" aria-describedby="tooltip-folders" tabindex="0"
		                                data-tooltip="<?= htmlspecialchars( $tooltips['folders'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-folders" class="sr-only" role="tooltip"><?= htmlspecialchars( $tooltips['folders'] ) ?></span><br>

		<div id="folders-config">
			<ul id="folders-config-list" class="draggable-list"></ul>
			<button type="button" id="add-folder-column">➕ Add Column</button>
		</div>
		<input type="hidden" id="folders_json_input" name="folders_json">
		<br>

		<h3>Folder Link Templates <span class="tooltip-icon" aria-describedby="tooltip-link_templates" tabindex="0"
		                                data-tooltip="<?= htmlspecialchars( $tooltips['link_templates'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-link_templates" class="sr-only"
		      role="tooltip"><?= htmlspecialchars( $tooltips['link_templates'] ) ?></span>

		<div id="link-templates-config">
			<ul id="link-templates-list" class="template-list"></ul>
			<button type="button" id="add-link-template">➕ Add Link Template</button>
		</div>
		<input type="hidden" id="link_templates_json_input" name="link_templates" value="">
		<br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Dock Configuration <span class="tooltip-icon" aria-describedby="tooltip-dock" tabindex="0"
		                             data-tooltip="<?= htmlspecialchars( $tooltips['dock'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-dock" class="sr-only" role="tooltip"><?= htmlspecialchars( $tooltips['dock'] ) ?></span>

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
		                             data-tooltip="<?= htmlspecialchars( $tooltips['apache_control'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span></h2>
		<span id="tooltip-apache_control" class="sr-only" role="tooltip"><?= htmlspecialchars( $tooltips['apache_control'] ) ?></span>
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
		<span class="tooltip-icon" aria-describedby="tooltip-php_error" tabindex="0"
		      data-tooltip="<?= htmlspecialchars( $tooltips['clear_storage'] ) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
		</span>
	</div>
</div>
