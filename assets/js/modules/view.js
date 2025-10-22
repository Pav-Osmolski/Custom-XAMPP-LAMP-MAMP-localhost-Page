// assets/js/modules/view.js
export function initToggleAccordion() {
	// Ensure Firefox animates collapses
	function forceReflow( el ) {
		void el.offsetHeight;
	}

	function isClickInside( e, sel ) {
		return !!e.target.closest( sel );
	}

	function firstFocusable( root ) {
		return root.querySelector( 'a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])' );
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		document.querySelectorAll( '.toggle-content-container' ).forEach( ( container, index ) => {
			// Guard against double init
			if ( container.dataset.accordionInit === '1' ) return;
			container.dataset.accordionInit = '1';

			const toggle = container.querySelector( '.toggle-accordion' );
			const content = container.querySelector( '.toggle-content' );
			if ( !toggle || !content ) return;

			const collapsedHeight = parseInt( content.dataset.collapsedHeight ) || 0;
			const accordionId = container.dataset.id || 'accordion-' + index;

			// Initial state (restore + ARIA + hidden)
			const saved = localStorage.getItem( 'accordion_' + accordionId );
			const startOpen = saved === 'open';
			container.classList.toggle( 'open', startOpen );
			toggle.setAttribute( 'aria-expanded', startOpen ? 'true' : 'false' );

			if ( startOpen ) {
				// must be visible to measure
				content.hidden = false;
				content.style.height = content.scrollHeight + 'px';
				requestAnimationFrame( () => {
					content.style.height = 'auto';
				} );
			} else {
				content.style.height = collapsedHeight + 'px';
				content.hidden = true; // keyboard users won't tab into closed panel
			}

			function openPanel() {
				// unhiding BEFORE measuring is vital
				content.hidden = false;
				container.classList.add( 'open' );
				toggle.setAttribute( 'aria-expanded', 'true' );

				// animate from collapsed -> target -> auto
				content.style.height = collapsedHeight + 'px';
				requestAnimationFrame( () => {
					const target = content.scrollHeight;
					content.style.height = target + 'px';
					const onEnd = ( e ) => {
						if ( e.target !== content || e.propertyName !== 'height' ) return;
						content.style.height = 'auto';
						content.removeEventListener( 'transitionend', onEnd );
					};
					content.addEventListener( 'transitionend', onEnd );
				} );

				localStorage.setItem( 'accordion_' + accordionId, 'open' );
			}

			function closePanel() {
				container.classList.remove( 'open' );
				toggle.setAttribute( 'aria-expanded', 'false' );

				// animate current -> collapsed, THEN hide for tab order
				content.style.height = content.scrollHeight + 'px';
				forceReflow( content );
				requestAnimationFrame( () => {
					content.style.height = collapsedHeight + 'px';
					const onEnd = ( e ) => {
						if ( e.target !== content || e.propertyName !== 'height' ) return;
						content.hidden = true; // remove from tab order after animation
						content.removeEventListener( 'transitionend', onEnd );
					};
					content.addEventListener( 'transitionend', onEnd );
				} );

				localStorage.setItem( 'accordion_' + accordionId, 'closed' );
			}

			function togglePanel() {
				const willOpen = !container.classList.contains( 'open' );
				willOpen ? openPanel() : closePanel();
			}

			// Mouse/touch
			toggle.addEventListener( 'click', ( event ) => {
				event.stopPropagation();
				// Ignore clicks on tooltip icon
				if ( isClickInside( event, '.tooltip-icon' ) ) return;
				togglePanel();
			} );

			// Keyboard: Space/Enter toggle; ArrowDown jumps into open panel
			toggle.addEventListener( 'keydown', ( e ) => {
				if ( e.key === ' ' || e.key === 'Enter' ) {
					e.preventDefault();
					togglePanel();
				} else if ( e.key === 'ArrowDown' ) {
					if ( container.classList.contains( 'open' ) && !content.hidden ) {
						const target = firstFocusable( content );
						if ( target ) target.focus();
					}
				}
			} );
		} );

		// Keep heights sane on resize
		window.addEventListener( 'resize', () => {
			document.querySelectorAll( '.toggle-content-container.open .toggle-content' ).forEach( ( content ) => {
				if ( !content.hidden && content.style.height !== 'auto' ) {
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
				if ( target && !target.dataset.loaded ) {
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
