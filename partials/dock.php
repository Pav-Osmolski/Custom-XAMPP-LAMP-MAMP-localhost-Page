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
 * @author  Pawel Osmolski
 * @version 1.1
 */

require_once __DIR__ . '/../config/config.php';

$dockItems = read_json_array_safely( __DIR__ . '/../config/dock.json' );
?>
<nav class="dock" aria-label="Quick launch">
	<ul class="dock-list">
		<?php foreach ( $dockItems as $item ):
			$label = isset( $item['label'] ) ? trim( $item['label'] ) : '';
			$alt = isset( $item['alt'] ) ? trim( $item['alt'] ) : '';
			$name = $label !== '' ? $label : $alt; // fallback if no label
			$opens = '(opens in a new tab)';
			?>
			<li class="dock-item">
				<a
						href="<?= htmlspecialchars( $item['url'] ) ?>"
						target="_blank"
						rel="noopener noreferrer"
					<?php if ( $label === '' ): // no visible text -> name the link ?>
						aria-label="<?= htmlspecialchars( $name . ' ' . $opens ) ?>"
					<?php endif; ?>
				>
					<img
						src="<?= htmlspecialchars( $item['icon'] ) ?>"
						alt="<?= $label === '' ? htmlspecialchars( $alt ) : '' ?>"
						<?php if ( $label !== '' ): ?>aria-hidden="true"<?php endif; ?>
					>
					<?php if ( $label !== '' ): ?>
						<span class="dock-label"><?= htmlspecialchars( $label ) ?></span>
						<span class="sr-only"><?= $opens ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
