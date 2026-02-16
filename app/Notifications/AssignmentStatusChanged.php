<?php

namespace App\Notifications;

use App\Models\OrderAssignment;
use Illuminate\Notifications\Notification;

class AssignmentStatusChanged extends Notification
{

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
        // Не отправлять уведомление самому себе
        $actionUser = $this->actionUser ?? auth()->user();
        if ($notifiable->id === $actionUser->id) {
            return [];
        }
        
        $channels = ['database'];
        
        // Добавляем FCM канал, если у пользователя есть FCM токен
        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
            \Illuminate\Support\Facades\Log::info('AssignmentStatusChanged: Adding FCM channel', [
                'user_id' => $notifiable->id,
                'username' => $notifiable->username ?? 'unknown',
                'order_id' => $this->assignment->order_id
            ]);
        } else {
            \Illuminate\Support\Facades\Log::warning('AssignmentStatusChanged: User has no FCM token, skipping FCM channel', [
                'user_id' => $notifiable->id,
                'username' => $notifiable->username ?? 'unknown'
            ]);
        }
        
        return $channels;
    }

    public function toDatabase($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user(); // Пользователь, который совершил действие

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
            'assigned_user_id' => $this->assignment->user_id,
            'assigned_user_name' => $this->assignment->user->display_name ?? $this->assignment->user->username ?? '',
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'role_type' => $roleType,
            'stage' => $this->stage,
            'status' => $this->assignment->status,
            'message' => "Пользователь " . ($actionUser->display_name ?? $actionUser->username) . " изменил статус назначения на '{$this->assignment->status}' для заказа #{$this->assignment->order_id} (роль: '" . $roleDisplayName . "')",
            'changed_at' => now(),
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
        
        $order = $this->assignment->order;
        
        $title = 'Изменен статус назначения';
        $body = 'Пользователь ' . ($actionUser->display_name ?? $actionUser->username) . ' изменил статус назначения на "' . $this->assignment->status . '" для заказа #' . $this->assignment->order_id . ' (роль: "' . $roleDisplayName . '")';

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'assignment_status_changed',
                'order_id' => $this->assignment->order_id,
                'assignment_id' => $this->assignment->id,
                'status' => $this->assignment->status,
                'role_type' => $roleType,
                'stage' => $this->stage,
                'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
                'url' => '/orders?order=' . $this->assignment->order_id,
            ],
        ];
    }
}
