# School Admin — Master Overview

> ไฟล์นี้คือดัชนีหลักของงานบริหารจัดการโรงเรียนฝั่ง Admin
> อ่านไฟล์นี้ก่อนเริ่มทำงานทุกครั้ง เพื่อรู้ว่าเมนูไหนอยู่สถานะไหน และไฟล์รองของเมนูนั้นอยู่ที่ไหน
>
> **หลักการ:** ทุกฟีเจอร์ในโรงเรียน = admin ต้องทำได้ทั้งหมด → จากนั้นค่อยแจกจ่ายสิทธิ์ให้บทบาทย่อย (ครู/ฝ่าย/หัวหน้าฝ่าย) ตามหน้าที่ของแต่ละส่วนงาน โดยไม่ให้สิทธิ์ซ้ำซ้อนกันระหว่างฝ่าย

---

## Workflow (Loop ต่อเมนู)

```
[1] สแกนโค้ดจริงของเมนู (frontend page + backend controller + routes + models)
[2] เขียนไฟล์รอง NN-<slug>.md (Scope / Current State / Feature Checklist /
    Permission Matrix / Gap Analysis / Implementation Tasks / Review Log)
[3] ส่ง task ทีละ step ให้ codex (ผ่าน Agent subagent codex:rescue)
[4] codex ทำงาน → รายงานผล → Claude ตรวจ (diff + build + test)
    - ถ้าไม่ผ่าน → แจ้ง codex แก้ → วนกลับ [4]
    - ถ้าผ่าน → update Review Log + สถานะในไฟล์นี้
[5] เมนูถัดไป
```

**กฎ:**
- Claude วางแผน + ตรวจสอบเท่านั้น ไม่เขียนโค้ดตรง (ตาม feedback memory)
- ทำทีละเมนู อย่ากระโดดข้าม อย่าทำงานคู่ขนาน (กันสับสน)
- ทุกเมนูต้อง verify build + relevant tests ก่อนเลื่อนไปเมนูถัดไป
- Commit เป็นชุดเล็ก ๆ ต่อ step

---

## Permission Model (สรุปย่อ)

- **owner** — เจ้าของโรงเรียน สิทธิ์ทุกอย่าง
- **admin** — ผู้ดูแลระบบทั้งโรงเรียน สิทธิ์ทุกอย่าง (ยกเว้นการโอนความเป็นเจ้าของ)
- **department admin** — admin ของฝ่าย/ส่วนงาน สิทธิ์เต็มเฉพาะเมนูที่ผูกกับฝ่ายตนเอง
- **staff / teacher / observer** — สิทธิ์บางส่วนตามหน้าที่ (view/manage รายเมนู)
- **student / guardian** — สิทธิ์ read เฉพาะที่ตัวเองมีส่วนเกี่ยวข้อง

สิทธิ์แต่ละอันใช้ key แบบ `<domain>.<action>` เช่น `members.view`, `students.manage`, `finance.view` — รายละเอียดแต่ละเมนูอยู่ในไฟล์รอง

---

## Menu Inventory & Status

Legend: 🟢 พร้อมใช้งาน (ผ่านการตรวจสอบครบ) · 🟡 มีแล้วแต่ยังไม่ได้ audit · 🔴 มี gap ที่ต้องอุด · ⚪ ยังไม่เริ่ม

| # | เมนู | หน้า (`ui/`) | Permission | ไฟล์รอง | สถานะ |
|---|---|---|---|---|---|
| **1** | **บทบาทและสิทธิ์** | `admin/roles.vue` | `roles.view` / `roles.manage` | [01-roles-permissions.md](01-roles-permissions.md) | 🟢 S1–S6 done · S7/S8 (dept scope) deferred → เมนู #9 |
| 2 | สมาชิก | `admin/members.vue` + `admin/members/` | `members.view` / `members.manage` | [02-members.md](02-members.md) | 🟢 M-S1–S7 done · M-S8/S9/S10 deferred |
| 3 | คำขอเข้าร่วม | `admin/requests.vue` | `members.manage` | [03-join-requests.md](03-join-requests.md) | 🟢 S1 done · deferred: reject reason, pagination, realtime badge |
| 4 | ลิงก์เชิญสมาชิก | `admin/invite-links/` | `members.manage` | [04-invite-links.md](04-invite-links.md) | 🟢 L-S1–S3 done · deferred: L-S4 edit/filter, L-S5 usage history, L-S6 QR |
| 5 | แท็กสมาชิก | `admin/member-tags/` | `members.manage` | [05-member-tags.md](05-member-tags.md) | 🟢 T-S1–S3 done · deferred: T-S4 tag members page, T-S5 reorder |
| 6 | ผู้ปกครอง | `admin/guardians/` | `guardians.*` (เสนอใหม่) | [06-guardians.md](06-guardians.md) | 🟡 เฟส A: G-S0/S1/S2/S2b/S2c ✅ · G-S3 อ่าน 3/10 จุด · G-S4–S6 ค้าง · เฟส B/C รอ #9 |
| 7 | ตั้งค่าโรงเรียน | `admin/settings.vue` | `settings.manage` | 07-settings.md | ⚪ |
| 8 | ระบบบริหารโรงเรียน | `admin/school-management.vue` | `settings.manage` | 08-school-management.md | ⚪ |
| **9** | **ฝ่าย/แผนก** | `admin/departments/` | `departments.*` | [09-departments.md](09-departments.md) | 🔴 **กำลังทำ** — สเปกล็อกแล้ว · D-S1 อุดช่องโหว่ route+authz เป็นงานแรก |
| 10 | ห้องเรียน | `admin/classrooms/` | `groups.view` / `groups.manage` | 10-classrooms.md | 🟡 มีงานเก็บค้าง |
| 11 | ตารางเรียน | `admin/schedule.vue` | `schedule.view` / `schedule.manage` | 11-schedule.md | ⚪ |
| 12 | คอร์สเรียน | `admin/courses/` | `courses.view` / `courses.manage` | 12-courses.md | ⚪ |
| 13 | หลักสูตร | `admin/curriculums.vue` | `courses.view` / `courses.manage` | 13-curriculums.md | ⚪ |
| 14 | บุคลากร | `admin/staff.vue` | `staff.view` / `staff.manage` | 14-staff.md | ⚪ |
| 15 | ทะเบียนนักเรียน | `admin/students.vue` + `admin/students/` | `students.view` / `students.manage` | 15-students.md | ⚪ |
| 16 | บัตรนักเรียน | `admin/student-cards/` | `students.view` / `students.manage` | 16-student-cards.md | 🟡 |
| 17 | เยี่ยมบ้าน | `admin/home-visits/` | `home_visits.view` / `home_visits.manage` | 17-home-visits.md | ⚪ |
| 18 | การเข้าเรียน | `admin/attendance` + `admin/school-attendance/` | `attendance.view` / `attendance.manage` | 18-attendance.md | ⚪ |
| 19 | ผลการเรียน | `admin/grades` + `admin/gradebook/` | `grades.view` / `grades.manage` | 19-grades.md | ⚪ |
| 20 | ประกาศ | `admin/announcements.vue` | `announcements.manage` | 20-announcements.md | ⚪ |
| 21 | สถิติและรายงาน | `admin/reports.vue` | `reports.view` | 21-reports.md | ⚪ |
| 22 | ประวัติกิจกรรม | `admin/activity-log/` | `reports.view` | 22-activity-log.md | ⚪ |
| 23 | รายได้ | `admin/revenue.vue` | `finance.view` / `finance.manage` | 23-revenue.md | 🟡 Phase 1 done |
| **24** | **แดชบอร์ด** | `admin/index.vue` | `academy.view` | 24-dashboard.md | ⚪ (ทำหลังสุด — รวม signal) |
| **25** | **การเลือกตั้งสภานักเรียน** | `admin/elections/` + `elections/[id]/station.vue` | `elections.view` / `elections.manage` / `elections.station` | [25-elections.md](25-elections.md) | 🔴 **กำลังทำ** — E-S1–E-S9 ✅ (หน่วยเลือกตั้ง + หน้าแอดมินครบ 6 แท็บ ยิง HTTP ผ่านครบ) · **ถัดไป E-S10** หน้าสมัครพรรค + ผลสาธารณะ · เหลือ E-S11 hardening · E-S12 ตั้งคณะกรรมการสภาฯ · **ยังไม่ได้ซ้อมตาม §9 เลย** |
| **26** | **เช็คชื่อเข้าร่วมกิจกรรม** | `admin/events/` + หน้า session check-in (ยังไม่มี) | `events.view` / `events.manage` | [26-activity-attendance.md](26-activity-attendance.md) | 🟢 **ปิดครบ A-S0–A-S6** (2026-08-01) |
| **27** | **กีฬาสี** | ยังไม่มี | `sports.*` (เสนอใหม่) | [27-sports-day.md](27-sports-day.md) | 🟢 **ปิดครบ S-S1–S-S7** (2026-08-20) |

> **เมนู #25–#27 เพิ่ม 2026-07-31 ตามคำสั่งเร่งด่วนของเจ้าของโปรเจค** — ทั้งสามเป็นงานของ **ฝ่ายบริหารงานกิจการนักเรียน** และแทรกคิวก่อนเมนูที่เหลือ · ลำดับที่ตกลง: **#25 ก่อน** แล้วค่อย #26 → #27

---

## ลำดับการทำงาน (ตามที่ตกลงกัน)

เริ่มจาก **#1 บทบาทและสิทธิ์** (เป็นรากของทุกอย่าง) แล้วไล่ตามลำดับตัวเลขในตารางด้านบน ยกเว้น #24 แดชบอร์ดทำท้ายสุด

**ไม่ทำงานคู่ขนานข้ามเมนู** — จบเมนูหนึ่งค่อยขึ้นเมนูถัดไป

### ปรับลำดับ (2026-07-29)

**#6 ผู้ปกครอง → เขียนสเปกไว้แล้วแต่ยังไม่ implement, ข้ามไปทำ #9 ฝ่าย/แผนก ก่อน**

เหตุผล: #6 เป็น *งานย่อยระดับฝ่าย* (ใต้ฝ่ายบริหารงานกิจการนักเรียน + งานทะเบียนของฝ่ายวิชาการ) ซึ่งต้องใช้โมเดลสิทธิ์ระดับฝ่ายที่ยังไม่มี — ตัวเดียวกับที่ S7/S8 ของเมนู #1 ถูก defer มารอที่ #9 เมนูที่รอข้างหน้า (#14 บุคลากร, #17 เยี่ยมบ้าน, #18 การเข้าเรียน, #19 ผลการเรียน) ก็ผูกกับฝ่ายแบบเดียวกัน → ตัดสินใจโมเดลทีเดียวที่ #9 แล้วเมนูที่เหลือไหลตามได้ทั้งแถว

**ลำดับใหม่:** #9 → กลับมา #6 (เฟส B/C) → ต่อ #7, #8, #10 …
รายละเอียดข้อตกลงทั้งหมดของ #6 อยู่ใน [06-guardians.md](06-guardians.md) §0

### สถานะคิว (ทวนกับโค้ดจริง 2026-08-23)

#26 กับ #27 ปิดครบแล้ว · **#25 เป็นงานที่ค้างอยู่ตัวเดียวในคิวเร่งด่วน** — backend จบตั้งแต่ 2026-07-31 แต่ฝั่งหน้าจอเพิ่ง commit E-S8 ไว้เฉย ๆ โดยไม่มีใครตรวจ และตรวจแล้วไม่ผ่าน · ลำดับต่อจากนี้: ~~E-S8b · E-S8c · E-S9~~ ✅ → **E-S10** → E-S11 → E-S12 แล้วค่อยกลับไป #9 ที่ยังค้าง D-S6b/D-S7

---

## ตำแหน่งงานอื่น ๆ (nested pages) ที่ไม่อยู่ในเมนูตรง แต่ต้องนับรวมในเมนูแม่

- `admin/allocations.vue`, `admin/at-risk.vue`, `admin/events/`, `admin/store/` — ต้อง map เข้าเมนูใดเมนูหนึ่งใน audit
- `admin/dashboard/admin.vue` (จาก `academies/[name]/dashboard/admin.vue`) — ตรวจว่าซ้ำกับ `admin/index.vue` หรือไม่

Audit จุดพวกนี้ให้เสร็จตอนเข้าเมนูที่เกี่ยวข้อง

---

## Template ของไฟล์รอง

ดู [_template.md](_template.md) (สร้างเมื่อเริ่มเมนูแรก)
