# 02 — สมาชิก (Members)

> ไฟล์รองของเมนู **สมาชิก** ใน [OVERVIEW.md](OVERVIEW.md)
> จัดการสมาชิกของโรงเรียนทั้งหมด (invite, approve, suspend, remove, bulk, import)

---

## 1. Scope & Purpose

- **ใคร:** ทุกคนที่มีความสัมพันธ์กับโรงเรียน (owner, admin, teacher, staff, student, guardian) ที่มี record ในตาราง `academy_members`
- **admin ทำอะไรได้บ้าง:** เชิญ, อนุมัติ/ปฏิเสธคำขอ, ระงับ/ปลด, ลบ, แก้ข้อมูล, กำหนดบทบาท (via #1), ติดแท็ก (via #5), ดูโปรไฟล์เชิงลึก, import CSV, bulk actions, export
- **สิทธิ์หลัก:** `members.view`, `members.manage`

**ไม่รวมในเมนูนี้ (แยกไปเมนูอื่น):**
- คำขอเข้าร่วม (join requests) → #3
- ลิงก์เชิญสมาชิก → #4
- แท็กสมาชิก → #5
- ผู้ปกครอง → #6
- บทบาท/สิทธิ์ → #1 (เสร็จแล้ว)

---

## 2. Current State

### Frontend
- **Page:** [members.vue](../../ui/pages/academies/%5Bname%5D/admin/members.vue) — **1,376 บรรทัด** (monolith ใหญ่)
  - `layout: 'main'` ⚠️ ควรเป็น `academy-admin` (G8-repeat)
  - Filter หลายชั้น: search, status, role, tag, class_level/section, classroom, gender, member_type
  - View modes: card/table (auto mobile)
  - Group by: none/classroom/class_level/gender
  - Actions: ผูกกับ `can('members.manage')`
- **Detail page:** [members/[memberId].vue](../../ui/pages/academies/%5Bname%5D/admin/members/%5BmemberId%5D.vue) — 465 บรรทัด
- **Components:** ต้อง audit `ui/components/academy/member/`

### Backend
- **Controller:** [AcademyMemberController.php](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php) — **1,649 บรรทัด · 30+ methods**
- **Model:** [AcademyMember.php](../../api/nuxnanravel/app/Models/AcademyMember.php) — 214 บรรทัด
- **Routes:** [routes/learn/academy.php:174–232](../../api/nuxnanravel/routes/learn/academy.php)

### Endpoint Inventory (from route:list)

**Query:**
- `GET /{academy}/members` → `getAcademyMembers` (main list)
- `GET /{academy}/members/search` → `searchMembers`
- `GET /{academy}/members/stats` → `getMemberStats`
- `GET /{academy}/members/filter-options` → `getFilterOptions`
- `GET /{academy}/members/{member}/profile` → `getMemberProfile`
- `GET /{academy}/members/{member}/courses` → `getMemberCourses`
- `GET /{academy}/members/{member}/activity` → `getMemberActivity`

**Mutations (single):**
- `POST /{academy}/members` → `storemember` ⚠️ camelCase drift
- `POST /{academy}/unmembers` → `unmember` ⚠️ verb-noun drift
- `POST /{academy}/members/{member}/accept` → `acceptmember`
- `POST /{academy}/members/{member}/reject` → `rejectmember`
- `DELETE /{academy}/members/{member}` → `removeMember`
- `POST /{academy}/members/{member}/suspend` → `suspendMember`
- `POST /{academy}/members/{member}/unsuspend` → `unsuspendMember`
- `PATCH /{academy}/members/{member}` → `updateMember`
- `PATCH /{academy}/members/{member}/identity` → `updateIdentity`

**Bulk / Import / Export:**
- `POST /{academy}/members/invite` → `bulkInviteMembers`
- `POST /{academy}/members/import-csv` → `importMembersFromCsv`
- `POST /{academy}/members/bulk-action` → `bulkAction`
- `POST /{academy}/members/export-selected` → `exportSelectedMembers`
- `GET  /{academy}/members/export-csv` → `exportMembersToCsv`

**Invitation flow (user-facing):**
- `POST /{academy}/invite` → `inviteMember` (single invite)
- `POST /{academy}/invitations/accept` → `acceptInvitation`
- `POST /{academy}/invitations/decline` → `declineInvitation`
- `GET  /my/invitations` → `getMyInvitations`
- `GET  /{academy}/pending-requests` → `getPendingRequests`

---

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายชื่อสมาชิก + filter/search | ✅ | รวย filter มาก |
| 2 | แสดงสถิติ (จำนวนสมาชิก แยกตามสถานะ/role) | ✅ | `getMemberStats` |
| 3 | ดูโปรไฟล์เชิงลึก (courses + activity) | ✅ | detail page + tabs |
| 4 | อนุมัติ/ปฏิเสธคำขอเข้าร่วม | ✅ | รวมกับเมนู #3 |
| 5 | ระงับ/ปลดระงับ | ✅ | + reason |
| 6 | ลบสมาชิก | ✅ | |
| 7 | แก้ไขข้อมูล (member profile) | ✅ | updateMember + updateIdentity |
| 8 | เปลี่ยนบทบาท (assign role) | ⚠️ | endpoint อยู่ที่ #1 controller ไม่ใช่ที่นี่ — verify FE เรียกถูก |
| 9 | Invite ทีละคน | ✅ | inviteMember |
| 10 | Bulk invite | ✅ | bulkInviteMembers |
| 11 | Import CSV | ✅ | importMembersFromCsv |
| 12 | Bulk action (ระงับ/ลบ หลายคน) | ✅ | bulkAction |
| 13 | Export (all + selected) | ✅ | CSV |
| 14 | ติดแท็ก | ⚠️ | endpoint อยู่ที่ #5 — verify integration |
| 15 | Audit log ทุกการเปลี่ยนแปลง | ⚠️ | MemberActivityLog มี แต่ต้อง verify ว่า wire ครบ (ที่ #1 เพิ่งเพิ่มสำหรับ role — members อาจยังขาด) |
| 16 | Layout: `academy-admin` | ❌ | ยังใช้ `main` |
| 17 | Self-lockout guard (admin ลบตัวเอง) | ❌ | น่าจะไม่มีเหมือน #1 |
| 18 | Guard: ห้ามลบ owner | ⚠️ | ต้อง verify |
| 19 | Bulk role change | ⚠️ | มี `POST /members/bulk-role` (จาก S1 audit) — verify FE |
| 20 | Filter ตาม role/tag ผสม | ✅ | มี |
| 21 | Merge duplicate members | ❌ | ยังไม่มี |
| 22 | View invitation history | ⚠️ | มี `getMyInvitations` (personal) ยังไม่มี admin view |
| 23 | Print member list | ❌ | ยังไม่มี |
| 24 | Search by ID card / phone / student number | ⚠️ | searchMembers รับ query แต่ต้อง verify field coverage |
| 25 | Filter: date joined range | ❌ | ยังไม่มี |

---

## 4. Permission Matrix (ตามคีย์ที่ทำใน S1 แล้ว)

| Permission | Owner | Admin | Dept Admin (future) | Teacher | Staff | Student | Guardian |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `members.view` | ✅ | ✅ | ✅ (in scope) | ✅ read-only | ✅ read-only | ❌ | ❌ |
| `members.manage` | ✅ | ✅ | ✅ (in scope) | ❌ | ❌ | ❌ | ❌ |
| `members.invite` | ✅ | ✅ | ✅ (in scope) | ❌ | ❌ | ❌ | ❌ |
| `members.roles.manage` | ✅ | ✅ | ✅ (in scope) | ❌ | ❌ | ❌ | ❌ |

---

## 5. Gap Analysis

- **M1** — Endpoint naming drift (Laravel convention breach): `storemember`, `unmember`, `acceptmember`, `rejectmember`, `memberstatus`, `memberlist`, `membercount` — ควรเป็น `store`, `unstore`, `accept`, `reject`, `status`, `list`, `count` (แต่ rename ก็จะกระทบ routes/frontend หลายที่ — ต้อง refactor ระวัง)
- **M2** — Layout `members.vue` = `main` ไม่ใช่ `academy-admin` (repeat G8)
- **M3** — Controller 1,649 บรรทัด/30+ methods → refactor เป็น multiple controllers (`AcademyMemberQueryController`, `MembershipController`, `MemberInvitationController`, `MemberBulkController`) — หรืออย่างน้อย move bulk/export ออก
- **M4** — Frontend `members.vue` 1,376 บรรทัด → refactor เป็น component composition
- **M5** — Missing audit log ในบาง action (invite, bulk-action, import CSV) — ควรมีให้ครบ
- **M6** — Self-lockout / owner protection guards — ยังไม่ verify
- **M7** — Bulk role change (S1 มี route แล้ว) แต่ FE อาจไม่ได้ใช้
- **M8** — Merge duplicate members — ไม่มี (จำเป็นตอน import ซ้ำ)
- **M9** — Missing filter: date joined range, member type, verified flag
- **M10** — searchMembers ต้อง audit ว่า search field ครอบคลุมพอ (ID card, phone, student number)
- **M11** — ไม่มี admin view สำหรับ invitation history (ใครเชิญใคร เมื่อไหร่ ตอบยัง)
- **M12** — Print / PDF export ยังไม่มี

---

## 6. Implementation Tasks

**ลำดับที่แนะนำ (จากเล็ก/ปลอดภัย → ใหญ่):**

| Step | Title | Depends | Deliverable | Priority |
|---|---|---|---|---|
| **M-S1** | Audit — เจาะดู `members.vue` เรียกใช้ endpoint ครบและถูก + ตรวจ guard ครบ + verify audit log wire | — | audit report | 🔴 first |
| M-S2 | Layout: `members.vue` → `academy-admin` | — | 1-line fix | 🟢 quick |
| M-S3 | Wire audit log ให้ครบ (invite/bulk/import/suspend/remove/update) | M-S1 | + tests | 🟡 |
| M-S4 | Owner protection + self-lockout guards | M-S1 | + tests | 🟠 safety |
| M-S5 | Bulk role change UI (เชื่อม endpoint ที่มีอยู่) | M-S1 | UI + integration | 🟡 |
| M-S6 | Invitation history — admin view | M-S1 | new endpoint + UI | 🟢 |
| M-S7 | Missing filters (date joined range, member type, verified) | — | UI + BE | 🟢 |
| M-S8 | Print / PDF export | — | new endpoint + UI | 🟢 low |
| M-S9 | Merge duplicate members | — | complex — separate design | 🔵 defer |
| M-S10 | Rename endpoints (M1) + refactor controller (M3) + component split (M4) | — | breaking — separate release | 🔵 defer |

---

## 7. Codex Prompt Template — M-S1 (Audit)

จะเขียนตอนพร้อมส่ง

---

## 8. Review Log

_(ว่างไว้)_
