<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\DTOs\UserDTO;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    private function checkUserManagementAccess()
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Доступ запрещён. Только администраторы и менеджеры могут управлять пользователями.');
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Необходима аутентификация');
        }

        // Кэшируем результаты поиска на 5 минут для быстрых ответов
        $cacheKey = 'users_' . md5($request->fullUrl());
        $result = CacheService::rememberWithTags($cacheKey, 300, function () use ($request) {
            return $this->userRepository->getPaginatedUsers($request);
        }, [CacheService::TAG_USERS]);

        return response()->json($result);
    }

    public function getByRole(Request $request, string $role)
    {
        Log::info('UserController::getByRole called', [
            'role' => $role,
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()->roles->pluck('name')->toArray()
        ]);

        // Проверяем только аутентификацию, без проверки ролей
        if (!auth()->check()) {
            Log::warning('Authentication failed for getByRole', [
                'role' => $role
            ]);
            abort(401, 'Необходима аутентификация');
        }

        $users = $this->userRepository->getUsersByRole($role);

        Log::info('Users loaded by role', [
            'role' => $role,
            'users_count' => count($users)
        ]);

        return response()->json($users);
    }

    public function show(User $user)
    {
        $this->checkUserManagementAccess();

        $userDTO = $this->userRepository->getUserById($user->id);

        if (!$userDTO) {
            abort(404, 'Пользователь не найден');
        }

        return response()->json($userDTO->toArray());
    }

    public function store(Request $request)
    {
        $this->checkUserManagementAccess();

        $data = $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|max:5120',
            'username' => 'required|string|unique:users,username',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users', 'public');
        }

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'image' => $imagePath,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->roles()->sync($data['roles']);

        return new UserResource($user->fresh('roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkUserManagementAccess();

        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'username' => 'sometimes|string|unique:users,username,' . $user->id,
            'image' => 'nullable|image|max:5120',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $imagePath = $request->file('image')->store('users', 'public');
            $user->image = $imagePath;
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['username'])) {
            $user->username = $data['username'];
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        if (isset($data['is_active'])) {
            $user->is_active = $data['is_active'];
        }

        $user->save();

        if (isset($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $this->checkUserManagementAccess();

        // Проверяем все назначения пользователя (не только активные)
        $assignmentsCount = $user->assignments()->count();

        if ($assignmentsCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить пользователя, который назначен в {$assignmentsCount} заказах"
            ], 422);
        }

        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        return response()->json(['message' => 'Пользователь удалён']);
    }

    public function toggleActive(User $user)
    {
        $this->checkUserManagementAccess();

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'message' => $user->is_active ? 'Пользователь активирован' : 'Пользователь деактивирован',
            'is_active' => $user->is_active
        ]);
    }

    public function getAllUsers(Request $request)
    {
        $this->checkUserManagementAccess();
        $users = User::all();
        return UserResource::collection($users);
    }
}
