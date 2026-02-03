<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    /**
     * Show the notification center page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->input('filter') === 'unread') {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Click a notification: mark as read and redirect to deep link.
     */
    public function click(Request $request, Notification $notification): RedirectResponse
    {
        $user = $request->user();

        if ($notification->hotel_id !== $user->hotel_id || $notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->markAsRead();

        return redirect($notification->deep_link);
    }

    /**
     * Mark all unread notifications as read (web form submission).
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        Notification::query()
            ->where('hotel_id', $user->hotel_id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back();
    }
}
