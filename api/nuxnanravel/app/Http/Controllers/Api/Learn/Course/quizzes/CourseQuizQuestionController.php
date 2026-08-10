<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Http\Controllers\Controller;
use App\Http\Resources\Learn\Course\questions\QuestionResource;
use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\Question;
use App\Models\QuestionImage;
use App\Services\CourseMediaService;
use App\Services\CourseScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CourseQuizQuestionController extends Controller
{
    public function store(Course $course, CourseQuiz $quiz, Request $request)
    {
        $validatedData = $request->validate([
            'text' => 'required|string',
            'points' => 'required|integer',
            'pp_fine' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $new_question = $quiz->questions()->create([
            'user_id' => auth()->id(),
            'course_id' => $course->id,
            'text' => $request->text,
            'points' => $request->points,
            'pp_fine' => $request->pp_fine ?? 0,
        ]);

        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $quiz->increment('total_score', $request->points);
        $quiz->increment('total_questions');

        $this->storeQuestionImages($new_question, $request);

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
        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $quiz->decrement('total_score', $question->points);

        $validatedData = $request->validate([
            'text' => 'required|string',
            'points' => 'required|integer',
            'pp_fine' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $question->update([
            'text' => $request->text,
            'points' => $request->points,
            'pp_fine' => $request->pp_fine ?? 0,
        ]);

        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $quiz->increment('total_score', $request->points);

        // The editor holds a single picture per question, so an upload replaces
        // what is there instead of stacking a second image on top of it.
        $this->storeQuestionImages($question, $request, replace: true);

        return response()->json([
            'success' => true,
            'question' => new QuestionResource($question->fresh('images')),
        ], 200);
    }

    /**
     * Persist uploaded question images.
     *
     * `question_images` only has a `filename` column — writing `image_url` here
     * used to throw "Unknown column", which silently lost every image attached
     * while editing a question.
     */
    private function storeQuestionImages(Question $question, Request $request, bool $replace = false): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        if ($replace) {
            $media = app(CourseMediaService::class);

            foreach ($question->images as $old) {
                $media->deleteUnused('quiz_question_image', QuestionImage::class, 'filename', $old->filename, $old->id);
                $old->delete();
            }
        }

        foreach ($request->file('images') as $q_image) {
            $q_img_filename = uniqid().'.'.$q_image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/courses/quizzes/questions', $q_image, $q_img_filename);
            $question->images()->create([
                'filename' => $q_img_filename,
            ]);
        }
    }

    public function destroy(Course $course, CourseQuiz $quiz, Question $question)
    {
        if ($question->images) {
            foreach ($question->images as $q_image) {
                Storage::disk('public')->delete('images/courses/quizzes/questions/'.$q_image->filename);
            }
            $question->images()->delete();
        }

        if ($question->options) {
            foreach ($question->options as $q_option) {
                if ($q_option->images) {
                    foreach ($q_option->images as $q_opt_image) {
                        Storage::disk('public')->delete('images/courses/quizzes/questions/'.$q_opt_image->filename);
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

        app(CourseScoreService::class)->syncCourseTotalScore($course);
        $quiz->decrement('total_score', $question->points);
        $quiz->decrement('total_questions');

        $question->delete();

        return response()->json([
            'success' => true,
        ], 204);
    }
}
