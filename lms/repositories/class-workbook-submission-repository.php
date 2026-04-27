<?php

/**
 * Repository for workbook submission persistence.
 *
 * Handles all DB access for the {prefix}lxp_workbook_submissions table.
 * One row per (lesson_id, user_id) pair — resubmission is an upsert.
 */
class TL_Workbook_Submission_Repository {

	/** @var wpdb */
	private $wpdb;

	/** @var string Fully-qualified table name. */
	private $table;

	/**
	 * @param wpdb|null $wpdb_instance  Inject a custom wpdb for testing; defaults to global.
	 */
	public function __construct( $wpdb_instance = null ) {
		global $wpdb;
		$this->wpdb  = $wpdb_instance ?? $wpdb;
		$this->table = $this->wpdb->prefix . 'lxp_workbook_submissions';
	}

	// -------------------------------------------------------------------------
	// Write
	// -------------------------------------------------------------------------

	/**
	 * Insert or update a workbook submission.
	 *
	 * @param  int   $lesson_id   Lesson post ID.
	 * @param  int   $course_id   Course post ID.
	 * @param  int   $user_id     WordPress user ID.
	 * @param  array $fields      Associative array of field label => answer text.
	 * @return int|false  Row ID on success, false on failure.
	 */
	public function upsert( $lesson_id, $course_id, $user_id, array $fields ) {
		$now        = current_time( 'mysql' );
		$fields_json = wp_json_encode( $fields );

		$existing = $this->get_by_lesson_user( $lesson_id, $user_id );

		if ( $existing ) {
			$updated = $this->wpdb->update(
				$this->table,
				array(
					'fields'     => $fields_json,
					'updated_at' => $now,
				),
				array(
					'lesson_id' => absint( $lesson_id ),
					'user_id'   => absint( $user_id ),
				),
				array( '%s', '%s' ),
				array( '%d', '%d' )
			);
			return ( false !== $updated ) ? (int) $existing->id : false;
		}

		$inserted = $this->wpdb->insert(
			$this->table,
			array(
				'lesson_id'    => absint( $lesson_id ),
				'course_id'    => absint( $course_id ),
				'user_id'      => absint( $user_id ),
				'fields'       => $fields_json,
				'submitted_at' => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		return ( false !== $inserted ) ? (int) $this->wpdb->insert_id : false;
	}

	// -------------------------------------------------------------------------
	// Read
	// -------------------------------------------------------------------------

	/**
	 * Fetch a single submission by lesson and user.
	 *
	 * @param  int $lesson_id
	 * @param  int $user_id
	 * @return object|null
	 */
	public function get_by_lesson_user( $lesson_id, $user_id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE lesson_id = %d AND user_id = %d LIMIT 1",
				absint( $lesson_id ),
				absint( $user_id )
			)
		);
	}

	/**
	 * Fetch a single submission by primary key.
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function get_by_id( $id ) {
		return $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
				absint( $id )
			)
		);
	}

	/**
	 * Fetch a paginated list of submissions with optional filters.
	 *
	 * @param  array $filters   Supported keys: course_id (int), lesson_id (int), user_id (int).
	 * @param  int   $per_page  Rows per page.
	 * @param  int   $offset    Row offset.
	 * @return array
	 */
	public function get_all( array $filters = array(), $per_page = 20, $offset = 0 ) {
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $filters['course_id'] ) ) {
			$where[]  = 'ws.course_id = %d';
			$values[] = absint( $filters['course_id'] );
		}
		if ( ! empty( $filters['lesson_id'] ) ) {
			$where[]  = 'ws.lesson_id = %d';
			$values[] = absint( $filters['lesson_id'] );
		}
		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'ws.user_id = %d';
			$values[] = absint( $filters['user_id'] );
		}

		$where_sql = implode( ' AND ', $where );
		$values[]  = absint( $per_page );
		$values[]  = absint( $offset );

		$sql = "SELECT ws.*, u.display_name, u.user_email,
		               p_lesson.post_title AS lesson_title,
		               p_course.post_title AS course_title
		        FROM {$this->table} ws
		        LEFT JOIN {$this->wpdb->users}       u        ON u.ID            = ws.user_id
		        LEFT JOIN {$this->wpdb->posts}        p_lesson ON p_lesson.ID    = ws.lesson_id
		        LEFT JOIN {$this->wpdb->posts}        p_course ON p_course.ID    = ws.course_id
		        WHERE {$where_sql}
		        ORDER BY ws.updated_at DESC
		        LIMIT %d OFFSET %d";

		if ( ! empty( $values ) ) {
			return $this->wpdb->get_results(
				$this->wpdb->prepare( $sql, $values )
			);
		}

		return $this->wpdb->get_results( $sql );
	}

	/**
	 * Count submissions matching the given filters.
	 *
	 * @param  array $filters  Same supported keys as get_all().
	 * @return int
	 */
	public function count( array $filters = array() ) {
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $filters['course_id'] ) ) {
			$where[]  = 'course_id = %d';
			$values[] = absint( $filters['course_id'] );
		}
		if ( ! empty( $filters['lesson_id'] ) ) {
			$where[]  = 'lesson_id = %d';
			$values[] = absint( $filters['lesson_id'] );
		}
		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = absint( $filters['user_id'] );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}";

		if ( ! empty( $values ) ) {
			return (int) $this->wpdb->get_var(
				$this->wpdb->prepare( $sql, $values )
			);
		}

		return (int) $this->wpdb->get_var( $sql );
	}
}
