// assets/js/modules/drag.js
const _initialisedLists = new WeakSet();

export function enableDragSort( listSelector, opts = {} ) {
	const list = document.querySelector( listSelector );
	if ( !list ) return;

	if ( _initialisedLists.has( list ) ) return;
	_initialisedLists.add( list );

	const itemsSelector = opts.items || 'li';
	const handleSelector = opts.handle || null;

	// Configurable "interactive" rules
	const interactiveSelector = opts.interactiveSelector || 'input, select, textarea, button, a, [contenteditable], .no-drag';
	const allowInsideHandle = opts.allowInsideHandle || '[data-drag-allow]'; // whitelist inside handle
	const allowHandleInteractive = opts.allowHandleInteractive ?? true; // allow whitelisted interactives inside handle

	let dragSrcEl = null;
	let dragging = false;

	function isInteractive( el ) {
		return !!el.closest( interactiveSelector );
	}

	list.addEventListener( 'pointerdown', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( !item || !list.contains( item ) ) return;

		const isHandleHit = handleSelector ? !!e.target.closest( handleSelector ) : true;
		if ( handleSelector && !isHandleHit ) return;

		const interactiveHit = isInteractive( e.target );

		// If we hit an interactive element…
		if ( interactiveHit ) {
			// …but it's inside the handle and on the allowlist, let it start drag
			const allowed = allowHandleInteractive && isHandleHit && e.target.closest( allowInsideHandle );
			if ( !allowed ) return;
		}

		item.draggable = true;
	}, {capture: true} );

	list.addEventListener( 'dragstart', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( !item || !list.contains( item ) ) return;
		dragSrcEl = item;
		dragging = true;

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
		if ( item && list.contains( item ) ) item.classList.remove( 'over' );
	} );

	list.addEventListener( 'drop', ( e ) => {
		e.preventDefault();
		const target = e.target.closest( itemsSelector );
		if ( !target || !dragSrcEl || target === dragSrcEl ) return;

		const children = Array.from( list.querySelectorAll( `:scope > ${ itemsSelector }` ) );
		const from = children.indexOf( dragSrcEl );
		const to = children.indexOf( target );

		if ( from > -1 && to > -1 ) {
			if ( from < to ) target.after( dragSrcEl );
			else target.before( dragSrcEl );
			list.dispatchEvent( new CustomEvent( 'sorted', {bubbles: true} ) );
		}
		target.classList.remove( 'over' );
	} );

	list.addEventListener( 'dragend', ( e ) => {
		const item = e.target.closest( itemsSelector );
		if ( item && list.contains( item ) ) {
			item.classList.remove( 'dragElem' );
			item.draggable = false;
		}
		Array.from( list.querySelectorAll( itemsSelector ) ).forEach( el => el.classList.remove( 'over' ) );
		dragSrcEl = null;
		dragging = false;
	} );

	// Suppress accidental click activation after a drag
	list.addEventListener( 'click', ( e ) => {
		if ( !dragging ) return;
		if ( handleSelector && e.target.closest( handleSelector ) ) e.preventDefault();
	}, true );
}
