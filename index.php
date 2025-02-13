<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pav's localhost</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&display=swap');

        :root {
            --background-color: #121212e8;
            --light-mode-bg-overlay: #e7e6f0d9;
            --text-color: #e0dde8;
            --header-background-color: #1d1c1fe8;
            --border-color: #444;
            --link-color: #4672c9;
            --hover-color: #f15e83;
            --input-bg: #1d1c1f;
            --input-text: #e0dde8;
            --input-placeholder: #888;
        }

        .light-mode {
            --background-color: #f0f0f0;
            --text-color: #2a3246;
            --header-background-color: #dfdde6;
            --border-color: #626798;
            --link-color: #237493;
            --input-bg: #fbf9ff;
            --input-text: #2a3246;
        }

        body {
            font-family: 'Ubuntu', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow-x: hidden;
        }

        .background-image {
            background-image: url(/img/background.jpg);
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .light-mode.background-image::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--light-mode-bg-overlay);
            z-index: -1;
        }

        .container {
            display: flex;
            flex-direction: column;
            /* height: 100vh; */
        }

        header {
            padding: 15px;
            background-color: var(--header-background-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid var(--border-color);
            height: 88px;
            box-sizing: border-box;
            animation: slideInDown 1s ease-out;
            position: -webkit-sticky; /* For Safari */
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .search-bar {
            font-family: 'Ubuntu', sans-serif;
            width: 100%;
            max-width: 300px;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background-color: var(--input-bg);
            color: var(--input-text);
        }

        .search-bar::placeholder {
            color: var(--input-placeholder);
        }

        header h1, .folders a, a {
            color: var(--link-color);
            /* animation: hueCycle 10s infinite; */
        }

        header h1 {
            margin: 0;
            font-family: 'Ubuntu', sans-serif;
        }

        header .clock {
            font-size: 2.1em;
            font-weight: 700;
        }

        .server-info {
            flex: none;
            text-align: right;
            font-size: 14px;
            /* bottom: -7px; */
            position: relative;
        }

        section {
            flex: 1;
            padding: 20px 20px 123px;
            animation: fadeIn 1.5s ease-in;
        }

        .columns {
            display: flex;
            gap: 30px;
        }

        .column {
            flex: 1;
            background-color: var(--header-background-color);
            padding: 10px;
            border-radius: 8px;
        }

        .column h3 a {
            color: var(--text-color);
        }

        .folders ul {
            list-style: none;
            padding: 0;
        }

        .folders li {
            margin: 5px 0;
            gap: 20px;
        }

        .folders a {
            text-decoration: none;
            font-weight: bold;
        }

        .folder-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .url-container {
            display: flex;
            gap: 10px;
        }

        footer {
            padding: 20px;
            background-color: var(--header-background-color);
            border-top: 1px solid var(--border-color);
            text-align: center;
            animation: slideInUp 1s ease-out;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 93px;
            box-sizing: border-box;
            overflow: hidden;
        }

        footer p {
            font-style: italic;
        }

        footer a {
            font-weight: bold;
            margin: 0 5px;
        }

        a {
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            text-decoration: none;
            color: var(--hover-color) !important;
        }

        .toggle-theme {
            cursor: pointer;
            font-size: 18px;
            background: transparent;
            border: none;
            color: var(--text-color);
            border-radius: 5px;
            flex: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 11px;
        }

        .emoji {
            filter: hue-rotate(170deg) saturate(0.4) brightness(0.8);
        }

        .light-mode .emoji {
            filter: none;
        }

        span.quote {
            font-size: 25px;
            line-height: 13px;
            margin: 0 4px;
            position: relative;
            top: 4px;
        }
        .dock {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            padding: 10px;
            /* background: rgba(255, 255, 255, 0.1); */
            /* border-radius: 15px; */
            /* backdrop-filter: blur(10px); */
        }

        .dock a {
            display: inline-block;
            transition: transform 0.3s ease-in-out;
        }

        .dock a:hover {
            transform: scale(1.3);
        }

        .dock img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
        }
        .misc {
            pointer-events: none;
        }

        /* Media breakpoints */
        @media (max-width: 1110px) {
            header h1 {
                font-size: 26px;
            }
        }

        @media (max-width: 900px) {
            header h1 {
                /* animation: fadeOut 0.5s forwards; */
                display: none;
            }

            .columns {
                flex-direction: column;
            }

            .toggle-theme {
                position: static;
                transform: none;
            }
        }

        @media (min-width: 901px) {
            header h1 {
                /* animation: fadeIn 0.5s forwards; */
            }
        }

        /* Keyframe animations */
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                display: none;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                display: none;
            }
            to {
                opacity: 1;
                display: block;
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes hueCycle {
            0% {
                color: #4672c9;
            }
            50% {
                color: #ff00ff; /* Midway color (magenta) */
            }
            100% {
                color: #4672c9;
            }
        }
    </style>
    <script>
        function updateClock() {
            const now = new Date();
            const hours = now.getHours().toString().padStart( 2, '0' );
            const minutes = now.getMinutes().toString().padStart( 2, '0' );
            const seconds = now.getSeconds().toString().padStart( 2, '0' );
            document.querySelector( '.clock' ).textContent = `${ hours }:${ minutes }:${ seconds }`;
        }

        setInterval( updateClock, 1000 );
        document.addEventListener( 'DOMContentLoaded', updateClock );

        function toggleTheme() {
            document.body.classList.toggle( 'light-mode' );
        }

        function searchProjects() {
            let input = document.querySelector( '.search-bar' ).value.toLowerCase();
            let items = document.querySelectorAll( '.folders li' );

            items.forEach( item => {
                let text = item.textContent.toLowerCase();
                if ( text.includes( input ) ) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            } );
        }
    </script>
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
                        <img src="/img/Jira.png" alt="Jira">
                    </a>
                    <a href="https://github.com/" target="_blank">
                        <img src="/img/GitHub.png" alt="GitHub">
                    </a>
                    <a href="https://bitbucket.org/" target="_blank">
                        <img src="/img/Bitbucket.png" alt="Bitbucket">
                    </a>
                </div>
            </div>
        </section>
    </main>
    <footer role="contentinfo">
        <a href="/dashboard/">Dashboard</a> | <a href="/phpmyadmin/">PHPMyAdmin</a>
        <button class="toggle-theme" onclick="toggleTheme()"><span class="emoji">🌙 ☀️</span></button>
        <p><span class="quote">“</span>It’s not a bug. It’s an undocumented feature!<span class="quote">”</span></p>
    </footer>
</div>
</body>
</html>