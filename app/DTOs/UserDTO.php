<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public int $id,
        public string $username,
        public string $name,
        public ?string $phone,
        public ?bool $is_active,
        public ?string $created_at,
        public ?string $updated_at,
        public array $roles = []
    ) {}

    public static function fromModel($user): self
    {
        return new self(
            id: $user->id,
            username: $user->username,
            name: $user->name,
            phone: $user->phone,
            is_active: $user->is_active ?? false,
            created_at: $user->created_at ? (is_string($user->created_at) ? $user->created_at : $user->created_at->toISOString()) : null,
            updated_at: $user->updated_at ? (is_string($user->updated_at) ? $user->updated_at : $user->updated_at->toISOString()) : null,
            roles: $user->roles ? array_map([RoleDTO::class, 'fromModel'], $user->roles->all()) : []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => array_map(fn($role) => $role->toArray(), $this->roles)
        ];
    }
}
