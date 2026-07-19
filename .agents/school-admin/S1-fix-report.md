# S1 Fix Report

Date: 2026-07-19
Codex task: `task-mrrmfrrq-z4819i` (7m 30s)
Follows: [S1-audit-report.md](S1-audit-report.md) + [01-roles-permissions.md §9](01-roles-permissions.md) (D1–D7 approved)

---

## 1. Files Changed (relevant to S1)

| File | Change |
|---|---|
| [AcademyPermission.php](../../api/nuxnanravel/app/Models/AcademyPermission.php) | +36 lines · 7 new groups + 6 new keys in existing groups |
| [AcademyRole.php](../../api/nuxnanravel/app/Models/AcademyRole.php) | +13 lines · grants added to director/admin/teacher/registrar/staff/finance_staff |
| [member-tags/index.vue:173](../../ui/pages/academies/%5Bname%5D/admin/member-tags/index.vue) | `manage_members` → `members.manage` |
| [dashboard/parent.vue:138](../../ui/pages/academies/%5Bname%5D/dashboard/parent.vue) | `payments.make` → `payments.pay` |

**Pint auto-reformatting** (unrelated cosmetic) — 10+ files got import-order sort + one-liner expansion:
- PublicAcademyController.php + resources
- StudentCardRequestController.php, RiskEventController.php
- routes/earn/advert.php, donate.php, routes/public/courses.php
- AcademyScopeFilteringTest.php, StudentCardByStudentTest.php, StudentCardSSOTTest.php
- ⚠️ These are **not** scope creep — Pint auto-formats every touched project file.

---

## 2. New Canonical Permission Keys (21 total)

**New groups (7):**
- `roles` — `roles.view`, `roles.manage`
- `groups` — `groups.view`, `groups.manage`
- `staff` — `staff.view`, `staff.manage`
- `settings` — `settings.view`, `settings.manage`
- `behavior` — `behavior.view`, `behavior.record`, `behavior.approve`, `behavior.manage`, `behavior.view.own`
- `events` — `events.view`, `events.manage`
- `school_attendance` — `school_attendance.view`, `school_attendance.manage`

**Added to existing groups:**
- `courses.manage` (courses)
- `grades.view`, `grades.manage` (grades)
- `schedule.view` (schedule)
- `children.behavior.view` (children)

---

## 3. System-Role Grants Added

| Role | Keys granted (delta) |
|---|---|
| **director** | `roles.view/manage`, `groups.view/manage`, `schedule.view`, `grades.view/manage`, `staff.view/manage`, `settings.view/manage`, `events.view/manage`, `school_attendance.view/manage`, `courses.manage` |
| **admin** | same as director |
| **teacher** | `grades.view`, `schedule.view`, `groups.view`, `staff.view`, `events.view`, `school_attendance.view` (read-only helpers) |
| **registrar** | `groups.view`, `schedule.view`, `staff.view`, `events.view` |
| **staff** | `schedule.view`, `events.view` |
| **finance_staff** | `events.view`, `settings.view` |
| owner / card_admin / student / parent / guest | no change |

⚠️ **behavior.* keys** already existed in role grants before — S1-fix just added them to `PERMISSIONS` canonical set to remove the orphan status.

---

## 4. Verification

| Check | Result | Notes |
|---|---|---|
| Laravel Pint | ✅ pass | ran by codex |
| Permission tests (`--filter=Permission`) | ✅ 7 pass | |
| Role tests (`--filter=Role`) | ⚠️ 3 pass / 1 pre-existing fail | `RoleAssignmentTest::test_new_user_is_assigned_student_role` fails with 404 on `/api/auth/register` — verified fail also on HEAD before S1-fix; unrelated to permission-key changes (targets app-level `roles` table, not `academy_roles`) |
| npm run build | ✅ pass (exit 0) | Total bundle 29.5 MB (5.97 MB gzip) · Claude re-ran with 10-min timeout after codex sandbox timed out at 120s |

---

## 5. Deviations from Plan
- **None.** All D1–D7 fixes applied as approved.
- Pint auto-format of unrelated files is expected behavior, not scope creep.

---

## 6. Follow-ups
- **Pre-existing 404 fail** in `RoleAssignmentTest::test_new_user_is_assigned_student_role` — investigate under a separate task (not part of admin panel scope; likely auth registration route)
- Steps S2–S8 remain for menu #1 (see [OVERVIEW.md](OVERVIEW.md))
