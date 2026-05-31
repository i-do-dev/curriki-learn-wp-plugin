AI Video feature context (complete)

Overview
- This feature enables lesson authors to generate an animated video from lesson content using AWS Bedrock for script generation and Remotion Lambda for rendering.
- Video duration is configurable by the author (0:30 to 5:00, default 1:00).
- The workflow is a 2-step wizard: paste raw lesson text → AI produces a structured scene script → author reviews/edits → generates video.
- Only the latest rendered video artifact is tracked per lesson.
- Post meta persists the raw input, the scene script, the target duration, and the render status across sessions.

---

Core files
- `lms/lms-rest-apis/ai-video.php`: All REST routes, prompt builders, sanitisation helpers, duration params, Remotion Lambda invocation.
- `lms/class-learnpress-lesson-extension.php`: Admin metabox UI — 2-step wizard modal, layout picker, video status display.
- `admin/js/tiny-lxp-platform-post.js`: Step navigation, raw-text sanitisation, duration validation, AJAX requests, polling, and post-render action UI.
- `admin/css/tiny-lxp-platform-post.css`: Wizard step indicator, action rows, modal and button styles.
- `remotion-video-service/src/compositions/Scenes.tsx`: All 19 scene layout React components with icon, badge, description, and callout rendering.
- `remotion-video-service/src/compositions/types.ts`: TypeScript schema — InputProps, Scene, SceneItem, LayoutType.
- `remotion-video-service/src/compositions/theme.ts`: Color palette constants and Palette objects (6 palettes).
- `remotion-video-service/src/compositions/LessonVideo.tsx`: Root composition component; palette routing; LAYOUT_MAP.

---

Feature flow (current — 2-step wizard)
1. Lesson editor displays an AI Video section in the AI Content Gen metabox.
2. Author clicks `Generate Video`; modal opens. If persisted content exists, the modal pre-fills Step 1 (raw text) and Step 2 (scene script) and routes directly to Step 2.
3. **Step 1 — Paste Content**: Author pastes full lesson text. JS sanitises it (strips HTML, markdown, bullet chars). Author sets target video length in M:SS format (0:30–5:00, default 1:00). Author clicks `Process with AI →`.
4. Client POSTs to `/wp-json/lms/v1/lesson/ai-video-script` with `{ post_id, raw_text, target_seconds }`. Server sanitises further, calls Bedrock with `build_script_system_prompt()`, receives `:::layout-name\n...\n:::` block-marker output, saves both raw text and script to post meta.
5. **Step 2 — Edit & Generate**: The AI-produced scene script is shown in a monospace textarea. Author can edit, reorder, or insert new `:::layout-name:::` blocks via the layout picker. Author clicks `Generate Video`.
6. Client validates the M:SS duration field, then POSTs to `/wp-json/lms/v1/lesson/ai-video` with `{ post_id, prompt, target_seconds }`.
7. Server saves the final script to meta, calls Bedrock with the full `build_system_prompt()` (JSON mode), receives scene JSON, invokes Remotion Lambda render, persists render metadata with status `processing`.
8. Client polls `GET /wp-json/lms/v1/lesson/ai-video?post_id=N` every 5 seconds until status becomes `done` or `error`.
9. On completion, the metabox shows a Play Video link and provides Copy Link and Insert Into Editor actions.

---

REST Endpoints

| Method | Route | Callback | Purpose |
|---|---|---|---|
| POST | `/wp-json/lms/v1/lesson/ai-video-script` | `Rest_Lxp_AI_Video::generate_video_script()` | Step 1 — convert raw text to block-marker script via Bedrock |
| GET | `/wp-json/lms/v1/lesson/ai-video-script` | `Rest_Lxp_AI_Video::get_video_script()` | Return persisted `raw_text`, `script`, `target_seconds` |
| POST | `/wp-json/lms/v1/lesson/ai-video` | `Rest_Lxp_AI_Video::trigger_video_render()` | Step 2 — generate JSON scene script via Bedrock + invoke Remotion Lambda |
| GET | `/wp-json/lms/v1/lesson/ai-video` | `Rest_Lxp_AI_Video::get_video_status()` | Poll Remotion Lambda render progress |

---

Backend keys and persistence

| Meta key | Value |
|---|---|
| `lxp_lesson_video_render_id` | Current Remotion render ID |
| `lxp_lesson_video_bucket` | Remotion S3 bucket name |
| `lxp_lesson_video_status` | `processing` \| `done` \| `error` |
| `lxp_lesson_video_url` | Final rendered video URL (MP4) |
| `lxp_lesson_video_raw_text` | Sanitised raw lesson text from Step 1 |
| `lxp_lesson_video_script` | Block-marker scene script (from Step 1 AI or last Generate click) |
| `lxp_lesson_video_target_seconds` | Target video duration in seconds (30–300, default 60) |
| `lxp_lesson_video_bg_clip` | Optional external background-clip URL (overlay mode); empty = none |

---

PHP class — `Rest_Lxp_AI_Video` (in `lms/lms-rest-apis/ai-video.php`)

Key methods:

| Method | Role |
|---|---|
| `generate_video_script()` | Step 1 handler: sanitise → Bedrock → persist |
| `get_video_script()` | Returns persisted raw_text / script / target_seconds |
| `trigger_video_render()` | Step 2 handler: Bedrock JSON → Remotion Lambda |
| `get_video_status()` | Polls Remotion progress and caches done/error to meta |
| `sanitize_raw_text(string)` | 7-step structural sanitisation (HTML, markdown, bullets, etc.) |
| `parse_duration_input($input)` | Accepts int seconds OR "M:SS" string; clamps to 30–300 |
| `resolve_duration_params(int)` | Maps seconds → `[target_frames, n_min, n_max, frames_min, frames_max]` |
| `build_script_system_prompt(n_min, n_max)` | Block-marker conversion prompt (Step 1) |
| `build_script_user_message(title, text)` | User message for Step 1 Bedrock call |
| `build_system_prompt(target_seconds)` | Full JSON generation prompt (Step 2) — parameterised by duration |
| `build_user_message(title, prompt)` | User message for Step 2 Bedrock call |

Duration parameter tiers (from `resolve_duration_params()`):

| Range | target_frames | scenes | frames/scene |
|---|---|---|---|
| ≤ 30s | 900 | 4–6 | 120–190 |
| ≤ 60s | 1800 | 6–10 | 150–240 |
| ≤ 90s | 2700 | 9–13 | 180–250 |
| ≤ 120s | 3600 | 12–17 | 190–260 |
| ≤ 180s | 5400 | 16–22 | 200–270 |
| > 180s | seconds × 30 | dynamic (n_ideal ± 3) | 210–280 |

---

Remotion service (`remotion-video-service/`)

The Remotion project is a React/TypeScript app deployed to AWS Lambda via `npm run deploy-site`. It is NOT in the WordPress plugin's build pipeline — changes require a separate deploy.

**Deploy command:**
```bash
cd remotion-video-service
npm run deploy-site   # updates the Remotion site bundle; copy new serve URL to WP settings if it changes
```

**Composition:** `LessonVideo` (1920×1080, 30fps). Duration is computed from `scenes[].duration_frames` sum via `calculateMetadata`.

**Color palettes** (`theme.ts`) — 6 palettes, selected via `InputProps.accent`:

| `accent` value | Primary | Alt | Domain |
|---|---|---|---|
| `gold` (default) | `#F0B429` | `#F59E0B` | General / business / finance |
| `cyan_orange` | `#22D3EE` | `#F97316` | Technology / STEM / programming |
| `emerald` | `#10B981` | `#34D399` | Health / science / environment / growth |
| `violet` | `#8B5CF6` | `#C4B5FD` | Creativity / AI / design / innovation |
| `rose` | `#F43F5E` | `#FB923C` | Leadership / management / social |
| `teal` | `#14B8A6` | `#2DD4BF` | Data / systems / analytics / digital |

All palettes render over a `#0B1A3B` (navy) background.

**Scene layouts** (`Scenes.tsx`) — 24 total (19 original + 5 Tier-1 additions):

| Layout | Description |
|---|---|
| `intro` | Title card + orbiting concept badges |
| `problem` | Stacked cards; featured item rises with accent |
| `framework` | Numbered blueprint grid |
| `process` | Left-to-right pipeline with animated accent line |
| `contrast` | Overloaded card fades → two focused cards split |
| `evaluation` | Checklist with ⚠ GAP reveal animation |
| `options` | Circles; selected option pulses with glow |
| `conclusion` | Workflow line + rotating badge |
| `card_list` | Alias of `problem` (same component) |
| `branching_flow` | 1 input → animated lines → 2–4 outputs |
| `before_after` | Card transitions bad→good at frame 55 |
| `quad_grid` | 2×2 grid; checkmarks light sequentially |
| `three_step_flow` | 3 boxes with animated arrows |
| `cycle_loop` | 4 nodes in diamond with arc arrows |
| `split_blueprint` | Two columns: left=inputs, right=outputs |
| `fuel_engine` | Inputs → pulsing engine box → output |
| `checklist_reveal` | Ordered checklist; items check off one by one |
| `deployment_circles` | 4 concentric rings expanding outward |
| `editorial` | Rich text blocks: badge pill + heading + sub + paragraph + callout |
| `comparison` | Two panels A vs B with a VS marker; optional 3rd item = merged result card |
| `gate` | Clarify/confirm checkpoint: question cards reveal, then the gate opens to the result |
| `routing` | Each item routes from a source chip into its labelled destination bucket |
| `stat_highlight` | Hero metric — single big number, or before→after (`role:'bad'`→`role:'good'`) |
| `transform_text` | One statement morphs in place: `role:'bad'` (weak) → `role:'good'` (sharp) |

**`SceneItem` fields** (`types.ts`):

| Field | Type | Purpose |
|---|---|---|
| `text` | string (required) | Main display label / heading |
| `sub_label` | string? | Secondary detail or subheading |
| `featured` | boolean? | Marks hero/recommended item |
| `role` | 'input'\|'output'\|'bad'\|'good'? | Semantic role for flow/contrast layouts |
| `status` | 'pass'\|'gap'\|'warn'? | Colour coding for evaluation/checklist |
| `icon` | string? | Single emoji rendered in the item (semantic; not decorative) |
| `badge` | string? | Short ALL-CAPS keyword pill (e.g. KEY CONCEPT, TIP, RULE) |
| `description` | string? | 1–2 sentence body paragraph (required in `editorial`) |

**`Scene` fields** (`types.ts`):

| Field | Type | Purpose |
|---|---|---|
| `layout` | LayoutType | Which of the 19 layout components to render |
| `title` | string | Max 6 words; accent-colour heading |
| `on_screen_text` | string | Max 10 words; white supporting phrase |
| `narration` | string | 1–2 spoken sentences for subtitle/voice-over |
| `items` | SceneItem[] | Content items (count and contract per layout) |
| `duration_frames` | int | Length of this scene in frames at 30fps |
| `callout` | string? | Highlighted insight box (💡 prefix, left accent bar) |

**Icon rendering** — `item.icon` is wired in: IntroScene (orbit badges), ProblemScene/CardListScene, FrameworkScene, OptionsScene, ConclusionScene, CycleLoopScene, QuadGridScene, ChecklistRevealScene, BranchingFlowScene, SplitBlueprintScene, FuelEngineScene. In circle-based layouts (Options, Conclusion, CycleLoop) the icon replaces the number when present.

**Callout rendering** — `scene.callout` renders a `CalloutBlock` component in: EditorialScene (first-class element), FrameworkScene, ProblemScene/CardListScene, ChecklistRevealScene (appended below items).

**Badge rendering** — `item.badge` renders a `BadgePill` component in all card-based layouts.

---

AI system prompts

**Step 1 — `build_script_system_prompt(n_min, n_max)`**
- Role: "lesson video scene architect"
- Output: ONLY `:::layout-name\n[description]\n:::` blocks — no JSON
- Scene count driven by `n_min`/`n_max` (from `resolve_duration_params()`)
- Layout list includes all 24 layouts; layout-to-content matching guidance included
- No-repeat rule enforced (at most 1 repeat for long scripts)

**Step 2 — `build_system_prompt(target_seconds)`**
- Role: "professional instructional video designer and motion graphics director"
- Output: a single valid JSON object `{ title, accent, scenes[] }`
- Scene count range and `duration_frames` budget are dynamic (from `resolve_duration_params()`)
- Accent selection: 6-option table with domain guidance
- SceneItem full schema including `icon`, `badge`, `description`; Scene includes `callout`
- DESIGN PRINCIPLES section: layout variety, scene rhythm, icon economy, badge labels, description usage, callout placement, editorial layout guidance, progressive density, color specificity

---

Admin UX

**Metabox structure** (in AI Content Gen sidebar metabox on `lp_lesson` edit screen):
- Generate Full Lesson, Build with Block Markers, Restore Pre-AI Content (for lesson HTML content)
- Generate Lesson Video section:
  - `Generate Video` button → opens 2-step wizard modal
  - After completion: `Play Last Generated Video` link, `Copy Link` button, `Insert Into Editor` button, inline status messages

**2-step wizard modal:**
- Step indicator bar (1 · Paste Content → 2 · Edit & Generate)
- **Step 1**: Raw text textarea (rows 9) + Video length input (M:SS, default `1:00`) + `Process with AI →` + `Restore Last Input`
- **Step 2**: Block-marker textarea (monospace) + layout picker `<select>` + Insert button + Layout Reference link + `← Back` + `Restore Last Script` + `Generate Video`
- Modal width: 520px

**Layout picker** (in Step 2 and in the content block-markers section): lists all 24 layout names including `editorial`, `comparison`, `gate`, `routing`, `stat-highlight`, `transform-text`.

---

JavaScript helpers (`admin/js/tiny-lxp-platform-post.js`)

| Function | Purpose |
|---|---|
| `lxpVideoGoToStep(n)` | Toggle Step 1/2 panels and update step indicator |
| `lxpVideoSetStep1Status(text, isError)` | Set Step 1 status message |
| `lxpSanitizeRawText(text)` | Client-side raw text clean-up (mirrors PHP) |
| `lxpParseDurationToSeconds(val)` | Validate M:SS format; return seconds or null if invalid; clamps 30–300 |
| `lxpFormatSecondsToMinSec(seconds)` | Convert integer seconds to "M:SS" string |
| `lxpRenderVideoActionArea(url)` | Rebuild the metabox play/copy/insert action area after render |
| `lxpInsertVideoIntoEditor(url)` | Insert `<video>` embed into Gutenberg / TinyMCE / textarea |
| `lxpInsertVideoBlock(slug)` | Insert `:::layout-name\n\n:::` into the Step 2 prompt textarea |
| `lxpPollVideoStatus(postId)` | Poll GET endpoint every 5s; updates modal status and action area |

---

Remotion Lambda configuration (stored in WordPress options)

| WP option | Default | Purpose |
|---|---|---|
| `tl_remotion_region` | `us-east-2` | AWS region for Lambda function and S3 |
| `tl_remotion_function_name` | — | Lambda function name from `npx remotion lambda functions deploy` |
| `tl_remotion_serve_url` | — | Remotion site URL from `npx remotion lambda sites create` |

Settings page: WP Admin → Curriki Learn → Remotion Lambda Settings (slug `curriki-learn-remotion-settings`).

AWS credentials are resolved automatically from the EC2 IAM role (same mechanism as the Bedrock client). On non-EC2 environments use environment variables or `~/.aws/credentials`.

---

External background video (overlay mode)

The author can attach **one external video clip** that plays **full-screen behind every scene** for
the whole lesson video, with the animated content composited on top as overlays.

- **Authoring**: Step 2 of the wizard has a **Background Video Clip** field — a URL input + **Browse
  Media** (`wp.media`, video library) + **Clear**. Accepts a public `.mp4/.webm/.mov` URL. Empty =
  the normal navy-background video (unchanged).
- **Data flow**: the URL is validated (`Rest_Lxp_AI_Video::sanitize_clip_url()` — http/https +
  extension allowlist), persisted to `lxp_lesson_video_bg_clip`, and injected as the top-level
  `background_clip` field of the scene JSON **after** Bedrock returns — Bedrock never sees the URL.
- **Duration**: the author's M:SS length drives the total (scene durations sum to it, as before);
  the clip is **trimmed** to that length automatically (the Remotion composition ends there). The
  clip plays its own audio (the only audio in the render).
- **Rendering** (`LessonVideo.tsx`): when `background_clip` is set, an `OffthreadVideo`
  (`objectFit: cover`) renders as the bottom layer; each scene is wrapped in `OverlayContext`
  (`overlay: true`, `anchor: scene.overlay_anchor`).
- **Art direction** (`Scenes.tsx`, driven by `OverlayContext`): `SceneWrap` drops its solid navy to
  transparent + a directional gradient scrim, and clusters content into an anchored zone
  (`overlay_anchor`: `bottom` default / `left` / `right`) to avoid the video's center subject;
  `GlassCard`/`CalloutBlock` become frosted (`backdropFilter: blur`); titles/phrases gain a
  text-shadow. When no clip is present, every scene renders exactly as before.
- **AI nudge**: when a clip is attached, `build_system_prompt($target_seconds, true)` appends an
  OVERLAY MODE hint — favor text-forward layouts (`editorial`, `intro`, `conclusion`, `card_list`,
  `checklist_reveal`, `process`), fewer items per scene, avoid center-owning layouts
  (`quad_grid`, `cycle_loop`, `deployment_circles`), and set `overlay_anchor` per scene.
- **Env constraint**: Remotion Lambda fetches the clip over the **public internet** — a clip on a
  local XAMPP `wp-content/uploads` URL (localhost) is unreachable; test with a public URL.

`InputProps.background_clip?: string` and `Scene.overlay_anchor?: 'bottom'|'left'|'right'` are the
two new schema fields (`types.ts`). No new `SceneItem` fields.

---

Inline text emphasis + named SVG icons

- **`*keyword*` emphasis**: any `*word*` span inside a title, `on_screen_text`, item text, or
  description renders in the accent colour (bold). Implemented by the `RichText` helper in
  `Scenes.tsx`, wired through `AccentTitle`/`WhitePhrase` (covers every scene) and the 5 new
  components. The accent comes from `PaletteContext` (provided once per video in `LessonVideo`).
  Plain text without asterisks is unchanged, so existing scripts are unaffected. The Step-2 prompt
  instructs the AI to emphasise sparingly (one or two words per scene).
- **Named SVG icons** (`icons.tsx`): `item.icon` may be a named glyph — `shield`, `lock`, `globe`,
  `building`, `mic`, `calendar`, `fuel`, `target`, `gauge`, `document`, `network`, `checkmark` —
  rendered as a crisp stroke SVG that inherits the surrounding text colour (`currentColor`). Any
  unrecognised `item.icon` (e.g. an emoji) falls back to the raw string via the `renderIcon()`
  helper, so all existing emoji keep working. The prompt lists the named vocabulary first.

---

Known limitations / watch-outs

- Only the **latest render** is tracked per lesson. Generating a new video deletes `lxp_lesson_video_url` and overwrites all render meta.
- `current_user_can('edit_post', $post_id)` checks are **commented out** in all four REST callbacks — authorization is not enforced at the route level.
- The generated video URL is **admin-only** — it is not exposed on the public lesson frontend.
- The Remotion `vendor/` PHP SDK is in `composer.json`; run `composer install` after clone.
- The Remotion React source (`remotion-video-service/`) has **no node_modules in git** — run `npm install` before local studio preview or deploy.
- After any change to `Scenes.tsx`, `theme.ts`, `types.ts`, `LessonVideo.tsx`, `Root.tsx`, or `icons.tsx`, the Remotion site must be **redeployed** (`npm run deploy-site`); the Lambda function itself does not need redeployment.
- A background clip makes every scene's `SceneWrap` **transparent** (overlay mode via `OverlayContext`). Any new scene component MUST use `SceneWrap` to inherit this; painting its own opaque background would hide the footage.
- The `build_system_prompt()` heredoc is now a **PHP interpolating heredoc** (not nowdoc) because it injects duration variables — do not accidentally convert it back to single-quoted nowdoc (`<<<'PROMPT'`).

---

Next steps for feature expansion
- Expose the video URL on the lesson frontend or page template
- Add render history and artifact versioning
- Add a direct preview player in the admin modal
- Add a `Copy Embed Code` action in addition to Copy Link
- Improve authorization checks for the REST endpoints (the commented-out `current_user_can` blocks are the right hook points)
- Consider adding a per-lesson video thumbnail/poster image
- Consider exposing video as a shareable public URL via a dedicated page template
