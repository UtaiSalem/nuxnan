<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsMatch;
use App\Models\SportsMatchParticipant;
use App\Services\Sports\SportsFixtureGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SportsMatchController extends Controller
{
    public function index(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $query = SportsMatch::where('edition_id', $edition->id)->with('participants');

        if ($request->has('discipline_id')) {
            $query->where('discipline_id', $request->query('discipline_id'));
        }

        $matches = $query->orderBy('round_order')
            ->orderBy('match_number')
            ->orderBy('id')
            ->get();

        return response()->json($matches);
    }

    public function store(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $validated = $request->validate([
            'discipline_id' => ['required', Rule::exists('sports_disciplines', 'id')->where('edition_id', $edition->id)],
            'round_label' => 'nullable|string|max:60',
            'round_order' => 'nullable|integer|min:0',
            'match_number' => 'nullable|integer|min:1',
            'scheduled_at' => 'nullable|date',
            'location' => 'nullable|string|max:150',
            'note' => 'nullable|string|max:255',
            'participants' => 'nullable|array',
            'participants.*.house_group_id' => 'required|integer',
            'participants.*.slot' => 'nullable|integer|min:1|max:255',
        ]);

        $houseGroupIds = $edition->houseGroupIds();
        $participantsData = $validated['participants'] ?? [];

        $seenHouses = [];
        foreach ($participantsData as $p) {
            $houseId = (int) $p['house_group_id'];
            if (! in_array($houseId, array_map('intval', $houseGroupIds), true)) {
                abort(422, 'House is not part of this edition.');
            }
            if (in_array($houseId, $seenHouses, true)) {
                abort(422, 'Duplicate house in one match.');
            }
            $seenHouses[] = $houseId;
        }

        $match = DB::transaction(function () use ($edition, $validated, $participantsData) {
            $match = new SportsMatch;
            $match->edition_id = $edition->id;
            $match->academy_id = $edition->academy_id;
            $match->discipline_id = $validated['discipline_id'];
            $match->round_label = $validated['round_label'] ?? null;
            $match->round_order = $validated['round_order'] ?? 0;
            $match->match_number = $validated['match_number'] ?? 1;
            $match->scheduled_at = $validated['scheduled_at'] ?? null;
            $match->location = $validated['location'] ?? null;
            $match->note = $validated['note'] ?? null;
            $match->save();

            $slotCounter = 1;
            foreach ($participantsData as $p) {
                $match->participants()->create([
                    'house_group_id' => $p['house_group_id'],
                    'slot' => $p['slot'] ?? $slotCounter++,
                ]);
            }

            return $match;
        });

        $match->load('participants');

        return response()->json($match, 201);
    }

    public function update(Request $request, Academy $academy, SportsEdition $edition, SportsMatch $match)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $match->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'round_label' => 'sometimes|nullable|string|max:60',
            'round_order' => 'sometimes|nullable|integer|min:0',
            'match_number' => 'sometimes|nullable|integer|min:1',
            'scheduled_at' => 'sometimes|nullable|date',
            'location' => 'sometimes|nullable|string|max:150',
            'note' => 'sometimes|nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['scheduled', 'in_progress', 'finished', 'cancelled'])],
        ]);

        $match->update($validated);
        $match->load('participants');

        return response()->json($match);
    }

    public function destroy(Academy $academy, SportsEdition $edition, SportsMatch $match)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $match->edition_id === (int) $edition->id, 404);

        $match->delete();

        return response()->noContent();
    }

    public function recordResult(Request $request, Academy $academy, SportsEdition $edition, SportsMatch $match)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $match->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*.house_group_id' => 'required|integer',
            'participants.*.slot' => 'nullable|integer|min:1|max:255',
            'participants.*.score' => 'nullable|numeric',
            'participants.*.time_ms' => 'nullable|integer|min:0',
            'participants.*.placing' => 'nullable|integer|min:1',
            'participants.*.status' => ['nullable', Rule::in(['ok', 'dq', 'dns', 'dnf'])],
            'status' => ['sometimes', Rule::in(['scheduled', 'in_progress', 'finished', 'cancelled'])],
        ]);

        $houseGroupIds = $edition->houseGroupIds();
        $participantsData = $validated['participants'];

        $seenHouses = [];
        foreach ($participantsData as $p) {
            $houseId = (int) $p['house_group_id'];
            if (! in_array($houseId, array_map('intval', $houseGroupIds), true)) {
                abort(422, 'House is not part of this edition.');
            }
            if (in_array($houseId, $seenHouses, true)) {
                abort(422, 'Duplicate house in one match.');
            }
            $seenHouses[] = $houseId;
        }

        DB::transaction(function () use ($match, $validated, $participantsData) {
            foreach ($participantsData as $p) {
                $attributes = [];
                if (array_key_exists('slot', $p)) {
                    $attributes['slot'] = $p['slot'];
                }
                if (array_key_exists('score', $p)) {
                    $attributes['score'] = $p['score'];
                }
                if (array_key_exists('time_ms', $p)) {
                    $attributes['time_ms'] = $p['time_ms'];
                }
                if (array_key_exists('placing', $p)) {
                    $attributes['placing'] = $p['placing'];
                }
                if (array_key_exists('status', $p)) {
                    $attributes['status'] = $p['status'];
                }

                SportsMatchParticipant::updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'house_group_id' => $p['house_group_id'],
                    ],
                    $attributes
                );
            }

            $match->status = $validated['status'] ?? 'finished';

            $firstPlaceHouses = [];
            foreach ($participantsData as $p) {
                if (isset($p['placing']) && (int) $p['placing'] === 1) {
                    $firstPlaceHouses[] = $p['house_group_id'];
                }
            }

            $winnerHouseId = count($firstPlaceHouses) === 1 ? $firstPlaceHouses[0] : null;
            $match->winner_house_group_id = $winnerHouseId;
            $match->save();

            if ($match->next_match_id && $winnerHouseId) {
                $nextSlot = $match->next_match_slot ?? 1;

                SportsMatchParticipant::where('match_id', $match->next_match_id)
                    ->where('slot', $nextSlot)
                    ->where('house_group_id', '!=', $winnerHouseId)
                    ->delete();

                SportsMatchParticipant::updateOrCreate(
                    [
                        'match_id' => $match->next_match_id,
                        'house_group_id' => $winnerHouseId,
                    ],
                    ['slot' => $nextSlot]
                );
            }
        });

        $match->load('participants');

        return response()->json($match);
    }

    public function generateFixtures(
        Request $request,
        Academy $academy,
        SportsEdition $edition,
        SportsDiscipline $discipline,
        SportsFixtureGenerator $generator
    ) {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $discipline->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'format' => ['nullable', Rule::in(['none', 'knockout', 'round_robin', 'heats'])],
            'house_group_ids' => 'required|array|min:2',
            'house_group_ids.*' => 'required|integer',
            'options' => 'nullable|array',
            'options.third_place' => 'nullable|boolean',
            'options.lanes_per_heat' => 'nullable|integer|min:2|max:20',
        ]);

        $format = $validated['format'] ?? $discipline->format;
        if ($format === 'none') {
            abort(422, 'This discipline has no fixture format.');
        }

        $houseGroupIds = $validated['house_group_ids'];
        $uniqueHouseGroupIds = array_unique($houseGroupIds);
        if (count($houseGroupIds) !== count($uniqueHouseGroupIds)) {
            abort(422, 'Duplicate house in the fixture request.');
        }

        $editionHouseGroupIds = $edition->houseGroupIds();
        $editionHouseGroupIdsInt = array_map('intval', $editionHouseGroupIds);
        foreach ($houseGroupIds as $houseId) {
            if (! in_array((int) $houseId, $editionHouseGroupIdsInt, true)) {
                abort(422, 'House is not part of this edition.');
            }
        }

        $hasNotScheduledMatches = SportsMatch::where('discipline_id', $discipline->id)
            ->where('status', '!=', 'scheduled')
            ->exists();
        if ($hasNotScheduledMatches) {
            abort(422, 'This discipline already has matches that are not scheduled.');
        }

        if (isset($validated['format']) && $validated['format'] !== $discipline->format) {
            $discipline->update(['format' => $validated['format']]);
        }

        $options = $validated['options'] ?? [];
        $matches = $generator->generate($edition, $discipline, $format, $houseGroupIds, $options);

        return response()->json($matches, 201);
    }
}
