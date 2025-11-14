<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     * Includes both topic-based and FCM notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user-specific FCM notifications
        $userNotifications = UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'sent' => $notification->sent,
                    'created_at' => $notification->created_at,
                    'notification_type' => 'user', // distinguish from topic
                ];
            });

        // Get topic-based notifications (broadcast to all users based on role)
        $topic = $user->role; // 'user', 'partner', 'admin'
        $topicNotifications = Notification::where('topic', $topic)
            ->where('sent', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'topic' => $notification->topic,
                    'data' => $notification->data,
                    'sent' => $notification->sent,
                    'created_at' => $notification->created_at,
                    'notification_type' => 'topic', // distinguish from user
                ];
            });

        // Merge both collections
        $allNotifications = $userNotifications->concat($topicNotifications)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $allNotifications,
            'count' => $allNotifications->count(),
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = UserNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }
}

