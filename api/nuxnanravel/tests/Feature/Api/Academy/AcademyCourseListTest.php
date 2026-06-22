<?php

namespace Tests\Feature\Api\Academy;

use App\Models\Academy;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyCourseListTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->academy = Academy::create([
            'user_id' => $this->owner->id,
            'name' => 'Academy Course List',
            'slug' => 'academy-course-list',
        ]);
    }

    public function test_courses_can_be_loaded_by_academy_id(): void
    {
        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Physics 101',
            'education_level' => 'มัธยมศึกษา',
            'education_year' => 1,
            'finalization_status' => 'published',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses");

        $courseNames = collect($response->json('courses'))->pluck('name')->all();

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'courses');

        $this->assertEquals(['Physics 101'], $courseNames);
    }

    public function test_courses_support_student_segment_filters_and_metadata(): void
    {
        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Math M1',
            'education_level' => 'มัธยมศึกษา',
            'education_year' => 1,
            'semester' => '1',
            'academic_year' => 2569,
            'status' => 1,
            'finalization_status' => 'published',
        ]);

        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Math M2',
            'education_level' => 'มัธยมศึกษา',
            'education_year' => 2,
            'semester' => '2',
            'academic_year' => 2569,
            'status' => 1,
            'finalization_status' => 'published',
        ]);

        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'General Elective',
            'education_level' => null,
            'education_year' => null,
            'semester' => null,
            'academic_year' => null,
            'status' => 0,
            'finalization_status' => 'active',
        ]);

        $response = $this->actingAs($this->owner, 'api')
            ->getJson('/api/academies/'.$this->academy->id.'/courses?education_level='.urlencode('มัธยมศึกษา').'&education_year=1&semester=1&academic_year=2569&search=Math');

        $courseNames = collect($response->json('courses'))->pluck('name')->all();

        $response->assertOk()
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('available_filters.education_levels.0.value', 'มัธยมศึกษา');

        $this->assertEquals(['Math M1'], $courseNames);

        $levelValues = collect($response->json('available_filters.education_levels'))->pluck('value')->all();
        $this->assertContains('unspecified', $levelValues);
    }

    public function test_courses_support_admin_status_filters(): void
    {
        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Published Course',
            'status' => 1,
            'finalization_status' => 'published',
        ]);

        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Draft Course',
            'status' => 0,
            'finalization_status' => 'active',
        ]);

        Course::factory()->create([
            'academy_id' => $this->academy->id,
            'user_id' => $this->owner->id,
            'instructor_id' => $this->owner->id,
            'name' => 'Archived Course',
            'status' => 1,
            'finalization_status' => 'archived',
        ]);

        $publishedResponse = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses?status=published");
        $publishedNames = collect($publishedResponse->json('courses'))->pluck('name')->all();
        $this->assertEquals(['Published Course'], $publishedNames);

        $draftResponse = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses?status=draft");
        $draftNames = collect($draftResponse->json('courses'))->pluck('name')->all();
        $this->assertEquals(['Draft Course'], $draftNames);

        $archivedResponse = $this->actingAs($this->owner, 'api')
            ->getJson("/api/academies/{$this->academy->id}/courses?status=archived");
        $archivedNames = collect($archivedResponse->json('courses'))->pluck('name')->all();
        $this->assertEquals(['Archived Course'], $archivedNames);
    }
}
