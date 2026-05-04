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

		// -------------------------------------------------------------------------
		// On load: pre-fill any previously saved response.
		// -------------------------------------------------------------------------
		function showStatus( msg, isError ) {
			statusMsg.textContent = msg;
			statusMsg.style.color = isError
				? '#c0392b'
				: 'var(--lp-secondary-color,#442e66)';
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
			.then( function () {
				showStatus( 'Response saved successfully!', false );
			} )
			.catch( function ( err ) {
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
