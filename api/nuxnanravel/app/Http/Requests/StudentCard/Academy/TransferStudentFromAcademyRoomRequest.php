<?php

namespace App\Http\Requests\StudentCard\Academy;

use App\Http\Requests\StudentCard\TransferStudentFromRoomRequest;

/**
 * ดู AddStudentToAcademyRoomRequest สำหรับเหตุผลที่ต้องมีคลาสนี้
 */
class TransferStudentFromAcademyRoomRequest extends TransferStudentFromRoomRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
