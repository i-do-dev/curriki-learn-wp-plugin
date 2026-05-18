<?php

require_once __DIR__ . '/class-ai-content-request-helper.php';
require_once __DIR__ . '/class-ai-content-template-library.php';
require_once __DIR__ . '/class-ai-content-standard-generator.php';
require_once __DIR__ . '/class-ai-content-block-generator.php';

/**
 * REST API endpoints for AI-powered lesson content generation.
 *
 * Routes (namespace lms/v1):
 *   POST /lesson/ai-content        - Generate formatted HTML lesson from existing content.
 *   POST /lesson/ai-content-blocks - Generate formatted HTML lesson from ordered author blocks.
 *   GET  /lesson/original-content  - Retrieve the pre-generation backup of lesson content.
 */
class Rest_Lxp_AI_Content {

	const ORIGINAL_CONTENT_META_KEY = TL_AI_Content_Request_Helper::ORIGINAL_CONTENT_META_KEY;

	/**
	 * Register AI content routes.
	 */
	public static function init() {
		register_rest_route(
			'lms/v1',
			'/lesson/ai-content',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( 'Rest_Lxp_AI_Content', 'generate_lesson_content' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'lms/v1',
			'/lesson/ai-content-blocks',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( 'Rest_Lxp_AI_Content', 'generate_blocks_content' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'lms/v1',
			'/lesson/original-content',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Rest_Lxp_AI_Content', 'get_original_content' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Generate standard AI lesson content.
	 *
	 * @param  WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_lesson_content( WP_REST_Request $request ) {
		return TL_AI_Content_Standard_Generator::generate_lesson_content( $request );
	}

	/**
	 * Generate block-mode AI lesson content.
	 *
	 * @param  WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_blocks_content( WP_REST_Request $request ) {
		return TL_AI_Content_Block_Generator::generate_blocks_content( $request );
	}

	/**
	 * Return the original pre-generation lesson content.
	 *
	 * @param  WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_original_content( WP_REST_Request $request ) {
		return TL_AI_Content_Request_Helper::get_original_content( $request );
	}

	/**
	 * Parse author content into ordered block segments.
	 *
	 * @param  string $content
	 * @return array<int,array<string,string>>
	 */
	public static function parse_block_markers( $content ) {
		return TL_AI_Content_Block_Generator::parse_block_markers( $content );
	}

	/**
	 * Provide a catalog of supported blocks for admin reference UI.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function get_block_catalog() {
		return TL_AI_Content_Block_Generator::get_block_catalog();
	}
}
