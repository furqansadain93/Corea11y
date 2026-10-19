/**
 * CoreA11y front-end highlighter: outlines each issue on the live page and explains it.
 * Shown only to administrators who open it from a CoreA11y report (nonce-protected).
 *
 * The panel lives in a shadow root so the site's CSS can't break it.
 */
( function () {
	'use strict';

	const cfg = window.corea11yHighlight || {};
	const issues = Array.isArray( cfg.issues ) ? cfg.issues : [];
	const t = cfg.i18n || {};
	if ( ! issues.length ) {
		return;
	}

	const COLORS = { critical: '#b32d2e', serious: '#b45309', moderate: '#8a6d00', minor: '#50575e' };
	let index = Math.max( 0, issues.findIndex( ( i ) => String( i.id ) === String( cfg.current ) ) );
	const boxes = [];

	function find( selector ) {
		if ( ! selector || selector.indexOf( '>>>' ) !== -1 ) {
			return null;
		}
		try {
			return document.querySelector( selector );
		} catch ( e ) {
			return null;
		}
	}

	// Outline boxes (decorative, ignored by assistive technology).
	const layer = document.createElement( 'div' );
	layer.setAttribute( 'aria-hidden', 'true' );
	layer.style.cssText = 'position:absolute;left:0;top:0;width:0;height:0;z-index:2147483646;pointer-events:none;';
	document.body.appendChild( layer );

	issues.forEach( ( issue, i ) => {
		const box = document.createElement( 'div' );
		const tag = document.createElement( 'span' );
		tag.textContent = String( i + 1 );
		box.appendChild( tag );
		box.style.cssText = 'position:absolute;border:3px solid ' + ( COLORS[ issue.impact ] || '#3546d1' ) + ';border-radius:4px;box-shadow:0 0 0 3px #fff;display:none;';
		tag.style.cssText = 'position:absolute;top:-14px;left:-14px;min-width:24px;height:24px;padding:0 4px;border-radius:12px;background:' + ( COLORS[ issue.impact ] || '#3546d1' ) + ';color:#fff;font:700 13px/24px sans-serif;text-align:center;box-shadow:0 0 0 2px #fff;';
		layer.appendChild( box );
		boxes.push( box );
	} );

	function placeBoxes() {
		issues.forEach( ( issue, i ) => {
			const elm = find( issue.selector );
			const box = boxes[ i ];
			if ( ! elm ) {
				box.style.display = 'none';
				return;
			}
			const r = elm.getBoundingClientRect();
			box.style.display = 'block';
			box.style.left = r.left + window.scrollX - 4 + 'px';
			box.style.top = r.top + window.scrollY - 4 + 'px';
			box.style.width = Math.max( r.width, 8 ) + 8 + 'px';
			box.style.height = Math.max( r.height, 8 ) + 8 + 'px';
			box.style.opacity = i === index ? '1' : '0.45';
			box.style.zIndex = i === index ? '2' : '1';
		} );
	}

	// Panel.
	const host = document.createElement( 'div' );
	host.id = 'corea11y-highlighter';
	document.body.appendChild( host );
	const root = host.attachShadow( { mode: 'open' } );
	root.innerHTML =
		'<style>' +
		':host{all:initial}' +
		'.p{position:fixed;right:16px;bottom:16px;z-index:2147483647;width:min(380px,calc(100vw - 32px));max-height:min(70vh,560px);overflow:auto;background:#fff;color:#1d2327;border:1px solid #c3c4c7;border-radius:12px;box-shadow:0 12px 32px rgba(16,24,40,.25);font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;padding:16px 18px}' +
		'h2{font-size:16px;margin:0 0 4px}h3{font-size:15px;margin:10px 0 6px}p{margin:0 0 8px}' +
		'.meta{color:#50575e;font-size:13px}.badge{display:inline-block;padding:1px 8px;border-radius:999px;font-size:12px;color:#fff}' +
		'.row{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-top:10px;flex-wrap:wrap}' +
		'button,a.btn{font:inherit;font-size:13px;padding:6px 12px;border-radius:6px;border:1px solid #3546d1;background:#fff;color:#3546d1;cursor:pointer;text-decoration:none}' +
		'button.primary{background:#3546d1;color:#fff}button:focus-visible,a:focus-visible{outline:2px solid #3546d1;outline-offset:2px}' +
		'.close{position:absolute;top:10px;right:10px;border:0;font-size:20px;line-height:1;padding:4px 8px}' +
		'code{display:block;white-space:pre-wrap;word-break:break-all;background:#f6f7f7;padding:6px 8px;border-radius:6px;font-size:12px}' +
		'.missing{background:#fdf3e7;padding:8px 10px;border-radius:6px}' +
		'</style>' +
		'<section class="p" role="region" aria-labelledby="h" tabindex="-1">' +
		'<button class="close" type="button" aria-label="' + ( t.close || 'Close' ) + '">&times;</button>' +
		'<h2 id="h"></h2><p class="meta" id="count"></p><div id="body" aria-live="polite"></div>' +
		'<div class="row"><span><button type="button" id="prev">' + ( t.prev || 'Previous' ) + '</button> <button type="button" class="primary" id="next">' + ( t.next || 'Next' ) + '</button></span>' +
		'<a class="btn" id="back"></a></div></section>';

	const panel = root.querySelector( '.p' );
	const body = root.getElementById( 'body' );
	root.getElementById( 'h' ).textContent = t.title || 'Accessibility issues on this page';
	const back = root.getElementById( 'back' );
	back.textContent = t.back || 'Back to report';
	back.href = cfg.reportUrl || '#';

	function esc( s ) {
		const d = document.createElement( 'div' );
		d.textContent = s == null ? '' : String( s );
		return d.innerHTML;
	}

	function show( i ) {
		index = ( i + issues.length ) % issues.length;
		const issue = issues[ index ];
		const elm = find( issue.selector );
		root.getElementById( 'count' ).textContent = ( t.count || 'Issue %1$d of %2$d' ).replace( '%1$d', index + 1 ).replace( '%2$d', issues.length );
		body.innerHTML =
			'<h3>' + esc( issue.title ) + '</h3>' +
			'<p><span class="badge" style="background:' + ( COLORS[ issue.impact ] || '#3546d1' ) + '">' + esc( issue.impactLabel ) + '</span>' +
			( issue.wcag ? ' <span class="meta">WCAG ' + esc( issue.wcag ) + '</span>' : '' ) + '</p>' +
			( elm ? '' : '<p class="missing">' + esc( t.missing || 'This element is no longer on the page. It may already be fixed.' ) + '</p>' ) +
			'<p><strong>' + esc( t.why || 'Why it matters:' ) + '</strong> ' + esc( issue.why ) + '</p>' +
			'<p><strong>' + esc( t.fix || 'How to fix:' ) + '</strong> ' + esc( issue.fix ) + '</p>' +
			( issue.snippet ? '<code>' + esc( issue.snippet ) + '</code>' : '' );
		placeBoxes();
		if ( elm ) {
			const reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			elm.scrollIntoView( { block: 'center', behavior: reduce ? 'auto' : 'smooth' } );
			setTimeout( placeBoxes, 400 );
		}
	}

	root.getElementById( 'prev' ).addEventListener( 'click', () => show( index - 1 ) );
	root.getElementById( 'next' ).addEventListener( 'click', () => show( index + 1 ) );
	function close() {
		host.remove();
		layer.remove();
		window.removeEventListener( 'resize', placeBoxes );
		window.removeEventListener( 'scroll', placeBoxes );
	}
	root.querySelector( '.close' ).addEventListener( 'click', close );
	panel.addEventListener( 'keydown', ( e ) => {
		if ( e.key === 'Escape' ) {
			close();
		}
	} );
	window.addEventListener( 'resize', placeBoxes );
	window.addEventListener( 'scroll', placeBoxes, { passive: true } );

	function start() {
		show( index );
		panel.focus();
	}
	if ( document.readyState === 'complete' ) {
		start();
	} else {
		window.addEventListener( 'load', start );
	}
}() );
