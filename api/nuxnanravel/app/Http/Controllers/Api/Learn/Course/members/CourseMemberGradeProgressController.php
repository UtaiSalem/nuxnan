<?php

namespace App\Http\Controllers\Api\Learn\Course\members;

use App\Models\Course;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use App\Models\AssignmentAnswer;
use App\Models\CourseQuizResult;
use App\Models\UserAnswerQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\Learn\Course\assignments\AssignmentResource;
use App\Http\Resources\CourseQuizResource;
use App\Http\Resources\CourseGroupResource;
use App\Http\Resources\CourseMemberResource;
use App\Http\Resources\AssignmentAnswerResource;
use App\Http\Resources\CourseQuizResultResource;

class CourseMemberGradeProgressController extends Controller
{
    /**
     * ตรวจสอบความถูกต้องของคะแนน
     * @param int $achievedScore คะแนนที่ทำได้
     * @param int $totalScore คะแนนรวมสูงสุด
     * @param int $memberId รหัสสมาชิก
     * @param int $courseId รหัสคอร์ส
     * @return array ผลการตรวจสอบ
     */
    private function validateScore($achievedScore, $totalScore, $memberId, $courseId)
    {
        $validation = [
            'is_valid' => true,
            'error_message' => '',
            'has_error' => false,
            'severity' => 'none', // 'none', 'warning', 'error', 'critical'
            'percentage' => 0
        ];

        // ตรวจสอบค่าว่าง
        if ($achievedScore === null || $totalScore === null || $totalScore == 0) {
            return $validation;
        }

        $percentage = round(($achievedScore / $totalScore) * 100, 2);
        $validation['percentage'] = $percentage;

        // ตรวจสอบค่าติดลบ
        if ($achievedScore < 0 || $totalScore < 0) {
            $validation['is_valid'] = false;
            $validation['has_error'] = true;
            $validation['severity'] = 'critical';
            $validation['error_message'] = "พบค่าคะแนนติดลบ: คะแนนที่ทำได้ ({$achievedScore}), คะแนนรวม ({$totalScore})";
            
            Log::error('Score Validation Error (Negative Values)', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'achieved_score' => $achievedScore,
                'total_score' => $totalScore,
                'timestamp' => now()
            ]);

            return $validation;
        }

        // ตรวจสอบว่าคะแนนที่ทำได้ไม่เกินคะแนนรวม
        if ($achievedScore > $totalScore) {
            $validation['is_valid'] = false;
            $validation['has_error'] = true;
            $validation['severity'] = $achievedScore > ($totalScore * 1.5) ? 'critical' : 'error';
            $validation['error_message'] = "คะแนนที่บันทึก ({$achievedScore}) สูงกว่าคะแนนรวมสูงสุด ({$totalScore}) คิดเป็น {$percentage}%";
            
            Log::error('Score Validation Error (Score Exceeds Total)', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'achieved_score' => $achievedScore,
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'exceed_by' => $achievedScore - $totalScore,
                'severity' => $validation['severity'],
                'timestamp' => now()
            ]);

            return $validation;
        }

        return $validation;
    }

    public function processGrades($courseId, $memberId)
    {
        DB::beginTransaction();

        try {
            // ดึง user_id จากตาราง course_members โดยใช้ member_id
            $courseMember = CourseMember::where('course_id', $courseId)
                ->where('id', $memberId)
                ->first();
            
            if (!$courseMember) {
                return response()->json(['error' => 'Course member not found'], 404);
            }
            
            $userId = $courseMember->user_id;
            
            // 1. หาคำตอบล่าสุดสำหรับแต่ละคำถามในแต่ละ quiz
            $latestAnswers = DB::table('user_answer_questions as uaq')
                ->select('quiz_id', 'question_id',
                    DB::raw('MAX(id) as latest_id'),
                    DB::raw('MAX(CASE WHEN answer_id = correct_option_id THEN 1 ELSE 0 END) as is_correct'))
                ->where('course_id', $courseId)
                ->where('user_id', $userId)
                ->groupBy(['quiz_id', 'question_id'])
                ->get();

            // 2. ลบคำตอบเก่าทั้งหมดที่ไม่ใช่คำตอบล่าสุด
            $idsToKeep = $latestAnswers->pluck('latest_id')->toArray();
            if (!empty($idsToKeep)) {
                DB::table('user_answer_questions')
                    ->where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->whereNotIn('id', $idsToKeep)
                    ->delete();
            }

            // 3. คำนวณคะแนนรวมสำหรับแต่ละ quiz
            $quizResults = DB::table('user_answer_questions as uaq')
                ->select('quiz_id',
                    DB::raw('SUM(CASE WHEN answer_id = correct_option_id THEN 1 ELSE 0 END) as score'))
                ->where('course_id', $courseId)
                ->where('user_id', $userId)
                ->whereIn('id', $idsToKeep)
                ->groupBy('quiz_id')
                ->get();

            // 4. อัปเดตคะแนนในตาราง course_quiz_results แบบ batch
            $totalScore = 0;
            $updateData = [];
            
            foreach ($quizResults as $result) {
                $updateData[] = [
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'quiz_id' => $result->quiz_id,
                    'score' => $result->score,
                    'updated_at' => now()
                ];
                $totalScore += $result->score;
            }

            if (!empty($updateData)) {
                DB::table('course_quiz_results')->upsert(
                    $updateData,
                    ['course_id', 'user_id', 'quiz_id'],
                    ['score', 'updated_at']
                );
            }

            // 5. ดึงคะแนนรวมสูงสุดของคอร์ส
            $course = Course::findOrFail($courseId);
            $courseTotalScore = $course->total_score ?? 0;

            // 6. ตรวจสอบความถูกต้องของคะแนน
            $scoreValidation = $this->validateScore($totalScore, $courseTotalScore, $memberId, $courseId);

            // 7. อัปเดตคะแนนรวมในตาราง course_members
            CourseMember::where('course_id', $courseId)
                ->where('id', $memberId)
                ->update(['achieved_score' => $totalScore]);

            DB::commit();

            // 8. ส่งข้อมูลกลับไปยัง Frontend พร้อมผลการตรวจสอบ
            $response = [
                'success' => true,
                'message' => 'Grades processed successfully',
                'total_score' => $totalScore,
                'course_total_score' => $courseTotalScore,
                'quizzes_processed' => count($updateData),
                'validation' => $scoreValidation
            ];

            // หากมีข้อผิดพลาด เพิ่ม warning flag
            if ($scoreValidation['has_error']) {
                $response['warning'] = true;
                $response['message'] = 'Grades processed with validation warnings';
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function memberGradeProgress(Course $course, CourseMember $member)
    {
        $courseAssignments = $course->courseAssignments;

        $user_assignments_answers = AssignmentAnswer::with([
                                        'user',
                                        'assignment.assignmentable'
                                    ])
                                    ->whereIn('assignment_id', $courseAssignments->pluck('id'))
                                    ->where('user_id', $member->user_id)
                                    ->get();

        $user_quizes_results = CourseQuizResult::with('user')
                ->where('course_id', $course->id)
                ->where('user_id', $member->user_id)
                ->get();

        return response()->json([
            'isCourseAdmin'             => $course->isAdmin(auth()->user()),
            'course'                    => new CourseResource($course),
            // 'lessons'                   => LessonResource::collection($course->courseLessons),
            // 'groups'                    => CourseGroupResource::collection($course->courseGroups),
            'course_assignments'        => $course->courseAssignments,
            'course_quizzes'            => $course->courseQuizzes,
            'member'                    => new CourseMemberResource($member),
            'member_assignments_answers'   => AssignmentAnswerResource::collection($user_assignments_answers), 
            'member_quizes_results'     => CourseQuizResultResource::collection($user_quizes_results)
        ]);
    }
    public function updateEditedGrade(Course $course, CourseMember $member, Request $request)
    {
        $request->validate([
            'edited_grade' => 'nullable|numeric|between:0,4',
        ]);

        $member->update([
            'edited_grade' => $request->edited_grade
        ]);

        return response()->json([
            'success' => true,
            'edited_grade' => $member->edited_grade,
            'grade_name' => $member->getGradeName(), // This should ideally reflect the edited grade if we update logic
        ]);
    }
}
