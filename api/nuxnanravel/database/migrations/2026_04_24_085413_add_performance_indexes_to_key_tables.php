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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_status']);
            $table->index('created_at');
        });

        Schema::table('course_members', function (Blueprint $table) {
            $table->index(['course_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->index(['academy_store_id', 'status']);
            $table->index('user_id');
            $table->index('order_number');
        });

        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->index(['quiz_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('course_members', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'user_id']);
            $table->dropIndex(['user_id', 'role']);
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropIndex(['academy_store_id', 'status']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['order_number']);
        });

        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'user_id']);
            $table->dropIndex(['status']);
        });
    }
};
