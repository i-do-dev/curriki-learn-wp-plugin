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

if ( function_exists( 'learn_press_get_user' ) ) {
	$lp_user = learn_press_get_user( $user_id );
	if ( $lp_user ) {
		$course_ids = $lp_user->get_course_ids();
		if ( ! empty( $course_ids ) ) {
			$posts = get_posts( array(
				'post_type'      => 'lp_course',
				'post__in'       => $course_ids,
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
</style>

<div class="lxp-journal-wrap">
	<h1 class="lxp-journal-title">Learner Workbook</h1>
	<p class="lxp-journal-subtitle">Review your lesson reflections for this course in one place.</p>

	<?php if ( count( $enrolled_courses ) > 1 ) : ?>
	<div class="lxp-course-selector">
		<label for="lxp-course-select">Course:</label>
		<select id="lxp-course-select" onchange="if(this.value){window.location.href=this.value;}">
			<?php foreach ( $enrolled_courses as $ec ) : ?>
			<?php
			$course_url = ! empty( $ec['slug'] )
				? home_url( '/courses/' . $ec['slug'] . '/learner-workbook/' )
				: add_query_arg( 'course_id', absint( $ec['id'] ), home_url( '/capstone-journal/' ) );
			?>
			<option value="<?php echo esc_url( $course_url ); ?>"<?php selected( absint( $ec['id'] ), $course_id ); ?>>
				<?php echo esc_html( $ec['title'] ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php endif; ?>

	<?php if ( $course_id > 0 ) : ?>
	<p class="lxp-journal-subtitle" style="margin-bottom:20px;">
		<strong><?php echo $course_title; ?></strong>
		&mdash; <?php echo count( $lessons ); ?> lesson<?php echo count( $lessons ) !== 1 ? 's' : ''; ?>
	</p>
	<?php endif; ?>

	<?php if ( empty( $lessons ) ) : ?>
	<div class="lxp-empty-state">
		<p><?php $course_id > 0 ? print( 'No lessons found for this course.' ) : print( 'Select a course to view your workbook.' ); ?></p>
	</div>
	<?php else : ?>

	<?php $idx = 0; foreach ( $lessons as $lesson ) :
		$idx++;
		$submitted  = ! empty( $lesson->response );
		$lesson_url = '';
		if ( $course_post ) {
			$lesson_url = home_url( '/' . $course_post->post_name . '/lessons/' . $lesson->lesson_slug . '/' );
		} else {
			$lesson_url = get_permalink( (int) $lesson->lesson_id );
		}
	?>
	<div class="lxp-lesson-row<?php echo $submitted ? ' is-open' : ''; ?>" id="lxp-row-<?php echo $idx; ?>">
		<div class="lxp-lesson-header" onclick="lxpToggleRow(this.closest('.lxp-lesson-row'))">
			<div class="lxp-lesson-number"><?php echo $idx; ?></div>
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
