<?php

class Rest_Lxp_Course
{
	/**
	 * Register the REST API routes.
	 */
	public static function init()
	{
		if (!function_exists('register_rest_route')) {
			// The REST API wasn't integrated into core until 4.4, and we support 4.0+ (for now).
			return false;
		}

		register_rest_route('lms/v1', '/course/lxp_sections', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Course', 'get_lxp_sections'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/course/lxp_section/lessons', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Course', 'get_lxp_course_section_lessons'),
				'permission_callback' => '__return_true'
			)
		));

		register_rest_route('lms/v1', '/course/lxp_lessons', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array('Rest_Lxp_Course', 'get_lxp_lessons_by_course'),
				'permission_callback' => '__return_true'
			)
		));
		
	}

	public static function get_lxp_sections($request) {
		$course_id = absint($request->get_param('course_id'));
		$repository = new TL_LearnPress_Section_Repository();
		$results = $repository->get_sections_by_section_course_id($course_id);
  		$lxp_sections = $results ? $results : [];
  		return wp_send_json_success(array("lxp_sections" => $lxp_sections));
	}

	public static function get_lxp_course_section_lessons($request) {		
		$lxp_sections = $request->get_param('lxp_sections');
		if ( is_array($lxp_sections) ) {
			$lxp_lessons = [];
			$repository = new TL_LearnPress_Section_Repository();
			foreach ($lxp_sections as $section_id) {
				$section_id = absint($section_id);
				$lxp_lessons[$section_id] = $repository->get_lessons_by_section_id($section_id);
			}
		}
		return wp_send_json_success(array("lxp_lessons" => $lxp_lessons));
	}

	public static function get_lxp_lessons_by_course($request) {
		$course_id = absint($request->get_param('course_id'));
		$repository = new TL_LearnPress_Section_Repository();
		$sections = $repository->get_sections_by_section_course_id($course_id);
		$lesson_ids = array();

		if (is_array($sections)) {
			foreach ($sections as $section) {
				if (!isset($section->section_id)) {
					continue;
				}

				$section_lessons = $repository->get_lessons_by_section_id($section->section_id);
				if (!is_array($section_lessons)) {
					continue;
				}

				foreach ($section_lessons as $section_lesson) {
					if (isset($section_lesson->ID)) {
						$lesson_ids[] = absint($section_lesson->ID);
					}
				}
			}
		}

		$lesson_ids = array_values(array_unique(array_filter($lesson_ids)));
		$lessons = array();
		if (!empty($lesson_ids)) {
			$lessons = get_posts(array(
				'post_type' => TL_LESSON_CPT,
				'post_status' => array('publish'),
				'posts_per_page' => -1,
				'post__in' => $lesson_ids,
				'orderby' => 'post__in',
			));
		}

		return wp_send_json_success(array("lxp_lessons" => $lessons));
	}
}