<?php
/**
 * PHP Info Display
 *
 * Outputs the content of `phpinfo()` into a div without styling or layout junk.
 * Useful for embedding inside custom dashboards (e.g. XAMPP/MAMP panels).
 *
 * Sanitisation:
 * - Removes everything outside the `<body>` tag
 * - Strips out `<style>` blocks and inline `style` attributes
 * - Leaves only raw HTML structure and content
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */
?>
<div id="phpinfo-view">
	<?php
	ob_start();
	phpinfo();
	$info = ob_get_clean();

	// Strip everything before <body> and after </body>
	$info = preg_replace( '%^.*<body>(.*)</body>.*$%s', '$1', $info );

	// Optionally strip the style block
	$info = preg_replace( '%<style[^>]*>.*?</style>%s', '', $info );

	// Or, if you want to remove ALL styles inline or block
	$info = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $info );
	$info = preg_replace( '/style=("|\')(.*?)("|\')/i', '', $info );

	echo $info;
	?>
</div>
