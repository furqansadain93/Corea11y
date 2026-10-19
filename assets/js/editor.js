/**
 * CoreA11y block editor panel: checks the post's content for accessibility problems
 * every time it is saved, and explains each problem in plain language.
 *
 * Plain JavaScript (no build step) using WordPress's bundled packages.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.data || ! wp.element ) {
		return;
	}

	const { createElement: el, Fragment, useState, useEffect, useRef } = wp.element;
	const { __, _n, sprintf } = wp.i18n;
	const { useSelect } = wp.data;
	const { Button, Spinner } = wp.components;
	const editorPkg = wp.editor || {};
	const editPostPkg = wp.editPost || {};
	const DocPanel = editorPkg.PluginDocumentSettingPanel || editPostPkg.PluginDocumentSettingPanel;
	const PrePublishPanel = editorPkg.PluginPrePublishPanel || editPostPkg.PluginPrePublishPanel;
	const cfg = window.corea11yEditor || {};

	if ( ! DocPanel ) {
		return;
	}

	const CONTENT_SELECTORS = [ '.wp-block-post-content', '.entry-content', 'article', 'main', '[role="main"]' ];
	const AXE_OPTIONS = {
		runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] },
		resultTypes: [ 'violations' ],
	};
	const RANK = { critical: 0, serious: 1, moderate: 2, minor: 3 };

	// Shared state so the sidebar panel and pre-publish panel show the same result.
	const store = { result: null, listeners: new Set(), run: null };
	function setResult( result ) {
		store.result = result;
		store.listeners.forEach( ( fn ) => fn( result ) );
	}

	function withScanArg( url ) {
		const u = new URL( url, window.location.href );
		// Match the editor's cross-origin isolation (WordPress 7.1+), so the frame stays readable.
		u.searchParams.set( cfg.queryArg || 'corea11y_scan', window.crossOriginIsolated ? 'isolated' : '1' );
		return u.toString();
	}

	function scanUrl( url ) {
		return new Promise( ( resolve, reject ) => {
			const frame = document.createElement( 'iframe' );
			frame.setAttribute( 'aria-hidden', 'true' );
			frame.setAttribute( 'tabindex', '-1' );
			frame.title = __( 'Accessibility check', 'corea11y' );
			frame.style.cssText = 'position:absolute;left:-10000px;top:0;width:1280px;height:900px;border:0;';
			const timer = setTimeout( () => {
				frame.remove();
				reject( new Error( __( 'The page took too long to load.', 'corea11y' ) ) );
			}, 30000 );
			frame.addEventListener( 'load', () => {
				clearTimeout( timer );
				setTimeout( () => {
					let doc;
					try {
						doc = frame.contentDocument;
					} catch ( e ) {
						doc = null;
					}
					if ( ! doc ) {
						frame.remove();
						reject( new Error( __( 'The page could not be opened for checking.', 'corea11y' ) ) );
						return;
					}
					const script = doc.createElement( 'script' );
					script.src = cfg.axeUrl;
					script.onerror = () => {
						frame.remove();
						reject( new Error( __( 'The accessibility engine could not be loaded.', 'corea11y' ) ) );
					};
					script.onload = () => {
						let context = null;
						for ( const sel of CONTENT_SELECTORS ) {
							const found = doc.querySelector( sel );
							if ( found ) {
								context = found;
								break;
							}
						}
						frame.contentWindow.axe
							.run( context || doc.body, AXE_OPTIONS )
							.then( ( res ) => {
								frame.remove();
								resolve( { violations: res.violations, scoped: !! context } );
							} )
							.catch( ( err ) => {
								frame.remove();
								reject( err );
							} );
					};
					( doc.head || doc.documentElement ).appendChild( script );
				}, 500 );
			} );
			frame.src = withScanArg( url );
			document.body.appendChild( frame );
		} );
	}

	function useResult() {
		const [ result, set ] = useState( store.result );
		useEffect( () => {
			store.listeners.add( set );
			return () => store.listeners.delete( set );
		}, [] );
		return result;
	}

	function explain( rule, help ) {
		const map = cfg.explain || {};
		if ( map[ rule ] ) {
			return map[ rule ];
		}
		return { t: help, w: '', o: '', f: cfg.genericFix || '' };
	}

	function Issue( { v } ) {
		const ex = explain( v.id, v.help );
		const impact = ( cfg.impacts || {} )[ v.impact ] || v.impact;
		return el(
			'li',
			{ className: 'corea11y-ed-issue corea11y-ed-' + v.impact },
			el( 'strong', { className: 'corea11y-ed-title' }, ex.t ),
			el( 'span', { className: 'corea11y-ed-meta' }, impact + ' · ' + sprintf( _n( '%d element', '%d elements', v.nodes.length, 'corea11y' ), v.nodes.length ) ),
			el(
				'details',
				null,
				el( 'summary', null, __( 'Why and how to fix', 'corea11y' ) ),
				ex.w ? el( 'p', null, el( 'strong', null, __( 'Why it matters: ', 'corea11y' ) ), ex.w ) : null,
				ex.o ? el( 'p', null, el( 'strong', null, __( 'Who it affects: ', 'corea11y' ) ), ex.o ) : null,
				el( 'p', null, el( 'strong', null, __( 'How to fix: ', 'corea11y' ) ), ex.f ),
				el( 'code', { className: 'corea11y-ed-code' }, ( v.nodes[ 0 ] && v.nodes[ 0 ].html ) || '' )
			)
		);
	}

	function Results( { compact } ) {
		const result = useResult();
		if ( ! result ) {
			return el( 'p', null, __( 'Save your post to check its content for accessibility problems.', 'corea11y' ) );
		}
		if ( result.running ) {
			return el( 'p', { className: 'corea11y-ed-running' }, el( Spinner ), ' ', __( 'Checking your content…', 'corea11y' ) );
		}
		if ( result.error ) {
			return el( 'p', null, result.error );
		}
		const list = result.violations.slice().sort( ( a, b ) => RANK[ a.impact ] - RANK[ b.impact ] );
		const total = list.reduce( ( n, v ) => n + v.nodes.length, 0 );
		if ( ! total ) {
			return el( 'p', { className: 'corea11y-ed-ok' }, __( 'No problems found in this content. Nice work!', 'corea11y' ) );
		}
		return el(
			Fragment,
			null,
			el( 'p', null, sprintf( _n( '%d problem found in this content.', '%d problems found in this content.', total, 'corea11y' ), total ) ),
			compact ? null : el( 'ul', { className: 'corea11y-ed-list' }, list.map( ( v ) => el( Issue, { key: v.id, v } ) ) ),
			compact ? el( 'p', null, __( 'See the CoreA11y panel in the post settings for details. You can still publish.', 'corea11y' ) ) : null
		);
	}

	function useChecker() {
		const { isSaving, isAutosaving, link, preview, status } = useSelect( ( select ) => {
			const ed = select( 'core/editor' );
			return {
				isSaving: ed.isSavingPost(),
				isAutosaving: ed.isAutosavingPost(),
				link: ed.getPermalink(),
				preview: ed.getEditedPostPreviewLink(),
				status: ed.getEditedPostAttribute( 'status' ),
			};
		}, [] );
		const wasSaving = useRef( false );

		const run = () => {
			const url = 'publish' === status || 'private' === status ? link : preview;
			if ( ! url ) {
				return;
			}
			setResult( { running: true } );
			scanUrl( url )
				.then( ( r ) => setResult( r ) )
				.catch( ( e ) => setResult( { error: ( e && e.message ) || String( e ) } ) );
		};

		useEffect( () => {
			if ( wasSaving.current && ! isSaving && ! isAutosaving ) {
				run();
			}
			wasSaving.current = isSaving && ! isAutosaving;
		}, [ isSaving, isAutosaving ] );

		store.run = run;
		return run;
	}

	// Always mounted, so checks run after every save even when the sidebar is closed.
	function Watcher() {
		useChecker();
		return null;
	}

	function Panel() {
		const result = useResult();
		const run = () => store.run && store.run();
		return el(
			DocPanel,
			{ name: 'corea11y-check', title: __( 'Accessibility (CoreA11y)', 'corea11y' ), className: 'corea11y-ed-panel' },
			el( 'div', { role: 'status', 'aria-live': 'polite' }, el( Results, { compact: false } ) ),
			el( Button, { variant: 'secondary', onClick: run, disabled: !! ( result && result.running ) }, __( 'Check now', 'corea11y' ) )
		);
	}

	function PrePublish() {
		return PrePublishPanel
			? el( PrePublishPanel, { title: __( 'Accessibility check', 'corea11y' ), initialOpen: true }, el( Results, { compact: true } ) )
			: null;
	}

	wp.plugins.registerPlugin( 'corea11y-editor', {
		render: () => el( Fragment, null, el( Watcher ), el( Panel ), el( PrePublish ) ),
		icon: 'universal-access-alt',
	} );
}( window.wp ) );
