            <div id="folders-view">
                <div class="column-controls">
                    <button onclick="setColumnWidth('auto')">X</button>
                    <button onclick="cycleColumnWidth('prev')">−</button>
                    <button onclick="cycleColumnWidth('next')">+</button>
                </div>
                <h2>Document Folders</h2>
                <div class="columns">
                    <div class="column">
                        <h3>Miscellaneous 🤷🏻‍♂️</h3>
                        <ul>
                            <?php
                            $dir = HTDOCS_PATH . 'projects/Other/';
                            $excludeList = [
                                'example',
                                'folder'
                            ]; // Add folder names you want to exclude

                            if ( ! is_dir( $dir ) ) {
                                echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                            } else {
                                $folders = array_filter( glob( $dir . '*' ), 'is_dir' );

                                if ( empty( $folders ) ) {
                                    echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                                } else {
                                    foreach ( $folders as $folder ) {
                                        $folderName = basename( $folder );

                                        // Check if the folder is in the exclusion list
                                        if ( in_array( $folderName, $excludeList ) ) {
                                            continue;
                                        }
                                        
                                        echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="column">
                        <h3><a href="">GitHub</a> 🚀</h3>
                        <ul>
                            <?php
                            $dir = HTDOCS_PATH . 'projects/GitHub/';
                            $excludeList = [
                                'example',
                                'folder'
                            ]; // Add folder names you want to exclude

                            if ( ! is_dir( $dir ) ) {
                                echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                            } else {
                                $folders = array_filter( glob( $dir . '*' ), 'is_dir' );

                                if ( empty( $folders ) ) {
                                    echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                                } else {
                                    foreach ( $folders as $folder ) {
                                        $folderName = basename( $folder );

                                        // Check if the folder is in the exclusion list
                                        if ( in_array( $folderName, $excludeList ) ) {
                                            continue;
                                        }
                                        
                                        echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="column">
                        <h3><a href="">Pantheon</a> 🏛️</h3>
                        <ul>
                            <?php
                            $dir = HTDOCS_PATH . 'projects/Pantheon/';
                            $excludeList = [
                                'example',
                                'folder'
                            ]; // Add folder names you want to exclude

                            if ( ! is_dir( $dir ) ) {
                                echo "<li style='color: red;'>Error: The directory '$dir' does not exist.</li>";
                            } else {
                                $folders = array_filter( glob( $dir . '*' ), 'is_dir' );

                                if ( empty( $folders ) ) {
                                    echo "<li style='color: orange;'>No projects found in '$dir'.</li>";
                                } else {
                                    foreach ( $folders as $folder ) {
                                        $folderName = basename( $folder );

                                        // Check if the folder is in the exclusion list
                                        if ( in_array( $folderName, $excludeList ) ) {
                                            continue;
                                        }
                                        
                                        echo "<li><a href=\"http://local.$folderName.com\">$folderName</a></li>";
                                    }
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>