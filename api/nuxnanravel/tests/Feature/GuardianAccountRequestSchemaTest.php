<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Guardian;
use App\Models\GuardianAccountRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuardianAccountRequestSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(Academy $academy, ?User $studentUser = null): Student
    {
        return Student::create([
            'academy_id' => $academy->id,
            'user_id' => $studentUser?->id,
            'student_id' => 'S'.uniqid(),
            'title_prefix_th' => 'นาย',
            'first_name_th' => 'ทดสอบ',
            'last_name_th' => 'ผู้ปกครอง',
            'status' => 'active',
        ]);
    }

    public function test_can_create_guardian_account_request_with_all_columns(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $user = User::factory()->create();
        $guardian = Guardian::create([
            'academy_id' => $academy->id,
            'citizen_id' => '1234567890123',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
        ]);

        $respondedAt = now()->subDay();

        $request = GuardianAccountRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'user_id' => $user->id,
            'direction' => GuardianAccountRequest::DIRECTION_GUARDIAN,
            'initiated_by_user_id' => $studentUser->id,
            'initiated_by_role' => GuardianAccountRequest::ROLE_STUDENT,
            'status' => GuardianAccountRequest::STATUS_DECLINED,
            'responded_by_user_id' => $user->id,
            'responded_at' => $respondedAt,
            'decline_reason' => 'Not my child',
        ]);

        $fetched = GuardianAccountRequest::find($request->id);

        $this->assertEquals($academy->id, $fetched->academy_id);
        $this->assertEquals($student->id, $fetched->student_id);
        $this->assertEquals($guardian->id, $fetched->guardian_id);
        $this->assertEquals($user->id, $fetched->user_id);
        $this->assertEquals(GuardianAccountRequest::DIRECTION_GUARDIAN, $fetched->direction);
        $this->assertEquals($studentUser->id, $fetched->initiated_by_user_id);
        $this->assertEquals(GuardianAccountRequest::ROLE_STUDENT, $fetched->initiated_by_role);
        $this->assertEquals(GuardianAccountRequest::STATUS_DECLINED, $fetched->status);
        $this->assertEquals($user->id, $fetched->responded_by_user_id);
        $this->assertInstanceOf(Carbon::class, $fetched->responded_at);
        $this->assertEquals($respondedAt->format('Y-m-d H:i:s'), $fetched->responded_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Not my child', $fetched->decline_reason);
    }

    public function test_guardian_id_can_be_null(): void
    {
        $academy = Academy::factory()->create();
        $studentUser = User::factory()->create();
        $student = $this->createStudent($academy, $studentUser);
        $user = User::factory()->create();

        $request = GuardianAccountRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'guardian_id' => null,
            'user_id' => $user->id,
            'direction' => GuardianAccountRequest::DIRECTION_STUDENT,
            'status' => GuardianAccountRequest::STATUS_PENDING,
        ]);

        $this->assertNull($request->fresh()->guardian_id);
    }

    public function test_unique_academy_id_user_id_on_guardians_table(): void
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create();

        Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'citizen_id' => '1111111111111',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
        ]);

        $this->expectException(QueryException::class);

        Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'citizen_id' => '2222222222222',
            'first_name' => 'Somsri',
            'last_name' => 'Jaidee',
        ]);
    }

    public function test_user_id_can_be_null_multiple_times_in_same_academy(): void
    {
        $academy = Academy::factory()->create();

        $g1 = Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => null,
            'citizen_id' => '1111111111111',
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
        ]);

        $g2 = Guardian::create([
            'academy_id' => $academy->id,
            'user_id' => null,
            'citizen_id' => '2222222222222',
            'first_name' => 'Somsri',
            'last_name' => 'Jaidee',
        ]);

        $this->assertNull($g1->user_id);
        $this->assertNull($g2->user_id);
    }

    public function test_scope_pending_returns_only_pending_requests(): void
    {
        $academy = Academy::factory()->create();
        $student = $this->createStudent($academy);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $r1 = GuardianAccountRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'user_id' => $user1->id,
            'direction' => GuardianAccountRequest::DIRECTION_STUDENT,
            'status' => GuardianAccountRequest::STATUS_PENDING,
        ]);

        $r2 = GuardianAccountRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'user_id' => $user2->id,
            'direction' => GuardianAccountRequest::DIRECTION_STUDENT,
            'status' => GuardianAccountRequest::STATUS_ACCEPTED,
        ]);

        $r3 = GuardianAccountRequest::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'user_id' => $user3->id,
            'direction' => GuardianAccountRequest::DIRECTION_STUDENT,
            'status' => GuardianAccountRequest::STATUS_PENDING,
        ]);

        $pendingRequests = GuardianAccountRequest::pending()->get();

        $this->assertCount(2, $pendingRequests);
        $this->assertTrue($pendingRequests->contains('id', $r1->id));
        $this->assertTrue($pendingRequests->contains('id', $r3->id));
        $this->assertFalse($pendingRequests->contains('id', $r2->id));
    }
}
