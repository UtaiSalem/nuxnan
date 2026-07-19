# 03 — คำขอเข้าร่วม (Join Requests)

> ไฟล์รองของเมนู **คำขอเข้าร่วม** ใน [OVERVIEW.md](OVERVIEW.md)
> จัดการคำขอเข้าร่วมโรงเรียนจากผู้ใช้ (approve / reject / bulk approve)

---

## 1. Scope & Purpose

- **ใคร:** ผู้ใช้ที่กด "เข้าร่วมโรงเรียน" แล้วอยู่ในสถานะรออนุมัติ (`academy_members.status = 1`)
- **admin ทำอะไรได้บ้าง:** ดูรายการคำขอ, อนุมัติทีละคน, ปฏิเสธทีละคน, อนุมัติทั้งหมด (bulk)
- **สิทธิ์หลัก:** `members.manage` (ทั้งดูและจัดการ — การดูคำขอ = คนที่จัดการคำขอได้)

**ไม่รวมในเมนูนี้ (แยกไปเมนูอื่น):**
- คำเชิญที่ admin ส่งออก (invited, status 4) → #4 ลิงก์เชิญ / #2 สมาชิก (ประวัติการเชิญ)
- การจัดการสมาชิกที่อนุมัติแล้ว → #2

---

## 2. Current State (S1 done)

### Frontend
- **Page:** [requests.vue](../../ui/pages/academies/%5Bname%5D/admin/requests.vue)
  - `layout: 'academy-admin'` ✅ (S1 fix)
  - Permission gate: `await fetchMyRole()` → `navigateTo('.../admin')` เมื่อ `!can('members.manage')` ✅ (S1 fix)
  - Stats card (จำนวนคำขอรอดำเนินการ), empty state, list (avatar + ชื่อ + email + reference_code + วันที่)
  - Actions: อนุมัติ / ปฏิเสธ (ต่อรายการ, มี processing lock), อนุมัติทั้งหมด (bulk) ✅

### Backend
- **Controller:** [AcademyMemberController.php](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php)
  - `getPendingRequests` — guard `canManageMembers()` ✅ (S1 fix, เดิม owner-only), eager-load `user:id,name,email,profile_photo_path,reference_code`, order `latest('created_at')`
  - `acceptmember` / `rejectmember` — guard `canManageMembers()` + audit log (members-bundle)
  - `bulkAction` (`action=approve`) — guard `canManageMembers()` + audit BULK_ACTION
- **Routes:** [routes/learn/academy.php](../../api/nuxnanravel/routes/learn/academy.php)
  - GET `/{academy}/pending-requests` (:196)
  - POST `/{academy}/members/{member}/accept` (:207)
  - POST `/{academy}/members/{member}/reject` (:208)
  - POST `/{academy}/members/bulk-action` (:224)

### Endpoint Inventory

| Method | Route | Guard | Audit |
|---|---|---|---|
| getPendingRequests | GET pending-requests | `canManageMembers` | — (read) |
| acceptmember | POST members/{m}/accept | `canManageMembers` | APPROVE |
| rejectmember | POST members/{m}/reject | `canManageMembers` | REJECT |
| bulkAction(approve) | POST members/bulk-action | `canManageMembers` | BULK_ACTION |

---

## 3. S1 Changes Applied

1. **Backend guard** — `getPendingRequests` เปลี่ยนจาก `academy->user_id === auth()->id()` เป็น `canManageMembers()` เพื่อเปิดให้ admin / ผู้มี `members.manage` ดูได้
2. **FE layout + gate** — `requests.vue` → `academy-admin` layout + permission gate ก่อน render
3. **Bulk approve** — "อนุมัติทั้งหมด" เรียก `bulk-action` `approve` ครั้งเดียว (แทน loop N calls); ได้ audit entry เดียว
4. **Dead code** — ลบ `isOwner`/`isAdmin` ที่ไม่ได้ใช้จาก destructure

## 4. Tests

[AcademyJoinRequestGuardsTest.php](../../api/nuxnanravel/tests/Feature/Academy/AcademyJoinRequestGuardsTest.php) — 6 cases:
- owner ดู pending ได้
- member ที่มี `members.manage` (admin) ดูได้
- student (ไม่มีสิทธิ์) → 403
- คนนอก → 403
- owner bulk approve → status 2
- student bulk approve → 403 (status ยังคง 1)

## 5. Deferred / Future (S2+)

- Reject with reason (ปัจจุบัน reject ไม่บันทึกเหตุผล)
- Realtime badge จำนวนคำขอที่ค้างบนเมนู sidebar
- Consolidate route pending-requests ที่ซ้ำ (academy.php:145 และ :196)
- Pagination ถ้าคำขอมีจำนวนมาก (ปัจจุบัน `get()` ทั้งหมด)
