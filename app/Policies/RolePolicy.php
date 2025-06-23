<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Role $role)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Role $role)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Role $role)
    {
        return $user->hasRole('admin');
    }
}
