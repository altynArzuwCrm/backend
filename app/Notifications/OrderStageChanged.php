<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Stage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStageChanged extends Notification
{
    use Queueable;

    public $order;
    public $oldStage;
    public $newStage;
    public $actionUser;
    public $roleType;

    public function __construct(Order $order, $oldStage = null, ?Stage $newStage = null, $actionUser = null, $roleType = null)
    {
        $this->order = $order;
        $this->oldStage = $oldStage;
        $this->newStage = $newStage;
        $this->actionUser = $actionUser;
        $this->roleType = $roleType;
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
        $oldStageName = $this->oldStage ? $this->oldStage->display_name : 'Не определена';
        $newStageName = $this->newStage ? $this->newStage->display_name : 'Не определена';

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($this->roleType) {
            $role = \App\Models\Role::where('name', $this->roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $this->roleType;
        }

        // Создаем разные сообщения в зависимости от того, есть ли roleType
        if ($this->roleType) {
            $message = 'Заказ #' . $this->order->id . ' переведен на стадию "' . $newStageName . '" (ваша роль: ' . $roleDisplayName . '). Вам необходимо выполнить работу по заказу.';
        } else {
            $message = 'Заказ #' . $this->order->id . ' переведен со стадии "' . $oldStageName . '" на стадию "' . $newStageName . '" пользователем ' . ($actionUser->display_name ?? $actionUser->username);
        }

        return [
            'order_id' => $this->order->id,
            'project_id' => $this->order->project_id,
            'title' => $this->order->project?->title ?? 'Заказ #' . $this->order->id,
            'old_stage_id' => $this->oldStage ? $this->oldStage->id : null,
            'old_stage_name' => $oldStageName,
            'new_stage_id' => $this->newStage ? $this->newStage->id : null,
            'new_stage_name' => $newStageName,
            'action_user_id' => $actionUser->id,
            'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
            'action_user_role' => $actionUser->roles->first() ? $actionUser->roles->first()->name : null,
            'role_type' => $this->roleType,
            'message' => $message,
            'changed_at' => now(),
            'icon' => $this->roleType ? 'assignment' : 'stage_transition',
            'url' => '/orders?order=' . $this->order->id,
        ];
    }

    public function toFcm($notifiable)
    {
        $actionUser = $this->actionUser ?? auth()->user();
        $oldStageName = $this->oldStage ? $this->oldStage->display_name : 'Не определена';
        $newStageName = $this->newStage ? $this->newStage->display_name : 'Не определена';

        // Получаем display_name роли
        $roleDisplayName = '-';
        if ($this->roleType) {
            $role = \App\Models\Role::where('name', $this->roleType)->first();
            $roleDisplayName = $role ? $role->display_name : $this->roleType;
        }

        // Создаем разные сообщения в зависимости от того, есть ли roleType
        if ($this->roleType) {
            $title = $this->order->project?->title ?? 'Заказ #' . $this->order->id;
            $body = 'Заказ #' . $this->order->id . ' переведен на стадию "' . $newStageName . '" (ваша роль: ' . $roleDisplayName . '). Вам необходимо выполнить работу по заказу.';
        } else {
            $title = $this->order->project?->title ?? 'Заказ #' . $this->order->id;
            $body = 'Заказ #' . $this->order->id . ' переведен со стадии "' . $oldStageName . '" на стадию "' . $newStageName . '" пользователем ' . ($actionUser->display_name ?? $actionUser->username);
        }

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'order_stage_changed',
                'order_id' => $this->order->id,
                'project_id' => $this->order->project_id,
                'old_stage_id' => $this->oldStage ? $this->oldStage->id : null,
                'old_stage_name' => $oldStageName,
                'new_stage_id' => $this->newStage ? $this->newStage->id : null,
                'new_stage_name' => $newStageName,
                'action_user_name' => $actionUser->display_name ?? $actionUser->username ?? '',
                'role_type' => $this->roleType,
                'url' => '/orders?order=' . $this->order->id,
            ],
        ];
    }
