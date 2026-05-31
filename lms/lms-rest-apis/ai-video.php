<?php
/**
 * REST endpoints for AI-powered lesson video generation via Remotion Lambda.
 *
 * POST /wp-json/lms/v1/lesson/ai-video-script
 *   Converts raw lesson text to structured block-marker script via Bedrock.
 *   Returns { script: string }.
 *
 * GET /wp-json/lms/v1/lesson/ai-video-script
 *   Returns persisted { raw_text, script } from post meta.
 *
 * POST /wp-json/lms/v1/lesson/ai-video
 *   Generates a JSON scene script via Bedrock, triggers Remotion Lambda render.
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

	// Post meta keys — render/status
	const META_RENDER_ID = 'lxp_lesson_video_render_id';
	const META_BUCKET    = 'lxp_lesson_video_bucket';
	const META_STATUS    = 'lxp_lesson_video_status';
	const META_URL       = 'lxp_lesson_video_url';

	// Post meta keys — script persistence (2-step workflow)
	const META_RAW_TEXT       = 'lxp_lesson_video_raw_text';
	const META_SCRIPT         = 'lxp_lesson_video_script';
	const META_TARGET_SECONDS = 'lxp_lesson_video_target_seconds';
	const META_BG_CLIP        = 'lxp_lesson_video_bg_clip';

	public static function init() {
		self::register_routes();
	}

	public static function register_routes() {
		register_rest_route( 'lms/v1', '/lesson/ai-video-script', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( 'Rest_Lxp_AI_Video', 'get_video_script' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( 'Rest_Lxp_AI_Video', 'generate_video_script' ),
				'permission_callback' => '__return_true',
			),
		) );

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
	// POST  /lms/v1/lesson/ai-video-script
	// Converts raw lesson text → block-marker scene script via Bedrock.
	// ─────────────────────────────────────────────────────────────────────────

	public static function generate_video_script( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$post_id        = absint( $body['post_id'] ?? 0 );
		$raw_text       = $body['raw_text'] ?? '';
		$target_seconds = self::parse_duration_input( $body['target_seconds'] ?? 60 );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}
		if ( empty( trim( $raw_text ) ) ) {
			return new WP_Error( 'missing_raw_text', 'Raw lesson text is required.', array( 'status' => 400 ) );
		}

		// Two-pass sanitisation: WP standard → structural clean-up
		$raw_text = sanitize_textarea_field( $raw_text );
		$raw_text = self::sanitize_raw_text( $raw_text );

		if ( empty( $raw_text ) ) {
			return new WP_Error( 'empty_after_sanitize', 'No usable text remained after sanitisation.', array( 'status' => 400 ) );
		}

		// Persist the sanitised raw text and duration preference
		update_post_meta( $post_id, self::META_RAW_TEXT, $raw_text );
		update_post_meta( $post_id, self::META_TARGET_SECONDS, $target_seconds );

		$params        = self::resolve_duration_params( $target_seconds );
		$post_title    = get_the_title( $post_id );
		$user_message  = self::build_script_user_message( $post_title, $raw_text );
		$system_prompt = self::build_script_system_prompt( $params['n_min'], $params['n_max'] );

		$script = TL_AWS_Bedrock_Client::invoke_bedrock( $user_message, $system_prompt, 2048 );

		if ( is_wp_error( $script ) ) {
			return $script;
		}

		// Strip accidental markdown fences
		$script = trim( $script );
		$script = preg_replace( '/^```[a-z]*\s*/i', '', $script );
		$script = preg_replace( '/\s*```$/', '', $script );
		$script = trim( $script );

		// Persist the generated script
		update_post_meta( $post_id, self::META_SCRIPT, $script );

		return rest_ensure_response( array( 'script' => $script ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// GET  /lms/v1/lesson/ai-video-script
	// Returns persisted raw_text and script from post meta.
	// ─────────────────────────────────────────────────────────────────────────

	public static function get_video_script( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}

		$saved_seconds = (int) get_post_meta( $post_id, self::META_TARGET_SECONDS, true );
		return rest_ensure_response( array(
			'raw_text'         => (string) get_post_meta( $post_id, self::META_RAW_TEXT, true ),
			'script'           => (string) get_post_meta( $post_id, self::META_SCRIPT, true ),
			'target_seconds'   => $saved_seconds > 0 ? $saved_seconds : 60,
			'background_clip'  => (string) get_post_meta( $post_id, self::META_BG_CLIP, true ),
		) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SANITIZE RAW TEXT  (server-side structural clean-up)
	// ─────────────────────────────────────────────────────────────────────────

	private static function sanitize_raw_text( string $text ): string {
		// 1. Strip any remaining HTML
		$text = wp_strip_all_tags( $text );

		// 2. Strip markdown headers (##, ###, etc.)
		$text = preg_replace( '/^#{1,6}\s*/m', '', $text );

		// 3. Strip markdown bold/italic wrappers (**text**, __text__, *text*, _text_)
		$text = preg_replace( '/(\*\*|__)(.+?)\1/s', '$2', $text );
		$text = preg_replace( '/(\*|_)(.+?)\1/s', '$2', $text );

		// 4. Strip markdown links [label](url) → label
		$text = preg_replace( '/\[([^\]]+)\]\([^)]+\)/', '$1', $text );

		// 5. Normalise Unicode and ASCII bullet chars at line start → '- '
		$text = preg_replace( '/^[\x{2022}\x{2023}\x{25E6}\x{2043}\x{2219}\*]\s*/mu', '- ', $text );

		// 6. Remove horizontal rules
		$text = preg_replace( '/^(-{3,}|\*{3,}|_{3,})\s*$/m', '', $text );

		// 7. Collapse 3+ consecutive blank lines to 2
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		return trim( $text );
	}

	/**
	 * Validate an optional external background-clip URL.
	 *
	 * Returns the sanitised URL, or '' when empty. Returns a WP_Error when a non-empty value
	 * is not an http(s) URL ending in an allowed video extension.
	 * Remotion Lambda must be able to fetch this URL over the public internet.
	 *
	 * @return string|WP_Error
	 */
	private static function sanitize_clip_url( $url ) {
		$url = trim( (string) $url );
		if ( $url === '' ) {
			return '';
		}

		$safe   = esc_url_raw( $url, array( 'http', 'https' ) );
		$path   = (string) wp_parse_url( $safe, PHP_URL_PATH );
		$ext    = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$allowed = array( 'mp4', 'webm', 'mov' );

		if ( empty( $safe ) || ! in_array( $ext, $allowed, true ) ) {
			return new WP_Error(
				'invalid_clip_url',
				'Background clip must be a public http(s) URL ending in .mp4, .webm, or .mov.',
				array( 'status' => 400 )
			);
		}

		return $safe;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// DURATION PARAMS RESOLVER
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Parse a duration value that is either an integer seconds or a "M:SS" string.
	 * Returns seconds clamped to [30, 300].
	 *
	 * Accepts:  90  |  "1:30"  |  "01:30"
	 */
	private static function parse_duration_input( $input ): int {
		$input = trim( (string) $input );

		// M:SS or MM:SS format
		if ( preg_match( '/^(\d{1,2}):([0-5]\d)$/', $input, $m ) ) {
			$seconds = (int) $m[1] * 60 + (int) $m[2];
		} else {
			$seconds = absint( $input );
		}

		return max( 30, min( 300, $seconds ) );
	}

	/**
	 * Map a target duration (seconds) to scene-count and frame-budget parameters.
	 *
	 * @return array{ target_frames: int, n_min: int, n_max: int, frames_min: int, frames_max: int }
	 */
	private static function resolve_duration_params( int $target_seconds ): array {
		if ( $target_seconds <= 30 ) {
			return array( 'target_frames' => 900,  'n_min' => 4,  'n_max' => 6,  'frames_min' => 120, 'frames_max' => 190 );
		}
		if ( $target_seconds <= 60 ) {
			return array( 'target_frames' => 1800, 'n_min' => 6,  'n_max' => 10, 'frames_min' => 150, 'frames_max' => 240 );
		}
		if ( $target_seconds <= 90 ) {
			return array( 'target_frames' => 2700, 'n_min' => 9,  'n_max' => 13, 'frames_min' => 180, 'frames_max' => 250 );
		}
		if ( $target_seconds <= 120 ) {
			return array( 'target_frames' => 3600, 'n_min' => 12, 'n_max' => 17, 'frames_min' => 190, 'frames_max' => 260 );
		}
		if ( $target_seconds <= 180 ) {
			return array( 'target_frames' => 5400, 'n_min' => 16, 'n_max' => 22, 'frames_min' => 200, 'frames_max' => 270 );
		}
		// 181–300 s (up to 5 min): compute dynamically
		$target_frames = $target_seconds * 30;
		$n_ideal       = (int) round( $target_frames / 215 );
		return array(
			'target_frames' => $target_frames,
			'n_min'         => max( 18, $n_ideal - 3 ),
			'n_max'         => $n_ideal + 3,
			'frames_min'    => 210,
			'frames_max'    => 280,
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SCRIPT PROMPT BUILDERS
	// ─────────────────────────────────────────────────────────────────────────

	private static function build_script_system_prompt( int $n_min = 6, int $n_max = 10 ): string {
		$repeat_rule = $n_max <= 8
			? 'No layout type may repeat.'
			: "No layout type may repeat in a {$n_min}-" . min( $n_max, 8 ) . " block script. For longer scripts allow at most one repeat.";
		return <<<PROMPT
You are a lesson video scene architect. Your task is to read raw lesson content and convert it into a structured video scene script using block markers.

Output ONLY the scene blocks — no JSON, no explanations, no intro or closing text.

Each block must follow this exact format:
:::layout-name
[1-3 sentences describing what this specific scene should show, derived from the lesson content]
:::

RULES:
- Produce exactly {$n_min} to {$n_max} scene blocks total (matching the target video duration).
- First block MUST use layout 'intro'.
- Last block MUST use layout 'conclusion' or 'cycle-loop'.
- Choose layouts from this list only: intro, problem, framework, process, contrast, evaluation, options, conclusion, card-list, branching-flow, before-after, quad-grid, three-step-flow, cycle-loop, split-blueprint, fuel-engine, checklist-reveal, deployment-circles, editorial, comparison, gate, routing, stat-highlight, transform-text
- Match layout to content type: 'editorial' for concept definitions or prose explanations; 'process' for numbered steps; 'framework' for named components; 'contrast' for before/after or good/bad comparisons; 'checklist-reveal' for criteria or requirements; 'quad-grid' for exactly 4 parallel items; 'branching-flow' for one input producing multiple outputs; 'comparison' for two competing options shown side-by-side (X vs Y); 'gate' for a clarify/confirm checkpoint where the tool asks questions before acting; 'routing' for sorting several items each to its correct destination/category; 'stat-highlight' for a single striking metric or a before→after number (e.g. 2 hours → 10 minutes); 'transform-text' for one weak statement rewritten into a sharp one; etc.
- {$repeat_rule}
- Scene descriptions must be specific to the lesson topic — no generic filler text.
- Output ONLY the raw :::layout-name\ncontent\n::: blocks. Any text outside the blocks will break the parser.
PROMPT;
	}

	private static function build_script_user_message( string $post_title, string $raw_text ): string {
		$safe_title = wp_strip_all_tags( $post_title );
		return "Lesson title: {$safe_title}\n\nRaw lesson content:\n{$raw_text}\n\nConvert this lesson into a structured video scene script using the block marker format.";
	}

	// ─────────────────────────────────────────────────────────────────────────
	// POST  /lms/v1/lesson/ai-video
	// ─────────────────────────────────────────────────────────────────────────

	public static function trigger_video_render( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$post_id        = absint( $body['post_id'] ?? 0 );
		$prompt         = sanitize_textarea_field( $body['prompt'] ?? '' );
		$target_seconds = self::parse_duration_input( $body['target_seconds'] ?? 60 );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'invalid_post_id', 'A valid post_id is required.', array( 'status' => 400 ) );
		}
		if ( empty( $prompt ) ) {
			return new WP_Error( 'missing_prompt', 'A lesson description prompt is required.', array( 'status' => 400 ) );
		}

		// Optional full-screen background clip — played behind every scene (overlay mode).
		$bg_clip = self::sanitize_clip_url( $body['background_clip'] ?? '' );
		if ( is_wp_error( $bg_clip ) ) {
			return $bg_clip;
		}
		$has_bg_clip = ( $bg_clip !== '' );

		/*
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to edit this post.', array( 'status' => 403 ) );
		}
		*/

		// Persist the final script, duration, and background clip used for this generation
		update_post_meta( $post_id, self::META_SCRIPT, $prompt );
		update_post_meta( $post_id, self::META_TARGET_SECONDS, $target_seconds );
		update_post_meta( $post_id, self::META_BG_CLIP, $bg_clip );

		$post_title = get_the_title( $post_id );

		// ── Step 1: Generate scene JSON via AWS Bedrock ───────────────────────
		// When a background clip is present, nudge Bedrock toward overlay-friendly layouts.
		$user_message  = self::build_user_message( $post_title, $prompt );
		$system_prompt = self::build_system_prompt( $target_seconds, $has_bg_clip );

		// 8192 = max output tokens for Claude Sonnet via Converse API.
		// 14-scene videos with descriptions + callouts + overlay fields exceed 4096 tokens.
		$json_response = TL_AWS_Bedrock_Client::invoke_bedrock( $user_message, $system_prompt, 8192 );

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

		// Attach the background clip as a top-level inputProp so Remotion plays it behind every scene.
		if ( $has_bg_clip ) {
			$script['background_clip'] = $bg_clip;
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

	private static function build_system_prompt( int $target_seconds = 60, bool $has_background_clip = false ): string {
		$p            = self::resolve_duration_params( $target_seconds );
		$target_frames = $p['target_frames'];
		$n_min         = $p['n_min'];
		$n_max         = $p['n_max'];
		$frames_min    = $p['frames_min'];
		$frames_max    = $p['frames_max'];
		$prompt = <<<PROMPT
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
  "text": "<required — the display label / heading>",
  "sub_label": "<optional — secondary detail line or subheading>",
  "featured": <optional boolean — this item is the hero/recommended choice>,
  "role": "<optional — 'input' | 'output' | 'bad' | 'good'>",
  "status": "<optional — 'pass' | 'gap' | 'warn'>",
  "icon": "<optional — adds immediate semantic meaning; omit when uncertain. PREFER a named icon that renders as a crisp accent-coloured glyph: shield, lock, globe, building, mic, calendar, fuel, target, gauge, document, network, checkmark. Otherwise a single emoji: 🎯 📊 ⚡ 🔒 🌐 🔧 📱 💡 🚀 📈 🛡️ 🔄 ✅ ⚠️ 🏆 👥 💼 🧩 📋 🎓 🔍 🌱 ⚖️ 🔬 📡 🏛️ 💬 📐 🗂️>",
  "badge": "<optional — a short ALL-CAPS keyword label shown as an accent pill tag. Vocabulary: KEY CONCEPT · TIP · EXAMPLE · WARNING · BEST PRACTICE · NOTE · STEP · TOOL · RULE · INSIGHT · MYTH · FACT. Use only when the item has a named semantic type.>",
  "description": "<optional — a 1-2 sentence body paragraph explaining the concept; required in editorial layout; useful in framework, card_list, split_blueprint when items need full explanations not just labels.>"
}

Scene object also accepts:
{
  "callout": "<optional — the single most important takeaway of this scene, expressed as one clear sentence. Shown as a highlighted callout box. Use in editorial (recommended), framework, problem/card_list, checklist_reveal, and whenever you want to spotlight one key insight.>"
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
| editorial          | 1-3 items — rich content blocks; each item MUST have description; badge and sub_label recommended; callout highly recommended; use for concept-explanation scenes with substantial prose |
| comparison         | exactly 2 items — items[0]=left, items[1]=right; set featured:true on the preferred side. Optional 3rd item = the merged/result card. Use for "X vs Y" or "blueprint vs bricks" scenes |
| gate               | 2-4 items — the clarifying questions or confirm checks shown before the gate opens; on_screen_text is the result once cleared. Use for "ask before acting" / confirmation-checkpoint scenes |
| routing            | 3-5 items — set item.text = the thing being sorted and item.sub_label = its destination/category bucket. Use for "route each task to the right place" scenes |
| stat_highlight     | 1-2 items — item.text = the value/number, item.sub_label = its label. For a before→after metric use exactly 2 items with role:'bad' (before) and role:'good' (after). Use for one striking metric |
| transform_text     | exactly 2 items — role:'bad' = the weak/before statement, role:'good' = the sharp/after statement. Use for "rewrite this vague instruction into a precise one" scenes |

SCENE ORDERING RULES:
- First scene MUST use layout 'intro'.
- Last scene MUST use layout 'conclusion' or 'cycle_loop'.
- Middle scenes (2nd to n-1): choose layouts that best illustrate the lesson content — no rigid order required.
- Total {$n_min} to {$n_max} scenes; total duration_frames should sum to approximately {$target_frames} (target ~{$target_seconds} seconds at 30 fps).
- Each scene duration: {$frames_min} (simple) to {$frames_max} (complex) frames.

BLOCK MODE RULES (applied when the user message lists scenes explicitly with layout and content):
- Scene count must equal the number of declared scenes exactly — do not add or remove scenes.
- Each scene layout must be exactly the declared layout — never substitute a different layout.
- Derive items[], title, on_screen_text, and narration from the block content prose.
- Scene ordering and intro/conclusion position rules do NOT apply — honour the declared order exactly.

CONTENT RULES:
- All titles, phrases, and item text must be SPECIFIC to the lesson topic — no generic placeholders.
- Emphasis: you may wrap ONE or TWO key words in *asterisks* inside on_screen_text, item text, or descriptions to render them in the accent colour (e.g. "Two *focused* tools beat one"). Use sparingly — at most a couple per scene; do NOT asterisk whole phrases. Plain text without asterisks is fine.
- Narration: write confident, declarative, student-facing sentences. State a fact or principle — do not preview the slide ("In this scene we will see..."). Avoid passive voice.
- Output ONLY the raw JSON object. Any text outside the JSON causes a fatal parse error.

DESIGN PRINCIPLES:
- Layout variety: no layout type may repeat in a 6-8 scene video; in a 9-10 scene video allow at most one repeat. Structural diversity keeps the viewer engaged.
- Scene rhythm: alternate between analytical layouts (framework, quad_grid, split_blueprint, checklist_reveal, editorial) and high-impact visual layouts (process, cycle_loop, contrast, before_after, branching_flow) for natural pacing. Avoid clustering the same category of layout.
- Icon economy: assign an icon only when an emoji unambiguously matches the item concept and adds meaning a viewer will register instantly. Omit the icon field entirely when uncertain — decoration is worse than absence.
- Badge labels: assign badge only when the item has a clear named semantic type (a TIP is different from a RULE; a MYTH is different from a FACT). Use the vocabulary provided. Do not add badges to every item.
- Description usage: add description when an item needs a full explanation — not just for items that are labels. Required in editorial; optional elsewhere. Do not pad with repetition of item.text.
- Callout placement: use callout for the scene's single most important insight. Prefer editorial for callout-heavy content. In other layouts, use callout sparingly — one callout per video is better than one per scene.
- Editorial layout: choose editorial for scenes where the content is primarily conceptual prose — definitions, comparisons, explanations, analyses. Give every editorial item both sub_label and description; always include a callout.
- Progressive density: open with visual impact (intro), build conceptual complexity in middle scenes (mix analytical + editorial), close with synthesis and call to action (conclusion or cycle_loop).
- Color selection: choose the accent that best reflects the lesson's primary domain from the ACCENT SELECTION table. Prefer specificity over defaulting to gold.
PROMPT;

		if ( $has_background_clip ) {
			$prompt .= <<<OVERLAYHINT


OVERLAY MODE (a full-screen background VIDEO plays behind every scene):
- The animated content is composited on top of live footage, so design for legibility and breathing room.
- Strongly prefer text-forward layouts that leave the frame's center open: editorial, intro, conclusion, card_list, checklist_reveal, process.
- Avoid center-owning layouts that fight the video subject: quad_grid, cycle_loop, deployment_circles. Use them at most once, only if essential.
- Keep items[] short — 2 to 4 items per scene — so panels stay sparse over the footage.
- Add an "overlay_anchor" field to EACH scene object: "bottom" (default, lower-third caption band), "left", or "right" (side column for card-heavy scenes). Vary it for rhythm; default to "bottom".
- Keep titles and on_screen_text concise; the footage carries the visual energy.
OVERLAYHINT;
		}

		return $prompt;
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
