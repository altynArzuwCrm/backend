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
            return;
        }

        $message = $notification->toFcm($notifiable);

        return $this->fcmService->sendToUser(
            $notifiable->fcm_token,
            $message['title'],
            $message['body'],
            $message['data'] ?? []
        );
    }
}

