<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_user_deletion_audits', function (Blueprint $table) {
            $table->id();

            // nullable เผื่อ hard delete ในอนาคต (user row จะถูกลบถาวร)
            $table->unsignedBigInteger('deleted_user_id')->nullable();
            $table->string('deleted_user_email')->nullable(); // เก็บ email ก่อน anonymize

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->enum('mode', ['soft_delete', 'restore', 'force_delete'])
                ->default('soft_delete');

            $table->text('reason')->nullable();

            // Snapshot ข้อมูล user ก่อนลบ (email จริง, ชื่อจริง, roles ฯลฯ)
            $table->json('user_snapshot')->nullable();

            // Impact ที่ตรวจพบตอนลบ (orphan counts, cascade counts)
            $table->json('impact_snapshot')->nullable();

            $table->timestamps();

            $table->index('deleted_user_id');
            $table->index('deleted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_user_deletion_audits');
    }
};
