<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\MeetingBooking;
use App\Models\MeetingSlot;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * List meeting slots for a teacher
     */
    public function slots(Request $request, Academy $academy)
    {
        $query = MeetingSlot::where('academy_id', $academy->id)
            ->with(['teacher:id,name,profile_photo_path']);

        // Filter by teacher
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->where('meeting_date', $request->date);
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('meeting_date', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('meeting_date', '<=', $request->to);
        }

        // Only future slots by default
        if (! $request->has('include_past')) {
            $query->where('meeting_date', '>=', now()->format('Y-m-d'));
        }

        // Filter by availability
        if ($request->boolean('available_only')) {
            $query->where('is_active', true)
                ->whereColumn('booked_count', '<', 'max_bookings');
        }

        $slots = $query->orderBy('meeting_date')
            ->orderBy('start_time')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $slots->items(),
            'meta' => [
                'current_page' => $slots->currentPage(),
                'total' => $slots->total(),
                'per_page' => $slots->perPage(),
                'last_page' => $slots->lastPage(),
            ],
        ]);
    }

    /**
     * Get available time slots for a teacher on a date
     */
    public function available(Request $request, Academy $academy)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $slots = MeetingSlot::where('academy_id', $academy->id)
            ->where('teacher_id', $request->teacher_id)
            ->where('meeting_date', $request->date)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $availableSlots = $slots->filter(function ($slot) {
            return $slot->booked_count < $slot->max_bookings;
        })->map(function ($slot) {
            return [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'duration' => $slot->slot_duration,
                'meeting_type' => $slot->meeting_type,
                'location' => $slot->location,
                'available_spots' => $slot->max_bookings - $slot->booked_count,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $availableSlots,
        ]);
    }

    /**
     * Create meeting slots (teacher)
     */
    public function createSlots(Request $request, Academy $academy)
    {
        $user = $request->user();

        // Check if user is a teacher in this academy
        if (! $this->isTeacher($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'meeting_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'integer|min:10|max:120',
            'max_bookings' => 'integer|min:1',
            'meeting_type' => 'in:in_person,video_call',
            'location' => 'nullable|string|max:255',
            'online_url' => 'nullable|url',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $slot = MeetingSlot::create([
                'academy_id' => $academy->id,
                'teacher_id' => $user->id,
                'meeting_date' => $request->meeting_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'slot_duration' => $request->get('slot_duration', 15),
                'max_bookings' => $request->get('max_bookings', 1),
                'meeting_type' => $request->get('meeting_type', 'in_person'),
                'location' => $request->location,
                'online_url' => $request->online_url,
                'notes' => $request->notes,
                'is_active' => true,
            ]);

            $this->auditLog->log(
                'meeting_slot.created',
                $slot,
                null,
                $slot->toArray(),
                'meetings',
                ['academy_id' => $academy->id]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meeting slot created',
                'data' => $slot,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create slot: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create multiple slots (bulk)
     */
    public function createBulkSlots(Request $request, Academy $academy)
    {
        $user = $request->user();

        if (! $this->isTeacher($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'dates' => 'required|array|min:1',
            'dates.*' => 'date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'integer|min:10|max:120',
            'max_bookings' => 'integer|min:1',
            'meeting_type' => 'in:in_person,video_call',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $slots = [];
            foreach ($request->dates as $date) {
                $slot = MeetingSlot::create([
                    'academy_id' => $academy->id,
                    'teacher_id' => $user->id,
                    'meeting_date' => $date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'slot_duration' => $request->get('slot_duration', 15),
                    'max_bookings' => $request->get('max_bookings', 1),
                    'meeting_type' => $request->get('meeting_type', 'in_person'),
                    'location' => $request->location,
                    'is_active' => true,
                ]);
                $slots[] = $slot;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($slots).' meeting slots created',
                'data' => $slots,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create slots: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a meeting slot
     */
    public function updateSlot(Request $request, Academy $academy, MeetingSlot $slot)
    {
        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($slot->teacher_id !== $user->id && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i',
            'max_bookings' => 'integer|min:1',
            'location' => 'nullable|string|max:255',
            'online_url' => 'nullable|url',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldData = $slot->toArray();
        $slot->update($request->only([
            'start_time', 'end_time', 'max_bookings', 'location',
            'online_url', 'notes', 'is_active',
        ]));

        $this->auditLog->log(
            'meeting_slot.updated',
            $slot,
            $oldData,
            $slot->fresh()->toArray(),
            'meetings',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot updated',
            'data' => $slot->fresh(),
        ]);
    }

    /**
     * Delete a meeting slot
     */
    public function deleteSlot(Request $request, Academy $academy, MeetingSlot $slot)
    {
        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($slot->teacher_id !== $user->id && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if there are bookings
        if ($slot->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete slot with active bookings',
            ], 400);
        }

        $oldData = $slot->toArray();
        $slot->delete();

        $this->auditLog->log(
            'meeting_slot.deleted',
            $slot,
            $oldData,
            null,
            'meetings',
            ['academy_id' => $academy->id]
        );

        return response()->json([
            'success' => true,
            'message' => 'Slot deleted',
        ]);
    }

    /**
     * Book a meeting slot (parent)
     */
    public function book(Request $request, Academy $academy, MeetingSlot $slot)
    {
        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        // Check if slot is available
        if (! $slot->is_active || $slot->booked_count >= $slot->max_bookings) {
            return response()->json([
                'success' => false,
                'message' => 'This slot is no longer available',
            ], 400);
        }

        // Check if user already has a booking for this slot
        $existing = MeetingBooking::where('slot_id', $slot->id)
            ->where('parent_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a booking for this slot',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'purpose' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
            'preferred_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $booking = MeetingBooking::create([
                'slot_id' => $slot->id,
                'parent_id' => $user->id,
                'student_id' => $request->student_id,
                'purpose' => $request->purpose,
                'notes' => $request->notes,
                'preferred_time' => $request->preferred_time,
                'status' => 'pending',
            ]);

            // Increment booked count
            $slot->increment('booked_count');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meeting booked successfully. Waiting for confirmation.',
                'data' => $booking->load(['slot.teacher:id,name', 'student:id,name']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to book meeting: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * List bookings for a slot (teacher)
     */
    public function bookings(Request $request, Academy $academy, MeetingSlot $slot)
    {
        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($slot->teacher_id !== $user->id && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $bookings = $slot->bookings()
            ->with([
                'parent:id,name,email,profile_photo_path',
                'student:id,name,profile_photo_path',
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Get teacher's bookings
     */
    public function teacherBookings(Request $request, Academy $academy)
    {
        $user = $request->user();

        if (! $this->isTeacher($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = MeetingBooking::whereHas('slot', function ($q) use ($user, $academy) {
            $q->where('academy_id', $academy->id)
                ->where('teacher_id', $user->id);
        })->with([
            'slot:id,meeting_date,start_time,end_time,location',
            'parent:id,name,email,profile_photo_path',
            'student:id,name,profile_photo_path',
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereHas('slot', function ($q) use ($request) {
                $q->where('meeting_date', $request->date);
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Get parent's bookings
     */
    public function myBookings(Request $request, Academy $academy)
    {
        $user = $request->user();

        $query = MeetingBooking::where('parent_id', $user->id)
            ->whereHas('slot', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })
            ->with([
                'slot:id,teacher_id,meeting_date,start_time,end_time,location,meeting_type',
                'slot.teacher:id,name,profile_photo_path',
                'student:id,name,profile_photo_path',
            ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Only upcoming by default
        if (! $request->has('include_past')) {
            $query->whereHas('slot', function ($q) {
                $q->where('meeting_date', '>=', now()->format('Y-m-d'));
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    /**
     * Confirm a booking (teacher)
     */
    public function confirmBooking(Request $request, Academy $academy, MeetingBooking $booking)
    {
        $slot = $booking->slot;

        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($slot->teacher_id !== $user->id && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'confirmed_time' => 'nullable|date_format:H:i',
            'teacher_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking->confirm(
            $request->confirmed_time,
            $request->teacher_notes
        );

        // TODO: Send notification to parent

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed',
            'data' => $booking->load(['slot.teacher:id,name', 'student:id,name']),
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Request $request, Academy $academy, MeetingBooking $booking)
    {
        $slot = $booking->slot;

        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        // Allow teacher, parent, or admin to cancel
        if ($slot->teacher_id !== $user->id &&
            $booking->parent_id !== $user->id &&
            ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $booking->cancel();

            // Decrement booked count
            $slot->decrement('booked_count');

            DB::commit();

            // TODO: Send notification to affected party

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark booking as completed (teacher)
     */
    public function completeBooking(Request $request, Academy $academy, MeetingBooking $booking)
    {
        $slot = $booking->slot;

        if ($slot->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($slot->teacher_id !== $user->id && ! $this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'meeting_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking->complete($request->meeting_notes);

        return response()->json([
            'success' => true,
            'message' => 'Meeting completed',
            'data' => $booking,
        ]);
    }

    /**
     * Check if user is academy admin
     */
    private function isAcademyAdmin($user, Academy $academy): bool
    {
        // Canonical check: owner (user_id/owner_id), academy_admins, or super admin.
        // The academy_members pivot `role` is not the source of truth for admin rights.
        return $academy->isAdmin($user);
    }

    /**
     * Check if user is a teacher
     */
    private function isTeacher($user, Academy $academy): bool
    {
        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator', 'teacher'])
            ->exists();
    }
}
