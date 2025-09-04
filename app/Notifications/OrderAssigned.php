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

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($this->roleType) {
            $role = \App\Models\Role::where('name', $this->roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $this->roleType;
        }



        return [
            'order_id' => $this->order->id,
            'project_id' => $this->order->project_id,
            'title' => $this->order->project?->title ?? 'Заказ #' . $this->order->id,
            'action_user_id' => $actionUser ? $actionUser->id : null,
            'action_user_name' => $actionUser ? ($actionUser->display_name ?? $actionUser->username ?? '') : '',
            'action_user_role' => $actionUser ? $actionUser->role : null,
            'role_type' => $this->roleType,
            'stage' => $this->stage,
            'message' => 'Вам назначен новый заказ #' . $this->order->id . ' в роли "' . $roleDisplayName . '" пользователем ' . ($actionUser ? ($actionUser->display_name ?? $actionUser->username) : 'система'),
            'assigned_at' => now(),
            'icon' => 'assignment',
            'url' => '/orders?order=' . $this->order->id,
        ];
    }
}
