// assets/js/modules/error.js
export function initApacheErrorLog() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const body = document.body;
		const errorLogSection = document.getElementById( 'error-log-section' );

		if ( errorLogSection ) {
			const toggleBtn = document.getElementById( 'toggle-error-log' );
			const errorLog = document.getElementById( 'error-log' );

			// Always allow toggling visibility
			toggleBtn.addEventListener( 'click', function () {
				const isVisible = errorLog.style.display === 'block';
				errorLog.style.display = isVisible ? 'none' : 'block';
				this.setAttribute( 'aria-expanded', !isVisible );
			} );

			// Only run AJAX if enabled
			const ajaxEnabled = body.getAttribute( 'data-ajax-enabled' ) === 'true';
			if ( ajaxEnabled ) {
				function fetchErrorLog() {
					const url = 'apache_error_log.php';
					fetch( url, {cache: 'no-store'} )
						.then( response => {
							if ( !response.ok ) throw new Error( 'Error log unavailable' );
							return response.text();
						} )
						.then( data => {
							errorLog.innerHTML = `<code>${ data }</code>`;
						} )
						.catch( error => {
							console.error( 'Error fetching error log:', error );
							clearInterval( errorLogInterval );
						} );
				}

				const errorLogInterval = setInterval( fetchErrorLog, 3000 );
				fetchErrorLog();
			}
		}
	} );
}
