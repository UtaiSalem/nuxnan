<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * อีเมลตอบรับการสมัคร
 *
 * เคสที่สำคัญที่สุดคือ `test_the_welcome_email_actually_renders` — เวอร์ชันเดิมของ
 * `WelcomeEmail` สร้างลิงก์ด้วย `URL::temporarySignedRoute('verification.verify', ...)`
 * ทั้งที่ทั้งเรพไม่มี route ชื่อนั้น ⇒ **แค่ render ก็ throw**
 * ถ้า assert แค่ "ส่งเมลแล้ว" ด้วย `Mail::fake()` จะจับไม่ได้ เพราะ fake ไม่ render เนื้อหา
 */
class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'somchai test',
            'email' => 'somchai@example.test',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'reference_code' => User::ADMIN_SUGGESTER_CODE,
        ], $overrides);
    }

    public function test_registering_sends_the_welcome_email_to_the_new_user()
    {
        Mail::fake();

        $this->postJson('/api/register', $this->registerPayload())
            ->assertStatus(200);

        Mail::assertSent(WelcomeEmail::class, function (WelcomeEmail $mail) {
            return $mail->hasTo('somchai@example.test');
        });
    }

    /**
     * เนื้อหาต้องประกอบขึ้นมาได้จริง ไม่ใช่แค่ "ถูกส่ง"
     */
    public function test_the_welcome_email_actually_renders()
    {
        $user = User::factory()->create([
            'name' => 'สมชาย ใจดี',
            'username' => 'somchai',
        ]);

        $html = (new WelcomeEmail($user))->render();

        $this->assertStringContainsString('สมชาย ใจดี', $html);
        $this->assertStringContainsString('รอผู้ดูแลอนุมัติ', $html);
        $this->assertStringContainsString((string) config('app.name'), $html);
    }

    /**
     * โมเดลจริงของโปรเจคนี้คือ "รอแอดมินอนุมัติ" ไม่ใช่ "ผู้ใช้กดยืนยันอีเมลเอง"
     * อีเมลจึงต้องไม่ชวนให้ไปกดยืนยัน และต้องไม่มีแบรนด์ของเทมเพลตต้นทางหลงเหลือ
     */
    public function test_the_welcome_email_does_not_promise_self_service_verification()
    {
        $user = User::factory()->create(['name' => 'Test User', 'username' => 'testuser']);

        $html = (new WelcomeEmail($user))->render();

        $this->assertStringNotContainsStringIgnoringCase('vikinger', $html);
        $this->assertStringNotContainsString('ยืนยันบัญชี', $html);
        $this->assertStringNotContainsString('verification.verify', $html);
    }

    /**
     * บัญชีถูกสร้างไปแล้วตอนที่พยายามส่งเมล — เมลล่มต้องไม่ทำให้การสมัครล้ม
     */
    public function test_a_mail_failure_does_not_break_registration()
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP ล่ม'));

        $this->postJson('/api/register', $this->registerPayload([
            'username' => 'malee test',
            'email' => 'malee@example.test',
        ]))->assertStatus(200);

        $this->assertDatabaseHas('users', ['email' => 'malee@example.test']);
    }
}
