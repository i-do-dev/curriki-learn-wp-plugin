<?php
/**
 * REST endpoints for AI-powered lesson video generation via Remotion Lambda.
 *
 * POST /wp-json/lms/v1/lesson/ai-video
 *   Generates an 8-scene JSON script via Bedrock, triggers Remotion Lambda render.
 *   Returns { render_id, status: 'processing' }.
 *
 * GET /wp-json/lms/v1/lesson/ai-video
 *   Polls render progress. Returns { status, progress, video_url }.
 */

use Remotion\LambdaPhp\PHPClient;
use Remotion\LambdaPhp\RenderParams;

class Rest_Lxp_AI_Video {

	// Remotion composition ID (must match Root.tsx)
	const COMPOSITION_ID = 'LessonVideo';

	// Post meta keys
	const META_RENDER_ID = 'lxp_lesson_video_render_id';
	const META_BUCKET    = 'lxp_lesson_video_bucket';
	const META_STATUS    = 'lxp_lesson_video_status';
	const META_URL       = 'lxp_lesson_video_url';

	public static function init() {
		self::register_routes();
	}

	public static function register_routes() {
		register_rest_route( 'lms/v1', '/lesson/ai-video', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'Rest_Lxp_AI_Video', 'get_video_status' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( 'Rest_Lxp_AI_Video', 'trigger_video_render' ),
				'permission_callback' => '__return_true',
			),
		) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// POST  /lms/v1/lesson/ai-video
	// ─────────────────────────────────────────────────────────────────────────

	public static function trigger_video_render( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$post_id = absint( $body['post_id'] ?? 0 );
		$prompt  = sanitize_textarea_field( $body['prompt'] ?? '' );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}
		if ( empty( $prompt ) ) {
			return new WP_Error( 'missing_prompt', 'A lesson description prompt is required.', array( 'status' => 400 ) );
		}
		
		/*
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to edit this post.', array( 'status' => 403 ) );
		}
		*/

		$post_title = get_the_title( $post_id );

		// ── Step 1: Generate 8-scene JSON via AWS Bedrock ────────────────────
		$user_message = self::build_user_message( $post_title, $prompt );
		$system_prompt = self::build_system_prompt();

		$json_response = TL_AWS_Bedrock_Client::invoke_bedrock( $user_message, $system_prompt, 4096 );

		if ( is_wp_error( $json_response ) ) {
			return $json_response;
		}

		// Strip any accidental markdown fences Bedrock might add
		$clean_json = trim( $json_response );
		$clean_json = preg_replace( '/^```(?:json)?\s*/i', '', $clean_json );
		$clean_json = preg_replace( '/\s*```$/', '', $clean_json );

		$script = json_decode( $clean_json, true );
		if ( ! is_array( $script ) || empty( $script['scenes'] ) ) {
			return new WP_Error(
				'bedrock_invalid_json',
				'Bedrock did not return valid JSON. Response: ' . substr( $clean_json, 0, 200 ),
				array( 'status' => 502 )
			);
		}

		// ── Step 2: Fire off Remotion Lambda render ───────────────────────────
		$region        = get_option( 'tl_remotion_region', 'us-east-2' );
		$function_name = get_option( 'tl_remotion_function_name', '' );
		$serve_url     = get_option( 'tl_remotion_serve_url', '' );

		if ( empty( $function_name ) || empty( $serve_url ) ) {
			return new WP_Error(
				'remotion_not_configured',
				'Remotion Lambda is not configured. Set the function name and serve URL in Tiny LXP Platform Settings.',
				array( 'status' => 503 )
			);
		}

		try {
			// The Remotion PHP SDK resolves AWS credentials the same way the AWS PHP SDK
			// does — from the EC2 instance IAM role via CredentialProvider::defaultProvider().
			$client = new PHPClient( $region, $serve_url, $function_name, null );

			$params = new RenderParams();
			$params->setComposition( self::COMPOSITION_ID );
			$params->setInputProps( $script );
			$params->setCodec( 'h264' );

			$result = $client->renderMediaOnLambda( $params );

			if ( empty( $result->renderId ) || empty( $result->bucketName ) ) {
				return new WP_Error( 'remotion_error', 'Remotion Lambda did not return a render ID.', array( 'status' => 502 ) );
			}
		} catch ( \Exception $e ) {
			return new WP_Error( 'remotion_exception', $e->getMessage(), array( 'status' => 502 ) );
		}

		// ── Step 3: Persist render metadata to post meta ─────────────────────
		update_post_meta( $post_id, self::META_RENDER_ID, sanitize_text_field( $result->renderId ) );
		update_post_meta( $post_id, self::META_BUCKET, sanitize_text_field( $result->bucketName ) );
		update_post_meta( $post_id, self::META_STATUS, 'processing' );
		delete_post_meta( $post_id, self::META_URL );

		return rest_ensure_response( array(
			'render_id' => $result->renderId,
			'status'    => 'processing',
		) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// GET  /lms/v1/lesson/ai-video
	// ─────────────────────────────────────────────────────────────────────────

	public static function get_video_status( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}
		/*
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to view this.', array( 'status' => 403 ) );
		}
		*/
		$render_id = get_post_meta( $post_id, self::META_RENDER_ID, true );
		$bucket    = get_post_meta( $post_id, self::META_BUCKET, true );
		$status    = get_post_meta( $post_id, self::META_STATUS, true );

		// If already done or error (cached), return immediately
		if ( $status === 'done' ) {
			$video_url = get_post_meta( $post_id, self::META_URL, true );
			return rest_ensure_response( array(
				'status'    => 'done',
				'progress'  => 1,
				'video_url' => $video_url ?: '',
			) );
		}
		if ( $status === 'error' ) {
			return rest_ensure_response( array( 'status' => 'error', 'progress' => 0 ) );
		}

		if ( empty( $render_id ) || empty( $bucket ) ) {
			return rest_ensure_response( array( 'status' => 'not_started', 'progress' => 0 ) );
		}

		// Poll Remotion Lambda for progress
		$region        = get_option( 'tl_remotion_region', 'us-east-2' );
		$function_name = get_option( 'tl_remotion_function_name', '' );
		$serve_url     = get_option( 'tl_remotion_serve_url', '' );

		try {
			$client   = new PHPClient( $region, $serve_url, $function_name, null );
			$progress = $client->getRenderProgress( $render_id, $bucket );
		} catch ( \Exception $e ) {
			return new WP_Error( 'remotion_exception', $e->getMessage(), array( 'status' => 502 ) );
		}

		if ( ! empty( $progress->fatalErrorEncountered ) ) {
			update_post_meta( $post_id, self::META_STATUS, 'error' );
			return rest_ensure_response( array( 'status' => 'error', 'progress' => 0 ) );
		}

		if ( ! empty( $progress->done ) && ! empty( $progress->outputFile ) ) {
			$video_url = sanitize_url( $progress->outputFile );
			update_post_meta( $post_id, self::META_STATUS, 'done' );
			update_post_meta( $post_id, self::META_URL, $video_url );
			return rest_ensure_response( array(
				'status'    => 'done',
				'progress'  => 1,
				'video_url' => $video_url,
			) );
		}

		$pct = isset( $progress->overallProgress ) ? (float) $progress->overallProgress : 0.0;
		return rest_ensure_response( array(
			'status'   => 'processing',
			'progress' => $pct,
		) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// PROMPT BUILDERS
	// ─────────────────────────────────────────────────────────────────────────

	private static function build_system_prompt(): string {
		return <<<'PROMPT'
You are a professional instructional video designer and motion graphics director. You apply modern video design principles: purposeful layout selection, visual hierarchy, progressive content disclosure, scene rhythm, and thematic color harmony. Your output powers a real animated lesson video watched by students, so every design decision must serve clarity and engagement.

Your output MUST be a single valid JSON object — no markdown fences, no explanations, no text outside the JSON.

The JSON must follow this exact schema:
{
  "title": "<lesson title (string)>",
  "accent": "<color theme — see ACCENT SELECTION table below>",
  "scenes": [ <6 to 10 scene objects> ]
}

Each scene object:
{
  "layout": "<layout type — see list below>",
  "title": "<max 6 words, accent-colour heading displayed on screen>",
  "on_screen_text": "<max 10 words, key white phrase displayed on screen>",
  "narration": "<1-2 complete spoken sentences for voice-over or subtitle>",
  "items": [ <array of SceneItem objects — see per-layout requirements below> ],
  "duration_frames": <integer between 150 and 240>
}

SceneItem shape:
{
  "text": "<required — the display label>",
  "sub_label": "<optional — secondary detail line>",
  "featured": <optional boolean — this item is the hero/recommended choice>,
  "role": "<optional — 'input' | 'output' | 'bad' | 'good'>",
  "status": "<optional — 'pass' | 'gap' | 'warn'>",
  "icon": "<optional — a single emoji that adds immediate semantic meaning; omit when uncertain. Recommended: 🎯 📊 ⚡ 🔒 🌐 🔧 📱 💡 🚀 📈 🛡️ 🔄 ✅ ⚠️ 🏆 👥 💼 🧩 📋 🎓 🔍 🌱 ⚖️ 🔬 📡 🏛️ 💬 📐 🗂️>"
}

ACCENT SELECTION — choose the palette that best matches the lesson's primary domain:
| accent value  | domain                                                    |
|---------------|-----------------------------------------------------------|
| gold          | General / business / finance / history (default)          |
| cyan_orange   | Technology / STEM / programming / engineering             |
| emerald       | Health / science / environment / growth / biology         |
| violet        | Creativity / AI / design / innovation / arts              |
| rose          | Leadership / management / communication / social sciences |
| teal          | Data / systems / analytics / digital / information        |
Prefer specificity — a lesson on machine learning belongs to 'violet', not the generic 'gold'.

AVAILABLE LAYOUTS and their items[] contract:
| layout             | items[] contract                                                                                 |
|--------------------|--------------------------------------------------------------------------------------------------|
| intro              | 3-6 items — concept/topic names that orbit the title card                                        |
| problem            | 3-5 items — task or pain-point names; set featured:true on the key problem item                  |
| framework          | 4-6 items — architecture component names (shown as numbered blueprint blocks)                    |
| process            | 3-5 items — ordered pipeline stage names (animated left-to-right with a connecting accent line)  |
| contrast           | 2-3 items — set role:'bad' on the overloaded/before item, role:'good' on the focused/after items |
| evaluation         | 3-5 items — test case labels; set status:'gap' on the item that reveals a weakness               |
| options            | 3-4 items — option names; set featured:true on the recommended choice                            |
| conclusion         | 3-5 items — call-to-action cycle stage names (e.g. Draft, Test, Share, Refine)                  |
| card_list          | 3-6 items — plain card list (same visual as 'problem'); set featured:true on standout item       |
| branching_flow     | 3-5 items — set role:'input' on exactly 1 source item; role:'output' on 2-4 branch targets       |
| before_after       | exactly 2 items — role:'bad' = before state, role:'good' = after state                          |
| quad_grid          | exactly 4 items — shown in a 2x2 grid with animated checkmarks; use sub_label for detail        |
| three_step_flow    | exactly 3 items — sequential boxes with animated arrows; use sub_label for detail               |
| cycle_loop         | exactly 4 items — nodes in a diamond-shaped cycle loop with arc arrows                          |
| split_blueprint    | 4-8 items — set role:'input' on left-column items and role:'output' on right-column items        |
| fuel_engine        | 3-5 items — set role:'input' on ingredients (left), role:'output' on exactly 1 result (right)   |
| checklist_reveal   | 3-6 items — sequentially revealed checklist; use status:'gap' or 'warn' on weak/missing items   |
| deployment_circles | exactly 4 items — concentric ring labels (innermost first, e.g. individual → team → org → all)  |

SCENE ORDERING RULES:
- First scene MUST use layout 'intro'.
- Last scene MUST use layout 'conclusion' or 'cycle_loop'.
- Middle scenes (2nd to n-1): choose layouts that best illustrate the lesson content — no rigid order required.
- Total 6 to 10 scenes; total duration_frames should sum to approximately 1800 (target ~60 seconds at 30 fps).
- Each scene duration: 150 (simple) to 240 (complex) frames.

BLOCK MODE RULES (applied when the user message lists scenes explicitly with layout and content):
- Scene count must equal the number of declared scenes exactly — do not add or remove scenes.
- Each scene layout must be exactly the declared layout — never substitute a different layout.
- Derive items[], title, on_screen_text, and narration from the block content prose.
- Scene ordering and intro/conclusion position rules do NOT apply — honour the declared order exactly.

CONTENT RULES:
- All titles, phrases, and item text must be SPECIFIC to the lesson topic — no generic placeholders.
- Narration: write confident, declarative, student-facing sentences. State a fact or principle — do not preview the slide ("In this scene we will see..."). Avoid passive voice.
- Output ONLY the raw JSON object. Any text outside the JSON causes a fatal parse error.

DESIGN PRINCIPLES:
- Layout variety: no layout type may repeat in a 6-8 scene video; in a 9-10 scene video allow at most one repeat. Structural diversity keeps the viewer engaged.
- Scene rhythm: alternate between analytical layouts (framework, quad_grid, split_blueprint, checklist_reveal) and high-impact visual layouts (process, cycle_loop, contrast, before_after, branching_flow) for natural pacing. Avoid clustering the same category of layout.
- Icon economy: assign an icon only when an emoji unambiguously matches the item concept and adds meaning a viewer will register instantly. Omit the icon field entirely when uncertain — decoration is worse than absence.
- Progressive density: open with visual impact (intro), build conceptual complexity in middle scenes, close with synthesis and call to action (conclusion or cycle_loop).
- Color selection: choose the accent that best reflects the lesson's primary domain from the ACCENT SELECTION table. Prefer specificity over defaulting to gold.
PROMPT;
	}

	/**
	 * Parse :::layout-name\n[content]\n::: blocks from a prompt string.
	 * Returns an array of ['layout' => string, 'content' => string] pairs.
	 * Returns an empty array when no markers are found.
	 */
	private static function parse_scene_blocks( string $prompt ): array {
		$blocks = array();
		if ( preg_match_all( '/:::([a-z][a-z0-9-]+)\r?\n(.*?)\r?\n:::/s', $prompt, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$blocks[] = array(
					'layout'  => str_replace( '-', '_', $m[1] ),
					'content' => trim( $m[2] ),
				);
			}
		}
		return $blocks;
	}

	private static function build_user_message( string $post_title, string $prompt ): string {
		$safe_title  = wp_strip_all_tags( $post_title );
		$safe_prompt = wp_strip_all_tags( $prompt );

		$blocks = self::parse_scene_blocks( $safe_prompt );

		if ( ! empty( $blocks ) ) {
			$count = count( $blocks );
			$msg   = "Lesson title: {$safe_title}\n\n";
			$msg  .= "Generate exactly {$count} scenes in the exact order listed below.\n";
			$msg  .= "For each scene, use ONLY the specified layout — do not substitute a different layout.\n\n";
			foreach ( $blocks as $i => $block ) {
				$num   = $i + 1;
				$msg  .= "Scene {$num} — layout: {$block['layout']}\nContent: {$block['content']}\n\n";
			}
			$msg .= 'Generate the video script JSON now.';
			return $msg;
		}

		return "Lesson title: {$safe_title}\n\nLesson description:\n{$safe_prompt}\n\nGenerate the video script JSON now (6-10 scenes).";
	}
}
