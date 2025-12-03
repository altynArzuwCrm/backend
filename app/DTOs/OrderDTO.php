<?php

namespace App\DTOs;

use App\Models\Order;
use App\Models\Client;
use App\Models\Project;
use App\Models\Product;
use App\Models\Stage;
use App\Models\OrderAssignment;
use Illuminate\Support\Collection;

class OrderDTO
{
    public function __construct(
        public int $id,
        public ?int $client_id,
        public ?int $project_id,
        public ?int $product_id,
        public ?int $quantity,
        public ?string $deadline,
        public ?float $price,
        public ?float $payment_amount,
        public string $stage,
        public ?string $status,
        public ?string $reason,
        public ?string $reason_status,
        public ?bool $is_archived,
        public ?string $archived_at,
        public ?string $work_type,
        public ?string $created_at,
        public ?string $updated_at,
        public ?ClientDTO $client = null,
        public ?ProjectDTO $project = null,
        public ?ProductDTO $product = null,
        public ?StageDTO $current_stage = null,
        public array $assignments = [],
        public array $stages = []
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            client_id: $order->client_id,
            project_id: $order->project_id,
            product_id: $order->product_id,
            quantity: $order->quantity,
            deadline: $order->deadline ? (is_string($order->deadline) ? $order->deadline : $order->deadline->toISOString()) : null,
            price: $order->price,
            payment_amount: $order->payment_amount ?? null,
            // Always return stage as a plain string (stage name)
            stage: is_string($order->stage) ? $order->stage : ($order->stage?->name ?? 'draft'),
            status: $order->status,
            reason: $order->reason,
            reason_status: $order->reason_status,
            is_archived: $order->is_archived ?? false,
            archived_at: $order->archived_at ? (is_string($order->archived_at) ? $order->archived_at : $order->archived_at->toISOString()) : null,
            work_type: $order->work_type,
            created_at: $order->created_at ? (is_string($order->created_at) ? $order->created_at : $order->created_at->toISOString()) : null,
            updated_at: $order->updated_at ? (is_string($order->updated_at) ? $order->updated_at : $order->updated_at->toISOString()) : null,
            client: $order->client ? ClientDTO::fromModel($order->client) : null,
            project: $order->project ? ProjectDTO::fromModel($order->project) : null,
            product: $order->product ? ProductDTO::fromModel($order->product) : null,
            current_stage: $order->stage ? StageDTO::fromModel($order->stage) : null,
            assignments: $order->assignments ? $order->assignments->map(fn($assignment) => OrderAssignmentDTO::fromModel($assignment))->toArray() : [],
            stages: [] // У заказа нет прямого отношения stages
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'project_id' => $this->project_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'deadline' => $this->deadline,
            'price' => $this->price,
            'payment_amount' => $this->payment_amount,
            'stage' => $this->stage,
            'status' => $this->status,
            'reason' => $this->reason,
            'reason_status' => $this->reason_status,
            'is_archived' => $this->is_archived,
            'archived_at' => $this->archived_at,
            'work_type' => $this->work_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client' => $this->client?->toArray(),
            'project' => $this->project?->toArray(),
            'product' => $this->product?->toArray(),
            'current_stage' => $this->current_stage?->toArray(),
            'assignments' => array_map(fn($assignment) => $assignment->toArray(), $this->assignments),
            'stages' => array_map(fn($stage) => $stage->toArray(), $this->stages)
        ];
    }

    public static function validate(array $data): array
    {
        $errors = [];

        // Обязательные поля
        if (empty($data['stage'])) {
            $errors['stage'] = 'Стадия обязательна';
        }

        // Валидация числовых полей
        if (isset($data['quantity']) && !is_numeric($data['quantity'])) {
            $errors['quantity'] = 'Количество должно быть числом';
        }

        if (isset($data['price']) && !is_numeric($data['price'])) {
            $errors['price'] = 'Цена должна быть числом';
        }

        // Валидация дат
        if (isset($data['deadline'])) {
            try {
                new \DateTime($data['deadline']);
            } catch (\Exception $e) {
                $errors['deadline'] = 'Неверный формат даты';
            }
        }

        // Валидация статусов
        $validStatuses = ['draft', 'design', 'print', 'engraving', 'workshop', 'final', 'completed', 'cancelled'];
        if (isset($data['stage']) && !in_array($data['stage'], $validStatuses)) {
            $errors['stage'] = 'Неверная стадия';
        }

        return $errors;
    }
}
