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

		$course_id     = absint( $request->get_param( 'course_id' ) );
		$district_name = sanitize_text_field( wp_unslash( $request->get_param( 'district_name' ) ) );
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

		// Fetch policy-flagged lessons with the user's workbook submissions.
		require_once LMS__PLUGIN_DIR . 'repositories/class-workbook-submission-repository.php';
		$repo    = new TL_Workbook_Submission_Repository();
		$lessons = $repo->get_course_summary( $course_id, $user_id );

		// Build the HTML for dompdf.
		$logo_url    = self::get_site_logo_url();
		$course_title = esc_html( $course->post_title );
		$html        = self::build_pdf_html( $course_title, $district_name, $effective_date, $user_name, $logo_url, $lessons );

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
		$safe_course = sanitize_file_name( $course->post_title );
		$filename    = 'policy-document-' . $safe_course . '.pdf';

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
	private static function build_pdf_html( $course_title, $district_name, $effective_date, $user_name, $logo_url, $lessons ) {
		$brand_color = '#442e66';

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11pt;
    color: #2d2d2d;
    line-height: 1.55;
  }
  /* ---- Header ---- */
  .pdf-header {
    border-bottom: 3px solid <?php echo esc_attr( $brand_color ); ?>;
    padding-bottom: 12px;
    margin-bottom: 18px;
  }
  .pdf-header table {
    width: 100%;
    border-collapse: collapse;
  }
  .pdf-header td {
    vertical-align: middle;
  }
  .pdf-header .logo-cell {
    width: 90px;
  }
  .pdf-header img {
    max-width: 80px;
    max-height: 60px;
  }
  .pdf-header .title-cell {
    padding-left: 16px;
  }
  .pdf-header h1 {
    font-size: 15pt;
    color: <?php echo esc_attr( $brand_color ); ?>;
    font-weight: bold;
  }
  /* ---- Meta block ---- */
  .pdf-meta {
    background: #f4f1f8;
    border-left: 4px solid <?php echo esc_attr( $brand_color ); ?>;
    padding: 12px 16px;
    margin-bottom: 22px;
    font-size: 10.5pt;
  }
  .pdf-meta table {
    border-collapse: collapse;
    width: 100%;
  }
  .pdf-meta td {
    padding: 3px 8px 3px 0;
    vertical-align: top;
  }
  .pdf-meta .label {
    font-weight: bold;
    color: <?php echo esc_attr( $brand_color ); ?>;
    width: 170px;
    white-space: nowrap;
  }
  /* ---- Lesson sections ---- */
  .lesson-block {
    margin-bottom: 24px;
    page-break-inside: avoid;
  }
  .lesson-title {
    background: <?php echo esc_attr( $brand_color ); ?>;
    color: #fff;
    font-size: 11.5pt;
    font-weight: bold;
    padding: 7px 12px;
    margin-bottom: 10px;
  }
  .field-block {
    margin-bottom: 10px;
    padding: 0 4px;
  }
  .field-label {
    font-weight: bold;
    font-size: 10pt;
    color: #444;
    margin-bottom: 3px;
  }
  .field-answer {
    background: #fafafa;
    border: 1px solid #ddd;
    padding: 8px 10px;
    font-size: 10pt;
    color: #2d2d2d;
    white-space: pre-wrap;
    word-wrap: break-word;
  }
  .no-answer {
    color: #aaa;
    font-style: italic;
    font-size: 10pt;
  }
  /* ---- Empty state ---- */
  .empty-state {
    text-align: center;
    color: #888;
    padding: 30px 0;
    font-size: 11pt;
  }
</style>
</head>
<body>

<!-- HEADER -->
<div class="pdf-header">
  <table>
    <tr>
      <?php if ( $logo_url ) : ?>
      <td class="logo-cell">
        <img src="<?php echo esc_attr( $logo_url ); ?>" alt="Logo" />
      </td>
      <?php endif; ?>
      <td class="title-cell">
        <h1><?php echo esc_html( $course_title ); ?></h1>
      </td>
    </tr>
  </table>
</div>

<!-- META -->
<div class="pdf-meta">
  <table>
    <tr>
      <td class="label">District / School:</td>
      <td><?php echo esc_html( $district_name ); ?></td>
    </tr>
    <tr>
      <td class="label">Effective Date:</td>
      <td><?php echo esc_html( $effective_date ); ?></td>
    </tr>
    <tr>
      <td class="label">Prepared by:</td>
      <td><?php echo esc_html( $user_name ); ?></td>
    </tr>
  </table>
</div>

<!-- LESSON SECTIONS -->
<?php if ( empty( $lessons ) ) : ?>
<div class="empty-state">No policy-flagged lessons found for this course.</div>
<?php else : ?>

<?php foreach ( $lessons as $lesson ) :
	$lesson_title  = isset( $lesson->lesson_title ) ? $lesson->lesson_title : '';
	$fields_json   = isset( $lesson->fields ) ? $lesson->fields : null;
	$fields        = $fields_json ? json_decode( $fields_json, true ) : array();
?>
<div class="lesson-block">
  <div class="lesson-title"><?php echo esc_html( $lesson_title ); ?></div>

  <?php if ( is_array( $fields ) && ! empty( $fields ) ) :
	  foreach ( $fields as $label => $answer ) : ?>
  <div class="field-block">
    <div class="field-label"><?php echo esc_html( $label ); ?></div>
    <div class="field-answer"><?php echo esc_html( $answer ); ?></div>
  </div>
  <?php endforeach;
  else : ?>
  <div class="field-block">
    <span class="no-answer">No answers submitted for this lesson.</span>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

</body>
</html>
		<?php
		return ob_get_clean();
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
