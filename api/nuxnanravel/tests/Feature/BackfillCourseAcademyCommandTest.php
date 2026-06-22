<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillCourseAcademyCommandTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Test Academy',
            'slug' => 'test-academy',
            'courses_offered' => 0,
        ]);
    }

    public function test_command_does_not_modify_database_in_dry_run(): void
    {
        $course = Course::create([
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Test Course',
            'slug' => 'test-course',
            'academy_id' => null,
            'academic_year' => null,
            'status' => 1,
        ]);

        $this->artisan('courses:backfill-academy', [
            '--academy' => $this->academy->id,
            '--year' => 2569,
            '--ids' => (string) $course->id,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $course->refresh();
        $this->assertNull($course->academy_id);
        $this->assertNull($course->academic_year);

        $this->academy->refresh();
        $this->assertEquals(0, $this->academy->courses_offered);
    }

    public function test_command_updates_courses_and_syncs_courses_offered(): void
    {
        $course1 = Course::create([
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Course 1',
            'slug' => 'course-1',
            'academy_id' => null,
            'academic_year' => null,
            'status' => 1,
        ]);

        $course2 = Course::create([
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Course 2',
            'slug' => 'course-2',
            'academy_id' => null,
            'academic_year' => null,
            'status' => 1,
        ]);

        // Run command and mock confirmation prompt
        $this->artisan('courses:backfill-academy', [
            '--academy' => $this->academy->id,
            '--year' => 2569,
            '--ids' => "{$course1->id},{$course2->id}",
        ])
            ->expectsConfirmation("Patch 2 course(s) to academy_id={$this->academy->id}, academic_year=2569?", 'yes')
            ->assertExitCode(0);

        $course1->refresh();
        $course2->refresh();

        $this->assertEquals($this->academy->id, $course1->academy_id);
        $this->assertEquals(2569, $course1->academic_year);
        $this->assertEquals($this->academy->id, $course2->academy_id);
        $this->assertEquals(2569, $course2->academic_year);

        $this->academy->refresh();
        $this->assertEquals(2, $this->academy->courses_offered);
    }

    public function test_command_fails_if_academy_not_found(): void
    {
        $this->artisan('courses:backfill-academy', [
            '--academy' => 9999,
            '--ids' => '1',
        ])->assertExitCode(1);
    }
}
