<?php

namespace Edudeme\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Assignment_Calander_Widget extends Widget_Base {

	public function get_name() {
		return 'student-assignment-calandar';
	}

	public function get_title() {
		return esc_html__( 'LXP Student Assignment Calandar', 'edudeme' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return [ 'edudeme-category' ]; // Or 'general', 'learpress', etc.
	}
	
	public function get_style_depends() {
		return [ 'calendar', 'newAssignment', 'calendar-style' ];
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

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title = $settings['title'] ?? '';

        echo $title;

		echo '
		<div class="calendar-flex-box" style="display:flex; gap:16px">
			<div class="calendar-main" style="width: 980px;padding: 15px;">
				<div id="calendar"></div>
			</div>
			<div class="calendar-right-box">
				<div class="small-calendar">
					<div id="calendar-monthly">
						<form action="#" class="ws-validate">
							<div class="form-row">
								<input type="date" class="hide-replaced" />
							</div>
							<div class="form-row">
								<input type="submit" />
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>';
	}

}