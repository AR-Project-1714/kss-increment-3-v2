<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->systemNotifications()->active();
        $unreadCount = (clone $query)->whereNull('read_at')->count();

        $items = $query
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (SystemNotification $notification): array => [
                'id' => $notification->id,
                'category' => $notification->category,
                'severity' => $notification->severity,
                'title' => $notification->title,
                'message' => $notification->message,
                'action_url' => $notification->action_url,
                'action_label' => $notification->action_label,
                'is_read' => $notification->read_at !== null,
                'occurred_at' => $notification->created_at?->locale('id')->translatedFormat('d M Y, H:i'),
                'occurred_ago' => $notification->created_at?->locale('id')->diffForHumans(),
                'expires_at' => $notification->expires_at?->locale('id')->translatedFormat('d M Y, H:i'),
            ]);

        return response()->json([
            'unread_count' => $unreadCount,
            'items' => $items,
            'refreshed_at' => now()->locale('id')->translatedFormat('H:i'),
        ]);
    }

    public function markRead(Request $request, SystemNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()
            ->systemNotifications()
            ->active()
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
