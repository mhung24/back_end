<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('recipient_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'title' => $notif->title ?? 'Thông báo hệ thống',
                'message' => $notif->message ?? '',

                'type' => in_array($notif->type, ['success', 'article_created']) ? 'success' : 'info',

                'time' => $notif->created_at->diffForHumans(),
                'isRead' => (bool) $notif->is_read
            ];
        });

        return response()->json($formatted);
    }

    public function markAsRead($id)
    {
        $notification = Notification::find($id);
        // Dùng auth()->id() cho ngắn gọn và chuẩn
        if ($notification && $notification->recipient_id == auth()->id()) {
            $notification->update(['is_read' => true]);
        }
        return response()->json(['message' => 'Đã đọc']);
    }
}
