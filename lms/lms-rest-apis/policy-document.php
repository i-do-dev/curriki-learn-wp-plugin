<?php

/**
 * REST API endpoint for generating and downloading the Policy Document PDF.
 *
 * Route (namespace lms/v1):
 *   GET /course/policy-document  — Stream a PDF of workbook answers for policy-flagged lessons.
 *
 * Required params:
 *   course_id      (int)    — Parent course post ID.
 *   district_name  (string) — District or school name (mandatory).
 *   effective_date (string) — Effective date string (supplied by the frontend form, read-only).
 */
class Rest_Lxp_Policy_Document {

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	public static function init() {
		register_rest_route(
			'lms/v1',
			'/course/policy-document',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'Rest_Lxp_Policy_Document', 'download' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	// -------------------------------------------------------------------------
	// Callback
	// -------------------------------------------------------------------------

	/**
	 * Generate and stream the Policy Document PDF.
	 *
	 * @param  WP_REST_Request $request
	 * @return void  Sends PDF headers + binary body and exits.
	 */
	public static function download( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'You must be logged in to download a policy document.' ), 401 );
			return;
		}

		$course_id      = absint( $request->get_param( 'course_id' ) );
		$district_name  = sanitize_text_field( wp_unslash( $request->get_param( 'district_name' ) ) );
		$effective_date = sanitize_text_field( wp_unslash( $request->get_param( 'effective_date' ) ) );

		if ( $course_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'A valid course_id is required.' ), 400 );
			return;
		}
		if ( '' === $district_name ) {
			wp_send_json_error( array( 'message' => 'district_name is required.' ), 400 );
			return;
		}

		$course = get_post( $course_id );
		if ( ! $course || $course->post_type !== 'lp_course' ) {
			wp_send_json_error( array( 'message' => 'Course not found.' ), 404 );
			return;
		}

		$user_id   = get_current_user_id();
		$user_data = get_userdata( $user_id );
		$user_name = $user_data ? $user_data->display_name : '';

		// 1. Policy-flagged lessons + any workbook field answers.
		$wb_repo = new TL_Workbook_Submission_Repository();
		$lessons = $wb_repo->get_course_summary( $course_id, $user_id );

		// 2. Capstone responses as fallback per lesson.
		$capstone_map = array();
		if ( ! empty( $lessons ) ) {
			$cap_repo = new TL_Capstone_Submission_Repository();
			foreach ( $lessons as $lesson ) {
				$lid = (int) $lesson->lesson_id;
				$row = $cap_repo->get_by_lesson_user( $lid, $user_id );
				if ( $row && ! empty( $row->response ) ) {
					$capstone_map[ $lid ] = $row->response;
				}
			}
		}

		// 3. Build and render PDF.
		$html = self::build_pdf_html(
			$course->post_title,
			$district_name,
			$effective_date,
			$user_name,
			self::get_site_logo_url(),
			$lessons,
			$capstone_map
		);

		// Render via dompdf.
		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			$autoloader = plugin_dir_path( LMS__PLUGIN_DIR ) . '../vendor/autoload.php';
			if ( file_exists( $autoloader ) ) {
				require_once $autoloader;
			}
		}

		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			wp_send_json_error( array( 'message' => 'PDF library not available. Please run composer install on the server.' ), 500 );
			return;
		}

		$options = new \Dompdf\Options();
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'isRemoteEnabled', true );
		$options->set( 'defaultFont', 'DejaVu Sans' );

		$dompdf = new \Dompdf\Dompdf( $options );
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();

		$pdf_output = $dompdf->output();

		// Stream to browser.
		// Build a filing-friendly filename: <course-slug>-<user-slug>-policy-document.pdf
		$safe_course = strtolower( sanitize_file_name( $course->post_title ) );
		$safe_course = preg_replace( '/[\s_]+/', '-', $safe_course );
		$safe_course = trim( preg_replace( '/-+/', '-', $safe_course ), '-' );
		$safe_user   = strtolower( sanitize_file_name( $user_name ) );
		$safe_user   = preg_replace( '/[\s_]+/', '-', $safe_user );
		$safe_user   = trim( preg_replace( '/-+/', '-', $safe_user ), '-' );
		$filename    = $safe_course . ( $safe_user ? '-' . $safe_user : '' ) . '-policy-document.pdf';

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $pdf_output ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $pdf_output;
		exit;
	}

	// -------------------------------------------------------------------------
	// PDF HTML builder
	// -------------------------------------------------------------------------

	/**
	 * Build the full HTML document string for dompdf rendering.
	 *
	 * @param  string $course_title
	 * @param  string $district_name
	 * @param  string $effective_date
	 * @param  string $user_name
	 * @param  string $logo_url
	 * @param  array  $lessons  Rows from TL_Workbook_Submission_Repository::get_course_summary().
	 * @return string
	 */
	private static function build_pdf_html(
		$course_title,
		$district_name,
		$effective_date,
		$user_name,
		$logo_url,
		$lessons,
		$capstone_map = array()
	) {
		// Group lessons by module, keyed by section_id. Preserve DB order.
		$modules = array();
		foreach ( $lessons as $lesson ) {
			$mod_id = isset( $lesson->module_id ) ? (int) $lesson->module_id : 0;
			if ( ! isset( $modules[ $mod_id ] ) ) {
				$mod_name  = ( isset( $lesson->module_name ) && '' !== trim( $lesson->module_name ) )
					? trim( $lesson->module_name ) : 'Lessons';
				$mod_order = isset( $lesson->module_order ) ? (int) $lesson->module_order : 0;
				$modules[ $mod_id ] = array(
					'name'    => $mod_name,
					'order'   => $mod_order,
					'lessons' => array(),
				);
			}
			$modules[ $mod_id ]['lessons'][] = $lesson;
		}

		$brand = '#442e66';

		ob_start();

		echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" />';
		echo '<style>';
		echo '@page { margin: 18mm 16mm 18mm 16mm; }';
		echo '* { box-sizing: border-box; }';
		echo 'body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #222; line-height: 1.6; margin: 0; padding: 0; }';
		echo '.pdf-header { border-bottom: 3px solid ' . $brand . '; padding-bottom: 10px; margin-bottom: 16px; }';
		echo '.pdf-header table { width: 100%; border-collapse: collapse; }';
		echo '.pdf-header td { vertical-align: middle; padding: 0; }';
		echo '.logo-cell { width: 80px; }';
		echo '.logo-cell img { max-width: 72px; max-height: 56px; }';
		echo '.title-cell { padding-left: 14px; }';
		echo '.pdf-course-title { font-size: 15pt; font-weight: bold; color: ' . $brand . '; margin: 0; }';
		echo '.pdf-meta { background-color: #f5f2f9; border-left: 4px solid ' . $brand . '; padding: 10px 14px; margin-bottom: 20px; }';
		echo '.pdf-meta table { border-collapse: collapse; width: 100%; }';
		echo '.pdf-meta td { padding: 2px 6px 2px 0; vertical-align: top; font-size: 10pt; }';
		echo '.meta-label { font-weight: bold; color: ' . $brand . '; width: 165px; white-space: nowrap; }';
		echo '.module-block { margin-bottom: 22px; }';
		echo '.module-heading { font-size: 12pt; font-weight: bold; color: #fff; background-color: ' . $brand . '; padding: 6px 12px; margin-bottom: 10px; }';
		echo '.lesson-block { margin-bottom: 16px; page-break-inside: avoid; }';
		echo '.lesson-heading { font-size: 10.5pt; font-weight: bold; color: ' . $brand . '; background-color: #ede8f5; border-left: 3px solid ' . $brand . '; padding: 5px 10px; margin-bottom: 8px; }';
		echo '.field-block { margin-bottom: 10px; padding: 0 8px; }';
		echo '.field-label { font-weight: bold; font-size: 9.5pt; color: #444; margin-bottom: 3px; }';
		echo '.field-answer { background-color: #fafafa; border: 1px solid #ddd; padding: 7px 10px; font-size: 9.5pt; color: #222; white-space: pre-wrap; word-wrap: break-word; min-height: 26px; }';
		echo '.field-answer-empty { color: #aaa; font-style: italic; }';
		echo '.capstone-block { padding: 0 8px; margin-bottom: 10px; }';
		echo '.capstone-label { font-weight: bold; font-size: 9.5pt; color: #444; margin-bottom: 3px; }';
		echo '.capstone-answer { background-color: #fafafa; border: 1px solid #ddd; padding: 7px 10px; font-size: 9.5pt; color: #222; white-space: normal; word-wrap: break-word; }';
		echo '.empty-state { text-align: center; color: #888; padding: 30px 0; font-style: italic; }';
		echo '</style></head><body>';

		// Header.
		echo '<div class="pdf-header"><table><tr>';
		if ( $logo_url ) {
			echo '<td class="logo-cell"><img src="' . esc_attr( $logo_url ) . '" alt="" /></td>';
		}
		echo '<td class="title-cell"><p class="pdf-course-title">' . esc_html( $course_title ) . '</p></td>';
		echo '</tr></table></div>';

		// Meta block.
		echo '<div class="pdf-meta"><table>';
		echo '<tr><td class="meta-label">District / School Name:</td><td>' . esc_html( $district_name ) . '</td></tr>';
		echo '<tr><td class="meta-label">Effective Date:</td><td>' . esc_html( $effective_date ) . '</td></tr>';
		echo '<tr><td class="meta-label">Prepared by:</td><td>' . esc_html( $user_name ) . '</td></tr>';
		echo '</table></div>';

		// Modules.
		if ( empty( $modules ) ) {
			echo '<div class="empty-state">No policy-flagged lessons found for this course.</div>';
		} else {
			foreach ( $modules as $module ) {
				echo '<div class="module-block">';
				echo '<div class="module-heading">Module ' . (int) $module['order'] . ': ' . esc_html( $module['name'] ) . '</div>';

				foreach ( $module['lessons'] as $lesson ) {
					$lid         = (int) $lesson->lesson_id;
					$fields_json = isset( $lesson->fields ) ? $lesson->fields : null;
					$fields      = ( $fields_json && '' !== $fields_json ) ? json_decode( $fields_json, true ) : null;
					$has_wb      = is_array( $fields ) && ! empty( $fields );
					$has_cap     = isset( $capstone_map[ $lid ] ) && '' !== $capstone_map[ $lid ];

					echo '<div class="lesson-block">';
					echo '<div class="lesson-heading">' . esc_html( $lesson->lesson_title ) . '</div>';

					if ( $has_wb ) {
						foreach ( $fields as $lbl => $ans ) {
							$empty_cls = '' === trim( $ans ) ? ' field-answer-empty' : '';
							$val       = '' !== trim( $ans ) ? esc_html( $ans ) : '(not answered)';
							echo '<div class="field-block">';
							echo '<div class="field-label">' . esc_html( $lbl ) . '</div>';
							echo '<div class="field-answer' . $empty_cls . '">' . $val . '</div>';
							echo '</div>';
						}
					} elseif ( $has_cap ) {
						echo '<div class="capstone-block">';
						echo '<div class="capstone-label">Response</div>';
					echo '<div class="capstone-answer">' . wp_kses_post( $capstone_map[ $lid ] ) . '</div>';
						echo '</div>';
					} else {
						$labels = self::extract_field_labels_from_html( $lid );
						if ( ! empty( $labels ) ) {
							foreach ( $labels as $lbl ) {
								echo '<div class="field-block">';
								echo '<div class="field-label">' . esc_html( $lbl ) . '</div>';
								echo '<div class="field-answer field-answer-empty">(not answered)</div>';
								echo '</div>';
							}
						} else {
							echo '<div class="capstone-block">';
							echo '<div class="capstone-label">Response</div>';
							echo '<div class="capstone-answer field-answer-empty">(not answered)</div>';
							echo '</div>';
						}
					}

					echo '</div><!-- .lesson-block -->';
				}

				echo '</div><!-- .module-block -->';
			}
		}

		echo '</body></html>';
		return ob_get_clean();
	}

	/**
	 * Extract workbook field labels from a lesson's post_content.
	 *
	 * Splits on [Text Box] sentinels and reads the last <strong> text before
	 * each sentinel as the field label - matching lxp-workbook.js logic.
	 *
	 * @param  int   $lesson_id  Lesson post ID.
	 * @return array  Ordered array of label strings.
	 */
	private static function extract_field_labels_from_html( $lesson_id ) {
		$post = get_post( absint( $lesson_id ) );
		if ( ! $post || empty( $post->post_content ) ) {
			return array();
		}
		$labels = array();
		$parts  = explode( '[Text Box]', $post->post_content );
		array_pop( $parts ); // Last segment has no sentinel after it.
		foreach ( $parts as $idx => $chunk ) {
			preg_match_all( '/<strong[^>]*>(.*?)<\/strong>/is', $chunk, $m );
			$label = '';
			if ( ! empty( $m[1] ) ) {
				$label = rtrim( trim( wp_strip_all_tags( end( $m[1] ) ) ), ':' );
			}
			$labels[] = '' !== $label ? $label : ( 'Field ' . ( $idx + 1 ) );
		}
		return $labels;
	}


	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the site-wide WordPress custom logo URL, or empty string if none set.
	 *
	 * @return string
	 */
	private static function get_site_logo_url() {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( ! $logo_id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( absint( $logo_id ), 'medium' );
		return $url ? $url : '';
	}
}
