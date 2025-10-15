<?php
/**
 * Filesystem helpers
 *
 * normalise_subdir(), list_subdirs()
 */

/**
 * Normalise a configured subdirectory safely below HTDOCS_PATH.
 *
 * Uses normalise_path() for slash handling, prevents traversal,
 * and anchors the result under HTDOCS_PATH.
 *
 * @param string $relative
 *
 * @return array{dir:string,error:?string}
 */
function normalise_subdir( $relative ) {
	$relative = (string) $relative;
	$subdir   = trim( normalise_path( $relative ), DIRECTORY_SEPARATOR );
	if ( strpos( $subdir, '..' ) !== false ) {
		return [ 'dir' => '', 'error' => 'Security: directory traversal detected in "dir".' ];
	}
	$abs = rtrim( HTDOCS_PATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR;

	return [ 'dir' => $abs, 'error' => null ];
}

/**
 * List immediate subdirectories of a directory, skipping dot entries and sorting naturally.
 *
 * @param string $absDir
 *
 * @return array<int, string> Folder basenames
 */
function list_subdirs( $absDir ) {
	if ( ! is_dir( $absDir ) ) {
		return [];
	}
	$out = [];
	$it  = new DirectoryIterator( $absDir );
	foreach ( $it as $f ) {
		if ( $f->isDot() ) {
			continue;
		}
		if ( $f->isDir() ) {
			$out[] = $f->getBasename();
		}
	}
	natcasesort( $out );

	return array_values( $out );
}
