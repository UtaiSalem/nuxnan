<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * อีเมลตอบรับการสมัคร — "สมัครสำเร็จ กำลังรอผู้ดูแลอนุมัติ"
 *
 * 🔴 อย่าใส่ปุ่ม "ยืนยันอีเมล" กลับเข้ามา
 * ในโปรเจคนี้ `users.email_verified_at` **ไม่ใช่** "ผู้ใช้กดยืนยันอีเมลแล้ว"
 * แต่แปลว่า **"ผู้ดูแลอนุมัติบัญชีแล้ว"** — ดู `AuthController::login()` ที่บล็อกด้วยข้อความ
 * "บัญชีของคุณยังไม่ได้รับการอนุมัติจากผู้ดูแล" และ endpoint ฝั่งแอดมิน
 * `verify-email` / `bulk-verify` ที่เป็นคนเซ็ตค่านี้
 *
 * เวอร์ชันเดิมของไฟล์นี้สร้างลิงก์ด้วย `URL::temporarySignedRoute('verification.verify', ...)`
 * ทั้งที่ **ทั้งเรพไม่มี route ชื่อนั้น** ⇒ แค่ render ก็ throw · ไม่เคยมีใครส่งมันจริง
 * (`AuthService::register()` ซึ่งเป็นผู้เรียกรายเดียวก็เป็นโค้ดตายที่ INSERT ไม่ผ่าน)
 */
class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        $app = config('app.name');

        return new Envelope(
            subject: "ยินดีต้อนรับสู่ {$app} — บัญชีของคุณรอการอนุมัติ",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'displayName' => $this->user->name ?: $this->user->username,
                'appName' => config('app.name'),
            ],
        );
    }
}
