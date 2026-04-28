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
		/*
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to edit this lesson.', array( 'status' => 403 ) );
		}
		*/
		// Back up the current post_content only on the FIRST generation so the
		// true original is always preserved across subsequent AI generations.
		$existing_backup = get_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, true );
		if ( empty( $existing_backup ) ) {
			$post = get_post( $post_id );
			if ( $post && ! empty( $post->post_content ) ) {
				update_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, $post->post_content );
			}
		}

		// Fetch the lesson title for richer prompt context.
		$lesson_title = get_the_title( $post_id );

		// Detect lesson type from the original (pre-AI) content and title.
		$sanitized_content = sanitize_textarea_field( wp_unslash( $lesson_content ) );
		$lesson_type       = self::detect_lesson_type( $post_id, $sanitized_content );

		// Choose prompt builders based on detected lesson type.
		if ( 'workbook' === $lesson_type ) {
			$prompt        = self::build_workbook_user_message( $sanitized_content, $lesson_title );
			$system_prompt = self::build_workbook_system_prompt( $lesson_title );

			$result = TL_AWS_Bedrock_Client::invoke_bedrock( $prompt, $system_prompt );

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

			return rest_ensure_response( array(
				'content'     => $result,
				'lesson_type' => $lesson_type,
			) );
		}

		// Standard lesson — two-pass: classify template, then generate.
		// Pass 1: Ask Bedrock which of the 15 structural templates best fits this lesson.
		$template_id = self::classify_template( $lesson_title, $sanitized_content );

		// Pass 2: Fill the selected template with lesson-specific content.
		$template_html = self::get_template( $template_id );
		$system_prompt = self::build_template_system_prompt( $lesson_title );
		$prompt        = self::build_template_user_message( $sanitized_content, $lesson_title, $template_html );

		$result = TL_AWS_Bedrock_Client::invoke_bedrock( $prompt, $system_prompt );

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

		return rest_ensure_response( array(
			'content'     => $result,
			'lesson_type' => $lesson_type,
			'template_id' => $template_id,
		) );
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
		/*
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to access this lesson.', array( 'status' => 403 ) );
		}
		*/
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
	private static function build_system_prompt( $lesson_title = '' ) {
		$title_instruction = ! empty( $lesson_title )
			? ' Use "' . $lesson_title . '" as the primary heading for the Hero Header section.'
			: '';

		return 'You are an expert Instructional Designer. '
			. 'Transform the provided lesson text into a high-end HTML lesson page.' . $title_instruction . ' '
			. 'CRITICAL: Output ONLY the raw HTML code — no markdown, no code fences, no explanation text. '
			. 'The HTML must start with <div class="lp-ai-lesson-template"> and end with </div>. '
			. 'Use the following six sections exactly as structured in the template the user provides: '
			. 'Hero Header, Lesson Overview, Learning Goals, Key Ideas, Classroom Example, and Check for Understanding. '
			. 'Preserve all inline styles verbatim. Use #442e66 for heading colours and #ffb606 for accent borders. '
			. 'Keep CSS variable references exactly as written: var(--lp-primary-color, #ffb606) and var(--lp-secondary-color, #442e66). '
			. 'Replace every [PLACEHOLDER] with content that is tightly relevant to the original lesson topic. '
			. 'READING TIME CONSTRAINT: The total reading time of the generated lesson MUST NOT exceed 15 minutes. '
			. 'Calibrate the depth and length of each section (number of goals, key ideas, example detail, quiz complexity) '
			. 'based on the complexity and scope of the original content so that a learner can read and complete the lesson in 15 minutes or less. '
			. 'Set the Estimated Time metadata field accordingly (e.g. "8 min", "12 min" — never more than "15 min").';
	}

	/**
	 * Build the user-turn message containing the lesson content and HTML template.
	 *
	 * @param  string $lesson_content  Sanitized raw lesson content.
	 * @return string
	 */
	private static function build_user_message( $lesson_content, $lesson_title = '' ) {
		$template = <<<'HTML'
<div class="lp-ai-lesson-template" style="max-width: 980px; margin: 0 auto;">

<!-- Hero Header -->
<section style="position: relative; margin-bottom: 24px; border-radius: 18px; overflow: hidden; min-height: 320px; background-image: linear-gradient(rgba(32, 22, 55, 0.55), rgba(32, 22, 55, 0.72)), url('https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=1200'); background-size: cover; background-position: center; box-shadow: 0 12px 28px rgba(0,0,0,.10);">
  <div style="padding: 42px 30px; display: flex; align-items: flex-end; min-height: 320px;">
    <div style="max-width: 720px; color: #ffffff;">
      <p style="margin: 0 0 10px; display: inline-block; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 0.82rem; font-weight: bold; letter-spacing: .04em; text-transform: uppercase;">[Short Tagline]</p>
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
	  <li>[GOAL_N]</li>
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
  <div class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 14px;">
    <h4 style="margin-top: 0;">N. [KEY_IDEA_N_TITLE]</h4>
    <p style="margin-bottom: 0;">[KEY_IDEA_N_BODY]</p>
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
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(248,246,252,.96), rgba(240,236,247,.98));">[ANSWER_N]</div>
    </div>
    <div style="margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: linear-gradient(135deg, rgba(255,245,203,.95), rgba(255,236,170,.92)); border-left: 5px solid var(--lp-primary-color, #ffb606);">
      <p style="margin: 0;"><strong>Correct answer:</strong> [CORRECT_ANSWER_EXPLANATION]</p>
    </div>
  </div>
</section>

</div>
HTML;

		$title_line = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';

		return "Understand the following lesson content and transform it suitable for effective teaching, while calculating parameters appropriate for example estimated time to complete, key ideas, learning goals, and a quiz question.\n\n"
			. "IMPORTANT: Transformed lesson content should not deviate from the original meaning and boundaries. The output must be a richly formatted HTML lesson page that strictly follows the structure and styles of the provided template. "
			. "READING TIME CONSTRAINT: The total reading time of the generated lesson MUST NOT exceed 15 minutes. Calibrate the number of bullet points, key ideas, and the depth of explanations based on the complexity of the original content — simpler topics should result in shorter output. "
			. "Transform newly generated lesson text into the HTML template below.\n\n"
			. $title_line
			. "ORIGINAL LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE TO FILL IN:\n{$template}";
	}

	// -------------------------------------------------------------------------
	// 15-Template system
	// -------------------------------------------------------------------------

	/**
	 * Topic-agnostic structural manifest.
	 * Each entry describes the STRUCTURAL pattern — not the subject matter.
	 */
	private static $template_manifest = array(
		'01' => 'Stats/evidence grid + numbered consequence cards + myth-vs-reality two-column comparison. Use when data establishes urgency and documents consequences.',
		'02' => 'Goal-alignment cards + strategy-to-mission mapping. Use when learners should articulate why a concept exists and how it connects to their goals.',
		'03' => 'Role-split two-column + opportunity/boundary table. Use when two groups or perspectives apply the same concept differently.',
		'04' => 'Compliance rule cards + do/never list + violation-and-risk two-column. Use for hard rules, legal obligations, or ethical non-negotiables.',
		'05' => 'Criteria-based evaluation grid + tier/access decision cards. Use when teaching how to assess, select, or vet options against standards.',
		'06' => 'Adoption-stats block + supportive-vs-problematic two-column + gap-warning dark block. Use when contrasting current reality with desired behaviour to expose a gap.',
		'07' => 'Failure-reason icon grid + alert block + alternatives row. Use when a common default approach is flawed and needs replacing with better alternatives.',
		'08' => 'Curated option cards + access/tier grid. Use when introducing a specific approved set of tools, resources, or categorised choices.',
		'09' => 'Audience-split cards + domain grid + stability/flexibility dark block. Use when the same concept applies differently across contexts, levels, or domains.',
		'10' => 'Three-tier framework cards + implementation icon cards + goal-matching. Use for multi-level classification or permission systems with distinct tiers.',
		'11' => 'Standards-definition block + proper/improper conduct comparison. Use when defining acceptable vs unacceptable conduct or disclosure.',
		'12' => 'Cognitive-framing card + augments/bypasses contrast + visible-thinking row. Use when analysing how a practice affects quality of thinking.',
		'13' => 'Audience-specific message cards + communication-channel grid. Use when the same idea must be communicated differently to different stakeholders.',
		'14' => 'Bridge-mapping grid + alignment checklist. Use when connecting new guidance to an existing framework or standard learners already know.',
		'15' => 'Numbered component grid + writing-guidelines dark block + living-document cycle. Use as a capstone/synthesis template that consolidates all prior learning into a draft document.',
	);

	/**
	 * Pass 1: Ask Bedrock which of the 15 structural templates best fits this lesson.
	 *
	 * @param  string $lesson_title    The lesson title.
	 * @param  string $lesson_content  Sanitized lesson content.
	 * @return string Template ID ('01' – '15'). Falls back to '01' on error.
	 */
	private static function classify_template( $lesson_title, $lesson_content ) {
		$manifest_lines = array();
		foreach ( self::$template_manifest as $id => $description ) {
			$manifest_lines[] = "{$id}: {$description}";
		}
		$manifest_text = implode( "\n", $manifest_lines );

		$system = 'You are a lesson template classifier. '
			. 'Given a lesson title and content, return ONLY the two-digit number (01–15) of the best-matching structural template. '
			. 'Output nothing else — no explanation, no period, no whitespace.';

		$user = "LESSON TITLE: {$lesson_title}\n\n"
			. "LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE MANIFEST:\n{$manifest_text}\n\n"
			. "Which template number (01–15) best matches the structural needs of this lesson? Reply with only the two-digit number.";

		$result = TL_AWS_Bedrock_Client::invoke_bedrock( $user, $system );

		if ( is_wp_error( $result ) ) {
			return '01';
		}

		$id = trim( $result );
		// Validate: must be exactly '01' through '15'.
		if ( preg_match( '/^(0[1-9]|1[0-5])$/', $id ) ) {
			return $id;
		}
		// Try to extract a number from a longer response.
		if ( preg_match( '/\b(0[1-9]|1[0-5])\b/', $id, $matches ) ) {
			return $matches[1];
		}

		return '01';
	}

	/**
	 * Return the HTML template heredoc for the given template ID.
	 *
	 * Each template:
	 * - Wraps in <div class="lp-ai-lesson-template">
	 * - Uses inline CSS only (no Tailwind, no Font Awesome)
	 * - Uses var(--lp-primary-color,#ffb606) and var(--lp-secondary-color,#442e66)
	 * - Uses checkmarks as ✓ in styled <span> elements
	 * - Ends with a Capstone Activity section containing [Capstone Box] sentinel
	 *
	 * @param  string $template_id  '01' – '15'
	 * @return string  Full HTML template with [PLACEHOLDER] tokens.
	 */
	private static function get_template( $template_id ) {
		switch ( $template_id ) {
			case '02': return self::_tpl_02();
			case '03': return self::_tpl_03();
			case '04': return self::_tpl_04();
			case '05': return self::_tpl_05();
			case '06': return self::_tpl_06();
			case '07': return self::_tpl_07();
			case '08': return self::_tpl_08();
			case '09': return self::_tpl_09();
			case '10': return self::_tpl_10();
			case '11': return self::_tpl_11();
			case '12': return self::_tpl_12();
			case '13': return self::_tpl_13();
			case '14': return self::_tpl_14();
			case '15': return self::_tpl_15();
			default:   return self::_tpl_01();
		}
	}

	/** Section accent bar reused across templates. */
	private static function accent_bar() {
		return '<div style="width:6px;height:30px;background:var(--lp-primary-color,#ffb606);border-radius:3px;display:inline-block;vertical-align:middle;margin-right:12px;"></div>';
	}

	/** Reusable hero header HTML chunk. */
	private static function hero_html() {
		return <<<'HTML'
<!-- Hero Header -->
<section style="position:relative;margin-bottom:24px;border-radius:18px;overflow:hidden;min-height:280px;background:linear-gradient(135deg,#442e66,#2a1a4a);box-shadow:0 10px 30px -10px rgba(68,46,102,.35);">
  <div style="padding:42px 32px;display:flex;align-items:flex-end;min-height:280px;">
    <div style="max-width:700px;color:#ffffff;">
      <p style="margin:0 0 10px;display:inline-block;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.15);font-size:0.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;">[LESSON_TAG]</p>
      <h2 style="margin:0 0 10px;color:#ffffff;font-size:1.75rem;">[LESSON_TITLE]</h2>
      <p style="margin:0;font-size:1rem;color:rgba(255,255,255,.9);">[LESSON_SUBTITLE]</p>
    </div>
  </div>
</section>
HTML;
	}

	/** Reusable metadata table HTML chunk. */
	private static function meta_table_html() {
		return <<<'HTML'
<!-- Overview -->
<section style="padding:22px;border-radius:14px;background:rgba(68,46,102,.04);border:1px solid rgba(68,46,102,.1);margin-bottom:22px;">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">Lesson Overview</h3>
  <p style="margin-bottom:14px;">[OVERVIEW_PARAGRAPH]</p>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="padding:10px 12px;border:1px solid rgba(68,46,102,.12);"><strong>Audience</strong><br>[AUDIENCE]</td>
      <td style="padding:10px 12px;border:1px solid rgba(68,46,102,.12);"><strong>Estimated Time</strong><br>[ESTIMATED_TIME]</td>
    </tr>
    <tr>
      <td style="padding:10px 12px;border:1px solid rgba(68,46,102,.12);"><strong>Focus</strong><br>[LESSON_FOCUS]</td>
      <td style="padding:10px 12px;border:1px solid rgba(68,46,102,.12);"><strong>Takeaway</strong><br>[TAKEAWAY]</td>
    </tr>
  </table>
</section>
HTML;
	}

	/** Reusable capstone section HTML chunk. */
	private static function capstone_html() {
		return <<<'HTML'
<!-- Capstone Activity -->
<section style="margin-top:28px;padding:0;border:1px solid rgba(68,46,102,.12);border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.05);overflow:hidden;">
  <div style="padding:20px 24px;background:linear-gradient(135deg,#442e66,#2a1a4a);color:#fff;">
    <p style="display:inline-block;margin:0 0 8px;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.15);font-size:0.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;">Capstone Activity</p>
    <h3 style="margin:0 0 6px;color:#fff;">Apply What You Learned</h3>
    <p style="margin:0;color:rgba(255,255,255,.88);font-size:0.96rem;">[CAPSTONE_PROMPT]</p>
  </div>
  <div style="padding:22px 24px;background:#faf9fc;">
    <p style="margin:0 0 10px;font-weight:600;color:var(--lp-secondary-color,#442e66);">Your Response:</p>
    <div style="min-height:80px;border:1px solid rgba(68,46,102,.2);border-radius:12px;background:#fff;padding:14px 16px;color:#999;">[Capstone Box]</div>
  </div>
</section>
HTML;
	}

	/** Reusable quiz section HTML chunk. */
	private static function quiz_html() {
		return <<<'HTML'
<!-- Check for Understanding -->
<section style="margin-top:22px;border:1px solid rgba(68,46,102,.12);border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.05);">
  <div style="padding:20px 24px;background:linear-gradient(135deg,rgba(68,46,102,.96),rgba(44,30,80,.96));color:#fff;">
    <p style="display:inline-block;margin:0 0 8px;padding:5px 12px;border-radius:999px;background:rgba(255,255,255,.14);font-size:0.8rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;">Check for Understanding</p>
    <h3 style="margin:0 0 6px;color:#fff;">Quick Knowledge Check</h3>
    <p style="margin:0;color:rgba(255,255,255,.88);">Choose the best answer based on the lesson.</p>
  </div>
  <div style="padding:22px 24px;background:linear-gradient(135deg,rgba(244,247,250,1),rgba(236,241,246,1));">
    <p style="margin:0 0 16px;color:#222;font-size:1.05rem;"><strong>[QUIZ_QUESTION]</strong></p>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
      <div style="padding:12px 14px;border:1px solid rgba(68,46,102,.16);border-radius:12px;background:#fff;">[ANSWER_A]</div>
      <div style="padding:12px 14px;border:1px solid rgba(68,46,102,.16);border-radius:12px;background:rgba(248,246,252,.96);">[ANSWER_B]</div>
      <div style="padding:12px 14px;border:1px solid rgba(68,46,102,.16);border-radius:12px;background:#fff;">[ANSWER_C]</div>
      <div style="padding:12px 14px;border:1px solid rgba(68,46,102,.16);border-radius:12px;background:rgba(248,246,252,.96);">[ANSWER_D]</div>
    </div>
    <div style="margin-top:16px;padding:12px 16px;border-radius:10px;background:rgba(255,245,203,.95);border-left:5px solid var(--lp-primary-color,#ffb606);">
      <p style="margin:0;"><strong>Correct answer:</strong> [CORRECT_ANSWER_EXPLANATION]</p>
    </div>
  </div>
</section>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 01 — Stats grid + consequence cards + myth/reality comparison
	// ---------------------------------------------------------------------------------
	private static function _tpl_01() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Key Statistics -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);margin-bottom:14px;">[STATS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);text-align:center;">
      <div style="font-size:2rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[STAT_1_VALUE]</div>
      <p style="margin:6px 0 0;font-size:0.88rem;color:#555;">[STAT_1_LABEL]</p>
    </div>
    <div style="padding:20px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);text-align:center;">
      <div style="font-size:2rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[STAT_2_VALUE]</div>
      <p style="margin:6px 0 0;font-size:0.88rem;color:#555;">[STAT_2_LABEL]</p>
    </div>
    <div style="padding:20px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);text-align:center;">
      <div style="font-size:2rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[STAT_3_VALUE]</div>
      <p style="margin:6px 0 0;font-size:0.88rem;color:#555;">[STAT_3_LABEL]</p>
    </div>
  </div>
</section>
<!-- Consequences -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CONSEQUENCES_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">1. [CONSEQUENCE_1_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[CONSEQUENCE_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">2. [CONSEQUENCE_2_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[CONSEQUENCE_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">3. [CONSEQUENCE_3_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[CONSEQUENCE_3_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">4. [CONSEQUENCE_4_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[CONSEQUENCE_4_BODY]</p>
    </div>
  </div>
</section>
<!-- Myth vs Reality -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">Myth vs Reality</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Myth</th>
        <th style="padding:12px 14px;background:rgba(255,182,6,.12);border:1px solid rgba(68,46,102,.12);text-align:left;">Reality</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MYTH_1]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[REALITY_1]</td>
      </tr>
      <tr style="background:rgba(68,46,102,.03);">
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MYTH_2]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[REALITY_2]</td>
      </tr>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MYTH_3]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[REALITY_3]</td>
      </tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 02 — Goal-alignment cards + strategy-to-mission mapping
	// ---------------------------------------------------------------------------------
	private static function _tpl_02() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Why This Concept Exists -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[PURPOSE_HEADING]</h3>
  <div style="padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);border-radius:12px;background:#fff;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
    <p style="margin:0;">[PURPOSE_BODY]</p>
  </div>
</section>
<!-- Goal Alignment Cards -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[GOALS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[GOAL_1_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[GOAL_1_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[GOAL_2_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[GOAL_2_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[GOAL_3_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[GOAL_3_BODY]</p>
    </div>
  </div>
</section>
<!-- Strategy-to-Mission Mapping -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[MAPPING_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Strategy / Action</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Mission Connection</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[STRATEGY_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MISSION_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[STRATEGY_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MISSION_2]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[STRATEGY_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[MISSION_3]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 03 — Role-split two-column + opportunity/boundary table
	// ---------------------------------------------------------------------------------
	private static function _tpl_03() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Role Perspectives -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[ROLES_HEADING]</h3>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div style="padding:22px;border-radius:14px;background:#fff;border-top:5px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <h4 style="margin:0 0 12px;color:var(--lp-secondary-color,#442e66);">[ROLE_A_TITLE]</h4>
      <ul style="margin:0;padding-left:18px;">
        <li style="margin-bottom:6px;">[ROLE_A_POINT_1]</li>
        <li style="margin-bottom:6px;">[ROLE_A_POINT_2]</li>
        <li>[ROLE_A_POINT_3]</li>
      </ul>
    </div>
    <div style="padding:22px;border-radius:14px;background:#fff;border-top:5px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <h4 style="margin:0 0 12px;color:var(--lp-secondary-color,#442e66);">[ROLE_B_TITLE]</h4>
      <ul style="margin:0;padding-left:18px;">
        <li style="margin-bottom:6px;">[ROLE_B_POINT_1]</li>
        <li style="margin-bottom:6px;">[ROLE_B_POINT_2]</li>
        <li>[ROLE_B_POINT_3]</li>
      </ul>
    </div>
  </div>
</section>
<!-- Opportunities vs Boundaries -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">Opportunities &amp; Boundaries</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(255,182,6,.12);border:1px solid rgba(68,46,102,.12);text-align:left;">Opportunity</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Boundary / Limitation</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[OPP_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[BOUNDARY_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[OPP_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[BOUNDARY_2]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[OPP_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[BOUNDARY_3]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 04 — Compliance rule cards + do/never list + violation/risk two-column
	// ---------------------------------------------------------------------------------
	private static function _tpl_04() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Compliance Rules -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[RULES_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[RULE_1_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[RULE_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[RULE_2_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[RULE_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[RULE_3_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[RULE_3_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-left:5px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[RULE_4_TITLE]</p>
      <p style="margin:0;font-size:0.9rem;color:#555;">[RULE_4_BODY]</p>
    </div>
  </div>
</section>
<!-- Do vs Never -->
<section style="margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="padding:22px;border-radius:14px;background:#f6fdf6;border:1px solid rgba(34,139,34,.2);">
    <h4 style="margin:0 0 12px;color:#1a7a1a;">Always Do</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [DO_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [DO_2]</li>
      <li><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [DO_3]</li>
    </ul>
  </div>
  <div style="padding:22px;border-radius:14px;background:#fdf6f6;border:1px solid rgba(180,0,0,.2);">
    <h4 style="margin:0 0 12px;color:#b40000;">Never Do</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [NEVER_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [NEVER_2]</li>
      <li><span style="color:#b40000;font-weight:700;">&#10007;</span> [NEVER_3]</li>
    </ul>
  </div>
</section>
<!-- Violations & Risk -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">Violations &amp; Risk</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Violation</th>
        <th style="padding:12px 14px;background:rgba(180,0,0,.07);border:1px solid rgba(68,46,102,.12);text-align:left;">Risk / Consequence</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[VIOLATION_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[RISK_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[VIOLATION_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[RISK_2]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 05 — Criteria evaluation grid + tier/access decision cards
	// ---------------------------------------------------------------------------------
	private static function _tpl_05() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Evaluation Criteria -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CRITERIA_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Criterion</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">What to Look For</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Why It Matters</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:700;">[CRITERION_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[LOOK_FOR_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[WHY_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:700;">[CRITERION_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[LOOK_FOR_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[WHY_2]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:700;">[CRITERION_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[LOOK_FOR_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[WHY_3]</td></tr>
    </tbody>
  </table>
</section>
<!-- Tier Decision Cards -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[TIERS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_1_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_1_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_2_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_2_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid rgba(68,46,102,.4);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_3_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_3_DESC]</p>
    </div>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 06 — Adoption stats + supportive/problematic contrast + gap warning
	// ---------------------------------------------------------------------------------
	private static function _tpl_06() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Adoption Stats -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[ADOPTION_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);text-align:center;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.8rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[ADOPT_STAT_1_VALUE]</div>
      <p style="margin:4px 0 0;font-size:0.85rem;color:#666;">[ADOPT_STAT_1_LABEL]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);text-align:center;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.8rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[ADOPT_STAT_2_VALUE]</div>
      <p style="margin:4px 0 0;font-size:0.85rem;color:#666;">[ADOPT_STAT_2_LABEL]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);text-align:center;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.8rem;font-weight:800;color:var(--lp-primary-color,#ffb606);">[ADOPT_STAT_3_VALUE]</div>
      <p style="margin:4px 0 0;font-size:0.85rem;color:#666;">[ADOPT_STAT_3_LABEL]</p>
    </div>
  </div>
</section>
<!-- Supportive vs Problematic -->
<section style="margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="padding:22px;border-radius:14px;background:#f6fdf6;border:1px solid rgba(34,139,34,.2);">
    <h4 style="margin:0 0 12px;color:#1a7a1a;">Supportive Behaviours</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [SUPPORTIVE_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [SUPPORTIVE_2]</li>
      <li><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [SUPPORTIVE_3]</li>
    </ul>
  </div>
  <div style="padding:22px;border-radius:14px;background:#fdf6f6;border:1px solid rgba(180,0,0,.2);">
    <h4 style="margin:0 0 12px;color:#b40000;">Problematic Behaviours</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [PROBLEMATIC_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [PROBLEMATIC_2]</li>
      <li><span style="color:#b40000;font-weight:700;">&#10007;</span> [PROBLEMATIC_3]</li>
    </ul>
  </div>
</section>
<!-- Gap Warning -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:var(--lp-secondary-color,#442e66);color:#fff;">
  <h3 style="margin-top:0;color:#fff;">[GAP_HEADING]</h3>
  <p style="margin:0;">[GAP_BODY]</p>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 07 — Failure-reason grid + alert block + alternatives row
	// ---------------------------------------------------------------------------------
	private static function _tpl_07() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Why the Default Fails -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[FAILURE_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[FAILURE_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[FAILURE_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[FAILURE_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[FAILURE_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[FAILURE_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[FAILURE_3_BODY]</p>
    </div>
  </div>
</section>
<!-- Alert Block -->
<section style="margin-bottom:22px;padding:20px 22px;border-radius:12px;background:rgba(255,182,6,.1);border-left:6px solid var(--lp-primary-color,#ffb606);">
  <p style="margin:0;font-weight:700;">[ALERT_HEADING]</p>
  <p style="margin:8px 0 0;">[ALERT_BODY]</p>
</section>
<!-- Better Alternatives -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[ALTERNATIVES_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[ALT_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[ALT_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[ALT_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[ALT_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[ALT_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[ALT_3_BODY]</p>
    </div>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 08 — Curated option cards + access/tier grid
	// ---------------------------------------------------------------------------------
	private static function _tpl_08() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Curated Options -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[OPTIONS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[OPTION_1_NAME]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[OPTION_1_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[OPTION_2_NAME]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[OPTION_2_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[OPTION_3_NAME]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[OPTION_3_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[OPTION_4_NAME]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[OPTION_4_DESC]</p>
    </div>
  </div>
</section>
<!-- Access / Tier Grid -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[ACCESS_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Option / Category</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Access Level</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Notes</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_ITEM_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_LEVEL_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_NOTE_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_ITEM_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_LEVEL_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_NOTE_2]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_ITEM_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_LEVEL_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[ACCESS_NOTE_3]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 09 — Audience-split cards + domain grid + stability/flexibility block
	// ---------------------------------------------------------------------------------
	private static function _tpl_09() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Audience-Specific Application -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[AUDIENCE_SPLIT_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[AUDIENCE_1_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[AUDIENCE_1_APPLICATION]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[AUDIENCE_2_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[AUDIENCE_2_APPLICATION]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid rgba(68,46,102,.35);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[AUDIENCE_3_LABEL]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[AUDIENCE_3_APPLICATION]</p>
    </div>
  </div>
</section>
<!-- Domain Grid -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[DOMAIN_GRID_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Domain / Context</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Application</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[DOMAIN_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DOMAIN_1_APP]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[DOMAIN_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DOMAIN_2_APP]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[DOMAIN_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DOMAIN_3_APP]</td></tr>
    </tbody>
  </table>
</section>
<!-- Stability vs Flexibility -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:var(--lp-secondary-color,#442e66);color:#fff;">
  <h3 style="margin-top:0;color:#fff;">[STABILITY_HEADING]</h3>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:14px;">
    <div>
      <p style="margin:0 0 8px;font-weight:700;color:var(--lp-primary-color,#ffb606);">Fixed / Stable</p>
      <ul style="margin:0;padding-left:18px;color:rgba(255,255,255,.9);">
        <li>[STABLE_1]</li>
        <li>[STABLE_2]</li>
      </ul>
    </div>
    <div>
      <p style="margin:0 0 8px;font-weight:700;color:var(--lp-primary-color,#ffb606);">Flexible / Adaptive</p>
      <ul style="margin:0;padding-left:18px;color:rgba(255,255,255,.9);">
        <li>[FLEXIBLE_1]</li>
        <li>[FLEXIBLE_2]</li>
      </ul>
    </div>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 10 — Three-tier framework cards + implementation cards + goal-matching
	// ---------------------------------------------------------------------------------
	private static function _tpl_10() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Three-Tier Framework -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[TIERS_FRAMEWORK_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--lp-primary-color,#ffb606);display:flex;align-items:center;justify-content:center;font-weight:800;color:#442e66;margin-bottom:10px;">1</div>
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_1_BODY]</p>
    </div>
    <div style="padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--lp-secondary-color,#442e66);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;margin-bottom:10px;">2</div>
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_2_BODY]</p>
    </div>
    <div style="padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="width:36px;height:36px;border-radius:50%;background:rgba(68,46,102,.4);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;margin-bottom:10px;">3</div>
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_3_BODY]</p>
    </div>
  </div>
</section>
<!-- Implementation Cards -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[IMPLEMENTATION_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:3px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[IMPL_1_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[IMPL_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:3px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[IMPL_2_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[IMPL_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border-top:3px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;">[IMPL_3_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[IMPL_3_BODY]</p>
    </div>
  </div>
</section>
<!-- Goal Matching -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[GOAL_MATCH_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Learner Goal</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Matching Tier / Approach</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_1_GOAL]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_1_TIER]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_2_GOAL]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_2_TIER]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_3_GOAL]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[GOAL_MATCH_3_TIER]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 11 — Standards definition block + proper/improper conduct comparison
	// ---------------------------------------------------------------------------------
	private static function _tpl_11() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Standards Definition -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:rgba(68,46,102,.05);border:1px solid rgba(68,46,102,.1);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[STANDARDS_HEADING]</h3>
  <p style="margin:0 0 10px;">[STANDARDS_INTRO]</p>
  <ul style="margin:0;padding-left:20px;">
    <li style="margin-bottom:6px;">[STANDARD_1]</li>
    <li style="margin-bottom:6px;">[STANDARD_2]</li>
    <li style="margin-bottom:6px;">[STANDARD_3]</li>
    <li>[STANDARD_4]</li>
  </ul>
</section>
<!-- Proper vs Improper Conduct -->
<section style="margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="padding:22px;border-radius:14px;background:#f6fdf6;border:1px solid rgba(34,139,34,.2);">
    <h4 style="margin:0 0 12px;color:#1a7a1a;">Proper Conduct</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:8px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [PROPER_1]</li>
      <li style="margin-bottom:8px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [PROPER_2]</li>
      <li><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [PROPER_3]</li>
    </ul>
  </div>
  <div style="padding:22px;border-radius:14px;background:#fdf6f6;border:1px solid rgba(180,0,0,.2);">
    <h4 style="margin:0 0 12px;color:#b40000;">Improper Conduct</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:8px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [IMPROPER_1]</li>
      <li style="margin-bottom:8px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [IMPROPER_2]</li>
      <li><span style="color:#b40000;font-weight:700;">&#10007;</span> [IMPROPER_3]</li>
    </ul>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 12 — Cognitive framing + augments/bypasses contrast + visible-thinking row
	// ---------------------------------------------------------------------------------
	private static function _tpl_12() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Cognitive Framing -->
<section style="margin-bottom:22px;padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);border-radius:12px;background:#fff;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[COGNITIVE_FRAMING_HEADING]</h3>
  <p style="margin:0;">[COGNITIVE_FRAMING_BODY]</p>
</section>
<!-- Augments vs Bypasses -->
<section style="margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="padding:22px;border-radius:14px;background:#f6fdf6;border:1px solid rgba(34,139,34,.2);">
    <h4 style="margin:0 0 12px;color:#1a7a1a;">Augments Thinking</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [AUGMENT_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [AUGMENT_2]</li>
      <li><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [AUGMENT_3]</li>
    </ul>
  </div>
  <div style="padding:22px;border-radius:14px;background:#fdf6f6;border:1px solid rgba(180,0,0,.2);">
    <h4 style="margin:0 0 12px;color:#b40000;">Bypasses Thinking</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [BYPASS_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [BYPASS_2]</li>
      <li><span style="color:#b40000;font-weight:700;">&#10007;</span> [BYPASS_3]</li>
    </ul>
  </div>
</section>
<!-- Making Thinking Visible -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[VISIBLE_THINKING_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[THINKING_STEP_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[THINKING_STEP_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[THINKING_STEP_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[THINKING_STEP_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[THINKING_STEP_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[THINKING_STEP_3_BODY]</p>
    </div>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 13 — Audience-specific message cards + communication channel grid
	// ---------------------------------------------------------------------------------
	private static function _tpl_13() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Core Message -->
<section style="margin-bottom:22px;padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);border-radius:12px;background:#fff;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[CORE_MESSAGE_HEADING]</h3>
  <p style="margin:0;">[CORE_MESSAGE_BODY]</p>
</section>
<!-- Audience-Specific Messages -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[AUDIENCE_MSG_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[MSG_AUDIENCE_1]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[MSG_1_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[MSG_AUDIENCE_2]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[MSG_2_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid rgba(68,46,102,.4);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[MSG_AUDIENCE_3]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[MSG_3_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[MSG_AUDIENCE_4]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[MSG_4_BODY]</p>
    </div>
  </div>
</section>
<!-- Communication Channel Grid -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CHANNEL_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Channel</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Best Used For</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Audience</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_1_USE]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_1_AUDIENCE]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_2_USE]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_2_AUDIENCE]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_3_USE]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[CHANNEL_3_AUDIENCE]</td></tr>
    </tbody>
  </table>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 14 — Bridge-mapping grid + alignment checklist
	// ---------------------------------------------------------------------------------
	private static function _tpl_14() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Existing Framework Overview -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:rgba(68,46,102,.04);border:1px solid rgba(68,46,102,.1);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[EXISTING_FRAMEWORK_HEADING]</h3>
  <p style="margin:0;">[EXISTING_FRAMEWORK_BODY]</p>
</section>
<!-- Bridge Mapping Grid -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[BRIDGE_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Familiar Concept</th>
        <th style="padding:12px 14px;background:rgba(255,182,6,.12);border:1px solid rgba(68,46,102,.12);text-align:left;">New Concept / Guideline</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">Key Difference</th>
      </tr>
    </thead>
    <tbody>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[FAMILIAR_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[NEW_1]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DIFF_1]</td></tr>
      <tr style="background:rgba(68,46,102,.03);"><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[FAMILIAR_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[NEW_2]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DIFF_2]</td></tr>
      <tr><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[FAMILIAR_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[NEW_3]</td><td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[DIFF_3]</td></tr>
    </tbody>
  </table>
</section>
<!-- Alignment Checklist -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[CHECKLIST_HEADING]</h3>
  <ul style="margin:0;padding-left:0;list-style:none;">
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_1]</li>
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_2]</li>
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_3]</li>
    <li style="padding:8px 0;display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_4]</li>
  </ul>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	// ---------------------------------------------------------------------------------
	// Template 15 — Numbered component grid + writing guidelines + living document cycle
	// ---------------------------------------------------------------------------------
	private static function _tpl_15() {
		$hero    = self::hero_html();
		$meta    = self::meta_table_html();
		$capstone = self::capstone_html();
		$quiz    = self::quiz_html();
		return <<<HTML
<div class="lp-ai-lesson-template" style="max-width:980px;margin:0 auto;font-family:inherit;">
{$hero}
{$meta}
<!-- Document Components -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[COMPONENTS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">01</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_1_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_1_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">02</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_2_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_2_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">03</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_3_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_3_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">04</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_4_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_4_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">05</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_5_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_5_DESC]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">06</div>
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_6_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_6_DESC]</p>
    </div>
  </div>
</section>
<!-- Writing Guidelines -->
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:var(--lp-secondary-color,#442e66);color:#fff;">
  <h3 style="margin-top:0;color:#fff;">[GUIDELINES_HEADING]</h3>
  <ul style="margin:0;padding-left:20px;">
    <li style="margin-bottom:8px;color:rgba(255,255,255,.9);">[GUIDELINE_1]</li>
    <li style="margin-bottom:8px;color:rgba(255,255,255,.9);">[GUIDELINE_2]</li>
    <li style="margin-bottom:8px;color:rgba(255,255,255,.9);">[GUIDELINE_3]</li>
    <li style="color:rgba(255,255,255,.9);">[GUIDELINE_4]</li>
  </ul>
</section>
<!-- Living Document Cycle -->
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CYCLE_HEADING]</h3>
  <div style="display:flex;gap:0;align-items:stretch;">
    <div style="flex:1;padding:18px;border-radius:12px 0 0 12px;background:#fff;border:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-primary-color,#ffb606);font-size:1.2rem;margin-bottom:4px;">Draft</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_DRAFT]</p>
    </div>
    <div style="flex:1;padding:18px;background:#fff;border-top:1px solid rgba(68,46,102,.12);border-bottom:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-secondary-color,#442e66);font-size:1.2rem;margin-bottom:4px;">Review</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_REVIEW]</p>
    </div>
    <div style="flex:1;padding:18px;background:#fff;border-top:1px solid rgba(68,46,102,.12);border-bottom:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-secondary-color,#442e66);font-size:1.2rem;margin-bottom:4px;">Publish</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_PUBLISH]</p>
    </div>
    <div style="flex:1;padding:18px;border-radius:0 12px 12px 0;background:#fff;border:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-primary-color,#ffb606);font-size:1.2rem;margin-bottom:4px;">Revise</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_REVISE]</p>
    </div>
  </div>
</section>
{$quiz}
{$capstone}
</div>
HTML;
	}

	/**
	 * System prompt for the template-filling generation pass.
	 *
	 * @param  string $lesson_title
	 * @return string
	 */
	private static function build_template_system_prompt( $lesson_title = '' ) {
		$title_instruction = ! empty( $lesson_title )
			? ' Use "' . $lesson_title . '" as the primary heading in the Hero Header [LESSON_TITLE] token.'
			: '';

		return 'You are an expert Instructional Designer.' . $title_instruction . ' '
			. 'You will receive a lesson content and an HTML template. Transform the lesson content into the template. '
			. 'CRITICAL: Output ONLY the raw HTML code — no markdown, no code fences, no explanation. '
			. 'The HTML must start exactly with <div class="lp-ai-lesson-template"> and end with </div>. '
			. 'Replace every [PLACEHOLDER] token with content tightly relevant to the original lesson topic. '
			. 'Preserve ALL inline styles exactly as written — do not add, remove or alter any style attributes. '
			. 'Keep CSS variable references exactly as written: var(--lp-primary-color, #ffb606) and var(--lp-secondary-color, #442e66). '
			. 'CRITICAL: Preserve the [Capstone Box] sentinel exactly as written inside its <div> — do NOT replace it with any text. '
			. 'Do NOT add Tailwind classes, Font Awesome, or any external CSS/JS references. '
			. 'READING TIME CONSTRAINT: Total reading time MUST NOT exceed 15 minutes. '
			. 'Calibrate depth and number of items per section based on the complexity of the original content. '
			. 'Set [ESTIMATED_TIME] accordingly (e.g. "8 min" — never more than "15 min").';
	}

	/**
	 * Build the user-turn message for the template-filling generation pass.
	 *
	 * @param  string $lesson_content  Sanitized lesson content.
	 * @param  string $lesson_title    The lesson title.
	 * @param  string $template_html   The selected template HTML with [PLACEHOLDER] tokens.
	 * @return string
	 */
	private static function build_template_user_message( $lesson_content, $lesson_title, $template_html ) {
		$title_line = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';
		return "Transform the following lesson content into the provided HTML template. "
			. "Replace every [PLACEHOLDER] token with content derived from the original lesson. "
			. "Do not alter any HTML structure, inline styles, or CSS variable names. "
			. "Preserve the [Capstone Box] sentinel exactly as-is inside its parent <div>.\n\n"
			. $title_line
			. "ORIGINAL LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE TO FILL IN:\n{$template_html}";
	}

	// -------------------------------------------------------------------------
	// Workbook lesson type
	// -------------------------------------------------------------------------

	/**
	 * Detect whether the lesson should use the standard or workbook template.
	 * Detection runs on the original pre-AI content and the lesson title.
	 *
	 * @param  int    $post_id         The lesson post ID.
	 * @param  string $lesson_content  Sanitized pre-AI lesson content.
	 * @return string 'workbook' | 'standard'
	 */
	private static function detect_lesson_type( $post_id, $lesson_content ) {
		if (
			false !== stripos( get_the_title( $post_id ), 'workbook' ) ||
			false !== stripos( $lesson_content, 'Workbook Entry' )
		) {
			return 'workbook';
		}
		return 'standard';
	}

	/**
	 * System-level instruction for workbook-type lessons.
	 *
	 * @param  string $lesson_title  The lesson title (anchors the Hero Header heading).
	 * @return string
	 */
	private static function build_workbook_system_prompt( $lesson_title = '' ) {
		$title_instruction = ! empty( $lesson_title )
			? ' Use "' . $lesson_title . '" as the primary heading for the Hero Header section.'
			: '';

		return 'You are an expert Instructional Designer specialising in reflective workbook activities. '
			. 'Transform the provided lesson content into a high-end HTML workbook activity page.' . $title_instruction . ' '
			. 'CRITICAL: Output ONLY the raw HTML code — no markdown, no code fences, no explanation text. '
			. 'The HTML must start with <div class="lp-ai-lesson-template"> and end with </div>. '
			. 'Use the following nine sections exactly as structured in the template the user provides: '
			. 'Hero Header, Activity Overview, Why This Matters, Reflection Prompt, Workbook Entry, Example, Important Note, Next Step, and Check for Understanding. '
			. 'Preserve all inline styles verbatim. Use #442e66 for heading colours and #ffb606 for accent borders. '
			. 'Keep CSS variable references exactly as written: var(--lp-primary-color, #ffb606) and var(--lp-secondary-color, #442e66). '
			. 'Replace every [PLACEHOLDER] token with content tightly relevant to the original lesson topic. '
			. 'CRITICAL: Every [Text Box] div MUST remain exactly as-is — do NOT replace it with text or any other content. '
			. 'These are interactive form field sentinels that JavaScript converts to input fields at runtime. '
			. 'Derive Workbook Entry field labels from the reflection questions in the original content. '
			. 'Add or remove Workbook Entry field blocks to match the number of fields in the original. '
			. 'READING TIME CONSTRAINT: The total reading and completion time of the generated workbook MUST NOT exceed 15 minutes. '
			. 'Calibrate the depth of each section (reflection bullets, workbook entry fields, example detail, quiz complexity) '
			. 'based on the complexity and scope of the original content so a learner can finish the activity in 15 minutes or less. '
			. 'Set the Estimated Time metadata field accordingly (e.g. "10 min" — never more than "15 min").';
	}

	/**
	 * Build the user-turn message for workbook-type lessons.
	 *
	 * @param  string $lesson_content  Sanitized pre-AI workbook lesson content.
	 * @param  string $lesson_title    The lesson title.
	 * @return string
	 */
	private static function build_workbook_user_message( $lesson_content, $lesson_title = '' ) {
		$title_line = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';

		$template = <<<'HTML'
<div class="lp-ai-lesson-template" style="max-width: 980px; margin: 0 auto;">

<!-- Hero Header -->
<section style="position: relative; margin-bottom: 24px; border-radius: 18px; overflow: hidden; min-height: 320px; background-image: linear-gradient(rgba(32, 22, 55, 0.55), rgba(32, 22, 55, 0.72)), url('https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=1200'); background-size: cover; background-position: center; box-shadow: 0 12px 28px rgba(0,0,0,.10);">
  <div style="padding: 42px 30px; display: flex; align-items: flex-end; min-height: 320px;">
    <div style="max-width: 740px; color: #ffffff;">
      <p style="margin: 0 0 10px; display: inline-block; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 0.82rem; font-weight: bold; letter-spacing: .04em; text-transform: uppercase;">[Short Tagline]</p>
      <h2 style="margin: 0 0 12px; color: #ffffff;">[WORKBOOK_HERO_TITLE]</h2>
      <p style="margin: 0; font-size: 1.05rem; color: rgba(255,255,255,.95);">[WORKBOOK_HERO_SUBTITLE]</p>
    </div>
  </div>
</section>

<!-- Activity Overview -->
<section class="has-very-light-gray-to-cyan-bluish-gray-gradient-background" style="padding: 24px; border-radius: 16px; margin-bottom: 24px;">
  <h3 style="margin-top: 0; color: var(--lp-secondary-color, #442e66);">Activity Overview</h3>
  <p>[ACTIVITY_OVERVIEW_INTRO]</p>
  <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
    <tbody>
      <tr>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Activity Type</strong><br>[ACTIVITY_TYPE]</td>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Estimated Time</strong><br>[ACTIVITY_ESTIMATED_TIME]</td>
      </tr>
      <tr>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Focus</strong><br>[ACTIVITY_FOCUS]</td>
        <td style="padding: 12px; border: 1px solid rgba(68,46,102,.12); vertical-align: top;"><strong>Outcome</strong><br>[ACTIVITY_OUTCOME]</td>
      </tr>
    </tbody>
  </table>
</section>

<!-- Why This Matters -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Why This Matters</h3>
  <div class="has-white-background-color" style="padding: 22px; border-left: 6px solid var(--lp-primary-color, #ffb606); border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,.04);">
    <p style="margin-top: 0;"><strong>[WHY_MATTERS_HEADING]</strong></p>
    <p style="margin-bottom: 0;">[WHY_MATTERS_BODY]</p>
  </div>
</section>

<!-- Reflection Prompt -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Reflection Prompt</h3>
  <div class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 14px;">
    <p style="margin-top: 0;">[REFLECTION_INTRO]</p>
    <ul style="margin-bottom: 0;">
      <li>[REFLECTION_BULLET_1]</li>
      <li>[REFLECTION_BULLET_2]</li>
      <li>[REFLECTION_BULLET_3]</li>
    </ul>
  </div>
</section>

<!-- Workbook Entry -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Workbook Entry</h3>
  <div class="has-white-background-color" style="padding: 24px; border-radius: 14px; border: 1px solid rgba(68,46,102,.12);">
    <div style="margin-bottom: 18px;">
      <p style="margin: 0 0 8px;"><strong>[WORKBOOK_FIELD_1_LABEL]</strong></p>
      <div style="min-height: 54px; border: 1px solid rgba(68,46,102,.18); border-radius: 12px; background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,244,250,.96)); padding: 14px 16px; color: #666666;">[Text Box]</div>
    </div>
    <div>
      <p style="margin: 0 0 8px;"><strong>[WORKBOOK_FIELD_2_LABEL]</strong></p>
      <div style="min-height: 54px; border: 1px solid rgba(68,46,102,.18); border-radius: 12px; background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(247,244,250,.96)); padding: 14px 16px; color: #666666;">[Text Box]</div>
    </div>
  </div>
</section>

<!-- Example -->
<section style="margin-bottom: 24px;">
  <h3 style="color: var(--lp-secondary-color, #442e66);">Example</h3>
  <blockquote style="margin: 0; padding: 20px 22px; background: rgba(255,182,6,.12); border-left: 5px solid var(--lp-primary-color, #ffb606); border-radius: 12px;">
    <p style="margin: 0 0 10px;"><strong>Scenario:</strong> [WORKBOOK_EXAMPLE_SCENARIO]</p>
    <p style="margin: 0;"><strong>[WORKBOOK_EXAMPLE_KEY_LABEL]:</strong> [WORKBOOK_EXAMPLE_KEY_VALUE]</p>
  </blockquote>
</section>

<!-- Important Note -->
<section style="margin-bottom: 24px;">
  <div style="padding: 22px; border-radius: 14px; background: var(--lp-secondary-color, #442e66); color: #ffffff;">
    <h3 style="margin-top: 0; color: #ffffff;">Important Note</h3>
    <p style="margin-bottom: 0;">[IMPORTANT_NOTE_BODY]</p>
  </div>
</section>

<!-- Next Step -->
<section class="has-white-background-color" style="padding: 24px; border: 1px solid rgba(68,46,102,.12); border-radius: 16px; margin-bottom: 28px;">
  <h3 style="margin-top: 0; color: var(--lp-secondary-color, #442e66);">Next Step</h3>
  <p style="margin-bottom: 0;">[NEXT_STEP_BODY]</p>
</section>

<!-- Check for Understanding -->
<section class="has-white-background-color" style="margin-top: 0; padding: 0; border: 1px solid rgba(68,46,102,.12); border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,.05);">
  <div style="padding: 20px 24px; border-bottom: 1px solid rgba(68,46,102,.10); background: linear-gradient(135deg, rgba(68,46,102,.96), rgba(44,30,80,.96)); border-radius: 16px 16px 0 0; color: #ffffff;">
    <p style="display: inline-block; margin: 0 0 10px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 0.82rem; font-weight: bold; letter-spacing: .04em; text-transform: uppercase;">Check for Understanding</p>
    <h3 style="margin: 0 0 8px; color: #ffffff;">Quick Knowledge Check</h3>
    <p style="margin: 0; color: rgba(255,255,255,.92);">Choose the best answer based on this workbook activity.</p>
  </div>
  <div style="padding: 24px; background: linear-gradient(135deg, rgba(244,247,250,1), rgba(236,241,246,1));">
    <p style="margin-top: 0; margin-bottom: 18px; color: #222222; font-size: 1.05rem;"><strong>[WORKBOOK_QUIZ_QUESTION]</strong></p>
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px;">
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(247,244,250,.96));">[WORKBOOK_ANSWER_A]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(248,246,252,.96), rgba(240,236,247,.98));">[WORKBOOK_ANSWER_B]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(247,244,250,.96));">[WORKBOOK_ANSWER_C]</div>
      <div style="padding: 12px 14px; border: 1px solid rgba(68,46,102,.16); border-radius: 14px; background: linear-gradient(180deg, rgba(248,246,252,.96), rgba(240,236,247,.98));">[WORKBOOK_ANSWER_D]</div>
    </div>
    <div style="margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: linear-gradient(135deg, rgba(255,245,203,.95), rgba(255,236,170,.92)); border-left: 5px solid var(--lp-primary-color, #ffb606);">
      <p style="margin: 0;"><strong>Correct answer:</strong> [WORKBOOK_CORRECT_ANSWER_EXPLANATION]</p>
    </div>
  </div>
</section>

</div>
HTML;

		return "Understand the following workbook lesson content and transform it into an interactive workbook activity page suitable for effective reflection and learning.\n\n"
			. "IMPORTANT: The output must strictly follow the structure and styles of the provided template. "
			. "Replace every [PLACEHOLDER] token with content tightly relevant to the original lesson topic. "
			. "CRITICAL: Every [Text Box] div MUST remain exactly as written — do NOT replace it with any text or content. "
			. "Derive Workbook Entry field labels from the reflection questions in the original content. "
			. "Add or remove Workbook Entry field blocks to match the number of fields in the original. "
			. "READING TIME CONSTRAINT: The total reading and completion time MUST NOT exceed 15 minutes. Calibrate content depth and the number of workbook fields based on the complexity of the original — simpler topics should result in fewer fields and shorter explanations.\n\n"
			. $title_line
			. "ORIGINAL LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE TO FILL IN:\n{$template}";
	}
}
