<?php
/**
 * System helpers (no safe_shell_exec defined here)
 *
 * getLegacyOSFlags(), resolveCurrentUser()
 */

/**
 * Detect legacy OS flags.
 *
 * @return array<string, bool>
 */
function getLegacyOSFlags(): array {
	return [
		'isWindows' => strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN',
		'isLinux'   => strtoupper( substr( PHP_OS, 0, 5 ) ) === 'LINUX',
		'isMac'     => strtoupper( substr( PHP_OS, 0, 6 ) ) === 'DARWIN' || strtoupper( substr( PHP_OS, 0, 3 ) ) === 'MAC',
	];
}

/**
 * Attempt to resolve the current OS user.
 *
 * Uses environment variables first, then whoami via safe_shell_exec (if present),
 * then get_current_user() as a final fallback.
 *
 * @return string
 */
function resolveCurrentUser(): string {
	$user = $_SERVER['USERNAME']
	        ?? $_SERVER['USER']
	           ?? ( function_exists( 'safe_shell_exec' ) ? trim( (string) safe_shell_exec( 'whoami' ) ) : '' )
	              ?? get_current_user();

	if ( strpos( $user, '\\' ) !== false ) {
		$parts = explode( '\\', $user, 2 );
		$user  = $parts[1];
	} elseif ( strpos( $user, '@' ) !== false ) {
		$parts = explode( '@', $user, 2 );
		$user  = $parts[0];
	}

	return $user ?: 'Guest';
}
