<?php

interface TL_Grades_Repository_Interface {
	public function get_grade_id($lesson_id, $user_id);
	public function upsert_score($lesson_id, $user_id, $score);
}
