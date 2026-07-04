# บทวิเคราะห์โครงสร้าง 5 ฝ่ายงานโรงเรียน — ฉบับปรับปรุง (Revised)

**วันที่:** 2026-07-04
**วัตถุประสงค์:** เทียบโครงสร้าง 5 ฝ่ายงานโรงเรียนตามแนวทางกระทรวงศึกษาธิการ กับ Data Model จริงของ nuxnan เพื่อระบุสิ่งที่มีแล้ว สิ่งที่ต้องเพิ่ม และสิ่งที่ต้องปรับ

---

## สรุป: สถานะ Data Model ปัจจุบันของ nuxnan

ระบบ nuxnan **มี Models ครอบคลุมทั้ง 5 ฝ่ายอยู่แล้ว** แต่ความสมบูรณ์แตกต่างกันมาก:

| ฝ่าย | สถานะ | ความสมบูรณ์ |
|------|--------|------------|
| 1. วิชาการ (Academic) | ✅ สมบูรณ์มาก | 90% — มี Gradebook, Transcript, Remediation, Appeal, Finalization workflow |
| 2. กิจการนักเรียน (Student Affairs) | ✅ สมบูรณ์ปานกลาง | 70% — มี Attendance, Home Visit, Student Profile แต่ยังขาด Behavior/Discipline |
| 3. บุคคล (HR) | ✅ มี Model แล้ว | 50% — มี StaffProfile, LeaveRequest, StaffAttendance, StaffTraining แต่ยังไม่มี Controller/Route ครบ |
| 4. ทั่วไป/IT (General) | ✅ มี Model บางส่วน | 40% — มี SchoolAnnouncement, SchoolEvent, SchoolAsset (ว่าง) |
| 5. งบประมาณ (Finance) | ✅ มี Model แล้ว | 50% — มี Budget, Payment, Scholarship, TuitionFee แต่ยังไม่มี UI ครบ |

---

## 1. ฝ่ายบริหารงานวิชาการ (Academic Affairs) — ✅ สมบูรณ์ที่สุด

### สิ่งที่มีแล้ว (Existing)

#### 1.1 Core Foundation (ฐานข้อมูลหลัก)

| บทวิเคราะห์เดิมเสนอ | ระบบจริง | หมายเหตุ |
|---------------------|---------|---------|
| `AcademicYears` | ✅ `academic_years` | มี `academy_id`, `is_current`, `start_date`, `end_date` |
| `Semesters` | ✅ `semesters` | มี `semester_number` (1,2,3=summer), `is_current`, ผูกกับ `academic_year_id` |
| `GradeLevels` (ตารางแยก) | ⚠️ **ไม่มีตารางแยก** | เป็น string field `grade_level` บน `classrooms` + JSON `grade_levels` บน `subjects` |
| `Classrooms` | ✅ `classrooms` | มี `grade_level`, `section`, `homeroom_teacher_id`, `capacity`, `classroom_code` (6-char), `academic_year_id` |
| `StudentProfiles` | ✅ `students` | **รวยกว่าที่เสนอมาก** — ชื่อไทย/อังกฤษ, `citizen_id`, guardians, health, addresses, contacts, documents, home visits, student card |
| `TeacherProfiles` (ตารางแยก) | ⚠️ **ไม่มีตารางเฉพาะครู** | ครู = `User` + `AcademyMember` (role=teacher) + อาจมี `StaffProfile` |

#### 1.2 หลักสูตรและรายวิชา

| บทวิเคราะห์เดิมเสนอ | ระบบจริง | หมายเหตุ |
|---------------------|---------|---------|
| `Subjects` | ✅ `subjects` | มี `subject_code`, `name_th`, `name_en`, `credits`, `hours_per_week`, `subject_type` (required/elective/activity), `subject_group` (8 กลุ่มสาระ), `grade_levels` (JSON) |
| `CourseSections` (ตารางเชื่อมใหม่) | ❌ **ไม่ควรสร้าง** | `Course` ทำหน้าที่นี้อยู่แล้ว — ผูก `academy_id`, `semester`, `academic_year`, มี `Subject.courses()` hasMany |

**สถาปัตยกรรมจริง:**
```
Subject (หลักสูตร/รายวิชา)
  └── Course (การเปิดรายวิชา = "CourseSections" ในบทวิเคราะห์เดิม)
        ├── academy_id (โรงเรียน)
        ├── semester, academic_year (ภาคเรียน)
        ├── user_id (ผู้สร้าง), instructor_id (ผู้สอน)
        ├── CourseAdmin (ครูผู้สอนเพิ่มเติม)
        ├── CourseMember (นักเรียนที่ลงทะเบียน)
        └── CourseGroup (กลุ่มย่อยในรายวิชา)
```

#### 1.3 ระบบวัดผลและเกรด (Gradebook)

| Model | Table | คำอธิบาย |
|-------|-------|---------|
| `GradebookAssessment` | `gradebook_assessments` | กำหนด assessment + น้ำหนักคะแนน (weight %), ผูก semester, sync จาก Assignment/Quiz ได้ |
| `GradebookScore` | `gradebook_scores` | คะแนนรายนักเรียนต่อ assessment (status: pending/graded/excused/missing) |
| `CourseGrade` | `course_grades` | **เกรดสรุปรายวิชา** — `total_score`, `percentage`, `letter_grade`, `grade_points`, approval workflow |
| `GradeScale` + `GradeScaleItem` | `grade_scales` | เกณฑ์ตัดเกรดที่ปรับได้ต่อ Academy (A=80-100%, B+=75-79%, ...) |
| `CourseExternalScore` | `course_external_scores` | คะแนนจากแหล่งภายนอก (เช่น สอบกลาง) |

#### 1.4 ใบ ปพ. (Transcript)

| Model | Table | คำอธิบาย |
|-------|-------|---------|
| `SemesterTranscript` | `semester_transcripts` | **ปพ.5 — ผลการเรียนรายภาค** — GPA, ranking ในชั้น/ระดับ, `total_credits`, `earned_credits`, approval workflow |
| `SemesterTranscriptItem` | `semester_transcript_items` | รายวิชาแต่ละตัวใน transcript |
| `AnnualTranscript` | `annual_transcripts` | **ปพ.6 — ผลการเรียนรายปี** |

> **หมายเหตุ:** ระบบ Transcript สมบูรณ์กว่า `AcademicRecords` ที่เสนอมามาก — มีทั้ง generate, publish, approve workflow

#### 1.5 ระบบเสริมวิชาการ (ที่บทวิเคราะห์เดิมไม่ได้กล่าวถึง)

| Model | คำอธิบาย |
|-------|---------|
| `CourseRemediationSession` + `CourseRemediationEnrollment` | **ระบบสอบแก้ตัว** — `allow_remediation`, `max_remediation_attempts`, `remediation_max_grade` |
| `GradeAppeal` | **ระบบอุทธรณ์เกรด** — `allow_grade_appeal`, `appeal_deadline_days` |
| `CourseFinalizationLog` | **Workflow ปิดรายวิชา** — not_started → grading → published → finalized → archived |
| `GradeEditLog` | **Audit trail** การแก้ไขเกรด |

#### 1.6 LMS (ห้องเรียนออนไลน์)

| บทวิเคราะห์เดิมเสนอ | ระบบจริง | หมายเหตุ |
|---------------------|---------|---------|
| `LMS_Courses` (แยกจาก CourseSections) | ❌ **ไม่แยก** | `Course` ทำทั้ง Academic + LMS ในตัวเดียว |
| `LMS_Topics` | ✅ `lessons` → `topics` | เป็น **2 ชั้น**: Course → Lesson (บท) → Topic (หัวข้อย่อย) |
| `LMS_Assignments` | ✅ `assignments` | **Polymorphic** — ผูกได้ทั้งกับ Lesson หรือ Topic |
| `LMS_Quizzes` | ✅ `course_quizzes` | มี duration, passing_score, shuffle, show_answers |
| `LMS_Submissions` | ✅ `assignment_answers` + `course_quiz_results` | แยกตาม type |

**สถาปัตยกรรม LMS จริง:**
```
Course (ห้องเรียนออนไลน์ = รายวิชาที่เปิดสอน)
  ├── Lesson (บทเรียน) — ordered by `order`
  │     ├── Topic (หัวข้อย่อย) — ordered by `sort_order`
  │     │     ├── Assignment (morphMany) — งานมอบหมาย
  │     │     └── TopicReadProgress — tracking อ่านจบ
  │     ├── Assignment (morphMany) — งานระดับบท
  │     └── LessonProgress — tracking เรียนจบ
  ├── CourseQuiz — แบบทดสอบ
  ├── CourseAttendance → AttendanceDetail — เช็กชื่อรายคาบ
  ├── CourseGroup — กลุ่มย่อย
  └── GradebookAssessment → GradebookScore — คะแนน (sync จาก Assignment/Quiz)
```

### สิ่งที่ยังขาด/ต้องเพิ่มในฝ่ายวิชาการ

| รายการ | ความสำคัญ | หมายเหตุ |
|--------|----------|---------|
| ระบบจัดตารางสอน (Timetable) | 🔴 สูง | Course มี `class_schedule` (JSON) แต่ยังไม่มี Timetable engine ที่จัดตาราง auto |
| เชื่อม Subject → Course อย่างเป็นระบบ | 🟡 กลาง | Subject hasMany courses มีแล้ว แต่ UI สำหรับ "เปิดรายวิชาจาก Subject" ยังไม่ครบ |
| ระบบจัดการทดสอบระดับชาติ (O-NET) | 🟢 ต่ำ | ยังไม่จำเป็นในระยะแรก |

---

## 2. ฝ่ายบริหารงานกิจการนักเรียน (Student Affairs) — ✅ มีพื้นฐานดี

### สิ่งที่มีแล้ว

| งาน | Models ที่มี | สถานะ |
|-----|-------------|-------|
| **โปรไฟล์นักเรียน** | `Student` + `StudentAcademicInfo` + `StudentAddress` + `StudentContact` + `StudentGuardian` + `StudentHealthInfo` + `StudentDocument` | ✅ สมบูรณ์มาก (เพิ่งทำ Master Profile Unification เสร็จ Phase 0-10) |
| **บัตรนักเรียน** | `StudentCard` | ✅ มี QR, photo, flip card |
| **เยี่ยมบ้าน** | `StudentHomeVisit` | ✅ CRUD + status workflow |
| **เช็กชื่อรายวัน** | `SchoolAttendance` + `SchoolAttendanceRecord` | ✅ เช็กชื่อระดับโรงเรียน |
| **เช็กชื่อรายคาบ** | `CourseAttendance` + `AttendanceDetail` | ✅ เช็กชื่อรายวิชา/กลุ่ม |
| **การลงทะเบียนห้องเรียน** | `ClassroomStudent` | ✅ มี enrollment history, status tracking (active/promoted/graduated/transferred/repeating) |
| **เลื่อนชั้น** | `RolloverBatch` + `StudentEnrollmentService` + `AcademicYearRolloverService` | ✅ preview → plan → commit → undo workflow |
| **กิจกรรมนักเรียน** | `SchoolEvent` + `EventRegistration` + `ActivityEnrollment` + `ActivitySession` + `ActivityAttendance` | ✅ รองรับชมรม/ชุมนุม/กิจกรรม |
| **Gamification** | `SchoolXpCycle` + `CoursePointAccount` + `CoursePointTransaction` | ✅ ระบบแต้ม/XP |

### สิ่งที่ยังขาด/ต้องเพิ่ม

| รายการ | ความสำคัญ | หมายเหตุ |
|--------|----------|---------|
| **ระบบดูแลช่วยเหลือนักเรียน (SDQ Screening)** | 🔴 สูง | ยังไม่มี Model — ต้องสร้าง `StudentScreening` (แบบคัดกรอง SDQ), `StudentCounselingLog` (บันทึกการให้คำปรึกษา) |
| **ระบบคะแนนความประพฤติ (Behavior/Discipline)** | 🔴 สูง | ยังไม่มี Model — ต้องสร้าง `BehaviorLog` (บันทึกพฤติกรรม +/- คะแนน), `DisciplineCase` (คดีวินัยร้ายแรง) |
| **ระบบสภานักเรียน** | 🟢 ต่ำ | สามารถใช้ `AcademyGroup` (type=student_council) + `SchoolEvent` (type=election) ได้ |

---

## 3. ฝ่ายบริหารงานบุคคล (HR) — ✅ มี Model แล้ว แต่ยังขาด Controller/Route

### สิ่งที่มีแล้ว

| Model | Table | คำอธิบาย |
|-------|-------|---------|
| `StaffProfile` | `staff_profiles` | ข้อมูลบุคลากร — `employee_id`, `citizen_id`, `department_id`, `position_id`, `employment_type` (full_time/part_time/contract/temporary), `education_history` (JSON), `certifications` (JSON) |
| `StaffAttendance` | `staff_attendances` | ลงเวลา — `check_in_time`, `check_out_time`, `work_hours`, `overtime_hours`, status (present/absent/late/early_leave/half_day/on_leave/holiday/work_from_home) |
| `LeaveRequest` | `leave_requests` | ใบลา — `leave_type_id`, `start_date`, `end_date`, `total_days`, `substitute_staff_id`, approval workflow |
| `LeaveType` | `leave_types` | ประเภทการลา — `max_days_per_year` |
| `StaffTraining` | `staff_trainings` | อบรม — `training_program_id`, `score`, `certificate_number`, `certificate_expiry` |
| `Department` | `departments` | ฝ่ายงาน/กลุ่มสาระ |
| `Position` | `positions` | ตำแหน่ง |

> **หมายเหตุ:** `StaffProfile` ≠ `AcademyMember` — `StaffProfile` เก็บข้อมูล HR เฉพาะ (สัญญาจ้าง, ลา, เงินเดือน) ส่วน `AcademyMember` เก็บ role/permission ในระบบ

### สิ่งที่ยังขาด/ต้องเพิ่ม

| รายการ | ความสำคัญ | มี Model? | หมายเหตุ |
|--------|----------|----------|---------|
| **Payroll (เงินเดือน)** | 🟡 กลาง | มี `Payroll` model (ref จาก StaffProfile) | ยังไม่มี Controller/Route |
| **PerformanceReview (ประเมิน)** | 🟡 กลาง | มี `PerformanceReview` model (ref จาก StaffProfile) | ยังไม่มี Controller/Route |
| **TrainingProgram (หลักสูตรอบรม)** | 🟡 กลาง | มี `TrainingProgram` model (ref จาก StaffTraining) | ยังไม่มี Controller/Route |
| **เชื่อม StaffProfile ↔ AcademyMember** | 🔴 สูง | ยังไม่ชัดเจน | ครูคนเดียวอาจมีทั้ง StaffProfile (HR) และ AcademyMember (LMS) — ต้องมี link ที่ชัด |
| **วิทยฐานะ** | 🟢 ต่ำ | ไม่มี | เพิ่มเป็น field ใน StaffProfile ได้ |

---

## 4. ฝ่ายบริหารงานทั่วไปและ IT (General & IT) — ✅ มีบางส่วน

### สิ่งที่มีแล้ว

| Model | Table | คำอธิบาย |
|-------|-------|---------|
| `SchoolAnnouncement` | `school_announcements` | ประกาศ — type (general/academic/financial/event/emergency), priority, target_audience (JSON), is_pinned, expires_at, read tracking |
| `SchoolEvent` | `school_events` | กิจกรรม/ปฏิทิน — event_type (14 ประเภท), attendance_pattern, location, registration, recurring support |
| `SchoolAsset` | `school_assets` | ทรัพย์สิน — **Model ว่าง ยังไม่ implement** |
| `AcademySetting` | `academy_settings` | ตั้งค่าระบบ — cached 24 ชม. |

### สิ่งที่ยังขาด/ต้องเพิ่ม

| รายการ | ความสำคัญ | หมายเหตุ |
|--------|----------|---------|
| **ทะเบียนทรัพย์สิน (Asset Management)** | 🟡 กลาง | `SchoolAsset` มีแล้วแต่ว่าง — ต้อง implement fields + CRUD |
| **ระบบจองห้อง/สถานที่** | 🟡 กลาง | ใช้ `SchoolEvent` (location_type) + ต้องเพิ่ม `RoomBooking` model |
| **ระบบแจ้งซ่อม** | 🟢 ต่ำ | ยังไม่มี — อาจเพิ่มภายหลัง |
| **หนังสือเข้า-ออก (สารบรรณ)** | 🟢 ต่ำ | ยังไม่มี — เป็นงานเฉพาะทางที่อาจไม่จำเป็นในระยะแรก |

---

## 5. ฝ่ายบริหารงบประมาณ (Budget & Finance) — ✅ มี Model แล้ว

### สิ่งที่มีแล้ว

| Model | Table | คำอธิบาย |
|-------|-------|---------|
| `Budget` | `budgets` | งบประมาณ — `allocated_amount`, `spent_amount`, `remaining_amount`, ผูก `academic_year_id`, `category_id` → `ExpenseCategory` |
| `Expense` + `ExpenseCategory` | `expenses`, `expense_categories` | รายจ่าย — approval workflow, ผูกกับ Budget |
| `TuitionFee` | `tuition_fees` | ค่าเทอม/ค่าบำรุง |
| `Payment` | `payments` | ชำระเงิน — `receipt_number`, `slip_image`, `bank_name`, confirm/reject workflow |
| `PaymentMethod` | `payment_methods` | วิธีชำระ |
| `PaymentPlan` + `StudentPaymentPlan` | `payment_plans`, `student_payment_plans` | แผนผ่อนชำระ |
| `Scholarship` + `StudentScholarship` | `scholarships`, `student_scholarships` | ทุนการศึกษา — `discount_type` (percentage/fixed), `max_recipients` |

### สิ่งที่ยังขาด/ต้องเพิ่ม

| รายการ | ความสำคัญ | หมายเหตุ |
|--------|----------|---------|
| **ระบบพัสดุ/จัดซื้อจัดจ้าง** | 🟡 กลาง | ยังไม่มี — ต้องสร้าง `PurchaseOrder`, `PurchaseItem` |
| **ใบเสร็จอิเล็กทรอนิกส์/PDF** | 🟡 กลาง | `Payment` มี `receipt_number` แต่ยังไม่มี PDF generator |
| **รายงานสรุปการเงิน** | 🟡 กลาง | ข้อมูลมีแล้ว แต่ยังไม่มี dashboard/report view |

---

## Data Flow ข้ามฝ่าย — จุดเชื่อมต่อที่สำคัญ

### จุดเชื่อมที่มีอยู่แล้ว

```
Academy (ศูนย์กลาง — multi-tenant)
  ├── AcademicYear → Semester (ปฏิทินการศึกษา)
  ├── Classroom ← ClassroomStudent → Student (ห้องเรียน ↔ นักเรียน)
  ├── Subject → Course (หลักสูตร → รายวิชาที่เปิดสอน)
  │     ├── CourseMember (นักเรียนลงทะเบียน — ผูก User)
  │     ├── CourseAdmin (ครูผู้สอน — ผูก User)
  │     ├── GradebookAssessment → GradebookScore (คะแนน)
  │     ├── CourseGrade (เกรดสรุป)
  │     └── CourseAttendance → AttendanceDetail (เช็กชื่อรายคาบ)
  ├── SchoolAttendance → SchoolAttendanceRecord (เช็กชื่อรายวัน)
  ├── AcademyMember (สมาชิก — role-based permissions)
  ├── AcademyGroup (กลุ่ม — classroom/department/club)
  ├── Student (โปรไฟล์นักเรียน — ข้อมูลส่วนตัว, สุขภาพ, ผู้ปกครอง)
  ├── StaffProfile (โปรไฟล์บุคลากร — HR data)
  ├── Budget → Expense (งบประมาณ → รายจ่าย)
  ├── TuitionFee → Payment (ค่าเทอม → ชำระเงิน)
  └── SchoolAnnouncement, SchoolEvent (สื่อสาร)
```

### จุดเชื่อมที่ต้องสร้าง/เสริม

| จุดเชื่อม | ฝ่ายที่เกี่ยว | สถานะ |
|-----------|-------------|-------|
| `StaffProfile` ↔ `AcademyMember` ↔ `User` | HR + วิชาการ | ⚠️ ต้องทำให้ชัดเจน — ครูคนเดียวต้องเชื่อมทั้ง 3 |
| `Student` ↔ `CourseMember` ↔ `User` | กิจการ + วิชาการ | ✅ มีแล้ว ผ่าน `Student.user_id` → `CourseMember.user_id` |
| `ClassroomStudent` ↔ `CourseGrade` | กิจการ + วิชาการ | ✅ ผ่าน `student_id` |
| `Scholarship` ↔ `TuitionFee` | งบประมาณ + กิจการ | ✅ ผ่าน `StudentScholarship` + `StudentPaymentPlan` |
| `BehaviorLog` ↔ `Student` + `Teacher` | กิจการ + บุคคล | ❌ ยังไม่มี |
| `Timetable` ↔ `Course` + `Classroom` + `Teacher` | วิชาการ + ทั่วไป | ❌ ยังไม่มี |

---

## Role & Permission — ระบบที่มีอยู่

ระบบ permission ปัจจุบันแบ่งเป็น 2 ระดับ:

### ระดับ Academy (โรงเรียน)
- `AcademyRole` → กำหนด permissions ตาม role
- `AcademyMember.academyRole()` → ตรวจสิทธิ์ด้วย `hasPermission()`
- Role helpers: `isAdmin()`, `isTeacher()`, `isStudent()`, `isParent()`

### ระดับ Course (รายวิชา)
- `CourseAdmin` → ครูผู้สอน/ผู้ดูแลรายวิชา
- `CoursePermission` → สิทธิ์ย่อยในรายวิชา
- `CourseMember` → นักเรียนที่ลงทะเบียน

> **สิ่งที่ขาด:** Role mapping ระดับฝ่ายงาน — ปัจจุบันยังไม่มีแนวคิด "หัวหน้าฝ่ายวิชาการ" หรือ "หัวหน้าฝ่ายกิจการ" ในระบบ permission อาจใช้ `AcademyGroup` (type=department) + admin ของ group ได้

---

## ข้อแก้ไขจากบทวิเคราะห์เดิม — สรุป

### ❌ สิ่งที่ไม่ควรทำตามบทวิเคราะห์เดิม

1. **อย่าสร้าง `CourseSections` ตารางใหม่** — `Course` ทำหน้าที่นี้อยู่แล้ว การสร้างตารางเชื่อมใหม่จะทำให้ต้อง refactor ระบบ Gradebook, Attendance, Enrollment ทั้งหมด
2. **อย่าแยก `LMS_Courses` ออกจาก `Course`** — สถาปัตยกรรม Course-as-LMS ทำงานได้ดีและมี workflow ครบ (Finalization, Remediation, Appeal)
3. **อย่าสร้าง `GradeLevels` ตารางแยก** (ในตอนนี้) — ใช้ string field `grade_level` ก็เพียงพอ การเปลี่ยนจะกระทบ Classroom, Subject, Student ทั้งหมด
4. **อย่าสร้าง `AcademicRecords` ตารางเดียว** — ระบบปัจจุบัน (`CourseGrade` + `SemesterTranscript` + `AnnualTranscript`) ละเอียดและสมบูรณ์กว่ามาก

### ✅ สิ่งที่ควรทำต่อ (Priority Order)

1. **🔴 สร้างระบบคะแนนความประพฤติ (Behavior/Discipline)** — เป็นงาน core ของฝ่ายกิจการนักเรียนที่ยังไม่มี
   - สร้าง `BehaviorLog` model — ผูก `student_id` + `recorded_by` (teacher)
   - สร้าง `BehaviorCategory` — ประเภทพฤติกรรม (ดี/ไม่ดี)
   - อาจรวมกับระบบ Gamification Points ที่มีอยู่

2. **🔴 สร้างระบบคัดกรอง SDQ** — กระทรวงกำหนดให้ทำ
   - สร้าง `StudentScreening` model — แบบประเมิน SDQ
   - สร้าง `StudentCounselingLog` — บันทึกการให้คำปรึกษา

3. **🔴 เชื่อม StaffProfile ↔ AcademyMember** — ให้ครูมี HR data (ลา, เงินเดือน) ที่เชื่อมกับ role ในระบบ

4. **🟡 Implement Controller/Route สำหรับ HR models** — StaffProfile, LeaveRequest, StaffAttendance มี Model แล้วแต่ยังขาด API

5. **🟡 Implement Controller/Route สำหรับ Finance models** — Budget, Payment, Scholarship มี Model แล้วแต่ยังขาด UI ครบ

6. **🟡 เพิ่ม SchoolAsset** — ทะเบียนทรัพย์สิน model ว่างอยู่

7. **🟢 ระบบจัดตารางสอน (Timetable)** — ซับซ้อนสูง อาจทำทีหลัง

---

## Architecture Decision Records (ADR)

### ADR-1: Course = CourseSection (ไม่แยก)
- **ตัดสินใจ:** ไม่สร้างตาราง `CourseSections` ใหม่
- **เหตุผล:** Course มี field ที่ทำหน้าที่ CourseSections อยู่แล้ว (`academy_id`, `semester`, `academic_year`, `instructor_id`) และมี Gradebook, Attendance, Enrollment ผูกอยู่ทั้งหมด
- **ผลกระทบ:** ต้องยอมรับว่า Course model จะมี fields เยอะ (ทั้ง LMS + Academic) — trade-off ที่ยอมรับได้เพราะหลีกเลี่ยง migration ขนาดใหญ่

### ADR-2: GradeLevel เป็น String ไม่ใช่ FK
- **ตัดสินใจ:** ยังคงใช้ `grade_level` เป็น string field
- **เหตุผล:** ใช้งานได้ดี, ระบบ Subject ใช้ JSON array `grade_levels` ที่ยืดหยุ่นกว่า FK
- **ทบทวนเมื่อ:** ต้องการ report ที่ aggregate ตาม grade level หรือต้องการจัดลำดับ grade levels แบบ custom

### ADR-3: Teacher = User + AcademyMember (ไม่มี TeacherProfile แยก)
- **ตัดสินใจ:** ครูผู้สอนคือ `User` ที่มี `AcademyMember` (role=teacher) + อาจมี `StaffProfile` (HR data)
- **เหตุผล:** หลีกเลี่ยง N+1 profile tables; StaffProfile ครอบคลุมข้อมูล HR ของทุกบุคลากร ไม่เฉพาะครู
- **ผลกระทบ:** ข้อมูลเฉพาะครู (เช่น วิทยฐานะ, วิชาเอก) ต้องเก็บใน `StaffProfile.certifications` (JSON) หรือเพิ่ม field ใน StaffProfile
