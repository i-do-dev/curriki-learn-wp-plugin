<?php

/**
 * REST API endpoints for AI-powered lesson content generation.
 *
 * Routes (namespace lms/v1):
 *   POST /lesson/ai-content        — Generate formatted HTML lesson from existing content.
 *   GET  /lesson/original-content  — Retrieve the pre-generation backup of lesson content.
 */
class Rest_Lxp_AI_Content {

	const ORIGINAL_CONTENT_META_KEY = 'lxp_lesson_original_content';

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

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

	// -------------------------------------------------------------------------
	// Callbacks
	// -------------------------------------------------------------------------

	/**
	 * Generate a richly formatted HTML lesson page via AWS Bedrock.
	 *
	 * Backs up the current post_content to meta (once, on first generation)
	 * before returning AI-generated HTML. The caller is responsible for saving
	 * the returned content to post_content — it is not auto-saved here.
	 *
	 * @param  WP_REST_Request $request  Required params: post_id (int), lesson_content (string).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_lesson_content( WP_REST_Request $request ) {
		$post_id        = absint( $request->get_param( 'post_id' ) );
		$lesson_content = $request->get_param( 'lesson_content' );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}

		if ( empty( trim( $lesson_content ) ) ) {
			return new WP_Error( 'missing_content', 'lesson_content cannot be empty.', array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to edit this lesson.', array( 'status' => 403 ) );
		}

		// Back up the current post_content only on the FIRST generation so the
		// true original is always preserved across subsequent AI generations.
		$existing_backup = get_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, true );
		if ( empty( $existing_backup ) ) {
			$post = get_post( $post_id );
			if ( $post && ! empty( $post->post_content ) ) {
				update_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, $post->post_content );
			}
		}

		$prompt        = self::build_user_message( sanitize_textarea_field( wp_unslash( $lesson_content ) ) );
		$system_prompt = self::build_system_prompt();
		$result        = TL_AWS_Bedrock_Client::invoke_bedrock( $prompt, $system_prompt );

		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
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

		return rest_ensure_response( array( 'content' => $result ) );
	}

	/**
	 * Return the original lesson content that was backed up before the first AI generation.
	 *
	 * @param  WP_REST_Request $request  Required param: post_id (int).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_original_content( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to access this lesson.', array( 'status' => 403 ) );
		}

		$original = get_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, true );

		if ( empty( $original ) ) {
			return new WP_Error( 'no_backup', 'No original content backup found for this lesson.', array( 'status' => 404 ) );
		}

		return rest_ensure_response( array( 'content' => $original ) );
	}

	// -------------------------------------------------------------------------
	// Prompt builder
	// -------------------------------------------------------------------------

	/**
	 * System-level instruction passed to the Converse API.
	 * Tells Claude how to behave and what format to produce.
	 *
	 * @return string
	 */
	private static function build_system_prompt() {
		return 'You are an expert Instructional Designer. '
			. 'Transform the provided lesson text into a high-end HTML lesson page. '
			. 'CRITICAL: Output ONLY the raw HTML code — no markdown, no code fences, no explanation text. '
			. 'The HTML must start with <div class="lp-ai-lesson-template"> and end with </div>. '
			. 'Use the following six sections exactly as structured in the template the user provides: '
			. 'Hero Header, Lesson Overview, Learning Goals, Key Ideas, Classroom Example, and Check for Understanding. '
			. 'Preserve all inline styles verbatim. Use #442e66 for heading colours and #ffb606 for accent borders. '
			. 'Keep CSS variable references exactly as written: var(--lp-primary-color, #ffb606) and var(--lp-secondary-color, #442e66). '
			. 'Replace every [PLACEHOLDER] with content that is tightly relevant to the original lesson topic.';
	}

	/**
	 * Build the user-turn message containing the lesson content and HTML template.
	 *
	 * @param  string $lesson_content  Sanitized raw lesson content.
	 * @return string
	 */
	private static function build_user_message( $lesson_content ) {
		$template = <<<'HTML'
<div class="lp-ai-lesson-template" style="max-width: 980px; margin: 0 auto;">

<!-- Hero Header -->
<section style="position: relative; margin-bottom: 24px; border-radius: 18px; overflow: hidden; min-height: 320px; background-image: linear-gradient(rgba(32, 22, 55, 0.55), rgba(32, 22, 55, 0.72)), url('https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=1200'); background-size: cover; background-position: center; box-shadow: 0 12px 28px rgba(0,0,0,.10);">
  <div style="padding: 42px 30px; display: flex; align-items: flex-end; min-height: 320px;">
    <div style="max-width: 720px; color: #ffffff;">
      <p style="margin: 0 0 10px; display: inline-block; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 0.82rem; font-weight: bold; letter-spacing: .04em; text-transform: uppercase;">[PILL_LABEL]</p>
      <h2 style="margin: 0 0 12px; color: #ffffff;">[HERO_TITLE]</h2>
      <p style="margin: 0; font-size: 1.05rem; color: rgba(255,255,255,.95);">[HERO_SUBTITLE]</p>
    </div>
  </div>
</section>

<!-- Lesson Overview -->
<section class="has-very-light-gray-to-cyan-bluish-gray-gradient-background" style="padding: 24px; border-radius: 16px; margin-bottom: 24px;">
  <h3 style="margin-top: 0; color: var(--lp-secondary-color, #442e66);">Lesson Overview</h3>
  <p>[OVERVIEW_PARAGRAPH]</p>
  <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
    <tbody>
      <tr>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Audience</strong><br>[AUDIENCE]</td>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Estimated Time</strong><br>[ESTIMATED_TIME]</td>
      </tr>
      <tr>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Lesson Focus</strong><br>[LESSON_FOCUS]</td>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Takeaway</strong><br>[TAKEAWAY]</td>
      </tr>
    </tbody>
  </table>
</section>

<!-- Learning Goals -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Learning Goals</h3>
  <div class="has-white-background-color" style="padding: 22px; border-left: 6px solid var(--lp-primary-color, #ffb606); border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,.04);">
    <p style="margin-top: 0;"><strong>By the end of this lesson, participants will be able to:</strong></p>
    <ul style="margin-bottom: 0;">
      <li>[GOAL_1]</li>
      <li>[GOAL_2]</li>
      <li>[GOAL_3]</li>
    </ul>
  </div>
</section>

<!-- Key Ideas -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Key Ideas</h3>
  <div class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 14px; margin-bottom: 16px;">
    <h4 style="margin-top: 0;">1. [KEY_IDEA_1_TITLE]</h4>
    <p style="margin-bottom: 0;">[KEY_IDEA_1_BODY]</p>
  </div>
  <div class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 14px; margin-bottom: 16px;">
    <h4 style="margin-top: 0;">2. [KEY_IDEA_2_TITLE]</h4>
    <p style="margin-bottom: 0;">[KEY_IDEA_2_BODY]</p>
  </div>
  <div class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 14px;">
    <h4 style="margin-top: 0;">3. [KEY_IDEA_3_TITLE]</h4>
    <p style="margin-bottom: 0;">[KEY_IDEA_3_BODY]</p>
  </div>
</section>

<!-- Classroom Example -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Classroom Example</h3>
  <blockquote style="margin: 0; padding: 20px 22px; background: rgba(255,182,6,.12); border-left: 5px solid var(--lp-primary-color, #ffb606); border-radius: 12px;">
    <p style="margin: 0 0 10px;"><strong>Scenario:</strong> [EXAMPLE_SCENARIO]</p>
    <p style="margin: 0;"><strong>Application:</strong> [EXAMPLE_APPLICATION]</p>
  </blockquote>
</section>

<!-- Check for Understanding -->
<section class="has-white-background-color" style="margin-top: 0; padding: 0; border: 1px solid rgba(68,46,102,.12); border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.05);">
  <div style="padding: 20px 24px; border-bottom: 1px solid rgba(68,46,102,.10); background: linear-gradient(135deg, rgba(68,46,102,.96), rgba(44,30,80,.96)); border-radius: 16px 16px 0 0; color: #ffffff;">
    <p style="display: inline-block; margin: 0 0 10px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 0.82rem; font-weight: bold; letter-spacing: .04em; text-transform: uppercase;">Check for Understanding</p>
    <h3 style="margin: 0 0 8px; color: #ffffff;">Quick Knowledge Check</h3>
    <p style="margin: 0; color: rgba(255,255,255,.92);">Choose the best answer based on the lesson.</p>
  </div>
  <div style="padding: 24px; background: linear-gradient(135deg, rgba(244,247,250,1), rgba(236,241,246,1));">
    <p style="margin-top: 0; margin-bottom: 18px; color: #222222; font-size: 1.05rem;"><strong>[QUIZ_QUESTION]</strong></p>
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(247,244,250,.96));">[ANSWER_A]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(248,246,252,.96), rgba(240,236,247,.98));">[ANSWER_B]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(247,244,250,.96));">[ANSWER_C]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(248,246,252,.96), rgba(240,236,247,.98));">[ANSWER_D]</div>
    </div>
    <div style="margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: linear-gradient(135deg, rgba(255,245,203,.95), rgba(255,236,170,.92)); border-left: 5px solid var(--lp-primary-color, #ffb606);">
      <p style="margin: 0;"><strong>Correct answer:</strong> [CORRECT_ANSWER_EXPLANATION]</p>
    </div>
  </div>
</section>

</div>
HTML;

		return "Please transform this lesson text into the HTML template below.\n\n"
			. "ORIGINAL LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE TO FILL IN:\n{$template}";
	}
}
