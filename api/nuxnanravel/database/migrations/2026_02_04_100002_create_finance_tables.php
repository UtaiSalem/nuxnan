<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // Fee Structures - โครงสร้างค่าเล่าเรียน (Template)
        // =====================================================
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อโครงสร้าง เช่น "ค่าเทอม ป.1", "ค่าเทอม ม.1"
            $table->text('description')->nullable();
            $table->json('grade_levels')->nullable(); // ระดับชั้นที่ใช้ [1, 2, 3]
            $table->decimal('total_amount', 12, 2)->default(0); // ยอดรวมทั้งหมด
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academy_id', 'academic_year_id']);
        });

        // =====================================================
        // Fee Items - รายการค่าใช้จ่ายในโครงสร้าง
        // =====================================================
        Schema::create('fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อรายการ เช่น "ค่าเล่าเรียน", "ค่าอาหาร", "ค่ารถ"
            $table->string('code', 50)->nullable(); // รหัสรายการ
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2); // จำนวนเงิน
            $table->enum('fee_type', [
                'tuition',      // ค่าเล่าเรียน
                'registration', // ค่าลงทะเบียน
                'activity',     // ค่ากิจกรรม
                'transport',    // ค่ารถ
                'meal',         // ค่าอาหาร
                'uniform',      // ค่าชุดนักเรียน
                'book',         // ค่าหนังสือ
                'insurance',    // ค่าประกัน
                'other'         // อื่นๆ
            ])->default('other');
            $table->boolean('is_mandatory')->default(true); // บังคับจ่ายหรือไม่
            $table->boolean('is_refundable')->default(false); // คืนเงินได้หรือไม่
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('fee_structure_id');
        });

        // =====================================================
        // Tuition Fees - ค่าเล่าเรียนของนักเรียน
        // =====================================================
        Schema::create('tuition_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            
            // Financial details
            $table->string('invoice_number', 50)->unique(); // เลขที่ใบแจ้งหนี้
            $table->decimal('total_amount', 12, 2); // ยอดรวม
            $table->decimal('discount_amount', 12, 2)->default(0); // ส่วนลด
            $table->decimal('scholarship_amount', 12, 2)->default(0); // ทุนการศึกษา
            $table->decimal('net_amount', 12, 2); // ยอดสุทธิที่ต้องชำระ
            $table->decimal('paid_amount', 12, 2)->default(0); // ยอดที่ชำระแล้ว
            $table->decimal('balance_amount', 12, 2); // ยอดค้างชำระ
            
            // Dates
            $table->date('issue_date'); // วันที่ออกใบแจ้งหนี้
            $table->date('due_date'); // วันครบกำหนดชำระ
            $table->date('paid_date')->nullable(); // วันที่ชำระครบ
            
            // Status
            $table->enum('status', [
                'pending',      // รอชำระ
                'partial',      // ชำระบางส่วน
                'paid',         // ชำระครบแล้ว
                'overdue',      // เกินกำหนด
                'cancelled',    // ยกเลิก
                'refunded'      // คืนเงินแล้ว
            ])->default('pending');
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['academy_id', 'academic_year_id']);
            $table->index(['student_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        // =====================================================
        // Tuition Fee Items - รายการในใบแจ้งหนี้
        // =====================================================
        Schema::create('tuition_fee_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_fee_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // ชื่อรายการ
            $table->string('fee_type', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tuition_fee_id');
        });

        // =====================================================
        // Payment Methods - วิธีการชำระเงิน
        // =====================================================
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อวิธี เช่น "เงินสด", "โอนเงิน", "บัตรเครดิต"
            $table->string('code', 50)->unique(); // รหัส เช่น "cash", "transfer", "credit_card"
            $table->text('description')->nullable();
            $table->json('config')->nullable(); // ตั้งค่าเพิ่มเติม เช่น เลขบัญชี
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();
        });

        // =====================================================
        // Payments - การชำระเงิน
        // =====================================================
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('tuition_fee_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // ผู้ชำระ (parent/guardian)
            
            // Payment details
            $table->string('receipt_number', 50)->unique(); // เลขที่ใบเสร็จ
            $table->decimal('amount', 12, 2); // จำนวนเงินที่ชำระ
            $table->date('payment_date'); // วันที่ชำระ
            $table->string('payment_method_name', 50)->nullable(); // ชื่อวิธีการชำระ
            
            // Bank transfer details
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->string('transfer_reference', 100)->nullable();
            $table->string('slip_image')->nullable(); // รูปสลิป
            
            // Status
            $table->enum('status', [
                'pending',      // รอตรวจสอบ
                'confirmed',    // ยืนยันแล้ว
                'rejected',     // ปฏิเสธ
                'refunded'      // คืนเงินแล้ว
            ])->default('confirmed');
            
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tuition_fee_id', 'status']);
            $table->index(['academy_id', 'payment_date']);
        });

        // =====================================================
        // Scholarships - ทุนการศึกษา
        // =====================================================
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อทุน
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2); // % หรือ จำนวนเงิน
            $table->decimal('max_amount', 12, 2)->nullable(); // จำนวนเงินสูงสุด (กรณี %)
            $table->json('applicable_fee_types')->nullable(); // ใช้กับค่าใช้จ่ายประเภทไหนบ้าง
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_recipients')->nullable(); // จำนวนคนที่รับได้
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academy_id', 'is_active']);
        });

        // =====================================================
        // Student Scholarships - ทุนที่นักเรียนได้รับ
        // =====================================================
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'suspended', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'scholarship_id', 'academic_year_id'], 'unique_student_scholarship');
        });

        // =====================================================
        // Fee Discounts - ส่วนลดค่าเล่าเรียน
        // =====================================================
        Schema::create('fee_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->enum('applies_to', [
                'all',          // ทั้งหมด
                'tuition',      // เฉพาะค่าเล่าเรียน
                'specific'      // รายการที่ระบุ
            ])->default('all');
            $table->json('applicable_items')->nullable(); // fee_item_ids ที่ใช้ได้
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // =====================================================
        // Payment Plans - แผนผ่อนชำระ
        // =====================================================
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อแผน เช่น "ผ่อน 3 งวด", "ผ่อน 6 งวด"
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('installments'); // จำนวนงวด
            $table->json('installment_percentages'); // % แต่ละงวด [40, 30, 30]
            $table->decimal('interest_rate', 5, 2)->default(0); // ดอกเบี้ย %
            $table->decimal('admin_fee', 12, 2)->default(0); // ค่าธรรมเนียม
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // =====================================================
        // Student Payment Plans - แผนผ่อนของนักเรียน
        // =====================================================
        Schema::create('student_payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tuition_fee_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_plan_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('installment_number'); // งวดที่
            $table->decimal('amount', 12, 2); // จำนวนเงินงวดนี้
            $table->date('due_date'); // วันครบกำหนด
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('paid_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->timestamps();

            $table->index(['tuition_fee_id', 'status']);
        });

        // =====================================================
        // Expense Categories - หมวดหมู่รายจ่าย
        // =====================================================
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // =====================================================
        // Expenses - รายจ่าย
        // =====================================================
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('vendor', 255)->nullable(); // ผู้รับเงิน/ร้านค้า
            $table->string('reference_number', 100)->nullable(); // เลขที่อ้างอิง
            $table->string('receipt_image')->nullable(); // รูปใบเสร็จ
            
            $table->enum('status', [
                'pending',      // รอการอนุมัติ
                'approved',     // อนุมัติแล้ว
                'rejected',     // ปฏิเสธ
                'paid',         // จ่ายแล้ว
                'reimbursed'    // เบิกคืนแล้ว
            ])->default('pending');
            
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();

            $table->index(['academy_id', 'expense_date']);
            $table->index(['status', 'expense_date']);
        });

        // =====================================================
        // Budgets - งบประมาณ
        // =====================================================
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('allocated_amount', 12, 2); // งบที่จัดสรร
            $table->decimal('spent_amount', 12, 2)->default(0); // ใช้ไปแล้ว
            $table->decimal('remaining_amount', 12, 2); // คงเหลือ
            $table->enum('period', ['yearly', 'semester', 'monthly'])->default('yearly');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['academy_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('student_payment_plans');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('fee_discounts');
        Schema::dropIfExists('student_scholarships');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('tuition_fee_items');
        Schema::dropIfExists('tuition_fees');
        Schema::dropIfExists('fee_items');
        Schema::dropIfExists('fee_structures');
    }
};
