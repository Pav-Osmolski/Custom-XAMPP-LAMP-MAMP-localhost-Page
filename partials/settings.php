<?php
$apachePathValid = file_exists( APACHE_PATH );
$htdocsPathValid = file_exists( HTDOCS_PATH );
$phpPathValid    = file_exists( PHP_PATH );

// Centralised tooltip descriptions
$tooltips = [
	'php_error'     => 'Configure how PHP displays or logs errors, including toggling error reporting levels and defining log output behavior for development or production use.',
	'folders'       => 'Manage which folders appear in each column, their titles, filters, and link behaviour.',
	'link_templates'=> 'Define how each folder\'s website links should appear by customising the HTML templates used per column.',
	'dock'          => 'Manage the items displayed in the dock, including their order, icons, and link targets.'
];
?>

<div id="settings-view" style="display: none;">
	<!-- User Settings -->
	<h2>User Settings Configuration</h2>
	<form method="post">
		<label>DB Host:&nbsp;<input type="text" name="DB_HOST" value="<?= DB_HOST ?>"></label>
		<label>DB User:&nbsp;<input type="text" name="DB_USER" value="<?= DB_USER ?>"></label>
		<label>DB Password:&nbsp;<input type="password" name="DB_PASSWORD" value="<?= DB_PASSWORD ?>"></label>

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
			<span class="tooltip-icon" aria-describedby="tooltip-php_error" tabindex="0" data-tooltip="<?= htmlspecialchars($tooltips['php_error']) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-php_error" class="sr-only" role="tooltip"><?= htmlspecialchars($tooltips['php_error']) ?></span>

		<label>Display Errors:
			<input type="checkbox" name="displayErrors" <?= ini_get( 'display_errors' ) ? 'checked' : '' ?>>
		</label>

		<label>Error Reporting Level:
			<select name="errorReportingLevel">
				<option value="E_ALL" <?= error_reporting() == E_ALL ? 'selected' : '' ?>>E_ALL</option>
				<option value="E_ERROR" <?= error_reporting() == E_ERROR ? 'selected' : '' ?>>E_ERROR</option>
				<option value="E_WARNING" <?= error_reporting() == E_WARNING ? 'selected' : '' ?>>E_WARNING</option>
				<option value="E_NOTICE" <?= error_reporting() == E_NOTICE ? 'selected' : '' ?>>E_NOTICE</option>
			</select>
		</label>

		<label>Log Errors:
			<input type="checkbox" name="logErrors" <?= ini_get( 'log_errors' ) ? 'checked' : '' ?>>
		</label><br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Folders Configuration <span class="tooltip-icon" aria-describedby="tooltip-folders" tabindex="0" data-tooltip="<?= htmlspecialchars($tooltips['folders']) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-folders" class="sr-only" role="tooltip"><?= htmlspecialchars($tooltips['folders']) ?></span><br>

		<div id="folders-config">
			<ul id="folders-config-list" class="draggable-list"></ul>
			<button type="button" id="add-folder-column">➕ Add Column</button>
		</div>
		<input type="hidden" name="folders_json" id="folders_json_input">
		<br>

		<h3>Folder Link Templates <span class="tooltip-icon" aria-describedby="tooltip-link_templates" tabindex="0" data-tooltip="<?= htmlspecialchars($tooltips['link_templates']) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-link_templates" class="sr-only" role="tooltip"><?= htmlspecialchars($tooltips['link_templates']) ?></span>

		<div id="link-templates-config">
			<ul id="link-templates-list" class="template-list"></ul>
			<button type="button" id="add-link-template">➕ Add Link Template</button>
		</div>
		<input type="hidden" id="link_templates_json_input" name="link_templates" value="">
		<br>

		<button type="submit">Save Settings</button>

		<br><br>
		<h3>Dock Configuration <span class="tooltip-icon" aria-describedby="tooltip-dock" tabindex="0" data-tooltip="<?= htmlspecialchars($tooltips['dock']) ?>"><?php include __DIR__ . '/../assets/images/tooltip-icon.svg'; ?>
			</span>
		</h3>
		<span id="tooltip-dock" class="sr-only" role="tooltip"><?= htmlspecialchars($tooltips['dock']) ?></span>

		<div id="dock-config-editor">
			<ul id="dock-list"></ul>
			<button type="button" id="add-dock-item">➕ Add Item</button>
		</div>
		<br>

		<button type="submit">Save Settings</button>
	</form>
	<br>

	<!-- Apache Control -->
	<div class="apache-control">
		<h2>Apache Control</h3>
			<button id="restart-apache-button">Restart Apache</button>

			<div id="apache-status-message" style="margin-top:10px;"></div>
	</div>

	<!-- vHosts Manager -->
	<div id="vhosts-manager">
		<h2>Virtual Hosts Manager</h2>
		<table>
			<thead>
			<tr>
				<th>Server Name</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$vhostsPath = APACHE_PATH . '/conf/extra/httpd-vhosts.conf';
			if ( file_exists( $vhostsPath ) ) {
				$lines       = file( $vhostsPath );
				$serverNames = [];
				foreach ( $lines as $line ) {
					if ( preg_match( '/^\s*ServerName\s+(.+)/i', $line, $matches ) ) {
						$serverNames[] = trim( $matches[1] );
					}
				}
				$hostsFiles = [
					'Windows' => getenv('WINDIR') . '/System32/drivers/etc/hosts',
					'Linux'   => '/etc/hosts',
					'Mac'     => '/etc/hosts',
				];
				$hostsEntries = [];
				foreach ( $hostsFiles as $os => $path ) {
					if ( file_exists( $path ) ) {
						foreach ( file( $path ) as $line ) {
							$line = trim( $line );
							if ( $line === '' || strpos( $line, '#' ) === 0 ) {
								continue;
							}
							$parts = preg_split( '/\s+/', $line );
							for ( $i = 1; $i < count( $parts ); $i ++ ) {
								$hostsEntries[ $os ][] = $parts[ $i ];
							}
						}
					}
				}
				foreach ( $serverNames as $serverName ) {
					$valid = false;
					foreach ( $hostsEntries as $names ) {
						if ( in_array( $serverName, $names, true ) ) {
							$valid = true;
							break;
						}
					}
					echo '<tr>';
					echo '<td>' . htmlspecialchars( $serverName ) . '</td>';
					echo '<td class="status">' . ( $valid ? '<span class="tick">✔️</span>' : '<span class="cross">❌</span>' ) . '</td>';
					echo '</tr>';
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>
