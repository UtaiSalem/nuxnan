<?php

namespace App\Services\Sports;

use App\Models\SportsEdition;
use Illuminate\Support\Facades\DB;

/** The only writer of house-type academy_group_members. house_memberships is source of truth; count headcount there because students without accounts have no projected row. */
class HouseMembershipProjector
{
    public function rebuild(SportsEdition $edition): void
    {
        DB::transaction(function () use ($edition) {
            $ids = $edition->houseGroupIds();

            // The clear-out happens before the empty check, never after it. Activating an
            // edition that has no houses yet still has to leave the projection empty —
            // returning early first would strand the previous edition's members here,
            // published under a house set that is no longer the live one.
            DB::table('academy_group_members')->whereIn('academy_group_id', DB::table('academy_groups')->where('academy_id', $edition->academy_id)->where('type', 'house')->pluck('id'))->delete();
            if (empty($ids)) {
                return;
            }
            $rows = DB::table('house_memberships')->where('edition_id', $edition->id)->whereIn('house_group_id', $ids)->whereNotNull('user_id')->get();
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
