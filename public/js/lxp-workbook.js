/**
 * lxp-workbook.js
 *
 * Runs on every lp_lesson single page. Finds the "Workbook Entry" section inside
 * .lp-ai-lesson-template, converts [Text Box] sentinel divs into <textarea> elements,
 * pre-fills saved answers if any exist, and adds a Save button that POSTs to the
 * workbook submission REST endpoint.
 *
 * Localised variables (via wp_localize_script) are available as lxp_workbook_vars:
 *   rest_url   - base REST URL with trailing slash (e.g. /wp-json/lms/v1/)
 *   nonce      - WP REST nonce
 *   lesson_id  - current lesson post ID (int)
 *   course_id  - parent course post ID (int)
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var vars = window.lxp_workbook_vars;
		if ( ! vars || ! vars.lesson_id ) {
			return; // not a workbook-enabled page
		}

		var template = document.querySelector( '.lp-ai-lesson-template' );
		if ( ! template ) {
			return;
		}

		// -------------------------------------------------------------------------
		// Find the Workbook Entry section by its <h3> heading text.
		// -------------------------------------------------------------------------
		var workbookSection = null;
		var headings = template.querySelectorAll( 'section h3' );
		headings.forEach( function ( h ) {
			if ( h.textContent.trim() === 'Workbook Entry' ) {
				workbookSection = h.closest( 'section' );
			}
		} );

		if ( ! workbookSection ) {
			return; // No Workbook Entry section on this lesson.
		}

		// -------------------------------------------------------------------------
		// Replace every [Text Box] sentinel div with a labelled <textarea>.
		// The label is read from the preceding <strong> element.
		// -------------------------------------------------------------------------
		var fieldMap = {}; // label -> textarea element

		var textBoxDivs = workbookSection.querySelectorAll( 'div' );
		textBoxDivs.forEach( function ( div ) {
			if ( div.textContent.trim() !== '[Text Box]' ) {
				return;
			}

			// Derive the label from the nearest preceding <strong>.
			var label = '';
			var prevP = div.previousElementSibling;
			if ( prevP ) {
				var strong = prevP.querySelector( 'strong' );
				if ( strong ) {
					label = strong.textContent.replace( /:$/, '' ).trim();
				}
			}

			var textarea = document.createElement( 'textarea' );
			textarea.setAttribute( 'data-label', label );
			textarea.setAttribute( 'placeholder', 'Type your answer here\u2026' );
			textarea.style.cssText =
				'width:100%;min-height:90px;padding:12px 14px;border:1px solid rgba(68,46,102,.25);' +
				'border-radius:12px;font-size:0.97rem;font-family:inherit;resize:vertical;' +
				'background:#fff;box-sizing:border-box;';

			div.replaceWith( textarea );

			if ( label ) {
				fieldMap[ label ] = textarea;
			}
		} );

		if ( Object.keys( fieldMap ).length === 0 ) {
			return; // Nothing to do.
		}

		// -------------------------------------------------------------------------
		// Pre-fill existing submission (if any).
		// -------------------------------------------------------------------------
		function prefillAnswers( savedFields ) {
			if ( ! savedFields ) {
				return;
			}
			Object.keys( savedFields ).forEach( function ( label ) {
				if ( fieldMap[ label ] ) {
					fieldMap[ label ].value = savedFields[ label ] || '';
				}
			} );
		}

		fetch(
			vars.rest_url + 'workbook/submission?lesson_id=' + encodeURIComponent( vars.lesson_id ),
			{
				method: 'GET',
				headers: {
					'X-WP-Nonce': vars.nonce,
				},
				credentials: 'same-origin',
			}
		)
			.then( function ( res ) {
				return res.ok ? res.json() : null;
			} )
			.then( function ( data ) {
				if ( data && data.fields ) {
					prefillAnswers( data.fields );
				}
			} )
			.catch( function () {
				// Pre-fill is best-effort; silently ignore network errors.
			} );

		// -------------------------------------------------------------------------
		// Inject a Save button at the bottom of the workbook section.
		// -------------------------------------------------------------------------
		var saveBtn = document.createElement( 'button' );
		saveBtn.textContent = 'Save Workbook';
		saveBtn.type = 'button';
		saveBtn.style.cssText =
			'margin-top:20px;padding:12px 28px;background:var(--lp-secondary-color,#442e66);' +
			'color:#fff;border:none;border-radius:12px;font-size:1rem;cursor:pointer;';

		var statusMsg = document.createElement( 'span' );
		statusMsg.style.cssText = 'margin-left:14px;font-size:0.92rem;';

		var btnWrapper = document.createElement( 'div' );
		btnWrapper.style.cssText = 'text-align:right;margin-top:12px;';
		btnWrapper.appendChild( saveBtn );
		btnWrapper.appendChild( statusMsg );

		var innerBox = workbookSection.querySelector( 'div' );
		if ( innerBox ) {
			innerBox.appendChild( btnWrapper );
		} else {
			workbookSection.appendChild( btnWrapper );
		}

		// -------------------------------------------------------------------------
		// Save button click handler.
		// -------------------------------------------------------------------------
		saveBtn.addEventListener( 'click', function () {
			var fields = {};
			Object.keys( fieldMap ).forEach( function ( label ) {
				fields[ label ] = fieldMap[ label ].value;
			} );

			saveBtn.disabled = true;
			statusMsg.style.color = '#666';
			statusMsg.textContent = 'Saving\u2026';

			fetch( vars.rest_url + 'workbook/submission', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': vars.nonce,
				},
				credentials: 'same-origin',
				body: JSON.stringify( {
					lesson_id: vars.lesson_id,
					course_id: vars.course_id,
					fields:    fields,
				} ),
			} )
				.then( function ( res ) {
					return res.json().then( function ( data ) {
						return { ok: res.ok, data: data };
					} );
				} )
				.then( function ( result ) {
					if ( result.ok && result.data.saved ) {
						statusMsg.style.color = '#2a7d4f';
						statusMsg.textContent = 'Saved!';
					} else {
						statusMsg.style.color = '#c0392b';
						statusMsg.textContent =
							( result.data && result.data.message ) ? result.data.message : 'Save failed.';
					}
				} )
				.catch( function () {
					statusMsg.style.color = '#c0392b';
					statusMsg.textContent = 'Network error. Please try again.';
				} )
				.finally( function () {
					saveBtn.disabled = false;
					setTimeout( function () {
						if ( statusMsg.textContent === 'Saved!' ) {
							statusMsg.textContent = '';
						}
					}, 4000 );
				} );
		} );
	} );
}() );
