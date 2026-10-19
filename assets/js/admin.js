/**
 * CoreA11y admin UI: "?" explanation tips.
 *
 * Tips are visible without JavaScript; this script turns them into
 * toggle buttons. Escape or a click elsewhere closes the open tip.
 */
( function () {
	'use strict';

	const tips = Array.prototype.slice.call( document.querySelectorAll( '.corea11y-tip' ) );
	if ( ! tips.length ) {
		return;
	}

	function close( tip, returnFocus ) {
		const btn = tip.querySelector( '.corea11y-tip-btn' );
		const panel = tip.querySelector( '.corea11y-tip-panel' );
		if ( btn.getAttribute( 'aria-expanded' ) !== 'true' ) {
			return;
		}
		btn.setAttribute( 'aria-expanded', 'false' );
		panel.hidden = true;
		if ( returnFocus ) {
			btn.focus();
		}
	}

	function closeAll( except ) {
		tips.forEach( ( tip ) => {
			if ( tip !== except ) {
				close( tip, false );
			}
		} );
	}

	tips.forEach( ( tip ) => {
		const btn = tip.querySelector( '.corea11y-tip-btn' );
		const panel = tip.querySelector( '.corea11y-tip-panel' );
		panel.hidden = true;
		tip.classList.add( 'is-enhanced' );

		btn.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			event.stopPropagation();
			const open = btn.getAttribute( 'aria-expanded' ) === 'true';
			closeAll( tip );
			btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			panel.hidden = open;
			if ( ! open ) {
				// Keep the panel inside the viewport.
				panel.classList.remove( 'is-left' );
				const rect = panel.getBoundingClientRect();
				if ( rect.right > window.innerWidth - 8 ) {
					panel.classList.add( 'is-left' );
				}
			}
		} );

		// A tip inside <summary> must not toggle the <details>.
		tip.addEventListener( 'click', ( event ) => event.stopPropagation() );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			const open = tips.find( ( tip ) => tip.querySelector( '.corea11y-tip-btn' ).getAttribute( 'aria-expanded' ) === 'true' );
			if ( open ) {
				close( open, true );
			}
		}
	} );

	document.addEventListener( 'click', () => closeAll( null ) );
}() );
