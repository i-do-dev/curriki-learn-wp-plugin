/**
 * lxp-capstone.js
 *
 * Runs on every lp_lesson single page. Finds the "Capstone Activity" section inside
 * .lp-ai-lesson-template, converts the [Capstone Box] sentinel div into a rich-text
 * (contenteditable) editor with a formatting toolbar and a Save button, pre-fills any
 * saved response, and POSTs on Save.
 *
 * Localised variables (via wp_localize_script) are available as lxp_capstone_vars:
 *   rest_url   - base REST URL with trailing slash (e.g. /wp-json/lms/v1/)
 *   nonce      - WP REST nonce
 *   lesson_id  - current lesson post ID (int)
 *   course_id  - parent course post ID (int)
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var vars = window.lxp_capstone_vars;
		if ( ! vars || ! vars.lesson_id ) {
			return;
		}

		activateCapstoneWithRetry( 0 );

		window.addEventListener( 'load', function () {
			activateCapstoneWithRetry( 0 );
		} );

		function activateCapstoneWithRetry( attempt ) {
			if ( document.getElementById( 'lxp-capstone-editor-wrap' ) ) {
				return;
			}

			var template    = findTemplateRoot();
			var sentinelDiv = findSentinel( template );

			if ( ! sentinelDiv ) {
				if ( attempt < 6 ) {
					window.setTimeout( function () {
						activateCapstoneWithRetry( attempt + 1 );
					}, 250 );
				}
				return;
			}

			activateCapstoneEditor( sentinelDiv );
		}

		function findTemplateRoot() {
			return document.querySelector( '.lp-ai-lesson-template' )
				|| document.querySelector( '.learn-press-course-content' )
				|| document.querySelector( '.entry-content' )
				|| document.querySelector( '.learn-press-content-item-summary' )
				|| document.querySelector( 'article' )
				|| document.body;
		}

		function findSentinel( root ) {
			var sentinel = root.querySelector( '.lxp-capstone-box' );
			if ( sentinel ) {
				return sentinel;
			}

			var allDivs = Array.from( root.querySelectorAll( 'div' ) );
			sentinel = allDivs.find( function ( div ) {
				return div.children.length === 0 && div.textContent.trim() === '[Capstone Box]';
			} );
			if ( sentinel ) {
				return sentinel;
			}

			return allDivs.find( function ( div ) {
				var text = ( div.textContent || '' ).replace( /\s+/g, ' ' );
				if ( text.indexOf( '[Capstone Box]' ) === -1 ) {
					return false;
				}

				var sectionText = '';
				var section     = div.closest ? div.closest( 'section' ) : null;
				if ( section ) {
					sectionText = ( section.textContent || '' ).replace( /\s+/g, ' ' );
				}

				return sectionText.indexOf( 'Capstone Activity' ) !== -1
					|| sectionText.indexOf( 'Apply What You Learned' ) !== -1
					|| sectionText.indexOf( 'Your Response:' ) !== -1;
			} );
		}

		function activateCapstoneEditor( sentinelDiv ) {
			// -------------------------------------------------------------------------
			// Editor wrapper.
			// Prefer Quill when available, otherwise fall back to a styled contenteditable.
			// -------------------------------------------------------------------------
			var wrapper = document.createElement( 'div' );
			wrapper.id = 'lxp-capstone-editor-wrap';
			wrapper.style.cssText =
				'border:2px solid rgba(68,46,102,.25);border-radius:12px;overflow:hidden;' +
				'font-size:1rem;font-family:inherit;margin-bottom:2px;background:#fff;';

			sentinelDiv.replaceWith( wrapper );

			var editorApi = createEditorAdapter( wrapper );

			// -------------------------------------------------------------------------
			// Status message element.
			// -------------------------------------------------------------------------
			var statusMsg = document.createElement( 'p' );
			statusMsg.id = 'lxp-capstone-status';
			statusMsg.style.cssText = 'margin-top:10px;font-size:0.9rem;font-weight:600;min-height:1.4em;';

			// -------------------------------------------------------------------------
			// Save button.
			// -------------------------------------------------------------------------
			var saveBtn = document.createElement( 'button' );
			saveBtn.textContent = 'Save Response';
			saveBtn.style.cssText =
				'margin-top:14px;padding:11px 28px;' +
				'background:var(--lp-secondary-color,#442e66);color:#fff;' +
				'border:none;border-radius:8px;font-size:1rem;font-weight:600;' +
				'cursor:pointer;transition:background .2s;';
			saveBtn.addEventListener( 'mouseover', function () {
				this.style.background = 'var(--lp-primary-color,#ffb606)';
				this.style.color = '#442e66';
			} );
			saveBtn.addEventListener( 'mouseout', function () {
				this.style.background = 'var(--lp-secondary-color,#442e66)';
				this.style.color = '#fff';
			} );

			wrapper.insertAdjacentElement( 'afterend', statusMsg );
			statusMsg.insertAdjacentElement( 'afterend', saveBtn );

			var workbookCtaWrap = document.createElement( 'div' );
			workbookCtaWrap.id = 'lxp-workbook-cta';
			workbookCtaWrap.style.cssText =
				'display:none;margin-top:14px;padding:14px 16px;' +
				'border:1px solid rgba(68,46,102,.18);border-radius:12px;' +
				'background:rgba(68,46,102,.04);text-align:center;';
			saveBtn.insertAdjacentElement( 'afterend', workbookCtaWrap );

			// -------------------------------------------------------------------------
			// On load: pre-fill any previously saved response.
			// -------------------------------------------------------------------------
			function showStatus( msg, isError ) {
				statusMsg.textContent = msg;
				statusMsg.style.color = isError
					? '#c0392b'
					: 'var(--lp-secondary-color,#442e66)';
			}

			function hideWorkbookCta() {
				workbookCtaWrap.style.display = 'none';
				workbookCtaWrap.innerHTML = '';
			}

			function showPreviewWorkbookBtn( workbookUrl ) {
				if ( ! workbookUrl ) {
					hideWorkbookCta();
					return;
				}
				workbookCtaWrap.innerHTML = '';
				var link = document.createElement( 'a' );
				link.href = workbookUrl;
				var icon = document.createElement( 'span' );
				icon.textContent = '\uD83D\uDC41\uFE0F';
				link.textContent = 'Preview Workbook';
				link.style.cssText =
					'display:inline-block;padding:10px 16px;border-radius:8px;' +
					'background:var(--lp-secondary-color,#442e66);color:#fff;' +
					'text-decoration:none;font-weight:600;font-size:.92rem;';
				link.prepend( icon );
				workbookCtaWrap.appendChild( link );
				workbookCtaWrap.style.display = 'block';
			}

			fetch( vars.rest_url + 'lesson/capstone-submission?lesson_id=' + vars.lesson_id, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': vars.nonce
				}
			} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				if ( data && data.response ) {
					editorApi.setHtml( data.response );
					showStatus( 'Last saved: ' + formatDate( data.updated_at ), false );
					if ( ( data.is_last_lesson_in_sequence || data.is_workbook_lesson ) && data.workbook_url ) {
						showPreviewWorkbookBtn( data.workbook_url );
					}
					if ( data.evaluation ) {
						renderEvaluation( data.evaluation );
					}
				}
			} );

			// -------------------------------------------------------------------------
			// On Save: POST response to REST endpoint.
			// -------------------------------------------------------------------------
			saveBtn.addEventListener( 'click', function () {
				var response = normalizeEditorHtml( editorApi.getHtml() ).trim();
				if ( ! editorApi.getText().trim() ) {
					showStatus( 'Please write your response before saving.', true );
					return;
				}

				saveBtn.disabled = true;
				saveBtn.textContent = 'Saving\u2026';
				showStatus( '', false );

				fetch( vars.rest_url + 'lesson/capstone-submission', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': vars.nonce
					},
					body: JSON.stringify( {
						lesson_id: vars.lesson_id,
						response:  response
					} )
				} )
				.then( function ( res ) {
					if ( ! res.ok ) {
						return res.json().then( function ( err ) {
							throw new Error( err.message || 'Server error.' );
						} );
					}
					return res.json();
				} )
				.then( function ( data ) {
					showStatus( 'Response saved successfully!', false );

					if ( data && ( data.is_last_lesson_in_sequence || data.is_workbook_lesson ) && data.workbook_url ) {
						showPreviewWorkbookBtn( data.workbook_url );
					} else {
						hideWorkbookCta();
					}

					if ( data && data.response_changed ) {
						renderEvaluation( null );

						fetch( vars.rest_url + 'lesson/capstone-evaluation', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': vars.nonce
							},
							body: JSON.stringify( { lesson_id: vars.lesson_id } )
						} )
						.then( function ( res ) { return res.json(); } )
						.then( function ( evalData ) {
							if ( evalData && evalData.evaluation ) {
								renderEvaluation( evalData.evaluation );
							} else {
								renderEvaluation( '' );
							}
						} )
						.catch( function () {
							renderEvaluation( '' );
						} );
					}
				} )
				.catch( function ( err ) {
					hideWorkbookCta();
					showStatus( 'Save failed: ' + err.message, true );
				} )
				.finally( function () {
					saveBtn.disabled = false;
					saveBtn.textContent = 'Save Response';
				} );
			} );

			function createEditorAdapter( editorWrapper ) {
				if ( 'function' === typeof window.Quill ) {
					var quill = new window.Quill( editorWrapper, {
						theme: 'snow',
						placeholder: 'Write your capstone response here\u2026',
						modules: {
							toolbar: [
								[ 'bold', 'italic', 'underline' ],
								[ { list: 'ordered' }, { list: 'bullet' } ],
							],
						},
					} );

					var quillEditorEl = editorWrapper.querySelector( '.ql-editor' );
					if ( quillEditorEl ) {
						quillEditorEl.style.minHeight = '200px';
					}

					return {
						setHtml: function ( html ) {
							quill.clipboard.dangerouslyPasteHTML( html || '' );
						},
						getHtml: function () {
							return quill.root.innerHTML;
						},
						getText: function () {
							return quill.getText();
						}
					};
				}

				var fallbackEditor = document.createElement( 'div' );
				fallbackEditor.contentEditable = 'true';
				fallbackEditor.setAttribute( 'role', 'textbox' );
				fallbackEditor.setAttribute( 'aria-label', 'Capstone response editor' );
				fallbackEditor.setAttribute( 'data-placeholder', 'Write your capstone response here...' );
				fallbackEditor.style.cssText =
					'min-height:200px;padding:14px 16px;outline:none;line-height:1.6;' +
					'white-space:pre-wrap;';
				editorWrapper.appendChild( fallbackEditor );

				return {
					setHtml: function ( html ) {
						fallbackEditor.innerHTML = html || '';
					},
					getHtml: function () {
						return fallbackEditor.innerHTML;
					},
					getText: function () {
						return fallbackEditor.textContent || '';
					}
				};
			}
		}

		// -------------------------------------------------------------------------
		// Helpers
		// -------------------------------------------------------------------------

		/**
		 * Quill 2.x stores every list as <ol> and distinguishes bullet vs ordered
		 * via data-list="bullet"|"ordered" on each <li>. wp_kses_post strips
		 * data-list at save time, so we must normalise before posting:
		 * split each <ol> into runs of <ul> (bullet) and <ol> (ordered) so
		 * standard HTML is stored in the DB and renders correctly everywhere.
		 */
		function normalizeEditorHtml( html ) {
			if ( 'function' !== typeof window.Quill ) {
				return html;
			}

			var temp = document.createElement( 'div' );
			temp.innerHTML = html;

			var ols = Array.from( temp.querySelectorAll( 'ol' ) );
			ols.forEach( function ( ol ) {
				var children = Array.from( ol.childNodes );
				var groups = [];
				var current = null;

				children.forEach( function ( node ) {
					var type = 'ordered';
					if ( node.nodeName === 'LI' ) {
						var dl = node.getAttribute && node.getAttribute( 'data-list' );
						type = ( dl === 'bullet' ) ? 'bullet' : 'ordered';
					}
					if ( ! current || current.type !== type ) {
						current = { type: type, nodes: [] };
						groups.push( current );
					}
					current.nodes.push( node );
				} );

				// Only replace if there is at least one bullet group; pure ordered lists are fine.
				var hasBullet = groups.some( function ( g ) { return g.type === 'bullet'; } );
				if ( ! hasBullet ) { return; }

				var fragment = document.createDocumentFragment();
				groups.forEach( function ( group ) {
					var tag = group.type === 'bullet' ? 'ul' : 'ol';
					var list = document.createElement( tag );
					group.nodes.forEach( function ( n ) { list.appendChild( n.cloneNode( true ) ); } );
					fragment.appendChild( list );
				} );
				ol.parentNode.replaceChild( fragment, ol );
			} );

			// Strip remaining data-list attributes (e.g. on pure ordered lists).
			temp.querySelectorAll( 'li[data-list]' ).forEach( function ( li ) {
				li.removeAttribute( 'data-list' );
			} );

			return temp.innerHTML;
		}

		function formatDate( dateStr ) {
			if ( ! dateStr ) { return ''; }
			var d = new Date( dateStr.replace( ' ', 'T' ) );
			if ( isNaN( d.getTime() ) ) { return dateStr; }
			return d.toLocaleDateString( undefined, { year: 'numeric', month: 'short', day: 'numeric' } )
				+ ' ' + d.toLocaleTimeString( undefined, { hour: '2-digit', minute: '2-digit' } );
		}

		/**
		 * Escape a plain-text string for safe innerHTML insertion.
		 */
		function escapeHtml( str ) {
			if ( ! str ) { return ''; }
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		}

		/**
		 * Render (or refresh) the evaluation block below the editor.
		 *
		 * evaluationText - plain-text evaluation string from Bedrock, or null / '' for
		 *                  the "generating" placeholder state.
		 *
		 * The block replaces any existing #lxp-capstone-eval-wrap.
		 */
		function renderEvaluation( evaluationText ) {
			var existing = document.getElementById( 'lxp-capstone-eval-wrap' );
			if ( existing ) {
				existing.remove();
			}

			var wrap = document.createElement( 'div' );
			wrap.id = 'lxp-capstone-eval-wrap';
			wrap.style.cssText = 'margin-top:22px;';

			var evalBodyHtml;
			if ( null === evaluationText ) {
				// Placeholder while Bedrock is still generating.
				evalBodyHtml =
					'<em style="color:#aaa;font-size:.9rem;">Generating evaluation…</em>';
			} else if ( '' === evaluationText ) {
				evalBodyHtml =
					'<em style="color:#aaa;font-size:.9rem;">Evaluation unavailable.</em>';
			} else {
				// Preserve newlines as <br> so multi-sentence evaluations are readable.
				evalBodyHtml = escapeHtml( evaluationText ).replace( /\n/g, '<br>' );
			}

			wrap.innerHTML =
				'<div style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;' +
				'letter-spacing:.04em;margin-bottom:6px;">Evaluation</div>' +
				'<div style="background:rgba(255,182,6,.08);border:1px solid rgba(255,182,6,.35);' +
				'border-radius:10px;padding:14px 16px;font-size:.9rem;line-height:1.65;color:#333;">' +
				evalBodyHtml +
				'</div>';

			// Insert the evaluation block below the Save button.
			saveBtn.insertAdjacentElement( 'afterend', wrap );
		}
	} );
} )();
