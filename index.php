<?php
/** @var string $user */
/** @var bool $displayHeader */
/** @var bool $displayFooter */
/** @var bool $useAjaxForStats */
/** @var string $bodyClasses */

// Start session before anything else so CSRF can safely use it during render
if ( session_status() !== PHP_SESSION_ACTIVE ) {
	// Optional: set cookie attributes before session_start
	if ( function_exists( 'session_set_cookie_params' ) ) {
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params( [
			'lifetime' => $cookieParams['lifetime'] ?? 0,
			'path'     => $cookieParams['path'] ?? '/',
			'domain'   => $cookieParams['domain'] ?? '',
			'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
			'httponly' => true,
			'samesite' => 'Lax', // 'Strict' if you never post across origins
		] );
	}
	session_start();
}

include __DIR__ . '/config/security.php';
include __DIR__ . '/config/config.php';
//include __DIR__ . '/config/debug.php';
require_once __DIR__ . '/partials/submit.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="<?php echo htmlspecialchars( $user ); ?>'s custom XAMPP localhost index page.">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo htmlspecialchars( $user ); ?>'s localhost</title>
	<link rel="icon" type="image/x-icon" href="assets/favicon/favicon.ico">
	<link rel="icon" sizes="192x192" href="assets/favicon/android-chrome-192x192.png">
	<link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
	<link rel="stylesheet" type="text/css"
	      href="dist/css/style.min.css?v=<?= filemtime( 'dist/css/style.min.css' ); ?>">
	<script>
		window.BASE_URL = "<?= rtrim( dirname( $_SERVER['SCRIPT_NAME'] ), '/' ) ?>/";
	</script>
	<script src="dist/js/script.min.js?v=<?= filemtime( 'dist/js/script.min.js' ); ?>"></script>
</head>
<body<?= $useAjaxForStats ? ' data-ajax-enabled="true"' : '' ?> class="<?= $bodyClasses ?>">
<div class="container">
	<?php $displayHeader && require_once __DIR__ . '/partials/header.php'; ?>
	<main role="main">
		<section class="folders">
			<?php require_once __DIR__ . '/partials/folders.php'; ?>
			<?php require_once __DIR__ . '/partials/settings.php'; ?>
			<?php require_once __DIR__ . '/utils/phpinfo.php'; ?>
			<div id="apache-view"><?php /* Dynamically loads /utils/apache_inspector.php */ ?></div>
			<div id="mysql-view"><?php /* Dynamically loads /utils/mysql_inspector.php */ ?></div>
			<?php require_once __DIR__ . '/partials/dock.php'; ?>
		</section>
		<?php require_once __DIR__ . '/partials/info.php'; ?>
	</main>
	<?php $displayFooter && require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</body>
</html>
