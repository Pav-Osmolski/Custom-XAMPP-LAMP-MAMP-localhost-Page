// assets/js/modules/export.js
export function initExportModule() {
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', () => setupExport() );
	} else {
		setupExport();
	}
}

function setupExport() {
	const groupEl = document.getElementById( 'export-group' );
	const folderEl = document.getElementById( 'export-folder' );
	const uploadsModeRow = document.getElementById( 'uploads-mode-row' );

	const filesForm = document.getElementById( 'export-files-form' );
	const filesStatus = document.getElementById( 'export-files-status' );

	const dbForm = document.getElementById( 'export-db-form' );
	const dbSelect = document.getElementById( 'export-db' );
	const dbStatus = document.getElementById( 'export-db-status' );

	if ( !groupEl || !folderEl || !filesForm || !dbForm ) return;

	let groups = [];

	// Helpers
	function refreshCsrf() {
		return fetch( `${ window.BASE_URL }utils/export_files.php?action=token`, {credentials: 'same-origin'} )
			.then( r => r.json() )
			.then( d => {
				if ( d.ok && d.token ) {
					document.querySelectorAll( 'input[name="csrf"]' ).forEach( el => {
						el.value = d.token;
					} );
				}
			} )
			.catch( () => {
			} );
	}

	function getArchiveEngine() {
		const checked = document.querySelector( 'input[name="archiveEngine"]:checked' );
		return checked ? checked.value : 'php';
	}

	function renderGroups( selectEl, data ) {
		selectEl.innerHTML = '';
		data.forEach( ( g ) => {
			const opt = document.createElement( 'option' );
			opt.value = String( g.index );
			opt.textContent = g.title;
			selectEl.appendChild( opt );
		} );
	}

	function renderFolders( selectEl, subfolders ) {
		selectEl.innerHTML = '';
		if ( !subfolders.length ) {
			selectEl.innerHTML = `<option value="">No subfolders found</option>`;
			return;
		}
		subfolders.forEach( sf => {
			const opt = document.createElement( 'option' );
			opt.value = sf.name;
			opt.textContent = sf.name;
			selectEl.appendChild( opt );
		} );
	}

	function renderDatabases( selectEl, dbs ) {
		selectEl.innerHTML = '';
		if ( !dbs.length ) {
			selectEl.innerHTML = `<option value="">No databases found</option>`;
			return;
		}
		dbs.forEach( name => {
			const opt = document.createElement( 'option' );
			opt.value = name;
			opt.textContent = name;
			selectEl.appendChild( opt );
		} );
	}

	// Initial loads
	refreshCsrf();

	fetch( `${ window.BASE_URL }utils/export_files.php?action=scan`, {credentials: 'same-origin'} )
		.then( r => r.json() )
		.then( data => {
			if ( !data.ok ) {
				throw new Error( data.error || 'Failed to scan folders' );
			}

			groups = data.groups || [];

			if ( !groups.length ) {
				// Graceful empty state
				groupEl.innerHTML = `<option value="">No folders defined</option>`;
				groupEl.disabled = true;

				folderEl.innerHTML = `<option value="">No subfolders</option>`;
				folderEl.disabled = true;

				if ( filesStatus ) {
					filesStatus.innerHTML = 'No folders defined. Add entries to <code>config/folders.json</code> to enable file exports.';
				}

				if ( uploadsModeRow ) {
					uploadsModeRow.style.display = 'none';
				}

				return; // stop here
			}

			renderGroups( groupEl, groups );
			if ( groupEl.options.length ) {
				groupEl.dispatchEvent( new Event( 'change' ) );
			}
		} )
		.catch( err => {
			console.error( err );
			groupEl.innerHTML = `<option value="">Error loading groups</option>`;
			groupEl.disabled = true;
			folderEl.innerHTML = `<option value="">—</option>`;
			folderEl.disabled = true;
		} );

	fetch( `${ window.BASE_URL }utils/export_files.php?action=dbs`, {credentials: 'same-origin'} )
		.then( r => r.json() )
		.then( data => {
			if ( !data.ok ) {
				throw new Error( data.error || 'Failed to list databases' );
			}
			renderDatabases( dbSelect, data.databases || [] );
		} )
		.catch( err => {
			console.error( err );
			dbSelect.innerHTML = `<option value="">Error loading databases</option>`;
		} );

	// Events
	groupEl.addEventListener( 'change', () => {
		const idx = parseInt( groupEl.value, 10 );
		const group = Number.isNaN( idx ) ? null : groups[idx];

		// Reset UI
		folderEl.disabled = true;
		folderEl.innerHTML = `<option value="">Select a group first…</option>`;
		if ( uploadsModeRow ) {
			uploadsModeRow.style.display = 'none';
		}

		const def = document.querySelector( 'input[name="uploadsMode"][value="exclude"]' );
		if ( def ) def.checked = true;

		if ( !group ) return;

		renderFolders( folderEl, group.subfolders );
		folderEl.disabled = (group.subfolders.length === 0);

		// Auto-select first folder and trigger its change
		if ( group.subfolders.length > 0 ) {
			folderEl.selectedIndex = 0;
			folderEl.dispatchEvent( new Event( 'change' ) );
		}
	} );

	folderEl.addEventListener( 'change', () => {
		const gIdx = parseInt( groupEl.value, 10 );
		const group = Number.isNaN( gIdx ) ? null : groups[gIdx];
		if ( !group ) return;

		const sf = group.subfolders.find( s => s.name === folderEl.value );
		const showUploads = !!(sf && sf.isWordPress && sf.hasUploads);

		if ( uploadsModeRow ) {
			uploadsModeRow.style.display = showUploads ? '' : 'none';
		}

		// default to exclude on change
		const def = document.querySelector( 'input[name="uploadsMode"][value="exclude"]' );
		if ( def ) def.checked = true;
	} );

	filesForm.addEventListener( 'submit', e => {
		e.preventDefault();
		filesStatus.textContent = 'Preparing archive…';

		const form = new FormData( filesForm );
		form.append( 'action', 'zip' );
		form.append( 'engine', getArchiveEngine() ); // php | external

		fetch( `${ window.BASE_URL }utils/export_files.php`, {
			method: 'POST',
			body: form,
			credentials: 'same-origin'
		} )
			.then( r => r.json() )
			.then( data => {
				if ( !data.ok ) throw new Error( data.error || 'Export failed' );

				let html = `Done. <a href="${ window.BASE_URL }${ data.href }" download="${ data.name }">${ data.name }</a>`;
				if ( data.message ) {
					html += `<br><small class="muted">${ data.message }</small>`;
				}

				filesStatus.innerHTML = html;
				window.location.href = `${ window.BASE_URL }${ data.href }`;
				return refreshCsrf();
			} )
			.catch( err => {
				console.error( err );
				filesStatus.textContent = 'Error: ' + err.message;
				return refreshCsrf();
			} );
	} );

	dbForm.addEventListener( 'submit', e => {
		e.preventDefault();
		dbStatus.textContent = 'Dumping database…';

		const form = new FormData( dbForm );
		form.append( 'action', 'dumpdb' );
		form.append( 'engine', getArchiveEngine() ); // php | external

		fetch( `${ window.BASE_URL }utils/export_files.php`, {
			method: 'POST',
			body: form,
			credentials: 'same-origin'
		} )
			.then( r => r.json() )
			.then( data => {
				if ( !data.ok ) {
					throw new Error( data.error || 'DB export failed' );
				}

				let html = `Done. <a href="${ window.BASE_URL }${ data.href }" download="${ data.name }">${ data.name }</a>`;
				if ( data.message ) {
					html += `<br><small class="muted">${ data.message }</small>`;
				}

				dbStatus.innerHTML = html;
				window.location.href = `${ window.BASE_URL }${ data.href }`;
				return refreshCsrf();
			} )
			.catch( err => {
				console.error( err );
				dbStatus.textContent = 'Error: ' + err.message;
				return refreshCsrf();
			} );
	} );
}
