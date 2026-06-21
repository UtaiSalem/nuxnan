<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_single_type(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Single type match',
            'type' => Notification::TYPE_STUDENT_PROMOTED,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Other type',
            'type' => Notification::TYPE_STUDENT_DROPPED,
        ]);

        Notification::create([
            'user_id' => $otherUser->id,
            'content' => 'Other user',
            'type' => Notification::TYPE_STUDENT_PROMOTED,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notifications?type='.Notification::TYPE_STUDENT_PROMOTED);

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.type', Notification::TYPE_STUDENT_PROMOTED)
            ->assertJsonPath('data.data.0.content', 'Single type match');
    }

    public function test_index_filters_by_multiple_types_array(): void
    {
        $user = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Promoted notice',
            'type' => Notification::TYPE_STUDENT_PROMOTED,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Rollover notice',
            'type' => Notification::TYPE_ROLLOVER_COMMITTED,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'content' => 'Dropped notice',
            'type' => Notification::TYPE_STUDENT_DROPPED,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/notifications?types[]='.Notification::TYPE_STUDENT_PROMOTED.'&types[]='.Notification::TYPE_ROLLOVER_COMMITTED);

        $response->assertOk()
            ->assertJsonCount(2, 'data.data');

        $types = collect($response->json('data.data'))->pluck('type')->all();
        $this->assertEqualsCanonicalizing([
            Notification::TYPE_STUDENT_PROMOTED,
            Notification::TYPE_ROLLOVER_COMMITTED,
        ], $types);
    }
}
