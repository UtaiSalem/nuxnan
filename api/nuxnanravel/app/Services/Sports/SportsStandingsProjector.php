<?php

namespace App\Services\Sports;

use App\Models\SportsEdition;
use App\Models\SportsHouseStanding;
use App\Models\SportsScoreEntry;
use Illuminate\Support\Facades\DB;

class SportsStandingsProjector
{
    /**
     * Rebuild the standings for a sports edition.
     */
    public function rebuild(SportsEdition $edition): void
    {
        DB::transaction(function () use ($edition) {
            $houseGroupIds = $edition->houseGroupIds();

            // Fetch non-voided score entries for the edition
            $entries = SportsScoreEntry::where('edition_id', $edition->id)
                ->active()
                ->get();

            $standings = [];
            foreach ($houseGroupIds as $houseGroupId) {
                $standings[$houseGroupId] = [
                    'edition_id' => $edition->id,
                    'house_group_id' => $houseGroupId,
                    'total_points' => 0.0,
                    'gold_count' => 0,
                    'silver_count' => 0,
                    'bronze_count' => 0,
                    'rank' => null,
                    'computed_at' => now(),
                ];
            }

            foreach ($entries as $entry) {
                $houseId = $entry->house_group_id;
                if (! isset($standings[$houseId])) {
                    continue; // Safeguard if a score entry has a house not in edition
                }

                $standings[$houseId]['total_points'] += (float) $entry->points;

                if ($entry->source === 'placing') {
                    if ($entry->placing === 1) {
                        $standings[$houseId]['gold_count']++;
                    } elseif ($entry->placing === 2) {
                        $standings[$houseId]['silver_count']++;
                    } elseif ($entry->placing === 3) {
                        $standings[$houseId]['bronze_count']++;
                    }
                }
            }

            // Points are decimal(8,2) in the database but sum as floats here, so two houses
            // that tie can differ in the last bits and miss the equality check below.
            foreach ($standings as &$rounded) {
                $rounded['total_points'] = round($rounded['total_points'], 2);
            }
            unset($rounded);

            usort($standings, function ($a, $b) {
                // Return -1 if $a > $b (to sort descending)
                // If points are equal, keep original order (which doesn't matter since ranks will be same)
                if ($b['total_points'] == $a['total_points']) {
                    return 0;
                }

                return ($b['total_points'] > $a['total_points']) ? 1 : -1;
            });

            // Calculate ranks (standard competition ranking: 1, 1, 3, etc.)
            $currentRank = 1;
            $rankCounter = 1;
            $previousPoints = null;

            foreach ($standings as &$standing) {
                if ($previousPoints === null || $standing['total_points'] != $previousPoints) {
                    $currentRank = $rankCounter;
                }
                $standing['rank'] = $currentRank;
                $previousPoints = $standing['total_points'];
                $rankCounter++;
            }
            unset($standing);

            // A house dropped from the edition must not keep its row: this table is an
            // aggregate of the current house set, not a history of everyone who ever scored.
            // With no houses left that clears the edition entirely, which is the correct
            // empty standings rather than the previous house set frozen in place.
            SportsHouseStanding::where('edition_id', $edition->id)
                ->when($houseGroupIds !== [], fn ($query) => $query->whereNotIn('house_group_id', $houseGroupIds))
                ->delete();

            SportsHouseStanding::upsert(
                $standings,
                ['edition_id', 'house_group_id'],
                ['total_points', 'gold_count', 'silver_count', 'bronze_count', 'rank', 'computed_at']
            );
        });
    }
}
