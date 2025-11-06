// assets/js/modules/accordion.js
export function initToggleAccordion() {
    // Ensure Firefox animates collapses
    function forceReflow( el ) {
        void el.offsetHeight;
    }

    function isClickInside( e, sel ) {
        return !!e.target.closest( sel );
    }

    function firstFocusable( root ) {
        return root.querySelector( 'a,button,input,select,textarea,[tabindex]:not([tabindex="-1"])' );
    }

    function getTransitionMs( el ) {
        const cs = getComputedStyle( el );
        // Handles multiple comma-separated durations (take the longest)
        const parseList = ( s ) => s.split( ',' ).map( v => parseFloat( v ) || 0 );
        const d = Math.max( ...parseList( cs.transitionDuration ) );
        const delay = Math.max( ...parseList( cs.transitionDelay ) );
        return Math.round( (d + delay) * 1000 ) || 0;
    }

    document.addEventListener( 'DOMContentLoaded', () => {
        document.querySelectorAll( '.toggle-content-container' ).forEach( ( container, index ) => {
            // Guard against double init
            if ( container.dataset.accordionInit === '1' ) return;
            container.dataset.accordionInit = '1';

            const toggle = container.querySelector( '.toggle-accordion' );
            const content = container.querySelector( '.toggle-content' );
            if ( !toggle || !content ) return;

            const collapsedHeight = parseInt( content.dataset.collapsedHeight ) || 0;
            const accordionId = container.dataset.id || 'accordion-' + index;

            // Animation control
            let animToken = 0;      // bumped for each open/close run
            let fallbackTimer = 0;  // transitionend safety net
            let onEnd = null;       // current transitionend handler

            function clearAnimGuards() {
                if ( onEnd ) {
                    content.removeEventListener( 'transitionend', onEnd );
                    onEnd = null;
                }
                if ( fallbackTimer ) {
                    clearTimeout( fallbackTimer );
                    fallbackTimer = 0;
                }
            }

            function setOpenAria( isOpen ) {
                container.classList.toggle( 'open', isOpen );
                toggle.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
                localStorage.setItem( 'accordion_' + accordionId, isOpen ? 'open' : 'closed' );
            }

            function finishOpen( token ) {
                if ( token !== animToken ) return;
                content.style.height = 'auto';
            }

            function finishClose( token ) {
                if ( token !== animToken ) return;
                content.hidden = true; // remove from tab order after animation
            }

            function openPanel() {
                const token = ++animToken;
                clearAnimGuards();

                // Unhide immediately for any open intent
                content.hidden = false;
                setOpenAria( true );

                // Animate from collapsed -> target -> auto
                content.style.height = collapsedHeight + 'px';
                forceReflow( content );
                requestAnimationFrame( () => {
                    if ( token !== animToken ) return;
                    const target = content.scrollHeight;
                    content.style.height = target + 'px';

                    onEnd = ( e ) => {
                        if ( e.target !== content || e.propertyName !== 'height' ) return;
                        clearAnimGuards();
                        finishOpen( token );
                    };
                    content.addEventListener( 'transitionend', onEnd );

                    const ms = getTransitionMs( content );
                    if ( ms > 0 ) {
                        fallbackTimer = setTimeout( () => {
                            clearAnimGuards();
                            finishOpen( token );
                        }, ms + 40 );
                    }
                } );
            }

            function closePanel() {
                const token = ++animToken;
                clearAnimGuards();

                setOpenAria( false );

                // Animate current -> collapsed, THEN hide
                content.style.height = content.scrollHeight + 'px';
                forceReflow( content );
                requestAnimationFrame( () => {
                    if ( token !== animToken ) return;
                    content.style.height = collapsedHeight + 'px';

                    onEnd = ( e ) => {
                        if ( e.target !== content || e.propertyName !== 'height' ) return;
                        clearAnimGuards();
                        finishClose( token );
                    };
                    content.addEventListener( 'transitionend', onEnd );

                    const ms = getTransitionMs( content );
                    if ( ms > 0 ) {
                        fallbackTimer = setTimeout( () => {
                            clearAnimGuards();
                            finishClose( token );
                        }, ms + 40 );
                    }
                } );
            }

            function togglePanel() {
                const willOpen = !container.classList.contains( 'open' );
                // If opening, guarantee visibility immediately to avoid “stuck hidden”
                if ( willOpen && content.hidden ) content.hidden = false;
                willOpen ? openPanel() : closePanel();

                // Self-heal if state and hidden diverge
                requestAnimationFrame( () => {
                    const isOpen = container.classList.contains( 'open' );
                    if ( isOpen && content.hidden ) content.hidden = false;
                } );
            }

            // Initial state (restore + ARIA + hidden)
            const saved = localStorage.getItem( 'accordion_' + accordionId );
            const startOpen = saved === 'open';
            setOpenAria( startOpen );

            if ( startOpen ) {
                content.hidden = false; // must be visible to measure
                content.style.height = content.scrollHeight + 'px';
                requestAnimationFrame( () => {
                    content.style.height = 'auto';
                } );
            } else {
                content.style.height = collapsedHeight + 'px';
                content.hidden = true; // keyboard users won't tab into closed panel
            }

            // Mouse/touch
            toggle.addEventListener( 'click', ( event ) => {
                event.stopPropagation();
                // Ignore clicks on tooltip icon
                if ( isClickInside( event, '.tooltip-icon' ) ) return;
                togglePanel();
            } );

            // Keyboard: Space/Enter toggle; ArrowDown jumps into open panel
            toggle.addEventListener( 'keydown', ( e ) => {
                if ( e.key === ' ' || e.key === 'Enter' ) {
                    e.preventDefault();
                    togglePanel();
                } else if ( e.key === 'ArrowDown' ) {
                    if ( container.classList.contains( 'open' ) && !content.hidden ) {
                        const target = firstFocusable( content );
                        if ( target ) target.focus();
                    }
                }
            } );
        } );

        // Keep heights sane on resize
        window.addEventListener( 'resize', () => {
            document.querySelectorAll( '.toggle-content-container.open .toggle-content' ).forEach( ( content ) => {
                if ( content.hidden ) content.hidden = false;
                if ( content.style.height !== 'auto' ) {
                    content.style.height = content.scrollHeight + 'px';
                }
            } );
        } );
    } );
}
