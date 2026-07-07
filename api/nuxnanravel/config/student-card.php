<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Student Card Management (Temporary)
    |--------------------------------------------------------------------------
    |
    | ระบบจัดการห้องเรียนชั่วคราวบนหน้า /student-card/{level}/{room}
    | เปิดให้ครูภายในโรงเรียนใช้งานโดยไม่ต้องล็อกอิน
    | เมื่อโรงเรียนตั้งค่าครูประจำชั้นแล้ว ให้ปิด flag นี้และย้ายไปใช้
    | EnrollmentPolicy ผ่าน academy-scoped routes แทน
    |
    */

    'public_management' => env('PUBLIC_STUDENT_CARD_MANAGEMENT', false),

];
