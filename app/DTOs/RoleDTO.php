<?php

namespace App\DTOs;

class RoleDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $display_name,
        public ?string $description,
        public ?string $color,
        public ?string $created_at,
        public ?string $updated_at
    ) {}

    public static function fromModel($role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            display_name: $role->display_name,
            description: $role->description,
            color: $role->color,
            created_at: $role->created_at ? (is_string($role->created_at) ? $role->created_at : $role->created_at->toIso8601String()) : null,
            updated_at: $role->updated_at ? (is_string($role->updated_at) ? $role->updated_at : $role->updated_at->toIso8601String()) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'color' => $this->color,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
