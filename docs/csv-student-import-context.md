# CSV Student Import — Feature Context

Session context: CSV student import improvements (completed June 20, 2026)

---

## Purpose

Allow school admins to bulk-import students from a CSV file. Each row creates a WordPress user
(`lxp_student` role) + a student CPT post, and optionally enrolls the student in one or more
LearnPress courses immediately.

---

## Key Files

| File | Role |
|------|------|
| `lms/lms-rest-apis/students.php` | REST handler `Rest_Lxp_Student::import()` + `enroll_user_in_courses()` |
| `lms/templates/tinyLxpTheme/lxp/admin-students.php` | Admin UI: upload form, course picker, guide modal |
| `includes/class-tiny-lxp-platform-tool.php` | `lxp_add_user_roles()` — role + capability definitions |
| `lms/templates/tinyLxpTheme/treks-src/assets/sample-students.csv` | Downloadable sample for admins |

---

## CSV Format

Exactly **6 columns**, in this order, no reordering:

| # | Column | Example | Notes |
|---|--------|---------|-------|
| 1 | `first_name` | `Ava` | Student's first name |
| 2 | `last_name` | `Stone` | Student's last name |
| 3 | `username` | `ava.stone` | Becomes WP login AND `username@tinylxp.com` email |
| 4 | `password` | `ChangeMe123!` | Plain-text; hashed by `wp_set_password()` on import |
| 5 | `grade` | `3-5` or `6` | Single value or hyphen range |
| 6 | `student_id` | `S001` | School-assigned ID stored as post meta |

- **Header row** (`first_name,last_name,...`) is auto-skipped when present.
- Rows with fewer than 6 columns → `$skipped` counter, row silently skipped.
- Rows whose generated email (`username@tinylxp.com`) already exists → `$duplicates` counter, skipped.
- File must be `.csv`; MIME allow-list: `text/csv`, `application/vnd.ms-excel`, `application/octet-stream`, `text/plain` (covers Excel-saved CSVs).

---

## REST Endpoint

`POST /wp-json/lms/v1/students/import`

**Form-data params:**

| Param | Type | Notes |
|-------|------|-------|
| `students_file` | file | The CSV file |
| `school_admin_id_imp` | int | School admin user ID |
| `student_school_id_imp` | int | School CPT post ID |
| `teacher_id_imp` | int | Teacher user ID (0 if not set) |
| `course_ids` | JSON string | e.g. `[42, 71]` — empty array = onboard only |

**Response JSON:**

```json
{
  "message": "X students imported successfully.",
  "imported": 3,
  "skipped": 1,
  "duplicates": 0,
  "enrolled": 3
}
```

---

## What Happens Per Row

1. `wp_insert_user()` — creates WP user with `lxp_student` role.
2. `wp_set_password()` — sets the plain-text CSV password (hashed by WP).
3. `wp_insert_post()` — creates `tl_student` CPT post linked to the school.
4. `enroll_user_in_courses($user_id, $course_ids)` — enrolls in any selected LP courses (skipped if `course_ids` is empty).

---

## Enrollment API

Uses `\LearnPress\Models\UserItems\UserCourseModel` — the same model LP uses in its own enroll
REST controller. **No raw SQL.** Goes through `LP_User_Items_DB::insert_data()` + `clean_caches()`
and fires `do_action('learnpress/user/course-enrolled', ...)`.

```php
$userCourse = \LearnPress\Models\UserItems\UserCourseModel::find($user_id, $course_id, true);
// skip if already enrolled
$userCourse->status     = LP_COURSE_ENROLLED;           // 'enrolled'
$userCourse->graduation = LP_COURSE_GRADUATION_IN_PROGRESS; // 'in-progress'
$userCourse->start_time = gmdate('Y-m-d H:i:s', time());
$userCourse->save();
do_action('learnpress/user/course-enrolled', $userCourse->ref_id, $course_id, $user_id);
```

---

## lxp_student Role Capabilities

Defined in `includes/class-tiny-lxp-platform-tool.php` → `lxp_add_user_roles()` (runs on every
`init`, so changes apply immediately to all existing users).

Key caps added in this session:

```php
$student->add_cap('read');               // base WP cap LearnPress learners need
$student->add_cap('read' . $course_cap); // read LP courses
$student->add_cap('read' . $lesson_cap); // read LP lessons
```

`read` was missing before this session — without it, LP's `current_user_can('read')` checks could
block students from accessing course/lesson pages.

> **Do NOT add `subscriber` as a second role** to imported students. `get_custom_role()` in
> `lms/templates/tinyLxpTheme/lxp/functions.php` keys off `$roles[0]` for dashboard routing —
> a second role doesn't affect WP access but would confuse role-switcher logic.

---

## Admin UI (admin-students.php)

All import controls (upload button, course picker, guide link, modals) are inside the
`if($school_post)` guard — they only render when a school is selected. Gate was previously
`if(isset($_GET['teacher_id']))`, which broke the Import button at school level without a teacher
context (fixed).

### Course picker

A Bootstrap multi-select (`#import-course-ids`) populated server-side with all published LP courses:

```php
get_posts(['post_type' => LP_COURSE_CPT, 'post_status' => 'publish', 'numberposts' => -1])
```

Label: **"Enroll in course(s) (optional)"** — hint text: *"Leave empty to onboard students without
enrolling — they can self-enroll later."*

### CSV format guide modal (`#csvGuideModal`)

Triggered by a link next to the Import button. Contains:
- The 6-column reference table (same as above).
- Rules list (column order, header row, file format, duplicates, short rows, course picker optional).
- **"Download sample CSV"** button: `<a download href="<?php echo esc_url($treks_src . 'assets/sample-students.csv'); ?>">`.

`$treks_src` = `TL_PLUGIN_URL . 'lms/templates/tinyLxpTheme/treks-src/'`

---

## Known Limitations / Pending Work

| Topic | Status |
|-------|--------|
| **Password handling** | Plain-text in CSV, hashed on import via `wp_set_password()`. No forced-change on first login, no auto-generated password, no email notification. **Deferred — user wants to discuss separately.** |
| Runtime test plan | `php -l` passes. Full 21-step manual test (role caps, onboard-only, enroll path, LP cache, hook firing, dedupe) not yet executed — needs running WP + LP instance. |
| MIME detection | Uses extension + allow-list; if WP's `finfo` is unreliable on the host, could still reject valid CSVs from some Excel versions. |
