<?php
require_once 'config.php';

if ( !$displayApacheErrorLog ) {
    exit( "Apache error log display is disabled." );
}

include 'apache_error_log.php';

?>
