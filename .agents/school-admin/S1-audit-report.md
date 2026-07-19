# S1 Permission-Key Audit

Full report is retained in the audit workspace.

Audit date: 2026-07-19. Read-only investigation; no source files, tests, migrations, or generated files were changed.

## Counts

| set | distinct |
|---|---:|
| `AcademyPermission::PERMISSIONS` | 60 |
| frontend literal usage | 67 |
| backend literal usage | 27 |
| non-wildcard `SYSTEM_ROLES` keys | 56 |

## Table A — Canonical permission set

Groups and keys: `academy`: `academy.view`, `academy.view.public`, `academy.settings.view`, `academy.settings.edit`; `members`: `members.view`, `members.manage`, `members.invite`, `members.roles.manage`; `courses`: `courses.view`, `courses.view.enrolled`, `courses.create`, `courses.edit`, `courses.edit.own`, `courses.delete`; `students`: `students.view`, `students.manage`, `students.create`, `students.import`, `students.lifecycle`, `students.activate_account`, `students.export`, `students.delete`, `students.cards.request`, `students.cards.produce`; `teachers`: `teachers.view`, `teachers.manage`; `attendance`: `attendance.view`, `attendance.manage`; `gradebook`: `gradebook.view`, `gradebook.manage`; `grades`: `grades.view.own`, `grades.view.all`; `assignments`: `assignments.view.own`, `assignments.submit`, `assignments.manage`; `schedule`: `schedule.view.own`, `schedule.view.all`, `schedule.manage`; `finance`: `finance.view`, `finance.manage`, `finance.reports`; `payments`: `payments.view`, `payments.pay`; `reports`: `reports.view`, `reports.export`, `reports.manage`; `announcements`: `announcements.view`, `announcements.create`, `announcements.create.own`, `announcements.manage`; `home_visits`: `home_visits.view`, `home_visits.create`, `home_visits.manage`; `messages`: `messages.view`, `messages.send`, `messages.teacher`; `children`: `children.view`, `children.grades.view`, `children.attendance.view`, `children.schedule.view`.

SYSTEM_ROLES grants: owner=`*`; director/admin include academy settings, member management, course administration, student administration, teacher administration, finance/reporting, announcements, home visits, plus `behavior.view`, `behavior.record`, `behavior.approve`, `behavior.manage`; teacher includes academy/member/course-own, student-card request, attendance/gradebook, announcements-own, home-visits, `behavior.view`, `behavior.record`; registrar includes student intake/lifecycle/export and `behavior.view`; staff/card_admin/finance_staff have the narrower defaults visible in `AcademyRole.php`; student includes enrolled-course/assignment/own-grade/own-schedule and `behavior.view.own`; parent includes children/payments/messages and `children.behavior.view`; guest=`academy.view.public`. Six role keys absent from PERMISSIONS: the five behavior keys and `children.behavior.view`.

## Table B — Frontend usage and exact locations

| key | locations (count) | canonical? |
|---|---|---|
| `roles.view` | `ui/layouts/academy-admin.vue:113`; `ui/pages/academies/[name]/admin.vue:76`; `ui/pages/academies/[name]/admin/roles.vue:118` (3) | no |
| `roles.manage` | `ui/pages/academies/[name]/admin/roles.vue:307` (1) | no |
| `members.roles.manage` | `ui/composables/useAcademyRole.ts:175`; `ui/pages/academies/[name]/dashboard/admin.vue:175` (2) | yes |
| `groups.view` | `ui/layouts/academy-admin.vue:154,160`; `ui/pages/academies/[name]/admin.vue:121,127` (4) | no |
| `schedule.view` | `ui/layouts/academy-admin.vue:166`; `ui/pages/academies/[name]/admin.vue:133` (2) | no |
| `grades.view` | `ui/layouts/academy-admin.vue:195`; `ui/pages/academies/[name]/admin.vue:168`; `ui/pages/academies/[name]/dashboard/parent.vue:124` (3) | no |
| `staff.view` | `ui/layouts/academy-admin.vue:212`; `ui/pages/academies/[name]/admin.vue:191` (2) | no |
| `courses.manage` | `ui/pages/academies/[name]/admin.vue:105,109` (2) | no |
| `events.manage` | `ui/pages/academies/[name]/admin/events/index.vue:55` (1) | no |
| `manage_members` | `ui/pages/academies/[name]/admin/member-tags/index.vue:173` (1) | no |
| `school_attendance.manage` | `ui/pages/academies/[name]/admin/school-attendance/index.vue:49` (1) | no |
| `payments.make` | `ui/pages/academies/[name]/dashboard/parent.vue:138` (1) | no |
| `settings.manage` | `ui/layouts/academy-admin.vue:263,269`; `ui/pages/academies/[name]/admin.vue:219,247`; `ui/pages/academies/[name]/admin/settings.vue:69` (5) | no |
| other canonical FE keys | exact literal locations were enumerated by scan; principal clusters: `academy.view` (layouts:68,89; admin.vue:121,127,133,162,168,191,208,219; schedule.vue:78), `members.manage` (composable:175; layout:107,119,125; admin.vue:64,70,76,82,88; members.vue:844,860,1227,1228,1244,1245,1364; dashboard/admin.vue:140), `members.view` (layout:101,131; admin.vue:64,94; admin/index.vue:192; members.vue:288; member detail:55; dashboard/admin.vue:390), `students.view` (layout:177,183; admin.vue:144,150,180; cards index/detail/print:38,76,44; dashboard/admin.vue:408), and `home_visits.view/manage` (layout/admin/home-visits/dashboard locations). | yes |

The remaining canonical FE occurrences are the direct literals shown by the same scan in `useAcademyRole.ts:168,182,189,196`, `useAcademyNavigation.ts:193`, admin/dashboard pages, and `students.cards.*`, `announcements.*`, `academy.settings.*`, `finance.*`, `reports.*`, `courses.*`, `attendance.view`, `teachers.view`, `children.view`, `messages.send`, `home_visits.create`; no additional noncanonical keys were found beyond the rows above.

## Table C — Backend usage

| key | exact locations (count) | canonical? |
|---|---|---|
| `members.manage` | `app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php:981` (1) | yes |
| `members.roles.manage` | `app/Http/Controllers/Api/Learn/Academy/AcademyRoleController.php:425` (1) | yes |
| `settings.manage` | `app/Http/Controllers/Api/Learn/Academy/AcademyController.php:487` (1) | no |
| `enrollment.undo` | `RolloverController.php:174`; `UndoRolloverRequest.php:12` (2) | no |
| `enrollment.lifecycle` | `StudentLifecycleController.php:146`; Drop/Graduate/Promote/Repeat/Transfer requests:12/12/15/13/15 (6) | no |
| `student.import` | `StudentImportController.php:158`; `UploadStudentImportRequest.php:16` (2) | no |
| `student.intake` | `CheckStudentDuplicateRequest.php:15`; `StoreStudentIntakeRequest.php:18` (2) | no |
| `enrollment.commit/plan/preview` | Commit request:12 + rollover resource:17; Plan request:14; Preview request:13 (4) | no |
| `manage` | `RemediationController.php:43,122` (2) | no |
| `donate` | four AcademyDonate/CourseDonate request files at `:12` (4) | no |
| middleware canonical keys | `academy-home-visit.php:54,58,170,175,177,179`; student-card routes `:7,15,27,49`; `academy.php:260,262,264,272,274,992-999` | yes |

Additional Gate key: `enrollment.viewBatches` at `RolloverController.php:74,91`. Course API checks (`edit_grades`, `view_reports`, `export_reports`, `remove_members`, `edit_member_details`, `add_bonus_points`) are outside academy-level reconciliation.

## Table D — Orphans

FE-only: `roles.view`, `roles.manage`, `groups.view`, `schedule.view`, `grades.view`, `staff.view`, `courses.manage`, `events.manage`, `manage_members`, `school_attendance.manage`, `payments.make`.

BE-only: `enrollment.undo`, `student.import`, `enrollment.lifecycle`, `manage`, `student.intake`, `enrollment.commit`, `enrollment.plan`, `enrollment.preview`, `donate`, `enrollment.viewBatches`.

Both: `settings.manage` (used in FE and BE, absent from both sources of truth).

## Table E — Unused canonical keys

`academy.view.public`, `members.invite`, `courses.view.enrolled`, `courses.edit.own`, `courses.delete`, `students.create`, `students.import`, `students.lifecycle`, `students.delete`, `teachers.manage`, `gradebook.view`, `gradebook.manage`, `grades.view.own`, `grades.view.all`, `assignments.view.own`, `assignments.submit`, `assignments.manage`, `schedule.view.own`, `schedule.view.all`, `schedule.manage`, `payments.view`, `payments.pay`, `reports.manage`, `messages.view`, `messages.send`, `messages.teacher`, `children.grades.view`, `children.attendance.view`, `children.schedule.view` (29). “Unused” means no literal match in requested patterns.

## Table F — Route wiring gaps

| method | current route | status | proposed route |
|---|---|---|---|
| permissions() | `GET /permissions/all`, `api.academy.permissions.all`, `academy.php:248` | wired | — |
| available() | `GET /{academy}/roles/available`, `api.academy.roles.available`, `academy.php:237` | wired | — |
| myRole() | `GET /{academy}/my-role`, `api.academy.roles.my`, `academy.php:238` | wired | — |
| assignRole() | `POST /{academy}/members/{member}/role`, `api.academy.members.assignRole`, `academy.php:244` | wired | — |
| bulkAssignRole() | `POST /{academy}/members/bulk-role`, `api.academy.members.bulkAssignRole`, `academy.php:245` | wired | — |

## Table G — Mismatches

`roles.view`/`roles.manage` are FE-only while BE checks `members.roles.manage` (`AcademyRoleController.php:425`). FE uses keys backend never checks: `groups.view`, `schedule.view`, `grades.view`, `staff.view`, `courses.manage`, `events.manage`, `manage_members`, `school_attendance.manage`, `payments.make`. `settings.manage` is checked by BE but no system role grants it. Role defaults contain six keys absent from `PERMISSIONS` listed above. Spelling drift includes `payments.make`/`payments.pay`, `student.import`/`students.import`, and unscoped `schedule.view`/`grades.view` versus scoped canonical names.

## Table H — Recommendations

| issue | recommendation |
|---|---|
| missing route | No change: all five audited methods are wired; retain named routes. |
| orphan | Add genuine academy keys to `PERMISSIONS` and intended roles, or rename callers; document separate Gate/course namespaces. |
| mismatch | Decide one role-management contract, then align FE/BE; explicitly resolve payment/import/schedule/grade spellings. |
| drift | Add a contract audit for canonical keys, role defaults, FE groups, and middleware; prefer the permissions endpoint over duplicated FE lists. |
