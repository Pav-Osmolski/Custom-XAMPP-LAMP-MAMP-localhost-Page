<?php
/**
 * Document Folders Viewer
 *
 * Dynamically generates a folder listing UI based on a JSON configuration file.
 * Each column in the layout corresponds to a configured directory and can:
 * - Apply exclusion lists
 * - Transform URLs via regex
 * - Use a named link template from `link_templates.json`
 * - Support custom folder name replacements (`specialCases`)
 * - Disable links entirely if required
 *
 * Configuration is read from:
 * - `/config/folders.json`
 * - `/config/link_templates.json`
 *
 * Output:
 * - HTML markup with columns and folder links
 * - Error or warning messages for invalid or empty directories
 *
 * @author Pav
 * @license MIT
 * @version 1.0
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

$columnCounter = 0;
$configPath    = __DIR__ . '/../config/folders.json';
$configData    = json_decode( file_get_contents( $configPath ), true );
?>

<div id="folders-view" class="visible">
	<div class="column-controls">
		<button id="reset-width">X</button>
		<button id="prev-width">−</button>
		<button id="next-width">+</button>
	</div>
	<h2>Document Folders</h2>
	<div class="columns max-md">
		<?php foreach ( $configData as $column ): ?>
			<div class="column" id="<?php echo 'column_' . ( ++ $columnCounter ); ?>">
				<div class="drag-handle"><?php echo file_get_contents( __DIR__ . '/../assets/images/hamburger.svg' ); ?></div>
				<h3>
					<?php if ( ! empty( $column['href'] ) ): ?>
						<a href="<?= $column['href'] ?>"><?= $column['title'] ?></a>
					<?php else: ?>
						<?= $column['title'] ?>
					<?php endif; ?>
				</h3>
				<ul>
					<?php
					$subdir = trim( str_replace( [
						'/',
						'\\'
					], DIRECTORY_SEPARATOR, $column['dir'] ), DIRECTORY_SEPARATOR );
					$dir    = HTDOCS_PATH . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR;
					if ( ! is_dir( $dir ) ) {
						echo "<ul><li style='color: red;'>Error: The directory '{$dir}' does not exist.</li></ul>";
						echo "</div>";
						continue;
					}
					$folders = array_filter( glob( $dir . '*' ), 'is_dir' );
					if ( empty( $folders ) ) {
						echo "<ul><li style='color: orange;'>No projects found in '{$dir}'.</li></ul>";
						echo "</div>";
						continue;
					}
					foreach ( $folders as $folder ) {
						$folderName  = basename( $folder );
						$excludeList = $column['excludeList'] ?? [];
						if ( in_array( $folderName, $excludeList ) ) {
							continue;
						}

						if ( isset( $column['urlRules'] ) &&
						     ! empty( $column['urlRules']['match'] ) &&
						     ! empty( $column['urlRules']['replace'] )
						) {
							$matchRegex   = $column['urlRules']['match'];
							$replaceRegex = $column['urlRules']['replace'];
							$urlName      = preg_replace( $replaceRegex, '', $folderName );

							if ( ! preg_match( $matchRegex, $folderName ) ) {
								continue;
							}
						} else {
							$urlName = $folderName;
						}

						if ( ! empty( $column['specialCases'][ $urlName ] ) ) {
							$urlName = $column['specialCases'][ $urlName ];
						}

						$disableLinks = ! empty( $column['disableLinks'] );

						$template      = $column['linkTemplate'] ?? 'basic';
						$templateFile  = __DIR__ . '/../config/link_templates.json';
						$linkTemplates = json_decode( file_get_contents( $templateFile ), true );
						$html          = '';

						foreach ( $linkTemplates as $t ) {
							if ( $t['name'] === $template ) {
								$html = $t['html'];
								break;
							}
						}

						$html = str_replace( '{urlName}', $urlName, $html );

						if ( $disableLinks ) {
							$html = strip_tags( $html, '<li><div>' );
						}

						echo $html;
					}
					?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</div>
