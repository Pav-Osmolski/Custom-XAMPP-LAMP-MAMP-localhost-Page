// assets/js/modules/folders.js
import {enableDragSort} from './drag.js';

export function initFoldersConfig() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const list = document.getElementById( 'folders-config-list' );
		const addBtn = document.getElementById( 'add-folder-column' );
		const jsonInput = document.getElementById( 'folders_json_input' );
		if ( !list || !addBtn || !jsonInput ) return;

		let linkTemplates = [];
		let templateOptionsHtml = `<option value="">(none)</option>`;
		let dirty = false;
		let debounceTimer = null;

		// helpers for specialCases (object map <-> rows)

		function mapToRows( scMap ) {
			if ( scMap && typeof scMap === 'object' && !Array.isArray( scMap ) ) {
				return Object.entries( scMap ).map( ( [ match, replace ] ) => ({
					match: String( match ?? '' ),
					replace: String( replace ?? '' )
				}) );
			}
			return []; // default if missing/invalid
		}

		function rowsToMap( rows ) {
			const map = {};
			rows.forEach( ( {match = '', replace = ''} ) => {
				const m = String( match ).trim();
				const r = String( replace ).trim();
				if ( m.length || r.length ) {
					map[m] = r; // later duplicates overwrite earlier — deterministic
				}
			} );
			return map;
		}

		// templates

		function fetchLinkTemplates() {
			if ( Array.isArray( window.LINK_TEMPLATES ) ) {
				linkTemplates = window.LINK_TEMPLATES
					.map( t => t && typeof t.name === 'string' ? t.name.trim() : '' )
					.filter( Boolean );
				linkTemplates = Array.from( new Set( linkTemplates ) );
				templateOptionsHtml = `<option value="">(none)</option>` + linkTemplates.map( n => `<option value="${ n }">${ n }</option>` ).join( '' );
				return Promise.resolve( linkTemplates );
			}

			return fetch( `${ window.BASE_URL }utils/read_config.php?file=link_templates`, {cache: 'no-store'} )
				.then( res => res.json() )
				.then( data => {
					linkTemplates = (Array.isArray( data ) ? data : [])
						.map( t => t && typeof t.name === 'string' ? t.name.trim() : '' )
						.filter( Boolean );
					linkTemplates = Array.from( new Set( linkTemplates ) );
					templateOptionsHtml = `<option value="">(none)</option>` + linkTemplates.map( n => `<option value="${ n }">${ n }</option>` ).join( '' );
					return linkTemplates;
				} )
				.catch( () => {
					linkTemplates = [];
					templateOptionsHtml = `<option value="">(none)</option>`;
					return linkTemplates;
				} );
		}

		// rendering

		function specialCaseRowHtml( sc = {} ) {
			const matchVal = sc.match || '';
			const replaceVal = sc.replace || '';
			return `
				<div class="special-case">
					<input type="text" class="sc-match" placeholder="Match Regex" value="${ matchVal }">
					<input type="text" class="sc-replace" placeholder="Replace Regex" value="${ replaceVal }">
					<button type="button" class="remove-special">✖</button>
				</div>
			`;
		}

		function folderItemHtml( item = {} ) {
			const casesHtml = mapToRows( item.specialCases ).map( specialCaseRowHtml ).join( '' );
			const selected = item.linkTemplate || '';

			return `
				<li class="folder-config-item">
					<input type="text" data-key="title" placeholder="Title" value="${ item.title || '' }">
					<input type="text" data-key="href" placeholder="Href (optional)" value="${ item.href || '' }">
					<input type="text" data-key="dir" placeholder="Dir (relative to HTDOCS_PATH)" value="${ item.dir || '' }">
					<input type="text" data-key="excludeList" placeholder="Exclude List (comma-separated)" value="${ (item.excludeList || []).join( ',' ) }">
					<input type="text" data-key="match" placeholder="Match Regex" value="${ item.urlRules?.match ?? '' }">
					<input type="text" data-key="replace" placeholder="Replace Regex" value="${ item.urlRules?.replace ?? '' }">
					<label>Link Template:
						<select class="link-template-select" name="linkTemplate">${ templateOptionsHtml }</select>
					</label>
					<label><input type="checkbox" class="disable-links"${ item.disableLinks ? ' checked' : '' }> Disable Links</label>

					<div class="special-cases-wrapper">
						<label>Special Cases:</label>
						<div class="special-cases">${ casesHtml }</div>
						<button type="button" class="add-special">➕ Add Rule</button>
					</div>

					<button type="button" class="remove-folder-column">❌</button>
				</li>
			`;
		}

		function appendFolderItems( items ) {
			const frag = document.createDocumentFragment();
			const tmp = document.createElement( 'div' );
			tmp.innerHTML = items.map( folderItemHtml ).join( '' );
			Array.from( tmp.children ).forEach( child => frag.appendChild( child ) );
			list.appendChild( frag );

			// Apply select value + disable state
			list.querySelectorAll( '.folder-config-item' ).forEach( li => {
				const select = li.querySelector( '.link-template-select' );
				if ( select ) {
					// Find the corresponding item by matching the title currently in the li
					const titleInLi = li.querySelector( 'input[data-key="title"]' )?.value || '';
					const found = items.find( it => (it.title || '') === titleInLi );
					const val = found?.linkTemplate || '';
					if ( val ) select.value = val;
					select.disabled = linkTemplates.length === 0;
				}
			} );
		}

		function addFolderItem( item = {} ) {
			const tmp = document.createElement( 'div' );
			tmp.innerHTML = folderItemHtml( item );
			const li = tmp.firstElementChild;
			list.appendChild( li );
			const select = li.querySelector( '.link-template-select' );
			if ( select ) {
				select.disabled = linkTemplates.length === 0;
				if ( item.linkTemplate ) select.value = item.linkTemplate;
			}
			markDirty();
		}

		// updates

		function markDirty() {
			dirty = true;
			debounceUpdate();
		}

		function debounceUpdate() {
			if ( debounceTimer ) clearTimeout( debounceTimer );
			debounceTimer = setTimeout( updateInput, 120 );
		}

		function updateInput() {
			debounceTimer = null;
			if ( !dirty ) return;
			jsonInput.value = JSON.stringify( serializeList(), null, 2 );
			dirty = false;
		}

		function serializeList() {
			const items = [];
			list.querySelectorAll( '.folder-config-item' ).forEach( li => {
				const get = ( sel ) => li.querySelector( sel );

				const title = get( 'input[data-key="title"]' )?.value.trim() || '';
				const href = get( 'input[data-key="href"]' )?.value.trim() || '';
				const dir = get( 'input[data-key="dir"]' )?.value.trim() || '';
				const excludeStr = get( 'input[data-key="excludeList"]' )?.value || '';
				const match = get( 'input[data-key="match"]' )?.value || '';
				const replace = get( 'input[data-key="replace"]' )?.value || '';
				const linkTemplate = li.querySelector( '.link-template-select' )?.value || '';
				const disableLinks = !!li.querySelector( '.disable-links' )?.checked;

				// Collect specialCases from rows and convert back to an object map
				const scRows = [];
				li.querySelectorAll( '.special-case' ).forEach( row => {
					const m = row.querySelector( '.sc-match' )?.value || '';
					const r = row.querySelector( '.sc-replace' )?.value || '';
					if ( m.length || r.length ) scRows.push( {match: m, replace: r} );
				} );
				const specialCases = rowsToMap( scRows );

				const excludeList = excludeStr.split( ',' ).map( s => s.trim() ).filter( Boolean );

				const record = {
					title,
					href,
					dir,
					excludeList,
					urlRules: {match, replace},
					linkTemplate,
					disableLinks,
					specialCases
				};

				// Skip fully empty rows
				const hasValue =
					title || href || dir || excludeList.length || match || replace || linkTemplate || disableLinks || Object.keys( specialCases ).length;
				if ( hasValue ) items.push( record );
			} );
			return items;
		}

		// delegated events

		list.addEventListener( 'input', ( e ) => {
			if ( e.target.matches( 'input[type="text"], select' ) ) {
				markDirty();
			}
		} );
		list.addEventListener( 'change', ( e ) => {
			if ( e.target.matches( 'select, input[type="checkbox"]' ) ) {
				markDirty();
			}
		} );

		list.addEventListener( 'click', ( e ) => {
			if ( e.target.closest( '.add-special' ) ) {
				const wrap = e.target.closest( '.folder-config-item' )?.querySelector( '.special-cases' );
				if ( wrap ) {
					wrap.insertAdjacentHTML( 'beforeend', specialCaseRowHtml() );
					markDirty();
				}
			}
			if ( e.target.closest( '.remove-special' ) ) {
				const row = e.target.closest( '.special-case' );
				if ( row ) {
					row.remove();
					markDirty();
				}
			}
			if ( e.target.closest( '.remove-folder-column' ) ) {
				const li = e.target.closest( '.folder-config-item' );
				if ( li ) {
					li.remove();
					markDirty();
				}
			}
		} );

		list.addEventListener( 'sorted', () => {
			dirty = true;
			updateInput(); // immediate update after reorder
		} );

		// init

		Promise.all( [
			fetch( `${ window.BASE_URL }utils/read_config.php?file=folders`, {cache: 'no-store'} ).then( r => r.json() ).catch( () => [] ),
			fetchLinkTemplates()
		] ).then( ( [ data ] ) => {
			enableDragSort( '#folders-config-list' );
			if ( Array.isArray( data ) && data.length ) {
				appendFolderItems( data );
			}
			if ( list.children.length === 0 ) addFolderItem( {} );
			dirty = true;
			updateInput();
		} );

		addBtn.addEventListener( 'click', () => {
			addFolderItem( {} );
		} );
	} );
}
