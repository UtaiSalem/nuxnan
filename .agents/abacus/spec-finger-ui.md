# สเปค: UI มือ + เกมด่านนิ้วมือ 1–2 (ให้ Codex เขียน · Claude ตรวจ)

บริบท: ต่อยอดจาก `ui/components/games/fingers/fingerEngine.ts` (มีแล้ว ผ่านเทสต์) ตามแผน
[`.agents/abacus/plan.md`](plan.md) ส่วน "หลักสูตรนิ้วมือก่อนลูกคิด". เป้าหมาย = ด่าน 1 (มือเดียว)
และด่าน 2 (สองมือ 0–99) แบบ mock-first ยึดแพตเทิร์นเดียวกับ `AbacusPractice.vue` ที่มีอยู่

## กติกา (ล็อกแล้ว — ห้ามเปลี่ยน)
- มือซ้าย = หลักสิบ · มือขวา = หลักหน่วย · จำนวน = สิบ×10 + หน่วย (0–99)
- ต่อมือ 0–9: หัวแม่มือ = 5, นิ้วชี้→ก้อย = 1→4 (เช่น 8 = 5+3)
- **ทุกค่า/ท่าทางต้องมาจาก `fingerEngine` เท่านั้น ห้ามคำนวณค่านิ้ว/แปลงเลขเอง**
  ใช้: `digitToHand`, `handValue`, `fingersUp`, `makeHand`, `numberToHands`, `handsValue`, `HandGesture`, `HandsState`, `Finger`, `DEFAULT_CONVENTION`

## ไฟล์ที่ต้องสร้าง (5)
1. `ui/components/games/fingers/HandDisplay.vue` — แสดงมือ 1 ข้างของเลข 0–9
2. `ui/services/fingers/fingerExerciseService.ts` — mock service (interface + สร้างโจทย์/ตัวเลือก)
3. `ui/composables/useFingerPractice.ts` — สถานะเซสชัน
4. `ui/components/games/fingers/FingerPractice.vue` — UI เกม (2 โหมด × 2 ด่าน)
5. `ui/pages/practice/fingers.vue` — route `/practice/fingers` (`definePageMeta({ layout: 'main' })`, render `<FingerPractice/>` ในกล่อง `max-w-2xl` เหมือน `pages/practice/abacus.vue`)

## 1) HandDisplay.vue
Props:
- `digit?: number` (0–9) — ถ้าให้มา ใช้ `digitToHand(digit)`; หรือ `gesture?: HandGesture` ตรง ๆ (อย่างใดอย่างหนึ่ง)
- `side?: 'left' | 'right'` (default `'right'`) — มือซ้าย **สะท้อนแนวนอน** (`scaleX(-1)`) เพื่อหันฝ่ามือถูก
- `label?: string` — ป้ายใต้มือ เช่น "หลักสิบ"
- `showValue?: boolean` (default false) — โชว์ค่าตัวเลขของมือ
Render:
- มือสไตล์กราฟิก (div/CSS, ไม่ต้องสมจริง) นิ้ว 5 นิ้ว เรียง หัวแม่มือ + ชี้/กลาง/นาง/ก้อย
- นิ้วที่อยู่ใน `fingersUp(gesture)` = "กางขึ้น" (เด่น/สีอุ่น vikinger-yellow) · ที่เหลือ = "พับ/จาง"
- มือซ้าย mirror ด้วย `transform: scaleX(-1)` (ครอบเฉพาะรูปมือ ไม่ครอบ label/ตัวเลข)
- mobile-first, dark mode, ใช้ vikinger tokens
- **display-only** (ไม่ interactive) — การป้อนคำตอบทำผ่านตัวเลือกใน FingerPractice

## 2) fingerExerciseService.ts
```ts
export type FingerStage = 1 | 2            // 1=มือเดียว 0–9, 2=สองมือ 0–99
export type FingerMode = 'read' | 'make'  // read=ดูมือตอบเลข · make=ดูเลขเลือกมือ
export interface FingerExercise {
  id: string
  stage: FingerStage
  mode: FingerMode
  value: number            // คำตอบที่ถูก (0–9 หรือ 0–99)
  choices: number[]        // 4 ตัวเลือก (มีคำตอบถูก 1 + ลวง 3, สับลำดับ, ไม่ซ้ำ)
}
export interface FingerExerciseService {
  next(stage: FingerStage, mode: FingerMode): FingerExercise
  check(ex: FingerExercise, chosen: number): boolean
}
export function createMockFingerExerciseService(): FingerExerciseService
```
- stage 1 → value 0–9 · stage 2 → value 0–99
- choices: สุ่มตัวลวงใกล้เคียง (±ไม่กว้างเกิน, อยู่ในช่วงของ stage, ไม่ซ้ำกับคำตอบ) รวม 4 ตัว สับลำดับ
- ห้ามเรียก API/`$fetch` — mock ในเครื่องล้วน

## 3) useFingerPractice.ts
- รับ `service` (default = mock) เป็น argument
- state: `stage`(ref 1), `mode`(ref 'read'), `exercise`, `selected`(ref<number|null>), `phase`('idle'|'answering'|'correct'|'wrong'), `correctCount`,`totalCount`,`streak`,`bestStreak`, computed `accuracy`
- actions: `start()`, `nextExercise()`, `choose(n)` (ตั้ง selected + ตรวจทันที → correct/wrong + นับสถิติ), `setStage(s)`, `setMode(m)` (เปลี่ยนแล้ว nextExercise)
- ไม่เรียก API — ใช้ service + fingerEngine เท่านั้น

## 4) FingerPractice.vue
โครงเหมือน `AbacusPractice.vue` (glass-card, vikinger, mobile-first, `onMounted(start)`):
- Header "ฝึกนิ้วมือ"
- Config: เลือกด่าน (ด่าน 1 มือเดียว / ด่าน 2 สองมือ) + โหมด (ดูมือ→ตอบเลข / ดูเลข→เลือกมือ)
- โจทย์:
  - mode `read`: โชว์ `HandDisplay` (stage1 = มือขวา 1 ข้าง · stage2 = ซ้าย(สิบ)+ขวา(หน่วย) ด้วย `numberToHands`) → ตัวเลือกเป็น **ปุ่มตัวเลข** 4 ตัว (`exercise.choices`)
  - mode `make`: โชว์ **ตัวเลขโจทย์** ใหญ่ ๆ → ตัวเลือกเป็น **HandDisplay 4 อัน** (จาก choices) ให้แตะเลือก
- กดเลือก → `choose(n)` → feedback ถูก(เขียว)/ผิด(แดง + เฉลยถูก) → ปุ่ม "ข้อถัดไป"
- Stats: ถูก/ทั้งหมด · แม่นยำ% · ต่อเนื่อง (เหมือน AbacusPractice)
- touch target ปุ่ม/ตัวเลือก ≥ 44px

## 🔴 Mobile-first (บังคับทุกไฟล์ ui/)
- ออกแบบจากจอมือถือขึ้นไป: class ไม่มี prefix = มือถือ แล้วค่อย `sm:`/`md:`/`lg:` (ห้าม desktop-first)
- ห้าม `hidden` ซ่อนข้อมูลสำคัญบนมือถือ — จัดวางใหม่แทน
- touch target ≥ 44px (`min-h-[44px]`/`p-3`)
- flex row กัน layout พัง: ฝั่งห้ามบีบ `flex-shrink-0 whitespace-nowrap`, ฝั่งข้อความ `min-w-0 flex-1 break-words`
- ของกว้าง (มือสองข้าง/ตัวเลือก) อยู่ในกล่อง `overflow-x-auto` ของตัวเอง — ห้ามทั้งหน้าเลื่อนแนวนอน
- padding/ตัวอักษรไล่เล็ก→ใหญ่ (`p-3 sm:p-5`, `text-sm sm:text-base`)
- ตรวจจริงที่ 375px ก่อน แล้ว 768/1280

## เกณฑ์ผ่าน (Claude ตรวจเอง — ห้ามเชื่อรายงาน)
1. `npx vitest run` ผ่านหมด (ไม่ทำเทสต์เดิมพัง)
2. ทุก SFC parse ได้ (build ไม่ error) · ไม่มี `$fetch`/`axios` ใน component (grep ต้องว่าง)
3. ค่านิ้ว/แปลงเลขมาจาก `fingerEngine` ทั้งหมด (grep หา `digitToHand`/`fingersUp`/`numberToHands` ใน HandDisplay/service/composable)
4. สโมค `/practice/fingers` ที่ 375px: ด่าน1/ด่าน2 × โหมด read/make ทำงาน · ตอบถูก→เขียว · ผิด→แดง+เฉลย · สถิติเดิน · ไม่มี console error จาก component · ไม่มี horizontal page scroll
5. HandDisplay: เลข 8 = หัวแม่มือ+3 นิ้ว · เลข 0 = ไม่มีนิ้วกาง · มือซ้าย mirror
