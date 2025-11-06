<?php
/**
 * Export helpers
 *
 * Provides folder scanning and archive/database export utilities.
 *
 * @author  Pawel Osmolski
 * @version 1.0
 */

/**
 * Returns the default folder names to exclude when zipping.
 *
 * Override by defining EXPORT_EXCLUDE (array) in user_config.php.
 *
 * @return string[] Normalised, unique folder names (no empties).
 */
function export_get_default_excludes(): array {
	$defaults = [ '.git', '.idea', '.vscode', 'node_modules', 'vendor', 'dist', 'build' ];
	if ( defined( 'EXPORT_EXCLUDE' ) && is_array( EXPORT_EXCLUDE ) ) {
		$items = array_map( function ( $v ) {
			return trim( (string) $v );
		}, EXPORT_EXCLUDE );
		$items = array_values( array_unique( array_filter( $items, function ( $v ) {
			return $v !== '';
		} ) ) );

		return $items;
	}

	return $defaults;
}

/**
 * Load folders.json and return as an array.
 *
 * @param string $path Absolute path to folders.json
 *
 * @return array<int, array<string,mixed>> Parsed config; empty array on missing/invalid JSON.
 */
function export_load_folders_json( string $path ): array {
	if ( ! is_file( $path ) ) {
		return [];
	}
	$json = @file_get_contents( $path );
	if ( $json === false || $json === '' ) {
		return [];
	}
	$data = json_decode( $json, true );

	return is_array( $data ) ? $data : [];
}

/**
 * Parse a regex literal string like "/^wp-/" into a valid pattern.
 * If input looks like a delimited regex already, it is returned as-is.
 *
 * @param string $raw
 *
 * @return string|null
 */
function export_parse_regex_literal( string $raw ): ?string {
	$raw = trim( $raw );
	if ( strlen( $raw ) >= 2 && $raw[0] === '/' && strrpos( $raw, '/' ) !== 0 ) {
		return $raw;
	}

	return null;
}

/**
 * Detect if a directory looks like a WordPress site root.
 *
 * @param string $absPath
 *
 * @return bool
 */
function export_is_wp_root( string $absPath ): bool {
	return is_file( $absPath . DIRECTORY_SEPARATOR . 'wp-config.php' )
	       || is_dir( $absPath . DIRECTORY_SEPARATOR . 'wp-content' );
}

/**
 * Detect if a WordPress uploads folder exists under a given root.
 *
 * @param string $absPath
 *
 * @return bool
 */
function export_has_wp_uploads( string $absPath ): bool {
	return is_dir( $absPath . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' );
}

/**
 * List subfolders of a base "dir" entry, applying excludeList and urlRules.match.
 *
 * @param string $htdocsPath Absolute HTDOCS path base.
 * @param string $dirEntry Relative dir from folders.json.
 * @param string[] $excludeList Names to exclude (exact match).
 * @param array $urlRules Optional urlRules with "match" (pre-delimited regex string).
 *
 * @return array<int, array{name:string,abs:string,isWp:bool,hasUploads:bool}>
 */
function export_list_subfolders( string $htdocsPath, string $dirEntry, array $excludeList = [], array $urlRules = [] ): array {
	$result  = [];
	$baseAbs = rtrim( $htdocsPath, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . str_replace( [
			'/',
			'\\'
		], DIRECTORY_SEPARATOR, $dirEntry );
	if ( ! is_dir( $baseAbs ) ) {
		return $result;
	}

	$excludeSet = array_flip( $excludeList );
	$matchRaw   = isset( $urlRules['match'] ) && is_string( $urlRules['match'] ) ? $urlRules['match'] : null;
	$pattern    = $matchRaw ? export_parse_regex_literal( $matchRaw ) : null;

	$items = @scandir( $baseAbs ) ?: [];
	foreach ( $items as $name ) {
		if ( $name === '.' || $name === '..' ) {
			continue;
		}
		if ( isset( $excludeSet[ $name ] ) ) {
			continue;
		}
		$abs = $baseAbs . DIRECTORY_SEPARATOR . $name;
		if ( ! is_dir( $abs ) ) {
			continue;
		}

		if ( $pattern !== null ) {
			$pm = @preg_match( $pattern, $name );
			if ( $pm !== 1 ) {
				continue;
			}
		}

		$isWp       = export_is_wp_root( $abs );
		$hasUploads = $isWp && export_has_wp_uploads( $abs );
		$result[]   = [ 'name' => $name, 'abs' => $abs, 'isWp' => $isWp, 'hasUploads' => $hasUploads ];
	}

	usort( $result, function ( $a, $b ) {
		return strnatcasecmp( $a['name'], $b['name'] );
	} );

	return $result;
}

/**
 * Ensure the public exports directory exists and is writable.
 * Uses /dist/exports so the browser can download directly.
 *
 * @return array{abs:string,rel:string} Absolute path and web-relative path.
 */
function export_ensure_exports_dir(): array {
	$root    = dirname( __DIR__, 2 );
	$absPath = $root . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'exports';
	if ( ! is_dir( $absPath ) ) {
		@mkdir( $absPath, 0775, true );
	}

	return [ 'abs' => $absPath, 'rel' => 'dist/exports' ];
}

/**
 * Create a compressed archive of a project directory for download.
 *
 * Prefers ZipArchive; falls back to .tar.gz via PharData (or .tar if zlib is absent).
 *
 * @param string $sourceAbs Absolute path to the directory to archive.
 * @param string $zipAbs Preferred .zip path; fallback may create .tar.gz or .tar with the same basename.
 * @param string[] $excludeFolders Folder names to exclude at any depth.
 * @param bool $includeUploads Include wp-content/uploads when the source is a WP root.
 * @param string|null &$error Output error message on failure.
 * @param bool $onlyUploads When true, export only wp-content/uploads for WP sites.
 *
 * @return bool True if an archive was created (either $zipAbs or the same path with .tar[.gz]).
 */
function export_zip_directory( string $sourceAbs, string $zipAbs, array $excludeFolders, bool $includeUploads, ?string &$error = null, bool $onlyUploads = false ): bool {
	$error = null;
	if ( ! is_dir( $sourceAbs ) ) {
		$error = 'Source folder does not exist.';

		return false;
	}

	$sourceAbs    = rtrim( $sourceAbs, DIRECTORY_SEPARATOR );
	$baseLen      = strlen( $sourceAbs ) + 1;
	$patterns     = export_build_exclude_patterns( $excludeFolders );
	$excludeSet   = array_flip( $patterns['segments'] );
	$wpUploadsRel = 'wp-content' . DIRECTORY_SEPARATOR . 'uploads';
	$isWp         = export_is_wp_root( $sourceAbs );
	$uploadsOk    = $isWp && $includeUploads;

	if ( $onlyUploads ) {
		$uploadsAbs = $sourceAbs . DIRECTORY_SEPARATOR . $wpUploadsRel;
		if ( ! $isWp || ! is_dir( $uploadsAbs ) ) {
			$error = 'Uploads folder not found for this selection.';

			return false;
		}
		$sourceAbs  = $uploadsAbs;
		$baseLen    = strlen( $sourceAbs ) + 1;
		$excludeSet = [];
	}

	$iter = function () use ( $sourceAbs, $baseLen, $excludeSet, $isWp, $uploadsOk, $wpUploadsRel, $onlyUploads ) {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $sourceAbs, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ( $it as $path => $info ) {
			$rel = substr( $path, $baseLen );
			if ( ! $onlyUploads ) {
				$segments   = explode( DIRECTORY_SEPARATOR, strtolower( $rel ) );
				$excludeHit = false;
				foreach ( $segments as $seg ) {
					if ( isset( $excludeSet[ $seg ] ) ) {
						$excludeHit = true;
						break;
					}
				}
				if ( $excludeHit ) {
					continue;
				}
				if ( $isWp && ! $uploadsOk ) {
					if ( str_starts_with( $rel, $wpUploadsRel ) ) {
						continue;
					}
				}
			}
			yield [ 'path' => $path, 'rel' => $rel, 'isDir' => $info->isDir() ];
		}
	};

	$dstDir = dirname( $zipAbs );
	if ( ! is_dir( $dstDir ) && ! @mkdir( $dstDir, 0775, true ) ) {
		$error = 'Cannot create destination directory: ' . $dstDir;

		return false;
	}
	if ( ! is_writable( $dstDir ) ) {
		$error = 'Destination directory is not writable: ' . $dstDir;

		return false;
	}

	if ( class_exists( 'ZipArchive' ) ) {
		$zip      = new ZipArchive();
		$openCode = $zip->open( $zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		if ( $openCode !== true ) {
			$error = 'ZipArchive open failed: ' . export_zip_err( (int) $openCode ) . ' (' . (int) $openCode . ')';

			return false;
		}
		$added = 0;
		foreach ( $iter() as $entry ) {
			$rel = str_replace( '\\', '/', $entry['rel'] );
			if ( $entry['isDir'] ) {
				$zip->addEmptyDir( rtrim( $rel, '/' ) );
			} else {
				$zip->addFile( $entry['path'], $rel );
			}
			$added ++;
		}
		$zip->close();
		if ( ! is_file( $zipAbs ) ) {
			$error = 'ZIP was not created (no files were added).';

			return false;
		}

		return true;
	}

	if ( ! extension_loaded( 'phar' ) ) {
		$error = 'Neither ZipArchive nor Phar are available.';

		return false;
	}

	$roRaw = ini_get( 'phar.readonly' );
	$roVal = filter_var( $roRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
	if ( $roVal === true ) {
		$error = 'Phar fallback blocked: set phar.readonly=0 in php.ini.';

		return false;
	}

	$wantGzip = extension_loaded( 'zlib' );
	$tarAbs   = preg_replace( '/\.zip$/i', '.tar', $zipAbs );
	$tgzAbs   = preg_replace( '/\.zip$/i', '.tar.gz', $zipAbs );

	try {
		if ( file_exists( $tarAbs ) ) {
			@unlink( $tarAbs );
		}
		if ( file_exists( $tgzAbs ) ) {
			@unlink( $tgzAbs );
		}

		$tar   = new PharData( $tarAbs );
		$added = 0;
		foreach ( $iter() as $entry ) {
			$rel = str_replace( '\\', '/', $entry['rel'] );
			if ( $entry['isDir'] ) {
				$tar->addEmptyDir( rtrim( $rel, '/' ) );
			} else {
				$tar->addFile( $entry['path'], $rel );
			}
			$added ++;
		}

		if ( $wantGzip ) {
			$tar->compress( Phar::GZ );
			unset( $tar );
			@unlink( $tarAbs );
			if ( ! is_file( $tgzAbs ) ) {
				$error = 'TAR.GZ was not created (unknown reason).';

				return false;
			}

			return true;
		}

		unset( $tar );
		if ( ! is_file( $tarAbs ) ) {
			$error = 'TAR was not created (unknown reason).';

			return false;
		}

		return true;
	} catch ( Throwable $e ) {
		$error = 'Phar error: ' . $e->getMessage();

		return false;
	}
}

/**
 * Dump a MySQL database to SQL text (schema + data).
 *
 * @param string $host MySQL host.
 * @param string $user MySQL username.
 * @param string $pass MySQL password.
 * @param string $dbName Database name to dump.
 * @param null|string $charset Connection charset (default 'utf8mb4'); set null to skip.
 *
 * @return string Complete SQL dump.
 * @throws RuntimeException on init/connect failure.
 */
function export_dump_mysql_database( string $host, string $user, string $pass, string $dbName, ?string $charset = 'utf8mb4' ): string {
	$mysqli = mysqli_init();
	if ( ! $mysqli ) {
		throw new RuntimeException( 'mysqli_init() failed' );
	}
	if ( ! $mysqli->real_connect( $host, $user, $pass, $dbName, 0, null, 0 ) ) {
		throw new RuntimeException( 'MySQL connect error: ' . mysqli_connect_error() );
	}
	if ( $charset ) {
		$mysqli->set_charset( $charset );
	}

	$e = function ( string $s ) {
		return '`' . str_replace( '`', '``', $s ) . '`';
	};

	$out = "-- Dump of database {$e($dbName)}\n";
	$out .= "-- Generated: " . date( 'Y-m-d H:i:s' ) . "\n\n";
	$out .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
	$out .= "SET time_zone = '+00:00';\n";
	$out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

	$tables = [];
	if ( $res = $mysqli->query( "SHOW FULL TABLES WHERE Table_type='BASE TABLE'" ) ) {
		while ( $row = $res->fetch_row() ) {
			$tables[] = (string) $row[0];
		}
		$res->free();
	}

	foreach ( $tables as $table ) {
		if ( $res = $mysqli->query( "SHOW CREATE TABLE " . $e( $table ) ) ) {
			$row    = $res->fetch_array( MYSQLI_NUM );
			$create = $row[1] ?? '';
			$res->free();
		} else {
			$out .= "-- ERROR: SHOW CREATE TABLE failed for {$e($table)}: " . $mysqli->error . "\n\n";
			continue;
		}

		$out .= "DROP TABLE IF EXISTS " . $e( $table ) . ";\n";
		$out .= $create . ";\n\n";

		$columns = [];
		if ( $colsRes = $mysqli->query( "SHOW COLUMNS FROM " . $e( $table ) ) ) {
			while ( $f = $colsRes->fetch_assoc() ) {
				$columns[] = $f['Field'];
			}
			$colsRes->free();
		}

		$res = $mysqli->query( "SELECT * FROM " . $e( $table ), MYSQLI_USE_RESULT );
		if ( ! $res ) {
			$out .= "-- ERROR: SELECT * failed for {$e($table)}: " . $mysqli->error . "\n\n";
			continue;
		}

		$batchSize = 200;
		$values    = [];
		$rowCount  = 0;

		$flush = function () use ( &$out, $table, &$values, $columns, $e ) {
			if ( ! $values ) {
				return;
			}
			$colsSql = $columns ? ( " (" . implode( ',', array_map( $e, $columns ) ) . ")" ) : '';
			$out     .= "INSERT INTO " . $e( $table ) . $colsSql . " VALUES\n" . implode( ",\n", $values ) . ";\n";
			$values  = [];
		};

		while ( $row = $res->fetch_assoc() ) {
			if ( ! $columns ) {
				$columns = array_keys( $row );
			}
			$vals = [];
			foreach ( $columns as $col ) {
				$v      = $row[ $col ] ?? null;
				$vals[] = $v === null ? "NULL" : "'" . $mysqli->real_escape_string( $v ) . "'";
			}
			$values[] = '(' . implode( ',', $vals ) . ')';
			if ( count( $values ) >= $batchSize ) {
				$flush();
			}
			$rowCount ++;
		}
		$res->free();

		if ( $values ) {
			$flush();
		}
		if ( $rowCount > 0 ) {
			$out .= "\n";
		}
	}

	$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
	$mysqli->close();

	return $out;
}

/**
 * List databases available to the configured user (excludes system schemas).
 *
 * @param string $host
 * @param string $user
 * @param string $pass
 *
 * @return string[] Database names (sorted natural, case-insensitive).
 */
function export_list_databases( string $host, string $user, string $pass ): array {
	$mysqli = @new mysqli( $host, $user, $pass );
	if ( $mysqli->connect_error ) {
		return [];
	}
	$mysqli->set_charset( 'utf8mb4' );

	$out = [];
	if ( $res = $mysqli->query( 'SHOW DATABASES' ) ) {
		while ( $row = $res->fetch_array( MYSQLI_NUM ) ) {
			$name = (string) $row[0];
			if ( in_array( $name, [ 'information_schema', 'performance_schema', 'mysql', 'sys' ], true ) ) {
				continue;
			}
			$out[] = $name;
		}
		$res->free();
	}
	$mysqli->close();
	sort( $out, SORT_NATURAL | SORT_FLAG_CASE );

	return $out;
}

/**
 * Map ZipArchive::open() error codes to readable messages.
 *
 * @param int $code ZipArchive error/status code (e.g., ZipArchive::ER_OPEN).
 *
 * @return string Human-friendly description for the given error code.
 */
function export_zip_err( int $code ): string {
	switch ( $code ) {
		case ZipArchive::ER_EXISTS:
			return 'File already exists';
		case ZipArchive::ER_INCONS:
			return 'Archive inconsistent';
		case ZipArchive::ER_INVAL:
			return 'Invalid argument';
		case ZipArchive::ER_MEMORY:
			return 'Out of memory';
		case ZipArchive::ER_NOENT:
			return 'No such file';
		case ZipArchive::ER_NOZIP:
			return 'Not a zip archive';
		case ZipArchive::ER_OPEN:
			return 'Cannot open file';
		case ZipArchive::ER_READ:
			return 'Read error';
		case ZipArchive::ER_SEEK:
			return 'Seek error';
		default:
			return 'Unknown error';
	}
}

/**
 * Build a unified, segment-style exclusion pattern set for both PHP and external archivers.
 *
 * Each entry in $names is treated as a file or folder name to be excluded at any depth.
 * Returns:
 *  - 'segments': lowercase array for internal (PHP) use when walking directories.
 *  - 'seven': array of -xr! flags for 7-Zip.
 *  - 'zip': array of patterns for zip (-x).
 *
 * @param string[] $names Simple names from EXPORT_EXCLUDE or user_config.php.
 *
 * @return array{segments:string[],seven:string[],zip:string[]}
 */
function export_build_exclude_patterns( array $names ): array {
	$segments = [];
	$seven    = [];
	$zip      = [];

	foreach ( $names as $name ) {
		$name = trim( (string) $name );
		if ( $name === '' ) {
			continue;
		}

		// Normalise and lowercase for PHP segment matching
		$seg        = strtolower( str_replace( [ '\\', '/' ], '', $name ) );
		$segments[] = $seg;

		// 7-Zip exclusion flag: -xr!name
		$seven[] = '-xr!' . $name;

		// zip exclusion patterns: exclude both files and directories at any depth
		$zip[] = $name;
		$zip[] = $name . '/*';
		$zip[] = '*/' . $name;
		$zip[] = '*/' . $name . '/*';
	}

	return [
		'segments' => array_values( array_unique( $segments ) ),
		'seven'    => array_values( array_unique( $seven ) ),
		'zip'      => array_values( array_unique( $zip ) ),
	];
}

/**
 * Check whether safe_shell_exec() or exec() is disabled / unavailable.
 *
 * @return bool
 */
function export_exec_disabled(): bool {
	// If shell_exec itself is missing, we can't do external calls.
	if ( ! function_exists( 'shell_exec' ) ) {
		return true;
	}

	$disabled = (string) ini_get( 'disable_functions' );
	if ( $disabled !== '' ) {
		$list = array_map( 'trim', explode( ',', $disabled ) );
		if ( in_array( 'shell_exec', $list, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Find the first available executable from a list of candidate names.
 *
 * Works cross-platform (Windows, macOS, Linux).
 * - On Windows, scans PATH manually, checks common 7-Zip locations,
 *   and finally uses `where` via safe_shell_exec() as a fallback.
 * - On POSIX systems, uses `command -v` via safe_shell_exec().
 *
 * @param string[] $candidates Binary names to try.
 *
 * @return string|null Full path to the first available binary, or null if none found.
 */
function export_find_executable( array $candidates ): ?string {
	$debugExport = false; // set true to log discovery steps

	if ( export_exec_disabled() ) {
		return null;
	}

	$isWindows       = DIRECTORY_SEPARATOR === '\\';
	$cleanCandidates = array_filter( array_map( 'trim', $candidates ) );

	if ( ! $cleanCandidates ) {
		return null;
	}

	if ( $isWindows ) {
		// Manual PATH scan
		$path = (string) getenv( 'PATH' );
		$dirs = array_filter( array_map( 'trim', explode( PATH_SEPARATOR, $path ) ) );

		$probeNames = [];
		foreach ( $cleanCandidates as $name ) {
			$probeNames[] = $name;
			if ( ! str_ends_with( strtolower( $name ), '.exe' ) ) {
				$probeNames[] = $name . '.exe';
			}
		}

		foreach ( $dirs as $dir ) {
			foreach ( $probeNames as $file ) {
				$full = rtrim( $dir, "\\/" ) . DIRECTORY_SEPARATOR . $file;
				if ( is_file( $full ) ) {
					if ( $debugExport ) {
						error_log( "[export] Found executable via PATH: $full" );
					}

					return $full;
				}
			}
		}

		// Common 7-Zip install paths
		$commonSevenZip = [
			'C:\\Program Files\\7-Zip\\7z.exe',
			'C:\\Program Files (x86)\\7-Zip\\7z.exe',
		];
		foreach ( $commonSevenZip as $full ) {
			if ( is_file( $full ) ) {
				if ( $debugExport ) {
					error_log( "[export] Found 7-Zip at common path: $full" );
				}

				return $full;
			}
		}

		// Fallback to `where`, via safe_shell_exec
		foreach ( $cleanCandidates as $name ) {
			$cmd    = 'where ' . escapeshellarg( $name ) . ' 2>NUL';
			$output = safe_shell_exec( $cmd );
			if ( $output ) {
				$lines = array_filter( array_map( 'trim', explode( "\n", $output ) ) );
				$path  = reset( $lines );
				if ( $path && is_file( $path ) ) {
					if ( $debugExport ) {
						error_log( "[export] Found executable via where: $path" );
					}

					return $path;
				}
			}
		}

		// Still nothing
		return null;
	}

	// POSIX: use `command -v`
	foreach ( $cleanCandidates as $name ) {
		$cmd    = 'command -v ' . escapeshellarg( $name ) . ' 2>/dev/null';
		$output = safe_shell_exec( $cmd );
		if ( $output ) {
			$lines = array_filter( array_map( 'trim', explode( "\n", $output ) ) );
			$path  = reset( $lines );
			if ( $path && file_exists( $path ) ) {
				if ( $debugExport ) {
					error_log( "[export] Found executable via command -v: $path" );
				}

				return $path;
			}
		}
	}

	return null;
}

/**
 * Create an archive of a directory using an external archiver (7-Zip preferred, zip as fallback).
 *
 * Respects:
 * - $excludeFolders: names to exclude at any depth (files or directories),
 *   similar in spirit to export_zip_directory() where any matching path segment is skipped.
 * - WordPress uploads include/exclude/only behaviour.
 *
 * @param string $sourceAbs Absolute source directory.
 * @param string $zipAbs Absolute output .zip path.
 * @param string[] $excludeFolders Folder/file names to exclude at any depth.
 * @param bool $includeUploads Whether to keep wp-content/uploads when $sourceAbs is a WP root.
 * @param null|string $error Error description on failure.
 * @param bool $onlyUploads When true, archive only wp-content/uploads for WP sites.
 *
 * @return bool True on success, false on failure.
 */
function export_external_zip_directory(
	string $sourceAbs,
	string $zipAbs,
	array $excludeFolders,
	bool $includeUploads,
	?string &$error = null,
	bool $onlyUploads = false
): bool {
	$error = null;

	if ( ! is_dir( $sourceAbs ) ) {
		$error = 'Source folder does not exist.';

		return false;
	}

	$sourceAbs    = rtrim( $sourceAbs, DIRECTORY_SEPARATOR );
	$isWp         = export_is_wp_root( $sourceAbs );
	$wpUploadsRel = 'wp-content' . DIRECTORY_SEPARATOR . 'uploads';

	// If we are exporting uploads only, switch source to uploads and ignore excludes
	// (to mirror the PHP ZipArchive implementation).
	if ( $onlyUploads ) {
		$uploadsAbs = $sourceAbs . DIRECTORY_SEPARATOR . $wpUploadsRel;
		if ( ! $isWp || ! is_dir( $uploadsAbs ) ) {
			$error = 'Uploads folder not found for this selection.';

			return false;
		}
		$sourceAbs      = $uploadsAbs;
		$excludeFolders = [];
	}

	$dstDir = dirname( $zipAbs );
	if ( ! is_dir( $dstDir ) && ! @mkdir( $dstDir, 0775, true ) ) {
		$error = 'Cannot create destination directory: ' . $dstDir;

		return false;
	}
	if ( ! is_writable( $dstDir ) ) {
		$error = 'Destination directory is not writable: ' . $dstDir;

		return false;
	}

	$isWindows = DIRECTORY_SEPARATOR === '\\';

	// Build unified exclusion sets from default excludes
	$patterns = export_build_exclude_patterns( $excludeFolders );

	$sevenExcludeFlags  = $patterns['seven'];
	$zipExcludePatterns = $patterns['zip'];

	// Handle WordPress uploads exclusion (when not including them and not uploads-only).
	if ( $isWp && ! $includeUploads && ! $onlyUploads ) {
		$uploadsKey = 'wp-content/uploads';

		$sevenExcludeFlags[] = '-xr!' . $uploadsKey;

		$zipExcludePatterns[] = $uploadsKey;
		$zipExcludePatterns[] = $uploadsKey . '/*';
		$zipExcludePatterns[] = '*/' . $uploadsKey;
		$zipExcludePatterns[] = '*/' . $uploadsKey . '/*';
	}

	$sevenCandidates = $isWindows
		? [ '7z.exe', '7za.exe', '7zz.exe', '7z' ]
		: [ '7z', '7za', '7zz' ];
	$zipCandidates   = $isWindows
		? [ 'zip.exe', 'zip' ]
		: [ 'zip' ];

	$cwd = getcwd();
	try {
		if ( ! @chdir( $sourceAbs ) ) {
			$error = 'Failed to change directory for archiving.';

			return false;
		}

		// 1) Try 7-Zip
		$sevenPath = export_find_executable( $sevenCandidates );
		if ( $sevenPath !== null ) {
			$cmd = escapeshellarg( $sevenPath ) . ' a -tzip ' . escapeshellarg( $zipAbs ) . ' .';
			if ( $sevenExcludeFlags ) {
				$cmd .= ' ' . implode( ' ', $sevenExcludeFlags );
			}

			$output = safe_shell_exec( $cmd );
			if ( $output !== null && is_file( $zipAbs ) ) {
				return true;
			}

			$error = '7-Zip failed or produced no output.';
		}

		// 2) Try system zip
		$zipPath = export_find_executable( $zipCandidates );
		if ( $zipPath !== null ) {
			$cmd = escapeshellarg( $zipPath ) . ' -r ' . escapeshellarg( $zipAbs ) . ' .';

			if ( $zipExcludePatterns ) {
				$cmd .= ' -x ' . implode(
						' ',
						array_map(
							static function ( string $p ): string {
								return escapeshellarg( $p );
							},
							$zipExcludePatterns
						)
					);
			}

			$output = safe_shell_exec( $cmd );
			if ( $output !== null && is_file( $zipAbs ) ) {
				return true;
			}

			$error = 'System zip failed or produced no output.';

			return false;
		}

		$error = 'No external archiver (7-Zip or zip) found on PATH.';

		return false;
	} finally {
		if ( $cwd && is_string( $cwd ) && $cwd !== '' ) {
			@chdir( $cwd );
		}
	}
}

/**
 * Compress a single file using an external archiver (7-Zip preferred, zip fallback).
 *
 * @param string $fileAbs Absolute path to the input file.
 * @param string $zipAbs Absolute output .zip path.
 * @param null|string $error Error description on failure.
 *
 * @return bool True on success, false on failure.
 */
function export_external_archive_file(
	string $fileAbs,
	string $zipAbs,
	?string &$error = null
): bool {
	$error = null;

	if ( ! is_file( $fileAbs ) ) {
		$error = 'Input file does not exist.';

		return false;
	}

	$dstDir = dirname( $zipAbs );
	if ( ! is_dir( $dstDir ) && ! @mkdir( $dstDir, 0775, true ) ) {
		$error = 'Cannot create destination directory: ' . $dstDir;

		return false;
	}
	if ( ! is_writable( $dstDir ) ) {
		$error = 'Destination directory is not writable: ' . $dstDir;

		return false;
	}

	$isWindows       = DIRECTORY_SEPARATOR === '\\';
	$sevenCandidates = $isWindows
		? [ '7z.exe', '7za.exe', '7zz.exe', '7z' ]
		: [ '7z', '7za', '7zz' ];
	$zipCandidates   = $isWindows
		? [ 'zip.exe', 'zip' ]
		: [ 'zip' ];

	$baseDir  = dirname( $fileAbs );
	$fileName = basename( $fileAbs );

	$cwd = getcwd();
	try {
		if ( ! @chdir( $baseDir ) ) {
			$error = 'Failed to change directory for archiving.';

			return false;
		}

		// 1) Try 7-Zip
		$sevenPath = export_find_executable( $sevenCandidates );
		if ( $sevenPath !== null ) {
			$cmd    = escapeshellarg( $sevenPath ) . ' a -tzip ' . escapeshellarg( $zipAbs ) . ' ' . escapeshellarg( $fileName );
			$output = safe_shell_exec( $cmd );
			if ( $output !== null && is_file( $zipAbs ) ) {
				return true;
			}

			$error = '7-Zip failed or produced no output.';
		}

		// 2) Try system zip
		$zipPath = export_find_executable( $zipCandidates );
		if ( $zipPath !== null ) {
			$cmd    = escapeshellarg( $zipPath ) . ' -j ' . escapeshellarg( $zipAbs ) . ' ' . escapeshellarg( $fileName );
			$output = safe_shell_exec( $cmd );
			if ( $output !== null && is_file( $zipAbs ) ) {
				return true;
			}

			$error = 'System zip failed or produced no output.';

			return false;
		}

		$error = 'No external archiver (7-Zip or zip) found on PATH.';

		return false;
	} finally {
		if ( $cwd && is_string( $cwd ) && $cwd !== '' ) {
			@chdir( $cwd );
		}
	}
}
