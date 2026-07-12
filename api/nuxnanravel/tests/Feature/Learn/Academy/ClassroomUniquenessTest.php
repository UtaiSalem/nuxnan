<?php

namespace Tests\Feature\Learn\Academy;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Academy $academy;

    private AcademicYear $year2568;

    private AcademicYear $year2569;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Academy Admin',
            'email' => 'admin@academy.test',
            'password' => bcrypt('password'),
            'username' => 'academy_admin',
        ]);

        $this->academy = Academy::create([
            'name' => 'Test Academy',
            'user_id' => $this->admin->id,
        ]);

        $this->year2568 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2568',
            'start_date' => '2025-05-16',
            'end_date' => '2026-03-31',
            'is_current' => false,
        ]);

        $this->year2569 = AcademicYear::create([
            'academy_id' => $this->academy->id,
            'name' => '2569',
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);
    }

    /**
     * Test duplicate classroom creation in the same academic year is blocked.
     */
    public function test_duplicate_classroom_creation_in_same_year_is_blocked(): void
    {
        $this->actingAs($this->admin, 'api');

        // Create first classroom
        Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Attempt duplicate creation
        $response = $this->postJson("/api/academies/{$this->academy->id}/classrooms", [
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1 อีกห้อง',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['grade_level']);
    }

    /**
     * Test creating classrooms with same names in different years is allowed.
     */
    public function test_same_classroom_in_different_years_is_allowed(): void
    {
        $this->actingAs($this->admin, 'api');

        // Create first classroom in 2568
        Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2568->id,
            'academic_year' => '2568',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Create second classroom in 2569
        $response = $this->postJson("/api/academies/{$this->academy->id}/classrooms", [
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('classrooms', 2);
    }

    /**
     * Test that resolution from academic_year string resolves FK and blocks duplicate.
     */
    public function test_resolution_from_string_resolves_fk_and_blocks_duplicate(): void
    {
        $this->actingAs($this->admin, 'api');

        // Create first classroom using year ID
        Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Attempt creation using only string (legacy/form style)
        $response = $this->postJson("/api/academies/{$this->academy->id}/classrooms", [
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['grade_level']);
    }

    /**
     * Test that updating a classroom to clash with another one is blocked.
     */
    public function test_update_to_clashing_classroom_is_blocked(): void
    {
        $this->actingAs($this->admin, 'api');

        // Room A
        $roomA = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '1',
            'name' => 'ม.1/1',
        ]);

        // Room B
        $roomB = Classroom::create([
            'academy_id' => $this->academy->id,
            'academic_year_id' => $this->year2569->id,
            'academic_year' => '2569',
            'grade_level' => 'ม.1',
            'section' => '2',
            'name' => 'ม.1/2',
        ]);

        // Update Room B to be ม.1/1
        $response = $this->patchJson("/api/academies/{$this->academy->id}/classrooms/{$roomB->id}", [
            'grade_level' => 'ม.1',
            'section' => '1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['grade_level']);
    }
}
