// assets/js/modules/theme.js
export function initThemeToggle() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const storedTheme = localStorage.getItem( 'theme' );

		if ( storedTheme === 'light' ) {
			document.documentElement.classList.add( 'light-mode' );
			document.body.classList.add( 'light-mode' );
		}

		const toggleButton = document.querySelector( '.toggle-theme' );
		if ( !toggleButton ) return;

		toggleButton.addEventListener( 'click', () => {
			document.documentElement.classList.toggle( 'light-mode' );
			document.body.classList.toggle( 'light-mode' );

			const isLight = document.documentElement.classList.contains( 'light-mode' );
			localStorage.setItem( 'theme', isLight ? 'light' : 'dark' );
		} );
	} );
}
