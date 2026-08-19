<?php

namespace App\Services\Sports;

use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsMatch;
use Illuminate\Support\Facades\DB;

class SportsFixtureGenerator
{
    /**
     * @param  array<int,int>  $houseGroupIds  ลำดับใน array = ลำดับ seed (ตัวแรก = seed 1)
     * @param  array<string,mixed>  $options
     */
    public function generate(SportsEdition $edition, SportsDiscipline $discipline, string $format, array $houseGroupIds, array $options = [])
    {
        return DB::transaction(function () use ($edition, $discipline, $format, $houseGroupIds, $options) {
            // ล้างของเดิม
            SportsMatch::where('discipline_id', $discipline->id)->update(['next_match_id' => null, 'next_match_slot' => null]);
            SportsMatch::where('discipline_id', $discipline->id)->delete();

            if ($format === 'round_robin') {
                $this->generateRoundRobin($edition, $discipline, $houseGroupIds);
            } elseif ($format === 'knockout') {
                $this->generateKnockout($edition, $discipline, $houseGroupIds, $options);
            } elseif ($format === 'heats') {
                $this->generateHeats($edition, $discipline, $houseGroupIds, $options);
            }

            return SportsMatch::where('discipline_id', $discipline->id)
                ->with('participants')
                ->orderBy('round_order')
                ->orderBy('match_number')
                ->orderBy('id')
                ->get();
        });
    }

    private function generateRoundRobin(SportsEdition $edition, SportsDiscipline $discipline, array $houseGroupIds)
    {
        $list = array_values($houseGroupIds);
        if (count($list) % 2 === 1) {
            $list[] = null;               // bye
        }
        $n = count($list);
        $rounds = $n - 1;
        $half = intdiv($n, 2);

        for ($r = 0; $r < $rounds; $r++) {
            $matchNumber = 1;
            for ($i = 0; $i < $half; $i++) {
                $a = $list[$i];
                $b = $list[$n - 1 - $i];
                if ($a === null || $b === null) {
                    continue;             // คู่ที่เจอ bye = ไม่สร้างแมตช์
                }

                $match = SportsMatch::create([
                    'edition_id' => $edition->id,
                    'academy_id' => $edition->academy_id,
                    'discipline_id' => $discipline->id,
                    'activity_session_id' => null,
                    'round_label' => 'รอบที่ '.($r + 1),
                    'round_order' => $r + 1,
                    'match_number' => $matchNumber++,
                    'scheduled_at' => null,
                    'location' => null,
                    'status' => 'scheduled',
                    'winner_house_group_id' => null,
                    'next_match_id' => null,
                    'next_match_slot' => null,
                ]);

                $match->participants()->createMany([
                    ['house_group_id' => $a, 'slot' => 1],
                    ['house_group_id' => $b, 'slot' => 2],
                ]);
            }
            // หมุนวง: ล็อกตัวแรกไว้ ที่เหลือหมุน
            $fixed = array_shift($list);
            $last = array_pop($list);
            array_unshift($list, $last);
            array_unshift($list, $fixed);
        }
    }

    private function bracketOrder(int $size): array
    {
        $order = [1, 2];
        while (count($order) < $size) {
            $next = [];
            $len = count($order) * 2 + 1;
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $len - $seed;
            }
            $order = $next;
        }

        return $order;
    }

    private function generateKnockout(SportsEdition $edition, SportsDiscipline $discipline, array $houseGroupIds, array $options)
    {
        $n = count($houseGroupIds);
        $size = 2;
        while ($size < $n) {
            $size *= 2;
        }
        $rounds = (int) log($size, 2);

        // seed i (1-based) -> $houseGroupIds[$i - 1] ถ้า $i <= $n มิฉะนั้น = bye (null)
        $seeds = [];
        for ($i = 1; $i <= $size; $i++) {
            $seeds[$i] = ($i <= $n) ? $houseGroupIds[$i - 1] : null;
        }

        $byRound = [];

        for ($r = $rounds; $r >= 1; $r--) {
            $matchCount = $size / (2 ** $r);
            $byRound[$r] = [];

            for ($index = 0; $index < $matchCount; $index++) {
                if ($r === $rounds) {
                    $roundLabel = 'รอบชิงชนะเลิศ';
                } elseif ($r === $rounds - 1) {
                    $roundLabel = 'รอบรองชนะเลิศ';
                } elseif ($r === $rounds - 2) {
                    $roundLabel = 'รอบก่อนรองชนะเลิศ';
                } else {
                    $roundLabel = 'รอบที่ '.$r;
                }

                $nextMatchId = null;
                $nextMatchSlot = null;
                if ($r < $rounds) {
                    $nextMatchId = $byRound[$r + 1][intdiv($index, 2)];
                    $nextMatchSlot = ($index % 2) + 1;
                }

                $match = SportsMatch::create([
                    'edition_id' => $edition->id,
                    'academy_id' => $edition->academy_id,
                    'discipline_id' => $discipline->id,
                    'activity_session_id' => null,
                    'round_label' => $roundLabel,
                    'round_order' => $r,
                    'match_number' => $index + 1,
                    'scheduled_at' => null,
                    'location' => null,
                    'status' => 'scheduled',
                    'winner_house_group_id' => null,
                    'next_match_id' => $nextMatchId,
                    'next_match_slot' => $nextMatchSlot,
                ]);

                $byRound[$r][$index] = $match->id;
            }
        }

        $bracketOrder = $this->bracketOrder($size);
        $roundOneMatches = SportsMatch::where('discipline_id', $discipline->id)
            ->where('round_order', 1)
            ->orderBy('match_number')
            ->get();

        foreach ($roundOneMatches as $index => $match) {
            $seed1 = $bracketOrder[$index * 2];
            $seed2 = $bracketOrder[$index * 2 + 1];
            $a = $seeds[$seed1];
            $b = $seeds[$seed2];

            if ($a !== null && $b !== null) {
                // ทั้งสองฝั่งเป็นคณะจริง
                $match->participants()->createMany([
                    ['house_group_id' => $a, 'slot' => 1],
                    ['house_group_id' => $b, 'slot' => 2],
                ]);
            } elseif ($a !== null || $b !== null) {
                // ฝั่งใดฝั่งหนึ่งเป็น bye
                $realHouse = $a !== null ? $a : $b;
                $nextMatchId = $match->next_match_id;
                $nextMatchSlot = $match->next_match_slot;

                if ($nextMatchId) {
                    DB::table('sports_match_participants')->insert([
                        'match_id' => $nextMatchId,
                        'house_group_id' => $realHouse,
                        'slot' => $nextMatchSlot,
                        'status' => 'ok',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $match->delete();
            } else {
                // ทั้งสองฝั่งเป็น bye
                $match->delete();
            }
        }

        if (($options['third_place'] ?? false) === true && $rounds >= 2) {
            SportsMatch::create([
                'edition_id' => $edition->id,
                'academy_id' => $edition->academy_id,
                'discipline_id' => $discipline->id,
                'activity_session_id' => null,
                'round_label' => 'ชิงอันดับที่ 3',
                'round_order' => $rounds,
                'match_number' => 2,
                'scheduled_at' => null,
                'location' => null,
                'status' => 'scheduled',
                'winner_house_group_id' => null,
                'next_match_id' => null,
                'next_match_slot' => null,
            ]);
        }
    }

    private function generateHeats(SportsEdition $edition, SportsDiscipline $discipline, array $houseGroupIds, array $options)
    {
        $lanes = (int) ($options['lanes_per_heat'] ?? 8);
        $lanes = max(2, min(20, $lanes));
        $heatCount = (int) ceil(count($houseGroupIds) / $lanes);

        $heats = [];
        foreach ($houseGroupIds as $i => $houseId) {
            $heatIndex = $i % $heatCount;
            if (! isset($heats[$heatIndex])) {
                $heats[$heatIndex] = [];
            }
            $heats[$heatIndex][] = $houseId;
        }

        foreach ($heats as $index => $houses) {
            $match = SportsMatch::create([
                'edition_id' => $edition->id,
                'academy_id' => $edition->academy_id,
                'discipline_id' => $discipline->id,
                'activity_session_id' => null,
                'round_label' => 'ฮีตที่ '.($index + 1),
                'round_order' => 1,
                'match_number' => $index + 1,
                'scheduled_at' => null,
                'location' => null,
                'status' => 'scheduled',
                'winner_house_group_id' => null,
                'next_match_id' => null,
                'next_match_slot' => null,
            ]);

            $participants = [];
            foreach ($houses as $slotIndex => $houseId) {
                $participants[] = ['house_group_id' => $houseId, 'slot' => $slotIndex + 1];
            }
            $match->participants()->createMany($participants);
        }

        if ($heatCount > 1) {
            SportsMatch::create([
                'edition_id' => $edition->id,
                'academy_id' => $edition->academy_id,
                'discipline_id' => $discipline->id,
                'activity_session_id' => null,
                'round_label' => 'รอบชิงชนะเลิศ',
                'round_order' => 2,
                'match_number' => 1,
                'scheduled_at' => null,
                'location' => null,
                'status' => 'scheduled',
                'winner_house_group_id' => null,
                'next_match_id' => null,
                'next_match_slot' => null,
            ]);
        }
    }
}
