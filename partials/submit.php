<?php
// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

$user_config = "<?php
";
$user_config .= "define('DB_HOST', '" . addslashes($_POST['DB_HOST']) . "');
";
$user_config .= "define('DB_USER', '" . addslashes($_POST['DB_USER']) . "');
";
$user_config .= "define('DB_PASSWORD', '" . addslashes($_POST['DB_PASSWORD']) . "');
";
$user_config .= "define('APACHE_PATH', '" . addslashes($_POST['APACHE_PATH']) . "');
";
$user_config .= "define('HTDOCS_PATH', '" . addslashes($_POST['HTDOCS_PATH']) . "');
";
$user_config .= "define('PHP_PATH', '" . addslashes($_POST['PHP_PATH']) . "');
";
$user_config .= "\$displaySystemStats = " . (isset($_POST['displaySystemStats']) ? 'true' : 'false') . ";
";
$user_config .= "\$displayApacheErrorLog = " . (isset($_POST['displayApacheErrorLog']) ? 'true' : 'false') . ";
";
$user_config .= "\$useAjaxForStats = " . (isset($_POST['useAjaxForStats']) ? 'true' : 'false') . ";
";

$user_config .= "ini_set('display_errors', " . (isset($_POST['displayErrors']) ? '1' : '0') . ");\n";
$user_config .= "error_reporting(" . $_POST['errorReportingLevel'] . ");\n";
$user_config .= "ini_set('log_errors', " . (isset($_POST['logErrors']) ? '1' : '0') . ");\n";

file_put_contents( 'user_config.php', $user_config );
    // Invalidate OPcache to ensure updated config is used immediately
    if ( function_exists( 'opcache_invalidate' ) ) {
        opcache_invalidate( __DIR__ . '/../user_config.php', true );
    }

    
    // Update php.ini file with submitted settings
    $php_ini_path = $_POST['PHP_PATH'] . 'php.ini';

    if (file_exists($php_ini_path) && is_writable($php_ini_path)) {
        $ini_content = file_get_contents($php_ini_path);

        // Update display_errors
        $display_errors_value = isset($_POST['displayErrors']) ? 'On' : 'Off';
        $ini_content = preg_replace('/^display_errors\s*=\s*.*/m', 'display_errors = ' . $display_errors_value, $ini_content);

        // Update error_reporting
        $error_reporting_value = $_POST['errorReportingLevel'];
        $ini_content = preg_replace('/^error_reporting\s*=\s*.*/m', 'error_reporting = ' . $error_reporting_value, $ini_content);

        file_put_contents($php_ini_path, $ini_content);
    }

    header("Location: index.php");
    exit;
}