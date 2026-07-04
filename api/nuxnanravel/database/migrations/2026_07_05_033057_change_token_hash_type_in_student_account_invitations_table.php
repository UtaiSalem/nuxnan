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
        Schema::table('student_account_invitations', function (Blueprint $table) {
            $table->dropUnique(['token_hash']);
            $table->text('token_hash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_account_invitations', function (Blueprint $table) {
            $table->string('token_hash', 64)->unique()->change();
        });
    }
};
