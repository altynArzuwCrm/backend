<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        return $user->notifications()->orderBy('created_at', 'desc')->get();
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        return $user->unreadNotifications()->orderBy('created_at', 'desc')->get();
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['status' => 'ok']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $user->unreadNotifications->markAsRead();
        return response()->json(['status' => 'ok']);
    }
}
