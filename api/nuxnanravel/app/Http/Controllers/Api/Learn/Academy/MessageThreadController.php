<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\Academy;
use App\Models\MessageThread;
use App\Models\ThreadParticipant;
use App\Models\ThreadMessage;
use App\Models\MessageReadReceipt;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageThreadController extends Controller
{
    protected AuditLogService $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    /**
     * List message threads for current user
     */
    public function index(Request $request, Academy $academy)
    {
        $user = $request->user();
        
        $query = MessageThread::where('academy_id', $academy->id)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereNull('left_at');
            })
            ->with([
                'participants' => function ($q) {
                    $q->with('user:id,name,profile_photo_path')
                      ->whereNull('left_at');
                },
                'latestMessage.sender:id,name',
            ]);

        // Filter by thread type
        if ($request->has('type')) {
            $query->where('thread_type', $request->type);
        }

        // Filter archived
        if ($request->has('archived')) {
            $query->where('is_archived', $request->boolean('archived'));
        } else {
            $query->where('is_archived', false);
        }

        $threads = $query->orderByDesc('last_message_at')
            ->paginate($request->get('per_page', 15));

        // Get unread counts
        $threads->getCollection()->transform(function ($thread) use ($user) {
            $participant = $thread->participants->firstWhere('user_id', $user->id);
            $lastRead = $participant ? $participant->last_read_at : null;
            
            $thread->unread_count = $thread->messages()
                ->where('created_at', '>', $lastRead ?? '1970-01-01')
                ->where('sender_id', '!=', $user->id)
                ->count();
            
            $thread->is_muted = $participant ? $participant->is_muted : false;
            
            return $thread;
        });

        return response()->json([
            'success' => true,
            'data' => $threads->items(),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'total' => $threads->total(),
                'per_page' => $threads->perPage(),
                'last_page' => $threads->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single thread with messages
     */
    public function show(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        // Check if user is participant
        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $thread->load([
            'participants' => function ($q) {
                $q->with('user:id,name,profile_photo_path')
                  ->whereNull('left_at');
            },
            'relatedStudent:id,name,profile_photo_path',
        ]);

        // Mark as read
        $participant->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $thread,
        ]);
    }

    /**
     * Get messages for a thread
     */
    public function messages(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        // Check if user is participant
        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $messages = $thread->messages()
            ->with([
                'sender:id,name,profile_photo_path',
                'replyTo:id,content,sender_id',
                'replyTo.sender:id,name',
            ])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 30));

        // Mark as read
        $participant->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new thread
     */
    public function store(Request $request, Academy $academy)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:255',
            'thread_type' => 'required|in:direct,group,parent_teacher,class',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'related_student_id' => 'nullable|exists:users,id',
            'class_id' => 'nullable|exists:academy_groups,id',
            'initial_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // For direct messages, check if thread already exists
        if ($request->thread_type === 'direct' && count($request->participant_ids) === 1) {
            $existingThread = MessageThread::where('academy_id', $academy->id)
                ->where('thread_type', 'direct')
                ->whereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->whereNull('left_at');
                })
                ->whereHas('participants', function ($q) use ($request) {
                    $q->where('user_id', $request->participant_ids[0])->whereNull('left_at');
                })
                ->first();

            if ($existingThread) {
                return response()->json([
                    'success' => true,
                    'message' => 'Existing thread found',
                    'data' => $existingThread->load('participants.user:id,name,profile_photo_path'),
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $thread = MessageThread::create([
                'academy_id' => $academy->id,
                'subject' => $request->subject,
                'thread_type' => $request->thread_type,
                'related_student_id' => $request->related_student_id,
                'class_id' => $request->class_id,
            ]);

            // Add creator as participant (admin for group/parent_teacher)
            $creatorRole = in_array($request->thread_type, ['group', 'parent_teacher']) ? 'admin' : 'member';
            $thread->addParticipant($user->id, $creatorRole);

            // Add other participants
            foreach ($request->participant_ids as $participantId) {
                if ($participantId != $user->id) {
                    $thread->addParticipant($participantId, 'member');
                }
            }

            // Send initial message if provided
            if ($request->initial_message) {
                $message = ThreadMessage::create([
                    'thread_id' => $thread->id,
                    'sender_id' => $user->id,
                    'content' => $request->initial_message,
                    'message_type' => 'text',
                ]);

                $thread->update(['last_message_at' => now()]);
            }

            DB::commit();

            $thread->load([
                'participants.user:id,name,profile_photo_path',
                'latestMessage.sender:id,name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thread created successfully',
                'data' => $thread,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create thread: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message to a thread
     */
    public function sendMessage(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        // Check if user is participant
        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not a participant'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'message_type' => 'in:text,file,image',
            'attachments' => 'nullable|array',
            'reply_to_id' => 'nullable|exists:thread_messages,id',
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
            $message = ThreadMessage::create([
                'thread_id' => $thread->id,
                'sender_id' => $user->id,
                'content' => $request->content,
                'message_type' => $request->get('message_type', 'text'),
                'attachments' => $request->attachments,
                'reply_to_id' => $request->reply_to_id,
            ]);

            $thread->update(['last_message_at' => now()]);

            // Mark as read for sender
            $participant->markAsRead();

            DB::commit();

            $message->load([
                'sender:id,name,profile_photo_path',
                'replyTo:id,content,sender_id',
                'replyTo.sender:id,name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent',
                'data' => $message,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, Academy $academy, MessageThread $thread, ThreadMessage $message)
    {
        if ($thread->academy_id !== $academy->id || $message->thread_id !== $thread->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        if ($message->sender_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message->edit($request->content);

        return response()->json([
            'success' => true,
            'message' => 'Message updated',
            'data' => $message->load('sender:id,name,profile_photo_path'),
        ]);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Request $request, Academy $academy, MessageThread $thread, ThreadMessage $message)
    {
        if ($thread->academy_id !== $academy->id || $message->thread_id !== $thread->id) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $user = $request->user();

        // Allow message owner or thread admin to delete
        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if ($message->sender_id !== $user->id && $participant?->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Message deleted',
        ]);
    }

    /**
     * Add participant to a thread
     */
    public function addParticipant(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        // Check if user is admin
        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->whereNull('left_at')
            ->first();

        if (!$participant && !in_array($thread->thread_type, ['group', 'parent_teacher'])) {
            return response()->json(['success' => false, 'message' => 'Cannot add participants to this thread type'], 400);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'in:member,moderator,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $thread->addParticipant($request->user_id, $request->get('role', 'member'));

        // Add system message
        ThreadMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'content' => 'Added a new participant',
            'message_type' => 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant added',
        ]);
    }

    /**
     * Remove participant from a thread
     */
    public function removeParticipant(Request $request, Academy $academy, MessageThread $thread, int $participantUserId)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        // Check if user is admin or removing themselves
        $isAdmin = $thread->participants()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->whereNull('left_at')
            ->exists();

        if (!$isAdmin && $participantUserId != $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $thread->removeParticipant($participantUserId);

        // Add system message
        ThreadMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $user->id,
            'content' => $participantUserId == $user->id ? 'Left the conversation' : 'Removed a participant',
            'message_type' => 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant removed',
        ]);
    }

    /**
     * Archive a thread
     */
    public function archive(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not a participant'], 403);
        }

        $thread->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Thread archived',
        ]);
    }

    /**
     * Unarchive a thread
     */
    public function unarchive(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not a participant'], 403);
        }

        $thread->update(['is_archived' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Thread unarchived',
        ]);
    }

    /**
     * Mute thread notifications
     */
    public function mute(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not a participant'], 403);
        }

        $participant->mute();

        return response()->json([
            'success' => true,
            'message' => 'Thread muted',
        ]);
    }

    /**
     * Unmute thread notifications
     */
    public function unmute(Request $request, Academy $academy, MessageThread $thread)
    {
        if ($thread->academy_id !== $academy->id) {
            return response()->json(['success' => false, 'message' => 'Thread not found'], 404);
        }

        $user = $request->user();

        $participant = $thread->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not a participant'], 403);
        }

        $participant->unmute();

        return response()->json([
            'success' => true,
            'message' => 'Thread unmuted',
        ]);
    }

    /**
     * Get unread count across all threads
     */
    public function unreadCount(Request $request, Academy $academy)
    {
        $user = $request->user();

        $threads = MessageThread::where('academy_id', $academy->id)
            ->where('is_archived', false)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('is_muted', false)
                  ->whereNull('left_at');
            })
            ->get();

        $totalUnread = 0;
        foreach ($threads as $thread) {
            $participant = $thread->participants->firstWhere('user_id', $user->id);
            $lastRead = $participant ? $participant->last_read_at : null;
            
            $totalUnread += $thread->messages()
                ->where('created_at', '>', $lastRead ?? '1970-01-01')
                ->where('sender_id', '!=', $user->id)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $totalUnread,
            ],
        ]);
    }
}
