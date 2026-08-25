<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\StudentGuardianLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The master registry (StudentResource) and the student profile now build their guardian
 * payload from student_guardian_links + guardians, not from student_guardians (G-S3-b).
 *
 * These lock the parts of that move the older tests could not see: they assert on the whole
 * response body, and they build the backfilled data shape — one link carrying several legacy
 * row ids — which no write path produces on its own.
 */
class StudentRegistryGuardianPayloadTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwner(): array
    {
        $owner = User::factory()->create();

        return [Academy::factory()->create(['user_id' => $owner->id]), $owner];
    }

    private function student(Academy $academy): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ทะเบียน',
            'status' => 'active',
        ]);
    }

    /** One person, N legacy rows, one link over all of them — exactly what guardians:backfill wrote. */
    private function backfilledGuardian(Academy $academy, Student $student, int $legacyRows = 1): Guardian
    {
        $person = Guardian::create([
            'academy_id' => $academy->id,
            'citizen_id' => '1234567890123',
            'title_prefix' => 'นาง',
            'first_name' => 'ทวีวรรณ',
            'last_name' => 'มัจฉาวานิช',
            'occupation' => 'ค้าขาย',
            'monthly_income' => 9000,
            'nationality' => 'ไทย',
            'status' => 'alive',
        ]);

        $legacyIds = [];
        foreach (range(1, $legacyRows) as $n) {
            $legacy = StudentGuardian::create([
                'academy_id' => $academy->id,
                'student_id' => $student->id,
                'student_code' => $student->student_id,
                'guardian_type' => 'mother',
                'citizen_id' => $person->citizen_id,
                'first_name' => $person->first_name,
                'last_name' => $person->last_name,
                'status' => 'alive',
                'nationality' => 'ไทย',
            ]);
            $legacyIds[] = $legacy->id;

            GuardianContact::create([
                'guardian_id' => $legacy->id,
                'guardian_person_id' => $person->id,
                'contact_type' => 'phone',
                'contact_value' => '099000000'.$n,
                'is_primary' => $n === 1,
            ]);
        }

        StudentGuardianLink::create([
            'student_id' => $student->id,
            'guardian_id' => $person->id,
            'guardian_type' => 'mother',
            'relationship' => 'มารดา',
            'appointed_by_role' => 'import',
            'legacy_row_ids' => $legacyIds,
        ]);

        return $person;
    }

    public function test_master_registry_reads_the_name_and_contacts_from_the_person(): void
    {
        [$academy, $owner] = $this->academyOwner();
        $student = $this->student($academy);
        $this->backfilledGuardian($academy, $student);

        $response = $this->actingAs($owner, 'api')->getJson("/api/student/master/{$student->id}");

        $response->assertOk();
        $response->assertJsonPath('data.guardians.0.first_name', 'ทวีวรรณ');
        $response->assertJsonPath('data.guardians.0.occupation', 'ค้าขาย');
        $response->assertJsonPath('data.guardians.0.contacts.0.contact_value', '0990000001');
        // The owner clears guardians.sensitive.view, so the gated pair is present for them.
        $response->assertJsonPath('data.guardians.0.citizen_id', '1234567890123');
    }

    /**
     * The relation the payload is built from must not ride along in the body. Serialized, it
     * would carry the whole person row — citizen_id and monthly_income included — past the
     * gate applied to guardians[]. Assertions scoped to guardians.* cannot see that.
     */
    public function test_master_registry_never_serializes_the_link_relation(): void
    {
        [$academy, $owner] = $this->academyOwner();
        $student = $this->student($academy);
        $this->backfilledGuardian($academy, $student);

        $body = $this->actingAs($owner, 'api')
            ->getJson("/api/student/master/{$student->id}")
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('guardian_links', $body);
        $this->assertStringNotContainsString('guardianLinks', $body);
    }

    public function test_profile_never_serializes_the_link_relation(): void
    {
        [$academy, $owner] = $this->academyOwner();
        $student = $this->student($academy);
        $this->backfilledGuardian($academy, $student);

        $body = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile")
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('guardian_links', $body);
        $this->assertStringNotContainsString('guardianLinks', $body);
    }

    /**
     * 46 students on the live database carry a guardian entered twice. The backfill folded each
     * pair into a single link, so the registry must now list that person once — and hand back
     * both phone numbers, which the two legacy rows used to split between them.
     */
    public function test_two_legacy_rows_for_one_person_list_the_guardian_once(): void
    {
        [$academy, $owner] = $this->academyOwner();
        $student = $this->student($academy);
        $this->backfilledGuardian($academy, $student, legacyRows: 2);

        $this->assertSame(2, StudentGuardian::where('student_id', $student->id)->count());

        $response = $this->actingAs($owner, 'api')->getJson("/api/student/master/{$student->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.guardians');
        $response->assertJsonCount(2, 'data.guardians.0.contacts');
    }

    /**
     * The unverified-appointment gate is aimed at the student, not at staff: a student who
     * attached someone else's guardian must not read their citizen id until it is verified,
     * while a teacher holding guardians.sensitive.view still sees it. The registry route had
     * that rule through maskUnverifiedSelfAppointments(); it has to survive the rewrite.
     */
    public function test_registry_hides_an_unverified_self_appointment_from_the_student_only(): void
    {
        [$academy, $owner] = $this->academyOwner();

        $sibling = $this->student($academy);
        $person = $this->backfilledGuardian($academy, $sibling);

        $studentUser = User::factory()->create();
        $student = $this->student($academy);
        $student->update(['user_id' => $studentUser->id]);
        StudentGuardianLink::create([
            'student_id' => $student->id,
            'guardian_id' => $person->id,
            'guardian_type' => 'mother',
            'appointed_by_role' => 'student',
            'legacy_row_ids' => [],
        ]);

        $this->actingAs($studentUser, 'api')
            ->getJson("/api/student/master/{$student->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.guardians.0.citizen_id')
            ->assertJsonMissingPath('data.guardians.0.monthly_income');

        $this->actingAs($owner, 'api')
            ->getJson("/api/student/master/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.guardians.0.citizen_id', '1234567890123');
    }

    /**
     * The profile used to walk legacy_row_ids to find the link. A link with no legacy row behind
     * it — what the write path will produce once student_guardians is dropped in G-S6 — was
     * invisible on that route; it must show up now, appointment status and all.
     */
    public function test_profile_lists_a_link_that_has_no_legacy_row(): void
    {
        [$academy, $owner] = $this->academyOwner();
        $student = $this->student($academy);

        $person = Guardian::create([
            'academy_id' => $academy->id,
            'first_name' => 'ไร้',
            'last_name' => 'แถวเก่า',
            'status' => 'alive',
        ]);
        StudentGuardianLink::create([
            'student_id' => $student->id,
            'guardian_id' => $person->id,
            'guardian_type' => 'guardian',
            'appointed_by_role' => 'owner',
            'legacy_row_ids' => [],
        ]);

        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/students/{$student->id}/profile");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.guardians');
        $response->assertJsonPath('data.guardians.0.first_name', 'ไร้');
        $response->assertJsonPath('data.guardians.0.appointed_by_role', 'owner');
        $response->assertJsonPath('data.guardians.0.is_verified', false);
        $response->assertJsonPath('data.guardians.0.guardian_id', $person->id);
    }
}
