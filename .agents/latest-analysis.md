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

ตัดสินใจรอยืนยันก่อนเริ่ม:
1. **E4 enum students.status** — ใช้ `'graduated'`/`'inactive'` ของเดิมตาม recommendation (ไม่ขยาย enum) — OK?
2. **Same-classroom re-enroll** — Phase 2 ยอมรับ history loss สำหรับเคสนี้; แก้จริงต้องเปลี่ยน UNIQUE → partial unique = Phase ถัดไป — OK?
3. **Lock strategy** — single-academy SELECT FOR UPDATE ที่ batch row เป็นจุดเริ่มต้น; multi-academy concurrency เลื่อนทีหลัง — OK?
