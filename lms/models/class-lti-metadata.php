<?php

class TL_LTI_Metadata {
	public $lti_post_attr_id = '';
	public $lti_content_title = '';
	public $lti_tool_code = '';
	public $lti_custom_attr = '';
	public $lti_tool_url = '';
	public $lti_tool_id = '';
	public $lti_resource_link_id = '';
	public $lti_deployment_id = '';
	public $lti_post_score = '';
	public $lti_post_attr = '';
	public $lti_content_id = '';
	public $lti_course_id = '';

	public function __construct($values = array()) {
		foreach ($this->to_meta_array() as $key => $value) {
			if (isset($values[$key])) {
				$this->$key = is_scalar($values[$key]) ? (string) $values[$key] : '';
			}
		}
	}

	public function to_meta_array() {
		return array(
			'lti_post_attr_id' => $this->lti_post_attr_id,
			'lti_content_title' => $this->lti_content_title,
			'lti_tool_code' => $this->lti_tool_code,
			'lti_custom_attr' => $this->lti_custom_attr,
			'lti_tool_url' => $this->lti_tool_url,
			'lti_tool_id' => $this->lti_tool_id,
			'lti_resource_link_id' => $this->lti_resource_link_id,
			'lti_deployment_id' => $this->lti_deployment_id,
			'lti_post_score' => $this->lti_post_score,
			'lti_post_attr' => $this->lti_post_attr,
			'lti_content_id' => $this->lti_content_id,
			'lti_course_id' => $this->lti_course_id,
		);
	}
}