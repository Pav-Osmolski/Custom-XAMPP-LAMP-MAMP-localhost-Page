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
$user_config .= "\$displaySystemStats = " . (isset($_POST['displaySystemStats']) ? 'true' : 'false') . ";
";
$user_config .= "\$displayApacheErrorLog = " . (isset($_POST['displayApacheErrorLog']) ? 'true' : 'false') . ";
";
$user_config .= "\$useAjaxForStats = " . (isset($_POST['useAjaxForStats']) ? 'true' : 'false') . ";
";

file_put_contents( 'user_config.php', $user_config );
    // Invalidate OPcache to ensure updated config is used immediately
    if ( function_exists( 'opcache_invalidate' ) ) {
        opcache_invalidate( __DIR__ . '/../user_config.php', true );
    }

    header("Location: index.php");
    exit;
}