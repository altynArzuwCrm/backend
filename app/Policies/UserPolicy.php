<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $authUser, $ability)
    {
        if ($authUser->roles->pluck('name')->contains('Админ')) {
            return true;
        }
    }

    public function viewAny(User $authUser)
    {
        return $authUser->roles->pluck('name')->contains('Админ') || $authUser->roles->pluck('name')->contains('Менеджер');
    }

    public function view(User $authUser, User $user)
    {
        return $authUser->id === $user->id || $authUser->roles->pluck('name')->contains('Админ') || $authUser->roles->pluck('name')->contains('Менеджер');
    }

    public function update(User $authUser, User $user)
    {
        return $authUser->id === $user->id || $authUser->roles->pluck('name')->contains('Админ') || $authUser->roles->pluck('name')->contains('Менеджер');
    }

    public function delete(User $authUser, User $user)
    {
        return $authUser->roles->pluck('name')->contains('Админ');
    }
}
