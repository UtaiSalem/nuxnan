<?php

namespace App\Http\Controllers\Api\Learn\Course\assignments;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use App\Models\AssignmentAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\Course\assignments\AssignmentAnswerResource;

class AssignmentAnswerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Assignment $assignment, Request $request)
    {
        $query = $assignment->answers()->with('user', 'images')->latest();

        if ($request->filled('group_id') && $request->group_id != 'all') {
             $groupId = $request->group_id;
             $query->whereHas('user.courseMembers', function($q) use ($groupId) {
                 $q->where('group_id', $groupId);
             });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return AssignmentAnswerResource::collection($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Assignment $assignment, Request $request)
    {
        $answer = $assignment->answers()->where('user_id', auth()->id())->first();
        if( $answer ){
            $oldPoints = $answer->points ?? 0;
            $courseMember = CourseMember::where('course_id', $request->course_id)->where('user_id', $answer->user_id)->first();
            if ($courseMember) {
                 $courseMember->achieved_score -= $oldPoints;
                 $courseMember->save();
            }

            $answer->update([
                'content' => $request->content, 
                'points' => null
            ]);
            
            // Handle deleted images
            if ($request->filled('deleted_images')) {
                $deletedIds = $request->input('deleted_images');
                if (is_array($deletedIds)) {
                    $imagesToDelete = $answer->images()->whereIn('id', $deletedIds)->get();
                    foreach ($imagesToDelete as $img) {
                        try {
                             Storage::disk('public')->delete('images/courses/assignments/answers/' . $img->filename);
                        } catch (\Exception $e) {
                            // Log error but continue
                        }
                        $img->delete();
                    }
                }
            }
        }else{
            $answer = $assignment->answers()->create([
                'user_id' => auth()->id(),
                'content' => $request->content, 
                'points' => null
            ]);

        }

        if($request->hasFile('images')) {
            $images = $request->file('images');
            foreach ($images as $image) {
                $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image_url = Storage::disk('public')->putFileAs('images/courses/assignments/answers/', $image, $fileName);
                $answer->images()->create([
                    'filename' => $fileName
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'newAnswer' => new AssignmentAnswerResource($answer),
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment, AssignmentAnswer $answer, Request $request)
    {
        try {
            DB::beginTransaction();
            
            $courseMember = CourseMember::where('course_id', $request->course_id)
                ->where('user_id', $answer->user_id)
                ->first();
            
            if ($courseMember) {
                $courseMember->achieved_score -= $answer->points;
                $courseMember->save();
            }
    
            foreach ($answer->images as $image) {
                Storage::disk('public')->delete('images/courses/assignments/answers/'.$image->filename);
            }
    
            $answer->images()->delete();
            $answer->delete();
    
            DB::commit();
            
            return response()->json([
                'success' => true,
            ], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting assignment answer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete assignment answer'], 500);
        }
    }

    public function setAnswerPoints(Assignment $assignment, AssignmentAnswer $answer, Request $request)
    {
        // Resolve Course ID
        $courseId = $request->course_id;
        if (!$courseId) {
             if ($assignment->assignmentable_type === 'App\Models\Lesson') {
                $courseId = $assignment->assignmentable->course_id;
            } elseif ($assignment->assignmentable_type === 'App\Models\Course') {
                $courseId = $assignment->assignmentable->id;
            }
        }

        $courseMember = CourseMember::where('course_id', $courseId)->where('user_id', $answer->user_id)->first();
        
        // Only update score if member found (e.g. not admin grading themselves or test data)
        if ($courseMember) {
            $oldAnswer = $assignment->answers()->where('user_id', $answer->user_id)->orderBy('updated_at', 'desc')->get();
            if (count($oldAnswer) > 1) {
                $oldPoints = $oldAnswer[0]->points;
                $newPoints = $request->points ?? 0;
            }else{
                $oldPoints = $answer->points ?? 0;
                $newPoints = $request->points ?? 0;      
            }

            $courseMember->achieved_score -= $oldPoints;
            $courseMember->achieved_score += $newPoints;
            $courseMember->save();
        }

        $answer->update([
            'points' => $request->points,
            'feedback' => $request->feedback,
            'status' => 'graded', // Set status to graded when points are assigned
        ]);

        return response()->json([
            'success' => true,
        ], 200);
    }
}
