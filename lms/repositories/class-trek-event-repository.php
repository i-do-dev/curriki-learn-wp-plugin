<?php

class TL_Trek_Event_Repository implements TL_Trek_Event_Repository_Interface {
	private $wpdb;
	private $students_table;
	private $student_assignments_table;
	private $trek_events_table;

	public function __construct($wpdb_instance = null) {
		global $wpdb;
		$this->wpdb = $wpdb_instance ? $wpdb_instance : $wpdb;
		$this->students_table = $this->wpdb->prefix . 'students';
		$this->student_assignments_table = $this->wpdb->prefix . 'student_assignments';
		$this->trek_events_table = $this->wpdb->prefix . 'trek_events';
	}

	public function create_event($trek_section_id, $start, $end, $user_id) {
		$this->wpdb->insert(
			$this->trek_events_table,
			array(
				'trek_section_id' => absint($trek_section_id),
				'start' => $start,
				'end' => $end,
				'user_id' => absint($user_id),
			),
			array('%d', '%s', '%s', '%d')
		);

		return absint($this->wpdb->insert_id);
	}

	public function list_events_by_user($user_id) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->trek_events_table} WHERE user_id = %d",
			absint($user_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function update_event($event_id, $start, $end, $trek_section_id = 0) {
		$event_id = absint($event_id);
		$trek_section_id = absint($trek_section_id);
		if ($event_id <= 0) {
			return 0;
		}

		$data = array(
			'start' => $start,
			'end' => $end,
		);
		$formats = array('%s', '%s');
		if ($trek_section_id > 0) {
			$data['trek_section_id'] = $trek_section_id;
			$formats[] = '%d';
		}

		return $this->wpdb->update(
			$this->trek_events_table,
			$data,
			array('id' => $event_id),
			$formats,
			array('%d')
		);
	}

	public function get_event_by_id($event_id) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->trek_events_table} WHERE id = %d LIMIT 1",
			absint($event_id)
		);

		return $this->wpdb->get_row($query);
	}

	public function delete_event($event_id) {
		return $this->wpdb->delete(
			$this->trek_events_table,
			array('id' => absint($event_id)),
			array('%d')
		);
	}

	public function assign_student_to_event($student_id, $event_id) {
		$this->wpdb->insert(
			$this->student_assignments_table,
			array(
				'student_id' => absint($student_id),
				'assignment_id' => absint($event_id),
			),
			array('%d', '%d')
		);

		return absint($this->wpdb->insert_id);
	}

	public function unassign_student_assignment($student_assignment_id) {
		return $this->wpdb->delete(
			$this->student_assignments_table,
			array('id' => absint($student_assignment_id)),
			array('%d')
		);
	}

	public function get_assigned_students_by_section_and_teacher($trek_section_id, $teacher_id) {
		$query = $this->wpdb->prepare(
			"SELECT s.*, sa.id as student_assignment_id
			 FROM {$this->students_table} s
			 JOIN {$this->student_assignments_table} sa ON sa.student_id = s.id
			 JOIN {$this->trek_events_table} te ON te.id = sa.assignment_id
			 WHERE te.trek_section_id = %d AND te.user_id = %d",
			absint($trek_section_id),
			absint($teacher_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function get_assigned_students_by_event($event_id) {
		$query = $this->wpdb->prepare(
			"SELECT s.*
			 FROM {$this->students_table} s
			 JOIN {$this->student_assignments_table} sa ON sa.student_id = s.id
			 JOIN {$this->trek_events_table} te ON te.id = sa.assignment_id
			 WHERE te.id = %d",
			absint($event_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function get_unassigned_students($excluded_student_ids = array()) {
		$excluded_student_ids = is_array($excluded_student_ids) ? $excluded_student_ids : array();
		$excluded_student_ids = array_values(array_filter(array_map('absint', $excluded_student_ids)));
		if (empty($excluded_student_ids)) {
			return $this->wpdb->get_results("SELECT * FROM {$this->students_table}");
		}

		$placeholders = implode(', ', array_fill(0, count($excluded_student_ids), '%d'));
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->students_table} WHERE id NOT IN ({$placeholders})",
			$excluded_student_ids
		);

		return $this->wpdb->get_results($query);
	}
}
