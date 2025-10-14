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

	if ( !groupEl || !folderEl ) return;

	let groups = [];

	// Helpers
	function refreshCsrf() {
		return fetch( `${ window.BASE_URL }partials/export.php?action=token`, {credentials: 'same-origin'} )
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

	fetch( `${ window.BASE_URL }partials/export.php?action=scan`, {credentials: 'same-origin'} )
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

				const filesStatus = document.getElementById( 'export-files-status' );
				if (filesStatus) {
					filesStatus.innerHTML = 'No folders defined. Add entries to <code>config/folders.json</code> to enable file exports.';
				}

				// Hide media options just in case
				const uploadsModeRow = document.getElementById( 'uploads-mode-row' );
				if ( uploadsModeRow ) uploadsModeRow.style.display = 'none';

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

	fetch( `${ window.BASE_URL }partials/export.php?action=dbs`, {credentials: 'same-origin'} )
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
		if ( Number.isNaN( idx ) || !groups[idx] ) {
			folderEl.innerHTML = `<option value="">Select a group first…</option>`;
			folderEl.disabled = true;
			uploadsModeRow.style.display = 'none';
			// reset radio to exclude
			const def = document.querySelector( 'input[name="uploadsMode"][value="exclude"]' );
			if ( def ) def.checked = true;
			return;
		}
		renderFolders( folderEl, groups[idx].subfolders );
		folderEl.disabled = false;
		uploadsModeRow.style.display = 'none';
		const def = document.querySelector( 'input[name="uploadsMode"][value="exclude"]' );
		if ( def ) def.checked = true;
	} );

	folderEl.addEventListener( 'change', () => {
		const gIdx = parseInt( groupEl.value, 10 );
		const group = groups[gIdx];
		if ( !group ) return;
		const sf = group.subfolders.find( s => s.name === folderEl.value );
		const showUploads = !!(sf && sf.isWordPress && sf.hasUploads);

		uploadsModeRow.style.display = showUploads ? '' : 'none';
		// default to exclude on change
		const def = document.querySelector( 'input[name="uploadsMode"][value="exclude"]' );
		if ( def ) def.checked = true;
	} );

	filesForm.addEventListener( 'submit', e => {
		e.preventDefault();
		filesStatus.textContent = 'Preparing archive…';

		const form = new FormData( filesForm );
		form.append( 'action', 'zip' ); // radios are included automatically

		fetch( `${ window.BASE_URL }partials/export.php`, {
			method: 'POST',
			body: form,
			credentials: 'same-origin'
		} )
			.then( r => r.json() )
			.then( data => {
				if ( !data.ok ) {
					throw new Error( data.error || 'Export failed' );
				}
				filesStatus.innerHTML = `Done. <a href="${ window.BASE_URL }${ data.href }" download="${ data.name }">${ data.name }</a>`;
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

		fetch( `${ window.BASE_URL }partials/export.php`, {
			method: 'POST',
			body: form,
			credentials: 'same-origin'
		} )
			.then( r => r.json() )
			.then( data => {
				if ( !data.ok ) {
					throw new Error( data.error || 'DB export failed' );
				}
				dbStatus.innerHTML = `Done. <a href="${ window.BASE_URL }${ data.href }" download="${ data.name }">${ data.name }</a>`;
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
