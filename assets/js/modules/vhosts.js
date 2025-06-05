// assets/js/modules/vhosts.js
export function setupVhostCertButtons() {
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', bindButtons );
    } else {
        bindButtons();
    }
}

function bindButtons() {
    const buttons = document.querySelectorAll( '[data-generate-cert]' );

    buttons.forEach( button => {
        button.addEventListener( 'click', () => {
            const name = button.dataset.generateCert;
            if ( confirm( 'Generate a new SSL certificate for ' + name + '?' ) ) {
                fetch( `${ window.BASE_URL }utils/generate_cert.php?name=${ encodeURIComponent( name ) }` )
                    .then( res => res.text() )
                    .then( msg => {
                        alert( msg );
                        if ( msg.includes( 'successfully' ) ) {
                            const restartBtn = document.getElementById( 'restart-apache' );
                            if ( restartBtn ) restartBtn.click();
                        }
                    })
                    .catch( () => alert( 'Failed to run cert script.' ) );
            }
        });
    });
}
