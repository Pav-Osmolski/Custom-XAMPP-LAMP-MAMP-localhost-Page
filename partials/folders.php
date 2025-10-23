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
 * @version 1.3
 */

require_once __DIR__ . '/../config/config.php';

// Load config via safe JSON reader
$foldersConfigData = read_json_array_safely( __DIR__ . '/../config/folders.json' );
$linkTemplates     = read_json_array_safely( __DIR__ . '/../config/link_templates.json' );

// Index templates by name for fast lookup
$templatesByName = [];
foreach ( $linkTemplates as $tpl ) {
    if ( is_array( $tpl ) && isset( $tpl['name'] ) ) {
        $templatesByName[ (string) $tpl['name'] ] = $tpl;
    }
}

// Load hamburger icon, prefixing IDs to avoid collisions
$hamburgerSvgPath = __DIR__ . '/../assets/images/hamburger.svg';
$hamburgerSvg     = is_file( $hamburgerSvgPath )
    ? injectSvgWithUniqueIds( $hamburgerSvgPath, 'drag-' . bin2hex( random_bytes( 3 ) ) )
    : '';

$columnCounter = 0;
$globalErrors  = [];
?>

<?php if ( empty( $foldersConfigData ) || empty( $templatesByName ) ) : ?>
    <div id="folders-view" class="visible" aria-labelledby="folders-view-heading">
        <h2>Document Folders</h2>
        <div class="columns max-md">
            <div class="column">
                <?php if ( empty( $foldersConfigData ) && empty( $templatesByName ) ) : ?>
                    <p>No folders or link templates configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first folder column and link template.</p>
                <?php elseif ( empty( $foldersConfigData ) ) : ?>
                    <p>No folders configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first folder column.</p>
                <?php elseif ( empty( $templatesByName ) ) : ?>
                    <p>No link templates configured yet. Pop over to <a href="?view=settings">Settings</a> to add your first link template.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else : ?>
    <div id="folders-view" class="visible">
        <div class="column-controls" role="group" aria-label="Column width controls">
            <button id="reset-width" type="button" aria-label="Reset column width"><span aria-hidden="true">X</span></button>
            <button id="prev-width" type="button" aria-label="Decrease column width"><span aria-hidden="true">−</span></button>
            <button id="next-width" type="button" aria-label="Increase column width"><span aria-hidden="true">+</span></button>
        </div>
        <h2>Document Folders</h2>
        <div class="columns max-md" role="list" aria-describedby="drag-help">
            <?php foreach ( $foldersConfigData as $column ): ?>
                <?php
                if ( ! is_array( $column ) ) {
                    $globalErrors[] = 'Column configuration must be an object.';
                    continue;
                }

                $title       = isset( $column['title'] ) ? (string) $column['title'] : 'Untitled';
                $href        = isset( $column['href'] ) ? (string) $column['href'] : '';
                $template    = isset( $column['linkTemplate'] ) ? (string) $column['linkTemplate'] : 'basic';
                $excludeList = isset( $column['excludeList'] ) && is_array( $column['excludeList'] ) ? $column['excludeList'] : [];
                $disable     = ! empty( $column['disableLinks'] );

                $norm = normalise_subdir( $column['dir'] ?? '' );
                $dir  = $norm['dir'];
                if ( $norm['error'] ) {
                    $globalErrors[] = $norm['error'] . ' (Column: ' . htmlspecialchars( $title ) . ')';
                }

                $folders = $dir ? list_subdirs( $dir ) : [];
                ?>
                <div class="column" id="<?php echo 'column_' . ( ++$columnCounter ); ?>" role="listitem">
                    <div class="drag-handle" type="button" aria-label="Reorder column <?= htmlspecialchars( $title ) ?>" aria-describedby="drag-help"><?php echo $hamburgerSvg; ?></div>
                    <h3>
                        <?php if ( $href !== '' ): ?>
                            <a href="<?= htmlspecialchars( $href ) ?>"><?= htmlspecialchars( $title ) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars( $title ) ?>
                        <?php endif; ?>
                    </h3>
                    <ul>
                        <?php
                        if ( ! $dir || ! is_dir( $dir ) ) {
                            echo "<li class='invalid'>Error: The directory '" . htmlspecialchars( $dir ?: '(unset)' ) . "' does not exist.</li>";
                        } elseif ( empty( $folders ) ) {
                            echo "<li class='empty'>No projects found in '" . htmlspecialchars( $dir ) . "'.</li>";
                        } else {
                            $templateHtml = resolve_template_html( $template, $templatesByName );

                            foreach ( $folders as $folderName ) {
                                if ( in_array( $folderName, $excludeList, true ) ) {
                                    continue;
                                }

                                $errors  = [];
                                $urlName = build_url_name( $folderName, $column, $errors );

                                if ( $urlName === '__SKIP__' ) {
                                    continue;
                                }

                                foreach ( $errors as $e ) {
                                    $globalErrors[] = $e;
                                }

                                echo render_item_html( $templateHtml, $urlName, $disable );
                            }
                        }
                        ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ( ! empty( $globalErrors ) ): ?>
            <div class="column warnings max-md">
                <h4>Notes</h4>
                <ul>
                    <?php foreach ( array_unique( $globalErrors ) as $msg ): ?>
                        <li><?= $msg ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
