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
use App\Services\Support\CourseCloneContext;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseCloneService
{
    protected $mediaService;

    public function __construct(CourseMediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Clone a course and all its related content.
     */
    public function clone(Course $source, User $newOwner, ?CourseCloneContext $context = null): Course
    {
        $context = $context ?? new CourseCloneContext();

        return DB::transaction(function () use ($source, $newOwner, $context) {
            // Step 1: Clone Course
            $newCourse = $this->cloneCourse($source, $newOwner, $context);

            if ($source->courseSettings) {
                $newSettings = $source->courseSettings->replicate();
                $newSettings->course_id = $newCourse->id;
                $newSettings->save();
            }

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
                    $newFilename = $this->mediaService->copyLessonImage($image->filename);
                    if ($newFilename) {
                        $newLesson->images()->create(['filename' => $newFilename]);
                    }
                }

                // Step 3: Clone Topics for each Lesson
                foreach ($lesson->topics()->get() as $topic) {
                    $newTopic = $this->cloneTopic($topic, $newCourse, $newLesson, $newOwner);
                    $topicMap[$topic->id] = $newTopic->id;

                    // Clone Topic Images
                    foreach ($topic->images as $image) {
                        $newFilename = $this->mediaService->copyTopicImage($image->filename);
                        if ($newFilename) {
                            $newTopic->images()->create(['filename' => $newFilename]);
                        }
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

    protected function cloneCourse(Course $source, User $newOwner, CourseCloneContext $context): Course
    {
        $allowlist = [
            'name', 'code', 'description', 'category', 'level', 'education_level',
            'education_year', 'duration', 'start_date', 'end_date',
            'class_schedule', 'hours_per_week', 'tuition_fees', 'price', 'discount',
            'discount_type', 'points', 'credit_units', 'capacity', 'prerequisites',
            'course_materials', 'syllabus',
            'accreditation', 'accreditation_body', 'certificate',
            'certificate_download_cost', 'certificate_free_download',
            'max_absence_percent', 'allow_unlock_by_points', 'unlock_points_cost',
            'allow_unlock_by_reading', 'unlock_reading_minutes', 'allow_remediation',
            'max_remediation_attempts', 'remediation_max_grade', 'allow_grade_appeal',
            'appeal_deadline_days', 'price_points', 'price_type'
        ];

        $data = array_intersect_key($source->getAttributes(), array_flip($allowlist));

        // New ownership and status
        $data['user_id'] = $newOwner->id;
        $data['instructor_id'] = $newOwner->id;
        $data['academy_id'] = null;
        $data['source_course_id'] = $source->id;
        $data['status'] = 1; // Active
        
        // Handle name
        if ($context->addCopySuffix) {
            $data['name'] = $source->name . ' (Copy)';
        }

        // Handle marketplace state
        $data['is_for_marketplace'] = $context->copyMarketplaceState ? $source->is_for_marketplace : false;
        $data['saleable'] = $context->copyMarketplaceState ? $source->saleable : false;
        $data['price'] = $context->copyMarketplaceState ? $source->price : 0;
        
        // Reset counters and schedule
        $data['total_sales'] = 0;
        $data['enrolled_students'] = 0;
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
        $data['total_score'] = 0;

        // Clone Cover (Strict Isolation: no ?? fallback)
        if ($source->cover) {
            $data['cover'] = $this->mediaService->copyCourseCover($source->cover);
        }

        // Generate unique slug based on (potentially new) name
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        return Course::create($data);
    }

    protected function cloneLesson(Lesson $source, Course $newCourse, User $newOwner): Lesson
    {
        $allowlist = [
            'title', 'description', 'content', 'video_url', 'youtube_url',
            'duration', 'min_read', 'status', 'order', 'point_tuition_fee'
        ];

        $data = array_intersect_key($source->getAttributes(), array_flip($allowlist));
        
        $data['course_id'] = $newCourse->id;
        $data['user_id'] = $newOwner->id;
        
        // Reset counters
        $data['view_count'] = 0;
        $data['like_count'] = 0;
        $data['dislike_count'] = 0;
        $data['comment_count'] = 0;
        $data['share_count'] = 0;
        $data['download_count'] = 0;

        return Lesson::create($data);
    }

    protected function cloneTopic(Topic $source, Course $newCourse, Lesson $newLesson, User $newOwner): Topic
    {
        $allowlist = [
            'title', 'content', 'youtube_url', 'min_read', 'status', 'hashtags',
            'privacy_settings', 'location', 'url', 'tags', 'source_platform', 'meta'
        ];

        $data = array_intersect_key($source->getAttributes(), array_flip($allowlist));
        
        $data['course_id'] = $newCourse->id;
        $data['lesson_id'] = $newLesson->id;
        $data['user_id'] = $newOwner->id;
        $data['academy_id'] = null;

        // Reset counters and metrics
        $data['likes'] = 0;
        $data['dislikes'] = 0;
        $data['comments'] = 0;
        $data['shares'] = 0;
        $data['views'] = 0;
        $data['sentiment'] = 0;
        $data['engagement_rate'] = 0;
        $data['interacted_at'] = null;

        return Topic::create($data);
    }

    protected function cloneAssignments($sourceOwner, string $type, int $newId): void
    {
        $assignments = Assignment::where('assignmentable_type', $type)
            ->where('assignmentable_id', $sourceOwner->id)
            ->get();

        foreach ($assignments as $assignment) {
            $allowlist = [
                'title', 'description', 'points', 'passing_score', 'increase_points',
                'decrease_points', 'assignment_type', 'submission_method', 'max_file_size',
                'is_group_assignment', 'grading_rubric', 'status'
            ];

            $data = array_intersect_key($assignment->getAttributes(), array_flip($allowlist));
            
            $data['assignmentable_type'] = $type;
            $data['assignmentable_id'] = $newId;
            
            // Reset interaction data
            $data['due_date'] = null;
            $data['start_date'] = null;
            $data['end_date'] = null;
            $data['target_groups'] = null;
            $data['graded_score'] = 0;
            $data['feedback'] = null;

            $newAssignment = Assignment::create($data);

            // Clone Assignment Images
            foreach ($assignment->images as $image) {
                $newFilename = $this->mediaService->copyAssignmentImage($image->image_url);
                if ($newFilename) {
                    $newAssignment->images()->create([
                        'image_url' => $newFilename,
                    ]);
                }
            }
        }
    }

    protected function cloneQuiz(CourseQuiz $source, Course $newCourse, User $newOwner): CourseQuiz
    {
        $allowlist = [
            'title', 'description', 'duration', 'pass_percentage', 'max_attempts',
            'status', 'shuffle_questions', 'shuffle_options', 'view_answers'
        ];

        $data = array_intersect_key($source->getAttributes(), array_flip($allowlist));
        
        $data['course_id'] = $newCourse->id;
        $data['user_id'] = $newOwner->id;
        
        // Reset dates
        $data['start_date'] = null;
        $data['end_date'] = null;

        return CourseQuiz::create($data);
    }

    protected function cloneQuestions($sourceOwner, string $type, int $newId, Course $newCourse, User $newOwner): void
    {
        $questions = Question::where('questionable_type', $type)
            ->where('questionable_id', $sourceOwner->id)
            ->get();

        foreach ($questions as $question) {
            $allowlist = [
                'text', 'type', 'correct_answers', 'explanation', 'difficulty_level',
                'time_limit', 'points', 'pp_fine', 'position', 'tags'
            ];

            $data = array_intersect_key($question->getAttributes(), array_flip($allowlist));
            
            $data['questionable_type'] = $type;
            $data['questionable_id'] = $newId;
            $data['course_id'] = $newCourse->id;
            $data['user_id'] = $newOwner->id;
            
            // Temporary null for correct_option_id, will update after cloning options
            $data['correct_option_id'] = null;
            $data['correct_answers'] = null;

            $newQuestion = Question::create($data);

            // Clone Question Images
            foreach ($question->images as $image) {
                // Determine target path based on searchable locations or standard
                $filename = $image->filename ?? $image->image_url;
                $newFilename = $this->mediaService->copyQuestionImage($filename);
                
                if ($newFilename) {
                    $newQuestion->images()->create([
                        'filename' => $newFilename,
                    ]);
                }
            }

            // Clone Question Options
            $options = QuestionOption::where('optionable_type', 'App\Models\Question')
                ->where('optionable_id', $question->id)
                ->get();

            $newCorrectOptionId = null;

            foreach ($options as $option) {
                $allowlistOption = [
                    'text', 'is_correct', 'explanation', 'position', 'status'
                ];

                $optionData = array_intersect_key($option->getAttributes(), array_flip($allowlistOption));
                
                $optionData['optionable_type'] = 'App\Models\Question';
                $optionData['optionable_id'] = $newQuestion->id;
                
                $newOption = QuestionOption::create($optionData);

                // Clone Option Images
                foreach ($option->images as $image) {
                    $filename = $image->filename ?? $image->image_url;
                    $newFilename = $this->mediaService->copyOptionImage($filename);
                    
                    if ($newFilename) {
                        $newOption->images()->create([
                            'filename' => $newFilename,
                        ]);
                    }
                }

                if ($option->id == $question->correct_option_id) {
                    $newCorrectOptionId = $newOption->id;
                }
            }

            if ($newCorrectOptionId) {
                $newQuestion->update([
                    'correct_option_id' => $newCorrectOptionId,
                    'correct_answers' => $newCorrectOptionId,
                ]);
            }
        }
    }
}
