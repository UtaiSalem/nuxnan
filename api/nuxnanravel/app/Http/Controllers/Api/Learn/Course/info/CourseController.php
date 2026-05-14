<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Http\Controllers\Controller;
use App\Models\User;

use App\Models\Course;
use App\Models\Academy;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\CourseMember;
use App\Models\CourseInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Course\lessons\LessonResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\Learn\Course\quizzes\CourseQuizResource;
use App\Http\Resources\Learn\Course\progress\CourseMemberGradeProgressResource;
use App\Http\Resources\Learn\Course\groups\CourseGroupResource;
use App\Http\Resources\Learn\Course\members\CourseMemberResource;
use App\Http\Resources\Learn\Course\info\MemberedCourseResource;
use App\Http\Resources\Learn\Course\info\UserProfileCourseResource;
use App\Models\RecentlyViewedCourse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LearningResultsExport;

class CourseController extends Controller
{
    public function getRecentCourses(Request $request)
    {
        $user = auth()->user();
        
        // Get the most recently viewed course IDs
        $recentCourseIds = RecentlyViewedCourse::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->pluck('course_id');
            
        if ($recentCourseIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'courses' => [],
            ]);
        }
        
        // Fetch the course objects, preserving the order
        // MySQL FIELD() function usage for custom ordering
        $courses = Course::whereIn('id', $recentCourseIds)
            ->orderByRaw("FIELD(id, " . implode(',', $recentCourseIds->toArray()) . ")")
            ->get();

        return response()->json([
            'success' => true,
            'courses' => CourseResource::collection($courses),
        ]);
    }

    public function getPopularCourses()
    {
        // Get top 5 courses by member count
        $courses = Course::with('user')
            ->withCount('courseMembers')
            ->orderBy('course_members_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'courses' => CourseResource::collection($courses),
        ]);
    }

    public function index(Request $request)
    {
        // Directly call getMoreCourses which returns a properly formatted JSON response
        return $this->getMoreCourses($request);
    }

    public function getFavoriteCourses(Request $request)
    {
        $courses = Course::whereHas('favorites', function($q) {
            $q->where('user_id', auth()->id());
        })
        ->with(['user'])
        ->orderBy('created_at', 'desc')
        ->paginate($request->input('limit', 12));

        return \App\Http\Resources\Learn\Course\info\CourseResource::collection($courses);
    }

    public function toggleFavorite(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $user = auth()->user();

        $action = $course->favorites()->toggle($user->id);

        // 'attached' (added) or 'detached' (removed)
        $isFavorited = count($action['attached']) > 0;

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'เพิ่มในรายการโปรดแล้ว' : 'ลบออกจากรายการโปรดแล้ว'
        ]);
    }

    public function getMoreCourses(Request $request = null) {
        $request = $request ?? request();
        $query = Course::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('education_level') && $request->education_level !== 'all') {
            $query->where('education_level', $request->education_level);
        }
        if ($request->filled('education_year') && $request->education_year !== 'all') {
            $query->where('education_year', $request->education_year);
        }

        if ($request->filled('semester') && $request->semester !== 'all') {
            $query->where('semester', $request->semester);
        }

        if ($request->filled('academic_year') && $request->academic_year !== 'all') {
            $query->where('academic_year', $request->academic_year);
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'latest':
                    $query->latest();
                    break;
                case 'popular':
                    $query->withCount('courseMembers')->orderBy('course_members_count', 'desc');
                    break;
                case 'price-low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price-high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    // Assuming rating logic exists or just fallback
                    $query->latest(); 
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        if (auth()->guard('api')->check()) {
            $query->with(['courseMembers' => function($q) {
                $q->where('user_id', auth()->guard('api')->id());
            }]);
        }
        
        $perPage = $request->input('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success'       => true,
            'courses'       => [
                'data' => CourseResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ], 200);
    }

    public function getUserCourses(User $user)
    {
        return response()->json([
            'courses'           => CourseResource::collection($user->courses()->latest()->paginate()),
        ]);
    }

    public function getMyCourses(User $user, Request $request)
    {
        $perPage = $request->input('per_page', 8);
        $query = $user->courses()->with(['user', 'courseMembers' => function($q) {
            $q->where('user_id', auth()->guard('api')->id());
        }]);

        if ($request->has('cloned')) {
            if ($request->cloned == '1') {
                $query->whereNotNull('source_course_id');
            } else {
                $query->whereNull('source_course_id');
            }
        }

        $paginated = $query->latest()->paginate($perPage);

        return response()->json([
            'success'   => true,
            'courses'   => CourseResource::collection($paginated),
            'create_course_threshold' => config('features.create_course_threshold', 100),
            'pagination' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ]
        ], 200);
    }


    public function getAuthMemberCourses(User $user)
    {
        $authMemberCourse = CourseMember::where('user_id', auth()->id())->pluck('course_id')->all();
        $paginated = Course::whereIn('id', $authMemberCourse)
            ->with(['user', 'courseMembers' => function($q) {
                $q->where('user_id', auth()->guard('api')->id());
            }])
            ->latest()
            ->paginate();

        return response()->json([
            'courses' => CourseResource::collection($paginated),
        ]);
    }

    /**
     * Search courses where user is admin for quiz duplication
     */
    public function searchCourses(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'courses' => [],
            ]);
        }

        $user = auth()->user();
        
        // Get courses where user is admin
        $courses = Course::where(function($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereHas('courseMembers', function($q2) use ($user) {
                  $q2->where('user_id', $user->id)
                     ->whereIn('role', ['admin', 'teacher', 'instructor']);
              });
        })
        ->where(function($q) use ($query) {
            $q->where('name', 'like', '%' . $query . '%')
              ->orWhere('title', 'like', '%' . $query . '%')
              ->orWhere('code', 'like', '%' . $query . '%');
        })
        ->select(['id', 'name', 'title', 'code', 'cover_image'])
        ->orderBy('updated_at', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    public function getAuthMemberedCourses(User $user, Request $request)
    {
        $perPage = $request->input('per_page', 8); // Default to 8 if not specified
        $authMemberCourse = CourseMember::where('user_id', auth()->id())
            ->pluck('course_id')
            ->all();
        
        $query = Course::whereIn('id', $authMemberCourse)
            ->where('user_id', '!=', auth()->id()) // Exclude courses owned by the user
            ->latest()
            ->paginate($perPage);
        $coursesAuthMember = MemberedCourseResource::collection($query);

        return response()->json([
            'success'           => true,
            'courses'           => $coursesAuthMember,
            'pagination'        => [
                'total'        => $query->total(),
                'per_page'     => $query->perPage(),
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'from'         => $query->firstItem(),
                'to'           => $query->lastItem(),
            ]
        ], 200);
    }

    public function getUserMemberedCourses(User $user, Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status', 'enrolled'); // 'enrolled' or 'completed'

        $query = CourseMember::where('user_id', $user->id)
            ->when($status === 'completed', function($q) {
                // Completed if completion_date is set OR progress is 100% (optional check)
                $q->whereNotNull('completion_date');
            })
            ->when($status === 'enrolled', function($q) {
                $q->whereNull('completion_date');
            })
            ->with('course') // Eager load course
            ->latest()
            ->paginate($perPage);

        $courses = $query->map(function ($member) {
            return new UserProfileCourseResource($member->course, $member);
        });

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'pagination' => [
                'total'        => $query->total(),
                'per_page'     => $query->perPage(),
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'from'         => $query->firstItem(),
                'to'           => $query->lastItem(),
            ]
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function getSearchFilterOptions()
    {
        $semesters = Course::select('semester')
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        $years = Course::select('academic_year')
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        $categories = Course::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $educationLevels = Course::select('education_level')
            ->whereNotNull('education_level')
            ->where('education_level', '!=', '')
            ->distinct()
            ->orderBy('education_level')
            ->pluck('education_level');

        return response()->json([
            'success'           => true,
            'semesters'         => $semesters,
            'years'             => $years,
            'categories'        => $categories,
            'education_levels'  => $educationLevels,
        ]);
    }

    public function create()
    {
        return response()->json(['message' => 'Create form not needed for API']);
    }

    public function store(Request $request)
    {

        try {
            // $validated = $request->validate([
            //     'name'              => 'required|string|max:255',
            //     'cover'                => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            // ]);

            //validate for all of this request academy_id,code,name,description,category,level,credit_units,hours_per_week,start_date,end_date,auto_accept_members,saleable,price,status,cover
            $validated = $request->validate([
                'academy_id'        => 'nullable',
                'code'              => 'nullable',
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'category'          => 'nullable|string',
                'education_level'   => ['nullable', \Illuminate\Validation\Rule::in(['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'อุดมศึกษา', 'อื่นๆ'])],
                'education_year'    => ['nullable', 'integer', 'min:1', 'max:6'],
                'credit_units'      => 'nullable|numeric',
                'hours_per_week'    => 'nullable|numeric',
                'start_date'        => 'nullable|date',
                'end_date'          => 'nullable|date',
                'auto_accept_members'=> 'nullable|boolean',
                'saleable'          => 'nullable|boolean',
                'price'             => 'nullable|numeric',
                'status'            => 'nullable|string',
                'cover'             => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            ]);

            if($request->file('cover')) {
                $cover_file = $request->file('cover');
                $cover_filename =  uniqid().'.'.$cover_file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/covers', $cover_file, $cover_filename);              
                $validated['cover'] = $cover_filename;
            }


            // return response()->json([
            //     'success'   => true,
            //     'newCourse' => $request->all(),
            // ], 200);
            
            $newCourse = new Course();
            $newCourse->academy_id       = $request->academy_id;
            $newCourse->user_id          = auth()->id();
            $newCourse->instructor_id    = auth()->id();
            $newCourse->code             = $request->code;
            $newCourse->name             = $request->name;
            $newCourse->slug             = Str::slug($request->name);
            $newCourse->description      = $request->description;
            $newCourse->category         = $request->category;
            $newCourse->education_level  = $request->education_level;
            $newCourse->education_year   = $request->education_year;
            $newCourse->credit_units     = $request->credit_units;
            $newCourse->hours_per_week   = $request->hours_per_week;
            $newCourse->start_date       = Carbon::parse($validated['start_date'])->setTimezone('Asia/Bangkok');
            $newCourse->end_date         = Carbon::parse($validated['end_date'])->setTimezone('Asia/Bangkok');
            // $newCourse->auto_accept_members = $request->auto_accept_members;
            $newCourse->saleable         = $request->saleable;
            $newCourse->price            = $request->price;
            $newCourse->status           = $request->status;
            $newCourse->cover            = $validated['cover'] ?? '';
                        
            $newCourse->save();

            if ($newCourse) {
                $newCourse->courseSettings()->create([
                    'auto_accept_members' => $request->auto_accept_members ? 1 : 0,
                ]);

                $newCourse->courseGroups()->create([
                    'user_id' => auth()->id(),
                    'name'      => 'กลุ่ม1',
                ]);

                $newCourse->courseMembers()->create([
                    'user_id' => auth()->id(),
                    'status' => 1,
                    'course_member_status' => 1,
                    'role' => 4, // 4: Admin
                ]);
            }

            return response()->json([
                'success'   => true,
                'newCourse' => $newCourse,
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Course $course)
    {
        // Update recently viewed course if authenticated
        $user = auth()->user();
        if ($user) {
            RecentlyViewedCourse::updateOrInsert(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['updated_at' => now()]
            );
        }

        return to_route('course.feeds', $course->id);
    }

    public function edit(Course $course)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Course $course, Request $request )
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id'           => 'nullable',
            'instructor_id'     => 'nullable',
            'academy_id'        => 'nullable',
            'name'              => 'nullable|string',
            'slug'              => 'nullable',
            'code'              => 'nullable',
            'description'       => 'nullable',
            'duration'          => 'nullable',
            'tuition_fees'      => 'nullable|numeric',
            'price'             => 'nullable|numeric',
            'credit_units'      => 'nullable|numeric',
            'hours_per_week'    => 'nullable|numeric',
            'category'          => 'nullable',
            'capacity'          => 'nullable|numeric',
            'education_level'   => ['nullable', \Illuminate\Validation\Rule::in(['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'อุดมศึกษา', 'อื่นๆ'])],
            'education_year'    => ['nullable', 'integer', 'min:1', 'max:6'],
            'is_for_marketplace' => 'nullable|boolean',
            'price_points'      => 'nullable|numeric',
            'price_type'        => 'nullable|string|in:free,points,wallet,both',

        ]);

        $validated['name']          = $request->name ?? $course->name;
        $validated['start_date']    = $request->start_date == 'null' || $request->start_date == 'undefined' ? null : Carbon::parse($request->start_date);
        $validated['end_date']      = $request->end_date == 'null' || $request->end_date == 'undefined' ? null : Carbon::parse($request->end_date);

        $validated['status']        = $request->status ?? $course->status;
        $validated['saleable']      = $request->saleable;
        
        $course->update($validated);

        $course->courseSettings()->update([
            'auto_accept_members' => $request->auto_accept_members ?? $course->courseSettings->auto_accept_members,
        ]);

        if($request->hasFile('cover')) {

            $file = public_path().'\storage\images\courses\covers\\'. $course->cover;
            if ($course->cover && File::exists($file)) {
                File::delete($file); 
            }

            $cover_file = $request->file('cover');
            $cover_filename =  uniqid().'.'.$cover_file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/covers', $cover_file, $cover_filename); 
            
            $course->cover = $cover_filename;
            $course->save();
        }

        $course->refresh();

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function updateCover(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
        ]);

        if ($request->hasFile('cover')) {
            // Delete old cover
            if ($course->cover) {
                $oldFile = public_path('storage/images/courses/covers/' . $course->cover);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $file = $request->file('cover');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/covers', $file, $filename);

            $course->update(['cover' => $filename]);

            return response()->json([
                'success' => true,
                'cover' => $filename,
                'cover_url' => $course->cover_url,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    public function updateLogo(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($course->logo) {
                $oldFile = public_path('storage/images/courses/logos/' . $course->logo);
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $file = $request->file('logo');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/logos', $file, $filename);

            $course->update(['logo' => $filename]);

            return response()->json([
                'success' => true,
                'logo' => $filename,
                'logo_url' => $course->logo_url,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    public function updateHeader(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['header' => 'required|string|max:255']);
        $course->update(['cover_header' => $request->header]);

        return response()->json(['success' => true]);
    }

    public function updateSubheader(Course $course, Request $request)
    {
        if (!$course->isAdmin(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['subheader' => 'required|string|max:255']);
        $course->update(['cover_subheader' => $request->subheader]);

        return response()->json(['success' => true]);
    }

    public function profile(Course $course)
    {
        return response()->json([
            'success' => true,
            'course' => new CourseResource($course->load(['user', 'academy'])),
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        if (!$course->isAdmin(auth()->user())) { // Usually only owner can destroy course, but if user wants admin = owner, then this is fine.
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $lessons = $course->lessons;
        if ($lessons) {
            foreach ($lessons as $lesson) {
                if ($lesson->images) {
                    foreach ($lesson->images as $image) {
                        Storage::disk('public')->delete('images/courses/lessons/'. $image->image_url);
                        $image->delete();   
                    }
                }
                $lesson->delete();
            }
        }

        $course->courseSettings->delete();
        $course->delete();
        return response()->json([
            'success' => true,
        ], 200);
    }

    //function to process all member progrss and grade
    public function progress(Course $course, Request $request)
    {
        // Only select needed user columns to reduce data transfer
        $query = $course->courseMembers()->with(['user:id,name,email,profile_photo_path']);

        // Filter by Group
        if ($request->has('group_id') && $request->group_id && $request->group_id !== 'all') {
            if ($request->group_id === 'ungrouped') {
                $query->whereNull('group_id');
            } else {
                $query->where('group_id', $request->group_id);
            }
        }

        // Search
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('member_name', 'like', $searchTerm)
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                    $uq->where('name', 'like', $searchTerm)
                       ->orWhere('email', 'like', $searchTerm);
                })->orWhere('member_code', 'like', $searchTerm)
                  ->orWhereRaw('order_number LIKE ?', ["%{$request->search}%"]);
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        
        if ($sortField === 'name') {
            $query->join('users', 'course_members.user_id', '=', 'users.id')
                  ->orderByRaw('COALESCE(course_members.member_name, users.name) ' . ($sortOrder === 'desc' ? 'DESC' : 'ASC'))
                  ->select('course_members.*');
        } elseif ($sortField === 'progress') {
            $query->orderBy('grade_progress', $sortOrder);
        } else {
            $query->orderBy('course_members.' . $sortField, $sortOrder);
        }

        $courseMembers = $query->paginate($request->get('per_page', 20));
        
        // 1. Fetch course structure data (cached per request - these don't change per page)
        $courseAssignments = $course->courseAssignments()->select('id', 'assignmentable_type', 'assignmentable_id', 'points')->get();
        $courseQuizzes = $course->courseQuizzes()->select('id', 'course_id', 'total_score')->get();
        $lessons = $course->courseLessons()->select('id', 'course_id')->with([
            'assignments:id,assignmentable_type,assignmentable_id,points',
            'questions:id,questionable_type,questionable_id,points,correct_option_id'
        ])->get();
        
        $lessonAssignments = $lessons->flatMap->assignments;
        $lessonQuestions = $lessons->flatMap->questions;

        $courseAssignmentIds = $courseAssignments->pluck('id');
        $lessonAssignmentIds = $lessonAssignments->pluck('id');
        $lessonQuestionIds = $lessonQuestions->pluck('id');
        $allAssignmentIds = $courseAssignmentIds->merge($lessonAssignmentIds);
        
        // 2. Fetch only data for current page members using DB aggregates where possible
        $memberUserIds = $courseMembers->pluck('user_id');
        $memberIds = $courseMembers->pluck('id');

        // Use DB-level aggregation for scores instead of loading all models
        // Assignment scores - aggregate at DB level
        $assignmentScoresByUser = \App\Models\AssignmentAnswer::whereIn('assignment_id', $allAssignmentIds)
            ->whereIn('user_id', $memberUserIds)
            ->where(function($q) {
                $q->where('status', 'graded')
                  ->orWhereNotNull('points');
            })
            ->select('user_id', 'assignment_id', 'points')
            ->get()
            ->groupBy('user_id');

        // Quiz results - only select needed columns
        $allQuizResults = \App\Models\CourseQuizResult::where('course_id', $course->id)
            ->whereIn('user_id', $memberUserIds)
            ->select('user_id', 'quiz_id', 'score')
            ->get()
            ->groupBy('user_id');

        // Question answers - pre-join question data to avoid N+1
        $allQuestionAnswers = \App\Models\UserAnswerQuestion::whereIn('question_id', $lessonQuestionIds)
            ->whereIn('user_id', $memberUserIds)
            ->select('user_id', 'question_id', 'answer_id')
            ->get()
            ->groupBy('user_id');

        // Build a lookup for question correct answers (faster than loading relations)
        $questionLookup = $lessonQuestions->keyBy('id');

        // Lesson Progress - count only
        $lessonIds = $lessons->pluck('id');
        $lessonProgressCounts = \App\Models\LessonProgress::whereIn('lesson_id', $lessonIds)
            ->whereIn('user_id', $memberUserIds)
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as completed_count')
            ->groupBy('user_id')
            ->pluck('completed_count', 'user_id');

        $totalLessons = $lessons->count();
        $totalAssignments = $courseAssignments->count() + $lessonAssignments->count();
        $totalQuizzes = $courseQuizzes->count();

        // Attendance data - pre-compute per group
        $allCourseAttendances = $course->courseAttendances()->select('id', 'course_id', 'group_id')->get();
        $attendancesByGroup = $allCourseAttendances->groupBy('group_id');
        
        // Pre-compute group session IDs and counts (avoid repeated pluck per member)
        $groupSessionIdsMap = [];
        $groupSessionCountMap = [];
        foreach ($attendancesByGroup as $groupId => $sessions) {
            $groupSessionIdsMap[$groupId] = $sessions->pluck('id')->toArray();
            $groupSessionCountMap[$groupId] = $sessions->count();
        }
        
        // Attendance details - use DB aggregate
        $attendancePresenceByMember = \App\Models\AttendanceDetail::whereIn('course_attendance_id', $allCourseAttendances->pluck('id'))
            ->whereIn('course_member_id', $memberIds)
            ->whereIn('status', [1, 2])
            ->select('course_member_id', 'course_attendance_id')
            ->distinct()
            ->get()
            ->groupBy('course_member_id');

        // Compute max totals (once, outside loop)
        $maxLessonAssignments = $lessonAssignments->sum('points');
        $maxLessonQuizzes = $lessonQuestions->sum('points');
        $maxCourseAssignments = $courseAssignments->sum('points');
        $maxCourseQuizzes = $courseQuizzes->sum('total_score');
        $computedMaxTotal = $maxCourseAssignments + $maxLessonAssignments + $maxCourseQuizzes + $maxLessonQuizzes;

        $courseMembersProgress = [];
        foreach ($courseMembers as $member) {
            $userId = $member->user_id;
            $memberId = $member->id;

            // Calculate Scores using pre-grouped data
            $userAssignments = $assignmentScoresByUser[$userId] ?? collect([]);
            $courseAssignScore = $userAssignments->whereIn('assignment_id', $courseAssignmentIds)->sum('points');
            $lessonAssignScore = $userAssignments->whereIn('assignment_id', $lessonAssignmentIds)->sum('points');

            $courseQuizScore = isset($allQuizResults[$userId]) 
                ? $allQuizResults[$userId]->sum('score') 
                : 0;

            // Lesson test score using pre-built lookup (no relation loading)
            $lessonTestScore = 0;
            if (isset($allQuestionAnswers[$userId])) {
                foreach ($allQuestionAnswers[$userId] as $ans) {
                    $question = $questionLookup[$ans->question_id] ?? null;
                    if ($question && $ans->answer_id == $question->correct_option_id) {
                        $lessonTestScore += $question->points ?? 1;
                    }
                }
            }

            // Progress counts
            $lessonsCompleted = $lessonProgressCounts[$userId] ?? 0;
            $assignmentsCompleted = $userAssignments->unique('assignment_id')->count();
            $quizzesCompleted = isset($allQuizResults[$userId]) 
                ? $allQuizResults[$userId]->unique('quiz_id')->count() 
                : 0;
            
            $lessonsProgressPct = ($totalLessons > 0) ? round(($lessonsCompleted / $totalLessons) * 100) : 0;
            $assignmentsProgressPct = ($totalAssignments > 0) ? round(($assignmentsCompleted / $totalAssignments) * 100) : 0;
            $quizzesProgressPct = ($totalQuizzes > 0) ? round(($quizzesCompleted / $totalQuizzes) * 100) : 0;

            // Attendance - using pre-computed maps
            $memberGroupId = $member->group_id;
            $totalGroupAttendanceSessions = $groupSessionCountMap[$memberGroupId] ?? 0;
            $groupSessionIds = $groupSessionIdsMap[$memberGroupId] ?? [];
            
            $attendancePresent = 0;
            if (isset($attendancePresenceByMember[$memberId]) && !empty($groupSessionIds)) {
                $attendancePresent = $attendancePresenceByMember[$memberId]
                    ->whereIn('course_attendance_id', $groupSessionIds)
                    ->count();
            }
            
            $attendanceRate = ($totalGroupAttendanceSessions > 0) ? round(($attendancePresent / $totalGroupAttendanceSessions) * 100) : 0;

            // Calculate totals and grade
            $rawTotal = $courseAssignScore + $lessonAssignScore + $courseQuizScore + $lessonTestScore + ($member->bonus_points ?? 0);
            $percentage = ($computedMaxTotal > 0) ? ($rawTotal / $computedMaxTotal) * 100 : 0;
            $percentage = min(100, max(0, $percentage));
            $realtimeGrade = \App\Models\CourseMember::calculateGradeFromPercentage($percentage);
            
            $finalGrade = $member->edited_grade ?? $realtimeGrade;
            $finalGradeName = \App\Models\CourseMember::getGradeNameFromGrade($finalGrade);

            $courseMembersProgress[] = [
                'member' => $member,
                'lessons_completed' => $lessonsCompleted,
                'total_lessons' => $totalLessons,
                'lessons_progress' => $lessonsProgressPct,
                'assignments_completed' => $assignmentsCompleted,
                'total_assignments' => $totalAssignments,
                'assignments_progress' => $assignmentsProgressPct,
                'quizzes_completed' => $quizzesCompleted,
                'total_quizzes' => $totalQuizzes,
                'quizzes_progress' => $quizzesProgressPct,
                'attendance_present' => $attendancePresent,
                'total_attendance' => $totalGroupAttendanceSessions,
                'attendance_rate' => $attendanceRate,
                'overall_progress' => round($percentage),
                'scores' => [
                    'lesson_assignments' => $lessonAssignScore,
                    'lesson_quizzes' => $lessonTestScore,
                    'course_assignments' => $courseAssignScore,
                    'course_quizzes' => $courseQuizScore,
                    'bonus_points' => $member->bonus_points ?? 0,
                    'edited_grade' => $member->edited_grade,
                    'total_score' => $rawTotal,
                    'score_percentage' => round($percentage),
                    'db_achieved_score' => $member->achieved_score,
                    'grade_progress' => $finalGrade,
                    'calculated_grade' => $realtimeGrade,
                    'grade_name' => $finalGradeName,
                    'max_lesson_assignments' => $maxLessonAssignments,
                    'max_lesson_quizzes' => $maxLessonQuizzes,
                    'max_course_assignments' => $maxCourseAssignments,
                    'max_course_quizzes' => $maxCourseQuizzes,
                    'max_total' => $computedMaxTotal,
                ]
            ];
        }

        // Calculate Class Stats
        $totalMembers = $course->courseMembers()->count();
        $completedMembers = $course->courseMembers()->where('course_member_status', 1)->count();

        return response()->json([
            'isCourseAdmin' => $course->isAdmin(auth()->user()),
            'canViewReports' => $course->hasPermission(auth()->user(), 'view_reports'),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'courseMembersProgress' => $courseMembersProgress,
            'courseMemberOfAuth'=> $course->courseMembers()->where('user_id', auth()->id())->first(),
            'pagination' => [
                'total' => $courseMembers->total(),
                'per_page' => $courseMembers->perPage(),
                'current_page' => $courseMembers->currentPage(),
                'last_page' => $courseMembers->lastPage(),
                'from' => $courseMembers->firstItem(),
                'to' => $courseMembers->lastItem(),
            ],
            'stats' => [
                'total' => $totalMembers,
                'completed' => $completedMembers,
            ]
        ]);
    }

    /**
     * Get top performers for a course (sorted by total_score)
     */
    public function topPerformers(Course $course, Request $request)
    {
        $limit = $request->get('limit', 5);
        
        // Fetch all course members with user info
        $courseMembers = $course->courseMembers()
            ->with('user')
            ->get();
        
        if ($courseMembers->isEmpty()) {
            return response()->json([
                'success' => true,
                'topPerformers' => []
            ]);
        }
        
        // Fetch related data for score calculation
        $courseAssignments = $course->courseAssignments;
        $courseQuizzes = $course->courseQuizzes;
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();
        
        $lessonAssignments = $lessons->flatMap->assignments;
        $lessonQuestions = $lessons->flatMap->questions;

        $courseAssignmentIds = $courseAssignments->pluck('id');
        $lessonAssignmentIds = $lessonAssignments->pluck('id');
        $lessonQuestionIds = $lessonQuestions->pluck('id');
        
        $memberUserIds = $courseMembers->pluck('user_id');

        // Get all graded assignment answers
        $allAssignmentAnswers = \App\Models\AssignmentAnswer::whereIn('assignment_id', $courseAssignmentIds->merge($lessonAssignmentIds))
            ->whereIn('user_id', $memberUserIds)
            ->where(function($query) {
                $query->where('status', 'graded')
                      ->orWhereNotNull('points');
            })
            ->get()
            ->groupBy('user_id');

        // Get quiz results
        $allQuizResults = \App\Models\CourseQuizResult::where('course_id', $course->id)
            ->whereIn('user_id', $memberUserIds)
            ->get()
            ->groupBy('user_id');

        // Get lesson question answers
        $allQuestionAnswers = \App\Models\UserAnswerQuestion::whereIn('question_id', $lessonQuestionIds)
            ->whereIn('user_id', $memberUserIds)
            ->with('question')
            ->get()
            ->groupBy('user_id');

        // Compute actual max total from all score sources
        $maxCourseAssign = $courseAssignments->sum('points');
        $maxLessonAssign = $lessonAssignments->sum('points');
        $maxCourseQuiz = $courseQuizzes->sum('total_score');
        $maxLessonQuiz = $lessonQuestions->sum('points');
        $computedMaxTotal = $maxCourseAssign + $maxLessonAssign + $maxCourseQuiz + $maxLessonQuiz;

        // Calculate scores for each member
        $membersWithScores = [];
        foreach ($courseMembers as $member) {
            $userId = $member->user_id;

            $courseAssignScore = isset($allAssignmentAnswers[$userId]) 
                ? $allAssignmentAnswers[$userId]->whereIn('assignment_id', $courseAssignmentIds)->sum('points') 
                : 0;

            $lessonAssignScore = isset($allAssignmentAnswers[$userId]) 
                ? $allAssignmentAnswers[$userId]->whereIn('assignment_id', $lessonAssignmentIds)->sum('points') 
                : 0;

            $courseQuizScore = isset($allQuizResults[$userId]) 
                ? $allQuizResults[$userId]->sum('score') 
                : 0;

            $lessonTestScore = 0;
            if (isset($allQuestionAnswers[$userId])) {
                foreach ($allQuestionAnswers[$userId] as $ans) {
                    if ($ans->question && $ans->answer_id == $ans->question->correct_option_id) {
                        $lessonTestScore += $ans->question->points ?? 1;
                    }
                }
            }

            $totalScore = $courseAssignScore + $lessonAssignScore + $courseQuizScore + $lessonTestScore + ($member->bonus_points ?? 0);
            
            // Calculate grade
            $percentage = ($computedMaxTotal > 0) ? ($totalScore / $computedMaxTotal) * 100 : 0;
            $percentage = min(100, max(0, $percentage));
            $grade = \App\Models\CourseMember::calculateGradeFromPercentage($percentage);
            $finalGrade = $member->edited_grade ?? $grade;
            $gradeName = \App\Models\CourseMember::getGradeNameFromGrade($finalGrade);

            $membersWithScores[] = [
                'id' => $member->id,
                'user' => [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'avatar' => $member->user->avatar ?? $member->user->profile_photo_url,
                ],
                'overall_progress' => round($percentage),
                'scores' => [
                    'total_score' => $totalScore,
                    'bonus_points' => $member->bonus_points ?? 0,
                    'grade_progress' => $finalGrade,
                    'grade_name' => $gradeName,
                ]
            ];
        }
        
        // Sort by total_score descending
        usort($membersWithScores, function($a, $b) {
            return ($b['scores']['total_score'] ?? 0) - ($a['scores']['total_score'] ?? 0);
        });
        
        // Take top N
        $topPerformers = array_slice($membersWithScores, 0, $limit);
        
        return response()->json([
            'success' => true,
            'topPerformers' => $topPerformers
        ]);
    }

    public function settings(Course $course)
    {
        return response()->json([
            'course'                => new CourseResource($course),
            'isCourseAdmin'         => $course->isAdmin(auth()->user()),
            'courseMemberOfAuth'   => $course->courseMembers()->where('user_id', auth()->id())->first(),
            'pendingInvitation'    => \App\Models\CourseInvitation::where('course_id', $course->id)
                ->where('invitee_id', auth()->id())
                ->where('status', 'pending')
                ->first(),
        ]);
    }

    public function basicInfo(Course $course){
        return response()->json([
            'course'                => new CourseResource($course),
            'isCourseAdmin'         => $course->isAdmin(auth()->user()),
            'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
            'pendingInvitation'    => \App\Models\CourseInvitation::where('course_id', $course->id)
                ->where('invitee_id', auth()->id())
                ->where('status', 'pending')
                ->first(),
        ]);
    }

    /**
     * V2: API endpoint for course listing with pagination and filtering
     */
    public function indexV2(Request $request)
    {
        $query = Course::with(['user', 'academy', 'courseSettings']);

        // Apply filters
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('level') && $request->level) {
            $query->where('level', $request->level);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by name or description
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        $courses = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CourseResourceV2::collection($courses),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    /**
     * V2: API endpoint for course details with enhanced information
     */
    public function showV2(Course $course, Request $request)
    {
        $course->load([
            'user',
            'academy',
            'courseSettings',
            'courseGroups' => function ($query) {
                $query->withCount('members');
            },
            'courseMembers' => function ($query) {
                $query->with('user')->orderBy('order_number');
            }
        ]);

        // Calculate additional statistics
        $stats = [
            'total_groups' => $course->courseGroups->count(),
            'total_members' => $course->courseMembers->count(),
            'active_members' => $course->courseMembers->where('status', 1)->count(),
            'completion_rate' => $this->calculateCourseCompletionRate($course),
        ];

        return response()->json([
            'success' => true,
            'data' => new CourseResourceV2($course),
            'stats' => $stats,
        ]);
    }

    /**
     * V2: API endpoint for updating course information
     */
    public function updateV2(Course $course, Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'level' => 'nullable|string',
            'credit_units' => 'nullable|numeric',
            'hours_per_week' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string',
            'saleable' => 'nullable|boolean',
            'price' => 'nullable|numeric',
        ]);

        $course->update($validated);

        // Update course settings if provided
        if ($request->has('auto_accept_members')) {
            $course->courseSettings()->update([
                'auto_accept_members' => $request->auto_accept_members ? 1 : 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new CourseResourceV2($course->fresh()),
        ]);
    }

    /**
     * V2: API endpoint for course summary statistics
     */
    public function summaryV2(Course $course, Request $request)
    {
        $course->load([
            'courseGroups',
            'courseMembers',
            'courseLessons',
            'courseAssignments',
            'courseQuizzes'
        ]);

        $summary = [
            'basic_info' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
                'category' => $course->category,
                'level' => $course->level,
                'status' => $course->status,
                'enrolled_students' => $course->enrolled_students,
            ],
            'statistics' => [
                'total_groups' => $course->courseGroups->count(),
                'total_members' => $course->courseMembers->count(),
                'active_members' => $course->courseMembers->where('status', 1)->count(),
                'total_lessons' => $course->courseLessons->count(),
                'total_assignments' => $course->courseAssignments->count(),
                'total_quizzes' => $course->courseQuizzes->count(),
            ],
            'progress' => [
                'average_completion' => $this->calculateCourseCompletionRate($course),
                'average_grade' => $this->calculateCourseAverageGrade($course),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Helper method to calculate course completion rate
     */
    private function calculateCourseCompletionRate(Course $course): float
    {
        $members = $course->courseMembers()->where('status', 1)->get();
        
        if ($members->isEmpty()) return 0;

        $totalCompletion = 0;
        foreach ($members as $member) {
            $totalCompletion += $this->calculateMemberCompletionRate($course, $member);
        }

        return round($totalCompletion / $members->count(), 2);
    }

    /**
     * Helper method to calculate member completion rate
     */
    private function calculateMemberCompletionRate(Course $course, $member): float
    {
        $totalItems = $course->courseLessons()->count() +
                     $course->courseAssignments()->count() +
                     $course->courseQuizzes()->count();
        
        if ($totalItems === 0) return 0;

        $completedItems = 0;
        
        // Count completed lessons
        if ($member->lessons_completed) {
            $completedItems += count(json_decode($member->lessons_completed, true) ?? []);
        }
        
        // Count completed assignments
        if ($member->assignments_completed) {
            $completedItems += count(json_decode($member->assignments_completed, true) ?? []);
        }
        
        // Count completed quizzes
        if ($member->quizzes_completed) {
            $completedItems += count(json_decode($member->quizzes_completed, true) ?? []);
        }

        return round(($completedItems / $totalItems) * 100, 2);
    }

    /**
     * Helper method to calculate course average grade
     */
    private function calculateCourseAverageGrade(Course $course): float
    {
        $members = $course->courseMembers()->where('status', 1)->get();
        
        if ($members->isEmpty()) return 0;

        $totalGrade = 0;
        foreach ($members as $member) {
            if ($member->grade_progress) {
                $totalGrade += (float) $member->grade_progress;
            }
        }

        return round($totalGrade / $members->count(), 2);
    }

    public function exportLearningResults(Course $course, Request $request)
    {
        if (!$course->hasPermission(auth()->user(), 'export_reports')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = $course->courseMembers()->with(['user', 'group']);

        // Filter by Group
        if ($request->has('group_id') && $request->group_id && $request->group_id !== 'all') {
            if ($request->group_id === 'ungrouped') {
                $query->whereNull('group_id');
            } else {
                $query->where('group_id', $request->group_id);
            }
        }

        // Fetch ALL members for export
        $courseMembers = $query->orderBy('order_number')->get();
        
        // --- Calculation Logic (Reused from progress method) ---
        $courseAssignments = $course->courseAssignments;
        $courseQuizzes = $course->courseQuizzes;
        $lessons = $course->courseLessons()->with(['assignments', 'questions'])->get();
        
        $lessonAssignments = $lessons->flatMap->assignments;
        $lessonQuestions = $lessons->flatMap->questions;

        $courseAssignmentIds = $courseAssignments->pluck('id');
        $lessonAssignmentIds = $lessonAssignments->pluck('id');
        $lessonQuestionIds = $lessonQuestions->pluck('id');
        
        $memberUserIds = $courseMembers->pluck('user_id');

        $allAssignmentAnswers = \App\Models\AssignmentAnswer::whereIn('assignment_id', $courseAssignmentIds->merge($lessonAssignmentIds))
            ->whereIn('user_id', $memberUserIds)
            ->where(function($query) {
                $query->where('status', 'graded')
                      ->orWhereNotNull('points');
            })
            ->get()
            ->groupBy('user_id');

        $allQuizResults = \App\Models\CourseQuizResult::where('course_id', $course->id)
            ->whereIn('user_id', $memberUserIds)
            ->get()
            ->groupBy('user_id');

        $allQuestionAnswers = \App\Models\UserAnswerQuestion::whereIn('question_id', $lessonQuestionIds)
            ->whereIn('user_id', $memberUserIds)
            ->with('question')
            ->get()
            ->groupBy('user_id');

        $lessonIds = $lessons->pluck('id');
        $allLessonProgress = \App\Models\LessonProgress::whereIn('lesson_id', $lessonIds)
            ->whereIn('user_id', $memberUserIds)
            ->where('status', 'completed')
            ->get()
            ->groupBy('user_id');

        $totalLessons = $lessons->count();
        $totalAssignments = $courseAssignments->count() + $lessonAssignments->count();
        $totalQuizzes = $courseQuizzes->count();

        $allCourseAttendances = $course->courseAttendances()->get();
        $attendancesByGroup = $allCourseAttendances->groupBy('group_id');
        
        $allAttendanceDetails = \App\Models\AttendanceDetail::whereIn('course_attendance_id', $allCourseAttendances->pluck('id'))
            ->whereIn('course_member_id', $courseMembers->pluck('id'))
            ->get()
            ->groupBy('course_member_id');

        // Compute actual max total from all score sources
        $computedMaxTotal2 = $courseAssignments->sum('points') + $lessonAssignments->sum('points')
            + $courseQuizzes->sum('total_score') + $lessonQuestions->sum('points');

        $exportData = [];
        foreach ($courseMembers as $member) {
            $userId = $member->user_id;

            $courseAssignScore = isset($allAssignmentAnswers[$userId]) 
                ? $allAssignmentAnswers[$userId]->whereIn('assignment_id', $courseAssignmentIds)->sum('points') 
                : 0;

            $lessonAssignScore = isset($allAssignmentAnswers[$userId]) 
                ? $allAssignmentAnswers[$userId]->whereIn('assignment_id', $lessonAssignmentIds)->sum('points') 
                : 0;

            $courseQuizScore = isset($allQuizResults[$userId]) 
                ? $allQuizResults[$userId]->sum('score') 
                : 0;

            $lessonTestScore = 0;
            if (isset($allQuestionAnswers[$userId])) {
                foreach ($allQuestionAnswers[$userId] as $ans) {
                    if ($ans->question && $ans->answer_id == $ans->question->correct_option_id) {
                        $lessonTestScore += $ans->question->points ?? 1;
                    }
                }
            }

            $lessonsCompleted = isset($allLessonProgress[$userId]) ? $allLessonProgress[$userId]->count() : 0;
            $assignmentsCompleted = isset($allAssignmentAnswers[$userId]) 
                ? $allAssignmentAnswers[$userId]->unique('assignment_id')->count() 
                : 0;
            $quizzesCompleted = isset($allQuizResults[$userId]) 
                ? $allQuizResults[$userId]->unique('quiz_id')->count() 
                : 0;
            
            $lessonsProgressPct = ($totalLessons > 0) ? round(($lessonsCompleted / $totalLessons) * 100) : 0;
            $assignmentsProgressPct = ($totalAssignments > 0) ? round(($assignmentsCompleted / $totalAssignments) * 100) : 0;
            $quizzesProgressPct = ($totalQuizzes > 0) ? round(($quizzesCompleted / $totalQuizzes) * 100) : 0;

            $memberId = $member->id;
            $memberGroupId = $member->group_id;
            $groupAttendanceSessions = isset($attendancesByGroup[$memberGroupId]) ? $attendancesByGroup[$memberGroupId] : collect([]);
            $totalGroupAttendanceSessions = $groupAttendanceSessions->count();
            $memberAttendance = isset($allAttendanceDetails[$memberId]) ? $allAttendanceDetails[$memberId] : collect([]);
            $groupSessionIds = $groupAttendanceSessions->pluck('id');
            $memberGroupAttendance = $memberAttendance->whereIn('course_attendance_id', $groupSessionIds);
            
            $attendancePresent = $memberGroupAttendance->whereIn('status', [1, 2])->pluck('course_attendance_id')->unique()->count();
            $attendanceRate = ($totalGroupAttendanceSessions > 0) ? round(($attendancePresent / $totalGroupAttendanceSessions) * 100) : 0;

            $rawTotal = $courseAssignScore + $lessonAssignScore + $courseQuizScore + $lessonTestScore + ($member->bonus_points ?? 0);
            $percentage = ($computedMaxTotal2 > 0) ? ($rawTotal / $computedMaxTotal2) * 100 : 0;
            $percentage = min(100, max(0, $percentage));
            $realtimeGrade = \App\Models\CourseMember::calculateGradeFromPercentage($percentage);
            $finalGrade = $member->edited_grade ?? $realtimeGrade;
            $finalGradeName = \App\Models\CourseMember::getGradeNameFromGrade($finalGrade);

            $exportData[] = [
                'member' => $member,
                'attendance_rate' => $attendanceRate,
                'lessons_progress' => $lessonsProgressPct,
                'assignments_progress' => $assignmentsProgressPct,
                'quizzes_progress' => $quizzesProgressPct,
                'scores' => [
                    'lesson_assignments' => $lessonAssignScore,
                    'lesson_quizzes' => $lessonTestScore,
                    'course_assignments' => $courseAssignScore,
                    'course_quizzes' => $courseQuizScore,
                    'bonus_points' => $member->bonus_points ?? 0,
                    'total_score' => $rawTotal,
                    'percentage' => (int) round($percentage),
                    'grade_progress' => $finalGrade,
                    'grade_name' => $finalGradeName,
                    'max_lesson_assignments' => $lessonAssignments->sum('points'),
                    'max_lesson_quizzes' => $lessonQuestions->sum('points'),
                    'max_course_assignments' => $courseAssignments->sum('points'),
                    'max_course_quizzes' => $courseQuizzes->sum('total_score'),
                    'max_total' => $computedMaxTotal2,
                ]
            ];
        }

        $filename = 'learning-results-' . $course->id . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new LearningResultsExport($exportData, $course->name),
            $filename
        );
    }

}
