<div id="vhosts-manager">
    <h2>Virtual Hosts Manager</h2>
    <?php
    $vhostsPath = APACHE_PATH . '/conf/extra/httpd-vhosts.conf';
    $hostsFiles = [
        'Windows' => getenv('WINDIR') . '/System32/drivers/etc/hosts',
        'Linux'   => '/etc/hosts',
        'Mac'     => '/etc/hosts',
    ];
    $hostsEntries = [];
    $serverNames  = [];

    if (file_exists($vhostsPath)) {
        $lines = file($vhostsPath);
        foreach ($lines as $line) {
            if (preg_match('/^\s*ServerName\s+(.+)/i', $line, $matches)) {
                $serverNames[] = trim($matches[1]);
            }
        }

        foreach ($hostsFiles as $os => $path) {
            if (file_exists($path)) {
                foreach (file($path) as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) {
                        continue;
                    }
                    $parts = preg_split('/\s+/', $line);
                    for ($i = 1; $i < count($parts); $i++) {
                        $hostsEntries[$os][] = $parts[$i];
                    }
                }
            }
        }

        if (!empty($hostsEntries)) {
            echo '<table>';
            echo '<thead><tr><th>Server Name</th><th>Status</th></tr></thead><tbody>';
            foreach ($serverNames as $serverName) {
                $valid = false;
                foreach ($hostsEntries as $names) {
                    if (in_array($serverName, $names, true)) {
                        $valid = true;
                        break;
                    }
                }
                echo '<tr>';
                echo '<td>' . htmlspecialchars($serverName) . '</td>';
                echo '<td class="status">' . ($valid ? '<span class="tick">✔️</span>' : '<span class="cross">❌</span>') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p><strong>Note:</strong> None of the expected hosts files were found on your system, or they contain no relevant entries. Please check your OS and ensure your hosts file is readable.</p>';
        }
    } else {
        echo '<p><strong>Note:</strong> The <code>httpd-vhosts.conf</code> file was not found at <code>' . htmlspecialchars($vhostsPath) . '</code>. Please ensure your Apache setup is correct and virtual hosts are enabled.</p>';
    }
    ?>
</div>