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
        Schema::table('academy_group_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_group_admins', 'academy_group_id')) {
                $table->foreignId('academy_group_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('academy_group_admins', 'user_id')) {
                $table->foreignId('user_id')->after('academy_group_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('academy_group_admins', 'role')) {
                $table->string('role')->default('admin')->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_group_admins', function (Blueprint $table) {
            $table->dropForeign(['academy_group_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['academy_group_id', 'user_id', 'role']);
        });
    }
};
