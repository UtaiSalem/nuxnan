# 05 — แท็กสมาชิก (Member Tags)

> ไฟล์รองของเมนู **แท็กสมาชิก** ใน [OVERVIEW.md](OVERVIEW.md)
> สร้าง/จัดการแท็กเพื่อจัดกลุ่มสมาชิก (CRUD tags + assign/remove from members)

---

## 1. Scope & Purpose

- **ใคร:** admin สร้าง/แก้/ลบแท็ก และ assign แท็กให้สมาชิก
- **admin ทำอะไรได้:** สร้างแท็ก (ชื่อ/สี/คำอธิบาย), แก้ไข, ลบ, ดูสมาชิกในแท็ก, bulk add tag, assign/remove tag per member
- **สิทธิ์หลัก:** `members.manage`

---

## 2. Current State

### Frontend
- **Page:** [member-tags/index.vue](../../ui/pages/academies/%5Bname%5D/admin/member-tags/index.vue) — **284 บรรทัด**
  - ❌ `layout: 'main'` — ควรเป็น `academy-admin`
  - ✅ Permission gate: `can('members.manage')` via computed `canManage` → ซ่อน create/edit/delete buttons
  - ⚠️ แต่ไม่มี redirect ถ้าไม่มีสิทธิ์ — ยังเข้าหน้าได้ เห็น read-only list
  - ❌ ใช้ `$api` (from `useNuxtApp()`) แทน `useApi()`
  - ✅ ใช้ `@iconify/vue` Icon (ถูก convention)
  - ✅ Dark mode — มี `dark:` variants
  - ✅ Component: `MemberTagFormModal` สำหรับ create/edit
  - Features: tag grid (name/color/description/member_count), create/edit modal, delete with confirm, link to tag members

### Backend
- **Controller:** [MemberTagController.php](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/MemberTagController.php) — **344 บรรทัด · 9 methods**
  - ❌ **ไม่มี permission guard เลย** — ไม่มี `canManageMembers()` หรือ middleware ใดๆ
  - Methods: index, store, update, destroy, getTagMembers, addTagsToMember, removeTagsFromMember, bulkAddTag, getMemberTags, getAvailableColors
  - ✅ Academy scope check ใน update/destroy/getTagMembers/addTagsToMember/removeTagsFromMember/getMemberTags
  - ❌ ไม่มี audit log ใดๆ

### Routes (academy.php:340-354)
```
GET    member-tags/colors                       → getAvailableColors (no academy scope)
GET    {academy}/member-tags                     → index
POST   {academy}/member-tags                     → store
PATCH  {academy}/member-tags/{tag}               → update
DELETE {academy}/member-tags/{tag}               → destroy
GET    {academy}/member-tags/{tag}/members        → getTagMembers
POST   {academy}/member-tags/bulk-add             → bulkAddTag
GET    {academy}/members/{member}/tags            → getMemberTags
POST   {academy}/members/{member}/tags            → addTagsToMember
DELETE {academy}/members/{member}/tags            → removeTagsFromMember
```

---

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายการแท็ก | ✅ | grid + member_count |
| 2 | สร้างแท็ก | ✅ | modal (name/color/description) |
| 3 | แก้ไขแท็ก | ✅ | edit modal |
| 4 | ลบแท็ก | ✅ | detach all members + delete |
| 5 | ดูสมาชิกในแท็ก | ✅ | getTagMembers + paginated |
| 6 | Assign แท็กให้สมาชิก | ✅ | addTagsToMember |
| 7 | Remove แท็กจากสมาชิก | ✅ | removeTagsFromMember |
| 8 | Bulk add tag | ✅ | bulkAddTag |
| 9 | Available colors | ✅ | getAvailableColors |
| 10 | Layout academy-admin | ❌ | ใช้ `main` |
| 11 | Permission guard (BE) | ❌ | ไม่มี canManageMembers() |
| 12 | Permission redirect (FE) | ⚠️ | ซ่อนปุ่มแต่ไม่ redirect |
| 13 | Audit log | ❌ | ไม่มี |
| 14 | Reorder tags (drag & drop) | ❌ | BE มี sort_order แต่ไม่มี reorder endpoint/UI |
| 15 | Tag members page (FE) | ❌ | มี link `/member-tags/${id}/members` แต่ไม่มี page file |

---

## 4. Permission Matrix

| Permission | Owner | Admin | Teacher | Staff | Student | Guardian |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| `members.manage` (CRUD tags) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `members.view` (read tag list) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |

---

## 5. Gap Analysis

- **T1** — **CRITICAL: ไม่มี permission guard** ทั้ง BE — สมาชิกทุกคนสร้าง/ลบ tag ได้
- **T2** — Layout ผิด — `layout: 'main'` ควรเป็น `academy-admin`
- **T3** — ใช้ `$api` แทน `useApi()` — ไม่ตรง convention
- **T4** — FE ไม่ redirect ถ้าไม่มีสิทธิ์ — แค่ซ่อนปุ่ม
- **T5** — ไม่มี audit log ใน CRUD
- **T6** — หน้า tag members (`/member-tags/${id}/members`) ไม่มี — link ชี้ไปหน้าที่ไม่มีอยู่
- **T7** — ไม่มี reorder UI (drag & drop) แม้ BE มี `sort_order`

---

## 6. Implementation Tasks

| Step | Title | Depends | Deliverable | Priority |
|---|---|---|---|---|
| **T-S1** | Permission guard — BE `canManageMembers()` ทุก mutating method + FE redirect | — | controller fix + FE gate + tests | 🔴 critical |
| **T-S2** | Layout + convention fix — definePageMeta, useApi | — | FE refactor | 🟡 |
| **T-S3** | Audit log — wire create/update/delete/bulkAdd/assign/remove to MemberActivityLog | T-S1 | controller + tests | 🟡 |
| **T-S4** | Tag members page — สร้างหน้า `/member-tags/[tagId]/members.vue` | T-S2 | new page | 🟢 |
| **T-S5** | Reorder tags (drag & drop) | — | new endpoint + FE | 🔵 defer |

---

## 7. Review Log

_(ว่างไว้)_
