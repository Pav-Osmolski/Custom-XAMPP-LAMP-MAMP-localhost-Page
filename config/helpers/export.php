<?php
/**
 * Export helpers
 *
 * Provides folder scanning and archive/database export utilities.
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
	$root    = dirname( __DIR__ );
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
	$excludeSet   = array_flip( array_map( 'strtolower', $excludeFolders ) );
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
