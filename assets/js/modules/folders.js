// assets/js/modules/folders.js
import {enableDragSort} from './drag.js';

export function initFoldersConfig() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const list = document.getElementById( 'folders-config-list' );
		const addBtn = document.getElementById( 'add-folder-column' );
		const input = document.getElementById( 'folders_json_input' );

		if ( !list || !addBtn || !input ) return;

		fetch( 'partials/folders.json' )
			.then( res => res.json() )
			.then( data => {
				data.forEach( item => addFolderItem( item ) );
				updateInput();
				enableDragSort( '#folders-config-list' );
			} );

		addBtn.addEventListener( 'click', () => {
			addFolderItem();
			updateInput();
		} );

		function addFolderItem( item = {} ) {
			const li = document.createElement( 'li' );
			li.className = 'folder-config-item';
			li.draggable = true;

			const specialCases = item.specialCases || {};
			const casesHtml = Object.entries( specialCases ).map(
				( [ key, val ] ) => `
                    <div class="special-case">
                        <input type="text" class="case-key" placeholder="match" value="${ key }">
                        <input type="text" class="case-val" placeholder="replacement" value="${ val }">
                        <button type="button" class="remove-special">❌</button>
                    </div>
                `
			).join( '' );

			li.innerHTML = `
                <input type="text" placeholder="Title" value="${ item.title || '' }">
                <input type="text" placeholder="Href (optional)" value="${ item.href || '' }">
                <input type="text" placeholder="Dir (relative to HTDOCS_PATH)" value="${ item.dir || '' }">
                <input type="text" placeholder="Exclude List (comma-separated)" value="${ (item.excludeList || []).join( ',' ) }">
                <input type="text" placeholder="Match Regex" value="${ item.urlRules?.match || '' }">
                <input type="text" placeholder="Replace Regex" value="${ item.urlRules?.replace || '' }">
                <label>Link Template:
                  <select>
                    <option value="basic" ${ item.linkTemplate === 'basic' ? 'selected' : '' }>basic</option>
                    <option value="env-links" ${ item.linkTemplate === 'env-links' ? 'selected' : '' }>env-links</option>
                    <option value="pantheon" ${ item.linkTemplate === 'pantheon' ? 'selected' : '' }>pantheon</option>
                  </select>
                </label>
                <label><input type="checkbox" class="disable-links" ${ item.disableLinks ? 'checked' : '' }> Disable Links</label>
                <div class="special-cases-wrapper">
                  <label>Special Cases:</label>
                  <div class="special-cases">${ casesHtml }</div>
                  <button type="button" class="add-special">➕ Add Rule</button>
                </div>
                <button type="button" class="remove-folder-column">❌</button>
            `;

			list.appendChild( li );

			li.querySelector( '.remove-folder-column' ).addEventListener( 'click', () => {
				li.remove();
				updateInput();
			} );

			li.querySelector( '.add-special' ).addEventListener( 'click', () => {
				const container = li.querySelector( '.special-cases' );
				const div = document.createElement( 'div' );
				div.className = 'special-case';
				div.innerHTML = `
                    <input type="text" class="case-key" placeholder="match">
                    <input type="text" class="case-val" placeholder="replacement">
                    <button type="button" class="remove-special">❌</button>
                `;
				container.appendChild( div );
				div.querySelector( '.remove-special' ).addEventListener( 'click', () => {
					div.remove();
					updateInput();
				} );
			} );

			li.querySelectorAll( '.remove-special' ).forEach( btn =>
				btn.addEventListener( 'click', e => {
					e.target.closest( '.special-case' ).remove();
					updateInput();
				} )
			);

			Array.from( li.querySelectorAll( 'input, select' ) ).forEach( el => {
				el.addEventListener( 'input', updateInput );
				el.addEventListener( 'change', updateInput );
			} );
		}

		function updateInput() {
			const items = [];
			list.querySelectorAll( '.folder-config-item' ).forEach( li => {
				const inputs = li.querySelectorAll( 'input' );
				const selects = li.querySelectorAll( 'select' );

				const specialCaseElements = li.querySelectorAll( '.special-case' );
				const specialCases = {};
				specialCaseElements.forEach( row => {
					const key = row.querySelector( '.case-key' ).value.trim();
					const val = row.querySelector( '.case-val' ).value.trim();
					if ( key ) specialCases[key] = val;
				} );

				const disableLinks = li.querySelector( '.disable-links' )?.checked || false;

				items.push( {
					title: inputs[0].value.trim(),
					href: inputs[1].value.trim(),
					dir: inputs[2].value.trim(),
					excludeList: inputs[3].value.split( ',' ).map( s => s.trim() ).filter( Boolean ),
					urlRules: {
						match: inputs[4].value.trim(),
						replace: inputs[5].value.trim()
					},
					linkTemplate: selects[0].value,
					disableLinks,
					specialCases
				} );
			} );

			input.value = JSON.stringify( items, null, 2 );
		}
	} );
}
