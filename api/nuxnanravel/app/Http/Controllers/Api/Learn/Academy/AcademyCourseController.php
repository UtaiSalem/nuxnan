<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Academy\AcademyResource;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Models\Academy;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AcademyCourseController extends Controller
{
    public function index(Academy $academy, Request $request)
    {
        return $this->respondWithCourses($academy, $request, false);
    }

    public function create(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'courses' => CourseResource::collection($academy->courses()->paginate()),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    public function store(Academy $academy, Request $request)
    {
        try {

            $validated = $request->validate([
                // 'academy_id'        => 'nullable',
                // 'code'              => 'nullable|string|max:255',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:500000',
                // 'category'          => 'nullable|string|max:255',
                // 'level'             => 'nullable|string|max:255',
                // 'credit_units'      => 'nullable|string',
                // 'hours_per_week'    => 'nullable|string',
                // 'start_date'        => 'nullable',
                // 'end_date'          => 'nullable',
                // 'saleable'          => 'nullable|string',
                // 'tuition_fees'      => 'nullable|string',
                // 'price'             => 'nullable|string',
                // 'status'            => 'required',
                'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            ]);

            if ($request->file('cover')) {
                $cover_file = $request->file('cover');
                $cover_filename = uniqid().'.'.$cover_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/covers', $cover_file, $cover_filename);
            }

            // $validated['academy_id'] = $request->academy_id ?? null;
            // $validated['user_id'] = auth()->id();
            // $validated['instructor_id'] = auth()->id();
            // $validated['cover'] =  $cover_filename ?? null;
            // $validated['start_date'] =  $request->start_date ?? null;
            // $validated['end_date'] =  $request->end_date ?? null;
            // $validated['saleable'] =  $request->saleable ?? null;
            // $validated['status'] =  $request->status ?? null;

            // $newCourse = Course::create($validated);
            $newCourse = new Course;

            $newCourse->user_id = auth()->id();
            $newCourse->instructor_id = auth()->id();
            $newCourse->academy_id = $request->academy_id == 'null' || $request->academy_id == null ? null : $academy->id;
            $newCourse->name = $validated['name'];
            // $newCourse->slug            = Str::slug($validated['name'], '-');
            $newCourse->code = $request->code == 'null' || $request->code == null ? null : $request->code;
            $newCourse->description = $request->description == 'null' || $request->description == null ? null : $request->description;
            $newCourse->duration = $request->duration ?? null;
            $newCourse->start_date = $request->start_date == 'null' || $request->start_date == 'undefined' ? null : Carbon::parse($request->start_date);
            $newCourse->end_date = $request->end_date == 'null' || $request->end_date == 'undefined' ? null : Carbon::parse($request->end_date);
            $newCourse->credit_units = $request->credit_units ?? null;
            $newCourse->hours_per_week = $request->hours_per_week ?? null;
            $newCourse->category = $request->category == 'null' || $request->category == null ? null : $request->category;
            $newCourse->tuition_fees = $request->tuition_fees ?? null;
            $newCourse->status = $request->status === 'true' ? 1 : 0;
            $newCourse->saleable = $request->saleable === 'true' ? 1 : 0;
            $newCourse->price = $request->price == 'null' || $request->price == null ? null : $request->price;
            $newCourse->level = $request->level == 'null' || $request->level == null ? null : $request->level;
            $newCourse->education_level = $request->education_level == 'null' || $request->education_level == null ? null : $request->education_level;
            $newCourse->education_year = $request->education_year == 'null' || $request->education_year == null ? null : $request->education_year;
            $newCourse->cover = $cover_filename ?? null;

            $newCourse->save();

            if ($newCourse) {
                $newCourse->courseSettings()->create([
                    'auto_accept_members' => $request->auto_accept_members == 'true' ? 1 : 0,
                ]);

                $academy->increment('courses_offered');
            }

            // to_route('course.show', $newCourse->id);
            return response()->json([
                'success' => true,
                // 'id'        => $newCourse->id,
                'newCourse' => $newCourse,
                // 'request'   => $request->all(),
                // 'newCourse' => $validated,
            ], 200);
            // return to_route('academy.courses.index', $academy->id);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Academy $academy, Course $course)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'course' => new CourseResource($course),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    public function edit(Academy $academy, Course $course)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();

        return response()->json([
            'academy' => new AcademyResource($academy),
            'course' => new CourseResource($course),
            'isAcademyAdmin' => $isAcademyAdmin,
        ]);
    }

    public function getAcademyCourses(Academy $academy, Request $request)
    {
        return $this->respondWithCourses($academy, $request, true);
    }

    protected function respondWithCourses(Academy $academy, Request $request, bool $includeSuccess): \Illuminate\Http\JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $courses = $this->buildCourseQuery($academy, $request)
            ->paginate($perPage)
            ->withQueryString();

        $coursesResource = CourseResource::collection($courses);
        $authUser = auth()->guard('api')->user() ?? auth()->user();
        $isAcademyAdmin = $academy->user_id === ($authUser?->id);

        $payload = [
            'courses' => $coursesResource,
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
            'available_filters' => $this->buildAvailableFilters($academy),
        ];

        if ($includeSuccess) {
            $payload['success'] = true;
        } else {
            $payload['allCourses'] = $coursesResource;
            $payload['authOwnerCourses'] = $authUser
                ? CourseResource::collection($authUser->courses()->take(10)->get())
                : [];
            $payload['authMemberCourses'] = [];
            $payload['academy'] = new AcademyResource($academy);
            $payload['isAcademyAdmin'] = $isAcademyAdmin;
        }

        return response()->json($payload, 200);
    }

    protected function buildCourseQuery(Academy $academy, Request $request)
    {
        $authUser = auth()->guard('api')->user() ?? auth()->user();

        $query = $academy->courses()
            ->with([
                'user',
                'courseMembers' => function ($relation) use ($authUser) {
                    if ($authUser) {
                        $relation->where('user_id', $authUser->id);
                    }
                },
                'favorites' => function ($relation) use ($authUser) {
                    if ($authUser) {
                        $relation->where('user_id', $authUser->id);
                    }
                },
            ])
            ->withCount(['courseLessons', 'courseMembers'])
            ->latest();

        if ($request->filled('education_level') && $request->input('education_level') !== 'all') {
            $query->where('education_level', $request->input('education_level'));
        }

        if ($request->filled('education_year') && $request->input('education_year') !== 'all') {
            $query->where('education_year', $request->input('education_year'));
        }

        if ($request->filled('semester') && $request->input('semester') !== 'all') {
            $query->where('semester', $request->input('semester'));
        }

        if ($request->filled('academic_year') && $request->input('academic_year') !== 'all') {
            $query->where('academic_year', $request->input('academic_year'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = (string) $request->input('status');

            match ($status) {
                'published' => $query
                    ->where(function (Builder $builder) {
                        $builder
                            ->where('status', 1)
                            ->orWhere('status', 'published');
                    })
                    ->where(function (Builder $builder) {
                        $builder
                            ->whereNull('finalization_status')
                            ->orWhere('finalization_status', '!=', 'archived');
                    }),
                'draft' => $query->where(function (Builder $builder) {
                    $builder
                        ->whereIn('status', [0, 2])
                        ->orWhereIn('status', ['draft', 'pending']);
                }),
                'archived' => $query->where(function (Builder $builder) {
                    $builder
                        ->where('finalization_status', 'archived')
                        ->orWhere('status', 'archived');
                }),
                default => null,
            };
        }

        return $query;
    }

    protected function buildAvailableFilters(Academy $academy): array
    {
        $baseQuery = Course::query()->where('academy_id', $academy->id);

        $levelOrder = [
            'ประถมศึกษา' => 1,
            'มัธยมศึกษา' => 2,
            'ปวช.' => 3,
            'ปวส.' => 4,
            'อุดมศึกษา' => 5,
            'อื่นๆ' => 6,
            'unspecified' => 99,
        ];

        $semesterOrder = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            'summer' => 4,
            'weekend' => 5,
            'unspecified' => 99,
        ];

        $educationLevels = (clone $baseQuery)
            ->selectRaw("COALESCE(education_level, 'unspecified') as value, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(education_level, 'unspecified')")
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => $row->value === 'unspecified' ? 'ยังไม่ระบุ' : $row->value,
                'count' => (int) $row->aggregate,
            ])
            ->sortBy(fn (array $row) => $levelOrder[$row['value']] ?? 90)
            ->values();

        $educationYears = (clone $baseQuery)
            ->selectRaw("COALESCE(CAST(education_year as CHAR), 'unspecified') as value, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(CAST(education_year as CHAR), 'unspecified')")
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => $row->value === 'unspecified' ? 'ยังไม่ระบุ' : 'ปี '.$row->value,
                'count' => (int) $row->aggregate,
            ])
            ->sortBy(function (array $row) {
                if ($row['value'] === 'unspecified') {
                    return 999;
                }

                return (int) $row['value'];
            })
            ->values();

        $semesters = (clone $baseQuery)
            ->selectRaw("COALESCE(CAST(semester as CHAR), 'unspecified') as value, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(CAST(semester as CHAR), 'unspecified')")
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => $row->value === 'unspecified' ? 'ยังไม่ระบุ' : 'ภาค '.$row->value,
                'count' => (int) $row->aggregate,
            ])
            ->sortBy(fn (array $row) => $semesterOrder[$row['value']] ?? 90)
            ->values();

        $academicYears = (clone $baseQuery)
            ->selectRaw("COALESCE(CAST(academic_year as CHAR), 'unspecified') as value, COUNT(*) as aggregate")
            ->groupByRaw("COALESCE(CAST(academic_year as CHAR), 'unspecified')")
            ->get()
            ->map(fn ($row) => [
                'value' => $row->value,
                'label' => $row->value === 'unspecified' ? 'ยังไม่ระบุ' : $row->value,
                'count' => (int) $row->aggregate,
            ])
            ->sortByDesc(function (array $row) {
                if ($row['value'] === 'unspecified') {
                    return -1;
                }

                return is_numeric($row['value']) ? (int) $row['value'] : 0;
            })
            ->values();

        return [
            'education_levels' => $educationLevels,
            'education_years' => $educationYears,
            'semesters' => $semesters,
            'academic_years' => $academicYears,
        ];
    }
}
