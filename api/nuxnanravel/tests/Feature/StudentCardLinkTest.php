<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCardLinkTest extends TestCase
{
    use RefreshDatabase;

    private function createDependencies()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'username' => 'testuser'.uniqid(),
            'reference_code' => 'REF'.uniqid(),
            'personal_code' => 'PER'.uniqid(),
        ]);

        $academy = Academy::create([
            'name' => 'Test Academy',
            'user_id' => $user->id,
        ]);

        return [$user, $academy];
    }

    public function test_student_card_relation_works_with_foreign_key()
    {
        [$user, $academy] = $this->createDependencies();

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'STU001',
            'citizen_id' => '1234567890123',
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'student_number' => 'STU001',
            'national_id' => '1234567890123',
            'full_name_thai' => 'Test Student',
        ]);

        // Test relation from Student to StudentCard
        $this->assertEquals($card->id, $student->studentCard->id);

        // Test relation from StudentCard to Student
        $card->refresh();
        $this->assertEquals($student->id, $card->student_id, 'FK value mismatch');

        $foundStudent = Student::find($card->student_id);
        $this->assertNotNull($foundStudent, 'Student should be found by direct find()');

        $this->assertNotNull($card->student, 'student relation should not be null');
        $this->assertEquals($student->id, $card->student->id);
    }

    public function test_legacy_accessor_still_works()
    {
        [$user, $academy] = $this->createDependencies();

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'STU002',
            'citizen_id' => '2234567890123',
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => null, // No FK yet
            'student_number' => 'STU002',
            'national_id' => '2234567890123',
            'full_name_thai' => 'Test Student',
        ]);

        // Test legacy accessor
        $this->assertEquals($card->id, $student->legacy_student_card->id);

        // Test unified accessor
        $this->assertEquals($card->id, $student->student_card->id);
    }

    public function test_backfill_command_matches_correctly()
    {
        [$user, $academy] = $this->createDependencies();

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'STU003',
            'citizen_id' => '3234567890123',
            'first_name_th' => 'Test',
            'last_name_th' => 'Student',
        ]);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => null,
            'student_number' => 'STU003',
            'national_id' => 'X', // Mismatch this one
            'full_name_thai' => 'Test Student',
        ]);

        $this->artisan('students:backfill-card-link')->assertExitCode(0);

        $card->refresh();
        $this->assertEquals($student->id, $card->student_id);
    }
}
