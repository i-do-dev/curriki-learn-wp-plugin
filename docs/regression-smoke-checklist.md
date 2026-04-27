# Regression Smoke Checklist

Date: 2026-03-30
Scope: repository migration for grades and trek event queries, plus LearnPress lesson metadata flows.

## Preconditions

1. Plugin is active.
2. LearnPress is active.
3. At least one course, lesson, teacher, student, and assignment exist.
4. A lesson has LTI metadata configured (tool URL and post attribute id).

## A. Lesson Admin Save (LTI metadata)

1. Open lesson edit screen in wp-admin.
2. Update lesson LTI fields and save.
3. Verify post meta keys updated:
   - lti_tool_url
   - lti_tool_code
   - lti_content_title
   - lti_custom_attr
   - lti_post_attr_id
4. Verify tl_course_id remains set to relation-derived course id.

Expected:
- Save succeeds with no PHP warning.
- Existing launch metadata still appears on lesson launch.

## B. REST Lesson Insert or Update

1. Send REST request to create or update lp_lesson with meta containing:
   - lti_content_id
   - lti_tool_url
   - lti_tool_code
   - lti_content_title
   - lti_custom_attr
   - lti_post_attr_id
2. Verify metadata persisted.
3. Verify tl_course_id is present and corresponds to LearnPress relation when available.

Expected:
- Endpoint returns success.
- Metadata remains backward compatible with launch flow.

## C. Lesson Launch Embed

1. Open a singular lesson page as expected role.
2. Confirm iframe renders when lti_post_attr_id exists.
3. Confirm iframe launch URL includes plugin query args and post id.

Expected:
- Embed appears and loads tool content.
- No JS or PHP fatal errors.

## D. Grade Upsert Endpoint

1. Call POST /wp-json/lms/v1/scores with payload including userId and scoreGiven and lesson query param.
2. Repeat same call with a new score value.
3. Inspect tiny_lms_grades table.

Expected:
- First call inserts row.
- Second call updates same row instead of creating duplicate.

## E. Trek Event CRUD Endpoints

1. Call store trek event endpoint.
2. Call update trek event endpoint with and without trek_section_id.
3. Call get trek event endpoint for created event id.
4. Call delete trek event endpoint.

Expected:
- Returned keys remain unchanged for existing frontend consumers.
- Created event id is reused in update or delete calls.
- Deleted event no longer returns from get endpoint.

## F. Student Assignment Binding

1. Call trek section assigned students store endpoint with student_ids.
2. Call assigned students endpoint.
3. Call unassign endpoint for one student_assignment_id.
4. Call unassigned students endpoint.

Expected:
- Assignments are created and deleted correctly.
- Unassigned list excludes assigned ids and restores after unassign.

## Notes

- If any endpoint payload shape changed, record old and new JSON keys before rollout.
- If LearnPress relation lookup differs across versions, keep SQL fallback paths active and log version details.
