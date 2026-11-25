/**
 * Pay For Post admin: behaviour for the Settings, Protected Content and
 * Need Help screens. Plain JavaScript; every control also works without it.
 */
( function () {
	'use strict';

	var config = window.wcPppApp || { i18n: {} };
	var i18n = config.i18n || {};

	function each( selector, callback, root ) {
		Array.prototype.forEach.call( ( root || document ).querySelectorAll( selector ), callback );
	}

	/* ------------------------------------------------------ settings tabs */

	// Every tab is in the one form, so Save stores them all. The links work
	// without JavaScript; with it, switching is instant and keeps edits.
	function initSettingsTabs() {
		var nav = document.querySelector( '[data-settings-tabs]' );
		var form = document.getElementById( 'wcppp-settings-form' );
		if ( ! nav || ! form ) {
			return;
		}

		var links = Array.prototype.slice.call( nav.querySelectorAll( 'a[data-tab]' ) );
		var panes = Array.prototype.slice.call( form.querySelectorAll( '[data-pane]' ) );

		function urlFor( tab, href ) {
			var url = new window.URL( href, window.location.href );
			url.searchParams.set( 'tab', tab );
			url.hash = '';
			return url;
		}

		function show( tab, focus ) {
			if ( ! panes.some( function ( pane ) {
				return pane.getAttribute( 'data-pane' ) === tab;
			} ) ) {
				return false;
			}

			panes.forEach( function ( pane ) {
				pane.hidden = pane.getAttribute( 'data-pane' ) !== tab;
			} );
			links.forEach( function ( link ) {
				var on = link.getAttribute( 'data-tab' ) === tab;
				link.classList.toggle( 'is-current', on );
				if ( on ) {
					link.setAttribute( 'aria-current', 'true' );
				} else {
					link.removeAttribute( 'aria-current' );
				}
			} );

			// Saving comes back to this tab, and so does Discard.
			var url = urlFor( tab, window.location.href );
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', url.toString() );
			}
			var referer = form.querySelector( 'input[name="_wp_http_referer"]' );
			if ( referer ) {
				referer.value = url.pathname + url.search;
			}
			each( '[data-discard]', function ( discard ) {
				discard.href = urlFor( tab, discard.href ).toString();
			} );

			if ( focus ) {
				var heading = form.querySelector( '[data-pane="' + tab + '"] h2' );
				if ( heading ) {
					heading.setAttribute( 'tabindex', '-1' );
					heading.focus( { preventScroll: true } );
				}
			}
			return true;
		}

		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button ) {
					return;
				}
				event.preventDefault();
				show( link.getAttribute( 'data-tab' ), true );
			} );
		} );

		// A field the browser rejects on submit may be on another tab.
		form.addEventListener(
			'invalid',
			function ( event ) {
				var pane = event.target.closest( '[data-pane]' );
				if ( pane && pane.hidden ) {
					show( pane.getAttribute( 'data-pane' ), false );
				}
			},
			true
		);

		// Old links pointed at a section on the one long page: #advanced and so on.
		var hash = window.location.hash.slice( 1 );
		if ( hash ) {
			var target = document.getElementById( hash );
			var pane = target ? target.closest( '[data-pane]' ) : null;
			if ( pane && show( pane.getAttribute( 'data-pane' ), false ) && target !== pane ) {
				target.scrollIntoView();
			}
		}
	}

	/* ------------------------------------------------------------ save bar */

	var dirty = false;
	var submitting = false;

	function markDirty() {
		if ( dirty ) {
			return;
		}
		dirty = true;

		each( '[data-savebar]', function ( bar ) {
			bar.classList.add( 'is-dirty' );
			var message = bar.querySelector( '[data-savebar-msg]' );
			if ( message ) {
				message.textContent = i18n.unsaved || '';
			}
			var discard = bar.querySelector( '[data-discard]' );
			if ( discard ) {
				discard.hidden = false;
			}
		} );
	}

	function initSaveBar() {
		var form = document.getElementById( 'wcppp-settings-form' );
		if ( ! form ) {
			return;
		}

		form.addEventListener( 'input', function ( event ) {
			if ( event.target.closest( '.wcppp-paywall-preview' ) ) {
				return;
			}
			markDirty();
		} );
		form.addEventListener( 'change', function ( event ) {
			if ( event.target.closest( '.wcppp-paywall-preview' ) ) {
				return;
			}
			markDirty();
		} );
		form.addEventListener( 'submit', function () {
			submitting = true;
		} );

		// The paywall message is a TinyMCE editor in Visual mode.
		if ( window.jQuery ) {
			window.jQuery( document ).on( 'tinymce-editor-init', function ( event, editor ) {
				if ( editor && editor.id === 'wc_pay_per_post_restricted_content_default' ) {
					editor.on( 'input change undo redo ExecCommand', markDirty );
				}
			} );
		}

		window.addEventListener( 'beforeunload', function ( event ) {
			if ( dirty && ! submitting ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );

		each( '[data-discard]', function ( link ) {
			link.addEventListener( 'click', function () {
				submitting = true;
			} );
		} );
	}

	/* ------------------------------------------------------ paywall editor */

	var EDITOR_ID = 'wc_pay_per_post_restricted_content_default';

	function visualEditor() {
		var editor = window.tinymce && window.tinymce.get ? window.tinymce.get( EDITOR_ID ) : null;
		return editor && ! editor.isHidden() ? editor : null;
	}

	function paywallMessage() {
		var editor = visualEditor();
		if ( editor ) {
			return editor.getContent();
		}
		var textarea = document.getElementById( EDITOR_ID );
		return textarea ? textarea.value : '';
	}

	function insertToken( token ) {
		var editor = visualEditor();
		if ( editor ) {
			editor.focus();
			editor.execCommand( 'mceInsertContent', false, token );
			markDirty();
			return;
		}

		var textarea = document.getElementById( EDITOR_ID );
		if ( ! textarea ) {
			return;
		}
		var start = textarea.selectionStart || 0;
		var end = textarea.selectionEnd || 0;
		textarea.value = textarea.value.slice( 0, start ) + token + textarea.value.slice( end );
		textarea.focus();
		textarea.selectionStart = textarea.selectionEnd = start + token.length;
		markDirty();
	}

	function initTokens() {
		each( '.wcppp-token[data-token]', function ( button ) {
			button.addEventListener( 'click', function () {
				insertToken( button.getAttribute( 'data-token' ) );
			} );
		} );
	}

	/* --------------------------------------------------------- comments */

	function initComments() {
		var group = document.querySelector( '[data-comments-mode]' );
		if ( ! group ) {
			return;
		}

		var whenProtected = document.getElementById( 'wc_pay_per_post_turn_off_comments_when_protected' );
		var openToCustomers = document.getElementById( 'wc_pay_per_post_turn_off_comments_public' );

		group.addEventListener( 'change', function ( event ) {
			var mode = event.target.value;

			// "Customers with access" keeps the free setting on too, so that
			// comments close again if the licence lapses.
			if ( whenProtected ) {
				whenProtected.value = 'everyone' === mode ? '' : '1';
			}
			if ( openToCustomers ) {
				openToCustomers.value = 'customers' === mode ? '1' : '';
			}
		} );
	}

	/* --------------------------------------------------- dependent fields */

	function initDependents() {
		var tab = document.getElementById( 'wc_pay_per_post_display_on_my_account' );
		var lists = document.getElementById( 'wcppp-my-account-lists' );
		if ( tab && lists ) {
			tab.addEventListener( 'change', function () {
				lists.hidden = ! tab.checked;
			} );
		}

		var deleteData = document.getElementById( 'wc_pay_per_post_delete_settings' );
		if ( deleteData && i18n.deleteData ) {
			deleteData.addEventListener( 'change', function () {
				if ( deleteData.checked && ! window.confirm( i18n.deleteData ) ) {
					deleteData.checked = false;
				}
			} );
		}
	}

	/* ----------------------------------------------------------- preview */

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	function previewDocument( data ) {
		var links = ( data.stylesheets || [] )
			.map( function ( href ) {
				return '<link rel="stylesheet" href="' + escapeHtml( href ) + '">';
			} )
			.join( '' );

		return (
			'<!doctype html><html><head><meta charset="utf-8">' +
			links +
			'<style>body{margin:0;padding:16px;font:15px/1.6 Georgia,"Times New Roman",serif;color:#1f2937;background:#fff}' +
			'h1{font-size:20px;line-height:1.3;margin:0 0 12px}' +
			'.wcppp-fade{height:28px;margin-bottom:12px;border-radius:4px;background:linear-gradient(#e2e8f0,#fff)}' +
			'img{max-width:100%;height:auto}</style>' +
			'</head><body><h1>' +
			escapeHtml( data.title || '' ) +
			'</h1><div class="wcppp-fade"></div>' +
			( data.html || '' ) +
			'</body></html>'
		);
	}

	function initPreview() {
		var button = document.getElementById( 'wcppp-preview-button' );
		var input = document.getElementById( 'wcppp-preview-post' );
		var status = document.getElementById( 'wcppp-preview-status' );
		var wrap = document.getElementById( 'wcppp-preview' );
		var frame = document.getElementById( 'wcppp-preview-frame' );

		if ( ! button || ! input || ! status || ! wrap || ! frame || ! config.ajaxUrl ) {
			return;
		}

		function render() {
			var target = input.value.trim();
			if ( ! target ) {
				status.textContent = i18n.needPost || '';
				input.focus();
				return;
			}

			var body = new window.FormData();
			body.append( 'action', 'wc_ppp_preview_paywall' );
			body.append( 'nonce', config.previewNonce || '' );
			body.append( 'post', target );
			body.append( 'message', paywallMessage() );

			button.disabled = true;
			status.textContent = i18n.rendering || '';

			window
				.fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) {
					return response.json().catch( function () {
						return { success: false, data: {} };
					} );
				} )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						var message = result && result.data && result.data.message;
						status.textContent = message || i18n.previewError || '';
						return;
					}
					frame.srcdoc = previewDocument( result.data );
					wrap.hidden = false;
					status.textContent = ( i18n.rendered || '' ) + ' ' + ( result.data.title || '' );
				} )
				.catch( function () {
					status.textContent = i18n.previewError || '';
				} )
				.then( function () {
					button.disabled = false;
				} );
		}

		button.addEventListener( 'click', render );

		// Enter previews; it must not submit the settings form.
		input.addEventListener( 'keydown', function ( event ) {
			if ( 'Enter' === event.key ) {
				event.preventDefault();
				render();
			}
		} );

		// Opened from "Preview the paywall" in the post editor box, with the
		// post filled in.
		if ( input.hasAttribute( 'data-autopreview' ) && input.value ) {
			render();
		}
	}

	/* -------------------------------------------------------------- copy */

	function flash( button, text ) {
		if ( ! button.hasAttribute( 'data-label' ) ) {
			button.setAttribute( 'data-label', button.textContent );
		}
		button.textContent = text;
		window.setTimeout( function () {
			button.textContent = button.getAttribute( 'data-label' );
		}, 2000 );
	}

	function initCopy() {
		each( '[data-copy]', function ( button ) {
			button.addEventListener( 'click', function () {
				var source = document.querySelector( button.getAttribute( 'data-copy' ) );
				if ( ! source ) {
					return;
				}
				var text = source.value !== undefined ? source.value : source.textContent;

				function fallback() {
					var copied = false;
					try {
						source.removeAttribute( 'aria-hidden' );
						source.select();
						copied = document.execCommand( 'copy' );
					} catch ( e ) {
						copied = false;
					}
					flash( button, copied ? i18n.copied || '' : i18n.copyFailed || '' );
				}

				if ( navigator.clipboard && window.isSecureContext ) {
					navigator.clipboard.writeText( text ).then( function () {
						flash( button, i18n.copied || '' );
					}, fallback );
				} else {
					fallback();
				}
			} );
		} );
	}

	/* --------------------------------------------------------- confirms */

	function initConfirms() {
		each( 'form[data-confirm]', function ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				if ( ! window.confirm( form.getAttribute( 'data-confirm' ) ) ) {
					event.preventDefault();
				}
			} );
		} );
	}

	/* ------------------------------------------------- protected content */

	/*
	 * "Show buyers" on the Protected Content screen. The link opens the
	 * one-post buyers view without JavaScript; with it, the buyers load into
	 * the row below, once, and the link toggles them.
	 */
	function initBuyers() {
		var table = document.querySelector( '.wcppp-pc-table[data-buyers-nonce]' );
		if ( ! table || ! config.ajaxUrl ) {
			return;
		}

		var nonce = table.getAttribute( 'data-buyers-nonce' );

		table.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( '[data-buyers]' );
			if ( ! link ) {
				return;
			}

			var row = document.getElementById( link.getAttribute( 'aria-controls' ) );
			var panel = row ? row.querySelector( '[data-buyers-panel]' ) : null;
			if ( ! row || ! panel ) {
				return;
			}

			event.preventDefault();

			if ( 'true' === link.getAttribute( 'aria-expanded' ) ) {
				row.hidden = true;
				link.setAttribute( 'aria-expanded', 'false' );
				link.textContent = i18n.showBuyers || link.textContent;
				return;
			}

			row.hidden = false;
			link.setAttribute( 'aria-expanded', 'true' );
			link.textContent = i18n.hideBuyers || link.textContent;

			if ( 'loaded' === panel.getAttribute( 'data-state' ) || 'loading' === panel.getAttribute( 'data-state' ) ) {
				return;
			}

			panel.setAttribute( 'data-state', 'loading' );
			panel.textContent = i18n.loadingBuyers || '';

			var body = new window.FormData();
			body.append( 'action', 'wc_ppp_protected_content_buyers' );
			body.append( 'nonce', nonce || '' );
			body.append( 'post', link.getAttribute( 'data-buyers' ) );

			window
				.fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( response ) {
					return response.json().catch( function () {
						return { success: false, data: {} };
					} );
				} )
				.then( function ( result ) {
					if ( ! result || ! result.success ) {
						var message = result && result.data && result.data.message;
						panel.removeAttribute( 'data-state' );
						panel.textContent = message || i18n.buyersError || '';
						return;
					}
					panel.innerHTML = result.data.html;
					panel.setAttribute( 'data-state', 'loaded' );
				} )
				.catch( function () {
					panel.removeAttribute( 'data-state' );
					panel.textContent = i18n.buyersError || '';
				} );
		} );
	}

	/* ------------------------------------------------- tab bars on phones */

	// On a narrow screen the tab bars scroll sideways. Bring the current tab
	// into view, so the last one (What's new, Advanced) is not off the edge.
	// Only the bar scrolls, never the page.
	function initTabBars() {
		each( '.wcppp-tabs, .wcppp-sectabs, .wcppp-subnav', function ( bar ) {
			var current = bar.querySelector( 'a.is-current' );
			if ( ! current || bar.scrollWidth <= bar.clientWidth ) {
				return;
			}
			var left = current.offsetLeft - bar.offsetLeft;
			var right = left + current.offsetWidth;
			if ( left < bar.scrollLeft || right > bar.scrollLeft + bar.clientWidth ) {
				bar.scrollLeft = Math.max( 0, right - bar.clientWidth + 16 );
			}
		} );
	}

	function init() {
		initSettingsTabs();
		initSaveBar();
		initTokens();
		initComments();
		initDependents();
		initPreview();
		initCopy();
		initConfirms();
		initBuyers();
		initTabBars();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
