# แบบทดสอบบทเรียน — โหมดเกม + ตรวจทีเดียว + หักแต้มเมื่อแก้ + XP + อันดับ

> สถานะ: ⏸️ **พักไว้ (2026-11-14)** — ผู้ใช้สั่งพัก เพราะทุกทางติดเงื่อนไข (ต้องเปลี่ยนกติกาตรวจคำตอบของบทเรียนทั้งระบบ + ปัญหาเดิมข้อ 7) · ยังไม่มีโค้ดของเกม · ห้ามเริ่มจนกว่าผู้ใช้จะสั่ง
> ปรับครั้งที่ 3 เมื่อ 2026-11-14 ตามที่ผู้ใช้เคาะ
> (ชื่อไฟล์คงเดิม `quiz-rush-game.md` — เดิมเป็นเกมแยก แล้วย้ายเข้ารายวิชา ตอนนี้ย้ายเข้า**แบบทดสอบของบทเรียน**)
> ✅ ชิ้นงาน 0 (ช่องโหว่เฉลย) แก้แล้วใน session เดียวกัน — ดูข้อ 3

## 1. สิ่งที่ผู้ใช้เคาะแล้ว

1. อยู่ใน**แบบทดสอบของบทเรียน** (`LessonQuizSection`) ไม่ใช่หน้าเกมแยก หรือแบบทดสอบของรายวิชา
2. **นักเรียนเลือกโหมด**เอง: ปกติ / เกม
3. **ไม่บอกคำตอบทันที** — ทำครบทุกข้อแล้ว**ตรวจทีเดียว**
4. **แก้คำตอบหรือตอบใหม่ = หักแต้มต่อข้อ** ตามที่ครูกำหนด
5. **ให้ XP**
6. มี**แท็บอันดับ**ในหน้าแบบทดสอบบทเรียน ให้นักเรียนเปิดดูได้

## 2. ระบบเดิม (สำรวจแล้ว)

| เรื่อง | ที่อยู่ | สภาพตอนนี้ |
|---|---|---|
| UI แบบทดสอบบทเรียน | `ui/components/learn/course/lesson/LessonQuizSection.vue` (863 บรรทัด) | ปุ่ม "ตรวจคำตอบ" **ทีละข้อ** ตอบซ้ำได้จนถูก |
| ดึงคำถาม | `GET /api/lessons/{lesson}/questions` → `LessonQuestionController@index` | ⚠️ **ไม่เช็คสิทธิ์/visibility** และส่ง `explanation` ของตัวเลือกให้นักเรียน (ดูข้อ 7) |
| ส่งคำตอบ | `POST /api/lessons/{lesson}/questions/{q}/answer` → `LessonAnswerQuestionController@store` | ตอบ `is_correct` **ทันที**, `updateOrCreate` ตอบใหม่ได้ฟรีไม่จำกัด, มีด่าน visibility / lifecycle / completion ครบ |
| คะแนน | `lesson_answer_questions.points` → `CourseScoreService::recompute()` | ผูกกับคะแนนรายวิชาแล้ว |
| แต้มหักต่อข้อ | `questions.pp_fine` (มีอยู่แล้ว) | ใช้แค่ในแบบทดสอบรายวิชา (`UserAnswerQuestionController.php:168`) และ ⚠️ หักด้วย `$user->decrement('pp')` **ไม่ลง ledger** — ห้ามลอกแบบนี้ |
| ฟอร์มครู | ช่อง `pp_fine` มีแค่ในหน้าแก้ไขแบบทดสอบรายวิชา | ฟอร์มคำถามของบทเรียนยังไม่มีช่องนี้ |
| ledger แต้ม | `PointsService::spend()` / `PointLedgerService::debitUserPoints()` (รับ idempotencyKey) | ใช้ตัวนี้ตอนหักแต้ม |
| XP | `PointsService::addXp()` + เพดานใน `config/economy.php` `game_xp` | ให้ server เรียกเองหลังตรวจ ไม่ให้ client ยิง `/points/xp/earn` |

## 3. ✅ ชิ้นงาน 0 — ปิดช่องโหว่เฉลย (เสร็จแล้ว ยังไม่ commit)

- `GET /api/questions/{question}/answers` เดิมคืน `correct_option_id` ให้ทุกคนที่ล็อกอิน
- แก้ [`QuestionAnswerController.php`](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/questions/QuestionAnswerController.php) ให้เหลือแค่เจ้าของคำถาม + `course->isAdmin()` คนอื่นได้ 403
- เทสต์ [`QuestionAnswerKeyAccessTest.php`](../../api/nuxnanravel/tests/Feature/QuestionAnswerKeyAccessTest.php) 4 เคส (นักเรียน/คนนอก 403, guest 401, ผู้เขียน 200) + `LessonQuestionScoringIntegrityTest` เดิม 9 เคส ผ่านทั้งหมดบน sqlite · pint ผ่าน
- ยังไม่ได้รันบน MySQL (`-c phpunit.mysql.xml`)

## 4. 🔴 กฎข้อเดียวที่ต้องยึด: กติกาใหม่ต้องใช้กับ**ทั้งสองโหมด**

คำตอบของทั้ง 2 โหมดลงตารางเดียวกัน (`lesson_answer_questions`) และนับคะแนนรายวิชาเดียวกัน
ถ้าโหมดปกติยังตรวจทีละข้อฟรี นักเรียนจะไปหาเฉลยในโหมดปกติ แล้วกลับมาเก็บ XP/อันดับในโหมดเกม
**→ "ตรวจทีเดียว + หักแต้มเมื่อแก้" เป็นกติกาของแบบทดสอบบทเรียน ส่วน "โหมด" เปลี่ยนแค่หน้าตา**
และ endpoint เดิม `POST .../questions/{q}/answer` ที่ตอบ `is_correct` ทันที **ต้องปิด** ไม่งั้นยิงตรงก็ข้ามกติกาได้

## 5. ลำดับการเล่น (ทั้งสองโหมด)

1. **เริ่ม** → เลือกโหมด (จำค่าใน localStorage) → server สร้าง attempt บันทึก `started_at`
2. **ทำ** → เลือกคำตอบได้อิสระ เปลี่ยนกี่ครั้งก็ได้ **ไม่เสียแต้ม** เพราะยังไม่ได้ตรวจ (เก็บเป็น draft ฝั่ง client + localStorage กันรีเฟรชหาย)
3. **ส่งตรวจ** → ปุ่มเปิดเมื่อตอบครบทุกข้อ → ส่งทั้งชุดใน request เดียว → server ตรวจ บันทึก ให้ XP → คืนผลรายข้อ **✓/✗ เท่านั้น ไม่บอกว่าข้อไหนถูก**
4. **ตอบใหม่** (ไม่บังคับ) → เลือกข้อที่ผิด → ระบบโชว์ "ข้อนี้ใช้ X แต้ม รวม Y แต้ม (คุณมี Z)" → ยืนยัน → ส่งเฉพาะข้อที่แก้ → หักแต้มลง ledger → ตรวจใหม่
5. **เฉลยเต็ม** (ข้อที่ถูก + คำอธิบาย) → แสดงเมื่อถูกครบทุกข้อ หรือเมื่อนักเรียนกด "ดูเฉลย / ยอมแพ้" ซึ่งจะ**ล็อกไม่ให้ตอบใหม่อีก** (D3)

**โหมดเกม** ต่างแค่การแสดงผล: ทีละข้อเต็มจอ, แถบความคืบหน้า, เสียง/เอฟเฟกต์, ตอนส่งตรวจมีแอนิเมชันเปิดผลทีละข้อ + คอมโบจากข้อที่ถูกติดกัน, เด้ง XP ที่ได้, ลิงก์ไปแท็บอันดับ
(คอมโบ/เอฟเฟกต์เป็นแค่การแสดงผล **ไม่มีผลกับคะแนนรายวิชาและอันดับ**)

## 6. การตัดสินใจที่ยังต้องเคาะ

- **D1 ใช้กับคำถามเดิมที่นักเรียนตอบไปแล้วยังไง** — แนะนำ: คำตอบเดิมถือว่า "ตรวจแล้ว" ตามจริง ถ้าแก้ต้องเสียแต้ม (ไม่ล้างของเดิม คะแนนรายวิชาไม่ขยับ)
- **D2 แต้มที่หัก** — แนะนำ: ใช้ `questions.pp_fine` ที่มีอยู่แล้ว (0 = ตอบใหม่ฟรี) + เพิ่มช่องในฟอร์มคำถามของบทเรียน · แต้มที่หักหายไปเลย หรือเข้ากองทุนรายวิชา (`creditCoursePoints`)
- **D3 เฉลยเต็มเปิดเมื่อไหร่** — แนะนำตามข้อ 5.5
- **D4 สูตร XP** — แนะนำ: 10 XP/ข้อที่ถูก**ในการส่งรอบแรก** + โบนัส 20 ถ้าถูกครบในรอบแรก · ข้อที่ถูกตอนตอบใหม่ไม่ได้ XP · ให้ครั้งเดียวต่อบทเรียนต่อคน (idempotency key `lesson_quiz:{lesson}:{user}`) · source `lesson_quiz` อยู่ใต้เพดาน `game_xp`
- **D5 อันดับเรียงยังไง** — แนะนำ: คะแนนรอบแรก ↓ → จำนวนครั้งที่ตอบใหม่ ↑ → เวลาที่ใช้ (started→submitted) ↑ · เห็นเฉพาะสมาชิกรายวิชา · โชว์ชื่อ+รูป+คะแนน (ไม่โชว์คำตอบ)

## 7. ปัญหาเดิมที่ต้องแก้ไปพร้อมกัน

1. `LessonQuestionController@index` ไม่เรียก `ContentVisibilityService` → ใครล็อกอินก็ดึงคำถามบทเรียนที่ยังไม่เผยแพร่/ต้องซื้อได้ → เพิ่มด่านเดียวกับ `store` ของคำตอบ
2. `QuestionOptionResource` ส่ง `explanation` ของ**ทุกตัวเลือก**ให้นักเรียน (มักเขียนว่า "ถูก เพราะ…") → ซ่อนจนกว่าจะเปิดเฉลยเต็ม (D3)
3. `QuestionResource` ส่ง `explanation` ของคำถามด้วย → เงื่อนไขเดียวกัน

## 8. Backend (`api/nuxnanravel/`)

1. **Migration** (ไฟล์ละเรื่อง, มี `down()` จริง, รันด้วย `migrate --path`)
   - `lesson_quiz_attempts`: user_id, lesson_id, mode(`standard|game`), started_at, first_submitted_at, first_round_correct, first_round_total, reanswer_count, pp_spent, xp_awarded, revealed_at null, timestamps · unique(user_id, lesson_id)
   - `lesson_answer_questions` เพิ่ม `edit_count` int default 0
2. **Controller** `Api/Learn/Course/lessons/questions/LessonQuizAttemptController.php` + routes ในกลุ่ม `/lessons` เดิม:
   - `POST /lessons/{lesson}/quiz/start` `{mode}` → สร้างหรือคืน attempt
   - `POST /lessons/{lesson}/quiz/submit` `{answers:[{question_id, answer_id}]}`
     - รอบแรก: ต้องครบทุกข้อ ไม่งั้น 422
     - รอบหลัง: ข้อที่มีคำตอบตรวจแล้วและ answer_id เปลี่ยน → รวมค่า `pp_fine` → เช็คยอดพอก่อน → ทั้งหมดใน `DB::transaction` + `lockForUpdate` → หักผ่าน `PointsService::spend()` (idempotency key ต่อ submit) → `edit_count++`
     - ด่าน: visibility, `Gate submitLessonQuestion`, completion requirement, ตัวเลือกต้องเป็นของคำถามนั้น (ลอกจาก `LessonAnswerQuestionController@store`)
     - หลังบันทึก: `CourseScoreService::recompute()` ครั้งเดียว + ให้ XP รอบแรกครั้งเดียว
     - คืน `{results:[{question_id, is_correct}], score, total, xp_awarded, pp_spent, user_pp, all_correct}` — **ไม่มี correct_option_id**
   - `POST /lessons/{lesson}/quiz/reveal` → ตั้ง `revealed_at` (ล็อกการตอบใหม่) แล้วคืนเฉลย+คำอธิบาย
   - `GET  /lessons/{lesson}/quiz/leaderboard` → เฉพาะสมาชิกรายวิชา เรียงตาม D5
3. **ปิด** `POST /lessons/{lesson}/questions/{q}/answer` เดิม → คืน 410 พร้อมข้อความให้ใช้ `/quiz/submit` (หรือลบ route) — ตรวจก่อนว่าไม่มีที่อื่นเรียก
4. แก้ข้อ 7 ทั้ง 3 จุด
5. **Tests** `tests/Feature/LessonQuizAttemptTest.php` — รันทั้ง sqlite และ MySQL:
   ส่งไม่ครบรอบแรก 422 · response ไม่มีเฉลย · แก้ข้อที่ตรวจแล้วหักแต้มตรง `pp_fine` + มีแถว ledger · แต้มไม่พอ 422 และไม่มีอะไรเปลี่ยน · แก้ข้อที่ไม่เปลี่ยนคำตอบไม่เสียแต้ม · XP ได้ครั้งเดียวแม้ส่งซ้ำ · หลัง reveal ตอบใหม่ไม่ได้ · endpoint เดิมใช้ไม่ได้แล้ว · คนนอกรายวิชาดูอันดับไม่ได้ · ยิงพร้อมกัน 2 request ไม่หักแต้มซ้ำ

## 9. Frontend (`ui/`) — mobile-first, Vikinger tokens สำหรับโหมดเกม

1. `services/learn/lessonQuizService.ts` (ผ่าน `useApi` — error อยู่ที่ `err.data`)
2. `composables/useLessonQuiz.ts` — attempt, draft (localStorage ต่อ lesson+user), submit, reanswer, reveal
3. `LessonQuizSection.vue` แยกเป็น:
   - `lesson/quiz/LessonQuizModePicker.vue` (ปกติ / เกม)
   - `lesson/quiz/LessonQuizStandard.vue` (รายการทุกข้อ + ปุ่มส่งตรวจด้านล่าง — แทนปุ่มตรวจรายข้อเดิม)
   - `lesson/quiz/LessonQuizGame.vue` (ทีละข้อ + HUD + แอนิเมชันเปิดผล)
   - `lesson/quiz/LessonQuizReanswerDialog.vue` (สรุปแต้มที่จะเสีย + ยอดคงเหลือ)
   - `lesson/quiz/LessonQuizLeaderboard.vue` (แท็บ "อันดับ")
   - แท็บ: "แบบทดสอบ | อันดับ" — tab bar `overflow-x-auto` ปุ่ม `min-h-[44px]`
4. ฟอร์มคำถามของบทเรียน: เพิ่มช่อง "แต้มที่หักเมื่อตอบใหม่" (`pp_fine`, ≥0)
5. i18n TH/EN
6. ตรวจจริง 375 → 768 → 1280: เลือกโหมด → ตอบไม่ครบ (ปุ่มส่งปิด) → ครบ → ส่ง → ✓/✗ → ตอบใหม่ (แต้มลด) → แต้มไม่พอ → ดูเฉลย (ล็อก) → แท็บอันดับ → รีเฟรชกลางทาง draft ไม่หาย · ทั้งสองโหมด

## 10. ลำดับงาน (agy/Codex เขียน — Claude ตรวจ diff + รันเทสต์ + เปิดจอจริงเองทุกชิ้น)

0. ✅ ช่องโหว่ `/questions/{id}/answers`
1. ข้อ 7: visibility ของ index + ซ่อน explanation (เล็ก ทำได้ทันที ไม่ต้องรอ D1–D5)
2. Migration + `LessonQuizAttemptController` start/submit/reveal + ปิด endpoint เดิม + เทสต์
3. XP + leaderboard endpoint + เทสต์
4. Frontend โหมดปกติแบบใหม่ (ส่งตรวจทีเดียว + ตอบใหม่เสียแต้ม) + ช่อง `pp_fine` ในฟอร์มครู
5. Frontend โหมดเกม + แท็บอันดับ

**ข้อควรระวังตอนปล่อย:** ชิ้น 2 กับ 4 ต้องขึ้นพร้อมกัน — ถ้าปิด endpoint เดิมก่อน UI ใหม่ขึ้น นักเรียนจะส่งคำตอบไม่ได้เลย
