<?php

namespace App\Services;

use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\CourseQuiz;
use App\Models\TopicImage;
use App\Models\LessonImage;
use App\Models\QuestionOption;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseCloneService
{
    /**
     * Clone a course and all its related content.
     */
    public function clone(Course $source, User $newOwner): Course
    {
        return DB::transaction(function () use ($source, $newOwner) {
            // Step 1: Clone Course
            $newCourse = $this->cloneCourse($source, $newOwner);

            // Mapping to track old IDs to new IDs
            $lessonMap = [];
            $topicMap = [];
            $quizMap = [];

            // Step 2: Clone Lessons
            foreach ($source->courseLessons()->orderBy('order', 'asc')->get() as $lesson) {
                $newLesson = $this->cloneLesson($lesson, $newCourse, $newOwner);
                $lessonMap[$lesson->id] = $newLesson->id;

                // Clone Lesson Images
                foreach ($lesson->images as $image) {
                    $newLesson->images()->create([
                        'filename' => $image->filename,
                    ]);
                }

                // Step 3: Clone Topics for each Lesson
                foreach ($lesson->topics()->get() as $topic) {
                    $newTopic = $this->cloneTopic($topic, $newCourse, $newLesson, $newOwner);
                    $topicMap[$topic->id] = $newTopic->id;

                    // Clone Topic Images
                    foreach ($topic->images as $image) {
                        $newTopic->images()->create([
                            'filename' => $image->filename,
                        ]);
                    }

                    // Clone Topic-level Assignments
                    $this->cloneAssignments($topic, 'App\Models\Topic', $newTopic->id);

                    // Clone Topic-level Questions
                    $this->cloneQuestions($topic, 'App\Models\Topic', $newTopic->id, $newCourse, $newOwner);
                }

                // Clone Lesson-level Assignments
                $this->cloneAssignments($lesson, 'App\Models\Lesson', $newLesson->id);

                // Clone Lesson-level Questions
                $this->cloneQuestions($lesson, 'App\Models\Lesson', $newLesson->id, $newCourse, $newOwner);
            }

            // Step 4: Clone Course-level Assignments
            $this->cloneAssignments($source, 'App\Models\Course', $newCourse->id);

            // Step 5: Clone Course-level Quizzes
            foreach ($source->courseQuizzes as $quiz) {
                $newQuiz = $this->cloneQuiz($quiz, $newCourse, $newOwner);
                $quizMap[$quiz->id] = $newQuiz->id;

                // Step 6: Clone Questions for Quiz
                $this->cloneQuestions($quiz, 'App\Models\CourseQuiz', $newQuiz->id, $newCourse, $newOwner);
            }

            // Step 7: Update counters
            $newCourse->update([
                'lessons' => $newCourse->courseLessons()->count(),
                'assignments' => Assignment::where('assignmentable_type', 'App\Models\Course')
                    ->where('assignmentable_id', $newCourse->id)->count(),
                'quizzes' => $newCourse->courseQuizzes()->count(),
            ]);

            return $newCourse->fresh();
        });
    }

    protected function cloneCourse(Course $source, User $newOwner): Course
    {
        $data = $source->toArray();

        // New ownership and status
        $data['user_id'] = $newOwner->id;
        $data['instructor_id'] = $newOwner->id;
        $data['academy_id'] = null;
        unset($data['creator_id']);
        $data['source_course_id'] = $source->id;
        $data['status'] = 1; // Active
        $data['is_for_marketplace'] = false;
        $data['saleable'] = false;
        $data['price'] = 0;
        $data['price_points'] = 0;
        $data['price_type'] = 'free';
        $data['total_sales'] = 0;
        $data['enrolled_students'] = 0;
        
        // Reset counters and schedule
        $data['lessons'] = 0;
        $data['assignments'] = 0;
        $data['quizzes'] = 0;
        $data['groups'] = 0;
        $data['semester'] = null;
        $data['academic_year'] = null;
        $data['finalization_status'] = 'active';
        $data['finalized_at'] = null;
        $data['finalized_by'] = null;
        $data['rating'] = null;

        // Generate unique slug
        $data['slug'] = Str::slug($source->name) . '-' . Str::random(6);

        // Remove ID to allow auto-increment
        unset($data['id']);

        return Course::create($data);
    }

    protected function cloneLesson(Lesson $source, Course $newCourse, User $newOwner): Lesson
    {
        $data = $source->toArray();
        $data['course_id'] = $newCourse->id;
        $data['user_id'] = $newOwner->id;
        
        // Reset counters
        $data['view_count'] = 0;
        $data['like_count'] = 0;
        $data['dislike_count'] = 0;
        $data['comment_count'] = 0;
        $data['share_count'] = 0;
        $data['download_count'] = 0;

        unset($data['id']);
        unset($data['lesson_url']); // Computed attribute

        return Lesson::create($data);
    }

    protected function cloneTopic(Topic $source, Course $newCourse, Lesson $newLesson, User $newOwner): Topic
    {
        $data = $source->toArray();
        $data['course_id'] = $newCourse->id;
        $data['lesson_id'] = $newLesson->id;
        $data['user_id'] = $newOwner->id;
        $data['academy_id'] = null;

        // Reset counters
        $data['likes'] = 0;
        $data['dislikes'] = 0;
        $data['comments'] = 0;
        $data['shares'] = 0;
        $data['views'] = 0;

        unset($data['id']);

        return Topic::create($data);
    }

    protected function cloneAssignments($sourceOwner, string $type, int $newId): void
    {
        $assignments = Assignment::where('assignmentable_type', $type)
            ->where('assignmentable_id', $sourceOwner->id)
            ->get();

        foreach ($assignments as $assignment) {
            $data = $assignment->toArray();
            $data['assignmentable_type'] = $type;
            $data['assignmentable_id'] = $newId;
            
            // Reset dates and group info
            $data['due_date'] = null;
            $data['start_date'] = null;
            $data['end_date'] = null;
            $data['target_groups'] = null;

            unset($data['id']);
            unset($data['is_published']); // Computed attribute

            Assignment::create($data);
        }
    }

    protected function cloneQuiz(CourseQuiz $source, Course $newCourse, User $newOwner): CourseQuiz
    {
        $data = $source->toArray();
        $data['course_id'] = $newCourse->id;
        $data['user_id'] = $newOwner->id;
        
        // Reset dates
        $data['start_date'] = null;
        $data['end_date'] = null;

        unset($data['id']);

        return CourseQuiz::create($data);
    }

    protected function cloneQuestions($sourceOwner, string $type, int $newId, Course $newCourse, User $newOwner): void
    {
        $questions = Question::where('questionable_type', $type)
            ->where('questionable_id', $sourceOwner->id)
            ->get();

        foreach ($questions as $question) {
            $data = $question->toArray();
            $data['questionable_type'] = $type;
            $data['questionable_id'] = $newId;
            $data['course_id'] = $newCourse->id;
            $data['user_id'] = $newOwner->id;
            
            // Temporary null for correct_option_id, will update after cloning options
            $originalCorrectOptionId = $data['correct_option_id'];
            $data['correct_option_id'] = null;

            unset($data['id']);
            $newQuestion = Question::create($data);

            // Clone Question Options
            $options = QuestionOption::where('optionable_type', 'App\Models\Question')
                ->where('optionable_id', $question->id)
                ->get();

            $newCorrectOptionId = null;

            foreach ($options as $option) {
                $optionData = $option->toArray();
                $optionData['optionable_id'] = $newQuestion->id;
                
                unset($optionData['id']);
                $newOption = QuestionOption::create($optionData);

                if ($option->id == $originalCorrectOptionId) {
                    $newCorrectOptionId = $newOption->id;
                }
            }

            if ($newCorrectOptionId) {
                $newQuestion->update(['correct_option_id' => $newCorrectOptionId]);
            }
        }
    }
}
