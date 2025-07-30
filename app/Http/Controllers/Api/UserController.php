<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private function checkUserManagementAccess()
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Доступ запрещён. Только администраторы и менеджеры могут управлять пользователями.');
        }
    }

    public function index(Request $request)
    {
        $this->checkUserManagementAccess();

        $query = User::query();

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');

        // Обычная сортировка по полям пользователя
        $allowedSorts = ['id', 'name', 'username', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $perPage = $request->get('per_page', 30);
        $users = $query->with('roles')->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ], 200);
    }

    public function getByRole(Request $request, string $role)
    {
        $this->checkUserManagementAccess();

        $users = User::whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        })->where('is_active', true)->get();

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        $this->checkUserManagementAccess();

        return new UserResource($user);
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
        if (isset($data['phone'])) {
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
