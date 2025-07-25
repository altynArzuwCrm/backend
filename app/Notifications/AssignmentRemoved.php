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
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();

        return [
            'order_id' => $this->assignment->order_id,
            'assignment_id' => $this->assignment->id,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->role,
            'role_type' => $this->roleType,
            'stage' => $this->stage,
            'message' => 'Ваше назначение на заказ #' . $this->assignment->order_id . ' (стадия: "' . ($this->stage ?? '-') . '", роль: "' . ($this->roleType ?? '-') . '") было удалено пользователем ' . ($actionUser->display_name ?? $actionUser->username),
            'removed_at' => now(),
        ];
    }
}
