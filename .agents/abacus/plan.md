# แผนพัฒนาเกมจินตคณิตออนไลน์ (Mental Abacus Adventure) — ฉบับนำไปพัฒนาจริง

> เอกสารอ้างอิง (ผู้ใช้แปะจาก ChatGPT, 2026-10-16). เก็บไว้เป็น spec ตั้งต้นของฟีเจอร์ "เกมจินตคณิต/ลูกคิดเสมือน" ใน nuxnan
> หมายเหตุปรับบริบท: แผนต้นฉบับสมมติเป็น **แอปใหม่แยก repo** (`mental-abacus/`) — แต่เรา **adapt เข้ากับ nuxnan** (reuse auth/games/academies/leaderboard เดิม) ไม่สร้าง repo ใหม่

## 1. ข้อกำหนดหลัก (Product Definition)
- รูปแบบ: Responsive Web App (ไม่ต้องติดตั้งแอปในรุ่นแรก)
- ผู้ใช้: ผู้เรียน / ครู / ผู้ปกครอง / ผู้ดูแล — สิทธิ์แยกตามบทบาท
- หลักการ: **ความถูกต้องมาก่อนความเร็ว** · ระบบเกมสนับสนุนการเรียนรู้ · คะแนนแข่งขันต้องตรวจที่ server
- MVP ต้องมี: สมัคร/เข้าสู่ระบบ+บทบาท · dashboard+เส้นทางเรียน · ลูกคิดเสมือนใช้ได้จริง · บทเรียน+แบบฝึก+คำอธิบาย · โหมดมีลูกคิด+โหมดซ่อนลูกคิด · ตรวจคำตอบ+บันทึกประวัติ · XP/เหรียญ/ภารกิจ/ปลดล็อกด่าน · รายงานความก้าวหน้า · ระบบผู้ดูแลจัดการบทเรียน · ทดสอบความถูกต้อง+ความปลอดภัย
- ระยะต่อไป: แข่งสด · ห้องเรียนเต็มรูป · แข่งระหว่างโรงเรียน · สมาชิกชำระเงิน · แอป native · analytics ขั้นสูง/AI tutor

## 2. เทคโนโลยี
Nuxt 4 + Vue 3 (Composition API, `<script setup>`) + TypeScript + Tailwind · Backend Laravel REST API + Sanctum · MySQL (migration+FK+index) · Redis/Queue เพิ่มภายหลัง · Test: Vitest, PHPUnit, Playwright (E2E)
- ไม่ต้องใช้ Pinia วันแรก — เริ่มจาก composables/สถานะเฉพาะฟีเจอร์ก่อน
- Laravel = Modular Monolith (ไม่แยก microservices)

## 3. โครงสร้าง/โมดูล
แยก frontend/backend ระดับโฟลเดอร์. โมดูล: **Abacus** (กฎลูกคิด+คำนวณ) · **Learning** (บทเรียน/ขั้น/เงื่อนไขปลดล็อก) · **Exercise** (สร้างโจทย์/ตรวจคำตอบ/คำใบ้) · **Game** (XP/ภารกิจ/เหรียญ/รางวัล) · **Progress** (ประวัติ/ความชำนาญ) · **Competition** (เพิ่มภายหลัง)
- **ห้ามเขียนกฎคำนวณซ้ำในหลาย component** · controller ไม่ควรคำนวณเองทั้งหมด · กฎหลักอยู่ใน service/โมดูลคำนวณที่ทดสอบแยกได้

## 4. หน้าจอ/URL (ปรับให้เข้ากับ nuxnan ภายหลัง)
`/` แนะนำ · `/login` `/register` · `/dashboard` ผู้เล่น · `/adventure` แผนที่โลก · `/learn` + `/learn/lessons/:slug` · `/practice` เลือกโหมด · `/practice/abacus` ฝึกลูกคิด · `/practice/anzan` ฝึกในใจ · `/results/:id` สรุปผล · `/profile` · `/leaderboard` · `/teacher` · `/admin`
Layout: public / player / **training** (ลดสิ่งรบกวน ใช้พื้นที่ลูกคิดเต็มจอ) / teacher / admin. **Backend ตัดสินสิทธิ์จริงเสมอ**

## 5. ลูกคิดเสมือน (แกนหลัก)
### 5.1 มาตรฐาน Soroban
เม็ดบน 1 (=5) · เม็ดล่าง 4 (เม็ดละ 1) · คานกลางแยกเม็ดที่นับ/ไม่นับ · ขวาสุด=หน่วย. **สถานะเป็นข้อมูล ไม่ใช่พิกเซล**:
```
interface AbacusColumn { upperActive: boolean; lowerActive: number /*0–4*/ }
interface AbacusState { columns: AbacusColumn[] }
```
### 5.2 API ภายใน Calculation Engine
`createAbacusState()` · `setNumber(value)` · `readNumber(state)` · `moveBead(...)` · `applyOperation(...)` · `validateState(state)` · `getHint(...)`
### 5.3 ขอบเขตคณิต MVP
จำนวนเต็มไม่ติดลบ · บวก/ลบหลายหลัก · ทด/ยืมข้ามหลัก · คู่5/คู่10 · อ่านค่าจากสถานะ · ตรวจคำตอบตรงคณิต · รองรับจำนวนหลักตามระดับ. (คูณ/หาร/ทศนิยม/ติดลบ เพิ่มเมื่อบวก–ลบผ่านเทสต์ครบ)
### 5.4 ชุดทดสอบที่ต้องผ่าน
2+3=5 · 4+1=5 · 8+7=15 · 9+1=10 · 47+38=85 · 99+1=100 · 10−1=9 · 52−27=25 · 100−1=99 · 1000−999=1 (+ unit test ทุกค่าในหลัก, เลื่อนเม็ดกลับไปมา, สถานะผิดกฎ, เกินขอบเขต)
**เกณฑ์ตรวจรับ:** อ่าน state กลับเป็นตัวเลขตรงกัน + ผลคำนวณตรงคณิตทุกเคส

## 6. ฐานข้อมูล MySQL
ตารางหลัก: `users` `player_profiles` `courses` `learning_stages` `lessons` `exercise_templates` `exercise_attempts` `game_sessions` `skill_mastery` `user_progress` `missions` `user_missions` `reward_transactions` `audit_logs`
กฎ: ใช้ migration ทุกครั้ง · FK+unique · เก็บเวลา+แสดงตาม timezone ผู้ใช้ · XP/คะแนนเป็นประวัติธุรกรรม (ไม่แก้ตัวเลขลอย) · แยกโจทย์ที่เล่นจริงจากแม่แบบ · นโยบายเก็บ/ลบข้อมูล · ไม่เก็บ password plaintext/ไม่ log ความลับ
MVP เริ่มจาก: users, courses, lessons, โจทย์, sessions, attempts, progress, reward_transactions

## 7. REST API (Nuxt ↔ Laravel)
Frontend ไม่ต่อ DB ตรง — ผ่าน API. prefix `/api/v1/`
`GET /me` `GET /dashboard` `GET /courses` `GET /lessons` `GET /lessons/{id}` · `POST /game-sessions` `GET /game-sessions/{id}` `POST /game-sessions/{id}/attempts` `POST /game-sessions/{id}/complete` · `GET /progress` `GET /missions` `POST /missions/{id}/claim` `GET /leaderboard`
- **ตรวจคำตอบ/คะแนนที่ server จากโจทย์ที่ server สร้าง/เก็บ** — ไม่เชื่อค่าจาก client · client แสดงผลเบื้องต้นได้ แต่ที่บันทึก/แข่งต้องผ่าน backend
- Response ตัวอย่าง: `{ correct, result, earned_xp, progress_updated }` — ต้องกำหนด validation/error shape ให้เหมือนทุก endpoint

## 8. Sprint (12 sprint × 2 สัปดาห์ = 6 เดือน)
- **S1** วิเคราะห์+ล็อก MVP (requirements, wireframe, acceptance)
- **S2** ตั้งค่าโปรเจกต์+พื้นฐาน
- **S3** **Abacus Engine** (ผ่านเทสต์) ← *กำลังทำ*
- **S4** **Virtual Abacus UI** (เลื่อนเม็ด+อ่านผลได้) ← *ทำแล้วบางส่วน*
- **S5** ระบบบทเรียน+โจทย์
- **S6** บัญชี+บันทึกผล
- **S7** Game Progression
- **S8** Mental Abacus Mode (Anzan ซ่อนลูกคิด)
- S9 ปรับความยาก+รายงาน · S10 ครู+แข่งพื้นฐาน · S11 ทดสอบครบวงจร · S12 deploy+pilot
เงื่อนไข: ต้องตรวจรับ+เทสต์ผ่านก่อนเริ่มงานที่พึ่งพาผลนั้น

## 9. วิธีทำงานแต่ละ Sprint
Specification → UI/Data Contract → Implementation (mock เมื่อ API ยังไม่พร้อม) → Integration (service layer) → Automated Testing → Acceptance (จอจริง มือถือ+คอม) → Release (merge+changelog+rollback)
- **Mock-first ผ่าน interface เดียวกับของจริง** (`ExerciseService`, `ProgressService`) · component ไม่เรียก API กระจัดกระจาย · แยก mock จาก production · ทดสอบ loading/empty/error/session หมดอายุ

## 10. ความปลอดภัย (ก่อน production)
HTTPS+secure cookie · authn/authz ฝั่ง server · validate ทุก request · rate limit (login/ส่งคำตอบ/รับรางวัล) · กันรับ XP/รางวัลซ้ำ (idempotency) · ตรวจเจ้าของ game session · กัน IDOR/XSS/CSRF/SQLi · แยก secret ออกจาก repo · log ไม่เก็บ password/token · backup+ทดสอบกู้คืน · monitoring · นโยบายข้อมูลเด็ก
Deploy: Development → Staging → Production

## 11. เกณฑ์ตรวจรับ MVP (20 ข้อ)
A ลูกคิด/บทเรียน: แสดงค่าถูกทุกหลัก · เลื่อนเม็ดเมาส์+สัมผัส · โจทย์/คำตอบผ่านเทสต์ · คำใบ้ตรงกฎ · เรียนต่อเนื่องจบได้
B บัญชี/ข้อมูล: สมัคร/เข้าระบบ · แยกข้อมูลตามบัญชี · ประวัติคงอยู่ · กันบันทึก/รางวัลซ้ำ · สิทธิ์บังคับฝั่ง server
C เกม/เรียนรู้: โหมดลูกคิด+ซ่อนลูกคิด · XP/ปลดล็อกตามกติกา · ความยากตามผลงาน(ไม่ใช่ความเร็วล้วน) · รายงานแยกถูก/เร็ว · มีคำแนะนำเมื่อตอบผิด
D production: จอมือถือ/แท็บเล็ต/คอม · ไม่มี bug วิกฤตค้าง · ผ่านเทสต์ API+เส้นทางหลัก · HTTPS/log/backup · ทดสอบกู้คืน+rollback

## 12. ข้อกำหนดด้านการเรียนรู้
ประเมิน 3 แบบ: Immediate / Transfer (โจทย์ใหม่ทักษะเดิม) / Retention (เว้นระยะ). อย่าใช้คะแนนในเกมอ้างว่าพัฒนาความจำ/สมาธิโดยไม่มีการวัดเฉพาะ

## 13. เริ่มทันที (ลำดับ)
1. ล็อก MVP (จำนวนหลัก, กฎลูกคิด, บวก–ลบ, บทเรียนแรก)
2. Learning Blueprint (แต่ละบทเรียนก่อน–หลังต้องทำอะไรได้)
3. **Abacus Engine** (ทดสอบก่อนทำแอนิเมชัน)
4. เกมหนึ่งเส้นทางให้ครบ (เข้าเล่น→บทเรียน→ลูกคิด→แบบฝึก→ตรวจ→บันทึก)
5. เชื่อม Laravel+MySQL (mock→จริง, บัญชี+สิทธิ์)
6. ทดลองกับผู้เรียน

**ข้อสรุป:** เป้าหมายแรก = ทำหนึ่งเส้นทางเรียนให้ครบ มีข้อมูลจริง ตรวจคำตอบได้ วัดความก้าวหน้าได้ แล้วค่อยขยาย

---
## ส่วนขยาย: หลักสูตรนิ้วมือก่อนลูกคิด (ผู้ใช้เสนอ 2026-10-16)

**หลักการ:** เริ่มจากรูปธรรม→ภาพ→ในใจ. เส้นทาง = **นิ้วมือ → นิ้วสองมือ 0–99 → ลูกคิด → Anzan** (เพิ่ม 3 ด่านเตรียมก่อนด่านลูกคิดเดิม)

### เส้นทางเรียนฉบับปรับปรุง
- **ด่าน 1 — นิ้วมือข้างเดียว 0–4:** ความหมายจำนวน, ยก/ลดนิ้ว, จับคู่เลข–นิ้ว–สิ่งของ, เพิ่ม/ลดทีละ 1
- **ด่าน 2 — นิ้วมือสองข้าง 0–99:** แทนจำนวนด้วยสองมือ, แยกหลักสิบ/หน่วย, บวก/ลบ/เติมเต็ม/เปลี่ยนผ่านหลัก
- **ด่าน 3 — ลูกคิดจริง+เสมือน:** เชื่อมจำนวนจากนิ้วมือ→ตำแหน่งลูกคิด, ค่าประจำหลัก (= งานที่ทำแล้ว VirtualAbacus/practice)
- **ด่าน 4 — ลูกคิดในใจ (Anzan):** ลดภาพช่วยจนเหลือโจทย์ตัวเลข

### แผนที่บทเรียน (lessons)
1.1 รู้จัก 0–4 · 1.2 มองภาพรู้จำนวน · 1.3 เพิ่ม/ลด · 1.4 บวก/ลบเบื้องต้น
2.1 ระบบนิ้วสองข้าง · 2.2 จำนวน 0–9 · 2.3 จำนวน 10–99 · 2.4 บวก/ลบสองหลัก
3.1 รู้จักลูกคิด · 3.2 แสดงจำนวนบนลูกคิด · 3.3 บวก/ลบด้วยลูกคิด · 4 ลูกคิดในใจ

### กติกานิ้วมือ (ผู้ใช้ยืนยันบางส่วน)
- ✅ **มือซ้าย = หลักสิบ (0–9) · มือขวา = หลักหน่วย (0–9)** → จำนวน = สิบ×10 + หน่วย (ผู้ใช้ยืนยัน)
- ⚠️ **ท่ามือ 0–9 ต่อ 1 มือ = ยังเป็นต้นแบบ ต้องล็อก:** ข้อเสนอต้นแบบ = หัวแม่มือ=5, นิ้ว 1–4 = ค่า 1–4 (เช่น 8=5+3) — โครงสร้างเดียวกับลูกคิด (heaven=5, earth=1..4) → **ต้องยืนยันว่าตรงกับที่ผู้ใช้เคยเรียนไหมก่อนล็อก** (ยังไม่ยืนยัน)
- ข้อควรระวัง: ต้องกำหนดการหันฝ่ามือ/ลำดับนิ้ว/ภาพมือซ้าย-ขวาให้ถูก (ภาพสะท้อนทำให้จำผิด)

### โมดูลที่ต้องแยก (§6 ของส่วนขยาย — testable เหมือน abacusEngine)
1. **FingerGestureEngine** — แปลง 0–9 ↔ ท่ามือ. **แยก "ค่าของมือ (0–9)" ออกจาก "ท่าทางที่แสดง (gesture data)"** ให้ convention เป็น config ปรับได้ โดยไม่ต้องเขียนเกมใหม่
2. **PlaceValueEngine** — รวมสองมือ (สิบ+หน่วย) → 0–99 (คล้าย abacusValue 2 หลัก)
3. **LessonEngine** — จัดบทเรียน/สุ่มโจทย์/ตรวจ/บันทึกความก้าวหน้า (ต่อยอด ExerciseService เดิม)

### โหมดเกม (ด่านนิ้วมือ)
ดูแล้วตอบ (ท่ามือ→เลือกจำนวน) · ดูเลขแล้วทำมือ (เลข→เลือกท่าหลักสิบ/หน่วย) · ฟังแล้วตอบ (เสียง→ตอบ) · ฝึกคำนวณ (โจทย์→สร้างคำตอบด้วยท่ามือ)

### หลักปลดล็อก (ทุกด่าน)
ไม่ปลดล็อกเพราะทำครบจำนวนข้อ แต่ดูความแม่นยำ+ความเข้าใจ: เรียนรู้(มีคำใบ้)→คล่อง(ลดคำใบ้)→ทดสอบความเข้าใจ(เปลี่ยนรูปโจทย์)→ผ่านเกณฑ์ความแม่นยำ→ถ้าผิดบ่อยกลับไปฝึกทักษะย่อยที่ยังไม่แม่น (ไม่เริ่มใหม่หมด)

### ✅ กติกาล็อกแล้ว (ผู้ใช้ยืนยัน 2026-10-16)
ท่ามือ 0–9 ต่อมือ = **หัวแม่มือ=5, นิ้วชี้→ก้อย=1–4** (ตรงที่ผู้ใช้เรียน) — โครงสร้างเดียวกับ soroban (thumb≈heaven, extended≈earth)
- ✅ **FingerGestureEngine เสร็จ:** `ui/components/games/fingers/fingerEngine.ts` — `HandGesture{thumb,extended}`, `handValue/digitToHand` (0–9), `fingersUp` (ชั้นแสดงผล, convention เป็น config `DEFAULT_CONVENTION` index→pinky), `validateHand` + PlaceValueEngine สองมือ `HandsState{tens,units}`/`handsValue/numberToHands` (0–99 ซ้าย=สิบ ขวา=หน่วย). เทสต์ `ui/tests/fingers.spec.ts` 9 เคส (รวม round-trip 0–99, fingersUp, custom convention)
- ✅ **UI มือ + เกมด่านนิ้วมือ 1–2 เสร็จ** (Claude เขียนเอง เพราะ Codex blocked — ดู [[feedback_codex_agy_pipeline]]): `HandDisplay.vue` (มือ CSS, นิ้วกางจาก fingersUp, มือซ้าย mirror), `services/fingers/fingerExerciseService.ts` (mock สร้างโจทย์+ตัวเลือก 0–9/0–99), `composables/useFingerPractice.ts`, `components/games/fingers/FingerPractice.vue` (ด่าน1 มือเดียว/ด่าน2 สองมือ × โหมด read ดูมือ→ตอบเลข / make ดูเลข→เลือกมือ), `pages/practice/fingers.vue` (route `/practice/fingers`). สโมค 375px ครบ (read/make × ด่าน1/2, ตอบถูกเขียว, สองมือ mirror, สถิติเดิน) · เทสต์รวม 51/51 ผ่าน · สเปคที่ `.agents/abacus/spec-finger-ui.md`
- ✅ **ด่าน 3 (สะพานนิ้วมือ↔ลูกคิด) เสร็จ:** `composables/useAbacusBridge.ts` + `components/games/abacus/AbacusBridge.vue` + `pages/practice/bridge.vue` (`/practice/bridge`). โชว์จำนวนเป็นท่ามือ (+ตัวเลขช่วย ซ่อนได้) → สร้างบน VirtualAbacus → ตรวจด้วย isAbacusCorrect → ตอบผิดได้คำใบ้รายหลัก (หลักสิบ/หน่วยควรเป็นเท่าไร). ด่าน 3.1 (0–9/1หลัก) · 3.2 (0–99/2หลัก). สโมค 375px ครบ (ตรงกัน=เขียว, ผิด=คำใบ้). เทสต์ 51/51
- ✅ **ด่าน 6 (บวก/ลบด้วยนิ้วมือ) เสร็จ:** `services/fingers/fingerMathService.ts` (โจทย์ +− ผลไม่ติดลบในช่วง stage + ตัวเลือก) + `composables/useFingerMath.ts` + `components/games/fingers/FingerMathPractice.vue` + `pages/practice/finger-math.vue` (`/practice/finger-math`). โชว์โจทย์ "a ± b = ?" → เลือกท่ามือที่เป็นคำตอบ (การ์ด HandDisplay). ด่าน 6.1 (0–9 มือเดียว) · 6.2 (0–99 สองมือ). สโมค 375px ครบ (9+0→9 ถูก, 82+12=94 การ์ดสองมือ). เทสต์ 51/51
- ถัดไป: LessonEngine + ปลดล็อกตามความแม่นยำ (§4) · เชื่อม backend (S6: สลับ service → API) · Anzan เต็ม (ด่าน 4) · หน้ารวมเส้นทาง/แผนที่ด่าน

---
## สถานะปัจจุบันใน nuxnan (2026-10-16)
- ✅ S3 (บางส่วน): `ui/components/games/abacus/abacusEngine.ts` — กฎ soroban + planAdd/SubDigit + toggleHeaven/tapEarth + เทสต์
- ✅ S4 (บางส่วน): `ui/components/games/abacus/VirtualAbacus.vue` — เลื่อนเม็ด เมาส์+สัมผัส อ่านค่า
- ✅ S3 ครบ: `applyOperation`/`getHint`/`validateState` ใน engine + adapter (`abacusAdapter.ts`) ชื่อตามแผน §5.2 (คง heaven/earth เดิม) · เทสต์ 27/27 (รวม §5.4)
- ✅ S4/S5 (slice แรก): หน้า `/practice/abacus` mock-first — โจทย์→เลื่อนเม็ด→ตรวจ→ตอบผิดโชว์คำใบ้ (getHint)→ซ่อนลูกคิด (ghost)→สถิติ. แยก `services/abacus/exerciseService.ts` (interface+mock), `composables/useAbacusPractice.ts`, `components/games/abacus/AbacusPractice.vue`, `pages/practice/abacus.vue`. สโมค 375px ครบ (ถูก/ผิด/คำใบ้/ระดับ1-3/ghost). ยังไม่ commit
- โหมดลูกคิดใน MentalMath = quick feature (ยังไม่ commit) — อาจยุบเข้า `/practice/abacus` ภายหลัง
- **ถัดไป (S5 ต่อ/S6+):** บทเรียน (`/learn`, lessons) · บัญชี+บันทึกผลจริง (เชื่อม Laravel `/api/v1/game-sessions` + attempts แทน mock service) · game progression/XP (S7) · Anzan mode เต็ม (S8) · training layout แยก (ลดสิ่งรบกวน)
- หมายเหตุ backend: layout `main` มี global fetch notifications/gamification (401 ถ้าไม่ล็อกอิน) — หน้า practice เองไม่เรียก API (mock) · ตอนต่อ backend จริงให้สลับ implementation ของ ExerciseService เท่านั้น ไม่แก้ UI
