<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\ActivityEnrollment;
use App\Models\ActivitySession;
use App\Models\EventRegistration;
use App\Models\SchoolEvent;
use App\Services\AuditLogService;
use App\Traits\ManagesEventPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SchoolEventController extends Controller
{
    use ManagesEventPermissions;

    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * List events for an academy
     */
    public function index(Request $request, Academy $academy)
    {
        $user = $request->user();

        $query = SchoolEvent::where('academy_id', $academy->id)
            ->with(['author:id,name,profile_photo_path']);

        // Academy admins see every event. Group admins additionally see drafts of their own group.
        // Everyone else only sees published events.
        if (! $this->isAcademyAdmin($user, $academy)) {
            $managedGroupIds = \App\Models\AcademyGroupAdmin::where('user_id', $user->id)
                ->whereIn('academy_group_id', \App\Models\AcademyGroup::where('academy_id', $academy->id)->pluck('id'))
                ->pluck('academy_group_id');

            $query->where(function ($q) use ($managedGroupIds) {
                $q->where('status', 'published');
                if ($managedGroupIds->isNotEmpty()) {
                    $q->orWhereIn('group_id', $managedGroupIds);
                }
            });
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('event_type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('start_datetime', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('start_datetime', '<=', $request->to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Calendar view - get by month
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('start_datetime', $request->month)
                ->whereYear('start_datetime', $request->year);
        }

        $events = $query->orderBy('start_datetime')
            ->paginate($request->get('per_page', 15));

        // Add registration status for current user
        $registeredIds = EventRegistration::where('user_id', $user->id)
            ->whereIn('event_id', $events->pluck('id'))
            ->pluck('status', 'event_id')
            ->toArray();

        $events->getCollection()->transform(function ($event) use ($registeredIds) {
            $event->registration_status = $registeredIds[$event->id] ?? null;
            $event->registered_count = $event->registrations()->whereIn('status', ['pending', 'confirmed', 'attended'])->count();

            return $event;
        });

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Get upcoming events for calendar widget
     */
    public function upcoming(Request $request, Academy $academy)
    {
        $limit = $request->get('limit', 5);

        $events = SchoolEvent::where('academy_id', $academy->id)
            ->where('status', 'published')
            ->where('start_datetime', '>', now())
            ->orderBy('start_datetime')
            ->take($limit)
            ->get(['id', 'title', 'event_type', 'start_datetime', 'end_datetime', 'location']);

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Get a single event
     */
    public function show(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        // Check access for non-published events
        if ($event->status !== 'published' && ! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $event->load('author:id,name,profile_photo_path');

        // Get user's registration if exists
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $event->user_registration = $registration;
        $event->registered_count = $event->registrations()
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->count();
        $event->spots_remaining = $event->max_participants
            ? max(0, $event->max_participants - $event->registered_count)
            : null;

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Create a new event
     */
    public function store(Request $request, Academy $academy)
    {
        $user = $request->user();

        // Group admins (e.g. หัวหน้าหอพัก/ชมรม) may create events scoped to their own group;
        // school-wide events (no group_id) require academy admin.
        if (! $this->canManageEvent($user, $academy, $request->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'in:meeting,training,holiday,orientation,exam,academic_competition,student_development,club,activity,sports,ceremony,field_trip,volunteer,other',
            'attendance_pattern' => 'in:one_time,semester,recurring',
            'group_id' => 'nullable|exists:academy_groups,id',
            'category' => 'nullable|string|max:100',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'location_type' => 'in:onsite,online,hybrid',
            'meeting_url' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean',
            'registration_deadline' => 'nullable|date|before:start_datetime',
            'is_all_day' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|array',
            'status' => 'in:draft,published',
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
            $eventType = $request->get('event_type', 'activity');

            $event = SchoolEvent::create([
                'academy_id' => $academy->id,
                'group_id' => $request->group_id,
                'created_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'event_type' => $eventType,
                'attendance_pattern' => $request->get(
                    'attendance_pattern',
                    SchoolEvent::DEFAULT_PATTERN_BY_TYPE[$eventType] ?? SchoolEvent::PATTERN_ONE_TIME
                ),
                'category' => $request->category,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'location' => $request->location,
                'location_type' => $request->get('location_type', 'onsite'),
                'meeting_url' => $request->meeting_url,
                'max_participants' => $request->max_participants,
                'requires_registration' => $request->get('requires_registration', false),
                'registration_deadline' => $request->registration_deadline,
                'is_all_day' => $request->get('is_all_day', false),
                'is_recurring' => $request->get('is_recurring', false),
                'recurrence_pattern' => $request->recurrence_pattern,
                'status' => $request->get('status', 'draft'),
                'published_at' => $request->get('status') === 'published' ? now() : null,
            ]);

            $this->auditLog->log(
                'event.created',
                'school_event',
                $event->id,
                null,
                $event->toArray(),
                $academy->id
            );

            DB::commit();

            // Mirror if published
            if ($event->status === 'published') {
                app(\App\Services\EventToPostMirror::class)->mirror($event);
            }

            // Generate sessions if recurring
            if ($event->is_recurring && ! empty($event->recurrence_pattern)) {
                $this->generateSessions($event);
            }

            $event->load('author:id,name,profile_photo_path');

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create event: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an event
     */
    public function update(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Re-assigning to a different group also requires managing that group
        if ($request->has('group_id') && $request->group_id != $event->group_id
            && ! $this->canManageEvent($user, $academy, $request->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'in:meeting,training,holiday,orientation,exam,academic_competition,student_development,club,activity,sports,ceremony,field_trip,volunteer,other',
            'attendance_pattern' => 'in:one_time,semester,recurring',
            'group_id' => 'nullable|exists:academy_groups,id',
            'category' => 'nullable|string|max:100',
            'start_datetime' => 'date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'location_type' => 'in:onsite,online,hybrid',
            'meeting_url' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'requires_registration' => 'boolean',
            'registration_deadline' => 'nullable|date',
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
            $oldData = $event->toArray();

            $event->update($request->only([
                'title', 'description', 'event_type', 'attendance_pattern', 'group_id', 'category',
                'start_datetime', 'end_datetime', 'location',
                'location_type', 'meeting_url', 'max_participants',
                'requires_registration', 'registration_deadline',
                'is_all_day', 'is_recurring', 'recurrence_pattern',
            ]));

            $this->auditLog->log(
                'event.updated',
                'school_event',
                $event->id,
                $oldData,
                $event->fresh()->toArray(),
                $academy->id
            );

            DB::commit();

            // Mirror if published, otherwise unmirror (if draft/cancelled)
            if ($event->status === 'published') {
                app(\App\Services\EventToPostMirror::class)->mirror($event);
            } else {
                app(\App\Services\EventToPostMirror::class)->unmirror($event);
            }

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event->fresh()->load('author:id,name,profile_photo_path'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update event: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish an event
     */
    public function publish(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $event->publish();

        $this->auditLog->log(
            'event.published',
            'school_event',
            $event->id,
            ['status' => 'draft'],
            ['status' => 'published'],
            $academy->id
        );

        // Mirror to post
        app(\App\Services\EventToPostMirror::class)->mirror($event);

        return response()->json([
            'success' => true,
            'message' => 'Event published successfully',
            'data' => $event,
        ]);
    }

    /**
     * Cancel an event
     */
    public function cancel(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldStatus = $event->status;
        $event->cancel();

        $this->auditLog->log(
            'event.cancelled',
            'school_event',
            $event->id,
            ['status' => $oldStatus],
            ['status' => 'cancelled'],
            $academy->id
        );

        // Unmirror event post
        app(\App\Services\EventToPostMirror::class)->unmirror($event);

        // TODO: Notify registered participants

        return response()->json([
            'success' => true,
            'message' => 'Event cancelled successfully',
            'data' => $event,
        ]);
    }

    /**
     * Register for an event
     */
    public function register(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        // Check if event accepts registrations
        if (! $event->registration_required) {
            return response()->json([
                'success' => false,
                'message' => 'This event does not require registration',
            ], 400);
        }

        // Check if registration deadline passed
        if ($event->registration_deadline && $event->registration_deadline < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration deadline has passed',
            ], 400);
        }

        // Check if event is full
        if ($event->max_participants) {
            $currentCount = $event->registrations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();
            if ($currentCount >= $event->max_participants) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event is full',
                ], 400);
            }
        }

        // Check if already registered
        $existing = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'cancelled') {
                // Re-register
                $existing->update([
                    'status' => 'pending',
                    'cancelled_at' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Re-registered for event successfully',
                    'data' => $existing->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Already registered for this event',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'guests' => 'nullable|integer|min:0|max:10',
            'guest_names' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'guests' => $request->get('guests', 0),
            'guest_names' => $request->guest_names,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registered for event successfully',
            'data' => $registration,
        ], 201);
    }

    /**
     * Cancel registration for an event
     */
    public function cancelRegistration(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $registration) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
            ], 404);
        }

        $registration->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Registration cancelled successfully',
        ]);
    }

    /**
     * Get event registrations (admin)
     */
    public function registrations(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $registrations = $event->registrations()
            ->with('user:id,name,email,profile_photo_path')
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $registrations->items(),
            'meta' => [
                'current_page' => $registrations->currentPage(),
                'total' => $registrations->total(),
                'per_page' => $registrations->perPage(),
                'last_page' => $registrations->lastPage(),
            ],
            'summary' => [
                'pending' => $event->registrations()->where('status', 'pending')->count(),
                'confirmed' => $event->registrations()->where('status', 'confirmed')->count(),
                'cancelled' => $event->registrations()->where('status', 'cancelled')->count(),
                'attended' => $event->registrations()->where('status', 'attended')->count(),
            ],
        ]);
    }

    /**
     * Confirm a registration (admin)
     */
    public function confirmRegistration(Request $request, Academy $academy, SchoolEvent $event, EventRegistration $registration)
    {
        if ($event->academy_id !== $academy->id || $registration->event_id !== $event->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $registration->confirm();

        return response()->json([
            'success' => true,
            'message' => 'Registration confirmed',
            'data' => $registration->load('user:id,name,email'),
        ]);
    }

    /**
     * Mark attendance for a registration (admin)
     */
    public function markAttendance(Request $request, Academy $academy, SchoolEvent $event, EventRegistration $registration)
    {
        if ($event->academy_id !== $academy->id || $registration->event_id !== $event->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $attended = $request->get('attended', true);

        if ($attended) {
            $registration->markAsAttended();
        } else {
            $registration->update(['status' => 'no_show']);
        }

        return response()->json([
            'success' => true,
            'message' => $attended ? 'Marked as attended' : 'Marked as no show',
            'data' => $registration,
        ]);
    }

    /**
     * Delete an event
     */
    public function destroy(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        if (! $this->canManageEvent($user, $academy, $event->group_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldData = $event->toArray();

        // Unmirror post before deleting event
        app(\App\Services\EventToPostMirror::class)->unmirror($event);

        $event->delete();

        $this->auditLog->log(
            'event.deleted',
            'school_event',
            $event->id,
            $oldData,
            null,
            $academy->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ]);
    }

    /**
     * Enroll in a semester-based activity
     */
    public function enroll(Request $request, Academy $academy, SchoolEvent $event)
    {
        if ($event->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $user = $request->user();

        // Check enrollment deadline/criteria
        // ...

        // Check if already enrolled
        $existing = ActivityEnrollment::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('semester', $request->get('semester', '1')) // Default or current
            ->where('academic_year', $request->get('academic_year', '2024'))
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Already enrolled'], 400);
        }

        $enrollment = ActivityEnrollment::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => 'active',
            'semester' => $request->get('semester', '1'),
            'academic_year' => $request->get('academic_year', '2024'),
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enrolled successfully',
            'data' => $enrollment,
        ], 201);
    }

    /**
     * Get my activity schedule
     */
    public function mySchedule(Request $request, Academy $academy)
    {
        $user = $request->user();
        $start = $request->get('start', now()->startOfWeek());
        $end = $request->get('end', now()->endOfWeek());

        // Get activities user is enrolled in
        $enrolledEventIds = ActivityEnrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('event_id');

        // Get sessions for these events within date range
        $sessions = ActivitySession::whereIn('event_id', $enrolledEventIds)
            ->whereBetween('start_datetime', [$start, $end])
            ->with('event:id,title,location')
            ->orderBy('start_datetime')
            ->get();

        // Also include attendance status if exists
        // ...

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Generate sessions based on recurrence pattern
     */
    private function generateSessions(SchoolEvent $event)
    {
        $pattern = $event->recurrence_pattern;
        $startDate = Carbon::parse($event->start_datetime);
        $endDate = Carbon::parse($event->end_datetime); // Or pattern end date

        // If end_datetime is same as start (one day event), maybe rely on pattern
        if ($startDate->isSameDay($endDate)) {
            // Check if pattern defines an extended end date
            if (isset($pattern['until'])) {
                $endDate = Carbon::parse($pattern['until']);
            } else {
                // Default to one month if not specified? Or just today + 1 month
                $endDate = $startDate->copy()->addMonths(4); // Default semester length?
            }
        }

        $frequency = $pattern['frequency'] ?? 'weekly'; // daily, weekly
        $daysOfWeek = $pattern['days'] ?? []; // 0=Sun, 1=Mon... or specific dates
        $timeSlots = $pattern['time_slots'] ?? null; // [{label, time}, ...] for multiple check-ins per day (e.g. ละหมาด 5 เวลา)
        $slotDurationMinutes = $pattern['slot_duration_minutes'] ?? 15;

        $sessions = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $shouldCreate = false;

            if ($frequency === 'daily') {
                $shouldCreate = true;
            } elseif ($frequency === 'weekly') {
                // Check if current day is in daysOfWeek
                // Carbon dayOfWeek: 0 (Sunday) - 6 (Saturday)
                if (empty($daysOfWeek) || in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                    $shouldCreate = true;
                }
            }

            if ($shouldCreate) {
                if (! empty($timeSlots)) {
                    // One session per time slot per day (e.g. ละหมาด 5 เวลา/วัน)
                    foreach ($timeSlots as $slot) {
                        $sessionStart = $currentDate->copy()->setTimeFromString($slot['time']);
                        $sessionEnd = $sessionStart->copy()->addMinutes($slotDurationMinutes);

                        $sessions[] = [
                            'event_id' => $event->id,
                            'title' => $event->title.' - '.$sessionStart->format('d M').' ('.($slot['label'] ?? $slot['time']).')',
                            'slot_label' => $slot['label'] ?? null,
                            'start_datetime' => $sessionStart,
                            'end_datetime' => $sessionEnd,
                            'status' => 'scheduled',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                } else {
                    // Single session per day — preserve time block from the event's first instance
                    $sessionStart = $currentDate->copy()->setTimeFromString($startDate->format('H:i'));

                    $originalStart = Carbon::parse($event->start_datetime);
                    $originalEnd = Carbon::parse($event->end_datetime);

                    if ($originalStart->isSameDay($originalEnd)) {
                        $durationMinutes = $originalStart->diffInMinutes($originalEnd);
                        $sessionEnd = $sessionStart->copy()->addMinutes($durationMinutes);
                    } else {
                        // Multi-day session? default 2 hours
                        $sessionEnd = $sessionStart->copy()->addHours(2);
                    }

                    $sessions[] = [
                        'event_id' => $event->id,
                        'title' => $event->title.' - '.$sessionStart->format('d M'),
                        'slot_label' => null,
                        'start_datetime' => $sessionStart,
                        'end_datetime' => $sessionEnd,
                        'status' => 'scheduled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $currentDate->addDay();
        }

        if (! empty($sessions)) {
            ActivitySession::insert($sessions);
        }
    }
}
