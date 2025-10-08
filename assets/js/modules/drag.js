// assets/js/modules/drag.js
export function enableDragSort( listSelector ) {
	const list = document.querySelector( listSelector );
	if ( !list ) return;

	// Prevent double-initialising the same list
	if ( list.dataset.sortBound === '1' ) return;
	list.dataset.sortBound = '1';

	let dragSrcEl = null;

	function isInteractive( el ) {
		return !!el.closest( 'input, select, textarea, button, a, [contenteditable=""], .no-drag' );
	}

	// We only toggle draggable dynamically when the pointer goes down on a list item
	list.addEventListener( 'pointerdown', ( e ) => {
		const li = e.target.closest( 'li' );
		if ( !li || !list.contains( li ) ) return;
		if ( isInteractive( e.target ) ) return; // don't start drag from form controls
		li.draggable = true; // enable just-in-time
	}, { capture: true } );

	list.addEventListener( 'dragstart', ( e ) => {
		const li = e.target.closest( 'li' );
		if ( !li || !list.contains( li ) ) return;
		dragSrcEl = li;
		e.dataTransfer.effectAllowed = 'move';
		li.classList.add( 'dragElem' );
	});

	list.addEventListener( 'dragenter', ( e ) => {
		const li = e.target.closest( 'li' );
		if ( !li || !list.contains( li ) ) return;
		li.classList.add( 'over' );
	});

	list.addEventListener( 'dragover', ( e ) => {
		// Allow dropping
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
	});

	list.addEventListener( 'dragleave', ( e ) => {
		const li = e.target.closest( 'li' );
		if ( !li || !list.contains( li ) ) return;
		li.classList.remove( 'over' );
	});

	list.addEventListener( 'drop', ( e ) => {
		e.preventDefault();
		const li = e.target.closest( 'li' );
		if ( !li || !list.contains( li ) ) return;
		if ( dragSrcEl && dragSrcEl !== li ) {
			const items = Array.from( list.children );
			const from = items.indexOf( dragSrcEl );
			const to = items.indexOf( li );
			if ( from < to ) {
				list.insertBefore( dragSrcEl, li.nextSibling );
			} else {
				list.insertBefore( dragSrcEl, li );
			}
			list.dispatchEvent( new CustomEvent( 'sorted', { bubbles: true } ) );
		}
		li.classList.remove( 'over' );
	});

	list.addEventListener( 'dragend', ( e ) => {
		const li = e.target.closest( 'li' );
		if ( li && list.contains( li ) ) {
			li.classList.remove( 'dragElem' );
			li.draggable = false; // remove again so inputs behave normally
		}
		Array.from( list.children ).forEach( el => el.classList.remove( 'over' ) );
	});
}
