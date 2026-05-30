<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\AcademyMember;
use App\Models\ClassroomStudent;
use App\Models\AcademicYear;
use App\Models\Student;

class LearnerIdentityService
{
    /**
     * Resolve effective identity for a user in a course
     * 
     * Fallback Chain:
     * - Name: course_member.member_name -> user.name
     * - Code: course_member.member_code -> academy_member.member_code
     * - Order: course_member.order_number -> classroom_student.student_number
     * 
     * @param User $user
     * @param Course $course
     * @return array
     */
    public function resolve(User $user, Course $course): array
    {
        $member = CourseMember::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->first();

        $identity = [
            'member_name' => $member?->member_name,
            'member_code' => $member?->member_code,
            'order_number' => $member?->order_number,
            'source' => 'user',
        ];

        // 1. Resolve Name fallback
        if (empty($identity['member_name'])) {
            $identity['member_name'] = $user->name;
        } else {
            $identity['source'] = 'course_override';
        }

        // 2. Resolve Academy-level data (member_code)
        if ($course->academy_id && empty($identity['member_code'])) {
            $academyMember = AcademyMember::where('academy_id', $course->academy_id)
                ->where('user_id', $user->id)
                ->first();
            
            if ($academyMember && !empty($academyMember->member_code)) {
                $identity['member_code'] = $academyMember->member_code;
                if ($identity['source'] === 'user') {
                    $identity['source'] = 'academy';
                }
            }
        } elseif (!empty($identity['member_code']) && $identity['source'] === 'user') {
             $identity['source'] = 'course_override';
        }

        // 3. Resolve Classroom-level data (order_number)
        if ($course->academy_id && empty($identity['order_number'])) {
            $currentYear = AcademicYear::where('academy_id', $course->academy_id)
                ->where('is_current', true)
                ->first();

            if ($currentYear) {
                $student = Student::where('user_id', $user->id)
                    ->where('academy_id', $course->academy_id)
                    ->first();

                if ($student) {
                    $classroomStudent = ClassroomStudent::where('student_id', $student->id)
                        ->where('academic_year_id', $currentYear->id)
                        ->first();

                    if ($classroomStudent && !empty($classroomStudent->student_number)) {
                        $identity['order_number'] = $classroomStudent->student_number;
                        if ($identity['source'] === 'user' || $identity['source'] === 'academy') {
                            $identity['source'] = 'classroom';
                        }
                    }
                }
            }
        } elseif (!empty($identity['order_number']) && $identity['source'] !== 'course_override') {
            $identity['source'] = 'course_override';
        }

        return $identity;
    }

    /**
     * Auto-populate course member identity fields if they are empty
     * 
     * @param CourseMember $member
     * @return void
     */
    public function autoPopulate(CourseMember $member): void
    {
        $identity = $this->resolve($member->user, $member->course);

        if (empty($member->member_name)) {
            $member->member_name = $identity['member_name'];
        }
        if (empty($member->member_code)) {
            $member->member_code = $identity['member_code'];
        }
        if (empty($member->order_number)) {
            $member->order_number = $identity['order_number'];
        }
    }
}
