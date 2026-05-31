# School Management System (SMS) — Master Implementation Plan
# Play Learn Earn · nuxnan

**Version:** 2.0 (Refined & Actionable)
**Last updated:** 2026-05-31  
**Status:** Ready for Implementation

---

## 1. Product Vision: The Digital Campus

nuxnan SMS คือ **"Digital School Campus"** ที่เชื่อมโยงผู้คนในโรงเรียนเข้าด้วยกันผ่านระบบที่ใช้งานง่าย สวยงาม และสนุกสนาน โดยมีรากฐานจากแนวคิด **Play Learn Earn**:

- **Play (สังคมและการมีส่วนร่วม)**: โรงเรียนไม่ใช่แค่ที่เรียน แต่เป็นที่พบเจอเพื่อนผ่าน Academy Feed, กิจกรรมชมรม (Clubs), การแข่งขันระหว่างบ้าน (Houses), และ Event ต่างๆ
- **Learn (วิชาการและการเติบโต)**: ระบบบริหารจัดการการเรียนที่โปร่งใส ตารางเรียนที่ชัดเจน การเช็คชื่อที่รวดเร็ว และการติดตามผลการเรียนที่เข้าใจง่าย
- **Earn (ระบบเศรษฐกิจและแรงจูงใจ)**: ทุกความตั้งใจ (มาเรียนตรงเวลา, ทำความดี, ร่วมกิจกรรม) ถูกเปลี่ยนเป็น Points/Merits ที่นำไปแลกรางวัลใน School Store ได้จริง

---

## 2. Phase 0: Foundation Stabilization (Immediate Priority)

**Goal:** เคลียร์ทางให้ API contract ระหว่าง Nuxt และ Laravel มั่นคงก่อนเริ่มสร้าง UI ใหม่

### 2.1 Fix `useSchoolManagement.ts` Route Drifts
ต้องแก้ไขฟังก์ชันต่อไปนี้ใน `ui/composables/useSchoolManagement.ts`:

| Category | Function | Current (Wrong) | Target (Correct) | Method |
|---|---|---|---|---|
| **Meetings** | `getMeetingSlots` | `/meeting-slots` | `/meetings/slots` | GET |
| | `createMeetingSlot` | `/meeting-slots` | `/meetings/slots` | POST |
| | `bookMeeting` | `/meeting-slots/{id}/book` | `/meetings/slots/{id}/book` | POST |
| **Finance** | `getFeeStructures` | `/fees/structures` | `/fee-structures` | GET |
| | `getFeeStructure` | `/fees/structures/{id}` | `/fee-structures/{id}` | GET |
| | `createFeeStructure` | `/fees/structures` | `/fee-structures` | POST |
| | `updateFeeStructure` | `/fees/structures/{id}` | `/fee-structures/{id}` | **PATCH** |
| **Expenses** | `getExpenseCategories` | `/expense-categories` | `/expenses/categories` | GET |
| | `createExpenseCategory` | `/expense-categories` | `/expenses/categories` | POST |
| **Comm** | `updateAnnouncement` | `/announcements/{id}` | same | **PATCH** |
| | `updateEvent` | `/events/{id}` | same | **PATCH** |
| **Layout** | `updateUserDashboardLayout` | `/dashboard/layout` | same | **POST** |

### 2.2 Define School TypeScript Types
สร้างหรืออัปเดต `ui/types/school.ts` เพื่อรองรับข้อมูล SMS ทั้งหมด (หลีกเลี่ยง `any`):
- `Classroom`, `Subject`, `Schedule`
- `FeeStructure`, `Expense`, `Budget`
- `StaffProfile`, `LeaveRequest`, `Payroll`
- `Announcement`, `SchoolEvent`, `EmergencyAlert`
- `MeetingSlot`, `MeetingBooking`

---

## 3. Phase 1: The Heart of the Campus (Dashboards)

**Goal:** ให้ผู้ใช้ทุก Role เห็น "โลกในโรงเรียน" ของตัวเองเมื่อ Login

### 3.1 Student "My School Day" Dashboard
- **UI Components:** `CurrentPeriodCard`, `TodayTimetable`, `PointsBalanceWidget`, `UpcomingAssignments`.
- **Logic:** โหลดตารางสอนวันนี้ + สรุปแต้ม Play Learn Earn ล่าสุด
- **Action:** Fix dead links ใน `ui/pages/academies/[name]/dashboard/student.vue`

### 3.2 Teacher "Classroom Command" Dashboard
- **UI Components:** `NextClassWidget`, `HomeroomShortcuts`, `PendingApprovals` (ใบลา/แต้ม), `ParentMeetingsList`.
- **Logic:** เน้นการเข้าถึง "งานที่ต้องทำตอนนี้" (เช่น เช็คชื่อวิชาถัดไป)

### 3.3 Parent "Child Watch" Dashboard
- **UI Components:** `ChildStatusCard`, `FeeAlertBanner`, `AttendanceTrendChart`.
- **Logic:** สลับดูข้อมูลลูกได้หลายคน (ถ้ามี) เห็นสถานะการมาเรียนและค่าเทอมทันที

---

## 4. Phase 2: Operations & Play Learn Earn Integration

**Goal:** เปลี่ยนกิจกรรมในโรงเรียนให้เป็นข้อมูลและรางวัล

### 4.1 Daily School Attendance (Missing Table)
**Migration Plan:**
```php
Schema::create('classroom_attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
    $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
    $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
    $table->date('date');
    $table->string('status'); // present, absent, late, leave
    $table->string('remark')->nullable();
    $table->foreignId('recorded_by')->constrained('users');
    $table->timestamps();
});
```
**Earn Hook:** เมื่อสถานะเป็น `present` → มอบ 10 Points (จำกัด 1 ครั้งต่อวัน)

### 4.2 Merit & Behavior System
- **Play Hook:** ครูสามารถให้ "Merit Points" สำหรับพฤติกรรมดี (มีผลต่อ House Leaderboard)
- **UI:** ปุ่ม "Give Points" ใน Classroom Roster

### 4.3 Finance & Fees
- **Earn Integration:** ระบบผ่อนชำระค่าเทอม หรือการใช้ Points เป็นส่วนลด (ถ้ามีนโยบาย)
- **UI:** `SchoolFinanceTab.vue` ต้องดึงข้อมูลจาก `fee_structures` และ `payments` จริง

---

## 5. Phase 3: Safety & Unified Communication

### 5.1 Emergency Alert System (End-to-End)
- **Trigger:** Admin กดปุ่ม "Emergency" → ส่ง Push/Notification/Email ทันที
- **UI:** `EmergencyAlertBanner.vue` ปรากฏด้านบนสุดของทุกหน้าใน Academy
- **Play Hook:** การตอบรับ (Acknowledge) หรือแจ้งสถานะ "ปลอดภัย"

### 5.2 Parent-Teacher Meeting Booking
- **UI:** ครูตั้ง Slot เวลาว่าง → ผู้ปกครองกดจอง → ยืนยันผ่านระบบ
- **Safety:** ป้องกันการนัดพบนอกรอบที่ไม่เป็นทางการ

---

## 6. Phase 4: Reports & High-Level Analytics

### 6.1 Director's KPI Dashboard
- **Metrics:** อัตราการเข้าเรียนเฉลี่ย, ยอดค้างชำระรวม, จำนวนเคสเด็กกลุ่มเสี่ยง (At-Risk)
- **Automation:** Scheduled Report ส่งสรุปรายสัปดาห์ทาง Email ให้ผู้บริหาร

---

## 7. Detailed Agent Task Queue (For Implementation)

| ID | Task Name | Description | Target Files |
|---|---|---|---|
| **T01** | Fix Composable Routes | แก้ไข 12 route drifts ใน Phase 0 | `ui/composables/useSchoolManagement.ts` |
| **T02** | Add TypeScript Types | นิยาม Interface สำหรับ School objects ทั้งหมด | `ui/types/school.ts` |
| **T03** | Attendance Migration | สร้างตาราง `classroom_attendances` และ Controller | `api/nuxnanravel/...` |
| **T04** | Student Dashboard Polish | เปลี่ยน TODO เป็น Real Data + Fix ลิงก์ | `ui/pages/academies/[name]/dashboard/student.vue` |
| **T05** | Emergency Alert Logic | Implement notification dispatch ใน Alert Controller | `api/nuxnanravel/...` |
| **T06** | Fee Structure UI | เชื่อมต่อหน้าการเงินกับ API Fee Structure จริง | `ui/components/school/SchoolFinanceTab.vue` |
| **T07** | Point Award Service | สร้าง Service สำหรับมอบแต้มอัตโนมัติ (Attendance/Events) | `api/nuxnanravel/app/Services/PointsService.php` |

---

## 8. Definition of Done (DoD)

1. **Contracts**: URL และ Method ตรงกับ `php artisan route:list`
2. **Types**: ไม่มี `any` ในส่วนที่เกี่ยวข้องกับ SMS
3. **Roles**: ระบบแสดงข้อมูลตามสิทธิ์ (Admin, Teacher, Student, Parent) อย่างถูกต้อง
4. **Loop**: กิจกรรมหลัก (เช็คชื่อ, ประกาศ) ต้องส่ง Event ไปที่ Academy Feed
5. **Quality**: รัน `./vendor/bin/pint` และ `npx vue-tsc` ผ่าน

---

## 9. Risks & Mitigations

- **Complexity**: ระบบ SMS มีขนาดใหญ่ → **Mitigation:** แบ่งทำทีละ Phase ตามลำดับความสำคัญ (Dashboard มาก่อน)
- **Data Integrity**: การเช็คชื่อซ้ำซ้อน → **Mitigation:** ใช้ Unique constraint บน `(student_id, date)` ใน Attendance
- **Performance**: รายงานขนาดใหญ่ → **Mitigation:** ใช้ Database indexing และ Query caching
