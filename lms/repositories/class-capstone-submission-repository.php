<?php

/**
 * Repository for capstone submission persistence.
 *
 * Handles all DB access for the {prefix}lxp_capstone_submissions table.
 * One row per (lesson_id, user_id) pair — resubmission is an upsert.
 */
class TL_Capstone_Submission_Repository {

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
		$this->table = $this->wpdb->prefix . 'lxp_capstone_submissions';
	}

	// -------------------------------------------------------------------------
	// Write
	// -------------------------------------------------------------------------

	/**
	 * Insert or update a capstone submission.
	 *
	 * @param  int    $lesson_id  Lesson post ID.
	 * @param  int    $course_id  Course post ID.
	 * @param  int    $user_id    WordPress user ID.
	 * @param  string $response   Student's capstone response text.
	 * @return int|false  Row ID on success, false on failure.
	 */
	public function upsert( $lesson_id, $course_id, $user_id, $response ) {
		$now = current_time( 'mysql' );

		$existing = $this->get_by_lesson_user( $lesson_id, $user_id );

		if ( $existing ) {
			$updated = $this->wpdb->update(
				$this->table,
				array(
					'response'   => sanitize_textarea_field( $response ),
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
				'response'     => sanitize_textarea_field( $response ),
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
	 * Return ALL lessons in a course joined with any capstone submission for a specific user.
	 *
	 * Used by the journal page to show full course progress.
	 *
	 * @param  int $course_id  Course post ID.
	 * @param  int $user_id    WordPress user ID.
	 * @return array  Each row has: lesson_id, lesson_title, lesson_url, response (or null),
	 *                submitted_at (or null), updated_at (or null).
	 */
	public function get_course_summary( $course_id, $user_id ) {
		global $wpdb;

		$lessons_table  = $wpdb->prefix . 'learnpress_section_items';
		$sections_table = $wpdb->prefix . 'learnpress_sections';

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT
				    p.ID          AS lesson_id,
				    p.post_title  AS lesson_title,
				    p.post_name   AS lesson_slug,
				    cs.id         AS submission_id,
				    cs.response,
				    cs.submitted_at,
				    cs.updated_at
				FROM {$lessons_table} li
				INNER JOIN {$sections_table} s   ON s.section_id   = li.section_id
				INNER JOIN {$wpdb->posts}     p   ON p.ID           = li.item_id
				LEFT  JOIN {$this->table}     cs  ON cs.lesson_id   = li.item_id
				                                  AND cs.user_id    = %d
				WHERE s.section_course_id = %d
				  AND li.item_type = 'lp_lesson'
				  AND p.post_status = 'publish'
				ORDER BY s.section_order ASC, li.item_order ASC",
				absint( $user_id ),
				absint( $course_id )
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
			$where[]  = 'cs.course_id = %d';
			$values[] = absint( $filters['course_id'] );
		}
		if ( ! empty( $filters['lesson_id'] ) ) {
			$where[]  = 'cs.lesson_id = %d';
			$values[] = absint( $filters['lesson_id'] );
		}
		if ( ! empty( $filters['user_id'] ) ) {
			$where[]  = 'cs.user_id = %d';
			$values[] = absint( $filters['user_id'] );
		}

		$where_sql = implode( ' AND ', $where );
		$values[]  = absint( $per_page );
		$values[]  = absint( $offset );

		$sql = "SELECT cs.*, u.display_name, u.user_email,
		               p_lesson.post_title AS lesson_title,
		               p_course.post_title AS course_title
		        FROM {$this->table} cs
		        LEFT JOIN {$this->wpdb->users} u         ON u.ID           = cs.user_id
		        LEFT JOIN {$this->wpdb->posts} p_lesson  ON p_lesson.ID    = cs.lesson_id
		        LEFT JOIN {$this->wpdb->posts} p_course  ON p_course.ID    = cs.course_id
		        WHERE {$where_sql}
		        ORDER BY cs.updated_at DESC
		        LIMIT %d OFFSET %d";

		return $this->wpdb->get_results(
			$this->wpdb->prepare( $sql, $values )
		);
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
