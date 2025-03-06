<?php
// config.php
define( 'DB_HOST', 'localhost' );
define( 'DB_USER', 'user' );
define( 'DB_PASSWORD', 'password' );
define( 'APACHE_PATH', 'C:\\xampp\\apache\\' );
define( 'HTDOCS_PATH', 'C:\\htdocs\\' ); // Where your local projects are stored

$displaySystemStats    = true;
$displayApacheErrorLog = true;

// Get username based on OS
$user = $_SERVER['USERNAME']
        ?? $_SERVER['USER']
           ?? trim( shell_exec( 'whoami' ) )
              ?? get_current_user();

// Remove the computer name if present (Windows)
if ( str_contains( $user, '\\' ) ) {
    $user = explode( '\\', $user )[1];
}

$user = $user ?: 'Guest';

$bodyClasses = 'background-image';

if ( file_exists( 'system_stats.php' ) && $displaySystemStats ) {
    $bodyClasses .= ' system-monitor-active';
}

if ( file_exists( 'apache_error_log.php' ) && $displayApacheErrorLog ) {
    $bodyClasses .= ' error-log-section-active';
}
?>