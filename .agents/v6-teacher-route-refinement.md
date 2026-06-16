# Refinement v6 — Teacher Patrol Deterministic Route (2026-06-16)

**Trigger:** user แผน frontend-first 6 phase สำหรับ teacher route deterministic + avatar fallback + responsive guard. แผนถูกทิศ แต่จากการอ่าน [attendancePhaserScene.ts](../ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts) จริงเจอ 12 จุดต้องเสริม

## 12 ข้อค้นพบจาก source code ปัจจุบัน

1. **`aisleX = this.gridCenterX`** ที่ [line 821](../ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts:821) — ผิด! นั่นคือกลางห้อง ไม่ใช่ aisle จริง. aisle X = `zoneStarts[z] + zones[z]*deskCellW + aisleWidth/2`
2. **Pause ใช้ tween no-op** [line 838-841](../ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts:838): `y: targetY` กับ y เดิม → Phaser ข้าม → pause หาย; ต้อง `scene.time.delayedCall()`
3. **Nested onComplete 4 ชั้น** ทำให้ cancel ยาก — ใช้ `tweens.chain()` แทน
4. **`teacherPatrolToken` invalidate closure** แต่ in-flight tweens วิ่งต่อ; ต้องเก็บ `teacherChain` แล้ว `chain.destroy()` ก่อนเริ่มใหม่
5. **Teacher width 56px > aisle 48px max** → เดินลง aisle ล้นทับโต๊ะ; ต้อง scale 0.65 ตอนลง aisle
6. **Depth ไม่ครบ:** teacher=5, walker=7, door=undefined → door อาจถูกทับ; ต้อง door.setDepth(10)
7. **Front-patrol y = 220px** (FLOOR_TOP=112 + 28 + FRONT_WALKWAY/2=36) ไม่ทับผนัง ✓ แต่ผูก magic number; ทำเป็น const
8. **Bob อยู่บน `person` sub-container** ไม่ชน patrol y-tween บน teacher root ✓ — verify ถูกแล้ว
9. **drawAvatar fallback chain ทำเสร็จ Phase E** ([line 729](../ui/components/learn/course/attendances/phaser/attendancePhaserScene.ts:729)) — user Phase 3 = no-op, แค่ verify
10. **Direction-aware sprite** — head circle symmetric; flip `person.scaleX = -1` + เพิ่ม 2 จุดดำเป็นตา → ทำให้รู้สึก "หันหน้า" โดยไม่ต้อง sprite sheet
11. **Inspect row depth** ไม่กำหนด — แนะนำลงสุด aisle (row last) เสมอ; pause กลางทาง 0.8-1.5s = "หยุดดูนักเรียน"
12. **Reduced-motion partial mode** — user Phase 6 อยาก "เดินช้าๆ" แต่ปัจจุบัน reduced-motion = static; ต้องเพิ่ม mode `partial` (front-only ช้า × 3)

## State model + helpers

```ts
private teacherChain?: Phaser.Tweens.TweenChain
private teacherRoute: RoutePoint[] = []
private teacherRouteIndex = 0
private teacherFacing: 'left' | 'right' = 'right'

type RoutePoint = {
  id: string                       // 'front-left' | 'aisle-1-down' | ...
  x: number
  y: number
  pause: number                    // ms — 0 = ผ่านเลย
  scale?: number                   // 0.65 สำหรับ aisle, 1.0 default
  facing?: 'left' | 'right'
}
```

**Helpers ที่ต้องมี:**
- `buildTeacherRoute(geom, layout): RoutePoint[]` — pure, deterministic, testable
- `computeAisleXs(geom, layout): number[]` — X กลาง aisle ต่ออัน (จาก zoneStarts + width)
- `startTeacherPatrol()` — destroy chain เก่า, build chain ใหม่จาก teacherRoute
- `moveTeacherToPoint(p)` — single tween + face update
- `pauseTeacherAtPoint(p)` — `scene.time.delayedCall(p.pause)`
- `stopTeacherPatrol()` — destroy chain + null ref

## Phase T1-T7 (refined)

### Phase T1 — Route builder (pure, testable)

- [ ] `buildTeacherRoute(geom, layout)` คืน array RoutePoint:
  ```
  front-left → front-center → front-right →
  aisle[0]-down → pause → aisle[0]-up → front-center →
  aisle[1]-down → pause → aisle[1]-up → front-center → (loop)
  ```
- [ ] เคส **1 aisle** (mobile 480-639): ตัดเหลือ front-LCR + aisle[0] only
- [ ] เคส **0 aisle** (mobile <480): hide teacher (return ก่อน build)
- [ ] เคส **skipAisles=true** (reduced-motion): front-LCR ความเร็ว × 0.4
- [ ] `computeAisleXs()`: X กลาง aisle = `zoneStarts[z] + zones[z]*deskCellW + (zones[z]-1)*zoneGap + aisleWidth/2`

### Phase T2 — Tweens chain implementation

- [ ] แทน nested onComplete ด้วย:
  ```ts
  this.teacherChain = this.tweens.chain({
    targets: teacher,
    tweens: route.flatMap(p => [
      { x: p.x, y: p.y, scale: p.scale ?? 1, duration: computeDuration(p), ease: 'Sine.InOut',
        onStart: () => this.faceTeacher(p.facing) },
      ...(p.pause > 0 ? [{ duration: p.pause }] : [])  // pause = delay tween
    ]),
    repeat: -1,
  })
  ```
- [ ] `computeDuration(p)` = `distance(prev, p) / TEACHER_SPEED` (0.055 px/ms ตามต้นฉบับ commit 094f95f8)

### Phase T3 — Aisle scaling + direction flip + eyes

- [ ] aisle points: `scale: 0.65` → tween ลด size ตอนเข้า, กลับ 1.0 ตอนออก
- [ ] `faceTeacher(facing)`: `person.scaleX = facing === 'left' ? -1 : 1` (flip body+head; name label ไม่อยู่ใน person ไม่ flip)
- [ ] เพิ่มในตอน createTeacher: 2 จุดดำ `add.circle(head.x - 4, head.y - 2, 1.5, 0x000000)` × 2 ในตำแหน่งตา → ใส่ใน `person` ด้วยจะ flip ตามตัว

### Phase T4 — Cancellation + lifecycle

- [ ] `stopTeacherPatrol()`:
  ```ts
  this.teacherChain?.destroy()
  this.teacherChain = undefined
  this.teacherPatrolToken++  // เก็บไว้ extra safety
  ```
- [ ] เรียกที่ start `drawTeacher()` (ก่อน build chain ใหม่)
- [ ] hook `scene.events.once('shutdown', () => this.stopTeacherPatrol())`
- [ ] เรียก `stopTeacherPatrol()` ก่อน `teacherSprite.destroy()` ใน renderRoom

### Phase T5 — Depth + UI guards

- [ ] `this.doorContainer?.setDepth(10)` ใน `drawDoor`
- [ ] `this.teacherSprite.setDepth(5)` ✓ มีอยู่
- [ ] `walker.setDepth(7)` ✓ มีอยู่
- [ ] empty placeholders depth = 0
- [ ] guard ใน `buildTeacherRoute`: ถ้า aisle ปลาย y ใกล้ door (delta < 80) → ลด y ของ aisle-down ให้สูงกว่า door 80px (กันชน UI)

### Phase T6 — Responsive + reduced-motion

- [ ] `width < 480` → skip teacher ทั้งตัว
- [ ] `width < 640` → route = front-only (no aisle)
- [ ] `width >= 640` → full route
- [ ] `prefersReducedMotion()` → mode partial (front-only × 3 ช้า, ไม่มี bob)
- [ ] route rebuild auto ที่ `renderRoom()` (มี call อยู่แล้ว ✓)

### Phase T7 — Verification matrix

- [ ] Desktop 1280: 30s patrol → ครูเดิน front-L→C→R → ลง aisle ซ้าย → pause กลางทาง → ลงสุด → ขึ้น → center → ลง aisle ขวา → loop ✓
- [ ] Tablet 768 (2:2:2): 2 aisles → ครูลงทั้งคู่ scale 0.65
- [ ] Mobile 480-639 (2:2): 1 aisle → ลงเฉพาะอันนั้น
- [ ] Mobile <480: ไม่มีครู
- [ ] Direction flip: ครูเดินจาก right→left, head ตาหันซ้าย, body x flip
- [ ] Avatar มี: รูปจริง + mask ติดตามครู (`postupdate` listener)
- [ ] Avatar ไม่มี: initials fallback
- [ ] Walker check-in: depth 7 > teacher 5 → ไม่ถูกทับ
- [ ] Door click: button responds; ครู depth 5 < door 10
- [ ] Resize cross-breakpoint: chain destroyed + rebuilt smooth
- [ ] Reduced-motion: front-only ช้า, no bob

## ความเสี่ยง

- **`tweens.chain()` ต้อง Phaser 3.60+** — ตรวจ package.json
- **flip body container** อาจทำให้ collar เห็น asymmetric — รับได้สำหรับ cartoony style
- **pause `duration` only tween** บาง version อาจ optimize ออก — ทดสอบจริง, fallback `time.delayedCall()`
- **Avatar mask + chain.destroy** — postupdate listener cleanup ใน `img.once('destroy', off)` ✓ มีอยู่; verify chain ปลายทาง
- **3:3:3 desktop** aisle ~48px พอดี teacher 56 → scale 0.65 จำเป็น (verified width 48 * 0.65 = 31 < 56 = OK)

## ลำดับ commit (revertable)

1. **T1 helpers (pure)** — `buildTeacherRoute` + `computeAisleXs` — testable ไม่ break
2. **T4 stopTeacherPatrol** — safety net ก่อน refactor
3. **T2 chain refactor** — แทน nested onComplete (large, careful)
4. **T5 depth + door guard** — UI ไม่ชน
5. **T3 direction flip + eyes + aisle scale** — polish
6. **T6 responsive + reduced-motion** — partial mode
7. **T7 verify pass** — ทุก viewport

**Recommendation:** T1 → T4 → T2 → T5 → T3 → T6 → T7
ทำ T1+T4 ก่อน (safety + testability), T2 ครั้งใหญ่สุดอันเดียว, ที่เหลือทยอย polish
