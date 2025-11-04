<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Оптимизация: добавляем пагинацию для больших списков уведомлений
        $perPage = min((int) $request->get('per_page', 20), 100);
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($notifications);
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Оптимизация: добавляем пагинацию и кэширование количества непрочитанных
        $cacheKey = "user_{$user->id}_unread_count";
        $unreadCount = Cache::remember($cacheKey, 60, function () use ($user) {
            return $user->unreadNotifications()->count();
        });

        $perPage = min((int) $request->get('per_page', 20), 100);
        $notifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $unreadCount
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Оптимизация: используем прямой запрос вместо findOrFail + markAsRead
        $updated = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated === 0) {
            return response()->json(['error' => 'Notification not found or already read'], 404);
        }

        // Инвалидируем кэш количества непрочитанных
        Cache::forget("user_{$user->id}_unread_count");

        return response()->json(['status' => 'ok']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Оптимизация: используем массовое обновление через DB вместо загрузки всех уведомлений
        $updated = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Инвалидируем кэш количества непрочитанных
        Cache::forget("user_{$user->id}_unread_count");

        return response()->json([
            'status' => 'ok',
            'marked_count' => $updated
        ]);
    }
}
