<?php

namespace App\Policies;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $user->assignedOrders()->exists();
    }

    public function view(User $user, Project $project)
    {
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        return $project->orders()
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->exists();
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function update(User $user, Project $project)
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    public function delete(User $user, Project $project)
    {
        return in_array($user->role, ['admin', 'manager']);
    }
} 