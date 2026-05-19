<?php

class TL_AI_Content_Block_Generator {

	/**
	 * Generate lesson content using ordered author-defined block markers.
	 *
	 * @param  WP_REST_Request $request Required params: post_id (int), lesson_content (string).
	 * @return WP_REST_Response|WP_Error
	 */
	public static function generate_blocks_content( WP_REST_Request $request ) {
		$post_id        = absint( $request->get_param( 'post_id' ) );
		$lesson_content = $request->get_param( 'lesson_content' );

		$validation = TL_AI_Content_Request_Helper::validate_generation_request( $post_id, $lesson_content, true );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		TL_AI_Content_Request_Helper::maybe_backup_original_content( $post_id );

		$lesson_title      = get_the_title( $post_id );
		$segments          = self::parse_block_markers( wp_unslash( $lesson_content ) );
		$lesson_context    = self::build_lesson_context( $segments );
		$rendered_segments = array();
		$errors            = array();
		$rendered_count    = 0;

		foreach ( $segments as $segment ) {
			$rendered = self::render_block( $segment['type'], $segment['content'], $post_id, $lesson_title, $lesson_context );
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
	 * Parse author content into ordered block segments.
	 *
	 * Supports plain text fences and TinyMCE paragraph-wrapped fence lines.
	 * Unmarked content becomes a prose block.
	 *
	 * @param  string $content
	 * @return array<int,array<string,string>>
	 */
	public static function parse_block_markers( $content ) {
		$segments      = array();
		$current_type  = null;
		$current_lines = array();
		$prose_lines   = array();
		$content       = preg_replace( '#</p>\s*<p>#i', "</p>\n<p>", (string) $content );
		$content       = preg_replace( '#<br\s*/?>#i', "<br />\n", $content );
		$lines         = preg_split( '/\r\n|\r|\n/', $content );

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
		return array(
			array(
				'type'         => 'hero',
				'label'        => 'Hero',
				'description'  => 'Large lesson header with tag, title, and subtitle.',
				'marker'       => ":::hero\nThis lesson guides school teams through the essentials of writing clear, usable policy documents.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'hero' ), array(
					'[LESSON_TAG]'      => 'Policy Essentials',
					'[LESSON_TITLE]'    => 'Writing Better Policy Documents',
					'[LESSON_SUBTITLE]' => 'Build a shared understanding before the formal guidance begins.',
				) ),
			),
			array(
				'type'         => 'learning-outcomes',
				'label'        => 'Learning Outcomes',
				'description'  => 'Four actionable lesson outcomes.',
				'marker'       => ":::learning-outcomes\n- Identify the core sections of a policy document\n- Understand why structure shapes how rules are interpreted\n- Review whether each section fits its intended audience\n- Revise a draft outline for greater clarity\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'learning-outcomes' ), array(
					'[OUTCOME_1]' => 'Identify the core sections of the document.',
					'[OUTCOME_2]' => 'Explain why the structure matters.',
					'[OUTCOME_3]' => 'Review the audience fit of each section.',
					'[OUTCOME_4]' => 'Revise the outline for clarity.',
				) ),
			),
			array(
				'type'         => 'opening-hook',
				'label'        => 'Opening Hook',
				'description'  => 'Context-setting quote or framing statement.',
				'marker'       => ":::opening-hook\nA policy document only works when readers can see how its rules connect to what they do every day.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'opening-hook' ), array(
					'[OPENING_HOOK_STATEMENT]' => 'A policy document only works when readers can immediately see how its rules connect to daily decisions.',
				) ),
			),
			array(
				'type'         => 'capstone',
				'label'        => 'Capstone',
				'description'  => 'Final applied-response activity with the preserved sentinel box.',
				'marker'       => ":::capstone\nLearners draft a revised policy section that names the audience, states the required action, and explains how the document will be updated over time.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'capstone' ), array(
					'[CAPSTONE_PROMPT]' => 'Draft a revised policy section that clarifies who the audience is, what action is required, and how revision should happen over time.',
				) ),
			),
			array(
				'type'         => 'stats-grid',
				'label'        => 'Stats Grid',
				'description'  => 'Three metric cards for evidence or quick context.',
				'marker'       => ":::stats-grid\nMost teams that adopt a shared policy template report stronger consistency, review cycles run roughly three times faster, and revision loops fall by about forty percent.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'stats-grid' ), array(
					'[STATS_HEADING]' => 'Why this matters',
					'[STAT_1_VALUE]'  => '72%',
					'[STAT_1_LABEL]'  => 'Teams that used a shared template',
					'[STAT_2_VALUE]'  => '3x',
					'[STAT_2_LABEL]'  => 'Faster review cycles',
					'[STAT_3_VALUE]'  => '41%',
					'[STAT_3_LABEL]'  => 'Fewer revision loops',
				) ),
			),
			array(
				'type'         => 'cards-grid',
				'label'        => 'Cards Grid',
				'description'  => 'Three summary cards with titles and short bodies.',
				'marker'       => ":::cards-grid\nStrong policy writing relies on three moves: naming the audience early for clarity, sequencing decisions in the order readers will encounter them, and assigning clear ownership of revisions.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'cards-grid' ), array(
					'[CARDS_HEADING]' => 'Core writing moves',
					'[CARD_1_TITLE]'  => 'Clarity',
					'[CARD_1_BODY]'   => 'Name the audience early.',
					'[CARD_2_TITLE]'  => 'Sequence',
					'[CARD_2_BODY]'   => 'Put decisions in the order readers will use them.',
					'[CARD_3_TITLE]'  => 'Revision',
					'[CARD_3_BODY]'   => 'Say who updates the document and when.',
				) ),
			),
			array(
				'type'         => 'tier-cards',
				'label'        => 'Tier Cards',
				'description'  => 'Three top-accent cards for levels, options, or approaches.',
				'marker'       => ":::tier-cards\nPolicy teams grow through three maturity levels: a starter stage with one owner and one clear action, an intermediate stage with cross-team review, and an advanced stage with full governance and a scheduled revision cycle.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'tier-cards' ), array(
					'[TIER_CARDS_HEADING]' => 'Implementation levels',
					'[TIER_CARD_1_TITLE]'  => 'Starter',
					'[TIER_CARD_1_BODY]'   => 'Single owner, one audience, one action.',
					'[TIER_CARD_2_TITLE]'  => 'Intermediate',
					'[TIER_CARD_2_BODY]'   => 'Cross-team review and approval.',
					'[TIER_CARD_3_TITLE]'  => 'Advanced',
					'[TIER_CARD_3_BODY]'   => 'Full lifecycle governance and revision schedule.',
				) ),
			),
			array(
				'type'         => 'numbered-grid',
				'label'        => 'Numbered Grid',
				'description'  => 'Dynamic numbered card grid for named components or steps.',
				'marker'       => ":::numbered-grid\nA solid policy template has four parts: purpose explains why the document exists, audience names who should use it, action tells readers exactly what to do, and revision explains how updates are made over time.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'numbered-grid', 4 ), array(
					'[COMPONENTS_N_HEADING]' => '4 policy components',
					'[COMPONENT_1_TITLE]'    => 'Purpose',
					'[COMPONENT_1_DESC]'     => 'States why the document exists.',
					'[COMPONENT_2_TITLE]'    => 'Audience',
					'[COMPONENT_2_DESC]'     => 'Names who should use it.',
					'[COMPONENT_3_TITLE]'    => 'Action',
					'[COMPONENT_3_DESC]'     => 'Explains what readers must do.',
					'[COMPONENT_4_TITLE]'    => 'Revision',
					'[COMPONENT_4_DESC]'     => 'Shows how updates are made over time.',
				) ),
			),
			array(
				'type'         => 'two-col-table',
				'label'        => 'Two-Column Table',
				'description'  => 'Comparison or mapping table with three rows.',
				'marker'       => ":::two-col-table\nA rough draft outline becomes audience-ready language, internal working notes become a public-facing explanation, and a one-time memo becomes a living policy document.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'two-col-table' ), array(
					'[TABLE_2_HEADING]'     => 'Draft to publish mapping',
					'[TABLE_2_COL_1]'       => 'Draft concept',
					'[TABLE_2_COL_2]'       => 'Published version',
					'[TABLE_2_ROW_1_LEFT]'  => 'Draft outline',
					'[TABLE_2_ROW_1_RIGHT]' => 'Final audience-ready language',
					'[TABLE_2_ROW_2_LEFT]'  => 'Internal notes',
					'[TABLE_2_ROW_2_RIGHT]' => 'Public-facing explanation',
					'[TABLE_2_ROW_3_LEFT]'  => 'One-time memo',
					'[TABLE_2_ROW_3_RIGHT]' => 'Living policy document',
				) ),
			),
			array(
				'type'         => 'three-col-table',
				'label'        => 'Three-Column Table',
				'description'  => 'Evaluation or comparison grid with three columns.',
				'marker'       => ":::three-col-table\nTeachers need clear classroom actions because they apply the policy daily. Leaders need decision checkpoints because they approve and revise it. Families need a plain-language summary because they need to understand the purpose in accessible terms.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'three-col-table' ), array(
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
				'type'         => 'contrast-panel',
				'label'        => 'Contrast Panel',
				'description'  => 'Green vs red do-or-don\'t guidance pair.',
				'marker'       => ":::contrast-panel\nGood policy writing names the audience, defines the required action, and assigns revision ownership. Weak policy writing hides how decisions are made, mixes audiences in the same instruction, and publishes without scheduling a review.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'contrast-panel' ), array(
					'[CONTRAST_LEFT_HEADING]'  => 'Always do',
					'[CONTRAST_RIGHT_HEADING]' => 'Never do',
					'[LEFT_1]'                 => 'Name the audience.',
					'[LEFT_2]'                 => 'Define the action.',
					'[LEFT_3]'                 => 'Explain the revision owner.',
					'[RIGHT_1]'                => 'Hide the decision path.',
					'[RIGHT_2]'                => 'Mix multiple audiences in one instruction.',
					'[RIGHT_3]'                => 'Publish without a review date.',
				) ),
			),
			array(
				'type'         => 'callout',
				'label'        => 'Callout',
				'description'  => 'Single highlighted text block with a yellow left border.',
				'marker'       => ":::callout\nA policy document becomes easier to use when readers know who it is for before they reach the first requirement.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'callout' ), array(
					'[CALLOUT_HEADING]' => 'Why this matters',
					'[CALLOUT_BODY]'    => 'A policy document becomes easier to use when readers can see who it is for before they reach the first requirement.',
				) ),
			),
			array(
				'type'         => 'dark-block',
				'label'        => 'Dark Block',
				'description'  => 'High-emphasis dark callout or guideline section.',
				'marker'       => ":::dark-block\nEvery published policy needs to name its owner, state what triggers a review, and include the date of the next scheduled review.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'dark-block' ), array(
					'[DARK_BLOCK_HEADING]' => 'Revision guardrail',
					'[DARK_BLOCK_BODY]'    => 'Every published policy should name the owner, the trigger for review, and the date the next review must happen.',
				) ),
			),
			array(
				'type'         => 'definition-block',
				'label'        => 'Definition Block',
				'description'  => 'Soft-tinted definition or overview section with bullets.',
				'marker'       => ":::definition-block\nStrong policy writing has four core qualities: clarity about the audience, language that makes the required action plain, visible accountability for decisions, and a built-in path for revision over time.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'definition-block' ), array(
					'[DEFINITION_HEADING]'  => 'Policy writing standards',
					'[DEFINITION_INTRO]'    => 'Strong policy writing usually includes these core qualities:',
					'[DEFINITION_ITEM_1]'   => 'Clarity of audience',
					'[DEFINITION_ITEM_2]'   => 'Clear action language',
					'[DEFINITION_ITEM_3]'   => 'Visible accountability',
					'[DEFINITION_ITEM_4]'   => 'Revision over time',
				) ),
			),
			array(
				'type'         => 'role-split',
				'label'        => 'Role Split',
				'description'  => 'Two role-perspective panels with bullets.',
				'marker'       => ":::role-split\nAuthors define scope, sequence decisions, and assign ownership. Reviewers focus on readability, check alignment with the stated goals, and confirm the document is ready for a revision cycle.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'role-split' ), array(
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
				'type'         => 'option-cards',
				'label'        => 'Option Cards',
				'description'  => 'Four 2x2 cards for options, audiences, or messages.',
				'marker'       => ":::option-cards\nThis lesson pairs with four companion resources: a teacher-facing summary of day-to-day actions, a leader review guide for approval and oversight, a family-friendly explainer in plain language, and a revision checklist for keeping the policy current.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'option-cards' ), array(
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
				'type'         => 'checklist',
				'label'        => 'Checklist',
				'description'  => 'White card with four checkmarked alignment items.',
				'marker'       => ":::checklist\n- Name the audience\n- Define the required action\n- Assign ownership\n- Set the review cycle\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'checklist' ), array(
					'[CHECKLIST_HEADING]' => 'Alignment checklist',
					'[CHECK_1]'           => 'Name the audience.',
					'[CHECK_2]'           => 'Define the action.',
					'[CHECK_3]'           => 'Assign ownership.',
					'[CHECK_4]'           => 'Set the review cycle.',
				) ),
			),
			array(
				'type'         => 'cycle',
				'label'        => 'Cycle',
				'description'  => 'Four-stage lifecycle bar.',
				'marker'       => ":::cycle\nA policy moves through four stages: drafting the first version, reviewing it for audience fit and risk, publishing the approved version, and revising it after feedback or when circumstances change.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'cycle' ), array(
					'[CYCLE_HEADING]'        => 'Living document cycle',
					'[CYCLE_DRAFT_LABEL]'    => 'Draft',
					'[CYCLE_DRAFT]'          => 'Build the first version.',
					'[CYCLE_REVIEW_LABEL]'   => 'Review',
					'[CYCLE_REVIEW]'         => 'Check audience fit and risk.',
					'[CYCLE_PUBLISH_LABEL]'  => 'Publish',
					'[CYCLE_PUBLISH]'        => 'Release the approved version.',
					'[CYCLE_REVISE_LABEL]'   => 'Revise',
					'[CYCLE_REVISE]'         => 'Update after feedback or policy shifts.',
				) ),
			),
			array(
				'type'         => 'myth-reality',
				'label'        => 'Myth vs Reality',
				'description'  => 'Three-row two-column comparison table.',
				'marker'       => ":::myth-reality\nThree beliefs hold policy writers back. Many think the work is just formatting, that one sign-off is enough, and that plain language means less detail. In reality, structure shapes how rules are understood, revision must be built in from the start, and plain language makes the required action far clearer.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'myth-reality' ), array(
					'[MYTH_REALITY_HEADING]' => 'Myth vs Reality',
					'[MYTH_1]'              => 'Policy writing is just formatting.',
					'[REALITY_1]'           => 'The structure shapes how people interpret the rule.',
					'[MYTH_2]'              => 'One approval is enough.',
					'[REALITY_2]'           => 'Good policy documents build in revision.',
					'[MYTH_3]'              => 'Plain language means less detail.',
					'[REALITY_3]'           => 'Plain language makes the action easier to follow.',
				) ),
			),
			array(
				'type'         => 'blockquote',
				'label'        => 'Blockquote',
				'description'  => 'Accent-bordered pull-quote for a key insight or memorable statement.',
				'marker'       => ":::blockquote\nThe educators who will have the greatest professional impact are not those who work harder in isolation — they are those who build once and share widely, turning personal efficiency into school-wide transformation.\n:::",
				'preview_html' => TL_AI_Content_Template_Library::render_preview_template( TL_AI_Content_Template_Library::get_block_template( 'blockquote' ), array(
					'[BLOCKQUOTE_TEXT]' => 'The educators who will have the greatest professional impact are not those who work harder in isolation — they build once and share widely.',
				) ),
			),
		);
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
	private static function render_block( $type, $content, $post_id, $lesson_title = '', $lesson_context = '' ) {
		$type           = strtolower( trim( (string) $type ) );
		$raw_content    = trim( (string) $content );
		$template_title = $lesson_title;

		if ( 'prose' === $type ) {
			return self::render_prose_block( $raw_content );
		}

		if ( 'hero' === $type ) {
			$template_title = get_the_title( $post_id );
		}

		$template_html = TL_AI_Content_Template_Library::get_block_template(
			$type,
			'numbered-grid' === $type ? TL_AI_Content_Template_Library::detect_component_count( $raw_content ) : 0
		);

		if ( empty( $template_html ) ) {
			return new WP_Error( 'unknown_block_type', sprintf( 'Unsupported block type "%s".', $type ) );
		}

		$system_prompt = self::build_block_system_prompt( $type, $template_title, $lesson_context );
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
	 * Prompt for a single block rendering call.
	 *
	 * @param  string $block_type
	 * @param  string $lesson_title
	 * @return string
	 */
	private static function build_block_system_prompt( $block_type, $lesson_title = '', $lesson_context = '' ) {
		$title_instruction   = '';
		$policy_instruction  = '';
		$context_instruction = '';
		if ( 'hero' === $block_type && ! empty( $lesson_title ) ) {
			$title_instruction = ' Use "' . $lesson_title . '" exactly for the [LESSON_TITLE] placeholder.';
		}
		if ( ! empty( $lesson_context ) ) {
			$context_instruction = ' This block is one part of a multi-section lesson. LESSON OVERVIEW (all sections in order): ' . $lesson_context . ' Use this overview to keep your output tonally aligned with the other sections, avoid repeating content already covered elsewhere, and reinforce the lesson\'s central message where relevant.';
		}

		switch ( self::get_block_prompt_policy( $block_type ) ) {
			case 'shell':
				$policy_instruction = ' You may enhance and rephrase the source for clarity and engagement, but keep the meaning, intent, and key points faithful to the author input.';
				break;
			case 'preserve-close':
				$policy_instruction = ' Preserve the author intent and any explicit comparisons, contrasts, or pairings as closely as possible, making only minimal edits needed to fit the template cleanly.';
				break;
			case 'structured':
			default:
				$policy_instruction = ' You may reorganize and lightly rewrite the source to fit the card or table structure, infer the most natural grouping from the author notes, and tighten wording for readability, but never invent new claims, facts, examples, scenarios, or data points.';
				break;
		}

		return 'You are an expert instructional designer formatting one lesson section at a time.'
			. $title_instruction
			. $context_instruction
			. $policy_instruction . ' '
			. 'The source content is the author\'s own notes about the lesson subject: topic sentences, key points, comparisons, or a short description of what this section should communicate. '
			. 'Interpret those notes as content intent and derive the most suitable headings, labels, rows, or card text from them. '
			. 'Output ONLY the raw HTML for the provided section - no markdown, no explanations, no surrounding wrapper. '
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
		$title_line          = ! empty( $lesson_title ) ? "LESSON TITLE: {$lesson_title}\n\n" : '';
		$message_instruction = '';

		switch ( self::get_block_prompt_policy( $block_type ) ) {
			case 'shell':
				$message_instruction = 'You may enhance and rephrase for readability while staying faithful to the source intent and key points.';
				break;
			case 'preserve-close':
				$message_instruction = 'Preserve explicit author comparisons or pairings closely and make only minimal template-fitting edits.';
				break;
			case 'structured':
			default:
				$message_instruction = 'Infer the most natural grouping and structure from the author notes and fit them into the template without inventing new claims, examples, or facts.';
				break;
		}

		$count_hint = '';
		if ( in_array( $block_type, self::get_list_blocks(), true ) ) {
			$detected = self::detect_item_count( $block_content );
			if ( $detected > 0 ) {
				$count_hint = "\n\nITEM COUNT HINT: The author's notes describe approximately {$detected} items. Adapt the repeating inner elements of the template to produce exactly {$detected} items. Replicate the HTML and inline CSS of the existing item elements for any additional items; remove excess elements if fewer are needed. Do not leave unfilled [PLACEHOLDER] tokens in the output.";
			}
		}

		return "Render the following {$block_type} block into the provided HTML section template. "
			. "The source is the author's own notes about what this section should say — topic sentences, key points, or a short description of the lesson content. Use it to fill the placeholders and keep the template structure unchanged. {$message_instruction}\n\n"
			. $title_line
			. "SOURCE BLOCK CONTENT:\n{$block_content}\n\n"
			. "SECTION TEMPLATE:\n{$template_html}"
			. $count_hint;
	}

	/**
	 * Get the prompt policy for a block type.
	 *
	 * @param  string $block_type
	 * @return string
	 */
	private static function get_block_prompt_policy( $block_type ) {
		switch ( $block_type ) {
			case 'hero':
			case 'learning-outcomes':
			case 'opening-hook':
			case 'capstone':
			case 'blockquote':
				return 'shell';

			case 'cycle':
			case 'myth-reality':
			case 'contrast-panel':
			case 'role-split':
			case 'definition-block':
			case 'callout':
			case 'dark-block':
			case 'checklist':
				return 'preserve-close';

			case 'stats-grid':
			case 'cards-grid':
			case 'tier-cards':
			case 'numbered-grid':
			case 'two-col-table':
			case 'three-col-table':
			case 'option-cards':
			default:
				return 'structured';
		}
	}

	/**
	 * Build a compact lesson context string from all parsed non-prose segments.
	 * Used to give each block render call awareness of the full lesson scope.
	 *
	 * @param  array $segments
	 * @return string
	 */
	private static function build_lesson_context( $segments ) {
		$lines = array();
		foreach ( $segments as $seg ) {
			if ( 'prose' === $seg['type'] ) {
				continue;
			}
			$lines[] = '[' . $seg['type'] . '] ' . wp_strip_all_tags( $seg['content'] );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Detect the number of discrete items in block content.
	 *
	 * Detection order:
	 * 1. Count bullet / numbered lines (-, *, bullet char, N.).
	 * 2. Count sentence transitions as a rough sentence count (only when >= 3 and no bullets).
	 * 3. Return $default (0 = let AI decide freely) if neither heuristic fires.
	 *
	 * @param  string $content
	 * @param  int    $default
	 * @return int
	 */
	private static function detect_item_count( $content, $default = 0 ) {
		$content = wp_strip_all_tags( (string) $content );
		$lines   = preg_split( '/\r\n|\r|\n/', $content );

		// Count explicit bullet / numbered lines.
		$bullet_count = 0;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^\s*([-*\x{2022}]|\d+[.):]\s)\s*\S/u', $line ) ) {
				++$bullet_count;
			}
		}
		if ( $bullet_count >= 2 ) {
			return $bullet_count;
		}

		// Fall back to sentence count when no bullets are found.
		$sentence_count = preg_match_all( '/(?<=[.!?])\s+(?=[A-Z])|[.!?]\s*$/', $content );
		if ( $sentence_count >= 3 ) {
			return $sentence_count;
		}

		return $default;
	}

	/**
	 * Block types whose inner items can vary in count based on author input.
	 * Used by build_block_user_message() to emit a dynamic item-count hint.
	 *
	 * @return string[]
	 */
	private static function get_list_blocks() {
		return array(
			'learning-outcomes',
			'stats-grid',
			'cards-grid',
			'tier-cards',
			'checklist',
			'cycle',
			'myth-reality',
			'two-col-table',
			'three-col-table',
			'contrast-panel',
			'role-split',
			'option-cards',
			'definition-block',
		);
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
}
