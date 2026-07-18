<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_point_transactions', function (Blueprint $table) {
            $table->string('type', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('course_point_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'lesson_income',
                'owner_withdraw',
                'campaign_debit',
                'student_claim',
                'refund',
            ])->change();
        });
    }
};
