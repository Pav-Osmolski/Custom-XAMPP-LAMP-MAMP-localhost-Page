// assets/js/modules/columns.js
import {enableDragSort} from './drag.js';

export function setColumnWidth( size ) {
	localStorage.setItem( 'columnSize', size );
	const columnsDiv = document.querySelector( '.columns' );
	if ( !columnsDiv ) return;

	columnsDiv.classList.forEach( ( cls ) => {
		if ( cls.startsWith( 'max-' ) ) {
			columnsDiv.classList.remove( cls );
		}
	} );

	if ( size !== 'auto' ) {
		columnsDiv.classList.add( size );
	}
}

export function cycleColumnWidth( direction, sizes ) {
	const current = localStorage.getItem( 'columnSize' ) || 'auto';
	const idx = sizes.indexOf( current );
	let nextIdx = 0;

	if ( direction === 'next' ) {
		nextIdx = (idx + 1) % sizes.length;
	} else if ( direction === 'prev' ) {
		nextIdx = (idx - 1 + sizes.length) % sizes.length;
	}

	setColumnWidth( sizes[nextIdx] );
}

export function initColumnFeatures() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const container = document.querySelector( '.columns' );
		if ( !container ) return;

		// Restore saved width
		const columnSizes = [ 'max-xs', 'max-sm', 'max-md', 'max-lg' ];
		const savedSize = localStorage.getItem( 'columnSize' );
		if ( savedSize && columnSizes.includes( savedSize ) ) {
			setColumnWidth( savedSize );
		}

		// Width controls
		const resetBtn = document.getElementById( 'reset-width' );
		const prevBtn = document.getElementById( 'prev-width' );
		const nextBtn = document.getElementById( 'next-width' );

		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', () => setColumnWidth( 'auto' ) );
		}
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', () => cycleColumnWidth( 'prev', columnSizes ) );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', () => cycleColumnWidth( 'next', columnSizes ) );
		}

		// Restore saved column order (IDs are the source of truth)
		const savedOrder = JSON.parse( localStorage.getItem( 'columnOrder' ) || '[]' );
		if ( Array.isArray( savedOrder ) && savedOrder.length ) {
			savedOrder.forEach( ( id ) => {
				const col = document.getElementById( id );
				// skip control “column” rows if any; only reorder actual columns
				if ( col && col.classList.contains( 'column' ) && !col.classList.contains( 'column-controls' ) ) {
					container.appendChild( col );
				}
			} );
		}

		function saveOrder() {
			const order = Array.from( container.querySelectorAll( ':scope > .column:not(.column-controls)' ) )
				.map( ( el ) => el.id )
				.filter( Boolean );
			localStorage.setItem( 'columnOrder', JSON.stringify( order ) );
		}

		// Hook into the shared drag util (same pattern as dock/folders)
		enableDragSort( '.columns', {
			items: '.column:not(.column-controls)',
			handle: '.drag-handle'
		} );

		// Persist whenever user reorders
		container.addEventListener( 'sorted', saveOrder );
	} );
}
