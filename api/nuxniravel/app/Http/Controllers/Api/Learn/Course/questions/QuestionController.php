<?php

namespace App\Http\Controllers\Api\Learn\Course\questions;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\\Http\\Resources\\Learn\\Course\\questions\\QuestionResource;

class QuestionController extends Controller
{

    public function getUserQuestions(Request $request)
    {
        $searchText = $request->input('text');
    
        $questions = Question::where('user_id', auth()->id())
            ->where('text', 'like', '%' . $searchText . '%')
            ->with(['course', 'images', 'options'])
            ->limit(10)
            ->latest()
            ->get();
    
        return response()->json([
            'success' => true,
            'questions' => $questions,
            'questions_resource' => QuestionResource::collection($questions),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'text' =>'required|string',
            'points' =>'required|integer',
            'pp_fine' => 'nullable|integer',
            'course_id' => 'required|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $new_question = Question::create([
            'user_id'   => auth()->id(),
            'course_id' => $request->course_id,
            'text'      => $request->text,
            'points'    => $request->points,
            'pp_fine'  => $request->pp_fine ?? 0,
        ]);

        if($request->hasFile('images')) {
            $q_images = $request->file('images');
            foreach ($q_images as $q_image) {
                $q_img_filename = uniqid() . '.' . $q_image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/questions', $q_image, $q_img_filename);
                $new_question->images()->create([
                    'filename' => $q_img_filename,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($new_question),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Question $question, Request $request)
    {
        $validatedData = $request->validate([
            'text' =>'required|string',
            'points' =>'required|integer',
            'pp_fine' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $question->update([
            'text'      => $request->text,
            'points'    => $request->points,
            'pp_fine'  => $request->pp_fine ?? 0,
        ]);

        if($request->hasFile('images')) {
            $q_images = $request->file('images');
            foreach ($q_images as $q_image) {
                $q_img_filename = uniqid() . '.' . $q_image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/courses/questions', $q_image, $q_img_filename);
                $question->images()->create([
                    'filename' => $q_img_filename,
                ]);
            }
        }

        $question->refresh();

        $questionResource = new QuestionResource($question);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'question' => $questionResource,
        ], 200);
    }

    public function set_correct_option(Question $question, Request $request, )
    {
        $q_options = $question->options;

        $q_options->each(function ($q_option) {
            $q_option->update([
                'is_correct' => 0,
                ]);
            });
            
        $q_option = $q_options->find($request->answer)->update([
            'is_correct' => 1,
        ]);

        // $q_option->update([
        //     'is_correct' => 1,
        // ]);

        $question->update([
            'correct_option_id' => $request->answer,
            'correct_answers' => $request->answer,
        ]);        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        if ($question->images) {
            foreach ($question->images as $q_image) {
                Storage::disk('public')->delete('images/courses/questions/' . $q_image->filename);
            }
            $question->images()->delete();
        }

        if ($question->options) {
            foreach ($question->options as $option) {
                if ($option->images) {
                    foreach ($option->images as $o_image) {
                        Storage::disk('public')->delete('images/courses/questions/' . $o_image->filename);
                    }
                    $option->images()->delete();
                }
            }
            $question->options()->delete();
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully',
        ], 204);
    }

    public function duplicateQuestion(Question $question, Request $request)
    {
        $new_question = $question->replicate();
        if ($request->quiz_id) {
            $new_question->questionable_type = 'App\Models\CourseQuiz';
            $new_question->questionable_id = $request->quiz_id;
        }
        $new_question->save();

        if ($request->quiz_id) {
            $quiz = $new_question->questionable;
            $quiz->increment('total_score', $question->points);
            $quiz->increment('total_questions');

            $course = $quiz->course;
            $course->increment('total_score', $question->points);
        }

        if($question->images){
            foreach ($question->images as $old_q_image) {
                // $q_img_file_extention = File::extension($old_q_image->url);
                // $new_q_img_filename = uniqid() . '.' . $q_img_file_extention;
                // $new_q_image_url = 'images/courses/quizzes/questions/'. $new_q_img_filename;
                
                // $new_q_image = $new_question->images()->create([
                //     'filename' => $new_q_img_filename,
                // ]);
                // Storage::disk('public')->move($old_q_image->url, $new_q_image_url);
                
                $new_q_image = $old_q_image->replicate();
                $new_q_image->imageable_id = $new_question->id;
                
                $new_q_image_file_extention = File::extension($old_q_image->url);
                $new_q_image_filename = uniqid() . '.' . $new_q_image_file_extention;
                $new_q_image_url = 'images/courses/quizzes/questions/'. $new_q_image_filename;
                Storage::disk('public')->copy('images/courses/quizzes/questions/'. $old_q_image->filename, $new_q_image_url);
                
                $new_q_image->filename = $new_q_image_filename;

                $new_q_image->save();
            }

        }

        if($question->options){
            foreach ($question->options as $old_q_option) {

                $new_q_option = $old_q_option->replicate();
                $new_q_option->optionable_id = $new_question->id;
                $new_q_option->save();

                if ($new_q_option->is_correct == 1 || $new_q_option->is_correct == '1') {
                    $new_question->update([
                        'correct_option_id' => $new_q_option->id,
                        'correct_answers' => $new_q_option->id,
                    ]);
                }else {
                    $new_question->update([
                        'correct_option_id' => null,
                        'correct_answers'   => null
                    ]);
                }

                if($old_q_option->images){

                    foreach ($old_q_option->images as $old_q_opt_image) {
                        $opt_img_file_extention = File::extension($old_q_opt_image->url);
                        $new_opt_img_filename = uniqid() . '.' . $opt_img_file_extention;
                        $new_opt_image_url = 'images/courses/quizzes/questions/'. $new_opt_img_filename;

                        Storage::disk('public')->copy('images/courses/quizzes/questions/'. $old_q_opt_image->filename, $new_opt_image_url);

                        $new_q_option->images()->create([
                            'filename' => $new_opt_img_filename
                        ]);
                    }

                }
            }
        }

        // if($old_question->options){
        //     foreach ($old_question->options as $old_q_option) {
        //         $new_q_option = $new_question->options()->create([
        //             'text' => $old_q_option->text,
        //             'is_correct' => $old_q_option->is_correct,
        //         ]);

        //         if($old_q_option->images){
        //             foreach ($old_q_option->images as $old_q_opt_image) {
        //                 $opt_img_file_extention = File::extension($old_q_opt_image->url);
        //                 $new_opt_img_filename = uniqid() . '.' . $opt_img_file_extention;
        //                 $new_opt_image_url = 'images/courses/quizzes/questions/'. $new_opt_img_filename;

        //                 Storage::disk('public')->copy('images/courses/quizzes/questions/'. $old_q_opt_image->filename, $new_opt_image_url);

        //                 $new_q_option->images()->create([
        //                     'filename' => $new_opt_img_filename
        //                 ]);              
        //             }
        //         }
        //     }
        // }

        // new_question->save();

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($new_question)
            // 'question' => new QuestionResource($question)
        ], 200);

    }
}
