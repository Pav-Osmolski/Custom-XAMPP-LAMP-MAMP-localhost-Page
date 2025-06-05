<div id="phpinfo-view" style="display: none;">
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
