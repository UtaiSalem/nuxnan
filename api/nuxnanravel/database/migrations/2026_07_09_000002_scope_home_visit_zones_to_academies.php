<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_visit_zones', function (Blueprint $table) {
            $table->foreignId('academy_id')
                ->nullable()
                ->after('id')
                ->constrained('academies')
                ->nullOnDelete();
        });

        DB::table('home_visit_zones')->orderBy('id')->each(function (object $zone): void {
            $academyIds = DB::table('student_home_visits')
                ->where('zone_id', $zone->id)
                ->whereNotNull('academy_id')
                ->distinct()
                ->orderBy('academy_id')
                ->pluck('academy_id');

            if ($academyIds->isEmpty()) {
                $academyIds = DB::table('academies')->orderBy('id')->pluck('id');
            }

            foreach ($academyIds as $index => $academyId) {
                if ($index === 0) {
                    DB::table('home_visit_zones')->where('id', $zone->id)->update(['academy_id' => $academyId]);

                    continue;
                }

                $cloneId = DB::table('home_visit_zones')->insertGetId([
                    'academy_id' => $academyId,
                    'zone_name' => $zone->zone_name,
                    'description' => $zone->description,
                    'zone_code' => 'ZONECLONE_'.$zone->id.'_ACADEMY_'.$academyId,
                    'color' => $zone->color,
                    'is_active' => $zone->is_active,
                    'display_order' => $zone->display_order,
                    'created_at' => $zone->created_at,
                    'updated_at' => $zone->updated_at,
                ]);

                DB::table('student_home_visits')
                    ->where('zone_id', $zone->id)
                    ->where('academy_id', $academyId)
                    ->update(['zone_id' => $cloneId]);
            }
        });
    }

    public function down(): void
    {
        $clones = DB::table('home_visit_zones')
            ->where('zone_code', 'like', 'ZONECLONE_%_ACADEMY_%')
            ->orderByDesc('id')
            ->get();

        foreach ($clones as $clone) {
            preg_match('/^ZONECLONE_(\d+)_ACADEMY_\d+$/', $clone->zone_code, $matches);
            $originalId = isset($matches[1]) ? (int) $matches[1] : null;

            if ($originalId) {
                DB::table('student_home_visits')->where('zone_id', $clone->id)->update(['zone_id' => $originalId]);
                DB::table('home_visit_zones')->where('id', $clone->id)->delete();
            }
        }

        Schema::table('home_visit_zones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academy_id');
        });
    }
};
