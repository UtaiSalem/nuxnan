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
        // สร้างตาราง academy_roles สำหรับเก็บบทบาทในโรงเรียน
        Schema::create('academy_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id')->nullable(); // NULL = default/system roles
            $table->string('name', 50); // teacher, student, parent, etc.
            $table->string('display_name_th', 100); // ชื่อแสดงภาษาไทย
            $table->string('display_name_en', 100)->nullable(); // ชื่อแสดงภาษาอังกฤษ
            $table->text('description')->nullable();
            $table->json('permissions')->nullable(); // เก็บ permissions เป็น JSON array
            $table->boolean('is_system')->default(false); // true = system role ลบไม่ได้
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('color', 20)->nullable(); // สีสำหรับแสดง badge
            $table->string('icon', 50)->nullable(); // icon class
            $table->timestamps();

            // Indexes
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->unique(['academy_id', 'name'], 'unique_academy_role');
            $table->index('is_system');
        });

        // เพิ่ม role_id ใน academy_members
        Schema::table('academy_members', function (Blueprint $table) {
            $table->unsignedBigInteger('academy_role_id')->nullable()->after('role');
            $table->foreign('academy_role_id')->references('id')->on('academy_roles')->onDelete('set null');
        });

        // สร้างตาราง academy_role_permissions สำหรับ granular permissions (optional)
        Schema::create('academy_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // academy.members.view, academy.courses.create, etc.
            $table->string('display_name', 150);
            $table->string('group', 50); // members, courses, finance, reports, etc.
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_members', function (Blueprint $table) {
            $table->dropForeign(['academy_role_id']);
            $table->dropColumn('academy_role_id');
        });

        Schema::dropIfExists('academy_permissions');
        Schema::dropIfExists('academy_roles');
    }
};
