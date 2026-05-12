/**
 * lxp-policy-document.js
 *
 * Handles the "Download Policy Document" form on the learner workbook page.
 * Validates the District/School Name field, then fetches the PDF from the
 * REST endpoint and triggers a browser download via a Blob URL.
 *
 * Localised variables (via wp_localize_script) available as lxp_policy_vars:
 *   rest_url  - base REST URL with trailing slash (e.g. /wp-json/lms/v1/)
 *   nonce     - WP REST nonce
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var vars = window.lxp_policy_vars;
		if ( ! vars ) {
			return;
		}

		var form = document.getElementById( 'lxp-policy-form' );
		if ( ! form ) {
			return;
		}

		var courseId       = form.getAttribute( 'data-course-id' );
		var districtInput  = document.getElementById( 'lxp-district-name' );
		var dateInput      = document.getElementById( 'lxp-effective-date' );
		var districtError  = document.getElementById( 'lxp-district-error' );
		var submitBtn      = document.getElementById( 'lxp-download-policy-btn' );
		var statusMsg      = document.getElementById( 'lxp-policy-status' );

		if ( ! districtInput || ! submitBtn ) {
			return;
		}

		// Clear error state when the user starts typing.
		districtInput.addEventListener( 'input', function () {
			districtInput.style.borderColor = '';
			if ( districtError ) {
				districtError.hidden = true;
			}
			statusMsg.textContent = '';
			statusMsg.style.color = '';
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var districtName  = districtInput.value.trim();
			var effectiveDate = dateInput ? dateInput.value.trim() : '';

			// ---- Validation ----
			if ( ! districtName ) {
				districtInput.style.borderColor = '#c0392b';
				if ( districtError ) {
					districtError.hidden = false;
				}
				districtInput.focus();
				return;
			}

			// Reset any previous error state.
			districtInput.style.borderColor = '';
			if ( districtError ) {
				districtError.hidden = true;
			}

			// ---- Build request URL ----
			var qs = [
				'course_id='      + encodeURIComponent( courseId ),
				'district_name='  + encodeURIComponent( districtName ),
				'effective_date=' + encodeURIComponent( effectiveDate ),
			].join( '&' );

			var url = vars.rest_url + 'course/policy-document?' + qs;

			// ---- Loading state ----
			submitBtn.disabled    = true;
			statusMsg.style.color = '#666';
			statusMsg.textContent = 'Generating PDF\u2026';

			// ---- Fetch PDF as Blob ----
			fetch( url, {
				method:      'GET',
				headers:     { 'X-WP-Nonce': vars.nonce },
				credentials: 'same-origin',
			} )
				.then( function ( res ) {
					if ( ! res.ok ) {
						// Try to parse a JSON error message from the response.
						return res.json().then( function ( data ) {
							var msg = ( data && data.data && data.data.message )
								? data.data.message
								: ( 'Server error: ' + res.status );
							throw new Error( msg );
						} ).catch( function () {
							throw new Error( 'Server error: ' + res.status );
						} );
					}
					return res.blob();
				} )
				.then( function ( blob ) {
					var objectUrl = URL.createObjectURL( blob );
					var a         = document.createElement( 'a' );
					a.href        = objectUrl;
					a.download    = 'policy-document.pdf';
					document.body.appendChild( a );
					a.click();
					document.body.removeChild( a );
					URL.revokeObjectURL( objectUrl );

					statusMsg.style.color = '#2a7d4f';
					statusMsg.textContent = 'Downloaded!';
					setTimeout( function () {
						if ( statusMsg.textContent === 'Downloaded!' ) {
							statusMsg.textContent = '';
						}
					}, 4000 );
				} )
				.catch( function ( err ) {
					statusMsg.style.color = '#c0392b';
					statusMsg.textContent = err.message || 'Download failed. Please try again.';
				} )
				.finally( function () {
					submitBtn.disabled = false;
				} );
		} );
	} );
}() );
