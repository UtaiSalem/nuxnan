<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\AcademyGroup;
use App\Models\AnnouncementRead;
use App\Models\Classroom;
use App\Models\SchoolAnnouncement;
use App\Models\Student;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * List announcements for an academy
     */
    public function index(Request $request, Academy $academy)
    {
        $user = $request->user();

        $query = SchoolAnnouncement::where('academy_id', $academy->id)
            ->with(['creator:id,name,profile_photo_path,email_verified_at']);

        // Scope validation and authorization
        $scopeType = $request->get('scope_type', 'academy');
        $scopeId = $request->get('scope_id');

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
            if (! $this->isAcademyAdmin($user, $academy)) {
                $isMember = \DB::table('academy_group_members')
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
            $query->where('scope_type', 'department')->where('scope_id', $group->id);
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
            if (! $this->isAcademyAdmin($user, $academy)) {
                $isHomeroomTeacher = $classroom->homeroom_teacher_id === $user->id;
                $isStudent = false;
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
                if (! $isHomeroomTeacher && ! $isStudent) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'คุณไม่มีสิทธิ์เข้าถึงห้องเรียนนี้',
                    ], 403));
                }
            }
            $query->where('scope_type', 'classroom')->where('scope_id', $classroom->id);
        } else {
            // Default: academy scope (match scope_type = 'academy' with either academy ID or NULL)
            $query->where('scope_type', 'academy');
            if ($scopeId) {
                $query->where(function ($q) use ($scopeId) {
                    $q->where('scope_id', $scopeId)->orWhereNull('scope_id');
                });
            }
        }

        // For non-admins, only show published and non-expired announcements
        if (! $this->isAcademyAdmin($user, $academy)) {
            $query->where('is_published', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('announcement_type', $request->type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Order by pinned first, then by date
        $announcements = $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        // Mark read status for current user
        $readIds = AnnouncementRead::where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id')
            ->toArray();

        $announcements->getCollection()->transform(function ($announcement) use ($readIds) {
            $announcement->is_read = in_array($announcement->id, $readIds);

            return $announcement;
        });

        return response()->json([
            'success' => true,
            'data' => $announcements->items(),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'total' => $announcements->total(),
                'per_page' => $announcements->perPage(),
                'last_page' => $announcements->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single announcement
     */
    public function show(Request $request, Academy $academy, SchoolAnnouncement $announcement)
    {
        if ($announcement->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $user = $request->user();

        // Check access for unpublished announcements
        if (! $announcement->is_published && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        // Increment view count
        $announcement->increment('view_count');

        // Mark as read
        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        $announcement->load('creator:id,name,profile_photo_path,email_verified_at');
        $announcement->is_read = true;
        $announcement->read_count = $announcement->reads()->count();

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ]);
    }

    /**
     * Create a new announcement
     */
    public function store(Request $request, Academy $academy)
    {
        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'announcement_type' => 'in:general,academic,financial,event,emergency',
            'priority' => 'in:low,normal,high,urgent',
            'target_audience' => 'nullable|array',
            'target_audience.roles' => 'nullable|array',
            'target_audience.grades' => 'nullable|array',
            'target_audience.departments' => 'nullable|array',
            'attachments' => 'nullable|array',
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'scope_type' => 'nullable|in:academy,department,classroom',
            'scope_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $announcement = SchoolAnnouncement::create([
                'academy_id' => $academy->id,
                'scope_type' => $request->get('scope_type', 'academy'),
                'scope_id' => $request->get('scope_id', $academy->id),
                'created_by' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'announcement_type' => $request->get('announcement_type', 'general'),
                'priority' => $request->get('priority', 'normal'),
                'target_audience' => $request->target_audience,
                'attachments' => $request->attachments,
                'is_pinned' => $request->get('is_pinned', false),
                'is_published' => $request->get('is_published', false),
                'published_at' => $request->get('is_published', false) ? now() : null,
                'expires_at' => $request->expires_at,
            ]);

            $this->auditLog->log(
                'announcement.created',
                $announcement,
                null,
                $announcement->toArray(),
                'announcements',
                ['academy_id' => $academy->id]
            );

            DB::commit();

            $announcement->load('creator:id,name,profile_photo_path,email_verified_at');

            return response()->json([
                'success' => true,
                'message' => 'Announcement created successfully',
                'data' => $announcement,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create announcement: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an announcement
     */
    public function update(Request $request, Academy $academy, SchoolAnnouncement $announcement)
    {
        if ($announcement->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'content' => 'string',
            'announcement_type' => 'in:general,academic,financial,event,emergency',
            'priority' => 'in:low,normal,high,urgent',
            'target_audience' => 'nullable|array',
            'attachments' => 'nullable|array',
            'is_pinned' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldData = $announcement->toArray();

            $announcement->update($request->only([
                'title', 'content', 'announcement_type', 'priority',
                'target_audience', 'attachments', 'is_pinned', 'expires_at',
            ]));

            $this->auditLog->log(
                'announcement.updated',
                $announcement,
                $oldData,
                $announcement->fresh()->toArray(),
                'announcements',
                ['academy_id' => $academy->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Announcement updated successfully',
                'data' => $announcement->fresh()->load('creator:id,name,profile_photo_path,email_verified_at'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update announcement: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish an announcement
     */
    public function publish(Request $request, Academy $academy, SchoolAnnouncement $announcement)
    {
        if ($announcement->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $announcement->publish();

        $this->auditLog->log(
            'announcement.published',
            $announcement,
            ['is_published' => false],
            ['is_published' => true],
            'announcements',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully',
            'data' => $announcement,
        ]);
    }

    /**
     * Unpublish an announcement
     */
    public function unpublish(Request $request, Academy $academy, SchoolAnnouncement $announcement)
    {
        if ($announcement->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $announcement->unpublish();

        $this->auditLog->log(
            'announcement.unpublished',
            $announcement,
            ['is_published' => true],
            ['is_published' => false],
            'announcements',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement unpublished successfully',
            'data' => $announcement,
        ]);
    }

    /**
     * Delete an announcement
     */
    public function destroy(Request $request, Academy $academy, SchoolAnnouncement $announcement)
    {
        if ($announcement->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Announcement not found'], 404);
        }

        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldData = $announcement->toArray();
        $announcement->delete();

        $this->auditLog->log(
            'announcement.deleted',
            $announcement,
            $oldData,
            null,
            'announcements',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }

    /**
     * Get announcement statistics
     */
    public function stats(Request $request, Academy $academy)
    {
        $user = $request->user();

        if (! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total' => SchoolAnnouncement::where('academy_id', $academy->id)->count(),
            'published' => SchoolAnnouncement::where('academy_id', $academy->id)->where('is_published', true)->count(),
            'drafts' => SchoolAnnouncement::where('academy_id', $academy->id)->where('is_published', false)->count(),
            'by_type' => SchoolAnnouncement::where('academy_id', $academy->id)
                ->selectRaw('announcement_type, count(*) as count')
                ->groupBy('announcement_type')
                ->pluck('count', 'announcement_type'),
            'by_priority' => SchoolAnnouncement::where('academy_id', $academy->id)
                ->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Check if user is academy admin
     */
    private function isAcademyAdmin($user, Academy $academy): bool
    {
        // Canonical check: owner (user_id/owner_id), academy_admins, or super admin.
        // The academy_members pivot `role` is not the source of truth for admin rights.
        return $academy->isAdmin($user);
    }
}
