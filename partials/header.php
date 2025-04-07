    <header role="banner">
        <h1>localhost is ready, <?php echo htmlspecialchars( $user ); ?>! 👨🏻‍💻</h1>
        <input type="text" class="search-bar" placeholder="Search projects..." onkeyup="searchProjects()"
               aria-label="Search projects">
        <div class="clock" aria-live="polite"></div>
        <div class="server-info">
            <?php renderServerInfo(); ?>
        </div>
    </header>