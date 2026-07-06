<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // สถานะเผยแพร่ (แยกจาก access_type)
            $table->enum('publication_status', ['draft', 'published', 'archived'])
                ->default('published')
                ->after('status');

            // รูปแบบการเข้าถึง
            $table->enum('access_type', ['free', 'points', 'money'])
                ->default('free')
                ->after('publication_status');

            // ราคาเงินบาท (money type)
            $table->decimal('money_tuition_fee', 10, 2)
                ->unsigned()
                ->nullable()
                ->after('point_tuition_fee');
        });

        // Migrate ข้อมูลเดิม: status='1' → published, status='0' → draft
        DB::statement("
            UPDATE lessons SET
                publication_status = CASE WHEN status = '1' THEN 'published' ELSE 'draft' END,
                access_type = CASE WHEN point_tuition_fee > 0 THEN 'points' ELSE 'free' END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['publication_status', 'access_type', 'money_tuition_fee']);
        });
    }
};
