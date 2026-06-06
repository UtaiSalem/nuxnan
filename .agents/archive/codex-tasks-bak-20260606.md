# Codex Task List — School Department Management Feature

**Feature Goal**: ระบบบริหารจัดการแผนกในโรงเรียน
- ผู้บริหารสร้าง/จัดการแผนก
- แต่งตั้งผู้ใช้เป็น admin/หัวหน้าของแต่ละแผนก
- ดูสมาชิก, บทบาท, สิทธิ์ของแผนก

**โครงสร้างที่มีอยู่แล้ว** (อย่าสร้างซ้ำ):
- Backend: `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php`
- Routes: `api/nuxnanravel/routes/learn/academy.php` (line 272–292)
- Frontend page: `ui/pages/academies/[name]/admin/departments.vue`
- Frontend overview: `ui/pages/academies/[name]/admin/school-management.vue`

---

## TASK-001 — Fix: DepartmentController::getStatistics() missing `departments_with_head`

```yaml
id: TASK-001
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-06-03 06:33
completion_notes: getStatistics now returns departments_with_head while preserving existing statistic fields.
```

**ปัญหา**: หน้า `departments.vue` แสดง `statistics.departments_with_head` แต่ `getStatistics()` ไม่ได้ return field นี้

**ไฟล์ที่ต้องแก้**:
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php`
- method: `getStatistics()` (line ~432)

**Acceptance Criteria**:
- [ ] `getStatistics()` return `departments_with_head` = จำนวน departments ที่มี `settings.head_user_id` ไม่เป็น null
- [ ] ยังคง return fields เดิมทั้งหมด (`total_departments`, `total_members`, `average_members_per_department`, `departments`)

**ตัวอย่าง response ที่ต้องการ**:
```json
{
  "success": true,
  "data": {
    "total_departments": 5,
    "total_members": 42,
    "average_members_per_department": 8.4,
    "departments_with_head": 3,
    "departments": [...]
  }
}
```

---

## TASK-002 — Fix: DepartmentController::index() ไม่ส่ง head_user data

```yaml
id: TASK-002
assigned_to: codex
status: done
priority: high
type: backend
completed_at: 2026-06-03 06:33
completion_notes: Department index now resolves head_user_id values in bulk and includes head_user objects or null.
```

**ปัญหา**: หน้า departments.vue แสดง `department.head_user` แต่ `index()` ไม่ส่ง head_user object มาด้วย

**ไฟล์ที่ต้องแก้**:
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php`
- method: `index()` (line ~49)

**Acceptance Criteria**:
- [ ] แต่ละ department object ใน response มี `head_user` field
- [ ] `head_user` = `{ id, name, email, profile_photo_url }` หรือ `null` ถ้าไม่มี
- [ ] หา head_user_id จาก `department->settings['head_user_id']` แล้ว query User model
- [ ] ถ้าไม่มี head_user_id หรือ user ไม่มีในระบบ ให้ส่ง `head_user: null`
- [ ] ประสิทธิภาพ: ใช้ eager loading หรือ map แทน N+1 query

---

## TASK-003 — Fix: Frontend API URL paths ใน departments.vue

```yaml
id: TASK-003
assigned_to: codex
status: done
priority: critical
type: frontend
depends_on: []
completed_at: 2026-06-03 06:33
completion_notes: Fixed department page API URLs, added missing /api prefixes, and sent member remove/role payloads in request bodies.
```

**ปัญหา**: หลาย API calls ใน `departments.vue` ใช้ URL ผิด — บางจุดขาด `/api/` prefix, บางจุดมี route pattern ผิด

**ไฟล์ที่ต้องแก้**:
- `ui/pages/academies/[name]/admin/departments.vue`

**Bug list** (แก้ทั้งหมด):

| function | URL ที่ผิด | URL ที่ถูกต้อง |
|---|---|---|
| `updateDepartment` | `/api/academies/departments/${id}` | `/api/academies/${academyId}/departments/${id}` |
| `deleteDepartment` | `/api/academies/departments/${id}` | `/api/academies/${academyId}/departments/${id}` |
| `fetchDepartmentPermissions` | `/academies/${academyId}/departments/${id}/permissions` | `/api/academies/${academyId}/departments/${id}/permissions` |
| `saveDepartmentPermissions` | `/academies/${academyId}/departments/${id}/permissions` | `/api/academies/${academyId}/departments/${id}/permissions` |
| `fetchDepartmentMembers` | `/academies/${academyId}/departments/${id}/members` | `/api/academies/${academyId}/departments/${id}/members` |
| `fetchAvailableMembers` | `/academies/${academyId}/members` | `/api/academies/${academyId}/members` |
| `addMembersToDepartment` | `/academies/${academyId}/departments/${id}/members/bulk` | `/api/academies/${academyId}/departments/${id}/members/bulk` |
| `removeMember` | `/academies/${academyId}/departments/${id}/members/${memberId}` | ดู note ด้านล่าง |
| `updateMemberRole` | `/academies/${academyId}/departments/${id}/members/${memberId}/role` | ดู note ด้านล่าง |

**Note สำหรับ removeMember**: Backend route คือ `DELETE /api/academies/departments/{department}/members` รับ `user_id` ใน request body
ให้แก้ให้เรียก `/api/academies/${academyId}/departments/${selectedDepartment.value.id}/members` พร้อม body `{ user_id: memberId }`

**Note สำหรับ updateMemberRole**: Backend route คือ `PATCH /api/academies/departments/{department}/members/role` รับ `user_id` และ `role` ใน body
ให้แก้ให้เรียก `/api/academies/${academyId}/departments/${selectedDepartment.value.id}/members/role` พร้อม body `{ user_id: memberId, role: newRole }`

**Acceptance Criteria**:
- [ ] แก้ URL ทั้งหมดตาม table ด้านบน
- [ ] `removeMember` ส่ง `user_id` ใน body แทนการใส่ใน URL
- [ ] `updateMemberRole` ส่ง `user_id` ใน body

---

## TASK-004 — Fix: Frontend response shape mismatch ใน departments.vue

```yaml
id: TASK-004
assigned_to: codex
status: done
priority: critical
type: frontend
depends_on: [TASK-001, TASK-002]
completed_at: 2026-06-03 06:33
completion_notes: fetchDepartments and fetchStatistics now read Laravel-wrapped response.data shape and pagination total from data.
```

**ปัญหา**: หลาย function อ่าน response shape ผิด เพราะ Laravel controller wrap data ไว้ใน `data` key

**ไฟล์ที่ต้องแก้**:
- `ui/pages/academies/[name]/admin/departments.vue`

**Bug list**:

| function | อ่านผิด | อ่านถูก |
|---|---|---|
| `fetchDepartments` | `response.departments` | `response.data.departments` |
| `fetchStatistics` | `response.statistics` | `response.data` |

**Acceptance Criteria**:
- [ ] `fetchDepartments` อ่าน `response.data.departments` และ set `departments.value`
- [ ] `fetchStatistics` อ่าน `response.data` และ set `statistics.value` (ได้ `total_departments`, `total_members`, `departments_with_head`, `average_members_per_department`)
- [ ] จำนวน pagination ถ้ามี ให้อ่านจาก `response.data.total` แทน `response.pagination`

---

## TASK-005 — Fix: `api.put` → `api.patch` สำหรับ update department

```yaml
id: TASK-005
assigned_to: codex
status: done
priority: medium
type: frontend
depends_on: [TASK-003]
completed_at: 2026-06-03 06:33
completion_notes: updateDepartment now uses api.patch against the academy-scoped department update endpoint.
```

**ปัญหา**: `updateDepartment()` ใช้ `api.put()` แต่ backend route กำหนดเป็น `PATCH`

**ไฟล์ที่ต้องแก้**:
- `ui/pages/academies/[name]/admin/departments.vue`
- function `updateDepartment` (ดู line ที่เรียก `api.put`)

**Acceptance Criteria**:
- [ ] เปลี่ยนจาก `api.put(...)` → `api.patch(...)` ในฟังก์ชัน `updateDepartment`

---

## TASK-006 — Verify: ตรวจสอบ useApi composable รองรับ patch method

```yaml
id: TASK-006
assigned_to: codex
status: done
priority: medium
type: frontend
depends_on: [TASK-005]
completed_at: 2026-06-03 06:33
completion_notes: Verified useApi already provides patch(), so no useApi code change was needed.
```

**ปัญหา**: ต้องตรวจสอบว่า `useApi` composable มี method `patch()` หรือไม่

**ไฟล์ที่ต้องตรวจ**:
- `ui/composables/useApi.ts`

**Acceptance Criteria**:
- [ ] อ่าน `useApi.ts` แล้วตรวจสอบว่ามี `patch` method
- [ ] ถ้าไม่มี: เพิ่ม `patch` method ที่ส่ง HTTP PATCH request (เหมือนกับ `put` แต่ใช้ method PATCH)
- [ ] ถ้ามีแล้ว: mark task นี้ done โดยไม่ต้องแก้อะไร

---

## สถานะรวม (Claude จะ update section นี้)

| Task | Status | Verified |
|------|--------|---------|
| TASK-001 | done | Codex |
| TASK-002 | done | Codex |
| TASK-003 | done | Codex |
| TASK-004 | done | Codex |
| TASK-005 | done | Codex |
| TASK-006 | done | Codex |

**Last updated**: 2026-06-03
**Updated by**: Codex (implementation)
