<?php
// config.php
define( 'DB_HOST', 'localhost' );
define( 'DB_USER', 'user' );
define( 'DB_PASSWORD', 'password' );
define( 'APACHE_PATH', 'C:\\xampp\\apache\\' );

$displaySystemStats    = true;
$displayApacheErrorLog = true;

$bodyClasses = 'background-image';

if ( file_exists( 'system_stats.php' ) && $displaySystemStats ) {
    $bodyClasses .= ' system-monitor-active';
}

if ( file_exists( 'apache_error_log.php' ) && $displayApacheErrorLog ) {
    $bodyClasses .= ' error-log-section-active';
}
?>