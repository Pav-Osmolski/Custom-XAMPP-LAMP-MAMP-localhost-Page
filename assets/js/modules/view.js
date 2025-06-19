// assets/js/modules/view.js
export function initViewToggles() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const views = {
			index: document.getElementById( 'folders-view' ),
			settings: document.getElementById( 'settings-view' ),
			phpinfo: document.getElementById( 'phpinfo-view' ),
			apache: document.getElementById( 'apache-view' ),
		};

		const toggles = {
			'toggle-index': 'index',
			'toggle-settings': 'settings',
			'toggle-phpinfo': 'phpinfo',
		};

		Object.entries( toggles ).forEach( ( [ toggleId, viewKey ] ) => {
			const toggle = document.getElementById( toggleId );
			if ( toggle ) {
				toggle.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					Object.entries( views ).forEach( ( [ key, el ] ) => {
						if ( el ) el.classList.toggle( 'visible', key === viewKey );
					} );
				} );
			}
		} );

		const asyncToggles = {
			'toggle-apache-inspector': {
				targetKey: 'apache',
				url: 'utils/apache_inspector.php',
			},
		};

		Object.entries( asyncToggles ).forEach( ( [ toggleId, {targetKey, url} ] ) => {
			const toggle = document.getElementById( toggleId );
			const target = views[targetKey];
			if ( toggle && target ) {
				toggle.addEventListener( 'click', async ( e ) => {
					e.preventDefault();

					// Hide all views
					Object.values( views ).forEach( ( view ) => {
						if ( view ) view.classList.remove( 'visible' );
					} );

					// Always show the target immediately
					target.classList.add( 'visible' );

					// Only fetch and overwrite if not already loaded
					// To use a spinning emoji, add the class "emoji" to "loader" and insert your emoji within the <span>
					if ( !target.dataset.loaded ) {
						target.innerHTML = '<div class="loader"><span class="spinner" role="img" aria-label="Loading"></span></div>';

						try {
							const res = await fetch( `${ window.BASE_URL || '/' }${ url }` );
							target.innerHTML = await res.text();
							target.dataset.loaded = 'true';
						} catch ( err ) {
							target.innerHTML = '<p class="error">Failed to load ' + url + '</p>';
							console.error( `[view.js] Failed to fetch ${ url }`, err );
						}
					}
				} );
			}
		} );
	} );
}
