<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notifications()->orderBy('created_at', 'desc')->get();
    }

    public function unread(Request $request)
    {
        return $request->user()->unreadNotifications()->orderBy('created_at', 'desc')->get();
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['status' => 'ok']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['status' => 'ok']);
    }
}
