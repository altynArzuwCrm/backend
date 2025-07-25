<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny($user)
    {
        return $user->hasAnyRole(['admin', 'manager']) || $user->assignedOrders()->exists();
    }

    public function view(User $user, Product $product)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $product->orders
            && $product->orders->flatMap->assignments->where('user_id', $user->id)->isNotEmpty();
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Product $product)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $product->orders() &&
            $product->orders->assignments()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Product $product)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function allProducts(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
