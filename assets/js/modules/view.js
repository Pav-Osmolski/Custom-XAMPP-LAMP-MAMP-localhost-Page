// assets/js/modules/view.js
export function initToggleAccordion() {
	// Helper: force layout reflow to ensure transitions behave correctly (especially in Firefox)
	function forceReflow( element ) {
		void element.offsetHeight;
	}

	// Helper: check if the click happened inside a given selector
	function isClickInside( event, selector ) {
		return !!event.target.closest( selector );
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

				// Prevent toggling if clicking inside .tooltip-icon
				if ( isClickInside( event, '.tooltip-icon' ) ) {
					return;
				}

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
			'index': document.getElementById( 'folders-view' ),
			'settings': document.getElementById( 'settings-view' ),
			'phpinfo': document.getElementById( 'phpinfo-view' ),
			'apache': document.getElementById( 'apache-view' ),
			'mysql': document.getElementById( 'mysql-view' ),
		};

		const pathMap = {
			'index': 'index',
			'settings': 'settings',
			'phpinfo': 'phpinfo',
			'apache-inspector': 'apache',
			'mysql-inspector': 'mysql',
		};

		const toggleMap = {
			'toggle-index': 'index',
			'toggle-settings': 'settings',
			'toggle-phpinfo': 'phpinfo',
			'toggle-apache-inspector': 'apache-inspector',
			'toggle-mysql-inspector': 'mysql-inspector',
		};

		function getCurrentRoute() {
			const params = new URLSearchParams( window.location.search );
			return params.get( 'view' ) || 'index';
		}

		function showView( routeKey, push = false ) {
			const key = pathMap[routeKey] || 'index';

			Object.entries( views ).forEach( ( [ k, el ] ) => {
				if ( el ) el.classList.toggle( 'visible', k === key );
			} );

			// Lazy load for Apache and MySQL views
			if ( key === 'apache' || key === 'mysql' ) {
				const target = views[key];
				if ( !target.dataset.loaded ) {
					// To use a spinning emoji, add the class "emoji" to "loader" and insert your emoji within the <span>
					target.innerHTML = '<div class="loader"><span class="spinner" role="img" aria-label="Loading"></span></div>';
					const url = `utils/${ key }_inspector.php`;

					fetch( `${ window.BASE_URL || '/' }${ url }` )
						.then( res => res.text() )
						.then( html => {
							target.innerHTML = html;
							target.dataset.loaded = 'true';
						} )
						.catch( err => {
							target.innerHTML = `<p class="error">Failed to load ${ url }</p>`;
							console.error( `[view.js] Failed to fetch ${ url }`, err );
						} );
				}
			}

			if ( push ) {
				const base = window.BASE_URL || window.location.pathname;
				const param = key === 'index' ? '' : '?view=' + encodeURIComponent( routeKey );
				history.pushState( {}, '', base + param );
			}
		}

		// Attach toggle click handlers
		Object.entries( toggleMap ).forEach( ( [ toggleId, routeKey ] ) => {
			const toggle = document.getElementById( toggleId );
			if ( toggle ) {
				toggle.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					showView( routeKey, true );
				} );
			}
		} );

		// Initial load
		showView( getCurrentRoute() );

		// Back/forward navigation
		window.addEventListener( 'popstate', () => {
			showView( getCurrentRoute() );
		} );
	} );
}
