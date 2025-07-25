<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function before($user, $ability)
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->assignedOrders()->exists();
    }

    public function view(User $user, Project $project)
    {
        return $project->orders()
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->exists();
    }

    public function allProjects(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Project $project)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Project $project)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
