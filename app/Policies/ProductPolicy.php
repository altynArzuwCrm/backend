<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function view(User $user, Product $product)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Product $product)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user, Product $product)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}
