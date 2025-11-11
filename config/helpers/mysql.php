<?php
/**
 * MySQL Helpers
 *
 * Utility functions for working with MySQL connections and metadata.
 * Provides a robust wrapper around mysqli with strict and non-strict modes,
 * automatic charset initialisation, and optional database selection.
 * Also includes helpers to normalise and simplify MySQL-family version
 * strings (MariaDB, Percona, Aurora, etc.) for clean display in the UI.
 *
 * @author  Pawel Osmolski
 * @version 1.0
 */

/**
 * Create a secure MySQLi connection with utf8mb4 charset.
 *
 * @param array $opts {
 *   Optional connection options.
 *
 * @type string $user Database username. Defaults to global $dbUser.
 * @type string $pass Database password. Defaults to global $dbPass.
 * @type string $db Database name to select. Optional.
 * @type bool $strictMode Enable MYSQLI_REPORT_STRICT temporarily. Default true.
 * }
 *
 * @return mysqli
 * @throws Exception On connection, charset, or database selection failure (in strict mode).
 */
function getMysqliConnection( array $opts = [] ): mysqli {
	global $dbUser, $dbPass;

	$user       = isset( $opts['user'] ) ? $opts['user'] : $dbUser;
	$pass       = isset( $opts['pass'] ) ? $opts['pass'] : $dbPass;
	$db         = isset( $opts['db'] ) ? $opts['db'] : null;
	$strictMode = array_key_exists( 'strictMode', $opts ) ? (bool) $opts['strictMode'] : true;

	$prevFlags = mysqli_report( MYSQLI_REPORT_OFF );
	if ( $strictMode ) {
		mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
	}

	try {
		// Quiet constructor in non-strict mode to avoid HTML warnings in output
		$mysqli = $strictMode
			? new mysqli( DB_HOST, $user, $pass )
			: @new mysqli( DB_HOST, $user, $pass );

		// In non-strict mode, if connect failed, return as-is so caller can read ->connect_error
		if ( ! $strictMode && $mysqli->connect_errno ) {
			return $mysqli;
		}

		$mysqli->set_charset( 'utf8mb4' );

		if ( $db !== null ) {
			$mysqli->select_db( $db );
		}

		return $mysqli;

	} catch ( mysqli_sql_exception $e ) {
		throw new Exception( 'MySQL connection failed: ' . $e->getMessage() );
	} finally {
		if ( $strictMode ) {
			mysqli_report( $prevFlags );
		}
	}
}

/**
 * Check MySQL credential validity for host, user, and password.
 *
 * Runs a non-strict connection and inspects mysqli->connect_error to infer
 * which parts are valid. Where MySQL does not differentiate, a heuristic is used:
 * - If error contains "access denied" and "using password: YES", assume user exists and password is wrong.
 * - If error contains "access denied" and "using password: NO", assume username is wrong or password missing.
 *
 * @param string|null $key Optional. One of 'host', 'user', or 'pass'.
 *                         If provided, returns a boolean for that specific status.
 *                         If null, returns an associative array with all three.
 *
 * @return array|bool {
 * @type bool $host True if DB_HOST is reachable.
 * @type bool $user True if DB_USER appears valid.
 * @type bool $pass True if DB_PASSWORD appears valid.
 * }
 */
function checkMysqlCredentialsStatus( string $key = null ): array|bool {
	$status = [
		'host' => false,
		'user' => false,
		'pass' => false,
	];

	try {
		$mysqli = getMysqliConnection( [ 'strictMode' => false ] );

		// Successful connection → all valid
		if ( ! $mysqli->connect_errno ) {
			$status = [ 'host' => true, 'user' => true, 'pass' => true ];
			$mysqli->close();
		} else {
			$error = strtolower( $mysqli->connect_error );

			// Host-level problems
			if (
				strpos( $error, 'unknown host' ) !== false ||
				strpos( $error, 'no route to host' ) !== false ||
				strpos( $error, 'can\'t connect to mysql server' ) !== false ||
				strpos( $error, 'connection refused' ) !== false
			) {
				$status['host'] = false;
			} // Access denied → host reachable. Apply heuristic for user vs pass.
			elseif ( strpos( $error, 'access denied' ) !== false ) {
				$status['host'] = true;

				if ( strpos( $error, 'using password: no' ) !== false ) {
					// Likely bad username, password not used
					$status['user'] = false;
					$status['pass'] = true;
				} else {
					// Password was provided. Assume username exists and password is wrong.
					$status['user'] = true;
					$status['pass'] = false;
				}
			} // Other errors: assume host reachable but credentials suspect
			else {
				$status['host'] = true;
			}
		}

	} catch ( Exception $e ) {
		// Strict mode or unexpected failure
		$status = [ 'host' => false, 'user' => false, 'pass' => false ];
	}

	if ( $key !== null ) {
		return isset( $status[ $key ] ) ? $status[ $key ] : false;
	}

	return $status;
}

/**
 * Normalise MySQL-family version strings from mysqli->server_info.
 *
 * Examples:
 * - "10.11.7-MariaDB-1:10.11.7+maria~deb11-log"  -> "10.11.7-MariaDB"
 * - "8.0.36-0.el9"                               -> "8.0.36"
 * - "5.7.42-cll-lve"                             -> "5.7.42"
 * - "8.0.36-28"          (Percona)               -> "8.0.36-Percona"
 * - "8.0.33-ndb-8.0.33"  (MySQL Cluster)         -> "8.0.33-ndb"
 * - "5.7.mysql_aurora.2.11.1" (Aurora)           -> "Aurora MySQL 5.7 (2.11.1)"
 *
 * @param string $serverInfo
 *
 * @return string
 */
function normaliseDbServerInfo( string $serverInfo ): string {
	$info = trim( $serverInfo );

	// Aurora MySQL: 5.7.mysql_aurora.2.11.1 or 8.0.32.amazon_aurora.3.04.0
	if ( preg_match( '/^(?<base>\d+\.\d+)\.(?:mysql_aurora|amazon_aurora)\.(?<track>[\d.]+)/i', $info, $m ) ) {
		return "Aurora MySQL {$m['base']} ({$m['track']})";
	}

	// MySQL NDB Cluster: 8.0.33-ndb-8.0.33  -> keep "8.0.33-ndb"
	if ( preg_match( '/^(?<ver>\d+(?:\.\d+){1,3})-ndb(?:-[\d.]+)?/i', $info, $m ) ) {
		return "{$m['ver']}-ndb";
	}

	// Percona: 8.0.36-28[-anything] -> "8.0.36-Percona"
	// Heuristic: version-<release>, and version_comment will say Percona, but we infer here.
	if ( stripos( $info, 'percona' ) !== false || preg_match( '/^\d+(?:\.\d+){1,3}-\d+(?:-[A-Za-z0-9_.-]+)?$/', $info ) ) {
		if ( preg_match( '/^(?<ver>\d+(?:\.\d+){1,3})-\d+/', $info, $m ) ) {
			return "{$m['ver']}-Percona";
		}
	}

	// MariaDB: keep "X.Y[.Z]-MariaDB" and drop packaging/log tails.
	if ( stripos( $info, 'MariaDB' ) !== false ) {
		if ( preg_match( '/^(?<ver>\d+(?:\.\d+){1,3})-MariaDB\b/i', $info, $m ) ) {
			return "{$m['ver']}-MariaDB";
		}
	}

	// Plain MySQL with distro clutter (Debian, Ubuntu, EL, Amazon, CloudLinux, -log, etc.)
	// Keep leading X.Y[.Z], optionally a short edition token like -ndb handled earlier.
	if ( preg_match( '/^(?<ver>\d+(?:\.\d+){1,3})\b/i', $info, $m ) ) {
		return $m['ver'];
	}

	// Fallback
	return $info;
}
