<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentIntakeGuardianDualWriteTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Academy $academy;

    private AcademicYear $year;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->academy = Academy::create(['user_id' => $this->owner->id, 'name' => 'Dual Write', 'slug' => 'dual-write']);
        $this->year = AcademicYear::create(['academy_id' => $this->academy->id, 'name' => '2569', 'start_date' => '2026-05-16', 'end_date' => '2027-03-31']);
        $this->classroom = Classroom::create(['academy_id' => $this->academy->id, 'academic_year_id' => $this->year->id, 'grade_level' => 'ม.1', 'section' => '1', 'name' => 'ม.1/1', 'capacity' => 40, 'is_active' => true, 'status' => 'active']);
    }

    public function test_two_guardians_write_all_three_tables_and_correct_legacy_ids(): void
    {
        $this->intake('S-1', ['guardians' => [$this->guardian('1111111111111', 'One'), $this->guardian('2222222222222', 'Two')]])->assertCreated();
        $this->assertDatabaseCount('student_guardians', 2);
        $this->assertDatabaseCount('guardians', 2);
        $this->assertDatabaseCount('student_guardian_links', 2);
        foreach (DB::table('student_guardian_links')->get() as $link) {
            $this->assertSame([$link->legacy_row_ids ? json_decode($link->legacy_row_ids, true)[0] : null], json_decode($link->legacy_row_ids, true));
            $this->assertDatabaseHas('student_guardians', ['id' => json_decode($link->legacy_row_ids, true)[0]]);
        }
    }

    public function test_separate_sibling_intakes_reuse_matching_guardian_person(): void
    {
        $this->intake('S-1', ['guardians' => [$this->guardian('1234567890123', 'Shared')]])->assertCreated();
        $this->intake('S-2', ['guardians' => [$this->guardian('1234567890123', 'Shared')]], 8)->assertCreated();
        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseCount('student_guardian_links', 2);
    }

    public function test_guardian_without_national_id_creates_a_person(): void
    {
        $this->intake('S-1', ['guardians' => [$this->guardian(null, 'NoId')]])->assertCreated();
        $this->assertDatabaseCount('guardians', 1);
        $this->assertDatabaseHas('guardians', ['citizen_id' => null, 'first_name' => 'NoId']);
    }

    public function test_intake_contacts_have_both_legacy_and_person_ids(): void
    {
        $this->intake('S-1', ['guardians' => [$this->guardian('1234567890123', 'Contact', [['contact_type' => 'phone', 'contact_value' => '0812345678']])]])->assertCreated();
        $contact = DB::table('guardian_contacts')->first();
        $this->assertNotNull($contact);
        $this->assertNotNull($contact->guardian_id);
        $this->assertNotNull($contact->guardian_person_id);
    }

    /** The intake response reads the person model now (G-S3-b), not the legacy row it also wrote. */
    public function test_intake_response_lists_the_guardian_from_the_person_model(): void
    {
        $response = $this->intake('S-1', ['guardians' => [$this->guardian('1234567890123', 'Contact', [['contact_type' => 'phone', 'contact_value' => '0812345678']])]]);

        $response->assertCreated();
        $response->assertJsonCount(1, 'data.guardians');
        $response->assertJsonPath('data.guardians.0.first_name', 'Contact');
        $response->assertJsonPath('data.guardians.0.contacts.0.contact_value', '0812345678');
        $link = DB::table('student_guardian_links')->first();
        $response->assertJsonPath('data.guardians.0.id', $link->id);
        $response->assertJsonPath('data.guardians.0.guardian_id', $link->guardian_id);
    }

    public function test_late_intake_failure_rolls_back_nested_guardian_writes(): void
    {
        $this->mock(AuditLogService::class)->shouldReceive('logCustom')->andThrow(new \RuntimeException('forced late failure'));
        $this->intake('S-1', ['guardians' => [$this->guardian('1234567890123', 'Rollback')]])->assertStatus(500);
        $this->assertDatabaseCount('student_guardians', 0);
        $this->assertDatabaseCount('guardians', 0);
        $this->assertDatabaseCount('student_guardian_links', 0);
    }

    private function intake(string $studentId, array $overrides = [], int $number = 7)
    {
        $payload = ['identity' => ['student_id' => $studentId, 'citizen_id' => str_pad((string) (1000000000000 + $number), 13, '0', STR_PAD_LEFT)], 'personal' => ['first_name_th' => 'Student', 'last_name_th' => $studentId, 'date_of_birth' => '2013-01-15', 'gender' => 1, 'nationality' => 'ไทย'], 'admission' => ['academic_year_id' => $this->year->id, 'classroom_id' => $this->classroom->id, 'student_number' => $number, 'enrollment_date' => '2026-05-16'], 'previous_school' => [], 'guardians' => [], 'account' => []];

        return $this->actingAs($this->owner, 'api')->postJson("/api/academies/{$this->academy->id}/student-intakes", array_replace_recursive($payload, $overrides));
    }

    private function guardian(?string $citizen, string $first, array $contacts = []): array
    {
        return array_filter(['guardian_type' => 'father', 'first_name' => $first, 'last_name' => 'Shared', 'relationship' => 'father', 'citizen_id' => $citizen, 'contacts' => $contacts], fn ($v) => $v !== null);
    }
}
