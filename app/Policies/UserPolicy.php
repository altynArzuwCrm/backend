<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    public function before($authUser, $ability)
    {
        if ($authUser->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
    }

    public function viewAny($authUser)
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $authUser, User $user)
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $authUser, User $user)
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $authUser, User $user)
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }
}
