// assets/js/modules/linkTemplates.js
export function initLinkTemplates() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const list = document.getElementById( 'link-templates-list' );
		const addBtn = document.getElementById( 'add-link-template' );
		if ( !list || !addBtn ) return;

		function loadTemplatesFromFile() {
			list.innerHTML = '';
			fetch( `${ window.BASE_URL }config/link_templates.json`, {cache: 'no-store'} )
				.then( ( res ) => res.json() )
				.then( ( data ) => {
					data.forEach( addTemplateItem );
					updateHiddenInput();
				} );
		}

		function updateHiddenInput() {
			const templates = [];
			list.querySelectorAll( '.template-item' ).forEach( item => {
				const name = item.querySelector( '.template-name' )?.value.trim();
				const html = item.querySelector( '.template-html' )?.value.trim();
				if ( name && html ) templates.push( {name, html} );
			} );

			const hidden = document.getElementById( 'link_templates_json_input' );
			if ( hidden ) hidden.value = JSON.stringify( templates, null, 2 );
		}

		function addTemplateItem( data = {} ) {
			const li = document.createElement( 'li' );
			li.className = 'template-item';
			li.innerHTML = `
        <input type="text" class="template-name" placeholder="Template Name" value="${ data.name || '' }">
        <input type="text" class="template-html" placeholder="HTML Template" value="${ data.html || '' }">
        <button type="button" class="remove-template">❌</button>
      `;

			li.querySelector( '.remove-template' ).addEventListener( 'click', () => {
				li.remove();
				updateHiddenInput();
			} );

			Array.from( li.querySelectorAll( 'input' ) ).forEach( input => {
				input.addEventListener( 'input', updateHiddenInput );
			} );

			list.appendChild( li );
			updateHiddenInput();
		}

		addBtn.addEventListener( 'click', () => addTemplateItem() );

		// Load templates from file on page load
		loadTemplatesFromFile();
	} );
}
