<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OrderAssigned extends Notification
{
    use Queueable;

    public $order;
    public $actionUser;
    public $roleType;
    public $stage;

    public function __construct(Order $order, $actionUser = null, $roleType = null, $stage = null)
    {
        $this->order = $order;
        $this->actionUser = $actionUser;
        $this->roleType = $roleType;
        $this->stage = $stage;
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
            'project_id' => $this->order->project_id,
            'title' => $this->order->project?->title ?? 'Заказ #' . $this->order->id,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'role_type' => $this->roleType,
            'stage' => $this->stage,
            'message' => 'Вам назначен новый заказ #' . $this->order->id . ' на стадию "' . ($this->stage ?? '-') . '" в роли "' . ($this->roleType ?? '-') . '" пользователем ' . ($actionUser->display_name ?? $actionUser->username),
            'assigned_at' => now(),
        ];
    }
}
