<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    private function checkUserManagementAccess()
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'Доступ запрещён. Только администраторы и менеджеры могут управлять пользователями.');
        }
    }

    public function index(Request $request)
    {
        $this->checkUserManagementAccess();

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['name', 'role', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'has_more_pages' => $users->hasMorePages(),
                'has_previous_page' => $users->previousPageUrl() !== null,
                'has_next_page' => $users->nextPageUrl() !== null,
            ]
        ]);
    }

    public function getByRole(Request $request, string $role)
    {
        $this->checkUserManagementAccess();

        $allowedRoles = ['admin', 'manager', 'designer', 'print_operator', 'workshop_worker'];

        if (!in_array($role, $allowedRoles)) {
            abort(400, 'Недопустимая роль');
        }

        $users = User::where('role', $role)->get();

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
            'role' => 'required|in:admin,manager,designer,print_operator,workshop_worker',
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
            'role' => $data['role'],
        ]);

        return new UserResource($user);
    }

    public function update(Request $request, User $user)
    {
        $this->checkUserManagementAccess();

        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'image' => 'nullable|image|max:5120',
            'role' => 'sometimes|required|in:admin,manager,designer,print_operator,workshop_worker',
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
        if (isset($data['phone'])) {
            $user->phone = $data['phone'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        if (isset($data['role'])) {
            $user->role = $data['role'];
        }
        $user->save();

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $this->checkUserManagementAccess();
        if ($user->image && Storage::disk('public')->exists($user->image)) {
            Storage::disk('public')->delete($user->image);
        }

        $user->delete();

        return response()->json(['message' => 'Пользователь удалён']);
    }
}
