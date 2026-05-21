<?php

class TL_AI_Content_Request_Helper {

	const ORIGINAL_CONTENT_META_KEY = 'lxp_lesson_original_content';

	/**
	 * Validate a generation request.
	 *
	 * @param  int         $post_id
	 * @param  string      $lesson_content
	 * @param  bool        $enforce_capability
	 * @return true|WP_Error
	 */
	public static function validate_generation_request( $post_id, $lesson_content, $enforce_capability = false ) {
		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}

		if ( empty( trim( (string) $lesson_content ) ) ) {
			return new WP_Error( 'missing_content', 'lesson_content cannot be empty.', array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Persist the latest pre-AI lesson content from the editor request.
	 *
	 * @param int    $post_id
	 * @param string $lesson_content
	 */
	public static function persist_generation_input_backup( $post_id, $lesson_content ) {
		if ( $post_id <= 0 ) {
			return;
		}

		update_post_meta(
			$post_id,
			self::ORIGINAL_CONTENT_META_KEY,
			wp_unslash( (string) $lesson_content )
		);
	}

	/**
	 * Return the latest pre-AI lesson content that was backed up before generation.
	 *
	 * @param  WP_REST_Request $request  Required param: post_id (int).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_original_content( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}

		$original = get_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, true );

		if ( empty( $original ) ) {
			return new WP_Error( 'no_backup', 'No original content backup found for this lesson.', array( 'status' => 404 ) );
		}

		return rest_ensure_response( array( 'content' => $original ) );
	}
}
