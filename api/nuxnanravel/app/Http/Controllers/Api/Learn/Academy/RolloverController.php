<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Exceptions\RolloverNotUndoable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Rollover\CommitRolloverRequest;
use App\Http\Requests\Academy\Rollover\PlanRolloverRequest;
use App\Http\Requests\Academy\Rollover\PreviewRolloverRequest;
use App\Http\Requests\Academy\Rollover\UndoRolloverRequest;
use App\Http\Resources\Learn\Academy\Enrollment\RolloverBatchResource;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\RolloverBatch;
use App\Services\AcademicYearRolloverService;
use App\Services\Rollover\RolloverPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class RolloverController extends Controller
{
    public function __construct(
        private readonly AcademicYearRolloverService $rollover,
    ) {}

    public function preview(PreviewRolloverRequest $req, Academy $academy): JsonResponse
    {
        $from = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('from_year_id'));
        $to = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('to_year_id'));

        $preview = $this->rollover->previewRollover($academy, $from, $to);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    public function plan(PlanRolloverRequest $req, Academy $academy): JsonResponse
    {
        $from = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('from_year_id'));
        $to = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail($req->integer('to_year_id'));

        $plan = $this->rollover->planRollover(
            $academy, $from, $to, $req->input('mapping')
        );

        $planId = (string) Str::uuid();
        Cache::put(
            "rollover_plan:{$planId}:user:{$req->user()->id}",
            $plan->toArray(),
            900 // 15 min
        );

        return response()->json([
            'success' => true,
            'plan_id' => $planId,
            'expires_in_seconds' => 900,
            'summary' => $plan->summary,
            'warnings' => $plan->warnings,
            'entries_count' => count($plan->entries),
        ]);
    }

    public function index(Request $req, Academy $academy): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.viewBatches', $academy), 403);

        $query = RolloverBatch::where('academy_id', $academy->id)
            ->with(['academy', 'fromAcademicYear:id,name', 'toAcademicYear:id,name', 'committedBy:id,name', 'undoneBy:id,name']);

        if ($req->filled('status')) {
            $query->where('status', $req->input('status'));
        }

        $batches = $query->latest('committed_at')
            ->paginate(min($req->integer('per_page', 20), 100));

        return RolloverBatchResource::collection($batches)->response();
    }

    public function show(Academy $academy, RolloverBatch $batch): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.viewBatches', $academy), 403);
        // scopeBindings already enforces $batch->academy_id === $academy->id

        $batch->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy']);

        return response()->json([
            'success' => true,
            'batch' => new RolloverBatchResource($batch),
        ]);
    }

    public function commit(CommitRolloverRequest $req, Academy $academy): JsonResponse
    {
        $planId = $req->string('plan_id')->toString();
        $cacheKey = "rollover_plan:{$planId}:user:{$req->user()->id}";
        $cachedPlan = Cache::get($cacheKey);

        if (! is_array($cachedPlan)) {
            return response()->json([
                'success' => false,
                'error' => 'plan_expired',
                'message' => 'Plan expired or not found. Please re-run the plan step.',
            ], 410);
        }

        if ((int) ($cachedPlan['academy_id'] ?? 0) !== $academy->id) {
            return response()->json([
                'success' => false,
                'error' => 'academy_mismatch',
                'message' => 'Cached plan belongs to a different academy.',
            ], 422);
        }

        $expectedConfirmText = AcademicYear::where('academy_id', $academy->id)
            ->findOrFail((int) $cachedPlan['to_academic_year_id'])
            ->name;

        if ($req->string('confirm_text')->toString() !== $expectedConfirmText) {
            return response()->json([
                'success' => false,
                'error' => 'confirm_text_mismatch',
                'message' => 'Confirmation text must exactly match the destination academic year name.',
                'expected_format' => $expectedConfirmText,
            ], 422);
        }

        $batch = $this->rollover->commitRollover(
            RolloverPlan::fromArray($cachedPlan),
            $req->user()
        );

        Cache::forget($cacheKey);

        $batch->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy']);

        return response()->json([
            'success' => true,
            'batch' => new RolloverBatchResource($batch),
        ], 201);
    }

    public function undo(UndoRolloverRequest $req, Academy $academy, RolloverBatch $batch): JsonResponse
    {
        try {
            $batch = $this->rollover->undoRollover($batch->id, $req->user());
        } catch (RolloverNotUndoable $e) {
            return response()->json([
                'success' => false,
                'error' => 'cannot_undo',
                'message' => $e->getMessage(),
            ], 409);
        }

        $batch->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy']);

        return response()->json([
            'success' => true,
            'batch' => new RolloverBatchResource($batch),
        ]);
    }

    public function closeUndo(Request $req, Academy $academy, RolloverBatch $batch): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.undo', [$academy, $batch]), 403);

        $this->rollover->closeUndoWindow($batch->id, $req->user());

        $batch = $batch->fresh()->load(['academy', 'fromAcademicYear', 'toAcademicYear', 'committedBy', 'undoneBy']);

        return response()->json([
            'success' => true,
            'batch' => new RolloverBatchResource($batch),
        ]);
    }
}
