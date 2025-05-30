<?php
// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

	$user_config = "<?php
";
	$user_config .= "define('DB_HOST', '" . addslashes( $_POST['DB_HOST'] ) . "');
";
	$user_config .= "define('DB_USER', '" . addslashes( $_POST['DB_USER'] ) . "');
";
	$user_config .= "define('DB_PASSWORD', '" . addslashes( $_POST['DB_PASSWORD'] ) . "');
";
	$user_config .= "define('APACHE_PATH', '" . addslashes( $_POST['APACHE_PATH'] ) . "');
";
	$user_config .= "define('HTDOCS_PATH', '" . addslashes( $_POST['HTDOCS_PATH'] ) . "');
";
	$user_config .= "define('PHP_PATH', '" . addslashes( $_POST['PHP_PATH'] ) . "');
";
	$user_config .= "\$displaySystemStats = " . ( isset( $_POST['displaySystemStats'] ) ? 'true' : 'false' ) . ";
";
	$user_config .= "\$displayApacheErrorLog = " . ( isset( $_POST['displayApacheErrorLog'] ) ? 'true' : 'false' ) . ";
";
	$user_config .= "\$useAjaxForStats = " . ( isset( $_POST['useAjaxForStats'] ) ? 'true' : 'false' ) . ";
";

	$user_config .= "ini_set('display_errors', " . ( isset( $_POST['displayErrors'] ) ? '1' : '0' ) . ");\n";
	$user_config .= "error_reporting(" . $_POST['errorReportingLevel'] . ");\n";
	$user_config .= "ini_set('log_errors', " . ( isset( $_POST['logErrors'] ) ? '1' : '0' ) . ");\n";

	// Save folders configuration
	if ( ! empty( $_POST['folders_json'] ) ) {
		$foldersPath  = __DIR__ . '/folders.json';
		$foldersArray = json_decode( $_POST['folders_json'], true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			file_put_contents( $foldersPath, json_encode( $foldersArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}
	}

	// Save link templates
	if ( ! empty( $_POST['link_templates'] ) ) {
		$linkTemplatesPath  = __DIR__ . '/link_templates.json';
		$linkTemplatesArray = json_decode( $_POST['link_templates'], true );
		file_put_contents( $linkTemplatesPath, json_encode( $linkTemplatesArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}

	// Handle dock settings
	if ( ! empty( $_POST['dock_json'] ) ) {
		$dockPath  = __DIR__ . '/dock.json';
		$dockArray = json_decode( $_POST['dock_json'], true );
		file_put_contents( $dockPath, json_encode( $dockArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}

	file_put_contents( 'user_config.php', $user_config );

	// Invalidate OPcache to ensure updated config is used immediately
	if ( function_exists( 'opcache_invalidate' ) ) {
		opcache_invalidate( __DIR__ . '/../user_config.php', true );
	}

	// Update php.ini file with submitted settings
	$php_ini_path = php_ini_loaded_file();

	if ( $php_ini_path && file_exists( $php_ini_path ) && is_writable( $php_ini_path ) ) {
		// Backup original ini
		$backup_path = $php_ini_path . '.bak';
		if ( ! file_exists( $backup_path ) ) {
			copy( $php_ini_path, $backup_path );
		}

		$ini_content = file_get_contents( $php_ini_path );

		$display_errors_value  = isset( $_POST['displayErrors'] ) ? 'On' : 'Off';
		$error_reporting_value = $_POST['errorReportingLevel'] ?? 'E_ALL';

		// Patch display_errors
		if ( preg_match( '/^\s*display_errors\s*=.*/mi', $ini_content ) ) {
			$ini_content = preg_replace( '/^\s*display_errors\s*=.*/mi', 'display_errors = ' . $display_errors_value, $ini_content );
		} else {
			$ini_content .= "\ndisplay_errors = " . $display_errors_value;
		}

		// Patch error_reporting
		if ( preg_match( '/^\s*error_reporting\s*=.*/mi', $ini_content ) ) {
			$ini_content = preg_replace( '/^\s*error_reporting\s*=.*/mi', 'error_reporting = ' . $error_reporting_value, $ini_content );
		} else {
			$ini_content .= "\nerror_reporting = " . $error_reporting_value;
		}

		file_put_contents( $php_ini_path, $ini_content );
	}

	header( "Location: index.php" );
	exit;
}
