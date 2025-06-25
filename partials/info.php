<?php
/** @var bool $displayApacheErrorLog */
/** @var bool $displayPhpErrorLog */
/** @var bool $displaySystemStats */
/** @var bool $useAjaxForStats */

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/config.php';

if ( $displayApacheErrorLog || $displayPhpErrorLog || $displaySystemStats ): ?>

	<?php if ( $useAjaxForStats ): ?>

		<?php if ( $displayApacheErrorLog ): ?>
			<section id="apache-error-log-section" class="error-log-section" aria-labelledby="apache-error-log-title">
				<h3 id="apache-error-log-title">
					<button id="toggle-apache-error-log" aria-expanded="false" aria-controls="apache-error-log">
						📝 Toggle Apache Error Log
					</button>
				</h3>
				<pre id="apache-error-log" aria-live="polite" tabindex="0">
                    <code>Loading...</code>
                </pre>
			</section>
		<?php endif; ?>

		<?php if ( $displayPhpErrorLog ): ?>
			<section id="php-error-log-section" class="error-log-section" aria-labelledby="php-error-log-title">
				<h3 id="php-error-log-title">
					<button id="toggle-php-error-log" aria-expanded="false" aria-controls="php-error-log">
						📝 Toggle PHP Error Log
					</button>
				</h3>
				<pre id="php-error-log" aria-live="polite" tabindex="0">
                    <code>Loading...</code>
                </pre>
			</section>
		<?php endif; ?>

		<?php if ( $displaySystemStats ): ?>
			<div id="system-monitor" class="system-monitor" role="region" aria-labelledby="system-monitor-title">
				<h3 id="system-monitor-title">System Stats</h3>
				<p>CPU Load: <span id="cpu-load" aria-live="polite">N/A</span></p>
				<p>RAM Usage: <span id="memory-usage" aria-live="polite">N/A</span></p>
				<p>Disk Space: <span id="disk-space" aria-live="polite">N/A</span></p>
			</div>
		<?php endif; ?>

	<?php else: ?>

		<?php if ( $displayApacheErrorLog ): ?>
			<section id="apache-error-log-section" class="error-log-section" aria-labelledby="apache-error-log-title">
				<?php include __DIR__ . '/../utils/apache_error_log.php'; ?>
			</section>
		<?php endif; ?>

		<?php if ( $displayPhpErrorLog ): ?>
			<section id="php-error-log-section" class="error-log-section" aria-labelledby="php-error-log-title">
				<?php include __DIR__ . '/../utils/php_error_log.php'; ?>
			</section>
		<?php endif; ?>

		<?php if ( $displaySystemStats ): ?>
			<section id="system-monitor" class="system-monitor" role="region" aria-labelledby="system-monitor-title">
				<?php include __DIR__ . '/../utils/system_stats.php'; ?>
			</section>
		<?php endif; ?>

	<?php endif; ?>

<?php endif; ?>
