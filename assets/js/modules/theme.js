// assets/js/modules/theme.js
export function initThemeSwitcher() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const selector = document.querySelector( '#theme-selector' );
		if ( !selector ) return;

		// Use serverTheme first, then fallback to default
		const saved =
			localStorage.getItem( 'theme' ) ||
			(typeof serverTheme !== 'undefined' ? serverTheme : 'default');

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
	const root = document.documentElement;

	// Remove any existing *-theme classes
	root.className = root.className
		.split( /\s+/ )
		.filter( ( cls ) => cls && !cls.endsWith( '-theme' ) )
		.join( ' ' );

	if ( theme !== 'default' ) {
		root.classList.add( `${ theme }-theme` );
	}

	// Default to dark mode
	let colorScheme = 'dark';
	if ( !document.body.classList.contains( 'dark-mode' ) ) {
		document.body.classList.remove( 'light-mode' );
		document.body.classList.add( 'dark-mode' );
	}

	// Apply light mode if defined in themeTypes
	if ( typeof themeTypes !== 'undefined' && themeTypes[theme] === 'light' ) {
		colorScheme = 'light';
		if ( !document.body.classList.contains( 'light-mode' ) ) {
			document.body.classList.remove( 'dark-mode' );
			document.body.classList.add( 'light-mode' );
		}
	}

	// Update or create <meta name="color-scheme">
	let meta = document.querySelector( 'meta[name="color-scheme" ]' );
	if ( !meta ) {
		meta = document.createElement( 'meta' );
		meta.setAttribute( 'name', 'color-scheme' );
		document.head.appendChild( meta );
	}
	if ( meta.getAttribute( 'content' ) !== colorScheme ) {
		meta.setAttribute( 'content', colorScheme );
	}
}
