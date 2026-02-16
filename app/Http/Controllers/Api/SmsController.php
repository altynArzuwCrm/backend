<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    public function sendUnapprovedTasksReminders(): JsonResponse
    {
        $users = User::query()
            ->whereNotNull('phone')
            ->where('is_active', true)
            ->withCount([
                'assignments as unapproved_tasks_count' => function ($query) {
                    $query->where('status', '!=', 'approved');
                }
            ])
            ->having('unapproved_tasks_count', '>', 0)
            ->get();

        if ($users->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No users with unapproved tasks found.',
                'sent_count' => 0
            ]);
        }

        // Transform users to the required data format
        $data = $users->map(function ($user) {
            return [
                'phone' => $user->phone,
                'message' => "У вас {$user->unapproved_tasks_count} неподтвержденных задач. Пожалуйста, проверьте их."
            ];
        });

        // Return the data directly
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'count' => $data->count()
        ]);
    }
}
