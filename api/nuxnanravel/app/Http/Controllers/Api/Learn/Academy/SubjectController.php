<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Get all subjects for academy
     */
    public function index(Request $request, int $academyId): JsonResponse
    {
        $subjectType = $request->query('type');
        $subjectGroup = $request->query('group');
        $search = $request->query('search');

        $query = Subject::where('academy_id', $academyId);

        if ($subjectType) {
            $query->where('subject_type', $subjectType);
        }

        if ($subjectGroup) {
            $query->where('subject_group', $subjectGroup);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_th', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        $subjects = $query->active()
            ->orderBy('subject_group')
            ->orderBy('name_th')
            ->get();

        return response()->json([
            'success' => true,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Get single subject
     */
    public function show(int $academyId, int $id): JsonResponse
    {
        $subject = Subject::where('academy_id', $academyId)
            ->with('courses')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'subject' => $subject,
        ]);
    }

    /**
     * Store new subject
     */
    public function store(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'subject_code' => 'nullable|string|max:20',
            'name_th' => 'required|string|max:150',
            'name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'credits' => 'numeric|min:0|max:99',
            'hours_per_week' => 'numeric|min:0|max:99',
            'subject_type' => 'in:required,elective,activity',
            'subject_group' => 'nullable|string|max:50',
            'grade_levels' => 'nullable|array',
        ]);

        $validated['academy_id'] = $academyId;
        $validated['created_by'] = auth()->id();

        $subject = Subject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'สร้างรายวิชาสำเร็จ',
            'subject' => $subject,
        ], 201);
    }

    /**
     * Update subject
     */
    public function update(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $subject = Subject::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'subject_code' => 'nullable|string|max:20',
            'name_th' => 'string|max:150',
            'name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'credits' => 'numeric|min:0|max:99',
            'hours_per_week' => 'numeric|min:0|max:99',
            'subject_type' => 'in:required,elective,activity',
            'subject_group' => 'nullable|string|max:50',
            'grade_levels' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตรายวิชาสำเร็จ',
            'subject' => $subject,
        ]);
    }

    /**
     * Delete subject
     */
    public function destroy(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $subject = Subject::where('academy_id', $academyId)->findOrFail($id);
        $subject->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'ลบรายวิชาสำเร็จ',
        ]);
    }

    /**
     * Get subject groups
     */
    public function getGroups(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'groups' => Subject::GROUPS,
        ]);
    }

    // Helper Methods
    protected function canManage(Academy $academy): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($academy->user_id === $user->id) {
            return true;
        }

        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'director', 'admin'])
            ->exists();
    }
}
