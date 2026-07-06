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
        Schema::table('academy_members', function (Blueprint $table) {
            if (! Schema::hasColumn('academy_members', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('status');
                $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('academy_members', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('invited_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_members', function (Blueprint $table) {
            if (Schema::hasColumn('academy_members', 'invited_by')) {
                $table->dropForeign(['invited_by']);
                $table->dropColumn('invited_by');
            }
            if (Schema::hasColumn('academy_members', 'invited_at')) {
                $table->dropColumn('invited_at');
            }
        });
    }
};
