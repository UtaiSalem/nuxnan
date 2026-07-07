<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardByStudentTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $tag = ''): User
    {
        return User::create([
            'name' => 'U'.$tag,
            'email' => 'u'.$tag.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => 'u'.$tag.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    private function setupStudentAndAcademy(): array
    {
        $owner = $this->makeUser('owner');
        $admin = $this->makeUser('adm');

        $academy = Academy::create([
            'name' => 'TestAcademy_'.uniqid(),
            'user_id' => $admin->id,
        ]);

        AcademyMember::create([
            'user_id' => $admin->id,
            'academy_id' => $academy->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $owner->id,
            'student_id' => 'STU1001',
            'citizen_id' => '1234567890123',
            'title_prefix_th' => 'เด็กชาย',
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี',
            'status' => 'studying',
        ]);

        AcademyMember::create([
            'user_id' => $owner->id,
            'academy_id' => $academy->id,
            'role' => 'student',
            'status' => 'active',
        ]);

        return [$owner, $admin, $academy, $student];
    }

    public function test_can_retrieve_student_card_by_student_id_successfully()
    {
        [$owner, $admin, $academy, $student] = $this->setupStudentAndAcademy();

        // Create student card linked to student
        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'student_number' => 'STU1001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '2',
            'student_status' => 'active',
        ]);

        // Access via authenticated owner
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/student-cards/by-student/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'student' => [
                    'id' => $card->id,
                    'student_number' => 'STU1001',
                    'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
                ],
            ]);
    }

    public function test_returns_null_when_card_does_not_exist()
    {
        [$owner, $admin, $academy, $student] = $this->setupStudentAndAcademy();

        // Access when card doesn't exist
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/academies/{$academy->id}/student-cards/by-student/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'student' => null,
            ]);
    }
}
