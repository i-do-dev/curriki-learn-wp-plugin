<?php

class TL_LTI_Metadata_Repository {
	public function get($post_id) {
		$post_id = absint($post_id);
		$metadata = array();
		$model = new TL_LTI_Metadata();

		foreach (array_keys($model->to_meta_array()) as $meta_key) {
			$metadata[$meta_key] = get_post_meta($post_id, $meta_key, true);
		}

		return new TL_LTI_Metadata($metadata);
	}

	public function update($post_id, TL_LTI_Metadata $metadata) {
		$post_id = absint($post_id);

		foreach ($metadata->to_meta_array() as $meta_key => $value) {
			update_post_meta($post_id, $meta_key, $value);
		}
	}

	public function update_from_array($post_id, $metadata) {
		$current_metadata = $this->get($post_id)->to_meta_array();
		$this->update(absint($post_id), new TL_LTI_Metadata(array_merge($current_metadata, $metadata)));
	}
}