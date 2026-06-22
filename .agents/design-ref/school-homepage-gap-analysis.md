# School Homepage Design — Gap Analysis

อ้างอิง design: `.agents/design-ref/School Homepage.html` (Claude design bundler)
เปรียบเทียบกับ: `ui/pages/academies/[name].vue` + `ui/components/school/*` + `ui/components/academy/*`
วันที่: 2026-06-20

---

## 1. โครงสร้าง Design (ที่ Claude ออกแบบมา)

### 1.1 Header (sticky 64px, glass blur)
- Logo + brand "nuxnan"
- Search bar กลาง: "ค้นหาโรงเรียน รายวิชา หรือสมาชิก..."
- Chat icon + Bell icon
- Avatar + ชื่อผู้ใช้ + **เลเวล/XP** ("เลเวล 7 · 320 XP")

### 1.2 Cover Section (การ์ดเดียวรวม)
- Cover gradient + pattern overlay + dark gradient
- โลโก้ 112x112 (กรอบขาว + gradient)
- ชื่อโรงเรียน + verified badge + **Badge "เลเวล 12"**
- Subtitle: ชื่ออังกฤษ · @handle
- Stats inline: **สมาชิก / รายวิชา / โพสต์**
- ปุ่ม: `ขอเป็นสมาชิก` (toggle เป็น `เป็นสมาชิกแล้ว`) + `แชร์`
- Tabs: หน้าหลัก | รายวิชา (86) | สมาชิก (1,248) | ปฏิทิน | เกี่ยวกับ

### 1.3 Layout 3 คอลัมน์ (260 / 1fr / 320 px)

**LEFT sidebar (sticky):**
1. **Card "เมนูลัด"** — 5 รายการ icon สีต่างกัน:
   - ประกาศทั้งหมด (megaphone, purple)
   - ตารางเรียน (table-cells, cyan)
   - ผลการเรียน (chart-bar, green)
   - ห้องสมุดดิจิทัล (book-open, orange)
   - แต้มรางวัล (trophy, pink)
2. **Card "ระดับโรงเรียน"** — Level badge + "อีก 32% ถึงเลเวล 13" + ProgressBar 68%

**CENTER feed:**
1. **Composer card** — Avatar + "แบ่งปันข่าวสาร..." + ปุ่ม รูปภาพ/ประกาศ/กิจกรรม + ปุ่มโพสต์
2. **Pinned announcement** (purple ring) — ฝ่ายวิชาการ + Badge "ด่วน"
   - มีบรรทัด **"กลุ่มเป้าหมาย: นักเรียนทุกระดับชั้น"**
   - มีแถว reaction + **"+5 แต้ม"** (gamification reward)
3. **Director's message** — แถบ gradient ด้านบน + Badge "ฝ่ายบริหาร" + pills "ผู้อำนวยการ" + "อ่านแผนพัฒนาโรงเรียน"
4. **Registrar post** — Badge "สูง" (priority) + deadline pills
5. **Dormitory post** — pills หอพักชาย/หญิง + วันที่เริ่ม
6. **Activity attendance post** — embed **ProgressBar 95% (1,180/1,248)** ภายในโพสต์
7. **Robot club achievement** — image banner (gradient + trophy icon)
8. **Classroom activity** — image banner (gradient + beaker icon) + ring avatar
9. **Course assignment** — pills "ม.4 · 3 ห้องเรียน" + "กำหนดส่ง 27 มิ.ย."
10. **Event registration** — date card (วันที่+เดือน) + เวลา + สถานที่ + ปุ่ม "ลงทะเบียน"

ทุกโพสต์มี footer: **like / comment / share / +5 แต้ม**

**RIGHT sidebar (sticky):**
1. **StatCard 2x2 grid** — นักเรียน / ครู / รายวิชา / อันดับจังหวัด (โทนสีต่างกัน)
2. **Card "กิจกรรมที่จะถึง"** — date chip + ชื่อ + Badge audience (ครู/นักเรียน/ผู้ปกครอง)
3. **Card "อันดับห้องเรียน"** — leaderboard 1-2-3 พร้อม medal color (gold/silver/bronze) + แต้ม

---

## 2. ระบบปัจจุบัน — สิ่งที่ "มีแล้ว"

ไฟล์หลัก: `ui/pages/academies/[name].vue` (2,301 บรรทัด)

| ส่วน | สถานะ | หมายเหตุ |
|---|---|---|
| Cover + Logo + Stats inline | ✅ มี | `[name].vue:915-1008` แต่ไม่มี gradient/pattern, ไม่มี Level badge |
| Tabs (feed/courses/members/classrooms/events/groups) | ✅ มี | `[name].vue:142-149` — มี 6 tabs แล้ว แต่ไม่มี count, ไม่มี "เกี่ยวกับ" |
| ปุ่ม Join/Leave | ✅ มี | `[name].vue:986-1006` |
| Feed posts | ✅ มี | ใช้ `FeedPost.vue` (3,001 บรรทัด — component ใหญ่มาก) + `CreatePostBox` |
| Emergency Alert | ✅ มี | `SchoolEmergencyAlertBanner` |
| Attendance Widget | ✅ มี | `SchoolAttendanceWidget` (role-aware) |
| Tab content: Courses, Members, Classrooms, Events, Groups | ✅ มี | ใน `[name].vue` |
| Admin pages | ✅ มี | `[name]/admin.vue` + sub-pages |
| Tab content เฉพาะ: Academic / Asset / Communication / Finance / Gamification / Library / Reports / Staff / AuditLog | ✅ มี components | อยู่ใน `components/school/Sch*.vue` แต่อาจยังไม่ผูกกับหน้านี้ |

---

## 3. สิ่งที่ "ขาด" — Gap Analysis

### 🔴 P0 — ขาดทั้งโครงสร้าง

| # | สิ่งที่ขาด | ผลกระทบ | จุดที่ควรเพิ่ม |
|---|---|---|---|
| **G1** | **Layout 3 คอลัมน์** — Left sidebar + Center + Right sidebar (sticky) | หน้าตอนนี้เป็น single-column 7xl — เสีย real-estate, ไม่ได้บรรยากาศ "social school hub" | `[name].vue:904` `<div class="max-w-7xl">` ต้องเปลี่ยนเป็น grid 3 col |
| **G2** | **Left sidebar — Card "เมนูลัด"** (5 ทางลัด) | ไม่มี shortcut → user ต้องคลิก tabs ทุกครั้ง | สร้าง `SchoolQuickMenu.vue` ใหม่ |
| **G3** | **Right sidebar — StatCard 2x2 grid** (นักเรียน/ครู/วิชา/อันดับ) | overview ไม่ชัด → ดู stats ต้อง scroll | สร้าง `SchoolStatGrid.vue` (อาจ reuse Common/StatCard ถ้ามี) |
| **G4** | **Right sidebar — กิจกรรมที่จะถึง** widget | ดูปฏิทินต้อง switch tab events | สร้าง `SchoolUpcomingEvents.vue` (limit 3, link ไป tab events) |
| **G5** | **Right sidebar — Leaderboard ห้องเรียน** (top 3 + medal) | gamification ไม่ได้โชว์ → ห้องเรียนไม่แข่ง | สร้าง `SchoolClassroomLeaderboard.vue` ต้องมี API คะแนนสะสมต่อห้อง |

### 🟡 P1 — ขาดเฉพาะส่วน

| # | สิ่งที่ขาด | จุดที่ควรเพิ่ม |
|---|---|---|
| **G6** | **Level/XP badge** บน Cover (เลเวล 12 ของโรงเรียน) | `[name].vue:942` หลัง `<h1>` |
| **G7** | **Card "ระดับโรงเรียน"** ใน left sidebar (progress bar + % ถึงเลเวลถัดไป) | `SchoolLevelCard.vue` ใหม่ |
| **G8** | **Pinned post styling** (purple ring + แถบ "ปักหมุดไว้") | ปรับ `FeedPost.vue` ให้รับ prop `pinned` แล้วเปลี่ยน border + label |
| **G9** | **"กลุ่มเป้าหมาย" line** ในแต่ละโพสต์ (นักเรียน/ครู/ผู้ปกครอง/ทุกระดับ) | ต้องเพิ่ม column `target_audience` ใน posts table + แสดงใน FeedPost |
| **G10** | **"+5 แต้ม" reward chip** ที่ footer ของโพสต์ (gamification cue) | เพิ่มใน FeedPost footer |
| **G11** | **Post type variants** — director message, registrar, dormitory, event registration, attendance summary | ตอนนี้ FeedPost น่าจะ generic — ต้องดูว่ามี `post_type` field รึยัง ถ้าไม่มี → เพิ่ม |
| **G12** | **Embedded ProgressBar in post** (เช่น เช็คชื่อกิจกรรม 95%) | block พิเศษใน FeedPost ที่ render จาก `post.embed_progress` |
| **G13** | **Date chip on Event post** (วันที่+เดือน big block) + ปุ่ม "ลงทะเบียน" | EventPostCard variant ใน FeedPost |
| **G14** | **Post tag pills** (เช่น "อัปเดตทะเบียนประวัติ", "ปิดระบบ 30 มิ.ย. 16:30") | field `post.tags` → render เป็น pill row |
| **G15** | **Gradient header strip** บนโพสต์สำคัญ (Director's message) | optional prop |
| **G16** | **Verified badge** (check-badge-solid cyan) ข้างชื่อ author ของโพสต์ | render จาก `author.verified` |
| **G17** | **Tab count badges** (รายวิชา 86, สมาชิก 1,248) | `[name].vue:1014-1020` tab button เพิ่ม `count` |

### 🟢 P2 — Polish / nice-to-have

| # | สิ่งที่ขาด |
|---|---|
| **G18** | Cover pattern overlay (sci-fi grid) + bottom dark gradient — ตอนนี้แค่ dark-to-transparent |
| **G19** | Logo container เป็น gradient background ทึบ (Vikinger purple→cyan) แทน photo |
| **G20** | Composer แยกปุ่ม 3 ประเภท (รูปภาพ/ประกาศ/กิจกรรม) แทน trigger เดียว |
| **G21** | Hover state + cursor pointer ของรายการ Quick menu |
| **G22** | "อ่านบทวิเคราะห์" tag pill: รวม Director name + role ใน 1 pill |

---

## 4. Backend Data — ตรวจสอบที่ต้องการ

| ฟิลด์/feature ที่ design ใช้ | มีใน DB หรือยัง? | Action |
|---|---|---|
| `academies.level` + `academies.xp` | ❓ ต้องเช็ค | ถ้าไม่มี → migration ใหม่ |
| `posts.is_pinned` | ❓ | เช็ค `posts` table |
| `posts.target_audience` (student/teacher/parent/all) | ❓ | น่าจะยังไม่มี — ต้องเพิ่ม |
| `posts.post_type` (announcement/director/registrar/event/achievement/classroom/course) | ❓ | ดูใน `Post` model |
| `posts.reward_points` | ❓ | gamification reward |
| `posts.embed_data` (progress, date, location) | ❓ | json column สำหรับ embed |
| Classroom points/leaderboard | ❓ | ต้องมี `classroom_points` aggregate |
| Academy stats (members/courses/posts count) | ✅ มี | `academy.total_students`, `academy.courses_offered` ใช้แล้ว |

> ผมยังไม่ได้ verify DB จริง — ก่อน implement ต้อง grep `database/migrations/` เพื่อยืนยัน

---

## 5. แผนพัฒนา (ลำดับ Phase)

### Phase 1 — Layout shell (no backend change)
- [ ] **P1.1** เปลี่ยน `[name].vue` main content เป็น grid 3 col (responsive collapse ตาม CSS ของ design)
- [ ] **P1.2** สร้าง `SchoolQuickMenu.vue` (static link ก่อน)
- [ ] **P1.3** สร้าง `SchoolStatGrid.vue` (ใช้ data ที่มี + mock อันดับจังหวัด)
- [ ] **P1.4** สร้าง `SchoolUpcomingEvents.vue` (ใช้ events ที่มี slice top 3)
- [ ] **P1.5** ย้าย Cover/Tabs ไป grid-cols-span-3

### Phase 2 — Gamification widgets
- [ ] **P2.1** Migration: `academies` + `level`, `xp`, `xp_to_next_level`
- [ ] **P2.2** `SchoolLevelCard.vue` + API endpoint
- [ ] **P2.3** Migration: `classroom_points` (sum จาก activities/posts/attendance)
- [ ] **P2.4** `SchoolClassroomLeaderboard.vue` + API top 3
- [ ] **P2.5** Tab count badges (เติม count ใน computed)

### Phase 3 — Post enhancements
- [ ] **P3.1** Migration `posts`: `is_pinned`, `target_audience`, `post_type`, `reward_points`, `embed_data` (json)
- [ ] **P3.2** Pinned style ใน `FeedPost.vue` (purple ring + "ปักหมุดไว้" label)
- [ ] **P3.3** Target audience line + +X แต้ม chip ที่ footer
- [ ] **P3.4** Post type variants (director gradient strip, event date chip, embed progress)
- [ ] **P3.5** Composer 3-button (Image/Announcement/Event) → 3 modal variants

### Phase 4 — Polish
- [ ] **P4.1** Cover pattern + gradient overlay
- [ ] **P4.2** Verified badges ข้างชื่อ author
- [ ] **P4.3** Hover states + transitions ใน sidebar widgets

---

## 6. ไฟล์ที่จะถูกแตะ (Phase 1)

```
ui/pages/academies/[name].vue                    (เปลี่ยนเป็น 3-col grid)
ui/components/school/SchoolQuickMenu.vue         (NEW)
ui/components/school/SchoolStatGrid.vue          (NEW)
ui/components/school/SchoolUpcomingEvents.vue    (NEW)
ui/components/school/SchoolLevelCard.vue         (NEW - phase 2)
ui/components/school/SchoolClassroomLeaderboard.vue (NEW - phase 2)
```

ไฟล์ที่ "อาจ reuse" — ตรวจก่อนเขียนใหม่:
- `ui/components/atoms/` หรือ `Common/` มี StatCard, ProgressBar, Badge อยู่แล้วหรือไม่
- `ui/components/play/feed/FeedPost.vue` (3,001 บรรทัด) — มี post_type variants อยู่บ้างหรือไม่

---

## 7. คำถามที่ต้องตัดสินใจก่อนเริ่ม

1. **G3 "อันดับจังหวัด"** — ใช้ mock หรือมี ranking algorithm จริง?
2. **G7 School Level/XP** — คำนวณจากอะไร? (โพสต์, member activity, attendance, ผลรางวัล?)
3. **G5 Classroom leaderboard** — แต้มมาจาก attendance + assignment submission + post engagement หรือมี table แยก?
4. **G9 target_audience** — ทำเป็น enum (student/teacher/parent/all) หรือ multi-select?
5. **Layout breakpoint** — design ใช้ 1080/820 ซ่อน left/right; nuxnan ใช้ Tailwind → ใช้ `lg:`/`xl:` หรือกำหนด breakpoint เอง?
