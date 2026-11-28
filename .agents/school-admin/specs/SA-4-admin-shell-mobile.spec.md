# สเปค SA-4 — frontend: เปลือกแอดมินใหม่ mobile-first + component กลาง

> แผนแม่: `C:\wamp64\www\nuxnan\.agents\plans\school-admin-improvement.md` (ข้อ 2.6 · เฟส 2 งาน 2c/2d · ข้อ 6 แถว SA-4)
> ต่อจาก SA-3 (`useAcademyAdminContext` + `provideAcademyAdminContext` ใน `admin.vue` มีแล้ว) — ห้ามแตะส่วนนั้น
> ต้นแบบดีไซน์: sidebar ของ HopeUI Pro Tailwind (`hopa/.../html/dashboard/index.html` `<aside class="sidebar">` + `scss/layouts/menu_style/_default_sidebar.scss`)
> แปลงแล้วด้านล่าง — **ห้ามเปิด/ก๊อปจาก `hopa/` เอง ห้าม import CSS/JS ของ HopeUI**
> **ไม่อยู่ใน shard นี้:** ย้ายหน้าลูกมาใช้ component กลาง (= SA-5) · ป้ายตัวเลขจริง (= SA-9) · จัดกลุ่มเมนูใหม่ (= SA-6)

## ข้อห้ามประจำ
- ห้าม `git commit` · `git push` · `php artisan migrate` · `npm run build` · แตะ `.env` · ห้ามแตะ `api/` · ห้ามแตะ `ui/utils/academyAdminNav.ts`
- ห้ามแตะไฟล์นอกรายการ · template ต้องมี root element เดียว · `<Icon` ต้อง `import { Icon } from '@iconify/vue'`
- รายงานกลับ: `git diff --stat` + ผลรันเกณฑ์แบบดิบ

```
กติกา UI บังคับ (mobile-first — ห้ามละเมิด):
- ออกแบบจากจอ 375px ขึ้นไป: class ที่ไม่มี prefix = สไตล์มือถือ แล้วค่อยเพิ่ม sm: md: lg:
  ห้ามเขียน desktop-first เช่น `flex-row md:flex-col` — ต้องเป็น `flex-col md:flex-row`
- ห้ามใช้ `hidden` ซ่อนข้อมูลสำคัญบนมือถือ ให้จัดวางใหม่แทน (ซ้อนเป็นแถว/ย้ายลงล่าง)
- ปุ่ม/ลิงก์ที่กดได้ต้อง touch target >= 44px บนมือถือ (`min-h-[44px]` หรือ `p-3`) แล้วค่อยลดที่ sm:
- แถว flex: ฝั่งที่ห้ามถูกบีบใส่ `flex-shrink-0 whitespace-nowrap`,
  ฝั่งข้อความใส่ `min-w-0 flex-1 break-words` (ภาษาไทยไม่มีช่องว่าง จะแตกแนวตั้งถ้าโดนบีบ)
- ตาราง/โค้ด/ไดอะแกรมกว้าง ต้องอยู่ในกล่อง `overflow-x-auto` ของตัวเอง — ทั้งหน้าห้ามเลื่อนแนวนอน
- padding/font ไล่เล็กไปใหญ่: `p-3 sm:p-6`, `text-sm sm:text-base`
- `<script setup lang="ts">` เป็นค่าเริ่มต้น · เรียก API ผ่าน `useApi` เท่านั้น ห้าม `$fetch`/`axios` ตรง ๆ
- ห้าม import อะไรจาก `@inertiajs/*` ในโค้ดใหม่
```

## บริบท (วิเคราะห์จบแล้ว — ห้ามออกแบบใหม่)
ปัญหาของเปลือกปัจจุบัน (`ui/pages/academies/[name]/admin.vue`):
- มือถือ: กด "แสดงเมนู" แล้วเมนูเป็นกริดสูง 70vh **ในหน้า** ดันเนื้อหาลงล่าง · ไม่มีช่องค้นหา (เมนู 35 รายการ) · ไม่มี breadcrumb
- หัวการ์ดเขียน "Admin Panel" (อังกฤษ) · ชื่อบทบาท `hidden sm:inline` = ซ่อนบนมือถือ
- layout หลักมี header `fixed top-0 h-16 z-50` และ BottomNav `fixed bottom-0 z-40 lg:hidden` (`components/layout/BottomNav.vue`) · โมดัลทั้งเรพใช้ `fixed inset-0 z-50`

## ไฟล์ที่แก้ได้
1. `C:\wamp64\www\nuxnan\ui\pages\academies\[name]\admin.vue` — **เฉพาะ template + state ของเมนู/drawer** (script ส่วน registry · SA-3 context · onMounted ประตู ห้ามแตะตรรกะ)
2. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\components\academy\admin\AcademyAdminNav.vue`
3. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\components\academy\admin\AcademyAdminPageHeader.vue`
4. สร้างใหม่: `C:\wamp64\www\nuxnan\ui\components\academy\admin\AcademyAdminState.vue`

## การแก้

### 1) `AcademyAdminNav.vue` (ใหม่) — รายการเมนู + ค้นหา + กลุ่มพับได้ (ใช้ทั้งใน drawer มือถือและ sidebar desktop)
props:
```ts
const props = defineProps<{
  groups: Array<AdminGroup & { sections: AdminSection[] }>   // จาก visibleAdminGroups()
  currentId: string | null
  sectionTo: (path: string) => string
  badges?: Record<string, number>                            // SA-9 จะเติม · ตอนนี้ว่าง
}>()
```
(import type จาก `~/utils/academyAdminNav`)
- **ช่องค้นหา** ด้านบน: `<input type="search">` placeholder "ค้นหาเมนู…" · `min-h-[44px] sm:min-h-0` · ไอคอน `fluent:search-24-regular` ซ้าย · ปุ่มล้าง (×) เมื่อมีคำ
  กรอง section ที่ `label` หรือ `group.label` มีคำค้น (ไม่สนตัวพิมพ์ · `trim()`) · กลุ่มที่ไม่เหลือ section ไม่แสดง ·
  ไม่เจออะไร → ข้อความ "ไม่พบเมนูที่ค้นหา" กลางกล่อง
- **หัวกลุ่ม** (สไตล์ HopeUI `static-item`): ปุ่มเต็มแถว `min-h-[44px] sm:min-h-0` · ข้อความ `text-[13px] font-normal uppercase tracking-[.18rem] text-gray-900 dark:text-gray-100`
  · chevron `fluent:chevron-down-20-regular` หมุน `-rotate-90` เมื่อพับ · `aria-expanded`
- **พับ/กาง:** state `collapsed = ref<Set<string>>` จำใน `localStorage` key `academy-admin-nav-collapsed` (อ่าน/เขียนใน `try/catch` · อ่านใน `onMounted`)
  กลุ่มที่มี `currentId` **กางเสมอ** · ระหว่างค้นหา **ทุกกลุ่มกาง**
- **รายการ** (สไตล์ HopeUI `nav-link` แบบ rounded): `NuxtLink` `flex min-h-[44px] items-center gap-3 rounded-lg px-4 py-2.5 lg:min-h-0`
  · active: `bg-primary-500 text-white shadow-[0_10px_20px_-10px_rgba(14,165,233,0.6)]` + `aria-current="page"`
  · ปกติ: `text-gray-600 dark:text-gray-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400`
  · ไอคอน `w-5 h-5 flex-shrink-0` · ชื่อ `min-w-0 flex-1 break-words text-sm leading-tight`
  · ป้าย: ถ้า `badges?.[section.id] > 0` แสดง `flex-shrink-0 whitespace-nowrap rounded-full bg-red-500 px-2 py-0.5 text-xs text-white` (เกิน 99 → `99+`)
- รายการเป็น **คอลัมน์เดียวทุกขนาดจอ** (เลิกกริด 2–3 คอลัมน์)
- dark mode ทุกสี (`dark:` ≥ 20 จุดในไฟล์นี้)

### 2) `admin.vue` — โครงใหม่
**script** (เพิ่ม ห้ามแก้ของเดิมนอกจากที่บอก):
- import `AcademyAdminNav` (path เต็ม `~/components/academy/admin/AcademyAdminNav.vue`)
- เปลี่ยน `isSidebarOpen` เป็น `isDrawerOpen` ทั้งไฟล์ (watch route ที่ปิดเมนูคงไว้ แค่เปลี่ยนชื่อ) · ลบ `toggleSidebar` ถ้าไม่ใช้แล้ว
- `const badges = ref<Record<string, number>>({})` (SA-9 จะเติม)
- `const currentGroupLabel = computed(() => navGroups.value.find(g => g.sections.some(s => s.id === currentSection.value?.id))?.label ?? null)`
- เปิด drawer: ล็อกการเลื่อนหน้า (`document.body.style.overflow = 'hidden'`) · ปิด: คืนค่า · `onBeforeUnmount` คืนค่าเสมอ
- กด `Escape` ขณะ drawer เปิด → ปิด (listener ใส่/ถอดใน `onMounted`/`onBeforeUnmount`)

**template** (root เดียว `<div class="w-full">` เหมือนเดิม · loading spinner เดิมคงไว้):
- **หัว (ทุกขนาดจอ):** การ์ด `p-3 sm:p-4` แถวเดียว `flex items-center gap-3`
  - โลโก้ `h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0` (src เดิม)
  - กลาง `min-w-0 flex-1`: ชื่อโรงเรียน `break-words font-bold text-base sm:text-lg` (ตัดบรรทัดได้ ห้าม truncate) · บรรทัดล่าง `text-xs sm:text-sm text-gray-500 dark:text-gray-400` = **"หน้าจัดการโรงเรียน"** (แทน "Admin Panel")
  - ป้ายบทบาท `flex-shrink-0` สีเดิม (`roleColor`) · ชื่อบทบาท **แสดงทุกจอ** (`whitespace-nowrap text-xs sm:text-sm` — เลิก `hidden sm:inline`)
  - ลิงก์ชื่อโรงเรียนกลับหน้าโรงเรียนคงไว้
- **แถบมือถือ (`lg:hidden`)** ใต้หัว: แถว `flex items-center gap-2`
  - ปุ่ม "เมนู" `flex-shrink-0 min-h-[44px] px-4` ไอคอน `fluent:navigation-24-regular` · `aria-controls="academy-admin-drawer"` `:aria-expanded="isDrawerOpen"`
  - breadcrumb (ข้อ 4) ใน `min-w-0 flex-1`
- **drawer มือถือ** (`lg:hidden`): `<Teleport to="body">` **ภายใน** root div · `v-if="isDrawerOpen"`
  `id="academy-admin-drawer"` `fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-900` `role="dialog" aria-modal="true" aria-label="เมนูจัดการโรงเรียน"`
  - หัว drawer: ชื่อโรงเรียน (`min-w-0 flex-1 break-words font-semibold`) + ปุ่มปิด `fluent:dismiss-24-regular` `min-h-[44px] min-w-[44px] flex-shrink-0`
  - เนื้อ: `<AcademyAdminNav>` ใน `flex-1 overflow-y-auto p-3` · ท้าย: ลิงก์ "กลับหน้าโรงเรียน" `min-h-[44px]` + `pb-[env(safe-area-inset-bottom)]`
  - (z-50 + เต็มจอ = ทับทั้ง header ของ layout และ BottomNav · ปิดเองเมื่อ route เปลี่ยนด้วย watch เดิม)
- **desktop (`lg:`)**: `flex flex-col lg:flex-row gap-4 lg:gap-6`
  - `<aside class="hidden lg:block lg:w-64 lg:flex-shrink-0">` (ซ่อนบนมือถือได้ เพราะมือถือมี drawer แทน) ·
    กล่องใน `lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto` การ์ดขาว shadow แบบเดิม `p-4` · มี `<AcademyAdminNav>` + ลิงก์ "กลับหน้าโรงเรียน" ท้าย
  - `<main class="min-w-0 flex-1">`: breadcrumb แบบ desktop (`hidden lg:block mb-3`) แล้วการ์ดเนื้อหา `p-3 sm:p-6` เดิม + `AcademyAdminForbidden` / `NuxtPage` เดิมไม่เปลี่ยน
- ลบ `<style scoped>` เดิมได้ถ้าไม่เหลือ `nav` ที่ใช้ (scrollbar ไปทำใน component ด้วย Tailwind ไม่ต้อง)

### 3) breadcrumb
`<nav aria-label="breadcrumb">` ใน `overflow-x-auto` ของตัวเอง · `<ol class="flex items-center gap-1 whitespace-nowrap text-xs sm:text-sm">`:
"จัดการโรงเรียน" (ลิงก์ไปแดชบอร์ด) › `currentGroupLabel` (ข้อความ ไม่ใช่ลิงก์ · ไม่แสดงถ้า null หรือเป็นกลุ่มภาพรวม) › `currentSection.label` (ตัวหนา `aria-current="page"`)
· ตัวคั่น `fluent:chevron-right-16-regular` · อยู่บนแดชบอร์ด = แสดงแค่ "จัดการโรงเรียน"

### 4) `AcademyAdminPageHeader.vue` (ใหม่ — ยังไม่มีหน้าใช้ · SA-5 จะย้ายมา)
props `{ title: string; description?: string }` · slot `actions` · slot default (ใต้หัว)
โครง: `flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4 sm:mb-6` ·
ซ้าย `min-w-0 flex-1`: `h1 break-words text-lg sm:text-2xl font-bold text-gray-900 dark:text-white` + คำอธิบาย `text-sm text-gray-500 dark:text-gray-400 break-words`
· ขวา (มี slot actions): `flex flex-wrap gap-2 sm:flex-shrink-0` (ปุ่มในนั้นเป็นของหน้า)

### 5) `AcademyAdminState.vue` (ใหม่ — ยังไม่มีหน้าใช้)
props `{ state: 'loading' | 'empty' | 'error'; title?: string; message?: string }` · emit `retry` · slot `action`
- loading: spinner แบบเดียวกับเปลือก + ข้อความ (ค่าเริ่ม "กำลังโหลด…")
- empty: ไอคอน `fluent:document-search-24-regular` + title (ค่าเริ่ม "ยังไม่มีข้อมูล") + message
- error: ไอคอน `fluent:error-circle-24-regular` สีแดง + title (ค่าเริ่ม "โหลดข้อมูลไม่สำเร็จ") + ปุ่ม "ลองใหม่" `min-h-[44px] sm:min-h-0` ที่ emit `retry`
- (สถานะ "ไม่มีสิทธิ์" ใช้ `AcademyAdminForbidden` เดิม — ห้ามซ้ำ)
- กลางกล่อง `flex flex-col items-center gap-3 px-3 py-10 text-center sm:py-16` · dark mode ครบ

## เกณฑ์ผ่าน (Claude รันเอง)
```bash
cd C:/wamp64/www/nuxnan/ui
grep -c "Admin Panel" "pages/academies/[name]/admin.vue"                       # = 0
grep -c "hidden sm:inline" "pages/academies/[name]/admin.vue"                  # = 0
grep -c "isSidebarOpen\|max-h-\[70vh\]\|grid-cols-2" "pages/academies/[name]/admin.vue"   # = 0
grep -c "provideAcademyAdminContext\|canEnterAcademyAdmin" "pages/academies/[name]/admin.vue"   # = 2 ขึ้นไป (ของเดิมยังอยู่)
for f in AcademyAdminNav AcademyAdminPageHeader AcademyAdminState; do grep -c "dark:" components/academy/admin/$f.vue; done   # Nav ≥ 20
compile SFC ทั้ง 4 ไฟล์ (@vue/compiler-sfc compileScript inlineTemplate) · npx vitest run tests/academyAdminNav.spec.ts tests/useAcademyRole.spec.ts
```
**Claude เปิดจอจริง (บัญชีเจ้าของ) 375 → 768 → 1280:**
375: หัวแถวเดียว ชื่อบทบาทเห็น · ปุ่ม "เมนู" เปิด drawer เต็มจอทับ BottomNav · ค้นหา "บัตร" เหลือ 2 รายการ · กดรายการ → drawer ปิด + หน้าเปลี่ยน · Escape ปิด · หน้าเลื่อนไม่ได้ขณะ drawer เปิด ·
ทุกจอ: ไม่มีเลื่อนแนวนอนทั้งหน้า (`scrollWidth <= innerWidth`) · 1280: sidebar sticky ขณะเลื่อนเนื้อหา · breadcrumb ถูกต้องบน `/admin/student-cards/requests` · console ไม่มี error
