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

	const WCAG_TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];
	// Best-practice checks that are widely expected and rarely wrong. Reported as
	// suggestions and never counted in the score.
	const BEST_PRACTICE = [ 'empty-heading', 'heading-order', 'empty-table-header', 'image-redundant-alt', 'tabindex', 'aria-allowed-role', 'page-has-heading-one', 'label-title-only' ];

	function axeOptions( axe ) {
		const ids = axe.getRules( WCAG_TAGS ).map( ( r ) => r.ruleId );
		BEST_PRACTICE.forEach( ( id ) => {
			if ( ids.indexOf( id ) === -1 ) {
				ids.push( id );
			}
		} );
		return { runOnly: { type: 'rule', values: ids }, resultTypes: [ 'violations', 'incomplete' ] };
	}
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

	// Scanned pages load in a hidden frame, where browsers pause CSS transitions and
	// animations. Colours caught mid-transition (a button fading to white, for example)
	// would be reported wrongly, so jump every transition and animation to its end state.
	function finishTransitions( frame ) {
		try {
			const doc = frame.contentDocument;
			const style = doc.createElement( 'style' );
			style.id = 'corea11y-settle';
			style.textContent = '*,*::before,*::after{transition-duration:0s!important;transition-delay:0s!important;animation-duration:0s!important;animation-delay:0s!important;animation-iteration-count:1!important}';
			( doc.head || doc.documentElement ).appendChild( style );
			void doc.documentElement.offsetHeight; // Apply now.
			// Transitions already running are not affected by the rule above: finish them.
			if ( doc.getAnimations ) {
				doc.getAnimations().forEach( ( anim ) => {
					try {
						anim.finish();
					} catch ( err ) {
						// Endless animations can't finish; leave them.
					}
				} );
			}
		} catch ( e ) {}
		return wait( 60 );
	}

	function loadFrame( url, width ) {
		return new Promise( ( resolve, reject ) => {
			const frame = document.createElement( 'iframe' );
			frame.className = 'corea11y-scan-frame';
			frame.style.width = width + 'px';
			frame.setAttribute( 'aria-hidden', 'true' );
			frame.setAttribute( 'tabindex', '-1' );
			frame.title = __( 'Page being scanned', 'corea11y' );

			const timer = setTimeout( () => {
				frame.remove();
				reject( new Error( __( 'The page took longer than 30 seconds to load. Slow pages, or pages that keep loading (such as some chat widgets), can cause this. Try again, or leave this page out in Settings.', 'corea11y' ) ) );
			}, config.timeout || 30000 );

			frame.addEventListener( 'load', () => {
				clearTimeout( timer );
				resolve( frame );
			} );
			frame.src = withScanArg( url );
			document.body.appendChild( frame );
		} );
	}

	// Load a script into the scanned page and resolve with the global it defines.
	function injectScript( frame, src, globalName ) {
		return new Promise( ( resolve, reject ) => {
			const doc = frame.contentDocument;
			if ( frame.contentWindow[ globalName ] ) {
				resolve( frame.contentWindow[ globalName ] );
				return;
			}
			const script = doc.createElement( 'script' );
			script.src = src;
			script.onload = () => resolve( frame.contentWindow[ globalName ] );
			script.onerror = () => reject( new Error( globalName ) );
			( doc.head || doc.documentElement ).appendChild( script );
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
				reject( new Error( __( 'The page could not be opened for scanning. Usually a security setting stops pages from being shown in a frame (X-Frame-Options or a Content-Security-Policy frame-ancestors rule), or the page redirects to another address. Ask your host to allow same-origin framing.', 'corea11y' ) ) );
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

	async function scanAtWidth( url, width ) {
		const frame = await loadFrame( url, width );
		try {
			await wait( SETTLE_MS );
			await finishTransitions( frame );
			const axe = await injectAxe( frame );
			const doc = frame.contentDocument;
			const results = await axe.run(
				{ include: [ doc.documentElement ], exclude: [ [ '#wpadminbar' ] ] },
				axeOptions( axe )
			);
			const extra = await extraChecks( frame );
			return {
				violations: slim( results.violations.concat( extra.violations ) ),
				review: slim( results.incomplete.concat( extra.review ) ),
			};
		} finally {
			frame.remove();
		}
	}

	// CoreA11y's own checks, plus the curated IBM Equal Access rules when enabled.
	// Any failure here leaves the axe-core results untouched.
	async function extraChecks( frame ) {
		const empty = { violations: [], review: [] };
		if ( ! config.checksUrl ) {
			return empty;
		}
		try {
			if ( config.extended && config.aceUrl ) {
				await injectScript( frame, config.aceUrl, 'ace' ).catch( () => null );
			}
			const checks = await injectScript( frame, config.checksUrl, 'corea11yChecks' );
			return checks ? await checks.run( { ibm: !! config.extended } ) : empty;
		} catch ( e ) {
			return empty;
		}
	}

	// Combine results from several screen widths: one entry per rule, each element once.
	function merge( lists ) {
		const byId = new Map();
		lists.forEach( ( list ) => {
			list.forEach( ( v ) => {
				const hidden = Math.max( 0, v.nodeCount - v.nodes.length );
				const seen = byId.get( v.id );
				if ( ! seen ) {
					byId.set( v.id, Object.assign( {}, v, { nodes: v.nodes.slice(), hidden } ) );
					return;
				}
				const keys = new Set( seen.nodes.map( ( n ) => JSON.stringify( n.target ) ) );
				v.nodes.forEach( ( n ) => {
					if ( ! keys.has( JSON.stringify( n.target ) ) ) {
						seen.nodes.push( n );
					}
				} );
				seen.hidden = Math.max( seen.hidden, hidden );
			} );
		} );
		return Array.from( byId.values() ).map( ( v ) => {
			const out = Object.assign( {}, v, { nodeCount: v.nodes.length + v.hidden } );
			delete out.hidden;
			return out;
		} );
	}

	async function scanPage( url ) {
		const widths = config.widths || { desktop: 1280, mobile: 390 };
		const desktop = await scanAtWidth( url, widths.desktop );
		if ( ! config.mobile ) {
			return desktop;
		}
		let mobile;
		try {
			mobile = await scanAtWidth( url, widths.mobile );
		} catch ( e ) {
			// The desktop results are still valid on their own.
			return desktop;
		}
		return {
			violations: merge( [ desktop.violations, mobile.violations ] ),
			review: merge( [ desktop.review, mobile.review ] ),
		};
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
			let review = [];
			let error = '';
			try {
				const result = await scanPage( page.url );
				violations = result.violations;
				review = result.review;
			} catch ( e ) {
				error = e.message || String( e );
			}

			try {
				const saved = await apiFetch( {
					path: '/corea11y/v1/audits/' + audit.id + '/pages',
					method: 'POST',
					data: { url: page.url, violations, review, error },
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

	// "Verify with a new audit" links start the audit automatically.
	if ( new URLSearchParams( window.location.search ).get( 'corea11y_autorun' ) === '1' ) {
		run();
	}

	window.addEventListener( 'beforeunload', ( event ) => {
		if ( running ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );
}() );
