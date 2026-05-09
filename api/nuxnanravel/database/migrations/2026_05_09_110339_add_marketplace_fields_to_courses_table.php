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
        Schema::table('courses', function (Blueprint $blueprint) {
            $blueprint->boolean('is_for_marketplace')->default(false)->after('saleable');
            $blueprint->integer('price_points')->nullable()->after('price');
            $blueprint->enum('price_type', ['free', 'points', 'wallet', 'both'])->default('free')->after('price_points');
            $blueprint->unsignedInteger('total_sales')->default(0)->after('price_type');
            $blueprint->unsignedBigInteger('source_course_id')->nullable()->after('total_sales');
            $blueprint->foreign('source_course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['source_course_id']);
            $blueprint->dropColumn([
                'is_for_marketplace',
                'price_points',
                'price_type',
                'total_sales',
                'source_course_id',
            ]);
        });
    }
};
