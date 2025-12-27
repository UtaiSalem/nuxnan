<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\CourseQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Learn\questions\QuestionResource;

class CourseQuizQuestionController extends Controller
{
    public function store(Course $course, CourseQuiz $quiz, Request $request)
    {
        $validatedData = $request->validate([
            'text' =>'required|string',
            'points' =>'required|integer',
            'pp_fine' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $new_question = $quiz->questions()->create([
            'user_id'   => auth()->id(),
            'course_id' => $course->id,
            'text'      => $request->text,
            'points'    => $request->points,
            'pp_fine'  => $request->pp_fine ?? 0,
        ]);

        $course->increment('total_score', $request->points);
        $quiz->increment('total_score', $request->points);
        $quiz->increment('total_questions');

        if($request->hasFile('images')) {
            $q_images = $request->file('images');
            foreach ($q_images as $q_image) {
                $q_img_filename = uniqid() . '.' . $q_image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $q_image, $q_img_filename);
                $new_question->images()->create([
                    'filename' => $q_img_filename,
                ]);
            }
        }

        // if($request->question_id){

        //     $old_question = Question::find($request->question_id);

        //     if($old_question->images){
        //         foreach ($old_question->images as $old_q_image) {
        //             $q_img_file_extention = File::extension($old_q_image->url);
        //             $new_q_img_filename = uniqid() . '.' . $q_img_file_extention;
        //             $new_q_img_url = 'images/courses/quizzes/questions/' . $new_q_img_filename;

        //             Storage::disk('public')->copy('images/courses/quizzes/questions'. $old_q_image, $new_q_img_url);
        //             $new_question->images()->create([
        //                 'filename' => $new_q_img_filename,
        //             ]);
        //         }
        //     }

        //     if($old_question->options){
        //         foreach ($old_question->options as $old_q_option) {

        //             $new_q_option = $new_question->options()->create([
        //                 'text' => $old_q_option->text,
        //                 'is_correct' => $old_q_option->is_correct,
        //             ]);

        //             if($old_q_option->images){
        //                 foreach ($old_q_option->images as $old_q_opt_image) {
        //                     $opt_img_file_extention = File::extension($old_q_opt_image->url);
        //                     $new_opt_img_filename = uniqid() . '.' . $opt_img_file_extention;
        //                     $new_opt_image_url = 'images/courses/quizzes/questions/'. $new_opt_img_filename;

        //                     Storage::disk('public')->copy('images/courses/quizzes/questions/'. $old_q_opt_image->filename, $new_opt_image_url);

        //                     $new_q_option->images()->create([
        //                         'filename' => $new_opt_img_filename
        //                     ]);              
        //                 }
        //             }
        //         }
        //     }
        // };

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($new_question),
        ], 200);
    }

    public function update(Course $course, CourseQuiz $quiz, Question $question, Request $request)
    {
        $course->decrement('total_score', $question->points);
        $quiz->decrement('total_score', $question->points);

        $validatedData = $request->validate([
            'text' =>'required|string',
            'points' =>'required|integer',
            'pp_fine' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $question->update([
            'text' => $request->text,
            'points' => $request->points,
            'pp_fine' => $request->pp_fine ?? 0,
        ]);

        $course->increment('total_score', $request->points);
        $quiz->increment('total_score', $request->points);     

        if($request->hasFile('images')) {
            $q_images = $request->file('images');
            foreach ($q_images as $q_image) {
                $q_img_filename = uniqid() . '.' . $q_image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $q_image, $q_img_filename);
                $question->images()->create([
                    'filename' => $q_img_filename,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question),
        ], 200);
    }

    public function destroy(Course $course, CourseQuiz $quiz, Question $question)
    {
        if ($question->images) {
            foreach ($question->images as $q_image) {
                Storage::disk('public')->delete('images/courses/quizzes/questions/'. $q_image->filename);
            }
            $question->images()->delete();
        }

        if ($question->options) {
            foreach ($question->options as $q_option) {
                if ($q_option->images) {
                    foreach ($q_option->images as $q_opt_image) {
                        Storage::disk('public')->delete('images/courses/quizzes/questions/'. $q_opt_image->filename);
                    }
                    $q_option->images()->delete();
                }
            }
            $question->options()->delete();
        }
        
        // $userAnswerQuestion 
        $userAnswerQuestion = $question->userAnswers;
        foreach ($userAnswerQuestion as $answer) {
            $answer->delete();
        }
        
        $course->decrement('total_score', $question->points);
        $quiz->decrement('total_score', $question->points);
        $quiz->decrement('total_questions');
        
        $question->delete();

        return response()->json([
            'success' => true,
        ], 204);
    }
}
