<?php

namespace App\Http\Controllers\Api\Learn\Student\HomeVisit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentCard;
use App\Models\StudentHomeVisit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class HomeVisitAuthController extends Controller
{
    /**
     * Show the main home visit system login page
     */
    public function index()
    {
        return response()->json(['success' => true]);
    }

    /**
     * Authenticate student using National ID and Student ID
     */
    public function studentLogin(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string',
            'student_id' => 'required|string',
        ]);

        // Find student by both National ID and Student ID
        $student = StudentCard::where('student_number', $request->student_id)
            ->where('national_id', $request->national_id)
            ->first();

        if (!$student) {
            throw ValidationException::withMessages([
                'credentials' => 'ไม่พบข้อมูลนักเรียนที่ตรงกับหมายเลขบัตรประชาชนและรหัสนักเรียนที่ระบุ'
            ]);
        }

        // Store student session
        session([
            'homevisit_user_type' => 'student',
            'homevisit_student_id' => $student->id,
            'homevisit_authenticated' => true
        ]);

        return redirect()->route('homevisit.student.profile');
    }

    /**
     * Authenticate teacher using username and password
     */
    public function teacherLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check teacher credentials
        if ($request->username !== 'teacher' || $request->password !== 'jsm1234') {
            throw ValidationException::withMessages([
                'credentials' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
            ]);
        }

        // Store teacher session
        session([
            'homevisit_user_type' => 'teacher',
            'homevisit_authenticated' => true
        ]);

        return redirect()->route('homevisit.teacher.dashboard');
    }

    /**
     * Authenticate admin using username and password
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Admin authentication - username: admin, password: jsm_admin2025
        if ($request->username === 'admin' && $request->password === 'jsm_admin2025') {
            // Store admin session
            session([
                'homevisit_user_type' => 'admin',
                'homevisit_admin_username' => $request->username,
                'homevisit_admin_authenticated' => true
            ]);

            return redirect()->route('homevisit.admin.dashboard');
        }

        throw ValidationException::withMessages([
            'credentials' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
        ]);
    }

    /**
     * Logout from home visit system
     */
    public function logout()
    {
        session()->forget(['homevisit_user_type', 'homevisit_student_id', 'homevisit_authenticated', 'homevisit_admin_authenticated']);
        return redirect()->route('homevisit.login');
    }

    /**
     * Check if user is authenticated for home visit system
     */
    public function checkAuth(Request $request)
    {
        $isAuthenticated = session('homevisit_authenticated', false);
        $userType = session('homevisit_user_type');
        
        return response()->json([
            'authenticated' => $isAuthenticated,
            'user_type' => $userType
        ]);
    }
}
