<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

class BulkOrdersStageChanged extends Notification
{
    use Queueable;

    /**
     * @var array<int, array{
     *     id: int,
     *     title: string,
     *     old_stage: string,
     *     new_stage: string
     * }>
     */
    protected array $orders;

    protected string $stageName;

    protected string $stageDisplayName;

    protected $actionUser;

    public function __construct(array $orders, string $stageName, string $stageDisplayName, $actionUser)
    {
        $this->orders = array_map(function ($order) {
            return [
                'id' => Arr::get($order, 'id'),
                'title' => Arr::get($order, 'title'),
                'old_stage' => Arr::get($order, 'old_stage'),
                'new_stage' => Arr::get($order, 'new_stage'),
            ];
        }, $orders);

        $this->stageName = $stageName;
        $this->stageDisplayName = $stageDisplayName;
        $this->actionUser = $actionUser;
    }

    public function via($notifiable)
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toDatabase($notifiable)
    {
        $orderList = collect($this->orders)
            ->pluck('id')
            ->map(fn ($id) => '#' . $id)
            ->implode(', ');

        $actionUser = $this->actionUser;

        return [
            'orders' => $this->orders,
            'stage' => [
                'name' => $this->stageName,
                'display_name' => $this->stageDisplayName,
            ],
            'action_user_id' => $actionUser?->id,
            'action_user_name' => $actionUser?->display_name ?? $actionUser?->username ?? '',
            'message' => sprintf(
                'Пользователь %s изменил стадию на "%s" для заказов: %s',
                $actionUser?->display_name ?? $actionUser?->username ?? 'Сотрудник',
                $this->stageDisplayName,
                $orderList
            ),
            'changed_at' => now(),
        ];
    }

    public function toFcm($notifiable)
    {
        $orderList = collect($this->orders)
            ->pluck('id')
            ->map(fn ($id) => '#' . $id)
            ->implode(', ');

        $actionUser = $this->actionUser;

        return [
            'title' => 'Изменение стадии заказов',
            'body' => sprintf(
                '%s изменил стадию на "%s" для заказов: %s',
                $actionUser?->display_name ?? $actionUser?->username ?? 'Сотрудник',
                $this->stageDisplayName,
                $orderList
            ),
            'data' => [
                'type' => 'bulk_orders_stage_changed',
                'stage_name' => $this->stageName,
                'stage_display_name' => $this->stageDisplayName,
                'order_ids' => collect($this->orders)->pluck('id')->implode(','),
            ],
        ];
    }
}


