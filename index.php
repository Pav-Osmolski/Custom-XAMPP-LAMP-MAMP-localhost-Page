<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pav's custom XAMPP localhost index page.">
    <title>Pav's localhost</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon/favicon.ico">
    <link rel="icon" sizes="192x192" href="assets/favicon/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <script src="assets/js/script.js"></script>
</head>
<body class="background-image">
<div class="container">
    <header role="banner">
        <h1>localhost is ready, Pav! 👨🏻‍💻</h1>
        <input type="text" class="search-bar" placeholder="Search projects..." onkeyup="searchProjects()" aria-label="Search projects">
        <div class="clock" aria-live="polite"></div>
        <div class="server-info">
            <?php
            include 'config.php';

            // Get the Apache version using shell_exec and the full path to httpd.exe
            $apacheVersion = shell_exec( 'C:\\xampp\\apache\\bin\\httpd.exe -v' );
            if ( $apacheVersion ) {
                // Extract the version from the output
                if ( preg_match( '/Server version: Apache\/([0-9\.]+)/', $apacheVersion, $matches ) ) {
                    echo 'Apache: ' . $matches[1] . ' ✔️<br>';
                } else {
                    echo 'Apache: Could not extract version info ❌<br>';
                }
            } else {
                echo 'Apache: Not available ❌<br>';
            }

            // Check if PHP version is detected
            $phpVersion = phpversion();
            if ( $phpVersion === false ) {
                echo 'Error: PHP version not detected ❌<br>';
            } else {
                echo 'PHP: <a href="/dashboard/phpinfo.php">' . $phpVersion . '</a> ✔️<br>';

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
                    echo "Error: " . $e->getMessage() . " ❌<br>";
                } catch ( Exception $e ) {
                    echo "Unexpected error: " . $e->getMessage() . " ❌<br>";
                }
            };
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
                            $dir     = './projects/Other/';
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3><a href="">GitHub</a> 🚀</h3>
                    <ul>
                        <?php
                            $dir     = './projects/GitHub/';
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3><a href="">Pantheon</a> 🏛️</h3>
                    <ul>
                        <?php
                            $dir     = './projects/Pantheon/';
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
                <div class="dock">
                    <a href="https://jira.atlassian.com/" target="_blank">
                        <img src="/assets/images/Jira.png" alt="Jira">
                    </a>
                    <a href="https://github.com/" target="_blank">
                        <img src="/assets/images/GitHub.png" alt="GitHub">
                    </a>
                    <a href="https://bitbucket.org/" target="_blank">
                        <img src="/assets/images/Bitbucket.png" alt="Bitbucket">
                    </a>
                </div>
            </div>
        </section>
    </main>
    <footer role="contentinfo">
        <a href="/dashboard/">Dashboard</a> | <a href="/phpmyadmin/">PHPMyAdmin</a>
        <button class="toggle-theme"><span class="emoji">🌙 ☀️</span></button>
        <p><span class="quote">“</span>It’s not a bug. It’s an undocumented feature!<span class="quote">”</span></p>
    </footer>
</div>
</body>
</html>