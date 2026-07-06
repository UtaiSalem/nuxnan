<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademyMember;
use App\Models\StudentAccountInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentActivationController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $invitation = $this->findInvitationByToken($token);

        if (! $invitation) {
            return response()->json(['success' => false, 'message' => 'ลิงก์ไม่ถูกต้องหรือหมดอายุ'], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'ลิงก์นี้ถูกใช้งานแล้ว'], 410);
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'ลิงก์หมดอายุแล้ว กรุณาติดต่อนายทะเบียน'], 410);
        }

        $student = $invitation->student;
        $academy = $invitation->academy;

        return response()->json([
            'success' => true,
            'data' => [
                'student_name' => trim(($student->title_prefix_th ?? '').$student->first_name_th.' '.$student->last_name_th),
                'student_id' => $student->student_id,
                'academy_name' => $academy->name ?? $academy->display_name ?? '',
                'academy_logo' => $academy->logo_url ?? null,
            ],
        ]);
    }

    public function activate(Request $request, string $token): JsonResponse
    {
        $invitation = $this->findInvitationByToken($token);

        if (! $invitation) {
            return response()->json(['success' => false, 'message' => 'ลิงก์ไม่ถูกต้องหรือหมดอายุ'], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'ลิงก์นี้ถูกใช้งานแล้ว'], 410);
        }

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'ลิงก์หมดอายุแล้ว'], 410);
        }

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        $student = $invitation->student;
        $academy = $invitation->academy;

        $result = DB::transaction(function () use ($request, $student, $academy, $invitation) {
            $username = User::normalizeUsername($student->first_name_th.' '.$student->last_name_th);
            $existingUsername = User::where('username', $username)->exists();
            if ($existingUsername) {
                $username = $username.'_'.$student->student_id;
            }

            $user = User::create([
                'name' => $student->first_name_th.' '.$student->last_name_th,
                'username' => $username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'verified' => true,
            ]);

            $student->update([
                'user_id' => $user->id,
                'account_status' => 'active',
            ]);

            AcademyMember::firstOrCreate(
                ['user_id' => $user->id, 'academy_id' => $academy->id],
                [
                    'student_id' => $student->id,
                    'role' => 'student',
                ]
            );

            $invitation->update([
                'status' => 'activated',
                'activated_at' => now(),
            ]);

            return $user;
        });

        $jwtToken = auth('api')->login($result);

        return response()->json([
            'success' => true,
            'message' => 'เปิดบัญชีสำเร็จ! ยินดีต้อนรับ',
            'token' => $jwtToken,
            'user' => $result->only(['id', 'name', 'email', 'username']),
        ]);
    }

    private function findInvitationByToken(string $token): ?StudentAccountInvitation
    {
        $invitations = StudentAccountInvitation::with(['student', 'academy'])
            ->where('status', 'pending')
            ->get();

        foreach ($invitations as $invitation) {
            try {
                $decrypted = Crypt::decryptString($invitation->token_hash);
                if ($decrypted === $token) {
                    return $invitation;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
