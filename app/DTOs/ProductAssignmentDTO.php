<?php

namespace App\DTOs;

class ProductAssignmentDTO
{
    public function __construct(
        public int $id,
        public int $product_id,
        public ?int $user_id,
        public string $role_type,
        public ?bool $is_active,
        public ?string $created_at,
        public ?string $updated_at,
        public ?UserDTO $user = null
    ) {}

    public static function fromModel($assignment): self
    {
        return new self(
            id: $assignment->id,
            product_id: $assignment->product_id,
            user_id: $assignment->user_id,
            role_type: $assignment->role_type,
            is_active: $assignment->is_active ?? false,
            created_at: $assignment->created_at ? (is_string($assignment->created_at) ? $assignment->created_at : $assignment->created_at->toISOString()) : null,
            updated_at: $assignment->updated_at ? (is_string($assignment->updated_at) ? $assignment->updated_at : $assignment->updated_at->toISOString()) : null,
            user: $assignment->user ? UserDTO::fromModel($assignment->user) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'role_type' => $this->role_type,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->user?->toArray()
        ];
    }
}
