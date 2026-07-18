<?php

namespace App\Http\Controllers\Api\Academies;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademyAllocation\AllocateToCourseRequest;
use App\Http\Resources\AcademyAllocation\AcademyAllocationResource;
use App\Models\Academy;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Services\AcademyAllocationService;
use DomainException;
use Illuminate\Http\Request;

class AcademyAllocationController extends Controller
{
    public function __construct(protected AcademyAllocationService $service) {}

    public function store(AllocateToCourseRequest $request, Academy $academy)
    {
        try {
            $course = Course::findOrFail($request->integer('course_id'));
            $result = $this->service->allocateToCourse($request->user(), $academy, $course, $request->integer('amount'), $request->input('purpose'), $request->header('Idempotency-Key'));

            return response()->json(['academy_tx' => new AcademyAllocationResource($result['academy_tx']), 'course_tx' => $result['course_tx']], 201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request, Academy $academy)
    {
        abort_unless($request->user()->id === $academy->user_id || $academy->isAdmin($request->user()), 403);

        return AcademyAllocationResource::collection(AcademyPointTransaction::where('academy_id', $academy->id)->where('type', AcademyPointTransaction::TYPE_ALLOCATION_OUT)->latest()->paginate());
    }
}
