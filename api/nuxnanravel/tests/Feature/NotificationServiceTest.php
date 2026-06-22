<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser()
    {
        return User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'username' => 'user' . uniqid(),
            'reference_code' => 'REF' . uniqid(),
            'personal_code' => 'PER' . uniqid(),
        ]);
    }

    public function test_send_creates_single_notification()
    {
        $user = $this->createUser();
        $sender = $this->createUser();
        
        $service = new NotificationService();
        $notification = $service->send([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'type' => 'test_type',
            'content' => 'Test content',
            'action_url' => '/test/url',
            'related_id' => 999,
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals($sender->id, $notification->sender_id);
        $this->assertEquals('test_type', $notification->type);
        $this->assertEquals('Test content', $notification->content);
        $this->assertEquals('/test/url', $notification->action_url);
        $this->assertEquals(999, $notification->related_id);
        $this->assertEquals(['key' => 'value'], $notification->metadata);
        $this->assertFalse($notification->read_status);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'test_type',
        ]);
    }

    public function test_send_bulk_creates_multiple_notifications()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $sender = $this->createUser();

        $service = new NotificationService();
        $count = $service->sendBulk([
            [
                'user_id' => $user1->id,
                'sender_id' => $sender->id,
                'type' => 'test_bulk',
                'content' => 'Bulk content 1',
                'action_url' => '/url/1',
                'related_id' => 111,
                'metadata' => ['meta' => 1],
            ],
            [
                'user_id' => $user2->id,
                'sender_id' => $sender->id,
                'type' => 'test_bulk',
                'content' => 'Bulk content 2',
                'action_url' => '/url/2',
                'related_id' => 222,
                'metadata' => ['meta' => 2],
            ]
        ]);

        $this->assertEquals(2, $count);
        $this->assertDatabaseCount('notifications', 2);

        $noti1 = Notification::where('user_id', $user1->id)->first();
        $this->assertNotNull($noti1);
        $this->assertEquals('Bulk content 1', $noti1->content);
        $this->assertEquals('/url/1', $noti1->action_url);
        $this->assertEquals(111, $noti1->related_id);
        $this->assertEquals(['meta' => 1], $noti1->metadata);

        $noti2 = Notification::where('user_id', $user2->id)->first();
        $this->assertNotNull($noti2);
        $this->assertEquals('Bulk content 2', $noti2->content);
        $this->assertEquals('/url/2', $noti2->action_url);
        $this->assertEquals(222, $noti2->related_id);
        $this->assertEquals(['meta' => 2], $noti2->metadata);
    }
}
