<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Play\ActivityResource;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;

class AcademyActivityController extends Controller
{
    public function index(Academy $academy, Request $request)
    {
        $user = $request->user();
        $isAcademyAdmin = $user ? $academy->isAdmin($user) : false;
        $userId = $user ? $user->id : null;
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');

        $scope = $this->validateAndAuthorizeScope($request, $academy, $user);

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
            ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType, $scope) {
                $query->where('academy_id', $academy->id);

                // Apply scope filter
                if ($scope['scope_type'] === 'academy') {
                    $query->where('scope_type', 'academy');
                    if ($scope['scope_id']) {
                        $query->where(function ($q) use ($scope) {
                            $q->where('scope_id', $scope['scope_id'])->orWhereNull('scope_id');
                        });
                    }
                } else {
                    $query->where('scope_type', $scope['scope_type'])
                        ->where('scope_id', $scope['scope_id']);
                }

                // กรองตามประเภทของโพสต์ (เช่น announcement, event)
                if ($filterType && $filterType !== 'all') {
                    $query->where('post_type', $filterType);
                }

                // กรองตามประเภทกลุ่มย่อย
                if ($groupType) {
                    $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                        $g->where('type', $groupType);
                    });
                }

                // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
                if ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->whereNull('posted_as_group_id')
                            ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                                $sub->select('academy_group_id')
                                    ->from('user_muted_groups')
                                    ->where('user_id', $userId);
                            });
                    });
                }
            })
            ->latest()
            ->paginate();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'isAcademyAdmin' => $isAcademyAdmin,
            'activities' => ActivityResource::collection($activities),
        ]);
    }

    public function getActivities(Academy $academy, Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $filterType = $request->input('filter_type');
        $groupType = $request->input('group_type');

        $scope = $this->validateAndAuthorizeScope($request, $academy, $user);

        $activities = Activity::with([
            'user',
            'activityable.user',
            'activityable.academy',
            'activityable.images',
            'activityable.postedAsGroup',
            'activityable.comments' => function ($cq) {
                $cq->with('user')->latest()->limit(3);
            },
        ])
            ->whereHasMorph('activityable', [AcademyPost::class], function ($query) use ($academy, $userId, $filterType, $groupType, $scope) {
                $query->where('academy_id', $academy->id);

                // Apply scope filter
                if ($scope['scope_type'] === 'academy') {
                    $query->where('scope_type', 'academy');
                    if ($scope['scope_id']) {
                        $query->where(function ($q) use ($scope) {
                            $q->where('scope_id', $scope['scope_id'])->orWhereNull('scope_id');
                        });
                    }
                } else {
                    $query->where('scope_type', $scope['scope_type'])
                        ->where('scope_id', $scope['scope_id']);
                }

                // กรองตามประเภทของโพสต์
                if ($filterType && $filterType !== 'all') {
                    $query->where('post_type', $filterType);
                }

                // กรองตามประเภทกลุ่มย่อย
                if ($groupType) {
                    $query->whereHas('postedAsGroup', function ($g) use ($groupType) {
                        $g->where('type', $groupType);
                    });
                }

                // ซ่อนโพสต์จากกลุ่มที่กด Mute ไว้
                if ($userId) {
                    $query->where(function ($q) use ($userId) {
                        $q->whereNull('posted_as_group_id')
                            ->orWhereNotIn('posted_as_group_id', function ($sub) use ($userId) {
                                $sub->select('academy_group_id')
                                    ->from('user_muted_groups')
                                    ->where('user_id', $userId);
                            });
                    });
                }
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'activities' => [
                'data' => ActivityResource::collection($activities->items()),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'next_page_url' => $activities->nextPageUrl(),
                'prev_page_url' => $activities->previousPageUrl(),
            ],
        ]);
    }

    private function validateAndAuthorizeScope(Request $request, Academy $academy, $user)
    {
        $scopeType = $request->input('scope_type', 'academy');
        $scopeId = $request->input('scope_id');

        if ($scopeType === 'department') {
            if (! $scopeId) {
                abort(response()->json(['success' => false, 'message' => 'scope_id is required for department scope'], 422));
            }
            $group = AcademyGroup::where('id', $scopeId)
                ->where('academy_id', $academy->id)
                ->where('type', 'department')
                ->first();
            if (! $group) {
                abort(404);
            }
            $isAdmin = $user && $academy->isAdmin($user);
            if (! $isAdmin) {
                $isMember = $user && \DB::table('academy_group_members')
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

            return ['scope_type' => 'department', 'scope_id' => $group->id];
        } elseif ($scopeType === 'classroom') {
            if (! $scopeId) {
                abort(response()->json(['success' => false, 'message' => 'scope_id is required for classroom scope'], 422));
            }
            $classroom = Classroom::where('id', $scopeId)
                ->where('academy_id', $academy->id)
                ->first();
            if (! $classroom) {
                abort(404);
            }
            $isAdmin = $user && $academy->isAdmin($user);
            if (! $isAdmin) {
                $isHomeroomTeacher = $user && $classroom->homeroom_teacher_id === $user->id;
                $isStudent = false;
                if ($user) {
                    $student = Student::where('user_id', $user->id)
                        ->where('academy_id', $academy->id)
                        ->where('status', 'active')
                        ->first();
                    if ($student) {
                        $isStudent = \DB::table('classroom_students')
                            ->where('classroom_id', $classroom->id)
                            ->where('student_id', $student->id)
                            ->where('status', 'active')
                            ->exists();
                    }
                }
                if (! $isHomeroomTeacher && ! $isStudent) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าถึงห้องเรียนนี้',
                    ], 403));
                }
            }

            return ['scope_type' => 'classroom', 'scope_id' => $classroom->id];
        } else {
            return ['scope_type' => 'academy', 'scope_id' => $scopeId];
        }
    }
}
