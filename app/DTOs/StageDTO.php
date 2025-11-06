<?php

namespace App\DTOs;

class StageDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $display_name,
        public ?string $color,
        public ?int $order,
        public ?string $created_at,
        public ?string $updated_at,
        public array $roles = []
    ) {}

    public static function fromModel($stage): self
    {
        return new self(
            id: $stage->id,
            name: $stage->name,
            display_name: $stage->display_name,
            color: $stage->color,
            order: $stage->order ?? 0, // Используем 0 по умолчанию, если null
            created_at: $stage->created_at ? (is_string($stage->created_at) ? $stage->created_at : $stage->created_at->toISOString()) : null,
            updated_at: $stage->updated_at ? (is_string($stage->updated_at) ? $stage->updated_at : $stage->updated_at->toISOString()) : null,
            roles: $stage->roles ? array_map([RoleDTO::class, 'fromModel'], $stage->roles->all()) : []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'color' => $this->color,
            'order' => $this->order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => array_map(fn($role) => $role->toArray(), $this->roles)
        ];
    }
}
