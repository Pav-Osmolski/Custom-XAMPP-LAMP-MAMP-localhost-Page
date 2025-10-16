// assets/js/modules/drag.js
export function enableDragSort( listSelector, opts = {} ) {
	const list = document.querySelector( listSelector );
	if ( !list ) return;

	// Prevent double-initialising the same list
	if ( list.dataset.sortBound === '1' ) return;
	list.dataset.sortBound = '1';

	const itemsSelector = opts.items || 'li';
	const handleSelector = opts.handle || null;

	let dragSrcEl = null;

	function isInteractive( el ) {
		return !!el.closest( 'input, select, textarea, button, a, [contenteditable=""], .no-drag' );
	}

	// Enable "just-in-time" draggable, but only if we’re on a valid item (and, if set, on its handle)
	list.addEventListener( 'pointerdown', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( !item || !list.contains( item ) ) return;
		if ( isInteractive( e.target ) ) return;
		if ( handleSelector && !e.target.closest( handleSelector ) ) return;
		item.draggable = true;
	}, {capture: true} );

	list.addEventListener( 'dragstart', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( !item || !list.contains( item ) ) return;
		dragSrcEl = item;
		e.dataTransfer.effectAllowed = 'move';
		try {
			e.dataTransfer.setData( 'text/plain', '' );
		} catch ( _ ) {
		}
		item.classList.add( 'dragElem' );
	} );

	list.addEventListener( 'dragenter', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( !item || !list.contains( item ) ) return;
		item.classList.add( 'over' );
	} );

	list.addEventListener( 'dragover', ( e ) => {
		if ( dragSrcEl ) e.preventDefault();
	} );

	list.addEventListener( 'dragleave', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( item && list.contains( item ) ) {
			item.classList.remove( 'over' );
		}
	} );

	list.addEventListener( 'drop', ( e ) => {
		e.preventDefault();
		const target = e.target.closest( itemsSelector );
		if ( !target || !dragSrcEl || target === dragSrcEl ) return;

		const children = Array.from( list.querySelectorAll( `:scope > ${ itemsSelector }` ) );
		const from = children.indexOf( dragSrcEl );
		const to = children.indexOf( target );

		if ( from > -1 && to > -1 ) {
			if ( from < to ) {
				target.after( dragSrcEl );
			} else {
				target.before( dragSrcEl );
			}
			list.dispatchEvent( new CustomEvent( 'sorted', {bubbles: true} ) );
		}
		target.classList.remove( 'over' );
	} );

	list.addEventListener( 'dragend', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( item && list.contains( item ) ) {
			item.classList.remove( 'dragElem' );
			item.draggable = false; // return inputs to normal behaviour
		}
		Array.from( list.querySelectorAll( itemsSelector ) ).forEach( el => el.classList.remove( 'over' ) );
		dragSrcEl = null;
	} );
}
