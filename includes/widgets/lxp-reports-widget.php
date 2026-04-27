<?php

namespace Edudeme\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base; // Important!

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Elementor oEmbed Widget.
 *
 * Elementor widget that inserts embeddable content into the page from any given URL.
 *
 * @since 1.0.0
 */
class LXP_Reports_Widget extends Widget_Base {
    
    public function get_name() {
        return 'reports-widget';
    }

    public function get_title() {
        return __('LXP Assignment Report', 'textdomain');
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_style_depends(): array {
		return [ 'widget-style-1', 'student-dashboard-css' ];
	}

    protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Assignment Report', 'edudeme' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'edudeme' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Reports', 'edudeme' ),
				'placeholder' => esc_html__( 'Report title', 'edudeme' ),
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
        $assignments = lxp_get_student_assignments($student_post->ID);
        $assignments_submissions = assignments_submissions($assignments, $student_post);
        $assets_src = content_url().'/plugins/TinyLxp-wp-plugin/lms/templates/tinyLxpTheme/treks-src/';
        ob_start();
        ?>
        <style>
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
                font-family: 'Nunito';
                font-style: normal;
                font-weight: 500;
                font-size: 16px;
                line-height: 24px;
                color: #1a1a1a;
                margin: 0;
            }
            .graded-report {
                background: #eeeef4;
                color: #868cec;
            }
            .submit-report {
                background: rgba(159, 195, 59, 0.16);
                color: #80ab07;
            }
            .pending-report {
                background: rgba(255, 205, 78, 0.16);
                color: #d69a00;
            }
            .reviewed-report {
                background: #f6f7fa;
                color: #979797;
            }
        </style>
        <div class="post-inner">
			<div class="post-content">
				<div class="entry-content">
                    <h3 class="entry-title"><?= $title ?></h3>
					<div class="mptt-shortcode-wrapper">
						<table class="mptt-shortcode-table">
                            <thead>
                                <tr>
                                    <th><strong>Course</strong></th>
                                    <th><strong>Date</strong></th>
                                    <th><strong>Status</strong></th>
                                    <th><strong>Teacher</strong></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($dynamic) {
                                    if (count($assignments) > 0) {
                                        foreach ($assignments as $assignment) { 
                                            $course = get_post(get_post_meta($assignment->ID, 'course_id', true));
                                            if (is_object($course)) {
                                                $lxp_lesson_post = get_post(get_post_meta($assignment->ID, 'lxp_lesson_id', true));                      
                                                $segmentColor = "#1fa5d4";
                                                $status_items = array_filter($assignments_submissions, function($submission) use ($assignment) {
                                                    return isset($submission[$assignment->ID]);
                                                });
                                                $status = count($status_items) > 0 ? array_values($status_items)[0][$assignment->ID]['status'] : 'None';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="assignments-table-cs-td-poly">
                                                            <div class="polygon-shap" style="background-color: <?= $segmentColor; ?>">
                                                                <span>L</span>
                                                            </div>
                                                            <div>
                                                                <span style="color: <?= $segmentColor; ?>"><?= $lxp_lesson_post->post_title; ?></span>
                                                                <span><?= $course->post_title; ?></span>
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
                                                        <?php 
                                                            $assignment_grade = false;
                                                            $assignment_submission_item = lxp_get_assignment_submissions($assignment->ID, $student_post->ID);
                                                            if ($assignment_submission_item) {
                                                                $assignment_grade = get_post_meta($assignment_submission_item['ID'], 'mark_as_graded', true);
                                                            }
                                                            // get assignment lxp_assignment_teacher_id metadata as $teacher_post_id
                                                            $teacher_post_id = get_post_meta($assignment->ID, "lxp_assignment_teacher_id", true);
                                                            // get teacher lxp_teacher_admin_id metadata as $teacher_admin_id
                                                            $teacher_admin_id = get_post_meta($teacher_post_id, "lxp_teacher_admin_id", true);
                                                            // get teacher user data by $teacher_admin_id as $teacher_user
                                                            $teacher_user = get_userdata($teacher_admin_id);
                                                        ?>
                                                        <?php if ($assignment_grade == 'true') { ?>
                                                            <span class="grade-label graded-report">Grade</span>
                                                        <?php } elseif ($status === 'Completed') { ?>
                                                            <span class="grade-label submit-report">Submitted</span>
                                                        <?php } elseif ($status === 'In Progress') { ?>
                                                            <span class="grade-label pending-report">In Progress</span>
                                                        <?php } else { ?>
                                                            <span class="grade-label reviewed-report">To Do</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <div class="teacher">
                                                            <img src="<?php echo $assets_src; ?>/assets/img/profile-icon.png" alt="student" />
                                                            <h3> <?= $teacher_user->display_name; ?> </h3>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                        }
                                    } else { ?>
                                        <tr> <td >No asssignment available</td><td colspan="3"></td></tr>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
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
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
					</div>
				</div>
			</div>
		</div>
        <?php
        echo ob_get_clean();
    }
}