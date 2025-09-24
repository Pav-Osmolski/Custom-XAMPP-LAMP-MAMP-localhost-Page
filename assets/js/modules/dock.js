// assets/js/modules/dock.js
import {enableDragSort} from './drag.js';

export function initDockConfig() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const dockList = document.getElementById( 'dock-list' );
		const addBtn = document.getElementById( 'add-dock-item' );
		const form = document.querySelector( '#settings-view form' );

		if ( !dockList || !addBtn || !form ) return;

		// Load current config
		fetch( `${ window.BASE_URL }utils/read_config.php?file=dock`, {cache: 'no-store'} )
			.then( res => res.json() )
			.then( data => {
				data.forEach( addDockItem );
				enableDragSort( '#dock-list' );
				updateInput();
			} );

		addBtn.addEventListener( 'click', () => {
			addDockItem();
			enableDragSort( '#dock-list' );
			updateInput();
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
                <button type="button" class="remove-dock-item">❌</button>
            `;
			dockList.appendChild( li );

			li.querySelector( '.remove-dock-item' ).addEventListener( 'click', () => {
				li.remove();
				updateInput();
			} );

			Array.from( li.querySelectorAll( 'input' ) ).forEach( input => {
				input.addEventListener( 'input', updateInput );
			} );
		}

		function updateInput() {
			const dockItems = [];
			dockList.querySelectorAll( '.dock-item' ).forEach( li => {
				const inputs = li.querySelectorAll( 'input' );
				dockItems.push( {
					label: inputs[0].value.trim(),
					url: inputs[1].value.trim(),
					icon: inputs[2].value.trim(),
					alt: inputs[3].value.trim()
				} );
			} );
			const dockJsonInput = document.getElementById( 'dock_json_input' );
			if ( dockJsonInput ) {
				dockJsonInput.value = JSON.stringify( dockItems, null, 2 );
			}
		}
	} );
}
