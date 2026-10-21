/**
 * CoreA11y fix checker.
 *
 * After quick fixes are turned on, re-checks the pages where the latest audit found
 * the problems those fixes solve (only those pages, only those rules) and records
 * whether the problems are gone: "Fixed and verified".
 */
( function () {
	'use strict';

	const { __, sprintf } = wp.i18n;
	const apiFetch = wp.apiFetch;
	const config = window.corea11yVerify || {};
	const plan = config.plan || { audit: 0, fixes: [] };

	const button = document.getElementById( 'corea11y-verify-fixes' );
	const status = document.getElementById( 'corea11y-verify-status' );
	if ( ! button || ! status || ! plan.fixes.length ) {
		return;
	}

	const SETTLE_MS = 750;
	let running = false;

	function say( text ) {
		status.textContent = text;
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
			frame.title = __( 'Page being checked', 'corea11y' );
			const timer = setTimeout( () => {
				frame.remove();
				reject( new Error( 'timeout' ) );
			}, config.timeout || 30000 );
			frame.addEventListener( 'load', () => {
				clearTimeout( timer );
				resolve( frame );
			} );
			const u = new URL( url, window.location.href );
			u.searchParams.set( config.queryArg, '1' );
			// A fresh copy, past any page cache.
			u.searchParams.set( 'corea11y_verify', String( Date.now() ) );
			frame.src = u.toString();
			document.body.appendChild( frame );
		} );
	}

	function inject( frame, src, globalName ) {
		return new Promise( ( resolve, reject ) => {
			const win = frame.contentWindow;
			if ( win[ globalName ] ) {
				resolve( win[ globalName ] );
				return;
			}
			const doc = frame.contentDocument;
			const script = doc.createElement( 'script' );
			script.src = src;
			script.onload = () => resolve( win[ globalName ] );
			script.onerror = () => reject( new Error( globalName ) );
			( doc.head || doc.documentElement ).appendChild( script );
		} );
	}

	// Count the elements that still fail each rule on one page, at one width.
	async function checkAtWidth( url, width, rules ) {
		const frame = await loadFrame( url, width );
		try {
			await wait( SETTLE_MS );
			await finishTransitions( frame );
			const found = {};
			rules.forEach( ( r ) => {
				found[ r ] = new Set();
			} );
			const axe = await inject( frame, config.axeUrl, 'axe' );
			const known = axe.getRules().map( ( r ) => r.ruleId );
			const axeRules = rules.filter( ( r ) => known.indexOf( r ) !== -1 );
			if ( axeRules.length ) {
				const res = await axe.run( { include: [ frame.contentDocument.documentElement ], exclude: [ [ '#wpadminbar' ] ] }, { runOnly: { type: 'rule', values: axeRules }, resultTypes: [ 'violations' ] } );
				res.violations.forEach( ( v ) => v.nodes.forEach( ( n ) => found[ v.id ] && found[ v.id ].add( JSON.stringify( n.target ) ) ) );
			}
			const ownRules = rules.filter( ( r ) => axeRules.indexOf( r ) === -1 );
			if ( ownRules.length && config.checksUrl ) {
				if ( config.extended && config.aceUrl ) {
					await inject( frame, config.aceUrl, 'ace' ).catch( () => null );
				}
				const checks = await inject( frame, config.checksUrl, 'corea11yChecks' ).catch( () => null );
				if ( checks ) {
					const res = await checks.run( { ibm: !! config.extended } );
					( res.violations || [] ).forEach( ( v ) => ( v.nodes || [] ).forEach( ( n ) => found[ v.id ] && found[ v.id ].add( JSON.stringify( n.target ) ) ) );
				}
			}
			return found;
		} finally {
			frame.remove();
		}
	}

	async function checkPage( url, rules ) {
		const widths = config.widths || { desktop: 1280, mobile: 390 };
		const sets = [ await checkAtWidth( url, widths.desktop, rules ) ];
		if ( config.mobile ) {
			try {
				sets.push( await checkAtWidth( url, widths.mobile, rules ) );
			} catch ( e ) {
				// Desktop results are enough on their own.
			}
		}
		const counts = {};
		rules.forEach( ( r ) => {
			const all = new Set();
			sets.forEach( ( s ) => s[ r ].forEach( ( t ) => all.add( t ) ) );
			counts[ r ] = all.size;
		} );
		return counts;
	}

	async function run() {
		running = true;
		button.disabled = true;

		// Check each page once, for every rule any of the fixes covers there.
		const pages = new Map();
		plan.fixes.forEach( ( fix ) => fix.pages.forEach( ( p ) => {
			const entry = pages.get( p.url ) || { label: p.label, rules: new Set() };
			fix.rules.forEach( ( r ) => entry.rules.add( r ) );
			pages.set( p.url, entry );
		} ) );

		const counts = new Map();
		let i = 0;
		for ( const [ url, entry ] of pages ) {
			i++;
			/* translators: 1: current page number, 2: number of pages, 3: page name. */
			say( sprintf( __( 'Checking that the fixes worked: page %1$d of %2$d (%3$s)…', 'corea11y' ), i, pages.size, entry.label ) );
			try {
				counts.set( url, await checkPage( url, Array.from( entry.rules ) ) );
			} catch ( e ) {
				counts.set( url, null );
			}
		}

		const results = [];
		plan.fixes.forEach( ( fix ) => fix.pages.forEach( ( p ) => {
			const c = counts.get( p.url );
			results.push( {
				fix: fix.id,
				url: p.url,
				after: c ? fix.rules.reduce( ( sum, r ) => sum + ( c[ r ] || 0 ), 0 ) : -1,
			} );
		} ) );

		try {
			await apiFetch( { path: '/corea11y/v1/fixes/verify', method: 'POST', data: { audit: plan.audit, results } } );
			say( __( 'Check complete. Showing the results…', 'corea11y' ) );
			running = false;
			const u = new URL( window.location.href );
			u.searchParams.delete( 'saved' );
			u.searchParams.delete( 'batch' );
			u.searchParams.set( 'verified', '1' );
			window.location.href = u.toString() + '#corea11y-fix-list';
		} catch ( e ) {
			say( __( 'The results could not be saved.', 'corea11y' ) + ' ' + ( e.message || '' ) );
			running = false;
			button.disabled = false;
		}
	}

	button.addEventListener( 'click', () => {
		if ( ! running ) {
			run();
		}
	} );

	if ( button.hasAttribute( 'data-autostart' ) ) {
		run();
	}

	window.addEventListener( 'beforeunload', ( event ) => {
		if ( running ) {
			event.preventDefault();
			event.returnValue = '';
		}
	} );
}() );
