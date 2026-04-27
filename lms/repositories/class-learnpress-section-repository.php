<?php

class TL_LearnPress_Section_Repository implements TL_Section_Repository_Interface {

	private $wpdb;
	private $sections_table;
	private $section_items_table;

	public function __construct($wpdb_instance = null) {
		global $wpdb;
		$this->wpdb = $wpdb_instance ? $wpdb_instance : $wpdb;
		$this->sections_table = $this->wpdb->prefix . 'learnpress_sections';
		$this->section_items_table = $this->wpdb->prefix . 'learnpress_section_items';
	}

	private function extract_value_from_section($section, $keys = array()) {
		if (empty($section) || !is_array($keys) || empty($keys)) {
			return '';
		}

		foreach ($keys as $key) {
			if (is_array($section) && isset($section[$key]) && $section[$key] !== '') {
				return $section[$key];
			}

			if (is_object($section) && isset($section->{$key}) && $section->{$key} !== '') {
				return $section->{$key};
			}
		}

		return '';
	}

	private function learnpress_section_by_item($item_id) {
		$item_id = absint($item_id);
		if ($item_id <= 0) {
			return null;
		}

		if (function_exists('learn_press_get_item_section')) {
			return learn_press_get_item_section($item_id);
		}

		if (!class_exists('LP_Section_DB')) {
			return null;
		}

		$db_instance = null;
		if (method_exists('LP_Section_DB', 'getInstance')) {
			$db_instance = LP_Section_DB::getInstance();
		} elseif (method_exists('LP_Section_DB', 'instance')) {
			$db_instance = LP_Section_DB::instance();
		} elseif (method_exists('LP_Section_DB', 'get_instance')) {
			$db_instance = LP_Section_DB::get_instance();
		}

		if (!$db_instance) {
			return null;
		}

		if (method_exists($db_instance, 'get_section_by_item_id')) {
			return $db_instance->get_section_by_item_id($item_id);
		}

		if (method_exists($db_instance, 'get_section_by_item')) {
			return $db_instance->get_section_by_item($item_id);
		}

		return null;
	}

	private function learnpress_course_id_by_item($item_id) {
		$item_id = absint($item_id);
		if ($item_id <= 0) {
			return 0;
		}

		if (function_exists('learn_press_get_item_course')) {
			$course = learn_press_get_item_course($item_id);
			$course_id = $this->extract_value_from_section($course, array('ID', 'id', 'post_id', 'course_id'));
			if (!empty($course_id)) {
				return absint($course_id);
			}
		}

		$section = $this->learnpress_section_by_item($item_id);
		$course_id = $this->extract_value_from_section($section, array('section_course_id', 'course_id'));
		if (!empty($course_id)) {
			return absint($course_id);
		}

		return 0;
	}

	public function get_sections_by_section_course_id($course_id) {
		$query = $this->wpdb->prepare(
			"SELECT section_id, section_name FROM {$this->sections_table} WHERE section_course_id = %d",
			intval($course_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function get_lessons_by_section_id($section_id) {
		$query = $this->wpdb->prepare(
			"SELECT p.ID, p.post_title
			 FROM {$this->wpdb->prefix}posts AS p
			 INNER JOIN {$this->section_items_table} AS si ON p.ID = si.item_id
			 WHERE si.section_id = %d",
			intval($section_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function get_lesson_id_by_course_and_lti_attr($course_id, $attr_id) {
		$course_id = absint($course_id);
		$attr_id = sanitize_text_field((string) $attr_id);

		if ($course_id <= 0 || $attr_id === '') {
			return 0;
		}

		$sections = $this->get_sections_by_section_course_id($course_id);
		if (empty($sections)) {
			$sections = $this->get_sections_by_course_id($course_id);
		}

		foreach ((array) $sections as $section) {
			$section_id = absint($this->extract_value_from_section($section, array('section_id', 'id')));
			if ($section_id <= 0) {
				continue;
			}

			$items = $this->get_lessons_by_section_id($section_id);
			foreach ((array) $items as $item) {
				$item_id = absint($this->extract_value_from_section($item, array('ID', 'id', 'item_id')));
				if ($item_id <= 0) {
					continue;
				}

				$item_post = get_post($item_id);
				if (empty($item_post) || !isset($item_post->post_type) || $item_post->post_type !== TL_LESSON_CPT) {
					continue;
				}

				if ((string) get_post_meta($item_id, 'lti_post_attr_id', true) === $attr_id) {
					return $item_id;
				}
			}
		}

		return 0;
	}

	public function get_course_id_by_item_id($item_id) {
		$item_id = intval($item_id);

		$learnpress_course_id = $this->learnpress_course_id_by_item($item_id);
		if ($learnpress_course_id > 0) {
			return $learnpress_course_id;
		}

		$query = $this->wpdb->prepare(
			"SELECT s.section_course_id
			 FROM {$this->sections_table} s
			 INNER JOIN {$this->section_items_table} si ON s.section_id = si.section_id
			 WHERE si.item_id = %d
			 LIMIT 1",
			$item_id
		);

		$course_id = $this->wpdb->get_var($query);
		if (!empty($course_id)) {
			return absint($course_id);
		}

		$query = $this->wpdb->prepare(
			"SELECT s.course_id
			 FROM {$this->sections_table} s
			 INNER JOIN {$this->section_items_table} si ON s.section_id = si.section_id
			 WHERE si.item_id = %d
			 LIMIT 1",
			$item_id
		);

		$course_id = $this->wpdb->get_var($query);

		return absint($course_id);
	}

	public function get_sections_by_course_id($course_id) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->sections_table} WHERE course_id = %d",
			intval($course_id)
		);

		return $this->wpdb->get_results($query);
	}

	public function get_section_by_id($section_id) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->sections_table} WHERE id = %d",
			intval($section_id)
		);

		$records = $this->wpdb->get_results($query);
		if (is_array($records) && !empty($records) && isset($records[0]->content)) {
			$records[0]->content = stripslashes($records[0]->content);
		}

		return $records;
	}

	public function update_section($section_id, $title, $content, $sort) {
		return $this->wpdb->update(
			$this->sections_table,
			array(
				'content' => $content,
				'title' => $title,
				'sort' => intval($sort),
			),
			array('id' => intval($section_id)),
			array('%s', '%s', '%d'),
			array('%d')
		);
	}

	public function create_section($course_id, $title, $content, $sort) {
		$this->wpdb->insert(
			$this->sections_table,
			array(
				'course_id' => intval($course_id),
				'title' => $title,
				'type' => 'content',
				'content' => $content,
				'sort' => intval($sort),
			),
			array('%d', '%s', '%s', '%s', '%d')
		);

		return $this->wpdb->insert_id;
	}

	public function delete_section($section_id) {
		return $this->wpdb->delete(
			$this->sections_table,
			array('id' => intval($section_id)),
			array('%d')
		);
	}

	public function get_section_name_by_item_id($item_id) {
		$section = $this->learnpress_section_by_item($item_id);
		$section_name = $this->extract_value_from_section($section, array('section_name', 'title', 'name'));
		if ($section_name !== '') {
			return $section_name;
		}

		$query = $this->wpdb->prepare(
			"SELECT s.section_name
			 FROM {$this->sections_table} s
			 INNER JOIN {$this->section_items_table} si ON s.section_id = si.section_id
			 WHERE si.item_id = %d",
			intval($item_id)
		);

		return $this->wpdb->get_var($query);
	}
}
