# TinyLxp WordPress Plugin

WordPress plugin (v2.0.3) that turns a WP site into an IMS LTI 1.3 Platform + full LMS. Manages Courses, Lessons, Assignments, Students, Teachers, Classes, Groups, Schools, Districts, and Treks.

Full architecture and copilot guidance: [.github/copilot-instructions.md](.github/copilot-instructions.md)  
AI Video feature detail: [docs/ai-video-context.md](docs/ai-video-context.md)

---

## Dev Commands

```bash
composer install          # after clone or library updates — vendor/ is gitignored
php -l path/to/file.php   # syntax check before declaring done
wp rewrite flush          # after any CPT registration change
wp rest route list --namespace=lms/v1 --fields=route,methods
```

> No JS build pipeline. Edit `admin/js/`, `admin/css/`, `includes/widgets/assets/` directly.

---

## Architecture at a Glance

| Layer | Where |
|---|---|
| Plugin entry | `TinyLxp-wp-plugin.php` → `tiny-lxp-platform.php` (identical legacy duplicate — keep both) |
| Core orchestrator | `includes/class-tiny-lxp-platform.php` (`Tiny_LXP_Platform`) |
| Hook registry | `includes/class-tiny-lxp-platform-loader.php` — never call `add_action()` directly for admin/public hooks |
| Admin layer | `admin/class-tiny-lxp-platform-admin.php` + `admin/partials/` |
| LMS domain | `lms/` — CPT classes, REST APIs, repositories, templates |
| REST namespace | `lms/v1` → `/wp-json/lms/v1/` — all registered via `LMS_REST_API::init()` |
| Elementor widgets | `namespace Edudeme\Elementor` in `includes/widgets/` |

### Where to add new things

| Task | Location |
|---|---|
| New CPT | `lms/class-{entity}-post-type.php` (extend `TL_Post_Type`, singleton pattern) |
| New REST endpoint | `lms/lms-rest-apis/{entity}.php` → register in `LMS_REST_API::init()` |
| DB access | `lms/repositories/class-{domain}-repository.php` (prefer over inline SQL) |
| Admin page | `admin/class-tiny-lxp-platform-admin.php` + `admin/partials/` |
| Page template | `lms/templates/tinyLxpTheme/page-{slug}.php` |
| Dashboard partial | `lms/templates/tinyLxpTheme/lxp/{role}-{feature}.php` |
| Elementor widget | `includes/widgets/lxp-{name}-widget.php` → register in `includes/class-tiny-lxp-platform-widget.php` |
| AI Bedrock calls | `includes/class-aws-bedrock-client.php` (`TL_AWS_Bedrock_Client::invoke_bedrock`) |
| AI REST endpoints | `lms/lms-rest-apis/ai-content.php` (`Rest_Lxp_AI_Content`) |
| AI Video endpoints | `lms/lms-rest-apis/ai-video.php` (`Rest_Lxp_AI_Video`) |

---

## Critical Rules

- **Hook routing**: Admin/public hooks → `Tiny_LXP_Platform_Loader`. CPT-specific hooks → register directly in constructor.
- **Singletons**: All CPT classes must use `$_instance` / `instance()` pattern.
- **REST auth**: All routes currently use `'permission_callback' => '__return_true'`. Always implement `current_user_can()` or nonce inside the callback for write/sensitive ops.
- **No credentials in code**: Use `get_option()` — follow the `edlink_options` pattern.
- **DB**: Always `$wpdb->prefix` for table references. Always `$wpdb->prepare()` for queries. Never interpolate user input.
- **Output**: Always escape (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`). Always sanitize input.
- **Namespace in Elementor widgets**: All LP class calls inside `Edudeme\Elementor` must be prefixed with `\` (e.g., `\LP_Global::course_item()`) — bare names resolve to the wrong namespace and cause fatals.
- **LearnPress tables**: Always `$wpdb->prefix` — never hardcode `wp_` prefix.
- **Autoloader**: Don't `require_once` files already in Composer autoload.

---

## Known Gotchas (read before editing)

| # | Issue |
|---|---|
| 1 | `TL_Assingment_Post_Type` — typo (double-s) is intentional, do not fix without full audit |
| 2 | REST route `/shools/save` — missing 'c' is intentional, do not fix without migration plan |
| 3 | `TinyLxp-wp-plugin.php` and `tiny-lxp-platform.php` are duplicates — keep both |
| 4 | LP4 lesson URLs (`/{course}/lessons/{lesson}/`) — `get_queried_object_id()` returns the **course** ID, not lesson ID. Use URL slug extraction as fallback. |
| 5 | `meta_table_html()` is misnamed — it returns Learning Outcomes + Opening Hook, not a metadata table (legacy name, do not rename) |
| 6 | `aws/aws-sdk-php` pinned to `3.337.3` due to PHP version constraint — do not `composer update` without checking server PHP version |
| 7 | `[Capstone Box]` and `[Text Box]` are plain-text sentinels in post content — WP kses strips `<textarea>`. Frontend JS converts them at runtime. Never store form elements in post content. |
| 8 | Standard AI templates (15 total) have **no quiz** — `quiz_html()` returns `''`. Do not add "Check for Understanding" to standard templates. |
| 9 | `lxp-capstone.js` is a single IIFE — any syntax error silently disables all capstone boxes sitewide. Check browser console after edits. |
| 10 | AI Video REST callbacks have `current_user_can()` checks **commented out** — auth not enforced at route level. |

---

## Key Integrations

| Service | Config |
|---|---|
| AWS Bedrock (AI) | `TL_AWS_Bedrock_Client::MODEL_ID` / `::REGION` — credentials from EC2 IAM role via IMDSv2 |
| Remotion Lambda (video) | WP options: `tl_remotion_region`, `tl_remotion_function_name`, `tl_remotion_serve_url` |
| Edlink (SIS) | WP option: `edlink_options` array |
| xAPI / Curriki Studio / Tsugi | `lms/xapi-constants.php` — update constants there, never hardcode URLs |
| LearnPress | Direct `$wpdb` queries on `{prefix}learnpress_sections` — LP must be active |

---

## AI Video Feature (2-step wizard)

1. **Step 1** — author pastes raw text + sets duration (M:SS, 0:30–5:00) → POST `/lms/v1/lesson/ai-video-script` → Bedrock returns `:::layout-name\n...\n:::` block-marker script
2. **Step 2** — author edits script → POST `/lms/v1/lesson/ai-video` → Bedrock returns JSON scene array → Remotion Lambda renders MP4 → client polls GET `/lms/v1/lesson/ai-video` every 5s

19 scene layouts available (see [docs/ai-video-context.md](docs/ai-video-context.md)).  
Remotion deploy (separate from WP): `cd remotion-video-service && npm run deploy-site`  
After any change to `Scenes.tsx`, `theme.ts`, `types.ts`, or `LessonVideo.tsx` → must redeploy Remotion site.

---

## Validation Checklist (before declaring done)

- [ ] Scope matches request exactly — nothing extra added
- [ ] New PHP class: `class-{kebab-name}.php`, singleton if CPT, extends correct base
- [ ] File is autoloaded or explicitly `require_once`'d in the right bootstrap location
- [ ] REST endpoint registered in `LMS_REST_API::init()`; has capability/nonce check in callback
- [ ] No hardcoded secrets or credentials
- [ ] All DB queries use `$wpdb->prepare()`
- [ ] All output escaped; all input sanitized
- [ ] If CPT added: note that `wp rewrite flush` is required
- [ ] PHP syntax verified: `php -l {file}`
