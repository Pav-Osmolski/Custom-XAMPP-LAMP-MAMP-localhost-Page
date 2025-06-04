// assets/js/modules/settings.js
export function initClearStorageButton() {
	document.addEventListener( 'DOMContentLoaded', () => {
		const clearBtn = document.getElementById( 'clear-local-storage' );
		if ( !clearBtn ) return;

		clearBtn.addEventListener( 'click', () => {
			if ( !confirm( "Are you sure you want to reset saved UI settings?" ) ) return;

			const keysToRemove = [ 'theme', 'columnOrder', 'columnSize' ];

			Object.keys( localStorage ).forEach( ( key ) => {
				if ( prefixesToRemove.some( prefix => key.startsWith( prefix ) ) ) {
					localStorage.removeItem( key );
				}
			} );

			alert( "Saved settings have been cleared from local storage." );
			location.reload();
		} );
	} );
}
