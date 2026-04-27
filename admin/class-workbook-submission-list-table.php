<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table subclass for the Workbook Submissions admin screen.
 */
class TL_Workbook_Submission_List_Table extends WP_List_Table {

	/** @var TL_Workbook_Submission_Repository */
	private $repo;

	/** @var array Active filter values (course_id, lesson_id, user_id). */
	private $filters = array();

	public function __construct( TL_Workbook_Submission_Repository $repo ) {
		parent::__construct(
			array(
				'singular' => 'workbook_submission',
				'plural'   => 'workbook_submissions',
				'ajax'     => false,
			)
		);
		$this->repo = $repo;
	}

	// -------------------------------------------------------------------------
	// Columns
	// -------------------------------------------------------------------------

	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'student'      => __( 'Student', 'tiny-lxp-platform' ),
			'lesson_title' => __( 'Lesson', 'tiny-lxp-platform' ),
			'course_title' => __( 'Course', 'tiny-lxp-platform' ),
			'submitted_at' => __( 'Submitted', 'tiny-lxp-platform' ),
			'updated_at'   => __( 'Last Updated', 'tiny-lxp-platform' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'student'      => array( 'student', false ),
			'submitted_at' => array( 'submitted_at', true ),
			'updated_at'   => array( 'updated_at', false ),
		);
	}

	// -------------------------------------------------------------------------
	// Row rendering
	// -------------------------------------------------------------------------

	protected function column_cb( $item ) {
		return '<input type="checkbox" name="submission_ids[]" value="' . absint( $item->id ) . '" />';
	}

	protected function column_student( $item ) {
		$name  = esc_html( $item->display_name ?: $item->user_email ?: '—' );
		$email = esc_html( $item->user_email ?: '' );

		$detail_url = add_query_arg(
			array(
				'page' => 'curriki-learn-workbook-submissions',
				'view' => 'detail',
				'id'   => absint( $item->id ),
			),
			admin_url( 'admin.php' )
		);

		return '<strong><a href="' . esc_url( $detail_url ) . '">' . $name . '</a></strong>'
			. ( $email ? '<br><span style="color:#666;font-size:0.87em;">' . $email . '</span>' : '' );
	}

	protected function column_lesson_title( $item ) {
		return esc_html( $item->lesson_title ?: '—' );
	}

	protected function column_course_title( $item ) {
		return esc_html( $item->course_title ?: '—' );
	}

	protected function column_submitted_at( $item ) {
		return esc_html( $item->submitted_at ?: '—' );
	}

	protected function column_updated_at( $item ) {
		return esc_html( $item->updated_at ?: '—' );
	}

	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '—';
	}

	// -------------------------------------------------------------------------
	// Data loading
	// -------------------------------------------------------------------------

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		// Gather filters from query string (sanitize immediately).
		$this->filters = array(
			'course_id' => absint( isset( $_GET['filter_course_id'] ) ? $_GET['filter_course_id'] : 0 ),
			'lesson_id' => absint( isset( $_GET['filter_lesson_id'] ) ? $_GET['filter_lesson_id'] : 0 ),
			'user_id'   => absint( isset( $_GET['filter_user_id'] ) ? $_GET['filter_user_id'] : 0 ),
		);
		// Remove zero values (no filter).
		$this->filters = array_filter( $this->filters );

		$per_page     = $this->get_items_per_page( 'workbook_submissions_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$total_items = $this->repo->count( $this->filters );
		$this->items = $this->repo->get_all( $this->filters, $per_page, $offset );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Filter bar
	// -------------------------------------------------------------------------

	/**
	 * Render course/lesson/user filter dropdowns above the table.
	 * Called from the admin partial before display().
	 */
	public function render_filter_bar() {
		$current_url = admin_url( 'admin.php?page=curriki-learn-workbook-submissions' );

		$filter_course = isset( $_GET['filter_course_id'] ) ? absint( $_GET['filter_course_id'] ) : 0;
		$filter_lesson = isset( $_GET['filter_lesson_id'] ) ? absint( $_GET['filter_lesson_id'] ) : 0;
		$filter_user   = isset( $_GET['filter_user_id'] ) ? absint( $_GET['filter_user_id'] ) : 0;

		// Build course dropdown from all published lp_course posts.
		$courses = get_posts( array( 'post_type' => 'lp_course', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		// Build lesson dropdown from all published lp_lesson posts.
		$lessons = get_posts( array( 'post_type' => 'lp_lesson', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		// Build user dropdown (only users who have submitted at least once).
		$users = get_users( array( 'orderby' => 'display_name' ) );

		echo '<form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">';
		echo '<input type="hidden" name="page" value="curriki-learn-workbook-submissions" />';

		// Course filter.
		echo '<select name="filter_course_id"><option value="">' . esc_html__( 'All Courses', 'tiny-lxp-platform' ) . '</option>';
		foreach ( $courses as $c ) {
			printf(
				'<option value="%d"%s>%s</option>',
				absint( $c->ID ),
				selected( $filter_course, $c->ID, false ),
				esc_html( $c->post_title )
			);
		}
		echo '</select>';

		// Lesson filter.
		echo '<select name="filter_lesson_id"><option value="">' . esc_html__( 'All Lessons', 'tiny-lxp-platform' ) . '</option>';
		foreach ( $lessons as $l ) {
			printf(
				'<option value="%d"%s>%s</option>',
				absint( $l->ID ),
				selected( $filter_lesson, $l->ID, false ),
				esc_html( $l->post_title )
			);
		}
		echo '</select>';

		// User filter.
		echo '<select name="filter_user_id"><option value="">' . esc_html__( 'All Users', 'tiny-lxp-platform' ) . '</option>';
		foreach ( $users as $u ) {
			printf(
				'<option value="%d"%s>%s</option>',
				absint( $u->ID ),
				selected( $filter_user, $u->ID, false ),
				esc_html( $u->display_name )
			);
		}
		echo '</select>';

		echo '<button type="submit" class="button">' . esc_html__( 'Filter', 'tiny-lxp-platform' ) . '</button>';

		if ( $filter_course || $filter_lesson || $filter_user ) {
			echo ' <a href="' . esc_url( $current_url ) . '" class="button">' . esc_html__( 'Reset', 'tiny-lxp-platform' ) . '</a>';
		}

		echo '</form>';
	}
}
