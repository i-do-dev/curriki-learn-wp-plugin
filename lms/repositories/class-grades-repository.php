<?php

class TL_Grades_Repository implements TL_Grades_Repository_Interface {
	private $wpdb;
	private $grades_table;

	public function __construct($wpdb_instance = null) {
		global $wpdb;
		$this->wpdb = $wpdb_instance ? $wpdb_instance : $wpdb;
		$this->grades_table = $this->wpdb->prefix . 'tiny_lms_grades';
	}

	public function get_grade_id($lesson_id, $user_id) {
		$lesson_id = absint($lesson_id);
		$user_id = absint($user_id);
		if ($lesson_id <= 0 || $user_id <= 0) {
			return 0;
		}

		$query = $this->wpdb->prepare(
			"SELECT id FROM {$this->grades_table} WHERE lesson_id = %d AND user_id = %d LIMIT 1",
			$lesson_id,
			$user_id
		);
		$grade_id = $this->wpdb->get_var($query);

		return absint($grade_id);
	}

	public function upsert_score($lesson_id, $user_id, $score) {
		$lesson_id = absint($lesson_id);
		$user_id = absint($user_id);
		$score = floatval($score);
		if ($lesson_id <= 0 || $user_id <= 0) {
			return 0;
		}

		$grade_id = $this->get_grade_id($lesson_id, $user_id);
		if ($grade_id > 0) {
			$this->wpdb->update(
				$this->grades_table,
				array('score' => $score),
				array('id' => $grade_id),
				array('%f'),
				array('%d')
			);

			return $grade_id;
		}

		$this->wpdb->insert(
			$this->grades_table,
			array(
				'lesson_id' => $lesson_id,
				'user_id' => $user_id,
				'score' => $score,
			),
			array('%d', '%d', '%f')
		);

		return absint($this->wpdb->insert_id);
	}
}
