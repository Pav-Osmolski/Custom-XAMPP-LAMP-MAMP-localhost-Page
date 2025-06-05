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
	$certData     = [];

	if ( file_exists( $vhostsPath ) ) {
		$lines       = file( $vhostsPath );
		$currentSSL  = false;
		$certFile    = '';
		$keyFile     = '';
		$currentName = '';

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( preg_match( '#^<VirtualHost\s+.*:443>#i', $line ) ) {
				$currentSSL = true;
				$certFile   = '';
				$keyFile    = '';
			} elseif ( preg_match( '#^</VirtualHost>#i', $line ) ) {
				$currentSSL  = false;
				$certFile    = '';
				$keyFile     = '';
				$currentName = '';
			} elseif ( preg_match( '/^\s*ServerName\s+(.+)/i', $line, $matches ) ) {
				$currentName                = trim( $matches[1] );
				$serverData[ $currentName ] = [
					'valid'     => false,
					'ssl'       => $currentSSL,
					'cert'      => '',
					'key'       => '',
					'certValid' => true
				];
			} elseif ( $currentSSL && $currentName && preg_match( '/^\s*SSLCertificateFile\s+(.+)/i', $line, $matches ) ) {
				$serverData[ $currentName ]['cert'] = trim( $matches[1] );
			} elseif ( $currentSSL && $currentName && preg_match( '/^\s*SSLCertificateKeyFile\s+(.+)/i', $line, $matches ) ) {
				$serverData[ $currentName ]['key'] = trim( $matches[1] );
			}
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

		if ( ! empty( $serverData ) ) {
			echo '<table>';
			echo '<thead><tr><th>Server Name</th><th>Status</th><th>SSL</th><th>Cert</th></tr></thead><tbody>';
			foreach ( $serverData as $name => $info ) {
				$protocol = $info['ssl'] ? 'https' : 'http';
				$link     = $info['valid'] ? '<a href="' . $protocol . '://' . $name . '" target="_blank">' . htmlspecialchars( $name ) . '</a>' : htmlspecialchars( $name );
				echo '<tr>';
				echo '<td>' . $link . '</td>';
				echo '<td class="status">' . ( $info['valid'] ? '<span class="tick">✔️</span>' : '<span class="cross">❌</span>' ) . '</td>';
				echo '<td>' . ( $info['ssl'] ? '<span class="lock">🔒</span>' : '—' ) . '</td>';
				echo '<td>';
				if ( $info['ssl'] ) {
					echo $info['certValid']
						? '<span class="tick">✔️</span>'
						: '<span class="cross">❌</span> <button data-generate-cert="' . htmlspecialchars( $name ) . '">Generate Cert</button>';
				} else {
					echo '—';
				}
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<p><strong>Note:</strong> No <code>ServerName</code> entries found in your <code>httpd-vhosts.conf</code>.</p>';
		}
	} else {
		echo '<p><strong>Note:</strong> The <code>httpd-vhosts.conf</code> file was not found at <code>' . htmlspecialchars( $vhostsPath ) . '</code>. Please ensure your Apache setup is correct and virtual hosts are enabled.</p>';
	}
	?>
</div>
