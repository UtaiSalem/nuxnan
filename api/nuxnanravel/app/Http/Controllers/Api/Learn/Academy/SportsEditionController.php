<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\SportsEdition;
use App\Models\SportsEditionHouse;
use App\Services\Sports\HouseMembershipProjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportsEditionController extends Controller
{
    public function index(Academy $academy)
    {
        $editions = SportsEdition::with('houses')->where('academy_id', $academy->id)->latest()->get();

        $counts = DB::table('house_memberships')
            ->where('academy_id', $academy->id)
            ->selectRaw('edition_id, count(*) as count')
            ->groupBy('edition_id')
            ->pluck('count', 'edition_id');

        $editions->each(function ($edition) use ($counts) {
            $edition->students_count = $counts->get($edition->id, 0);
        });

        return response()->json($editions);
    }

    public function store(Request $request, Academy $academy)
    {
        $request->validate([
            'academic_year_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'school_event_id' => ['nullable', 'integer'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'house_group_ids' => ['nullable', 'array'],
            'house_group_ids.*' => ['integer'],
        ]);

        abort_unless(DB::table('academic_years')->where('id', $request->input('academic_year_id'))->where('academy_id', $academy->id)->exists(), 422, 'Academic year does not belong to this academy.');

        $houseIds = $request->input('house_group_ids') ?? [];
        if (! empty($houseIds)) {
            $validHouses = DB::table('academy_groups')
                ->where('academy_id', $academy->id)
                ->where('type', 'house')
                ->whereIn('id', $houseIds)
                ->count();
            abort_unless($validHouses === count(array_unique($houseIds)), 422, 'Invalid house groups.');
        }

        $sequence = $request->input('sequence');
        if ($sequence === null) {
            $max = SportsEdition::where('academy_id', $academy->id)
                ->where('academic_year_id', $request->input('academic_year_id'))
                ->max('sequence');
            $sequence = $max ? $max + 1 : 1;
        }

        $edition = DB::transaction(function () use ($request, $academy, $sequence, $houseIds) {
            $edition = SportsEdition::create([
                'academy_id' => $academy->id,
                'academic_year_id' => $request->input('academic_year_id'),
                'school_event_id' => $request->input('school_event_id'),
                'name' => $request->input('name'),
                'sequence' => $sequence,
                'status' => 'draft',
                'starts_on' => $request->input('starts_on'),
                'ends_on' => $request->input('ends_on'),
                'created_by_user_id' => $request->user()->id,
            ]);

            foreach ($houseIds as $i => $id) {
                SportsEditionHouse::create([
                    'edition_id' => $edition->id,
                    'house_group_id' => $id,
                    'display_order' => $i,
                ]);
            }

            return $edition;
        });

        return response()->json($edition->load('houses'), 201);
    }

    public function update(Request $request, Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $request->validate([
            'academic_year_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'school_event_id' => ['nullable', 'integer'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'house_group_ids' => ['nullable', 'array'],
            'house_group_ids.*' => ['integer'],
        ]);

        abort_unless(DB::table('academic_years')->where('id', $request->input('academic_year_id'))->where('academy_id', $academy->id)->exists(), 422, 'Academic year does not belong to this academy.');

        $houseIds = $request->input('house_group_ids');
        if ($houseIds !== null) {
            $hasMembers = DB::table('house_memberships')->where('edition_id', $edition->id)->exists();
            abort_if($hasMembers, 422, 'Cannot change houses when memberships exist.');

            $validHouses = DB::table('academy_groups')
                ->where('academy_id', $academy->id)
                ->where('type', 'house')
                ->whereIn('id', $houseIds)
                ->count();
            abort_unless($validHouses === count(array_unique($houseIds)), 422, 'Invalid house groups.');
        }

        DB::transaction(function () use ($request, $edition, $houseIds) {
            $edition->update([
                'academic_year_id' => $request->input('academic_year_id'),
                'school_event_id' => $request->input('school_event_id'),
                'name' => $request->input('name'),
                'sequence' => $request->input('sequence') ?? $edition->sequence,
                'starts_on' => $request->input('starts_on'),
                'ends_on' => $request->input('ends_on'),
            ]);

            if ($houseIds !== null) {
                SportsEditionHouse::where('edition_id', $edition->id)->delete();
                foreach ($houseIds as $i => $id) {
                    SportsEditionHouse::create([
                        'edition_id' => $edition->id,
                        'house_group_id' => $id,
                        'display_order' => $i,
                    ]);
                }
            }
        });

        return response()->json($edition->load('houses'));
    }

    public function activate(Academy $academy, SportsEdition $edition, HouseMembershipProjector $projector)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        DB::transaction(function () use ($academy, $edition, $projector) {
            SportsEdition::where('academy_id', $academy->id)->where('status', 'active')->where('id', '!=', $edition->id)->update(['status' => 'closed']);
            $edition->update(['status' => 'active']);
            $projector->rebuild($edition);
        });

        return response()->json($edition->fresh()->load('houses'));
    }

    public function close(Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $edition->update(['status' => 'closed']);

        return response()->json($edition->fresh()->load('houses'));
    }

    public function destroy(Academy $academy, SportsEdition $edition)
    {
        abort_unless((int) $edition->academy_id === (int) $academy->id, 404);

        $hasMemberships = DB::table('house_memberships')->where('edition_id', $edition->id)->exists();
        abort_if($hasMemberships, 422, 'Cannot delete an edition with memberships.');

        $hasBatches = DB::table('house_assignment_batches')->where('edition_id', $edition->id)->exists();
        abort_if($hasBatches, 422, 'Cannot delete an edition with assignment batches.');

        $edition->delete();

        return response()->noContent();
    }
}
