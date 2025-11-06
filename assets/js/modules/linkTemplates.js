// assets/js/modules/linkTemplates.js
import {attachHtmlEditor} from './htmlSyntaxEditor.js';

// 🔧 Toggle this to enable/disable syntax highlighting editor
const USE_SYNTAX_HIGHLIGHTER = true;

export function initLinkTemplates() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const list = document.getElementById( 'link-templates-list' );
		const addBtn = document.getElementById( 'add-link-template' );
		const PLACEHOLDER = 'HTML Template';
		if ( !list || !addBtn ) return;

		function loadTemplatesFromFile() {
			list.innerHTML = '';
			fetch( `${ window.BASE_URL }utils/read_config.php?file=link_templates`, {cache: 'no-store'} )
				.then( res => res.json() )
				.then( data => {
					const arr = Array.isArray( data )
						? data
						: Array.isArray( data?.templates ) ? data.templates : [];
					arr.forEach( addTemplateItem );
					updateHiddenInput();
				} )
				.catch( () => updateHiddenInput() );
		}

		function updateHiddenInput() {
			const templates = [];
			list.querySelectorAll( '.template-item' ).forEach( item => {
				const name = item.querySelector( '.template-name' )?.value.trim();

				let html = '';
				if ( USE_SYNTAX_HIGHLIGHTER ) {
					html = item.querySelector( '.template-html' )?.textContent || '';
				} else {
					html = item.querySelector( '.template-textarea' )?.value || '';
				}

				html = html.replace( /\u200B/g, '' );
				if ( name && html ) templates.push( {name, html} );
			} );
			const hidden = document.getElementById( 'link_templates_json_input' );
			if ( hidden ) hidden.value = JSON.stringify( templates, null, 2 );
		}

		function addTemplateItem( data = {} ) {
			const li = document.createElement( 'li' );
			li.className = 'template-item';

			// Editor markup changes depending on toggle
			let editorMarkup = '';
			if ( USE_SYNTAX_HIGHLIGHTER ) {
				editorMarkup = `
					<div class="template-html-wrapper">
						<pre class="template-html syntax-highlight"
							role="textbox" aria-multiline="true" aria-label="HTML Template"
							placeholder="${ PLACEHOLDER }"></pre>
					</div>`;
			} else {
				editorMarkup = `
					<textarea class="template-textarea"
						placeholder="${ PLACEHOLDER }" spellcheck="false"
						autocomplete="off" autocapitalize="off" autocorrect="off"
						rows="5">${ data.html || '' }</textarea>`;
			}

			li.innerHTML = `
				<input type="text" class="template-name" placeholder="Template Name" value="${ data.name || '' }">
				${ editorMarkup }
				<button type="button" class="remove-template">❌</button>
			`;

			// --- Wire up behaviour ---
			let api = null;

			if ( USE_SYNTAX_HIGHLIGHTER ) {
				const editor = li.querySelector( '.template-html' );
				editor.textContent = data.html || '';

				api = attachHtmlEditor( editor, {
					placeholder: PLACEHOLDER,
					onChange: updateHiddenInput
				} );
			} else {
				const textarea = li.querySelector( '.template-textarea' );
				textarea.addEventListener( 'input', updateHiddenInput );
			}

			// Remove button
			li.querySelector( '.remove-template' ).addEventListener( 'click', () => {
				if ( api && typeof api.destroy === 'function' ) api.destroy();
				li.remove();
				updateHiddenInput();
			} );

			// Sync name field
			li.querySelector( '.template-name' ).addEventListener( 'input', updateHiddenInput );

			list.appendChild( li );
			updateHiddenInput();
		}

		addBtn.addEventListener( 'click', () => addTemplateItem() );
		loadTemplatesFromFile();
	} );
}
