<?php

namespace App\Http\Resources\Learn\Academy;

use App\Http\Resources\UserResource;
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
            'name_slug' => $this->name_slug,
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
        ];

        if (! $canViewContent) {
            return $base;
        }

        return array_merge($base, [
            'creater' => new UserResource($this->user),
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'province' => $this->province,
            'country' => $this->country,
            'director' => new UserResource(User::find($this->director)),
            'established_year' => $this->established_year,
            'type' => $this->type,
            'accreditation' => $this->accreditation,
            'accreditation_body' => $this->accreditation_body,
            'facilities' => $this->facilities,
            'academy_timings' => $this->academy_timings,
            'holidays' => $this->holidays,
            'social_media_links' => $this->social_media_links,
            'setting' => $settings,
        ]);
    }
}
