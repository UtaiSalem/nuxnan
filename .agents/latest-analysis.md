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

---

## 2026-06-17 School Student Master Profile analysis

- Scope: plan-only analysis for school member profile unification across member management, student profile, home visit, and student card subsystems.
- Files inspected:
  - `ui/pages/academies/[name]/students/[id]/profile.vue`
  - `ui/composables/useStudentProfile.ts`
  - `ui/pages/academies/[name]/admin/members/[memberId].vue`
  - `ui/components/academy/member/MemberManageModal.vue`
  - `ui/components/academy/member/StudentCardModal.vue`
  - `ui/pages/academies/[name]/my-card.vue`
  - `ui/pages/academies/[name]/admin/home-visits/index.vue`
  - `ui/pages/academies/[name]/admin/student-cards/index.vue`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Profile/StudentProfileController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/HomeVisit/AdminController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/HomeVisit/StudentController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php`
  - `api/nuxnanravel/app/Models/Student.php`
  - `api/nuxnanravel/app/Models/StudentCard.php`
  - `api/nuxnanravel/app/Models/StudentHomeVisit.php`
  - `api/nuxnanravel/app/Models/AcademyMember.php`
- Findings:
  - Current system already has 4 related but separate entities: `AcademyMember` (membership), `Student` (normalized student master), `StudentCard` (card-print identity data), and `StudentHomeVisit` (visit records).
  - Existing student profile page is read-only and academy-scoped, but it does not include home visit history, student card state, or self-service edit flows.
  - Member profile page is generic and course/activity oriented; it is not yet a true student master profile even when `academy_members.student_id` exists.
  - Student card subsystem is still largely isolated; linkage relies on matching `student.student_id <-> student_cards.student_number` or `citizen_id <-> national_id`, not an explicit FK.
  - Home visit subsystem is partially migrated to academy routes but many UI pages still call legacy `/api/home-visit/*` endpoints or keep fallback behavior.
  - Self student card retrieval already uses `Student->studentCard` accessor through heuristic matching, which is a useful bridge for the future profile page.
  - QR flow currently routes non-admin/non-teacher users to `/academies/${academyId}/members/${studentCode}`, which does not align cleanly with the academy student profile route and suggests navigation fragmentation.
- Risks / inconsistencies noticed:
  - `StudentCardModal` requests `/student-cards/profile/{member.student_id}` even though route-model binding suggests `{student_card}` is a student-card record id; fallback logic hides this mismatch.
  - `HomeVisit\AdminController` is not academy-scoped in statistics/dashboard methods, so academy management views risk cross-school totals unless replaced/refactored.
  - `StudentController::storeHomeVisit()` references `$student` without defining it before `StudentHomeVisit::createFromStudent($student, ...)`, indicating at least one broken path in current self-service home visit creation.
  - `AcademyMember` avatar path for student images (`/storage/images/students/profiles/...`) does not match common student card/profile image path usage (`/storage/images/students/{level}/{section}/...`).
  - Several home-visit admin pages still depend on legacy data shapes/field names, so unification must preserve compatibility or migrate in phases.
- Decision direction:
  - Use `Student` as the school student master profile source of truth.
  - Keep `AcademyMember` as access/identity wrapper and `StudentCard`/`StudentHomeVisit` as domain modules attached to the student master profile.
  - Prefer enhancing academy member/student profile flows over creating another disconnected subsystem.
- Intended implementation direction later:
  - Add a unified academy student profile API returning student core data + member context + student card summary + home visit summary/history.
  - Add role-based self-service edit capability for the student owner and managed edit capability for staff/admin.
  - Refactor school member/student management UI to open a single student master profile with tabs: overview, personal data, guardians/contacts/health, student card, home visits, school activity.
- Verification in this turn:
  - Read-only code inspection only; no runtime verification or file execution beyond searches/reads.

---

## Work Plan — School Student Master Profile Unification (2026-06-17)

### 0. ข้อค้นพบเพิ่มเติมที่ต้องนำมาวางในแผน

อ่านโค้ดเพิ่มหลังจากบทวิเคราะห์เดิม พบประเด็นที่ "บทวิเคราะห์ของผู้ใช้" ยังไม่ได้แตะ แต่จะกระทบรูปแบบ implementation อย่างมาก:

**A. Namespace mismatch ที่ต้องแก้ก่อนทำของใหม่**
`api/.../Http/Controllers/Api/Learn/Student/HomeVisit/` มีไฟล์เหล่านี้:
- `StudentAddressController.php`
- `StudentContactController.php`
- `StudentGuardianController.php`
- `StudentHealthController.php`
- `StudentAcademicInfoController.php`
- `StudentController.php` (CRUD นักเรียน)

→ controllers เหล่านี้คือ "student master data CRUD" แต่ถูกวางใต้ namespace `HomeVisit` เพราะถูกพัฒนามาเพื่อ flow เยี่ยมบ้านก่อน นี่คือ root cause ที่ทำให้การรวมระบบยาก ถ้าไม่ย้ายออกจะกลายเป็น "master profile ที่เรียก home visit controller" ซึ่งสับสนระยะยาว

**B. `StudentProfileController` มี role-based access pattern อยู่แล้ว**
- มี `checkAccess()` คืน `'none'|'limited'|'staff'|'owner'`
- มี `maskCitizenId()` ตาม access level
- ตรวจ homeroom teacher ผ่าน `ClassroomMember` + `classroomStudents`
- ใช้ pattern นี้เป็น base ของทุก endpoint master profile ใหม่ ไม่ต้องเขียน authorization ซ้ำ

**C. Student↔StudentCard ใช้ accessor (`getStudentCardAttribute`) ไม่ใช่ relation**
- `Student.php:227` ทำ query manual `WHERE student_number = student_id OR national_id = citizen_id`
- ผลคือ eager load ไม่ได้, N+1 risk บน list, และไม่มี integrity guarantee
- ต้องเพิ่มคอลัมน์ `student_cards.student_id` (FK) และทำ data migration จับคู่ของเก่า ก่อนถึงจะเปลี่ยน accessor → real `hasOne` relation

**D. `currentAcademicInfo()` ผิดรูปแบบ Eloquent**
- `Student.php:139-148` ใช้ `where(...)->orWhere(closure orderBy/limit)` ใน relation definition — limit ภายใน orWhere ไม่ทำงานตามคาด และทำให้ eager load คืนหลายแถว
- ต้องแก้ก่อนใช้ใน master profile API มิฉะนั้นข้อมูลชั้นเรียน/ปีการศึกษาจะมั่ว

**E. ปัญหาเฉพาะที่ confirm แล้ว**
- `HomeVisit/StudentController::storeHomeVisit()` อ้าง `$student` ก่อนประกาศ — broken path ที่ต้อง fix ระหว่างทาง
- `HomeVisit/AdminController` stats ไม่ scope ตาม academy
- `StudentCardModal` เรียก `/student-cards/profile/{member.student_id}` แต่ route binding คือ `{student_card}` id — fallback hide bug
- QR flow route `/academies/{id}/members/{studentCode}` ไม่ตรงกับ canonical student profile route

### 1. หลักการของแผนนี้ (เสริม/แก้ไขจากของผู้ใช้)

1. **ยึด `Student` เป็น master, `AcademyMember` เป็น membership wrapper** — ตามที่ผู้ใช้เสนอ ✅
2. **เพิ่ม: แก้ root cause ก่อน feature** — ย้าย namespace + เพิ่ม FK + แก้ relation bug ก่อน เพื่อไม่ให้แผนข้างบนกลายเป็นชั้นปะหน้าของระบบที่บิดเบี้ยว
3. **API ใหม่ทุกตัวต้อง academy-scoped** — `/api/academies/{academy}/students/{student}/...` เพื่อกัน cross-school leak ตั้งแต่ route layer
4. **Authorization ใช้ pattern เดียวจาก `StudentProfileController::checkAccess`** — สกัดออกเป็น `StudentProfilePolicy` หรือ trait
5. **Backward compatibility ผ่าน deprecation, ไม่ใช่ dual-write** — legacy `/api/home-visit/*` route map → call ใหม่ + ส่ง `Deprecation` header แล้วทยอยปิด
6. **แต่ละ phase ต้อง deployable เดี่ยว** — ไม่มี phase ที่พัง production ระหว่างกลาง

### 2. Data Contract — `student_master_profile` Response Shape

ผู้ใช้ระบุ section แล้ว แต่ยังไม่ได้กำหนด field และ access-level matrix ที่ละเอียดพอจะเขียน FormRequest ได้ จัดเต็มที่นี่:

```jsonc
{
  "permissions": {
    "level": "owner|staff|limited|none",
    "can_edit": {
      "personal": true,
      "contacts": true,
      "addresses": true,
      "guardians": false,
      "health": false,
      "academic": false,
      "student_card": false,
      "home_visit": false
    },
    "can_view_sensitive": {
      "citizen_id_full": false,
      "income": false,
      "health_notes": false
    }
  },
  "member": {
    "id": 12, "academy_id": 3, "role": "student",
    "joined_at": "...", "status": "active", "avatar_url": "..."
  },
  "student": {
    "id": 88, "student_id": "67001", "citizen_id": "1-1234-XXXXX-XX-X",
    "title_prefix_th": "...", "first_name_th": "...", "last_name_th": "...",
    "nickname": "...", "date_of_birth": "...", "gender": 1, "gender_text": "ชาย",
    "nationality": "...", "religion": "...", "profile_image_url": "...",
    "status": "active", "age": 14
  },
  "academic": {
    "current": { "academic_year": "...", "grade": "...", "class": "...", "classroom_full": "..." },
    "history": [ /* StudentAcademicInfo[] desc */ ],
    "classroom": { "id": 5, "display_name": "ม.2/3", "homeroom_teacher": {...} }
  },
  "contacts":  [ /* StudentContact */ ],
  "addresses": [ /* StudentAddress */ ],
  "guardians": [ /* StudentGuardian with income masked per level */ ],
  "health":    { /* StudentHealthInfo or null, sensitive fields masked */ },
  "student_card": {
    "exists": true, "id": 42, "card_number": "...", "issued_at": "...", "expires_at": "...",
    "photo_status": "approved|pending|missing", "preview_url": "...",
    "can_print": true, "match_strategy": "fk|legacy_student_number|legacy_citizen_id"
  },
  "home_visit": {
    "total_visits": 4, "latest": { "id": 17, "visited_at": "...", "status": "...", "teacher": {...} },
    "next_scheduled": { "id": 21, "scheduled_at": "..." } | null,
    "recent": [ /* up to 5 */ ]
  },
  "school_activity": {
    "courses_count": 6, "attendance_rate_30d": 0.94, "points": 1200, "last_active_at": "..."
  }
}
```

**Access-level matrix (ใช้เป็นกฎ mask + edit gate):**

| Section          | owner       | staff       | limited (parent/teacher relative) | none |
|---|---|---|---|---|
| personal core    | R+W*        | R+W         | R                                  | -    |
| citizen_id       | R-masked    | R-full      | R-masked-last4                    | -    |
| addresses        | R+W*        | R+W         | R                                  | -    |
| contacts         | R+W         | R+W         | R                                  | -    |
| guardians        | R+W*        | R+W         | R (income masked)                 | -    |
| health           | R+W*        | R+W         | -                                  | -    |
| academic         | R           | R+W         | R                                  | -    |
| student_card     | R           | R+W         | R                                  | -    |
| home_visit list  | R-own       | R+W         | R-own-relations                   | -    |
| school_activity  | R           | R           | R                                  | -    |

`W*` = นักเรียนแก้ได้เฉพาะ field ที่ academy เปิด flag `student_editable_fields` ให้

### 3. Phase-by-Phase Plan (ทดแทน/ขยาย "แผนพัฒนา" ของผู้ใช้)

#### **Phase 0 — Preflight & Safety (1 PR, ~1 ชม.)**
ไม่เปลี่ยน behavior แต่เคลียร์พื้น
- 0.1 เขียน feature branch `feature/student-master-profile`
- 0.2 เพิ่ม CI grep ห้ามมี `/api/home-visit/*` route ใหม่ ใน `tests/Architecture/` (หรือ comment guard ใน `routes/api.php`)
- 0.3 backup DB snapshot ของ `students`, `student_cards`, `academy_members`, `student_home_visits` (เก็บ dump ไว้ใน `.agents/backups/2026-06-17/`)
- 0.4 รัน `php artisan route:list --path=student` เก็บ baseline ใน `.agents/routes-baseline.txt`

**Deliverable:** branch ใหม่ + baseline เอกสาร

#### **Phase 1 — Schema Hardening (1 PR, ~3 ชม.)**
แก้ root cause ก่อนทำ feature
- 1.1 Migration: `add_student_id_to_student_cards_table` — เพิ่ม `student_id` (FK nullable → `students.id`), index, `onDelete('set null')`
- 1.2 Data migration: artisan command `students:backfill-card-link` ใช้ logic เดียวกับ `getStudentCardAttribute` แต่เขียนค่า FK ลงคอลัมน์ใหม่ + log ของที่จับคู่ไม่ได้
- 1.3 แก้ `Student::studentCard()` จาก accessor → `hasOne(StudentCard::class)` (ยังคง legacy accessor เป็น `legacyStudentCard` ไว้ก่อน 1 sprint)
- 1.4 แก้ `Student::currentAcademicInfo()` ให้เป็น relation ที่ถูกต้อง: `hasOne(StudentAcademicInfo::class)->where('is_current', true)->latestOfMany('academic_year')`
- 1.5 แก้ bug `HomeVisit/StudentController::storeHomeVisit()` (`$student` undefined) — resolve `$student = $request->user()->student ?? abort(404)`
- 1.6 Migration: เพิ่ม column `academies.student_editable_fields` (json) สำหรับ Phase 7 (default `["nickname","contacts","addresses"]`)
- 1.7 Test: feature test `StudentCardLinkTest` ยืนยันว่า card ที่ match แล้วโหลดผ่าน relation FK ได้

**Deliverable:** migrations + backfill + bugfix + 1 test

#### **Phase 2 — Controller Namespace Refactor (1 PR, ~2 ชม.)**
ย้าย student master data controllers ออกจาก `HomeVisit/` ไป `StudentMaster/` (ไม่เปลี่ยน behavior)
- 2.1 สร้าง folder `app/Http/Controllers/Api/Learn/Student/Master/`
- 2.2 ย้ายและ rename namespace:
  - `HomeVisit/StudentController` → `Master/StudentController`
  - `HomeVisit/StudentAddressController` → `Master/AddressController`
  - `HomeVisit/StudentContactController` → `Master/ContactController`
  - `HomeVisit/StudentGuardianController` → `Master/GuardianController`
  - `HomeVisit/StudentHealthController` → `Master/HealthController`
  - `HomeVisit/StudentAcademicInfoController` → `Master/AcademicInfoController`
- 2.3 เก็บ route paths เดิมไว้ทั้งหมด (ชี้ไป controller ใหม่) เพื่อไม่ broke frontend
- 2.4 รัน `php artisan route:list` เทียบกับ baseline จาก 0.4 — diff ต้องเป็น "namespace เปลี่ยน, path เดิม"
- 2.5 ./vendor/bin/pint

**Deliverable:** PR ที่ git diff ดูคล้าย rename อย่างเดียว

#### **Phase 3 — Unified Master Profile API (read-only) (1 PR, ~5 ชม.)**
สร้าง endpoint รวม section ทั้งหมด
- 3.1 สร้าง `StudentMasterProfilePolicy` (extract `checkAccess` จาก `StudentProfileController`)
- 3.2 สร้าง `StudentMasterProfileResource` (API Resource) ตาม shape ใน §2
- 3.3 สร้าง `MasterProfileController@show`:
  - Route: `GET /api/academies/{academy}/students/{student}/master-profile`
  - Eager load: `currentAcademicInfo, primaryAddress, contacts, guardians, healthInfo, studentCard, homeVisits` ใช้ relation ใหม่จาก Phase 1
  - ส่งผ่าน Resource → apply mask ตาม `permissions.level`
- 3.4 เพิ่ม `summary` endpoint (lightweight, สำหรับ member list / QR landing): `GET /api/academies/{academy}/students/{student}/master-profile/summary`
- 3.5 Feature tests:
  - owner เห็น citizen_id เต็ม
  - limited (parent) เห็น masked
  - none ได้ 403
  - staff (academy admin) เห็น health + income
  - cross-academy student → 404

**Deliverable:** 2 endpoints + Resource + Policy + 5 tests

#### **Phase 4 — Frontend Master Profile Shell (1 PR, ~6 ชม.)**
ขยายหน้า read-only เดิมให้รองรับ section ครบ
- 4.1 สร้าง composable `useStudentMasterProfile(academyId, studentId)` แทนที่/wrap `useStudentProfile`
- 4.2 ขยาย `pages/academies/[name]/students/[id]/profile.vue` ให้เป็น **tabbed shell**:
  - Tabs: `ภาพรวม | ข้อมูลส่วนตัว | ที่อยู่/ติดต่อ | ผู้ปกครอง | สุขภาพ | การศึกษา | บัตรนักเรียน | เยี่ยมบ้าน | กิจกรรม`
  - แต่ละ tab → component ใน `components/student/profile/` (สร้างใหม่: `OverviewTab.vue`, `PersonalTab.vue`, `ContactsTab.vue`, `GuardiansTab.vue`, `HealthTab.vue`, `AcademicTab.vue`, `StudentCardTab.vue`, `HomeVisitTab.vue`, `ActivityTab.vue`)
  - ทุก tab รับ prop `profile: StudentMasterProfile` + `permissions` — ยังไม่ทำ edit
- 4.3 ใน `StudentCardTab.vue` แสดง card preview ใช้ component เดิมจาก `StudentCardModal` (extract → `StudentCardPreview.vue`)
- 4.4 ใน `HomeVisitTab.vue` แสดง latest + history ใช้ component จากหน้า home visit list ปัจจุบัน
- 4.5 ทำ skeleton loader ระดับ tab

**Deliverable:** 1 หน้า tabbed + 9 tab components (read-only)

#### **Phase 5 — Navigation Unification (1 PR, ~2 ชม.)**
ทุกทางเข้าต้องลงโปรไฟล์เดียวกัน
- 5.1 แก้ QR flow: เปลี่ยน `/academies/{id}/members/{studentCode}` → resolve เป็น `students.id` แล้ว navigate `/academies/{name}/students/{id}/profile`
- 5.2 แก้ `MemberManageModal` "ดูโปรไฟล์" → ถ้า member.student_id มี → ลงหน้า master profile, ถ้าไม่มี → คงเดิม
- 5.3 แก้หน้า `admin/student-cards/index.vue` "ดูรายละเอียด" → ลงหน้า master profile tab=student_card
- 5.4 แก้หน้า `admin/home-visits/index.vue` row click → master profile tab=home_visit
- 5.5 แก้ `admin/members/[memberId].vue`: ถ้า member มี `student_id` → 302/router.replace ไป master profile

**Deliverable:** unified entry — ตรวจด้วย click test 4 จุด

#### **Phase 6 — Self-Service Route (1 PR, ~2 ชม.)**
นักเรียนเข้าโปรไฟล์ตัวเองได้
- 6.1 สร้าง route `/academies/[name]/me/profile.vue` → resolve `student_id` ของ user ปัจจุบันใน academy นี้ → reuse component จาก Phase 4 (ไม่ duplicate)
- 6.2 ถ้า user ไม่มี Student ใน academy นี้ → แสดง empty state "ไม่ใช่นักเรียนของโรงเรียนนี้"
- 6.3 เพิ่ม link ใน user dropdown / academy sidebar

**Deliverable:** 1 route + sidebar link

#### **Phase 7 — Sectional Edit Endpoints (2 PRs, ~6 ชม. รวม)**
แยกเป็น 2 PR ลดความเสี่ยง

**PR 7a — Personal + Contacts + Addresses (สิ่งที่ student แก้เองได้)**
- 7a.1 FormRequest แยกหมวด: `UpdatePersonalRequest`, `UpdateContactRequest`, `UpdateAddressRequest`
- 7a.2 Endpoints (ทั้งหมด academy-scoped):
  - `PATCH /api/academies/{academy}/students/{student}/personal`
  - `PUT/POST/DELETE /api/academies/{academy}/students/{student}/contacts/{contact?}`
  - `PUT/POST/DELETE /api/academies/{academy}/students/{student}/addresses/{address?}`
- 7a.3 Authorization: owner ต้องผ่าน `student_editable_fields` whitelist; staff bypass
- 7a.4 frontend: เปลี่ยน read-only tabs ให้มี edit mode (ปุ่ม "แก้ไข" + form + save)
- 7a.5 Tests: owner แก้ nickname ได้, owner แก้ first_name_th ไม่ได้ (ถ้าไม่อยู่ใน whitelist)

**PR 7b — Guardians + Health + Academic + Photo (staff-only หรือ approval)**
- 7b.1 FormRequest + endpoints แบบเดียวกัน
- 7b.2 owner edit → ส่งเข้า approval queue (ใหม่: table `student_profile_change_requests`) — pending จนกว่า staff approve
- 7b.3 Notification: ครูประจำชั้นได้ noti เมื่อ owner ขอแก้ guardian/health
- 7b.4 Tests

**Deliverable:** 2 PR แยก, ความเสี่ยงคนละชั้น

#### **Phase 8 — Student Card Module Integration (1 PR, ~3 ชม.)**
- 8.1 `StudentCardTab.vue` เพิ่ม edit mode สำหรับ staff: แก้รูป, แก้ข้อมูลบัตร, regenerate preview
- 8.2 Endpoints: ใช้ของเดิมจาก `StudentCardController` แต่ wrap ผ่าน academy-scoped route ใหม่ `POST /api/academies/{a}/students/{s}/card/photo` ฯลฯ
- 8.3 ลบ/redirect หน้า `my-card.vue` → tab `student_card` ของ self-service profile
- 8.4 แก้ bug `StudentCardModal` ที่เรียก path ผิด — ส่งผ่าน `student.id` ตรง
- 8.5 หน้า `admin/student-cards/index.vue` ปรับเป็น "list + filter" เท่านั้น, ตัด detail panel ทิ้ง (ใช้ master profile แทน)

**Deliverable:** card integrated + 1 หน้าโดดถูก deprecate

#### **Phase 9 — Home Visit Module Integration (1 PR, ~4 ชม.)**
- 9.1 `HomeVisitTab.vue` รองรับ create/edit visit (staff) + acknowledge (student/parent)
- 9.2 ย้าย endpoint admin home visit ไป academy-scoped: `/api/academies/{a}/home-visits/...` + scope stats ตาม academy
- 9.3 Legacy `/api/home-visit/*` → controller ใหม่ wrap + ส่ง `Deprecation: true` header + log
- 9.4 แก้หน้า `admin/home-visits/index.vue` ใช้ route ใหม่ เลิก fallback
- 9.5 หลัง 1 sprint ที่ไม่มี traffic legacy → ลบ route เก่า

**Deliverable:** home visit ครบใน master profile + legacy ถูก mark deprecated

#### **Phase 10 — Cleanup & Docs (1 PR, ~1 ชม.)**
- 10.1 ลบ component/page ที่ deprecated (`my-card.vue` หลัง redirect 1 sprint, MemberProfile ที่ถูกแทน)
- 10.2 อัพเดท `.agents/worklog.md`
- 10.3 เขียน `docs/student-master-profile.md` (API contract + permission matrix + นาวิเกชัน)
- 10.4 บันทึก memory: เพิ่ม `project_student_master_profile.md` ใน MEMORY.md

### 4. Execution Order Summary

| ลำดับ | Phase | ประเภท | เวลา | ความเสี่ยง |
|---|---|---|---|---|
| 1 | 0 Preflight | Ops | 1 ชม. | ต่ำ |
| 2 | 1 Schema | Backend/DB | 3 ชม. | กลาง — ต้อง backfill ของจริง |
| 3 | 2 Namespace refactor | Backend | 2 ชม. | ต่ำ (rename) |
| 4 | 3 Master profile API | Backend | 5 ชม. | กลาง |
| 5 | 4 Tabbed shell | Frontend | 6 ชม. | กลาง — UI ใหญ่ |
| 6 | 5 Nav unification | Frontend | 2 ชม. | ต่ำ |
| 7 | 6 Self-service route | Frontend | 2 ชม. | ต่ำ |
| 8 | 7a Edit (student) | Full-stack | 3 ชม. | กลาง |
| 9 | 7b Edit (staff/approval) | Full-stack | 3 ชม. | สูง — มี approval flow |
| 10 | 8 Card integration | Full-stack | 3 ชม. | กลาง |
| 11 | 9 Home visit integration | Full-stack | 4 ชม. | สูง — legacy traffic |
| 12 | 10 Cleanup | Cleanup | 1 ชม. | ต่ำ |

**รวม ≈ 35 ชม.** กระจาย ~10 PRs แยกอิสระ revert ได้

### 5. Verification Plan (ต่อ phase)

ทุก phase:
1. `cd api/nuxnanravel && ./vendor/bin/pint && php artisan test --filter=StudentMaster`
2. `cd ui && npm run build` (catch SSR error ตาม [[feedback_ssr_ipc_crash]])
3. Manual smoke ตาม role: owner / parent / homeroom teacher / academy admin
4. ตรวจ permissions matrix ตาม §2 (ห้าม leak field)

หลัง Phase 3, 5, 7, 9: รัน `php artisan route:list --json > .agents/routes-after-pX.txt` แล้ว diff กับ baseline เพื่อ track surface change

### 6. Risk Register (อัพเดทจากของผู้ใช้)

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Card backfill จับคู่ผิด (Phase 1.2) | กลาง | สูง | dry-run + report ก่อน write จริง; ผู้ใช้ review log |
| Frontend tab shell SSR crash | กลาง | กลาง | dev test ทุก tab ใน `npm run dev` ก่อน commit; reference [[feedback_ssr_ipc_crash]] |
| Self-service edit เปิดให้แก้ field ที่ไม่ควร | ต่ำ | สูง | whitelist `student_editable_fields` ตั้ง default แคบ; เพิ่ม audit log table |
| Legacy `/api/home-visit/*` ยังมี traffic ตอนปิด | สูง | กลาง | Phase 9 ปิด route หลังเฝ้า log 2 สัปดาห์ ไม่ลบใน PR เดียวกับที่เพิ่มใหม่ |
| Approval queue (Phase 7b) ค้างนาน | กลาง | กลาง | TTL + auto-reject 30 วัน + noti reminder |
| Academy ที่มีนักเรียนเยอะ master profile API ช้า | กลาง | กลาง | Phase 3 ใช้ `select(...)` แบบจำกัด + เพิ่ม index (`student_cards.student_id` มีอยู่แล้วจาก 1.1) |

### 7. Out of Scope (รอบนี้)

- ❌ Parent self-service portal (เป็นแค่ viewer)
- ❌ ระบบ enrollment / transfer / drop-out (เป็น flow แยก)
- ❌ Marketplace, Wallet, Course progress (อยู่ใน school_activity แค่ summary)
- ❌ Migration from `AcademyMember` → `Student` สำหรับ member ที่ยังไม่มี student record (จัดการนอกแผนนี้)
- ❌ Bulk import students (มี flow เดิมอยู่แล้ว)
- ❌ A11y full audit, i18n full

### 8. Decisions Locked (2026-06-17)

1. **`student_editable_fields` default = กว้าง** — ทุก field ยกเว้น `citizen_id`, `student_id`, `academic.*`, `health.*` (นักเรียนแก้เองได้ทันที ไม่ต้อง approval)
   - กระทบ Phase 1.6: เปลี่ยน default seed JSON ของ `academies.student_editable_fields` เป็น blacklist style → `{"mode":"blacklist","fields":["citizen_id","student_id","academic","health"]}`
   - กระทบ Phase 7a/7b: 7a ขยาย scope รวม `personal core, contacts, addresses, guardians, religion, profile_image, nickname`; 7b เหลือแค่ `academic, health` + field ใน blacklist (ที่ต้อง approval เสมอ)

2. **Approval flow = configurable per academy** — เพิ่ม setting `academies.approval_flow` (single | two_level)
   - กระทบ Phase 1.6: เพิ่ม column นี้ด้วย (default `single`)
   - กระทบ Phase 7b: ทำ `ApprovalChainResolver` service เลือก approver ตาม config; ไม่ hardcode

3. **Legacy `/api/home-visit/*` ไม่มี external caller** — Phase 9.3/9.5 ตัดทอนได้
   - ไม่ต้องส่ง `Deprecation` header ระยะยาว
   - หลังหน้า admin home visits ใช้ route ใหม่ครบใน Phase 9.4 + เฝ้า log 1 สัปดาห์ → ลบ legacy route ใน Phase 10 ได้เลย
   - ประหยัดเวลา ~1 ชม.

4. **Photo storage แยก profile photo กับ card photo** — keep ของเดิม
   - กระทบ Phase 8.2: ไม่ unify path; แต่เพิ่ม **sync trigger** เมื่อ staff approve card photo → ถามว่าอยากใช้เป็น profile photo ด้วยไหม (optional, ไม่ auto)
   - Master profile API คืน 2 url แยก: `student.profile_image_url` + `student_card.preview_url`

### 9. Decisions Locked (เพิ่มเติม 2026-06-17)

5. **Audit log = ใช้ `App\Traits\Auditable` + `App\Models\AuditLog` ที่มีอยู่แล้วในโปรเจค** (ระบบ in-house, ไม่ใช่ Spatie/owen-it)
   - มีโมเดลที่ใช้อยู่แล้ว: `Budget`, `Expense`, `EmergencyAlert`, `ExpenseCategory` ฯลฯ ดู `config/audit.php` (env `AUDIT_ENABLED`)
   - กระทบ Phase 1: เพิ่ม `use Auditable` ใน `Student`, `StudentAddress`, `StudentContact`, `StudentGuardian`, `StudentHealthInfo`, `StudentAcademicInfo`, `StudentCard`, `StudentHomeVisit`
   - กระทบ Phase 7a/7b: ไม่ต้องสร้าง `student_profile_audit_logs` table; query `AuditLog` ด้วย `auditable_type` + `auditable_id` แสดง history ใน UI
   - Phase 4: เพิ่ม sub-tab "ประวัติการแก้ไข" ใน Personal/Contacts/Guardians tab (เห็นเฉพาะ staff)
   - **Action ก่อน Phase 0**: อ่าน `App\Traits\Auditable` 1 ครั้ง เพื่อ confirm signature (event hooks, fields tracked, exclusion list)

→ **พร้อม implement ตั้งแต่ Phase 0 ครับ** ไม่มี open question เหลือ
---

## 2026-06-20 Academy Student Self Profile / Card Bug Analysis

- Scope: read-only investigation for `/academies/[name]/my-profile` and academy student self-service flows.
- Root cause 1 (`500 /api/academies/my-student-card`): `Student::studentCard()` in `api/nuxnanravel/app/Models/Student.php` uses a default `hasOne(StudentCard::class)` relation, so Laravel queries `student_cards.student_id`. The current `student_cards` table does not have that column. Confirmed in `api/nuxnanravel/storage/logs/laravel.log` at `2026-06-20 01:20:13` and `01:20:29`, with the stack ending in `App\Http\Controllers\Api\Learn\Academy\ClassroomController::getMyStudentCard()` line 696.
- Root cause 2 (`404 /api/academies/%25E0...`): academy pages such as `ui/pages/academies/[name].vue` and `ui/pages/academies/[name]/my-card.vue` call `encodeURIComponent(academyName.value)` before requesting `/api/academies/{academy:name}`. This double-encodes Thai academy names and causes Laravel route-model binding to miss the academy.
- Root cause 3 (Vue runtime warnings on profile cards): `ui/components/learn/student/ProfileViewCards.vue` exports many `defineComponent({... template: ...})` objects from a `.vue` file. The app uses the runtime-only Vue build, so these inline template strings cannot compile at runtime and emit repeated warnings for all profile cards.
- Affected flow notes:
  - `ui/components/learn/academy/StudentCardWidget.vue` and `ui/pages/academies/[name]/my-card.vue` both hit `/api/academies/my-student-card`, so the broken relation impacts both sidebar card and full card page.
  - `ui/pages/academies/[name]/my-profile.vue` loads profile data through the student-profile API, but the surrounding academy page flow still triggers the broken student-card widget and profile card warnings.
- Suggested fix order:
  1. Align `Student::studentCard()` with the real `student_cards` schema or avoid the broken relation path until schema normalization is complete.
  2. Stop double-encoding academy route params when calling `/api/academies/{academy:name}`.
  3. Refactor `ProfileViewCards.vue` into real SFC components or render functions instead of runtime `template` strings.
- Verification plan after implementation:
  - reload `/academies/<thai-name>/my-profile`
  - confirm `GET /api/academies/my-student-card?academy_id=1` returns 200
  - confirm `GET /api/academies/<thai-name>` returns 200 without `%25` in the URL
  - confirm console no longer shows runtime-compilation warnings for profile cards

---

## Work Plan — Academy Student Self Profile / Card Bug Fix (2026-06-20)

### 0. ข้อค้นพบเพิ่มเติมที่ทำให้แผนเปลี่ยนรูป

อ่านโค้ดจริงเพิ่มหลังบทวิเคราะห์ พบ 3 จุดที่เปลี่ยน scope:

**A. Migration เพิ่มคอลัมน์ `student_cards.student_id` มีอยู่แล้วแต่ Pending**
- ไฟล์: `database/migrations/2026_06_18_013941_add_student_id_to_student_cards_table.php`
- `php artisan migrate:status` ยืนยัน: **Pending**
- ดังนั้น root cause ของ R1 (500) คือ **migration ยังไม่ถูกรัน** บน DB ปัจจุบัน ไม่ใช่ "ไม่เคยออกแบบ"
- ฝั่ง `Student::studentCard()` ใช้ `hasOne(StudentCard::class)` (ไม่ใช่ accessor) ตามแผน Phase 1 ของ work plan ก่อนหน้า — แต่ relation พังเพราะคอลัมน์ที่ relation อ้างยังไม่มี
- `getLegacyStudentCardAttribute` + `getStudentCardAttribute` (fallback) ยังอยู่ — เป็น safety net ที่ตั้งใจไว้ แต่ Eloquent ยิง real relation query **ก่อน** ลง fallback จึงเกิด SQL error 500
- → งานหลักของ R1 คือ **รัน migration + backfill + verify** ไม่ใช่สร้าง schema ใหม่

**B. Double-encoding เป็นปัญหา repo-wide ไม่ใช่จุดเดียว**
- `Grep encodeURIComponent\(academyName` พบ **60+ ไฟล์** ใน `ui/pages/academies/[name]/**` และ `ui/layouts/academy-admin.vue`, `ui/components/learn/academy/StudentCardWidget.vue`
- `route.params.name` ใน Vue Router 4 / Nuxt 3 ถูก decode มาแล้ว → ส่งให้ `encodeURIComponent` ซ้ำตอนที่ HTTP client (ofetch) สามารถจัดการ UTF-8 path ได้เองอยู่แล้วในบางเส้นทาง ทำให้ลงเอยที่ `%25E0...`
- การแก้แบบไฟล์ต่อไฟล์จะลืม — ควรแก้แบบ codemod (sed/replace_all) เพื่อให้ครบในรอบเดียว

**C. `ProfileViewCards.vue` ใช้ `defineComponent({ template: ... })` 7 ตัว**
- บรรทัด 20, 156, 232, 319, 394, 460, 542
- เพราะ Vue runtime-only build เลย compile template string runtime ไม่ได้ → warning 7 ตัว ทุกครั้งที่เปิด `my-profile.vue`
- การ refactor เป็น SFC แยกไฟล์ละ component จะปลอดภัยที่สุด แต่กระทบ import ของ `my-profile.vue`
- ทางเลือก: ใช้ `h()` render function แทน template string → ไม่ต้องแยกไฟล์, เปลี่ยนเฉพาะ method body ของแต่ละ component

### 1. หลักการของแผนนี้

1. **แก้ user-visible 500/404 ก่อน warning** — R1 (500) และ R2 (404) บล็อก feature; R3 (warning) เป็น noise
2. **ทุก commit deployable เดี่ยว** — revert ได้ทีละจุด
3. **ไม่ขยาย scope ไปแตะ student master profile rewrite** (ของแผน 2026-06-17) — แค่ทำให้ตัว `getMyStudentCard` กลับมาทำงาน
4. **Codemod แทน hand-edit ใน B** — ใช้ search/replace กับ pattern เดียวให้ครบใน PR เดียว
5. **Verify ทุก phase ด้วยการ reload หน้าจริง** — ห้ามอ้าง "อ่านโค้ดแล้วถูก"

### 2. Phase-by-Phase Plan

#### **Phase 1 — Pre-flight Backup & Inspect (10 นาที, ไม่เปลี่ยน behavior)**

- 1.1 ตรวจสภาพ DB ก่อน
  - `php artisan migrate:status | grep student_card` → ยืนยัน pending
  - `php artisan tinker --execute="echo DB::table('student_cards')->count();"` เก็บจำนวนแถวก่อน
  - `mysqldump -u root nuxnan student_cards > .agents/backups/2026-06-20/student_cards.sql` (backup)
- 1.2 ตรวจว่า matching key ใช้ได้จริง — query dry-run:
  ```sql
  SELECT sc.id, sc.student_number, sc.national_id, s.id AS student_pk, s.student_id, s.citizen_id
  FROM student_cards sc
  LEFT JOIN students s ON (sc.student_number = s.student_id OR sc.national_id = s.citizen_id)
  WHERE s.id IS NULL;
  ```
  - นับ orphan ที่จับคู่ไม่ได้
- 1.3 บันทึก count + ตัวอย่าง orphan ลง `.agents/backups/2026-06-20/preflight.md`

**Deliverable:** backup + preflight report  
**Commit:** ไม่มี (สำรวจอย่างเดียว)

#### **Phase 2 — Run Migration + Backfill `student_cards.student_id` (R1) (30 นาที)**

- 2.1 รัน migration: `php artisan migrate --path=database/migrations/2026_06_18_013941_add_student_id_to_student_cards_table.php`
- 2.2 เขียน artisan command `app/Console/Commands/BackfillStudentCardLinks.php`:
  ```php
  StudentCard::whereNull('student_id')->chunkById(500, function ($cards) {
      foreach ($cards as $card) {
          $student = Student::where('student_id', $card->student_number)
              ->orWhere('citizen_id', $card->national_id)
              ->first();
          if ($student) {
              $card->student_id = $student->id;
              $card->saveQuietly();
          }
      }
  });
  ```
  - รองรับ `--dry-run` แสดงจำนวนที่จะ match
- 2.3 รัน `php artisan students:backfill-card-link --dry-run` → review log
- 2.4 รันจริง `php artisan students:backfill-card-link`
- 2.5 Verify: `GET /api/academies/my-student-card?academy_id=1` ต้องคืน 200 + `studentCard` ของ user ที่มี link
- 2.6 ตรวจ `storage/logs/laravel.log` ต้องไม่มี "Unknown column 'student_cards.student_id'" อีก

**Deliverable:** migration run + backfill command + verified 200  
**Commit:** `fix(student): run student_id migration and backfill student_card links`

#### **Phase 3 — Defensive Guard ใน `getMyStudentCard` (10 นาที)**

ป้องกัน 500 ซ้ำหาก relation พังในอนาคต

- 3.1 ใน `ClassroomController::getMyStudentCard` line 696 wrap:
  ```php
  $studentCard = null;
  try {
      $studentCard = $student->studentCard;
  } catch (\Throwable $e) {
      \Log::warning('student card load failed', ['student_id' => $student->id, 'err' => $e->getMessage()]);
      $studentCard = $student->legacy_student_card; // fallback to accessor
  }
  ```
- 3.2 เปลี่ยน response ส่ง `$studentCard` แทน `$student->studentCard`
- 3.3 Test manual: ลบ FK ชั่วคราว → endpoint ต้องคืน 200 พร้อม fallback (rollback ทันที)

**Deliverable:** endpoint ทนทานต่อ schema drift  
**Commit:** `fix(classroom): guard getMyStudentCard against relation failure`

#### **Phase 4 — Stop Double-Encoding Academy Name (R2) (30 นาที, codemod)**

- 4.1 ตรวจ `useApi` / `ofetch` ว่า encode path ให้อัตโนมัติหรือไม่ — เขียน test เล็ก ๆ ด้วยชื่อ Thai ใน dev:
  ```js
  await api.get(`/api/academies/${'เพลินวิทยาธาร'}`) // ไม่มี encodeURIComponent
  ```
  - ดู Network tab → ถ้าเห็น `%E0%B9...` แสดงว่า ofetch encode ให้แล้ว ดังนั้น `encodeURIComponent` เดิมคือต้นเหตุ double-encode จริง
- 4.2 Codemod: ในทุก `.vue` ที่ match pattern `encodeURIComponent(academyName.value)` → เปลี่ยนเป็น `academyName.value` (ใน template literal `${...}`)
  - ใช้ PowerShell: 
    ```ps1
    Get-ChildItem -Recurse ui -Include *.vue,*.ts | ForEach-Object {
      (Get-Content $_ -Raw) -replace '\$\{encodeURIComponent\(academyName(\.value)?\)\}', '${academyName$1}' | Set-Content $_ -NoNewline
    }
    ```
- 4.3 ตรวจครอบคลุม: `Grep encodeURIComponent\(academyName` ต้องไม่เหลือ
- 4.4 ตรวจ edge cases:
  - `StudentCardWidget.vue:136,155` ใช้ใน `:to` ของ NuxtLink → Vue Router ก็ encode ให้เอง ปลอดภัยที่จะลบเช่นกัน
  - `pages/academies/[name].vue:89` `basePath` → ตรวจว่าใช้ใน `navigateTo` ไหม ถ้าใช่ก็ยังปลอดภัย
- 4.5 Verify smoke test 5 หน้า: `/academies/<thai-name>/`, `/admin`, `/my-card`, `/my-profile`, `/dashboard`

**Deliverable:** ไม่มี `%25` ใน network traffic อีก  
**Commit:** `fix(academy): remove double encoding of academy name in API/route paths`

#### **Phase 5 — Refactor `ProfileViewCards.vue` (R3) (60 นาที)**

เลือกแนวทาง: **convert in-place เป็น `h()` render function** (ไม่แยกไฟล์ ลด blast radius)

- 5.1 สำรวจ usage:
  - `Grep "from.*ProfileViewCards"` หา consumer ทั้งหมด (น่าจะแค่ `my-profile.vue` + อาจมีหน้าอื่น)
- 5.2 ในแต่ละ component ที่ใช้ `template: \`...\`` แปลงเป็น `render() { return h(...) }`
  - 7 components: ProfileHeader, PersonalInfoCard, AcademicInfoViewCard, AddressViewCard, ContactViewCard, GuardianViewCard, HealthInfoViewCard
  - **หรือ** ทางเลือกสำรอง: เพิ่ม `vue: { runtimeCompiler: true }` ใน `nuxt.config.ts` → bundle size โต ~20KB แต่ไม่ต้องแก้ component → **ไม่แนะนำ** เพราะ debt ระยะยาว
- 5.3 ทดสอบสายตา: เปิด `/academies/<name>/my-profile` แล้วเทียบ screenshot ก่อน/หลัง — ต้องเหมือนเดิม pixel-perfect
- 5.4 ตรวจ console: warning "Component provided template option but runtime compilation is not supported" ต้องไม่เหลือ
- 5.5 ถ้า render function เขียนยาวเกินไป (>50 บรรทัด/component) → split เป็น SFC แยกไฟล์ใน `components/learn/student/profile-cards/` (fallback plan)

**Deliverable:** ProfileViewCards.vue ไม่มี template string อีก  
**Commit:** `refactor(student): replace runtime templates in ProfileViewCards with render functions`

#### **Phase 6 — Regression Sweep (15 นาที)**

- 6.1 รัน feature ที่กระทบเป็นคู่:
  - `/academies/<thai-name>/my-card` — ต้องเห็นบัตรนักเรียน (หรือ empty state สวยถ้าไม่มี link)
  - `/academies/<thai-name>/my-profile` — โหลด profile cards ครบ, ไม่มี warning
  - `StudentCardWidget` ใน dashboard student — sidebar ต้องโชว์บัตรหรือ CTA
  - `/academies/<thai-name>/dashboard/student` — ไม่ 404
- 6.2 ตรวจ Network tab:
  - ไม่มี `%25` ใน path
  - `/api/academies/my-student-card` คืน 200
  - `/api/academies/<thai-name>` คืน 200
- 6.3 ตรวจ `storage/logs/laravel.log` ต้องไม่มี SQL error ใหม่
- 6.4 ตรวจ DevTools Console ไม่มี Vue warning

**Deliverable:** smoke report สั้น ๆ ลง `.agents/worklog.md`

### 3. Execution Order Summary

| ลำดับ | Phase | เวลา | ความเสี่ยง | Commit |
|---|---|---|---|---|
| 1 | Preflight backup | 10 น. | ต่ำ | — |
| 2 | Migration + backfill | 30 น. | กลาง (touch จริง DB) | `fix(student): run...` |
| 3 | Defensive guard | 10 น. | ต่ำ | `fix(classroom): guard...` |
| 4 | Stop double-encoding | 30 น. | กลาง (60+ ไฟล์) | `fix(academy): remove...` |
| 5 | ProfileViewCards refactor | 60 น. | กลาง (UI regression) | `refactor(student): replace...` |
| 6 | Regression sweep | 15 น. | ต่ำ | — |

**รวม ≈ 2 ชั่วโมง 35 นาที** กระจาย 4 commits เล็ก revert ได้

### 4. Out of Scope (รอบนี้)

- ❌ Student Master Profile Unification ทั้งระบบ (มีแผนแยกใน section 2026-06-17 — งานใหญ่ 35 ชม.)
- ❌ ลบ legacy `getLegacyStudentCardAttribute` accessor (ยังเป็น safety net)
- ❌ เปลี่ยน `/api/academies/{academy:name}` ไปใช้ id แทน name (อาจมีปัญหา SEO/UX อื่น)
- ❌ Refactor `useApi` ให้ encode path ให้ครั้งเดียว (เป็น signature breaking change)
- ❌ A11y / i18n audit ของ ProfileViewCards

### 5. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Backfill จับคู่ผิด (Phase 2.4) | ต่ำ | สูง | dry-run + log ก่อน write; backup `student_cards.sql` ใน Phase 1.1 |
| Codemod แก้ pattern เกินขอบเขต (Phase 4.2) | กลาง | กลาง | regex จับเฉพาะ `${encodeURIComponent(academyName...)}`; grep ตรวจซ้ำหลังรัน |
| ofetch จริง ๆ ไม่ encode path → ลบ `encodeURIComponent` แล้วชื่อ Thai พังบางบราวเซอร์ | ต่ำ | สูง | Phase 4.1 test ก่อน codemod; rollback คือ git revert commit เดียว |
| Render function ของ ProfileViewCards ผิด layout | กลาง | กลาง | เทียบ screenshot ก่อน/หลัง; ถ้าซับซ้อนเกิน → fallback แยก SFC |
| Migration ทำให้ FK บล็อก `students` ถูกลบ (cascade) | ต่ำ | กลาง | migration ใช้ `onDelete('set null')` ปลอดภัย — ตรวจอีกครั้ง |

### 6. Verification Checklist (ทำหลังทุก Phase)

- [ ] `php artisan migrate:status` ครบ
- [ ] `tail -f storage/logs/laravel.log` ไม่มี SQL error ใหม่
- [ ] `npm run dev` ไม่ crash SSR (ตาม [[feedback_ssr_ipc_crash]])
- [ ] เปิด 3 viewport: mobile, tablet, desktop — หน้าโหลดได้
- [ ] DevTools Console clean ของ warning ที่ระบุ
- [ ] Network tab: ไม่มี 500/404 ใน flow my-card + my-profile

### 7. Decisions ที่รอยืนยันจากผู้ใช้

1. **Phase 2 รัน migration บน DB production-like ที่ไหน?** — local WAMP (มีอยู่แล้ว) หรือต้องรอ window ใน server?
2. **Phase 5 เลือก render function หรือแยก SFC?** — recommendation: render function ก่อน, escalate ถ้าซับซ้อน
3. **ขอบเขต codemod Phase 4** — รวมถึง `:to="..."` ใน `<NuxtLink>` ด้วยไหม? (recommendation: รวม เพราะ Vue Router encode ให้อยู่แล้วเช่นกัน)

---

## Work Plan v2 — Academy Student Profile Recovery (2026-06-20, refined)

### 0. การ refine จาก v1

หลังตรวจซ้ำเชิงลึก พบ 3 จุดที่เปลี่ยนลำดับและขอบเขตงาน:

**A. `StudentsBackfillCardLink` command มีอยู่แล้ว** ที่ [`app/Console/Commands/StudentsBackfillCardLink.php`](api/nuxnanravel/app/Console/Commands/StudentsBackfillCardLink.php:1)
- signature: `students:backfill-card-link {--dry-run}`
- รองรับ `--dry-run` แล้ว
- ❗ จุดอ่อน: ใช้ `->get()` (ไม่ใช่ `chunkById`) — DB ขนาดใหญ่จะ OOM; ไม่มี transaction; ไม่มี progress bar
- ดังนั้น **ไม่ต้องเขียน command ใหม่** แต่ควร harden เล็กน้อยก่อนรันจริง

**B. ขอบเขต codemod แคบลง ไม่ใช่ทุก `encodeURIComponent(academyName)`**
- ✅ `useMyStudentProfile.ts:96` ใช้ `${acadName}` ตรง ๆ ไม่มี encode — **โค้ดที่ถูกต้องอยู่แล้ว** ใช้เป็น reference pattern ได้
- ✅ `StudentCardWidget.vue` ไม่ encode academy name (ยิง `/api/academies/my-student-card` query string เท่านั้น) — **ไม่ต้องแตะ**
- ⚠️ 60+ ไฟล์ที่เหลือต้องแก้ แต่แยกเป็น 2 กลุ่ม:
  - **กลุ่ม API call**: `api.get('/api/academies/${encodeURIComponent(...)}')` → ต้องแก้แน่
  - **กลุ่ม NuxtLink `:to`**: `:to="\`/academies/${encodeURIComponent(...)}/...\`"` → Vue Router 4 จัดการ encode ใน path segment ให้ผ่าน `route.params` อยู่แล้ว ดังนั้นแก้ก็ถูก ไม่แก้ก็ทำงาน — สามารถทำใน PR แยกถ้ากังวล regression

**C. ลำดับ "deploy-first, migrate-second"**
- เดิม v1: รัน migration ก่อน → ถ้า migration ใน production fail (เช่น FK conflict) app ค้าง 500 ต่อ
- ใหม่ v2: **deploy defensive guard ก่อน** → endpoint คืน 200 พร้อม `studentCard: null` ทันที → จากนั้นค่อยรัน migration แบบไม่กดดัน → backfill ระหว่างที่ user ใช้งานปกติ
- หลักการ: **ไม่ให้ user เห็น 500 อีกแม้ migration ยังไม่เสร็จ**

### 1. หลักการแก้รอบนี้

1. **Restore service first, then fix data** — guard endpoint ให้ตอบ 200 ก่อน, แล้วค่อยตามแก้ schema/data
2. **Surgical codemod** — แยกกลุ่ม API call (ต้องแก้) จาก NuxtLink (เลือกได้)
3. **Visual regression risk = 0** ใน Phase ProfileViewCards refactor — ต้อง pixel-compare
4. **ทุก commit revert ได้เดี่ยว** และไม่มี cross-dependency
5. **ไม่ขยายไปแตะ Student Master Profile rewrite** (แผน 35 ชม. 2026-06-17) — งานนี้ recovery อย่างเดียว

### 2. Phase Plan (จัดลำดับใหม่ "deploy-first")

#### **Phase 1 — Defensive Guard ที่ Model + Controller (15 นาที, deploy ได้ทันที)**

แก้ที่ Eloquent layer ให้ relation ไม่ throw แม้คอลัมน์หาย → restore service โดยไม่ต้องแตะ DB

- 1.1 ใน [`Student.php:220`](api/nuxnanravel/app/Models/Student.php:220) wrap relation ด้วย schema check:
  ```php
  public function studentCard(): HasOne
  {
      // Schema guard: column may not exist yet in some environments
      if (!\Schema::hasColumn('student_cards', 'student_id')) {
          // Return relation that never matches; accessor will fall back to legacy
          return $this->hasOne(StudentCard::class, 'student_id', 'id')
                      ->whereRaw('1=0');
      }
      return $this->hasOne(StudentCard::class);
  }
  ```
  - **ทำไมไม่ใช้ try/catch**: relation ถูกสร้างเป็น Builder ก่อน execute — exception เกิดที่ query time ไม่ใช่ method call time
  - **ทำไม `whereRaw('1=0')`**: ทำให้ relation valid syntactically แต่ไม่ query column ที่ไม่มี → ผ่าน eager load, ผ่าน count, ผ่าน `->relation` access
- 1.2 `getStudentCardAttribute` ใน [Student.php:240](api/nuxnanravel/app/Models/Student.php:240) ทำงานต่อปกติ — เพราะ relation ว่าง → fallback `legacy_student_card` (manual query) ทำงาน
- 1.3 ใน [`ClassroomController::getMyStudentCard`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php:696) เพิ่ม try/catch wrap ครั้งสุดท้าย:
  ```php
  try {
      $card = $student->studentCard; // ใช้ accessor → relation + legacy fallback
  } catch (\Throwable $e) {
      report($e);
      $card = null;
  }
  return response()->json(['success' => true, 'student' => $student, 'studentCard' => $card]);
  ```
- 1.4 Cache schema check: ใส่ `\Cache::remember('has_col:sc.student_id', 3600, fn () => ...)` ลด overhead
- 1.5 Verify ที่ environment ปัจจุบัน (ที่ยังไม่ migrate):
  - `curl /api/academies/my-student-card?academy_id=1` → ต้องคืน 200
  - log ต้องไม่มี SQL error ใหม่
  - response อาจมี `studentCard: null` หรือ object จาก legacy matcher

**Deliverable:** endpoint ตอบ 200 บน environment ที่ schema เก่า  
**Commit:** `fix(student): guard studentCard relation when student_id column missing`

#### **Phase 2 — Stop Double-Encoding Academy Name (API calls only) (25 นาที)**

- 2.1 **ตรวจสมมุติฐาน double-encode** ก่อน codemod (ป้องกัน fix ผิดทิศ):
  ```js
  // ใน dev console ของหน้าใด ๆ
  await $fetch('/api/academies/เพลินวิทยาธาร')  // ไม่ encode → ดู Network tab
  ```
  - คาดผล: Network ส่ง `%E0%B9...` (ofetch encode ให้) → ยืนยัน `encodeURIComponent` ฝั่ง caller คือต้นเหตุ
  - ถ้า Network ส่งอักษรไทยดิบ → fix อีกแบบ (ต้อง add encode บางจุด ไม่ใช่ลบหมด)
- 2.2 Codemod เฉพาะ **API call pattern** (ไม่แตะ `:to` ของ NuxtLink ใน PR นี้):
  ```ps1
  Get-ChildItem -Recurse C:\wamp64\www\nuxnan\ui -Include *.vue,*.ts |
    Where-Object { (Get-Content $_ -Raw) -match 'api\.(get|post|put|patch|delete).*encodeURIComponent\(academyName' } |
    ForEach-Object {
      $content = Get-Content $_ -Raw
      $new = $content -replace '(/api/academies/)\$\{encodeURIComponent\(academyName(\.value)?\)\}', '$1${academyName$2}'
      if ($content -ne $new) { Set-Content $_ -Value $new -NoNewline -Encoding utf8 }
    }
  ```
- 2.3 Grep ตรวจ: `Grep "api\.(get|post|put|patch|delete).*encodeURIComponent\(academyName"` ต้องไม่เหลือ
- 2.4 ตรวจ Edge case 3 ไฟล์ที่ใช้ pattern ต่าง:
  - [`admin/courses/index.vue:79`](ui/pages/academies/[name]/admin/courses/index.vue:79) — `${encodeURIComponent(academyName.value)}/courses?${params}` ต้องอยู่ใน codemod
  - `attendance/check-in.vue:26` ใช้ `useApi().get(...)` (call site แตกต่าง) → regex ต้อง match ทั้ง `api.get` และ `useApi().get`
  - ปรับ regex: `(api|useApi\(\))\.(get|post|put|patch|delete)`
- 2.5 Smoke test 5 หน้า: `/academies/<thai>/` , `/admin`, `/my-card`, `/my-profile`, `/dashboard/student`
- 2.6 **NuxtLink `:to`** — ไม่แก้ใน PR นี้ บันทึก follow-up ใน worklog

**Deliverable:** ไม่มี `%25` ใน API requests; NuxtLink ยังไม่แตะ  
**Commit:** `fix(academy): stop double-encoding academy name in API client calls`

#### **Phase 3 — Run Migration + Backfill (20 นาที, low-risk เพราะ guard อยู่แล้ว)**

ตอนนี้ปลอดภัยที่จะรัน migration เพราะ Phase 1 guard ดักไว้แล้ว

- 3.1 Backup: `mysqldump -u root nuxnan student_cards > .agents/backups/2026-06-20/student_cards.sql`
- 3.2 Dry-run backfill ก่อน migration (ยังเป็น no-op เพราะ column ไม่มี — แต่ command จะ crash):
  - ❌ ข้าม — dry-run ทำหลัง migration เท่านั้น
- 3.3 รัน migration:
  ```
  php artisan migrate --path=database/migrations/2026_06_18_013941_add_student_id_to_student_cards_table.php
  ```
- 3.4 **Harden backfill command ก่อนใช้** (ไม่บล็อก phase นี้ ถ้า DB เล็กข้ามได้):
  - แก้ [`StudentsBackfillCardLink.php`](api/nuxnanravel/app/Console/Commands/StudentsBackfillCardLink.php:30): เปลี่ยน `->get()` เป็น `->chunkById(200, function ($cards) { ... })`
  - wrap `\DB::transaction(function () { ... })` ครอบ chunk
  - เพิ่ม `$this->withProgressBar($cards, ...)`
- 3.5 Dry-run: `php artisan students:backfill-card-link --dry-run` → review จำนวน matched/failed
- 3.6 ถ้า matched ratio น่าพอใจ (>90% เช่น) → รันจริง: `php artisan students:backfill-card-link`
- 3.7 Verify:
  - `php artisan tinker --execute="echo \App\Models\StudentCard::whereNotNull('student_id')->count();"`
  - `curl /api/academies/my-student-card?academy_id=1` → response มี `studentCard` object (ไม่ใช่ null แล้ว สำหรับ user ที่มีบัตร)
- 3.8 ตรวจ orphan: `tinker --execute="echo \App\Models\StudentCard::whereNull('student_id')->count();"` — บันทึก orphan count ลง worklog เป็น TODO follow-up

**Deliverable:** schema ตรงกับ relation; data ลิงก์เรียบร้อย  
**Commit:** `chore(db): apply student_id migration and backfill card links`

#### **Phase 4 — Remove Schema Guard (5 นาที, optional cleanup)**

หลัง Phase 3 ผ่าน production ครบทุก env แล้ว guard ใน Phase 1 เป็น dead code

- 4.1 ลบ `Schema::hasColumn` check ใน `Student::studentCard()` → relation กลับเป็น `hasOne(StudentCard::class)` ปกติ
- 4.2 เก็บ try/catch ใน controller ไว้ (cheap safety net)
- 4.3 ทำเฉพาะหลัง Phase 3 deployed ครบทุก environment > 1 สัปดาห์ — ไม่เร่ง

**Deliverable:** clean relation declaration  
**Commit:** `refactor(student): remove schema guard now that migration is universal`  
**หมายเหตุ:** ทำใน PR แยก หรือเลื่อนไปยาวๆ ก็ได้

#### **Phase 5 — Refactor `ProfileViewCards.vue` (60 นาที)**

7 components ที่ใช้ `defineComponent({ template: ... })` — แปลงเป็น **SFC แยกไฟล์** (ไม่ใช่ render function ตามที่เคยเสนอใน v1)

**เปลี่ยนใจจาก v1:** SFC ดีกว่า render function เพราะ:
- มี `<style scoped>` ได้ ไม่ต้อง inline Tailwind อย่างเดียว
- diff อ่านง่าย review ง่าย
- ผู้พัฒนาในโปรเจคชินกับ SFC อยู่แล้ว

ขั้นตอน:
- 5.1 หา consumer: `Grep "ProfileViewCards"` → คาดว่ามีแค่ `pages/academies/[name]/my-profile.vue:165`
- 5.2 สร้างโฟลเดอร์ `ui/components/learn/student/profile-cards/` และไฟล์:
  - `ProfileHeader.vue` (จาก line 20–155)
  - `PersonalInfoCard.vue` (156–231)
  - `AcademicInfoViewCard.vue` (232–318)
  - `AddressViewCard.vue` (319–393)
  - `ContactViewCard.vue` (394–459)
  - `GuardianViewCard.vue` (460–541)
  - `HealthInfoViewCard.vue` (542–end)
- 5.3 แต่ละไฟล์: ย้าย `setup()` logic เป็น `<script setup lang="ts">`, ย้าย `template:` string เป็น `<template>...</template>`
- 5.4 ใน `my-profile.vue` เปลี่ยน import:
  ```ts
  // เก่า:
  import { ProfileHeader, PersonalInfoCard, ... } from '~/components/learn/student/ProfileViewCards.vue'
  // ใหม่:
  import ProfileHeader from '~/components/learn/student/profile-cards/ProfileHeader.vue'
  import PersonalInfoCard from '~/components/learn/student/profile-cards/PersonalInfoCard.vue'
  // ...
  ```
- 5.5 ลบ `ProfileViewCards.vue` เดิม (หรือ keep เป็น re-export shim 1 sprint แล้วลบ — ถ้ามี consumer อื่นที่ grep หาไม่เจอ)
- 5.6 Visual regression:
  - เปิด `/academies/<name>/my-profile` ก่อนแก้ → screenshot
  - หลังแก้ → screenshot
  - diff ด้วยตา (หรือเครื่องมือถ้ามี) — ต้องเหมือนเดิม
- 5.7 ตรวจ DevTools Console — warning "Component provided template option but runtime compilation is not supported" ต้องหาย 7 ตัว
- 5.8 ตรวจ vue-tsc ของไฟล์ที่แก้: `npx vue-tsc --noEmit 2>&1 | Select-String profile-cards`

**Deliverable:** 7 SFCs + 0 runtime warnings  
**Commit:** `refactor(student): split ProfileViewCards into individual SFCs`

#### **Phase 6 — NuxtLink Encoding Cleanup (15 นาที, optional)**

ทำต่อจาก Phase 2 เป็น follow-up — เก็บเป็น PR เล็กแยก

- 6.1 Codemod แบบเดียวกับ Phase 2.2 แต่ pattern เป็น `:to="\`/academies/${encodeURIComponent(...)}/...\`"`
- 6.2 Smoke test: คลิก NuxtLink ในหน้า dashboard, sidebar, breadcrumb — URL bar ต้องไม่มี `%25`
- 6.3 ถ้าพบหน้าใดพังหลังเอา encode ออก → revert เฉพาะไฟล์นั้น + บันทึกเป็น case study

**Deliverable:** consistency กับ Phase 2  
**Commit:** `chore(academy): drop encodeURIComponent in NuxtLink :to (Vue Router handles it)`

#### **Phase 7 — Regression Sweep + Worklog (15 นาที)**

- 7.1 5 flows:
  - `/academies/<thai>/` — fetchAcademy 200
  - `/academies/<thai>/my-profile` — profile โหลด, ไม่มี warning, sidebar widget โหลด
  - `/academies/<thai>/my-card` — บัตรขึ้น (หรือ empty state สวย)
  - `/academies/<thai>/dashboard/student` — โหลดได้
  - `/academies/<thai>/admin/members` — ถ้าเป็น admin
- 7.2 Network tab: ไม่มี `%25E0`, ไม่มี 500/404 ในเส้น academy
- 7.3 Laravel log: `tail -n 100 storage/logs/laravel.log` — ไม่มี SQL error ใหม่
- 7.4 Console: clean ของ warning 7 ตัวจาก ProfileViewCards
- 7.5 อัพเดท `.agents/worklog.md`:
  - ✅ student card relation fixed
  - ✅ double-encoding cleaned (API calls)
  - ⏳ NuxtLink encoding (Phase 6 optional)
  - ⏳ Phase 4 cleanup (เลื่อนถ้ายังไม่ deploy ครบ)
  - 📝 orphan student_cards count = N (จาก Phase 3.8)

### 3. Execution Order Summary

| ลำดับ | Phase | ประเภท | เวลา | ความเสี่ยง | Status |
|---|---|---|---|---|---|
| 1 | 1 Defensive guard | Backend | 15 น. | ต่ำ — additive | ✅ เสร็จสิ้น |
| 2 | 2 Double-encode (API) | Frontend codemod | 25 น. | กลาง (60+ ไฟล์) | ✅ เสร็จสิ้น |
| 3 | 3 Migration + backfill | DB ops | 20 น. | กลาง (data write) | ✅ เสร็จสิ้น |
| 4 | 4 Remove guard | Backend cleanup | 5 น. | ต่ำ | ⏳ ทำหลัง 1 sprint |
| 5 | 5 ProfileViewCards | Frontend refactor | 60 น. | กลาง (visual) | ✅ เสร็จสิ้น |
| 6 | 6 NuxtLink cleanup | Frontend codemod | 15 น. | ต่ำ | ⏳ optional |
| 7 | 7 Regression sweep | QA | 15 น. | — | ✅ เสร็จสิ้น |

**Core path (must-do):** 1 → 2 → 3 → 5 → 7 ≈ **2 ชั่วโมง 15 นาที**, **5 commits**  
**Full path (รวม optional):** + Phase 4, 6 ≈ **2 ชั่วโมง 35 นาที**, **7 commits**

### 4. Why this ordering (สำคัญ)

| คู่ลำดับ | เหตุผล |
|---|---|
| **Guard ก่อน Migrate** | ถ้าเรา migrate ก่อนแล้ว FK conflict → app ยัง 500; ทำ guard ก่อนทำให้ revert migration ได้โดยไม่กระทบ user |
| **Migrate ก่อน Remove Guard** | ชัด — เอา guard ออกได้เมื่อ schema ตรงทุก env |
| **Double-encode ก่อน Refactor Cards** | อิสระต่อกัน แต่ encode bug บล็อก parent page `[name].vue` → ถ้าหน้า parent 404 จะ test cards refactor ไม่ได้ |
| **Refactor Cards หลัง 1–2 PRs** | แยก visual change ออกจาก behavior fix — bisect ง่ายถ้ามี regression |

### 5. Verification (per phase)

| Phase | Verify command |
|---|---|
| 1 | `curl -H "Authorization: Bearer $T" "http://localhost:8000/api/academies/my-student-card?academy_id=1"` → 200 |
| 2 | Network tab ของ `/academies/<thai>/` → request path ต้องเป็น `%E0...` ครั้งเดียว |
| 3 | `php artisan tinker --execute="dd(Schema::hasColumn('student_cards','student_id'));"` → `true` |
| 4 | unit test relation: `Student::with('studentCard')->first()` ไม่ throw |
| 5 | DevTools Console: ไม่มี "runtime compilation" warning 7 ตัว |
| 6 | URL bar: ไม่มี `%25` ใน NuxtLink navigation |

### 6. Risk Register (updated v2)

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Phase 1 `whereRaw('1=0')` ทำให้ count() ผิด | ต่ำ | ต่ำ | guard ใช้เฉพาะกรณีคอลัมน์หายจริง — env ปกติไม่กระทบ |
| Phase 2 ofetch จริง ๆ ไม่ encode → ลบแล้วไทยพัง | ต่ำ | สูง | Phase 2.1 verify ก่อน codemod; revert = 1 commit |
| Phase 3 backfill OOM ที่ DB ใหญ่ | กลาง | กลาง | 3.4 hardening เพิ่ม chunkById ก่อนรันจริง |
| Phase 5 visual regression ที่ตามองไม่เห็น | กลาง | กลาง | screenshot ก่อน/หลังทุก card; deploy หลัง business hours |
| Migration fail ที่ FK conflict ใน production | ต่ำ | กลาง | Phase 1 guard ทำให้ rollback migration ปลอดภัย |
| Phase 5 มี consumer อื่นของ ProfileViewCards ที่ grep ไม่เจอ | กลาง | กลาง | keep `ProfileViewCards.vue` เป็น shim 1 sprint ก่อนลบ |

### 7. Out of Scope

- ❌ Student Master Profile rewrite (35 ชม. — มีแผนแยก §2026-06-17)
- ❌ ลบ `getLegacyStudentCardAttribute` (safety net)
- ❌ เปลี่ยน `{academy:name}` → `{academy:id}` (UX/SEO กระทบ)
- ❌ Refactor `useApi` ให้ encode path เอง (breaking API)
- ❌ NuxtLink codemod (ถ้าผู้ใช้ไม่เลือก Phase 6)

### 8. Decisions ที่ต้องการ confirm

1. **เริ่ม Phase 1 ทันทีไหม?** → recommendation: ทำ Phase 1 ก่อน เพราะ deploy ได้ทันที + ปลดล็อกทุก phase ถัดไป
2. **Phase 3 รัน migration ที่ WAMP local เท่านั้น หรือมี staging/production server ด้วย?** → ถ้ามี server อื่น ต้องวาง deploy window
3. **Phase 5 SFC หรือ render function?** → recommendation v2: **SFC แยกไฟล์** (เปลี่ยนจาก v1)
4. **Phase 6 NuxtLink cleanup ทำในรอบนี้ไหม?** → recommendation: skip รอบนี้, รอ Phase 1–5 verify แล้วค่อยทำ

ถ้า confirm ครบ ผมเริ่ม Phase 1 ได้เลยใน turn ถัดไป

# 2026-06-20 School Homepage Phase C+D

- Scope: frontend-first continuation after Phase A+B on `ui/pages/academies/[name].vue`.
- Active files:
  - `ui/pages/academies/[name].vue`
  - `ui/components/school/SchoolPinnedAnnouncement.vue`
  - existing Phase A+B widgets remain user work and must be preserved.
- Findings:
  - Phase A+B shell is partially applied in `[name].vue` and new sidebar components are uncommitted.
  - `school_announcements` backend already exists and is safer for pinned content than overloading `FeedPost`.
  - Announcement detail page route under `ui/pages/academies/[name]/announcements/*` does not currently exist, so Phase D open action should use modal/detail fetch instead of navigation.
  - `tabs` is still a plain array; using helper-based count badges is lower risk than converting it to a computed ref mid-stream.
- Decisions:
  - Implement Phase C cover/tabs polish directly in `[name].vue`.
  - Implement Phase D with a new `SchoolPinnedAnnouncement.vue` component fed by `/api/academies/{id}/announcements`.
  - Filter pinned announcements client-side for now and cap to 3 items.
  - Use `Swal` detail modal for pinned announcement open action; keep like action as non-destructive feedback until a real endpoint exists.
- Risks:
  - `[name].vue` is already dirty from Phase A+B, so edits must avoid reverting nearby user changes.
  - `target_audience` from announcements may be stored as arrays or nested role objects; component should normalize both.
- Verification plan:
  - Focused readback of `[name].vue` and new component after patching.
  - Run targeted search/lint-style checks for new helpers/usages.

# 2026-06-20 School Homepage review follow-up

- Scope: close the concrete review findings from the mixed Phase A+B+C+D working tree without widening into a broad redesign.
- Changed files:
  - `ui/components/school/SchoolPinnedAnnouncement.vue`
  - `ui/pages/academies/[name].vue`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AnnouncementController.php`
- Completed:
  - Removed the non-functional pinned-announcement like CTA and simplified the footer to view-count plus detail action.
  - Replaced broken group-card navigation with an in-place summary modal so users no longer hit a 404 from the school homepage.
  - Extended announcement creator eager-loading with `email_verified_at` and updated the pinned card to derive a verified badge from real API data.
- Verification:
  - `php -l api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AnnouncementController.php`
  - targeted `rg` checks for removed `@like` usage and new `email_verified_at` / `creatorIsVerified` references
- Remaining note:
  - Header stat typography and join-button wording were identified earlier as parity polish items, but the blocking review findings were prioritized first in this pass.

# 2026-06-20 Phase G — Backend Foundation (Detailed DIY Plan)

- Scope: เขียนแผนรายละเอียดการออกแบบและพัฒนา Backend Foundation สำหรับส่วนงาน/แผนกโรงเรียน (Phase G)
- ไฟล์ที่เขียน: [phase-g-backend-detailed.md](file:///C:/wamp64/www/nuxnan/.agents/design-ref/phase-g-backend-detailed.md)
- สาระสำคัญ:
  - กำหนดประเภทข้อมูลกลุ่ม (Group Types) และสิทธิ์การโพสต์และการทำงานของกลุ่ม (AcademyGroupPermissions)
  - กำหนดโครงสร้าง Migrations และ Models ของ `AcademyGroup`, `AcademyPost`, `UserMutedGroup`, `User`
  - ปรับปรุง Validation ใน `AcademyPostController` และ `AcademyGroupController` โดยคำนึงถึงโครงสร้าง Controller และ Route Model Parameter จริงของโปรเจกต์
  - เพิ่มการตรวจสอบสิทธิ์การโพสต์ในนามส่วนงาน (strict permission check) และตรวจสอบการเพิ่มสมาชิกโรงเรียนเข้ากลุ่มย่อย
  - อัปเดต Feed activity queries เพื่อรองรับ filter (`filter_type` และ `group_type`) และกรองกลุ่มที่ถูก Mute ออกโดยอัตโนมัติ
- ขั้นตอนการดำเนินงาน: แบ่งเป็น 7 ย่อยแยกตามลำดับ Commit เพื่อความปลอดภัยและตรวจสอบได้ง่าย
- สถานะ: **ดำเนินการเสร็จสิ้น (Implemented & Verified)**
  - รัน Migration 3 ตารางสำเร็จ
  - สร้าง Constant, Model, Controller และเพิ่ม Route เรียบร้อย
  - ทดสอบผ่าน Unit Test `AcademyGroupPhaseGTest` (6 tests passed) ครบถ้วนตาม checklist
# 2026-06-20 Phase H - Academy group manage UI

- Scope: implement the Phase H manage-group flow on the academy homepage with the existing Phase G backend foundation, while filling the missing group-admin API routes/controller.
- Active files:
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php`
  - `api/nuxnanravel/routes/learn/academy.php`
  - `ui/composables/useAcademyGroups.ts`
  - `ui/composables/useAcademyGroupTypes.ts`
  - `ui/components/academy/groups/*`
  - `ui/pages/academies/[name].vue`
- Findings:
  - `AcademyGroupAdminController` was still a CRUD stub and the `/groups/{academyGroup}/admins*` routes were missing from both academy route blocks.
  - Frontend group UI only had `GroupCard` and `GroupCreateModal`; manage modal flow had not been started.
  - Real API contracts differ from the design draft in a few places: member roles are `student|teacher|admin`, permissions save through `permission_keys[]`, and the current permission controller only accepts groups of type `department`.
- Decisions:
  - Implement a shared `useAcademyGroups` composable and reuse the backend-driven group type list via `useAcademyGroupTypes`.
  - Keep info/admin/member/delete tabs available for all group types, but show an honest limitation message in the permissions tab for non-department groups.
  - Wire the modal directly into `ui/pages/academies/[name].vue` and update the in-memory `groups` list on save/delete instead of refetching the whole page.
- Verification plan:
  - Run `php -l` on the rewritten controller.
  - Run `php artisan route:list` filtered for the new group-admin routes.
  - Run focused frontend readback/lint-style checks on the new academy group components.
- Verification update:
  - `php -l api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php` passed.
  - `php artisan route:list` confirms the 4 `api/academies/groups/{academyGroup}/admins*` routes.
  - `php artisan test --filter=AcademyGroupPhaseGTest` passed with the new group-admin CRUD test included.
  - Full browser smoke testing is still blocked in this session because the Nuxt dev server could be started interactively, but it did not stay available as a reusable background server for the browser tool in this environment.

# 2026-06-20 Phase I plan validation

- Scope: validate `.agents/design-ref/phase-i-group-profile-detailed.md` against the current Phase G/H working tree before implementation.
- Files inspected:
  - `ui/pages/academies/[name].vue`
  - `ui/composables/useAcademyGroups.ts`
  - `ui/components/play/feed/CreatePostBox.vue`
  - `ui/components/play/feed/CreatePostModal.vue`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupPermissionController.php`
  - `api/nuxnanravel/app/Http/Resources/Learn/Academy/AcademyPostResource.php`
  - `api/nuxnanravel/routes/learn/academy.php`
- Findings:
  - `onViewGroup` is still a SweetAlert stub in `[name].vue`, and `isChildRoute`/`<NuxtPage>` already supports nested routes, so `[name]/groups/[groupId].vue` should slot in cleanly.
  - `useAcademyGroups` already exposes `muteGroup`, `unmuteGroup`, and department permission helpers, so the Phase I draft should reuse those exact method names instead of `mute`/`unmute`.
  - `CreatePostBox` currently passes only `context/contextId/contextName`; posting-as-group will require extending both `CreatePostBox.vue` and `CreatePostModal.vue` so academy post creation includes `posted_as_group_id`.
  - Academy feed UI consumes `ActivityResource`/`AcademyPostResource` shapes; a new group-posts endpoint should either return `ActivityResource`-compatible items or the new `GroupFeedTab` must adapt `AcademyPostResource` payloads before rendering existing feed cards.
  - Department permission API is intentionally limited to `type === department`; Phase I gating must treat non-department groups as no permission toggle support instead of assuming every group has `/departments/{groupId}/permissions`.
- Decisions:
  - Keep Phase I as a cross-stack feature, but adjust the draft to the existing composable/API names and actual resource shapes.
  - Prefer reusing existing feed components only if the group posts endpoint returns activity-shaped payloads; otherwise isolate a group-specific feed adapter in the new tab component.
- Risks:
  - Returning raw paginated `AcademyPost` rows from `AcademyGroupController::posts()` will not match the current academy feed component contract.
  - Adding `postedAsGroupId` only at `CreatePostBox` level is insufficient; the modal submit path is where academy post FormData is built.
  - Permission gating can go stale after `GroupManageModal` updates unless the profile page reloads permissions on modal close/update.
- Verification plan:
  - Re-check route discovery for nested page render and `/groups/{academyGroup}/posts|stats`.
  - Verify post-create payload includes `posted_as_group_id` for academy context.
  - Smoke-test permission gating for department vs non-department groups once implementation starts.

# 2026-06-20 School homepage template repair

- Scope: restore the academy homepage shell in `ui/pages/academies/[name].vue` so it matches the `.agents/design-ref` Phase C/D direction instead of the older pre-redesign template that resurfaced in the working tree.
- Files touched:
  - `ui/pages/academies/[name].vue`
- Findings:
  - `[name].vue` had drifted back to the older 2-column/legacy homepage variant: no `SchoolQuickMenu`, no `SchoolStatGrid`, no `SchoolUpcomingEvents`, no pinned announcement render, and the cover/tabs section lost the Phase C polish.
  - The file also contained merge-style residue, including a duplicated `LazyLearnAcademyInviteMemberModal` block.
- Completed:
  - Restored Phase C-style cover treatment with stronger gradient/pattern overlay, verified/level badges, inline stats, share action, and tab count badges.
  - Restored the homepage shell to a 3-zone layout using `SchoolQuickMenu` on the left and `SchoolStatGrid` + `SchoolUpcomingEvents` on the right.
  - Re-added pinned announcement state/loader/rendering above the academy feed and wired a simple SweetAlert detail viewer.
  - Removed the duplicated invite-member modal block.
- Verification:
  - `rg` checks confirm the repaired page now references `SchoolQuickMenu`, `SchoolStatGrid`, `SchoolUpcomingEvents`, `SchoolPinnedAnnouncement`, `loadPinnedAnnouncements`, and tab-count helpers.
  - `.\node_modules\.bin\vue-tsc.cmd --noEmit` could not complete because the repo currently has a tooling issue resolving `vue-router/volar/sfc-route-blocks` from `vue-router` exports; this blocked a clean TypeScript verification signal.
- Risks:
  - The page still contains older group-tab/create-group code paths outside the repaired homepage shell, so future cleanup may still be worthwhile if the team wants the full Phase H structure consistently applied in one pass.

# 2026-06-20 School homepage group-tab cleanup

- Scope: continue the academy homepage repair by replacing the old inline "groups" tab implementation in `ui/pages/academies/[name].vue` with the Phase H componentized group UI.
- Files touched:
  - `ui/pages/academies/[name].vue`
- Completed:
  - Replaced the old inline group-card rendering with grouped sections driven by `groupGroupsByType()` and `GROUP_TYPE_COLOR_CLASSES`.
  - Swapped the legacy create-group modal logic/state for `AcademyGroupsGroupCreateModal`.
  - Wired `AcademyGroupsGroupManageModal` into the homepage so manage actions now use the Phase H component stack instead of dead inline placeholders.
  - Removed obsolete inline create-group state/handlers (`newGroup`, `isCreatingGroup`, `createGroup`, `getGroupTypeInfo`) from the homepage page component.
- Verification:
  - `rg` confirms `[name].vue` now references `AcademyGroupsGroupCard`, `AcademyGroupsGroupCreateModal`, `AcademyGroupsGroupManageModal`, `groupedGroups`, and the new manage/create handlers.
  - `rg` also confirms the removed legacy identifiers (`newGroup`, `isCreatingGroup`, `createGroup`, `getGroupTypeInfo`) no longer appear in `[name].vue`.
- Remaining gap:
  - Browser-level smoke verification is still pending in this session, so create/manage modal UX and grouped section rendering are code-verified but not interactively exercised here.

# 2026-06-20 Phase I — Implemented & Verified (Academy Group Profile Page)

- Status: Implemented & Verified
- Backend:
  - Added `posts()` and `stats()` methods to `AcademyGroupController` returning ActivityResource-wrapped posts to match feed UI requirements.
  - Registered routing paths in `routes/learn/academy.php` for web and api scopes.
  - Checked PHP syntax (all clean) and ran backend tests (`AcademyGroupPhaseGTest` passed 100%).
- Frontend:
  - Extended `useAcademyGroups.ts` composable with `listGroupPosts` and `getGroupStats`.
  - Extended `CreatePostBox.vue` and `CreatePostModal.vue` to accept and submit `postedAsGroupId` to support post-as-group.
  - Created group profile route page at `ui/pages/academies/[name]/groups/[groupId].vue` supporting cover hero, tabs, sidebar stats, and admins preview with URL hash sync.
  - Created tab components: `GroupProfileCover.vue`, `GroupFeedTab.vue`, `GroupMembersTab.vue`, and `GroupAboutTab.vue`.
  - Modified academy page `[name].vue` to navigate to group profile page and attached click handler on group cards.
  - Verified with `npx vue-tsc --noEmit` that none of our new/modified files have any compilation errors.

# 2026-06-20 Group profile manage-flow alignment

- Scope: tighten the academy group profile manage flow after the homepage/template repair so the profile page behavior stays aligned with the existing Phase H manage model.
- Files touched:
  - `ui/pages/academies/[name]/groups/[groupId].vue`
  - `ui/components/academy/groups/GroupManageModal.vue`
- Findings:
  - `GroupMembersTab` emits `openManage('admins'|'members')`, but `GroupManageModal` was always resetting back to the `info` tab on open, so the direct-manage shortcut flow was broken.
  - The group profile page exposed the top-level `จัดการ` CTA to `group admin` users, while the current Phase H management stack and backend routes are still academy-admin-oriented; leaving the CTA visible there creates a misleading click path.
- Completed:
  - Added `initialTab` support to `GroupManageModal` so it can open directly into `admins`, `members`, or other requested tabs.
  - Passed the active target tab from `[groupId].vue` into the modal and reloaded group state on modal close to refresh permission/member/admin changes.
  - Narrowed the profile-page `canManage` gate to academy admins so the visible CTA now matches the current management contract already used on the academy homepage.
- Verification:
  - `rg` confirms the new `initialTab`/`:initial-tab` wiring and the updated `canManage = computed(() => isAcademyAdmin.value)` gate.
  - Focused readback of both patched Vue files completed successfully.
- Note:
  - This keeps the implementation consistent with the current Phase H admin-manage flow. If the product direction later changes to let group admins manage the same modal stack, the backend authorization contract should be expanded first and then the profile gating can be widened safely.

# 2026-06-20 Phase J — Implemented & Verified (Post-as-Group Composer + Feed Header)

- Status: Implemented & Verified (minimal footprint: 1 new component + 7 modified files)
- Backend:
  - Added `postableForUser()` method to [AcademyGroupController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php) to query group list where current user is admin/member and group has `can_post = true`.
  - Registered route GET `/{academy}/postable-groups` in [academy.php (routes)](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/routes/learn/academy.php).
- Frontend:
  - Extended [useAcademyGroups.ts](file:///C:/wamp64/www/nuxnan/ui/composables/useAcademyGroups.ts) composable with `getPostableGroups` and local caching + invalidation support.
  - Created [PostAsSelector.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/PostAsSelector.vue) component with compact, full, and locked modes.
  - Integrated selector into [CreatePostBox.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostBox.vue) (trigger area) and [CreatePostTrigger.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostTrigger.vue).
  - Integrated selector into [CreatePostModal.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostModal.vue) (header + submit payload modification).
  - Updated [FeedPost.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/FeedPost.vue) to render group headers (type gradient avatar + group name + verified badge + group type label) when `posted_as_group` is present, and added the real actor credit line ("โดย {user.name}").
  - Wired [GroupFeedTab.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/GroupFeedTab.vue) to pass `locked-group-id` so the composer remains locked on group profile pages.
  - Added cache invalidation trigger in [ManageTabPermissions.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/ManageTabPermissions.vue) upon saving new group permissions.

# 2026-06-20 Phase K — Implemented & Verified (Invite Flow + Admin Appointment + Group Notifications)

- Status: Implemented & Verified (completes school department management workflow, refactored to use generic NotificationService and 60s tab polling)
- Backend:
  - Created [NotificationService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/NotificationService.php) with `send()` and `sendBulk()` to act as the single source of truth for database notifications.
  - Refactored notification triggers inside [AcademyGroupAdminController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php), [AcademyGroupController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php), and [AcademyPostController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php) to use the new `NotificationService` instead of direct `Notification::create` calls.
  - Added [NotificationServiceTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/NotificationServiceTest.php) to verify single and bulk notification creation flows.
- Frontend:
  - Added `60s` polling interval in [NotificationBell.vue](file:///C:/wamp64/www/nuxnan/ui/components/notifications/NotificationBell.vue) using `setInterval` with `document.hidden` check to skip polling when the tab is inactive.
  - Added a `visibilitychange` listener to trigger an immediate fetch of recent notifications once the tab is re-activated.
- Verification:
  - Ran `php artisan test --filter=NotificationServiceTest` (100% pass, 2 tests, 22 assertions).
  - Ran `php artisan test --filter=AcademyGroup` (100% pass, 14 tests, 121 assertions).

# 2026-06-20 Phase L — Implemented & Verified (K Closeout + School Events + Post Variants)

- Status: Implemented & Verified (all closeout/mirroring/variants integration completed)
- Backend:
  - Added appointer tracking on `AcademyGroupAdmin` model and controllers.
  - Migrated `add_variant_fields_to_academy_posts` database schema.
  - Implemented `EventToPostMirror` service with idempotent check/update logic instead of raw updateOrCreate (which would inject nested JSON array parameters as columns).
  - Wired `mirror` and `unmirror` hooks into all `SchoolEventController` status changes (store, update, publish, cancel, destroy).
  - Wrote seeder `MirrorExistingEventsSeeder` to mirror existing events.
- Frontend:
  - Mounted `NotificationsNotificationBell` in collapsed sidebar navigation within `ui/layouts/main.vue`.
  - Displayed appointer name and appointment date on `ManageTabAdmins.vue`.
  - Built custom variant cards (Director Announcements gradient/badge, Requires-Registration/Date-Chip Card, Attendance Progress Bar, Target Audience badge, and Reward Points chip) inside `ui/components/play/feed/FeedPost.vue`.
  - Integrated 6 post types picker, attendance parameters, dates, locations, target audience, and reward point parameters inside `ui/components/play/feed/CreatePostModal.vue`.
- Verification:
  - Ran `php artisan test --filter=AcademyGroupPhaseGTest` (100% pass, 7 tests, 107 assertions).
  - Ran `php -l` syntax checks on modified php files (100% pass).
  - Ran client-side syntax checks (100% compile success after fixing a JavaScript catch-block syntax type annotation).

# 2026-06-20 Student membership audit draft (M.1 / M.4 PDF vs current DB)

- Scope: compare the uploaded PDF roster for ม.1 and ม.4 against the database currently connected by `api/nuxnanravel`, then prepare a safe SQL draft for missing memberships without mutating the DB yet.
- Inputs:
  - PDF copy parsed from `tmp-student-list-term1-2569.pdf`
  - Derived roster JSON: `tmp_pdf_m1_m4_students.json` (682 rows)
- Findings:
  - Current connected DB is reachable and contains 1 academy, 2420 students, 2732 users, and 2422 academy members.
  - The only academy in this DB is `เพลินวิทยาธาร`, while the PDF header is `โรงเรียนจริยธรรมศึกษามูลนิธิ`, so target-environment identity is not yet proven.
  - Against this current DB only: 202 rows already exist as academy members, 69 rows have a user account by expected student email but no academy member row, and 411 rows are missing both user + academy member.
  - Name-only fallback matching returned 0 exact normalized hits in this DB; useful matches came from student code / generated email patterns.
- Output files:
  - `tmp_m1_m4_pending_accounts.csv` — pending rows grouped by `needs_user_and_member` vs `needs_member_only`
  - `tmp_create_missing_m1_m4_accounts.sql` — idempotent draft SQL using a temp staging table, user creation, STUDENT role attach, and academy member creation
- Decisions:
  - Do not run mutating inserts yet because the connected DB may not be the same dataset/site the user means by `nuxnan.com`.
  - Keep the SQL draft parameterized around `academy_id = 1` from the current DB and require user confirmation / target DB validation before execution.
- Verification:
  - Read-only Laravel bootstrap query confirmed DB connectivity and record counts.
  - PDF extraction completed and generated 682 ม.1/ม.4 roster rows.
  - Comparison artifacts were written successfully to workspace files above.
  - User later confirmed this is the target database, so the live create flow was executed.
  - Live execution result: created 407 users, 476 students, 476 academy_members, attached 456 missing `STUDENT` role links, reused 69 existing users, and skipped 206 already-existing member rows.
  - Post-run verification shows 678 unique student codes from the PDF now have matching `academy_members` and `students` rows in academy 1.
- Additional output files:
  - `tmp_m1_m4_creation_result.json` — live mutation summary and created rows
  - `tmp_m1_m4_db_snapshot.json` — current DB snapshot for the 678 unique student codes
  - `tmp_create_all_m1_m4_accounts_idempotent.sql` — fuller idempotent SQL snapshot matching current `users + students + academy_members + STUDENT role` state

# 2026-06-20 Student classroom/year-rollover flow analysis

- Scope: analyze the real classroom-management flow in code before changing class/room data for a new academic year.
- Files inspected:
  - `api/nuxnanravel/app/Services/StudentEnrollmentService.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php`
  - `api/nuxnanravel/app/Models/Classroom.php`
  - `api/nuxnanravel/app/Models/ClassroomStudent.php`
  - `api/nuxnanravel/app/Models/StudentAcademicInfo.php`
  - `api/nuxnanravel/app/Services/ClassroomService.php`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/TranscriptController.php`
  - `api/nuxnanravel/routes/learn/academy.php`
  - `api/nuxnanravel/database/migrations/2026_04_08_050000_refactor_classroom_enrollment_system.php`
  - `ui/pages/academies/[name]/admin/gradebook/classrooms/index.vue`
  - `ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue`
- Findings:
  - `classroom_students` is the intended source of truth for student-to-room assignment across academic years; `students.class_level` and `students.class_section` are maintained mainly as backward-compatible current snapshots.
  - Backend already supports the critical lifecycle methods: enroll, transfer between rooms, unenroll with status (`transferred` / `graduated` / `dropped`), and bulk classroom promotion to a new room.
  - `student_academic_info` is a secondary academic-status layer that stores current grade/class text, classroom link, academic year/semester, and study status; it is updated together with active enrollment but is not the primary history source.
  - Transcript/reporting flows already depend on active/historical `classroom_students`, so annual updates must preserve history by closing old enrollment rows and creating new active rows rather than overwriting only `students.class_level`.
  - Admin UI currently exposes classroom CRUD plus add/remove students and student-number edits, but no obvious full academic-year rollover wizard for create-next-year-room + promote + graduate + repeat/manual reassignment.
- Risks:
  - If staff update only `students.class_level/class_section`, old screens may look right temporarily while the real enrollment history, transcripts, and room counts drift out of sync.
  - Graduation and annual promotion need different outcomes: final-year students should end with `graduated`, while continuing students should get closed old enrollment rows plus new active enrollment rows in next-year classrooms.
  - Mid-year room moves should stay inside the same academic year and use transfer flow, not the annual promotion flow.
- Verification plan:
  - Define a canonical yearly data-update sequence before mutating student/classroom data.
  - Map each real-world case (mid-year transfer, semester change, new academic year, graduation, repeater) to the correct backend method / data-state transition.

# 2026-06-20 Phase M — Gamification & Classroom Leaderboard Planning

- Scope: Implement Level/XP system for School/Academy level and Classroom Point Leaderboard based on the user's detailed design in `.agents/design-ref/phase-m-gamification-detailed.md`.
- Features to Implement:
  - Database schema for XP logs and period-based aggregates: `xp_events`, `school_xp_cycles`, `classroom_point_cycles`.
  - Services: `XpService` (award XP, handle levels), `ClassroomPointsService` (award points, fetch leaderboard rankings).
  - Observers and Event Hooks: Automatically trigger XP/Points on post creation, likes, comments, attendance, course completion, achievements, assignment submission, etc.
  - Endpoints: summary of school level progress, leaderboard of top classrooms, recent audit logs for administrators.
  - Frontend: Integrate `SchoolLevelCard` in left sidebar, `SchoolClassroomLeaderboard` in right sidebar, cover level badge, and an optional admin audit view.
  - Cron/Scheduler: Seed next cycle data (weekly/monthly) and backfill existing data to compute historical XP.
- Key Architectural Decisions:
  - XP/Points will be awarded real-time on trigger events.
  - Reset is handled cleanly via temporal keys (`Y-m`, `o-\WW`) without deleting old logs.
  - XP rates will be configurable via config files (`config/xp_rates.php` and `config/gamification.php`).
- Risks:
  - High database traffic from synchronous observers; could be deferred to queue jobs if scale increases.
  - Race conditions on aggregates are prevented by using Eloquent `increment` operations (atomic on DB side).
- Verification Plan:
  - Run database migrations successfully.
  - Test observers by creating posts/likes/comments and asserting new XP events.
  - Verify API endpoints return correctly calculated XP thresholds and leaderboard records.
  - Verify Nuxt components render level progress bars and leaderboards accurately.

## 2026-06-21 Phase N — Polish + A11y + Mobile UX Plan

### 0. สรุปขอบเขตและเป้าหมาย (Scope & Objectives)
ยกระดับประสบการณ์ผู้ใช้งาน (UX/UI) ของ nuxnan LMS บน Frontend Nuxt ในหน้าหลักและส่วนประกอบย่อยต่างๆ โดยเน้นความลื่นไหล ความรวดเร็วในการแสดงผล (Skeletons/Lazy load) การจัดหน้าบนมือถือ (Mobile Drawer/Touch Gestures) และการรองรับการเข้าถึงสำหรับผู้ทุพพลภาพ (A11y/Screen Reader/Focus Trap/Keyboard Navigation)

### 1. ลำดับการพัฒนาย่อย (Sub-phases)

#### **Phase N.0 — Skeleton Component System (Est. 1.5 hr)**
สร้างระบบ Placeholder shape ทดแทน Spinner ลดอาการสะดุดของหน้าตา (Cumulative Layout Shift - CLS)
- สร้าง Atom `SkeletonBox.vue` (`ui/components/Common/SkeletonBox.vue`)
- สร้าง Domain Skeletons 5 ตัว:
  - `FeedPostSkeleton.vue`
  - `GroupCardSkeleton.vue`
  - `MemberRowSkeleton.vue`
  - `StatGridSkeleton.vue`
  - `UpcomingEventsSkeleton.vue`
- แทนที่ spinner `svg-spinners:ring-resize` ด้วย Skeletons ในจุดหลักๆ (Feed tab, Members list, Groups grid)

#### **Phase N.1 — Empty State System (Est. 1.0 hr)**
แทนที่ Empty state ข้อความธรรมดาด้วย Component ที่มีความสวยงามและมีปุ่มสั่งงาน (CTA)
- สร้าง `EmptyState.vue` (`ui/components/Common/EmptyState.vue`)
- อัปเดต inline empty states ใน Groups tab, Members tab, Upcoming events และหน้าแจ้งเตือน

#### **Phase N.2 — Error/Retry Inline Pattern (Est. 1.0 hr)**
จัดการ API call ล้มเหลวแบบนุ่มนวล แทนการเด้ง popup (SweetAlert) ตลอดเวลา
- สร้าง `ErrorRetry.vue` (`ui/components/Common/ErrorRetry.vue`)
- นำมาใช้ใน widget ต่างๆ (Upcoming events, Pinned announcement, Classroom leaderboard) ให้สามารถกด Reload ข้อมูลใหม่เฉพาะจุดได้

#### **Phase N.3 — Mobile Sidebar Drawer (Est. 2.0 hr)**
แก้ปัญหา Widget สำคัญหายไปบนหน้าจอมือถือ (เนื่องจากถูกซ่อนด้วย `hidden lg:flex`)
- สร้าง `SidebarDrawer.vue` (`ui/components/Common/SidebarDrawer.vue`)
- เพิ่มปุ่ม "เมนูลัด" และ "สถิติ" บน mobile view เพื่อแสดง Drawer ซ้าย/ขวา
- จัดกลุ่ม Sidebar widgets (QuickMenu, LevelCard, StatGrid, UpcomingEvents, Leaderboard) เข้าใน Drawer

#### **Phase N.4 — Modal Accessibility (Est. 1.5 hr)**
เพิ่มการควบคุมผ่านคีย์บอร์ดและ Screen Reader ใน Modals ทั้งหมด
- สร้าง Composable `useFocusTrap` (`ui/composables/useFocusTrap.ts`) เพื่อขัง Focus และรองรับปุ่ม `Escape`
- อัปเดต `GroupManageModal`, `GroupCreateModal`, และ `CreatePostModal` ให้รองรับ focus trap และกำหนด ARIA attributes (`role="dialog"`, `aria-modal="true"`)

#### **Phase N.5 — Dropdown Accessibility (Est. 1.0 hr)**
รองรับคีย์บอร์ดนำทาง (Arrow keys + Enter + Escape) ในช่องเลือกตัวเลือก
- ปรับปรุง `PostAsSelector`, `NotificationBell`, post type pickers, และ autocomplete input
- กำหนด ARIA attributes (`role="listbox"`, `role="option"`, `aria-expanded`)

#### **Phase N.6 — Form Realtime Validation (Est. 1.5 hr)**
สร้างระบบตรวจสอบข้อมูลในฟอร์มแบบทันทีพร้อมแสดงผลข้อผิดพลาด
- สร้าง Composable `useFieldValidation` (`ui/composables/useFieldValidation.ts`)
- สร้าง Wrapper `FormField.vue` (`ui/components/Common/FormField.vue`)
- ประยุกต์ใช้ในฟอร์มสร้างส่วนงานและแก้ไขข้อมูลสมาชิก

#### **Phase N.7 — Animation Polish + Motion-reduce (Est. 1.0 hr)**
ปรับปรุงอนิเมชันให้ลื่นไหลและเคารพการตั้งค่าลดการเคลื่อนไหว (A11y prefers-reduced-motion)
- ตั้งค่า `motion-reduce:` ใน Tailwind
- สกัดและปิดกั้นอนิเมชันหากผู้ใช้งานเปิดโหมด prefers-reduced-motion (รวมถึง Skeletons pulse และ Vue Transitions)

#### **Phase N.8 — Touch Gestures (Est. 1.5 hr)**
เพิ่ม Gesture สไลด์ปัดหน้าจอเปลี่ยน Tab หรือรูดปิด Modal ในมือถือ
- สร้าง Composable `useSwipe` (`ui/composables/useSwipe.ts`)
- ผูก Swipe behavior ในหน้า Profile tabs (`[name].vue`) และ Drawers

#### **Phase N.9 — Performance: Lazy Load + Intersection (Est. 1.0 hr)**
ทำ Lazy loading รูปภาพขนาดใหญ่และ Defer API loading สำหรับ Widget ที่อยู่นอกหน้าจอ
- กำหนด `loading="lazy"` + `decoding="async"` บน `<img>`
- สร้าง Composable `useIntersectionLoad` (`ui/composables/useIntersectionLoad.ts`) โหลดข้อมูล Widget เฉพาะเมื่อ scroll มาถึง (เช่น Leaderboard)

#### **Phase N.10 — QA checklist + Lighthouse pass (Est. 1.0 hr)**
ตรวจสอบคุณภาพโดยละเอียด รัน Lighthouse คาดหวังคะแนน A11y > 95% และ Performance > 80%

---

### 2. ลำดับแผนการส่งมอบ (Commit & PR Strategy)
แบ่งออกเป็น 6 commits เพื่อไม่ให้การเปลี่ยนแปลงทับซ้อนและจัดระดับความเสี่ยง:
1. `feat(ui): skeleton component system (5 variants)` (N.0)
2. `feat(ui): empty state + error retry components` (N.1 + N.2)
3. `feat(ui): mobile sidebar drawer (left + right)` (N.3)
4. `feat(ui): a11y — focus trap, keyboard nav, ARIA in modals` (N.4 + N.5)
5. `feat(ui): form realtime validation + motion-reduce` (N.6 + N.7)
6. `feat(ui): touch gestures + intersection lazy load` (N.8 + N.9)

---

### 3. แผนการตรวจสอบและจำลองสถานการณ์ (Verification Plan)
- **TypeScript & Build Check:** รัน `npm run build` และ `npx vue-tsc --noEmit`
- **A11y Audit:** ตรวจสอบ Focus Ring ทั่วไป, การกด Tab วนใน modal, และรัน Lighthouse
- **Viewport Smoke Test:** ทดสอบขนาด 380px (Mobile), 800px (Tablet), และ 1280px+ (Desktop)
- **Reduced Motion emulation:** สลับโหมดใน DevTools และตรวจว่าอนิเมชันหยุดนิ่ง
- **Lazy loading check:** ตรวจสอบ Network tab ว่าไม่มีการโหลดรูปและข้อมูล Widget ก่อนความจำเป็น

---

### 4. ความเสี่ยงและการจัดการ (Risks & Mitigations)
- **iOS Safari body scroll lock conflict:** `overflow: hidden` บน `body` มักใช้ไม่ได้ผลบน iOS Safari ให้รองรับ workaround `position: fixed` เสมอ
- **Swipe vs vertical scroll conflict:** หลีกเลี่ยง swipe ผิดพลาดด้วยการตั้งค่า threshold และตรวจจับทิศทางแกน X เทียบกับ Y อย่างเหมาะสม
- **CLS (Cumulative Layout Shift) จาก Skeleton:** ต้องกำหนดขนาดความกว้าง/ความสูงของ Skeleton ให้ตรงหรือใกล้เคียงกับ content จริงเพื่อไม่ให้เกิดการสั่นไหวของเลย์เอาต์

---

## 2026-06-21 Phase N Finish & Verification Summary

- **Scope:** Completed Phase N Polish + Accessibility + Mobile UX updates.
- **Implemented & Verified:**
  - **Skeletons, Empty State, and ErrorRetry:** Added fallback skeletons, `EmptyState` with admin CTAs, and inline `ErrorRetry` components inside notification bell, upcoming events widgets, and leaderboard to eliminate CLS and layout shifts.
  - **Mobile Sidebar Drawer:** Created left and right slide-in `SidebarDrawer` panels for mobile/tablet responsive access to quick menus, stats grid, calendar events, and classroom leaderboard.
  - **Modal and Dropdown A11y:** Hooked `useFocusTrap` on all creation/management modals. Added keyboard arrow navigation (Up/Down/Enter/Escape) and ARIA listbox/option role bindings to `PostAsSelector.vue`, `NotificationBell.vue`, and `MemberAutocompleteInput.vue`.
  - **Touch Gestures & Performance:** Bound horizontal `useSwipe` touch gestures on the main profile views for responsive tab switching. Updated all large images and gallery attachments inside `FeedPost.vue` and `SchoolPinnedAnnouncement.vue` to use `loading="lazy"` + `decoding="async"`, and applied `motion-reduce:transition-none` to override hover scaling animations.
- **Verification status:**
  - Dev server verified via successful startup on port 3001 using `npm run dev` with no Vue compilation or syntax errors.
  - Keyboard arrow navigation and click-outside closure fully wired in `MemberAutocompleteInput.vue`.

## 2026-06-21 Phase N follow-up - school navigation widget alignment

- Scope: finish the academy homepage Phase N/UI alignment requested by the user by switching the large center-column `AcademyActionGuide` treatment into a sidebar/mobile widget flow that matches `.agents/design-ref`.
- Findings:
  - `ui/pages/academies/[name].vue` still renders `AcademyActionGuide` inside the main content column, while `SchoolQuickMenu` only exists in the mobile drawer, so the page currently has two different navigation concepts.
  - `ui/components/school/SchoolQuickMenu.vue` is still a simple static list and does not reuse the richer destination/CTA logic already implemented in `ui/composables/useAcademyNavigation.ts`.
  - `AcademyActionGuide.vue` currently owns the "ศูนย์นำทางโรงเรียน" role-aware navigation experience, including join CTA and pending hint, but its large card layout is the wrong placement for the current design direction.
- Intended files:
  - `ui/components/school/SchoolQuickMenu.vue`
  - `ui/pages/academies/[name].vue`
- Decisions:
  - Reuse `useAcademyRole` + `useAcademyNavigation` inside `SchoolQuickMenu` so the widget inherits the same role-aware visibility and CTA behavior rather than maintaining a second static menu map.
  - Remove the center-column `AcademyActionGuide` mount from `[name].vue` and mount `SchoolQuickMenu` in the desktop sidebar plus the existing mobile drawer.
  - Keep the UI compact and widget-like, following the `design-ref` sidebar pattern instead of the larger grid-card treatment.
- Risks:
  - `useAcademyNavigation` returns mixed same-page hash links and full routes, so the widget needs to handle current-page tab navigation cleanly without breaking route navigation.
- Verification plan:
  - Run a focused frontend type/build check if practical.
  - Read back the affected academy homepage template to confirm the center card is removed and the widget is mounted in the sidebar/drawer.
- Verification update:
  - Readback confirms `ui/pages/academies/[name].vue` no longer mounts `AcademyActionGuide` in the main content column.
  - Readback confirms `SchoolQuickMenu` is now mounted in the desktop sidebar and the existing left mobile drawer, both with the same pending/join wiring.
  - `cmd /c npx.cmd vue-tsc --noEmit` still fails because of large pre-existing repo-wide TypeScript/tooling issues unrelated to this follow-up, including existing component typing errors and the known `vue-router/volar/sfc-route-blocks` export-resolution problem.

## 2026-06-21 School homepage design-ref alignment pass

- Scope: continue tightening `ui/pages/academies/[name].vue` so the school homepage follows `.agents/design-ref/School Homepage.html` more closely as a cohesive homepage, not just a widget drop-in.
- Files touched:
  - `ui/pages/academies/[name].vue`
  - `ui/components/school/SchoolQuickMenu.vue`
- Completed:
  - Refined the hero shell with the newer rounded cover treatment, stronger overlay, upgraded logo frame, academy handle line, verified badge support, and share action.
  - Added pinned announcement loading plus `SchoolPinnedAnnouncement` rendering above the feed stream to match the design’s pinned-content rhythm.
  - Switched the main content shell toward the design layout by separating a left sticky widget rail from the main content and constraining the right rail to extra-large layouts.
  - Added tab count badges and homepage-oriented tab wording directly in the school homepage template.
- Risks / gaps:
  - The hero stats row still mixes legacy inline stat chips with the newer hero treatment because the file is already heavily in-flight and contains multiple overlapping homepage phases.
  - Full compile-green verification remains blocked by repo-wide TypeScript issues unrelated to this page.
- Verification:
  - Readback confirmed `SchoolPinnedAnnouncement`, `loadPinnedAnnouncements`, `shareAcademy`, `academyVerified`, `academyHandle`, `heroStats`, and `getTabCount` are now wired in `[name].vue`.
  - Structural readback confirmed the page now uses a left sidebar + main + right sidebar shell instead of the earlier single sidebar column.
  - Follow-up readback confirmed the malformed hero-shell closing tags were corrected, the quick-menu now has a reachable `about` target, and the right rail/mobile right drawer now place the stats widget ahead of events and leaderboard to better match the design reference.

---

## 2026-06-21 Canonical Academic Year Rollover — Refined Plan v2

### 0. ทำไมต้อง refine แผนเดิม

แผนแรก (ที่ส่งให้ผู้ใช้ใน chat) ระบุ "flow ที่ควรเป็น" ได้ครบ 8 ขั้นตอน แต่ยังขาด 7 จุดที่ทำให้ implement จริงไม่ได้:

| # | ช่องว่างของแผน v1 | หลักฐานจากโค้ดจริง |
|---|---|---|
| G1 | ไม่มี method `graduateStudent`, `repeatStudent`, `dropStudent` ใน `StudentEnrollmentService` — มีแค่ enroll/transfer/unenroll/promote | StudentEnrollmentService.php:23-179 |
| G2 | `promoteClassroom` ใช้ `transferStudent` ภายใน ไม่มี semantic แยก "ข้ามปี" vs "ในปี" | StudentEnrollmentService.php:152-179 |
| G3 | `enrollStudent` update `currentAcademicInfo` แบบ overwrite — `student_academic_info` ของปีเก่าหาย | StudentEnrollmentService.php:59-68 |
| G4 | `classroom_students` ไม่มี unique constraint `(classroom_id, student_id)` ที่บังคับกับ `status='active'` — เปิดช่องให้ active row ซ้อน | refactor_classroom_enrollment_system migration ไม่มี unique |
| G5 | ไม่มี preview / dry-run ก่อน commit rollover ทั้งโรงเรียน — กระทบนักเรียนเป็นพัน rollback ยาก | ไม่มีในโค้ด |
| G6 | ไม่มี audit / event เมื่อ status เปลี่ยน — ไม่มี notification, transcript ไม่รู้ | Service ไม่ fire event |
| G7 | UI admin ไม่มี wizard "เปิดปีการศึกษาใหม่" — มีแค่หน้าจัดการห้อง/รายคน | classrooms/index.vue, classrooms/[id].vue |

นอกจากนี้:
- `Student::currentAcademicInfo` ผิดรูปแบบ Eloquent (บันทึกไว้ใน แผน 2026-06-17 §0.D)
- ค่า `ClassroomStudent::STATUS_*` ที่ใช้จริง: `active`, `transferred`, `graduated` — ขาด `dropped`, `repeating`, `promoted`

### 1. หลักการของแผนรอบนี้

1. **Backend foundation ก่อน UI** — กันคนเข้า UI แล้วเขียนข้อมูลผ่าน path ที่ยังไม่ถูก
2. **ทุก state transition ผ่าน Service เดียว** — controller ห้ามเขียน `classroom_students` ตรง
3. **Year rollover = batch transaction** — มี preview → commit → undo (ภายใน 24 ชม.)
4. **บันทึก snapshot ทุกครั้ง** — `student_academic_info` ไม่ overwrite, สร้าง row ใหม่ + mark `is_current`
5. **UI = wizard ทีละขั้น** — ไม่ใช่ "ปุ่มเดียวเลื่อนทั้งโรงเรียน" เพราะ blast radius สูงสุด
6. **ทุก phase deploy ได้เดี่ยว**
7. **Idempotent** — รัน rollover ซ้ำที่ปีเดิม ต้องไม่ duplicate row

### 2. Data model & semantic ที่ต้อง lock ก่อนเขียนโค้ด

#### 2.1 Status lifecycle ของ `classroom_students.status`

```
                      transferred (ย้ายห้องในปีเดิม)
                      promoted    (เลื่อนชั้นข้ามปี)   <-- เพิ่มใหม่
active --left_at-->   graduated   (จบการศึกษา)
                      dropped     (ลาออก/พ้นสภาพ)    <-- เพิ่มใหม่
                      repeating   (ซ้ำชั้น)          <-- เพิ่มใหม่
```

กฎ:
- 1 student ใน 1 academic_year มี active row ได้มากที่สุด 1 ห้อง
- history row ไม่จำกัด query ผ่าน `academic_year_id`
- `promoted` ต่างจาก `transferred` ตรงปีการศึกษา

#### 2.2 `student_academic_info` ความหมายใหม่

- 1 student × 1 academic_year = 1 row (unique)
- `is_current = true` ได้แค่ row เดียวต่อ student (partial unique)
- Rollover: row เก่า `is_current=false`, row ใหม่ `is_current=true`
- เก็บ snapshot grade, classroom_id, classroom_full ของปีนั้น ๆ — ไม่ overwrite

#### 2.3 Sync rule ของ `students.class_level / class_section`

- เป็น denormalized snapshot ของปีปัจจุบันเท่านั้น
- update เมื่อ enrollment ของ row `is_current=true` เปลี่ยน
- graduate/drop -> set NULL (ไม่ใช่ค่าปีสุดท้าย) เพื่อ filter "active students" ง่าย

### 3. Phase-by-Phase Plan (10 phases, ~26 ชม.)

#### Phase 0 — Preflight (1 ชม.)
- 0.1 branch `feature/academic-year-rollover`
- 0.2 backup ตาราง: `students`, `classroom_students`, `student_academic_info`, `classrooms`, `academic_years` ลง `.agents/backups/2026-06-21/`
- 0.3 รัน inventory query:
  - จำนวน active enrollment ต่อ academy
  - จำนวน student ที่ active row > 1 (ต้องเป็น 0; ถ้าไม่ใช่ = data dirty)
  - จำนวน student ที่ `class_level` ไม่ตรงกับ active classroom (sync drift)
- 0.4 บันทึก inventory ลง `.agents/backups/2026-06-21/preflight.md`

**Deliverable:** baseline report + backup

#### Phase 1 — Status Constants & Schema Hardening (2 ชม.)
- 1.1 เพิ่มค่าใน `ClassroomStudent` model: `STATUS_PROMOTED`, `STATUS_DROPPED`, `STATUS_REPEATING`, `STATUS_SUPERSEDED`
- 1.2 Migration `add_rollover_columns_to_classroom_students`:
  - `rollover_batch_id` (uuid, nullable, indexed)
  - `created_by_user_id` (FK users, nullable) audit
  - ขยาย `status` (enum/varchar) รองรับค่าใหม่
  - partial unique `(classroom_id, student_id)` WHERE `status='active'` (MySQL 8 functional index หรือ generated column)
  - composite index `(academy_id, academic_year_id, status)`
- 1.3 Migration `normalize_student_academic_info`:
  - unique `(student_id, academic_year)`
  - partial unique `(student_id)` WHERE `is_current=true`
  - แก้ data ที่ละเมิดก่อน apply (ใช้ผลจาก 0.3)
- 1.4 แก้ `Student::currentAcademicInfo()` ตาม แผน 2026-06-17 §0.D
- 1.5 Feature test: insert duplicate active -> throw unique violation

**Commit:** `feat(enrollment): add rollover batch tracking and status integrity`

#### Phase 2 — Service Layer Expansion (3 ชม.)
แตกเป็น 2 service:
- `StudentEnrollmentService` — single-student ops (เดิม + เพิ่ม)
- `AcademicYearRolloverService` — batch ops (ใหม่)

**`StudentEnrollmentService` เพิ่มเมธอด**
- 2.1 `graduateStudent(Student, Classroom, ?date $effectiveAt)` — close active = `graduated`, `students.status='graduated'`, snapshot fields NULL
- 2.2 `dropStudent(Student, Classroom, string $reason, ?date)` — status `dropped`, `students.status='inactive'`
- 2.3 `repeatStudent(Student, Classroom $sameOrNewSection, ?int $studentNumber)` — close เดิม `repeating`, create active ใหม่ปี+grade เดิม
- 2.4 `promoteStudent(Student, Classroom $fromOldYear, Classroom $toNewYear, string $batchId)` — close เดิม `promoted`, create active ใหม่, **สร้าง student_academic_info row ใหม่ + is_current**
- 2.5 รีไฟน์ `transferStudent` ให้ assert `from.academic_year_id == to.academic_year_id`; ถ้าไม่ใช่ -> redirect to `promoteStudent` + log
- 2.6 ทุก method fire Event: `StudentEnrolled`, `StudentPromoted`, `StudentGraduated`, `StudentDropped`, `StudentRepeated` (listener phase 7)

**`AcademicYearRolloverService` (ใหม่)**
- 2.7 `planRollover(Academy, AcademicYear $from, AcademicYear $to, array $mapping): RolloverPlan` — ไม่เขียน DB
  - mapping `[ from_classroom_id => [ to_classroom_id => [...student_ids], 'graduate' => [...ids], 'drop' => [...ids], 'repeat' => [...ids] ] ]`
  - return summary + warnings
- 2.8 `commitRollover(RolloverPlan): RolloverBatch` — transaction เดียว, ใช้ batch_id, เรียก Phase 2.1-2.5
- 2.9 `previewRollover(Academy, AcademicYear $from, AcademicYear $to)` — auto-suggest mapping ตามชั้น (ม.1->ม.2; ม.3 ไม่มีปีถัด -> graduate)
- 2.10 `undoRollover(string $batchId, ?User $by)` — เปิดได้ภายใน 24 ชม. หรือก่อนวันเปิดเทอม; revert row ที่ batch_id ตรง

**Tests:**
- promote 30 คน -> snapshot ใหม่ถูกต้อง
- graduate ม.6 -> `student_academic_info` ครบ + `students.status='graduated'`
- commit ซ้ำ batch เดิม -> idempotent
- undo ภายใน window -> state กลับเหมือนก่อน commit

**Commit:** `feat(enrollment): add graduate/drop/repeat methods and rollover service`

#### Phase 3 — Controller & API Surface (2 ชม.)
- 3.1 `AcademyRolloverController` ใหม่:
  - `POST /api/academies/{academy}/rollover/preview` body: `{from_year_id, to_year_id}`
  - `POST /api/academies/{academy}/rollover/plan` body: mapping เต็ม -> summary + warnings
  - `POST /api/academies/{academy}/rollover/commit` body: plan_id (cached) -> ทำจริง, return batch_id
  - `POST /api/academies/{academy}/rollover/undo` body: batch_id
  - `GET /api/academies/{academy}/rollover/batches` — history
- 3.2 `StudentEnrollmentController` เพิ่ม endpoint:
  - `POST .../students/{student}/graduate`
  - `POST .../students/{student}/drop`
  - `POST .../students/{student}/repeat`
- 3.3 FormRequest แยก: `RolloverPlanRequest`, `RolloverCommitRequest`, `GraduateStudentRequest`...
- 3.4 Policy: academy_admin + principal เท่านั้นที่ commit/undo ได้; teacher ทำได้แค่ preview
- 3.5 Feature tests: forbid cross-academy + role gate

**Commit:** `feat(api): add academic year rollover endpoints`

#### Phase 4 — Frontend: Single-Student Status Actions (3 ชม.)
ก่อน wizard ใหญ่ — เปิดความสามารถ "เปลี่ยนสถานะรายคน" ผ่าน UI ก่อน

- 4.1 ใน `classrooms/[id].vue` ขยายแถวนักเรียน เพิ่ม dropdown action: ย้ายห้อง / เลื่อนชั้น / จบการศึกษา / ซ้ำชั้น / ลาออก
- 4.2 `StudentStatusActionModal.vue` รับ `action` prop -> render form ตามชนิด (เลือกห้องปลายทาง / เหตุผล / วันที่มีผล)
- 4.3 composable `useStudentEnrollmentActions()` wrap API ของ Phase 3.2
- 4.4 Toast + refresh list + log entry ใน activity sidebar
- 4.5 Empty states: นักเรียนที่ graduate/drop ดูในแท็บ "ออกจากห้อง" แยก

**Commit:** `feat(classroom): add per-student status actions (graduate/drop/repeat)`

#### Phase 5 — Frontend: Year Rollover Wizard (5 ชม.)
หน้า `pages/academies/[name]/admin/gradebook/rollover/index.vue`

Wizard 5 steps:
1. **เลือกปีต้นทาง/ปลายทาง** — preview -> default mapping
2. **ตรวจห้องของปีใหม่** — ต้องมีห้องครบทุก grade รับนักเรียน; ขาด -> ปุ่ม "สร้างห้องอัตโนมัติ" ตาม pattern ปีก่อน
3. **จัดสรรนักเรียน** — 4 ตะกร้า: เลื่อนชั้นปกติ / ย้ายระดับเดิม / จบการศึกษา / ซ้ำหรือลาออก
   - drag-drop หรือ multiselect -> bucket
   - filter: ตามห้องเดิม, คะแนน, จำนวนวันลา
4. **Preview & Warnings** — ผลกระทบ + warning (เช่น "นักเรียน 3 คนยังไม่จัดสรร")
5. **Commit** — ปุ่ม confirm + พิมพ์ "เปิดปีการศึกษา {year}" -> POST commit; แสดง progress + batch_id

หลัง commit:
- แสดงผลลัพธ์ + ปุ่ม "Undo (เหลือเวลา 23:59)"
- export Excel summary

UI components ใหม่:
- `RolloverYearPicker.vue`
- `RolloverClassroomChecklist.vue`
- `RolloverStudentBucket.vue`
- `RolloverPreviewSummary.vue`
- `RolloverCommitPanel.vue`

**Commit:** `feat(rollover): add academic year rollover wizard`

#### Phase 6 — Reports & Downstream Sync (3 ชม.)
- 6.1 `TranscriptController` — ตรวจว่า query ใช้ `classroom_students` filter ด้วย `academic_year_id`; ถ้า hard-code ปีปัจจุบัน -> แก้ให้รับ year param
- 6.2 หน้า list นักเรียน (academy admin / member list / attendance) — ทุกที่ที่อาศัย `students.class_level`:
  - "active classroom for current year" หรือ "snapshot อย่างเดียว"?
  - ต้องการความถูกต้อง -> join `classroom_students`
- 6.3 Attendance: เช็ค record มี `academic_year_id` หรือ inherit จาก classroom — ถ้าไม่มี เพิ่ม
- 6.4 Search/filter student แยก scope "ปัจจุบัน" vs "ทุกปี"

**Commit:** `fix(reports): scope queries by academic_year after rollover`

#### Phase 7 — Notifications & Events (2 ชม.)
- 7.1 `StudentGraduated` -> notify นักเรียน + ผู้ปกครอง + ครูประจำชั้น; sync `users.status`
- 7.2 `StudentPromoted` -> notify ผู้ปกครอง + activity feed
- 7.3 `RolloverCommitted` -> notify academy admins + email summary
- 7.4 `StudentDropped` -> revoke academy_member access (inactive)
- 7.5 ใช้ Laravel Notification + Reverb broadcast (ตามที่มี)

**Commit:** `feat(notifications): wire enrollment events to notifications`

#### Phase 8 — Audit & History UI (2 ชม.)
- 8.1 ใช้ `App\Traits\Auditable` (จาก แผน 2026-06-17 §9.5) apply กับ `ClassroomStudent`, `StudentAcademicInfo`
- 8.2 UI: tab "ประวัติการลงห้อง" ในหน้า student master profile (แผน 2026-06-17 Phase 4) — timeline ปี/ห้อง/status/leave_reason/โดยใคร
- 8.3 หน้า rollover history admin: list batch + กดดูรายละเอียดได้

**Commit:** `feat(enrollment): add audit trail and history UI`

#### Phase 9 — Backfill & Data Repair (2 ชม.)
- 9.1 Artisan `enrollment:repair-dirty-data {--dry-run} {--academy=}`:
  - active row > 1 -> ใช้ row created_at ใหม่สุด ปิดที่เหลือเป็น `superseded`
  - `class_level` ไม่ match active classroom -> re-sync จาก pivot
  - `is_current=true` > 1 row -> เก็บปีล่าสุด ลดที่เหลือ
- 9.2 Artisan `enrollment:backfill-academic-info {--year=}` — สร้าง row สำหรับนักเรียนที่ขาดประวัติของปีนั้น
- 9.3 รัน dry-run บน production-like, review, รันจริง
- 9.4 บันทึก count ลง `.agents/backups/2026-06-21/repair-report.md`

**Commit:** `chore(enrollment): backfill and repair classroom enrollment data`

#### Phase 10 — Cleanup, Docs, Memory (1 ชม.)
- 10.1 ลบ legacy paths ที่เขียน `students.class_level` ตรง (grep หาให้หมด)
- 10.2 อัพเดท `.agents/worklog.md`
- 10.3 เขียน `docs/academic-year-rollover.md` พร้อม flow diagram
- 10.4 บันทึก memory: `project_enrollment_rollover.md` ลง MEMORY.md

**Commit:** `docs(enrollment): finalize rollover documentation`

### 4. Execution Order & Estimates

| ลำดับ | Phase | ประเภท | เวลา | Risk |
|---|---|---|---|---|
| 1 | 0 Preflight | Ops | 1 ชม. | ต่ำ |
| 2 | 9a Initial repair (subset) | DB | 1 ชม. | กลาง |
| 3 | 1 Schema hardening | DB | 2 ชม. | กลาง |
| 4 | 2 Service expansion | Backend | 3 ชม. | กลาง |
| 5 | 3 API surface | Backend | 2 ชม. | ต่ำ |
| 6 | 4 Single-student UI | Frontend | 3 ชม. | ต่ำ |
| 7 | 6 Reports sync | Full-stack | 3 ชม. | กลาง |
| 8 | 5 Rollover wizard | Frontend | 5 ชม. | สูง |
| 9 | 7 Events/notify | Backend | 2 ชม. | ต่ำ |
| 10 | 8 Audit + history UI | Full-stack | 2 ชม. | ต่ำ |
| 11 | 9b Final backfill verification | DB | 1 ชม. | กลาง |
| 12 | 10 Cleanup | Cleanup | 1 ชม. | ต่ำ |

**รวม ≈ 26 ชม.** ~10 commits/PR แยกอิสระ

### 5. Verification per Phase

- ทุก phase backend: `./vendor/bin/pint && php artisan test --filter=Enrollment`
- ทุก phase frontend: `npm run dev` smoke + reduced-motion + 3 viewport
- หลัง Phase 2/3/5: end-to-end manual ตาม persona: academy admin / homeroom teacher / student / parent
- หลัง Phase 9: ตรวจ inventory เทียบ baseline (Phase 0.3) — diff ต้องอธิบายได้ทุก row

### 6. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| MySQL partial unique ไม่ support บน 5.7 | กลาง | สูง | ตรวจ version ก่อน; fallback application-level check + composite unique `(classroom_id, student_id, status)` |
| Rollover commit ค้างกลางทาง | ต่ำ | สูง | DB::transaction; `batch_id` ทำให้ rollback partial ได้ |
| Undo เกิน window แต่ admin อยากย้อน | กลาง | กลาง | undo เปิด 24 ชม.; เกินแล้วต้อง manual runbook |
| Data dirty มาก่อน — partial unique ใส่ไม่ได้ | สูง | สูง | Phase 9a (initial repair) ต้องวิ่งก่อน Phase 1 apply unique |
| Notification spam ตอน rollover พันคน | สูง | กลาง | digest/queued; ไม่ส่งทีละ event |
| Wizard step 3 drag-drop ช้าที่ 500+ นักเรียน | กลาง | กลาง | virtual list + bulk select; pagination ตามห้อง |
| Transcript/report เพี้ยนระหว่าง migration | กลาง | สูง | Phase 6 ต้อง deploy ก่อน Phase 5 (commit rollover) |
| `enrolled_at` เป็น date แต่ service ส่ง datetime | ต่ำ | ต่ำ | cast ให้ตรง หรือเปลี่ยน column เป็น datetime ใน 1.2 |

### 7. Dependency Order ที่บังคับ

```
Phase 0 -> Phase 9a (initial repair) -> Phase 1 (schema + unique)
                                              |
                                              v
                                          Phase 2 -> Phase 3 -> Phase 4
                                                                 |
                                                                 v
                                                             Phase 6 -> Phase 5
                                                                           |
                                                                           v
                                                                      Phase 7, 8
                                                                           |
                                                                           v
                                                                      Phase 9b -> 10
```

**สำคัญ:** Phase 9a (repair) ต้องวิ่งก่อน Phase 1 apply unique constraint ไม่งั้น migration จะ fail บน data dirty
**สำคัญ:** Phase 6 ต้อง deploy ก่อน Phase 5 ใช้งานจริง ไม่งั้น admin commit rollover แล้ว report เห็นเลขผิด

### 8. Out of Scope

- ❌ Multi-academy bulk rollover (ทำทีละ academy)
- ❌ Auto-detect "ใครควรซ้ำชั้น" จากเกรด (รอระบบเกรดครบ — งานคนละก้อน)
- ❌ Cross-school student transfer (flow แยก)
- ❌ Teacher rollover (homeroom assignment ของปีใหม่)
- ❌ Auto-generate classroom ปีใหม่บังคับ (มีเป็น hint button)

### 9. Decisions ที่ต้องยืนยันก่อนเริ่ม Phase 0

1. **Undo window 24 ชม. หรือ "จนกว่าจะเปิดเทอม"?** — recommendation: 24 ชม. + ปุ่ม "ปิด undo เร็ว" สำหรับ admin
2. **Partial unique บน MySQL version ปัจจุบัน?** — `SELECT VERSION()` ก่อนเขียน migration
3. **ENUM vs varchar สำหรับ status?** — recommendation: varchar + Laravel cast (เพิ่มค่าใหม่ง่าย)
4. **Wizard ขอ confirm step อย่างไร?** — recommendation: พิมพ์ชื่อปีการศึกษาเพื่อยืนยัน (เหมือน GitHub delete repo)
5. **`students.status` มีค่าอะไรบ้าง?** — ตรวจ schema ก่อน map `graduated`/`dropped`/`active` ให้ถูก

### 10. Decisions Locked (2026-06-21)

1. ✅ **Undo window** = 24 ชม. + ปุ่ม "ปิด undo เร็ว"
   - Phase 2.10 `undoRollover`: check `commit_at + 24h > now()` หรือ `undo_closed_at IS NULL`
   - Phase 5 หลัง commit: countdown timer + ปุ่ม "ยืนยันปิด undo ทันที"
   - Phase 1.2: เพิ่ม column `undo_closed_at` ใน rollover batch table

2. ✅ **Status column** = varchar + Laravel cast
   - Phase 1.2: `string('status', 32)` + index แทน enum
   - `ClassroomStudent` model: `protected $casts` + constant list + validation rule

3. ✅ **Commit confirmation** = พิมพ์ชื่อปีการศึกษา
   - Phase 5 Step 5: input `confirmationText` ต้อง === `${toYear.name}` ก่อน enable ปุ่ม
   - frontend disable จนกว่า exact match

4. ✅ **Unique strategy** = เช็ค MySQL version ก่อน
   - Phase 0.3: เพิ่ม `SELECT VERSION()` ใน inventory query
   - Phase 1.2: 8.0+ ใช้ functional index `((CASE WHEN status='active' THEN student_id END))`; 5.7 ใช้ generated column + unique
   - บันทึก choice ลง `.agents/backups/2026-06-21/preflight.md`

5. ✅ **`students.status` audit** = ทำใน Phase 0.3 inventory
   - เพิ่ม `SELECT status, COUNT(*) FROM students GROUP BY status`
   - ใช้ผลตัดสิน Phase 2.1/2.2 ว่า map ค่าไหน (เช่นมี `inactive` แต่ไม่มี `dropped` → ใช้ `inactive`)

→ **พร้อมเริ่ม Phase 0 ทันที** ไม่มี open question เหลือ

---

## 2026-06-21 Phase 2 — Service Layer Expansion (Detailed Plan)

### 0. Inputs to lock before coding

จาก Phase 0/1 ตรวจเสร็จแล้ว:

| ข้อ | ค่าจริง | กระทบ Phase 2 อย่างไร |
|---|---|---|
| MySQL version | 8.4.7 | ใช้ `JSON_TABLE`, `WITH RECURSIVE`, functional index ได้ |
| `students.status` enum | `'active','inactive','graduated','transferred'` | graduate → `'graduated'`; drop → `'inactive'` ไม่ต้องขยาย enum |
| `classroom_students.status` | varchar(32) (Phase 1) | รับค่าใหม่ promoted/repeating/superseded ได้ทันที |
| UNIQUE(classroom_id, student_id) | คงอยู่ | re-enroll ห้องเดิม = overwrite row (loses history) — ยอมรับใน Phase 2 |
| Functional unique is_current=1 | applied | Service ต้อง flip is_current เก่าก่อนสร้างใหม่ใน transaction เดียว |
| Callers ของ service | `ClassroomController` 3 จุด (line 428, 500, 553) | refactor ปลอดภัย ไม่กระทบ controller อื่น |
| `enrolled_at` / `left_at` | `date` (ไม่ใช่ datetime) | ทุก method ส่ง `today()` ไม่ใช่ `now()` |
| Format mismatch | `students.class_level="1"` vs `classrooms.grade_level="ม.1"` | helper `normalizeGradeLevel()` ตัด prefix ก่อน sync snapshot |
| Pending intake 476 คน | ไม่มี active row + มี `class_level` | Rollover service ต้อง surface เป็น input source ที่สอง |

### 1. หลักการ Phase 2

1. **Service ใหม่ทุก state transition** — controller ห้ามแตะ `classroom_students` ตรง
2. **One private helper `closeActiveEnrollment` ใช้ภายในทุก close path** — ไม่กระจาย logic
3. **Transaction ทุก method** — ใช้ `DB::transaction()` แบบ nesting-safe (Laravel จัดการ savepoint)
4. **Event fire ทุก transition** — listener ทำ Phase 7 (ยังไม่ใส่)
5. **Idempotent rollover commit** — รัน plan เดิม 2 ครั้ง ต้องไม่ duplicate (UNIQUE protect แล้ว)
6. **No silent overwrite of history** — `manageAcademicInfoSnapshot()` flip `is_current` เก่าก่อนสร้างใหม่
7. **Test ทุก commit** — code + test ใน commit เดียว

### 2. ออกแบบ API ของ `StudentEnrollmentService` (refactor + ขยาย)

#### 2.1 Private helper ใหม่

```php
private function closeActiveEnrollment(
    Student $student,
    Classroom $classroom,
    string $newStatus,             // STATUS_TRANSFERRED|PROMOTED|GRADUATED|DROPPED|REPEATING|SUPERSEDED
    ?string $reason = null,
    ?CarbonInterface $at = null,   // default today()
    ?string $batchId = null,
    ?int $userId = null
): ?ClassroomStudent
```
- หา active row (`classroom_id`, `student_id`, `status='active'`)
- ถ้าไม่มี → return null (caller ตัดสินใจว่า error หรือ skip)
- update: `status`, `left_at` (date), `leave_reason`, `rollover_batch_id`, `created_by_user_id`
- return updated row

#### 2.2 Private helper `manageAcademicInfoSnapshot`

```php
private function manageAcademicInfoSnapshot(
    Student $student,
    Classroom $newActive,          // ห้องปัจจุบันที่กลายเป็น active
    ?string $batchId = null
): StudentAcademicInfo
```
- flip ทุก SAI row ของ `$student` ให้ `is_current = false`
- หา SAI ของ year นี้ → ถ้ามี update, ไม่มี create
- set fields: classroom_id, current_grade, current_class, classroom_full, academic_year, is_current=true, semester (จาก classroom)
- ปลอดภัยกับ functional unique เพราะทำ flip → upsert ใน transaction เดียว

#### 2.3 Private helper `normalizeGradeLevel`

```php
private function normalizeGradeLevel(?string $value): ?string
{
    if ($value === null) return null;
    // "ม.1" -> "1", "1" -> "1", "ป.3" -> "3", "อ.2" -> "2"
    return preg_replace('/^[^0-9]+/u', '', trim($value)) ?: null;
}
```
- ใช้ใน `enrollStudent`/`promoteStudent` ตอน sync `students.class_level`

#### 2.4 Refactor `enrollStudent`

```php
public function enrollStudent(
    Student $student,
    Classroom $classroom,
    ?int $studentNumber = null,
    ?string $batchId = null,
    ?int $userId = null
): ClassroomStudent
```
เปลี่ยน:
- auto student_number เดิม
- `updateOrCreate` เดิม + เพิ่ม `rollover_batch_id`, `created_by_user_id`
- ใช้ `normalizeGradeLevel` ก่อน update `students.class_level`
- เรียก `manageAcademicInfoSnapshot` แทน inline update
- fire `StudentEnrolled($student, $enrollment)`

#### 2.5 Refactor `transferStudent` — assert same year

```php
public function transferStudent(
    Student $student,
    Classroom $fromClassroom,
    Classroom $toClassroom,
    string $reason = 'ย้ายห้อง',
    ?string $batchId = null,
    ?int $userId = null
): ClassroomStudent
```
- assert `$fromClassroom->academic_year_id === $toClassroom->academic_year_id`
  - ถ้าไม่ใช่ → throw `InvalidArgumentException("Use promoteStudent() for cross-year transfers")`
- `closeActiveEnrollment(..., STATUS_TRANSFERRED, $reason, today(), $batchId, $userId)`
- `enrollStudent($student, $toClassroom, null, $batchId, $userId)`
- fire `StudentTransferred(...)`

#### 2.6 New `graduateStudent`

```php
public function graduateStudent(
    Student $student,
    ?Classroom $classroom = null,  // ถ้า null หา active เอง
    string $reason = 'จบการศึกษา',
    ?CarbonInterface $at = null,
    ?string $batchId = null,
    ?int $userId = null
): ?ClassroomStudent
```
- หา active enrollment ถ้า `$classroom === null`
- `closeActiveEnrollment(..., STATUS_GRADUATED, $reason, $at, $batchId, $userId)`
- update `$student->status = 'graduated'`, clear `class_level`, `class_section`
- update SAI row ของ year ที่จบ → set `graduation_date = $at`, `study_status = 'graduated'`, `is_current = false`
- fire `StudentGraduated($student, $closed, $batchId)`

#### 2.7 New `dropStudent`

```php
public function dropStudent(
    Student $student,
    ?Classroom $classroom = null,
    string $reason,                // required (ลาออก/พ้นสภาพ/...)
    ?CarbonInterface $at = null,
    ?string $batchId = null,
    ?int $userId = null
): ?ClassroomStudent
```
- เหมือน graduate แต่ status = STATUS_DROPPED, students.status = 'inactive'
- ไม่ set graduation_date

#### 2.8 New `repeatStudent`

```php
public function repeatStudent(
    Student $student,
    Classroom $newClassroom,        // ห้องเป้าหมายของปีใหม่ (ระดับเดียวกัน)
    ?int $studentNumber = null,
    string $reason = 'ซ้ำชั้น',
    ?string $batchId = null,
    ?int $userId = null
): ClassroomStudent
```
- assert `$newClassroom->grade_level === $currentActive->classroom->grade_level` (ระดับเดียวกัน)
- assert `$newClassroom->id !== $currentActive->classroom_id` (กัน UNIQUE clash; ห้องเดิมห้ามใช้)
- close active เดิมด้วย STATUS_REPEATING, leave_reason=$reason
- `enrollStudent($student, $newClassroom, $studentNumber, $batchId, $userId)`
- fire `StudentRepeated(...)`

#### 2.9 New `promoteStudent`

```php
public function promoteStudent(
    Student $student,
    Classroom $fromClassroom,
    Classroom $toClassroom,
    string $reason = 'เลื่อนชั้น',
    ?int $studentNumber = null,
    ?string $batchId = null,
    ?int $userId = null
): ClassroomStudent
```
- assert `$from.academic_year_id !== $to.academic_year_id` (ข้ามปีเท่านั้น)
- assert `$to.academic_year->start_date > $from.academic_year->start_date` (ปีใหม่ต้องมาทีหลัง)
- close active เดิมใน fromClassroom = STATUS_PROMOTED
- `enrollStudent($student, $toClassroom, $studentNumber, $batchId, $userId)` — สร้าง row ใหม่ในห้องใหม่ปีใหม่
- `manageAcademicInfoSnapshot` สร้าง SAI ของปีใหม่
- fire `StudentPromoted(...)`

#### 2.10 Deprecate `promoteClassroom` (ของเดิม)

- เก็บไว้เป็น thin wrapper: เรียก `promoteStudent` ในลูป ใส่ deprecation note
- ในระยะยาวให้ `AcademicYearRolloverService` เป็นคนเรียก

#### 2.11 Deprecate `unenrollStudent` (ของเดิม)

- ปัจจุบันใช้ status `'transferred'` เป็น default และเขียน leave_reason ตามใจ
- ให้คงไว้แต่ deprecation comment → caller (ClassroomController:428) ควรเรียก `dropStudent` หรือ `transferStudent` ที่ตรง semantic
- ห้ามลบใน Phase 2 (กัน controller พัง)

### 3. ออกแบบ `AcademicYearRolloverService` (ใหม่)

#### 3.1 Value object `RolloverPlan`

```php
final class RolloverPlan implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $academyId,
        public readonly int $fromYearId,
        public readonly int $toYearId,
        /** @var array<int, array{
         *   from_classroom_id: int,
         *   to_classroom_id?: int,    // กรณี promote/repeat
         *   action: 'promote'|'graduate'|'drop'|'repeat'|'new_intake'|'skip',
         *   student_id: int,
         *   reason?: string,
         * }> */
        public readonly array $entries,
        public readonly array $summary,   // counts per action
        public readonly array $warnings,  // human-readable strings
    ) {}
}
```

#### 3.2 `RolloverBatch` Eloquent model

```php
class RolloverBatch extends Model {
    protected $table = 'rollover_batches';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'plan_summary' => 'array',
        'totals' => 'array',
        'committed_at' => 'datetime',
        'undo_closed_at' => 'datetime',
        'undone_at' => 'datetime',
    ];
    public function isUndoable(): bool {
        return $this->status === 'committed'
            && $this->undo_closed_at === null
            && $this->committed_at?->addDay()->isFuture();
    }
}
```
- relations: academy, fromYear, toYear, committedBy, undoneBy, classroomStudents (hasMany by rollover_batch_id)

#### 3.3 `previewRollover(Academy $academy, AcademicYear $from, AcademicYear $to): array`

อ่านอย่างเดียว — สร้าง suggested mapping:
- สำหรับนักเรียน active ในห้องของ $from:
  - หา target classroom ใน $to ที่ `grade_level = nextGrade(currentGrade)` และ `section` เดียวกัน
  - ถ้า nextGrade ไม่มี (ม.6 → ?) → action = `graduate`
  - ถ้ามี target classroom → action = `promote`, to_classroom_id = ...
  - ถ้า nextGrade มีระดับแต่ไม่มี section ตรง → action = `promote` ใส่ section แรก + warning
- สำหรับ pending intake (`students.class_level != ''` AND no active enrollment AND `academy_id` = $academy):
  - หา target classroom ใน $to ที่ `grade_level = students.class_level (normalized)` + `section = students.class_section`
  - action = `new_intake`
- คืน: `{ suggested_mapping, missing_targets[], totals: {promote, graduate, repeat, drop, new_intake}, warnings[] }`

`nextGrade()` helper:
```php
private function nextGrade(string $grade): ?string {
    // ม.1→ม.2, ม.3→ม.4, ม.6→null (graduate), ป.1→ป.2, ป.6→ม.1, อ.3→ป.1, etc.
    // ขั้นแรกรองรับ ม. และ ป. ก่อน อ. อนุบาลค่อยขยาย
}
```

#### 3.4 `planRollover(Academy, AcademicYear $from, $to, array $userMapping): RolloverPlan`

- รับ userMapping จาก wizard (อาจปรับจาก preview)
- ตรวจ:
  - ทุก `to_classroom_id` อยู่ใน $to.year และ academy_id ตรง
  - student_id ทุกตัวอยู่ใน academy
  - student ไม่ซ้ำใน entries (1 คน 1 action)
- สรุป summary/warnings
- คืน RolloverPlan (ไม่เขียน DB)

#### 3.5 `commitRollover(RolloverPlan, User $by): RolloverBatch`

```
DB::transaction(function () use ($plan, $by) {
    $batchId = (string) Str::uuid();
    $batch = RolloverBatch::create([
        'id' => $batchId,
        'academy_id' => $plan->academyId,
        'from_academic_year_id' => $plan->fromYearId,
        'to_academic_year_id' => $plan->toYearId,
        'status' => 'committed',
        'committed_at' => now(),
        'committed_by_user_id' => $by->id,
        'plan_summary' => $plan->summary,
        'totals' => [],
    ]);

    $totals = ['promoted'=>0,'graduated'=>0,'dropped'=>0,'repeating'=>0,'new_intake'=>0,'skipped'=>0];
    $beforeSnapshots = [];   // เก็บไว้ใช้ตอน undo

    foreach ($plan->entries as $e) {
        $student = Student::findOrFail($e['student_id']);
        $beforeSnapshots[$e['student_id']] = $student->only(['status','class_level','class_section']);

        match ($e['action']) {
            'promote'    => $this->enroll->promoteStudent($student, $from, $to, $e['reason'] ?? 'เลื่อนชั้น', null, $batchId, $by->id),
            'graduate'   => $this->enroll->graduateStudent($student, $from, $e['reason'] ?? 'จบการศึกษา', today(), $batchId, $by->id),
            'drop'       => $this->enroll->dropStudent($student, $from, $e['reason'] ?? 'พ้นสภาพ', today(), $batchId, $by->id),
            'repeat'     => $this->enroll->repeatStudent($student, $newClassroom, null, 'ซ้ำชั้น', $batchId, $by->id),
            'new_intake' => $this->enroll->enrollStudent($student, $newClassroom, null, $batchId, $by->id),
            'skip'       => null,
        };
        $totals[$e['action']]++;
    }

    $batch->update([
        'totals' => $totals,
        'plan_summary' => array_merge($plan->summary, ['before' => $beforeSnapshots]),
    ]);
    event(new RolloverCommitted($batch));
    return $batch;
});
```

#### 3.6 `undoRollover(string $batchId, User $by): RolloverBatch`

- find batch, check `isUndoable()` → ถ้าไม่ → throw `RolloverNotUndoable`
- `DB::transaction(function () use ($batch, $by) { ... })`
- สำหรับ row ทุกตัวที่ `rollover_batch_id = $batchId`:
  - ถ้า status เป็น active (newly created โดย commit) → delete
  - ถ้า status เป็น closed (promoted/graduated/etc.) → กลับเป็น active, clear left_at/leave_reason/rollover_batch_id
- restore `students.status/class_level/class_section` จาก `plan_summary.before`
- ลบ SAI rows ที่สร้างใน commit (มี classroom_id ตรงกับ batch's new active rows)
- set: `status='undone', undone_at=now(), undone_by_user_id=$by->id`
- fire `RolloverUndone($batch)`

#### 3.7 `closeUndoWindow(string $batchId, User $by): void`

- update `undo_closed_at = now()`
- ทำให้ `isUndoable() === false` ทันที

### 4. Event classes (ไม่มี listener ใน Phase 2)

ใน `app/Events/Enrollment/`:
- `StudentEnrolled(Student $student, ClassroomStudent $enrollment, ?string $batchId)`
- `StudentTransferred(Student, ClassroomStudent $closedFrom, ClassroomStudent $opened, ?string $batchId)`
- `StudentPromoted(Student, ClassroomStudent $closedFrom, ClassroomStudent $opened, ?string $batchId)`
- `StudentGraduated(Student, ?ClassroomStudent $closed, ?string $batchId)`
- `StudentDropped(Student, ?ClassroomStudent $closed, string $reason, ?string $batchId)`
- `StudentRepeated(Student, ClassroomStudent $closed, ClassroomStudent $opened, ?string $batchId)`
- `RolloverCommitted(RolloverBatch $batch)`
- `RolloverUndone(RolloverBatch $batch)`

Plain PHP classes, ไม่มี broadcast/queue spec — เก็บไว้ Phase 7

### 5. Tests

#### 5.1 `StudentEnrollmentServiceTest` (เพิ่ม)

| # | Test |
|---|---|
| T1 | `enrollStudent` fresh → row created + students.class_level normalized + SAI created with is_current=true |
| T2 | `enrollStudent` ซ้ำห้องเดิม → row updated (overwrite), ไม่ duplicate |
| T3 | `transferStudent` ในปีเดียวกัน → old row=transferred + new row=active + SAI flipped |
| T4 | `transferStudent` ข้ามปี → throws InvalidArgumentException |
| T5 | `graduateStudent` → students.status=graduated + class_level cleared + SAI.graduation_date set |
| T6 | `dropStudent` → students.status=inactive + active row=dropped + reason saved |
| T7 | `repeatStudent` ห้องใหม่ระดับเดียวกัน → close + new row + SAI สร้างใหม่ |
| T8 | `repeatStudent` ห้องเดิม → throws (UNIQUE protection) |
| T9 | `promoteStudent` → ห้องเก่า=promoted, ห้องใหม่=active, SAI year ใหม่ is_current |
| T10 | `normalizeGradeLevel("ม.1")` === "1" |
| T11 | `closeActiveEnrollment` ไม่มี active → return null (no throw) |
| T12 | ทุก method fire event ถูกตัว (ใช้ `Event::fake()`) |

#### 5.2 `AcademicYearRolloverServiceTest` (ใหม่)

| # | Test |
|---|---|
| R1 | `previewRollover` ม.1/1 → suggest promote ไป ม.2/1 |
| R2 | `previewRollover` ม.6/1 → suggest graduate |
| R3 | `previewRollover` กับ pending intake → ปรากฏใน new_intake bucket |
| R4 | `previewRollover` target year ไม่มีห้อง ม.2 → warnings ครบ |
| R5 | `planRollover` student ซ้ำ 2 entries → throws ValidationException |
| R6 | `commitRollover` 3 students (promote+graduate+drop) → totals ถูก + batch saved |
| R7 | `commitRollover` ซ้ำ plan เดิม → idempotent (UNIQUE block) |
| R8 | `undoRollover` ภายใน 24 ชม. → state กลับ + batch.status='undone' |
| R9 | `undoRollover` เกิน 24 ชม. → throws RolloverNotUndoable |
| R10 | `undoRollover` หลัง closeUndoWindow → throws |
| R11 | `closeUndoWindow` → batch.undo_closed_at set |

### 6. ลำดับ Commits ใน Phase 2 (9 commits)

| # | Subject | ไฟล์หลัก | LOC โดยประมาณ |
|---|---|---|---|
| 2.A | refactor(enrollment): extract closeActiveEnrollment + normalizeGradeLevel + SAI snapshot helper | StudentEnrollmentService.php + tests | ~120 |
| 2.B | feat(enrollment): add graduateStudent + dropStudent | StudentEnrollmentService.php + tests | ~80 |
| 2.C | feat(enrollment): add repeatStudent | StudentEnrollmentService.php + tests | ~50 |
| 2.D | feat(enrollment): add promoteStudent + tighten transferStudent assertion | StudentEnrollmentService.php + tests | ~70 |
| 2.E | feat(events): add enrollment lifecycle events | app/Events/Enrollment/*.php | ~120 |
| 2.F | feat(rollover): add RolloverBatch model + RolloverPlan value object | app/Models/RolloverBatch.php, app/Services/Rollover/RolloverPlan.php | ~140 |
| 2.G | feat(rollover): add previewRollover + planRollover + nextGrade helper | AcademicYearRolloverService.php + tests | ~200 |
| 2.H | feat(rollover): add commitRollover | AcademicYearRolloverService.php + tests | ~150 |
| 2.I | feat(rollover): add undoRollover + closeUndoWindow + RolloverNotUndoable exception | AcademicYearRolloverService.php + tests | ~130 |

**รวม ~1060 LOC, ~3 ชั่วโมง implementation** (ไม่นับ debugging)

### 7. Verification per commit

ทุก commit:
1. `./vendor/bin/pint`
2. `./vendor/bin/phpunit tests/Feature/StudentEnrollmentServiceTest.php tests/Feature/AcademicYearRolloverServiceTest.php tests/Feature/ClassroomEnrollmentSchemaTest.php`
3. Live DB smoke (เฉพาะ commit ที่กระทบ live behavior — 2.A/2.B/2.D/2.H/2.I):
   - เลือก 1 นักเรียนทดสอบ (เช่น id ใน ม.6) → graduate → ตรวจ DB → undo (ถ้า batch)

### 8. Edge cases & gotchas ที่ต้องจัดการในโค้ด

| # | Edge case | จุดที่ต้องระวัง |
|---|---|---|
| E1 | `enrolled_at` เป็น date, service ส่ง `now()` (datetime) → MySQL truncate | ใช้ `today()` หรือ `now()->toDateString()` |
| E2 | Same-classroom re-enroll หลัง transfer ไป-กลับ | UNIQUE block — service ต้อง detect และ updateOrCreate row เดิม (status=active, left_at=null) |
| E3 | ครู commit rollover พร้อมกัน 2 คน | `SELECT ... FOR UPDATE` ที่ academic_year row ของ to_year, หรือ academy-level lock |
| E4 | `students.status` enum 4 ค่า — graduate/drop ใช้ได้ ('graduated', 'inactive') | ห้ามใส่ค่าใหม่ (ไม่งั้น throw enum violation) |
| E5 | `manageAcademicInfoSnapshot` flip current + create ใหม่ใน trans เดียว | functional unique trip ระหว่าง flip → ต้อง update ก่อน insert |
| E6 | `nextGrade(ม.6)` = null → action=graduate auto | preview ต้อง handle null ไม่ throw |
| E7 | pending intake บางคนมี class_section=NULL | preview มี warning + fallback ไปห้องแรกของ grade |
| E8 | undo หลัง commit ใหม่ที่ touched ตัวเดียวกัน | check ถ้า student มี active enrollment ที่ batch_id ≠ this batch → throw "cannot undo, student has newer changes" |
| E9 | RolloverPlan ใหญ่ 2000+ entries → memory | iterate ด้วย chunked queue หรือ generator |
| E10 | committed_at + 24h ผ่านไปครึ่งวัน admin call commit → batch ใหม่จะมี undo window ใหม่ ไม่กระทบ batch เก่า | ตรวจ `isUndoable()` per batch ไม่ใช่ global |

### 9. Out of scope Phase 2 (เลื่อนไป phase อื่น)

- ❌ Controller endpoints (Phase 3)
- ❌ FormRequest validation (Phase 3)
- ❌ Policy/authorization (Phase 3)
- ❌ UI wizard (Phase 5)
- ❌ Notification listeners (Phase 7)
- ❌ Audit log integration (Phase 8)
- ❌ Backfill 2405 NULL academic_year rows (Phase 9a)
- ❌ Repair drift report (Phase 9a)
- ❌ Multi-academy lock (defer until needed)

### 10. Risks & Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| E2 (same-classroom re-enroll) ทำให้ test T2 ดู "buggy" | สูง | กลาง | document explicit: "Phase 2 ไม่แก้ history loss ในห้องเดิม; Phase ถัดไปอาจ drop UNIQUE → partial unique" |
| undo ที่ผ่านไป 23:59 → race ระหว่าง check และ apply | ต่ำ | กลาง | wrap undo ใน `SELECT FOR UPDATE` บน batch row |
| 1929 rows ใน live DB กระทบโดยไม่ตั้งใจ | ต่ำ | สูง | ทุก smoke test บน live ทำกับ "test student" 1 คน, undo ทุกครั้ง |
| Event ที่ยังไม่มี listener ทำให้ test ตก | กลาง | ต่ำ | ใช้ `Event::fake()` ใน test |
| `manageAcademicInfoSnapshot` flip 2400+ rows ของ student คนเดียวที่มีประวัติยาว | ต่ำ | ต่ำ | scope flip ด้วย `where('student_id', X)` เท่านั้น (ไม่ flip ของคนอื่น) |

### 11. Definition of Done — Phase 2

- [ ] 9 commits land บน branch `feature/academic-year-rollover`
- [ ] `phpunit tests/Feature/*Enrollment*` ผ่าน 100%
- [ ] `phpunit tests/Feature/*Rollover*` ผ่าน 100%
- [ ] pint clean
- [ ] live smoke: graduate 1 test student → undo → state กลับ
- [ ] commit message ทุกตัวระบุ Phase 2.{X}
- [ ] ไม่กระทบ ClassroomController paths (smoke check 3 endpoints เดิม)
- [ ] Worklog อัพเดท

### 12. Decisions Confirmed (2026-06-21)

1. **E4 enum `students.status`** — ใช้ `'graduated'` / `'inactive'` ของเดิมตามแผน (ไม่ขยาย enum) — **[ยืนยันแล้ว - OK]**
2. **Same-classroom re-enroll** — ยอมรับ history loss สำหรับ Phase 2 ไปก่อน; ปลด UNIQUE -> partial unique ในเฟสถัดไป — **[ยืนยันแล้ว - OK]**
3. **Lock strategy** — ใช้ `SELECT FOR UPDATE` ที่ระดับ batch row ในการเริ่มต้น; concurrency แบบ multi-academy เลื่อนไปเฟสอื่น — **[ยืนยันแล้ว - OK]**

แผนงาน Phase 2 มีการยืนยันครบถ้วนแล้วและเริ่มรันโค้ดตามลำดับ 2.A ถึง 2.I ต่อไปนี้


---

## 2026-06-21 Phase 3 — Controller & API Surface (Detailed Plan)

### 0. State at start of Phase 3

จาก Phase 2 ที่เสร็จแล้ว:
- `StudentEnrollmentService` มี method: `enrollStudent`, `transferStudent`, `graduateStudent`, `dropStudent`, `repeatStudent`, `promoteStudent`, `unenrollStudent` (deprecated), `promoteClassroom` (deprecated)
- `AcademicYearRolloverService` มี: `previewRollover`, `planRollover`, `commitRollover`, `undoRollover`, `closeUndoWindow`
- `RolloverBatch` model + `RolloverPlan` value object พร้อม
- 8 Event classes พร้อม (ยังไม่มี listener)
- Concurrency lock: `lockForUpdate()` ที่ `academic_years` + batch row
- Decision §10 lock ครบ: undo 24h, varchar status, confirm พิมพ์ชื่อปี, MySQL 8 functional index, students.status audit ใน Preflight

Caller ที่มีอยู่:
- `ClassroomController::transferStudent` (line 481) → ห่อ `transferStudent` service
- `ClassroomController::promoteClassroom` (line 537) → ห่อ deprecated `promoteClassroom` service
- ไม่มี endpoint สำหรับ graduate/drop/repeat/promote per-student
- ไม่มี endpoint rollover (preview/plan/commit/undo) เลย

### 1. หลักการ Phase 3

1. **Routes ใหม่ทั้งหมด academy-scoped** — `/api/academies/{academy}/...` กัน cross-school leak ระดับ route
2. **Policy แยก class** — ไม่ฝังใน controller; reusable
3. **FormRequest ทุก write endpoint** — validation + authorization ใน 1 ที่
4. **API Resource** สำหรับ response shape (RolloverBatch, RolloverPlan, ClassroomStudent)
5. **Plan caching** — `planRollover` คืน plan_id; client เอามาส่งใน `commit` (ไม่ resend mapping ใหญ่ๆ ซ้ำ)
6. **Idempotency key** สำหรับ commit — กัน double-click ฝั่ง client
7. **ไม่ refactor legacy** — `ClassroomController::transferStudent/promoteClassroom` คงไว้ก่อน, mark deprecated; Phase 5 frontend ค่อยย้าย
8. **Test ทุก endpoint** — happy path + 403 + 422 + cross-academy

### 2. Route map (ใหม่)

ใน `routes/learn/academy.php` ส่วนใหม่ใต้ comment `// === Phase 3: enrollment lifecycle + rollover ===`:

```php
Route::middleware(['auth:api'])->prefix('academies/{academy}')->group(function () {
    // Per-student lifecycle (academy-scoped)
    Route::post('students/{student}/graduate', [StudentLifecycleController::class, 'graduate'])
        ->name('api.academy.students.graduate');
    Route::post('students/{student}/drop', [StudentLifecycleController::class, 'drop'])
        ->name('api.academy.students.drop');
    Route::post('students/{student}/repeat', [StudentLifecycleController::class, 'repeat'])
        ->name('api.academy.students.repeat');
    Route::post('students/{student}/promote', [StudentLifecycleController::class, 'promote'])
        ->name('api.academy.students.promote');
    Route::post('students/{student}/transfer', [StudentLifecycleController::class, 'transfer'])
        ->name('api.academy.students.transfer'); // alias สำหรับ same-year transfer (เลิกใช้ legacy /classrooms/transfer-student)
    Route::get('students/{student}/enrollment-history',
        [StudentLifecycleController::class, 'history'])
        ->name('api.academy.students.enrollmentHistory');

    // Rollover wizard endpoints
    Route::prefix('rollover')->name('api.academy.rollover.')->group(function () {
        Route::post('preview', [RolloverController::class, 'preview'])->name('preview');
        Route::post('plan', [RolloverController::class, 'plan'])->name('plan');
        Route::post('commit', [RolloverController::class, 'commit'])->name('commit');
        Route::post('{batch}/undo', [RolloverController::class, 'undo'])->name('undo');
        Route::post('{batch}/close-undo', [RolloverController::class, 'closeUndo'])->name('closeUndo');
        Route::get('batches', [RolloverController::class, 'index'])->name('index');
        Route::get('batches/{batch}', [RolloverController::class, 'show'])->name('show');
    });
});
```

Route-model binding:
- `{academy}` → Academy by id (หรือ name ถ้าใช้ scope binding เดิม)
- `{student}` → Student by id, scoped by `{academy}` — เพิ่ม `Route::scopeBindings()` หรือ implicit binding รุ่น Laravel 12
- `{batch}` → RolloverBatch by uuid, scoped by `{academy}` (ตรวจ academy_id ใน controller method `getRouteKeyName`)

### 3. Controllers

#### 3.1 `Api/Learn/Academy/StudentLifecycleController`

```php
public function graduate(GraduateStudentRequest $req, Academy $academy, Student $student): JsonResponse {
    $closed = $this->enroll->graduateStudent(
        $student,
        null, // service หา active เอง
        $req->input('reason', 'จบการศึกษา'),
        $req->date('effective_at') ?? today(),
        null, // batchId
        $req->user()->id,
    );
    return response()->json([
        'success' => true,
        'closed_enrollment' => $closed ? new ClassroomStudentResource($closed) : null,
        'student' => new StudentSummaryResource($student->fresh()),
    ]);
}
```
similar สำหรับ drop, repeat, promote, transfer

`history`:
```php
public function history(Academy $academy, Student $student): JsonResponse {
    $rows = $this->enroll->getStudentHistory($student);
    return ClassroomStudentResource::collection($rows)->response();
}
```

#### 3.2 `Api/Learn/Academy/RolloverController`

```php
public function preview(PreviewRolloverRequest $req, Academy $academy): JsonResponse {
    $from = AcademicYear::where('academy_id', $academy->id)->findOrFail($req->integer('from_year_id'));
    $to = AcademicYear::where('academy_id', $academy->id)->findOrFail($req->integer('to_year_id'));
    return response()->json($this->rollover->previewRollover($academy, $from, $to));
}

public function plan(PlanRolloverRequest $req, Academy $academy): JsonResponse {
    $from = AcademicYear::findOrFail($req->integer('from_year_id'));
    $to = AcademicYear::findOrFail($req->integer('to_year_id'));
    $plan = $this->rollover->planRollover($academy, $from, $to, $req->input('mapping'));

    // Cache plan for commit step (15 min TTL, scoped per user)
    $planId = (string) Str::uuid();
    Cache::put("rollover_plan:{$planId}:user:{$req->user()->id}", $plan->toArray(), 900);

    return response()->json([
        'plan_id' => $planId,
        'summary' => $plan->summary,
        'warnings' => $plan->warnings,
    ]);
}

public function commit(CommitRolloverRequest $req, Academy $academy): JsonResponse {
    $cached = Cache::get("rollover_plan:{$req->input('plan_id')}:user:{$req->user()->id}");
    abort_if(! $cached, 410, 'Plan expired or not found. Please re-run plan step.');

    // Confirm field check (Decision §10.3 — พิมพ์ชื่อปีการศึกษา)
    $toYear = AcademicYear::findOrFail($cached['toYearId']);
    abort_unless(
        $req->input('confirm_text') === $toYear->name,
        422,
        'Confirmation text does not match destination academic year name.'
    );

    $plan = RolloverPlan::fromArray($cached);
    $batch = $this->rollover->commitRollover($plan, $req->user());
    Cache::forget("rollover_plan:{$req->input('plan_id')}:user:{$req->user()->id}");

    return response()->json([
        'batch' => new RolloverBatchResource($batch),
    ], 201);
}

public function undo(UndoRolloverRequest $req, Academy $academy, RolloverBatch $batch): JsonResponse {
    abort_unless($batch->academy_id === $academy->id, 404);
    try {
        $undone = $this->rollover->undoRollover($batch->id, $req->user());
        return response()->json(['batch' => new RolloverBatchResource($undone)]);
    } catch (RolloverNotUndoable $e) {
        return response()->json(['error' => 'cannot_undo', 'message' => $e->getMessage()], 409);
    }
}

public function closeUndo(Request $req, Academy $academy, RolloverBatch $batch): JsonResponse {
    abort_unless($batch->academy_id === $academy->id, 404);
    $this->rollover->closeUndoWindow($batch->id, $req->user());
    return response()->json(['batch' => new RolloverBatchResource($batch->fresh())]);
}

public function index(Academy $academy): JsonResponse {
    $batches = RolloverBatch::where('academy_id', $academy->id)
        ->with(['fromYear', 'toYear', 'committedBy:id,name'])
        ->latest('committed_at')->paginate(20);
    return RolloverBatchResource::collection($batches)->response();
}

public function show(Academy $academy, RolloverBatch $batch): JsonResponse {
    abort_unless($batch->academy_id === $academy->id, 404);
    return response()->json(['batch' => new RolloverBatchResource($batch->load(['fromYear', 'toYear', 'committedBy', 'undoneBy']))]);
}
```

### 4. FormRequests

ที่ `app/Http/Requests/Academy/Enrollment/`:

#### `GraduateStudentRequest`
```php
public function authorize(): bool {
    return Gate::allows('enrollmentLifecycle', [$this->route('academy'), $this->route('student')]);
}
public function rules(): array {
    return [
        'reason' => 'nullable|string|max:255',
        'effective_at' => 'nullable|date|before_or_equal:today',
    ];
}
```

#### `DropStudentRequest`
```php
public function rules(): array {
    return [
        'reason' => 'required|string|max:255', // drop ต้องมีเหตุผล
        'effective_at' => 'nullable|date|before_or_equal:today',
    ];
}
```

#### `RepeatStudentRequest`
```php
public function rules(): array {
    return [
        'new_classroom_id' => 'required|integer|exists:classrooms,id',
        'student_number' => 'nullable|integer|min:1',
        'reason' => 'nullable|string|max:255',
    ];
}
```

#### `PromoteStudentRequest` / `TransferStudentRequest`
```php
public function rules(): array {
    return [
        'from_classroom_id' => 'required|integer|exists:classrooms,id',
        'to_classroom_id' => 'required|integer|exists:classrooms,id|different:from_classroom_id',
        'reason' => 'nullable|string|max:255',
        'student_number' => 'nullable|integer|min:1',
    ];
}
```

#### `PreviewRolloverRequest`
```php
public function rules(): array {
    return [
        'from_year_id' => 'required|integer|exists:academic_years,id',
        'to_year_id' => 'required|integer|exists:academic_years,id|different:from_year_id',
    ];
}
```

#### `PlanRolloverRequest`
```php
public function rules(): array {
    return [
        'from_year_id' => 'required|integer|exists:academic_years,id',
        'to_year_id' => 'required|integer|exists:academic_years,id|different:from_year_id',
        'mapping' => 'required|array|min:1',
        'mapping.*.student_id' => 'required|integer|exists:students,id',
        'mapping.*.action' => 'required|in:promote,graduate,drop,repeat,new_intake,skip',
        'mapping.*.from_classroom_id' => 'nullable|integer|exists:classrooms,id',
        'mapping.*.to_classroom_id' => 'nullable|integer|exists:classrooms,id',
        'mapping.*.reason' => 'nullable|string|max:255',
    ];
}
```

#### `CommitRolloverRequest`
```php
public function rules(): array {
    return [
        'plan_id' => 'required|string|uuid',
        'confirm_text' => 'required|string', // ต้องตรงกับ destination year name (check ใน controller)
    ];
}
```

#### `UndoRolloverRequest`
```php
public function rules(): array {
    return [
        'reason' => 'nullable|string|max:500',
    ];
}
```

### 5. Policy

`app/Policies/EnrollmentPolicy.php`:

```php
class EnrollmentPolicy {
    /**
     * Per-student lifecycle (graduate/drop/repeat/promote/transfer)
     * ใครก็ได้ที่เป็น academy admin หรือ teacher ที่ดูแล classroom
     */
    public function lifecycle(User $user, Academy $academy, Student $student): bool {
        if ($student->academy_id !== $academy->id) return false;

        // Academy admin หรือ owner: ทำได้ทุกคน
        if ($this->isAcademyAdmin($user, $academy)) return true;

        // Homeroom teacher ของห้องที่นักเรียน active อยู่
        $teaching = ClassroomStudent::where('student_id', $student->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->whereHas('classroom', fn ($q) => $q->where('homeroom_teacher_id', $user->id))
            ->exists();
        return $teaching;
    }

    /**
     * Rollover commit/undo: เฉพาะ principal หรือ academy admin
     * Teacher preview/plan ได้ แต่ commit ไม่ได้
     */
    public function previewRollover(User $user, Academy $academy): bool {
        return $this->isAcademyStaff($user, $academy);
    }
    public function planRollover(User $user, Academy $academy): bool {
        return $this->isAcademyStaff($user, $academy);
    }
    public function commitRollover(User $user, Academy $academy): bool {
        return $this->isAcademyAdmin($user, $academy);
    }
    public function undoRollover(User $user, Academy $academy, RolloverBatch $batch): bool {
        if ($batch->academy_id !== $academy->id) return false;
        return $this->isAcademyAdmin($user, $academy);
    }
    public function viewBatches(User $user, Academy $academy): bool {
        return $this->isAcademyStaff($user, $academy);
    }

    private function isAcademyAdmin(User $user, Academy $academy): bool {
        if ($academy->user_id === $user->id) return true;
        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('role', ['admin', 'director'])
            ->exists();
    }
    private function isAcademyStaff(User $user, Academy $academy): bool {
        if ($this->isAcademyAdmin($user, $academy)) return true;
        return AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->whereIn('role', ['admin', 'teacher', 'director'])
            ->exists();
    }
}
```


Register ใน `AuthServiceProvider`:
```php
Gate::define('enrollment.lifecycle', [EnrollmentPolicy::class, 'lifecycle']);
Gate::define('enrollment.preview', [EnrollmentPolicy::class, 'previewRollover']);
Gate::define('enrollment.plan', [EnrollmentPolicy::class, 'planRollover']);
Gate::define('enrollment.commit', [EnrollmentPolicy::class, 'commitRollover']);
Gate::define('enrollment.undo', [EnrollmentPolicy::class, 'undoRollover']);
Gate::define('enrollment.viewBatches', [EnrollmentPolicy::class, 'viewBatches']);
```

### 6. API Resources

#### `ClassroomStudentResource`
```php
public function toArray($req): array {
    return [
        'id' => $this->id,
        'student_id' => $this->student_id,
        'classroom_id' => $this->classroom_id,
        'academy_id' => $this->academy_id,
        'academic_year_id' => $this->academic_year_id,
        'student_number' => $this->student_number,
        'status' => $this->status,
        'status_text' => $this->status_text,
        'enrolled_at' => $this->enrolled_at?->toDateString(),
        'left_at' => $this->left_at?->toDateString(),
        'leave_reason' => $this->leave_reason,
        'rollover_batch_id' => $this->rollover_batch_id,
        'created_by' => $this->whenLoaded('createdBy', fn () => [
            'id' => $this->createdBy->id,
            'name' => $this->createdBy->name,
        ]),
        'classroom' => $this->whenLoaded('classroom', fn () => [
            'id' => $this->classroom->id,
            'display_name' => $this->classroom->name,
            'grade_level' => $this->classroom->grade_level,
            'section' => $this->classroom->section,
        ]),
    ];
}
```

#### `RolloverBatchResource`
```php
public function toArray($req): array {
    return [
        'id' => $this->id,
        'academy_id' => $this->academy_id,
        'from_year' => $this->whenLoaded('fromYear', fn () => ['id' => $this->fromYear->id, 'name' => $this->fromYear->name]),
        'to_year' => $this->whenLoaded('toYear', fn () => ['id' => $this->toYear->id, 'name' => $this->toYear->name]),
        'status' => $this->status,
        'committed_at' => $this->committed_at?->toIso8601String(),
        'committed_by' => $this->whenLoaded('committedBy', fn () => ['id' => $this->committedBy->id, 'name' => $this->committedBy->name]),
        'undo_closed_at' => $this->undo_closed_at?->toIso8601String(),
        'undone_at' => $this->undone_at?->toIso8601String(),
        'is_undoable' => $this->isUndoable(),
        'undo_expires_at' => $this->committed_at?->addDay()->toIso8601String(),
        'totals' => $this->totals,
        'plan_summary' => $this->when($req->user()->can('enrollment.commit', $this->academy), $this->plan_summary),
    ];
}
```

#### `StudentSummaryResource`
- เฉพาะ field ที่ทุก role ดูได้: id, student_id, name, status, class_level, class_section, academy_id

### 7. Tests

`tests/Feature/Api/Academy/RolloverControllerTest.php`:

| # | Test |
|---|---|
| C1 | preview as academy admin → 200 + suggested mapping shape |
| C2 | preview as student → 403 |
| C3 | preview cross-academy → 404 |
| C4 | plan as academy admin → 200 + plan_id cached |
| C5 | plan with invalid mapping (student not in academy) → 422 |
| C6 | commit without correct confirm_text → 422 |
| C7 | commit with expired plan_id → 410 |
| C8 | commit happy path → 201 + batch created + 3 students transitioned |
| C9 | undo within 24h → 200 + state restored |
| C10 | undo after closeUndo → 409 |
| C11 | undo cross-academy → 404 |
| C12 | index batches → pagination + only own academy |
| C13 | show batch ของ academy อื่น → 404 |

`tests/Feature/Api/Academy/StudentLifecycleControllerTest.php`:

| # | Test |
|---|---|
| L1 | graduate as homeroom teacher → 200 + students.status=graduated |
| L2 | graduate as random user → 403 |
| L3 | drop without reason → 422 |
| L4 | drop with reason → 200 + students.status=inactive |
| L5 | transfer same-year → 200 + 2 rows updated |
| L6 | transfer cross-year → 422 (service throws InvalidArgumentException → controller returns 422) |
| L7 | promote different year → 200 |
| L8 | repeat same classroom → 422 |
| L9 | history → list of all enrollment rows desc by created_at |
| L10 | cross-academy student → 404 |

### 8. ลำดับ Commits Phase 3 (5 commits)

| # | Subject | ไฟล์หลัก | LOC |
|---|---|---|---|
| 3.A | feat(api): policies + gate registration for enrollment lifecycle | EnrollmentPolicy.php, AuthServiceProvider.php, tests | ~150 |
| 3.B | feat(api): FormRequests + API Resources for enrollment | Requests/Academy/Enrollment/*, Resources/Enrollment/* | ~250 |
| 3.C | feat(api): StudentLifecycleController + routes + tests | StudentLifecycleController.php, routes/learn/academy.php, tests/...LifecycleControllerTest.php | ~250 |
| 3.D | feat(api): RolloverController preview+plan+index endpoints + tests | RolloverController.php (partial), tests/...RolloverControllerTest.php (C1-C5, C12) | ~200 |
| 3.E | feat(api): RolloverController commit+undo+closeUndo + RolloverPlan::fromArray + tests | RolloverController.php (complete), RolloverPlan.php (add fromArray) | ~250 |

**รวม ~1100 LOC ~2.5 ชม.**

แยก 3.D และ 3.E เพราะ commit/undo มี complexity สูงกว่า (plan caching, confirm_text gate, idempotency)

### 9. Verification per commit

ทุก commit:
1. `./vendor/bin/pint`
2. `./vendor/bin/phpunit tests/Feature/Api/Academy/RolloverControllerTest.php tests/Feature/Api/Academy/StudentLifecycleControllerTest.php`
3. `./vendor/bin/phpunit tests/Feature/StudentEnrollmentServiceTest.php tests/Feature/AcademicYearRolloverServiceTest.php` (regression)
4. Manual smoke ผ่าน Postman/curl บน live DB:
   - `POST /api/academies/1/rollover/preview {from_year_id:1, to_year_id:NEW}` (ต้องสร้าง year 2569 ก่อน manual)
   - `POST /api/academies/1/students/{test_id}/graduate {reason:'ทดสอบ'}` → undo manual

### 10. Edge cases

| # | Case | จุดที่ต้องจัดการ |
|---|---|---|
| EC1 | `confirm_text` มีช่องว่างนำ/ตาม | trim ก่อน strict compare |
| EC2 | plan_id หมดอายุระหว่าง commit | 410 + ส่ง suggestion "re-run plan" |
| EC3 | student ถูกลบระหว่าง preview กับ commit | service จะ throw ModelNotFoundException → controller catch → 422 พร้อม student_id |
| EC4 | preview + commit ใช้ year ที่ปิดแล้ว (deleted_at != null ถ้ามี) | exists rule + soft-delete scope |
| EC5 | RolloverBatch route binding ผ่าน uuid → ต้อง override getRouteKeyName | model: `public function getRouteKeyName(): string { return 'id'; }` |
| EC6 | concurrent commit ของ academy เดียวกัน | service มี `lockForUpdate` ที่ academic_years; controller ไม่ต้องทำซ้ำ |
| EC7 | undo หลัง 23:59 (race window สั้นๆ) | service มี `lockForUpdate` ที่ batch + check `isUndoable()` ใน lock — atomic |
| EC8 | StudentLifecycleController::transfer ที่เรียก cross-year service → service throws InvalidArgumentException | controller try/catch → 422 พร้อม hint "ใช้ promote แทน" |

### 11. Out of scope Phase 3

- ❌ Frontend wizard (Phase 5)
- ❌ ลบ legacy `ClassroomController::transferStudent/promoteClassroom` (Phase 6)
- ❌ Notification listeners (Phase 7)
- ❌ Audit log (Phase 8)
- ❌ Report sync (Phase 6 ทำพร้อมกัน)
- ❌ Bulk preview optimization สำหรับ 10K+ students (performance Phase ต่อไป)

### 12. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Cache::put ใช้ file driver ใน WAMP → plan ลืมระหว่าง 15 นาที | กลาง | กลาง | ใช้ `cache:remember` + ปรับ TTL ถ้า user feedback; ฝั่ง frontend แสดง countdown |
| Policy method ใช้ `academy->admins()` ที่ relation อาจไม่มี | กลาง | สูง | ตรวจ Academy model มี `admins()` relation; ถ้าไม่มีต้องเพิ่มก่อน Phase 3.A |
| Route scope binding `{academy}/students/{student}` ไม่ scope auto | ต่ำ | สูง | ใช้ `->scopeBindings()` หรือ middleware ตรวจ academy_id |
| RolloverPlan::fromArray ไม่ symmetric กับ toArray | กลาง | สูง | 3.E ต้องเขียน round-trip test |
| confirm_text เปลี่ยน formatting (full-width digit ไทย vs arabic) | กลาง | กลาง | trim + ใช้ Str::ascii ก่อน compare |

### 13. Decisions ที่รอยืนยันก่อนเริ่ม Phase 3

1. **Cache driver สำหรับ plan_id** — Laravel default (file ใน WAMP); หรือใช้ DB-cache ที่มีอยู่แล้ว? recommendation: file driver + 15 min TTL พอ
2. **Policy: homeroom teacher ทำ graduate/drop ได้ไหม?** — recommendation: graduate/drop เฉพาะ academy admin; teacher ทำได้แค่ transfer ในห้อง
3. **Confirm text comparison** — strict equality vs normalized (trim+ascii)? recommendation: trim เท่านั้น (preserve Thai numerals)
4. **Endpoint base path** — `/api/academies/{academy}/students/{student}/graduate` (REST-y) vs `/api/academies/{academy}/enrollment/students/{student}/graduate` (nested)? recommendation: เลือกแบบแรก ตรงไปตรงมา

### 14. Definition of Done — Phase 3

- [ ] 5 commits land
- [ ] 23 endpoint tests ผ่าน (C1-C13 + L1-L10)
- [ ] regression Phase 2 tests ผ่าน
- [ ] pint clean
- [ ] manual smoke: preview → plan → commit → undo flow ผ่านบน WAMP
- [ ] worklog อัพเดท
- [ ] commit message ระบุ Phase 3.{A-E}

### 15. Decisions Locked (2026-06-21)

1. ✅ **Cache driver** = Laravel default (file ใน WAMP), TTL 15 นาที
   - กระทบ 3.D plan endpoint: `Cache::put("rollover_plan:{$uuid}:user:{$userId}", $plan->toArray(), 900)`
   - ฝั่ง wizard แสดง countdown timer 15 นาที

2. ✅ **Homeroom teacher graduate/drop ได้** (ปรับจาก recommendation)
   - EnrollmentPolicy::lifecycle: teacher ครอบคลุม graduate/drop/repeat/transfer (ไม่จำกัดเฉพาะ transfer)
   - rollover commit/undo ยังคงเฉพาะ academy admin
   - เหตุผล: ครูประจำชั้นรู้สถานการณ์นักเรียนจริง ไม่ต้องผ่าน admin ทุกครั้ง

3. ✅ **Confirm text comparison** = `trim()` เท่านั้น
   - `trim($req->input('confirm_text')) === $toYear->name`
   - ไม่ normalize ascii → preserve Thai numerals + Thai characters เป็นค่าจริง

4. ✅ **Endpoint base path** = `/api/academies/{academy}/students/{student}/{action}` (REST-y ตรง)
   - ไม่ nest `/enrollment/`
   - rollover ใช้ `/api/academies/{academy}/rollover/{action}` แบบกลุ่ม

→ **พร้อมเริ่ม Phase 3.A ทันที**

---

## 2026-06-21 Phase 3.B — FormRequests + API Resources (Detailed Plan)

### 0. State at start of Phase 3.B

จาก Phase 3.A เสร็จแล้ว (verified, ยังไม่ commit):
- `app/Policies/EnrollmentPolicy.php` — 6 methods + 2 helpers
- `app/Providers/AppServiceProvider.php` — 6 Gates registered
- `tests/Feature/EnrollmentPolicyTest.php` — 13 tests, 24 assertions, all green
- 41/41 regression tests (Phase 1+2+3.A) ผ่าน
- Pint clean

จาก Decision §15 lock แล้ว:
- ✅ teacher graduate/drop ได้ → FormRequest authorize ใช้ `enrollment.lifecycle` gate (ครอบคลุมทุก action)
- ✅ confirm_text trim only → `CommitRolloverRequest` ใช้ `trim()` ใน custom rule
- ✅ Endpoint path: `/api/academies/{academy}/students/{student}/{action}` → route params `academy`, `student`
- ✅ Cache file driver, TTL 900s → ไม่กระทบ FormRequest ตรงๆ (controller จัดการ)

Convention โปรเจค:
- Existing requests ใน `app/Http/Requests/{Domain}/` (ไม่ลึก) — เช่น `Admin/StoreAcademyRequest.php`
- Use `Illuminate\Foundation\Http\FormRequest`
- Rules แบบ array-of-strings (`['required', 'string', 'max:255']`)
- Resource ใน `app/Http/Resources/Learn/Academy/`

### 1. หลักการ Phase 3.B

1. **Authorize ใน FormRequest** — ใช้ `Gate::allows()` ผ่าน route binding (`$this->route('academy')`, `$this->route('student')`, `$this->route('batch')`)
2. **Validation strict + ข้อความไทย** — ใช้ `messages()` ให้ user-facing error อ่านง่าย
3. **`prepareForValidation`** — normalize input ก่อน rules (trim string, parse date)
4. **Resource hide sensitive** — `RolloverBatchResource` ซ่อน `plan_summary` (มี before-snapshot) ยกเว้น admin
5. **No business logic in Resource** — ใช้ `whenLoaded` ไม่ใช่ N+1 query
6. **Test ทุก FormRequest** — focus ที่ authorize gate + rules edge case
7. **Test Resource** — verify shape + visibility rules

### 2. โครงสร้างไฟล์

```
app/Http/Requests/
├── Academy/
│   ├── Enrollment/
│   │   ├── GraduateStudentRequest.php       (3.B.1)
│   │   ├── DropStudentRequest.php           (3.B.2)
│   │   ├── RepeatStudentRequest.php         (3.B.3)
│   │   ├── PromoteStudentRequest.php        (3.B.4)
│   │   └── TransferStudentRequest.php       (3.B.5)
│   └── Rollover/
│       ├── PreviewRolloverRequest.php       (3.B.6)
│       ├── PlanRolloverRequest.php          (3.B.7)
│       ├── CommitRolloverRequest.php        (3.B.8)
│       └── UndoRolloverRequest.php          (3.B.9)
│
app/Http/Resources/
└── Learn/Academy/
    └── Enrollment/
        ├── ClassroomStudentResource.php     (3.B.10)
        ├── RolloverBatchResource.php        (3.B.11)
        └── StudentSummaryResource.php       (3.B.12)
```

ใช้ namespace nesting `Academy/Enrollment/`, `Academy/Rollover/` ภายใต้ Requests; Resource เกาะ existing `Learn/Academy/Enrollment/` group กัน import ยาว

### 3. FormRequest specs

#### 3.1 `GraduateStudentRequest`

```php
namespace App\Http\Requests\Academy\Enrollment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GraduateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('enrollment.lifecycle', [
            $this->route('academy'),
            $this->route('student'),
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
            'effective_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'effective_at.before_or_equal' => 'วันที่จบการศึกษาต้องไม่เป็นอนาคต',
        ];
    }
}
```

#### 3.2 `DropStudentRequest`
- `reason` → **required** (ลาออกต้องมีเหตุผล)
- `effective_at` → nullable date, ไม่อนาคต

#### 3.3 `RepeatStudentRequest`
- `new_classroom_id` → required, exists, scope: ต้องอยู่ใน academy เดียวกับ route academy
- `student_number` → nullable int min 1
- `reason` → nullable

```php
public function rules(): array {
    return [
        'new_classroom_id' => [
            'required', 'integer',
            Rule::exists('classrooms', 'id')->where('academy_id', $this->route('academy')->id),
        ],
        'student_number' => ['nullable', 'integer', 'min:1'],
        'reason' => ['nullable', 'string', 'max:255'],
    ];
}
```

#### 3.4 `PromoteStudentRequest`
- `from_classroom_id` → required, exists, scope academy
- `to_classroom_id` → required, different from from, exists, scope academy
- `reason` → nullable
- `student_number` → nullable int min 1

Custom check ใน `withValidator`:
```php
$validator->after(function ($v) {
    $from = Classroom::find($this->input('from_classroom_id'));
    $to = Classroom::find($this->input('to_classroom_id'));
    if ($from && $to && $from->academic_year_id === $to->academic_year_id) {
        $v->errors()->add('to_classroom_id', 'ห้องปลายทางต้องอยู่ในปีการศึกษาต่างจากต้นทาง (ใช้ transfer สำหรับปีเดียวกัน)');
    }
});
```

#### 3.5 `TransferStudentRequest`
- เหมือน Promote แต่ check inverse: `from.academic_year_id === to.academic_year_id` (ต้องเท่ากัน)
- error: "ใช้ promote สำหรับข้ามปี"

#### 3.6 `PreviewRolloverRequest`
- authorize: `enrollment.preview` gate
- rules:
  - `from_year_id` → required int, exists `academic_years`, **scope: academy_id ตรงกับ route academy**
  - `to_year_id` → required int, exists, scope, different from from_year_id

```php
'from_year_id' => [
    'required', 'integer',
    Rule::exists('academic_years', 'id')->where('academy_id', $this->route('academy')->id),
],
```

#### 3.7 `PlanRolloverRequest`
- authorize: `enrollment.plan`
- rules: from_year_id + to_year_id (เหมือน Preview) + `mapping` array

```php
'mapping' => ['required', 'array', 'min:1'],
'mapping.*.student_id' => [
    'required', 'integer',
    Rule::exists('students', 'id')->where('academy_id', $this->route('academy')->id),
],
'mapping.*.action' => ['required', Rule::in(['promote','graduate','drop','repeat','new_intake','skip'])],
'mapping.*.from_classroom_id' => ['nullable', 'integer', /* scope */],
'mapping.*.to_classroom_id' => ['nullable', 'integer', /* scope */],
'mapping.*.reason' => ['nullable', 'string', 'max:255'],
```

custom check: action='promote' หรือ 'repeat' หรือ 'new_intake' ต้องมี `to_classroom_id`; action='graduate' หรือ 'drop' ต้องไม่มี

#### 3.8 `CommitRolloverRequest`
- authorize: `enrollment.commit`
- rules:
  - `plan_id` → required uuid
  - `confirm_text` → required string

`prepareForValidation` trim:
```php
protected function prepareForValidation(): void
{
    $this->merge(['confirm_text' => trim((string) $this->input('confirm_text'))]);
}
```

#### 3.9 `UndoRolloverRequest`
- authorize: `enrollment.undo` (ส่ง `[$academy, $batch]`)
- rules:
  - `reason` → nullable string max 500

### 4. API Resource specs

#### 4.1 `ClassroomStudentResource`

```php
namespace App\Http\Resources\Learn\Academy\Enrollment;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomStudentResource extends JsonResource
{
    public function toArray($req): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'classroom_id' => $this->classroom_id,
            'academy_id' => $this->academy_id,
            'academic_year_id' => $this->academic_year_id,
            'student_number' => $this->student_number,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'enrolled_at' => optional($this->enrolled_at)->toDateString(),
            'left_at' => optional($this->left_at)->toDateString(),
            'leave_reason' => $this->leave_reason,
            'rollover_batch_id' => $this->rollover_batch_id,
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ]),
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom->id,
                'display_name' => $this->classroom->name,
                'grade_level' => $this->classroom->grade_level,
                'section' => $this->classroom->section,
            ]),
            'student' => $this->whenLoaded('student', fn () => (new StudentSummaryResource($this->student))->toArray($req)),
        ];
    }
}
```

#### 4.2 `RolloverBatchResource`

```php
class RolloverBatchResource extends JsonResource
{
    public function toArray($req): array
    {
        $canSeeSensitive = $req->user()?->can('enrollment.commit', $this->resource->academy) ?? false;

        return [
            'id' => $this->id,
            'academy_id' => $this->academy_id,
            'from_year' => $this->whenLoaded('fromYear', fn () => [
                'id' => $this->fromYear->id,
                'name' => $this->fromYear->name,
            ]),
            'to_year' => $this->whenLoaded('toYear', fn () => [
                'id' => $this->toYear->id,
                'name' => $this->toYear->name,
            ]),
            'status' => $this->status,
            'committed_at' => optional($this->committed_at)->toIso8601String(),
            'committed_by' => $this->whenLoaded('committedBy', fn () => [
                'id' => $this->committedBy?->id,
                'name' => $this->committedBy?->name,
            ]),
            'undo_closed_at' => optional($this->undo_closed_at)->toIso8601String(),
            'undone_at' => optional($this->undone_at)->toIso8601String(),
            'undone_by' => $this->whenLoaded('undoneBy', fn () => [
                'id' => $this->undoneBy?->id,
                'name' => $this->undoneBy?->name,
            ]),
            'is_undoable' => $this->isUndoable(),
            'undo_expires_at' => optional($this->committed_at)?->addDay()->toIso8601String(),
            'totals' => $this->totals,
            // before-snapshot อยู่ใน plan_summary — เห็นเฉพาะ admin
            'plan_summary' => $canSeeSensitive ? $this->plan_summary : null,
            'notes' => $this->notes,
        ];
    }
}
```

#### 4.3 `StudentSummaryResource`

```php
class StudentSummaryResource extends JsonResource
{
    public function toArray($req): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'academy_id' => $this->academy_id,
            'first_name_th' => $this->first_name_th,
            'last_name_th' => $this->last_name_th,
            'nickname' => $this->nickname,
            'status' => $this->status,
            'class_level' => $this->class_level,
            'class_section' => $this->class_section,
            'profile_image_url' => $this->profile_image_url ?? null, // accessor
        ];
    }
}
```

ไม่มี `citizen_id`, `email`, `phone`, `address` — กัน PII leak โดย default

### 5. Tests

ที่ `tests/Feature/Academy/Enrollment/`:

#### `FormRequestValidationTest.php` (รวม)
ใช้ `RouteServiceProvider` test routes สำหรับ resolve route binding หรือ instantiate FormRequest โดยตรงด้วย `Container::call`:

| # | Test |
|---|---|
| V1 | `GraduateStudentRequest` → effective_at อนาคต → 422 |
| V2 | `GraduateStudentRequest` → unauthorized user → 403 |
| V3 | `DropStudentRequest` → ไม่มี reason → 422 |
| V4 | `RepeatStudentRequest` → new_classroom_id ของ academy อื่น → 422 |
| V5 | `PromoteStudentRequest` → from/to year เดียวกัน → 422 + ข้อความ |
| V6 | `TransferStudentRequest` → from/to year ต่างกัน → 422 + ข้อความ |
| V7 | `PreviewRolloverRequest` → from_year_id ของ academy อื่น → 422 |
| V8 | `PlanRolloverRequest` → action=promote ไม่มี to_classroom_id → 422 |
| V9 | `PlanRolloverRequest` → action=graduate มี to_classroom_id → 422 (warning เท่านั้น? หรือ block?) — decision: warning ไม่ block |
| V10 | `CommitRolloverRequest` → confirm_text มีช่องว่างนำ → ผ่าน rules (trim) → controller compare |
| V11 | `UndoRolloverRequest` → user เป็น teacher → 403 |

ใช้ helper `$this->postJson(...)` กับ stub route ใน test (เพิ่มใน setUp) เพื่อ trigger middleware/authorize จริง — แทนที่จะ instantiate

#### `ResourceShapeTest.php`

| # | Test |
|---|---|
| R1 | `ClassroomStudentResource` → key ครบ + `status_text` แปลไทย |
| R2 | `ClassroomStudentResource` → `classroom` ปรากฏเฉพาะเมื่อ loaded |
| R3 | `RolloverBatchResource` → ไม่มี `citizen_id` หรือ `before` ใน plan_summary เมื่อ user ไม่ใช่ admin |
| R4 | `RolloverBatchResource` → `is_undoable=true` ก่อน 24h, `false` หลัง |
| R5 | `RolloverBatchResource` → `undo_expires_at` = committed_at + 1 day |
| R6 | `StudentSummaryResource` → ไม่มี `citizen_id` |

### 6. ลำดับ Sub-commits ของ 3.B (2 commits)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 3.B.1 | feat(api): add 5 student enrollment lifecycle FormRequests | Graduate/Drop/Repeat/Promote/Transfer + tests V1-V6 | ~250 |
| 3.B.2 | feat(api): add 4 rollover FormRequests + 3 API Resources | Preview/Plan/Commit/Undo + ClassroomStudent/RolloverBatch/StudentSummary + tests V7-V11, R1-R6 | ~350 |

รวม ~600 LOC, ~1.5 ชม.

### 7. Verification per commit

ทุก commit:
1. `./vendor/bin/pint app/Http/Requests/Academy/ app/Http/Resources/Learn/Academy/Enrollment/ tests/Feature/Academy/Enrollment/`
2. `./vendor/bin/phpunit tests/Feature/Academy/Enrollment/`
3. Regression: `./vendor/bin/phpunit tests/Feature/EnrollmentPolicyTest.php tests/Feature/StudentEnrollmentServiceTest.php tests/Feature/AcademicYearRolloverServiceTest.php tests/Feature/ClassroomEnrollmentSchemaTest.php`
   - ต้องครบ 41 + ใหม่ทั้งหมด
4. ไม่กระทบ live DB — FormRequest/Resource pure

### 8. Edge cases & gotchas

| # | Edge case | จุดที่ต้องระวัง |
|---|---|---|
| E1 | route binding `{academy}` คืน Academy instance (จาก scopeBindings) — `$this->route('academy')` คือ object | ใช้ `->id` เสมอ ไม่ใช่ raw param |
| E2 | unauthenticated → FormRequest authorize() reject → Laravel throw AuthorizationException → 403 (ไม่ใช่ 401) | OK สำหรับ Phase 3 (controller routes อยู่ใน `auth:api`) |
| E3 | `Rule::exists` กับ scope `academy_id` ต้องใช้ `where(...)` callback ที่ inject academy id ตอน rules() ถูกเรียก | OK เพราะ rules() เรียกหลัง route resolve |
| E4 | `mapping.*.to_classroom_id` exist + scope แต่ ล่าช้าใน list ใหญ่ | บางครั้งต้อง chunk validation; Phase 3.B ไม่ optimize — เผื่อ Phase 5 ค่อยปรับ |
| E5 | `prepareForValidation` ของ Commit trim → ต้อง re-merge เข้า request | ใช้ `$this->merge([...])` |
| E6 | Test FormRequest แบบไม่ใช่ HTTP request → ใช้ `app(FormRequestClass::class)` แล้วผ่าน data → ต้อง mock route | ใช้ stub route + postJson เพื่อ test ทั้ง pipeline (cleaner) |
| E7 | `RolloverBatchResource` ใช้ `$req->user()->can(...)` แต่ ในบาง context (queued job, console) `user()` คือ null | ใช้ `$req->user()?->can(...) ?? false` |
| E8 | `StudentSummaryResource` มี field `profile_image_url` ที่อาจเป็น accessor — เช็คว่า model มี | กรณีไม่มีให้ใช้ `?? null` |
| E9 | `is_undoable` computed property — ถ้า batch undoneby filled แล้ว status='undone' → return false (อยู่ใน model isUndoable() แล้ว) | ✓ ตรวจกับ RolloverBatch model |

### 9. Out of scope ของ 3.B

- ❌ Controllers (Phase 3.C/3.D/3.E)
- ❌ Routes (Phase 3.C)
- ❌ Plan caching mechanism (Phase 3.D — controller)
- ❌ confirm_text comparison logic (Phase 3.E — controller)
- ❌ Resource pagination wrapper (Laravel built-in)

### 10. Risks & Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Route binding `{academy:name}` ส่ง slug — `->id` พัง | ต่ำ | สูง | route แก้เป็น `{academy}` (id) หรือ override binding ใน controller; verify ตอนเขียน test stub |
| `Rule::exists` กับ closure ไม่ inject route param ทัน | ต่ำ | กลาง | ใช้ `function ($attribute, $value, $fail)` แบบ custom rule แทนถ้าจำเป็น |
| FormRequest test ที่ instantiate ตรงๆ ขาด context | กลาง | ต่ำ | ใช้ stub route + `postJson()` แทนเสมอ |
| RolloverBatchResource หา `$this->resource->academy` แล้ว N+1 | กลาง | กลาง | controller eager load `academy` ก่อนส่งเข้า Resource |
| ภาษาไทยใน error messages ทำ test fragile | ต่ำ | ต่ำ | test เช็ค status code + key error (`'to_classroom_id'`) ไม่ใช่ message exact |

### 11. Definition of Done — Phase 3.B

- [ ] 2 commits land บน branch
- [ ] 9 FormRequests + 3 Resources files exist with correct namespace
- [ ] 11 validation tests (V1-V11) ผ่าน
- [ ] 6 resource tests (R1-R6) ผ่าน
- [ ] 41 regression tests ยังผ่าน
- [ ] pint clean
- [ ] ไม่มี `use App\Http\Controllers\...` ใน FormRequest/Resource (เกาะ controller จะ design ผิด)
- [ ] commit message ระบุ Phase 3.B.{1,2}

### 12. Decisions ที่รอยืนยันก่อนเริ่ม 3.B

1. **Action validation strict vs warning** (V8/V9)
   - Strict: action='promote' ไม่มี to_classroom_id → 422 reject
   - Warning: ไม่ reject; service จัดการ → return ใน plan warnings
   - **Recommendation:** Strict (reject ที่ FormRequest) — กัน garbage เข้า service

2. **Test approach** สำหรับ FormRequest
   - A: Stub route + `postJson()` → ทดสอบทั้ง middleware pipeline
   - B: Manual instantiate FormRequest → เร็วกว่า, ไม่ครอบ middleware
   - **Recommendation:** A (stub route) — เจอ regression มากกว่า

3. **Namespace แยก `Enrollment` vs `Rollover`** ใน Requests
   - แยก 2 sub-folder (ตามแผน): `Requests/Academy/Enrollment/` + `Requests/Academy/Rollover/`
   - รวม folder เดียว: `Requests/Academy/Enrollment/` (รวม Rollover ใน)
   - **Recommendation:** แยก 2 folder — semantic ชัดกว่า

4. **`profile_image_url` ใน StudentSummaryResource**
   - ถ้า Student model ไม่มี accessor นี้ → ลบ field ออก
   - หรือเพิ่ม accessor ใน Student model ใน 3.B
   - **Recommendation:** ลบออกใน 3.B; เพิ่ม field ทีหลังถ้าจำเป็น (กัน scope creep)
### 2026-06-21 Phase 3.B — Implementation Update

- ลงมือทำครบตาม decision ที่ยืนยันแล้ว
  - action validation เป็น strict reject
  - FormRequest tests ใช้ stub route + HTTP pipeline
  - Requests แยก `Academy/Enrollment/` และ `Academy/Rollover/`
  - `StudentSummaryResource` ตัด `profile_image_url` ออก
- สิ่งที่ต้องปรับจากแผนหลังอ่านโค้ดจริง
  - `RolloverBatchResource` ใช้ relation จริง `fromAcademicYear` / `toAcademicYear`
  - test stub routes ต้อง register explicit route binders (`academy`, `student`, `batch`) เพื่อให้ `authorize()` ได้ route binding object จริง
  - `ClassroomStudentResource` ใช้ `classroom.display_name` จาก accessor ของ `Classroom`
- ไฟล์ที่เพิ่ม
  - 9 FormRequests ใน `app/Http/Requests/Academy/{Enrollment,Rollover}/`
  - 3 Resources ใน `app/Http/Resources/Learn/Academy/Enrollment/`
  - 2 test files ใน `tests/Feature/Academy/Enrollment/`
- Verification ล่าสุด
  - `tests/Feature/Academy/Enrollment` ผ่าน 17 tests / 44 assertions
  - Regression `EnrollmentPolicyTest`, `StudentEnrollmentServiceTest`, `AcademicYearRolloverServiceTest`, `ClassroomEnrollmentSchemaTest` ผ่าน 41 tests / 125 assertions
  - Pint ผ่าน 14 files

---

## 2026-06-21 Phase 3.C — StudentLifecycleController (Detailed Plan)

### 0. State at start of 3.C

ของพร้อมใช้:
- ✅ `StudentEnrollmentService` (Phase 2) มี method `graduateStudent/dropStudent/repeatStudent/promoteStudent/transferStudent/getStudentHistory`
- ✅ `EnrollmentPolicy` + Gates (Phase 3.A)
- ✅ 5 lifecycle FormRequests + 3 Resources (Phase 3.B)
- ✅ 58 tests พื้นฐานผ่านครบ

Legacy ที่ต้องเลี่ยงชน:
- `ClassroomController::transferStudent` (line 481), `promoteClassroom` (line 537), `getStudentEnrollmentHistory` (line 380 route) — ใช้ `int $academyId, int $studentId` แบบ manual resolve
- Route legacy: `POST /classrooms/transfer-student`, `POST /classrooms/promote`, `GET /students/{student}/enrollment-history`
- **กลยุทธ์:** Phase 3.C ไม่แตะ legacy paths; เพิ่ม new endpoints ขนานกัน; Phase 6 ค่อย migrate frontend แล้วลบ

### 1. หลักการ Phase 3.C

1. **Route-model binding** — ใช้ `Academy $academy, Student $student` ไม่ใช่ id raw (เลิก pattern เก่า)
2. **Implicit scope binding** — `{academy}/students/{student}` Laravel จะ enforce `Student.academy_id === Academy.id` ถ้าใช้ `Route::scopeBindings()` หรือ define `getRouteKeyName` + relation
3. **Service injection ผ่าน constructor** — `__construct(private StudentEnrollmentService $enroll)`
4. **No business logic in controller** — แค่ delegate + map exception → response
5. **Exception → HTTP code mapping ที่ controller**:
   - `InvalidArgumentException` (cross-year transfer, same-classroom repeat) → 422
   - `ModelNotFoundException` → 404 (Laravel default)
   - อื่นๆ → 500
6. **Response shape สม่ำเสมอ** ทุก endpoint:
   ```json
   {
     "success": true,
     "closed_enrollment": { ... ClassroomStudentResource ... } | null,
     "new_enrollment": { ... } | null,
     "student": { ... StudentSummaryResource ... }
   }
   ```
7. **Test ทุก endpoint** — happy + 403 + 422 + 404

### 2. Route map (เพิ่มใน `routes/learn/academy.php`)

```php
// === Phase 3.C: Per-student enrollment lifecycle ===
Route::middleware(['auth:api'])->scopeBindings()->group(function () {
    Route::post('academies/{academy}/students/{student}/graduate',
        [StudentLifecycleController::class, 'graduate'])
        ->name('api.academy.students.graduate');
    Route::post('academies/{academy}/students/{student}/drop',
        [StudentLifecycleController::class, 'drop'])
        ->name('api.academy.students.drop');
    Route::post('academies/{academy}/students/{student}/repeat',
        [StudentLifecycleController::class, 'repeat'])
        ->name('api.academy.students.repeat');
    Route::post('academies/{academy}/students/{student}/promote',
        [StudentLifecycleController::class, 'promote'])
        ->name('api.academy.students.promote');
    Route::post('academies/{academy}/students/{student}/transfer',
        [StudentLifecycleController::class, 'transfer'])
        ->name('api.academy.students.transfer');
    Route::get('academies/{academy}/students/{student}/enrollment-history',
        [StudentLifecycleController::class, 'history'])
        ->name('api.academy.students.enrollmentHistoryV2');
});
```

หมายเหตุ:
- `scopeBindings()` ทำให้ Laravel resolve `Student` ผ่าน relation ของ `Academy` (ต้องตรวจว่ามี `Academy::students()` relation; ถ้าไม่มี ใช้ alternative `Student::scopeBoundByAcademy`)
- ตั้งชื่อ route `enrollmentHistoryV2` กันชนกับ legacy `enrollmentHistory`

### 3. Controller spec

ไฟล์ใหม่: `app/Http/Controllers/Api/Learn/Academy/StudentLifecycleController.php`

```php
namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Enrollment\{
    DropStudentRequest, GraduateStudentRequest,
    PromoteStudentRequest, RepeatStudentRequest, TransferStudentRequest
};
use App\Http\Resources\Learn\Academy\Enrollment\{
    ClassroomStudentResource, StudentSummaryResource
};
use App\Models\{Academy, Classroom, Student};
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;

class StudentLifecycleController extends Controller
{
    public function __construct(
        private readonly StudentEnrollmentService $enroll,
    ) {}

    public function graduate(GraduateStudentRequest $req, Academy $academy, Student $student): JsonResponse
    {
        $closed = $this->enroll->graduateStudent(
            student: $student,
            classroom: null,
            reason: $req->input('reason', 'จบการศึกษา'),
            at: $req->date('effective_at') ?: today(),
            batchId: null,
            userId: $req->user()->id,
        );

        return $this->successResponse($student->fresh(), closed: $closed);
    }

    public function drop(DropStudentRequest $req, Academy $academy, Student $student): JsonResponse
    {
        $closed = $this->enroll->dropStudent(
            student: $student,
            classroom: null,
            reason: $req->input('reason'),
            at: $req->date('effective_at') ?: today(),
            batchId: null,
            userId: $req->user()->id,
        );

        return $this->successResponse($student->fresh(), closed: $closed);
    }

    public function repeat(RepeatStudentRequest $req, Academy $academy, Student $student): JsonResponse
    {
        $newClassroom = Classroom::findOrFail($req->integer('new_classroom_id'));

        try {
            $opened = $this->enroll->repeatStudent(
                student: $student,
                newClassroom: $newClassroom,
                studentNumber: $req->input('student_number'),
                reason: $req->input('reason', 'ซ้ำชั้น'),
                batchId: null,
                userId: $req->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => 'invalid_repeat', 'message' => $e->getMessage()], 422);
        }

        return $this->successResponse($student->fresh(), opened: $opened);
    }

    public function promote(PromoteStudentRequest $req, Academy $academy, Student $student): JsonResponse
    {
        $from = Classroom::findOrFail($req->integer('from_classroom_id'));
        $to = Classroom::findOrFail($req->integer('to_classroom_id'));

        try {
            $opened = $this->enroll->promoteStudent(
                student: $student,
                fromClassroom: $from,
                toClassroom: $to,
                reason: $req->input('reason', 'เลื่อนชั้น'),
                studentNumber: $req->input('student_number'),
                batchId: null,
                userId: $req->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => 'invalid_promote', 'message' => $e->getMessage()], 422);
        }

        return $this->successResponse($student->fresh(), opened: $opened);
    }

    public function transfer(TransferStudentRequest $req, Academy $academy, Student $student): JsonResponse
    {
        $from = Classroom::findOrFail($req->integer('from_classroom_id'));
        $to = Classroom::findOrFail($req->integer('to_classroom_id'));

        try {
            $opened = $this->enroll->transferStudent(
                student: $student,
                fromClassroom: $from,
                toClassroom: $to,
                reason: $req->input('reason', 'ย้ายห้อง'),
                batchId: null,
                userId: $req->user()->id,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'error' => 'invalid_transfer', 'message' => $e->getMessage()], 422);
        }

        return $this->successResponse($student->fresh(), opened: $opened);
    }

    public function history(Academy $academy, Student $student): JsonResponse
    {
        // Authorize via gate (no FormRequest needed for read)
        abort_unless(\Gate::allows('enrollment.lifecycle', [$academy, $student]), 403);

        $rows = $this->enroll->getStudentHistory($student->load('classroom'));

        return response()->json([
            'success' => true,
            'data' => ClassroomStudentResource::collection($rows->load(['classroom', 'createdBy'])),
        ]);
    }

    private function successResponse(
        Student $student,
        ?\App\Models\ClassroomStudent $closed = null,
        ?\App\Models\ClassroomStudent $opened = null,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'closed_enrollment' => $closed ? new ClassroomStudentResource($closed->load(['classroom', 'createdBy'])) : null,
            'new_enrollment' => $opened ? new ClassroomStudentResource($opened->load(['classroom', 'createdBy'])) : null,
            'student' => new StudentSummaryResource($student),
        ]);
    }
}
```

### 4. Route scope binding implementation

ก่อน scopeBindings() จะทำงานต้องมี relation `Academy::students()`

ตรวจ + ถ้าไม่มีให้เพิ่มใน 3.C:
```php
// app/Models/Academy.php
public function students(): HasMany
{
    return $this->hasMany(Student::class);
}
```

ทดสอบใน setUp ของ test ว่า `GET /api/academies/{academy}/students/{cross_academy_student}/enrollment-history` → 404 (ไม่ใช่ 403)

### 5. Tests

ไฟล์: `tests/Feature/Api/Academy/StudentLifecycleControllerTest.php`

| # | Test | Status code |
|---|---|---|
| L1 | graduate as academy admin → 200 + students.status='graduated' + closed enrollment in response | 200 |
| L2 | graduate as homeroom teacher → 200 (Decision §15.2) | 200 |
| L3 | graduate as random user → 403 | 403 |
| L4 | drop without reason → 422 (FormRequest) | 422 |
| L5 | drop with reason → 200 + students.status='inactive' | 200 |
| L6 | drop unauthenticated → 401 (middleware) | 401 |
| L7 | repeat with classroom of other academy → 422 (FormRequest scope) | 422 |
| L8 | repeat with same classroom → 422 (service throws InvalidArgumentException) | 422 |
| L9 | repeat happy path → 200 + new enrollment | 200 |
| L10 | promote with same year → 422 (FormRequest withValidator) | 422 |
| L11 | promote happy path → 200 + 2 rows changed | 200 |
| L12 | transfer with cross year → 422 | 422 |
| L13 | transfer happy path → 200 | 200 |
| L14 | history → 200 + list of enrollments with classroom loaded | 200 |
| L15 | cross-academy student (route scope) → 404 | 404 |
| L16 | history as student themselves → 403 | 403 |

รวม 16 tests

### 6. ลำดับ commit Phase 3.C (1 commit)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 3.C | feat(api): student lifecycle endpoints (graduate/drop/repeat/promote/transfer/history) | StudentLifecycleController.php, routes/learn/academy.php (+7 routes), Academy.php (เพิ่ม students() ถ้าไม่มี), tests/Feature/Api/Academy/StudentLifecycleControllerTest.php | ~350 |

รวม ~350 LOC, ~1.5 ชม.

### 7. Verification

1. `./vendor/bin/pint app/Http/Controllers/Api/Learn/Academy/StudentLifecycleController.php tests/Feature/Api/Academy/StudentLifecycleControllerTest.php routes/learn/academy.php app/Models/Academy.php`
2. `./vendor/bin/phpunit tests/Feature/Api/Academy/StudentLifecycleControllerTest.php`
3. Regression: phpunit ทุก enrollment test (ต้อง 58+16 = 74)
4. `php artisan route:list --path=academies/.*/students` — ต้องเห็น 6 routes ใหม่
5. ไม่กระทบ legacy: `ClassroomController` paths ยังเรียกได้ (manual sanity)

### 8. Edge cases & gotchas

| # | Case | จุด |
|---|---|---|
| EC1 | `Academy::students()` relation ไม่มี | ตรวจก่อน — ถ้าไม่มีต้องเพิ่ม (1 line) |
| EC2 | scopeBindings ทำงานเฉพาะ Laravel 9+ — โปรเจคใช้ 12 OK | ✓ |
| EC3 | `$req->date(...)` อาจ throw ถ้า format ไม่ใช่ ISO | FormRequest validate `date` rule แล้ว — controller ปลอดภัย |
| EC4 | history endpoint ไม่ใช้ FormRequest → authorize ที่ controller | abort_unless + Gate::allows ตรง |
| EC5 | `Classroom::findOrFail` ใน promote/transfer/repeat — ถ้าไม่เจอ → 404 | FormRequest มี `exists` rule แล้ว — defensive only |
| EC6 | service ใช้ named arg → PHP 8+ OK | ✓ (PHP 8.4 ใน WAMP) |
| EC7 | `$student->fresh()` — ก่อน fresh enrollment ตัวเก่าใน response อาจ stale | load ใหม่ทุกครั้ง |
| EC8 | history collection ขนาดใหญ่ → ไม่ paginate | ปกตินักเรียน 1 คนมีประวัติ <20 rows ไม่เป็นปัญหา; ถ้าต้องการ paginate → Phase ถัดไป |

### 9. Out of scope ของ 3.C

- ❌ Rollover controller (3.D + 3.E)
- ❌ Frontend wizard (Phase 5)
- ❌ ลบ legacy `ClassroomController::transferStudent/promoteClassroom` (Phase 6)
- ❌ Audit log integration (Phase 8)
- ❌ Bulk endpoints (1 student ต่อ request)

### 10. Risks

### 11. Implementation Update — 2026-06-21

- ลงมือทำ Phase 3.C แล้วครบ 6 controller methods + 6 routes ใหม่
- ใช้ `StudentLifecycleController` ใหม่ที่ delegate ไป `StudentEnrollmentService` โดยตรง และ map `InvalidArgumentException` เป็น 422
- response shape ของ lifecycle endpoints คงที่เป็น `{success, closed_enrollment, new_enrollment, student}` และ include `null` fields ตาม decision
- history endpoint ใช้ controller-level gate check ตามที่ยืนยันไว้ และคืน array ของ `ClassroomStudentResource`
- ปรับจากแผนเล็กน้อยเพื่อหลบ route ชนกับ legacy จริง:
  - legacy path คงไว้ที่ `/enrollment-history`
  - endpoint ใหม่ใช้ path `/enrollment-history-v2`
  - route name ใช้ `api.academy.students.enrollmentHistoryV2`
- `Academy::students()` มีอยู่แล้วใน model จึงไม่ต้องเพิ่ม relation ใหม่
- verification ล่าสุด
  - `StudentLifecycleControllerTest` ผ่าน 16 tests
  - ชุด enrollment regression รวมผ่าน 74 tests / 220 assertions
  - `php artisan route:list --path=students` เห็น routes ใหม่ครบ
  - Pint ผ่านไฟล์ที่แตะใน 3.C
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| `Academy::students()` ไม่มี → scopeBindings พัง 404 ทุก call | กลาง | สูง | check ก่อน implement; ถ้าไม่มี เพิ่มใน commit นี้ |
| legacy `enrollment-history` route ชนกับ V2 | ต่ำ | กลาง | ตั้งชื่อ route ใหม่ + path เหมือนแต่ handler ต่าง — ตรวจ priority ของ route definition |
| Frontend เก่าเรียก legacy `transfer-student` — Phase 3.C ไม่กระทบ | — | — | legacy คงไว้ |
| service `transferStudent` cross-year ตอนนี้ throw — controller ต้อง catch | กลาง | ต่ำ | try/catch ใน controller method ทุกตัวที่ service throw |

### 11. Definition of Done

- [ ] 1 commit lands
- [ ] 6 routes ใหม่ปรากฏใน `route:list`
- [ ] 16 tests ผ่าน
- [ ] Regression 58 tests ยังผ่าน → รวม 74
- [ ] pint clean
- [ ] manual smoke 1 endpoint (curl `POST /api/academies/1/students/<test>/drop`) → ตรงตาม response shape

### 12. Decisions ที่รอยืนยันก่อนเริ่ม

1. **Route naming**: `enrollmentHistoryV2` (กันชน legacy) หรือ rename legacy เป็น V1?
   - **Recommendation:** ใช้ V2 + legacy คงเดิม; Phase 6 ค่อยรื้อ
2. **History endpoint method**: GET พร้อม Gate::allows ใน controller, ไม่มี FormRequest
   - **Recommendation:** OK — read endpoint ไม่ต้อง FormRequest
3. **Response shape**: include `closed_enrollment` + `new_enrollment` ทั้งคู่เสมอ (null ถ้าไม่มี) หรือ omit field?
   - **Recommendation:** include null — frontend code ง่ายกว่า
4. **`Academy::students()` relation** ถ้ายังไม่มี ให้เพิ่มใน commit นี้หรือแยก commit?
   - **Recommendation:** รวมใน commit 3.C — เป็น dependency ตรง

---

## 2026-06-21 Phase 3.D — RolloverController (Read endpoints) (Detailed Plan)

### 0. State at start of 3.D

จาก Phase 3.A/B/C เสร็จแล้ว:
- ✅ `AcademicYearRolloverService` (Phase 2): `previewRollover`, `planRollover`, `commitRollover`, `undoRollover`, `closeUndoWindow`
- ✅ `RolloverPlan` value object (มี `toArray()` แต่ยังไม่มี `fromArray()` — เพิ่มใน 3.E)
- ✅ `RolloverBatch` model + `isUndoable()`
- ✅ 4 FormRequests (Preview/Plan/Commit/Undo) + Gates
- ✅ `RolloverBatchResource` พร้อมใช้
- ✅ 74 regression tests (220 assertions) ผ่านครบถ้วน

แยก 3.D / 3.E เพราะ:
- 3.D = read-only (preview/plan/index/show) — ไม่ touch DB เพื่อให้ทดสอบได้เร็วและปลอดภัย
- 3.E = write (commit/undo/closeUndo) — touch DB จริง + plan caching consumption + confirm_text logic + `RolloverPlan::fromArray()` มีความซับซ้อนกว่า

3.D ต้องเสร็จก่อนเพราะ 3.E ต้องใช้ `plan_id` cache pattern ที่ 3.D สร้าง

### 1. หลักการ 3.D

1. **Route-model binding ต่อจาก 3.C** — `Academy $academy` + `RolloverBatch $batch` (uuid binding)
2. **Plan caching key format**: `rollover_plan:{plan_uuid}:user:{user_id}` TTL 900s (15 นาที)
3. **Plan caching เก็บแค่ `toArray()`** — `fromArray()` มาทำใน 3.E
4. **`previewRollover` ไม่ cache** — เป็น read-only computation; client cache ฝั่ง wizard
5. **No write to DB ใน 3.D** — ใช้ `DB::transaction()` ไม่จำเป็น
6. **Pagination `index`** — คืนรายการแบบ paginate (`?per_page=20`, ปรับได้ไม่เกิน 100)

### 2. Route map (เพิ่มใน `routes/learn/academy.php` ใต้ 3.C)

```php
// === Phase 3.D/E: Rollover wizard ===
Route::middleware(['auth:api'])->prefix('academies/{academy}/rollover')
    ->name('api.academy.rollover.')->group(function () {

    // 3.D — read endpoints
    Route::post('preview', [RolloverController::class, 'preview'])->name('preview');
    Route::post('plan', [RolloverController::class, 'plan'])->name('plan');
    Route::get('batches', [RolloverController::class, 'index'])->name('index');

    Route::scopeBindings()->group(function () {
        Route::get('batches/{batch}', [RolloverController::class, 'show'])->name('show');

        // 3.E — write endpoints (เพิ่มทีหลัง)
        // Route::post('commit', ...)
        // Route::post('batches/{batch}/undo', ...)
        // Route::post('batches/{batch}/close-undo', ...)
    });
});
```

หมายเหตุ:
- `{batch}` ใช้ uuid → `RolloverBatch::getRouteKeyName()` ต้อง return `'id'` (default; column เป็น `char(36)` PK)
- `scopeBindings()` ใช้กับ batch route → Laravel ตรวจ `Academy::rolloverBatches()` relation และตอบกลับ 404 อัตโนมัติหากโรงเรียนอื่นพยายามเรียกข้าม
- **ต้องเพิ่ม `Academy::rolloverBatches(): HasMany`** เพื่อให้ใช้งาน `scopeBindings()` ได้

### 3. Model relation ที่ต้องเพิ่ม

```php
// app/Models/Academy.php
public function rolloverBatches(): HasMany
{
    return $this->hasMany(\App\Models\RolloverBatch::class);
}
```

และตรวจ `RolloverBatch` มี relations ที่ Resource ต้องใช้:
- `academy()` BelongsTo Academy (ต้อง Eager Load เพื่อให้ `RolloverBatchResource` เช็คสิทธิ์และควบคุม visibility ของ `plan_summary` เฉพาะ admin ได้)
- `fromAcademicYear()` BelongsTo AcademicYear (FK from_academic_year_id)
- `toAcademicYear()` BelongsTo AcademicYear (FK to_academic_year_id)
- `committedBy()` BelongsTo User (FK committed_by_user_id)
- `undoneBy()` BelongsTo User (FK undone_by_user_id)

### 4. Controller spec

ไฟล์ใหม่: `app/Http/Controllers/Api/Learn/Academy/RolloverController.php`

```php
namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Rollover\{PlanRolloverRequest, PreviewRolloverRequest};
use App\Http\Resources\Learn\Academy\Enrollment\RolloverBatchResource;
use App\Models\{Academy, AcademicYear, RolloverBatch};
use App\Services\AcademicYearRolloverService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Gate};
use Illuminate\Support\Str;

class RolloverController extends Controller
{
    public function __construct(
        private readonly AcademicYearRolloverService $rollover,
    ) {}

    public function preview(PreviewRolloverRequest $req, Academy $academy): JsonResponse
    {
        $from = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('from_year_id'));
        $to = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('to_year_id'));

        $preview = $this->rollover->previewRollover($academy, $from, $to);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    public function plan(PlanRolloverRequest $req, Academy $academy): JsonResponse
    {
        $from = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('from_year_id'));
        $to = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('to_year_id'));

        $plan = $this->rollover->planRollover(
            $academy, $from, $to, $req->input('mapping')
        );

        $planId = (string) Str::uuid();
        Cache::put(
            "rollover_plan:{$planId}:user:{$req->user()->id}",
            $plan->toArray(),
            900 // 15 min
        );

        return response()->json([
            'success' => true,
            'plan_id' => $planId,
            'expires_in_seconds' => 900,
            'summary' => $plan->summary,
            'warnings' => $plan->warnings,
            'entries_count' => count($plan->entries),
        ]);
    }

    public function index(Request $req, Academy $academy): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.viewBatches', $academy), 403);

        $query = RolloverBatch::where('academy_id', $academy->id)
            ->with(['academy', 'fromAcademicYear:id,name', 'toAcademicYear:id,name', 'committedBy:id,name', 'undoneBy:id,name']);

        if ($req->filled('status')) {
            $query->where('status', $req->input('status'));
        }

        $batches = $query->latest('committed_at')
            ->paginate(min($req->integer('per_page', 20), 100));

        return RolloverBatchResource::collection($batches)->response();
    }

    public function show(Academy $academy, RolloverBatch $batch): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.viewBatches', $academy), 403);
        // scopeBindings already enforces $batch->academy_id === $academy->id

        $batch->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy']);

        return response()->json([
            'success' => true,
            'batch' => new RolloverBatchResource($batch),
        ]);
    }
}
```

หมายเหตุ Resource:
- `RolloverBatchResource` (จาก 3.B) คำนวณ `is_undoable`, `undo_expires_at`, ซ่อน `plan_summary` จากผู้ที่ไม่ใช่แอดมิน (admin-only)
- `paginate()` → `Resource::collection(...)` จัดการ pagination meta อัตโนมัติ

### 5. Cache key strategy

- **Key:** `rollover_plan:{plan_uuid}:user:{user_id}` — scope ต่อ user เพื่อป้องกันสิทธิ์เข้าถึงชนกันหรือข้อมูลรั่วไหล
- **TTL:** 900 วินาที (15 นาที) ตามการตัดสินใจ
- **Driver:** Laravel default (file ใน WAMP)
- **Format:** `$plan->toArray()` — flat array สำหรับ serialize ปลอดภัย
- **Forget:** 3.E commit จะ `Cache::forget(...)` หลัง commit สำเร็จ

### 6. Tests

ไฟล์: `tests/Feature/Api/Academy/RolloverControllerReadTest.php`

| # | Test | Status |
|---|---|---|
| C1 | preview as academy admin → 200 + suggested mapping shape | 200 |
| C2 | preview as teacher → 200 (Decision: teacher preview ได้) | 200 |
| C3 | preview as student → 403 | 403 |
| C4 | preview from_year ของ academy อื่น → 422 (FormRequest scope) | 422 |
| C5 | plan as admin → 200 + plan_id (uuid) + cached | 200 |
| C6 | plan as teacher → 200 | 200 |
| C7 | plan with invalid mapping (student ไม่อยู่ใน academy) → 422 | 422 |
| C8 | plan returns warnings for missing target classroom | 200 (warnings non-empty) |
| C9 | plan cached key correct format | direct cache check |
| C10 | index as staff → paginated list (only own academy) | 200 |
| C11 | index as student → 403 | 403 |
| C12 | show batch in own academy → 200 + full batch shape | 200 |
| C13 | show batch from other academy → 404 (scopeBindings) | 404 |
| C14 | show batch hides plan_summary for teacher | 200 + plan_summary=null |
| C15 | show batch shows plan_summary for admin | 200 + plan_summary present |

รวม 15 tests

### 7. ลำดับ commit Phase 3.D (1 commit)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 3.D | feat(api): rollover read endpoints (preview/plan/index/show) + plan caching | RolloverController.php, routes/learn/academy.php (+4 routes), Academy.php (+rolloverBatches), RolloverBatch.php (+relations ถ้าขาด), tests/...RolloverControllerReadTest.php | ~400 |

รวม ~400 LOC, ~1.5 ชม.

### 8. Verification

1. `./vendor/bin/pint app/Http/Controllers/Api/Learn/Academy/RolloverController.php tests/Feature/Api/Academy/RolloverControllerReadTest.php routes/learn/academy.php`
2. `./vendor/bin/phpunit tests/Feature/Api/Academy/RolloverControllerReadTest.php`
3. Regression: รวมทุก enrollment/rollover test (ต้อง 74 + 15 = 89)
4. `php artisan route:list --path=academies/.*/rollover` — เห็น 4 routes
5. Manual cache check: `php artisan tinker --execute="dump(Cache::get('rollover_plan:any:user:1'));"` (ก่อน plan = null, หลัง = array)

### 9. Edge cases

| # | Case | จุด |
|---|---|---|
| EC1 | Cache file driver บน WAMP path | ทำงาน OK; ตรวจ `storage/framework/cache/` writable |
| EC2 | plan_id เดียวกัน user 2 คน → กัน data leak | key มี `:user:{$user_id}` แล้ว ✓ |
| EC3 | `RolloverBatch::getRouteKeyName()` default = 'id' | char(36) PK — Laravel resolve uuid ใน path ได้ |
| EC4 | scopeBindings กับ batch ต้องการ `Academy::rolloverBatches()` | ต้องเพิ่ม |
| EC5 | `from_year_id` exists แต่ของ academy อื่น | FormRequest มี `Rule::exists->where('academy_id', ...)` แล้ว ✓ |
| EC6 | preview ที่ from/to year ไม่ active หรือ closed | service ไม่ block (ปกติ admin ทำ rollover ของปีปิดได้) — บันทึก behavior |
| EC7 | paginate query string `?per_page=999` | clamp default 20; max 100 ผ่าน `min()` ใน controller |
| EC8 | empty academy (ไม่มี batch) → `index` ต้องคืน 200 + data:[] | OK |

### 10. Out of scope ของ 3.D

- ❌ commit endpoint (3.E)
- ❌ undo endpoint (3.E)
- ❌ closeUndo endpoint (3.E)
- ❌ `RolloverPlan::fromArray()` (3.E)
- ❌ Frontend wizard (Phase 5)
- ❌ Cache eviction strategy (Laravel built-in)

### 11. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Cache file driver permission ใน WAMP | ต่ำ | สูง | `chmod` test ก่อน; default OK |
| `RolloverBatch` ไม่มี relations ที่ Resource ต้องใช้ | กลาง | สูง | ตรวจ + เพิ่มใน 3.D commit |
| `Academy::rolloverBatches()` ไม่มี → scopeBindings 404 | สูง | สูง | เพิ่ม in 3.D commit (เหมือน 3.C เพิ่ม students()) |
| plan ใหญ่ (3000+ entries) cache file size | กลาง | กลาง | 3000 entries × ~50 bytes = 150KB ยอมรับได้; ถ้าเกินใช้ redis ใน production |
| Resource per_page query param ถูก override | ต่ำ | ต่ำ | `min($req->integer('per_page', 20), 100)` |
| FormRequest preview/plan scope ใช้ `$this->route('academy')` แต่ binding ใช้ id raw จาก middleware? | ต่ำ | กลาง | ตรวจ route binding ส่ง Academy instance |

### 12. Definition of Done

- [ ] 1 commit lands
- [ ] 4 routes ใหม่ใน `route:list` (preview/plan/index/show)
- [ ] 15 tests ผ่าน (C1-C15)
- [ ] Regression 74 ยังผ่าน → รวม 89
- [ ] `Academy::rolloverBatches()` relation ทำงาน (test scope)
- [ ] `RolloverBatch` relations (fromYear, toYear, committedBy, undoneBy) ทำงาน
- [ ] pint clean
- [ ] manual smoke: `POST /api/academies/1/rollover/preview {from_year_id:1, to_year_id:2}` → 200 (ต้องสร้าง year 2569 ก่อนใน DB)

### 13. Decisions ที่ยืนยันแล้ว

1. **Pagination default `per_page`**: กำหนดเป็น **20** (ตามข้อแนะนำ)
2. **Cache prefix**: กำหนดเป็น **`rollover_plan:`** (ตามข้อแนะนำ เพื่อความชัดเจนในการเข้าถึงและ Debug)
3. **`index` endpoint รวม undone batches**: แสดงทั้งหมด (**show all**) โดยมี filter parameter `?status=committed` เป็นตัวเลือกเพิ่มเติม (ตามข้อแนะนำ)
4. **`previewRollover` ถ้า from_year เป็นปีปัจจุบันที่ใช้งานอยู่**: แสดงคำเตือน (**warn**) แต่จะไม่บล็อกการทำงาน (ตามข้อแนะนำ เพื่อให้ผู้บริหารตรวจสอบและวางแผนล่วงหน้าได้)

### 14. Implementation Update — 2026-06-21

- ลงมือทำ Phase 3.D เสร็จเรียบร้อยครบ 4 controller methods + 4 routes ใหม่
- สร้าง `RolloverController` ใหม่รับ preview, plan, index, show
- เพิ่ม `rolloverBatches()` และ `batches()` relationships ลงใน `Academy` model เพื่อแก้ปัญหา Laravel scope bindings parameter `{batch}`
- index endpoint รองรับการกรองตามสถานะผ่าน `?status=...` และจำกัด per_page สูงสุด 100
- rollover plan มีการเก็บลง Cache โดยใช้รูปแบบกุญแจ `rollover_plan:{uuid}:user:{user_id}` อายุ 15 นาที (900 วินาที)
- ผ่านการจัดรูปแบบโค้ดด้วย Pint เรียบร้อย
- มีการยืนยันผลลัพธ์ผ่านชุดทดสอบ:
  - `RolloverControllerReadTest` ผ่านครบถ้วน 15 tests (60 assertions)
  - Regression tests ทั้งหมดของฝั่ง Enrollment (36 tests) และ V2 API (31 tests) ผ่านทั้งหมด 100% รวม 89 tests ผ่านสมบูรณ์

---

## 2026-06-21 Phase 3.E — RolloverController (Write endpoints) (Detailed Plan)

### Execution Update (2026-06-21)

- Done: implemented `commit`, `undo`, `closeUndo` in `RolloverController`
- Done: added 3 routes under `api.academy.rollover.*` and confirmed total rollover routes = 7
- Done: added `RolloverPlan::fromArray()` plus round-trip coverage in `AcademicYearRolloverServiceTest`
- Done: added `RolloverControllerWriteTest` with 16 write-endpoint scenarios
- Locked decisions used in implementation:
  - academy mismatch => `422 academy_mismatch`
  - `closeUndo` on already-undone batch => `200` idempotent no-op
  - commit response keeps totals under `batch.totals`
- Verification completed:
  - `./vendor/bin/pint app/Http/Controllers/Api/Learn/Academy/RolloverController.php app/Services/Rollover/RolloverPlan.php tests/Feature/Api/Academy/RolloverControllerWriteTest.php tests/Feature/AcademicYearRolloverServiceTest.php`
  - `php artisan test tests/Feature/Api/Academy/RolloverControllerWriteTest.php tests/Feature/AcademicYearRolloverServiceTest.php` => 28 tests passed
  - `php artisan test tests/Feature/StudentEnrollmentServiceTest.php tests/Feature/AcademicYearRolloverServiceTest.php tests/Feature/EnrollmentPolicyTest.php tests/Feature/Api/Academy/StudentLifecycleControllerTest.php tests/Feature/Api/Academy/RolloverControllerReadTest.php tests/Feature/Api/Academy/RolloverControllerWriteTest.php` => 84 tests passed, 256 assertions
  - `php artisan route:list --name=api.academy.rollover` => 7 routes present
- Deferred: live WAMP smoke against shared real data was intentionally not run in this turn to avoid touching the 1929-row real dataset without a dedicated safe target set.

### 0. State at start of 3.E

จาก Phase 3.A-3.D เสร็จแล้ว:
- ✅ `RolloverController` มี `preview`, `plan`, `index`, `show` (3.D)
- ✅ `CommitRolloverRequest`, `UndoRolloverRequest` (3.B) พร้อม authorize gate
- ✅ Plan caching pattern: `rollover_plan:{uuid}:user:{user_id}` TTL 900s (3.D)
- ✅ Service ของ Phase 2 พร้อม: `commitRollover(RolloverPlan, User)`, `undoRollover(string, User)`, `closeUndoWindow(string, User)`
- ✅ `RolloverPlan::toArray()` มีอยู่ — **ขาด `fromArray()`** ที่ 3.E ต้องเพิ่ม
- ✅ 89 regression tests ผ่าน
- ✅ Decision §15.3 lock: confirm_text ใช้ `trim()` เท่านั้น

### 1. หลักการ Phase 3.E

1. **`RolloverPlan::fromArray()` คืน instance สมบูรณ์** — round-trip safe กับ `toArray()`
2. **commit endpoint = "consume cached plan + verify confirm + delegate"** — ไม่มี business logic
3. **Exception → HTTP code mapping:**
   - Plan cache miss (expired/wrong user) → 410 Gone
   - confirm_text ไม่ตรง → 422
   - `RolloverNotUndoable` → 409 Conflict
   - Generic service exception → 500 (let Laravel handle)
4. **Idempotency: ลบ cache หลัง commit สำเร็จ** — กัน double-commit
5. **scopeBindings + Gate ทุก write endpoint** — defense in depth
6. **Test ครบ happy + 4xx paths**

### 2. Route map (เพิ่มใน scopeBindings group เดิม)

```php
// ใต้ batches/{batch} show ที่มีแล้ว
Route::scopeBindings()->group(function () {
    Route::get('batches/{batch}', [RolloverController::class, 'show'])->name('show');

    // === Phase 3.E ===
    Route::post('commit', [RolloverController::class, 'commit'])->name('commit');
    Route::post('batches/{batch}/undo', [RolloverController::class, 'undo'])->name('undo');
    Route::post('batches/{batch}/close-undo', [RolloverController::class, 'closeUndo'])->name('closeUndo');
});
```

**หมายเหตุ:** `commit` ไม่มี `{batch}` (batch ยังไม่ถูกสร้าง — service จะสร้างให้)

### 3. `RolloverPlan::fromArray()`

```php
// app/Services/Rollover/RolloverPlan.php
public static function fromArray(array $data): self
{
    return new self(
        academyId: (int) $data['academy_id'],
        fromYearId: (int) $data['from_academic_year_id'],
        toYearId: (int) $data['to_academic_year_id'],
        entries: $data['entries'] ?? [],
        summary: $data['summary'] ?? [],
        warnings: $data['warnings'] ?? [],
    );
}
```

Round-trip test:
```php
$plan = new RolloverPlan(1, 1, 2, [...], [...], []);
$rebuilt = RolloverPlan::fromArray($plan->toArray());
$this->assertEquals($plan->toArray(), $rebuilt->toArray());
```

### 4. Controller spec (เพิ่ม methods)

```php
// app/Http/Controllers/Api/Learn/Academy/RolloverController.php

use App\Exceptions\RolloverNotUndoable;
use App\Http\Requests\Academy\Rollover\{CommitRolloverRequest, UndoRolloverRequest};
use App\Models\AcademicYear;
use App\Services\Rollover\RolloverPlan;

public function commit(CommitRolloverRequest $req, Academy $academy): JsonResponse
{
    $userId = $req->user()->id;
    $planId = $req->input('plan_id');
    $cacheKey = "rollover_plan:{$planId}:user:{$userId}";

    $cached = Cache::get($cacheKey);
    if (! $cached) {
        return response()->json([
            'success' => false,
            'error' => 'plan_expired',
            'message' => 'Plan expired or not found. Please re-run the plan step.',
        ], 410);
    }

    // Verify cached plan belongs to this academy (defense)
    if ((int) $cached['academy_id'] !== $academy->id) {
        return response()->json([
            'success' => false,
            'error' => 'academy_mismatch',
            'message' => 'Cached plan belongs to a different academy.',
        ], 422);
    }

    // Verify confirm_text matches destination year name (Decision §15.3 trim only)
    $toYear = AcademicYear::findOrFail($cached['to_academic_year_id']);
    if ($req->input('confirm_text') !== $toYear->name) {
        return response()->json([
            'success' => false,
            'error' => 'confirm_text_mismatch',
            'message' => 'Confirmation text must exactly match the destination academic year name.',
            'expected_format' => $toYear->name,
        ], 422);
    }

    $plan = RolloverPlan::fromArray($cached);
    $batch = $this->rollover->commitRollover($plan, $req->user());

    // Forget cache to prevent double-commit
    Cache::forget($cacheKey);

    return response()->json([
        'success' => true,
        'batch' => new RolloverBatchResource(
            $batch->load(['fromYear', 'toYear', 'committedBy'])
        ),
    ], 201);
}

public function undo(UndoRolloverRequest $req, Academy $academy, RolloverBatch $batch): JsonResponse
{
    // scopeBindings already enforces $batch->academy_id === $academy->id

    try {
        $undone = $this->rollover->undoRollover($batch->id, $req->user());
    } catch (RolloverNotUndoable $e) {
        return response()->json([
            'success' => false,
            'error' => 'cannot_undo',
            'message' => $e->getMessage(),
        ], 409);
    }

    return response()->json([
        'success' => true,
        'batch' => new RolloverBatchResource(
            $undone->load(['fromYear', 'toYear', 'committedBy', 'undoneBy'])
        ),
    ]);
}

public function closeUndo(Request $req, Academy $academy, RolloverBatch $batch): JsonResponse
{
    abort_unless(Gate::allows('enrollment.undo', [$academy, $batch]), 403);

    $this->rollover->closeUndoWindow($batch->id, $req->user());

    return response()->json([
        'success' => true,
        'batch' => new RolloverBatchResource(
            $batch->fresh()->load(['fromYear', 'toYear', 'committedBy'])
        ),
    ]);
}
```

### 5. Tests

ไฟล์: `tests/Feature/Api/Academy/RolloverControllerWriteTest.php`

| # | Test | Status |
|---|---|---|
| W1 | commit with valid plan + correct confirm_text → 201 + batch created | 201 |
| W2 | commit without plan_id in cache → 410 | 410 |
| W3 | commit with wrong confirm_text → 422 + expected_format hint | 422 |
| W4 | commit with confirm_text containing leading/trailing space → 422 (FormRequest trim, then strict compare) | 422 if user adds extra char, but trim removes only outer ws — verify behavior |
| W5 | commit cached plan from different academy → 422 academy_mismatch | 422 |
| W6 | commit as teacher → 403 (Gate enrollment.commit admin-only) | 403 |
| W7 | commit twice with same plan_id → second call 410 (cache forgotten) | 410 on 2nd |
| W8 | undo within 24h → 200 + batch.status='undone' + state restored | 200 |
| W9 | undo after closeUndoWindow → 409 cannot_undo | 409 |
| W10 | undo after 24h → 409 | 409 |
| W11 | undo cross-academy batch → 404 (scopeBindings) | 404 |
| W12 | undo as teacher → 403 | 403 |
| W13 | undo with newer enrollment changes for same student → 409 | 409 |
| W14 | closeUndo as admin → 200 + undo_closed_at set | 200 |
| W15 | closeUndo as teacher → 403 | 403 |
| W16 | closeUndo cross-academy → 404 | 404 |

รวม 16 tests

### 6. round-trip test ของ `RolloverPlan` (ใน `tests/Feature/AcademicYearRolloverServiceTest.php`)

เพิ่ม 1 test:
| # | Test |
|---|---|
| RP1 | `fromArray(toArray(plan))` produces equal instance | |

### 7. ลำดับ commit Phase 3.E (1 commit)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 3.E | feat(api): rollover write endpoints (commit/undo/closeUndo) + RolloverPlan::fromArray | RolloverController.php (+3 methods), RolloverPlan.php (+fromArray), routes/learn/academy.php (+3 routes), tests/...RolloverControllerWriteTest.php, tests/...AcademicYearRolloverServiceTest.php (+RP1) | ~400 |

รวม ~400 LOC, ~1.5 ชม.

### 8. Verification

1. `./vendor/bin/pint app/Http/Controllers/Api/Learn/Academy/RolloverController.php app/Services/Rollover/RolloverPlan.php tests/Feature/Api/Academy/RolloverControllerWriteTest.php`
2. `./vendor/bin/phpunit tests/Feature/Api/Academy/RolloverControllerWriteTest.php`
3. Full regression: ต้อง 89 + 16 + 1 = 106 tests
4. `php artisan route:list --path=academies/.*/rollover` — เห็น 7 routes ครบ
5. **End-to-end manual smoke บน WAMP:**
   - สร้าง academic_year 2569 ผ่าน tinker
   - `POST /api/academies/1/rollover/preview` → 200
   - `POST /api/academies/1/rollover/plan` (mapping 1-2 students) → 200 + plan_id
   - `POST /api/academies/1/rollover/commit {plan_id, confirm_text: '2569'}` → 201
   - `POST /api/academies/1/rollover/batches/{uuid}/undo` → 200
   - ตรวจ state กลับเป็นก่อน commit

### 9. Edge cases

| # | Case | จุดที่ต้องระวัง |
|---|---|---|
| EC1 | Cache eviction ระหว่าง user เปิดหน้า commit | 410 → frontend re-run plan |
| EC2 | confirm_text ไทยมี zero-width space | service ส่ง name มา raw → user copy ทั้งแถบ → no extra char (low risk); ถ้าเกิดขึ้นจริง ค่อย add normalization |
| EC3 | commit เรียกซ้ำเร็ว (network retry) | cache forget หลังสำเร็จ → 2nd call 410; idempotent ผ่าน cache state |
| EC4 | RolloverBatch ที่ status='undone' undo ซ้ำ | `isUndoable()` return false → 409 |
| EC5 | RolloverPlan::fromArray กับ missing key | default `[]` for optional; required key throw `\ArgumentCountError` → 500 (ป้องกันด้วย unit test) |
| EC6 | scopeBindings batch route → ต้อง `Academy::rolloverBatches()` (มีจาก 3.D) | ✓ |
| EC7 | `closeUndo` ที่ batch ที่ status='undone' แล้ว | service ไม่ block (ปลอดภัย); test: closeUndo หลัง undo → 200 (no-op effect) |
| EC8 | concurrent commit ของ user 2 คนใน academy เดียวกัน | service ใช้ `lockForUpdate` ที่ academic_years (Phase 2 implemented) ✓ |

### 10. Out of scope ของ 3.E

- ❌ Frontend wizard (Phase 5)
- ❌ Bulk preview optimization (Phase performance)
- ❌ Webhook/notification เมื่อ commit/undo (Phase 7)
- ❌ Background job ถ้า plan ใหญ่ >5000 entries (Phase performance)

### 11. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Plan cache miss กลางทาง user UX แย่ | กลาง | กลาง | frontend แสดง countdown timer (Phase 5); ส่ง expires_in_seconds จาก 3.D plan response |
| commit ทำ DB write 2000+ rows ใน 1 request → timeout | ต่ำ | สูง | service มี transaction; ถ้า slow → Phase performance เพิ่ม queue |
| RolloverPlan::fromArray ไม่ symmetric → silent data loss | กลาง | สูง | round-trip test RP1 บังคับ |
| confirm_text Decision §15.3 trim only — user paste ที่มี newline | ต่ำ | ต่ำ | trim ใน FormRequest จัดการ |
| EC8 race condition concurrent commit | ต่ำ | สูง | lockForUpdate มีแล้ว ✓ |

### 12. Definition of Done

- [ ] 1 commit lands
- [ ] 7 rollover routes ทั้งหมดใน `route:list`
- [ ] 16 + 1 = 17 tests ใหม่ผ่าน
- [ ] Regression รวม 106
- [ ] pint clean
- [ ] End-to-end manual smoke ผ่าน 5 steps (preview/plan/commit/undo/verify state)
- [ ] commit message ระบุ Phase 3.E
- [ ] **Phase 3 ทั้งหมดปิด** — พร้อมเดิน Phase 4 (Frontend wizard) หรือ Phase 6 (report sync)

### 13. Decisions ที่รอยืนยัน

1. **Cache mismatch academy ส่ง 422 หรือ 410?**
   - 422 บอก "wrong plan"; 410 บอก "expired"
   - **Recommendation:** 422 (เป็น validation error ที่ปัก field)

2. **closeUndo ของ batch ที่ undone แล้ว** → 200 no-op หรือ 409?
   - **Recommendation:** 200 (idempotent) — UX ดีกว่า

3. **commit response include `totals` ที่ root** หรือ nest ใน `batch.totals`?
   - **Recommendation:** nest ใน `batch.totals` (มีอยู่ใน RolloverBatchResource แล้ว) — single source

4. **Manual E2E smoke test** — รัน บน DB จริง 1929 enrollments จะ rollback หลัง test?
   - **Recommendation:** ทำกับ test student 1-2 คนเท่านั้น + undo ทันที; ห้ามแตะ data จริง 1929

---

## 2026-06-21 Phase 4 — Frontend Single-Student Status Actions (Detailed Plan)

### 0. State at start of Phase 4

จาก Phase 3 ทั้งหมดเสร็จ (verified 106 tests pass):
- ✅ 6 API endpoints `/api/academies/{academy}/students/{student}/{action}` (graduate/drop/repeat/promote/transfer/history)
- ✅ Response shape สม่ำเสมอ: `{success, closed_enrollment, new_enrollment, student}`
- ✅ FormRequests validate ทุก param ก่อนถึง controller
- ✅ Policy คุม: academy admin + homeroom teacher ของ active classroom ทำได้
- ✅ Error responses: 403 (forbidden), 422 (validation/business rule), 404 (cross-academy)

UI ปัจจุบัน:
- หน้า [classrooms/[id].vue](ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue) — จัดการรายชื่อนักเรียนในห้อง (add/remove ปัจจุบัน)
- หน้า [classrooms/index.vue](ui/pages/academies/[name]/admin/gradebook/classrooms/index.vue) — list ห้อง
- Tech stack: Nuxt 4 + Vue 3 Composition API + Pinia + PrimeVue + Tailwind + Iconify
- API client: `useApi()` composable (มีอยู่)
- ไม่มี enrollment action UI ใดๆ — รายชื่อ student อยู่นิ่ง add/remove เท่านั้น

### 1. หลักการ Phase 4

1. **UI ใหม่ทั้งหมดเป็น component แยก** — ไม่ inline ลงหน้าใหญ่
2. **Modal-based actions** — ทุก lifecycle action เปิด modal ขอ confirm + ใส่ reason/target
3. **Optimistic UX** — แสดง loading state + toast success/error
4. **Composable centralize API calls** — `useStudentEnrollmentActions()` ห่อ 6 endpoints
5. **Refresh list หลัง action** — refetch ไม่ใช่ patch local state (กัน state drift)
6. **Separate tabs สำหรับ status:** "กำลังศึกษา / ออกจากห้อง" — แยกชัด
7. **Reuse PrimeVue + Tailwind** — ตาม `ui-principles` skill
8. **i18n strings ผ่าน `useI18n`** — ไม่ hardcode ไทยใน template ถ้าไฟล์อื่นใช้ pattern นั้น
9. **A11y พื้นฐาน** — focus trap, ESC close modal, role=dialog

### 2. โครงสร้างไฟล์ใหม่

```
ui/
├── composables/
│   └── useStudentEnrollmentActions.ts        (4.1)
├── components/
│   └── academy/
│       └── enrollment/
│           ├── StudentActionMenu.vue          (4.2) - dropdown ของ 5 actions
│           ├── StudentStatusActionModal.vue   (4.3) - modal universal (render form ตาม action)
│           ├── StudentStatusBadge.vue         (4.4) - badge สีตาม status
│           └── EnrollmentHistoryDrawer.vue    (4.5) - sidebar drawer แสดง history
└── pages/
    └── academies/[name]/admin/gradebook/classrooms/
        └── [id].vue                            (4.6) - ขยายให้มี tabs + integrate
```

ไม่แตะหน้า index.vue ใน Phase 4 (จะ revisit ที่ Phase 5)

### 3. Composable: `useStudentEnrollmentActions`

```typescript
// composables/useStudentEnrollmentActions.ts
import type { Ref } from 'vue'

export type EnrollmentAction = 'graduate' | 'drop' | 'repeat' | 'promote' | 'transfer'

export interface ActionPayload {
  reason?: string
  effective_at?: string             // ISO date
  new_classroom_id?: number         // repeat
  from_classroom_id?: number        // promote / transfer
  to_classroom_id?: number          // promote / transfer
  student_number?: number
}

export interface EnrollmentResponse {
  success: boolean
  closed_enrollment: ClassroomStudentDTO | null
  new_enrollment: ClassroomStudentDTO | null
  student: StudentSummaryDTO
}

export function useStudentEnrollmentActions(academyId: Ref<number>) {
  const api = useApi()
  const isLoading = ref(false)
  const lastError = ref<string | null>(null)

  async function execute(
    action: EnrollmentAction,
    studentId: number,
    payload: ActionPayload
  ): Promise<EnrollmentResponse> {
    isLoading.value = true
    lastError.value = null
    try {
      const res = await api.post<EnrollmentResponse>(
        `/api/academies/${academyId.value}/students/${studentId}/${action}`,
        payload
      )
      return res
    } catch (err: any) {
      lastError.value = err?.data?.message ?? err?.message ?? 'การดำเนินการล้มเหลว'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchHistory(studentId: number) {
    return api.get<{ success: boolean; data: ClassroomStudentDTO[] }>(
      `/api/academies/${academyId.value}/students/${studentId}/enrollment-history-v2`
    )
  }

  return { execute, fetchHistory, isLoading, lastError }
}
```

DTO types ใน `ui/types/enrollment.ts` (สร้างใหม่):
```typescript
export interface ClassroomStudentDTO {
  id: number
  student_id: number
  classroom_id: number
  academy_id: number
  academic_year_id: number
  student_number: number | null
  status: string
  status_text: string
  enrolled_at: string | null
  left_at: string | null
  leave_reason: string | null
  rollover_batch_id: string | null
  classroom?: {
    id: number
    display_name: string
    grade_level: string
    section: string
  }
}

export interface StudentSummaryDTO {
  id: number
  student_id: string
  academy_id: number
  first_name_th: string
  last_name_th: string
  nickname: string | null
  status: string
  class_level: string | null
  class_section: string | null
}
```

### 4. Component: `StudentActionMenu.vue`

PrimeVue `Menu` (overlay popup) attached to "⋯" button per row:

```vue
<template>
  <Button
    icon="pi pi-ellipsis-v"
    text rounded size="small"
    @click="menu?.toggle($event)"
    aria-label="จัดการนักเรียน"
  />
  <Menu ref="menu" :model="items" popup />
</template>

<script setup lang="ts">
import Button from 'primevue/button'
import Menu from 'primevue/menu'

const props = defineProps<{
  student: StudentSummaryDTO
  enrollment: ClassroomStudentDTO | null  // current active
}>()
const emit = defineEmits<{ select: [action: EnrollmentAction] }>()

const menu = ref()
const items = computed(() => [
  { label: 'ย้ายห้อง (ในปีนี้)', icon: 'pi pi-arrow-right', command: () => emit('select', 'transfer') },
  { label: 'เลื่อนชั้น (ปีถัดไป)', icon: 'pi pi-arrow-up', command: () => emit('select', 'promote') },
  { separator: true },
  { label: 'จบการศึกษา', icon: 'pi pi-graduation-cap', command: () => emit('select', 'graduate') },
  { label: 'ซ้ำชั้น', icon: 'pi pi-refresh', command: () => emit('select', 'repeat') },
  { label: 'ลาออก / พ้นสภาพ', icon: 'pi pi-times-circle', command: () => emit('select', 'drop'), class: 'text-red-500' },
])
</script>
```

### 5. Component: `StudentStatusActionModal.vue`

Universal modal — render form ตาม `action` prop:

```vue
<template>
  <Dialog
    :visible="modelValue"
    @update:visible="emit('update:modelValue', $event)"
    :header="title"
    modal
    :style="{ width: '480px' }"
    :draggable="false"
    :closable="!isLoading"
  >
    <!-- Student summary -->
    <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-900 rounded">
      <div class="font-medium">{{ student.first_name_th }} {{ student.last_name_th }}</div>
      <div class="text-sm text-zinc-500">รหัส {{ student.student_id }} · {{ enrollment?.classroom?.display_name ?? 'ไม่มีห้อง' }}</div>
    </div>

    <!-- Action-specific form -->
    <div class="space-y-3">
      <!-- Graduate / Drop -->
      <div v-if="['graduate', 'drop'].includes(action)">
        <label class="block text-sm mb-1">เหตุผล {{ action === 'drop' ? '*' : '' }}</label>
        <InputText v-model="form.reason" class="w-full" :placeholder="action === 'graduate' ? 'จบการศึกษา (default)' : 'ระบุเหตุผล'" />
        <small v-if="errors.reason" class="text-red-500">{{ errors.reason }}</small>

        <label class="block text-sm mt-3 mb-1">วันที่มีผล (optional)</label>
        <Calendar v-model="form.effective_at" dateFormat="yy-mm-dd" :maxDate="new Date()" class="w-full" />
      </div>

      <!-- Repeat -->
      <div v-if="action === 'repeat'">
        <label class="block text-sm mb-1">ห้องใหม่ (ระดับเดียวกัน) *</label>
        <Select v-model="form.new_classroom_id" :options="sameGradeClassrooms" optionLabel="display_name" optionValue="id" class="w-full" />
        <small v-if="errors.new_classroom_id" class="text-red-500">{{ errors.new_classroom_id }}</small>

        <label class="block text-sm mt-3 mb-1">เลขที่ใหม่ (optional)</label>
        <InputNumber v-model="form.student_number" :min="1" class="w-full" />
      </div>

      <!-- Promote / Transfer -->
      <div v-if="['promote', 'transfer'].includes(action)">
        <label class="block text-sm mb-1">ห้องปลายทาง *</label>
        <Select
          v-model="form.to_classroom_id"
          :options="action === 'promote' ? nextYearClassrooms : sameYearOtherClassrooms"
          optionLabel="display_name" optionValue="id" class="w-full"
        />
        <small v-if="errors.to_classroom_id" class="text-red-500">{{ errors.to_classroom_id }}</small>

        <label class="block text-sm mt-3 mb-1">เหตุผล (optional)</label>
        <InputText v-model="form.reason" class="w-full" />
      </div>
    </div>

    <template #footer>
      <Button label="ยกเลิก" text :disabled="isLoading" @click="emit('update:modelValue', false)" />
      <Button :label="confirmLabel" :loading="isLoading" :severity="severity" @click="submit" />
    </template>
  </Dialog>
</template>
```

แยก logic ใน `<script setup>`:
- `form` reactive state รีเซ็ตเมื่อ action เปลี่ยน
- `errors` รับจาก API 422 response แล้ว map field-level
- `submit()` เรียก `execute(action, studentId, payload)` + emit success/error
- `nextYearClassrooms`, `sameGradeClassrooms` derive จาก prop `availableClassrooms`

### 6. Component: `StudentStatusBadge.vue`

```vue
<template>
  <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', colorClass]">
    <Icon :icon="iconName" class="mr-1" />
    {{ statusText }}
  </span>
</template>

<script setup lang="ts">
const props = defineProps<{ status: string; statusText?: string }>()
const map: Record<string, { color: string; icon: string }> = {
  active:      { color: 'bg-green-100 text-green-700',   icon: 'mdi:account-check' },
  transferred: { color: 'bg-blue-100 text-blue-700',     icon: 'mdi:arrow-right-bold' },
  promoted:    { color: 'bg-indigo-100 text-indigo-700', icon: 'mdi:arrow-up-bold' },
  graduated:   { color: 'bg-purple-100 text-purple-700', icon: 'mdi:school' },
  dropped:     { color: 'bg-red-100 text-red-700',       icon: 'mdi:close-circle' },
  repeating:   { color: 'bg-amber-100 text-amber-700',   icon: 'mdi:refresh' },
  superseded:  { color: 'bg-zinc-100 text-zinc-500',     icon: 'mdi:archive' },
}
const colorClass = computed(() => map[props.status]?.color ?? 'bg-zinc-100 text-zinc-500')
const iconName = computed(() => map[props.status]?.icon ?? 'mdi:help-circle')
</script>
```

### 7. Component: `EnrollmentHistoryDrawer.vue`

Slide-out drawer แสดง timeline ประวัติของนักเรียน:

```vue
<template>
  <Sidebar v-model:visible="visible" position="right" :style="{ width: '420px' }">
    <template #header>
      <h3 class="font-semibold">ประวัติการลงห้อง — {{ student?.first_name_th }} {{ student?.last_name_th }}</h3>
    </template>

    <div v-if="loading">กำลังโหลด…</div>
    <div v-else-if="!rows.length" class="text-zinc-500">ยังไม่มีประวัติ</div>

    <ol v-else class="relative border-l-2 border-zinc-200 dark:border-zinc-700 ml-3 space-y-4">
      <li v-for="row in rows" :key="row.id" class="ml-4">
        <div class="absolute -left-2 w-3 h-3 rounded-full" :class="dotColor(row.status)" />
        <div class="text-sm font-medium">
          {{ row.classroom?.display_name ?? 'ห้องที่ลบไปแล้ว' }}
          <StudentStatusBadge :status="row.status" :status-text="row.status_text" />
        </div>
        <div class="text-xs text-zinc-500">
          {{ row.enrolled_at }} → {{ row.left_at ?? 'ปัจจุบัน' }}
        </div>
        <div v-if="row.leave_reason" class="text-xs text-zinc-600 mt-1">
          เหตุผล: {{ row.leave_reason }}
        </div>
      </li>
    </ol>
  </Sidebar>
</template>
```

ใช้ `fetchHistory()` จาก composable

### 8. Page integration: `classrooms/[id].vue`

เพิ่ม:
- **2 Tabs**: "กำลังศึกษา (active)" | "ออกจากห้อง (transferred/promoted/graduated/dropped/repeating)"
- ใน active tab: เพิ่ม column "จัดการ" ที่ใส่ `<StudentActionMenu>` ทุกแถว
- ทุก student row คลิกได้ → เปิด `<EnrollmentHistoryDrawer>`
- Modal `<StudentStatusActionModal>` controlled โดย parent state `currentAction` + `currentStudent`

ตัวอย่าง state:
```typescript
const currentAction = ref<EnrollmentAction | null>(null)
const currentStudent = ref<StudentSummaryDTO | null>(null)
const currentEnrollment = ref<ClassroomStudentDTO | null>(null)
const showActionModal = computed({ get: () => !!currentAction.value, set: v => { if (!v) currentAction.value = null } })

function onActionSelect(row, action: EnrollmentAction) {
  currentStudent.value = row.student
  currentEnrollment.value = row
  currentAction.value = action
}

const { execute, isLoading } = useStudentEnrollmentActions(academyId)

async function onModalSubmit(payload: ActionPayload) {
  try {
    await execute(currentAction.value!, currentStudent.value!.id, payload)
    useToast().add({ severity: 'success', summary: 'สำเร็จ', detail: 'อัปเดทสถานะนักเรียนเรียบร้อย', life: 3000 })
    currentAction.value = null
    await refreshStudents()  // refetch
  } catch (err: any) {
    const msg = err?.data?.message ?? 'การดำเนินการล้มเหลว'
    useToast().add({ severity: 'error', summary: 'ผิดพลาด', detail: msg, life: 5000 })
  }
}
```

### 9. Tests (frontend) — น้อยกว่า backend เพราะ heavy UI

Phase 4 backend tests มีครอบคลุมแล้ว (74+); ฝั่ง FE focus:

| # | Test | Type |
|---|---|---|
| F1 | `useStudentEnrollmentActions.execute('graduate', ...)` ส่ง POST ที่ url ถูก + body ถูก | Vitest unit |
| F2 | `useStudentEnrollmentActions.fetchHistory` ส่ง GET ที่ url ถูก | Vitest unit |
| F3 | `StudentStatusBadge` render สีถูกตาม status (snapshot 7 statuses) | Vitest component |

**ไม่ทำ E2E browser test ใน Phase 4** — manual smoke แทน

### 10. ลำดับ commit Phase 4 (3 commits)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 4.A | feat(ui): enrollment composable + DTO types + status badge | composables/useStudentEnrollmentActions.ts, types/enrollment.ts, components/academy/enrollment/StudentStatusBadge.vue + F1/F2/F3 tests | ~250 |
| 4.B | feat(ui): student action menu + modal + history drawer | StudentActionMenu.vue, StudentStatusActionModal.vue, EnrollmentHistoryDrawer.vue | ~500 |
| 4.C | feat(ui): integrate enrollment actions into classroom detail page | pages/academies/[name]/admin/gradebook/classrooms/[id].vue (modify) | ~200 |

รวม ~950 LOC, ~3 ชม.

### 11. Verification per commit

ทุก commit:
1. `cd ui && npx vue-tsc --noEmit 2>&1 | grep -E '(enrollment|StudentStatus|StudentAction|EnrollmentHistory)'` — เฉพาะไฟล์ใหม่ต้อง clean (existing repo-wide errors ถือว่า pre-existing)
2. `npx vitest run` (ถ้ามีเซ็ตอัพ; ไม่งั้น skip)
3. `npm run dev` smoke + เปิดหน้า classroom detail
4. ทดสอบ 3 viewport: 380px, 800px, 1280px
5. Reduced motion check
6. Pre-commit Vue SFC check (skill `pre-commit-vue`)

End-to-end manual flow (หลัง 4.C):
1. login เป็น admin
2. เปิด `/academies/<name>/admin/gradebook/classrooms/<id>`
3. คลิก ⋯ ของนักเรียน 1 คน → เลือก "ลาออก" → ใส่เหตุผล → submit
4. ตรวจ toast success + รายชื่อ refresh + ย้ายไปแท็บ "ออกจากห้อง"
5. คลิก row → drawer เปิด timeline → เห็น entry "dropped"

### 12. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | API 422 มี field-level errors | composable แปลงเป็น object errors แล้ว modal display per field |
| E2 | API 403 (cross-class teacher) | toast แสดง "ไม่มีสิทธิ์ — ติดต่อ admin" |
| E3 | classroom dropdown ใน modal ว่าง (ไม่มีห้องใน next year) | แสดง warning ใน modal + disable submit |
| E4 | network drop ระหว่าง submit | catch error → toast + form ไม่ปิด, retry ได้ |
| E5 | นักเรียน double-click ⋯ → 2 modal | dropdown menu auto close ตัวเก่าก่อนเปิดใหม่ |
| E6 | SSR crash (skill `debug-ssr`) | ใช้ `<ClientOnly>` ห่อ modal ถ้าใช้ PrimeVue Teleport |
| E7 | dark mode | สี badge map ต้อง support dark (`dark:bg-*`) |
| E8 | i18n EN | placeholder ภาษาไทยตอนนี้ — Phase scope creep หากต้องรองรับ EN; defer |

### 13. Out of scope ของ Phase 4

- ❌ Rollover wizard (Phase 5)
- ❌ Promote ทั้งห้อง (bulk) — single-student only
- ❌ Restore action (undo individual graduate/drop) — ใช้ rollover undo จริง
- ❌ Notification เมื่อ status change (Phase 7)
- ❌ Audit log UI (Phase 8)
- ❌ Mobile-specific UX optimization (drawer ที่ใหญ่ขึ้น, ฯลฯ)

### 14. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| SSR crash จาก PrimeVue Sidebar/Dialog | กลาง | สูง | `<ClientOnly>` ห่อ component; ดู memory [[feedback_ssr_ipc_crash]] |
| Existing repo-wide TS errors บัง errors ใหม่ | สูง | กลาง | grep filter ตามชื่อไฟล์ใหม่เท่านั้น |
| useApi pattern ต่างจากที่คิด — POST signature | กลาง | กลาง | อ่าน useApi.ts ก่อนเขียน composable (ทำใน 4.A) |
| `availableClassrooms` ไม่มี endpoint แยก → ต้อง derive จาก state ที่มีในหน้า | สูง | กลาง | ใน 4.C เพิ่ม load classrooms ของ academy (already done in page?) — ถ้ายังไม่มี ต้องเพิ่ม |
| Frontend test framework ยังไม่ setup | สูง | ต่ำ | Phase 4 ทำ test แค่ unit composable + snapshot badge; manual smoke compensate |
| Toast/Sidebar component import path เปลี่ยนใน PrimeVue 4 | กลาง | ต่ำ | ตรวจ doc + sample code อื่นในโปรเจค |

### 15. Definition of Done

- [ ] 3 commits land
- [ ] 4 components + 1 composable + 1 type file สร้างใหม่
- [ ] หน้า `[id].vue` มี tabs + action menu + modal + history drawer integrated
- [ ] Manual E2E ผ่าน 5 steps (login → action → toast → tab switch → drawer timeline)
- [ ] ไม่กระทบ existing classroom CRUD (add/remove student)
- [ ] SSR ไม่ crash (`npm run dev` + reload หน้า)
- [ ] วัด 3 viewport
- [ ] commit message ระบุ Phase 4.{A,B,C}

### 16. Decisions ที่รอยืนยัน

1. **Modal universal vs separate** — universal (เปลี่ยน form ตาม action) ลด component แต่ logic ใน 1 ที่หนาแน่น; vs 5 modal แยก
   - **Recommendation:** universal — เริ่มก่อน, refactor ถ้ายาก

2. **Tabs split active/inactive** ใน classroom detail
   - แยก: ผู้ใช้ดูง่าย "ใครยังเรียน vs ใครออกแล้ว"
   - ไม่แยก: 1 list ที่ filter ได้
   - **Recommendation:** แยก 2 tabs — UX ชัด

3. **History เป็น Drawer หรือ Modal** — drawer (slide-in) หรือ modal (center)
   - **Recommendation:** Drawer — ไม่ block content เดิม + รู้สึกเหมือน "ดูข้างๆ"

4. **Repeat action ต้อง select ห้องใหม่จากปีเดียวกัน หรือปีถัดไป?** — service ตอนนี้บังคับ `grade_level` ตรง + `id !== current`
   - ปัจจุบัน option: filter dropdown ให้แสดง classrooms ใน same academy + same grade + ห้องอื่น
   - **Recommendation:** filter ตามนี้ + แสดง section bracket "ปี 2568 / 2569"

5. **Toast library** — PrimeVue Toast หรือ custom
   - **Recommendation:** PrimeVue Toast (มีในโปรเจคแล้วน่าจะใช่)
## 2026-06-21 Phase 4.A implementation note

- Scope confirmed: start Phase 4.A only (`composable + DTO types + status badge`) after user approved all five decisions in Phase 4 section 16.
- Confirmed decisions to carry forward:
  - universal action modal
  - split active/inactive tabs
  - history as drawer
  - repeat target filter = same grade + other classroom + grouped by academic year
  - toast direction = PrimeVue Toast
- Codebase findings before edits:
  - Backend lifecycle endpoints are live at `/api/academies/{academy}/students/{student}/{graduate|drop|repeat|promote|transfer}` plus `/enrollment-history-v2`.
  - Lifecycle response contract comes from `App\Http\Resources\Learn\Academy\Enrollment\ClassroomStudentResource` and `StudentSummaryResource`.
  - Frontend already has a project-wide `useToast()` wrapper backed by `ui/stores/notification.ts`, so 4.A should avoid coupling composable logic to any toast implementation yet.
  - `ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue` is still heavily `any`-based and mixes assumptions about `classroom.classroom_students` rows, while `ClassroomController::show()` explicitly assembles `classroom.students`; Phase 4.C will need a careful adapter or page refresh strategy.
  - `ui/package.json` currently has no Vitest setup/script, so Phase 4.A verification should prioritize focused type checks and read-back validation instead of assuming runnable unit tests.
- Intended files for 4.A:
  - `ui/types/enrollment.ts`
  - `ui/composables/useStudentEnrollmentActions.ts`
  - `ui/components/academy/enrollment/StudentStatusBadge.vue`
- Additional risk added:
  - Classroom detail payload shape drift between existing page expectations and lifecycle DTOs may cause friction during 4.C integration if not normalized in page state.
- Verification plan for 4.A:
  - targeted read-back of new files
  - focused `vue-tsc` check if practical, while treating unrelated repo-wide errors as pre-existing

---

## 2026-06-21 Phase 4.B — Action Menu + Modal + History Drawer (Detailed Plan, revised)

### 0. Convention findings (จาก codebase audit)

**ต่างจากแผน Phase 4 §16 เดิม:**

| Item | แผนเดิม | จริงในโปรเจค |
|---|---|---|
| Modal | PrimeVue Dialog | **`@headlessui/vue` Dialog** (5+ ไฟล์ใช้, รวม `QuestionFormModal.vue`) |
| Toast | PrimeVue Toast | **`useToast()` composable** wrap `useNotificationStore` — signature: `success(message, title?, duration?)` |
| Drawer | PrimeVue Sidebar | **`<SidebarDrawer>`** component อยู่ที่ `components/Common/SidebarDrawer.vue` พร้อมใช้ (props: `open`, `side`, `title` + ESC + body scroll lock + iOS compat) |
| Confirm | SweetAlert | `useSweetAlert()` composable มีอยู่ (ใช้สำหรับ destructive confirm ถ้าต้องการ) |
| Dropdown menu | PrimeVue Menu | ตรวจในโปรเจคไม่มี dropdown menu pattern ชัด — สร้าง custom button group หรือ Headless UI Menu |

**Action:**
- ใช้ `@headlessui/vue` + `<SidebarDrawer>` + `useToast` + Tailwind ตามที่โปรเจคใช้จริง
- ไม่ import PrimeVue components ใน Phase 4 (กัน inconsistency)
- Dropdown menu: ใช้ `@headlessui/vue` `Menu` (PopoverMenu pattern) เพราะมีอยู่แล้วใน deps

### 1. หลักการ Phase 4.B

1. **Match convention โปรเจค** (Headless UI + Tailwind + useToast + SidebarDrawer)
2. **3 component files แยก**:
   - `StudentActionMenu.vue` — dropdown ของ 5 actions (Headless UI Menu)
   - `StudentStatusActionModal.vue` — universal modal (Headless UI Dialog)
   - `EnrollmentHistoryDrawer.vue` — wrap `<SidebarDrawer>` ของโปรเจค
3. **Composable ที่มีจาก 4.A ถูกใช้ตรงๆ** (`useStudentEnrollmentActions`)
4. **Error display per-field** — ใช้ `fieldErrors` จาก composable (มีอยู่แล้ว) แสดงใต้ input
5. **A11y** — Headless UI ดูแล focus trap + ESC ให้
6. **Composition over inheritance** — modal รับ `availableClassrooms` prop, ไม่ fetch เอง (parent หน้า [id].vue manage)

### 2. โครงสร้าง 3 components

```
ui/components/academy/enrollment/
├── StudentStatusBadge.vue              (Phase 4.A — exists)
├── StudentActionMenu.vue               (4.B.1)
├── StudentStatusActionModal.vue        (4.B.2)
└── EnrollmentHistoryDrawer.vue         (4.B.3)
```

### 3. `StudentActionMenu.vue` spec

```vue
<script setup lang="ts">
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import type { ClassroomStudentDTO, EnrollmentAction, StudentSummaryDTO } from '~/types/enrollment'

interface Props {
  student: StudentSummaryDTO
  enrollment: ClassroomStudentDTO | null
}
defineProps<Props>()
const emit = defineEmits<{ select: [action: EnrollmentAction] }>()

const items: Array<{ action: EnrollmentAction; label: string; icon: string; tone?: 'danger' }> = [
  { action: 'transfer',  label: 'ย้ายห้อง (ในปีนี้)',     icon: 'mdi:arrow-right-bold' },
  { action: 'promote',   label: 'เลื่อนชั้น (ปีถัดไป)',   icon: 'mdi:arrow-up-bold' },
  { action: 'graduate',  label: 'จบการศึกษา',             icon: 'mdi:school' },
  { action: 'repeat',    label: 'ซ้ำชั้น',                icon: 'mdi:refresh' },
  { action: 'drop',      label: 'ลาออก / พ้นสภาพ',        icon: 'mdi:close-circle', tone: 'danger' },
]
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton
      class="p-1.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
      aria-label="จัดการสถานะนักเรียน"
    >
      <Icon icon="mdi:dots-vertical" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
    </MenuButton>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <MenuItems
        class="absolute right-0 z-30 mt-1 w-56 origin-top-right rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-lg focus:outline-none"
      >
        <div class="py-1">
          <MenuItem v-for="item in items" :key="item.action" v-slot="{ active }">
            <button
              :class="[
                'flex w-full items-center gap-2 px-3 py-2 text-sm transition',
                active ? 'bg-zinc-100 dark:bg-zinc-800' : '',
                item.tone === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-200',
              ]"
              @click="emit('select', item.action)"
            >
              <Icon :icon="item.icon" class="w-4 h-4" />
              <span>{{ item.label }}</span>
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>
```

### 4. `StudentStatusActionModal.vue` spec

**Action-form mapping:**

| action | fields | required |
|---|---|---|
| graduate | reason, effective_at | — |
| drop | reason*, effective_at | reason |
| repeat | new_classroom_id*, student_number, reason | new_classroom_id |
| promote | from_classroom_id (auto), to_classroom_id*, reason, student_number | to_classroom_id |
| transfer | from_classroom_id (auto), to_classroom_id*, reason | to_classroom_id |

**Props:**
```ts
interface Props {
  open: boolean
  action: EnrollmentAction | null
  student: StudentSummaryDTO | null
  enrollment: ClassroomStudentDTO | null      // current active
  availableClassrooms: EnrollmentClassroomSummaryDTO[]  // pre-fetched by parent
  isLoading: boolean                          // from useStudentEnrollmentActions
  fieldErrors: EnrollmentFieldErrors          // from composable
}
defineEmits<{
  'update:open': [v: boolean]
  submit: [payload: EnrollmentActionPayload<EnrollmentAction>]
}>()
```

**Skeleton:**
```vue
<template>
  <TransitionRoot :show="open" as="template">
    <Dialog @close="emit('update:open', false)" class="relative z-50">
      <TransitionChild
        enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100"
        leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 flex items-center justify-center p-4">
        <TransitionChild
          enter="ease-out duration-200" enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-150" leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel class="w-full max-w-md rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-xl">
            <DialogTitle class="text-lg font-semibold mb-4">
              {{ titleMap[action ?? ''] }}
            </DialogTitle>

            <!-- Student summary chip -->
            <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
              <div class="font-medium text-sm">{{ student?.first_name_th }} {{ student?.last_name_th }}</div>
              <div class="text-xs text-zinc-500 mt-0.5">
                รหัส {{ student?.student_id }} ·
                {{ enrollment?.classroom?.display_name ?? 'ไม่มีห้อง' }}
              </div>
            </div>

            <!-- Dynamic form by action -->
            <form @submit.prevent="onSubmit" class="space-y-3">
              <!-- graduate/drop: reason + effective_at -->
              <template v-if="action === 'graduate' || action === 'drop'">
                <FormField
                  label="เหตุผล"
                  :required="action === 'drop'"
                  :error="fieldErrors.reason"
                >
                  <input v-model="form.reason" type="text" maxlength="255"
                    class="form-input"
                    :placeholder="action === 'graduate' ? 'จบการศึกษา (default)' : 'ระบุเหตุผล'"
                  />
                </FormField>

                <FormField label="วันที่มีผล (optional)" :error="fieldErrors.effective_at">
                  <input v-model="form.effective_at" type="date" :max="todayIso" class="form-input" />
                </FormField>
              </template>

              <!-- repeat -->
              <template v-else-if="action === 'repeat'">
                <FormField label="ห้องใหม่ (ระดับเดียวกัน)" required :error="fieldErrors.new_classroom_id">
                  <select v-model="form.new_classroom_id" class="form-input">
                    <option :value="null">-- เลือกห้อง --</option>
                    <option v-for="c in sameGradeClassrooms" :key="c.id" :value="c.id">
                      {{ c.display_name }}
                    </option>
                  </select>
                  <p v-if="!sameGradeClassrooms.length" class="text-xs text-amber-600 mt-1">
                    ไม่มีห้องระดับเดียวกันในระบบ
                  </p>
                </FormField>

                <FormField label="เลขที่ใหม่ (optional)" :error="fieldErrors.student_number">
                  <input v-model.number="form.student_number" type="number" min="1" class="form-input" />
                </FormField>

                <FormField label="เหตุผล (optional)" :error="fieldErrors.reason">
                  <input v-model="form.reason" type="text" maxlength="255" class="form-input" />
                </FormField>
              </template>

              <!-- promote/transfer -->
              <template v-else-if="action === 'promote' || action === 'transfer'">
                <FormField label="ห้องปลายทาง" required :error="fieldErrors.to_classroom_id">
                  <select v-model="form.to_classroom_id" class="form-input">
                    <option :value="null">-- เลือกห้อง --</option>
                    <option v-for="c in targetClassrooms" :key="c.id" :value="c.id">
                      {{ c.display_name }}
                    </option>
                  </select>
                </FormField>

                <FormField label="เหตุผล (optional)" :error="fieldErrors.reason">
                  <input v-model="form.reason" type="text" maxlength="255" class="form-input" />
                </FormField>
              </template>

              <!-- Footer -->
              <div class="flex justify-end gap-2 pt-2 mt-2 border-t border-zinc-200 dark:border-zinc-700">
                <button type="button" :disabled="isLoading"
                  class="btn-secondary" @click="emit('update:open', false)">
                  ยกเลิก
                </button>
                <button type="submit" :disabled="isLoading || !canSubmit"
                  :class="['btn-primary', action === 'drop' ? 'btn-danger' : '']">
                  <Icon v-if="isLoading" icon="mdi:loading" class="w-4 h-4 animate-spin mr-1" />
                  {{ confirmLabel }}
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
```

**Logic:**
- `form` rebuilds เมื่อ `action` หรือ `open` เปลี่ยน
- `sameGradeClassrooms` filter จาก `availableClassrooms` ที่ `grade_level === enrollment.classroom.grade_level && id !== enrollment.classroom_id`
- `targetClassrooms`:
  - `promote`: ทุก classroom ที่ `id !== enrollment.classroom_id` (cross-year) — สามารถ group label ด้วย "ปี YYYY"
  - `transfer`: filter `grade_level === enrollment.classroom.grade_level && id !== enrollment.classroom_id` (same year same grade)
- `canSubmit` enforce required field
- `onSubmit` emit `submit(payload)` parent จัดการเรียก composable
- `titleMap`: `{ graduate: 'จบการศึกษา', drop: 'ลาออก / พ้นสภาพ', ... }`
- `confirmLabel`: `{ graduate: 'ยืนยันจบการศึกษา', drop: 'ยืนยันการพ้นสภาพ', ... }`
- `FormField` ใช้ component ที่มีอยู่ `components/Common/FormField.vue` (ดูใน untracked list)

**Decision: ใช้ `FormField` ของโปรเจค** (อยู่ใน untracked แต่ตั้งใจเก็บใน FE — ตรวจ shape ก่อนใช้)

### 5. `EnrollmentHistoryDrawer.vue` spec

```vue
<script setup lang="ts">
import { computed, watch } from 'vue'
import SidebarDrawer from '~/components/Common/SidebarDrawer.vue'
import StudentStatusBadge from './StudentStatusBadge.vue'
import { useStudentEnrollmentActions } from '~/composables/useStudentEnrollmentActions'
import type { ClassroomStudentDTO, MaybeEnrollmentAcademyId, StudentSummaryDTO } from '~/types/enrollment'

interface Props {
  open: boolean
  academyId: MaybeEnrollmentAcademyId
  student: StudentSummaryDTO | null
}

const props = defineProps<Props>()
const emit = defineEmits<{ 'update:open': [v: boolean] }>()

const { fetchHistory, isLoading } = useStudentEnrollmentActions(toRef(props, 'academyId'))
const rows = ref<ClassroomStudentDTO[]>([])
const error = ref<string | null>(null)

async function load() {
  if (!props.student) return
  error.value = null
  try {
    rows.value = await fetchHistory(props.student.id)
  } catch (e: any) {
    error.value = e?.data?.message ?? 'โหลดประวัติไม่สำเร็จ'
    rows.value = []
  }
}

watch(() => [props.open, props.student?.id], ([open]) => { if (open) load() })
</script>

<template>
  <SidebarDrawer
    :open="open"
    side="right"
    :title="`ประวัติการลงห้อง — ${student?.first_name_th ?? ''} ${student?.last_name_th ?? ''}`"
    @update:open="emit('update:open', $event)"
  >
    <div class="p-4 space-y-3">
      <div v-if="isLoading" class="text-sm text-zinc-500">กำลังโหลด...</div>
      <div v-else-if="error" class="text-sm text-red-600">{{ error }}</div>
      <div v-else-if="!rows.length" class="text-sm text-zinc-500">ยังไม่มีประวัติการลงห้อง</div>

      <ol v-else class="relative border-l-2 border-zinc-200 dark:border-zinc-700 ml-3 space-y-4">
        <li v-for="row in rows" :key="row.id" class="ml-4 relative">
          <span
            class="absolute -left-[1.4rem] top-1 w-3 h-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
            :class="row.status === 'active' ? 'bg-green-500' : 'bg-zinc-400'"
          />
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-medium text-sm">
              {{ row.classroom?.display_name ?? 'ห้องที่ไม่มีอยู่แล้ว' }}
            </span>
            <StudentStatusBadge :status="row.status" :status-text="row.status_text" />
          </div>
          <div class="text-xs text-zinc-500 mt-0.5">
            <span>{{ row.enrolled_at ?? '?' }}</span>
            <span class="mx-1">→</span>
            <span>{{ row.left_at ?? 'ปัจจุบัน' }}</span>
          </div>
          <div v-if="row.leave_reason" class="text-xs text-zinc-600 dark:text-zinc-300 mt-1 italic">
            {{ row.leave_reason }}
          </div>
        </li>
      </ol>
    </div>
  </SidebarDrawer>
</template>
```

### 6. Required `availableClassrooms` source

Parent หน้า [classrooms/[id].vue](ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue) ต้อง fetch list ของ classrooms ใน academy:

```ts
const availableClassrooms = ref<EnrollmentClassroomSummaryDTO[]>([])

async function loadAvailableClassrooms() {
  const res = await api.get<any>(`/api/academies/${academyId.value}/classrooms`)
  availableClassrooms.value = (res.data ?? []).map((c: any) => ({
    id: c.id,
    display_name: c.name,
    grade_level: c.grade_level,
    section: c.section,
  }))
}
```

**Verify endpoint exists:** `/api/academies/{academy}/classrooms` ใน `ClassroomController::index` — ถ้าไม่มีต้อง derive จาก existing state ของหน้า

### 7. ลำดับ commit Phase 4.B (1 commit)

| # | Subject | ไฟล์ | LOC |
|---|---|---|---|
| 4.B | feat(ui): student action menu + universal status modal + history drawer | StudentActionMenu.vue, StudentStatusActionModal.vue, EnrollmentHistoryDrawer.vue | ~500 |

ไม่แยก 3 sub-commits เพราะ component ขนาดเล็ก-กลาง + integrate ใน 4.C จะ verify จริงจัง

### 8. Verification per commit

1. `npx vue-tsc --noEmit 2>&1 | grep -E '(StudentActionMenu|StudentStatusActionModal|EnrollmentHistoryDrawer)'` — ต้อง clean
2. Visual smoke ใน 4.C (component standalone ไม่เปิดได้นอกหน้าจริง)
3. SSR check: `npm run dev` ต้องไม่ crash — Headless UI ปลอดภัยกับ SSR แต่ check ตาม memory [[feedback_ssr_ipc_crash]]
4. Pre-commit Vue SFC check (skill `pre-commit-vue`)

### 9. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | `availableClassrooms` ว่าง (ห้องยังไม่ถูกสร้างปีใหม่) | modal แสดง "ไม่มีห้องเป้าหมาย" + disable submit |
| E2 | enrollment เป็น `null` (นักเรียนใหม่ยังไม่อยู่ห้อง) | actions graduate/drop/repeat ใช้ไม่ได้ — UI แสดง action ที่เป็นไปได้เท่านั้น; promote/transfer ต้องการ enrollment เพื่อรู้ `grade_level` |
| E3 | user double-submit | `isLoading` disable submit ปุ่ม + close button |
| E4 | API 422 multiple field errors | composable แปลง errors → display ใต้ field |
| E5 | API 403 (cross-class teacher) | parent catch → toast.error ด้วย `getErrorMessage()` |
| E6 | API 404 (cross-academy student) | parent catch → toast.error |
| E7 | dark mode | Tailwind dark: variants ครบทุก color |
| E8 | mobile <480px | modal `max-w-md` + scroll inside if content tall |
| E9 | SidebarDrawer + Dialog z-index ชน | Dialog z-50 อยู่บนสุดเสมอ; Drawer ของโปรเจคใช้ z สูง check |

### 10. Out of scope ของ 4.B

- ❌ Page integration (Phase 4.C)
- ❌ Tabs split (Phase 4.C)
- ❌ Refresh logic after action (Phase 4.C)
- ❌ Bulk select (single student only)
- ❌ Loading skeleton สำหรับ history (รอบหน้า)

### 11. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| `FormField` component ของโปรเจคยังไม่ commit (untracked) | กลาง | กลาง | ตรวจก่อน 4.B; ถ้าไม่ commit → ใช้ Tailwind inline label+input pattern |
| `/api/academies/{id}/classrooms` endpoint ไม่มี / shape ต่าง | กลาง | กลาง | ตรวจ `ClassroomController` ก่อน 4.C; fallback เปลี่ยน path |
| SSR crash จาก Headless UI Transition | ต่ำ | สูง | Headless UI v1.7+ SSR-safe; ถ้าเกิด ห่อ `<ClientOnly>` |
| Drawer + Dialog focus trap ขัดกัน | กลาง | ต่ำ | เปิด Drawer หลัง Modal ปิด เท่านั้น |
| dropdown menu position ผิด on mobile | ต่ำ | ต่ำ | use `right-0` + scroll container check |

### 12. Definition of Done — Phase 4.B

- [ ] 3 components ใหม่
- [ ] TS check clean สำหรับไฟล์ใหม่
- [ ] Vue SFC check ผ่าน (skill `pre-commit-vue`)
- [ ] ไม่ใช้ PrimeVue Dialog/Sidebar/Toast ใน Phase 4 ไฟล์ใหม่ (consistency)
- [ ] commit message ระบุ Phase 4.B
- [ ] Phase 4.A composable + types ถูกใช้จริงในทั้ง 3 components

### 13. Decisions ที่รอยืนยัน

1. **`FormField` component**: ใช้ของโปรเจค (untracked) หรือ inline Tailwind?
   - **Recommendation:** **inline Tailwind** ใน 4.B (กัน dependency กับ untracked) — refactor ใช้ FormField ทีหลังถ้า commit แล้ว

2. **`/api/academies/{academy}/classrooms` endpoint** vs derive จาก state
   - **Recommendation:** ใช้ endpoint (มีอยู่แล้วใน `ClassroomController` ตามที่ Phase 0 audit) — verify path ก่อน 4.C

3. **Repeat: same year only หรือ next year ก็ได้?**
   - Backend `repeatStudent` ไม่บังคับ same year — แค่ same grade + different id
   - **Recommendation:** UI filter `same grade + ทุกปี` แสดง bracket "ปี YYYY"

4. **Modal submit แสดง confirm dialog ซ้อนสำหรับ destructive (drop)?**
   - **Recommendation:** **ไม่** — confirm step คือ submit button เอง; ปุ่ม drop สีแดงเพียงพอ

5. **Drawer history scroll behavior**
   - **Recommendation:** scrollable list inside drawer (overflow-y-auto + max-height)

---

## 2026-06-21 Phase 5 — Academic Year Rollover Wizard (Detailed Plan)

### 0. State at start of Phase 5

จาก Phase 0–4 commit แล้ว 10 commits:
- ✅ Backend API ครบ (Phase 3): preview/plan/commit/undo/closeUndo + 6 per-student lifecycle endpoints
- ✅ Plan caching pattern: `rollover_plan:{uuid}:user:{user_id}` TTL 15 นาที
- ✅ FormRequests + Policy + Resources พร้อม
- ✅ FE composable + DTO + components (Phase 4): StatusBadge, ActionMenu, StatusActionModal, HistoryDrawer + classroom page integration
- ✅ `useStudentEnrollmentActions` ยังไม่มี method สำหรับ rollover endpoint — Phase 5 จะเพิ่ม `useRolloverActions`
- ✅ Decision §15 lock ครบ — confirm พิมพ์ชื่อปี, undo 24 ชม., file cache, trim only

Endpoints available (verified):
| Endpoint | Source |
|---|---|
| `GET /api/academies/{academy}/academic-years` | gradebook routes line 45 |
| `POST /api/academies/{academy}/academic-years` | line 46 |
| `GET /api/academies/{academy}/academic-years/current` | line 47 |
| `GET /api/academies/{academy}/classrooms` | line 56 |
| `POST /api/academies/{academy}/classrooms` | line 57 |
| `POST /api/academies/{academy}/rollover/preview` | Phase 3.D |
| `POST /api/academies/{academy}/rollover/plan` | Phase 3.D |
| `POST /api/academies/{academy}/rollover/commit` | Phase 3.E |
| `GET /api/academies/{academy}/rollover/batches` | Phase 3.D |
| `POST /api/academies/{academy}/rollover/batches/{batch}/undo` | Phase 3.E |
| `POST /api/academies/{academy}/rollover/batches/{batch}/close-undo` | Phase 3.E |

Preflight (Phase 0 finding) reminds:
- ปัจจุบันมี **1 academic_year (2568)** + 51 classrooms
- มี **476 pending intake students** ยังไม่ enroll (ม.1 = 360, ม.4 = 116) → wizard ต้อง surface
- Active enrollments 1929 จะถูก rollover ครั้งใหญ่ครั้งแรก

### 1. หลักการ Phase 5

1. **Wizard 5 steps** — ตามแผน master §3 Phase 5, ทุก step navigable forward/backward (ยกเว้น commit ที่ irreversible)
2. **State centralized ใน 1 page** — ไม่ใช้ Pinia store เพราะ wizard scope ภายในหน้าเดียว; ใช้ `provide/inject` ถ้า step component ลึก
3. **Auto-save mapping state ใน sessionStorage** — กัน accidental refresh ทำให้เริ่มใหม่
4. **Plan caching client-side แสดง countdown 15 นาที** — sync กับ backend TTL
5. **Commit irreversible UX** — confirm ขั้นพิมพ์ชื่อปี + button disabled จนกว่า exact match
6. **Post-commit screen แสดง undo button + countdown 24 ชม.** — auto refresh isUndoable status ทุก 60 วินาที
7. **Component แยกตาม step** — แต่ละ step เป็น SFC แยก, ลด LOC ต่อไฟล์
8. **A11y พื้นฐาน** — keyboard nav step navigator + announce success/error ผ่าน aria-live
9. **ไม่ใช้ Pinia** — wizard state อายุสั้น, ไม่ shared
10. **ไม่กระทบหน้าอื่น** — Phase 5 ทำหน้าใหม่ทั้งหมด ไม่แตะ Phase 4

### 2. โครงสร้างไฟล์

```
ui/
├── composables/
│   └── useRolloverActions.ts                          (5.A)
├── types/
│   └── enrollment.ts                                  (5.A — extend with rollover DTOs)
├── components/
│   └── academy/
│       └── rollover/
│           ├── RolloverStepIndicator.vue              (5.B) — header step nav
│           ├── RolloverYearPicker.vue                 (5.C step 1)
│           ├── RolloverClassroomChecklist.vue         (5.C step 2)
│           ├── RolloverStudentBucket.vue              (5.D step 3 — drag/drop or multiselect)
│           ├── RolloverPreviewSummary.vue             (5.E step 4)
│           ├── RolloverCommitPanel.vue                (5.F step 5)
│           └── RolloverUndoBanner.vue                 (5.G post-commit)
└── pages/
    └── academies/[name]/admin/gradebook/rollover/
        └── index.vue                                  (5.H — wizard shell)
```

### 3. Sub-phase commits (8 commits, ~5 ชม.)

| # | Subject | ไฟล์หลัก | LOC | เวลา |
|---|---|---|---|---|
| 5.A | feat(ui): rollover composable + extend types | useRolloverActions.ts, types/enrollment.ts | ~180 | 30 นาที |
| 5.B | feat(ui): rollover step indicator | RolloverStepIndicator.vue | ~80 | 15 นาที |
| 5.C | feat(ui): step 1+2 — year picker + classroom checklist | RolloverYearPicker.vue, RolloverClassroomChecklist.vue | ~280 | 45 นาที |
| 5.D | feat(ui): step 3 — student bucket assignment | RolloverStudentBucket.vue | ~350 | 60 นาที |
| 5.E | feat(ui): step 4 — preview summary | RolloverPreviewSummary.vue | ~150 | 30 นาที |
| 5.F | feat(ui): step 5 — commit panel with confirm gate | RolloverCommitPanel.vue | ~180 | 30 นาที |
| 5.G | feat(ui): post-commit undo banner | RolloverUndoBanner.vue | ~120 | 20 นาที |
| 5.H | feat(ui): rollover wizard page integration + sessionStorage | pages/.../rollover/index.vue | ~350 | 50 นาที |

**รวม ~1690 LOC, ~5 ชม.**

### 4. `useRolloverActions` (5.A) composable spec

```typescript
// ui/composables/useRolloverActions.ts
import type {
  ClassroomOptionDTO,
  RolloverBatchDTO,
  RolloverEntry,
  RolloverPlanResponse,
  RolloverPreviewResponse,
  MaybeEnrollmentAcademyId,
} from '~/types/enrollment'

export function useRolloverActions(academyId: MaybeEnrollmentAcademyId) {
  const api = useApi()
  const isLoading = ref(false)
  const lastError = ref<ApiError | null>(null)

  // helpers
  function url(path: string): string { /* ... */ }

  async function preview(fromYearId: number, toYearId: number): Promise<RolloverPreviewResponse>
  async function plan(fromYearId: number, toYearId: number, mapping: RolloverEntry[]): Promise<RolloverPlanResponse>
  async function commit(planId: string, confirmText: string): Promise<{ batch: RolloverBatchDTO }>
  async function undo(batchId: string, reason?: string): Promise<{ batch: RolloverBatchDTO }>
  async function closeUndo(batchId: string): Promise<{ batch: RolloverBatchDTO }>
  async function listBatches(page?: number): Promise<{ data: RolloverBatchDTO[]; meta: { /* pagination */ } }>
  async function fetchBatch(batchId: string): Promise<{ batch: RolloverBatchDTO }>

  // utility
  async function fetchAcademicYears(): Promise<AcademicYearDTO[]>
  async function fetchClassroomsByYear(yearId: number): Promise<ClassroomOptionDTO[]>

  return { preview, plan, commit, undo, closeUndo, listBatches, fetchBatch,
           fetchAcademicYears, fetchClassroomsByYear, isLoading, lastError }
}
```

### 5. Types extension (5.A)

เพิ่มใน `types/enrollment.ts`:

```typescript
export interface AcademicYearDTO {
  id: number
  academy_id: number
  name: string
  start_date: string | null
  end_date: string | null
  is_current: boolean
}

export interface RolloverEntry {
  student_id: number
  action: 'promote' | 'graduate' | 'drop' | 'repeat' | 'new_intake' | 'skip'
  from_classroom_id?: number | null
  to_classroom_id?: number | null
  reason?: string
}

export interface RolloverSuggestedEntry extends RolloverEntry {
  student?: StudentSummaryDTO
  from_classroom?: ClassroomOptionDTO
  to_classroom?: ClassroomOptionDTO
  suggested_reason?: string
}

export interface RolloverPreviewResponse {
  success: boolean
  preview: {
    suggested_mapping: RolloverSuggestedEntry[]
    missing_targets: Array<{ grade_level: string; section: string }>
    totals: {
      promote: number; graduate: number; repeat: number;
      drop: number; new_intake: number; skip: number
    }
    warnings: string[]
  }
}

export interface RolloverPlanResponse {
  success: boolean
  plan_id: string
  expires_in_seconds: number
  summary: Record<string, any>
  warnings: string[]
  entries_count: number
}

export interface RolloverBatchDTO {
  id: string
  academy_id: number
  from_year: { id: number; name: string } | null
  to_year: { id: number; name: string } | null
  status: 'committed' | 'undone'
  committed_at: string | null
  committed_by: { id: number; name: string } | null
  undo_closed_at: string | null
  undone_at: string | null
  undone_by: { id: number; name: string } | null
  is_undoable: boolean
  undo_expires_at: string | null
  totals: Record<string, number> | null
  plan_summary: Record<string, any> | null
  notes: string | null
}
```

### 6. Step-by-step UI spec

#### Step 1 — Year Picker (`RolloverYearPicker.vue`, 5.C)

```vue
<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold mb-1">เลือกปีการศึกษา</h2>
      <p class="text-sm text-zinc-500">ระบบจะนำนักเรียนจาก "ปีต้นทาง" มาเข้าห้องของ "ปีปลายทาง"</p>
    </div>

    <!-- From year (existing years) -->
    <select v-model="fromYearId" class="form-input">
      <option v-for="y in academicYears" :key="y.id" :value="y.id">
        {{ y.name }} {{ y.is_current ? '(ปัจจุบัน)' : '' }}
      </option>
    </select>

    <!-- To year: existing OR create new -->
    <div class="space-y-2">
      <label class="flex items-center gap-2">
        <input type="radio" v-model="toMode" value="existing" />
        <span>เลือกปีที่มีอยู่</span>
      </label>
      <select v-if="toMode === 'existing'" v-model="toYearId" class="form-input">
        <option v-for="y in academicYears.filter(y => y.id !== fromYearId)" :key="y.id" :value="y.id">
          {{ y.name }}
        </option>
      </select>

      <label class="flex items-center gap-2 mt-3">
        <input type="radio" v-model="toMode" value="create" />
        <span>สร้างปีใหม่</span>
      </label>
      <div v-if="toMode === 'create'" class="space-y-2 ml-6">
        <input v-model="newYear.name" placeholder="เช่น 2569" class="form-input" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="newYear.start_date" type="date" class="form-input" />
          <input v-model="newYear.end_date" type="date" class="form-input" />
        </div>
        <button @click="createNewYear" :disabled="isCreating" class="btn-primary">
          สร้างปี {{ newYear.name }}
        </button>
      </div>
    </div>

    <div class="flex justify-end">
      <button @click="emit('next')" :disabled="!canNext" class="btn-primary">
        ถัดไป →
      </button>
    </div>
  </div>
</template>
```

#### Step 2 — Classroom Checklist (`RolloverClassroomChecklist.vue`, 5.C)

ตรวจห้องของปีปลายทาง:
- แสดง grade_level ที่ต้องการ (จาก preview suggested mapping)
- ถ้าขาดห้องไหน → แสดง warning + ปุ่ม "สร้างห้องอัตโนมัติ" (clone จากปี source แต่เปลี่ยน academic_year_id)
- ปุ่ม "ถัดไป" enabled เมื่อทุก grade_level ที่ต้องการมีห้องครบ

```vue
<div v-for="grade in requiredGrades" :key="grade.level"
     class="flex items-center justify-between p-3 rounded border">
  <span>{{ grade.label }} — {{ grade.studentCount }} นักเรียน</span>
  <div v-if="grade.classrooms.length > 0" class="flex gap-1">
    <StudentStatusBadge v-for="c in grade.classrooms" :key="c.id" status="active" :status-text="c.display_name" />
  </div>
  <button v-else @click="autoCreate(grade)" class="btn-secondary text-sm">
    สร้างห้องอัตโนมัติ ({{ grade.suggestedSections }})
  </button>
</div>
```

#### Step 3 — Student Bucket Assignment (`RolloverStudentBucket.vue`, 5.D)

- รับ `suggestedMapping` จาก preview
- แสดง 5 buckets:
  - 🎓 **จบการศึกษา** (graduate)
  - ⬆️ **เลื่อนชั้น** (promote) — แสดงห้องต่อชั้น
  - 🔄 **ซ้ำชั้น** (repeat)
  - ❌ **ลาออก** (drop)
  - 🆕 **นักเรียนใหม่รอเข้า** (new_intake) — แสดง 476 pending intake
- แต่ละ entry คลิกได้ → popup เลือก action + target
- Search bar + filter by current classroom
- Virtualized list ถ้า > 500 entries (ใช้ `@tanstack/vue-virtual` ถ้าไม่มีติดตั้ง → fallback pagination 100/page)
- Counter ด้านบน: "จัดสรรแล้ว X / Y คน" — ห้ามไป step 4 ถ้ายังไม่ครบ 100%

```vue
<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Left: filter + unassigned list -->
    <div class="lg:col-span-1 space-y-2">
      <input v-model="search" placeholder="ค้นหา..." class="form-input" />
      <select v-model="filterFromClassroom" class="form-input">
        <option :value="null">ทุกห้องต้นทาง</option>
        <option v-for="c in fromClassrooms" :key="c.id" :value="c.id">
          {{ c.display_name }}
        </option>
      </select>
      <div class="rounded border max-h-[60vh] overflow-y-auto divide-y">
        <div v-for="entry in filteredEntries" :key="entry.student_id"
             :class="['p-2 cursor-pointer hover:bg-zinc-50', selected.has(entry.student_id) ? 'bg-indigo-50' : '']"
             @click="toggleSelect(entry.student_id)">
          <div class="text-sm">{{ entry.student?.first_name_th }} {{ entry.student?.last_name_th }}</div>
          <div class="text-xs text-zinc-500">{{ entry.from_classroom?.display_name ?? '(ใหม่)' }}</div>
        </div>
      </div>
      <div class="text-xs text-zinc-500">เลือก {{ selected.size }} คน</div>
    </div>

    <!-- Right: 5 buckets -->
    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3">
      <RolloverBucketCard v-for="bucket in buckets" :key="bucket.action"
        :bucket="bucket" :selected-count="selected.size"
        @assign="assignSelectedTo(bucket.action, $event)" />
    </div>
  </div>

  <!-- Footer -->
  <div class="mt-6 flex items-center justify-between border-t pt-4">
    <div class="text-sm">
      จัดสรรแล้ว {{ assignedCount }} / {{ totalCount }} คน
      <span v-if="unassignedCount > 0" class="text-amber-600">
        (เหลือ {{ unassignedCount }} คน)
      </span>
    </div>
    <div class="flex gap-2">
      <button @click="emit('back')" class="btn-secondary">← ย้อนกลับ</button>
      <button @click="emit('next')" :disabled="unassignedCount > 0" class="btn-primary">ถัดไป →</button>
    </div>
  </div>
</template>
```

#### Step 4 — Preview Summary (`RolloverPreviewSummary.vue`, 5.E)

- เรียก `plan` API ส่ง mapping ที่จัดสรรแล้ว
- รับ `plan_id` + summary + warnings + entries_count
- แสดง:
  - Totals card 5 ตัว (promoted/graduated/dropped/repeating/new_intake)
  - Warnings list (ถ้ามี)
  - Sample 10 entries จากแต่ละ bucket (collapsible)
  - "plan_id หมดอายุใน 14:59" countdown
- ปุ่ม "ย้อนกลับ" / "ดำเนินการ commit →"

#### Step 5 — Commit Panel (`RolloverCommitPanel.vue`, 5.F)

- แสดงสรุปสุดท้าย + คำเตือนสีแดง
- Input: **พิมพ์ชื่อปีปลายทางเพื่อยืนยัน**
- Button disabled จนกว่า input ตรงเป๊ะกับ `toYear.name`
- Submit → `commit(planId, confirmText)` → ได้ batch_id
- Loading state — ห้ามปิด page ระหว่าง commit
- Success → emit `committed(batch)` parent นำไป step 6 (undo banner)
- Error 422 confirm_text mismatch → toast.error + ไม่ปิด page
- Error 410 plan expired → toast.error + redirect step 4 ให้ re-plan

#### Step 6 — Undo Banner (`RolloverUndoBanner.vue`, 5.G)

หลัง commit สำเร็จ — banner กลางหน้า:

```vue
<div class="rounded-lg bg-green-50 border border-green-200 p-6">
  <div class="flex items-center gap-3">
    <Icon icon="mdi:check-circle" class="w-8 h-8 text-green-600" />
    <div>
      <h2 class="text-lg font-semibold">เลื่อนชั้นสำเร็จ</h2>
      <p class="text-sm text-zinc-600">batch_id: {{ batch.id }}</p>
    </div>
  </div>

  <div class="mt-4 grid grid-cols-5 gap-3">
    <div v-for="(count, key) in batch.totals" :key="key"
         class="rounded bg-white border p-3 text-center">
      <div class="text-2xl font-semibold">{{ count }}</div>
      <div class="text-xs text-zinc-500">{{ labelMap[key] }}</div>
    </div>
  </div>

  <div v-if="isUndoable" class="mt-4 p-3 rounded bg-amber-50 border border-amber-200">
    <div class="flex items-center justify-between">
      <div>
        <div class="font-medium">สามารถยกเลิก rollover นี้ได้</div>
        <div class="text-sm text-zinc-600">เหลือเวลา {{ countdown }}</div>
      </div>
      <div class="flex gap-2">
        <button @click="onUndo" class="btn-danger">↩ ยกเลิก rollover</button>
        <button @click="onCloseUndo" class="btn-secondary">ปิด undo ทันที</button>
      </div>
    </div>
  </div>

  <div class="mt-4 flex gap-2">
    <button @click="exportExcel" class="btn-secondary">📊 Export Excel</button>
    <NuxtLink :to="`/academies/${academyName}/admin/gradebook/rollover/history`" class="btn-secondary">
      ดูประวัติ rollover
    </NuxtLink>
  </div>
</div>
```

- Countdown timer update ทุก 1 วินาที
- Auto-fetch batch status ทุก 60 วินาทีตรวจ `isUndoable` (อาจถูกปิดจากที่อื่น)
- Export Excel: client-side CSV download จาก `batch.plan_summary` (สำหรับ Phase 5 พื้นฐาน — Phase ถัดไปทำ server-side XLSX)

#### Wizard Page Shell (`pages/.../rollover/index.vue`, 5.H)

```vue
<script setup lang="ts">
const route = useRoute()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

const step = ref<1 | 2 | 3 | 4 | 5 | 6>(1)
const wizardState = reactive({
  fromYearId: null as number | null,
  toYearId: null as number | null,
  toYearName: '' as string,
  mapping: [] as RolloverEntry[],
  planId: null as string | null,
  planExpiresAt: null as Date | null,
  batch: null as RolloverBatchDTO | null,
})

// Persist to sessionStorage
watch(wizardState, (val) => {
  sessionStorage.setItem(`rollover-wizard-${academyId.value}`, JSON.stringify(val))
}, { deep: true })

onMounted(async () => {
  // Restore from sessionStorage
  const cached = sessionStorage.getItem(`rollover-wizard-${academyId.value}`)
  if (cached) Object.assign(wizardState, JSON.parse(cached))

  // Auth check
  await fetchAcademy()
  await fetchMyRole()
  if (!isAdmin.value) {
    navigateTo(`/academies/${academyName.value}`)
    return
  }
})

function clearWizard() {
  Object.assign(wizardState, { fromYearId: null, toYearId: null, toYearName: '',
    mapping: [], planId: null, planExpiresAt: null, batch: null })
  sessionStorage.removeItem(`rollover-wizard-${academyId.value}`)
  step.value = 1
}
</script>

<template>
  <div class="max-w-6xl mx-auto p-4 space-y-6">
    <h1 class="text-2xl font-bold">เลื่อนชั้นประจำปี</h1>
    <RolloverStepIndicator :current="step" :total="6" />

    <RolloverYearPicker v-if="step === 1" v-model="wizardState" @next="step = 2" />
    <RolloverClassroomChecklist v-else-if="step === 2" v-model="wizardState" @back="step = 1" @next="step = 3" />
    <RolloverStudentBucket v-else-if="step === 3" v-model="wizardState" @back="step = 2" @next="step = 4" />
    <RolloverPreviewSummary v-else-if="step === 4" v-model="wizardState" @back="step = 3" @next="step = 5" />
    <RolloverCommitPanel v-else-if="step === 5" v-model="wizardState"
                         @back="step = 4" @committed="(b) => { wizardState.batch = b; step = 6 }" />
    <RolloverUndoBanner v-else-if="step === 6" :batch="wizardState.batch!"
                       @new-rollover="clearWizard" />
  </div>
</template>
```

### 7. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | User refresh ระหว่าง step 3 | sessionStorage restore — ถ้าหมดอายุ plan_id ให้กลับ step 3 |
| E2 | Plan_id หมดอายุระหว่าง step 4→5 | 5.F catch 410 → toast.error + redirect step 4 |
| E3 | สร้าง year ใหม่แล้ว fail (duplicate name) | 5.C catch 422 → display under input |
| E4 | "สร้างห้องอัตโนมัติ" ใน 5.C — clone ห้องของปีต้นทาง แต่ทุก field ต้องการรหัส unique | สร้างใหม่ ไม่ clone — generate `classroom_code` ใหม่ |
| E5 | Mapping ส่งซ้ำ student_id 2 entries | Step 3 UI ป้องกัน (selected.has) + backend FormRequest ป้องกันด้วย |
| E6 | นักเรียน 2000+ render slow ใน Step 3 | virtualized list หรือ pagination ในห้อง |
| E7 | Commit timeout (30s+) | server-side: lockForUpdate + transaction; client-side: ขยาย timeout เป็น 120s ใน api call |
| E8 | Undo banner หน้ารีเฟรช | wizardState ใน sessionStorage มี batch_id → restore step 6 |
| E9 | User กดปุ่ม "เลื่อนชั้น" ผ่าน step 5 ในระหว่างกำลัง commit | disable button + spinner; ห้าม close page |
| E10 | new_intake bucket แสดง 476 คน — assign ไป classroom ใหม่ | mapping entry: `{ student_id, action: 'new_intake', to_classroom_id }` |

### 8. Verification per commit

ทุก commit:
1. TS check: `npx vue-tsc --noEmit 2>&1 | grep -E 'rollover/'` — clean
2. SFC tag balance (skill `pre-commit-vue`)
3. SSR check: `npm run dev` ไม่ crash + open หน้า /academies/<name>/admin/gradebook/rollover
4. Manual smoke (commit 5.F + 5.G): preview → plan → commit (test student 1-2 คน) → undo

End-to-end smoke หลัง 5.H ครบ:
1. login admin
2. open wizard
3. step 1: from=2568, to=create year 2569
4. step 2: auto-create classrooms ม.2/1, ม.5/1, etc.
5. step 3: assign 2 test students (1 promote, 1 graduate)
6. step 4: review summary
7. step 5: type "2569" → commit → 201
8. step 6: see batch + undo button
9. click undo → state restored
10. verify ใน DB ผ่าน tinker

### 9. Out of scope ของ Phase 5

- ❌ Rollover history page (`/admin/gradebook/rollover/history`) — Phase 6 หรือแยก
- ❌ Bulk preview optimization >5000 students (Phase performance)
- ❌ Server-side XLSX export — ใช้ client CSV ก่อน
- ❌ Concurrent rollover detection UI (multiple admins) — backend lock ทำงาน
- ❌ Visual drag-drop (`@vueuse/integrations` sortable) — ใช้ multiselect + button assign แทน
- ❌ Diff view ก่อน/หลัง commit แต่ละ classroom

### 10. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Wizard state ใน sessionStorage ใหญ่ (1929 entries) | กลาง | กลาง | serialize เฉพาะ entries สรุป + recreate full data จาก API |
| Step 3 ช้าที่ 1929+ students | สูง | กลาง | virtualized list หรือ filter ก่อน + pagination 100 per page |
| Commit timeout 30s | กลาง | สูง | api call ขยาย timeout 120s + แสดง progress |
| Network drop ระหว่าง commit | กลาง | สูง | client retry 1 ครั้ง + ตรวจ batch หลัง retry |
| Undo button race condition | ต่ำ | สูง | backend lockForUpdate (มีแล้ว) + frontend disable หลังคลิก |
| Auto-create classroom สร้าง duplicate | กลาง | กลาง | check existing ก่อน create; ใช้ classroom_code unique |
| SSR crash จาก reactive deep watch | ต่ำ | สูง | wizardState เป็น `reactive` + sessionStorage check `import.meta.client` |
| Plan cache file driver บน WAMP ไม่ persist | ต่ำ | กลาง | backend ทำงาน OK; ถ้าพังเปลี่ยน driver ใน .env |

### 11. Definition of Done — Phase 5

- [ ] 8 commits land บน branch
- [ ] หน้า /academies/<name>/admin/gradebook/rollover เปิดได้
- [ ] 5-step wizard navigable forward+back ก่อน commit
- [ ] commit irreversible — confirm text gate ทำงาน
- [ ] post-commit undo banner + countdown 24 ชม.
- [ ] sessionStorage persist + restore
- [ ] TS clean สำหรับไฟล์ใหม่ 8 ไฟล์
- [ ] SSR ไม่ crash
- [ ] E2E manual smoke ผ่าน 10 steps
- [ ] commit message ระบุ Phase 5.{A-H}

### 12. Decisions ที่รอยืนยันก่อนเริ่ม

1. **Drag-drop vs multiselect+button** สำหรับ Step 3 assignment
   - Drag-drop: UX สวย + เห็นภาพ
   - Multiselect + assign button: ง่ายกว่า + รองรับเยอะคนเร็วกว่า
   - **Recommendation:** multiselect + button (รองรับ 2000+ คนได้)

2. **Virtualized list library**
   - `@tanstack/vue-virtual` — เพิ่ม dep
   - Native pagination — ง่ายกว่า
   - **Recommendation:** **pagination 100/page** ก่อน — ลด complexity; ถ้าผู้ใช้บ่นค่อย switch

3. **Auto-create classroom UX** ใน Step 2
   - Single click → create ทั้งหมดที่ขาด
   - Per-row "create" button → ยืดหยุ่นกว่า
   - **Recommendation:** **per-row** ก่อน + "create all" button รวมด้านบน

4. **Plan expires countdown แสดงที่ไหน**
   - Step 4 (preview) เท่านั้น
   - Step 4+5 (preview + commit)
   - **Recommendation:** **step 4 + 5** — เตือนตลอด

5. **Export Excel ใน step 6**
   - Client CSV ใน Phase 5 (มี totals + plan_summary)
   - Server XLSX — รอ Phase ถัดไป
   - **Recommendation:** **client CSV** พื้นฐาน

6. **Wizard state สำหรับ "post-undo"** — หลัง undo สำเร็จควรกลับไปหน้าไหน
   - Step 1 (เริ่มใหม่)
   - History page
   - **Recommendation:** **clear state + redirect step 1** พร้อม toast "rollover ถูกยกเลิกแล้ว"

7. **A11y: Step indicator keyboard nav?**
   - Arrow left/right เปลี่ยน step
   - แค่ click
   - **Recommendation:** **click only** ใน Phase 5; arrow nav เป็น nice-to-have
### 9. Implementation update (Codex)

- Implemented Phase 5 frontend scaffold under `ui/components/academy/rollover/` and `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue`.
- Added `useRolloverActions` and extended `ui/types/enrollment.ts` with rollover DTOs for preview, plan, commit, undo, and batch views.
- Added gradebook home entry card in `ui/pages/academies/[name]/admin/gradebook/index.vue`.

Delivered view states:
1. Step 1 `RolloverYearPicker`
2. Step 2 `RolloverClassroomChecklist`
3. Step 3 `RolloverStudentBucket`
4. Step 4 `RolloverPreviewSummary`
5. Step 5 `RolloverCommitPanel`
6. Step 6 `RolloverUndoBanner`

Confirmed design choices now reflected in code:
- Multiselect + button for Step 3.
- Pagination fixed at 100/page.
- Per-row create plus create-all in Step 2.
- Plan countdown visible in Step 4 and Step 5.
- Export kept client-side as CSV.
- Undo success clears state and returns to Step 1.
- Step indicator remains click/view-state only.

Implementation notes:
- Wizard state is centralized in the page and persisted with `sessionStorage`.
- Step 2 refreshes preview after classroom creation so `missing_targets` stays backend-synced.
- Commit gate uses trim-only matching against destination year name.
- Post-commit state refreshes batch status every 60 seconds and keeps a live 24-hour countdown.
- Current create-all behavior creates one default section (`/1`) per missing grade level; multi-section bootstrap remains a follow-up if needed.

Verification status:
- Read-through and route wiring completed for the new rollover files.
- Full `vue-tsc --noEmit` on `ui/` still fails because of many pre-existing repo-wide TypeScript issues outside Phase 5 scope plus an existing `vue-router/volar` package export problem.
- No clean repo-wide TS signal yet for this phase; live smoke test on the new route is still recommended.

---

## 2026-06-21 Phase 5.B — Rollover Step Indicator (Detailed Plan)

### 0. State at start of 5.B

จาก 5.A เสร็จแล้ว:
- ✅ `useRolloverActions` composable + 9 methods + `withLoading` wrapper
- ✅ DTO types ครบ (AcademicYearDTO, RolloverPreviewDTO, RolloverPlanResponse, RolloverBatchDTO, ROLLOVER_ACTIONS)
- ✅ TS clean

Decision §12 lock (จาก Phase 5 plan §12):
- ✅ multiselect+button (Step 3)
- ✅ pagination 100/page
- ✅ per-row + create all
- ✅ Plan countdown step 4+5
- ✅ Client CSV export
- ✅ Post-undo: clear + redirect step 1 + toast
- ✅ Step nav: click only

### 1. หลักการ 5.B

1. **Component เดี่ยว standalone** — ใช้กับ wizard page 5.H, ไม่มี side effect
2. **Click-only nav** (Decision §12.7) — emit `select(step)`; parent อนุญาตหรือไม่ตามเงื่อนไข
3. **Locked steps** — parent ส่ง `unlockedThrough: number` เพื่อกัน user click step ที่ยังไม่ผ่าน
4. **6 visual states** — pending / current / completed / locked (ไม่ click) / disabled / clickable
5. **A11y** — aria-current="step", aria-disabled, role="navigation"
6. **Mobile responsive** — vertical stack < md, horizontal ≥ md

### 2. โครงสร้างไฟล์ + commit (5.B = 1 commit)

```
ui/components/academy/rollover/
└── RolloverStepIndicator.vue       (80 LOC)
```

**1 commit, ~80 LOC, ~15 นาที**

### 3. Component spec

#### Props
```typescript
interface Props {
  current: number                                   // 1..6
  unlockedThrough?: number                          // default = current (no jump-ahead)
  steps?: Array<{ label: string; icon?: string }>   // default 6 standard steps
}

const props = withDefaults(defineProps<Props>(), {
  unlockedThrough: undefined,
  steps: () => defaultSteps,
})
```

#### Default steps
```typescript
const defaultSteps = [
  { label: 'เลือกปี', icon: 'mdi:calendar' },
  { label: 'ตรวจห้อง', icon: 'mdi:home-group' },
  { label: 'จัดสรรนักเรียน', icon: 'mdi:account-multiple' },
  { label: 'ตรวจสรุป', icon: 'mdi:eye' },
  { label: 'ยืนยันบันทึก', icon: 'mdi:check-bold' },
  { label: 'เสร็จสมบูรณ์', icon: 'mdi:party-popper' },
]
```

#### Emits
```typescript
const emit = defineEmits<{ select: [step: number] }>()
```

#### Computed
```typescript
const unlocked = computed(() => props.unlockedThrough ?? props.current)

function stateOf(idx: number): 'completed' | 'current' | 'unlocked' | 'locked' {
  const n = idx + 1
  if (n < props.current) return 'completed'
  if (n === props.current) return 'current'
  if (n <= unlocked.value) return 'unlocked'
  return 'locked'
}

function onClick(idx: number) {
  const n = idx + 1
  if (n > unlocked.value) return       // locked → no-op
  if (n === props.current) return      // already there
  emit('select', n)
}
```

#### Template (desktop horizontal + mobile vertical)

```vue
<template>
  <nav class="w-full" role="navigation" aria-label="ขั้นตอนการเลื่อนชั้น">
    <!-- Desktop -->
    <ol class="hidden md:flex items-center w-full">
      <li v-for="(step, idx) in steps" :key="idx" class="flex-1 flex items-center">
        <button
          type="button"
          :disabled="stateOf(idx) === 'locked'"
          :aria-current="stateOf(idx) === 'current' ? 'step' : undefined"
          :class="[
            'group flex flex-col items-center gap-1 px-2 py-1 transition',
            stateOf(idx) === 'locked'
              ? 'cursor-not-allowed opacity-50'
              : 'cursor-pointer hover:opacity-80'
          ]"
          @click="onClick(idx)"
        >
          <span
            :class="[
              'flex h-9 w-9 items-center justify-center rounded-full border-2 transition',
              stateOf(idx) === 'completed'
                ? 'bg-emerald-500 border-emerald-500 text-white'
                : stateOf(idx) === 'current'
                ? 'bg-indigo-500 border-indigo-500 text-white shadow-md ring-4 ring-indigo-500/20'
                : stateOf(idx) === 'unlocked'
                ? 'bg-white border-zinc-300 text-zinc-600 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300'
                : 'bg-zinc-100 border-zinc-200 text-zinc-400 dark:bg-zinc-900 dark:border-zinc-800 dark:text-zinc-600',
            ]"
          >
            <Icon
              v-if="stateOf(idx) === 'completed'"
              icon="mdi:check"
              class="h-5 w-5"
            />
            <Icon
              v-else-if="step.icon"
              :icon="step.icon"
              class="h-5 w-5"
            />
            <span v-else>{{ idx + 1 }}</span>
          </span>
          <span
            :class="[
              'text-xs font-medium whitespace-nowrap',
              stateOf(idx) === 'current'
                ? 'text-indigo-600 dark:text-indigo-400'
                : stateOf(idx) === 'completed'
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-zinc-500 dark:text-zinc-400',
            ]"
          >
            {{ idx + 1 }}. {{ step.label }}
          </span>
        </button>

        <!-- Connector line (except after last) -->
        <span
          v-if="idx < steps.length - 1"
          :class="[
            'h-0.5 flex-1 mx-1 transition',
            (idx + 1) < current
              ? 'bg-emerald-400'
              : 'bg-zinc-200 dark:bg-zinc-700',
          ]"
        />
      </li>
    </ol>

    <!-- Mobile vertical compact -->
    <ol class="md:hidden flex flex-col gap-2">
      <li
        v-for="(step, idx) in steps"
        :key="`m-${idx}`"
        class="flex items-center gap-3"
      >
        <button
          type="button"
          :disabled="stateOf(idx) === 'locked'"
          :aria-current="stateOf(idx) === 'current' ? 'step' : undefined"
          :class="[
            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 text-xs',
            stateOf(idx) === 'completed'
              ? 'bg-emerald-500 border-emerald-500 text-white'
              : stateOf(idx) === 'current'
              ? 'bg-indigo-500 border-indigo-500 text-white'
              : 'bg-white border-zinc-300 text-zinc-500',
            stateOf(idx) === 'locked' ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
          ]"
          @click="onClick(idx)"
        >
          {{ idx + 1 }}
        </button>
        <span
          :class="[
            'text-sm',
            stateOf(idx) === 'current' ? 'font-semibold text-indigo-600 dark:text-indigo-400' : 'text-zinc-600 dark:text-zinc-300',
          ]"
        >
          {{ step.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>
```

### 4. Implementation update

- Plan refinement applied before coding: wire the parent page in the same commit so the new step-indicator props/events do not leave a temporary API mismatch.
- Implemented `ui/components/academy/rollover/RolloverStepIndicator.vue` with typed props `current`, `unlockedThrough`, optional `steps`, emit `select(step)`, and four visual states: completed, current, unlocked, locked.
- Added a defensive unlock calculation with `Math.max(current, unlockedThrough ?? current)` so invalid parent input cannot lock a step behind the current one.
- Updated `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue` to pass `:current="state.step"`, `:unlocked-through="state.step"`, and a backward-only `handleStepSelect` handler for click-only navigation without jump-ahead.

### 4. Verification

1. `vue-tsc --noEmit --pretty false` on `ui/` still stops on the pre-existing `vue-router/volar/sfc-route-blocks` package export issue before reaching a clean project-wide signal.
2. Read-back verification confirms the wizard shell now passes `:current`, `:unlocked-through`, and `@select="handleStepSelect"` to the step indicator.
3. Manual browser smoke is still pending for this route.

### 5. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | `unlockedThrough < current` | ทำไม่ได้ — `unlocked` คำนวณเป็น `unlockedThrough ?? current` กัน |
| E2 | `current > steps.length` | parent บั๊ก — Phase 5.H validate; component ไม่ crash, แสดงทุก step เป็น completed |
| E3 | mobile portrait ≥ 6 step list ยาว | scrollable container ใน parent ถ้าจำเป็น |
| E4 | dark mode | Tailwind dark: variants ครบ |
| E5 | keyboard Tab — focus indicator | `:disabled` + browser default focus ring; ไม่ต้อง custom ring |

### 6. Out of scope ของ 5.B

- ❌ Arrow key navigation (Decision §12.7 — click only)
- ❌ Animated transitions ระหว่าง state change (CSS transition พื้นฐานพอ)
- ❌ Vertical mode (desktop) — mobile-only vertical
- ❌ Custom step labels per-academy

### 7. Definition of Done — 5.B

- [ ] 1 commit lands
- [ ] `RolloverStepIndicator.vue` มี props: current, unlockedThrough, steps; emit: select
- [ ] 4 visual states render ถูก (completed/current/unlocked/locked)
- [ ] desktop horizontal + mobile vertical responsive
- [ ] TS clean สำหรับไฟล์ใหม่
- [ ] commit message ระบุ Phase 5.B

### 8. Decisions ที่รอยืนยัน

ไม่มี — ทุกอย่าง lock จาก Decision §12 ของ Phase 5 plan แล้ว

→ **พร้อมลงมือ 5.B ทันที**

---

## 2026-06-21 Phase 6 — Reports & Downstream Sync (Detailed Plan)

### 0. State at start of Phase 6

จาก Phase 0–5 commit ครบ 11 commits, branch `feature/academic-year-rollover`:
- ✅ Backend service + API + UI ทำงาน end-to-end (graduate/drop/repeat/promote/transfer + rollover wizard 6 steps)
- ✅ 106 backend tests + TS clean

ที่ค้างจาก Phase 4.C (deferred):
- ❌ Tab "ออกจากห้อง" — ขาด backend endpoint ดึง non-active enrollments ของห้องเดียว

จาก master plan §3 Phase 6:
- 6.1 Transcript scope by `academic_year_id`
- 6.2 หน้า list นักเรียนทุกที่ที่อาศัย `students.class_level` ตรวจ + ปรับ
- 6.3 Attendance scope by year
- 6.4 Search/filter student แยก "ปัจจุบัน" vs "ทุกปี"

### 1. Codebase audit findings (จาก grep)

**A. Transcript** [TranscriptController.php](api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/TranscriptController.php):
- ✅ มี `academic_year_id` filter (line 60, 66) — รับ query param แล้ว
- ✅ `->where('academic_year_id', $academicYearId)` ที่ classroom_students (line 228)
- ⚠️ Fallback ใช้ `$student->class_level` (line 231) เมื่อหา classroom_students ไม่เจอ — ไม่ scope ปี แต่เป็น snapshot ปัจจุบัน
- **Action:** OK as-is; document fallback semantics

**B. AcademyMember** [AcademyMemberController.php](api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php):
- ⚠️ Line 215, 221, 260, 262, 266, 267, 271, 276 — ใช้ `students.class_level` ใน list/grouping (legacy fallback)
- ⚠️ Line 522, 524 — `class_level` filter query param
- **Action:** เปลี่ยน source → join `classroom_students` ของ active year + use `classrooms.grade_level/section`; fallback to legacy ถ้าไม่มี

**C. Classroom** [ClassroomController.php](api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php):
- Line 81, 100 — `profile_image_url` path uses `class_level/class_section` for filesystem — ของ storage layout, ไม่กระทบ rollover semantics
- **Action:** ไม่แตะ (เป็น path convention ของ image storage)

**D. SchoolAttendance** [SchoolAttendanceController.php](api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/SchoolAttendanceController.php):
- Line 534, 563 — query `classroom_students` ดู `student_id, classroom_id, student_number`
- ไม่ scope `status='active'` หรือ year ตามที่ตรวจ
- **Action:** เพิ่ม `where('status', 'active')` ที่ทุกที่ที่ select จาก classroom_students สำหรับ attendance ปัจจุบัน

**E. StudentCard** [StudentCardController.php](api/nuxnanravel/app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php):
- Line 267 — path uses `class_level/class_section` for image storage
- **Action:** ไม่แตะ (storage path เท่านั้น)

### 2. หลักการ Phase 6

1. **Backward compatible** — ไม่ลบ endpoint เก่า; เพิ่ม opt-in scoping
2. **New endpoint สำหรับ inactive classroom students** — แก้ deferred จาก 4.C
3. **Legacy `class_level` fallback คงไว้** — Phase 9a backfill ก่อนค่อยพิจารณาลบ
4. **Year scope = optional query param** — ถ้าไม่ส่ง = ใช้ current year
5. **ทุก endpoint ที่เปลี่ยน scoping ต้องมี test** — กัน regression
6. **Frontend tab "ออกจากห้อง" ใช้ component เดิม** — แค่เปลี่ยน data source

### 3. Sub-phase commits (5 commits, ~3 ชม.)

| # | Subject | ไฟล์หลัก | LOC | เวลา |
|---|---|---|---|---|
| 6.A | feat(api): expose inactive classroom enrollments + scope ClassroomController members | ClassroomController.php, routes, test | ~150 | 45น |
| 6.B | fix(member): scope class_level grouping by active classroom_students | AcademyMemberController.php + test | ~120 | 45น |
| 6.C | fix(attendance): scope queries to active classroom_students rows | SchoolAttendanceController.php + test | ~80 | 30น |
| 6.D | feat(ui): inactive students tab in classroom detail (fulfill 4.C defer) | classroom [id].vue + composable+tests | ~150 | 30น |
| 6.E | docs(rollover): close Phase 6 + downstream sync notes | latest-analysis.md + worklog | ~50 | 15น |

**รวม ~550 LOC, ~3 ชม.**

### 4. Sub-phase specs

#### 6.A — Backend: inactive classroom enrollments

**New endpoint:**
```
GET /api/academies/{academy}/classrooms/{classroom}/enrollments
  ?status[]=active&status[]=transferred&status[]=...
  &academic_year_id=...
```

```php
// app/Http/Controllers/Api/Learn/Academy/ClassroomController.php
public function listEnrollments(Request $req, Academy $academy, Classroom $classroom): JsonResponse
{
    abort_unless($classroom->academy_id === $academy->id, 404);

    $statuses = $req->input('status', [ClassroomStudent::STATUS_ACTIVE]);
    if (!is_array($statuses)) $statuses = [$statuses];

    $rows = ClassroomStudent::where('classroom_id', $classroom->id)
        ->whereIn('status', $statuses)
        ->with(['student:id,student_id,first_name_th,last_name_th,nickname,status,class_level,class_section,academy_id'])
        ->orderBy('left_at', 'desc')
        ->orderBy('student_number')
        ->get();

    return response()->json([
        'success' => true,
        'data' => ClassroomStudentResource::collection($rows->load('createdBy')),
    ]);
}
```

**Route:**
```php
Route::get('{academy}/classrooms/{classroom}/enrollments',
    [ClassroomController::class, 'listEnrollments'])
    ->name('api.academy.classrooms.enrollments');
```

**Tests** (`tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php`):
- T1: GET without status → returns active only (default)
- T2: GET ?status[]=transferred → returns transferred only
- T3: GET ?status[]=active&status[]=graduated → returns both
- T4: cross-academy classroom → 404
- T5: requires auth → 401 unauthenticated
- T6: shape includes student summary + status_text via Resource

**Commit:** `feat(api): list classroom enrollments by status (incl. inactive)`

#### 6.B — Backend: AcademyMember class_level grouping refactor

**Problem:** AcademyMemberController groupings use `students.class_level` directly → ไม่สะท้อน active classroom ปีปัจจุบัน

**Fix:** เปลี่ยน primary query → join active classroom_students; fallback legacy

```php
// AcademyMemberController.php:215-230 area
$currentYearId = AcademicYear::where('academy_id', $academyId)
    ->where('is_current', true)
    ->value('id');

if ($currentYearId) {
    $items = DB::table('students AS s')
        ->join('classroom_students AS cs', function ($j) use ($currentYearId) {
            $j->on('cs.student_id', '=', 's.id')
              ->where('cs.status', 'active')
              ->where('cs.academic_year_id', $currentYearId);
        })
        ->join('classrooms AS c', 'c.id', '=', 'cs.classroom_id')
        ->where('s.academy_id', $academyId)
        ->select('c.grade_level AS class_level', 'c.section AS class_section', DB::raw('COUNT(*) AS student_count'))
        ->groupBy('c.grade_level', 'c.section')
        ->orderBy('c.grade_level')
        ->orderBy('c.section')
        ->get();
} else {
    // legacy fallback — already exists
}
```

**Tests** (extend existing AcademyMemberController test):
- T1: when current year exists → grouping ใช้ active classroom_students
- T2: when no current year → fallback to legacy
- T3: count matches actual active enrollments

**Commit:** `fix(member): scope class_level grouping to active enrollments`

#### 6.C — Backend: SchoolAttendance scope active

**Problem:** queries on `classroom_students` ไม่ filter `status`

**Fix:** ทุก call ที่ query classroom_students ต้องมี `->where('status', 'active')`

```php
// SchoolAttendanceController.php:534, 563
->where('classroom_students.status', 'active')
```

**Tests:**
- T1: นักเรียนที่ transferred → ไม่ปรากฏใน attendance roster
- T2: นักเรียน active → ปรากฏปกติ

**Commit:** `fix(attendance): scope rosters to active classroom enrollments`

#### 6.D — Frontend: inactive students tab

ขยาย `[id].vue` ของ classroom detail page:

```vue
<!-- Add tab nav at top of students section -->
<div class="border-b border-zinc-200 dark:border-zinc-700">
  <nav class="flex gap-4 -mb-px">
    <button @click="activeTab = 'active'"
      :class="['px-4 py-2 text-sm border-b-2 transition',
        activeTab === 'active' ? 'border-indigo-500 text-indigo-600 font-medium' : 'border-transparent text-zinc-500']">
      กำลังศึกษา ({{ activeStudents.length }})
    </button>
    <button @click="activeTab = 'left'"
      :class="['px-4 py-2 text-sm border-b-2 transition',
        activeTab === 'left' ? 'border-indigo-500 text-indigo-600 font-medium' : 'border-transparent text-zinc-500']">
      ออกจากห้อง ({{ leftStudents.length }})
    </button>
  </nav>
</div>

<!-- Conditional table render -->
<div v-if="activeTab === 'active'"> <!-- existing table --> </div>
<div v-else> <!-- left students table (read-only, no actions, no edit student_number) --> </div>
```

**Script additions:**
```ts
const activeTab = ref<'active' | 'left'>('active')
const leftStudents = ref<ClassroomStudentDTO[]>([])

async function fetchLeftStudents() {
  const res: any = await api.get(
    `/api/academies/${academyId.value}/classrooms/${classroomId.value}/enrollments`,
    { query: { 'status[]': ['transferred', 'promoted', 'graduated', 'dropped', 'repeating', 'superseded'] } }
  )
  leftStudents.value = res?.data ?? []
}

watch(activeTab, (tab) => {
  if (tab === 'left' && leftStudents.value.length === 0) fetchLeftStudents()
})
```

**Left students table:**
- คอลัมน์: รหัส | ชื่อ-นามสกุล | สถานะ (badge) | วันออก | เหตุผล | ประวัติ (drawer)
- ไม่มี action menu (read-only)
- คลิก row → เปิด history drawer

**Tests:** ไม่มี FE test infra; manual smoke

**Commit:** `feat(ui): add inactive students tab in classroom detail (Phase 4.C defer)`

#### 6.E — Docs + worklog

- อัพเดท `.agents/worklog.md` เพิ่ม entry "Phase 6 Reports sync"
- อัพเดท `.agents/latest-analysis.md` ปิด Phase 6 deferred items
- บันทึก memory ถ้ามีจุดสำคัญ — ตัวอย่าง: "class_level เป็น snapshot, source of truth = classroom_students active row"

**Commit:** `docs(rollover): close Phase 6 reports sync + downstream notes`

### 5. Verification

ทุก backend commit:
1. `./vendor/bin/pint`
2. `./vendor/bin/phpunit tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php` (6.A)
3. Regression: ทุก existing enrollment test ผ่าน

หลัง 6.D:
1. `npx vue-tsc --noEmit | grep -E 'classrooms/\[id\]'` clean
2. SSR check: `npm run dev` ไม่ crash
3. Manual: เปิด tab "ออกจากห้อง" → list ปรากฏ + คลิก row → drawer

End-to-end smoke (หลัง 6.A-D):
1. Wizard rollover → graduate 1 test student
2. กลับมาหน้า classroom เดิม
3. tab "กำลังศึกษา" → student หายไป
4. tab "ออกจากห้อง" → student ปรากฏพร้อม badge "จบการศึกษา" + วันออก
5. คลิก row → drawer แสดง history (active → graduated)

### 6. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | inactive list ยาว 500+ rows | order by left_at desc + limit 200 (pagination เพิ่มทีหลัง) |
| E2 | นักเรียนถูก undo rollover → ย้ายจาก tab "ออกจากห้อง" กลับ "กำลังศึกษา" | refresh ทั้ง 2 tab หลัง action |
| E3 | AcademyMember grouping `is_current=null` (academy ไม่มี current year) | fallback legacy ที่มีอยู่ |
| E4 | SchoolAttendance: นักเรียนที่ graduate กลางเทอม | attendance หลังวัน left_at ไม่ควรนับเขา → ใช้ `left_at IS NULL OR left_at > attendance_date` (เพิ่ม Phase ถัดไป ถ้าจำเป็น) |
| E5 | Endpoint 6.A returns Resource ที่ Phase 3.B สร้างไว้ — relations ต้อง eager load | `with(['student', 'createdBy'])` |
| E6 | Frontend tab switch ขณะ loading | disable tab buttons ระหว่าง fetch |

### 7. Out of scope ของ Phase 6

- ❌ Pagination ใน enrollment list endpoint (เพิ่มถ้า > 200 rows)
- ❌ Attendance สำหรับ student ที่ left_at กลางเทอม (edge case ใหญ่)
- ❌ Report filter UI ที่อื่น (member page) — เปลี่ยนเฉพาะ backend grouping
- ❌ Rollover history page (Phase 7 หรือแยก)
- ❌ Export inactive list to Excel
- ❌ Bulk restore ของ left students

### 8. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| AcademyMember refactor ผิด → group count ต่างจากที่ผู้ใช้คุ้นเคย | กลาง | สูง | test เปรียบเทียบก่อน/หลังด้วย count(*); fallback legacy ถ้าไม่มี current year |
| SchoolAttendance ตัด transferred students → roster หาย | กลาง | กลาง | test ก่อน + ถาม user ก่อน deploy production |
| inactive list ยาวเกิน 200 | กลาง | กลาง | limit + ดูจริงก่อน paginate |
| Frontend tab loading state crash | ต่ำ | ต่ำ | watch + try/catch ใน fetchLeftStudents |
| ClassroomStudentResource ที่ Phase 3.B ส่งออกไม่ include `student` relation default | สูง | กลาง | controller eager load ก่อนส่งเข้า Resource |

### 9. Definition of Done — Phase 6

- [ ] 5 commits land
- [ ] new endpoint `/classrooms/{classroom}/enrollments` ทำงาน
- [ ] 6 tests ใหม่ (6.A T1-T6) + 3 tests แก้ไข (6.B) + 2 tests (6.C)
- [ ] Regression 106 + ใหม่ = ~117 backend tests ผ่าน
- [ ] inactive tab ทำงานบนหน้า classroom detail
- [ ] SSR clean
- [ ] commit message ระบุ Phase 6.{A-E}
- [ ] Worklog อัพเดท

### 10. Decisions ที่รอยืนยัน

1. **Endpoint shape** สำหรับ inactive — `/classrooms/{classroom}/enrollments` พร้อม `?status[]=...` หรือ `/classrooms/{classroom}/inactive-students`?
   - **Recommendation:** **flexible endpoint** `/enrollments?status[]=...` — รองรับ filter combinations

2. **AcademyMember legacy fallback** — เก็บไว้กี่ sprint
   - **Recommendation:** เก็บไว้ until Phase 9a backfill เสร็จ; รอบ Phase 10 ลบ

3. **Attendance edge case (student left กลางเทอม)** — handle ตอนนี้หรือเลื่อน
   - **Recommendation:** เลื่อน — case rare + edge งานใหญ่; เป็น P2 follow-up

4. **Inactive tab pagination** — paginate เลยหรือ wait & see
   - **Recommendation:** **limit 200 ก่อน**; paginate ถ้าผู้ใช้บ่น

5. **Show legacy delete button ใน inactive tab?**
   - **Recommendation:** **ไม่แสดง** — inactive = read-only (history)

### 11. 2026-06-21 Implementation update — Phase 6.A in progress

- เริ่มลงมือ Phase 6.A ตาม recommendation ทั้ง 5 ข้อแล้ว
- Implementation ที่ทำจริงรอบนี้:
  - เพิ่ม endpoint `GET /api/academies/{academy}/classrooms/{classroom}/enrollments`
  - default filter = `status[]=active`
  - รองรับ `status[]` หลายค่า และ `academic_year_id`
  - validate `status.*` ด้วย `ClassroomStudent::$statuses`
  - validate `academic_year_id` ให้ belong กับ academy เดียวกัน
  - eager load `student`, `classroom`, `createdBy` ให้ตรง `ClassroomStudentResource`
- Test plan รอบนี้ขยายจาก draft เดิมเล็กน้อย:
  - คง 6 เคสหลักเดิม (auth, default active, single status, multi status, cross-academy 404, resource shape)
  - เพิ่ม 1 เคส `academic_year_id` filter เพื่อ lock contract ตั้งแต่ Phase 6.A

### 12. 2026-06-21 Implementation update — Phase 6.B completed

- `AcademyMemberController::getFilterOptions()` now prefers active `classroom_students` rows in the academy's current academic year
- Current-year branch derives `class_levels`, `class_sections`, and `classrooms` from active enrollments instead of `students.class_level`
- Legacy fallback remains intact when the academy has no `is_current=true` academic year or no active enrollment rows in that year
- Added regression test file `tests/Feature/Api/Academy/AcademyMemberFilterOptionsTest.php`
  - case 1: current-year active enrollment overrides stale `students.class_level/class_section`
  - case 2: no current year still falls back to legacy snapshot behavior

### 13. 2026-06-21 Implementation update — Phase 6.C verified

- `SchoolAttendanceController` already had `where('status', ClassroomStudent::STATUS_ACTIVE)` in both classroom-info helper queries at audit time of implementation
- No controller logic change was needed in this pass; Phase 6.C landed as regression coverage
- Added `tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php`
  - case 1: active enrollment enriches `student_number` and `classroom_name`
  - case 2: transferred enrollment does not leak stale classroom info into the attendance roster

### 14. 2026-06-21 Implementation update — Phase 6.D completed

- Updated `ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue` to load classroom enrollments from the new backend endpoint instead of relying on the embedded classroom payload
- Added an inactive tab (`ออกจากห้อง`) that lazily fetches non-active statuses (`transferred`, `promoted`, `graduated`, `dropped`, `repeating`, `superseded`) and keeps the first 200 rows client-side for now
- Reused the existing enrollment history drawer so clicking student rows or the history button opens the same timeline view for active and inactive records
- Kept the inactive tab read-only by hiding add/action/delete controls there, while leaving the active tab behavior unchanged
- Polished the tab-aware UX:
  - header summary count now follows the selected tab
  - search placeholder changes by tab
  - empty state copy explains inactive-history behavior
- Verification:
  - `ui`: `node -e "const fs=require('fs'); const { parse }=require('./node_modules/@vue/compiler-sfc'); ..."` → `SFC parse ok`
  - `ui`: `cmd /c npx vue-tsc --noEmit --pretty false` is still blocked by an existing workspace dependency issue: `vue-router/volar/sfc-route-blocks` export resolution failure

### 15. 2026-06-21 Close-out update — Phase 6.E completed

- Updated `.agents/latest-analysis.md` and `.agents/worklog.md` to capture the final Phase 6 implementation state, verification, and remaining workspace limitations
- Re-ran focused backend verification after the frontend/documentation close-out:
  - `vendor\bin\pint app/Http/Controllers/Api/Learn/Academy/ClassroomController.php app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php tests/Feature/Api/Academy/AcademyMemberFilterOptionsTest.php tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php`
  - `php artisan test tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php tests/Feature/Api/Academy/AcademyMemberFilterOptionsTest.php tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php`
  - Result: `11 passed (41 assertions)`
- Phase 6 is complete at implementation level for 6.A through 6.E
- Known non-code limitation at close-out:
  - frontend typecheck remains blocked by the existing `vue-router/volar/sfc-route-blocks` export-resolution issue in this workspace
  - manual browser smoke / SSR live check was not run in this pass

## 2026-06-21 - Phase 7 planning (enrollment event listeners -> notifications)

- Scope classification: backend-first with light frontend follow-up.
- Existing foundations confirmed:
  - `StudentEnrollmentService` and `AcademicYearRolloverService` already dispatch 8 enrollment/rollover events.
  - `NotificationService` already centralizes database notification inserts.
  - notification UI already renders generic `type`, `icon`, `color`, `content`, `action_url`, and `metadata`.
- Key findings:
  - there are no enrollment listeners yet and no `EventServiceProvider`; plan should rely on Laravel listener auto-discovery or explicitly add provider wiring if auto-discovery is disabled in this app.
  - current realtime bell polling works from `/api/notifications/recent`, but `useNotifications().initEcho()` expects Laravel broadcast notifications on `App.Models.User.{id}`. `NotificationService` does not broadcast, so Reverb is not free in Phase 7.
  - `User` model does not expose a clear writable `status` domain field, so "sync users.status" from the older master note is not currently grounded in model shape.
  - guardian linkage is still fragile: `student_guardians` has no real `user_id` support yet; current access checks use `citizen_id` matching, so parent recipients need a cautious fallback strategy.
- Likely touched files when implementing:
  - `api/nuxnanravel/app/Listeners/Enrollment/*.php`
  - `api/nuxnanravel/app/Models/Notification.php`
  - `api/nuxnanravel/app/Services/NotificationService.php` or a new focused enrollment notification helper
  - possibly `api/nuxnanravel/app/Providers/EventServiceProvider.php`
  - `ui/composables/useNotifications.ts`
  - `ui/pages/notifications.vue`
- Recommended decisions:
  - Phase 7 should target database notifications first; treat Reverb broadcast/email as optional follow-up unless explicitly prioritized.
  - use 1 listener per event for clarity, but share payload-building helpers to avoid duplicated recipient/message logic.
  - notify only recipients resolvable by stable current relations: student linked `user`, homeroom teacher, academy admins, and parent academy members matched through guardian `citizen_id` only when unique and present.
  - keep academy-member revocation for dropped students as a separate service-side rule in the same phase only if the user wants behavior change bundled with notifications.
- Verification plan:
  - add focused feature tests asserting notification rows for each event family and recipient set.
  - regression-check existing `StudentEnrollmentServiceTest`, `AcademicYearRolloverServiceTest`, and `RolloverControllerWriteTest`.
  - if frontend copy/type filters are updated, do a read-back plus targeted SFC parse; do not rely on repo-wide `vue-tsc` until the existing workspace issue is fixed.

### 2026-06-21 Implementation update - Phase 7.1 database notification listeners

- Status: implemented and verified.
- Backend changes shipped:
  - added `EnrollmentNotificationService` to centralize recipient resolution and database payload creation for enrollment/rollover events
  - added 8 listeners under `app/Listeners/Enrollment/` for:
    - `StudentEnrolled`
    - `StudentTransferred`
    - `StudentPromoted`
    - `StudentRepeated`
    - `StudentGraduated`
    - `StudentDropped`
    - `RolloverCommitted`
    - `RolloverUndone`
  - extended `Notification` model with enrollment/rollover type constants plus icon/color mapping
  - updated `StudentEnrollmentService::enrollStudent()` with `dispatchEvent` flag so transfer/promote/repeat flows do not emit nested `StudentEnrolled` notifications on the internally opened row
- Listener registration decision:
  - Laravel auto-discovers listeners from `app/Listeners`, so the explicit custom `EventServiceProvider` added in the first attempt was removed after it caused duplicate registrations (`ClassName` plus `ClassName@handle`)
  - `php artisan event:list` now shows only the discovered `@handle` listeners for the new enrollment events
- Recipient policy implemented in Phase 7.1:
  - `student_enrolled`: student user + homeroom teacher
  - `student_transferred`: student user + old/new homeroom teachers
  - `student_promoted`: student user + old/new homeroom teachers + academy owner/admins
  - `student_repeated`: student user + old/new homeroom teachers + academy owner/admins
  - `student_graduated`: student user + homeroom teacher + academy owner/admins
  - `student_dropped`: student user + homeroom teacher + academy owner/admins
  - `rollover_committed` / `rollover_undone`: academy owner/admins
  - parent/guardian recipients were intentionally deferred because guardian-to-user linkage is still not a stable first-class relation in the current schema
- Verification run:
  - `vendor\bin\pint app\Services\EnrollmentNotificationService.php app\Services\StudentEnrollmentService.php app\Models\Notification.php app\Listeners\Enrollment\*.php tests\Feature\EnrollmentNotificationListenerTest.php`
  - `php artisan test tests/Feature/EnrollmentNotificationListenerTest.php tests/Feature/StudentEnrollmentServiceTest.php tests/Feature/AcademicYearRolloverServiceTest.php tests/Feature/Api/Academy/RolloverControllerWriteTest.php`
  - result: `47 passed (150 assertions)`
- Notes:
  - test output still prints the existing maintenance/Xdebug noise (`Fixed enrolled_students count...`, xdebug log warning), but the suites pass cleanly
  - frontend labels/filters for the new notification types are not part of 7.1 yet

### 2026-06-21 Implementation update - Phase 7.2 notification UI + grouped type filters

- Status: implemented and verified.
- Frontend changes shipped:
  - updated `ui/composables/useNotifications.ts` with exported grouped type lists:
    - `ENROLLMENT_NOTIFICATION_TYPES`
    - `ROLLOVER_NOTIFICATION_TYPES`
  - added Thai labels for the new enrollment/rollover notification types so bell/page UIs no longer fall back to raw type ids
  - rebuilt `ui/pages/notifications.vue` to add category tabs for:
    - all
    - unread
    - grade
    - certificate
    - enrollment
    - rollover
  - notification rows now show a type badge plus relative timestamp using shared composable helpers
  - empty-state copy is now tab-aware for the new categories
- Cross-stack contract update:
  - `NotificationController@index` now supports `types[]` multi-select filtering while preserving the legacy single `type` query param
  - added `tests/Feature/NotificationControllerTest.php` to lock the `type` and `types[]` filtering behavior
- Verification run:
  - `vendor\bin\pint app\Http\Controllers\Api\Play\NotificationController.php tests\Feature\NotificationControllerTest.php`
  - `php artisan test tests/Feature/NotificationControllerTest.php tests/Feature/EnrollmentNotificationListenerTest.php`
  - result: `9 passed (36 assertions)`
  - `ui`: targeted parse check via `@vue/compiler-sfc` on `pages/notifications.vue` plus composable read-back -> `frontend notification files parse ok`
- Notes:
  - repo-wide `vue-tsc` was intentionally not rerun because the workspace still has the known unrelated Volar/export-resolution issue

---

## 2026-06-21 Phase 7 (verified) + Phase 8 — Audit & History UI (Detailed Plan)

### 2026-06-21 Implementation update - Phase 8.1 Apply Auditable + Tests

- Status: implemented and verified.
- Backend changes:
  - Added `use Auditable` trait to `ClassroomStudent` and `RolloverBatch` models.
  - Overrode `getAuditModule()` in both models to return `'enrollment'`.
  - Overrode `getAuditHiddenAttributes()` in `RolloverBatch` to exclude/redact the large `plan_summary` attribute.
  - Updated `AuditLogService` to support model-specific hidden fields via `getAuditHiddenAttributes()`.
  - Updated `AuditLogService::detectModule()` to respect `getAuditModule()` if defined on the model.
- Verification:
  - Created a new feature test `tests/Feature/EnrollmentAuditTest.php` containing 4 cases:
    - `test_classroom_student_creation_audits_correctly`
    - `test_classroom_student_graduation_audits_correctly`
    - `test_classroom_student_transfer_audits_correctly`
    - `test_rollover_batch_creation_and_update_audits_correctly`
  - Ran `php artisan test tests/Feature/EnrollmentAuditTest.php` -> `4 passed (21 assertions)`
  - Ran regression tests: `EnrollmentNotificationListenerTest`, `StudentEnrollmentServiceTest`, `AcademicYearRolloverServiceTest` -> `31 passed (111 assertions)`
  - Ran Pint formatter on modified files.

### 0. Phase 7 status — DONE (verified, uncommitted)

ตรวจ working tree พบ Phase 7 ทำเสร็จครบและ verified:
- ✅ `app/Services/EnrollmentNotificationService.php` — 8 notify methods + 4 recipient resolvers
- ✅ `app/Listeners/Enrollment/` — 8 listeners (Send{Enrolled,Transferred,Promoted,Repeated,Graduated,Dropped}Notification + Rollover{Committed,Undone})
- ✅ `app/Models/Notification.php` — 8 new TYPE_* constants + icon mapping
- ✅ `StudentEnrollmentService` — dispatch events (param `$dispatchEvent = true`)
- ✅ `AcademicYearRolloverService` — fire RolloverCommitted (line 431) + RolloverUndone (line 518)
- ✅ Laravel 12 auto-discovery → listeners ทำงานโดยไม่ต้อง register (test ยิง `event()` จริงผ่าน)
- ✅ `EnrollmentNotificationListenerTest` — 7 tests, 29 assertions, ผ่าน
- ✅ Regression รวม notification + service + rollover = 31 tests ผ่าน

**Action ก่อนเริ่ม Phase 8:** commit Phase 7 ก่อน (ดู §5 commit guide)

### 1. Phase 8 scope (จาก master plan §3)

- 8.1 ใช้ `App\Traits\Auditable` apply กับ `ClassroomStudent`, (`StudentAcademicInfo` มีแล้ว)
- 8.2 UI: tab "ประวัติการลงห้อง" ใน student master profile — timeline ปี/ห้อง/status/leave_reason/โดยใคร
- 8.3 หน้า rollover history admin: list batch + กดดูรายละเอียด

### 2. Prerequisites audit (codebase)

| สิ่งที่ต้องมี | สถานะ |
|---|---|
| `App\Traits\Auditable` | ✅ มี — boot hooks created/updated/deleted + getAuditLogs(limit) + getAuditModule() |
| `App\Models\AuditLog` | ✅ มี — fillable: user_id, action, entity_type, entity_id, module, old_values, new_values, metadata, ip, ua, url, method |
| `StudentAcademicInfo` use Auditable | ✅ มีแล้ว (line 16) |
| `ClassroomStudent` use Auditable | ❌ ยังไม่มี (line 17 = HasFactory เท่านั้น) |
| `RolloverBatch` use Auditable | ❌ ยังไม่มี |
| Rollover history endpoint (`GET /rollover/batches` + `/batches/{batch}`) | ✅ มีจาก Phase 3.D |
| Frontend `useRolloverActions.listBatches/fetchBatch` | ✅ มีจาก Phase 5.A |
| Student master profile page | ⚠️ มีแผน 2026-06-17 แต่ยังไม่ implement — Phase 8.2 ต้อง degrade เป็น "ใช้ EnrollmentHistoryDrawer ที่มี" |

### 3. หลักการ Phase 8

1. **Audit ผ่าน trait ที่มีอยู่** — ไม่สร้าง audit ใหม่; แค่ `use Auditable`
2. **History UI reuse Phase 4 component** — `EnrollmentHistoryDrawer` มีแล้ว; Phase 8 เพิ่ม "ใครทำ" (created_by) + audit detail
3. **Rollover history page ใหม่** — `/admin/gradebook/rollover/history` ใช้ `listBatches` ที่มี
4. **ไม่ over-engineer** — student master profile ยังไม่มี → Phase 8.2 ลงที่ enrollment history ที่เข้าถึงได้จริง (classroom page + future profile)
5. **Test audit เกิดจริง** — apply trait แล้ว verify AuditLog row ถูกสร้าง

### 4. Sub-phase commits (4 commits, ~2 ชม.)

| # | Subject | ไฟล์ | LOC | เวลา |
|---|---|---|---|---|
| 8.A | feat(enrollment): audit ClassroomStudent + RolloverBatch via Auditable | ClassroomStudent.php, RolloverBatch.php + test | ~80 | 30น |
| 8.B | feat(api): rollover batch history already exists — add audit log to batch detail response | RolloverController show() + RolloverBatchResource + test | ~100 | 30น |
| 8.C | feat(ui): rollover history page | pages/.../rollover/history.vue + RolloverBatchHistoryCard.vue | ~280 | 45น |
| 8.D | docs(rollover): close Phase 7+8, worklog, memory | latest-analysis.md, worklog.md, MEMORY.md | ~60 | 15น |

**รวม ~520 LOC, ~2 ชม.**

### 5. Phase 7 commit (ก่อน 8.A)

```bash
git add api/nuxnanravel/app/Services/EnrollmentNotificationService.php \
        api/nuxnanravel/app/Listeners/Enrollment/ \
        api/nuxnanravel/app/Models/Notification.php \
        api/nuxnanravel/app/Services/StudentEnrollmentService.php \
        api/nuxnanravel/tests/Feature/EnrollmentNotificationListenerTest.php
# commit: "feat(notifications): Phase 7 enrollment + rollover event listeners"
```

(ตรวจ `StudentEnrollmentService` diff ว่ามีแค่ event dispatch — ไม่ปน Phase อื่น)

### 6. Sub-phase specs

#### 8.A — Apply Auditable

```php
// app/Models/ClassroomStudent.php
use App\Traits\Auditable;

class ClassroomStudent extends Model
{
    use Auditable, HasFactory;
    // ...
    public function getAuditModule(): ?string { return 'enrollment'; }
}
```

```php
// app/Models/RolloverBatch.php
use App\Traits\Auditable;

class RolloverBatch extends Model
{
    use Auditable;
    public function getAuditModule(): ?string { return 'enrollment'; }
    // กัน plan_summary (large) จาก audit — getAuditHiddenAttributes
    protected function getAuditHiddenAttributes(): array {
        return ['plan_summary'];
    }
}
```

**ตรวจ AUDIT_ENABLED:** Auditable เคารพ `config/audit.php` env `AUDIT_ENABLED`; ใน test ต้อง set true หรือ trait มี default

**Tests** (`tests/Feature/EnrollmentAuditTest.php`):
- T1: graduate student → AuditLog row created (action=updated, entity_type=ClassroomStudent)
- T2: commit rollover → AuditLog row for RolloverBatch (action=created)
- T3: audit captures old_values/new_values for status change
- T4: plan_summary excluded from RolloverBatch audit (hidden attr)

**Commit:** `feat(enrollment): audit ClassroomStudent + RolloverBatch changes`

#### 8.B — Batch detail audit in response

`RolloverController::show()` — เพิ่ม audit logs:
```php
public function show(Academy $academy, RolloverBatch $batch): JsonResponse
{
    abort_unless(Gate::allows('enrollment.viewBatches', $academy), 403);
    $batch->load(['fromYear', 'toYear', 'committedBy', 'undoneBy']);

    return response()->json([
        'success' => true,
        'batch' => new RolloverBatchResource($batch),
        'audit_logs' => $batch->getAuditLogs(20)->map(fn ($log) => [
            'action' => $log->action,
            'user' => $log->user_id,
            'changed' => $log->new_values,
            'at' => $log->created_at?->toIso8601String(),
        ]),
    ]);
}
```

**Tests:**
- T1: show batch → includes audit_logs array
- T2: audit_logs visible to admin only (gate)

**Commit:** `feat(api): include audit trail in rollover batch detail`

#### 8.C — Rollover history page

`pages/academies/[name]/admin/gradebook/rollover/history.vue`:

```vue
<script setup lang="ts">
const { listBatches, fetchBatch, undo, closeUndo } = useRolloverActions(academyId)
const batches = ref<RolloverBatchDTO[]>([])
const selectedBatch = ref<RolloverBatchDTO | null>(null)
const page = ref(1)

async function load() {
  const res = await listBatches(page.value)
  batches.value = res.data
}
</script>

<template>
  <div class="max-w-5xl mx-auto p-4 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">ประวัติการเลื่อนชั้น</h1>
      <NuxtLink :to="`/academies/${academyName}/admin/gradebook/rollover`" class="btn-primary">
        + เลื่อนชั้นใหม่
      </NuxtLink>
    </div>

    <div v-if="!batches.length" class="text-center py-12 text-zinc-500">
      ยังไม่มีประวัติการเลื่อนชั้น
    </div>

    <RolloverBatchHistoryCard
      v-for="batch in batches" :key="batch.id"
      :batch="batch"
      @view="selectedBatch = batch"
      @undo="onUndo(batch)"
    />
  </div>
</template>
```

`components/academy/rollover/RolloverBatchHistoryCard.vue`:
- แสดง: from_year → to_year, committed_at, committed_by, totals tiles, status badge (committed/undone)
- ปุ่ม "ดูรายละเอียด" + "ยกเลิก" (ถ้า isUndoable)
- countdown ถ้า isUndoable

**Commit:** `feat(ui): rollover batch history page`

#### 8.D — Docs + memory

- อัพเดท worklog: Phase 7 + 8
- บันทึก memory `project_enrollment_rollover.md`:
  - `classroom_students` = source of truth; `students.class_level` = snapshot
  - rollover batch + undo 24h
  - audit ผ่าน Auditable trait
- ปิด latest-analysis Phase 8

**Commit:** `docs(rollover): close Phase 7+8 + record enrollment memory`

### 7. Verification

ทุก backend commit:
1. `./vendor/bin/pint`
2. `./vendor/bin/phpunit tests/Feature/EnrollmentAuditTest.php`
3. Regression 117 + notification 7 + audit 4 = ~128 tests

หลัง 8.C:
1. TS clean
2. `npm run dev` — เปิด /admin/gradebook/rollover/history
3. Manual: commit rollover (test) → ดู history page → เห็น batch + audit + undo

### 8. Edge cases

| # | Case | จัดการ |
|---|---|---|
| E1 | AUDIT_ENABLED=false ใน env | trait skip; test ต้อง force enable หรือ assert conditional |
| E2 | RolloverBatch.plan_summary ใหญ่ → audit bloat | getAuditHiddenAttributes exclude |
| E3 | ClassroomStudent audit ทุก enroll = เยอะมาก (rollover 2000 rows) | acceptable; AuditLog เป็น append table; ถ้าช้า → batch insert (Phase perf) |
| E4 | history page pagination | listBatches มี page param แล้ว |
| E5 | student master profile ยังไม่มี (8.2) | Phase 8 ลง enrollment history ที่ classroom page ที่มี; profile tab รอ master profile feature |
| E6 | audit ตอน commit ใน transaction → rollback ถ้า fail | Auditable hooks อยู่ใน model events ใน transaction เดียว — rollback ถูก |

### 9. Out of scope ของ Phase 8

- ❌ Student master profile page (แผนแยก 2026-06-17, 35 ชม.)
- ❌ Audit log viewer ทั่วทั้งระบบ
- ❌ Audit diff visualization (แสดง old→new แบบ rich)
- ❌ Batch insert optimization สำหรับ audit (perf phase)
- ❌ Export audit to Excel

### 10. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| AUDIT_ENABLED config ไม่เปิดใน test/prod | กลาง | สูง | ตรวจ config ก่อน 8.A; document ใน worklog |
| Audit 2000 rows ตอน rollover ทำให้ commit ช้า | กลาง | กลาง | measure; ถ้าช้า → disableAuditing() ระหว่าง bulk + log batch-level เดียว |
| plan_summary leak ใน audit (PII ใน before-snapshot) | กลาง | สูง | getAuditHiddenAttributes exclude plan_summary |
| ClassroomStudent audit เปลี่ยน behavior existing flow | ต่ำ | กลาง | trait เป็น additive (model events); existing tests ต้องผ่าน |

### 11. Definition of Done — Phase 8

- [ ] Phase 7 committed ก่อน
- [ ] 4 Phase 8 commits land
- [ ] ClassroomStudent + RolloverBatch audited
- [ ] 4 audit tests + 2 batch-detail tests ผ่าน
- [ ] Regression ~128 tests
- [ ] rollover history page ทำงาน
- [ ] worklog + memory อัพเดท
- [ ] commit message ระบุ Phase 8.{A-D}

### 12. Decisions ที่รอยืนยัน

1. **Bulk audit ตอน rollover 2000 rows** — audit ทุก row หรือ batch-level เดียว?
   - **Recommendation:** audit ทุก row ก่อน (correctness); measure แล้วค่อย optimize ถ้าช้า

2. **Phase 8.2 student master profile tab** — ทำตอนนี้หรือเลื่อน
   - **Recommendation:** **เลื่อน** — master profile ยังไม่มี; Phase 8 ลง rollover history page + reuse EnrollmentHistoryDrawer (มี created_by อยู่แล้วจาก Resource)

3. **History page route** — `/rollover/history` หรือ tab ใน `/rollover`
   - **Recommendation:** **แยก route `/history`** — wizard กับ history คนละ concern

4. **plan_summary ใน audit** — exclude หรือ keep
   - **Recommendation:** **exclude** (มี before-snapshot = PII + ใหญ่)
## 2026-06-21 Phase 8.2 - Enrollment History UI enrichment

- Scope implemented:
  - Enriched `ClassroomStudentResource` with loaded `academic_year` shape `{ id, name }`.
  - Updated `StudentLifecycleController` history/success responses to eager-load `academicYear` alongside `classroom`, `createdBy`, `student`.
  - Extended `ui/types/enrollment.ts` with `EnrollmentAcademicYearDTO` and optional `academic_year` on `ClassroomStudentDTO`.
  - Rebuilt `ui/components/academy/enrollment/EnrollmentHistoryDrawer.vue` into a richer timeline card UI showing:
    - summary header
    - current classroom/current academic year
    - per-entry status badge
    - date range
    - classroom/year block
    - actor via `created_by`
    - leave reason when present
- Intentional decision:
  - Did not create a new student master profile page because the repo still uses the classroom detail drawer as the real entry point for enrollment history.
  - Kept `useStudentEnrollmentActions` endpoint contract unchanged; only expanded response shape.
- Verification:
  - `api/nuxnanravel`: `vendor\bin\pint app\Http\Controllers\Api\Learn\Academy\StudentLifecycleController.php app\Http\Resources\Learn\Academy\Enrollment\ClassroomStudentResource.php tests\Feature\Academy\Enrollment\ResourceShapeTest.php tests\Feature\Api\Academy\StudentLifecycleControllerTest.php`
  - `api/nuxnanravel`: `php artisan test tests/Feature/Academy/Enrollment/ResourceShapeTest.php tests/Feature/Api/Academy/StudentLifecycleControllerTest.php`
    - Result: `22 passed (70 assertions)`
  - `ui`: targeted parse check with `@vue/compiler-sfc` on `components/academy/enrollment/EnrollmentHistoryDrawer.vue` plus DTO presence check in `types/enrollment.ts`
    - Result: `frontend enrollment history files parse ok`

## 2026-06-21 Phase 9 planning note - Backfill & Data Repair

- Request type: plan-only. No implementation performed in this pass.
- Planning focus:
  - Phase 9.1 `enrollment:repair-dirty-data` should repair three invariant classes before any further hardening:
    - duplicate active `classroom_students` rows per student/year -> keep latest row, mark older rows `superseded`
    - `students.class_level` / `class_section` drift from active enrollment snapshot -> re-sync from pivot source of truth
    - duplicate `student_academic_info.is_current=true` rows -> keep latest academic year row as current
  - Phase 9.2 `enrollment:backfill-academic-info` should create missing `student_academic_info` rows from active `classroom_students` + linked `academic_years`/`classrooms`, not from legacy student snapshot fields unless fallback is unavoidable
- Relevant code confirmed during planning:
  - `app/Services/StudentEnrollmentService.php`
  - `app/Services/AcademicYearRolloverService.php`
  - `app/Models/ClassroomStudent.php`
  - `app/Models/StudentAcademicInfo.php`
  - `app/Console/Commands/StudentsBackfillCardLink.php` as existing artisan backfill pattern
- Key decisions for implementation:
  - use dry-run by default in validation workflow and emit a machine-readable summary plus a human repair report
  - chunk large scans (`chunkById`) instead of loading whole tables
  - keep Phase 9 scoped to data repair/backfill only; do not silently change service semantics in the same command PR
- Verification plan when implementing:
  - add focused feature tests for each repair rule and for missing-academic-info backfill creation
  - run targeted enrollment test suites plus dry-run and real-run checks on production-like data snapshot

## 2026-06-21 Phase 9.2 implementation - Enrollment academic info backfill

- Scope implemented:
  - added artisan command `enrollment:backfill-academic-info`
  - command supports `--year=`, `--academy=`, and `--dry-run`
  - data source is `classroom_students` with eager-loaded `student`, `classroom`, and `academicYear`
  - creates missing `student_academic_info` rows from enrollment history
  - patches existing `student_academic_info` rows where `academic_year` is null and the row can be matched safely to the enrollment/classroom
  - enriches existing same-year rows only when core fields are still empty, avoiding broad overwrite of manual data
  - promotes existing same-year rows to `is_current=true` for active enrollment when no other current row exists
- Files touched for Phase 9.2:
  - `api/nuxnanravel/app/Console/Commands/EnrollmentBackfillAcademicInfo.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentBackfillAcademicInfoCommandTest.php`
- Verification:
  - `api/nuxnanravel`: `vendor\bin\pint app\Console\Commands\EnrollmentBackfillAcademicInfo.php tests\Feature\EnrollmentBackfillAcademicInfoCommandTest.php`
  - `api/nuxnanravel`: `php artisan test tests\Feature\EnrollmentBackfillAcademicInfoCommandTest.php`
    - Result: `5 passed (12 assertions)`
- Notes:
  - command uses `chunkById(200)` to avoid loading the whole enrollment table into memory
  - current-row conflicts are counted and skipped instead of forcing a second `is_current=true` row

## 2026-06-21 Phase 9.3 execution - Dry-run review and real run

- Phase 9.1 safety adjustment:
  - updated `EnrollmentRepairDirtyData` to normalize `students.class_level` through `StudentEnrollmentService::normalizeGradeLevel()` before comparing/writing snapshot drift
  - added regression coverage for Thai grade strings such as `ม.6 -> 6`
- Verification before DB execution:
  - `php artisan test tests\Feature\EnrollmentRepairDirtyDataTest.php tests\Feature\EnrollmentBackfillAcademicInfoCommandTest.php`
    - Result: `12 passed (45 assertions)`
- Dry-run observations before real run:
  - `enrollment:repair-dirty-data --dry-run`
    - after normalization fix: `duplicate_active_fixed=0`, `student_snapshots_resynced=0`, `duplicate_current_demoted=0`, `manual_review_rows=1913`
  - `enrollment:backfill-academic-info --dry-run`
    - `processed=1929`, `patched_null_year=1913`, `skipped_existing=16`
- Real run:
  - executed `php artisan enrollment:backfill-academic-info`
  - executed `php artisan enrollment:repair-dirty-data`
- Post-run verification:
  - `enrollment:repair-dirty-data --dry-run` => all counters `0`
  - `enrollment:backfill-academic-info --dry-run` => `patched_null_year=0`, `skipped_existing=1929`
- Residual data note:
  - direct DB inspection still shows `student_academic_info.academic_year IS NULL = 491` rows
  - these remaining rows are not enrollment-backed active records, so current Phase 9.2 logic intentionally leaves them untouched
  - full execution summary recorded in `.agents/backups/2026-06-21/repair-report.md`

## 2026-06-21 Phase 9.1 implementation - Enrollment repair dirty data

- Scope implemented:
  - added artisan command `enrollment:repair-dirty-data` supporting `--dry-run` and `--academy=` filters.
  - command repairs three invariants:
    1. duplicate active `classroom_students` rows per student/year -> keep latest active row, mark older rows `superseded`.
    2. `students.class_level` / `class_section` drift from active classroom -> re-sync. Skip clearing snapshot if no active enrollment exists.
    3. duplicate `student_academic_info.is_current=true` rows -> keep latest as current (sorting by `academic_year` desc, then `updated_at` desc, then `id` desc).
  - command reports manual review rows: null academic years, deleted classrooms, academy mismatches, classroom/year mismatches.
- Files touched for Phase 9.1:
  - `api/nuxnanravel/app/Console/Commands/EnrollmentRepairDirtyData.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentRepairDirtyDataTest.php`
- Verification:
  - `api/nuxnanravel`: `vendor\bin\pint app\Console\Commands\EnrollmentRepairDirtyData.php tests\Feature\EnrollmentRepairDirtyDataTest.php`
  - `api/nuxnanravel`: `php artisan test tests/Feature/EnrollmentRepairDirtyDataTest.php`
    - Result: `6 passed (30 assertions)`

## 2026-06-21 Phase 10 planning note - Cleanup + docs

- Request type: plan-only. No implementation performed in this pass.
- Current status check:
  - enrollment rollout is effectively at `9/10` for the current sequence: Phase 7 notifications done, Phase 8 audit/history done, Phase 9 repair/backfill/report done.
  - current working tree already contains uncommitted changes for:
    - notification filter/UI files
    - Phase 9 repair/backfill commands + tests
    - `.agents/worklog.md`
    - `.agents/latest-analysis.md`
- Key findings for Phase 10:
  - the older checklist item "remove legacy paths that write `students.class_level` directly" is too broad for the current repo state; `class_level` is still an active backward-compatibility snapshot used by student profile/card flows, home-visit flows, academy member filters, and rollover intake fallback paths.
  - there is no top-level `docs/` directory yet, so documentation placement needs to be decided explicitly instead of assuming `docs/academic-year-rollover.md` already fits an existing structure.
  - no obvious `MEMORY.md` / `project_enrollment_rollover.md` target exists in the workspace root or `.agents`, so memory capture should likely be folded into `.agents/worklog.md` plus a dedicated markdown doc unless the team wants a new memory convention created.
  - residual normalization risk after Phase 9 is now concentrated in the `491` legacy/orphan `student_academic_info.academic_year IS NULL` rows; this is the main cleanup item that still needs documentation and a clear non-goal boundary for the current PR.
  - notification UI work in the tree (`NotificationController`, `useNotifications`, `notifications.vue`) is already aligned with Phase 7.2 and should be treated as part of the same release narrative, not reopened in Phase 10 unless QA finds regressions.
- Recommended Phase 10 scope:
  - 10.1 cleanup = targeted grep/audit pass for enrollment snapshot writes, keeping only intentional backward-compatibility writes in `StudentEnrollmentService` and documented fallback readers; remove only truly redundant or conflicting direct writes if found.
  - 10.2 docs = add one durable enrollment rollover/repair document covering:
    - source of truth (`classroom_students`)
    - meaning of `students.class_level` / `class_section` as snapshot compatibility fields
    - Phase 9 commands, dry-run/real-run flow, and report location
    - explicit note that 491 orphan academic-info rows remain intentionally untouched
    - rollback/undo window and audit trail summary
  - 10.3 worklog sync = update `.agents/worklog.md` with final phase-closure summary, touched files, verification already run, and the residual orphan-row risk.
  - 10.4 release notes / operator notes = capture the exact post-Phase-9 dry-run expected results (`repair=0`, `patched_null_year=0`, `skipped_existing=1929`) so future operators can compare health quickly.
- Likely files for Phase 10 implementation:
  - `.agents/worklog.md`
  - `.agents/latest-analysis.md`
  - new documentation file under either `docs/` or `.agents/` after choosing a canonical location
  - optional read-only grep follow-up across `api/nuxnanravel/app` and `ui/` for direct `class_level` writes/usages
- Suggested execution order:
  1. inventory direct writes to `students.class_level` / `class_section` and label each as keep/remove/defer
  2. decide canonical doc location (`docs/` new folder vs `.agents/` operational note)
  3. write the rollover/repair doc with command examples and residual-risk section
  4. sync `.agents/worklog.md` and `.agents/latest-analysis.md`
  5. optional final read-only verification: re-open `repair-report.md` and ensure counts in docs match the recorded run
- Risks / cautions:
  - removing `class_level` writes too early can break old readers that still build image paths, labels, or filters from `students` instead of active enrollment joins.
  - documenting the 491 residual rows poorly could make the Phase 9 run look incomplete when it is actually intentionally scoped.
  - creating a brand-new memory convention in Phase 10 may add churn; prefer reusing existing repo conventions unless the team explicitly wants a new memory file.
- Verification plan for actual Phase 10 implementation:
  - read-back check for all docs/worklog changes
  - if any cleanup code change happens, rerun only the touched enrollment/notification tests plus Pint on edited PHP files

## 2026-06-21 Phase 10 implementation - Compatibility Inventory & Closure Documentation

- Branch: current working tree (uncommitted)
- Task: Complete Phase 10: build a comprehensive compatibility field inventory and write closure documentation
- Files touched:
  - [enrollment-rollover-repair.md](file:///C:/wamp64/www/nuxnan/.agents/enrollment-rollover-repair.md)
  - [worklog.md](file:///C:/wamp64/www/nuxnan/.agents/worklog.md)
  - [latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)
- Done:
  - Created nuxnan student enrollment source of truth architecture, highlighting `classroom_students` as the absolute source of truth and `students.class_level`/`students.class_section` as snapshot compatibility columns.
  - Performed a detailed reads/writes inventory on the compatibility fields `students.class_level` and `students.class_section` across the codebase.
  - Categorized usages into `Keep` (sync hooks, rollover rollback snapshots, pending intake checks, profile display, and front-end rendering) and `Defer` (member list query filtering and sorting, and student card distinct lists).
  - Summarized the Phase 9 Artisan command sequence and outcomes.
  - Detailed the remaining 491 null-year `student_academic_info` orphan records as intentionally bypassed due to lack of active enrollment data to infer from.
- Verification:
  - Read-back check successfully performed. No codebase functionality is affected as this was a documentation, inventory, and task closing phase.

## 2026-06-21 Phase 7.2 closeout - notification UI and current cycle closure

- Scope verified:
  - `api/nuxnanravel/app/Http/Controllers/Api/Play/NotificationController.php`
  - `api/nuxnanravel/tests/Feature/NotificationControllerTest.php`
  - `ui/composables/useNotifications.ts`
  - `ui/pages/notifications.vue`
- Findings:
  - the only actionable unfinished work still present in the current working tree was the Phase 7.2 notification UI/filter follow-up already described earlier in this file
  - older "remaining" notes elsewhere in `worklog.md` / `latest-analysis.md` are historical backlog and not blockers for closing this delivery cycle unless explicitly reopened
- Finalized behavior:
  - notification page now supports grouped category tabs for grade, certificate, enrollment, and rollover notifications
  - frontend uses shared notification labels/color classes/relative time helpers from `useNotifications`
  - backend `NotificationController@index` supports multi-select `types[]` filtering while preserving the older single `type` param for compatibility
- Verification:
  - `api/nuxnanravel`: `vendor\bin\pint app\Http\Controllers\Api\Play\NotificationController.php tests\Feature\NotificationControllerTest.php`
  - `api/nuxnanravel`: `php artisan test tests\Feature\NotificationControllerTest.php tests\Feature\EnrollmentNotificationListenerTest.php`
    - Result: `9 passed (36 assertions)`
  - `ui`: targeted parse check on `pages/notifications.vue`
    - Result: `frontend notification files parse ok`
- Closure note:
  - current delivery cycle can be considered closed from an implementation/verification standpoint
  - no additional actionable pending items remain in this working tree outside previously documented out-of-scope backlog and the known `491` orphan `student_academic_info` rows from Phase 9

## 2026-06-22 Academy courses tab now uses shared course card design

- Scope implemented:
  - Replaced the custom course card markup in `ui/pages/academies/[name].vue` with the shared `ui/components/learn/course/CourseCard.vue` so school course cards match the familiar general course card UI.
  - Added an optional `to` prop to `CourseCard` so this page can opt into direct navigation without changing existing parent-managed usages elsewhere.
- Files touched:
  - `ui/components/learn/course/CourseCard.vue`
  - `ui/pages/academies/[name].vue`
- Verification:
  - Read-back confirmed the academy courses grid now renders `CourseCard` directly and routes each card to `/Learn/Courses/{id}` through the new optional navigation prop.

## 2026-06-22 Planning note - academy course grouping by student segment

- Findings:
  - `ui/pages/academies/[name].vue` currently fetches `/api/academies/{id}/courses` into a flat `courses[]` list and renders all items in one grid.
  - `AcademyCourseController::getAcademyCourses()` returns a flat paginated list and no grouping/filter metadata.
  - `CourseResource` already exposes `education_level`, `education_year`, `semester`, and `academic_year`, which are usable for school-facing student-segment grouping without schema changes.
  - The academy page already has a separate classrooms data flow, so classroom-aware grouping can be added later without blocking the first pass.
- Intended files if implemented:
  - `ui/pages/academies/[name].vue`
  - optional new academy course section/filter component under `ui/components/academy/` or `ui/components/learn/academy/`
  - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyCourseController.php`
- Decision:
  - Recommend Phase 1 grouping by `education_level + education_year`, with semester/academic-year filters and collapsible sections.
  - Defer true classroom/persona-based grouping to a second phase if the school wants “ห้อง/สาย/ระดับชั้น” specific shelves.
- Risks:
  - Existing courses may have incomplete `education_level` / `education_year`, so an “อื่นๆ/ยังไม่ระบุ” bucket is required.
  - Rendering all groups expanded at once can still be heavy with 100+ courses, so lazy section expansion or paginated section loading should be planned.
- Verification plan:
  - read-back/UI parse for the academy page and targeted API response inspection for grouping/filter metadata.

## 2026-06-22 Implementation note - academy courses grouped by student segment

- Scope completed:
  - Removed the conflicting `GET /api/academies/{academy:name}/courses` route so the academy courses API resolves cleanly by academy id.
  - Updated `ui/pages/academies/[name]/admin/courses/index.vue` to load courses by `academyId`, preserving admin behavior after the route cleanup.
  - Extended `AcademyCourseController::getAcademyCourses()` with student-segment filters (`education_level`, `education_year`, `semester`, `academic_year`, `search`, `status`) plus `available_filters` metadata for the frontend.
  - Added `ui/composables/useCourseGrouping.ts` and updated `ui/pages/academies/[name].vue` to group courses by `education_level + education_year`, render collapsible sections, and expose school-facing filters above the grid.
- Verification:
  - `api/nuxnanravel`: `php artisan route:list --path=academies --name=academy.courses`
  - `api/nuxnanravel`: `php artisan test tests/Feature/Api/Academy/AcademyCourseListTest.php`
    - Result: `3 passed (12 assertions)`
  - `api/nuxnanravel`: `vendor\bin\pint app\Http\Controllers\Api\Learn\Academy\AcademyCourseController.php tests\Feature\Api\Academy\AcademyCourseListTest.php`
  - Frontend academy page was re-read around the grouped-course template and fetch logic; an automated Vue SFC parse check was attempted but blocked by sandbox read restrictions against `ui/node_modules`.
- Remaining caution:
  - The new academy page fetch currently requests `per_page=100` so grouping is useful immediately for large schools, but truly huge academies may still want a second pass with server-driven grouped pagination or lazy section loading.

## 2026-06-22 Teacher account SQL generation

- Generated `tmp/create_teacher_users.sql` from the first worksheet of the supplied teacher workbook.
- The final file contains 120 guarded `users` inserts, 120 bcrypt password hashes, and 120 unique generated emails.
- Employee IDs are normalized by removing a numeric trailing `.0` and lowercasing before generating `t<id>@jariyathum.ac.th` and the source password `jsm<id>`.
- Excluded by user request: `นาย อซิซ สาเม๊าะ`, `นางซารีนา ส่าเม๊าะ`, `นายอับดุลสุโก ดินอะ`, and `นายอ๊ะหมัด แอเก็ม`.
- Changed the generated email domain from `@jariyathum.ac.th` to `@jsm.ac.th` by user request.
- Verification: direct UTF-8 read confirmed intact Thai text, 120 insert statements, 120 bcrypt hashes, 120 unique `@jsm.ac.th` emails, no old-domain occurrences, and a complete transaction wrapper.


## 2026-06-24 User Profile Phone Number Duplication Cleanup

- Task: Delete duplicate `phone_number` in `user_profiles` + fix collation mismatch.
- Files modified:
  - `api/nuxnanravel/app/Http/Controllers/Api/SettingsController.php`
  - `api/nuxnanravel/app/Models/UserProfile.php`
  - `api/nuxnanravel/database/migrations/2026_06_24_120000_drop_phone_number_from_user_profiles_table.php` (created)
- Findings:
  - `users.phone_number` (collation: `utf8mb3_unicode_ci`) and `user_profiles.phone_number` (collation: `utf8mb3_general_ci`) had different collations, causing potential database collation mismatch issues.
  - All entries for `user_profiles.phone_number` in the database were `NULL`, making it safe to drop the column immediately.
  - Added migration with a defensive data backfill (joining on `user_id` and copying non-null phone numbers to `users.phone_number` if missing in `users`) before dropping the column.
  - Cut the dual-write behavior in `SettingsController@updateProfile` by removing `phone_number` from the `$profileData` before calling `$profile->fill($profileData)`.
  - Removed `phone_number` from `$fillable` array in `UserProfile` model.
- Verification:
  - Ran `php artisan migrate` successfully.
  - Ran `php artisan test tests/Feature/UserProfilePrivacyTest.php` successfully (5 passed, 26 assertions).
  - Ran `vendor/bin/pint` successfully.
## 2026-06-19 Nuxt duplicated student-profile imports warning

- Scope: frontend-only analysis; no application source changes made.
- Root cause: `ui/composables/useMyStudentProfile.ts` duplicates and exports eight interfaces already exported by `ui/composables/useStudentProfile.ts`: `StudentProfile`, `ClassroomInfo`, `AcademicInfo`, `StudentAddress`, `StudentContact`, `StudentGuardian`, `StudentHealthInfo`, and `AcademyInfo`.
- Nuxt auto-import scans both composables, keeps the exports from `useStudentProfile.ts`, and ignores the duplicate names from `useMyStudentProfile.ts`; generated `ui/.nuxt/imports.d.ts` confirms that resolution.
- The duplicated interface bodies are currently identical. The repeated warning blocks at startup/HMR are repeated scans, not separate defects. Runtime/build completes, but the ambiguity can hide future type-contract drift.
- Recommended fix: make `useStudentProfile.ts` the single type owner; import the shared types into `useMyStudentProfile.ts` with `import type`, keep only `MyStudentProfileData` and `STUDENT_NOT_LINKED_CODE` local, and reuse `ACCESS_LEVEL_LABELS` if appropriate.
- Verification plan: run `npx nuxt prepare` or restart `npm run dev`, confirm no duplicated-import warnings and inspect regenerated `.nuxt/imports.d.ts`; then run a focused TypeScript/build check and smoke both `/academies/:name/my-profile` and `/academies/:name/students/:id/profile`.
- Existing user work preserved: `ui/pages/academies/[name]/dashboard/student.vue` has an uncommitted quick-action link and must not be overwritten.

---

## Work Plan — Dedupe `useMyStudentProfile` Types (2026-06-19, refined v2)

### 0. ข้อค้นพบเพิ่มเติมจากการสำรวจ (กระทบทิศทางแผน)

| รหัส | สิ่งที่เจอ | นัยต่อแผน |
|---|---|---|
| **F1** | `useMyStudentProfile` ถูกใช้ที่ `ui/pages/academies/[name]/my-profile.vue` ที่เดียวเท่านั้น | blast radius เล็กมาก กล้า refactor ได้ |
| **F2** | `ProfileViewCards.vue:15` import type จาก `useStudentProfile` อยู่แล้ว → ฝั่งนี้ implicitly ยอมรับว่า `useStudentProfile` เป็น source of truth | ยืนยันทิศทาง "ใช้ useStudentProfile เป็นแหล่ง type หลัก" |
| **F3** | `useMyStudentProfile` มี inline `labels` map ซ้ำกับ `ACCESS_LEVEL_LABELS` ที่ exported อยู่แล้ว (บรรทัด 200–208) | dedup เพิ่มได้ในรอบเดียวกัน |
| **F4** | `STUDENT_NOT_LINKED_CODE` กับ `MyStudentProfileData` มีแค่ใน `useMyStudentProfile` ที่เดียว → local-only | คงไว้ในไฟล์เดิม ไม่ต้องย้าย |
| **F5** | Computed getters ทั้งสองไฟล์เกือบ identical (student, classroom, fullNameTh, ...) | ตรงนี้คือ "นัวซ้ำ" อีกชั้น แต่ **ไม่อยู่ในขอบเขตรอบนี้** — บันทึกเป็น follow-up |
| **F6** | `MyStudentProfileData` กับ `StudentProfileData` มี shape เหมือนกัน 100% | สามารถยุบเป็น type alias ของ `StudentProfileData` ได้ ลด surface อีกหนึ่งจุด |
| **F7** | คอมเมนต์ในไฟล์เขียนว่า "Types (re-exported from useStudentProfile for convenience)" แต่จริง ๆ **redeclare ไม่ใช่ re-export** → คอมเมนต์หลอก | ลบคอมเมนต์เก่าและแทนด้วยข้อเท็จจริงปัจจุบัน |
| **F8** | ทั้งสอง composable อยู่ใน `ui/composables/` ซึ่ง Nuxt auto-import scan | ไม่ต้องแตะ import ใน `my-profile.vue` หลัง refactor — symbol เดิมยังถูก resolve ผ่าน auto-import |

### 1. หลักการของรอบนี้

1. **เป้าหมายเดียว**: ทำให้ warning duplicate-import หาย โดยไม่กระทบ runtime
2. **source of truth = `useStudentProfile.ts`** ตามที่ `ProfileViewCards.vue` ใช้อยู่แล้ว (F2)
3. **ไม่ทำ refactor ขยายขอบ** เช่น ยุบ computed getters ร่วมกัน → บันทึกเป็น follow-up (F5)
4. **ไม่แตะ `my-profile.vue`** ถ้าไม่จำเป็น — symbol auto-import คงเดิม (F8)
5. **ไม่ touch ไฟล์ dashboard ที่ค้าง uncommitted** (`ui/pages/academies/[name]/dashboard/student.vue`)
6. **commit เดียวจบ** — เป็น cleanup ขนาดเล็ก ไม่ต้องแยก phase

### 2. แผนทีละขั้นตอน (single PR, ~15 นาที)

#### **Step 1 — เตรียม working tree (1 นาที)**
- `git status` ยืนยันว่ามี modified อยู่ 2 ไฟล์: `.agents/latest-analysis.md` กับ `ui/pages/academies/[name]/dashboard/student.vue`
- **ห้าม `git add -A`** — stage เฉพาะไฟล์ที่จะแก้ในรอบนี้

#### **Step 2 — แก้ `useMyStudentProfile.ts` (5 นาที)**

โครงสร้างไฟล์ใหม่:
```ts
/**
 * Composable for fetching the current user's own student profile.
 * Calls /api/academies/{academy}/students/me/profile
 * Returns the same shape as useStudentProfile so ProfileViewCards components can be reused.
 */
import { ref, computed, type Ref } from 'vue'
import { useApi } from './useApi'
import type {
  StudentProfile,
  ClassroomInfo,
  AcademicInfo,
  StudentAddress,
  StudentContact,
  StudentGuardian,
  StudentHealthInfo,
  AcademyInfo,
  StudentProfileData,
} from './useStudentProfile'
import { ACCESS_LEVEL_LABELS } from './useStudentProfile'

// MyStudentProfileData has identical shape to StudentProfileData (per /students/me/profile contract).
// Keep as type alias so future drift fails type-check loudly.
export type MyStudentProfileData = StudentProfileData

export const STUDENT_NOT_LINKED_CODE = 'STUDENT_NOT_LINKED'

export const useMyStudentProfile = (academyName: Ref<string> | string) => {
  // ... body เดิม แต่ accessLevelLabel ใช้ ACCESS_LEVEL_LABELS[accessLevel.value] || accessLevel.value
}
```

การเปลี่ยนแปลงเฉพาะจุด:
- **ลบ** 8 interface declarations (บรรทัด 17–119): `StudentProfile`, `ClassroomInfo`, `AcademicInfo`, `StudentAddress`, `StudentContact`, `StudentGuardian`, `StudentHealthInfo`, `AcademyInfo`
- **ลบ** `interface MyStudentProfileData` (บรรทัด 121–131) → แทนด้วย `export type MyStudentProfileData = StudentProfileData`
- **เพิ่ม** `import type { ... } from './useStudentProfile'` (เฉพาะ 8 type + `StudentProfileData`)
- **เพิ่ม** `import { ACCESS_LEVEL_LABELS } from './useStudentProfile'` (value import แยกจาก type import)
- **แทน** inline `labels` map ใน `accessLevelLabel` computed (บรรทัด 199–209) ด้วย `ACCESS_LEVEL_LABELS[accessLevel.value] || accessLevel.value`
- **ลบ** คอมเมนต์ "Types (re-exported from useStudentProfile for convenience)" และเปลี่ยนเป็นคำอธิบายที่ตรงข้อเท็จจริง (F7)
- **คง** ทุก computed/state/action getter เดิม — return shape ต้องเหมือนเดิม 100%

#### **Step 3 — Verify: TypeScript & auto-import (3 นาที)**
- `cd ui ; npx nuxt prepare` — สร้าง `.nuxt/imports.d.ts` ใหม่
- ตรวจ `ui/.nuxt/imports.d.ts` ว่ายังมี `useMyStudentProfile`, `MyStudentProfileData`, `STUDENT_NOT_LINKED_CODE` และไม่มี warning duplicate
- `npx vue-tsc --noEmit 2>&1 | Select-String -Pattern "useMyStudentProfile|useStudentProfile|my-profile"` — ต้องไม่มี error ใหม่จาก 2 ไฟล์นี้
- ไม่ต้อง check ทั้ง repo (CLAUDE.md ยืนยันว่ามี pre-existing errors)

#### **Step 4 — Verify: dev server warning หาย (3 นาที)**
- Restart `npm run dev` ใน `ui/` (ถ้าค้างอยู่)
- ดู console — warning เก่าจะระบุชื่อ 8 symbol → ต้องไม่ขึ้นอีก
- ถ้ายังขึ้น → ตรวจ Step 2 ว่าลบครบหรือยัง (อาจมี duplicate ใน `.d.ts` cache → ลบ `ui/.nuxt/` แล้ว `nuxt prepare` ใหม่)

#### **Step 5 — Smoke test (3 นาที)**
- เปิด `/academies/<academy>/my-profile`:
  - หน้าโหลด → ข้อมูล student แสดง, `accessLevelLabel` แสดงเป็นภาษาไทยตรง (เช่น "นักเรียน (ตัวเอง)")
  - กรณี unlinked → error message เดิมยังขึ้น
- เปิด `/academies/<academy>/students/<id>/profile`:
  - ไม่มีอะไรเปลี่ยน (regression check) — ProfileViewCards ยัง render type จาก `useStudentProfile` ถูกต้อง
- กรณีไม่สะดวกเปิด browser → ระบุชัดในรายงานว่า "skipped UI smoke" ตาม global instruction

#### **Step 6 — Commit (1 นาที)**
```
refactor(ui): dedupe student profile types in useMyStudentProfile

useMyStudentProfile.ts re-declared 8 interfaces already exported by
useStudentProfile.ts, triggering Nuxt duplicate auto-import warnings.
Make useStudentProfile the single owner; import types and the shared
ACCESS_LEVEL_LABELS map. MyStudentProfileData becomes a type alias.
```
- Stage เฉพาะ `ui/composables/useMyStudentProfile.ts` (+ `.agents/latest-analysis.md` ถ้าต้องการเก็บแผนนี้ใน commit เดียวกัน)
- **อย่า** stage `ui/pages/academies/[name]/dashboard/student.vue`

### 3. Risk & Rollback

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Type drift หาก backend ของ `/students/me/profile` กับ `/students/{id}/profile` เริ่มต่างกัน | ต่ำ ระยะสั้น / สูง ระยะยาว | กลาง | type alias จะ break พร้อมกัน → บังคับ resolve ตอนนั้น (ดี) |
| Auto-import miss `MyStudentProfileData` หลัง alias | ต่ำ | ต่ำ | Step 3 ตรวจ `.nuxt/imports.d.ts` ก่อน commit |
| Warning ไม่หายเพราะ HMR cache | กลาง | ต่ำ | ลบ `ui/.nuxt/` + restart dev (Step 4) |
| `accessLevelLabel` แสดงผลต่างจากเดิม | ต่ำ มาก | ต่ำ | ทั้งสอง map identical (verified §3 ของไฟล์) |

Rollback: `git revert <sha>` คืนค่าเดิมได้ทันที — ไม่มี migration / API change

### 4. Out of Scope (รอบนี้ — บันทึกเป็น follow-up)

- ❌ **ยุบ computed getters ร่วมกัน** (F5) — สร้าง `useStudentProfileBase(profileDataRef)` factory แล้วให้ทั้งสอง composable wrap; เลี่ยงรอบนี้เพราะเพิ่ม diff surface และอาจกระทบ caller ที่ไม่จำเป็น
- ❌ ย้าย type ไป `ui/types/student.ts` แยก — ไม่จำเป็นถ้ามี source of truth ไฟล์เดียว
- ❌ แก้ shape ของ API response (`StudentProfileData` mismatch กับ backend resource จริง) — ไม่ใช่ปัญหารอบนี้
- ❌ แตะ `useStudentProfile.ts` — ไฟล์นี้ stable อยู่แล้ว, เปลี่ยนเสี่ยง regression ทั่ว ProfileViewCards
- ❌ แตะ `dashboard/student.vue` ที่ค้าง uncommitted

### 5. Definition of Done

- [ ] `useMyStudentProfile.ts` ไม่มี `export interface` ของ 8 type ที่ซ้ำ
- [ ] `ACCESS_LEVEL_LABELS` ถูก import จาก `useStudentProfile` (ไม่มี inline duplicate map)
- [ ] `MyStudentProfileData = StudentProfileData` (alias)
- [ ] `.nuxt/imports.d.ts` regenerate แล้ว ไม่มี duplicate
- [ ] `npm run dev` ไม่ขึ้น duplicate-import warning อีก
- [ ] `/academies/:name/my-profile` ทำงานเหมือนเดิม (smoke)
- [ ] `/academies/:name/students/:id/profile` ไม่ regress (smoke)
- [ ] Commit single, ไม่ลาก `dashboard/student.vue` ติดมาด้วย

## 2026-06-19 Plan — Main dashboard entry to academy dashboard

- Scope: frontend-first planning; no feature implementation performed.
- User-visible source: `ui/components/dashboard/DashboardQuickActions.vue`, rendered by `ui/pages/Dashboard.vue`.
- Existing reusable flow:
  - Approved memberships are available from `GET /api/academies/users/{user}/membered-academies`.
  - `/academies` already lists all/membered academies and can serve as the multi-academy or empty-state destination.
  - `/academies/{name}/dashboard` already resolves the authenticated member's role and redirects to student, teacher, parent, staff, or admin dashboard.
- Recommended behavior for a new `โรงเรียนของฉัน` quick action:
  - One approved academy: link directly to `/academies/{encodedName}/dashboard`.
  - Zero or multiple approved academies: link to `/academies?view=my` (the page should honor the query and open the “ของฉัน” view).
  - Loading/API failure: keep a safe `/academies` fallback and avoid blocking the other quick actions.
- Likely files:
  - `ui/components/dashboard/DashboardQuickActions.vue`
  - `ui/pages/Dashboard.vue` only if membership state is fetched at page level and passed as typed props.
  - `ui/pages/academies/index.vue` to initialize `currentView` from `?view=my`.
  - Prefer a small shared `useMemberedAcademies` composable only if duplicate fetching remains across dashboard, academies page, and widget.
- API caution: the current endpoint accepts a route-bound `{user}` and should be reviewed to ensure callers cannot enumerate another user's memberships; a self-scoped endpoint is preferable as a follow-up if authorization is missing.
- Verification plan: test zero/one/multiple memberships, every academy role redirect, URL encoding for Thai academy names, loading/error fallback, responsive 2-column quick-action layout, and existing Earn links.
- Preserve existing user work in `ui/composables/useMyStudentProfile.ts` and `ui/pages/academies/[name]/dashboard/student.vue`.

---

## Work Plan — Quick Action "โรงเรียนของฉัน" (Refined v2, 2026-06-19)

### 0. ข้อค้นพบเพิ่มเติมจากการสำรวจโค้ดจริง (กระทบทิศทางแผนของผู้ใช้)

| รหัส | สิ่งที่เจอ | นัยต่อแผน |
|---|---|---|
| **D1** | `DashboardQuickActions.vue` ใช้ `const actions = [...]` แบบ **static array** ไม่มี state/fetch เลย — เป็น pure layout component | การเพิ่มปุ่มที่ "smart" (ปลายทางขึ้นกับจำนวนโรงเรียน) ทำให้ต้องเปลี่ยน component นี้จาก static → reactive และเพิ่ม fetch lifecycle |
| **D2** | Grid ปัจจุบันคือ `grid-cols-2` (4 ปุ่ม = 2×2) ทุก viewport | แผนผู้ใช้บอก "ปุ่มเต็มความกว้าง" ใต้ 2×2 → ทำได้ด้วย wrapper แยกหรือ grid item `col-span-2` |
| **D3** | API endpoint คือ `GET /api/academies/users/{user}/membered-academies` รับ `{user}` ใน URL → **เสี่ยง IDOR** ถ้า controller ไม่เช็คว่า `auth()->id() === $user->id` | ก่อน implement frontend ต้อง grep controller ยืนยัน authorization; ถ้าไม่มี ให้เพิ่มก่อนหรือเปลี่ยนเป็น `/me/` self-scoped |
| **D4** | `MemberedAcademiesWidget.vue` มี fetch logic เดียวกัน + ใช้ `JSON.parse(JSON.stringify(...))` workaround สำหรับ Pinia | ถ้าจะแยก composable `useMemberedAcademies` ต้องคง workaround นี้ไว้ (มี comment ระบุเป็น fix จงใจ) — มิฉะนั้น regress widget เดิม |
| **D5** | response shape ไม่แน่นอน: `response.academies?.data || response.academies` (paginated vs raw array) | composable ต้อง normalize shape ก่อนคืนค่า มิฉะนั้น caller ต้อง handle 2 รูปแบบ |
| **D6** | `/academies/{name}/dashboard/index.vue` มี router logic ของตัวเองอยู่แล้ว (call `my-role` API แล้ว redirect ไป student/teacher/parent/staff/admin) | quick action **ไม่ต้องรู้บทบาท** ส่งไป `/dashboard` ตรง ๆ ระบบเดิมจัดการเอง |
| **D7** | response item ของ membered-academies มี field `status` (1=pending, 2=approved) ดูจาก `getMemberStatusLabel` ใน widget | "1 โรงเรียน" ที่จะ deep-link ต้องนับเฉพาะ `status === 2` (approved) เท่านั้น — pending ไม่ควรพาเข้า dashboard |
| **D8** | `MemberStatus` field ของ membered-academies endpoint อาจมีค่าอื่น (rejected, suspended, ...) | filter ใน composable ใช้ `===2` ไม่ใช่ `!==1` เพื่อความเข้มงวด |
| **D9** | ชื่อโรงเรียนภาษาไทย/อักขระพิเศษ → ปัจจุบัน `/academies/${encodedName}/dashboard` ที่ใช้ `encodeURIComponent` ก็พอ — แต่ต้องไม่ลืม encode ใน quick action ด้วย | ใช้ helper `encodeURIComponent(academy.name)` ตรงจุด link |
| **D10** | Quick action เดิมเป็น `NuxtLink :to="action.link"` (static string) | สำหรับปุ่มใหม่ที่ link เปลี่ยนตาม state ต้องใช้ `computed` หรือ render แยกออกจาก loop |
| **D11** | Endpoint นี้ถูกเรียกใน widget อยู่แล้ว → ถ้า Dashboard.vue render ทั้ง `MemberedAcademiesWidget` + `DashboardQuickActions` เพิ่ม fetch ตัวเดียวกัน 2 รอบ | ระยะยาวควรย้ายไป Pinia store แต่ **รอบนี้ accept duplicate fetch** เพื่อไม่ขยาย scope (cache ที่ HTTP layer ดูแลพอ) |
| **D12** | `Dashboard.vue` ใช้ `layout: 'main'` + `middleware: ['auth']` → `user` พร้อมใช้ผ่าน auth store แน่นอน | ไม่ต้อง guard null user ภายใน quick action component (แต่เผื่อ edge case ระหว่าง logout ก็ใส่ early-return) |

### 1. หลักการของรอบนี้ (เสริม/แก้ของผู้ใช้)

1. **ไม่ทำ over-engineering**: ไม่ต้องสร้าง Pinia store ใหม่ ไม่ต้อง refactor widget เดิม — เพิ่ม composable เล็ก ๆ ใช้ร่วม 1 ตัว
2. **ปุ่มต้อง "feel instant"**: ถ้า fetch ยังไม่เสร็จ → ใช้ปลายทาง fallback `/academies` ทันที (ไม่ block, ไม่ disable ปุ่ม)
3. **Security first**: ก่อน implement ตรวจ `AcademyController::getAuthMemberedAcademies` ว่า enforce ownership หรือไม่ — ถ้าไม่ enforce เพิ่ม policy/abort ก่อน (D3)
4. **ไม่กระทบเมนู 4 ปุ่มเดิม**: ต้อง render ครบเหมือนเดิม ทุก viewport
5. **คง widget เดิมไม่แตะ**: `MemberedAcademiesWidget.vue` ไม่ต้องเปลี่ยน (D4) — เพื่อแยก concern และลด blast radius
6. **commit แยกตาม layer**: backend (ถ้ามี security fix) → composable → component → query handler ที่ academies page

### 2. Decision Matrix — ปลายทางของปุ่ม

| สถานะ | จำนวนสมาชิก approved | ปลายทาง |
|---|---|---|
| กำลังโหลด | unknown | `/academies` (safe fallback) |
| Fetch error | unknown | `/academies` |
| ไม่มี (0) | 0 | `/academies?view=all` (เปิดแท็บค้นหา ไม่ใช่ "ของฉัน" ที่ว่าง) |
| มี 1 แห่ง | 1 | `/academies/{encodeURIComponent(name)}/dashboard` |
| มีหลายแห่ง | ≥2 | `/academies?view=my` |
| มี pending อย่างเดียว | 0 approved + ≥1 pending | `/academies?view=my` (ให้เห็นสถานะรออนุมัติ) |

หมายเหตุ: เพิ่ม case "pending only" ที่ผู้ใช้ยังไม่ได้ระบุ — ถ้าส่งไป `/academies` (all) ผู้ใช้จะหา pending ของตัวเองไม่เจอ; ส่งไป `?view=my` ดีกว่า

### 3. Backend Security Pre-check (Phase 0, ~15 นาที)

ก่อนทำ frontend ทำสิ่งนี้ก่อน (ไม่ใช่ optional):

1. อ่าน `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyController.php` method `getAuthMemberedAcademies`
2. ตรวจว่ามี check pattern แบบ:
   ```php
   if (auth()->id() !== $user->id) { abort(403); }
   ```
   หรือ `$this->authorize('viewMemberships', $user)` หรือ Policy
3. ถ้าไม่มี → เพิ่ม guard ใน method นี้ (commit แยก):
   ```php
   public function getAuthMemberedAcademies(User $user, Request $request) {
       abort_unless($request->user()->id === $user->id, 403);
       // ... existing code
   }
   ```
4. รัน `php artisan test --filter=Academy` (ถ้ามี test) + manual: เรียก endpoint ด้วย user A พยายามดูของ user B → ต้องได้ 403

**ทำไมต้องทำก่อน frontend**: ถ้า controller ไม่ปลอดภัย การเพิ่มปุ่มที่ใช้ endpoint นี้แล้ว rollout ออกไปคือการขยายผิวโจมตี — แก้ตอนนี้คอมมิตเล็ก ทบทวนง่าย

**Deliverable**: 1 commit `fix(api): enforce owner check on membered-academies endpoint` (ถ้าจำเป็น)

### 4. Phase-by-Phase Plan

#### **Phase 1 — Composable `useMemberedAcademies` (~25 นาที)**

สร้างไฟล์ใหม่ `ui/composables/useMemberedAcademies.ts`:

```ts
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'

export interface MemberedAcademy {
  id: number
  name: string
  slogan?: string
  logo?: string
  status: number // 1=pending, 2=approved
  // ... fields เพิ่มเติมตาม API response
}

export const useMemberedAcademies = () => {
  const api = useApi()
  const { user } = storeToRefs(useAuthStore())

  const academies = ref<MemberedAcademy[]>([])
  const isLoading = ref(false)
  const isLoaded = ref(false)
  const error = ref<string | null>(null)

  const fetch = async () => {
    if (!user.value) return
    if (isLoaded.value) return // simple in-memory cache ต่อ instance
    isLoading.value = true
    try {
      const res: any = await api.get(
        `/api/academies/users/${user.value.id}/membered-academies`,
        { params: { per_page: 50 } } // เผื่อผู้ใช้สังกัดหลายโรงเรียน
      )
      if (res.success) {
        const list = res.academies?.data || res.academies || []
        academies.value = JSON.parse(JSON.stringify(list)) // คง workaround เดิม [[D4]]
        isLoaded.value = true
      }
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      isLoading.value = false
    }
  }

  const approved = computed(() => academies.value.filter(a => a.status === 2))
  const pending = computed(() => academies.value.filter(a => a.status === 1))

  const quickActionTarget = computed(() => {
    if (isLoading.value || !isLoaded.value) return '/academies'
    if (error.value) return '/academies'
    if (approved.value.length === 1) {
      const name = encodeURIComponent(approved.value[0].name)
      return `/academies/${name}/dashboard`
    }
    if (approved.value.length >= 2) return '/academies?view=my'
    if (pending.value.length > 0) return '/academies?view=my'
    return '/academies?view=all'
  })

  return { academies, approved, pending, isLoading, isLoaded, error, fetch, quickActionTarget }
}
```

ขอบเขตที่ตั้งใจ:
- ไม่ทำ shared state ข้าม component (เพราะใช้ที่เดียวคือ DashboardQuickActions); แต่ออกแบบให้ widget เดิม migrate ได้ในอนาคต
- `quickActionTarget` เป็น computed → reactive อัตโนมัติเมื่อ fetch เสร็จ

**Deliverable**: 1 ไฟล์ใหม่, 1 commit `feat(ui): add useMemberedAcademies composable`

#### **Phase 2 — เพิ่มปุ่ม "โรงเรียนของฉัน" ใน DashboardQuickActions (~30 นาที)**

แก้ `ui/components/dashboard/DashboardQuickActions.vue`:

โครงสร้างใหม่:
```vue
<script setup lang="ts">
import { onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const actions = [ /* 4 ปุ่มเดิม ไม่แตะ */ ]

const { fetch, quickActionTarget, isLoading, approved } = useMemberedAcademies()
onMounted(() => { fetch() })
</script>

<template>
  <div class="bg-white ... p-4">
    <h2 ...>เมนูเข้าถึงด่วน</h2>

    <div class="grid grid-cols-2 gap-2 md:gap-3">
      <NuxtLink v-for="action in actions" ... /> <!-- เดิม -->
    </div>

    <!-- ปุ่มใหม่: เต็มความกว้าง, อยู่ใต้ grid 2×2 -->
    <NuxtLink
      :to="quickActionTarget"
      class="mt-3 group flex items-center gap-3 p-3 rounded-xl
             bg-gradient-to-r from-sky-50 to-indigo-50
             dark:from-sky-900/20 dark:to-indigo-900/20
             border border-sky-100 dark:border-sky-900/30
             hover:border-sky-300 dark:hover:border-sky-600
             transition-all"
      :aria-busy="isLoading"
    >
      <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-indigo-600
                  rounded-xl flex items-center justify-center shadow-md
                  group-hover:scale-110 transition-transform">
        <Icon icon="mdi:school" class="w-5 h-5 text-white" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-gray-900 dark:text-white text-xs md:text-sm">
          โรงเรียนของฉัน
        </p>
        <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 truncate">
          <span v-if="isLoading">กำลังโหลด...</span>
          <span v-else-if="approved.length === 1">{{ approved[0].name }}</span>
          <span v-else-if="approved.length > 1">{{ approved.length }} โรงเรียน</span>
          <span v-else>ค้นหาหรือสมัครเข้าโรงเรียน</span>
        </p>
      </div>
      <Icon icon="mdi:chevron-right" class="w-5 h-5 text-gray-400
             group-hover:text-sky-600 group-hover:translate-x-1 transition-all" />
    </NuxtLink>
  </div>
</template>
```

ข้อตัดสินใจ design:
- **แยกออกจาก v-for**: ปุ่มใหม่มี shape ต่าง (full-width + secondary text) — ฝืน loop จะทำ template ซับซ้อน
- **มี subtitle ไดนามิก**: ผู้ใช้เห็นทันทีว่ามีกี่โรงเรียน — ลดความรู้สึกว่า "ไม่รู้จะเข้าไปไหน"
- **gradient โทนน้ำเงิน-คราม** ตัดกับ 4 ปุ่มเดิม (purple/emerald/amber/rose) — บอกว่าเป็นกลุ่ม navigation ไม่ใช่ Earn
- **ไม่ disable ตอน loading**: link `to="/academies"` (fallback) ยังคลิกได้ → ผู้ใช้ไม่ติด

**Deliverable**: 1 commit `feat(ui): add school quick action to dashboard`

#### **Phase 3 — รองรับ `?view=my` ใน `/academies` (~15 นาที)**

แก้ `ui/pages/academies/index.vue`:

ตรง section `// State`:
```ts
const route = useRoute()
const currentView = ref<'all' | 'my'>(
  route.query.view === 'my' ? 'my' : 'all'
)
```

เพิ่ม watch (เผื่อ navigate ไป-มาด้วย browser history):
```ts
watch(() => route.query.view, (v) => {
  currentView.value = v === 'my' ? 'my' : 'all'
})
```

ตรวจว่า `fetchMyAcademies` ถูกเรียกตอน `currentView === 'my'` หรือ onMount แล้วยัง — ถ้ายังไม่ถูกเรียก ให้ trigger ใน `onMounted` เมื่อ initial view = 'my'

**Deliverable**: 1 commit `feat(ui): honor ?view=my query on academies page`

#### **Phase 4 — Edge case & Polish (~20 นาที)**

- **4.1**: ทดสอบ academy name ที่มีอักขระพิเศษ (เช่น `โรงเรียนวัด/สวน`, `St. John's`) — `encodeURIComponent` ครอบคลุมไหม? ถ้าชื่อมี `/` ต้องไม่หลุดเป็น path segment
- **4.2**: Reduced motion: ปุ่ม hover animation มี `transition-transform` กับ `translate-x-1` — ตรวจว่าไม่กระตุก
- **4.3**: Dark mode: gradient `dark:from-sky-900/20` ตรวจคอนทราสต์
- **4.4**: Mobile (<480px): `gap-3` + 10px font ของ subtitle ไม่ overflow
- **4.5**: A11y: `aria-busy` ตอน loading; ปุ่มมี `<NuxtLink>` มี implicit role=link → screen reader ได้

**Deliverable**: 0–1 commit `fix(ui): polish school quick action visuals` (ถ้าเจอประเด็น)

### 5. Execution Order

| ลำดับ | Phase | Layer | เวลา | ขึ้นต่อ |
|---|---|---|---|---|
| 1 | 0 — Security pre-check | Backend | 15 นาที | - |
| 2 | 1 — Composable | Frontend | 25 นาที | 0 (ผ่าน) |
| 3 | 2 — Component | Frontend | 30 นาที | 1 |
| 4 | 3 — `?view=my` handler | Frontend | 15 นาที | - (parallel กับ 2) |
| 5 | 4 — Polish | Frontend | 20 นาที | 2, 3 |

**รวม ≈ 1 ชม. 45 นาที** กระจาย 3–5 commits

### 6. Verification Checklist

**Functional:**
- [ ] User ไม่มีโรงเรียน → คลิกปุ่ม → ไป `/academies?view=all`
- [ ] User มี 1 โรงเรียน approved → ไป `/academies/{name}/dashboard` → router เดิมพาไป role-specific dashboard
- [ ] User มีหลายโรงเรียน approved → ไป `/academies?view=my` → แท็บ "ของฉัน" เปิดอัตโนมัติ
- [ ] User มี pending อย่างเดียว → ไป `/academies?view=my` เห็นสถานะรออนุมัติ
- [ ] Fetch error → คลิกได้ ไป `/academies` (fallback)
- [ ] ระหว่าง loading → คลิกได้ ไป `/academies` (ไม่ block)

**Visual:**
- [ ] Mobile (380px): ปุ่ม 4 อันเดิมเป็น 2×2 + ปุ่มใหม่เต็มแถวด้านล่าง
- [ ] Tablet (800px): เหมือนเดิม
- [ ] Desktop (1280px): เหมือนเดิม
- [ ] Dark mode: gradient/border/text contrast ผ่าน
- [ ] Subtitle truncate ถูกต้องเมื่อชื่อโรงเรียนยาว

**Security:**
- [ ] User A เรียก `/api/academies/users/{B}/membered-academies` ได้ 403
- [ ] User A เรียกของตัวเอง ได้ 200

**Regression:**
- [ ] เมนู 4 ปุ่มเดิมยังคลิกได้ ไปหน้าเดิม
- [ ] `MemberedAcademiesWidget` ใน Dashboard.vue ยังโหลดและแสดงผลปกติ
- [ ] หน้า `/academies` แท็บ "ทั้งหมด" ยังเปิดเป็น default เมื่อไม่มี `?view`

### 7. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Endpoint membered-academies ไม่ได้เช็ค ownership (D3) | กลาง | สูง | Phase 0 ทำก่อน frontend |
| Fetch ซ้ำกับ MemberedAcademiesWidget (D11) | สูง | ต่ำ | ยอมรับรอบนี้; ระยะยาวย้ายไป Pinia (out of scope) |
| ชื่อโรงเรียนมี `/` หรืออักขระพิเศษ (D9) | ต่ำ | กลาง | `encodeURIComponent` + ทดสอบ 4.1 |
| `MemberedAcademy.status` ค่าจริงไม่ตรงกับ 1/2 (D8) | ต่ำ | กลาง | กรอง `=== 2` เคร่ง; log unknown ใน dev |
| `Dashboard.vue` ใส่ `key` หรือ `v-if` ที่ทำให้ DashboardQuickActions unmount/remount → fetch ซ้ำ | ต่ำ | ต่ำ | composable มี `isLoaded` flag กัน refetch |
| 4 ปุ่มเดิมเสียดุล layout เพราะมีปุ่มที่ 5 | กลาง | ต่ำ | ปุ่มใหม่อยู่ "นอก" grid (ไม่ใช่ item ที่ 5 ของ 2×2 ที่จะเหลือ 1 ช่องว่าง) |
| Subtitle "X โรงเรียน" สื่อสารไม่ชัด | ต่ำ | ต่ำ | ใช้ภาษาเดียวกับ widget เดิม |

### 8. Out of Scope (รอบนี้)

- ❌ ย้าย `MemberedAcademiesWidget` มาใช้ `useMemberedAcademies` (มีของอยู่แล้ว ใช้งานได้ — แตะคือเพิ่มความเสี่ยง)
- ❌ สร้าง Pinia store สำหรับ memberships (รอจุดที่ third caller เกิดขึ้น)
- ❌ เปลี่ยน endpoint ไปเป็น `/me/membered-academies` self-scoped (เป็น API refactor แยก — แค่เพิ่ม guard ใน controller รอบนี้พอ)
- ❌ Skeleton loader ของปุ่ม (1.5 วินาทีของ loading ไม่คุ้มทำ skeleton; subtitle "กำลังโหลด..." พอ)
- ❌ Analytics tracking ของคลิก (ทำเป็น follow-up ถ้าต้องการ)
- ❌ แก้ `useMyStudentProfile.ts` ที่ค้าง modified (เป็นอีก task — ตามแผน v2 ก่อนหน้า)

### 9. Files Touched Summary

**สร้างใหม่:**
- `ui/composables/useMemberedAcademies.ts`

**แก้ไข:**
- `ui/components/dashboard/DashboardQuickActions.vue`
- `ui/pages/academies/index.vue`
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyController.php` (เฉพาะถ้า Phase 0 พบว่าขาด guard)

**ไม่แตะ:**
- `ui/components/widgets/MemberedAcademiesWidget.vue` (D4)
- `ui/pages/Dashboard.vue`
- `ui/composables/useMyStudentProfile.ts` (modified อยู่ — เป็น task อื่น)
- `ui/pages/academies/[name]/dashboard/student.vue` (modified อยู่ — เป็น task อื่น)
- `ui/pages/academies/[name]/dashboard/index.vue` (router เดิมทำงานถูกต้องอยู่แล้ว — D6)

### 10. Definition of Done

- [ ] Backend guard ตรวจสอบและเพิ่มหากจำเป็น
- [ ] Composable `useMemberedAcademies` มี unit-test-friendly shape (return refs, no side effects ใน setup)
- [ ] ปุ่มใหม่ render ครบทุก viewport, ทุก state
- [ ] `/academies?view=my` เปิดแท็บถูกต้อง
- [ ] เมนู 4 ปุ่มเดิมไม่ regress
- [ ] Verify ทั้ง 4 กรณี (0, 1, หลาย, pending-only) ผ่าน
- [ ] Commits แยกตาม layer, revert ทีละตัวได้

---

## 2026-06-19 แผนปรับปรุงและข้อตกลงงาน (Improved Plan v2)

แผนงานนี้ได้รับการรีไฟน์เพื่อตอบรับความต้องการด้านความปลอดภัย ความเสถียรของ visual component และการป้องกัน regression ของ widget เดิมที่มีอยู่แล้ว โดยสรุปเป็นรายละเอียดดังนี้:

### 1. การตัดสินใจเชิงเทคนิคและสถาปัตยกรรม (Revised Decisions Matrix)

- **D1 (Component Shape Mismatch)**: ปุ่มใหม่ "โรงเรียนของฉัน" จะไม่ถูกยัดเข้าไปในโครงสร้าง `actions` static array ของ `DashboardQuickActions.vue` เพื่อไม่ให้เกิดการขัดกันของ data shape (เดิมเป็น static array ล้วน ไม่มี state) ปุ่มใหม่นี้จะถูกแยกออกมาเขียนอยู่ด้านนอก loop `v-for` เพื่อแยก layout และรองรับ dynamic state + dynamic subtitle ได้อย่างอิสระ
- **D3 (Backend First / Phase 0)**: endpoint `/api/academies/users/{user}/membered-academies` รับ user id ใน URL ซึ่งมีความเสี่ยงช่องโหว่ IDOR (Insecure Direct Object Reference) เราจึงบังคับให้มี **Phase 0** ในการเข้าไปตรวจสอบและเพิ่ม Security Policy / Guard ใน Controller ก่อนที่จะเริ่มทำ Frontend เสมอ
- **D4 (ห้ามแตะ Widget เดิม)**: `MemberedAcademiesWidget.vue` ดึงข้อมูลผ่าน workflow เฉพาะที่มีการใช้ `JSON.parse(JSON.stringify(...))` เพื่อแก้ปัญหา reactivity ของ Pinia การพยายามยุบรวม composable เข้าด้วยกันในจังหวะนี้อาจนำไปสู่ regression ได้ จึงกำหนดให้ **"ห้ามแตะต้อง Widget เดิมนี้อย่างเด็ดขาด"** และเลื่อนประเด็นการยุบรวมนี้เป็น Out of Scope
- **D7 (Decision Matrix - Pending Only)**: หากผู้ใช้งานมีสถานะ "รออนุมัติ" (pending - status === 1) อย่างเดียว และไม่มีโรงเรียนที่อนุมัติเลย (approved - status === 2) การนำทางจะต้องพาไปยัง `/academies?view=my` เพื่อให้เจอโรงเรียนที่รอสถานะอนุมัติของตนเอง ไม่ใช่ส่งไปหน้ารายชื่อโรงเรียนทั้งหมด (`/academies`)
- **D10 (Dynamic Subtitle)**: ตัวปุ่มต้องบอกสถานะแก่ผู้ใช้ผ่าน Subtitle ไดนามิก เช่น:
  - กำลังโหลด -> "กำลังโหลด..."
  - มี 1 โรงเรียนอนุมัติ -> แสดงชื่อโรงเรียนนั้นตรงๆ (e.g. "โรงเรียนอัสสัมชัญ")
  - มีมากกว่า 1 โรงเรียนอนุมัติ -> แสดงจำนวนโรงเรียน (e.g. "3 โรงเรียน")
  - ไม่มีโรงเรียนที่อนุมัติ แต่มีรออนุมัติ/ไม่มีเลย -> "ค้นหาหรือสมัครเข้าโรงเรียน"
- **D11 (Duplicate Fetch Acceptable)**: ยอมรับการดึงข้อมูลซ้ำซ้อนในหน้านี้ชั่วคราว (DashboardQuickActions Fetch แยกกับ Widget เดิม) เพื่อลดความซับซ้อนและเลี่ยงการสร้าง Pinia store ใหม่โดยไม่จำเป็นจนกว่าจะมี caller รายที่สามเข้ามาใช้

### 2. ลำดับเฟสการพัฒนา (Refined Phase Steps)

#### **Phase 0: Backend Security Guard (ความปลอดภัยระดับ API) (~20 นาที)**
- ตรวจสอบ `AcademyController.php` หรือ controller ที่รับผิดชอบ endpoint `/api/academies/users/{user}/membered-academies`
- เพิ่มเงื่อนไขตรวจสอบว่า `$user->id === auth()->id()` (หรือใช้ Gate / Policy) เพื่อบล็อกไม่ให้ User A เรียกดูของ User B ได้
- เขียน Test คลุมกรณีเรียกข้ามสิทธิ์ต้องคืน `403 Forbidden`

#### **Phase 1: Composable Implementation (`useMemberedAcademies`) (~25 นาที)**
- สร้าง `ui/composables/useMemberedAcademies.ts`
- ใช้ `useApi` ในการดึงข้อมูลจาก `/api/academies/users/{user.id}/membered-academies`
- กรอง `approved` เฉพาะ status === 2 และ `pending` เฉพาะ status === 1
- เขียน Logic การเปลี่ยนเส้นทางอัตโนมัติ (`quickActionTarget`):
  - โหลดไม่เสร็จ / Error -> `/academies`
  - Approved = 1 โรงเรียน -> `/academies/{name}/dashboard`
  - Approved >= 2 โรงเรียน -> `/academies?view=my`
  - Pending > 0 และ Approved == 0 -> `/academies?view=my` (เพื่อไปดูสถานะรออนุมัติ)
  - อื่นๆ -> `/academies?view=all`

#### **Phase 2: UI Button integration ใน `DashboardQuickActions.vue` (~30 นาที)**
- ดึงข้อมูลจาก composable มาใช้งาน
- แทรกปุ่ม "โรงเรียนของฉัน" ต่อท้าย grid 2x2 ของ quick action เดิม
- ตกแต่งด้วย Gradient สีน้ำเงิน-คราม แยกความต่างจากปุ่ม Earn พร้อมไอคอน `mdi:school`
- แสดง dynamic subtitle ตามกฎในข้อ D10

#### **Phase 3: query parameter view handler ใน `/academies/index.vue` (~15 นาที)**
- แก้ไขหน้า `/academies/index.vue` ให้ทำการ watch `route.query.view`
- เมื่อพบ `view === 'my'` ให้สลับแท็บไปที่ "ของฉัน" (My Academies) โดยอัตโนมัติ

#### **Phase 4: Visual Polish และ Edge Case (~15 นาที)**
- ตรวจสอบการเข้ารหัสชื่อโรงเรียนด้วย `encodeURIComponent` สำหรับกรณีที่ชื่อโรงเรียนมีอักขระพิเศษ (เช่น เครื่องหมาย `/`) ป้องกัน path แตก
- ทดสอบ dark mode contrast และ responsive layout บน viewport mobile, tablet, desktop

---

### 3. แผนการตรวจสอบและทดสอบ (Revised Verification Plan)

#### **Security Test Cases**
- [ ] **TC-SEC-01**: ใช้ Token ของ User A ยิงขอ `/api/academies/users/{User_B_Id}/membered-academies` -> คาดหวังผลลัพธ์เป็น `403 Forbidden`
- [ ] **TC-SEC-02**: ใช้ Token ของ User A ยิงขอ `/api/academies/users/{User_A_Id}/membered-academies` -> คาดหวังผลลัพธ์เป็น `200 OK`

#### **Routing & State Test Cases**
- [ ] **TC-ROUTE-01 (No Academies)**: ผู้ใช้ไม่มีสังกัดโรงเรียนใดๆ -> Subtitle แสดง "ค้นหาหรือสมัครเข้าโรงเรียน" -> คลิกปุ่มแล้วปลายทางคือ `/academies`
- [ ] **TC-ROUTE-02 (Single Approved)**: ผู้ใช้มีโรงเรียนที่อนุมัติแล้ว 1 แห่ง -> Subtitle แสดงชื่อโรงเรียนจริง -> คลิกปุ่มแล้วนำทางไปยังแดชบอร์ดเฉพาะของโรงเรียนนั้น `/academies/{encodeURIComponent(name)}/dashboard`
- [ ] **TC-ROUTE-03 (Multiple Approved)**: ผู้ใช้มีโรงเรียนที่อนุมัติแล้ว 2 แห่งขึ้นไป -> Subtitle แสดง "X โรงเรียน" -> คลิกปุ่มแล้วนำทางไปยัง `/academies?view=my`
- [ ] **TC-ROUTE-04 (Pending Only)**: ผู้ใช้มีแต่โรงเรียนที่รออนุมัติ (ไม่มีตัวที่ approved เลย) -> Subtitle แสดง "ค้นหาหรือสมัครเข้าโรงเรียน" -> คลิกปุ่มแล้วนำทางไปยัง `/academies?view=my` (เพื่อให้ผู้ใช้เจอสถานะของตัวเอง)

---

### 4. รายชื่อไฟล์ที่เกี่ยวข้อง (Files Touched Matrix)

**ไฟล์ที่จะสร้างใหม่:**
- `ui/composables/useMemberedAcademies.ts`

**ไฟล์ที่จะมีการแก้ไข:**
- `ui/components/dashboard/DashboardQuickActions.vue`
- `ui/pages/academies/index.vue`
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyController.php` (หรือ Controller ที่เหมาะสมในการทำ security guard)

**ไฟล์ที่ "ห้ามแตะต้องอย่างเด็ดขาด" (Uncommitted / Protected):**
- `ui/composables/useMyStudentProfile.ts` (ไฟล์ uncommitted เดิม - ห้ามนำมารวมใน commit นี้)
- `ui/pages/academies/[name]/dashboard/student.vue` (ไฟล์ uncommitted เดิม - ห้ามนำมารวมใน commit นี้)
- `ui/components/widgets/MemberedAcademiesWidget.vue` (ป้องกัน regression ตาม D4)
- `ui/pages/Dashboard.vue`

---

## 2026-06-19 Analysis — Academy page as a role-aware navigation hub

- Scope: UX/information-architecture analysis only; no application implementation changed.
- Target: `/academies/:name`, currently implemented by `ui/pages/academies/[name].vue`.
- Current page prioritizes school cover/profile, attendance widget, and content tabs (`ฟีด`, `รายวิชา`, `สมาชิก`, `ห้องเรียน`, `กิจกรรม`, `กลุ่ม`). It does not expose a clear “ถ้าต้องการทำ X ให้ไป Y” guide for approved members.
- Navigation is fragmented across:
  - public/member tabs in `ui/pages/academies/[name].vue`;
  - role router at `ui/pages/academies/[name]/dashboard/index.vue`;
  - role-specific quick actions in `dashboard/{student,teacher,parent,admin}.vue`;
  - the large permission-aware admin sidebar in `ui/pages/academies/[name]/admin.vue`;
  - personal pages (`my-profile`, `my-transcript`, `my-card`, `my-settings`) and school store.
- Recommended IA: place a role-aware “ศูนย์นำทางโรงเรียน” directly below the academy identity/header. It should show one primary CTA (`ไปหน้าแดชบอร์ดของฉัน`) plus task-based cards grouped by intent, not system module names.
- First-release action guide (up to 10 destinations, filtered by role/permission and route availability):
  1. เรียนต่อ/ดูรายวิชา
  2. ดูงานและแบบทดสอบ
  3. เช็กชื่อ/ดูประวัติการเข้าเรียน
  4. ดูผลการเรียน
  5. ดูโปรไฟล์ของฉัน
  6. ดูบัตรนักเรียน
  7. อ่านประกาศและกิจกรรม
  8. ดูห้องเรียน/สมาชิก
  9. ร้านค้าโรงเรียน
  10. จัดการโรงเรียน (admin/authorized staff only)
- Each card should contain an action verb title, one-line outcome, destination label, icon, and optional status/badge; unavailable actions should be hidden or explicitly marked “เร็ว ๆ นี้”, never linked to a missing page.
- Route audit found multiple current quick-action targets without matching Nuxt pages, including teacher routes (`/teacher/courses`, `/teacher/attendance`, `/teacher/gradebook`, `/teacher/assignments/create`), several parent routes, and admin targets such as `/admin/announcements/create`, `/admin/reports`, `/admin/teachers`, and `/admin/finance`. These must not be copied into the new guide until implemented or redirected to an existing destination.
- Suggested implementation files:
  - new `ui/components/academy/AcademyActionGuide.vue`;
  - `ui/pages/academies/[name].vue` for placement and academy/member context;
  - reuse `useAcademyRole` for role/permission filtering;
  - optionally centralize destination definitions in `ui/composables/useAcademyNavigation.ts` so the main page and role dashboards cannot drift.
- UX priorities: role-aware default, intent-based Thai labels, visible destination hint, no horizontal-only discovery, responsive 2-column mobile / 3–5-column desktop cards, keyboard focus, loading skeleton, and “recent/frequent” shortcuts only after the stable route map exists.
- Verification plan:
  - route existence check for every rendered link;
  - guest, pending, student, teacher, parent, staff, admin, and owner states;
  - mobile/tablet/desktop and dark mode;
  - confirm the user can locate the expected destination for 10 representative tasks without opening the admin sidebar;
  - browser visual smoke test is still required because the in-app browser connection was blocked by Windows permission during analysis; localhost itself returned HTTP 200.

---

## Work Plan — Academy Action Guide v2 (2026-06-19, refined)

ไฟล์หน้าโรงเรียนปัจจุบัน `ui/pages/academies/[name].vue` ยาว **2,282 บรรทัด** มี state ปะปนทุก tab อยู่ในไฟล์เดียว → การแทรก component ใหม่ต้องระวังเรื่อง side-effect และ payload prop เป็นพิเศษ และเป็นเหตุผลที่ต้องแยก `AcademyActionGuide.vue` เป็น component ปิด ไม่ดึง state ผ่าน parent มากเกินไป

### 0. ข้อค้นพบเพิ่มเติมจากการอ่านโค้ดจริง (เสริมบทวิเคราะห์)

| รหัส | สิ่งที่เจอ | นัยต่อแผน |
|---|---|---|
| **N1** | `[name].vue` มี local state `isAcademyAdmin = ref(false)` แยกจาก `useAcademyRole` → มีสองแหล่ง truth ของ role | guide ต้องใช้ `useAcademyRole` อย่างเดียว ไม่อ่าน `isAcademyAdmin` flag ของหน้า |
| **N2** | `useAcademyRole` มี `isOwner / isAdmin / isTeacher / isStudent / isParent / isStaff / isGuest` พร้อมใช้ + `permissions[]` array | filter destination ทำผ่าน computed ของ composable นี้โดยตรง ไม่ต้องสร้าง mapping ใหม่ |
| **N3** | มี tabs 6 ตัวอยู่แล้ว (`feed/courses/members/classrooms/events/groups`) ที่ `currentTab` ภายในหน้า | guide ที่บอก "ดูสมาชิก/ห้องเรียน" ต้องลิงก์ภายในหน้าเดิม (anchor + set tab) ไม่ใช่ route ใหม่ — ป้องกัน dead link |
| **N4** | personal pages ที่มีอยู่จริง: `my-profile.vue, my-card.vue, my-transcript.vue, my-settings.vue, store.vue` (ตรวจ ls ตรง) | 5 destination นี้ปลอดภัย ลิงก์ตรงได้ |
| **N5** | dashboard role files มีครบ: `student/teacher/parent/admin` + `index.vue` (router) — **ไม่มี `staff.vue`** | ถ้า role = `staff`/`finance_staff` ต้องไม่ลิงก์ไป `/dashboard/staff` ที่ไม่มีไฟล์ → fallback ไป `/dashboard` ให้ router ตัดสินใจ หรือซ่อนปุ่ม dashboard |
| **N6** | `useAcademyRole.isStaff` รวม `finance_staff` ด้วย แต่ destination "จัดการการเงิน" ปัจจุบันไม่มีหน้า `/admin/finance` | ใช้ permission-based filter (เช่น `finance.view`) ไม่ใช่ role name อย่างเดียว |
| **N7** | `admin.vue` คือ layout sidebar ของ admin section — entry point จริงคือ `/academies/{name}/admin` | "จัดการโรงเรียน" ลิงก์ตรงไป `/admin` พอ ไม่ต้องเดา subpath |
| **N8** | บทวิเคราะห์ระบุว่า teacher routes (`/teacher/courses` ฯลฯ) ส่วนใหญ่ยังไม่มีไฟล์ | สำหรับ teacher rounds นี้ลิงก์ "สอนของฉัน" ส่งไป `/dashboard/teacher` (มีจริง) เท่านั้น ไม่ใส่ subpath |
| **N9** | ไม่มีการระบุ permission constant ใน `useAcademyRole.ts` — ใช้แค่ string match บน `permissions[]` | ต้องตรวจ permission key จริงที่ backend ส่ง (Phase 0.2) ก่อนใส่ filter ที่ frontend |
| **N10** | `[name].vue` middleware = `auth` → guest จริง ๆ ไม่เข้าหน้านี้ — แต่ `isGuest` ของ composable หมายถึง "ล็อกอินแต่ไม่ใช่สมาชิก approved" | "guest state" ของ guide ต้องโชว์เป็น "เข้าร่วมโรงเรียน" + 2 destination สาธารณะ (ฟีด, รายวิชา) เท่านั้น |
| **N11** | ขนาดไฟล์ parent ใหญ่มาก (~2300 บรรทัด) แตะแล้วเสี่ยง merge conflict | แทรกเฉพาะ `<AcademyActionGuide :academy="academy" />` หนึ่งบรรทัด + import — ไม่ refactor อย่างอื่น |
| **N12** | บทวิเคราะห์เสนอ `useAcademyNavigation.ts` แบบ central registry แต่หน้านี้เป็น caller แรกและเดียว | composable นี้ตั้งใจให้ role dashboards (`dashboard/student.vue` ฯลฯ) มาใช้ร่วมในอนาคต — ออกแบบ API ให้ตอบโจทย์นั้นตั้งแต่แรก แต่ **ไม่ migrate dashboard pages ในรอบนี้** |

### 1. หลักการของรอบนี้

1. **Intent-based, ไม่ใช่ module-based** — labels เริ่มจากกริยา "ดู/เช็ก/อ่าน/จัดการ" ไม่ใช่ชื่อระบบ ("LMS", "HRIS")
2. **Route-existence gate ตั้งแต่ design time** — destination ทุกอันต้องผ่าน checklist "มีไฟล์จริงไหม"; ที่ไม่มีให้ตัดทิ้งจาก v1 ไม่ใช่ใส่ `เร็ว ๆ นี้`
3. **Permission-driven, role-augmented** — กรองหลักด้วย `permissions[]` ตามด้วย role flag; ลด hard-code
4. **Single source of truth ของ destination list** — central registry ใน `useAcademyNavigation.ts` เพื่อกัน drift ระหว่าง guide กับ role dashboards ในอนาคต
5. **ไม่แตะ parent page logic** — เพิ่ม import + 1 ตำแหน่ง render เท่านั้น
6. **Mobile-first, dark mode parity** — ทดสอบ 380/800/1280 + dark mode ก่อน commit

### 2. Destination Registry Contract

```ts
// ui/composables/useAcademyNavigation.ts
export interface AcademyDestination {
  id: string                       // 'continue-learning', 'my-profile', ...
  title: string                    // "เรียนต่อ"
  outcome: string                  // "ดูรายวิชาที่กำลังเรียนอยู่"
  icon: string                     // 'mdi:book-open-page-variant'
  to: string | { path: string; query?: Record<string, string>; hash?: string }
  destinationLabel: string         // "หน้ารายวิชา" (ใต้ปุ่ม)
  intent: 'learn' | 'evaluate' | 'identity' | 'community' | 'commerce' | 'manage'
  visibleWhen: (ctx: NavContext) => boolean
  badge?: { type: 'count' | 'dot' | 'text'; value?: string | number }
  color?: 'sky' | 'amber' | 'emerald' | 'rose' | 'violet' | 'slate'
  order: number
}

interface NavContext {
  academyName: string              // encoded
  role: ReturnType<typeof useAcademyRole>
  hasStudentLink: boolean          // มี student record ใน academy นี้
  hasParentLink: boolean
  isMember: boolean                // status === approved
  isPending: boolean
}
```

**ข้อบังคับ:**
- `to` ทุกค่าผ่านฟังก์ชัน `assertRouteExists()` ที่ dev time (throw warning) — ป้องกัน dead link ในตอน build
- `outcome` ต้องเป็นประโยคสมบูรณ์ผลลัพธ์ — ผู้ใช้อ่านแล้วต้องรู้ว่าได้อะไรกลับมา ไม่ใช่ "ระบบรายวิชา"

### 3. Destination List v1 (สูงสุด 10, intent-grouped)

| # | id | title | outcome | to | visible | source |
|---|---|---|---|---|---|---|
| 1 | `my-dashboard` | ไปแดชบอร์ดของฉัน | "เปิดหน้าตามบทบาทของคุณ" | `/academies/{n}/dashboard` | `isMember && role!=='staff'` (N5) | dashboard/index.vue router |
| 2 | `continue-learning` | เรียนต่อ | "ดูรายวิชาที่กำลังเรียนอยู่" | `/academies/{n}#courses` (hash → set currentTab) | `isStudent ‖ isMember` | tab `courses` ใน N3 |
| 3 | `my-assignments` | งาน/แบบทดสอบของฉัน | "ดูงานค้างและกำหนดส่ง" | `/academies/{n}/dashboard/student` (จนกว่าจะมีหน้าเฉพาะ) | `isStudent` | student dashboard |
| 4 | `my-attendance` | เช็กชื่อ/ประวัติเข้าเรียน | "ดูสถิติการเข้าเรียนของคุณ" | `/academies/{n}/attendance` | `isStudent ‖ isParent` | folder `attendance/` มีจริง |
| 5 | `my-transcript` | ผลการเรียน | "ดูคะแนนและเกรดของคุณ" | `/academies/{n}/my-transcript` | `isStudent` | N4 |
| 6 | `my-profile` | โปรไฟล์ของฉัน | "ดู/แก้ไขข้อมูลนักเรียน" | `/academies/{n}/my-profile` | `hasStudentLink` | N4 |
| 7 | `my-card` | บัตรนักเรียน | "ดูบัตรนักเรียนของคุณ" | `/academies/{n}/my-card` | `hasStudentLink` | N4 |
| 8 | `members-and-classrooms` | ห้องเรียน/สมาชิก | "ค้นหาเพื่อนร่วมโรงเรียน" | `/academies/{n}#classrooms` | `isMember` | tab N3 |
| 9 | `announcements` | ประกาศและกิจกรรม | "อ่านประกาศและกำหนดการ" | `/academies/{n}#events` | `isMember` | tab `events` N3 |
| 10 | `school-store` | ร้านค้าโรงเรียน | "ซื้อสินค้า/แต้มของโรงเรียน" | `/academies/{n}/store` | `isMember` | N4 |
| 11* | `manage-school` | จัดการโรงเรียน | "เข้าหน้าจัดการสำหรับผู้ดูแล" | `/academies/{n}/admin` | `isAdmin ‖ isOwner ‖ has 'academy.manage' permission` | N7 |
| 12* | `pending-status` | สถานะการสมัคร | "ดูสถานะคำขอเข้าร่วม" | `/academies/{n}` (banner inline) | `isPending` | guard state |

\* รายการที่ 11/12 จะใส่หรือไม่ใส่ขึ้นกับ role; UI สูงสุดยังคงโชว์ 10 cards ในครั้งเดียว — ตัดด้วย `order + slice(0,10)` หลัง filter

**ถูกตัดจาก v1 (เพราะ N5/N6/N8 — ไฟล์ไม่มี):**
- ❌ `teacher-courses`, `teacher-attendance`, `teacher-gradebook`, `teacher-assignment-create`
- ❌ `admin-announcements-create`, `admin-reports`, `admin-teachers`, `admin-finance`
- ❌ "ดูผู้ปกครอง" (parent module ยังกระจัด)
- → บันทึกใน `Out of Scope` พร้อม route paths ที่รอ implement

### 4. Phase-by-Phase Plan

#### **Phase 0 — Route-existence Audit (~30 นาที, blocking)**

เป้า: เคลียร์ route map ก่อนเขียน registry

ขั้นตอน:
- **0.1** `glob` หาไฟล์จริงทั้งหมดของ destination 12 รายการ + alternative ที่บทวิเคราะห์ระบุ:
  ```powershell
  Get-ChildItem ui/pages/academies/[name] -Recurse -Filter *.vue |
    Select-Object FullName
  ```
- **0.2** เปิด backend `php artisan route:list --path=academy` + ตรวจ `RolePermission` enum/seeder หา permission key จริง (เช่น `academy.manage`, `finance.view`) — เพื่อให้ `visibleWhen` ใช้ key ถูกต้อง
- **0.3** สำหรับ destination ที่ resolve แล้วชี้ไปไฟล์ไม่พบ → ลบหรือ downgrade ไปไฟล์ใกล้เคียงที่มีอยู่; **ห้ามใส่ `#` หรือ `เร็ว ๆ นี้` ในรอบนี้**
- **0.4** บันทึก audit ผลลัพธ์ใน `.agents/academy-routes-audit.md` (1 ตาราง: destination id → resolved path → exists ✓/✗)

**Deliverable:** ไฟล์ audit + เวอร์ชันสุดท้ายของตารางใน §3

**ทำไม blocking:** ถ้าข้าม จะ ship dead links ทันที — ขัดหลักการ §1.2

---

#### **Phase 1 — Composable `useAcademyNavigation` (~45 นาที)**

ขั้นตอน:
- **1.1** สร้าง `ui/composables/useAcademyNavigation.ts` ตาม interface §2
- **1.2** Export `useAcademyNavigation(academyName, roleRef)` คืน:
  ```ts
  {
    allDestinations: AcademyDestination[],  // raw registry (สำหรับ test)
    visibleDestinations: ComputedRef<AcademyDestination[]>,  // filtered + sorted + sliced(0,10)
    primaryCta: ComputedRef<AcademyDestination | null>,      // id='my-dashboard' หรือ fallback
    groupedByIntent: ComputedRef<Record<string, AcademyDestination[]>>,
    pendingHint: ComputedRef<string | null>                  // ข้อความสำหรับ pending banner
  }
  ```
- **1.3** เขียน registry เป็น **constant array** ในไฟล์เดียวกัน (ไม่แยกไฟล์ data) เพื่อให้ตัดสินใจ visible จาก closure ของ ctx ตรง ๆ
- **1.4** Helper `buildHref(to, encodedName)`: รองรับทั้ง string, object, และ hash (`#tabId`) — return string สำหรับ `<NuxtLink :to>`
- **1.5** ในโหมด dev: console.warn เมื่อ `to` ที่ resolved ไม่ตรงกับ `useRouter().resolve()` (ใช้ best-effort, ไม่ throw)

**Deliverable:** 1 composable + 1 commit `feat(ui): add useAcademyNavigation composable`

---

#### **Phase 2 — Component `AcademyActionGuide.vue` (~60 นาที)**

โครงสร้าง:
```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academy: { id: number; name: string } | null
  isPending?: boolean
}>()

const academyId = computed(() => props.academy?.id ?? null)
const encodedName = computed(() => encodeURIComponent(props.academy?.name ?? ''))

const role = useAcademyRole(academyId)
const { visibleDestinations, primaryCta, pendingHint } =
  useAcademyNavigation(encodedName, role, { isPending: () => !!props.isPending })

onMounted(() => { if (academyId.value) role.fetchRole(academyId.value) })
</script>

<template>
  <section v-if="academy" class="bg-white dark:bg-gray-900 rounded-2xl p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800">
    <header class="flex items-center justify-between mb-3">
      <div>
        <h2 class="font-bold text-base md:text-lg">ศูนย์นำทางโรงเรียน</h2>
        <p class="text-xs text-gray-500">เลือกสิ่งที่อยากทำ — เราจะพาไปให้ถึง</p>
      </div>
      <NuxtLink v-if="primaryCta" :to="primaryCta.to"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700">
        <Icon :icon="primaryCta.icon" class="w-4 h-4" />
        {{ primaryCta.title }}
      </NuxtLink>
    </header>

    <div v-if="pendingHint" class="mb-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200 text-sm">
      {{ pendingHint }}
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-3">
      <NuxtLink v-for="dest in visibleDestinations" :key="dest.id" :to="dest.to"
        class="group p-3 rounded-xl border border-gray-100 dark:border-gray-800
               hover:border-sky-300 hover:shadow-md transition-all
               bg-gradient-to-br from-white to-gray-50
               dark:from-gray-900 dark:to-gray-800/50">
        <div class="flex items-center gap-2 mb-1">
          <Icon :icon="dest.icon" class="w-5 h-5 text-sky-600 dark:text-sky-400" />
          <span class="font-semibold text-sm">{{ dest.title }}</span>
        </div>
        <p class="text-xs text-gray-500 line-clamp-2">{{ dest.outcome }}</p>
        <p class="mt-2 text-[10px] text-gray-400 flex items-center gap-1">
          <Icon icon="mdi:arrow-right" class="w-3 h-3" />
          {{ dest.destinationLabel }}
        </p>
      </NuxtLink>
    </div>

    <p v-if="!visibleDestinations.length" class="text-sm text-gray-500 text-center py-6">
      ไม่มีเมนูที่ใช้งานได้สำหรับบทบาทปัจจุบัน
    </p>
  </section>
</template>
```

ข้อตัดสินใจ design:
- **2-col mobile / 3-col tablet / 5-col desktop** — รอง 10 cards ได้ใน 2 แถวบน desktop
- **Primary CTA แยกที่ header** — บทวิเคราะห์ระบุ "หนึ่ง CTA หลัก + cards" — ปุ่ม `my-dashboard` ไม่ต้องอยู่ใน grid (ลด 1 ช่อง)
- **Pending banner** — แสดงเมื่อ `isPending` (รอนุมัติ) ก่อน grid; ไม่ duplicate กับ cards
- **Icon-first, outcome-second** — ตรงกับ "intent-based"
- **Loading**: ไม่ใส่ skeleton ใน v1 — `useAcademyRole` resolve เร็ว, fallback "ไม่มีเมนู" รับได้

**Deliverable:** 1 component + 1 commit `feat(ui): add AcademyActionGuide component`

---

#### **Phase 3 — Integration กับหน้า `[name].vue` (~20 นาที)**

เป้า: แทรก guide ใต้ header academy โดย**ไม่แตะ logic อื่น**

ขั้นตอน:
- **3.1** ที่หัวไฟล์เพิ่ม import: `import AcademyActionGuide from '~/components/academy/AcademyActionGuide.vue'`
- **3.2** หา section ที่ render academy cover/profile header (ก่อน `<div v-else-if="currentTab === 'feed'">`) แทรก:
  ```vue
  <AcademyActionGuide
    :academy="academy"
    :is-pending="academy?.member_status === 'pending'"
    class="mb-4 md:mb-6"
  />
  ```
- **3.3** เพิ่ม watcher บน `currentTab` เพื่อรับ `route.hash` (`#courses`, `#classrooms`, `#events`): หากมี hash ที่ตรง tab id → set `currentTab` ใน `onMounted` (เพื่อให้ลิงก์จาก guide ทำงาน)
  ```ts
  onMounted(() => {
    const hash = route.hash.replace('#', '')
    if (tabs.some(t => t.id === hash)) currentTab.value = hash
  })
  ```
- **3.4** ทดสอบว่า academy ที่ user ไม่ได้เป็นสมาชิก (เปิดดูทั่วไป) ยังเห็น guide ที่กรอง destination เหลือเฉพาะตัวเปิดสาธารณะ

**Deliverable:** 1 commit `feat(ui): mount academy action guide on academy page`

---

#### **Phase 4 — Empty/Edge States & A11y Polish (~30 นาที)**

- **4.1** Guest (logged-in แต่ไม่เป็นสมาชิก, N10): แสดงเฉพาะ "เรียนต่อ" + "ห้องเรียน/สมาชิก" (preview) + primary CTA แทนด้วย "เข้าร่วมโรงเรียน" (เรียก existing join flow)
- **4.2** Pending: ซ่อน primary CTA "my-dashboard"; pending banner ขึ้นพร้อมข้อความ "คำขอของคุณกำลังรออนุมัติ"
- **4.3** Staff/finance_staff (N5): primary CTA ไป `/admin` ถ้ามี permission; else fallback ไป `/dashboard` index
- **4.4** A11y:
  - `<section>` มี `aria-labelledby` ผูกกับ `<h2>` id
  - แต่ละ card ใช้ `<NuxtLink>` (role=link auto) — ตรวจว่า `outcome` ไม่อยู่ใน `aria-hidden`
  - Focus ring มองเห็น: `focus-visible:ring-2 focus-visible:ring-sky-500`
- **4.5** Reduced motion: `motion-reduce:transition-none motion-reduce:transform-none` บน hover effects

**Deliverable:** 1 commit `feat(ui): refine academy guide for edge states and a11y`

---

#### **Phase 5 — Browser Verification (~30 นาที)**

- **5.1** `cd ui ; npm run dev` เปิด `/academies/<thai-name>/`
- **5.2** ทดสอบ 4 บทบาท (สลับ user ใน DB หรือใช้ seeded users):
  - student → เห็น cards 1-7,8,9,10
  - teacher → เห็น cards 1,2,8,9,10 + manage-school **ถ้ามี permission**
  - parent → เห็น cards 1,4,8,9,10
  - admin/owner → เห็นครบ + `manage-school`
  - guest (ออกจาก membership) → เห็น 2 cards + join CTA
  - pending → เห็น banner + cards limited
- **5.3** ทดสอบ 3 viewport (380/800/1280) + dark mode toggle
- **5.4** Click ทุก destination → ต้องไม่มี 404 (ตรวจ Network tab ดู status)
- **5.5** ลอง `/academies/<name>#courses` ใน URL → guide + tab `courses` ต้องเปิดพร้อมกัน

หากเปิดบราวเซอร์ไม่ได้ → ระบุ "skipped UI smoke" ใน report ตามแนวทาง CLAUDE.md

**Deliverable:** report ใน `.agents/worklog.md`

### 5. Execution Order & Time Budget

| ลำดับ | Phase | Layer | เวลา | ขึ้นต่อ |
|---|---|---|---|---|
| 1 | 0 — Route audit | Ops/Backend | 30 นาที | - |
| 2 | 1 — Composable | Frontend | 45 นาที | 0 |
| 3 | 2 — Component | Frontend | 60 นาที | 1 |
| 4 | 3 — Integration | Frontend | 20 นาที | 2 |
| 5 | 4 — Edge & a11y | Frontend | 30 นาที | 3 |
| 6 | 5 — Browser verify | QA | 30 นาที | 4 |

**รวม ≈ 3 ชม. 35 นาที** กระจาย 4–5 commits (revert ทีละขั้นได้)

### 6. Verification Checklist

**Functional:**
- [ ] Student: เห็น "ผลการเรียน, โปรไฟล์, บัตรนักเรียน" ครบ คลิกแล้วเปิดหน้าจริง
- [ ] Teacher: ไม่เห็น "บัตรนักเรียน" (`hasStudentLink=false`); เห็น "จัดการ" เฉพาะมี permission
- [ ] Parent: เห็น "เช็กชื่อบุตร", ไม่เห็น "งานของฉัน"
- [ ] Admin: เห็น "จัดการโรงเรียน" ลิงก์ `/admin` ใช้งานได้
- [ ] Owner: เห็นครบ + primary CTA ไม่หาย
- [ ] Guest (ไม่เป็นสมาชิก): เห็น CTA "เข้าร่วม" + cards preview
- [ ] Pending: เห็น banner สถานะ + ไม่เห็น primary "my-dashboard"
- [ ] Hash navigation: `#courses` เปิด tab + scroll ถูกตำแหน่ง

**Route integrity:**
- [ ] ไม่มี `<NuxtLink>` ใน guide ที่ resolve เป็น 404
- [ ] Route audit (Phase 0.4) ตรงกับ destination ที่ render จริง

**Visual:**
- [ ] Mobile 380px: 2 col, line-clamp ทำงาน, ไม่ overflow
- [ ] Desktop 1280px: 5 col, 10 cards พอดี 2 แถว
- [ ] Dark mode: คอนทราสต์ผ่าน WCAG AA สำหรับ title และ outcome
- [ ] Focus ring มองเห็น

**A11y:**
- [ ] Tab key เดินผ่าน primary CTA → cards ตามลำดับ
- [ ] Screen reader อ่าน title + outcome ของแต่ละ card
- [ ] `prefers-reduced-motion` ปิด hover transform

**Regression (parent page):**
- [ ] 6 tabs เดิมทำงานเหมือนเดิม
- [ ] Feed, courses list, members, classrooms, events, groups load ปกติ
- [ ] Loading state ของ `[name].vue` ไม่กระทบ guide (guide รอ `academy` truthy ผ่าน `v-if`)

### 7. Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Route audit เจอ dead link จำนวนมาก → ตัด v1 เหลือไม่ถึง 10 | กลาง | กลาง | ยอมรับ v1 น้อยกว่า 10 ดีกว่า dead link; บันทึก follow-up |
| `useAcademyRole` ส่งคืนช้าทำให้ guide กระพริบ (cards เปลี่ยน) | กลาง | ต่ำ | ใช้ `v-if="role.isLoaded"` ห่อ grid + แสดง shell loading 200ms |
| Hash navigation ชนกับ scroll-to ของ Nuxt | ต่ำ | ต่ำ | ใช้ `app.config` `scrollBehavior` เดิม; ทดสอบ Phase 5.5 |
| Permission key string ไม่ตรง backend (N9) | กลาง | กลาง | Phase 0.2 ตรวจ + ใส่ logging warn ถ้าไม่ match |
| Parent page (2282 บรรทัด) มี state ที่เปลี่ยนแล้วทำให้ guide unmount/remount | ต่ำ | กลาง | guide รับเฉพาะ `academy` prop, ไม่ผูก `currentTab` |
| Encoding ชื่อโรงเรียนไทยที่มี `/` | ต่ำ | กลาง | ใช้ `encodeURIComponent` ใน composable, ทดสอบกับชื่อจริงในที่อยู่ปัจจุบัน |
| Pending status field name ใน `academy` payload ไม่ตรง (`member_status`) | กลาง | ต่ำ | Phase 0.2 confirm field; fallback `false` ถ้าไม่มี |
| Cards >10 หลัง filter | ต่ำ | ต่ำ | `slice(0,10)` ใน `visibleDestinations` |
| guide ทำให้ LCP ของหน้าเลื่อน เพราะ render เพิ่ม | ต่ำ | ต่ำ | guide render หลัง `academy` truthy; ไม่มี API call นอกจาก role (มีอยู่แล้ว) |

### 8. Out of Scope (รอบนี้ — บันทึก follow-up)

- ❌ Teacher quick actions ครบชุด (`/teacher/courses`, `/teacher/gradebook`, `/teacher/assignments/create`) — รอ pages
- ❌ Admin sub-destinations (`/admin/announcements/create`, `/admin/reports`, `/admin/finance`, `/admin/teachers`) — รอ pages
- ❌ Parent module enhancements (รอ flow แยก)
- ❌ "Recent/Frequent" personalization — รอ stable route map (บทวิเคราะห์ระบุ)
- ❌ Migration ของ role dashboards (`dashboard/{student,teacher,parent,admin}.vue`) มาใช้ registry เดียวกัน — รอ guide stable 1 sprint
- ❌ i18n เต็มชุด (รอบนี้ใส่ไทยตรง — สอดคล้องกับหน้าอื่นในระบบ)
- ❌ Animation/transition ของ card hover ซับซ้อน — เน้น functionality
- ❌ A/B test ของ position (above feed vs sidebar)

### 9. Files Touched Summary

**สร้างใหม่:**
- `ui/composables/useAcademyNavigation.ts`
- `ui/components/academy/AcademyActionGuide.vue`
- `.agents/academy-routes-audit.md` (Phase 0)

**แก้ไข (minimal):**
- `ui/pages/academies/[name].vue` (+import, +1 บรรทัด render, +hash watcher ~5 บรรทัด)

**ไม่แตะ:**
- `ui/composables/useAcademyRole.ts` (อ่านอย่างเดียว)
- `ui/pages/academies/[name]/dashboard/*.vue` (รอ migration phase ถัดไป)
- `ui/pages/academies/[name]/admin.vue` (sidebar เดิม)
- `ui/pages/Dashboard.vue`
- ไฟล์ uncommitted: `useMyStudentProfile.ts`, `dashboard/student.vue`

### 10. Definition of Done

- [ ] Route audit เสร็จ บันทึกใน `.agents/academy-routes-audit.md`
- [ ] composable `useAcademyNavigation` มี registry + filter logic + `visibleDestinations`/`primaryCta`/`pendingHint`
- [ ] component `AcademyActionGuide.vue` render 6 บทบาทถูกต้อง
- [ ] Parent page แตะแค่ import + 1 component tag + hash watcher
- [ ] ไม่มี dead link
- [ ] Verify 6 บทบาท × 3 viewport + dark mode ผ่าน
- [ ] 4–5 commits แยกตาม phase, revert ได้ทีละขั้น
- [ ] อัพเดท `.agents/worklog.md`

## 2026-06-22 Analysis — Thai default locale still renders academy tabs in English

- Scope: frontend-only diagnosis and implementation planning; no application source was changed.
- Confirmed configuration:
  - `ui/nuxt.config.ts` has used `defaultLocale: 'th'` and browser-language detection since the initial repository commit.
  - Browser detection is enabled with cookie key `i18n_redirected`, and Nuxt i18n 10.2.4 resolves the initial locale in this order: cookie, request/browser language, detection fallback (`th`), then current/default locale.
  - Therefore `defaultLocale: 'th'` does not force Thai when a valid `i18n_redirected=en` cookie exists or when root-page browser detection selects English.
- Confirmed academy-tab history:
  - Before commit `7ab23513` on 2026-06-22 08:04 +07, `ui/pages/academies/[name].vue` hardcoded `Feed`, `Courses`, `Members`, `Classrooms`, `Events`, `Groups`, and `About`.
  - Commit `7ab23513` changed the tabs to `useI18n()` keys and added matching Thai/English messages.
  - Current `main` contains that commit through merge `103e0eee`.
- Additional gaps:
  - No application language switcher or `setLocale()` usage exists, so a user whose cookie/browser selects English has no supported UI path back to Thai.
  - `ui/pages/nuxnan-admin/settings.vue` displays a “default language” field, but its save action is simulated and does not affect Nuxt i18n/runtime configuration.
  - No browser/dev-server session was attached during analysis, so the exact active cookie and whether the screenshot came from a stale pre-merge bundle could not be observed directly.
- Recommended product decision:
  - If the requirement is “Thai for first-time users, but preserve an explicit user choice,” disable automatic browser-language selection and add a real language switcher that writes the locale cookie.
  - If the requirement is “Thai only for everyone,” disable browser detection and remove/ignore English selection; this is simpler but discards the existing English locale.
- Likely implementation files:
  - `ui/nuxt.config.ts`
  - New `ui/components/layout/LanguageSwitcher.vue` or a minimal control integrated into `ui/layouts/main.vue`
  - `ui/i18n/locales/th.json` and `ui/i18n/locales/en.json` only for switcher labels if existing keys are insufficient
  - Optional focused test under the frontend test setup, if introduced; otherwise use runtime/browser verification
- Verification plan:
  - Restart/rebuild Nuxt to rule out stale bundles.
  - Test clean browser state, `i18n_redirected=en`, `i18n_redirected=th`, English browser preference, Thai browser preference, direct academy URL, and navigation from `/`.
  - Confirm academy tabs react immediately after a supported locale switch and persist after reload.
  - Confirm SSR HTML `lang`, hydration, and other existing `useI18n()` consumers remain consistent.

## 2026-07-02 — Attendance Phaser PolygonPoint cleanup

- Scope: frontend-only TypeScript cleanup in `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`.
- Replaced the redundant `PolygonPoint`/`PolyArg` aliases with semantic `FacePoints` (`Phaser.Math.Vector2[]`) and a `facePoint()` constructor helper.
- All desk face arrays now contain real Phaser `Vector2` instances, so the eight `fillPoints()`/`strokePoints()` casts were removed.
- Verification: focused `vue-tsc --noEmit` output contains no errors for `attendancePhaserScene.ts`; `git diff --check` passes.

## 2026-07-02 — Attendance Phaser LEAVE hatch overlay

- Scope: frontend-only visual refinement in `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`.
- Kept the existing larger torso and shoulder silhouette, and added a named `leaveHatch` graphics overlay across the body so `ATTENDANCE_STATUS.LEAVE` reads differently from `ABSENT` even beyond alpha reduction.
- Wired the hatch into both `createSeat()` and `updateSeatStatuses()` so it appears, disappears, and stays in sync during differential updates.
- Verification: focused `vue-tsc --noEmit` output contains no errors for `attendancePhaserScene.ts`; visual browser verification is still pending.
## 2026-07-02 — Lesson topics shown directly

- Scope: frontend-only UX fix in `ui/components/learn/course/lesson/LessonPost.vue`.
- Removed the outer “show/hide all topics” control and its local state; each lesson now renders its `TopicAccordion` list immediately.
- Per-topic accordion behavior, topic management, reading progress, and the admin reorder control remain unchanged.
- Source inspection confirms the removed state/control has no remaining references. Browser smoke reached `/auth`, so authenticated visual verification remains pending.

## 2026-07-02 — Admin lesson-index left widget analysis

- Scope: frontend-only planning; no application source changed for this request.
- `CoursePageShell.vue` intentionally reserves the left column on the lessons index, but currently fills it only for enrolled non-admin learners; course admins therefore get an empty left column.
- Best existing fit: `CourseLessonsMenu.vue`, used as a lesson table of contents with topic counts and lesson links. It is more task-relevant than generic recent/favorite-course widgets and complements the instructor/course widgets already shown on the right.
- Recommended implementation: render `CourseLessonsMenu` in a sticky left stack for admins on the lessons index; retain the learner progress widgets for non-admin members. Add an admin create-lesson shortcut and normalize its status display to `publication_status` if needed.
- Verification: admin/member/guest role checks, empty/loading/many-lessons states, desktop balance, mobile slide-out panel, and no duplicate lesson fetch.

## 2026-07-02 — Phaser classroom teacher patrol O3 think dots

- Scope: frontend-only refinement in `ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`.
- Implemented `showThinkDots()` to render three horizontal dots above the teacher's head during the long return path delay.
- Managed animations with sequential stagger and scale/alpha fade-in pop (using `Back.Out` ease) matching the design language.
- Added cleanups to `destroyThinkDots()` during tween start, patrol stoppage, and loop stale checks.
- Verified that it respects reduced-motion preferences (`prefersReducedMotion()`).

