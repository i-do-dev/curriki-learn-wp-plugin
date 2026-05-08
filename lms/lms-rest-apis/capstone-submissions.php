<?php

/**
 * REST API endpoints for capstone submission persistence.
 *
 * Routes (namespace lms/v1):
 *   POST /lesson/capstone-submission         — Upsert a student's capstone response for a lesson.
 *   GET  /lesson/capstone-submission         — Fetch the current user's submission for a lesson.
 *   GET  /course/capstone-submissions        — All lessons in a course with the user's responses (journal).
 */
class Rest_Lxp_Capstone_Submission {

	/** @var TL_Capstone_Submission_Repository */
	private static $repo = null;

	private static function repo() {
		if ( null === self::$repo ) {
			self::$repo = new TL_Capstone_Submission_Repository();
		}
		return self::$repo;
	}

	private static function build_workbook_url( $course_id ) {
		$course_post = get_post( absint( $course_id ) );
		if ( ! $course_post || empty( $course_post->post_name ) ) {
			return add_query_arg( 'course_id', absint( $course_id ), home_url( '/capstone-journal/' ) );
		}

		return home_url( '/courses/' . $course_post->post_name . '/learner-workbook/' );
	}

	private static function get_completion_state( $course_id, $lesson_id, $user_id ) {
		$rows = self::repo()->get_course_summary( $course_id, $user_id );

		$total_lessons     = count( $rows );
		$submitted_lessons = 0;
		$modules           = array();

		foreach ( $rows as $row ) {
			$is_submitted = isset( $row->response ) && '' !== trim( (string) $row->response );
			if ( $is_submitted ) {
				$submitted_lessons++;
			}

			$module_id = isset( $row->module_id ) ? absint( $row->module_id ) : 0;
			if ( $module_id > 0 ) {
				if ( ! isset( $modules[ $module_id ] ) ) {
					$modules[ $module_id ] = array(
						'total'     => 0,
						'submitted' => 0,
					);
				}

				$modules[ $module_id ]['total']++;
				if ( $is_submitted ) {
					$modules[ $module_id ]['submitted']++;
				}
			}
		}

		$total_modules = count( $modules );
		$completed_modules = 0;
		foreach ( $modules as $module_stats ) {
			if ( $module_stats['total'] > 0 && $module_stats['submitted'] === $module_stats['total'] ) {
				$completed_modules++;
			}
		}

		$last_lesson_id = 0;
		if ( ! empty( $rows ) ) {
			$last_row       = end( $rows );
			$last_lesson_id = isset( $last_row->lesson_id ) ? (int) $last_row->lesson_id : 0;
			reset( $rows );
		}

		$is_last_lesson_in_sequence = $last_lesson_id > 0 && $last_lesson_id === absint( $lesson_id );
		$has_completed_all          = $total_lessons > 0 && $submitted_lessons === $total_lessons;
		$remaining_capstones        = max( 0, $total_lessons - $submitted_lessons );
		$remaining_modules          = max( 0, $total_modules - $completed_modules );

		$course_post = get_post( absint( $course_id ) );
		$course_slug = $course_post ? (string) $course_post->post_name : '';

		return array(
			'course_id'                    => absint( $course_id ),
			'course_slug'                  => $course_slug,
			'total_lessons'                => (int) $total_lessons,
			'submitted_lessons'            => (int) $submitted_lessons,
			'remaining_capstone_count'     => (int) $remaining_capstones,
			'total_modules'                => (int) $total_modules,
			'completed_modules'            => (int) $completed_modules,
			'remaining_module_count'       => (int) $remaining_modules,
			'is_last_lesson_in_sequence'   => (bool) $is_last_lesson_in_sequence,
			'has_completed_all_capstones'  => (bool) $has_completed_all,
			'should_show_workbook_cta'     => (bool) ( $is_last_lesson_in_sequence && $has_completed_all ),
			'should_show_incomplete_cta'   => (bool) ( $is_last_lesson_in_sequence && ! $has_completed_all ),
			'workbook_url'                 => self::build_workbook_url( $course_id ),
		);
	}

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	public static function init() {
		register_rest_route(
			'lms/v1',
			'/lesson/capstone-submission',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( 'Rest_Lxp_Capstone_Submission', 'upsert_submission' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Rest_Lxp_Capstone_Submission', 'get_submission' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'lms/v1',
			'/course/capstone-submissions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Rest_Lxp_Capstone_Submission', 'get_course_submissions' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Callbacks
	// -------------------------------------------------------------------------

	/**
	 * Upsert the logged-in user's capstone submission for a lesson.
	 *
	 * @param  WP_REST_Request $request  Required: lesson_id (int), response (string).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function upsert_submission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				'You must be logged in to submit a capstone response.',
				array( 'status' => 401 )
			);
		}

		$lesson_id = absint( $request->get_param( 'lesson_id' ) );
		$response  = $request->get_param( 'response' );

		if ( $lesson_id <= 0 ) {
			return new WP_Error( 'invalid_lesson_id', 'A valid lesson_id is required.', array( 'status' => 400 ) );
		}

		// Derive course_id server-side from the lesson association.
		$section_repo = new TL_LearnPress_Section_Repository();
		$course_id    = $section_repo->get_course_id_by_item_id( $lesson_id );
		if ( $course_id <= 0 ) {
			return new WP_Error( 'invalid_course_id', 'Could not resolve a course for this lesson.', array( 'status' => 400 ) );
		}
		if ( null === $response || '' === trim( $response ) ) {
			return new WP_Error( 'invalid_response', 'A non-empty response is required.', array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $lesson_id ) && ! is_user_logged_in() ) {
			return new WP_Error( 'forbidden', 'You do not have permission to submit for this lesson.', array( 'status' => 403 ) );
		}

		$user_id = get_current_user_id();
		$row_id  = self::repo()->upsert( $lesson_id, $course_id, $user_id, $response );

		if ( false === $row_id ) {
			return new WP_Error( 'db_error', 'Failed to save capstone submission.', array( 'status' => 500 ) );
		}

		$completion = self::get_completion_state( $course_id, $lesson_id, $user_id );

		return rest_ensure_response( array_merge( array( 'id' => $row_id, 'saved' => true ), $completion ) );
	}

	/**
	 * Fetch the logged-in user's capstone submission for a single lesson.
	 *
	 * @param  WP_REST_Request $request  Required: lesson_id (int).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_submission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				'You must be logged in to view capstone submissions.',
				array( 'status' => 401 )
			);
		}

		$lesson_id = absint( $request->get_param( 'lesson_id' ) );

		if ( $lesson_id <= 0 ) {
			return new WP_Error( 'invalid_lesson_id', 'A valid lesson_id is required.', array( 'status' => 400 ) );
		}

		$user_id    = get_current_user_id();
		$submission = self::repo()->get_by_lesson_user( $lesson_id, $user_id );

		// Resolve last-lesson flag and workbook URL so the frontend can show
		// the Preview Workbook button on the last lesson of the course.
		$is_last      = false;
		$workbook_url = '';
		$section_repo = new TL_LearnPress_Section_Repository();
		$course_id    = $section_repo->get_course_id_by_item_id( $lesson_id );
		if ( $course_id > 0 ) {
			$completion   = self::get_completion_state( $course_id, $lesson_id, $user_id );
			$is_last      = (bool) $completion['is_last_lesson_in_sequence'];
			$workbook_url = (string) $completion['workbook_url'];
		}

		if ( ! $submission ) {
			return rest_ensure_response( array(
				'response'                   => null,
				'is_last_lesson_in_sequence' => $is_last,
				'workbook_url'               => $workbook_url,
			) );
		}

		return rest_ensure_response( array(
			'id'                         => (int) $submission->id,
			'response'                   => $submission->response,
			'submitted_at'               => $submission->submitted_at,
			'updated_at'                 => $submission->updated_at,
			'is_last_lesson_in_sequence' => $is_last,
			'workbook_url'               => $workbook_url,
		) );
	}

	/**
	 * Fetch all lessons in a course with the current user's capstone response (or null).
	 *
	 * Powers the capstone journal page.
	 *
	 * @param  WP_REST_Request $request  Required: course_id (int).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_course_submissions( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				'You must be logged in to view course submissions.',
				array( 'status' => 401 )
			);
		}

		$course_id = absint( $request->get_param( 'course_id' ) );

		if ( $course_id <= 0 ) {
			return new WP_Error( 'invalid_course_id', 'A valid course_id is required.', array( 'status' => 400 ) );
		}

		$user_id = get_current_user_id();
		$rows    = self::repo()->get_course_summary( $course_id, $user_id );

		$data = array();
		foreach ( $rows as $row ) {
			// Build the LP4 lesson URL: /{course-slug}/lessons/{lesson-slug}/
			$course_post      = get_post( (int) $course_id );
			$course_slug      = $course_post ? $course_post->post_name : '';
			$lesson_url       = $course_slug
				? home_url( '/' . $course_slug . '/lessons/' . $row->lesson_slug . '/' )
				: get_permalink( (int) $row->lesson_id );

			$data[] = array(
				'lesson_id'     => (int) $row->lesson_id,
				'lesson_title'  => $row->lesson_title,
				'lesson_url'    => $lesson_url,
				'response'      => isset( $row->response ) ? $row->response : null,
				'submitted_at'  => isset( $row->submitted_at ) ? $row->submitted_at : null,
				'updated_at'    => isset( $row->updated_at ) ? $row->updated_at : null,
			);
		}

		return rest_ensure_response( $data );
	}
}
