<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FCMController extends Controller
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Обновить FCM токен пользователя
     */
    public function updateToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->update(['fcm_token' => $request->fcm_token]);

        Log::info('FCM token updated for user', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json([
            'message' => 'FCM токен успешно обновлен',
            'success' => true,
        ]);
    }

    /**
     * Удалить FCM токен пользователя
     */
    public function removeToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['fcm_token' => null]);

        Log::info('FCM token removed for user', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return response()->json([
            'message' => 'FCM токен успешно удален',
            'success' => true,
        ]);
    }

    /**
     * Проверить валидность FCM токена
     */
    public function validateToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->fcm_token) {
            return response()->json([
                'valid' => false,
                'message' => 'FCM токен не установлен',
            ]);
        }

        $isValid = $this->fcmService->validateToken($user->fcm_token);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'FCM токен валиден' : 'FCM токен невалиден',
        ]);
    }

    /**
     * Отправить тестовое уведомление
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'FCM токен не установлен',
            ], 400);
        }

        $success = $this->fcmService->sendToUser(
            $user->fcm_token,
            'Тестовое уведомление',
            'Это тестовое сообщение от системы Altyn Arzuw CRM',
            [
                'type' => 'test',
                'timestamp' => now()->toISOString(),
            ]
        );

        if ($success) {
            Log::info('Test FCM notification sent', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Тестовое уведомление отправлено',
            ]);
        } else {
            Log::error('Failed to send test FCM notification', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось отправить тестовое уведомление',
            ], 500);
        }
    }

    /**
     * Получить статистику FCM токенов
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Только администраторы могут видеть статистику
        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Доступ запрещен',
            ], 403);
        }

        $totalUsers = \App\Models\User::count();
        $usersWithFCM = \App\Models\User::whereNotNull('fcm_token')->count();
        $activeUsers = \App\Models\User::where('is_active', true)->count();
        $activeUsersWithFCM = \App\Models\User::where('is_active', true)
            ->whereNotNull('fcm_token')
            ->count();

        return response()->json([
            'total_users' => $totalUsers,
            'users_with_fcm' => $usersWithFCM,
            'active_users' => $activeUsers,
            'active_users_with_fcm' => $activeUsersWithFCM,
            'fcm_coverage' => $totalUsers > 0 ? round(($usersWithFCM / $totalUsers) * 100, 2) : 0,
            'active_fcm_coverage' => $activeUsers > 0 ? round(($activeUsersWithFCM / $activeUsers) * 100, 2) : 0,
        ]);
    }
}
