# Academic Year Rollover — Preflight Inventory
Generated: 2026-06-21 07:29:51

## 1. MySQL Version
- Version: `8.4.7`
- Decision (Phase 1.2): **functional index** supported (8.0+)

## 2. Scope
- Academies: 1
- Academic years: 1
  - id=1 academy=1 name=2568 2025-05-16 -> 2026-03-31 **CURRENT**

## 3. Classrooms by academic_year
- academic_year_id=1: 51 classrooms

## 4. Active enrollment per academy
- academy_id=1: 1929 active enrollments

## 5. Data integrity: duplicate active enrollments
- Students with >1 active row: **0** (must be 0 before applying unique constraint)

## 6. Sync drift: students.class_level vs active classroom
- `students.class_level` sample values: `1, 2, 3, 4, 5, 6`
- `classrooms.grade_level` sample values: `ม.1, ม.2, ม.3, ม.4, ม.5, ม.6`
- Rows where exact-match fails (raw diff): **1929**
- Rows where normalized comparison fails (real drift): **0**
  - Format mismatch finding: `students.class_level` stores numeric (`"1"`, `"2"`), `classrooms.grade_level` stores prefixed (`"ม.1"`)
  - **Implication for Phase 2**: `enrollStudent()` should normalize before sync, or change `students.class_level` to store full label.
- Students with non-empty `class_level` but **no active enrollment**: **476**

## 7. students.status distribution
- `active`: 2896

## 8. classroom_students.status distribution
- `active`: 1929

## 9. student_academic_info integrity
- Total rows: 2420
- Students with >1 `is_current=true` row: **0** (must be 0 before partial unique)
- Duplicate (student_id, academic_year) pairs: **0** (must be 0 before unique)

## 10. classroom_students with NULL academic_year_id
- Total NULL: 0 (of which active: 0)
- These must be repaired before rollover queries can scope by year reliably

## 11. Schema readiness check
- classroom_students.rollover_batch_id: MISSING
- classroom_students.created_by_user_id: MISSING
- classroom_students.academic_year_id: EXISTS
- student_academic_info.classroom_id: EXISTS
- student_academic_info.is_current: EXISTS
- student_academic_info.academic_year: EXISTS

---
## Decisions confirmed
- MySQL 8.x → use **functional unique index** in Phase 1.2
- students.status canonical values present: see §7
- classroom_students.status existing values: see §8
- Data dirtiness count for Phase 9a repair scope: see §5, §6, §9, §10
