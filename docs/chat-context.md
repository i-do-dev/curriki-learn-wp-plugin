Session context: AI Video link actions (saved May 30, 2026)

Summary
- Purpose: Add admin UX actions for AI-generated lesson videos: Copy Link + Insert Into Editor (HTML5 video embed). Keep artifact storage unchanged (latest-only per lesson).
- Status: Implemented and validated for PHP syntax. JS & CSS added; plan saved to session memory.

What changed
- lms/class-learnpress-lesson-extension.php: Added video action area in the AI Content Gen metabox (Play link, Copy Link, Insert Into Editor, action status area).
- admin/js/tiny-lxp-platform-post.js: Added helpers `lxpRenderVideoActionArea(url)` and `lxpInsertVideoIntoEditor(url)`, delegated handlers for copy/insert, and updated `lxpPollVideoStatus()` to call the renderer on completion. Reused `tinyLxpCopyText()` and `tinyLxpSetEditorContent()`.
- admin/css/tiny-lxp-platform-post.css: Added styles for `.lxp-ai-video-actions` and `.lxp-ai-video-action-status`.

Helpers and keys
- Post meta keys (unchanged): `lxp_lesson_video_render_id`, `lxp_lesson_video_bucket`, `lxp_lesson_video_status`, `lxp_lesson_video_url` (plugin continues to store only the latest URL).
- JS helpers to reuse: `tinyLxpCopyText()`, `tinyLxpSetEditorContent()`.

Plan & next steps
- Validation: Open a lesson edit screen, generate a video, and confirm the modal progress, then verify Play / Copy / Insert actions appear and work in Gutenberg, TinyMCE, and textarea fallback.
- Optional enhancements (future): inline preview modal, server-side version history, copy button UX improvements.

Notes
- This file is repo-visible context for the workspace.
- Session plan also saved at `/memories/session/plan.md` for in-session reference.

Files to review
- `lms/class-learnpress-lesson-extension.php`
- `admin/js/tiny-lxp-platform-post.js`
- `admin/css/tiny-lxp-platform-post.css`
