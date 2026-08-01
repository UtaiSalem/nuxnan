<?php

namespace App\Services\Sports;

use Illuminate\Support\Facades\DB;

/** The only writer of house-type academy_group_members. house_memberships is source of truth; count headcount there because students without accounts have no projected row. */
class HouseMembershipProjector
{
    public function rebuild(int $academyId, int $academicYearId): void
    {
        DB::transaction(function () use ($academyId, $academicYearId) {
            $ids = DB::table('academy_groups')->where('academy_id', $academyId)->where('type', 'house')->pluck('id');
            if ($ids->isEmpty()) {
                return;
            }
            DB::table('academy_group_members')->whereIn('academy_group_id', $ids)->delete();
            $rows = DB::table('house_memberships')->where('academic_year_id', $academicYearId)->whereIn('house_group_id', $ids)->whereNotNull('user_id')->get();
            $now = now();
            DB::table('academy_group_members')->insert($rows->map(fn ($row) => [
                'academy_group_id' => $row->house_group_id,
                'user_id' => $row->user_id,
                'role' => 'member',
                'status' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }
}
