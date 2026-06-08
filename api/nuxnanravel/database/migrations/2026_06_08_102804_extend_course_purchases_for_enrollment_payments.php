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
        Schema::table('course_purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['marketplace', 'enrollment'])->default('marketplace')->after('id');
            $table->unsignedBigInteger('course_member_id')->nullable()->after('buyer_id');
            
            $table->foreign('course_member_id')
                ->references('id')
                ->on('course_members')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            $table->dropForeign(['course_member_id']);
            $table->dropColumn(['purchase_type', 'course_member_id']);
        });
    }
};
