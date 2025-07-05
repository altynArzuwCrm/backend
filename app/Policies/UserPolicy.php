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
        if (in_array($authUser->role, ['admin', 'manager'])) {
            return true;
        }
    }

    public function viewAny(User $authUser)
    {
        return in_array($authUser->role, ['admin', 'manager']);
    }

    public function view(User $authUser, User $user)
    {
        return in_array($authUser->role, ['admin', 'manager']);
    }

    public function update(User $authUser, User $user)
    {
        return in_array($authUser->role, ['admin', 'manager']);
    }

    public function delete(User $authUser, User $user)
    {
        return in_array($authUser->role, ['admin', 'manager']);
    }
}
