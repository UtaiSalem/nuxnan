<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Enums\StudentCardRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreStudentCardRequest;
use App\Http\Requests\RejectStudentCardRequest;
use App\Http\Requests\StoreStudentCardRequest;
use App\Http\Resources\ClassroomSummaryResource;
use App\Http\Resources\StudentCardRequestResource;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCardRequest;
use App\Services\StudentCardRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentCardRequestController extends Controller
{
    public function __construct(private readonly StudentCardRequestService $service) {}

    public function index(Request $request, Academy $academy)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'in:normal,urgent'],
            'classroom_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = StudentCardRequest::query()
            ->with(['requestedBy', 'classroom.homeroomTeacher:id,name,profile_photo_path', 'classroom.academicYear:id,name'])
            ->where('academy_id', $academy->id);
        $query->when($validated['status'] ?? null, fn ($q, $value) => $q->where('status', $value));
        $query->when($validated['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value));
        $query->when($validated['classroom_id'] ?? null, fn ($q, $value) => $q->where('classroom_id', $value));
        $query->when($validated['search'] ?? null, fn ($q, $value) => $q->where(function ($nested) use ($value) {
            $nested->where('full_name_snapshot', 'like', "%{$value}%")->orWhere('student_number_snapshot', 'like', "%{$value}%");
        }));

        return StudentCardRequestResource::collection($query->latest('requested_at')->paginate($validated['per_page'] ?? 20));
    }

    public function show(Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        $this->ensureAcademy($academy, $studentCardRequest);

        return new StudentCardRequestResource($studentCardRequest->load([
            'requestedBy', 'auditLogs', 'classroom.homeroomTeacher:id,name,profile_photo_path', 'classroom.academicYear:id,name',
        ]));
    }

    public function counts(Academy $academy)
    {
        return response()->json(['data' => StudentCardRequest::query()->where('academy_id', $academy->id)
            ->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status')]);
    }

    public function myClassrooms(Request $request, Academy $academy)
    {
        $classrooms = Classroom::query()
            ->with(['homeroomTeacher:id,name,profile_photo_path', 'academicYear:id,name'])
            ->withCount(['classroomStudents as student_count' => fn ($query) => $query->active()])
            ->where('academy_id', $academy->id)
            ->where('homeroom_teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->get();

        return response()->json(['data' => ClassroomSummaryResource::collection($classrooms)]);
    }

    public function classroomStudents(Request $request, Academy $academy, Classroom $classroom)
    {
        abort_unless((int) $classroom->academy_id === (int) $academy->id && (int) $classroom->homeroom_teacher_id === (int) $request->user()->id, 403);
        $classroom->load(['homeroomTeacher:id,name,profile_photo_path', 'academicYear:id,name'])
            ->loadCount(['classroomStudents as student_count' => fn ($query) => $query->active()]);
        $students = ClassroomStudent::query()->with(['student.studentCard', 'student.activeCardRequest'])
            ->where('academy_id', $academy->id)->where('classroom_id', $classroom->id)->active()->get();

        return response()->json(['data' => [
            'classroom' => new ClassroomSummaryResource($classroom),
            'students' => $students,
        ]]);
    }

    public function store(StoreStudentCardRequest $request, Academy $academy): StudentCardRequestResource
    {
        $student = Student::findOrFail($request->integer('student_id'));
        $this->ensureHomeroom($request, $academy, $request->integer('classroom_id'));

        return new StudentCardRequestResource($this->service->create($academy, $student, $request->user(), $request->validated()));
    }

    public function bulkStore(BulkStoreStudentCardRequest $request, Academy $academy)
    {
        $results = [];
        foreach ($request->validated('requests') as $item) {
            try {
                $this->ensureHomeroom($request, $academy, $item['classroom_id'] ?? null);
                $created = $this->service->create($academy, Student::findOrFail($item['student_id']), $request->user(), $item);
                $results[] = ['student_id' => $item['student_id'], 'success' => true, 'request_id' => $created->id];
            } catch (\Throwable $e) {
                $results[] = ['student_id' => $item['student_id'], 'success' => false, 'message' => $e instanceof ValidationException ? $e->getMessage() : 'Unable to create request.'];
            }
        }

        return response()->json(['data' => $results]);
    }

    public function approve(Request $request, Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        return $this->transition($request, $academy, $studentCardRequest, StudentCardRequestStatus::Approved);
    }

    public function reject(RejectStudentCardRequest $request, Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        return $this->transition($request, $academy, $studentCardRequest, StudentCardRequestStatus::Rejected);
    }

    public function start(Request $request, Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        return $this->transition($request, $academy, $studentCardRequest, StudentCardRequestStatus::InProgress);
    }

    public function cancel(Request $request, Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        $this->ensureAcademy($academy, $studentCardRequest);
        abort_unless((int) $studentCardRequest->requested_by === (int) $request->user()->id, 403);

        return new StudentCardRequestResource($this->service->transition($studentCardRequest, StudentCardRequestStatus::Cancelled, $request->user()));
    }

    public function complete(Request $request, Academy $academy, StudentCardRequest $studentCardRequest): StudentCardRequestResource
    {
        $this->ensureAcademy($academy, $studentCardRequest);
        $data = $request->validate(['card_issue_date' => ['nullable', 'date'], 'card_expiry_date' => ['nullable', 'date', 'after:card_issue_date'], 'admin_notes' => ['nullable', 'string', 'max:2000']]);

        return new StudentCardRequestResource($this->service->complete($studentCardRequest, $request->user(), $data));
    }

    public function bulkTransition(Request $request, Academy $academy)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
            'action' => ['required', 'in:approve,start'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $status = $data['action'] === 'approve' ? StudentCardRequestStatus::Approved : StudentCardRequestStatus::InProgress;
        $results = [];
        foreach ($data['ids'] as $id) {
            try {
                $cardRequest = StudentCardRequest::query()->where('academy_id', $academy->id)->findOrFail($id);
                $updated = $this->service->transition($cardRequest, $status, $request->user(), $data);
                $results[] = ['id' => $id, 'success' => true, 'status' => $updated->status->value];
            } catch (\Throwable $e) {
                $results[] = ['id' => $id, 'success' => false, 'message' => $e instanceof ValidationException ? $e->getMessage() : 'Unable to update request.'];
            }
        }

        return response()->json(['data' => $results]);
    }

    private function transition(Request $request, Academy $academy, StudentCardRequest $cardRequest, StudentCardRequestStatus $status): StudentCardRequestResource
    {
        $this->ensureAcademy($academy, $cardRequest);

        return new StudentCardRequestResource($this->service->transition($cardRequest, $status, $request->user(), $request->all()));
    }

    private function ensureAcademy(Academy $academy, StudentCardRequest $cardRequest): void
    {
        abort_unless((int) $cardRequest->academy_id === (int) $academy->id, 404);
    }

    private function ensureHomeroom(Request $request, Academy $academy, ?int $classroomId): void
    {
        abort_unless($classroomId && Classroom::query()->whereKey($classroomId)->where('academy_id', $academy->id)
            ->where('homeroom_teacher_id', $request->user()->id)->exists(), 403);
    }
}
