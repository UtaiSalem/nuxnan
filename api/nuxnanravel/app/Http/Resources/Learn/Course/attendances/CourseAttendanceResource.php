<?php

namespace App\Http\Resources\Learn\Course\attendances;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAttendanceResource extends JsonResource
{
    /**
     * The authenticated user's course member ID, resolved once per request.
     */
    protected static ?array $authMemberCache = [];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'group_id' => $this->group_id,
            'instructor_id' => $this->instructor_id,
            'date' => $this->date,
            'start_at' => $this->start_at,
            'finish_at' => $this->finish_at,
            'late_time' => $this->late_time,
            'description' => $this->description,
            'details' => AttendanceDetailResource::collection($this->attendanceDetails),
            'authAtttendanceStatus' => $this->resolveAuthAttendanceStatus(),
        ];
    }

    /**
     * Resolve the authenticated user's attendance status for this session.
     *
     * Uses the hasMany `attendanceDetails` relation (more reliable than morphMany)
     * and resolves course_member_id via the course — avoids the original bug
     * where auth()->id() (user ID) was compared directly to course_member_id.
     */
    protected function resolveAuthAttendanceStatus()
    {
        $memberId = $this->getAuthMemberId();
        if (! $memberId) {
            return null;
        }

        return $this->attendanceDetails
            ->firstWhere('course_member_id', $memberId);
    }

    /**
     * Get the course_member_id for the authenticated user by caching
     * the lookup per course to avoid N+1 queries across a collection.
     */
    protected function getAuthMemberId(): ?int
    {
        $courseId = $this->course_id;
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        // Cache keyed by course_id so all attendances of the same course share one lookup
        if (! isset(static::$authMemberCache[$courseId])) {
            static::$authMemberCache[$courseId] = optional(
                $this->course?->courseMembers()
                    ->where('user_id', $userId)
                    ->first()
            )->id;
        }

        return static::$authMemberCache[$courseId];
    }
}
