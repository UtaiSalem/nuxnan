<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyScopeFile;
use App\Models\AcademyScopeTask;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademyScopeWorkspaceController extends Controller
{
    private function validateAndAuthorizeScope(Request $request, Academy $academy): array
    {
        $scope = $request->validate([
            'scope_type' => 'required|in:department,classroom,academy',
            'scope_id' => 'required|integer',
        ]);

        $user = $request->user();

        // 1. Check if the scope target actually belongs to this academy
        if ($scope['scope_type'] === 'department') {
            $group = AcademyGroup::where('id', $scope['scope_id'])
                ->where('academy_id', $academy->id)
                ->where('type', 'department')
                ->first();

            if (! $group) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'ไม่พบฝ่ายงานที่ระบุในสถาบันนี้',
                ], 404));
            }

            // 2. Authorize user access
            if (! $academy->isAdmin($user)) {
                // Check if user is a member of the department
                $isMember = DB::table('academy_group_members')
                    ->where('academy_group_id', $group->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $isMember) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าถึงฝ่ายงานนี้',
                    ], 403));
                }
            }
        } elseif ($scope['scope_type'] === 'classroom') {
            $classroom = Classroom::where('id', $scope['scope_id'])
                ->where('academy_id', $academy->id)
                ->first();

            if (! $classroom) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'ไม่พบห้องเรียนที่ระบุในสถาบันนี้',
                ], 404));
            }

            // 2. Authorize user access
            if (! $academy->isAdmin($user)) {
                // Check if homeroom teacher
                $isHomeroomTeacher = $classroom->homeroom_teacher_id === $user->id;

                // Check if student in the classroom
                $isStudent = DB::table('classroom_students')
                    ->join('students', 'classroom_students.student_id', '=', 'students.id')
                    ->where('classroom_students.classroom_id', $classroom->id)
                    ->where('students.user_id', $user->id)
                    ->exists();

                if (! $isHomeroomTeacher && ! $isStudent) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าถึงห้องเรียนนี้',
                    ], 403));
                }
            }
        } else { // academy scope
            if ((int) $scope['scope_id'] !== (int) $academy->id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'scope_id ต้องตรงกับ id ของโรงเรียน',
                ], 422));
            }

            // Check if user is a member of the academy
            if (! $academy->isAdmin($user)) {
                $isMember = $academy->members()->where('user_id', $user->id)->exists();
                if (! $isMember) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าถึงสถาบันนี้',
                    ], 403));
                }
            }
        }

        return $scope;
    }

    public function tasks(Request $request, Academy $academy)
    {
        $scope = $this->validateAndAuthorizeScope($request, $academy);

        return response()->json([
            'success' => true,
            'data' => AcademyScopeTask::where('academy_id', $academy->id)
                ->where($scope)
                ->latest()
                ->get(),
        ]);
    }

    public function storeTask(Request $request, Academy $academy)
    {
        $scope = $this->validateAndAuthorizeScope($request, $academy);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,done,cancelled',
            'priority' => 'nullable|in:low,normal,high',
            'due_at' => 'nullable|date',
        ]);

        return response()->json([
            'success' => true,
            'data' => AcademyScopeTask::create($scope + [
                'academy_id' => $academy->id,
                'created_by' => $request->user()->id,
            ] + $data),
        ], 201);
    }

    public function files(Request $request, Academy $academy)
    {
        $scope = $this->validateAndAuthorizeScope($request, $academy);

        return response()->json([
            'success' => true,
            'data' => AcademyScopeFile::where('academy_id', $academy->id)
                ->where($scope)
                ->latest()
                ->get(),
        ]);
    }

    public function uploadFile(Request $request, Academy $academy)
    {
        $scope = $this->validateAndAuthorizeScope($request, $academy);
        $request->validate(['file' => 'required|file|max:20480']);
        $file = $request->file('file');
        $path = $file->store("academies/{$academy->id}/scopes/{$scope['scope_type']}/{$scope['scope_id']}", 'local');

        return response()->json([
            'success' => true,
            'data' => AcademyScopeFile::create($scope + [
                'academy_id' => $academy->id,
                'uploaded_by' => $request->user()->id,
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]),
        ], 201);
    }
}
