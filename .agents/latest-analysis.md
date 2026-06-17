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
