<?php
require_once 'partials/submit.php';

// Load defaults
include 'config.php';
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
<?php echo( $useAjaxForStats ? '<body data-ajax-enabled="true"' : '<body' ); ?> class="<?= $bodyClasses; ?>">
<div class="container">
	<?php require_once 'partials/header.php'; ?>
	<main role="main">
		<section class="folders">
			<?php require_once 'partials/folders.php'; ?>
			<?php require_once 'partials/settings.php'; ?>
			<?php require_once 'phpinfo.php'; ?>
			<?php require_once 'partials/dock.php'; ?>
		</section>
		<?php require_once 'partials/info.php'; ?>
	</main>
	<?php require_once 'partials/footer.php'; ?>
</div>
</body>
</html>
