<?php
/** @var string $user */
/** @var bool $displayClock */
/** @var bool $displaySearch */
?>
<header role="banner">
	<h1>localhost is ready, <?php echo htmlspecialchars( $user ); ?>! 👨🏻‍💻</h1>
	<?= $displaySearch ? '<input type="text" class="search-bar" placeholder="Search projects..." aria-label="Search projects">' : '' ?>
	<?= $displayClock ? '<div class="clock" aria-live="polite"></div>' : '' ?>
	<div class="server-info">
		<?php renderServerInfo(); ?>
	</div>
</header>