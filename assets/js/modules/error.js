// assets/js/modules/error.js
export function initApacheErrorLog() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const body = document.body;
		const errorLogSection = document.getElementById( 'error-log-section' );

		if ( !errorLogSection ) return;

		const toggleBtn = document.getElementById( 'toggle-error-log' );
		const errorLog = document.getElementById( 'error-log' );

		// Always allow toggling visibility
		toggleBtn.addEventListener( 'click', function () {
			const isVisible = errorLog.style.display === 'block';
			errorLog.style.display = isVisible ? 'none' : 'block';
			this.setAttribute( 'aria-expanded', !isVisible );
		} );

		const ajaxEnabled = body.getAttribute( 'data-ajax-enabled' ) === 'true';
		if ( !ajaxEnabled ) return;

		let errorLogInterval;

		function fetchErrorLog() {
			fetch( `${ window.BASE_URL }utils/apache_error_log.php`, {cache: 'no-store'} )
				.then( response => {
					if ( !response.ok ) throw new Error( 'Error log unavailable' );
					return response.text();
				} )
				.then( data => {
					errorLog.innerHTML = `<code>${ data }</code>`;
				} );
		}

		function startMonitoring() {
			errorLogInterval = setInterval( fetchErrorLog, 3000 );
			fetchErrorLog();
		}

		// Pre-flight fetch to avoid first-call failure
		fetch( `${ window.BASE_URL }utils/apache_error_log.php`, {cache: 'no-store'} )
			.then( response => {
				if ( !response.ok ) return;
				return response.text();
			} )
			.then( data => {
				errorLog.innerHTML = `<code>${ data }</code>`;
				startMonitoring();
			} )
			.catch( () => {
				// Do nothing on first failure
			} );
	} );
}
