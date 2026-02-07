<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academy;
use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\FeeStructure;
use App\Models\FeeItem;
use App\Models\ExpenseCategory;
use App\Models\Expense;
use App\Models\Budget;
use App\Models\StaffProfile;
use App\Models\StaffAttendance;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\MeetingSlot;
use App\Models\ReportDefinition;
use App\Models\DashboardWidget;
use App\Models\KpiDefinition;
use Carbon\Carbon;

/**
 * School Management System Seeder
 * 
 * รัน: php artisan db:seed --class=SchoolManagementSeeder
 */
class SchoolManagementSeeder extends Seeder
{
    protected $academy;
    protected $users = [];
    protected $academicYear;
    protected $semester;

    public function run(): void
    {
        $this->command->info('🏫 Starting School Management System Seeder...');

        // Get or create academy
        $this->academy = Academy::first();
        if (!$this->academy) {
            $this->command->error('❌ No academy found! Please create an academy first.');
            return;
        }

        // Get users
        $this->users = User::take(10)->get();
        if ($this->users->isEmpty()) {
            $this->command->error('❌ No users found! Please create users first.');
            return;
        }

        $this->command->info("📍 Using Academy: {$this->academy->name} (ID: {$this->academy->id})");

        // Create or get academic year
        $this->academicYear = AcademicYear::firstOrCreate(
            ['academy_id' => $this->academy->id, 'name' => '2568'],
            [
                'academy_id' => $this->academy->id,
                'name' => '2568',
                'start_date' => '2025-05-16',
                'end_date' => '2026-03-31',
                'is_current' => true,
            ]
        );
        $this->command->info("📅 Using Academic Year: {$this->academicYear->name}");

        // Create or get semester
        $this->semester = Semester::firstOrCreate(
            ['academic_year_id' => $this->academicYear->id, 'semester_number' => 1],
            [
                'academic_year_id' => $this->academicYear->id,
                'semester_number' => 1,
                'name' => 'ภาคเรียนที่ 1',
                'start_date' => '2025-05-16',
                'end_date' => '2025-10-15',
                'is_current' => true,
            ]
        );
        $this->command->info("📆 Using Semester: {$this->semester->name}");

        // Seed each module
        $this->seedAcademicSystem();
        $this->seedFinanceSystem();
        $this->seedStaffSystem();
        $this->seedCommunicationSystem();
        $this->seedReportsSystem();

        $this->command->info('');
        $this->command->info('🎉 School Management System seeding completed!');
    }

    /**
     * Phase 1: Academic System
     */
    protected function seedAcademicSystem(): void
    {
        $this->command->info('');
        $this->command->info('📚 Seeding Academic System...');

        // Classrooms - columns: id,academy_id,academic_year_id,grade_level,section,name,homeroom_teacher_id,room_location,capacity,is_active
        $classrooms = [
            ['name' => 'ห้อง 101', 'grade_level' => 'ป.1', 'section' => 'A', 'room_location' => 'อาคาร A ชั้น 1', 'capacity' => 40],
            ['name' => 'ห้อง 102', 'grade_level' => 'ป.1', 'section' => 'B', 'room_location' => 'อาคาร A ชั้น 1', 'capacity' => 40],
            ['name' => 'ห้อง 201', 'grade_level' => 'ป.2', 'section' => 'A', 'room_location' => 'อาคาร A ชั้น 2', 'capacity' => 35],
            ['name' => 'ห้อง ม.1/1', 'grade_level' => 'ม.1', 'section' => '1', 'room_location' => 'อาคาร B ชั้น 1', 'capacity' => 30],
            ['name' => 'ห้อง ม.4/1', 'grade_level' => 'ม.4', 'section' => '1', 'room_location' => 'อาคาร B ชั้น 2', 'capacity' => 40],
            ['name' => 'หอประชุม', 'grade_level' => 'ทั่วไป', 'section' => '-', 'room_location' => 'อาคาร C ชั้น 1', 'capacity' => 200],
        ];

        foreach ($classrooms as $data) {
            Classroom::updateOrCreate(
                ['academy_id' => $this->academy->id, 'name' => $data['name']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'academic_year_id' => $this->academicYear->id,
                    'homeroom_teacher_id' => $this->users->random()->id,
                    'is_active' => true
                ])
            );
        }
        $this->command->info('  ✅ Classrooms: ' . count($classrooms) . ' records');

        // Subjects - columns: id,academy_id,subject_code,name_th,name_en,description,credits,hours_per_week,subject_type,subject_group,grade_levels,is_active,created_by
        $subjects = [
            ['subject_code' => 'MATH101', 'name_th' => 'คณิตศาสตร์พื้นฐาน', 'name_en' => 'Basic Mathematics', 'credits' => 2, 'hours_per_week' => 4, 'subject_type' => 'required', 'subject_group' => 'mathematics'],
            ['subject_code' => 'THAI101', 'name_th' => 'ภาษาไทย', 'name_en' => 'Thai Language', 'credits' => 2, 'hours_per_week' => 4, 'subject_type' => 'required', 'subject_group' => 'language'],
            ['subject_code' => 'SCI101', 'name_th' => 'วิทยาศาสตร์', 'name_en' => 'Science', 'credits' => 2, 'hours_per_week' => 4, 'subject_type' => 'required', 'subject_group' => 'science'],
            ['subject_code' => 'ENG101', 'name_th' => 'ภาษาอังกฤษ', 'name_en' => 'English', 'credits' => 2, 'hours_per_week' => 3, 'subject_type' => 'required', 'subject_group' => 'language'],
            ['subject_code' => 'SOC101', 'name_th' => 'สังคมศึกษา', 'name_en' => 'Social Studies', 'credits' => 2, 'hours_per_week' => 3, 'subject_type' => 'required', 'subject_group' => 'social'],
        ];

        foreach ($subjects as $data) {
            Subject::updateOrCreate(
                ['academy_id' => $this->academy->id, 'subject_code' => $data['subject_code']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'is_active' => true,
                    'created_by' => $this->users->first()->id,
                ])
            );
        }
        $this->command->info('  ✅ Subjects: ' . count($subjects) . ' records');

        // Class Schedules - columns: academy_id,academic_year_id,semester_id,classroom_id,subject_id,teacher_id,day_of_week,start_time,end_time,period_number,room,status,notes,created_by
        $classroom = Classroom::where('academy_id', $this->academy->id)->first();
        $allSubjects = Subject::where('academy_id', $this->academy->id)->get();
        
        if ($classroom && $allSubjects->isNotEmpty()) {
            $schedules = [
                ['day_of_week' => 1, 'start_time' => '08:30', 'end_time' => '09:30', 'period_number' => 1, 'room' => 'ห้อง 101', 'subject_index' => 0],
                ['day_of_week' => 1, 'start_time' => '09:30', 'end_time' => '10:30', 'period_number' => 2, 'room' => 'ห้อง 101', 'subject_index' => 1],
                ['day_of_week' => 1, 'start_time' => '10:45', 'end_time' => '11:45', 'period_number' => 3, 'room' => 'ห้อง 102', 'subject_index' => 2],
                ['day_of_week' => 2, 'start_time' => '08:30', 'end_time' => '09:30', 'period_number' => 1, 'room' => 'ห้อง 101', 'subject_index' => 3],
                ['day_of_week' => 2, 'start_time' => '09:30', 'end_time' => '10:30', 'period_number' => 2, 'room' => 'ห้อง 102', 'subject_index' => 4],
            ];

            foreach ($schedules as $data) {
                $subjectIndex = $data['subject_index'] ?? 0;
                unset($data['subject_index']);
                
                ClassSchedule::updateOrCreate(
                    [
                        'academy_id' => $this->academy->id,
                        'day_of_week' => $data['day_of_week'],
                        'start_time' => $data['start_time'],
                        'classroom_id' => $classroom->id,
                    ],
                    array_merge($data, [
                        'academy_id' => $this->academy->id,
                        'academic_year_id' => $this->academicYear->id,
                        'semester_id' => $this->semester->id,
                        'subject_id' => $allSubjects[$subjectIndex % count($allSubjects)]->id,
                        'classroom_id' => $classroom->id,
                        'teacher_id' => $this->users->random()->id,
                        'status' => 'active',
                        'created_by' => $this->users->first()->id,
                    ])
                );
            }
            $this->command->info('  ✅ Class Schedules: ' . count($schedules) . ' records');
        }
    }

    /**
     * Phase 2: Finance System
     */
    protected function seedFinanceSystem(): void
    {
        $this->command->info('');
        $this->command->info('💰 Seeding Finance System...');

        // Fee Structures - columns: id,academy_id,academic_year_id,name,description,grade_levels,total_amount,is_active,created_by
        $feeStructures = [
            ['name' => 'ค่าเทอม ป.1', 'description' => 'ค่าธรรมเนียมการศึกษาระดับ ป.1', 'grade_levels' => json_encode(['ป.1']), 'total_amount' => 15000],
            ['name' => 'ค่าเทอม ป.2', 'description' => 'ค่าธรรมเนียมการศึกษาระดับ ป.2', 'grade_levels' => json_encode(['ป.2']), 'total_amount' => 15000],
            ['name' => 'ค่าเทอม ม.1', 'description' => 'ค่าธรรมเนียมการศึกษาระดับ ม.1', 'grade_levels' => json_encode(['ม.1']), 'total_amount' => 20000],
            ['name' => 'ค่าเทอม ม.4', 'description' => 'ค่าธรรมเนียมการศึกษาระดับ ม.4', 'grade_levels' => json_encode(['ม.4']), 'total_amount' => 25000],
        ];

        foreach ($feeStructures as $data) {
            $structure = FeeStructure::updateOrCreate(
                ['academy_id' => $this->academy->id, 'name' => $data['name']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'academic_year_id' => $this->academicYear->id,
                    'is_active' => true,
                    'created_by' => $this->users->first()->id,
                ])
            );

            // Fee Items - columns: id,fee_structure_id,name,code,description,amount,fee_type,is_mandatory,is_refundable,display_order
            $items = [
                ['name' => 'ค่าเล่าเรียน', 'code' => 'TUITION', 'amount' => $data['total_amount'] * 0.6, 'fee_type' => 'tuition'],
                ['name' => 'ค่าอุปกรณ์การเรียน', 'code' => 'BOOK', 'amount' => $data['total_amount'] * 0.2, 'fee_type' => 'book'],
                ['name' => 'ค่ากิจกรรม', 'code' => 'ACTIVITY', 'amount' => $data['total_amount'] * 0.2, 'fee_type' => 'activity'],
            ];

            foreach ($items as $index => $itemData) {
                FeeItem::updateOrCreate(
                    ['fee_structure_id' => $structure->id, 'name' => $itemData['name']],
                    array_merge($itemData, [
                        'fee_structure_id' => $structure->id, 
                        'is_mandatory' => true,
                        'is_refundable' => false,
                        'display_order' => $index + 1,
                    ])
                );
            }
        }
        $this->command->info('  ✅ Fee Structures: ' . count($feeStructures) . ' records');

        // Expense Categories - columns: id,academy_id,code,name,description,is_active
        $categories = [
            ['name' => 'เงินเดือนบุคลากร', 'code' => 'SALARY', 'description' => 'ค่าใช้จ่ายด้านบุคลากร'],
            ['name' => 'ค่าสาธารณูปโภค', 'code' => 'UTILITY', 'description' => 'ค่าน้ำ ค่าไฟ ค่าโทรศัพท์'],
            ['name' => 'ค่าวัสดุการเรียน', 'code' => 'MATERIAL', 'description' => 'อุปกรณ์การเรียนการสอน'],
            ['name' => 'ค่าซ่อมบำรุง', 'code' => 'MAINTENANCE', 'description' => 'ค่าซ่อมแซมและบำรุงรักษา'],
            ['name' => 'ค่าใช้จ่ายอื่นๆ', 'code' => 'OTHER', 'description' => 'ค่าใช้จ่ายทั่วไป'],
        ];

        foreach ($categories as $data) {
            ExpenseCategory::updateOrCreate(
                ['academy_id' => $this->academy->id, 'code' => $data['code']],
                array_merge($data, ['academy_id' => $this->academy->id, 'is_active' => true])
            );
        }
        $this->command->info('  ✅ Expense Categories: ' . count($categories) . ' records');

        // Expenses - columns: id,academy_id,expense_category_id,academic_year_id,title,description,amount,expense_date,vendor,reference_number,receipt_image,status,requested_by,approved_by,approved_at,approval_notes
        $category = ExpenseCategory::where('academy_id', $this->academy->id)->first();
        if ($category) {
            $expenses = [
                ['title' => 'ค่าไฟฟ้าประจำเดือน ม.ค.', 'description' => 'ค่าไฟฟ้าเดือนมกราคม 2568', 'amount' => 45000, 'expense_date' => '2026-01-15', 'vendor' => 'การไฟฟ้า'],
                ['title' => 'ค่าน้ำประปาประจำเดือน ม.ค.', 'description' => 'ค่าน้ำประปาเดือนมกราคม 2568', 'amount' => 8000, 'expense_date' => '2026-01-15', 'vendor' => 'การประปา'],
                ['title' => 'ค่าซ่อมแอร์ห้อง 101', 'description' => 'ซ่อมเครื่องปรับอากาศห้อง 101', 'amount' => 5500, 'expense_date' => '2026-01-20', 'vendor' => 'บ.แอร์เซอร์วิส'],
                ['title' => 'ค่าอุปกรณ์กีฬา', 'description' => 'ซื้ออุปกรณ์กีฬาสำหรับนักเรียน', 'amount' => 15000, 'expense_date' => '2026-01-25', 'vendor' => 'ร้านกีฬา'],
            ];

            foreach ($expenses as $data) {
                Expense::updateOrCreate(
                    ['academy_id' => $this->academy->id, 'title' => $data['title']],
                    array_merge($data, [
                        'academy_id' => $this->academy->id,
                        'expense_category_id' => $category->id,
                        'status' => 'approved',
                        'requested_by' => $this->users->first()->id,
                        'approved_by' => $this->users->first()->id,
                        'approved_at' => now(),
                    ])
                );
            }
            $this->command->info('  ✅ Expenses: ' . count($expenses) . ' records');
        }

        // Budgets - columns: id,academy_id,academic_year_id,expense_category_id,name,description,allocated_amount,spent_amount,remaining_amount,period,start_date,end_date,is_active
        if ($category) {
            $budgets = [
                ['name' => 'งบประมาณประจำปี 2568', 'description' => 'งบประมาณดำเนินงานประจำปี', 'allocated_amount' => 5000000, 'spent_amount' => 500000, 'remaining_amount' => 4500000, 'period' => 'yearly', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'],
                ['name' => 'งบพัฒนาห้องเรียน', 'description' => 'งบปรับปรุงห้องเรียน', 'allocated_amount' => 500000, 'spent_amount' => 200000, 'remaining_amount' => 300000, 'period' => 'yearly', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'],
            ];

            foreach ($budgets as $data) {
                Budget::updateOrCreate(
                    ['academy_id' => $this->academy->id, 'name' => $data['name']],
                    array_merge($data, [
                        'academy_id' => $this->academy->id,
                        'academic_year_id' => $this->academicYear->id,
                        'expense_category_id' => $category->id,
                        'is_active' => true,
                    ])
                );
            }
            $this->command->info('  ✅ Budgets: ' . count($budgets) . ' records');
        }
    }

    /**
     * Phase 3: Staff System
     */
    protected function seedStaffSystem(): void
    {
        $this->command->info('');
        $this->command->info('👨‍💼 Seeding Staff System...');

        // Staff Profiles - columns: id,academy_id,user_id,department_id,position_id,employee_id,citizen_id,title_prefix,first_name,last_name,nickname,date_of_birth,gender,phone,emergency_contact_name,emergency_contact_phone,address,hire_date,contract_start_date,contract_end_date,employment_type,status,resignation_date,resignation_reason,education_history,work_history,certifications,skills,profile_image,notes
        $titlePrefixes = ['นาย', 'นางสาว', 'นาง'];
        $firstNames = ['สมชาย', 'สมหญิง', 'วิเชียร', 'พิมพ์ใจ', 'ประสิทธิ์'];
        $lastNames = ['ใจดี', 'สุขสันต์', 'รักเรียน', 'มั่นคง', 'เก่งกาจ'];
        $employmentTypes = ['full_time', 'part_time', 'contract'];

        $staffCount = 0;
        foreach ($this->users->take(5) as $index => $user) {
            StaffProfile::updateOrCreate(
                ['academy_id' => $this->academy->id, 'user_id' => $user->id],
                [
                    'academy_id' => $this->academy->id,
                    'user_id' => $user->id,
                    'employee_id' => 'EMP' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'citizen_id' => '1' . str_pad(rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT),
                    'title_prefix' => $titlePrefixes[$index % count($titlePrefixes)],
                    'first_name' => $firstNames[$index % count($firstNames)],
                    'last_name' => $lastNames[$index % count($lastNames)],
                    'nickname' => substr($firstNames[$index % count($firstNames)], 0, 6),
                    'gender' => $index % 2 == 0 ? 'male' : 'female',
                    'phone' => '08' . rand(10000000, 99999999),
                    'employment_type' => $employmentTypes[$index % count($employmentTypes)],
                    'hire_date' => Carbon::now()->subYears(rand(1, 10)),
                    'status' => 'active',
                ]
            );
            $staffCount++;
        }
        $this->command->info('  ✅ Staff Profiles: ' . $staffCount . ' records');

        // Staff Attendance - columns: id,staff_profile_id,attendance_date,check_in_time,check_out_time,break_start_time,break_end_time,work_hours,overtime_hours,status,check_in_location,check_out_location,notes,approved_by,approved_at
        $staffProfiles = StaffProfile::where('academy_id', $this->academy->id)->get();
        $attendanceCount = 0;
        foreach ($staffProfiles as $staff) {
            for ($i = 1; $i <= 5; $i++) {
                $date = Carbon::now()->subDays($i);
                $checkIn = '08:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT);
                $checkOut = '17:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT);
                StaffAttendance::updateOrCreate(
                    ['staff_profile_id' => $staff->id, 'attendance_date' => $date->toDateString()],
                    [
                        'staff_profile_id' => $staff->id,
                        'attendance_date' => $date,
                        'check_in_time' => $checkIn,
                        'check_out_time' => $checkOut,
                        'work_hours' => 8.0,
                        'overtime_hours' => 0,
                        'status' => rand(0, 10) > 1 ? 'present' : 'late',
                    ]
                );
                $attendanceCount++;
            }
        }
        $this->command->info('  ✅ Staff Attendance: ' . $attendanceCount . ' records');

        // Leave Types - columns: id,academy_id,code,name,description,max_days_per_year,is_paid,is_active
        $leaveTypes = [
            ['name' => 'ลาป่วย', 'code' => 'SICK', 'description' => 'ลาป่วยตามกฎหมายแรงงาน', 'max_days_per_year' => 30, 'is_paid' => true],
            ['name' => 'ลากิจ', 'code' => 'PERSONAL', 'description' => 'ลากิจส่วนตัว', 'max_days_per_year' => 10, 'is_paid' => true],
            ['name' => 'ลาพักร้อน', 'code' => 'VACATION', 'description' => 'ลาพักผ่อนประจำปี', 'max_days_per_year' => 10, 'is_paid' => true],
            ['name' => 'ลาคลอด', 'code' => 'MATERNITY', 'description' => 'ลาคลอดบุตร', 'max_days_per_year' => 90, 'is_paid' => true],
        ];

        foreach ($leaveTypes as $data) {
            LeaveType::updateOrCreate(
                ['academy_id' => $this->academy->id, 'code' => $data['code']],
                array_merge($data, ['academy_id' => $this->academy->id, 'is_active' => true])
            );
        }
        $this->command->info('  ✅ Leave Types: ' . count($leaveTypes) . ' records');

        // Leave Requests - columns: id,staff_profile_id,leave_type_id,start_date,end_date,total_days,leave_period,reason,document_path,contact_during_leave,status,approved_by,approved_at,rejection_reason,substitute_staff_id
        $leaveType = LeaveType::where('academy_id', $this->academy->id)->first();
        if ($leaveType && $staffProfiles->isNotEmpty()) {
            $staff = $staffProfiles->first();
            LeaveRequest::updateOrCreate(
                ['staff_profile_id' => $staff->id, 'start_date' => '2026-02-10'],
                [
                    'staff_profile_id' => $staff->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => '2026-02-10',
                    'end_date' => '2026-02-11',
                    'total_days' => 2,
                    'leave_period' => 'full_day',
                    'reason' => 'ลาป่วย ไม่สบาย',
                    'status' => 'approved',
                    'approved_by' => $this->users->first()->id,
                    'approved_at' => now(),
                ]
            );
            $this->command->info('  ✅ Leave Requests: 1 record');
        }

        // Payroll - columns: id,academy_id,staff_profile_id,salary_structure_id,payroll_period,period_start,period_end,payment_date,base_salary,total_allowances,total_deductions,overtime_pay,bonus,tax,social_security,net_salary,status,payment_method,bank_name,bank_account,payslip_number,notes,created_by,approved_by,approved_at
        foreach ($staffProfiles->take(3) as $index => $staff) {
            $baseSalary = rand(25000, 60000);
            $allowances = rand(2000, 5000);
            $deductions = rand(1000, 3000);
            Payroll::updateOrCreate(
                ['academy_id' => $this->academy->id, 'staff_profile_id' => $staff->id, 'period_start' => '2026-01-01'],
                [
                    'academy_id' => $this->academy->id,
                    'staff_profile_id' => $staff->id,
                    'payroll_period' => 'monthly',
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                    'payment_date' => '2026-01-31',
                    'base_salary' => $baseSalary,
                    'total_allowances' => $allowances,
                    'total_deductions' => $deductions,
                    'overtime_pay' => 0,
                    'bonus' => 0,
                    'tax' => $baseSalary * 0.05,
                    'social_security' => 750,
                    'net_salary' => $baseSalary + $allowances - $deductions,
                    'status' => 'paid',
                    'payment_method' => 'bank_transfer',
                    'bank_name' => 'กสิกรไทย',
                    'bank_account' => rand(1000000000, 9999999999),
                    'payslip_number' => 'PAY-2026-01-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'created_by' => $this->users->first()->id,
                    'approved_by' => $this->users->first()->id,
                    'approved_at' => now(),
                ]
            );
        }
        $this->command->info('  ✅ Payroll: 3 records');
    }

    /**
     * Phase 4: Communication System
     */
    protected function seedCommunicationSystem(): void
    {
        $this->command->info('');
        $this->command->info('📢 Seeding Communication System...');

        // Announcements - columns: id,academy_id,created_by,title,content,announcement_type,priority,target_audience,attachments,is_pinned,is_published,published_at,expires_at,view_count
        $announcements = [
            [
                'title' => 'ประกาศเปิดภาคเรียนที่ 2/2568',
                'content' => 'โรงเรียนจะเปิดภาคเรียนที่ 2 ประจำปีการศึกษา 2568 ในวันที่ 1 พฤศจิกายน 2568 ขอให้นักเรียนทุกคนมาลงทะเบียนตามกำหนด',
                'announcement_type' => 'general',
                'priority' => 'high',
            ],
            [
                'title' => 'กำหนดการสอบกลางภาค',
                'content' => 'การสอบกลางภาคจะจัดขึ้นระหว่างวันที่ 15-19 กุมภาพันธ์ 2568',
                'announcement_type' => 'academic',
                'priority' => 'normal',
            ],
            [
                'title' => 'งานวันเด็กแห่งชาติ',
                'content' => 'โรงเรียนจัดงานวันเด็กในวันเสาร์ที่ 11 มกราคม 2568 ตั้งแต่เวลา 8.00-12.00 น.',
                'announcement_type' => 'event',
                'priority' => 'normal',
            ],
        ];

        foreach ($announcements as $data) {
            SchoolAnnouncement::updateOrCreate(
                ['academy_id' => $this->academy->id, 'title' => $data['title']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'created_by' => $this->users->first()->id,
                    'target_audience' => json_encode(['all']),
                    'is_pinned' => $data['priority'] === 'high',
                    'is_published' => true,
                    'published_at' => now(),
                    'view_count' => rand(10, 100),
                ])
            );
        }
        $this->command->info('  ✅ Announcements: ' . count($announcements) . ' records');

        // Events - columns: id,academy_id,created_by,title,description,event_type,category,start_datetime,end_datetime,location,location_type,meeting_url,max_participants,requires_registration,registration_deadline,registration_fee,target_audience,cover_image,attachments,is_all_day,is_recurring,recurrence_pattern,status,published_at
        $events = [
            [
                'title' => 'กีฬาสี ประจำปี 2568',
                'description' => 'การแข่งขันกีฬาสีประจำปีการศึกษา 2568',
                'event_type' => 'sports',
                'category' => 'sport',
                'start_datetime' => '2026-02-20 08:00:00',
                'end_datetime' => '2026-02-21 16:00:00',
                'location' => 'สนามกีฬาโรงเรียน',
            ],
            [
                'title' => 'ประชุมผู้ปกครอง',
                'description' => 'ประชุมผู้ปกครองเพื่อรายงานผลการเรียน',
                'event_type' => 'meeting',
                'category' => 'academic',
                'start_datetime' => '2026-03-15 09:00:00',
                'end_datetime' => '2026-03-15 12:00:00',
                'location' => 'หอประชุม',
            ],
            [
                'title' => 'งานปัจฉิมนิเทศ',
                'description' => 'งานปัจฉิมนิเทศนักเรียนชั้น ม.6',
                'event_type' => 'ceremony',
                'category' => 'graduation',
                'start_datetime' => '2026-03-20 13:00:00',
                'end_datetime' => '2026-03-20 17:00:00',
                'location' => 'หอประชุม',
            ],
        ];

        foreach ($events as $data) {
            SchoolEvent::updateOrCreate(
                ['academy_id' => $this->academy->id, 'title' => $data['title']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'created_by' => $this->users->first()->id,
                    'location_type' => 'onsite',
                    'status' => 'published',
                    'max_participants' => rand(50, 500),
                    'requires_registration' => false,
                    'is_all_day' => false,
                    'is_recurring' => false,
                    'published_at' => now(),
                ])
            );
        }
        $this->command->info('  ✅ Events: ' . count($events) . ' records');

        // Meeting Slots - columns: id,academy_id,teacher_id,meeting_date,start_time,end_time,slot_duration,location,meeting_type,meeting_url,is_available
        for ($i = 1; $i <= 5; $i++) {
            MeetingSlot::updateOrCreate(
                [
                    'academy_id' => $this->academy->id,
                    'teacher_id' => $this->users->random()->id,
                    'meeting_date' => Carbon::now()->addDays($i)->toDateString(),
                    'start_time' => '14:00',
                ],
                [
                    'academy_id' => $this->academy->id,
                    'teacher_id' => $this->users->random()->id,
                    'meeting_date' => Carbon::now()->addDays($i),
                    'start_time' => '14:00',
                    'end_time' => '14:30',
                    'slot_duration' => 30,
                    'meeting_type' => 'parent_teacher',
                    'location' => 'ห้องรับรอง',
                    'is_available' => true,
                ]
            );
        }
        $this->command->info('  ✅ Meeting Slots: 5 records');
    }

    /**
     * Phase 5: Reports & Analytics System
     */
    protected function seedReportsSystem(): void
    {
        $this->command->info('');
        $this->command->info('📊 Seeding Reports & Analytics System...');

        // Report Definitions - columns: id,academy_id,code,name,description,category,report_type,data_source,columns,filters,grouping,sorting,chart_config,is_system,is_active,created_by
        $reports = [
            [
                'code' => 'ENROLLMENT_REPORT',
                'name' => 'รายงานการลงทะเบียน',
                'description' => 'รายงานสถิติการลงทะเบียนนักเรียน',
                'category' => 'enrollment',
                'report_type' => 'table',
                'data_source' => 'students',
            ],
            [
                'code' => 'ATTENDANCE_REPORT',
                'name' => 'รายงานการเข้าเรียน',
                'description' => 'รายงานสถิติการเข้าเรียนของนักเรียน',
                'category' => 'attendance',
                'report_type' => 'chart',
                'data_source' => 'attendance',
            ],
            [
                'code' => 'GRADE_REPORT',
                'name' => 'รายงานผลการเรียน',
                'description' => 'รายงานผลการเรียนแยกตามรายวิชา',
                'category' => 'academic',
                'report_type' => 'table',
                'data_source' => 'grades',
            ],
            [
                'code' => 'FINANCIAL_REPORT',
                'name' => 'รายงานรายรับ-รายจ่าย',
                'description' => 'รายงานสรุปการเงินของโรงเรียน',
                'category' => 'financial',
                'report_type' => 'summary',
                'data_source' => 'payments,expenses',
            ],
        ];

        foreach ($reports as $data) {
            ReportDefinition::updateOrCreate(
                ['academy_id' => $this->academy->id, 'code' => $data['code']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'is_system' => false,
                    'is_active' => true,
                    'created_by' => $this->users->first()->id,
                ])
            );
        }
        $this->command->info('  ✅ Report Definitions: ' . count($reports) . ' records');

        // Dashboard Widgets - columns: id,academy_id,code,name,widget_type,category,data_source,display_config,refresh_interval,is_system,is_active
        $widgets = [
            [
                'code' => 'STUDENT_COUNT',
                'name' => 'จำนวนนักเรียน',
                'widget_type' => 'counter',
                'category' => 'overview',
                'data_source' => 'students_count',
                'refresh_interval' => 3600,
            ],
            [
                'code' => 'MONTHLY_INCOME',
                'name' => 'รายรับประจำเดือน',
                'widget_type' => 'counter',
                'category' => 'financial',
                'data_source' => 'monthly_income',
                'refresh_interval' => 3600,
            ],
            [
                'code' => 'ATTENDANCE_RATE',
                'name' => 'อัตราการเข้าเรียน',
                'widget_type' => 'progress',
                'category' => 'attendance',
                'data_source' => 'attendance_rate',
                'refresh_interval' => 1800,
            ],
            [
                'code' => 'INCOME_EXPENSE_CHART',
                'name' => 'กราฟรายรับ-รายจ่าย',
                'widget_type' => 'chart',
                'category' => 'financial',
                'data_source' => 'income_expense_chart',
                'refresh_interval' => 3600,
            ],
        ];

        foreach ($widgets as $data) {
            DashboardWidget::updateOrCreate(
                ['academy_id' => $this->academy->id, 'code' => $data['code']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'display_config' => json_encode([]),
                    'is_system' => false,
                    'is_active' => true,
                ])
            );
        }
        $this->command->info('  ✅ Dashboard Widgets: ' . count($widgets) . ' records');

        // KPI Definitions - columns: id,academy_id,code,name,description,category,metric_type,calculation,target_value,warning_threshold,critical_threshold,comparison_direction,is_active
        $kpis = [
            [
                'code' => 'ATTENDANCE_RATE',
                'name' => 'อัตราการเข้าเรียน',
                'description' => 'เปอร์เซ็นต์การเข้าเรียนของนักเรียน',
                'category' => 'academic',
                'metric_type' => 'percentage',
                'calculation' => 'attendance_count / total_students * 100',
                'target_value' => 95.00,
                'warning_threshold' => 90.00,
                'critical_threshold' => 85.00,
                'comparison_direction' => 'higher_is_better',
            ],
            [
                'code' => 'COLLECTION_RATE',
                'name' => 'อัตราการเก็บค่าเทอม',
                'description' => 'เปอร์เซ็นต์การเก็บค่าธรรมเนียมสำเร็จ',
                'category' => 'financial',
                'metric_type' => 'percentage',
                'calculation' => 'collected_amount / total_fee * 100',
                'target_value' => 100.00,
                'warning_threshold' => 95.00,
                'critical_threshold' => 90.00,
                'comparison_direction' => 'higher_is_better',
            ],
            [
                'code' => 'STAFF_ATTENDANCE',
                'name' => 'อัตราการเข้างานบุคลากร',
                'description' => 'เปอร์เซ็นต์การมาทำงานของบุคลากร',
                'category' => 'staff',
                'metric_type' => 'percentage',
                'calculation' => 'present_count / total_staff * 100',
                'target_value' => 98.00,
                'warning_threshold' => 95.00,
                'critical_threshold' => 90.00,
                'comparison_direction' => 'higher_is_better',
            ],
            [
                'code' => 'STUDENT_COUNT',
                'name' => 'จำนวนนักเรียนทั้งหมด',
                'description' => 'จำนวนนักเรียนที่ลงทะเบียนในปัจจุบัน',
                'category' => 'operational',
                'metric_type' => 'count',
                'calculation' => 'count(students)',
                'target_value' => 1000,
                'comparison_direction' => 'higher_is_better',
            ],
        ];

        foreach ($kpis as $data) {
            KpiDefinition::updateOrCreate(
                ['academy_id' => $this->academy->id, 'code' => $data['code']],
                array_merge($data, [
                    'academy_id' => $this->academy->id,
                    'is_active' => true,
                ])
            );
        }
        $this->command->info('  ✅ KPI Definitions: ' . count($kpis) . ' records');
    }
}
