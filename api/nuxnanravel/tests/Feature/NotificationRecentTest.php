<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRecentTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_notifications_load_with_sender(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Test notification',
            'type' => 'test',
            'sender_id' => $sender->id,
            'read_status' => false,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notifications/recent');

        $response->assertOk();

        $payload = $response->json();
        $this->assertNotEmpty($payload['data']['notifications']);
        $this->assertArrayHasKey('sender', $payload['data']['notifications'][0]);
        $sender = $payload['data']['notifications'][0]['sender'];

        // คีย์ avatar ยังต้องมาครบ (มาจาก $appends ของ User) ⇒ frontend ที่อ่าน sender.avatar ไม่ต้องแก้
        $this->assertArrayHasKey('avatar', $sender);

        // ต้องเลือก profile_photo_path มาจริง — SQLite ไม่ฟ้องเมื่อ select คอลัมน์ที่ไม่มีอยู่
        // (พิสูจน์แล้ว: ใส่ 'avatar' กลับไปแล้วเทสต์ยังเขียวบน SQLite แต่ MySQL ตอบ 500)
        // การ assert ว่าคอลัมน์จริงถูกเลือกมา คือด่านเดียวที่จับ regression นี้ได้บนเทสต์ชุดนี้
        $this->assertArrayHasKey('profile_photo_path', $sender);
    }
}
