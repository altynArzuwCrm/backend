<?php

namespace App\Notifications;

use App\Models\OrderAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentStatusChanged extends Notification
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
        // Не отправлять уведомление самому себе
        $actionUser = $this->actionUser ?? auth()->user();
        if ($notifiable->id === $actionUser->id) {
            return [];
        }
        return ['database'];
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
        $title = 'Изменен статус назначения';
        $body = 'Статус вашего назначения на заказ #%order_id% изменен на "%status%"';
        
        // Заменяем плейсхолдеры на реальные данные
        $title = str_replace([
            '%order_id%',
            '%stage%',
            '%old_stage%',
            '%user%',
            '%status%',
            '%role%'
        ], [
            $this->order->id ?? '',
            $this->stage->name ?? '',
            $this->oldStage->name ?? '',
            $this->actionUser->name ?? '',
            $this->status ?? '',
            $this->roleType ?? ''
        ], $title);
        
        $body = str_replace([
            '%order_id%',
            '%stage%',
            '%old_stage%',
            '%user%',
            '%status%',
            '%role%'
        ], [
            $this->order->id ?? '',
            $this->stage->name ?? '',
            $this->oldStage->name ?? '',
            $this->actionUser->name ?? '',
            $this->status ?? '',
            $this->roleType ?? ''
        ], $body);

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'assignment_status_changed',
                'order_id' => $this->order->id ?? null,
                'stage' => $this->stage->name ?? null,
                'action_user_name' => $this->actionUser->name ?? '',
                'url' => '/orders?order=' . ($this->order->id ?? ''),
            ],
        ];
    }
