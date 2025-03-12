<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars( $user ); ?>'s custom XAMPP localhost index page.">
    <title><?php echo htmlspecialchars( $user ); ?>'s localhost</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon/favicon.ico">
    <link rel="icon" sizes="192x192" href="assets/favicon/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="stylesheet" type="text/css"
          href="assets/css/style.min.css?v=<?= filemtime( 'assets/css/style.min.css' ); ?>">
    <script src="assets/js/script.min.js?v=<?= filemtime( 'assets/js/script.min.js' ); ?>"></script>
</head>
<?php echo ( $useAjaxForStats ? '<body data-ajax-enabled="true"' : '<body' ); ?> class="<?= $bodyClasses; ?>">
<div class="container">
    <header role="banner">
        <h1>localhost is ready, <?php echo htmlspecialchars( $user ); ?>! 👨🏻‍💻</h1>
        <input type="text" class="search-bar" placeholder="Search projects..." onkeyup="searchProjects()"
               aria-label="Search projects">
        <div class="clock" aria-live="polite"></div>
        <div class="server-info">
            <?php
            if ( $isWindows ) {
                // Get the Apache version using shell_exec and the full path to httpd.exe
                $apacheVersion = shell_exec( APACHE_PATH . 'bin\\httpd.exe -v' );
            } else {
                // On Linux, try using apachectl or httpd to get the version
                $apacheVersion = shell_exec( 'apachectl -v 2>/dev/null' ) ?: shell_exec( 'httpd -v 2>/dev/null' );
            }

            // Attempt to extract the version
            if ( $apacheVersion && preg_match( '/Server version: Apache\/([\d\.]+)/', $apacheVersion, $matches ) ) {
                echo 'Apache: ' . $matches[1] . ' ✔️<br>';
            } else {
                // If shell_exec fails, check $_SERVER variables
                if ( !empty( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false ) {
                    echo 'Apache: Version unknown ⚠️<br>';
                } else {
                    echo 'Apache: Not detected ❌<br>';
                }
            }

            // Check if PHP version is detected
            $phpVersion = phpversion();
            if ( $phpVersion === false ) {
                echo 'PHP: Version unknown ⚠️<br>';
            } else {
                $isThreadSafe = ( ZEND_THREAD_SAFE ) ? "TS" : "NTS";
                $isFastCGI = ( strpos( PHP_SAPI, 'cgi-fcgi' ) !== false ) ? "FastCGI" : "Non-FastCGI";

                echo 'PHP: <a href="phpinfo.php">' . $phpVersion . " $isThreadSafe $isFastCGI</a> ✔️<br>";
            }

            // Check MySQL version
            try {
                // Create a connection to the MySQL server
                $mysqli = new mysqli( DB_HOST, DB_USER, DB_PASSWORD );

                // Check for connection errors
                if ( $mysqli->connect_error ) {
                    throw new Exception( "Connection failed: " . $mysqli->connect_error );
                }

                // Get the MySQL version
                $mysqlVersion = $mysqli->server_info;

                // Output the MySQL version
                echo "MySQL: " . $mysqlVersion . " ✔️<br>";

                // Close the connection
                $mysqli->close();
            } catch ( mysqli_sql_exception $e ) {
                echo "MySQL: " . $e->getMessage() . " ❌<br>";
            } catch ( Exception $e ) {
                echo "MySQL: " . $e->getMessage() . " ❌<br>";
            }
            ?>
        </div>
    </header>
    <main role="main">
        <section class="folders">
            <h2>Document Folders</h2>
            <div class="columns">
                <div class="column">
                    <h3>Miscellaneous 🤷🏻‍♂️</h3>
                    <ul>
                        <?php
                        $dir = HTDOCS_PATH . 'projects/Other/';

                        if ( !is_dir( $dir ) ) {
                            echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                        } else {
                            $folders = array_filter( glob( $dir . '*' ), 'is_dir' );
                            
                            if ( empty( $folders ) ) {
                                echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                            } else {
                                foreach ( $folders as $folder ) {
                                    $folderName = basename( $folder );
                                    echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3><a href="">GitHub</a> 🚀</h3>
                    <ul>
                        <?php
                        $dir = HTDOCS_PATH . 'projects/GitHub/';

                        if ( !is_dir( $dir ) ) {
                            echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                        } else {
                            $folders = array_filter( glob( $dir . '*' ), 'is_dir' );
                            
                            if ( empty( $folders ) ) {
                                echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                            } else {
                                foreach ( $folders as $folder ) {
                                    $folderName = basename( $folder );
                                    echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3><a href="">Pantheon</a> 🏛️</h3>
                    <ul>
                        <?php
                        $dir = HTDOCS_PATH . 'projects/Pantheon/';

                        if ( !is_dir( $dir ) ) {
                            echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                        } else {
                            $folders = array_filter( glob( $dir . '*' ), 'is_dir' );
                            
                            if ( empty( $folders ) ) {
                                echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                            } else {
                                foreach ( $folders as $folder ) {
                                    $folderName = basename( $folder );
                                    echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                }
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="dock">
                <a href="https://jira.atlassian.com/" target="_blank">
                    <img src="./assets/images/Jira.png" alt="Jira">
                </a>
                <a href="https://github.com/" target="_blank">
                    <img src="./assets/images/GitHub.png" alt="GitHub">
                </a>
                <a href="https://bitbucket.org/" target="_blank">
                    <img src="./assets/images/Bitbucket.png" alt="Bitbucket">
                </a>
            </div>
        </section>
        <?php if ( $displayApacheErrorLog ): ?>
            <section id="error-log-section" aria-labelledby="error-log-title">
                <h3 id="error-log-title">
                    <button id="toggle-error-log" aria-expanded="false" aria-controls="error-log">
                        📝 Toggle Apache Error Log
                    </button>
                </h3>
                <pre id="error-log" aria-live="polite" tabindex="0" style="display: none;">
                    <code>Loading...</code>
                </pre>
            </section>
        <?php endif; ?>
        <?php if ( $displaySystemStats ): ?>
            <div id="system-monitor" class="system-monitor" role="region" aria-labelledby="system-monitor-title">
                <h3 id="system-monitor-title">System Stats</h3>
                <p>CPU Load: <span id="cpu-load" aria-live="polite">N/A</span></p>
                <p>RAM Usage: <span id="memory-usage" aria-live="polite">N/A</span></p>
                <p>Disk Space: <span id="disk-space" aria-live="polite">N/A</span></p>
            </div>
        <?php endif; ?>
    </main>
    <footer role="contentinfo">
        <a href="/dashboard/">Dashboard</a> | <a href="/phpmyadmin/">PHPMyAdmin</a>
        <button class="toggle-theme"><span class="emoji">🌙 ☀️</span></button>
        <p><span class="quote">“</span>It’s not a bug. It’s an undocumented feature!<span class="quote">”</span></p>
    </footer>
</div>
</body>
</html>