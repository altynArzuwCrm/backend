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

        return [
            'order_id' => $this->assignment->order_id,
            'assignment_id' => $this->assignment->id,
            'assigned_user_id' => $this->assignment->user_id,
            'assigned_user_name' => $this->assignment->user->display_name ?? $this->assignment->user->username ?? '',
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'role_type' => $this->roleType,
            'stage' => $this->stage,
            'status' => $this->assignment->status,
            'message' => "Пользователь " . ($actionUser->display_name ?? $actionUser->username) . " изменил статус назначения на '{$this->assignment->status}' для заказа #{$this->assignment->order_id} (стадия: '" . ($this->stage ?? '-') . "', роль: '" . ($this->roleType ?? '-') . "')",
            'changed_at' => now(),
        ];
    }
}
