<?php

namespace App\Repositories;

use App\Models\Role;
use App\DTOs\RoleDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class RoleRepository
{
    public function getPaginatedRoles(Request $request): LengthAwarePaginator
    {
        $query = Role::with(['stages']);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('display_name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 30);
        return $query->paginate($perPage);
    }

    public function getRoleById(int $id): ?RoleDTO
    {
        $role = Role::with(['stages'])->find($id);

        if (!$role) {
            return null;
        }

        return RoleDTO::fromModel($role);
    }

    public function createRole(array $data): RoleDTO
    {
        $role = Role::create($data);

        // Если есть стадии, привязываем их
        if (isset($data['stages']) && is_array($data['stages'])) {
            $role->stages()->attach($data['stages']);
        }

        return RoleDTO::fromModel($role->load('stages'));
    }

    public function updateRole(Role $role, array $data): RoleDTO
    {
        $role->update($data);

        // Обновляем стадии если они переданы
        if (isset($data['stages']) && is_array($data['stages'])) {
            $role->stages()->sync($data['stages']);
        }

        return RoleDTO::fromModel($role->load('stages'));
    }

    public function deleteRole(Role $role): bool
    {
        return $role->delete();
    }

    public function getAllRoles(): array
    {
        $roles = Role::with(['stages'])->get();
        return array_map([RoleDTO::class, 'fromModel'], $roles->toArray());
    }

    public function getRoleByName(string $name): ?RoleDTO
    {
        $role = Role::with(['stages'])
            ->where('name', $name)
            ->first();

        if (!$role) {
            return null;
        }

        return RoleDTO::fromModel($role);
    }

    public function getRolesByStage(int $stageId): array
    {
        $roles = Role::with(['stages'])
            ->whereHas('stages', function ($query) use ($stageId) {
                $query->where('stages.id', $stageId);
            })
            ->get();
        return array_map([RoleDTO::class, 'fromModel'], $roles->toArray());
    }

    public function searchRoles(string $searchTerm): array
    {
        $roles = Role::with(['stages'])
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('display_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            })
            ->get();
        return array_map([RoleDTO::class, 'fromModel'], $roles->toArray());
    }
}
