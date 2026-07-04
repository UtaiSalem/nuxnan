<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\Student;
use App\Models\StudentAccountInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;

class StudentAccountController extends Controller
{
    public function invite(Request $request, Academy $academy, Student $student): JsonResponse
    {
        if ($student->academy_id !== $academy->id) {
            return response()->json(['message' => 'Student not found in this academy.'], 404);
        }

        if ($student->user_id) {
            return response()->json(['message' => 'Student already has an account.'], 400);
        }

        $raw = Str::random(40);
        $invitation = StudentAccountInvitation::updateOrCreate(
            ['student_id' => $student->id, 'academy_id' => $academy->id],
            [
                'token_hash' => Crypt::encryptString($raw),
                'status' => 'pending',
                'created_by' => $request->user()->id,
                'expires_at' => now()->addDays(7),
            ]
        );

        $student->update(['account_status' => 'pending_activation']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation generated successfully.',
            'data' => [
                'token' => $raw,
                'expires_at' => $invitation->expires_at,
            ]
        ]);
    }

    public function exportInvitations(Academy $academy)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=invitations_export.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $callback = function() use ($academy) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['รหัสนักเรียน', 'ชื่อ', 'นามสกุล', 'Link เปิดบัญชี', 'หมดอายุ']);
            
            $baseUrl = config('app.frontend_url', 'http://localhost:3000') . '/activate-student/';
            
            $sanitize = function($val) {
                if ($val !== null && preg_match('/^[=\-+@]/', $val)) {
                    return "'" . $val;
                }
                return $val;
            };

            $query = StudentAccountInvitation::with('student')
                ->where('academy_id', $academy->id)
                ->where('status', 'pending');

            foreach ($query->cursor() as $inv) {
                if ($inv->student) {
                    try {
                        $raw = Crypt::decryptString($inv->token_hash);
                    } catch (\Exception $e) {
                        $raw = $inv->token_hash; // Fallback if it wasn't encrypted
                    }

                    fputcsv($file, [
                        $sanitize($inv->student->student_id),
                        $sanitize($inv->student->first_name_th),
                        $sanitize($inv->student->last_name_th),
                        $baseUrl . $raw,
                        $inv->expires_at->format('Y-m-d H:i')
                    ]);
                }
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
