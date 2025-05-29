<?php
$columnCounter = 0;
$configPath    = __DIR__ . '/folders.json';
$configData    = json_decode( file_get_contents( $configPath ), true );
?>

<div id="folders-view">
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
					$dir = HTDOCS_PATH . rtrim( $column['dir'], '/' ) . '/';
					if ( ! is_dir( $dir ) ) {
						echo "<li style='color: red;'>Error: The directory '{$dir}' does not exist.</li>";
						continue;
					}
					$folders = array_filter( glob( $dir . '*' ), 'is_dir' );
					if ( empty( $folders ) ) {
						echo "<li style='color: orange;'>No projects found in '{$dir}'.</li>";
						continue;
					}
					foreach ( $folders as $folder ) {
						$folderName  = basename( $folder );
						$excludeList = $column['excludeList'] ?? [];
						if ( in_array( $folderName, $excludeList ) ) {
							continue;
						}

						$matchRegex   = $column['urlRules']['match'];
						$replaceRegex = $column['urlRules']['replace'];
						$urlName      = preg_replace( $replaceRegex, '', $folderName );

						if ( ! empty( $column['specialCases'][ $urlName ] ) ) {
							$urlName = $column['specialCases'][ $urlName ];
						}

						if ( ! preg_match( $matchRegex, $folderName ) ) {
							continue;
						}

						$disableLinks = ! empty( $column['disableLinks'] );

						switch ( $column['linkTemplate'] ) {
							case 'env-links':
								if ( $disableLinks ) {
									echo "<li class='folder-item'>$urlName</li>";
								} else {
									echo "<li class='folder-item'><a href='https://local.$urlName.com'>$urlName</a>";
									echo "<div class='url-container'>
											<a href='https://dev.$urlName.com'>dev</a>
											<a href='https://test.$urlName.com'>test</a>
											<a href='https://stage.$urlName.com'>stage</a>
											<a href='https://www.$urlName.com'>prod</a>
										  </div></li>";
								}
								break;
							case 'pantheon':
								if ( $disableLinks ) {
									echo "<li class='folder-item'>$urlName</li>";
								} else {
									echo "<li class='folder-item'><a href='https://local.pantheon.$urlName.com'>$urlName</a></li>";
								}
								break;
							default:
								if ( $disableLinks ) {
									echo "<li class='folder-item'>$urlName</li>";
								} else {
									echo "<li class='folder-item'><a href='https://local.$urlName.com'>$urlName</a></li>";
								}
								break;
						}
					}
					?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</div>
