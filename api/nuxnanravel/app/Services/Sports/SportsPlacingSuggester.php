<?php

namespace App\Services\Sports;

use App\Models\SportsDiscipline;
use App\Models\SportsDisciplineResult;
use App\Models\SportsEdition;
use App\Models\SportsMatch;
use App\Models\SportsScoreEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SportsPlacingSuggester
{
    /**
     * @return array<int, array{house_group_id:int, placing:int, reason:string}>
     */
    public function suggest(SportsDiscipline $discipline): array
    {
        $format = $discipline->format;

        if ($format === 'none') {
            return [];
        }

        if ($format === 'knockout') {
            $maxRound = SportsMatch::where('discipline_id', $discipline->id)->max('round_order');
            if ($maxRound === null) {
                return [];
            }

            $final = SportsMatch::where('discipline_id', $discipline->id)
                ->where('round_order', $maxRound)
                ->where('match_number', 1)
                ->first();

            if (! $final || $final->status !== 'finished' || ! $final->winner_house_group_id) {
                return [];
            }

            $results = [];
            $winnerId = $final->winner_house_group_id;

            $results[] = [
                'house_group_id' => $winnerId,
                'placing' => 1,
                'reason' => 'ชนะรอบชิงชนะเลิศ',
            ];

            $loser = $final->participants->firstWhere('house_group_id', '!=', $winnerId);
            if ($loser) {
                $results[] = [
                    'house_group_id' => $loser->house_group_id,
                    'placing' => 2,
                    'reason' => 'แพ้รอบชิงชนะเลิศ',
                ];
            }

            $thirdPlaceMatch = SportsMatch::where('discipline_id', $discipline->id)
                ->where('round_order', $maxRound)
                ->where('match_number', 2)
                ->first();

            if ($thirdPlaceMatch) {
                if ($thirdPlaceMatch->status === 'finished' && $thirdPlaceMatch->winner_house_group_id) {
                    $results[] = [
                        'house_group_id' => $thirdPlaceMatch->winner_house_group_id,
                        'placing' => 3,
                        'reason' => 'ชนะคู่ชิงอันดับที่ 3',
                    ];
                }
            } else {
                $semiFinals = SportsMatch::where('discipline_id', $discipline->id)
                    ->where('round_order', $maxRound - 1)
                    ->get();

                $allFinished = $semiFinals->count() > 0 && $semiFinals->every(fn ($m) => $m->status === 'finished');

                if ($allFinished) {
                    foreach ($semiFinals as $semi) {
                        if ($semi->winner_house_group_id) {
                            $loserSemi = $semi->participants->firstWhere('house_group_id', '!=', $semi->winner_house_group_id);
                            if ($loserSemi) {
                                $results[] = [
                                    'house_group_id' => $loserSemi->house_group_id,
                                    'placing' => 3,
                                    'reason' => 'แพ้รอบรองชนะเลิศ (อันดับร่วม)',
                                ];
                            }
                        }
                    }
                }
            }

            usort($results, fn ($a, $b) => $a['placing'] === $b['placing'] ? $a['house_group_id'] <=> $b['house_group_id'] : $a['placing'] <=> $b['placing']);

            return $results;
        }

        if ($format === 'round_robin') {
            $matches = SportsMatch::where('discipline_id', $discipline->id)
                ->where('status', 'finished')
                ->with('participants')
                ->get();

            $stats = [];

            foreach ($matches as $match) {
                if ($match->participants->count() !== 2) {
                    continue;
                }

                $p1 = $match->participants[0];
                $p2 = $match->participants[1];

                $h1 = $p1->house_group_id;
                $h2 = $p2->house_group_id;

                if (! isset($stats[$h1])) {
                    $stats[$h1] = ['w' => 0, 'd' => 0, 'l' => 0, 'points' => 0, 'scored' => 0, 'conceded' => 0];
                }
                if (! isset($stats[$h2])) {
                    $stats[$h2] = ['w' => 0, 'd' => 0, 'l' => 0, 'points' => 0, 'scored' => 0, 'conceded' => 0];
                }

                $s1 = $p1->score !== null ? (float) $p1->score : 0;
                $s2 = $p2->score !== null ? (float) $p2->score : 0;

                $stats[$h1]['scored'] += $s1;
                $stats[$h1]['conceded'] += $s2;
                $stats[$h2]['scored'] += $s2;
                $stats[$h2]['conceded'] += $s1;

                if ($p1->score !== null && $p2->score !== null) {
                    if ($p1->score > $p2->score) {
                        $stats[$h1]['w']++;
                        $stats[$h1]['points'] += 3;
                        $stats[$h2]['l']++;
                    } elseif ($p1->score < $p2->score) {
                        $stats[$h2]['w']++;
                        $stats[$h2]['points'] += 3;
                        $stats[$h1]['l']++;
                    } else {
                        $stats[$h1]['d']++;
                        $stats[$h1]['points'] += 1;
                        $stats[$h2]['d']++;
                        $stats[$h2]['points'] += 1;
                    }
                } elseif ($match->winner_house_group_id !== null) {
                    if ($match->winner_house_group_id == $h1) {
                        $stats[$h1]['w']++;
                        $stats[$h1]['points'] += 3;
                        $stats[$h2]['l']++;
                    } else {
                        $stats[$h2]['w']++;
                        $stats[$h2]['points'] += 3;
                        $stats[$h1]['l']++;
                    }
                } else {
                    $stats[$h1]['d']++;
                    $stats[$h1]['points'] += 1;
                    $stats[$h2]['d']++;
                    $stats[$h2]['points'] += 1;
                }
            }

            if (empty($stats)) {
                return [];
            }

            $houses = [];
            foreach ($stats as $id => $s) {
                $houses[] = [
                    'house_group_id' => $id,
                    'points' => $s['points'],
                    'diff' => $s['scored'] - $s['conceded'],
                    'scored' => $s['scored'],
                    'w' => $s['w'],
                    'd' => $s['d'],
                    'l' => $s['l'],
                ];
            }

            usort($houses, function ($a, $b) {
                if ($a['points'] !== $b['points']) {
                    return $b['points'] <=> $a['points'];
                }
                if ($a['diff'] !== $b['diff']) {
                    return $b['diff'] <=> $a['diff'];
                }

                return $b['scored'] <=> $a['scored'];
            });

            $results = [];
            $currentPlacing = 1;

            for ($i = 0; $i < count($houses); $i++) {
                if ($i > 0) {
                    $prev = $houses[$i - 1];
                    $curr = $houses[$i];
                    if ($prev['points'] == $curr['points'] && $prev['diff'] == $curr['diff'] && $prev['scored'] == $curr['scored']) {
                        // same placing
                    } else {
                        $currentPlacing = $i + 1;
                    }
                }
                $h = $houses[$i];
                $results[] = [
                    'house_group_id' => $h['house_group_id'],
                    'placing' => $currentPlacing,
                    'reason' => "ชนะ {$h['w']} เสมอ {$h['d']} แพ้ {$h['l']} · {$h['points']} แต้ม",
                ];
            }

            usort($results, fn ($a, $b) => $a['placing'] === $b['placing'] ? $a['house_group_id'] <=> $b['house_group_id'] : $a['placing'] <=> $b['placing']);

            return $results;
        }

        if ($format === 'heats') {
            $maxRound = SportsMatch::where('discipline_id', $discipline->id)->max('round_order');
            if ($maxRound === null) {
                return [];
            }

            $matches = SportsMatch::where('discipline_id', $discipline->id)
                ->where('round_order', $maxRound)
                ->where('status', 'finished')
                ->with('participants')
                ->get();

            $participants = collect();
            foreach ($matches as $match) {
                foreach ($match->participants as $p) {
                    if (in_array($p->status, ['dq', 'dns', 'dnf']) || $p->time_ms === null) {
                        continue;
                    }
                    $participants->push($p);
                }
            }

            $participants = $participants->sortBy('time_ms')->values();

            $results = [];
            $currentPlacing = 1;

            for ($i = 0; $i < count($participants); $i++) {
                if ($i > 0) {
                    if ($participants[$i - 1]->time_ms == $participants[$i]->time_ms) {
                        // same placing
                    } else {
                        $currentPlacing = $i + 1;
                    }
                }

                $results[] = [
                    'house_group_id' => $participants[$i]->house_group_id,
                    'placing' => $currentPlacing,
                    'reason' => "เวลา {$participants[$i]->time_ms} มิลลิวินาที",
                ];
            }

            usort($results, fn ($a, $b) => $a['placing'] === $b['placing'] ? $a['house_group_id'] <=> $b['house_group_id'] : $a['placing'] <=> $b['placing']);

            return $results;
        }

        return [];
    }

    /**
     * @param  array<int, array{house_group_id:int, placing:int}>  $placings
     * @return Collection<int, SportsDisciplineResult>
     */
    public function confirm(SportsEdition $edition, SportsDiscipline $discipline, array $placings, string $source, User $user)
    {
        return DB::transaction(function () use ($edition, $discipline, $placings, $source, $user) {
            $oldEntries = SportsScoreEntry::where('discipline_id', $discipline->id)
                ->where('source', 'placing')
                ->active()
                ->get();

            foreach ($oldEntries as $old) {
                app(SportsScoringService::class)->void($old, $user);
            }

            $houseIdsInPayload = array_column($placings, 'house_group_id');

            $finalResults = collect();

            foreach ($placings as $row) {
                $result = SportsDisciplineResult::updateOrCreate(
                    ['discipline_id' => $discipline->id, 'house_group_id' => $row['house_group_id']],
                    [
                        'edition_id' => $edition->id,
                        'placing' => $row['placing'],
                        'source' => $source,
                        'score_entry_id' => null,
                        'confirmed_at' => now(),
                        'confirmed_by_user_id' => $user->id,
                    ]
                );

                $finalResults->push($result);
            }

            SportsDisciplineResult::where('discipline_id', $discipline->id)
                ->whereNotIn('house_group_id', $houseIdsInPayload)
                ->delete();

            foreach ($finalResults as $result) {
                $entry = app(SportsScoringService::class)->award($edition, [
                    'house_group_id' => $result->house_group_id,
                    'source' => 'placing',
                    'discipline_id' => $discipline->id,
                    'placing' => $result->placing,
                    'ref_type' => 'sports_discipline_results',
                    'ref_id' => $result->id,
                ], $user);

                $result->update(['score_entry_id' => $entry->id]);
            }

            return SportsDisciplineResult::where('discipline_id', $discipline->id)
                ->orderBy('placing')
                ->orderBy('house_group_id')
                ->with('scoreEntry')
                ->get();
        });
    }
}
