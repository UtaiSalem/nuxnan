<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\SportsDiscipline;
use App\Models\SportsEdition;
use App\Models\SportsHouseStanding;
use App\Models\SportsScoreEntry;
use App\Services\Sports\SportsScoringService;
use App\Services\Sports\SportsStandingsProjector;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SportsScoringController extends Controller
{
    public function disciplines(Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $disciplines = SportsDiscipline::where('edition_id', $edition->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return response()->json($disciplines);
    }

    public function storeDiscipline(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'type' => ['required', Rule::in(['team', 'individual', 'judged'])],
            'scoring_table' => 'nullable|array',
            'max_score' => 'nullable|numeric',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $discipline = new SportsDiscipline($validated);
        $discipline->edition_id = $edition->id;
        $discipline->academy_id = $edition->academy_id;
        $discipline->save();

        return response()->json($discipline, 201);
    }

    public function updateDiscipline(Request $request, Academy $academy, SportsEdition $edition, SportsDiscipline $discipline)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $discipline->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => ['sometimes', 'required', Rule::in(['team', 'individual', 'judged'])],
            'scoring_table' => 'nullable|array',
            'max_score' => 'nullable|numeric',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $discipline->update($validated);

        return response()->json($discipline);
    }

    public function destroyDiscipline(Academy $academy, SportsEdition $edition, SportsDiscipline $discipline)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $discipline->edition_id === (int) $edition->id, 404);

        $discipline->delete();

        return response()->noContent();
    }

    public function entries(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $query = SportsScoreEntry::where('edition_id', $edition->id)->with('discipline');

        if ($request->has('house_group_id')) {
            $query->where('house_group_id', $request->query('house_group_id'));
        }

        if ($request->has('discipline_id')) {
            $query->where('discipline_id', $request->query('discipline_id'));
        }

        $entries = $query->orderByDesc('id')->get();

        return response()->json($entries);
    }

    public function storeEntry(Request $request, Academy $academy, SportsEdition $edition, SportsScoringService $service)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $validated = $request->validate([
            'house_group_id' => 'required|integer',
            'source' => ['required', Rule::in(['placing', 'judged', 'manual'])],
            'note' => 'nullable|string|max:255',
        ]);

        $houseGroupIds = $edition->houseGroupIds();
        if (! in_array((int) $validated['house_group_id'], array_map('intval', $houseGroupIds), true)) {
            abort(422, 'House is not part of this edition.');
        }

        if ($validated['source'] === 'placing') {
            $extra = $request->validate([
                'discipline_id' => [
                    'required',
                    Rule::exists('sports_disciplines', 'id')->where('edition_id', $edition->id),
                ],
                'placing' => 'required|integer|min:1',
            ]);
            $validated = array_merge($validated, $extra);
        } elseif ($validated['source'] === 'judged') {
            $extra = $request->validate([
                'discipline_id' => [
                    'required',
                    Rule::exists('sports_disciplines', 'id')->where('edition_id', $edition->id),
                ],
                'points' => 'required|numeric',
            ]);
            $validated = array_merge($validated, $extra);
        } elseif ($validated['source'] === 'manual') {
            $extra = $request->validate([
                'points' => 'required|numeric',
            ]);
            if ($request->filled('discipline_id')) {
                abort(422, 'discipline_id must be null for manual source.');
            }
            $validated = array_merge($validated, $extra);
            $validated['discipline_id'] = null;
        }

        $entry = $service->award($edition, $validated, $request->user());

        return response()->json($entry, 201);
    }

    public function voidEntry(Academy $academy, SportsEdition $edition, SportsScoreEntry $entry, SportsScoringService $service, Request $request)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);
        abort_unless((int) $entry->edition_id === (int) $edition->id, 404);

        $entry = $service->void($entry, $request->user());

        return response()->json($entry);
    }

    public function standings(Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $standings = SportsHouseStanding::with('houseGroup')
            ->where('edition_id', $edition->id)
            ->orderBy('rank')
            ->orderBy('id')
            ->get();

        return response()->json($standings);
    }

    public function rebuildStandings(Academy $academy, SportsEdition $edition, SportsStandingsProjector $projector)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $projector->rebuild($edition);

        $standings = SportsHouseStanding::with('houseGroup')
            ->where('edition_id', $edition->id)
            ->orderBy('rank')
            ->orderBy('id')
            ->get();

        return response()->json($standings);
    }
}
