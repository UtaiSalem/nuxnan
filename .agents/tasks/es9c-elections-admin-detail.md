# E-S9c — หน้ารายละเอียดการเลือกตั้ง + 3 แท็บแรก + เก็บงานค้าง

> เขียนโดย claude 2026-08-23 · เมนู #25 · shard ที่สามของ E-S9
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§11.4** (ตาราง 6 แท็บ) · **§11.7** (mobile-first) · **§11.10** (งานค้าง C1–C5) · **§7.2** (บทเรียน: ยิงจริงก่อนเชื่อ)

## บริบท

E-S9a (backend) และ E-S9b (หน้ารายการ + เมนู) ปิดแล้ว (`b26e58e9`, `879d9843`)
งานนี้คือ **หน้ารายละเอียดการเลือกตั้ง** ซึ่งเป็นที่ที่แอดมินทำงานจริงเกือบทั้งหมด

**ในขอบเขต:** โครงหน้า `[id].vue` + แท็บ **ภาพรวม · พรรค · บัญชีผู้มีสิทธิ์** + งานค้าง C1–C5 + บั๊ก B1 ข้างล่าง
**นอกขอบเขต:** แท็บ หน่วย/ผล/บันทึก (E-S9d) — แต่ต้องทำโครงแท็บให้รองรับไว้แล้ว

---

## ส่วนที่ 1 — โครงหน้า `ui/pages/academies/[name]/admin/elections/[id].vue`

- ไฟล์เดี่ยว ไม่ต้องมี `NuxtPage` · **root node เดียว** · `definePageMeta({ layout: 'main' })`
- `academyId` ใช้ `inject<Ref<number|null>>('academyId', ref(null))` ตามที่ `admin.vue` provide ไว้
- 🔴 **เริ่มงานด้วย `watch(academyId, …, { immediate: true })` ไม่ใช่ `onMounted`** — `admin.vue` (พ่อ) เซ็ต `academyId`
  ใน `onMounted` ของตัวเอง ซึ่งรัน **หลัง** `onMounted` ของหน้าลูก · หน้าที่ใช้ `onMounted` เฉย ๆ จะไม่เริ่มทำงาน
  (นี่คือข้อ C2 ที่ทำให้ด่านสิทธิ์ของหน้ารายการไม่เคยทำงาน ดู `students/index.vue` เป็นตัวอย่างที่ถูก)
- ด่านสิทธิ์: `isAdmin.value || can('elections.view') || can('elections.manage')` (ตรงกับเมนู)
  · ปุ่มที่เปลี่ยนแปลงข้อมูลทุกปุ่มต้องซ่อน/disable เมื่อไม่มี `elections.manage`
- **แถบแท็บ 6 อัน** (ภาพรวม · พรรค · บัญชีผู้มีสิทธิ์ · หน่วย · ผล · บันทึก) — บนจอ 375px ต้องเลื่อนแนวนอนได้
  ในกล่องของตัวเอง · แท็บที่ยังไม่ทำ (หน่วย/ผล/บันทึก) ให้ขึ้นข้อความ "อยู่ระหว่างพัฒนา" ไปก่อน **อย่าเอาออกจากแถบ**
- แท็บที่เลือกอยู่ผูกกับ query string (`?tab=parties`) เพื่อให้ refresh แล้วอยู่ที่เดิม
- แยกเนื้อแต่ละแท็บเป็น component ใน `ui/components/academy/elections/`
  🔴 **ชื่อยาวและไม่ซ้ำ** (`ElectionOverviewTab`, `ElectionPartiesTab`, `ElectionVoterRollTab`)
  ชื่อสั้นทำให้ auto-import ของ Nuxt หาไม่เจอแล้ว component หายเงียบ ๆ (บทเรียน S-S4)
- **หนึ่ง statement ต่อบรรทัด** — ห้ามเขียนบรรทัดยาว 1,000+ ตัวอักษรแบบที่ทำมาสองรอบ (C5)

## ส่วนที่ 2 — แท็บ "ภาพรวม"

ข้อมูลจาก `GET /elections/{e}` (`getElection`) และ `GET /elections/{e}/turnout` (`getTurnout`)

- หัวเรื่อง: ชื่อ · สถานะ · ระดับ (`education_level`: null/1/2) · ปีการศึกษา · ช่วงรับสมัคร/ลงคะแนน · `ballot_ttl_seconds` · `allow_abstain`
- การ์ดตัวเลข: `voters_count` · `approved_parties_count` · `receipts_cast_count` และ turnout (`voted` / `total` / `percentage`)
- ปุ่มแก้ไข (เปิด `ElectionFormModal` ตัวเดิมในโหมดแก้ไข) และปุ่มลบ (ถามยืนยัน)
- **แถบ state machine** — `draft → nomination → campaign → voting → closed → published` เดินหน้าทีละขั้น
  · `cancelled` เข้าได้จากทุกสถานะยกเว้น `published` · แสดง **ปุ่มเลื่อนขั้นถัดไปปุ่มเดียว** + ปุ่มยกเลิกการเลือกตั้ง
  · 🔴 **ต้อง disable พร้อมบอกเหตุผลล่วงหน้า** ไม่ใช่รอ 422: เข้า `voting` ไม่ได้ถ้า **ยังไม่ล็อกบัญชีผู้มีสิทธิ์**
  (`voter_roll_locked_at` เป็น null) หรือ **ยังไม่มีพรรคที่อนุมัติ** (`approved_parties_count` = 0)
- `turnout` คืน `voted` `total` `percentage` `by_grade_level[]` `by_station[]` — **ไม่มีข้อมูลรายพรรคโดยเจตนา**
  ห้ามพยายามคำนวณหรือแสดงคะแนนรายพรรคก่อนปิดหีบเด็ดขาด

## ส่วนที่ 3 — แท็บ "พรรค"

เพิ่มใน `useElections.ts`: `listParties(a, e)` · `approveParty(a, e, p, number?)` · `rejectParty(a, e, p, review_note)` · `withdrawParty(a, e, p)`

| endpoint | หมายเหตุ |
|---|---|
| `GET .../elections/{e}/parties` | คืนพรรคพร้อม `members.user` · เรียงพรรคที่ยังไม่มีเบอร์ไว้ท้าย |
| `POST .../parties/{p}/approve` | body `number` (nullable — ไม่ส่ง = ระบบหาเบอร์ว่างให้) |
| `POST .../parties/{p}/reject` | body `review_note` **บังคับ** |
| `POST .../parties/{p}/withdraw` | ไม่มี body |

- ตารางพรรค: เบอร์ · ชื่อ · สโลแกน · โลโก้ · สถานะ (`pending`/`approved`/`rejected`/`withdrawn`) · รายชื่อทีมพร้อมบทบาท
  (`leader`/`deputy`/`secretary`/`treasurer`/`member`)
- ปุ่มอนุมัติ (เลือกกรอกเบอร์เองหรือปล่อยว่าง) · ปฏิเสธ (บังคับกรอกเหตุผล) · ถอนตัว (ถามยืนยัน)
- ทำงานได้เฉพาะช่วง `nomination`/`campaign` ตามกฎ backend — นอกช่วงให้ disable พร้อมบอกเหตุผล
- ⚠️ **มีข้อค้างที่ยังไม่ได้ตัดสิน** (§10 E-S3): เบอร์ของพรรคที่ถอนตัวถูกปล่อยกลับมาแจกใหม่ได้
  **งานนี้ห้ามเปลี่ยนพฤติกรรมนั้น** แค่แสดงตามที่ระบบเป็นอยู่

## ส่วนที่ 4 — แท็บ "บัญชีผู้มีสิทธิ์"

เพิ่มใน `useElections.ts`: `lockVoterRoll(a, e)` · `listVoters(a, e, params)` · `voterRollStats(a, e)` · `setMemberEducationLevel(a, memberId, level)`

| endpoint | หมายเหตุ |
|---|---|
| `POST .../voter-roll/lock` | คืน 10 ตัวเลข: `total` `students` `staff` `without_member_code` `without_student_card` `duplicate_member_rows` `skipped_no_user_account` `skipped_inactive_student` `skipped_other_level` `staff_without_level` |
| `GET .../voter-roll` | paginator (`per_page` ค่าเริ่มต้น 50) · filter `voter_type` `grade_level` `search` `missing=member_code\|student_card` |
| `GET .../voter-roll/stats` | `by_voter_type[]` · `by_grade_level[]` |
| `PUT /api/academies/{a}/members/{member}/education-level` | body `education_level` = `null`/`1`/`2` · **ใช้ได้เฉพาะบุคลากร** (แถวที่มี `student_id` จะได้ 422) |

- ปุ่ม "ล็อกบัญชีผู้มีสิทธิ์" (ถามยืนยันก่อน · ล็อกซ้ำได้ idempotent) แล้ว **แสดงผลสรุปทั้ง 10 ตัวเลขเต็ม ๆ** ไม่ใช่แค่ยอดรวม
- 🔴 **คำเตือนสองข้อจาก §9.1 ต้องเห็นชัดก่อนวันจริง:**
  - `staff_without_level` > 0 → "บุคลากร N คนยังไม่ได้ระบุระดับ ถ้าล็อกตอนนี้จะไม่มีครูลงคะแนนในการเลือกตั้งแยกระดับเลย"
    พร้อมช่องตั้งระดับให้บุคลากรได้จากในตาราง (ตัวเลขจริงของโรงเรียนนี้คือ 131 คน)
  - `without_student_card` > 0 → "นักเรียน N คนไม่มีบัตร ต้องใช้ช่องพิมพ์รหัสสมาชิกที่หน่วยเลือกตั้ง"
    (ประถม 449 คนไม่มีบัตรเลยสักใบ)
- ตารางผู้มีสิทธิ์ + filter ครบตามตาราง + pagination (3,000 แถว — **ห้ามโหลดทั้งหมดมาทีเดียว**)

## ส่วนที่ 5 — บั๊กและงานค้างที่ต้องเก็บไปด้วย

**B1 (ใหม่ · backend) — filter `missing=student_card` เทียบคอลัมน์ผิดตาราง**
`ElectionVoterRollController::index()` เขียนว่า
`whereColumn('student_cards.student_id', 'election_voters.user_id')`
แต่ `election_voters.user_id` คือ `users.id` ส่วน `student_cards.student_id` คือ `students.id` — คนละตารางกัน
เทียบกับตัวนับใน `ElectionVoterRollService::lock()` ที่ทำถูก (ไล่ผ่าน `academy_members.id = election_voters.academy_member_id`
แล้วค่อย `student_cards.student_id = card_members.student_id`)
→ **แก้ให้ตรงกับนิยามใน `lock()`** และ **เขียนเทสต์ที่ยืนยันว่า จำนวนแถวจาก filter = `without_student_card` ที่ `lock()` รายงาน**
(ถ้าสองค่านี้ไม่ตรงกัน แอดมินจะเห็นตัวเลขขัดกันเองบนหน้าจอเดียวกัน)

**C1 (backend)** — `ElectionController::index()` และ `show()` ไม่ eager-load ปีการศึกษา → ช่องปีขึ้น `-` เสมอ
ยืนยันจาก payload จริงแล้ว · แก้ด้วย `with('academicYear')` (ตรวจชื่อความสัมพันธ์ในโมเดล `Election` ก่อน) แล้วให้หน้าอ่านคีย์ที่ส่งมาจริง

**C2 (ui)** — `admin/elections/index.vue` เปลี่ยนจาก `onMounted` เป็น `watch(academyId, …, { immediate: true })`
ให้ด่านสิทธิ์ทำงานจริง (รายละเอียดในส่วนที่ 1)

**C3 (ui)** — ลบกิ่งตาย `election.name ||` ใน `index.vue` (payload มีแค่ `title`)

**C5 (ui)** — จัดบรรทัด `index.vue` ใหม่ให้อ่านได้ หนึ่ง statement ต่อบรรทัด

---

## กติกา mobile-first (บังคับ)

- class ไม่มี prefix = มือถือ แล้วค่อย `sm:` `md:` `lg:` · **ห้าม desktop-first**
- ห้าม `hidden` ซ่อนข้อมูลสำคัญบนมือถือ — จัดวางใหม่แทน
- touch target ≥ 44px ทุกปุ่ม (อนุมัติ/ปฏิเสธ/ล็อกบัญชี/เลื่อนสถานะ)
- แถว flex: ฝั่งห้ามบีบ `flex-shrink-0 whitespace-nowrap` · ฝั่งข้อความ `min-w-0 flex-1 break-words`
- ตารางพรรค/ผู้มีสิทธิ์อยู่ในกล่อง `overflow-x-auto` ของตัวเอง **ห้ามให้ทั้งหน้าเลื่อนแนวนอน**
- แถบแท็บ 6 อันต้องเลื่อนแนวนอนได้ในแถบของตัวเองที่ 375px
- **ตรวจที่ 375px ก่อน** แล้วค่อย 768 / 1280 · ใช้สกิล `hopeui-port` ดึงโครง markup จาก `hopa/` มาก่อน

## Acceptance criteria

1. `./vendor/bin/pint` ผ่าน · `php artisan test --filter Election` เขียวทั้งชุด (**ฐาน 128 ห้ามลดลง**) + เทสต์ใหม่ของ B1
2. **ยิงทุก endpoint ที่หน้าใหม่เรียก ผ่าน HTTP จริงแบบล็อกอินแล้ว** (feature test หรือ tinker ที่สร้าง token ก็ได้)
   แล้ว **แปะ payload จริง** มาพร้อมบอกว่าหน้าอ่านคีย์ไหนบ้าง — รอบที่แล้วยิงแบบไม่ล็อกอินได้ 401 ซึ่งพิสูจน์อะไรไม่ได้
   endpoint ที่ต้องยิง: `GET /elections/{e}` · `turnout` · `parties` · `parties/{p}/approve` · `voter-roll/lock` · `voter-roll` (ทั้งแบบมี filter `missing=student_card`) · `voter-roll/stats`
3. บอกให้ชัดว่า **ปุ่มเลื่อนสถานะถูก disable ตอนไหนบ้าง และเช็คจากฟิลด์อะไร**
4. ยืนยันว่า B1 แก้แล้วโดยแนบตัวเลขสองฝั่งที่ตรงกัน (filter count = `without_student_card`)
5. รายชื่อไฟล์ที่แตะทั้งหมด

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ · **ห้าม `migrate:fresh`** · ห้ามแตะ `.env` · ห้าม refactor นอกขอบเขต
- **ห้ามแตะไฟล์เหล่านี้** (เจ้าของโปรเจคกำลังแก้ค้างอยู่): `ui/components/Modal.vue` ·
  `ui/components/learn/course/groups/ImportFromClassroomsModal.vue` · `ui/components/learn/course/groups/SyncClassroomModal.vue`
- ห้ามแก้ 8 ฟังก์ชันเดิมฝั่งหน่วยเลือกตั้งใน `useElections.ts`
- ห้ามแตะพฤติกรรมบัตรลับ และห้ามแสดงคะแนนรายพรรคก่อนปิดหีบ
- commit เป็นชุดเล็ก · ถ้า commit ไม่ได้เพราะ `.git/index.lock` ให้รายงานแล้วทิ้งไว้ใน working tree **อย่าลบไฟล์ lock**
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่เทสต์ยังแดง**
