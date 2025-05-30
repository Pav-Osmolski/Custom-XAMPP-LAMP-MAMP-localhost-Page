<?php
header( 'Content-Type: application/json' );
header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$os = PHP_OS_FAMILY;

// CPU
if ( $os === 'Windows' ) {
	$cpuLoad = trim( shell_exec( 'wmic cpu get loadpercentage 2>&1' ) );
	preg_match( '/\d+/', $cpuLoad, $matches );
	$cpu = isset( $matches[0] ) ? $matches[0] : 'N/A';
} else {
	$load  = sys_getloadavg();
	$cores = (int) shell_exec( "nproc 2>/dev/null || sysctl -n hw.ncpu" );
	$cpu   = isset( $load[0] ) && $cores > 0 ? round( $load[0] * 100 / $cores, 1 ) : 'N/A';
}

// Memory Usage
$memoryUsage     = memory_get_usage( true ) / 1024 / 1024; // Convert bytes to MB
$peakMemoryUsage = memory_get_peak_usage( true ) / 1024 / 1024;

// Disk Space
$diskFree = disk_free_space( "/" ) / disk_total_space( "/" ) * 100; // Get disk space percentage

echo json_encode( [
	"cpu"    => $cpu,
	"memory" => round( $peakMemoryUsage, 1 ),
	"disk"   => round( $diskFree, 1 )
] );
?>
