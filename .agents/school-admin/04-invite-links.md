# 04 — ลิงก์เชิญสมาชิก (Invite Links)

> ไฟล์รองของเมนู **ลิงก์เชิญสมาชิก** ใน [OVERVIEW.md](OVERVIEW.md)
> จัดการลิงก์เชิญ / QR Code เพื่อให้ผู้ใช้เข้าร่วมโรงเรียน

---

## 1. Scope & Purpose

- **ใคร:** admin สร้าง/จัดการลิงก์เชิญ; ผู้ใช้ทั่วไปใช้ลิงก์เข้าร่วม
- **admin ทำอะไรได้:** สร้างลิงก์ (กำหนดชื่อ/คำอธิบาย/role/จำนวนใช้/อายุ/require_approval), ดูรายการ, เปิด/ปิด, ลบ, reset use count, copy URL, download QR
- **สิทธิ์หลัก:** `members.manage` (ตาม OVERVIEW) — แต่ปัจจุบัน **ไม่มี permission guard ทั้ง FE และ BE**

**ไม่รวม:** การเชิญรายบุคคล (invite member modal → เมนู #2)

---

## 2. Current State

### Frontend
- **Page:** [invite-links/index.vue](../../ui/pages/academies/%5Bname%5D/admin/invite-links/index.vue) — **471 บรรทัด**
  - ❌ **ไม่มี layout** — ไม่ได้ set `layout: 'academy-admin'` (`definePageMeta` หายไป)
  - ❌ **ไม่มี permission gate** — ไม่มี `can()` check, ใครก็เข้าได้
  - ❌ ใช้ `$api` (from `useNuxtApp()`) แทน `useApi()` composable ที่โปรเจคใช้
  - ❌ ใช้ `Icon name="mdi:*"` แทน `@iconify/vue` ที่โปรเจคใช้
  - ❌ ไม่มี dark mode support (hard-coded `text-gray-900`, `bg-white` ไม่มี `dark:` variants)
  - Features ที่มี: list view (QR + URL + stats), create modal (ชื่อ/คำอธิบาย/role/max_uses/expires_in_days/require_approval), toggle active, delete, copy URL, download QR

### Backend
- **Controller:** [InviteLinkController.php](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/InviteLinkController.php) — **388 บรรทัด**
  - 7 methods: index, store, update, destroy, validateCode, joinWithCode, toggleActive, resetUseCount
  - ❌ **ไม่มี permission guard** — ไม่มี `canManageMembers()` หรือ middleware ใดๆ ใน CRUD methods (index/store/update/destroy/toggleActive/resetUseCount)
  - ✅ `joinWithCode` มี logic ครบ: validate link, check email domain, check existing member, DB transaction, audit log
  - ✅ `validateCode` เป็น public (ไม่ต้อง auth) — ถูกต้อง
  - ✅ Audit log ใน `joinWithCode` — ACTION_JOIN / ACTION_ACCEPT_INVITE
  - ❌ ไม่มี audit log ใน CRUD operations (create/update/delete/toggle)

- **Model:** [AcademyInviteLink.php](../../api/nuxnanravel/app/Models/AcademyInviteLink.php) — **202 บรรทัด**
  - Auto-generate unique code on create (Str::random(12))
  - Scopes: active, notExpired, hasUsesRemaining, valid
  - Computed attributes: remaining_uses, status, invite_url, qr_code_url
  - `isValid()`, `isEmailAllowed()`, `incrementUseCount()`
  - ⚠️ QR code ใช้ external API `api.qrserver.com` — dependency on third party

### Routes (academy.php:326-334)
```
GET    /{academy}/invite-links              → index
POST   /{academy}/invite-links              → store
PATCH  /{academy}/invite-links/{link}       → update
DELETE /{academy}/invite-links/{link}       → destroy
POST   /{academy}/invite-links/{link}/toggle-active  → toggleActive
POST   /{academy}/invite-links/{link}/reset-count    → resetUseCount
```
Public (no auth):
```
GET    /invite/{code}      → validateCode
POST   /invite/{code}/join → joinWithCode (auth required)
```

---

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายการลิงก์ | ✅ | list + QR preview |
| 2 | สร้างลิงก์ใหม่ | ✅ | modal with options |
| 3 | กำหนด role ให้ลิงก์ | ✅ | dropdown from available roles |
| 4 | จำกัดจำนวนใช้ | ✅ | max_uses |
| 5 | ตั้งวันหมดอายุ | ✅ | expires_in_days / expires_at |
| 6 | Require approval | ✅ | checkbox → pending vs auto-approve |
| 7 | Domain restriction | ⚠️ | BE support ✅ แต่ FE ไม่มี input field |
| 8 | เปิด/ปิดลิงก์ | ✅ | toggleActive |
| 9 | ลบลิงก์ | ✅ | with Swal confirm |
| 10 | Copy URL | ✅ | clipboard |
| 11 | Download QR | ✅ | download via anchor |
| 12 | Reset use count | ⚠️ | BE endpoint มี แต่ FE ไม่มีปุ่ม |
| 13 | แก้ไขลิงก์ | ⚠️ | BE update() มี แต่ FE ไม่มี edit modal |
| 14 | Filter active/inactive | ⚠️ | BE support `?status=active` แต่ FE ไม่มี filter UI |
| 15 | Layout academy-admin | ❌ | ไม่มี definePageMeta |
| 16 | Permission gate (FE) | ❌ | ไม่มี can() check |
| 17 | Permission guard (BE) | ❌ | ไม่มี canManageMembers() ใน CRUD |
| 18 | Audit log (CRUD) | ❌ | สร้าง/แก้/ลบ/toggle ไม่มี log |
| 19 | Dark mode | ❌ | ไม่มี dark: classes |
| 20 | Link usage history | ❌ | ไม่มีว่าใครใช้ลิงก์ไหนเข้ามา |

---

## 4. Permission Matrix

| Permission | Owner | Admin | Teacher | Staff | Student | Guardian |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `members.manage` (CRUD invite links) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 5. Gap Analysis

- **L1** — **CRITICAL: ไม่มี permission guard** ทั้ง FE และ BE — สมาชิกทุกคนเข้าถึง CRUD invite links ได้
- **L2** — Layout ผิด — ไม่มี `definePageMeta({ layout: 'academy-admin' })`
- **L3** — ใช้ `$api` (NuxtApp plugin) แทน `useApi()` composable — ไม่ตรง convention
- **L4** — ใช้ `Icon name=` (Nuxt Icon module) แทน `@iconify/vue` — ไม่ตรง convention (อาจจะใช้ได้ถ้า Nuxt Icon config ถูก)
- **L5** — ไม่มี dark mode — hard-coded light colors
- **L6** — ไม่มี audit log สำหรับ admin CRUD (create/update/delete/toggle)
- **L7** — FE ขาด: edit modal, reset use count button, filter active/inactive, domain restriction input
- **L8** — ไม่มี link usage history (ใครใช้ลิงก์ไหนเข้ามาเมื่อไหร่)
- **L9** — QR code ใช้ external API (`api.qrserver.com`) — อาจไม่ stable / privacy concern

---

## 6. Implementation Tasks

| Step | Title | Depends | Deliverable | Priority |
|---|---|---|---|---|
| **L-S1** | Permission guard — BE `canManageMembers()` ทุก CRUD method + FE `can()` gate | — | controller fix + FE gate + tests | 🔴 critical |
| **L-S2** | Layout + convention fix — definePageMeta, useApi, dark mode | — | FE refactor | 🟡 |
| **L-S3** | Audit log — wire create/update/delete/toggle to MemberActivityLog | L-S1 | controller + tests | 🟡 |
| **L-S4** | Missing FE features — edit modal, reset count, filter, domain restriction | L-S2 | UI improvements | 🟢 |
| **L-S5** | Link usage history — who joined via which link | — | new endpoint + UI tab | 🟢 low |
| **L-S6** | QR code — self-hosted or inline generation (ลด dependency external) | — | model change | 🔵 defer |

---

## 7. Review Log

_(ว่างไว้)_
