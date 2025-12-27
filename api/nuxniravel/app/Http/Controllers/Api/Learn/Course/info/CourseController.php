<?php

namespace App\Http\Controllers\Api\Learn\Course\info;

use App\Http\Controllers\Controller;
use App\Models\User;

use App\Models\Course;
use App\Models\Academy;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\CourseMember;
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

class CourseController extends Controller
{
    public function index()
    {
        // $courses = $activities = $this->getMoreCourses();
        $courses =  $this->getMoreCourses();
        
        return response()->json([
            'courses'       => $courses->original['courses'],
        ]);
    }

    public function getMoreCourses()
    {
        return response()->json([
            'success'       => true,
            'courses'       => CourseResource::collection(Course::latest()->paginate()),
        ], 200);
    }

    public function getUserCourses(User $user)
    {
        return response()->json([
            'courses'           => CourseResource::collection($user->courses()->latest()->paginate()),
        ]);
    }

    public function getMyCourses(User $user)
    {
        return response()->json([
            'success'   => true,
            'courses'   => CourseResource::collection($user->courses()->latest()->paginate()),
        ], 200);
    }


    public function getAuthMemberCourses(User $user)
    {
        $authMemberCourse = CourseMember::where('user_id', auth()->id())->pluck('course_id')->all();
        $coursesAuthMember = CourseResource::collection(Course::whereIn('id', $authMemberCourse)->latest()->paginate());

        return response()->json([
            'courses'           => $coursesAuthMember,
        ]);
    }

    public function getAuthMemberedCourses(User $user)
    {
        $authMemberCourse = CourseMember::where('user_id', auth()->id())->pluck('course_id')->all();
        $coursesAuthMember = CourseResource::collection(Course::whereIn('id', $authMemberCourse)->latest()->paginate());

        return response()->json([
            'success'           => true,
            'courses'           => $coursesAuthMember,
        ], 200);
    }


    /**
     * Show the form for creating a new resource.
     */
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
                'level'             => 'nullable|string',
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
            $newCourse->level            = $request->level;
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
                    'role' => 3,
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
            'level'             => 'nullable',

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
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
    public function progress(Course $course)
    {
        $courseMembers = $course->courseMembers;
        $courseMembersProgress = [];
        foreach ($courseMembers as $member) {
            $memberProgress = $member->memberProgress;
            $courseMembersProgress[] = [
                'member' => $member,
                'progress' => $memberProgress,
            ];
        }

        return response()->json([
            'isCourseAdmin' => $course->user_id === auth()->id(),
            'course'        => new CourseResource($course),
            'groups'        => CourseGroupResource::collection($course->courseGroups),
            'assignments'       => AssignmentResource::collection($course->courseAssignments),      
            'quizzes'           => CourseQuizResource::collection($course->courseQuizzes),
            'members'           => CourseMemberResource::collection($course->courseMembers),
            'courseMembersProgress' => $courseMembersProgress,
            'courseMemberOfAuth'=> $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);

    }

    public function settings(Course $course)
    {
        return response()->json([
            'course'                => new CourseResource($course),
            'isCourseAdmin'         => $course->user_id === auth()->id(),
            'courseMemberOfAuth'   => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function basicInfo(Course $course){
        return response()->json([
            'course'                => new CourseResource($course),
            'isCourseAdmin'         => $course->user_id === auth()->id(),
            'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
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

}
