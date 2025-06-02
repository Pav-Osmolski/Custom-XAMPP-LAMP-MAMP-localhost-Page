// assets/js/modules/system.js
export function initSystemMonitoring() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const body = document.body;
		const systemStatsSection = document.getElementById( 'system-monitor' );

		if ( systemStatsSection ) {
			const cpuElem = document.getElementById( 'cpu-load' );
			const memElem = document.getElementById( 'memory-usage' );
			const diskElem = document.getElementById( 'disk-space' );

			const ajaxEnabled = body.getAttribute( 'data-ajax-enabled' ) === 'true';

			if ( ajaxEnabled ) {
				function fetchSystemStats() {
					const url = 'system_stats.php';
					fetch( url, {cache: 'no-store'} )
						.then( response => {
							if ( !response.ok ) throw new Error( 'System stats unavailable' );
							return response.json();
						} )
						.then( data => {
							cpuElem.textContent = `${ data.cpu }%`;
							memElem.textContent = `${ data.memory } MB`;
							diskElem.textContent = `${ data.disk }%`;
						} )
						.catch( error => {
							console.error( 'Error fetching system stats:', error );
							clearInterval( statsInterval );
						} );
				}

				const statsInterval = setInterval( fetchSystemStats, 1000 );
				fetchSystemStats();
			}
		}
	} );
}
