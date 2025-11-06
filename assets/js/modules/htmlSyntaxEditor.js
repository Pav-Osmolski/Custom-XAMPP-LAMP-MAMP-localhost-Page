// assets/js/modules/htmlSyntaxEditor.js

/* Public API
 * ----------
 * import { attachHtmlEditor } from './htmlSyntaxEditor.js';
 *
 * const api = attachHtmlEditor( editorEl, {
 *   placeholder : 'HTML Template',
 *   onChange    : ( value ) => { /* value is plain text, ZWSP stripped * / },
 * });
 *
 * api.getValue();         // -> plain text (no ZWSP)
 * api.setValue( text );   // replace content (plain text)
 * api.destroy();          // remove listeners
 */

export function attachHtmlEditor( editor, opts = {} ) {
	const PLACEHOLDER = opts.placeholder || 'HTML Template';
	let lastText = '';
	let renderScheduled = false;
	let allowFocusEscape = false; // ← Esc sets this true to let Tab move focus

	// Set base attributes once
	editor.setAttribute( 'contenteditable', 'true' );
	editor.setAttribute( 'role', 'textbox' );
	editor.setAttribute( 'aria-multiline', 'true' );
	editor.setAttribute( 'aria-label', PLACEHOLDER );
	editor.setAttribute( 'aria-describedby', opts.describedBy || 'editor-instructions' );
	editor.setAttribute( 'placeholder', PLACEHOLDER );
	editor.setAttribute( 'spellcheck', 'false' );
	editor.setAttribute( 'autocomplete', 'off' );
	editor.setAttribute( 'autocapitalize', 'off' );
	editor.setAttribute( 'autocorrect', 'off' );

	function getPlainText() {
		// normalise NBSP, strip zero-width spaces
		return editor.textContent.replace( /\u200B/g, '' ).replace( /\u00A0/g, ' ' );
	}

	function saveCaretPosition( element ) {
		const sel = window.getSelection();
		if ( !sel.rangeCount ) return null;
		const range = sel.getRangeAt( 0 );
		const pre = range.cloneRange();
		pre.selectNodeContents( element );
		pre.setEnd( range.endContainer, range.endOffset );
		const len = element.textContent.length;
		return Math.min( pre.toString().length, len );
	}

	function restoreCaretPosition( element, position ) {
		const sel = window.getSelection();
		const range = document.createRange();
		let current = 0;
		let lastTextNode = null;
		const walker = document.createTreeWalker( element, NodeFilter.SHOW_TEXT, null, false );

		while ( walker.nextNode() ) {
			const node = walker.currentNode;
			const len = node.nodeValue.length;
			lastTextNode = node;
			if ( current + len >= position ) {
				range.setStart( node, position - current );
				range.collapse( true );
				sel.removeAllRanges();
				sel.addRange( range );
				return;
			}
			current += len;
		}

		if ( !lastTextNode ) {
			const empty = document.createTextNode( '' );
			element.appendChild( empty );
			lastTextNode = empty;
		}
		range.setStart( lastTextNode, lastTextNode.nodeValue.length );
		range.collapse( true );
		sel.removeAllRanges();
		sel.addRange( range );
	}

	function highlightHTML( text ) {
		const escaped = text
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );

		return escaped.replace( /&lt;\/?[a-zA-Z][^&]*?&gt;/g, match => {
			let inner = match.slice( 4, -4 ).trim();
			let isClosing = false;
			let selfClosing = false;

			if ( inner.startsWith( '/' ) ) {
				isClosing = true;
				inner = inner.slice( 1 ).trim();
			}
			if ( inner.endsWith( '/' ) ) {
				selfClosing = true;
				inner = inner.slice( 0, -1 ).trim();
			}

			const firstSpace = inner.search( /\s/ );
			const tagName = (firstSpace === -1 ? inner : inner.slice( 0, firstSpace )).toLowerCase();
			let rest = firstSpace === -1 ? '' : inner.slice( firstSpace ).trim();

			rest = rest.replace(
				/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*(=\s*(?:"[^"]*"|'[^']*'|[^\s'">=]+))?/g,
				( _, name, eqPart ) => {
					if ( !name ) return _;
					if ( !eqPart ) return `<span class="attr">${ name }</span>`;
					const m = eqPart.match( /^=\s*(?:"([^"]*)"|'([^']*)'|([^\s'">=]+))/ );
					if ( !m ) return `<span class="attr">${ name }</span>${ eqPart || '' }`;

					const value = m[1] ?? m[2] ?? m[3] ?? '';
					const quote = m[1] ? '"' : (m[2] ? "'" : '');

					return quote
						? `<span class="attr">${ name }</span>=<span class="value">${ quote }${ value }${ quote }</span>`
						: `<span class="attr">${ name }</span>=<span class="value">${ value }</span>`;
				}
			);

			const open = `<span class="pun">&lt;${ isClosing ? '/' : '' }</span>`;
			const t = `<span class="tag">${ tagName }</span>`;
			const body = rest ? ` ${ rest }` : '';
			const close = `<span class="pun">${ selfClosing ? ' /' : '' }&gt;</span>`;
			return `${ open }${ t }${ body }${ close }`;
		} );
	}

	function renderHighlight() {
		renderScheduled = false;

		const raw = getPlainText();
		if ( raw === lastText ) return;

		const caretPos = saveCaretPosition( editor );

		let html = raw;
		// Chromium collapses trailing \n in editable <pre>
		if ( html.endsWith( '\n' ) && !html.endsWith( '\n\u200B' ) ) {
			html += '\u200B';
		}

		editor.innerHTML = highlightHTML( html );

		// Re-assert placeholder attribute (innerHTML wipes it on some engines)
		if ( !editor.hasAttribute( 'placeholder' ) ) {
			editor.setAttribute( 'placeholder', PLACEHOLDER );
		}

		// Ensure a tail text node only when content exists
		if ( raw.length > 0 && (!editor.lastChild || editor.lastChild.nodeType !== Node.TEXT_NODE) ) {
			editor.appendChild( document.createTextNode( '' ) );
		}

		if ( caretPos != null ) restoreCaretPosition( editor, caretPos );

		lastText = raw;
		opts.onChange && opts.onChange( raw );
	}

	function scheduleRender() {
		if ( !renderScheduled ) {
			renderScheduled = true;
			requestAnimationFrame( () => {
				try {
					renderHighlight();
				} finally {
					renderScheduled = false;
				}
			} );
		}
	}

	// PASTE as plain text
	function setTextAtCaret( text ) {
		const plain = getPlainText();
		const pos = saveCaretPosition( editor ) ?? plain.length;
		const newText = plain.slice( 0, pos ) + text + plain.slice( pos );
		editor.textContent = newText;
		restoreCaretPosition( editor, pos + text.length );
		lastText = '';
		renderHighlight();
	}

	// Events
	function onBeforeInput( e ) {
		if ( e.inputType === 'insertFromPaste' && e.clipboardData ) {
			e.preventDefault();
			const txt = e.clipboardData.getData( 'text/plain' ).replace( /\r\n?/g, '\n' );
			setTextAtCaret( txt );
		}
	}

	function onKeyDown( e ) {
		// ESC: next Tab should move focus out (don’t indent)
		if ( e.key === 'Escape' ) {
			allowFocusEscape = true;
			return;
		}

		// TAB pressed after ESC: let the browser move focus (next/prev control)
		if ( e.key === 'Tab' && allowFocusEscape ) {
			allowFocusEscape = false; // consume the escape state
			return; // no preventDefault → native focus navigation
		}

		// TAB (indent / unindent) – only when not escaping focus
		if ( e.key === 'Tab' ) {
			e.preventDefault();
			const text = getPlainText();
			let pos = saveCaretPosition( editor );
			let newText = text;

			if ( e.shiftKey ) {
				// Unindent (quick heuristics for a single cursor)
				if ( text.slice( pos - 1, pos ) === '\t' ) {
					newText = text.slice( 0, pos - 1 ) + text.slice( pos );
					pos -= 1;
				} else if ( text.slice( pos - 2, pos ) === '  ' ) {
					newText = text.slice( 0, pos - 2 ) + text.slice( pos );
					pos -= 2;
				}
			} else {
				// Indent
				newText = text.slice( 0, pos ) + '\t' + text.slice( pos );
				pos += 1;
			}

			editor.textContent = newText;
			restoreCaretPosition( editor, pos );
			lastText = '';
			renderHighlight();
			return;
		}

		// ENTER (manual newline + auto-indent)
		if ( e.key === 'Enter' && !e.shiftKey ) {
			const plain = getPlainText();
			if ( plain.length === 0 ) return; // let browser insert first break

			e.preventDefault();

			let pos = saveCaretPosition( editor );
			const before = plain.slice( 0, pos );
			const lastLine = before.split( /\r?\n/ ).pop() || '';
			const indent = (lastLine.match( /^[\t ]+/ ) || [ '' ])[0];

			const insert = '\n' + indent;
			let newText = plain.slice( 0, pos ) + insert + plain.slice( pos );
			let newPos = pos + insert.length;

			if ( newText === plain ) {
				newText = plain + insert;
				newPos = plain.length + insert.length;
			}

			editor.textContent = newText;
			editor.focus();
			restoreCaretPosition( editor, newPos );
			lastText = '';
			renderHighlight();
		}
	}

	function onInput( e ) {
		// Normalise ghost empties after delete/cut (e.g. <div><br></div>)
		const isWhitespaceOnly = /^[\s\u200B]*$/.test( editor.textContent );
		if ( isWhitespaceOnly && ((e.inputType || '').startsWith( 'delete' ) || e.inputType === 'deleteByCut') ) {
			editor.innerHTML = '';
			lastText = '';
			opts.onChange && opts.onChange( '' );
			return;
		}

		if (
			e.inputType === 'insertParagraph' ||
			e.inputType === 'insertFromPaste' ||
			e.inputType === 'insertFromDrop' ||
			e.inputType === 'historyUndo' ||
			e.inputType === 'historyRedo'
		) {
			lastText = '';
			renderHighlight();
			return;
		}
		scheduleRender();
	}

	function onBlur() {
		allowFocusEscape = false; // leaving the editor cancels escape mode
		renderHighlight();
	}

	// Bind
	editor.addEventListener( 'beforeinput', onBeforeInput );
	editor.addEventListener( 'keydown', onKeyDown );
	editor.addEventListener( 'input', onInput );
	editor.addEventListener( 'blur', onBlur );

	// Initial paint
	renderHighlight();

	return {
		getValue() {
			return getPlainText();
		},
		setValue( text ) {
			editor.textContent = (text || '');
			lastText = '';
			renderHighlight();
		},
		destroy() {
			editor.removeEventListener( 'beforeinput', onBeforeInput );
			editor.removeEventListener( 'keydown', onKeyDown );
			editor.removeEventListener( 'input', onInput );
			editor.removeEventListener( 'blur', onBlur );
		}
	};
}
