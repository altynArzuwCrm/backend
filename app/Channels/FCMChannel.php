<?php

namespace App\Channels;

use App\Services\FCMService;
use Illuminate\Notifications\Notification;

class FCMChannel
{
    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->fcm_token) {
            \Illuminate\Support\Facades\Log::debug('FCM: User has no FCM token', [
                'user_id' => $notifiable->id,
                'username' => $notifiable->username ?? 'unknown'
            ]);
            return;
        }

        try {
            $message = $notification->toFcm($notifiable);

            \Illuminate\Support\Facades\Log::info('FCM: Sending notification via FCM channel', [
                'user_id' => $notifiable->id,
                'username' => $notifiable->username ?? 'unknown',
                'title' => $message['title'] ?? 'unknown'
            ]);

            $result = $this->fcmService->sendToUser(
                $notifiable->fcm_token,
                $message['title'],
                $message['body'],
                $message['data'] ?? []
            );

            if (!$result) {
                \Illuminate\Support\Facades\Log::error('FCM: Failed to send notification', [
                    'user_id' => $notifiable->id,
                    'username' => $notifiable->username ?? 'unknown',
                    'title' => $message['title'] ?? 'unknown'
                ]);
            } else {
                \Illuminate\Support\Facades\Log::info('FCM: Notification sent successfully', [
                    'user_id' => $notifiable->id,
                    'username' => $notifiable->username ?? 'unknown',
                    'title' => $message['title'] ?? 'unknown'
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('FCM: Exception in FCMChannel', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

