# E-S9c2 — ทำ 3 แท็บของหน้าการเลือกตั้งให้ใช้งานได้จริง

> เขียนโดย claude 2026-08-23 · เมนู #25 · งานต่อจาก E-S9c
> อ่านประกอบ: `.agents/school-admin/25-elections.md` **§11.11** (รายการที่ยังไม่ครบ · เป็นที่มาของงานนี้ทั้งหมด) · **§11.4** (ตาราง 6 แท็บ) · **§9.1** (คำเตือนก่อนวันเลือกตั้งจริง) · **§11.7** (mobile-first)

## บริบท

E-S9c วางโครงหน้า `[id].vue` ไว้ถูกแล้ว (แท็บผูก `?tab=` · `watch(academyId, …, { immediate: true })` · แถบแท็บเลื่อนได้)
และ backend ผ่านครบ (commit `aef62f32` + `9c2af0df`) — **งานนี้คือเติมเนื้อในสามแท็บให้ใช้งานได้จริง**
ทุกข้อข้างล่างคือผลตรวจที่ยืนยันแล้ว ไม่ใช่การออกแบบใหม่

**ไฟล์หลักที่ต้องแตะ:**
`ui/components/academy/elections/ElectionVoterRollTab.vue` · `ElectionPartiesTab.vue` · `ElectionOverviewTab.vue` ·
`ElectionFormModal.vue` · `ui/pages/academies/[name]/admin/elections/[id].vue` · `index.vue` ·
`ui/composables/useElections.ts` · และ backend เล็กน้อย 1 จุด (F3)

---

## F1 🔴 filter/pagination ของแท็บบัญชีผู้มีสิทธิ์ไม่ทำงานเลย

`ElectionVoterRollTab.vue` เขียนว่า

```ts
watch(() => [props.academyId, props.electionId, page, search, missing], () => { … }, { immediate: true })
```

ใน getter อ้างถึง **ตัว ref เปล่า ๆ ไม่ได้อ่าน `.value`** → Vue ไม่ track ทั้ง `page`, `search`, `missing`
**กดปุ่ม "ถัดไป" หรือพิมพ์ค้นหาแล้วไม่มีอะไรเกิดขึ้น** (ตาราง 3,000 แถวจึงดูได้แค่หน้าแรก)

แก้เป็นรูปที่ track จริง เช่น `watch([() => props.academyId, () => props.electionId, page, search, missing], …)`
เพิ่ม **debounce ช่องค้นหา** (~300ms) และ **กลับไปหน้า 1 เมื่อ filter เปลี่ยน**
ปุ่ม "ถัดไป" ต้อง disable เมื่อถึงหน้าสุดท้าย (ใช้ `last_page`/`total` จาก paginator)

## F2 🔴 สรุปการล็อกต้องครบ 10 ตัวเลข + คำเตือนสองข้อของ §9.1

`POST /voter-roll/lock` คืน 10 คีย์ (ยืนยันจาก HTTP จริงแล้ว):
`total` `students` `staff` `without_member_code` `without_student_card` `duplicate_member_rows`
`skipped_no_user_account` `skipped_inactive_student` `skipped_other_level` `staff_without_level`

ตอนนี้หน้าแสดงแค่ 3 ตัวเป็นบรรทัดเดียว → ต้องแสดง **ครบทั้ง 10** (การ์ด/ตารางเล็กก็ได้ ขอให้อ่านออกบนมือถือ)
พร้อม **คำเตือนที่บอกผลที่ตามมา ไม่ใช่แค่ตัวเลข**:

- `staff_without_level > 0` → "บุคลากร N คนยังไม่ได้ระบุระดับ — ถ้าล็อกบัญชีตอนนี้ การเลือกตั้งแยกประถม/มัธยมจะไม่มีครูลงคะแนนเลยสักคน"
  (ตัวเลขจริงของโรงเรียนนี้คือ 131 คน) พร้อมลิงก์/ปุ่มไปกรองเฉพาะบุคลากรที่ยังไม่มีระดับ
- `without_student_card > 0` → "นักเรียน N คนไม่มีบัตรนักเรียน — ที่หน่วยเลือกตั้งต้องใช้ช่องพิมพ์รหัสสมาชิกแทนการสแกน"
  (ประถม 449 คนไม่มีบัตรเลยสักใบ) พร้อมปุ่มกรอง `missing=student_card`

ปุ่มล็อกต้อง **ถามยืนยันก่อน** และรองรับการกดซ้ำ (backend idempotent) — อย่า disable ถาวรหลังล็อกครั้งแรก
แต่ให้บอกสถานะว่าล็อกเมื่อไร (`voter_roll_locked_at`)

## F3 🔴 ช่องตั้งระดับบุคลากรไม่รู้ค่าปัจจุบัน (ต้องแก้ backend ด้วย)

ยิง `GET /voter-roll` จริงแล้ว แถวมี `academy_member_id` (ใช้ตั้งค่าได้) **แต่ไม่มี `education_level`**
→ `<select :value="row.education_level ?? ''">` เป็นค่าว่างเสมอแม้ตั้งไปแล้ว · แอดมินไล่ตั้งระดับให้ครู 131 คนไม่ได้จริง

**แก้ที่ `ElectionVoterRollController::index()`** — ส่ง `education_level` ของ `academy_members` มาด้วย
(เช่น `leftJoin` แล้ว select `election_voters.*` + `academy_members.education_level`) โดย:
- **รูปร่าง paginator เดิมต้องไม่เปลี่ยน** และคีย์เดิมทุกตัวต้องอยู่ครบ
- ต้องไม่ทำให้ filter `missing=…` ที่เพิ่งแก้ไปพัง (มีเทสต์ผูก `without_student_card` อยู่ **ห้ามให้แดง**)
- เพิ่มเทสต์: ตั้งระดับให้บุคลากรผ่าน `PUT /members/{member}/education-level` แล้ว `GET /voter-roll` ต้องเห็นค่าที่ตั้ง

## F4 ปุ่ม "แก้ไขข้อมูล" กดแล้วไม่มีอะไรเกิดขึ้น

`ElectionOverviewTab` `emit('edit')` แต่ `[id].vue` ไม่ได้ฟัง `@edit`
→ ให้ `[id].vue` ฟังแล้วเปิด `ElectionFormModal` **ในโหมดแก้ไข** (ตอนนี้ modal รองรับแต่การสร้าง
ต้องเพิ่ม prop รับค่าเดิม + เรียก `updateElection` แทน `createElection` + refresh หน้าหลังบันทึก)

## F5 ไม่มีปุ่มยกเลิกการเลือกตั้ง

เพิ่มปุ่ม "ยกเลิกการเลือกตั้ง" (ถามยืนยัน) เรียก `transitionStatus(a, e, 'cancelled')`
· ใช้ได้ทุกสถานะ **ยกเว้น `published`** และยกเว้นเมื่อยกเลิกไปแล้ว

## F6 แท็บภาพรวมยังแสดงข้อมูลไม่ครบ

เพิ่ม: ช่วงรับสมัคร (`nomination_opens_at`/`nomination_closes_at`) · ช่วงลงคะแนน (`voting_opens_at`/`voting_closes_at`) ·
`ballot_ttl_seconds` (แสดงเป็น "อายุบัตร N วินาที") · `allow_abstain` (มี/ไม่มีช่อง "ไม่ประสงค์ลงคะแนน") ·
ระดับ (`education_level`: null/1/2) · และ turnout แยกตามชั้น/ตามหน่วย (`by_grade_level` / `by_station`)

## F7 แท็บพรรค

- ช่องเหตุผลปฏิเสธใช้ `note` **ก้อนเดียวร่วมกันทุกแถว** → แยกเป็นรายพรรค (เก็บเป็น map ตาม `party.id` หรือทำใน modal)
- เพิ่ม **ช่องกรอกเบอร์ตอนอนุมัติ** (ปล่อยว่าง = ให้ระบบหาเบอร์ว่างให้ · กรอกเอง = ส่ง `number`)
- แสดง `review_note` ของพรรคที่ถูกปฏิเสธ และ `reviewed_at`
- ⚠️ **ห้ามเปลี่ยนพฤติกรรมเบอร์ของพรรคที่ถอนตัว** (ยังเป็นข้อค้างที่เจ้าของโปรเจคต้องตัดสิน §10 E-S3)

## F8 + C2 ด่านสิทธิ์

- `[id].vue` ยังไม่ redirect คนที่ไม่มีสิทธิ์ → เพิ่มด่าน `isAdmin.value || can('elections.view') || can('elections.manage')`
  ให้เหมือนเมนู (ทำในบล็อก `watch(academyId, …, { immediate: true })` ที่มีอยู่แล้ว)
- `admin/elections/index.vue` ยังเรียก `initialize()` จาก `onMounted` ซึ่งรัน**ก่อน** `admin.vue` (พ่อ) เซ็ต `academyId`
  → ด่านสิทธิ์ไม่เคยทำงาน · เปลี่ยนเป็น `watch(academyId, …, { immediate: true })` เหมือนที่ `[id].vue` และ `students/index.vue` ทำ

## C3 + C5 เก็บกวาด

- ลบกิ่งตาย `election.name ||` ใน `index.vue` (payload จริงมีแค่ `title`)
- **จัดบรรทัดใหม่ทั้ง 4 ไฟล์ที่แตะ (index.vue + 3 แท็บ): หนึ่ง statement ต่อบรรทัด**
  ตอนนี้ทั้ง template ของแต่ละไฟล์อยู่ในบรรทัดเดียวยาวหลักพันตัวอักษร ซึ่งสเปกห้ามมาตั้งแต่ §11.1 และย้ำใน E-S9c แล้ว
  **นี่เป็นรอบที่สาม — รอบนี้ต้องแก้จริง**

---

## กติกา mobile-first (บังคับ · ตรวจที่ 375px ก่อน)

- class ไม่มี prefix = มือถือ แล้วค่อย `sm:` `md:` `lg:` · ห้าม desktop-first
- ห้าม `hidden` ซ่อนข้อมูลสำคัญ — การ์ด 10 ตัวเลขบนมือถือให้เรียง 2 คอลัมน์แล้วค่อยขยายที่ `sm:`/`lg:`
- touch target ≥ 44px ทุกปุ่ม/ทุก select
- ตารางพรรค/ผู้มีสิทธิ์อยู่ในกล่อง `overflow-x-auto` ของตัวเอง · ห้ามทั้งหน้าเลื่อนแนวนอน
- แถวข้อความ `min-w-0 flex-1 break-words` · ฝั่งห้ามบีบ `flex-shrink-0 whitespace-nowrap`

## Acceptance criteria

1. `./vendor/bin/pint` ผ่าน · `php artisan test --filter Election` เขียวทั้งชุด (**ฐาน 129 ห้ามลดลง**) + เทสต์ใหม่ของ F3
2. **พิสูจน์ F1 ว่าแก้แล้วจริง** — บอกมาว่าเปลี่ยน watch เป็นรูปไหน และอธิบายว่าทำไมของเดิมไม่ track
3. **พิสูจน์ F3** — แปะผลยิง HTTP จริง: ตั้งระดับให้บุคลากรแล้ว `GET /voter-roll` คืน `education_level` ตามที่ตั้ง
4. บอกให้ชัดว่าสรุปการล็อกแสดงกี่ตัวเลข (ต้องเป็น 10) และข้อความเตือนสองข้อเขียนว่าอะไร
5. ยืนยันว่าไฟล์ทั้ง 4 ไม่มีบรรทัดยาวเกิน ~200 ตัวอักษรแล้ว (บอกจำนวนบรรทัดของแต่ละไฟล์ก่อน/หลัง)
6. รายชื่อไฟล์ที่แตะทั้งหมด

## กฎการทำงาน

- อ่านไฟล์ก่อนแก้เสมอ · **ห้าม `migrate:fresh`** · ห้ามแตะ `.env` · ห้าม refactor นอกขอบเขต
- **ห้ามแตะไฟล์เหล่านี้** (เจ้าของโปรเจคแก้ค้างอยู่): `ui/components/Modal.vue` ·
  `ui/components/learn/course/groups/ImportFromClassroomsModal.vue` · `ui/components/learn/course/groups/SyncClassroomModal.vue`
- ห้ามแก้ 8 ฟังก์ชันเดิมฝั่งหน่วยเลือกตั้งใน `useElections.ts`
- ห้ามแตะพฤติกรรมบัตรลับ · ห้ามแสดงคะแนนรายพรรคก่อนปิดหีบ (turnout ไม่มีข้อมูลรายพรรคโดยเจตนา)
- commit เป็นชุดเล็ก · ถ้า commit ไม่ได้เพราะ `.git/index.lock` ให้รายงานแล้วทิ้งไว้ใน working tree **อย่าลบไฟล์ lock**
- ถ้าทำข้อไหนไม่ได้ ให้รายงานตรง ๆ **ห้ามรายงานว่าเสร็จทั้งที่ยังไม่ได้ทำ**
