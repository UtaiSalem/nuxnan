<?php

namespace App\Http\Controllers\Api\Shared;

use App\Models\User;

use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\AcademyMember;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends \App\Http\Controllers\Controller
{
    public function index(){

        if (auth()->id() !== 1) {
            return redirect('/dashboard');
        }
        return response()->json(['success' => true]);
    }

    public function getUser(Request $request){
        $email = $request->email;

        $users = UserResource::collection(User::where('email', 'like', '%'. $email . '%')
        ->orWhere('name', 'like', '%'. $email . '%')
        ->limit(10)->get());

        return response()->json([
            'users' => $users,
            'success' => true,
        ], 200);

    }

    public function resetPassword(User $user)
    {
        if ($user->pp < 4800) {
            return redirect()->back()->with([
                'success' => false,
                'message' => 'แต้มสะสมไม่เพียงพอ'
            ]);
        }

        $user->update([
            'password' => Hash::make('0000'),
            'pp' => $user->pp - 4800,
        ]);

        DB::table('password_reset_tokens')->where(['email'=> $user->email])->delete();

        return response()->json([
            'success' => true,
            'message' => 'รีเซ็ตรหัสผ่านแล้ว'
        ], 200);

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
