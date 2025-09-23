<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\FirebaseCloudMessaging;

class FCMService
{
    private $accessToken;
    private $fcmUrl = 'https://fcm.googleapis.com/v1/projects/';

    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Получить access token для FCM v1 API
     */
    public function getAccessToken()
    {
        try {
            $client = new Client();
            $client->setAuthConfig(storage_path('app/firebase-service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            $accessToken = $client->fetchAccessTokenWithAssertion();

            if (isset($accessToken['access_token'])) {
                return $accessToken['access_token'];
            }

            Log::error('FCM: Failed to get access token', $accessToken);
            return null;
        } catch (\Exception $e) {
            Log::error('FCM: Error getting access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Отправить уведомление одному пользователю
     */
    public function sendToUser($fcmToken, $title, $body, $data = [])
    {
        if (!$fcmToken || !$this->accessToken) {
            Log::warning('FCM: Missing token or access token');
            return false;
        }

        $projectId = config('services.fcm.project_id');
        if (!$projectId) {
            Log::error('FCM: Project ID not configured');
            return false;
        }

        // Преобразуем данные в строки для FCM API v1
        $stringData = [];
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $value) {
                $stringData[$key] = (string) $value;
            }
        }

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'notification' => [
                        'sound' => 'default',
                        'icon' => 'ic_notification',
                    ],
                    'priority' => 'high',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Добавляем данные только если они есть
        if (!empty($stringData)) {
            $payload['message']['data'] = $stringData;
        }

        return $this->sendRequest($payload, $projectId);
    }

    /**
     * Отправить уведомление нескольким пользователям
     */
    public function sendToMultipleUsers($fcmTokens, $title, $body, $data = [])
    {
        if (empty($fcmTokens) || !$this->accessToken) {
            Log::warning('FCM: Missing tokens or access token');
            return false;
        }

        $projectId = config('services.fcm.project_id');
        if (!$projectId) {
            Log::error('FCM: Project ID not configured');
            return false;
        }

        $results = [];
        foreach ($fcmTokens as $token) {
            $results[] = $this->sendToUser($token, $title, $body, $data);
        }

        return !in_array(false, $results);
    }

    /**
     * Отправить уведомление всем активным пользователям
     */
    public function sendToAllActiveUsers($title, $body, $data = [])
    {
        $fcmTokens = \App\Models\User::where('is_active', true)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($fcmTokens)) {
            Log::info('FCM: No active users with FCM tokens found');
            return false;
        }

        return $this->sendToMultipleUsers($fcmTokens, $title, $body, $data);
    }

    /**
     * Отправить уведомление пользователям с определенной ролью
     */
    public function sendToUsersByRole($roleName, $title, $body, $data = [])
    {
        $fcmTokens = \App\Models\User::where('is_active', true)
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($fcmTokens)) {
            Log::info("FCM: No users with role {$roleName} and FCM tokens found");
            return false;
        }

        return $this->sendToMultipleUsers($fcmTokens, $title, $body, $data);
    }

    /**
     * Отправить HTTP запрос к FCM
     */
    private function sendRequest($payload, $projectId)
    {
        try {
            $url = $this->fcmUrl . $projectId . '/messages:send';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('FCM notification sent successfully', [
                    'name' => $result['name'] ?? 'unknown',
                ]);
                return true;
            } else {
                Log::error('FCM notification failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверить валидность FCM токена
     */
    public function validateToken($fcmToken)
    {
        if (!$fcmToken || !$this->accessToken) {
            return false;
        }

        // Отправляем тестовое уведомление для проверки токена
        return $this->sendToUser($fcmToken, 'Test', 'Token validation', ['test' => 'true']);
    }
}
