# Enrollment Rollover & Repair Summary

This document outlines the source of truth architecture for student enrollment in the `nuxnan` platform, analyzes the compatibility fields `class_level` and `class_section` on the `students` table, and details the Phase 9 repair execution results, including remaining legacy orphans.

---

## 1. Single Source of Truth vs. Snapshot Compatibility

In the current `nuxnan` LMS database schema:
* **Absolute Source of Truth:** [ClassroomStudent](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/ClassroomStudent.php) (`classroom_students` table) serves as the primary registry of a student's active enrollment in a classroom for a specific academic year.
* **Snapshot Compatibility Fields:** `students.class_level` and `students.class_section` are synchronized snapshots of the student's *current* active classroom. These columns are preserved exclusively to maintain compatibility with legacy APIs, UI elements (e.g., student cards, profile page sidebars, home visit selectors), and third-party consumers that read directly from the `students` table.

---

## 2. Inventory of `class_level` & `class_section` Usage

To prevent regressions, the following inventory analyzes all direct reads and writes of `class_level` and `class_section`. Each usage is classified under **Keep**, **Remove**, or **Defer**.

### Direct Writes (Database Writes & Sync Hooks)

| File / Location | Context / Logic | Classification | Rationale |
| :--- | :--- | :--- | :--- |
| [StudentEnrollmentService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentEnrollmentService.php#L143-L146) | Synced during `enrollStudent` (normalized `grade_level` + `section`). | **Keep** | Essential to keep the compatibility snapshot updated when students are enrolled. |
| [StudentEnrollmentService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentEnrollmentService.php#L254-L257) | Cleared to `null` during `graduateStudent`. | **Keep** | Clears state upon graduation so they do not show in active filters. |
| [StudentEnrollmentService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentEnrollmentService.php#L308-L311) | Cleared to `null` during `dropStudent`. | **Keep** | Clears state upon drop-out. |
| [StudentEnrollmentService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentEnrollmentService.php#L488-L491) | Updates fields in `syncStudentFields`. | **Keep** | Used to manually rebuild or repair snapshots from active enrollments. |
| [Classroom.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/Classroom.php#L264-L268) | Synced during `addStudent`. | **Keep** | Backward-compatibility update when direct model assignment is used. |
| [AcademicYearRolloverService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/AcademicYearRolloverService.php#L377-L381) | Snapshot captured before rollover. | **Keep** | Required to allow restoration of the previous state during undo. |
| [AcademicYearRolloverService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/AcademicYearRolloverService.php#L492-L496) | Snapshot restored during `undoRollover`. | **Keep** | Reverts compatibility fields to their pre-rollover state. |
| [EnrollmentRepairDirtyData.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Console/Commands/EnrollmentRepairDirtyData.php#L162-L165) | Re-syncs student drift to active classroom. | **Keep** | Keeps compatibility snapshots aligned with the source of truth. |

### Direct Reads (Database Queries & Resource Mapping)

| File / Location | Context / Logic | Classification | Rationale |
| :--- | :--- | :--- | :--- |
| [AcademicYearRolloverService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/AcademicYearRolloverService.php#L198-L200) | Queries pending intake (new students). | **Keep** | Used to identify imported students who have no `classroom_students` records yet. |
| [AcademyMemberController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php#L326-L381) | Fallback options lookup when classrooms are empty. | **Keep** | Necessary fallback for empty academies or newly initialized years. |
| [AcademyMemberController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php#L621-L633) | Filter query logic in member list. | **Defer** | Filters by these fields. Future migration should join `classroom_students` + `classrooms`. |
| [AcademyMemberController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php#L656-L659) | Sorting query logic by `class_level` / `class_section`. | **Defer** | Future sorting should join `classroom_students` + `classrooms`. |
| [StudentCardController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php#L38) | Distinct level list for student cards. | **Defer** | Card printing depends on these fields for filter bounds. |
| [StudentCardController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php#L61) | Student count group-by level. | **Defer** | Part of student card dashboard statistics. |
| [StudentProfileController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Profile/StudentProfileController.php) | Returns snapshot fields in resource mappings. | **Keep** | Displays class level and section in student profile endpoints. |
| `ui/` Front-end Components | Renders student card layouts, header text. | **Keep** | Components use these fields directly for text overlays (e.g., `ม.2/3`). |

---

## 3. Phase 9 Command Flow & Execution Results

During Phase 9, database repairs and academic info backfills were executed to resolve inconsistency and repair dirty data across all academies.

### Command Execution Sequence
The following sequence was executed on the production database:
1. **Dry-Run Repair:** `php artisan enrollment:repair-dirty-data --dry-run`
2. **Dry-Run Backfill:** `php artisan enrollment:backfill-academic-info --dry-run`
3. **Execution Backfill:** `php artisan enrollment:backfill-academic-info`
4. **Execution Repair:** `php artisan enrollment:repair-dirty-data`
5. **Verification Dry-Runs:** Verified that all command targets were resolved to `0`.

### Outcomes
* **Invariants Fixed:**
  * Duplicated active `classroom_students` rows were marked as `superseded` (retaining the latest active record).
  * Student snapshots (`students.class_level` and `students.class_section`) were re-synced with active enrollments, incorporating normalization (e.g., preserving `ม.` prefix rules).
  * Multiple current `student_academic_info` rows were resolved, leaving only the newest record marked as `is_current = true`.
* **Statistics:**
  * **1913** missing/null-academic-year `student_academic_info` records with an active enrollment were successfully backfilled and linked to their appropriate academic year.

---

## 4. Intentionally Untouched Orphan Legacy Data

Following the Phase 9 run, database inspection revealed a residual set of legacy rows:
* **Quantity:** `491` rows in the `student_academic_info` table.
* **State:** `academic_year IS NULL` and `is_current = true`.

### Rationale for Leaving Untouched
* **No Active Enrollment:** These 491 rows correspond to historical student records that do not have any corresponding active `classroom_students` records.
* **No Safe Inference:** Because there are no active enrollments or classrooms associated with these rows, it is impossible to safely infer which academic year they belong to.
* **Action:** The `enrollment:backfill-academic-info` command intentionally bypassed these rows to prevent corrupting historical records with guessed data. They remain untouched and isolated.

---

## 5. Operator Notes & Expected Post-Run Health Baseline

For future database migrations, runs, or checks, the database state is considered healthy if dry-runs of the repair and backfill commands produce the following baseline outputs:

### 1. Enrollment Repair Command
```bash
php artisan enrollment:repair-dirty-data --dry-run
```
* **Expected Health Baseline:** All counters must return `0`.
  ```text
  [dry-run] duplicate active classroom student rows fixed: 0
  [dry-run] student snapshot fields resynced to active classroom: 0
  [dry-run] duplicate current academic info rows demoted: 0
  [dry-run] manual review rows detected: 0
  ```

### 2. Enrollment Backfill Command
```bash
php artisan enrollment:backfill-academic-info --dry-run
```
* **Expected Health Baseline:** All eligible enrollment-backed rows are matched.
  * `processed`: `1929` (or current count of students)
  * `patched_null_year`: `0` (no active enrollment-backed records have null year)
  * `skipped_existing`: `1929` (all active enrollment-backed records are already mapped)
  ```text
  [dry-run] processed: 1929
  [dry-run] patched null year: 0
  [dry-run] skipped existing: 1929
  ```

### Residual Risks
* **Unmapped Legacy Records:** The 491 orphan `student_academic_info` records with `academic_year IS NULL` remain a minor normalization debt. If schema normalization constraints are added in the future (e.g. making `academic_year` `NOT NULL`), these 491 rows must be deleted or backfilled via a custom legacy seeder, as the active-enrollment backfill command cannot safely patch them.
* **Compatibility Snapshot Drift:** If direct updates to `students.class_level` or `class_section` are made without going through the enrollment service, snapshots will drift. Running `enrollment:repair-dirty-data` (without `--dry-run`) will automatically align them.

