<div id="vhosts-manager">
	<h2>Virtual Hosts Manager</h2>
	<?php
	$vhostsPath   = APACHE_PATH . '/conf/extra/httpd-vhosts.conf';
	$crtPath      = APACHE_PATH . '/crt/';
	$hostsFiles   = [
		'Windows' => getenv( 'WINDIR' ) . '/System32/drivers/etc/hosts',
		'Linux'   => '/etc/hosts',
		'Mac'     => '/etc/hosts',
	];
	$hostsEntries = [];
	$serverData   = [];

	if ( file_exists( $vhostsPath ) ) {
		$lines        = file( $vhostsPath );
		$currentBlock = [];
		$currentSSL   = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( preg_match( '#^<VirtualHost\s+.*:443>#i', $line ) ) {
				$currentSSL   = true;
				$currentBlock = [ 'ssl' => true ];
			} elseif ( preg_match( '#^<VirtualHost\s+.*:80>#i', $line ) ) {
				$currentSSL   = false;
				$currentBlock = [ 'ssl' => false ];
			} elseif ( preg_match( '#^</VirtualHost>#i', $line ) ) {
				if ( isset( $currentBlock['name'] ) ) {
					$name = $currentBlock['name'];
					$serverData[ $name ] = array_merge([
						'valid'     => false,
						'cert'      => '',
						'key'       => '',
						'certValid' => true,
						'docRoot'   => '',
					], $currentBlock);
				}
				$currentBlock = [];
			} elseif ( preg_match( '/^\s*ServerName\s+(.+)/i', $line, $matches ) ) {
				$currentBlock['name'] = trim( $matches[1] );
			} elseif ( preg_match( '/^\s*DocumentRoot\s+(.+)/i', $line, $matches ) ) {
				$currentBlock['docRoot'] = trim( $matches[1] );
			} elseif ( preg_match( '/^\s*SSLCertificateFile\s+(.+)/i', $line, $matches ) ) {
				$currentBlock['cert'] = trim( $matches[1] );
			} elseif ( preg_match( '/^\s*SSLCertificateKeyFile\s+(.+)/i', $line, $matches ) ) {
				$currentBlock['key'] = trim( $matches[1] );
			}
		}

		$duplicateTracker = [];
		foreach ( $serverData as $name => $info ) {
			$duplicateTracker[ $name ] = isset( $duplicateTracker[ $name ] ) ? $duplicateTracker[ $name ] + 1 : 1;
		}

		foreach ( $serverData as $name => &$info ) {
			if ( $info['ssl'] ) {
				$certPath          = $crtPath . $name . '/server.crt';
				$keyPath           = $crtPath . $name . '/server.key';
				$info['cert']      = $certPath;
				$info['key']       = $keyPath;
				$info['certValid'] = file_exists( $certPath ) && file_exists( $keyPath );
			}
		}

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

		foreach ( array_keys( $serverData ) as $serverName ) {
			foreach ( $hostsEntries as $entries ) {
				if ( in_array( $serverName, $entries, true ) ) {
					$serverData[ $serverName ]['valid'] = true;
					break;
				}
			}
		}
		?>
		<div class="vhost-filters">
			<label>Filter:
				<select id="vhost-filter">
					<option value="all">All</option>
					<option value="missing-cert">Missing Cert</option>
					<option value="missing-host">Missing Host</option>
					<option value="ssl-only">SSL Only</option>
					<option value="non-ssl">Non-SSL</option>
				</select>
			</label>
		</div>

		<table id="vhosts-table">
			<thead>
			<tr>
				<th>Server Name</th>
				<th>Document Root</th>
				<th>Status</th>
				<th>SSL</th>
				<th>Cert</th>
				<th>Open</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $serverData as $host => $info ) :
				$isDuplicate = ( $duplicateTracker[ $host ] ?? 0 ) > 1;
				$classes = [];
				if ( $info['ssl'] ) {
					$classes[] = 'vhost-ssl';
				}
				if ( $info['certValid'] ) {
					$classes[] = 'cert-valid';
				}
				if ( $info['valid'] ) {
					$classes[] = 'host-valid';
				}
				if ( $isDuplicate ) {
					$classes[] = 'vhost-duplicate';
				}
				$classAttr = implode( ' ', $classes );
				$protocol  = $info['ssl'] ? 'https' : 'http';
				$link      = $info['valid']
					? '<a href="' . $protocol . '://' . $host . '" target="_blank">' . htmlspecialchars( $host ) . '</a>'
					: htmlspecialchars( $host );
				?>
				<tr class="<?= $classAttr ?>">
					<td data-label="Server Name"><?= $link ?></td>
					<td data-label="Document Root">
						<code><?= $info['docRoot'] !== '' ? htmlspecialchars( $info['docRoot'] ) : 'N/A' ?></code></td>
					<td data-label="Status"
					    class="status"><?= $info['valid'] ? '<span class="tick">✔️</span>' : '<span class="cross">❌</span>' ?></td>
					<td data-label="SSL"><?= $info['ssl'] ? '<span class="lock">🔒</span>' : '<span class="empty">—</span>' ?></td>
					<td data-label="Cert">
						<?php if ( $info['ssl'] ) : ?>
							<?= $info['certValid'] ? '<span class="tick">✔️</span>' : '<span class="cross">❌</span> <button data-generate-cert="' . htmlspecialchars( $host ) . '">Generate Cert</button>' ?>
						<?php else : ?>
							<span class="empty">—</span>
						<?php endif; ?>
					</td>
					<td data-label="Open">
						<?= $info['docRoot'] !== '' ? '<button class="open-folder" data-path="' . htmlspecialchars( $info['docRoot'] ) . '">📂</button>' : '<span class="empty">—</span>' ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div id="vhost-empty-msg" style="display: none; padding: 1em;">No matching entries found.</div>
		<?php
	} else {
		echo '<p><strong>Note:</strong> The <code>httpd-vhosts.conf</code> file was not found at <code>' . htmlspecialchars( $vhostsPath ) . '</code>. Please ensure your Apache setup is correct and virtual hosts are enabled.</p>';
	}
	?>
</div>
