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

	// Embedded frames without a title, named from where they come from.
	const FRAMES = [ [ /(youtube\.com|youtube-nocookie\.com|youtu\.be)/, 'video', 'YouTube' ], [ /vimeo\.com/, 'video', 'Vimeo' ], [ /(google\.[a-z.]+\/maps|maps\.google)/, 'map' ], [ /spotify\.com/, 'player', 'Spotify' ], [ /soundcloud\.com/, 'player', 'SoundCloud' ], [ /(twitter\.com|platform\.x\.com)/, 'post', 'X' ], [ /instagram\.com/, 'post', 'Instagram' ], [ /facebook\.com/, 'post', 'Facebook' ] ];
	const t = cfg.frames || {};
	function textOf( el ) {
		let out = '';
		el.childNodes.forEach( function walk( n ) {
			if ( n.nodeType === 3 ) {
				out += n.nodeValue;
			} else if ( n.nodeType === 1 && n.getAttribute( 'aria-hidden' ) !== 'true' ) {
				if ( n.tagName === 'IMG' ) {
					out += n.getAttribute( 'alt' ) || '';
				} else {
					n.childNodes.forEach( walk );
				}
			}
		} );
		return out.replace( /[\s\u00a0]+/g, '' );
	}
	function fixPage( root ) {
		if ( on( 'iframe-titles' ) ) {
			root.querySelectorAll( 'iframe:not([title])' ).forEach( ( f ) => {
				if ( f.getAttribute( 'aria-label' ) || f.getAttribute( 'aria-labelledby' ) ) {
					return;
				}
				const src = f.getAttribute( 'src' ) || f.getAttribute( 'data-src' ) || '';
				const known = FRAMES.find( ( k ) => k[ 0 ].test( src ) );
				let title = t.embedded || 'Embedded content';
				if ( known ) {
					title = known[ 1 ] === 'map' ? t.map || 'Map' : ( t[ known[ 1 ] ] || '%s' ).replace( '%s', known[ 2 ] );
				}
				f.setAttribute( 'title', title );
			} );
		}
		if ( on( 'tabindex' ) ) {
			root.querySelectorAll( '[tabindex]' ).forEach( ( el ) => {
				if ( parseInt( el.getAttribute( 'tabindex' ), 10 ) > 0 ) {
					el.setAttribute( 'tabindex', '0' );
				}
			} );
		}
		if ( on( 'accesskeys' ) ) {
			root.querySelectorAll( '[accesskey]' ).forEach( ( el ) => el.removeAttribute( 'accesskey' ) );
		}
		if ( on( 'empty-headings' ) ) {
			root.querySelectorAll( 'h1, h2, h3, h4, h5, h6, [role="heading"]' ).forEach( ( h ) => {
				if ( ! textOf( h ) && ! h.getAttribute( 'aria-label' ) && ! h.querySelector( 'svg[aria-label]' ) ) {
					h.setAttribute( 'aria-hidden', 'true' );
				}
			} );
		}
		if ( on( 'empty-paragraphs' ) ) {
			root.querySelectorAll( 'p' ).forEach( ( p ) => {
				if ( ! p.children.length && ! ( p.textContent || '' ).replace( /[\s\u00a0]+/g, '' ) ) {
					p.setAttribute( 'aria-hidden', 'true' );
				}
			} );
		}
	}
	const pageFixes = [ 'iframe-titles', 'tabindex', 'accesskeys', 'empty-headings', 'empty-paragraphs' ].some( on );
	function startPage() {
		fixPage( doc );
		// Also fix content added later (pop-ups, sliders, lazy embeds), in small batches.
		if ( window.MutationObserver ) {
			let pending = [];
			let timer = null;
			new MutationObserver( ( records ) => {
				records.forEach( ( r ) => r.addedNodes.forEach( ( n ) => n.nodeType === 1 && pending.push( n ) ) );
				if ( pending.length && ! timer ) {
					timer = setTimeout( () => {
						const nodes = pending;
						pending = [];
						timer = null;
						nodes.forEach( ( n ) => n.isConnected && fixPage( n.parentElement || n ) );
					}, 200 );
				}
			} ).observe( doc.body, { childList: true, subtree: true } );
		}
	}
	if ( pageFixes ) {
		if ( doc.readyState === 'loading' ) {
			doc.addEventListener( 'DOMContentLoaded', startPage );
		} else {
			startPage();
		}
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
