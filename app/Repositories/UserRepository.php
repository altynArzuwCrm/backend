<?php

namespace App\Repositories;

use App\Models\User;
use App\DTOs\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserRepository
{
    public function getPaginatedUsers(Request $request): LengthAwarePaginator
    {
        $query = User::with(['roles']);

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        // Фильтр по активности
        if ($request->filled('is_active')) {
            $isActive = $request->boolean('is_active');
            $query->where('is_active', $isActive);
        }

        // Фильтр по роли
        if ($request->filled('role')) {
            $roleName = $request->role;
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = (int) $request->get('per_page', 30);
        return $query->paginate($perPage);
    }

    public function getUserById(int $id): ?UserDTO
    {
        // Кэшируем отдельных пользователей на 1 час
        $cacheKey = 'user_' . $id;
        return Cache::remember($cacheKey, 3600, function () use ($id) {
            $user = User::with(['roles'])->find($id);

            if (!$user) {
                return null;
            }

            return UserDTO::fromModel($user);
        });
    }

    public function createUser(array $data): UserDTO
    {
        $user = User::create($data);

        // Если есть роли, привязываем их
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->attach($data['roles']);
        }

        return UserDTO::fromModel($user->load('roles'));
    }

    public function updateUser(User $user, array $data): UserDTO
    {
        $user->update($data);

        // Обновляем роли если они переданы
        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        return UserDTO::fromModel($user->load('roles'));
    }

    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    public function getAllUsers(): array
    {
        $users = User::with(['roles'])->get();
        return array_map([UserDTO::class, 'fromModel'], $users->toArray());
    }

    public function getUsersByRole(string $roleName): array
    {
        // Кэшируем пользователей по роли на 30 минут
        $cacheKey = 'users_by_role_' . $roleName;
        return Cache::remember($cacheKey, 1800, function () use ($roleName) {
            $users = User::with(['roles'])
                ->whereHas('roles', function ($query) use ($roleName) {
                    $query->where('name', $roleName);
                })
                ->get();

            // Возвращаем массив моделей напрямую, а не DTO
            // Это исправляет ошибку "Attempt to read property 'id' on array"
            // Преобразуем каждую модель в массив с правильной структурой
            return $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name
                        ];
                    })->toArray()
                ];
            })->toArray();
        });
    }

    public function getActiveUsers(): array
    {
        $users = User::with(['roles'])
            ->where('is_active', true)
            ->get();
        return array_map([UserDTO::class, 'fromModel'], $users->toArray());
    }

    public function getUserByUsername(string $username): ?UserDTO
    {
        $user = User::with(['roles'])
            ->where('username', $username)
            ->first();

        if (!$user) {
            return null;
        }

        return UserDTO::fromModel($user);
    }
}
