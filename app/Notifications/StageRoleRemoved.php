<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Stage;
use Illuminate\Notifications\Notification;

class StageRoleRemoved extends Notification
{

    public $order;
    public $stage;
    public $roleType;
    public $actionUser;

    public function __construct(Order $order, Stage $stage, $roleType, $actionUser = null)
    {
        $this->order = $order;
        $this->stage = $stage;
        $this->roleType = $roleType;
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
            'stage_id' => $this->stage->id,
            'stage_name' => $this->stage->name,
            'role_type' => $this->roleType,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'message' => 'Ваша роль "' . $roleDisplayName . '" на стадии "' . $this->stage->display_name . '" для заказа #' . $this->order->id . ' была удалена пользователем ' . ($actionUser->display_name ?? $actionUser->username),
            'removed_at' => now(),
            'icon' => 'stage_removal',
        ];
    }

    public function toFcm($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($this->roleType) {
            $role = \App\Models\Role::where('name', $this->roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $this->roleType;
        }

        $title = $this->order->project?->title ?? 'Заказ #' . $this->order->id;
        $body = 'Ваша роль "' . $roleDisplayName . '" на стадии "' . $this->stage->display_name . '" для заказа #' . $this->order->id . ' была удалена пользователем ' . ($actionUser->display_name ?? $actionUser->username ?? '');

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'stage_role_removed',
                'order_id' => $this->order->id,
                'project_id' => $this->order->project_id,
                'stage_id' => $this->stage->id,
                'stage_name' => $this->stage->name,
                'role_type' => $this->roleType,
                'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
                'url' => '/orders?order=' . $this->order->id,
            ],
        ];
    }
}
