<?php

namespace App\Services\Sports;

use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SportsScoringService
{
    /**
     * Get points for a specific placing from a discipline's scoring table.
     */
    public function pointsForPlacing(SportsDiscipline $discipline, int $placing): float
    {
        $scoringTable = $discipline->scoring_table;
        if (! is_array($scoringTable)) {
            return 0.0;
        }

        $key = (string) $placing;
        if (isset($scoringTable[$key])) {
            return (float) $scoringTable[$key];
        }

        return 0.0;
    }

    /**
     * Award points to a house.
     */
    public function award(SportsEdition $edition, array $data, User $user): SportsScoreEntry
    {
        return DB::transaction(function () use ($edition, $data, $user) {
            $entry = new SportsScoreEntry;
            $entry->edition_id = $edition->id;
            $entry->academy_id = $edition->academy_id;
            $entry->house_group_id = $data['house_group_id'];
            $entry->source = $data['source'];
            $entry->awarded_by_user_id = $user->id;

            if (isset($data['note'])) {
                $entry->note = $data['note'];
            }

            if ($data['source'] === 'placing') {
                $entry->discipline_id = $data['discipline_id'];
                $entry->placing = $data['placing'];

                $discipline = SportsDiscipline::find($data['discipline_id']);
                $entry->points = $this->pointsForPlacing($discipline, $data['placing']);
            } elseif ($data['source'] === 'judged') {
                $entry->discipline_id = $data['discipline_id'];
                $entry->points = $data['points'];
            } elseif ($data['source'] === 'manual') {
                $entry->discipline_id = null;
                $entry->points = $data['points'];
            }

            $entry->save();

            $projector = app(SportsStandingsProjector::class);
            $projector->rebuild($edition);

            return $entry;
        });
    }

    /**
     * Void a score entry.
     */
    public function void(SportsScoreEntry $entry, User $user): SportsScoreEntry
    {
        if ($entry->voided_at !== null) {
            return $entry;
        }

        return DB::transaction(function () use ($entry, $user) {
            $entry->voided_at = now();
            $entry->voided_by_user_id = $user->id;
            $entry->save();

            $edition = SportsEdition::find($entry->edition_id);
            if ($edition) {
                $projector = app(SportsStandingsProjector::class);
                $projector->rebuild($edition);
            }

            return $entry;
        });
    }
}
