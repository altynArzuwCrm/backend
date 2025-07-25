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
        return ['database'];
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
}
