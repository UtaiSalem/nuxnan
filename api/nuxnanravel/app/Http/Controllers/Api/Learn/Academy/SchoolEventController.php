<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\SchoolEvent;
use App\Models\EventRegistration;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SchoolEventController extends Controller
{
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
            ->with(['creator:id,name,profile_photo_path']);

        // For non-admins, only show published events
        if (!$this->isAcademyAdmin($user, $academy)) {
            $query->where('status', 'published');
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
        if ($event->status !== 'published' && !$this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $event->load('creator:id,name,profile_photo_path');

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

        if (!$this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'in:meeting,holiday,exam,activity,sports,ceremony,other',
            'category' => 'nullable|string|max:100',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'online_url' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'registration_required' => 'boolean',
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
            $event = SchoolEvent::create([
                'academy_id' => $academy->id,
                'created_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'event_type' => $request->get('event_type', 'activity'),
                'category' => $request->category,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'location' => $request->location,
                'is_online' => $request->get('is_online', false),
                'online_url' => $request->online_url,
                'max_participants' => $request->max_participants,
                'registration_required' => $request->get('registration_required', false),
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

            $event->load('creator:id,name,profile_photo_path');

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event: ' . $e->getMessage(),
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

        if (!$this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'in:meeting,holiday,exam,activity,sports,ceremony,other',
            'category' => 'nullable|string|max:100',
            'start_datetime' => 'date',
            'end_datetime' => 'date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'online_url' => 'nullable|url',
            'max_participants' => 'nullable|integer|min:1',
            'registration_required' => 'boolean',
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
                'title', 'description', 'event_type', 'category',
                'start_datetime', 'end_datetime', 'location',
                'is_online', 'online_url', 'max_participants',
                'registration_required', 'registration_deadline',
                'is_all_day', 'is_recurring', 'recurrence_pattern'
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

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event->fresh()->load('creator:id,name,profile_photo_path'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage(),
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

        if (!$this->isAcademyAdmin($user, $academy)) {
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

        if (!$this->isAcademyAdmin($user, $academy)) {
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
        if (!$event->registration_required) {
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

        if (!$registration) {
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

        if (!$this->isAcademyAdmin($user, $academy)) {
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

        if (!$this->isAcademyAdmin($user, $academy)) {
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

        if (!$this->isAcademyAdmin($user, $academy)) {
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

        if (!$this->isAcademyAdmin($user, $academy)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldData = $event->toArray();
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
     * Check if user is academy admin
     */
    private function isAcademyAdmin($user, Academy $academy): bool
    {
        return $academy->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->exists();
    }
}
