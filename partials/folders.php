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
 * @version 1.1
 */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

$foldersConfigData = read_json_array_safely( __DIR__ . '/../config/folders.json' );
$linkTemplates     = read_json_array_safely( __DIR__ . '/../config/link_templates.json' );

$hamburgerSvgPath = __DIR__ . '/../assets/images/hamburger.svg';
$hamburgerSvg     = is_file( $hamburgerSvgPath ) ? file_get_contents( $hamburgerSvgPath ) : '';

$columnCounter = 0;
?>

<?php if ( empty( $foldersConfigData ) || empty( $linkTemplates ) ) : ?>
    <div id="folders-view" class="visible">
        <h2>Document Folders</h2>
        <div class="columns max-md">
            <div class="column">
				<?php if ( empty( $foldersConfigData ) && empty( $linkTemplates ) ) : ?>
				    <p>No folders or link templates configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first folder column and link template.</p>
				<?php elseif ( empty( $foldersConfigData ) ) : ?>
				    <p>No folders configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first folder column.</p>
				<?php elseif ( empty( $linkTemplates ) ) : ?>
				    <p>No link templates configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first link template.</p>
				<?php endif; ?>
            </div>
        </div>
    </div>
<?php else : ?>
    <div id="folders-view" class="visible">
        <div class="column-controls">
            <button id="reset-width">X</button>
            <button id="prev-width">−</button>
            <button id="next-width">+</button>
        </div>
        <h2>Document Folders</h2>
        <div class="columns max-md">
            <?php foreach ( $foldersConfigData as $column ): ?>
                <div class="column" id="<?php echo 'column_' . ( ++ $columnCounter ); ?>">
                    <div class="drag-handle"><?php echo $hamburgerSvg; ?></div>
                    <h3>
                        <?php if ( ! empty( $column['href'] ) ): ?>
                            <a href="<?= htmlspecialchars( $column['href'] ) ?>"><?= htmlspecialchars( $column['title'] ) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars( $column['title'] ) ?>
                        <?php endif; ?>
                    </h3>
                    <ul>
                        <?php
                        $subdir = trim( str_replace( [
                            '/',
                            '\\'
                        ], DIRECTORY_SEPARATOR, $column['dir'] ), DIRECTORY_SEPARATOR );
                        $dir    = HTDOCS_PATH . DIRECTORY_SEPARATOR . $subdir . DIRECTORY_SEPARATOR;
                        $folders = array_filter( glob( $dir . '*' ), 'is_dir' );

                        if ( ! is_dir( $dir ) ) {
                            echo "<li class='invalid'>Error: The directory '{$dir}' does not exist.</li>";
                        } elseif ( empty( $folders ) ) {
                            echo "<li class='empty'>No projects found in '{$dir}'.</li>";
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
<?php endif; ?>
