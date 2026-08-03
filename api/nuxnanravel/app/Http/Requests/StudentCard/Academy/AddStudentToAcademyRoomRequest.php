<?php

namespace App\Http\Requests\StudentCard\Academy;

use App\Http\Requests\StudentCard\AddStudentToRoomRequest;

/**
 * เหมือน AddStudentToRoomRequest ทุกอย่าง ยกเว้นการอนุญาต
 *
 * ตัวเดิมผูกกับ config('student-card.public_management') ซึ่งปกติปิดอยู่ ถ้านำมา
 * ใช้ซ้ำบนเส้นทางของโรงเรียนจะ 403 ตลอด — เส้นทางนี้ตรวจสิทธิ์จริงด้วย
 * StudentCardAccessService ใน controller แทน
 */
class AddStudentToAcademyRoomRequest extends AddStudentToRoomRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // confirm_transfer = ครูยืนยันแล้วว่าจะดึงนักเรียนออกจากห้องอื่นเข้ามาห้องนี้
        return parent::rules() + [
            'confirm_transfer' => ['nullable', 'boolean'],
        ];
    }
}
