<?php

namespace App\Notifications;

use App\Models\OrderAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentRemoved extends Notification
{
    use Queueable;

    public $assignment;
    public $actionUser;
    public $roleType;
    public $stage;

    public function __construct(OrderAssignment $assignment, $actionUser = null, $roleType = null, $stage = null)
    {
        $this->assignment = $assignment;
        $this->actionUser = $actionUser;
        $this->roleType = $roleType;
        $this->stage = $stage;
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

        // Получаем тип роли из назначения, если не передан явно
        $roleType = $this->roleType ?? $this->assignment->role_type;

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($roleType) {
            $role = \App\Models\Role::where('name', $roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $roleType;
        }

        return [
            'order_id' => $this->assignment->order_id,
            'assignment_id' => $this->assignment->id,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'role_type' => $roleType,
            'stage' => $this->stage,
            'message' => 'Ваше назначение на заказ #' . $this->assignment->order_id . ' (роль: "' . $roleDisplayName . '") было удалено пользователем ' . ($actionUser->display_name ?? $actionUser->username),
            'removed_at' => now(),
        ];
    }

    public function toFcm($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();

        // Получаем тип роли из назначения, если не передан явно
        $roleType = $this->roleType ?? $this->assignment->role_type;

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($roleType) {
            $role = \App\Models\Role::where('name', $roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $roleType;
        }

        $title = 'Назначение удалено';
        $body = 'Ваше назначение на заказ #' . $this->assignment->order_id . ' (роль: "' . $roleDisplayName . '") было удалено пользователем ' . ($actionUser->display_name ?? $actionUser->username);

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'assignment_removed',
                'order_id' => $this->assignment->order_id,
                'assignment_id' => $this->assignment->id,
                'role_type' => $roleType,
                'stage' => $this->stage,
                'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
                'url' => '/orders?order=' . $this->assignment->order_id,
            ],
        ];
    }
}
