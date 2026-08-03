<?php

namespace App\Http\Requests\StudentCard\Academy;

use App\Http\Requests\StudentCard\RemoveStudentFromRoomRequest;

/**
 * ดู AddStudentToAcademyRoomRequest สำหรับเหตุผลที่ต้องมีคลาสนี้
 */
class RemoveStudentFromAcademyRoomRequest extends RemoveStudentFromRoomRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
