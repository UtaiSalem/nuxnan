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
            // Check if index exists before adding
            $conn = DB::connection();
            $dbName = $conn->getDatabaseName();
            
            $existingIndexes = collect(DB::select("SHOW INDEX FROM notifications"))->pluck('Key_name');

            if (!$existingIndexes->contains('notifications_user_id_read_status_index')) {
                $table->index(['user_id', 'read_status']);
            }
            if (!$existingIndexes->contains('notifications_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('course_members', function (Blueprint $table) {
            $existingIndexes = collect(DB::select("SHOW INDEX FROM course_members"))->pluck('Key_name');
            
            if (!$existingIndexes->contains('course_members_course_id_user_id_index')) {
                $table->index(['course_id', 'user_id']);
            }
            if (!$existingIndexes->contains('course_members_user_id_role_index')) {
                $table->index(['user_id', 'role']);
            }
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $existingIndexes = collect(DB::select("SHOW INDEX FROM store_orders"))->pluck('Key_name');

            if (!$existingIndexes->contains('store_orders_academy_store_id_status_index')) {
                $table->index(['academy_store_id', 'status']);
            }
            if (!$existingIndexes->contains('store_orders_user_id_index') && !$existingIndexes->contains('store_orders_user_id_foreign')) {
                $table->index('user_id');
            }
            if (!$existingIndexes->contains('store_orders_order_number_index') && !$existingIndexes->contains('store_orders_order_number_unique')) {
                $table->index('order_number');
            }
        });

        Schema::table('course_quiz_results', function (Blueprint $table) {
            $existingIndexes = collect(DB::select("SHOW INDEX FROM course_quiz_results"))->pluck('Key_name');

            if (!$existingIndexes->contains('course_quiz_results_quiz_id_user_id_index')) {
                $table->index(['quiz_id', 'user_id']);
            }
            if (!$existingIndexes->contains('course_quiz_results_status_index')) {
                $table->index('status');
            }
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
