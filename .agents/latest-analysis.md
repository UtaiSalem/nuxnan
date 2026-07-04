# Student Intake System — แผนพัฒนาฉบับปรับปรุง (Revised v2)

> วิเคราะห์จาก codebase จริง ณ 2026-07-04  
> แก้ไขจาก User Analysis Input — ปรับปรุงให้สอดคล้องกับโครงสร้างที่มีอยู่แล้ว

---

## User Analysis Input

*(เนื้อหาจากแผนเดิมของผู้ใช้ — สรุปย่อ)*

แผนเดิมเสนอ 6 Phases:
- Phase 0: ล็อกกติกาทางทะเบียน
- Phase 1: Database Integrity
- Phase 2: Single Student Intake Backend
- Phase 3: Bulk Import Backend
- Phase 4: Registrar UI
- Phase 5: Guardian + Account Activation
- Phase 6: Operational Readiness

---

## Codebase Audit — สิ่งที่มีอยู่แล้ว

### ✅ มีแล้วและพร้อมใช้
| Component | สถานะ | หมายเหตุ |
|-----------|--------|----------|
| `Student` model | สมบูรณ์ | fillable ครบ, has academy_id + user_id (nullable), relationships ครบ |
| `StudentEnrollmentService` | สมบูรณ์มาก | enrollStudent, transfer, promote, graduate, drop, repeat — ทุก method อยู่ใน DB::transaction แล้ว |
| `ClassroomStudent` pivot | สมบูรณ์ | 7 statuses, audit trail via created_by_user_id |
| `StudentAcademicInfo` | สมบูรณ์ | is_current flag, updateOrCreate per year, education_level |
| `AcademyMember` + `AcademyRole` | สมบูรณ์ | permission-based system, hasPermission() ทำงานแล้ว |
| `AcademicYear` | สมบูรณ์ | per-academy, is_current flag |
| `StudentGuardian` | สมบูรณ์ | guardian_type, citizen_id, contacts, primary/emergency |
| `AuditLog` + `Auditable` trait | สมบูรณ์ | auto-audit on create/update/delete |
| Student Master Profile (8 tabs) | สมบูรณ์ | addresses, contacts, guardians, health, academic-info, documents, cards, home-visits |
| Events | มีแล้ว | StudentEnrolled, StudentTransferred, StudentPromoted, StudentGraduated, StudentDropped, StudentRepeated |
| Queue infrastructure | พื้นฐาน | 5 jobs อยู่แล้ว, queue driver configured |

### ⚠️ ปัญหาที่ต้องแก้ (จาก Audit)

| # | ปัญหา | ผลกระทบ | ระดับ |
|---|--------|---------|-------|
| **D1** | `students.student_id` เป็น `UNIQUE` ทั้ง DB | โรงเรียน 2 แห่งใช้รหัสนักเรียนซ้ำกันไม่ได้ (เช่น "00001") | Critical |
| **D2** | `students.citizen_id` เป็น `UNIQUE` ทั้ง DB | นักเรียนย้ายโรงเรียนใน platform เดียวกันไม่ได้ (ถ้า multi-academy) | Critical |
| **D3** | `students.status` เป็น ENUM ที่ DB level | เพิ่มค่าใหม่ต้อง ALTER TABLE, ไม่มี `dropped` แต่ UI บางที่ใช้ | Medium |
| **D4** | ไม่มี `registrar` role ใน system roles | permissions มีแค่ `students.view` + `students.manage` ยังไม่ละเอียดพอ | Medium |
| **D5** | `StudentEnrollmentService::enrollStudent()` ใช้ `updateOrCreate` | ถ้า student เคยอยู่ห้องเดียวกันแล้วจะ update row เดิม ไม่ create ใหม่ — อาจทำให้ประวัติหาย | Low (by design) |
| **D6** | ไม่มี import batch tables | ไม่มีโครงสร้างรองรับ bulk import | Expected gap |

### ❌ สิ่งที่ยังไม่มีเลย (ช่องว่างจริง)
1. **StudentIntakeService** — orchestrator ที่รวม User + Student + AcademyMember + Enrollment + AcademicInfo ใน transaction เดียว
2. **Duplicate detection logic** — ตรวจ citizen_id / student_id ซ้ำก่อนสร้าง
3. **Import batch infrastructure** — tables, parser, validation, confirm flow
4. **Account activation system** — invitation token, activation flow
5. **Registrar-specific permissions** — students.create, students.import, students.lifecycle, students.activate-account
6. **Registrar UI page** — `/academies/{name}/admin/students` (อาจมีบ้างแต่ไม่ใช่ intake flow)

---

## Work Plan

### ลำดับ Phases ที่ปรับปรุงแล้ว

```
Phase 0: Domain Rules Lock (ไม่เขียนโค้ด — ตกลงกติกา)
Phase 1: Database Constraints Fix (migration only)
Phase 2: StudentIntakeService + API (single student)
Phase 3: Registrar UI — Single Intake
Phase 4: Bulk Import Backend
Phase 5: Bulk Import UI
Phase 6: Account Activation + Operational Reports
```

**เปลี่ยนแปลงจากแผนเดิม:**
- รวม Phase 0 + Phase 1 เดิมเข้าด้วยกัน (gating rules + DB fix ควรทำพร้อมกัน)
- แยก UI ออกจาก Backend ชัดเจนกว่า (Single UI → Phase 3, Bulk UI → Phase 5)
- Guardian เก็บตั้งแต่ Phase 2 (มีโครงสร้างพร้อมแล้ว) ไม่ต้องแยก phase
- Account Activation เลื่อนไปท้ายสุด (value ต่ำกว่า intake flow หลัก)

---

## Phase 0 — Domain Rules Lock

> **เป้าหมาย:** ตกลง business rules ให้ชัดก่อนเขียน code  
> **Deliverable:** เอกสารยืนยันใน file นี้ section "Locked Rules"  
> **Duration:** 1 session (discussion only)

### 0.1 Identity Architecture (ยืนยันจาก codebase)

```
users (id, email, password, ...)          → บัญชีเข้าใช้งาน (nullable สำหรับนักเรียน)
students (id, academy_id, user_id?, ...)  → ระเบียนนักเรียน (1 per academy)
academy_members (user_id, academy_id, student_id?, academy_role_id)  → membership
classroom_students (student_id, classroom_id, academic_year_id, status) → enrollment history
student_academic_info (student_id, academic_year, is_current)  → snapshot ต่อปี
```

**Policy ที่ต้องล็อก:**
- นักเรียนสร้างได้โดยไม่ต้องมี `user_id` (✅ schema รองรับแล้ว — nullable)
- `AcademyMember` สร้างได้โดยไม่ต้องมี `user_id` → **ต้องตรวจ** (ปัจจุบัน `user_id` ใน `academy_members` อาจเป็น required)
- Account activation เป็น separate step ทำภายหลังได้

### 0.2 Duplicate Detection Rules

| Field | Scope | ผลเมื่อพบซ้ำ |
|-------|-------|--------------|
| `student_id` (รหัสนักเรียน) | Per academy | Block — ต้องแก้ก่อนบันทึก |
| `citizen_id` (เลขประชาชน) | Per academy | Warn — แสดงข้อมูลเดิม ให้เจ้าหน้าที่ตัดสิน |
| `citizen_id` cross-academy | Global | Info — แจ้งว่ามีในระบบแล้ว (กรณีย้ายโรงเรียน) |
| `first_name_th` + `last_name_th` + `date_of_birth` | Per academy | Warn — possible duplicate |

**Resolution options เมื่อพบ citizen_id ซ้ำใน academy เดียวกัน:**
1. "ใช้ข้อมูลเดิม" — link existing student, skip create
2. "สร้างใหม่" — override (กรณี citizen_id ผิดพลาด ให้แก้ก่อน)
3. "ยกเลิก" — abort

### 0.3 Status Contract (Confirmed from Code)

| เหตุการณ์ | `students.status` | `classroom_students.status` | `student_academic_info.study_status` |
|-----------|-------------------|----------------------------|--------------------------------------|
| กำลังเรียน | `active` | `active` | `studying` |
| ย้ายห้อง (ในปี) | `active` | old=`transferred`, new=`active` | `studying` (update classroom_id) |
| เลื่อนชั้น (ข้ามปี) | `active` | old=`promoted`, new=`active` | old closes, new `studying` |
| ซ้ำชั้น | `active` | old=`repeating`, new=`active` | new snapshot `studying` |
| จบการศึกษา | `graduated` | `graduated` | `graduated` |
| ลาออก/พ้นสภาพ | `inactive` | `dropped` | `dropped` |
| ย้ายสถานศึกษา | `transferred` | `transferred` | `transferred` |
| พักการเรียน | `inactive` | `dropped` (with reason) | `suspended` |

**ข้อสังเกต:** `students.status` ENUM ปัจจุบันมี `['active', 'inactive', 'graduated', 'transferred']` — ครอบคลุมเพียงพอแล้ว เพราะ "ลาออก" และ "พักการเรียน" ใช้ `inactive` ส่วนรายละเอียดอยู่ที่ `classroom_students.leave_reason`

### 0.4 Permission Model (Revised)

ปัจจุบัน `AcademyRole::SYSTEM_ROLES` มี permissions:
- `students.view` — ดูข้อมูลนักเรียน
- `students.manage` — จัดการนักเรียน (กว้างเกินไป)

**เสนอเพิ่ม (granular):**
```php
'students.create'           // สร้างนักเรียนใหม่ (single intake)
'students.import'           // นำเข้าจำนวนมาก (bulk)
'students.lifecycle'        // เปลี่ยนสถานะ (graduate, drop, transfer)
'students.activate_account' // เปิดบัญชีให้นักเรียน
'students.export'           // ส่งออกข้อมูล
```

**สร้าง role "registrar":**
```php
'registrar' => [
    'display_name_th' => 'นายทะเบียน',
    'display_name_en' => 'Registrar',
    'color' => 'teal',
    'icon' => 'fluent:clipboard-text-24-filled',
    'sort_order' => 5, // between admin and staff
    'permissions' => [
        'academy.view',
        'members.view',
        'students.view', 'students.create', 'students.import',
        'students.lifecycle', 'students.activate_account', 'students.export',
        'reports.view',
        'announcements.view',
    ],
],
```

Backward-compatible: existing `students.manage` ยังทำงานได้ — `hasPermission()` ตรวจ hierarchical อยู่แล้ว (`students.manage` grants `students.manage.*` แต่ไม่ grant `students.create` ตรงๆ)

**ตัดสินใจ:** ให้ `students.manage` เป็น alias ที่ grants ทุก students.* sub-permission → ต้องแก้ `hasPermission()` ให้ตรวจ parent prefix ด้วย

### 0.5 Student Code Format

| Field | Format | Example | หมายเหตุ |
|-------|--------|---------|----------|
| `student_id` | Academy กำหนดเอง | "00123", "2569-001", "STD001" | Max 20 chars, unique per academy |
| Auto-generate | Optional | `{year_suffix}{running_4digit}` → "69-0001" | ถ้า academy ไม่กำหนดเอง |

### 0.6 Acceptance Criteria for Phase 0

- [x] Status mapping confirmed
- [ ] `AcademyMember.user_id` nullable confirmed (ต้องตรวจ migration)
- [ ] Duplicate resolution policy signed off
- [ ] Permission additions approved
- [ ] Student code format approved
- [ ] Account activation policy approved (pending vs immediate)

---

## Phase 1 — Database Constraints Fix

> **เป้าหมาย:** แก้ unique constraints ให้ academy-scoped, เพิ่ม import batch tables  
> **Deliverable:** Migration files (additive only, no data loss)  
> **Duration:** 1 sprint

### Migration 1.1: Fix student_id uniqueness

```php
// Drop global unique, add academy-scoped unique
Schema::table('students', function (Blueprint $table) {
    $table->dropUnique(['student_id']); // drop global unique
    $table->unique(['academy_id', 'student_id'], 'students_academy_student_id_unique');
});
```

**ความเสี่ยง:** ถ้ามี `student_id` ซ้ำข้าม academy อยู่แล้ว migration จะผ่าน (เพราะ scope แคบลง) แต่ถ้ามีซ้ำ*ใน*academy เดียวกันจะ fail → ต้องตรวจ data ก่อนรัน

### Migration 1.2: Fix citizen_id uniqueness

```php
Schema::table('students', function (Blueprint $table) {
    $table->dropUnique(['citizen_id']); // drop global unique
    $table->unique(['academy_id', 'citizen_id'], 'students_academy_citizen_id_unique');
});
```

**หมายเหตุ:** `citizen_id` nullable ซึ่ง MySQL unique index อนุญาตหลาย NULL ได้ — ไม่มีปัญหา

### Migration 1.3: Add enrollment lookup index

```php
// Compound index for fast lookup: "นักเรียนคนนี้มี active enrollment ในปีนี้หรือยัง?"
Schema::table('classroom_students', function (Blueprint $table) {
    $table->unique(
        ['academy_id', 'student_id', 'academic_year_id', 'status'],
        'cs_academy_student_year_status_unique'
    );
    // Note: This prevents duplicate active enrollments per student per year
    // But allows same student to have 'transferred' + 'active' in same year (which is correct)
});
```

**⚠️ ปัญหา:** unique constraint แบบนี้จะ block กรณี student มี `transferred` 2 ครั้งในปีเดียวกัน (ย้ายห้อง 2 รอบ) → **ใช้ application-level check แทน** ไม่ใส่ unique constraint ตรงนี้

**แก้ไข:** ใช้ composite index (ไม่ unique) สำหรับ performance + application-level validation:
```php
Schema::table('classroom_students', function (Blueprint $table) {
    // Fast lookup: "does this student have an active enrollment this year?"
    $table->index(
        ['student_id', 'academic_year_id', 'status'],
        'cs_student_year_status_idx'
    );
});
```

### Migration 1.4: Import batch tables

```php
Schema::create('student_import_batches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
    $table->foreignId('academic_year_id')->constrained();
    $table->string('filename');
    $table->string('original_filename');
    $table->enum('status', [
        'uploaded',    // ไฟล์ถูก upload แล้ว
        'validating',  // กำลัง parse + validate
        'validated',   // validate เสร็จ — รอ confirm
        'processing',  // กำลัง import (after confirm)
        'completed',   // import สำเร็จทั้งหมด
        'partial',     // import สำเร็จบางส่วน
        'failed',      // import ล้มเหลว
        'cancelled',   // ยกเลิกโดยผู้ใช้
    ]);
    $table->unsignedInteger('total_rows')->default(0);
    $table->unsignedInteger('valid_rows')->default(0);
    $table->unsignedInteger('invalid_rows')->default(0);
    $table->unsignedInteger('imported_rows')->default(0);
    $table->unsignedInteger('skipped_rows')->default(0);
    $table->json('column_mapping')->nullable();
    $table->json('default_values')->nullable(); // e.g. { academic_year_id: 5, classroom_id: 12 }
    $table->string('idempotency_key', 64)->unique();
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('confirmed_by')->nullable()->constrained('users');
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['academy_id', 'status']);
});

Schema::create('student_import_rows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('batch_id')->constrained('student_import_batches')->cascadeOnDelete();
    $table->unsignedInteger('row_number');
    $table->json('raw_data');
    $table->json('normalized_data')->nullable();
    $table->enum('status', [
        'pending',     // ยังไม่ validate
        'valid',       // ผ่าน validation
        'warning',     // ผ่านแต่มี warning (เช่น possible duplicate)
        'invalid',     // ไม่ผ่าน validation
        'imported',    // import สำเร็จ
        'skipped',     // ข้าม (duplicate resolution = skip)
        'failed',      // import ล้มเหลว (runtime error)
    ]);
    $table->json('errors')->nullable();    // [{ field: "citizen_id", message: "...", code: "DUPLICATE" }]
    $table->json('warnings')->nullable();  // same format
    $table->foreignId('student_id')->nullable()->constrained(); // link after import
    $table->timestamps();

    $table->index(['batch_id', 'status']);
    $table->index(['batch_id', 'row_number']);
});
```

### Migration 1.5: Add registrar permissions to AcademyRole

ไม่ต้องเป็น migration — ใช้ seeder/command update `SYSTEM_ROLES` constant + seed existing academies

### ✅ Phase 1 Acceptance Criteria
- [x] `student_id` unique per `(academy_id, student_id)` — tested
- [x] `citizen_id` unique per `(academy_id, citizen_id)` — tested
- [x] Import batch tables created
- [x] Existing data ไม่เสียหาย (run on copy first)
- [x] `StudentEnrollmentService` ยังทำงานปกติ (existing tests pass)
- [x] Application-level check: 1 active enrollment per student per academic year

---

## Phase 2 — Single Student Intake (Backend)

> **เป้าหมาย:** API endpoint เดียวที่รับข้อมูลนักเรียนใหม่ → สร้างครบทุก entity ใน transaction  
> **Deliverable:** Service + Controller + FormRequest + Tests  
> **Duration:** 1 sprint

### 2.1 StudentIntakeService

**File:** `app/Services/StudentIntakeService.php`

```php
class StudentIntakeService
{
    public function __construct(
        private StudentEnrollmentService $enrollmentService,
    ) {}

    /**
     * รับนักเรียนใหม่เข้าสู่ระบบ (Single)
     * ทำทุกอย่างใน transaction เดียว
     */
    public function intake(Academy $academy, array $data, User $operator): IntakeResult
    {
        return DB::transaction(function () use ($academy, $data, $operator) {
            // 1. Resolve academic year + classroom
            $academicYear = $this->resolveAcademicYear($academy, $data);
            $classroom = $this->resolveClassroom($academy, $academicYear, $data);

            // 2. Duplicate check (throws if blocking duplicate found)
            $this->assertNoDuplicate($academy, $data);

            // 3. Create or link User (based on account policy)
            $user = $this->resolveUser($data['account'] ?? []);

            // 4. Create Student record
            $student = $this->createStudent($academy, $user, $data);

            // 5. Create AcademyMember (role = student)
            $member = $this->createMembership($academy, $student, $user, $operator);

            // 6. Enroll in classroom (delegates to existing service)
            $enrollment = $this->enrollmentService->enrollStudent(
                $student,
                $classroom,
                $data['admission']['student_number'] ?? null,
                null, // batchId
                $operator->id,
            );

            // 7. Store guardian if provided
            $guardians = $this->storeGuardians($student, $academy, $data['guardians'] ?? []);

            // 8. Return unified result
            return new IntakeResult(
                student: $student,
                member: $member,
                enrollment: $enrollment,
                academicInfo: $student->currentAcademicInfo,
                guardians: $guardians,
                user: $user,
                warnings: $this->warnings,
            );
        });
    }

    /**
     * Duplicate check — soft version for preview
     */
    public function checkDuplicate(Academy $academy, array $data): DuplicateCheckResult
    {
        // Returns matches with confidence levels
    }
}
```

**Design decisions:**
- ใช้ `StudentEnrollmentService::enrollStudent()` ที่มีอยู่แล้ว — ไม่ duplicate logic
- `enrollStudent()` จะสร้าง `ClassroomStudent` + update `StudentAcademicInfo` ให้อัตโนมัติ
- Guardian สร้างตรงนี้เลย (model พร้อมแล้ว) ไม่ต้องรอ Phase 5
- `IntakeResult` เป็น DTO ที่ return ข้อมูลครบทุก entity

### 2.2 IntakeResult DTO

```php
class IntakeResult
{
    public function __construct(
        public readonly Student $student,
        public readonly AcademyMember $member,
        public readonly ClassroomStudent $enrollment,
        public readonly ?StudentAcademicInfo $academicInfo,
        public readonly array $guardians,
        public readonly ?User $user,
        public readonly array $warnings = [],
    ) {}

    public function hasAccount(): bool
    {
        return $this->user !== null;
    }
}
```

### 2.3 DuplicateCheckResult DTO

```php
class DuplicateCheckResult
{
    public function __construct(
        public readonly bool $hasBlockingDuplicate,
        public readonly array $matches, // [{student, match_type, confidence}]
    ) {}
}
```

### 2.4 Request Validation — StoreStudentIntakeRequest

```php
class StoreStudentIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->getAcademyMember();
        return $member && $member->hasAnyPermission(['students.create', 'students.manage']);
    }

    public function rules(): array
    {
        $academyId = $this->route('academy')->id;

        return [
            // Identity
            'identity.student_code'    => ['required', 'string', 'max:20',
                Rule::unique('students', 'student_id')
                    ->where('academy_id', $academyId)],
            'identity.citizen_id'      => ['nullable', 'string', 'size:13',
                Rule::unique('students', 'citizen_id')
                    ->where('academy_id', $academyId)],

            // Personal
            'personal.title_prefix_th' => ['nullable', 'string', 'max:20'],
            'personal.first_name_th'   => ['required', 'string', 'max:100'],
            'personal.last_name_th'    => ['required', 'string', 'max:100'],
            'personal.middle_name_th'  => ['nullable', 'string', 'max:100'],
            'personal.first_name_en'   => ['nullable', 'string', 'max:100'],
            'personal.last_name_en'    => ['nullable', 'string', 'max:100'],
            'personal.nickname'        => ['nullable', 'string', 'max:50'],
            'personal.date_of_birth'   => ['nullable', 'date', 'before:today'],
            'personal.gender'          => ['nullable', 'integer', 'in:0,1'],
            'personal.nationality'     => ['nullable', 'string', 'max:50'],
            'personal.religion'        => ['nullable', 'string', 'max:50'],

            // Admission
            'admission.academic_year_id' => ['required', 'exists:academic_years,id'],
            'admission.classroom_id'     => ['required', 'exists:classrooms,id'],
            'admission.student_number'   => ['nullable', 'integer', 'min:1'],
            'admission.enrollment_date'  => ['nullable', 'date'],

            // Previous school (optional)
            'previous_school.name'     => ['nullable', 'string', 'max:200'],
            'previous_school.province' => ['nullable', 'string', 'max:100'],
            'previous_school.grade'    => ['nullable', 'string', 'max:20'],

            // Guardians (optional array)
            'guardians'                    => ['nullable', 'array', 'max:5'],
            'guardians.*.guardian_type'    => ['required', 'in:father,mother,guardian,relative,other'],
            'guardians.*.title_prefix'     => ['nullable', 'string', 'max:20'],
            'guardians.*.first_name'       => ['required', 'string', 'max:100'],
            'guardians.*.last_name'        => ['required', 'string', 'max:100'],
            'guardians.*.citizen_id'       => ['nullable', 'string', 'size:13'],
            'guardians.*.occupation'       => ['nullable', 'string', 'max:100'],
            'guardians.*.phone'            => ['nullable', 'string', 'max:20'],
            'guardians.*.is_primary_contact' => ['nullable', 'boolean'],
            'guardians.*.is_emergency_contact' => ['nullable', 'boolean'],

            // Account (optional)
            'account.mode'  => ['nullable', 'in:none,pending_activation,link_existing,create_now'],
            'account.email' => ['nullable', 'email', 'required_if:account.mode,link_existing,create_now'],
            'account.user_id' => ['nullable', 'exists:users,id', 'required_if:account.mode,link_existing'],
        ];
    }
}
```

### 2.5 API Routes

```php
// routes/learn/academy.php (under academy middleware group)

Route::prefix('academies/{academy}')->middleware(['auth:api'])->group(function () {
    // Student Intake
    Route::post('/student-intakes', [StudentIntakeController::class, 'store']);
    Route::get('/student-intakes/duplicate-check', [StudentIntakeController::class, 'duplicateCheck']);
    Route::get('/student-intakes/next-student-code', [StudentIntakeController::class, 'nextStudentCode']);
});
```

### 2.6 Controller (thin)

```php
class StudentIntakeController extends Controller
{
    public function store(StoreStudentIntakeRequest $request, Academy $academy): JsonResponse
    {
        $result = app(StudentIntakeService::class)->intake(
            $academy,
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'data' => new StudentIntakeResource($result),
            'warnings' => $result->warnings,
        ], 201);
    }

    public function duplicateCheck(Request $request, Academy $academy): JsonResponse
    {
        $result = app(StudentIntakeService::class)->checkDuplicate(
            $academy,
            $request->only(['student_code', 'citizen_id', 'first_name_th', 'last_name_th', 'date_of_birth']),
        );

        return response()->json(['data' => $result]);
    }

    public function nextStudentCode(Request $request, Academy $academy): JsonResponse
    {
        // Auto-generate suggestion based on academy pattern
    }
}
```

### 2.7 Tests (Feature)

| Test Case | Expected |
|-----------|----------|
| สร้างนักเรียนครบทุก field | 201, student + member + enrollment created |
| สร้างนักเรียนโดยไม่มีบัญชี (account.mode=none) | 201, user_id=null |
| สร้างพร้อม guardian 2 คน | 201, guardians created |
| student_code ซ้ำใน academy | 422, validation error |
| citizen_id ซ้ำใน academy | 422, validation error |
| classroom ไม่ตรง academy | 422, validation error |
| academic_year ไม่ตรง academy | 422, validation error |
| active enrollment ซ้ำในปีเดียวกัน | 409, conflict error |
| ผู้ไม่มีสิทธิ์ (role=student) | 403, forbidden |
| tenant isolation: academy A เข้าถึง academy B | 403/404 |
| transaction rollback เมื่อ enrollment fail | student ไม่ถูกสร้าง |
| link_existing account mode | 201, user linked |
| duplicate check endpoint | 200, returns matches |

---

## Phase 3 — Registrar UI (Single Intake)

> **เป้าหมาย:** หน้า UI สำหรับนายทะเบียนรับนักเรียนใหม่ทีละคน  
> **Deliverable:** Page + Wizard Component + Service + Store  
> **Duration:** 1 sprint

### 3.1 File Structure

```
ui/
├── pages/academies/[name]/admin/students/
│   ├── index.vue              # หน้าทะเบียนหลัก (dashboard + list)
│   └── intake.vue             # Single intake wizard
├── components/academy/student-intake/
│   ├── IntakeWizard.vue       # Main wizard container (5 steps)
│   ├── StepIdentity.vue       # Step 1: รหัส + ตรวจซ้ำ
│   ├── StepPersonal.vue       # Step 2: ข้อมูลส่วนตัว
│   ├── StepAdmission.vue      # Step 3: ปีการศึกษา + ห้องเรียน
│   ├── StepGuardian.vue       # Step 4: ผู้ปกครอง
│   ├── StepReview.vue         # Step 5: ตรวจทาน + ยืนยัน
│   └── DuplicateWarning.vue   # Alert component เมื่อพบข้อมูลซ้ำ
├── composables/useStudentIntake.ts
├── services/studentIntakeService.ts
└── types/studentIntake.ts
```

### 3.2 Wizard Steps

**Step 1 — Identity Check:**
- กรอกรหัสนักเรียน (auto-suggest + manual)
- กรอกเลขประชาชน (optional)
- กดปุ่ม "ตรวจสอบ" → เรียก `/duplicate-check`
- ถ้าพบซ้ำ: แสดง `DuplicateWarning` พร้อม options
- ถ้าไม่ซ้ำ: ไปขั้นถัดไป

**Step 2 — Personal Info:**
- คำนำหน้า, ชื่อ, นามสกุล (TH + EN)
- วันเกิด, เพศ, สัญชาติ, ศาสนา
- ชื่อเล่น
- Inline validation ทุก field

**Step 3 — Admission:**
- เลือกปีการศึกษา (default: ปีปัจจุบัน)
- เลือกระดับชั้น/ห้อง (cascade: grade → section)
- เลขที่ในห้อง (auto-suggest next available)
- วันที่เข้าเรียน
- ข้อมูลโรงเรียนเดิม (optional)

**Step 4 — Guardian:**
- เพิ่มผู้ปกครอง 1-5 คน (dynamic form)
- ระบุ: ความสัมพันธ์, ชื่อ-สกุล, อาชีพ, เบอร์โทร
- เลือกผู้ติดต่อหลัก + ผู้ติดต่อฉุกเฉิน
- ข้ามได้ (optional step)

**Step 5 — Review:**
- แสดงสรุปข้อมูลทั้งหมด
- Account policy: "ยังไม่สร้างบัญชี" / "สร้างทีหลัง"
- ปุ่ม "บันทึก" → POST to API
- หลังสำเร็จ: แสดง success + link ไปหน้า Master Profile

### 3.3 Dashboard Statistics (students/index.vue)

```
┌─────────────────────────────────────────────────┐
│  📊 สถิติทะเบียน (ปีการศึกษาปัจจุบัน)            │
├──────────┬──────────┬──────────┬────────────────┤
│ กำลังเรียน │ รับเข้าใหม่ │ ยังไม่มีห้อง │ รอเปิดบัญชี    │
│   342     │    48    │     3    │     156      │
├──────────┴──────────┴──────────┴────────────────┤
│  [+ เพิ่มนักเรียน]  [📥 นำเข้า]  [📤 ส่งออก]   │
├─────────────────────────────────────────────────┤
│  🔍 ค้นหา: [_______________] Filter: [ระดับชั้น▾] │
│                                                  │
│  # | รหัส  | ชื่อ-สกุล    | ห้อง | สถานะ | ...   │
│  1 | 00001 | สมชาย ใจดี    | ม.1/1 | active | ... │
│  ...                                            │
└─────────────────────────────────────────────────┘
```

### 3.4 Technical Notes
- ใช้ PrimeVue `Stepper` component สำหรับ wizard
- Pinia store เก็บ draft data (persist ใน sessionStorage กันหาย)
- Debounced duplicate check ที่ Step 1
- Auto-save draft ทุก step change
- Mobile responsive (PrimeVue handles)

---

## Phase 4 — Bulk Import (Backend)

> **เป้าหมาย:** รองรับ CSV upload → validate → confirm → import  
> **Deliverable:** Job + Service + Controller + batch processing  
> **Duration:** 1.5 sprints

### 4.1 Import Flow (2-step confirmation)

```
[Upload CSV] → [Parse & Validate] → [Preview Results] → [Confirm] → [Process Queue] → [Done]
     ↓              ↓                      ↓                ↓              ↓
  POST /imports  background job      GET /imports/{id}   POST /confirm   Job per row
  → batch created  → validate rows   → show valid/error  → queue jobs    → IntakeService
  → status:uploaded → status:validated                    → status:processing
```

### 4.2 CSV Template

คอลัมน์ขั้นต่ำ (required*):
```csv
student_code*,title_th,first_name_th*,last_name_th*,citizen_id,date_of_birth,gender,grade_level*,section*,student_number,enrollment_date,previous_school,guardian_name,guardian_phone,guardian_relation
```

### 4.3 API Routes

```php
Route::prefix('academies/{academy}/student-imports')->group(function () {
    Route::get('/', [StudentImportController::class, 'index']);           // list batches
    Route::post('/', [StudentImportController::class, 'upload']);         // upload + start validation
    Route::get('/template', [StudentImportController::class, 'template']); // download CSV template
    Route::get('/{batch}', [StudentImportController::class, 'show']);     // batch detail + stats
    Route::get('/{batch}/rows', [StudentImportController::class, 'rows']); // paginated row list
    Route::post('/{batch}/confirm', [StudentImportController::class, 'confirm']); // trigger import
    Route::post('/{batch}/retry', [StudentImportController::class, 'retry']);     // retry failed rows
    Route::get('/{batch}/errors', [StudentImportController::class, 'errors']);    // download error CSV
    Route::delete('/{batch}', [StudentImportController::class, 'cancel']);        // cancel batch
});
```

### 4.4 ValidateImportBatchJob

```php
class ValidateImportBatchJob implements ShouldQueue
{
    public function handle(): void
    {
        // 1. Parse CSV rows
        // 2. Normalize each row (trim, date format, gender mapping)
        // 3. Validate each row against rules
        // 4. Cross-reference: duplicates within file
        // 5. Cross-reference: duplicates against DB
        // 6. Update row statuses + batch counters
        // 7. Set batch status = 'validated'
    }
}
```

### 4.5 ProcessImportBatchJob

```php
class ProcessImportBatchJob implements ShouldQueue
{
    public function handle(StudentIntakeService $intakeService): void
    {
        $batch = StudentImportBatch::findOrFail($this->batchId);

        // Idempotency check
        if ($batch->status !== 'validated' && $batch->status !== 'partial') {
            return;
        }

        $batch->update(['status' => 'processing']);

        $rows = $batch->rows()->whereIn('status', ['valid', 'warning'])->get();

        foreach ($rows as $row) {
            try {
                $result = $intakeService->intake(
                    $batch->academy,
                    $this->rowToIntakeData($row, $batch),
                    $batch->confirmedBy,
                );

                $row->update([
                    'status' => 'imported',
                    'student_id' => $result->student->id,
                ]);

                $batch->increment('imported_rows');
            } catch (\Throwable $e) {
                $row->update([
                    'status' => 'failed',
                    'errors' => [['field' => '_system', 'message' => $e->getMessage()]],
                ]);

                // Don't stop — continue with next row
            }
        }

        // Final status
        $batch->refresh();
        $finalStatus = $batch->imported_rows === $batch->valid_rows ? 'completed' : 'partial';
        $batch->update(['status' => $finalStatus, 'completed_at' => now()]);
    }
}
```

**Key design:** ทุกแถวเรียก `StudentIntakeService::intake()` ตัวเดียวกับ single — ไม่มี logic แยก

### 4.6 Validation Rules (per row)

| Rule | Type | Message |
|------|------|---------|
| required fields missing | error | "ต้องกรอก {field}" |
| citizen_id ไม่ 13 หลัก | error | "เลขประชาชนต้อง 13 หลัก" |
| citizen_id checksum ผิด | warning | "เลขประชาชนอาจไม่ถูกต้อง (checksum)" |
| student_code ซ้ำในไฟล์ | error | "รหัสนักเรียนซ้ำกับแถว {n}" |
| student_code ซ้ำใน DB | error | "รหัสนักเรียนนี้มีในระบบแล้ว" |
| citizen_id ซ้ำใน DB | warning | "พบข้อมูลเดิมในระบบ: {name}" |
| classroom ไม่พบ | error | "ไม่พบห้องเรียน {grade}/{section}" |
| student_number ซ้ำในห้อง | warning | "เลขที่ {n} มีผู้ใช้แล้ว" |
| date format ผิด | error | "รูปแบบวันที่ไม่ถูกต้อง" |
| gender ไม่รู้จัก | warning | "เพศที่ระบุไม่ตรงรูปแบบ — จะข้าม" |

### 4.7 Idempotency

- `idempotency_key` = SHA256 ของ (academy_id + filename + file_hash + uploaded_at)
- ถ้า confirm ซ้ำ → return current batch status (ไม่ process ซ้ำ)
- ถ้า retry → process เฉพาะ rows ที่ `status = 'failed'`

---

## Phase 5 — Bulk Import UI

> **เป้าหมาย:** UI สำหรับ upload, preview, confirm, monitor import  
> **Duration:** 1 sprint

### 5.1 File Structure

```
ui/components/academy/student-import/
├── ImportWizard.vue           # Main container
├── StepUpload.vue             # Upload + select year/classroom defaults
├── StepPreview.vue            # Show validation results table
├── StepConfirm.vue            # Confirm + progress bar
├── ImportRowTable.vue         # Paginated table with status badges
├── ImportErrorBadge.vue       # Error/warning display per row
└── ImportHistoryList.vue      # Past imports list
```

### 5.2 UX Flow

1. **Download template** — กดปุ่ม ได้ CSV template พร้อม header
2. **Select defaults** — เลือกปีการศึกษา, ระดับชั้น/ห้อง (ถ้า import ทั้งห้อง)
3. **Upload file** — drag & drop หรือ browse
4. **Wait for validation** — spinner/progress (poll หรือ websocket)
5. **Preview** — ตารางแสดง valid ✅ / warning ⚠️ / error ❌
   - Filter by status
   - Click row → show details + error messages
   - "แก้ไข" inline สำหรับ warning rows (optional — v2)
6. **Confirm** — กดยืนยัน → processing starts
7. **Progress** — realtime counter (imported/total)
8. **Result** — summary + download error report

### 5.3 Polling Strategy

ใช้ polling 3 วินาทีระหว่าง processing (Laravel Reverb available แต่เริ่มง่ายก่อน):
```typescript
const pollInterval = ref<ReturnType<typeof setInterval>>()

function startPolling(batchId: number) {
    pollInterval.value = setInterval(async () => {
        const batch = await studentImportService.getBatch(batchId)
        if (['completed', 'partial', 'failed'].includes(batch.status)) {
            clearInterval(pollInterval.value)
        }
    }, 3000)
}
```

---

## Phase 6 — Account Activation + Operational Reports

> **เป้าหมาย:** เปิดบัญชีให้นักเรียน + รายงานสำหรับนายทะเบียน  
> **Duration:** 1 sprint

### 6.1 Account Activation Modes

| Mode | Description | When |
|------|-------------|------|
| `none` | ไม่สร้างบัญชี | นักเรียนเล็ก, ยังไม่ต้องเข้าระบบ |
| `pending_activation` | สร้าง invitation token ไว้ | จะเปิดบัญชีทีหลัง |
| `create_now` | สร้าง User ทันที (set password required on first login) | ต้องการให้เข้าใช้งานเลย |
| `link_existing` | เชื่อม User ที่มีอยู่ | นักเรียนมีบัญชี nuxnan อยู่แล้ว |

### 6.2 Invitation System

```php
// student_account_invitations table
Schema::create('student_account_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_id')->constrained();
    $table->foreignId('academy_id')->constrained();
    $table->string('token_hash', 64)->unique();
    $table->string('email')->nullable();
    $table->enum('status', ['pending', 'sent', 'activated', 'expired', 'revoked']);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('activated_at')->nullable();
    $table->timestamp('expires_at');
    $table->timestamps();
});
```

### 6.3 Operational Reports (Dashboard queries)

| Report | Query |
|--------|-------|
| กำลังเรียน | `Student::byAcademy($id)->active()->count()` |
| รับเข้าใหม่ (เทอมนี้) | Students created this semester |
| ยังไม่มีห้อง | `Student::active()->doesntHave('currentEnrollment')` |
| รอเปิดบัญชี | `Student::whereNull('user_id')->active()` |
| ข้อมูลไม่สมบูรณ์ | Students missing required fields |
| จบ/พ้นสภาพ | `Student::whereIn('status', ['graduated', 'inactive'])` |
| Import history | `StudentImportBatch::byAcademy($id)->latest()` |

### 6.4 Export

- CSV export ของรายชื่อนักเรียน (filtered)
- ใช้ `maatwebsite/excel` ที่มีอยู่แล้วใน project

---

## Sprint Delivery Schedule

| Sprint | Phase | Deliverables | Dependencies |
|--------|-------|-------------|--------------|
| **S1** (1 week) | 0 + 1 | Domain rules locked, migrations, registrar role seed | None |
| **S2** (1 week) | 2 | StudentIntakeService, API, FormRequest, 13+ feature tests | S1 |
| **S3** (1 week) | 3 | Registrar dashboard, Intake wizard (5 steps), service/store/types | S2 |
| **S4** (1.5 weeks) | 4 + 5 | Import batch jobs, CSV parser, Import UI wizard | S2 |
| **S5** (1 week) | 6 | Account activation, reports, export | S2 |

**Total estimated: ~5.5 weeks** (working solo, accounting for testing)

---

## Definition of Done (Final)

ระบบ Intake ถือว่าใช้งานจริงได้เมื่อ:

- [ ] นักเรียนใหม่ถูกสร้างครบทุก entity (Student + AcademyMember + ClassroomStudent + AcademicInfo) โดยไม่เกิดข้อมูลครึ่งชุด (transaction)
- [ ] รองรับนักเรียนที่ยังไม่มีบัญชี (`user_id = null`)
- [ ] Single และ Bulk ใช้ `StudentIntakeService::intake()` ตัวเดียวกัน
- [ ] ตรวจ duplicate (student_code + citizen_id) ก่อนเขียนข้อมูล
- [ ] ทุก query ถูกจำกัดตาม `academy_id` (tenant isolation)
- [ ] มีสิทธิ์ "นายทะเบียน" (`registrar` role) แยกจาก admin
- [ ] Bulk มี Preview → Confirm → Error report → Retry
- [ ] สถานะ consistent ระหว่าง `students.status`, `classroom_students.status`, `student_academic_info.study_status`
- [ ] มี audit trail ทุก action (via Auditable trait)
- [ ] ผ่าน backend feature tests (13+ cases)
- [ ] Frontend build pass (TypeScript + Nuxt)
- [ ] Browser smoke test ผ่าน (wizard ทำงานได้จริง)

---

## Risk & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| `student_id` unique constraint change breaks existing data | High | Run data check query before migration; backup DB |
| `StudentEnrollmentService::enrollStudent()` ใช้ updateOrCreate | Medium | เข้าใจ behavior แล้ว — same classroom re-enroll updates existing row (by design for repair) |
| Import ไฟล์ใหญ่ (500+ rows) timeout | Medium | Queue-based processing, not synchronous |
| Permission backward-compat | Low | `students.manage` ยังทำงาน — add granular as additive |
| `AcademyMember.user_id` อาจ NOT NULL | High | ต้องตรวจ migration → ถ้า required ต้อง ALTER ก่อน |

---

## Technical Debt to Address During Implementation

| # | Issue | Where | Fix |
|---|-------|-------|-----|
| **T1** | `Student` model has both `$guarded = []` and `$fillable` | `app/Models/Student.php:16,70` | Remove `$guarded` — rely on `$fillable` only |
| **T2** | Policies use legacy `role` string, not `AcademyRole` permissions | `StudentMasterProfilePolicy`, `EnrollmentPolicy` | New intake controller should use `AcademyRole` system (don't perpetuate legacy) |
| **T3** | `classroom_students` has `UNIQUE(classroom_id, student_id)` | Base migration | Re-enrolling same classroom overwrites history — acceptable for intake (student is new) but document this |
| **T4** | `students.status` is still ENUM at DB level | Base migration | Phase 1 migration should ALTER to VARCHAR(20) for flexibility |
| **T5** | `student_guardians` มี 2 migrations สร้างตารางเดียวกัน | `2025_10_26_*` + `2026_02_01_*` | อันที่ 2 มี `hasTable` guard อยู่แล้ว — no-op แต่ควร cleanup |
| **T6** | `student_guardians` migration ไม่มี `academy_id`, `student_code` | Base migration vs Model fillable | Schema drift — ต้องตรวจว่ามี ALTER เพิ่มทีหลังหรือไม่ |
| **T7** | `maatwebsite/excel` installed แต่ไม่มี code ใช้เลย | `composer.json` | ใช้ได้สำหรับ Phase 4 (Bulk Import) — หรือเริ่มจาก native CSV parser ก่อน |
| **T8** | `student_academic_info.academic_year` เป็น string, ไม่ FK ไป `academic_years` | Model + migration | Denormalized by design — `StudentEnrollmentService` ใช้ `$academicYear->name` เป็น string |

---

## Authorization Strategy for Intake

ปัจจุบันมี 2 ระบบ authorization:
1. **Legacy:** `academy_members.role` string column (checked in policies)
2. **Structured:** `AcademyRole.permissions` JSON array (checked via `hasPermission()`)

**Decision:** Intake controller จะใช้ **structured system** (AcademyRole) เท่านั้น ไม่เพิ่ม legacy checks
- FormRequest `authorize()` → `$member->hasAnyPermission(['students.create', 'students.manage'])`
- ไม่แก้ policies เดิม (out of scope) แต่ intake flow ใหม่ไม่ใช้ legacy

**Implication:** ต้องตรวจว่า academy ที่ทดสอบมี AcademyRole seed แล้ว — ถ้ายังไม่มีให้รัน `AcademyRoleSeeder`

---

## Locked Rules (ยืนยันแล้ว — ห้ามเปลี่ยนหลัง Phase 0)

*(จะถูก fill เมื่อ Phase 0 sign-off)*

- [x] AcademyMember.user_id nullable: **YES** (confirmed from migration `2026_01_16_133845`)
- [x] AcademyMember.student_id nullable: **YES** (same migration)
- [x] Student code format: **`{school_code}{entry_year_2}{sequence_4}`** — academy-scoped, configurable per academy, ไม่ encode ชั้น/ห้อง (เปลี่ยนทุกปี), auto-generate ถ้าไม่ระบุ, รองรับรหัสจากระบบเดิม
- [x] Account policy default: **`pending_activation`** — สร้าง Student record โดยยังไม่สร้าง credential, บันทึกสถานะรอเปิดบัญชี, ส่งคำเชิญภายหลังได้, มี `none` เป็นตัวเลือกสำหรับกรณียกเว้น
- [x] Duplicate citizen_id: **Block + Resolution Workflow** — block โดยค่าเริ่มต้น, เปิด workflow ให้นายทะเบียน: (1) เชื่อมตัวตนเดิม (2) แก้เลขประชาชน (3) ส่งตรวจสอบ (4) override พร้อมเหตุผล+audit, รองรับนักเรียนไม่มีเลขประชาชน (ใช้ passport/alternative ID)
- [x] Permission model: **Granular under students.*** — students.create, students.import, students.lifecycle, students.activate_account, students.export + registrar role ใหม่
- [x] Phase 0 sign-off: **2026-07-05** ✅
