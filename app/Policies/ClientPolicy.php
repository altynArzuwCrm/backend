<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny($user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function allClients(User $user)
    {
        // Разрешаем всем активным пользователям
        return $user->is_active;
    }

    public function view(User $user, Client $client)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Client $client)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Client $client)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
