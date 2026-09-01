<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\RecentlyViewedCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `/api/me/recent-courses` ต้องคืนคอร์สเรียงตาม "ดูล่าสุดก่อน"
 *
 * เดิมเรียงด้วย `orderByRaw('FIELD(id, ...)')` ซึ่งเป็นฟังก์ชันของ MySQL เท่านั้น
 * ⇒ endpoint นี้ตอบ 500 บนทุก driver ที่ไม่ใช่ MySQL และเทสต์ครอบไม่ได้เลย
 * (แพตเทิร์นเดียวกับที่เจอใน `EmergencyAlertController::active()` ตอนปิด G18)
 *
 * เทสต์นี้ต้อง assert **ลำดับ** ไม่ใช่แค่ status code — ถ้า assert แค่ 200
 * การเปลี่ยนวิธีเรียงจะทำให้ลำดับเพี้ยนโดยไม่มีอะไรจับได้
 */
class RecentCoursesOrderTest extends TestCase
{
    use RefreshDatabase;

    private function markViewed(User $user, Course $course, string $viewedAt): void
    {
        $row = RecentlyViewedCourse::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        // `updated_at` คือคีย์ที่ใช้เรียง ต้องเซ็ตหลังสร้างเพราะ timestamps จะทับตอน create
        $row->forceFill(['updated_at' => $viewedAt])->saveQuietly();
    }

    public function test_recent_courses_come_back_newest_view_first()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $oldest = Course::factory()->create();
        $middle = Course::factory()->create();
        $newest = Course::factory()->create();

        // จงใจสร้างเรียงกลับด้านกับลำดับที่คาด เพื่อไม่ให้ผ่านด้วยลำดับ id เผอิญ
        $this->markViewed($user, $oldest, '2026-01-01 08:00:00');
        $this->markViewed($user, $middle, '2026-01-02 08:00:00');
        $this->markViewed($user, $newest, '2026-01-03 08:00:00');

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/me/recent-courses')
            ->assertStatus(200);

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            array_column($response->json('courses'), 'id'),
            'ลำดับต้องเป็น "ดูล่าสุดก่อน" ไม่ใช่ลำดับ id'
        );
    }

    public function test_recent_courses_are_capped_at_five()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $courses = Course::factory()->count(7)->create();

        foreach ($courses as $index => $course) {
            $this->markViewed($user, $course, '2026-01-0'.($index + 1).' 08:00:00');
        }

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/me/recent-courses')
            ->assertStatus(200)
            ->assertJsonCount(5, 'courses');

        // 5 ตัวล่าสุดคือ index 6..2 (เรียงใหม่ไปเก่า)
        $this->assertSame(
            $courses->slice(2)->reverse()->pluck('id')->values()->all(),
            array_column($response->json('courses'), 'id')
        );
    }

    public function test_no_recent_views_returns_an_empty_list()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user, 'api')
            ->getJson('/api/me/recent-courses')
            ->assertStatus(200)
            ->assertJsonPath('courses', []);
    }
}
