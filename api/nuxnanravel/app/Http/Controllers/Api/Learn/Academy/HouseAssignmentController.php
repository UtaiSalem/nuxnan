<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\HouseAssignmentBatch;
use App\Services\Sports\HouseAssignmentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseAssignmentController extends Controller
{
    public function __construct(private readonly HouseAssignmentService $service) {}

    private function scoped(Academy $academy, HouseAssignmentBatch $batch): HouseAssignmentBatch
    {
        abort_unless((int) $batch->academy_id === (int) $academy->id, 404);

        return $batch;
    }

    public function index(Request $request, Academy $academy)
    {
        return response()->json(HouseAssignmentBatch::where('academy_id', $academy->id)->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function current(Request $request, Academy $academy)
    {
        $request->validate(['academic_year_id' => ['required', 'integer']]);
        $year = $request->integer('academic_year_id');
        abort_unless(DB::table('academic_years')->where('id', $year)->where('academy_id', $academy->id)->exists(), 422);

        return response()->json(['academic_year_id' => $year, 'counts' => DB::table('house_memberships')->where('academy_id', $academy->id)->where('academic_year_id', $year)->selectRaw('house_group_id, COUNT(*) as count')->groupBy('house_group_id')->pluck('count', 'house_group_id')]);
    }

    public function previewRandom(Request $request, Academy $academy)
    {
        $request->validate(['academic_year_id' => ['required', 'integer'], 'house_group_ids' => ['required', 'array', 'min:2'], 'house_group_ids.*' => ['integer'], 'strategy' => ['nullable', 'in:stratified,pure_random'], 'scope' => ['nullable', 'in:unassigned_only,all'], 'seed' => ['nullable', 'integer'], 'balance_gender' => ['nullable', 'boolean']]);
        try {
            $batch = $this->service->previewRandom($academy, $request->integer('academic_year_id'), $request->all(), $request->user());

            return response()->json(['batch' => $batch], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Academy $academy, HouseAssignmentBatch $batch)
    {
        return response()->json(['batch' => $this->scoped($academy, $batch)]);
    }

    public function rows(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        $batch = $this->scoped($academy, $batch);
        $q = $batch->rows()->with('student');
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        return response()->json($q->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function commit(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        try {
            $this->service->commit($this->scoped($academy, $batch), $request->user());

            return response()->json(['batch' => $batch->fresh()]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function undo(Request $request, Academy $academy, HouseAssignmentBatch $batch)
    {
        try {
            $this->service->undo($this->scoped($academy, $batch), $request->user());

            return response()->json(['batch' => $batch->fresh()]);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function discard(Academy $academy, HouseAssignmentBatch $batch)
    {
        $batch = $this->scoped($academy, $batch);
        abort_unless($batch->status === 'draft', 422);
        $batch->delete();

        return response()->noContent();
    }
}
