<?php

namespace App\DTOs;

class OrderAssignmentDTO
{
    public function __construct(
        public int $id,
        public int $order_id,
        public int $user_id,
        public string $role_type,
        public ?int $stage_id,
        public string $status,
        public ?string $assigned_at,
        public ?string $completed_at,
        public ?int $assigned_by = null,
        public ?UserDTO $user = null,
        public ?StageDTO $assigned_stage = null,
        public ?UserDTO $assigned_by_user = null
    ) {}

    public static function fromModel($assignment): self
    {
        // Получаем первую назначенную стадию из коллекции assignedStages
        $assignedStage = null;
        if ($assignment->assignedStages && $assignment->assignedStages->isNotEmpty()) {
            $assignedStage = $assignment->assignedStages->first();
        } elseif (isset($assignment->assigned_stage) && $assignment->assigned_stage) {
            // Fallback для обратной совместимости
            $assignedStage = $assignment->assigned_stage;
        }
        
        return new self(
            id: $assignment->id,
            order_id: $assignment->order_id,
            user_id: $assignment->user_id,
            role_type: $assignment->role_type,
            stage_id: $assignment->stage_id,
            status: $assignment->status,
            assigned_at: $assignment->assigned_at ? (is_string($assignment->assigned_at) ? $assignment->assigned_at : $assignment->assigned_at->toISOString()) : null,
            completed_at: $assignment->completed_at ? (is_string($assignment->completed_at) ? $assignment->completed_at : $assignment->completed_at->toISOString()) : null,
            assigned_by: $assignment->assigned_by,
            user: $assignment->user ? UserDTO::fromModel($assignment->user) : null,
            assigned_stage: $assignedStage ? StageDTO::fromModel($assignedStage) : null,
            assigned_by_user: $assignment->assignedBy ? UserDTO::fromModel($assignment->assignedBy) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'role_type' => $this->role_type,
            'stage_id' => $this->stage_id,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at,
            'completed_at' => $this->completed_at,
            'assigned_by' => $this->assigned_by,
            'user' => $this->user?->toArray(),
            'assigned_stage' => $this->assigned_stage?->toArray(),
            'assigned_by_user' => $this->assigned_by_user?->toArray()
        ];
    }
}
