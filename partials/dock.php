<?php
$dockItemsPath = __DIR__ . '/dock.json';
if ( file_exists( $dockItemsPath ) ) {
	$dockItems = json_decode( file_get_contents( $dockItemsPath ), true );
} else {
	$dockItems = [];
}
?>
<div class="dock">
	<?php foreach ( $dockItems as $item ): ?>
		<a href="<?= htmlspecialchars( $item['url'] ) ?>" target="_blank">
			<?php if ( ! empty( $item['label'] ) ): ?>
				<span><?= htmlspecialchars( $item['label'] ) ?></span>
			<?php endif; ?>
			<img src="<?= htmlspecialchars( $item['icon'] ) ?>" alt="<?= htmlspecialchars( $item['alt'] ) ?>">
		</a>
	<?php endforeach; ?>
</div>
