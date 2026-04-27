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
		$course_id = absint( $request->get_param( 'course_id' ) );
		$fields    = $request->get_param( 'fields' );

		if ( $lesson_id <= 0 ) {
			return new WP_Error( 'invalid_lesson_id', 'A valid lesson_id is required.', array( 'status' => 400 ) );
		}
		if ( $course_id <= 0 ) {
			return new WP_Error( 'invalid_course_id', 'A valid course_id is required.', array( 'status' => 400 ) );
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

		return rest_ensure_response( array( 'id' => $row_id, 'saved' => true ) );
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

		if ( ! $row ) {
			return rest_ensure_response( array( 'fields' => null ) );
		}

		$fields = json_decode( $row->fields, true );

		return rest_ensure_response(
			array(
				'id'           => (int) $row->id,
				'lesson_id'    => (int) $row->lesson_id,
				'course_id'    => (int) $row->course_id,
				'fields'       => is_array( $fields ) ? $fields : array(),
				'submitted_at' => esc_html( $row->submitted_at ),
				'updated_at'   => esc_html( $row->updated_at ),
			)
		);
	}
}
