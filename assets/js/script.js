function updateClock() {
	const now = new Date();
	const hours = now.getHours().toString().padStart( 2, '0' );
	const minutes = now.getMinutes().toString().padStart( 2, '0' );
	const seconds = now.getSeconds().toString().padStart( 2, '0' );
	document.querySelector( '.clock' ).textContent = `${ hours }:${ minutes }:${ seconds }`;
}

setInterval( updateClock, 1000 );
document.addEventListener( 'DOMContentLoaded', updateClock );

document.addEventListener( "DOMContentLoaded", function () {
	const theme = localStorage.getItem( "theme" );

	if ( theme === "light" ) {
		document.documentElement.classList.add( "light-mode" );
		document.body.classList.add( "light-mode" );
	}

	document.querySelector( ".toggle-theme" ).addEventListener( "click", function () {
		document.documentElement.classList.toggle( "light-mode" );
		document.body.classList.toggle( "light-mode" );

		if ( document.documentElement.classList.contains( "light-mode" ) ) {
			localStorage.setItem( "theme", "light" );
		} else {
			localStorage.setItem( "theme", "dark" );
		}
	} );
} );

function searchProjects() {
	let input = document.querySelector( '.search-bar' ).value.toLowerCase();
	let items = document.querySelectorAll( '.folders li' );

	items.forEach( item => {
		let text = item.textContent.toLowerCase();
		if ( text.includes( input ) ) {
			item.style.display = '';
		} else {
			item.style.display = 'none';
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', function () {
	const body = document.body;
	const useAjax = body.getAttribute( "data-ajax-enabled" ) === "true";

	// Error Log Section
	const errorLogSection = document.getElementById( 'error-log-section' );
	if ( errorLogSection ) {
		document.getElementById( 'toggle-error-log' ).addEventListener( 'click', function () {
			const errorLog = document.getElementById( 'error-log' );
			const isVisible = errorLog.style.display === 'block';
			errorLog.style.display = isVisible ? 'none' : 'block';
			this.setAttribute( 'aria-expanded', !isVisible );
		} );

		function fetchErrorLog() {
			const url = useAjax ? "ajax_apache_error_log.php" : "apache_error_log.php";
			fetch( url, {cache: 'no-store'} )
				.then( response => {
					if ( !response.ok ) throw new Error( 'Error log unavailable' );
					return response.text();
				} )
				.then( data => {
					document.getElementById( 'error-log' ).innerHTML = `<code>${ data }</code>`;
				} )
				.catch( error => {
					console.error( 'Error fetching error log:', error );
					clearInterval( errorLogInterval );
				} );
		}

		let errorLogInterval = setInterval( fetchErrorLog, 3000 );
		fetchErrorLog();
	}

	// System Stats Section
	const systemStatsSection = document.getElementById( 'system-monitor' );
	if ( systemStatsSection ) {
		function fetchSystemStats() {
			const url = useAjax ? "ajax_system_stats.php" : "system_stats.php";
			fetch( url, {cache: 'no-store'} )
				.then( response => {
					if ( !response.ok ) throw new Error( 'System stats unavailable' );
					return response.json();
				} )
				.then( data => {
					document.getElementById( 'cpu-load' ).textContent = `${ data.cpu }%`;
					document.getElementById( 'memory-usage' ).textContent = `${ data.memory } MB`;
					document.getElementById( 'disk-space' ).textContent = `${ data.disk }%`;
				} )
				.catch( error => {
					console.error( 'Error fetching system stats:', error );
					clearInterval( statsInterval );
				} );
		}

		let statsInterval = setInterval( fetchSystemStats, 1000 );
		fetchSystemStats();
	}
} );

const columnSizes = [ 'max-800', 'max-1300', 'max-1600', 'max-fit-content' ];

function setColumnWidth( size ) {
	localStorage.setItem( 'columnSize', size );
	const columnsDiv = document.querySelector( '.columns' );
	if ( !columnsDiv ) return;

	// Remove any class starting with "max-"
	columnsDiv.classList.forEach( cls => {
		if ( cls.startsWith( 'max-' ) ) {
			columnsDiv.classList.remove( cls );
		}
	} );

	if ( size !== 'auto' ) {
		columnsDiv.classList.add( size );
	}
}

function cycleColumnWidth( direction ) {
	let currentSize = localStorage.getItem( 'columnSize' );
	let index = columnSizes.indexOf( currentSize );

	// Default to before first if nothing is set
	if ( index === -1 ) {
		index = direction === 'next' ? -1 : 0;
	}

	if ( direction === 'next' && index < columnSizes.length - 1 ) {
		index += 1;
	} else if ( direction === 'prev' && index > 0 ) {
		index -= 1;
	}

	setColumnWidth( columnSizes[index] );
}

document.addEventListener( 'DOMContentLoaded', function () {
	const savedSize = localStorage.getItem( 'columnSize' );
	if ( savedSize ) {
		setColumnWidth( savedSize );
	}
} );
