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
        Schema::create('course_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_member_id')->constrained('course_members')->onDelete('cascade');
            $table->string('permission');
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['course_member_id', 'permission']);
            $table->index(['course_member_id']);
            $table->index(['permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_permissions');
    }
};
