# M-S1 Academy Members FE↔BE Audit

Date: 2026-07-19. Scope: `members.vue`, `members/[memberId].vue`, member child components, `routes/learn/academy.php`, and `AcademyMemberController.php`. Read-only audit; no source files were changed.

## Executive summary

31 FE member-flow API call sites were found. All current primary page/detail endpoints match routes; legacy child components use the same contracts with `$axios` paths that omit `/api` in the literal (base-prefix hypothesis). Two mutators (`acceptmember`, `rejectmember`) have neither `academy.permission` middleware nor a manual permission check. AcademyMemberController contains no `MemberActivityLog::logActivity` call, so all mutating methods listed below lack controller-level audit logging. `selectedClassroomKey` has no matching backend filter.

## Table A — FE→BE endpoint map

| FE file:line | HTTP verb | URL pattern | Matches BE route? | Controller method | Notes |
|---|---:|---|---|---|---|
| members.vue:282; members/[memberId].vue:49 | GET | `/api/academies/{name}` | Yes, academy route | AcademyController | Bootstrap, not AcademyMemberController. |
| members.vue:350 | GET | `/api/academies/{id}/members/search?...` | Yes, academy.php:201 | searchMembers (583) | Main list. |
| members.vue:374 | GET | `/api/academies/{id}/roles/available` | Yes, academy.php:237 | AcademyRoleController::available | Role options. |
| members.vue:387 | GET | `/api/academies/{id}/members/stats` | Yes, academy.php:202 | getMemberStats (931) | Stats. |
| members.vue:400 | GET | `/api/academies/{id}/member-tags` | Yes, academy.php:341 | MemberTagController::index | Tags. |
| members.vue:415 | GET | `/api/academies/{id}/members/filter-options` | Yes, academy.php:203 | getFilterOptions (179) | Filter choices. |
| members.vue:470,493 | POST | `/api/academies/{id}/members/{member}/accept|reject` | Yes, academy.php:206–207 | acceptmember (108); rejectmember (122) | Non-conventional names. |
| members.vue:516 | DELETE | `/api/academies/{id}/members/{member}` | Yes, academy.php:208 | removeMember (729) | Direct remove. |
| members.vue:617,649,684,717 | POST | `/api/academies/{id}/members/bulk-action` | Yes, academy.php:223 | bulkAction (1390) | Four call sites. |
| members.vue:738 | POST | `/api/academies/{id}/members/export-selected` | Yes, academy.php:224 | exportSelectedMembers (1520) | Selected CSV. |
| members.vue:770 | GET | `/api/academies/{id}/members/export` | Yes, academy.php:228 | exportMembersToCsv (1598) | `window.open`. |
| members/[memberId].vue:72,93,109 | GET | `/api/academies/{id}/members/{member}/profile|courses|activity` | Yes, academy.php:215–217 | getMemberProfile (990); getMemberCourses (1082); getMemberActivity (1125) | Detail page. |
| academy/InviteMemberModal.vue:57 | GET | `/api/users/search?...` | Yes, users route | User search | Invitation dependency. |
| academy/InviteMemberModal.vue:125 | POST | `/api/academies/{id}/members/invite` | Yes, academy.php:220 | bulkInviteMembers (1166) | Admin invite. |
| member/BulkRoleModal.vue:57,100 | GET/POST | `/api/academies/{id}/roles/available`; `/members/bulk-role` | Yes, academy.php:237,245 | AcademyRoleController::available; bulkAssignRole | Child modal. |
| member/MemberImportModal.vue:75 | POST | `/academies/{id}/members/import` | Yes if `$axios` base is `/api`; academy.php:227 | importMembersFromCsv (1261) | Prefix omitted in literal; hypothesis. |
| member/MemberList.vue:92,113,125 | GET | `/academies/{id}/members/search|stats`; `/roles/available` | Yes if `$axios` base is `/api`; academy.php:201,202,237 | searchMembers; getMemberStats; AcademyRoleController::available | Legacy alternate list. |
| member/MemberManageModal.vue:113,151,186,220 | PATCH/POST/POST/DELETE | `/academies/{id}/members/{member}` and `/suspend|unsuspend` | Yes if `$axios` base is `/api`; academy.php:208–211 | updateMember (895); suspendMember (773); unsuspendMember (818); removeMember (729) | Legacy alternate modal. |
| member/MemberRoleModal.vue:62,78 | GET/POST | `/academies/{id}/roles/available`; `/members/{member}/role` | Yes if `$axios` base is `/api`; academy.php:237,244 | available; AcademyRoleController::assignRole | Legacy alternate modal. |
| member/StudentCardModal.vue:47,57 | GET | Multiline `/api/...` literals | Unresolved from literal extraction | See file | Hypothesis; expand URL manually before implementation. |

No `useFetch()` calls were found. `members.vue` renders child components at 1224–1364; AdvancedFilterModal and BulkActionBar have no direct API call.

## Table B — BE endpoints unused by FE

| Route | Method | Controller method | Evidence |
|---|---:|---|---|
| academy.php:138 | POST | storemember (42) | No scoped caller. |
| academy.php:139 | POST | unmember (81) | No scoped caller. |
| academy.php:142/193 | POST | inviteMember (441) | FE uses `/members/invite` → bulkInviteMembers. |
| academy.php:143/194 | POST | acceptInvitation (486) | No caller. |
| academy.php:144/195 | POST | declineInvitation (516) | No caller. |
| academy.php:145/196 | GET | getPendingRequests (559) | No caller. |
| academy.php:174 | GET | getAcademyMembers (419) | FE uses enhanced search. |
| academy.php:212 | PATCH | updateIdentity (859) | No caller. |
| academy.php:339–353 | mixed | MemberTagController CRUD/member-tag operations | Only tag index is called at members.vue:400. |

`memberstatus` (136), `memberlist` (146), and `membercount` (165) have no route in academy.php and no scoped caller. `updateMember` is covered only by legacy MemberManageModal.

## Table C — Authorization/guard coverage

Enhanced routes are under `auth:api` at academy.php:157; none has `academy.permission` middleware. Manual `canManageMembers()` calls are at controller lines 732,775,820,897,1264,1393,1523,1601.

| Method | Line | Middleware? | Manual permission? | Owner protection? | Self-lockout? | Notes |
|---|---:|---|---|---|---|---|
| storemember | 42 | No | No | N/A | N/A | Self-service join; first route-group auth is hypothesis. |
| unmember | 81 | No | No | No | Yes, auth user query 83–85 | Self leave. |
| acceptmember | 108 | No | No | No | No | Permission gap. |
| rejectmember | 122 | No | No | No | No | Permission gap. |
| inviteMember | 441 | No | Owner check 444–446 | N/A | N/A | Owner-only. |
| acceptInvitation | 486 | No | Self lookup | N/A | Yes | Self action. |
| declineInvitation | 516 | No | Self lookup | N/A | Yes | Self action. |
| removeMember | 729 | No | Yes, 732 | Yes, 747–752 | No explicit check | Non-owner self-remove not separately blocked. |
| suspendMember | 773 | No | Yes, 775 | Yes, 790–795 | No | Self-suspend not blocked. |
| unsuspendMember | 818 | No | Yes, 820 | No explicit branch | No | Scope check exists. |
| updateIdentity | 859 | No | Self record or manager, 863–866 | No | No | Manager can update owner record. |
| updateMember | 895 | No | Yes, 897 | No demotion check | No | Owner demotion guard not visible. |
| bulkInviteMembers | 1166 | No | Yes, 1169–1173 | N/A | N/A | One check before loop. |
| importMembersFromCsv | 1261 | No | Yes, 1264 | N/A | N/A | One check before loop. |
| bulkAction | 1390 | No | Yes, 1393 | Yes, per target 1415–1433 | No | Rechecks academy and owner per target. |
| exportSelectedMembers | 1520 | No | Yes, 1523 | N/A | N/A | Included per requested list. |
| exportMembersToCsv | 1598 | No | Yes, 1601 | N/A | N/A | Included per requested list. |

Flagged methods with neither middleware nor manual permission: `acceptmember`, `rejectmember`.

## Table D — Audit-log coverage

Whole-file search found no `MemberActivityLog` import or `MemberActivityLog::logActivity` call.

| Method(s) | Logs? | Expected constant | Missing? |
|---|---|---|---|
| storemember, unmember | No | JOIN, LEAVE | Yes |
| acceptmember, rejectmember | No | APPROVE, REJECT | Yes |
| inviteMember, acceptInvitation, declineInvitation | No | INVITE, ACCEPT_INVITE, DECLINE_INVITE | Yes |
| removeMember | No | REMOVE | Yes |
| suspendMember, unsuspendMember | No | SUSPEND, UNSUSPEND | Yes |
| updateIdentity, updateMember | No | PROFILE_UPDATE, ROLE_CHANGE as applicable | Yes |
| bulkInviteMembers, importMembersFromCsv | No | INVITE, BULK_ACTION | Yes |
| bulkAction | No | BULK_ACTION plus action metadata | Yes |
| exportSelectedMembers, exportMembersToCsv | No | No listed export constant | Policy decision; currently unlogged. |

Constants are in `app/Models/MemberActivityLog.php`.

## Table E — Naming drift

| Current method | Current URL | Canonical suggestion |
|---|---|---|
| storemember | POST `/{academy}/members` (138) | store or joinMember |
| unmember | POST `/{academy}/unmembers` (139) | destroy/leaveMember; REST DELETE self-membership |
| acceptmember | POST `/{academy}/members/{member}/accept` (206) | acceptMember |
| rejectmember | POST `/{academy}/members/{member}/reject` (207) | rejectMember |
| memberstatus | No route found | status |
| memberlist | No route found | index |
| membercount | No route found | count/stats |

No rename was applied.

## Table F — Search/filter field coverage

`searchMembers` searches user name/email/reference code, student Thai/English names/student ID, and member code (583–608); filters status (611–614), role/roles (616–624), academy_role_id (626–629), tag_id (631–635), class_level/section (638–671), gender (673–677), member_type (680–687), and date range (689–695). Sorting/pagination is 697–712. `getFilterOptions` starts at 179 and derives classroom levels/sections with fallback options.

| FE field | Backend parameter | Result |
|---|---|---|
| searchQuery | search | Covered |
| selectedStatus | status | Covered |
| selectedRole | role or academy_role_id | Covered if serialized to matching parameter; exact mapping is hypothesis (333–350) |
| selectedTag | tag_id | Covered |
| selectedClassLevel | class_level | Covered |
| selectedClassSection | class_section | Covered |
| selectedClassroomKey | None found | Gap; likely client-only |
| selectedGender | gender | Covered |
| selectedMemberType | member_type | Covered |

The nine FE fields are declared at members.vue:31–56 and request assembly is 329–350.

## Section G — Guard specifics

- `removeMember`: YES owner protection at 747–752; NO independent target != `Auth::id` check.
- `suspendMember`: YES owner protection at 790–795; NO self-suspend block.
- `updateMember`: NO explicit owner demotion/target check in 895–921; relies on `canManageMembers` at 897.
- `updateIdentity`: NO owner-demotion check; own record or manager allowed at 863–866; update at 883.
- `bulkAction`: YES per-target academy scope and owner check at 1415–1433; NO self-target check; direct switch at 1435–1491 does not inherit single-target safeguards.

## Section H — Recommendations

- Add middleware or manual permission checks to acceptmember/rejectmember.
- Define and enforce self-remove/self-suspend and owner demotion policy in single and bulk paths.
- Add MemberActivityLog entries with matching constants and target/actor metadata.
- Add canonical aliases/deprecation before caller migration; preserve URLs.
- Normalize `$axios` prefixes and retire/confirm legacy duplicate components.
- Align selectedRole serialization and implement a server classroom_key filter or remove that state.
- Choose one invitation contract and document the other as legacy.
- Centralize bulk authorization, transitions, owner/self rules, and logging.
