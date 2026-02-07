<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: Staff System - บุคลากร
 * 
 * Tables:
 * - staff_profiles: ข้อมูลบุคลากร
 * - positions: ตำแหน่ง
 * - staff_attendances: การเข้างานบุคลากร
 * - leave_types: ประเภทการลา
 * - leave_requests: คำขอลา
 * - salary_structures: โครงสร้างเงินเดือน
 * - payrolls: เงินเดือน
 * - payroll_items: รายการเงินเดือน
 * - performance_reviews: การประเมินผลงาน
 * - training_programs: หลักสูตรอบรม
 * - staff_trainings: การอบรมบุคลากร
 */
return new class extends Migration
{
    public function up(): void
    {
        // Positions - ตำแหน่ง
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->string('level', 20)->nullable()->comment('ระดับตำแหน่ง');
            $table->text('description')->nullable();
            $table->json('responsibilities')->nullable()->comment('หน้าที่รับผิดชอบ');
            $table->decimal('min_salary', 12, 2)->nullable();
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->boolean('is_teaching_position')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index(['academy_id', 'department_id']);
        });

        // Staff Profiles - ข้อมูลบุคลากร
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->string('employee_id', 20)->comment('รหัสพนักงาน');
            $table->string('citizen_id', 13)->nullable();
            $table->string('title_prefix', 20)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('nickname', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->date('hire_date');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary'])->default('full_time');
            $table->enum('status', ['active', 'on_leave', 'suspended', 'resigned', 'terminated'])->default('active');
            $table->date('resignation_date')->nullable();
            $table->string('resignation_reason')->nullable();
            $table->json('education_history')->nullable()->comment('ประวัติการศึกษา');
            $table->json('work_history')->nullable()->comment('ประวัติการทำงาน');
            $table->json('certifications')->nullable()->comment('ใบรับรอง/ใบอนุญาต');
            $table->json('skills')->nullable();
            $table->string('profile_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['academy_id', 'employee_id']);
            $table->unique(['academy_id', 'user_id']);
            $table->index(['academy_id', 'department_id', 'status']);
        });

        // Staff Attendances - การเข้างานบุคลากร
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->decimal('work_hours', 5, 2)->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'early_leave', 'half_day', 'on_leave', 'holiday', 'work_from_home'])->default('present');
            $table->string('check_in_location')->nullable();
            $table->string('check_out_location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['staff_profile_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
        });

        // Leave Types - ประเภทการลา
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_days_per_year')->nullable()->comment('จำนวนวันสูงสุดต่อปี');
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->integer('advance_notice_days')->default(0)->comment('ต้องแจ้งล่วงหน้ากี่วัน');
            $table->json('applicable_to')->nullable()->comment('ใช้ได้กับประเภทพนักงาน');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->unique(['academy_id', 'code']);
        });

        // Leave Requests - คำขอลา
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2);
            $table->enum('leave_period', ['full_day', 'morning', 'afternoon'])->default('full_day');
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->string('contact_during_leave')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('substitute_staff_id')->nullable()->constrained('staff_profiles')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['staff_profile_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // Salary Structures - โครงสร้างเงินเดือน
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('pay_frequency', ['monthly', 'bi_weekly', 'weekly'])->default('monthly');
            $table->json('allowances')->nullable()->comment('เงินเพิ่ม: ค่าตำแหน่ง, ค่าครองชีพ, etc.');
            $table->json('deductions')->nullable()->comment('เงินหัก: ประกันสังคม, ภาษี, etc.');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Payrolls - เงินเดือน
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_structure_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payroll_period', 20)->comment('เช่น 2026-02');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date')->nullable();
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('social_security', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->string('payment_method', 50)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('payslip_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['staff_profile_id', 'payroll_period']);
            $table->index(['academy_id', 'payroll_period', 'status']);
        });

        // Payroll Items - รายการเงินเดือน (รายละเอียด)
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['earning', 'deduction']);
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_taxable')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index(['payroll_id', 'type']);
        });

        // Performance Reviews - การประเมินผลงาน
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->string('review_period', 50)->comment('เช่น 2026-Q1, 2026-H1');
            $table->date('review_date');
            $table->json('criteria_scores')->nullable()->comment('คะแนนตามเกณฑ์');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->enum('rating', ['excellent', 'good', 'satisfactory', 'needs_improvement', 'unsatisfactory'])->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->text('staff_comments')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->enum('status', ['draft', 'submitted', 'acknowledged', 'final'])->default('draft');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            
            $table->unique(['staff_profile_id', 'review_period']);
        });

        // Training Programs - หลักสูตรอบรม
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['internal', 'external', 'online', 'workshop', 'certification'])->default('internal');
            $table->string('provider')->nullable();
            $table->integer('duration_hours')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->integer('max_participants')->nullable();
            $table->decimal('cost_per_person', 10, 2)->nullable();
            $table->json('target_positions')->nullable()->comment('ตำแหน่งเป้าหมาย');
            $table->json('prerequisites')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Staff Trainings - การอบรมบุคลากร
        Schema::create('staff_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_program_id')->constrained()->onDelete('cascade');
            $table->date('enrollment_date');
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'cancelled', 'failed'])->default('enrolled');
            $table->date('completion_date')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('certificate_expiry')->nullable();
            $table->text('feedback')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['staff_profile_id', 'training_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_trainings');
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('positions');
    }
};
