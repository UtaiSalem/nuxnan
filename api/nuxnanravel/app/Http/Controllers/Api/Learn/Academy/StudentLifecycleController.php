<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Enrollment\DropStudentRequest;
use App\Http\Requests\Academy\Enrollment\GraduateStudentRequest;
use App\Http\Requests\Academy\Enrollment\PromoteStudentRequest;
use App\Http\Requests\Academy\Enrollment\RepeatStudentRequest;
use App\Http\Requests\Academy\Enrollment\TransferStudentRequest;
use App\Http\Resources\Learn\Academy\Enrollment\ClassroomStudentResource;
use App\Http\Resources\Learn\Academy\Enrollment\StudentSummaryResource;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class StudentLifecycleController extends Controller
{
    public function __construct(
        private readonly StudentEnrollmentService $enrollmentService
    ) {}

    public function graduate(GraduateStudentRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $closedEnrollment = $this->enrollmentService->graduateStudent(
            $student,
            null,
            $request->input('reason', 'จบการศึกษา'),
            null,
            $request->user()->id,
        );

        return $this->successResponse($request, $student->fresh(), $closedEnrollment, null);
    }

    public function drop(DropStudentRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $closedEnrollment = $this->enrollmentService->dropStudent(
            $student,
            null,
            $request->input('reason'),
            null,
            $request->user()->id,
        );

        return $this->successResponse($request, $student->fresh(), $closedEnrollment, null);
    }

    public function repeat(RepeatStudentRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $currentEnrollment = $student->currentEnrollment()->first();
        $newClassroom = Classroom::findOrFail($request->integer('new_classroom_id'));

        try {
            $newEnrollment = $this->enrollmentService->repeatStudent(
                $student,
                $newClassroom,
                $request->integer('student_number'),
                $request->input('reason', 'ซ้ำชั้น'),
                null,
                $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->invalidResponse('invalid_repeat', $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $student->fresh(),
            $currentEnrollment?->fresh(),
            $newEnrollment->fresh()
        );
    }

    public function promote(PromoteStudentRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $fromClassroom = Classroom::findOrFail($request->integer('from_classroom_id'));
        $toClassroom = Classroom::findOrFail($request->integer('to_classroom_id'));
        $closedEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $fromClassroom->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->first();

        try {
            $newEnrollment = $this->enrollmentService->promoteStudent(
                $student,
                $fromClassroom,
                $toClassroom,
                $request->input('reason', 'เลื่อนชั้น'),
                $request->integer('student_number'),
                null,
                $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->invalidResponse('invalid_promote', $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $student->fresh(),
            $closedEnrollment?->fresh(),
            $newEnrollment->fresh()
        );
    }

    public function transfer(TransferStudentRequest $request, Academy $academy, Student $student): JsonResponse
    {
        $fromClassroom = Classroom::findOrFail($request->integer('from_classroom_id'));
        $toClassroom = Classroom::findOrFail($request->integer('to_classroom_id'));
        $closedEnrollment = ClassroomStudent::query()
            ->where('student_id', $student->id)
            ->where('classroom_id', $fromClassroom->id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->first();

        try {
            $newEnrollment = $this->enrollmentService->transferStudent(
                $student,
                $fromClassroom,
                $toClassroom,
                $request->input('reason', 'ย้ายห้อง'),
                null,
                $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->invalidResponse('invalid_transfer', $exception->getMessage());
        }

        return $this->successResponse(
            $request,
            $student->fresh(),
            $closedEnrollment?->fresh(),
            $newEnrollment->fresh()
        );
    }

    public function history(Request $request, Academy $academy, Student $student): JsonResponse
    {
        abort_unless(Gate::allows('enrollment.lifecycle', [$academy, $student]), 403);

        $history = $this->enrollmentService
            ->getStudentHistory($student)
            ->load(['classroom', 'createdBy', 'student']);

        return response()->json([
            'success' => true,
            'data' => $history->map(
                fn (ClassroomStudent $row) => (new ClassroomStudentResource($row))->resolve($request)
            )->values()->all(),
        ]);
    }

    private function successResponse(
        Request $request,
        Student $student,
        ?ClassroomStudent $closedEnrollment,
        ?ClassroomStudent $newEnrollment
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'closed_enrollment' => $closedEnrollment
                ? (new ClassroomStudentResource($closedEnrollment->loadMissing(['classroom', 'createdBy', 'student'])))->resolve($request)
                : null,
            'new_enrollment' => $newEnrollment
                ? (new ClassroomStudentResource($newEnrollment->loadMissing(['classroom', 'createdBy', 'student'])))->resolve($request)
                : null,
            'student' => (new StudentSummaryResource($student))->resolve($request),
        ]);
    }

    private function invalidResponse(string $error, string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message,
        ], 422);
    }
}
