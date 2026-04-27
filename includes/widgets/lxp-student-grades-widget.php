<?php

namespace Edudeme\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Student_Grades_Widget extends Widget_Base {

	public function get_name() {
		return 'student-grade-widget';
	}

	public function get_title() {
		return esc_html__( 'LXP Student Student Grades', 'edudeme' );
	}

	public function get_icon() {
		return 'eicon-document-file';
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
				'default' => esc_html__( 'Student Assignment Calander', 'edudeme' ),
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
        $settings = $this->get_settings_for_display();
		$title = $settings['title'] ?? '';
        $dynamic = $settings['enable_dynamic'];
        // student assignments
        $student_post = lxp_get_student_post(get_current_user_id());
		$assignments = array_filter(lxp_get_student_assignments($student_post->ID), function($assignment) {
			$assignment_submission = lxp_get_assignment_submissions($assignment->ID, lxp_get_student_post(get_current_user_id())->ID);
			return $assignment_submission && get_post_meta($assignment_submission['ID'], 'mark_as_graded', true) == 'true';
		});
        // $assignments_submissions = assignments_submissions($assignments, $student_post);
        $treks_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
        echo '
			<style>
				.polygon-shap {
					float: left;
					width: 48px;
					height: 48px;
					background-color: #1fa5d4;
					clip-path: polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%);
					display: flex;
					align-items: center;
					justify-content: center;
				}
				.polygon-shap span {
					display: block;
					font-style: normal;
					font-weight: 700;
					font-size: 16px;
					line-height: 24px;
					color: #ffffff;
				}
				.assignments-table-cs-td-poly div:nth-child(2) span
				{
					margin-left: 8px;
					display: block;
				}
				.assignments-table-cs-td-poly div:nth-child(2) span:nth-child(2)
				{
					font-family: "sans-serif";
					font-size: 12px;
					line-height: 16px;
					color: #979797;
				}
				.teacher {
					display: flex;
					align-items: center;
					gap: 8px;
				}
				.teacher img {
					width: 32px;
					height: 32px;
					object-fit: cover;
				}
				.teacher h3 {
					font-size: 16px;
					margin: 0;
				}
			</style>
			<div class="post-inner">
				<div class="post-content">
					<div class="entry-content">
						<h3 class="entry-title">'.$title.'</h3>
						<div class="mptt-shortcode-wrapper">
							<table class="mptt-shortcode-table">
								<thead>
									<tr class="mptt-shortcode-row">
										<th><strong>Lesson</strong></th>
										<th><strong>Due Date</strong></th>
										<th><strong>Teacher</strong></th>
										<th><strong>Grade Summary</strong></th>
									</tr>
								</thead>
								<tbody>';
								if ($dynamic) {
									if (count($assignments) > 0) {
										foreach ($assignments as $assignment) { 
											$course = get_post(get_post_meta($assignment->ID, 'course_id', true));
											if (is_object($course)) {
												$lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));                      
												$segmentColor = "#1fa5d4";
											?>
											<tr>
												<td>
													<div class="assignments-table-cs-td-poly">
														<div class="polygon-shap" style="background-color: <?php echo $segmentColor; ?>">
														<span><?php echo 'L'; ?></span>
														</div>
														<div>
														<span style="color: <?php echo $segmentColor; ?>"><?php echo $lxp_lesson_post->post_title; ?></span>
														<span><?php echo $course->post_title; ?></span>
														</div>
													</div>
												</td>
												<td>
												<?php
												// get start_date metadata from assignment and format it by month, date and year
												$start_date = get_post_meta($assignment->ID, "start_date", true);
												$start_date = date("M d, Y", strtotime($start_date));
												echo $start_date;
												?>
												</td>
												<td>
													<div class="teacher">
														<img src="<?php echo $treks_src; ?>/assets/img/profile-icon.png" alt="student" />
														<h3>
														<?php
														// get assignment lxp_assignment_teacher_id metadata as $teacher_post_id
														$teacher_post_id = get_post_meta($assignment->ID, "lxp_assignment_teacher_id", true);
														// get teacher lxp_teacher_admin_id metadata as $teacher_admin_id
														$teacher_admin_id = get_post_meta($teacher_post_id, "lxp_teacher_admin_id", true);
														// get teacher user data by $teacher_admin_id as $teacher_user
														$teacher_user = get_userdata($teacher_admin_id);
														echo $teacher_user->display_name;
														?>
														</h3>
													</div>
												</td>
												<td>
													<a target="__blank" href="<?php echo site_url("grade-summary?assignment_id=" . $assignment->ID); ?>">
														<img width="28" src="<?php echo $treks_src; ?>/assets/img/review-icon.svg" style="opacity: 0.7;" />
													</a>
												</td>
											</tr>
											<?php
											}
										}
									} else {
		echo '						<tr> <td >No asssignment available</td><td colspan="3"></td></tr>';
									}
								} else {
		echo '						<tr>
										<td>Dynaminc</td>
										<td>Aug 15, 2023</td>
										<td><span style="color: #28a745;">Completed</span></td>
										<td><a href="#" style="color: #007cba; text-decoration: none;">David</a></td>
									</tr>
									<tr>
										<td>Customer Feedback Report</td>
										<td>Aug 5, 2023</td>
										<td><span style="color: #ffc107;">Pending</span></td>
										<td><a href="#" style="color: #007cba; text-decoration: none;">John</a></td>
									</tr>';
								}
		echo '
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>';
    }
}