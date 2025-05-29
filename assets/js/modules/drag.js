// assets/js/modules/drag.js
export function enableDragSort( listSelector ) {
	const list = document.querySelector( listSelector );
	if ( !list ) return;

	let dragSrcEl = null;

	function handleDragStart( e ) {
		dragSrcEl = this;
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/html', this.outerHTML );
		this.classList.add( 'dragElem' );
	}

	function handleDragOver( e ) {
		if ( e.preventDefault ) e.preventDefault();
		this.classList.add( 'over' );
		e.dataTransfer.dropEffect = 'move';
		return false;
	}

	function handleDragEnter() {
		this.classList.add( 'over' );
	}

	function handleDragLeave() {
		this.classList.remove( 'over' );
	}

	function handleDrop( e ) {
		if ( e.stopPropagation ) e.stopPropagation();
		if ( dragSrcEl !== this ) {
			const dropHTML = e.dataTransfer.getData( 'text/html' );
			const tempDiv = document.createElement( 'div' );
			tempDiv.innerHTML = dropHTML.trim();
			const dropElem = tempDiv.firstChild;

			const bounding = this.getBoundingClientRect();
			const offset = e.clientY - bounding.top;

			if ( offset > bounding.height / 2 ) {
				this.after( dropElem );
			} else {
				this.before( dropElem );
			}

			dragSrcEl.remove();
			addDnDHandlers( dropElem );
		}
		this.classList.remove( 'over' );
		return false;
	}

	function handleDragEnd() {
		this.classList.remove( 'over' );
		this.classList.remove( 'dragElem' );
	}

	function addDnDHandlers( elem ) {
		elem.addEventListener( 'dragstart', handleDragStart );
		elem.addEventListener( 'dragenter', handleDragEnter );
		elem.addEventListener( 'dragover', handleDragOver );
		elem.addEventListener( 'dragleave', handleDragLeave );
		elem.addEventListener( 'drop', handleDrop );
		elem.addEventListener( 'dragend', handleDragEnd );
	}

	Array.from( list.children ).forEach( addDnDHandlers );
}
