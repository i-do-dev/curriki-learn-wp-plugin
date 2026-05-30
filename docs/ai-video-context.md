AI Video feature context (complete)

Overview
- This feature enables lesson authors to generate a 60-second animated video from lesson content using AWS Bedrock for script generation and Remotion Lambda for rendering.
- It lives entirely in the WordPress lesson edit UI and stores only the latest rendered video artifact per lesson.

Core files
- `lms/lms-rest-apis/ai-video.php`: REST routes and backend workflow.
- `lms/class-learnpress-lesson-extension.php`: admin metabox UI for AI video generation and status display.
- `admin/js/tiny-lxp-platform-post.js`: modal behavior, prompt editing, AJAX requests, polling, and post-render action UI.
- `admin/css/tiny-lxp-platform-post.css`: styles for the modal and action buttons.

Feature flow
1. Lesson editor displays an AI Video section in the AI Content Gen metabox.
2. Author clicks `Generate Video`, opens a modal prefilled with lesson title.
3. Author edits the prompt or inserts scene layout markers using the layout picker.
4. Author submits; the client POSTs to `/wp-json/lms/v1/lesson/ai-video`.
5. Server sends the prompt to Bedrock, receives JSON script, invokes Remotion Lambda render, and persists render metadata with status `processing`.
6. The client polls the same endpoint every 5 seconds until status becomes `done` or `error`.
7. On completion, the metabox displays a Play Video link and the feature provides Copy Link and Insert Into Editor actions.

Current behavior
- Only the latest render is tracked per lesson.
- On new generation, the previous `lxp_lesson_video_url` is deleted and replaced.
- The UI does not currently provide a video history or version archive.
- The generated video URL is visible only in the lesson admin metabox, not on the public frontend.

Admin UX now includes
- `Play Last Generated Video` link
- `Copy Link` button (copies URL to clipboard)
- `Insert Into Editor` button (inserts HTML5 `<video>` embed into Gutenberg/TinyMCE/textarea)
- inline feedback messages for copy and insert actions

Backend keys and persistence
- `lxp_lesson_video_render_id`: current Remotion render id
- `lxp_lesson_video_bucket`: render artifact bucket/source
- `lxp_lesson_video_status`: `processing` | `done` | `error`
- `lxp_lesson_video_url`: final rendered video URL

Next steps for feature expansion
- expose the video URL on the lesson frontend or page template
- add render history and artifact versioning
- add a direct preview player in the admin modal
- add a `Copy Embed Code` action in addition to Copy Link
- improve authorization checks for the REST endpoints
