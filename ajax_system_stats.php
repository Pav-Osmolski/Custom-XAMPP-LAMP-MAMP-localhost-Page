<?php
require_once 'config.php';

if ( !$displaySystemStats ) {
    exit( "System stats display is disabled." );
}

include 'system_stats.php';

?>
