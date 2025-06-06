<?php
/** @var bool $displayApacheErrorLog */
/** @var bool $displaySystemStats */
/** @var bool $useAjaxForStats */

if ( $displayApacheErrorLog || $displaySystemStats ): ?>

	<?php if ( $useAjaxForStats ): ?>

		<?php if ( $displayApacheErrorLog ): ?>
			<section id="error-log-section" aria-labelledby="error-log-title">
				<h3 id="error-log-title">
					<button id="toggle-error-log" aria-expanded="false" aria-controls="error-log">
						📝 Toggle Apache Error Log
					</button>
				</h3>
				<pre id="error-log" aria-live="polite" tabindex="0" style="display: none;">
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
			<section id="error-log-section" aria-labelledby="error-log-title">
				<?php include __DIR__ . '/../utils/apache_error_log.php'; ?>
			</section>
		<?php endif; ?>

		<?php if ( $displaySystemStats ): ?>
			<section id="system-monitor" class="system-monitor" role="region" aria-labelledby="system-monitor-title">
				<?php include __DIR__ . '/../utils/system_stats.php'; ?>
			</section>
		<?php endif; ?>

	<?php endif; ?>

<?php endif; ?>
