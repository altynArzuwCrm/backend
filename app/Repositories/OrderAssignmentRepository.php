<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderAssignment;
use App\Services\CacheService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для работы с назначениями заказов.
 */
class OrderAssignmentRepository
{
    /**
     * Массовое обновление статуса назначений по списку заказов.
     * Обновляет все назначения с учётом прав доступа пользователя.
     *
     * @param array $orderIds
     * @param string $newStatus
     * @param \App\Models\User $user
     * @return array{updated: int, errors: array}
     */
    public function bulkUpdateStatus(array $orderIds, string $newStatus, $user): array
    {
        $updated = 0;
        $errors = [];

        foreach ($orderIds as $orderId) {
            try {
                $order = Order::find($orderId);
                if (!$order) {
                    $errors[] = "Заказ ID {$orderId} не найден";
                    continue;
                }

                $assignments = $order->assignments()->get();

                foreach ($assignments as $assignment) {
                    if (Gate::denies('updateStatus', $assignment)) {
                        $errors[] = "Заказ ID {$orderId}: нет прав на изменение назначения #{$assignment->id}";
                        continue;
                    }

                    $assignment->status = $newStatus;
                    $assignment->save();
                    CacheService::invalidateOrderCaches($order->id);
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Заказ ID {$orderId}: {$e->getMessage()}";
                Log::error("Bulk assignment status update error for Order {$orderId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
