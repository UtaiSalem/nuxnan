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
        Schema::table('course_invitations', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')->default(3)->after('invitee_id')->comment('3: Teacher(TA), 4: Admin');
            $table->json('permissions')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_invitations', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};
