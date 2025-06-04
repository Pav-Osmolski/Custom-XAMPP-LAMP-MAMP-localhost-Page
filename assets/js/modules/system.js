// assets/js/modules/system.js
export function initSystemMonitoring() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const body = document.body;
		const systemStatsSection = document.getElementById( 'system-monitor' );

		if ( !systemStatsSection ) return;

		const ajaxEnabled = body.getAttribute( 'data-ajax-enabled' ) === 'true';
		if ( !ajaxEnabled ) return;

		const cpuElem = document.getElementById( 'cpu-load' );
		const memElem = document.getElementById( 'memory-usage' );
		const diskElem = document.getElementById( 'disk-space' );

		let statsInterval;

		function fetchSystemStats() {
			fetch( `${ window.BASE_URL }system_stats.php`, {cache: 'no-store'} )
				.then( response => {
					if ( !response.ok ) throw new Error( 'System stats unavailable' );
					return response.json();
				} )
				.then( data => {
					cpuElem.textContent = `${ data.cpu }%`;
					memElem.textContent = `${ data.memory } MB`;
					diskElem.textContent = `${ data.disk }%`;
				} );
		}

		function startMonitoring() {
			statsInterval = setInterval( fetchSystemStats, 1000 );
			fetchSystemStats();
		}

		// Only begin polling once we know the backend is returning valid JSON
		fetch( `${ window.BASE_URL }system_stats.php`, {cache: 'no-store'} )
			.then( response => {
				if ( !response.ok ) return;
				return response.json();
			} )
			.then( data => {
				// First fetch succeeded — safe to start polling
				cpuElem.textContent = `${ data.cpu }%`;
				memElem.textContent = `${ data.memory } MB`;
				diskElem.textContent = `${ data.disk }%`;
				startMonitoring();
			} )
			.catch( () => {
				// Do nothing — let next AJAX-enabled cycle pick it up silently
			} );
	} );
}
