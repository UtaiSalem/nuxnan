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

## 2026-06-16 Phaser classroom visual refinement

- Active file: `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`
- Goal: pull the Phaser renderer back toward the cleaner layout language of `ClassroomSeatGrid.vue` without removing patrol/walker animation work already in progress.
- Implemented: larger framed blackboard with hanging brackets and date line, softer board light cone, top skirting + ambient floor glow, calmer aisle treatment, empty-seat avatar placeholders, and truncated seat names to reduce visual clutter.
- Verification: `.\node_modules\.bin\vue-tsc.cmd --noEmit` still fails on many pre-existing repo-wide TypeScript issues; relevant output still includes existing `attendancePhaserScene.ts` polygon typing errors and unrelated project-wide failures, so no clean green project signal yet.
- Risk: browser smoke verification is still pending because local browser automation was not available in this turn.

---

## Work Plan — Phaser Classroom Visual Refinement v5 (2026-06-16)

### 0. สรุปสถานะปัจจุบัน (ก่อนแก้)

**สิ่งที่ทำเสร็จแล้วใน commit `907dedc0`:**
- กระดานมีกรอบไม้ + bracket แขวน + วันที่ + ฝุ่นชอล์ก
- พื้นมี skirting ด้านบน + ambient glow
- Aisle ทำเป็น dashed center line
- Empty seat placeholder (เส้นประ + เลขที่นั่ง)
- ตัดชื่อนักเรียนสั้นลง (max 10–14 ตัวอักษร)
- Lighting cone จากกระดาน

**ปัญหาที่ยังเหลือ (จากการอ่านโค้ดล่าสุด):**

| รหัส | ปัญหา | บรรทัด/หลักฐาน |
|---|---|---|
| **R1** | TypeScript error: `desk.fillPoints(leftFace, true)` — `PolygonPoint[]` ไม่ assignable เป็น `Geom.Point[]` ที่ Phaser ต้องการ | createSeat() / updateSeatStatuses() — pre-existing repo-wide error |
| **R2** | กระดานยังอยู่บน "wall band" สูง 96px เดิม แต่ไม่มี shadow แยกจากผนัง → ดูแบนกับฉาก | drawBackground() L164–168, drawBoard() L176–244 |
| **R3** | Wall band 96px เป็นสีไม้แต่ไม่มี skirting/molding ด้านล่าง → รอยต่อระหว่างผนังกับพื้นแข็งทื่อ | drawBackground() vs drawFloor() |
| **R4** | ประตูถูก anchor ที่ `floorY + floorH - 24` ทำให้กรอบประตู (-8 ถึง +78) มีส่วนล่างที่ +54 อยู่นอก floor frame เมื่อ rows น้อย — ยังเสี่ยง clip ที่ขอบล่างจริง | drawDoor() L635–685 |
| **R5** | `doorChip` ที่ลบไปทำให้ผู้ใช้ไม่รู้ว่าเป็นที่นั่งของตัวเอง — แต่ตอนนี้ไม่มี indicator ใดบนประตูเลย | A3 over-corrected |
| **R6** | Lighting cone ใช้ fillTriangle ที่ทอดยาว 420px คงที่ → บน room สั้นมันทะลุพื้น/ประตู | drawLighting() L246–260 |
| **R7** | Empty placeholder วาดแค่ outline ไม่มี shadow/ground plane → "ลอย" ดูไม่กลมกลืนกับ desk จริง | createEmptySeat() L387–420 |
| **R8** | Teacher patrol บน tablet (640–1024) เดินอยู่หน้าห้องอย่างเดียว — แต่ aisle inspection ที่ disable ไว้ทำให้ดูซ้ำซาก | startTeacherPatrol() L909–911 |
| **R9** | `body` ของ seat (เสื้อ rectangle 16×10) ลอยอยู่เหนือ desk → ดูเหมือนตัวคนถูก amputate | createSeat() L485 |
| **R10** | ชื่อ component แสดงผลแล้วถูกตัดให้สั้นลง แต่ tooltip/hover ไม่ได้แสดงชื่อเต็ม → UX สูญหายข้อมูล | createSeat() L502–507 |

### 1. หลักการแก้รอบนี้

1. **ไม่รื้อ animation ที่ทำเสร็จแล้ว** — patrol/walker/arrival tweens เก็บไว้
2. **แก้ทีละ commit เล็ก** ตามเฟส ไม่รวมทุกอย่างใน commit เดียว (ตาม CLAUDE.md "commit เป็นชุดเล็กๆ")
3. **ทุกการแก้ visual ต้อง verify บน 3 viewport**: mobile (<480), tablet (640–1024), desktop (≥1024)
4. **TS error pre-existing ไม่บล็อกงาน** แต่ถ้าแตะฟังก์ชันนั้นแล้ว ให้แก้พร้อมกัน (R1)
5. **ไม่เพิ่ม feature ใหม่** — เน้นปรับความรู้สึก "เป็นระเบียบ + แพงขึ้น" เท่านั้น

### 2. แผน Phase ใหม่ (ทีละขั้นตอน)

#### **Phase G — Wall/Floor Junction (แก้ R2, R3)**
เป้า: ทำให้รอยต่อ ผนัง → พื้น ดูเป็นชั้น ๆ ไม่ใช่สองสี่เหลี่ยมชนกัน

ขั้นตอน:
- **G1**: ใน `drawBackground()` เพิ่ม molding strip สูง 8px สีเข้มกว่า wall (0x5C3D24) ที่ y=88–96 เพื่อแบ่ง wall จาก floor
- **G2**: เพิ่ม shadow gradient (alpha 0→0.25) ใต้ wall (y=96 ถึง 96+24) ตกลงบนพื้น → ผนังดูมี depth
- **G3**: ขยับ skirting ของพื้นจาก y=floorY+6 ขึ้นมาเป็น y=floorY+2 ให้ชน molding พอดี
- Verify: เปิดบราวเซอร์ดูว่ามี "เส้นเงา" ใต้ wall ชัดเจน

Commit: `feat(attendance): add wall molding and shadow junction`

#### **Phase H — Board Depth & Indicator (แก้ R2, R5)**
เป้า: กระดานมีเงาแยกจากผนัง + ประตูมี indicator ของผู้ใช้ปัจจุบัน

ขั้นตอน:
- **H1**: ใน `drawBoard()` เพิ่ม drop shadow ใต้ frame: `frame.fillStyle(0x000000, 0.18); frame.fillRoundedRect(boardX - boardWidth/2 + 4, boardY - BOARD_HEIGHT/2 + 6, boardWidth, BOARD_HEIGHT, 16)` วาด **ก่อน** frame หลัก
- **H2**: เพิ่ม chalk tray (ถาดวางชอล์ก) สีไม้เข้มใต้กระดาน: `rect(boardX - boardWidth/2 + 12, boardY + BOARD_HEIGHT/2 - 8, boardWidth - 24, 6)` พร้อมไอคอน chalk เล็ก ๆ 3 จุด (สีขาว/ชมพู/ฟ้า)
- **H3**: ใน `drawDoor()` เพิ่ม small badge ที่มุมขวาบนของกรอบประตู (ไม่ซ้อนกับ knob) แสดง "ทางเข้า" หรือ icon เล็ก ๆ — ใช้ text แทน chip ขนาดเล็ก
- Verify: กระดานต้องดู "ลอยอยู่หน้าผนัง" ไม่ใช่ "ฝังในผนัง"

Commit: `feat(attendance): add board shadow, chalk tray, door entry badge`

#### **Phase I — Door Geometry Fix (แก้ R4)**
เป้า: แก้การ clip ของประตูเมื่อ rows น้อย

ขั้นตอน:
- **I1**: คำนวณ `doorY = floorY + floorH - 54` (แทน -24) เพื่อให้กรอบประตูทั้งหมด (-8 ถึง +78 = 86px) อยู่ภายใน floor frame
- **I2**: ขยาย `FLOOR_BOTTOM` จาก 40 → 56 เพื่อรองรับ door action chip ที่ y=8 (มีความสูง 34px เพิ่มอีก 16px จาก center)
- **I3**: ปรับ `emitHeightChange()` ให้ใช้ค่า FLOOR_BOTTOM ใหม่
- Verify: ทดสอบ 1 row, 3 rows, 6 rows ว่าประตูไม่ถูก clip

Commit: `fix(attendance): keep door frame inside floor bounds for all row counts`

#### **Phase J — Lighting Cone Bounds (แก้ R6)**
เป้า: lighting cone ปรับตามความสูงห้องจริง

ขั้นตอน:
- **J1**: ส่ง `height` (canvas height ใช้แล้วใน `drawFloor`) เข้า `drawLighting()`
- **J2**: คำนวณ `coneEndY = Math.min(boardY + 420, height - FLOOR_BOTTOM - 80)` ป้องกัน overflow
- **J3**: ลด alpha cone จาก 0.05 → 0.04 และเพิ่ม inner cone สว่างกว่า (alpha 0.07) ครึ่งความสูง — ทำให้ดู "พุ่ง" จากกระดานมากกว่า flat overlay
- Verify: ห้องสั้นต้องไม่มี cone ทะลุพื้น/ประตู

Commit: `fix(attendance): clip lighting cone to room bounds and add inner highlight`

#### **Phase K — Empty Seat Polish (แก้ R7)**
เป้า: ที่นั่งว่างไม่ลอย

ขั้นตอน:
- **K1**: ใน `createEmptySeat()` เพิ่ม subtle ground shadow (ellipse 0xc4a677, alpha 0.15) ใต้ตำแหน่ง desk
- **K2**: เพิ่ม dashed top face (ไม่ใช่ solid stroke) ให้มี "rhythm" กับ desk จริง — ใช้ moveTo/lineTo เป็น segment สั้น
- **K3**: เพิ่มข้อความ "ว่าง" สีเทาอ่อนเล็ก ๆ ใต้เลขที่นั่ง (เฉพาะ desktop ที่ deskCellW > 80)
- Verify: empty seat ต้อง "ดูเป็นที่นั่งจริงที่ยังไม่มีคน" ไม่ใช่ "ภาพร่าง"

Commit: `feat(attendance): refine empty seat placeholder with shadow and label`

#### **Phase L — Seat Body Anatomy (แก้ R9)**
เป้า: ลำตัวนักเรียนต่อกับเก้าอี้/desk

ขั้นตอน:
- **L1**: ขยาย body จาก 16×10 → 20×14 และเลื่อนจาก y=-18 → y=-14 ให้ต่อกับ avatar (y=-28, radius 14 = bottom -14)
- **L2**: เพิ่ม shoulders (rect 26×4 ที่ y=-12 สีเข้มกว่า shirt) เพื่อ silhouette ดูมีคอ-ไหล่-ลำตัว
- **L3**: ปรับ body alpha สำหรับสถานะ LEAVE จาก 0.55 → 0.45 และเพิ่ม diagonal hatch ผ่าน fillStyle เพื่อแสดง "ลา" ชัดเจนกว่า
- Verify: นักเรียนต้องดูเป็น "คน" ไม่ใช่ "หัวลอย + กล่องสี"

Commit: `feat(attendance): improve student avatar body anatomy`

#### **Phase M — Name Tooltip (แก้ R10)**
เป้า: ชื่อที่ถูกตัดยังเข้าถึงได้

ขั้นตอน:
- **M1**: ใน `createSeat()` `pointerover` handler — ถ้าชื่อจริง > ความยาวที่แสดง ให้สร้าง tooltip text container ลอยเหนือ desk (y=-60) พร้อม background สีเข้ม
- **M2**: เก็บ reference ของ tooltip ใน container data เพื่อ destroy ตอน `pointerout`
- **M3**: tooltip ต้องอยู่ภายในขอบ canvas — clamp ตำแหน่ง x ถ้า seat อยู่ขอบ
- Verify: hover ที่นักเรียนชื่อยาวต้องเห็นชื่อเต็ม

Commit: `feat(attendance): show full name tooltip on seat hover`

#### **Phase N — TypeScript Hygiene (แก้ R1)**
เป้า: คลีน TS error ของ polygon

ขั้นตอน:
- **N1**: เปลี่ยน `PolygonPoint` type จาก `{x, y}` → ใช้ `Phaser.Types.Math.Vector2Like` หรือ cast ตอนส่ง: `desk.fillPoints(leftFace as Phaser.Geom.Point[], true)`
- **N2**: รัน `vue-tsc --noEmit | grep attendancePhaserScene` ต้องไม่เหลือ error จากไฟล์นี้
- Verify: TS เคลียร์เฉพาะไฟล์นี้ (ไม่บังคับทั้ง repo)

Commit: `fix(attendance): clean up Phaser polygon typing errors`

#### **Phase O — Tablet Teacher Patrol (แก้ R8)**
เป้า: tablet มี variation มากกว่า back-and-forth

ขั้นตอน:
- **O1**: ใน `startTeacherPatrol()` เปลี่ยน `isDesktop = width >= 1024` → `inspectChance = width >= 1024 ? 0.4 : width >= 768 ? 0.2 : 0`
- **O2**: ตอน tablet inspection ใช้แค่ aisle แรก (ไม่ random หลาย aisle) เพื่อลด chaos
- **O3**: เพิ่ม "ครุ่นคิด" indicator (จุดเล็ก ๆ ลอยเหนือหัว) ตอน pause นานเกิน 2 วินาที
- Verify: บน tablet ครูต้องเดินทั้งหน้าห้อง + บางครั้ง inspect aisle เดียว

Commit: `feat(attendance): add tablet teacher patrol variation`

### 3. ลำดับการ Execute

| ลำดับ | Phase | ความเสี่ยง | เวลาประมาณ |
|---|---|---|---|
| 1 | N (TS hygiene) | ต่ำ — non-visual | 10 นาที |
| 2 | I (Door clip) | กลาง — แก้ layout ต้อง verify หลาย viewport | 20 นาที |
| 3 | J (Lighting bounds) | ต่ำ | 10 นาที |
| 4 | G (Wall junction) | ต่ำ | 15 นาที |
| 5 | H (Board depth + door badge) | ต่ำ | 20 นาที |
| 6 | L (Body anatomy) | ต่ำ | 15 นาที |
| 7 | K (Empty seat) | ต่ำ | 10 นาที |
| 8 | M (Tooltip) | กลาง — ต้อง test hover behavior | 20 นาที |
| 9 | O (Tablet patrol) | ต่ำ | 15 นาที |

**รวม ≈ 2 ชั่วโมง 15 นาที** (9 commits เล็ก)

### 4. Verification Plan (รัน 1 ครั้งหลังทุก Phase)

1. **TS check**: `cd ui && .\node_modules\.bin\vue-tsc.cmd --noEmit 2>&1 | Select-String attendancePhaserScene` — ต้องไม่มี error ใหม่
2. **Dev server smoke**: `npm run dev` แล้วเปิด `/Learn/Courses/<id>/.../attendance` ใน 3 ขนาด: 380px, 800px, 1280px
3. **Interaction**: ทดสอบ hover seat, click door, click teacher, walker arrival
4. **Reduced motion**: เปิด DevTools → Rendering → Emulate `prefers-reduced-motion: reduce` → patrol/walker ต้องไม่เคลื่อน

### 5. Out of Scope (รอบนี้ไม่ทำ)

- ❌ เปลี่ยน Phaser → Pixi/Konva
- ❌ Refactor scene เป็น multiple scenes
- ❌ Server-side seat persistence
- ❌ Animation editor / timeline tool
- ❌ Sound effects
- ❌ A11y full audit (เกินขอบเขต visual refinement)

### 6. Risk & Rollback

- ทุก commit แยกอิสระ → revert ทีละตัวได้ด้วย `git revert <sha>`
- ถ้า Phase G/H ทำให้ใน production ดูแปลก → revert Phase นั้นเดียว ไม่กระทบ patrol/walker
- บันทึก screenshot ก่อน/หลังแต่ละ phase ไว้ใน `.agents/phaser-refinement-screenshots/` (ถ้าเปิด browser ได้)
