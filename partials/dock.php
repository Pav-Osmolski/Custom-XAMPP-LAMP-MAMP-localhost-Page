<?php
/**
 * Dock Items Renderer
 *
 * Loads a list of dock shortcut items from `dock.json` and renders them
 * as clickable icons with optional labels. Each item supports:
 * - A URL (`url`)
 * - An icon image path (`icon`)
 * - Alternate text (`alt`)
 * - An optional label (`label`)
 *
 * Configuration:
 * - Reads from `/config/dock.json`
 *
 * Output:
 * - A horizontal dock bar with anchor elements linking to external tools or resources
 *
 * @author Pav
 * @license MIT
 * @version 1.1
 */

require_once __DIR__ . '/../config/config.php';

$dockItems = read_json_array_safely( __DIR__ . '/../config/dock.json' );
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
