// assets/js/modules/view.js
export function initViewToggles() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const views = {
			index: document.getElementById( 'folders-view' ),
			settings: document.getElementById( 'settings-view' ),
			phpinfo: document.getElementById( 'phpinfo-view' ),
		};

		const toggles = {
			'toggle-index': 'index',
			'toggle-settings': 'settings',
			'toggle-phpinfo': 'phpinfo',
		};

		Object.entries( toggles ).forEach( ( [ toggleId, viewKey ] ) => {
			const toggle = document.getElementById( toggleId );
			if ( toggle ) {
				toggle.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					Object.entries( views ).forEach( ( [ key, el ] ) => {
						if ( el ) el.style.display = key === viewKey ? 'block' : 'none';
					} );
				} );
			}
		} );
	} );
}
