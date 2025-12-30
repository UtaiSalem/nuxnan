<?php

namespace App\Http\Controllers\Api\Learn\Course\quizzes;

use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Question;
use App\Models\CourseQuiz;
use App\Models\CourseMember;
use Illuminate\Http\Request;
// use App\Http\Resources\LessonResource;
// use Illuminate\Support\Facades\Validator;
use App\Models\QuestionOption;
use Illuminate\Support\Carbon;
use App\Models\CourseQuizResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Exceptions\ImageCopyException;
use App\\Http\\Resources\\Learn\\Course\\info\\CourseResource;

use Illuminate\Support\Facades\Storage;
use App\Exceptions\ImageNotFoundException;
use App\\Http\\Resources\\Learn\\Course\\quizzes\\CourseQuizResource;
use App\\Http\\Resources\\Learn\\Course\\groups\\CourseGroupResource;

class CourseQuizController extends Controller
{
    private const QUIZ_IMAGE_PATH = 'images/courses/quizzes/questions/';

    public function index(Course $course) 
    {
        $isCourseAdmin = $course->user_id === auth()->id();
        return response()->json([
            'course'                => new CourseResource($course),
            'quizzes'               => $isCourseAdmin ? CourseQuizResource::collection($course->courseQuizzes) : CourseQuizResource::collection($course->courseQuizzes->where('is_active', 1)),
            // 'groups'             => CourseGroupResource::collection($course->courseGroups),
            'isCourseAdmin'         => $isCourseAdmin,
            'courseMemberOfAuth'    => $course->courseMembers()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function create()
    {
        return response()->json(['success' => true]);
    }

    public function store(Course $course, Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string',
            'description'       => 'nullable|string',
            'time_limit'        => 'required|integer|min:1',
            'is_active'         => 'boolean',
            'start_date'        => 'nullable|date',
            // 'end_date'          => 'nullable|date|after:start_date',
            'end_date'          => 'nullable|date',
            'shuffle_questions' => 'boolean',
            'passing_score'     => 'nullable|integer|min:0',
            
        ]);

        $validated['user_id']       = auth()->id();
        $validated['is_active']     = $validated['is_active'] ? 1 : 0;
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ? 1 : 0;
        $validated['start_date']    = $validated['start_date'] ?  Carbon::parse($validated['start_date'])->setTimezone('Asia/Bangkok') : null;
        $validated['end_date']      = $validated['end_date'] ? Carbon::parse($validated['end_date'])->setTimezone('Asia/Bangkok') : null;
        try {

            $quiz = $course->courseQuizzes()->create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Quiz created successfully',
                'quiz' => new CourseQuizResource($quiz),
            ], 201); // Created

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quiz',
                'error' => $e->getMessage(),
            ], 500); // Internal Server Error
        }
    }

    public function show(CourseQuiz $quiz)
    {
        return response()->json([
            'quiz' => $quiz
        ]);
    }

    public function edit(CourseQuiz $quiz)
    {
        return response()->json([
            'quiz' => $quiz 
        ]);
    }

    public function update(Course $course, CourseQuiz $quiz, Request $request)
    {
        // $quiz->update($request->all());
        $validated = $request->validate([
            'title'             => 'required|string',
            'description'       => 'nullable|string',
            'time_limit'        => 'required|integer|min:1',
            'is_active'         => 'boolean',
            'start_date'        => 'nullable|date',
            // 'end_date'          => 'nullable|date|after:start_date',
            'end_date'          => 'nullable|date',
            'shuffle_questions' => 'boolean',
            'passing_score'     => 'nullable|integer|min:0',
        ]);

        $validated['user_id']       = auth()->id();
        $validated['is_active']     = $validated['is_active'] ? 1 : 0;
        $validated['shuffle_questions'] = $validated['shuffle_questions'] ? 1 : 0;
        $validated['start_date']    = $validated['start_date'] ?  Carbon::parse($validated['start_date']) : null;
        $validated['end_date']      = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        $quiz->update($validated);

        // return redirect()->route('quizzes.show', $quiz);
        return response()->json([
            'success' => true,
            'message' => 'Quiz updated successfully',
            'quiz' => new CourseQuizResource($quiz),
        ], 200); // OK
    }

    public function destroy(Course $course, CourseQuiz $quiz)
    {
        $questions = $quiz->questions;
        foreach ($questions as $question) {
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
        }

        $userResults = $quiz->userResults;

        foreach ($userResults as $result) {
            $courseMember = CourseMember::where('user_id', $result->user_id)->where('course_id', $result->course_id)->first();
            $courseMember->decrement('achieved_score', $result->score);

            $result->delete();
        }

        $course->decrement('total_score', $quiz->total_score);

        $quiz->delete();

        return response()->json([
            'success' => true,
        ], 204);
    }

    public function calculateSumQuizsEfficiency(Course $course, CourseQuiz $quiz)
    {
        $crsQuizsResult = CourseQuizResult::where('user_id', auth()->id())->where('course_id', $quiz->course_id)->get();
        $sumEfficiency = $crsQuizsResult->sum('efficiency');

        $courseMember = CourseMember::where('user_id', auth()->id())->where('course_id', $quiz->course_id)->first();
        $courseMember->update([
            'efficiency' => $sumEfficiency,
        ]);

        return response()->json([
            'success' => true,
        ], 200);
    }

    public function getQuizzes(Course $course, Request $request)
    {
        $quizs = CourseQuiz::where('title', 'like', '%'. $request->input('title') . '%')->limit(5)->get();

        return response()->json([
            'success' => true,
            'quizs' => $quizs,
        ], 200);
    }

    public function duplicateQuiz(CourseQuiz $quiz, Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $newQuiz = $this->createDuplicateQuiz($quiz, $request->course_id);
            $this->duplicateQuestions($quiz, $newQuiz);
            $this->updateCourseTotalScore($newQuiz, $quiz->total_score);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'quiz' => new CourseQuizResource($newQuiz),
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Quiz duplication failed:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate quiz: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function createDuplicateQuiz(CourseQuiz $quiz, int $courseId): CourseQuiz
    {
        $newQuiz = $quiz->replicate();
        $newQuiz->course_id = $courseId;
        $newQuiz->save();
        
        return $newQuiz;
    }
    
    private function duplicateQuestions(CourseQuiz $originalQuiz, CourseQuiz $newQuiz): void
    {
        $originalQuiz->questions->each(function ($question) use ($newQuiz) {
            $newQuestion = $this->duplicateQuestion($question, $newQuiz->id);
            $this->duplicateQuestionImages($question, $newQuestion);
            $this->duplicateOptions($question, $newQuestion);
        });
    }
    
    private function duplicateQuestion($question, int $quizId): Question
    {
        $newQuestion = $question->replicate();
        $newQuestion->questionable_id = $quizId;
        $newQuestion->correct_answers = null;
        $newQuestion->correct_option_id = null;
        $newQuestion->save();
        
        return $newQuestion;
    }
    
    private function duplicateQuestionImages($question, $newQuestion): void
    {
        $question->images->each(function ($image) use ($newQuestion) {
            $this->duplicateImage($image, $newQuestion->id);
        });
    }
    
    private function duplicateImage($image, int $newImageableId): void
    {
        if (!Storage::disk('public')->exists(self::QUIZ_IMAGE_PATH . $image->filename)) {
            throw new ImageNotFoundException('Original image file not found');
        }
        
        $newImage = $image->replicate();
        $newImage->imageable_id = $newImageableId;
        $newImageFilename = uniqid() . '.' . File::extension($image->url);
        $newImageUrl = self::QUIZ_IMAGE_PATH . $newImageFilename;
        
        if (!Storage::disk('public')->copy(
            self::QUIZ_IMAGE_PATH . $image->filename,
            $newImageUrl
        )) {
            throw new ImageCopyException('Failed to copy image file');
        }
        
        $newImage->filename = $newImageFilename;
        $newImage->save();
    }
    
    private function duplicateOptions($question, $newQuestion): void
    {
        $question->options->each(function ($option) use ($newQuestion) {
            $newOption = $this->duplicateOption($option, $newQuestion);
            $this->duplicateOptionImages($option, $newOption);
            
            if ($option->is_correct) {
                $this->updateCorrectAnswer($newQuestion, $newOption->id);
            }
        });
    }
    
    private function duplicateOption($option, $newQuestion): QuestionOption
    {
        $newOption = $option->replicate();
        $newOption->optionable_id = $newQuestion->id;
        $newOption->save();
        
        return $newOption;
    }

    private function duplicateOptionImages($option, $newOption): void 
    {
        $option->images->each(function ($image) use ($newOption) {
            $this->duplicateImage($image, $newOption->id);
        });
    }
    
    private function updateCorrectAnswer($question, int $optionId): void
    {
        $question->update([
            'correct_answers' => $optionId,
            'correct_option_id' => $optionId,
        ]);
    }
    
    private function updateCourseTotalScore(CourseQuiz $quiz, int $score): void
    {
        $quiz->course->increment('total_score', $score);
    }
}

