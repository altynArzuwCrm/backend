<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    private function checkAdminAccess()
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Доступ запрещён. Только администраторы могут управлять пользователями.');
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Необходима аутентификация');
        }

        $cacheKey = 'users_' . md5($request->fullUrl());
        $result = CacheService::rememberWithTags($cacheKey, 900, function () use ($request) {
            $query = User::with('roles');

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->has('role') && $request->role) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            if ($request->has('is_active') && $request->is_active !== null) {
                $query->where('is_active', $request->is_active);
            }

            // Добавляем сортировку
            $sortBy = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');

            // Проверяем, что поле для сортировки безопасно
            $allowedSortFields = ['id', 'name', 'username', 'phone', 'is_active', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('id', 'asc');
            }

            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return [
                'data' => UserResource::collection($users->items()),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ];
        }, [CacheService::TAG_USERS]);

        return response()->json($result);
    }

    public function getByRole(Request $request, string $role)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Необходима аутентификация');
        }

        $users = User::with('roles')
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('name', $role);
            })
            ->where('is_active', true)
            ->get();

        return response()->json($users);
    }

    public function show(User $user)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isAdminOrManager()) {
            abort(403, 'Доступ запрещён. Только администраторы и менеджеры могут просматривать детали пользователей.');
        }

        $userWithRoles = User::with('roles')->find($user->id);

        if (!$userWithRoles) {
            abort(404, 'Пользователь не найден');
        }

        return response()->json(new UserResource($userWithRoles));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            abort(403, 'Доступ запрещён. Только администраторы могут создавать пользователей.');
        }

        $data = $request->validate([
            'name' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'username' => 'required|string|unique:users,username',
            'phone' => 'nullable|string',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ], [
            'image.image' => 'Файл должен быть изображением.',
            'image.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg, gif или webp.',
            'image.max' => 'Размер изображения не должен превышать 10MB.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Дополнительная проверка размера файла
            if ($image->getSize() > 10 * 1024 * 1024) { // 10MB в байтах
                return response()->json([
                    'message' => 'Размер изображения превышает 10MB.',
                    'errors' => [
                        'image' => ['Размер изображения не должен превышать 10MB.']
                    ]
                ], 422);
            }

            try {
                $imagePath = $image->store('users', 'public');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Ошибка при загрузке изображения.',
                    'errors' => [
                        'image' => ['Не удалось сохранить изображение. Попробуйте другое изображение.']
                    ]
                ], 422);
            }
        }

        $newUser = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'image' => $imagePath,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $newUser->roles()->sync($data['roles']);

        return new UserResource($newUser->fresh('roles'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            abort(403, 'Доступ запрещён. Только администраторы могут редактировать пользователей.');
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'username' => 'sometimes|string|unique:users,username,' . $user->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean',
        ], [
            'image.image' => 'Файл должен быть изображением.',
            'image.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg, gif или webp.',
            'image.max' => 'Размер изображения не должен превышать 10MB.',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Дополнительная проверка размера файла
            if ($image->getSize() > 10 * 1024 * 1024) { // 10MB в байтах
                return response()->json([
                    'message' => 'Размер изображения превышает 10MB.',
                    'errors' => [
                        'image' => ['Размер изображения не должен превышать 10MB.']
                    ]
                ], 422);
            }

            // Удаляем старое изображение
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            try {
                $imagePath = $image->store('users', 'public');
                $user->image = $imagePath;
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Ошибка при загрузке изображения.',
                    'errors' => [
                        'image' => ['Не удалось сохранить изображение. Попробуйте другое изображение.']
                    ]
                ], 422);
            }
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

        return response()->json(new UserResource($user->fresh('roles')));
    }

    public function destroy(User $userToDelete)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            abort(403, 'Доступ запрещён. Только администраторы могут удалять пользователей.');
        }

        if ($currentUser->id === $userToDelete->id) {
            abort(403, 'Вы не можете удалить самого себя.');
        }

        $assignmentsCount = $userToDelete->assignments()->count();

        if ($assignmentsCount > 0) {
            return response()->json([
                'message' => "Невозможно удалить пользователя, который назначен в {$assignmentsCount} заказах"
            ], 422);
        }

        if ($userToDelete->image && Storage::disk('public')->exists($userToDelete->image)) {
            Storage::disk('public')->delete($userToDelete->image);
        }

        $userToDelete->delete();

        return response()->json(['message' => 'Пользователь удалён']);
    }

    public function toggleActive(Request $request, $userId)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            abort(403, 'Доступ запрещён. Только администраторы могут изменять активность пользователей.');
        }

        $userToToggle = User::find($userId);
        if (!$userToToggle) {
            abort(404, 'Пользователь не найден.');
        }

        if ($currentUser->id === $userToToggle->id) {
            abort(403, 'Вы не можете деактивировать самого себя.');
        }

        // Используем update() вместо save() для гарантии UPDATE запроса
        $newStatus = !$userToToggle->is_active;
        User::where('id', $userId)->update(['is_active' => $newStatus]);

        // Очищаем кэш ролей пользователя
        \Illuminate\Support\Facades\Cache::forget("user_roles_{$userId}");

        return response()->json([
            'message' => $newStatus ? 'Пользователь активирован' : 'Пользователь деактивирован',
            'is_active' => $newStatus
        ]);
    }

    public function getAllUsers(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isAdminOrManager()) {
            abort(403, 'Доступ запрещён. Только администраторы и менеджеры могут получать всех пользователей.');
        }
        $users = User::with('roles')->get();
        return UserResource::collection($users);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        // Валидация
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean'
        ]);

        // Обновление имени
        if ($request->has('name') && $request->name) {
            $user->name = $request->name;
        }

        // Обновление телефона
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        // Смена пароля
        if ($request->password) {
            if (!$request->current_password) {
                return response()->json(['message' => 'Текущий пароль обязателен для смены пароля'], 400);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Неверный текущий пароль'], 400);
            }

            $user->password = Hash::make($request->password);
        }

        // Удаление изображения
        if ($request->has('remove_image') && $request->boolean('remove_image')) {
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
            $user->image = null;
        }
        // Загрузка нового изображения
        elseif ($request->hasFile('image')) {
            // Удаляем старое изображение если есть
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $path = $request->file('image')->store('profile-images', 'public');
            $user->image = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Профиль успешно обновлен',
            'user' => new UserResource($user->load('roles'))
        ]);
    }

    public function validatePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $request->validate([
            'current_password' => 'required|string'
        ]);

        if (Hash::check($request->current_password, $user->password)) {
            return response()->json(['valid' => true]);
        } else {
            return response()->json(['valid' => false, 'message' => 'Неверный текущий пароль'], 400);
        }
    }
}
