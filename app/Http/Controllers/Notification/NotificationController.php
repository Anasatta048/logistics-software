<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A minimal in-app list over the caller's own `Notifiable::notifications()`
 * relation — no Service/Repository needed, this is a straight read of the
 * authenticated user's own rows (Section 0.1 exempts trivial single-model
 * reads like this one from needing a Service class).
 */
final class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->first()?->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }
}
