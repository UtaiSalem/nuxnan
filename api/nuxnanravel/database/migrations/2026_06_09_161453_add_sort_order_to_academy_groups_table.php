<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('academy_id');
            $table->index(['academy_id', 'sort_order']);
        });

        // Backfill: ตั้ง sort_order ตาม created_at, id ภายใน academy_id เดียวกัน
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE academy_groups ag
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY academy_id ORDER BY created_at, id) AS rn
                    FROM academy_groups
                ) ranked ON ranked.id = ag.id
                SET ag.sort_order = ranked.rn
            ');
        } else {
            $groups = DB::table('academy_groups')->orderBy('academy_id')->orderBy('created_at')->orderBy('id')->get();
            $currentAcademyId = null;
            $order = 0;
            foreach ($groups as $group) {
                if ($group->academy_id !== $currentAcademyId) {
                    $currentAcademyId = $group->academy_id;
                    $order = 1;
                } else {
                    $order++;
                }
                DB::table('academy_groups')->where('id', $group->id)->update(['sort_order' => $order]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academy_groups', function (Blueprint $table) {
            $table->dropIndex(['academy_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
