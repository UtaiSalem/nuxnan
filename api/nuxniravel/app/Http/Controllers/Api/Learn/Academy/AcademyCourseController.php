<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;


use App\Models\Course;
use App\Models\Academy;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Resources\Learn\Course\info\CourseResource;
use App\Http\Resources\Learn\Academy\AcademyResource;
use Illuminate\Support\Facades\Storage;

class AcademyCourseController extends Controller
{
    public function index(Academy $academy)
    {
        // $authMemberCourseIds = CourseMember::where('user_id', auth()->id())->get('user_id');
        // $authMemberCourses = CourseResource::collection(Course::where('user_id', $authMemberCourseIds));
        $courses = $academy->courses;
        $coursesresource = CourseResource::collection($courses);
        $isAcademyAdmin = $academy->user_id == auth()->id();
        
        return response()->json([
            // 'authMemberCourses' => $authMemberCourses,
            'allCourses'        => $coursesresource,
            'courses'           => $coursesresource,
            'authOwnerCourses'  => CourseResource::collection(auth()->user()->courses),
            'authMemberCourses' => [],
            'academy'           => new AcademyResource($academy),
            'isAcademyAdmin'    => $isAcademyAdmin,
        ]);
    }

    public function create(Academy $academy)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();
        
        return response()->json([ 
            'academy'           => new AcademyResource($academy),
            'courses'           => CourseResource::collection($academy->courses()->paginate()),
            'isAcademyAdmin'    => $isAcademyAdmin,
        ]);
    }

    public function store(Academy $academy, Request $request)
    {
        try {

            $validated = $request->validate([
                // 'academy_id'        => 'nullable',
                // 'code'              => 'nullable|string|max:255',
                'name'              => 'required|string|max:255',
                // 'description'       => 'nullable|string',
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
                'cover'                => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:4096',
            ]);

            if($request->file('cover')) {
                $cover_file = $request->file('cover');
                $cover_filename =  uniqid().'.'.$cover_file->getClientOriginalExtension();
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
            $newCourse = new Course();

            $newCourse->user_id         = auth()->id();
            $newCourse->instructor_id   = auth()->id();
            $newCourse->academy_id      = $request->academy_id == 'null' || $request->academy_id == null ? null : $academy->id ;
            $newCourse->name            = $validated['name'];
            // $newCourse->slug            = Str::slug($validated['name'], '-');
            $newCourse->code            = $request->code == 'null' || $request->code == null ? null : $request->code;
            $newCourse->description     = $request->description == 'null' || $request->description == null ? null: $request->description;
            $newCourse->duration        = $request->duration ?? null;
            $newCourse->start_date      = $request->start_date == 'null' || $request->start_date == 'undefined' ? null : Carbon::parse($request->start_date);
            $newCourse->end_date        = $request->end_date == 'null' || $request->end_date == 'undefined' ? null : Carbon::parse($request->end_date);
            $newCourse->credit_units    = $request->credit_units ?? null;
            $newCourse->hours_per_week  = $request->hours_per_week ?? null;
            $newCourse->category        = $request->category == 'null' || $request->category == null ? null : $request->category;
            $newCourse->tuition_fees    = $request->tuition_fees ?? null;
            $newCourse->status          = $request->status ==='true'? 1 : 0;
            $newCourse->saleable        = $request->saleable ==='true'? 1 : 0;
            $newCourse->price           = $request->price == 'null' || $request->price == null ? null: $request->price;
            $newCourse->level           = $request->level == 'null' || $request->level == null ? null: $request->level;
            $newCourse->cover           = $cover_filename ?? null;
            
            $newCourse->save();

            if ($newCourse) {
                $newCourse->courseSettings()->create([
                    'auto_accept_members' => $request->auto_accept_members == 'true' ? 1 : 0,
                ]);

                $academy->increment('courses_offered');
            }

            // to_route('course.show', $newCourse->id);
            return response()->json([
                'success'   => true,
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
            'academy'           => new AcademyResource($academy),
            'course'            => new CourseResource($course),
            'isAcademyAdmin'    => $isAcademyAdmin,
        ]);
    }

    public function edit(Academy $academy, Course $course)
    {
        $isAcademyAdmin = $academy->user_id == auth()->id();
        
        return response()->json([
            'academy'           => new AcademyResource($academy),
            'course'            => new CourseResource($course),
            'isAcademyAdmin'    => $isAcademyAdmin,
        ]);
    }

    public function getAcademyCourses(Academy $academy)
    {
        $courses = $academy->courses()->latest()->paginate();
        $coursesresource = CourseResource::collection($courses);
        
        return response()->json([
            'success'   => true,
            'courses'   => $coursesresource,
        ], 200);
    }
}
