<?php

class TL_AI_Content_Standard_Generator {

	/**
	 * Generate a richly formatted HTML lesson page via AWS Bedrock.
	 *
	 * Backs up the current post_content to meta (once, on first generation)
	 * before returning AI-generated HTML. The caller is responsible for saving
	 * the returned content to post_content - it is not auto-saved here.
	 *
	 * @param  WP_REST_Request $request Required params: post_id (int), lesson_content (string).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_lesson_content( WP_REST_Request $request ) {
		$post_id        = absint( $request->get_param( 'post_id' ) );
		$lesson_content = $request->get_param( 'lesson_content' );

		$validation = TL_AI_Content_Request_Helper::validate_generation_request( $post_id, $lesson_content );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		TL_AI_Content_Request_Helper::maybe_backup_original_content( $post_id );

		$lesson_title      = get_the_title( $post_id );
		$sanitized_content = sanitize_textarea_field( wp_unslash( $lesson_content ) );
		$template_id       = TL_AI_Content_Template_Library::classify_template( $lesson_title, $sanitized_content );
		$template_html     = TL_AI_Content_Template_Library::get_template( $template_id );
		$component_count   = ( '15' === $template_id ) ? TL_AI_Content_Template_Library::detect_component_count( $sanitized_content ) : 0;
		$system_prompt     = TL_AI_Content_Template_Library::build_template_system_prompt( $lesson_title );
		$prompt            = TL_AI_Content_Template_Library::build_template_user_message( $sanitized_content, $lesson_title, $template_html, $component_count );
		$max_tokens        = ( '15' === $template_id ) ? 8192 : 4096;
		$result            = TL_AWS_Bedrock_Client::invoke_bedrock( $prompt, $system_prompt, $max_tokens );

		if ( is_wp_error( $result ) ) {
			$error_code  = $result->get_error_code();
			$http_status = 502;
			if ( 'bedrock_access_denied' === $error_code ) {
				$http_status = 403;
			} elseif ( 'bedrock_validation_error' === $error_code ) {
				$http_status = 400;
			} elseif ( 'bedrock_model_not_found' === $error_code ) {
				$http_status = 404;
			}
			return new WP_Error( $error_code, $result->get_error_message(), array( 'status' => $http_status ) );
		}

		return rest_ensure_response(
			array(
				'content'     => $result,
				'template_id' => $template_id,
			)
		);
	}
}
