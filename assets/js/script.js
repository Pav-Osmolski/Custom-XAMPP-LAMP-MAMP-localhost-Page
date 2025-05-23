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

const columnSizes = [ 'max-800', 'max-1300', 'max-1600' ];

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

document.addEventListener( 'DOMContentLoaded', () => {
	const views = {
		'index': document.getElementById( 'folders-view' ),
		'settings': document.getElementById( 'settings-view' ),
		'phpinfo': document.getElementById( 'phpinfo-view' )
	};

	const toggles = {
		'toggle-index': 'index',
		'toggle-settings': 'settings',
		'toggle-phpinfo': 'phpinfo'
	};

	Object.entries( toggles ).forEach( ( [ toggleId, viewKey ] ) => {
		const toggle = document.getElementById( toggleId );
		if ( toggle ) {
			toggle.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				Object.entries( views ).forEach( ( [ key, el ] ) => {
					el.style.display = (key === viewKey) ? 'block' : 'none';
				} );
			} );
		}
	} );
} );

document.addEventListener( 'DOMContentLoaded', function () {
	const container = document.querySelector( '.columns' );
	if ( !container ) return;

	let draggedItem = null;

	// Restore saved order
	const savedOrder = JSON.parse( localStorage.getItem( 'columnOrder' ) || '[]' );
	if ( savedOrder.length ) {
		savedOrder.forEach( id => {
			const col = document.getElementById( id );
			if ( col ) container.appendChild( col );
		} );
	}

	function saveOrder() {
		const order = Array.from( container.children )
			.filter( el => !el.classList.contains( 'column-controls' ) )
			.map( el => el.id );
		localStorage.setItem( 'columnOrder', JSON.stringify( order ) );
	}

	container.querySelectorAll( '.column:not(.column-controls)' ).forEach( column => {
		const handle = column.querySelector( '.drag-handle' );
		if ( !handle ) return;

		column.setAttribute( 'draggable', false ); // Default to not draggable

		handle.addEventListener( 'mousedown', () => {
			column.setAttribute( 'draggable', true );
		} );

		handle.addEventListener( 'mouseup', () => {
			column.setAttribute( 'draggable', false );
		} );

		column.addEventListener( 'dragstart', () => {
			draggedItem = column;
			column.style.opacity = '0.5';
		} );

		column.addEventListener( 'dragend', () => {
			draggedItem = null;
			column.style.opacity = '1';
			column.setAttribute( 'draggable', false );
			saveOrder();
		} );

		column.addEventListener( 'dragover', e => e.preventDefault() );

		column.addEventListener( 'drop', e => {
			e.preventDefault();
			if ( draggedItem && draggedItem !== column ) {
				const all = [ ...container.children ];
				const dropIndex = all.indexOf( column );
				const dragIndex = all.indexOf( draggedItem );
				if ( dragIndex < dropIndex ) {
					column.after( draggedItem );
				} else {
					column.before( draggedItem );
				}
			}
		} );
	} );
} );

// Dock Configuration Script
document.addEventListener( 'DOMContentLoaded', function () {
	const dockList = document.getElementById( 'dock-list' );
	const addBtn = document.getElementById( 'add-dock-item' );

	if ( !dockList || !addBtn ) return;

	// Load current config
	fetch( 'partials/dock.json' )
		.then( res => res.json() )
		.then( data => {
			data.forEach( addDockItem );
		} );

	function addDockItem( item = {} ) {
		const li = document.createElement( 'li' );
		li.className = 'dock-item';
		li.draggable = true;
		li.innerHTML = `
            <input type="text" placeholder="Label" value="${ item.label || '' }">
            <input type="text" placeholder="URL" value="${ item.url || '' }">
            <input type="text" placeholder="Icon" value="${ item.icon || '' }">
            <input type="text" placeholder="Alt Text" value="${ item.alt || '' }">
            <button class="remove">🗑️</button>
        `;
		dockList.appendChild( li );
		li.querySelector( '.remove' ).onclick = () => li.remove();
		handleDrag( li );
	}

	addBtn.onclick = () => addDockItem();

	function handleDrag( item ) {
		item.addEventListener( 'dragstart', e => {
			item.classList.add( 'dragging' );
		} );
		item.addEventListener( 'dragend', e => {
			item.classList.remove( 'dragging' );
		} );
	}

	dockList.addEventListener( 'dragover', e => {
		e.preventDefault();
		const dragging = dockList.querySelector( '.dragging' );
		const afterElement = getDragAfterElement( dockList, e.clientY );
		if ( afterElement == null ) {
			dockList.appendChild( dragging );
		} else {
			dockList.insertBefore( dragging, afterElement );
		}
	} );

	function getDragAfterElement( container, y ) {
		const items = [ ...container.querySelectorAll( '.dock-item:not(.dragging)' ) ];
		return items.reduce( ( closest, child ) => {
			const box = child.getBoundingClientRect();
			const offset = y - box.top - box.height / 2;
			return offset < 0 && offset > closest.offset
				? {offset: offset, element: child}
				: closest;
		}, {offset: Number.NEGATIVE_INFINITY} ).element;
	}

	// Save hook
	const form = document.querySelector( '#settings-view form' );
	form.addEventListener( 'submit', () => {
		const items = [ ...dockList.querySelectorAll( '.dock-item' ) ].map( li => {
			const [ label, url, icon, alt ] = li.querySelectorAll( 'input' );
			return {
				label: label.value,
				url: url.value,
				icon: icon.value,
				alt: alt.value
			};
		} );
		const hidden = document.createElement( 'input' );
		hidden.type = 'hidden';
		hidden.name = 'dock_json';
		hidden.value = JSON.stringify( items );
		form.appendChild( hidden );
	} );
} );
