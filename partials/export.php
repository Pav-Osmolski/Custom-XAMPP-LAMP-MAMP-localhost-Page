<?php
/**
 * Export Files & Database
 *
 * Behaviour:
 * - When included by index.php (already bootstrapped), just render the UI.
 * - When hit directly via AJAX, lightly self-bootstrap (security/config/helpers only).
 *
 * JSON endpoints:
 *   ?action=scan    → list exportable groups/subfolders
 *   ?action=dbs     → list databases
 *   POST action=zip → create archive for chosen subfolder (ZIP preferred, tar.gz fallback)
 *   POST action=dumpdb → dump a database to SQL and compress (ZIP preferred, tar.gz fallback)
 *   ?action=token   → return a fresh CSRF token (for rotating-tokens setups)
 */

/** @var string[] $tooltips */
/** @var string $defaultTooltipMessage */
/** @var string $dbUser */
/** @var string $dbPass */
/** @var bool $phpPathValid */

require_once __DIR__ . '/../config/config.php';

$pageClasses = buildPageViewClasses( $settingsView ?? null );

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? null;

/* ------------------------------------------------------------------ */
/* JSON endpoints                                                      */
/* ------------------------------------------------------------------ */
if ( $action ) {
	header( 'Content-Type: application/json; charset=utf-8' );

	// CSRF for POST actions
	$postActions = [ 'zip', 'dumpdb' ];
	if ( in_array( $action, $postActions, true ) ) {
		if ( defined( 'DEMO_MODE' ) && DEMO_MODE ) {
			echo json_encode( [ 'ok' => false, 'error' => 'Demo mode: export disabled.' ] );
			exit;
		}
		$token = $_POST['csrf'] ?? '';
		if ( ! function_exists( 'csrf_verify' ) || ! csrf_verify( $token ) ) {
			echo json_encode( [ 'ok' => false, 'error' => 'Invalid CSRF token.' ] );
			exit;
		}
	}

	try {
		// Fresh token
		if ( $action === 'token' ) {
			echo json_encode( [ 'ok' => true, 'token' => csrf_get_token() ] );
			exit;
		}

		// List groups/subfolders from folders.json applying urlRules/excludeList
		if ( $action === 'scan' ) {
			$groups = [];
			$cfg    = export_load_folders_json( __DIR__ . '/../config/folders.json' );

			foreach ( $cfg as $i => $entry ) {
				if ( empty( $entry['dir'] ) || empty( $entry['title'] ) ) {
					continue;
				}

				$dir        = (string) $entry['dir'];
				$title      = (string) $entry['title'];
				$exclude    = isset( $entry['excludeList'] ) && is_array( $entry['excludeList'] ) ? $entry['excludeList'] : [];
				$urlRules   = isset( $entry['urlRules'] ) && is_array( $entry['urlRules'] ) ? $entry['urlRules'] : [];
				$subfolders = export_list_subfolders( HTDOCS_PATH, $dir, $exclude, $urlRules );

				$groups[] = [
					'index'      => $i,
					'title'      => $title,
					'dir'        => $dir,
					'subfolders' => array_map(
						static function ( $sf ) {
							return [
								'name'        => $sf['name'],
								'isWordPress' => $sf['isWp'],
								'hasUploads'  => $sf['hasUploads'],
							];
						},
						$subfolders
					),
				];
			}

			echo json_encode( [ 'ok' => true, 'groups' => $groups ] );
			exit;
		}

		// List databases
		if ( $action === 'dbs' ) {
			$host = DB_HOST;
			$user = $dbUser;
			$pass = $dbPass;
			$dbs  = export_list_databases( $host, $user, $pass );

			// In demos, obfuscate names so we don’t leak anything spicy.
			if ( defined( 'DEMO_MODE' ) && DEMO_MODE && function_exists( 'obfuscate_value' ) ) {
				$dbs = array_map(
					static function ( $name ) {
						return obfuscate_value( (string) $name );
					},
					$dbs
				);
			}

			echo json_encode( [ 'ok' => true, 'databases' => $dbs ] );
			exit;
		}

		// Create files archive
		if ( $action === 'zip' && $method === 'POST' ) {
			// Breathing room
			@set_time_limit( 0 );
			@ini_set( 'max_execution_time', '0' );

			$groupIndex = isset( $_POST['group'] ) ? (int) $_POST['group'] : - 1;
			$folderName = trim( (string) ( $_POST['folder'] ?? '' ) );

			$mode           = $_POST['uploadsMode'] ?? 'exclude';         // 'exclude' | 'include' | 'only'
			$includeUploads = ( $mode === 'include' );
			$mediaOnly      = ( $mode === 'only' );

			if ( $folderName === '' || strpos( $folderName, '..' ) !== false || strpbrk( $folderName, "/\\" ) !== false ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Invalid folder name.' ] );
				exit;
			}

			if ( $groupIndex < 0 || $folderName === '' ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Invalid folder selection.' ] );
				exit;
			}

			$cfg = export_load_folders_json( __DIR__ . '/../config/folders.json' );
			if ( ! isset( $cfg[ $groupIndex ] ) ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Group not found.' ] );
				exit;
			}

			$group  = $cfg[ $groupIndex ];
			$dirRel = (string) $group['dir'];

			$absPath = rtrim( HTDOCS_PATH, DIRECTORY_SEPARATOR )
			           . DIRECTORY_SEPARATOR
			           . str_replace(
				           [ '/', '\\' ],
				           DIRECTORY_SEPARATOR,
				           rtrim( $dirRel, "/\\" ) . DIRECTORY_SEPARATOR . $folderName
			           );

			if ( ! is_dir( $absPath ) ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Folder no longer exists on disk.' ] );
				exit;
			}

			$excludes = export_get_default_excludes();
			$exports  = export_ensure_exports_dir();
			$stamp    = date( 'Ymd-His' );
			$safeName = preg_replace( '/[^a-zA-Z0-9._-]+/', '_', $folderName );
			$kind     = $mediaOnly ? 'uploads-' : '';
			$zipName  = "files-{$safeName}-{$kind}{$stamp}.zip";
			$zipAbs   = $exports['abs'] . DIRECTORY_SEPARATOR . $zipName;
			$zipRel   = $exports['rel'] . '/' . $zipName;

			$err = null;
			$ok  = export_zip_directory( $absPath, $zipAbs, $excludes, $includeUploads, $err, $mediaOnly );

			// Pick produced artefact: .zip preferred; .tar.gz or .tar fallback
			$publicHref = null;
			if ( $ok ) {
				if ( is_file( $zipAbs ) ) {
					$publicHref = $zipRel;
				} else {
					$tgzName = preg_replace( '/\.zip$/i', '.tar.gz', $zipName );
					$tgzRel  = $exports['rel'] . '/' . $tgzName;
					$tarName = preg_replace( '/\.zip$/i', '.tar', $zipName );
					$tarRel  = $exports['rel'] . '/' . $tarName;

					$absTgz = $exports['abs'] . DIRECTORY_SEPARATOR . $tgzName;
					$absTar = $exports['abs'] . DIRECTORY_SEPARATOR . $tarName;

					if ( is_file( $absTgz ) ) {
						$publicHref = $tgzRel;
						$zipName    = $tgzName;
					} elseif ( is_file( $absTar ) ) {
						$publicHref = $tarRel;
						$zipName    = $tarName;
					}
				}
			}

			if ( ! $ok || ! $publicHref ) {
				$detail = $err ?: 'Unknown reason';
				echo json_encode( [ 'ok' => false, 'error' => 'Failed to create archive: ' . $detail ] );
				exit;
			}

			echo json_encode( [ 'ok' => true, 'href' => $publicHref, 'name' => $zipName ] );
			exit;
		}

		// Dump database and compress
		if ( $action === 'dumpdb' && $method === 'POST' ) {
			// Breathing room
			@set_time_limit( 0 );
			@ini_set( 'max_execution_time', '0' );

			$db = trim( (string) ( $_POST['db'] ?? '' ) );
			if ( $db === '' ) {
				echo json_encode( [ 'ok' => false, 'error' => 'No database selected.' ] );
				exit;
			}

			$sql     = export_dump_mysql_database( DB_HOST, $dbUser, $dbPass, $db );
			$exports = export_ensure_exports_dir();
			$stamp   = date( 'Ymd-His' );
			$safeDb  = preg_replace( '/[^a-zA-Z0-9._-]+/', '_', $db );
			$sqlName = "db-{$safeDb}-{$stamp}.sql";
			$zipName = "db-{$safeDb}-{$stamp}.zip";
			$sqlAbs  = $exports['abs'] . DIRECTORY_SEPARATOR . $sqlName;
			$zipAbs  = $exports['abs'] . DIRECTORY_SEPARATOR . $zipName;

			file_put_contents( $sqlAbs, $sql );

			if ( file_put_contents( $sqlAbs, $sql ) === false ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Failed to write SQL dump.' ] );
				exit;
			}

			$ok = false;
			if ( class_exists( 'ZipArchive' ) ) {
				$zip      = new ZipArchive();
				$openCode = $zip->open( $zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE );
				if ( $openCode !== true ) {
					echo json_encode( [
						'ok'    => false,
						'error' => 'ZipArchive open failed: ' . export_zip_err( $openCode ) . " ($openCode)"
					] );
					@unlink( $sqlAbs );
					exit;
				}
				$zip->addFile( $sqlAbs, $sqlName );
				$zip->close();
				$ok = true;
			} else {
				// tar(.gz) fallback
				$tarAbs = preg_replace( '/\.zip$/i', '.tar', $zipAbs );
				$tgzAbs = preg_replace( '/\.zip$/i', '.tar.gz', $zipAbs );
				try {
					if ( file_exists( $tarAbs ) ) {
						@unlink( $tarAbs );
					}
					if ( file_exists( $tgzAbs ) ) {
						@unlink( $tgzAbs );
					}

					$tar = new PharData( $tarAbs );
					$tar->addFile( $sqlAbs, $sqlName );
					if ( extension_loaded( 'zlib' ) ) {
						$tar->compress( Phar::GZ );
						unset( $tar );
						@unlink( $tarAbs );
						$zipName = basename( $tgzAbs );
						$ok      = is_file( $tgzAbs );
					} else {
						unset( $tar );
						$zipName = basename( $tarAbs );
						$ok      = is_file( $tarAbs );
					}
				} catch ( Throwable $e ) {
					$ok = false;
				}
			}

			@unlink( $sqlAbs );

			if ( ! $ok ) {
				echo json_encode( [ 'ok' => false, 'error' => 'Failed to create DB archive.' ] );
				exit;
			}

			echo json_encode( [ 'ok' => true, 'href' => $exports['rel'] . '/' . $zipName, 'name' => $zipName ] );
			exit;
		}

		echo json_encode( [ 'ok' => false, 'error' => 'Unknown action.' ] );
		exit;
	} catch ( Throwable $e ) {
		echo json_encode( [ 'ok' => false, 'error' => $e->getMessage() ] );
		exit;
	}
}
?>

<div id="export" class="<?= $pageClasses ?>">
	<?php if ( empty( $settingsView ) ): ?>
		<?php echo render_versioned_assets_with_base(); ?>

		<div class="heading">
			<?= renderHeadingTooltip( 'export', $tooltips, $defaultTooltipMessage, 'h2', 'Export Files & Database' ) ?>
		</div>
	<?php endif; ?>

	<?php if ( $phpPathValid ) { ?>
		<div class="export-grid">
			<!-- Files export -->
			<div class="export-card" role="region" aria-labelledby="export-files-title">
				<h3 id="export-files-title">Files</h3>
				<p id="export-files-help">Select a group and subfolder to export as a compressed archive. WordPress
					uploads are excluded by
					default.</p>
				<?php renderSeparatorLine( 'small' ) ?>
				<form id="export-files-form" method="post" aria-describedby="export-files-help export-files-status" novalidate>
					<input type="hidden" name="csrf" value="<?= htmlspecialchars( csrf_get_token(), ENT_QUOTES ) ?>">
					<div class="row">
						<label for="export-group">Group:</label>
						<select
								id="export-group"
								name="group"
								required
								aria-required="true"
								aria-controls="export-folder"
						></select>
					</div>
					<div class="row" id="export-folder-row">
						<label for="export-folder">Subfolder:</label>
						<select
								id="export-folder"
								name="folder"
								required
								disabled
								aria-required="true"
						>
							<option value="">Select a subfolder…</option>
						</select>
					</div>
					<div class="row" id="uploads-mode-row" style="display:none">
						<fieldset class="radio-group" aria-describedby="uploads-mode-desc">
							<legend>Media:</legend>
							<p id="uploads-mode-desc" class="sr-only">Choose how WordPress uploads should be
								handled.</p>
							<label>
								<input type="radio" name="uploadsMode" value="exclude" checked>
								Exclude uploads
							</label>
							<label>
								<input type="radio" name="uploadsMode" value="include">
								Include uploads
							</label>
							<label>
								<input type="radio" name="uploadsMode" value="only">
								Export uploads only
							</label>
						</fieldset>
					</div>
					<?php renderSeparatorLine( 'small' ) ?>
					<div>
						<button class="button" type="submit" aria-describedby="export-files-submit-desc">Create
							Archive
						</button>
						<span id="export-files-submit-desc" class="sr-only">This will create a compressed archive based on your selections.</span>
						<small
								id="export-files-status"
								class="muted"
								role="status"
								aria-live="polite"
								aria-atomic="true"
						></small>
					</div>
					<br>
				</form>
			</div>

			<!-- Database export -->
			<div class="export-card" role="region" aria-labelledby="export-db-title">
				<h3 id="export-db-title">Database</h3>
				<p id="export-db-help">Choose a database to dump into a compressed archive.</p>
				<?php renderSeparatorLine( 'small' ) ?>
				<form id="export-db-form" method="post" aria-describedby="export-db-help export-db-status" novalidate>
					<input type="hidden" name="csrf" value="<?= htmlspecialchars( csrf_get_token(), ENT_QUOTES ) ?>">
					<div class="row">
						<label for="export-db">Database:</label>
						<select
								id="export-db"
								name="db"
								required
								aria-required="true"
						>
							<option value="">Loading…</option>
						</select>
					</div>
					<?php renderSeparatorLine( 'small' ) ?>
					<div>
						<button class="button" type="submit" aria-describedby="export-db-submit-desc">Export Database
						</button>
						<span id="export-db-submit-desc" class="sr-only">This will create a compressed dump of the selected database.</span>

						<small
								id="export-db-status"
								class="muted"
								role="status"
								aria-live="polite"
								aria-atomic="true"
						></small>
					</div>
					<?php renderSeparatorLine( 'small' ) ?>
				</form>
			</div>
		</div>
	<?php } else { ?>
		<p role="alert"><strong>Note:</strong> The <code>PHP Path</code> is not valid. Please ensure your PHP setup is
			correct.</p>
	<?php } ?>
</div>
