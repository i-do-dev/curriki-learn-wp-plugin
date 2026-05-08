/**
 * lxp-capstone.js
 *
 * Runs on every lp_lesson single page. Finds the "Capstone Activity" section inside
 * .lp-ai-lesson-template, converts the [Capstone Box] sentinel div into a <textarea>
 * with a Save button, pre-fills any saved response, and POSTs on Save.
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

		var template = document.querySelector( '.lp-ai-lesson-template' );
		if ( ! template ) {
			return;
		}

		// -------------------------------------------------------------------------
		// Find the [Capstone Box] sentinel div.
		//
		// Primary: query by class="lxp-capstone-box" stamped by capstone_html() on
		// newly-generated lessons — fast, no DOM traversal needed.
		// Fallback: text-match on leaf divs for lessons generated before the class
		// was added (backwards compatibility with existing content in the DB).
		// -------------------------------------------------------------------------
		var sentinelDiv = template.querySelector( '.lxp-capstone-box' );

		if ( ! sentinelDiv ) {
			// Fallback for legacy AI-generated lessons without the class.
			var allDivs = template.querySelectorAll( 'div' );
			allDivs.forEach( function ( div ) {
				if ( ! sentinelDiv && div.children.length === 0 && div.textContent.trim() === '[Capstone Box]' ) {
					sentinelDiv = div;
				}
			} );
		}

		if ( ! sentinelDiv ) {
			return; // Sentinel not found — lesson may not have AI content yet.
		}

		var textarea = document.createElement( 'textarea' );
		textarea.id = 'lxp-capstone-response';
		textarea.setAttribute( 'placeholder', 'Write your capstone response here\u2026' );
		textarea.style.cssText =
			'width:100%;min-height:140px;padding:14px 16px;' +
			'border:2px solid rgba(68,46,102,.25);border-radius:12px;' +
			'font-size:1rem;font-family:inherit;resize:vertical;' +
			'background:#fff;box-sizing:border-box;line-height:1.6;';

		sentinelDiv.replaceWith( textarea );

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

		textarea.insertAdjacentElement( 'afterend', statusMsg );
		statusMsg.insertAdjacentElement( 'afterend', saveBtn );

		var workbookCtaWrap = document.createElement( 'div' );
		workbookCtaWrap.id = 'lxp-workbook-cta';
		workbookCtaWrap.style.cssText =
			'display:none;margin-top:14px;padding:14px 16px;' +
			'border:1px solid rgba(68,46,102,.18);border-radius:12px;' +
			'background:rgba(68,46,102,.04);';
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

		function getCourseUrlFromLessonPath() {
			var match = window.location.pathname.match( /^\/([^\/]+)\/lessons\/[^\/]+\/?$/ );
			return match && match[1] ? '/' + match[1] + '/' : null;
		}

		function showIncompleteProgressCta( data ) {
			if ( ! data ) {
				hideWorkbookCta();
				return;
			}

			var remaining = parseInt( data.remaining_module_count, 10 );
			var unitLabel = 'module';
			if ( isNaN( remaining ) ) {
				remaining = parseInt( data.remaining_capstone_count, 10 );
				unitLabel = 'lesson capstone';
				if ( isNaN( remaining ) ) {
					remaining = 0;
				}
			}

			if ( remaining <= 0 ) {
				hideWorkbookCta();
				return;
			}

			workbookCtaWrap.innerHTML = '';

			var text = document.createElement( 'p' );
			text.textContent = 'Great effort. To unlock your Learner Workbook, go back and complete the remaining '
				+ remaining + ' ' + unitLabel + ( remaining === 1 ? '' : 's' ) + '.';
			text.style.cssText = 'margin:0 0 10px 0;color:#2f2f2f;font-size:.95rem;line-height:1.5;';
			workbookCtaWrap.appendChild( text );

			var actions = document.createElement( 'div' );
			actions.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;';

			var courseUrl = getCourseUrlFromLessonPath();
			if ( courseUrl ) {
				var link = document.createElement( 'a' );
				link.href = courseUrl;
				link.textContent = 'Continue Course';
				link.style.cssText =
					'display:inline-block;padding:10px 16px;border-radius:8px;' +
					'background:var(--lp-secondary-color,#442e66);color:#fff;' +
					'text-decoration:none;font-weight:600;font-size:.92rem;';
				actions.appendChild( link );
			}

			if ( data.workbook_url ) {
				var previewLink = document.createElement( 'a' );
				previewLink.href = data.workbook_url;
				previewLink.textContent = 'View Workbook Preview';
				previewLink.style.cssText =
					'display:inline-block;padding:10px 16px;border-radius:8px;' +
					'background:#fff;color:var(--lp-secondary-color,#442e66);' +
					'text-decoration:none;font-weight:600;font-size:.92rem;' +
					'border:1px solid rgba(68,46,102,.35);';
				actions.appendChild( previewLink );
			}

			if ( actions.children.length > 0 ) {
				workbookCtaWrap.appendChild( actions );
			}

			workbookCtaWrap.style.display = 'block';
		}

		function showWorkbookCta( workbookUrl ) {
			if ( ! workbookUrl ) {
				hideWorkbookCta();
				return;
			}

			workbookCtaWrap.innerHTML = '';
			var text = document.createElement( 'p' );
			text.textContent = 'You completed all lesson reflections. Open your workbook to review everything in one place.';
			text.style.cssText = 'margin:0 0 10px 0;color:#2f2f2f;font-size:.95rem;line-height:1.5;';

			var link = document.createElement( 'a' );
			link.href = workbookUrl;
			link.textContent = 'View Workbook';
			link.style.cssText =
				'display:inline-block;padding:10px 16px;border-radius:8px;' +
				'background:var(--lp-secondary-color,#442e66);color:#fff;' +
				'text-decoration:none;font-weight:600;font-size:.92rem;';

			workbookCtaWrap.appendChild( text );
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
				textarea.value = data.response;
				showStatus( 'Last saved: ' + formatDate( data.updated_at ), false );
			}
		} )
		.catch( function () {
			// Silently fail — pre-fill is best-effort.
		} );

		// -------------------------------------------------------------------------
		// On Save: POST response to REST endpoint.
		// -------------------------------------------------------------------------
		saveBtn.addEventListener( 'click', function () {
			var response = textarea.value.trim();
			if ( ! response ) {
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

				if ( data && data.should_show_workbook_cta && data.workbook_url ) {
					showWorkbookCta( data.workbook_url );
				} else if ( data && data.is_last_lesson_in_sequence && ! data.has_completed_all_capstones ) {
					showIncompleteProgressCta( data );
				} else {
					hideWorkbookCta();
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

		// -------------------------------------------------------------------------
		// Helpers
		// -------------------------------------------------------------------------
		function formatDate( dateStr ) {
			if ( ! dateStr ) { return ''; }
			var d = new Date( dateStr.replace( ' ', 'T' ) );
			if ( isNaN( d.getTime() ) ) { return dateStr; }
			return d.toLocaleDateString( undefined, { year: 'numeric', month: 'short', day: 'numeric' } )
				+ ' ' + d.toLocaleTimeString( undefined, { hour: '2-digit', minute: '2-digit' } );
		}
	} );
} )();
