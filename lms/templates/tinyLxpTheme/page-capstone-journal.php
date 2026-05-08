<?php
/**
 * Template: Learner Workbook
 *
 * Displays all lessons in a course with the current student's reflection entries.
 * Accessible to lxp_student and lxp_teacher roles.
 *
 * Loaded by tinyLxp_page_templates() for capstone-journal, learner-workbook,
 * and dynamic /courses/{course-slug}/learner-workbook routes.
 * Requires: TL_Capstone_Submission_Repository
 */

if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '../../repositories/class-capstone-submission-repository.php';

$user_id   = get_current_user_id();
$course_id = absint( isset( $_GET['course_id'] ) ? $_GET['course_id'] : 0 );
$course_slug = isset( $_GET['course_slug'] ) ? sanitize_title( wp_unslash( $_GET['course_slug'] ) ) : '';

// -------------------------------------------------------------------------
// Build enrolled courses list for the selector.
// We query lp_course posts the user is enrolled in.
// -------------------------------------------------------------------------
$enrolled_courses = array();

// LP4 canonical API: LP_User_Items_DB::get_user_courses() with LP_User_Items_Filter.
// LP_User has no bulk get_course_ids() method — only per-course checks exist on it.
if ( class_exists( 'LP_User_Items_DB' ) && class_exists( 'LP_User_Items_Filter' ) ) {
	$lp_user_items_db = LP_User_Items_DB::getInstance();
	$filter           = new LP_User_Items_Filter();
	$filter->user_id  = $user_id;
	$filter->statues  = array( LP_COURSE_ENROLLED, LP_COURSE_FINISHED );
	$total_rows       = 0;
	$results          = $lp_user_items_db->get_user_courses( $filter, $total_rows );
	$enrolled_ids     = is_array( $results ) ? wp_list_pluck( $results, 'item_id' ) : array();

	if ( ! empty( $enrolled_ids ) ) {
		$posts = get_posts( array(
			'post_type'      => 'lp_course',
			'post__in'       => array_map( 'absint', $enrolled_ids ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		foreach ( $posts as $p ) {
			$enrolled_courses[] = array(
				'id'    => $p->ID,
				'title' => $p->post_title,
				'slug'  => $p->post_name,
			);
		}
	}
}

// Fallback: admins / teachers can view any course.
if ( empty( $enrolled_courses ) && current_user_can( 'edit_posts' ) && $course_id > 0 ) {
	$p = get_post( $course_id );
	if ( $p ) {
		$enrolled_courses[] = array(
			'id'    => $p->ID,
			'title' => $p->post_title,
			'slug'  => $p->post_name,
		);
	}
}

if ( $course_id <= 0 && '' !== $course_slug ) {
	$course_post_by_slug = get_page_by_path( $course_slug, OBJECT, 'lp_course' );
	if ( $course_post_by_slug ) {
		$course_id = (int) $course_post_by_slug->ID;
	}
}

// Default to first enrolled course if none specified.
if ( $course_id <= 0 && ! empty( $enrolled_courses ) ) {
	$course_id = (int) $enrolled_courses[0]['id'];
}

// -------------------------------------------------------------------------
// Fetch lessons for the selected course.
// -------------------------------------------------------------------------
$lessons = array();
if ( $course_id > 0 ) {
	$repo    = new TL_Capstone_Submission_Repository();
	$lessons = $repo->get_course_summary( $course_id, $user_id );
}

$course_post  = $course_id > 0 ? get_post( $course_id ) : null;
$course_title = $course_post ? esc_html( $course_post->post_title ) : 'Course';

// -------------------------------------------------------------------------
// Aggregate totals and group lessons by module/section.
// -------------------------------------------------------------------------
$total_lessons     = count( $lessons );
$completed_lessons = 0;
$remaining_lessons = 0;
$continue_url      = '';

// modules_map: ordered array keyed by module_id, each entry holds module
// metadata and the ordered list of its lessons.
$modules_map = array();

foreach ( $lessons as $lesson ) {
	$is_completed = ! empty( $lesson->response );
	if ( $is_completed ) {
		$completed_lessons++;
	} else {
		$remaining_lessons++;
		if ( '' === $continue_url ) {
			$continue_url = $course_post
				? home_url( '/courses/' . $course_post->post_name . '/lessons/' . $lesson->lesson_slug . '/' )
				: get_permalink( (int) $lesson->lesson_id );
		}
	}

	$mod_id = isset( $lesson->module_id ) ? (int) $lesson->module_id : 0;
	if ( ! isset( $modules_map[ $mod_id ] ) ) {
		$modules_map[ $mod_id ] = array(
			'id'      => $mod_id,
			'name'    => ( isset( $lesson->module_name ) && '' !== $lesson->module_name )
			             ? $lesson->module_name
			             : 'Lessons',
			'lessons' => array(),
		);
	}
	$modules_map[ $mod_id ]['lessons'][] = $lesson;
}

$total_modules     = count( $modules_map );
$module_index      = 0; // used for module numbering in the UI

get_header();
?>

<style>
.lxp-journal-wrap {
	max-width: 920px;
	margin: 40px auto;
	padding: 0 20px 60px;
	font-family: inherit;
}
.lxp-journal-title {
	font-size: 1.65rem;
	font-weight: 700;
	color: var(--lp-secondary-color, #442e66);
	margin-bottom: 6px;
}
.lxp-journal-subtitle {
	font-size: 1rem;
	color: #666;
	margin-bottom: 28px;
}
.lxp-course-selector {
	margin-bottom: 30px;
}
.lxp-course-selector label {
	font-weight: 600;
	color: var(--lp-secondary-color, #442e66);
	margin-right: 10px;
}
.lxp-course-selector select {
	padding: 8px 14px;
	border: 1px solid rgba(68,46,102,.3);
	border-radius: 8px;
	font-size: 0.97rem;
	cursor: pointer;
	min-width: 260px;
}
.lxp-lesson-row {
	background: #fff;
	border-radius: 14px;
	box-shadow: 0 10px 30px -10px rgba(68,46,102,.12);
	margin-bottom: 14px;
	overflow: hidden;
	border: 1px solid rgba(68,46,102,.08);
}
.lxp-lesson-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 16px 20px;
	cursor: pointer;
	gap: 12px;
}
.lxp-lesson-header:hover {
	background: rgba(68,46,102,.03);
}
.lxp-lesson-number {
	width: 34px;
	height: 34px;
	border-radius: 50%;
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.82rem;
	font-weight: 700;
	flex-shrink: 0;
}
.lxp-lesson-info {
	flex: 1;
}
.lxp-lesson-name {
	font-weight: 600;
	color: var(--lp-secondary-color, #442e66);
	font-size: 1rem;
}
.lxp-lesson-meta {
	font-size: 0.82rem;
	color: #888;
	margin-top: 2px;
}
.lxp-lesson-status {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-shrink: 0;
}
.lxp-badge {
	padding: 4px 12px;
	border-radius: 20px;
	font-size: 0.78rem;
	font-weight: 700;
	white-space: nowrap;
}
.lxp-badge-submitted {
	background: rgba(68,46,102,.1);
	color: var(--lp-secondary-color, #442e66);
}
.lxp-badge-pending {
	background: rgba(255,182,6,.15);
	color: #8a6000;
}
.lxp-go-lesson {
	font-size: 0.82rem;
	color: var(--lp-secondary-color, #442e66);
	text-decoration: none;
	font-weight: 600;
	border: 1px solid rgba(68,46,102,.3);
	padding: 4px 10px;
	border-radius: 6px;
	white-space: nowrap;
}
.lxp-go-lesson:hover {
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	text-decoration: none;
}
.lxp-chevron {
	color: #aaa;
	transition: transform .2s;
	font-size: 1rem;
	line-height: 1;
}
.lxp-lesson-row.is-open .lxp-chevron {
	transform: rotate(180deg);
}
.lxp-response-body {
	display: none;
	padding: 0 20px 20px;
	border-top: 1px solid rgba(68,46,102,.07);
}
.lxp-lesson-row.is-open .lxp-response-body {
	display: block;
}
.lxp-response-label {
	font-size: 0.82rem;
	font-weight: 700;
	color: #888;
	text-transform: uppercase;
	letter-spacing: .04em;
	margin: 14px 0 8px;
}
.lxp-response-text {
	background: rgba(68,46,102,.04);
	border-radius: 10px;
	padding: 14px 16px;
	font-size: 0.95rem;
	color: #333;
	white-space: pre-wrap;
	line-height: 1.65;
}
.lxp-empty-state {
	text-align: center;
	padding: 60px 20px;
	color: #888;
}
.lxp-workbook-notice {
	margin: 0 0 20px;
	padding: 14px 16px;
	border-radius: 12px;
	border: 1px solid rgba(68,46,102,.18);
	background: rgba(68,46,102,.04);
	color: #2f2f2f;
	font-size: .95rem;
	line-height: 1.5;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	flex-wrap: wrap;
}
.lxp-workbook-notice a {
	display: inline-block;
	padding: 9px 14px;
	border-radius: 8px;
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	text-decoration: none;
	font-weight: 600;
	font-size: .9rem;
}
.lxp-workbook-notice-success {
	border-color: rgba(30,130,76,.24);
	background: rgba(30,130,76,.08);
}
.lxp-workbook-notice-success strong {
	color: #1e824c;
}
/* Module / section grouping */
.lxp-module-block {
	margin-bottom: 32px;
}
.lxp-module-header {
	display: flex;
	align-items: center;
	gap: 14px;
	margin-bottom: 12px;
}
.lxp-module-number {
	width: 34px;
	height: 34px;
	border-radius: 8px;
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 0.78rem;
	font-weight: 700;
	flex-shrink: 0;
	letter-spacing: .03em;
}
.lxp-module-title {
	font-size: 1.05rem;
	font-weight: 700;
	color: var(--lp-secondary-color, #442e66);
}
.lxp-module-progress {
	font-size: 0.82rem;
	color: #888;
	margin-left: auto;
	white-space: nowrap;
}
.lxp-module-lessons {
	padding-left: 0;
}
</style>

<div class="lxp-journal-wrap">
	<h1 class="lxp-journal-title">Workbook<?php echo $course_title ? ' &mdash; ' . $course_title : ''; ?></h1>

	<?php if ( $total_lessons > 0 && $remaining_lessons > 0 ) : ?>
	<div class="lxp-workbook-notice">
		<span>
			You have completed <?php echo (int) $completed_lessons; ?> of <?php echo (int) $total_lessons; ?> lesson capstone<?php echo 1 === $total_lessons ? '' : 's'; ?>.
			Complete the remaining <?php echo (int) $remaining_lessons; ?> to unlock your full workbook journey.
		</span>
		<?php if ( ! empty( $continue_url ) ) : ?>
		<a href="<?php echo esc_url( $continue_url ); ?>">Continue Course</a>
		<?php endif; ?>
	</div>
	<?php elseif ( $total_lessons > 0 ) : ?>
	<div class="lxp-workbook-notice lxp-workbook-notice-success">
		<span>
			<strong>Workbook complete.</strong>
			You finished all <?php echo (int) $total_lessons; ?> lesson capstone<?php echo 1 === $total_lessons ? '' : 's'; ?> in this course.
		</span>
	</div>
	<?php endif; ?>

	<?php if ( $course_id > 0 ) : ?>
	<p class="lxp-journal-subtitle" style="margin-bottom:20px;">
		<?php echo (int) $total_modules; ?> module<?php echo 1 !== $total_modules ? 's' : ''; ?>,
		<?php echo (int) $total_lessons; ?> lesson<?php echo 1 !== $total_lessons ? 's' : ''; ?>
	</p>
	<?php endif; ?>

	<?php if ( empty( $lessons ) ) : ?>
	<div class="lxp-empty-state">
		<p><?php $course_id > 0 ? print( 'No lessons found for this course.' ) : print( 'Select a course to view your workbook.' ); ?></p>
	</div>
	<?php else : ?>

	<?php
	$global_idx = 0;
	foreach ( $modules_map as $module ) :
		$module_index++;
		$mod_lessons    = $module['lessons'];
		$mod_total      = count( $mod_lessons );
		$mod_completed  = 0;
		foreach ( $mod_lessons as $ml ) {
			if ( ! empty( $ml->response ) ) $mod_completed++;
		}
	?>
	<div class="lxp-module-block">
		<div class="lxp-module-header">
			<div class="lxp-module-number">M<?php echo $module_index; ?></div>
			<div class="lxp-module-title"><?php echo esc_html( $module['name'] ); ?></div>
			<div class="lxp-module-progress"><?php echo $mod_completed; ?> / <?php echo $mod_total; ?> completed</div>
		</div>
		<div class="lxp-module-lessons">
		<?php foreach ( $mod_lessons as $lesson ) :
			$global_idx++;
			$submitted  = ! empty( $lesson->response );
			$lesson_url = $course_post
				? home_url( '/courses/' . $course_post->post_name . '/lessons/' . $lesson->lesson_slug . '/' )
				: get_permalink( (int) $lesson->lesson_id );
		?>
		<div class="lxp-lesson-row<?php echo $submitted ? ' is-open' : ''; ?>" id="lxp-row-<?php echo $global_idx; ?>">
			<div class="lxp-lesson-header" onclick="lxpToggleRow(this.closest('.lxp-lesson-row'))">
				<div class="lxp-lesson-number"><?php echo $global_idx; ?></div>
				<div class="lxp-lesson-info">
					<div class="lxp-lesson-name"><?php echo esc_html( $lesson->lesson_title ); ?></div>
					<?php if ( $submitted ) : ?>
					<div class="lxp-lesson-meta">
						Submitted <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $lesson->submitted_at ) ) ); ?>
						<?php if ( $lesson->updated_at !== $lesson->submitted_at ) : ?>
						&middot; Updated <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $lesson->updated_at ) ) ); ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
				<div class="lxp-lesson-status">
					<?php if ( $submitted ) : ?>
					<span class="lxp-badge lxp-badge-submitted">&#10003; Completed</span>
					<?php else : ?>
					<span class="lxp-badge lxp-badge-pending">Not completed</span>
					<?php endif; ?>
					<a href="<?php echo esc_url( $lesson_url ); ?>" class="lxp-go-lesson" onclick="event.stopPropagation()">Go to Lesson</a>
					<span class="lxp-chevron">&#9660;</span>
				</div>
			</div>
			<?php if ( $submitted ) : ?>
			<div class="lxp-response-body">
				<div class="lxp-response-label">Your Workbook Entry</div>
				<div class="lxp-response-text"><?php echo esc_html( $lesson->response ); ?></div>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
		</div><!-- .lxp-module-lessons -->
	</div><!-- .lxp-module-block -->
	<?php endforeach; ?>
	<?php endif; ?>
</div>

<script>
function lxpToggleRow(row) {
	if ( row.classList.contains('is-open') ) {
		row.classList.remove('is-open');
	} else {
		row.classList.add('is-open');
	}
}
</script>

<?php get_footer(); ?>
