/**
 * CoreA11y scanner.
 *
 * Loads each page of the site in a hidden, same-origin frame, runs axe-core
 * inside it, and sends the violations to the CoreA11y REST API.
 */
( function () {
	'use strict';

	const { __, sprintf } = wp.i18n;
	const apiFetch = wp.apiFetch;
	const config = window.corea11yScanner || {};

	const AXE_OPTIONS = {
		runOnly: {
			type: 'tag',
			values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ],
		},
		resultTypes: [ 'violations' ],
	};
	const MAX_NODES = 25;
	const SETTLE_MS = 750;

	const button = document.getElementById( 'corea11y-run-audit' );
	const panel = document.getElementById( 'corea11y-progress' );
	if ( ! button || ! panel ) {
		return;
	}
	const statusText = document.getElementById( 'corea11y-progress-text' );
	const bar = document.getElementById( 'corea11y-progress-bar' );
	const log = document.getElementById( 'corea11y-progress-log' );
	const stopButton = document.getElementById( 'corea11y-stop-audit' );

	let running = false;
	let stopRequested = false;

	function announce( text ) {
		statusText.textContent = text;
	}

	function logLine( text, isError ) {
		const li = document.createElement( 'li' );
		li.textContent = text;
		if ( isError ) {
			li.className = 'corea11y-log-error';
		}
		log.appendChild( li );
	}

	function withScanArg( url ) {
		const u = new URL( url, window.location.href );
		u.searchParams.set( config.queryArg, '1' );
		return u.toString();
	}

	function wait( ms ) {
		return new Promise( ( resolve ) => setTimeout( resolve, ms ) );
	}

	function loadFrame( url ) {
		return new Promise( ( resolve, reject ) => {
			const frame = document.createElement( 'iframe' );
			frame.className = 'corea11y-scan-frame';
			frame.setAttribute( 'aria-hidden', 'true' );
			frame.setAttribute( 'tabindex', '-1' );
			frame.title = __( 'Page being scanned', 'corea11y' );

			const timer = setTimeout( () => {
				frame.remove();
				reject( new Error( __( 'The page took too long to load.', 'corea11y' ) ) );
			}, config.timeout || 30000 );

			frame.addEventListener( 'load', () => {
				clearTimeout( timer );
				resolve( frame );
			} );
			frame.src = withScanArg( url );
			document.body.appendChild( frame );
		} );
	}

	function injectAxe( frame ) {
		return new Promise( ( resolve, reject ) => {
			let doc;
			try {
				doc = frame.contentDocument;
			} catch ( e ) {
				doc = null;
			}
			if ( ! doc || ! doc.documentElement ) {
				reject( new Error( __( 'The page could not be opened for scanning. It may redirect to another site or block framing.', 'corea11y' ) ) );
				return;
			}
			if ( frame.contentWindow.axe ) {
				resolve( frame.contentWindow.axe );
				return;
			}
			const script = doc.createElement( 'script' );
			script.src = config.axeUrl;
			script.onload = () => resolve( frame.contentWindow.axe );
			script.onerror = () => reject( new Error( __( 'The accessibility engine could not be loaded into the page.', 'corea11y' ) ) );
			( doc.head || doc.documentElement ).appendChild( script );
		} );
	}

	function slim( violations ) {
		return violations.map( ( v ) => ( {
			id: v.id,
			impact: v.impact,
			tags: v.tags,
			help: v.help,
			nodeCount: v.nodes.length,
			nodes: v.nodes.slice( 0, MAX_NODES ).map( ( n ) => ( {
				target: n.target,
				html: ( n.html || '' ).slice( 0, 1000 ),
				impact: n.impact,
				failureSummary: n.failureSummary,
			} ) ),
		} ) );
	}

	async function scanPage( url ) {
		const frame = await loadFrame( url );
		try {
			await wait( SETTLE_MS );
			const axe = await injectAxe( frame );
			const doc = frame.contentDocument;
			const results = await axe.run(
				{ include: [ doc.documentElement ], exclude: [ [ '#wpadminbar' ] ] },
				AXE_OPTIONS
			);
			return slim( results.violations );
		} finally {
			frame.remove();
		}
	}

	async function run() {
		running = true;
		stopRequested = false;
		button.disabled = true;
		panel.hidden = false;
		stopButton.hidden = false;
		stopButton.disabled = false;
		log.textContent = '';
		announce( __( 'Starting audit…', 'corea11y' ) );

		let audit;
		try {
			audit = await apiFetch( { path: '/corea11y/v1/audits', method: 'POST' } );
		} catch ( e ) {
			announce( __( 'The audit could not be started.', 'corea11y' ) + ' ' + ( e.message || '' ) );
			finish();
			return;
		}

		const pages = audit.pages || [];
		bar.max = pages.length;
		bar.value = 0;

		for ( let i = 0; i < pages.length; i++ ) {
			if ( stopRequested ) {
				break;
			}
			const page = pages[ i ];
			/* translators: 1: current page number, 2: total pages, 3: page name. */
			announce( sprintf( __( 'Scanning page %1$d of %2$d: %3$s', 'corea11y' ), i + 1, pages.length, page.label ) );

			let violations = [];
			let error = '';
			try {
				violations = await scanPage( page.url );
			} catch ( e ) {
				error = e.message || String( e );
			}

			try {
				const saved = await apiFetch( {
					path: '/corea11y/v1/audits/' + audit.id + '/pages',
					method: 'POST',
					data: { url: page.url, violations, error },
				} );
				if ( error ) {
					/* translators: 1: page name, 2: error message. */
					logLine( sprintf( __( '%1$s: not scanned (%2$s)', 'corea11y' ), page.label, error ), true );
				} else {
					/* translators: 1: page name, 2: number of issues. */
					logLine( sprintf( __( '%1$s: %2$d issues', 'corea11y' ), page.label, saved.stored ) );
				}
			} catch ( e ) {
				/* translators: 1: page name, 2: error message. */
				logLine( sprintf( __( '%1$s: results could not be saved (%2$s)', 'corea11y' ), page.label, e.message || '' ), true );
			}
			bar.value = i + 1;
		}

		announce( __( 'Running site-wide checks and building the report…', 'corea11y' ) );
		try {
			const done = await apiFetch( {
				path: '/corea11y/v1/audits/' + audit.id + '/complete',
				method: 'POST',
			} );
			/* translators: %d: number of issues found. */
			announce( sprintf( __( 'Audit complete: %d issues found. Opening the report…', 'corea11y' ), done.issues ) );
			running = false;
			window.location.href = config.reportUrl + encodeURIComponent( done.id );
		} catch ( e ) {
			announce( __( 'The audit could not be finished.', 'corea11y' ) + ' ' + ( e.message || '' ) );
			finish();
		}
	}

	function finish() {
		running = false;
		button.disabled = false;
		stopButton.hidden = true;
	}

	button.addEventListener( 'click', () => {
		if ( ! running ) {
			run();
		}
	} );

	stopButton.addEventListener( 'click', () => {
		stopRequested = true;
		stopButton.disabled = true;
		announce( __( 'Stopping after the current page. Results so far will be saved.', 'corea11y' ) );
	} );

	window.addEventListener( 'beforeunload', ( event ) => {
		if ( running ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );
}() );
