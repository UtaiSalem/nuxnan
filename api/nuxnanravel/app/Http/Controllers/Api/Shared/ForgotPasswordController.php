<?php

namespace App\Http\Controllers\Api\Shared;

use App\Models\User;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\AcademyMember;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends \App\Http\Controllers\Controller
{
    /**
     * ตรวจสอบสิทธิ์การเข้าถึงหน้า Admin Reset Password
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user || !$user->isPlearndAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง'
            ], 403);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * ค้นหาผู้ใช้จาก email, ชื่อ, เบอร์โทร หรือ รหัสประจำตัว
     */
    public function getUser(Request $request)
    {
        $search = $request->input('email', '');
        
        if (empty(trim($search))) {
            return response()->json([
                'users' => [],
                'success' => true,
            ], 200);
        }

        $users = UserResource::collection(
            User::where('email', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%')
                ->orWhere('phone_number', 'like', '%' . $search . '%')
                ->orWhere('personal_code', 'like', '%' . $search . '%')
                ->limit(10)
                ->get()
        );

        return response()->json([
            'users' => $users,
            'success' => true,
        ], 200);
    }

    /**
     * Admin รีเซ็ตรหัสผ่านให้ผู้ใช้
     * - ตัดแต้ม 4800 แต้มจากสมาชิก (ไม่ใช่ Admin)
     * - สามารถใช้แต้มร่วมกับ Wallet ได้
     * - อัตราแลกเปลี่ยน: 1 บาท = 1080 แต้ม
     * - Admin กำหนดรหัสผ่านเองได้ (default: 00000000)
     */
    public function resetPassword(User $user, Request $request)
    {
        $admin = auth()->user();
        
        // ตรวจสอบสิทธิ์ Admin
        if (!$admin || !$admin->isPlearndAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์ในการรีเซ็ตรหัสผ่าน'
            ], 403);
        }

        // Validate request
        $request->validate([
            'new_password' => 'nullable|string|min:4|max:50',
        ]);

        // ค่าแต้มที่ต้องใช้
        $requiredPoints = 4800;
        $pointsPerBaht = 1080;
        
        // ตรวจสอบแต้มและ wallet ของสมาชิก
        $userPoints = $user->pp ?? 0;
        $userWallet = $user->wallet ?? 0;
        
        // คำนวณมูลค่ารวมของแต้ม + wallet (แปลง wallet เป็นแต้ม)
        $walletAsPoints = $userWallet * $pointsPerBaht;
        $totalAvailablePoints = $userPoints + $walletAsPoints;
        
        // ตรวจสอบว่ามีเพียงพอหรือไม่ (รวมแต้ม + wallet)
        if ($totalAvailablePoints < $requiredPoints) {
            $neededPoints = $requiredPoints - $totalAvailablePoints;
            $neededMoney = ceil($neededPoints / $pointsPerBaht);
            
            return response()->json([
                'success' => false,
                'message' => 'แต้มและเงินใน Wallet ของสมาชิกไม่เพียงพอ',
                'required_points' => $requiredPoints,
                'user_points' => $userPoints,
                'user_wallet' => $userWallet,
                'total_available_points' => $totalAvailablePoints,
                'needed_points' => $neededPoints,
                'needed_money' => $neededMoney,
            ], 402);
        }

        // ใช้รหัสผ่านที่กำหนด หรือ default = 00000000
        $newPassword = $request->input('new_password');
        if (empty($newPassword)) {
            $newPassword = '00000000';
        }

        try {
            DB::beginTransaction();
            
            $pointsDeducted = 0;
            $moneyDeducted = 0;
            $remainingToDeduct = $requiredPoints;
            
            // 1. หักจากแต้มก่อน (ใช้แต้มให้หมดก่อน)
            if ($userPoints > 0) {
                $pointsToUse = min($userPoints, $remainingToDeduct);
                $user->decrement('pp', $pointsToUse);
                $pointsDeducted = $pointsToUse;
                $remainingToDeduct -= $pointsToUse;
            }
            
            // 2. ถ้ายังไม่พอ หักจาก Wallet
            if ($remainingToDeduct > 0) {
                // คำนวณเงินที่ต้องหัก (ปัดขึ้น)
                $moneyNeeded = ceil($remainingToDeduct / $pointsPerBaht);
                $user->decrement('wallet', $moneyNeeded);
                $moneyDeducted = $moneyNeeded;
                
                // เพิ่มแต้มจากเงิน แล้วหักแต้มที่เหลือ
                $pointsFromMoney = $moneyNeeded * $pointsPerBaht;
                $user->increment('pp', $pointsFromMoney);
                $user->decrement('pp', $remainingToDeduct);
            }
            
            // อัพเดทรหัสผ่าน
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // ลบ password reset tokens ถ้ามี
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            DB::commit();
            
            // Refresh user data
            $user->refresh();

            // Log การกระทำ
            Log::info('Admin reset password for user', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'points_deducted' => $pointsDeducted,
                'money_deducted' => $moneyDeducted,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'รีเซ็ตรหัสผ่านสำเร็จ',
                'new_password' => $newPassword,
                'points_deducted' => $pointsDeducted,
                'money_deducted' => $moneyDeducted,
                'user_remaining_points' => $user->pp,
                'user_remaining_wallet' => $user->wallet,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password reset failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน'
            ], 500);
        }
    }

    public function exchangeMoney(User $user, Request $request)
    {
        try {
            if ($request->money && $request->money < 0) {
                return redirect()->back()->with([
                    'success' => false,
                    'message' => 'จำนวนเงินน้อยเกินไป'
                ]);
            }

            $user->increment('pp', $request->money*1080);

            return response()->json([
                'success' => true,
                'message' => 'เพิ่มแต้มเส็จสมบูรณ์',
                'pp' => $user->pp,
            ], 200);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === 1) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบผู้ดูแลระบบได้'
            ]);
        }

        // $user->academies()->delete();

        // AcademyMember::where('user_id', $user->id)->delete();
        // Course::where('user_id', $user->id)->delete();
        // CourseGroup::where('user_id', $user->id)->delete();
        // CourseGroupMember::where('user_id', $user->id)->delete();
        // CourseMember::where('user_id', $user->id)->delete();
        // CourseQuiz::where('user_id', $user->id)->delete();
        // CourseQuizResult::where('user_id', $user->id)->delete();
        // Lesson::where('user_id', $user->id)->delete();

        // Question::where('user_id', $user->id);
        // $questions = Question::where('user_id', $user->id)->get();
        // foreach ($questions as $question) {
        //     $question->answers()->delete();
        // }


        // Post::where('user_id', $user->id)->delete();
        // PostComment::where('user_id', $user->id)->delete();
        // PostLike::where('user_id', $user->id)->delete();
        // PostDislike::where('user_id', $user->id)->delete();
        // PostCommentLike::where('user_id', $user->id)->delete();
        // PostCommentDislike::where('user_id', $user->id)->delete();


        // $user->delete();
        
        // return response()->json([
        //     'success' => true,
        //     'message' => 'User deleted successfully'
        // ]);

    }

}
