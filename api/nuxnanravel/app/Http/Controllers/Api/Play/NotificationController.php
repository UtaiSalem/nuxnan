<?php

namespace App\Http\Controllers\Api\Play;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::forUser($request->user()->id)
            ->with('sender:id,name,profile_photo_path')
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('unread_only') && $request->unread_only) {
            $query->unread();
        }

        $types = $request->input('types', []);
        if (is_string($types)) {
            $types = [$types];
        }

        if (is_array($types) && ! empty($types)) {
            $query->whereIn('type', array_values(array_filter($types, fn ($type) => is_string($type) && $type !== '')));
        } elseif ($request->type) {
            $query->ofType($request->type);
        }

        $notifications = $query->paginate($request->per_page ?? 20);

        // Add computed attributes
        $notifications->getCollection()->transform(function ($notification) {
            $notification->icon = $notification->icon;
            $notification->color = $notification->color;

            return $notification;
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get recent notifications (for dropdown)
     */
    public function recent(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->with('sender:id,name,profile_photo_path')
            ->orderBy('created_at', 'desc')
            ->limit($request->limit ?? 10)
            ->get();

        // Add computed attributes
        $notifications->transform(function ($notification) {
            $notification->icon = $notification->icon;
            $notification->color = $notification->color;

            return $notification;
        });

        $unreadCount = Notification::forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        // Verify ownership
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'อ่านแล้ว',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read_status' => true]);

        return response()->json([
            'success' => true,
            'message' => 'อ่านทั้งหมดแล้ว',
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        // Verify ownership
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบแล้ว',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead(Request $request): JsonResponse
    {
        $deleted = Notification::forUser($request->user()->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "ลบ {$deleted} รายการ",
            'data' => ['deleted_count' => $deleted],
        ]);
    }
}
