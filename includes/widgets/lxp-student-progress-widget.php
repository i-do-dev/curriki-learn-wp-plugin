<?php

namespace Edudeme\Elementor;

use Elementor\Widget_Base; // Important!
use Elementor\Controls_Manager;

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
class Student_Progress_Widget extends Widget_Base {

	public function get_name() {
		return 'lxp-student-progress';
	}

	public function get_title() {
		return esc_html__( 'LXP Student Progress', 'edudeme' );
	}

	public function get_icon() {
		return 'eicon-info-circle';
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
				'default' => esc_html__( 'Assignments', 'edudeme' ),
				'placeholder' => esc_html__( 'Enter title', 'edudeme' ),
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title = $settings['title'] ?? '';

        // Enqueue styles inline
    echo '<style>
			.progress-container {
                max-width: 600px;
                margin: 30px auto;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
            }

            .progress-title {
                text-align: center;
                font-size: 20px;
                margin-bottom: 20px;
                color: #333;
                font-weight: 600;
            }

            .progress-grid {
                display: flex;
                justify-content: space-between;
                gap: 20px;
                flex-wrap: wrap;
            }

            .progress-item {
                flex: 1;
                min-width: 120px;
                text-align: center;
                padding: 15px;
                background: #f9f9ff;
                border-radius: 8px;
                box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            }

            .progress-label {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 8px;
                color: #333;
                white-space: nowrap;
            }

            .progress-value {
                font-size: 28px;
                font-weight: bold;
                color: #444;
                margin: 0;
                line-height: 1;
            }

            /* Optional: Color coding */
            .todo .progress-label { background-color: #e5e5e5; padding: 2px 8px; border-radius: 4px; }
            .in-progress .progress-label { background-color: #f4a261; color: white; padding: 2px 8px; border-radius: 4px; }
            .submitted .progress-label { background-color: #4CAF50; color: white; padding: 2px 8px; border-radius: 4px; }
		</style>';

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Please log in to view your progress.', 'edudeme' ) . '</p>';
			return;
		}

		$student_post = lxp_get_student_post(get_current_user_id());
        $assignments = lxp_get_student_assignments($student_post->ID);

        $assignments_submissions = assignments_submissions($assignments, $student_post);

        $statuses_count = array_reduce($assignments, function($carry, $assignment) use ($assignments_submissions) {
        $status_items = array_filter($assignments_submissions, function($submission) use ($assignment) {
            return isset($submission[$assignment->ID]);
        });
        $status = count($status_items) > 0 ? array_values($status_items)[0][$assignment->ID]['status'] : 'None';
        switch ($status) {
            case 'In Progress':
            $carry['inprogress']++;
            break;
            case 'Completed':
            $carry['completed']++;
            break;
            case 'To Do':
            $carry['todo']++;
            break;  
        }
        return $carry;
        }, array('todo' => 0, 'inprogress' => 0, 'completed' => 0));

		// Output HTML
		echo '
        <div class="post-inner">
			<div class="post-content">
		        <div class="progress-container">
                    <h2 class="progress-title">'.$title.'</h2>
                    <div class="progress-grid">
                        <div class="progress-item todo">
                            <div class="progress-label">To-Do</div>
                            <div class="progress-value">'.$statuses_count['todo'].'</div>
                        </div>
                    <div class="progress-item in-progress">
                        <div class="progress-label">In Progress</div>
                        <div class="progress-value">'.$statuses_count['inprogress'].'</div>
                    </div>
                    <div class="progress-item submitted">
                        <div class="progress-label">Submitted</div>
                            <div class="progress-value">'.$statuses_count['completed'].'</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
	}

}