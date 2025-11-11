// assets/js/modules/columns.js
import {enableDragSort} from './drag.js';

const WIDTH_SIZES = [ 'max-xs', 'max-sm', 'max-md', 'max-lg' ];

function getWidthKey( el ) {
	return el.dataset.widthKey || 'width_element';
}

function setWidthForElement( el, size ) {
	if ( !el ) return;

	const storageKey = getWidthKey( el );

	// Always store the chosen size, even "auto"
	localStorage.setItem( storageKey, size );

	// Remove any existing max-* classes
	el.classList.forEach( ( cls ) => {
		if ( cls.startsWith( 'max-' ) ) {
			el.classList.remove( cls );
		}
	} );

	// Only add a class if not "auto"
	if ( size !== 'auto' ) {
		el.classList.add( size );
	} else {
		// Clear any inline max-width overrides
		el.style.maxWidth = '';
	}
}

function cycleWidthForElement( el, direction, sizes = WIDTH_SIZES ) {
	if ( !el ) return;

	const storageKey = getWidthKey( el );
	const current = localStorage.getItem( storageKey ) || 'auto';
	let idx = sizes.indexOf( current );
	let nextIdx;

	// If current is not in sizes (e.g. "auto"), start from an edge
	if ( idx === -1 ) {
		nextIdx = direction === 'prev'
			? sizes.length - 1 // go to the last size on prev
			: 0;               // go to the first size on next
	} else if ( direction === 'next' ) {
		nextIdx = (idx + 1) % sizes.length;
	} else if ( direction === 'prev' ) {
		nextIdx = (idx - 1 + sizes.length) % sizes.length;
	} else {
		// Unknown direction, bail
		return;
	}

	setWidthForElement( el, sizes[nextIdx] );
}

function resolveWidthTarget( target ) {
	if ( !target ) {
		// No implicit default: caller must pass a target or key
		return null;
	}

	if ( target instanceof HTMLElement ) {
		return target;
	}

	if ( typeof target === 'string' ) {
		// First try by data-width-key
		const byKey = document.querySelector( `.width-resizable[data-width-key="${ target }"]` );
		if ( byKey ) return byKey;

		// Fallback: treat as selector
		return document.querySelector( target );
	}

	return null;
}

// Public API: still named for backwards compatibility
export function setColumnWidth( size, target ) {
	const el = resolveWidthTarget( target );
	setWidthForElement( el, size );
}

export function cycleColumnWidth( direction, sizes = WIDTH_SIZES, target ) {
	const el = resolveWidthTarget( target );
	cycleWidthForElement( el, direction, sizes );
}

export function initColumnFeatures() {
	document.addEventListener( 'DOMContentLoaded', () => {
		// Find all resizable elements
		const resizables = document.querySelectorAll( '.width-resizable' );

		// Restore widths for each element
		resizables.forEach( ( el ) => {
			const storageKey = getWidthKey( el );
			const savedSize = localStorage.getItem( storageKey );

			// only apply stored sizes if it's a known max-* value
			if ( savedSize && WIDTH_SIZES.includes( savedSize ) ) {
				setWidthForElement( el, savedSize );
			}
			// else: either "auto" or nothing, so do nothing
		} );

		// Wire up all control groups
		const controlGroups = document.querySelectorAll( '.width-controls' );

		controlGroups.forEach( ( controls ) => {
			const key = controls.dataset.widthFor;
			if ( !key ) return;

			const target = document.querySelector( `.width-resizable[data-width-key="${ key }"]` );
			if ( !target ) return;

			const resetBtn = controls.querySelector( '[data-action="reset"]' );
			const prevBtn = controls.querySelector( '[data-action="decrease"]' );
			const nextBtn = controls.querySelector( '[data-action="increase"]' );

			if ( resetBtn ) {
				resetBtn.addEventListener( 'click', () => {
					setWidthForElement( target, 'auto' );
				} );
			}

			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', () => {
					cycleWidthForElement( target, 'prev', WIDTH_SIZES );
				} );
			}

			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', () => {
					cycleWidthForElement( target, 'next', WIDTH_SIZES );
				} );
			}
		} );

		const container = document.querySelector( '.columns' );
		if ( container ) {
			const savedOrder = JSON.parse( localStorage.getItem( 'columnOrder' ) || '[]' );
			if ( Array.isArray( savedOrder ) && savedOrder.length ) {
				savedOrder.forEach( ( id ) => {
					const col = document.getElementById( id );
					if ( col && col.classList.contains( 'column' ) && !col.classList.contains( 'column-controls' ) ) {
						container.appendChild( col );
					}
				} );
			}

			function saveOrder() {
				const order = Array
					.from( container.querySelectorAll( ':scope > .column:not(.column-controls)' ) )
					.map( ( el ) => el.id )
					.filter( Boolean );

				localStorage.setItem( 'columnOrder', JSON.stringify( order ) );
			}

			enableDragSort( '.columns', {
				items: '.column:not(.column-controls)',
				handle: '.drag-handle'
			} );

			container.addEventListener( 'sorted', saveOrder );
		}
	} );
}
