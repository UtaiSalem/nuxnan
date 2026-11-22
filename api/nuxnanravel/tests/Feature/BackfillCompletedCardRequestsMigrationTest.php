<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use App\Models\StudentCardRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillCompletedCardRequestsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function setupStudentAndAcademy(): array
    {
        $user = User::create([
            'name' => 'Card repair student',
            'email' => uniqid('repair').'@x.test',
            'password' => bcrypt('x'),
            'username' => uniqid('repair'),
            'reference_code' => uniqid('R'),
            'personal_code' => uniqid('P'),
        ]);
        $academy = Academy::create([
            'name' => uniqid('RepairAcademy_'),
            'user_id' => $user->id,
        ]);
        $year = AcademicYear::create([
            'academy_id' => $academy->id,
            'name' => '2569',
            'is_current' => true,
            'start_date' => '2026-05-16',
            'end_date' => '2027-03-31',
        ]);
        $classroom = Classroom::create([
            'academy_id' => $academy->id,
            'academic_year_id' => $year->id,
            'grade_level' => 'ม.5',
            'section' => '3',
            'name' => 'ม.5/3',
        ]);
        $student = Student::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'student_id' => 'STU1001',
            'citizen_id' => '1103700123456',
            'title_prefix_th' => 'นางสาว',
            'first_name_th' => 'วริษฐา',
            'last_name_th' => 'ใจดี',
            'first_name_en' => 'Waritta',
            'date_of_birth' => '2009-05-27',
            'profile_image' => 'images/students/profiles/x.jpeg',
            'status' => 'active',
        ]);

        // Enrollment must precede cards: its observer can reactivate expired cards.
        $enrollment = ClassroomStudent::create([
            'academy_id' => $academy->id,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'student_number' => 1,
        ]);

        return compact('academy', 'year', 'classroom', 'student', 'enrollment');
    }

    private function makeCard(array $fixture, bool $backdate = true, string $status = 'active'): StudentCard
    {
        $card = StudentCard::create([
            'academy_id' => $fixture['academy']->id,
            'student_id' => $fixture['student']->id,
            'academic_year_id' => $fixture['year']->id,
            'student_number' => 'STU1001',
            'national_id' => '1103700123456',
            'full_name_thai' => 'นางสาว วริษฐา ใจดี',
            'class_level' => '5',
            'class_section' => 3,
            'student_status' => $status,
        ]);
        if ($backdate) {
            DB::table('student_cards')->where('id', $card->id)->update([
                'created_at' => '2025-06-18 08:00:00',
                'updated_at' => '2025-06-18 08:00:00',
                'card_issue_date' => '2025-06-18 08:00:00',
                'card_expiry_date' => '2028-06-18 08:00:00',
            ]);
        }

        return $card->fresh();
    }

    private function makeRequest(array $fixture, ?StudentCard $old = null, array $overrides = []): StudentCardRequest
    {
        return StudentCardRequest::create(array_replace([
            'academy_id' => $fixture['academy']->id,
            'academic_year_id' => $fixture['year']->id,
            'classroom_id' => $fixture['classroom']->id,
            'student_id' => $fixture['student']->id,
            'existing_card_id' => $old?->id,
            'request_type' => 'replacement',
            'status' => 'completed',
            'origin' => 'public',
            'priority' => 'normal',
            'full_name_snapshot' => 'นางสาว วริษฐา ใจดี',
            'student_number_snapshot' => 'STU1001',
            'grade_level_snapshot' => 'ม.5',
            'section_snapshot' => '3',
            'reason_code' => 'lost',
            'requested_at' => '2026-09-17 16:08:05',
            'approved_at' => '2026-09-18 09:00:00',
            'started_at' => '2026-09-19 09:00:00',
            'completed_at' => '2026-09-25 10:53:20',
            'result_card_id' => null,
        ], $overrides));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_22_000000_backfill_cards_for_completed_card_requests.php');
    }

    private function activeCards(array $fixture)
    {
        return DB::table('student_cards')
            ->where('student_id', $fixture['student']->id)
            ->where('academy_id', $fixture['academy']->id)
            ->where('student_status', 'active');
    }

    private function backupCount(): int
    {
        return DB::table('classroom_repair_backups')->where('batch', '2026_11_22_000000')->count();
    }

    private function cardSnapshot(): array
    {
        return DB::table('student_cards')->orderBy('id')->get()->toArray();
    }

    private function requestSnapshot(): array
    {
        return DB::table('student_card_requests')->orderBy('id')->get()->toArray();
    }

    private function twoRequests(): array
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture);
        // Insert B first so ordering by id alone cannot satisfy the test.
        $requestB = $this->makeRequest($fixture, $old, ['completed_at' => '2026-10-20 09:00:00']);
        $requestA = $this->makeRequest($fixture, $old);

        return [$fixture, $old, $requestA, $requestB];
    }

    public function test_backfills_card_for_completed_public_replacement(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture);
        $request = $this->makeRequest($fixture, $old);
        $this->assertNull($request->fresh()->result_card_id);

        $this->migration()->up();

        $this->assertSame('expired', $old->fresh()->student_status);
        $this->assertSame(1, $this->activeCards($fixture)->count());
        $card = $this->activeCards($fixture)->first();
        $this->assertNotEquals($old->id, $card->id);
        foreach ([
            'academy_id' => $fixture['academy']->id,
            'academic_year_id' => $request->academic_year_id,
            'student_id' => $fixture['student']->id,
            'student_number' => 'STU1001',
            'national_id' => '1103700123456',
            'title_name' => 'นางสาว',
            'first_name_thai' => 'วริษฐา',
            'last_name_thai' => 'ใจดี',
            'first_name_english' => 'Waritta',
            'full_name_thai' => 'นางสาว วริษฐา ใจดี',
            'birth_date_string' => '27/05/2009',
            'class_level' => 5,
            'class_section' => 3,
            'level_and_room' => 'ม.5/3',
            'profile_image' => 'images/students/profiles/x.jpeg',
        ] as $column => $expected) {
            $this->assertEquals($expected, $card->{$column}, $column);
        }
        $this->assertSame('2009-05-27', Carbon::parse($card->birth_date)->toDateString());
        $this->assertSame('2026-09-25 10:53:20', Carbon::parse($card->card_issue_date)->toDateTimeString());
        $this->assertSame('2029-09-25 10:53:20', Carbon::parse($card->card_expiry_date)->toDateTimeString());
        $this->assertEquals($card->id, $request->fresh()->result_card_id);
        $this->assertNull($request->fresh()->processed_by);
        $this->assertDatabaseHas('student_card_requests', ['id' => $request->id, 'status' => 'completed']);
    }

    public function test_backfills_first_card_for_enrolled_student_without_any_card(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $request = $this->makeRequest($fixture, null, ['request_type' => 'first_issue']);
        $this->assertDatabaseCount('student_cards', 0);

        $this->migration()->up();

        $this->assertDatabaseCount('student_cards', 1);
        $this->assertSame(1, $this->activeCards($fixture)->count());
        $this->assertEquals($this->activeCards($fixture)->first()->id, $request->fresh()->result_card_id);
    }

    public function test_skips_when_a_newer_active_card_exists(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $this->travelTo(Carbon::parse('2026-10-01 09:00:00'));
        $card = $this->makeCard($fixture, false);
        $this->travelBack();
        $request = $this->makeRequest($fixture, $card);
        $cardsBefore = $this->cardSnapshot();
        $requestsBefore = $this->requestSnapshot();

        $this->migration()->up();

        $this->assertEquals($cardsBefore, $this->cardSnapshot());
        $this->assertEquals($requestsBefore, $this->requestSnapshot());
        $this->assertSame('active', $card->fresh()->student_status);
        $this->assertNull($request->fresh()->result_card_id);
    }

    public function test_skips_student_without_active_card_who_is_no_longer_enrolled(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture, true, 'expired');
        DB::table('classroom_students')->where('id', $fixture['enrollment']->id)->update(['status' => 'graduated']);
        $request = $this->makeRequest($fixture, $old);
        $cardsBefore = $this->cardSnapshot();
        $requestsBefore = $this->requestSnapshot();

        $this->migration()->up();

        $this->assertEquals($cardsBefore, $this->cardSnapshot());
        $this->assertEquals($requestsBefore, $this->requestSnapshot());
        $this->assertNull($request->fresh()->result_card_id);
    }

    public function test_processes_two_completed_requests_for_one_student_in_completion_order(): void
    {
        [$fixture, $old, $requestA, $requestB] = $this->twoRequests();

        $this->migration()->up();

        $cardA = DB::table('student_cards')->find($requestA->fresh()->result_card_id);
        $cardB = DB::table('student_cards')->find($requestB->fresh()->result_card_id);
        $this->assertNotNull($cardA);
        $this->assertNotNull($cardB);
        $this->assertNotEquals($old->id, $cardA->id);
        $this->assertNotEquals($cardA->id, $cardB->id);
        $this->assertSame('expired', $old->fresh()->student_status);
        $this->assertSame('expired', $cardA->student_status);
        $this->assertSame('active', $cardB->student_status);
        $this->assertSame('2026-09-25 10:53:20', Carbon::parse($cardA->card_issue_date)->toDateTimeString());
        $this->assertSame('2026-10-20 09:00:00', Carbon::parse($cardB->card_issue_date)->toDateTimeString());
        $this->assertSame(1, $this->activeCards($fixture)->count());
        $this->assertDatabaseCount('student_cards', 3);
    }

    public function test_skips_row_whose_value_does_not_fit_the_card_column(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('ความยาวคอลัมน์บังคับเฉพาะ MySQL');
        }

        $fixture = $this->setupStudentAndAcademy();
        $fixture['student']->update(['title_prefix_th' => str_repeat('ก', 15)]);
        $old = $this->makeCard($fixture);
        $request = $this->makeRequest($fixture, $old);
        $cardsBefore = $this->cardSnapshot();
        $requestsBefore = $this->requestSnapshot();

        $this->migration()->up();

        $this->assertEquals($cardsBefore, $this->cardSnapshot());
        $this->assertEquals($requestsBefore, $this->requestSnapshot());
        $this->assertSame('active', $old->fresh()->student_status);
        $this->assertNull($request->fresh()->result_card_id);
    }

    public function test_ignores_requests_that_are_not_completed_or_already_have_a_card(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture);
        $this->makeRequest($fixture, $old, ['status' => 'pending', 'completed_at' => null]);
        $second = $this->setupStudentAndAcademy();
        $existing = $this->makeCard($second);
        $this->makeRequest($second, $existing, ['result_card_id' => $existing->id]);
        $cardsBefore = $this->cardSnapshot();
        $requestsBefore = $this->requestSnapshot();

        $this->migration()->up();

        $this->assertEquals($cardsBefore, $this->cardSnapshot());
        $this->assertEquals($requestsBefore, $this->requestSnapshot());
    }

    public function test_running_up_twice_creates_no_duplicates(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture);
        $request = $this->makeRequest($fixture, $old);
        $this->migration()->up();
        $cardId = $request->fresh()->result_card_id;
        $backups = $this->backupCount();
        $this->assertNotNull($cardId);
        $this->assertSame(3, $backups);

        $this->migration()->up();

        $this->assertDatabaseCount('student_cards', 2);
        $this->assertSame(1, $this->activeCards($fixture)->count());
        $this->assertEquals($cardId, $request->fresh()->result_card_id);
        $this->assertSame($backups, $this->backupCount());
    }

    public function test_down_restores_cards_and_requests(): void
    {
        [$fixture, $old, $requestA, $requestB] = $this->twoRequests();
        $this->migration()->up();
        $cardAId = $requestA->fresh()->result_card_id;
        $cardBId = $requestB->fresh()->result_card_id;
        $this->assertNotNull($cardAId);
        $this->assertNotNull($cardBId);
        $this->assertSame(6, $this->backupCount());

        $this->migration()->down();

        $this->assertDatabaseMissing('student_cards', ['id' => $cardAId]);
        $this->assertDatabaseMissing('student_cards', ['id' => $cardBId]);
        $this->assertDatabaseCount('student_cards', 1);
        $this->assertSame('active', $old->fresh()->student_status);
        $this->assertSame('2025-06-18 08:00:00', Carbon::parse($old->fresh()->updated_at)->toDateTimeString());
        $this->assertNull($requestA->fresh()->result_card_id);
        $this->assertNull($requestB->fresh()->result_card_id);
        $this->assertSame(0, $this->backupCount());
        $this->assertSame(1, $this->activeCards($fixture)->count());
    }

    public function test_down_keeps_old_card_expired_when_a_newer_card_was_issued_later(): void
    {
        $fixture = $this->setupStudentAndAcademy();
        $old = $this->makeCard($fixture);
        $request = $this->makeRequest($fixture, $old);
        $this->migration()->up();
        $backfilledId = $request->fresh()->result_card_id;
        $this->assertNotNull($backfilledId);
        DB::table('student_cards')->where('id', $backfilledId)->update(['student_status' => 'expired']);
        $later = $this->makeCard($fixture, false);

        $this->migration()->down();

        $this->assertDatabaseMissing('student_cards', ['id' => $backfilledId]);
        $this->assertSame('active', $later->fresh()->student_status);
        $this->assertSame('expired', $old->fresh()->student_status);
        $this->assertSame(1, $this->activeCards($fixture)->count());
        $this->assertNull($request->fresh()->result_card_id);
        $this->assertSame(0, $this->backupCount());
    }
}
