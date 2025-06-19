<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function view(User $authUser, User $user)
    {
        if ($authUser->role === 'admin') {
            return true;
        }

        if ($authUser->role === 'manager' && $user->role === 'executor') {
            return true;
        }

        return $authUser->id === $user->id;
    }

    public function create(User $authUser)
    {
        return in_array($authUser->role, ['admin', 'manager']);
    }

    public function update(User $authUser, User $user)
    {
        if ($authUser->role === 'admin') {
            return true;
        }

        if ($authUser->role === 'manager' && $user->role === 'executor')
        {
            return true;
        }

        return $authUser->id === $user->id;
    }

    public function delete(User $authUser, User $user)
    {
        if ($authUser->role === 'admin') {
            return true;
        }

        return $authUser->role === 'manager' && $user->role === 'executor';
    }

}
