<?php

namespace App\Models {
    use Illuminate\Database\Eloquent\Model;

    class StudentAttendance extends Model {}
}

namespace Tests\Feature {

    use App\Models\Academy;
    use App\Models\ClassroomStudent;
    use App\Models\Student;
    use App\Models\StudentGuardianLink;
    use App\Models\User;
    use App\Services\GuardianAccountLinkService;
    use App\Services\GuardianWriteService;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class ParentDashboardAccessTest extends TestCase
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            Student::resolveRelationUsing('currentClassroom', function ($student) {
                return $student->hasOne(ClassroomStudent::class)->where('status', 'active');
            });
        }

        private function createStudentWithGuardian(Academy $academy, ?User $studentUser = null): array
        {
            $student = Student::create([
                'academy_id' => $academy->id,
                'user_id' => $studentUser?->id,
                'student_id' => 'S'.uniqid(),
                'first_name_th' => 'เธ—เธ”เธชเธญเธ',
                'last_name_th' => 'เธเธนเนเธเธเธเธฃเธญเธ',
                'status' => 'active',
            ]);

            $guardianData = [
                'first_name' => 'Somchai',
                'last_name' => 'Jaidee',
                'citizen_id' => '123'.rand(1000000000, 9999999999),
                'guardian_type' => 'father',
                'relationship' => 'father',
                'status' => 'alive',
            ];

            $link = app(GuardianWriteService::class)->create($student, $guardianData);

            return [$student, $link->guardian];
        }

        public function test_parent_without_linked_account_gets_empty_children()
        {
            $academy = Academy::factory()->create();
            $this->createStudentWithGuardian($academy); // someone else's kid

            $parentUser = User::factory()->create();

            $response = $this->actingAs($parentUser, 'api')
                ->getJson("/api/academies/{$academy->id}/parent/children");

            $response->assertOk()
                ->assertJsonPath('children', []);
        }

        public function test_parent_with_linked_account_sees_child()
        {
            $academy = Academy::factory()->create();
            [$student, $guardian] = $this->createStudentWithGuardian($academy);

            $parentUser = User::factory()->create();

            // Setup link
            $owner = User::factory()->create(); // mock school admin
            $request = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $owner, $guardian);
            app(GuardianAccountLinkService::class)->accept($request, $parentUser);

            $response = $this->actingAs($parentUser, 'api')
                ->getJson("/api/academies/{$academy->id}/parent/children");

            $response->assertOk()
                ->assertJsonCount(1, 'children')
                ->assertJsonPath('children.0.id', $student->id);
        }

        public function test_parent_cannot_view_other_student_grades()
        {
            $academy = Academy::factory()->create();

            [$studentA, $guardianA] = $this->createStudentWithGuardian($academy);
            $parentUserA = User::factory()->create();
            $owner = User::factory()->create();
            $reqA = app(GuardianAccountLinkService::class)->createRequest($academy, $studentA, $parentUserA, $owner, $guardianA);
            app(GuardianAccountLinkService::class)->accept($reqA, $parentUserA);

            [$studentB, $guardianB] = $this->createStudentWithGuardian($academy); // Another child

            // Parent A accesses Student B's grades
            $this->actingAs($parentUserA, 'api')
                ->getJson("/api/academies/{$academy->id}/parent/children/{$studentB->id}/grades")
                ->assertForbidden();
        }

        public function test_parent_can_view_own_student_grades_and_attendance()
        {
            $academy = Academy::factory()->create();
            [$student, $guardian] = $this->createStudentWithGuardian($academy);
            $parentUser = User::factory()->create();
            $owner = User::factory()->create();
            $req = app(GuardianAccountLinkService::class)->createRequest($academy, $student, $parentUser, $owner, $guardian);
            app(GuardianAccountLinkService::class)->accept($req, $parentUser);

            $this->actingAs($parentUser, 'api')
                ->getJson("/api/academies/{$academy->id}/parent/children/{$student->id}/grades")
                ->assertOk();
        }

        public function test_parent_with_multiple_linked_children_sees_all_children()
        {
            $academy = Academy::factory()->create();
            $parentUser = User::factory()->create();
            $owner = User::factory()->create();

            // Create first student with guardian
            [$student1, $guardian] = $this->createStudentWithGuardian($academy);

            // Create request and accept it
            $req1 = app(GuardianAccountLinkService::class)->createRequest($academy, $student1, $parentUser, $owner, $guardian);
            app(GuardianAccountLinkService::class)->accept($req1, $parentUser);

            // Create second student and manually link to the SAME guardian (simulate sibling)
            $student2 = Student::create([
                'academy_id' => $academy->id,
                'student_id' => 'S'.uniqid(),
                'title_prefix_th' => 'เธ”.เธ.',
                'first_name_th' => 'เธ—เธ”เธชเธญเธ2',
                'last_name_th' => 'เธเนเธญเธ',
                'status' => 'active',
            ]);

            StudentGuardianLink::create([
                'student_id' => $student2->id,
                'guardian_id' => $guardian->id,
                'guardian_type' => 'father',
                'relationship' => 'father',
                'is_primary_contact' => true,
            ]);

            $response = $this->actingAs($parentUser, 'api')
                ->getJson("/api/academies/{$academy->id}/parent/children");

            $response->assertOk()
                ->assertJsonCount(2, 'children');

            $ids = collect($response->json('children'))->pluck('id')->toArray();
            $this->assertContains($student1->id, $ids);
            $this->assertContains($student2->id, $ids);
        }
    }
}
