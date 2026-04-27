<?php

namespace Edudeme\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Student_Grade_Summay_Widget extends Widget_Base {

	public function get_name() {
		return 'student-grade-summary-widget';
	}

	public function get_title() {
		return esc_html__( 'LXP Student Grades Summary', 'edudeme' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return [ 'edudeme-category' ]; // Or 'general', 'learpress', etc.
	}
	
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Progress Settings', 'edudeme' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'edudeme' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Grade Summary', 'edudeme' ),
				'placeholder' => esc_html__( 'Enter title', 'edudeme' ),
			]
		);

		$this->add_control(
            'enable_dynamic',
            [
                'label' => 'Dynamic Data',
                'type'  => Controls_Manager::SWITCHER,
                'frontend_available' => true,
                'label_on' => __('Yes', 'textdomain'),
                'label_off' => __('No', 'textdomain'),
            ]
        );

		$this->end_controls_section();
	}

	protected function render() {
		// $settings = $this->get_settings_for_display();
		// $dynamic = $settings['enable_dynamic'] ?? '';
		
		global $wpdb;

		// Initialize variables
		// $assignments = [];

		// Get assignment ID from URL
		$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
		if (!$assignment_id) {
			echo '<p>' . esc_html__('No assignment selected.', 'text-domain') . '</p>';
			return;
		}

		// Get assignment
		$assignment = lxp_get_assignment($assignment_id);
		if (!$assignment) {
			echo '<p>' . esc_html__('Assignment not found.', 'text-domain') . '</p>';
			return;
		}

		// Get course & lesson
		$course_id = get_post_meta($assignment->ID, 'course_id', true);
		$lxp_lesson_id = get_post_meta($assignment->ID, 'lxp_lesson_id', true);
		$assignment_type = get_post_meta($assignment->ID, 'assignment_type', true) ?: 'default';

		$course = get_post($course_id);
		$lxp_lesson_post = get_post($lxp_lesson_id);

		if (!$course || !$lxp_lesson_post) {
			echo '<p>' . esc_html__('Course or lesson not found.', 'text-domain') . '</p>';
			return;
		}

		// Get section name (cached)
		$cache_key = "section_name_{$lxp_lesson_id}";
		$section_title = wp_cache_get($cache_key, 'lxp');
		if (false === $section_title) {
			$section_repository = new TL_LearnPress_Section_Repository();
			$section_title = $section_repository->get_section_name_by_item_id($lxp_lesson_id);
			$section_title = $section_title ?: esc_html__('Uncategorized', 'text-domain');
			wp_cache_set($cache_key, $section_title, 'lxp', 3600); // 1 hour
		}

		// Get submission
		$student_post = lxp_get_student_post(get_current_user_id());
		if (!$student_post) {
			echo '<p>' . esc_html__('Student not found.', 'text-domain') . '</p>';
			return;
		}
		$assignment_submission = lxp_get_assignment_submissions($assignment->ID, $student_post->ID);

		// Sanitize output
		$course_title = esc_html($course->post_title);
		$lesson_title = esc_html($lxp_lesson_post->post_title);
		$section_title = esc_html($section_title);
		$assets_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
		// Start output buffering
		ob_start();
		?>
		<style>
			.date-time {
				margin-left: 10px;
				font-family: "Arial";
				font-style: normal;
				font-weight: 400;
				font-size: 16px;
				padding: 4px 8px;
				line-height: 24px;
				background: rgba(31, 165, 212, 0.16);
				border-radius: 8px;
				color: #0b5d7a;
			}
			.breadcrumz {
				margin-left: 1%;
			}
			.to-text {
				color: #757575;
				background: none;
			}
			.course_nav_path
			{
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				gap: 10px;
			}
			.practice_flx {
				display: flex;
				gap: 16px;
				color: #979797
			}
			.practice_flx img {
				width: 23px;
				height: 20px;
			}
		</style>
		<div class='breadcrumz'>
			<div class="course_nav_path">
				<div class="practice_flx">
					<img src="<?= $assets_src; ?>/assets/img/nav_Treks.svg" />
					<p class="practice_text">My Course</p>
				</div>
				<div class="practice_flx">
					<img src="<?= $assets_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?= $course_title ?></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $assets_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?= $section_title ?></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $assets_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?= $lesson_title ?></p>
				</div>
				<div class="practice_flx">
					<img src="<?= $assets_src; ?>/assets/img/bc_arrow_right.svg" />
					<p class="practice_text"><?php the_title(); ?></p>
				</div>
			</div>
			<div class="course_nav_path">
					<p class="date-time"><span id="assignment_day"><?= date("l", strtotime($assignment->start_date)); ?></span>, <span id="assignment_month"><?= date("M", strtotime($assignment->start_date)); ?></span> <span id="assignment_date"><?= date("d", strtotime($assignment->start_date)); ?>,&nbsp;<?= date("Y", strtotime($assignment->start_date)); ?></span>&nbsp;<?= date("h:i A", strtotime($assignment->start_time)); ?></p>
					<p class="date-time to-text">To</p>
					<p class="date-time"><span id="assignment_day"><?= date("l", strtotime($assignment->end_date)); ?></span>, <span id="assignment_month"><?= date("M", strtotime($assignment->end_date)); ?></span> <span id="assignment_date"><?= date("d", strtotime($assignment->end_date)); ?>,&nbsp;<?= date("Y", strtotime($assignment->end_date)); ?></span>&nbsp;<?= date("h:i A", strtotime($assignment->end_time)); ?></p>
			</div>
		</div>
		<div class="assignment-widget post-inner">
			<div class="post-content">
				<div class="entry-content">
					<!-- <div class="row">
						<div class="col-sm-3">
							<button type="button" class="btn btn-outline-secondary history-back-button" ><i class="bi bi-arrow-return-left"></i> Back</button>
						</div>
					</div> -->
					<div class="mptt-shortcode-wrapper">
						<?php
						if ($assignment_type == 'video_activity') {
							// get all interactions for interactive-video / interactive-book
							$all_interactions = get_interactions_by_activity($assignment_submission);
							$interaction_xapis = [];
							// $interaction_xapis = get_submitted_xapi_data( $assignment_submission );
							if ($all_interactions['type'] == 'interactive_video') {
								echo $this->interactiveVideoGradeSummary($all_interactions, $interaction_xapis, $assignment_submission);
							} else {
								echo $this->interactiveBookGradeSummary($all_interactions, $interaction_xapis, $assignment_submission);
							}
						} else {
							$slides = get_assignment_lesson_slides($assignment->ID);
							echo $this->slidesGradeSummary($assignment_submission, $slides);
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
		echo $this->feedbackViewModal($assignment_submission);
		// Echo buffered output
		echo ob_get_clean();
	}

	function slidesGradeSummary($assignment_submission, $slides) {
		$slides = (isset($slides->slides)) ? $slides->slides : false ;

		ob_start(); // Use output buffering for cleaner output
		?>
		<table class="mptt-shortcode-table">
			<thead>
				<tr class="mptt-shortcode-row">
					<th><strong>Slide</strong></th>
					<th><strong>Feedback</strong></th>
					<th><strong>Result</strong></th>
					<th><strong>Point/Total</strong></th>
					<th><strong>Progress</strong></th>
				</tr>
			</thead>
			<tbody>
    		<?php

				if ($slides) {
					foreach ($slides as $slide) { 
						$slide_number = isset($slide->slide) ? $slide->slide : 0;
						$slide_title = isset($slide->title) ? $slide->title : 'Untitled';
						$slide_type = isset($slide->type) ? $slide->type : '';
						$graded_manually = in_array($slide_type, array('Essay')) ? true : false;
						$total_grades = 10;

						// Feedback
						$feedback = '';
						if ($assignment_submission) {
							$feedback = get_post_meta($assignment_submission['ID'], "slide_{$slide_number}_feedback", true);
						}

						// Grade
						$grade = '';
						if ($assignment_submission) {
							$grade = get_post_meta($assignment_submission['ID'], "slide_{$slide_number}_grade", true);
						}

						// Auto score (used in non-essay or fallback)
						$auto_score = array('score' => 0, 'max' => 0);
						if (!$graded_manually) {
							$auto_score = lxp_assignment_submission_auto_score($assignment_submission['ID'], intval($slide_number));
						}

						$score = ($graded_manually ) ? $grade : $auto_score['score'];
						$max = ($graded_manually ) ? $total_grades : $auto_score['max'];
						// Normalize score/max if max is 0
						if ($max == 0) {
							$score = 0;
							$max = 1; // avoid division by zero
						}

						$percentage = ($score / $max) * 100;

						// Determine progress class and icon
						if ($percentage >= 80) {
							$progress_class = 'bg-success';
							$icon = 'check-lg';
						} else {
							$progress_class = 'bg-danger';
							$icon = 'x-lg';
						}
						echo '<tr>';
						echo '<td>Slide ' . esc_html($slide_number) . ': ' . esc_html($slide_title) . '</td>';
						// Feedback Button (Yes/No)
						echo '<td>';
						if ($feedback) {
							// echo '<button onclick="showWidgetAlert()">Yes Show Alert</button>';
							echo '<a class="show-feedback-btn bold" href="#" data-slide="' . esc_attr($slide_number) . '">Yes</a>';
						} else {
							echo '<a class="show-feedback-btn" href="#" data-slide="' . esc_attr($slide_number) . '">No</a>';
						}
						echo '</td>';

						// Grade & Progress Columns
						if (in_array($slide_type, array('Essay'))) {
							if ($grade !== '' && $grade !== false) {
								// Graded
								echo '<td>';
								echo '<div class="' . esc_attr($progress_class) . ' rounded-pill d-flex" style="height: 25px; width: 100%; font-size: 1rem; border: 1px solid transparent;">';
								echo '<i class="bi bi-' . esc_attr($icon) . ' text-white" style="font-size: 1rem;"></i>';
								echo '</div>';
								echo '</td>';

								echo '<td>'.esc_html($grade) . '/' . esc_html($total_grades).'</td>';

								echo '<td>';
									echo '<div class="progress" style="height: 25px;">';
										echo '<div class="progress-bar ' . esc_attr($progress_class) . '" role="progressbar" style="width: ' . esc_attr(round($percentage)) . '%; color:#fff;" aria-valuenow="' . esc_attr($score) . '" aria-valuemin="0" aria-valuemax="' . esc_attr($max) . '">';
										echo esc_html(round($percentage)) . '%';
										echo '</div>';
									echo '</div>';
								echo '</td>';
							} else {
								// Not graded yet
								echo '<td>';
								echo '<div class="' . esc_attr($progress_class) . ' progress-pill">';
								echo '<i class="bi bi-' . esc_attr($icon) . ' text-white"></i></div>';
								echo '</td>';

								echo '<td>---</td>';

								echo '<td>To Be Graded</td>';
							}
						} else {
							if ($auto_score['max'] > 0) {
								// Auto-graded
								echo '<td>';
								echo '<div class="' . esc_attr($progress_class) . ' rounded-pill" style="height: 25px; width: 100%;">';
								echo '<center><i class="bi bi-' . esc_attr($icon) . ' text-white"></i></center>';
								echo '</div>';
								echo '</td>';

								echo '<td>'.esc_html($score) . '/' . esc_html($max).'</td>';

								echo '<td>';
								echo '<div class="progress" style="height: 25px;">';
								echo '<div class="progress-bar ' . esc_attr($progress_class) . '" role="progressbar" style="width: ' . esc_attr(round($percentage)) . '%; color:#fff;" aria-valuenow="' . esc_attr($score) . '" aria-valuemin="0" aria-valuemax="' . esc_attr($max) . '">';
								echo esc_html(round($percentage)) . '%';
								echo '</div>';
								echo '</div>';
								echo '</td>';
							} else {
								// Not auto-graded
								echo '<td>';
								echo '<div class="' . esc_attr($progress_class) . ' progress-pill">';
								echo '<i class="bi bi-' . esc_attr($icon) . ' text-white"></i></div>';
								echo '</td>';
								echo '<td>---</td>';
								echo '<td>Not Auto-graded</td>';
							}
						}

						echo '</tr>';
					}
				} else {
			echo '<tr> <td >Sorry, no data found!</td><td colspan="4"></td></tr>';
				}
		echo '</tbody>
		</table>';
		return ob_get_clean(); // Return the full HTML
	}

	/**
	 * feed back view all work here
	 */
	function feedbackViewModal($assignment_submission){

		$submission_id = intval($assignment_submission['ID'] ?? 0);
		// Only enqueue once per page
		static $modal_included = false;
		if ($modal_included) {
			return ''; // Prevent duplicate modals
		}
		$modal_included = true;

		static $bootstrap_loaded = false;
		if (!$bootstrap_loaded) {
			echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">';
			$bootstrap_loaded = true;
		}

		// Enqueue CSS and JS
    	add_action('wp_footer', function () use ($submission_id) {
			// Enqueue CSS
			wp_enqueue_style(
				'widget-feedback-modal-css',
				plugins_url('/TinyLxp-wp-plugin/includes/widgets/assets/style/feedback-view-style.css'),
				array(),
				'1.0'
			);
			// Enqueue JS (depends on jQuery + Bootstrap)
			wp_enqueue_script(
				'widget-feedback-modal',
				plugins_url('/TinyLxp-wp-plugin/includes/widgets/assets/js/feedback-view-modal.js'),
				array('jquery'),
				'1.0',
				true
			);
			// Localize data for JS
			$host = home_url();
			$api_url = trailingslashit($host) . 'wp-json/lms/v1/';
			wp_localize_script('widget-feedback-modal', 'FeedbackModalData', array(
				'apiUrl'              => esc_url_raw($api_url),
				'assignmentSubmissionId' => $submission_id,
				'nonce'               => wp_create_nonce('feedback_view_nonce'), // if API requires nonce
				'ajaxurl'             => admin_url('admin-ajax.php'), // fallback
			));
		});
	ob_start(); // Use output buffering for cleaner output
	?>
	<div class="modal fade" id="feedbackViewModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Grade Feedback</h5>
					<button type="button" class="btn-close viewclose" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<form id="feedbackViewForm">
						<input type="hidden" name="slide" id="slide" value="0" />
						<input type="hidden" name="assignment_submission_id" value="<?= esc_attr($submission_id); ?>" />
						<div id="feedback-container">Loading...</div>
						<button type="button" class="btn btn-outline-secondary viewclose" data-bs-dismiss="modal">Close</button>
					</form>
				</div>
			</div>
		</div>
	</div>
          
    <?php
	echo ob_get_clean();
	}

	function interactiveVideoGradeSummary($all_interactions, $interaction_xapis, $assignment_submission) {
		ob_start(); // Use output buffering for cleaner output
		if ($all_interactions['type'] == 'interactive_video') {
            // interactive-video Grade-Summary start here
        ?>
            <div class="row">
                <div class="col-md-12">
                    <table class="mptt-shortcode-table">
                        <thead>
                            <tr class="mptt-shortcode-row">
                                <th style="width: 40%;"><strong>Question</strong></th>
                                <th style="width: 20%;"><strong>Feedback</strong></th>
                                <th style="width: 10%;"><strong>Result</strong></th>
                                <th style="width: 10%;"><strong>Points/Total</strong></th>
                                <th style="width: 20%;"><strong>Progress<strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($all_interactions['data'] as $main_key => $single_iv_question):
                                    $sub_content_id_array = [];

                                    $intrect_type_name = $single_iv_question['action']['metadata']['contentType'];
                                    if ( isset($intrect_type_name) && in_array($intrect_type_name, Allowed_Activity_types) ) {
                                        if ($intrect_type_name == 'Free Text Question') {
                                            $sub_content_id_array[] = $single_iv_question['action']['subContentId'];
                                            $scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
                                            $xapi_res_max = array_sum(array_column($scores_array, 'max'));

                                            $i_grade = get_post_meta($assignment_submission['ID'], 'slide_'.$sub_content_id_array[0].'_grade', true);
                                            $score_raw = ($scores_array ? ($i_grade == '' ? '0' : $i_grade) : '0');
                                            $score_max = (isset($xapi_res_max) && $xapi_res_max > 0) ? $xapi_res_max : '7';
                                        } else {
                                            if ($intrect_type_name == 'Statements' || $intrect_type_name == 'Summary') {
                                                $sub_content_id_array = array_column( $single_iv_question['action']['params']['summaries'], 'subContentId' );
                                            } elseif ($intrect_type_name == 'Single Choice Set') {
                                                $sub_content_id_array = array_column( $single_iv_question['action']['params']['choices'], 'subContentId');
                                            } else {
                                                $sub_content_id_array[] = $single_iv_question['action']['subContentId'];
                                            }
                                            $scores_array = getScoresByGivenXapiStatements($sub_content_id_array, $interaction_xapis);
                                            $scores = array_column($scores_array, 'score');
                                            $score_raw = array_sum(array_column($scores, 'raw'));
                                            $score_max = array_sum(array_column($scores, 'max'));
                                        }
                                        $percentage = $score_raw == '' || $score_raw == 0 || $score_max == 0 ? '' : ($score_raw / $score_max) * 100;
                                        if ($percentage >= 80) {
                                            $progress_class = "bg-success";
                                            $icon = 'check-lg';
                                        } else {
                                            $progress_class = "bg-danger";
                                            $icon = 'x-lg';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <?= ++$main_key.'. '.$single_iv_question['action']['metadata']['title'] ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $feedback = $assignment_submission ? get_post_meta($assignment_submission['ID'], "slide_" . $sub_content_id_array[0] . "_feedback", true) : "";
                                                    if ($feedback) {
                                                ?>
                                                        <button class="btn btn-link" onclick="viewFeedback('<?= $sub_content_id_array[0]; ?>')"><strong>Yes</strong></button>
                                                <?php } else { ?>
                                                        No
                                                <?php } ?>
                                                
                                            </td>
                                            <td>
                                                <div class="<?= $progress_class; ?> rounded-pill" style="height: 25px; width: 100%;">
                                                    <center><i class="bi bi-<?= $icon; ?> text-white"></i></center>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $score_raw > 0 ? $score_raw. '/' . $score_max : "---"; ?>
                                            </td>
                                            <td>
                                                <?php if($score_raw == '' || $score_max == 0): ?>
                                                    ---
                                                <?php else: ?>
                                                    <div class="progress" style="height: 25px;">
                                                        <div class="progress-bar <?= $progress_class; ?>" role="progressbar" style="width: <?= $percentage; ?>%;" aria-valuenow="<?= $score_raw; ?>" aria-valuemin="0" aria-valuemax="<?= $score_max; ?>">
                                                            <?= round(($score_raw / $score_max) * 100); ?>%
                                                        </div>
                                                    </div>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                            ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
           // interactive-Video Grade-Summary end here
        }
		echo ob_get_clean();
	}

	/**
	 * @return interactive-book-table-html
	*/
	function interactiveBookGradeSummary($all_interactions, $interaction_xapis, $assignment_submission) {
		ob_start(); // Use output buffering for cleaner output
		$interaction_count = 0; $interaction_seen_count = 0;
		$marks_float = 0;
		$interactions_float = 0;
		$progress_float = 0;
        
		$progress_count = 0; $obtain_score = 0; $total_score = 0;
		foreach ($all_interactions['data'] as $single_chapter) {
			$chapter_sub_content_id = $single_chapter['chapter']['subContentId'];
			foreach ($single_chapter['chapter']['params']['content'] as $single_interaction) {
				if(in_array( $single_interaction['content']['metadata']['contentType'], Allowed_Activity_types )) {
					$interaction_count++;
					if (count($interaction_xapis) > 0){
						foreach ($interaction_xapis as $statement) {
							$sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
							if ($sub_content_id == $single_interaction['content']['subContentId']) {
								$interaction_seen_count++;
							}
						}
					}
				}
			}
			if ( count($interaction_xapis) > 0) {
				foreach ($interaction_xapis as $statement) {
					$sub_content_id = $statement['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
					if ($sub_content_id == $chapter_sub_content_id && $statement['verb']['display']['en-US'] == 'progressed') {
						$progress_count++;
					} elseif ($sub_content_id == $chapter_sub_content_id && $statement['verb']['display']['en-US'] == 'completed') {
						$obtain_score += $statement['result']['score']['raw'];
						$total_score += $statement['result']['score']['max'];
					}
				}
			}
		}
		$total_score = ($total_score > 0) ? $total_score : 1;
		$marks_float = $obtain_score/$total_score;
		$interactions_float = $interaction_seen_count/$interaction_count;
		$interaction_percentage = round($interactions_float*100);
		$progress_float = $progress_count/count($all_interactions['data']);
		$book_progress_percentage = round($progress_float*100);
		
		add_action('wp_footer', function () use ($marks_float, $interactions_float, $progress_float) {

			// Enqueue JS (depends on jQuery + Bootstrap)
			wp_enqueue_script(
				'animated-circular-progress',
				plugins_url('/TinyLxp-wp-plugin/includes/widgets/assets/js/Animated-Circular-Progress-Bar-with-jQuery-Canvas-Circle-Progress/dist/circle-progress.js'),
				array('jquery'),
				'1.0',
				true
			);

			wp_enqueue_script(
				'widget-interactive-book-circle',
				plugins_url('/TinyLxp-wp-plugin/includes/widgets/assets/js/ib-custom-circular.js'),
				array('jquery'),
				'1.0',
				true
			);

			wp_localize_script('widget-interactive-book-circle', 'ibSummaryCircularData', array(
				'marks_float' => $marks_float,
				'interactions_float' => $interactions_float,
				'progress_float' => $progress_float
			));
		});
		?>
		<div class="row">
			<div class="col-md-4 progress-card">
				<div class="progress-title">Total score</div>
				<div class="progress-container">
					<div class="progress-percent"><?= $obtain_score.'/'.$total_score; ?></div>
					<div class="progress-details">
					<div class="score-progress"></div>
					</div>
				</div>
			</div>
			<div class="col-md-4 progress-card">
				<div class="progress-title">Book progress</div>
				<div class="progress-container">
					<div class="progress-details">
						<div class="progress-count"><?= $book_progress_percentage; ?>%</div>
						<div class="progress-label"><?= $progress_count.' of '.count($all_interactions['data']); ?> pages</div>
					</div>
					<div class="book-progress"></div>
				</div>
			</div>
			<div class="col-md-4 progress-card">
				<div class="progress-title">Interactions progress</div>
				<div class="progress-container">
					<div class="progress-details">
						<div class="progress-count"><?= $interaction_percentage; ?>%</div>
						<div class="progress-label"><?= $interaction_seen_count.' of '.$interaction_count; ?> interactions</div>
					</div>
					<div class="interaction-progress"></div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<table class="mptt-shortcode-table">
					<thead>
						<tr class="mptt-shortcode-row">
							<th scope="col" style="width: 40%;">Chapter</th>
							<th scope="col" style="width: 40%;">Section</th>
							<th scope="col" style="width: 10%;">Points/Total</th>
							<th scope="col" style="width: 10%;">Progress</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$num = 1;
						foreach ($all_interactions['data'] as $main_key => $single_chapter): ?>
						<tr class="department-row">
							<td rowspan="<?= count($single_chapter['chapter']['params']['content']) ?>"><?= $num.'. '.htmlspecialchars($single_chapter['chapter']['metadata']['title']) ?>
							</td>
							<?php
								$firstTeam = true;
								foreach ($single_chapter['chapter']['params']['content'] as $sub_key => $interaction_per_chapter):
								if (!$firstTeam):
							?>
							<tr class="team-row">
							<?php endif; ?>
								<td>
									<?php
									$interaction_type = $interaction_per_chapter['content']['metadata']['contentType'];
									$sub_content_id = $interaction_per_chapter['content']['subContentId'];
									echo htmlspecialchars($interaction_per_chapter['content']['metadata']['title']);

									
									$raw = 0;
									$max = 0;
									if ($interaction_type == 'Course Presentation') {
										$slides = $interaction_per_chapter['content']['params']['presentation']['slides'];
										$result = get_cp_interactions_score($assignment_submission, $slides, $interaction_xapis);
										$raw 			  = $result['score_raw'];
										$max 			  = $result['score_max'];
										$f_score = $raw.'/'.$max;
									} elseif ( $interaction_type == 'Interactive Video') {
										$iv_data = $interaction_per_chapter['content']['params']['interactiveVideo'];
										$result = get_iv_interactions_score($assignment_submission, $iv_data, $interaction_xapis);
										$raw 			  = $result['score_raw'];
										$max 			  = $result['score_max'];
										$f_score = $raw.'/'.$max;
									} elseif ( $interaction_type == 'Question Set') { 
										$qs_data = $interaction_per_chapter['content']['params']['questions'];
										$result = get_question_set_interactions_score($assignment_submission, $qs_data, $interaction_xapis);
										$raw 			  = $result['score_raw'];
										$max 			  = $result['score_max'];
									} else {
										$interactioin_sub_content_id = $interaction_per_chapter['content']['subContentId'];
										$filtered = array_filter($interaction_xapis, function($xapi) use ($interactioin_sub_content_id) {
											$sub_content_id = $xapi['object']['definition']['extensions']['http://h5p.org/x-api/h5p-subContentId'];
											return ($interactioin_sub_content_id == $sub_content_id && ( $xapi['verb']['display']['en-US'] == 'completed' || $xapi['verb']['display']['en-US'] == 'answered' ));
										});
										$xapi_score = reset($filtered);
										if ( isset($xapi_score['result']['score']) ) {
											$raw = $xapi_score['result']['score']['raw'];
											$max = $xapi_score['result']['score']['max'];
										}
									}
									$f_score = $raw > 0 && $max > 0 ? $raw.'/'.$max : '---';
									$percentage = $raw > 0 && $max > 0 ? ($raw / $max) * 100 : '0';
									$progress_class = $percentage >= 80 ? "bg-success" : 'bg-danger';
									$percentage_html = $raw > 0 && $max > 0 ? '<div class="progress" style="height: 25px;"> <div class="progress-bar '.$progress_class.'" role="progressbar" style="width: '.$percentage.'%;" > '.round($percentage).'% </div> </div>' : '---';
									?>
								</td>
								<td >
									<?= $f_score ?>
								</td>
								<td>
									<?= $percentage_html; ?>
								</td>
								<?php $firstTeam = false; ?>
							</tr>
							<?php endforeach; ?>
						</tr>
						<?php $num++; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}
}