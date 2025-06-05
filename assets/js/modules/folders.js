// assets/js/modules/folders.js
import {enableDragSort} from './drag.js';

export function initFoldersConfig() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const list = document.getElementById( 'folders-config-list' );
		const addBtn = document.getElementById( 'add-folder-column' );
		const input = document.getElementById( 'folders_json_input' );

		if ( !list || !addBtn || !input ) return;

		let linkTemplatesCache = null;

		const getLinkTemplates = () => {
			if ( linkTemplatesCache ) return Promise.resolve( linkTemplatesCache );
			return fetch( `${ window.BASE_URL }config/link_templates.json`, {cache: 'no-store'} )
				.then( res => res.json() )
				.then( templates => {
					linkTemplatesCache = templates;
					return templates;
				} );
		};

		const createSpecialCasesHTML = ( specialCases = {} ) => {
			return Object.entries( specialCases ).map(
				( [ key, val ] ) => `
					<div class="special-case">
						<input type="text" class="case-key" placeholder="match" value="${ key }">
						<input type="text" class="case-val" placeholder="replacement" value="${ val }">
						<button type="button" class="remove-special">❌</button>
					</div>
				`
			).join( '' );
		};

		const populateLinkTemplates = ( select, selectedTemplate ) => {
			getLinkTemplates().then( templates => {
				let found = false;
				templates.forEach( template => {
					const option = document.createElement( 'option' );
					option.value = template.name;
					option.textContent = template.name;
					if ( template.name === selectedTemplate ) {
						option.selected = true;
						found = true;
					}
					select.appendChild( option );
				} );
				if ( !found && selectedTemplate ) {
					const customOption = document.createElement( 'option' );
					customOption.value = selectedTemplate;
					customOption.textContent = selectedTemplate;
					customOption.selected = true;
					select.appendChild( customOption );
				}
				updateInput();
			} );
		};

		const addFolderItem = ( item = {} ) => {
			const li = document.createElement( 'li' );
			li.className = 'folder-config-item';
			li.draggable = true;

			const casesHtml = createSpecialCasesHTML( item.specialCases );

			li.innerHTML = `
				<input type="text" data-key="title" placeholder="Title" value="${ item.title || '' }">
				<input type="text" data-key="href" placeholder="Href (optional)" value="${ item.href || '' }">
				<input type="text" data-key="dir" placeholder="Dir (relative to HTDOCS_PATH)" value="${ item.dir || '' }">
				<input type="text" data-key="excludeList" placeholder="Exclude List (comma-separated)" value="${ (item.excludeList || []).join( ',' ) }">
				<input type="text" data-key="match" placeholder="Match Regex" value="${ item.urlRules?.match ?? '' }">
				<input type="text" data-key="replace" placeholder="Replace Regex" value="${ item.urlRules?.replace ?? '' }">
				<label>Link Template: <select class="link-template-select" name="linkTemplate"></select></label>
				<label><input type="checkbox" class="disable-links" ${ item.disableLinks ? 'checked' : '' }> Disable Links</label>
				<div class="special-cases-wrapper">
					<label>Special Cases:</label>
					<div class="special-cases">${ casesHtml }</div>
					<button type="button" class="add-special">➕ Add Rule</button>
				</div>
				<button type="button" class="remove-folder-column">❌</button>
			`;

			list.appendChild( li );
			li.querySelector( 'input[data-key="title"]' ).focus();

			const select = li.querySelector( '.link-template-select' );
			populateLinkTemplates( select, item.linkTemplate );

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
			} );

			li.querySelector( '.special-cases' ).addEventListener( 'click', e => {
				if ( e.target.matches( '.remove-special' ) ) {
					e.target.closest( '.special-case' ).remove();
					updateInput();
				}
			} );

			li.querySelectorAll( 'input, select' ).forEach( el => {
				el.addEventListener( 'input', updateInput );
				el.addEventListener( 'change', updateInput );
			} );
		};

		const updateInput = () => {
			const items = [];
			list.querySelectorAll( '.folder-config-item' ).forEach( li => {
				const getValue = selector => li.querySelector( selector )?.value.trim() || '';
				const specialCases = {};
				li.querySelectorAll( '.special-case' ).forEach( row => {
					const key = row.querySelector( '.case-key' ).value.trim();
					const val = row.querySelector( '.case-val' ).value.trim();
					if ( key ) specialCases[key] = val;
				} );

				items.push( {
					title: getValue( 'input[data-key="title"]' ),
					href: getValue( 'input[data-key="href"]' ),
					dir: getValue( 'input[data-key="dir"]' ),
					excludeList: getValue( 'input[data-key="excludeList"]' ).split( ',' ).map( s => s.trim() ).filter( Boolean ),
					urlRules: {
						match: getValue( 'input[data-key="match"]' ),
						replace: getValue( 'input[data-key="replace"]' )
					},
					linkTemplate: li.querySelector( '.link-template-select' )?.value || '',
					disableLinks: li.querySelector( '.disable-links' )?.checked || false,
					specialCases
				} );
			} );
			input.value = JSON.stringify( items, null, 2 );
		};

		fetch( `${ window.BASE_URL }config/folders.json`, {cache: 'no-store'} )
			.then( res => res.json() )
			.then( data => {
				data.forEach( item => addFolderItem( item ) );
				enableDragSort( '#folders-config-list' );
			} );

		addBtn.addEventListener( 'click', () => {
			addFolderItem();
			updateInput();
		} );
	} );
}
