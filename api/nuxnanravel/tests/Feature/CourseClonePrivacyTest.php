<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseQuiz;
use App\Models\CourseQuizResult;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonProgress;
use App\Models\Topic;
use App\Models\User;
use App\Services\CourseCloneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseClonePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloning_course_excludes_member_data_and_interaction()
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $this->actingAs($owner);

        // 1. Create Source Course
        $course = Course::create([
            'name' => 'Source Course',
            'slug' => 'source-course',
            'user_id' => $owner->id,
            'instructor_id' => $owner->id,
            'enrolled_students' => 1,
            'total_sales' => 100,
            'rating' => 5,
            'certificate' => false,
            'accreditation' => false,
            'discount_type' => 'fixed',
            'price_type' => 'free',
            'status' => 1,
        ]);

        // 2. Add Member Data to Course
        CourseMember::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 1,
            'status' => 1,
        ]);

        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'rating' => 5,
            'comment' => 'Great course!',
            'is_approved' => true,
        ]);

        // 3. Add Lesson with Interaction
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
            'title' => 'Source Lesson',
            'view_count' => 50,
            'like_count' => 10,
            'comment_count' => 5,
        ]);

        LessonComment::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'content' => 'Nice lesson',
        ]);

        LessonProgress::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'status' => 'completed',
        ]);

        // 4. Add Topic with Interaction
        $topic = Topic::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'user_id' => $owner->id,
            'title' => 'Source Topic',
            'views' => 20,
            'likes' => 5,
            'comments' => 2,
            'sentiment' => 0.8,
            'engagement_rate' => 0.5,
        ]);

        // 5. Add Assignment with Answer
        $assignment = Assignment::create([
            'assignmentable_type' => 'App\Models\Course',
            'assignmentable_id' => $course->id,
            'title' => 'Source Assignment',
            'graded_score' => 80, // Should be reset
            'feedback' => 'Good job', // Should be reset
        ]);

        $assignment->answers()->create([
            'user_id' => $student->id,
            'content' => 'My answer',
            'status' => 'submitted',
        ]);

        // 6. Add Quiz with Result
        $quiz = CourseQuiz::create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
            'title' => 'Source Quiz',
        ]);

        CourseQuizResult::create([
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 100,
        ]);

        // 7. Perform Clone
        $cloneService = app(CourseCloneService::class);
        $clonedCourse = $cloneService->clone($course, $owner);

        // 8. Assertions - Course Level
        $this->assertEquals(0, $clonedCourse->enrolled_students);
        $this->assertEquals(0, $clonedCourse->total_sales);
        $this->assertNull($clonedCourse->rating);
        $this->assertCount(0, $clonedCourse->courseMembers);
        $this->assertCount(0, $clonedCourse->reviews);

        // 9. Assertions - Lesson Level
        $clonedLesson = $clonedCourse->courseLessons()->first();
        $this->assertEquals(0, $clonedLesson->view_count);
        $this->assertEquals(0, $clonedLesson->like_count);
        $this->assertEquals(0, $clonedLesson->comment_count);
        $this->assertCount(0, $clonedLesson->comments);
        $this->assertCount(0, $clonedLesson->progress);

        // 10. Assertions - Topic Level
        $clonedTopic = $clonedLesson->topics()->first();
        $this->assertEquals(0, $clonedTopic->views);
        $this->assertEquals(0, $clonedTopic->likes);
        $this->assertEquals(0, $clonedTopic->comments);
        // These might fail if not explicitly reset
        $this->assertEquals(0, $clonedTopic->sentiment ?? 0);
        $this->assertEquals(0, $clonedTopic->engagement_rate ?? 0);

        // 11. Assertions - Assignment Level
        $clonedAssignment = Assignment::where('assignmentable_type', 'App\Models\Course')
            ->where('assignmentable_id', $clonedCourse->id)
            ->first();
        $this->assertNotNull($clonedAssignment);
        $this->assertEquals(0, $clonedAssignment->graded_score ?? 0);
        $this->assertNull($clonedAssignment->feedback);
        $this->assertCount(0, $clonedAssignment->answers);

        // 12. Assertions - Quiz Level
        $clonedQuiz = $clonedCourse->courseQuizzes()->first();
        $this->assertNotNull($clonedQuiz);
        $this->assertCount(0, CourseQuizResult::where('quiz_id', $clonedQuiz->id)->get());
    }
}
