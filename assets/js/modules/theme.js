// assets/js/modules/theme.js
export function initThemeSwitcher() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const selector = document.querySelector( '#theme-selector' );
		if ( !selector ) return;

		// Use serverTheme first, then fallback to localStorage
		const saved = localStorage.getItem( 'theme' ) || serverTheme || 'default';
		setTheme( saved );

		selector.value = saved;
		selector.addEventListener( 'change', ( e ) => {
			const selected = e.target.value;
			setTheme( selected );
			localStorage.setItem( 'theme', selected );
		} );
	} );
}

function setTheme( theme ) {
	document.documentElement.className = '';

	if ( theme !== 'default' ) {
		document.documentElement.classList.add( `${ theme }-theme` );
	}

	document.body.classList.remove( 'light-mode' );

	if ( typeof themeTypes !== 'undefined' && themeTypes[theme] === 'light' ) {
		document.body.classList.add( 'light-mode' );
	}
}
