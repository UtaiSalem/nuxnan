# แผนการรีไฟน์ Phaser Classroom Simulation (Refined v4)

## 5 Critical Bugs

| # | ปัญหา | จุดที่เห็น |
|---|---|---|
| **B1** | Floor clip — ชื่อใต้ desk row สุดท้ายถูกตัด | canvas height คงที่ 640px ไม่พอสำหรับ rows จำนวนมาก |
| **B2** | ประตูครึ่งล่างหลุดใต้ floor frame | `doorY = height - 82` ไม่ผูกกับขอบ floor |
| **B3** | Chip ชื่อลอยซ้ำกับที่นั่ง (ขวาล่าง) | `createDoorChip(mySeat)` ตำแหน่งผิด/ซ้ำซ้อน |
| **B4** | ครูยืนนิ่งไม่เดิน | `renderRoom()` ถูกเรียกบ่อยแล้ว destroy tween ตลอด |
| **B5** | Top wall สูงเกินไป | กิน vertical space (~22% ของ viewport) |

## 7 Visual Polish

| # | จุด |
|---|---|
| **P6** | ไม่มี empty seat placeholders | โต๊ะหายกะทันหันในแถวสุดท้าย |
| **P7** | Aisle ดูโปร่งบาง | ไม่รู้สึกเป็นพื้นเดิน |
| **P8** | ไม่มี lighting cone | ห้องดูแบน |
| **P9** | Teacher avatar เป็น initials | ไม่ใช่รูปจริง |
| **P10** | Desktop desk เล็กลอย | จาก canvas height ที่ fixed |
| **P11** | Board ไม่มี chalk texture | ดูสะอาดเกินไป |
| **P12** | Walker check-in ไม่มี trail | ขาด impact |

## ลำดับ Phase

### Phase A — Critical layout
- A1: Dynamic canvas height = `FLOOR_TOP + FRONT_WALKWAY + rows*rowGap + DOOR_AREA + FLOOR_BOTTOM`
- A2: `doorY = floorY + floorH - 24` (อิงขอบ floor)
- A3: ลบ door chip mySeat — ซ้ำซ้อน
- A4: ปรับ Top wall จาก `144 → 96px`

### Phase B — Differential render (แก้ teacher static)
- แยก `staticLayer` (background/walls/floor) ออกจาก `dynamicLayer` (seats/door/teacher)
- Static render ครั้งเดียว, Dynamic patch แทนการ destroy ทั้งหมด

### Phase C — Empty placeholders
- วาด Outline เส้นประสำหรับที่นั่งว่างจนครบ `totalSeats`

### Phase D — Atmosphere
- D1: ปรับ Aisle opacity และ gradient
- D2: เพิ่ม lighting cone จากกระดาน
- D3: เพิ่ม chalk dust บนกระดาน

### Phase E — Real avatar
- โหลดรูป Avatar จริงผ่าน Phaser loader พร้อม fallback initials

### Phase F — Walker polish
- เพิ่ม trail door → seat
- เพิ่ม sparkle/scale impact ตอนเดินถึงที่นั่ง

## ลำดับ Commit
1. **Phase A** — Layout & Clipping fixes
2. **Phase B** — Differential render & Teacher fix
3. **Phase C** — Empty placeholders
4. **Phase D** — Atmosphere & Lighting
5. **Phase E** — Real avatar implementation
6. **Phase F** — Walker animation polish
## 2026-06-16 Teacher patrol planning

- Attendance simulator payload already exposes `attendance.instructor { name, avatar }`, so teacher avatar can come directly from user profile data without API schema changes.
- Current Phaser scene already has a `teacher` state slot and an initial patrol loop scaffold, making this a frontend-first refinement centered in `attendancePhaserScene.ts`, `AttendancePhaserScene.vue`, and `AttendanceSimulatorShell.vue`.
- Plan focus: formalize a deterministic room path for the teacher, keep front-of-class pacing plus aisle traversal, and preserve reduced-motion/mobile fallbacks.
- Verification plan: inspect desktop/tablet/mobile paths, ensure no overlap with door check-in UI or student arrival walkers, and confirm avatar fallback when instructor avatar is missing.
