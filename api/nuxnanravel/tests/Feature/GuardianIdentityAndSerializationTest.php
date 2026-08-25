<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Guardian;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentGuardianLink;
use App\Models\User;
use App\Services\GuardianAccessService;
use App\Services\GuardianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two pieces G-S3-c introduced: "is this user a guardian of this student", which four call
 * sites used to answer against the legacy per-student table, and the payload builder the home-visit screens
 * use because they serialize the student model whole.
 */
class GuardianIdentityAndSerializationTest extends TestCase
{
    use RefreshDatabase;

    private function student(Academy $academy): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);
    }

    private function linkGuardian(Academy $academy, Student $student, ?User $account, string $first = 'สมชาย'): Guardian
    {
        $person = Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => $account?->id,
            'citizen_id' => (string) random_int(1000000000000, 9999999999999),
            'title_prefix' => 'นาย',
            'first_name' => $first,
            'last_name' => 'ใจดี',
            'occupation' => 'รับจ้าง',
            'monthly_income' => 12000,
            'status' => 'alive',
        ]);

        GuardianContact::create([
            'guardian_person_id' => $person->id,
            'contact_type' => 'phone',
            'contact_value' => '08'.random_int(10000000, 99999999),
            'is_primary' => true,
        ]);

        StudentGuardianLink::create([
            'student_id' => $student->id,
            'guardian_id' => $person->id,
            'guardian_type' => 'father',
            'appointed_by_role' => 'import',
            'legacy_row_ids' => [],
        ]);

        return $person;
    }

    public function test_the_account_linked_to_a_guardian_is_that_students_guardian(): void
    {
        $academy = Academy::factory()->create();
        $student = $this->student($academy);
        $parent = User::factory()->create();
        $this->linkGuardian($academy, $student, $parent);

        $this->assertTrue(app(GuardianAccessService::class)->isGuardianOf($parent, $student));
    }

    public function test_an_unlinked_account_is_not_a_guardian(): void
    {
        $academy = Academy::factory()->create();
        $student = $this->student($academy);
        $this->linkGuardian($academy, $student, null);

        $access = app(GuardianAccessService::class);
        $this->assertFalse($access->isGuardianOf(User::factory()->create(), $student));
        $this->assertFalse($access->isGuardianOf(null, $student));
    }

    /** A guardian of one child is not thereby a guardian of another. */
    public function test_the_link_is_checked_per_student(): void
    {
        $academy = Academy::factory()->create();
        $mine = $this->student($academy);
        $someone_elses = $this->student($academy);
        $parent = User::factory()->create();
        $this->linkGuardian($academy, $mine, $parent);
        $this->linkGuardian($academy, $someone_elses, null);

        $access = app(GuardianAccessService::class);
        $this->assertTrue($access->isGuardianOf($parent, $mine));
        $this->assertFalse($access->isGuardianOf($parent, $someone_elses));
    }

    public function test_guardian_student_ids_covers_siblings_and_stops_at_the_academy_border(): void
    {
        $academy = Academy::factory()->create();
        $other = Academy::factory()->create();
        $parent = User::factory()->create();

        $first = $this->student($academy);
        $second = $this->student($academy);
        $elsewhere = $this->student($other);
        $person = $this->linkGuardian($academy, $first, $parent);

        foreach ([$second, $elsewhere] as $child) {
            StudentGuardianLink::create([
                'student_id' => $child->id,
                'guardian_id' => $person->id,
                'guardian_type' => 'father',
                'appointed_by_role' => 'import',
                'legacy_row_ids' => [],
            ]);
        }

        $ids = app(GuardianAccessService::class)->guardianStudentIds($parent, $academy);

        sort($ids);
        $expected = [$first->id, $second->id];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    /**
     * The home-visit screens hand the student model straight to the serializer. If the relation
     * the payload was built from stays loaded it rides along as guardian_links[].guardian — the
     * whole person row — beside the array the caller thought was the only guardian data.
     */
    public function test_attaching_the_payload_drops_the_relation_it_was_built_from(): void
    {
        $academy = Academy::factory()->create();
        $student = $this->student($academy);
        $this->linkGuardian($academy, $student, null);

        app(GuardianService::class)->attachGuardiansTo($student, withSensitive: true);
        $json = $student->toJson();

        $this->assertStringNotContainsString('guardian_links', $json);
        $this->assertStringNotContainsString('guardianLinks', $json);

        $payload = json_decode($json, true);
        $this->assertCount(1, $payload['guardians']);
        $this->assertSame('นาย สมชาย ใจดี', $payload['guardians'][0]['full_name']);
        $this->assertCount(1, $payload['guardians'][0]['contacts']);
    }

    public function test_the_payload_builder_gates_the_sensitive_pair(): void
    {
        $academy = Academy::factory()->create();
        $student = $this->student($academy);
        $this->linkGuardian($academy, $student, null);

        app(GuardianService::class)->attachGuardiansTo($student, withSensitive: false);
        $hidden = json_decode($student->toJson(), true)['guardians'][0];
        $this->assertArrayNotHasKey('citizen_id', $hidden);
        $this->assertArrayNotHasKey('monthly_income', $hidden);

        $reloaded = $student->fresh();
        app(GuardianService::class)->attachGuardiansTo($reloaded, withSensitive: true);
        $shown = json_decode($reloaded->toJson(), true)['guardians'][0];
        $this->assertArrayHasKey('citizen_id', $shown);
        $this->assertSame('12000.00', $shown['monthly_income']);
    }
}
