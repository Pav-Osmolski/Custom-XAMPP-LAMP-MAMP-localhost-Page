// assets/js/modules/view.js
export function initToggleAccordion() {
	// Helper: force layout reflow to ensure transitions behave correctly (especially in Firefox)
	function forceReflow( element ) {
		void element.offsetHeight;
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		document.querySelectorAll( '.toggle-content-container' ).forEach( ( container, index ) => {
			const toggle = container.querySelector( '.toggle-accordion' );
			const content = container.querySelector( '.toggle-content' );
			if ( !toggle || !content ) return;

			const collapsedHeight = parseInt( content.dataset.collapsedHeight ) || 0;
			const accordionId = container.dataset.id || 'accordion-' + index;

			// Restore state from localStorage
			const savedState = localStorage.getItem( 'accordion_' + accordionId );
			if ( savedState === 'open' ) {
				container.classList.add( 'open' );
				content.style.height = content.scrollHeight + 'px';
				requestAnimationFrame( () => {
					content.style.height = 'auto';
				} );
			} else {
				container.classList.remove( 'open' );
				content.style.height = collapsedHeight + 'px';
			}

			// Toggle click
			toggle.addEventListener( 'click', ( event ) => {
				event.stopPropagation();

				const isOpen = container.classList.toggle( 'open' );
				localStorage.setItem( 'accordion_' + accordionId, isOpen ? 'open' : 'closed' );

				if ( isOpen ) {
					content.style.height = content.scrollHeight + 'px';
					const onTransitionEnd = () => {
						content.style.height = 'auto';
						content.removeEventListener( 'transitionend', onTransitionEnd );
					};
					content.addEventListener( 'transitionend', onTransitionEnd );
				} else {
					content.style.height = content.scrollHeight + 'px';
					forceReflow( content ); // Ensure Firefox animates the collapse properly
					requestAnimationFrame( () => {
						content.style.height = collapsedHeight + 'px';
					} );
				}
			} );
		} );

		// Recalculate heights on window resize
		window.addEventListener( 'resize', () => {
			document.querySelectorAll( '.toggle-content-container.open .toggle-content' ).forEach( ( content ) => {
				if ( content.style.height !== 'auto' ) {
					content.style.height = content.scrollHeight + 'px';
				}
			} );
		} );
	} );
}

export function initViewToggles() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const views = {
			index: document.getElementById( 'folders-view' ),
			settings: document.getElementById( 'settings-view' ),
			phpinfo: document.getElementById( 'phpinfo-view' ),
			apache: document.getElementById( 'apache-view' ),
			mysql: document.getElementById( 'mysql-view' ),
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
			'toggle-mysql-inspector': {
				targetKey: 'mysql',
				url: 'utils/mysql_inspector.php',
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
