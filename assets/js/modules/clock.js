// assets/js/modules/clock.js
export function initClock() {
	document.addEventListener( 'DOMContentLoaded', () => {
		updateClock();
		setInterval( updateClock, 1000 );
	} );
}

export function updateClock() {
	const clock = document.querySelector( '.clock' );
	if ( clock ) {
		const now = new Date();
		clock.textContent = now.toLocaleTimeString( [], {
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			hour12: false
		} );
	}
}
