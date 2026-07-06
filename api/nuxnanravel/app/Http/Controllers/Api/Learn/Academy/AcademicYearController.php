<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    /**
     * Get all academic years for academy
     */
    public function index(int $academyId): JsonResponse
    {
        $academicYears = AcademicYear::where('academy_id', $academyId)
            ->with('semesters')
            ->orderByDesc('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store new academic year
     */
    public function store(Request $request, int $academyId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
            'create_semesters' => 'boolean',
        ]);

        $academicYear = AcademicYear::create([
            'academy_id' => $academyId,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $validated['is_current'] ?? false,
        ]);

        if ($validated['is_current'] ?? false) {
            $academicYear->setAsCurrent();
        }

        // Auto-create semesters
        if ($validated['create_semesters'] ?? true) {
            $this->createDefaultSemesters($academicYear);
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างปีการศึกษาสำเร็จ',
            'academicYear' => $academicYear->load('semesters'),
        ], 201);
    }

    /**
     * Update academic year
     */
    public function update(Request $request, int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $academicYear = AcademicYear::where('academy_id', $academyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:20',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $academicYear->update($validated);

        if (isset($validated['is_current']) && $validated['is_current']) {
            $academicYear->setAsCurrent();
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตปีการศึกษาสำเร็จ',
            'academicYear' => $academicYear->fresh('semesters'),
        ]);
    }

    /**
     * Delete academic year
     */
    public function destroy(int $academyId, int $id): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $academicYear = AcademicYear::where('academy_id', $academyId)->findOrFail($id);
        $academicYear->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบปีการศึกษาสำเร็จ',
        ]);
    }

    /**
     * Get current academic year and semester
     */
    public function getCurrent(int $academyId): JsonResponse
    {
        $academicYear = AcademicYear::where('academy_id', $academyId)
            ->where('is_current', true)
            ->with('semesters')
            ->first();

        $currentSemester = $academicYear?->semesters()
            ->where('is_current', true)
            ->first();

        return response()->json([
            'success' => true,
            'academicYear' => $academicYear,
            'semester' => $currentSemester,
        ]);
    }

    // Semester Management
    public function storeSemester(Request $request, int $academyId, int $academicYearId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $academicYear = AcademicYear::where('academy_id', $academyId)->findOrFail($academicYearId);

        $validated = $request->validate([
            'semester_number' => 'required|integer|between:1,3',
            'name' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYearId,
            'semester_number' => $validated['semester_number'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_current' => $validated['is_current'] ?? false,
        ]);

        if ($validated['is_current'] ?? false) {
            $semester->setAsCurrent();
        }

        return response()->json([
            'success' => true,
            'message' => 'สร้างภาคเรียนสำเร็จ',
            'semester' => $semester,
        ], 201);
    }

    public function updateSemester(Request $request, int $academyId, int $academicYearId, int $semesterId): JsonResponse
    {
        $academy = Academy::findOrFail($academyId);

        if (! $this->canManage($academy)) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสิทธิ์จัดการ'], 403);
        }

        $semester = Semester::where('academic_year_id', $academicYearId)->findOrFail($semesterId);

        $validated = $request->validate([
            'semester_number' => 'integer|between:1,3',
            'name' => 'string|max:50',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $semester->update($validated);

        if (isset($validated['is_current']) && $validated['is_current']) {
            $semester->setAsCurrent();
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตภาคเรียนสำเร็จ',
            'semester' => $semester,
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

    protected function createDefaultSemesters(AcademicYear $academicYear): void
    {
        $startDate = $academicYear->start_date;
        $endDate = $academicYear->end_date;

        // Calculate midpoint for semester split
        $totalDays = $startDate->diffInDays($endDate);
        $midDate = $startDate->copy()->addDays(intval($totalDays / 2));

        // Semester 1
        Semester::create([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 1,
            'name' => 'ภาคเรียนที่ 1',
            'start_date' => $startDate,
            'end_date' => $midDate->copy()->subDay(),
            'is_current' => true,
        ]);

        // Semester 2
        Semester::create([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 2,
            'name' => 'ภาคเรียนที่ 2',
            'start_date' => $midDate,
            'end_date' => $endDate,
            'is_current' => false,
        ]);
    }
}
