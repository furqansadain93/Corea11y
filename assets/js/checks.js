/**
 * CoreA11y extra checks.
 *
 * Runs inside the page being scanned, after axe-core. Adds rule-based checks for
 * problems axe-core doesn't cover, and (when enabled) a curated set of rules from
 * the IBM Equal Access engine. Results use the same shape as axe-core results:
 *   violations: confident problems (WCAG failures, or best-practice suggestions)
 *   review:     things a person needs to look at ("Needs a human check")
 *
 * No AI, no network requests: everything is decided by fixed rules.
 */
( function () {
	'use strict';

	const MAX_NODES = 25;

	// IBM Equal Access rules we use, and how CoreA11y reports them. Only rules that
	// axe-core doesn't cover and that proved reliable in testing are listed.
	const IBM_RULES = {
		fieldset_legend_valid: { kind: 'violations', impact: 'serious', tags: [ 'wcag2a', 'wcag131' ], help: 'Groups of form fields need a clear legend' },
		input_checkboxes_grouped: { kind: 'violations', impact: 'moderate', tags: [ 'wcag2a', 'wcag131' ], help: 'Related radio buttons and checkboxes should be grouped' },
		img_alt_null: { kind: 'violations', impact: 'moderate', tags: [ 'wcag2a', 'wcag111' ], help: 'Images marked decorative should not have a title' },
		element_tabbable_role_valid: { kind: 'violations', impact: 'serious', tags: [ 'wcag2a', 'wcag412' ], help: 'Elements reachable by keyboard must be interactive' },
		table_headers_related: { kind: 'violations', impact: 'serious', tags: [ 'wcag2a', 'wcag131' ], help: 'Table cells must be linked to their headers' },
		element_attribute_deprecated: { kind: 'violations', impact: 'minor', tags: [ 'best-practice' ], help: 'Outdated HTML elements or attributes are used' },
		img_alt_misuse: { kind: 'review', tags: [ 'wcag2a', 'wcag111' ], help: 'Alt text may not describe the image' },
		text_block_heading: { kind: 'review', tags: [ 'wcag2a', 'wcag131' ], help: 'Text may look like a heading without being one' },
		list_markup_review: { kind: 'review', tags: [ 'wcag2a', 'wcag131' ], help: 'Content may be a list without list markup' },
		media_audio_transcribed: { kind: 'review', tags: [ 'wcag2a', 'wcag121' ], help: 'Audio may need a transcript' },
		media_track_available: { kind: 'review', tags: [ 'wcag2a', 'wcag122' ], help: 'Video may need captions' },
	};

	const GENERIC_LINK_TEXT = [ 'click here', 'click', 'here', 'read more', 'more', 'learn more', 'find out more', 'more info', 'more information', 'details', 'continue', 'continue reading', 'this link', 'link', 'go', 'this page' ];
	const FILE_TYPES = { pdf: /\bpdf\b/i, doc: /\b(doc|word)\b/i, docx: /\b(docx?|word)\b/i, xls: /\b(xls|excel|spreadsheet)\b/i, xlsx: /\b(xlsx?|excel|spreadsheet)\b/i, ppt: /\b(ppt|powerpoint|slides?)\b/i, pptx: /\b(pptx?|powerpoint|slides?)\b/i, zip: /\bzip\b/i };
	const MEDIA_TYPES = /\.(mp3|wav|m4a|ogg|mp4|mov|webm|avi)(\?|#|$)/i;
	const NEW_WINDOW_HINT = /(new (window|tab)|opens|external)/i;

	function cssEscape( value ) {
		return window.CSS && CSS.escape ? CSS.escape( value ) : String( value ).replace( /[^a-zA-Z0-9_-]/g, '\\$&' );
	}

	// A CSS selector that points to this element, so "Show on page" can find it.
	function selectorFor( el ) {
		const doc = el.ownerDocument;
		const parts = [];
		let node = el;
		while ( node && node.nodeType === 1 && node !== doc.documentElement ) {
			if ( node.id && doc.querySelectorAll( '#' + cssEscape( node.id ) ).length === 1 ) {
				parts.unshift( '#' + cssEscape( node.id ) );
				return parts.join( ' > ' );
			}
			let part = node.tagName.toLowerCase();
			const parent = node.parentElement;
			if ( parent ) {
				const same = Array.prototype.filter.call( parent.children, ( c ) => c.tagName === node.tagName );
				if ( same.length > 1 ) {
					part += ':nth-of-type(' + ( same.indexOf( node ) + 1 ) + ')';
				}
			}
			parts.unshift( part );
			node = parent;
		}
		parts.unshift( 'html' );
		return parts.join( ' > ' );
	}

	function openingTag( el ) {
		const html = el.outerHTML || '';
		const end = html.indexOf( '>' );
		const tag = end > -1 ? html.slice( 0, end + 1 ) : html;
		const text = ( el.textContent || '' ).replace( /\s+/g, ' ' ).trim().slice( 0, 80 );
		return ( tag + ( text ? text + '…' : '' ) ).slice( 0, 300 );
	}

	function isVisible( el ) {
		if ( ! el.getClientRects().length ) {
			return false;
		}
		const style = window.getComputedStyle( el );
		return style.visibility !== 'hidden' && style.display !== 'none';
	}

	function ownText( el ) {
		let text = '';
		el.childNodes.forEach( ( n ) => {
			if ( n.nodeType === 3 ) {
				text += n.nodeValue;
			}
		} );
		return text.replace( /\s+/g, ' ' ).trim();
	}

	function linkText( a ) {
		const label = a.getAttribute( 'aria-label' );
		if ( label && label.trim() ) {
			return label.trim();
		}
		const labelledby = a.getAttribute( 'aria-labelledby' );
		if ( labelledby ) {
			const t = labelledby.split( /\s+/ ).map( ( id ) => {
				const ref = a.ownerDocument.getElementById( id );
				return ref ? ref.textContent : '';
			} ).join( ' ' ).trim();
			if ( t ) {
				return t;
			}
		}
		let text = '';
		a.querySelectorAll( 'img[alt]' ).forEach( ( img ) => {
			text += ' ' + img.getAttribute( 'alt' );
		} );
		return ( ( a.textContent || '' ) + text ).replace( /\s+/g, ' ' ).trim();
	}

	function hasLabel( field ) {
		const doc = field.ownerDocument;
		if ( ( field.getAttribute( 'aria-label' ) || '' ).trim() || field.getAttribute( 'aria-labelledby' ) || ( field.getAttribute( 'title' ) || '' ).trim() ) {
			return true;
		}
		if ( field.closest( 'label' ) ) {
			return true;
		}
		return !! ( field.id && doc.querySelector( 'label[for="' + cssEscape( field.id ) + '"]' ) );
	}

	function createCollector() {
		const out = { violations: {}, review: {} };
		return {
			add( kind, id, impact, tags, help, el, why ) {
				const bucket = out[ kind ];
				if ( ! bucket[ id ] ) {
					bucket[ id ] = { id, impact, tags, help, nodes: [], nodeCount: 0 };
				}
				const v = bucket[ id ];
				const target = selectorFor( el );
				if ( v.nodes.some( ( n ) => n.target[ 0 ] === target ) ) {
					return;
				}
				v.nodeCount++;
				if ( v.nodes.length < MAX_NODES ) {
					v.nodes.push( { target: [ target ], html: openingTag( el ), impact, failureSummary: why } );
				}
			},
			result() {
				return { violations: Object.values( out.violations ), review: Object.values( out.review ) };
			},
		};
	}

	// CoreA11y's own rule-based checks.
	function ownChecks( doc, c ) {
		const links = Array.prototype.filter.call( doc.querySelectorAll( 'a[href]' ), isVisible );
		const byText = new Map();

		links.forEach( ( a, i ) => {
			const text = linkText( a );
			const lower = text.toLowerCase().replace( /[.…:»›→>\s]+$/, '' ).trim();
			const href = a.getAttribute( 'href' ) || '';
			let url = null;
			try {
				url = new URL( href, doc.baseURI );
			} catch ( e ) {}

			if ( text && /^[\s\p{P}\p{S}]+$/u.test( text ) ) {
				c.add( 'violations', 'corea11y-link-symbol-only', 'serious', [ 'wcag2a', 'wcag244' ], 'Links need words, not just symbols', a, 'The link text is only "' + text + '", which tells screen reader users nothing about where it goes.' );
			} else if ( lower && GENERIC_LINK_TEXT.indexOf( lower ) !== -1 ) {
				c.add( 'violations', 'corea11y-link-generic', 'minor', [ 'best-practice' ], 'Link text should say where the link goes', a, 'The link text "' + text + '" only makes sense with the text around it.' );
			}

			if ( href === '#' || /^javascript:/i.test( href ) ) {
				if ( a.getAttribute( 'role' ) !== 'button' ) {
					c.add( 'review', 'corea11y-link-no-destination', 'moderate', [ 'wcag2a', 'wcag412' ], 'A link that doesn\'t go anywhere may need to be a button', a, 'This link has no real destination (' + href + '). If it opens or controls something on the page, it should be a button.' );
				}
			} else if ( url && /\.(pdf|docx?|xlsx?|pptx?|zip)$/i.test( url.pathname ) ) {
				const ext = url.pathname.split( '.' ).pop().toLowerCase();
				const hint = text + ' ' + ( a.getAttribute( 'title' ) || '' );
				if ( FILE_TYPES[ ext ] && ! FILE_TYPES[ ext ].test( hint ) ) {
					c.add( 'violations', 'corea11y-link-file-type', 'minor', [ 'best-practice' ], 'Links to files should say the file type', a, 'This link opens a ' + ext.toUpperCase() + ' file, but the link text doesn\'t say so.' );
				}
			} else if ( url && MEDIA_TYPES.test( url.pathname ) ) {
				c.add( 'review', 'corea11y-media-link', 'moderate', [ 'wcag2a', 'wcag121' ], 'Audio or video files may need a transcript', a, 'This links to an audio or video file. Make sure a transcript or captions are available.' );
			}

			if ( ( a.getAttribute( 'target' ) || '' ).toLowerCase() === '_blank' ) {
				const hint = text + ' ' + ( a.getAttribute( 'title' ) || '' ) + ' ' + Array.prototype.map.call( a.querySelectorAll( '[aria-label], .screen-reader-text, .sr-only, .visually-hidden' ), ( n ) => n.getAttribute( 'aria-label' ) || n.textContent ).join( ' ' );
				if ( ! NEW_WINDOW_HINT.test( hint ) ) {
					c.add( 'violations', 'corea11y-link-new-window', 'minor', [ 'best-practice' ], 'Say when a link opens a new tab', a, 'This link opens in a new tab or window without warning, which can disorient screen reader and keyboard users.' );
				}
			}

			const title = ( a.getAttribute( 'title' ) || '' ).trim();
			if ( title && title.toLowerCase() === text.toLowerCase() ) {
				c.add( 'violations', 'corea11y-link-title-repeat', 'minor', [ 'best-practice' ], 'Link title repeats the link text', a, 'The title attribute repeats the link text, so some screen readers read it twice.' );
			}

			const next = links[ i + 1 ];
			// Classic pattern: an image link and a text link side by side, to the same page.
			if ( next && url && next.getAttribute( 'href' ) === href && href !== '#' && ( a.querySelector( 'img' ) || next.querySelector( 'img' ) ) && ( a.nextElementSibling === next || a.parentElement.nextElementSibling === next.parentElement ) ) {
				c.add( 'violations', 'corea11y-link-adjacent-duplicate', 'minor', [ 'best-practice' ], 'Two links next to each other go to the same place', a, 'An image link and a text link (or two links) side by side lead to the same page. Combine them into one link.' );
			}

			// Dates and numbers (post dates, pagination) are expected to repeat.
			const looksLikeDate = /\d/.test( lower ) && ! isNaN( Date.parse( text ) );
			if ( lower && url && GENERIC_LINK_TEXT.indexOf( lower ) === -1 && ! looksLikeDate && ! /^[\d\s.,/-]+$/.test( lower ) ) {
				const key = lower;
				const dest = url.origin + url.pathname.replace( /\/$/, '' );
				if ( ! byText.has( key ) ) {
					byText.set( key, { dest, el: a, flagged: false } );
				} else {
					const seen = byText.get( key );
					if ( seen.dest !== dest && ! seen.flagged ) {
						seen.flagged = true;
						c.add( 'review', 'corea11y-link-same-text', 'moderate', [ 'wcag2a', 'wcag244' ], 'Links with the same text go to different pages', a, 'More than one link says "' + text + '" but they lead to different pages. Make each link text unique.' );
					}
				}
			}
		} );

		// Placeholder text used instead of a label.
		doc.querySelectorAll( 'input[placeholder], textarea[placeholder]' ).forEach( ( field ) => {
			const type = ( field.getAttribute( 'type' ) || 'text' ).toLowerCase();
			if ( [ 'hidden', 'submit', 'button', 'reset', 'image', 'checkbox', 'radio' ].indexOf( type ) !== -1 || ! isVisible( field ) ) {
				return;
			}
			if ( hasLabel( field ) && field.labels && field.labels.length && ! ( field.getAttribute( 'aria-label' ) || '' ).trim() && ! field.getAttribute( 'aria-labelledby' ) && ! ( field.getAttribute( 'title' ) || '' ).trim() ) {
				// A label exists, but the theme hides it completely (display: none), so some
				// screen readers and checkers treat the field as unlabelled.
				const shown = Array.prototype.some.call( field.labels, ( l ) => {
					const st = field.ownerDocument.defaultView.getComputedStyle( l );
					return st.display !== 'none' && st.visibility !== 'hidden';
				} );
				if ( ! shown ) {
					c.add( 'violations', 'corea11y-hidden-label', 'serious', [ 'wcag2a', 'wcag131', 'wcag412' ], 'Form labels are hidden from everyone', field, 'This field has a label, but the theme hides it with display: none, so screen readers may announce the field without a name.' );
					return;
				}
			}
			if ( ! hasLabel( field ) ) {
				c.add( 'violations', 'corea11y-placeholder-label', 'moderate', [ 'wcag2a', 'wcag332' ], 'Fields need a label, not just placeholder text', field, 'This field relies on placeholder text, which disappears as soon as someone starts typing.' );
			}
		} );

		// Data tables without header cells.
		doc.querySelectorAll( 'table' ).forEach( ( table ) => {
			const role = ( table.getAttribute( 'role' ) || '' ).toLowerCase();
			if ( role === 'presentation' || role === 'none' || ! isVisible( table ) || table.querySelector( 'table' ) ) {
				return;
			}
			const rows = table.rows ? table.rows.length : 0;
			const cols = rows ? table.rows[ 0 ].cells.length : 0;
			if ( rows >= 2 && cols >= 2 && ! table.querySelector( 'th, [role="columnheader"], [role="rowheader"]' ) ) {
				c.add( 'review', 'corea11y-table-no-headers', 'moderate', [ 'wcag2a', 'wcag131' ], 'A table has no header cells', table, 'If this table holds data, mark its column or row headings as header cells (th). If it is only used for layout, add role="presentation".' );
			}
		} );

		// Text styling that makes reading harder.
		doc.querySelectorAll( 'body *' ).forEach( ( el ) => {
			if ( [ 'SCRIPT', 'STYLE', 'NOSCRIPT', 'SVG', 'svg', 'OPTION' ].indexOf( el.tagName ) !== -1 ) {
				return;
			}
			const text = ownText( el );
			if ( text.length < 3 || ! isVisible( el ) ) {
				return;
			}
			const style = window.getComputedStyle( el );
			const size = parseFloat( style.fontSize );
			if ( size && size < 12 && /[a-z]{3}/i.test( text ) ) {
				c.add( 'violations', 'corea11y-small-text', 'minor', [ 'best-practice' ], 'Very small text', el, 'This text is ' + Math.round( size * 10 ) / 10 + 'px. Text smaller than 12px is hard for many people to read.' );
			}
			if ( style.textAlign === 'justify' && text.length > 200 ) {
				c.add( 'violations', 'corea11y-justified-text', 'minor', [ 'best-practice' ], 'Long text is justified', el, 'Justified text creates uneven gaps between words, which makes reading harder, especially for people with dyslexia.' );
			}
			const letters = text.replace( /[^A-Za-z]/g, '' );
			if ( letters.length >= 30 && ( style.textTransform === 'uppercase' || letters === letters.toUpperCase() ) ) {
				c.add( 'violations', 'corea11y-all-caps', 'minor', [ 'best-practice' ], 'Long text in capital letters', el, 'Long passages in capitals are harder to read, and some screen readers spell them out letter by letter.' );
			}
		} );

		// Empty paragraphs used for spacing.
		doc.querySelectorAll( 'p' ).forEach( ( p ) => {
			if ( ! p.children.length && ! ( p.textContent || '' ).replace( /[\s ]+/g, '' ) && ! p.closest( '[aria-hidden="true"]' ) ) {
				c.add( 'violations', 'corea11y-empty-paragraph', 'minor', [ 'best-practice' ], 'Empty paragraphs used for spacing', p, 'Screen readers may announce empty paragraphs as "blank". Use spacing settings instead.' );
			}
		} );

		// Keyboard shortcuts that can clash with assistive technology.
		doc.querySelectorAll( '[accesskey]' ).forEach( ( el ) => {
			c.add( 'violations', 'corea11y-accesskey', 'minor', [ 'best-practice' ], 'Access keys are used', el, 'Access keys can clash with screen reader and browser shortcuts. Remove the accesskey attribute.' );
		} );

		// Possibly animated images.
		doc.querySelectorAll( 'img' ).forEach( ( img ) => {
			const src = img.currentSrc || img.getAttribute( 'src' ) || '';
			if ( /\.gif(\?|#|$)/i.test( src ) && isVisible( img ) && img.width >= 50 && img.height >= 50 ) {
				c.add( 'review', 'corea11y-animated-gif', 'moderate', [ 'wcag2a', 'wcag222' ], 'An image may be animated', img, 'If this GIF moves for more than five seconds, visitors need a way to pause it.' );
			}
		} );

		// Same-page links whose target doesn't exist (broken skip links especially).
		links.forEach( ( a ) => {
			const href = a.getAttribute( 'href' ) || '';
			if ( href.length < 2 || href.charAt( 0 ) !== '#' || /^#[!/]/.test( href ) || /^#top$/i.test( href ) ) {
				return;
			}
			let id = href.slice( 1 );
			try {
				id = decodeURIComponent( id );
			} catch ( e ) {}
			if ( doc.getElementById( id ) || doc.getElementsByName( id ).length ) {
				return;
			}
			if ( /skip/i.test( linkText( a ) ) ) {
				c.add( 'violations', 'corea11y-skip-link-broken', 'serious', [ 'wcag2a', 'wcag241' ], 'The "Skip to content" link goes nowhere', a, 'This skip link points to "' + href + '", but nothing on the page has that id, so keyboard users can\'t skip the menu.' );
			} else {
				c.add( 'violations', 'corea11y-link-anchor-missing', 'minor', [ 'best-practice' ], 'A link to a spot on the page goes nowhere', a, 'This link points to "' + href + '", but nothing on the page has that id.' );
			}
		} );

		// Labels that point at nothing, and empty labels.
		doc.querySelectorAll( 'label' ).forEach( ( label ) => {
			const forId = label.getAttribute( 'for' );
			const text = ( label.textContent || '' ).replace( /\s+/g, ' ' ).trim();
			if ( forId ) {
				const target = doc.getElementById( forId );
				if ( ! target || ! /^(INPUT|SELECT|TEXTAREA|METER|OUTPUT|PROGRESS|BUTTON)$/.test( target.tagName ) ) {
					if ( text && isVisible( label ) ) {
						c.add( 'violations', 'corea11y-label-orphaned', 'minor', [ 'best-practice' ], 'A form label isn\'t connected to any field', label, 'This label points to "' + forId + '", but there is no form field with that id, so screen readers don\'t read it with the field.' );
					}
					return;
				}
				if ( ! text && ! label.querySelector( 'img[alt]:not([alt=""])' ) && ( target.getAttribute( 'aria-label' ) || target.getAttribute( 'aria-labelledby' ) || target.getAttribute( 'title' ) ) ) {
					c.add( 'violations', 'corea11y-label-empty', 'minor', [ 'best-practice' ], 'A form label is empty', label, 'This label has no text. The field gets its name elsewhere, but the empty label can confuse some assistive technology.' );
				}
			}
		} );

		// Images: very long alt text, title instead of alt, title repeating alt,
		// and the same alt text on images right next to each other.
		const imgs = Array.prototype.filter.call( doc.querySelectorAll( 'img' ), isVisible );
		imgs.forEach( ( img, i ) => {
			const alt = img.getAttribute( 'alt' );
			const title = ( img.getAttribute( 'title' ) || '' ).trim();
			if ( alt && alt.trim().length > 150 ) {
				c.add( 'review', 'corea11y-alt-long', 'minor', [ 'wcag2a', 'wcag111' ], 'Very long alt text', img, 'This alt text is ' + alt.trim().length + ' characters. Alt text should be short; put longer descriptions in the page text or a caption.' );
			}
			if ( null === alt && title ) {
				c.add( 'violations', 'corea11y-image-title-only', 'moderate', [ 'best-practice' ], 'An image is described only by a tooltip', img, 'This image has a title but no alt attribute. Many screen readers and voice tools don\'t use the title, so give it alt text.' );
			}
			if ( alt && title && alt.trim().toLowerCase() === title.toLowerCase() ) {
				c.add( 'violations', 'corea11y-image-title-repeat', 'minor', [ 'best-practice' ], 'Image title repeats the alt text', img, 'The title attribute repeats the alt text, so some screen readers read it twice.' );
			}
			const next = imgs[ i + 1 ];
			if ( next && alt && alt.trim() && next.getAttribute( 'alt' ) && alt.trim().toLowerCase() === next.getAttribute( 'alt' ).trim().toLowerCase() && ( next.parentElement === img.parentElement || ( img.parentElement && next.parentElement && img.parentElement.parentElement === next.parentElement.parentElement ) ) ) {
				c.add( 'violations', 'corea11y-alt-duplicate', 'minor', [ 'best-practice' ], 'Images next to each other have the same alt text', next, 'This image and the one before it are both described as "' + alt.trim() + '". If they show different things, describe each one; if they are the same, mark one as decorative.' );
			}
		} );

		// Text styled with an underline that isn't a link.
		doc.querySelectorAll( 'u, [style*="underline"], span, strong, em, b, i, p' ).forEach( ( el ) => {
			if ( el.closest( 'a, button, [role="link"], [role="button"], abbr, ins, summary, label' ) || ! ownText( el ) || ! isVisible( el ) ) {
				return;
			}
			const line = window.getComputedStyle( el ).textDecorationLine || '';
			if ( line.indexOf( 'underline' ) !== -1 && ( ! el.parentElement || ( window.getComputedStyle( el.parentElement ).textDecorationLine || '' ).indexOf( 'underline' ) === -1 ) ) {
				c.add( 'violations', 'corea11y-underline-text', 'minor', [ 'best-practice' ], 'Underlined text looks like a link', el, 'Visitors expect underlined text to be a link. Use bold or italics for emphasis instead.' );
			}
		} );

		// No landmarks at all (no header, navigation, main or footer regions).
		if ( ! doc.querySelector( 'main, nav, header, footer, aside, [role="main"], [role="navigation"], [role="banner"], [role="contentinfo"], [role="complementary"], [role="region"][aria-label], [role="search"]' ) ) {
			c.add( 'violations', 'corea11y-no-landmarks', 'moderate', [ 'best-practice' ], 'The page has no regions', doc.body, 'Screen reader users jump between header, navigation, main content and footer regions. This page has none, so they have to read everything in order.' );
		}

		// Page titles that don't describe the page.
		const pageTitle = ( doc.title || '' ).replace( /\s+/g, ' ' ).trim();
		if ( pageTitle && /^(untitled( document| page)?|new page|page|document|no title|title|\(no title\))$/i.test( pageTitle ) ) {
			c.add( 'violations', 'corea11y-title-uninformative', 'moderate', [ 'wcag2a', 'wcag242' ], 'The page title doesn\'t describe the page', doc.querySelector( 'title' ) || doc.documentElement, 'The page title is "' + pageTitle + '". Browser tabs, bookmarks and screen readers use it, so it should say what the page is about.' );
		}

		// Invalid longdesc.
		doc.querySelectorAll( 'img[longdesc]' ).forEach( ( img ) => {
			const v = ( img.getAttribute( 'longdesc' ) || '' ).trim();
			if ( ! v || /\s/.test( v ) || ! /[./#]/.test( v ) ) {
				c.add( 'violations', 'corea11y-longdesc-invalid', 'moderate', [ 'wcag2a', 'wcag111' ], 'An image\'s long description link is broken', img, 'The longdesc attribute should be the address of a page describing the image, but it is "' + v.slice( 0, 60 ) + '".' );
			}
		} );

		// Embedded videos: someone needs to check the captions.
		doc.querySelectorAll( 'iframe[src]' ).forEach( ( frame ) => {
			if ( /(youtube(-nocookie)?\.com|youtu\.be|vimeo\.com|wistia\.(com|net)|dailymotion\.com|videopress\.com|loom\.com)/i.test( frame.getAttribute( 'src' ) || '' ) && isVisible( frame ) ) {
				c.add( 'review', 'corea11y-video-captions', 'moderate', [ 'wcag2a', 'wcag122' ], 'Check that this video has captions', frame, 'This is an embedded video. Videos with speech need accurate captions, and important visual information needs a description. Software can\'t check this, so please watch it with captions on.' );
			}
		} );
	}

	// Curated IBM Equal Access rules.
	async function ibmChecks( doc, c ) {
		if ( ! window.ace || ! window.ace.Checker ) {
			return;
		}
		const report = await new window.ace.Checker().check( doc, [ 'IBM_Accessibility' ] );
		( report.results || [] ).forEach( ( r ) => {
			const rule = IBM_RULES[ r.ruleId ];
			if ( ! rule || ! r.value ) {
				return;
			}
			const outcome = r.value[ 1 ];
			if ( outcome === 'PASS' || ( rule.kind === 'violations' && outcome !== 'FAIL' ) ) {
				return;
			}
			let el = null;
			try {
				el = doc.evaluate( r.path.dom, doc, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null ).singleNodeValue;
			} catch ( e ) {}
			if ( el && el.nodeType === 1 ) {
				c.add( rule.kind, 'ibm-' + r.ruleId.replace( /_/g, '-' ), rule.impact || 'moderate', rule.tags, rule.help, el, r.message || '' );
			}
		} );
	}

	window.corea11yChecks = {
		/**
		 * Run the checks on this document.
		 *
		 * @param {Object}  options     Options.
		 * @param {boolean} options.ibm Also run the curated IBM Equal Access rules.
		 * @return {Promise<{violations: Array, review: Array}>} Results in axe-core shape.
		 */
		async run( options ) {
			const c = createCollector();
			ownChecks( document, c );
			if ( options && options.ibm ) {
				try {
					await ibmChecks( document, c );
				} catch ( e ) {
					// The extra engine is optional; axe-core and CoreA11y results still stand.
				}
			}
			return c.result();
		},
	};
}() );
