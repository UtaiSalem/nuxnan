# Task for Codex
**Assigned by:** Claude Orchestrator  
**Date:** 2026-06-03  
**Status:** DONE

---

## ข้อตกลงการรายงาน
- `PENDING` → กำลังรอ | `DONE` → เสร็จแล้ว | `BLOCKED: <เหตุผล>` → ติดปัญหา

ห้าม commit หรือ push โดยไม่ได้รับอนุญาตจาก Orchestrator  
ห้ามแก้ไขไฟล์นอกที่ระบุไว้ใน task

---

## TASK — เพิ่มเมนู Admin Dashboard ใน Navbar Dropdown
**Status:** DONE  
**File:** `ui/layouts/main.vue`

### เป้าหมาย
เพิ่มลิงค์ไปยัง `/nuxnan-admin` ในเมนู dropdown ของ navbar  
**แสดงเฉพาะ `authUser.is_super_admin === true` เท่านั้น**

---

### ตำแหน่งที่ต้องแก้ไข

ค้นหา block นี้ใน `main.vue` (อยู่ในส่วน dropdown, บรรทัด ~642–651):

```html
<NuxtLink
  v-if="authUser.is_plearnd_admin"
  to="/nuxnan-admin/supports"
  @click="closeSettings"
  class="flex items-center gap-3 px-4 py-3 transition-colors"
  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
>
  <Icon icon="mdi:hand-heart" class="w-5 h-5 text-green-500" />
  <span>จัดการการสนับสนุน</span>
</NuxtLink>
```

**แทรกโค้ดต่อไปนี้ต่อท้าย block ด้านบน (ก่อน `<div class="border-t my-1"...>` ที่เป็น divider ก่อน logout):**

```html
<!-- Super Admin only — Nuxnan Admin Dashboard -->
<NuxtLink
  v-if="authUser.is_super_admin"
  to="/nuxnan-admin"
  @click="closeSettings"
  class="flex items-center gap-3 px-4 py-3 transition-colors"
  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-indigo-400' : 'hover:bg-indigo-50 text-indigo-600'"
>
  <Icon icon="fluent:shield-person-24-regular" class="w-5 h-5 text-indigo-500" />
  <span>Nuxnan Admin</span>
</NuxtLink>
```

---

### หมายเหตุสำคัญ
- ใช้ `authUser.is_super_admin` ไม่ใช่ `authUser.is_plearnd_admin`
- วางหลัง "จัดการการสนับสนุน" และก่อน divider + ปุ่ม logout
- สี indigo แยกให้เห็นว่าเป็น super admin level
- ตรวจ tag balance ก่อน save

---

## เมื่อทำเสร็จ
อัพเดท Status เป็น `DONE` แล้วบันทึกไฟล์นี้ทิ้งไว้
