<?php
// if (isset($_GET['assignment_id'])) {
// 	$student_post = lxp_get_student_post(get_current_user_id());
// 	lxp_check_assignment_submission($_GET['assignment_id'], $student_post->ID);
// }
$marks_float = 0;
$interactions_float = 0;
$progress_float = 0;
$assignment = isset($_GET['assignment_id']) ? lxp_get_assignment($_GET['assignment_id']) : null;
$assignment_submission = lxp_get_assignment_submissions($assignment->ID, lxp_get_student_post(get_current_user_id())->ID);
$course = get_post(get_post_meta($assignment->ID, 'course_id', true));
$lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));
$section_repository = new TL_LearnPress_Section_Repository();
$section_name = $section_repository->get_section_name_by_item_id($lxp_lesson_post->ID);
$courseTitle = $course->post_title;
$coursePermaLink = get_permalink($course->ID);

$content = get_post_meta($post->ID);
$attrId =  isset($content['lti_post_attr_id'][0]) ? $content['lti_post_attr_id'][0] : "";
$title =  isset($content['lti_content_title'][0]) ? $content['lti_content_title'][0] : "";
$toolCode =  isset($content['lti_tool_code'][0]) ? $content['lti_tool_code'][0] : "";
$customAttr =  isset($content['lti_custom_attr'][0]) ? $content['lti_custom_attr'][0] : "";
$toolUrl =  isset($content['lti_tool_url'][0]) ? $content['lti_tool_url'][0] : "";
$plugin_name = Tiny_LXP_Platform::get_plugin_name();
$content = '<p>' . $post->post_content . '</p>';
if ($attrId) {
	$content .= '<p> [' . $plugin_name . ' tool=' . $toolCode . ' id=' . $attrId . ' title=\"' . $title . '\" url=' . $toolUrl . ' custom=' . $customAttr . ']' . "" . '[/' . $plugin_name . ']  </p>';
}
$queryParam = '';
if (isset($_GET['slide'])) {
	$queryParam = "&slideNumber=" . $_GET['slide'];
}
if (isset($_GET["assignment_id"])) {
	$queryParam = $queryParam . "&assignment_id=" . $_GET["assignment_id"];	
}
$toolUrl = $toolUrl . $queryParam;
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?php the_title(); ?></title>
	<link href="<?= $treks_src; ?>/style/main.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous" />
	<link rel="stylesheet" href="<?= $treks_src; ?>/style/header-section.css" />
	<link href="<?= $treks_src; ?>/style/treksstyle.css" rel="stylesheet" />
	<link rel="stylesheet" href="<?= $treks_src; ?>/style/newAssignment.css" />
	<link rel="stylesheet" href="<?= $treks_src; ?>/style/schoolAdminTeachers.css" />
	<link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css"
      crossorigin="anonymous"
    />

	<script src="https://code.jquery.com/jquery-3.6.3.js" integrity="sha256-nQLuAZGRRcILA+6dMBOvcRh5Pe310sBpanc6+QBmyVM=" crossorigin="anonymous"></script>
	<script src="<?= $treks_src; ?>/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js"></script>
	<script src="<?= $treks_src; ?>/js/custom.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
	<style>
		.truncate-words {
			display: -webkit-box;
			overflow: hidden;
    	}
	</style>
</head>

<body>
	<nav class="navbar navbar-expand-lg bg-light">
		<div class="container-fluid">
			<?php include $livePath.'/trek/header-logo.php'; ?>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<div class="navbar-nav me-auto mb-2 mb-lg-0">
					<div class="header-logo-search">

						<!-- searching input -->
						<div class="header-search">
							<img src="<?= $treks_src; ?>/assets/img/header_search.svg" alt="svg" />
							<form action="<?= site_url("search"); ?>">
								<input placeholder="Search" id="q" name="q" value="<?= isset($_GET["q"]) ? $_GET["q"]:''; ?>" />
							</form>
						</div>
					</div>
				</div>
				<div class="d-flex" role="search">
					<div class="header-notification-user">
						<?php include $livePath.'/trek/user-profile-block.php'; ?>
					</div>
				</div>
			</div>
		</div>
	</nav>

	<!-- Basic Container -->
	<section class="main-container treks_main_container">
		<section class="main-container nav_container">
			<!-- Nav Section -->
			<nav class="nav-section nav_section_interpendence">
				<?php include $livePath.'/trek/navigation-student.php'; ?>
			</nav>
		</section>
		<!-- Interpendence Practice Section -->
		<section class="interpendence_content_section">
			<div class="treks_practice_bx">
				<div class="practice_flx">
					<img src="<?= $treks_src; ?>/assets/img/nav_Treks.svg" />
					<p class="practice_text">My Course</p>
				</div>
				<div class="practice_flx">
					<img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><a  style = "color: #979797 !important;text-decoration: none !important;" href="<?= $coursePermaLink ?> "  target="_self"><?= $courseTitle ?></a></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?= $section_name ?></a></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?= $lxp_lesson_post->post_title; ?></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $treks_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?php the_title(); ?></p>
				</div>
			</div>
			<p class="interpendence_text"><?= $courseTitle .' > '.$section_name .' > '. $lxp_lesson_post->post_title; ?></p>
			<!-- <p class="practice_text student_text">Digital Student Journal &nbsp;<span><a id="dsj_link" href="#"><img class="copy-anchor-icon-img" src="<?php // echo $treks_src; ?>/assets/img/link_icon.png" width="18" height="18" /></a></span></p> -->
			<?php if ($assignment) { ?>
				<!-- make row with 2 columns -->
					<div class="row">
						<div class="col-md-12">
							<div class="time-date-box">
								<p class="date-time"><span id="assignment_day"><?= date("D", strtotime($assignment->start_date)); ?></span>, <span id="assignment_month"><?= date("F", strtotime($assignment->start_date)); ?></span> <span id="assignment_date"><?= date("d", strtotime($assignment->start_date)); ?></span>, <span id="assignment_date"><?= date("Y", strtotime($assignment->start_date)); ?></span></p>
								<p class="date-time" id="assignment_time_start"><?= date("h:i:s a", strtotime($assignment->start_time)); ?></p>
								<p class="date-time to-text">To</p>
								<p class="date-time" id="assignment_time_end"><?= date("h:i:s a", strtotime($assignment->end_time)); ?></p>
							</div>
						</div>
					</div>	
					<br />
					<?php						
						$mark_as_graded = get_post_meta($assignment_submission['ID'], 'mark_as_graded', true);
						if ($mark_as_graded === 'true') {
							$slides = get_assignment_lesson_slides($assignment->ID);
							$args['slides'] = $slides;
							$args['assignment_submission'] = $assignment_submission;
							$assignment_type = get_post_meta($assignment->ID, 'assignment_type', true);
							if ($assignment_type == 'video_activity') {
								$all_interactions = get_interactions_by_activity($assignment_submission);
								$interaction_xapis = get_submitted_xapi_data( $assignment_submission );
								include $livePath.'/lxp/grade-book-video.php';
							} else {
								include $livePath.'/lxp/grade-book.php';
							}
						
							$args['assignment_submission_id'] = $assignment_submission['ID'];
							include $livePath.'/lxp/teacher-grading-feedback-view-modal.php';
						} else {
							echo '<p class="text-center">Assignment is not graded yet.</p>';
						}
					?>
			<?php } ?>
			
		</section>
	</section>

	<script type="text/javascript">
		const marks_float = "<?= $marks_float; ?>";
		const interactions_float = "<?= $interactions_float; ?>";
		const progress_float = "<?= $progress_float; ?>";
		if (interactions_float !== null) {
			$('.score-progress').circleProgress({
				startAngle: -Math.PI / 4 * 1,
				value: marks_float,
				size: 50,
				thickness: 6,
				lineCap: 'round',
				fill: {gradient: ['#0e7c57']}
			});
			$('.book-progress').circleProgress({
				startAngle: -Math.PI / 4 * 1,
				value: progress_float,
				size: 50,
				thickness: 6,
				lineCap: 'round',
				fill: {gradient: ['#1768c4']}
			});
			$('.interaction-progress').circleProgress({
				startAngle: -Math.PI / 4 * 1,
				value: interactions_float,
				size: 50,
				thickness: 6,
				lineCap: 'round',
				fill: {gradient: ['#1768c4']}
			});
		}
		jQuery(document).ready(function() {
			jQuery("#dsj_link").on('click',function(e){
				e.preventDefault();
                document.addEventListener('copy', function(e) {
                    e.clipboardData.setData('text/plain', jQuery('#dsj_link').attr('href'));
                    e.preventDefault();
                }, true);
                document.execCommand('copy');  
			});

			var gradeBookModal = document.getElementById('gradeBookModal');
			gradeBookModalObj = new bootstrap.Modal(gradeBookModal);
			window.gradeBookModalObj = gradeBookModalObj;
		});

		window.addEventListener('message', function (event) {
			if (typeof event.data === 'object' && event.data.hasOwnProperty('currentSlide')) {
				let dsj_copy_link = location.origin + location.pathname + '?slide='+ event.data.currentSlide;
				jQuery('#dsj_link').attr('href', dsj_copy_link);
			}
		});

	</script>
</body>

</html>
