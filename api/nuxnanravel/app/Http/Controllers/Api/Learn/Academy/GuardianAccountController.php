<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Exceptions\GuardianAccountLinkException;
use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Guardian;
use App\Models\GuardianAccountRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademyGroupPermissionAccessService;
use App\Services\GuardianAccessService;
use App\Services\GuardianAccountLinkService;
use Illuminate\Http\Request;

class GuardianAccountController extends Controller
{
    public function __construct(
        private GuardianAccountLinkService $linkService,
        private GuardianAccessService $accessService,
        private AcademyGroupPermissionAccessService $groupPermissionAccess
    ) {}

    public function search(Academy $academy, Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:3|max:100',
        ]);
        $q = $validated['q'];

        $actor = $request->user() ?? auth()->user();

        if (! $actor->isSuperAdmin() && ! $academy->isAdmin($actor)) {
            $isMember = AcademyMember::where('academy_id', $academy->id)
                ->where('user_id', $actor->id)
                ->where('status', 2)
                ->exists();

            if (! $isMember) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
        }

        $targetUser = current(array_filter([
            User::where('username', $q)->first(),
            User::where('personal_code', $q)->first(),
            User::where('phone_number', $q)->first(),
        ]));

        if (! $targetUser) {
            return response()->json([
                'success' => true,
                'data' => null,
            ], 200);
        }

        $alreadyLinked = Guardian::where('academy_id', $academy->id)
            ->where('user_id', $targetUser->id)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'username' => $targetUser->username,
                'avatar' => $targetUser->avatar ?? null,
            ],
            'already_linked' => $alreadyLinked,
        ], 200);
    }

    public function studentSearch(Academy $academy, Request $request)
    {
        $studentCode = $request->query('student_code');
        $lastName = $request->query('last_name');

        if (! $studentCode || ! $lastName) {
            return response()->json(['success' => true, 'data' => null], 200);
        }

        $lastName = trim(preg_replace('/\s+/', ' ', $lastName));

        $student = Student::where('academy_id', $academy->id)
            ->where('student_id', $studentCode)
            ->first();

        if (! $student) {
            return response()->json(['success' => true, 'data' => null], 200);
        }

        $dbLastName = trim(preg_replace('/\s+/', ' ', $student->last_name_th));
        if ($dbLastName !== $lastName) {
            return response()->json(['success' => true, 'data' => null], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'full_name' => trim($student->title_prefix_th.' '.$student->first_name_th.' '.$student->last_name_th),
                'classroom' => $student->current_classroom,
            ],
        ], 200);
    }

    public function store(Academy $academy, Student $student, Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'guardian_id' => 'nullable|exists:guardians,id',
        ]);

        $actor = $request->user() ?? auth()->user();

        $isStudentUser = $student->user_id !== null && $student->user_id === $actor->id;
        $isTargetUser = $actor->id === (int) $validated['user_id'];
        $canAppoint = $this->accessService->allows($actor, $student, 'guardians.appoint');

        if (! $isStudentUser && ! $isTargetUser && ! $canAppoint) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $targetUser = User::findOrFail($validated['user_id']);
        $guardian = isset($validated['guardian_id']) ? Guardian::find($validated['guardian_id']) : null;

        try {
            $accountRequest = $this->linkService->createRequest($academy, $student, $targetUser, $actor, $guardian);

            return response()->json([
                'success' => true,
                'data' => $accountRequest,
            ], 201);
        } catch (GuardianAccountLinkException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->httpStatus());
        }
    }

    public function index(Academy $academy, Request $request)
    {
        $actor = $request->user() ?? auth()->user();
        $scope = $request->query('scope');

        if ($scope === 'academy') {
            $hasPermission = false;
            if ($actor->isSuperAdmin() || $academy->isAdmin($actor)) {
                $hasPermission = true;
            } else {
                $member = AcademyMember::where('academy_id', $academy->id)
                    ->where('user_id', $actor->id)
                    ->where('status', 2)
                    ->first();
                if ($member && $member->academyRole?->hasAnyPermission(['guardians.view'])) {
                    $hasPermission = true;
                } elseif ($this->groupPermissionAccess->hasAnyPermission($actor, $academy, ['guardians.view'])) {
                    $hasPermission = true;
                }
            }

            if (! $hasPermission) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            $query = GuardianAccountRequest::where('academy_id', $academy->id);
        } else {
            $query = GuardianAccountRequest::where('academy_id', $academy->id)
                ->where(function ($q) use ($actor) {
                    $q->where('user_id', $actor->id)
                        ->orWhere('initiated_by_user_id', $actor->id)
                        ->orWhereHas('student', function ($sq) use ($actor) {
                            $sq->where('user_id', $actor->id);
                        });
                });
        }

        $status = $request->query('status');
        if ($status && in_array($status, ['pending', 'accepted', 'declined', 'cancelled'])) {
            $query->where('status', $status);
        }

        $requests = $query->with(['student', 'user', 'guardian'])->get();

        $mapped = $requests->map(function ($req) use ($actor) {
            $isIncoming = false;
            if ($req->direction === GuardianAccountRequest::DIRECTION_GUARDIAN && $req->user_id === $actor->id) {
                $isIncoming = true;
            } elseif ($req->direction === GuardianAccountRequest::DIRECTION_STUDENT && $req->student && $req->student->user_id === $actor->id) {
                $isIncoming = true;
            }

            return [
                'id' => $req->id,
                'student_id' => $req->student_id,
                'student_name' => $req->student ? $req->student->full_name_th : null,
                'user_id' => $req->user_id,
                'user_name' => $req->user ? $req->user->name : null,
                'guardian_name' => $req->guardian ? trim($req->guardian->first_name.' '.$req->guardian->last_name) : null,
                'status' => $req->status,
                'direction' => $req->direction,
                'type' => $isIncoming ? 'incoming' : 'outgoing',
                'created_at' => $req->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'incoming' => $mapped->where('type', 'incoming')->values(),
            'outgoing' => $mapped->where('type', 'outgoing')->values(),
        ], 200);
    }

    public function accept(Academy $academy, GuardianAccountRequest $accountRequest, Request $request)
    {
        if ($accountRequest->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        try {
            $result = $this->linkService->accept($accountRequest, $request->user() ?? auth()->user());

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (GuardianAccountLinkException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }
    }

    public function decline(Academy $academy, GuardianAccountRequest $accountRequest, Request $request)
    {
        if ($accountRequest->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->linkService->decline($accountRequest, $request->user() ?? auth()->user(), $validated['reason'] ?? null);

            return response()->json(['success' => true], 200);
        } catch (GuardianAccountLinkException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }
    }

    public function cancel(Academy $academy, GuardianAccountRequest $accountRequest, Request $request)
    {
        if ($accountRequest->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        try {
            $this->linkService->cancel($accountRequest, $request->user() ?? auth()->user());

            return response()->json(['success' => true], 200);
        } catch (GuardianAccountLinkException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }
    }

    public function destroy(Academy $academy, Guardian $guardian, Request $request)
    {
        if ($guardian->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        try {
            $this->linkService->unlink($guardian, $request->user() ?? auth()->user());

            return response()->json(['success' => true], 200);
        } catch (GuardianAccountLinkException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->httpStatus());
        }
    }
}
