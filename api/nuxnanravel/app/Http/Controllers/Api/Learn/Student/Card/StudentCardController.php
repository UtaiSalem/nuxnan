<?php

namespace App\Http\Controllers\Api\Learn\Student\Card;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentCardPublicResource;
use App\Http\Resources\StudentCardResource;
use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentCard;
use App\Services\StudentCardAuditService;
use App\Services\StudentCardSyncService;
use App\Services\StudentPhotoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentCardController extends Controller
{
    private function ensureCardBelongsToAcademy(StudentCard $card, $academy): void
    {
        if ($academy && (int) $card->academy_id !== (int) ($academy instanceof Academy ? $academy->id : $academy)) {
            abort(404);
        }
    }

    private function resolveCardRouteParameters($academy, $studentCard): array
    {
        if (! $studentCard) {
            $card = $academy instanceof StudentCard ? $academy : StudentCard::findOrFail($academy);

            return [null, $card];
        }

        $academyModel = $academy instanceof Academy ? $academy : Academy::findOrFail($academy);
        $card = $studentCard instanceof StudentCard ? $studentCard : StudentCard::findOrFail($studentCard);

        return [$academyModel, $card];
    }

    private function resolveRoomRouteParameters($academy, $level, $room): array
    {
        if ($room === null) {
            return [null, $academy, $level];
        }

        return [$academy, $level, $room];
    }

    /**
     * Get academy-scoped query builder
     */
    private function academyQuery($academy, $status = 'active')
    {
        $academyId = $academy instanceof Academy ? $academy->id : $academy;

        $query = StudentCard::where('academy_id', $academyId);
        if ($status !== 'all') {
            $query->where('student_status', $status);
        }

        return $query;
    }

    private function withStudentContext($query)
    {
        return $query->with([
            'student.classroomEnrollments' => fn ($query) => $query
                ->where('status', 'active')
                ->with('classroom.academicYear'),
            'activeCardRequest',
        ]);
    }

    private function applyMasterSearch($query, string $search)
    {
        return $query->whereHas('student', function ($studentQuery) use ($search) {
            $studentQuery->where(function ($fields) use ($search) {
                $fields->where('first_name_th', 'like', '%'.$search.'%')
                    ->orWhere('last_name_th', 'like', '%'.$search.'%')
                    ->orWhere('student_id', 'like', '%'.$search.'%')
                    ->orWhere('citizen_id', 'like', '%'.$search.'%');
            });
        });
    }

    private function applyCurrentClassFilters($query, $level = null, $section = null)
    {
        if ($level === null && $section === null) {
            return $query;
        }

        return $query->whereHas('student.classroomEnrollments', function ($enrollmentQuery) use ($level, $section) {
            $enrollmentQuery->where('status', 'active')
                ->whereHas('classroom', function ($classroomQuery) use ($level, $section) {
                    $classroomQuery->whereHas('academicYear', fn ($yearQuery) => $yearQuery->where('is_current', true));
                    if ($level !== null) {
                        $classroomQuery->where('grade_level', 'like', '%'.$level);
                    }
                    if ($section !== null) {
                        $classroomQuery->where('section', $section);
                    }
                });
        });
    }

    /**
     * Main index page
     */
    public function index($academy = null)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Dashboard - show overview
     */
    public function dashboard($academy = null)
    {
        $baseQuery = fn () => $academy ? $this->academyQuery($academy, 'active') : StudentCard::where('student_status', 'active');
        $totalStudents = $baseQuery()->count();
        $levelRows = $baseQuery()
            ->selectRaw('class_level, class_section, COUNT(*) as student_count')
            ->groupBy('class_level', 'class_section')
            ->orderBy('class_level')
            ->orderBy('class_section')
            ->get();
        $levels = $levelRows->groupBy('class_level')->map(function ($sections, $level) {
            return [
                'level' => (string) $level,
                'name' => 'ม.'.$level,
                'sections' => $sections->pluck('class_section')->map(fn ($section) => (string) $section)->values(),
                'studentCount' => $sections->sum('student_count'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'totalStudents' => $totalStudents,
            'levels' => $levels,
        ]);
    }

    /**
     * Get statistics for student cards - for academy admin dashboard
     */
    public function statistics($academy = null)
    {
        $query = $this->academyQuery($academy, 'active');
        $totalStudents = $query->count();

        $totalGraduated = $this->academyQuery($academy, 'graduated')->count();
        $totalExpired = $this->academyQuery($academy, 'expired')->count();

        $withPhoto = $this->academyQuery($academy, 'active')
            ->whereNotNull('profile_image')
            ->where('profile_image', '!=', '')
            ->count();
        $withoutPhoto = $totalStudents - $withPhoto;

        $byLevel = $this->academyQuery($academy, 'active')
            ->selectRaw('class_level, COUNT(*) as count')
            ->groupBy('class_level')
            ->pluck('count', 'class_level')
            ->toArray();

        // Sections per level
        $sectionsByLevel = $this->academyQuery($academy, 'active')
            ->selectRaw('class_level, class_section')
            ->distinct()
            ->orderBy('class_level')
            ->orderBy('class_section')
            ->get()
            ->groupBy('class_level')
            ->map(fn ($items) => $items->pluck('class_section')->sort()->values())
            ->toArray();

        return response()->json([
            'success' => true,
            'statistics' => [
                'totalActive' => $totalStudents,
                'totalStudents' => $totalStudents,
                'totalGraduated' => $totalGraduated,
                'totalExpired' => $totalExpired,
                'withPhoto' => $withPhoto,
                'withoutPhoto' => $withoutPhoto,
                'byLevel' => $byLevel,
                'sectionsByLevel' => $sectionsByLevel,
            ],
        ]);
    }

    /**
     * Get all class levels with student counts (Dynamic levels endpoint)
     */
    public function getLevels($academy = null)
    {
        $levelsData = $this->academyQuery($academy, 'active')
            ->selectRaw('class_level, class_section, COUNT(*) as studentCount')
            ->groupBy('class_level', 'class_section')
            ->get();

        $formattedLevels = [];

        foreach ($levelsData->groupBy('class_level') as $level => $sections) {
            $formattedLevels[] = [
                'level' => $level,
                'name' => 'ม.'.$level,
                'sections' => $sections->pluck('class_section')->sort()->values()->toArray(),
                'studentCount' => $sections->sum('studentCount'),
            ];
        }

        return response()->json([
            'success' => true,
            'levels' => $formattedLevels,
        ]);
    }

    /**
     * Get all sections/rooms
     */
    public function getSections($academy = null)
    {
        $sections = $this->academyQuery($academy, 'active')->distinct()->pluck('class_section')->sort()->values();

        return response()->json([
            'success' => true,
            'sections' => $sections,
        ]);
    }

    /**
     * Search functionality
     */
    public function search(Request $request, $academy = null)
    {
        $status = $request->input('status', 'active');
        $query = $academy ? $this->academyQuery($academy, $status) : StudentCard::query();

        if ($request->search) {
            $this->applyMasterSearch($query, $request->search);
        }

        $this->applyCurrentClassFilters($query, $request->level, $request->section);

        $students = $this->withStudentContext($query)->orderBy('class_level')
            ->orderBy('class_section')
            ->orderBy('order_no')
            ->paginate(20);

        $students->through(fn ($card) => new StudentCardResource($card));

        return response()->json([
            'success' => true,
            'students' => $students,
            'filters' => $request->only(['search', 'level', 'section', 'status']),
        ]);
    }

    /**
     * Student profile view
     */
    public function profile($academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $student_card->load('student.classroomEnrollments.classroom.academicYear');

        return response()->json([
            'success' => true,
            'student' => new StudentCardResource($student_card),
        ]);
    }

    /**
     * Get student card by student ID
     */
    public function byStudent(Academy $academy, Student $student)
    {
        abort_unless((int) $student->academy_id === (int) $academy->id, 404);

        $card = $this->withStudentContext(StudentCard::where('academy_id', $academy->id))
            ->where('student_id', $student->id)
            ->first();

        return response()->json([
            'success' => true,
            'student' => $card ? new StudentCardResource($card) : null,
        ]);
    }

    /**
     * Admin main page
     */
    public function adminIndex($academy = null)
    {
        return response()->json(['success' => true]);
    }

    /**
     * Admin student management with search/filter
     */
    public function adminStudents(Request $request, $academy = null)
    {
        $status = $request->input('status', 'active');
        $query = $academy ? $this->academyQuery($academy, $status) : StudentCard::query();

        if ($request->search) {
            $this->applyMasterSearch($query, $request->search);
        }

        $this->applyCurrentClassFilters($query, $request->level, $request->section);

        $students = $this->withStudentContext($query)->orderBy('class_level')
            ->orderBy('class_section')
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->paginate(50);

        $students->through(fn ($card) => new StudentCardResource($card));

        return response()->json([
            'success' => true,
            'students' => $students,
            'filters' => $request->only(['search', 'level', 'section', 'status']),
        ]);
    }

    /**
     * Get students by level and room
     */
    public function getStudentByRoom(Request $request, $academy = null, $level = null, $room = null)
    {
        [$academy, $level, $room] = $this->resolveRoomRouteParameters($academy, $level, $room);
        $baseQuery = $academy ? $this->academyQuery($academy, 'active') : StudentCard::where('student_status', 'active');

        $this->applyCurrentClassFilters($baseQuery, $level, $room);
        $students = $this->withStudentContext($baseQuery)
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->get();

        $resourceClass = ($academy && $request->user()) ? StudentCardResource::class : StudentCardPublicResource::class;

        return response()->json([
            'success' => true,
            'students' => $resourceClass::collection($students),
            'level' => $level,
            'room' => $room,
        ]);
    }

    /**
     * Admin: Get students by level and room
     */
    public function adminGetStudentByRoom($academy = null, $level = null, $room = null)
    {
        [$academy, $level, $room] = $this->resolveRoomRouteParameters($academy, $level, $room);
        $baseQuery = $academy ? $this->academyQuery($academy, 'active') : StudentCard::where('student_status', 'active');

        $this->applyCurrentClassFilters($baseQuery, $level, $room);
        $students = $this->withStudentContext($baseQuery)
            ->orderBy('order_no')
            ->orderBy('first_name_thai')
            ->get();

        return response()->json([
            'success' => true,
            'students' => StudentCardResource::collection($students),
            'level' => $level,
            'room' => $room,
        ]);
    }

    /**
     * Audit student cards against actual enrollments
     */
    public function audit(Request $request, Academy $academy, StudentCardAuditService $auditService)
    {
        $yearId = $request->input('academic_year_id');
        $year = $yearId
            ? AcademicYear::where('academy_id', $academy->id)->find($yearId)
            : AcademicYear::where('academy_id', $academy->id)->where('is_current', 1)->first();

        if (! $year) {
            return response()->json(['success' => false, 'message' => 'Academic year not found'], 404);
        }

        $levels = $request->input('levels') ? explode(',', $request->input('levels')) : [];

        $report = $auditService->audit($academy, $year, $levels);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Preview Sync
     */
    public function syncPreview(Request $request, Academy $academy, StudentCardSyncService $syncService)
    {
        $yearId = $request->input('academic_year_id');
        $year = $yearId
            ? AcademicYear::where('academy_id', $academy->id)->find($yearId)
            : AcademicYear::where('academy_id', $academy->id)->where('is_current', 1)->first();

        if (! $year) {
            return response()->json(['success' => false, 'message' => 'Academic year not found'], 404);
        }

        $report = $syncService->previewSync($academy, $year);

        return response()->json([
            'success' => true,
            'preview' => $report,
        ]);
    }

    /**
     * Commit Sync
     */
    public function syncCommit(Request $request, Academy $academy, StudentCardSyncService $syncService)
    {
        if ((bool) $academy->getSettings()?->card_request_flow_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Legacy student card sync is disabled. Use student card requests instead.',
            ], 410);
        }

        $request->validate([
            'academic_year_id' => ['nullable', 'integer'],
            'confirmation' => ['required', 'in:SYNC'],
        ]);

        $yearId = $request->input('academic_year_id');
        $year = $yearId
            ? AcademicYear::where('academy_id', $academy->id)->find($yearId)
            : AcademicYear::where('academy_id', $academy->id)->where('is_current', 1)->first();

        if (! $year) {
            return response()->json(['success' => false, 'message' => 'Academic year not found'], 404);
        }

        $user = $request->user();

        try {
            $result = $syncService->commitSync($academy, $year, $user);

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload/update student photo
     */
    public function updateImage(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            if ($request->hasFile('photo')) {
                $student = $student_card->student;
                if (! $student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่พบข้อมูลนักเรียนที่เชื่อมโยงกับบัตรนี้',
                    ], 400);
                }

                $photoService = app(StudentPhotoService::class);
                $photoService->delete($student);
                $path = $photoService->store($student, $request->file('photo'));
                $student_card->refresh();

                return response()->json([
                    'success' => true,
                    'message' => 'อัพโหลดรูปภาพสำเร็จ',
                    'photo' => basename($path),
                    'path' => Storage::disk('public')->url($path),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์รูปภาพ',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพโหลดรูปภาพได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update student number/code
     */
    public function updateStudentID(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $request->validate([
            'student_number' => 'required|string|max:255',
        ]);

        try {
            if ($student = $student_card->student) {
                $student->update(['student_id' => $request->input('student_number')]);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทรหัสนักเรียนสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทรหัสนักเรียนได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update student Thai name
     */
    public function updateStudentNameTh(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $request->validate([
            'title_name' => 'nullable|string|max:50',
            'first_name_thai' => 'nullable|string|max:255',
            'last_name_thai' => 'nullable|string|max:255',
        ]);

        try {
            $titleName = $request->input('title_name', $student_card->title_name);
            $firstName = $request->input('first_name_thai', $student_card->first_name_thai);
            $lastName = $request->input('last_name_thai', $student_card->last_name_thai);

            if ($student = $student_card->student) {
                $student->update([
                    'title_prefix_th' => $titleName,
                    'first_name_th' => $firstName,
                    'last_name_th' => $lastName,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทชื่อสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทชื่อได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update student English name
     */
    public function updateStudentNameEn(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $request->validate([
            'first_name_english' => 'nullable|string|max:255',
            'last_name_english' => 'nullable|string|max:255',
        ]);

        try {
            if ($student = $student_card->student) {
                $updates = ['first_name_en' => $request->input('first_name_english')];
                if ($request->has('last_name_english')) {
                    $updates['last_name_en'] = $request->input('last_name_english');
                }
                $student->update($updates);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทชื่อภาษาอังกฤษสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทชื่อภาษาอังกฤษได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update full student card info
     */
    public function update(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        $request->validate([
            'student_number' => 'nullable|string|max:255',
            'title_name' => 'nullable|string|max:50',
            'first_name_thai' => 'nullable|string|max:255',
            'last_name_thai' => 'nullable|string|max:255',
            'first_name_english' => 'nullable|string|max:255',
            'last_name_english' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'class_level' => 'nullable|string|max:10',
            'class_section' => 'nullable|string|max:10',
            'card_issue_date' => 'nullable|date',
            'card_expiry_date' => 'nullable|date',
        ]);

        try {
            $student = $student_card->student;
            if (! $student) {
                return response()->json(['success' => false, 'message' => 'Student master record not found'], 422);
            }

            DB::transaction(function () use ($request, $student, $student_card) {
                $studentUpdates = [];
                if ($request->has('student_number')) {
                    $studentUpdates['student_id'] = $request->input('student_number');
                }
                if ($request->has('title_name')) {
                    $studentUpdates['title_prefix_th'] = $request->input('title_name');
                }
                if ($request->has('first_name_thai')) {
                    $studentUpdates['first_name_th'] = $request->input('first_name_thai');
                }
                if ($request->has('last_name_thai')) {
                    $studentUpdates['last_name_th'] = $request->input('last_name_thai');
                }
                if ($request->has('first_name_english')) {
                    $studentUpdates['first_name_en'] = $request->input('first_name_english');
                }
                if ($request->has('last_name_english')) {
                    $studentUpdates['last_name_en'] = $request->input('last_name_english');
                }
                if ($request->has('national_id')) {
                    $studentUpdates['citizen_id'] = $request->input('national_id');
                }
                if ($request->has('birth_date')) {
                    $studentUpdates['date_of_birth'] = $request->input('birth_date');
                }

                if (! empty($studentUpdates)) {
                    $student->update($studentUpdates);
                }

                $cardUpdates = $request->only(['card_issue_date', 'card_expiry_date']);
                if (! empty($cardUpdates)) {
                    $student_card->update($cardUpdates);
                }
            });

            $student_card = $student_card->fresh();
            $student_card->load('student.classroomEnrollments.classroom.academicYear');

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลสำเร็จ',
                'student' => new StudentCardResource($student_card),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new student card
     */
    public function store($academy, Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:255',
            'first_name_thai' => 'required|string|max:255',
            'last_name_thai' => 'required|string|max:255',
            'class_level' => 'required|string|max:10',
            'class_section' => 'required|string|max:10',
            'title_name' => 'nullable|string|max:50',
            'first_name_english' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
        ]);

        $academyId = $academy instanceof Academy ? $academy->id : $academy;

        try {
            $data = $request->only([
                'student_number', 'title_name', 'first_name_thai', 'last_name_thai',
                'first_name_english', 'national_id', 'birth_date', 'class_level', 'class_section',
            ]);

            $student = Student::where('academy_id', $academyId)
                ->where('student_id', $request->input('student_number'))
                ->first();

            if (! $student) {
                $student = Student::create([
                    'academy_id' => $academyId,
                    'student_id' => $request->input('student_number'),
                    'citizen_id' => $request->input('national_id'),
                    'title_prefix_th' => $request->input('title_name'),
                    'first_name_th' => $request->input('first_name_thai'),
                    'last_name_th' => $request->input('last_name_thai'),
                    'first_name_en' => $request->input('first_name_english'),
                    'date_of_birth' => $request->input('birth_date'),
                    'status' => 'active',
                ]);
            }

            $data['student_id'] = $student->id;
            $data['academy_id'] = $academyId;
            $data['full_name_thai'] = trim(
                ($data['title_name'] ?? '').' '.$data['first_name_thai'].' '.$data['last_name_thai']
            );
            $data['student_status'] = 'active';

            if (! empty($data['birth_date'])) {
                $data['birth_date_string'] = $this->convertDateToThaiFormat($data['birth_date']);
            }

            $studentCard = StudentCard::create($data);

            return response()->json([
                'success' => true,
                'message' => 'สร้างบัตรนักเรียนสำเร็จ',
                'student' => new StudentCardResource($studentCard),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างบัตรนักเรียนได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function convertDateToThaiFormat($date)
    {
        if (! $date) {
            return null;
        }

        try {
            $carbonDate = Carbon::parse($date);

            return $carbonDate->format('d/m/Y');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete student photo
     */
    public function destroyPhoto(Request $request, $academy = null, $student_card = null)
    {
        [$academy, $student_card] = $this->resolveCardRouteParameters($academy, $student_card);
        $this->ensureCardBelongsToAcademy($student_card, $academy);
        try {
            $student = $student_card->student;
            if ($student) {
                $deleted = app(StudentPhotoService::class)->delete($student);
                $student_card->refresh();
                if ($deleted) {
                    return response()->json([
                        'success' => true,
                        'message' => 'ลบรูปภาพสำเร็จ',
                    ]);
                }
            } elseif ($student_card->profile_image) {
                $path = str_starts_with($student_card->profile_image, 'images/')
                    ? $student_card->profile_image
                    : 'images/students/'.$student_card->class_level.'/'.$student_card->class_section.'/'.$student_card->profile_image;
                Storage::disk('public')->delete($path);
                $student_card->update(['profile_image' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'ลบรูปภาพสำเร็จ',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรูปภาพ',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบรูปภาพได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import student cards from CSV/Excel
     */
    public function import(Request $request, $academy = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์นำเข้าข้อมูลยังอยู่ระหว่างการพัฒนา',
        ], 501);
    }

    /**
     * Export student cards to CSV/Excel
     */
    public function export(Request $request, $academy = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์ส่งออกข้อมูลยังอยู่ระหว่างการพัฒนา',
        ], 501);
    }

    /**
     * Bulk upload photos
     */
    public function bulkUploadPhotos(Request $request, $academy = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์อัพโหลดรูปภาพแบบกลุ่มยังอยู่ระหว่างการพัฒนา',
        ], 501);
    }

    /**
     * Bulk update student cards
     */
    public function bulkUpdate(Request $request, $academy = null)
    {
        return response()->json([
            'success' => false,
            'message' => 'ฟีเจอร์อัพเดทข้อมูลแบบกลุ่มยังอยู่ระหว่างการพัฒนา',
        ], 501);
    }
}
