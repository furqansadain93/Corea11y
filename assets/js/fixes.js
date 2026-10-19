/**
 * CoreA11y quick fixes that need the rendered page (for themes that hard-code markup).
 * Each fix only acts when the problem is actually present.
 */
( function () {
	'use strict';

	const cfg = window.corea11yFixes || {};
	const on = ( id ) => Array.isArray( cfg.fixes ) && cfg.fixes.indexOf( id ) !== -1;
	const doc = document;

	// Page language.
	if ( on( 'lang' ) && cfg.lang && ! doc.documentElement.getAttribute( 'lang' ) ) {
		doc.documentElement.setAttribute( 'lang', cfg.lang );
	}

	// Page title.
	if ( on( 'title' ) && cfg.title && ! ( doc.title || '' ).trim() ) {
		doc.title = cfg.title;
	}

	// Pinch-zoom: drop user-scalable=no and maximum-scale below 2.
	if ( on( 'zoom' ) ) {
		doc.querySelectorAll( 'meta[name="viewport"]' ).forEach( ( meta ) => {
			const parts = ( meta.getAttribute( 'content' ) || '' )
				.split( ',' )
				.map( ( p ) => p.trim() )
				.filter( ( p ) => {
					const [ key, value ] = p.split( '=' ).map( ( s ) => ( s || '' ).trim().toLowerCase() );
					if ( key === 'user-scalable' && ( value === 'no' || value === '0' ) ) {
						return false;
					}
					if ( key === 'maximum-scale' && parseFloat( value ) < 2 ) {
						return false;
					}
					return p !== '';
				} );
			meta.setAttribute( 'content', parts.join( ', ' ) );
		} );
	}

	// Search inputs with no accessible name (theme templates that bypass WordPress filters).
	if ( on( 'form-labels' ) ) {
		doc.querySelectorAll( 'input[type="search"], input[name="s"]' ).forEach( ( input ) => {
			const hasLabel = ( input.labels && input.labels.length ) || input.getAttribute( 'aria-label' ) || input.getAttribute( 'aria-labelledby' ) || input.getAttribute( 'title' );
			if ( ! hasLabel ) {
				input.setAttribute( 'aria-label', cfg.searchLabel || 'Search' );
			}
		} );
	}

	// Skip link, unless the theme (or WordPress for block themes) already provides one.
	// Runs on load so WordPress's own skip-link script has had its turn.
	function addSkipLink() {
		const focusable = Array.prototype.slice.call(
			doc.querySelectorAll( 'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])' ),
			0,
			5
		);
		const existing = focusable.some( ( el ) => el.tagName === 'A' && ( el.getAttribute( 'href' ) || '' ).charAt( 0 ) === '#' && /skip/i.test( el.textContent || '' ) );
		const main = doc.querySelector( 'main, [role="main"], #main, #content, #primary, .site-content, .wp-site-blocks' );
		if ( ! existing && main ) {
			if ( ! main.id ) {
				main.id = 'corea11y-main';
			}
			if ( ! main.hasAttribute( 'tabindex' ) ) {
				main.setAttribute( 'tabindex', '-1' );
			}
			if ( main.tagName !== 'MAIN' && ! main.getAttribute( 'role' ) && ! doc.querySelector( 'main, [role="main"]' ) ) {
				main.setAttribute( 'role', 'main' );
			}
			const link = doc.createElement( 'a' );
			link.className = 'corea11y-skip-link';
			link.href = '#' + main.id;
			link.textContent = cfg.skipText || 'Skip to content';
			doc.body.insertBefore( link, doc.body.firstChild );
		}
	}
	if ( on( 'skip-link' ) ) {
		if ( doc.readyState === 'complete' ) {
			addSkipLink();
		} else {
			window.addEventListener( 'load', addSkipLink );
		}
	}
}() );
