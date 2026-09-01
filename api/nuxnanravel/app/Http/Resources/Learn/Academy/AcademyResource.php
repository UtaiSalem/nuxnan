<?php

namespace App\Http\Resources\Learn\Academy;

use App\Http\Resources\UserResource;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * โรงเรียนที่ตั้ง privacy=private จะคืนเฉพาะ "ข้อมูลหน้าปก" ให้คนนอก
     * (ชื่อ/โลโก้/ปก/คำอธิบาย/ปุ่มเข้าร่วม) — ข้อมูลติดต่อและรายละเอียดอื่นถูกตัดออก
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = auth()->user();
        $settings = $this->getSettings();

        $canViewContent = $this->resource->canViewContent($viewer);

        $base = [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'slogan' => $this->slogan,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'logo' => $this->logo,
            'logo_url' => $this->logo_url,
            'cover' => $this->cover,
            'cover_url' => $this->cover_url,
            'total_students' => $this->total_students,
            'total_teachers' => $this->total_teachers,
            'courses_offered' => $this->courses_offered,

            // นโยบายที่ frontend ใช้ตัดสินใจว่าจะโชว์อะไร
            'privacy' => $settings?->privacy ?? 'public',
            'join_mode' => $this->resource->joinMode(),
            'show_member_list' => $settings ? (bool) $settings->show_member_list : true,
            'show_course_list' => $settings ? (bool) $settings->show_course_list : true,
            'can_view_content' => $canViewContent,
            'can_view_member_list' => $this->resource->canViewMemberList($viewer),
            'can_view_course_list' => $this->resource->canViewCourseList($viewer),
            'is_restricted' => ! $canViewContent,

            'memberStatus' => $this->memberStatus ?? $this->member_status($this->id),
            'authIsAcademyAdmin' => auth()->id() === $this->user_id,

            // SET-S2 — ต้องอยู่ใน $base เพราะโรงเรียนที่ถูกเก็บถาวรคืนแค่ $base ให้คนนอก
            'is_archived' => $this->resource->isArchived(),
            'archived_at' => $this->archived_at,
        ];

        if (! $canViewContent) {
            return $base;
        }

        // SET-S7 / G22 — คอลัมน์ director เป็น varchar ที่เก็บ user id
        // ถ้าค่าไม่ใช่ตัวเลข หรือหา user ไม่เจอ ต้องคืน null เฉย ๆ
        // ก่อนหน้านี้ new UserResource(null) ทำให้ทั้ง endpoint ตอบ 500 (TypeError ใน method_exists)
        $director = is_numeric($this->director) ? User::find((int) $this->director) : null;

        return array_merge($base, [
            'creater' => new UserResource($this->user),
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'province' => $this->province,
            'country' => $this->country,
            'director' => $director ? new UserResource($director) : null,
            'established_year' => $this->established_year,
            'type' => $this->type,
            'accreditation' => $this->accreditation,
            'accreditation_body' => $this->accreditation_body,
            'facilities' => $this->facilities,
            'academy_timings' => $this->academy_timings,
            'holidays' => $this->holidays,
            'social_media_links' => $this->social_media_links,

            // SET-S6 — ส่ง "ค่าที่ resolve แล้ว" ไม่ใช่คอลัมน์ดิบ
            // ก่อนหน้านี้ resource ไม่ส่งคีย์นี้เลย frontend เลยเขียน `!== false` แล้วได้ true เสมอ
            // ⇒ แผงบริจาคโชว์ตลอดทั้งที่ API บังคับใช้ค่าจริง (บั๊กแบบเดียวกับ 403 หน้า gradebook)
            'donation_enabled' => $this->resource->donationEnabled(),
            'student_editable_fields' => $this->student_editable_fields,
            'student_editable_field_catalog' => Academy::STUDENT_EDITABLE_FIELD_CATALOG,
            'academy_type_catalog' => Academy::ACADEMY_TYPE_CATALOG,
            'social_link_catalog' => Academy::SOCIAL_LINK_CATALOG,

            'setting' => $settings,
        ]);
    }
}
