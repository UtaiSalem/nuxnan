<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classroom & Membership Management Module — Schema Upgrade
 *
 * Adds new columns to existing `classrooms` table and creates 3 new tables:
 * - classroom_members (replaces classroom_students with multi-role support)
 * - classroom_groups (sub-groups within a classroom)
 * - group_members (members of classroom groups)
 * - classroom_invitations (invitation links and email invitations)
 *
 * CASCADE RULES:
 * - classrooms deleted → cascade classroom_members, classroom_groups, classroom_invitations
 * - classroom_groups deleted → cascade group_members
 * - classroom_members deleted → cascade group_members (via service-layer hook)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. Upgrade existing classrooms table
        // ──────────────────────────────────────────────
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('classroom_code', 6)->nullable()->unique()->after('id')
                ->comment('6-char alphanumeric auto-generated code for join');
            $table->string('academic_year', 10)->nullable()->after('academic_year_id')
                ->comment('ปีการศึกษา format: 2567 or 2024');
            $table->unsignedTinyInteger('semester')->nullable()->after('academic_year')
                ->comment('1 or 2');
            $table->enum('status', ['active', 'archived'])->default('active')->after('is_active');
            $table->unsignedBigInteger('created_by')->nullable()->after('status');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('classroom_code');
            $table->index('status');
            $table->index(['academic_year', 'semester']);
        });

        // ──────────────────────────────────────────────
        // 2. Create classroom_members table
        // ──────────────────────────────────────────────
        Schema::create('classroom_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['teacher', 'co_teacher', 'student', 'observer'])->default('student');
            $table->integer('student_no')->nullable()->comment('เลขที่นั่ง/เลขที่เรียง');
            $table->enum('join_method', ['admin_assigned', 'invitation', 'classroom_code'])->default('admin_assigned');
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable()->comment('Populated on removal (soft-delete)');
            $table->unsignedBigInteger('added_by')->nullable()->comment('nullable for self-join via code');
            $table->timestamps();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['classroom_id', 'user_id'], 'classroom_members_unique');
            $table->index(['classroom_id', 'role']);
            $table->index(['classroom_id', 'is_active']);
            $table->index('user_id');
        });

        // ──────────────────────────────────────────────
        // 3. Create classroom_groups table
        // ──────────────────────────────────────────────
        Schema::create('classroom_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classroom_id');
            $table->string('group_name');
            $table->enum('group_type', ['static', 'dynamic'])->default('static');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('classroom_id');
        });

        // ──────────────────────────────────────────────
        // 4. Create group_members table
        // ──────────────────────────────────────────────
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('member_id')->comment('FK → classroom_members, NOT users');
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();

            $table->foreign('group_id')->references('id')->on('classroom_groups')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('classroom_members')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['group_id', 'member_id'], 'group_members_unique');
            $table->index('group_id');
            $table->index('member_id');
        });

        // ──────────────────────────────────────────────
        // 5. Create classroom_invitations table
        // ──────────────────────────────────────────────
        Schema::create('classroom_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classroom_id');
            $table->uuid('token')->unique()->comment('UUID v4 invitation link token');
            $table->string('invited_email')->nullable()->comment('nullable if inviting existing user by ID');
            $table->unsignedBigInteger('invited_user_id')->nullable()->comment('nullable if inviting by email only');
            $table->enum('role_to_assign', ['teacher', 'co_teacher', 'student', 'observer'])->default('student');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('invited_by');
            $table->timestamp('expires_at')->comment('Default: now() + 7 days');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('invited_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('token');
            $table->index(['classroom_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_invitations');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('classroom_groups');
        Schema::dropIfExists('classroom_members');

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['classroom_code']);
            $table->dropIndex(['status']);
            $table->dropIndex(['academic_year', 'semester']);
            $table->dropColumn(['classroom_code', 'academic_year', 'semester', 'status', 'created_by']);
        });
    }
};
