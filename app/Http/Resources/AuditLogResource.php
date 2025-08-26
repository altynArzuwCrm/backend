<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Карта переводов для ключей
        $fieldTranslations = [
            'total_price' => 'Общая сумма',
            'text' => 'Комментарий',
            'order_id' => 'Заказ',
            'client_id' => 'Клиент',
            'product_id' => 'Товар',
            'stage' => 'Этап',
            'price' => 'Цена',
            'name' => 'Имя',
            'company_name' => 'Компания',
            'deadline' => 'Дедлайн',
            'quantity' => 'Количество',
            // Добавьте другие поля по необходимости
        ];

        // Функция для перевода ключей и значений
        $translate = function ($arr) use ($fieldTranslations) {
            if (!is_array($arr)) return $arr;
            $result = [];
            foreach ($arr as $key => $value) {
                $translatedKey = $fieldTranslations[$key] ?? $key;
                $translatedValue = $value;
                // Пример: если это id пользователя, можно подгрузить имя (опционально)
                $result[$translatedKey] = $translatedValue;
            }
            return $result;
        };

        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
            ] : null,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'model_name' => $this->model_name,
            'action' => $this->action,
            'action_name' => $this->action_name,
            'old_values' => $translate($this->old_values),
            'new_values' => $translate($this->new_values),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'created_at_human' => $this->created_at ? $this->created_at->diffForHumans() : null,
            'auditable' => $this->whenLoaded('auditable', function () {
                $name = null;
                if ($this->auditable_type === 'App\\Models\\Order') {
                    $name = $this->auditable->display_name ?? "Заказ #{$this->auditable->id}";
                } else {
                    $name = $this->auditable->name ?? $this->auditable->title ?? $this->auditable->id;
                }
                return [
                    'id' => $this->auditable->id,
                    'name' => $name,
                ];
            }),
        ];
    }
}
