<?php

interface TL_Trek_Event_Repository_Interface {
	public function create_event($trek_section_id, $start, $end, $user_id);
	public function list_events_by_user($user_id);
	public function update_event($event_id, $start, $end, $trek_section_id = 0);
	public function get_event_by_id($event_id);
	public function delete_event($event_id);
	public function assign_student_to_event($student_id, $event_id);
	public function unassign_student_assignment($student_assignment_id);
	public function get_assigned_students_by_section_and_teacher($trek_section_id, $teacher_id);
	public function get_assigned_students_by_event($event_id);
	public function get_unassigned_students($excluded_student_ids = array());
}
