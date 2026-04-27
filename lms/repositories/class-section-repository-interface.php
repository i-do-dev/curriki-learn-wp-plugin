<?php

interface TL_Section_Repository_Interface {
	public function get_sections_by_section_course_id($course_id);
	public function get_lessons_by_section_id($section_id);
	public function get_course_id_by_item_id($item_id);
	public function get_sections_by_course_id($course_id);
	public function get_section_by_id($section_id);
	public function update_section($section_id, $title, $content, $sort);
	public function create_section($course_id, $title, $content, $sort);
	public function delete_section($section_id);
	public function get_section_name_by_item_id($item_id);
}
