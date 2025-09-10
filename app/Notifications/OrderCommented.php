<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCommented extends Notification
{
    use Queueable;

    public $order;
    public $comment;
    public $actionUser;

    public function __construct(Order $order, Comment $comment, $actionUser = null)
    {
        $this->order = $order;
        $this->comment = $comment;
        $this->actionUser = $actionUser;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        // Добавляем FCM канал, если у пользователя есть FCM токен
        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toDatabase($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();
        return [
            'order_id' => $this->order->id,
            'comment_id' => $this->comment->id,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'message' => 'Новый комментарий к заказу #' . $this->order->id . ' от ' . ($actionUser->display_name ?? $actionUser->username),
            'comment_text' => $this->comment->text,
            'created_at' => now(),
        ];
    }

    public function toFcm($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();

        $title = 'Новый комментарий к заказу #' . $this->order->id;
        $body = 'От ' . ($actionUser->display_name ?? $actionUser->username) . ': ' . \Str::limit($this->comment->text, 100);

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'order_commented',
                'order_id' => $this->order->id,
                'comment_id' => $this->comment->id,
                'action_user_name' => $actionUser->display_name ?? $actionUser->username,
                'comment_text' => $this->comment->text,
                'url' => '/orders?order=' . $this->order->id,
            ],
        ];
    }
}
