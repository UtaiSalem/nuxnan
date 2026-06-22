# Enrollment Repair Report

Date: 2026-06-21
Scope: Phase 9.3 execution review for enrollment backfill and repair

## Commands Run

```powershell
php artisan enrollment:repair-dirty-data --dry-run
php artisan enrollment:backfill-academic-info --dry-run
php artisan enrollment:backfill-academic-info
php artisan enrollment:repair-dirty-data
php artisan enrollment:repair-dirty-data --dry-run
php artisan enrollment:backfill-academic-info --dry-run
```

## Key Results

### Before real run

- `enrollment:repair-dirty-data --dry-run`
  - `duplicate_active_fixed`: 0
  - `student_snapshots_resynced`: 0 after normalization fix
  - `duplicate_current_demoted`: 0
  - `manual_review_rows`: 1913 before backfill execution

- `enrollment:backfill-academic-info --dry-run`
  - `processed`: 1929
  - `created`: 0
  - `patched_null_year`: 1913
  - `enriched_existing`: 0
  - `skipped_existing`: 16

### Real run

- `enrollment:backfill-academic-info`
  - completed successfully
  - practical effect matched the dry-run projection for enrollment-backed rows

- `enrollment:repair-dirty-data`
  - `duplicate_active_fixed`: 0
  - `student_snapshots_resynced`: 0
  - `duplicate_current_demoted`: 0
  - `manual_review_rows`: 0 in repair-command scope

### After real run

- `enrollment:repair-dirty-data --dry-run`
  - all counters remained 0

- `enrollment:backfill-academic-info --dry-run`
  - `processed`: 1929
  - `created`: 0
  - `patched_null_year`: 0
  - `enriched_existing`: 0
  - `skipped_existing`: 1929

## Residual Legacy Rows

Post-run inspection still found:

- `student_academic_info.academic_year IS NULL`: 491 rows
- `student_academic_info.academic_year IS NULL AND is_current = true`: 491 rows

Interpretation:

- these rows are not tied to current `classroom_students` active enrollments
- the Phase 9.2 command intentionally skipped them because there is no safe enrollment-backed source of truth to infer from
- they appear to be legacy/orphan academic-info records, not active enrollment history rows

## Notes

- Phase 9.1 was adjusted to normalize `students.class_level` using the same logic as `StudentEnrollmentService`, preventing a large snapshot regression from values like `ม.6` back to non-normalized storage.
- Enrollment-backed repair/backfill is now clean.
- Remaining 491 null-year `student_academic_info` rows need a separate legacy cleanup strategy if the team wants that table to be fully normalized beyond enrollment-backed data.
