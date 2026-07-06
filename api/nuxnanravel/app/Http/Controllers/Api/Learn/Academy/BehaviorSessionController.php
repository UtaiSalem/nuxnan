<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academy\Behavior\StoreBehaviorSessionRecordsRequest;
use App\Http\Requests\Academy\Behavior\StoreBehaviorSessionRequest;
use App\Models\Academy;
use App\Models\Learn\Academy\BehaviorSession;
use App\Models\Student;
use App\Services\BehaviorService;
use App\Traits\ManagesEventPermissions;
use Illuminate\Http\Request;

class BehaviorSessionController extends Controller
{
    use ManagesEventPermissions;

    public function __construct(private BehaviorService $behaviorService) {}

    public function index(Request $request, Academy $academy)
    {
        $query = BehaviorSession::where('academy_id', $academy->id)
            ->with(['classroom', 'creator'])
            ->withCount('records');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderByDesc('date')->paginate(20);

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    public function store(StoreBehaviorSessionRequest $request, Academy $academy)
    {
        $session = BehaviorSession::create(array_merge(
            $request->validated(),
            [
                'academy_id' => $academy->id,
                'created_by' => $request->user()->id,
                'date' => $request->date ?? now()->toDateString(),
                'status' => 'open',
            ]
        ));

        $session->load(['classroom', 'creator']);

        return response()->json(['success' => true, 'data' => $session, 'message' => 'เปิด Session สำเร็จ'], 201);
    }

    public function show(Academy $academy, BehaviorSession $session)
    {
        $session->load([
            'classroom',
            'creator',
            'records' => fn ($q) => $q->with(['student', 'category', 'recorder'])->orderByDesc('created_at'),
        ]);

        return response()->json(['success' => true, 'data' => $session]);
    }

    public function storeRecords(StoreBehaviorSessionRecordsRequest $request, Academy $academy, BehaviorSession $session)
    {
        if (! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session ปิดแล้ว'], 422);
        }

        $created = [];
        foreach ($request->records as $recordData) {
            $student = Student::find($recordData['student_id']);
            if (! $student) {
                continue;
            }

            $created[] = $this->behaviorService->recordBehavior(
                $academy,
                $student,
                array_merge($recordData, [
                    'session_id' => $session->id,
                    'recorded_by' => $request->user()->id,
                    'incident_date' => $session->date,
                ])
            );
        }

        return response()->json([
            'success' => true,
            'data' => ['count' => count($created)],
            'message' => 'บันทึก '.count($created).' รายการสำเร็จ',
        ], 201);
    }

    public function close(Academy $academy, BehaviorSession $session)
    {
        if (! $session->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Session ปิดไปแล้ว'], 422);
        }

        $session->update(['status' => 'closed', 'closed_at' => now()]);

        return response()->json(['success' => true, 'message' => 'ปิด Session สำเร็จ']);
    }
}
