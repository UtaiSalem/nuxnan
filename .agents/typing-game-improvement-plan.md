# แผนการปรับปรุงระบบเกมพิมพ์ดีด (Typing Master) — ฉบับตรวจสอบกับโค้ดจริง

> อัพเดท: ตรวจสอบข้อวิเคราะห์เดิมกับซอร์สจริงทุกไฟล์แล้ว (frontend + backend)
> เอกสารนี้เป็น **แผน (plan) เท่านั้น** ยังไม่แตะซอร์สโค้ด — ใช้เป็น action items ตอน implement

แผนงานนี้แก้ 5 ประเด็น: บั๊กปุ่มไม่ตอบสนอง, การตั้งค่าไม่เป็นมาตรฐานเดียว, focus หลุดในเกม Phaser, หน้าผลลัพธ์ไม่รวมศูนย์, และไฮไลต์ Spacebar

---

## 0. สรุปผลตรวจสอบโค้ดจริง (Reality Check)

| ข้อ | สถานะแผนเดิม | ข้อค้นพบจากซอร์สจริง |
|-----|--------------|----------------------|
| 1.1 | ✅ ถูก | Regex `KeyTrainingMode.vue:56` กรอง `Minus/Equal/BracketLeft/BracketRight/Backslash` ทิ้ง และ regex นี้ **ซ้ำซ้อน** (มี `handleKeyCode` เช็ค `char===undefined` ให้แล้ว) |
| 1.2 | ⚠️ ต้องขยาย | `index.vue` ปัจจุบัน **ซ่อน** language และโชว์ placeholder สำหรับ key_training; `KeyTrainingMode` มี idle screen เลือก lang+lesson เองด้วย **local ref** (ไม่ผ่าน store) — ต้องรวมสองที่ |
| 1.3 | ✅ ถูก | ทั้ง `MonsterBattleMode` & `FallingWordsMode` ใช้ HTML `<input>` โฟกัสตอน mount; คลิก canvas → blur → พิมพ์ไม่ติด |
| 1.4 | ⚠️ **มีช่องโหว่ร้ายแรง** | `KeyTrainingMode` **ไม่ได้ emit** และ `play.vue:76` render โดย **ไม่มี `@finished`** → ผลไม่เคยถูกบันทึก. ยิ่งกว่านั้น backend ต้องการ payload รูปแบบตายตัว (ดู §1.4) การ emit เฉยๆ จะโดน **422** |
| 1.5 | ✅ ถูก (แต่ผลกระทบต่ำ) | ปุ่ม SPACE ใน `VirtualKeyboard.vue:182` ไม่ผูก `keyClasses`. **หมายเหตุ:** ยังไม่มี lesson ไหนใส่ Space ใน `keys` เลย → ไฮไลต์นี้จะยังไม่ทำงานจนกว่าจะมี lesson ที่ใช้ space (จัดเป็น P3) |

**ข้อดีที่พบเพิ่ม:** backend `TypingSessionController@store` validation รองรับ `game_mode` = `key_training` และ `letter_runner` อยู่แล้ว → **ไม่ต้องแก้ backend**

---

## 1. ปัญหาและแนวทางแก้ (Analysis & Solutions)

### 1.1 บั๊กปุ่มบางกลุ่มไม่ตอบสนอง — [P1]
- **สาเหตุจริง**: `KeyTrainingMode.vue:56`
  ```js
  if (!e.code.match(/^(Key|Digit|Semicolon|Quote|Comma|Period|Slash|Space)/)) return
  ```
  ตกหล่น `Minus (-)`, `Equal (=)`, `BracketLeft ([)`, `BracketRight (])`, `Backslash (\)` → ปุ่มไทยตำแหน่งนั้น (`ข ช บ ล ฃ`) และ lesson **แถวบน (top_row)** + **แถวตัวเลข (numbers)** ของไทยฝึกไม่จบ
- **แนวทางแก้**: regex นี้ซ้ำซ้อน (composable กรอง unmapped ให้แล้ว) — เปลี่ยนเป็นเช็คตรงกับ `LAYOUT_MAP`:
  ```js
  function onKeyDown(e: KeyboardEvent) {
    if (gameState.value !== 'playing') return
    if (e.ctrlKey || e.altKey || e.metaKey) return
    if (!(e.code in LAYOUT_MAP[selectedLang.value])) return   // ← เช็คจาก map จริง
    e.preventDefault()
    handleKeyCode(e.code)
  }
  ```
  ต้อง `import { LAYOUT_MAP } from '~/composables/useKeyTraining'` เพิ่ม

### 1.2 การตั้งค่าแต่ละเกมไม่เป็นมาตรฐานเดียว — [P2]
- **สาเหตุจริง**: โหมดปกติเลือก lang/difficulty ที่ lobby แล้วกด START; แต่ key_training ต้องเข้าเกมก่อนแล้วเลือก lang+lesson ในหน้า idle (local ref) — สับสนและไม่สอดคล้อง
- **แนวทางแก้** (2 ทางเลือก — ดู §3 จุดตัดสินใจ D2):
  - **แนะนำ**: เพิ่ม state `selectedKeyLesson` ใน store, ให้ lobby (`index.vue`) โชว์ **language + lesson** สำหรับ key_training (แทน placeholder), ให้ `KeyTrainingMode` อ่านค่าเริ่มต้นจาก store แล้วคง idle screen ไว้เป็น "พรีวิว + START" (ไม่ต้องเลือกซ้ำ)
  - reuse `store.selectedLang` ที่มีอยู่ (type `Lang` = `'th'|'en'|'ar'` ตรงกับ `TrainingLang` พอดี)

### 1.3 กดนอกช่องพิมพ์แล้วคีย์บอร์ดไม่ตอบสนอง (Phaser) — [P1]
- **สาเหตุจริง**: `MonsterBattleMode.vue` & `FallingWordsMode.vue` โฟกัส `<input>` แค่ตอน `onMounted`; คลิกบน Phaser canvas ทำให้ input blur → v-model ไม่รับตัวอักษรต่อ
- **แนวทางแก้** (แนะนำวิธี `@blur` เพราะกันหลุดได้ 100% ไม่มีปัญหาตกตัวอักษรแรกเหมือนวิธี global keydown):
  ```js
  function keepFocus() {
    if (!sharedState.gameOver) nextTick(() => inputRef.value?.focus())
  }
  ```
  ผูก `@blur="keepFocus"` ที่ `<input>` + ผูก `@click="keepFocus"` ที่ container ของ canvas
  (ทางเลือกเสริม: global `keydown` refocus — แต่มีความเสี่ยงตัวอักษรแรกหลัง blur หลุด จึงเป็น secondary)

### 1.4 หน้าผลลัพธ์ไม่รวมศูนย์ — [P1, ซับซ้อนสุด]
- **สาเหตุจริง**:
  - `play.vue:76` → `<KeyTrainingMode v-if="isKeyTraining" />` **ไม่มี `@finished`** และไม่ส่ง props
  - `KeyTrainingMode` มีหน้า finished ของตัวเอง (บรรทัด 287–394) และ **ไม่ emit** → ผลไม่ถูกส่งเข้า API เลย
- **ช่องโหว่ที่แผนเดิมมองข้าม**: backend `TypingSessionController@store` บังคับ payload:
  ```
  session_token (unique), game_mode, language, difficulty (required!),
  correct_chars, total_chars, correct_words, total_words,
  mistakes, max_combo, time_elapsed (min:1!), time_limit
  ```
  Key Training เป็นแบบ **รายคีย์** ไม่มี word/difficulty/WPM — ถ้า emit ผิดรูปจะโดน **422**
- **แนวทางแก้**: ให้ `KeyTrainingMode` emit payload ที่ map ครบตามรูปแบบเดียวกับ Monster/Falling:
  ```js
  // watch จนกว่า gameState === 'finished' แล้ว emit
  emit('finished', {
    session_token: uuid(),
    game_mode:     'key_training',
    language:      selectedLang.value,
    difficulty:    'beginner',                 // ← placeholder (ดู D1)
    correct_chars: correctCount.value,
    total_chars:   attempts.value.length,
    correct_words: Math.round(correctCount.value / 5),      // มาตรฐาน 5 char/word
    total_words:   Math.round(sequence.value.length / 5),
    mistakes:      attempts.value.length - correctCount.value,
    max_combo:     maxStreak.value,
    time_elapsed:  Math.max(1, elapsedSeconds.value),        // ← กัน min:1 (422)
    time_limit:    0,
  })
  ```
  แล้วแก้ `play.vue` ให้ render `<KeyTrainingMode ... @finished="onFinished" />` (onFinished ที่มีอยู่จะ `submitSession` + push `/result` ให้เอง)
- **ผลข้างเคียงที่ต้องรู้**: `useKeyTraining` ต้อง **expose `sequence`** เพิ่ม (ปัจจุบัน export แล้ว ✅), และต้องมี `uuid()` (โหมดอื่นเรียก `uuid()` ได้ = มี auto-import อยู่แล้ว ✅)

### 1.5 ไฮไลต์ Spacebar ใน VirtualKeyboard — [P3]
- **สาเหตุจริง**: `VirtualKeyboard.vue:182` ปุ่ม SPACE เป็น div ตายตัว ไม่ผูก `keyClasses('Space')`
- **หมายเหตุสำคัญ**: ยังไม่มี lesson ใดใส่ `' '` (space) ใน `keys` → ไฮไลต์นี้จะยังไม่มีผลจนกว่าจะเพิ่ม lesson ที่ใช้ space **จึงเป็น P3 (future-proof/cosmetic)**
- **แนวทางแก้**: ผูก `:class="keyClasses('Space')"` เข้ากับ div SPACE (ระวัง class ความกว้าง `w-48 sm:w-56...` ที่มีอยู่เดิมต้องคงไว้)

---

## 2. การเปลี่ยนแปลงราย ไฟล์ (ทีละขั้นตอน)

### 2.1 `ui/stores/typing.ts`
1. เพิ่ม state: `const selectedKeyLesson = ref<KeyLesson>('home_row')`
   - import type `KeyLesson` จาก `~/composables/useKeyTraining` (หรือประกาศ type ซ้ำเพื่อลด coupling)
2. เพิ่ม `selectedKeyLesson` ใน return object
3. (ตัวเลือก) เพิ่มพารามิเตอร์ให้ `setConfig` รองรับ lesson

### 2.2 `ui/pages/Play/Games/typing/index.vue`
1. import `LESSON_ORDER`, `LESSON_INFO_BY_LANG` จาก `~/composables/useKeyTraining`
2. ในบล็อก `v-if="isKeyTraining"` (บรรทัด 118–122) เปลี่ยน placeholder → โชว์:
   - **Language selector** (reuse ปุ่มภาษาเดิม — เอาเงื่อนไข `v-if="!isKeyTraining"` ออกจาก block Language หรือทำ block แยกที่ผูก `store.selectedLang`)
   - **Lesson selector** (loop `LESSON_ORDER` ผูก `store.selectedKeyLesson`, ใช้ `LESSON_INFO_BY_LANG[store.selectedLang][id]` โชว์ชื่อ/emoji)
3. คง difficulty ให้ **ซ่อน** สำหรับ key_training ตามเดิม (ไม่เกี่ยว)
4. `start()` เดิม push ไป `/play` ได้เลย (ค่าอยู่ใน store แล้ว)

### 2.3 `ui/pages/Play/Games/typing/play.vue`
1. บรรทัด 76 เปลี่ยนเป็น:
   ```html
   <KeyTrainingMode v-if="isKeyTraining" @finished="onFinished" />
   ```
   (`onFinished` ที่มีอยู่ handle submit + navigate ให้แล้ว — ไม่ต้องเขียนใหม่)
2. ตรวจว่า `result.vue` แสดงผล payload ของ key_training ได้สมเหตุสมผล (WPM รายคีย์อาจดูแปลก — ดู verification §5)

### 2.4 `ui/components/games/typing/modes/KeyTrainingMode.vue`
1. **แก้ 1.1**: import `LAYOUT_MAP`; แก้ `onKeyDown` (บรรทัด 52–59) ตาม §1.1
2. **แก้ 1.2**: เปลี่ยน `selectedLang`/`selectedLesson` จาก local ref → sync กับ store
   - อ่านค่าเริ่มต้นจาก `store.selectedLang` / `store.selectedKeyLesson` ตอน setup
   - ปรับ `watch(selectedLang)` ที่ reset lesson เป็น home_row (บรรทัด 26–29) — ระวังอย่าให้ทับค่าที่เลือกมาจาก lobby (ตั้ง flag "first init" หรือ sync สองทาง)
3. **แก้ 1.4**: เพิ่ม `const emit = defineEmits(['finished'])` + `watch(gameState, ...)` เมื่อเป็น `'finished'` ให้ emit payload ตาม §1.4
   - ตัดสินใจ (D3): ยังคงหน้า finished ภายในไว้ไหม — ถ้า navigate ไป `/result` ทันที หน้า finished ภายในจะไม่ถูกแสดง → พิจารณาลบเพื่อลด dead code
4. คงปุ่ม START/idle เป็น "พรีวิว" (ค่ามาจาก store แล้ว)

### 2.5 `ui/components/games/typing/ui/VirtualKeyboard.vue`
1. **แก้ 1.5**: บรรทัด 182 ผูก `:class="[keyClasses('Space'), 'h-8 sm:h-9 md:h-10 w-48 sm:w-56 md:w-64']"` (คงความกว้างเดิม)
   - `keyClasses`/`keyState`/`targetCode` รองรับ `'Space'` อยู่แล้ว (Space มีใน LAYOUT_MAP ทุกภาษา)

### 2.6 `ui/components/games/typing/modes/MonsterBattleMode.vue` & `FallingWordsMode.vue`
1. **แก้ 1.3**: เพิ่มฟังก์ชัน `keepFocus()` (§1.3)
2. `<input>` เพิ่ม `@blur="keepFocus"`
3. `<div :id="containerId" ...>` (canvas container) เพิ่ม `@click="keepFocus"`
4. ตรวจว่า `keepFocus` ไม่ทำงานหลัง `sharedState.gameOver = true` (มี guard แล้วใน §1.3)

---

## 3. จุดตัดสินใจ (ต้องเคาะก่อน implement)

- **D1 — difficulty ของ key_training**: backend บังคับ `difficulty`. เสนอส่ง `'beginner'` (ตัวคูณต่ำสุด 1.0 ไม่ปั่น score). ถ้าอยากให้ key_training ได้ XP มากกว่านี้ค่อยปรับ
- **D2 — รูปแบบ config key_training**: (ก) ย้ายเลือกทั้งหมดไป lobby + คง idle เป็นพรีวิว [แนะนำ] หรือ (ข) auto-start ทันทีเมื่อเข้าเกม (ลดคลิก แต่ผู้ใช้ไม่เห็นพรีวิวคีย์)
- **D3 — หน้า finished ภายใน KeyTrainingMode**: ลบทิ้ง (ใช้ `/result` รวมศูนย์) หรือคงไว้เป็น fallback. แนะนำ: ใช้ `/result` รวมศูนย์เพื่อความสม่ำเสมอ + บันทึกประวัติ
- **D4 — correct_words mapping**: ใช้ `round(chars/5)` (มาตรฐาน 5 char/word) เพื่อให้ score/WPM คำนวณได้สมเหตุสมผล — ยืนยันสูตรนี้
- **D5 — leaderboard pollution**: key_training จะเข้าตาราง session ด้วย. backend แยก leaderboard ตาม `game_mode` อยู่แล้ว → ไม่ปน WPM โหมดอื่น (ยอมรับได้ ไม่ต้องแก้)

---

## 4. ลำดับการทำงาน (Implementation Phases)

**Phase 1 — บั๊กกระทบผู้ใช้ทันที (P1, เสี่ยงต่ำ):**
1. 2.4 ข้อ 1 (แก้ regex 1.1)
2. 2.6 (แก้ focus 1.3 ทั้งสองไฟล์)

**Phase 2 — รวมศูนย์ผลลัพธ์ (P1, เสี่ยงกลาง — ทดสอบ API ให้แน่น):**
3. 2.4 ข้อ 3 (emit 1.4) + 2.3 (ผูก @finished)
4. ทดสอบ submit ไม่ให้ 422 (โดยเฉพาะ `time_elapsed>=1`, `difficulty`)

**Phase 3 — ความสม่ำเสมอ UX (P2):**
5. 2.1 (store) → 2.2 (lobby) → 2.4 ข้อ 2 (sync store)

**Phase 4 — เก็บงานเล็ก (P3):**
6. 2.5 (Spacebar)

> commit แยกเป็นชุดเล็กตาม phase เพื่อ revert ง่าย (ตาม CLAUDE.md)

---

## 5. แผนการทดสอบ (Verification Plan — ขยาย)

**5.1 บั๊กปุ่ม (1.1)**
- [ ] TH → lesson **แถวบน**: กด `[ ] \` (BracketLeft/Right/Backslash) ได้ `บ ล ฃ` ✅
- [ ] TH → lesson **แถวตัวเลข**: กด `- =` (Minus/Equal) ได้ `ข ช` ✅
- [ ] EN/AR ยังพิมพ์ปกติ, ปุ่ม modifier (Ctrl/Alt/Meta) ยังไม่ถูกดัก

**5.2 focus (1.3)**
- [ ] Monster Battle: คลิกกลาง canvas แล้วพิมพ์ต่อ → ตัวอักษรเข้า input ทันที
- [ ] Falling Words: เหมือนกัน; กด Tab/คลิกที่อื่นแล้วโฟกัสเด้งกลับ
- [ ] หลังเกมจบ (gameOver) โฟกัสไม่เด้งกลับ (input disabled)

**5.3 config รวมศูนย์ (1.2)**
- [ ] Lobby: เลือก key_training → เห็น Language + Lesson (ไม่เห็น Difficulty)
- [ ] เปลี่ยนภาษาแล้ว lesson list อัพเดตชื่อตามภาษา
- [ ] เข้าเกมแล้วค่า lang/lesson ตรงกับที่เลือกใน lobby (ไม่ถูก reset)

**5.4 ผลลัพธ์รวมศูนย์ (1.4) — สำคัญสุด**
- [ ] เล่น key_training จนจบ → ไปหน้า `/result` (ไม่ใช่หน้า finished เดิม)
- [ ] Network: `POST /typing/sessions` คืน **200** (ไม่ใช่ 422) — เช็ค payload มี `difficulty`, `time_elapsed>=1`
- [ ] `/result` แสดง accuracy/score/xp สมเหตุสมผล (ตรวจ WPM รายคีย์ว่าไม่ทำ UI พัง)
- [ ] เล่นเร็วมาก (จบ <1 วิ) → ยังไม่ 422 (guard `Math.max(1,...)`)
- [ ] ประวัติใน `/typing/sessions/history` มี record ใหม่ game_mode=key_training

**5.5 Spacebar (1.5)**
- [ ] (ถ้ายังไม่มี lesson ใช้ space) ยืนยันว่าไม่ทำ layout keyboard เพี้ยน
- [ ] (ถ้าเพิ่ม lesson ทดลอง) กด Space → ปุ่มไฮไลต์ correct/wrong

**5.6 regression**
- [ ] Word/Time Attack/Sentence ยังเล่น + submit ได้
- [ ] `letter_runner` (ยัง comment ใน lobby) ไม่ถูกกระทบ

---

## 6. ความเสี่ยง & Rollback

- **เสี่ยงสูงสุด = Phase 2 (1.4)**: mapping payload ผิด → 422 หรือ score/xp เพี้ยน. บรรเทาโดยทดสอบ network ก่อน merge, commit แยก phase
- **watch(selectedLang) reset lesson**: อาจทับค่า lobby — ต้องทดสอบ 5.3 ให้ผ่าน
- **@blur refocus**: ทำให้ Tab ออกจาก input ไม่ได้ระหว่างเล่น (ยอมรับได้สำหรับเกม) — ปิด listener เมื่อ gameOver
- ทุก phase revert ได้อิสระเพราะ commit แยก; ไม่มีการแก้ DB/backend จึงไม่ต้อง migrate

---

## 7. ไฟล์ที่เกี่ยวข้อง (อ้างอิงเร็ว)

| ไฟล์ | บทบาท |
|------|-------|
| `ui/stores/typing.ts` | state กลาง (เพิ่ม `selectedKeyLesson`) |
| `ui/pages/Play/Games/typing/index.vue` | lobby (เพิ่ม lesson selector) |
| `ui/pages/Play/Games/typing/play.vue` | ตัวกลาง render mode + `onFinished` (ผูก @finished) |
| `ui/components/games/typing/modes/KeyTrainingMode.vue` | แก้ 1.1 + 1.2 + 1.4 |
| `ui/components/games/typing/modes/MonsterBattleMode.vue` | แก้ 1.3 |
| `ui/components/games/typing/modes/FallingWordsMode.vue` | แก้ 1.3 |
| `ui/components/games/typing/ui/VirtualKeyboard.vue` | แก้ 1.5 |
| `ui/composables/useKeyTraining.ts` | LAYOUT_MAP / lessons (อ่านอย่างเดียว — ไม่แก้) |
| `api/.../TypingSessionController.php` | validation (รองรับ key_training แล้ว — ไม่แก้) |
