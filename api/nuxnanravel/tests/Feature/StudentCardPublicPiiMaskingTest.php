<?php

namespace Tests\Feature;

use App\Http\Resources\StudentCardPublicResource;
use App\Http\Resources\StudentCardResource;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StudentCardPublicPiiMaskingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'U'.uniqid(),
            'email' => 'u'.uniqid().'@x.test',
            'password' => bcrypt('x'),
            'username' => 'u'.uniqid(),
            'reference_code' => 'R'.uniqid(),
            'personal_code' => 'P'.uniqid(),
        ]);
    }

    /**
     * @return array{0: Academy, 1: Student, 2: StudentCard}
     */
    private function makeCard(string $citizenId, string $dob): array
    {
        $academy = Academy::create([
            'name' => 'Academy_'.uniqid(),
            'user_id' => $this->makeUser()->id,
        ]);

        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $this->makeUser()->id,
            'student_id' => 'STU'.uniqid(),
            'citizen_id' => $citizenId,
            'title_prefix_th' => 'เด็กชาย',
            'first_name_th' => 'สมปอง',
            'last_name_th' => 'ใจดี',
            'date_of_birth' => $dob,
            'status' => 'studying',
        ]);

        $card = StudentCard::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'student_number' => $student->student_id,
            'full_name_thai' => 'เด็กชาย สมปอง ใจดี',
            'class_level' => '1',
            'class_section' => '2',
            'student_status' => 'active',
        ]);

        return [$academy, $student, $card->fresh()];
    }

    public function test_public_resource_masks_national_id_and_withholds_birth_date(): void
    {
        [, , $card] = $this->makeCard('1234567890123', '2010-05-15');

        $data = (new StudentCardPublicResource($card))->toArray(Request::create('/'));

        // Only the final two ID groups (3 digits) remain visible.
        $this->assertSame('x-xxxx-xxxxx-12-3', $data['national_id']);
        $this->assertNull($data['birth_date']);
        $this->assertNull($data['birth_date_string']);

        // Non-sensitive display fields are still present.
        $this->assertSame('เด็กชาย สมปอง ใจดี', $data['full_name_thai']);
    }

    public function test_public_resource_masks_a_different_national_id(): void
    {
        [, , $card] = $this->makeCard('1101700203451', '2011-01-20');

        $data = (new StudentCardPublicResource($card))->toArray(Request::create('/'));

        $this->assertSame('x-xxxx-xxxxx-45-1', $data['national_id']);
        $this->assertNull($data['birth_date']);
    }

    public function test_authenticated_resource_still_returns_full_pii(): void
    {
        [, , $card] = $this->makeCard('1234567890123', '2010-05-15');

        $data = (new StudentCardResource($card))->toArray(Request::create('/'));

        $this->assertSame('1234567890123', $data['national_id']);
        $this->assertNotNull($data['birth_date']);
    }
}
