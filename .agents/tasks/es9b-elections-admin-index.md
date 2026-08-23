# E-S9b — composable ฝั่งแอดมิน + หน้ารายการการเลือกตั้ง + เมนู

> เขียนโดย claude 2026-08-23 · เมนู #25 การเลือกตั้งสภานักเรียน · shard ที่สองของ E-S9
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§11** (สเปกหน้าแอดมินเต็ม) · **§11.5** (หน้า index) · **§11.6** (เมนู) · **§11.7** (mobile-first) · **§7.2** (บทเรียน: อย่าเชื่อว่า endpoint ทำงานจนกว่าจะยิงจริง)

## บริบทและขอบเขต

backend ของเมนูนี้ครบตั้งแต่ E-S1–E-S7 และหน้าหน่วยเลือกตั้ง (E-S8) ใช้งานได้จริงแล้ว
งานนี้คือ **ประตูทางเข้าฝั่งแอดมิน** — ทำให้แอดมินเห็นรายการการเลือกตั้งและสร้างใหม่ได้จากหน้าจอ

⚠️ **มีงาน E-S9a (backend) รันคู่ขนานอยู่ตอนนี้** — มันแตะเฉพาะ `api/nuxnanravel/` เท่านั้น
งานนี้ **ห้ามแตะไฟล์ใด ๆ ใน `api/nuxnanravel/`** เด็ดขาด แตะเฉพาะ `ui/` และ **ห้าม `git commit`**
(ทิ้งงานไว้ใน working tree แล้วรายงาน — claude จะตรวจและ commit ให้เอง เพื่อไม่ให้ชน index กับอีกงาน)

**นอกขอบเขต:** หน้า `[id].vue` และ 6 แท็บ (เป็น E-S9c/E-S9d) · หน้าสมัครพรรค/ผลสาธารณะ (E-S10)

---

## ส่วนที่ 1 — ขยาย `ui/composables/useElections.ts`

ตอนนี้ไฟล์นี้มีแต่ฟังก์ชันฝั่ง **หน่วยเลือกตั้ง** (`openStation` `closeStation` `stationProgress` `lookupVoter`
`searchVoters` `issueBallot` `voidBallot` `castBallot`) — **ห้ามแก้ลายเซ็นของ 8 ตัวนี้** เพราะหน้าหน่วยเลือกตั้งใช้อยู่จริงและเพิ่งผ่านการตรวจ

เพิ่มฟังก์ชันฝั่งแอดมิน (ทุกตัวเรียกผ่าน `api.call` ตามรูปเดิมของไฟล์):

| ฟังก์ชัน | endpoint |
|---|---|
| `listElections(a, params?)` | `GET /api/academies/{a}/elections` (รองรับ `status`, `academic_year_id`, `per_page`, `page`) |
| `getElection(a, e)` | `GET .../elections/{e}` |
| `createElection(a, payload)` | `POST .../elections` |
| `updateElection(a, e, payload)` | `PUT .../elections/{e}` |
| `deleteElection(a, e)` | `DELETE .../elections/{e}` |
| `transitionStatus(a, e, status)` | `POST .../elections/{e}/status` body `{ status }` |
| `getTurnout(a, e)` | `GET .../elections/{e}/turnout` |

(ที่เหลือ — พรรค / บัญชีผู้มีสิทธิ์ / หน่วย / ผล / บันทึก — เป็นของ E-S9c/E-S9d **ยังไม่ต้องเพิ่มในงานนี้**)

## ส่วนที่ 2 — หน้าใหม่ `ui/pages/academies/[name]/admin/elections/index.vue`

**ลอกโครงการโหลด/ด่านสิทธิ์จาก `ui/pages/academies/[name]/admin/events/index.vue`** ซึ่งเป็นหน้าแอดมินที่ใช้งานจริงอยู่:
`definePageMeta({ layout: 'main' })` → `api.get('/api/academies/{name}')` → `academyId` → `fetchMyRole()` →
ถ้าไม่ผ่านด่านให้ `navigateTo('/academies/{name}')` → แล้วค่อยโหลดข้อมูล

🔴 **ด่านสิทธิ์ต้องเป็น `isAdmin.value || can('elections.view') || can('elections.manage')`
และต้องตรงกับเงื่อนไข `show` ของเมนูในส่วนที่ 3 เป๊ะ ๆ** — บทเรียนกีฬาสี: เมนูกับ guard ไม่ตรงกัน = แอดมินเข้าหน้าได้แต่มองไม่เห็นเมนู

**เนื้อหาหน้า:**
- หัวเรื่อง + ปุ่ม "สร้างการเลือกตั้ง" (แสดงเมื่อ `can('elections.manage')` เท่านั้น)
- filter ตามสถานะ (7 สถานะ) — ค่าที่ backend ใช้คือ `draft` `nomination` `campaign` `voting` `closed` `published` `cancelled`
  ป้ายภาษาไทยที่ต้องใช้: ร่าง · รับสมัคร · หาเสียง · ลงคะแนน · ปิดหีบ · ประกาศผลแล้ว · ยกเลิก
- รายการการเลือกตั้ง แต่ละแถว/การ์ดแสดง: ชื่อ · ป้ายสถานะ (สีต่างกันตามสถานะ) · ปีการศึกษา ·
  **ระดับ** จากคอลัมน์ `education_level` (`null` = ทั้งโรงเรียน · `1` = ประถม · `2` = มัธยม) ·
  `voters_count` (ผู้มีสิทธิ์) · `approved_parties_count` (พรรคที่อนุมัติ) · `receipts_cast_count` (ลงคะแนนแล้ว)
  → กดแล้วไป `/academies/{name}/admin/elections/{id}` (หน้านั้นยังไม่มีในงานนี้ ลิงก์ไว้ก่อนได้)
- empty state พร้อมปุ่มสร้าง เมื่อยังไม่มีการเลือกตั้งเลย (สถานะจริงตอนนี้: ตาราง `elections` มี **0 แถว**)
- ตัวโหลด/สถานะกำลังโหลด และการรองรับ paginator (endpoint คืน paginator: อ่าน `data.data` และ `data.total`)

**Modal สร้างการเลือกตั้ง** (`ui/components/academy/elections/ElectionFormModal.vue`) ฟิลด์ตาม FormRequest จริง:
`title` (บังคับ ≤150) · `description` · `academic_year_id` · `education_level` (`null`/`1`/`2`) ·
`nomination_opens_at` / `nomination_closes_at` · `voting_opens_at` / `voting_closes_at` ·
`allow_abstain` (ค่าเริ่มต้น true) · `ballot_ttl_seconds` (ค่าเริ่มต้น 180)
— แสดงข้อความ error ที่ backend ส่งกลับ (มี message ภาษาไทยครบใน `StoreElectionRequest::messages()` อยู่แล้ว) อย่าเขียนข้อความเองซ้ำ

🔴 **ตั้งชื่อ component ให้ยาวและไม่ซ้ำใคร** (`ElectionFormModal` ไม่ใช่ `FormModal`) — ชื่อสั้นทำให้ auto-import ของ Nuxt
หาไม่เจอ/ชนกัน แล้ว **component หายเงียบ ๆ โดยไม่มี error** (บทเรียน S-S4 เมนู #27)
และทุกหน้าต้องมี **root node เดียว**

## ส่วนที่ 3 — เมนูใน `ui/pages/academies/[name]/admin.vue`

เพิ่มในกลุ่ม **"กิจกรรม & การสื่อสาร"** ต่อจากรายการ "คณะสี (กีฬาสี)":

```
name: 'การเลือกตั้งสภานักเรียน'
icon: 'fluent:vote-24-regular'
to: `/academies/${academyName.value}/admin/elections`
show: isAdmin.value || can('elections.view') || can('elections.manage')
```

---

## กติกา mobile-first (บังคับ)

- class ที่ไม่มี prefix = มือถือ แล้วค่อย `sm:` `md:` `lg:` · **ห้าม desktop-first**
- ห้าม `hidden` ซ่อนข้อมูลสำคัญบนมือถือ — จัดวางใหม่ (ซ้อนเป็นแถว/ย้ายลงล่าง) แทนการตัดทิ้ง
- touch target ≥ 44px (`min-h-[44px]`) สำหรับปุ่มและแถวที่กดได้
- แถว flex: ฝั่งที่ห้ามถูกบีบใส่ `flex-shrink-0 whitespace-nowrap` · ฝั่งข้อความใส่ `min-w-0 flex-1 break-words`
  (ชื่อการเลือกตั้งเป็นภาษาไทยไม่มีช่องว่าง จะแตกเป็นแนวตั้งถ้าโดนบีบ)
- ถ้าทำเป็นตาราง ต้องอยู่ในกล่อง `overflow-x-auto` ของตัวเอง **ห้ามให้ทั้งหน้าเลื่อนแนวนอน**
  (หรือจะใช้การ์ดบนมือถือแล้วสลับเป็นตารางที่ `md:` ก็ได้ — เลือกอย่างใดอย่างหนึ่งให้สม่ำเสมอ)
- padding/ตัวอักษรไล่จากเล็กไปใหญ่ เช่น `p-3 sm:p-6`, `text-sm sm:text-base`
- **ตรวจที่ 375px ก่อน** แล้วค่อย 768 / 1280
- ใช้สกิล `hopeui-port` ดึงโครง markup จาก `hopa/` มาเป็นต้นแบบก่อนเขียนเอง (markup ของ HopeUI เป็น desktop-first
  ให้เอาโครงสร้าง/spacing มา แล้ว**เขียน breakpoint ใหม่เป็น mobile-first เสมอ**)

## Acceptance criteria

1. `npx nuxi typecheck` หรืออย่างน้อย `npx tsc --noEmit` ในโฟลเดอร์ `ui/` ไม่มี error ใหม่จากไฟล์ที่แตะ
   (**ห้ามรัน `npm run build`** — เจ้าของโปรเจครันเอง)
2. **ยิง endpoint จริงอย่างน้อยหนึ่งครั้งก่อนบอกว่าเสร็จ** — เช่นด้วย `php artisan tinker` หรือ curl ที่ `GET /api/academies/1/elections`
   แล้ว **แปะ payload จริงที่ได้กลับมา** พร้อมบอกว่าโค้ดในหน้าอ่านคีย์ไหนจาก payload นั้นบ้าง
   (บทเรียน §7.1/§7.2: หน้าที่เขียนจากสเปกอย่างเดียวโดยไม่เคยเห็น payload จริง ผ่าน build ได้แต่ใช้งานไม่ได้)
3. ระบุมาให้ชัดว่าเงื่อนไข `show` ของเมนู กับด่านสิทธิ์ในหน้า **เหมือนกันทุกตัวอักษร**
4. บอกรายชื่อไฟล์ที่แตะทั้งหมด — ต้องไม่มีไฟล์ใดอยู่นอก `ui/`

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ
- **ห้ามแตะ `api/nuxnanravel/` ทุกไฟล์** (มีงาน backend รันคู่ขนานอยู่)
- **ห้าม `git commit` และห้าม `git add`** — ทิ้งงานไว้ใน working tree
- ห้าม refactor นอกขอบเขต · ห้ามแก้ 8 ฟังก์ชันเดิมใน `useElections.ts`
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่ยังไม่ได้ทำ**
