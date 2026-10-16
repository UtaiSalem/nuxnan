# สเปค: ปรับ HandDisplay ให้รูปมือสวย/ชัดขึ้น (SVG) — ให้ Codex ทำ, Claude ตรวจ

## เป้าหมาย
ไฟล์ `ui/components/games/fingers/HandDisplay.vue` ตอนนี้วาดมือด้วย div เป็นแท่ง ๆ ดูหยาบ
ให้ **เขียนใหม่ให้วาดด้วย SVG เป็นรูปมือที่ดูเป็นมือจริงขึ้น** (ฝ่ามือ + 5 นิ้ว โค้งมน) ตามรูปอ้างอิง:
- 0 = กำมือ (ไม่มีนิ้วกาง) · 1 = กางนิ้วชี้ · 2–4 = กางนิ้วชี้→ก้อยเพิ่มทีละนิ้ว · 5 = กางหัวแม่มืออย่างเดียว (นิ้วอื่นงอ) · 6–9 = หัวแม่มือ + กางนิ้ว 1–4

## ข้อบังคับ (ห้ามพลาด)
1. **แก้ไฟล์เดียว: `ui/components/games/fingers/HandDisplay.vue` เท่านั้น** ห้ามแตะไฟล์อื่น
2. **คง props/API เดิมทุกตัว** (เกมอื่นเรียกใช้อยู่ — FingerPractice, FingerMathPractice, AbacusBridge):
   `digit?: number` · `gesture?: HandGesture` · `side?: 'left'|'right'` (default 'right') · `label?: string` · `showValue?: boolean` · `compact?: boolean`
   ชื่อ/ชนิด props ต้องเหมือนเดิมเป๊ะ ไม่เพิ่ม required prop ใหม่
3. **ค่า/ท่าทางมาจาก `fingerEngine` เท่านั้น** ใช้ `digitToHand`, `handValue`, `fingersUp` (+ type `HandGesture`, `Finger`) จาก `~/components/games/fingers/fingerEngine` — ห้ามคำนวณว่านิ้วไหนกางเอง ให้ `fingersUp(hand)` เป็นตัวบอกว่านิ้วไหนกาง (คืน array ของ 'thumb'|'index'|'middle'|'ring'|'pinky')
4. **มือซ้าย (`side==='left'`) = สะท้อนแนวนอน** (`transform: scaleX(-1)` เฉพาะรูปมือ ไม่ครอบ label/ตัวเลข) — หัวแม่มือหันเข้าด้านใน
5. `compact` = ย่อขนาดมือลง (สำหรับการ์ดตัวเลือก) · ปกติมือใหญ่กว่า
6. คง `<span class="sr-only">ค่า {{ value }}</span>` และส่วน label/showValue ไว้เหมือนเดิม
7. **ห้าม** `$fetch`/axios · ห้าม commit · ห้าม `npm run build` · ห้าม migrate

## แนวทาง SVG (คำแนะนำ)
- วาดมือเป็น `<svg viewBox="0 0 100 120">` : ฝ่ามือเป็นสี่เหลี่ยมมุมมน + 4 นิ้ว (ชี้/กลาง/นาง/ก้อย) อยู่บน + หัวแม่มือด้านข้างเอียง ~35°
- นิ้วที่ "กาง" (อยู่ใน fingersUp) = วาดยาวขึ้น สีเด่น (อุ่น เช่น #fcd34d→#f59e0b หรือโทนผิว) · นิ้วที่ "งอ" = สั้น/จางลง
- ให้แต่ละนิ้วเป็น element แยก มี transition ตอนเปลี่ยน (ไม่บังคับแต่ดี) · ใส่ `aria-hidden="true"` ที่ svg
- โทนสี: เลือกให้เข้ากับ Vikinger/ดูเป็นมือ (ผิว #e8b48a ฝ่ามือ, นิ้วกาง highlight อุ่น) รองรับ dark mode พอประมาณ
- **สำคัญกว่าสวย = อ่านออกชัด**: ดูแล้วนับจำนวนค่าได้ถูก (0=กำมือ, 5=หัวแม่มืออย่างเดียว, 8=หัวแม่มือ+3, 9=หัวแม่มือ+4)

## เกณฑ์ผ่าน (Claude ตรวจเอง)
1. `git diff --stat` เปลี่ยนแค่ `HandDisplay.vue` ไฟล์เดียว
2. `npx vitest run` ผ่านหมด (51) · SFC parse ได้ · ไม่มี `$fetch`
3. สโมค 375px ทั้ง 4 หน้า (`/practice/fingers`, `/practice/bridge`, `/practice/finger-math`): มือเรนเดอร์ค่าถูก (ลองค่า 0,5,8,9 และสองมือเช่น 86), มือซ้าย mirror, การ์ดตัวเลือก (compact) ยังพอดี 2×2 ไม่ล้นจอ
4. props เดิมถูกใช้เหมือนเดิม (เกมอื่นไม่ต้องแก้)
