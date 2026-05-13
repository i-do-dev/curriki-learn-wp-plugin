<?php
/**
 * Template: Learner Workbook
 *
 * Displays all lessons in a course with the current user's reflection entries.
 * Admins can also view a selected user's workbook via the view_user_id query arg.
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

$viewer_user_id   = get_current_user_id();
$requested_user_id = absint( isset( $_GET['view_user_id'] ) ? $_GET['view_user_id'] : 0 );
$target_user      = get_userdata( $viewer_user_id );
$is_admin_view    = false;

if ( $requested_user_id > 0 && current_user_can( 'manage_options' ) ) {
	$requested_user = get_userdata( $requested_user_id );
	if ( $requested_user ) {
		$target_user   = $requested_user;
		$is_admin_view = $requested_user_id !== $viewer_user_id;
	}
}

$user_id     = $target_user ? (int) $target_user->ID : $viewer_user_id;
$course_id   = absint( isset( $_GET['course_id'] ) ? $_GET['course_id'] : 0 );
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
	$lessons = $repo->get_course_summary( $course_id, $user_id, true );
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
			'order'   => isset( $lesson->module_order ) ? (int) $lesson->module_order : 0,
			'lessons' => array(),
		);
	}
	$modules_map[ $mod_id ]['lessons'][] = $lesson;
}

$total_modules = count( $modules_map );

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
.lxp-admin-view-notice {
	margin: 0 0 20px;
	padding: 14px 16px;
	border-radius: 12px;
	border: 1px solid rgba(68,46,102,.18);
	background: rgba(68,46,102,.05);
	color: #2f2f2f;
	font-size: 0.95rem;
	line-height: 1.5;
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
	white-space: normal;
	line-height: 1.65;
}
/* Override theme reset: menu,ol,ul{list-style:none;margin:0;padding:0} */
.lxp-response-text ul {
	list-style-type: disc;
	margin: 0.4em 0 0.4em 1.5em;
	padding: 0;
}
.lxp-response-text ol {
	list-style-type: decimal;
	margin: 0.4em 0 0.4em 1.5em;
	padding: 0;
}
.lxp-response-text li {
	margin-bottom: 0.2em;
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
	padding: 4px 10px;
	width: auto;
	border-radius: 8px;
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 0.78rem;
	font-weight: 700;
	flex-shrink: 0;
	letter-spacing: .03em;
	white-space: nowrap;
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
.lxp-btn {
	display: inline-block;
	padding: 10px 18px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 0.95rem;
	text-decoration: none;
	border: none;
	cursor: pointer;
	transition: all 0.2s;
}
.lxp-btn-primary {
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
}
.lxp-btn-primary:hover {
	background: #2a1e3f;
}
.lxp-btn-secondary {
	background: #f0f0f0;
	color: #333;
	border: 1px solid #ddd;
}
.lxp-btn-secondary:hover {
	background: #e8e8e8;
	text-decoration: none;
}
/* ---- Policy Document form card ---- */
.lxp-policy-card {
	background: #fff;
	border-radius: 14px;
	box-shadow: 0 10px 30px -10px rgba(68,46,102,.12);
	border: 1px solid rgba(68,46,102,.12);
	padding: 24px 28px;
	margin-bottom: 32px;
}
.lxp-policy-card h2 {
	font-size: 1.15rem;
	font-weight: 700;
	color: var(--lp-secondary-color, #442e66);
	margin-bottom: 18px;
}
.lxp-policy-fields {
	display: flex;
	flex-wrap: wrap;
	gap: 16px;
	align-items: flex-end;
	margin-bottom: 16px;
}
.lxp-policy-field {
	display: flex;
	flex-direction: column;
	gap: 5px;
	flex: 1 1 220px;
}
.lxp-policy-field label {
	font-size: 0.88rem;
	font-weight: 600;
	color: #555;
}
.lxp-policy-field input {
	padding: 9px 12px;
	border: 1px solid rgba(68,46,102,.3);
	border-radius: 8px;
	font-size: 0.95rem;
	font-family: inherit;
	background: #fff;
	width: 100%;
	box-sizing: border-box;
}
.lxp-policy-field input[readonly] {
	background: #f5f5f5;
	color: #888;
	cursor: default;
}
.lxp-policy-field input:focus {
	outline: none;
	border-color: var(--lp-secondary-color, #442e66);
	box-shadow: 0 0 0 2px rgba(68,46,102,.15);
}
.lxp-field-error {
	font-size: 0.82rem;
	color: #c0392b;
	margin-top: 2px;
}
.lxp-policy-footer {
	display: flex;
	align-items: center;
	gap: 14px;
	flex-wrap: wrap;
}
#lxp-download-policy-btn {
	padding: 11px 26px;
	background: var(--lp-secondary-color, #442e66);
	color: #fff;
	border: none;
	border-radius: 10px;
	font-size: 0.97rem;
	font-weight: 600;
	cursor: pointer;
	white-space: nowrap;
	font-family: inherit;
}
#lxp-download-policy-btn:disabled {
	opacity: .65;
	cursor: not-allowed;
}
#lxp-policy-status {
	font-size: 0.9rem;
}
</style>

<div class="lxp-journal-wrap">
	<h1 class="lxp-journal-title">Workbook<?php echo $course_title ? ' &mdash; ' . $course_title : ''; ?></h1>
	<?php if ( $is_admin_view && $target_user ) : ?>
	<div class="lxp-admin-view-notice">
		<?php echo esc_html( sprintf( 'Viewing workbook for %s.', $target_user->display_name ) ); ?>
	</div>
	<?php endif; ?>

	<?php if ( $course_id > 0 && ! $is_admin_view ) : ?>
	<div class="lxp-policy-card">
		<h2>Download Policy Document</h2>
		<form id="lxp-policy-form" data-course-id="<?php echo absint( $course_id ); ?>" novalidate>
			<div class="lxp-policy-fields">
				<div class="lxp-policy-field">
					<label for="lxp-district-name">
						District / School Name <span aria-hidden="true" style="color:#c0392b;">*</span>
					</label>
					<input type="text" id="lxp-district-name" name="district_name"
						   required placeholder="Enter district or school name" />
					<span class="lxp-field-error" id="lxp-district-error" hidden>
						This field is required.
					</span>
				</div>
				<div class="lxp-policy-field">
					<label for="lxp-effective-date">Effective Date</label>
					<input type="text" id="lxp-effective-date" name="effective_date"
						   readonly value="<?php echo esc_attr( date_i18n( 'F j, Y' ) ); ?>" />
				</div>
			</div>
			<div class="lxp-policy-footer">
				<button type="submit" id="lxp-download-policy-btn">Download Policy Document</button>
				<span id="lxp-policy-status"></span>
			</div>
		</form>
	</div>
	<?php endif; ?>

	<?php if ( empty( $lessons ) ) : ?>
	<div class="lxp-empty-state">
		<p><?php $course_id > 0 ? print( 'No workbook entries yet for this course.' ) : print( 'Select a course to view the workbook.' ); ?></p>
	</div>
	<?php else : ?>

	<?php
	foreach ( $modules_map as $module ) :
		// Only show modules that have at least one submitted lesson.
		$submitted_lessons = array_filter( $module['lessons'], function( $l ) {
			return ! empty( $l->response );
		} );
		if ( empty( $submitted_lessons ) ) continue;
	?>
	<div class="lxp-module-block">
		<div class="lxp-module-header">
			<div class="lxp-module-number">Module <?php echo $module['order']; ?></div>
			<div class="lxp-module-title"><?php echo esc_html( $module['name'] ); ?></div>
		</div>
		<div class="lxp-module-lessons">
		<?php foreach ( $submitted_lessons as $lesson ) : ?>
		<div class="lxp-lesson-row" id="lxp-row-<?php echo (int) $lesson->lesson_id; ?>">
			<div class="lxp-lesson-header">
				<div class="lxp-lesson-info">
					<div class="lxp-lesson-name"><?php echo esc_html( $lesson->lesson_title ); ?></div>
					<div class="lxp-lesson-meta">
						Submitted <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $lesson->submitted_at ) ) ); ?>
						<?php if ( $lesson->updated_at !== $lesson->submitted_at ) : ?>
						&middot; Updated <?php echo esc_html( date_i18n( 'M j, Y', strtotime( $lesson->updated_at ) ) ); ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="lxp-response-body" style="display:block;">
				<div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
					<div style="flex:0 0 63%;min-width:200px;">
						<div class="lxp-response-label"><?php echo esc_html( $is_admin_view ? 'Workbook Entry' : 'Your Workbook Entry' ); ?></div>
						<div class="lxp-response-text"><?php echo wp_kses_post( $lesson->response ); ?></div>
					</div>
					<?php if ( ! empty( $lesson->evaluation ) ) : ?>
					<div style="flex:1;min-width:180px;">
						<div class="lxp-response-label">Evaluation</div>
						<div class="lxp-response-text" style="background:rgba(255,182,6,.07);border:1px solid rgba(255,182,6,.28);">
							<?php echo esc_html( $lesson->evaluation ); ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
		</div><!-- .lxp-module-lessons -->
	</div><!-- .lxp-module-block -->
	<?php endforeach; ?>
	<?php endif; ?>
</div>


<?php if ( $course_post ) : ?>
	<div class="lxp-workbook-actions" style="margin-top: 40px;margin-bottom: 40px; padding-top: 20px; border-top: 1px solid rgba(68,46,102,.12); display: flex; gap: 12px; justify-content: center;">
		<a href="<?php echo esc_url( get_permalink( $course_post->ID ) ); ?>" class="lxp-btn lxp-btn-secondary">Back to Course</a>
	</div>
<?php endif; ?>

<?php get_footer(); ?>
