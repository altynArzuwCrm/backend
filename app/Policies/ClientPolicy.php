<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function allClients(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function view(User $user, Client $client)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Client $client)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user, Client $client)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}
