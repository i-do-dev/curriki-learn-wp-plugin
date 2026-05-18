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

    $validation = self::validate_generation_request( $post_id, $lesson_content );
    if ( is_wp_error( $validation ) ) {
      return $validation;
    }

    self::maybe_backup_original_content( $post_id );

		// Fetch the lesson title for richer prompt context.
		$lesson_title = get_the_title( $post_id );

		$sanitized_content = sanitize_textarea_field( wp_unslash( $lesson_content ) );

		// Two-pass: classify template, then generate.
		// Pass 1: Ask Bedrock which of the 15 structural templates best fits this lesson.
		$template_id = self::classify_template( $lesson_title, $sanitized_content );

		// Pass 2: Fill the selected template with lesson-specific content.
		$template_html    = self::get_template( $template_id );
		$component_count  = ( '15' === $template_id ) ? self::detect_component_count( $sanitized_content ) : 0;
		$system_prompt    = self::build_template_system_prompt( $lesson_title );
		$prompt           = self::build_template_user_message( $sanitized_content, $lesson_title, $template_html, $component_count );

		$max_tokens = ( '15' === $template_id ) ? 8192 : 4096;
		$result     = TL_AWS_Bedrock_Client::invoke_bedrock( $prompt, $system_prompt, $max_tokens );

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
			'template_id' => $template_id,
		) );
	}

  /**
   * Generate lesson content using ordered author-defined block markers.
   *
   * @param  WP_REST_Request $request Required params: post_id (int), lesson_content (string).
   * @return WP_REST_Response|WP_Error
   */
  public static function generate_blocks_content( WP_REST_Request $request ) {
    $post_id        = absint( $request->get_param( 'post_id' ) );
    $lesson_content = $request->get_param( 'lesson_content' );

    $validation = self::validate_generation_request( $post_id, $lesson_content, true );
    if ( is_wp_error( $validation ) ) {
      return $validation;
    }

    self::maybe_backup_original_content( $post_id );

    $lesson_title       = get_the_title( $post_id );
    $segments           = self::parse_block_markers( wp_unslash( $lesson_content ) );
    $rendered_segments  = array();
    $errors             = array();
    $rendered_count     = 0;

    foreach ( $segments as $segment ) {
      $rendered = self::render_block( $segment['type'], $segment['content'], $post_id, $lesson_title );
      if ( is_wp_error( $rendered ) ) {
        $errors[] = array(
          'type'    => $segment['type'],
          'code'    => $rendered->get_error_code(),
          'message' => $rendered->get_error_message(),
        );
        continue;
      }

      if ( '' !== trim( $rendered ) ) {
        $rendered_segments[] = $rendered;
        ++$rendered_count;
      }
    }

    if ( empty( $rendered_segments ) ) {
      return new WP_Error( 'blocks_render_failed', 'No lesson blocks could be rendered.', array( 'status' => 502, 'errors' => $errors ) );
    }

    return rest_ensure_response(
      array(
        'content'         => implode( "\n", $rendered_segments ),
        'blocks_rendered' => $rendered_count,
        'errors'          => $errors,
      )
    );
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

  /**
   * Validate a generation request.
   *
   * @param  int         $post_id
   * @param  string      $lesson_content
   * @param  bool        $enforce_capability
   * @return true|WP_Error
   */
  private static function validate_generation_request( $post_id, $lesson_content, $enforce_capability = false ) {
    if ( $post_id <= 0 ) {
      return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
    }

    if ( empty( trim( (string) $lesson_content ) ) ) {
      return new WP_Error( 'missing_content', 'lesson_content cannot be empty.', array( 'status' => 400 ) );
    }

    if ( $enforce_capability && ! current_user_can( 'edit_post', $post_id ) ) {
      return new WP_Error( 'forbidden', 'You do not have permission to edit this lesson.', array( 'status' => 403 ) );
    }

    return true;
  }

  /**
   * Persist the first-seen original lesson content only once.
   *
   * @param int $post_id
   */
  private static function maybe_backup_original_content( $post_id ) {
    $existing_backup = get_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, true );
    if ( ! empty( $existing_backup ) ) {
      return;
    }

    $post = get_post( $post_id );
    if ( $post && ! empty( $post->post_content ) ) {
      update_post_meta( $post_id, self::ORIGINAL_CONTENT_META_KEY, $post->post_content );
    }
  }

  /**
   * Parse author content into ordered block segments.
   *
   * Supports plain text fences and TinyMCE paragraph-wrapped fence lines.
   * Unmarked content becomes a prose block.
   *
   * @param  string $content
   * @return array<int,array<string,string>>
   */
  public static function parse_block_markers( $content ) {
    $segments         = array();
    $current_type     = null;
    $current_lines    = array();
    $prose_lines      = array();
    $content          = preg_replace( '#</p>\s*<p>#i', "</p>\n<p>", (string) $content );
    $content          = preg_replace( '#<br\s*/?>#i', "<br />\n", $content );
    $lines            = preg_split( '/\r\n|\r|\n/', $content );

    foreach ( $lines as $line ) {
      $fence = self::normalize_marker_line( $line );

      if ( preg_match( '/^:::\s*([a-z0-9-]+)\s*$/i', $fence, $matches ) ) {
        if ( null !== $current_type ) {
          $current_lines[] = $line;
          continue;
        }

        self::push_block_segment( $segments, 'prose', $prose_lines );
        $current_type  = strtolower( $matches[1] );
        $current_lines = array();
        continue;
      }

      if ( ':::' === trim( $fence ) && null !== $current_type ) {
        self::push_block_segment( $segments, $current_type, $current_lines );
        $current_type  = null;
        $current_lines = array();
        continue;
      }

      if ( null !== $current_type ) {
        $current_lines[] = $line;
      } else {
        $prose_lines[] = $line;
      }
    }

    if ( null !== $current_type ) {
      array_unshift( $current_lines, ':::' . $current_type );
      self::push_block_segment( $segments, 'prose', $current_lines );
    }

    self::push_block_segment( $segments, 'prose', $prose_lines );

    if ( empty( $segments ) ) {
      $segments[] = array(
        'type'    => 'prose',
        'content' => trim( (string) $content ),
      );
    }

    return $segments;
  }

  /**
   * Provide a catalog of supported blocks for admin reference UI.
   *
   * @return array<int,array<string,string>>
   */
  public static function get_block_catalog() {
    $catalog = array(
      array(
        'type'        => 'hero',
        'label'       => 'Hero',
        'description' => 'Large lesson header with tag, title, and subtitle.',
        'marker'      => ":::hero\nTag: Policy Essentials\nSubtitle: Build a shared understanding before the formal guidance begins.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'hero' ), array(
          '[LESSON_TAG]'      => 'Policy Essentials',
          '[LESSON_TITLE]'    => 'Writing Better Policy Documents',
          '[LESSON_SUBTITLE]' => 'Build a shared understanding before the formal guidance begins.',
        ) ),
      ),
      array(
        'type'        => 'learning-outcomes',
        'label'       => 'Learning Outcomes',
        'description' => 'Four actionable lesson outcomes.',
        'marker'      => ":::learning-outcomes\n- Identify the core sections of the document\n- Explain why the structure matters\n- Review the audience fit of each section\n- Revise the outline for clarity\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'learning-outcomes' ), array(
          '[OUTCOME_1]' => 'Identify the core sections of the document.',
          '[OUTCOME_2]' => 'Explain why the structure matters.',
          '[OUTCOME_3]' => 'Review the audience fit of each section.',
          '[OUTCOME_4]' => 'Revise the outline for clarity.',
        ) ),
      ),
      array(
        'type'        => 'opening-hook',
        'label'       => 'Opening Hook',
        'description' => 'Context-setting quote or framing statement.',
        'marker'      => ":::opening-hook\nA policy document only works when readers can immediately see how its rules connect to daily decisions.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'opening-hook' ), array(
          '[OPENING_HOOK_STATEMENT]' => 'A policy document only works when readers can immediately see how its rules connect to daily decisions.',
        ) ),
      ),
      array(
        'type'        => 'capstone',
        'label'       => 'Capstone',
        'description' => 'Final applied-response activity with the preserved sentinel box.',
        'marker'      => ":::capstone\nDraft a revised policy section that clarifies who the audience is, what action is required, and how revision should happen over time.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'capstone' ), array(
          '[CAPSTONE_PROMPT]' => 'Draft a revised policy section that clarifies who the audience is, what action is required, and how revision should happen over time.',
        ) ),
      ),
      array(
        'type'        => 'stats-grid',
        'label'       => 'Stats Grid',
        'description' => 'Three metric cards for evidence or quick context.',
        'marker'      => ":::stats-grid\nHeading: Why this matters\n72% | Teams that used a shared template\n3x | Faster review cycles\n41% | Fewer revision loops\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'stats-grid' ), array(
          '[STATS_HEADING]' => 'Why this matters',
          '[STAT_1_VALUE]' => '72%',
          '[STAT_1_LABEL]' => 'Teams that used a shared template',
          '[STAT_2_VALUE]' => '3x',
          '[STAT_2_LABEL]' => 'Faster review cycles',
          '[STAT_3_VALUE]' => '41%',
          '[STAT_3_LABEL]' => 'Fewer revision loops',
        ) ),
      ),
      array(
        'type'        => 'cards-grid',
        'label'       => 'Cards Grid',
        'description' => 'Three summary cards with titles and short bodies.',
        'marker'      => ":::cards-grid\nClarity: Name the audience early.\nSequence: Put decisions in the order readers will use them.\nRevision: Say who updates the document and when.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'cards-grid' ), array(
          '[CARDS_HEADING]'  => 'Core writing moves',
          '[CARD_1_TITLE]'   => 'Clarity',
          '[CARD_1_BODY]'    => 'Name the audience early.',
          '[CARD_2_TITLE]'   => 'Sequence',
          '[CARD_2_BODY]'    => 'Put decisions in the order readers will use them.',
          '[CARD_3_TITLE]'   => 'Revision',
          '[CARD_3_BODY]'    => 'Say who updates the document and when.',
        ) ),
      ),
      array(
        'type'        => 'tier-cards',
        'label'       => 'Tier Cards',
        'description' => 'Three top-accent cards for levels, options, or approaches.',
        'marker'      => ":::tier-cards\nStarter: Single owner, one audience, one action.\nIntermediate: Cross-team review and approval.\nAdvanced: Full lifecycle governance and revision schedule.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'tier-cards' ), array(
          '[TIER_CARDS_HEADING]' => 'Implementation levels',
          '[TIER_CARD_1_TITLE]' => 'Starter',
          '[TIER_CARD_1_BODY]'  => 'Single owner, one audience, one action.',
          '[TIER_CARD_2_TITLE]' => 'Intermediate',
          '[TIER_CARD_2_BODY]'  => 'Cross-team review and approval.',
          '[TIER_CARD_3_TITLE]' => 'Advanced',
          '[TIER_CARD_3_BODY]'  => 'Full lifecycle governance and revision schedule.',
        ) ),
      ),
      array(
        'type'        => 'numbered-grid',
        'label'       => 'Numbered Grid',
        'description' => 'Dynamic numbered card grid for named components or steps.',
        'marker'      => ":::numbered-grid\nThere are 4 components in this policy template: Purpose, Audience, Action, and Revision.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'numbered-grid', 4 ), array(
          '[COMPONENTS_N_HEADING]' => '4 policy components',
          '[COMPONENT_1_TITLE]' => 'Purpose',
          '[COMPONENT_1_DESC]'  => 'States why the document exists.',
          '[COMPONENT_2_TITLE]' => 'Audience',
          '[COMPONENT_2_DESC]'  => 'Names who should use it.',
          '[COMPONENT_3_TITLE]' => 'Action',
          '[COMPONENT_3_DESC]'  => 'Explains what readers must do.',
          '[COMPONENT_4_TITLE]' => 'Revision',
          '[COMPONENT_4_DESC]'  => 'Shows how updates are made over time.',
        ) ),
      ),
      array(
        'type'        => 'two-col-table',
        'label'       => 'Two-Column Table',
        'description' => 'Comparison or mapping table with three rows.',
        'marker'      => ":::two-col-table\nDraft outline | Final audience-ready language\nInternal notes | Public-facing explanation\nOne-time memo | Living policy document\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'two-col-table' ), array(
          '[TABLE_2_HEADING]' => 'Draft to publish mapping',
          '[TABLE_2_COL_1]'   => 'Draft concept',
          '[TABLE_2_COL_2]'   => 'Published version',
          '[TABLE_2_ROW_1_LEFT]'  => 'Draft outline',
          '[TABLE_2_ROW_1_RIGHT]' => 'Final audience-ready language',
          '[TABLE_2_ROW_2_LEFT]'  => 'Internal notes',
          '[TABLE_2_ROW_2_RIGHT]' => 'Public-facing explanation',
          '[TABLE_2_ROW_3_LEFT]'  => 'One-time memo',
          '[TABLE_2_ROW_3_RIGHT]' => 'Living policy document',
        ) ),
      ),
      array(
        'type'        => 'three-col-table',
        'label'       => 'Three-Column Table',
        'description' => 'Evaluation or comparison grid with three columns.',
        'marker'      => ":::three-col-table\nAudience | What they need | Why it matters\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'three-col-table' ), array(
          '[TABLE_3_HEADING]' => 'Audience fit check',
          '[TABLE_3_COL_1]'   => 'Audience',
          '[TABLE_3_COL_2]'   => 'What they need',
          '[TABLE_3_COL_3]'   => 'Why it matters',
          '[TABLE_3_ROW_1_A]' => 'Teachers',
          '[TABLE_3_ROW_1_B]' => 'Clear classroom actions',
          '[TABLE_3_ROW_1_C]' => 'They apply the policy daily',
          '[TABLE_3_ROW_2_A]' => 'Leaders',
          '[TABLE_3_ROW_2_B]' => 'Decision checkpoints',
          '[TABLE_3_ROW_2_C]' => 'They approve and revise it',
          '[TABLE_3_ROW_3_A]' => 'Families',
          '[TABLE_3_ROW_3_B]' => 'Plain-language summary',
          '[TABLE_3_ROW_3_C]' => 'They need the purpose in accessible language',
        ) ),
      ),
      array(
        'type'        => 'contrast-panel',
        'label'       => 'Contrast Panel',
        'description' => 'Green vs red do-or-don’t guidance pair.',
        'marker'      => ":::contrast-panel\nDO: Name the audience.\nDO: Define the action.\nDO: Explain the revision owner.\nNEVER: Hide the decision path.\nNEVER: Mix multiple audiences in one instruction.\nNEVER: Publish without a review date.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'contrast-panel' ), array(
          '[CONTRAST_LEFT_HEADING]' => 'Always do',
          '[CONTRAST_RIGHT_HEADING]' => 'Never do',
          '[LEFT_1]' => 'Name the audience.',
          '[LEFT_2]' => 'Define the action.',
          '[LEFT_3]' => 'Explain the revision owner.',
          '[RIGHT_1]' => 'Hide the decision path.',
          '[RIGHT_2]' => 'Mix multiple audiences in one instruction.',
          '[RIGHT_3]' => 'Publish without a review date.',
        ) ),
      ),
      array(
        'type'        => 'callout',
        'label'       => 'Callout',
        'description' => 'Single highlighted text block with a yellow left border.',
        'marker'      => ":::callout\nA policy document becomes easier to use when readers can see who it is for before they reach the first requirement.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'callout' ), array(
          '[CALLOUT_HEADING]' => 'Why this matters',
          '[CALLOUT_BODY]'    => 'A policy document becomes easier to use when readers can see who it is for before they reach the first requirement.',
        ) ),
      ),
      array(
        'type'        => 'dark-block',
        'label'       => 'Dark Block',
        'description' => 'High-emphasis dark callout or guideline section.',
        'marker'      => ":::dark-block\nHeading: Revision guardrail\nEvery published policy should name the owner, the trigger for review, and the date the next review must happen.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'dark-block' ), array(
          '[DARK_BLOCK_HEADING]' => 'Revision guardrail',
          '[DARK_BLOCK_BODY]'    => 'Every published policy should name the owner, the trigger for review, and the date the next review must happen.',
        ) ),
      ),
      array(
        'type'        => 'definition-block',
        'label'       => 'Definition Block',
        'description' => 'Soft-tinted definition or overview section with bullets.',
        'marker'      => ":::definition-block\nPolicy writing standards include clarity, sequence, accountability, and revision.\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'definition-block' ), array(
          '[DEFINITION_HEADING]' => 'Policy writing standards',
          '[DEFINITION_INTRO]'   => 'Strong policy writing usually includes these core qualities:',
          '[DEFINITION_ITEM_1]'  => 'Clarity of audience',
          '[DEFINITION_ITEM_2]'  => 'Clear action language',
          '[DEFINITION_ITEM_3]'  => 'Visible accountability',
          '[DEFINITION_ITEM_4]'  => 'Revision over time',
        ) ),
      ),
      array(
        'type'        => 'role-split',
        'label'       => 'Role Split',
        'description' => 'Two role-perspective panels with bullets.',
        'marker'      => ":::role-split\nAuthors: define scope, sequence, and ownership\nReviewers: test readability, alignment, and revision readiness\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'role-split' ), array(
          '[ROLE_SPLIT_HEADING]' => 'Role perspectives',
          '[ROLE_A_TITLE]'       => 'Authors',
          '[ROLE_A_POINT_1]'     => 'Define scope.',
          '[ROLE_A_POINT_2]'     => 'Sequence the decisions.',
          '[ROLE_A_POINT_3]'     => 'Assign ownership.',
          '[ROLE_B_TITLE]'       => 'Reviewers',
          '[ROLE_B_POINT_1]'     => 'Test readability.',
          '[ROLE_B_POINT_2]'     => 'Check alignment.',
          '[ROLE_B_POINT_3]'     => 'Confirm revision readiness.',
        ) ),
      ),
      array(
        'type'        => 'option-cards',
        'label'       => 'Option Cards',
        'description' => 'Four 2x2 cards for options, audiences, or messages.',
        'marker'      => ":::option-cards\nTeacher-facing summary\nLeader-facing review guide\nFamily-facing explainer\nRevision checklist\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'option-cards' ), array(
          '[OPTIONS_HEADING]' => 'Useful companion blocks',
          '[OPTION_1_NAME]'   => 'Teacher-facing summary',
          '[OPTION_1_DESC]'   => 'Shows day-to-day actions.',
          '[OPTION_2_NAME]'   => 'Leader-facing review guide',
          '[OPTION_2_DESC]'   => 'Supports approval and oversight.',
          '[OPTION_3_NAME]'   => 'Family-facing explainer',
          '[OPTION_3_DESC]'   => 'Translates the policy into plain language.',
          '[OPTION_4_NAME]'   => 'Revision checklist',
          '[OPTION_4_DESC]'   => 'Keeps the policy current after publication.',
        ) ),
      ),
      array(
        'type'        => 'checklist',
        'label'       => 'Checklist',
        'description' => 'White card with four checkmarked alignment items.',
        'marker'      => ":::checklist\nName the audience\nDefine the action\nAssign ownership\nSet the review cycle\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'checklist' ), array(
          '[CHECKLIST_HEADING]' => 'Alignment checklist',
          '[CHECK_1]' => 'Name the audience.',
          '[CHECK_2]' => 'Define the action.',
          '[CHECK_3]' => 'Assign ownership.',
          '[CHECK_4]' => 'Set the review cycle.',
        ) ),
      ),
      array(
        'type'        => 'cycle',
        'label'       => 'Cycle',
        'description' => 'Four-stage lifecycle bar.',
        'marker'      => ":::cycle\nDraft\nReview\nPublish\nRevise\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'cycle' ), array(
          '[CYCLE_HEADING]' => 'Living document cycle',
          '[CYCLE_DRAFT_LABEL]' => 'Draft',
          '[CYCLE_DRAFT]' => 'Build the first version.',
          '[CYCLE_REVIEW_LABEL]' => 'Review',
          '[CYCLE_REVIEW]' => 'Check audience fit and risk.',
          '[CYCLE_PUBLISH_LABEL]' => 'Publish',
          '[CYCLE_PUBLISH]' => 'Release the approved version.',
          '[CYCLE_REVISE_LABEL]' => 'Revise',
          '[CYCLE_REVISE]' => 'Update after feedback or policy shifts.',
        ) ),
      ),
      array(
        'type'        => 'myth-reality',
        'label'       => 'Myth vs Reality',
        'description' => 'Three-row two-column comparison table.',
        'marker'      => ":::myth-reality\nPolicy writing is just formatting | The structure shapes how people interpret the rule\nOne approval is enough | Good policy documents build in revision\nPlain language means less detail | Plain language makes the action easier to follow\n:::",
        'preview_html' => self::render_preview_template( self::get_block_template( 'myth-reality' ), array(
          '[MYTH_REALITY_HEADING]' => 'Myth vs Reality',
          '[MYTH_1]' => 'Policy writing is just formatting.',
          '[REALITY_1]' => 'The structure shapes how people interpret the rule.',
          '[MYTH_2]' => 'One approval is enough.',
          '[REALITY_2]' => 'Good policy documents build in revision.',
          '[MYTH_3]' => 'Plain language means less detail.',
          '[REALITY_3]' => 'Plain language makes the action easier to follow.',
        ) ),
      ),
    );

    return $catalog;
  }

  /**
   * Render a single block segment.
   *
   * @param  string $type
   * @param  string $content
   * @param  int    $post_id
   * @param  string $lesson_title
   * @return string|WP_Error
   */
  private static function render_block( $type, $content, $post_id, $lesson_title = '' ) {
    $type           = strtolower( trim( (string) $type ) );
    $raw_content    = trim( (string) $content );
    $template_title = $lesson_title;

    if ( 'prose' === $type ) {
      return self::render_prose_block( $raw_content );
    }

    if ( 'hero' === $type ) {
      $template_title = get_the_title( $post_id );
    }

    $template_html = self::get_block_template(
      $type,
      'numbered-grid' === $type ? self::detect_component_count( $raw_content ) : 0
    );

    if ( empty( $template_html ) ) {
      return new WP_Error( 'unknown_block_type', sprintf( 'Unsupported block type "%s".', $type ) );
    }

    $system_prompt = self::build_block_system_prompt( $type, $template_title );
    $user_prompt   = self::build_block_user_message( $type, $raw_content, $template_html, $template_title );
    $result        = TL_AWS_Bedrock_Client::invoke_bedrock( $user_prompt, $system_prompt, 2048 );

    if ( is_wp_error( $result ) ) {
      return $result;
    }

    return $result;
  }

  /**
   * Normalize a single line so TinyMCE-wrapped fences can be detected.
   *
   * @param  string $line
   * @return string
   */
  private static function normalize_marker_line( $line ) {
    $normalized = html_entity_decode( trim( wp_strip_all_tags( (string) $line ) ), ENT_QUOTES, 'UTF-8' );
    return preg_replace( '/\x{00a0}/u', ' ', $normalized );
  }

  /**
   * Push a parsed segment into the list if non-empty.
   *
   * @param array  $segments
   * @param string $type
   * @param array  $lines
   */
  private static function push_block_segment( &$segments, $type, &$lines ) {
    $content = trim( implode( "\n", $lines ) );
    $lines   = array();

    if ( '' === $content ) {
      return;
    }

    $segments[] = array(
      'type'    => $type,
      'content' => $content,
    );
  }

  /**
   * Resolve a standalone block HTML template.
   *
   * @param  string $type
   * @param  int    $component_count
   * @return string
   */
  private static function get_block_template( $type, $component_count = 0 ) {
    switch ( $type ) {
      case 'hero':
        return self::_block_hero();
      case 'learning-outcomes':
        return self::_block_learning_outcomes();
      case 'opening-hook':
        return self::_block_opening_hook();
      case 'capstone':
        return self::_block_capstone();
      case 'stats-grid':
        return self::_block_stats_grid();
      case 'cards-grid':
        return self::_block_cards_grid();
      case 'tier-cards':
        return self::_block_tier_cards();
      case 'numbered-grid':
        return self::_block_numbered_grid( $component_count );
      case 'two-col-table':
        return self::_block_two_col_table();
      case 'three-col-table':
        return self::_block_three_col_table();
      case 'contrast-panel':
        return self::_block_contrast_panel();
      case 'callout':
        return self::_block_callout();
      case 'dark-block':
        return self::_block_dark_block();
      case 'definition-block':
        return self::_block_definition_block();
      case 'role-split':
        return self::_block_role_split();
      case 'option-cards':
        return self::_block_option_cards();
      case 'checklist':
        return self::_block_checklist();
      case 'cycle':
        return self::_block_cycle();
      case 'myth-reality':
        return self::_block_myth_reality();
      default:
        return '';
    }
  }

  /**
   * Render a static preview by replacing placeholders with sample content.
   *
   * @param  string $template
   * @param  array  $replacements
   * @return string
   */
  private static function render_preview_template( $template, $replacements ) {
    return strtr( $template, $replacements );
  }

  /**
   * Prompt for a single block rendering call.
   *
   * @param  string $block_type
   * @param  string $lesson_title
   * @return string
   */
  private static function build_block_system_prompt( $block_type, $lesson_title = '' ) {
    $title_instruction = '';
    if ( 'hero' === $block_type && ! empty( $lesson_title ) ) {
      $title_instruction = ' Use "' . $lesson_title . '" exactly for the [LESSON_TITLE] placeholder.';
    }

    return 'You are an expert instructional designer formatting one lesson section at a time.'
      . $title_instruction . ' '
      . 'Output ONLY the raw HTML for the provided section — no markdown, no explanations, no surrounding wrapper. '
      . 'Replace every [PLACEHOLDER] token with content derived from the provided source text. '
      . 'Preserve every inline style exactly as written. '
      . 'Do not add scripts, classes, external assets, or extra sections. '
      . 'If the section contains [Capstone Box], preserve that sentinel exactly as written. '
      . 'Keep the result concise and aligned to a total page reading time under 15 minutes.';
  }

  /**
   * Build the user prompt for a single block rendering call.
   *
   * @param  string $block_type
   * @param  string $block_content
   * @param  string $template_html
   * @param  string $lesson_title
   * @return string
   */
  private static function build_block_user_message( $block_type, $block_content, $template_html, $lesson_title = '' ) {
    $title_line = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';

    return "Render the following {$block_type} block into the provided HTML section template. "
      . "Use the source content to fill the placeholders and keep the structure unchanged.\n\n"
      . $title_line
      . "SOURCE BLOCK CONTENT:\n{$block_content}\n\n"
      . "SECTION TEMPLATE:\n{$template_html}";
  }

  /**
   * Render plain unblocked prose without an AI call.
   *
   * @param  string $content
   * @return string
   */
  private static function render_prose_block( $content ) {
    if ( '' === trim( $content ) ) {
      return '';
    }

    return '<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.08);box-shadow:0 10px 30px -10px rgba(68,46,102,.08);">'
      . wp_kses_post( wpautop( $content ) )
      . '</section>';
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

  public static function _block_hero() {
    return self::hero_html();
  }

	/**
	 * Learning Outcomes + Opening Hook sections.
	 * Inserted immediately after the hero header in every standard template.
	 * Replaces the old "Lesson Overview" metadata table.
	 */
	private static function meta_table_html() {
		return <<<'HTML'
<!-- Learning Outcomes -->
<section style="margin-bottom:22px;padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);background:rgba(255,182,6,.06);border-radius:0 14px 14px 0;">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);text-transform:uppercase;letter-spacing:.04em;font-size:.9rem;">Learning Outcomes</h3>
  <p style="margin-top:0;margin-bottom:12px;">By the end of this lesson, you will be able to:</p>
  <ul style="margin:0;padding-left:20px;">
    <li style="margin-bottom:8px;">[OUTCOME_1]</li>
    <li style="margin-bottom:8px;">[OUTCOME_2]</li>
    <li style="margin-bottom:8px;">[OUTCOME_3]</li>
    <li style="margin-bottom:0;">[OUTCOME_4]</li>
  </ul>
</section>
<!-- Opening Hook -->
<section style="margin-bottom:22px;">
  <blockquote style="margin:0;padding:22px 26px;background:rgba(68,46,102,.04);border-left:5px solid var(--lp-primary-color,#ffb606);border-radius:0 14px 14px 0;">
    <p style="margin:0;font-size:1.08rem;line-height:1.7;color:#333;">[OPENING_HOOK_STATEMENT]</p>
  </blockquote>
</section>
HTML;
	}

	public static function _block_learning_outcomes() {
		return <<<'HTML'
<!-- Learning Outcomes -->
<section style="margin-bottom:22px;padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);background:rgba(255,182,6,.06);border-radius:0 14px 14px 0;">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);text-transform:uppercase;letter-spacing:.04em;font-size:.9rem;">Learning Outcomes</h3>
  <p style="margin-top:0;margin-bottom:12px;">By the end of this lesson, you will be able to:</p>
  <ul style="margin:0;padding-left:20px;">
    <li style="margin-bottom:8px;">[OUTCOME_1]</li>
    <li style="margin-bottom:8px;">[OUTCOME_2]</li>
    <li style="margin-bottom:8px;">[OUTCOME_3]</li>
    <li style="margin-bottom:0;">[OUTCOME_4]</li>
  </ul>
</section>
HTML;
	}

	public static function _block_opening_hook() {
		return <<<'HTML'
<!-- Opening Hook -->
<section style="margin-bottom:22px;">
  <blockquote style="margin:0;padding:22px 26px;background:rgba(68,46,102,.04);border-left:5px solid var(--lp-primary-color,#ffb606);border-radius:0 14px 14px 0;">
    <p style="margin:0;font-size:1.08rem;line-height:1.7;color:#333;">[OPENING_HOOK_STATEMENT]</p>
  </blockquote>
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
    <div class="lxp-capstone-box" style="min-height:80px;border:1px solid rgba(68,46,102,.2);border-radius:12px;background:#fff;padding:14px 16px;color:#999;">[Capstone Box]</div>
  </div>
</section>
HTML;
	}

	public static function _block_capstone() {
		return self::capstone_html();
	}

	public static function _block_stats_grid() {
		return <<<'HTML'
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
HTML;
	}

	public static function _block_cards_grid() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CARDS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[CARD_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[CARD_1_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[CARD_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[CARD_2_BODY]</p>
    </div>
    <div style="padding:18px;border-radius:12px;background:#fff;border:1px solid rgba(68,46,102,.1);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[CARD_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[CARD_3_BODY]</p>
    </div>
  </div>
</section>
HTML;
	}

	public static function _block_tier_cards() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[TIER_CARDS_HEADING]</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-primary-color,#ffb606);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_CARD_1_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_CARD_1_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid var(--lp-secondary-color,#442e66);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_CARD_2_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_CARD_2_BODY]</p>
    </div>
    <div style="padding:20px;border-radius:14px;background:#fff;border-top:4px solid rgba(68,46,102,.35);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
      <p style="margin:0 0 6px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[TIER_CARD_3_TITLE]</p>
      <p style="margin:0;font-size:0.88rem;color:#555;">[TIER_CARD_3_BODY]</p>
    </div>
  </div>
</section>
HTML;
	}

	public static function _block_numbered_grid( $component_count = 0 ) {
		$count = ( $component_count > 0 ) ? $component_count : 6;
		$cards = array();

		for ( $index = 1; $index <= $count; $index++ ) {
			$number = sprintf( '%02d', $index );
			$cards[] = '<div style="padding:20px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">'
				. '<div style="font-size:1.5rem;font-weight:800;color:var(--lp-primary-color,#ffb606);margin-bottom:6px;">' . $number . '</div>'
				. '<p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_' . $index . '_TITLE]</p>'
				. '<p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_' . $index . '_DESC]</p>'
				. '</div>';
		}

		return '<section style="margin-bottom:22px;">'
			. '<h3 style="color:var(--lp-secondary-color,#442e66);">[COMPONENTS_N_HEADING]</h3>'
			. '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">'
			. implode( '', $cards )
			. '</div>'
			. '</section>';
	}

	public static function _block_two_col_table() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[TABLE_2_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">[TABLE_2_COL_1]</th>
        <th style="padding:12px 14px;background:rgba(255,182,6,.12);border:1px solid rgba(68,46,102,.12);text-align:left;">[TABLE_2_COL_2]</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_1_LEFT]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_1_RIGHT]</td>
      </tr>
      <tr style="background:rgba(68,46,102,.03);">
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_2_LEFT]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_2_RIGHT]</td>
      </tr>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_3_LEFT]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_2_ROW_3_RIGHT]</td>
      </tr>
    </tbody>
  </table>
</section>
HTML;
	}

	public static function _block_three_col_table() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[TABLE_3_HEADING]</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">[TABLE_3_COL_1]</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">[TABLE_3_COL_2]</th>
        <th style="padding:12px 14px;background:rgba(68,46,102,.08);border:1px solid rgba(68,46,102,.12);text-align:left;">[TABLE_3_COL_3]</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[TABLE_3_ROW_1_A]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_1_B]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_1_C]</td>
      </tr>
      <tr style="background:rgba(68,46,102,.03);">
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[TABLE_3_ROW_2_A]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_2_B]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_2_C]</td>
      </tr>
      <tr>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);font-weight:600;">[TABLE_3_ROW_3_A]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_3_B]</td>
        <td style="padding:12px 14px;border:1px solid rgba(68,46,102,.12);">[TABLE_3_ROW_3_C]</td>
      </tr>
    </tbody>
  </table>
</section>
HTML;
	}

	public static function _block_contrast_panel() {
		return <<<'HTML'
<section style="margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
  <div style="padding:22px;border-radius:14px;background:#f6fdf6;border:1px solid rgba(34,139,34,.2);">
    <h4 style="margin:0 0 12px;color:#1a7a1a;">[CONTRAST_LEFT_HEADING]</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [LEFT_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [LEFT_2]</li>
      <li><span style="color:#1a7a1a;font-weight:700;">&#10003;</span> [LEFT_3]</li>
    </ul>
  </div>
  <div style="padding:22px;border-radius:14px;background:#fdf6f6;border:1px solid rgba(180,0,0,.2);">
    <h4 style="margin:0 0 12px;color:#b40000;">[CONTRAST_RIGHT_HEADING]</h4>
    <ul style="margin:0;padding-left:18px;">
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [RIGHT_1]</li>
      <li style="margin-bottom:6px;"><span style="color:#b40000;font-weight:700;">&#10007;</span> [RIGHT_2]</li>
      <li><span style="color:#b40000;font-weight:700;">&#10007;</span> [RIGHT_3]</li>
    </ul>
  </div>
</section>
HTML;
	}

	public static function _block_callout() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CALLOUT_HEADING]</h3>
  <div style="padding:22px;border-left:6px solid var(--lp-primary-color,#ffb606);border-radius:12px;background:#fff;box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
    <p style="margin:0;">[CALLOUT_BODY]</p>
  </div>
</section>
HTML;
	}

	public static function _block_dark_block() {
		return <<<'HTML'
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:var(--lp-secondary-color,#442e66);color:#fff;">
  <h3 style="margin-top:0;color:#fff;">[DARK_BLOCK_HEADING]</h3>
  <p style="margin:0;">[DARK_BLOCK_BODY]</p>
</section>
HTML;
	}

	public static function _block_definition_block() {
		return <<<'HTML'
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:rgba(68,46,102,.05);border:1px solid rgba(68,46,102,.1);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[DEFINITION_HEADING]</h3>
  <p style="margin:0 0 10px;">[DEFINITION_INTRO]</p>
  <ul style="margin:0;padding-left:20px;">
    <li style="margin-bottom:6px;">[DEFINITION_ITEM_1]</li>
    <li style="margin-bottom:6px;">[DEFINITION_ITEM_2]</li>
    <li style="margin-bottom:6px;">[DEFINITION_ITEM_3]</li>
    <li>[DEFINITION_ITEM_4]</li>
  </ul>
</section>
HTML;
	}

	public static function _block_role_split() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[ROLE_SPLIT_HEADING]</h3>
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
HTML;
	}

	public static function _block_option_cards() {
		return <<<'HTML'
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
HTML;
	}

	public static function _block_checklist() {
		return <<<'HTML'
<section style="margin-bottom:22px;padding:22px;border-radius:14px;background:#fff;border:1px solid rgba(68,46,102,.12);box-shadow:0 10px 30px -10px rgba(68,46,102,.15);">
  <h3 style="margin-top:0;color:var(--lp-secondary-color,#442e66);">[CHECKLIST_HEADING]</h3>
  <ul style="margin:0;padding-left:0;list-style:none;">
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_1]</li>
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_2]</li>
    <li style="padding:8px 0;border-bottom:1px solid rgba(68,46,102,.07);display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_3]</li>
    <li style="padding:8px 0;display:flex;align-items:flex-start;gap:10px;"><span style="color:var(--lp-secondary-color,#442e66);font-weight:700;margin-top:2px;">&#10003;</span> [CHECK_4]</li>
  </ul>
</section>
HTML;
	}

	public static function _block_cycle() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[CYCLE_HEADING]</h3>
  <div style="display:flex;gap:0;align-items:stretch;">
    <div style="flex:1;padding:18px;border-radius:12px 0 0 12px;background:#fff;border:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-primary-color,#ffb606);font-size:1.2rem;margin-bottom:4px;">[CYCLE_DRAFT_LABEL]</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_DRAFT]</p>
    </div>
    <div style="flex:1;padding:18px;background:#fff;border-top:1px solid rgba(68,46,102,.12);border-bottom:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-secondary-color,#442e66);font-size:1.2rem;margin-bottom:4px;">[CYCLE_REVIEW_LABEL]</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_REVIEW]</p>
    </div>
    <div style="flex:1;padding:18px;background:#fff;border-top:1px solid rgba(68,46,102,.12);border-bottom:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-secondary-color,#442e66);font-size:1.2rem;margin-bottom:4px;">[CYCLE_PUBLISH_LABEL]</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_PUBLISH]</p>
    </div>
    <div style="flex:1;padding:18px;border-radius:0 12px 12px 0;background:#fff;border:1px solid rgba(68,46,102,.12);text-align:center;">
      <div style="font-weight:800;color:var(--lp-primary-color,#ffb606);font-size:1.2rem;margin-bottom:4px;">[CYCLE_REVISE_LABEL]</div>
      <p style="margin:0;font-size:0.83rem;color:#666;">[CYCLE_REVISE]</p>
    </div>
  </div>
</section>
HTML;
	}

	public static function _block_myth_reality() {
		return <<<'HTML'
<section style="margin-bottom:22px;">
  <h3 style="color:var(--lp-secondary-color,#442e66);">[MYTH_REALITY_HEADING]</h3>
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
HTML;
	}

	/**
	 * Quiz / "Check for Understanding" section — retired.
	 * Standard templates no longer include a quiz; the ai-lessons source pattern
	 * ends with the Capstone Activity only.
	 *
	 * @return string  Always empty.
	 */
	private static function quiz_html() {
		return '';
	}

	/**
	 * Detect the intended number of component cards for Template 15 from the lesson content.
	 *
	 * Scans the original lesson content for a count-noun phrase (e.g. "15 components",
	 * "7 key principles") so incidental numbers (years, dates, etc.) are ignored and
	 * only semantically meaningful counts are returned.
	 *
	 * @param  string $lesson_content  The original (pre-AI) lesson content.
	 * @return int  Component count in [3, 30], or 6 when no count is detectable.
	 */
	private static function detect_component_count( $lesson_content ) {
		$count_noun_pattern = '/\b(\d+)\s+(?:component|step|principle|element|part|type|way|factor|point|item|section|rule|stage|phase|aspect|concept|skill|strategy|pillar|layer|dimension|area|domain|category|criterion|criteria|key|core|essential|fundamental|basic|important|critical|major|main)s?\b/i';
		if ( preg_match( $count_noun_pattern, $lesson_content, $matches ) ) {
			$n = (int) $matches[1];
			if ( $n >= 3 && $n <= 30 ) {
				return $n;
			}
		}
		return 6;
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
  <h3 style="color:var(--lp-secondary-color,#442e66);">[COMPONENTS_N_HEADING]</h3>
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
      <p style="margin:0 0 4px;font-weight:700;color:var(--lp-secondary-color,#442e66);">[COMPONENT_N_TITLE]</p>
      <p style="margin:0;font-size:0.85rem;color:#555;">[COMPONENT_N_DESC]</p>
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
			. 'You will receive lesson content and an HTML template. Transform the lesson content into the template. '
			. 'CRITICAL: Output ONLY the raw HTML code — no markdown, no code fences, no explanation. '
			. 'The HTML must start exactly with <div class="lp-ai-lesson-template"> and end with </div>. '
			. 'Replace every [PLACEHOLDER] token with content tightly relevant to the original lesson topic. '
			. 'Preserve ALL inline styles exactly as written — do not add, remove or alter any style attributes. '
			. 'EXCEPTION: For the Document Components grid in Template 15, you MAY replicate the card <div> pattern to produce the exact number of component cards stated in the user message — adding more card divs than shown in the template is permitted and required when a count is given. '
			. 'Keep CSS variable references exactly as written: var(--lp-primary-color, #ffb606) and var(--lp-secondary-color, #442e66). '
			. 'REQUIRED: Replace [OUTCOME_1] through [OUTCOME_4] in the Learning Outcomes section with 3-4 specific, actionable outcomes derived from the lesson content. '
			. 'REQUIRED: Replace [OPENING_HOOK_STATEMENT] with a compelling, context-setting statement drawn directly from the lesson topic. '
			. 'CRITICAL: Preserve the [Capstone Box] sentinel exactly as written inside its <div> — do NOT replace it with any text or form element. '
			. 'FORBIDDEN: Do NOT add any "Check for Understanding", quiz, or multiple-choice question section. '
			. 'Do NOT add Tailwind classes, Font Awesome, or any external CSS/JS references. '
			. 'READING TIME CONSTRAINT: Total reading time MUST NOT exceed 15 minutes. '
			. 'Calibrate depth and number of items per section based on the complexity of the original content — simpler topics should produce shorter output. '
			. 'Set any time-related placeholder token accordingly (e.g. "8 min" — never more than "15 min").';
	}

	/**
	 * Build the user-turn message for the template-filling generation pass.
	 *
	 * @param  string $lesson_content   Sanitized lesson content.
	 * @param  string $lesson_title     The lesson title.
	 * @param  string $template_html    The selected template HTML with [PLACEHOLDER] tokens.
	 * @param  int    $component_count  For Template 15 only: number of component cards to generate. 0 = not applicable.
	 * @return string
	 */
	private static function build_template_user_message( $lesson_content, $lesson_title, $template_html, $component_count = 0 ) {
		$title_line = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';

		$component_instruction = '';
		if ( $component_count > 0 ) {
			$component_instruction = "TEMPLATE 15 — COMPONENT GRID: The lesson heading references {$component_count} components. "
				. "Generate exactly {$component_count} card <div> blocks inside the Document Components grid section. "
				. "Replicate the card <div> pattern already shown in the template for every additional card beyond those provided. "
				. "Number each card badge sequentially (01, 02, … " . sprintf( '%02d', $component_count ) . "). "
				. "You MUST produce all {$component_count} cards — do not stop early.\n\n";
		}

		return "Transform the following lesson content into the provided HTML template. "
			. "Replace every [PLACEHOLDER] token with content derived from the original lesson. "
			. "Do not alter any HTML structure, inline styles, or CSS variable names. "
			. "Fill [OUTCOME_1] through [OUTCOME_4] with specific, actionable learning outcomes from the lesson. "
			. "Fill [OPENING_HOOK_STATEMENT] with a compelling framing statement relevant to the lesson topic. "
			. "Preserve the [Capstone Box] sentinel exactly as-is inside its parent <div>.\n\n"
			. $component_instruction
			. $title_line
			. "ORIGINAL LESSON CONTENT:\n{$lesson_content}\n\n"
			. "TEMPLATE TO FILL IN:\n{$template_html}";
	}
}
