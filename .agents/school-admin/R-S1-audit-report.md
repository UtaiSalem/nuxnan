# R-S1 Academy Join Requests FE↔BE Audit

Date: 2026-07-19. Scope: `admin/requests.vue`, `routes/learn/academy.php`, and `AcademyMemberController.php` (`getPendingRequests`, `acceptmember`, `rejectmember`, `bulkAction`). Audit + fix pass (menu #3).

## Executive summary

The join-requests page was functional but had three gaps: (1) `getPendingRequests` gated on `academy->user_id === auth()->id()` only, so academy admins and members holding `members.manage` were blocked with 403; (2) `requests.vue` used `layout: 'main'` and did not enforce any FE permission gate before rendering; (3) "อนุมัติทั้งหมด" looped one `accept` request per pending member (N calls) instead of using the existing bulk endpoint. The accept/reject mutators already gained permission checks and audit logging in the members-bundle work, so no audit-log gap remains for this menu. All three gaps are now fixed and covered by tests.

## Table A — FE→BE endpoint map (after fix)

| FE file:line | HTTP verb | URL pattern | Matches BE route? | Controller method | Notes |
|---|---:|---|---|---|---|
| requests.vue (onMounted) | GET | `/api/academies/{name}` | Yes, academy route | AcademyController | Bootstrap to resolve academy id. |
| requests.vue (onMounted) | GET | `/api/academies/{id}/my-role` | Yes, academy.php:239 | AcademyRoleController::myRole | Drives FE permission gate. |
| requests.vue (fetchPendingRequests) | GET | `/api/academies/{id}/pending-requests` | Yes, academy.php:196 | getPendingRequests | Now `canManageMembers`-gated. |
| requests.vue (acceptRequest) | POST | `/api/academies/{id}/members/{member}/accept` | Yes, academy.php:207 | acceptmember | Single approve. |
| requests.vue (rejectRequest) | POST | `/api/academies/{id}/members/{member}/reject` | Yes, academy.php:208 | rejectmember | Single reject. |
| requests.vue (acceptAll) | POST | `/api/academies/{id}/members/bulk-action` | Yes, academy.php:224 | bulkAction | `action=approve`; replaces per-item loop. |

## Table B — Authorization/guard coverage (after fix)

| Method | Line | Manual permission | Result |
|---|---:|---|---|
| getPendingRequests | 577 | `canManageMembers()` (was owner-only) | Owner + admins + `members.manage` can view; others 403. |
| acceptmember | 113 | `canManageMembers()` | Gated (members-bundle). |
| rejectmember | 129 | `canManageMembers()` | Gated (members-bundle). |
| bulkAction | 1477 | `canManageMembers()` | Gated; per-target owner/self skip. |

FE gate: `requests.vue` awaits `fetchMyRole()` then `navigateTo('.../admin')` when `!can('members.manage')`.

## Table C — Audit-log coverage

`acceptmember`, `rejectmember`, and `bulkAction` already emit `MemberActivityLog` entries (APPROVE, REJECT, BULK_ACTION) from the members-bundle change. Bulk approve via `bulkAction` produces one BULK_ACTION entry with action + target ids. No new audit-log work required for menu #3.

## Table D — Performance

Before: `acceptAll` issued one POST per pending member. After: a single `bulk-action` POST processes all ids server-side, which also yields a single audit entry instead of N single-approve entries.

## Section E — Changes applied

- `AcademyMemberController::getPendingRequests` — replace owner-only check with `canManageMembers()`, order results `latest('created_at')`.
- `requests.vue` — `layout: 'academy-admin'`; await `fetchMyRole()` + `can('members.manage')` gate; `acceptAll` calls `bulk-action` `approve` and re-fetches; drop unused `isOwner`/`isAdmin`.
- `tests/Feature/Academy/AcademyJoinRequestGuardsTest.php` — owner/admin view, student + outsider 403, owner bulk approve, student bulk approve 403.

## Section F — Deferred / notes

- No new endpoint was added; the existing `bulk-action` already validates `action in approve,reject,suspend,unsuspend,remove`.
- `getPendingRequests` has two route registrations (academy.php:145 and :196); FE uses the `/api/academies` prefix (:196). Consolidation is out of scope for this menu.
