<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ใบประกาศนียบัตร/Certificate
     */
    public function up(): void
    {
        Schema::create('course_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique()
                ->comment('เลขที่ใบประกาศ');

            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Certificate type
            $table->enum('type', [
                'completion',       // ใบจบวิชา
                'achievement',      // ใบประกาศเกียรติคุณ
                'participation',    // ใบรับรองการเข้าร่วม
                'excellence',        // ใบประกาศความเป็นเลิศ
            ])->default('completion');

            // Grade at issuance
            $table->string('grade', 2);
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('grade_point', 3, 2)->nullable();

            // Certificate details
            $table->string('student_name');
            $table->string('course_title');
            $table->string('instructor_name')->nullable();
            $table->date('completion_date');
            $table->date('issue_date');

            // Digital verification
            $table->string('verification_code', 64)->unique()
                ->comment('รหัสตรวจสอบความถูกต้อง');
            $table->string('qr_code_path')->nullable();

            // PDF storage
            $table->string('pdf_path')->nullable();
            $table->string('thumbnail_path')->nullable();

            // Status
            $table->enum('status', ['draft', 'issued', 'revoked', 'expired'])
                ->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()
                ->constrained('users')->nullOnDelete();

            // Revocation (if needed)
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();

            // Download tracking
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();

            // Expiration (optional)
            $table->date('expires_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'type'], 'cc_student_type_idx');
            $table->index(['course_id', 'status'], 'cc_course_status_idx');
            $table->index('verification_code', 'cc_verification_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_certificates');
    }
};
