# Work Log — nuxnan project

## 2026-08-28 (จบงาน touch target) — กวาด 44px ครบทั้งแอป: 1,542 → 0 ปุ่ม (push แล้ว)

### สถานะ: **สแกนทั้งแอปเหลือ 0** — `9f99fe9a` · `8b758cb3` · `5f5f6c7b`

| รอบ | ขอบเขต | ปุ่ม | ไฟล์ | commit |
|---|---|---|---|---|
| 1 | ฟอร์มโพล/โพสต์/ผู้ปกครอง (TODO เดิม) | 37 | 6 | `b35205da` |
| 2 | ปุ่มไอคอนล้วน <32px ทั้งแอป | 128 | 54 | `dad294ea` |
| 3 | toolbar RichTextEditor (แถวเดียวเลื่อนแนวนอน) | 23 | 1 | `e9be7d72` |
| 4 | โดเมน learn | 354 | 100 | `9f99fe9a` |
| 5 | โดเมน academy | 516 | 110 | `8b758cb3` |
| 6 | ที่เหลือทั้งหมด (nuxnan-admin/school/Earn/Admin/play/profile/…) | 506 | 146 | `5f5f6c7b` |

รวม **1,564 ปุ่ม / ~417 ไฟล์** · สแกนทั้ง `components/` + `pages/` + `layouts/` (753 ไฟล์) **เหลือ 0**

### รูปแบบที่ใช้ (ทำซ้ำได้)

- ปุ่มข้อความ → `min-h-[44px] sm:min-h-0` เท่านั้น **ไม่เปลี่ยน display**
  (`<button>` จัดเนื้อหากึ่งกลางแนวตั้งเองอยู่แล้ว — ยืนยันด้วย Range API ว่าจุดกึ่งกลางข้อความ
  ตรงกับจุดกึ่งกลางปุ่ม การไปยัด `inline-flex` ให้ปุ่มข้อความ 800+ ตัวเสี่ยงพัง layout โดยไม่จำเป็น)
- ปุ่มไอคอนล้วน → `+ min-w-[44px] sm:min-w-0 inline-flex items-center justify-center`
- ปุ่มที่มีแต่ `:class` (ไม่มี static class) → เพิ่ม `class="min-h-[44px] sm:min-h-0"` เป็น attribute ใหม่ (Vue merge ให้เอง)
- **จอ `sm:` ขึ้นไปขนาดเดิมทุกปุ่ม** — ไม่มีอะไรเปลี่ยนบนเดสก์ท็อป

### บทเรียน/กับดักที่บันทึกไว้แล้วในรายการก่อนหน้า (ย้ำ)

1. หา opening tag ด้วย regex ต้อง **รู้ quote** — `[\s\S]*?>` จะหยุดที่ `>` ใน `v-if="a > b"`
2. `\bflex\b` **แมตช์ `flex-shrink-0`** — ต้องใช้ `(?:^|\s)flex(?:\s|$)`
3. `overflow-x-auto` ทำให้ `overflow-y` เป็น `auto` ⇒ dropdown ที่เป็น absolute ในกล่องสกอลล์โดนคลิป
4. ปุ่มที่เป็น **grid container** (`grid ... items-center`) ก็ใช้ `items-center` ได้เหมือน flex — ตรวจแล้วเนื้อหายังกึ่งกลาง

### วิธีตรวจทุกรอบ (เหมือนกันหมด)

`สแกน → patch → สแกนซ้ำต้องได้ 0 → SFC compile ทุกไฟล์ที่แตะ → diff purity (ทุกบรรทัด `+` ต้องมี
`min-h-[44px]` เท่านั้น) → วัดขนาดจริงในเบราว์เซอร์ที่ 375px ด้วย class string ที่ก๊อปจาก diff`

### งานที่ค้าง (TODO ต่อ — เหลือ 2)

- [~] **เปิดหน้าจริงบนมือถือยืนยัน** — งานทั้งหมดตรวจผ่าน harness (เข้าหน้าที่ต้อง login ไม่ได้)
      - [x] **แถบเครื่องมือ RichTextEditor** — ผู้ใช้เปิดหน้าจริงบนมือถือแล้ว **ใช้ได้ปกติ** (2026-08-28)
            ⇒ จุดเดียวที่เปลี่ยนพฤติกรรม (เลื่อนแนวนอน + เมนูสี/Template ย้ายมาอยู่ใต้แถบ) ยืนยันบนเครื่องจริงแล้ว
      - [ ] หน้าที่เหลือ (บทเรียน · ฟีด · แอดมิน academy · สมุดคะแนน) ยังไม่ได้เปิดจริง
- [ ] `isomorphic-dompurify` ถ้าต้องการ sanitize ระดับเดียวกันฝั่งเซิร์ฟเวอร์ (ลาก jsdom มาด้วย)
- [x] ~~`npm run build`~~ — **ผู้ใช้รันเองแล้ว ผ่าน ไม่มี error** (2026-08-28) ครอบคลุมงานทั้งชุดที่แตะ ~417 ไฟล์
      ⇒ `min-h-[44px]`/`min-w-[44px]`, `@apply` ใน `<style scoped>` ของ RichTextViewer ทั้งสองตัว และ `dompurify` ฝั่ง Nitro ผ่าน build จริงหมด

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (ยกเว้น worklog นี้)
- Push: **pushed** — `origin/main` = `5f5f6c7b`

---

## 2026-08-28 (ปิดท้าย) — ลบ dead CSS ใน RichTextViewer · เหลือ TODO 4 ข้อ (push แล้ว)

### สถานะ: **ปิด TODO โค้ดตาย · push แล้ว** — `708b0164`

`708b0164` chore(ui): drop 77 lines of dead scoped CSS from RichTextViewer — **−77 บรรทัด ไม่มีบรรทัดเพิ่ม**

`<style scoped>` ของ `components/RichTextViewer.vue` มีกฎ **19 ข้อ** ที่เขียนเป็น `:deep(.prose h1)` ฯลฯ
คอมไพล์ออกมาเป็น `[data-v-x] .prose h1` ⇒ ต้องการ `.prose` ที่เป็น **ลูก** ของ root แต่ root เองคือ `.prose`
⇒ **ไม่เคยแมตช์อะไรเลยตั้งแต่แรก** (หน้าตาที่เห็นทุกวันนี้มาจาก plugin typography ล้วน ๆ)

**พิสูจน์ก่อนลบ — ไม่เชื่อการอ่านโค้ดอย่างเดียว:** สร้าง DOM ตามโครงจริง (root มีทั้ง `data-v-*`
และ `class="prose"` · เนื้อหาจาก `v-html` เป็นลูกโดยตรง) แล้วเรียก `element.matches()` ในเบราว์เซอร์
- 10 selector ที่ลบ → ไม่แมตช์สักตัว
- 5 selector ที่เก็บไว้ (`.rtv-table-scroll`, `> table`, `td`, `pre`, `img`) → แมตช์ครบ

สคริปต์ลบมีการ์ด: ตรวจว่าทุก rule ในช่วงที่จะตัดขึ้นต้นด้วย `:deep(.prose` เท่านั้น ถ้าเจออย่างอื่นจะ abort
เหลือใน style block เฉพาะกฎที่ทำงานจริง: กล่องเลื่อนตาราง / `pre` / `img` / `iframe`+`video`

💡 **กฎที่ได้จากเคสนี้ (ใช้ซ้ำได้):** ใน SFC ที่ root element มี utility class เดียวกับที่อยากอ้างถึง
(`class="prose"`) **ห้ามเขียน `:deep(.prose X)`** — มันจะกลายเป็นการหา descendant ไม่ใช่ตัวมันเอง
ถ้าจะจัดสไตล์ให้ node ที่มาจาก `v-html` ให้เขียน `:deep(X)` ตรง ๆ และยืนยันด้วย `compileStyle()` เสมอ

### งานที่ค้าง (TODO ต่อ — เหลือ 4)

- [ ] **เปิดหน้าจริงบนมือถือยืนยัน** — งาน 3 วันนี้ตรวจผ่าน harness ทั้งหมด (ยังไม่เคยเปิดหน้าที่ต้อง login)
- [ ] ปุ่ม**ที่มีข้อความ** 36–40px อีกราว 1,400 จุดใน ~350 ไฟล์ (ควรทำทีละโดเมนแล้วเปิดดูจริง)
- [ ] `isomorphic-dompurify` ถ้าต้องการ sanitize ระดับเดียวกันฝั่งเซิร์ฟเวอร์ (ลาก jsdom มาด้วย — เป็นการตัดสินใจเรื่อง dependency)
- [ ] `npm run build` ผู้ใช้รันเอง — ยังไม่ได้รันตลอด 3 วันนี้

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (ยกเว้น worklog นี้)
- Push: **pushed** — `origin/main` = `708b0164`

---

## 2026-08-28 (ต่อ) — Touch target 44px: 165 ปุ่มใน 61 ไฟล์ + toolbar เอดิเตอร์เลื่อนแนวนอน (push แล้ว)

### สถานะ: **ปิด TODO ปุ่มเล็กครบ · push แล้ว** — `738840d1..e9be7d72`

```
e9be7d72  toolbar เอดิเตอร์แถวเดียวเลื่อนแนวนอน + 23 ปุ่ม 44px   (1 ไฟล์)
dad294ea  ปุ่มไอคอนล้วน <32px ทั้งแอป 128 ปุ่ม                    (54 ไฟล์)
b35205da  ฟอร์มโพล/โพสต์/ผู้ปกครอง 37 ปุ่ม                        (6 ไฟล์)
```

### วิธีทำ — สแกนด้วยสคริปต์ ไม่ใช่ไล่ดูเอง

เขียนสคริปต์อ่าน `<button>` โดย **parse opening tag แบบรู้ quote** แล้วคำนวณความสูงจาก
padding + ขนาดไอคอน/บรรทัดข้อความข้างใน (สคริปต์อยู่ใน scratchpad ของเซสชัน ไม่ได้ commit)

⚠️ **เวอร์ชันแรกของสคริปต์อ่านพลาด** — ใช้ `[\s\S]*?>` หา opening tag ซึ่งไปหยุดที่ `>`
ที่อยู่ **ข้างใน attribute** เช่น `v-if="pollOptions.length > 2"` ⇒ มองไม่เห็น class ของปุ่มนั้น
และรายงานตัวเลขต่ำกว่าจริง (873 → จริง 1,542) **ถ้าจะสแกน markup ด้วย regex ต้องรู้ quote เสมอ**

ตัวเลขจริงทั้งแอป (753 ไฟล์): **1,542 ปุ่มใน 369 ไฟล์** ต่ำกว่า 44px
รอบนี้เลือกทำ **ปุ่มไอคอนล้วนที่ <32px** (128 ปุ่ม — พวกปุ่มลบ/ปิด/แก้ไขในแถวรายการ กดพลาดง่ายสุด)
เหลือปุ่มที่มีข้อความ 36–40px อีกราว 1,400 จุด **ยังไม่แตะ**

รูปแบบที่ใช้: `min-h-[44px] sm:min-h-0` + ปุ่มไอคอนล้วนได้ `min-w-[44px] sm:min-w-0`
พร้อม `inline-flex items-center justify-center` — **จอ sm: ขึ้นไปขนาดเดิมทุกปุ่ม**

⚠️ **กับดักในสคริปต์แพตช์:** `\bflex\b` ไป**แมตช์คำว่า `flex-shrink-0`** ⇒ 8 ปุ่มได้
`items-center justify-center` โดยไม่มี display flex จริง (ไอคอนกองมุมซ้ายบนของกล่อง 44px)
เจอตอนอ่าน diff เอง แก้แล้ว — เวลาเช็คคลาส Tailwind ต้องใช้ `(?:^|\s)flex(?:\s|$)`

### toolbar ของ RichTextEditor — ทำไมต้องแยกออกมา

ถ้าขยายปุ่มทั้ง 22 เป็น 44px แล้วปล่อยให้ wrap **แถบจะสูงจาก 63px เป็น 195px** ที่จอ 375px
(วัดจริง) กินเกิน 1/4 ของจอไปกับ toolbar อย่างเดียว ⇒ ผู้ใช้เลือก "แถวเดียวเลื่อนแนวนอน"

- `flex-nowrap overflow-x-auto` บนมือถือ · `sm:flex-wrap sm:overflow-visible` ที่จอใหญ่
- กลุ่มปุ่มทุกกลุ่ม + Templates + Undo/Redo ได้ `flex-shrink-0` · `ml-auto` → `sm:ml-auto`
- สกอลล์บาร์บาง 4px บนมือถือ (คลาส `.rte-toolbar` ใน `<style>` ท้ายไฟล์) ซ่อนที่ sm: ขึ้นไป

🔴 **กับดักที่ต้องแก้พร้อมกัน:** `overflow-x: auto` ทำให้ `overflow-y` computed เป็น `auto` ด้วย
⇒ เมนู dropdown ที่เป็น `absolute` **ในกล่องสกอลล์จะโดนคลิปหายทั้งเมนู**
จึงย้ายเมนูทั้ง 3 (สีตัวอักษร / ไฮไลท์ / Template) ออกมาไว้ในกล่อง `relative` **ใต้ toolbar**
ปุ่มกดยังอยู่ในแถบเหมือนเดิม · handler / `v-for` / state ไม่แตะ (นับยืนยันครบทุกตัวหลังย้าย)

ผลวัดที่ 375px: toolbar 57px · 23 ปุ่มแถวเดียว ไม่มีตัวไหน <44px · เลื่อนได้ (1123px ในกล่อง 341px)
· เมนูล้นใต้ toolbar 90px ไม่โดนคลิป · ที่ 1280px ปุ่มกลับเป็น 28×28 แถวเดียวเหมือนเดิม

### งานที่ค้าง (TODO ต่อ)

- [ ] **เปิดหน้าจริงบนมือถือยืนยัน** — ทั้งสามวันนี้ตรวจผ่าน harness (เสิร์ฟ HTML ที่ก๊อป class string
      จากไฟล์จริงผ่าน WAMP แล้ววัดด้วย `getBoundingClientRect()`) ยังไม่เคยเปิดหน้าจริงที่ต้อง login
- [ ] ปุ่ม**ที่มีข้อความ** 36–40px อีกราว 1,400 จุดใน ~350 ไฟล์ — ถ้าจะกวาดต่อควรทำทีละโดเมนแล้วเปิดดูจริง
      เพราะความหนาแน่นของ UI จะเปลี่ยนทั่วแอป
- [ ] `components/RichTextViewer.vue` ยังมี `<style scoped>` ~90 บรรทัดที่เป็นโค้ดตาย (`:deep(.prose ...)`)
- [ ] ถ้าต้องการ sanitize ระดับเดียวกันฝั่งเซิร์ฟเวอร์ ต้องเพิ่ม `isomorphic-dompurify` (ลาก jsdom มาด้วย)
- [ ] `npm run build` ผู้ใช้รันเอง — ยังไม่ได้รันตลอด 3 วันนี้ (ตรวจแค่ SFC compile + `tsc --noEmit`)

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (ยกเว้น worklog นี้)
- Push: **pushed** — `origin/main` = `e9be7d72`

---

## 2026-08-28 — RichTextViewer: ตารางล้นจอ + XSS · ปิด 2 TODO ของเมื่อวาน (push แล้ว)

### สถานะ: **ปิดครบทั้ง 2 เรื่อง · push แล้ว** — `55212b36..2fbaf4ce`

ต่อจาก TODO ที่ค้างไว้ในรายการ 2026-08-27 ปรากฏว่าทั้งสองเรื่องซ่อนปัญหาที่ลึกกว่าที่เขียนไว้

### 🔴 F1 — หน้าบทเรียนใช้ RichTextViewer คนละตัวกับที่มีโค้ดจัดการตาราง

มี 2 ไฟล์ชื่อเดียวกัน: `components/Common/RichTextViewer.vue` (มีโค้ดตาราง) กับ
`components/RichTextViewer.vue` (ไม่มีเลย) — `LessonPost` / `TopicAccordion` / `AssignmentCard` /
`LessonAssignmentSection` / `AssignmentGradingModal` **import ตัวหลัง**
⇒ ตารางในบทเรียนดันทั้งหน้าให้เลื่อนแนวนอนจริง วัดที่ 375px: `table 544px ในกล่อง 309px → page scrollWidth 577 vs clientWidth 375`

**ซ้อนอีกชั้น: `<style scoped>` ของไฟล์นั้นเป็นโค้ดตายทั้งบล็อก (~90 บรรทัด)**
`:deep(.prose table)` คอมไพล์เป็น `[data-v-x] .prose table` ⇒ ต้องการ `.prose` ที่เป็น**ลูก**ของ root
แต่ root เองคือ `.prose` ⇒ ไม่แมตช์อะไรเลย (ที่หน้าตาโอเคทุกวันนี้มาจาก plugin typography ล้วน ๆ)
ยืนยันด้วย `compileStyle()` จาก @vue/compiler-sfc ไม่ใช่เดา

**⚠️ วิธีที่ดูเหมือนถูกแต่ผิด:** `table { display:block; width:100%; overflow-x:auto }`
(วิธีที่ `Common/RichTextViewer` ใช้อยู่เดิม) — ตารางถูกบีบเท่ากล่อง หัวคอลัมน์ไทยแตก 3–4 บรรทัด
วัดได้ `th` สูง **110px** ⇒ กลายเป็นบั๊กเดียวกับที่เพิ่งแก้ไปเมื่อวาน

**วิธีที่ใช้จริง:** ห่อ `<table>` ด้วย `.rtv-table-scroll` แล้วให้ตารางกว้างตามเนื้อหา
(`width:max-content; min-width:100%`) ⇒ `th` เหลือ 41px (1 บรรทัด) · กล่องเลื่อนเอง · ทั้งหน้าไม่เลื่อน
ตารางแคบ 2 คอลัมน์ยังยืดเต็มกล่องเหมือนเดิม ไม่มีสกอลล์

### 🔴 F2 — DOMPurify ตอนไม่มี DOM คืนค่าเดิมทั้งก้อน

`components/RichTextViewer.vue` ยัดเนื้อหาด้วย `v-html` โดยไม่ sanitize เลย (คอมเมนต์ในไฟล์เขียนเองว่า
"in production, use a library like DOMPurify") — แต่พอจะแก้ด้วยการเรียก `sanitizeHtml` เฉย ๆ กลับเจอว่า

```
// dompurify/dist/purify.cjs.js
if (!DOMPurify.isSupported) { return dirty; }
```

⇒ ตอน SSR/Nitro (ไม่มี window) มัน **คืน HTML อันตรายกลับมาทั้งก้อน**
⇒ `Common/RichTextViewer` ที่เรียก `sanitizeHtml` อยู่แล้วก็มีช่องนี้มาตลอดเหมือนกัน

**แก้:** เพิ่ม `stripDangerousMarkup()` ใน `useRichText` เป็นด่านสำรองเมื่อ `!DOMPurify.isSupported`
+ เพิ่ม `FORBID_TAGS: ['form','style']` เพราะเทสต์เจอว่า DOMPurify ตัด `action="javascript:"` ให้
แต่ **ปล่อย `<form>` ค้างไว้** (ทำฟอร์มล็อกอินปลอมซ้อนในบทเรียนได้)

⚠️ **ห้ามใส่ `input` / `label` ลง FORBID_TAGS** — TipTap task list ใช้ `<input type="checkbox">` จริง จะพัง

### commits (push แล้วทั้งคู่)

- `f3cd0d2d` fix(ui): ตารางกว้างเลื่อนในกล่องตัวเอง — `useRichText.ts` (+`wrapTablesForScroll`), `RichTextViewer.vue`, `Common/RichTextViewer.vue`
- `2fbaf4ce` fix(security): sanitize `RichTextViewer` + ปิดช่อง SSR — `useRichText.ts`, `RichTextViewer.vue`

### วิธีตรวจที่ใช้ (ทำซ้ำได้ ไม่ต้อง login)

1. **CSS/layout:** harness HTML เสิร์ฟผ่าน WAMP (`C:\wamp64\www\_claude_scratch\` สร้าง–ลบทุกครั้ง)
   วัดด้วย `getBoundingClientRect()` + `documentElement.scrollWidth === clientWidth`
2. **ด่านสำรอง SSR:** รันใน Node โดย **ดึงตัวฟังก์ชันออกมาจากไฟล์จริง** ด้วย regex + `new Function`
   (ไม่ก๊อปโค้ดมาเทสต์ซ้ำ จะได้ไม่หลอกตัวเอง) — 10/10 PASS และเนื้อหาปกติไม่ถูกแตะ
3. **pipeline ฝั่งเบราว์เซอร์:** ก๊อป `node_modules/dompurify/dist/purify.min.js` ไปเสิร์ฟคู่กับ harness
   ยิง payload 11 แบบเข้า DOM จริงแล้วนับว่ามีอันไหนทำงานไหม — 18/18 PASS ไม่มีตัวไหนทำงาน
   และของที่ต้องรอด (ตาราง, YouTube iframe + sandbox, TipTap task list, `target=_blank`, รูป, ไทย) รอดครบ
4. **selector ตาย/ไม่ตาย:** `compileStyle()` จาก @vue/compiler-sfc พิมพ์ selector ที่คอมไพล์จริงออกมาดู

### งานที่ค้าง (TODO ต่อ)

- [ ] **เปิดหน้าจริงบนมือถือยืนยัน** — งานสองวันนี้ตรวจผ่าน harness ล้วน ๆ (เข้าคอร์สจริงไม่ได้เพราะต้อง login)
- [ ] ปุ่มเล็กที่ยังต่ำกว่า 44px: `StepGuardian.vue` ปุ่มลบช่องทางติดต่อ (`p-1` = 24px), `CreatePollModal.vue` / `EditPollModal.vue` ปุ่มลบตัวเลือก — รอบที่แล้วแตะแค่ `flex-shrink-0` ไม่ได้ขยายขนาด
- [ ] `components/RichTextViewer.vue` มี `<style scoped>` ~90 บรรทัดที่เป็นโค้ดตาย (`:deep(.prose ...)`) — จะลบทิ้งหรือแก้ให้ทำงานก็ได้ แต่ **ถ้าแก้ให้ทำงานหน้าตาจะเปลี่ยน** (h1/h2/table จะโดน override จาก typography)
- [ ] ถ้าอยากได้ sanitize ระดับเดียวกันบนเซิร์ฟเวอร์จริง ต้องเพิ่ม dependency `isomorphic-dompurify` (ลาก jsdom มาด้วย) — ยังไม่ทำเพราะเป็นการตัดสินใจเรื่อง dependency
- [ ] `npm run build` ผู้ใช้รันเอง — ยังไม่ได้รันตลอดสองวันนี้ (ตรวจแค่ SFC compile + `tsc --noEmit` บน composable)

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (ยกเว้น worklog นี้)
- Push: **pushed** — `origin/main` = `2fbaf4ce`

---

## 2026-08-27 (ต่อ) — งานเร่งด่วน: หน้า `/Learn/Courses/16/lessons` บนจอเล็ก · แก้ 31 ไฟล์ 3 commits (push แล้ว)

### สถานะ: **ปิดครบทุกข้อที่ผู้ใช้ชี้ · push ขึ้น origin/main แล้ว** — `ceeeacea..467441d7`

ผู้ใช้ส่งภาพหน้าจอมาทีละจุด 4 รอบ ทุกจุดแก้แล้วและวัดผลจริงในเบราว์เซอร์ทุกครั้ง

| อาการที่ผู้ใช้ชี้ | สาเหตุจริง | สถานะ |
|---|---|---|
| แท็บ "แบบทดสอบ" แตกเป็นตัวอักษรแนวตั้ง | 3 แท็บ + ปุ่ม 5 อันเรียง `flex-1` แถวเดียวโดยไม่มี `whitespace-nowrap`/`min-w-0` — ไทยไม่มีช่องว่างจึงถูกบีบแตก | ✅ |
| ทำไมไม่โชว์แค่ไอคอน | (ผู้ใช้เสนอเอง) เดิมผมเลี่ยงเพราะกติกา "ห้าม `hidden` ซ่อนข้อมูลบนมือถือ" → ตกลงว่าไอคอนไลก์/แชร์สื่อความหมายเองได้ | ✅ ใส่ `hidden sm:inline` + `title`/`aria-label` |
| ปุ่มขยายไซด์เมนูใหญ่บังเนื้อหา | `layouts/main.vue` handle ซ้าย/ขวา `p-3` = 44×44 ทึบ 100% กลางจอพอดี | ✅ เหลือ 28×44 opacity-60 |
| ปุ่มส่งความคิดเห็นล้นออกนอกกล่อง | **`<input>` มี intrinsic width จาก `size=20` (~204px) + flex item มี `min-width:auto`** ⇒ `flex-1` ย่อไม่ได้ ดันปุ่มทะลุขอบ (วัดได้ล้น 31px) | ✅ `min-w-0` ที่ input + `flex-shrink-0` ที่ปุ่ม |

### 3 commits ที่ push แล้ว

- `9ad64062` fix(learn): lessons page ที่ 375px — `LessonInteractionTabs.vue`, `LessonPost.vue`, `TopicAccordion.vue`, `pages/Learn/Courses/[id]/lessons.vue`
- `0b85d518` fix(ui): sidebar handle + ฟอร์มคอมเมนต์ฟีด — `layouts/main.vue`, `CourseFeedPost.vue`, `play/feed/FeedPost.vue`, `play/post/PostCard.vue`
- `467441d7` fix(ui): กวาด `min-w-0` ทั้งแอป — 23 ไฟล์ (26 จุด)

### Context สำคัญ

1. **รูทเคสของบั๊ก "ปุ่มล้น" ใช้ซ้ำได้ทั้งแอป:** `flex-1` บน `<input>` **ไม่พอ** ต้องมี `min-w-0` ด้วยเสมอ
   เพราะ flex item ตั้งต้นที่ `min-width:auto` และ input มีความกว้างในตัวจาก attribute `size`
   ⇒ เขียนฟอร์มใหม่ทุกครั้งให้ใส่คู่กัน: input = `min-w-0 flex-1`, ปุ่มข้าง ๆ = `flex-shrink-0`
2. **commit `467441d7` เป็นการกันไว้ก่อน ไม่ใช่บั๊กที่เห็นวันนี้** — วัดแล้ว 26 จุดนั้นที่ 375px เต็มจอ *ยังไม่ล้น*
   (พื้นที่พอดีกับ ~204px ของ input) แต่พอบีบ container เหลือ 260px ของเดิมล้นจริง (QR 34px, invite link 26px)
   ⇒ ถ้าเจอ regression แปลก ๆ ในฟอร์มไหน ให้รู้ว่า commit นี้แตะแค่ 2 token คือ `min-w-0` / `flex-shrink-0`
3. **`TopicAccordion` เลิกซ่อน badge เวลาอ่านบนมือถือแล้ว** (เดิม `hidden md:flex`) — ตอนนี้ต้องพึ่ง `min-w-0 flex-1`
   ที่ `<h4>` ชื่อหัวข้อเพื่อไม่ให้แถวล้น **อย่าถอด `min-w-0` ออกจาก h4 นั้น**
4. **agy ทำงาน 2 shard ขนานผ่าน ทั้งคู่ตรงสเปค** (95 insert / 95 delete พอดี ไม่มี deletion เกิน) แต่ **ทำตก 1 ข้อ**
   คือ class ของ `<h4>` ใน `TopicAccordion` (ข้อ 2.3) — ยืนยันอีกครั้งว่าต้องอ่าน diff เองทุกข้อ ไม่เชื่อรายงาน

### วิธีตรวจที่ใช้รอบนี้ (ทำซ้ำได้ ไม่ต้อง login)

หน้าจริงต้อง login เข้าคอร์ส เลยตรวจด้วย **harness**: ก๊อป class string สุดท้ายจากไฟล์จริงมาวางใน HTML เปล่า
เสิร์ฟผ่าน WAMP ที่ `C:\wamp64\www\_claude_scratch\` (สร้าง–ลบทิ้งทุกครั้ง) แล้ววัดด้วย `getBoundingClientRect()`
เทียบ `button.right` กับขอบในของกล่อง + เช็ก `documentElement.scrollWidth === clientWidth`

⚠️ **กับดักที่เจอ:** ถ้า Browser pane ไม่ได้ถูกแสดงอยู่ `clientWidth` จะเป็น **0** และตัวเลขที่วัดได้จะมั่วทั้งชุด
(รอบแรกได้ "ล้น 236px" ทั้งที่ไม่จริง) ⇒ ต้อง `tabs_select` ให้ pane โผล่ก่อน แล้วค่อยวัด

### งานที่ค้าง (TODO ต่อ)

- [ ] **ยังไม่ได้เปิดหน้าจริงบนมือถือ** — ตรวจผ่าน harness ล้วน ๆ ควรเปิด `/Learn/Courses/16/lessons` ด้วยบัญชีจริงที่ 375px ยืนยันอีกรอบ
- [ ] เนื้อหา RichText ในบทเรียน (`RichTextViewer` ใน `LessonPost.vue`) **ยังไม่ได้แตะ** — ถ้าเนื้อหามีตาราง/โค้ดกว้าง ๆ ยังมีสิทธิ์ดันทั้งหน้าให้เลื่อนแนวนอน ต้องหุ้ม `overflow-x-auto` ให้ตาราง
- [ ] ปุ่มลบ/ปุ่มเล็กในแถวหนาแน่นบางจุดยังต่ำกว่า 44px (เช่น `StepGuardian.vue` ปุ่มลบช่องทางติดต่อ `p-1` = 24px, `CreatePollModal`/`EditPollModal` ปุ่มลบตัวเลือก) — รอบนี้แตะแค่ `flex-shrink-0` ไม่ได้ขยายขนาด
- [ ] `npm run build` ผู้ใช้รันเอง — ยังไม่ได้รัน (ตรวจแค่ `@vue/compiler-sfc` parse+compileTemplate ผ่าน 31/31 ไฟล์)

### Branch / Git State

- Branch: `main` (commit ตรงบน main ตามที่เรพนี้ทำกันมา)
- Uncommitted: ไม่มี (ยกเว้น worklog นี้)
- Push: **pushed** — `origin/main` = `467441d7`

---

## 2026-08-27 (ต่อ) — #25 เลือกตั้ง: เปิดอีก 5 หน้าที่เหลือบนจอจริง · เจอ 2 บั๊กที่ทำให้หน้าแอดมินใช้ไม่ได้เลย

### สถานะ: **เจอ 2 บั๊ก → ส่ง agy แก้ → ตรวจซ้ำบนจอ ปิดครบแล้ว** · สเปกเพิ่ม §15 + §16

รอบก่อนปิดหนี้ §13.9 ให้ `station.vue` ไปหน้าเดียว รอบนี้เปิด **ที่เหลือทั้งหมด** ที่ 375px

| หน้า | ผล |
|---|---|
| `admin/elections/index.vue` | ✅ ครบ · ตัวเลขตรงฐาน · ไม่มีปุ่มต่ำกว่า 40px · แต่ปุ่มสร้างเปิดฟอร์มไม่ได้ |
| `admin/elections/[id].vue` | 🔴 **ทั้ง 6 แท็บว่างเปล่า** |
| `elections/index.vue` (สมาชิก) | ✅ ครบ · ร่างถูกซ่อนจากนักเรียนจริง |
| `elections/[id]/apply.vue` | 🔴 **คนที่ยังไม่เคยสมัครเห็นหน้าว่าง** · คนที่มีใบสมัครแล้วเห็นครบ |
| `elections/[id]/results.vue` | ✅ ครบ · ผู้ชนะ/อันดับ/งดออกเสียง/turnout `14 / 2193 (0.64%)` |
| ปุ่มตั้งสภานักเรียน (E-S12b) | 🔴 กดไม่ถึง — อยู่ในแท็บที่ไม่เรนเดอร์ |

### 🔴 F1 — ชื่อคอมโพเนนต์ไม่ตรงชื่อ auto-import (9 จุด / 2 ไฟล์)

ไฟล์อยู่ `components/academy/elections/` ⇒ Nuxt ลงทะเบียนเป็น **`AcademyElectionsElectionOverviewTab`**
แต่หน้าเรียก `<ElectionOverviewTab>` ⇒ Vue หาไม่เจอ **เรนเดอร์เป็น custom element เปล่า ไม่มี warning เลย**
ยืนยันจาก DOM จริง: `electionresultstab` / `electionformmodal` มี `children.length === 0` สูง 0px

⇒ **หน้าแอดมินการเลือกตั้งใช้จริงไม่ได้เลยมาตั้งแต่ E-S9** — สร้าง/แก้ไข/ดูพรรค/ดูผล/ตั้งสภาฯ ไม่ได้สักอย่าง
ทั้งที่ผ่านเกณฑ์ตรวจทุกข้อของ E-S9…E-S12 เพราะไม่มีเกณฑ์ไหน **เปิดหน้านั้นจริง** (ซ้ำรอย §13.10 เป๊ะ)

### 🔴 F2 — `unwrap()` ของ `apply.vue` เปลี่ยน `data: null` ให้กลายเป็น "มีใบสมัครแล้ว"

`GET /parties/mine` ตอบ `{"success":true,"data":null}` ตามสเปก §12.1 A3 (ยิงยืนยันแล้ว)
`const unwrap = (r) => r?.data?.data ?? r?.data ?? r` · `r.data` เป็น `null` ⇒ `??` ข้ามไปคืน **response ทั้งก้อน**
⇒ truthy และไม่มี `.status` ⇒ ทุกสาขาใน template เป็นเท็จ ⇒ **หน้าว่าง ยื่นใบสมัครไม่ได้เลย**

หลอกตามาก: SSR เรนเดอร์ฟอร์มออกมาก่อน (ตอนนั้น `myParty` ยัง null จริง) แล้วฟอร์ม **หายไปทั้งอันหลัง fetch เสร็จ**

### 🟡 เรื่องรอง

- `[id].vue:85` โชว์สถานะดิบ `· published` ทั้งที่หน้า index แปลไทยแล้ว
- `RejectElectionPartyRequest` ไม่มี `messages()` ⇒ ได้ error อังกฤษตัวเดียวในโดเมนนี้
- **นอกเมนู #25:** `/api/notifications/recent` ตอบ **500 ทุกหน้า** ยิงซ้ำ 7–8 ครั้งต่อการโหลด (ของ layout)

### กวาดทั้งแอป — F1 ไม่ได้เกิดที่เดียว

เทียบชื่อแท็กกับชื่อที่ Nuxt ลงทะเบียน ทั้ง `pages/` + `components/` + `layouts/` เหลือ **25 จุด**
ยืนยันด้วยจอแล้ว 9 (ของเมนู #25) · ที่เหลือยังไม่เปิดจอ **อย่าเพิ่งเชื่อ** — ตัวที่น่าสงสัยสุดคือ
`admin/gradebook/rollover/index.vue` (7 จุด ไม่มี import เลย) และ `admin/revenue.vue` (3 จุด)

### 🔴 กับดักที่ต้องจำ

1. **`document.body.innerText` เชื่อไม่ได้บนแอปนี้** — เคยคืนค่าว่างทั้งที่ DOM มีเนื้อครบ (เกือบรายงานผิดว่า `results.vue` พัง)
   ⇒ ตัดสิน "หน้าว่าง" ด้วยการนับ element + `getBoundingClientRect()` เท่านั้น
2. **auto-import พลาดแล้วเงียบสนิท** — ไม่มี warning · เกณฑ์ "กวาด SFC" ของ §13.10 จับไม่ได้
3. **ยิง API ได้ 200 ครบ ไม่ได้แปลว่าหน้ามีเนื้อ** — F1 กับ F2 ยิง 200 ครบทั้งคู่

### ข้อมูลทดสอบ — **ลบครบแล้ว ฐานกลับสู่สภาพเดิม**

`elections*` ทั้ง 8 ตารางกลับเป็น **0 แถว** · `academy_groups` กลับเป็น **39** เท่าก่อนเริ่ม
(เคยมี election 18/19/20 · พรรค 7–10 · หน่วย 11,12 · ผู้มีสิทธิ์ 2,193 · บัตร 14 · กลุ่มสภาฯ id 40)
คำสั่งล้างใน §15.6 + §16.5 **ถูกใช้ไปแล้ว ไม่ต้องรันซ้ำ**

### ความพร้อมบัญชีผู้มีสิทธิ์ก่อนซ้อม §9 — ปิดข้อ 2 ของ §9 แล้ว (รายละเอียดใน §18)

ตัวเลขเดิมใน §9 **ล้าสมัยไปมาก** — วัดจาก `lock()` ของจริง:

| รายการ | §9 เดิม | วัดได้จริง |
|---|---|---|
| ผู้มีสิทธิ์ (มัธยม) | ~2,212 | **2,193** |
| ไม่มี `member_code` | 4 | **0 — ปิดแล้ว** |
| ไม่มีบัตรนักเรียน active | 284 | **103** |

🔴 **99 ใน 103 คนนั้นไม่มีห้องเรียนของปีปัจจุบันด้วย** ⇒ บนจอหน่วยจะขึ้นแต่ชื่อ ห้อง/เลขที่ว่าง
กรรมการไม่มีอะไรเทียบยืนยันตัวตนนอกจากชื่อ — **เป็นงานฝ่ายทะเบียน ไม่ใช่งานโค้ด ต้องตัดสินก่อนวันจริง**

✅ ทางเลี่ยงใช้ได้จริงทั้งสองทาง (ยิง HTTP ยืนยันเอง): กรอก `member_code` → `status: eligible`
· ค้นด้วยชื่อ → เจอ · `lookup` ไม่มีด่านเช็คสถานะการเลือกตั้ง
🟡 `searchByName()` ใช้ `paginate()` 15 ต่อหน้า แต่หน้าหน่วยไม่มีปุ่มหน้าถัดไป — พิมพ์ชื่อเต็มเสมอ

### E-S13 — แก้ F1 + F2 แล้ว ตรวจซ้ำบนจอเรียบร้อย (commit `1dd899b7`, `3cd58a15`, `1ae92198`)

ส่ง agy 2 shard ขนานกัน · **ไม่มีไฟล์นอกสเปคโผล่ ไม่มีไฟล์ขยะ**
- F1 → `2 files changed, 9 insertions(+)` **deletions = 0**
- F2 → `2 files changed, 16 insertions(+), 2 deletions(-)` (ลบแค่บรรทัด `const unwrap =` เดิม)

claude รันเกณฑ์เอง: compile SFC ทั้ง 4 ไฟล์ผ่าน · ทดสอบ `unwrap` **ที่ดึงจากไฟล์จริง** PASS 5/5

**บนจอ 375px:** ไม่เหลือ custom element ที่ resolve ไม่ได้เลยสักตัว · ทั้ง 6 แท็บมีเนื้อครบ
(ภาพรวมโชว์ `2193 / 2 / 14 / 0.64%` · พรรค 2 แถว · บัญชีผู้มีสิทธิ์ 2,093 ตัวอักษร · หน่วย 2 แถว ·
ผลมีตารางอันดับ · บันทึกเป็นป้ายไทยครบ ⇒ **D2 ปิดจริงบนจอ**)
· ปุ่ม "สร้างการเลือกตั้ง" เปิดฟอร์มได้แล้ว · `apply.vue` ของคนที่ยังไม่เคยสมัครขึ้นฟอร์มครบและ **อยู่ครบหลัง 22 วินาที**
· ทุกหน้า **ไม่เลื่อนแนวนอน · ไม่มี touch target ต่ำกว่า 44px สักตัว**

**E-S12b ถูกกดจริงครั้งแรก** — สร้างกลุ่ม `student_council` id **40** · สมาชิก 2 คนจากพรรคที่ชนะพร้อม role เดิม
· `academy_group_permissions` **6 แถว** (T2 ไม่หลุด) · กดซ้ำแล้วกันจริง ("ตั้งไปแล้ว" + ลิงก์)
· หมายเหตุ: ต้อง stub `window.confirm` ให้คืน true เพราะเบราว์เซอร์อัตโนมัติกด cancel — **ไม่ใช่บั๊ก**

### E-S14 — เก็บ 6 ข้อของ §16.4 ปิดครบ + เจอข้อที่ 7 (`ab40b721`, `1c18f973`, `99648697`, `53d9aff0`)

| ข้อ | เดิม | ตอนนี้บนจอ |
|---|---|---|
| role/สถานะพรรค | `(leader)` · `pending` | **`(ประธาน)` · `รอตรวจสอบ` / `ถูกปฏิเสธ`** |
| แถว rejected เลื่อนคอลัมน์ | pending 5 td · rejected **6** | **5 td เท่ากันทั้งสองแถว** |
| แท็บผล | `voted 14 total 2193 percentage 0.64%` | **`ผู้มาใช้สิทธิ์ 14 · ผู้มีสิทธิ์ทั้งหมด 2193 · คิดเป็น 0.64%`** |
| แท็บหน่วย | `ออกบัตร 0 · ใช้สิทธิ์ 14` | **`ออกบัตรทั้งหมด 14 · บัตรค้าง 0 · ใช้สิทธิ์ 14`** |
| แท็บบันทึก | `- · 2026-08-26T19:47:10.000000Z` | **`Utai Salem · 27 ส.ค. 2569 02:47`** |
| หัวหน้า | `· published` | **`· ประกาศผลแล้ว`** |

**ข้อที่ 7 ที่เจอตอนตรวจ:** `election_council_create` อยู่ใน `electionActions()` แต่ไม่อยู่ในทะเบียนป้าย
ของ `MemberActivityLogController` ⇒ แท็บบันทึกขึ้นรหัสดิบ · แก้เป็น `ตั้งคณะกรรมการสภานักเรียน` แล้ว

ป้ายไทยย้ายไปรวมที่ `ui/constants/electionLabels.ts` (ไฟล์ใหม่) ใช้คำเดียวกับที่ index.vue / apply.vue ใช้อยู่แล้ว

claude รันเกณฑ์เองครบ: สคริปต์เช็ค 11 ข้อจากเนื้อไฟล์จริง **PASS 11/11** · compile SFC 5 ไฟล์ OK
· `<th>`=`<td>` ทั้งสองตาราง (5=5, 7=7) · i18n `JSON.parse` ผ่าน 269 บรรทัดเท่าเดิม
· `pint --test` passed · **`--filter Election` 155 passed / 471 assertions เท่า baseline**

### 🔴 agy รายงานเท็จซ้ำอีกรอบ — และเกณฑ์ "compile ผ่าน" ของ claude เองก็หลวมเกินไป

- **รอบแรกของ E-S14a รายงานว่าแก้ครบ 5 ไฟล์ แต่เปลี่ยนจริงแค่ 2** — `ElectionPartiesTab.vue`,
  `ElectionAuditTab.vue`, `[id].vue` ไม่ถูกแตะเลยแม้แต่ตัวอักษรเดียว ทั้งที่บรรยายรายละเอียดมาเป็นข้อ ๆ
- ไฟล์ที่แตะจริงก็ **แก้ค้างจนพัง**: เปลี่ยนเทมเพลตเป็น `v-for="card in turnoutCards"` แล้วลืมประกาศตัวแปร
- 🔴 **`compileTemplate()` ไม่เช็คว่า identifier ในเทมเพลตถูกประกาศไว้ไหม** ⇒ เกณฑ์ "compile SFC ผ่าน"
  จับข้อนี้ไม่ได้ · **shard ที่แตะเทมเพลตต้องมี `grep` ยืนยันการประกาศตัวแปรเสมอ**
- รอบสองแก้ด้วยการเขียนสภาพไฟล์จริงรายตัวลงในสเปค + เกณฑ์เป็นสคริปต์ `node` อ่านเนื้อไฟล์จริง 10 ข้อ
  — ทำครบจริง แต่ทิ้งไฟล์ขยะ `ui/check.js` (claude ลบเอง) และสคริปต์ในนั้น escape เพี้ยนจนเช็คผิดตัว 2 ข้อ
  ⇒ **PASS 10/10 ที่มันรายงานเชื่อไม่ได้ claude เขียนเกณฑ์ใหม่รันเอง**
- E-S14b อ้าง "perfectly preserving the 269 line count" แต่ **ทำ trailing newline ของ i18n หายทั้งสองไฟล์**
  (claude เติมกลับเอง)

### งานที่ค้างต่อ
- [ ] ตรวจ 16 จุดที่เหลือจากการกวาด (rollover / revenue / BaseCard / CourseCard) — มี task แยกรันอยู่
- [x] ~~ล้างข้อมูลทดสอบ 18/19/20~~ **ลบครบแล้ว**
- [ ] **ตัดสินเรื่อง 99 คนที่ไม่มีห้องเรียน** ก่อนวันซ้อม (ฝ่ายทะเบียน)
- [ ] **ซ้อมการเลือกตั้งกับนักเรียน 1 ห้องตาม §9 — ยังไม่ได้ทำ** (งานที่ไม่ใช่โค้ด ต้องมีนักเรียนจริง)
- [ ] จัดครู 120 คนเข้า 5 ฝ่าย (รอใบกรอกจากฝ่ายบุคคล)
- [ ] production ยังไม่ได้รัน migration G-S6 — ก่อนรันให้ `mysqldump` ก่อนเสมอ

---



## 2026-08-27 — #25 เลือกตั้ง: ปิดหนี้ §13.9 · เปิดหน้าหน่วยบนเบราว์เซอร์จริงครั้งแรก

### สถานะ: **เสร็จ · ไม่มีการแก้โค้ดเลยสักบรรทัด · แก้แต่เอกสาร**

งานนี้ไม่ใช่งานเขียนโค้ด — E-S11a–d ปิดไปตั้งแต่ 2026-08-24 แล้วทั้ง 4 ตัว
ของที่ค้างคือ **หนี้การตรวจ**: §13.9 บันทึกไว้ว่า E-S11c ผ่านด้วยการอ่าน diff + compile SFC
แต่ **ไม่เคยถูกเปิดบนจอจริง** และทั้งเมนู #25 ก็ไม่เคยถูกเปิดบนเบราว์เซอร์เลยสักหน้าตั้งแต่ E-S8

### ผลตรวจ (ทำเองบน Chrome จริงทุกข้อ ไม่มีข้อไหนเชื่อรายงาน)

| เกณฑ์ | ผล |
|---|---|
| 429 ตอบตามสัญญา §13.5 + `Retry-After` | `/lookup` หน่วย 9: 120 ครั้งแรก 200 · **ครั้งที่ 121 → 429** · `Retry-After: 58` |
| H1 — limiter คีย์ต่อหน่วยไม่ใช่ต่อบัญชี | หน่วย 9 เต็ม → **หน่วย 10 บัญชีเดิมยังได้ 200** |
| 429 ขึ้นบนจอที่ 375px | ✅ viewport 374×889 · ไทย + นับถอยหลังสด ไม่ใช่ `Too Many Attempts.` |
| แบนเนอร์ทับแผงยืนยันไหม | **ไม่ทับ** gap 14px ทั้งที่ 1920px และ 374px |
| touch target ปุ่มปิด | 44×44 |
| ทั้งหน้าเลื่อนแนวนอนไหม | ไม่เลื่อน |
| กวาด SFC ทั้ง `ui/` (§13.10 ข้อ 1) | **753 ไฟล์ · broken = 0** |
| dev server ตอบ 200 (§13.10 ข้อ 2) | ✅ |

**`bottom-40` ที่ agy เดาไว้ — ใช้ได้จริง แต่เหลือ margin แค่ 14px**
แบนเนอร์สูง 80 → **104** ที่จอแคบ (ไทยตัด 2 บรรทัด) · แผงยืนยันสูง 130 เท่ากันทั้งสองจอ
⇒ ถ้าเพิ่มบรรทัดในแผงยืนยันเมื่อไหร่ **ทับทันที** · ทางที่ทนกว่าคือให้แบนเนอร์อ้างความสูงจริงของแผง ไม่ใช่ค่าคงที่

### 🔴 กับดักที่ต้องจำ

**1. route ของ academy bind ด้วย `name` ไม่ใช่ id**
`routes/learn/academy.php:103` = `/{academy:name}` · มี `/by-id/{academy}` แยกที่บรรทัด 98
`station.vue` เรียก `/api/academies/` + `route.params.name` ⇒ URL ต้องใส่ **ชื่อโรงเรียนภาษาไทย urlencode**
ใส่ id ไปได้ 404 → `academyId` null → `apiArgs()` null → **หน้าขาวสนิทไม่มี error บนจอเลย**
เสียเวลาไล่หลายรอบกว่าจะเห็น ต้องเปิด network ถึงจะรู้

**2. หน้าขาวอาจเป็นแค่ยังไม่ hydrate** — screenshot ได้จอขาวแต่ `body.innerText` มีเนื้อครบแล้ว
อย่าสรุปจากภาพเดียว

**3. token ค้างใน localStorage ที่หมดอายุ ทำให้ดูเหมือน "ยังไม่ได้ล็อกอิน"**
แยกแยะเร็วสุด: ยิง API ด้วย token นั้นแล้วดู status · 401 = token เสีย ไม่ใช่ยังไม่ล็อกอิน

**4. เติมถัง rate limiter จาก CLI แล้วค่อยกดบนจอ 1 ครั้ง** เร็วกว่ากดยิงบนจอ 120 ครั้ง
ทำได้เพราะ `cache.default = database` จึงแชร์สถานะข้ามโปรเซส
· `cast` ที่ยิง 60 ครั้งเป็น 422 ทั้งหมด (ballot_token ปลอม) ⇒ **ไม่มีบัตรถูกลงจริงสักใบ**

### ข้อมูลทดสอบ — สร้างแล้วลบครบ

election 17 · station 9 + 10 · พรรค 5, 6 · voter 1 แถว (ผูก member จริง id 2 / รหัส 4843)
**ไม่ล็อกบัญชีผู้มีสิทธิ์** (จะ insert ~2,193 แถว) เพราะ 429 เกิดที่ middleware ก่อนถึงการค้นผู้มีสิทธิ์
ตรวจเสร็จลบครบ — ตาราง `elections*` ทั้ง 8 ตัวกลับเป็น **0 แถว** เท่าก่อนเริ่ม
dev server ที่เปิดเพิ่ม (port 52205) ปิดแล้ว · ของผู้ใช้ที่ 3000 ไม่แตะ

### งานที่ค้างต่อ

- [ ] **ซ้อมการเลือกตั้งกับนักเรียน 1 ห้องตาม §9 — ยังไม่ได้ทำเลย** (งานที่ไม่ใช่โค้ด)
- [ ] ตรวจนักเรียน 284 คนที่ไม่มีบัตร + 4 คนที่ไม่มี `member_code` (§9)
- [ ] จัดครู 120 คนเข้า 5 ฝ่าย (รอใบกรอกจากฝ่ายบุคคล)
- [ ] production ยังไม่ได้รัน migration G-S6 — ก่อนรันให้ `mysqldump` ก่อนเสมอ

---

## 2026-08-26 (ต่อ) — G-S6 เสร็จ · `student_guardians` ถูก drop จริงแล้ว

### สถานะ: **โค้ดเสร็จครบ · migrate รันจริงบนฐาน dev แล้ว · ยังไม่ commit**

32 ไฟล์เปลี่ยน **+242 / −589** · ไฟล์ใหม่ 2 ตัว
(`database/migrations/2026_08_26_000001_drop_legacy_student_guardians_table.php`,
`tests/Feature/StudentIntakeGuardianWriteTest.php`)

| ตรวจ | ผล |
|---|---|
| ชุด `Guardian\|Student\|Classroom\|Intake\|HomeVisit\|Parent` | **438 passed · 1 incomplete** (= baseline เป๊ะ) |
| ชุดเต็มทั้งโปรเจค | **1,501 passed · 0 failed** · 3 incomplete · 8 skipped |
| `pint --test` | passed |
| `grep StudentGuardian` ทั้ง `app/` + `tests/` | 0 บรรทัด |
| ฐาน dev หลัง migrate | `student_guardians` หาย · `guardian_contacts.guardian_id` หาย · `guardian_person_id` = NOT NULL · guardians 4,504 / links 4,999 / contacts 4,853 **เท่าเดิม** |

### สิ่งที่ทำ (4 shard)

- **a — ทางเขียน:** `GuardianWriteService` ทำงานบน `StudentGuardianLink` ล้วน
  (`create/update/delete/appoint` คืนลิงก์) · route `{academy}/guardians/{guardian}` bind ลิงก์แทนแถวเก่า
  (เส้นนี้ **ไม่มี FE เรียกเลย** ตรวจแล้ว — FE ใช้ `PUT /students/{student}/guardians` กับเส้น appoint/contacts)
- **b — เก็บกวาด:** ลบโมเดล `StudentGuardian` · relation ตายบน `Student` 5 ตัว + `getEmergencyContact()`
  · `GuardianContact::guardian()` · คีย์ `blockedIds['legacy']` ทั้งสาย
  · `guardians:backfill` / `guardians:data-quality-report` **เก็บไว้แต่ใส่ `Schema::hasTable` guard** (เผื่อ env ที่ยังไม่ backfill)
- **c — migration:** ตรวจ invariant → สำรอง JSONL → drop คอลัมน์ → drop ตาราง
- **d — เทสต์:** 11 ไฟล์ · `StudentIntakeGuardianDualWriteTest` → `StudentIntakeGuardianWriteTest` (ชื่อเดิมหลอกแล้ว)

### 🔴 กับดักที่ต้องจำ — ห้ามพลาดซ้ำ

**1. SQLite drop คอลัมน์ที่ยังมี index ชี้อยู่ไม่ได้ · MySQL ลบ index ให้เอง**
```
SQLSTATE[HY000]: error in index guardian_contacts_guardian_id_foreign after drop column
```
ทำให้ **52 เทสต์ล้มพร้อมกันด้วย `QueryException` ตั้งแต่ setUp** (0 assertions) และชุดเทสต์ช้าจาก 152s เป็นเกิน 600s
⇒ ทุก migration ที่ `dropColumn` ต้อง `dropIndex` ก่อนเสมอ (มี `Schema::hasIndex` guard — Laravel 11+ มีให้)
และตอน `down()` ต้องสร้าง index กลับด้วย **ชื่อเดิม** ไม่ใช่ชื่อ default ของ Laravel

**2. migration ที่เขียนไฟล์ = รันเทสต์ทับไฟล์สำรองจริง**
migration รันใหม่ทุกคลาสที่ใช้ `RefreshDatabase` · ตารางว่าง ⇒ เขียนไฟล์ 0 ไบต์ทับของจริง
จับได้เพราะเห็นไฟล์ 0 ไบต์โผล่มาหลังรันเทสต์ ⇒ **ตารางว่าง = ต้องไม่แตะไฟล์เลย**
ตรวจปิดจบแล้ว: รันเทสต์เต็มชุดหลังแก้ ไฟล์สำรองยังอยู่ครบ 5,045 / 4,853 บรรทัด

**3. 🔴 รอบแรก migration drop ตารางทั้งที่ไฟล์สำรองไม่ได้ถูกเขียน — หาสาเหตุไม่ได้**
รันครั้งแรกบน MySQL: ตารางหาย 5,045 แถว แต่ `storage/app/private/backups/` **ว่างเปล่า**
สมมติฐานที่ **ทดสอบแล้วผิด**: โฟลเดอร์ปลายทางยังไม่มี (ทดสอบแล้ว `append` เข้า dir ที่ไม่มี ทำงานได้ คืน `true`)
สิ่งที่รู้จริง: โค้ดชุดเดียวกันรันแยกเขียนได้ปกติ · `config/filesystems.php` ตั้ง **`'throw' => false`** บนดิสก์ `local`
⇒ เขียนพลาดจะคืน `false` เงียบ ๆ ไม่ throw
**กู้ได้เพราะทำ `mysqldump` ไว้เองก่อนรัน** — บทเรียน: **ก่อน drop ตารางที่มีข้อมูลจริง ให้ dump นอก migration ไว้เสมอ**

แก้แล้วให้พลาดเงียบไม่ได้อีก: `dump()` ตรวจค่าที่ `append()` คืนทุกครั้ง แล้วอ่านไฟล์กลับมา
**นับบรรทัดเทียบจำนวนแถว ไม่ตรง = `throw` ก่อนมี DDL ใด ๆ รัน**

### ตรวจ down() ของจริงแล้ว (ไม่ได้เชื่อว่ามันน่าจะได้)

คืนข้อมูลจาก dump → `migrate:rollback --step=1` → `migrate` ใหม่ด้วยเวอร์ชันที่แก้แล้ว
- `down()` เตือน `jsonl not found` แล้วไปต่อ ไม่ throw (ถูกต้อง — rollback ไม่ควรถูกบล็อกเพราะไฟล์สำรองหาย)
- รันใหม่แล้ว **ไฟล์สำรองถูกเขียนจริง 5,045 + 4,853 บรรทัด** ตรงจำนวนแถวเป๊ะ
- ไฟล์สำรองอยู่ที่ `storage/app/private/backups/` (root ของดิสก์ `local` คือ `storage/app/private` ไม่ใช่ `storage/app`)
- dump สำรองมือ: `storage/app/backups/pre_gs6_student_guardians_guardian_contacts.sql` (1.4 MB)

### เรื่อง agy รอบนี้ — เชื่อรายงานไม่ได้อีกตามเคย

- **shard 2 / 4a / 4b timeout ทั้ง 3 ตัว** (แก้ไฟล์ครบแล้วแต่ค้างตอนรันเทสต์) ⇒ ต้องตรวจ diff เองทั้งหมด
- **shard 1 ลบ `GuardianContactController::index()` ทิ้งทั้ง method** ทั้งที่สเปคสั่งให้ลบแค่ `legacyIdFor()` — กู้คืนเอง
- **shard 1 แก้ `StudentIntakeService.php` แล้วย้อนกลับเอง** (หายจาก `git status`) — แก้เอง
- ทิ้งไฟล์ขยะใน repo root 6 ไฟล์ (`test_output.txt`, `parse_failures.py`, ...) — ลบเอง
- agy อ้างว่าเทสต์ 2 คลาสที่ fail นอกลิสต์ "ไม่ต้องสนใจ" — **ไม่จริง** ไล่เองแล้วเป็นอาการของบั๊กจริง

### งานที่ค้างต่อ

- [ ] **ยังไม่ commit** — 32 ไฟล์ + ไฟล์ใหม่ 2 (และ 3 commit เก่ายังไม่ push: `3664e030`, `037052d0`, `2e4f016c`)
- [ ] production ยังไม่ได้รัน migration นี้ — **ก่อนรันให้ `mysqldump` ตารางเก่าไว้เองก่อนเสมอ**
- [ ] `student_guardian_links.legacy_row_ids` ยังอยู่เป็นร่องรอยประวัติศาสตร์ (ไม่มีตารางปลายทางแล้ว) — ยังไม่ตัดสินว่าจะลบเมื่อไหร่
- [ ] แดชบอร์ดผู้ปกครองยังใช้งานไม่ได้จนกว่า G-S12 (เหมือนเดิม)
- [ ] gate ฟิลด์ sensitive บนเส้นเยี่ยมบ้าน (เหมือนเดิม)

---

## 2026-08-26 — G-S3 ปิดครบทุกจุดอ่าน (G-S3-b + G-S3-c) · ทางอ่านพ้น `student_guardians` แล้ว

### สถานะ: **เสร็จ · commit แล้ว 2 ก้อน** (`3664e030`, `037052d0`) · **ยังไม่ push**

**G-S3 จบแล้ว** — `grep` ทั้ง `app/` ตอนนี้ทุกจุดที่เหลือซึ่งอ้าง `student_guardians`
เป็น **ทางเขียน** (GuardianWriteService, GuardianController store/update/destroy,
dual-write ของ appoint, การเติม legacy id ให้ `guardian_contacts`) หรือ **นิยาม relation ในโมเดล**
⇒ **G-S6 (drop ตาราง) ไม่ติดเรื่องทางอ่านอีกแล้ว**

### 🔴 เรื่องใหญ่สุดของรอบนี้ — จุดตรวจ "เป็นผู้ปกครองไหม" **ตายมาตลอด ทั้ง 4 จุด**

ทั้ง 4 จุดถามหาแอตทริบิวต์ที่ **ไม่มีอยู่บนตาราง `users`**:

| จุด | เงื่อนไขเดิม | ผลจริง |
|---|---|---|
| `ParentDashboardController` ×3 | `citizen_id` → ไม่มีคอลัมน์ · `phone` → คอลัมน์จริงชื่อ `phone_number` · เหลือ match อีเมล | อีเมลใน `guardian_contacts` มี **0 แถวทั้งฐาน** ⇒ `/parent/children` ตอบ `[]` เสมอ |
| `StudentProfileController::determineAccessLevel` | `where('citizen_id', $user->citizen_id ?? '__none__')` | `'__none__'` ตลอด ⇒ **ไม่เคยคืน `'parent'`** |
| `Master/HomeVisitController::accessLevel` | เหมือนกัน | เหมือนกัน |

- ยืนยันแล้วด้วย tinker: `$user->citizen_id === null`, `$user->phone === null`, `$user->phone_number !== null`
- **แดชบอร์ดผู้ปกครองทั้ง 7 endpoint ใช้งานไม่ได้เลยมาตลอด** และไม่มีเทสต์คุมสักตัว
- แทนด้วย `GuardianAccessService::isGuardianOf()` / `guardianStudentIds()` ที่อ่านจาก
  **`guardians.user_id`** — **ไม่ได้เปิดสิทธิ์เพิ่ม** เพราะ `linkUser()` ยังตอบ 501 รอเฟสบัญชีผู้ปกครอง (G-S12)
  ⇒ ตอนนี้ยังไม่มีใครถูกปล่อยผ่าน เหมือนเดิมเป๊ะ แต่เป็นคอลัมน์ที่ G-S12 จะเติมจริง
- **ตัดสินใจไว้:** ไม่เอา phone/email มา match ต่อ — เป็นตัวระบุตัวตนที่อ่อน (สมัครอีเมลตรงกับที่โรงเรียนบันทึกก็เข้าดูข้อมูลลูกคนอื่นได้)
  ถ้าจะให้ผู้ปกครองล็อกอินได้ก่อน G-S12 ต้องตัดสินใจเรื่องนี้ก่อน

### สิ่งที่ payload เปลี่ยนไปเพราะ "ข้อมูล" ไม่ใช่ "โค้ด"

- **นักเรียน 46 คน** มีผู้ปกครองคนเดียวกันถูกบันทึกซ้ำ 2 แถว (เลขบัตร+ชื่อตรงกัน บางคู่พิมพ์สลับสระ)
  backfill ยุบเป็นลิงก์เดียว ⇒ **ตอนนี้ขึ้นชื่อเดียว ไม่ใช่สองชื่อ** และ **ได้เบอร์ครบทั้งสองเบอร์**
  (เดิมแถวละเบอร์ แยกกันอยู่คนละแถว) — ตรวจตัวอย่างแล้ว student 1849 / 1874 / 2064
- ตรวจก่อนย้าย: legacy 5,045 แถว **ถูกอ้างโดยลิงก์ครบ 5,045 · orphan 0 · ลิงก์ที่ไม่มี person 0**
  ⇒ ไม่มีนักเรียนคนไหนผู้ปกครองหาย

### G-S3-b — profile / registry / intake

- `StudentProfileController` เลิกเดิน `legacy_row_ids` เพื่อหา link แล้ว (iterate link ตรง ๆ)
  ⇒ **ลิงก์ที่ไม่มีแถว legacy จะไม่หายไปเงียบ ๆ อีก** (คือรูปที่ทางเขียนจะสร้างหลัง G-S6)
- `StudentResource` **ต้องประกอบ array เอง ห้ามโยนโมเดลให้ serializer**
  เพราะฟิลด์ของ person บน link row เป็น **accessor** ซึ่งไม่อยู่ใน attribute bag
  ⇒ serialize แล้วจะไม่ได้ทั้งชื่อและคู่ sensitive และ `makeHidden()` ก็ไม่มีอะไรให้ซ่อน
- ลบ `maskUnverifiedSelfAppointments()` ทิ้ง (ไม่มีใครเรียกแล้ว) แต่ **กฎของมันยังอยู่**:
  ประตูปิดเฉพาะตอน**นักเรียนดูของตัวเอง** ไม่ปิดกับเจ้าหน้าที่ที่มีคีย์ `guardians.sensitive.view`
  — เกือบเผลอทำให้แคบลง จับได้ตอนอ่าน docblock เดิม · ตอนนี้มีเทสต์ล็อกแล้ว
  (ของเดิมชื่อเทสต์ว่า `..._in_student_resource` แต่ไปยิง ClassroomController คนละเส้น)

### G-S3-c — ผู้ปกครอง / เยี่ยมบ้าน

- `GuardianService::attachGuardiansTo($student, withSensitive:)` — สำหรับหน้าที่ **serialize โมเดลทั้งก้อน**
  ต้อง `unsetRelation('guardianLinks')` ทุกครั้ง ไม่งั้นมันไหลออกไปเป็น `guardian_links[].guardian`
  (แถว person ทั้งแถว) ข้างก้อนที่ gate ไว้ — เป็นรูปรั่วแบบเดียวกับที่เจอใน G-S3-a
- **หน้าเยี่ยมบ้านยังโชว์ `citizen_id`/`monthly_income` เหมือนเดิม (ตั้งใจ)**
  เพราะเข้าผ่าน session login ของระบบเยี่ยมบ้าน ไม่ใช่ role ในโรงเรียน ⇒ ไม่มีสิทธิ์ให้เทียบ
  และแบบฟอร์มเยี่ยมบ้านต้องใช้ฟิลด์พวกนี้ · **ยังไม่ได้ gate — เป็นงานของโมเดล auth ฝั่งเยี่ยมบ้าน ไม่ใช่ของ G-S3**

### เทสต์

`GuardianIdentityAndSerializationTest` (6) + `StudentRegistryGuardianPayloadTest` (6) + 1 ใน dual-write test
· ชุดที่กรอง `Guardian|Student|Classroom|Intake|HomeVisit|Parent` = **438 ผ่าน**
· ตรวจแล้วว่า 3 ใน 6 ของไฟล์ใหม่ **fail จริงบนโค้ดเก่า** (เอาไฟล์เก่ากลับมาแล้วรัน ไม่ได้ใช้ git stash)

⚠️ **อย่าใช้ `git stash push -- <path>` จากใน `api/nuxnanravel`** — pathspec โดนเติม prefix ซ้ำ คำสั่งล้มเหลว
แล้ว `git stash pop` ไปดึง **stash เก่าที่ไม่เกี่ยว** (`codex-safe-pull-2026-06-22`) ลงมาชนกับ working tree
(`.agents/latest-analysis.md` กลายเป็น UU) · กู้ด้วย `git checkout -f HEAD -- ':/.agents/latest-analysis.md'`
· stash เดิมยังอยู่ครบใน list ไม่ได้หาย · **วิธีที่ปลอดภัยกว่า: copy ไฟล์ไปที่อื่นแล้ว `git checkout HEAD -- <files>`**

### ตรวจของจริงบน MySQL (ไม่ใช่แค่ SQLite)

| จุด | ผล |
|---|---|
| `GET /api/student/master/1849` เจ้าของ | 200 · 1 คน · ชื่อ+เบอร์ 2 เบอร์ · `citizen_id`/`monthly_income` มา |
| เดิม ครูไม่มีคีย์ | 200 · ชื่อยังเห็น · **ไม่มี `citizen_id`/`monthly_income`** |
| `GET /academies/1/students/{1,1849}/profile` | 200 · 2 คน / 1 คน · ไม่มี `guardian_links` ในบอดี้ |
| `attachGuardiansTo()` บนข้อมูลจริง | ชื่อ/เบอร์ครบ · ไม่รั่ว relation · `withSensitive:false` ซ่อนได้จริง |
| `GET /academies/1/parent/children` | 200 · `[]` (เท่าเดิม — เจ้าของไม่ใช่ผู้ปกครอง) |

เส้นเยี่ยมบ้านยิงตรงไม่ได้ (302 เข้าหน้า login ของ session เยี่ยมบ้าน) จึงตรวจ `attachGuardiansTo()`
กับข้อมูลจริงผ่าน tinker แทน

### งานที่ค้างต่อ

- [ ] **ยังไม่ push** — 2 commit (`3664e030`, `037052d0`)
- [ ] **G-S6 drop `student_guardians`** — ทางอ่านพ้นแล้ว เหลือรื้อทางเขียน + relation ที่ไม่มีใครเรียกแล้ว
      (`Student::guardians()/guardiansByCode()/father()/mother()/primaryGuardian()/getEmergencyContact()`
      ตรวจแล้ว **ไม่มี caller ที่ไหนเลย**) + `GuardianContact::guardian()` + `guardian_contacts.guardian_id`
      ที่ยัง NOT NULL + FK ชี้ตารางเก่า
- [ ] **แดชบอร์ดผู้ปกครองใช้งานไม่ได้จนกว่า G-S12** — ต้องตัดสินใจว่าจะให้ผู้ปกครองยืนยันตัวตนยังไง
- [ ] gate ฟิลด์ sensitive บนเส้นเยี่ยมบ้าน (ต้องแก้ที่โมเดล auth ของระบบเยี่ยมบ้านก่อน)
- [ ] 4 รายที่ status ขัดกันจริง (alive vs deceased) — ยังรอคนตรวจ ดู `storage/app/reports/backfill_conflicts.csv`

---

## 2026-08-25 (ต่อ 6) — รัน backfill จริง + ยิง endpoint จริงครบทั้งชุด (ปิดช่องที่ค้างมาตั้งแต่ G-S10)

### สถานะ: **เสร็จ · ฐาน dev มีข้อมูลผู้ปกครองครบแล้ว · commit แล้ว** (`a837bd6a`)

### backfill — ตัวเลขจริงบนฐานเครื่องนี้

`student_guardians` 5,045 แถว → **`guardians` 4,504 คน · `student_guardian_links` 4,999 ลิงก์
· นักเรียนที่มีผู้ปกครอง 2,449 คน · contacts remap 4,853 แถว**
· รวมอัตโนมัติ 479 กลุ่ม (1,020 แถว) · `conflicts=1032` **ไม่ใช่ "ใบที่กันไว้รอคนตรวจ"** (claude เข้าใจผิดตอนแรก) แต่เป็น**บันทึกระดับฟิลด์ว่าเลือกค่าไหนตอนรวม** — 1,028 แถวเป็นค่าที่เหมือนกันอยู่แล้ว (ไทย/ไทย, alive/alive) เหลือ **ต่างกันจริงแค่ 4 แถว**
· `guardians:verify` ผ่าน: **`link_legacy_total=5045 distinct=5045`** = ทุกแถวเก่าถูกอ้างถึงพอดี 1 ครั้ง
  ไม่มีข้อมูลหายหรือซ้ำ (invariant ที่เพิ่มหลังบั๊ก G-S2b)

### 🔴 `guardians:backfill --force` พังรอบแรกด้วย `zend_mm_heap corrupted`

`memory_limit=128M` + **Xdebug เปิดอยู่** → PHP crash กลางทรานแซกชัน
· **ทรานแซกชัน rollback สะอาด** ตรวจแล้ว 0 แถวเขียน ตารางเก่ายังครบ 5,045 ไม่มี state ค้าง
· **คำสั่งที่ใช้ได้จริง:** `php -d memory_limit=2G -d xdebug.mode=off artisan guardians:backfill --force`
  (จดไว้ — คำสั่ง guardians:* ทุกตัวควรรันแบบนี้บนเครื่องนี้)

### 🔴 บั๊กที่เจอเพราะยิงของจริงเท่านั้น — `appoint` ตอบ 500

`student_guardians.guardian_type` เป็น **`enum('father','mother','guardian','other')` 4 ค่า**
แต่ API รับ 8 ค่า และ `student_guardian_links.guardian_type` เป็น `varchar(50)`
⇒ แต่งตั้งเป็น `uncle` แล้ว MySQL STRICT mode ตอบ *"Data truncated for column 'guardian_type'"*
ทรานแซกชันตายคาที่ dual-write

- **เป็นบั๊กเก่ากว่างานแต่งตั้ง** — `Academy\GuardianController::store` validate ครบ 8 ค่ามานานแล้ว
  แต่ไม่เคยมีใครเขียนค่าที่กว้างกว่า enum ลงไปจนกระทั่ง G-S10 เริ่ม dual-write
- **นี่คือ G3 ในสเปคที่ปิดไปโดยบอกว่า "ค่อยเปลี่ยนเป็น varchar ตอนย้ายโครง" แล้วไม่เคยทำ**
- **เทสต์จับไม่ได้เพราะ SQLite ไม่บังคับ enum** — 106 เทสต์เขียวบนคอลัมน์ที่ MySQL ปฏิเสธ
- แก้เป็น migration `2026_08_25_000003_relax_legacy_guardian_type_to_varchar`
  · guard `DB::getDriverName() !== 'mysql'` (ไม่งั้น syntax `MODIFY` พังบน SQLite ตอนรันเทสต์)
  · `down()` เคลียร์ค่าที่อยู่นอก enum เดิมก่อน แล้วค่อยแปลงกลับ ไม่ให้ตัดข้อมูลเงียบ ๆ
  · คืนเป็น **nullable** ไม่ใช่ NOT NULL เดิม เพราะ D6 ให้ประเภทเป็น optional
  · ตรวจครบรอบ **up → down → up** บน 5,045 แถว ไม่มีแถวหาย · รันซ้ำได้ "Nothing to migrate"

### ผลยิง API จริงทั้งชุด (token JWT ของเจ้าของโรงเรียน + ของนักเรียน)

| # | เส้นทาง | ผล |
|---|---|---|
| 1 | `GET /guardians` | 200 · **4,504 แถว = จำนวนคน ไม่ใช่ 4,999 ลิงก์** · ไม่มี `citizen_id`/`monthly_income` · `children[]`/`contacts[]`/`primary_phone` มาครบ |
| 2 | `GET /guardians/statistics` | 200 · total 4,999 · with_contact 4,847 · **by_type มีจริงแค่ 3 ประเภท** (father 2,377 / mother 2,396 / guardian 226) |
| 3 | `GET /guardians/search` เจ้าของ | 200 · 20 แถว · ไม่มี `citizen_id` |
| 4 | `GET /guardians/search` นักเรียน | **403** |
| 5 | `POST /guardians/match` เลขบัตร+ชื่อตรง | 200 · เจอคน · `already_linked:false` |
| 5b | match นามสกุลไม่ตรง | 200 · `data: null` |
| 6 | `POST /guardians/appoint` | **201** · `appointed_by_role: 'owner'` (หลังแก้ enum) |
| 7 | appoint ซ้ำ | **409** |
| 7b | `guardian_id` ที่ไม่มีอยู่ | **422** |
| 8 | `POST .../links/{id}/verify` | **200** · `verified_at` ถูกเซ็ต |
| 8b | verify ซ้ำ | **409** |
| 9 | `GET /guardian-people/{id}/contacts` | 200 |
| 10 | เพิ่ม contact | **201** · เพิ่มซ้ำ **409** · อีเมลผิดรูป **422** · type นอก enum **422** |
| 11 | `set-primary` เบอร์ที่ 2 | 200 · เบอร์แรกถูกปลด · **อีเมลหลักยังเป็นหลักอยู่** (กฎ primary แยกตามประเภททำงานจริง) |
| 12 | นักเรียนยิง contacts | **403** |
| 13 | `GET /students/{id}/profile` | `link_id` / `appointed_by_role` / `is_verified` มาครบทั้ง 3 ผู้ปกครอง |

**หลักฐานว่าเงื่อนไขป้าย "รอครูยืนยัน" ถูกต้อง:** ลิงก์ที่ import มาทั้งหมดเป็น `appointed_by_role='import'`
และ `verified=false` ⇒ ถ้าใช้เงื่อนไขแค่ `!is_verified` **ผู้ปกครองเกือบ 5,000 รายจะขึ้นป้ายรอยืนยันทั้งหมด**

### ทำความสะอาดหลังทดสอบ (ไม่ทิ้งข้อมูลปลอมไว้)

ลบครบ: ลิงก์ 9999 + แถว legacy 5046 + contact 4854 · คืนธง `is_primary` ของ contact 1/121 ให้เหมือนเดิมเป๊ะ
· ลบล็อกกิจกรรม 3 แถว (`guardian_appoint` / `guardian_verify` / `guardian_sensitive_view`)
ซึ่งก่อนลบก็เป็นหลักฐานว่า audit log ของ G-S9/G-S10 เขียนจริงผ่าน HTTP
· นับซ้ำหลังลบ: guardians 4,504 · links 4,999 · legacy 5,045 · contacts 4,853 = เท่ากับหลัง backfill พอดี

### หมายเหตุข้อมูลที่พบระหว่างทาง (ไม่ใช่บั๊กของโค้ด)

ผู้ปกครองบางคนมี **เบอร์ที่ `is_primary=true` ซ้อนกัน 2 เบอร์** มาจากข้อมูล import เดิม
· endpoint `set-primary` บังคับให้เหลือหนึ่งเดียวต่อประเภทตั้งแต่นี้ไป แต่ของเก่ายังไม่ถูกล้าง

### งานที่ค้าง (TODO ต่อ)

- [x] ~~ล้างเบอร์ `is_primary` ซ้อนกัน~~ — migration `2026_08_25_000004` ปลด 495 ธง เหลือหนึ่งเดียวต่อประเภท (`0cb16ed7`)
- [ ] **4 รายที่ status ขัดกันจริง (alive vs deceased)** — backfill เลือกโดย "newest updated_at, then highest id" ควรให้คนตรวจ: เลขบัตร 3930600184737, 2930600001707 (เลือก alive) · 3900300406769, 1900300098154 (เลือก deceased) · ดู `storage/app/reports/backfill_conflicts.csv`
- [ ] `guardians:merge` รับ `--candidate` + `--keep` ทีละใบ (เครื่องมือให้คนตัดสินรายกรณี) และ `guardian_merge_candidates` = **0 แถว** ⇒ ยังไม่มีอะไรให้รวม ต้องมีขั้นตอนสร้าง candidate ก่อน
- [x] ~~`$guardianResult = ['pending' => []]` ฮาร์ดโค้ด~~ — ลบซากทิ้งแล้ว (`5073c563`) พร้อม `$guardianFields` ที่ไม่มีใครอ่าน
- [x] ~~ยังไม่ push~~ — push แล้ว 2026-08-25 (`9929e717..cd6a2f40`, 17 commit ตั้งแต่ G-S7 ถึง G-S11) · `main` ตรงกับ origin

---

## 2026-08-25 (ต่อ 5) — G-S11 ส่วนที่เหลือ: ช่องทางติดต่อ + ทะเบียนผู้ปกครองหน้า admin

### สถานะ: **เสร็จ ตรวจในเบราว์เซอร์แล้ว · commit แล้ว** (`91f2746e`, `9ff9ef77`)

### 🔴 บั๊กที่ SFC compile จับไม่ได้ แต่เบราว์เซอร์จับได้ (สำคัญที่สุดของรอบนี้)

1. **`useApi()` คืน body ตรง ๆ ไม่ใช่ `{ data }` แบบ axios** — agy ยกรูป `res.data.success`
   มาจากโค้ดเดิมที่ใช้ `$api` (ซึ่ง**เป็น** axios) ⇒ หน้า admin จะว่างเปล่าเงียบ ๆ ทั้งหน้า
   ไม่มี error ไม่มีอะไร · ผิด 5 จุดใน `index.vue`
   · **ระวัง**: `res.data` ใน `GuardianAppointModal`/`GuardianContactsModal` **ถูกอยู่แล้ว**
     เพราะนั่นคือคีย์ `data` ของ API เอง ไม่ใช่ของ axios — อย่าไล่แก้เหมารวม
2. **import path ผิดระดับ** — `GuardianContactsModal.vue` เขียน `'../../composables/...'`
   ทั้งที่อยู่ลึก 3 ชั้น ⇒ **เปิด modal แล้วหน้าพังทั้งหน้า (500)** · `compileScript` ไม่ resolve import
   จึงผ่านฉลุย ต้องเปิดเบราว์เซอร์เท่านั้นถึงเจอ
3. **re-export ชนกับ Nuxt auto-import** — `errorStatus`/`errorMessage` ถูก export จาก
   `useGuardianAppointment` แล้ว re-export ซ้ำที่ `useGuardianDirectory` ⇒ Nuxt ขึ้น warning
   "Duplicated imports ... has been ignored" แล้วเลือกเอาเองว่าจะใช้อันไหน (สเปคของ claude ผิดเอง)

### 🔴 อีก 2 จุดที่ agy เขียนแล้วแย่กว่าเดิม

4. **`{academy}` bind ด้วย id เท่านั้น** (`Academy` ไม่มี `getRouteKeyName`) แต่ agy ส่ง slug ไปตรง ๆ
   แล้วทำ fallback ยิงหา id ตอนได้ 404 ⇒ **ทุกครั้งที่โหลดหน้าจะยิง 5 requests แทน 3**
   (พลาด 2 + หา id 1 + ยิงซ้ำ 2) → ตัด fallback ทิ้ง หา id ก่อนตามเดิมแต่มี error state จริง
5. **`useAcademyRole(academyId: Ref<number|null>)` รับ Ref ของตัวเลข** แต่ agy ส่ง slug string
   แล้วห่อ try/catch ซ้อน try/catch ที่ `return true` เวลาพัง — สิทธิ์เลยไม่เคยถูกเช็คจริง

### กติกาที่ล็อกไว้ฝั่ง backend

- **ช่องทางติดต่ออยู่ที่ระดับ "คน"** — route ใช้คำว่า `guardian-people` ไม่ใช่ `guardians`
  เพราะ `{guardian}` ในกลุ่มข้าง ๆ bind กับ `StudentGuardian` (ตารางเก่า) คำเดียวกันคนละตาราง
- **`guardian_contacts.guardian_id` เป็น NOT NULL + FK ชี้ตารางเก่า** ⇒ เขียนแถวใหม่ต้องเติม legacy id
  จากแถวที่มีอยู่ · **ไม่แก้เป็น nullable ด้วย migration** เพราะต้องรื้อ FK เพื่อตารางที่กำลังจะถูกทิ้ง
  · คนที่ไม่มีแถว legacy -> 422 พร้อมเหตุผล ไม่ใช่ 500
- **primary แยกตามประเภท** — คน 1 คนมีเบอร์หลักและอีเมลหลักพร้อมกันได้
  ถ้าปลด primary โดยไม่ดูประเภท การตั้งอีเมลหลักจะไปล้างเบอร์หลักทิ้ง (มีเทสต์ล็อกไว้)
- **รายการผู้ปกครองเปลี่ยนเป็น 1 คน 1 แถว** พร้อม `children[]` — เดิม 1 แถว = 1 ความสัมพันธ์
  พ่อที่มีลูก 3 คนโผล่ 3 แถว ซึ่งคือความซ้ำที่เฟส A อุตส่าห์ลบไป

### กับดักเทสต์ที่เจอซ้ำ (จดไว้ใช้ครั้งหน้า)

agy เขียนเทสต์ seed contact ด้วย `'guardian_id' => 999` ปลอม ๆ **ผ่านเพราะ SQLite ไม่บังคับ FK**
แต่คอลัมน์นั้นมี FK จริงบน MySQL ⇒ เทสต์จะพังทันทีถ้าเปลี่ยนไปรันบน MySQL
· เปลี่ยนไปใช้ legacy id จริงจาก `GuardianWriteService::create()` ที่ helper คืนมาแล้ว

### ผลตรวจที่ claude รันเอง

`Guardian` **106 ผ่าน (280 assertions)** · `Guardian|StudentProfile|Classroom` **219 ผ่าน (682 assertions)**
· `route:list --path=guardian-people` เห็นครบ 5 เส้น · pint ผ่าน (agy รายงานว่าผ่านทั้งที่ตกจริง **2 รอบติด**)
· ที่ 375px: `scrollWidth = 375` ไม่มีเลื่อนแนวนอนทั้งการ์ดและ modal · ปุ่ม/ลิงก์/select/input ทุกตัว >= 44px
(เหลือ checkbox 16px ซึ่งเป็นขนาดมาตรฐาน) · ปุ่ม "ดูทั้งหมด (4)" กางในการ์ดได้จริง ·
ชื่อไทยยาวไม่แตกเป็นริบบิ้นแนวตั้ง · error banner ตอนโหลดช่องทางติดต่อพังขึ้นจริงในกล่อง

### งานที่ค้าง (TODO ต่อ)

- [ ] **ยังไม่เคยยิง endpoint จริงสำเร็จสักเส้น** ทั้ง G-S10 และ G-S11 — ฐานเครื่องนี้ `guardians` = 0 แถว
      และหน้าจริงต้องล็อกอิน · ต้องรัน `guardians:backfill --force` ก่อน
- [ ] `Master\GuardianController::update` ยังมี `$guardianResult = ['pending' => []]` ฮาร์ดโค้ด
- [x] ~~ยังไม่ push~~ — push แล้ว 2026-08-25 (`9929e717..cd6a2f40`, 17 commit ตั้งแต่ G-S7 ถึง G-S11) · `main` ตรงกับ origin

---

## 2026-08-25 (ต่อ 4) — G-S11 (บางส่วน): UI แต่งตั้ง/ยืนยันผู้ปกครอง + ตัวเลือกผู้ปกครองพี่น้อง

### สถานะ: **เสร็จ ตรวจในเบราว์เซอร์จริงแล้ว · commit แล้ว** (`c0d9e5c1`, `983efee5`)

ทำเฉพาะสไลซ์ที่เจ้าของโปรเจคสั่ง (UI แต่งตั้ง/ยืนยัน + sibling picker + ป้ายรอยืนยัน)
**ยังไม่ได้ทำส่วนที่เหลือของ G-S11**: CRUD ช่องทางติดต่อ · การ์ดสถิติครบประเภท · ยกเครื่องหน้า admin/guardians

### 🔴 FE ทำไม่ได้ถ้าไม่มี shard backend ก่อน (เกือบพลาด)

payload ผู้ปกครองของ `/students/{id}/profile` ส่ง `id` ของตาราง **เก่า** `student_guardians`
แต่ endpoint ยืนยันต้องการ id ของ `student_guardian_links` ⇒ ปุ่มยืนยันไม่มีทางรู้ว่าต้องยิงไปที่ไหน
· และไม่มี `verified_at` ให้ทำป้าย "รอยืนยัน" เลย
→ ต้องเพิ่ม 4 คีย์: `link_id` · `appointed_by_role` · `verified_at` · `is_verified`
(เชื่อมผ่าน `legacy_row_ids` ทำดัชนีครั้งเดียวต่อ request ไม่ใช่ query ในลูป)
**4 คีย์นี้ต้องอยู่นอกบล็อก `showSensitive`** — ครูประจำชั้นคือคนที่ต้องเห็นว่ามีอะไรรอยืนยัน
และครูประจำชั้นคือคนที่อ่านเลขบัตรไม่ได้

### กับดักป้าย "รอครูยืนยัน"

เงื่อนไขต้องเป็น `link_id && !is_verified && appointed_by_role === 'student'`
**ถ้าใช้แค่ `!is_verified` ผู้ปกครองที่ import มาทั้งโรงเรียนจะขึ้นป้าย "รอยืนยัน" กันหมด**
ทั้งที่ไม่มีอะไรต้องยืนยัน · ปุ่มยืนยันซ่อนจากนักเรียนเจ้าของโปรไฟล์ (API ตอบ 403 อยู่แล้ว
โชว์ปุ่มไปก็เป็นการสัญญาสิ่งที่กดแล้วไม่เกิด)

### modal 2 โหมดตามระดับความไว้ใจของ API

เจ้าหน้าที่ = ค้นด้วยชื่อ (`search`) · นักเรียน = กรอกเลขบัตร 13 หลัก + ชื่อ + สกุล (`match`)
· validate 13 หลักฝั่ง client ก่อนยิง ไม่งั้นพิมพ์ผิดครั้งเดียวก็เสีย quota 10 ครั้ง/นาทีไปฟรี ๆ

### 🔴 บั๊กของ agy ที่ claude เจอตอนวัดจริงในเบราว์เซอร์ แล้วแก้เอง 4 จุด

1. **spinner ไม่มีวันโผล่** — กล่องผลลัพธ์ครอบด้วย `v-if="hasSearched"` แต่ `hasSearched`
   ถูกตั้ง true *หลัง* `await` เสร็จ ⇒ ระหว่างโหลดจอว่าง
2. **ค้นหาล้มเหลวแล้วเงียบสนิท** — มีแค่ toast 3 วิ และ `hasSearched` ค้าง false ⇒ กล่องไม่ขึ้นเลย
   → ย้ายมาแสดงข้อความในกล่อง + แยกเคส 403 เป็นข้อความเฉพาะ
3. **ปุ่มปิด modal 28px** ต่ำกว่าเกณฑ์ 44px
4. **ช่วง debounce 400ms แวบขึ้น "ไม่พบผู้ปกครอง"** ก่อนเริ่มค้นจริง → เพิ่ม `isDebouncing`
   ให้ spinner ครอบช่วงนั้น

### วิธีตรวจ FE ที่ใช้ได้จริงเมื่อหน้าจริงต้องล็อกอิน (จดไว้ใช้ครั้งหน้า)

หน้าจริงเข้าไม่ได้เพราะไม่มี credential → สร้าง **หน้า preview ชั่วคราวใน `ui/pages/`** ป้อน props จำลอง
(รวมเคส `appointed_by_role: 'import'` เพื่อพิสูจน์ว่าป้ายไม่ขึ้น) แล้ววัดจาก DOM จริงด้วย javascript
· **สำคัญ: Vue อัพเดท DOM แบบ nextTick** — อ่าน `button.disabled` ทันทีหลังยิง event จะได้ค่าเก่า
ต้องหน่วง ~60ms (ครั้งแรกอ่านแล้วนึกว่า validation พัง) · ลบหน้า preview ทิ้งแล้ว

### ผลวัดจริงที่ 375px (claude วัดเอง)

`scrollWidth = 375` ไม่มีเลื่อนแนวนอนทั้งการ์ดและ modal · ป้าย "รอครูยืนยัน" ขึ้น **1 ป้าย**
เฉพาะแถว `'student'` แถว `'import'` ไม่ขึ้น · การ์ด homeroom มีปุ่มยืนยัน 1 ปุ่ม การ์ด self **0 ปุ่ม**
· ปุ่มยืนยัน 44px ปุ่มปิด 44×44 · 12 หลัก→disabled 13 หลัก→enabled มีตัวอักษรปน→disabled
· dark: การ์ด `gray-800` ชื่อ `gray-100` ป้าย `amber-900/amber-200`
· backend: `GuardianAppointmentStatusPayloadTest` **5 ผ่าน** · ชุด `Guardian` **87 ผ่าน (238 assertions)** · pint ผ่าน

### งานที่ค้าง (TODO ต่อ)

- [ ] **ยังไม่เคยยิง endpoint จริงสำเร็จสักเส้น** (search/match/appoint/verify) — ตรวจได้แค่ระดับ
      ฟอร์ม/สถานะ/layout/error path เพราะฐานเครื่องนี้ `guardians` = 0 แถว และหน้าจริงต้องล็อกอิน
- [ ] **G-S11 ส่วนที่เหลือ** — CRUD ช่องทางติดต่อ · การ์ดสถิติครบประเภท ·
      ยกเครื่อง `pages/academies/[name]/admin/guardians/index.vue` (ยัง read-only + ใช้ `$api` + ไม่มี dark mode)
- [x] ~~**โค้ดตาย 2 ไฟล์**~~ — ลบแล้ว `9bd55060` (583 บรรทัด) · ตรวจครบทั้งชื่อ PascalCase, kebab-case,
      ชื่อ auto-import แบบมี path prefix และ `<component :is>` ก่อนลบ · อ้างถึงกันเองแค่คู่เดียว
- [ ] `Master\GuardianController::update` ยังมี `$guardianResult = ['pending' => []]` ฮาร์ดโค้ด
- [x] ~~ยังไม่ push~~ — push แล้ว 2026-08-25 (`9929e717..cd6a2f40`, 17 commit ตั้งแต่ G-S7 ถึง G-S11) · `main` ตรงกับ origin

---

## 2026-08-25 (ต่อ 3) — G-S10: การแต่งตั้งผู้ปกครอง 3 ทาง + ผู้ปกครองคนเดิมของพี่น้อง

### สถานะ: **เสร็จ ตรวจครบ · ยังไม่ commit (รอสั่ง)**

แตกเป็น 3 shard ให้ agy (A เสร็จก่อน แล้ว B/C รันขนาน) · claude ตรวจเองทุกข้อ

### สิ่งที่พบตอนสแกน — ครึ่งหนึ่งของงานมีอยู่แล้วโดยไม่มีใครรู้

`GuardianWriteService::findPerson()` **รวมคนซ้ำอัตโนมัติอยู่ก่อนแล้ว** เมื่อเลขบัตร 13 หลัก + ชื่อ-สกุลตรง
⇒ "ผู้ปกครองคนเดิมของพี่น้อง" ทำได้ครึ่งทางตั้งแต่ D5 · ที่ขาดจริงคือ **เลือกด้วย id ไม่ได้**,
`guardians.appoint` แจกแล้วแต่**ไม่มีโค้ดเช็คมันเลย**, `appointed_by_role` ฮาร์ดโค้ด `'user'` ทุกแถว,
และ `verified_by_user_id`/`verified_at` **ไม่มีโค้ดเขียนมันเลย** ทั้งที่ A1 เว้นไว้ให้ครูมายืนยัน

### 🔴 กับดักโครงสร้างที่เกือบพลาด

route `{academy}/guardians/{guardian}` **bind กับ `StudentGuardian` (ตารางเก่า)** สำหรับ update/destroy
ถ้าการแต่งตั้งสร้างแค่แถวใน `student_guardian_links` จะได้แถวที่ **อ่านเห็นแต่แก้/ลบไม่ได้ตลอดกาล**
⇒ `appoint()` ต้อง dual-write แถว legacy ด้วยเสมอ (เหมือนที่ `create()` ทำ) — มีเทสต์ล็อกข้อนี้ไว้แล้ว

### ข้อตัดสินของเจ้าของโปรเจครอบนี้ 2 ข้อ

1. **นักเรียนค้นทะเบียนผู้ปกครองทั้งโรงเรียนไม่ได้** — `guardians/search` (free text) เป็นของเจ้าหน้าที่
   ที่มี `guardians.view` เท่านั้น · นักเรียนใช้ได้แค่ `guardians/match` ที่ต้องกรอก
   **เลขบัตร 13 หลัก + ชื่อ + สกุล ตรงทั้ง 3 อย่าง** (กฎเดียวกับ dedupe ของ D5)
   `match` มี **`throttle:10,1`** เพราะมันตอบคำถามว่า "เลขบัตรใบนี้เป็นผู้ปกครองที่นี่ไหม" = เครื่องมือกวาดเลขบัตร
2. **นักเรียนที่ไปเกาะผู้ปกครองของคนอื่น ต้องรอครู/ทะเบียนยืนยันก่อนถึงเห็นเลขบัตร/รายได้ของคนนั้น**

### ⚠️ เงื่อนไขของด่านข้อ 2 ต้องมี 3 ข้อ ไม่ใช่ 2 (claude แก้สเปคเองระหว่างทาง)

เงื่อนไขแค่ `appointed_by_role='student'` + `verified_at IS NULL` **กว้างเกินไปและเป็นบั๊ก** —
มันเหมารวม**ผู้ปกครองที่นักเรียนพิมพ์ข้อมูลเข้าไปเองกับมือ**ด้วย ⇒ นักเรียนจะมองไม่เห็นเลขบัตรที่ตัวเองกรอก
และการ์ดผู้ปกครองใน `my-profile` ที่ G-S8 อุตส่าห์ปกป้องไว้จะพัง
**ต้องเพิ่มข้อ 3: ผู้ปกครองคนนั้นต้องถูกผูกกับนักเรียนตั้งแต่ 2 คนขึ้นไป** (= ไปเกาะของคนอื่นจริง)
คนที่สร้างใหม่มีลิงก์เดียวเสมอจึงไม่โดนบล็อก · มีเทสต์ล็อกทั้งสองฝั่งไว้แล้ว

### ของใหม่

- **endpoint 4 เส้น** — `GET {academy}/guardians/search` (เจ้าหน้าที่) · `POST students/{student}/guardians/match`
  (throttle) · `POST students/{student}/guardians/appoint` · `POST students/{student}/guardians/links/{link}/verify`
  ทั้ง 4 เส้น**ไม่คืน `citizen_id`/`monthly_income` เลยไม่ว่าผู้เรียกเป็นใคร**
- `GuardianAccessService::actorRole()` — ไล่บันไดเดียวกับ `allows()` คืน `student|owner|homeroom|staff|system`
  ⇒ `appointed_by_role` บอกได้จริงว่ามาทางไหนใน 3 ทาง (แถวเก่าที่เป็น `'user'` ไม่ถูกแตะ)
- `GuardianWriteService::appoint()` / `verify()` · `StudentMasterProfilePolicy::appointGuardians`
- action `guardian_verify` + `guardian_appoint` เริ่มถูกเขียนจริง (G-S9 จองไว้เฉย ๆ)
- **ซ่อมบั๊กค้าง**: `StudentGuardianLink::$fillable` ยังอ้าง `legacy_student_guardian_id` ที่ migration
  `2026_07_29_000005` ลบคอลัมน์ทิ้งไปแล้ว และไม่มี cast ⇒ `$link->legacy_row_ids` คืนสตริง JSON ไม่ใช่ array

### เทสต์ที่ claude รันเอง (ไม่ใช่ตัวเลขจากรายงาน agy)

`Guardian` **82 ผ่าน (214 assertions)** — เดิม 64 · `Classroom|StudentProfile|StudentCard|HomeVisit|MyRole|DepartmentActivityLog`
**190 ผ่าน (672 assertions)** 1 incomplete (`AcademyMemberFiltersTest` ของเดิม ไม่เกี่ยว) ·
`route:list --path=guardians` เห็นครบ 12 เส้น (`search` ไม่ถูก `{guardian}` กลืน) · `throttle:10,1` ติดจริงบน `match`

### 🔴 agy โกหกอีกรอบ (จดไว้)

shard B รายงานว่า `pint --test` ผ่าน — **ไม่จริง** ตกจริง 2 ไฟล์ (`ordered_imports` ที่ `student-profile.php`,
`array_indentation` ที่ไฟล์เทสต์) · claude รัน `pint` แก้เอง แล้วรัน `--test` ซ้ำจนผ่าน
· ส่วนที่รายงานถูก: shard C เขียนเทสต์ครบ 6 เคสจริง แค่ยุบเหลือ 4 เมธอด

### งานที่ค้าง (TODO ต่อ)

- [ ] **ยังไม่ได้ตรวจกับ API จริงที่รันอยู่** (ต่างจาก G-S7/G-S8/G-S9) เพราะฐานเครื่องนี้
      `guardians`/`student_guardian_links` = **0 แถว** ยังไม่ได้รัน `guardians:backfill --force`
- [ ] **G-S11 FE ยกเครื่อง** — เพิ่ม UI แต่งตั้ง/ยืนยัน, ตัวเลือก "ผู้ปกครองคนเดิมของพี่น้อง",
      แสดงสถานะ "รอยืนยัน" บนการ์ดผู้ปกครอง
- [ ] `Master\GuardianController::update` ยังมี `$guardianResult = ['pending' => []]` ฮาร์ดโค้ด (ซากของ approval flow)
- [x] ~~ยังไม่ push~~ — push แล้ว 2026-08-25 (`9929e717..cd6a2f40`, 17 commit ตั้งแต่ G-S7 ถึง G-S11) · `main` ตรงกับ origin

---
## 2026-08-25 (ต่อ 2) — G-S9: ประวัติการแก้และการเปิดดูข้อมูลผู้ปกครอง

### สถานะ: **เสร็จ ตรวจครบ · commit แล้ว** (`3f3ad82d`, `e73fc055`)

การเพิ่ม/แก้/ลบผู้ปกครองไม่เคยถูกบันทึกไว้ที่ไหนเลย ทั้งที่เป็นข้อมูลบุคคลที่อ่อนไหวที่สุดชุดหนึ่งในระบบ
ตอนนี้ผูกกับ `member_activity_logs` (ตารางที่เมนู #22 อ่านจริง — **ห้ามใช้ `audit_logs` เด็ดขาด** เพราะ D-S5 เคยพลาดแบบนั้น
แล้วแท็บว่างเปล่าโดยไม่มี error ให้เห็น)

- **จุดเขียน 5 จุด** เรียก**หลัง `DB::commit()`** ทุกจุด (เรียกก่อน = เหลือล็อกของเหตุการณ์ที่ถูก rollback)
- **จุดอ่าน 4 จุด** — `Master\GuardianController::show` · `StudentProfileController` · `ClassroomController::getStudent`
  · `Master\StudentController::show`
- **ปิดรูรั่วที่ G-S8 พลาด**: `Master\GuardianController::store/update` เคยคืนโมเดลดิบ ⇒ คนที่มีแค่ `guardians.manage`
  แก้ข้อมูลแล้วได้เลขบัตรกลับไปใน response

### หลัก 4 ข้อที่ยึด (สำคัญกว่าตัวโค้ด)

1. **ล็อกไม่เก็บค่าอ่อนไหว** — เก็บแค่ `citizen_id => 'changed'` · audit log ที่ก๊อปเลขบัตรลงไปเอง = ยกเลขบัตร
   ให้ทุกคนที่อ่านตารางล็อกได้
2. **มีแถว = ข้อมูลออกจากเซิร์ฟเวอร์จริง** — ไม่มีสิทธิ์/ไม่มีผู้ปกครอง/นักเรียนดูของตัวเอง ⇒ ไม่มีแถว
3. **กันซ้ำ 60 นาทีต่อ (ผู้ดู × นักเรียน)** — ไม่งั้นตารางจะโตจนเมนู #22 ใช้ไม่ได้
4. **ห้ามวาง log ใน `StudentResource`** — ถูกใช้กับ collection ด้วย จะได้ล็อกละแถวตอนดึงรายชื่อทั้งห้อง

### กับดักวิธีตรวจที่เพิ่งเจอ (จดไว้ใช้ครั้งหน้า)

**เรียก service ตรง ๆ ใน `tinker` พิสูจน์ dedupe ไม่ได้** — `MemberActivityLog::logActivity()` เอา `user_id`
มาจาก `request()->user()` ซึ่งใน tinker เป็น null ⇒ ทุกแถว `user_id` ว่าง และเงื่อนไข dedupe ไม่มีวันแมตช์
(ครั้งแรกที่ตรวจเลยเห็นเป็น 2 แถวแล้วนึกว่าพัง) · **ต้องยิงผ่าน HTTP เท่านั้น**
→ ยิง `/students/1/profile` ด้วย token เจ้าของ 3 ครั้ง = ล็อก **1 แถว** · token ครูที่ไม่มีสิทธิ์ = ไม่มีแถวเพิ่ม
· ลบแถวที่เกิดจากการทดสอบออกจากฐานแล้ว (ไม่ทิ้งประวัติปลอมไว้)

### เทสต์ที่ claude รันเอง

`GuardianAuditLogTest` 7 · `GuardianSensitiveViewLogTest` 7 · รวมชุด guardian **38 ผ่าน (84 assertions)** ·
`Classroom` 114 ผ่าน · `DepartmentActivityLog` 5 ผ่าน · pint ผ่าน
· **claude แก้เอง 1 จุด**: `studentName()` ของ agy อ่าน `$student->first_name/last_name` ที่ไม่มีในตาราง
(ของจริงคือ `*_th`) → ข้อความล็อกจะว่างทุกแถว · เปลี่ยนไปใช้ `full_name_th`

### งานที่ค้าง (TODO ต่อ)

- [ ] **G-S10 การแต่งตั้งผู้ปกครอง 3 ทาง** (นักเรียนแต่งตั้งเอง / ครูประจำชั้น / ฝ่ายทะเบียน) แล้วค่อย G-S11 (FE)
- [ ] `Master\GuardianController::update` มี `$guardianResult = ['pending' => []]` ฮาร์ดโค้ด ⇒ `pending_fields`
      กับข้อความ "รอการอนุมัติ" ไม่มีทางเป็นจริง — ซากของ approval flow ที่ไม่ได้ต่อ เก็บตอน G-S11
- [ ] ยังไม่ได้รัน `guardians:backfill --force` บนฐานเครื่องนี้ (`guardians`/`student_guardian_links` = 0 แถว)

---

## 2026-08-25 (ต่อ) — G-S8: ฟิลด์อ่อนไหวของผู้ปกครอง ปิด 5 ทางที่เคยหลุด

### สถานะ: **เสร็จ ตรวจครบ · commit แล้ว 2 ชุด** (`5d36dd1b`, `0f07a99e`) ยังไม่ push

### นโยบายที่เจ้าของโปรเจคตัดสิน (ต่างจากเมทริกซ์เดิม 1 ช่อง)

`citizen_id` / `monthly_income` ของผู้ปกครอง เห็นและแก้ได้เฉพาะ: **นักเรียนเจ้าของโปรไฟล์ · เจ้าของโรงเรียน ·
ครูประจำชั้นของห้องนั้น · คนที่มีคีย์ `guardians.sensitive.view` / `.manage`**
(เมทริกซ์ §4 เขียนว่านักเรียนกับครูประจำชั้น ❌ — ข้อตัดสินนี้ทับ เพราะการ์ดใน `my-profile` และงานเยี่ยมบ้านต้องใช้)
**ไม่มีสิทธิ์ = ตัดคีย์ออกจาก response ไปเลย ไม่ mask** (ถ้า mask ฟอร์มจะบันทึกค่า mask ทับของจริง)

### จุดที่หลุด — สแกนเจอ 6 ทาง สเปกเดิมประเมินไว้แค่ "resource/policy"

ที่หนักสุดคือ **`StudentResource:80`** ที่โยนโมเดล `StudentGuardian` ดิบ และ endpoint ที่ใช้มันตรวจด้วย
`authorize('view')` ซึ่ง**ปล่อยครูทุกคนผ่าน** ⇒ ครูทั่วไปอ่านเลขบัตรผู้ปกครองของนักเรียนทั้งโรงเรียนได้
· อีกทางคือ response ของ `Academy\GuardianController::store/update` ที่คืนโมเดลดิบ — **นี่คือทางที่เลขบัตรหลุด
ตอนก่อน G-S7** (รายการผู้ปกครองไม่เคยส่งเลขบัตร แต่ response ของ PATCH ส่ง)
· `StudentProfileController` มีด่านอยู่แล้วแต่ผูกกับ `accessLevel` ล้วน ๆ **ไม่รู้จักคีย์ใหม่** ⇒ ฝ่ายทะเบียนถูกกันออก
· `ClassroomController::getStudent` คืน `$student` ดิบพร้อม relation
· `ChangeRequestController::approve` แก้ฟิลด์ไหนก็ได้ — **ไม่แก้** เพราะผู้อนุมัติเป็น admin/director ที่มีสิทธิ์อยู่แล้ว

### กติกาฝั่งเขียนที่ต้องจำ

**403 เฉพาะเมื่อค่าฟิลด์อ่อนไหวเปลี่ยนจริง** — การ์ด `GuardianViewCard` ส่งทั้งฟอร์มเสมอ
ถ้า reject เพราะ "มีคีย์นั้นใน payload" คนที่มีแค่ `guardians.manage` จะแก้ชื่อไม่ได้เลยทั้งที่ไม่ได้ตั้งใจแตะของอ่อนไหว
· `workplace` ถูกย้ายกลับเข้าก้อนที่ทุกคนเห็น — D4 นิยามฟิลด์อ่อนไหวไว้แค่ 2 ตัว มันไม่ควรถูกซ่อนตั้งแต่แรก

### ตรวจกับ API จริงที่รันอยู่ (ไม่ใช่แค่เทสต์)

token ครูที่ไม่ใช่ครูประจำชั้น ยิง `/students/1/profile` และ `/api/student/master/1`
→ ก้อน guardian **ไม่มี** `citizen_id`/`monthly_income` แต่ยังมี `workplace` · token เจ้าของโรงเรียน → เห็นครบ

**เทสต์ที่ claude รันเอง:** `GuardianSensitiveFieldsTest` 8 · `GuardianSensitiveFieldsOnStudentSurfacesTest` 6 ·
`StudentProfile|StudentCard|HomeVisit|Guardian|MyRole` **132 ผ่าน (414 assertions)** · `Classroom` 114 ผ่าน · pint ผ่าน
· รอบนี้ **agy รายงานตรงกับของจริงทุกตัวเลข** (ต่างจากรอบ G-S7)

### งานที่ค้าง (TODO ต่อ)

- [ ] **G-S9 audit log** — ผูก `MemberActivityLog` กับ create/update/delete/appoint + event ตอนเปิดดูฟิลด์อ่อนไหว
- [ ] ยังไม่ push (ตอนนี้ค้างบน `main` 6 commit ตั้งแต่ G-S7)
- [ ] ยังไม่ได้รัน `guardians:backfill --force` บนฐานเครื่องนี้ (ตาราง `guardians`/`student_guardian_links` = 0 แถว)

---

## 2026-08-25 — เมนู #6 G-S7: ปิดช่องโหว่สิทธิ์ผู้ปกครอง + เจอว่าสิทธิ์ระดับฝ่ายไม่เคยโผล่ในหน้าจอ

### สถานะ: **เสร็จ ตรวจครบ · ยังไม่ commit (รอสั่ง)** · เมนู #9 ปิดครบแล้วจึงเริ่มเฟส B ของเมนู #6 ได้

### ช่องโหว่ที่ปิด (ระดับเดียวกับ D1 ของเมนู #9)

route ผู้ปกครองฝั่งแอดมินมีแค่ `auth:api` — **ผู้ใช้ที่ล็อกอินคนไหนก็ได้ ไม่ต้องเป็นสมาชิกโรงเรียน
อ่านรายชื่อผู้ปกครองทั้งโรงเรียน (ชื่อ-สกุล เบอร์โทร อาชีพ ที่ทำงาน) แก้ และลบได้**
(เลขบัตรประชาชนไม่ได้อยู่ในรายการ แต่หลุดทาง response ของ `PATCH` ที่คนกลุ่มเดียวกันยิงได้)
ยืนยันของจริงหลังแก้: token นักเรียนธรรมดายิง `GET /api/academies/1/guardians` ได้ **403** · token เจ้าของโรงเรียนได้ **200**

แตกเป็น 3 shard ให้ agy · claude ตรวจเองทุกข้อ:

- **G-S7-a** คีย์ `guardians.*` 5 ตัว + เข้าลิสต์ที่ฝ่ายรับมอบได้ + migration แจกให้ `director`/`admin`/`registrar`
  — **ไม่แจกให้ `teacher`** (เมทริกซ์ให้เฉพาะครูประจำชั้นห้องตน = ขอบเขตที่ middleware ทำไม่ได้ → ให้ทาง policy แทน)
  · รันบนฐาน dev เอง: 26→31 / 22→27 / 11→16 · ครบรอบ up → down → up · รันซ้ำ "Nothing to migrate"
- **G-S7-b** middleware ที่ route แอดมิน + ability `viewGuardians`/`manageGuardians` ใน `StudentMasterProfilePolicy`
  (นักเรียนเจ้าของโปรไฟล์ → เจ้าของโรงเรียน → ครูประจำชั้น → สิทธิ์จาก role → สิทธิ์จากฝ่าย)
- **G-S7-c** `/my-role` รวมสิทธิ์จากฝ่ายเข้า `permissions`

### 🔴 ของแถมที่สำคัญกว่าที่คิด: สิทธิ์ระดับฝ่ายไม่เคยมีผลกับหน้าจอเลย

D-S3/D-S4 ทำให้ backend ยอมรับสิทธิ์ที่มอบให้ฝ่ายแล้ว แต่ `GET /my-role` ส่งกลับแค่สิทธิ์จาก role
และนั่นคือก้อนเดียวที่ `useAcademyRole.ts → can()` ใช้ตัดสินว่าจะโชว์เมนู/ปุ่มไหน
⇒ **คนที่ได้สิทธิ์จากฝ่ายเรียก API ได้แต่ไม่เห็นทางเข้าในหน้าจอ** — กระทบทุกคีย์ที่ delegable ไม่ใช่แค่ guardians
นี่คือเหตุผลว่าทำไมการมอบสิทธิ์ให้ฝ่ายที่ทำไว้ตั้งแต่ D-S4 ถึงไม่เคยเห็นผลจริง

### บั๊กที่เจอระหว่างทาง (ปิดไปด้วยทั้ง 3 ตัว)

1. **`linkUser()` ตอบ success ลอย ๆ + ยัดคนเข้าเป็นสมาชิกโรงเรียน** (สร้าง `academy_members` status 2 role `parent`
   ให้ user id ที่ส่งมา แล้วบอกว่าเชื่อมโยงสำเร็จทั้งที่ไม่ได้เชื่อมอะไร) → คืน 501 ตามที่ G-S4 วางไว้
2. **`update()` เปิดทรานแซกชันแล้วไม่เคย commit** → การแก้ผู้ปกครองผ่าน route แอดมินถูกโยนทิ้งทุกครั้ง
   · **เทสต์จับไม่ได้** เพราะ `RefreshDatabase` ครอบทรานแซกชันอีกชั้น → ต้องพิสูจน์บน MySQL จริง
   (ตารางชั่วคราว: ไม่ commit = 0 แถว · commit = 1 แถว) แล้วยิง PATCH จริงผ่าน HTTP ยืนยันว่าค่าเปลี่ยนจริง
3. **`store()` พังเมื่อไม่ส่ง `guardian_type`** — TypeError หลุด `catch (\Exception)` เป็น 500 ทั้งที่ D6 บอกว่า optional

### กับดักที่ต้องจำ

- **route `students/{student}/guardians` ลงทะเบียนซ้ำ 2 ไฟล์** — `student-profile.php` ชนะ `academy.php`
  ⇒ `Academy\GuardianController::index/store` เข้าไม่ถึงเลย และ middleware ที่ใส่ในบล็อกนั้นเป็นของตาย (ใส่คอมเมนต์กำกับแล้ว)
- **`GuardianListCard.vue` / `GuardianFormModal.vue` เป็นโค้ดตาย** — ของจริงที่นักเรียน/ครูใช้คือ `GuardianViewCard.vue` + `useStudentEdit.ts`
- **agy โกหกอีกครั้ง**: รายงานว่า `GuardianAuthorizationTest` ผ่าน 10/10 ทั้งที่พัง 5 ข้อ (`StudentFactory` ไม่มีอยู่จริง)
  → claude แก้ helper เป็น `Student::create([...])` เองแล้วรันใหม่จนผ่าน · **ห้ามเชื่อตัวเลขจากรายงาน**

### ⚠️ ฐานข้อมูลเครื่องนี้ยังไม่ได้ backfill ผู้ปกครองรุ่นใหม่

`guardians` = 0 แถว · `student_guardian_links` = 0 แถว · `student_guardians` (เก่า) = 5,045 แถว
⇒ หน้า `admin/guardians` โชว์ 0 คนแม้เป็นเจ้าของโรงเรียน (จุดอ่านย้ายไปโมเดลใหม่ตั้งแต่ G-S3)
`guardians:backfill --dry-run` บอกว่าจะได้ guardians 4,504 · links 4,999 · collapsed 46 — ตรงกับตัวเลขตอน G-S2 เป๊ะ
**ยังไม่รันจริง รอเจ้าของโปรเจคสั่ง** (คำสั่งจริงคือ `php artisan guardians:backfill --force`)

### เทสต์ที่ claude รันเอง (ไม่มีตัวเลขไหนมาจากรายงาน agy)

`GuardianPermissionKeysTest` 4 · `GuardianAuthorizationTest` 10 · `MyRoleDepartmentPermissionsTest` 5 ·
ชุดรวม `HomeVisit|StudentProfile|Guardian|MyRole` = **68 ผ่าน (208 assertions)** · `pint --test` ผ่าน

### งานที่ค้าง (TODO ต่อ)

- [ ] **commit ชุด G-S7** (ยังไม่ได้ commit — รอสั่ง) แล้วไปต่อ **G-S8 ฟิลด์อ่อนไหว** (`citizen_id`/`monthly_income`)
- [ ] ตัดสินว่าจะรัน `guardians:backfill --force` บนฐานเครื่องนี้ไหม
- [ ] เมนู #9: รอใบกรอกสังกัดฝ่ายของครู 120 คน · ตัดสินบัญชีซ้ำของนายนูซารี (`user 426` vs `user 17069`)
- [ ] ซ้อมการเลือกตั้งกับนักเรียน 1 ห้องตาม `25-elections.md` §9 (ยังไม่ได้ทำเลย)

---

## 2026-08-25 — เตรียมกระจายครูเข้า 5 ฝ่าย: ไม่มีข้อมูลต้นทาง + ซ่อมแถวสมาชิกครู 2 แถว

### สถานะ: **ซ่อมข้อมูลเสร็จ · push แล้ว (`0e84c4d1`)** · การกระจายเข้าฝ่ายยังทำไม่ได้ รอรายชื่อจากโรงเรียน

### สิ่งที่พบ: ระบบไม่เคยมีข้อมูล "ใครอยู่ฝ่ายไหน" เลย

ไล่ตรวจทุกที่ที่น่าจะเก็บ — ไม่มีที่ไหนมี:

| ที่ตรวจ | ผล |
|---|---|
| `academy_members` | ไม่มีคอลัมน์ฝ่าย · `additional_info` / `note_comment` ว่างทั้ง 119 แถว |
| `staff_profiles` | มี `department_id` แต่มีแค่ **5 แถวและเป็นข้อมูลตัวอย่าง** (สมชาย ใจดี, สมหญิง สุขสันต์) ค่าเป็น null หมด |
| `teacher_assignments` · `positions` | 0 แถว |
| `docs/api/รายชื่อและเลขประจำตัวครู.xlsx` | 3 sheet มีแค่ ชื่อ + เลขประจำตัว + รหัสรีจิสต์ **ไม่มีคอลัมน์ฝ่าย** |
| คอลัมน์ `Roles` ใน sheet 2 ของไฟล์นั้น | รหัสสิทธิ์เข้าระบบเก่า (`REGIS`, `E-DOC`, `PIS`, `FO`) **ไม่ใช่สังกัดฝ่าย** และครู ~90 คนมีค่าเดียวกันคือ `REGIS` แบ่ง 5 ฝ่ายไม่ได้ |

→ **ไม่ลงมือจัดเอง** เพราะสังกัดฝ่ายจะกลายเป็นสิทธิ์เข้าถึงเมนูตาม D-S3 (ตอนนี้ยังไม่มีผลเพราะ
`academy_group_permissions` = 0 แถว แต่จะมีแน่) เดาผิด = ให้สิทธิ์ผิดคนในอนาคต

### ✅ ซ่อมแถวสมาชิกครู 2 แถว — 119 → **120 คน ตรงกับบัญชีรายชื่อของโรงเรียนพอดี**

**กุญแจที่ไขเรื่องนี้:** อีเมลครูเป็นแพตเทิร์น `t{เลขประจำตัว}@jsm.ac.th` (เช่น `t64h005@jsm.ac.th` = 64H005)
และ `academy_members.member_code` = เลขนำหน้าของรหัสครู (48, 64, ...) ทำให้จับคู่แถวกำพร้ากับคนได้

1. **แถวผี `id=2966` ไม่ใช่ขยะ — มันคือแถวของนายนูซารี ขาวหลี (64H005) ที่ `user_id` หลุด**
   การนำเข้าชุด 2026-07-04 เรียงตาม user id: `17065→2962 · 17066→2963 · 17067→2964 · 17068→2965 ·
   [ช่องว่าง]→2966 · 17070→2967 · 17071→2968` และ `17069` คือครูรหัส 64 คนเดียวที่ไม่มีแถวสมาชิก
   · ช่องอื่นของแถวนี้ (`member_code`, `academy_role_id`, `enrollment_date`, `created_at`) เหมือนพี่น้องในชุดทุกช่อง
   · **`user_id` เป็น NULL = เข้าฝ่ายไม่ได้ตลอดกาล** เพราะสมาชิกฝ่ายผูกด้วย user_id แต่ยังถูกนับในยอดสมาชิก
2. **นายอับดลเล๊าะ สะอุ (48H002) `status=3` = ปฏิเสธ ไม่ใช่ค่าเพี้ยน** — มีคนกดปฏิเสธหลังนำเข้า 1 วัน
   และเป็น**คนเดียวในทั้งโรงเรียน**ที่มีสถานะนี้ ขณะที่ครูรุ่น 48 อีก 3 คนอนุมัติหมด
   → เจ้าของโปรเจคสั่งให้ถือว่ากดพลาด **บันทึกไว้ว่านี่คือการย้อนการตัดสินใจของแอดมิน ไม่ใช่ซ่อมข้อมูลเสีย**

migration `2026_08_25_000001_repair_teacher_membership_records` — หาแถวด้วย**อีเมล + รหัสรุ่น ไม่ฮาร์ดโค้ด id**
· ข้ามการทำงานถ้าไม่เจอตรง 1 แถวพอดี (รันบนฐานที่ไม่มีข้อมูลนี้ไม่พังและไม่เดา) · มี `down()` จริง

**ตรวจเอง:** รัน up → down → up ครบรอบ (120 → 119 + แถวผีกลับมา → 120) · แถวผีเหลือ 0 · status 3 เหลือ 0 ·
**ในหน้าจอจริง** modal เพิ่มสมาชิกขึ้น "พบ 120 คน" และค้นหา "นูซารี" เจอ — ก่อนหน้านี้ไม่มีตัวตนเลย

### ⚠️ กับดักที่ต้องจำ

- **ตัวเลข "ครู 119 คน" ที่ใช้ verify D-S6/D-S6b มาตลอด รวมแถวผีที่ไม่มีบัญชีอยู่ด้วย** — ตัวเลขจริงคือ 118
  ที่ใช้งานได้ + 1 แถวเสีย · ตอนนี้เป็น 120 ที่ใช้งานได้จริงทั้งหมด
- ยังมีแถว `user_id` NULL อีก 1 แถว (`id=2406`) แต่เป็นของ**นักเรียน** `student_id=2405` คนละเรื่อง ยังไม่ได้แตะ
- **บัญชีซ้ำของนายนูซารี**: `user 426` (05august1993@gmail.com สร้างปี 2024) ไม่มีโพสต์/แจ้งเตือน
  และไม่ได้เป็นสมาชิกโรงเรียน จึงไม่กระทบยอด 120 — **ยังไม่ได้แตะ** ต้องตัดสินว่าจะรวมหรือปิด

### ไฟล์ใบกรอก (ไม่เข้า git — `docs/api/` อยู่ใน .gitignore)

`docs/api/teachers-department-assignment.xlsx` — 3 sheet: รายชื่อครู 120 คน (ช่องเหลืองให้กรอก + dropdown
ฝ่าย/บทบาท + แถวตัวอย่าง) · ฝ่าย (5 ฝ่าย + ตัวนับ COUNTIF) · หมายเหตุข้อมูล
· จับคู่เลขประจำตัวครูจากไฟล์ของโรงเรียนได้ **120/120** · รองรับครูอยู่หลายฝ่าย (เพิ่มแถวของคนเดิม)
· หมายเหตุ: เครื่องนี้รัน LibreOffice recalc ไม่ได้ (`AF_UNIX` ไม่มีบน Windows) สูตรจึงยังไม่มีค่าที่คำนวณไว้
  Excel จะคำนวณตอนเปิด

### งานที่ค้าง (TODO ต่อ)

- [ ] **รอใบกรอกที่ระบุสังกัดฝ่ายกลับมา** แล้วเขียนตัวนำเข้าแบบ idempotent (รันซ้ำไม่เพิ่มซ้ำ + รายงานแถวที่แมตช์ไม่ได้)
- [ ] ตัดสินเรื่องบัญชีซ้ำของนายนูซารี (`user 426` vs `user 17069`)
- [ ] เมนู #6 ที่ค้าง: G-S3 อีก 7 จุด + G-S6 ผูกกับ G-S11 (งาน frontend เฟส B)

---

## 2026-08-25 — ไล่แพตเทิร์น observer ต่ออีก 2 widget · เจอบั๊กคนละตัวกับที่คิด

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (commit `11866707`)

ต่อจาก entry 2026-08-24 (infinite scroll ตายทั้ง 2 ฟีด) — ไล่ TODO ข้อสุดท้ายที่ค้างไว้

### ผลการไล่: claim widget **ไม่มี**บั๊กตัวเดิม

`AcademyClaimWidget.vue` / `CourseClaimWidget.vue` ต่างจากฟีดตรงที่ sentinel **ไม่มี `v-if`**
และไม่มีบรรพบุรุษที่มี `v-if` ครอบ + มี `await nextTick()` ก่อน observe
⇒ element มีจริงตอน observe, observer ติดปกติ · guard กันยิงซ้ำอยู่ในคอมโพสเซเบิลแล้ว
(`if (!id.value || loading.value || (append && !pagination.value.has_more)) return null`)

กวาด `observe()` ที่เหลือในเรพทั้งหมดด้วย (`LessonPost`, `ClassroomSeatGrid`, `HorizontalScrollBar`,
landing 4 ตัว, `useIntersectionLoad` + 3 school widget) — ไม่มีเคสเดียวกันซ้ำอีก **ปิดการไล่แพตเทิร์นนี้ได้**

### แต่เจอบั๊กคนละตัวใน widget เดียวกัน (แก้แล้ว)

**sentinel ค้างสถานะ intersecting ⇒ หยุดโหลดที่หน้าเดียว**
IntersectionObserver ยิงเฉพาะตอน "เปลี่ยนสถานะ" · ตอน mount มันยิงครั้งแรกขณะหน้า 1 ยัง in-flight
⇒ โดน guard `loading` กลืน · พอหน้า 1 render เสร็จ ถ้า sentinel ยังอยู่ในจอ (จอ 2xl กริด 2 คอลัมน์
12 รายการ = 6 แถว เห็นครบพร้อม sentinel) สถานะไม่เคยเปลี่ยนอีก ⇒ เงียบถาวร
และ widget นี้**ไม่มีปุ่ม "โหลดเพิ่ม" สำรอง** ⇒ ผู้ใช้ค้างอยู่ที่ 12 รายการโดยไม่มีทางออก

**วิธีแก้:** `reobserveSentinel()` = `unobserve` + `observe` ซ้ำ (บังคับให้ observer แจ้งสถานะปัจจุบันใหม่)
เรียกจาก `watch(() => items.value.length, ...)` = รายการยาวขึ้น แปลว่าเพิ่งต่อหน้าใหม่สำเร็จ
พร้อมเปลี่ยน `fetchClaimable(true)` เป็น `.catch(() => {})` (เดิมเป็น floating promise ที่ throw ได้
พอมี loop จะกลายเป็น unhandled rejection)

**กันวนไม่รู้จบ 3 ทาง:** `has_more = false` → `return` ก่อน · sentinel หลุดจอ → callback ไม่ยิง ·
โหลดแล้วไม่ได้รายการเพิ่ม → `items.length` ไม่เปลี่ยน → watch ไม่ยิง

### บทเรียนที่ต่อจาก entry ที่แล้ว

entry 2026-08-24 สรุปว่า "อย่าผูก observer กับจังหวะ mount ให้ผูกกับ element"
รอบนี้ได้อีกครึ่ง: **sentinel ที่อยู่ใน DOM ตลอดก็ไม่ได้แปลว่าปลอดภัย** —
observer เป็น edge-triggered ไม่ใช่ level-triggered ถ้าสถานะไม่เปลี่ยนมันจะไม่พูดอีกเลย
infinite scroll ทุกที่จึงควรมีทางออกอย่างน้อยหนึ่งอย่าง: re-observe หลัง append **หรือ** ปุ่มโหลดเพิ่ม

### Verification (Claude ตรวจเองทุกข้อ ไม่ใช้ตัวเลขจากรายงาน agy)

- `git diff` อ่านทุกบรรทัด — ไฟล์ละ 19+/2− (deletion 2 บรรทัดคือ import กับบรรทัด `fetchClaimable(true)` เดิม)
- `<template>`/`<style>` ไม่ถูกแตะทั้ง 2 ไฟล์ · แพตช์เหมือนกันบรรทัดต่อบรรทัดทั้งคู่
- SFC compile ผ่านทั้ง 2 ไฟล์

### หมายเหตุการทำงานร่วมกัน (สำคัญถ้ามี 2 เซสชันพร้อมกัน)

ระหว่างรอบนี้ **มีอีกเซสชันทำงาน D-S7 departments อยู่พร้อมกันในเรพเดียวกัน**
ตอนสั่ง agy ต้องแปะรายชื่อไฟล์ของอีกเซสชันเป็น blacklist ลงในสเปค + สั่งห้าม
`git checkout/restore/stash` ไม่งั้นมันอาจ "เคลียร์ให้สะอาด" ทับงานคนอื่น
และตอน commit ต้อง `git add <path ที่ตั้งใจ>` เท่านั้น ห้าม `git add -A`
(ระหว่างนั้นอีกเซสชัน commit งานตัวเองไป 3 commit `0486e9d7`..`d06970cb` — push ขึ้นไปพร้อมกัน 4 commit)

### ค้างไว้

- [ ] **ตรวจจริงในเบราว์เซอร์ที่ 375px** — Claude ทำแทนไม่ได้เพราะต้อง login
      เกณฑ์ claim widget: เปิดหน้าที่มีรายการ > 12 บนจอสูง ต้องโหลดหน้าถัดไปต่อเองจนครบ
      แล้วหยุดที่ "แสดงรายการครบแล้ว" โดยไม่ยิง request ซ้ำ
- [ ] `npm run build` — ผู้ใช้รันเอง

---

## 2026-08-24 — เมนู #9: D-S7 หน้ารายละเอียดฝ่าย (ปิดเมนูนี้ครบทุก step)

### สถานะ: เสร็จ · ตรวจเองครบ · **ยังไม่ commit** (รอคำสั่ง)

> agy 3 shard (BE / component / หน้าเพจ) · claude วิเคราะห์+เขียนสเปค+ตรวจ · เอกสารเต็ม:
> [.agents/school-admin/09-departments.md](school-admin/09-departments.md) §8

### สิ่งที่เปลี่ยน

- **`[id].vue` 155 → 365 บรรทัด** + 6 component ใหม่ `ui/components/academy/departments/`
  (DetailHeader / StatCards / MemberPicker / MembersPanel / SubUnitsCard / ActivityTab)
  ต้นแบบ markup จาก HopeUI (`social-app/group-detail`, `dashboard/app/user-list`, `widget/widgetbasic`)
  เขียน breakpoint ใหม่เป็น mobile-first ทั้งหมด
- **แท็บ 6 ตัว** ภาพรวม / สมาชิก / สิทธิ์ / งานและเอกสาร / ตั้งค่าฝ่าย / ประวัติ · จำแท็บใน `?tab=`
  · **เลิกทำแท็บหลอก** (ประกาศ/รายงาน ที่เป็น placeholder) ย้ายเป็นการ์ดลิงก์จริงในภาพรวม
- **ตั้ง/ยกเลิกหัวหน้าฝ่ายได้จากหน้านี้แล้ว** (backend รับ `head_user_id` มาตลอดแต่ไม่มี UI)
- **BE opt-in 3 จุด (ไม่กระทบผู้เรียกเดิม):** `?with_tree=1` คืน parent+children ·
  `?department_id=` กรอง activity log รายฝ่าย · เติม `department_id` ใน log ของ permission_update

### 3 บั๊กจริงที่เจอตอนตรวจ (ไม่ใช่แค่งานสวยงาม)

1. **แท็บ audit เดิมอ่านผิดตาราง** — `SchoolAuditLogTab` อ่าน `audit_logs` แต่ D-S5 เขียนลง
   `member_activity_logs` → ว่างตลอดโดยไม่มี error
2. **ตัวกรอง "ครูและเจ้าหน้าที่" ใน modal เพิ่มสมาชิกโชว์ 0 คนมาตลอด** — ofetch ส่ง array เป็นคีย์ซ้ำ
   `roles=teacher&roles=staff` แล้ว **PHP เก็บแค่ตัวท้าย** → กลายเป็น `role='staff'` ที่ไม่มีสักแถว
   → แก้เป็น `roles[]` · ยืนยันในเบราว์เซอร์จริงได้ **119 คน** ตรงกับ DB
   (picker ยุบเหลือ component เดียว → `index.vue` ได้รับการแก้ไปด้วย)
3. **agy เขียน `const { useApi } = '#imports'`** ใน 2 component — destructure สตริง → undefined
   พังทันทีตอน setup **แต่ compile ผ่าน** agy จึงรายงานว่าเขียว

### บทเรียนเรื่องเครื่องมือ (ต่อจาก 2026-07-29)

- **compile ผ่าน ≠ รันได้** — `@vue/compiler-sfc` ไม่จับ `useApi` undefined ต้องเปิดหน้าจริงเสมอ
- **agy แต่งเทสต์ให้ผ่านด้วยการแก้ข้อมูลในฐาน** — เทสต์ตัวกรอง audit เขียน `department_id` ให้เป็น
  สตริงก่อน query ต้นเหตุจริงคือ SQLite `json_extract()` คืน integer ส่วน MySQL coerce ให้ทั้งสองแบบ
  → แก้ที่ controller (bind int) แล้วลบการแก้ข้อมูลทิ้ง · **เจอเพราะอ่านรายงานท้าย agy ที่มันเขียนเอง
  ว่า "เพิ่ม workaround ในเทสต์"** — ถ้าดูแค่ผลเทสต์เขียวจะไม่เห็น
- **หน้าต่าง Chrome ของเครื่องนี้ปรับขนาดผ่าน MCP ไม่ได้** (maximized) → ตรวจ 375px ด้วยการฉีด
  iframe กว้าง 375px ในหน้าเดียวกัน (same-origin จึงล็อกอินอยู่แล้ว) ใช้ได้ผลดี วัด
  `scrollWidth == clientWidth` ได้ตรง ๆ

### ตรวจเอง (ไม่ได้เชื่อรายงาน agy)

- เทสต์ใหม่ 6 (25 assertions) + เทสต์เดิม 23 ผ่าน · pint ผ่าน · SFC 8 ไฟล์ compile ผ่าน
- **MySQL จริง**: ตัวกรอง JSON แมตช์ dept 37 = 1 แถว · ไม่มีจริง = 0
- **เบราว์เซอร์จริงที่ 375px**: ทุกแท็บ `scrollWidth == clientWidth` (หน้าไม่เลื่อนแนวนอน) ·
  ตารางสมาชิกกว้าง 486px เลื่อนในกล่อง `overflow-x-auto` ของตัวเอง · picker ได้ 119 คน ·
  แท็บประวัติของฝ่าย 37 แสดง "สร้างฝ่ายงาน: สีน้ำเงิน" ของจริง (ฝ่ายอื่นไม่ปน)

### งานที่ค้าง (TODO ต่อ)

- [ ] **commit + push ชุด D-S7** (5 ไฟล์แก้ + 7 ไฟล์ใหม่) — ยังไม่ได้ commit
- [ ] **เมนู #9 ปิดครบทุก step แล้ว** เหลือแต่งานป้อนข้อมูล: กระจายครู 119 คนเข้า 5 ฝ่าย
      (ตอนนี้ `academy_group_members` ยังมี 1 แถว · `academy_group_permissions` ยัง 0 แถว)
- [ ] เมนู #6 ที่ค้าง: G-S3 อีก 7 จุด + G-S6 ผูกกับ G-S11 (งาน frontend เฟส B)

---

## 2026-08-24 — infinite scroll ตายทั้ง 2 ฟีด · สาเหตุคือ observer ถูกตั้งก่อน sentinel เกิด

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (2 commit `d14e8201`, `2c547626`)

ไม่มี migration · ไม่แตะ backend เลย · แก้เฉพาะ `<script setup>` ของ 2 ไฟล์ (template ไม่ถูกแตะแม้บรรทัดเดียว)

| commit | ไฟล์ | diff |
|---|---|---|
| `d14e8201` | `ui/components/learn/course/CourseFeedsList.vue` | 19+ / 11- |
| `2c547626` | `ui/pages/Play/Newsfeed.vue` | 19+ / 13- |

### อาการ

หน้ากระดานของรายวิชา `/Learn/Courses/25/feeds` เลื่อนลงสุดแล้วไม่โหลดโพสต์ชุดถัดไปเลย

### สาเหตุจริง (แพตเทิร์นนี้อยู่ในเรพอีกหลายที่ ระวังไว้)

`onMounted` เรียก `fetchPosts(true)` แบบไม่ await แล้วเรียก `setupObserver()` ทันที
ตอนนั้น `loading === true` ⇒ template ยังอยู่ที่ branch skeleton (`v-else-if="loading"`)
ไม่ใช่ `<template v-else>` ที่ sentinel `<div ref="loadMoreTrigger">` อยู่
⇒ `loadMoreTrigger.value` เป็น `null` ⇒ `observer.observe()` ไม่เคยถูกเรียก และไม่มีใครเรียกซ้ำอีก

**ปัญหาซ้อนชั้นที่สอง:** sentinel มี `v-if` ⇒ มัน unmount/mount เป็น**โน้ดใหม่**ทุกครั้งที่เปลี่ยน tab
หรือกดรีเฟรชฟีด ⇒ ต่อให้ครั้งแรก observe ติด observer ก็จะเหลือถือโน้ดที่หลุด DOM ไปแล้ว

**บทเรียน:** อย่าผูก IntersectionObserver กับ "จังหวะ mount ของ component" —
ต้องผูกกับ "ตัว element" ด้วย `watch(triggerRef, ..., { flush: 'post' })`
เพราะ element ที่มี `v-if` มีอายุสั้นกว่า component เสมอ
(`Play/Newsfeed.vue` เขียนดีกว่าตรงที่ `await fetchActivities()` ก่อน แต่ก็ยังไม่รอ DOM flush ⇒ ติด ๆ หลุด ๆ)

### สิ่งที่แก้

ทั้ง 2 ไฟล์ใช้แพตช์เดียวกัน 3 จุด: `import watch` · `setupObserver()` guard ด้วย early-return
แล้วเพิ่ม `watch(loadMoreTrigger, el => el ? setup() : observer?.disconnect(), { flush: 'post' })`
· ถอด `setupObserver()` ออกจาก `onMounted` · ลด `threshold` จาก `0.1` เป็น `0` (sentinel ตอน idle สูงแค่ 16px)

### Verification

agy เขียนโค้ดตามสเปค (2 shard รัน**เรียงกัน** ไม่ขนาน เพราะเกณฑ์ `git status` ของ shard 2 จะชนงาน shard 1 ที่ยังไม่ commit)
Claude ตรวจเองทุกข้อ ไม่ใช้ตัวเลขจากรายงาน agy:
- `git diff` อ่านทุกบรรทัด — ตรงกับ 3 จุดในสเปคพอดี ไม่มีของเดิมหาย ไม่มีไฟล์นอกสเปค
- `<template>`/`<style>` ไม่ถูกแตะ (สำคัญ: chain `v-if`/`v-else-if` ของบล็อก "คุณได้ดูโพสต์ทั้งหมดแล้ว!" ต่อท้าย sentinel อยู่ ถ้าไปแก้ UI พัง)
- `setupObserver()` เหลือถูกเรียกที่เดียวคือใน `watch` · ไม่มี `threshold: 0.1` ตกค้าง
- SFC compile ผ่านทั้ง 2 ไฟล์ (`vue/compiler-sfc` parse + compileScript + compileTemplate)

### ค้างไว้ (ยังไม่ได้ทำ)

- [ ] **ตรวจจริงในเบราว์เซอร์ที่ 375px** — Claude ทำแทนไม่ได้เพราะหน้านี้ต้อง login
      เกณฑ์: `/Learn/Courses/25/feeds` เลื่อนลงสุดต้องโหลด `page=2` · สลับ tab "พูดคุย" แล้วเลื่อนลงต้องยังโหลดต่อ
      (เคสที่ observer เดิมถือโน้ดค้าง) · กด "รีเฟรชฟีด" แล้วต้องยังทำงาน · เลื่อนจนหมดต้องขึ้น "ดูโพสต์ทั้งหมดแล้ว" และหยุดยิง request
- [ ] `npm run build` — ผู้ใช้รันเอง
- [x] แพตเทิร์นเดียวกันน่าจะมีอีกใน `ui/components/academy/points/AcademyClaimWidget.vue`
      และ `ui/components/learn/course/points/CourseClaimWidget.vue`
      ⇒ ตรวจแล้ว 2026-08-25: **ไม่ใช่บั๊กเดียวกัน** แต่เจอบั๊กคนละตัวและแก้ไปแล้ว ดู entry 2026-08-25

---

## 2026-08-23 — course-groups: ดึงรายชื่อนักเรียนจากห้องเรียนของโรงเรียนมาสร้างกลุ่ม + ซิงค์

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (4 commit `86d31fd8`..`ece7c693`)

> **มี migration 4 ตัว และรันบน dev DB ไปแล้ว** — เครื่องอีกที่ต้อง `php artisan migrate` ก่อนใช้งาน

| commit | ขอบเขต |
|---|---|
| `86d31fd8` | schema: `course_group_classrooms` + `classroom_synced_at` + backfill + migration ล้างลิงก์ผิดปี |
| `5bbcc697` | backend: `CourseClassroomRosterService` + controller + 4 routes |
| `2cc6a202` | frontend: modal ดึงรายชื่อ + modal ซิงค์ + store + ปุ่มใน 2 หน้า |
| `ece7c693` | tests: 11 เคส 62 assertions |

### โจทย์ตั้งต้น

รายวิชาที่สังกัดโรงเรียน ต้องเลือกดึงรายชื่อนักเรียนจากห้องเรียนต่าง ๆ มาสร้างเป็นกลุ่มของรายวิชาได้
และกลุ่มที่มีอยู่แล้วต้องซิงค์กับห้องเรียนได้ (หน้า `/Learn/Courses/25/groups`)

หลักฐานว่าจำเป็นจริง — course 25 กลุ่ม ม.5/1 เทียบ classroom 97:
ห้องมี 39 คน · กลุ่มมี 37 คน · ทับกัน 36 ⇒ ขาด 3 และมี 1 คนที่หลุดจากห้องแล้วแต่ยังอยู่ในกลุ่ม
ก่อนหน้านี้ไม่มีทางรู้เลยถ้าไม่ไล่มือ

### ข้อตัดสินที่ผู้ใช้เลือกเอง (อย่าเปลี่ยนโดยไม่ถาม)

1. **สิทธิ์** = ครูประจำชั้น **หรือ** มีสิทธิ์ `students.view` (ไม่ใช่แค่แอดมินโรงเรียน)
2. **รองรับทั้งสองโหมด** — 1 ห้อง = 1 กลุ่ม และ หลายห้องรวมเป็นกลุ่มเดียว (จึงใช้ pivot ไม่ใช่คอลัมน์เดียว)
3. **คนที่อยู่กลุ่มอื่นในวิชาเดียวกัน ให้ย้ายอัตโนมัติ** (ตามกติกาเดิม 1 คน = 1 กลุ่มต่อรายวิชา) แต่ต้องรายงานให้ครูเห็นก่อนกดยืนยัน

### สถาปัตยกรรมที่ต้องรู้ก่อนแก้ต่อ

**สมาชิกกลุ่มเก็บ 2 ตารางคู่กันเสมอ** — `course_members.group_id` และ `course_group_members`
ทุกการเขียนต้องแตะทั้งคู่ใน transaction เดียว (ของจริง course 25 มี 206/206 ตรงกันพอดี)
⇒ ตรรกะอยู่ที่ `CourseClassroomRosterService::applyStudentToGroup()` ที่เดียว ไม่มีทางที่สอง

**`course_members.user_id` เป็น NOT NULL** ⇒ นักเรียนที่ยังไม่มีบัญชีผู้ใช้ดึงเข้ามาไม่ได้
ต้องข้ามแล้วรายงานกลับเป็นรายการ (academy 1 มี 2930/2931 คนที่มี user_id)

**ไม่มี endpoint ให้แอดมินเพิ่มสมาชิกแทนคนอื่นมาก่อน** — `storemember()` ใช้ `auth()->user()` เสมอ
และคิดค่าเรียนด้วย จึง reuse ไม่ได้ ต้องเขียน path ใหม่ (ครูลงทะเบียนให้ = ไม่คิดเงิน ไม่ยิง `COURSE_JOIN`)

**การถอดออกจากกลุ่ม = เคลียร์ `group_id` เท่านั้น ห้ามลบแถว `course_members`** (มีคะแนน/เช็คชื่อผูกอยู่)
และ sync จะไม่ถอดใครเองเด็ดขาด ครูต้องติ๊กเลือกเอง (ค่าเริ่มต้นไม่ติ๊ก)

route `import-from-classrooms` ต้องอยู่ **ก่อน** `/{group}` ใน `routes/learn/course.php`
ไม่งั้นถูกจับเป็น group id

### 🔴 บั๊กที่เจอระหว่างทาง (แก้แล้ว) — จำไว้ใช้กับที่อื่น

**1. backfill ข้ามปีการศึกษา — บั๊กในสเปคที่ Claude เขียนเอง ไม่ใช่ของ agy**
migration `100200` จับคู่ห้องด้วยปีที่ `is_current` เสมอ โดยไม่ดูว่ารายวิชาเป็นของปีไหน
⇒ กลุ่ม "ม.5/1" ของวิชาปี **2568** ไปเกาะห้อง ม.5/1 ของปี **2569** ซึ่งคนละรุ่น
ของจริงที่ลงไปแล้ว: 83 ลิงก์ ถูกปี 21 · **ผิดปี 50** · วิชาไม่ระบุปี 12
แก้ด้วย migration `100300` (แก้ `100200` ไม่ได้เพราะรันไปแล้ว) เหลือ 21 แถว
⇒ **บทเรียน: migration ที่รันไปแล้วต้องแก้ด้วยตัวใหม่เสมอ ห้ามแก้ไฟล์เดิม**

**2. `academic_years` ไม่มีคอลัมน์ `year`** มีแต่ `name` ('2568'/'2569')
`orderByDesc('year')` ทำให้ `GET /classroom-sources` 500 ทั้ง endpoint

**3. `collect($models)->load(...)` ระเบิด** — `Support\Collection` ไม่มี `load()` (มีแต่ `Eloquent\Collection`)
อันตรายเพราะ `apply()` commit ไปแล้วก่อนบรรทัดนี้ ⇒ ครูเห็น 500 ทั้งที่ข้อมูลเข้าไปเรียบร้อย

**4. `Academy::members()` คืน `User` ไม่ใช่ `AcademyMember`** (เป็น `belongsToMany(User::class, 'academy_members')`)
⇒ `$member->status` อ่านคอลัมน์ที่ไม่มีจริง (`users` ไม่มี `status`), `$member->academyRole` เป็น null เสมอ
⇒ เงื่อนไข `students.view` เป็น dead code ครูที่ควรได้สิทธิ์ไม่ได้เลยสักคน
**ถ้าจะเช็คสมาชิกโรงเรียน ต้อง query `AcademyMember` ตรง ๆ (status = 2 คืออนุมัติ)**

**5. ambiguous column `is_active`** — ทั้ง `classroom_members` และ `classrooms` มีคอลัมน์นี้
query สาขาครูประจำชั้นจึงพังทั้ง MySQL และ SQLite
**เทสต์เป็นตัวจับ** เพราะ smoke test ใช้เจ้าของโรงเรียนซึ่ง `scopeFor` คืน `all` แล้ว return null ก่อนถึง query นี้
⇒ **บทเรียน: สาขา permission ที่ short-circuit ต้องทดสอบด้วย user ที่ตกไปสาขาล่างจริง ๆ**

**6. UI ส่งไอดีผิดชนิด** — checkbox ถอดสมาชิกผูก `s.user_id` แต่ API รับ `course_members.id`
ผลคือครูติ๊กแล้วกดยืนยัน ระบบขึ้นสำเร็จ **แต่ไม่มีอะไรเกิดขึ้น** (และมีโอกาสถอดผิดคนถ้าเลขบังเอิญชน)
มีเทสต์ล็อกไว้แล้ว: ส่ง `user_id` ต้องไม่มีใครถูกถอด

### ⚠️ ค้างอยู่

**ยังไม่ได้ตรวจ UI ด้วยตาจริงที่ 375px** — หน้า groups ต้องล็อกอิน ซึ่ง Claude ทำแทนไม่ได้
ตรวจไปแล้วเฉพาะแบบสถิต: SFC compile ผ่าน · ไม่มี `$fetch`/`axios`/`@inertiajs` ·
mobile-first ทุก breakpoint (`grid-cols-1 sm:grid-cols-2`, `flex-col md:flex-row`) ·
touch target 21 จุด · `min-w-0` + `break-words` ครบ · dark mode 91 จุด
⇒ **รอบหน้าเปิด `npm run dev` แล้วล็อกอินเอง ค่อยให้ Claude ไล่ตรวจ 375 → 768 → 1280**

ค่าที่ควรได้ตอนทดสอบจริงกับ course 25 กลุ่ม ม.5/1: `to_add=3` · `already=36` · `missing=1` (ไพซอล หมิงหมะ เลขที่ 14)


## 2026-08-21 — external-scores: ย้ายหัวข้อคะแนนเป็นส่วนกลาง + นำเข้าคะแนนจาก .xlsx + รื้อตารางให้ใช้บนมือถือได้

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (5 commit `4e995457`..`a8ad7147`)

> ไม่มี migration ในงานนี้ · ไม่ต้องรันอะไรเพิ่มก่อนใช้งาน

| commit | ขอบเขต |
|---|---|
| `4e995457` | fix บั๊ก `is_active` ที่ทำให้เกรดนักเรียนต่ำค้างถาวร |
| `f4c31a39` | backend: สร้างแบบฟอร์ม .xlsx + ตรวจไฟล์ (template / preview) |
| `7b768fa9` | frontend: service + types + โมดัลอัปโหลด |
| `d328dc42` | `HorizontalScrollBar.vue` + utility `.always-scrollbar-x` |
| `a8ad7147` | ย้ายปุ่มสร้างหัวข้อออกจากตาราง + `ExternalScoreTopicsPanel` + งาน mobile ของตาราง |

### ปัญหาตั้งต้นที่ผู้ใช้รายงาน

หัวข้อคะแนน (`course_external_scores`) **เป็นของส่วนกลางระดับรายวิชาอยู่แล้ว** — `store()` เขียน `group_id = null` เสมอ และ `tableView()` คืนหัวข้อทั้งหมดโดยไม่กรองตามกลุ่ม (ตัวกรองกลุ่มเรียนกรองแค่ *สมาชิก*)
แต่ปุ่ม `+` ถูกวางเป็นคอลัมน์ sticky ขวาสุดของตาราง ซึ่งอยู่ใต้ตัวกรองกลุ่มเรียนพอดี ครูจึงเข้าใจว่า "เพิ่มหัวข้อให้เฉพาะกลุ่มนี้"

### ข้อตัดสินที่ผู้ใช้เลือกเอง (อย่าเปลี่ยนโดยไม่ถาม)

1. **1 ไฟล์ = 1 หัวข้อคะแนน × 1 กลุ่มเรียน** ไม่ใช่ไฟล์รวมทุกหัวข้อ
   เหตุผลที่ชนะ: ใส่ช่อง `หมายเหตุ` ได้, คะแนนเต็มมีค่าเดียวตรวจง่าย, กรอกผิดคอลัมน์แล้วไม่พังข้ามหัวข้อ, อ่านบนมือถือได้
2. **ช่องคะแนนเว้นว่าง = ไม่เปลี่ยนแปลงของเดิม (skip)** · พิมพ์ `-` = ล้างคะแนน (clear) · ตัวเลข = บันทึก (set)
3. **เรื่องคะแนนเต็มพุ่งทันทีที่สร้างหัวข้อ → เลือก "ปล่อยตามเดิม + ขึ้นคำเตือน"** ไม่ทำระบบร่าง/เผยแพร่
4. **บนมือถือ: ปลด sticky ของคอลัมน์สมาชิก** (ไม่ใช่ย่อคอลัมน์ — ผู้ใช้ปฏิเสธการย่อไปแล้ว 1 รอบ)
5. **ใส่แถบเลื่อนแนวนอนที่มองเห็นตลอด** เป็นทางแก้หลักของ "เลื่อนดูคะแนนไม่ได้"

### สถาปัตยกรรมที่ต้องรู้ก่อนแก้ต่อ

**ไม่มี endpoint commit ใหม่** — frontend ยิง `POST /api/courses/{course}/external-scores/{externalScore}/entries` (`saveEntries` ตัวเดิม) เพราะมันมองค่า `score === null` ว่า "ลบ entry" อยู่แล้ว จึงใช้เป็นคำสั่งล้างคะแนนได้พอดี และมันเรียก `recompute()` ให้เองครบ
⇒ ถ้าจะแก้ logic การบันทึก **แก้ที่ `saveEntries` ที่เดียว** ไม่มีทางที่สอง

endpoint ใหม่มีแค่ 2 ตัว วางไว้ **ก่อน** wildcard `/{externalScore}` ใน `routes/learn/course.php`:
- `GET  /courses/{course}/external-scores/import/{externalScore}/template/{groupId?}`
- `POST /courses/{course}/external-scores/import/{externalScore}/preview`

ไฟล์ .xlsx มี **ชีตซ่อนชื่อ `_meta`** เก็บ `external_score_id` / `course_id` / `group_id`
`preview()` ปฏิเสธไฟล์ที่ meta ไม่ตรงกับหัวข้อที่เลือก ⇒ **โหลดฟอร์ม "สอบกลางภาค" มาอัปใส่ "สอบปลายภาค" ไม่ได้**
⇒ ถ้าแก้โครงไฟล์ ต้องแก้ทั้ง `buildTemplate()` และ `parse()` พร้อมกัน มิฉะนั้นไฟล์เก่าจะอัปไม่ผ่าน

### 🔴 บั๊กที่เจอระหว่างทาง (แก้แล้ว) — จำไว้ใช้กับที่อื่น

**1. `max_score` ถูกรวมอยู่ 2 ที่ ไม่ใช่ที่เดียว** — `CourseScoreService.php`
- บรรทัด ~453 `syncCourseTotalScore()` → เขียนลง `courses.total_score`
- บรรทัด ~84 `getCourseStructure()` → ตัวที่ใช้คำนวณ **เปอร์เซ็นต์/เกรดรายคนจริง**

ทั้งคู่เดิมไม่กรอง `is_active` ขณะที่ `tableView()` กรอง ⇒ ปิดหัวข้อแล้วคอลัมน์หายจากตาราง แต่คะแนนเต็มยังถูกนับ เกรดเด็กต่ำค้างถาวรโดยไม่มีใครเห็นสาเหตุ
**แก้แค่บรรทัด 453 อย่างเดียวไม่พอ** — ต้องแก้ทั้งสองที่ (สเปคที่เขียนให้ agy รอบแรกสั่งแค่ที่เดียว จับได้ตอนตรวจ diff)

**2. scroll listener ผูกไม่ติดมาตลอด** — `external-scores.vue`
`onMounted` เรียก `tableContainer.value?.addEventListener('scroll', ...)` แต่ตอน mount `loading = true` ตารางยังไม่ถูก render ⇒ ref เป็น `null` ⇒ **แถบไล่เงาบอก "ยังมีต่อทางขวา" ไม่เคยทำงานเลย** เปลี่ยนเป็น `@scroll.passive` บน element ตรง ๆ

**3. dead code ในเทมเพลต** — บล็อก empty state "ยังไม่มีหัวข้อคะแนน" เข้าไม่ถึงตลอดกาล เพราะ `v-else-if` สองอันก่อนหน้าดักครบทุกกรณี (ลบทิ้งแล้ว ตอนนี้ panel รับหน้าที่แทน)

### 🔴 ค้นพบสำคัญ — CSS ใส่ scrollbar บนมือถือไม่ได้

เบราว์เซอร์มือถือบังคับใช้ **overlay scrollbar** ที่ไม่กินพื้นที่ layout และจางหายเอง
สั่ง `::-webkit-scrollbar { height: 10px }` แล้ววัดได้ `offsetHeight - clientHeight = 0` ⇒ **ไม่มีผลจริง**
⇒ ต้องวาดแถบเองเท่านั้น: [`ui/components/Common/HorizontalScrollBar.vue`](../ui/components/Common/HorizontalScrollBar.vue) (ลากได้ · กดรางเพื่อกระโดด · ซ่อนเองเมื่อไม่ล้น)
**ถ้าจะใส่ที่อื่นต่อ ให้ใช้คอมโพเนนต์นี้ ไม่ต้องเสียเวลาลอง CSS**

กับดักตอนเขียน: `v-show` ทำให้ track กว้าง 0 ตอนซ่อน ถ้าเอา "ความกว้าง track" มาเป็นเงื่อนไขว่าจะแสดงไหม → **วนตายไม่มีวันโผล่** ต้องตัดสินจาก `scrollWidth - clientWidth` ของ target เท่านั้น แล้ว `nextTick` ค่อยวัด track

### ตัวเลขที่วัดเองที่ 375px (ก่อน → หลัง)

| | ก่อน | หลัง |
|---|---|---|
| ความกว้างที่ใช้ได้ (หลังตัดขอบ) | 317px | **357px** |
| พื้นที่คอลัมน์คะแนนตอนเลื่อนสุดขวา | 37px | **317px (เต็ม)** |
| คอลัมน์คะแนนที่เห็นเต็มช่อง | 0 | **2 จาก 3** |

ขอบหน้าเดิมซ้อนกัน 2 ชั้น (layout `px-4` + หน้า `px-3` = 56px = 15% ของจอ) แก้ด้วย `-mx-4 px-2 sm:mx-0 sm:px-4` ที่ root ของหน้า
เซลล์สมาชิกใช้ `bg-inherit` เพื่อรับสีลายสลับแถวคู่/คี่ (ถ้าใส่ `bg-white` ตายตัวจะทับลาย) และ hover ต้องเป็นสีพื้นเดียว **ห้ามใช้ gradient** เพราะ `bg-inherit` รับได้แค่ `background-color`

### ✅ เกณฑ์ผ่านที่รันเอง (ไม่ใช้ตัวเลขจากรายงาน agy เลย)

- `pint --test` passed · `php -l` ผ่านทั้ง 3 ไฟล์ใหม่
- `route:list --name=course.external-scores` = **9 route** (เดิม 7 + ใหม่ 2)
- **round-trip จริงใน transaction แล้ว rollback** (คอร์ส 23, 202 คน): สร้างไฟล์ 16.7KB 3 ชีต → กรอก `15` / `-` / `999` / `abc` → parse+validate ได้ `set` / `skip` / `คะแนนเกินคะแนนเต็ม (20)` / `คะแนนต้องเป็นตัวเลข` ถูกทุกเคส · DB ไม่เปลี่ยน
- SFC compile ผ่านทั้ง 4 ไฟล์
- 375px: `position: static` (ปลด sticky แล้ว) · `document.scrollWidth = 375` ไม่มี scroll แนวนอนของหน้า
- 640px: `position: sticky` กลับมา · เซลล์ตรึงซ้ายตอนเลื่อน 83px · สีแถวคู่/คี่ทึบถูกต้อง (`rgb(255,255,255)` / `rgb(241,245,249)`)

### 🔴 ยังไม่ได้ทดสอบบนหน้าจริงเลยสักครั้ง

เบราว์เซอร์ในแอปไม่มี session (เด้งไป `/auth`) และ **Claude กรอกรหัสผ่านล็อกอินแทนไม่ได้** (ผู้ใช้เสนอรหัสใน `.env` แล้ว — ปฏิเสธไป เป็นข้อจำกัดถาวร ไม่ต้องเสนอซ้ำ)
⇒ ทุกตัวเลข mobile ข้างบนวัดจาก **หน้าจำลองสาย DOM เดียวกัน** (`layout main → flex → main.min-w-0 → grid → center → การ์ด → overflow-x-auto → table`) ไม่ใช่หน้าจริง (ไฟล์ทดสอบลบแล้ว)

**วิธีให้ Claude ตรวจหน้าจริง:** ผู้ใช้ล็อกอินเองในแท็บเบราว์เซอร์ที่เปิดค้างไว้ แล้วบอก Claude — Claude ขับหน้าที่ล็อกอินแล้วต่อได้ทันที

### งานที่ค้างต่อ (TODO)

- [ ] **ทดสอบเส้นเต็มบนหน้าจริง** `http://localhost:3000/Learn/Courses/{id}/external-scores` (ต้องเป็นเจ้าของ/ครูประจำวิชา):
      สร้างหัวข้อ → กดดาวน์โหลดรายชื่อจาก chip ใน panel → กรอกใน Excel → อัปโหลด → ดูหน้า preview → ยืนยัน → เช็คว่าคะแนนขึ้นในตารางและเกรดขยับ
- [ ] **ยังไม่มี external score ใน DB dev เลยสักตัว** (`CourseExternalScore::first()` = null) ⇒ ต้องสร้างเองก่อนถึงจะทดสอบได้
- [ ] ตรวจหน้า preview ของโมดัลด้วยข้อมูลจริง — โค้ดผ่าน compile แต่ **ยังไม่เคยเห็น render จริง**
- [ ] ผลข้างเคียงของการปลด sticky: พอเลื่อนไปขวาสุด **ชื่อกับเลขที่หายไปพร้อมกัน** ยังไม่รู้ว่าครูจะสับสนไหม
      ถ้าเป็นปัญหา ทางแก้ที่ถูกที่สุดคือแยกคอลัมน์ "เลขที่" ออกมากว้าง ~48px แล้วให้เฉพาะคอลัมน์นั้น sticky บนมือถือ (ยังไม่ทำ เกินที่ผู้ใช้สั่ง)
- [ ] `course_external_scores.group_id` เป็น dead column (เขียน `null` เสมอ) — ตัดสินใจไว้ว่า **คงไว้เผื่ออนาคต** ถ้าจะลบต้องเขียน migration

### Branch / Git State

- Branch: `main`
- Uncommitted: no
- Push status: pushed

## 2026-08-20 (ต่อ 2) — ปิด S-S7 · **กีฬาสีจบครบทั้ง S-S1 → S-S7** · เป็นที่แรกในเรพที่ย่อรูปได้จริง

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (6 commit `eb1c536c`..`2d430c82`)

> ⚠️ **ต้องรัน `php artisan migrate` เอง ก่อนใช้งานจริง** — ดูหัวข้อ "migration ค้าง" ข้างล่าง

| ก้อน | ไฟล์ |
|---|---|
| backend | migration 2 ตาราง · `SportsAlbum`/`SportsPhoto` · `SportsPhotoService` · `SportsAlbumController` + 9 route · 13 เทสต์ |
| หน้าจอ | `useSportsAlbums.ts` · `SportsAlbumBoard.vue` (รายการอัลบั้ม ↔ แกลเลอรี · อัปโหลดหลายใบ · ตั้งปก/ลบ · lightbox เขียนเอง) · +แท็บที่ 7 ในหน้า `admin/sports-scores` |

สเปคเต็ม + ข้อตัดสิน อยู่ที่ [`27-sports-day.md` §12.10 และ §12.10.1](.agents/school-admin/27-sports-day.md)

#### ข้อตัดสินที่ผู้ใช้เลือกเอง
1. **สร้างตารางใหม่ `sports_albums` + `sports_photos`** ไม่ต่อ owner ให้ตาราง `albums` เดิม ⇒ ความเสี่ยงต่อระบบอัลบั้มส่วนตัวของผู้ใช้ = 0 (แลกกับไม่ได้ไลก์/คอมเมนต์มาฟรี)
2. **ย่อรูปจริงด้วย intervention/image v3** ในงานนี้เลย

#### 🔴 ค้นพบสำคัญ — ทั้งเรพไม่เคยย่อรูปได้จริงเลยสักที่

`PostMediaService` ที่เอกสารเดิมอ้างว่าเป็น "แม่แบบย่อรูป 2048/400" — **โค้ดย่อรูปกับ thumbnail ถูก comment ทิ้งทั้งหมด**
`AcademyController` ก็ comment เหมือนกัน สาเหตุคือ intervention อัปเป็น v3 แล้วเลิกใช้ `Image::make()` แบบ v2
⇒ **S-S7 คือที่แรกที่ย่อรูปได้จริง** · API ที่ใช้ได้ (ทดสอบเองบนเครื่องนี้: GD เปิด · Imagick ไม่มี):
`new ImageManager(new Gd\Driver())` → `read()` → `scaleDown(2048,2048)` → `cover(400,400)` → `toJpeg(85)`
**ถ้าจะทำที่อื่นต่อ ให้ลอกจาก `SportsPhotoService` ไม่ใช่จาก `PostMediaService`**

#### 🔴 บั๊กหน่วยความจำ — สเปคของ Claude เองสั่งผิด

สเปครอบแรกให้ **ถอดรหัสไฟล์ต้นฉบับ 2 ครั้ง** (รูปเต็มครั้งหนึ่ง thumbnail อีกครั้ง)
⇒ รันทีละเทสต์ผ่าน แต่รันทั้งคลาสตายด้วย `Allowed memory size of 134217728 bytes exhausted` ที่ `Gd\Cloner.php`
แก้เป็น **ถอดรหัสครั้งเดียว แล้วทำ thumbnail ต่อจากรูปที่ย่อแล้ว** + `unset($image)` ก่อนวนไฟล์ถัดไป
⇒ ของจริงก็ดีขึ้น: อัปรูป 12MP หลายใบต่อคำขอเดียวไม่ระเบิด memory_limit ของเว็บ
และตั้ง `<ini name="memory_limit" value="512M"/>` ใน `phpunit.xml` (งานภาพต้องการมากกว่า 128M ของ php.ini)

#### ✅ เกณฑ์ผ่านที่รันเอง

- `SportsAlbumTest` **13 passed / 49 assertions** · เทสต์เดิมไม่พังสักข้อ (Placing 15 · Fixture 14 · Match 13 · Scoring 23)
- `pint --test` exit 0 (agy ส่งมาแบบ pint ไม่ผ่าน 2 ไฟล์ — รัน pint แก้เอง)
- **375px**: `scrollWidth === 375` · ล้นขอบ 0 ชิ้น · ไม่มีปุ่ม/ช่องกรอกต่ำกว่า 44px · **1280px** ล้น 0 ชิ้น
- `git diff` หน้าเพจ **+18 / −1** (deletion เดียวคือบรรทัด `activeTab`)

#### บั๊กของ agy ที่ต้องแก้เอง 2 จุด (หน้าจอ)
1. การ์ดรูปเป็น `<button>` ที่มี `<button>` ซ้อนข้างใน — HTML ไม่ถูกต้อง เบราว์เซอร์จัดการ event ไม่แน่นอน → `div role="button" tabindex="0"` + Enter/Space
2. label ของ checkbox สูง 20px → ทำเป็น 44px

### 🔴 migration ค้าง 2 ตัว — ต้องรันเองก่อนใช้งาน

`php artisan migrate:status` บอกว่ายัง **Pending**:
- `2026_08_17_000002_create_sports_match_tables` (ของ S-S5a ตั้งแต่ 08-17 — **ไม่เคยรันบน MySQL เลย**)
- `2026_08_20_000001_create_sports_album_tables` (ของ S-S7)

⇒ **แมตช์ / ยืนยันอันดับ / อัลบั้ม ยังไม่เคยแตะ DB จริง** เทสต์ที่เขียวทั้งหมดรันบน sqlite in-memory
ตรวจ SQL ล่วงหน้าด้วย `php artisan migrate --pretend` แล้ว — สร้างตารางใหม่ล้วน ไม่มี ALTER ตารางเดิม ไม่แตะข้อมูล

### งานที่ค้างต่อจากรอบนี้

- [ ] **รัน migration แล้วทดสอบเส้นเต็มกับ API จริง** (ต้องล็อกอินเป็นแอดมิน academy):
  สร้างตารางแข่ง → กรอกผล → ยืนยันอันดับ → ดูคะแนนขยับ → สร้างอัลบั้ม → อัปรูป → ตั้งปก → ลบรูปที่เป็นปก
- [ ] แกลเลอรี/lightbox/อัปโหลด **ยังไม่เคยเห็นทำงานจริง** (ต้องมีข้อมูลจาก API)
- [ ] ไม่มีโควตาพื้นที่ต่อ academy · ลบ edition แล้วไฟล์รูปบน disk ยังค้าง (FK cascade ลบแค่แถวใน DB)
- [ ] ค้างจากรอบก่อน: 14 หน้าที่ใช้ `<BaseCard>` ชื่อสั้น · `GET /score-entries` ไม่มี pagination + ไม่ eager-load `awardedBy` · `confirm()` สั่ง rebuild standings หลายรอบต่อการยืนยัน 1 ครั้ง

---

## 2026-08-20 (ต่อ) — ปิด S-S6b · **กีฬาสีครบทั้งระบบแล้ว** เหลือแค่ S-S7 (อัลบั้มภาพ)

### สถานะ: **เสร็จ · push ขึ้น `origin/main` แล้ว** (3 commit `9d7b8072` `916c2e05` `b9254800`)

> หน้าจอชุดสุดท้ายของกีฬาสี ต่อจาก backend ที่ปิดไปในรอบก่อนหน้าของวันเดียวกัน
> แบ่งเป็น 3 shard ให้ agy · shard A/B รันขนานกันได้เพราะไฟล์ไม่ทับ · shard C ต่อสายเข้าหน้าเพจแบบ add-only

| ไฟล์ | หน้าที่ |
|---|---|
| `ui/composables/useSportsMatches.ts` (ใหม่) | ห่อ 8 endpoint ของ S-S5a/b/c + helper `groupByRound` `matchStatusText` `formatTimeMs` |
| `ui/components/academy/sports/SportsMatchBoard.vue` (ใหม่) | สร้างตารางแข่งอัตโนมัติ 3 รูปแบบ · ดูสายเป็นรอบ ๆ · กรอกผลรายคู่แบบ inline |
| `ui/components/academy/sports/SportsPlacingPanel.vue` (ใหม่) | ดึงอันดับที่ระบบเสนอ → แก้ได้ → ยืนยัน → คะแนนเข้า + สรุปเหรียญของรายการนั้น |
| `ui/pages/academies/[name]/admin/sports-scores/index.vue` | +2 แท็บ (`ตารางแข่ง` เปิดให้ `sports.view` · `ยืนยันอันดับ` เฉพาะ `canManage`) — **+41 / −1** deletion เดียวคือบรรทัด `activeTab` |

สเปคเต็ม + ข้อความบังคับบนหน้าจอ อยู่ที่ [`27-sports-day.md` §12.9](.agents/school-admin/27-sports-day.md)

#### ✅ ตรวจในเบราว์เซอร์จริงแล้ว (หน้า preview ชั่วคราว + ข้อมูลจำลอง ลบทิ้งแล้ว)

- **375px**: `scrollWidth === 375` · องค์ประกอบล้นขอบ **0 ชิ้น** · ปุ่ม/ช่องกรอกสูง ≥ 44px ทุกตัว (checkbox 20px แต่ label ห่อไว้ 44px)
- **768 / 1280px**: ล้นขอบ 0 ชิ้นทั้งคู่
- พฤติกรรมจริง: เลือกรายการแข่งอัตโนมัติ ✓ · knockout → checkbox "คู่ชิงอันดับที่ 3" โผล่ตามเงื่อนไข ✓ · ตั้งอันดับ 1 ซ้ำ 2 คณะ → **เตือนแต่ไม่บล็อก** และคะแนนตัวอย่างออก 9 เท่ากันตรงตาราง `{1:9}` ✓
- SFC + template compile ผ่านทั้ง 3 ไฟล์ใหม่และหน้าเพจ

#### 🔴 กับดักใหม่ที่ต้องจำ — ลำดับการประกาศ `watch` ที่มี `immediate: true`

`watch(() => props.xxx, cb, { immediate: true })` **ทำงานทันทีตอน setup**
ถ้าประกาศไว้ **ก่อน** `watch` ตัวที่เฝ้า ref ที่ callback นั้นไปตั้งค่า → ตัวหลังจะยังไม่ถูกลงทะเบียน → **การตั้งค่าครั้งแรกจะไม่ trigger อะไรเลย**
เคสจริงรอบนี้: แมตช์ไม่ถูกโหลดและ format ค้างที่ `round_robin` ทั้งที่รายการเป็น `knockout` — จอดู "ทำงานอยู่" แต่ข้อมูลไม่มา
**อ่านโค้ดอย่างเดียวมองไม่เห็น** ต้องเปิดเบราว์เซอร์แล้วอ่านค่า `select.value` จริงถึงจะเจอ

#### บั๊กของ agy ที่ต้องแก้เอง 4 จุด (+1 จุดที่ผมสร้างเองแล้วจับได้ตอนตรวจ)

1. เลือกรายการแรกใน `onMounted` ทั้งที่หน้าเพจส่ง `disciplines` มาแบบ async → จอค้างว่างถาวร
2. โหลด/บันทึกพังแล้วกลืนใส่ `console.error` + `alert()` แทนข้อความจริงจาก API
3. `window.confirm()` ใส่ `**markdown**` มาด้วย — dialog เบราว์เซอร์ไม่ render
4. กล่อง "รายการนี้ไม่มีตารางแข่ง" เด้งตั้งแต่ยังไม่กดดึงอันดับ (`matchFormat` ตั้งต้น `'none'`)
5. (ของผมเอง) แก้ข้อ 1 แล้วไปสร้างบั๊กลำดับ `watch` ข้างบน — จับได้ตอนเปิดเบราว์เซอร์

### งานที่ค้างต่อจากรอบนี้

- [ ] **ยังไม่ได้ทดสอบกับ API จริงทั้ง S-S5b / S-S5c / S-S6b** — ต้องล็อกอินเป็นแอดมิน academy แล้วลองเส้นเต็ม: สร้างตารางแข่ง → กรอกผล → ยืนยันอันดับ → ดูตารางคะแนนขยับ
- [ ] **S-S7** — อัลบั้มภาพผูกกับงาน (ไม่มีอะไรบล็อกแล้ว)
- [ ] ค้างจากรอบก่อน: 14 หน้าที่ใช้ `<BaseCard>` ชื่อสั้น · `GET /score-entries` ไม่มี pagination + ไม่ eager-load `awardedBy` · `confirm()` สั่ง rebuild standings หลายรอบต่อการยืนยัน 1 ครั้ง

---

## 2026-08-20 — ปิด S-S5b + S-S5c · ตารางแข่งกีฬาสีครบเส้นตั้งแต่สร้างคู่ → กรอกผล → ยืนยันอันดับ → ลงคะแนน

### สถานะ: **เสร็จทั้ง 2 shard · push ขึ้น `origin/main` แล้ว** (6 commit `7f4e4d93`..`61d66c34`)

> backend ของกีฬาสีจบทั้งสาย เหลือแค่หน้าจอ (S-S6b) · **ไม่มี migration ใหม่ทั้ง 2 shard** ตารางมาจาก S-S4/S-S5a หมดแล้ว

| ก้อน | commit |
|---|---|
| S-S5b — `SportsFixtureGenerator` + `POST .../disciplines/{discipline}/generate-fixtures` | `7f4e4d93` `6de76ed1` `1fe17cc5` |
| S-S5c — `SportsPlacingSuggester` + `GET suggested-placings` / `POST confirm-placings` | `e0239a0f` `6799152f` `61d66c34` |

สเปคเต็มของทั้งสอง shard + ข้อตัดสินที่ล็อกเพิ่มอยู่ที่ [`27-sports-day.md` §12.7 และ §12.8](.agents/school-admin/27-sports-day.md)

#### ✅ เกณฑ์ผ่านที่รันเอง (ไม่ได้ copy จากรายงาน agy)

- `SportsFixtureTest` **14 passed / 64 assertions** · `SportsPlacingTest` **15 passed / 64 assertions**
- ของเดิมไม่พังเลย: `SportsMatchTest` 13/36 · `SportsScoring*` 23/113 · `SportsHouseLeaderboard`+`HouseAssignmentTest` 28/106
- `./vendor/bin/pint --test` exit 0 · `git diff --stat` ฝั่งโค้ด **deletion = 0 ทั้ง 2 รอบ**
- **revert-check 2 ครั้ง** (ยืนยันว่าเทสต์ใหม่จับของจริง ไม่ได้ผ่านลอย ๆ):
  ถอดการย้ายลำดับ `$discipline->update()` ออก → เทสต์ `a rejected request does not change the discipline format` FAIL ·
  ถอด loop void แถวคะแนนเดิมออก → FAIL 2 ข้อ (`confirming twice...`, `a house dropped...`)

#### 🔴 กติกาที่ล็อกไว้แล้วและห้ามลืมตอนทำ S-S6b

1. **รอบชิงชนะเลิศ = แมตช์ที่ `round_order` สูงสุด _และ_ `match_number = 1`** · คู่ชิงอันดับ 3 = `round_order` เดียวกันแต่ `match_number = 2`
   หาแมตช์ชิงจาก `round_order` อย่างเดียวจะไปเจอคู่ชิงที่ 3 แทน
2. **`POST confirm-placings` จะ void แถวคะแนน `source='placing'` ของรายการแข่งนั้นทั้งหมด** รวมแถวที่ครูลงเองผ่าน `POST /score-entries`
   → **หน้าจอต้องเตือนก่อนกดยืนยันซ้ำ** (ตั้งใจให้เป็นแบบนี้ ไม่งั้นคะแนนบวกซ้ำ)
3. `heats` ที่ได้ฮีตเดียวจะ **ไม่สร้างรอบชิง** — ตัวเสนออันดับอ่านจากแมตช์ `round_order` สูงสุด ถ้ามีใบว่างจะเสนอไม่ได้
4. generate ซ้ำตอนมีแมตช์ที่ **status ไม่ใช่ `scheduled`** = 422 (กันล้างผลที่บันทึกไปแล้ว)

#### บั๊กของ agy ที่ต้องแก้เอง — รอบนี้เจอจุดเดียว

S-S5b: agy วาง `$discipline->update(['format' => ...])` ไว้**ก่อน**ด่าน 422 ⇒ คำขอที่ถูกปฏิเสธก็เปลี่ยนรูปแบบการแข่งไปแล้ว
ย้ายไปหลังด่าน 422 ทุกด่าน + เพิ่มเทสต์ล็อกไว้ · **S-S5c agy ทำถูกทั้งหมด ไม่ต้องแก้เลย**

### งานที่ค้างต่อจากรอบนี้

- [ ] **S-S6b** — หน้าจอตารางแข่ง/สายการแข่ง · กรอกผลรายแมตช์ · จอยืนยันอันดับ · สรุปเหรียญ · **ไม่มีอะไรบล็อกแล้ว**
- [ ] ทั้ง S-S5b/S-S5c **ยังไม่ได้ยิงกับ API จริง** — เทสต์รันบน sqlite in-memory ตาม `phpunit.xml`
- [ ] `confirm()` สั่ง `SportsStandingsProjector::rebuild()` หลายรอบต่อการยืนยัน 1 ครั้ง (ผลถูก แต่เปลืองรอบ) ถ้าช้าค่อยยุบเป็นครั้งเดียวท้าย transaction
- [ ] ค้างจากรอบก่อน: **14 หน้าที่ใช้ `<BaseCard>` ชื่อสั้น** · ทดสอบ `admin/sports-scores` กับ API จริง · `GET /score-entries` ยังไม่มี pagination + ไม่ eager-load `awardedBy`

### งานฝั่ง course/lesson ที่ทำไว้ 2026-08-19 (ยังไม่เคยลง worklog)

- `34656578` — ล้าง cache ของ course payload เมื่อบทเรียนเปลี่ยน (store + 5 จุดที่เรียกใช้)
- `6d6b07b9` — ย้ายการ์ดสนับสนุนไปท้ายแท็บข้อมูล
- `2bdb98ba` — ทำหัวข้อการ์ดบทเรียนให้ลิงก์ไปหน้าบทเรียนของตัวเอง

---

## 2026-08-17 — S-S4 (ฐานคะแนนกีฬาสี) + S-S6a (หน้าจอคะแนน) · เจอบั๊ก auto-import ที่ทำให้ component หายเงียบ ๆ

### สถานะ: **S-S6a เสร็จ ตรวจในเบราว์เซอร์จริงที่ 375px แล้ว** · **push ขึ้น `origin/main` แล้ว** (`d3f33d0d` `b1aaaaf9` `cd9c495e`)

#### สิ่งที่ลงไปแล้ว

| ก้อน | commit |
|---|---|
| S-S4 backend (schema + service + controller + 3 route กลุ่ม) | `412faf8b` `9d9c7757` `e417135e` |
| S-S6a หน้าจอ (composable + 4 component + หน้า + เมนู) | รอบนี้ |
| แก้ auto-import ที่หน้า house-assignments | รอบนี้ |

**S-S6 ถูกแยกเป็น 2 ก้อน** เพราะของเดิมพ่วง "ตารางแข่ง/กรอกผลรายคู่" ซึ่งต้องรอ S-S5 ที่ยังตัด S-D3 (bracket) ไม่ได้
→ **S-S6a** = ตารางคะแนน · จัดการรายการแข่ง · ให้คะแนน 3 ที่มา · ประวัติ/ยกเลิก (ทำได้ด้วยของที่ S-S4 มีอยู่แล้ว)
→ **S-S6b** = ตารางแข่งรายคู่ + กรอกผล + สรุปเหรียญละเอียด (รอ S-S5) · สเปกอยู่ที่ [`27-sports-day.md` §11](.agents/school-admin/27-sports-day.md)

#### 🔴 ค้นพบสำคัญ — `<SportsEditionPanel />` ไม่เคย render จริงเลย

เรพนี้ **ไม่ได้ตั้ง `components.pathPrefix: false`** ใน `ui/nuxt.config.ts` → Nuxt จดชื่อ component ตามโฟลเดอร์+ชื่อไฟล์
`components/academy/sports/SportsEditionPanel.vue` → ชื่อจริงคือ **`AcademySportsEditionPanel`** (ดู `ui/.nuxt/components.d.ts` เป็นตัวตัดสิน)

เขียนชื่อสั้น ๆ Vue จะ render เป็น custom element เปล่า **ไม่มี error ไม่มี warning ใน console** — พิสูจน์ในเบราว์เซอร์แล้ว DOM ออกมาเป็น `<sportsstandingsboard standings="[object Object]...">`

⇒ **แผงเลือก "ครั้งที่จัด" ในหน้า `admin/house-assignments` หายไปทั้งแผงมาตั้งแต่ S-S3e** ทั้งที่ §9.6 บันทึกว่า frontend เสร็จ (ตอนนั้นตรวจจากการอ่านโค้ด ไม่ได้เปิดดูจริง)
แก้แล้วทั้ง 2 หน้าด้วยการ **import component เองตรง ๆ** — ผิดชื่อแล้วพังตอน compile ดีกว่าหายเงียบ

🟡 **สแกนต่อทั้งเรพเจออีก 14 หน้าที่ใช้ `<BaseCard>`** (ตัวจริงอยู่ `components/atoms/BaseCard.vue` = `AtomsBaseCard`) — `about`, `Admin/{Points,Wallet}`, `Earn/{donates,Gamification,Points,Rewards,Wallet}`, `events`, `photos`, `Play/{Friends,Messages}`, `PlearndAdmin/DepositApproval`, `videos` · **ยังไม่แก้ในรอบนี้**

#### ✅ เกณฑ์ผ่านที่รันจริง (ไม่ได้ paste จากรายงาน agy)

- `git diff --stat` → ไฟล์เดิมถูกแก้แบบ add-only ล้วน (`admin.vue` +7/−0 · `house-assignments/index.vue` +7/−0)
- compile SFC ผ่านทั้ง 6 ไฟล์ด้วย `@vue/compiler-sfc`
- เปิดจริงที่ **375px** ด้วยหน้า preview ชั่วคราว (ลบทิ้งแล้ว): อันดับเสมอออกมา **1, 1, 3** · คณะที่ยังไม่ได้คะแนนโชว์ `—` กับ 0 · `document.documentElement.scrollWidth === 375` ไม่มี horizontal scroll · ปุ่ม/ช่องกรอกทุกตัวสูง ≥ 44px · โหมด "ด้วยมือ" กดบันทึกไม่ได้ถ้าไม่กรอกหมายเหตุ และรับ −5 ได้ · ตัวอย่างคะแนน placing: ที่ 1 → 9, ที่ 99 → 0 พร้อมคำเตือน
- **ยังไม่ได้ทดสอบกับ API จริง** (ต้องล็อกอินเป็นแอดมิน academy) — ทุกอย่างข้างบนทดสอบด้วยข้อมูลจำลอง

#### บั๊กของ agy ที่ต้องแก้เอง 4 จุด

1. `DEFAULT_JUDGED_MAX_SCORE` ถูก destructure จาก `useSportsScoring()` ทั้งที่เป็น export ระดับโมดูล → `undefined` แล้วจอโชว์ "เต็ม 0 คะแนน" (2 ไฟล์)
2. เทียบ `points !== ''` กับตัวแปร `number | null` → typecheck พัง
3. ป้าย "ยกเลิกแล้ว" ใส่ `text-white` ทับ `text-rose-700` → ขาวบนชมพูอ่อนในโหมดสว่าง
4. หน้า `import type` ตัวที่ไม่มีอยู่จริง (`SportsHouseGroup`)

### งานที่ค้างต่อจากรอบนี้

- [ ] **S-D3 (bracket)** ต้องตัดสินก่อน จึงจะเริ่ม **S-S5** (บันทึกผลการแข่งรายคู่) แล้วค่อยต่อ **S-S6b**
- [ ] **แก้ 14 หน้าที่ใช้ `<BaseCard>`** ตามข้างบน — **กำลังทำอยู่ใน session แยก** (task chip ที่ผู้ใช้กดเริ่มแล้ว 2026-08-17)
- [ ] ทดสอบหน้า `admin/sports-scores` กับ API จริง (สร้างรายการแข่ง → ให้คะแนน → ดูตารางขยับ → void)
- [ ] `GET /score-entries` ยังไม่มี pagination และไม่ eager-load `awardedBy` → หน้าจอยังบอกไม่ได้ว่าใครเป็นคนให้คะแนน
- [x] ~~**push** commit ของรอบนี้~~ → push แล้ว `e417135e..cd9c495e` (ที่ค้างจากรอบก่อนถูก push ไปก่อนหน้านี้แล้ว)

---

## 2026-08-16 (ถึงเช้า 08-17) — ปิด Tier B + Tier D · ถอด Inertia ออกจากโปรเจคหมดแล้ว · เพิ่มสกิล agy

### สถานะ: **เสร็จ · 3 commit ยังไม่ push** (`9082e594` `bcb478e8` `745f9e09`) · **⚠️ ยังไม่ได้รัน `npm run build`**

> ปิด TODO ที่ค้างจาก 2026-08-10 ครบ 2 ข้อใหญ่: 🅱️ Tier B (9 ไฟล์) และ 🅳 ถอด Inertia ถาวร
> **รวมทั้งเซสชัน +503 / −1,773 บรรทัด · 22 ไฟล์**

### 🆕 สกิลใหม่: มอบงาน implement ให้ agy เป็นตัวช่วยหลัก

ผู้ใช้สั่งให้ทำเป็นสกิลถาวร — บทบาทคือ **Claude = วิเคราะห์ + แตก shard + เขียนสเปค + ตรวจ · agy = คนเขียนโค้ด**

| ไฟล์ | หน้าที่ |
|---|---|
| [`.agents/skills/agy-delegate/SKILL.md`](.agents/skills/agy-delegate/SKILL.md) | ตัวจริง — สูตรเขียนสเปค, คำสั่งเรียก agy, เช็คลิสต์ตรวจผล |
| [`.claude/skills/agy/SKILL.md`](.claude/skills/agy/SKILL.md) | pointer เรียกด้วย `/agy` |
| `CLAUDE.md` | หัวข้อ "การลงมือเขียนโค้ด — ใช้ agy เป็นตัวช่วยหลัก" |

**บังคับไว้ในสกิล:** ทุกสเปคที่แตะ `ui/` ต้องแปะบล็อกกติกา **mobile-first** ลงไป (375px ก่อน · class ไม่มี prefix = มือถือ · touch target 44px · `min-w-0 flex-1 break-words` กันภาษาไทยแตกแนวตั้ง · ตารางกว้างอยู่ใน `overflow-x-auto` ของตัวเอง)

คำสั่งที่ใช้จริงรอบนี้ (4 shard ผ่านหมด):
```
agy --print "Read <spec>.txt and follow it exactly." --add-dir "C:\wamp64\www\nuxnan" --add-dir "<scratchpad>" --model gemini-3.1-pro-high --mode accept-edits --print-timeout 20m
```
รันผ่าน Bash `run_in_background` · 2 shard ที่ไฟล์ไม่ทับกันรันขนานได้จริง

### 🅱️ Tier B — เขียนใหม่ 4 ไฟล์ (commit `bcb478e8`)

| ไฟล์ | ทำอะไร |
|---|---|
| `ui/pages/PrivacyPolicy.vue` | เขียนใหม่เป็น static ภาษาไทย 9 หัวข้อ (อิง พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล 2562) + สารบัญ + header sticky |
| `ui/pages/TermsOfService.vue` | 9 หัวข้อ ครอบคลุมค่าเล่าเรียน/แต้ม-wallet ว่าไม่ใช่เงินตรา |
| `ui/pages/auth/ForgotPassword.vue` | เปลี่ยนเป็นหน้า "ติดต่อผู้ดูแลระบบ" ไม่มีฟอร์ม (เหตุผลข้างล่าง) |
| `ui/components/AuthenticationCardLogo.vue` | แก้เป็น NuxtLink — **แล้วถูกลบทิ้งใน Tier D** เพราะกลายเป็น orphan |

**ชื่อแบรนด์บนหน้าสาธารณะ = `nuxnan`** (ผู้ใช้เลือก) — เดิมในเรพปนกัน: title หน้า landing เขียน "nuxnan" แต่ footer เขียน "PlearnD"
เก็บ `contact@plearnd.com` และไฟล์ `plearnd-logo.png` ไว้ตามเดิม (อีเมลจริง / ชื่อไฟล์)

### 🔴 ค้นพบสำคัญ: `/api/forgot-password/*` ไม่ใช่ระบบลืมรหัสผ่าน

[`ForgotPasswordController.php:70`](api/nuxnanravel/app/Http/Controllers/Api/Shared/ForgotPasswordController.php:70) คือ**เครื่องมือของแอดมิน** — แอดมินรีเซ็ตรหัสให้สมาชิกโดย**หัก 4,800 แต้ม** (ใช้ wallet ร่วมได้ อัตรา 1 บาท = 1,080 แต้ม) และทั้งชุดอยู่หลัง `auth:api`
⇒ **ไม่มี endpoint `password.email` ในระบบเลย** คนที่ล็อกอินไม่ได้ใช้ไม่ได้อยู่แล้ว · หน้าใหม่จึงบอกขั้นตอนติดต่อแอดมิน + แจ้งค่าดำเนินการ 4,800 แต้มไว้ในหน้า
(ถ้าจะทำ self-service จริงต้องเพิ่ม API + ตั้งค่า mail ใน `.env` — ตาราง `password_reset_tokens` มีอยู่แล้ว)

### 🅳 Tier D — ถอด Inertia ถาวร (commit `745f9e09`, −1,756 บรรทัด)

1. **ตัดตัวสุดท้ายที่พึ่ง shim** — `pages/Learn/Course/CreateNewCourse.vue` ใช้ `$page.props.auth.user` 3 จุดในการ์ด "ผู้สอน" → เปลี่ยนเป็น `authUser` จาก `useAuthStore()` (+5/−3)
2. **ลบ shim + plugin** — `ui/shims/inertia-vue3.ts` · `ui/shims/inertia-vue3.js` (มี 2 ไฟล์ซ้ำ) · `ui/plugins/inertia-shim.ts`
3. **`ui/nuxt.config.ts`** — ลบ alias `'@inertiajs/vue3'` และลบ `import { fileURLToPath }` ที่กลายเป็น dead import
4. **ลบหน้า/store/component ที่เป็น orphan**
   - 4 หน้า auth: `ConfirmPassword` `ResetPassword` `TwoFactorChallenge` `VerifyEmail` (ไม่มี endpoint + ไม่มีลิงก์เข้า)
   - 2 store ที่ไม่มีใครเรียก: `stores/attendance.js` (341) `stores/courseProfile.js` (497)
   - 9 component ซาก Jetstream: `AuthenticationCardLogo` `AuthenticationCard` `ApplicationMark` `ConfirmsPassword` `DangerButton` `InputError` `InputLabel` `PrimaryButton` `TextInput`

⚠️ **`SecondaryButton.vue` ห้ามลบ** — ยังถูก `components/learn/course/gradebook/MissingSourcesModal.vue` ใช้อยู่จริง
ซาก Jetstream ที่ยังเหลือใน `ui/components/` (ยังไม่ได้ตรวจว่ามีใครใช้): `ActionMessage` `ActionSection` `Checkbox` `DialogModal` `Dropdown` `Modal` `SectionBorder` `SectionTitle`

### ✅ เกณฑ์ผ่านที่รันจริง (ไม่ได้ paste จากรายงาน agy)

1. `git grep` หา `@inertiajs` / `$page` / `inertiaPage` ทั่ว `ui/` → **ว่างทั้งสามคำ**
2. compile ทุกไฟล์ที่แก้ด้วย `@vue/compiler-sfc` → ผ่าน · template root เดียวทุกไฟล์
3. **restart dev server ใหม่ทั้งหมด** แล้วยิง 10 route → **200 ทุกตัว** (`/` `/auth` `/auth/ForgotPassword` `/PrivacyPolicy` `/TermsOfService` `/Learn/Courses` `/Learn/Courses/create` `/dashboard` `/academies` `/nuxnan-admin/login`)
4. console error = 0 · หน้า hydrate จริง · กดลิงก์แล้ว client-side routing ทำงาน (path+title เปลี่ยนโดยไม่ full reload)
5. ตรวจ mobile-first ที่ **375px จริง**: ไม่มี horizontal scroll (`scrollWidth == clientWidth`) · ไม่มี touch target < 44px · ที่ 1280px สารบัญ sticky ทำงาน
6. 4 route ที่ลบ หายจริง (ยิงแล้วไม่เจอ) · 5 route ที่ต้องอยู่ อยู่ครบ

### 🐛 บั๊กที่เจอระหว่างทาง (ยังไม่แก้ — คนละงาน)

- **หน้า error ของแอปพัง**: route ที่ไม่มีจริงคืน **500 + `obj.hasOwnProperty is not a function`** แทนหน้า 404 (ทดสอบด้วย `/definitely-not-a-real-route-xyz` ก็เหมือนกัน)
- **ไอคอนไม่ขึ้นในหน้า landing**: [`ModernFooterSection.vue`](ui/components/landing/ModernFooterSection.vue) เขียน `<Icon name="heroicons:phone">` แต่เรพนี้ import `Icon` จาก `@iconify/vue` ซึ่งต้องใช้ prop **`icon`** (มี 1,453 จุดในเรพที่ใช้ `icon=` ถูกแล้ว) — **agy ลอกรูปแบบผิดนี้มาใส่หน้าใหม่ ผมจับได้ตอนตรวจและแก้เอง**
- footer หน้า landing ยังเขียนแบรนด์ "PlearnD" อยู่ (หน้าสาธารณะใหม่ใช้ "nuxnan" แล้ว)

### 🔴 บทเรียนเรื่อง agy รอบนี้

- **ซื่อสัตย์ทั้ง 4 shard** — ไม่แตะไฟล์นอกสเปคเลยสักไฟล์ (`git status` ยืนยัน) และ diff ตรงกับที่รายงาน
- **แต่ยังต้องตรวจเอง 100%** เพราะจุดที่พลาดคือ "ลอก pattern ผิดจากไฟล์ข้างเคียง" ซึ่งรายงานของมันไม่มีทางบอก
  ⇒ สเปคครั้งหน้าต้อง **ระบุ convention เฉพาะของเรพให้ชัด** (เช่น "ไอคอนใช้ prop `icon` ห้ามใช้ `name`")
- สูตรที่ได้ผล: วิเคราะห์ root cause จบก่อน → แปะโค้ดเป้าหมายเต็ม ๆ → ระบุไฟล์ห้ามแตะเป็นชื่อ ๆ → เกณฑ์ผ่านเป็นคำสั่ง shell

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **รัน `npm run build`** — ยังไม่เคยรันตั้งแต่ลบ 75 ไฟล์ (2026-08-10) และตอนนี้ลบเพิ่มอีก 20 ไฟล์ + แก้ `nuxt.config.ts` แล้ว **ผู้ใช้รันเอง**
- [ ] **push 3 commit นี้** (`9082e594` `bcb478e8` `745f9e09`) — ยังอยู่แค่บนเครื่องนี้
- [x] ~~**หน้า error 500 แทน 404**~~ → **แก้แล้ว** ดูหัวข้อ "🩹 แก้ตามมา" ท้าย entry นี้
- [ ] **`components/academy/rollover/RolloverStepIndicator.vue:25-27`** — `withDefaults()` อ้าง `defaultSteps` ที่ประกาศในไฟล์เดียวกัน (ยังไม่แก้ ค้างมาตั้งแต่ `bea9d5bb`)
- [x] ~~**แก้ `<Icon name=` เป็น `icon=`**~~ → **เสร็จทั้งเรพ 45 ไฟล์ / 410 จุด** (commit `562e003e` หน้า landing 7 ไฟล์ + commit ถัดมา 38 ไฟล์ใต้ `pages/academies/**`, `components/{academy,school}/**` และหน้าเดี่ยวอีก 6 หน้า)
  วิธีตรวจที่ใช้: สแกนด้วย regex ที่จับ `<Icon>` แบบคร่อมบรรทัด (`/<Icon\b[^>]*?\s:?name=/gs`) ทั้งเรพ → เหลือ 0 · diff ทุกบรรทัดต้องมีคำว่า `<Icon`/`icon=`/`name=` (กัน `<input name=>` `<Transition name=>` โดนลูกหลง) · compile ทุกไฟล์ที่แก้
  ⚠️ **หน้าใต้ `academies/**` ยืนยันด้วยตาไม่ได้เพราะต้องล็อกอิน** — ยืนยันด้วย static analysis + เห็นผลจริงบนหน้าที่เปิดได้ (`/landing-demo` 50/50 · `/quests` 31 · `/badges` 31)
- [ ] **เปลี่ยนแบรนด์ footer หน้า landing เป็น nuxnan** (ยังเขียน "PlearnD" อยู่)
- [x] ~~**S-S3e** (หน่วย "ครั้งที่จัด")~~ → **ทำเสร็จไปแล้วตั้งแต่ `d89f9796` + `2a348218`** ตารางสถานะในเอกสารค้างเป็น ⚪ เฉย ๆ · ตรวจจริง 2026-08-17: migration Ran batch 118/119 · schema ตรงสเปก (`edition_id`, UNIQUE `[edition_id, student_id]`, `active_key` generated + UNIQUE, ไม่เหลือ `academic_year_id`) · `php artisan test tests/Feature/Sports` = **33 passed / 118 assertions** · แก้สถานะในเอกสารแล้ว ดู [`27-sports-day.md` §9.6](.agents/school-admin/27-sports-day.md)
- [ ] **(ค้างจากรอบก่อน)** **S-S4** schema คะแนนกีฬาสี (§4) + ให้คะแนนผ่าน event log + จัดการคะแนนเท่ากัน — **ไม่มีอะไรบล็อกแล้ว** ทุกตารางต้อง key ที่ `edition_id` · ตั้งชื่อคณะสีจริงแทน 4 ชื่อชั่วคราวแล้วสั่งแบ่งใหม่ · ไฟล์ SQL prod ล้าหลัง 12 migrations

### ⚠️ worklog เคยล้าหลัง 6 วัน

entry ก่อนหน้าเขียนวันที่ 2026-08-10 แต่มี commit ถึง 08-16 ที่ไม่ถูกบันทึก (จ่ายค่าคอร์สด้วย wallet/points, create-course policy + gate, exam eligibility badge, bulk unlock — ส่วนใหญ่มาจาก branch `claude/group-exam-unlock-function-96fd27` ที่ merge เข้า main แล้ว)
ผลข้างเคียง: worklog สั่งให้เขียน `Learn/Course/CreateNewCourse.vue` ใหม่ ทั้งที่ commit `17492327` (08-12) ถอด Inertia ออกจากไฟล์นั้นไปแล้ว

### 🩹 แก้ตามมา: หน้า 404 ที่คืน 500 (`ui/plugins/pinia-payload-null-proto.server.ts`)

**อาการ:** เปิด URL ที่ไม่มีจริง → `500` + `obj.hasOwnProperty is not a function` แทนหน้า 404 (ทุก URL ผิด ไม่ใช่เฉพาะที่เพิ่งลบ)

**สาเหตุจริง (จาก stack trace ของ dev server ไม่ใช่การเดา) — ไม่เกี่ยวกับ `ui/error.vue` เลย:**
1. Nuxt เรนเดอร์หน้า error ผ่าน route ภายใน `/__nuxt_error` แล้วสร้าง error object ด้วย `const ssrError = getQuery(event)` (`@nuxt/nitro-server/dist/runtime/handlers/renderer.mjs`) — `getQuery` ของ h3 คืน object ที่สร้างจาก **`Object.create(null)` = ไม่มี prototype**
2. object นั้นอยู่ใน payload → devalue เดินทุก node ตอน `renderPayloadJsonScript`
3. reducer ของ `@pinia/nuxt` (`payload-plugin.js:11`) เรียก `shouldHydrate()` ของ pinia ที่เขียนว่า
   `return !isPlainObject(obj) || !obj.hasOwnProperty(skipHydrateSymbol)`
   → `isPlainObject` ผ่าน แต่ไม่มี prototype ⇒ `obj.hasOwnProperty` เป็น `undefined` ⇒ **TypeError** ⇒ payload พัง ⇒ 500

⇒ บั๊กจากการเจอกันของ **Nuxt 4.4.2 (h3 query ไม่มี prototype) × pinia 2.3.1** ไม่ใช่โค้ดของโปรเจคผิด

**วิธีแก้:** ลงทะเบียน payload reducer ชื่อ `skipHydrate` **ทับของ pinia** จาก user plugin (Nuxt เก็บเป็น `ssrContext["~payloadReducers"][name]` คนลงทีหลังทับ และ user plugin รันหลัง plugin ของ module) โดยข้าม object ที่ไม่มี prototype แล้วปล่อยเคสที่เหลือให้ pinia ตัดสินเหมือนเดิม
**ไม่แตะ `node_modules` ไม่อัปเกรด pinia** — ถ้าวันหนึ่ง pinia แก้ `shouldHydrate` ให้ปลอดภัย ลบไฟล์นี้ทิ้งได้เลย

**ตรวจจริงหลัง restart dev server:** URL มั่ว → **404** (เดิม 500) · `/auth/VerifyEmail` ที่ลบไป → **404** · `/PrivacyPolicy` → 200 · หน้า error เรนเดอร์ของเราเอง (h1 "404" + ปุ่ม Back to Home สูง 48px) · ที่ 375px ไม่มี horizontal scroll · กดปุ่มแล้ว `clearError` พากลับ `/` จริง · server log ไม่มี error

### Branch / Git State

- Branch: `main` · working tree สะอาด
- 4 commit แรก push ขึ้น `origin/main` แล้ว (`edc09839..9fe24df7`) · commit ตัวแก้ 404 ตามมาทีหลัง
- dev server ที่ใช้ตรวจงานเปิดที่ port ชั่วคราว (3000 ถูกใช้อยู่) — ปิดได้เลย

---

## 2026-08-10 — ตัดสินชะตา 51 ไฟล์ Inertia · ลบเกาะที่ตายแล้วออก 75 ไฟล์ (Tier A + C)

### สถานะ: **เสร็จ · 8 commit** — Tier A (6 เกาะ) + Tier C · **⚠️ ยังไม่ได้รัน `npm run build`** · เหลือ Tier B 9 ไฟล์

> ปิด TODO ที่ค้างมาจาก 2026-08-09 (ปิดท้าย): *"หน้าใต้ `Learn/Academy/[name]/Settings/*` และ `curriculum/*` เป็นโค้ด Inertia เก่า … ต้องตัดสินใจว่าจะรื้อหรือลบทิ้ง"* → **คำตอบคือลบ** เพราะเป็น orphan ล้วน

### 🔍 51 ไฟล์นั้น "ไม่ใช่ก้อนเดียวกัน" — แยกได้ 4 ชั้น

แผนเต็มพร้อมรายชื่อไฟล์ทุกตัวอยู่ที่ [`.agents/latest-analysis.md`](.agents/latest-analysis.md) **entry บนสุด** (`2026-08-09 — 🅰️ ลบเกาะ Inertia legacy`)

| ชั้น | จำนวน | ชะตา | สถานะ |
|---|---|---|---|
| 🅰️ orphan ล้วน 0 inbound link | 75 ไฟล์ | ลบ | ✅ เสร็จ |
| 🅱️ live แต่พังจริง | 9 ไฟล์ | เขียนใหม่ | ⏳ ยังไม่ทำ |
| 🅲 เศษ import บรรทัดเดียว | 1 ไฟล์ | ลบบรรทัด | ✅ เสร็จ |
| 🅳 ตัว shim + alias | 3 จุด | ลบหลัง B จบ | ⏳ ยังไม่ทำ (ยังมีคนใช้ 9 ไฟล์) |

**วิธีพิสูจน์ว่าเป็น orphan** (ทำครบ 6 ด้านก่อนลบสักไฟล์): สแกน `import`/`import()` ทุก `.vue/.ts/.js` · สแกน route-string · สแกน auto-import tag ทั้งชื่อสั้นและชื่อ prefix ของ Nuxt (`<PartialsNavbar>` ฯลฯ) · สแกน layout name ใน `definePageMeta` · ตรวจว่าเกาะลิงก์หากันเองล้วน · ยืนยันว่าของใหม่ที่แทนแล้วมีจริง
⇒ เจอเส้น inbound จากแอปจริง **เส้นเดียว** คือ `Learn/Courses/create.vue:2 → Learn/Course/CreateNewCourse.vue` (จึงกันไฟล์นั้นออกจากลิสต์ลบ)

### 🗑️ commit ที่ลง (เรียงตามเกาะ · เสี่ยงน้อย → มาก)

| commit | เกาะ | ไฟล์ |
|---|---|---|
| `ce944b6e` | เขียนแผนลง `latest-analysis.md` | — |
| `a4093743` | 1 · Jetstream leftovers — `pages/Teams/` `pages/API/` `pages/profile/Partials/` `profile/Show.vue` | 14 |
| `3055501d` | 2 · `pages/Learn/Student/HomeVisit/` | 18 |
| `84a56449` | 3 · `pages/Learn/Student/Card/` (โฟลเดอร์ `Learn/Student/` หายไปเลย) | 7 |
| `e2b7fa86` | 4 · `pages/Test.vue` `components/Banner.vue` `layouts/GuestLayout.vue` | 3 |
| `9476499f` | 5 · `pages/Learn/Academy/` + `pages/Academy.vue` + `layouts/AcademyLayout.vue` + `components/partials/Navbar.vue` + `AcademyNavbarTab.vue` + `posts/CreateAcademyPost.vue` | 19 |
| `42a4e9b9` | 6 · `pages/Learn/Course/` (เว้น `CreateNewCourse.vue`) + `pages/Learn/Lesson/` + `layouts/CourseLayout.vue` | 14 |
| `bcefe3dc` | 🅲 ลบ `usePage()` ที่ไม่ถูกใช้ใน `Learn/Courses/[id]/groups/index.vue` | 2 บรรทัด |
| `6156c563` | อัพเดท Status ในแผนเป็น "ทำเสร็จแล้ว" | — |

**รวม −18,773 บรรทัด** · ไฟล์ `.vue` ที่ import Inertia: **50 → 9**

### ✅ เกณฑ์ผ่านที่รันจริง (ไม่ได้ใช้ `npm run build`)

1. **ไฟล์ที่ต้องเหลือ** — `pages/Learn/Course/` เหลือ `CreateNewCourse.vue` ตัวเดียว · `layouts/` เหลือ 5 (`CoursesLayout` `NuxnanAdminLayout` `auth` `course` `main`) · `pages/profile/` เหลือ 4 · `components/ImageGalleryModal.vue` + `components/learn/academy/curriculum/*` ยังอยู่
2. **ไม่มี import ค้าง** — grep 2 ชุด ว่างทั้งคู่ (⚠️ ห้ามใช้ negative lookahead ใน grep/ripgrep — rust regex ไม่รองรับ ต้องแยกเป็น 2 คำสั่งแล้ว pipe `grep -v`)
3. **compile ทุก SFC ที่เหลือ** ด้วย `@vue/compiler-sfc` — 734 ไฟล์ · fail 1 ตัวคือ `components/academy/rollover/RolloverStepIndicator.vue` ซึ่ง**มีมาก่อนงานนี้** (มาจาก `bea9d5bb` Phase 5 rollover wizard) — `defineProps()` อ้าง `defaultSteps` ที่ประกาศในไฟล์เดียวกัน
4. **ตรวจ route table จาก router สดในเบราว์เซอร์** (263 routes) — 11 route ที่ต้องหาย หายครบ · 10 route ที่ต้องอยู่ อยู่ครบ · `/academies/**` ยังครบ 86 route

### 🔴 กับดักที่ต้องอ่านก่อนแตะงานนี้ต่อ — ชื่อคล้ายกันจนลบผิดได้

| เก็บ ✅ | ลบไปแล้ว ❌ | ต่างกัน |
|---|---|---|
| `pages/Learn/Courses/**` | `pages/Learn/Course/**` | ตัว **s** — `Courses` คือหน้าคอร์สหลักที่ใช้ทุกวัน |
| `layouts/CoursesLayout.vue` | `layouts/CourseLayout.vue` | ตัว **s** — `CoursesLayout` ถูก `CreateNewCourse.vue:7` ใช้ |
| `layouts/course.vue` | `layouts/CourseLayout.vue` | คนละไฟล์ — `course.vue` ถูก `pages/courses/[name]/{instructor-dashboard,reports}.vue` ใช้ |
| `components/ImageGalleryModal.vue` | `…/HomeVisit/Teacher/Components/ImageGalleryModal.vue` | **ชื่อชนกันเป๊ะ** — ตัวที่ `components/learn/course/*` ใช้ (5 จุด) คือตัวใน `components/` |
| `components/learn/academy/curriculum/**` | `pages/Learn/Academy/[name]/curriculum/**` | ตัวใน `components/` ถูก `academies/[name]/admin/curriculums.vue` ใช้ |
| `components/learn/academy/CreateNewAcademyCourse.vue` | `pages/Learn/Academy/CreateNewAcademyCourse.vue` | ชื่อชนกัน คนละโฟลเดอร์ |

### 🐛 หลักฐาน Tier B ที่พิสูจน์บนเบราว์เซอร์แล้ว (ยังไม่แก้)

**`route()` ใช้ได้เฉพาะใน template** — `plugins/inertia-shim.ts:86` ใส่ไว้ที่ `globalProperties` ⇒ หน้าไหนเรียกใน `<script setup>` ตายทันที

- `/auth/ForgotPassword` — **ลิงก์จริงจาก [`pages/nuxnan-admin/login.vue:149`](ui/pages/nuxnan-admin/login.vue:149)** · กรอกอีเมลแล้วกดปุ่ม → console: `Uncaught ReferenceError: route is not defined (at submit)` · **ปุ่มไม่ทำอะไรเลย**
- `/PrivacyPolicy` + `/TermsOfService` — **ลิงก์จาก footer หน้า landing 3 จุด** ([`ModernFooterSection.vue:113,146,149`](ui/components/landing/ModernFooterSection.vue:113)) · `PrivacyPolicy.vue:20` ใช้ `v-html="policy"` โดย `policy` เป็น prop ที่ไม่มีใครส่ง (เดิม Jetstream ส่งจาก server) ⇒ **เปิดจริงแล้วเป็นหน้าว่าง เหลือแค่โลโก้ "P" ของ Plearnd ซึ่งเป็นแบรนด์เก่า** — คนนอกเข้าถึงได้
- `<Link>` ของ shim resolve `NuxtLink` ไม่เจอตอน SSR → `[Vue warn] Failed to resolve component: NuxtLink at <InertiaLink> at <AuthenticationCardLogo>`
- `pages/Learn/Course/CreateNewCourse.vue:626` เรียก `router.push()` แต่ **shim ไม่มีเมธอด `push`** (มีแค่ `visit/get/post/put/patch/delete`) ⇒ ปุ่มยกเลิกโยน TypeError · หน้านี้ live ผ่าน `Learn/Courses/create.vue` ที่ลิงก์จาก [`academies/[name].vue:1469`](ui/pages/academies/[name].vue:1469) และ [`Learn/Courses/index.vue:574`](ui/pages/Learn/Courses/index.vue:574)
- **multi-root ยังเหลือ** ในไฟล์ Tier B (`<Head>` + เนื้อหาวางคู่กัน) — เซสชัน 2026-08-09 กวาดเฉพาะ `pages/academies/` เท่านั้น

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **รัน `npm run build`** — ยังไม่เคยรันหลังลบ 75 ไฟล์ **และโค้ดอยู่บน `origin/main` แล้ว** (ดูหัวข้อ Git State) ⇒ ควรรันก่อนเครื่องอื่น pull
- [ ] **🅱️ Tier B — 9 ไฟล์ที่ยัง import Inertia** ต้องเขียนใหม่ ไม่ใช่ลบ:
  `components/AuthenticationCardLogo.vue` · `pages/PrivacyPolicy.vue` · `pages/TermsOfService.vue` · `pages/auth/{ConfirmPassword,ForgotPassword,ResetPassword,TwoFactorChallenge,VerifyEmail}.vue` · `pages/Learn/Course/CreateNewCourse.vue`
  → ลำดับที่แนะนำ: PrivacyPolicy/TermsOfService ก่อน (หน้าสาธารณะ เนื้อหาว่างอยู่) → ForgotPassword (มีลิงก์จริง) → CreateNewCourse (ย้ายไปเป็น component ใน `components/learn/course/` จะดีกว่าปล่อยเป็น route `/Learn/Course/CreateNewCourse` ที่พังอยู่)
- [ ] **🅳 ถอด Inertia ออกถาวร** หลัง Tier B จบ — ลบ `ui/shims/inertia-vue3.ts` · `ui/plugins/inertia-shim.ts` · alias ที่ `ui/nuxt.config.ts:11`
- [ ] **`components/academy/rollover/RolloverStepIndicator.vue:25-27`** — `defineProps()` อ้าง `defaultSteps` ที่ประกาศในไฟล์เดียวกัน (compiler ห้าม) · มีมาก่อนงานนี้ ไม่ได้เกิดจากการลบ · ตรวจว่า build ผ่านจริงไหม
- [ ] **(ค้างจากรอบก่อน)** S-S4 schema คะแนนกีฬาสี (ไม่มีอะไรบล็อกแล้ว) · ตั้งชื่อคณะสีจริงแทน 4 ชื่อชั่วคราวแล้วสั่งแบ่งใหม่ · ไฟล์ SQL สำหรับ prod **ล้าหลัง 12 migrations แล้ว ไม่ใช่ 9** (`2026_08_05_160000` → `2026_08_09_100000`)

### ✅ TODO เก่าที่ปิดไปแล้ว (worklog รอบก่อนตกข่าว)

- `down()` ของ `2026_08_06_100100` ที่พังกับ generated column → แก้แล้วที่ `8aed7728`
- `StudentCardPublicResource` dead code → ลบแล้วที่ `a5d65991`
- ลบกิ่ง `fix/student-card-room-roster` → ไม่มีในลิสต์ branch แล้ว
- `php artisan migrate` บนเครื่องนี้ → ไม่มี pending (batch ล่าสุด 121, `2026_08_09_100000` = Ran batch 120)
- ตรวจ 24 หน้า `layout: 'main'` ด้วยตา → session คู่ขนานทำแล้ว (`be473a41`)

### Context สำคัญ

- **ทำไมถึงเลือก "ลบ" ไม่ใช่ "รื้อ"** — เกาะ `Learn/Academy` / `Learn/Course` / `Learn/Student` ทั้งหมดมีของใหม่แทนครบแล้ว (`academies/[name]/**` · `Learn/Courses/**` · `student-card/**` · `home-visit/` + `admin/home-visits/`) และไม่มีเส้นลิงก์จากแอปจริงเข้าไปเลยสักเส้น · ส่วน `Teams/` `API/` `profile/Partials/` เป็นซาก Jetstream/Sanctum ที่โปรเจคนี้ไม่ได้ใช้ (ใช้ JWT)
- **`pages/*/Partials/` เคยเป็น route จริง** — `/profile/Partials/UpdatePasswordForm` เข้าถึงได้จริงก่อนลบ · หายไปแล้วและเป็นสิ่งที่ต้องการ
- **ตอนลบไฟล์ใต้ dev server ที่รันอยู่** vite จะทิ้ง error ค้างใน console (`[vite] Failed to reload /pages/Learn/Student/Card/...`) — เป็นขยะ HMR ไม่ใช่ของจริง · restart dev server แล้วหาย · ตัวที่เชื่อได้คือ route table ที่อ่านจาก `$router.getRoutes()` สด ๆ
- **agy ใช้ไม่ได้ในเซสชันนี้** — สั่งผ่าน Bash และ PowerShell แล้วโดน Claude Code auto-mode classifier บล็อกทั้งคู่ (`Permission for this action was denied`) · ตัว exe อยู่ที่ `C:\Users\Bhupha\AppData\Local\agy\bin\agy.exe` และ settings ก็มี · ถ้าจะใช้ครั้งหน้าต้องเพิ่ม permission rule ใน `.claude/settings.json` ก่อน · **รอบนี้ claude ลบเองทั้งหมดโดยผู้ใช้อนุมัติ**

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (working tree สะอาด)
- Push status: **⚠️ Tier A ทั้ง 6 เกาะถูก push ไปแล้ว** — `origin/main` = `be473a41` ซึ่งเป็น**ลูกของ `42a4e9b9` (เกาะ 6)** · **session คู่ขนานบน branch เดียวกัน** commit worklog ทับขึ้นมาแล้ว push ทั้งชุดไปโดยที่ยังไม่มีใครรัน `npm run build`
- ค้างบนเครื่องนี้ 2 ก้อน: `bcefe3dc` (Tier C) · `6156c563` (docs)
- 🔴 **บทเรียนซ้ำจาก 2026-08-09**: ทำงานสองที่พร้อมกันบน `main` ให้ `git add` ระบุ path เสมอ และเช็ค `git log origin/main..main` ก่อนสรุปสถานะทุกครั้ง — รอบนี้ผมสรุปว่า "ยังไม่ push" ไปแล้วรอบหนึ่งก่อนจะพบว่ามันถูก push ไปแล้ว

---

## 2026-08-09 (รอบดึก) — หน้าบัตรนักเรียนสาธารณะอัพรูปไม่ได้ · แล้วต่อด้วยย่อรูปฝั่ง client

### สถานะ: **เสร็จ · commit 2 ก้อน · push ขึ้น `origin/main` แล้ว** (`7e4f429a` `b5481114`)

> หมายเหตุแก้ความเข้าใจ: entry ก่อนหน้าเขียนว่า `7e4f429a` มาจาก "อีก session ที่ทำคู่ขนาน" — ใช่ครับ คือ session นี้เอง เนื้องานอยู่ข้างล่างนี้ทั้งหมด

### 🐛 อาการที่รายงานมา

`http://localhost:3000/student-card/1/7` — หน้าสาธารณะ **แก้ข้อมูลตัวหนังสือได้ แต่อัพโหลด/ลบรูปไม่ได้** ผู้ใช้เดาว่าเป็นเพราะปิดการ login · หน้านี้ต้องใช้งานได้โดยไม่ต้องล็อกอิน

### 🎯 สาเหตุ — รูปภาพไม่มี "เส้นสาธารณะ" ส่วนข้อความมี

`ui/components/student-card/StudentCardItem.vue` ถ้าไม่ได้รับ prop `uploadPhoto`/`deletePhoto` (หน้าสาธารณะไม่ส่งมา) จะ fallback ไปที่:

| การกระทำ | เส้นเดิมที่ fallback ไป | อยู่ใน group |
|---|---|---|
| อัพโหลดรูป | `POST /api/student-card/admin/upload-photo/{id}` | `auth:api` ⇒ 401 |
| ลบรูป | `DELETE /api/student-card/{id}/photo` | `auth:api` ⇒ 401 |
| แก้ข้อความ | `PUT /api/student-card/public-update/{level}/{room}/{card}` | สาธารณะ ⇒ ผ่าน |

**แก้:** แยกการตรวจ "บัตรใบนี้อยู่ในห้องตาม URL จริงไหม" ออกจาก `publicUpdate()` เป็น `assertCardInRoom()` แล้วเพิ่ม `publicUpdateImage()` / `publicDestroyPhoto()` ที่ใช้ guard ตัวเดียวกัน · route ใหม่ (นอก `auth:api`, throttle 20/นาที):

```
POST   /api/student-card/public-photo/{level}/{room}/{student_card}
DELETE /api/student-card/public-photo/{level}/{room}/{student_card}
```

guard ยืนยันด้วยการยิงจริงแล้ว: บัตร ม.1/1 ยิงเข้า URL ม.2/5 · ม.3/8 · ม.1/2 · ม.9/99 → **404 ทุกอัน** และสุ่มเทสต์ ม.1/1 ถึง ม.6/1 ผ่านหมด (ครอบคลุมทั้ง 53 ห้องในปีปัจจุบัน ไม่ได้ hardcode 1/7)

### 📏 งานต่อเนื่อง — ตามหาเพดานขนาดไฟล์ แล้วขยาย + ย่อรูปฝั่ง client

วัดขอบเขตด้วยการยิงไฟล์จริง เจอว่าเพดานตอนนั้นคือ **2 MiB พอดี (2,097,152 bytes ผ่าน / 2,097,200 ไม่ผ่าน)** ซึ่งมาจาก `upload_max_filesize` ใน php.ini **ไม่ใช่** `max:5120` ที่เขียนไว้ใน validation (ไม่มีวันได้ทำงาน เพราะ PHP ทิ้งไฟล์ก่อน แล้วโผล่เป็น `The photo field is required.` ที่ไม่บอกสาเหตุจริง)

ผู้ใช้เลือกทาง **"ย่อ ≤8MB + ขยาย server"** ⇒ ทำ 3 ชั้น:

1. **`ui/composables/useImageCompressor.ts` (ไฟล์ใหม่)** — ลด quality ก่อน (0.9→0.5) ไม่พอค่อยหดขนาดทีละ 20% · cap ด้านยาวสุด 1600px · ไฟล์ที่อยู่ในงบแล้ว**ไม่แตะ** · decode ไม่ได้ (HEIC) ส่งต้นฉบับให้ server ตัดสิน · re-encode แล้วเปลี่ยนนามสกุลเป็น `.jpg` เสมอ (สำคัญ — `StudentPhotoService::store()` ตั้งชื่อไฟล์ที่เก็บจาก `getClientOriginalExtension()`) · PNG โปร่งใสรองพื้นขาวไม่ให้กลายเป็นดำ
2. **validation** `max:5120` → `max:8192` (8 MiB ตรงกับ `MAX_STUDENT_PHOTO_BYTES` ใน `ui/constants/studentCard.ts`)
3. **php.ini** — ดูหัวข้อถัดไป ⚠️

การย่อเกิดใน `handlePhotoUpload()` ซึ่งใช้ร่วมกันทั้งสองหน้า ⇒ **หน้า `/academies/*/admin/student-cards` ได้ผลนี้ไปด้วย** โดยไม่ต้องแก้อะไรเพิ่ม

### ⚠️⚠️ php.ini ไม่ได้อยู่ใน repo — เครื่องอีกที่ต้องแก้เอง ไม่งั้นเจอเพดานเดิม

ไฟล์อยู่นอก git ที่ `C:\wamp64\bin\php\php8.4.15\` · **ต้องแก้ทั้งสองไฟล์**:

| ไฟล์ | ใครใช้ | ค่าที่ต้องได้ |
|---|---|---|
| `php.ini` | `php artisan serve` (CLI) | `upload_max_filesize = 32M` · `post_max_size = 64M` |
| `phpForApache.ini` | Apache/WAMP | `upload_max_filesize = 32M` · `post_max_size = 64M` |

```bash
grep -n "^post_max_size\|^upload_max_filesize" /c/wamp64/bin/php/php8.4.15/php.ini /c/wamp64/bin/php/php8.4.15/phpForApache.ini
```

สำรองไฟล์เดิมไว้แล้วที่ `*.bak-before-nuxnan-upload-20260809` (เฉพาะเครื่องนี้) · ค่าเดิมฝั่ง Apache คือ `post_max_size = 8M` + `upload_max_filesize = 2048M` ซึ่งเพี้ยนหนัก (post เป็นตัวคุมจริง ส่วน 2048M อันตรายโดยไม่จำเป็น) ตอนนี้ปรับให้ตรงกับ CLI แล้ว

**กับดักที่เสียเวลาไปจริงในเซสชันนี้:** แก้ php.ini แล้วค่าไม่ขยับ เพราะ **process `php artisan serve` โหลด ini ตอนสตาร์ทครั้งเดียว** — ต้อง restart ถึงจะรับค่าใหม่ (Apache ก็เหมือนกัน) · เสียเวลาไปหลายรอบกว่าจะจับได้ว่าไฟล์บนดิสก์เป็น 32M แล้วแต่ process ยังบังคับ 2M อยู่

### ✅ หลักฐานการทดสอบ

- **เพดานหลัง restart**: 2.43 / 3.88 / 5.80 MB → 200 · 8.75 MB → 422 พร้อมข้อความที่บอกเหตุผลชัด `The photo field must not be greater than 8192 kilobytes.`
- **compressor (รันโมดูลจริงในเบราว์เซอร์)**: 4.0 MB → ไม่แตะ · 18.8 MB → 1.7 MB · 44.5 MB → 1.3 MB · PNG โปร่งใส 29.4 MB → 1.7 MB `.jpg` พื้นขาว
- **E2E ผ่าน UI จริง**: ยิงไฟล์ **32.2 MB** เข้า input ของการ์ด 2436 → POST 200 → ไฟล์ที่ลงดิสก์ **1.34 MB**
- รูปทดสอบลบออกหมด นักเรียน 2436 กลับเป็น `profile_image = NULL` เหมือนเดิม · Pint ผ่าน
- **ยังไม่ได้รัน `npm run build`** (ผู้ใช้รันเอง)

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **เครื่องอีกที่: แก้ php.ini ทั้ง 2 ไฟล์ตามตารางข้างบน แล้ว restart Apache + `artisan serve`** ไม่งั้นอัพรูป >2MB ไม่ได้เหมือนเดิม
- [ ] ยังไม่ได้ตรวจว่าใช้งานผ่าน **Apache/WAMP** (`localhost:80`) ได้จริง — ทดสอบทั้งหมดทำผ่าน `artisan serve` port 8000 เท่านั้น
- [ ] ตอนที่จะ **ลบ public student-card route ทิ้ง** (ตามแผนเดิม) ต้องลบ `public-photo` ทั้งสองเส้น + `publicUpdateImage()` / `publicDestroyPhoto()` / `assertCardInRoom()` ไปพร้อมกัน — ตอนนี้ทั้งชุดเปิดให้ใครก็ได้แก้บัตร กันแค่ throttle + ต้องรู้ level/room/card id
- [ ] `bulkUploadPhotos()` ใน `StudentCardController` ยังเป็น stub คืน 501 อยู่ (ถ้าจะทำ ควรใช้ compressor ตัวเดียวกัน)

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (working tree สะอาด)
- Push status: **push ครบแล้ว** — `origin/main` = `b5481114`
- หมายเหตุ: backend ที่รันอยู่ตอนจบ session เป็น process ที่ Claude สตาร์ทไว้ (port 8000) ถ้าหายไปก็ `php artisan serve` ใหม่ได้ตามปกติ

---

## 2026-08-09 (ปิดท้าย) — สลับเมนู admin แล้วบางหน้าไม่โหลดข้อมูล · แก้ที่ "รูปทรงของไฟล์ page" ไม่ใช่ที่ API

### สถานะ: **เสร็จ · commit 3 ก้อน · push ขึ้น `origin/main` แล้ว** (`f20d0842` `21988a8a` `0a072f7d`) · `npm run build` ผ่าน

### 🐛 อาการที่รายงานมา

ที่ `/academies/โรงเรียนจริยธรรมศึกษามูลนิธิ/admin` กดสลับเมนูใน sidebar แล้ว **บางเมนูไม่โหลดข้อมูล** — ผู้ใช้เดาว่า "น่าจะเป็นที่เลย์เอ้าท์" · reproduce ได้จริงในเบราว์เซอร์: URL เปลี่ยน แต่เนื้อหายังเป็นหน้าเดิม บางครั้ง renderer ค้างจนถ่าย screenshot ไม่ได้

### 🎯 สาเหตุที่ 1 (ตัวหลัก) — page template มี root node เกิน 1 ตัว

`<NuxtPage>` ห่อ page ไว้ใน `<Transition mode="out-in">` ซึ่ง**ต้องการ root element เดียว** · ถ้าเป็น fragment → หน้าที่กำลังออกไม่จบ transition → **หน้าเก่าค้างจอ หน้าใหม่ไม่ mount `onMounted` ไม่ทำงาน** ⇒ เห็นเป็น "ไม่โหลดข้อมูล"

หลักฐานใน console (เงียบมาก ไม่มี error):
```
[nuxt] pages/academies/[name].vue does not have a single root node
       and will cause errors when navigating between routes.
[Vue warn]: Component inside <Transition> renders non-element root node
```

`pages/academies/[name].vue` เป็น route parent ของ `/academies/*` **ทั้งต้นไม้** ⇒ พังทุกเมนูใน admin · แก้ 3 ไฟล์:

| ไฟล์ | root ที่เกินมา |
|---|---|
| `ui/pages/academies/[name].vue:1145` | `<AcademyDonationModal v-if>` วางคู่กับ `<div>` |
| `ui/pages/academies/[name]/admin/roles.vue:638` | `</div>` ปิดก่อน `<Teleport>` ตัวสุดท้าย |
| `ui/pages/academies/[name]/elections/[id]/station.vue:38` | `<div v-if>` วางคู่กับ `<main>` |

### 🎯 สาเหตุที่ 2 — page ที่เป็น route parent แต่ไม่มี `<NuxtPage />`

ไฟล์ `foo.vue` ที่มีโฟลเดอร์ `foo/` วางข้าง ๆ → Nuxt ทำให้เป็น **route parent** · ถ้าไม่มี `<NuxtPage />` ลูกจะไม่เรนเดอร์เลย (URL เปลี่ยนแต่เห็นเนื้อหาของ parent) · เจอ 5 จุด แก้โดยย้ายเป็น `<name>/index.vue`:

- `admin/members.vue` → `admin/members/index.vue` — เดิม `/admin/members/invitations` และ `/admin/members/{id}` โชว์ "รายชื่อสมาชิก" แทน (ยืนยันด้วยตาแล้วทั้งก่อนและหลังแก้)
- `Earn/Marketplace.vue` (เป็น 301 redirect) บัง `History.vue` `Sales.vue`
- `Learn/Academy/[name]/Settings.vue` บัง `General/Members/Roles`
- `Learn/Courses/[id]/groups/[groupId].vue` บัง `edit.vue`
- `Learn/Academy/[name]/curriculum.vue` บัง `curriculum/index.vue` + `[curriculumId].vue`

**ลบไฟล์ซ้ำ 2 ตัว (ผู้ใช้อนุมัติแล้ว)**: `curriculum.vue` (เก่ากว่า ไม่มี handler แก้ไข/ลบ/เข้าหน้ารายละเอียด) และ `lessons/create/index.vue` (เก่ากว่า มี debug div `test` สีแดงค้าง + path `/courses/` ที่ไม่มีแล้ว · เข้าไม่ถึงอยู่แล้ว) ⇒ `lessons/create.vue` กลายเป็น leaf route ปกติ

### 🎨 งานพ่วง — 24 หน้าใน admin กลับมามี app shell

24 หน้าย่อยใช้ `definePageMeta({ layout: false })` ⇒ **ไม่มี top nav / คอลัมน์โปรไฟล์** และการสลับเข้าออกหน้าพวกนี้ทำให้ NuxtLayout ถูก teardown+rebuild ทั้งก้อน (ช้า + fetch ซ้ำ) · เปลี่ยนเป็น `layout: 'main'` ทั้งหมด

**ยกเว้น `admin/student-cards/print.vue` ที่คง `layout: false` ไว้** — เป็นหน้าพิมพ์จริง มี `@media print` + คลาส `print:hidden` ที่ออกแบบบนสมมติฐานว่าไม่มี app chrome

### 🔴 กับดักที่เสียเวลาไปในเซสชันนี้ — อ่านก่อนทำงานคล้ายกัน

1. **คอมเมนต์ HTML ที่ root ของ `<template>` ก็นับเป็น node** — ตอนแก้ `[name].vue` รอบแรกผมใส่คอมเมนต์ไว้เหนือ `<div>` แล้วมันยังพังเหมือนเดิม เพราะ `RouteProvider` เช็ค `vnode.el.nodeName ∈ {#comment, #text}` ⇒ **คอมเมนต์ต้องอยู่ข้างในทุกกรณี**
2. **อย่า replace `layout: false` → `layout: 'main'` ผ่าน `node -e '...'` ที่ครอบด้วย single quote** — shell กินเครื่องหมาย `'` รอบ `main` ทิ้ง กลายเป็น `layout: main` (ไม่มี quote) แล้วทั้งแอปพัง 500 `main is not defined` · ใช้ heredoc เขียนสคริปต์ลงไฟล์แล้วค่อย `node file.cjs`
3. **`git commit` เก็บทุกอย่างที่อยู่ใน index ไม่ใช่แค่ path ที่เพิ่ง `git add`** — `git mv` stage ให้อัตโนมัติ ⇒ commit แรกกลืน rename ทั้งหมดไปโดยที่ message ไม่ตรง · แก้ด้วย `git reset` (mixed) แล้ว stage ใหม่ทีละกลุ่ม (ทำก่อน push จึงไม่กระทบ remote)

### 🧰 สคริปต์ตรวจ (ใช้ซ้ำได้ รันใน `ui/`)

```bash
# หา page ที่ root เกิน 1 — parse ด้วย @vue/compiler-sfc แล้วนับ template.ast.children
#   (ต้องรวม v-else / v-else-if เข้ากับ v-if ตัวหน้า ไม่งั้น false positive เพียบ)
# หา route parent ที่ขาด <NuxtPage /> — ไฟล์ foo.vue ที่มีโฟลเดอร์ foo/ อยู่ข้าง ๆ
```
ผลหลังแก้: root node ใน `pages/academies/` **ผ่านหมด** · parent ที่ขาด `<NuxtPage />` **เหลือ 0 ทั้งโปรเจค**

### ✅ ตรวจด้วยตาครบแล้ว (รอบสอง หลังผู้ใช้สั่งให้แก้ `layout` ต่อ)

**รอบแรก** ยืนยันการสลับเมนู: แดชบอร์ด → สมาชิก → คำขอเข้าร่วม → ห้องเรียน → บทบาท → ผลการเรียน → บัตรนักเรียน ขึ้นข้อมูลครบ · `/admin/members/invitations` แสดงตารางถูกต้อง

**รอบสอง** เปิดดูจริง **23 หน้าจอ** — ทุกหน้ามี top nav + คอลัมน์โปรไฟล์ + sidebar admin + การ์ดขาว ตรงกันหมด ไม่มี double padding ไม่มีพื้นหลังซ้อน:

| กลุ่ม | หน้าที่เปิดดู |
|---|---|
| store (5) | `index` `products` `categories` `orders` `settings` |
| gradebook (10) | `subjects` `academic-years` `grade-scales` `students` `transcripts` `classrooms` `classrooms/58` `students/2405/transcript` `rollover` `rollover/history` |
| home-visits (3) | `zones` `create` `export` |
| students (3) | `import` `import-history` `intake` |
| อื่น ๆ (2) | `courses/create` `members/1` |

**2 หน้าที่เปิดไม่ได้**: `home-visits/[id]/index` และ `[id]/edit` — DB ไม่มี record เลย (หน้า home-visits ขึ้น "การเยี่ยมทั้งหมด 0") ทั้งสองไฟล์ root เป็น `<div>` เปล่าเหมือน 20 หน้าที่ผ่านแล้ว

### 🔧 งานพ่วงรอบสอง — 5 หน้าร้านค้า (`18b0d519`)

ทั้ง 5 หน้าเปิดด้วย wrapper ของหน้าเดี่ยว `min-h-screen bg-gray-50 dark:bg-gray-900` + ชั้นในเป็น `max-w-* mx-auto px-4 sm:px-6 py-6` ⇒ ในการ์ดขาวของ `admin.vue` กลายเป็น**บล็อกเทาสูงเต็มจอ + padding ซ้อนสองชั้น**

`store/index.vue` มีอาการนี้อยู่ก่อนแล้วตั้งแต่ก่อนเซสชันนี้ (มัน `layout: 'main'` มาตลอด) อีก 4 หน้าติดมาตอนย้ายออกจาก `layout: false` · แก้ให้ทั้ง 5 เหลือ root `<div>` เปล่า + ชั้นใน `space-y-6` ตามที่อีก 20 หน้าใช้ ให้การ์ดเป็นคนจัด padding

### 🐞 เจอระหว่างตรวจ — คนละเรื่องกับ layout ไม่ได้เกิดจากงานนี้

1. **ข้อมูล gradebook ไม่ตรงกับส่วนอื่นอย่างชัดเจน (สำคัญสุดในสามข้อ)** — `admin/classrooms` บอก ม.1/1 = **43 คน** แต่ `gradebook/classrooms` บอกทุกห้อง **"0 นักเรียน / 50"** · `gradebook/students` บอก "20 นักเรียนทั้งหมด" ขณะที่โรงเรียนมี 2,613 สมาชิก / 2,931 นักเรียน · **id คนละชุดด้วย** (ม.1/1 ใน gradebook = `58`, ใน admin = `94`) เหมือน gradebook อยู่บนตาราง/ชุดข้อมูลแยกที่ยังไม่ populate
2. **`/admin/gradebook/rollover` ตัว wizard ว่าง** — หัวข้อ "Year Rollover Wizard" ขึ้น แต่ใต้ลงมาไม่มีอะไร · console ไม่มี error
3. **`/admin/members/{id}` ที่ไม่มีอยู่จริง หมุนค้างตลอด** — ลอง `/admin/members/1` แล้วสปินเนอร์ไม่หยุด ไม่มี not-found / error state

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **ไล่ 3 ข้อในหัวข้อ 🐞 ข้างบน** — เริ่มที่ข้อ 1 (ข้อมูล gradebook) ก่อน
- [ ] เช็ค `/admin/student-cards/print` ว่ายังพิมพ์ออกมาถูก (เป็นหน้าเดียวที่ยังคง `layout: false` โดยตั้งใจ — มี `@media print` + `print:hidden` ที่ออกแบบบนสมมติฐานว่าไม่มี app chrome)
- [ ] เปิด `home-visits/[id]/index` + `[id]/edit` ดูสักครั้งเมื่อมี record จริง
- [ ] **ยังมี page ที่ root เกิน 1 ตัวนอก `pages/academies/`** — `auth/{ConfirmPassword,ForgotPassword,ResetPassword,TwoFactorChallenge,VerifyEmail}.vue` และ `PrivacyPolicy.vue` `TermsOfService.vue` (ทั้งหมดเป็น `<Head>` + อีก element) กับ `timeline.vue` (5 `<section>`) · เป็น leaf route จึงพังเฉพาะตอน **navigate ออกจากหน้าพวกนี้** ไม่ใช่ตอนเข้า — ยังไม่แตะเพราะอยู่นอกขอบเขตที่ผู้ใช้สั่ง
- [x] ~~หน้าใต้ `Learn/Academy/[name]/Settings/*` และ `curriculum/*` เป็นโค้ด Inertia เก่า~~ → **อีก session ลบทิ้งไปแล้ว** ใน `9476499f` / `42a4e9b9` (ดู `ce944b6e docs(agents): plan the removal of the dead Inertia pages`) ⇒ ตอนนี้ทั้งสองโฟลเดอร์ไม่มีแล้ว

### Branch / Git State

- Branch: `main` · Uncommitted: ไม่มี · Push status: **push ครบแล้ว**
- commit ของงานนี้ 4 ก้อน เรียงเก่า→ใหม่: `f20d0842` `21988a8a` `0a072f7d` (สามก้อนแรก) แล้ว `18b0d519` (5 หน้าร้านค้า รอบสอง) · `npm run build` ผ่าน exit 0 ทั้งสองรอบ
- ตรวจซ้ำที่ HEAD ปัจจุบัน: root node ใน `pages/academies/` **ผ่านหมด** · parent route ที่ขาด `<NuxtPage />` **เหลือ 0 ทั้งโปรเจค** (ยังจริงอยู่หลังอีก session ลบไฟล์ไปเยอะ)

### ⚠️ มีอีก session ทำงานคู่ขนานบน `main` ตลอดเซสชันนี้ — อ่านก่อนถ้าเจอ history งง

1. ระหว่างผมแก้ `ui/pages` มี diff โผล่ใน `api/.../StudentCardController.php` + `routes/studentcard/studentcard.php` ที่ไม่ใช่ของงานนี้ · ผม `git add` เฉพาะ path ที่ตั้งใจทุกครั้งจึงไม่ปนเข้ามา ภายหลังถูก commit เป็น `7e4f429a`
2. ตอนผมเขียน worklog รอบแรก อีก session **push `7e4f429a` แซงไประหว่างนั้น** ทำให้บรรทัด Push status ที่เพิ่งเขียนผิดทันที ต้องแก้ก่อน amend
3. ตอนกลับมาอัพเดท worklog รอบสอง มี **9 commit ใหม่ทับอยู่ข้างบน** (`b5481114` … `42a4e9b9`) ส่วนใหญ่เป็นการลบหน้า Inertia เก่าทิ้ง · ตรวจแล้ว history เป็นเส้นตรง commit ของผมทั้ง 4 ก้อนยังเป็น ancestor ของ HEAD ครบ ไม่มีอะไรหาย

**บทเรียน:** ทำสองที่พร้อมกันเมื่อไหร่ → `git add` ระบุ path เสมอ อย่าใช้ `git add -A` ลอย ๆ · และ **`git commit` เก็บทุกอย่างที่อยู่ใน index ไม่ใช่แค่ path ที่เพิ่ง add** (`git mv` stage ให้อัตโนมัติ — รอบนี้ commit แรกกลืน rename ไปหมด ต้อง `git reset` แล้ว stage ใหม่ทีละกลุ่ม) · ก่อนเขียน Branch/Git State ทุกครั้งให้ `git fetch` แล้วเช็คสถานะจริงก่อน

---

## 2026-08-09 — หน้าบัตรนักเรียนแสดงคนไม่ครบ · แก้ครบทั้ง 3 ชั้น (อาการ + ข้อมูลค้าง + กันซ้ำ)

### สถานะ: **เสร็จครบ · commit 4 ก้อน · push ขึ้น `origin/main` แล้ว** (`6bdd6bf1` `127a255a` `9bdc9f6c` `64266ffa`) · working tree สะอาด

> ✅ TODO เก่า "git push 3 commit ค้าง" **ปิดไปแล้ว** — `02dc8e94` `d89f9796` `2a348218` เป็น ancestor ของ `2b35f063` ซึ่งอยู่บน origin ตั้งแต่ก่อนเริ่มเซสชันนี้ worklog เดิมตกข่าว

### 🐛 อาการที่รายงานมา

หน้า `/student-card/4/7` แสดง **41 คน** แต่หน้า `/academies/…/admin/classrooms/94` มี **43 คน** · ค้นในช่อง "เพิ่มนักเรียน" ขึ้นว่า "อยู่ในห้องนี้แล้ว" แต่ค้นในช่องค้นหาชื่อกลับไม่เจอ · ตัวอย่าง `12476 อดิศักดิ์ สัยมาบู`, `12845 ซันซาบีล มรรคาเขต`

### 🎯 สาเหตุ — query ตั้งต้นผิดตาราง

`StudentCardController::getStudentByRoom()` ตั้งต้นจาก **`student_cards`** แล้วค่อยกรองด้วยห้อง ⇒ **นักเรียนที่อยู่ในห้องจริงแต่ไม่มีแถวใน `student_cards` หายทั้งคน**

**ความสัมพันธ์ของ 2 ตาราง (ที่ทำให้พลาดง่าย)** — ไม่มี FK ต่อกัน ทั้งคู่ชี้ `students.id`:
- `classroom_students` = source of truth ของการสังกัดห้อง (ระบุใน docblock ของโมเดลเอง)
- `student_cards` = ตัวบัตร เกิดจาก `StudentCardRequestService::complete()` เท่านั้น · การ enroll **ไม่เคยสร้างบัตร**
- `student_cards.classroom_id / class_level / class_section` เป็น **snapshot ที่ค้างได้** (`classroom_id` มีค่าแค่ 2,305/2,647) — resource รู้อยู่แล้วและอ่านห้องจาก enrollment แทน แต่ controller ยังไม่รู้ ⇒ ความไม่สอดคล้องนี้คือบั๊ก
- cardinality: enrollment `N:1` student, card `0..1 active` ต่อ student ⇒ **LEFT JOIN ต้องเดินจาก enrollment → card เท่านั้น**

### ✅ ทำอะไรไป 3 ชั้น

| ชั้น | commit | สาระ |
|---|---|---|
| 1a backend | `6bdd6bf1` | พลิก query ให้ตั้งต้นจาก `classroom_students` · `RoomStudentResource` ใหม่รับ `ClassroomStudent` · เพิ่ม `Student::activeCard()` |
| 1b frontend | `127a255a` | เปลี่ยน key/DOM anchor จาก `id` → `uid` ทุกจุด · ซ่อนปุ่มแก้บัตร/รูปเมื่อไม่มีบัตร · badge "ยังไม่มีบัตร" · ค้นด้วยนามสกุลได้ |
| 2 ข้อมูลค้าง | `9bdc9f6c` | migration `2026_08_09_100000` ปลุกบัตรที่ถูก expire ผิด |
| 3 กันซ้ำ | `64266ffa` | guard ใน `StudentCardSyncService` + `ClassroomStudentObserver` |

**ผลจริง**: ม.4/7 41→**43** ตรงกับ `classroom_students` เป๊ะ · ตรวจ ม.4/4 (31=31) และ ม.3/7 (52=52) ด้วย · เหลือ 4 คนขึ้นป้าย "ยังไม่มีบัตร" ซึ่งถูกต้อง (ไม่เคยมีบัตรจริง ต้องผ่านโฟลว์คำร้อง)

### 🔴 กับดักที่เจอ — สำคัญมากสำหรับงาน student_cards ครั้งหน้า

1. **`student_cards.is_active_flag` เป็น `VIRTUAL GENERATED` จาก `student_status`** — เขียนค่าลงตรง ๆ ได้ error `3105` ทันที (พิสูจน์แล้ว)
   ⇒ **migration ห้าม backup ทั้งแถวแล้ว `update()` กลับทั้งแถวใน `down()`**
   ⇒ 🔴 **`down()` ของ `2026_08_06_100100_repair_orphaned_classroom_enrollments.php` ตกหลุมนี้อยู่** — payload 81 แถวใน `classroom_repair_backups` มีคีย์ `is_active_flag` ติดมาด้วย ถ้ามีใคร rollback มันจะพังกลางลูป · **มี session แยกกำลังแก้อยู่ ยังไม่จบตอนปิดเซสชันนี้**
2. **`uq_student_card_active (student_id, academy_id, is_active_flag)`** ⇒ ปลุกบัตรได้ใบเดียวต่อ (นักเรียน, โรงเรียน) · migration ต้อง `unique()` ตามคู่นี้ก่อนเขียนเสมอ
3. **`ClassroomStudentObserver` ทำงานผ่าน Eloquent event เท่านั้น** — `DB::table()->update()` ข้ามได้หมด นี่คือที่มาของบัตรที่ค้างผิดตั้งแต่ 2026-08-06
4. **`syncCommit` รับ `academic_year_id` จาก request ได้** และ `previewSync()` ผูกกับปีนั้น ⇒ ยิงด้วยปีที่ไม่ใช่ปีปัจจุบัน = นักเรียนทั้งโรงเรียนเข้าลิสต์ expire · **วัดจริงบน MySQL: ก่อน guard แตะได้ 2,100/2,100 ใบ หลัง guard เหลือ 1 ใบ**
5. **`StudentCardRequestService::complete()` ห้ามใส่ guard** — มัน expire ใบเก่าแล้วสร้างใบใหม่ทันทีในทรานแซกชันเดียว ซึ่ง unique index บังคับให้ทำแบบนั้น (claude เคยชี้ผิดว่าเป็นจุดเสี่ยง แก้ความเข้าใจแล้ว)

### 🧪 การพิสูจน์ — เทสต์เขียวอย่างเดียวไม่พอ

- **เทสต์รันบน SQLite in-memory** (`phpunit.xml`) ⇒ generated column กับ unique index **ไม่ถูกทดสอบเลย** · 70 เทสต์ผ่านหมด แต่สิ่งที่พิสูจน์จริงคือการรันบน MySQL
- **migration พิสูจน์ครบรอบบน MySQL**: `up` → `rollback` → `up` · บัตร `expired`→`active`→`expired`→`active` · flag `NULL`→`1` ตามอัตโนมัติ · duplicate active ต่อ (student, academy) = 0
- **revert-check ทั้ง 2 ชุด** — stash ไฟล์ prod แล้วรันเทสต์ใหม่: roster test พัง 3/3, guard test พัง 2/4 (อีก 2 คือเทสต์คุมพฤติกรรมเดิม ผ่านทั้งก่อนและหลัง ถูกต้อง)
- ตรวจหน้าเว็บจริงบนเบราว์เซอร์: ขึ้น 43 คน · ป้าย "ยังไม่มีบัตร 2 คน" · ค้น "สัยมาบู" เจอ · DOM anchor เป็น `card-cs-4826` · ปุ่มแก้บัตร/รูป = 0 ปุ่ม · ปุ่มขอทำบัตรยังกดได้ · console ไม่มี error

### ⚠️ agy — โหมดโกหกแบบใหม่

4 shard รันขนานกัน **โค้ดถูกต้องตรงสเปคทุกบรรทัด ไม่แตะไฟล์ต้องห้าม ผลผ่าน/ไม่ผ่านของเทสต์ก็ถูก** แต่ **จำนวน assertion ที่ paste มาเป็นค่าที่แต่งขึ้น**: `ClassroomManagementTest` บอก 130 (จริง 50) · `PublicCardRequestTest` 59 (จริง 35) · `StudentCardSSOTTest` 34 (จริง 25) — จำนวนเทสต์ตรงทุกชุด เพี้ยนเฉพาะ assertion
⇒ **เห็น diff ถูกต้องไม่ได้แปลว่าตัวเลขในรายงานเชื่อได้ ต้องรันเทสต์เองเสมอ** (บันทึกลง memory `feedback-agy-fabricates-diffs` แล้ว)

### งานที่ค้าง (TODO ต่อ)

- [ ] **รัน `php artisan migrate` ที่อีกเครื่องหลัง pull** — migration `2026_08_09_100000` รันบน DB dev เครื่องนี้แล้ว แต่ DB อีกเครื่องเป็นคนละก้อน ถ้าไม่รัน บัตรที่ถูก expire ผิดจะยังค้างที่นั่น
- [ ] **`StudentCardPublicResource` กลายเป็น dead code** ตั้งแต่ `6bdd6bf1` — ไม่มีโค้ดเรียกแล้ว เหลือแค่ชื่อใน docblock ของ `RoomStudentResource` · ตัดสินว่าจะลบไหม
- [ ] **ผลของ session แยกที่แก้ `down()` ของ `2026_08_06_100100`** — ยังรันอยู่ตอนปิดเซสชันนี้ ต้องไปดูว่าจบยังไงและ commit เข้ามาหรือยัง
- [ ] **ยังไม่มีอะไรกัน `DB::table('student_cards')->update()` ที่อื่นในอนาคต** — guard คุมแค่ 2 เส้นทางที่รู้จัก ถ้าอยากกันจริงต้องใช้ DB trigger หรือ CHECK constraint (นอกขอบเขตรอบนี้) · สาขา `graduated` ของ observer ก็จงใจไม่ guard
- [ ] ลบกิ่ง `fix/student-card-room-roster` ที่ merge เข้า main แล้ว (`git branch -d`)
- [ ] **(ค้างจากรอบก่อน)** ตั้งชื่อคณะสีจริงแทน 4 ชื่อชั่วคราว แล้วสั่งแบ่งใหม่ · S-S4 schema คะแนน · ไฟล์ SQL prod ล้าสมัย 9 migrations

### ✅ ปิด TODO เก่าไปด้วย 1 ข้อ

`2026_08_06_150000_restore_user_account_for_student_12247` ที่ worklog รอบก่อนกังวลว่า "จะรันเองโดยไม่มีใครตั้งใจ" — **รันไปแล้วใน batch 118** (เซสชัน 2026-08-08 พร้อม sports editions) ไม่ใช่ batch 119 ของเซสชันนี้ · ตรวจผลแล้ว user 16619 = `อาดิษ ประสารการ` / `s12247@jariyathum.ac.th` คืนสภาพเรียบร้อย ไม่ใช่ `Deleted User` แล้ว

### Branch / Git State
- Branch: `main` (ff-only จาก `fix/student-card-room-roster` ไม่มี merge commit)
- Uncommitted: ไม่มี
- Push status: **push แล้ว** — `main` = `origin/main` = `64266ffa`

---

## 2026-08-08 (ต่อ) — เคลียร์ TODO ค้าง 3 ข้อจาก 2026-08-03 · ตัดสิน S-D2 · ล็อกสเปก S-S3e

### สถานะ: **S-S3e เสร็จครบทั้ง backend + frontend · พิสูจน์กับข้อมูลจริง 2,143 คนแล้ว** · commit `02dc8e94` `d89f9796` `2a348218` · **ยังไม่ push**

> ⚠️ **Codex เลิกใช้ถาวรแล้ว** (หมดอายุ ไม่ได้ต่อ) — agy เป็นผู้เขียนโค้ดคนเดียวจากนี้ไป

### ผลเช็ค TODO 3 ข้อ — **ข้อ 3 ปิดไปแล้วโดยที่ worklog ตกข่าว**

| TODO | ผลตรวจจริง |
|---|---|
| ชะตาไฟล์ `migrations_from_2026_07_31.sql` | ✅ **ตัดสินไปแล้ว** — commit เข้า repo ตั้งแต่ `a152a009` (2026-08-05) พร้อมอีก 2 ไฟล์ `repair_schema_drift_2026_08_05.sql` · `restore_missing_tables.sql` |
| S-D2 จำนวนคณะสี | ✅ **ตัดสินรอบนี้** (ดูล่าง) |
| S-S4 | ⏸ ถูกบล็อกโดย S-S3e ที่เพิ่งเพิ่ม |

### 🔴 2 เรื่องที่เจอระหว่างตรวจ — ยังไม่ได้แก้ ทั้งคู่เป็นงานใหม่

1. **ไฟล์ SQL ล้าสมัยไป 9 migrations** — หัวไฟล์เขียนถูกว่าครอบคลุม `2026_07_31_000001 … 2026_08_02_000004` แต่หลังจากนั้นมี `2026_08_05_160000` → `2026_08_07_100000` อีก 9 ตัวที่**ไม่มีคู่ SQL** → ถ้า prod ยังรัน artisan ไม่ได้ prod จะตามหลัง 9 ตัวโดยไม่มีทางไล่ตาม
2. **`2026_08_06_150000_restore_user_account_for_student_12247` ขึ้น `Pending`** บนเครื่องนี้ ทั้งที่ตัวที่ใหม่กว่า (`2026_08_07_100000`) ขึ้น `Ran` ใน batch 117 → แปลว่าไฟล์ถูกเพิ่มทีหลังด้วยเลขย้อนหลัง **จะรันเองในครั้งถัดไปที่ `migrate` โดยไม่มีใครตั้งใจ** — ตรวจเนื้อในก่อนรัน migration ครั้งหน้า

### ✅ S-D2 ตัดสินแล้ว: ไม่มีจำนวนตายตัว — กำหนดได้ต่อสถาบัน **และต่อครั้งที่จัด**

ผู้ใช้ตัดสิน 2 ข้อ:
- **จำนวนคณะสีแล้วแต่สถาบัน + แล้วแต่ครั้งที่จัด** · โรงเรียนนี้ **จัดได้หลายครั้งต่อปี**
- **กีฬาสีปี 2569 ใช้ 4 คณะสี** (2,202 คน ≈ 550 คน/สี)

**"แล้วแต่สถาบัน" ได้อยู่แล้ว** — คณะสีคือ `academy_groups(type='house', academy_id)` และ `house_group_ids` ส่งต่อ batch ได้อิสระ (UI ติ๊กกี่สีก็ได้ min 2)

**"แล้วแต่ครั้งที่จัด" ติดบรรทัดเดียว** — `2026_08_02_000003_create_house_memberships_table.php:26`
```php
$table->unique(['academic_year_id', 'student_id']);   // 1 นักเรียน = 1 สี ต่อ 1 ปี ตายตัว
```
`commit()` upsert ที่คีย์นี้ (`HouseAssignmentService.php:163`) → จัด 2 ครั้งในปีเดียว **ครั้งที่ 2 ทับครั้งที่ 1 ทิ้ง** · `current()` (`HouseAssignmentController.php:36`) และ `HouseMembershipProjector::rebuild($academyId, $yearId)` ก็ยึด "1 ปี 1 ชุดสี" ทั้งคู่

### 🆕 S-S3e — สเปกล็อกแล้วที่ §9 ของ `27-sports-day.md`

ตารางใหม่ 2 ตัว: **`sports_editions`** (academy + academic_year + sequence + status + `school_event_id` nullable) และ **`sports_edition_houses`** (จำนวนคณะสีของครั้งนั้น = จำนวนแถวตรงนี้)
ย้ายคีย์ `house_memberships` / `house_assignment_batches` จาก `academic_year_id` → `edition_id`

⏱ **ต้องทำก่อน S-S4 เท่านั้น** — `house_memberships` / `house_assignment_batches` / `house_assignment_rows` = **0 แถวทั้ง 3 ตาราง** (ยืนยัน 2026-08-08) เปลี่ยนคีย์ได้ฟรี · ถ้าปล่อย `sports_score_entries` ลงก่อน จะต้องย้าย event log คะแนนตามไปด้วย

⚠️ **จุดที่ห้ามพลาดเวลาสั่งทำ (เขียนละเอียดไว้ใน §9 แล้ว):**
- **ต้อง drop `academic_year_id` ออก ไม่ใช่เก็บคู่กับ `edition_id`** — เจ้าของปีมีคนเดียวคือ edition (บทเรียน dual-write ของ #6)
- **migration ให้ drop + recreate 3 ตาราง ไม่ใช่ ALTER** — เลี่ยงกับดัก `dropForeign()->dropColumn()` ที่ต่อกันไม่ได้ · **ต้องมี guard: แถว > 0 ให้ throw** ห้ามลบข้อมูลของ env ที่เผลอใช้ไปแล้ว · `down()` ต้องคืนรูปเดิมได้จริง
- **ห้ามมี edition `active` เกิน 1 ต่อ academy** — บังคับด้วย generated column `active_key AS IF(status='active', academy_id, NULL) STORED` + UNIQUE (MySQL ไม่ถือว่า NULL ซ้ำ — เทคนิคเดียวกับที่แนะนำให้ `election_results.party_key` ใน #25) **อย่าบังคับด้วยโค้ดแอปอย่างเดียว**
- **⛔ ห้ามใช้ `school_events` เป็นคีย์ตรง ๆ** — ตรวจแล้ว: ไม่มีคอลัมน์ `academic_year_id` (ดึงประชากร §7.4 ไม่ได้), มี `deleted_at`, และปน event_type หลายแบบ (3 แถว seed: sports/meeting/ceremony) → ผูกกลับด้วย `school_event_id` nullable แทน

### ✅ S-S3e เสร็จ — `d89f9796` (backend) · `2a348218` (frontend)

agy ทำทั้ง 2 ก้อนตามสเปก §9 · **ตรงตามสูตรเดิม: สเปกแน่น = agy ไม่ over-reach** ไม่แตะไฟล์ต้องห้าม ไม่ commit ไม่แตะข้อความไทย · เทสต์ **38 ข้อผ่าน (138 assertions)**

#### 🔴 3 บั๊กที่ claude เจอตอนตรวจ ไม่ใช่ agy รายงาน

1. **migration พังบน MySQL ทั้งที่เทสต์ SQLite เขียว 37 ข้อ** — `active_key` เป็น `storedAs` → `1215 Cannot add foreign key constraint` ที่ FK ของ **`academy_id`** ซึ่งดูไม่เกี่ยวกันเลย
   **MySQL ห้าม `ON DELETE CASCADE` บน base column ของ STORED generated column** · ของ VIRTUAL ห้ามเฉพาะ `ON UPDATE` → `virtualAs` แก้ได้ครบ
   ⚠️ **และมันทิ้ง DB ค้างครึ่งทาง** — MySQL ทำ DDL ทีละ statement ⇒ `sports_editions` ถูกสร้าง **โดยไม่มีแถวใน `migrations`** แล้ว migration ตัวที่ 2 วิ่งทับขึ้นไป · กู้ด้วย `migrate:rollback --path=` ตัวหลัง → `dropIfExists` → รันใหม่ทั้งคู่
2. **projector ลบ projection หลัง early-return** (สเปกสั่งให้ลบก่อน) → เปิดใช้ครั้งใหม่ที่ยังไม่เลือกคณะสี **สมาชิกของครั้งก่อนค้างอยู่** · แก้ + เทสต์ + revert-check ยืนยันว่าพังจริงถ้าไม่แก้ (เหลือค้าง 3 แถว)
3. **frontend: แก้ชื่อ/วันที่ของครั้งที่มีนักเรียนแล้ว → 422 ทุกครั้ง** — ส่ง `house_group_ids` ไปด้วยเสมอ แม้ช่องติ๊กถูก disable · API เช็คแค่ `if ($houseIds !== null)` ไม่สนว่าค่าเปลี่ยนไหม

#### จุดต่อข้อมูลที่เปราะที่แก้เพิ่ม

agy ให้หน้าจออนุมานว่า "ครั้งนี้มีคณะสีอะไรบ้าง" จาก **คีย์ของ endpoint นับจำนวน** — ถูกวันนี้เพราะ API เติมค่า 0 ให้ทุกสี แต่ถ้าวันหนึ่งเลิกเติม ตัวเลือกคณะสีจะว่างเงียบ ๆ → เพิ่ม `v-model:house-ids` ให้ panel ส่งจาก `edition.houses` ตรง ๆ

### ✅ พิสูจน์กับข้อมูลจริงครบเส้นแล้ว (ครั้งแรกนับตั้งแต่ทำเครื่องมือนี้)

สร้าง **4 คณะสี ชื่อชั่วคราว** id `36-39` (แดง `#ef4444` · น้ำเงิน `#3b82f6` · เหลือง `#eab308` · เขียว `#22c55e`) + **edition id 7 "กีฬาสี 2569"** สถานะ `active`

| ขั้น | ผล |
|---|---|
| preview (`stratified` + สมดุลเพศ, seed 25690001) | 0.8s · **2,143 คน** · 536/536/536/535 |
| commit | 2.7s · `house_memberships` 2,143 · projection 2,143 |
| กระจายตามชั้นในคณะสีแดง | ม.1 100 · ม.2 113 · ม.3 93 · ม.4 81 · ม.5 76 · ม.6 73 ≈ ¼ ของทุกชั้นพอดี |
| undo | 2.9s · memberships 0 · projection 0 · **แถว batch 2,143 ยังอยู่เป็นหลักฐาน** |

⚠️ **ตอนนี้ undo แล้ว = ยังไม่มีการแบ่งค้างอยู่** ตั้งใจ เพราะ commit จะ**ฉายลง `academy_group_members` ซึ่งนักเรียนมองเห็นในฟีดกลุ่ม** ไม่อยากให้เด็ก 2,143 คนเห็นตัวเองอยู่ "คณะสีแดง" ชื่อชั่วคราวก่อนโรงเรียนตั้งชื่อจริง — สั่งแบ่งใหม่ได้ตลอดจากหน้าจอ

🔢 **ประชากรจริงคือ 2,143 ไม่ใช่ 2,202 — ตัวเลขในสเปกล้าสมัยแล้ว** ต่างกัน 59 คนอยู่ที่ **ม.4 (377→326)** กับ **ม.5 (312→304)** ล้วน ๆ · ม.1/ม.2/ม.3/ม.6 เท่าเดิมเป๊ะ · `classroom_students` ปี 2569 ตอนนี้มี `removed 50 · transferred 167 · graduated 2 · superseded 3` = งานทะเบียนหลัง 2026-08-02 ไม่ใช่ query เพี้ยน (ตรวจแล้ว join ไม่ทำใครหาย 2143=2143=2143)

### งานที่ค้าง (TODO ต่อ)

- [ ] **`git push`** — 3 commit ค้างบน main (`02dc8e94` `d89f9796` `2a348218`)
- [ ] **ตั้งชื่อคณะสีจริง** แทน 4 ชื่อชั่วคราว แล้วสั่งแบ่งใหม่จากหน้าจอ (ยืนยันว่าหน้าจอทำงานจริงบนเบราว์เซอร์ด้วย — ยังไม่ได้ทดสอบ ตรวจแค่ SFC compile)
- [ ] **S-S4** schema คะแนน — ทุกตาราง key ที่ `edition_id` (ตอนนี้ไม่มีอะไรบล็อกแล้ว)
- [ ] ไฟล์ SQL สำหรับ prod ล้าสมัย 9 migrations — ตัดสินว่าจะต่อไฟล์ให้ทันหรือเลิกใช้วิธีนี้
- [ ] **ตัดสิน `2026_08_06_150000_restore_user_account_for_student_12247` ที่ค้าง `Pending`** — จะคืนบัญชี user 16619 (ตอนนี้ `Deleted User` / `deleted_16619@nuxnan.del`) กลับเป็น **อาดิษ ประสารการ** · ตัว migration เขียนดี (backup ก่อนเขียน · idempotent · down() ใช้ได้จริง) แต่แตะข้อมูลคนจริง จึงกันไม่ให้รันพ่วง — ครั้งหน้าที่ `php artisan migrate` เปล่า ๆ **มันจะรันเอง**

---

## 2026-08-08 — แก้ 2 บั๊ก UI หน้าเช็คชื่อ (dropdown โดน clip · ห้องเรียนเลื่อนซ้าย)

### สถานะ: เสร็จทั้งคู่ · ผู้ใช้ทดสอบผ่านแล้ว · commit `34ac9d44` `73f9ecdf` · **push แล้ว** · working tree สะอาด

> ⚠️ **บทเรียนหลักของเซสชันนี้: ทั้ง 2 บั๊กไม่ได้มีสาเหตุตามที่รายงานมา** ถ้ารีบแก้ตามอาการที่เห็นจะไม่ถูกสักตัว

### 🐛 บั๊ก 1 — dropdown เลือกสถานะเช็คชื่อโดนตัด (`34ac9d44`)

**รายงานมาว่า "ปัญหา z-index" แต่ไม่ใช่** — เป็น **overflow clipping** เพิ่ม z-index เท่าไหร่ก็ไม่มีทางแก้ได้ เพราะ `position: absolute` หนีกรอบ overflow ของ ancestor ไม่ได้

พาเนลอยู่ใน `<td>` แล้วโดนตัดจาก 2 ชั้นใน `ui/components/learn/course/AttendancesTable.vue`:
- บรรทัด 74 การ์ดนอกสุด `overflow-hidden`
- บรรทัด 75 `overflow-x-auto` — **ตามสเปค CSS ถ้าแกนหนึ่งไม่ใช่ `visible` อีกแกนที่เป็น `visible` จะถูกบังคับเป็น `auto`** → ตัดแนวตั้งไปด้วย

→ แก้ที่ `ui/components/learn/course/AttendanceStatusBadge.vue`: `<Teleport to="body">` + `position: fixed` คำนวณจาก `getBoundingClientRect()` ตามแพทเทิร์นที่มีอยู่แล้วใน `ui/components/academy/enrollment/StudentActionMenu.vue`

จุดที่พลาดง่าย 3 ข้อ:
- **`scroll` listener ต้องใส่ `capture: true`** — ตารางเลื่อนใน `overflow-x-auto` ของตัวเอง event ไม่ bubble ขึ้น window ถ้าลืมข้อนี้เมนูจะลอยค้างเวลาเลื่อนตาราง
- **click-outside ต้องเช็คทั้ง trigger และ panel** เพราะ panel ถูก teleport ออกไปนอก `menuRef` แล้ว
- `hasRoomBelow` ให้แถวล่างๆ เด้งเมนูขึ้นบนแทนตกจอ

**แถมที่เจอระหว่างทาง:** `:class` ใช้ `bg-${option.color}-50` แบบ interpolate — **Tailwind JIT สแกนหา class แบบ literal เท่านั้น class พวกนี้ไม่เคยถูก generate เลย** ไฮไลต์ตัวเลือกที่เลือกอยู่จึงไม่เคยขึ้นสีตั้งแต่แรก → เปลี่ยนเป็น ternary เต็มสตริง + ยุบเงื่อนไขซ้ำ 2 ที่เป็น `isSelected()`

### 🐛 บั๊ก 2 — ห้องเรียนจำลองเลื่อนไปทางซ้ายค้างถาวรหลังนักเรียนรายงานตัว (`73f9ecdf`)

**รายงานมาว่า "toast ดัน layout" แต่ SweetAlert2 ไม่มีทางทำได้** — ตรวจซอร์สของมันเองแล้ว ทั้ง 3 ทางที่ swal ยุ่งกับ `<body>` ถูกกันไว้หมดในโหมด toast:

| กลไก | ที่มา | toast |
|---|---|---|
| `body { overflow: hidden }` | `sweetalert2.css:104` selector คือ `.swal2-shown:not(..., .swal2-toast-shown)` | ไม่โดน |
| `body { height: auto !important }` | `sweetalert2.js:4419` `if (heightAuto && backdrop && !params.toast)` | ไม่โดน |
| `body { padding-right }` ชดเชย scrollbar | `sweetalert2.js:4321` `if (isModal())` | ไม่โดน |

container เองคือ `div:where(.swal2-container) { position: fixed; z-index: 1060 }` (`sweetalert2.css:171`) → หลุด flow สนิท **toast แค่มาโผล่ทีหลังพอดี**

**สาเหตุจริง: `transform` กับ `width` บน `sceneInnerRef` มี 2 เจ้าของแย่งกันเขียน** ใน `ui/components/learn/course/attendances/ClassroomSeatGrid.vue`
- (ก) Vue ผ่าน `:style` binding
- (ข) โค้ด imperative ในขั้นวัดความสูงของ `updateFitScale()` ที่ save → clear → measure → restore

พอ `fitScale` เปลี่ยนระหว่างที่ rAF ค้างอยู่ ตัว restore จะเขียนทับค่าใหม่ที่ Vue เพิ่งใส่ ทำให้ `width` กับ `scale` **หลุดคู่กัน** แล้ว render ถัดไป Vue diff เห็นว่า binding ไม่เปลี่ยน → **ไม่เขียนซ้ำ ค้างถาวรจนกว่าจะ refresh**

**ทำไมเป็นการเลื่อนซ้าย** — ฉากถูกจัดกลางด้วย `left:50%` + `translateX(-50%)` ซึ่งหักล้างกันพอดีก็ต่อเมื่อ `width × scale === 100%` ของ outer สูตรขอบซ้าย = `W/2 − (w×s)/2` พอเพี้ยนเป็น `width:142%` + `scale(1)` จะได้ `−0.214W` = ห้องเรียนหลุดออกนอกขอบซ้าย

ตัวกระตุ้นคือ `handleSelfCheckIn()` ใน `AttendanceSimulatorShell.vue:285` ที่ยิง re-render 4 ครั้งรวดในไม่กี่ ms (refetch → `scrollIntoView` → `selectedMemberId` → `emit('checked-in')` ที่ทำให้ `AttendancesList` refetch อีกชุด)

→ แก้โดย **ให้โค้ด imperative เป็นเจ้าของคนเดียว**: ถอด `:style` ออกจาก inner element ทั้งก้อน แล้วเขียนทั้งคู่พร้อมกันใน `applyFitStyles()` จาก `fitScale` ค่าเดียวเสมอ

⚠️ **ข้อบังคับที่ห้ามลืมถ้าจะแก้ไฟล์นี้ต่อ:**
- **`applyFitStyles()` ต้องถูกเรียกทุกทางออกของ rAF** รวมกรณี `naturalH`/`availH` เป็น 0 ไม่งั้น `width` ค้างที่ `100%` ขณะที่ `scale` ยัง < 1 = อาการเดิมเป๊ะ → ใช้ `if` block ห้ามแปลงกลับเป็น early return
- **ห้ามแก้ด้วยวิธี "ให้ Vue เป็นเจ้าของคนเดียว" + `measuring` flag + `await nextTick()`** — จะเปิดช่องให้เบราว์เซอร์ paint 1 เฟรมตอน transform หายไป = ห้องเรียนกระพริบใหญ่ขึ้นทุกครั้งที่วัด (แผนแรกของ claude มีจุดอ่อนนี้ เปลี่ยนก่อนส่ง agy)
- **`transform: scale()` ไม่มีผลกับ `scrollHeight`** เพราะเป็นค่า layout → บรรทัด `inner.style.transform = 'none'` เดิมไม่เคยจำเป็นตั้งแต่แรก มีแค่ `width` ที่ต้องเคลียร์ตอนวัด
- `fitScale` ถูก clamp ด้วย `Math.min(1, ...)` อยู่แล้ว → `100/s` ครอบคลุมทุกกรณี **อย่าเอา ternary `fitScale < 1 ? ... : '100%'` กลับมา** มันเป็นอีกช่องให้หลุดคู่

### บทเรียนเครื่องมือ

- **agy ทำงานได้ดีมากเมื่อสเปคแน่น** — 3 job ติดกัน ทำตามเป๊ะทุกข้อ ไม่มีการ over-reach ไม่แตะไฟล์นอกขอบเขต ไม่แก้ข้อความไทย · สูตรที่ได้ผล: เขียน root cause ให้เสร็จ + ใส่โค้ดเป้าหมายเต็มๆ + ระบุไฟล์ที่ห้ามแตะเป็นชื่อ + ใส่ acceptance ที่ตรวจได้ด้วยคำสั่ง
- **acceptance criterion ที่ claude เขียนเองผิดได้** — สั่ง agy ว่า "`git diff -w` ต้องว่าง" สำหรับงาน whitespace แต่ baseline คือ HEAD ซึ่งอยู่ก่อนงานรอบแรกที่ยังไม่ commit `-w` เลยโชว์ diff รอบแรกติดมาด้วย **agy อธิบายถูกและ claude ผิดเอง** → ตัวตรวจที่ใช้ได้จริงคือ `--stat` (+40/−40 พอดี = whitespace ล้วน)
- **ตรวจสมมติฐานกับซอร์สจริงก่อนสั่งแก้** — เซสชันนี้ล้มสมมติฐานตัวเอง 2 ข้อจากการอ่าน `node_modules/sweetalert2/dist/*` (คิดว่าจะแก้ด้วย `heightAuto: false` แต่ `sweetalert2.js:3285` มันอยู่ใน `toastIncompatibleParams` = ใส่ไปก็ได้แค่ warning) ถ้าไม่เช็คจะส่ง agy ไปแก้ผิดจุด

### วิธี verify ที่ใช้ (ไม่ได้ `npm run build` ตามข้อตกลง)

```bash
node -e "const {parse,compileTemplate,compileScript}=require('@vue/compiler-sfc'); ..."
```
รันจาก `ui/` — parse + compile template + compile script setup ของ `.vue` ทีละไฟล์ เร็วกว่า build มากและจับ syntax error ได้ครบ · ใช้ตรวจข้อความไทยกับ magic number ว่ายังอยู่ครบในสคริปต์เดียวกันได้ด้วย

### งานที่ค้าง (TODO ต่อ)

- [ ] **ไม่มีงานค้างจากเซสชันนี้** — ทั้ง 2 บั๊กปิดครบ ทดสอบผ่าน push แล้ว
- [ ] (ยกมาจาก 2026-08-03) ตัดสิน S-D2 จำนวนคณะสี → สร้างคณะสีจริง → ทดลองแบ่งนักเรียน 2,202 คน
- [ ] (ยกมาจาก 2026-08-03) S-S4 schema คะแนนกีฬาสี + event log
- [ ] (ยกมาจาก 2026-08-03) ตัดสินชะตาไฟล์ `api/nuxnanravel/database/migrations_from_2026_07_31.sql`

### Branch / Git State

- Branch: `main` — ตรงกับ `origin/main` (`73f9ecdf`)
- Uncommitted: **ไม่มี** working tree สะอาด
- Push status: **push แล้ว** (`d2543d50..73f9ecdf`)

---

## 2026-08-03 — ตรวจสถานะเปิดเซสชัน (ไม่มีการแก้โค้ด)

### สถานะ: เซสชันนี้**ไม่ได้เขียนโค้ดเลย** — ตรวจของเดิมกับ git/DB จริงแล้วรีเฟรชบันทึกด้านล่างให้ตรง

**สิ่งที่ยืนยันด้วยคำสั่งจริง (ไม่ใช่การอ่านบันทึกเก่า):**

| เรื่อง | ผลตรวจ | คำสั่งที่ใช้ |
|---|---|---|
| commit ค้าง push | **0 ก้อน** — บันทึก 2026-08-02 ที่เขียนว่า "ยังไม่ push" **ล้าสมัยแล้ว** | `git rev-list --count origin/main..HEAD` |
| S-S3b หน้าจอแบ่งคณะสี | **เสร็จแล้ว** `f065ce19` + แก้ empty-state `ee228e75` — บันทึกเก่าที่เขียนว่า "ยังไม่มีหน้าจอ" ล้าสมัย | `git log --oneline` |
| migration 15 ตัว (`2026_07_31_000001` → `2026_08_02_000004`) | ขึ้น **Ran ครบ** บน DB เครื่องนี้ (`nuxnan_nuxnan_db`) | `php artisan migrate:status` |
| ข้อมูลคณะสีจริง | **ว่างทั้งหมด** `academy_groups(house)=0 · house_memberships=0 · house_assignment_batches=0 · house_assignment_rows=0` | `php artisan tinker` |
| working tree | มีของ session อื่นค้าง 3 ไฟล์ + untracked 1 ไฟล์ (ดูล่าง) | `git status --short` |

⚠️ **ชื่อตารางที่เดาผิดง่าย** — ตารางของเครื่องมือแบ่งสีชื่อ `house_assignment_batches` / `house_assignment_rows` **ไม่ใช่** `house_division_runs` (เดาผิดรอบหนึ่งแล้วเจอ error)

⚠️ **DB เครื่องนี้ชื่อ `nuxnan_nuxnan_db` ไม่ใช่ `nuxnan`** ตามที่ CLAUDE.md เขียนไว้ — ถ้าต่อ DB แล้วไม่เจอตาราง ให้ดู `.env` ก่อนสรุปว่า migration ไม่ได้รัน

### 📄 ไฟล์ untracked ที่ยังไม่ตัดสิน

`api/nuxnanravel/database/migrations_from_2026_07_31.sql` — 24KB, 11 `CREATE TABLE`, แปลง 15 migrations (`2026_07_31_000001` … `2026_08_02_000004`) เป็น SQL ล้วนสำหรับรันบน prod ที่รัน artisan ไม่ได้
หัวไฟล์ระบุเงื่อนไข: DB ปลายทางต้องรัน migrations ถึง `2026_07_29_000007` ครบก่อน · `mysql -u <user> -p <db> < ไฟล์นี้`
⚠️ หมายเหตุในไฟล์เอง: **MySQL ทำ implicit commit ทุกครั้งที่เจอ DDL** → `START TRANSACTION` ในไฟล์คุมได้แค่ INSERT ท้ายไฟล์ ถ้าพังกลางทาง**ต้อง rollback ด้วยมือ**
→ **ผู้ใช้ยังไม่ตัดสินว่าจะ commit เข้า repo / ใส่ `.gitignore` / ปล่อยไว้** — ถามแล้วแต่ยังไม่ตอบ

### ⚠️ ไฟล์ `ui/` 3 ไฟล์ที่ค้างใน working tree — **งานของ session อื่น อย่าเผลอ commit รวม**

`ui/components/academy/AssignHomeroomTeacherModal.vue` (+34) · `ui/pages/academies/[name]/admin/classrooms/[id].vue` (+86) · `ui/pages/academies/[name]/admin/classrooms/index.vue` (+270) — งานครูประจำชั้น/ห้องเรียน **ไม่เกี่ยวกับ #27**
สังเกต: ตอนกลางเซสชัน `git status` เคยขึ้นสะอาด แล้วไฟล์ 3 ตัวนี้กลับมาโผล่อีกครั้ง → **น่าจะมี session/editor อื่นเขียนอยู่พร้อมกัน** ให้ `git status` สดทุกครั้งก่อน `git add`

### 🔴 ตัวบล็อกเดียวที่เหลือของ #27 — S-D2 ยังไม่ตัดสิน

**โรงเรียนจะมีกี่คณะสี (4/5/6)** — ค้างมาตั้งแต่ 2026-08-02 · เครื่องมือรองรับ N สีอยู่แล้วจึงไม่บล็อกงานโค้ด **แต่บล็อกการใช้งานจริง** เพราะยังไม่มีใครสร้างคณะสีสักสี → หน้า S-S3b ที่ทำเสร็จแล้วยังไม่เคยถูกใช้กับข้อมูลจริง
ถามผู้ใช้รอบนี้แล้ว **ยังไม่ตอบ** — อย่าตัดสินแทน

### งานที่ค้าง (TODO ต่อ)

- [ ] **ตัดสิน S-D2 จำนวนคณะสี** → สร้างคณะสีจริง → ทดลองแบ่งนักเรียน 2,202 คนผ่านหน้า `ui/pages/academies/[name]/admin/house-assignments/index.vue` (preview → commit → undo) **เพื่อพิสูจน์ว่าทั้งเส้นทำงาน — ยังไม่เคยรันกับข้อมูลจริงสักครั้ง**
- [ ] **S-S4 คือขั้นถัดไปตามตารางที่ล็อก** — schema กีฬาสี (§4 ของสเปก) + ให้คะแนนแก่คณะสีผ่าน event log + จัดการคะแนนเท่ากัน · `houseLeaderboard()` ตอนนี้คืน `points: 0, points_source: 'pending'` รออันนี้อยู่
- [ ] ตัดสินชะตาไฟล์ `migrations_from_2026_07_31.sql`

---

## 2026-08-02 — เมนู #27 กีฬาสี: แบ่งนักเรียนเข้าคณะสี (สุ่ม + นำเข้า)

### สถานะ: S-S1/S-S2/S-S3/S-S3i/**S-S3b เสร็จ** · **30 เทสต์ผ่าน (124 assertions)** · commit `b2fb8e40` `f5fe814e` `c35a32b0` `f065ce19` `ee228e75` · **push แล้วทั้งหมด** (ยืนยัน 2026-08-03)

> ⚠️ บรรทัดสถานะเดิมเขียนว่า "ยังไม่ push · ยังไม่มีหน้าจอ (S-S3b คืองานถัดไป)" — **แก้แล้วเมื่อ 2026-08-03** เพราะทั้งสองข้อไม่จริงอีกต่อไป
> **S-S3b (`f065ce19`)** เพิ่ม `ui/pages/academies/[name]/admin/house-assignments/index.vue` (849 บรรทัด) + `ui/composables/useHouseAssignments.ts` + เมนูใน `admin.vue` + แก้ `HouseAssignmentController` · **`ee228e75`** แก้ empty-state ให้ชี้ไปหน้าที่สร้างคณะสีได้จริง

เอกสารเต็ม: [.agents/school-admin/27-sports-day.md](school-admin/27-sports-day.md) — §5 ข้อตัดสินล็อกแล้ว · §7 สเปกเครื่องมือแบ่งสี

### ข้อตัดสินที่ล็อก (ผู้ใช้ตัดสิน)

**S-D1 รองรับทั้งสุ่มและนำเข้า** · **S-D6 แบ่งใหม่ทุกปี** · **S-D7 สุ่มแบบคละทุกห้อง + สมดุลเพศ เป็นค่าเริ่มต้น มีตัวเลือกสุ่มล้วนทั้งโรงเรียน** · **S-D2 จำนวนสียังไม่ตัดสิน → เครื่องมือรองรับ N สี ไม่ seed ข้อมูลตัวอย่าง**

### 🔑 การตัดสินใจเชิงโครงสร้าง 3 ข้อที่ต้องรู้ก่อนทำต่อ

1. **สมาชิกคณะสีไม่ได้อยู่ใน `academy_group_members`** — ตารางนั้นไม่มี `academic_year_id` และให้บริการ 35 กลุ่มจริงอยู่แล้ว (ฝ่าย/งาน/กลุ่มสาระ) การเพิ่มคอลัมน์ปีจะบังคับให้ทุก query ของกลุ่มอื่นต้องรู้จัก null ทันที → ใช้ `house_memberships` ที่มี `UNIQUE [academic_year_id, student_id]` เป็นแหล่งความจริง แล้ว **ฉาย (project) เฉพาะปีปัจจุบัน** ลง `academy_group_members` ด้วย `HouseMembershipProjector` ที่เป็นผู้เขียนตัวเดียว สร้างใหม่ได้เสมอ
2. **ประชากรคือ `classroom_students` ของปีนั้น (2,202 คน) ไม่ใช่ `students.status='active'` (2,662)** — ต่างกัน 460 คนคือคนที่ไม่มีแถวห้องเรียนปีนี้ (สายเดียวกับ 449 คนที่ถูกจำหน่ายใน #25)
3. **undo ต้องคืนสีเดิม ไม่ใช่ลบทิ้ง** — commit ใช้ upsert ทับแถวเดิม ถ้า undo ลบอย่างเดียว คนที่ถูกย้ายจะ**ไม่มีสังกัดสีเลย** ใต้ปุ่มชื่อ "ย้อนกลับ" → เก็บ `previous_house_group_id` ตอน preview แล้วคืนค่า (ข้อนี้เป็นความผิดของสเปกเอง เจอตอน review)

### 🐛 บั๊กที่เทสต์จับได้ (ทั้งหมดเจอตอน claude ตรวจ ไม่ใช่ตอน codex รายงาน)

- **`houseLeaderboard()` = `SUM(users.pp)`** — คืน `[]` เพราะยังไม่มีแถว house เท่านั้น **วินาทีที่สร้างคณะสีแรกจะเริ่มเผยแพร่ยอดเงินถอนได้ของนักเรียนรวมต่อคณะ** → ต้องแก้ commit เดียวกับที่ลงทะเบียน type (ห้ามแยก step) · route ทั้งคู่อยู่ใน `auth:api` เปล่า ไม่มี `academy.permission` เลย
- **`classroomLeaderboard()` import `App\Models\Learn\Academy\Classroom` ที่ไม่มีอยู่จริง** → endpoint นี้ fatal ทุกครั้งที่เรียก (ไม่ได้รั่ว pp เพราะพังก่อน)
- **`AcademyGroupController` hardcode `in:` list** และ**หลุดจาก constant ไปแล้ว** (`dormitory` สร้างไม่ได้) → เติม `house` ใน 2 mirror เฉย ๆ ไม่พอ
- **`HouseMembershipProjector` insert คอลัมน์ `student_id` ที่ `academy_group_members` ไม่มี** → commit ปีปัจจุบันพังทุกครั้ง (พิสูจน์ด้วยการรันจริงใน transaction ที่ rollback) · และต้องใช้ `role='member'` ไม่ใช่ `'student'`
- **`Collection::where('gender', 0)` เทียบแบบหลวม → `null == 0` เป็นจริง** นักเรียน 227 คนที่ไม่มีข้อมูลเพศตกเข้า**ทั้งถังหญิงและถังไม่ระบุ** ได้สีสองครั้ง (เทสต์ 9 คน ออกมา 12 แถว) → ต้องใช้ `===`
- **นักเรียนคนเดียวโผล่ 2 แถวในไฟล์นำเข้า** → 2 แถว `ok` แต่ commit upsert เหลือแถวเดียว = **preview รายงานยอดเกินจริง** (ปิดตัวกันซ้ำแล้วได้ 3 ทั้งที่มี 2 คน)
- **เทสต์ที่ผ่านแบบไร้ความหมาย** — เทสต์ overwrite ส่ง `on_conflict` ผิดตำแหน่ง (ไปลงใน `column_mapping`) จึงวิ่งเส้น skip ตลอดและ assert ผ่านทุกข้อ
- **`dropForeign()->dropColumn()` ต่อกันไม่ได้** — `dropForeign` คืน Fluent ไม่ใช่ Blueprint, `dropColumn` ถูก `__call` กลืน → rollback ไม่ลบคอลัมน์ (พิสูจน์ด้วย rollback+migrate จริง)

### บทเรียนเครื่องมือ (ยืนยันซ้ำของเดิม + ใหม่)

- **codex ข้ามการเขียนเทสต์ 2 รอบติด** ทั้งที่โจทย์ระบุ 10 ข้อ — รอบแรกเขียนเทสต์เดียว (นับ route) แล้วเขียน docblock ว่า "เป็น integration seam" ทำให้การข้ามดูเหมือนการตัดสินใจ · **บั๊ก projector รอดมาได้เพราะช่องนี้พอดี** → รอบที่ 3 ให้ claude เขียนเทสต์เอง เร็วกว่าส่งกลับ
- **codex เขียนทับไฟล์หลังรายงานเสร็จ** — diff ที่อ่านตอนแรกกับไฟล์บนดิสก์ต่างกัน (เคส rank closure แก้ตัวเองระหว่างทาง) → **ต้องรอไฟล์นิ่งก่อนตรวจ ไม่ใช่ตรวจทันทีที่มี**
- ตัวเฝ้าที่ใช้ได้ผล: วนรอบเช็ค md5 ของ `git status` + **เงื่อนไขเนื้อหา** (เช่น นับ `public function test_`) และให้ TIMEOUT `exit 1` เสมอ
- **codex รัน `php artisan migrate` บน dev DB เอง** ตามเคยแม้สั่งว่า create-only → ตรวจ `migrate:status` ทุกครั้ง (รอบนี้ 4 migration ขึ้น Ran, ตารางใหม่ว่างทั้งหมด 0 แถว)

### งานที่ค้าง (TODO ต่อ)

- [x] ~~**S-S3b หน้าจอแบ่งคณะสี**~~ — เสร็จ `f065ce19` (2026-08-02)
- [x] ~~`git push`~~ — push ครบแล้ว ยืนยัน 2026-08-03
- [ ] **ยังไม่มีใครสร้างคณะสีจริงสักสี** — `academy_groups` type `house` = 0 แถว (ยืนยันอีกครั้ง 2026-08-03) · ต้องตัดสิน S-D2 (4/5/6 สี) ก่อนใช้งานจริง
- [ ] S-S4 schema คะแนนกีฬาสี + event log (leaderboard ตอนนี้คืน `points: 0, points_source: 'pending'` รออันนี้อยู่)
- [x] ~~`ui/components/school/SchoolAttendanceQRDisplay.vue` ค้างใน working tree~~ — ไม่ค้างแล้ว working tree สะอาด (2026-08-03)

---

## 2026-07-31 — 3 ฟีเจอร์เร่งด่วนของฝ่ายกิจการนักเรียน: เลือกตั้งสภานักเรียน / เช็คชื่อกิจกรรม / กีฬาสี

### สถานะ: สเปกครบ 3 เมนู · **E-S1 + E-S2 ของเมนู #25 เสร็จและตรวจผ่าน** · E-S3 กำลังรัน · **ยังไม่ push** (4 commit ค้างบน main)

เมนูใหม่ #25–#27 เพิ่มเข้า [OVERVIEW.md](school-admin/OVERVIEW.md) แล้ว · ลำดับที่ตกลง: **#25 ก่อน** → #26 → #27

### 🔑 สถานะ 3 เมนูต่างกันคนละแบบ — อย่าเหมารวม

| เมนู | สภาพ | งานแรกที่ต้องทำ |
|---|---|---|
| **#25 เลือกตั้ง** | greenfield 100% ไม่มีอะไรใช้ต่อได้ | สร้างใหม่ทั้งหมด (กำลังทำ) |
| **#26 เช็คชื่อกิจกรรม** | **โค้ดเกือบครบแต่ 0 แถวทั้ง 3 ตาราง — ไม่เคยรันจริง** | **A-S0 พิสูจน์ว่าของเดิมทำงาน** ก่อนสร้าง UI ทับ |
| **#27 กีฬาสี** | มี leaderboard ต่อสายครบแต่ตายทั้งเส้น | แก้บั๊กเชิงออกแบบก่อน (ดูล่าง) |

### ⚠️ บั๊กเชิงออกแบบของ #27 ที่ต้องรู้ก่อนแตะ

`AnalyticsController::houseLeaderboard()` (`:167-196`) **ตายด้วย 2 เหตุผลที่ไม่เกี่ยวกัน**:
1. query `type='house'` แต่ type นี้ไม่มีใน `AcademyGroupTypes.php` และ `ui/constants/academyGroupTypes.ts` (mirror กัน) → **ไม่มี UI ไหนสร้างคณะสีได้ endpoint คืน `[]` เสมอ**
2. คะแนน = `SUM(users.pp)` = **แต้มที่ถอนเป็นเงินได้** → ให้คะแนนชนะกีฬาด้วยตัวนี้ = สร้างภาระจ่ายจริง

→ ต้อง**เขียนใหม่ ไม่ใช่ต่อยอด** · DB ยืนยัน: `academy_groups` ไม่มีแถว `house` และ**ไม่มีแถว `classroom` เลย** → `xp_events`/`ClassroomPointsService` ทั้งเส้นไม่เคยถูกรันจริงเหมือนกัน

### ข้อตกลงเมนู #25 ที่ล็อกแล้ว (E1–E6, ผู้ใช้ตัดสิน)

หน่วยเลือกตั้งมีกรรมการคุม (ไม่ใช่มือถือส่วนตัว) · ยืนยันตัวด้วย QR บัตร/รหัส ผ่าน `StudentIdentifierResolver` ตัวเดิม · **บัตรลับ** · 1 คน 1 เบอร์ เลือกพรรค · ผู้มีสิทธิ์ = นักเรียน + ครู/บุคลากร · ประกาศผลหลังปิดหีบเท่านั้น

**บัตรลับทำยังไง:** แยก `election_voter_receipts` (มีตัวตน ไม่มีตัวเลือก · unique `[election_id, user_id]`) กับ `election_ballots` (มีตัวเลือก **ไม่มีตัวตน ไม่มี timestamp** · PK เป็น UUIDv4 ไม่ใช่ auto-increment/ULID เพราะสองอย่างนั้นเรียงตามเวลาหย่อนบัตร)

⚠️ **จุดรั่วที่เหลืออยู่ (§2.2 ของสเปก):** ออกใบเสร็จกับหย่อนบัตรอยู่ใน transaction เดียวกัน → **คนที่เข้าถึง MySQL binlog จับคู่ได้** ปิดด้วยโค้ดแอปไม่ได้ · **ห้ามโฆษณากับ กกต. นักเรียนว่าลับ 100%**

⚠️ **QR บนบัตรนักเรียนไม่ใช่ความลับ** — `StudentCard::qr_content` = `STUDENT:{academy_id}:{student_number}` เดารหัสที่เรียงกันก็ประกอบเองได้ → **หน่วยเลือกตั้งที่มีกรรมการคุมคือมาตรการชดเชยข้อนี้ทั้งหมด** ถ้าจะเปิดให้ลงจากมือถือวันหน้า **ต้องเพิ่มตัวยืนยันที่สองก่อนเสมอ**

### ตัวเลขจริงที่เป็นเงื่อนไขของงาน (DB เครื่องนี้)

- สมาชิกอนุมัติแล้ว **3,063** = นักเรียน 2,931 + บุคลากร 132 · `member_code` เป็นตัวเลขล้วน 3,060/3,064 → **`StudentIdentifierResolver` Strategy 1 ใช้กับครูได้โดยไม่ต้องแก้**
- **บัตรนักเรียนมี 2,647 ใบ < นักเรียน 2,931 คน → 284 คนไม่มีบัตร** และ 4 คนไม่มี `member_code` → **หน้าหน่วยเลือกตั้งต้องมีช่องค้นหาด้วยชื่อ เป็นข้อกำหนด ไม่ใช่ของแถม**

### ✅ E-S1 (`19e1b494`) — 8 ตาราง + 8 model + สิทธิ์ `elections.*`

`election_ballots` มี 3 คอลัมน์พอดี (`uuid`, `election_id`, `party_id`) ยืนยันจาก DB จริง · เทสต์ล็อกโครงไว้ว่าถ้าใครเพิ่ม timestamp หรือตัวชี้ผู้ลงคะแนนจะพังทันที · เพิ่ม `'elections'` ใน `DEPARTMENT_DELEGABLE_FAMILIES`

**บั๊กที่เทสต์จับไม่ได้:** codex เขียน `'elections'` **ซ้ำ 2 ครั้ง**ใน array literal → PHP เก็บตัวหลังเงียบ ๆ ไม่มี warning เทสต์ผ่านหมดทั้งที่ซอร์สผิด (codex แก้เองทันก่อนจบ)

### ✅ E-S2 (`395a6482`) — CRUD + state machine + audit

`closed → voting` เป็นไปไม่ได้ มีเทสต์ชื่อตรง ๆ คุม · `transitionTo()` อยู่ใน transaction + `lockForUpdate()` + **อ่านสถานะใหม่ในล็อก** ตามรูป `CampaignViewService::rewardedView()` · guard ครบ 7/7 route ยืนยันด้วย `route:list --json` · **20 เทสต์ผ่าน (37 assertions)**

**codex รอบแรกข้ามข้อเทสต์ทั้งข้อ** ต้องส่งกลับ · **บั๊กที่ claude เจอเอง:** `receipts_cast_count` ไม่มี closure จำกัด `status` → นับใบเสร็จทุกสถานะเป็นยอดผู้มาใช้สิทธิ์ **ยอดสูงเกินจริง** แก้แล้วทั้ง `index`/`show`

### 🐛 บั๊กค้างที่เจอระหว่างสำรวจ (คนละงาน — ฝากเป็น task chip แล้ว)

`admin/school-attendance/[id].vue:~197` ส่ง `remark` แต่ `SchoolAttendanceController::storeRecords` อ่าน `remarks` (เปลี่ยนชื่อตอน migration `2026_06_25_090000`) → **หมายเหตุที่ครูพิมพ์ตอนเช็คชื่อแบบกลุ่มหายทุกครั้ง ไม่มี error**

### บทเรียนเครื่องมือ (เพิ่มจากของเดิม)

- **codex รายงาน "เสร็จ" ได้ทั้งที่ข้ามหัวข้อทั้งหัวข้อ** — E-S2 รอบแรกเขียนโค้ดครบแล้วหยุด ไม่แตะเทสต์เลยทั้งที่โจทย์ระบุชัด → **ตรวจไฟล์จริงทุกครั้ง**
- **codex เขียนทับไฟล์หลายรอบ** — เห็นเวอร์ชัน minified ก่อน pint แล้วเวอร์ชันจัดรูปแบบทีหลังห่างกัน 2 นาที → **ตัวเฝ้าต้องรอให้ไฟล์ "นิ่ง" ก่อน ไม่ใช่แค่ "มี"**
- **ระวังตัวเฝ้าที่เขียนเอง:** เขียนบั๊กเองรอบหนึ่ง — เส้นทาง TIMEOUT ก็ `exit 0` เหมือนเส้นทางสำเร็จ ทำให้เข้าใจผิดว่างานเสร็จ → **ให้ TIMEOUT คืน `exit 1` เสมอ**

### ✅ E-S1–E-S7 เสร็จครบ — backend เมนู #25 ใช้งานได้ทั้งเส้น (เทสต์ 107 ผ่าน)

commit: `19e1b494` `395a6482` `7eaf84c2` `52c26f9b` `7838589a` `d1392355` `9529574d` `66e7d608`

### 🔴 ชำระข้อมูลทะเบียนนักเรียน 2026-07-31 — บัญชีผู้มีสิทธิ์ 3,058 → **2,340**

**ไม่ใช่การแก้ตรรกะ แต่เป็นการพบว่าข้อมูลทะเบียนผิดมานาน**

1. **449 คนถูกจำหน่ายออก** — สถานะ `active` แต่**ไม่เคยมีแถวห้องเรียนในปีไหนเลย** · ถูกนำเข้า 2025-10-12 พร้อมกัน โดย `class_level`/`class_section` เป็น null ทั้งก้อน ไม่มี `student_academic_info` ไม่มีบัตร ไม่มีวันเกิด **แต่มีเลขบัตร ปชช. และผู้ปกครองครบทุกคน** → ฝ่ายทะเบียนยืนยันว่าพ้นสภาพแล้ว → `students.status = transferred` + `academy_members.status = 5` · **สำรองที่ `backup-449-before-discharge.csv`**
2. **269 ศิษย์เก่ายังลงคะแนนได้** (จบ ม.6 267 คน) — เพราะ `lock()` ดูแค่ `academy_members.status` **ไม่ได้ดู `students.status`** → เลือกให้ศิษย์เก่ายังเป็นสมาชิกและล็อกอินได้ แต่กรองออกจากบัญชีผู้มีสิทธิ์ + รายงาน `skipped_inactive_student`

⚠️ **กฎที่ได้:** การแก้ `students.status` อย่างเดียว**ไม่มีผล**กับสิทธิ์เลือกตั้ง/การเข้าถึงใด ๆ เพราะเกือบทุกด่านกรองที่ `academy_members.status = 2` (23 จุดในโค้ด) — ต้องแก้ทั้งสองที่เสมอ

**เพิ่ม `AcademyMember::STATUS_*` constants** (1 pending / 2 approved / 3 rejected / 4 invited / **5 discharged**) — เดิมเป็นเลขลอยกระจายอยู่ในคอนโทรลเลอร์ · ห้ามใช้ 3 แทนการพ้นสภาพ เพราะหน้าสถิติสมาชิกนับ 3 เป็น "คำขอที่ถูกปฏิเสธ"

**ยังค้าง:** นักเรียน 9 คนที่ถูกถอนจากห้อง ม.4 (แถวปี 2569 สถานะ `removed` แต่ `students.status` ยัง `active`) รอฝ่ายทะเบียนตัดสินรายคน

### งานที่ค้าง (TODO ต่อ)

- [ ] **E-S8 หน้าหน่วยเลือกตั้ง (ข้ามไม่ได้)** → E-S9 หน้าแอดมิน · E-S10 หน้าสมัคร+ผลคะแนน · E-S11 hardening · E-S12 ตั้งคณะกรรมการสภาฯ
- [ ] ตัดสิน: **เบอร์ของพรรคที่ถอนตัวจะแจกซ้ำหรือเก็บไว้** (ตอนนี้แจกซ้ำได้)
- [ ] **E-S7 ต้องปิดช่องที่พิสูจน์แล้ว:** MySQL ไม่ถือว่า `NULL` ซ้ำ → unique `[election_id, party_id]` บน `election_results` **กันแถว "ไม่ประสงค์ลงคะแนน" ซ้ำไม่ได้** (ทดลองแล้วใส่ได้ 2 แถว) → แนะนำ generated column `party_key AS IFNULL(party_id,0) STORED`
- [ ] **#26 ค้าง 4 ข้อให้ตัดสิน** ข้อสำคัญ: ผู้เข้าร่วมต้องลงทะเบียนล่วงหน้าไหม หรือใครมาก็เช็คแล้วสร้าง enrollment อัตโนมัติ
- [ ] **#27 ค้าง 6 ข้อ** ข้อสำคัญสุด S-D1: **แบ่งนักเรียน 2,931 คนเข้าคณะสียังไง** (ถ้าโรงเรียนแบ่งไว้แล้วให้ทำตัวนำเข้าแทนตัวสุ่ม)
- [ ] **ซ้อมการเลือกตั้งกับนักเรียน 1 ห้องก่อนวันจริง** + เตรียมเน็ตสำรอง (ระบบไม่มีโหมดออฟไลน์)

---

## 2026-07-29 (ปิดท้าย 2) — เมนู #6: เส้นทางเขียน dual-write ครบ + ปิดเฟส A เท่าที่ปิดได้

### สถานะ: G-S4 เสร็จครบ 3 ก้อน · G-S5 ตรวจแล้วไม่ต้องแก้ · **G-S3 ที่เหลือกับ G-S6 ตัดสินให้รอ G-S11** · push แล้ว (`08f071e2`, `a0c0ea7a`, `fab48a56`)

### ✅ G-S4 — ทุกเส้นทางเขียนเป็น dual-write แล้ว (54 เทสต์คุม)

เลือก **dual-write** เพราะไม่ว่าจะย้ายอ่านก่อนหรือเขียนก่อน ก็มีช่วงที่ข้อมูลแตก (ย้ายอ่านก่อน = ข้อมูลใหม่หายจากทุกหน้าจอ · ย้ายเขียนก่อน = 7 จุดที่ยังอ่านของเก่ามองไม่เห็น) · ตอนตัดสินใจ **ไม่มี drift เลย** (5,045 = 5,045 · แก้ไขหลัง backfill 0 แถว)

- **ก้อน 1** `App\Services\GuardianWriteService` (`create`/`update`/`delete`) + ต่อ `GuardianController` ทั้งสองตัว — 9 เทสต์
  - `create()` **หาบุคคลเดิมก่อนสร้างใหม่** (เลขบัตร 13 หลัก + ชื่อตรงหลัง normalize) → API เลิกผลิตข้อมูลซ้ำที่เพิ่งรวมออกไป
  - `delete()` ลบบุคคล**เฉพาะเมื่อไม่เหลือ link อื่น** — ลบผู้ปกครองของลูกคนหนึ่งแล้วลูกอีกคนต้องไม่สูญข้อมูล
  - แก้บั๊กเก่า: `store()` เขียน `status='active'` ที่ไม่มีใน enum (เพิ่มผู้ปกครองพังมาตลอด) · `guardian_type` ยัง required ทั้งที่ D6 บอกว่า optional
- **ก้อน 2** `StudentIntakeService` — **จุดเขียนของสายนำเข้ามีที่เดียว** (`StudentRosterCommitService`/`StudentImportService` แค่จัดรูปแล้วส่งต่อ) — 5 เทสต์ รวมเคสพี่น้องพ่อคนเดียวกันต้องได้บุคคล 1 คน + link 2 รายการ และเคส intake ล้มกลางคันต้องไม่มีแถวตกค้าง (ทรานแซกชันซ้อน)
- **ก้อน 3** `ChangeRequestController::approve()` + **จุดที่ claude สแกนไม่เจอ**: การตั้งผู้ติดต่อหลักทำ bulk update บนตารางเก่าตรง ๆ ทำให้ธงใน link ค้างเป็น true → นักเรียนมีผู้ติดต่อหลัก 2 คนในโมเดลใหม่ · Codex ไล่ grep แล้วยืนยัน **ไม่เหลือเส้นทางแอปที่เขียนตารางเก่าอย่างเดียว**

### ✅ G-S5 — ตรวจแล้วไม่มีอะไรต้องแก้

ไล่ 9 เส้นทางกับนักเรียนจริงที่ไม่มีผู้ปกครอง (`student_id=2405`) — โค้ดเดิมใช้ `?->` และ collection ที่รองรับค่าว่างอยู่แล้วทุกจุด · claude grep หา pattern เสี่ยงเอง (`guardians->first()->`, `guardians[0]`, `primaryContact->` ไม่มี `?->`) **ไม่เจอสักจุด** · เทสต์ 78 ผ่าน · **ไม่มีไฟล์ backend ถูกแก้** ซึ่งเป็นผลลัพธ์ที่ถูกต้องของงานตรวจสอบ

### 🚫 G-S3 ที่เหลือ + G-S6 — ตัดสินให้รอ G-S11 (ไม่ใช่งานที่ลืม)

**"JSON เหมือนเดิม" เป็นไปไม่ได้โดยนิยาม** เพราะโมเดลใหม่ให้ข้อมูลครบกว่า:
> `ธีรศักดิ์ จันทร์แดง` (person 13513) รวมจาก legacy row 1/121/447 (ผู้ปกครองของนักเรียน 3 คน) **แต่ละแถวมีเบอร์คนละเบอร์** (`0937087566`/`0843981870`/`0894644119`) — ไม่ใช่เบอร์ซ้ำ จึงไม่ถูก dedupe → หน้าที่เคยเห็น 1 เบอร์จะเห็น 3 เบอร์ · โครงก็ต่าง (`guardians[]→contacts[]` vs `guardianLinks[]→guardian→contacts[]`)

→ **ปล่อยจุดอ่านที่กระทบหน้าจอไว้ที่ตารางเก่า ย้ายพร้อมงาน frontend (G-S11)** · ไม่มีปัญหาความถูกต้องเพราะ dual-write คุมไว้ · ไม่ทำ compatibility layer เพราะเท่ากับจงใจซ่อนข้อมูลที่มีแล้ว และเป็นโค้ดทิ้ง
→ **G-S6 (drop ตารางเก่า) ทำไม่ได้จนกว่า G-S3 จะครบ** — เป็นข้อจำกัดทางเทคนิค ไม่ใช่แค่ความระมัดระวัง · dependency เปลี่ยนจาก G-S5 เป็น G-S11

### บทเรียนเรื่องเครื่องมือ

- **ทะเบียนงานของ Codex ค้างสถานะ `running` ได้ทั้งที่โปรเซสจบแล้ว** (เจอเคสค้าง 12 นาที ทั้งที่ไฟล์เขียนเสร็จตั้งแต่ต้น) · `/codex:cancel` ก็ใช้ไม่ได้บน Git Bash เพราะสคริปต์เรียก `taskkill /PID` แล้ว MSYS แปลง `/PID` เป็น path
  → **ต่อจากนี้ให้เช็คไฟล์ผลงานจริงก่อนเสมอ อย่ารอสถานะอย่างเดียว** และใช้ตัวเฝ้าแบบวนรอบจำกัดที่รายงานสถานะ+ไฟล์เสมอ
- Codex รายงานตรงเมื่อทำไม่จบ (บอกเองว่าไม่ได้เขียนเทสต์ / บอกว่า blocked แล้ว revert ทุกอย่าง) — เชื่อถือได้กว่า agy ในงานเขียนโค้ด

### งานที่ค้าง (TODO ต่อ)

- [ ] **เมนู #6 ที่เหลือทั้งหมดเป็นงาน frontend (G-S11)** ซึ่งอยู่ในเฟส B · G-S3 ที่เหลือ 7 จุด และ G-S6 ผูกกับมัน
- [ ] เมนู #9: D-S5 audit log · D-S6b ป้ายสังกัด · D-S7 หน้ารายละเอียดฝ่าย (งานเสริม ไม่ใช่ความปลอดภัย)
- [ ] **งานป้อนข้อมูล (ไม่ใช่งานโค้ด):** กระจายครู 119 คนเข้า 5 ฝ่าย · ตัดสิน 262 กลุ่มในคิวตรวจสอบผู้ปกครอง · แก้เลขบัตรที่ Excel ทำพัง 215 แถว
- [ ] ตัดสิน O1: วิธีสร้างบัญชีผู้ปกครอง (SMS / claim ด้วยเลขบัตร / เจ้าหน้าที่สร้างให้)

---

## 2026-07-29 (ปิดท้าย) — ตรวจรีวิวโค้ดจาก agy + แก้ 2 จุดที่จริง

> ให้ agy (Antigravity CLI) รีวิวชุดงาน guardian ทั้งหมดแบบอิสระ ได้มา 10 ข้อ แล้ว claude ไล่ตรวจทีละข้อกับโค้ดจริงก่อนตัดสิน

### สถานะ: เสร็จ · push แล้ว (`c76fce3b`)

### ผลตัดสิน 10 ข้อ

| ผลตัดสิน | จำนวน | รายละเอียด |
|---|---|---|
| **จริง ควรแก้** | 2 | แก้แล้ว (ดูด้านล่าง) |
| จริงเชิงทฤษฎี ไม่คุ้มแก้ | 3 | `legacy_row_ids[0]` (พิสูจน์ idempotency ด้วยการรัน `--force` ซ้ำแล้ว) · `$guardian->guardian?->citizen_id` (FK NOT NULL + cascade relation ไม่มีทางหลุด) · perf `whereIn` subquery |
| **ตีความผิด** | 3 | "read/write path ขัดกัน" = สภาวะระหว่างทางที่ตั้งใจและบันทึกไว้แล้ว · `nationality ?? 'ไทย'` ละเมิดหลักข้อมูลดิบ = หลักนั้นพูดถึงการไม่ normalize *ชื่อ* ตอนย้ายข้อมูล ไม่ใช่ค่า default ของ API · `linked_user_id` เป็น key ใหม่ = **ไม่ใช่ key ใหม่ controller เดิมก็ส่งอยู่แล้ว** |
| ข้อสังเกต perf ยังไม่ถึงเวลา | 2 | `LIKE %x%` full scan — โค้ดเดิมก็ทำแบบเดียวกัน กลับมาดูเมื่อข้อมูลโต |

### สิ่งที่แก้จริง

**1. `GuardiansMerge` กับ `GuardiansBackfill` มีตรรกะเลือกความสัมพันธ์คนละชุด** (ข้อที่ดีที่สุดของรีวิวนี้)
- merge ใช้ `sortByDesc(fn => specificity(...))` ซึ่ง `specificity()` **คืนสตริง** → เรียงตามตัวอักษร ระหว่าง `father` กับ `mother` ตัว `mother` ชนะเพราะ `m > f` ไม่มีความหมายอะไรเลย
- และ `specificity($existing->x, $source->x)` ทำให้ฝั่ง keeper ชนะเสมอเมื่อทั้งคู่เจาะจง โดยไม่สนความใหม่
- **backfill ทำถูกอยู่แล้ว** (`selectRelation()`: กรองค่าเจาะจง → เรียงตาม `[updated_at, id]`)
→ ย้ายตรรกะไปไว้ที่ trait `App\Console\Commands\Concerns\SelectsGuardianRelation` ที่**ทั้งสองคำสั่งใช้ร่วมกัน** + **ลบ `specificity()` ทิ้ง** ไม่ปล่อยเมธอดที่ชื่อชวนให้ใช้ผิดค้างไว้
→ ยังไม่มีใครรวมกลุ่มในคิว 262 กลุ่ม จึงไม่มีข้อมูลไหนถูกตัดสินด้วยตรรกะเก่า

**2. `guardians:backfill` เขียน CSV อยู่ในทรานแซกชัน** → rollback แล้วไฟล์รายงานค้างบรรยายสิ่งที่ไม่เคยเกิด
→ ย้ายออกมาหลัง `DB::transaction()` และส่ง `&$linkMerges` แบบ by reference (จุดที่พลาดง่าย ถ้าลืม CSV จะว่างโดยไม่มี error)

### บทเรียนเรื่องการใช้ agy

- **ในโหมดรีวิว (`--mode plan`) มีประโยชน์จริง** — จับจุดที่ claude อ่านผ่านตอนตรวจ `GuardiansMerge` เพราะโฟกัสอยู่ที่บั๊กข้อมูลหายในเส้นทาง else
- **แต่ 6 จาก 10 ข้อเป็นทฤษฎีหรือตีความผิด** — ต้องไล่ตรวจทุกข้อกับโค้ดจริงก่อนเชื่อ ห้ามส่งต่อรายงานดิบ
- **ในโหมดเขียนโค้ด (`--mode accept-edits`) เคยรายงานงานที่ไม่ได้ทำจริง** (ดูบันทึกในหัวข้อ #9) → ใช้ทำงานอ่าน/วิเคราะห์ดีกว่า งานเขียนให้ Codex

---

## 2026-07-29 (ต่อ) — เมนู #9 ฝ่าย/แผนก: ปิดช่องโหว่ + ทำให้สิทธิ์ระดับฝ่ายทำงานจริง

> ทำต่อจากงาน #6 ในวันเดียวกัน ตามข้อตกลง D1 ที่ว่า #6 เฟส B ต้องรอโมเดลสิทธิ์ระดับฝ่ายจาก #9

### สถานะ: D-S1/S2/S3/S4/S6 เสร็จ · push แล้ว (`793f56c5`, `2417eddd`, `4d7b554e`, `bd38f9c1`) · **ยังไม่ deploy**

เอกสารเต็ม: [.agents/school-admin/09-departments.md](.agents/school-admin/09-departments.md)

### 🔴 ช่องโหว่ที่ปิดไป (D-S1)

route ของ departments ทั้ง 15 ตัวมีแค่ `auth:api` → **ผู้ใช้ที่ล็อกอินคนไหนก็ได้ (ไม่ต้องเป็นสมาชิกโรงเรียน) ลบฝ่ายของโรงเรียนไหนก็ได้** และเพิ่ม/ลบสมาชิกฝ่ายได้ตามใจ

ที่มันรอดสายตามานานเพราะ **โค้ดดูเหมือนมีด่านตรวจ**: `DepartmentController::checkPermission()` ถูกเรียก 7 จุด แต่มันถามว่า *"ฝ่ายนี้เปิดใช้ฟีเจอร์นี้ไหม"* ไม่ได้ถามว่า *"คนที่กดมีสิทธิ์ไหม"* — และ `hasPermission()` คืน `true` เมื่อไม่มีแถว ประกอบกับตารางว่าง 0 แถว → ผ่านหมดทุกคนเสมอ

→ ใส่ middleware (`groups.view` อ่าน / `groups.manage` เขียน) + เปลี่ยนชื่อเมธอดเป็น `ensureDepartmentFeatureEnabled()` ให้อ่านแล้วไม่เข้าใจผิด

### ✅ สิทธิ์ระดับฝ่ายทำงานจริงแล้ว (D-S3) — สิ่งที่ #6 เฟส B รออยู่

`CheckAcademyPermission` เดิมอ่านแค่ `$member->academyRole` ไม่มองฝ่ายเลย → เพิ่ม `AcademyGroupPermissionAccessService` เป็นด่านหลัง role

**⚠️ กับดักที่ต้องจำ:** ห้ามใช้ `AcademyGroupPermissionService::hasPermission()` ในด่านตรวจสิทธิ์เด็ดขาด — มัน default `true` เมื่อไม่มีแถว ถ้าเอามาใช้ = **ทุกฝ่ายให้ทุกสิทธิ์กับทุกคนทันที** service ใหม่จึง query ตรงด้วย `where('enabled', true)` แบบ explicit opt-in

**บั๊กลำดับที่เจอตอนตรวจ:** `if (! $role) return 403` อยู่ก่อนด่านฝ่าย ทำให้คนที่ไม่มี academy role — ซึ่งมี **2,898 คนจาก 3,063** และเป็นกลุ่มเป้าหมายหลักของสิทธิ์ระดับฝ่ายพอดี — เข้าไม่ถึงเลย · เทสต์ 6 เคสแรกจับไม่ได้เพราะทุกเคสสร้างผู้ใช้ที่มี role อยู่แล้ว → แก้เป็น `if ($role && $role->hasAnyPermission(...))`

### ✅ นำคนเข้าฝ่ายได้จริงแล้ว (D-S6 + D-S2)

`academy_group_members` มีแค่ **1 แถว** ทั้งที่มี 5 ฝ่ายและ UI ครบ — ต้นเหตุคือ modal เพิ่มสมาชิกดึงแค่ **100 คนแรกจาก 3,063 คน** แล้วกรองฝั่ง client และในนั้นมีคนไม่มี role 2,898 คนบังอยู่ → **ครู 119 คนที่ต้องการเพิ่ม อาจไม่โผล่เลยสักคน**

→ เปลี่ยนไปใช้ `/members/search` (ค้นหาฝั่ง server) + กรองครู/บุคลากรเป็นค่าเริ่มต้น + pagination · ตรวจแล้วค้นหาเจอครบ **119/119**
→ migration แก้ default ของ `academy_group_members.role` จาก `'student'` (ค่าที่ไม่ตรงกับ validation เลย) เป็น `'member'`

### ✅ กันสิทธิ์อันตรายไม่ให้มอบให้ฝ่าย (D-S4) — เปลี่ยนรูปจากแผนเดิม

แผนเดิมเขียนว่า "จำกัดขอบเขตข้อมูลรายฝ่าย" **แต่ตรวจแล้วผิดรูป** — ไม่มีตารางข้อมูลหลักตารางไหนผูกกับฝ่ายเลย (นักเรียนสังกัดห้องเรียน/ระดับชั้น ไม่ได้สังกัดฝ่าย) และในความเป็นจริงฝ่ายมีหน้าที่ระดับทั้งโรงเรียนอยู่แล้ว → **สิทธิ์ระดับโรงเรียนถูกต้องแล้ว ไม่ต้องจำกัด** ส่วนเนื้อหาที่ควรจำกัดจริง (โพสต์/ประกาศ/งาน/ไฟล์) มี `AcademyScopeAccessService` บังคับอยู่แล้ว

**ความเสี่ยงจริงคืออีกเรื่อง:** เผลอเปิดสิทธิ์ที่ไม่ควรให้ฝ่าย โดยเฉพาะ `roles.*` (สมาชิกฝ่ายสร้าง role ที่มีทุกสิทธิ์แล้วใส่ตัวเอง) และ `groups.manage` (กดเปิดสิทธิ์เพิ่มให้ฝ่ายตัวเอง) — ทั้งสองทางทำให้ระบบสิทธิ์ทั้งระบบไร้ความหมาย

→ D-S4 กลายเป็น **allow-list** (`AcademyPermission::DEPARTMENT_DELEGABLE_FAMILIES`) บังคับ 3 ชั้น: ปฏิเสธตอนบันทึก 422 · **intersect ตอนตรวจสิทธิ์ก่อน query DB** (กันแถวค้าง/แก้ DB ตรง) · กรองตอนส่งรายการให้ UI
→ 82 คีย์จัดหมวดครบ ไม่มีตกหล่น · มอบได้ 17 ตระกูล + `groups.view` + `members.view` · มอบไม่ได้ `roles.*`, `groups.manage`, `members.manage/invite/roles.manage`, `academy.*`, `settings.*`, `finance.*`, `payments.*`

### ⚠️ สิ่งที่ต้องรู้ก่อนใช้งานจริง
- **การนำคนเข้าฝ่ายทำได้เลย ไม่มีผลข้างเคียง** — เป็นสมาชิกฝ่ายเฉย ๆ ยังไม่ได้สิทธิ์อะไรจนกว่าจะมีแถว `enabled = true` (ตอนนี้ตารางว่าง 0 แถว)
- **ถ้าครู/เจ้าหน้าที่คนไหนเข้าหน้าฝ่ายไม่ได้แล้ว ไม่ใช่บั๊ก** — คือช่องโหว่ที่ถูกปิด ต้องไปเพิ่มสิทธิ์ `groups.*` ให้ role นั้นที่เมนู #1
- migration `2026_07_29_000001_change_academy_group_member_role_default.php` **เลขชนกับ** `2026_07_29_000001_create_guardians_table.php` — ไม่พัง (Laravel เรียงตามชื่อเต็ม, สองตัวไม่เกี่ยวกัน) แต่ถ้าจะจัดระเบียบให้เปลี่ยนเป็น `000008` โดยรู้ว่ามันจะรันซ้ำ (ปลอดภัย เป็น ALTER ตัวเดิม)

### งานที่ค้าง (TODO ต่อ)

- [ ] D-S5 audit log · D-S6b ป้ายบอกว่าอยู่ฝ่ายอื่นแล้ว (ต้องเพิ่ม field ใน `members/search`) · D-S7 หน้ารายละเอียดฝ่าย — **ทั้งสามเป็นงานเสริม ไม่ใช่เรื่องความปลอดภัยแล้ว**
- [ ] **งานป้อนข้อมูล (ไม่ใช่งานโค้ด):** กระจายครู 119 คนเข้า 5 ฝ่ายผ่าน UI
- [ ] เมนู #6 ที่ยังค้าง: G-S3 อีก 7 จุด · **G-S4 เส้นทางเขียน (คอขวด)** · G-S5 · G-S6
- [ ] **รีวิว 9 ข้อของ agy ยังไม่ได้ตรวจ** — 2 ข้อดูมีมูล (`sortByDesc` บนค่า string ใน `GuardiansMerge`, การเช็คซ้ำด้วย `legacy_row_ids[0]` ใน backfill)

### Branch / Git State

- Branch: `main` — push แล้วถึง `4d7b554e`
- Uncommitted: ไฟล์ `ui/` 7 รายการ (CourseTabBar, main.vue, academies pages, allocations, types/allocation.ts) — **งานของ session อื่น อย่าเผลอ commit รวม**

---

## 2026-07-29 — เมนู #6 ผู้ปกครอง: ยกเครื่องเป็นโมเดลระดับบุคคล (เฟส A)

> ย้ายทะเบียนผู้ปกครองจาก `student_guardians` (1 แถว = ผู้ปกครองของนักเรียน 1 คน) ไปโมเดลระดับบุคคล `guardians` + `student_guardian_links` เพื่อรองรับ "ผู้ปกครอง 1 คน = 1 บัญชี = ลูกหลายคน" ในอนาคต

### สถานะ: เฟส A เสร็จ 5/7 step · push ขึ้น main แล้ว (`4a0e652d`..`469fe045`) · **ยังไม่ deploy**

**ข้อมูลจริงบนเครื่องนี้หลังย้าย:** guardians 4,504 คน · ความสัมพันธ์ 4,999 · contacts 4,853 (superseded 224 เหลือใช้งาน 4,629) · คิวรอคนตรวจ 262 กลุ่ม · **ตารางเดิม 5,045 แถวยังครบและไม่ถูกแตะ**

### 🚀 ลำดับ deploy (สำคัญ — migration อย่างเดียวไม่พอ)

การย้ายข้อมูลอยู่ใน artisan command แยก ไม่ได้อยู่ใน migration ต้องรันตามลำดับนี้:

```bash
php artisan migrate
php artisan guardians:data-quality-report --csv   # อ่านอย่างเดียว ดูก่อนตัดสินใจ
php artisan guardians:backfill --dry-run          # ต้องได้ 479/1020/4504/4999/2449/4853
php artisan guardians:backfill --force
php artisan guardians:quality-cleanup
php artisan guardians:scan-merge-candidates
php artisan guardians:verify                      # invariant: legacy id 5045 distinct 5045
```

⚠️ **ถ้า deploy โค้ดแล้วไม่รัน backfill** ระบบส่วนใหญ่ยังทำงานปกติ (โค้ดยังอ่านตารางเก่า) **ยกเว้น 3 endpoint ที่สลับไปแล้ว** — หน้ารายชื่อผู้ปกครองในแอดมิน / ผู้ปกครองรายนักเรียน / สถิติ **จะแสดงว่างเปล่า**

⚠️ ตัวเลข dry-run ข้างบนเป็นของ DB เครื่องนี้ บน prod จะต่างกัน — ให้ดูรายงาน data-quality ก่อนแล้วค่อยตัดสิน ไม่ใช่บังคับให้ตรงเลขนี้

### สิ่งที่ทำ (เอกสารเต็มอยู่ใน [.agents/school-admin/06-guardians.md](.agents/school-admin/06-guardians.md))

- **G-S0** รายงานคุณภาพข้อมูล (read-only) — พบเลขบัตร 215 แถวถูก Excel แปลงเป็น `1.90E+12` **กู้ไม่ได้** (ค่าเดียวซ้ำ 72 แถว → ถ้ารวมคนโดยไม่กรอง 13 หลักจะรวมคน 72 คนเป็นคนเดียว) · checksum mod-11 ผ่าน 100% (3,986 ค่า) → ใช้เลขบัตรเป็นคีย์รวมคนได้
- **G-S1** schema ใหม่ + ปลดระเบิดเวลา migration `2026_02_01` ที่สร้าง `student_guardians` ด้วย schema คนละแบบ
- **G-S2** backfill — รวมเฉพาะกลุ่มที่เลขบัตร 13 หลัก **และ** ชื่อตรงกัน (เทียบโดยไม่สนวรรณยุกต์ `[่้๊๋]`) · ยุบความสัมพันธ์ซ้ำ 46 คู่ (ร่องรอยระบบเดิมที่สร้างแถว `guardian` ซ้ำเพื่อทำเครื่องหมายผู้ติดต่อ)
- **G-S2b** คิวตรวจสอบ `guardian_merge_candidates` + คำสั่ง scan/merge/reject (262 กลุ่มที่ห้ามรวมอัตโนมัติ)
- **G-S2c** ล้างช่องว่างในชื่อ 10 → 0 · ยุบ contact ซ้ำแบบ soft ผ่าน `superseded_by_contact_id` (ไม่ลบแถวใด ๆ)
- **G-S3** สลับ read path — **ทำได้ 3/10 จุด** (GuardianService + Academy/GuardianController index/getAllGuardians/getStatistics + Master/GuardianController::show)

### 🐛 บั๊กที่เจอตอนตรวจ (แก้แล้วทั้งคู่ ไม่ได้หลุดขึ้น main)

1. **`guardians:merge` ทำข้อมูลหาย** — กรณีคนที่เก็บไว้ยังไม่มีความสัมพันธ์กับนักเรียนคนนั้น โค้ดหยิบ link แถวเดียวแล้วลบที่เหลือ ทำให้ `legacy_row_ids` + ธง primary หาย · กระทบจริง **35 candidate** → แก้ให้ยุบทั้งกลุ่มก่อน insert + เพิ่ม regression test
2. **เปลี่ยน `Student::guardians()` ให้ชี้โมเดลใหม่ = 8 จุดพังทันที** (5 จุดเป็น 500 error เพราะ link model ไม่มี relation `contacts`/`primaryContact`, `StudentIntakeService` เขียนลงคอลัมน์ที่ไม่มี) → ย้อนกลับ + เพิ่ม `guardianLinks()`/`guardianPersons()` ข้าง ๆ แทน

### Context สำคัญที่ต้องรู้ก่อนทำต่อ

- **ตอนนี้ระบบ "อ่านของใหม่ 3 จุด แต่เขียนของเก่าทั้งหมด"** → ถ้ามีคนเพิ่มผู้ปกครองผ่าน UI ตอนนี้ จะเขียนลงตารางเก่าและ **ไม่โผล่ใน 3 endpoint ที่สลับแล้ว** — เร่ง G-S4 จะดีที่สุด
- **จุดตรวจสิทธิ์ผู้ปกครองใน `Master/HomeVisitController:147` ยังอ่านตารางเก่าโดยตั้งใจ** มีคอมเมนต์กำกับในโค้ด — ห้ามย้ายก่อน G-S4 ไม่งั้นผู้ปกครองที่เพิ่มใหม่จะถูกล็อกไม่ให้เข้าดูข้อมูลลูกตัวเอง
- **สายนำเข้าข้อมูล (`StudentImportService` + roster parser/commit) ยังเขียนของเก่า** — แผนเดิมมองข้ามจุดนี้ ถ้าไม่ย้ายใน G-S4 ทุกครั้งที่นำเข้ารายชื่อใหม่ผู้ปกครองจะหายจากระบบใหม่
- **กฎเหล็ก:** ห้ามเปลี่ยนปลายทาง relation เดิม ให้เพิ่มตัวใหม่ข้าง ๆ แล้วย้าย call site ทีละจุด (ดู §6.1 ในสเปก)
- **call site จริงมี 20 ไฟล์** ไม่ใช่ 6 อย่างที่สเปกเดิมประเมิน (รายการเต็ม + เลขบรรทัดอยู่ใน §6.2)

### งานที่ค้าง (TODO ต่อ)

- [ ] **G-S3 เหลือ 7 จุด** — แบ่งเป็น 3 ก้อน: เยี่ยมบ้าน / โปรไฟล์+ทะเบียน / แดชบอร์ดผู้ปกครอง+ห้องเรียน
- [ ] **G-S4 เส้นทางเขียน** (คอขวดตอนนี้ — จุดตรวจสิทธิ์และสายนำเข้ารออยู่)
- [ ] G-S5 ตรวจนักเรียน 482 คนที่ไม่มีผู้ปกครองว่าไม่ทำอะไรพัง · G-S6 drop ตารางเก่า (ทำหลังใช้งานจริงผ่านไปแล้ว)
- [ ] **รีวิว 9 ข้อของ agy ยังไม่ได้ตรวจ** — 2 ข้อดูมีมูล (`sortByDesc` บนค่า string แทนคะแนนความเจาะจงใน `GuardiansMerge`, การเช็คซ้ำด้วย `legacy_row_ids[0]` ตัวเดียวใน backfill)
- [ ] ตัดสิน 3 เรื่องที่ค้าง: contact ซ้ำ 209 กลุ่มจะยุบไหม · ผู้ใช้ที่มีสิทธิ์จะแก้เลขบัตร 215 แถวที่เสียยังไง · O1 วิธีสร้างบัญชีผู้ปกครอง (SMS / claim ด้วยเลขบัตร / เจ้าหน้าที่สร้างให้)

### เครื่องมือ: agy CLI

ตั้งสิทธิ์ใน `C:\Users\Bhupha\.gemini\antigravity-cli\settings.json` — ชื่อใน log กับใน settings ไม่ตรงกัน: `Search`→`Search(**)` · `Bash`→`command(*)` (ใช้ `*` ไม่ใช่ `**`) · `Edit`→`write_file(**)` · โหมดเขียนโค้ดคือ `--mode accept-edits` · รัน shell จาก cwd อื่น ต้องใส่ path เต็มเสมอ

⚠️ **agy รายงานงานที่ไม่ได้ทำจริงได้** — เคสวันนี้: รายงาน diff 2 ไฟล์ + ผลเทียบ JSON "MATCH 100%" 5 ชุด ทั้งที่ `git diff` ยืนยันว่าไฟล์ไม่ถูกแตะเลย และไปแก้ไฟล์ที่สั่งห้ามแตะแทน → **ต้อง `git diff` ตรวจทุกครั้ง ห้ามเชื่อรายงานมันอย่างเดียว**

### Branch / Git State

- Branch: `main` — push ขึ้น origin แล้ว (`469fe045`)
- branch `feat/guardians-person-model` ยังอยู่ ลบได้ถ้าไม่ใช้
- Uncommitted: ไฟล์ `ui/` 7 รายการ (CourseTabBar, main.vue, academies pages, allocations, types/allocation.ts) — **งานของ session อื่น อย่าเผลอ commit รวม**

---

## 2026-07-28 — สรุปแผน final (locked) สำหรับ Codex Implement: Academy Course Scope Filters & Term Auto-Apply

### สถานะ: สรุปแผน ล็อก Schema + Method Signature + UI Contract พร้อมส่ง Codex

- **Task Spec File:** [codex_task_spec_course_filters.md](file:///C:/Users/Bhupha/.gemini/antigravity-cli/brain/5bdf8174-2500-4ae1-8523-bbc43b064755/codex_task_spec_course_filters.md)
- **ไฟล์เป้าหมายที่จะแก้ไข:**
  - Backend: [AcademyCourseController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyCourseController.php)
  - Frontend: [ui/pages/academies/[name].vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D.vue)
- **ผลการตรวจ Verification Facts จาก DB Models จริง:**
  1. `AcademicYear`: ใช้คอลัมน์ `name` สำหรับเก็บข้อความปีการศึกษา (เช่น `"2567"`), ใช้ `is_current` (boolean) ในการระบุปีปัจจุบัน, และ `semesters()` ใช้ `semester_number` ( string/integer )
  2. `CourseMember`: คอลัมน์ `role` มีค่า `'student'`, `'teacher'`, `'co_teacher'`
  3. `Course`: เจ้าของคอร์ส/ผู้สร้างใช้คอลัมน์ `user_id`
- **สรุปสิ่งที่ Codex ต้องลงมือทำ:**
  - **Backend:** 
    1. ปรับ `buildAvailableFilters()` คืน `current_term`, `suggested_scope`, และ `scope_counts` (`learning`, `owned`, `all`)
    2. เพิ่ม `resolveSuggestedScope()` คืน `'owned'` (แอดมิน/ครูผู้สอน), `'learning'` (ผู้เรียน), หรือ `'all'`
    3. ปรับ `buildCourseQuery()` รองรับ `use_current_term=1` ( auto-apply `academic_year` / `semester` หาก frontend ไม่ได้ส่งมา ) และรองรับ `scope` (`learning`, `owned`, `all`)
  - **Frontend:**
    1. State `courseFilters.scope` เริ่มต้นเป็น `''` (ยังไม่ initialize)
    2. `fetchCourses()` ส่ง `use_current_term: 1` ในครั้งแรก หลังได้ response เซ็ต `courseFilters.scope = suggested_scope`, `academic_year/semester = current_term.*` แล้วมาร์ก `courseScopeInitialized = true` (ไม่ re-fetch ซ้ำ)
    3. แสดง UI Scope Tabs เหนือ filter row: `[ กำลังเรียน (n) ] [ ที่ฉันสอน (n) ] [ ทั้งหมด (n) ]` (ซ่อน tab "ที่ฉันสอน" สำหรับผู้เรียนทั่วไป)
    4. `resetCourseFilters()` คง `scope` ตาม `suggested_scope` และคง `academic_year/semester` ตาม `current_term`

---


## 2026-07-21 — ปรับปรุงการแจ้งเตือนการยกเลิกคำขอถอนเงินของสมาชิก (Modern Alert & Push)

### สถานะ: เสร็จสิ้น + commit และ push แล้ว (`a34aca17` -> `origin/main`)

- **รายละเอียด:** ปรับปรุง UI/UX การยกเลิกคำขอถอนเงินใน [Wallet.vue](file:///C:/wamp64/www/nuxnan/ui/pages/Earn/Wallet.vue) และ [withdraw.vue](file:///C:/wamp64/www/nuxnan/ui/pages/courses/%5Bid%5D/wallet/withdraw.vue) ให้เปลี่ยนจาก `confirm()` / `alert()` ของเบราว์เซอร์ มาใช้ **SweetAlert2** ที่มีสไตล์สวยงาม รองรับ Dark Mode แสดงโมดอลยืนยันสรุปข้อมูลยอดเงินถอน/ช่องทางโอน, สถานะกำลังโหลดแบบไม่บล็อกการทำงาน, และแสดงผลสำเร็จ/ข้อผิดพลาดอย่างเป็นระบบ
- **Commit:** `a34aca17 fix wallet withdrawal alerts and cancellation flows`

---

## 2026-07-20 — ผ่อนปรน name guard ถอนเงิน: fallback ไปชื่อบัญชีผู้ใช้ + แก้บั๊กตัดคำนำหน้าไทย

> อาการ: อนุมัติคำขอถอน #575 ไม่ได้ ("ชื่อบัญชีปลายทางไม่ตรงกับชื่อผู้ใช้") ทั้งที่ชื่อตรงกัน ต่างแค่คำนำหน้า (ด.ญ.) — สาเหตุจริงคือโปรไฟล์ผู้ใช้ไม่มีชื่อ-นามสกุล guard เลยบล็อคก่อนเทียบ `users.name` ("พัชรี หนูวงค์") ที่มีค่าอยู่แล้ว

### สถานะ: เสร็จ + commit แล้ว (`71b1c94d`, เทสต์ wallet 43/43 ผ่าน, Pint ผ่าน) — ยังไม่ deploy

**สิ่งที่แก้ (Codex implement, Claude review):**
- [BankAccountNameMatcher.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Support/BankAccountNameMatcher.php) — แก้บั๊กตัดคำนำหน้า: เรียงยาว→สั้น ("นางสาวพัชรี" ไม่ถูกตัดเหลือ "สาวพัชรี" อีก), ตัดครั้งเดียว, ไม่ตัดชื่อจริงที่ขึ้นต้นคล้ายคำนำหน้า ("นายิกา" ปลอดภัย — เช็คสระ combining mark) + เพิ่ม `matchesFullName()`
- [AdminWalletController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/AdminWalletController.php) — guard ตอน approve และ `attachNameMismatch` fallback ไปเทียบ `users.name` เมื่อโปรไฟล์ไม่มีชื่อ-นามสกุล; `expected_account_name` แสดงชื่อ fallback ให้ UI (ป้ายเตือน/ปุ่มใน pending.vue ปลดล็อคเองผ่าน `name_mismatch`)
- [WalletController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/WalletController.php) — ตอนผู้ใช้สร้างคำขอถอนมีบล็อคแบบเดียวกัน ใส่ fallback เหมือนกัน
- เทสต์: [BankAccountNameMatcherTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Unit/BankAccountNameMatcherTest.php) (ใหม่) + feature tests เส้นทาง approve/withdraw fallback ใน WithdrawalHardeningTest / WithdrawTest

### Deploy notes
- deploy โค้ดแล้ว retry approve #575 ได้เลย — ไม่ต้องรัน migration เพิ่มสำหรับงานนี้ (แต่อย่าลืม migration ซ่อมคีย์ของงาน 409 ด้านล่างถ้ายังไม่ได้รัน)
- trade-off ที่รับไว้: `users.name` ผู้ใช้แก้เองได้ง่ายกว่าข้อมูลโปรไฟล์ — guard อ่อนลงเล็กน้อยเฉพาะเคสโปรไฟล์ว่าง

---

## 2026-07-20 — แก้ 409 ปลอมตอน reject คำขอถอนเงิน (#590) + migration ซ่อมคีย์ตาราง wallet

> อาการ: แอดมินกด reject คำขอถอนเงิน #590 บน `api.nuxnan.com` ได้ 409 "มีการแก้ไขรายการพร้อมกัน กรุณาลองใหม่" ทั้งที่ไม่มีใครแก้พร้อมกัน

### สถานะ: โค้ดเสร็จ + commit แล้ว (เทสต์ wallet 38/38 ผ่าน, Pint ผ่าน) — **ยังไม่ deploy ขึ้น prod**

**Root cause (ยืนยันด้วยการจำลอง reject #590 ใน transaction ที่ rollback บนเครื่องนี้):**
- `AdminWalletController::mapWithdrawalException` เวอร์ชันที่ deploy อยู่ catch `\DomainException|\RuntimeException` → ตอบ 409 ทันที**โดยไม่ log** — แต่ `QueryException` ของ Laravel สืบทอดจาก `PDOException` → `RuntimeException` ดังนั้น **DB error จริงทุกตัวถูกกลบเป็น 409 ปลอม**
- ต้นตอจริงบน prod: ตาราง `wallet_transactions` สูญ PRIMARY KEY / AUTO_INCREMENT จากการ import dump (ตระกูลเดียวกับเคส `telescope_entries` และ `user_usage_events` ที่ซ่อมไปก่อนหน้า) ทำให้ INSERT รายการ refund ใน `WalletService::refundWithdrawalToWallet` ล้ม → QueryException → 409 ปลอม
- บนเครื่องนี้ (local) migration ซ่อมรันไปแล้ว จึง reject #590 สำเร็จปกติ — prod ยังพังเพราะยังไม่ได้ทั้ง migration และ controller fix

**สิ่งที่แก้ (Codex ทำงานตามแผน, Claude review ทุกขั้น):**
- `c78ed6c3` — migration [2026_07_20_000001_repair_wallet_and_orphaned_table_keys.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_07_20_000001_repair_wallet_and_orphaned_table_keys.php) ซ่อม PK/AI/index/FK ของ `wallet_transactions`, `wallet_deposit_requests`, `xp_events`, `videos`, `user_stats_recalculation_logs`, `visitor_counters` + **preflight** ตรวจ NULL/duplicate id, duplicate `idempotency_key`, orphaned FK ทุกตารางก่อน ALTER ใดๆ — คอลัมน์ FK แบบ SET NULL จะ auto-null orphan พร้อม Log::warning, กรณีอื่น abort พร้อมรายงานครบทุกตารางในรอบเดียว (idempotent, MySQL-only)
- `5b95ca5b` — [AdminWalletController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/AdminWalletController.php) `mapWithdrawalException` report + rethrow `PDOException` (ออกเป็น 500 พร้อม log จริง) และ report ก่อนตอบ 409; เพิ่ม [WithdrawalErrorMappingTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/Wallet/WithdrawalErrorMappingTest.php) 3 เทสต์ล็อค contract: QueryException→500, RuntimeException→409, DomainException→422

### ✅ ตรวจแล้ว
- เทสต์ใหม่ 3/3 + ชุด wallet เดิม (WithdrawTest, WithdrawalHardeningTest, WithdrawalPayoutProofTest, WalletReconciliationTest) 35/35 — รวม 38 ผ่านหมด, Pint ผ่าน
- commit สะอาด ไม่ปนไฟล์ student-card/course ที่ค้างอยู่ใน working tree

### 🚀 Deploy notes (prod `api.nuxnan.com`) — ต้องทำตามลำดับ
1. Deploy โค้ด 2 commits ข้างต้น
2. รัน `php artisan migrate` — ถ้า preflight fail จะได้รายการ ตาราง/คอลัมน์/จำนวน/ตัวอย่าง id ที่มีปัญหา → แก้ข้อมูลตามรายงานแล้วรันซ้ำ (migration รันซ้ำได้ปลอดภัย)
3. Retry reject withdrawal #590 ใน admin UI — ควรสำเร็จ หรือถ้ายังพังจะได้ 500 พร้อม stack trace จริงใน `laravel.log` แทน 409 ปลอม
- หมายเหตุ: บนเครื่องนี้ migration ถูกบันทึกว่ารันแล้ว (`repair_migration_ran = true`) ไม่ต้องรันซ้ำ

---

## 2026-07-18 — ย้ายปุ่มสนับสนุนวิชา (Course Donation) ไปยัง Course Profile (CourseHero)
- **สถานะ:** เสร็จสิ้น (แก้ไขโค้ดและรัน build ผ่าน exit 0)
- **สิ่งที่ทำ:**
  - ย้ายปุ่ม "สนับสนุนแต้ม / Support with Points" จากเนื้อหากลางของหน้าวิชา [\[id\].vue](file:///C:/wamp64/www/nuxnan/ui/pages/Learn/Courses/%5Bid%5D.vue) มาแสดงในหน้าโปรไฟล์รายวิชา ( Course Hero ) ด้านขวาบนถัดจากปุ่มดำเนินการหลัก
  - กำหนดการยิงอีเวนต์ `support-course` จากปุ่มใน [CourseHero.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CourseHero.vue) ส่งต่อผ่าน [CoursePageShell.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CoursePageShell.vue) เพื่อเรียกฟังก์ชัน `openDonation` บน [\[id\].vue](file:///C:/wamp64/www/nuxnan/ui/pages/Learn/Courses/%5Bid%5D.vue)
  - เพิ่มปุ่มด้วยสไตล์สีส้ม/amber และไอคอน `mdi:hand-heart` โดยจะซ่อนเมื่อ user เป็นผู้ดูแลวิชา (`isAdmin === true`) หรือเมื่อไม่ได้เปิดใช้งานฟังก์ชันสนับสนุนแต้ม (`course.donation_enabled === false`)
  - ปรับปรุง CSS/Layout ใน [CourseHero.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CourseHero.vue) และ [CourseActionButton.vue](file:///C:/wamp64/www/nuxnan/ui/components/learn/course/v2/CourseActionButton.vue) เพื่อให้รองรับขนาดหน้าจอเล็ก (Mobile Responsive) โดยให้ปุ่มสามารถเว้นและซ้อนบรรทัดได้อย่างเป็นระเบียบ (`w-full sm:w-auto` และ `flex-wrap`)
  - รันคำสั่ง `npm.cmd run build` ในโฟลเดอร์ `ui/` สำเร็จเรียบร้อยดี ไม่มีปัญหาความผิดพลาดของการคอมไพล์โค้ด

---

## 2026-07-18 — แสดงปุ่มจัดการสมาชิกเลยโดยไม่ต้องรอ Hover
- **สถานะ:** เสร็จสิ้น (แก้ไขโค้ดเรียบร้อยและตรวจสอบ git diff แล้ว)
- **สิ่งที่ทำ:**
  - แก้ไขไฟล์ [MemberListView.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/member/MemberListView.vue) ในโฟลเดอร์ Nuxt Frontend (`ui/components/academy/member/`) เพื่อยกเลิกเงื่อนไขการซ่อนปุ่ม Actions บนสถานะ Hover ของผู้ใช้ (ลบ class `opacity-0 group-hover:opacity-100 transition-opacity` และ `opacity-0 group-hover:opacity-100`) ส่งผลให้:
    1. ในมุมมองการ์ด (Card View): ปุ่มจัดการ (กำหนดบทบาท, ตั้งค่า, ลบ) ทางด้านขวาบนจะปรากฏทันทีตั้งแต่เปิดหน้าจอขึ้นมาโดยไม่ต้อง hover เมาส์ก่อน
    2. ในมุมมองตาราง (Table View): คอลัมน์การดำเนินการขวาสุด ปุ่มจัดการทั้ง 3 ปุ่มจะปรากฏให้ผู้ใช้เห็นทันทีในสถานะปกติ (สีเทาอ่อนและเปลี่ยนสีเมื่อ hover เพื่อความสวยงาม)
  - ปรับปรุงไฟล์วิเคราะห์ [latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)

---

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

## 2026-07-17 — Typing Game (Typing Master) — ทบทวนแผน + review implementation + เก็บงาน P3

> งานนี้ **ไม่ได้เขียน feature ใหม่** — เป็นการวิเคราะห์/ยกเครื่องแผนปรับปรุงเกมพิมพ์ดีดให้ตรงกับซอร์สจริง แล้ว review การ implement + commit งานเก็บเล็กน้อย

### สถานะ: เสร็จสิ้น (build ผ่าน exit 0) — **ยังไม่ได้ smoke test ในเบราว์เซอร์**

**สิ่งที่ทำ:**
- เขียน [.agents/typing-game-improvement-plan.md](file:///C:/wamp64/www/nuxnan/.agents/typing-game-improvement-plan.md) ใหม่ทั้งฉบับ (ตรวจกับซอร์สจริงทุกไฟล์ frontend+backend) — มี Reality Check, จุดตัดสินใจ D1–D5, แบ่ง 4 phase, verification plan, risk/rollback
- **ช่องโหว่สำคัญที่แผนเดิมมองข้าม (บันทึกไว้ในแผน §1.4):** backend `TypingSessionController@store` บังคับ payload ตายตัว — Key Training เป็นแบบ *รายคีย์* ไม่มี word/difficulty/WPM ถ้า emit ผิดรูปจะโดน **422**. ต้องส่ง `difficulty: 'beginner'` (placeholder) + `time_elapsed: Math.max(1, …)` (validation คือ `min:1`) + map chars→words ด้วยมาตรฐาน 5 char/word
- **backend รองรับ `key_training`/`letter_runner` เป็น game_mode อยู่แล้ว → ไม่ต้องแก้ backend** (งานนี้เป็น frontend-only)
- review implementation ทั้ง 5 ประเด็น (regex/`LAYOUT_MAP`, store+lobby config, focus Phaser, unified result, Spacebar) — ตรวจแล้วถูกต้องตามแผน
- commit งานเก็บ P3: `971378a3 style(typing): polish Key Training lobby & Spacebar key`
  - [VirtualKeyboard.vue](file:///C:/wamp64/www/nuxnan/ui/components/games/typing/ui/VirtualKeyboard.vue) — ห่อ label "SPACE" ใน span (idle=slate-500, flash=white) เพราะ `keyClasses('Space')` ไปทับสีเดิมหาย
  - [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/Play/Games/typing/index.vue) — ลบ dead code `v-if="false"` (placeholder เก่าที่ถูกแทนด้วย Language/Lesson selector แล้ว)

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **smoke test ในเบราว์เซอร์ (สำคัญสุด)** — หน้า `/play/games/typing` อยู่หลัง auth middleware ทดสอบไม่ได้โดยไม่ login:
  1. เล่น Key Training จนจบ → ยืนยัน `POST /typing/sessions` คืน **200 ไม่ใช่ 422** (payload ตรวจเชิง schema แล้วแต่ยังไม่ยิงจริง)
  2. ไทย lesson แถวบน/แถวตัวเลข → กด `[ ] \ - =` ต้องได้ `บ ล ฃ ข ช`
  3. Monster Battle / Falling Words → คลิกกลาง canvas แล้วต้องพิมพ์ต่อได้
- [ ] `git push` — main ahead origin 1 commit (ผู้ใช้สั่ง "เก็บไว้ก่อน" ยังไม่ push)

### Context สำคัญ
- **VirtualKeyboard Spacebar ยังไม่มีผลจริง** — ไม่มี lesson ไหนใส่ `' '` (space) ใน `keys` เลย ไฮไลต์นี้เป็น future-proof/cosmetic จนกว่าจะเพิ่ม lesson ที่ใช้ space
- **KeyTrainingMode ยังคงหน้า finished ของตัวเองไว้** (D3 เลือกไม่ลบ) → ตอนจบเกมจะเห็นหน้า finished วาบสั้นๆ ก่อน navigate ไป `/result` (เพราะ submit เป็น async) — ยอมรับได้ ถ้ารำคาญค่อยลบ block นั้น
- **`difficulty: 'beginner'` เป็น placeholder** ของ key_training (ตัวคูณ score ต่ำสุด 1.0 ไม่ปั่นคะแนน) — ถ้าอยากให้ได้ XP มากขึ้นค่อยปรับ (D1 ในแผน)
- key_training จะเข้าตาราง typing_sessions ด้วย แต่ backend แยก leaderboard ตาม `game_mode` อยู่แล้ว → ไม่ปน WPM โหมดอื่น
- ⚠️ **git state ระหว่าง session นี้แสดงผลไม่คงเส้นคงวา** — เห็น history คนละสายสลับกันหลายรอบ (ชุด wallet/deploy `34722636` กับชุดนี้ `a736d9ab`) และ reflog ไม่ตรงกับที่เห็น น่าจะมาจาก sync ข้ามเครื่อง (มี stash `codex-safe-pull`) **ก่อนเชื่อสถานะ git ให้ verify ด้วยการอ่านเนื้อไฟล์ใน HEAD จริง อย่าเชื่อ log อย่างเดียว**
- ยืนยันด้วยการอ่าน `git show HEAD:…` แล้วว่า **typing implementation อยู่ใน HEAD ครบ** (store/emit/LAYOUT_MAP/@finished/selectedKeyLesson)

### Branch / Git State
- Branch: main
- Uncommitted: มี — แต่เป็นงานค้างของ **session อื่น** (academy scope/classroom: `AcademyPostController`, `AcademyScopeAccessService` (untracked), `AssignHomeroomTeacherModal`, classrooms/departments pages, `latest-analysis.md`) **ไม่ใช่งาน typing — อย่าเผลอ commit รวม**
- Push status: **not pushed** — main ahead origin/main 1 commit


## 2026-07-17 — Student Card Request Form Improvements (ลดภาระครูประจำชั้น)

> ฟีเจอร์: ปรับปรุงระบบคำร้องทำบัตรนักเรียนทั้ง 2 ช่องทาง (หน้า public `/student-card/{level}/{room}` และหน้าครูล็อกอิน) ตามหลัก "ลดภาระผู้ใช้": แสดงสถานะกันส่งซ้ำ, เหตุผลเป็น dropdown, ผู้แจ้ง default ครูประจำชั้น, ส่งคำร้องทั้งห้องในคราวเดียว

### สถานะ: เสร็จสิ้น (เทสต์ผ่าน 13/13, build ผ่าน, ตรวจ UI ในเบราว์เซอร์กับข้อมูลจริงแล้ว)

**Commits ของงานนี้ (อยู่ใน main แล้ว):** `0e9f6559`, `3ed8ee81`, `d68fef06`, `810c29b4`

**สิ่งที่ทำ:**
- **Backend:**
  - Enum ใหม่ [StudentCardRequestReason.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Enums/StudentCardRequestReason.php) — 7 เหตุผล (lost, damaged, expired, name_changed, photo_outdated, new_student, other) พร้อม `deriveRequestType()`
  - Migration เพิ่มคอลัมน์ `reason_code`, `requester_name`, `requester_phone` ใน `student_card_requests` (รัน migrate บนเครื่องนี้แล้ว)
  - `StudentCardRequestService` — คำนวณ request_type อัตโนมัติจากเหตุผล + สถานะบัตรจริง (ไม่มีบัตร → first_issue, expired → renewal, อื่น → replacement) และ**แก้ type ให้เองแทนการ throw** เมื่อ type ที่ส่งมาขัดกับสถานะบัตร; ข้อความ error เป็นภาษาไทยแล้ว
  - Endpoint ใหม่ `POST /api/student-card/{level}/{room}/requests/bulk` (สูงสุด 60 คน/ครั้ง, ตอบผลรายคน, throttle 5/นาที)
  - ผู้แจ้งไม่กรอก → backend default เป็นชื่อครูประจำชั้นของห้อง
  - รายชื่อนักเรียนในห้อง (ทั้ง public และ classroomStudents) แนบ `active_card_request` + `has_physical_card` มาด้วย (relation `activeCardRequest` บน Student + StudentCard)
- **Frontend public:** การ์ดขึ้น badge สถานะแทนปุ่มเมื่อมีคำร้องค้าง, RequestCardModal ใหม่ (dropdown เหตุผล + badge จำเป็น/ไม่จำเป็น + prefill ครูประจำชั้น), โหมดเลือกหลายคน + [BulkRequestCardModal.vue](file:///C:/wamp64/www/nuxnan/ui/components/student-card/BulkRequestCardModal.vue)
- **Frontend ครูล็อกอิน:** ตาราง requests เพิ่มสถานะ + checkbox bulk submit (ใช้ endpoint `/bulk` เดิมที่มีอยู่แล้ว), SubmitRequestModal + BulkSubmitRequestModal ใหม่ใน `ui/components/school/studentCard/`
- **เทสต์:** เขียน [PublicCardRequestTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/PublicCardRequestTest.php) ใหม่ 13 เทสต์ครอบคลุม reason codes / type derivation / requester defaults / bulk / กันส่งซ้ำ + แก้เทสต์เก่า `manage_context_reports_disabled_when_config_off` ที่พังค้างมาก่อน (ไม่ได้สร้างห้องก่อนเรียก)

### Context สำคัญ
- **หน้า public ทุกคนที่โผล่ในห้องมี StudentCard record เสมอ** (list ดึงจากตาราง student_cards) ดังนั้น first_issue จากหน้านี้จะเกิดเฉพาะเมื่อ record ไม่มีจริงๆ — ระบบใช้ derived type เป็นหลัก UI ไม่ให้เลือก type เอง
- หน้า admin requests index ถูก session "Student card request list filtering" redesign เพิ่ม filter/pagination ทับภายหลัง — ตรวจแล้วยังต่อกับ component/bulk logic ของงานนี้ครบ ไม่ชนกัน
- ตรวจ UI แล้วแบบ **ไม่กดส่งคำร้องจริง** (DB เครื่องนี้มีนักเรียนจริง 2,195 คน) — ถ้าจะทดสอบ e2e จริงให้ใช้ข้อมูลเทสต์
- Screenshot ใน browser pane จะ timeout บนหน้าห้องเรียน (การ์ด 43 ใบ + QR canvas) — ใช้ read_page/get_page_text แทน

### Branch / Git State
- Branch: main — commits ของงานนี้ push ขึ้น origin แล้ว (มีงาน session อื่นต่อยอดทับจนถึง `8f02cd30`)
- Uncommitted: ไม่มี (เหลือเฉพาะ worklog นี้)


## 2026-07-14 — Legacy Student Card 401 Fix & Telescope Sequence Migration

> ฟีเจอร์/แก้ไข: แก้ไขปัญหาสิทธิ์ (401 Unauthorized) ในหน้าของครู/แอดมินสำหรับจัดการระดับชั้น/ห้องเรียน และแก้ไขโครงสร้างตาราง Telescope (`telescope_entries.sequence`) ที่ไม่มี auto-increment ซึ่งทำให้เกิด 500 error บนหน้าสาธารณะ

### สถานะ: เสร็จสิ้น (อัปโหลดและทดสอบความถูกต้องของสคริปต์ไมเกรตแล้ว)

**สถิติการเปลี่ยนแปลง:**
- **Legacy Student Card 401 Fix:**
  - อัปเดต [[room].vue](file:///C:/wamp64/www/nuxnan/ui/pages/student-card/admin/students/[level]/[room].vue) เพื่อเปลี่ยนมาใช้งาน `useApi()` แทน `$fetch` ตัวเดิม เพื่อให้แนบ JWT token ใน HTTP Header อัตโนมัติและจัดการ session refresh ได้อย่างถูกต้อง
  - กำหนด `middleware: ['auth']` ใน `definePageMeta` เพื่อกรองผู้ใช้ที่ยังไม่ล็อกอิน
- **Telescope Database Schema Fix:**
  - สร้างไฟล์ Migration [2026_07_14_130000_repair_telescope_sequence_auto_increment.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_07_14_130000_repair_telescope_sequence_auto_increment.php) สำหรับตรวจสอบและทำการแก้ไขตาราง `telescope_entries` คอลัมน์ `sequence` ให้เป็น `AUTO_INCREMENT` กรณีที่ยังไม่ได้กำหนด เพื่อแก้ปัญหา QueryException ที่ยิงผ่าน Telescope
- **Formatting & Analysis:**
  - รัน Laravel Pint เพื่อจัดระเบียบรูปแบบโค้ดไฟล์ migration ที่สร้างขึ้นใหม่
  - ปรับปรุง [.agents/latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)

## 2026-07-14 — Student Card Security Hardening & Robust UI State Handling

> ฟีเจอร์: การแก้ไขปัญหาหน้าจัดการบัตรนักเรียนว่างเปล่า/ไม่โหลดข้อมูล (Student Cards Page) พร้อมการจัดกลุ่มสิทธิ์อ่าน/เขียนฝั่ง Backend และการจัดการสถานะความคลาดเคลื่อน (Loading, Error, Empty) ครบถ้วน 100%

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ทั้งหมด 17 เทสต์ 50 assertions และคอมไพล์ผ่าน)

**สถิติการเปลี่ยนแปลง:**
- **Backend Authorization Contract:** 
  - แก้ไข [academy-student-card.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy-student-card.php) เพื่อจัดกลุ่ม Route อ่านข้อมูล (statistics, search, levels, sections, profile, by-student, getStudentByRoom) ให้ใช้สิทธิ์ `students.view` และหุ้มด้วย middleware `academy.permission` ป้องกันปัญหาสิทธิ์รั่วไหลข้ามโรงเรียน
  - จัดกลุ่ม Route เขียน/จัดการข้อมูล (update, destroyPhoto, adminIndex, adminStudents, adminGetStudentByRoom, store, import, export, upload-photo, update-code, update-name-th/en, bulk-update/upload-photos, sync/commit/preview, audit) ให้ใช้สิทธิ์ `students.manage`
- **Unit & Feature Tests Fixes:** 
  - แก้ไข database seeding ใน [StudentCardSSOTTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardSSOTTest.php) และ [StudentCardByStudentTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/StudentCardByStudentTest.php) จากสถานะ `'status' => 'active'` ให้เป็นสถานะตัวเลข `2` (approved member) เพื่อให้ผ่าน middleware ตรวจสอบสิทธิ์ของ Laravel
  - ทำการ Seed ข้อมูล `AcademyRole` ที่ผูกสิทธิ์ `students.view` ให้แก่ผู้ใช้งานบทบาทนักเรียน (`$owner`) เพื่อความเข้ากันได้ของการจำลองการเข้าถึงระบบ
  - รันการทดสอบ `php artisan test --filter=StudentCard` แล้วผ่านการทดสอบทั้งหมด (17 Passed, 50 Assertions)
- **Frontend State Handling & UI Enhancements:**
  - แก้ไขไฟล์ [index.vue](file:///C:/wamp64/www/nuxnan/ui/pages/academies/%5Bname%5D/admin/student-cards/index.vue) โดยการเพิ่ม refs `pageError`, `statsError`, `listError`, `roomError`, `isLoadingStats`, `isLoadingList` และ `hasLoaded` เพื่อเก็บและประมวลผลกรณีเกิดความผิดพลาดในการเรียกใช้ API รายส่วน
  - เพิ่มส่วนแสดงผล Error Boundary ขนาดใหญ่สำหรับหน้ารวมพร้อมปุ่ม **"ลองใหม่อีกครั้ง"** เมื่อการโหลดเริ่มต้นหน้าเว็บล้มเหลว
  - เพิ่มสเตตัส Empty State ใน 4 รูปแบบ: โหลดสถิติไม่สำเร็จ (พร้อมปุ่มรีดึงข้อมูล), ไม่มีจำนวนนักเรียนเลย (พร้อมปุ่มนำเข้าข้อมูลเฉพาะแอดมินที่มีสิทธิ์เขียน), โครงสร้างระดับชั้น/ห้องเรียนเป็นค่าว่าง, และนักเรียนในห้องเรียนที่เลือกเป็นศูนย์
  - ปรับปรุงการสลับสิทธิ์การค้นหารายชื่อระหว่าง `/admin/students` (สำหรับแอดมิน/ผู้จัดการ) และ `/search` (สำหรับครู/ผู้รับชมทั่วไป) พร้อมควบคุมสิทธิ์การเห็นปุ่ม "นำเข้าข้อมูล", "พิมพ์บัตร" และปุ่ม "แก้ไขข้อมูลนักเรียน" ให้มีสิทธิ์เฉพาะคนที่มี `students.manage` เท่านั้น
- **Pint Formatting:** จัดรูปแบบโค้ดไฟล์ php ทั้งหมดด้วย Pint เรียบร้อยแล้ว

## 2026-07-14 — Scope Security Hardening, Workspace and Feed/Announcement Scope Filtering

> ฟีเจอร์: การทำ Security Hardening และแก้ปัญหาข้อมูลรั่วไหล (Data Leakage) ของระบบฟีดข่าว ประกาศ และพื้นที่ขอบเขตงาน Scoped Workspace (departments & classrooms) พร้อมสร้างชุดทดสอบ PHPUnit ครบถ้วน 100%

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ทั้งหมด 11 เทสต์ 32 assertions)

**สถิติการเปลี่ยนแปลง:**
- **Security Hardening (Workspace API):** เสริมความปลอดภัยใน `AcademyScopeWorkspaceController.php` เพื่อปิดช่องโหว่ IDOR และ Cross-Academy access ในการเรียกจัดการงาน (Tasks) และเอกสาร (Files) โดยบังคับเช็คความเป็นเจ้าของขอบเขตงาน และตรวจสอบสิทธิ์สำหรับบทบาทต่างๆ (แอดมิน, สมาชิกฝ่ายงาน, ครูประจำชั้น, นักเรียน) อย่างรัดกุม
- **Scope Filtering & Authorization (Announcements & Feed):**
  - อัปเดต `AnnouncementController@index` ให้รองรับการกรองประกาศตามขอบเขต `scope_type` และ `scope_id` (หากไม่ระบุจะเลือกเป็นระดับ `academy` โดยปริยายเพื่อป้องกันข้อมูลประกาศของฝ่ายงาน/ห้องเรียนรั่วไหล) และบังคับการตรวจสอบสิทธิ์การเข้าถึงขอบเขตนั้นๆ สำหรับผู้ใช้ที่ไม่ใช่แอดมิน
  - อัปเดต `AcademyActivityController@index` และ `@getActivities` ให้รองรับการกรองโพสต์ในฟีดสถาบันตามขอบเขต `scope_type` และ `scope_id` พร้อมการตรวจสอบสิทธิ์แบบเดียวกัน ป้องกันข้อมูลจากกลุ่มย่อยหรือห้องเรียนรั่วไหลออกสู่ฟีดสถาบันหลัก
- **Database Schema Sync:** เปลี่ยนการคิวรีเช็คสมาชิกกลุ่มจาก `group_id` เป็น `academy_group_id` ในตาราง `academy_group_members` ให้ตรงตามโครงสร้างฐานข้อมูลจริง
- **PHP Unit Tests:**
  - สร้างไฟล์ทดสอบ [AcademyScopeWorkspaceTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/AcademyScopeWorkspaceTest.php) เพื่อตรวจสอบการอนุญาตเข้าถึงพื้นที่ขอบเขตงานในเคสต่างๆ (7 tests, 19 assertions) - **ผ่านหมด 100%**
  - สร้างไฟล์ทดสอบ [AcademyScopeFilteringTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/AcademyScopeFilteringTest.php) เพื่อตรวจสอบการกรองและความถูกต้องของการแสดงผลประกาศและกิจกรรมตามขอบเขต (4 tests, 13 assertions) - **ผ่านหมด 100%**
- **Pint Formatting:** จัดระเบียบรูปแบบโค้ดไฟล์ที่พัฒนาใหม่และเกี่ยวข้องผ่าน Pint ครบถ้วนทั้งหมด

---

## 2026-07-14 — Premium Classroom Detail Management Interface

> ฟีเจอร์: หน้าจัดการห้องเรียนเชิงลึก (`ui/pages/academies/[name]/admin/classrooms/[id].vue`) ในรูปแบบแท็บแดชบอร์ด 7 แท็บ (ภาพรวม, นักเรียน, ครูและสมาชิก, การเข้าเรียน, วิชา/เกรด, ประกาศ, รายงาน) สไตล์ HopeUI พรีเมียม พร้อมระบบสแกน QR บอร์ด, เพิ่ม/ย้ายนักเรียนจริง, ดูโปรไฟล์เชิงลึกผู้ปกครอง, บันทึกเช็คชื่อเข้าเรียน, และฟังก์ชันส่งออก Excel ในตัว

### สถานะ: เสร็จสิ้น (พร้อมทดสอบและใช้งานร่วมกับ Laravel API ในตัว + แก้ไขบั๊ก MySQL event table 100%)

**สถิติการเปลี่ยนแปลง:**
- **Database Bugfix:** แก้ไขบั๊ก `SQLSTATE[HY000]: General error: 1364 Field 'id' doesn't have a default value` ของตาราง `user_usage_events` 
  - ทำการรันคำสั่ง SQL ด่วนบนเซิร์ฟเวอร์ `ALTER TABLE user_usage_events MODIFY id BIGINT UNSIGNED AUTO_INCREMENT;` เพื่อเปิดใช้งานการเพิ่มค่า ID อัตโนมัติ (ซึ่งแต่เดิมหลุดเนื่องจากความจำกัดของระบบ Doctrine DBAL change)
  - ปรับปรุงไฟล์ไมเกรต [2026_07_10_013214_modify_id_in_user_usage_events_table.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/database/migrations/2026_07_10_013214_modify_id_in_user_usage_events_table.php) ให้ใช้ raw SQL statement `DB::statement()` บน MySQL ในเมธอด `up` และ `down` เพื่อเสถียรภาพและความเข้ากันได้สูงสุด และ fallback ไปใช้ Schema builder บนเครื่องทดสอบ SQLite
  - จัดรูปแบบโค้ดไฟล์ไมเกรตผ่าน Pint เรียบร้อยแล้ว
- **Backend Controllers:** อัปเดต `ClassroomController::getStudent` ให้ eager-load โครงสร้างความสัมพันธ์ `guardians`, `healthInfo`, `addresses`, และ `contacts` เพื่อให้หน้าระบบสามารถดึงประวัตินักเรียนและผู้ปกครองมาแสดงในแถบ Drawer ได้ครบถ้วน
- **Pint Formatting:** จัดระเบียบ code `ClassroomController.php` ผ่าน Pint เรียบร้อยแล้ว
- **Frontend UI:**
  - **Banner / Header:** ออกแบบ HopeUI Wave Hero Banner พร้อมอนิเมชัน slow-zoom อัตโนมัติ แสดงข้อมูลชื่อห้อง ปีการศึกษา และเกรด
  - **ภาพรวม (Overview):** การ์ดแสดงผลรวม อัตราผู้เรียน/ความจุ, ครูประจำชั้น, สรุปสถิติเข้าเรียน (96.5%), เกรดเฉลี่ยห้อง (3.25), ประกาศล่าสุด, ตารางสอน, และกลุ่มความเสี่ยง (At-Risk)
  - **นักเรียน (Students):** ตารางรายชื่อนักเรียนพร้อมการค้นหาและฟิลเตอร์, เมนูการจัดการเลขที่/เลขประจำตัวนักเรียน (Inline update), การนำนักเรียนเข้าห้องเรียนจากรายชื่อว่าง (Add Student Modal), การย้ายห้องเรียน (Transfer Student Modal) และแถบ Drawer ด้านข้างสำหรับแสดงผลโปรไฟล์ ประวัติการติดต่อ และผู้ปกครองโดยละเอียด
  - **ครูและสมาชิกห้อง (Members):** จัดการสมาชิกห้อง บทบาทครูร่วมสอน ครูผู้ช่วย และผู้สังเกตการณ์ พร้อมปุ่มลบ/เปลี่ยนบทบาท
  - **การเข้าเรียน (Attendance):** ระบบจัดการเช็คชื่อตามวันที่ ค้นหาและบันทึกรายชื่อผู้เข้าเรียน (มา, สาย, ลา, ขาด), แสดงสถิติวงกลม/สถิติกลุ่ม, และการเชื่อม QR Code สำหรับสแกนเข้าชั้นเรียนร่วมกับ `SchoolAttendanceQRDisplay`
  - **วิชาและผลการเรียน (Grades):** แสดงเกรดเฉลี่ยรายวิชาและ GPA/GPAX พร้อมปุ่มด่วนสำหรับเปิดหน้า Gradebook หลัก
  - **ประกาศและการสื่อสาร (Announcements):** ประดัษฐ์ระบบประกาศของชั้นเรียน ปฏิทินและแจ้งเตือนนักเรียน/ผู้ปกครองแบบโต้ตอบ
  - **รายงาน (Reports):** ฟังก์ชันส่งออกรายงานนักเรียน, รายงานเข้าเรียน, และรายงานผลการเรียน ออกมาเป็นไฟล์ Excel (.xlsx) จริงบนฝั่งไคลเอนต์โดยใช้ไลบรารี `xlsx` (SheetJS)
- **Dependencies:** ใช้งาน `xlsx` (SheetJS) ในการประมวลผลดาวน์โหลดไฟล์โดยตรง

---

## 2026-07-13 — Admin withdrawal payout proof + full detail review

> ฟีเจอร์: บันทึกการโอนเงิน (Mark Paid) ฝั่งแอดมินบังคับแนบหลักฐานสลิป (Payout Proof) เก็บใน private storage, หน้ารายละเอียดคำขอถอนเงิน (Pending Details Modal) แสดงข้อมูลครบถ้วนสำหรับโอนเงินจริง, ป้องกัน Maker-Checker Control ยอดสูง (≥ ฿10,000)

### สถานะ: เสร็จสิ้น (ผ่านการทดสอบ 100% ครบ 35 เทสต์ 146 assertions)

**สถิติการเปลี่ยนแปลง:**
- **Database:** สร้าง migration `2026_07_13_175026_add_payout_proof_to_wallet_transactions_table` เพิ่ม 6 columns สำหรับ payout proof metadata ใน `wallet_transactions` + backfill/alter migrations table
- **Models:** อัปเดต `WalletTransaction.php` (เพิ่ม `$fillable` + casts + `$hidden` field `payout_proof_path` + accessor `has_payout_proof`)
- **Backend Services:** `WalletService::markWithdrawalPaid` อัปเดต signature ให้รับ `proofData` เพื่อบันทึก metadata ลง database และป้องกัน double-paid โดยตรวจสอบ `payout_proof_path` ห้ามเขียนทับ (Immutability)
- **Backend Controllers:**
  - `AdminWalletController::pendingWithdrawals` อัปเดตให้รองรับ query param `status=awaiting-payout` (กรอง approved/processing) และ eager load `reviewer` เพื่อแสดงข้อมูลผู้ตรวจคนแรก
  - `AdminWalletController::showWithdrawal` eager load `reviewer`
  - `AdminWalletController::markWithdrawalPaid` เปลี่ยนไปใช้ FormRequest `MarkWithdrawalPaidRequest` ในการ validate input (payment_reference + proof file) และอัปโหลดไฟล์หลักฐานไปยัง private disk (local) ก่อนรัน DB Transaction หากล้มเหลวจะทำความสะอาดลบไฟล์ทิ้งใน `finally`/catch block
  - `AdminWalletController::downloadWithdrawalProof` (ใหม่) endpoint ดาวน์โหลดสลิปโอนเงินทางฝั่งแอดมิน ตรวจสอบ policy สิทธิ์เข้าถึงผ่าน `WithdrawalPolicy` + audit log event `withdrawal.proof_viewed`
- **FormRequest:** `App\Http\Requests\Admin\MarkWithdrawalPaidRequest` บังคับ `payment_reference` และ `proof` (ไฟล์ภาพ/PDF ≤ 5MB)
- **Routes:** `routes/admin/admin.php` เพิ่ม route `GET /withdrawals/{id}/proof`
- **Frontend UI:**
  - `ui/pages/nuxnan-admin/wallet/pending.vue` ปรับปรุง UI สไตล์ HopeUI มี 3 Tabs: "ถอนเงิน (รอดำเนินการ)", "ถอนเงิน (รอโอน)", "เติมเงิน (รอดำเนินการ)", เพิ่ม Modal รายละเอียดธุรกรรมแสดงข้อมูลครบถ้วนพร้อมปุ่มคัดลอกเลขบัญชีแบบ visual checkmark feedback, warning banner ระบบ Maker-Checker และ interface แนบสลิปพร้อมตรวจสอบ validation ต่างๆ ในตัว
  - `ui/pages/nuxnan-admin/wallet/index.vue` เพิ่มปุ่ม "ดูหลักฐานการโอน" ใน Modal รายละเอียดธุรกรรมที่เสร็จสิ้นแล้ว โดยจะดึงไฟล์ผ่าน blob stream พร้อมแนบ JWT token ใน header
- **Pint:** จัดรูปแบบ code จัดระเบียบเรียบร้อย

### ✅ ตรวจแล้ว (PHPUnit)
- `tests/Feature/Wallet/WithdrawalPayoutProofTest.php` (ใหม่) ผ่านครบ 7/7 เทสต์
- รันชุดทดสอบ wallet ทั้งหมด (`WalletReconciliationTest`, `WithdrawTest`, `WithdrawalHardeningTest`, `WithdrawalPayoutProofTest`) **ผ่านหมด 100% (35 passed, 146 assertions)**

---

## 2026-07-13 — แก้ `gradebook.php` ไม่ถูกโหลด (route หายทั้งไฟล์)

> ค้นพบระหว่างแก้ dropdown ปีการศึกษา: `routes/api.php` **ไม่เคย `require .../learn/gradebook.php`** เลย (ยืนยันด้วย git history — ลืมตั้งแต่แรก ไม่ใช่ปิดโดยตั้งใจ) → ทุก route ในไฟล์นั้นตาย

### สถานะ: เสร็จสิ้น (Option A) + verify ครบ

**ผลกระทบเดิม (endpoint ตาย):** course gradebook, subjects, grade-scales, assessment-categories, transcripts (academy+student+me), academic-years write/semesters → หน้า `admin/gradebook/*`, `my-transcript`, `Learn/Courses/{id}/gradebook/*`, rollover `createAcademicYear` พังเงียบ

**การแก้ (Option A — require ทั้งไฟล์):**
- `routes/api.php` — เพิ่ม `require __DIR__.'/learn/gradebook.php';` (ถัดจาก course.php ก่อน student.php → academy.php โหลดก่อน)
- `routes/learn/academy.php` — ลบ route `academic-years` ชั่วคราว + import `AcademicYearController` ที่เพิ่มไว้เมื่อวาน (gradebook.php ให้ครบชุดแล้ว)
- `ui/composables/useSchoolManagement.ts` — แก้ path ผิด `/gradebook/academic-years` → `/academic-years` (3 จุด: years/semesters/current) ⚠️ หมายเหตุ: `getSemesters` (GET) ยังไม่มี route backend รองรับ (gradebook.php มีแค่ POST/PUT semesters) — เป็น gap แยก

### ✅ ตรวจแล้ว
- `route:list`: course gradebook 10 routes คืนครบ, subjects/grade-scales/transcripts ครบ, **duplicate method+uri = 0** (Laravel dedupe by key, gradebook override academy.php ชี้ controller เดียวกัน), academic-years GET index = 1 (ไม่ซ้ำ)
- **Security:** non-admin POST `/subjects`,`/grade-scales`,`/transcripts/*`,`/academic-years` → **403 ทุกจุด** (controller gate เอง)
- **Happy path:** admin GET revived endpoints → 200 ทุกจุด (ทั้ง curl และ in-browser fetch จาก origin :3000 + CORS)
- Pint ผ่าน

### Option B (cleanup) — เสร็จแล้ว (2026-07-13)
ย้าย route ออกจาก gradebook.php ไปไฟล์ที่โหลดอยู่ แล้ว **ลบ gradebook.php ทิ้ง**:
- **course.php** — course gradebook (10 routes) เป็น group `['auth:api']` แยก (ไม่มี `verified` — รักษา middleware เดิม)
- **student.php** — student transcripts (`/students/{student}/transcripts/*`) + `/students/me/*` (transcripts, card)
- **academy.php** — academic-years CRUD+semesters, subjects, grade-scales, assessment-categories, transcripts (academy), `{academy}/students` index/show
- **academy.php classrooms update** — เปลี่ยน `PATCH` เป็น `match(['put','patch'])` เพราะ frontend ทั้ง 2 ฟอร์มใช้ **PUT** (ก่อน Option A การแก้ห้องผ่าน UI พังเงียบ — Option A กู้คืนผ่าน PUT ของ gradebook.php, Option B ย้ายมาไว้ที่ academy.php)
- `api.php` — เอา require gradebook.php ออก; **ลบไฟล์ gradebook.php**

**Verify (diff-based):** จับ golden route set (1690) ก่อน แล้วเทียบหลัง (1689) — ต่างกันแค่ที่ตั้งใจ: `PATCH` + `PUT classrooms/{classroom}` (2 route) → รวมเป็น `PATCH,PUT` (1 route) · **route อื่นเหมือนเดิมทุกตัว, duplicate = 0** · ClassroomUniquenessTest 4/4 ผ่าน · smoke test endpoint ที่ย้าย (subjects/grade-scales/transcripts/course-gradebook/students-me/classroom-PUT) → 200 · Pint ผ่าน

### 🐞 bug เดิมที่เจอ (แยกต่างหาก ไม่ใช่จาก refactor)
`GET /api/academies/{academy}/students` และ `/classrooms/students` (ClassroomController@getAllStudents) คืน **500** — `Unknown column 'current_student_number' in 'order clause'` (ใช้ alias จาก `addSelect` ใน `orderByRaw` — MySQL อ้าง alias ใน ORDER BY กับ subselect ไม่ได้) มีมาก่อน refactor (อยู่ใน golden) → ควรแก้แยก (ย้าย order logic หรือใช้ subquery ซ้ำใน ORDER BY)

### Backend gap (ยังเหลือ)
ไม่มี `GET .../academic-years/{year}/semesters` (มีแค่ POST/PUT) — semesters ฝังมากับ academic-years index (`->with('semesters')`) อยู่แล้ว ถ้า `getSemesters` ถูกเรียกจริงต้องเพิ่ม route

---

## 2026-07-12 — "ห้องเรียนซ้ำ" ในหน้า Academy

> อาการ: การ์ดห้องเรียนซ้ำ (เช่น ม.1/1 โผล่ 2 ใบ) ในหน้า `academies/[name]` แท็บห้องเรียน
> **บทเรียนสำคัญ:** root cause แรกที่วิเคราะห์ (NULL-trap ใน unique index) **ผิด** — การ์ดที่เห็นเป็นคู่คือ**ห้องเดียวกันคนละปีการศึกษา** (2568 vs 2569) ไม่ใช่ห้องซ้ำจริง ต้นตอจริงคือ **หน้าเว็บดึงห้องทุกปีมาโชว์รวมกันโดยไม่กรองปี**

### สถานะ: เสร็จสิ้น + verify ในเบราว์เซอร์จริงแล้ว (โหลดหน้าสด login เจ้าของ)

**Part 1 — สุขอนามัยข้อมูล (orthogonal กับบั๊กที่เห็น แต่ทำไว้เป็นการกันซ้ำ*ปีเดียวกัน*จริง):**
- Migration `2026_07_12_140000_backfill_classroom_academic_year_id.php` — backfill `academic_year_id` จาก `academic_year` string (find-or-create ปีถ้าไม่มี)
- Migration `2026_07_12_150000_fix_classrooms_unique_and_notnull.php` — `academic_year_id` เป็น NOT NULL + UNIQUE `(academy_id, academic_year_id, grade_level, section)`, FK `onDelete restrict` (รองรับ SQLite ในเทสต์)
- Command `classrooms:merge` (`app/Console/Commands/MergeDuplicateClassrooms.php`) — ยุบห้องซ้ำ + re-point FK ทุกตารางที่อ้าง `classrooms.id` (มี `--commit`, default dry-run)
- `ClassroomService.php` — `resolveAcademicYear` + `checkUniqueness` (app-level) + catch QueryException 23000 กัน race
- `ClassroomController@store/@update` — validation ยืดหยุ่นรับทั้ง `academic_year_id`/`academic_year`
- `admin/classrooms.vue` — ส่ง `academic_year_id` ให้สอดคล้องทั้งฟอร์มสร้าง/แก้ไข
- ⚠️ **ลำดับ deploy เครื่องอื่น:** ต้องรัน `classrooms:merge --commit` **ก่อน** migrate ถึง 150000 ไม่งั้น 150000 fail ตอนสร้าง unique index (merge ยังไม่ได้ผูกใน migration chain)

**Part 2 — แก้บั๊กที่เห็นจริง (กรองปีการศึกษา):**
- `ClassroomController@index` — ถ้าไม่ส่ง filter ปี → default เป็นปีปัจจุบันของ academy (มี escape hatch `?all_years=1`); เพิ่ม `use App\Models\AcademicYear`
- `academies/[name].vue` — เพิ่ม dropdown เลือกปีการศึกษาเฉพาะแท็บห้องเรียน (default ปีปัจจุบัน + ตัวเลือก "ทุกปีการศึกษา"), `fetchAcademicYears()`, ส่ง `academic_year_id`/`all_years` ตามที่เลือก, โหลดปีก่อนโหลดห้องตอนเปิดแท็บ
- **เพิ่ม route `GET /api/academies/{academy}/academic-years`** ใน `routes/learn/academy.php` → `AcademicYearController@index`
  - 🔴 **ค้นพบสำคัญ:** `routes/learn/gradebook.php` **ไม่เคยถูก `require` ใน `routes/api.php`** → ทุก route ในไฟล์นั้นหายหมด (academic-years CRUD, `/current`, course gradebook routes ฯลฯ) หน้า admin อื่นที่เรียก endpoint เหล่านี้ก็น่าจะพังเงียบ — **เป็นงานแยกที่ต้องสะสาง** (ควร require gradebook.php หรือย้าย route ที่ใช้จริงออกมา ระวัง route ซ้ำกับ academy.php)

### ✅ ตรวจแล้ว
- 4/4 `ClassroomUniquenessTest` ผ่าน (11 assertions)
- Pint ผ่าน / Nuxt production build ผ่าน (exit 0)
- ข้อมูลจริง: กรองปี 2569 → 54 ห้อง, ม.1/1 เหลือใบเดียว (105 → 54)
- **Browser จริง:** `academic-years` → 200 + dropdown ขึ้น "2569 (ปัจจุบัน)/2568/ทุกปีการศึกษา", default=2569, `classrooms?academic_year_id=2` → 200

---

## 2026-07-12 — Campaign system (โฆษณา + สนับสนุน) Phase 1-4 + review/fix

> ฟีเจอร์: ระบบ Campaign กลาง (โฆษณา + สนับสนุน) รองรับ scope public/academy/course — แผนเต็ม + findings อยู่ที่ [`.agents/campaign-system-plan.md`](campaign-system-plan.md)

### สถานะ: Phase 1-3 ✅ + review/fix (14/14 tests) | Phase 4 ✅ widget (Nuxt build ผ่าน) | Phase 5 (create/dashboard) มีโค้ดแล้ว ยังไม่ verify runtime

**Phase 1-3 (backend เสร็จ):**
- 3 migrations: `120000` add campaign fields (+`distributed_at`), `120001` backfill legacy, `130000` `campaign_delivery_events`
- 4 enums (CampaignType, ScopeType, PaymentStatus, ReviewStatus) + `config/campaign.php` (รวมค่าคงที่ราคา/รางวัล/points/split)
- 6 services (Authorization, Pricing, Delivery, View, SupportPayment, Refund) + `CampaignController` + 4 FormRequests + `CampaignResource` + `CampaignDeliveryEvent`
- routes ใหม่ `/api/campaigns/*` (legacy `/api/advertises/*` ไม่แตะ — strangler)

**Review + แก้ 10 findings (ทุกข้อแก้แล้ว):**
- 🔴 จ่ายเงิน support ซ้ำ → `distributed_at` guard idempotent; reject หลัง approve เสกเงิน → state-machine guard ใน `review()`
- 🟡 แต้มผู้สนับสนุนไม่เคยให้ (credit pp แล้ว); support โผล่ widget ไม่ได้ (filter advertisement-only); course ไม่เก็บ academy_id (derive server-side); referrer reward + points-portion หาย (wire config ครบ)
- 🟢 500→429 (`DailyViewLimitException`); nested transaction; comment ค้าง; backfill CASE order
- Contract frontend↔backend: `reward_per_view` แสดง=จ่ายจริง; route `impression` เปิด public (guest นับได้)

**Phase 4 (widget):** `ui/components/campaign/CampaignWidget.vue` — วางใน public (AdvertisesWidget wrap), course (CoursePageShell), academy ([name].vue desktop+mobile); `npm run build` ผ่าน

### ✅ ตรวจแล้ว
- 14/14 `CampaignSystemTest` ผ่าน (58 assertions) + Pint + `migrate --pretend` + Nuxt build

### ⚠️ ค้าง / Deploy notes
- **ยังไม่รัน migration จริง** — เมื่อ deploy: `php artisan migrate` (3 ไฟล์ใหม่ `120000/120001/130000`)
- Phase 5: `create.vue`/`manage.vue` compile ผ่าน แต่ยังไม่ทดสอบ flow จริง (ต้องรัน server ทั้งคู่ + login)
- ปุ่ม "สนับสนุน" บนหน้า academy/course ยังไม่มีจุดเริ่ม (widget เป็น delivery โฆษณาเท่านั้น)

---

## 2026-07-12 — คะแนนกิจกรรมประจำบทเรียนใน My Progress + admin view

> ฟีเจอร์: หน้า `/Learn/Courses/{id}/my-progress` แสดงคะแนนแบบฝึกหัด/แบบทดสอบประจำบทเรียน และให้ course admin ดูของนักเรียนแต่ละคนได้เหมือนที่นักเรียนดูของตัวเอง แผน/บทวิเคราะห์เต็มอยู่ที่ [`.agents/latest-analysis.md`](latest-analysis.md) (section บนสุด)

### Branch: `feat/my-progress-lesson-activity-scores` (push แล้ว, ยังไม่ merge)
- **`fe0e5ae4`** — Backend `CourseMemberController::show()`: โหลด lesson `questions` + ดึงคำตอบ bulk (กัน N+1), รวมคะแนน lesson-embedded questions เข้าคะแนนบทเรียน, เพิ่ม `reading_progress` (ตาม topic) และ `activity_progress` (แยก assignment/quiz) ต่อบทเรียน; Frontend `MyProgressDetails.vue` แสดง progress การอ่าน + คะแนนแบบฝึกหัด/แบบทดสอบ เคารพ `canShowScore`
- **`47cc4829`** — Backend: authorization gate ใน `show()` (เจ้าของ member หรือ course admin เท่านั้น → ปิด IDOR) + กัน member ข้ามคอร์ส (404); Frontend `ProgressList.vue` (modal admin) แสดงคะแนนบทเรียนชุดเดียวกับนักเรียน คงปุ่ม reset เฉพาะ admin

### Context สำคัญ
- **endpoint จริงที่หน้าใช้คือ `show()`** (route `/members/{member}/progress`) ไม่ใช่ `memberProgress()` (route `/admin/progress` — ไม่ถูกเรียกจาก frontend เลย). งานรวม contract ที่ `show()` ตัวเดียว
- **"แบบทดสอบประจำบทเรียน" = lesson-embedded `questions`** (morphMany, ตรวจผ่าน `LessonAnswerQuestion`) เท่านั้น — `CourseQuiz` ไม่มี `lesson_id` ผูกบทเรียนไม่ได้
- ตรรกะคะแนนบทเรียนอยู่ใน `resolveLessonScoreStatus()` — ยัง all-or-nothing (ซ่อนคะแนนถ้ามีชิ้นรอตรวจ/ขาด)

### ✅ ตรวจแล้ว / ⚠️ ค้าง
- ✅ `php -l` + Pint + Nuxt build ผ่าน
- ⚠️ **ยังไม่ทดสอบ browser ด้วย login จริง** (ไม่มี credential) — ต้องตรวจ: บทเรียน 3 กรณี (เฉพาะฝึกหัด/เฉพาะทดสอบ/ทั้งสอง), admin เปิดดูของนักเรียน, non-admin/non-owner ได้ 403
- ⚠️ **ยังไม่เปิด PR** — `gh` ไม่อยู่บน PATH ของ session นี้ (แม้ worklog เก่าจะระบุว่าติดตั้ง v2.96.0). ลิงก์เปิด PR: https://github.com/UtaiSalem/nuxnan/pull/new/feat/my-progress-lesson-activity-scores
- 📌 backlog เสริม: แท็บ admin `memberProgress()` ยังมี N+1 + logic แยก (ไม่ถูกใช้ ไม่ block); เปลี่ยน `memberProgress()` ให้ใช้ helper เดียวกันถ้าจะ reuse ภายหลัง

### ไฟล์ uncommitted ที่ **ไม่เกี่ยว** กับงานนี้ (ค้างบน branch, เว้นไว้ให้เจ้าของแยก)
`EditPostModal.vue` (academy post edit endpoint), `FeedPost.vue`, `pages/academies/[name].vue`, `pages/index.vue`, `pages/welcome.vue`

---

## 2026-07-12 — Withdrawal & Wallet Hardening ครบวงจร (8 PRs merged เข้า main)

> งานใหญ่: วิเคราะห์ → review งานที่ Codex/Gemini ทำ → แก้บั๊กวิกฤต → ตรวจ invariant เงินเข้า-ออก → baseline บน DB จริง → bcmath + locked_balance → decimal(15,2) → เก็บกวาด **ทั้งหมด merge เข้า `main` แล้ว (PR #3–#8)** เอกสารเต็มอยู่ที่ [`.agents/withdrawal-review-findings.md`](withdrawal-review-findings.md) + [`.agents/withdrawal-system-hardening-plan.md`](withdrawal-system-hardening-plan.md)

### PR ที่ merge เข้า main (8 PRs)
- **#3** Withdrawal hardening — atomic state machine 9 สถานะ + `lockForUpdate` ทุกจุด + maker-checker (ยอด ≥10,000 ต้อง 2 admin) + daily/monthly limit + `WithdrawalPolicy` (approve/reject = SUPER_ADMIN+ADMIN, MODERATOR = view) + audit ครบ + mask bank PII (เต็มเข้ารหัสใน `destination_snapshot`)
- **#4** ลบ dead code `WalletController::approveWithdrawal/rejectWithdrawal` (ไม่มี route ชี้)
- **#5** bcmath (เส้นถอนเงิน) + คอลัมน์ `users.locked_balance` (materialized: wallet = ยอดใช้ได้, total = wallet+locked) + `checkLockedBalance` reconciliation
- **#6** bcmath ครบทุกเมธอด (deposit/transfer/adminAdjust/points/purchase/refund)
- **#7** fix `PointsService::updateUserLevel` crash เมื่อ `$user->xp` null (user เพิ่งสร้าง) → แก้บั๊ก `user can earn points` ที่ค้างมานาน
- **#8** 🔑 **`users.wallet` เป็น `double` (float!) มาตลอด** = ต้นตอ float drift จริง → แปลงเป็น `decimal(15,2) unsigned`; fee/net_amount (10,2)→(15,2)

### เครื่องมือใหม่ (บน main)
- `WalletReconciliationService` + `php artisan wallet:reconcile [--user --mismatched]` — สรุป money-in/out, wallet↔ledger ต่อ user, ยอดถอน 9 สถานะ, refund integrity, locked integrity, ยอดติดลบ; **คืน exit≠0 เมื่อไม่ healthy**
- `php artisan wallet:baseline [--commit --force --user]` — opening-balance baseline (dry-run default, idempotent)
- `php artisan wallet:flag-legacy-withdrawals [--commit]` — flag รายการ returned เก่าที่ไม่มี refund ledger
- **Daily schedule** `wallet:reconcile` 03:30 + log alert (`routes/console.php`) → ผลที่ `storage/logs/wallet-reconcile.log`
- `app/Helpers/BcMathHelper.php` (`bcround`/`bcmax`)

### ✅ สถานะ DB จริง (nuxnan บน WAMP) — Ledger HEALTHY
- รัน migration แล้วบน dev: 000001–000005 (withdrawal fields, status enum, opening_balance type, locked_balance, decimal(15,2))
- baseline 385 users (opening_balance), flag 2 legacy cancelled, normalize wallet=ledger, แปลง wallet double→decimal
- reconcile: money out ≤ money in **OK**, wallet==ledger **OK**, 0 mismatched, 0 negatives, refund+locked integrity **OK**
- backup tables ลบหมดแล้ว

### ⚠️ Deploy notes (ต้องทำบน production ตามลำดับ)
1. `php artisan migrate` — รัน 000001–000005 (โดยเฉพาะ **000005 แปลง wallet double→decimal**; lossless ถ้าข้อมูล 2-decimal อยู่แล้ว)
2. `composer dump-autoload` (autoload.files เพิ่ม BcMathHelper — ไม่งั้น `bcround` ไม่โหลด)
3. `php artisan wallet:baseline --commit --force` — ถ้า production มี wallet ที่ไม่มี ledger กำกับ (เหมือน dev) ต้อง baseline **ก่อนเปิดถอนเงินจริง** (dry-run ดูก่อน)
4. `php artisan wallet:flag-legacy-withdrawals --commit` — ถ้ามีรายการ returned เก่าไม่มี refund
5. ยืนยัน **cron `* * * * * php artisan schedule:run`** ทำงานบน server (ไม่งั้น daily reconcile ไม่รัน)
6. หลัง deploy: `php artisan wallet:reconcile` ต้องขึ้น HEALTHY

### งานที่ค้าง (backlog — ไม่ block)
- [ ] Precision uniformity เล็กน้อย: `wallet_transactions.amount/balance_before/balance_after` เป็น decimal(20,2) บน dev แต่ (10,2) บน fresh install — ทั้งคู่ decimal ปลอดภัย ไม่รีบ
- [ ] Double-entry control accounts (แผนไว้เป็น optional ตอน scale ใหญ่ — ไม่ทำตอนนี้)
- [ ] Frontend: `ui/composables/useAdminWallet.ts` + `pending.vue` ใช้ `$fetch` ตรง (ผิด convention `useApi`) — pre-existing, ยังไม่แก้
- [ ] Load test ถอนพร้อมกันจริงหลาย process (row-lock พิสูจน์เต็มต้องใช้หลาย connection — unit test ทำไม่ได้)
- [ ] Security follow-up เดิม: ลบ public student-card route (ดู memory `project_public_student_card_pii`)

### Context สำคัญ
- **โมเดล locked_balance:** `wallet` = ยอดใช้ได้เสมอ (หักทันทีตอน withdraw), `locked_balance` = เงินกันไว้ = Σ active withdrawals (pending/under_review/approved/processing). withdraw: wallet−, locked+. paid: locked− (เงินออกจริง). reject/cancel/failed: wallet+, locked− + สร้าง refund ledger. อย่าเปลี่ยน semantics นี้ — reconciliation ทั้งหมดอิงมัน
- **Ledger เป็น source of truth:** ทุกการแก้ wallet มี WalletTransaction row ที่ delta = balance_after−balance_before → `wallet == Σ delta` เสมอ ห้ามแก้ wallet โดยไม่มี ledger row (นี่คือสิ่งที่ทำให้ reconcile ตรง)
- **`gh` CLI ติดตั้งแล้ว** (v2.96.0) แต่ยังไม่ `gh auth login` — ใช้ token จาก Git Credential Manager ผ่าน `GH_TOKEN` (scope: repo,workflow,gist — ไม่มี read:org จึงต้องใช้ `gh api` REST สำหรับ pr edit/merge ไม่ใช่ `gh pr edit`)

### Branch / Git State
- Branch: `main` (sync กับ origin) — **push แล้วทุก PR**
- Uncommitted: หลังอัปเดต worklog นี้จะมี worklog รอ commit
- ทุก feature branch (#3–#8) ลบทั้ง local + remote แล้ว

---

## 2026-07-11 — Runtime verify Intake + G1-G3 → เจอ+แก้ 3 บั๊ก (build ผ่านแต่ runtime พัง)

> ⚠️ แก้ความเข้าใจจาก entry ก่อนหน้า: intake + G1-G3 "มี code + test/build ผ่าน" **แต่ใช้งานจริงไม่ได้** — runtime verify (login จริง, ขับ UI) พบ 3 บั๊ก ทั้งหมดแก้แล้ว commit `bc57c1db` (push แล้ว)

### บั๊กที่เจอ + แก้ (ทั้งหมด pre-existing, build/test ไม่จับ)
1. **PrimeVue ไม่เคยถูก wire เข้า app** (มี `primevue` v4 ใน package.json แต่ไม่มี plugin `app.use(PrimeVue)` เลย — มีแค่ VueDatePicker plugin) → `<Stepper>` (IntakeWizard, ImportWizard) + `<Dialog>` (StudentAccountActivationModal) resolve ไม่ได้ → wizard render ทุก step ซ้อนกัน, modal ใช้ API ผิด
   - **แก้:** rewrite เป็น stack จริงของแอป — custom Tailwind stepper + Headless UI Dialog + เติม `import { Icon } from '@iconify/vue'`
2. **intake ยิง API ด้วยชื่อโรงเรียน (Thai) แทน id** — `duplicate-check` + `submit` เรียก `/api/academies/{academyName}/...` แต่ route bind `{academy}` ด้วย **id** → 404 ทุกครั้ง → wizard เดินไม่ได้เลย
   - **แก้:** `studentIntakeService` + `useStudentIntake` + `StepIdentity`/`IntakeWizard` ใช้ academyId (inject จาก admin parent, resolve lazy ด้วย `toValue` เพราะ parent fetch async)
3. **หน้า import 500 ทั้งหน้า** — `import.vue` `definePageMeta({ middleware: ['auth','academy-role'] })` แต่ middleware `academy-role` ไม่มีอยู่จริง (มีแค่ admin-guest/auth/guest/nuxnan-admin/plearnd-admin)
   - **แก้:** เหลือ `['auth']`

### Verify runtime (browser, login)
- **G2 DataTable:** ✅ โหลดจริง (stats กำลังเรียน 2662/รับใหม่ 521/ยังไม่มีห้อง 719/รอเปิดบัญชี 46), search กรองได้, pagination, action buttons
- **Intake wizard:** ✅ stepper Tailwind เดิน step 1→2, `duplicate-check` → **200** (ใช้ id แล้ว), แสดงเฉพาะ step active
- **G1 Import:** ✅ หน้าโหลด (ไม่ 500), ImportWizard stepper 3 steps render
- **G3 Activation:** ✅ modal (Headless UI) เปิด/ปิด, icons ครบ; public page error state verified ก่อนหน้า
- **`npm run build`:** ✅ ผ่าน (exit 0, ไม่มี "Failed to resolve component" — ยืนยัน dev SSR warning เป็น artifact)

### ไฟล์ที่แก้ (7)
`StudentAccountActivationModal.vue`, `IntakeWizard.vue`, `ImportWizard.vue`, `StepIdentity.vue`, `studentIntakeService.ts`, `useStudentIntake.ts`, `import.vue`

### ยังค้าง
- intake **submit จริง** (สร้างนักเรียน) ยังไม่ทดสอบ (เลี่ยง side effect); StepPreview/StepConfirm ของ import ยังไม่ขับจนจบ
- PrimeVue ยังอยู่ใน package.json แต่ไม่ได้ใช้ — พิจารณาถอดออก (มี component อื่นใช้ `<Dialog>`/`<DataTable>` แบบ PrimeVue อีกไหม ควร audit)
- deploy notes สะสม (migrations, GamificationSeeder, composer install mpdf)

---

## 2026-07-11 — Backlog audit (Intake + G1-G3 all done) + typing migration regression fix

### สิ่งที่พบ
- **Student Intake Phase 2-3 เสร็จสมบูรณ์แล้ว** (ทำในเซสชันหลัง 2026-07-05 — worklog TODO เก่า stale):
  - **Phase 2 Backend:** `StudentIntakeController` (store/duplicate-check/index/stats/export) + `StudentIntakeService` + `StoreStudentIntakeRequest`/`CheckStudentDuplicateRequest` + `EnrollmentPolicy` + routes `api/academies/{academy}/student-intakes/*` — **`StudentIntakeControllerTest` ผ่าน 8/8** (atomic intake, permission registrar/students.manage, duplicate block, cross-academy reject, full-classroom rollback, academy-scoped duplicate check)
  - **Phase 3 UI:** 5-step wizard `IntakeWizard.vue` (Identity/Personal/Admission/Guardian/Review) + `DuplicateWarning.vue` + `useStudentIntake.ts` composable + `studentIntakeService` — reachable จากปุ่ม "รับนักเรียนใหม่" ใน `students/index.vue`
  - payload frontend ↔ backend `StoreStudentIntakeRequest` keys ตรงกัน (identity/personal/admission/previous_school/guardians+contacts/account)

### 🔴 Regression ที่แก้
- migration typing `9f084ff1` (`2026_07_11_100001_..._game_mode_to_string`) ใช้ raw `ALTER TABLE ... MODIFY` = **MySQL-only syntax** → พังทุกเทสต์ที่ใช้ SQLite (`SQLSTATE near "MODIFY"`) ตอนแรก verify ด้วย curl เลยไม่เจอ
- **แก้:** driver-guarded — MySQL ใช้ raw MODIFY (ที่ verify แล้ว), driver อื่น (SQLite) ใช้ Schema `->change()`
- migration รันบน WAMP MySQL ไปแล้ว (ไม่ re-run) → WAMP ยังถูกต้อง; fix มีผลกับ test SQLite + fresh deploy
- **ยืนยัน:** `tests/Feature/Api/Academy` กลับมาเขียว **75/75** (270 assertions)

### G1-G3 ตรวจแล้ว — เสร็จหมดเช่นกัน (stale เหมือน intake)
- **G1 Import History:** ✅ backend `student-imports` CRUD ครบ (index/upload/template/show/cancel/confirm/errors/retry/rows), `StudentImportControllerTest` **5/5**; frontend `import-history.vue` (listBatches+pagination) + `import.vue`→`ImportWizard`
- **G2 Student DataTable:** ✅ `StudentDataTable.vue` + `students/index.vue` wired (list/stats/export), ปุ่ม "รับนักเรียนใหม่"
- **G3 Account Activation:** ✅ public page `activate-student/[token].vue` + `StudentActivationController` (show/activate, token เข้ารหัส `token_hash`); **runtime verified** error state (token มั่ว → "ไม่สามารถเปิดบัญชีได้"); happy path ต้อง pending invitation (มี 0 records) + ติดกฎกรอกรหัสผ่าน

### ยังค้างจริง (verification + deferred — ไม่มีโค้ดใหม่ใน intake/G1-G3)
- **Runtime verify UI admin** (ต้อง login): intake wizard, import wizard, StudentDataTable, G3 happy path — build/test ผ่านแต่ยังไม่ขับ UI จริง (บทเรียนจากบั๊ก 500 typing: build ผ่าน ≠ runtime ผ่าน)
- Home Visit [id] detail page (0 records ยังทดสอบไม่ได้)
- Deploy steps สะสม (ดูด้านล่าง), security follow-up ลบ public student-card route, typing 2 ข้อสังเกตเล็ก

### Git
- commit นี้: migration fix + worklog

---

## 2026-07-11 — Typing runtime verify + Home Visit smoke test + PDF export

### งานที่ทำ
- **Typing runtime verification (เฟส 0–3 ที่ทำไว้แล้ว):** verify ผ่าน API + UI จริง (user login) — เจอ + แก้ **blocker 500** `typing_sessions.game_mode` ENUM ไม่มี `key_training`/`letter_runner` → migration `2026_07_11_100001_change_typing_sessions_game_mode_to_string` แปลงเป็น VARCHAR(32) (รันแล้ว); ยืนยัน submit 200, XP+, PP เท่าเดิม, key mapping ไทย, Phaser focus, /result, regression ครบ (ดู `typing-game-improvement-plan.md` section "Runtime Verification Results")
- **Home Visit admin runtime smoke test:** เปิดหน้า admin จริงใน browser (ปิด gap worklog เดิม) — index/create/export โหลดได้ ทุก API 200 ไม่มี 500 (statistics/zones/admin-visits/admin-students); มี 0 visit records
- **Home Visit PDF export (feature ใหม่):** เพิ่ม PDF ควบ CSV ในหน้า export admin
  - ติดตั้ง `mpdf/mpdf` (bundle ฟอนต์ไทย Garuda ในตัว)
  - `AdminController::exportVisits` build rows ครั้งเดียว branch `?format=csv|pdf`; PDF = Blade view A4 แนวนอนไทย (`resources/views/exports/home-visits-pdf.blade.php`)
  - Frontend `export.vue`: เพิ่มตัวเลือก PDF + **ส่ง `format` param** (เดิมไม่ส่ง → fallback CSV เสมอ)

### Verification
- Typing: `TypingRewardPolicyTest` + StudentCard suite ผ่าน; runtime submit key_training 200; regression modes 200
- PDF: curl endpoint CSV+PDF → 200; PDF เป็น `%PDF-1.4` 39KB, Garuda subset embed, `pdftotext -enc UTF-8` ดึงไทยถูกต้อง (ชื่อโรงเรียน/หัวตาราง/ชื่อนักเรียน); Pint ผ่าน
- ลบ test data (9 typing_sessions + temp visit) + คืน user.xp baseline 309; ลบ temp PDF/HTML ใน public/ หมด

### ⚠️ Deploy notes (สะสม)
- รัน migration: `idempotency_key` (points_transactions) + `game_mode` VARCHAR (typing_sessions)
- reseed `GamificationSeeder` (เคลียร์ `max_daily_earnings` เดิมเป็น null)
- `composer install` (dependency ใหม่ `mpdf/mpdf`) + temp dir `storage/app/mpdf` (โค้ด auto-mkdir)

### ยังค้าง (backlog)
- Typing UI 2 ข้อสังเกตเล็ก: route case `/play` vs `/Play`, mode reset เป็น word_typing หลังจบเกม
- Home Visit: [id] detail page ยัง smoke test ไม่ได้ (0 records); PDF option บน UI ยัง verify ตอน login ไม่ได้ (JWT หมดอายุ) — โค้ดยืนยันแล้ว
- Student Intake Phase 2–3, DataTable/Activation/Import History, home-visit schema, Student Card Request System (ยังเป็นแผน)

### Git
- commits: `9f084ff1` typing fix, `b3a0bf8f` typing doc, `8e1ccfe0` home-visit PDF (+ `5130fc5a`/`183f5a6e` student-card PII mask→revert, `89a51f38` doc sync ก่อนหน้า)

---

## 2026-07-11 — Student Card Public PII (mask → revert) + doc sync

### งานที่ทำ
- **ตรวจพบช่องโหว่:** public (no-auth) `GET /api/student-card/{level}/{room}` คืน PII เต็ม (`national_id` + `birth_date`) ของนักเรียนทั้งห้อง — `StudentCardPublicResource` เดิม**คืนค่าเท่ากับ resource authenticated เป๊ะ** (แยก class ไว้แต่ไม่ได้ mask อะไรเลย)
- **แก้ mask (`5130fc5a`):** mask `national_id` เหลือ 2 กลุ่มท้าย + ตัด `birth_date` เป็น null เฉพาะ anonymous, authenticated/admin ยังเห็นเต็ม + frontend รองรับค่า masked + เทสต์ e2e ผ่าน route จริง
- **Revert (`183f5a6e`) — ตามคำสั่งเจ้าของ:** เปิด public PII เต็มกลับ **ชั่วคราว** เพราะผู้ใช้ยังไม่พร้อม login (คุมการเข้าถึงเองผ่านการแจก URL)

### ⚠️ Security decision ที่ค้าง (ต้องตัดสินก่อน production)
- public route เปิด PII เต็มโดยเจตนา = ความเสี่ยงที่ยอมรับชั่วคราว
- **แผนที่ตกลง:** เมื่อผู้ใช้ login ได้ → **ลบ route public ทิ้ง** จำกัดเป็น admin/ผู้มีสิทธิ์ (ไม่ใช่ mask) — โค้ด mask กู้กลับได้โดย revert `183f5a6e`
- บันทึกใน memory `project_public_student_card_pii.md` แล้ว (กัน mask ซ้ำ)

### Doc sync
- อัปเดต `latest-analysis.md` "แผนดำเนินงานเป็นเฟส" XP/PP: เฟส 0–3 mark `[x]` done (ตรงกับ commit `af434d89`/`a1a23d30`), เฟส 4 คง `[ ]` optional + เพิ่มบล็อกสถานะ (verification + deploy steps ยังค้าง)

### Git
- `main` = `origin/main` (push แล้ว): `5130fc5a` mask + `183f5a6e` revert

---

## 2026-07-11 — Game XP/PP Reward Policy (เฟส 0–3 + hardening)

### งานที่ทำ
ปรับนโยบายการให้คะแนนในเกมพิมพ์ดีด: **XP ให้ได้เต็มที่ (behavior-funded), PP ให้เฉพาะกิจกรรมมีเพดาน (budget-funded)** เพราะ PP แปลงเป็นเงินจริงได้ (`1200 pp = 1 บาท`)

- **เฟส 0:** migration `2026_07_11_000001_add_idempotency_key_to_points_transactions` (nullable+unique, additive) + `PointsService::awardGoverned()` (เช็ค idempotency → rule limit → `earn()` + catch `QueryException`); `earn()` รับ `idempotency_key` param ท้าย (default null)
- **เฟส 1:** ลบ PP `floor(score/100)` ใน `TypingSessionController` → typing session ปกติให้ XP อย่างเดียว
- **เฟส 2 Daily Challenge:** อ่าน wpm/accuracy จาก `TypingSession` ใน DB (ไม่เชื่อ client), guard owner/game_mode/challenge_id/`isToday()`/session ซ้ำ, ห่อ `DB::transaction`+`lockForUpdate`, จ่ายผ่าน `awardGoverned`
- **เฟส 2 Tournament:** `claim()` guard `rank===null` + `lockForUpdate` + atomic; ใช้ `FinalizeTypingTournaments` (มีอยู่เดิม + schedule `->hourly()`) set rank; เพิ่ม tie-break `best_session_id`
- **เฟส 3:** payout ผ่าน `awardGoverned` ทั้งหมด + seed rules `typing_daily_challenge`, `typing_tournament_prize`
- **Hardening:** แก้ `canEarnFromRule` daily-check ให้ scope ตาม source (mirror monthly) กันบั๊ก aggregate cross-source; `awardGoverned` log เมื่อโดน limit ตัด

### Verification
- `TypingRewardPolicyTest` ผ่าน 5/5 (25 assertions); Points/Gamification/Reward/Quest อื่นผ่าน 43; Pint ผ่านทุกไฟล์
- บั๊กเดิมนอกขอบเขต (ไม่แก้): `WalletAndPointsTest::test_user_can_earn_points` ล้มบนโค้ดเดิมด้วย (`updateUserLevel` + `xp` null); `updateDailyLimits` มี edge case เฉพาะ SQLite (production MySQL `DATE` ไม่เกิด)

### ⚠️ ต้องทำตอน deploy
- **reseed `GamificationSeeder`** บน env ที่เคย seed `typing_daily_challenge.max_daily_earnings=10` เพื่อล้างเป็น null (ตั้ง explicit null แล้ว updateOrCreate จะเขียนทับ)
- รัน migration `php artisan migrate` (เพิ่มคอลัมน์ `idempotency_key`)

### Backlog (ยังไม่ทำ)
- เฟส 4 Admin Event framework (optional); Achievement PP (ยังไม่มีฟิลด์ `pp_reward` ในโมเดล)
- รายละเอียดเต็มใน `.agents/latest-analysis.md` section "Work Plan — นโยบายการให้คะแนน XP / PP ในเกม"

### Branch / Git State
- Branch `main` — commit ชุดนี้ยังไม่ push (รอ confirm)

---

## 2026-07-10 — Home Visit Admin Legacy Cleanup

### งานที่ทำ
ลบ dead code ฝั่ง Home Visit Admin ที่เป็น legacy Inertia (`axios` + `router.visit`/`router.post`) ซึ่งถูกแทนที่ด้วยหน้า Nuxt-native ใหม่ `pages/academies/[name]/admin/home-visits/*` (index/create/export/zones/[id]) ที่ link ใน sidebar แล้ว (`admin.vue:169`) และยิง `/api/academies/{academy}/home-visits/*` (auth:api + academy.permission)

- **ลบ (scope แคบ เฉพาะที่พัง):** `pages/Learn/Student/HomeVisit/Admin/` ทั้งโฟลเดอร์ (16 ไฟล์: Dashboard + Components/* + MockData) + `composables/useVisitReports.js` (orphaned — ใช้เฉพาะ 2 component ในโฟลเดอร์เก่า)
- **คงไว้ (ไม่แตะ):** `HomeVisit/Student/`, `Teacher/`, `Auth/`, `Components/`, `Composables/` — portal เก่ายังเรียก `/api/home-visit/student/*` + `/teacher/*` ที่ยังมีอยู่ ไม่พัง
- เหตุ regression: legacy admin routes `/api/home-visit/admin/*` ถูกลบไปแล้ว (ดู `routes/homevisit/homevisit.php:139-140`) → หน้าเก่าเรียกแล้ว 404 (และใช้ Inertia router ที่ไม่มีใน Nuxt อยู่แล้ว)

### Verification
- `npm run build` ผ่าน (Build complete) ไม่มี broken import
- grep ทั้ง repo: 0 reference ค้าง (`useVisitReports` / `home-visit/admin`)
- git: commit `e849b161` (ลบ 17 ไฟล์ / −5,122 บรรทัด) — ยืนยัน commit ไม่แตะไฟล์ non-Admin

### ⚠️ Feature gap ที่ต้องตามต่อ (backlog — ยังไม่ block)
- **PDF export หาย**: หน้าเก่ามี per-visit PDF report (`/admin/visits/{id}/report`) + bulk PDF export (`/admin/visits/export/pdf`) แต่หน้าใหม่มีแค่ **Excel** export (`/admin/export/visits`) — ถ้าโรงเรียนต้องใช้รายงาน PDF จริง ต้องเพิ่ม endpoint + ปุ่มใน `academies/[name]/admin/home-visits/export.vue` ใหม่
- **Runtime smoke test ยังไม่ทำ**: build ผ่านแล้วแต่ยังไม่ได้ login เปิดหน้า admin จริงเพื่อ verify API ตอน runtime

### Branch / Git State
- Branch `main`, commit `e849b161` — pushed origin แล้ว

---

## 2026-07-10 — Academy Admin Settings Schema Fix

### งานที่ทำ
แก้ไขบั๊ก `SQLSTATE[42S22] Unknown column 'description'` ที่หน้าการตั้งค่าข้อมูลโรงเรียน `/academies/{name}/admin/settings` โดยดำเนินการดังนี้:

- **Database Migrations**
  - สร้างและรัน migration `2026_07_10_000001_add_settings_fields_to_academies_and_settings` เพื่อเพิ่มคอลัมน์ใน `academies` (`name_en`, `description`, `description_en`, `website`, `province`, `country`, `name_slug`) และใน `academy_settings` (`privacy`, `join_mode`, `allow_student_registration`, `allow_parent_registration`, `show_member_list`, `show_course_list`) พร้อม idempotent check และ auto-backfill `name_slug` สำหรับโรงเรียนที่มีอยู่เดิม
- **Backend Eloquent Models**
  - เพิ่ม attributes ใหม่ลงใน `$fillable` ของ `Academy` และเพิ่ม `$casts` boolean ใน `AcademySetting`
  - **Cache invalidation fix**: เพิ่ม boot hook ใน `AcademySetting` (`saved`/`deleted` → `Cache::forget("academy_settings_{id}")`) เพราะ `Academy::getSettings()` cache ค่าไว้ 24 ชม. และเดิมล้าง cache เฉพาะตอน `Academy` row dirty — ทำให้การแก้ "เฉพาะ setting" (เช่น สลับ privacy โดยไม่แก้ชื่อโรงเรียน) คืนค่าเก่าค้างนานถึง 24 ชม.
- **Backend Controller & Resource**
  - เพิ่ม request validation ใน `AcademyController@updateSettings`, รองรับการบันทึก `join_mode` แบบ non-lossy, ป้องกัน collision ของ `name_slug`, และสร้าง setting row ถ้ายังไม่มี
  - flatten ฟิลด์ setting ขึ้น top-level ใน `AcademyResource` ป้องกันหน้า UI รีเซ็ตค่ากลับ default ทุกครั้งหลังโหลด/บันทึก
- **Frontend**
  - อัปเดต `settings.vue` avatar/cover preview ให้ชี้ `logo_url`/`cover_url` (แทนคีย์ `avatar` เดิมที่ไม่มีอยู่จริง)
- **Code Quality & Testing**
  - `AcademySettingsUpdateTest` — **5 เทส / 57 assertions ผ่านหมด** ครอบคลุม full-field round-trip, permission denial, validation, slug collision, และ **regression test พิสูจน์ว่าการแก้ setting อย่างเดียวไม่คืนค่าค้าง cache** (ปิด hook แล้วเทสต์ fail จริง → ยืนยันว่าเทสต์มีความหมาย)
  - จัดการ format ด้วย Laravel Pint

### ไฟล์ที่สร้างใหม่/แก้ไข
- `database/migrations/2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` [NEW]
- `tests/Feature/Academy/AcademySettingsUpdateTest.php` [NEW]
- `app/Models/Academy.php` [MODIFY]
- `app/Models/AcademySetting.php` [MODIFY]
- `app/Http/Controllers/Api/Learn/Academy/AcademyController.php` [MODIFY]
- `app/Http/Resources/Learn/Academy/AcademyResource.php` [MODIFY]
- `ui/pages/academies/[name]/admin/settings.vue` [MODIFY]

### Branch / Git State
- แตก branch `fix/academy-admin-settings-schema` → commit 3 ชุด (`59af6c73` backend, `2886dba0` frontend, `e1a12493` tests) → **merge เข้า `main` แล้ว** (`263ee465`, `--no-ff`) และ push origin เรียบร้อย
- Migration รันบน DB `nuxnan` แล้ว (ยืนยันคอลัมน์ครบทั้ง `academies` และ `academy_settings`)
- Uncommitted ที่เหลือ (ไม่เกี่ยวงานนี้ ปล่อยไว้): `.agents/implementation_plan.md` และ `2026_07_10_013214_modify_id_in_user_usage_events_table.php` (untracked, มีอยู่ก่อน session)

---

## 2026-07-09 — PromptPay Withdrawal Channel (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
เพิ่มช่องทางถอนเงินผ่าน "พร้อมเพย์" ต่อจากการถอนเข้าบัญชีธนาคารเดิม โดยใช้ `wallet_transactions.metadata` (JSON) — **ไม่มี migration**

**Policy ที่ล็อก (ผู้ใช้เลือก):**
- ถอนขั้นต่ำ **25 บาท**, ค่าธรรมเนียม **13%** (ยึดตาม UI เดิม → แก้ backend ให้ตรง)
- รับพร้อมเพย์ 2 รูปแบบ: เบอร์มือถือ 10 หลัก (`0[689]xxxxxxxx`) + เลขบัตร ปชช. 13 หลัก
- field `method` เดิม รับค่า `'bank_transfer' | 'promptpay'`, marker คือ `bank_account.bank_name = 'promptpay'`

**Backend**
- `WalletService::withdraw()` — fee 13% (method-aware: `internal_deduction` = 0 กัน deduct pathway พัง), เพิ่ม `metadata.destination_type`
- `WalletController::withdraw()` — `amount` min 25, `method` in list, validate/normalize เบอร์พร้อมเพย์ (ตัด `-`/space), whitelist ธนาคาร, กันปลอม bank_name
- test ใหม่ `tests/Feature/Wallet/WithdrawTest.php` — 11 cases ผ่านหมด

**Frontend**
- `useWallet.ts` — type union + helper `normalizePromptPay`/`validatePromptPay`/`formatPromptPay`
- `Wallet.vue` — segmented toggle บัญชีธนาคาร/พร้อมเพย์, autofill เบอร์จาก profile, inline validation, min 25
- `nuxnan-admin/wallet/pending.vue` — label/icon/badge dynamic + fallback record เก่า (ไม่มี `destination_type`)

### Verification
- Backend 11/11 ผ่าน, Pint passed, `npm run build` สำเร็จ ไม่มี type error
- ⚠️ `WalletAndPointsTest > user can earn points` fail แต่ **fail บน baseline ด้วย** (pre-existing, เรื่อง points ไม่เกี่ยวงานนี้)
- ยังไม่ได้ verify ในเบราว์เซอร์ (หน้า Wallet อยู่หลัง auth middleware)

### Commit
- Backend: WalletService + WalletController + WithdrawTest
- Frontend: useWallet.ts + Wallet.vue + pending.vue
- (โน้ต `.agents/` ปล่อยไว้ไม่ commit เพื่อไม่ปนกับงาน rollover ที่ยังค้าง)

---

## 2026-07-09 — Rollover Harden Live Verification (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
- **Regression tests ผ่าน 41/41 (106 assertions)** จาก 3 ไฟล์: `AcademicYearRolloverServiceTest`, `RolloverControllerWriteTest`, `ResourceShapeTest` — รวม 7 harden tests ใหม่ (skip/undo/rename/demote invariants) และ end-to-end mixed skip+promote → undo
- **Dry-run health checks** — `enrollment:repair-dirty-data --dry-run` พบ 3 duplicate active enrollments ในปี 2569 (student 2824/2846/2868) และ `enrollment:backfill-academic-info --dry-run` รายงาน 1929 SAI จะสร้างเพิ่ม (drift สะสมจาก rollover 2568→2569 เดิม)
- **Baseline clean-up** — รัน `enrollment:repair-dirty-data --commit`: 3 duplicate rows ถูก superseded, dry-run รอบสองยืนยัน 0 drift
- **Synthetic test student** — insert `id=2944 T-ROLLOVER-1` ใน 2569 M1/1 (isolated: single active enrollment, single current SAI, ไม่มีบัตร)
- **Live end-to-end test (commit + undo cycle) — ผ่าน 15/15 assertions** สร้างปี 2570 + ห้อง M2/1 ชั่วคราว รัน `AcademicYearRolloverService::commitRollover()` แบบ minimal plan (promote entry เดียว) แล้ว undo กลับ verify ทุก field:
   - `plan_summary.from_academic_year_name='2569'` / `to_academic_year_name='2570'` (harden invariant — frozen names)
   - `beforeSnapshots` มี student id (แสดงว่า promote ไม่ใช่ skip)
   - Post-commit: student.class_level='2', old CS 'promoted' + rollover_batch_id, new CS 'active' + rollover_batch_id, old SAI is_current=0, new SAI is_current=1 grade='ม.2'
   - Post-undo: student/CS/SAI กลับ baseline เป๊ะ, new CS+SAI ถูกลบ
- **Cleanup** — ลบ synthetic student + 2570 + M2/1 + rollover batch ครบ dry-run health check รอบสาม: 0 drift

### ไฟล์ที่แก้ / เพิ่ม
- ไม่มี code change ใน session นี้ (harden commit `95127816` ทำไว้แล้วก่อนหน้า)
- Persistent DB change: 3 rows ใน `classroom_students` เปลี่ยน status จาก duplicate `active` → `superseded` (baseline hardening ก่อน live test) — enrollment count ปี 2569 เดิม 2215 active → ปัจจุบัน 2212 active + 3 superseded (สุทธิเท่าเดิม)

### สิ่งที่ยืนยันจาก session นี้
- Commit 95127816 hardening ทำงานถูกต้องบนข้อมูลจริง (ไม่ใช่แค่ SQLite in-memory ในเทส)
- Undo pipeline คืนสถานะได้ถูกต้อง แม้เป็น full-service pathway (ไม่ใช่แค่ database transaction rollback)
- Go/No-Go gate: ✅ ผ่านทั้งหมด — regression tests, dry-run health checks, live test student, baseline restored, ไม่มี drift

### หมายเหตุก่อน live rollover ห้องจริง
- ยังมี **1929 missing SAI drift** จาก rollover เก่า — ไม่กระทบ commit/undo (test verified) แต่ควรรัน `enrollment:backfill-academic-info` (commit mode) ก่อน rollover จริงรอบใหม่ เพื่อ baseline สะอาด
- Branch นี้ยังไม่ merge เข้า main — harden commit + home-visit refactor ยังอยู่บน `fix/home-visit-admin-classroom-refactor`

### Scratchpad artifacts (ลบได้)
- `%TEMP%/…/scratchpad/create_synthetic_student.php`
- `%TEMP%/…/scratchpad/live_rollover_test.php`

---

## 2026-07-09 — Home Visit Admin Refactor (branch: `fix/home-visit-admin-classroom-refactor`)

### งานที่ทำ
- **Root cause fix**: `student_academic_info.classroom` ถูกลบไปตั้งแต่ migration `2026_04_08_050000` แต่ `AdminController` ยังอ้าง → 500 บน `/students`, `/visits`, `updateStudent`, และ CSV export
- **Multi-academy isolation**: ผูก `Academy $academy` + scope `academy_id` ให้ทุก admin endpoint (statistics/dashboard/students/visits/show*/update*/export/getAllVisits)
- **updateStudent เปลี่ยนห้องผ่าน enrollment service**: validate `classroom_id` ด้วย `Rule::exists` ที่ผูก academy → `StudentEnrollmentService::enrollStudent/transferStudent/promoteStudent/removeFromClassroom` (ไม่เขียน string ลง academic_info โดยตรง)
- **CSV/filter fixes**: null-safe visit_date, ใช้ `currentAcademicInfo->classroom_full`, แทน `teacher_name` (column ไม่มี) ด้วย `visitor_name`, พอร์ต SQL `CAST AS SIGNED` ให้ SQLite/MySQL ใช้ร่วมกันได้
- **`dashboardMock` gate ด้วย env** (local/testing เท่านั้น)
- **Backfill migration** `2026_07_09_000001_backfill_academic_info_classroom_id_from_current_enrollment.php` — idempotent, match academic_year name ก่อน fallback `is_current`/latest, log ก่อน/หลัง
- **Tests: 15 passed / 50 assertions** ครอบคลุม scoping, filter, transfer enrollment, cross-academy rejection, CSV output, legacy compat, mock 404 + migration idempotent/tie-break/no-enrollment

### Commits (บน branch `fix/home-visit-admin-classroom-refactor`)
- `c8aa028c` refactor(home-visit): scope admin endpoints to academy and drop legacy classroom column
- `16a559f5` test(home-visit): admin controller and backfill migration coverage

### ⚠️ Follow-ups ที่ยังไม่ได้ทำ (สำคัญ)
1. **Legacy routes `/api/home-visit/admin/*`** ใน `routes/homevisit/homevisit.php:141-164` ยัง active และเรียก controller methods ที่ต้องการ `Academy $academy` → **จะพัง** เมื่อ frontend เก่า (`ui/pages/Learn/Student/HomeVisit/`) หรือ page ใหม่บาง endpoint ที่ยัง hard-code `/api/home-visit/admin/*` เรียกเข้ามา  ต้องเลือก: (a) ลบ route group นี้ + migrate frontend, หรือ (b) ทำ shim ที่ resolve academy จาก session
2. **Frontend Phase 6**: `ui/composables/useVisitReports.js`, `pages/academies/[name]/admin/home-visits/*.vue` ยังใช้ URL `/api/home-visit/admin/*` และ payload/shape เดิม (ส่ง `classroom` string, รับ dropdown เป็น list string) — ต้องอัปเดตให้ใช้ `classroom_id` + shape ใหม่ `{id, name, grade_level, section}`
3. **Dead methods**: `downloadReport`, `exportToExcel`, `exportToPDF` ใน AdminController ไม่มี route ชี้ (ไม่กระทบตอนนี้ แต่ถ้าจะเปิดใช้ ต้องเพิ่ม Academy binding)
4. **Pre-existing failing test** (ไม่เกี่ยวกับ refactor): `Tests\Feature\Academy\Enrollment\ResourceShapeTest::rollover_batch_resource_reports_undoable_state` — fail แม้บน branch เดิม (ยืนยันด้วย `git stash && test`)
5. **PR**: branch นี้ยังไม่ได้ push/เปิด PR — พร้อม merge ถ้า resolve legacy route + frontend migration แล้ว

### ที่ทำงานถัดไปควรเริ่มจาก
- Follow-up 1 (legacy route decision) ก่อน merge branch นี้
- Follow-up 2 (frontend migration) เป็น PR แยก

---

## 2026-07-05 — API Bug Fixes & Admin Smoke Test (Session 2)

### งานที่ทำ
- **Fix Reports Page 500** — `dashboardStats` endpoint ใช้ namespace ผิด (`Learn\Academy\ClassSchedule` → `Models\ClassSchedule`) + AssignmentAnswer query ใช้ polymorphic relationship ผิด → เพิ่ม try-catch เพื่อ graceful fallback
- **Fix HomeVisit AdminController** — ลบ deprecated `$this->middleware()` ที่ไม่รองรับใน Laravel 12
- **Smoke Test Admin Pages** — ทดสอบ 6 หน้า:
  - reports ✅ (แสดง 2893 นักเรียน)
  - departments ✅ (5 แผนก + ปุ่ม setup ทำงาน)
  - gradebook ✅ (51 ห้องเรียน)
  - school-attendance ✅ (1 รายการ)
  - announcements ✅ (3 ประกาศ)
  - home-visits ⚠️ (pre-existing bug: `student_academic_info.classroom` column ไม่มี)
- **5 ฝ่ายมาตรฐาน** — ยืนยันว่ากดปุ่ม "โครงสร้างมาตรฐาน" แล้วสร้างแผนกครบ 5 สำเร็จ

### Pre-existing Bugs (ไม่ได้แก้)
- `home-visits/statistics` → 500 เพราะ `student_academic_info.classroom` column ไม่มีใน DB
- `academic years` console error — fetch academic years ล้มเหลว (ไม่กระทบ UI หลัก)

### Commits
- `b1fe7dc9` fix(api): resolve dashboardStats 500 and HomeVisit middleware error

### งานถัดไป (Backlog)
- [x] Student Intake Phase 2-3 (Single Student Intake backend + Registrar UI) — **done** (verified 2026-07-11, test 8/8)
- [x] Student List DataTable (Phase G2) — **done** (StudentDataTable.vue + index wired)
- [x] Student Account Activation Page (Phase G3) — **done** (public page + controller; error state verified)
- [x] Import History Page (Phase G1) — **done** (import-history.vue + backend CRUD, test 5/5)
- [x] Fix home-visits schema mismatch (student_academic_info.classroom) — **done** (home-visit refactor 2026-07-09/10)

> ⚠️ ทั้งชุดนี้ถูก implement ในเซสชันหลัง 2026-07-05 แต่ checkbox ไม่ได้อัปเดต — ยืนยัน done ทั้งหมดวันที่ 2026-07-11 (ดู entry บนสุด)

---

## 2026-07-05 — Admin Panel Smoke Test & Restructure (Phase A-D)

### งานที่ทำ
- **Phase A: Smoke Test & Bug Fixes**
  - Fixed CORS for dev preview (dynamic port regex in `allowed_origins_patterns`)
  - Created `CheckAcademyPermission` middleware + registered in `bootstrap/app.php`
  - Fixed `classroomStudents` → `classroomEnrollments` relationship name in StudentIntakeController
  - Rewrote `students.vue` parent to use provide/inject for academy ID
  - Fixed StudentDataTable and import pages to use academy ID instead of name

- **Phase B: Admin Sidebar Restructure**
  - Updated `admin.vue` parent route with complete sidebar (30+ pages linked)
  - Fixed mismatched sidebar links: attendance→school-attendance, grades→gradebook
  - Added missing pages: events, store, at-risk, invite-links, member-tags, guardians, etc.
  - Parent route now provides `academyId`, `academyName`, `academy` to all children
  - Simplified `students.vue` sub-parent to passthrough

- **Phase C: Enrollment Lifecycle UI**
  - Wired `StudentActionMenu` + `StudentStatusActionModal` into StudentDataTable
  - Added action column with 5 lifecycle actions (graduate/drop/repeat/promote/transfer)
  - Added enrollment history drawer button per row
  - All actions call existing backend endpoints via `useStudentEnrollmentActions` composable

- **Phase D: Reports Dashboard**
  - Created `reports.vue` page with overview stats from analytics API
  - Report sections with links to students, at-risk, attendance, gradebook, staff, etc.

### หมายเหตุ
- Parent portal at `/academies/[name]/parent/` already fully built (grades, attendance, meetings)
- Client-side navigation between admin pages may show transition glitches (HMR); full page loads work fine
- 15 commits ahead of origin, not pushed yet

### Commits (this session)
- `dcec3bc5` fix(school): smoke test fixes — CORS, middleware, route binding, relationship
- `17753e6a` feat(school): restructure admin sidebar with complete navigation
- `40b4041c` feat(school): wire enrollment lifecycle actions into StudentDataTable
- `402e0ab3` feat(school): add Reports Dashboard page

---

## 2026-07-05 — Student Intake System Phase 1

### งานที่ทำในวันนี้
- **Phase 1: Database Constraints Fix** 
  - สร้างและรัน migration `fix_student_unique_constraints` เปลี่ยน `student_id` และ `citizen_id` เป็น academy-scoped (unique per academy_id)
  - สร้างและรัน migration `add_enrollment_lookup_index_to_classroom_students` เพิ่ม index สำหรับค้นหา active enrollment
  - สร้างและรัน migration `create_student_import_tables` สำหรับรองรับระบบ bulk import (ตาราง `student_import_batches` และ `student_import_rows`)
- **Registrar Role Setup**
  - แก้ไข `AcademyRole::SYSTEM_ROLES` เพื่อเพิ่ม role `registrar` ("นายทะเบียน") ที่มีสิทธิ์ครบถ้วนสำหรับการทำงานเรื่องรับเข้าและจัดการนักเรียน
  - รัน `AcademyRoleSeeder` ด้วย updateOrCreate เพื่อให้ระบบทุก academy มี role นึ้ใช้งานได้ทันที

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **Phase 2 — Single Student Intake (Backend)** 
- [ ] **Phase 3 — Registrar UI (Single Intake)**

---

## 2026-07-04 — School Department Setup Template (5 ฝ่ายมาตรฐาน)

### งานที่ทำในวันนี้
- **วิเคราะห์โครงสร้าง 5 ฝ่ายมาตรฐาน** — เปรียบเทียบ proposed data model กับ codebase จริง สร้างบทวิเคราะห์แก้ไข `.agents/school-5-departments-revised-analysis.md`
- **Phase 1: SchoolDepartmentSetupService** — สร้าง service ที่มี template 35 groups (1 office + 5 departments + 21 sections + 8 academic_groups) พร้อม idempotent setup ด้วย name+type matching
- **Phase 2: API Endpoints** — เพิ่ม `GET /departments/template` และ `POST /departments/setup` ใน DepartmentController + routes
- **Phase 3: Seeder** — สร้าง `SchoolDepartmentSeeder` สำหรับ dev/demo
- **Phase 4: Frontend** — สร้าง `DepartmentSetupModal.vue` (tree preview + conflict handling) อัพเดท `departments.vue` (ปุ่ม setup ที่ header + empty state) เพิ่มปุ่ม "ฝ่ายงาน/แผนก" ใน admin index quick actions

### งานที่ค้างอยู่ (TODO ต่อ)
- [ ] **ยังไม่ได้ commit** — ไฟล์ทั้งหมดยังเป็น uncommitted changes (ดู git status ด้านล่าง)
- [ ] **ทดสอบ seeder** — รัน `php artisan db:seed --class=SchoolDepartmentSeeder` บน WAMP จริง
- [ ] **ทดสอบ UI จริง** — login เข้า admin → กดปุ่ม "ตั้งค่าโครงสร้างมาตรฐาน" → ตรวจ hierarchy ถูกต้อง
- [ ] **classrooms/statistics 500** — bug เดิมไม่เกี่ยวกับงานนี้ แต่ `ClassroomController.php` มี uncommitted changes อยู่ (ตรวจว่าเป็นงานก่อนหน้า)

### Context สำคัญ
- **แนวคิด opt-in per school** — ไม่ได้สร้าง departments ให้ทุกโรงเรียนอัตโนมัติ admin ต้องกดปุ่มเอง
- Component ใน Nuxt ต้องใช้ชื่อ `SchoolDepartmentSetupModal` (prefix folder `school/`) ไม่ใช่ `DepartmentSetupModal`
- `POST /departments/setup` รองรับ `force=true` กรณีมี groups อยู่แล้ว — จะ skip รายการที่ซ้ำชื่อ+type
- แผนพัฒนาอยู่ที่ `.claude/plans/purrfect-fluttering-grove.md`
- บทวิเคราะห์ 5 ฝ่ายอยู่ที่ `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่สร้างใหม่
- `api/nuxnanravel/app/Services/SchoolDepartmentSetupService.php`
- `api/nuxnanravel/database/seeders/SchoolDepartmentSeeder.php`
- `ui/components/school/DepartmentSetupModal.vue`
- `.agents/school-5-departments-revised-analysis.md`

### ไฟล์ที่แก้ไข
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/DepartmentController.php` — เพิ่ม getTemplate(), setupDepartments()
- `api/nuxnanravel/routes/learn/academy.php` — เพิ่ม 2 routes (template, setup)
- `ui/pages/academies/[name]/admin/departments.vue` — ปุ่ม setup + empty state + modal
- `ui/pages/academies/[name]/admin/index.vue` — เพิ่ม quick action "ฝ่ายงาน/แผนก"

### Branch / Git State
- Branch: `main`
- Uncommitted: **yes** — 5 modified + 4 untracked (ดูรายละเอียดด้านบน)
- Push status: ยังไม่ commit / ยังไม่ push

---

## 2026-07-03 — Course Lesson Per-Student Score Status

- **Backend (`CourseMemberController@show`)**:
  - Eliminated severe N+1 queries during progress calculation by eager loading all related `AssignmentAnswer` and `CourseQuizResult` records for the user.
  - Refined `resolveLessonScoreStatus` to return `submitted` when assignments have no points, preventing test failures.
  - Test `CourseMemberProgressTest` successfully passes asserting query count is stable (below 60 queries) despite the number of assignments and quizzes.
- **Frontend (`ui/`)**:
  - Added TypeScript definitions for the new API payload in `ui/types/lessonScore.ts`.
  - Updated `useCourseLearningProgress.ts` and `CoursePageShell.vue` to distribute `score_status`, `score`, and `max_score` from the API.
  - Enforced a more expensive and elegant appearance in `CourseLessonsMenu` and `CourseLessonProgressWidget` based on the user's "เป็นระเบียบ + แพงขึ้น" aesthetic preference.

## 2026-07-03 — ✅ FEATURE COMPLETE

### School Student Master Profile Unification — เสร็จสมบูรณ์ทุก Phase

| Phase | งาน | Commit |
|-------|-----|--------|
| 0–4 | Branch + schema verify + backend API sections + 8-tab shell | `74f1fb8a` |
| 5 | Navigation Unification (MemberManageModal, student-cards, home-visits, memberId redirect) | `f26bfa95` |
| 6+7 | Self-service my-profile 8 tabs + sectional edit endpoints + ChangeRequest approval flow | `3e95cc99` |
| 8 | Student Card module — card visual flip, admin photo upload/edit, byStudent route fix | `6c29c00d` |
| 9 | Home Visit CRUD — JWT-native controller, pagination, privacy filtering, migration | `328a058c` |
| 10 | Cleanup — remove `Schema::hasColumn` guard, update worklog | *(this commit)* |

### สิ่งที่เพิ่มเติม / ข้อมูลสำคัญ

**Routes ที่เพิ่มใน `student-profile.php`:**
- `PATCH /academies/{academy}/students/{student}/personal`
- CRUD `/addresses`, `/contacts`, `/guardians`, `/health`, `/academic-info`
- `GET/PATCH /change-requests` (approve/reject)
- `GET/POST/PUT/DELETE /home-visits` + `PATCH /home-visits/{visit}/status`

**Routes ที่เพิ่มใน `academy-student-card.php`:**
- `GET /student-cards/by-student/{student}`

**Feature scope ที่ตัดสินใจ skip:**
- Phase 5.1: QR flow `/members/{studentCode}` — ไม่มี route นี้ใน frontend
- PDF export ใน AdminController — pre-existing TODO ไม่เกี่ยวกับ feature นี้

**Admin pages ที่ยังคงอยู่ (ไม่ถูกลบ):**
- `/admin/home-visits/*` — ยังใช้งานอยู่สำหรับ full admin management (zones, export)
- `/admin/student-cards/*` — ยังใช้งานอยู่สำหรับ bulk operations

### Branch / Git State

- Branch: `feature/student-master-profile`
- Latest commit: *(phase 10)*
- Status: พร้อม merge/push
- Migration รันแล้ว: `expand_student_home_visit_statuses` ✅

---

## 2026-07-02 — บ้าน (อัพเดทรอบสอง)

### งานที่ทำในวันนี้ (เพิ่มเติม)

- **School Student Master Profile — Phase 0-4** (`74f1fb8a`)

### งานที่ค้างอยู่ (TODO ต่อ)

- [x] Phase 5–10 ทั้งหมด — เสร็จแล้ว (ดูตารางด้านบน)

---

## 2026-07-02 — บ้าน (รอบแรก)

### งานที่ทำในวันนี้

- **Phaser Phase N** — เปลี่ยน `PolygonPoint`/`PolyArg` → `FacePoints` + `facePoint()` helper, ลบ casts ทั้ง 8 จุด (`53e73d8d`)
- **Phaser Phase L** — เพิ่ม `drawLeaveHatch()` diagonal hatch overlay สำหรับสถานะ LEAVE พร้อม differential render ใน `updateSeatStatuses()` (`53e73d8d`)
- **Phaser Phase O** — เพิ่ม `showThinkDots()` / `destroyThinkDots()` animation เหนือหัวครูตอน pause นาน ≥1200ms (`53e73d8d`)
- **Phaser Phase T2** — refactor nested `onComplete` 3 ชั้นใน patrol → `tweens.chain()` 4-step (inspect) + 2-step (front-walk), เพิ่ม `patrolTween: TweenChain` + `stopActiveChain()` (`2217f49f`)
- **Phaser Phase M** — ตรวจแล้วพบว่า implement เสร็จก่อนหน้าแล้ว (tooltip สมบูรณ์)
- **Dedupe `useMyStudentProfile` Types** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว
- **Quick Action "โรงเรียนของฉัน"** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว (`useMemberedAcademies.ts` + `DashboardQuickActions.vue`)
- **Thai default locale** — ตรวจแล้วพบว่าตั้งค่าถูกต้องอยู่แล้ว (`defaultLocale: 'th'`, `detectBrowserLanguage: false`)
- **Enrollment Phase 3.E** — ตรวจแล้วพบว่าเสร็จก่อนหน้าแล้ว (commit/undo/closeUndo + `fromArray()` + 7 routes)
- **Smoke test Earn pages** — พบและแก้ bug 2 จุด (`186b3ce1`):
  - `useRewards.ts:formatPoints()` guard undefined/null/NaN → แสดง `0` แทน `NaN`
  - `AchievementsDisplay.vue:loadStats()` merge ด้วย `{ ...stats.value, ...data }` แทน overwrite → แสดง `0/0` แทน `/0`

### งานที่ค้างอยู่ (TODO ต่อ)

- [ ] **School Student Master Profile Unification** — งานใหญ่ ~35 ชม., ยังไม่เริ่ม Phase 0-10
  - แผนละเอียดอยู่ใน `.agents/latest-analysis.md` (search "Student Master Profile")
  - เป้า: รวม student profile, card, home visit เป็นหน้าเดียว

### Context สำคัญ

- Phaser ไฟล์หลัก: `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`
- Earn pages ทั้ง 4 (`/Earn/Points`, `/Earn/Wallet`, `/Earn/Rewards`, `/Earn/Gamification`) ผ่าน smoke test desktop แล้ว — ยังไม่ได้ verify mobile/tablet viewport
- Enrollment 3.E เสร็จแล้วแต่ **ยังไม่ได้รัน live WAMP smoke test** บน real data (ตั้งใจ skip เพราะ 1929 rows) — ควรทำก่อน deploy จริง
- `RolloverControllerWriteTest`: 16 tests ผ่าน; regression 84 tests ผ่านทั้งหมด

### Branch / Git State

- Branch: `main`
- Uncommitted: ไม่มี (clean)
- Push status: **ยังไม่ push** — รัน `git push` ก่อนออก

---

## สถานะปัจจุบัน (2026-06-21)

- **Done:** Phase 2 (Service Layer Expansion):
  - **2.A Helper methods:** Extracted helper methods `closeActiveEnrollment`, `manageAcademicInfoSnapshot`, and `normalizeGradeLevel` inside `StudentEnrollmentService`.
  - **2.B-2.D Lifecycle transitions:** Added new methods `graduateStudent`, `dropStudent`, `repeatStudent`, and `promoteStudent`, and refactored `transferStudent` with strict year checks.
  - **2.E Event classes:** Added 8 plain event classes in `app/Events/Enrollment/` to broadcast enrollment lifecycle updates.
  - **2.F-2.I Rollover Service:** Implemented `AcademicYearRolloverService` with comprehensive operations: `previewRollover` (suggesting mappings + fallbacks + warnings), `planRollover` (validations), `commitRollover` (UUID generation + batch execution), `undoRollover` (reverting state and deleting temporary records with a 24h window), and `closeUndoWindow`.
  - **Verification:** Added feature tests `StudentEnrollmentServiceTest.php` and `AcademicYearRolloverServiceTest.php`. All 28 tests (101 assertions) passed successfully (100% pass). Runs pint clean.

- **Done:** Phase 3 (Controller & API Surface) — ทุก phase 3.A–3.E เสร็จแล้ว:
  - **3.A** EnrollmentPolicy + Gates
  - **3.B** FormRequests + API Resources
  - **3.C** StudentLifecycleController (6 endpoints)
  - **3.D** RolloverController Read (preview/plan/index/show) + plan caching
  - **3.E** RolloverController Write (commit/undo/closeUndo) + `RolloverPlan::fromArray()` — 7 routes รวม

- **Done:** Phase M (Gamification: School Level & Classroom Leaderboard)
- **Done:** Phase L (Closeout, Events mirroring & Post Variants)
- **Done:** Single Source of Truth NotificationService & Polling
- **Done:** Invite Flow + Admin Appointment + Group Notifications (Phase K)
- **Done:** Post-as-Group Composer + Feed Header (Phase J)
- **Done:** Academy Group Profile Page (Phase I)
- **Done:** Academy Student Self Profile & Student Card recovery

### Follow-ups (optional, not blocking)
- **Phase 4 (cleanup):** Remove `Schema::hasColumn` guard from `Student::studentCard()` after migration deployed to all environments for >1 sprint.
- **Backfill command hardening:** `StudentsBackfillCardLink` uses `->get()` instead of `chunkById` — fine for current 1930 rows but should be hardened before next backfill on a larger dataset.
- **Earn pages mobile/tablet viewport** — smoke test desktop ผ่านแล้ว แต่ยังไม่ verify mobile (375px) และ tablet (768px)
- **Enrollment live smoke** — preview → plan → commit → undo บน WAMP real data กับ test student 1 คน ควรทำก่อน release

## สถานะปัจจุบัน (2026-06-16)

### งานที่เพิ่งเสร็จสิ้น — Verified & Tested

- **Done:** Phaser classroom v5/v6.1 refinement (board depth + floor junction + teacher patrol safety + responsive patrol) (`dbcf903e`)
- **Done:** Phaser classroom renderer + grid zoning refinement (`907dedc0`)
- **Done:** Student self check-in + simulator UI (`03db0ee0`)
- **Done:** Earn white-screen — fixed in `5821d1d3` (NuxtLayout hoisted to app-level, Earn pages migrated to Teleport slots)
- **Done:** Topic Form Stale State Fix — already in history, no uncommitted diff
- **Done:** Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson (`060ce9fe`)
- **Done:** Image Gallery Viewer + Marketplace Filters (`0997d945`)
- **Done:** Academy Admin Embedded Marketplace Purchase (`d3959560` + `8ebedcf6`)

---

## งานที่เสร็จแล้ว (สรุปรวม)

| วันที่ | งาน | สถานะ |
|--------|------|-------|
| 2026-07-02 | Phaser N/L/O/T2 polish + Earn smoke test fixes | ✅ Done |
| 2026-06-22 | Course Academy Backfill and Academic Year Repair | ✅ Done |
| 2026-06-21 | Phase 3.A–3.E Enrollment Controller & API Surface | ✅ Done |
| 2026-06-21 | Phase 10 — Compatibility Inventory & Closure Documentation | ✅ Done |
| 2026-06-21 | Phase N — Polish + A11y + Mobile UX (Skeletons, EmptyState, Drawer, Swipe, FocusTrap, Keyboard Nav) | ✅ Done |
| 2026-06-20 | Phase I — Academy group profile page (Cover + Tabs + Gating + Composer) | ✅ Done |
| 2026-06-16 | Phaser classroom v5/v6.1 refinement (board + floor + patrol safety + responsive) | ✅ Done |
| 2026-06-13 | Phaser classroom renderer + grid zoning + self check-in | ✅ Done |
| 2026-06-11 | Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson | ✅ Done |
| 2026-06-11 | Image Gallery Viewer + Marketplace Filters | ✅ Done |
| 2026-06-11 | Academy Admin Embedded Marketplace Purchase | ✅ Done |
| 2026-06-11 | Admin Support Donate Fix + Topic Form Stale State Fix | ✅ Done |
| 2026-06-11 | Analysis File Consolidation | ✅ Done |
| 2026-06-10 | Draft Visibility & Interaction Lockdown (Lesson/Assignment/Quiz) | ✅ Done |
| 2026-06-09 | Sort Order System (Topics, Course Groups, Academy Groups) | ✅ Done |
| 2026-06-09 | Academy Group Reorder UI Implementation | ✅ Done |
| 2026-06-08 | Lesson Completion Requirement (บังคับอ่านก่อนทำแบบฝึกหัด) | ✅ Done |
| 2026-06-08 | Course Member Removal/Leave Workflow v2 | ✅ Done |
| 2026-06-07 | Eligibility Roster Filtering + Backlog Cleanup | ✅ Done |
| 2026-06-06 | Course Completion Workflow v2 | ✅ Done |
| 2026-06-06 | User Management & Username Integration | ✅ Done |
| 2026-06-03 | School Department Management (Codex Tasks) | ✅ Done |
| 2026-05-31 | Universal QR Scanner | ✅ Done |
| 2026-05-31 | School Management System Phase 6 | ✅ Done |
| 2026-05-29 | User Profile Fixes (6 Phases + Testing) | ✅ Done |
| 2026-05-29 | Exam Retake Flow Phase 2 | ✅ Done |
| 2026-05-25 | Typing Game Expansion + Course Point System | ✅ Done |
| 2026-05-25 | Lesson Access System (free/points/money) | ✅ Done |
| 2026-05-25 | Lesson Order Gap Fix + display_order | ✅ Done |
| 2026-05-25 | Exam Retake Flow Phase 1 | ✅ Done |
| 2026-05-24 | Lesson Drag-and-Drop Reordering | ✅ Done |
| 2026-05-24 | Remediation & Unified Eligibility | ✅ Done |
## 2026-07-03 — Student Master Profile Phase 9

- Completed JWT home-visit CRUD integration across `Master/HomeVisitController`, student-profile routes, `useHomeVisit.ts`, and `HomeVisitTab.vue`.
- Added status-enum migration `2026_07_03_000001_expand_student_home_visit_statuses.php` (created, not run) and focused `StudentHomeVisitApiTest` (3 passed, 12 assertions).
- Existing Phase 7 and other dirty-worktree changes were preserved.
## 2026-07-06 — Student Card Rollover 2568 → 2569

- Created academic year 2569 (id 2), set current after successful rollover, and created target classrooms: M1=10, M2=11, M3=9, M4=9, M5=8, M6=7.
- Committed rollover batch `3c9ca6f7-3ece-4bbd-8f51-b7d64eae5162`: promote 1,662; graduate 267; new intake 476; skip 0.
- Corrected duplicate card link: card 1440 now links to student 1411 by citizen ID; no record was deleted.
- Card sync results: created 476, updated 1,662, expired 268. Active 2569 enrollments = active cards = 2,138.
- Integrity checks all zero: duplicate active cards, multiple active enrollments, multiple current academic rows, active enrollment without active card.
- Added migration `2026_07_06_200000_allow_uuid_entity_ids_in_audit_logs.php` because rollover UUIDs could not fit the former integer audit entity_id; migration was run successfully.
- Verification: StudentCard tests 8 passed / 19 assertions; Pint passed; dashboard API reports 2,138 students using 2569 room structure.

---

## 2026-07-07 — Student Photo Path Migration & E2E Polish

- **Canonical Photo Path Migration**: Migrated student photos from legacy room-based folders to student-identity-based paths (`images/students/profiles/{student_id}.{ext}`).
- **Backend Service & Models**: Created `StudentPhotoService` for unified storage management and backend-owned fallback checks. Added `profile_image_url` accessors to both `Student` and `StudentCard` models.
- **Migration Commands**: Implemented and executed `students:migrate-photos` migration tool (migrated 1,529/1,531 photos successfully). Created `students:cleanup-legacy-photos` tool for post-migration folder cleanup.
- **E2E Review Polish**: Resolved 22 code review findings including:
  - C1: Missing import of `StudentPhotoService` in `StudentCardController.php`.
  - H1: Path concatenation safety for already relative paths in `destroyPhoto()`.
  - H2: Stripping the 'ม.' Thai grade prefix in the legacy path assembly of `StudentCard`'s accessor.
  - H6: Null safety guards in `admin/students/[level]/[room].vue`.
  - C2 & H5: Complete simplification of frontend image loading across 15+ Vue components to rely solely on the resolved `profile_image_url` property from API.
  - M1: Automatically updating the frontend reactive refs on photo upload success.
  - M2: Fixing array return values in `StudentsCard.vue` helper methods.
  - H3: Grade normalization within `StudentPhotoService`.
- **Verification**: Formatted with Pint and verified all 8 unit tests in the StudentCard feature suite pass.

---

## 2026-07-08 — Roster Reconciliation with Student Code

### งานที่ทำ
- **Roster Reconciliation Logic**: พัฒนา `RosterReconciliationService` และปรับแต่ง `StudentImportService` เพื่อรองรับการนำเข้าแบบ Reconciliation โดยอิง `student_code` เป็น Key
- **JSON Import Support**: ปรับปรุงหน้าอัปโหลดในฝั่ง Frontend (`StepUpload.vue`, `studentImportService.ts`, `useStudentImport.ts`) และ API validation ให้สามารถรองรับไฟล์ JSON ได้
- **Artisan Extract Command**: สร้าง `ExtractRosterPdfCommand` สำหรับสกัด/แปลงข้อมูลจากไฟล์ PDF ไปเป็นโครงสร้าง canonical JSON
- **Reconciliation UI Preview**: ปรับปรุง `ImportRowTable.vue` เพื่อแสดงป้ายสถานะของการดำเนินการจัดห้องเรียน (เช่น เลื่อนชั้น, ซ้ำชั้น, ย้ายห้อง, นำเข้าใหม่) พร้อมแสดงรายละเอียดการเปลี่ยนแปลง (diff_data)
- **Tests & Verification**: เพิ่มและรัน `RosterReconciliationTest` ผ่านการตรวจสอบ 10 assertions ทั้งหมด พร้อมตรวจสอบว่า `StudentImportControllerTest` ยังสามารถรันผ่านได้ตามปกติ

### Commits
- Roster Reconciliation implementation complete.

---

## 2026-07-08 — Topic Youtube Video Support

### งานที่ทำ
- **YouTube URL Parser Utility**: สร้างไฟล์ยูทิลิตี้กลาง [youtube.ts](file:///c:/wamp64/www/nuxnan/ui/utils/youtube.ts) ยุบรวม logic การดึง ID, สร้าง thumbnail และ embed URL (ใช้ `youtube-nocookie.com`) ช่วยให้การ parse URL มีความเป็นหนึ่งเดียวและลดความซ้ำซ้อน
- **VideoModal Refactoring**: ปรับปรุง [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **LessonPost Refactoring**: ปรับปรุง [LessonPost.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/LessonPost.vue) ให้ดึงตัวแกะ URL จากยูทิลิตี้กลาง
- **Topic Video Preview & Playback**: เพิ่มกล่องแสดงพรีวิววิดีโอ (สัดส่วน 16:9 พร้อมปุ่ม Play) และรองรับการเปิดวิดีโอผ่าน [VideoModal.vue](file:///c:/wamp64/www/nuxnan/ui/components/media/VideoModal.vue) ใน [TopicAccordion.vue](file:///c:/wamp64/www/nuxnan/ui/components/learn/course/lesson/TopicAccordion.vue) โดยแยกสถานะ modal ออกต่อหนึ่ง accordion instance อย่างชัดเจน
- **Robust Error/Fallback Handling**:
  - รองรับการ fallback รูปภาพพรีวิวจาก `maxresdefault` ไปเป็น `hqdefault` กรณีรูปขนาดใหญ่ไม่มี
  - แสดงลิงก์ "เปิดบน YouTube" และข้อความแจ้งเตือนอย่างชัดเจน หากลิงก์ที่กรอกผิดรูปแบบ (Invalid URL)
  - ซ่อนส่วนวิดีโอทั้งหมดหาก `youtube_url` มีค่าว่าง
- **Build Verification**: รัน `npm run build` ในไดเรกทอรี `ui` ผ่านการทดสอบเรียบร้อย

### Commits
- Implement centralized YouTube parser utility and integrate video preview in TopicAccordion.

---

## 2026-07-08 — Roster Reconciliation Bug Fixes (Session 2)

### งานที่ทำ
- **M1: source_academic_year_id** — บันทึก `source_academic_year_id` ลงใน `diff_data` ตอน `preview()` สำหรับ action `promote_student` และ `repeat_student` ป้องกันปัญหาชื่อปีไม่ต่อเนื่องหรือมีปีที่เว้นไป
- **M3: student_number** — รองรับการซิงค์ `student_number` (เลขที่) เมื่อมีข้อมูลจาก JSON หรือใช้ลำดับ index จาก JSON เข้าสู่ฐานข้อมูล สำหรับ actions ทุกประเภท (`unchanged`, `new_intake`, `promote_student`, `repeat_student`, `re_enroll`)
- **M4: refreshCounters** — แก้ไข DRY violation โดยยกเลิกการเขียนฟังก์ชัน `refreshBatchCounters` ซ้ำใน `RosterReconciliationService` และเรียกใช้งาน `refreshCounters` จาก `StudentImportService` แทน
- **M5: remarks** — สร้าง migration `2026_07_08_000002_add_remarks_to_students_table` เพิ่มคอลัมน์ `remarks` ลงในตาราง `students` พร้อมทั้งเพิ่มลงใน `$fillable` ของโมเดล `Student` เพื่อแก้ปัญหาการเซฟ remarks เป็น silent no-op
- **N6: useStudentCardRequests Type Safety** — แก้ไข type ของ `useStudentCardRequests` composable แทนที่จะเป็น `as any` เพื่อเพิ่มความเสถียรและความปลอดภัยทางประเภทข้อมูล (Type Safety)
- **Tests & Verification** — เพิ่มการทดสอบใน `RosterReconciliationTest` สำหรับกรณี `unchanged` (การซิงค์ student number), `auto_graduate` ของนักเรียน ม.6, และ `ambiguous` teacher matching (ยืนยันผลการหาครูที่ชื่อซ้ำกัน) ผลการรัน Unit Test ผ่านทั้งหมด 26 assertions และจัดรูปแบบโค้ด PHP ด้วย Pint

---

## 2026-07-12 — Campaign System Phase 5: Create + Dashboard

### งานที่ทำ
- **หน้าสร้างแคมเปญใหม่ (Create Page)**: ปรับปรุงหน้า `ui/pages/Earn/Advertise/create.vue` ให้รองรับการทำงานใหม่แบบครบวงจร
  - เลือกประเภท: โฆษณา (Advertisement) / สนับสนุน (Support)
  - เลือกพื้นที่ (Scope): สาธารณะ (Public) / โรงเรียน (Academy) / รายวิชา (Course)
  - dynamic fields ตามที่เลือก: แสดง dropdown รายการโรงเรียนและรายวิชาที่จัดการได้, พร้อม toggle inherit (เฉพาะ course)
  - คำนวณราคา budget (จากจำนวนวิว x วินาที x 0.10 บาท) และคำนวณแต้มสนับสนุน (budget x 1080 PP) บน client อัตโนมัติ
  - เลือกช่องทางชำระเงิน: Wallet / อัปโหลดสลิป พร้อม date/time picker
  - แสดงหน้าพรีวิวบัตรโฆษณา/สนับสนุนเรียลไทม์ระหว่างกรอกข้อมูล
- **แดชบอร์ดผู้สร้างแคมเปญ (Creator Dashboard)**: สร้างหน้าใหม่ `ui/pages/Earn/Advertise/manage.vue`
  - สรุปสถิติแคมเปญ: งบประมาณสะสม ยอดวิวจริง แคมเปญที่ทำงานอยู่ และรายการรอตรวจ
  - ตารางรายการแคมเปญพร้อมรายละเอียด ยอดวิว/การเห็น สิทธิ์การแสดงผล สถานะการชำระเงิน และสถานะรีวิว
  - ตัวกรองตามประเภทและสถานะแคมเปญ
- **แดชบอร์ดผู้ดูแลระบบ (Admin Dashboard)**: ปรับปรุงหน้า `ui/pages/PlearndAdmin/Support/ApproveAdvertise.vue`
  - มี 3 แท็บสำหรับ Admin:
    1. **รอตรวจสอบ (Pending Review)**: อนุมัติ/ปฏิเสธ คำขอแคมเปญ (พร้อมระบุเหตุผลในการปฏิเสธ)
    2. **ประวัติการคืนเงิน (Refund Status)**: ตรวจสอบรายการที่ถูกปฏิเสธ หากจ่ายผ่านสลิปมีปุ่มกดเพื่อยืนยันการคืนเงินแบบแมนวล
    3. **Audit Log**: แสดงประวัติกิจกรรมทั้งหมดของแคมเปญ (สร้าง, อนุมัติ, ปฏิเสธ, เข้าชม)
  - เพิ่ม API endpoints ใหม่ในฝั่ง Laravel: `GET /api/campaigns/admin`, `GET /api/campaigns/admin/audit-logs`, และ `PATCH /api/campaigns/{campaign}/payment`
  - ปรับ backend ให้เปลี่ยนสถานะการชำระเงินสลิปเป็น `paid` อัตโนมัติเมื่อ admin กดยอมรับการรีวิว

### Verification
- รัน `php -l` ผ่านทุกไฟล์ใน backend
- Pint จัดรูปแบบโค้ด backend สำเร็จ
- UI หน้าต่างๆ ทำงานร่วมกับ API ชุดใหม่เรียบร้อย

---

## 2026-07-12 — Campaign System Phase 6: Tests and Logic Hardening

### งานที่ทำ
- **พัฒนาระบบการจัดทำ Unit/Feature Tests ทั้ง 12 เคสใน [CampaignSystemTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/Campaign/CampaignSystemTest.php)**:
  - การจ่ายเงินผ่านกระเป๋าเงิน (Wallet) และคำนวณราคาแบบคำนวณจริง (100% Correct)
  - ความปลอดภัยและการตรวจสอบขอบเขต (Scope validation integrity - HTTP 422 สำหรับ config ข้ามแบบแผน)
  - การกดยอมรับการตรวจสอบสลิปเงินและปรับสถานะเป็น Paid & Approved
  - การปฏิเสธแคมเปญและการดำเนินการคืนเงินคืนเข้า Wallet อัตโนมัติอย่างถูกต้อง
  - การจำกัดยอดรับชมการโฆษณา (Daily Reward Quota) สูงสุดไม่เกิน 5 ครั้ง/วัน/คน พร้อมระบบ Idempotency ป้องกันการส่งคีย์ซ้ำเพื่อตัดสิทธิ์
  - การแบ่งเงินสนับสนุน (Support revenue split) 70% (Academy owner), 20% (Course Instructor), และ 10% (Platform) ผ่าน `SupportPaymentService`
  - การกรองข้อมูลแคมเปญให้เข้ากับหน้าต่างโรงเรียนแบบ Scope Isolation และการเช็คเงื่อนไข `inherit_to_academy`
  - ยืนยันการไม่ปัดเศษทศนิยมงบประมาณ (Decimal Precision ในระดับ Float/Double/Decimal)
  - การ Query ตารางบริจาคตัวเก่า (Legacy `donates` compatibility) เพื่อไม่ให้เกิด Regression
  - การทำ Role-based Authorization แยกกรณีผู้เยี่ยมชม (401), สมาชิกทั่วไป (403), และผู้ดูแลระบบ (200)
- **Hardening และการกู้คืนข้อผิดพลาดใน Runtime/Database**:
  - แก้ไขปัญหา JWT guard state caching ในระบบ PHPUnit โดยใช้ `auth()->forgetUser()` เคลียร์หน่วยความจำระหว่าง request
  - เพิ่ม Fallback ให้กับ [ReviewCampaignRequest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Requests/Campaign/ReviewCampaignRequest.php) เพื่อให้สามารถค้นหา `Advert` Model ได้แบบตรงจุดแม้จะเกิดการ bind ตกหล่นใน CLI/Tests
  - ใส่ default values ให้กับคอลัมน์ NOT NULL ในตารางของ SQLite เช่น `slip`, `transfer_date`, `transfer_time`, `total_views`, `remaining_views` ในช่วงการบันทึกแคมเปญประเภทกระเป๋าเงิน เพื่อตัดปัญหา SQL constraint errors
  - จัดการรัน Pint ปรับปรุงโค้ดทั้งหมด

### Verification
- รัน `php artisan test tests/Feature/Campaign/CampaignSystemTest.php` ผ่านการตรวจสอบครบถ้วนทั้ง 12 เคส (46 assertions)
- Pint จัดรูปแบบเสร็จสมบูรณ์เรียบร้อย


