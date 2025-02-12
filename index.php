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
            --text-color: #e0dde8;
            --header-background-color: #1d1c1fe8;
            --border-color: #444444;
            --link-color: #4672c9;
            --hover-color: #f15e83;
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
            animation: slideInDown 1s ease-out;
            position: -webkit-sticky; /* For Safari */
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        header h1, .folders a, a {
            color: var(--link-color);
            /* animation: hueCycle 10s infinite; */ /* Animation */
        }
        header h1 {
            margin: 0;
            font-family: 'Ubuntu', sans-serif;
        }
        header .clock {
            font-size: 2.2em;
            font-weight: 700;
        }
        .server-info {
            flex: none;
            text-align: right;
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
        .folders ul {
            list-style: none;
            padding: 0;
        }
        .folders li {
            margin: 5px 0;
        }
        .folders a {
            text-decoration: none;
            font-weight: bold;
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
        }
        a {
            text-decoration: none;
            transition: color 0.3s;
        }
        a:hover {
            text-decoration: none;
            color: var(--hover-color);
        }

        /* Media breakpoints */
        @media (max-width: 900px) {
            header h1 {
                animation: fadeOut 0.5s forwards;
            }
            .columns {
                flex-direction: column;
            }
        }
        @media (min-width: 901px) {
            header h1 {
                animation: fadeIn 0.5s forwards;
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
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            document.querySelector('.clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        document.addEventListener('DOMContentLoaded', updateClock);
    </script>
</head>
<body class="background-image">
    <div class="container">
        <header>
            <h1>localhost is up and running! 👨🏻‍💻</h1>
            <div class="clock"></div>
            <div class="server-info">
                <?php
                    include 'config.php';

                    // Get the Apache version using shell_exec and the full path to httpd.exe
                    $apacheVersion = shell_exec('C:\\xampp\\apache\\bin\\httpd.exe -v');
                    if ($apacheVersion) {
                        // Extract the version from the output
                        if (preg_match('/Server version: Apache\/([0-9\.]+)/', $apacheVersion, $matches)) {
                            echo 'Apache: ' . $matches[1] . ' ✔️<br>';
                        } else {
                            echo 'Apache: Could not extract version info ❌<br>';
                        }
                    } else {
                        echo 'Apache: Not available ❌<br>';
                    }

                    // Check if PHP version is detected
                    $phpVersion = phpversion();
                    if ($phpVersion === false) {
                        echo 'Error: PHP version not detected ❌<br>';
                    } else {
                        echo 'PHP: <a href="/dashboard/phpinfo.php">' . $phpVersion . '</a> ✔️<br>';

                        try {
                            // Create a connection to the MySQL server
                            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);

                            // Check for connection errors
                            if ($mysqli->connect_error) {
                                throw new Exception("Connection failed: " . $mysqli->connect_error);
                            }

                            // Get the MySQL version
                            $mysqlVersion = $mysqli->server_info;

                            // Output the MySQL version
                            echo "MySQL: " . $mysqlVersion . " ✔️<br>";

                            // Close the connection
                            $mysqli->close();
                        } catch (mysqli_sql_exception $e) {
                            echo "Error: " . $e->getMessage() . " ❌<br>";
                        } catch (Exception $e) {
                            echo "Unexpected error: " . $e->getMessage() . " ❌<br>";
                        }
                    };
                ?>
            </div>
        </header>
        <section class="folders">
            <h2>Document Folders</h2>
            <div class="columns">
                <div class="column">
                    <h3>Other Folders 🤷🏻‍♂️</h3>
                    <ul>
                        <?php
                            $dir = './projects/Other/';
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3>WP Folders 🚀</h3>
                    <ul>
                        <?php
                            $dir = './projects/WP/';
                            $excludeList = ['example1', 'example2']; // Add folder names you want to exclude
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                // Check if the folder is in the exclusion list
                                if (in_array($folderName, $excludeList)) {
                                    continue;
                                }

                                echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
                <div class="column">
                    <h3>Pantheon Folders 🏛️</h3>
                    <ul>
                        <?php
                            $dir = './projects/Pantheon/';
                            $folders = array_filter(glob($dir . '*'), 'is_dir');
                            foreach ($folders as $folder) {
                                $folderName = basename($folder);

                                echo "<li><a href=\"http://local.pantheon.$folderName.com\">$folderName</a></li>";
                            }
                        ?>
                    </ul>
                </div>
            </div>
        </section>
        <footer>
            <a href="/dashboard/">XAMPP Dashboard</a> | <a href="/phpmyadmin/">PHPMyAdmin</a>
            <p>“It’s not a bug. It’s an undocumented feature!”</p>
        </footer>
    </div>
</body>
</html>
