/**
 * CoreA11y Pro quick fixes that need the rendered page.
 *
 * Every fix only acts when the problem is actually present, never overrides what a
 * theme or author already provided, and is safe to run more than once. Content added
 * later (pop-ups, carts, sliders) is fixed too.
 */
( function () {
	'use strict';

	const cfg = window.corea11yProFixes || {};
	const text = cfg.text || {};
	const on = ( id ) => Array.isArray( cfg.fixes ) && cfg.fixes.indexOf( id ) !== -1;
	const doc = document;

	const NEW_TAB_HINT = /(new (window|tab)|opens|external)/i;
	const FILE_RE = /\.(pdf|docx?|xlsx?|pptx?|zip)(\?|#|$)/i;
	const FILE_WORDS = { pdf: /\bpdf\b/i, doc: /\b(doc|word)\b/i, docx: /\b(docx?|word)\b/i, xls: /\b(xls|excel|spreadsheet)\b/i, xlsx: /\b(xlsx?|excel|spreadsheet)\b/i, ppt: /\b(ppt|powerpoint|slides?)\b/i, pptx: /\b(pptx?|powerpoint|slides?)\b/i, zip: /\bzip\b/i };
	const SOCIAL = [ [ /facebook\.com/, 'Facebook' ], [ /(twitter\.com|\/\/x\.com)/, 'X' ], [ /instagram\.com/, 'Instagram' ], [ /linkedin\.com/, 'LinkedIn' ], [ /(youtube\.com|youtu\.be)/, 'YouTube' ], [ /tiktok\.com/, 'TikTok' ], [ /pinterest\./, 'Pinterest' ], [ /(wa\.me|whatsapp\.com)/, 'WhatsApp' ], [ /threads\.net/, 'Threads' ], [ /github\.com/, 'GitHub' ], [ /t\.me\//, 'Telegram' ] ];
	const BUTTON_HINTS = [ [ /search/, 'search' ], [ /(menu|hamburger|nav-toggle|navbar-toggle|mobile-toggle)/, 'menu' ], [ /(close|dismiss)/, 'close' ], [ /(cart|basket|bag)/, 'cart' ], [ /(account|user|login|profile)/, 'account' ], [ /(next|arrow-right|chevron-right)/, 'next' ], [ /(prev|arrow-left|chevron-left)/, 'prev' ], [ /play/, 'play' ], [ /pause/, 'pause' ] ];

	function nameOf( el ) {
		const label = ( el.getAttribute( 'aria-label' ) || '' ).trim();
		if ( label ) {
			return label;
		}
		const labelledby = el.getAttribute( 'aria-labelledby' );
		if ( labelledby ) {
			const t = labelledby.split( /\s+/ ).map( ( id ) => {
				const ref = doc.getElementById( id );
				return ref ? ref.textContent : '';
			} ).join( ' ' ).trim();
			if ( t ) {
				return t;
			}
		}
		let t = '';
		el.childNodes.forEach( function walk( node ) {
			if ( node.nodeType === 3 ) {
				t += node.nodeValue;
			} else if ( node.nodeType === 1 && node.getAttribute( 'aria-hidden' ) !== 'true' ) {
				if ( node.tagName === 'IMG' ) {
					t += ' ' + ( node.getAttribute( 'alt' ) || '' );
				} else if ( node.tagName.toLowerCase() === 'svg' ) {
					const title = node.querySelector( 'title' );
					t += ' ' + ( node.getAttribute( 'aria-label' ) || ( title ? title.textContent : '' ) );
				} else {
					node.childNodes.forEach( walk );
				}
			}
		} );
		return t.replace( /\s+/g, ' ' ).trim();
	}

	function addHint( link, hint ) {
		const label = link.getAttribute( 'aria-label' );
		if ( label ) {
			link.setAttribute( 'aria-label', label + ' ' + hint );
			return;
		}
		const span = doc.createElement( 'span' );
		span.className = 'corea11y-sr';
		span.textContent = ' ' + hint;
		link.appendChild( span );
	}

	function format( template, value ) {
		return ( template || '%s' ).replace( '%s', value );
	}

	function fixLinks( root ) {
		root.querySelectorAll( 'a[href]' ).forEach( ( a ) => {
			const href = a.getAttribute( 'href' ) || '';
			let name = nameOf( a );

			if ( on( 'link-names' ) && ! name ) {
				let label = ( a.getAttribute( 'title' ) || '' ).trim();
				if ( ! label ) {
					const img = a.querySelector( 'img[title]' );
					label = img ? img.getAttribute( 'title' ).trim() : '';
				}
				if ( ! label ) {
					const social = SOCIAL.find( ( s ) => s[ 0 ].test( href ) );
					if ( social ) {
						label = social[ 1 ];
					} else if ( /^mailto:/i.test( href ) ) {
						label = text.email || 'Email';
					} else if ( /^tel:/i.test( href ) ) {
						label = text.phone || 'Call';
					}
				}
				if ( label ) {
					a.setAttribute( 'aria-label', label );
					name = label;
				}
			}

			if ( on( 'link-titles' ) ) {
				const title = ( a.getAttribute( 'title' ) || '' ).trim();
				if ( title && name && title.toLowerCase() === name.toLowerCase() ) {
					a.removeAttribute( 'title' );
				}
			}

			if ( on( 'file-type' ) && ! a.querySelector( '.corea11y-sr-file' ) ) {
				const m = href.split( '?' )[ 0 ].match( FILE_RE );
				if ( m ) {
					const ext = m[ 1 ].toLowerCase();
					if ( FILE_WORDS[ ext ] && ! FILE_WORDS[ ext ].test( name + ' ' + ( a.getAttribute( 'title' ) || '' ) ) ) {
						const label = ( text.files && text.files[ ext ] ) || ext.toUpperCase();
						if ( a.getAttribute( 'aria-label' ) ) {
							a.setAttribute( 'aria-label', a.getAttribute( 'aria-label' ) + ' (' + label + ')' );
						} else {
							const span = doc.createElement( 'span' );
							span.className = 'corea11y-sr corea11y-sr-file';
							span.textContent = ' (' + label + ')';
							a.appendChild( span );
						}
					}
				}
			}

			if ( on( 'new-tab' ) && ( a.getAttribute( 'target' ) || '' ).toLowerCase() === '_blank' && ! NEW_TAB_HINT.test( nameOf( a ) + ' ' + ( a.getAttribute( 'title' ) || '' ) ) ) {
				addHint( a, text.newTab || '(opens in a new tab)' );
			}
		} );
	}

	function fixButtons( root ) {
		if ( ! on( 'button-names' ) ) {
			return;
		}
		root.querySelectorAll( 'button, [role="button"], input[type="button"], input[type="submit"]' ).forEach( ( b ) => {
			if ( b.tagName === 'INPUT' ? ( b.value || '' ).trim() || b.getAttribute( 'aria-label' ) : nameOf( b ) ) {
				return;
			}
			let label = ( b.getAttribute( 'title' ) || '' ).trim();
			if ( ! label ) {
				const hint = ( ( b.className && b.className.baseVal !== undefined ? b.className.baseVal : b.className ) + ' ' + b.id + ' ' + ( b.getAttribute( 'aria-controls' ) || '' ) ).toLowerCase();
				const match = BUTTON_HINTS.find( ( h ) => h[ 0 ].test( hint ) );
				label = match && text.buttons ? text.buttons[ match[ 1 ] ] : '';
			}
			if ( label ) {
				b.setAttribute( 'aria-label', label );
			}
		} );
	}

	const AUTOCOMPLETE = [ [ /(first.?name|fname|given)/, 'given-name' ], [ /(last.?name|lname|surname|family)/, 'family-name' ], [ /(^|[_-])(your-)?name$|full.?name|^author$/, 'name' ], [ /(phone|^tel$|mobile)/, 'tel' ], [ /(company|organi[sz]ation)/, 'organization' ], [ /(zip|postal|postcode)/, 'postal-code' ], [ /(^|[_-])(city|town)$/, 'address-level2' ], [ /country/, 'country-name' ], [ /(street|address.?(line)?.?1|^address$)/, 'street-address' ], [ /(^url$|website)/, 'url' ] ];

	function fixFields( root ) {
		root.querySelectorAll( 'input, textarea' ).forEach( ( field ) => {
			const type = ( field.getAttribute( 'type' ) || 'text' ).toLowerCase();
			if ( [ 'text', 'email', 'tel', 'url', 'search' ].indexOf( type ) === -1 && field.tagName !== 'TEXTAREA' ) {
				return;
			}
			if ( on( 'autocomplete' ) && field.tagName === 'INPUT' && type !== 'search' && ! field.hasAttribute( 'autocomplete' ) && ! field.closest( '[role="search"], .search-form, form[role="search"]' ) ) {
				let value = '';
				if ( type === 'email' ) {
					value = 'email';
				} else if ( type === 'tel' ) {
					value = 'tel';
				} else {
					const key = ( ( field.getAttribute( 'name' ) || '' ) + ' ' + ( field.id || '' ) ).toLowerCase().split( ' ' );
					const match = AUTOCOMPLETE.find( ( a ) => key.some( ( k ) => k && a[ 0 ].test( k ) ) );
					value = match ? match[ 1 ] : '';
				}
				if ( value ) {
					field.setAttribute( 'autocomplete', value );
				}
			}
			if ( on( 'placeholder-names' ) ) {
				const placeholder = ( field.getAttribute( 'placeholder' ) || '' ).trim();
				const labelled = ( field.labels && field.labels.length ) || field.getAttribute( 'aria-label' ) || field.getAttribute( 'aria-labelledby' ) || ( field.getAttribute( 'title' ) || '' ).trim();
				if ( placeholder && ! labelled ) {
					field.setAttribute( 'aria-label', placeholder );
				}
			}
		} );
	}

	function fixStructure( root ) {
		if ( on( 'decorative-svg' ) ) {
			root.querySelectorAll( 'a svg, button svg, [role="button"] svg' ).forEach( ( svg ) => {
				if ( svg.getAttribute( 'role' ) === 'img' || svg.getAttribute( 'aria-label' ) || svg.querySelector( 'title' ) || svg.getAttribute( 'aria-hidden' ) ) {
					return;
				}
				const control = svg.closest( 'a, button, [role="button"]' );
				const clone = control.cloneNode( true );
				clone.querySelectorAll( 'svg' ).forEach( ( s ) => s.remove() );
				if ( nameOf( clone ) || control.getAttribute( 'aria-label' ) || control.getAttribute( 'aria-labelledby' ) ) {
					svg.setAttribute( 'aria-hidden', 'true' );
					svg.setAttribute( 'focusable', 'false' );
				}
			} );
		}
	}

	function fixMain() {
		if ( ! on( 'main-landmark' ) || doc.querySelector( 'main, [role="main"]' ) ) {
			return;
		}
		const main = doc.querySelector( '#main, #content, #primary, .site-main, .site-content, .content-area, #corea11y-main' );
		if ( main ) {
			main.setAttribute( 'role', 'main' );
		}
	}

	// Keep focused elements out from under sticky headers and bars.
	function stickyTop() {
		let bottom = 0;
		doc.querySelectorAll( 'header, nav, [class*="sticky"], [class*="fixed"], #wpadminbar, body > div' ).forEach( ( el ) => {
			const style = window.getComputedStyle( el );
			if ( style.position !== 'fixed' && style.position !== 'sticky' ) {
				return;
			}
			const r = el.getBoundingClientRect();
			if ( r.top <= 1 && r.height > 0 && r.height < window.innerHeight * 0.4 && r.width > window.innerWidth * 0.5 ) {
				bottom = Math.max( bottom, r.bottom );
			}
		} );
		return bottom;
	}
	function setupFocusObscured() {
		if ( ! on( 'focus-obscured' ) ) {
			return;
		}
		const update = () => {
			const top = stickyTop();
			doc.documentElement.style.scrollPaddingTop = top ? Math.ceil( top + 8 ) + 'px' : '';
		};
		update();
		window.addEventListener( 'resize', update );
		doc.addEventListener( 'focusin', ( event ) => {
			const top = stickyTop();
			if ( ! top || ! event.target.getBoundingClientRect ) {
				return;
			}
			const r = event.target.getBoundingClientRect();
			if ( r.top < top && r.bottom > 0 && ! event.target.closest( 'header, nav, #wpadminbar, [class*="sticky"], [class*="fixed"]' ) ) {
				window.scrollBy( 0, r.top - top - 8 );
			}
		} );
	}

	// ---- Landmarks and headings ---------------------------------------------

	function fixMenus( root ) {
		if ( ! on( 'menu-names' ) || ! cfg.menus ) {
			return;
		}
		const slugs = cfg.menus.slugs || {};
		const locations = cfg.menus.locations || {};
		root.querySelectorAll( 'nav, [role="navigation"]' ).forEach( ( nav ) => {
			if ( nav.getAttribute( 'aria-label' ) || nav.getAttribute( 'aria-labelledby' ) ) {
				return;
			}
			let name = '';
			nav.querySelectorAll( 'ul[id], div[id]' ).forEach( ( list ) => {
				if ( name ) {
					return;
				}
				const id = list.id;
				if ( id.indexOf( 'menu-' ) === 0 && slugs[ id.slice( 5 ) ] ) {
					name = slugs[ id.slice( 5 ) ];
					return;
				}
				Object.keys( locations ).some( ( loc ) => {
					if ( id === loc || id === loc + '-menu' || id === 'menu-' + loc || id === loc.replace( /_/g, '-' ) + '-menu' ) {
						name = locations[ loc ];
						return true;
					}
					return false;
				} );
			} );
			if ( name ) {
				nav.setAttribute( 'aria-label', name );
			}
		} );
	}

	function topLevel( el ) {
		return ! el.closest( 'main, [role="main"], article, aside, section, nav' ) || el.closest( 'main, article, aside, section, nav' ) === el;
	}

	function fixHeaderFooter() {
		if ( ! on( 'header-footer' ) ) {
			return;
		}
		const hasBanner = doc.querySelector( '[role="banner"]' ) || Array.prototype.some.call( doc.querySelectorAll( 'header' ), ( h ) => ! h.parentElement.closest( 'main, article, aside, section, nav, [role="main"]' ) );
		if ( ! hasBanner ) {
			const header = Array.prototype.find.call( doc.querySelectorAll( '#masthead, .site-header, #site-header, #header, .header-main' ), topLevel );
			if ( header ) {
				header.setAttribute( 'role', 'banner' );
			}
		}
		const hasInfo = doc.querySelector( '[role="contentinfo"]' ) || Array.prototype.some.call( doc.querySelectorAll( 'footer' ), ( f ) => ! f.parentElement.closest( 'main, article, aside, section, nav, [role="main"]' ) );
		if ( ! hasInfo ) {
			const footer = Array.prototype.find.call( doc.querySelectorAll( '#colophon, .site-footer, #site-footer, #footer' ), topLevel );
			if ( footer ) {
				footer.setAttribute( 'role', 'contentinfo' );
			}
		}
	}

	function fixPageHeading() {
		if ( ! on( 'page-heading' ) || ! cfg.heading || doc.querySelector( 'h1, [role="heading"][aria-level="1"]' ) ) {
			return;
		}
		const h1 = doc.createElement( 'h1' );
		h1.className = 'corea11y-sr corea11y-page-heading';
		h1.textContent = cfg.heading;
		const main = doc.querySelector( 'main, [role="main"]' );
		if ( main ) {
			main.insertBefore( h1, main.firstChild );
		} else {
			doc.body.insertBefore( h1, doc.body.firstChild );
		}
	}

	// ---- Links ------------------------------------------------------------------

	function visibleText( el ) {
		let t = '';
		el.childNodes.forEach( function walk( n ) {
			if ( n.nodeType === 3 ) {
				t += n.nodeValue;
			} else if ( n.nodeType === 1 && n.getAttribute( 'aria-hidden' ) !== 'true' && ! ( n.classList && n.classList.contains( 'corea11y-sr' ) ) ) {
				n.childNodes.forEach( walk );
			}
		} );
		return t.replace( /[\s\u00a0]+/g, ' ' ).trim();
	}

	function skipLink( a ) {
		a.setAttribute( 'aria-hidden', 'true' );
		a.setAttribute( 'tabindex', '-1' );
		a.setAttribute( 'data-corea11y-skipped', '1' );
	}

	function fixLinkPairs( root ) {
		if ( on( 'empty-links' ) ) {
			root.querySelectorAll( 'a[href]' ).forEach( ( a ) => {
				if ( a.getAttribute( 'aria-hidden' ) || a.getAttribute( 'role' ) || a.getAttribute( 'aria-controls' ) || a.getAttribute( 'aria-expanded' ) || a.getAttribute( 'onclick' ) ) {
					return;
				}
				const hasContent = a.querySelector( 'img, svg, i, video, picture, canvas, input, [class*="icon"]' ) || ( a.getAttribute( 'title' ) || '' ).trim() || nameOf( a );
				const style = window.getComputedStyle( a );
				const hasBackground = style.backgroundImage && style.backgroundImage !== 'none';
				if ( ! hasContent && ! hasBackground && ! ( a.innerHTML || '' ).replace( /&nbsp;|<br\s*\/?>|\s/gi, '' ) ) {
					skipLink( a );
				}
			} );
		}
		if ( on( 'merge-links' ) ) {
			const links = Array.prototype.filter.call( root.querySelectorAll( 'a[href]' ), ( a ) => ! a.getAttribute( 'aria-hidden' ) );
			for ( let i = 0; i < links.length - 1; i++ ) {
				const a = links[ i ];
				const b = links[ i + 1 ];
				if ( a.href !== b.href || ( a.getAttribute( 'href' ) || '' ).charAt( 0 ) === '#' ) {
					continue;
				}
				const aImage = a.querySelector( 'img' ) && ! visibleText( a );
				const bImage = b.querySelector( 'img' ) && ! visibleText( b );
				if ( aImage === bImage ) {
					continue;
				}
				// Only links that sit together (same card or block), not the same URL far apart.
				let shared = a.parentElement;
				let depth = 0;
				while ( shared && ! shared.contains( b ) && depth < 4 ) {
					shared = shared.parentElement;
					depth++;
				}
				if ( shared && shared.contains( b ) && depth < 4 ) {
					skipLink( aImage ? a : b );
				}
			}
		}
		if ( on( 'page-numbers' ) ) {
			root.querySelectorAll( '.pagination, .nav-links, .page-numbers, .wp-block-query-pagination, .woocommerce-pagination, nav.navigation' ).forEach( ( box ) => {
				box.querySelectorAll( 'a, span' ).forEach( ( el ) => {
					const num = ( el.textContent || '' ).trim();
					if ( ! /^\d+$/.test( num ) || el.getAttribute( 'aria-label' ) || el.children.length ) {
						return;
					}
					if ( el.tagName === 'A' ) {
						el.setAttribute( 'aria-label', format( text.page || 'Page %s', num ) );
					} else if ( el.classList.contains( 'current' ) && ! el.getAttribute( 'aria-current' ) ) {
						el.setAttribute( 'aria-current', 'page' );
						el.setAttribute( 'aria-label', format( text.page || 'Page %s', num ) );
					}
				} );
			} );
		}
	}

	// ---- Images, tables, forms -------------------------------------------------

	function fixImagesTables( root ) {
		if ( on( 'image-titles' ) ) {
			root.querySelectorAll( 'img[title][alt]' ).forEach( ( img ) => {
				if ( img.getAttribute( 'title' ).trim().toLowerCase() === img.getAttribute( 'alt' ).trim().toLowerCase() ) {
					img.removeAttribute( 'title' );
				}
			} );
		}
		if ( on( 'table-headers' ) ) {
			root.querySelectorAll( 'table' ).forEach( ( table ) => {
				const role = table.getAttribute( 'role' );
				if ( role === 'presentation' || role === 'none' ) {
					return;
				}
				const rows = table.rows;
				Array.prototype.forEach.call( rows, ( row, r ) => {
					const allTh = Array.prototype.every.call( row.cells, ( c ) => c.tagName === 'TH' );
					Array.prototype.forEach.call( row.cells, ( cell, c ) => {
						if ( cell.tagName !== 'TH' || cell.hasAttribute( 'scope' ) || cell.hasAttribute( 'headers' ) ) {
							return;
						}
						if ( row.parentElement.tagName === 'THEAD' || ( r === 0 && allTh ) ) {
							cell.setAttribute( 'scope', 'col' );
						} else if ( c === 0 ) {
							cell.setAttribute( 'scope', 'row' );
						}
					} );
				} );
			} );
		}
	}

	const FIELD = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]), select, textarea';
	let fieldCounter = 0;

	function fixForms( root ) {
		if ( on( 'form-label-links' ) ) {
			root.querySelectorAll( 'label:not([for])' ).forEach( ( label ) => {
				if ( label.querySelector( FIELD ) || ! visibleText( label ) ) {
					return;
				}
				let field = null;
				let next = label.nextElementSibling;
				for ( let i = 0; i < 2 && next && ! field; i++, next = next.nextElementSibling ) {
					if ( next.matches( FIELD ) ) {
						field = next;
					} else if ( next.tagName !== 'LABEL' ) {
						const inside = next.querySelectorAll( FIELD );
						field = inside.length === 1 ? inside[ 0 ] : null;
					}
				}
				if ( ! field && label.parentElement ) {
					const inside = label.parentElement.querySelectorAll( FIELD );
					const labels = label.parentElement.querySelectorAll( 'label' );
					field = inside.length === 1 && labels.length === 1 ? inside[ 0 ] : null;
				}
				if ( ! field || ( field.labels && field.labels.length ) || field.getAttribute( 'aria-label' ) || field.getAttribute( 'aria-labelledby' ) ) {
					return;
				}
				if ( ! field.id ) {
					fieldCounter++;
					field.id = 'corea11y-field-' + fieldCounter;
				}
				label.htmlFor = field.id;
			} );
		}
		if ( on( 'required-fields' ) ) {
			root.querySelectorAll( FIELD ).forEach( ( field ) => {
				if ( field.required || field.hasAttribute( 'aria-required' ) ) {
					return;
				}
				let label = field.labels && field.labels.length ? field.labels[ 0 ].textContent : '';
				const by = field.getAttribute( 'aria-labelledby' );
				if ( ! label && by ) {
					label = by.split( /\s+/ ).map( ( id ) => ( doc.getElementById( id ) || {} ).textContent || '' ).join( ' ' );
				}
				if ( /\*|\((required|obligatoire|obligatorio|erforderlich|pflichtfeld)\)/i.test( label || '' ) ) {
					field.setAttribute( 'aria-required', 'true' );
				}
			} );
		}
		if ( on( 'form-messages' ) ) {
			root.querySelectorAll( '.elementor-message, .wpforms-error-container, .wpforms-confirmation-container, .nf-response-msg, .nf-error-msg, .gform_validation_errors, .gform_confirmation_message, .frm_error_style, .frm_message, .ff-message-success, .ff-errors-in-stack, .forminator-response-message, .mc4wp-response, .fl-form-success-message, .et-pb-contact-message, .wpcf7-response-output' ).forEach( ( box ) => {
				if ( box.getAttribute( 'role' ) || box.getAttribute( 'aria-live' ) ) {
					return;
				}
				const isError = /(error|invalid|fail|danger)/i.test( box.className );
				box.setAttribute( 'role', isError ? 'alert' : 'status' );
			} );
		}
	}

	// ---- Look: tap targets and placeholder contrast -----------------------------

	function luminance( rgb ) {
		const m = ( rgb || '' ).match( /[\d.]+/g );
		if ( ! m || m.length < 3 ) {
			return null;
		}
		const c = m.slice( 0, 3 ).map( ( v ) => {
			const x = parseFloat( v ) / 255;
			return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * c[ 0 ] + 0.7152 * c[ 1 ] + 0.0722 * c[ 2 ];
	}
	function backgroundOf( el ) {
		for ( let n = el; n && n.nodeType === 1; n = n.parentElement ) {
			const bg = window.getComputedStyle( n ).backgroundColor;
			const alpha = ( bg.match( /[\d.]+/g ) || [] )[ 3 ];
			if ( bg && bg !== 'transparent' && ( alpha === undefined || parseFloat( alpha ) > 0.5 ) ) {
				return bg;
			}
		}
		return 'rgb(255,255,255)';
	}

	function fixLook( root ) {
		if ( on( 'target-size' ) ) {
			root.querySelectorAll( 'a[href], button, [role="button"]' ).forEach( ( el ) => {
				if ( el.classList.contains( 'corea11y-target' ) || visibleText( el ) || ! el.querySelector( 'svg, img, i, [class*="icon"]' ) || el.closest( 'p, li p, td' ) ) {
					return;
				}
				const r = el.getBoundingClientRect();
				if ( r.width > 0 && r.height > 0 && ( r.width < 24 || r.height < 24 ) ) {
					el.classList.add( 'corea11y-target' );
					if ( window.getComputedStyle( el ).display === 'inline' ) {
						el.classList.add( 'corea11y-target-inline' );
					}
				}
			} );
		}
		if ( on( 'placeholder-contrast' ) ) {
			root.querySelectorAll( 'input[placeholder], textarea[placeholder]' ).forEach( ( field ) => {
				let ph = null;
				try {
					ph = window.getComputedStyle( field, '::placeholder' ).color;
				} catch ( e ) {
					return;
				}
				const bgL = luminance( backgroundOf( field ) );
				const phL = luminance( ph );
				if ( bgL === null || phL === null ) {
					return;
				}
				// Placeholder colors often use transparency; approximate with opacity.
				const opacity = parseFloat( window.getComputedStyle( field, '::placeholder' ).opacity || '1' );
				const effective = phL * opacity + bgL * ( 1 - opacity );
				const ratio = ( Math.max( bgL, effective ) + 0.05 ) / ( Math.min( bgL, effective ) + 0.05 );
				if ( ratio < 4.5 ) {
					field.classList.add( bgL > 0.4 ? 'corea11y-ph-dark' : 'corea11y-ph-light' );
				}
			} );
		}
	}

	// ---- Media and menus (whole page) -------------------------------------------

	function stopAutoplay( root ) {
		if ( ! on( 'autoplay' ) ) {
			return;
		}
		root.querySelectorAll( 'video[autoplay], audio[autoplay]' ).forEach( ( m ) => {
			if ( m.muted || m.hasAttribute( 'muted' ) ) {
				return;
			}
			m.removeAttribute( 'autoplay' );
			m.autoplay = false;
			if ( ! m.paused ) {
				m.pause();
			}
			if ( ! m.hasAttribute( 'controls' ) ) {
				m.setAttribute( 'controls', '' );
			}
		} );
		root.querySelectorAll( 'iframe[src*="autoplay=1"]' ).forEach( ( f ) => {
			const src = f.getAttribute( 'src' );
			if ( ! /[?&](mute|muted|background)=1/i.test( src ) ) {
				f.setAttribute( 'src', src.replace( /([?&])autoplay=1/i, '$1autoplay=0' ) );
			}
		} );
	}

	const SUBMENU = ':scope > ul, :scope > .sub-menu, :scope > .children, :scope > .dropdown-menu';
	function hidden( el ) {
		const s = window.getComputedStyle( el );
		const r = el.getBoundingClientRect();
		return s.display === 'none' || s.visibility === 'hidden' || parseFloat( s.opacity ) === 0 || r.right < 0 || r.left > window.innerWidth || r.height === 0;
	}
	function setupDropdowns() {
		if ( ! on( 'dropdown-keyboard' ) ) {
			return;
		}
		const close = ( except ) => {
			doc.querySelectorAll( '.corea11y-open' ).forEach( ( li ) => {
				if ( ! except || ! li.contains( except ) ) {
					li.classList.remove( 'corea11y-open' );
				}
			} );
		};
		let escaped = null;
		doc.addEventListener( 'focusin', ( event ) => {
			const target = event.target;
			close( target );
			if ( escaped && ! escaped.contains( target ) ) {
				escaped = null;
			}
			for ( let li = target.closest( 'li' ); li; li = li.parentElement ? li.parentElement.closest( 'li' ) : null ) {
				const sub = li.querySelector( SUBMENU );
				if ( li === escaped && ! sub.contains( target ) ) {
					continue;
				}
				if ( sub && li.closest( 'nav, [role="navigation"], .menu, .nav-menu' ) && ( li.classList.contains( 'corea11y-open' ) || hidden( sub ) ) ) {
					li.classList.add( 'corea11y-open' );
				}
			}
		} );
		doc.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'Escape' ) {
				return;
			}
			const open = event.target.closest && event.target.closest( '.corea11y-open' );
			if ( open ) {
				escaped = open;
				open.classList.remove( 'corea11y-open' );
				const link = open.querySelector( 'a, button' );
				if ( link && link !== event.target ) {
					link.focus();
				}
			}
		} );
		doc.addEventListener( 'pointerdown', () => close( null ) );
	}

	function fixAll( root ) {
		fixLinks( root );
		fixButtons( root );
		fixFields( root );
		fixStructure( root );
		fixMenus( root );
		fixLinkPairs( root );
		fixImagesTables( root );
		fixForms( root );
		stopAutoplay( root );
		fixLook( root );
	}

	function start() {
		fixMain();
		fixHeaderFooter();
		fixPageHeading();
		fixAll( doc );
		setupFocusObscured();
		setupDropdowns();

		// Fix content added later, in small batches.
		if ( window.MutationObserver ) {
			let pending = [];
			let timer = null;
			new MutationObserver( ( records ) => {
				records.forEach( ( r ) => r.addedNodes.forEach( ( n ) => {
					if ( n.nodeType === 1 && ! ( n.classList && n.classList.contains( 'corea11y-sr' ) ) ) {
						pending.push( n );
					}
				} ) );
				if ( pending.length && ! timer ) {
					timer = setTimeout( () => {
						const nodes = pending;
						pending = [];
						timer = null;
						nodes.forEach( ( n ) => n.isConnected && fixAll( n.parentElement || n ) );
					}, 200 );
				}
			} ).observe( doc.body, { childList: true, subtree: true } );
		}
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
