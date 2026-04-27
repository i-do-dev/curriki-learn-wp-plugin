# LearnPress Data Access Audit

Date: 2026-03-30
Status: Phase 2 in progress. Repositoryization started for grades and trek events, with first REST migrations applied.

## Classification Rules

- A: Replaceable with LearnPress core API or class without behavior regression.
- B: Keep in repository abstraction for now (core API gap or schema compatibility need).
- C: Unavoidable direct SQL currently outside repository. Needs containment and hardening.

## Inventory Matrix

| File | Method or Area | Current Access Pattern | Class | Replacement Target | Risk Notes |
|---|---|---|---|---|---|
| lms/repositories/class-learnpress-section-repository.php | get_course_id_by_item_id | LearnPress tables with SQL fallback | B -> A path started | Added guarded LearnPress API-first probes before SQL fallback | LearnPress API shape varies by version; keep fallback active |
| lms/repositories/class-learnpress-section-repository.php | get_section_name_by_item_id | LearnPress tables join | B -> A path started | Added guarded LearnPress API-first probes before SQL fallback | Section object fields differ across LP versions |
| lms/class-learnpress-lesson-extension.php | resolve_course_id_for_lesson | repository lookup from lesson item relation | B | Keep repository entrypoint, now API-first internally | Launch and save critical path, avoid broad rewrite |
| lms/lms-rest-apis/courses.php | get_lxp_sections/get_lxp_course_section_lessons/get_lxp_lessons_by_course | repository calls | B | Keep repository wrapper and improve internals | Low risk if return shape unchanged |
| lms/lms-rest-apis/lms-rest-api.php | course section CRUD helpers | repository calls on custom section table shape | B | Keep repository, evaluate LP DB class parity later | High coupling to existing UI payloads |
| lms/templates/tinyLxpTheme/page-learner-lesson.php | section_name resolution | repository call from template | B | Keep callsite, repository now API-first | Template expects string only |
| includes/widgets/lxp-student-grade-summary-widget.php | section_title resolution | repository call from widget | B | Keep callsite, repository now API-first | Cached response should remain string |
| lms/repositories/class-grades-repository.php | get_grade_id/upsert_score | dedicated repository with prepare + wpdb update/insert | B | Keep centralized for plugin grade table access | Endpoint payload parity must be preserved |
| lms/repositories/class-trek-event-repository.php | event + student assignment query helpers | dedicated repository with prepare + wpdb update/insert/delete | B | Keep centralized for plugin trek tables | Join query behavior must stay compatible |
| lms/lms-rest-apis/lms-rest-api.php | store_grade, trek event CRUD, assigned-student endpoints | now routed through repositories | B (migrated from C) | Keep repository owner model | Validate unchanged response keys |
| lms/lms-rest-apis/assignment-submissions.php | tiny_lms_grades video activity scoring | now routed through grades repository | B (migrated from C) | Keep shared upsert logic | Ensure same score semantics |

## Implemented In This Pass

1. Repository now attempts LearnPress API or DB class resolution before SQL fallback in:
   - get_course_id_by_item_id
   - get_section_name_by_item_id
2. Added dedicated plugin-table repositories:
   - class-grades-repository-interface.php
   - class-grades-repository.php
   - class-trek-event-repository-interface.php
   - class-trek-event-repository.php
3. Migrated lms-rest-api methods to repositories for:
   - store_grade
   - store_course_event
   - update_course_event
   - get_course_event
   - delete_course_event
   - course_section_assigned_students
   - course_assigned_students
   - course_get_unassigned_students
   - course_unassign_student
   - course_section_assigned_students_store
4. Migrated assignment-submissions grade upsert to shared grades repository.
5. Fallback SQL behavior for LearnPress section mapping remains unchanged to preserve compatibility.

## Next Implementation Steps

1. Add quick regression checks for:
   - lesson admin save of LTI metadata
   - REST lesson insert with LTI metadata
   - learner lesson embed launch
2. Review remaining direct SQL in lms-rest-api.php outside migrated methods and decide repository ownership.
3. Add small guards for malformed request payloads where route contract permits.
