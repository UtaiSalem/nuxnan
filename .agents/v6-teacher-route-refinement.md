# Teacher Patrol Refactor — Refinement v6.1

> v6.1 = v6 + การตรวจสอบกับโค้ดจริง (`ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts`, 1,144 บรรทัด) + เพิ่มเติมจุดที่ v6 พลาดหรือคลาดเคลื่อน

---

## 0. ผลการตรวจสอบ v6 กับโค้ดจริง

| v6 อ้างว่า | สถานะ | หมายเหตุ |
|---|---|---|
| `aisleX = this.gridCenterX` (line 821) | ✅ ถูก | บรรทัด 821 ตรงตามที่ระบุ |
| Pause = no-op tween (y: targetY ซ้ำ) | ✅ ถูก | บรรทัด 838-852 — `y: targetY` ที่ teacher อยู่แล้ว, delta=0 |
| Nested onComplete 4 ชั้น | ✅ ถูก | 825 → 832 → 838 → 843 |
| Token invalidate ไม่ kill tween in-flight | ✅ ถูก | `isStale()` เช็คใน step เท่านั้น, ไม่ได้ kill chain ปัจจุบัน |
| Teacher 56px > aisle 48px | ✅ ยืนยัน | `TEACHER_W = 56` (line 46), aisleWidth = `max(28, min(48, …))` (line 283) |
| Phaser version | ⚠️ **v6 เข้าใจผิด** | จริง = **Phaser 4.1** (`package.json:63`), ไม่ใช่ 3.60 — `tweens.chain` มีและถูกใช้แล้วใน walker (line 939) |
| "Magic number 220 สำหรับ front-Y" | ❌ **v6 ผิด** | ไม่มี 220 จริง — ค่าจริงคำนวณจาก `FLOOR_TOP(112) + 28 + FRONT_WALKWAY(72)/2 = 176`, ผูกกับ constant อยู่แล้ว ไม่ใช่ magic |
| Phase 3 (avatar fallback) เสร็จแล้ว | ✅ ยืนยัน | `drawAvatar()` ที่ line 733 ใช้กับ teacher แล้ว |
| Reduced-motion = static เท่านั้น | ✅ ยืนยัน | `drawTeacher` คืนค่าก่อน `startTeacherPatrol` ที่ line 759 |

---

## 1. ปัญหาเพิ่มเติมที่ v6 พลาด (9 ข้อใหม่)

### 1.1 `isStale()` ตรวจ `this.scene` ผิดเซแมนติก (line 812)
```ts
const isStale = () => myToken !== this.teacherPatrolToken
  || !this.teacherLoopActive
  || !this.teacherSprite
  || !this.scene   // ❌ this.scene คือ ScenePlugin, จริง ๆ ไม่เคยเป็น falsy
```
ควรเป็น `!this.scene?.isActive()` หรือ `!this.teacherSprite?.active`

### 1.2 `teacherSprite.active` ไม่ถูกตรวจ
หลัง `destroy()` Phaser ตั้ง `.active = false`. ปัจจุบันไม่มี guard นี้ → ถ้า scene shutdown แต่ tween onComplete fire ทีหลัง อาจอ้าง destroyed container

### 1.3 Drawing order = depth order ใน `dynamicLayer` (door ไม่มี `setDepth`)
- Door เพิ่มเข้า `dynamicLayer` ที่ line 637 — **ไม่มี** `door.setDepth(...)`
- Teacher `setDepth(5)` ที่ line 754, walker `setDepth(20)` ที่ line 929
- ปัจจุบันไม่ชนกันเพราะ teacher patrol อยู่ที่ `frontY` (top of floor) ห่างจาก door (`floorY + floorH - 24`, bottom of floor) มาก
- แต่ถ้าจะขยาย route ให้ครู "ส่งนักเรียนกลับบ้าน" หรือเดินไปทางประตู → ต้องตั้ง depth ของ door ให้ชัดก่อน

→ v6 ข้อ #6 ต้องแก้เป็น: **door ไม่ต้องตั้ง depth จนกว่า route จะแตะ doorY**

### 1.4 ไม่มี clamp ของ targetY ใน inspect mode (line 823)
```ts
const targetY = geom.floorY + row * geom.rowGap + 24
```
ถ้า `row` = rows-1 (เช่น 4 แถว → row=3): `targetY = floorY + 3*104 + 24 = floorY + 336`
สำหรับ rows=2: `targetY = floorY + 104 + 24 = floorY + 128` — ok แต่ไม่ตรงกับกลางโต๊ะ → ใช้ `(row + 0.5) * rowGap` แทน

### 1.5 ไม่มี facing flip ในโหมด front-walk (line 857-874)
- ครูเดินซ้าย-ขวาตลอด แต่ sprite ไม่หมุน
- ตอนนี้ sprite สมมาตรซ้าย-ขวา ไม่เห็นชัด — แต่หลังเพิ่ม "ตา" (T3) จะเห็นทันทีว่าหันผิดทาง

### 1.6 Bob tween บน `person` (line 762) ต้องระวังตอน flip
- Bob ทำงานบน inner `person` container ที่ y: -3
- ถ้า scaleX = -1 บน `person` จะกลับทิศ x แต่ y bob ยังถูกต้อง (ไม่กระทบ)
- ✅ Safe, แต่ต้อง flip ที่ `person` ไม่ใช่ outer `teacher` (ไม่งั้น shadow + nameLabel flip ด้วย)

### 1.7 Walker (depth 20) แทรกผ่าน teacher patrol path
- Walker spawn จากประตูเดินขึ้นไปยังโต๊ะ, ครูเดินซ้าย-ขวาที่ frontY
- ถ้า walker ผ่าน frontY พอดี → ทับกันชั่วครู่
- ปัจจุบัน walker depth 20 > teacher 5 → walker ทับครู (acceptable visually)
- ไม่ต้องแก้, แต่ verify ใน T7

### 1.8 `drawTeacher` ถูกเรียกซ้ำทุก resize/render → recreate sprite ใหม่ทั้งก้อน
- บรรทัด 703: `teacherPatrolToken++` invalidate
- แต่ไม่ destroy old `this.teacherSprite` ก่อนสร้างใหม่ → เกิด orphan container ถ้า dynamicLayer ไม่ถูกล้าง
- ตรวจว่า scene init ทำ `dynamicLayer.removeAll(true)` หรือไม่ ก่อนแก้

### 1.9 `setInteractive` rect ต่อเนื่องหลัง scale change
- บรรทัด 752: `setInteractive(new Phaser.Geom.Rectangle(-TEACHER_W/2, -24, TEACHER_W, 72), …)`
- เมื่อ scale 0.65 ใน aisle, hit area **ไม่** scale อัตโนมัติใน Phaser — ยังเป็น 56×72 → click ได้ยากขึ้นนิด (acceptable)

---

## 2. State model ฉบับแก้ไข (เพิ่มเติมจาก v6)

```ts
private teacherChain?: Phaser.Tweens.TweenChain  // ✅ v6
private teacherRoute: RoutePoint[] = []           // ✅ v6
private teacherFacing: 'left' | 'right' = 'right' // ✅ v6
private teacherEyes?: Phaser.GameObjects.Container // 🆕 v6.1 — สำหรับ flip ตา
private teacherBaseScale = 1                       // 🆕 v6.1 — เก็บ scale เริ่มต้น (รองรับ HiDPI ในอนาคต)

type RoutePoint = {
  id: string                              // ✅
  x: number                               // ✅
  y: number                               // ✅
  pauseMs: number                         // ✅ rename จาก pause
  scale?: number                          // ✅
  facing?: 'left' | 'right' | 'auto'      // 🆕 auto = อิงทิศจากจุดก่อน
  ease?: string                           // 🆕 ให้คุม per-segment
  travelMs?: number                       // 🆕 override duration ต่อ segment
}
```

---

## 3. Helpers — ฉบับขยาย (v6 มี 6 ตัว → v6.1 มี 9)

| # | Helper | Pure? | จุดประสงค์ |
|---|---|---|---|
| 1 | `computeAisleXs(geom, layout)` | ✅ | คืน [x_centerOfAisle1, x_centerOfAisle2, …] (v6) |
| 2 | `computeRowYs(geom)` | ✅ | คืน [y_centerOfRow0, …] — 🆕 แทน formula ในตัว |
| 3 | `buildTeacherRoute(geom, layout, viewport, mode)` | ✅ | สร้าง RoutePoint[] (v6) — `mode = full \| front-only \| reduced` |
| 4 | `clampRouteToFloor(route, floorBounds)` | ✅ | กัน Y ออกนอก floor — 🆕 |
| 5 | `startTeacherPatrol()` | ❌ | สร้าง chain, store ใน `this.teacherChain` (v6) |
| 6 | `moveTeacherToPoint(p)` | ❌ | คืน TweenBuilderConfig (v6) |
| 7 | `pauseAtPoint(p)` | ❌ | คืน `{ targets, duration: pauseMs, hold: pauseMs, y: '+=0' }` หรือใช้ `delay` ใน next tween — 🆕 ใช้ทั้ง 2 ทาง fallback |
| 8 | `faceTeacher(facing)` | ❌ | `person.setScale(±baseScale, baseScale)` + flip ตา (v6) |
| 9 | `stopTeacherPatrol()` | ❌ | `chain?.destroy(); chain=undefined` + hook `scene.events.once('shutdown')` (v6) |

### 3.1 Pause-tween workaround (สำคัญที่สุด)
Phaser 4 อาจ optimize tween ที่ delta=0 ทิ้ง — fallback 2 ชั้น:
```ts
// option A: ใช้ delay ของ tween ถัดไป (ดีที่สุด)
{ targets, x: nextX, y: nextY, duration: travelMs, delay: pauseMs, ease }

// option B: ถ้าต้องการ pause มี side-effect (เช่นเปลี่ยน facing)
this.scene.time.delayedCall(pauseMs, () => { ... })
```
**v6 บอก "duration-only tween" — v6.1 แนะนำใช้ `delay` ของ tween ถัดไปแทน เพื่อเลี่ยง edge case**

---

## 4. Phases — ฉบับขยาย (v6 มี T1-T7 → v6.1 มี T0-T8)

### T0 — Pre-flight (🆕 v6.1)
- เช็คว่า `dynamicLayer.removeAll()` ถูกเรียกตอน resize หรือไม่ (ป้องกัน orphan teacher)
- เพิ่ม `this.teacherSprite?.destroy()` ที่หัว `drawTeacher` ก่อน recreate
- **ไม่มี code change ที่ Phaser API — เป็น safety audit**
- **Estimated diff**: ~5 lines

### T1 — Pure helpers (`computeAisleXs`, `computeRowYs`, `buildTeacherRoute`, `clampRouteToFloor`)
- Extract เป็น function ใน file เดียวกัน (ยังไม่เป็น `.ts` util แยก เพื่อลด churn)
- **Testable**: ใส่ console.log ชั่วคราว เปรียบเทียบ output ก่อน/หลังกับ baseline geometry
- **ไม่ break** เพราะยังไม่ wire เข้า patrol
- **Diff**: ~80 lines (4 functions)

### T4 — Safety net: `stopTeacherPatrol` + shutdown hook
- ทำก่อน T2 (refactor ใหญ่) เพราะถ้า T2 พลาด, T4 จะกัน leak ไว้แล้ว
- เพิ่ม `scene.events.once(Phaser.Scenes.Events.SHUTDOWN, () => this.stopTeacherPatrol())`
- เพิ่ม `scene.events.once(Phaser.Scenes.Events.DESTROY, …)` ด้วย (safety สองชั้น)
- **Diff**: ~15 lines

### T2 — Chain refactor (commit ใหญ่ที่สุด)
- ลบ nested onComplete block (line 814-877 ทั้งหมด)
- สร้าง chain จาก `this.teacherRoute.flatMap(buildSegments)`
- เก็บ chain ใน `this.teacherChain`
- ใช้ `delay` ของ tween ถัดไปแทน pause-tween
- ตั้ง `repeat: -1` ที่ระดับ chain
- **Diff**: ~100 lines (–60 nested, +60 chain config + flatMap logic)

### T5 — Depth + collision guard
- ตั้ง `door.setDepth(2)` ที่ line 596 (ต่ำกว่า teacher) — แต่ **เฉพาะเมื่อ T8 verify ว่า route ไม่ใช้ doorY**
- ถ้า route ใช้ doorY → ตั้ง door depth สูงกว่า teacher (`setDepth(10)`)
- Guard route: drop point ที่ `distance(p, door) < 80`
- **Diff**: ~10 lines

### T3 — Visual polish (ตา + flip + scale)
- เพิ่ม 2 dot 4×4 ที่ y: -6, x: ±5 บน head — เก็บใน `this.teacherEyes`
- `faceTeacher`: `person.scaleX = facing === 'left' ? -baseScale : baseScale`
- ใน aisle: `person.setScale(0.65 * baseScale)`; ออกจาก aisle: คืน scale
- ใช้ tween 200ms `ease: 'Cubic.Out'` สำหรับ scale transition (ไม่ pop)
- **ระวัง**: flip ที่ `person` เท่านั้น (ไม่ใช่ outer container) — ไม่งั้น shadow/nameLabel flip
- **Diff**: ~40 lines

### T6 — Responsive + reduced-motion mode
| viewport | mode | route |
|---|---|---|
| <480 | hidden | — (return ที่ drawTeacher เหมือนเดิม) |
| 480-639 | hidden | — (ปัจจุบันคืน <640) |
| 640-1023 | `front-only` | LCR pattern, ไม่เข้า aisle (เพราะ aisle 1 ตัว, ดูแคบ) |
| ≥1024 | `full` | LCR + aisle dive |
| reduced-motion | `partial` | เดิน LCR ช้าลง 3× (travelMs ×3), ไม่มี bob, ไม่มี aisle |
- v6 ใช้ breakpoint 480/640 — v6.1 ปรับเป็น **640/1024** เพื่อให้ tablet (768) ใช้ front-only เพราะ aisle 1 ตัวที่ tablet ดูไม่สมเหตุสมผล
- **Diff**: ~20 lines

### T7 — Walker overlap visual check (🆕 แยกจาก T8)
- รัน scene ในสภาพ walker หลายตัว + teacher patrol
- ตรวจว่า walker depth 20 > teacher 5 ทำให้ walker ทับครูเสมอ (acceptable)
- ถ้า walker หลายตัวพร้อมกัน → frame rate drop ที่ tablet? (วัดด้วย Phaser stats)
- **ไม่ต้องแก้ — แค่ verify**

### T8 — Verification matrix
| Variant | จำนวน case |
|---|---|
| Viewport | 5 (375, 640, 768, 1024, 1440) |
| Direction flip | 2 (start L, start R) |
| Avatar fallback | 2 (has img, fallback) |
| Walker overlap | 2 (none, 5 walkers) |
| Resize during patrol | 4 (small→big, big→small, mobile→desktop, desktop→mobile) |
| Reduced-motion toggle | 2 (on, off) |
| **Total cases** | **5×2×2×2×4×2 = ≤ 16 representative samples** (ไม่ใช่ Cartesian เต็ม) |

---

## 5. ลำดับ commit (อัพเดทจาก v6)

```
1. T0 — orphan-sprite audit + cleanup (safety)
2. T4 — stopTeacherPatrol + shutdown hook (safety)
3. T1 — pure helpers (no behavior change)
4. T5 — depth pass (no behavior change, ง่าย)
5. T2 — chain refactor ★ commit ใหญ่ ★
6. T3 — eyes + flip + scale
7. T6 — responsive + reduced-motion
8. T7 + T8 — verify, no code change unless found
```

→ **v6.1 เพิ่ม T0 ขึ้นต้น** เพราะ orphan sprite อาจ mask bug ใน T2

---

## 6. ความเสี่ยงที่ปรับใหม่จาก v6

| v6 risk | v6.1 status |
|---|---|
| `tweens.chain()` ต้อง Phaser 3.60+ | ❌ ตกไป — เป็น Phaser 4.1 อยู่แล้ว และใช้ chain ใน walker แล้ว |
| Pause duration-only tween ถูก optimize ออก | ✅ คงไว้ — แก้ด้วยใช้ `delay` ของ tween ถัดไป |
| Flip body container ทำให้ collar/eyes asymmetric | ✅ คงไว้ — รับได้ใน cartoon style |
| 🆕 Orphan teacher sprite จาก resize bursts | ใหม่ — T0 จัดการ |
| 🆕 `isStale()` ตรวจ `this.scene` ผิด | ใหม่ — แก้ใน T4 พร้อม stopTeacherPatrol |
| 🆕 dynamicLayer add order = depth บาง object | ใหม่ — T5 ตั้ง explicit setDepth |

---

## 7. คำถามที่ต้อง user ยืนยันก่อนเริ่ม

1. **Tablet (768) → front-only หรือ full?** v6.1 แนะนำ front-only เพราะ aisle 1 ตัวดูไม่สมเหตุสมผล แต่ user อาจอยากให้ดู "ขยัน" → ขอ confirm
2. **Reduced-motion**: ครูเดินช้า ×3 หรือ static? v6 user บอก "partial slow" → ยืนยัน
3. **Visual: flip ตา (T3) สำคัญแค่ไหน?** ถ้าไม่สำคัญ ตัด T3 เหลือแค่ scale → ลด 40 lines

---

## 8. Out of scope (ระบุชัดเพื่อไม่ scope creep)

- Pathfinding (A*) — overkill สำหรับ patrol แบบสุ่ม
- ครูพูดคุย (speech bubble) — feature ใหม่ ไม่ใช่ refactor
- ครูตอบสนองต่อ student events (เช่นเดินไปหานักเรียนที่กดเช็คอินช้า) — feature, ไม่ใช่ patrol
- Sound effects — แยก ticket
- Sprite sheet animation จริง (4 directions) — overkill, scaleX flip ก็พอ
