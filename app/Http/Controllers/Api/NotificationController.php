<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Paginated list of notifications for the authenticated user, scoped by hotel.
     */
    public function index(ListNotificationsRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->has('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        $perPage = $request->integer('per_page', 20);
        $paginated = $query->paginate($perPage);

        $unreadCount = Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return NotificationResource::collection($paginated)
            ->additional([
                'meta' => [
                    'unread_count' => $unreadCount,
                ],
            ]);
    }

    /**
     * Return the count of unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $user = $request->user();

        // Tenant isolation: ensure notification belongs to this user's hotel
        if ($notification->hotel_id !== $user->hotel_id || $notification->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all unread notifications as read for the current user in their hotel.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $updated = Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'count' => $updated,
        ]);
    }
}
