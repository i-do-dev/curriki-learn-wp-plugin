<?php

/**
 * REST API endpoints for workbook submission persistence.
 *
 * Routes (namespace lms/v1):
 *   POST /workbook/submission  — Upsert a student's workbook answers.
 *   GET  /workbook/submission  — Fetch the current user's submission for a lesson.
 */
class Rest_Lxp_Workbook_Submission {

	/** @var TL_Workbook_Submission_Repository */
	private static $repo = null;

	private static function repo() {
		if ( null === self::$repo ) {
			self::$repo = new TL_Workbook_Submission_Repository();
		}
		return self::$repo;
	}

	/**
	 * Resolve the Preview Workbook button flags for a lesson.
	 * Returns is_last_lesson_in_sequence, is_workbook_lesson, and workbook_url.
	 */
	private static function build_preview_flags( $lesson_id ) {
		global $wpdb;

		$lesson_post        = get_post( absint( $lesson_id ) );
		$is_workbook_lesson = $lesson_post && false !== stripos( $lesson_post->post_title, 'workbook' );

		$is_last      = false;
		$workbook_url = '';

		$section_repo = new TL_LearnPress_Section_Repository();
		$course_id    = $section_repo->get_course_id_by_item_id( $lesson_id );

		if ( $course_id > 0 ) {
			// Find the last lesson in the course by section + item order.
			$last_lesson_id = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT si.item_id
				 FROM {$wpdb->prefix}learnpress_section_items si
				 INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id
				 WHERE s.section_course_id = %d AND si.item_type = 'lp_lesson'
				 ORDER BY s.section_order DESC, si.item_order DESC
				 LIMIT 1",
				$course_id
			) );

			$is_last = $last_lesson_id > 0 && $last_lesson_id === absint( $lesson_id );

			$course_post  = get_post( $course_id );
			$workbook_url = ( $course_post && $course_post->post_name )
				? home_url( '/courses/' . $course_post->post_name . '/learner-workbook/' )
				: add_query_arg( 'course_id', $course_id, home_url( '/capstone-journal/' ) );
		}

		return array(
			'is_last_lesson_in_sequence' => (bool) $is_last,
			'is_workbook_lesson'         => (bool) $is_workbook_lesson,
			'workbook_url'               => $workbook_url,
		);
	}

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	public static function init() {
		register_rest_route(
			'lms/v1',
			'/workbook/submission',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( 'Rest_Lxp_Workbook_Submission', 'upsert_submission' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Rest_Lxp_Workbook_Submission', 'get_submission' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Callbacks
	// -------------------------------------------------------------------------

	/**
	 * Upsert the logged-in user's workbook submission for a lesson.
	 *
	 * @param  WP_REST_Request $request  Required params: lesson_id (int), course_id (int), fields (array).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function upsert_submission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', 'You must be logged in to submit a workbook.', array( 'status' => 401 ) );
		}

		$lesson_id = absint( $request->get_param( 'lesson_id' ) );
		$fields    = $request->get_param( 'fields' );

		if ( $lesson_id <= 0 ) {
			return new WP_Error( 'invalid_lesson_id', 'A valid lesson_id is required.', array( 'status' => 400 ) );
		}

		// Derive course_id server-side from the lesson association.
		$section_repo = new TL_LearnPress_Section_Repository();
		$course_id    = $section_repo->get_course_id_by_item_id( $lesson_id );
		if ( $course_id <= 0 ) {
			return new WP_Error( 'invalid_course_id', 'Could not resolve a course for this lesson.', array( 'status' => 400 ) );
		}
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return new WP_Error( 'invalid_fields', 'fields must be a non-empty array.', array( 'status' => 400 ) );
		}

		// Sanitize every field value.
		$sanitized_fields = array();
		foreach ( $fields as $label => $value ) {
			$sanitized_fields[ sanitize_text_field( $label ) ] = sanitize_textarea_field( $value );
		}

		$user_id = get_current_user_id();
		$row_id  = self::repo()->upsert( $lesson_id, $course_id, $user_id, $sanitized_fields );

		if ( false === $row_id ) {
			return new WP_Error( 'db_error', 'Failed to save workbook submission.', array( 'status' => 500 ) );
		}

		$flags = self::build_preview_flags( $lesson_id );
		return rest_ensure_response( array_merge( array( 'id' => $row_id, 'saved' => true ), $flags ) );
	}

	/**
	 * Fetch the logged-in user's workbook submission for a lesson.
	 *
	 * @param  WP_REST_Request $request  Required param: lesson_id (int).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_submission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', 'You must be logged in to view workbook submissions.', array( 'status' => 401 ) );
		}

		$lesson_id = absint( $request->get_param( 'lesson_id' ) );

		if ( $lesson_id <= 0 ) {
			return new WP_Error( 'invalid_lesson_id', 'A valid lesson_id is required.', array( 'status' => 400 ) );
		}

		$user_id = get_current_user_id();
		$row     = self::repo()->get_by_lesson_user( $lesson_id, $user_id );
		$flags   = self::build_preview_flags( $lesson_id );

		if ( ! $row ) {
			return rest_ensure_response( array_merge( array( 'fields' => null ), $flags ) );
		}

		$fields = json_decode( $row->fields, true );

		return rest_ensure_response(
			array_merge(
				array(
					'id'           => (int) $row->id,
					'lesson_id'    => (int) $row->lesson_id,
					'course_id'    => (int) $row->course_id,
					'fields'       => is_array( $fields ) ? $fields : array(),
					'submitted_at' => esc_html( $row->submitted_at ),
					'updated_at'   => esc_html( $row->updated_at ),
				),
				$flags
			)
		);
	}
}
